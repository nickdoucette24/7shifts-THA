# 7shifts Take-Home — Restaurant Staff Scheduling

### A small full-stack app for managing restaurant staff and shifts, built with:

**Frontend:** React + Vite (JavaScript)

**Backend:** Plain PHP, JSON file database

**Dev UX:** Vite proxy + concurrently

7shifts noted a preference for PHP. I chose plain PHP (no framework) to stay within the timeline scope and keep the code easy to review. In production I would most likely use Laravel.

## Table of Contents

- [What it does](#what-it-does)
- [Project structure](#project-structure)
- [Getting started](#getting-started)
  - [Requirements](#requirements)
  - [Install & run (dev)](#install--run-dev)
  - [Scripts](#scripts)
  - [Troubleshooting](#troubleshooting)
- [API](#api)
  - [Data models](#data-models)
  - [Endpoints](#endpoints)
  - [Validation & errors](#validation--errors)
- [Frontend](#frontend)
  - [UI flows](#ui-flows)
  - [Responsiveness](#responsiveness)
- [Design decisions](#design-decisions)
- [Testing](#testing)
- [Assumptions & limitations](#assumptions--limitations)
- [If I had more time / Production notes](#if-i-had-more-time--production-notes)
- [Timeboxing & commits](#timeboxing--commits)
- [Access](#access)

## What it does

### Staff management

- List staff

- Create staff (name, role, phone)

### Shift management

- Create shifts (day, start, end, role)

- List shifts

- Assign a staff member to a shift (role-matched, no overlap on same day)

## Validation

- Start < end, role enum, phone sanity, role match on assign, prevent overlapping assignments

Project structure

```
repo-root/
server/
public/
index.php # Front controller / tiny router
src/
DataStore.php # JSON-file persistence (staff.json, shifts.json)
Validators.php # Input validation + assignment rules
helpers.php # JSON I/O, time math, overlap check
data/
staff.json # Seeded as []
shifts.json # Seeded as []
src/
api.js # Frontend API client (fetch wrapper)
App.jsx # Main UI (tabs: Staff / Shifts)
components/
StaffForm.jsx
StaffList.jsx
ShiftForm.jsx
ShiftList.jsx
App.css # Light styles
vite.config.js # /api proxy -> PHP server
package.json
README.md # (this file)
```

## Getting started

### Requirements

- Node.js: 20.19+ or 22.12+ (Vite requirement)
- npm: 9+ (comes with Node)
- PHP: 8.x (CLI)

Check:

```
node -v
php -v
```

If Node is < 20.19, update (nvm recommended). If php is missing:

- macOS: brew install php
- Windows: winget install PHP.PHP
- Ubuntu/Debian: sudo apt install php-cli

### Install & run (dev)

```
# from repo root

npm install

# seed JSON "DB" (already in repo as [])
# server/data/staff.json -> []
# server/data/shifts.json -> []
# run client (5173) + PHP server (3001) together

npm run dev
```

- [UI: http://localhost:5173](http://localhost:5173)
- API (proxied via Vite): requests to /api/\* are forwarded to http://127.0.0.1:3001

### Scripts

```
{
"scripts": {
"dev": "concurrently -k -n CLIENT,SERVER -c auto \"npm:dev:client\" \"npm:dev:server\"",
"dev:client": "vite",
"dev:server": "php -S 127.0.0.1:3001 -t server/public server/public/index.php",
"build": "vite build",
"preview": "vite preview",
"test": "vitest --environment jsdom"
}
}
```

- concurrently runs the client and server in one terminal; -k kills both if one exits; names/colorize output.

### Troubleshooting

- php: command not found → install PHP and restart your terminal.
- Node version error → Vite needs Node 20.19+ or 22.12+.
- CORS error → ensure you’re calling fetch('/api/...') (not hardcoding http://127.0.0.1:3001), and that vite.config.js contains the proxy block.
- 404 on root of API → GET http://127.0.0.1:3001/ returns {"error":"Not found"} by design. Use /api/staff or /api/shifts.

## API

### Data models

### Staff

```
{
"id": "32hex",
"name": "Jane Doe",
"role": "server", // "server" | "cook" | "manager"
"phone": "555-123-4567" // freeform; validated to have >=10 digits
}
```

### Shift

```
{
"id": "32hex",
"day": "2025-08-27", // YYYY-MM-DD
"start": "10:00", // HH:MM (24h)
"end": "16:00", // HH:MM (24h)
"role": "server",
"assignedStaffId": "32hex | null"
}
```

### Endpoints

Base path during dev: /api (Vite proxy → PHP)

- GET /api/staff
  Returns all staff.

- POST /api/staff
  Body:

```
{ "name": "Jane", "role": "server", "phone": "555-123-4567" }
```

Returns created staff with id.

GET /api/shifts
Returns all shifts.

POST /api/shifts
Body:

```
{ "day": "2025-08-27", "start": "10:00", "end": "16:00", "role": "server" }
```

Returns created shift with id and assignedStaffId: null.

POST /api/shifts/:id/assign
Body:

```
{ "staffId": "<32hex>" }
```

Assigns a staff to a shift (role match + no overlap). Returns updated shift.

### Example cURL

```
# create staff

curl -X POST http://127.0.0.1:3001/api/staff \
 -H 'Content-Type: application/json' \
 -d '{"name":"Jane","role":"server","phone":"555-123-4567"}'

# create shift

curl -X POST http://127.0.0.1:3001/api/shifts \
 -H 'Content-Type: application/json' \
 -d '{"day":"2025-08-27","start":"10:00","end":"16:00","role":"server"}'

# assign (replace SHIFT_ID, STAFF_ID)

curl -X POST http://127.0.0.1:3001/api/shifts/SHIFT_ID/assign \
 -H 'Content-Type: application/json' \
 -d '{"staffId":"STAFF_ID"}'
```

###Validation & errors

### - Staff

- name: required, non-empty
- role: one of server|cook|manager
- phone: must contain at least 10 digits (freeform allowed in storage)

### - Shift

- day: YYYY-MM-DD
- start, end: HH:MM 24-hour; start < end (no overnight shifts)
- role: one of server|cook|manager

### - Assign

- staffId: required, must refer to an existing staff
- Role match: shift.role === staff.role
- No overlap: the staff cannot have another assigned shift overlapping on the same day

- Back-to-back (e.g., 12:00–16:00 and 16:00–20:00) is allowed.

### Error shape / status codes

```
// 422 Unprocessable Entity (validation)
{
"error": {
"message": "Validation failed",
"fields": { "start": "Use HH:MM", "time": "Start must be before end." }
}
}

// 404 Not Found
{ "error": { "message": "Shift not found" } }
```

## Frontend

### UI flows

### - Staff tab

- Create staff (name, role, phone)
- List all staff

### - Shifts tab

- Create shifts (day, start, end, role)
- List shifts; if unassigned, choose a staff (filtered by role) from a dropdown to assign

After each create/assign, the app refreshes both lists.

### Responsiveness

- Mobile (1080×1920): stacked forms/lists with native inputs.
- Desktop (≥1400×1000): two-column form layout using simple CSS; container width ~1200px.

Accessibility basics:

- Semantic labels on inputs
- role="alert" for error messages
- Buttons have clear text; simple keyboard operation

## Design decisions

### - Plain PHP over a framework (Laravel/Slim):

For a 4–6h take-home, this keeps setup minimal and the code easy to review. It also surfaces core skills: routing, validation, and business rules.

### - JSON file persistence:

It’s enough to show CRUD + validation + assignment rules without database setup overhead. Files live at server/data/\*.json. (Production would use a real DB.)

### - Tiny front controller:

server/public/index.php uses a compact switch/regex router to keep everything visible in one file. In production, I’d introduce a real router/framework.

### - Vite proxy for DX:

The frontend calls /api/\* and Vite proxies to PHP—no CORS, no env juggling in dev.

### - One small fetch client (src/api.js):

Centralized headers, error handling, and JSON parsing; components remain simple.

## Testing

I included a minimal UI test setup with Vitest + React Testing Library (example: StaffForm happy path). Run:

```
npm test
```

If I had more time, I’d add:

- Backend request tests in PHP (PHPUnit/Pest) for create staff/shift, role mismatch, overlap rejection
- More component tests (ShiftForm/ShiftList)
- An end-to-end smoke test (Playwright)

## Assumptions & limitations

- Single restaurant/location; no authentication
- No editing/deleting staff/shifts (not required by brief)
- No overnight shifts (start must be strictly before end)
- Basic phone sanity (≥10 digits); format is not enforced
- No time zones (day/time treated as local)
- In-memory-like simplicity for queries (O(n) scans) — OK for demo

## If I had more time / Production notes

### Backend

- Framework: Laravel (routing, validation, Eloquent, migrations, auth scaffolding)
- DB: SQLite for local/dev, PostgreSQL in production; proper migrations & seeders
- Domain rules: richer overlap logic, overnight support with end-date, role hierarchies
- Validation: dedicated FormRequests; stricter phone normalization (E.164) and locale support
- Error handling: centralized exception handler; structured error codes
- Observability: request logging, metrics, structured logs

### Frontend

- State/data: TanStack Query for caching/retries & mutation states
- Forms: field-level error display using server fields map; better accessibility
- UX: filters (by day/role), pagination for larger datasets
- Tests: broader unit + E2E coverage

### DevOps

- Env configuration (dotenv) and VITE_API_BASE for non-proxied deployments
- Docker compose for PHP + Node dev parity
- CI (lint, type check, test) + simple CD
- Nginx/Apache reverse proxy /api to the PHP app in production

## Timeboxing & commits

I kept commits small and descriptive (e.g., feat(api): add validators and JSON datastore, feat(ui): shift create + assign). This mirrors a typical PR flow and makes it easy to review the evolution of the solution.

## Access

Per instructions, the assignment brief itself is not included in the repo. The repo is invite-only; collaborators have been added. If you need access or have questions, feel free to reach out.
