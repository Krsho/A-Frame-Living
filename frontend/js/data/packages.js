/*
 * The two packages every model is offered in.
 * Descriptions are from the project owner. `key` matches the keys in each
 * model's `prices` object in js/data/houses.js.
 */

export const packages = [
  {
    key: 'rohBau',
    name: 'ROH BAU',
    description:
      'The house is completely finished on the outside. The interior is left for you to design and finish yourself.',
  },
  {
    key: 'turnkey',
    name: 'Turnkey',
    description:
      'Everything is done except the furniture – including a finished kitchen and bathroom. You just move in with your own furniture.',
  },
];

/*
 * What each package covers, derived from the descriptions above:
 * ROH BAU = finished outside, interior left to the customer;
 * Turnkey = everything except furniture, incl. kitchen and bathroom.
 */
export const packageScope = [
  { item: 'Structure, roof, windows and outside doors', rohBau: true, turnkey: true },
  { item: 'Exterior finish (cladding and roofing)', rohBau: true, turnkey: true },
  { item: 'Interior walls, floors and installations', rohBau: false, turnkey: true },
  { item: 'Kitchen', rohBau: false, turnkey: true },
  { item: 'Bathroom', rohBau: false, turnkey: true },
  { item: 'Furniture', rohBau: false, turnkey: false },
];
