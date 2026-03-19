<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('/api/users', name: 'users_create', methods: ['POST'])]
    public function create(
        Request $request,
        UserRepository $userRepository,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $manager
    ): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);

        if ($existingUser) {
            return new JsonResponse(
                json_encode(['error' => 'Cette adresse email est déjà utilisée.']),
                JsonResponse::HTTP_CONFLICT,
                [],
                true
            );
        }

        $errors = $validator->validate($user);

        if ($errors->count() > 0) {
            return new JsonResponse(
                $this->serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $hashedPassword = $hasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);
        $manager->persist($user);
        $manager->flush();

        $context = SerializationContext::create()->setGroups(['user_detail']);
        $jsonUser = $this->serializer->serialize($user, 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);	
    }

    #[Route('/api/users/{id}', name: 'users_update', methods: ['PUT'], requirements: ['id' => '\d+'],)]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour ajouter un conseil.')]
    public function update(
        Request $request,
        UserRepository $userRepository,
        User $user,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $manager
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $existingUser = $userRepository->findOneBy(['email' => $data['email']]);

            if ($existingUser && $existingUser !== $user) {
                return new JsonResponse(
                    json_encode(['error' => 'Cette adresse email est déjà utilisée.']),
                    JsonResponse::HTTP_CONFLICT,
                    [],
                    true
                );
            }

            $user->setEmail($data['email']);
        }

        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        if (isset($data['city'])) {
            $user->setCity($data['city']);
        }

        $errors = $validator->validate($user);

        if ($errors->count() > 0) {
            return new JsonResponse(
                $this->serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        if (isset($data['password'])) {
            $hashedPassword = $hasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $manager->persist($user);
        $manager->flush();

        $context = SerializationContext::create()->setGroups(['user_detail', 'user_edit']);
        $jsonUser = $this->serializer->serialize($user, 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);	
    }
}
