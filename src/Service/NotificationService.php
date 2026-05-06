<?php

namespace App\Service;

use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    /**
     * Notifie l'adversaire qu'un score a été renseigné
     */
    public function sendScoreUpdateReminder(SportMatch $match, User $recipient): void
    {
        $email = (new Email())
            ->from('noreply@sportmanager.com')
            ->to($recipient->getEmailAddress())
            ->subject('Mise à jour de score - ' . $match->getTournament()->getTournamentName())
            ->html(sprintf(
                '<p>Bonjour %s,</p>
                <p>Ton adversaire a renseigné son score pour votre match du %s.</p>
                <p>Connecte-toi sur SportManager pour renseigner le tien !</p>',
                $recipient->getFirstName(),
                $match->getMatchDate()->format('d/m/Y')
            ));

        $this->mailer->send($email);
    }

    /**
     * Notifie tous les participants du vainqueur du tournoi
     */
    public function sendTournamentWinnerNotification(Tournament $tournament): void
    {
        $winner = $tournament->getWinner();
        if (!$winner) return;

        // On récupère tous les participants confirmés via les registrations
        $registrations = $tournament->getRegistrations();
        
        foreach ($registrations as $reg) {
            $player = $reg->getPlayer();
            
            $email = (new Email())
                ->from('noreply@sportmanager.com')
                ->to($player->getEmailAddress())
                ->subject('Résultat du tournoi : ' . $tournament->getTournamentName())
                ->html(sprintf(
                    '<h1>Félicitations à %s !</h1>
                    <p>Le tournoi <strong>%s</strong> est terminé.</p>
                    <p>Le grand vainqueur est <strong>%s %s</strong>.</p>
                    <p>Merci à tous pour votre participation !</p>',
                    $winner->getUsername(),
                    $tournament->getTournamentName(),
                    $winner->getFirstName(),
                    $winner->getLastName()
                ));

            $this->mailer->send($email);
        }
    }
}
