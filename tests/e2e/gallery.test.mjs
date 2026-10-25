import { connect, makeChecker, BASE, SHOTS } from './cdp.mjs';

const b = await connect();
const { check, summary } = makeChecker();
const ev = (expr) => b.evaluate(expr);
const counts = { atlantic: 7, norway: 10, scandinavia: 12, continental: 14 };
const loadAll = () => ev(`(async () => {
  for (let y = 0; y < document.body.scrollHeight; y += 300) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 50)); }
  await Promise.all([...document.images].map(img => img.complete ? null : new Promise(r => { img.onload = img.onerror = r; })));
  window.scrollTo(0, 0);
})()`);
const lb = () => ev(`(() => { const d = document.querySelector('dialog.lightbox'); return d ? { open: d.open, src: d.querySelector('.lightbox__image').getAttribute('src'), counter: d.querySelector('[data-lightbox-counter]').textContent, caption: d.querySelector('[data-lightbox-caption]').textContent } : null; })()`);
const key = async (k, code) => {
  await b.send('Input.dispatchKeyEvent', { type: 'keyDown', key: k, code: k, windowsVirtualKeyCode: code });
  await b.send('Input.dispatchKeyEvent', { type: 'keyUp', key: k, code: k, windowsVirtualKeyCode: code });
  await b.wait(150);
};

await b.viewport(1366, 900);
for (const [slug, n] of Object.entries(counts)) {
  await b.goto(BASE + `#/houses/${slug}`);
  await loadAll();
  const g = await ev(`(() => {
    const imgs = [...document.querySelectorAll('[data-gallery] img')];
    return {
      tiles: document.querySelectorAll('[data-gallery] .photo-button').length,
      loaded: imgs.filter(i => i.complete && i.naturalWidth > 0).length,
      broken: imgs.filter(i => i.complete && i.naturalWidth === 0).map(i => i.getAttribute('src')),
      placeholders: document.querySelectorAll('#app .img-placeholder').length,
      allAlts: imgs.every(i => i.alt.length > 10),
      count: document.querySelector('[data-photo-count]').textContent,
      fits: [...new Set(imgs.map(i => getComputedStyle(i).objectFit))],
    };
  })()`);
  check(`${slug}: ${n - 1} gallery tiles`, g.tiles === n - 1, `tiles=${g.tiles}`);
  check(`${slug}: every gallery photo loaded, none broken`, g.loaded === n - 1 && g.broken.length === 0 && g.placeholders === 0, JSON.stringify(g.broken));
  check(`${slug}: alt text on every photo`, g.allAlts);
  check(`${slug}: photo count label`, g.count === `${n} photos`);
  check(`${slug}: tiles crop with object-fit cover (no stretching)`, g.fits.length === 1 && g.fits[0] === 'cover');
}

// EXIF rotation: norway1.JPG is stored landscape with a "rotate 90°" flag; the browser should show it portrait.
await b.goto(BASE + '#/houses/norway'); await loadAll();
const rot = await ev(`(() => { const i = document.querySelector('[data-gallery] img[src$="norway1.JPG"]'); return { w: i.naturalWidth, h: i.naturalHeight }; })()`);
check('norway1.JPG shown upright (portrait after EXIF rotation)', rot.h > rot.w, JSON.stringify(rot));

// ---------- Lightbox ----------
await ev(`document.querySelector('[data-main-image] .photo-button').click()`); await b.wait(300);
let s = await lb();
check('main photo opens viewer at 1 / 10', s?.open && s.counter === '1 / 10' && s.src.endsWith('norway/norway.jfif'), JSON.stringify(s));
check('viewer caption is the alt text', s.caption.startsWith('Norway A-frame house'));
await ev(`document.querySelector('[data-lightbox-next]').click()`); await b.wait(150);
check('next button -> 2 / 10', (await lb()).counter === '2 / 10');
await key('ArrowRight', 39);
check('ArrowRight -> 3 / 10', (await lb()).counter === '3 / 10');
await key('ArrowLeft', 37); await key('ArrowLeft', 37); await key('ArrowLeft', 37);
check('wraps from first to last (10 / 10)', (await lb()).counter === '10 / 10');
await b.wait(800);
const full = await ev(`(() => { const i = document.querySelector('.lightbox__image'); const r = i.getBoundingClientRect(); return { fit: getComputedStyle(i).objectFit, ratioShown: +(r.width / r.height).toFixed(2), ratioReal: +(i.naturalWidth / i.naturalHeight).toFixed(2), fitsScreen: r.bottom <= innerHeight && r.right <= innerWidth }; })()`);
check('viewer shows full photo uncropped, within the screen', full.fit === 'contain' && Math.abs(full.ratioShown - full.ratioReal) < 0.02 && full.fitsScreen, JSON.stringify(full));
if (SHOTS) await b.screenshot(`${SHOTS}/g-lightbox-desktop.png`, false);
await key('Escape', 27);
check('Escape closes viewer', (await lb()).open === false);

await ev(`document.querySelector('[data-gallery] li:nth-child(4) .photo-button').click()`); await b.wait(300);
check('4th gallery tile opens 5 / 10', (await lb()).counter === '5 / 10');
await ev(`document.querySelector('[data-lightbox-close]').click()`); await b.wait(150);
check('close button closes viewer', (await lb()).open === false);

// Visiting another model and coming back must not stack listeners or mix photo sets.
await ev(`location.hash = '#/houses/atlantic'`); await b.wait(600);
await ev(`location.hash = '#/houses/norway'`); await b.wait(600);
await ev(`location.hash = '#/houses/atlantic'`); await b.wait(600);
await ev(`document.querySelector('[data-gallery] li:nth-child(1) .photo-button').click()`); await b.wait(300);
s = await lb();
check('after visiting other models: viewer shows Atlantic set (2 / 7)', s.counter === '2 / 7' && s.src.includes('atlantic/'), JSON.stringify(s));
await ev(`history.back()`); await b.wait(600);
check('browser Back closes viewer and navigates', (await lb()).open === false && (await ev('location.hash')) === '#/houses/norway');

// ---------- Mobile ----------
await b.viewport(390, 844, true);
await b.goto(BASE + '#/houses/continental'); await loadAll();
const w = await ev('({ scroll: document.documentElement.scrollWidth, inner: innerWidth })');
check('mobile: no horizontal overflow on detail page with gallery', w.scroll <= 390 && w.inner === 390, JSON.stringify(w));
check('mobile: gallery is 2 columns', (await ev(`getComputedStyle(document.querySelector('[data-gallery]')).gridTemplateColumns.split(' ').length`)) === 2);
await ev(`document.querySelector('[data-gallery] li:nth-child(2) .photo-button').click()`); await b.wait(600);
const m = await ev(`(() => { const i = document.querySelector('.lightbox__image').getBoundingClientRect(); const n = document.querySelector('[data-lightbox-next]').getBoundingClientRect(); const c = document.querySelector('.lightbox__caption').getBoundingClientRect(); return { imgRight: Math.round(i.right), captionBottom: Math.round(c.bottom), nextTop: Math.round(n.top), w: innerWidth }; })()`);
check('mobile: viewer photo fits width and arrows sit below the caption', m.imgRight <= m.w && m.nextTop >= m.captionBottom, JSON.stringify(m));
if (SHOTS) await b.screenshot(`${SHOTS}/g-lightbox-mobile.png`, false);
await key('Escape', 27);
if (SHOTS) await b.screenshot(`${SHOTS}/g-detail-mobile.png`);

if (SHOTS) { await b.viewport(1366, 900); await b.goto(BASE + '#/houses/norway'); await loadAll(); await b.screenshot(`${SHOTS}/g-detail-desktop.png`); }

check('no JavaScript errors in console', b.errors.length === 0, b.errors.join(' | '));
await b.close();
process.exit(summary() ? 1 : 0);
