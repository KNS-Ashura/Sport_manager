<?php

namespace App\DataFixtures;

use App\Entity\Registration;
use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Création de l'admin
        $admin = new User();
        $admin->setFirstName('Admin');
        $admin->setLastName('Super');
        $admin->setUsername('admin');
        $admin->setEmailAddress('admin@sportmanager.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setStatus('active');
        $manager->persist($admin);

        // 2. Création de joueurs
        $players = [];
        for ($i = 1; $i <= 4; $i++) {
            $player = new User();
            $player->setFirstName('Joueur ' . $i);
            $player->setLastName('Test');
            $player->setUsername('joueur' . $i);
            $player->setEmailAddress('joueur' . $i . '@sportmanager.com');
            $player->setRoles(['ROLE_USER']);
            $player->setPassword($this->passwordHasher->hashPassword($player, 'password'));
            $player->setStatus('active');
            $manager->persist($player);
            $players[] = $player;
        }

        // 3. Création d'un tournoi
        $tournament = new Tournament();
        $tournament->setTournamentName('Tournoi de Printemps');
        $tournament->setDescription('Le grand tournoi de printemps.');
        $tournament->setLocation('Stade de France');
        $tournament->setMaxParticipants(8);
        $tournament->setSport('Tennis');
        $tournament->setStartDate(new \DateTime('-1 day'));
        $tournament->setEndDate(new \DateTime('+1 week'));
        $tournament->setOrganizer($admin);
        $manager->persist($tournament);

        // 4. Inscription des joueurs au tournoi
        foreach ($players as $player) {
            $registration = new Registration();
            $registration->setTournament($tournament);
            $registration->setPlayer($player);
            $registration->setRegistrationDate(new \DateTime());
            $registration->setStatus('confirmed');
            $manager->persist($registration);
        }

        // 5. Création de quelques matchs
        $match1 = new SportMatch();
        $match1->setTournament($tournament);
        $match1->setPlayer1($players[0]);
        $match1->setPlayer2($players[1]);
        $match1->setMatchDate(new \DateTime('+1 hour'));
        $match1->setStatus('pending');
        $manager->persist($match1);

        $match2 = new SportMatch();
        $match2->setTournament($tournament);
        $match2->setPlayer1($players[2]);
        $match2->setPlayer2($players[3]);
        $match2->setMatchDate(new \DateTime('+2 hours'));
        $match2->setStatus('pending');
        $manager->persist($match2);

        $manager->flush();
    }
}
