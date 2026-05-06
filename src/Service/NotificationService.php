<?php

namespace App\Service;

use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Envoie une notification à tous les participants d'un tournoi lorsqu'il est remporté.
     */
    public function sendTournamentWinnerNotification(Tournament $tournament, User $winner, array $participantsEmails): void
    {
        if (empty($participantsEmails)) {
            return;
        }

        $email = (new Email())
            ->from('noreply@sportmanager.com')
            ->to(...$participantsEmails)
            ->subject(sprintf('Fin du tournoi %s !', $tournament->getTournamentName()))
            ->text(sprintf(
                "Le tournoi '%s' est terminé.\n\nLe grand vainqueur est %s %s (%s) !\n\nMerci à tous pour votre participation.",
                $tournament->getTournamentName(),
                $winner->getFirstName(),
                $winner->getLastName(),
                $winner->getUsername()
            ));

        $this->mailer->send($email);
    }

    /**
     * Envoie une notification à l'adversaire pour qu'il remplisse son score.
     */
    public function sendScoreUpdateReminder(SportMatch $match, User $playerToRemind): void
    {
        $email = (new Email())
            ->from('noreply@sportmanager.com')
            ->to($playerToRemind->getEmailAddress())
            ->subject('Votre adversaire a mis à jour son score !')
            ->text(sprintf(
                "Bonjour %s,\n\nVotre adversaire dans le tournoi '%s' a mis à jour son score pour votre match.\nMerci de bien vouloir vous connecter et remplir le vôtre afin de valider le résultat du match.\n\nL'équipe Sport Manager",
                $playerToRemind->getFirstName(),
                $match->getTournament()->getTournamentName()
            ));

        $this->mailer->send($email);
    }
}
