-- =============================================================
-- Add the role rows that the application code references but that
-- seed.sql never created: staff, delivery, main_admin.
--
-- Without them, creating a Staff account (Role Management → Add Staff) or a
-- Delivery Staff account fails with:
--   "NOT NULL constraint failed: users.role_id"  (SQLite)
--   "Column 'role_id' cannot be null"            (MySQL)
-- because User::create() resolves the role by slug and gets NULL back.
--
-- Written with INSERT ... SELECT ... WHERE NOT EXISTS rather than
-- INSERT IGNORE / INSERT OR IGNORE so the same file runs unchanged on both
-- MySQL and SQLite, and is safe to re-run.
-- =============================================================

INSERT INTO roles (slug, name)
SELECT 'staff', 'Staff'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = 'staff');

INSERT INTO roles (slug, name)
SELECT 'delivery', 'Delivery Staff'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = 'delivery');

INSERT INTO roles (slug, name)
SELECT 'main_admin', 'Main Admin'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = 'main_admin');
