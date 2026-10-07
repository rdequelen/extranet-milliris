# Pas de symfony/flex sur ce depot

Le projet n'installe pas `symfony/flex` : ses recettes auraient injecte des
lignes dans des fichiers ecrits a la main (`templates/base.html.twig`,
`assets/app.js`, via les recettes pentatrion/vite-bundle et stimulus-bundle)
au premier `composer install`.

**Pourquoi** : sans Flex, composer n'a plus de contrainte de plate-forme
Symfony et tire par defaut la derniere version majeure disponible (8.0) pour
tout paquet `symfony/*`, alors que l'application cible 7.4. Les composants
Symfony sont donc epingles explicitement en `7.4.*` dans `composer.json`.

**Comment l'appliquer** : tout futur `composer require symfony/<x>` (dans
n'importe quel module, pas seulement socle) doit etre epingle en `7.4.*` a la
main dans `composer.json`, sinon il remonte en 8.x en silence et peut
desynchroniser la pile. Reintroduire Flex reste possible le jour ou la config
est stable, en ajoutant `extra.symfony.require: "7.4.*"` dans
`composer.json`.
