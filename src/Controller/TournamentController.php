<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\User;
use App\Repository\TournamentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class TournamentController extends AbstractController
{
    #[Route('/api/tournaments', name: 'api_tournament_list', methods: ['GET'])]
    public function list(TournamentRepository $tournamentRepository): JsonResponse
    {
        $data = array_map(fn (Tournament $tournament) => $this->serializeTournament($tournament), $tournamentRepository->findAll());

        return $this->json($data);
    }

    #[Route('/api/tournaments/{id}', name: 'api_tournament_show', methods: ['GET'])]
    public function show(?Tournament $tournament): JsonResponse
    {
        if (!$tournament) {
            return $this->json(['error' => 'Tournament not found'], 404);
        }

        return $this->json($this->serializeTournament($tournament));
    }

    #[Route('/api/tournaments', name: 'api_tournament_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON body'], 400);
        }

        foreach (['tournamentName', 'startDate', 'endDate', 'description', 'maxParticipants', 'sport', 'organizerId'] as $requiredField) {
            if (!array_key_exists($requiredField, $payload)) {
                return $this->json(['error' => sprintf('Missing field: %s', $requiredField)], 400);
            }
        }

        $organizer = $userRepository->find((int) $payload['organizerId']);
        if (!$organizer instanceof User) {
            return $this->json(['error' => 'Organizer not found'], 404);
        }

        try {
            $tournament = new Tournament();
            $tournament->setTournamentName((string) $payload['tournamentName']);
            $tournament->setStartDate(new \DateTime((string) $payload['startDate']));
            $tournament->setEndDate(new \DateTime((string) $payload['endDate']));
            $tournament->setDescription((string) $payload['description']);
            $tournament->setLocation(isset($payload['location']) ? (string) $payload['location'] : null);
            $tournament->setMaxParticipants((int) $payload['maxParticipants']);
            $tournament->setSport((string) $payload['sport']);
            $tournament->setOrganizer($organizer);

            if (isset($payload['winnerId'])) {
                $winner = $userRepository->find((int) $payload['winnerId']);
                if (!$winner instanceof User) {
                    return $this->json(['error' => 'Winner not found'], 404);
                }
                $tournament->setWinner($winner);
            }

            $entityManager->persist($tournament);
            $entityManager->flush();

            return $this->json($this->serializeTournament($tournament), 201);
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid data format'], 400);
        }
    }

    #[Route('/api/tournaments/{id}', name: 'api_tournament_update', methods: ['PUT'])]
    public function update(?Tournament $tournament, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if (!$tournament) {
            return $this->json(['error' => 'Tournament not found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON body'], 400);
        }

        try {
            if (array_key_exists('tournamentName', $payload)) {
                $tournament->setTournamentName((string) $payload['tournamentName']);
            }
            if (array_key_exists('startDate', $payload)) {
                $tournament->setStartDate(new \DateTime((string) $payload['startDate']));
            }
            if (array_key_exists('endDate', $payload)) {
                $tournament->setEndDate(new \DateTime((string) $payload['endDate']));
            }
            if (array_key_exists('description', $payload)) {
                $tournament->setDescription((string) $payload['description']);
            }
            if (array_key_exists('location', $payload)) {
                $tournament->setLocation($payload['location'] !== null ? (string) $payload['location'] : null);
            }
            if (array_key_exists('maxParticipants', $payload)) {
                $tournament->setMaxParticipants((int) $payload['maxParticipants']);
            }
            if (array_key_exists('sport', $payload)) {
                $tournament->setSport((string) $payload['sport']);
            }
            if (array_key_exists('organizerId', $payload)) {
                $organizer = $userRepository->find((int) $payload['organizerId']);
                if (!$organizer instanceof User) {
                    return $this->json(['error' => 'Organizer not found'], 404);
                }
                $tournament->setOrganizer($organizer);
            }
            if (array_key_exists('winnerId', $payload)) {
                if ($payload['winnerId'] === null) {
                    $tournament->setWinner(null);
                } else {
                    $winner = $userRepository->find((int) $payload['winnerId']);
                    if (!$winner instanceof User) {
                        return $this->json(['error' => 'Winner not found'], 404);
                    }
                    $tournament->setWinner($winner);
                }
            }

            $entityManager->flush();

            return $this->json($this->serializeTournament($tournament));
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid data format'], 400);
        }
    }

    #[Route('/api/tournaments/{id}', name: 'api_tournament_delete', methods: ['DELETE'])]
    public function delete(?Tournament $tournament, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if (!$tournament) {
            return $this->json(['error' => 'Tournament not found'], 404);
        }

        $entityManager->remove($tournament);
        $entityManager->flush();

        return $this->json(null, 204);
    }

    private function serializeTournament(Tournament $tournament): array
    {
        return [
            'id' => $tournament->getId(),
            'tournamentName' => $tournament->getTournamentName(),
            'startDate' => $tournament->getStartDate()?->format(\DateTimeInterface::ATOM),
            'endDate' => $tournament->getEndDate()?->format(\DateTimeInterface::ATOM),
            'location' => $tournament->getLocation(),
            'description' => $tournament->getDescription(),
            'maxParticipants' => $tournament->getMaxParticipants(),
            'sport' => $tournament->getSport(),
            'organizerId' => $tournament->getOrganizer()?->getId(),
            'winnerId' => $tournament->getWinner()?->getId(),
        ];
    }
}
