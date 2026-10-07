# Socle

Fondations techniques de l'extranet client MILLIRIS : squelette Symfony 7.4 / PHP
8.4 avec Doctrine/PostgreSQL, chaine d'assets Vite, gabarit de page (barre
laterale, en-tete, theme clair/sombre) et catalogue des modules affiches en
navigation. Ne porte aucune entite ni migration (LOT 1) ; `migrations/` est
pose vide pour la suite.

## Composants cles
- `src/Navigation/ModuleRegistry.php` — catalogue des modules ; source unique
  lue par Twig pour le menu, les tuiles d'accueil et la page placeholder.
- `src/Navigation/Module.php`, `ModuleFamily.php`, `ModuleStatus.php` — objets
  de valeur / enum du catalogue.
- `src/Controller/HomeController.php`, `src/Controller/ModuleController.php` —
  page d'accueil et page placeholder partagee par les modules "a venir".
- `templates/base.html.twig` + `templates/_partials/{sidebar,topbar,toasts}` —
  gabarit de page commun.
- `assets/styles/_brand.scss` — charte MILLIRIS (couleurs), seule source qui
  surcharge Bootstrap avant compilation.

## Decisions structurantes
- Pas de symfony/flex : les recettes auraient injecte des lignes dans
  `templates/base.html.twig` / `assets/app.js`. Contrepartie : les composants
  Symfony sont epingles en `7.4.*` a la main dans `composer.json` (sinon ils
  remontent en 8.x en silence). Voir [[project_composer-sans-flex]].
- `ModuleFamily` (enum) doit rester aligne avec la carte SCSS
  `$milliris-familles` ; garde-fou : `ModuleRegistryTest::testFamilyValuesMatchTheScssColourMap`.
- Theme clair/sombre pose par un script inline dans le `<head>` (avant
  Stimulus) pour eviter un flash clair au premier rendu.
- Logo affiche en `background-image` CSS, pas en `<img>` : pentatrion/vite-bundle
  n'expose pas de helper Twig pour un asset isole hors import JS/CSS.

## Pieges
- `@symfony/ux-turbo` est un paquet npm `file:vendor/...` : `composer install`
  doit precéder `npm install` au premier lancement.
- Sass/Bootstrap : ne pas ajouter `mixed-decls` aux `silenceDeprecations` de
  `vite.config.js` (obsolete depuis Sass 1.77, provoque un avertissement).
- Les objets de valeur et enums de `src/Navigation/` sont exclus de
  l'autowiring de services dans `config/services.yaml`.

## Non prouve au merge (LOT 1)
Aucune commande n'a ete jouee dans le worktree de livraison (`composer
install`, `npm install`, `npm run build`, `phpunit`, `about`). A verifier au
premier enchainement complet.

## Liens
- Spec declaree dans `docs/modules/manifest.yaml` :
  `docs/specs/socle_cree-une-application-symfony-postgre-qui-sera-un-extranet-client-sur-notre-site-web.md`
  — fichier absent du depot a la date de ce lot, rien a y comparer.
- Aucune fiche `docs/roadmap/MODULE_*.md` trouvee pour ce module a la date de
  ce lot.
