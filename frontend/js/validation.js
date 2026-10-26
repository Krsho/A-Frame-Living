/*
 * Reusable client-side form validation.
 *
 * A "schema" lists the rules for each field by its name attribute:
 *   const schema = { email: [required('Please enter your email.'), email()] };
 *   const form = attachValidation(formElement, schema, { summary, onValid });
 *
 * Each rule is a function that returns an error message, or null when the
 * value is fine. The first failing rule's message is shown under the field
 * (in the element with id "<field id>-error"), the field gets
 * aria-invalid="true", and a summary box lists all problems.
 *
 * This only helps the user; the PHP backend must validate again later.
 */

import { escapeHtml } from './components.js';

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

// ---------- Rules ----------

export const required = (message = 'This field is required.') => (value) =>
  value.trim() === '' ? message : null;

export const email = (message = 'Please enter a valid email address, for example name@example.com.') => (value) =>
  value.trim() === '' || EMAIL_PATTERN.test(value.trim()) ? null : message;

export const minLength = (min, message = `Please use at least ${min} characters.`) => (value) =>
  value.trim() === '' || value.trim().length >= min ? null : message;

export const maxLength = (max, message = `Please use at most ${max} characters.`) => (value) =>
  value.trim().length <= max ? null : message;

/** The value must match a regular expression, e.g. pattern(/\d/, 'Include a number.') */
export const pattern = (regex, message) => (value) =>
  value === '' || regex.test(value) ? null : message;

/** The value must equal another field's value, e.g. matches('password', 'Passwords do not match.') */
export const matches = (otherName, message) => (value, form) =>
  value === '' || value === fieldOf(form, otherName).value ? null : message;

// ---------- Helpers ----------

function fieldOf(form, name) {
  return form.elements.namedItem(name);
}

// Checkboxes count as filled in only when ticked.
function valueOf(field) {
  if (field.type === 'checkbox') return field.checked ? 'on' : '';
  return field.value;
}

/** Checks one field, shows or clears its message, and returns the message (or null). */
export function validateField(form, name, rules) {
  const field = fieldOf(form, name);
  const message = rules.map((rule) => rule(valueOf(field), form)).find(Boolean) ?? null;
  const errorElement = document.getElementById(`${field.id}-error`);

  if (message) {
    field.setAttribute('aria-invalid', 'true');
  } else {
    field.removeAttribute('aria-invalid');
  }
  if (errorElement) errorElement.textContent = message ?? '';
  return message;
}

function renderSummary(summary, form, errors) {
  if (!summary) return;
  if (errors.length === 0) {
    summary.hidden = true;
    summary.innerHTML = '';
    return;
  }
  const items = errors
    .map(({ name, message }) => {
      const id = fieldOf(form, name).id;
      return `<li><a href="#${id}" data-focus-field="${id}">${escapeHtml(message)}</a></li>`;
    })
    .join('');
  const count = errors.length === 1 ? 'There is 1 problem' : `There are ${errors.length} problems`;
  summary.innerHTML = `<p class="notice__title">${count} with your message:</p><ul>${items}</ul>`;
  summary.hidden = false;
}

/** The trimmed values of all fields in the schema, e.g. { name: 'Ana', email: '...' }. Checkboxes give true/false. */
export function readValues(form, schema) {
  return Object.fromEntries(
    Object.keys(schema).map((name) => {
      const field = fieldOf(form, name);
      return [name, field.type === 'checkbox' ? field.checked : field.value.trim()];
    }),
  );
}

/**
 * Connects a form to a schema.
 * - On submit: validates everything; if there are errors it shows them and
 *   focuses the first invalid field, otherwise it calls onValid(values).
 * - On leaving a field: checks it (only if something was typed, or after the
 *   first submit attempt), so mistakes show early without nagging.
 * - After the first submit attempt, errors disappear as soon as they are fixed.
 * Returns { reset() } to clear the form and all messages.
 */
export function attachValidation(form, schema, { summary = null, onValid }) {
  let attempted = false;
  let summaryKey = '';
  form.noValidate = true; // we show our own messages instead of the browser's

  const validateAll = () =>
    Object.entries(schema)
      .map(([name, rules]) => ({ name, message: validateField(form, name, rules) }))
      .filter((result) => result.message);

  // Only re-render the summary when the list of problems changes,
  // so screen readers do not re-announce it on every keystroke.
  const updateSummary = (errors, force = false) => {
    const key = errors.map((e) => `${e.name}:${e.message}`).join('|');
    if (force || key !== summaryKey) renderSummary(summary, form, errors);
    summaryKey = key;
  };

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    attempted = true;
    const errors = validateAll();
    updateSummary(errors, true);
    if (errors.length > 0) {
      fieldOf(form, errors[0].name).focus();
      return;
    }
    onValid(readValues(form, schema));
  });

  form.addEventListener('input', (event) => {
    if (attempted && schema[event.target.name]) updateSummary(validateAll());
  });

  form.addEventListener('focusout', (event) => {
    const { name } = event.target;
    if (!schema[name]) return;
    if (attempted) {
      updateSummary(validateAll());
    } else if (valueOf(event.target).trim() !== '') {
      validateField(form, name, schema[name]);
    }
  });

  // Links in the summary move focus to the field (without changing the URL/route).
  summary?.addEventListener('click', (event) => {
    const link = event.target.closest('[data-focus-field]');
    if (!link) return;
    event.preventDefault();
    document.getElementById(link.dataset.focusField).focus();
  });

  return {
    reset() {
      attempted = false;
      summaryKey = '';
      form.reset();
      Object.keys(schema).forEach((name) => validateField(form, name, []));
      renderSummary(summary, form, []);
    },
  };
}
