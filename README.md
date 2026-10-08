# Extranet client

> Client : **MILLIRIS**
> Pile : **Web — Symfony + PostgreSQL** (`web-symfony-postgres`)
> Branche par defaut : `main`

## Comment ce depot evolue

Ce depot est **pilote depuis le Dev Center** (Centre de developpement). Le cycle est le suivant :

1. une douleur ou une demande devient un ticket dans le Dev Center ;
2. un agent **qualifie** le ticket (il ecrit une spec, en lecture seule sur ce code) ;
3. un humain fige la spec et valide le lancement ;
4. un second agent **developpe** dans un worktree isole et **pousse une branche** ici ;
5. un humain relit la branche et declenche le merge sur `main`.

⛔ **Aucun agent ne deploie.** La chaine s'arrete au `push`. Ce qui se passe ensuite
(integration continue, `git pull`, hook, store) appartient a ce depot et a son
exploitation — le Dev Center ne s'y connecte pas et n'y detient aucun acces.

## Premier acces a l'application

L'application est **fermee** : aucune page n'est accessible sans compte. Il n'y a
**pas de mot de passe** — on saisit son adresse de courriel et on recoit un lien
de connexion valable 5 minutes.

Deux consequences a l'installation :

1. **le courriel doit partir**, sinon personne ne peut entrer. Poser un vrai
   transport dans `.env.local` (`MAILER_DSN=smtp://...`) ; le defaut
   `null://null` n'envoie rien. En environnement de developpement, le lien est
   aussi ecrit dans le journal, ce qui permet de se connecter sans SMTP.
2. **le premier compte se cree en ligne de commande** (il n'existe aucun ecran
   d'inscription, ni de reinitialisation a tenter) :

```
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:user:create admin@milliris.com Prenom Nom MILLIRIS --client-name="MILLIRIS" --admin
```

Les comptes suivants s'ajoutent de la meme facon, en reprenant le code interne
de la societe cliente (cle partagee avec l'ERP) :

```
php bin/console app:user:create contact@societe.fr Prenom Nom CODECLIENT
```

## `.devcenter/app.yaml`

Le fichier `.devcenter/app.yaml` declare le **contrat de commandes** de cette
application (installer, construire, verifier, tester, migrer, servir). Il est **versionne
avec le code**, et c'est volontaire : un ticket qui monte une version de pile doit etre
testable **par sa propre branche**. Le fichier gagne sur tout reglage saisi a l'ecran.

