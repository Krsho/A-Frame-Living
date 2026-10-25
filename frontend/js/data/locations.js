/*
 * Completed project locations (provided by the project owner).
 *
 * `model` is the slug of the model built there, but only where the
 * owner confirmed it. Most of the other locations are Norway models,
 * but which ones exactly is not confirmed yet, so they are `null` and
 * the views show "TODO: confirm model".
 */

const country = 'Bosnia and Herzegovina';

export const locations = [
  { city: 'Velika Kladuša', country, model: null },
  { city: 'Bihać', country, model: null },
  { city: 'Banja Luka', country, model: null },
  { city: 'Šipovo', country, model: null },
  { city: 'Kupres', country, model: null },
  { city: 'Blidinje', country, model: null },
  { city: 'Rostovo', country, model: 'scandinavia' },
  { city: 'Sarajevo', country, model: 'continental' },
  { city: 'Jahorina', country, model: null },
  { city: 'Konjic', country, model: null },
  { city: 'Ivanica', country, model: null },
  { city: 'Brčko', country, model: null },
  { city: 'Visoko', country, model: null },
  { city: 'Bjelašnica', country, model: 'atlantic' },
];

export function locationsForModel(slug) {
  return locations.filter((location) => location.model === slug);
}
