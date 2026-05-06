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
     * Envoie un email à tous les participants confirmés pour annoncer le vainqueur du tournoi.
     */
    public function sendTournamentWinnerNotification(Tournament $tournament, User $winner, array $participantEmails): void
    {
        if (empty($participantEmails)) {
            return;
        }

        // Création de l'email avec le composant Mime de Symfony
        $email = (new Email())
            ->from('noreply@sportmanager.com')
            ->to(...$participantEmails) // Utilisation du spread operator pour envoyer à plusieurs destinataires
            ->subject('Vainqueur annoncé pour le tournoi : ' . $tournament->getTournamentName())
            ->text(sprintf(
                "Le tournoi %s est terminé !\nFélicitations à %s qui a remporté la victoire.",
                $tournament->getTournamentName(),
                $winner->getUsername()
            ));

        // Envoi via le transport configuré dans MAILER_DSN (Mailtrap)
        $this->mailer->send($email);
    }

    /**
     * Envoie un email de rappel à l'adversaire lorsqu'un joueur a mis à jour son score.
     */
    public function sendScoreUpdateReminder(SportMatch $match, User $recipient): void
    {
        if (!$recipient->getEmailAddress()) {
            return;
        }

        $email = (new Email())
            ->from('notifications@sportmanager.com')
            ->to($recipient->getEmailAddress())
            ->subject('Mise à jour de score : ' . $match->getTournament()?->getTournamentName())
            ->text(sprintf(
                "Bonjour %s,\nTon adversaire a mis à jour son score pour votre match du tournoi %s. N'oublie pas de saisir ton score pour valider le match.",
                $recipient->getUsername(),
                $match->getTournament()?->getTournamentName()
            ));

        $this->mailer->send($email);
    }
}
