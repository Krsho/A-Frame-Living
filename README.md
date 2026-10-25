# A-Frame Living

> **Status:** Draft. Milestone 1 (project structure only).

## About

A-Frame Living is a university web programming project. It is built as a single-page application (SPA) with a frontend and backend that communicate through a REST API.

**TODO:** Describe what the website does, who it is for, and its main features.

## Technology Stack

| Layer    | Technology                          |
|----------|-------------------------------------|
| Frontend | HTML, CSS, vanilla JavaScript       |
| Backend  | PHP with the FlightPHP framework    |
| Database | MySQL, accessed through PHP PDO     |
| API      | REST (JSON)                         |

## Architecture

- The **frontend** is a single-page application. It loads once in the browser and swaps views with JavaScript instead of reloading the whole page.
- The **backend** is a REST API. It receives HTTP requests and returns JSON.
- The frontend and backend are kept **separate**. The frontend talks to the backend only through API calls.

## Project Structure

```
A-Frame-Living/
├── frontend/
│   ├── css/          # Stylesheets
│   ├── js/           # JavaScript (SPA logic, API calls)
│   ├── views/        # HTML fragments for each SPA view
│   └── assets/       # Images, icons, fonts
├── backend/
│   ├── routes/       # FlightPHP route definitions (API endpoints)
│   ├── services/     # Business logic
│   ├── dao/          # Data Access Objects (database queries via PDO)
│   ├── middleware/   # Code run before routes (e.g. authentication)
│   ├── config/       # Configuration (real config is git-ignored)
│   └── logs/         # Runtime log files (git-ignored)
├── docs/
│   └── AI_LOG.md     # Record of AI assistance used in the project
├── AGENTS.md         # Instructions for AI coding assistants
└── README.md
```

## Setup

**TODO:** Requirements (PHP version, MySQL version, Composer), installation steps, and how to run locally.

## Configuration

**TODO:** Explain how to copy `backend/config/config.example.php` to `config.php` and fill in database credentials.

## Database

**TODO:** Database schema and setup instructions.

## API Endpoints

**TODO:** List of REST endpoints.

## Milestones

- [ ] Milestone 1: Project structure *(in progress)*
- [ ] **TODO:** Further milestones

## Authors

**TODO**
