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
