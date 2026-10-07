# Les assets doivent repartir de la base de la requete

L'application n'est pas toujours servie a la racine d'un hote : les
environnements de recette du Dev Center sont publies sous un prefixe d'URL
(`/<app>/recette/<n>/`), que le proxy RETIRE avant de transmettre la requete et
republie dans l'en-tete `X-Forwarded-Prefix`. Tout chemin d'asset ecrit avec une
barre en dur (`/build/...`, `/css/...`) sort donc du prefixe et est demande a la
racine de l'hote — c'est-a-dire au Dev Center, qui ne porte pas ces fichiers.

**Pourquoi c'est le defaut le plus trompeur du depot** : la page REPOND (200),
ses liens sont justes — le routeur Symfony honore deja le prefixe — et elle
s'affiche NUE, sans CSS ni JavaScript. On ne conclut pas « panne », on conclut
« cette page est moche ». Rien ne se voit en local, ou il n'y a pas de prefixe.

**Comment l'appliquer** :
- cote PHP/Twig, jamais de chemin d'asset en dur : `asset()` ou, pour les
  balises de pentatrion/vite-bundle (qui recopie les chemins figes par Vite),
  `App\Asset\ViteBasePathListener`, qui prefixe par `Request::getBasePath()` —
  et pas `getBaseUrl()`, qui garde le nom du script ;
- cote Vite, les URL ecrites DANS le CSS compile (polices, images de fond) ne
  passent par aucun code PHP : elles sont rendues relatives a la feuille de
  style par `experimental.renderBuiltUrl` (`{ relative: true }` pour
  `hostType === 'css'`) dans `vite.config.js` ;
- ne JAMAIS declarer `framework.trusted_proxies` / `trusted_headers` dans
  `config/packages/framework.yaml` : leur defaut lit `SYMFONY_TRUSTED_PROXIES` /
  `SYMFONY_TRUSTED_HEADERS`, que l'hote pose. Une valeur ecrite dans le depot
  gagnerait sur l'environnement et le prefixe redeviendrait invisible.

**Comment le verifier sans proxy** : l'en-tete suffit, si la boucle locale est un
proxy de confiance.

```
SYMFONY_TRUSTED_PROXIES=private_ranges \
SYMFONY_TRUSTED_HEADERS=x-forwarded-for,x-forwarded-host,x-forwarded-proto,x-forwarded-port,x-forwarded-prefix \
php -S 127.0.0.1:8123 -t public
curl -s -H 'X-Forwarded-Prefix: /pfx' http://127.0.0.1:8123/ | grep build/assets
```

Toutes les URL emises doivent commencer par `/pfx/`.
