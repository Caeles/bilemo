<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\Security;


final class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'app_customer_list')]
    public function getAllCustomers(CustomerRepository $customerRepository, SerializerInterface $serializer): JsonResponse
    {
        $customersList = $customerRepository->findAll();
        $jsonCustomersList = $serializer->serialize($customersList, 'json', ['groups' => 'getCustomer']);
        return new JsonResponse(
            $jsonCustomersList, Response::HTTP_OK, [], true
        );
    }
    
    #[Route('/api/customers/{id}', name: 'app_customer_show')]
    public function getCustomerById(Customer $customer, SerializerInterface $serializer): JsonResponse
    {
        $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' => 'getCustomer']);
        return new JsonResponse(
            $jsonCustomer, Response::HTTP_OK, [], true
        );
    }
    
    #[Route('/api/customers/{id}/users', name: 'app_customer_users')]
    public function getCustomerUsers(Customer $customer, SerializerInterface $serializer): JsonResponse
    {
        $users = $customer->getUsers();
        $jsonUsers = $serializer->serialize($users, 'json', ['groups' => 'getCustomer']);
        return new JsonResponse(
            $jsonUsers, Response::HTTP_OK, [], true
        );
    }
}
