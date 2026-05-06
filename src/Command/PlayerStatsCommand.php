<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\SportMatchRepository;
use App\Repository\UserRepository;
use App\Repository\TournamentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:player-stats',
    description: 'Affiche le nombre de victoires et de défaites pour un joueur',
)]
class PlayerStatsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private SportMatchRepository $matchRepository,
        private TournamentRepository $tournamentRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'L\'ID de l\'utilisateur')
            ->addArgument('tournamentId', InputArgument::OPTIONAL, 'L\'ID du tournoi (optionnel)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = (int) $input->getArgument('userId');
        $tournamentId = $input->getArgument('tournamentId');

        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            $io->error(sprintf('L\'utilisateur avec l\'ID "%s" n\'existe pas.', $userId));
            return Command::FAILURE;
        }

        $criteria = [];
        if ($tournamentId !== null) {
            $tournament = $this->tournamentRepository->find((int) $tournamentId);
            if (!$tournament) {
                $io->error(sprintf('Le tournoi avec l\'ID "%s" n\'existe pas.', $tournamentId));
                return Command::FAILURE;
            }
            $criteria['tournament'] = $tournament;
        }

        // On cherche tous les matchs où le joueur est player1 ou player2
        $matchesAsPlayer1 = $this->matchRepository->findBy(array_merge($criteria, ['player1' => $user, 'status' => 'finished']));
        $matchesAsPlayer2 = $this->matchRepository->findBy(array_merge($criteria, ['player2' => $user, 'status' => 'finished']));

        $wins = 0;
        $losses = 0;

        foreach ($matchesAsPlayer1 as $match) {
            if ($match->getScorePlayer1() > $match->getScorePlayer2()) {
                $wins++;
            } elseif ($match->getScorePlayer1() < $match->getScorePlayer2()) {
                $losses++;
            }
        }

        foreach ($matchesAsPlayer2 as $match) {
            if ($match->getScorePlayer2() > $match->getScorePlayer1()) {
                $wins++;
            } elseif ($match->getScorePlayer2() < $match->getScorePlayer1()) {
                $losses++;
            }
        }

        $io->title(sprintf('Statistiques pour %s %s', $user->getFirstName(), $user->getLastName()));
        
        if ($tournamentId !== null) {
            $io->text(sprintf('Tournoi : %s', $tournament->getTournamentName()));
        } else {
            $io->text('Global (Tous les tournois)');
        }

        $io->table(
            ['Victoires', 'Défaites'],
            [[$wins, $losses]]
        );

        return Command::SUCCESS;
    }
}
