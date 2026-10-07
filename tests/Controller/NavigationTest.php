<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Navigation\ModuleRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * La coquille du lot 1 : l'accueil et chaque entree de menu repondent, et le
 * catalogue de modules est bien la source unique des deux.
 *
 * Aucune base n'est requise : ces routes ne touchent pas Doctrine.
 */
final class NavigationTest extends WebTestCase
{
    public function testHomepageRendersOneTilePerModule(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1');
        self::assertCount(
            \count((new ModuleRegistry())->all()),
            $crawler->filter('.module-tile'),
        );
    }

    public function testPageShellIsPresent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        // La charpente que tout module heritera : menu, en-tete, pile de toasts.
        self::assertSelectorExists('.socle-sidebar');
        self::assertSelectorExists('.socle-topbar');
        self::assertSelectorExists('#socle-toasts');
        // La pile de toasts est posee mais vide : elle sera alimentee au lot 3.
        self::assertSame('', trim($crawler->filter('#socle-toasts')->html()));
    }

    #[DataProvider('moduleProvider')]
    public function testEveryModuleEntryRenders(string $code, string $label): void
    {
        $client = static::createClient();
        $client->request('GET', '/modules/'.$code);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $label);
    }

    public function testUnknownModuleReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/modules/module-qui-nexiste-pas');

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
}
