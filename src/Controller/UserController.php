<?php

namespace App\Controller;

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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Annotations as OA;



final class UserController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer l'ensemble des utilisateurs.
     * 
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des utilisateurs",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUser"}))
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
     *     description="Le nombre d'utilisateurs que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Users")
     * @ApiSecurity(name="Bearer")
     */
    #[Route('/api/users', name: 'app_user', methods: ['GET'])]
    // #[Security("is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')", message: "Accès non autorisé")]
    public function getAllUsers(UserRepository $userRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 5);
        
        $userList = $userRepository->findAllWithPagination($page, $limit);
        $context = SerializationContext::create()->setGroups(['getUser']);
        $jsonUserList = $serializer->serialize($userList, 'json', $context);
        return new JsonResponse(
            $jsonUserList, Response::HTTP_OK, [], true
        );
    }
    
    // #[Route('/api/users', name: 'app_user_create', methods: ['POST'])]
    // #[IsGranted('ROLE_ADMIN', message: "Accès non autorisé")]
    // public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    // {
    //     $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        
    //     // Vous pourriez ajouter des validations ici
        
    //     $em->persist($user);
    //     $em->flush();
        
    //     $context = SerializationContext::create()->setGroups(['getUser']);
    //     $jsonUser = $serializer->serialize($user, 'json', $context);
        
    //     $location = $urlGenerator->generate('app_user_id', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
    //     return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    // }
    
    /**
     * Cette méthode permet de récupérer les détails d'un utilisateur.
     * 
     * @OA\Response(
     *     response=200,
     *     description="Retourne les détails d'un utilisateur",
     *     @OA\JsonContent(
     *        ref=@Model(type=User::class, groups={"getUser"})
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Cet utilisateur n'existe pas"
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID de l'utilisateur",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Users")
     * @ApiSecurity(name="Bearer")
     */
    #[Route('/api/user/{id}', name: 'app_user_id', methods: ['GET'])]
    public function getUserById(UserRepository $userRepository, SerializerInterface $serializer, int $id): JsonResponse
    {
        $user = $userRepository->find($id);
        if ($user) {
            $context = SerializationContext::create()->setGroups(['getUser']);
            $jsonUser = $serializer->serialize($user, 'json', $context);
            return new JsonResponse(
                $jsonUser, Response::HTTP_OK, [], true
            );
        }
        return new JsonResponse(
            'Cet utilisateur n\'existe pas', Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Cette méthode permet de supprimer un utilisateur.
     * 
     * @OA\Response(
     *     response=204,
     *     description="Utilisateur supprimé avec succès"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Cet utilisateur n'existe pas"
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID de l'utilisateur à supprimer",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Users")
     * @ApiSecurity(name="Bearer")
     */
    #[Route('/api/user/{id}', name: 'delete_user_id', methods: ['DELETE'])]
    public function deleteUser(UserRepository $userRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $userRepository->find($id);
        if ($user) {
            $customer = $user->getCustomer();
                        $entityManager->remove($user);
            $entityManager->flush();
            return new JsonResponse(
                'Utilisateur supprimé', Response::HTTP_NO_CONTENT
            );
        }
        
        return new JsonResponse(
            'Ce utilisateur n\'existe pas', Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Cette méthode permet de créer un utilisateur.
     * 
     * @OA\RequestBody(
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 example="john_doe"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 example="password123"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="john@example.com"
     *             ),
     *             @OA\Property(
     *                 property="customerId",
     *                 type="integer",
     *                 example=1
     *             )
     *         )
     *     )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Utilisateur créé avec succès",
     *     @OA\JsonContent(
     *         ref=@Model(type=User::class, groups={"getUser"})
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Erreur de validation des données"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Le client n'existe pas"
     * )
     * @OA\Tag(name="Users")
     * @ApiSecurity(name="Bearer")
     */
    #[Route('/api/user', name: 'create_user', methods: ['POST'])]
    public function createUser(
        Request $request, 
        SerializerInterface $serializer, 
        EntityManagerInterface $entityManager, 
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        CustomerRepository $customerRepository,
        UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $content = $request->toArray();
        
         if (empty($user->getRoles())) {
            $user->setRoles(['ROLE_USER']);
        }
        
        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            )
        );

        $errors = $validator->validate($user);
        if(count($errors) > 0){
            return new JsonResponse([
                $serializer->serialize($errors, 'json', ['groups' => 'getUser']) 
            ], Response::HTTP_BAD_REQUEST);
        }

         $idCustomer = $content['id_customer'] ?? ($content['customer']['id'] ?? -1);
            
        
            if ($idCustomer > 0) {
                $customer = $customerRepository->find($idCustomer);
                if ($customer) {
                    $user->setCustomer($customer);
                } else {
                    return new JsonResponse(['error' => 'Client non trouvé avec ID ' . $idCustomer], Response::HTTP_BAD_REQUEST);
                }
            } else {
                return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
            }
            

            $entityManager->persist($user);
            $entityManager->flush();
            $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUser']);
            $location = $urlGenerator->generate('app_user_id', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse(
                $jsonUser, Response::HTTP_CREATED, ['Location' => $location], true
            );
    }

}

