/*
 * House detail view for one model, e.g. #/houses/norway.
 * The slug from the URL selects the model from js/data/houses.js.
 */

import { houses, findHouse } from '../data/houses.js';
import { locationsForModel } from '../data/locations.js';
import {
  escapeHtml, image, featureChips, priceBlock, packageCards, specLine, houseCard, pluralize, formatSize, formatMetres,
} from '../components.js';
import { openLightbox } from '../lightbox.js';

export function initHouseDetail({ outlet, params }) {
  const house = findHouse(params.slug);
  if (!house) return { notFound: true };

  outlet.querySelectorAll('[data-field="name"]').forEach((el) => (el.textContent = house.name));
  outlet.querySelector('[data-field="spec-line"]').textContent = specLine(house);
  outlet.querySelector('[data-field="summary"]').textContent = house.summary;

  outlet.querySelector('[data-main-image]').innerHTML = photoButton(house.images[0], 0, true);
  outlet.querySelector('[data-features]').innerHTML = featureChips(house);
  outlet.querySelector('[data-prices]').innerHTML = priceBlock(house);
  outlet.querySelector('[data-packages]').innerHTML = packageCards(house);

  const inquireLink = outlet.querySelector('[data-inquire-link]');
  inquireLink.href = `#/contact?model=${encodeURIComponent(house.slug)}`;
  inquireLink.textContent = `Inquire about ${house.name}`;

  outlet.querySelector('[data-spec-list]').innerHTML = specList(house);
  outlet.querySelector('[data-locations]').innerHTML = locationList(house);
  renderGallery(outlet, house);

  // Any photo button (main photo or gallery tile) opens the viewer at that photo.
  // The listeners go on elements inside this view (not on <main>, which stays
  // between pages), so each visit starts with fresh listeners.
  const openFromButton = (event) => {
    const button = event.target.closest('[data-lightbox-index]');
    if (button) openLightbox(house.images, Number(button.dataset.lightboxIndex));
  };
  outlet.querySelector('[data-main-image]').addEventListener('click', openFromButton);
  outlet.querySelector('[data-gallery]').addEventListener('click', openFromButton);

  outlet.querySelector('[data-other-houses]').innerHTML = houses
    .filter((other) => other.slug !== house.slug)
    .map(houseCard)
    .join('');

  return { title: house.name };
}

function specList(house) {
  const items = [
    ['Living area', `${house.area} m²`],
    ['Bedrooms', house.bedrooms],
    ['Bathrooms', house.bathrooms],
    ['Rooms', house.rooms.map(escapeHtml).join(', ')],
    ['Features', house.features.length ? house.features.map(escapeHtml).join(', ') : 'No additional features listed'],
    ['Footprint', formatSize(house.dimensions.footprint)],
    ...(house.dimensions.sideRoom ? [['Side room', formatSize(house.dimensions.sideRoom)]] : []),
    ['Height', formatMetres(house.dimensions.height)],
  ];
  return items.map(([label, value]) => `<div><dt>${label}</dt><dd>${value}</dd></div>`).join('');
}

function locationList(house) {
  const locations = locationsForModel(house.slug);
  if (locations.length === 0) {
    return `<p class="text-muted">No completed ${escapeHtml(house.name)} projects are listed yet.</p>`;
  }
  const countries = [...new Set(locations.map((l) => l.country))].join(', ');
  const items = locations.map((l) => `<li>${escapeHtml(l.city)}</li>`).join('');
  return `
    <p>${pluralize(locations.length, 'completed project')} in ${escapeHtml(countries)}:</p>
    <ul class="location-list">${items}</ul>`;
}

/** A photo inside a button that opens the full-screen viewer. */
function photoButton(photo, index, eager = false) {
  return `
    <button class="photo-button" type="button" data-lightbox-index="${index}"
      aria-label="View larger: ${escapeHtml(photo.alt)}">
      ${image({ ...photo, eager })}
    </button>`;
}

function renderGallery(outlet, house) {
  const section = outlet.querySelector('[data-gallery-section]');
  const extraPhotos = house.images.slice(1);
  if (extraPhotos.length === 0) {
    section.hidden = true;
    return;
  }
  // Gallery index starts at 1 because index 0 is the main photo.
  section.querySelector('[data-gallery]').innerHTML = extraPhotos
    .map((photo, i) => `<li class="gallery__item">${photoButton(photo, i + 1)}</li>`)
    .join('');
  section.querySelector('[data-photo-count]').textContent = `${house.images.length} photos`;
}
