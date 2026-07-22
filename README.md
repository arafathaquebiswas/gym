# PowerSurge Gym — Management System & Website

Phase 1 deliverable: the full public website (Home, About, Membership, Packages,
Pricing, Personal Training, Store, Gallery, Blog, FAQ, Contact, Login, Register)
running on a secure PHP 8 / MySQL MVC foundation, plus the **complete database
schema** for the whole system (members, packages, POS, inventory, payments,
promotions, attendance, reports data, etc.) so later phases — Admin Dashboard,
Member Panel, POS, Reports — build on it without rework.

## Tech stack
PHP 8.1+, MySQL/MariaDB (PDO, prepared statements only), Bootstrap 5, jQuery,
vanilla ES6, PHPMailer (via Composer). No framework — clean custom MVC.

## Folder structure
```
config/         DB + app configuration (blocked from direct web access)
core/           Database, Router, Controller, Model, Auth, Security, Validator, Mailer
controllers/    One controller per feature area
models/         One model per table/entity
views/          layouts/, partials/, and one view per page
api/            AJAX endpoints (contact form)
assets/         css/, js/, images/
uploads/        member/product/gallery/blog/trainer photos (scripts blocked, files servable)
database/       schema.sql, seed.sql
admin/ member/ store/  reserved for later phases
index.php       front controller (all requests route through here)
.htaccess       pretty URLs + folder protection (Apache)
router.php      dev-only router for `php -S` (mirrors .htaccess)
```

## 1. Install dependencies
```bash
composer install
```

## 2. Create the database
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS gym_powersurge"
mysql -u root -p gym_powersurge < database/schema.sql
mysql -u root -p gym_powersurge < database/seed.sql
```
This seeds: 6 roles, 1 super admin, 8 membership packages, 3 trainers, 3
product categories, 13 products, 4 promotions, blog posts, gallery items,
testimonials, FAQs, and gym settings.

**Default super admin login:** `admin@powersurgegym.test` / `Admin@12345`
— change this password immediately in a real deployment (Phase 2 will add a
profile/password-change screen; until then, update the `password_hash`
column directly with a fresh `password_hash()` value).

## 3. Configure
Edit `config/config.php` (DB credentials, `APP_URL`, SMTP settings). All
values can also be supplied via environment variables (`DB_HOST`, `DB_USER`,
`DB_PASS`, `SMTP_HOST`, etc.) — useful on Hostinger where you set these in
the hosting panel instead of committing secrets to a file.

Without SMTP configured, `Mailer::send()` no-ops and logs to
`logs/php-error.log` — the contact form and registration still work, they
just won't send real emails until SMTP is set.

## 4a. Run locally with PHP's built-in server (fastest for dev)
```bash
php -S localhost:8000 router.php
```
Visit http://localhost:8000

## 4b. Run locally with XAMPP
1. Copy this folder into `htdocs/powersurge-gym`.
2. Start Apache + MySQL in the XAMPP control panel.
3. Import `database/schema.sql` then `database/seed.sql` via phpMyAdmin.
4. Set `APP_URL` in `config/config.php` to `http://localhost/powersurge-gym`.
5. Visit http://localhost/powersurge-gym — `.htaccess` handles pretty URLs
   (make sure `mod_rewrite` is enabled, which it is by default in XAMPP).

## 5. Deploy to Hostinger shared hosting
1. Upload the entire project to `public_html` (or a subdomain's document
   root) via File Manager or FTP — the whole repo, not just a `/public`
   subfolder, since `.htaccess` protects the internal folders in place.
2. Create a MySQL database + user from hPanel, then import
   `database/schema.sql` and `database/seed.sql` via phpMyAdmin.
3. Update `config/config.php` with the Hostinger DB credentials and your
   real domain in `APP_URL`.
4. Run `composer install` via Hostinger's SSH access (or upload the
   `vendor/` folder from your machine if SSH isn't available).
5. Confirm PHP version is 8.1+ in hPanel → Advanced → PHP Configuration.
6. Set SMTP credentials (Hostinger provides SMTP for your domain email) so
   contact form and registration emails actually send.

## Security notes (what's already in place)
- PDO prepared statements everywhere — no raw SQL concatenation.
- Passwords hashed with `password_hash()` (bcrypt/argon2 depending on PHP build).
- CSRF token per session, validated on every POST (`Security::requireCsrf()`).
- Output escaped via `e()` (htmlspecialchars) in every view.
- Session hardening: httponly/samesite cookies, session ID regenerated on
  login, idle timeout (`SESSION_LIFETIME`).
- Failed login attempts logged to `login_logs`; 5 failures within 15 minutes
  locks out that email/IP combination.
- Security headers (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
  set both in PHP (`Security::sendSecurityHeaders()`) and `.htaccess` as
  defense-in-depth.
- `/config`, `/core`, `/models`, `/controllers`, `/database`, `/vendor` are
  blocked from direct HTTP access; `/uploads` blocks script execution but
  still serves images.

## What's NOT built yet (future phases)
Admin Dashboard (stats, charts, quick actions), full Member Management CRUD,
Attendance/QR check-in, Trainer management UI, Store Management CRUD, POS,
Promotions builder UI, Payments/Expense tracking, Reports & PDF/Excel export,
Settings UI, backup/restore, member card & QR generation, password-reset
email flow. The database schema for all of this already exists in
`database/schema.sql` so none of it requires a schema migration later.
