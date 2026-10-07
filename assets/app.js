// Entree unique de la chaine d'assets (voir vite.config.js).
//
// L'ordre compte : Stimulus et Turbo d'abord (bootstrap.js), la feuille de
// style ensuite, le JS de Bootstrap en dernier.

import './bootstrap.js';

import './styles/app.scss';
import 'bootstrap-icons/font/bootstrap-icons.css';

// `import 'bootstrap'` branche l'API par attributs (data-bs-toggle) utilisee
// par l'offcanvas du menu et les menus deroulants de l'en-tete. On expose en
// plus l'objet sur `window` : le lot 3 en aura besoin pour instancier des
// Toast depuis un Turbo Stream.
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;
