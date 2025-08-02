<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\Security;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Customers')]
final class CustomerController extends AbstractController
{
    // Liste des clients
    #[Route('/api/customers', name: 'app_customer_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/customers',
        summary: 'Liste des clients',
        description: "Cette méthode permet de récupérer l'ensemble des clients.",
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des clients',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(type: Customer::class, groups: ['getCustomer'])
            )
        )
    )]
    public function getAllCustomers(
        CustomerRepository $customerRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $customers   = $customerRepository->findAll();
        $context = \JMS\Serializer\SerializationContext::create()->setGroups(['getCustomer']);
        $jsonContent = $serializer->serialize($customers, 'json', $context);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    // Détails d'un client
    #[Route('/api/customers/{id}', name: 'app_customer_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/customers/{id}',
        summary: "Détails d'un client",
        description: "Cette méthode permet de récupérer les détails d'un client.",
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du client',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: "Retourne les détails d'un client",
        content: new OA\JsonContent(
            ref: new Model(type: Customer::class, groups: ['getCustomer'])
        )
    )]
    public function getCustomerById(
        Customer $customer,
        SerializerInterface $serializer
    ): JsonResponse {
        $context = \JMS\Serializer\SerializationContext::create()->setGroups(['getCustomer']);
        $jsonContent = $serializer->serialize($customer, 'json', $context);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    // Utilisateurs d'un client
    #[Route('/api/customers/{id}/users', name: 'app_customer_users', methods: ['GET'])]
    #[OA\Get(
        path: '/api/customers/{id}/users',
        summary: 'Liste des utilisateurs d’un client',
        description: 'Cette méthode permet de récupérer les utilisateurs d’un client spécifique.',
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du client',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des utilisateurs du client',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(type: User::class, groups: ['getCustomer'])
            )
        )
    )]
    public function getCustomerUsers(
        Customer $customer,
        SerializerInterface $serializer
    ): JsonResponse {
        $users       = $customer->getUsers();
        $context = \JMS\Serializer\SerializationContext::create()->setGroups(['getCustomer']);
        $jsonContent = $serializer->serialize($users, 'json', $context);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }
}