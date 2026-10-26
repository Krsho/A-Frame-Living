import { connect, makeChecker, BASE, SHOTS } from './cdp.mjs';

const b = await connect();
const { check, summary } = makeChecker();
const ev = (expr) => b.evaluate(expr);
const text = (sel) => ev(`document.querySelector(${JSON.stringify(sel)})?.textContent.replace(/\\s+/g,' ').trim() ?? null`);
const cardNames = () => ev(`[...document.querySelectorAll('[data-house-grid] .house-card__title')].map(e => e.textContent.trim())`);
const clickLabel = (name, value) => ev(`document.querySelector('input[name="${name}"][value="${value}"]').closest('label').click()`);

// Load every lazy image by scrolling through the page, then wait for them to finish.
const loadAllImages = async () => {
  await ev(`(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 400) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 60)); }
    await Promise.all([...document.images].map(img => img.complete ? null : new Promise(r => { img.onload = img.onerror = r; })));
    window.scrollTo(0, 0);
  })()`);
  await b.wait(300);
};
const imageReport = (scope) => ev(`(() => {
  const imgs = [...document.querySelectorAll(${JSON.stringify(scope + ' img')})];
  return {
    total: imgs.length,
    loaded: imgs.filter(i => i.complete && i.naturalWidth > 0).length,
    placeholders: document.querySelectorAll(${JSON.stringify(scope + ' .img-placeholder')}).length,
    srcs: imgs.map(i => i.getAttribute('src')),
    alts: imgs.map(i => i.alt),
  };
})()`);

const expected = {
  atlantic:    { name: 'Atlantic',    area: '46 m²',  bed: '1', bath: '1', roh: '€16,000', turn: '€37,000', features: [] },
  norway:      { name: 'Norway',      area: '56 m²',  bed: '2', bath: '1', roh: '€22,000', turn: '€48,000', features: [] },
  scandinavia: { name: 'Scandinavia', area: '105 m²', bed: '3', bath: '2', roh: '€40,000', turn: '€80,000', features: ['Laminated timber'] },
  continental: { name: 'Continental', area: '124 m²', bed: '3', bath: '2', roh: '€45,000', turn: '€90,000', features: ['Laminated timber', 'Aluminium windows and doors', 'Fireplace'] },
};

// ---------- Catalogue (desktop) ----------
await b.viewport(1366, 900);
await b.goto(BASE + '#/houses');
check('catalogue: h1', (await text('#app h1')) === 'Our A-frame models');
check('catalogue: title', (await ev('document.title')) === 'Models | A-Frame Living');
check('catalogue: nav "Models" is current', (await ev(`document.querySelector('.site-nav [data-nav="houses"]').getAttribute('aria-current')`)) === 'page');
check('catalogue: 4 cards in order', JSON.stringify(await cardNames()) === '["Atlantic","Norway","Scandinavia","Continental"]');
await loadAllImages();
let imgs = await imageReport('[data-house-grid]');
check('catalogue: all 4 card photos loaded', imgs.total === 4 && imgs.loaded === 4 && imgs.placeholders === 0, JSON.stringify(imgs.srcs));
check('catalogue: each photo matches its model', imgs.srcs.every((s, i) => s === `assets/images/${Object.keys(expected)[i]}/${Object.keys(expected)[i]}.jfif`));
check('catalogue: alt text present and model-specific', imgs.alts.every((a, i) => a.startsWith(Object.values(expected)[i].name)));
const cardText = await ev(`[...document.querySelectorAll('[data-house-grid] .house-card')].map(c => c.textContent.replace(/\\s+/g,' '))`);
check('catalogue: cards show area, bed/bath, both prices', Object.values(expected).every((e, i) =>
  cardText[i].includes(e.area) && cardText[i].includes(`${e.bed} bedroom`) && cardText[i].includes(`${e.bath} bathroom`) && cardText[i].includes(e.roh) && cardText[i].includes(e.turn)));
check('catalogue: Continental card lists its 3 features', ['Laminated timber', 'Aluminium windows and doors', 'Fireplace'].every(f => cardText[3].includes(f)));
const imgFit = await ev(`(() => { const i = document.querySelector('[data-house-grid] img'); const r = i.getBoundingClientRect(); return { fit: getComputedStyle(i).objectFit, ratio: +(r.width / r.height).toFixed(2) }; })()`);
check('catalogue: images use object-fit cover in a 3:2 box (no distortion)', imgFit.fit === 'cover' && Math.abs(imgFit.ratio - 1.5) < 0.02, JSON.stringify(imgFit));

// ---------- Filters ----------
check('filters: bedroom options from data', JSON.stringify(await ev(`[...document.querySelectorAll('input[name="bedrooms"]')].map(i => i.value)`)) === '["","1","2","3"]');
check('filters: feature options from data', JSON.stringify(await ev(`[...document.querySelectorAll('input[name="features"]')].map(i => i.value)`)) === '["Laminated timber","Aluminium windows and doors","Fireplace"]');
check('filters: initial count', (await text('[data-result-count]')) === 'Showing 4 of 4 models');

await clickLabel('bedrooms', '3'); await b.wait(100);
check('filter 3 bedrooms -> Scandinavia, Continental', JSON.stringify(await cardNames()) === '["Scandinavia","Continental"]');
check('filter count updates', (await text('[data-result-count]')) === 'Showing 2 of 4 models');

await clickLabel('bedrooms', '2'); await b.wait(100);
check('filter 2 bedrooms -> Norway', JSON.stringify(await cardNames()) === '["Norway"]');

await clickLabel('bedrooms', ''); await clickLabel('features', 'Fireplace'); await b.wait(100);
check('filter Fireplace -> Continental', JSON.stringify(await cardNames()) === '["Continental"]');

await clickLabel('features', 'Fireplace'); await clickLabel('features', 'Laminated timber'); await b.wait(100);
check('filter Laminated timber -> Scandinavia, Continental', JSON.stringify(await cardNames()) === '["Scandinavia","Continental"]');

await clickLabel('bedrooms', '1'); await b.wait(100);
check('filter 1 bedroom + Laminated timber -> no results', (await cardNames()).length === 0);
check('no-match message visible', (await ev(`!document.querySelector('[data-empty-state]').hidden`)) && (await ev(`document.querySelector('[data-house-grid]').hidden`)));
if (SHOTS) await b.screenshot(`${SHOTS}/b-empty.png`, false);

await ev(`document.querySelector('[data-empty-state] [data-clear-filters]').click()`); await b.wait(200);
check('clear filters (empty-state button) -> 4 models', (await cardNames()).length === 4 && (await text('[data-result-count]')) === 'Showing 4 of 4 models');
check('clear resets inputs', (await ev(`document.querySelector('input[name="bedrooms"][value=""]').checked && !document.querySelector('input[name="features"]:checked')`)));

await clickLabel('features', 'Fireplace'); await b.wait(100);
await ev(`document.querySelector('form [data-clear-filters]').click()`); await b.wait(200);
check('clear filters (form button) -> 4 models', (await cardNames()).length === 4);

// Keyboard: focus a radio and press arrow key
await ev(`document.querySelector('input[name="bedrooms"][value=""]').focus()`);
await b.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'ArrowRight', code: 'ArrowRight', windowsVirtualKeyCode: 39 });
await b.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'ArrowRight', code: 'ArrowRight', windowsVirtualKeyCode: 39 });
await b.wait(150);
check('filters work with keyboard (arrow key selects 1 bedroom)', JSON.stringify(await cardNames()) === '["Atlantic"]');
await ev(`document.querySelector('form [data-clear-filters]').click()`); await b.wait(150);

// ---------- Comparison table ----------
const table = await ev(`(() => {
  const t = document.querySelector('[data-compare-table]');
  const head = [...t.querySelectorAll('thead th')].map(th => th.textContent.trim());
  const rows = Object.fromEntries([...t.querySelectorAll('tbody tr')].map(tr => [tr.querySelector('th').textContent.trim(), [...tr.querySelectorAll('td')].map(td => td.textContent.trim())]));
  return { head, rows };
})()`);
check('table: 4 model columns', JSON.stringify(table.head) === '["Atlantic","Norway","Scandinavia","Continental"]');
check('table: area row', JSON.stringify(table.rows['Living area']) === '["46 m²","56 m²","105 m²","124 m²"]');
check('table: bedrooms/bathrooms', JSON.stringify(table.rows['Bedrooms']) === '["1","2","3","3"]' && JSON.stringify(table.rows['Bathrooms']) === '["1","1","2","2"]');
check('table: ROH BAU prices', JSON.stringify(table.rows['ROH BAU']) === '["€16,000","€22,000","€40,000","€45,000"]');
check('table: Turnkey prices', JSON.stringify(table.rows['Turnkey']) === '["€37,000","€48,000","€80,000","€90,000"]');
check('table: Fireplace only Continental', JSON.stringify(table.rows['Fireplace']) === '["—","—","—","✓"]');
check('table: Laminated timber Scandinavia+Continental', JSON.stringify(table.rows['Laminated timber']) === '["—","—","✓","✓"]');
check('table: shared rooms all ticked', ['Living room', 'Kitchen', 'Gallery as a second floor'].every(r => JSON.stringify(table.rows[r]) === '["✓","✓","✓","✓"]'));
const pk = await ev(`[...document.querySelectorAll('[data-packages] .package-card')].map(c => [...c.children].map(el => el.textContent.trim()).join(' '))`);
check('catalogue: 2 package cards with descriptions, no prices', pk.length === 2 && pk[0].startsWith('ROH BAU') && pk[0].includes('finished on the outside') && pk[1].startsWith('Turnkey') && pk[1].includes('except the furniture') && !pk.join('').includes('€'), JSON.stringify(pk));
check('catalogue: no TODO left', (await ev(`document.querySelectorAll('#app .todo').length`)) === 0);
check('table: footprint row', JSON.stringify(table.rows['Footprint']) === '["5 × 7 m","5 × 7 m","9 × 7 m","14 × 8 m"]', JSON.stringify(table.rows['Footprint']));
check('table: side room row', JSON.stringify(table.rows['Side room']) === '["—","2.5 × 4 m","3.6 × 4.8 m","—"]', JSON.stringify(table.rows['Side room']));
check('table: height row', JSON.stringify(table.rows['Height']) === '["5.8 m","5.8 m","7.9 m","7.5 m"]', JSON.stringify(table.rows['Height']));

// ---------- Navigation: card -> detail -> back -> forward ----------
await ev('window.__spaMarker = 7');
await ev(`document.querySelector('[data-house-grid] .house-card:nth-child(2) .btn').click()`); await b.wait(500);
check('card button opens Norway detail', (await ev('location.hash')) === '#/houses/norway' && (await text('#app h1')) === 'Norway');
await ev('history.back()'); await b.wait(500);
check('Back returns to catalogue', (await ev('location.hash')) === '#/houses' && (await text('#app h1')) === 'Our A-frame models');
await ev('history.forward()'); await b.wait(500);
check('Forward returns to Norway', (await text('#app h1')) === 'Norway');
check('no full reload during card/back/forward', (await ev('window.__spaMarker')) === 7);

// ---------- Each detail route ----------
for (const [slug, e] of Object.entries(expected)) {
  await b.goto(BASE + `#/houses/${slug}`);
  await loadAllImages();
  const main = await ev(`(() => { const i = document.querySelector('[data-main-image] img'); return i ? { src: i.getAttribute('src'), ok: i.complete && i.naturalWidth > 0, alt: i.alt } : null; })()`);
  const specs = await ev(`Object.fromEntries([...document.querySelectorAll('[data-spec-list] > div')].map(d => [d.querySelector('dt').textContent, d.querySelector('dd').textContent.trim()]))`);
  const prices = await text('[data-prices]');
  const inquire = await ev(`document.querySelector('[data-inquire-link]').getAttribute('href')`);
  const featureText = await text('[data-features]');
  const others = await ev(`[...document.querySelectorAll('[data-other-houses] .house-card__title')].map(e => e.textContent.trim())`);
  check(`${slug}: h1 + title`, (await text('#app h1')) === e.name && (await ev('document.title')) === `${e.name} | A-Frame Living`);
  check(`${slug}: correct photo loaded`, main?.ok && main.src === `assets/images/${slug}/${slug}.jfif` && main.alt.startsWith(e.name), JSON.stringify(main));
  check(`${slug}: area/bed/bath`, specs['Living area'] === e.area && specs['Bedrooms'] === e.bed && specs['Bathrooms'] === e.bath);
  check(`${slug}: features`, e.features.length ? e.features.every(f => specs['Features'].includes(f) && featureText.includes(f)) : specs['Features'] === 'No additional features listed');
  const dims = { atlantic: ['5 × 7 m', undefined, '5.8 m'], norway: ['5 × 7 m', '2.5 × 4 m', '5.8 m'], scandinavia: ['9 × 7 m', '3.6 × 4.8 m', '7.9 m'], continental: ['14 × 8 m', undefined, '7.5 m'] }[slug];
  check(`${slug}: footprint, side room, height`, specs['Footprint'] === dims[0] && specs['Side room'] === dims[1] && specs['Height'] === dims[2], JSON.stringify(specs));
  const pkd = await ev(`[...document.querySelectorAll('[data-packages] .package-card')].map(c => [...c.children].map(el => el.textContent.trim()).join(' '))`);
  check(`${slug}: package cards show this model's prices`, pkd.length === 2 && pkd[0].includes(e.roh) && pkd[1].includes(e.turn), JSON.stringify(pkd));
  check(`${slug}: both prices`, prices.includes(e.roh) && prices.includes(e.turn), prices);
  check(`${slug}: inquiry link`, inquire === `#/contact?model=${slug}`);
  check(`${slug}: 3 other models, not itself`, others.length === 3 && !others.includes(e.name));
  check(`${slug}: no placeholders on page`, (await ev(`document.querySelectorAll('#app .img-placeholder').length`)) === 0);
}
check('locations: Atlantic shows Bjelašnica', (await b.goto(BASE + '#/houses/atlantic'), (await text('[data-locations]')).includes('Bjelašnica')));
await b.goto(BASE + '#/houses/norway');
const norwayLocs = await ev(`[...document.querySelectorAll('[data-locations] li')].map(li => li.textContent.trim())`);
check('locations: Norway lists its 11 locations', norwayLocs.length === 11 && norwayLocs.includes('Kupres') && norwayLocs.includes('Velika Kladuša') && !norwayLocs.includes('Sarajevo'), JSON.stringify(norwayLocs));
check('locations: Norway count line', (await text('[data-locations] p')) === '11 completed projects in Bosnia and Herzegovina:');
check('detail: no TODO left on Norway page', (await ev(`document.querySelectorAll('#app .todo').length`)) === 0);

await ev(`document.querySelector('[data-inquire-link]').click()`); await b.wait(500);
check('inquiry link navigates to contact route', (await ev('location.hash')) === '#/contact?model=norway');

await b.goto(BASE + '#/houses/not-a-model');
check('unknown model slug -> not-found', (await text('#app h1')) === 'Page not found');

// ---------- Compare link scrolls to table ----------
await b.goto(BASE + '#/houses/continental');
await ev(`document.querySelector('a[href="#/houses?section=compare"]').click()`); await b.wait(600);
const cmp = await ev(`(() => { const r = document.getElementById('compare').getBoundingClientRect(); return { top: Math.round(r.top), scrollY: Math.round(scrollY) }; })()`);
check('"Compare models" scrolls to the table', cmp.scrollY > 300 && cmp.top >= 0 && cmp.top < 120, JSON.stringify(cmp));

if (SHOTS) {
  await b.goto(BASE + '#/houses'); await loadAllImages(); await b.screenshot(`${SHOTS}/b-catalogue-desktop.png`);
  await b.goto(BASE + '#/houses/scandinavia'); await loadAllImages(); await b.screenshot(`${SHOTS}/b-detail-desktop.png`);
}

// ---------- Mobile ----------
await b.viewport(390, 844, true);
for (const route of ['#/houses', '#/houses/atlantic', '#/houses/continental', '#/']) {
  await b.goto(BASE + route);
  await loadAllImages();
  const w = await ev('({ scroll: document.documentElement.scrollWidth, inner: window.innerWidth })');
  check(`mobile ${route}: no horizontal page overflow`, w.scroll <= 390 && w.inner === 390, JSON.stringify(w));
}
await b.goto(BASE + '#/houses');
const tbl = await ev(`(() => { const s = document.querySelector('.table-scroll'); return { scrollW: s.scrollWidth, clientW: s.clientWidth }; })()`);
check('mobile: comparison table scrolls inside its own box', tbl.scrollW > tbl.clientW, JSON.stringify(tbl));
await clickLabel('bedrooms', '3'); await b.wait(150);
check('mobile: filter works', JSON.stringify(await cardNames()) === '["Scandinavia","Continental"]');
await ev(`document.querySelector('form [data-clear-filters]').click()`); await b.wait(150);
await ev(`document.querySelector('[data-nav-toggle]').click()`); await b.wait(150);
await ev(`document.querySelector('.site-nav [data-nav="home"]').click()`); await b.wait(500);
check('mobile: menu navigation works', (await ev('location.hash')) === '#/' && (await ev(`getComputedStyle(document.getElementById('site-nav')).display`)) === 'none');
await b.goto(BASE + '#/');
await loadAllImages();
imgs = await imageReport('[data-featured-houses]');
check('home: featured cards now show the 4 real photos', imgs.loaded === 4 && imgs.placeholders === 0);

if (SHOTS) {
  await b.goto(BASE + '#/houses'); await loadAllImages(); await b.screenshot(`${SHOTS}/b-catalogue-mobile.png`);
  await b.goto(BASE + '#/houses/continental'); await loadAllImages(); await b.screenshot(`${SHOTS}/b-detail-mobile.png`);
}

check('no JavaScript errors in console', b.errors.length === 0, b.errors.join(' | '));
await b.close();
process.exit(summary() ? 1 : 0);
