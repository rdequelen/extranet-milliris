<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La couche de securite vue de l'exterieur : ce qu'un visiteur ANONYME obtient.
 *
 * Aucune base n'est requise ici — et c'est voulu : ces assertions doivent
 * tourner partout, parce qu'elles decrivent la promesse du ticket (« on ne peut
 * acceder a l'application que si l'utilisateur est loggue »). Le parcours
 * complet de connexion, lui, a besoin de lignes en base : voir
 * MagicLinkLoginTest.
 */
final class SecurityTest extends WebTestCase
{
    use MailerAssertionsTrait;

    public function testAnonymousVisitorIsSentToTheLoginPage(): void
    {
        $browser = static::createClient();
        $browser->request('GET', '/');

        self::assertResponseRedirects('/connexion');
    }

    public function testModulePagesAreProtectedToo(): void
    {
        $browser = static::createClient();
        $browser->request('GET', '/modules/gmao');

        self::assertResponseRedirects('/connexion');
    }

    /**
     * Une route inconnue ne doit pas renseigner un visiteur anonyme : la
     * securite passe AVANT le routage applicatif, donc redirection et non 404.
     */
    public function testUnknownPageAlsoRedirectsAnonymousVisitors(): void
    {
        $browser = static::createClient();
        $browser->request('GET', '/modules/module-qui-nexiste-pas');

        self::assertResponseRedirects('/connexion');
    }

    public function testLoginPageAsksForAnEmailAndCarriesACsrfToken(): void
    {
        $browser = static::createClient();
        $browser->request('GET', '/connexion');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form input[name="email"]');
        self::assertSelectorExists('form input[name="_token"]');
        // Ecran public : il surcharge le bloc `page` et n'embarque donc ni le
        // menu ni l'en-tete, qui supposent un utilisateur authentifie.
        self::assertSelectorNotExists('.socle-sidebar');
        self::assertSelectorNotExists('.socle-topbar');
    }

    public function testLinkSentPageIsPublic(): void
    {
        $browser = static::createClient();
        $browser->request('GET', '/connexion/lien-envoye');

        self::assertResponseIsSuccessful();
    }

    public function testAMalformedEmailIsRefusedBeforeAnyLookup(): void
    {
        $browser = static::createClient();
        $crawler = $browser->request('GET', '/connexion');
        $browser->submit($crawler->filter('form')->form(), ['email' => 'pas-une-adresse']);

        // 422 et non 200 : c'est le code qu'attend Turbo Drive pour remplacer
        // la page par le formulaire en erreur.
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.alert');
        self::assertEmailCount(0);
    }

    public function testASubmissionWithoutCsrfTokenIsRefused(): void
    {
        $browser = static::createClient();
        $browser->request('POST', '/connexion', ['email' => 'quelquun@example.test']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.alert');
        self::assertEmailCount(0);
    }
}
