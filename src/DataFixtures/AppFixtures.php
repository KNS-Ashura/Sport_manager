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
        $admin = $this->createUser(
            lastName: 'Admin',
            firstName: 'Super',
            username: 'admin',
            emailAddress: 'admin@sport.local',
            plainPassword: 'Admin123!',
            roles: ['ROLE_ADMIN'],
            status: 'active'
        );

        $tom = $this->createUser('Gi', 'Tom', 'tomgi', 'tom@sport.local', 'User123!', ['ROLE_USER'], 'active');
        $lea = $this->createUser('Martin', 'Lea', 'lea', 'lea@sport.local', 'User123!', ['ROLE_USER'], 'active');
        $yan = $this->createUser('Durand', 'Yan', 'yan', 'yan@sport.local', 'User123!', ['ROLE_USER'], 'active');
        $emma = $this->createUser('Dupont', 'Emma', 'emma', 'emma@sport.local', 'User123!', ['ROLE_USER'], 'active');

        foreach ([$admin, $tom, $lea, $yan, $emma] as $user) {
            $manager->persist($user);
        }

        $tournament1 = new Tournament();
        $tournament1->setTournamentName('Spring Cup 2026');
        $tournament1->setSport('Football');
        $tournament1->setDescription('Tournoi principal de printemps.');
        $tournament1->setLocation('Paris');
        $tournament1->setStartDate(new \DateTime('2026-05-10 10:00:00'));
        $tournament1->setEndDate(new \DateTime('2026-05-20 18:00:00'));
        $tournament1->setMaxParticipants(16);
        $tournament1->setOrganizer($admin);

        $tournament2 = new Tournament();
        $tournament2->setTournamentName('Summer Open 2026');
        $tournament2->setSport('Basketball');
        $tournament2->setDescription('Tournoi d ete ouvert a tous.');
        $tournament2->setLocation('Lyon');
        $tournament2->setStartDate(new \DateTime('2026-06-05 09:00:00'));
        $tournament2->setEndDate(new \DateTime('2026-06-12 20:00:00'));
        $tournament2->setMaxParticipants(8);
        $tournament2->setOrganizer($admin);

        $manager->persist($tournament1);
        $manager->persist($tournament2);

        $registrations = [
            $this->createRegistration($tom, $tournament1, 'confirmed'),
            $this->createRegistration($lea, $tournament1, 'confirmed'),
            $this->createRegistration($yan, $tournament1, 'pending'),
            $this->createRegistration($emma, $tournament1, 'confirmed'),
            $this->createRegistration($tom, $tournament2, 'confirmed'),
            $this->createRegistration($lea, $tournament2, 'confirmed'),
        ];

        foreach ($registrations as $registration) {
            $manager->persist($registration);
        }

        $match1 = new SportMatch();
        $match1->setTournament($tournament1);
        $match1->setPlayer1($tom);
        $match1->setPlayer2($lea);
        $match1->setMatchDate(new \DateTime('2026-05-11 14:00:00'));
        $match1->setScorePlayer1(2);
        $match1->setScorePlayer2(1);
        $match1->setStatus('finished');

        $match2 = new SportMatch();
        $match2->setTournament($tournament1);
        $match2->setPlayer1($emma);
        $match2->setPlayer2($tom);
        $match2->setMatchDate(new \DateTime('2026-05-13 16:00:00'));
        $match2->setScorePlayer1(0);
        $match2->setScorePlayer2(0);
        $match2->setStatus('pending');

        $manager->persist($match1);
        $manager->persist($match2);

        $manager->flush();
    }

    private function createUser(
        string $lastName,
        string $firstName,
        string $username,
        string $emailAddress,
        string $plainPassword,
        array $roles,
        string $status
    ): User {
        $user = new User();
        $user->setLastName($lastName);
        $user->setFirstName($firstName);
        $user->setUsername($username);
        $user->setEmailAddress($emailAddress);
        $user->setRoles($roles);
        $user->setStatus($status);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        return $user;
    }

    private function createRegistration(User $player, Tournament $tournament, string $status): Registration
    {
        $registration = new Registration();
        $registration->setPlayer($player);
        $registration->setTournament($tournament);
        $registration->setStatus($status);
        $registration->setRegistrationDate(new \DateTime());

        return $registration;
    }
}
