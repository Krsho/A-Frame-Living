/*
 * Reusable UI building blocks.
 *
 * Most functions return an HTML string so views can combine them,
 * e.g. `container.innerHTML = houses.map(houseCard).join('')`.
 * All text values pass through escapeHtml() so that, once the data
 * comes from the API, it cannot inject HTML into the page.
 */

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

export function priceBlock(house) {
  return `
    <dl class="prices">
      <div>
        <dt>ROH BAU</dt>
        <dd>${formatPrice(house.prices.rohBau)}</dd>
      </div>
      <div>
        <dt>Turnkey</dt>
        <dd>${formatPrice(house.prices.turnkey)}</dd>
      </div>
    </dl>`;
}

export function houseCard(house) {
  const detailUrl = `#/houses/${encodeURIComponent(house.slug)}`;
  return `
    <article class="house-card">
      <a class="house-card__media media media--4x3" href="${detailUrl}" tabindex="-1" aria-hidden="true">
        ${image({ src: house.images[0], alt: `${house.name} A-frame house` })}
      </a>
      <div class="house-card__body">
        <p class="eyebrow">${escapeHtml(specLine(house))}</p>
        <h3 class="house-card__title"><a href="${detailUrl}">${escapeHtml(house.name)}</a></h3>
        <p class="house-card__summary">${escapeHtml(house.summary)}</p>
        ${priceBlock(house)}
        <div class="house-card__footer">
          <a class="btn btn--outline" href="${detailUrl}">
            View details<span class="visually-hidden"> of the ${escapeHtml(house.name)} model</span>
          </a>
        </div>
      </div>
    </article>`;
}
