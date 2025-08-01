<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\VersioningService;
use Doctrine\Migrations\Version\Version;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

#[Route('/api/products', name: 'app_product')]

final class ProductController extends AbstractController
{
    
    /**
     * Cette méthode permet de récupérer l'ensemble des produits.
     * 
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des produits",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"getProducts"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'éléments que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Products")
     * 
     * @param ProductRepository $productRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    public function __construct(private VersioningService $versioningService) {}

    #[Route('', name: '_list')]
    #[IsGranted('ROLE_USER', message: "Accès non autorisé")]
    public function getAllProducts(
        ProductRepository $productRepository, 
        SerializerInterface $serializer, 
        Request $request, 
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
      $page = $request->query->get('page', 1);
      $limit = $request->query->get('limit', 5);

      $idCache = "getAllProducts_" . $page . '_' . $limit;
      //permet de savoir si l'élément vient d'être mis en cache
      $fromCache = true;
      
      $productList = $cache->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, &$fromCache) {
        $fromCache = false;
        $item->tag('productsCache');
        return $productRepository->findAllWithPagination($page, $limit);
      });

      $jsonProductList = $serializer->serialize($productList, 'json', ['groups' => 'getProducts']);
      $response = new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
      
      if (!$fromCache) {
        $response->headers->set('X-Cache-Status', "Le contenu n'est pas encore en cache!");
      }
      
      return $response;
    }

   
    #[Route('/{id}', name: '_show')]
    #[IsGranted('ROLE_USER', message: "Accès non autorisé")]
     /**
     * Cette méthode permet de récupérer les détails d'un produit.
     * 
     * @OA\Response(
     *     response=200,
     *     description="Retourne les détails d'un produit",
     *     @OA\JsonContent(
     *        ref=@Model(type=Product::class, groups={"getProducts"})
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID du produit",
     *     required=true,
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Products")
     *  * 
     * @param ProductRepository $productRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    public function getProductById( 
      SerializerInterface $serializer, 
      Product $product
      ): JsonResponse
    {       
      $jsonProduct = $serializer->serialize($product, 'json');
        return new JsonResponse(
          $jsonProduct, Response::HTTP_OK,[], true
        ); 
    }
}
