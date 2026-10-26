/*
 * FAQ view: renders the questions from js/data/faq.js as <details> items.
 * <details>/<summary> open and close with mouse, Enter and Space without extra code.
 */

import { faqCategories } from '../data/faq.js';
import { escapeHtml } from '../components.js';

export function initFaq({ outlet }) {
  outlet.querySelector('[data-faq-categories]').innerHTML = faqCategories
    .map((cat) => `<li><a href="#/faq?section=faq-${cat.id}">${escapeHtml(cat.title)}</a></li>`)
    .join('');

  outlet.querySelector('[data-faq-list]').innerHTML = faqCategories
    .map(
      (cat) => `
        <section class="faq-group anchor-target" id="faq-${cat.id}" aria-labelledby="faq-${cat.id}-title">
          <h2 class="faq-group__title" id="faq-${cat.id}-title">${escapeHtml(cat.title)}</h2>
          ${cat.items
            .map(
              (item) => `
                <details class="faq-item">
                  <summary class="faq-item__question">${escapeHtml(item.question)}</summary>
                  <div class="faq-item__answer">${item.answer}</div>
                </details>`,
            )
            .join('')}
        </section>`,
    )
    .join('');

  const setAll = (open) => outlet.querySelectorAll('.faq-item').forEach((item) => (item.open = open));
  outlet.querySelector('[data-faq-expand]').addEventListener('click', () => setAll(true));
  outlet.querySelector('[data-faq-collapse]').addEventListener('click', () => setAll(false));
}
