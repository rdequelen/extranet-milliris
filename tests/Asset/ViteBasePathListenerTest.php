<?php

declare(strict_types=1);

namespace App\Tests\Asset;

use App\Asset\ViteBasePathListener;
use Pentatrion\ViteBundle\Event\RenderAssetTagEvent;
use Pentatrion\ViteBundle\Model\Tag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Garde-fou du defaut « la page repond mais s'affiche nue » : les chemins
 * d'assets que Vite fige au build (`/build/assets/...`) doivent repartir de la
 * base de la requete, sinon ils sortent du prefixe sous lequel l'application est
 * servie et sont demandes a la racine de l'hote.
 */
final class ViteBasePathListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        // Etat GLOBAL : sans cette remise a zero, les tests suivants (y compris
        // ceux des autres classes) heriteraient du proxy de confiance.
        Request::setTrustedProxies([], 0);
    }

    public function testThePrefixPublishedByTheProxyIsPrepended(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PREFIX);

        $tag = $this->render('/build/assets/app-abc123.css', Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_PREFIX' => '/extranet/recette/80',
        ]));

        self::assertSame('/extranet/recette/80/build/assets/app-abc123.css', $tag->getAttribute('href'));
    }

    public function testTheScriptNameIsNotPartOfTheBase(): void
    {
        // Application atteinte en `/pfx/index.php/...` : c'est tout l'interet de
        // getBasePath() sur getBaseUrl(), qui donnerait ici
        // `/pfx/index.php/build/assets/...` — un chemin qui n'existe pas.
        $tag = $this->render('/build/assets/app-abc123.js', Request::create(
            '/pfx/index.php/modules/gmao',
            'GET',
            [],
            [],
            [],
            [
                'SCRIPT_NAME' => '/pfx/index.php',
                'SCRIPT_FILENAME' => '/var/www/public/index.php',
            ],
        ), Tag::SCRIPT_TAG);

        self::assertSame('/pfx/build/assets/app-abc123.js', $tag->getAttribute('src'));
    }

    public function testAnApplicationServedAtTheRootIsLeftAlone(): void
    {
        $tag = $this->render('/build/assets/app-abc123.css', Request::create('/modules/gmao'));

        self::assertSame('/build/assets/app-abc123.css', $tag->getAttribute('href'));
    }

    public function testTheViteDevServerUrlIsLeftAlone(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PREFIX);

        $url = 'http://127.0.0.1:5173/build/assets/app.js';
        $tag = $this->render($url, Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_PREFIX' => '/extranet/recette/80',
        ]), Tag::SCRIPT_TAG);

        self::assertSame($url, $tag->getAttribute('src'));
    }

    public function testAPathAlreadyInsideThePrefixIsNotPrefixedTwice(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PREFIX);

        $url = '/extranet/recette/80/build/assets/app-abc123.css';
        $tag = $this->render($url, Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_PREFIX' => '/extranet/recette/80',
        ]));

        self::assertSame($url, $tag->getAttribute('href'));
    }

    public function testRenderingWithoutARequestIsNotAnError(): void
    {
        $tag = new Tag(Tag::LINK_TAG, ['rel' => 'stylesheet', 'href' => '/build/assets/app-abc123.css']);

        (new ViteBasePathListener(new RequestStack()))(new RenderAssetTagEvent(true, $tag));

        self::assertSame('/build/assets/app-abc123.css', $tag->getAttribute('href'));
    }

    private function render(string $url, Request $request, string $tagName = Tag::LINK_TAG): Tag
    {
        $tag = Tag::SCRIPT_TAG === $tagName
            ? new Tag(Tag::SCRIPT_TAG, ['type' => 'module', 'src' => $url])
            : new Tag(Tag::LINK_TAG, ['rel' => 'stylesheet', 'href' => $url]);

        $stack = new RequestStack();
        $stack->push($request);

        (new ViteBasePathListener($stack))(new RenderAssetTagEvent(true, $tag));

        return $tag;
    }
}
