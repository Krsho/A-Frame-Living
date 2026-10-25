/*
 * The four A-frame house models.
 *
 * This file is the single source of truth for model data in the
 * frontend. In a later milestone this list will come from the REST API
 * (GET /houses) instead of being written here.
 *
 * Facts below (area, rooms, features, prices, locations) were provided
 * by the project owner. Values written for the university assignment
 * are marked with a comment. Anything still unknown is `null` and the
 * views show a TODO label for it.
 *
 * dimensions: footprint and sideRoom are [width, length] in metres
 * (sideRoom is null when the model has none); height is in metres.
 */

// Rooms every model has (provided by the project owner).
const sharedRooms = ['Living room', 'Kitchen', 'Gallery as a second floor'];

// Builds the image list for a model from its folder in assets/images/.
// The first image is the main photo; the others form the detail-page gallery.
function photos(folder, list) {
  return list.map(([file, alt]) => ({ src: `assets/images/${folder}/${file}`, alt }));
}

// Prices are per package; the package definitions live in js/data/packages.js.
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
    // Footprint from the owner; height written for the assignment.
    dimensions: { footprint: [5, 7], sideRoom: null, height: 5.8 },
    images: photos('atlantic', [
      ['atlantic.jfif', 'Atlantic A-frame house with a dark metal roof, a glazed timber front and a wooden deck, set among pine trees'],
      ['atlantic1.JPEG', 'Atlantic exterior: glazed front and dark metal roof at sunset'],
      ['atlantic2.JPEG', 'Atlantic exterior in falling snow'],
      ['atlantic22.JPG', 'Atlantic with its wooden deck in a snowy mountain landscape'],
      ['atlantic3.png', 'Atlantic glazed front lit up at night'],
      ['atlantic4.jfif', 'Atlantic interior: living area with the staircase to the gallery'],
      ['atlantic5.png', 'Atlantic interior: view from the gallery towards the tall front window'],
    ]),
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
    // Footprint from the owner; side room size and height written for the assignment.
    dimensions: { footprint: [5, 7], sideRoom: [2.5, 4], height: 5.8 },
    images: photos('norway', [
      ['norway.jfif', 'Norway A-frame house with a dark roof, a tall glazed timber gable and a low side extension, in a garden at dusk'],
      ['norway1.JPG', 'Norway exterior at dusk with lit roof edges and a timber side extension'],
      ['norway2.JPG', 'Norway exterior with its timber-clad side extension on a sunny day'],
      ['norway3.JPG', 'Norway living room with a sofa and round coffee table in front of the glazed gable'],
      ['norway4.JPG', 'Norway living area with the staircase up to the gallery'],
      ['norway5.JPG', 'Norway living room next to the staircase, facing the glazed gable'],
      ['norway6.JPG', 'Norway hallway leading to the kitchen'],
      ['norway7.JPG', 'Norway living room with an armchair, a sofa and a round coffee table'],
      ['norway8.JPG', 'Norway bedroom with a large window'],
      ['norway9.JPG', 'Norway bedroom under the sloping timber roof'],
    ]),
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
    // Footprint and side-room width (3.6 m) from the owner; side-room length and height written for the assignment.
    dimensions: { footprint: [9, 7], sideRoom: [3.6, 4.8], height: 7.9 },
    images: photos('scandinavia', [
      ['scandinavia.jfif', 'Scandinavia A-frame house with a dark roof, a large glazed timber gable and a timber-clad side wing, on a lawn'],
      ['scandinavia1.jpeg', 'Scandinavia exterior: glazed gable and side wing in daylight'],
      ['scandinavia2.jpeg', 'Scandinavia seen from the back, with dark red cladding'],
      ['scandinavia3.jpg', 'Scandinavia kitchen and dining area with a breakfast bar and the gallery above'],
      ['scandinavia4.jpg', 'Scandinavia living room with a corner sofa and the staircase to the gallery'],
      ['scandinavia5.jpg', 'Scandinavia dining table in front of the kitchen'],
      ['scandinavia6.jpg', 'Scandinavia galley kitchen'],
      ['scandinavia7.jpg', 'Scandinavia hallway with built-in storage, looking towards the living room'],
      ['scandinavia8.jpg', 'Scandinavia bedroom with a tall mirror and wall art'],
      ['scandinavia9.jpg', 'Scandinavia bathroom with a vessel sink'],
      ['scandinavia10.jpg', 'Scandinavia upper floor with doors to two rooms under the roof'],
      ['scandinavia11.jpg', 'Scandinavia bedroom with a built-in wardrobe under the roof'],
    ]),
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
    // Footprint from the owner; height written for the assignment.
    dimensions: { footprint: [14, 8], sideRoom: null, height: 7.5 },
    images: photos('continental', [
      ['continental.jfif', 'Continental house with a steep dark roof, a glazed front, a stone chimney wall and a covered timber porch, in a forest'],
      ['continental1.JPG', 'Continental exterior: glazed gable and stone chimney wall on a sunny day'],
      ['continental2.JPG', 'Continental side view with dark cladding'],
      ['continental3.JPG', 'Continental kitchen island with the living area behind'],
      ['continental4.JPG', 'Continental open-plan living, dining and kitchen area with the fireplace'],
      ['continental5.JPG', 'Continental living room with the stone fireplace and tall windows'],
      ['continental6.JPG', 'Continental kitchen island facing the dining area'],
      ['continental7.JPG', 'Continental fireplace and dining area seen from the hallway'],
      ['continental8.JPG', 'Continental dining area and kitchen seen from the gallery'],
      ['continental9.JPG', 'Continental living and dining area seen from the gallery'],
      ['continental10.JPG', 'Continental bedroom on the gallery level with a roof window'],
      ['continental11.JPG', 'Continental gallery bedroom with a glass balustrade'],
      ['continental12.JPG', 'Continental kitchen seen from the gallery level'],
      ['continental13.JPG', 'Continental gallery bedroom facing the stone chimney and the glazed gable'],
    ]),
  },
];

// Filter options are derived from the data above, so they can never list
// a feature or bedroom count that no model actually has.
export const featureOptions = [...new Set(houses.flatMap((house) => house.features))];
export const bedroomOptions = [...new Set(houses.map((house) => house.bedrooms))].sort((a, b) => a - b);

export function findHouse(slug) {
  return houses.find((house) => house.slug === slug) ?? null;
}
