# Le fournisseur d'utilisateurs Doctrine ne revalide pas `is_active`

Le module [[socle.clients]] utilise un `UserProvider` maison
(`src/Security/UserProvider.php`) plutot que le fournisseur Doctrine fourni
par `security.yaml` (`entity: { class: User }`).

**Pourquoi** : Symfony recharge l'utilisateur depuis le fournisseur configure
a chaque requete pour verifier que la session reste valide. Le fournisseur
Doctrine standard recharge par CLE PRIMAIRE et ne reverifie aucun champ
metier comme `is_active` : un compte desactive en base garderait donc ses
sessions deja ouvertes jusqu'a expiration du cookie, au lieu d'etre coupe a
la requete suivante.

**Comment l'appliquer** : toute regle "un compte desactive perd l'acces
immediatement" sur une entite Symfony Security doit passer par un
fournisseur d'utilisateurs personnalise qui revalide la condition a chaque
rechargement, jamais par le fournisseur `entity:` par defaut. A reverifier si
une autre entite porteuse de droits (ex: roles par module) est introduite
plus tard sur ce depot.
