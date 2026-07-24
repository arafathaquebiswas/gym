-- =============================================================
-- PowerSurge Gym Management System — Full Database Schema
-- Engine: InnoDB, Charset: utf8mb4
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS gym_powersurge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gym_powersurge;

-- =============================================================
-- ACCESS / SECURITY
-- =============================================================

CREATE TABLE roles (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug          VARCHAR(30)  NOT NULL UNIQUE,
    name          VARCHAR(60)  NOT NULL
) ENGINE=InnoDB;

CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id         INT UNSIGNED NOT NULL,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    phone           VARCHAR(30)  NULL,
    password_hash   VARCHAR(255) NOT NULL,
    avatar          VARCHAR(255) NULL,
    status          ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    remember_token  VARCHAR(100) NULL,
    last_login_at   DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_users_role (role_id),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- Per-user, per-module permission overrides (Main Admin / Super Admin / Staff hierarchy).
-- No row for a given user+module = default-allow for super_admin, default-deny for staff
-- (see core/Permission.php) — main_admin never consults this table at all.
CREATE TABLE user_permissions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    module_key  VARCHAR(30) NOT NULL,
    can_view    TINYINT(1) NOT NULL DEFAULT 0,
    can_create  TINYINT(1) NOT NULL DEFAULT 0,
    can_edit    TINYINT(1) NOT NULL DEFAULT 0,
    can_delete  TINYINT(1) NOT NULL DEFAULT 0,
    can_export  TINYINT(1) NOT NULL DEFAULT 0,
    can_print   TINYINT(1) NOT NULL DEFAULT 0,
    can_approve TINYINT(1) NOT NULL DEFAULT 0,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_permissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_permissions (user_id, module_key)
) ENGINE=InnoDB;

-- Who can even see a module exists, independent of their own per-user permissions.
-- 'settings' defaults to main_admin_only (seeded below); every other module defaults 'everyone'.
CREATE TABLE module_locks (
    module_key  VARCHAR(30) PRIMARY KEY,
    scope       ENUM('everyone','main_admin_super_admin','main_admin_staff','main_admin_delivery','main_admin_super_admin_staff','main_admin_super_admin_delivery','main_admin_only') NOT NULL DEFAULT 'everyone',
    updated_by  INT UNSIGNED NULL,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_module_locks_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  VARCHAR(255) NOT NULL,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_password_resets_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE login_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    email       VARCHAR(150) NOT NULL,
    ip_address  VARCHAR(45) NOT NULL,
    user_agent  VARCHAR(255) NULL,
    status      ENUM('success','failed') NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_login_logs_lookup (email, ip_address, status, created_at)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    ip_address  VARCHAR(45) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_logs_user (user_id),
    INDEX idx_activity_logs_created (created_at)
) ENGINE=InnoDB;

-- =============================================================
-- MEMBERSHIP PACKAGES
-- =============================================================

CREATE TABLE membership_packages (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    slug                VARCHAR(120) NOT NULL UNIQUE,
    category            ENUM('regular','student','corporate','vip','premium') NOT NULL DEFAULT 'regular',
    duration_days       INT UNSIGNED NOT NULL,
    regular_price       DECIMAL(10,2) NOT NULL,
    offer_price         DECIMAL(10,2) NULL,
    discount_amount     DECIMAL(10,2) NULL,
    discount_percentage DECIMAL(5,2) NULL,
    offer_start_date    DATE NULL,
    offer_end_date      DATETIME NULL,
    offer_enabled       TINYINT(1) NOT NULL DEFAULT 0,
    badge               VARCHAR(30) NULL,
    image               VARCHAR(255) NULL,
    description         TEXT NULL,
    includes_trainer    TINYINT(1) NOT NULL DEFAULT 0,
    includes_locker     TINYINT(1) NOT NULL DEFAULT 0,
    includes_steam      TINYINT(1) NOT NULL DEFAULT 0,
    includes_sauna      TINYINT(1) NOT NULL DEFAULT 0,
    includes_diet_plan  TINYINT(1) NOT NULL DEFAULT 0,
    is_featured         TINYINT(1) NOT NULL DEFAULT 0,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    sort_order          INT UNSIGNED NOT NULL DEFAULT 0,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_packages_active (is_active, sort_order)
) ENGINE=InnoDB;

CREATE TABLE membership_package_features (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_id  INT UNSIGNED NOT NULL,
    feature_text VARCHAR(150) NOT NULL,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_package_features_package FOREIGN KEY (package_id) REFERENCES membership_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- TRAINERS
-- =============================================================

CREATE TABLE trainers (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NULL,
    name                VARCHAR(120) NOT NULL,
    slug                VARCHAR(150) NOT NULL UNIQUE,
    job_title           VARCHAR(100) NULL,
    gender              ENUM('male','female','other') NULL,
    phone               VARCHAR(30) NULL,
    email               VARCHAR(150) NULL,
    dob                 DATE NULL,
    joining_date        DATE NULL,
    specialization      VARCHAR(150) NULL,
    experience_years    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    certifications      VARCHAR(255) NULL,
    achievements        VARCHAR(255) NULL,
    languages_spoken     VARCHAR(255) NULL,
    salary              DECIMAL(10,2) NULL,
    monthly_pt_price    DECIMAL(10,2) NULL,
    hourly_rate         DECIMAL(10,2) NULL,
    offer_price         DECIMAL(10,2) NULL,
    offer_enabled       TINYINT(1) NOT NULL DEFAULT 0,
    offer_start_date    DATE NULL,
    offer_end_date      DATE NULL,
    max_members         SMALLINT UNSIGNED NULL,
    availability_status ENUM('available','busy','on_leave','offline') NOT NULL DEFAULT 'available',
    bio                 TEXT NULL,
    photo               VARCHAR(255) NULL,
    cover_photo         VARCHAR(255) NULL,
    schedule_notes      VARCHAR(255) NULL,
    facebook_url        VARCHAR(255) NULL,
    instagram_url       VARCHAR(255) NULL,
    linkedin_url        VARCHAR(255) NULL,
    display_order       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_featured         TINYINT(1) NOT NULL DEFAULT 0,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trainers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_trainers_active (is_active),
    INDEX idx_trainers_order (display_order)
) ENGINE=InnoDB;

CREATE TABLE trainer_gallery (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trainer_id  INT UNSIGNED NOT NULL,
    image_path  VARCHAR(255) NOT NULL,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trainer_gallery_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE trainer_reviews (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trainer_id  INT UNSIGNED NOT NULL,
    member_id   INT UNSIGNED NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL,
    comment     TEXT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trainer_reviews_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
    CONSTRAINT fk_trainer_reviews_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_trainer_member_review (trainer_id, member_id),
    CONSTRAINT chk_trainer_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE trainer_schedule (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trainer_id  INT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL COMMENT '0=Sunday..6=Saturday, matches PHP date(\'w\')',
    start_time  TIME NULL,
    end_time    TIME NULL,
    is_off      TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_trainer_schedule_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_trainer_day (trainer_id, day_of_week)
) ENGINE=InnoDB;

CREATE TABLE trainer_booking (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trainer_id    INT UNSIGNED NOT NULL,
    member_id     INT UNSIGNED NOT NULL,
    booking_date  DATE NOT NULL,
    start_time    TIME NOT NULL,
    end_time      TIME NOT NULL,
    status        ENUM('confirmed','cancelled','completed') NOT NULL DEFAULT 'confirmed',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trainer_booking_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
    CONSTRAINT fk_trainer_booking_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_trainer_slot (trainer_id, booking_date, start_time),
    INDEX idx_trainer_booking_member (member_id)
) ENGINE=InnoDB;

-- =============================================================
-- MEMBERS
-- =============================================================

CREATE TABLE members (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL UNIQUE,
    member_code         VARCHAR(20) NOT NULL UNIQUE,
    money_received_no   VARCHAR(20) NULL UNIQUE,
    photo               VARCHAR(255) NULL,
    dob                 DATE NULL,
    gender              ENUM('male','female','other') NULL,
    blood_group         VARCHAR(5) NULL,
    emergency_contact   VARCHAR(30) NULL,
    address             VARCHAR(255) NULL,
    height_cm           DECIMAL(5,2) NULL,
    weight_kg           DECIMAL(5,2) NULL,
    fitness_goal        VARCHAR(150) NULL,
    medical_notes       TEXT NULL,
    notify_email        TINYINT(1) NOT NULL DEFAULT 1,
    notify_promotions   TINYINT(1) NOT NULL DEFAULT 1,
    join_date           DATE NOT NULL,
    trainer_id          INT UNSIGNED NULL,
    preferred_package_id INT UNSIGNED NULL,
    registration_notes  TEXT NULL,
    reported_payment_method    ENUM('bkash','nagad','rocket','card','bank_transfer') NULL,
    reported_payment_reference VARCHAR(100) NULL,
    reported_payer_number      VARCHAR(30) NULL,
    locker_number       VARCHAR(20) NULL,
    status              ENUM('pending','active','suspended','frozen','expired') NOT NULL DEFAULT 'pending',
    qr_code             VARCHAR(255) NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_members_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE SET NULL,
    CONSTRAINT fk_members_preferred_package FOREIGN KEY (preferred_package_id) REFERENCES membership_packages(id) ON DELETE SET NULL,
    INDEX idx_members_status (status),
    INDEX idx_members_trainer (trainer_id)
) ENGINE=InnoDB;

CREATE TABLE member_subscriptions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id       INT UNSIGNED NOT NULL,
    package_id      INT UNSIGNED NOT NULL,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    price_paid      DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) NULL,
    notes           VARCHAR(255) NULL,
    status          ENUM('active','expired','frozen','cancelled') NOT NULL DEFAULT 'active',
    freeze_start    DATE NULL,
    freeze_end      DATE NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscriptions_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscriptions_package FOREIGN KEY (package_id) REFERENCES membership_packages(id) ON DELETE RESTRICT,
    CONSTRAINT fk_subscriptions_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_subscriptions_member (member_id),
    INDEX idx_subscriptions_end_date (end_date),
    INDEX idx_subscriptions_status (status)
) ENGINE=InnoDB;

CREATE TABLE bmi_records (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id   INT UNSIGNED NULL,
    height_cm   DECIMAL(5,2) NOT NULL,
    weight_kg   DECIMAL(5,2) NOT NULL,
    bmi         DECIMAL(4,1) NOT NULL,
    category    VARCHAR(30) NOT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bmi_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_bmi_member (member_id)
) ENGINE=InnoDB;

CREATE TABLE trainer_assignments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trainer_id      INT UNSIGNED NOT NULL,
    member_id       INT UNSIGNED NOT NULL,
    assigned_date   DATE NOT NULL,
    status          ENUM('active','ended') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_assignments_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_assignments_trainer (trainer_id),
    INDEX idx_assignments_member (member_id)
) ENGINE=InnoDB;

CREATE TABLE attendance (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id   INT UNSIGNED NOT NULL,
    check_in    DATETIME NOT NULL,
    check_out   DATETIME NULL,
    method      ENUM('qr','manual') NOT NULL DEFAULT 'manual',
    recorded_by INT UNSIGNED NULL,
    CONSTRAINT fk_attendance_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_attendance_member (member_id),
    INDEX idx_attendance_checkin (check_in)
) ENGINE=InnoDB;

-- =============================================================
-- STORE / INVENTORY
-- =============================================================

CREATE TABLE product_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   INT UNSIGNED NULL,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    image       VARCHAR(255) NULL,
    status      ENUM('active','hidden') NOT NULL DEFAULT 'active',
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    offer_enabled    TINYINT(1) NOT NULL DEFAULT 0,
    offer_percent    DECIMAL(5,2) NULL,
    offer_start_date DATE NULL,
    offer_end_date   DATE NULL,
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES product_categories(id) ON DELETE SET NULL,
    INDEX idx_categories_parent (parent_id)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    contact_person  VARCHAR(120) NULL,
    phone           VARCHAR(30) NULL,
    email           VARCHAR(150) NULL,
    address         VARCHAR(255) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE brands (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    slug            VARCHAR(120) NOT NULL UNIQUE,
    logo            VARCHAR(255) NULL,
    description     TEXT NULL,
    offer_enabled    TINYINT(1) NOT NULL DEFAULT 0,
    offer_percent    DECIMAL(5,2) NULL,
    offer_start_date DATE NULL,
    offer_end_date   DATE NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED NOT NULL,
    brand_id        INT UNSIGNED NULL,
    supplier_id     INT UNSIGNED NULL,
    sku             VARCHAR(50) NOT NULL UNIQUE,
    barcode         VARCHAR(50) NULL UNIQUE,
    name            VARCHAR(150) NOT NULL,
    slug            VARCHAR(180) NOT NULL UNIQUE,
    description     TEXT NULL,
    buying_price    DECIMAL(10,2) NOT NULL DEFAULT 0,
    selling_price   DECIMAL(10,2) NOT NULL,
    stock_qty       INT NOT NULL DEFAULT 0,
    min_stock       INT NOT NULL DEFAULT 5,
    expiry_date     DATE NULL,
    image           VARCHAR(255) NULL,
    status          ENUM('draft','published','hidden') NOT NULL DEFAULT 'published',
    offer_price     DECIMAL(10,2) NULL,
    offer_enabled   TINYINT(1) NOT NULL DEFAULT 0,
    offer_start_date DATE NULL,
    offer_end_date  DATE NULL,
    shipping_charge DECIMAL(10,2) NULL,
    ingredients     TEXT NULL,
    nutrition_facts TEXT NULL,
    allow_preorder  TINYINT(1) NOT NULL DEFAULT 0,
    bogo_enabled    TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Buy One Get One — every 2nd unit in a cart line is free',
    is_featured     TINYINT(1) NOT NULL DEFAULT 0,
    is_archived     TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Retired but kept for historical order records — orthogonal to status',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    INDEX idx_products_category (category_id),
    INDEX idx_products_brand (brand_id),
    INDEX idx_products_status (status),
    INDEX idx_products_stock (stock_qty)
) ENGINE=InnoDB;

-- =============================================================
-- PRODUCT CATALOG: dynamic attributes/variants (unlimited, admin-defined —
-- no hardcoded sizes/colors/weights). A "simple" product with no variants
-- keeps selling directly off its own selling_price/stock_qty/sku above;
-- products with variants sell through product_variants instead.
-- =============================================================

CREATE TABLE product_attributes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL UNIQUE,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE attribute_values (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_id    INT UNSIGNED NOT NULL,
    value           VARCHAR(100) NOT NULL,
    sort_order      INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_attribute_values_attribute FOREIGN KEY (attribute_id) REFERENCES product_attributes(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_attribute_value (attribute_id, value),
    INDEX idx_attribute_values_attribute (attribute_id)
) ENGINE=InnoDB;

-- Which attributes a given product uses (drives the variant-builder UI before any variant exists).
CREATE TABLE product_attribute_links (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id   INT UNSIGNED NOT NULL,
    attribute_id INT UNSIGNED NOT NULL,
    sort_order   INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_product_attribute_links_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_product_attribute_links_attribute FOREIGN KEY (attribute_id) REFERENCES product_attributes(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_product_attribute (product_id, attribute_id)
) ENGINE=InnoDB;

CREATE TABLE product_variants (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    sku         VARCHAR(50) NOT NULL UNIQUE,
    barcode     VARCHAR(50) NULL UNIQUE,
    price       DECIMAL(10,2) NULL COMMENT 'NULL falls back to the parent product selling_price',
    offer_price DECIMAL(10,2) NULL,
    stock_qty   INT NOT NULL DEFAULT 0,
    weight      DECIMAL(10,3) NULL COMMENT 'kg',
    image       VARCHAR(255) NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_variants_product (product_id)
) ENGINE=InnoDB;

-- Each variant is defined as a combination of attribute values (e.g. Size=Medium + Color=Red).
CREATE TABLE product_variant_values (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variant_id          INT UNSIGNED NOT NULL,
    attribute_value_id  INT UNSIGNED NOT NULL,
    CONSTRAINT fk_variant_values_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    CONSTRAINT fk_variant_values_attribute_value FOREIGN KEY (attribute_value_id) REFERENCES attribute_values(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_variant_value (variant_id, attribute_value_id),
    INDEX idx_variant_values_variant (variant_id)
) ENGINE=InnoDB;

CREATE TABLE variant_images (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variant_id  INT UNSIGNED NOT NULL,
    image_path  VARCHAR(255) NOT NULL,
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_variant_images_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_variant_images_variant (variant_id)
) ENGINE=InnoDB;

CREATE TABLE product_tags (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(60) NOT NULL,
    slug    VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE product_tag_map (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    tag_id      INT UNSIGNED NOT NULL,
    CONSTRAINT fk_product_tag_map_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_product_tag_map_tag FOREIGN KEY (tag_id) REFERENCES product_tags(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_product_tag (product_id, tag_id)
) ENGINE=InnoDB;

-- Admin-curated override/supplement to the automatic relatedProducts()/frequentlyBoughtWith() algorithms.
CREATE TABLE related_products (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id          INT UNSIGNED NOT NULL,
    related_product_id  INT UNSIGNED NOT NULL,
    sort_order          INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_related_products_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_related_products_related FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_related_product (product_id, related_product_id)
) ENGINE=InnoDB;

-- Site-wide or scoped time-boxed discount — highest priority in the pricing engine (see Product::withComputedOffer()).
CREATE TABLE flash_sales (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    discount_percent DECIMAL(5,2) NOT NULL,
    scope           ENUM('all','category','brand','product') NOT NULL DEFAULT 'all',
    scope_id        INT UNSIGNED NULL COMMENT 'category_id/brand_id/product_id depending on scope — no FK (polymorphic)',
    starts_at       DATETIME NOT NULL,
    ends_at         DATETIME NOT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_flash_sales_window (is_active, starts_at, ends_at)
) ENGINE=InnoDB;

-- A curated set of products sold together at a fixed combined price — auto-detected in the cart, not a separate SKU.
CREATE TABLE bundles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    slug        VARCHAR(180) NOT NULL UNIQUE,
    bundle_price DECIMAL(10,2) NOT NULL,
    image       VARCHAR(255) NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    starts_at   DATETIME NULL,
    ends_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bundle_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bundle_id   INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    qty         INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_bundle_items_bundle FOREIGN KEY (bundle_id) REFERENCES bundles(id) ON DELETE CASCADE,
    CONSTRAINT fk_bundle_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_bundle_items_bundle (bundle_id)
) ENGINE=InnoDB;

CREATE TABLE product_images (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    image_path  VARCHAR(255) NOT NULL,
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_images_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE purchases (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id     INT UNSIGNED NULL,
    invoice_no      VARCHAR(50) NOT NULL UNIQUE,
    purchase_date   DATE NOT NULL,
    total_amount    DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    CONSTRAINT fk_purchases_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_purchases_date (purchase_date)
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    qty         INT NOT NULL,
    unit_cost   DECIMAL(10,2) NOT NULL,
    subtotal    DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_purchase_items_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    CONSTRAINT fk_purchase_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_purchase_items_purchase (purchase_id),
    INDEX idx_purchase_items_product (product_id)
) ENGINE=InnoDB;

-- Unified ledger of every stock change (orders, POS sales, purchases, manual
-- corrections) — reference_id is polymorphic (order/sale/purchase id depending
-- on type), so it deliberately has no FK of its own.
CREATE TABLE stock_movements (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    change_qty      INT NOT NULL COMMENT 'signed: positive = added, negative = removed',
    type            ENUM('order','sale','purchase','adjustment','return') NOT NULL,
    reference_id    INT UNSIGNED NULL COMMENT 'order_id/sale_id/purchase_id depending on type — no FK (polymorphic)',
    note            VARCHAR(255) NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_movements_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_stock_movements_product (product_id, created_at)
) ENGINE=InnoDB;

-- =============================================================
-- SALES (POS) / PAYMENTS
-- =============================================================

CREATE TABLE sales (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no      VARCHAR(50) NOT NULL UNIQUE,
    member_id       INT UNSIGNED NULL,
    sold_by         INT UNSIGNED NULL,
    sale_date       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount        DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax             DECIMAL(10,2) NOT NULL DEFAULT 0,
    total           DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method  ENUM('cash','card','bkash','nagad','rocket','bank_transfer') NOT NULL DEFAULT 'cash',
    payment_status  ENUM('paid','due','partial','refunded') NOT NULL DEFAULT 'paid',
    promotion_id    INT UNSIGNED NULL,
    CONSTRAINT fk_sales_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    CONSTRAINT fk_sales_sold_by FOREIGN KEY (sold_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sales_date (sale_date),
    INDEX idx_sales_member (member_id)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id     INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    qty         INT NOT NULL,
    unit_price  DECIMAL(10,2) NOT NULL,
    subtotal    DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_sale_items_sale (sale_id),
    INDEX idx_sale_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id       INT UNSIGNED NULL,
    subscription_id INT UNSIGNED NULL,
    sale_id         INT UNSIGNED NULL,
    trainer_id      INT UNSIGNED NULL,
    type            ENUM('admission','membership','store_sale','trainer_fee','expense','income','refund','locker_fine') NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    method          ENUM('cash','card','bkash','nagad','rocket','bank_transfer') NOT NULL DEFAULT 'cash',
    reference_no    VARCHAR(100) NULL,
    payer_number    VARCHAR(30) NULL,
    card_type       VARCHAR(30) NULL,
    card_last4      VARCHAR(4) NULL,
    bank_name       VARCHAR(100) NULL,
    account_number  VARCHAR(50) NULL,
    status          ENUM('completed','pending','failed') NOT NULL DEFAULT 'completed',
    paid_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    recorded_by     INT UNSIGNED NULL,
    CONSTRAINT fk_payments_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    CONSTRAINT fk_payments_subscription FOREIGN KEY (subscription_id) REFERENCES member_subscriptions(id) ON DELETE SET NULL,
    CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,
    CONSTRAINT fk_payments_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE SET NULL,
    CONSTRAINT fk_payments_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_payments_type (type),
    INDEX idx_payments_paid_at (paid_at),
    INDEX idx_payments_reference (reference_no)
) ENGINE=InnoDB;

CREATE TABLE expenses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category        VARCHAR(100) NOT NULL,
    description     VARCHAR(255) NULL,
    amount          DECIMAL(10,2) NOT NULL,
    expense_date    DATE NOT NULL,
    recorded_by     INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_expenses_date (expense_date)
) ENGINE=InnoDB;

-- =============================================================
-- PROMOTIONS
-- =============================================================

CREATE TABLE promotions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150) NOT NULL,
    code            VARCHAR(50) NULL UNIQUE,
    description     VARCHAR(255) NULL,
    discount_type   ENUM('percent','fixed','bogo','free_item') NOT NULL DEFAULT 'percent',
    discount_value  DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_discount_amount DECIMAL(10,2) NULL,
    applies_to      ENUM('membership','product','trainer','both') NOT NULL DEFAULT 'both',
    min_purchase    DECIMAL(10,2) NOT NULL DEFAULT 0,
    usage_limit     INT UNSIGNED NULL,
    per_customer_limit INT UNSIGNED NULL,
    used_count      INT UNSIGNED NOT NULL DEFAULT 0,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_promotions_active (is_active, start_date, end_date)
) ENGINE=InnoDB;

CREATE TABLE coupon_usages (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promotion_id    INT UNSIGNED NOT NULL,
    member_id       INT UNSIGNED NULL,
    sale_id         INT UNSIGNED NULL,
    subscription_id INT UNSIGNED NULL,
    order_id        INT UNSIGNED NULL,
    used_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_coupon_usages_promotion FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
    CONSTRAINT fk_coupon_usages_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    CONSTRAINT fk_coupon_usages_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,
    CONSTRAINT fk_coupon_usages_subscription FOREIGN KEY (subscription_id) REFERENCES member_subscriptions(id) ON DELETE SET NULL,
    INDEX idx_coupon_usages_promotion (promotion_id)
) ENGINE=InnoDB;

ALTER TABLE sales
    ADD CONSTRAINT fk_sales_promotion FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL;

-- =============================================================
-- PUBLIC E-COMMERCE STORE: cart, wishlist, orders, reviews
-- Deliberately separate from the POS sales/sale_items pair above —
-- online orders need a delivery address + a multi-step status
-- workflow (and can be cancelled after the fact, unlike a completed
-- walk-in sale), so stock is decremented in application code
-- (models/Order.php) rather than via a DB trigger.
-- =============================================================

-- Delivery zones/time-slots are defined before `orders` since orders FKs reference them.
CREATE TABLE delivery_zones (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    charge      DECIMAL(10,2) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE delivery_time_slots (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type        ENUM('delivery','pickup') NOT NULL,
    label       VARCHAR(100) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Which delivery zones a given delivery person (users.role = 'delivery') covers.
CREATE TABLE delivery_person_zones (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    zone_id     INT UNSIGNED NOT NULL,
    CONSTRAINT fk_delivery_person_zones_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_delivery_person_zones_zone FOREIGN KEY (zone_id) REFERENCES delivery_zones(id) ON DELETE CASCADE,
    UNIQUE KEY uq_delivery_person_zones (user_id, zone_id)
) ENGINE=InnoDB;

CREATE TABLE orders (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_no            VARCHAR(30) NOT NULL UNIQUE,
    user_id             INT UNSIGNED NULL,
    fulfillment_method  ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
    zone_id             INT UNSIGNED NULL,
    time_slot_id        INT UNSIGNED NULL,
    delivery_person_id  INT UNSIGNED NULL,
    pickup_pin          VARCHAR(6) NULL,
    guest_name          VARCHAR(120) NULL,
    guest_email         VARCHAR(150) NULL,
    guest_phone         VARCHAR(30) NULL,
    delivery_address    VARCHAR(255) NULL,
    delivery_city       VARCHAR(100) NULL,
    delivery_area       VARCHAR(100) NULL,
    delivery_postal_code VARCHAR(20) NULL,
    order_notes         TEXT NULL,
    admin_notes         TEXT NULL,
    subtotal            DECIMAL(10,2) NOT NULL,
    discount            DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping_charge     DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax                 DECIMAL(10,2) NOT NULL DEFAULT 0,
    total               DECIMAL(10,2) NOT NULL,
    promotion_id        INT UNSIGNED NULL,
    payment_method      VARCHAR(30) NOT NULL COMMENT 'validated in application code (CheckoutController) so new gateways (Stripe, SSLCommerz, AmarPay, ...) can be added without a migration',
    payment_status      ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    status              ENUM('pending','confirmed','preparing','packed','ready_for_pickup','picked_up','shipped','delivered','delivery_failed','cancelled','returned') NOT NULL DEFAULT 'pending',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_promotion FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_zone FOREIGN KEY (zone_id) REFERENCES delivery_zones(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_time_slot FOREIGN KEY (time_slot_id) REFERENCES delivery_time_slots(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_delivery_person FOREIGN KEY (delivery_person_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_orders_status (status),
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_delivery_person (delivery_person_id)
) ENGINE=InnoDB;

ALTER TABLE coupon_usages
    ADD CONSTRAINT fk_coupon_usages_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;

CREATE TABLE order_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NOT NULL,
    product_name    VARCHAR(150) NOT NULL,
    sku             VARCHAR(50) NOT NULL,
    qty             INT NOT NULL,
    unit_price      DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    discount_source VARCHAR(20) NULL COMMENT 'product_offer/category_offer/brand_offer/flash_sale/bogo — null = regular price',
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB;

-- coupon_usages is defined earlier (before orders existed) — add the online-order link now.
ALTER TABLE coupon_usages
    ADD COLUMN order_id INT UNSIGNED NULL AFTER subscription_id,
    ADD CONSTRAINT fk_coupon_usages_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;

CREATE TABLE shopping_cart (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    cart_token  VARCHAR(64) NULL,
    product_id  INT UNSIGNED NOT NULL,
    qty         INT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_cart_user_product (user_id, product_id),
    UNIQUE KEY uniq_cart_token_product (cart_token, product_id)
) ENGINE=InnoDB;

CREATE TABLE wishlist (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_wishlist (user_id, product_id)
) ENGINE=InnoDB;

-- "Notify me when back in stock" — works for guests too (email only, no account needed).
CREATE TABLE stock_notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    email       VARCHAR(150) NOT NULL,
    notified_at DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_notifications_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_stock_notifications (product_id, email)
) ENGINE=InnoDB;

CREATE TABLE customer_addresses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    label           VARCHAR(50) NOT NULL DEFAULT 'Home',
    full_name       VARCHAR(120) NOT NULL,
    phone           VARCHAR(30) NOT NULL,
    address         VARCHAR(255) NOT NULL,
    city            VARCHAR(100) NOT NULL,
    area            VARCHAR(100) NULL,
    postal_code     VARCHAR(20) NULL,
    is_default      TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payment_transactions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    method          VARCHAR(20) NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    reference_no    VARCHAR(100) NULL,
    payer_number    VARCHAR(30) NULL,
    status          ENUM('pending','verified','failed') NOT NULL DEFAULT 'pending',
    recorded_by     INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_tx_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_tx_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_status_history (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    status      VARCHAR(30) NOT NULL,
    note        VARCHAR(255) NULL,
    changed_by  INT UNSIGNED NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_history_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_status_history_changed_by FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE product_reviews (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    member_id   INT UNSIGNED NOT NULL,
    order_id    INT UNSIGNED NULL,
    rating      TINYINT UNSIGNED NOT NULL,
    comment     TEXT NULL,
    status      ENUM('pending','approved','hidden') NOT NULL DEFAULT 'approved',
    admin_reply TEXT NULL,
    replied_at  DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_product_reviews_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_product_reviews (product_id, member_id),
    CONSTRAINT chk_product_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE product_review_photos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_id   INT UNSIGNED NOT NULL,
    image_path  VARCHAR(255) NOT NULL,
    CONSTRAINT fk_review_photos_review FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE refunds (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    reason      VARCHAR(255) NOT NULL,
    refunded_by INT UNSIGNED NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_refunds_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_refunds_refunded_by FOREIGN KEY (refunded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================
-- CONTENT: BLOG / GALLERY / TESTIMONIALS / FAQ / CONTACT / SETTINGS
-- =============================================================

CREATE TABLE blog_posts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id       INT UNSIGNED NULL,
    title           VARCHAR(200) NOT NULL,
    slug            VARCHAR(220) NOT NULL UNIQUE,
    category        ENUM('workout_tips','diet_tips','fitness_news','announcements') NOT NULL DEFAULT 'announcements',
    excerpt         VARCHAR(255) NULL,
    content         MEDIUMTEXT NOT NULL,
    featured_image  VARCHAR(255) NULL,
    status          ENUM('draft','published') NOT NULL DEFAULT 'draft',
    views           INT UNSIGNED NOT NULL DEFAULT 0,
    published_at    DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_blog_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_blog_posts_status (status, published_at),
    INDEX idx_blog_posts_category (category)
) ENGINE=InnoDB;

CREATE TABLE gallery (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NULL,
    category    ENUM('gym','events','competitions','transformation','team') NOT NULL DEFAULT 'gym',
    image_path  VARCHAR(255) NOT NULL,
    uploaded_by INT UNSIGNED NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gallery_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_gallery_category (category)
) ENGINE=InnoDB;

CREATE TABLE testimonials (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_name     VARCHAR(120) NOT NULL,
    photo           VARCHAR(255) NULL,
    rating          TINYINT UNSIGNED NOT NULL DEFAULT 5,
    message         VARCHAR(500) NOT NULL,
    is_featured     TINYINT(1) NOT NULL DEFAULT 0,
    is_approved     TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_testimonials_featured (is_featured, is_approved)
) ENGINE=InnoDB;

CREATE TABLE faqs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question    VARCHAR(255) NOT NULL,
    answer      TEXT NOT NULL,
    category    VARCHAR(60) NULL,
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_faqs_active (is_active, sort_order)
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    phone       VARCHAR(30) NULL,
    subject     VARCHAR(150) NULL,
    message     TEXT NOT NULL,
    status      ENUM('new','read','replied') NOT NULL DEFAULT 'new',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_messages_status (status)
) ENGINE=InnoDB;

CREATE TABLE settings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key  VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE free_trial_registrations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    phone       VARCHAR(30) NOT NULL,
    email       VARCHAR(150) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- TRIGGERS: keep product stock in sync with sales/purchases
-- =============================================================

DELIMITER //

CREATE TRIGGER trg_sale_items_after_insert
AFTER INSERT ON sale_items
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock_qty = stock_qty - NEW.qty
    WHERE id = NEW.product_id;
END//

CREATE TRIGGER trg_purchase_items_after_insert
AFTER INSERT ON purchase_items
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock_qty = stock_qty + NEW.qty
    WHERE id = NEW.product_id;
END//

DELIMITER ;
