<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\CustomerRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\Security;
use App\Service\VersioningService;



final class UserController extends AbstractController
{
    #[Route('/api/users', name: 'app_user', methods: ['GET'])]
    // #[Security("is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')", message: "Accès non autorisé")]
    public function getAllUsers(UserRepository $userRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 5);
        
        $userList = $userRepository->findAllWithPagination($page, $limit);
        $jsonUserList = $serializer->serialize($userList, 'json', ['groups' => 'getUser']);
        return new JsonResponse(
            $jsonUserList, Response::HTTP_OK, [], true
        );
    }
    
    #[Route('/api/user/{id}', name: 'app_user_id', methods: ['GET'])]
    public function getUserById(UserRepository $userRepository, SerializerInterface $serializer, int $id): JsonResponse
    {
        $user = $userRepository->find($id);
        if ($user) {
            $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUser']);
            return new JsonResponse(
                $jsonUser, Response::HTTP_OK, [], true
            );
        }
        return new JsonResponse(
            'Cet utilisateur n\'existe pas', Response::HTTP_NOT_FOUND
        );
    }

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

    #[Route('/api/user', name: 'create_user', methods: ['POST'])]
    public function createUser( Request $request, 
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

