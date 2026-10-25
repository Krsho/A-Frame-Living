# AGENTS.md

Instructions for AI coding assistants working on **A-Frame Living**, a university web programming project. Read this file before making changes.

## Working Rules

- The developer is a **beginner**. Explain changes in plain language.
- **Inspect before changing.** Read the existing files before you add or edit anything.
- **Never delete or overwrite existing files without asking first.**
- Do only the task requested. Do not build ahead to future milestones.
- Do not install packages, create database tables, or add dependencies unless asked.
- Show the changes and wait for approval before moving to the next task.
- Never invent features, results, or details. Mark unknowns as `TODO`.
- Log significant AI assistance in `docs/AI_LOG.md` honestly. Record what actually happened, including mistakes and corrections.

## Technology Stack (required, do not substitute)

| Layer    | Technology                       | Not allowed                                   |
|----------|----------------------------------|-----------------------------------------------|
| Frontend | HTML, CSS, vanilla JavaScript    | React, Vue, Angular, jQuery, TypeScript, build tools |
| Backend  | PHP + FlightPHP                  | Laravel, Symfony, other frameworks            |
| Database | MySQL via PHP PDO                | mysqli, ORMs                                  |
| API      | REST, JSON request/response      |                                               |

## Architecture

- **Single-page application (SPA):** `frontend/index.html` (not yet created) loads once. JavaScript loads HTML fragments from `frontend/views/` and swaps them in without a full page reload.
- **REST API backend:** The backend returns JSON only. It never renders HTML pages for the frontend.
- **Strict separation:** Frontend code never touches the database or PHP directly. All communication goes through HTTP requests (`fetch`) to the API.

### Backend layers

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

### Frontend folders

| Folder            | Responsibility                                    |
|-------------------|---------------------------------------------------|
| `frontend/css`    | Stylesheets                                       |
| `frontend/js`     | SPA routing, view loading, API calls, UI logic    |
| `frontend/views`  | HTML fragments for each SPA view                  |
| `frontend/assets` | Images, icons, fonts                              |

## Coding Rules

### General
- Use clear, descriptive names. Keep functions small and focused.
- Comment code where the reason isn't obvious. The developer is learning.
- Never commit secrets. Credentials belong only in `backend/config/config.php`, which is git-ignored.

### PHP / Backend
- Always use **PDO prepared statements** with bound parameters. Never build SQL by concatenating user input.
- Return JSON with appropriate HTTP status codes (200, 201, 400, 401, 404, 500).
- Validate all input in the service layer.
- Hash passwords with `password_hash()` and check them with `password_verify()`.
- Don't show raw database errors to the client. Log them to `backend/logs/` instead.

### JavaScript / Frontend
- Vanilla JavaScript only (ES6+). Use `fetch` for API calls.
- Use `const`/`let`, never `var`.
- Use `textContent` instead of `innerHTML` for user-supplied data to prevent XSS.
- Keep the API base URL in one place so it is easy to change.

### CSS
- Keep styles in `frontend/css/`. Avoid inline styles.

## Current Status

- **Milestone 1:** Folder structure and documentation only. No application code, routes, database tables, or packages yet.
