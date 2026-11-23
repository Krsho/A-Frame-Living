# A-Frame Living

> **Status:** Milestone 3 – REST API with FlightPHP: a service with business rules for every entity, CRUD routes for every entity, OpenAPI documentation with Swagger, and the external API (OpenStreetMap Nominatim). Login with JWT, middleware and the connection of the frontend to the API follow in Milestone 4; until then the frontend still uses its static data.

## About

A-Frame Living is a university web programming project: a website that presents and sells four A-frame house models – **Atlantic, Norway, Scandinavia and Continental** – built across Bosnia and Herzegovina.

Visitors can explore and compare the models, view photo galleries, see where houses have been built, learn how the buying process works, read the FAQ and send an inquiry.

Model sizes, rooms, features, prices, footprints and completed locations were provided by the project owner. Some other content (company contact details, parts of the texts, a few dimensions) was written for the assignment and is marked with a comment in the code.

## Technology Stack

| Layer    | Technology                          | Status |
|----------|-------------------------------------|--------|
| Frontend | HTML, CSS, vanilla JavaScript (ES modules) | Built |
| Backend  | PHP 8 with the FlightPHP framework  | DAO layer, services and routes built; JWT and middleware planned (M4) |
| Database | MySQL (MariaDB), accessed through PHP PDO | Built, with sample data |
| API      | REST (JSON), documented with OpenAPI / Swagger | Built |
| External API | OpenStreetMap Nominatim (geocoding) | Built in the backend; shown on the map in M4 |

No frontend frameworks, no build tools and no npm packages. The backend uses two Composer packages: `flightphp/core` and `zircote/swagger-php` (generates the OpenAPI document).

## Architecture

- **Single-page application.** `frontend/index.html` loads once. A small hash router (`js/router.js`) watches the part of the URL after `#` (for example `#/houses/norway`). It loads the matching HTML fragment from `frontend/views/`, puts it into `<main>`, and runs that page's JavaScript. The page never fully reloads.
- **Data in one place.** All model, package, location, FAQ and company data lives in `frontend/js/data/`. Pages generate their content from these files, so a price or a location only has to be changed once.
- **Reusable components.** `js/components.js` builds shared pieces (house cards, price blocks, package cards, images with fallbacks). `js/validation.js` handles form validation, and `js/lightbox.js` is the photo viewer.
- **Ready for the API.** The contact form sends through `js/services/inquiries.js`, which currently only *simulates* the request. In the backend phase this file will call the FlightPHP REST API. The pages themselves will not need to change.
- **Frontend and backend stay separate.** The frontend will talk to the backend only through HTTP requests (`fetch`) that return JSON.
- **Backend layers.** Every request goes through `backend/index.php` and flows in one direction:
  `HTTP request → route → service → DAO → MySQL`, and back as JSON.
  - **Routes** (`backend/routes/`) read the request, call one service method and send JSON. They contain no SQL and no business rules.
  - **Services** (`backend/services/`) validate the data (`Validator.php`) and apply the business rules, e.g. a unique e-mail, or "a model with projects cannot be deleted". They are the only layer that calls DAOs.
  - **DAOs** (`backend/dao/`) are the only place where SQL runs, always with prepared statements.
  - **Errors** are thrown by services as exceptions with a status code (`backend/exceptions/`). The error handler (`backend/utils/ErrorHandler.php`) turns them into JSON. Unexpected errors become a `500` with a general message, and the details go to `backend/logs/error.log`.

## Pages and Features

| Route | Page | Features |
|---|---|---|
| `#/` | Home | Hero, key numbers, the four models, company story, buying preview |
| `#/houses` | Models | Model cards, bedroom and feature filters, comparison table, package explanation |
| `#/houses/<model>` | Model detail | Photo, specs (area, rooms, footprint, height), prices per package, completed locations, gallery with a full-screen viewer, inquiry button |
| `#/locations` | Locations | 14 completed projects as cards, filter by model, map links |
| `#/how-to-buy` | How to buy | Seven-step timeline, ROH BAU vs Turnkey, "who does what" table |
| `#/faq` | FAQ | 16 questions in 5 categories, expandable (mouse and keyboard), expand/collapse all |
| `#/contact` | Contact | Contact details; form with validation and a **simulated** submission (nothing is sent yet) |
| `#/login` | Log in | Form with validation; demo notice (no real login yet) and links to preview both roles |
| `#/register` | Register | Form with validation (password rules, repeat password, terms); demo notice (no account is created) |
| `#/profile` | My account | Static customer view with **example data**: account details and own inquiries |
| `#/admin` | Admin dashboard | Static admin view: one management table per entity (models, packages, locations & projects, inquiries, users); buttons disabled |
| anything else | Not found | Friendly 404 page |

All pages are responsive (phone, tablet, desktop), with a mobile menu, visible keyboard focus and labelled form fields.

Log in, Register, Profile and Admin are **static previews** for Milestone 1: forms are validated in the browser, but nothing is sent or saved. The Profile and Admin pages use example data from `frontend/js/data/demo.js`.

## Project Structure

```
A-Frame-Living/
├── frontend/
│   ├── index.html          # The single page: header, <main>, footer
│   ├── css/
│   │   ├── base.css        # Colours, fonts, spacing (design tokens), reset
│   │   ├── components.css  # Buttons, cards, forms, tables, notices…
│   │   └── views.css       # Styles for individual pages
│   ├── js/
│   │   ├── app.js          # Entry point: routes and mobile menu
│   │   ├── router.js       # Hash router (no page reloads)
│   │   ├── components.js   # Reusable HTML building blocks
│   │   ├── validation.js   # Form validation rules and helpers
│   │   ├── lightbox.js     # Full-screen photo viewer
│   │   ├── data/           # houses, packages, locations, faq, company, demo (example data)
│   │   ├── services/       # API layer (inquiries.js – simulated for now)
│   │   └── views/          # One script per page
│   ├── views/              # One HTML fragment per page
│   └── assets/images/      # Photos (see assets/images/README.md)
├── backend/
│   ├── index.php                # Entry point of the API: loads classes, registers services and routes
│   ├── .htaccess                # Apache: sends every request to index.php
│   ├── composer.json / .lock    # Composer packages (FlightPHP, swagger-php)
│   ├── vendor/                  # Installed packages (git-ignored, created by composer install)
│   ├── config/
│   │   ├── config.example.php   # Settings template (committed)
│   │   ├── config.php           # Your real settings (git-ignored, create it yourself)
│   │   ├── AppConfig.php        # Reads config.php
│   │   └── Database.php         # Creates the PDO connection
│   ├── routes/                  # One route class per entity: URLs → service calls + OpenAPI docs
│   ├── services/                # Business logic and validation (Validator.php, GeocodingService.php = external API)
│   ├── dao/                     # BaseDao + one DAO per entity (the only place SQL runs)
│   ├── exceptions/              # Errors with an HTTP status code (400, 404, 409, 503)
│   ├── utils/                   # Logger and ErrorHandler
│   ├── docs/                    # Swagger page (index.html) and shared OpenAPI definitions
│   ├── database/
│   │   └── aframe_living.sql    # Database schema + sample data
│   ├── middleware/              # Milestone 4 (JWT, roles, request logging)
│   └── logs/                    # info.log and error.log (git-ignored)
├── tests/
│   ├── e2e/                # Browser tests (see tests/e2e/README.md)
│   ├── dao/                # Database/DAO tests (PHP)
│   └── api/                # REST API tests (PHP, real HTTP requests)
├── docs/
│   ├── database-plan.md    # Entities, relationships and draft ERD
│   └── AI_LOG.md           # Record of AI assistance used in the project
├── AGENTS.md               # Instructions for AI coding assistants
└── README.md
```

## Running the Website Locally

The site must be opened through a local web server. Opening `index.html` directly from the file system does not work, because browsers block loading the view files and JavaScript modules that way.

Choose one:

- **VS Code:** install the *Live Server* extension, right-click `frontend/index.html` and choose **Open with Live Server**.
- **Python:** from the project root run
  ```
  python -m http.server 8000 --directory frontend
  ```
  and open http://localhost:8000/.

## Running the Backend (REST API) Locally

1. Set up the database and `config.php` first (see [Database Setup](#database-setup)).
2. **Install the Composer packages** (once, and again whenever `composer.json` changes). You need [Composer](https://getcomposer.org/), and PHP's `openssl`, `curl` and `zip` extensions must be enabled in `php.ini`:
   ```
   cd backend
   composer install
   ```
   This creates `backend/vendor/` (git-ignored).
3. **Start the API** with PHP's built-in server, from the project root:
   ```
   php -S localhost:8080 -t backend backend/index.php
   ```
   - The API runs at http://localhost:8080, e.g. http://localhost:8080/houses.
   - The Swagger documentation is at http://localhost:8080/docs/.

   With XAMPP's Apache instead, put the project in `htdocs` (or point an alias to it) and open `.../backend/docs/`. `backend/.htaccess` sends every request to `index.php`.

## Running the Tests

**Frontend (browser tests, 6 suites).** Needs Node.js 22+, Python 3 and Microsoft Edge or Google Chrome; nothing to install:

```
node tests/e2e/run-all.mjs
```

See [tests/e2e/README.md](tests/e2e/README.md) for what each suite covers and how to save screenshots.

**Database and DAO layer.** Needs PHP 8 with `pdo_mysql`, a running MySQL server and `backend/config/config.php` (see [Database Setup](#database-setup)):

```
php tests/dao/run-dao-tests.php
```

The script builds a separate database `aframe_living_test` from the `.sql` file and tests create, read, update and delete for every DAO, plus the foreign-key rules. Your `aframe_living` database is not changed.

**REST API.** Needs the same as the DAO tests, plus `composer install` in `backend/` and PHP's `curl` extension:

```
php tests/api/run-api-tests.php
```

The script rebuilds `aframe_living_test` and starts the backend on ports 8091–8094 with PHP's built-in server. It then sends real HTTP requests to every endpoint and checks the status codes, the JSON, the business rules and the log entries.
- The external API is replaced by a small fake Nominatim (`tests/api/fake-nominatim.php`), so the tests never call OpenStreetMap.
- A second backend runs with Nominatim "down", to check that the application keeps working.
- Test logs are written to `tests/api/logs/`, not `backend/logs/`.

## Editing Content

Until the frontend is connected to the API (Milestone 4), the website still shows the data from these files. The same data is in the database and can already be changed through the API.

| What | Where |
|---|---|
| Models (sizes, prices, features, photos) | `frontend/js/data/houses.js` |
| Package descriptions and "who does what" | `frontend/js/data/packages.js` |
| Completed locations | `frontend/js/data/locations.js` |
| FAQ | `frontend/js/data/faq.js` |
| Company contact details | `frontend/js/data/company.js` |
| Example users and inquiries (Profile/Admin preview only) | `frontend/js/data/demo.js` |
| Photos | `frontend/assets/images/` (see its README) |

## Entities and Relationships

The schema with columns, keys and the ERD is documented in **[docs/database-plan.md](docs/database-plan.md)**. The database itself is created by [backend/database/aframe_living.sql](backend/database/aframe_living.sql).

| Entity (table) | Relationships |
|---|---|
| `users` | sends many `inquiries` |
| `house_models` | has many `model_images`, many `projects`, many `inquiries`; priced in many `packages` (via `model_prices`); has many `features` (via `model_features`) |
| `packages` | ROH BAU and Turnkey; linked to every model through `model_prices` |
| `features` | rooms and features such as "Kitchen" or "Fireplace"; linked to models through `model_features` |
| `locations` | hosts many `projects`; stores map coordinates from the external API |
| `projects` | a completed build: one model at one location |
| `inquiries` | contact-form messages; optionally linked to a user and a model |
| `faqs` | questions and answers for the FAQ page (added in Milestone 3; no relationships) |

## Roles and Test Accounts

| Role | Can do |
|---|---|
| Guest (not logged in) | Browse models, locations and FAQ; send an inquiry; register |
| Customer | Everything a guest can, plus manage own profile and see own inquiries |
| Admin | Manage models, packages, features, locations, projects, inquiries and users |

Test accounts (included in the sample data). Logging in through the website works from Milestone 4 (JWT authentication).

| Role | Email | Password |
|---|---|---|
| Admin | `admin@aframe-living.example` | `Admin123!` |
| Customer | `ana.kovac@example.com` | `Customer123!` |

The other sample customers (`emir.hadzic@example.com`, `lena.maric@example.com`) use the same password.

## External API

**OpenStreetMap Nominatim** (geocoding) turns a place name such as "Kupres, Bosnia and Herzegovina" into map coordinates.

- **Feature:** when an admin adds a location or changes its city, the backend looks up its coordinates and saves them. `GET /projects` returns every project with the coordinates of its location. In Milestone 4 the **Locations** page will use them to show a map with a pin per project; a placeholder panel already marks the spot.
- **Code:** `backend/services/GeocodingService.php` calls Nominatim. `LocationService` uses it when a location is saved.
- **Endpoints** (documented in Swagger under *External API*):
  - `GET /geocode?city=&country=`: look up a place; nothing is saved.
  - `POST /locations/{id}/geocode`: look up and save one location.
  - `POST /locations/geocode-missing`: look up and save every location without coordinates.
- **No API key** is needed. The settings (URL, User-Agent, timeout, optional contact e-mail) are in `config.php` under `nominatim`.
- **When Nominatim fails** (timeout, no connection, HTTP error, invalid answer):
  - the failure is written to `backend/logs/error.log`;
  - the client gets `503` with a friendly message;
  - a location that is being saved is still saved, without coordinates.

  Successful calls are written to `backend/logs/info.log`.
- Nominatim's usage policy is respected: the requests identify the app in the User-Agent and are sent at most once per second.

The sample locations have no coordinates yet. After importing the database and starting the backend, call `POST /locations/geocode-missing` once in Swagger to fill them in. It takes about 14 seconds, because of the one-request-per-second limit.

## Database Setup

You need MySQL or MariaDB (for example from **XAMPP**: start *MySQL* in the XAMPP Control Panel) and PHP 8 with the `pdo_mysql` extension.

1. **Import the database.** This creates the database `aframe_living` with all tables and sample data. Importing again resets it, so if you imported it before Milestone 3, import it again to get the new `faqs` table:
   - **phpMyAdmin:** open http://localhost/phpmyadmin, choose *Import*, select `backend/database/aframe_living.sql`, then *Go*.
   - **Command line:**
     ```
     mysql -u root < backend/database/aframe_living.sql
     ```
     In **PowerShell** (e.g. the VS Code terminal), `<` does not work; with XAMPP use:
     ```
     cmd /c "C:\xampp\mysql\bin\mysql.exe -u root < backend\database\aframe_living.sql"
     ```
2. **Create the configuration file.** Copy `backend/config/config.example.php` to `backend/config/config.php` and fill in your database user and password. XAMPP's default is user `root` with an empty password, which the template already uses. `config.php` is git-ignored, so your credentials stay on your computer.
3. **Check it works:** `php tests/dao/run-dao-tests.php` should end with all checks passed.

## API Documentation

With the backend running locally: **http://localhost:8080/docs/** (Swagger UI). The raw OpenAPI document is at http://localhost:8080/openapi.json.

The document is generated from the `#[OA\...]` attributes next to each route in `backend/routes/`, so it always matches the code. Every endpoint can be tried out directly on the Swagger page. **TODO:** link to the deployed Swagger page (Milestone 5).

Main endpoints (all JSON):

| Resource | Endpoints |
|---|---|
| House models | `GET/POST /houses`, `GET /houses/{id or slug}`, `PUT/PATCH/DELETE /houses/{id}` |
| Prices | `GET /houses/{id}/prices`, `PUT/DELETE /houses/{id}/prices/{packageId}` |
| Model features | `GET/POST /houses/{id}/features`, `DELETE /houses/{id}/features/{featureId}` |
| Photos | `GET/POST /houses/{id}/images`, `GET/PUT/PATCH/DELETE /images/{id}` |
| Packages, features, locations, projects, inquiries, users, FAQ | `GET/POST /<resource>`, `GET/PUT/PATCH/DELETE /<resource>/{id}` |
| Extra | `GET /users/{id}/inquiries`, filters `?category=`, `?status=`, `?include_inactive=true` |
| External API | `GET /geocode`, `POST /locations/{id}/geocode`, `POST /locations/geocode-missing` |

Status codes: `200` OK, `201` created, `400` invalid data (with one message per field), `404` not found, `409` conflict (duplicate value or row still in use), `503` external API unavailable, `500` unexpected error. `401`/`403` follow with login and roles in Milestone 4. Until then **all endpoints are open**.

## Deployed Application

**TODO:** link to the publicly hosted application.

## Milestones

- [x] **Milestone 1** – setup, static frontend, ERD
  - [x] Project structure, `.gitignore`, README, AGENTS.md, AI log
  - [x] Frontend SPA: Home, Models, Model details, Locations, How to buy, FAQ, Contact
  - [x] Automated browser tests
  - [x] Static Log in / Register views
  - [x] Static User profile and Admin dashboard views
  - [x] Planned place for the external API feature (map on Locations)
  - [x] Database plan and draft ERD
- [x] **Milestone 2** – database and DAO layer
  - [x] MySQL schema with 10 tables, primary and foreign keys (`backend/database/aframe_living.sql`)
  - [x] Sample data (the real models, prices, photos and the 14 projects; example users and inquiries)
  - [x] `BaseDao` with reusable CRUD methods and a DAO for every entity, using prepared statements
  - [x] Database credentials read from the git-ignored `config.php` (`config.example.php` committed)
  - [x] DAO tests
- [x] **Milestone 3** – services, routes, OpenAPI, external API
  - [x] A service with validation and business rules for every entity (`backend/services/`)
  - [x] FlightPHP routes for all CRUD operations of all entities, JSON with correct status codes
  - [x] OpenAPI documentation of every endpoint, Swagger page at `/docs/`
  - [x] External API: Nominatim service + endpoints, with timeout, error log and friendly `503`
  - [x] New `faqs` table, so the FAQ is no longer only hard-coded in the frontend
  - [x] API tests (`tests/api/`)
- [ ] **Later milestones:** JWT, middleware, logging, connected frontend (M4); frontend MVC, validation, deployment (M5)

## Authors

Faris Kršo – Web Programming, IBU, 2026
