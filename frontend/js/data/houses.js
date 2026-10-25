/*
 * The four A-frame house models.
 *
 * This file is the single source of truth for model data in the
 * frontend. In a later milestone this list will come from the REST API
 * (GET /houses) instead of being written here.
 *
 * Facts below (area, rooms, features, prices, locations) were provided
 * by the project owner. Anything not provided is `null` and the views
 * show a TODO label for it. Do not fill in guessed values.
 */

// Rooms every model has (provided by the project owner).
const sharedRooms = ['Living room', 'Kitchen', 'Gallery as a second floor'];

export const houses = [
  {
    slug: 'atlantic',
    name: 'Atlantic',
    area: 46,
    bedrooms: 1,
    bathrooms: 1,
    rooms: sharedRooms,
    features: [],
    prices: { rohBau: 16000, turnkey: 37000 },
    summary: 'Our most compact model: 46 m² with one bedroom and a gallery level under the roof.',
    dimensions: null, // TODO: footprint (width × length) and height
    images: [
      'assets/images/atlantic/main.jpg',
      'assets/images/atlantic/gallery-1.jpg',
      'assets/images/atlantic/gallery-2.jpg',
    ],
  },
  {
    slug: 'norway',
    name: 'Norway',
    area: 56,
    bedrooms: 2,
    bathrooms: 1,
    rooms: sharedRooms,
    features: [],
    prices: { rohBau: 22000, turnkey: 48000 },
    summary: 'Two bedrooms on 56 m² – the model we have built most often.',
    dimensions: null, // TODO: footprint (width × length) and height
    images: [
      'assets/images/norway/main.jpg',
      'assets/images/norway/gallery-1.jpg',
      'assets/images/norway/gallery-2.jpg',
    ],
  },
  {
    slug: 'scandinavia',
    name: 'Scandinavia',
    area: 105,
    bedrooms: 3,
    bathrooms: 2,
    rooms: sharedRooms,
    features: ['Laminated timber'],
    prices: { rohBau: 40000, turnkey: 80000 },
    summary: '105 m² with three bedrooms, two bathrooms and laminated timber.',
    dimensions: null, // TODO: footprint (width × length) and height
    images: [
      'assets/images/scandinavia/main.jpg',
      'assets/images/scandinavia/gallery-1.jpg',
      'assets/images/scandinavia/gallery-2.jpg',
    ],
  },
  {
    slug: 'continental',
    name: 'Continental',
    area: 124,
    bedrooms: 3,
    bathrooms: 2,
    rooms: sharedRooms,
    features: ['Laminated timber', 'Aluminium windows and doors', 'Fireplace'],
    prices: { rohBau: 45000, turnkey: 90000 },
    summary: 'Our largest model: 124 m² with laminated timber, aluminium windows and doors, and a fireplace.',
    dimensions: null, // TODO: footprint (width × length) and height
    images: [
      'assets/images/continental/main.jpg',
      'assets/images/continental/gallery-1.jpg',
      'assets/images/continental/gallery-2.jpg',
    ],
  },
];

export function findHouse(slug) {
  return houses.find((house) => house.slug === slug) ?? null;
}
