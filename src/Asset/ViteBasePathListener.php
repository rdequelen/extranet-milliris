<?php

declare(strict_types=1);

namespace App\Asset;

use Pentatrion\ViteBundle\Event\RenderAssetTagEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Recolle la base de la requete sur les balises d'assets produites par
 * pentatrion/vite-bundle.
 *
 * POURQUOI CETTE CLASSE EXISTE. Vite fige sa `base` (`/build/`) a la compilation :
 * public/build/.vite/entrypoints.json ne contient que des chemins a la racine de
 * l'hote (`/build/assets/app-<hash>.css`), et le bundle les recopie tels quels.
 * Des que l'application est servie sous un PREFIXE d'URL — ce qui est le cas des
 * environnements de recette du Dev Center, `/<app>/recette/<n>/`, ou le proxy
 * retire le prefixe et le republie dans `X-Forwarded-Prefix` — le navigateur
 * demande ces fichiers a la racine de l'hote, c'est-a-dire a quelqu'un d'autre.
 * La page repond 200, ses liens sont justes (le routeur Symfony, lui, honore le
 * prefixe), et elle s'affiche NUE : ni CSS, ni JavaScript, donc ni theme, ni menu
 * mobile, ni Turbo. C'est un defaut qu'on ne voit pas en local, ou il n'y a pas
 * de prefixe, et qui ne ressemble pas a une panne : juste a une page moche.
 *
 * `Request::getBasePath()` EST LA BONNE PRIMITIVE, et pas `getBaseUrl()` :
 *   - elle inclut `X-Forwarded-Prefix` quand le proxy est de confiance
 *     (cf. Request::getBaseUrl(), dont elle derive) ;
 *   - elle RETIRE le nom du script, la ou `getBaseUrl()` le garde : une
 *     application atteinte en `/index.php/une/page` fabriquerait sinon
 *     `/index.php/build/assets/...`, qui n'existe pas ;
 *   - elle vaut '' a la racine, cas ou ce listener ne fait donc rien.
 *
 * ⚠️ CE QUI RESTE A LA CHARGE DE VITE : les URL ecrites DANS le CSS compile
 * (police bootstrap-icons, logo en background-image) ne passent par aucun code
 * PHP. Elles sont rendues relatives a la feuille de style au build, par
 * `experimental.renderBuiltUrl` dans vite.config.js. Les deux reglages vont
 * ensemble : retirer l'un laisse la moitie des fichiers hors du prefixe.
 *
 * ⚠️ Pour que le prefixe soit vu, l'application doit faire confiance au proxy.
 * Rien n'est declare ici : `framework.trusted_proxies` / `trusted_headers` gardent
 * leur defaut, qui lit `SYMFONY_TRUSTED_PROXIES` / `SYMFONY_TRUSTED_HEADERS` dans
 * l'environnement — ce que l'hote pose. Voir config/packages/framework.yaml.
 */
#[AsEventListener]
final readonly class ViteBasePathListener
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(RenderAssetTagEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        // Rendu hors contexte HTTP (console, warmup) : il n'y a pas de base de
        // requete a appliquer, et le chemin absolu est le meilleur defaut.
        if (null === $request) {
            return;
        }

        $base = $request->getBasePath();
        if ('' === $base) {
            return;
        }

        $tag = $event->getTag();
        $attribute = $tag->isScriptTag() ? 'src' : 'href';
        $url = $tag->getAttribute($attribute);

        if (!\is_string($url) || !str_starts_with($url, '/')) {
            // Hors sujet : une URL (serveur de developpement Vite, CDN), un
            // `data:`, un protocole implicite `//`, ou une balise sans cible.
            return;
        }

        if (str_starts_with($url, '//') || str_starts_with($url, $base . '/')) {
            return;
        }

        $tag->setAttribute($attribute, $base . $url);
    }
}
