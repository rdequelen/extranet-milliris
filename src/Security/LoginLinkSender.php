<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

/**
 * Fabrique le lien de connexion d'un utilisateur et le lui envoie par courriel.
 *
 * Le lien est une URL SIGNEE (APP_SECRET + adresse + date d'expiration) : rien
 * n'est ecrit en base, il n'y a donc aucune table de jetons a purger, et un
 * lien ne peut pas etre « vole » dans la base. Sa duree de vie est reglee par
 * `login_link.lifetime` dans config/packages/security.yaml (5 minutes).
 *
 * DEUX CHOIX A CONNAITRE :
 *  - l'envoi est SYNCHRONE (pas de Messenger dans ce depot a ce jour) : la
 *    page de confirmation attend le serveur SMTP ;
 *  - une panne d'envoi est AVALEE (journalisee en `error`, pas propagee). Une
 *    exception remonterait en 500 uniquement pour les adresses qui existent :
 *    la page d'erreur deviendrait un detecteur de comptes valides.
 */
final class LoginLinkSender
{
    public function __construct(
        private readonly LoginLinkHandlerInterface $loginLinkHandler,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $mailFrom,
        private readonly bool $debug,
    ) {
    }

    public function send(User $user): void
    {
        $link = $this->loginLinkHandler->createLoginLink($user);

        // En developpement, le lien est aussi ecrit dans le journal : c'est ce
        // qui permet de se connecter sans serveur SMTP (MAILER_DSN=null://null
        // par defaut). JAMAIS en production : ce serait un mot de passe en
        // clair dans les journaux.
        if ($this->debug) {
            $this->logger->info('Lien de connexion pour {email} : {url}', [
                'email' => $user->getEmail(),
                'url' => $link->getUrl(),
            ]);
        }

        $email = (new TemplatedEmail())
            ->from(Address::create($this->mailFrom))
            ->to(new Address($user->getEmail(), $user->getDisplayName()))
            ->subject('Votre lien de connexion a l\'extranet MILLIRIS')
            ->htmlTemplate('emails/login_link.html.twig')
            ->textTemplate('emails/login_link.txt.twig')
            ->context([
                'user' => $user,
                'url' => $link->getUrl(),
                'expires_at' => $link->getExpiresAt(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Envoi du lien de connexion impossible pour {email} : {raison}', [
                'email' => $user->getEmail(),
                'raison' => $exception->getMessage(),
                'exception' => $exception,
            ]);
        }
    }
}
