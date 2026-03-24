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

    /**
     * Crée un nouveau compte.
     * 
     * Corps JSON attendu :
     *   - email (string) : adresse email (identifiant unique)
     *   - password (string) : mot de passe (8 caractères minimum)
     *   - city (string) : nom de la ville
     * 
     * Exemple :
     * {
     *     "email": "dave@ecogarden.com",
     *     "password": "password",
     *     "city": "Bitche"
     * }
     * 
     * @param Request $request
     * @param UserRepository $userRepository
     * @param ValidatorInterface $validator
     * @param UserPasswordHasherInterface $hasher
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     */
    #[Route('/users', name: 'users_create', methods: ['POST'])]
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

    /**
     * Met à jour un compte selon son id.
     * 
     * Corps JSON attendu :
     *   - email (string) : nouvelle adresse email (optionnel)
     *   - roles (array de string) : rôles de l'utilisateur (optionnel)
     *   - password (string) : nouveau mot de passe (optionnel)
     *   - city (string) : nouvelle ville (optionnel)
     * 
     * Exemple :
     * {
     *     "email": "dave@ecogarden.com",
     *     "roles": ["ROLE_ADMIN"],
     *     "password": "password",
     *     "city": "Bitche"
     * }
     * 
     * @param Request $request
     * @param UserRepository $userRepository
     * @param User $user
     * @param ValidatorInterface $validator
     * @param UserPasswordHasherInterface $hasher
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     */
    #[Route('/users/{id}', name: 'users_update', methods: ['PUT'], requirements: ['id' => '\d+'],)]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour un compte.')]
    public function update(
        Request $request,
        UserRepository $userRepository,
        User $user,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $manager
    ): JsonResponse
    {
        $updatedUser = $this->serializer->deserialize($request->getContent(), User::class, 'json');

        if ($updatedUser->getEmail() !== null) {
            $existingUser = $userRepository->findOneBy(['email' => $updatedUser->getEmail()]);

            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return new JsonResponse(
                    json_encode(['error' => 'Cette adresse email est déjà utilisée.']),
                    JsonResponse::HTTP_CONFLICT,
                    [],
                    true
                );
            }

            $user->setEmail($updatedUser->getEmail());
        }

        $content = $request->toArray();
        $roles = $content['roles'] ?? null;

        if (is_array($roles)) {
            $user->setRoles($roles);
        }

        if ($updatedUser->getPassword() !== null) {
            $user->setPassword($updatedUser->getPassword());
        }

        if ($updatedUser->getCity() !== null) {
            $user->setCity($updatedUser->getCity());
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

        if ($updatedUser->getPassword() !== null) {
            $hashedPassword = $hasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
        }

        $manager->persist($user);
        $manager->flush();

        $context = SerializationContext::create()->setGroups(['user_detail', 'user_edit']);
        $jsonUser = $this->serializer->serialize($user, 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);	
    }

    /**
     * Supprime un compte selon son id.
     * 
     * @param EntityManagerInterface $manager
     * @param User $user
     * @return JsonResponse
     */
    #[Route('/users/{id}', name: 'users_delete', methods: ['DELETE'], requirements: ['id' => '\d+'],)]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un compte.')]
    public function delete(EntityManagerInterface $manager, User $user): JsonResponse
    {
        $manager->remove($user);
        $manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
