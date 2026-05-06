<?php

namespace App\Controller;

use App\Entity\Registration;
use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Repository\RegistrationRepository;
use App\Repository\SportMatchRepository;
use App\Repository\TournamentRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Contrôleur gérant l'interface d'administration en Twig.
 * Réservé aux utilisateurs possédant le ROLE_ADMIN.
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(TournamentRepository $tournamentRepository, RegistrationRepository $registrationRepository, SportMatchRepository $sportMatchRepository, UserRepository $userRepository): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'tournamentsCount' => count($tournamentRepository->findAll()),
            'registrationsCount' => count($registrationRepository->findAll()),
            'matchesCount' => count($sportMatchRepository->findAll()),
            'usersCount' => count($userRepository->findAll()),
        ]);
    }

    #[Route('/tournaments', name: 'admin_tournaments', methods: ['GET'])]
    public function tournaments(TournamentRepository $tournamentRepository, UserRepository $userRepository): Response
    {
        return $this->render('admin/tournaments.html.twig', [
            'tournaments' => $tournamentRepository->findAll(),
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/tournaments/create', name: 'admin_tournaments_create', methods: ['POST'])]
    public function createTournament(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator): RedirectResponse
    {
        try {
            $organizer = $userRepository->find((int) $request->request->get('organizerId'));
            if (!$organizer) {
                $this->addFlash('error', 'Organizer introuvable.');
                return $this->redirectToRoute('admin_tournaments');
            }

            $tournament = new Tournament();
            $tournament->setTournamentName((string) $request->request->get('tournamentName'));
            $tournament->setStartDate(new \DateTime((string) $request->request->get('startDate')));
            $tournament->setEndDate(new \DateTime((string) $request->request->get('endDate')));
            $tournament->setDescription((string) $request->request->get('description'));
            $tournament->setLocation($request->request->get('location') ?: null);
            $tournament->setMaxParticipants((int) $request->request->get('maxParticipants'));
            $tournament->setSport((string) $request->request->get('sport'));
            $tournament->setOrganizer($organizer);

            $winnerId = $request->request->get('winnerId');
            if ($winnerId) {
                $winner = $userRepository->find((int) $winnerId);
                if ($winner) {
                    $tournament->setWinner($winner);
                }
            }

            $errors = $validator->validate($tournament);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('admin_tournaments');
            }

            $entityManager->persist($tournament);
            $entityManager->flush();
            $this->addFlash('success', 'Tournoi cree.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de la creation du tournoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_tournaments');
    }

    #[Route('/tournaments/{id}/edit', name: 'admin_tournaments_edit', methods: ['GET'])]
    public function editTournament(Tournament $tournament, UserRepository $userRepository): Response
    {
        return $this->render('admin/tournament_edit.html.twig', [
            'tournament' => $tournament,
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/tournaments/{id}/update', name: 'admin_tournaments_update', methods: ['POST'])]
    public function updateTournament(Tournament $tournament, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator, NotificationService $notificationService): RedirectResponse
    {
        try {
            $organizer = $userRepository->find((int) $request->request->get('organizerId'));
            if (!$organizer) {
                $this->addFlash('error', 'Organizer introuvable.');
                return $this->redirectToRoute('admin_tournaments_edit', ['id' => $tournament->getId()]);
            }

            $tournament->setTournamentName((string) $request->request->get('tournamentName'));
            $tournament->setStartDate(new \DateTime((string) $request->request->get('startDate')));
            $tournament->setEndDate(new \DateTime((string) $request->request->get('endDate')));
            $tournament->setDescription((string) $request->request->get('description'));
            $tournament->setLocation($request->request->get('location') ?: null);
            $tournament->setMaxParticipants((int) $request->request->get('maxParticipants'));
            $tournament->setSport((string) $request->request->get('sport'));
            $tournament->setOrganizer($organizer);

            $winnerId = $request->request->get('winnerId');
            $oldWinner = $tournament->getWinner();
            if ($winnerId) {
                $winner = $userRepository->find((int) $winnerId);
                if ($winner) {
                    $tournament->setWinner($winner);
                    // Notification si un nouveau vainqueur est désigné
                    if (!$oldWinner || $oldWinner->getId() !== $winner->getId()) {
                        $notificationService->sendTournamentWinnerNotification($tournament);
                    }
                }
            } else {
                $tournament->setWinner(null);
            }

            $errors = $validator->validate($tournament);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('admin_tournaments_edit', ['id' => $tournament->getId()]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Tournoi mis a jour.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de la mise a jour : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_tournaments');
    }

    #[Route('/tournaments/{id}/delete', name: 'admin_tournaments_delete', methods: ['POST'])]
    public function deleteTournament(Tournament $tournament, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($tournament);
        $entityManager->flush();
        $this->addFlash('success', 'Tournoi supprime.');

        return $this->redirectToRoute('admin_tournaments');
    }

    #[Route('/registrations', name: 'admin_registrations', methods: ['GET'])]
    public function registrations(RegistrationRepository $registrationRepository, TournamentRepository $tournamentRepository, UserRepository $userRepository): Response
    {
        return $this->render('admin/registrations.html.twig', [
            'registrations' => $registrationRepository->findAll(),
            'tournaments' => $tournamentRepository->findAll(),
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/registrations/create', name: 'admin_registrations_create', methods: ['POST'])]
    public function createRegistration(Request $request, UserRepository $userRepository, TournamentRepository $tournamentRepository, RegistrationRepository $registrationRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $player = $userRepository->find((int) $request->request->get('playerId'));
        $tournament = $tournamentRepository->find((int) $request->request->get('tournamentId'));

        if (!$player || !$tournament) {
            $this->addFlash('error', 'Joueur ou tournoi introuvable.');
            return $this->redirectToRoute('admin_registrations');
        }

        $existing = $registrationRepository->findOneBy(['player' => $player, 'tournament' => $tournament]);
        if ($existing) {
            $this->addFlash('error', 'Inscription deja existante.');
            return $this->redirectToRoute('admin_registrations');
        }

        $registration = new Registration();
        $registration->setPlayer($player);
        $registration->setTournament($tournament);
        $registration->setStatus((string) ($request->request->get('status') ?: 'pending'));
        $registration->setRegistrationDate(new \DateTime());

        $entityManager->persist($registration);
        $entityManager->flush();
        $this->addFlash('success', 'Inscription creee.');

        return $this->redirectToRoute('admin_registrations');
    }

    #[Route('/registrations/{id}/status', name: 'admin_registrations_status', methods: ['POST'])]
    public function updateRegistrationStatus(Registration $registration, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): RedirectResponse
    {
        $registration->setStatus((string) $request->request->get('status', 'pending'));
        
        $errors = $validator->validate($registration);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('admin_registrations');
        }

        $entityManager->flush();
        $this->addFlash('success', 'Statut inscription mis a jour.');

        return $this->redirectToRoute('admin_registrations');
    }

    #[Route('/registrations/{id}/delete', name: 'admin_registrations_delete', methods: ['POST'])]
    public function deleteRegistration(Registration $registration, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($registration);
        $entityManager->flush();
        $this->addFlash('success', 'Inscription supprimee.');

        return $this->redirectToRoute('admin_registrations');
    }

    #[Route('/matches', name: 'admin_matches', methods: ['GET'])]
    public function matches(SportMatchRepository $sportMatchRepository, TournamentRepository $tournamentRepository, UserRepository $userRepository): Response
    {
        return $this->render('admin/matches.html.twig', [
            'matches' => $sportMatchRepository->findAll(),
            'tournaments' => $tournamentRepository->findAll(),
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/matches/create', name: 'admin_matches_create', methods: ['POST'])]
    public function createMatch(Request $request, TournamentRepository $tournamentRepository, UserRepository $userRepository, RegistrationRepository $registrationRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator): RedirectResponse
    {
        try {
            $tournament = $tournamentRepository->find((int) $request->request->get('tournamentId'));
            $player1 = $userRepository->find((int) $request->request->get('player1Id'));
            $player2 = $userRepository->find((int) $request->request->get('player2Id'));

            if (!$tournament || !$player1 || !$player2) {
                $this->addFlash('error', 'Tournoi ou joueurs introuvables.');
                return $this->redirectToRoute('admin_matches');
            }

            $registration1 = $registrationRepository->findOneBy(['tournament' => $tournament, 'player' => $player1, 'status' => 'confirmed']);
            $registration2 = $registrationRepository->findOneBy(['tournament' => $tournament, 'player' => $player2, 'status' => 'confirmed']);
            if (!$registration1 || !$registration2) {
                $this->addFlash('error', 'Les deux joueurs doivent avoir une inscription confirmee.');
                return $this->redirectToRoute('admin_matches');
            }

            $match = new SportMatch();
            $match->setTournament($tournament);
            $match->setPlayer1($player1);
            $match->setPlayer2($player2);
            $match->setMatchDate(new \DateTime((string) $request->request->get('matchDate')));
            $match->setStatus((string) ($request->request->get('status') ?: 'pending'));
            $match->setScorePlayer1((int) $request->request->get('scorePlayer1', 0));
            $match->setScorePlayer2((int) $request->request->get('scorePlayer2', 0));

            $errors = $validator->validate($match);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('admin_matches');
            }

            $entityManager->persist($match);
            $entityManager->flush();
            $this->addFlash('success', 'Match cree.');
        } catch (\Throwable) {
            $this->addFlash('error', 'Erreur lors de la creation du match.');
        }

        return $this->redirectToRoute('admin_matches');
    }

    #[Route('/matches/{id}/update', name: 'admin_matches_update', methods: ['POST'])]
    public function updateMatch(SportMatch $match, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): RedirectResponse
    {
        $match->setScorePlayer1((int) $request->request->get('scorePlayer1', 0));
        $match->setScorePlayer2((int) $request->request->get('scorePlayer2', 0));
        $match->setStatus((string) $request->request->get('status', 'pending'));
        
        $errors = $validator->validate($match);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('admin_matches');
        }

        $entityManager->flush();
        $this->addFlash('success', 'Match mis a jour.');

        return $this->redirectToRoute('admin_matches');
    }

    #[Route('/matches/{id}/delete', name: 'admin_matches_delete', methods: ['POST'])]
    public function deleteMatch(SportMatch $match, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($match);
        $entityManager->flush();
        $this->addFlash('success', 'Match supprime.');

        return $this->redirectToRoute('admin_matches');
    }
}
