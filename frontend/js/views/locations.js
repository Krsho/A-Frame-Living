/*
 * Locations view: completed projects with a model filter.
 * Data comes from js/data/locations.js and js/data/houses.js.
 */

import { locations, countByModel, mapUrl } from '../data/locations.js';
import { houses, findHouse } from '../data/houses.js';
import { escapeHtml, pluralize } from '../components.js';

export function initLocations({ outlet }) {
  const counts = countByModel();
  const grid = outlet.querySelector('[data-location-grid]');
  const countText = outlet.querySelector('[data-location-count]');
  const form = outlet.querySelector('[data-location-filter]');

  // Key numbers
  const [topSlug] = Object.entries(counts).sort((a, b) => b[1] - a[1])[0];
  outlet.querySelector('[data-stat="locations"]').textContent = locations.length;
  outlet.querySelector('[data-stat="models"]').textContent = Object.keys(counts).length;
  outlet.querySelector('[data-stat="top-model"]').textContent = findHouse(topSlug).name;

  // Filter chips: "All" plus every model that has been built, with its count
  const options = [{ value: '', label: 'All', count: locations.length }].concat(
    houses.filter((h) => counts[h.slug]).map((h) => ({ value: h.slug, label: h.name, count: counts[h.slug] })),
  );
  outlet.querySelector('[data-model-options]').innerHTML = options
    .map(
      (option, i) => `
        <label class="chip-option">
          <input type="radio" name="model" value="${option.value}" ${i === 0 ? 'checked' : ''}>
          <span>${escapeHtml(option.label)} <span class="chip-count">${option.count}</span></span>
        </label>`,
    )
    .join('');

  function render() {
    const model = new FormData(form).get('model');
    const shown = model ? locations.filter((l) => l.model === model) : locations;
    grid.innerHTML = shown.map(locationCard).join('');
    countText.textContent = `Showing ${shown.length} of ${pluralize(locations.length, 'location')}`;
  }

  form.addEventListener('change', render);
  render();
}

function locationCard(location) {
  const house = findHouse(location.model);
  return `
    <li class="location-card">
      <div class="location-card__top">
        <a class="badge" href="#/houses/${house.slug}">${escapeHtml(house.name)}</a>
        <span class="location-card__setting">${escapeHtml(location.setting)}</span>
      </div>
      <h3 class="location-card__city">${escapeHtml(location.city)}</h3>
      <p class="location-card__region">
        <svg class="location-card__pin" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Z" />
          <circle cx="12" cy="9.5" r="2.5" />
        </svg>
        ${escapeHtml(location.region)}, ${escapeHtml(location.country)}
      </p>
      <div class="location-card__links">
        <a class="text-link" href="#/houses/${house.slug}">The ${escapeHtml(house.name)} model</a>
        <a class="text-link" href="${mapUrl(location)}" target="_blank" rel="noopener">
          Map<span class="visually-hidden"> of ${escapeHtml(location.city)} (opens in a new tab)</span> ↗
        </a>
      </div>
    </li>`;
}
