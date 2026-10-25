/*
 * User profile view (static preview). Shows the example customer from
 * js/data/demo.js; in the backend phase this data comes from GET /users/me
 * and GET /inquiries/mine.
 */

import { demoUsers, demoInquiries, demoCurrentUserId } from '../data/demo.js';
import { findHouse } from '../data/houses.js';
import { escapeHtml, statusBadge } from '../components.js';

export function initProfile({ outlet }) {
  const user = demoUsers.find((u) => u.id === demoCurrentUserId);
  const form = outlet.querySelector('[data-profile-form]');

  outlet.querySelector('[data-profile-first-name]').textContent = user.firstName;
  form.elements.firstName.value = user.firstName;
  form.elements.lastName.value = user.lastName;
  form.elements.email.value = user.email;
  form.elements.phone.value = user.phone ?? '';
  form.addEventListener('submit', (event) => event.preventDefault());

  const inquiries = demoInquiries.filter((inquiry) => inquiry.userId === user.id);
  outlet.querySelector('[data-profile-inquiries]').innerHTML = `
    <caption class="visually-hidden">Inquiries you have sent</caption>
    <thead>
      <tr><th scope="col">Date</th><th scope="col">Subject</th><th scope="col">Model</th><th scope="col">Status</th></tr>
    </thead>
    <tbody>
      ${inquiries
        .map((inquiry) => {
          const house = findHouse(inquiry.model);
          return `
            <tr>
              <td>${escapeHtml(inquiry.createdAt)}</td>
              <td>${escapeHtml(inquiry.subject)}</td>
              <td>${house ? `<a href="#/houses/${house.slug}">${escapeHtml(house.name)}</a>` : '—'}</td>
              <td>${statusBadge(inquiry.status)}</td>
            </tr>`;
        })
        .join('')}
    </tbody>`;
}
