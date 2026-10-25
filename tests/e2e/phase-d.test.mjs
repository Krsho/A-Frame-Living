// Phase D: Log in, Register, User profile, Admin dashboard and the external-API map placeholder.
import { connect, makeChecker, BASE, SHOTS } from './cdp.mjs';
import { houses } from '../../frontend/js/data/houses.js';
import { locations } from '../../frontend/js/data/locations.js';
import { demoUsers, demoInquiries, demoCurrentUserId } from '../../frontend/js/data/demo.js';

const b = await connect();
const { check, summary } = makeChecker();
const ev = (expr) => b.evaluate(expr);
const NL = String.fromCharCode(10);
const text = (sel) => ev(`document.querySelector(${JSON.stringify(sel)})?.textContent.trim().split(${JSON.stringify(NL)}).map(s => s.trim()).filter(Boolean).join(' ') ?? null`);
const count = (sel) => ev(`document.querySelectorAll(${JSON.stringify(sel)}).length`);
const click = (sel) => ev(`document.querySelector(${JSON.stringify(sel)}).click()`);
const visible = (sel) => ev(`(() => { const e = document.querySelector(${JSON.stringify(sel)}); return !!e && !e.closest('[hidden]') && getComputedStyle(e).display !== 'none'; })()`);
const typeInto = async (id, value) => {
  await ev(`(() => { const f = document.getElementById('${id}'); f.focus(); f.value = ''; })()`);
  if (value) await b.send('Input.insertText', { text: value });
  await b.wait(50);
};
const errorOf = (id) => ev(`document.getElementById('${id}-error').textContent`);

await b.viewport(1366, 900);

// =================== Log in ===================
await b.goto(BASE + '#/login');
check('login: h1 + title', (await text('#app h1')) === 'Log in' && (await ev('document.title')) === 'Log in | A-Frame Living');
check('login: labelled email and password fields', (await ev(`['email','password'].every(n => document.querySelector('[data-login-form]').elements[n].labels.length === 1)`)) && (await ev(`document.getElementById('login-password').type`)) === 'password');
await click('[data-login-form] [type="submit"]'); await b.wait(150);
check('login: empty submit shows both errors', (await errorOf('login-email')) !== '' && (await errorOf('login-password')) !== '');
check('login: focus on first invalid field', (await ev('document.activeElement.id')) === 'login-email');
check('login: no demo notice yet', !(await visible('[data-auth-result]')));
await typeInto('login-email', 'ana@');
await typeInto('login-password', 'secret123');
await click('[data-login-form] [type="submit"]'); await b.wait(150);
check('login: invalid email rejected', (await errorOf('login-email')).includes('valid email') && !(await visible('[data-auth-result]')));
await typeInto('login-email', 'ana@example.com');
await click('[data-login-form] [type="submit"]'); await b.wait(150);
check('login: valid submit shows the demo notice (not logged in)', (await visible('[data-auth-result]')) && (await text('[data-auth-result] .notice__title')) === 'Demo only – you are not logged in.');
check('login: notice receives focus', (await ev(`document.activeElement.matches('[data-auth-result]')`)));
await click('[data-auth-result] a[href="#/admin"]'); await b.wait(500);
check('login: "Admin dashboard" preview link works', (await text('#app h1')) === 'Dashboard');
await b.goto(BASE + '#/login');
await click('.auth-card__switch a'); await b.wait(500);
check('login: link to Register works', (await text('#app h1')) === 'Register');

// =================== Register ===================
await b.goto(BASE + '#/register');
check('register: title', (await ev('document.title')) === 'Register | A-Frame Living');
await click('[data-register-form] [type="submit"]'); await b.wait(150);
const requiredIds = ['register-first-name', 'register-last-name', 'register-email', 'register-password', 'register-password-confirm', 'register-terms'];
const errs = await Promise.all(requiredIds.map(errorOf));
check('register: empty submit flags the 6 required fields', errs.every((e) => e !== ''), JSON.stringify(errs));
check('register: phone is optional (no error when empty)', (await errorOf('register-phone')) === '');
check('register: summary lists 6 problems', (await count('[data-form-summary] li')) === 6);

await typeInto('register-first-name', 'Ana');
await typeInto('register-last-name', 'Kovač');
await typeInto('register-email', 'ana@example.com');
await typeInto('register-phone', 'abc');
check('register: invalid phone rejected', (await errorOf('register-phone')).includes('valid phone'));
await typeInto('register-phone', '+387 61 123 456');
check('register: valid phone accepted', (await errorOf('register-phone')) === '');
await typeInto('register-password', 'short');
check('register: short password rejected', (await errorOf('register-password')).includes('at least 8'));
await typeInto('register-password', 'longpassword');
check('register: password without a number rejected', (await errorOf('register-password')).includes('number'));
await typeInto('register-password', 'longpassword1');
await typeInto('register-password-confirm', 'longpassword2');
check('register: mismatched repeat password rejected', (await errorOf('register-password-confirm')) === 'The passwords do not match.');
await typeInto('register-password-confirm', 'longpassword1');
check('register: matching repeat password accepted', (await errorOf('register-password-confirm')) === '');
check('register: unticked terms still flagged', (await errorOf('register-terms')) !== '' && !(await visible('[data-auth-result]')));
await click('#register-terms'); await b.wait(100);
check('register: ticking terms clears its error', (await errorOf('register-terms')) === '');
await typeInto('register-first-name', '<b>Ana</b>');
await click('[data-register-form] [type="submit"]'); await b.wait(150);
check('register: valid submit shows demo notice (no account created)', (await visible('[data-auth-result]')) && (await text('[data-auth-result] .notice__title')).includes('Demo only – no account was created.'));
check('register: name shown as text, not HTML', (await text('[data-result-name]')) === '<b>Ana</b>' && (await count('[data-auth-result] b')) === 0);

// =================== Profile ===================
const user = demoUsers.find((u) => u.id === demoCurrentUserId);
const ownInquiries = demoInquiries.filter((i) => i.userId === user.id);
await b.goto(BASE + '#/profile');
check('profile: title + greeting', (await ev('document.title')) === 'My account | A-Frame Living' && (await text('#app h1')) === `Hello, ${user.firstName}`);
check('profile: "Example view" banner', (await text('.preview-banner .notice__title')) === 'Example view');
check('profile: details prefilled from demo data', (await ev(`document.getElementById('profile-email').value`)) === user.email && (await ev(`document.getElementById('profile-last-name').value`)) === user.lastName);
check('profile: Save is disabled', (await ev(`document.querySelector('[data-profile-form] [type="submit"]').disabled`)));
check("profile: lists only this user's inquiries", (await count('[data-profile-inquiries] tbody tr')) === ownInquiries.length);
check('profile: inquiry statuses shown as labels', (await count('[data-profile-inquiries] .status')) === ownInquiries.length && (await text('[data-profile-inquiries] .status--answered')) === 'Answered');
await click('[data-profile-inquiries] a'); await b.wait(500);
check('profile: model link opens the model page', (await ev('location.hash')).startsWith('#/houses/'));

// =================== Admin ===================
await b.goto(BASE + '#/admin');
check('admin: title + banner', (await ev('document.title')) === 'Admin dashboard | A-Frame Living' && (await text('.preview-banner .notice__title')) === 'Preview – not functional yet');
const sections = await ev(`[...document.querySelectorAll('.admin-section')].map(s => s.id)`);
check('admin: one section per entity', JSON.stringify(sections) === '["admin-models","admin-packages","admin-locations","admin-inquiries","admin-users"]', JSON.stringify(sections));
check('admin: stats from the data', (await text('[data-stat="models"]')) === String(houses.length) && (await text('[data-stat="projects"]')) === String(locations.length) && (await text('[data-stat="users"]')) === String(demoUsers.length));
const modelRows = await ev(`[...document.querySelectorAll('#admin-models tbody tr')].map(tr => [...tr.cells].map(td => td.textContent.trim()))`);
check('admin: models table matches houses.js (names, prices, photo counts)', modelRows.length === houses.length && houses.every((h, i) => modelRows[i][0] === h.name && modelRows[i].includes(`€${h.prices.rohBau.toLocaleString('en-IE')}`) && modelRows[i].includes(`€${h.prices.turnkey.toLocaleString('en-IE')}`) && modelRows[i].includes(String(h.images.length))));
check('admin: locations table has all 14 projects', (await count('#admin-locations tbody tr')) === locations.length);
check('admin: inquiries and users tables filled', (await count('#admin-inquiries tbody tr')) === demoInquiries.length && (await count('#admin-users tbody tr')) === demoUsers.length);
check('admin: guest inquiry marked as guest', (await text('#admin-inquiries tbody')).includes('guest'));
const buttons = await ev(`(() => { const all = [...document.querySelectorAll('.admin-section button')]; return { total: all.length, disabled: all.filter(b => b.disabled).length }; })()`);
check('admin: every management button is disabled', buttons.total > 20 && buttons.total === buttons.disabled, JSON.stringify(buttons));
await click('.admin-jump a[href$="admin-inquiries"]'); await b.wait(500);
const top = await ev(`Math.round(document.getElementById('admin-inquiries').getBoundingClientRect().top)`);
check('admin: jump link scrolls to its section', top >= 0 && top < 120, `top=${top}`);
if (SHOTS) { await b.goto(BASE + '#/admin'); await b.screenshot(`${SHOTS}/d-admin-desktop.png`); }

// =================== External API placeholder ===================
await b.goto(BASE + '#/locations');
check('locations: map placeholder shown (planned Nominatim/OpenStreetMap feature)', (await visible('[data-map-placeholder]')) && (await text('[data-map-placeholder]')).includes('Nominatim'));
if (SHOTS) await b.screenshot(`${SHOTS}/d-locations-desktop.png`, false);
if (SHOTS) {
  for (const r of ['login', 'register', 'profile']) { await b.goto(BASE + `#/${r}`); await b.screenshot(`${SHOTS}/d-${r}-desktop.png`); }
}

// =================== Mobile ===================
await b.viewport(390, 844, true);
for (const route of ['#/login', '#/register', '#/profile', '#/admin', '#/locations']) {
  await b.goto(BASE + route);
  const w = await ev('({ scroll: document.documentElement.scrollWidth, inner: innerWidth })');
  check(`mobile ${route}: no horizontal overflow`, w.scroll <= 390 && w.inner === 390, JSON.stringify(w));
  if (SHOTS) await b.screenshot(`${SHOTS}/d-${route.slice(2)}-mobile.png`);
}

check('no JavaScript errors in console', b.errors.length === 0, b.errors.join(' | '));
await b.close();
process.exit(summary() ? 1 : 0);
