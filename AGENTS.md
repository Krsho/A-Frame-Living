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
- **REST API backend (in progress):** The backend returns JSON only. It never renders HTML pages for the frontend.
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

### Backend layers

Built so far: `config/`, `dao/` (Milestone 2), `services/`, `routes/`, OpenAPI docs and the external API (Milestone 3). Middleware (JWT, roles, request logging) comes in Milestone 4. `backend/index.php` is the single entry point (loads classes, registers services with `Flight::register()`, maps the error handler, registers routes). Requests flow in one direction:

```
HTTP request → middleware → routes → services → dao → MySQL
```

| Folder               | Responsibility                                                                 |
|----------------------|--------------------------------------------------------------------------------|
| `backend/middleware` | Runs before routes, for example authentication and request checks (Milestone 4). |
| `backend/routes`     | One `<Entity>Routes` class per entity, extending `BaseRoutes`. `register()` maps URLs to static methods; each method reads the request, calls ONE service method and sends JSON (`self::send()`). No SQL and no business rules here. The `#[OA\...]` attributes above each method are its OpenAPI documentation; keep them in sync with the code. |
| `backend/services`   | One `<Entity>Service` per entity, extending `BaseService`. Validation (`Validator`) and business rules (`checkRules()`, `beforeDelete()`). The only layer that calls DAOs. Throws `HttpException` subclasses on errors. `GeocodingService` is the external API (Nominatim). |
| `backend/dao`        | Data Access Objects. The **only** place SQL runs, always through PDO. `BaseDao.php` has the reusable CRUD methods; one `<Entity>Dao.php` per table. |
| `backend/exceptions` | `HttpExceptions.php`: `ValidationException` (400), `NotFoundException` (404), `ConflictException` (409), `ExternalServiceException` (503). |
| `backend/utils`      | `Logger` (info.log / error.log) and `ErrorHandler` (every error → JSON; unexpected errors → 500 + error.log). |
| `backend/docs`       | Swagger UI page (`index.html`) and shared OpenAPI definitions (`OpenApiDefinitions.php`: schemas, common responses). swagger-php needs **one class per `#[OA\Schema]`**. `GET /openapi.json` (`routes/DocsRoutes.php`) generates the document. |
| `backend/config`     | Configuration. `config.php` is git-ignored; `config.example.php` is the committed template. `AppConfig.php` reads it (`AppConfig::get('nominatim')`); `Database.php` creates the single PDO connection. **Windows file names are case-insensitive:** never create a file whose name differs from an existing one only by case (e.g. `Config.php` next to `config.php`). |
| `backend/database`   | `aframe_living.sql`: schema + sample data. Re-import to reset the database.    |
| `backend/logs`       | Runtime logs. Contents are git-ignored.                                        |
| `backend/vendor`     | Composer packages (`flightphp/core`, `zircote/swagger-php`). Git-ignored; `composer.json` and `composer.lock` are committed. |

Run the backend locally with `php -S localhost:8080 -t backend backend/index.php`; Swagger is at `http://localhost:8080/docs/`.

**Adding a table:** add it to `backend/database/aframe_living.sql` and `docs/database-plan.md` (including the ERD). Then create a DAO that extends `BaseDao` and lists its writable columns, and add checks to `tests/dao/run-dao-tests.php`.

**Adding an endpoint / entity API:** create `<Entity>Service` (validation + rules) and `<Entity>Routes` (with `#[OA\...]` documentation), register the service in `index.php` with `Flight::register()` and call `<Entity>Routes::register()` there. Add checks to `tests/api/run-api-tests.php`.

### Tests

| Folder       | Responsibility                                                                 |
|--------------|--------------------------------------------------------------------------------|
| `tests/e2e`  | Browser tests (Node + headless Edge/Chrome, no npm packages). Run `node tests/e2e/run-all.mjs` after every change and keep all suites passing; add a suite or checks for new features. See `tests/e2e/README.md`. |
| `tests/dao`  | Database tests (PHP). `php tests/dao/run-dao-tests.php` rebuilds the separate database `aframe_living_test` from the `.sql` file and tests every DAO. Never point tests at the real `aframe_living` database. |
| `tests/api`  | REST API tests (PHP, real HTTP). `php tests/api/run-api-tests.php` rebuilds `aframe_living_test` and starts the backend on ports 8091–8094 with `test-bootstrap.php` (test database, fake Nominatim, logs in `tests/api/logs/`). Never call the real Nominatim from tests. Run it after every backend change. |

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
| REST endpoints | plural nouns, no verbs (the geocoding actions are the documented exception) | `GET /houses/{slug}`, `POST /inquiries` |
| PHP classes | PascalCase with a layer suffix | `HouseModelService`, `HouseModelDao`, `HouseModelRoutes`, `AuthMiddleware` |
| Flight service names | camelCase class name | `Flight::houseModelService()` |
| JSON fields | snake_case, same as the database columns | `price_eur`, `model_id` |

## Coding Rules

### General
- Use clear, descriptive names. Keep functions small and focused.
- Comment code where the reason isn't obvious. The developer is learning.
- Never commit secrets. Credentials belong only in `backend/config/config.php`, which is git-ignored.
- Don't duplicate content: pages read from `frontend/js/data/` instead of repeating prices, specs or locations.

### PHP / Backend
- Always use **PDO prepared statements** with bound parameters. Never build SQL by concatenating user input.
- Column and table names cannot be bound, so they may only come from a DAO's own column list (`BaseDao` rejects unknown columns). Never put request data into SQL as a column name or `ORDER BY` value.
- Every DAO extends `BaseDao`. Link tables with a composite key (`model_prices`, `model_features`) use their own methods instead of `getById`/`update`/`delete`.
- `UserDao` never returns `password_hash`, except `getByEmailWithPassword()`, which is for login only.
- Keep the connection settings in `Database.php`, including MySQL strict mode. Don't open PDO connections anywhere else.
- Return JSON with appropriate HTTP status codes: 200, 201 (created), 400 (invalid data, with `details` per field), 401/403 (Milestone 4), 404, 409 (duplicate or still in use), 503 (external API down), 500.
- Validate all input in the service layer with `Validator`. Unknown fields are rejected (400). PUT needs all required fields, PATCH only the sent ones.
- Routes don't catch exceptions: services throw `HttpException` subclasses, and `ErrorHandler` turns them into JSON. Never send stack traces, SQL or file paths to the client.
- External API calls need a timeout, must log failures to `error.log` and calls to `info.log`, and must never break the request that triggered them (save without the external data instead).
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
- **Milestone 2:**
  - Built:
    - the MySQL database (10 tables, primary and foreign keys, sample data) in `backend/database/aframe_living.sql`;
    - `Database.php` reading the git-ignored `config.php`;
    - `BaseDao` and a DAO for every table;
    - DAO tests (`tests/dao/`).
- **Milestone 3:**
  - Built:
    - Composer packages `flightphp/core` and `zircote/swagger-php` in `backend/`;
    - a service with validation and business rules for every entity;
    - FlightPHP routes for all CRUD operations of all entities;
    - the error handler (JSON errors, `error.log`);
    - OpenAPI attributes for every endpoint and the Swagger page at `/docs/`;
    - the external API (Nominatim) with `GeocodingService` and three endpoints;
    - the new `faqs` table (11 tables now);
    - API tests (`tests/api/`).
- **Not built yet (Milestone 4):**
  - JWT login and registration (`/auth/...`);
  - authentication and role middleware (401/403);
  - request-logging middleware (every request in `info.log`);
  - connecting the frontend to the API (replacing `frontend/js/data/`), including the map on the Locations page.

  Until then all endpoints are open.
