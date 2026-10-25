/*
 * Reusable UI building blocks.
 *
 * Most functions return an HTML string so views can combine them,
 * e.g. `container.innerHTML = houses.map(houseCard).join('')`.
 * All text values pass through escapeHtml() so that, once the data
 * comes from the API, it cannot inject HTML into the page.
 */

import { packages } from './data/packages.js';

const priceFormatter = new Intl.NumberFormat('en-IE', {
  style: 'currency',
  currency: 'EUR',
  maximumFractionDigits: 0,
});

export function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

export function formatPrice(amount) {
  return priceFormatter.format(amount);
}

const metreFormatter = new Intl.NumberFormat('en-IE', { maximumFractionDigits: 1 });

/** 5.8 -> "5.8 m" */
export function formatMetres(value) {
  return `${metreFormatter.format(value)} m`;
}

/** [2.5, 4] -> "2.5 × 4 m" */
export function formatSize([width, length]) {
  return `${metreFormatter.format(width)} × ${metreFormatter.format(length)} m`;
}

export function pluralize(count, word) {
  return `${count} ${word}${count === 1 ? '' : 's'}`;
}

/** A visible label for business information that is still missing. */
export function todo(text) {
  return `<span class="todo">TODO: ${escapeHtml(text)}</span>`;
}

/**
 * An <img> that turns into a labelled placeholder if the file is missing.
 * The swap is done by handleImageError(), registered once in app.js.
 */
export function image({ src, alt, eager = false }) {
  return `<img src="${escapeHtml(src)}" alt="${escapeHtml(alt)}"
    loading="${eager ? 'eager' : 'lazy'}" data-placeholder>`;
}

export function handleImageError(event) {
  const img = event.target;
  if (!(img instanceof HTMLImageElement) || !img.hasAttribute('data-placeholder')) return;
  img.replaceWith(createPlaceholder(img.getAttribute('src'), img.alt));
}

function createPlaceholder(src, alt) {
  const box = document.createElement('div');
  box.className = 'img-placeholder';
  box.setAttribute('role', 'img');
  box.setAttribute('aria-label', `${alt} (photo coming soon)`);

  const label = document.createElement('span');
  label.className = 'img-placeholder__label';
  label.textContent = 'Photo coming soon';

  const path = document.createElement('span');
  path.className = 'img-placeholder__path';
  path.textContent = `Add: frontend/${src}`;

  box.append(label, path);
  return box;
}

export function specLine(house) {
  return `${house.area} m² · ${pluralize(house.bedrooms, 'bedroom')} · ${pluralize(house.bathrooms, 'bathroom')}`;
}

/** Chips for the model's confirmed extra features (or the rooms all models share). */
export function featureChips(house) {
  if (house.features.length === 0) {
    return `<p class="feature-note">${escapeHtml(house.rooms.join(' · '))}</p>`;
  }
  const chips = house.features.map((feature) => `<li class="chip">${escapeHtml(feature)}</li>`).join('');
  return `<ul class="chip-list" aria-label="Features">${chips}</ul>`;
}

export function priceBlock(house) {
  const items = packages
    .map((pkg) => `
      <div>
        <dt>${escapeHtml(pkg.name)}</dt>
        <dd>${formatPrice(house.prices[pkg.key])}</dd>
      </div>`)
    .join('');
  return `<dl class="prices">${items}</dl>`;
}

// Inquiry statuses (same values as the planned `inquiries.status` column).
const inquiryStatusLabels = {
  new: 'New',
  in_progress: 'In progress',
  answered: 'Answered',
  closed: 'Closed',
};

/** A coloured label for an inquiry status, e.g. statusBadge('in_progress'). */
export function statusBadge(status) {
  const label = inquiryStatusLabels[status] ?? status;
  return `<span class="status status--${escapeHtml(status)}">${escapeHtml(label)}</span>`;
}

/**
 * The two packages with their descriptions. When a house is given,
 * each card also shows that model's price for the package.
 */
export function packageCards(house = null) {
  const cards = packages
    .map((pkg) => `
      <li class="package-card">
        <h3 class="package-card__name">${escapeHtml(pkg.name)}</h3>
        ${house ? `<p class="package-card__price">${formatPrice(house.prices[pkg.key])}</p>` : ''}
        <p class="package-card__text">${escapeHtml(pkg.description)}</p>
      </li>`)
    .join('');
  return `<ul class="package-cards">${cards}</ul>`;
}

export function houseCard(house) {
  const detailUrl = `#/houses/${encodeURIComponent(house.slug)}`;
  return `
    <article class="house-card">
      <a class="house-card__media media media--3x2" href="${detailUrl}" tabindex="-1" aria-hidden="true">
        ${image(house.images[0])}
      </a>
      <div class="house-card__body">
        <p class="eyebrow">${escapeHtml(specLine(house))}</p>
        <h3 class="house-card__title"><a href="${detailUrl}">${escapeHtml(house.name)}</a></h3>
        <p class="house-card__summary">${escapeHtml(house.summary)}</p>
        ${featureChips(house)}
        <div class="house-card__footer">
          ${priceBlock(house)}
          <a class="btn btn--outline" href="${detailUrl}">
            View details<span class="visually-hidden"> of the ${escapeHtml(house.name)} model</span>
          </a>
        </div>
      </div>
    </article>`;
}
