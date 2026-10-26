# AGENTS.md

Instructions for AI coding assistants working on **A-Frame Living**, a university web programming project. Read this file before making changes.

## Project Description

A-Frame Living is a web application for a company that builds A-frame houses in Bosnia and Herzegovina. It is for people building a family home, buyers looking for a holiday or weekend house, people from the diaspora who want to build back home, and investors who want to rent A-frames to tourists. The main goal is that a visitor can compare the four models (Atlantic, Norway, Scandinavia and Continental) and their ROH BAU and Turnkey prices, and then send an inquiry about one. Once the backend exists, customers will follow their own inquiries and edit their profile, and the admin will manage models, prices, photos, locations, projects, inquiries and users.

## My Top Priorities

1. **Never commit or push.** I review and commit myself.
2. **Never delete or overwrite files** – especially my photos – without asking first.
3. **Explain every change** in simple, beginner-friendly language.

## Working Rules

- The developer is a **beginner**. Explain changes in plain language.
- **Inspect before changing.** Read the existing files before you add or edit anything.
- **Never delete or overwrite existing files without asking first.** This includes the photos in `frontend/assets/images/`.
- Do only the task requested. Do not build ahead to future milestones.
- Do not install packages, create database tables, or add dependencies unless asked.
- Show the changes and wait for approval before moving to the next task. Never commit or push (see My Top Priorities).
- **Facts provided by the owner must stay exact:** model areas, rooms, features, prices, footprints and the completed locations. Do not guess business facts. Mark unknowns as `TODO`.
- This is a school assignment, so fictional content is allowed **when the owner says so**. Keep it general (no invented guarantees, deadlines or deposits) and mark it with a comment such as "written for the university assignment".
- Run the browser tests after every change (see [Tests](#tests)) and report only results you actually saw.
- Log significant AI assistance in `docs/AI_LOG.md` honestly. Record what actually happened, including mistakes and corrections.

## Technology Stack (required, do not substitute)

| Layer    | Technology                       | Not allowed                                   |
|----------|----------------------------------|-----------------------------------------------|
| Frontend | HTML, CSS, vanilla JavaScript    | React, Vue, Angular, jQuery, TypeScript, build tools |
| Backend  | PHP + FlightPHP                  | Laravel, Symfony, other frameworks            |
| Database | MySQL via PHP PDO                | mysqli, ORMs                                  |
| API      | REST, JSON request/response      |                                               |

## Architecture

- **Single-page application (SPA):** `frontend/index.html` loads once. The hash router (`frontend/js/router.js`) maps URLs like `#/houses/norway` to a route. It loads the HTML fragment from `frontend/views/`, inserts it into `<main>`, and calls the route's `init()` function. There are no full page reloads.
- **REST API backend (planned):** The backend returns JSON only. It never renders HTML pages for the frontend.
- **Strict separation:** Frontend code never touches the database or PHP directly. All communication goes through HTTP requests (`fetch`) to the API, inside `frontend/js/services/`.

### Frontend

| Path | Responsibility |
|---|---|
| `frontend/index.html` | The single page: header and navigation (`data-nav` attributes), `<main id="app">`, footer |
| `frontend/js/app.js` | Route table, mobile menu, active nav link |
| `frontend/js/router.js` | Hash router. Supports `:params`, `?query`, `?section=<id>` (scrolls to an element) and `{ notFound: true }` / `{ title }` returned from `init()` |
| `frontend/js/components.js` | Reusable HTML builders: `houseCard`, `priceBlock`, `packageCards`, `featureChips`, `statusBadge`, `image` (with placeholder fallback), `escapeHtml`, formatters |
| `frontend/js/validation.js` | Form rules (`required`, `email`, `minLength`, `maxLength`, `pattern`, `matches`) and `attachValidation()` |
| `frontend/js/lightbox.js` | Full-screen photo viewer (`<dialog>`) |
| `frontend/js/data/` | **Single source of truth** for content: `houses.js`, `packages.js`, `locations.js`, `faq.js`, `company.js` (later replaced by API calls). `demo.js` holds **example** users and inquiries for the static Profile/Admin preview only |
| `frontend/js/services/` | API layer. `inquiries.js` simulates `POST /inquiries` until the backend exists |
| `frontend/js/views/` | One script per page, exporting `init<Page>({ outlet, params, query })` |
| `frontend/views/` | One HTML fragment per page |
| `frontend/css/` | `base.css` (tokens, reset), `components.css` (shared UI), `views.css` (page-specific) |
| `frontend/assets/images/` | Photos, one folder per model (see its README) |

**Adding a page:** create `views/<name>.html` and `js/views/<name>.js` with an `init` function, then register the route in `app.js`. Add a test suite or checks in `tests/e2e/`.

### Backend layers (planned)

Requests flow in one direction:

```
HTTP request → middleware → routes → services → dao → MySQL
```

| Folder               | Responsibility                                                                 |
|----------------------|--------------------------------------------------------------------------------|
| `backend/middleware` | Runs before routes, for example authentication and request checks.             |
| `backend/routes`     | Defines FlightPHP endpoints. Reads the request, calls a service, returns JSON. No SQL here. |
| `backend/services`   | Business logic and validation. Calls DAOs. No direct SQL here.                 |
| `backend/dao`        | Data Access Objects. The **only** place SQL runs, always through PDO.          |
| `backend/config`     | Configuration. `config.php` is git-ignored; `config.example.php` is the committed template. |
| `backend/logs`       | Runtime logs. Contents are git-ignored.                                        |

### Tests

| Folder       | Responsibility                                                                 |
|--------------|--------------------------------------------------------------------------------|
| `tests/e2e`  | Browser tests (Node + headless Edge/Chrome, no npm packages). Run `node tests/e2e/run-all.mjs` after every change and keep all suites passing; add a suite or checks for new features. See `tests/e2e/README.md`. |

## Database

The planned schema (entities, columns, relationships, roles, draft ERD) is in `docs/database-plan.md`. Keep it up to date when tables change.

## Naming Conventions

| What | Convention | Example |
|---|---|---|
| Frontend files | kebab-case | `house-detail.js`, `how-to-buy.html` |
| Page script ↔ fragment | same name | `js/views/faq.js` ↔ `views/faq.html` |
| JS variables and functions | camelCase | `findHouse`, `priceBlock` |
| JS view entry points | `init` + PascalCase page name | `initHouseDetail`, `initAdmin` |
| CSS classes | block__element--modifier | `house-card__title`, `btn--primary` |
| JS hooks in HTML | `data-*` attributes (not classes) | `data-contact-form`, `data-nav` |
| Database tables / columns | snake_case, plural tables, `_id` for foreign keys | `house_models`, `model_id` |
| REST endpoints | plural nouns, no verbs | `GET /houses/{slug}`, `POST /inquiries` |
| PHP classes (planned) | PascalCase with a layer suffix | `HouseService`, `HouseDao`, `AuthMiddleware` |

## Coding Rules

### General
- Use clear, descriptive names. Keep functions small and focused.
- Comment code where the reason isn't obvious. The developer is learning.
- Never commit secrets. Credentials belong only in `backend/config/config.php`, which is git-ignored.
- Don't duplicate content: pages read from `frontend/js/data/` instead of repeating prices, specs or locations.

### PHP / Backend
- Always use **PDO prepared statements** with bound parameters. Never build SQL by concatenating user input.
- Return JSON with appropriate HTTP status codes (200, 201, 400, 401, 404, 500).
- Validate all input in the service layer.
- Hash passwords with `password_hash()` and check them with `password_verify()`.
- Don't show raw database errors to the client. Log them to `backend/logs/` instead.

### JavaScript / Frontend
- Vanilla JavaScript only (ES6+ modules). Use `fetch` for API calls, only inside `frontend/js/services/`.
- Use `const`/`let`, never `var`.
- Use `textContent` (or `escapeHtml()` in templates) for user-supplied or API data to prevent XSS.
- Keep the API base URL in one place so it is easy to change.
- Attach event listeners to elements inside the current view, not to `<main>` (which stays between pages).
- File names in image paths are case-sensitive on most servers. Write them exactly as on disk.

### CSS
- Keep styles in `frontend/css/`. Avoid inline styles. Use the tokens in `base.css`.
- Mobile first. Breakpoints at about 640 px and 960 px. Check that no page scrolls sideways at 390 px.
- Keep visible focus styles and labels on every form field.

## Current Status

- **Milestone 1:**
  - Built:
    - the static frontend SPA: Home, Models, Model details with gallery, Locations (with a placeholder for the planned map), How to buy, FAQ and Contact (simulated sending);
    - static previews of Log in, Register, Profile and the Admin dashboard;
    - browser tests (6 suites);
    - the database plan with a draft ERD.
- **Backend:** not started. `backend/` contains only empty folders. There are no database tables and no packages. The external API (OpenStreetMap Nominatim), JWT, middleware, logging and OpenAPI docs come in later milestones.
