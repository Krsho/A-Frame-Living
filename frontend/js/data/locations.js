/*
 * Completed project locations (provided by the project owner).
 *
 * `model` is the slug of the model built there (see js/data/houses.js).
 */

const country = 'Bosnia and Herzegovina';

export const locations = [
  { city: 'Velika Kladuša', country, model: 'norway' },
  { city: 'Bihać', country, model: 'norway' },
  { city: 'Banja Luka', country, model: 'norway' },
  { city: 'Šipovo', country, model: 'norway' },
  { city: 'Kupres', country, model: 'norway' },
  { city: 'Blidinje', country, model: 'norway' },
  { city: 'Rostovo', country, model: 'scandinavia' },
  { city: 'Sarajevo', country, model: 'continental' },
  { city: 'Jahorina', country, model: 'norway' },
  { city: 'Konjic', country, model: 'norway' },
  { city: 'Ivanica', country, model: 'norway' },
  { city: 'Brčko', country, model: 'norway' },
  { city: 'Visoko', country, model: 'norway' },
  { city: 'Bjelašnica', country, model: 'atlantic' },
];

export function locationsForModel(slug) {
  return locations.filter((location) => location.model === slug);
}
