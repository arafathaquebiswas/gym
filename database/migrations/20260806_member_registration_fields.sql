-- =============================================================
-- Online Membership Registration: target weight + claimed coupon.
--
-- Deliberately NOT adding "current_weight" or "profile_image" columns —
-- members.weight_kg and members.photo already hold exactly those values and are
-- what the admin member screens read and write. A second column for either
-- would give the same fact two homes that immediately drift apart.
--
-- The registration form assigns members.photo = 'logo/logo.png' when no picture
-- is uploaded; media_tile() resolves any non-uploads/ path under assets/images/,
-- so that renders the site logo with no view changes.
--
-- Runs on MySQL and SQLite alike. Not re-runnable: a second run reports a
-- duplicate column, which is harmless.
-- =============================================================

ALTER TABLE members ADD COLUMN target_weight_kg DECIMAL(5,2) NULL;

ALTER TABLE members ADD COLUMN registration_coupon_code VARCHAR(50) NULL;

ALTER TABLE members ADD COLUMN registration_coupon_discount DECIMAL(10,2) NULL;
