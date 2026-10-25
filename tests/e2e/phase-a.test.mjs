import { connect, makeChecker, BASE, SHOTS } from './cdp.mjs';

const b = await connect();
const { check, summary } = makeChecker();
const q = (sel, prop = 'textContent') => b.evaluate(`document.querySelector(${JSON.stringify(sel)})?.${prop}?.trim() ?? null`);
const click = (sel) => b.evaluate(`document.querySelector(${JSON.stringify(sel)}).click()`);

// ---------- Desktop ----------
await b.viewport(1366, 900);
await b.goto(BASE + '#/');
check('home: h1 rendered', (await q('#app h1'))?.startsWith('Timeless'));
check('home: 4 house cards', (await b.evaluate(`document.querySelectorAll('[data-featured-houses] .house-card').length`)) === 4);
check('home: stats filled', (await q('[data-stat="models"]')) === '4' && (await q('[data-stat="locations"]')) === '14', await q('[data-stat="area"]'));
check('home: title', (await b.evaluate('document.title')) === 'A-Frame Living');
check('home: nav Home is current', (await b.evaluate(`document.querySelector('.site-nav [data-nav="home"]').getAttribute('aria-current')`)) === 'page');
check('home: Atlantic prices shown', (await b.evaluate(`document.querySelector('.house-card .prices').textContent.replace(/\\s+/g,' ')`)).includes('€16,000'));
check('home: hero photo loaded', (await b.evaluate(`(() => { const i = document.querySelector('.hero__media img'); return !!i && i.complete && i.naturalWidth > 0; })()`)));

// Mark the window: if a full reload happens, the marker disappears.
await b.evaluate('window.__spaMarker = 42');

await click('.site-nav [data-nav="login"]'); await b.wait(500);
check('nav click changes hash', (await b.evaluate('location.hash')) === '#/login');
check('header "Log in" opens the Log in page', (await q('#app h1')) === 'Log in' && (await b.evaluate('document.title')) === 'Log in | A-Frame Living');
check('nav Log in is current', (await b.evaluate(`document.querySelector('.site-nav [data-nav="login"]').getAttribute('aria-current')`)) === 'page');
check('no full page reload', (await b.evaluate('window.__spaMarker')) === 42);

await b.evaluate('history.back()'); await b.wait(500);
check('browser back returns home', (await q('#app h1'))?.startsWith('Timeless'));
check('still no reload after back', (await b.evaluate('window.__spaMarker')) === 42);

await click('[data-featured-houses] .house-card .btn'); await b.wait(500);
check('card "View details" links to detail route', (await b.evaluate('location.hash')) === '#/houses/atlantic');

await b.evaluate(`location.hash = '#app'`); await b.wait(300);
check('plain #anchor is ignored by router (Atlantic stays)', (await q('#app h1')) === 'Atlantic');

await b.goto(BASE + '#/does-not-exist');
check('direct load of unknown URL shows not-found', (await q('#app h1')) === 'Page not found');
check('not-found title', (await b.evaluate('document.title')) === 'Page not found | A-Frame Living');

if (SHOTS) { await b.goto(BASE + '#/'); await b.screenshot(`${SHOTS}/a-desktop.png`); }

// ---------- Mobile ----------
await b.viewport(390, 844, true);
await b.goto(BASE + '#/');
const widths = await b.evaluate('({ scroll: document.documentElement.scrollWidth, inner: window.innerWidth })');
check('mobile: no horizontal overflow', widths.scroll <= 390 && widths.inner === 390, JSON.stringify(widths));
check('mobile: menu button visible', (await b.evaluate(`getComputedStyle(document.querySelector('[data-nav-toggle]')).display`)) !== 'none');
check('mobile: nav hidden initially', (await b.evaluate(`getComputedStyle(document.getElementById('site-nav')).display`)) === 'none');
await click('[data-nav-toggle]'); await b.wait(200);
check('mobile: menu opens', (await b.evaluate(`getComputedStyle(document.getElementById('site-nav')).display`)) !== 'none' && (await q('[data-nav-toggle]', 'getAttribute("aria-expanded")')) === 'true');
if (SHOTS) await b.screenshot(`${SHOTS}/a-mobile-menu.png`, false);
await click('.site-nav [data-nav="houses"]'); await b.wait(500);
check('mobile: menu closes after navigating', (await b.evaluate(`getComputedStyle(document.getElementById('site-nav')).display`)) === 'none');
if (SHOTS) { await b.goto(BASE + '#/'); await b.screenshot(`${SHOTS}/a-mobile.png`); }

check('no JavaScript errors in console', b.errors.length === 0, b.errors.join(' | '));
await b.close();
process.exit(summary() ? 1 : 0);
