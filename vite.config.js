import { defineConfig } from 'vite';
import symfonyPlugin from 'vite-plugin-symfony';

export default defineConfig({
    plugins: [
        symfonyPlugin({
            // Active le pont Stimulus : le module virtuel `virtual:symfony/controllers`
            // est construit a partir de assets/controllers.json, et les fichiers
            // assets/controllers/*_controller.js sont chargeables via `?stimulus`.
            stimulus: true,
        }),
    ],

    build: {
        // vite-plugin-symfony fixe deja base=/build/, outDir=public/build,
        // manifest=true et publicDir=false. On ne declare donc que l'entree.
        rollupOptions: {
            input: {
                app: './assets/app.js',
            },
        },
    },

    experimental: {
        // L'APPLICATION PEUT ETRE SERVIE SOUS UN PREFIXE D'URL (environnements de
        // recette du Dev Center : `/<app>/recette/<n>/`, prefixe retire par le
        // proxy et republie en `X-Forwarded-Prefix`). `base` vaut `/build/` et est
        // fige au BUILD : tout ce qui en sort est un chemin a la racine de l'hote.
        //
        // Pour les balises <link>/<script>, c'est App\Asset\ViteBasePathListener
        // qui recolle la base de la requete au moment du rendu. Mais les URL
        // ecrites DANS le CSS compile (police bootstrap-icons, logo MILLIRIS en
        // background-image) ne passent par aucun code PHP : sans ce reglage elles
        // restent en `/build/assets/...`, sont demandees a la racine de l'hote —
        // donc au Dev Center, qui ne porte pas ces fichiers — et la page s'affiche
        // avec son CSS mais sans logo ni icones.
        //
        // `relative: true` les rend relatives a la feuille de style elle-meme
        // (`url(milliris-logo-<hash>.png)`) : le navigateur les resout depuis l'URL
        // du CSS, donc elles suivent le prefixe quel qu'il soit, sans rien savoir
        // de lui. On ne touche QUE `hostType === 'css'` : rendre les autres
        // relatives les resoudrait depuis l'URL de la PAGE, qui varie d'une route a
        // l'autre.
        renderBuiltUrl(filename, { hostType }) {
            return 'css' === hostType ? { relative: true } : undefined;
        },
    },

    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap 5.3 est encore ecrit avec `@import` et des fonctions
                // de couleur historiques. Sans ce filtre, chaque build deverse
                // des milliers de lignes d'avertissements Sass qui noient les
                // vraies erreurs. A retirer le jour ou Bootstrap passe a `@use`.
                silenceDeprecations: [
                    'import',
                    'global-builtin',
                    'color-functions',
                ],
                quietDeps: true,
            },
        },
    },

    server: {
        host: '127.0.0.1',
        port: 5173,
    },
});
