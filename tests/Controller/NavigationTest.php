<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Navigation\ModuleRegistry;
use App\Tests\Support\NeedsTestDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La coquille de l'application : l'accueil et chaque entree de menu repondent,
 * et le catalogue de modules est bien la source unique des deux.
 *
 * Depuis la couche de securite, ces pages sont PROTEGEES : chaque test
 * authentifie son navigateur, ce qui demande une base de test (voir
 * NeedsTestDatabase — les tests sont sautes, pas en echec, sans base). La
 * protection elle-meme est verifiee par SecurityTest, qui n'a besoin de rien.
 */
final class NavigationTest extends WebTestCase
{
    use NeedsTestDatabase;

    public function testHomepageRendersOneTilePerModule(): void
    {
        $browser = $this->signedInBrowser();
        $crawler = $browser->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1');
        self::assertCount(
            \count((new ModuleRegistry())->all()),
            $crawler->filter('.module-tile'),
        );
    }

    public function testPageShellIsPresent(): void
    {
        $browser = $this->signedInBrowser();
        $crawler = $browser->request('GET', '/');

        self::assertResponseIsSuccessful();
        // La charpente que tout module heritera : menu, en-tete, pile de toasts.
        self::assertSelectorExists('.socle-sidebar');
        self::assertSelectorExists('.socle-topbar');
        self::assertSelectorExists('#socle-toasts');
        // La pile de toasts est posee mais vide : elle sera alimentee au lot 3.
        self::assertSame('', trim($crawler->filter('#socle-toasts')->html()));
    }

    public function testHeaderShowsTheAuthenticatedIdentity(): void
    {
        $browser = $this->signedInBrowser();
        $browser->request('GET', '/');

        self::assertResponseIsSuccessful();
        // Le bloc identite factice du lot 1 a laisse place a l'utilisateur reel.
        self::assertSelectorTextContains('.socle-identity__name', 'Camille Durand');
        self::assertSelectorTextContains('.socle-identity__role', 'Societe Cliente SAS');
    }

    #[DataProvider('moduleProvider')]
    public function testEveryModuleEntryRenders(string $code, string $label): void
    {
        $browser = $this->signedInBrowser();
        $browser->request('GET', '/modules/'.$code);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $label);
    }

    public function testUnknownModuleReturns404(): void
    {
        $browser = $this->signedInBrowser();
        $browser->request('GET', '/modules/module-qui-nexiste-pas');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function moduleProvider(): iterable
    {
        foreach ((new ModuleRegistry())->all() as $module) {
            yield $module->code => [$module->code, $module->label];
        }
    }

    private function signedInBrowser(): KernelBrowser
    {
        $browser = static::createClient();
        $browser->loginUser($this->createUser($this->resetSchema()));

        return $browser;
    }
}
