/*
 * Entry point of the frontend.
 * Registers all routes, sets up the header navigation and starts the router.
 */

import { startRouter } from './router.js';
import { handleImageError } from './components.js';
import { initHome } from './views/home.js';
import { initCatalogue } from './views/catalogue.js';
import { initHouseDetail } from './views/house-detail.js';

const routes = [
  { path: '/', view: 'home', init: initHome },
  { path: '/houses', view: 'houses', title: 'Models', init: initCatalogue },
  { path: '/houses/:slug', view: 'house-detail', title: 'Model', init: initHouseDetail },
];

const notFound = { view: 'not-found', title: 'Page not found' };

const navToggle = document.querySelector('[data-nav-toggle]');
const siteNav = document.getElementById('site-nav');

function setMenuOpen(isOpen) {
  navToggle.setAttribute('aria-expanded', String(isOpen));
  siteNav.classList.toggle('is-open', isOpen);
}

navToggle.addEventListener('click', () => {
  setMenuOpen(navToggle.getAttribute('aria-expanded') !== 'true');
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') setMenuOpen(false);
});

// Highlight the nav link of the current section, e.g. "#/houses/norway" -> "houses".
function updateActiveNav({ segments }) {
  const section = segments[0] ?? 'home';
  document.querySelectorAll('[data-nav]').forEach((link) => {
    if (link.dataset.nav === section) {
      link.setAttribute('aria-current', 'page');
    } else {
      link.removeAttribute('aria-current');
    }
  });
  setMenuOpen(false);
}

// The skip link focuses <main> directly so it does not change the route.
document.querySelector('[data-skip-link]').addEventListener('click', (event) => {
  event.preventDefault();
  document.getElementById('app').focus();
});

// Image "error" events do not bubble, so listen in the capture phase.
document.addEventListener('error', handleImageError, true);

document.querySelector('[data-year]').textContent = new Date().getFullYear();

startRouter({
  outlet: document.getElementById('app'),
  routes,
  notFound,
  siteName: 'A-Frame Living',
  onNavigate: updateActiveNav,
});
