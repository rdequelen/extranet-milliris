# Clients

Couche de securite de l'extranet : ferme l'application a tout visiteur non
identifie, porte le modele client/utilisateur (societe + personne rattachee)
et la connexion sans mot de passe par lien signe envoye par courriel.

## Composants cles
- `src/Entity/Client.php`, `src/Entity/User.php`,
  `src/Repository/{Client,User}Repository.php` — societe cliente (code ERP +
  nom) et utilisateur rattache (mail, prenom, nom, `is_active`, roles, client
  non nul).
- `src/Security/UserProvider.php` — fournisseur d'utilisateurs maison (pas le
  fournisseur Doctrine) qui recharge `is_active` a chaque requete.
- `src/Security/LoginLinkSender.php`, `src/Security/LoginFormEntryPoint.php`,
  `src/Controller/SecurityController.php`, `templates/security/*.html.twig`,
  `templates/emails/login_link.*` — formulaire de connexion, envoi et
  verification du lien signe (`login_link` natif Symfony).
- `src/Command/CreateUserCommand.php` (`app:user:create`) — amorce le premier
  compte (voir README, section "Premier acces").
- `config/packages/security.yaml` — pare-feu, `access_control`, authenticator
  `login_link`.

## Decisions structurantes
- `login_link` natif Symfony plutot qu'une table de jetons : le lien est signe
  par `APP_SECRET`, rien a stocker ni a purger, une fuite de la base ne donne
  aucun acces.
- Pas de `max_uses` sur le lien (reutilisable pendant 5 minutes) : les
  antivirus et previsualiseurs de messagerie ouvrent les liens avant
  l'humain, un lien a usage unique serait consomme par le robot. A
  rediscuter si la messagerie des clients ne le fait pas. Voir
  [[project_login-link-sans-limite-usage]].
- `UserProvider` maison et non le fournisseur Doctrine : celui-ci recharge par
  cle primaire sans regarder `is_active`, une desactivation ne couperait pas
  les sessions ouvertes. Voir
  [[project_fournisseur-utilisateur-recharge-is-active]].
- Session d'un an plutot que `remember_me` : "connecte sans date limite" est
  porte par un seul mecanisme, pas deux cookies signes.
- Table `app_user` (pas `user`, mot reserve PostgreSQL) ; noms d'index et de
  contrainte ecrits a la main, identiques dans l'entite et la migration.
- Cote inverse `Client -> users` non mappe, et relation vers `Client` en
  `fetch: EAGER` : l'utilisateur est serialise en session a chaque requete,
  une collection Doctrine n'a rien a y faire ; l'en-tete affiche le nom de la
  societe sur chaque page.
- Anti-enumeration : reponse identique pour adresse inconnue, compte inactif
  et panne SMTP (l'exception de transport est journalisee, pas propagee).
- Erreur de formulaire rendue en 422, pas 200 : Turbo Drive ignore un 200 sur
  une soumission et laisserait la page inchangee.

## Pieges
- Toute page etant desormais protegee, un test fonctionnel authentifie exige
  une base : le pare-feu recharge l'utilisateur a chaque requete, donc
  `loginUser()` sur une entite non persistee est deconnecte aussitot. Voir
  `tests/Support/NeedsTestDatabase.php`, qui saute ces tests quand la base de
  test manque (contrat de pile : `vendor/bin/phpunit` reste vert, les tests
  sautes ne se voient que dans le decompte).
- `MAILER_DSN=null://null` par defaut : aucun courriel ne part tant qu'un vrai
  transport n'est pas pose dans `.env.local`. En dev uniquement, le lien est
  aussi ecrit dans le journal.
- Le pare-feu `dev` exclut `^/build/` : sans cela, le proxy Vite de dev tombe
  sous `access_control` et la page de connexion s'affiche nue.
- `composer.lock` n'a pas pu etre regenere a la livraison : un `composer
  install` seul installe l'ancien verrou, sans security-bundle ni mailer. Le
  `composer update symfony/security-bundle symfony/security-csrf
  symfony/mailer --with-all-dependencies` est obligatoire avant.

## Non prouve au merge (LOT 1)
Worktree nu a la livraison : seuls `php -l` (17 fichiers), le parsing YAML des
configs et celui de `composer.json` sont passes. La migration
(`Version20261007200000`) n'a jamais ete jouee contre PostgreSQL, les tests
avec base (`NavigationTest`, `MagicLinkLoginTest`) n'ont jamais tourne une
seule fois, le rendu de l'ecran de connexion (SCSS neuf) n'a pas ete vu a
l'ecran (clair/sombre/mobile), et l'URL absolue du lien de connexion sous
prefixe d'URL (recette) n'a pas ete verifiee.

## Liens
- Spec declaree dans `docs/modules/manifest.yaml` :
  `docs/specs/socle-clients_ajoute-une-couche-de-securite-sur-l-application-on-ne-peut-y-acceder-que-si-l-utilisateur-est-loggue.md`
  — fichier absent du depot a la date de ce lot, rien a y comparer.
- Aucune fiche `docs/roadmap/MODULE_*.md` trouvee pour ce module a la date de
  ce lot.
