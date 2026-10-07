import { Controller } from '@hotwired/stimulus';

const STORAGE_KEY = 'milliris.theme';

/**
 * Bascule clair / sombre.
 *
 * La valeur initiale n'est PAS posee ici : un script en ligne dans
 * templates/base.html.twig l'ecrit sur <html> avant le premier rendu, sinon la
 * page clignote en clair avant de passer en sombre. Ce controleur ne gere que
 * la bascule, l'etat du bouton, et le suivi de la preference systeme tant que
 * l'utilisateur n'a pas choisi explicitement.
 *
 * Le choix est memorise dans localStorage, donc cote navigateur : il survit a
 * une navigation Turbo comme a un rechargement, sans aller-retour serveur.
 */
export default class extends Controller {
    static targets = ['icon'];

    connect() {
        this.media = window.matchMedia('(prefers-color-scheme: dark)');
        this.onSystemChange = () => {
            if (!this.storedTheme) {
                this.apply(this.media.matches ? 'dark' : 'light', false);
            }
        };
        this.media.addEventListener('change', this.onSystemChange);
        this.render();
    }

    disconnect() {
        this.media.removeEventListener('change', this.onSystemChange);
    }

    toggle() {
        this.apply(this.currentTheme === 'dark' ? 'light' : 'dark', true);
    }

    apply(theme, persist) {
        document.documentElement.setAttribute('data-bs-theme', theme);

        if (persist) {
            try {
                window.localStorage.setItem(STORAGE_KEY, theme);
            } catch (error) {
                // Navigation privee ou stockage refuse : la bascule reste
                // valable pour la session, on n'a rien de plus a faire.
            }
        }

        this.render();
    }

    render() {
        const dark = this.currentTheme === 'dark';
        const label = dark ? 'Passer en theme clair' : 'Passer en theme sombre';

        if (this.hasIconTarget) {
            this.iconTarget.className = `bi ${dark ? 'bi-sun' : 'bi-moon-stars'}`;
        }

        this.element.setAttribute('aria-pressed', dark ? 'true' : 'false');
        this.element.setAttribute('aria-label', label);
        this.element.setAttribute('title', label);
    }

    get currentTheme() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark'
            ? 'dark'
            : 'light';
    }

    get storedTheme() {
        try {
            return window.localStorage.getItem(STORAGE_KEY);
        } catch (error) {
            return null;
        }
    }
}
