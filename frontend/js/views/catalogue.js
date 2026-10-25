/*
 * Catalogue view: model cards, bedroom/feature filters and the comparison table.
 * Everything is generated from js/data/houses.js.
 */

import { houses, featureOptions, bedroomOptions } from '../data/houses.js';
import {
  houseCard, packageCards, escapeHtml, formatPrice, formatSize, formatMetres, pluralize,
} from '../components.js';

export function initCatalogue({ outlet }) {
  const form = outlet.querySelector('[data-filters]');
  const grid = outlet.querySelector('[data-house-grid]');
  const emptyState = outlet.querySelector('[data-empty-state]');
  const count = outlet.querySelector('[data-result-count]');

  renderFilterOptions(outlet);
  outlet.querySelector('[data-compare-table]').innerHTML = comparisonTable();
  outlet.querySelector('[data-packages]').innerHTML = packageCards();

  function applyFilters() {
    const { bedrooms, features } = readFilters(form);
    const matches = houses.filter(
      (house) =>
        (bedrooms === null || house.bedrooms === bedrooms) &&
        features.every((feature) => house.features.includes(feature)),
    );

    grid.innerHTML = matches.map(houseCard).join('');
    grid.hidden = matches.length === 0;
    emptyState.hidden = matches.length > 0;
    count.textContent = `Showing ${matches.length} of ${pluralize(houses.length, 'model')}`;
  }

  form.addEventListener('change', applyFilters);
  // A "reset" event fires before the form clears its inputs, so wait one tick.
  form.addEventListener('reset', () => setTimeout(applyFilters));
  outlet.querySelectorAll('[data-clear-filters]').forEach((button) => {
    if (button.type !== 'reset') button.addEventListener('click', () => form.reset());
  });

  applyFilters();
}

function renderFilterOptions(outlet) {
  const bedroomChoices = [{ value: '', label: 'All' }].concat(
    bedroomOptions.map((n) => ({ value: String(n), label: String(n) })),
  );
  outlet.querySelector('[data-bedroom-options]').innerHTML = bedroomChoices
    .map(
      (choice, i) => `
        <label class="chip-option">
          <input type="radio" name="bedrooms" value="${choice.value}" ${i === 0 ? 'checked' : ''}>
          <span>${escapeHtml(choice.label)}</span>
        </label>`,
    )
    .join('');

  outlet.querySelector('[data-feature-options]').innerHTML = featureOptions
    .map(
      (feature) => `
        <label class="chip-option">
          <input type="checkbox" name="features" value="${escapeHtml(feature)}">
          <span>${escapeHtml(feature)}</span>
        </label>`,
    )
    .join('');
}

function readFilters(form) {
  const data = new FormData(form);
  const bedrooms = data.get('bedrooms');
  return {
    bedrooms: bedrooms ? Number(bedrooms) : null,
    features: data.getAll('features'),
  };
}

function comparisonTable() {
  const listed = (yes) =>
    yes
      ? '<span class="tick" aria-label="Listed">✓</span>'
      : '<span class="dash" aria-label="Not listed">—</span>';

  // Each row: a label and how to show the value for one house.
  const rows = [
    ['Living area', (h) => `${h.area} m²`],
    ['Bedrooms', (h) => h.bedrooms],
    ['Bathrooms', (h) => h.bathrooms],
    ...houses[0].rooms.map((room) => [room, (h) => listed(h.rooms.includes(room))]),
    ...featureOptions.map((feature) => [feature, (h) => listed(h.features.includes(feature))]),
    ['ROH BAU', (h) => `<strong>${formatPrice(h.prices.rohBau)}</strong>`],
    ['Turnkey', (h) => `<strong>${formatPrice(h.prices.turnkey)}</strong>`],
    ['Footprint', (h) => formatSize(h.dimensions.footprint)],
    ['Side room', (h) => (h.dimensions.sideRoom ? formatSize(h.dimensions.sideRoom) : listed(false))],
    ['Height', (h) => formatMetres(h.dimensions.height)],
    ['', (h) => `<a class="text-link" href="#/houses/${h.slug}">View ${escapeHtml(h.name)}</a>`],
  ];

  const head = houses.map((h) => `<th scope="col">${escapeHtml(h.name)}</th>`).join('');
  const body = rows
    .map(
      ([label, cell]) => `
        <tr>
          <th scope="row">${escapeHtml(label)}</th>
          ${houses.map((h) => `<td>${cell(h)}</td>`).join('')}
        </tr>`,
    )
    .join('');

  return `
    <caption class="visually-hidden">Comparison of the four A-frame models</caption>
    <thead><tr><td></td>${head}</tr></thead>
    <tbody>${body}</tbody>`;
}
