-- =============================================================
-- Online payment verification for membership registration.
--
-- A visitor who pays by bKash/Nagad submits a Transaction ID and a screenshot;
-- an admin compares the two and marks the payment Verified or Rejected. The
-- membership only becomes active after that.
--
-- payment_status is left NULL rather than defaulting to 'pending': a member who
-- chose "Pay at Gym" has no online payment to verify, and a non-null default
-- would drop every walk-in registration into the verification queue forever.
-- NULL means "no online payment claimed"; the queue selects NOT NULL.
--
-- VARCHAR rather than ENUM for the three coded columns, even though neighbouring
-- columns use ENUM: SQLite cannot parse MySQL's ENUM(...) in an ALTER TABLE, and
-- a migrated database differing from a freshly-created one is worse than the
-- inconsistency. The allowed values are enforced in PHP, which has to validate
-- them anyway to produce error messages.
--
-- verified_by holds a users.id but carries no foreign key on purpose — deleting
-- a staff account must not cascade into a member's payment history.
--
-- Runs on MySQL and SQLite alike. Not re-runnable: a second run reports a
-- duplicate column, which is harmless.
-- =============================================================

ALTER TABLE members ADD COLUMN payment_method VARCHAR(20) NULL;

ALTER TABLE members ADD COLUMN payment_type VARCHAR(20) NULL;

ALTER TABLE members ADD COLUMN transaction_id VARCHAR(60) NULL;

ALTER TABLE members ADD COLUMN payment_screenshot VARCHAR(255) NULL;

ALTER TABLE members ADD COLUMN payment_status VARCHAR(20) NULL;

ALTER TABLE members ADD COLUMN verified_by INT UNSIGNED NULL;

ALTER TABLE members ADD COLUMN verified_at DATETIME NULL;

ALTER TABLE members ADD COLUMN rejection_reason VARCHAR(255) NULL;

-- The package price quoted at the moment of registration. Stored rather than
-- re-derived at display time: package offers start and end, so recomputing later
-- would show the admin a different figure from the one the visitor actually paid.
ALTER TABLE members ADD COLUMN registration_amount DECIMAL(10,2) NULL;

CREATE UNIQUE INDEX uniq_members_transaction_id ON members (transaction_id);

CREATE INDEX idx_members_payment_status ON members (payment_status);
