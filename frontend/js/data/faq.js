/*
 * Frequently asked questions, grouped by category.
 *
 * Answers that mention models, prices, sizes, packages or locations are
 * built from the other data files, so they always match the rest of the
 * site. The general answers were written for the university assignment.
 * Answers are trusted HTML (they may contain links); data values are escaped.
 */

import { houses } from './houses.js';
import { packages } from './packages.js';
import { locations, countByModel } from './locations.js';
import { escapeHtml, formatPrice } from '../components.js';

const areas = houses.map((h) => h.area);
const cheapest = Math.min(...houses.map((h) => h.prices.rohBau));
const modelNames = houses.map((h) => escapeHtml(h.name));
const listOf = (items) => `${items.slice(0, -1).join(', ')} and ${items[items.length - 1]}`;
const counts = countByModel();

const priceList = `
  <ul>
    ${houses
      .map((h) => `<li><strong>${escapeHtml(h.name)}</strong> (${h.area} m²): ${packages
        .map((p) => `${escapeHtml(p.name)} ${formatPrice(h.prices[p.key])}`)
        .join(' · ')}</li>`)
      .join('')}
  </ul>`;

const packageList = `
  <ul>
    ${packages.map((p) => `<li><strong>${escapeHtml(p.name)}:</strong> ${escapeHtml(p.description)}</li>`).join('')}
  </ul>`;

export const faqCategories = [
  {
    id: 'models',
    title: 'Models and prices',
    items: [
      {
        question: 'Which A-frame models do you offer?',
        answer: `
          <p>We offer four models: ${listOf(modelNames)}. They range from ${Math.min(...areas)} m² to
          ${Math.max(...areas)} m² and every model has a living room, a kitchen and a gallery as a second floor.</p>
          <p><a href="#/houses?section=compare">Compare the models side by side</a>.</p>`,
      },
      {
        question: 'How much does an A-frame house cost?',
        answer: `
          <p>Prices start at ${formatPrice(cheapest)}. Each model has a fixed price per package:</p>
          ${priceList}`,
      },
      {
        question: 'Is the land included in the price?',
        answer: `<p>No. The prices cover the house only. If you are still looking for a plot, we are happy to
          give you our opinion on whether it suits an A-frame.</p>`,
      },
      {
        question: 'Can the final price differ from the listed price?',
        answer: `<p>The listed prices are for the standard model. The final price can change if you choose
          extra options or layout changes, or if your plot needs extra work such as a difficult access road.
          You always receive a written offer before you sign anything.</p>`,
      },
    ],
  },
  {
    id: 'packages',
    title: 'ROH BAU or Turnkey',
    items: [
      {
        question: 'What is the difference between ROH BAU and Turnkey?',
        answer: `${packageList}<p><a href="#/how-to-buy?section=packages">See exactly who does what</a>.</p>`,
      },
      {
        question: 'Is furniture included in the Turnkey package?',
        answer: `<p>No. Turnkey includes everything except the furniture – the kitchen and bathroom are finished,
          so you only need to bring your own furniture.</p>`,
      },
      {
        question: 'Can I start with ROH BAU and have you finish the interior later?',
        answer: `<p>Yes. Many owners start with ROH BAU and decide later. Contact us when you are ready and we
          will make you an offer for the interior work.</p>`,
      },
    ],
  },
  {
    id: 'customization',
    title: 'Customization',
    items: [
      {
        question: 'Can I change the layout of a model?',
        answer: `<p>Smaller changes – such as window positions or the layout of interior walls – can usually be
          discussed during planning. Larger changes to the structure may affect the price and the permit,
          so we look at them together before the offer.</p>`,
      },
      {
        question: 'Can I choose the colours and materials?',
        answer: `<p>Yes. You can choose colours for the roof and cladding, and for Turnkey projects also the
          interior finishes. We will show you the options during the consultation.</p>`,
      },
    ],
  },
  {
    id: 'locations',
    title: 'Locations',
    items: [
      {
        question: 'Where have you built so far?',
        answer: `
          <p>We have completed ${locations.length} projects across Bosnia and Herzegovina – from
          ${escapeHtml(locations[0].city)} to ${escapeHtml(locations[locations.length - 1].city)}.
          ${counts.norway ?? 0} of them are Norway models.</p>
          <p><a href="#/locations">See all locations</a>.</p>`,
      },
      {
        question: 'Do you build outside Bosnia and Herzegovina?',
        answer: `<p>All our completed projects are in Bosnia and Herzegovina. If your plot is elsewhere,
          <a href="#/contact">send us an inquiry</a> and we will tell you whether we can help.</p>`,
      },
      {
        question: 'Can an A-frame be built in the mountains?',
        answer: `<p>Yes – several of our projects stand on mountain plots, for example on Kupres, Blidinje and
          Bjelašnica. The steep roof is well suited to snow. Access for delivery and construction is checked
          during the site visit.</p>`,
      },
    ],
  },
  {
    id: 'process',
    title: 'Buying process',
    items: [
      {
        question: 'How do I start?',
        answer: `<p><a href="#/contact">Send us an inquiry</a> with the model you like and where your plot is.
          We will get back to you to arrange a first conversation.
          <a href="#/how-to-buy">See all seven steps</a>.</p>`,
      },
      {
        question: 'Do I need a building permit?',
        answer: `<p>In most cases, yes. The requirements depend on your municipality. We prepare the drawings
          and help you put together the documentation for the application.</p>`,
      },
      {
        question: 'How long does it take to build?',
        answer: `<p>It depends on the model, the package, the permit and the season. You receive a schedule
          together with your offer, and we keep you updated during construction.</p>`,
      },
      {
        question: 'How does payment work?',
        answer: `<p>Payment terms are agreed in the contract and are usually split into stages that follow
          the progress of construction.</p>`,
      },
    ],
  },
];
