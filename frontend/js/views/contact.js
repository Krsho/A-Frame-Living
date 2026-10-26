/*
 * Contact view: company details and the inquiry form.
 * Validation rules come from js/validation.js; sending goes through
 * js/services/inquiries.js (simulated until the backend exists).
 */

import { company } from '../data/company.js';
import { findHouse } from '../data/houses.js';
import { escapeHtml } from '../components.js';
import { attachValidation, required, email, minLength, maxLength } from '../validation.js';
import { submitInquiry } from '../services/inquiries.js';

const schema = {
  name: [required('Please enter your name.'), minLength(2, 'Your name should be at least 2 characters.'), maxLength(100)],
  email: [required('Please enter your email address.'), email(), maxLength(150)],
  subject: [required('Please enter a subject.'), minLength(3, 'The subject should be at least 3 characters.'), maxLength(150)],
  message: [
    required('Please enter a message.'),
    minLength(10, 'Please write at least 10 characters so we know how to help.'),
    maxLength(2000),
  ],
};

export function initContact({ outlet, query }) {
  renderCompanyDetails(outlet.querySelector('[data-company-details]'));

  const form = outlet.querySelector('[data-contact-form]');
  const status = outlet.querySelector('[data-form-status]');
  const submitButton = outlet.querySelector('[data-submit]');
  const success = outlet.querySelector('[data-contact-success]');

  // "Inquire about Norway" on a detail page links to #/contact?model=norway
  const house = findHouse(query.get('model'));
  if (house) form.elements.subject.value = `Inquiry about the ${house.name} model`;

  const setSending = (sending) => {
    submitButton.disabled = sending;
    submitButton.textContent = sending ? 'Sending…' : 'Send message';
    form.setAttribute('aria-busy', String(sending));
  };

  const validation = attachValidation(form, schema, {
    summary: outlet.querySelector('[data-form-summary]'),
    async onValid(values) {
      status.hidden = true;
      setSending(true);
      try {
        await submitInquiry(values);
        showSuccess(values);
      } catch (error) {
        console.warn('Inquiry could not be submitted:', error);
        status.innerHTML = `<p class="notice__title">Your message could not be sent.</p>
          <p>Please try again in a moment. Your text has been kept.</p>`;
        status.hidden = false;
      } finally {
        setSending(false);
      }
    },
  });

  function showSuccess(values) {
    success.querySelector('[data-success-name]').textContent = values.name;
    success.querySelector('[data-success-summary]').innerHTML = [
      ['Email', values.email],
      ['Subject', values.subject],
      ['Message', values.message],
    ]
      .map(([label, value]) => `<div><dt>${label}</dt><dd>${escapeHtml(value)}</dd></div>`)
      .join('');
    form.hidden = true;
    success.hidden = false;
    success.querySelector('.contact-success__title').focus();
  }

  outlet.querySelector('[data-send-another]').addEventListener('click', () => {
    validation.reset();
    status.hidden = true;
    success.hidden = true;
    form.hidden = false;
    form.elements.name.focus();
  });
}

function renderCompanyDetails(list) {
  const phoneHref = `tel:${company.phone.replace(/[^\d+]/g, '')}`;
  const hours = company.hours
    .map(([days, time]) => `<span class="contact-list__line">${escapeHtml(days)}: ${escapeHtml(time)}</span>`)
    .join('');
  list.innerHTML = `
    <div><dt>Email</dt><dd><a href="mailto:${escapeHtml(company.email)}">${escapeHtml(company.email)}</a></dd></div>
    <div><dt>Phone</dt><dd><a href="${phoneHref}">${escapeHtml(company.phone)}</a></dd></div>
    <div><dt>Office</dt><dd>${company.address.map(escapeHtml).join('<br>')}</dd></div>
    <div><dt>Office hours</dt><dd>${hours}</dd></div>`;
}
