/*
 * Minimal Chrome DevTools Protocol helper for the browser tests.
 * No npm packages: uses Node's built-in fetch and WebSocket (Node 22+).
 * run-all.mjs starts the web server and a headless Edge/Chrome first.
 */
import { writeFileSync, mkdirSync } from 'node:fs';

// Settings shared by all suites (run-all.mjs sets them; defaults work too).
export const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8000/';
export const SHOTS = process.env.SCREENSHOTS || null; // folder for screenshots, or null to skip them
const DEBUG_PORT = Number(process.env.DEBUG_PORT ?? 9222);
if (SHOTS) mkdirSync(SHOTS, { recursive: true });

export async function connect(port = DEBUG_PORT) {
  // Each suite gets its own fresh tab. Sharing one tab would build up a long
  // history (browsers keep at most 50 entries), which breaks Back/Forward tests.
  const page = await (await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, { method: 'PUT' })).json();
  const ws = new WebSocket(page.webSocketDebuggerUrl);
  await new Promise((r) => ws.addEventListener('open', r, { once: true }));
  let id = 0;
  const pending = new Map();
  const errors = [];
  ws.addEventListener('message', (e) => {
    const msg = JSON.parse(e.data);
    if (msg.id && pending.has(msg.id)) {
      const { resolve, reject } = pending.get(msg.id);
      pending.delete(msg.id);
      msg.error ? reject(new Error(msg.error.message)) : resolve(msg.result);
    } else if (msg.method === 'Runtime.exceptionThrown') {
      errors.push(msg.params.exceptionDetails.exception?.description ?? msg.params.exceptionDetails.text);
    } else if (msg.method === 'Runtime.consoleAPICalled' && msg.params.type === 'error') {
      errors.push(msg.params.args.map((a) => a.value ?? a.description).join(' '));
    }
  });
  const send = (method, params = {}) =>
    new Promise((resolve, reject) => {
      const msgId = ++id;
      pending.set(msgId, { resolve, reject });
      ws.send(JSON.stringify({ id: msgId, method, params }));
    });
  await send('Runtime.enable');
  await send('Page.enable');

  const evaluate = async (expression) => {
    const res = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (res.exceptionDetails) throw new Error(res.exceptionDetails.exception?.description ?? res.exceptionDetails.text);
    return res.result.value;
  };
  const wait = (ms) => new Promise((r) => setTimeout(r, ms));
  const viewport = (width, height, mobile = false) =>
    send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile });
  const goto = async (url) => {
    // Go via about:blank so each goto is a real page load (a hash-only change would keep old JS).
    await send('Page.navigate', { url: 'about:blank' }); await wait(150);
    await send('Page.navigate', { url }); await wait(1200);
  };
  await send('Network.enable'); await send('Network.setCacheDisabled', { cacheDisabled: true });
  const screenshot = async (path, fullPage = true) => {
    let clip;
    if (fullPage) {
      const { cssContentSize } = await send('Page.getLayoutMetrics');
      clip = { x: 0, y: 0, width: cssContentSize.width, height: Math.min(cssContentSize.height, 6000), scale: 1 };
    }
    const { data } = await send('Page.captureScreenshot', { format: 'png', clip, captureBeyondViewport: true });
    writeFileSync(path, Buffer.from(data, 'base64'));
  };
  const close = async () => {
    ws.close();
    await fetch(`http://127.0.0.1:${port}/json/close/${page.id}`).catch(() => {});
  };
  return { send, evaluate, wait, viewport, goto, screenshot, errors, close };
}

// Tiny assertion collector
export function makeChecker() {
  const results = [];
  const check = (name, ok, detail = '') => {
    results.push({ name, ok: Boolean(ok), detail });
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? `  (${detail})` : ''}`);
  };
  const summary = () => {
    const failed = results.filter((r) => !r.ok).length;
    console.log(`\n${results.length - failed}/${results.length} checks passed`);
    return failed;
  };
  return { check, summary };
}
