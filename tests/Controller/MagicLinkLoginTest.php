<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Support\NeedsTestDatabase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;

/**
 * Le parcours de connexion de bout en bout : page protegee -> formulaire ->
 * courriel -> lien -> page demandee.
 *
 * Ces tests ont besoin d'une base (voir NeedsTestDatabase) : le pare-feu
 * recharge l'utilisateur depuis la base a chaque requete. Sans base de test
 * joignable, ils sont SAUTES.
 */
final class MagicLinkLoginTest extends WebTestCase
{
    use MailerAssertionsTrait;
    use NeedsTestDatabase;

    public function testClickingTheEmailedLinkOpensTheRequestedPage(): void
    {
        $browser = static::createClient();
        $this->createUser($this->resetSchema());

        // 1. une page protegee renvoie au formulaire, en memorisant la cible.
        $browser->request('GET', '/modules/gmao');
        self::assertResponseRedirects('/connexion');

        // 2. la demande part. L'adresse est saisie EN MAJUSCULES a dessein :
        //    la fiche est stockee en minuscules, la casse ne doit pas compter.
        $crawler = $browser->followRedirect();
        $browser->submit(
            $crawler->filter('form')->form(),
            ['email' => 'CAMILLE.DURAND@Societe-Cliente.test'],
        );

        self::assertResponseRedirects('/connexion/lien-envoye');
        self::assertEmailCount(1);

        // 3. le lien du courriel authentifie et ramene sur la page demandee en 1.
        $browser->request('GET', $this->emailedLoginLink());
        self::assertResponseRedirects();

        $browser->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'GMAO');
        self::assertSelectorTextContains('.socle-identity__name', 'Camille Durand');
    }

    public function testAnUnknownAddressGetsTheSameAnswerAndNoEmail(): void
    {
        $browser = static::createClient();
        $this->resetSchema();

        $crawler = $browser->request('GET', '/connexion');
        $browser->submit($crawler->filter('form')->form(), ['email' => 'inconnu@example.test']);

        // Meme reponse que pour une adresse connue : le formulaire ne doit pas
        // pouvoir servir d'annuaire des clients.
        self::assertResponseRedirects('/connexion/lien-envoye');
        self::assertEmailCount(0);
    }

    public function testADeactivatedAccountGetsNoEmail(): void
    {
        $browser = static::createClient();
        $this->createUser($this->resetSchema(), 'parti@societe-cliente.test', active: false);

        $crawler = $browser->request('GET', '/connexion');
        $browser->submit($crawler->filter('form')->form(), ['email' => 'parti@societe-cliente.test']);

        self::assertResponseRedirects('/connexion/lien-envoye');
        self::assertEmailCount(0);
    }

    public function testAForgedLinkSendsBackToTheFormWithAWarning(): void
    {
        $browser = static::createClient();
        $this->createUser($this->resetSchema());

        $browser->request('GET', '/connexion/verification?user=camille.durand%40societe-cliente.test&expires=4102444800&hash=signature-inventee');

        self::assertResponseRedirects();
        $browser->followRedirect();
        self::assertSelectorExists('.alert');
        // Et surtout : toujours pas d'acces a l'application.
        $browser->request('GET', '/');
        self::assertResponseRedirects('/connexion');
    }

    public function testLogoutClosesTheSession(): void
    {
        $browser = static::createClient();
        $browser->loginUser($this->createUser($this->resetSchema()));

        $crawler = $browser->request('GET', '/');
        self::assertResponseIsSuccessful();

        // L'URL de deconnexion est lue DANS la page : elle porte un jeton CSRF
        // fabrique par logout_path(), qu'on ne peut pas deviner ici.
        $browser->request('GET', $crawler->filter('a[href*="/deconnexion"]')->attr('href'));
        self::assertResponseRedirects();

        $browser->request('GET', '/');
        self::assertResponseRedirects('/connexion');
    }

    /**
     * Le lien est relu dans la PARTIE TEXTE du courriel : elle n'est pas
     * echappee en HTML, les `&` de la signature y sont donc intacts.
     */
    private function emailedLoginLink(): string
    {
        $message = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $message);

        $body = (string) $message->getTextBody();
        self::assertStringContainsString('/connexion/verification?', $body);
        self::assertSame(1, preg_match('#https?://\S+#', $body, $matches));

        return $matches[0];
    }
}
