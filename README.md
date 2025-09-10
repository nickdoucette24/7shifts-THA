# 7shifts Take-Home — Restaurant Staff Scheduling

### A small full-stack app for managing restaurant staff and shifts, built with:

**Frontend:** React + Vite (JavaScript)

**Backend:** Plain PHP + SQLite (PDO)

**Dev UX:** Vite proxy + concurrently

There was a noted preference for a PHP backend. I chose plain PHP (no framework) to stay within the timeline scope and keep the code easy to review. In production I would most likely use Laravel.

## Table of Contents

- [What it does](#what-it-does)
- [Project structure](#project-structure)
- [Getting started](#getting-started)
  - [Requirements](#requirements)
  - [Install & run (dev)](#install--run-dev)
  - [Database (SQLite)](#database)
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

- Start < end, roles , phone number characters, role match on assign, prevent overlapping assigned shifts

**Project structure**

```
*root/
server/
public/
  index.php # Controller / tiny router
src/
  DataStore.php # JSON-file persistence (staff.json, shifts.json)
  Validators.php # Input validation, assignment rules
  helpers.php # JSON work, time math, overlap check
data/
  app.db
db/
  schema.sql
phpunit.xml
tests/
  bootstrap.php
  DataStoreTest.php
  HelpersTest.php
  ValidatorsTest.php
root/src/
  api.js # Frontend API
  App.jsx # Main UI
   index.scss
  components/
    StaffForm.jsx
    StaffList.jsx
    ShiftForm.jsx
    ShiftList.jsx
  test/
    setup.js
    __tests__/
      StaffForm.test.jsx
      StaffList.test.jsx
      ShiftForm.test.jsx
      ShiftList.test.jsx
  App.scss
  vite.config.js # /api proxy -> PHP server
  package.json
  README.md # (this file)
```

## Getting started

### Requirements

- Node.js: 20.19+ or 22.12+ (Vite requirement)
- npm: 9+ (comes with Node)
- PHP: 8.x (CLI)
- PDO SQLite
- Composer (backend test)

Check:

```
node -v
php -v
php -m (should show pdo_sqlite or sqlite3)
composer -V
```

If Node is < 20.19, update (nvm recommended). If php is missing:

- macOS: brew install php
- Windows: winget install PHP.PHP
- Ubuntu/Debian: sudo apt install php-cli

### Install & run (dev)

```
# from repo root

npm install

npm run dev
```

- UI: http://localhost:5173
- API (proxied via Vite): requests to /api/\* are forwarded to http://127.0.0.1:3001

### Database (SQLite)

- The DB is a file at server/data/app.db
- You don't need to create it, the first API call will create it along with the schema.
- Inspect via:

```
sqlite3 server/data/app.db
.headers on
.mode table
.tables
SELECT * FROM staff; // or shifts
.quit
```

### Scripts

```
{
"scripts": {
"dev": "concurrently -k -n CLIENT,SERVER -c auto \"npm:dev:client\" \"npm:dev:server\"",
"dev:client": "vite",
"dev:server": "php -S 127.0.0.1:3001 -t server/public server/public/index.php",
"build": "vite build",
"preview": "vite preview",
"test": "vitest --run",
"test:watch": "vitest",
"test:php": "cd server && ./vendor/bin/phpunit"
}
}
```

- concurrently runs the client and server in one terminal; -k kills both if one exits; names/colorize output.
- this was not required for the assignment, but still is an efficiency add

## API

### Data models

### Staff

```
{
"id": "32hex",
"name": "Jane Doe",
"role": "server", // "server" | "cook" | "manager"
"phone": "555-123-4567" // freeform; validated to have 10-15 digits
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

- GET /api/shifts
  Returns all shifts.

- POST /api/shifts
  Body:

```
{ "day": "2025-08-27", "start": "10:00", "end": "16:00", "role": "server" }
```

Returns created shift with id and assignedStaffId: null.

- POST /api/shifts/:id/assign
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

### Validation & errors

### Staff

- name: required, non-empty
- role: one of server|cook|manager
- phone: must contain at least 10 digits (freeform allowed in storage)

### Shift

- day: YYYY-MM-DD
- start, end: HH:MM 24-hour; start < end (no overnight shifts)
- role: one of server|cook|manager

### Assign

- staffId: required, must refer to an existing staff
- Role match: shift.role === staff.role
- No overlap: the staff cannot have another assigned shift overlapping on the same day

- Back-to-back (e.g., 12:00–16:00 and 16:00–20:00) is allowed.

## Frontend

### UI flows

### Staff tab

- Create staff (name, role, phone)
- List all staff

### Shifts tab

- Create shifts (day, start, end, role)
- List shifts, and if unassigned, choose a staff (filtered by role) from a dropdown to assign

After each create/assign, the app refreshes both lists.

### Styling & Responsiveness

- **Files:** src/index.scss (global base), src/App.scss (layout/components)
- **Approach:** mobile-first defaults; one breakpoint at 1081px so that mobile responsiveness is locked into ≤ 1080px and desktop is locked into ≥ 1081px (which covers the requirements of 1400px)
- **Containers:** 63.5rem/1280px on large screens for a clean desktop layout

Accessibility basics:

- Semantic labels
- role="alert" for error messages
- Buttons have clear text and simple keyboard operation

## Design decisions

- Plain PHP over a framework (Laravel/Slim):
  For a 4–6h take-home, this keeps setup minimal and the code easy to review. It also surfaces core skills: routing, validation, and business rules.

- SQLite:
  Gives durability, simple relational checks with foreign keys. Would also add a couple small indexes to speed up validation checks in production.

- Tiny front controller:
  server/public/index.php uses a compact switch router to keep everything visible in one file. In production, I’d introduce a real router/framework.

- Vite proxy for DX:
  The frontend calls /api/\* and Vite proxies to PHP no CORS, no env juggling in dev.

- One small fetch client (src/api.js):
  Centralized headers, error handling, and JSON parsing; components remain simple.

## Testing

### Frontend (Vitest + React Testing Library)

- **Setup**
  - npm i -D vitest @testing-library/react @testing-library/user-event@testing-library/jest-dom jsdom
  - vite.config.js test block:

```
test: { environment: 'jsdom', setupFiles: './src/test/setup.js' }
```

- src/test/setup.js:

```
import '@testing-library/jest-dom/vitest';
import { afterEach } from 'vitest';
import { cleanup } from '@testing-library/react';
afterEach(() => cleanup());
```

- **What's covered**
  - `StaffForm` valid staff members (asserts api.createStaff)
  - `ShiftForm` valid shifts (asserts api.createShift)
  - `ShiftList` assignment flow (select staff → asserts api.assignShift)
  - `StaffList` renders multiple entries + empty state

```
npm test           # single run
npm run test:watch # watch mode
```

### Backend (PHPUnit 10)

- **Install**

```
cd server
composer require --dev phpunit/phpunit:^10
```

- **Config:** server/phpunit.xml
  , bootstrap loads helpers.php, DataStore.php, Validators.php.
- **What's covered:**

  - **Helpers:** as_minutes, overlaps
  - **DataStore:** create, upsert, getAll, findById
  - **Validators & assignment:**
    - Success paths tested directly
    - Error paths
      - `validate_staff` rejects invalid role
      - `validate_shift` rejects start after end
      - `assign_shift` rejects role mismatch
      - `assign_shift` rejects overlap
    - The tests launch a tiny child PHP process, capture JSON output, and assert message/fields. This keeps production code simple (validators still call json_response(...); exit;) while fully testing error behavior.

- **Run**

```
npm run test:php
# or
cd server && ./vendor/bin/phpunit
```

If I had more time, I’d add:

- Backend request tests in PHP (PHPUnit/Pest) for create staff/shift, role mismatch, overlap rejection

## Assumptions & limitations

- Single restaurant/location, no authentication
- No editing/deleting staff/shifts (not required by brief)
- No overnight shifts (start must be strictly before end)
- Basic phone sanity (10-15 digits)
- No time zones (day/time treated as local)

## If I had more time / Production notes

### Backend

- Framework: Laravel (for improved routing, validation, migrations, auth)
- Editing/Deleting of shifts and staff
- Overnight shift compatibility
- DB: SQLite / PostgreSQL in production; proper migrations & seeders
- Validation: dedicated FormRequests; stricter phone normalization, and
- Error handling: centralized exception handler with structured error codes

### Frontend

- Forms: better accessibility, more information options
- UX: filters (by day/role), pagination for larger datasets
- Tests: broader unit + E2E coverage

## Timeboxing & commits

I kept commits very descriptive for this assignment in order to allow for easy code review.

## Access

Per instructions, the assignment brief itself is not included in the repo. The repo is invite-only; collaborators have been added. If you need access or have questions, feel free to reach out!
