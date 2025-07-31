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
use Symfony\Contracts\Cache\Adapter\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/products', name: 'app_product')]

final class ProductController extends AbstractController
{
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

      $productList = $cache->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit) {
        echo("L'élément n'est pas encore en cache.\n ");
        $item->tag('productsCache');
        return $productRepository->findAllWithPagination($page, $limit);
      });

      $jsonProductList = $serializer->serialize($productList, 'json',['groups' => 'getProducts']);
        return new JsonResponse($jsonProductList, Response::HTTP_OK,[], true); 
    }

    #[Route('/{id}', name: '_show')]
    #[IsGranted('ROLE_USER', message: "Accès non autorisé")]
    public function getProductById( SerializerInterface $serializer, Product $product): JsonResponse
    {       
      // dd($this->versioningService->getVersion());
      $jsonProduct = $serializer->serialize($product, 'json');
        return new JsonResponse(
          $jsonProduct, Response::HTTP_OK,[], true
        ); 
    }
}
