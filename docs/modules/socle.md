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
- `src/Asset/ViteBasePathListener.php` — ecouteur `pentatrion/vite-bundle` qui
  reprefixe les balises `<script>`/`<link>` par `Request::getBasePath()` quand
  l'appli est servie sous un prefixe d'URL (recette). Voir
  [[project_assets-sous-prefixe-url]].

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
- Les URL d'assets (balises Vite comme CSS compile) doivent repartir de la base
  de la requete, pas de la racine de l'hote : voir
  [[project_assets-sous-prefixe-url]] (environnements de recette sous prefixe).

## Pieges
- `@symfony/ux-turbo` est un paquet npm `file:vendor/...` : `composer install`
  doit precéder `npm install` au premier lancement.
- Sass/Bootstrap : ne pas ajouter `mixed-decls` aux `silenceDeprecations` de
  `vite.config.js` (obsolete depuis Sass 1.77, provoque un avertissement).
- Les objets de valeur et enums de `src/Navigation/` sont exclus de
  l'autowiring de services dans `config/services.yaml`.
- `.socle-brand` (logo + wordmark) est partage entre un flex-row
  (`.offcanvas-header`, mobile) et un flex-COLUMN (`.socle-sidebar` desktop, ou
  il est le premier enfant avant `.socle-nav`). Un `flex: 1 1 auto` qui a du
  sens cote mobile fait grandir le bloc et repousse le menu vers le bas cote
  desktop : quand une classe partagee se comporte mal dans un seul des deux
  contextes, verifier les regles `flex:` portees par les ENFANTS, pas
  seulement le `flex-direction` du conteneur (ticket-80, 2026-10-07).

## Non prouve au merge (LOT 1)
Aucune commande n'a ete jouee dans le worktree de livraison (`composer
install`, `npm install`, `npm run build`, `phpunit`, `about`). A verifier au
premier enchainement complet.

### Ticket-80 (amelioration), 2026-10-07
Worktree nu egalement (pas de `node_modules`). Le correctif `.socle-brand` est
verifie a la lecture du CSS/flexbox, PAS a l'ecran : un `npm run build` suivi
d'une verification visuelle (desktop + mobile, theme clair et sombre) reste a
faire avant de considerer le rendu du menu desktop corrige.

## Liens
- Spec declaree dans `docs/modules/manifest.yaml` :
  `docs/specs/socle_cree-une-application-symfony-postgre-qui-sera-un-extranet-client-sur-notre-site-web.md`
  — fichier absent du depot a la date de ce lot, rien a y comparer.
- Aucune fiche `docs/roadmap/MODULE_*.md` trouvee pour ce module a la date de
  ce lot.
