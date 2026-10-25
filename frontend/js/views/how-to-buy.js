/*
 * How to buy view: fills in the package cards and the "who does what" table.
 * The steps themselves are static HTML in views/how-to-buy.html.
 */

import { packages, packageScope } from '../data/packages.js';
import { packageCards, escapeHtml } from '../components.js';

export function initHowToBuy({ outlet }) {
  outlet.querySelector('[data-packages]').innerHTML = packageCards();
  outlet.querySelector('[data-scope-table]').innerHTML = scopeTable();
}

function scopeTable() {
  const mark = (done) =>
    done
      ? '<span class="tick" aria-label="Done by us">✓</span>'
      : '<span class="dash" aria-label="Left to you">—</span>';

  const head = packages.map((pkg) => `<th scope="col">${escapeHtml(pkg.name)}</th>`).join('');
  const body = packageScope
    .map(
      (row) => `
        <tr>
          <th scope="row">${escapeHtml(row.item)}</th>
          ${packages.map((pkg) => `<td>${mark(row[pkg.key])}</td>`).join('')}
        </tr>`,
    )
    .join('');

  return `
    <caption class="visually-hidden">What the ROH BAU and Turnkey packages include</caption>
    <thead><tr><td></td>${head}</tr></thead>
    <tbody>${body}</tbody>`;
}
