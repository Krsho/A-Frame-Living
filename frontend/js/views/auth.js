/*
 * Log in and Register views (static, Milestone 1).
 * The forms are validated in the browser, but nothing is sent: real
 * accounts and JWT login come with the PHP backend in a later phase.
 */

import { attachValidation, required, email, minLength, maxLength, pattern, matches } from '../validation.js';

export function initLogin({ outlet }) {
  const result = outlet.querySelector('[data-auth-result]');

  attachValidation(
    outlet.querySelector('[data-login-form]'),
    {
      email: [required('Please enter your email address.'), email()],
      password: [required('Please enter your password.')],
    },
    {
      summary: outlet.querySelector('[data-form-summary]'),
      onValid() {
        result.hidden = false;
        result.focus();
      },
    },
  );
}

export function initRegister({ outlet }) {
  const result = outlet.querySelector('[data-auth-result]');

  attachValidation(
    outlet.querySelector('[data-register-form]'),
    {
      firstName: [required('Please enter your first name.'), maxLength(60)],
      lastName: [required('Please enter your last name.'), maxLength(60)],
      email: [required('Please enter your email address.'), email(), maxLength(150)],
      phone: [pattern(/^\+?[\d\s()-]{6,20}$/, 'Please enter a valid phone number, for example +387 61 123 456.')],
      password: [
        required('Please choose a password.'),
        minLength(8, 'Your password needs at least 8 characters.'),
        pattern(/\d/, 'Your password needs at least one number.'),
      ],
      passwordConfirm: [required('Please repeat your password.'), matches('password', 'The passwords do not match.')],
      terms: [required('Please accept this to create an account.')],
    },
    {
      summary: outlet.querySelector('[data-form-summary]'),
      onValid(values) {
        result.querySelector('[data-result-name]').textContent = values.firstName;
        result.hidden = false;
        result.focus();
      },
    },
  );
}
