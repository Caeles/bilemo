<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\Security;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Annotations as OA;


final class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'app_customer_list')]
    /**
     * Cette méthode permet de récupérer l'ensemble des clients.
     * 
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des clients",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Customer::class, groups={"getCustomer"}))
     *     )
     * )
     * @OA\Tag(name="Customers")
     * @ApiSecurity(name="Bearer")
     */
    public function getAllCustomers(CustomerRepository $customerRepository, SerializerInterface $serializer): JsonResponse
    {
        $customersList = $customerRepository->findAll();
        $jsonCustomersList = $serializer->serialize($customersList, 'json', ['groups' => 'getCustomer']);
        return new JsonResponse(
            $jsonCustomersList, Response::HTTP_OK, [], true
        );
    }
    
    #[Route('/api/customers/{id}', name: 'app_customer_show')]
    /**
     * Cette méthode permet de récupérer les détails d'un client.
     * 
     * @OA\Response(
     *     response=200,
     *     description="Retourne les détails d'un client",
     *     @OA\JsonContent(
     *        ref=@Model(type=Customer::class, groups={"getCustomer"})
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID du client",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Customers")
     * @ApiSecurity(name="Bearer")
     */
    public function getCustomerById(Customer $customer, SerializerInterface $serializer): JsonResponse
    {
        $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' => 'getCustomer']);
        return new JsonResponse(
            $jsonCustomer, Response::HTTP_OK, [], true
        );
    }
    
    #[Route('/api/customers/{id}/users', name: 'app_customer_users')]
    /**
     * Cette méthode permet de récupérer les utilisateurs d'un client spécifique.
     * 
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des utilisateurs du client",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getCustomer"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID du client",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Customers")
     * @ApiSecurity(name="Bearer")
     */
    public function getCustomerUsers(Customer $customer, SerializerInterface $serializer): JsonResponse
    {
        $users = $customer->getUsers();
        $jsonUsers = $serializer->serialize($users, 'json', ['groups' => 'getCustomer']);
        return new JsonResponse(
            $jsonUsers, Response::HTTP_OK, [], true
        );
    }
}
