# Carte du depot — Extranet client

> ⛔ **INDEX DERIVE, PAS UNE VERITE, ET JAMAIS UNE LIMITE.** Tout ce que porte ce fichier est
> derive du depot — manifeste des modules, routeur Symfony, historique git, perimetres de
> tickets livres. Rien n'y est devine, et rien n'y est exhaustif : ce sont des POINTS
> D'ENTREE pour ne pas fouiller le depot. Si ton travail t'emmene ailleurs, vas-y.
>
> **Fraicheur** — derivee de `f02cb1503b17` le 2026-10-07 20:07, 0 commit(s) de retard. Avant de t'y fier : `git rev-parse HEAD` — si le
> SHA differe de celui-ci, cette carte est un INDEX POSSIBLEMENT PERIME : les chemins qu'elle
> nomme restent de bons points de depart, ses compteurs ne sont plus des mesures.
>
> **Comment naviguer.** Le sommaire ci-dessous donne l'adresse exacte de chaque module :
> `Read('docs/modules/carte.md', offset: X, limit: N)`. Pour savoir a quel module appartient un
> chemin, ou ce qui bouge avec lui : `Grep` ce chemin dans ce fichier. Chaque ligne porte sa
> PROVENANCE entre parentheses (manifeste / routeur / git / ticket) — ce qui est derive est
> verifiable. Les seules lignes REDIGEES sont celles du volet **Roles ecrits** d'un module :
> 9 ligne(s) sur 1 module(s), ecrite(s) par l'agent de finalisation d'un ticket qui venait de
> toucher le fichier cite (source append-only : `docs/modules/semantique/`). Elles disent un CONSTAT, pas une derivation.
>
> **Poids** — plafond `carte_max_bytes` = 150 Ko. La carte n'est JAMAIS decoupee (le decoupage
> casserait toutes ses adresses) : au-dela du plafond elle reste COMPLETE et
> `app:roadmap:carte --check` echoue tant que le depassement dure.

## Degradations de cette carte

Ce qui MANQUE a cette carte, et pourquoi. Une carte amputee qui se presenterait comme
complete serait pire que pas de carte du tout.

- VOLET ECRANS ABSENT : les ecrans se derivent du routeur Symfony de l'application qui execute, et les routes d'un depot client n'y sont pas. Aucun ecran n'est donc rattache ici — ce qui ne veut pas dire que cette application n'en a pas.

## Sommaire adresse — 1 module(s)

- `socle` — l. 35, 43 lignes, 3 Ko — Socle

## socle — Socle

- **Place** (manifeste) : racine de l'arborescence.
- **Roles ecrits** (ecrit par la finalisation — constate sur un ticket, pas derive ; source `docs/modules/semantique/socle.md`, 9) :
  - `assets/styles/_socle.scss` — styles barre laterale/offcanvas ; `.socle-brand` y est partage entre flex-row (mobile) et flex-column (desktop)
  - `src/Asset/ViteBasePathListener.php` — ecouteur qui reprefixe href/src des balises Vite par `Request::getBasePath()` sous un prefixe d'URL (recette)
  - `templates/base.html.twig` — gabarit de page, pose le theme initial via script inline avant le premier rendu
  - `assets/styles/_brand.scss` — charte de couleurs MILLIRIS, seule source qui surcharge Bootstrap avant compilation
  - `assets/controllers/theme_controller.js` — bascule clair/sombre Stimulus, lit/persiste `data-bs-theme` dans localStorage, suit le theme systeme
  - `src/Controller/HomeController.php` — page d'accueil, rend les tuiles depuis la variable Twig globale `modules`
  - `src/Controller/ModuleController.php` — page "a venir" partagee par tous les modules sans route propre, resout `code` via ModuleRegistry
  - `src/Navigation/Module.php` — objet de valeur d'un module (code, label, icone, famille, statut) renvoye par ModuleRegistry
  - `src/Navigation/ModuleRegistry.php` — catalogue code-en-dur des modules, source unique lue par le menu, l'accueil et le placeholder
- **Documents** (manifeste, 1) — adresses pour `Read(offset, limit)` :
  - `docs/specs/socle_cree-une-application-symfony-postgre-qui-sera-un-extranet-client-sur-notre-site-web.md` — Spec — 20 Ko — rattache (manifeste `docs:`)
    - l. 1, 6 lignes, 279 o — # Cree une application Symfony + PostgreSQL qui sera un extranet client sur notre site WEB
    - l. 7, 4 lignes, 1 Ko — ## Intention
    - l. 11, 15 lignes, 827 o — ## Ce que ce ticket livre, et ce qu'il ne livre pas
    - l. 26, 49 lignes, 3 Ko — ## Reference de marque (le logo fourni par l'operateur)
    - l. 75, 66 lignes, 3 Ko — ## Choix techniques (arbitres ici, a ne pas re-arbitrer en dev)
    - l. 141, 62 lignes, 3 Ko — ## L'ergonomie du socle
    - l. 203, 15 lignes, 746 o — ## Ecran de demonstration et donnees
    - l. 218, 20 lignes, 588 o — ## Arborescence attendue
    - l. 238, 7 lignes, 331 o — ## Documentation a produire
    - l. 245, 21 lignes, 949 o — ## Recette
    - l. 266, 93 lignes, 6 Ko — ## Points volontairement hors perimetre
- **Code** (manifeste `paths:`, 2 prefixe(s)) :
  - `assets/styles` — 3 fichier(s)
  - `src/Asset` — 1 fichier(s)
  - Roles (derives du chemin) : service 1, front (js / scss) 3.
  - Fichiers (4 au total) :
    - `assets/styles/_brand.scss` — front
    - `assets/styles/_socle.scss` — front
    - `assets/styles/app.scss` — front
    - `src/Asset/ViteBasePathListener.php` — service
- **Ce qui bouge avec ce module** (git, co-modification) — c'est le signal qui repond a
  « qu'est-ce que je casse si je touche ca » :
  - `config/packages/framework.yaml` — config, module non rattache — 1 commit(s) commun(s) avec `src/Asset/ViteBasePathListener.php`
  - `config/packages/pentatrion_vite.yaml` — config, module non rattache — 1 commit(s) commun(s) avec `src/Asset/ViteBasePathListener.php`
- **Tickets livres** (perimetre depose, 1) :
  - #78 — SOCLE - LOT 1 — 58 fichier(s) — `c8bb1ff580805c474418f6a0a7a767e297a67a4c`

