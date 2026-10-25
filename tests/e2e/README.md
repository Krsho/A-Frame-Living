# Browser tests (end-to-end)

These tests open the real website in a headless browser and click through it the way a visitor would: navigating, filtering, opening the photo viewer, filling in the contact form, and so on. They also check the mobile layout.

No npm packages are needed. The tests talk to the browser through the Chrome DevTools Protocol, using what is built into Node.js.

## Requirements

- **Node.js 22 or newer** (for the built-in `WebSocket`)
- **Python 3** (serves the `frontend/` folder as a local website)
- **Microsoft Edge or Google Chrome**

## Running

From the project root:

```
node tests/e2e/run-all.mjs            # all suites
node tests/e2e/run-all.mjs phase-c    # only suites whose file name contains "phase-c"
```

The runner:
1. starts a local web server for `frontend/` on port 8123;
2. starts a headless browser on debugging port 9333;
3. runs each `*.test.mjs` file in a fresh browser tab;
4. prints a summary and stops the server and browser.

It exits with code 1 if any check fails.

Optional environment variables:

| Variable | Purpose |
|---|---|
| `BROWSER_PATH` | Full path to Edge/Chrome, if it is not found automatically |
| `SCREENSHOTS` | A folder to save screenshots into (desktop and mobile), useful for checking the layout by eye |

PowerShell example: `$env:SCREENSHOTS = "C:\temp\shots"; node tests/e2e/run-all.mjs`

## Suites

| File | What it covers |
|---|---|
| `phase-a.test.mjs` | Home page, SPA navigation without reloads, Back button, "page not found", mobile menu |
| `home-images.test.mjs` | Home page photos load on desktop and mobile |
| `phase-b.test.mjs` | Model catalogue, bedroom/feature filters, comparison table, the four detail pages, package cards |
| `gallery.test.mjs` | Detail-page galleries and the full-screen photo viewer (buttons, arrow keys, Escape) |
| `phase-c.test.mjs` | Locations (filter, cards, map links), How to buy (steps, package table), FAQ (mouse and keyboard), Contact form (validation, simulated sending, error state), navigation across all pages, mobile layout |
| `phase-d.test.mjs` | Log in and Register (validation, password rules, demo notices), User profile and Admin dashboard (example data, one table per entity, disabled actions), map placeholder on Locations, mobile layout |

## Files

- `run-all.mjs`: starts everything and runs the suites
- `cdp.mjs`: small helper for controlling the browser, plus the `check()` / `summary()` functions
- `*.test.mjs`: the test suites; each prints `PASS`/`FAIL` lines and `X/Y checks passed`

## Writing a new check

```js
import { connect, makeChecker, BASE } from './cdp.mjs';
const b = await connect();
const { check, summary } = makeChecker();

await b.goto(BASE + '#/faq');
check('faq page has a heading', (await b.evaluate(`document.querySelector('h1').textContent`)) === 'Frequently asked questions');

await b.close();
process.exit(summary() ? 1 : 0);
```
