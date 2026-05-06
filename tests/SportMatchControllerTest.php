<?php

namespace App\Tests\Controller;

use App\Entity\Registration;
use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\TournamentRepository;
use App\Repository\SportMatchRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SportMatchControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $userRepository;
    private $tournamentRepository;
    private $matchRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->client->getContainer()->get(UserRepository::class);
        $this->tournamentRepository = $this->client->getContainer()->get(TournamentRepository::class);
        $this->matchRepository = $this->client->getContainer()->get(SportMatchRepository::class);
    }

    public function testPlayerCannotUpdateOpponentScore(): void
    {
        // 1. Récupération des données depuis les fixtures
        $player1 = $this->userRepository->findOneBy(['username' => 'joueur1']);
        $match = $this->matchRepository->findOneBy(['player1' => $player1]);
        $tournamentId = $match->getTournament()->getId();
        $matchId = $match->getId();

        // 2. Connexion avec le joueur 1
        $this->client->loginUser($player1);

        // 3. Joueur 1 tente de modifier le score du joueur 2
        $this->client->request('PUT', "/api/tournaments/{$tournamentId}/sport-matchs/{$matchId}", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'scorePlayer2' => 5
        ]));

        $response = $this->client->getResponse();
        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('Player 1 cannot update player 2 score', $response->getContent());
    }

    public function testCreateMatchWithUnconfirmedPlayerFails(): void
    {
        // 1. Récupération d'un tournoi et de l'admin
        $admin = $this->userRepository->findOneBy(['username' => 'admin']);
        $tournament = $this->tournamentRepository->findAll()[0];
        $tournamentId = $tournament->getId();

        // 2. Création d'un joueur 5 sans inscription
        $unconfirmedPlayer = new User();
        $unconfirmedPlayer->setFirstName('Unconfirmed');
        $unconfirmedPlayer->setLastName('Player');
        $unconfirmedPlayer->setUsername('unconfirmed');
        $unconfirmedPlayer->setEmailAddress('unconfirmed@test.com');
        $unconfirmedPlayer->setPassword('password');
        $unconfirmedPlayer->setRoles(['ROLE_USER']);
        $unconfirmedPlayer->setStatus('active');
        $this->entityManager->persist($unconfirmedPlayer);

        // Inscription en statut 'pending' (non confirmé)
        $registration = new Registration();
        $registration->setTournament($tournament);
        $registration->setPlayer($unconfirmedPlayer);
        $registration->setStatus('pending');
        $registration->setRegistrationDate(new \DateTime());
        $this->entityManager->persist($registration);
        
        $this->entityManager->flush();

        $player1 = $this->userRepository->findOneBy(['username' => 'joueur1']); // Lui est confirmé

        // 3. L'admin tente de créer le match
        $this->client->loginUser($admin);
        $this->client->request('POST', "/api/tournaments/{$tournamentId}/sport-matchs", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'player1Id' => $player1->getId(),
            'player2Id' => $unconfirmedPlayer->getId(),
            'matchDate' => (new \DateTime('+1 day'))->format(\DateTimeInterface::ATOM),
        ]));

        $response = $this->client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Both players must have confirmed registrations', $response->getContent());
    }

    public function testAutoFinishMatchWhenBothScoresSubmitted(): void
    {
        // 1. Récupération des données depuis les fixtures
        $admin = $this->userRepository->findOneBy(['username' => 'admin']);
        $match = $this->matchRepository->findAll()[1]; // Prendre un autre match
        $tournamentId = $match->getTournament()->getId();
        $matchId = $match->getId();

        // Vérifie qu'il est en pending au début
        $this->assertSame('pending', $match->getStatus());

        // 2. L'admin met à jour les DEUX scores
        $this->client->loginUser($admin);
        $this->client->request('PUT', "/api/tournaments/{$tournamentId}/sport-matchs/{$matchId}", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'scorePlayer1' => 10,
            'scorePlayer2' => 5
        ]));

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        
        // 3. Vérification du statut en base
        $updatedMatch = $this->matchRepository->find($matchId);
        $this->assertSame('finished', $updatedMatch->getStatus());
    }
}
