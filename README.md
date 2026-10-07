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

## `.devcenter/app.yaml`

Le fichier `.devcenter/app.yaml` declare le **contrat de commandes** de cette
application (installer, construire, verifier, tester, migrer, servir). Il est **versionne
avec le code**, et c'est volontaire : un ticket qui monte une version de pile doit etre
testable **par sa propre branche**. Le fichier gagne sur tout reglage saisi a l'ecran.

