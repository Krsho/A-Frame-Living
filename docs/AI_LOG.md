# AI Usage Log

This file records how AI tools were used in the A-Frame Living project. Entries should describe what actually happened, including prompts, what the AI produced, mistakes, and how they were corrected.

---

## Milestone 1: Initial Setup, Static Frontend and Database Planning

### Summary

**Tool:** Claude Code (model: Claude Opus 5.5), used as an extension in VS Code. It read and edited the files in this repository, ran commands, and tested the site in a headless Microsoft Edge browser.

**What the AI was used for:**
- creating the folder structure and first drafts of `.gitignore`, `README.md`, `AGENTS.md` and this log;
- writing the HTML, CSS and JavaScript of the static single-page application, based on my requirements and my data;
- writing the browser test suites in `tests/e2e/` and running them after each change;
- drafting the database plan and ERD (`docs/database-plan.md`), which I reviewed;
- reviewing diffs before commits.

**What I did myself:**
- Provided all business facts:
  - the four models;
  - their areas, rooms and features;
  - ROH BAU and Turnkey prices;
  - footprints (Atlantic and Norway 5 × 7 m, Scandinavia 9 × 7 m with a 3.6 m side room, Continental 14 × 8 m);
  - what each package includes;
  - the 14 completed locations and which model was built where.
- Supplied and organised all 45 photos, including the hero and about images, into one folder per model.
- Wrote the core of the company story ("We have been leading people through one of the most stressful processes in their lives…").
- Made the decisions:
  - use my own photos instead of stock images;
  - a data-driven filter;
  - no "visualisation" labels on renders;
  - keep the tests in the repository;
  - OpenStreetMap Nominatim as the external API;
  - Mermaid for the ERD;
  - keep four contact-form fields.
- Corrected values the AI had to fill in:
  - the real Continental footprint replaced an invented one;
  - all remaining locations are Norway models.
- Reviewed the result of every phase before continuing, and made the commits and pushes (`a216978`, `483937b`).
- Pasted the official assignment brief so the AI could check the Milestone 1 requirements.

**Most important prompts (summaries):**
1. Inspect the empty repository and create the Milestone 1 structure, `.gitignore`, README draft, `AGENTS.md` and `docs/AI_LOG.md`; no code or packages yet.
2. Build the static frontend as a responsive SPA (Home, catalogue, model details, locations, how to buy, contact, FAQ, login/registration, profile/admin):
   - premium design with off-white, wood and forest green;
   - no invented prices or specifications;
   - work in small steps and test the homepage before continuing.
3. Phase B: catalogue, detail pages, bedroom/feature filters and comparison table, using the photos I placed in `frontend/assets/` and matching them to models by file name, without modifying them.
4. "Let's finish all the TODOs, one by one. I added more images for each model in a folder. Add that and then ask me to finish the TODOs one by one."
5. Phase C: Locations, How to buy, FAQ and Contact pages, with frontend validation and only a simulated submission.
6. Before committing: update the README/AGENTS.md, log Phase C truthfully, check the Milestone 1 requirements, run all tests and `git diff --check`.
7. Finish Milestone 1: database plan with at least 5 entities and a draft ERD; check the brief before building login/register/profile/admin; add truthful log entries for the earlier phases.

**Cases where the AI was wrong or produced poor code** (more details in the entries below):

| # | What was wrong | How it was noticed | How it was fixed |
|---|---|---|---|
| 1 | Photo placeholders on the model cards: the "Photo coming soon" text overlapped the drawing and was underlined, because it sat inside a link. | Screenshot review after Phase A | Changed the placeholder layout to a flex column with the drawing above the text, and removed the underline |
| 2 | After a change, the test run reported "all passed" although the browser was still running the **old** JavaScript. The test helper only changed the `#…` part of the URL, which does not reload the page. | The screenshot did not show the change. A check showed the new code was not active | The test helper now loads `about:blank` before every page and disables the cache |
| 3 | The AI attached the photo-viewer click listener to `<main>`, which stays on screen between pages. Visiting several models would stack listeners and could open the wrong model's photos. | The AI spotted it while reviewing its own code, before running it | Listeners are now attached to elements inside the current view. A test visits several models and checks that the right photo set opens |
| 4 | The AI made up a footprint for Continental (8 × 11 m) because none had been given yet. | I gave the real value | Changed to 14 × 8 m. Invented values are now marked with a comment |
| 5 | The mobile menu stayed open when tapping the link of the page you were already on. This bug was in the Phase A code. | A new navigation test in Phase C failed | The menu now closes on every link click (`app.js`) |
| 6 | Elements with `display: grid` ignored the `hidden` attribute, so the contact form would not hide after sending. | Found while building the contact page | A global `[hidden] { display: none !important; }` rule |
| 7 | The test suite crashed only in the full run: all suites shared one browser tab, which keeps at most 50 history entries, so the Back-button tests broke. | The suite passed alone but failed in the full run. A temporary probe showed the history length was stuck at 50 | Every suite now opens its own tab |
| 8 | A CSS rule gave **every** disabled button a "busy" cursor. | Final diff review before committing Phase C | Limited to buttons inside a form that is sending |
| 9 | On phones, the Admin and Profile pages were wider than the screen (669 px and 586 px), so the browser zoomed them out. The mobile tests had not caught it: they compared the page width with `innerWidth`, which also grows when the browser zooms out. | A full-page phone screenshot was wider than 390 px | A hidden table label escaped the scroll box (`.table-scroll` now has `position: relative`), and the profile grid columns got `min-width: 0`. The tests now require exactly 390 px |

---

### Entry 1: Initial project scaffolding

- **Date:** 2026-10-25
- **Tool:** Claude Code (Claude Opus 5.5), VS Code extension
- **Prompt (summary):** Inspect the empty repository. Create the Milestone 1 folder structure (`frontend/css, js, views, assets`; `backend/routes, services, dao, middleware, config, logs`), plus `.gitignore`, a draft `README.md`, `AGENTS.md`, and this log. No packages, database tables, routes, or website code.
- **What the AI did:**
  - Inspected the repository. It was empty, with no files and no commits on branch `milestone1`.
  - Created the folders, each with an empty `.gitkeep` file so Git tracks them.
  - Created `.gitignore`, `README.md` (draft with TODO sections), `AGENTS.md`, and `docs/AI_LOG.md`.
- **What was NOT done:** No packages installed, no database created, no routes or website code written.
- **Mistakes / corrections:** No problems found when I reviewed the files.
- **My review / changes:** I checked the generated folders and files and committed them myself (`f6cfedc`).

### Entry 2: Phase A – page shell, SPA router and homepage

- **Date:** 2026-10-25
- **Tool:** Claude Code (Claude Opus 5.5), VS Code extension
- **Prompt (summary):** Build the static frontend as a responsive SPA, with no full page reloads:
  - views: Home, catalogue, details, locations, how to buy, contact, FAQ, login/registration, profile/admin;
  - design: premium, with warm off-white, wood tones and forest green;
  - no invented prices, dimensions, contact details or locations;
  - first propose a file structure, then build the homepage and navigation and test them before continuing.
- **Decisions and input from me:**
  - The AI asked for business information. I provided the areas, rooms, features, ROH BAU and Turnkey prices for all four models, the 14 locations, and which models were built where.
  - I chose to use my own photos (so placeholders were shown until I added them) and a data-driven feature filter.
- **What the AI did:**
  - Proposed and created the frontend structure:
    - `index.html`;
    - `css/base.css`, `components.css`, `views.css`;
    - a hash router (`js/router.js`);
    - reusable components (`js/components.js`);
    - data files (`js/data/houses.js`, `locations.js`);
    - the Home and "not found" views.
  - Added a placeholder that replaces any missing photo.
  - Tested with headless Edge screenshots and a small interaction script (23 checks: navigation without reloads, Back button, mobile menu, no horizontal scrolling).
- **Mistakes and corrections:**
  - Placeholder text overlapped the drawing on the cards and was underlined. Seen in a screenshot; the layout was fixed.
  - The hero heading broke inside "A-frame" ("A- / frame"). Seen in a screenshot; fixed with a `nowrap` span.
  - The AI first suspected that the mobile page overflowed sideways. It turned out to be a limitation of the headless browser's window size, so the AI switched to proper mobile viewport emulation to check it.
- **Result:** The AI stopped after Phase A as I asked. I reviewed it and committed `a216978`.
- **My review / changes:** I gave the AI all business data (model sizes, rooms, features, prices and the 14 locations) and decided to use my own photos instead of stock images. I reviewed the homepage and navigation before letting it continue, and committed `a216978`.

### Entry 3: Phase B – catalogue, detail pages, filters and comparison

- **Date:** 2026-10-25
- **Tool:** Claude Code (Claude Opus 5.5), VS Code extension
- **Prompt (summary):** Implement only the catalogue, the four detail routes, a bedroom filter, feature filters based on confirmed data, and a comparison table:
  - use the photos I placed in `frontend/assets/` and match them to models by file name, without modifying them;
  - use `houses.js` as the single source of truth;
  - test, report and stop.
- **Input from me:** I added the four main photos (`atlantic.jfif`, `norway.jfif`, `scandinavia.jfif`, `continental.jfif`).
- **What the AI did:**
  - Viewed the photos through temporary copies and wrote alt text for each.
  - Built the catalogue, the filters (options derived from the data), the comparison table and the detail-page template.
  - Added `?section=` scrolling to the router.
  - Extended the tests to 83 checks.
  - Pointed out that the images look like renders rather than photos of finished houses. I later decided not to label them.
- **Mistakes and corrections:**
  - The AI added a `.media--3x2` CSS rule that already existed. It noticed right away and removed the duplicate.
  - Prices were not aligned across the four cards. Seen in a screenshot; moved to the bottom of each card.
  - After that change, a re-run passed against the old, still-loaded JavaScript (case 2 in the summary). The test helper was fixed and the tests were re-run on the new code.
- **My review / changes:** I added the four main photos and limited the task to Phase B only, with a stop for review. I reviewed the catalogue, filters and detail pages before the next step.

### Entry 4: Galleries and filling in the remaining TODOs

- **Date:** 2026-10-25
- **Tool:** Claude Code (Claude Opus 5.5), VS Code extension
- **Prompt (summary):** "Let's finish all the TODOs, one by one. I added the images for each model in a folder corresponding to the model. Add that and then ask me to finish the TODOs one by one."
- **Input from me:**
  - Organised 43 photos into one folder per model, and added `hero.jfif` and `about.jfif`.
  - Wrote the core of the company story.
  - Gave the footprints (Atlantic and Norway 5 × 7 m; Scandinavia 9 × 7 m with a 3.6 m wide side room; later Continental 14 × 8 m) and what ROH BAU and Turnkey include.
  - Said that all remaining locations are Norway models.
  - Chose not to label rendered images, and allowed invented wording where real details don't matter for the assignment.
- **What the AI did:**
  - Made contact sheets to check every image.
  - Fixed the main-photo paths, which broke when I moved the photos into folders. Checked all 43 paths for exact upper and lower case.
  - Built the detail-page galleries and a full-screen photo viewer (`js/lightbox.js`).
  - Added the Home images.
  - Turned my story text into two paragraphs.
  - Stored dimensions as structured data.
  - Added package descriptions and package cards.
  - Updated the locations.
  - Asked me about each TODO in turn.
- **Mistakes and corrections:**
  - Click listeners attached to `<main>` would have stacked between pages (case 3 in the summary). Fixed before testing.
  - The AI's invented Continental footprint (8 × 11 m) was replaced with my real value, 14 × 8 m (case 4).
  - In the first draft of the About section, "Welcome to A-Frame Living" appeared in the middle. Seen in a screenshot; reordered.
  - Some temporary test scripts had escaping mistakes (a regular expression lost its backslash), which produced false failures. Those were bugs in the tests, not the site, and were fixed.
- **Result:** I reviewed the result, then committed and pushed `483937b`.
- **My review / changes:** I organised all photos into model folders and answered each TODO with real data: footprints, package contents and locations. I replaced the AI's invented Continental footprint with the real one (14 × 8 m), decided not to label rendered images, and committed and pushed `483937b`.

### Entry 5: Phase C – Locations, How to buy, FAQ and Contact pages

- **Date:** 2026-10-25
- **Tool:** Claude Code (Claude Opus 5.5), VS Code extension
- **Prompt (summary):**
  - Implement Phase C on the current branch without committing or pushing.
  - Build four public pages in the existing SPA:
    - Locations (cards and grid from the existing data);
    - How to buy (customer journey steps, ROH BAU vs Turnkey);
    - FAQ (accessible expandable items covering models, prices, packages, customization, locations and buying);
    - Contact (name, email, subject, message, with required-field and email validation, clear success/error feedback, and a *simulated* submission organised so it can later connect to the PHP REST API).
  - Reuse the existing components and styles.
  - Fictional company content is acceptable (school assignment).
  - Run all existing tests, add tests for the new pages, fix regressions, and report files, results and limitations.
- **Decision asked during planning:** The AI asked where the browser tests should live, since until then they had existed only in the AI's temporary folder. I chose to add them to the repository (`tests/e2e/`).
- **What the AI did:**
  - Moved the package data into `frontend/js/data/packages.js` and added a "who does what" table (`packageScope`) based on my package descriptions.
  - Added `region` and `setting` to `locations.js` (settings written for the assignment), plus `countByModel()` and `mapUrl()`.
  - Added `company.js` (fictional contact details: `.example` email, placeholder phone) and `faq.js`. FAQ answers about prices, packages and locations are generated from the data files.
  - Built the four pages (`frontend/views/*.html` + `frontend/js/views/*.js`) and registered their routes in `app.js`.
  - Added `frontend/js/validation.js` (reusable rules, inline errors, error summary, `aria-invalid`) and `frontend/js/services/inquiries.js`. The latter only simulates sending and contains the future `fetch` call as a comment.
  - Moved the browser tests into `tests/e2e/`, added `run-all.mjs` (starts a local server and a headless browser) and a README, and wrote a new suite, `phase-c.test.mjs`.
  - Added short test sections to `README.md` and `AGENTS.md`.
- **Mistakes found and how they were corrected:**
  1. **Filter count numbers on the Locations page showed up as chips inside the chips.** The CSS selector `.chip-option span` also matched the inner count span. Fixed by changing it to `.chip-option > span`. Found from a screenshot.
  2. **FAQ layout.** The AI's first FAQ CSS used `margin-left: 0` on the page container, which would have pushed the FAQ to the left edge on wide screens. The AI noticed this before testing and replaced it with a width limit on the content.
  3. **The `hidden` attribute did not hide elements styled with `display: grid`.** The contact form would have stayed visible after sending; the existing catalogue grid had the same issue. Fixed with a global `[hidden] { display: none !important; }` rule in `base.css`.
  4. **Existing bug found by a new test:** in the mobile menu, tapping the link of the page you are already on left the menu open, because the URL did not change. Fixed in `app.js` by closing the menu whenever a link inside it is clicked.
  5. **Test suite crashed only in the full run.** All suites shared one browser tab, and browsers keep at most 50 history entries per tab, so the Back-button tests failed late in the run. The AI diagnosed this with a temporary probe file, which it deleted afterwards. Fixed by giving every suite its own new tab (`cdp.mjs`).
  6. **Headings broke inside the word "A-frame".** Two new headings broke at the hyphen on desktop ("A- / frame"). Fixed with the existing `.nowrap` class.
  7. **Outdated test check.** An older Phase A test used `#/faq` as an example of an unbuilt page. It was changed to `#/login`, which was still unbuilt at that time. In Entry 7 it was updated again, once the Log in page existed.
- **Result reported by the AI:** 260/260 checks in 5 suites passed in two consecutive full runs.
- **What was NOT done:** No backend, no database, no real sending of messages, no commit or push. Log in, Register, Profile and Admin views were not part of this phase.
- **My review / changes:** I wrote the Phase C requirements, decided that the tests belong in the repository, and reviewed the report of files, tests and limitations.

### Entry 6: Documentation update and final review before committing Phase C

- **Date:** 2026-10-25
- **Tool:** Claude Code (Claude Opus 5.5), VS Code extension
- **Prompt (summary):**
  - Update `README.md` and `AGENTS.md` to describe the current project accurately.
  - Log the Phase C work in this file without fabricating anything, keeping the Milestone 1 entries.
  - Check the Milestone 1 requirements against the repository (especially a database plan with at least five entities and a draft ERD).
  - Run the full test suite and `git diff --check`, and inspect the diff for accidental changes.
  - Do not commit, push or switch branches.
- **What the AI did:**
  - Rewrote `README.md`: status, features, page and route table, architecture, structure, how to run the site and the tests, where to edit content, and a milestone checklist.
  - Updated `AGENTS.md`:
    - architecture, frontend file map, how to add a page and current status;
    - a rule that owner-provided facts stay exact and fictional content must be marked;
    - all original rules and backend sections kept.
  - Added the Phase C and review entries. Entry 1 was left unchanged.
  - Checked the diff: syntax of all JS files, multi-line and single-line imports, duplicated data (packages are defined only in `packages.js`), duplicated CSS rule blocks (none), leftover temporary files (none). `git diff --check` reported no whitespace errors.
- **Mistake found and corrected:** a Phase C CSS rule (`.btn:disabled { cursor: progress; }`) gave *every* disabled button a "busy" cursor and overrode the earlier "not allowed" cursor. It was narrowed to `.form[aria-busy="true"] .btn:disabled`.
- **Requirements check (summary, at that time):**
  - Frontend pages, structure and docs were in place.
  - Missing:
    - the database plan with at least five entities and a draft ERD;
    - static Log in/Register views;
    - static User profile and Admin dashboard views.
  - The official assignment text was not in the repository yet, so other requirements could not be confirmed.
- **Result:** after the CSS fix, the full run passed 260/260 checks in 5 suites (exit code 0).
- **My review / changes:** I asked for a full review before committing. Based on the requirements report, I decided the next steps: database plan first, check the brief before building more pages, keep the four contact fields.

### Entry 7: Completing Milestone 1 – database plan, ERD, login/register/profile/admin

- **Date:** 2026-10-26
- **Tool:** Claude Code (Claude Opus 5.5), VS Code extension
- **Prompt (summary):**
  - Create a documented database plan with at least five entities and a draft ERD.
  - Check the exact Milestone 1 requirements before building the login, register, profile and admin pages, and keep the contact form at four fields unless required.
  - Add truthful log entries for Phase A, Phase B and the TODO round, making my own contributions visible.
- **Input from me:**
  - Pasted the official assignment brief.
  - Chose OpenStreetMap Nominatim (map of completed projects) as the external API, and Mermaid in Markdown for the ERD.
- **What the AI did:**
  - Compared the brief with the repository. Register/login and the other relevant pages (profile, dashboard) are required for the Milestone 1 static frontend, and so is a planned place for the external API feature.
  - Wrote `docs/database-plan.md`:
    - 10 tables (`users`, `house_models`, `packages`, `model_prices`, `features`, `model_features`, `model_images`, `locations`, `projects`, `inquiries`);
    - relationships, keys and constraints;
    - roles and a CRUD-per-role table;
    - the Nominatim plan;
    - draft REST endpoints;
    - a Mermaid ERD. The AI checked it renders by drawing it in a headless browser: 10 entity boxes, no errors.
  - Added static views:
    - **Log in** and **Register**, with validation including password rules, repeat password, terms checkbox and optional phone, plus demo notices;
    - **Profile** and the **Admin dashboard**, with one table per entity, all actions disabled, and example data in `js/data/demo.js`;
    - a map placeholder on the Locations page.
  - Extended `validation.js` with `pattern` and `matches` and checkbox support, and added a `statusBadge` component.
  - Added `tests/e2e/phase-d.test.mjs` (47 checks) and updated the Phase A check that used `#/login` as an unbuilt page.
  - Updated `README.md` (entities, roles, external API, the TODO links for Swagger and deployment) and `AGENTS.md` (naming conventions, database section, status).
  - Restructured this log into the per-milestone format.
- **Mistakes and corrections:**
  - The AI used inline `style` attributes for the map pins, against the "no inline styles" rule in `AGENTS.md`. Noticed in its own review; moved to CSS.
  - A mobile-first CSS rule for the admin stats was placed after its media query, so it would have overridden it. Noticed in review; reordered.
  - On Register, the "Repeat password" box sat lower than "Password" because of the hint text in the neighbouring field. Seen in a screenshot; fixed with `align-content: start` on form fields.
  - Admin and Profile were wider than a phone screen, and the mobile tests did not catch it (case 9 in the summary). Fixed, and the tests were made stricter.
- **Result:** all 6 suites passed, 307/307 checks.
- **My review / changes:** I pasted the official brief, chose OpenStreetMap Nominatim and a Mermaid ERD, and went through the final steps (authors, review notes, `AGENTS.md`) together with the AI before committing.

<!-- Copy the entry template below for each new AI interaction. -->

<!--
### Entry N: <title>

- **Date:**
- **Tool:**
- **Prompt (summary):**
- **What the AI did:**
- **Mistakes / corrections:**
- **My review / changes:**
-->
