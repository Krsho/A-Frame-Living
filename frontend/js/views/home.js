/*
 * Home view: fills in the featured models and the key numbers.
 */

import { houses } from '../data/houses.js';
import { locations } from '../data/locations.js';
import { houseCard } from '../components.js';

export function initHome({ outlet }) {
  outlet.querySelector('[data-featured-houses]').innerHTML = houses.map(houseCard).join('');

  const areas = houses.map((house) => house.area);
  setText(outlet, '[data-stat="models"]', houses.length);
  setText(outlet, '[data-stat="locations"]', locations.length);
  setText(outlet, '[data-stat="area"]', `${Math.min(...areas)}–${Math.max(...areas)} m²`);
}

function setText(root, selector, value) {
  const element = root.querySelector(selector);
  if (element) element.textContent = value;
}
