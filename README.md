# Social Affairs Management System

A complete, production-ready Management Information System (MIS) for a Local
Government Social Affairs Department — built with PHP 8+, MySQL/MariaDB 8+,
Bootstrap 5 and Chart.js. It is a real full-stack application (backend,
database, authentication, RBAC, APIs, validation, search, reports,
import/export and automation) — not a UI prototype.

## Table of Contents

1. [Technology Stack](#technology-stack)
2. [Feature Overview](#feature-overview)
3. [Project Structure](#project-structure)
4. [Requirements](#requirements)
5. [Installation on Windows (XAMPP)](#installation-on-windows-xampp)
6. [Installation on Linux / macOS](#installation-on-linux--macos)
7. [Environment Configuration](#environment-configuration)
8. [Creating the First Super Admin](#creating-the-first-super-admin)
9. [Scheduled Jobs (Automation)](#scheduled-jobs-automation)
10. [Testing the System](#testing-the-system)
11. [Roles & Permissions](#roles--permissions)
12. [Backing Up the Database](#backing-up-the-database)
13. [Deploying Online](#deploying-online)
14. [Security Notes](#security-notes)
15. [Troubleshooting](#troubleshooting)

---

## Technology Stack

- **Backend:** PHP 8.1+ (plain, dependency-light MVC — no framework lock-in)
- **Database:** MySQL 8+ / MariaDB 10.11+ (InnoDB, foreign keys, utf8mb4)
- **Frontend:** HTML5, CSS3 (custom design-token theme), Bootstrap 5, vanilla JS, AJAX
- **Charts:** Chart.js
- **Calendar:** FullCalendar
- **Maps:** Leaflet.js + OpenStreetMap tiles
- **Icons:** Font Awesome
- **Excel/CSV:** PhpSpreadsheet (Composer)
- **Architecture:** Metadata-driven MVC — one generic CRUD engine
  (`app/Controllers/CrudController.php`) driven by module definitions in
  `app/Modules/ModuleRegistry.php` powers every registration module (Camps,
  Villages, Host Communities, Refugees, PWD, Organizations, Projects,
  Activities, Needs Assessments, Beneficiaries, Complaints, Schools, Health
  Facilities, Events, Sections) with consistent search, filter, sort,
  pagination, RBAC enforcement, export/import and audit logging.

The system does not depend on any external API for its core functionality —
the "AI Needs Assessment Assistant" is a local, rule-based analytics engine
(see `app/Core/AiNeedsAssistant.php`) that only reports figures computed
directly from your own database; it never calls out to a third-party service
and never invents statistics.

## Feature Overview

- Secure authentication: login, signup with **admin approval workflow**,
  forgot/reset password (emailed or logged locally if no SMTP is configured),
  change password, profile + photo.
- **Role-Based Access Control** with 7 built-in roles (Super Admin, Admin,
  Department Head, Section/Unit Head, Staff/User, Data Entry Officer, Viewer)
  fully configurable from **Roles & Permissions** — enforced server-side on
  every request, not just hidden in the UI.
- 15 registration modules covering the full department mandate: Sections,
  Camps, Villages, Host Communities, Refugees, Persons with Disabilities,
  Organizations, Projects, Activities, Needs Assessments, Beneficiaries,
  Complaints, Schools, Health Facilities, Events.
- **Automatic project/activity tracking**: days elapsed/remaining, time
  progress %, and status (Upcoming/Active/Near Deadline/Overdue/Completed)
  are computed automatically and refreshed on a schedule.
- **Automatic Priority Engine**: needs assessments are scored (severity,
  urgency, people affected, spread across locations) into High/Medium/Low
  priority, with authorized manual override + full audit trail.
- **AI Needs Assessment Assistant**: most common/urgent needs, most affected
  locations, vulnerable groups, trends, service gaps, suggested
  interventions and potentially relevant organizations — clearly separating
  database facts from generated recommendations.
- Live **Dashboard** with Chart.js visualizations, **Reports** (filterable,
  printable, exportable), global **Search**, **Calendar** (projects,
  activities, events, follow-ups, meetings), **Map** view (Leaflet, GPS-based
  modules), in-app **Notifications**, and an admin-only **Audit Log**.
- **Excel (.xlsx) & CSV import/export** for every module, with downloadable
  templates, per-row validation and import history.
- **Backup & Data Management** page (one-click `mysqldump`-based backup with
  a pure-PHP fallback, download history).
- Light/Dark mode, fully responsive (desktop/tablet/mobile).

## Project Structure

```
social-affairs/
├── app/
│   ├── Core/          # Framework: DB, Router, Auth, RBAC, Audit, Validator, ...
│   ├── Controllers/    # Request handlers
│   ├── Modules/         # ModuleRegistry.php — metadata driving the CRUD engine
│   ├── Views/           # PHP view templates
│   ├── bootstrap.php    # Autoload + session + config bootstrap
│   └── routes.php       # All application routes
├── config/config.php    # .env loader
├── cron/                # Scheduled jobs (recalculate.php + Windows .bat helper)
├── database/
│   ├── schema.sql        # Full normalized schema (run first)
│   └── seed.sql          # Roles, permissions, categories, default admin (run second)
├── public/                # Web root — point Apache/XAMPP here
│   ├── index.php          # Front controller
│   ├── assets/            # CSS/JS
│   └── uploads/           # User-uploaded files (avatars, attachments)
├── storage/                # logs/, backups/, cache/ (writable by the web server)
├── composer.json
└── .env.example
```

## Requirements

- PHP **8.1+** with extensions: `pdo_mysql`, `mbstring`, `zip`, `gd`, `xml`
  (all bundled with XAMPP by default)
- MySQL 8+ or MariaDB 10.11+
- Composer (for PhpSpreadsheet, used for Excel import/export)
- Apache with `mod_rewrite` enabled (bundled with XAMPP)

## Installation on Windows (XAMPP)

1. **Install XAMPP** (PHP 8.1+, MySQL/MariaDB, Apache) from apachefriends.org.
2. **Copy the project** into `C:\xampp\htdocs\social-affairs`.
3. **Install Composer** (getcomposer.org) if not already installed, then, in
   a terminal inside the project folder:
   ```
   composer install
   ```
   This downloads PhpSpreadsheet into `vendor/` (used for .xlsx import/export).
4. **Start Apache and MySQL** from the XAMPP Control Panel.
5. **Create the database.** Open phpMyAdmin (`http://localhost/phpmyadmin`)
   or a terminal:
   ```
   mysql -u root -p -e "CREATE DATABASE social_affairs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p social_affairs < database/schema.sql
   mysql -u root -p social_affairs < database/seed.sql
   ```
6. **Configure environment.** Copy `.env.example` to `.env` and edit the
   `DB_*` values to match your MySQL credentials (root user or a dedicated
   `social_affairs` user — see `.env.example`). Set `APP_URL` to
   `http://localhost/social-affairs/public`.
7. **Point your browser** at `http://localhost/social-affairs/public/login`.
   Apache serves the `public/` folder directly — the `.htaccess` inside it
   handles URL rewriting so no `index.php` appears in the URL. (Prefer
   configuring a XAMPP VirtualHost with `DocumentRoot` set to the `public/`
   folder for clean URLs like `http://social-affairs.local/login`.)
8. Log in with the seeded Super Admin (see [Creating the First Super
   Admin](#creating-the-first-super-admin)) and **change the password
   immediately** from *My Profile*.

## Installation on Linux / macOS

```bash
git clone <your-repo-url> social-affairs
cd social-affairs
composer install
cp .env.example .env
# edit .env with your DB credentials

mysql -u root -p -e "CREATE DATABASE social_affairs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p social_affairs < database/schema.sql
mysql -u root -p social_affairs < database/seed.sql

# Quick local run (no Apache needed) — docroot MUST be public/:
php -S 127.0.0.1:8080 -t public
```
Then open `http://127.0.0.1:8080/login`.

For Apache, point the VirtualHost `DocumentRoot` at the `public/` directory
and ensure `AllowOverride All` so `.htaccess` rewriting works.

## Environment Configuration

All configuration lives in `.env` (never committed — see `.env.example` for
the full list of keys):

| Key | Purpose |
|---|---|
| `APP_URL` | Base URL the app is served from |
| `APP_DEBUG` | `true` shows detailed errors (development only — set `false` in production) |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Database connection |
| `SESSION_LIFETIME` | Session timeout, minutes |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` | Optional SMTP for password-reset emails. If left blank, reset links are written to `storage/logs/mail.log` (and shown on-screen when `APP_DEBUG=true`), so the system works fully offline. |

Database credentials are read only from `.env` server-side and are never
exposed to the frontend.

## Creating the First Super Admin

`database/seed.sql` creates one ready-to-use Super Admin:

- **Email:** `admin@socialaffairs.gov`
- **Password:** `ChangeMe#2026`

**Change this password immediately after first login** (*My Profile → Change
Password*). To create additional Super Admins/Admins afterwards:

1. Have the person sign up at `/signup` (their account is created with
   status `pending`).
2. Log in as an existing Admin/Super Admin, go to **Users**, and click
   **Approve**. Their role can be changed from the Users page too.

Or, to promote a user directly from SQL:
```sql
UPDATE users SET role_id = 1, status = 'approved' WHERE email = 'someone@example.com';
```

## Scheduled Jobs (Automation)

`cron/recalculate.php` recomputes Project/Activity status & progress,
recalculates the Priority engine, and raises deadline notifications. Run it
every 15–30 minutes:

**Linux/macOS (crontab):**
```
*/15 * * * * php /path/to/social-affairs/cron/recalculate.php >> /path/to/social-affairs/storage/logs/cron.log 2>&1
```

**Windows (Task Scheduler):**
```
schtasks /Create /SC MINUTE /MO 15 /TN "SocialAffairsRecalculate" /TR "C:\xampp\htdocs\social-affairs\cron\run_recalculate.bat"
```
(edit the `PHP_BIN` path inside `cron/run_recalculate.bat` if your XAMPP PHP
isn't at the default location).

Status/progress is also opportunistically refreshed on normal page loads (at
most once every 30 seconds), so the dashboard stays accurate even before the
first scheduled run.

## Testing the System

A suggested manual test pass after installation:

1. **Auth:** sign up a test account → confirm it's `pending` → approve it as
   Admin → log in as the new user → change password → reset password via
   "Forgot password" (check `storage/logs/mail.log` if no SMTP is set).
2. **RBAC:** log in as each role and confirm View/Edit/Delete buttons appear
   only where permitted, and that direct POSTs to restricted actions return
   "Access Denied" (403) even if a button was hidden.
3. **CRUD:** create/edit/archive/restore a record in a few modules (e.g.
   Camps, Organizations, Beneficiaries) and confirm it appears correctly,
   including relation dropdowns (e.g. Host Community → Village).
4. **Projects/Activities:** create a project with an end date a few days
   away and confirm its status/progress bar updates after
   `php cron/recalculate.php` runs.
5. **Needs Assessments → Priorities → AI Assistant:** log a few needs
   assessments, run the cron, and confirm the Priority ranking and AI
   Assistant reflect them.
6. **Import/Export:** download a module's Excel template, fill a row, import
   it, and confirm validation errors are reported for bad rows.
7. **Reports/Dashboard/Calendar/Map:** confirm charts and lists populate
   from live data.
8. **Audit Log:** confirm the actions above are recorded (Settings → Audit
   Log, Super Admin/Admin only).

## Roles & Permissions

| Role | View | Create/Edit | Delete | Approve Users | Manage Roles/Settings |
|---|---|---|---|---|---|
| Super Admin | All | All | All | Yes | Yes |
| Admin | All | All | All | Yes (if granted) | Reports/Settings only |
| Department Head | All (department) | Yes | No | No | No |
| Section/Unit Head | Own section | Yes | No | No | No |
| Staff/User | All | Yes | No | No | No |
| Data Entry Officer | All | Yes (+import) | No | No | No |
| Viewer | All (read-only) | No | No | No | No |

Permissions are stored per role × module × capability in the database
(`roles`, `permissions`, `role_permissions`) and are editable from
**Roles & Permissions** (Super Admin only) — every check is enforced in
`app/Core/Permission.php` on the server, so hiding a button in the UI is
never the only line of defense.

## Backing Up the Database

- **From the UI:** Settings → Backup → *Create Backup Now* generates a
  timestamped `.sql` dump in `storage/backups/` (via `mysqldump`, with a
  pure-PHP fallback if `mysqldump` isn't on the server's PATH) and lets you
  download it.
- **From the command line:**
  ```
  mysqldump -u root -p social_affairs > backup_$(date +%Y%m%d).sql
  ```
- **Restoring:**
  ```
  mysql -u root -p social_affairs < backup_20260101.sql
  ```
- Schedule the backup UI's underlying logic or a `mysqldump` cron job
  regularly in production, and store backups off-server.

## Deploying Online

1. Provision a server with PHP 8.1+, MySQL/MariaDB, and Composer (a standard
   LAMP/LEMP stack or managed PHP hosting).
2. Upload the project (excluding `vendor/` and `.env`; run `composer install
   --no-dev --optimize-autoloader` on the server, or upload `vendor/`).
3. Create the production database and import `schema.sql` then `seed.sql`
   (change the default admin password immediately).
4. Create `.env` on the server with production values:
   - `APP_ENV=production`, `APP_DEBUG=false`
   - `APP_URL=https://your-domain.example`
   - Strong, unique `DB_PASSWORD` and `APP_KEY`
   - Real SMTP credentials so password-reset emails are delivered
5. Point the web server's document root at `public/` and serve over
   **HTTPS** (e.g. via Let's Encrypt/Certbot or your host's SSL).
6. Set correct permissions so only the web server user can write to
   `storage/` and `public/uploads/` (avoid world-writable permissions).
7. Set up the scheduled job (`cron/recalculate.php`) and a recurring
   database backup on the server (cron/systemd timer).
8. New sign-ups will arrive as `pending` exactly as they do locally — an
   Admin approves them from **Users** before they can log in.

## Security Notes

- Passwords are hashed with bcrypt (`password_hash`/`PASSWORD_BCRYPT`).
- All state-changing requests require a CSRF token (`app/Core/Csrf.php`).
- All SQL uses parameterized PDO statements — no string-concatenated queries.
- Login attempts are rate-limited (configurable in Settings) with a
  temporary lockout after repeated failures.
- RBAC is enforced server-side on every controller action, independent of
  what the UI shows.
- Sensitive personal fields (phone numbers, addresses) are masked in the UI
  for roles without `view_all`/`manage` permission on that module.
- Every create/update/delete/approve/export/import is written to the audit
  log with the acting user, IP address, and (for updates) old vs. new
  values.
- Records are **soft-deleted** (archived) by default, not permanently
  removed, and can be restored by users with delete permission.
- `.env`, `vendor/`, and `storage/` are excluded from version control and
  are not web-accessible when the document root is `public/`.

## Troubleshooting

- **Blank page / 500 error:** set `APP_DEBUG=true` in `.env` temporarily to
  see the underlying PHP error, and check `storage/logs/` and your web
  server's error log.
- **"Database connection failed":** double-check `DB_*` values in `.env`
  and that MySQL/MariaDB is running.
- **Excel export/import errors:** run `composer install` — PhpSpreadsheet
  must be present in `vendor/`.
- **Pretty URLs (`/login`) return 404:** ensure `mod_rewrite` is enabled in
  Apache and that `AllowOverride All` is set for the `public/` directory, or
  that XAMPP's `.htaccess` support is enabled.
- **Password reset link not received:** without SMTP configured, links are
  written to `storage/logs/mail.log` instead of being emailed — this is
  expected for a fully offline local setup.
