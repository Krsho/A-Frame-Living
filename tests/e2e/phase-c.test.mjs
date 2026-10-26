// Phase C: Locations, How to buy, FAQ and Contact pages.
import { connect, makeChecker, BASE, SHOTS } from './cdp.mjs';

const b = await connect();
const { check, summary } = makeChecker();
const ev = (expr) => b.evaluate(expr);
const text = (sel) => ev(`document.querySelector(${JSON.stringify(sel)})?.textContent.trim().split(String.fromCharCode(10)).map(s => s.trim()).filter(Boolean).join(' ') ?? null`);
const count = (sel) => ev(`document.querySelectorAll(${JSON.stringify(sel)}).length`);
const click = (sel) => ev(`document.querySelector(${JSON.stringify(sel)}).click()`);
const noOverflow = async () => {
  const w = await ev('({ scroll: document.documentElement.scrollWidth, inner: innerWidth })');
  return { ok: w.scroll <= 390 && w.inner === 390, detail: JSON.stringify(w) };
};

await b.viewport(1366, 900);

// =================== Locations ===================
await b.goto(BASE + '#/locations');
check('locations: h1 + title', (await text('#app h1')) === 'Where we have built' && (await ev('document.title')) === 'Locations | A-Frame Living');
check('locations: nav item current', (await ev(`document.querySelector('.site-nav [data-nav="locations"]').getAttribute('aria-current')`)) === 'page');
check('locations: stats', (await text('[data-stat="locations"]')) === '14' && (await text('[data-stat="models"]')) === '4' && (await text('[data-stat="top-model"]')) === 'Norway');
check('locations: 14 cards', (await count('[data-location-grid] .location-card')) === 14);
check('locations: filter options with counts', JSON.stringify(await ev(`[...document.querySelectorAll('[data-model-options] label')].map(l => l.textContent.trim().split(String.fromCharCode(10)).map(s => s.trim()).filter(Boolean).join(' '))`)) === '["All 14","Atlantic 1","Norway 11","Scandinavia 1","Continental 1"]');
check('locations: count text', (await text('[data-location-count]')) === 'Showing 14 of 14 locations');

const pick = (value) => ev(`document.querySelector('[data-model-options] input[value="${value}"]').closest('label').click()`);
const cities = () => ev(`[...document.querySelectorAll('[data-location-grid] .location-card__city')].map(e => e.textContent.trim())`);
await pick('norway'); await b.wait(100);
check('locations: filter Norway -> 11', (await cities()).length === 11 && !(await cities()).includes('Sarajevo') && (await text('[data-location-count]')) === 'Showing 11 of 14 locations');
await pick('atlantic'); await b.wait(100);
check('locations: filter Atlantic -> Bjelašnica', JSON.stringify(await cities()) === '["Bjelašnica"]');
await pick('continental'); await b.wait(100);
check('locations: filter Continental -> Sarajevo', JSON.stringify(await cities()) === '["Sarajevo"]');
await pick(''); await b.wait(100);
check('locations: filter All -> 14', (await cities()).length === 14);

const card = await ev(`(() => {
  const c = [...document.querySelectorAll('.location-card')].find(c => c.querySelector('.location-card__city').textContent.trim() === 'Rostovo');
  const map = c.querySelector('a[target="_blank"]');
  return { badge: c.querySelector('.badge').getAttribute('href'), region: c.querySelector('.location-card__region').textContent.trim(), map: map.href, rel: map.rel };
})()`);
check('locations: card links to its model', card.badge === '#/houses/scandinavia');
check('locations: card shows region and country', card.region.includes('Central Bosnia') && card.region.includes('Bosnia and Herzegovina'));
check('locations: map link is OpenStreetMap, new tab, noopener', card.map.startsWith('https://www.openstreetmap.org/search?query=Rostovo') && card.rel === 'noopener', card.map);

await ev('window.__spaMarker = 1');
await click('.location-card .badge'); await b.wait(500);
check('locations: badge opens model detail without reload', (await ev('location.hash')).startsWith('#/houses/') && (await ev('window.__spaMarker')) === 1);
await ev('history.back()'); await b.wait(500);
check('locations: Back returns to locations', (await text('#app h1')) === 'Where we have built');

// Keyboard: arrow keys move between the filter radios
await ev(`document.querySelector('[data-model-options] input[value=""]').focus()`);
await b.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'ArrowRight', code: 'ArrowRight', windowsVirtualKeyCode: 39 });
await b.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'ArrowRight', code: 'ArrowRight', windowsVirtualKeyCode: 39 });
await b.wait(150);
check('locations: filter works with keyboard', JSON.stringify(await cities()) === '["Bjelašnica"]');
if (SHOTS) { await pick(''); await b.wait(400); await b.screenshot(`${SHOTS}/c-locations-desktop.png`); }

// =================== How to buy ===================
await b.goto(BASE + '#/how-to-buy');
check('how-to-buy: h1 + title', (await text('#app h1')) === 'From first idea to your own A-frame' && (await ev('document.title')) === 'How to buy | A-Frame Living');
check('how-to-buy: nav item current', (await ev(`document.querySelector('.site-nav [data-nav="how-to-buy"]').getAttribute('aria-current')`)) === 'page');
const steps = await ev(`[...document.querySelectorAll('.timeline__step .timeline__title')].map(e => e.textContent.trim())`);
check('how-to-buy: 7 steps from inquiry to handover', steps.length === 7 && steps[1] === 'Send an inquiry' && steps[6] === 'Handover', JSON.stringify(steps));
check('how-to-buy: every step has "You" and "We" roles', (await ev(`[...document.querySelectorAll('.timeline__step')].every(s => [...s.querySelectorAll('dt')].map(d => d.textContent).join() === 'You,We')`)));
const pkgCards = await ev(`[...document.querySelectorAll('[data-packages] .package-card__name')].map(e => e.textContent.trim())`);
check('how-to-buy: ROH BAU and Turnkey package cards', JSON.stringify(pkgCards) === '["ROH BAU","Turnkey"]');
const scope = await ev(`(() => {
  const t = document.querySelector('[data-scope-table]');
  return {
    head: [...t.querySelectorAll('thead th')].map(th => th.textContent.trim()),
    rows: [...t.querySelectorAll('tbody tr')].map(tr => [tr.querySelector('th').textContent.trim(), ...[...tr.querySelectorAll('td')].map(td => td.textContent.trim())]),
  };
})()`);
const { packageScope } = await import('../../frontend/js/data/packages.js');
const expectedRows = packageScope.map((r) => [r.item, r.rohBau ? '✓' : '—', r.turnkey ? '✓' : '—']);
check('how-to-buy: "who does what" table matches packages.js', JSON.stringify(scope.head) === '["ROH BAU","Turnkey"]' && JSON.stringify(scope.rows) === JSON.stringify(expectedRows), JSON.stringify(scope.rows));
check('how-to-buy: kitchen only in Turnkey, furniture in neither', JSON.stringify(scope.rows.find((r) => r[0] === 'Kitchen')) === '["Kitchen","—","✓"]' && JSON.stringify(scope.rows.find((r) => r[0] === 'Furniture')) === '["Furniture","—","—"]');

await click('.jump-links a[href$="section=packages"]'); await b.wait(500);
const jump = await ev(`Math.round(document.getElementById('packages').getBoundingClientRect().top)`);
check('how-to-buy: jump link scrolls to packages', jump >= 0 && jump < 120, `top=${jump}`);
await click('a[href="#/houses?section=compare"]'); await b.wait(600);
check('how-to-buy: prices link opens the comparison table', (await ev('location.hash')) === '#/houses?section=compare' && (await ev(`Math.round(document.getElementById('compare').getBoundingClientRect().top)`)) < 120);
await b.goto(BASE + '#/how-to-buy');
await click('.timeline__step:nth-child(2) a'); await b.wait(500);
check('how-to-buy: step link goes to contact', (await ev('location.hash')) === '#/contact');
if (SHOTS) { await b.goto(BASE + '#/how-to-buy'); await b.screenshot(`${SHOTS}/c-how-to-buy-desktop.png`); }

// =================== FAQ ===================
const press = async (key, code, vk) => {
  await b.send('Input.dispatchKeyEvent', { type: 'keyDown', key, code, windowsVirtualKeyCode: vk, text: key === 'Enter' ? '\r' : key === ' ' ? ' ' : undefined });
  await b.send('Input.dispatchKeyEvent', { type: 'keyUp', key, code, windowsVirtualKeyCode: vk });
  await b.wait(150);
};
const openStates = () => ev(`[...document.querySelectorAll('.faq-item')].map(d => d.open)`);

await b.goto(BASE + '#/faq');
check('faq: h1 + title', (await text('#app h1')) === 'Frequently asked questions' && (await ev('document.title')) === 'FAQ | A-Frame Living');
check('faq: nav item current', (await ev(`document.querySelector('.site-nav [data-nav="faq"]').getAttribute('aria-current')`)) === 'page');
const groups = await ev(`[...document.querySelectorAll('.faq-group__title')].map(e => e.textContent.trim())`);
check('faq: 5 categories', JSON.stringify(groups) === '["Models and prices","ROH BAU or Turnkey","Customization","Locations","Buying process"]', JSON.stringify(groups));
const total = await count('.faq-item');
check('faq: at least 12 questions, each with a summary and answer', total >= 12 && (await count('.faq-item > summary')) === total && (await count('.faq-item > .faq-item__answer')) === total, `items=${total}`);
check('faq: all collapsed initially', (await openStates()).every((o) => !o));

await click('.faq-item:nth-of-type(1) > summary'); await b.wait(100);
check('faq: click opens an item', (await openStates())[0] === true);
await click('.faq-item:nth-of-type(1) > summary'); await b.wait(100);
check('faq: second click closes it', (await openStates())[0] === false);

// Keyboard: Tab from the "Collapse all" button lands on the first question; Enter and Space toggle it.
await ev(`document.querySelector('[data-faq-collapse]').focus()`);
await press('Tab', 'Tab', 9);
check('faq: Tab moves focus to the first question', (await ev(`document.activeElement === document.querySelector('.faq-item > summary')`)));
await press('Enter', 'Enter', 13);
check('faq: Enter opens the focused question', (await openStates())[0] === true);
await press(' ', 'Space', 32);
check('faq: Space closes it again', (await openStates())[0] === false);
check('faq: focus ring style on summary', (await ev(`(() => { const s = document.querySelector('.faq-item > summary'); return getComputedStyle(s).outlineStyle; })()`)) !== 'none');

await click('[data-faq-expand]'); await b.wait(100);
check('faq: Expand all opens every item', (await openStates()).every((o) => o));
await click('[data-faq-collapse]'); await b.wait(100);
check('faq: Collapse all closes every item', (await openStates()).every((o) => !o));

const { houses } = await import('../../frontend/js/data/houses.js');
const priceAnswer = await ev(`(() => { const d = [...document.querySelectorAll('.faq-item')].find(d => d.querySelector('summary').textContent.includes('How much')); return d.querySelector('.faq-item__answer').textContent; })()`);
check('faq: price answer lists every model price from houses.js', houses.every((h) => priceAnswer.includes(h.name) && priceAnswer.includes(`€${h.prices.rohBau.toLocaleString('en-IE')}`) && priceAnswer.includes(`€${h.prices.turnkey.toLocaleString('en-IE')}`)));
const locAnswer = await ev(`(() => { const d = [...document.querySelectorAll('.faq-item')].find(d => d.querySelector('summary').textContent.includes('Where have you built')); return d.querySelector('.faq-item__answer').textContent; })()`);
check('faq: locations answer uses location data (14 projects, 11 Norway)', locAnswer.includes('14 projects') && locAnswer.includes('11 of them are Norway'));

await click('[data-faq-categories] a[href$="faq-process"]'); await b.wait(500);
const procTop = await ev(`Math.round(document.getElementById('faq-process').getBoundingClientRect().top)`);
check('faq: category link scrolls to its group', procTop >= 0 && procTop < 120, `top=${procTop}`);
await click('[data-faq-expand]'); await b.wait(100);
await click('#faq-locations a[href="#/locations"]'); await b.wait(500);
check('faq: link inside an answer navigates', (await ev('location.hash')) === '#/locations');
if (SHOTS) { await b.goto(BASE + '#/faq'); await click('.faq-item:nth-of-type(2) > summary'); await b.wait(200); await b.screenshot(`${SHOTS}/c-faq-desktop.png`); }

// =================== Contact ===================
// Real typing: focus the field, clear it, then insert text through the browser (fires input events).
const typeInto = async (id, value) => {
  await ev(`(() => { const f = document.getElementById('${id}'); f.focus(); f.value = ''; })()`);
  if (value) await b.send('Input.insertText', { text: value });
  await b.wait(50);
};
const errorsShown = () => ev(`Object.fromEntries([...document.querySelectorAll('.field-error')].map(e => [e.id.replace('contact-', '').replace('-error', ''), e.textContent]))`);
const visible = (sel) => ev(`(() => { const e = document.querySelector(${JSON.stringify(sel)}); return !!e && getComputedStyle(e).display !== 'none' && !e.closest('[hidden]'); })()`);
const fillValid = async () => {
  await typeInto('contact-name', 'Ana Kovač');
  await typeInto('contact-email', 'ana@example.com');
  await typeInto('contact-subject', 'Norway on Kupres');
  await typeInto('contact-message', 'We have a plot on Kupres and would like to know more about the Norway model.');
};

await b.goto(BASE + '#/contact');
check('contact: h1 + title', (await text('#app h1')) === "Let's talk about your A-frame" && (await ev('document.title')) === 'Contact | A-Frame Living');
check('contact: nav item current', (await ev(`document.querySelector('.site-nav [data-nav="contact"]').getAttribute('aria-current')`)) === 'page');
const details = await ev(`[...document.querySelectorAll('[data-company-details] a')].map(a => a.getAttribute('href'))`);
check('contact: details card with email and phone links', details.includes('mailto:info@aframe-living.example') && details.includes('tel:+38733000000'), JSON.stringify(details));
check('contact: every field has a label', (await ev(`['name','email','subject','message'].every(n => { const f = document.forms[0].elements[n]; return f.labels.length === 1 && f.labels[0].textContent.trim().length > 0; })`)));
check('contact: subject empty without ?model', (await ev(`document.getElementById('contact-subject').value`)) === '');

// Empty submit
await click('[data-submit]'); await b.wait(150);
let errs = await errorsShown();
check('contact: empty submit shows 4 field errors', Object.values(errs).filter(Boolean).length === 4, JSON.stringify(errs));
check('contact: fields marked aria-invalid', (await count('[data-contact-form] [aria-invalid="true"]')) === 4);
check('contact: error summary lists 4 problems', (await visible('[data-form-summary]')) && (await count('[data-form-summary] li a')) === 4 && (await text('[data-form-summary] .notice__title')).startsWith('There are 4 problems'));
check('contact: focus moves to the first invalid field', (await ev('document.activeElement.id')) === 'contact-name');
check('contact: errors are linked with aria-describedby', (await ev(`document.getElementById('contact-email').getAttribute('aria-describedby')`)) === 'contact-email-error');

// Fixing a field clears its error right away
await typeInto('contact-name', 'Ana Kovač');
errs = await errorsShown();
check('contact: typing a valid name clears its error', errs.name === '' && (await ev(`document.getElementById('contact-name').hasAttribute('aria-invalid')`)) === false);
check('contact: summary updates to 3 problems', (await count('[data-form-summary] li a')) === 3);

// Invalid email
await typeInto('contact-email', 'ana@');
errs = await errorsShown();
check('contact: invalid email shows email error', errs.email.includes('valid email address'), errs.email);
await ev(`[...document.querySelectorAll('[data-form-summary] a')].find(a => a.dataset.focusField === 'contact-email').click()`); await b.wait(100);
check('contact: summary link focuses the field without changing the route', (await ev('document.activeElement.id')) === 'contact-email' && (await ev('location.hash')) === '#/contact');

// Too-short message
await typeInto('contact-message', 'Hi');
check('contact: too-short message shows min-length error', (await errorsShown()).message.includes('at least 10 characters'));

// Blur validation on a fresh form (before any submit)
await b.goto(BASE + '#/contact');
await typeInto('contact-email', 'not-an-email');
await ev(`document.getElementById('contact-subject').focus()`); await b.wait(100);
check('contact: leaving a wrongly filled field shows its error', (await errorsShown()).email.includes('valid email'));
check('contact: untouched empty fields are not flagged before submit', (await errorsShown()).name === '' && (await errorsShown()).subject === '');

// Valid submit -> sending state -> demo confirmation
await b.goto(BASE + '#/contact');
await fillValid();
await click('[data-submit]'); await b.wait(100);
check('contact: button shows "Sending…" and is disabled while sending', (await text('[data-submit]')) === 'Sending…' && (await ev(`document.querySelector('[data-submit]').disabled`)));
await b.wait(1200);
check('contact: form is hidden after sending', !(await visible('[data-contact-form]')));
check('contact: confirmation is visible', await visible('[data-contact-success]'));
const successText = await text('[data-contact-success]');
check('contact: confirmation says it is a demo and nothing was sent', successText.includes('Demo only – your message was not sent') && !/has been sent|was sent successfully/i.test(successText.replace('was not sent', '')), successText.slice(0, 120));
check('contact: confirmation greets by name and repeats the message', successText.includes('Thank you, Ana Kovač!') && successText.includes('ana@example.com') && successText.includes('Norway on Kupres'));
check('contact: focus moves to the confirmation heading', (await ev(`document.activeElement.classList.contains('contact-success__title')`)));

await click('[data-send-another]'); await b.wait(150);
check('contact: "Send another message" shows an empty form again', (await visible('[data-contact-form]')) && !(await visible('[data-contact-success]')) && (await ev(`[...document.forms[0].elements].filter(e => e.name).every(e => e.value === '')`)));
check('contact: reset clears errors and focuses the name field', Object.values(await errorsShown()).every((e) => e === '') && (await ev('document.activeElement.id')) === 'contact-name');

// Simulated failure keeps the text and shows an error
await ev(`import('/js/services/inquiries.js').then(m => { m.demoSettings.fail = true; })`);
await fillValid();
await click('[data-submit]'); await b.wait(1300);
check('contact: failed send shows an error message', (await visible('[data-form-status]')) && (await text('[data-form-status]')).includes('could not be sent'));
check('contact: failed send keeps the typed text and re-enables the button', (await ev(`document.getElementById('contact-subject').value`)) === 'Norway on Kupres' && !(await ev(`document.querySelector('[data-submit]').disabled`)) && (await visible('[data-contact-form]')));
await ev(`import('/js/services/inquiries.js').then(m => { m.demoSettings.fail = false; })`);

// User input is shown as text, never as HTML
await b.goto(BASE + '#/contact');
await fillValid();
await typeInto('contact-name', '<img src=x onerror=alert(1)>');
await click('[data-submit]'); await b.wait(1200);
check('contact: HTML in the name is shown as text (no injection)', (await count('[data-contact-success] img')) === 0 && (await text('[data-success-name]')) === '<img src=x onerror=alert(1)>');

// Prefill from the detail page's "Inquire" button
await b.goto(BASE + '#/contact?model=norway');
check('contact: ?model=norway prefills the subject', (await ev(`document.getElementById('contact-subject').value`)) === 'Inquiry about the Norway model');
await b.goto(BASE + '#/contact?model=not-a-model');
check('contact: unknown model is ignored', (await ev(`document.getElementById('contact-subject').value`)) === '');
await b.goto(BASE + '#/houses/continental');
await click('[data-inquire-link]'); await b.wait(600);
check('contact: "Inquire about Continental" opens the form with subject filled', (await text('#app h1')) === "Let's talk about your A-frame" && (await ev(`document.getElementById('contact-subject').value`)) === 'Inquiry about the Continental model');
if (SHOTS) {
  await b.goto(BASE + '#/contact'); await click('[data-submit]'); await b.wait(200);
  await b.screenshot(`${SHOTS}/c-contact-errors-desktop.png`);
  await fillValid(); await click('[data-submit]'); await b.wait(1200);
  await b.screenshot(`${SHOTS}/c-contact-success-desktop.png`, false);
}

// =================== Navigation across all pages ===================
const navPages = {
  home: 'Timeless A-frame homes, from compact retreat to family house.',
  houses: 'Our A-frame models',
  locations: 'Where we have built',
  'how-to-buy': 'From first idea to your own A-frame',
  faq: 'Frequently asked questions',
  contact: "Let's talk about your A-frame",
};
await b.goto(BASE + '#/');
await ev('window.__spaMarker = 99');
for (const [nav, h1] of Object.entries(navPages)) {
  await click(`.site-nav__list [data-nav="${nav}"]`); await b.wait(500);
  const current = await ev(`[...document.querySelectorAll('.site-nav__list [aria-current="page"]')].map(a => a.dataset.nav)`);
  check(`nav (desktop): "${nav}" opens the right page and is the only current item`, (await text('#app h1')) === h1 && JSON.stringify(current) === JSON.stringify([nav]));
}
check('nav: no full reload while visiting every page', (await ev('window.__spaMarker')) === 99);
await ev('history.back()'); await b.wait(500);
check('nav: Back from Contact returns to FAQ', (await text('#app h1')) === navPages.faq);
await ev('history.back()'); await b.wait(500);
check('nav: Back again returns to How to buy', (await text('#app h1')) === navPages['how-to-buy']);
await ev('history.forward()'); await b.wait(500);
check('nav: Forward returns to FAQ', (await text('#app h1')) === navPages.faq && (await ev('window.__spaMarker')) === 99);

// =================== Mobile (all Phase C pages) ===================
await b.viewport(390, 844, true);
await b.goto(BASE + '#/');
for (const [nav, h1] of Object.entries(navPages)) {
  await click('[data-nav-toggle]'); await b.wait(150);
  await click(`.site-nav__list [data-nav="${nav}"]`); await b.wait(500);
  const menuClosed = (await ev(`getComputedStyle(document.getElementById('site-nav')).display`)) === 'none';
  check(`nav (mobile menu): "${nav}" opens the right page and closes the menu`, (await text('#app h1')) === h1 && menuClosed);
}
for (const route of ['#/locations', '#/how-to-buy', '#/faq', '#/contact']) {
  await b.goto(BASE + route);
  const o = await noOverflow();
  check(`mobile ${route}: no horizontal overflow`, o.ok, o.detail);
  if (SHOTS) await b.screenshot(`${SHOTS}/c-${route.slice(2)}-mobile.png`);
}

check('no JavaScript errors in console', b.errors.length === 0, b.errors.join(' | '));
await b.close();
process.exit(summary() ? 1 : 0);
