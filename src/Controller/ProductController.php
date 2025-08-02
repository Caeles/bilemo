<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\VersioningService;
use Doctrine\Migrations\Version\Version;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;

#[OA\Tag(name: 'Products')]                 
#[Route('/api/products', name: 'app_product')]
final class ProductController extends AbstractController
{
    public function __construct(private VersioningService $versioningService) {}

// Liste des produits
    #[Route('', name: '_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Accès non autorisé')]
    #[OA\Get(
        path: '/api/products',
        summary: 'Liste des produits',
        description: "Cette méthode permet de récupérer l'ensemble des produits.",
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: "La page que l'on veut récupérer",
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: "Le nombre d'éléments que l'on veut récupérer",
                schema: new OA\Schema(type: 'integer', default: 5)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Retourne la liste des produits',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: new Model(type: Product::class, groups: ['getProducts'])
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Accès non autorisé'),
        ]
    )]
    public function getAllProducts(
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        Request $request,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $page  = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 5);

        $idCache   = "getAllProducts_{$page}_{$limit}";
        $fromCache = true;

        $productList = $cache->get(
            $idCache,
            function (ItemInterface $item) use ($productRepository, $page, $limit, &$fromCache) {
                $fromCache = false;
                $item->tag('productsCache');
                return $productRepository->findAllWithPagination($page, $limit);
            }
        );

        $context = \JMS\Serializer\SerializationContext::create()->setGroups(['getProducts']);
        $jsonList = $serializer->serialize($productList, 'json', $context);
        $response = new JsonResponse($jsonList, Response::HTTP_OK, [], true);

        if (!$fromCache) {
            $response->headers->set('X-Cache-Status', "Le contenu n'est pas encore en cache !");
        }

        return $response;
    }

    // Détails d'un produit
    #[Route('/{id}', name: '_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Accès non autorisé')]
    #[OA\Get(
        path: '/api/products/{id}',
        summary: "Détails d'un produit",
        description: "Cette méthode permet de récupérer les détails d'un produit."
    )]
    #[OA\Response(
        response: 200,
        description: "Retourne les détails d'un produit",
        content: new OA\JsonContent(
            ref: new Model(type: Product::class, groups: ['getProducts'])
        )
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du produit',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    public function getProductById(
        SerializerInterface $serializer,
        Product $product
    ): JsonResponse {
        $context = \JMS\Serializer\SerializationContext::create()->setGroups(['getProducts']);
        $jsonProduct = $serializer->serialize($product, 'json', $context);

        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }
}