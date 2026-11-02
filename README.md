# A-Frame Living

> **Status:** Milestone 2 – MySQL database with sample data and the PHP DAO layer. The REST API (FlightPHP routes and services) follows in Milestone 3; the frontend still uses static data until Milestone 4.

## About

A-Frame Living is a university web programming project: a website that presents and sells four A-frame house models – **Atlantic, Norway, Scandinavia and Continental** – built across Bosnia and Herzegovina.

Visitors can explore and compare the models, view photo galleries, see where houses have been built, learn how the buying process works, read the FAQ and send an inquiry.

Model sizes, rooms, features, prices, footprints and completed locations were provided by the project owner. Some other content (company contact details, parts of the texts, a few dimensions) was written for the assignment and is marked with a comment in the code.

## Technology Stack

| Layer    | Technology                          | Status |
|----------|-------------------------------------|--------|
| Frontend | HTML, CSS, vanilla JavaScript (ES modules) | Built |
| Backend  | PHP 8 with the FlightPHP framework  | DAO layer built; routes and services planned |
| Database | MySQL (MariaDB), accessed through PHP PDO | Built, with sample data |
| API      | REST (JSON)                         | Planned |

No frontend frameworks, no build tools and no npm packages.

## Architecture

- **Single-page application.** `frontend/index.html` loads once. A small hash router (`js/router.js`) watches the part of the URL after `#` (for example `#/houses/norway`). It loads the matching HTML fragment from `frontend/views/`, puts it into `<main>`, and runs that page's JavaScript. The page never fully reloads.
- **Data in one place.** All model, package, location, FAQ and company data lives in `frontend/js/data/`. Pages generate their content from these files, so a price or a location only has to be changed once.
- **Reusable components.** `js/components.js` builds shared pieces (house cards, price blocks, package cards, images with fallbacks). `js/validation.js` handles form validation, and `js/lightbox.js` is the photo viewer.
- **Ready for the API.** The contact form sends through `js/services/inquiries.js`, which currently only *simulates* the request. In the backend phase this file will call the FlightPHP REST API. The pages themselves will not need to change.
- **Frontend and backend stay separate.** The frontend will talk to the backend only through HTTP requests (`fetch`) that return JSON.

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
│   ├── config/
│   │   ├── config.example.php   # Settings template (committed)
│   │   ├── config.php           # Your real settings (git-ignored, create it yourself)
│   │   └── Database.php         # Creates the PDO connection
│   ├── dao/                     # BaseDao + one DAO per entity (the only place SQL runs)
│   ├── database/
│   │   └── aframe_living.sql    # Database schema + sample data
│   ├── routes/  services/  middleware/   # Milestones 3–4
│   └── logs/                    # Log files (git-ignored)
├── tests/
│   ├── e2e/                # Browser tests (see tests/e2e/README.md)
│   └── dao/                # Database/DAO tests (PHP)
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

## Editing Content

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

**OpenStreetMap Nominatim** (geocoding) – *planned for the backend phase.* When an admin saves a location, the backend looks up its coordinates; the **Locations** page will show an interactive map with a pin for every completed project. A placeholder panel on the Locations page already marks where the map will appear. Details and error handling: [docs/database-plan.md](docs/database-plan.md#external-api-openstreetmap-nominatim).

## Database Setup

You need MySQL or MariaDB (for example from **XAMPP**: start *MySQL* in the XAMPP Control Panel) and PHP 8 with the `pdo_mysql` extension.

1. **Import the database.** This creates the database `aframe_living` with all tables and sample data (importing again resets it):
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

**TODO:** link to the Swagger (OpenAPI) documentation. The planned endpoints are listed in [docs/database-plan.md](docs/database-plan.md#draft-rest-endpoints-planning-notes).

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
- [ ] **Later milestones:** services, FlightPHP routes, OpenAPI, external API (M3); JWT, middleware, logging, connected frontend (M4); frontend MVC, validation, deployment (M5)

## Authors

Faris Kršo – Web Programming, IBU, 2026
