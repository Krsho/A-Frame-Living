/*
 * Runs every browser test suite (*.test.mjs) in this folder.
 *
 *   node tests/e2e/run-all.mjs              run all suites
 *   node tests/e2e/run-all.mjs phase-c      run only suites whose name contains "phase-c"
 *
 * It starts a local web server for frontend/ and a headless Edge or Chrome,
 * runs the suites one after another, prints a summary and stops both again.
 * Optional environment variables:
 *   BROWSER_PATH  full path to Edge/Chrome if it is not found automatically
 *   SCREENSHOTS   folder to save screenshots into (skipped when not set)
 */

import { spawn, spawnSync } from 'node:child_process';
import { existsSync, mkdtempSync, readdirSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const frontendDir = resolve(here, '../../frontend');
// Uncommon ports, so a server you already have running on 8000 does not interfere.
const SERVER_PORT = 8123;
const DEBUG_PORT = 9333;
const BASE_URL = `http://127.0.0.1:${SERVER_PORT}/`;

const filter = process.argv[2] ?? '';
const suites = readdirSync(here)
  .filter((name) => name.endsWith('.test.mjs') && name.includes(filter))
  .sort();

const wait = (ms) => new Promise((r) => setTimeout(r, ms));

function findOnPath(names) {
  const finder = process.platform === 'win32' ? 'where' : 'which';
  for (const name of names) {
    const result = spawnSync(finder, [name], { encoding: 'utf8' });
    if (result.status === 0) return result.stdout.split(/\r?\n/)[0].trim();
  }
  return null;
}

function findBrowser() {
  if (process.env.BROWSER_PATH) return process.env.BROWSER_PATH;
  const candidates = {
    win32: [
      'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
      'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
      'C:/Program Files/Google/Chrome/Application/chrome.exe',
      'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    ],
    darwin: [
      '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
      '/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge',
    ],
  }[process.platform] ?? [];
  return candidates.find(existsSync)
    ?? findOnPath(['google-chrome', 'chromium', 'chromium-browser', 'microsoft-edge']);
}

function findPython() {
  for (const name of ['python', 'python3', 'py']) {
    if (spawnSync(name, ['--version']).status === 0) return name;
  }
  return null;
}

async function waitFor(url, label, timeoutMs = 15000) {
  const start = Date.now();
  while (Date.now() - start < timeoutMs) {
    try {
      if ((await fetch(url)).ok) return;
    } catch {
      // not ready yet
    }
    await wait(200);
  }
  throw new Error(`${label} did not start (${url})`);
}

function stop(child) {
  if (!child || child.exitCode !== null) return;
  if (process.platform === 'win32') {
    // Browsers start several processes; /T stops the whole tree.
    spawnSync('taskkill', ['/pid', String(child.pid), '/T', '/F'], { stdio: 'ignore' });
  } else {
    child.kill('SIGTERM');
  }
}

function runSuite(file) {
  return new Promise((resolveSuite) => {
    const child = spawn(process.execPath, [join(here, file)], {
      env: { ...process.env, BASE_URL, DEBUG_PORT: String(DEBUG_PORT) },
    });
    let output = '';
    child.stdout.on('data', (chunk) => { output += chunk; process.stdout.write(chunk); });
    child.stderr.on('data', (chunk) => { output += chunk; process.stderr.write(chunk); });
    child.on('close', (code) => {
      const match = output.match(/(\d+)\/(\d+) checks passed/);
      resolveSuite({ file, code, passed: match ? Number(match[1]) : 0, total: match ? Number(match[2]) : 0 });
    });
  });
}

const browserPath = findBrowser();
const python = findPython();
if (!browserPath) throw new Error('No Edge or Chrome found. Set BROWSER_PATH to its full path.');
if (!python) throw new Error('Python was not found (needed for the local web server).');
if (suites.length === 0) throw new Error(`No test suites match "${filter}".`);

const profileDir = mkdtempSync(join(tmpdir(), 'aframe-e2e-'));
let server;
let browser;
const results = [];

try {
  server = spawn(python, ['-m', 'http.server', String(SERVER_PORT), '--bind', '127.0.0.1', '--directory', frontendDir], { stdio: 'ignore' });
  await waitFor(BASE_URL, 'Web server');

  browser = spawn(browserPath, [
    '--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-first-run', '--no-default-browser-check',
    `--remote-debugging-port=${DEBUG_PORT}`, `--user-data-dir=${profileDir}`, 'about:blank',
  ], { stdio: 'ignore' });
  await waitFor(`http://127.0.0.1:${DEBUG_PORT}/json/version`, 'Browser');

  for (const file of suites) {
    console.log(`\n=== ${file} ===`);
    results.push(await runSuite(file));
  }
} finally {
  stop(browser);
  stop(server);
  await wait(500);
  try { rmSync(profileDir, { recursive: true, force: true }); } catch { /* browser may still hold files */ }
}

console.log('\n=== Summary ===');
for (const r of results) {
  console.log(`${r.code === 0 ? 'PASS' : 'FAIL'}  ${r.file.padEnd(24)} ${r.passed}/${r.total}`);
}
const failedSuites = results.filter((r) => r.code !== 0).length;
const passed = results.reduce((sum, r) => sum + r.passed, 0);
const total = results.reduce((sum, r) => sum + r.total, 0);
console.log(`\n${passed}/${total} checks passed in ${results.length} suites` + (failedSuites ? ` – ${failedSuites} suite(s) failed` : ''));
process.exit(failedSuites ? 1 : 0);
