import { connect, makeChecker, BASE, SHOTS } from './cdp.mjs';
const b = await connect();
const { check, summary } = makeChecker();
const report = () => b.evaluate(`(async () => {
  for (let y = 0; y < document.body.scrollHeight; y += 300) { scrollTo(0, y); await new Promise(r => setTimeout(r, 50)); }
  await Promise.all([...document.images].map(i => i.complete ? null : new Promise(r => { i.onload = i.onerror = r; })));
  scrollTo(0, 0);
  const info = (sel) => { const i = document.querySelector(sel); const r = i.getBoundingClientRect(); return { src: i.getAttribute('src'), ok: i.complete && i.naturalWidth > 0, alt: i.alt, box: +(r.width / r.height).toFixed(2), fit: getComputedStyle(i).objectFit }; };
  return { hero: info('.hero__media img'), about: info('.split img'), placeholders: document.querySelectorAll('#app .img-placeholder').length, w: document.documentElement.scrollWidth, iw: innerWidth };
})()`);
for (const [w, h, mobile] of [[1366, 900, false], [390, 844, true]]) {
  await b.viewport(w, h, mobile);
  await b.goto(BASE + '#/');
  const r = await report();
  const tag = mobile ? 'mobile' : 'desktop';
  check(`${tag}: hero.jfif loaded`, r.hero.ok && r.hero.src === 'assets/images/hero.jfif' && r.hero.fit === 'cover', JSON.stringify(r.hero));
  check(`${tag}: about.jfif loaded`, r.about.ok && r.about.src === 'assets/images/about.jfif' && r.about.fit === 'cover', JSON.stringify(r.about));
  check(`${tag}: no placeholders left on Home`, r.placeholders === 0);
  check(`${tag}: no horizontal overflow`, r.w <= w && r.iw === w, `scroll=${r.w} inner=${r.iw}`);
  if (SHOTS) await b.screenshot(`${SHOTS}/home-${tag}.png`);
}
check('no JavaScript errors', b.errors.length === 0, b.errors.join(' | '));
await b.close();
process.exit(summary() ? 1 : 0);
