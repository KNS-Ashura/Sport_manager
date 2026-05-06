<?php

namespace App\Controller;

use App\Entity\Registration;
use App\Entity\Tournament;
use App\Entity\User;
use App\Repository\RegistrationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/api/tournaments/{id}/registrations', name: 'api_registration_list', methods: ['GET'])]
    public function list(Tournament $tournament, RegistrationRepository $registrationRepository): JsonResponse
    {
        $registrations = $registrationRepository->findBy(['tournament' => $tournament]);
        $data = array_map(fn (Registration $registration) => $this->serializeRegistration($registration), $registrations);

        return $this->json($data);
    }

    #[Route('/api/tournaments/{id}/registrations', name: 'api_registration_create', methods: ['POST'])]
    public function create(Tournament $tournament, Request $request, UserRepository $userRepository, RegistrationRepository $registrationRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !array_key_exists('playerId', $payload)) {
            return $this->json(['error' => 'Missing field: playerId'], 400);
        }

        $player = $userRepository->find((int) $payload['playerId']);
        if (!$player instanceof User) {
            return $this->json(['error' => 'Player not found'], 404);
        }

        $existing = $registrationRepository->findOneBy(['tournament' => $tournament, 'player' => $player]);
        if ($existing instanceof Registration) {
            return $this->json(['error' => 'Player is already registered for this tournament'], 409);
        }

        $registration = new Registration();
        $registration->setTournament($tournament);
        $registration->setPlayer($player);
        $registration->setRegistrationDate(new \DateTime());
        $registration->setStatus((string) ($payload['status'] ?? 'pending'));

        $entityManager->persist($registration);
        $entityManager->flush();

        return $this->json($this->serializeRegistration($registration), 201);
    }

    #[Route('/api/tournaments/{idTournament}/registrations/{idRegistration}', name: 'api_registration_delete', methods: ['DELETE'])]
    public function delete(Tournament $tournament, int $idRegistration, RegistrationRepository $registrationRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $registration = $registrationRepository->find($idRegistration);
        if (!$registration instanceof Registration || $registration->getTournament()?->getId() !== $tournament->getId()) {
            return $this->json(['error' => 'Registration not found for this tournament'], 404);
        }

        $entityManager->remove($registration);
        $entityManager->flush();

        return $this->json(null, 204);
    }

    private function serializeRegistration(Registration $registration): array
    {
        return [
            'id' => $registration->getId(),
            'tournamentId' => $registration->getTournament()?->getId(),
            'playerId' => $registration->getPlayer()?->getId(),
            'registrationDate' => $registration->getRegistrationDate()?->format(\DateTimeInterface::ATOM),
            'status' => $registration->getStatus(),
        ];
    }
}
