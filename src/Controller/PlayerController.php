<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class PlayerController extends AbstractController
{
    #[Route('/api/players', name: 'api_player_list', methods: ['GET'])]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $data = array_map(fn (User $user) => $this->serializePlayer($user), $userRepository->findAll());

        return $this->json($data);
    }

    #[Route('/api/players/{id}', name: 'api_player_show', methods: ['GET'])]
    public function show(?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Player not found'], 404);
        }

        return $this->json($this->serializePlayer($user));
    }

    #[Route('/register', name: 'api_player_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $payload = $this->getPayload($request);
        if ($payload === null) {
            return $this->json(['error' => 'Invalid JSON body'], 400);
        }

        $missingField = $this->getMissingField($payload, ['lastName', 'firstName', 'username', 'emailAddress', 'password']);
        if ($missingField !== null) {
            return $this->json(['error' => sprintf('Missing field: %s', $missingField)], 400);
        }

        $user = new User();
        $user->setLastName((string) $payload['lastName']);
        $user->setFirstName((string) $payload['firstName']);
        $user->setUsername((string) $payload['username']);
        $user->setEmailAddress((string) $payload['emailAddress']);
        $user->setStatus((string) ($payload['status'] ?? 'active'));
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, (string) $payload['password']));

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json($this->serializePlayer($user), 201);
    }

    #[Route('/api/players/{id}', name: 'api_player_update', methods: ['PUT'])]
    public function update(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $payload = $this->getPayload($request);
        if ($payload === null) {
            return $this->json(['error' => 'Invalid JSON body'], 400);
        }

        if (array_key_exists('lastName', $payload)) {
            $user->setLastName((string) $payload['lastName']);
        }
        if (array_key_exists('firstName', $payload)) {
            $user->setFirstName((string) $payload['firstName']);
        }
        if (array_key_exists('username', $payload)) {
            $user->setUsername((string) $payload['username']);
        }
        if (array_key_exists('emailAddress', $payload)) {
            $user->setEmailAddress((string) $payload['emailAddress']);
        }
        if (array_key_exists('status', $payload)) {
            $user->setStatus((string) $payload['status']);
        }
        if (array_key_exists('roles', $payload) && is_array($payload['roles'])) {
            $user->setRoles($payload['roles']);
        }
        if (array_key_exists('password', $payload)) {
            $user->setPassword($passwordHasher->hashPassword($user, (string) $payload['password']));
        }

        $entityManager->flush();

        return $this->json($this->serializePlayer($user));
    }

    #[Route('/api/players/{id}', name: 'api_player_delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(null, 204);
    }

    private function serializePlayer(User $user): array
    {
        return [
            'id' => $user->getId(),
            'lastName' => $user->getLastName(),
            'firstName' => $user->getFirstName(),
            'username' => $user->getUsername(),
            'emailAddress' => $user->getEmailAddress(),
            'status' => $user->getStatus(),
            'roles' => $user->getRoles(),
        ];
    }

    private function getPayload(Request $request): ?array
    {
        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : null;
    }

    private function getMissingField(array $payload, array $requiredFields): ?string
    {
        foreach ($requiredFields as $requiredField) {
            if (!array_key_exists($requiredField, $payload)) {
                return $requiredField;
            }
        }

        return null;
    }
}
