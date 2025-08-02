<?php

namespace App\Controller;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\CustomerRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\Security;
use App\Service\VersioningService;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Security\Http\Attribute\IsGranted;




#[OA\Tag(name: 'Users')]
final class UserController extends AbstractController
{
// Liste des utilisateurs
    #[Route('/api/users', name: 'app_user', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users',
        summary: 'Liste des utilisateurs',
        description: "Cette méthode permet de récupérer l'ensemble des utilisateurs.",
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: "La page que l'on veut récupérer",
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: "Le nombre d'utilisateurs que l'on veut récupérer",
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des utilisateurs',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(type: User::class, groups: ['getUser'])
            )
        )
    )]
    public function getAllUsers(
        UserRepository $userRepository,
        SerializerInterface $serializer,
        Request $request
    ): JsonResponse {
        $page  = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 5);

        $users       = $userRepository->findAllWithPagination($page, $limit);
        $context     = SerializationContext::create()->setGroups(['getUser']);
        $jsonContent = $serializer->serialize($users, 'json', $context);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    // Détails d'un utilisateur
    #[Route('/api/user/{id}', name: 'app_user_id', methods: ['GET'])]
    #[OA\Get(
        path: '/api/user/{id}',
        summary: "Détails d'un utilisateur",
        description: "Cette méthode permet de récupérer les détails d'un utilisateur.",
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: "ID de l'utilisateur",
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: "Retourne les détails d'un utilisateur",
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ['getUser'])
        )
    )]
    #[OA\Response(response: 404, description: "Cet utilisateur n'existe pas")]
    public function getUserById(
        UserRepository $userRepository,
        SerializerInterface $serializer,
        int $id
    ): JsonResponse {
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse('Cet utilisateur n\'existe pas', Response::HTTP_NOT_FOUND);
        }

        $context     = SerializationContext::create()->setGroups(['getUser']);
        $jsonContent = $serializer->serialize($user, 'json', $context);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
        }
    // Création d'un utilisateur
    #[Route('/api/user', name: 'create_user', methods: ['POST'])]
    #[OA\Post(
        path: '/api/user',
        summary: 'Créer un utilisateur',
        description: 'Cette méthode permet de créer un utilisateur.',
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'firstName',   type: 'string', example: 'Alice'),
                new OA\Property(property: 'lastName',   type: 'string', example: 'BOSSLIN'),
                new OA\Property(property: 'Email',      type: 'string', format: 'email', example: 'alice@bilemo.com'),
                new OA\Property(property: 'password',   type: 'string', example: 'password123'),
                new OA\Property(property: 'customerId', type: 'integer', example: 61, description: 'Optionnel'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Utilisateur créé avec succès',
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ['getUser'])
        )
    )]
    #[OA\Response(response: 400, description: 'Erreur de validation des données')]
    #[OA\Response(response: 404, description: "Le client n'existe pas")]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        CustomerRepository $customerRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
    
        $user     = $serializer->deserialize($request->getContent(), User::class, 'json');
        $payload  = $request->toArray();

       
        if (empty($user->getRoles())) {
            $user->setRoles(['ROLE_USER']);
        }

       
        $user->setPassword(
            $passwordHasher->hashPassword($user, $user->getPassword())
        );

      
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsJson = $serializer->serialize($errors, 'json');
            return new JsonResponse($errorsJson, Response::HTTP_BAD_REQUEST, [], true);
        }

    
        $customerId = $payload['customerId'] ?? $payload['id_customer'] ?? null;

        if ($customerId) {
            $customer = $customerRepository->find($customerId);
            if (!$customer) {
                return new JsonResponse(
                    ['error' => "Client non trouvé avec l'ID $customerId"],
                    Response::HTTP_NOT_FOUND
                );
            }
            $user->setCustomer($customer);
        }
        // Si aucun customerId n'est fourni, l'utilisateur est créé sans être associé à un client


      
        $entityManager->persist($user);
        $entityManager->flush();

 
        $context = \JMS\Serializer\SerializationContext::create()->setGroups(['getUser']);
        $jsonUser  = $serializer->serialize($user, 'json', $context);
        $location  = $urlGenerator->generate(
            'app_user_id',
            ['id' => $user->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ['Location' => $location], true);
    }
    // Suppression d'un utilisateur
    #[Route('/api/user/{id}', name: 'delete_user_id', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/user/{id}',
        summary: 'Supprimer un utilisateur',
        description: 'Cette méthode permet de supprimer un utilisateur.',
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: "ID de l'utilisateur à supprimer",
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(response: 204, description: 'Utilisateur supprimé avec succès')]
    #[OA\Response(response: 404, description: "Cet utilisateur n'existe pas")]
    public function deleteUser(
        UserRepository $userRepository,
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse('Cet utilisateur n\'existe pas', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse('Utilisateur supprimé', Response::HTTP_NO_CONTENT);
    }

 
}