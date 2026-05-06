<?php

namespace App\Controller;

use App\Entity\Registration;
use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use App\Repository\RegistrationRepository;
use App\Repository\SportMatchRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SportMatchController extends AbstractController
{
    #[Route('/api/tournaments/{id}/sport-matchs', name: 'api_sport_match_list', methods: ['GET'])]
    public function list(Tournament $tournament, SportMatchRepository $sportMatchRepository): JsonResponse
    {
        $matches = $sportMatchRepository->findBy(['tournament' => $tournament]);
        $data = array_map(fn (SportMatch $match) => $this->serializeSportMatch($match), $matches);

        return $this->json($data);
    }

    #[Route('/api/tournaments/{id}/sport-matchs', name: 'api_sport_match_create', methods: ['POST'])]
    public function create(Tournament $tournament, Request $request, UserRepository $userRepository, RegistrationRepository $registrationRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $payload = $this->getPayload($request);
        if ($payload === null) {
            return $this->json(['error' => 'Invalid JSON body'], 400);
        }

        foreach (['player1Id', 'player2Id', 'matchDate'] as $requiredField) {
            if (!array_key_exists($requiredField, $payload)) {
                return $this->json(['error' => sprintf('Missing field: %s', $requiredField)], 400);
            }
        }

        $player1 = $userRepository->find((int) $payload['player1Id']);
        $player2 = $userRepository->find((int) $payload['player2Id']);
        if (!$player1 instanceof User || !$player2 instanceof User) {
            return $this->json(['error' => 'Player not found'], 404);
        }

        if ($player1->getId() === $player2->getId()) {
            return $this->json(['error' => 'A player cannot play against themselves'], 400);
        }

        $registration1 = $registrationRepository->findOneBy(['tournament' => $tournament, 'player' => $player1, 'status' => 'confirmed']);
        $registration2 = $registrationRepository->findOneBy(['tournament' => $tournament, 'player' => $player2, 'status' => 'confirmed']);
        if (!$registration1 instanceof Registration || !$registration2 instanceof Registration) {
            return $this->json(['error' => 'Both players must have confirmed registrations for this tournament'], 400);
        }

        $matchDate = $this->parseDate((string) $payload['matchDate']);
        if ($matchDate === null) {
            return $this->json(['error' => 'Invalid matchDate format'], 400);
        }

        $sportMatch = new SportMatch();
        $sportMatch->setTournament($tournament);
        $sportMatch->setPlayer1($player1);
        $sportMatch->setPlayer2($player2);
        $sportMatch->setMatchDate($matchDate);
        $sportMatch->setStatus((string) ($payload['status'] ?? 'pending'));
        $sportMatch->setScorePlayer1((int) ($payload['scorePlayer1'] ?? 0));
        $sportMatch->setScorePlayer2((int) ($payload['scorePlayer2'] ?? 0));

        $entityManager->persist($sportMatch);
        $entityManager->flush();

        return $this->json($this->serializeSportMatch($sportMatch), 201);
    }

    #[Route('/api/tournaments/{idTournament}/sport-matchs/{idSportMatchs}', name: 'api_sport_match_show', methods: ['GET'])]
    public function show(Tournament $tournament, int $idSportMatchs, SportMatchRepository $sportMatchRepository): JsonResponse
    {
        $sportMatch = $sportMatchRepository->find($idSportMatchs);
        if (!$sportMatch instanceof SportMatch || $sportMatch->getTournament()?->getId() !== $tournament->getId()) {
            return $this->json(['error' => 'Sport match not found for this tournament'], 404);
        }

        return $this->json($this->serializeSportMatch($sportMatch));
    }

    #[Route('/api/tournaments/{idTournament}/sport-matchs/{idSportMatchs}', name: 'api_sport_match_update', methods: ['PUT'])]
    public function update(Tournament $tournament, int $idSportMatchs, Request $request, SportMatchRepository $sportMatchRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $sportMatch = $sportMatchRepository->find($idSportMatchs);
        if (!$sportMatch instanceof SportMatch || $sportMatch->getTournament()?->getId() !== $tournament->getId()) {
            return $this->json(['error' => 'Sport match not found for this tournament'], 404);
        }

        $payload = $this->getPayload($request);
        if ($payload === null) {
            return $this->json(['error' => 'Invalid JSON body'], 400);
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $currentUser = $this->getUser();

        if (!$isAdmin) {
            if (!$currentUser instanceof User) {
                return $this->json(['error' => 'Forbidden'], 403);
            }

            $isPlayer1 = $sportMatch->getPlayer1()?->getId() === $currentUser->getId();
            $isPlayer2 = $sportMatch->getPlayer2()?->getId() === $currentUser->getId();
            if (!$isPlayer1 && !$isPlayer2) {
                return $this->json(['error' => 'Only match participants or admins can update scores'], 403);
            }

            if ($isPlayer1 && array_key_exists('scorePlayer2', $payload)) {
                return $this->json(['error' => 'Player 1 cannot update player 2 score'], 403);
            }
            if ($isPlayer2 && array_key_exists('scorePlayer1', $payload)) {
                return $this->json(['error' => 'Player 2 cannot update player 1 score'], 403);
            }
            if (array_key_exists('status', $payload) || array_key_exists('matchDate', $payload)) {
                return $this->json(['error' => 'Only admins can update match metadata'], 403);
            }
        }

        if (array_key_exists('scorePlayer1', $payload)) {
            $sportMatch->setScorePlayer1((int) $payload['scorePlayer1']);
        }
        if (array_key_exists('scorePlayer2', $payload)) {
            $sportMatch->setScorePlayer2((int) $payload['scorePlayer2']);
        }
        if ($isAdmin && array_key_exists('status', $payload)) {
            $sportMatch->setStatus((string) $payload['status']);
        }
        if ($isAdmin && array_key_exists('matchDate', $payload)) {
            $matchDate = $this->parseDate((string) $payload['matchDate']);
            if ($matchDate === null) {
                return $this->json(['error' => 'Invalid matchDate format'], 400);
            }
            $sportMatch->setMatchDate($matchDate);
        }

        if ($sportMatch->getScorePlayer1() !== 0 || $sportMatch->getScorePlayer2() !== 0) {
            if (!$isAdmin) {
                $sportMatch->setStatus('in_progress');
            }
        }
        if (array_key_exists('scorePlayer1', $payload) && array_key_exists('scorePlayer2', $payload)) {
            $sportMatch->setStatus('finished');
        }

        $entityManager->flush();

        return $this->json($this->serializeSportMatch($sportMatch));
    }

    #[Route('/api/tournaments/{idTournament}/sport-matchs/{idSportMatchs}', name: 'api_sport_match_delete', methods: ['DELETE'])]
    public function delete(Tournament $tournament, int $idSportMatchs, SportMatchRepository $sportMatchRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $sportMatch = $sportMatchRepository->find($idSportMatchs);
        if (!$sportMatch instanceof SportMatch || $sportMatch->getTournament()?->getId() !== $tournament->getId()) {
            return $this->json(['error' => 'Sport match not found for this tournament'], 404);
        }

        $entityManager->remove($sportMatch);
        $entityManager->flush();

        return $this->json(null, 204);
    }

    private function serializeSportMatch(SportMatch $sportMatch): array
    {
        return [
            'id' => $sportMatch->getId(),
            'tournamentId' => $sportMatch->getTournament()?->getId(),
            'player1Id' => $sportMatch->getPlayer1()?->getId(),
            'player2Id' => $sportMatch->getPlayer2()?->getId(),
            'matchDate' => $sportMatch->getMatchDate()?->format(\DateTimeInterface::ATOM),
            'scorePlayer1' => $sportMatch->getScorePlayer1(),
            'scorePlayer2' => $sportMatch->getScorePlayer2(),
            'status' => $sportMatch->getStatus(),
        ];
    }

    private function getPayload(Request $request): ?array
    {
        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : null;
    }

    private function parseDate(string $value): ?\DateTime
    {
        try {
            return new \DateTime($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
