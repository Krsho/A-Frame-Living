/*
 * Completed project locations.
 *
 * City and model were provided by the project owner. `model` is the slug
 * of the model built there (see js/data/houses.js). `region` describes
 * where the place is; `setting` (the kind of plot) was written for the
 * university assignment.
 */

const country = 'Bosnia and Herzegovina';

export const locations = [
  { city: 'Velika Kladuša', region: 'Una-Sana', setting: 'Countryside', country, model: 'norway' },
  { city: 'Bihać', region: 'Una-Sana', setting: 'River valley', country, model: 'norway' },
  { city: 'Banja Luka', region: 'Bosanska Krajina', setting: 'Town outskirts', country, model: 'norway' },
  { city: 'Šipovo', region: 'Pliva valley', setting: 'River valley', country, model: 'norway' },
  { city: 'Kupres', region: 'Kupres plateau', setting: 'Mountain', country, model: 'norway' },
  { city: 'Blidinje', region: 'Blidinje Nature Park', setting: 'Mountain', country, model: 'norway' },
  { city: 'Rostovo', region: 'Central Bosnia', setting: 'Mountain', country, model: 'scandinavia' },
  { city: 'Sarajevo', region: 'Sarajevo area', setting: 'Town outskirts', country, model: 'continental' },
  { city: 'Jahorina', region: 'Sarajevo area', setting: 'Mountain', country, model: 'norway' },
  { city: 'Konjic', region: 'Neretva valley', setting: 'River valley', country, model: 'norway' },
  { city: 'Ivanica', region: 'Eastern Herzegovina', setting: 'Countryside', country, model: 'norway' },
  { city: 'Brčko', region: 'Brčko District', setting: 'Countryside', country, model: 'norway' },
  { city: 'Visoko', region: 'Central Bosnia', setting: 'Countryside', country, model: 'norway' },
  { city: 'Bjelašnica', region: 'Sarajevo area', setting: 'Mountain', country, model: 'atlantic' },
];

export function locationsForModel(slug) {
  return locations.filter((location) => location.model === slug);
}

/** e.g. { norway: 11, scandinavia: 1, continental: 1, atlantic: 1 } */
export function countByModel() {
  const counts = {};
  for (const { model } of locations) counts[model] = (counts[model] ?? 0) + 1;
  return counts;
}

/** Link to the place on OpenStreetMap (no API key needed). */
export function mapUrl(location) {
  return `https://www.openstreetmap.org/search?query=${encodeURIComponent(`${location.city}, ${location.country}`)}`;
}
