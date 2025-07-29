<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\Security;

final class ProductController extends AbstractController
{
    #[Route('/api/products', name: 'app_product')]
    #[Security("is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')", message: "Accès non autorisé")]
    public function getAllProducts(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
      $productList = $productRepository->findAll();
      $jsonProductList = $serializer->serialize($productList, 'json');
        return new JsonResponse(
          $jsonProductList, Response::HTTP_OK,[], true
        ); 
    }

    #[Route('/api/product/{id}', name: 'app_product_id')]
    public function getProductById(ProductRepository $productRepository, SerializerInterface $serializer, int $id): JsonResponse
    {
      $product = $productRepository->find($id);
      if ($product) {
        
      $jsonProduct = $serializer->serialize($product, 'json');
        return new JsonResponse(
          $jsonProduct, Response::HTTP_OK,[], true
        ); 
      }
      return new JsonResponse(
        'Ce produit n\'existe pas', Response::HTTP_NOT_FOUND
      );
    }
}
