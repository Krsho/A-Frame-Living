/*
 * Admin dashboard (static preview). Shows one management table per
 * database entity. All buttons are disabled until the backend exists;
 * then each table will load from and save to the REST API.
 */

import { houses, findHouse } from '../data/houses.js';
import { packages } from '../data/packages.js';
import { locations } from '../data/locations.js';
import { demoUsers, demoInquiries } from '../data/demo.js';
import { escapeHtml, formatPrice, statusBadge } from '../components.js';

export function initAdmin({ outlet }) {
  const openInquiries = demoInquiries.filter((i) => i.status === 'new' || i.status === 'in_progress');
  outlet.querySelector('[data-stat="models"]').textContent = houses.length;
  outlet.querySelector('[data-stat="projects"]').textContent = locations.length;
  outlet.querySelector('[data-stat="open-inquiries"]').textContent = openInquiries.length;
  outlet.querySelector('[data-stat="users"]').textContent = demoUsers.length;

  outlet.querySelector('[data-admin-sections]').innerHTML = [
    adminSection({
      id: 'admin-models',
      title: 'House models',
      addLabel: 'Add model',
      columns: ['Model', 'Area', 'Bed / bath', ...packages.map((p) => p.name), 'Photos'],
      rows: houses.map((h) => [
        `<a href="#/houses/${h.slug}">${escapeHtml(h.name)}</a>`,
        `${h.area} m²`,
        `${h.bedrooms} / ${h.bathrooms}`,
        ...packages.map((p) => formatPrice(h.prices[p.key])),
        h.images.length,
      ]),
    }),
    adminSection({
      id: 'admin-packages',
      title: 'Packages',
      addLabel: 'Add package',
      columns: ['Package', 'Description'],
      rows: packages.map((p) => [escapeHtml(p.name), escapeHtml(p.description)]),
    }),
    adminSection({
      id: 'admin-locations',
      title: 'Locations & projects',
      addLabel: 'Add project',
      intro: 'Map coordinates will be looked up automatically (OpenStreetMap Nominatim) when a location is saved.',
      columns: ['City', 'Region', 'Model', 'Setting', 'Coordinates'],
      rows: locations.map((l) => [
        escapeHtml(l.city),
        escapeHtml(l.region),
        escapeHtml(findHouse(l.model).name),
        escapeHtml(l.setting),
        '<span class="text-muted">Not geocoded yet</span>',
      ]),
    }),
    adminSection({
      id: 'admin-inquiries',
      title: 'Inquiries',
      columns: ['Date', 'From', 'Subject', 'Model', 'Status'],
      rows: [...demoInquiries]
        .sort((a, b) => b.createdAt.localeCompare(a.createdAt))
        .map((i) => [
          escapeHtml(i.createdAt),
          `${escapeHtml(i.name)}<br><span class="text-muted">${escapeHtml(i.email)}${i.userId ? '' : ' · guest'}</span>`,
          escapeHtml(i.subject),
          i.model ? escapeHtml(findHouse(i.model).name) : '—',
          statusBadge(i.status),
        ]),
      actions: ['Change status', 'Delete'],
    }),
    adminSection({
      id: 'admin-users',
      title: 'Users',
      columns: ['Name', 'Email', 'Role', 'Registered'],
      rows: demoUsers.map((u) => [
        `${escapeHtml(u.firstName)} ${escapeHtml(u.lastName)}`,
        escapeHtml(u.email),
        `<span class="role role--${u.role}">${u.role === 'admin' ? 'Admin' : 'Customer'}</span>`,
        escapeHtml(u.createdAt),
      ]),
      actions: ['Edit', 'Delete'],
    }),
  ].join('');
}

/** One dashboard section: heading, optional "Add" button and a table with row actions. */
function adminSection({ id, title, addLabel = null, intro = '', columns, rows, actions = ['Edit', 'Delete'] }) {
  const disabledButton = (label, extraClass = '') =>
    `<button class="btn btn--ghost btn--sm ${extraClass}" type="button" disabled aria-describedby="admin-note">${label}</button>`;

  return `
    <section class="admin-section anchor-target" id="${id}" aria-labelledby="${id}-title">
      <div class="admin-section__header">
        <h2 class="admin-section__title" id="${id}-title">${escapeHtml(title)} <span class="admin-section__count">${rows.length}</span></h2>
        ${addLabel ? disabledButton(`+ ${addLabel}`) : ''}
      </div>
      ${intro ? `<p class="admin-section__intro">${escapeHtml(intro)}</p>` : ''}
      <div class="table-scroll" tabindex="0" role="region" aria-label="${escapeHtml(title)}">
        <table class="data-table">
          <caption class="visually-hidden">${escapeHtml(title)}</caption>
          <thead>
            <tr>${columns.map((c) => `<th scope="col">${escapeHtml(c)}</th>`).join('')}<th scope="col"><span class="visually-hidden">Actions</span></th></tr>
          </thead>
          <tbody>
            ${rows
              .map(
                (cells) => `
                  <tr>
                    ${cells.map((cell) => `<td>${cell}</td>`).join('')}
                    <td class="data-table__actions">${actions.map((a) => disabledButton(a)).join('')}</td>
                  </tr>`,
              )
              .join('')}
          </tbody>
        </table>
      </div>
    </section>`;
}
