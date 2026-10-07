import {
    registerControllers,
    startStimulusApp,
} from 'vite-plugin-symfony/stimulus/helpers';

// startStimulusApp() enregistre les controleurs declares dans
// assets/controllers.json — dont `turbo-core` de symfony/ux-turbo, qui demarre
// Turbo Drive. C'est de la que vient la navigation sans rechargement.
const app = startStimulusApp();

// ... et ceux ecrits dans assets/controllers/ : un fichier `foo_controller.js`
// devient l'identifiant `foo`, utilisable en data-controller="foo".
registerControllers(
    app,
    import.meta.glob('./controllers/*_controller.js', {
        query: '?stimulus',
        eager: true,
    }),
);

export { app };
