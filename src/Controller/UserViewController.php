<?php

namespace App\Controller;

use App\Entity\Registration;
use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use App\Repository\RegistrationRepository;
use App\Repository\SportMatchRepository;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class UserViewController extends AbstractController
{
    #[Route('/dashboard', name: 'app_user_dashboard', methods: ['GET'])]
    public function dashboard(TournamentRepository $tournamentRepository, RegistrationRepository $registrationRepository, SportMatchRepository $sportMatchRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Résumé pour le dashboard
        $myRegistrations = $registrationRepository->findBy(['player' => $user], ['registrationDate' => 'DESC'], 5);
        
        $myMatches = $sportMatchRepository->createQueryBuilder('m')
            ->where('m.player1 = :user OR m.player2 = :user')
            ->setParameter('user', $user)
            ->orderBy('m.matchDate', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('player/dashboard.html.twig', [
            'registrations' => $myRegistrations,
            'matches' => $myMatches,
        ]);
    }

    #[Route('/tournaments', name: 'app_user_tournaments', methods: ['GET'])]
    public function listTournaments(TournamentRepository $tournamentRepository): Response
    {
        return $this->render('tournament/index.html.twig', [
            'tournaments' => $tournamentRepository->findAll(),
        ]);
    }

    #[Route('/my-matches', name: 'app_user_matches', methods: ['GET'])]
    public function myMatches(SportMatchRepository $sportMatchRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $myMatches = $sportMatchRepository->createQueryBuilder('m')
            ->where('m.player1 = :user OR m.player2 = :user')
            ->setParameter('user', $user)
            ->orderBy('m.matchDate', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('sport_match/index.html.twig', [
            'matches' => $myMatches,
        ]);
    }

    #[Route('/my-registrations', name: 'app_user_registrations', methods: ['GET'])]
    public function myRegistrations(RegistrationRepository $registrationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $myRegistrations = $registrationRepository->findBy(['player' => $user], ['registrationDate' => 'DESC']);

        return $this->render('registration/index.html.twig', [
            'registrations' => $myRegistrations,
        ]);
    }

    #[Route('/tournaments/{id}/register', name: 'app_user_tournament_register', methods: ['POST'])]
    public function register(Tournament $tournament, EntityManagerInterface $entityManager, RegistrationRepository $registrationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $existing = $registrationRepository->findOneBy(['player' => $user, 'tournament' => $tournament]);
        if ($existing) {
            $this->addFlash('error', 'Vous êtes déjà inscrit à ce tournoi.');
            return $this->redirectToRoute('app_user_registrations');
        }

        $registration = new Registration();
        $registration->setPlayer($user);
        $registration->setTournament($tournament);
        $registration->setStatus('pending');
        $registration->setRegistrationDate(new \DateTime());

        $entityManager->persist($registration);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande d\'inscription a été envoyée.');
        return $this->redirectToRoute('app_user_registrations');
    }

    #[Route('/matches/{id}/score', name: 'app_user_match_score', methods: ['POST'])]
    public function updateScore(SportMatch $match, Request $request, EntityManagerInterface $entityManager, NotificationService $notificationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $score = $request->request->get('score');
        if ($score === null || $score === '') {
            $this->addFlash('error', 'Le score est requis.');
            return $this->redirectToRoute('app_user_matches');
        }

        $opponent = null;
        if ($match->getPlayer1() === $user) {
            $match->setScorePlayer1((int)$score);
            $opponent = $match->getPlayer2();
        } elseif ($match->getPlayer2() === $user) {
            $match->setScorePlayer2((int)$score);
            $opponent = $match->getPlayer1();
        } else {
            $this->addFlash('error', 'Vous ne participez pas à ce match.');
            return $this->redirectToRoute('app_user_matches');
        }

        // Notification à l'adversaire
        if ($opponent) {
            $notificationService->sendScoreUpdateReminder($match, $opponent);
        }

        // Logique auto-finish
        if ($match->getScorePlayer1() !== null && $match->getScorePlayer2() !== null) {
            $match->setStatus('finished');
        } else {
            $match->setStatus('in_progress');
        }

        $entityManager->flush();

        $this->addFlash('success', 'Score mis à jour et adversaire notifié.');
        return $this->redirectToRoute('app_user_matches');
    }
}
