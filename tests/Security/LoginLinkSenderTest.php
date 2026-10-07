<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Client;
use App\Entity\User;
use App\Security\LoginLinkSender;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkDetails;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

final class LoginLinkSenderTest extends TestCase
{
    // Publique : la classe anonyme qui joue le fabricant de liens n'herite pas
    // de la portee privee de cette classe de test.
    public const URL = 'https://extranet.test/connexion/verification?user=camille%40societe.test&expires=1760000000&hash=abc';

    public function testItMailsTheSignedLinkToTheUser(): void
    {
        $mailer = $this->capturingMailer();
        $user = $this->user();

        $this->sender($mailer)->send($user);

        $message = $mailer->sent;
        self::assertInstanceOf(TemplatedEmail::class, $message);
        self::assertSame('camille@societe.test', $message->getTo()[0]->getAddress());
        self::assertSame('no-reply@milliris.test', $message->getFrom()[0]->getAddress());
        self::assertSame('emails/login_link.html.twig', $message->getHtmlTemplate());
        // La partie texte n'est pas decorative : c'est elle que relit le test
        // fonctionnel, et elle qui evite un classement en courrier indesirable.
        self::assertSame('emails/login_link.txt.twig', $message->getTextTemplate());
        self::assertSame(self::URL, $message->getContext()['url']);
        self::assertSame($user, $message->getContext()['user']);
    }

    /**
     * Une panne de SMTP ne doit PAS remonter : elle ne surviendrait que pour
     * les adresses qui existent, et la page d'erreur deviendrait alors un
     * detecteur de comptes valides.
     */
    public function testATransportFailureIsSwallowed(): void
    {
        $mailer = new class implements MailerInterface {
            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                throw new TransportException('serveur SMTP injoignable');
            }
        };

        // Pas d'exception attendue : l'assertion est l'absence d'echec.
        $this->expectNotToPerformAssertions();

        $this->sender($mailer)->send($this->user());
    }

    private function sender(MailerInterface $mailer): LoginLinkSender
    {
        return new LoginLinkSender(
            $this->loginLinkHandler(),
            $mailer,
            new NullLogger(),
            'Extranet MILLIRIS <no-reply@milliris.test>',
            false,
        );
    }

    private function user(): User
    {
        return new User(new Client('DEMO', 'Societe Cliente SAS'), 'camille@societe.test', 'Camille', 'Durand');
    }

    private function capturingMailer(): MailerInterface
    {
        return new class implements MailerInterface {
            public ?RawMessage $sent = null;

            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                $this->sent = $message;
            }
        };
    }

    private function loginLinkHandler(): LoginLinkHandlerInterface
    {
        return new class implements LoginLinkHandlerInterface {
            public function createLoginLink(UserInterface $user, ?Request $request = null, ?int $lifetime = null): LoginLinkDetails
            {
                return new LoginLinkDetails(LoginLinkSenderTest::URL, new \DateTimeImmutable('2026-10-07 09:05:00'));
            }

            public function consumeLoginLink(Request $request): UserInterface
            {
                throw new \LogicException('Non utilise par ce test.');
            }
        };
    }
}
