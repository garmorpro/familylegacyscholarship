# familylegacyscholarship

Website and application portal for The Morgan Legacy Scholarship (themorganlegacy.com).

## Stack

- PHP (server-rendered pages, no framework)
- PostgreSQL, accessed via PDO
- PHPMailer (installed via Composer) for account-lockout and recommendation-request emails
- Bootstrap 5 + vanilla JS on the front end

## Structure

- `admin/` — admin portal (application review, recipients, settings). Requires login; see `app/require_admin.php`.
- `app/` — shared PHP: database connection, form handling, auth guard, CSRF helper.
- `recommendation/` — public page recommenders use to submit a letter, linked from an emailed token.
- `assets/` — CSS, images, shared header/footer includes.
- `uploads/recipients/` — recipient photos uploaded through the admin portal (not tracked in git).

## Local setup

1. Install PHP dependencies: `composer install` (installs `vendor/`, not tracked in git).
2. Copy `app/config.local.example.php` to `app/config.local.php` and fill in real database and SMTP credentials. This file is gitignored — it must be created by hand on every environment, including production, and its values should never be committed.
3. Point a PHP-capable web server at the repo root, with a PostgreSQL database matching the schema referenced in `app/functions.php` and `app/db.php`.

## Deploying

The production server pulls from `main` via `git pull`. Because secrets live outside the repo (`app/config.local.php`), a fresh deploy target needs that file created manually before the app will run.
