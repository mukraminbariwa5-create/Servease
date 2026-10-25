-- =====================================================================
--  Servease database schema (MySQL 5.7+ / MariaDB 10.4+, XAMPP compatible)
--  Run through the installer at http://localhost/Servease/install
--  or import manually in phpMyAdmin (after creating the `servease` database).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS booking_issues, notifications, messages, conversations, reviews, payments, booking_events,
    bookings, quotes, jobs, saved_providers, portfolio_items, provider_days_off, provider_services,
    provider_profiles, categories, login_attempts, users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Users: one account table for clients, providers and admins
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role                ENUM('client','provider','admin') NOT NULL DEFAULT 'client',
    first_name          VARCHAR(80)  NOT NULL,
    last_name           VARCHAR(80)  NOT NULL,
    email               VARCHAR(190) NOT NULL,
    phone               VARCHAR(30)  NULL,
    password_hash       VARCHAR(255) NOT NULL,
    avatar              VARCHAR(255) NULL,
    city                VARCHAR(80)  NULL,
    status              ENUM('active','suspended') NOT NULL DEFAULT 'active',
    email_verified_at   DATETIME NULL,
    verify_token_hash   CHAR(64) NULL,
    reset_token_hash    CHAR(64) NULL,
    reset_expires_at    DATETIME NULL,
    last_login_at       DATETIME NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email         VARCHAR(190) NOT NULL,
    ip            VARCHAR(45)  NOT NULL,
    attempted_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_lookup (email, ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Service categories
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(80)  NOT NULL,
    slug         VARCHAR(80)  NOT NULL,
    icon         VARCHAR(40)  NOT NULL DEFAULT 'wrench',
    description  VARCHAR(255) NULL,
    sort_order   INT NOT NULL DEFAULT 0,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Provider profile (1:1 with a user whose role = provider)
-- Rating figures are denormalised for fast listing/sorting.
-- ---------------------------------------------------------------------
CREATE TABLE provider_profiles (
    user_id              INT UNSIGNED NOT NULL,
    category_id          INT UNSIGNED NULL,
    headline             VARCHAR(120) NULL,
    bio                  TEXT NULL,
    city                 VARCHAR(80) NULL,
    service_area         VARCHAR(160) NULL,
    hourly_rate          DECIMAL(10,2) NULL,
    years_experience     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    skills               VARCHAR(255) NULL,
    is_available         TINYINT(1) NOT NULL DEFAULT 1,
    work_days            VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6',
    work_start           TIME NOT NULL DEFAULT '08:00:00',
    work_end             TIME NOT NULL DEFAULT '18:00:00',
    verification_status  ENUM('unverified','pending','verified','rejected') NOT NULL DEFAULT 'unverified',
    verification_doc     VARCHAR(255) NULL,
    verification_note    VARCHAR(255) NULL,
    verified_at          DATETIME NULL,
    rating_avg           DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    rating_count         INT UNSIGNED NOT NULL DEFAULT 0,
    jobs_completed       INT UNSIGNED NOT NULL DEFAULT 0,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    KEY idx_pp_category (category_id),
    KEY idx_pp_city (city),
    KEY idx_pp_verification (verification_status),
    KEY idx_pp_rating (rating_avg),
    CONSTRAINT fk_pp_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE provider_services (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    provider_id  INT UNSIGNED NOT NULL,
    title        VARCHAR(120) NOT NULL,
    description  VARCHAR(255) NULL,
    price        DECIMAL(10,2) NOT NULL,
    price_unit   ENUM('fixed','hour','day','starting') NOT NULL DEFAULT 'fixed',
    duration_hours TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_services_provider (provider_id),
    CONSTRAINT fk_services_provider FOREIGN KEY (provider_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Single dates a pro has blocked (holidays, sick days), on top of their weekly hours
CREATE TABLE provider_days_off (
    provider_id  INT UNSIGNED NOT NULL,
    day          DATE NOT NULL,
    PRIMARY KEY (provider_id, day),
    CONSTRAINT fk_daysoff_provider FOREIGN KEY (provider_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE portfolio_items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    provider_id  INT UNSIGNED NOT NULL,
    image        VARCHAR(255) NOT NULL,
    caption      VARCHAR(160) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_portfolio_provider (provider_id),
    CONSTRAINT fk_portfolio_provider FOREIGN KEY (provider_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE saved_providers (
    user_id      INT UNSIGNED NOT NULL,
    provider_id  INT UNSIGNED NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, provider_id),
    KEY idx_saved_provider (provider_id),
    CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_provider FOREIGN KEY (provider_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Job requests posted by clients, and quotes sent by providers
-- ---------------------------------------------------------------------
CREATE TABLE jobs (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_id       INT UNSIGNED NOT NULL,
    category_id     INT UNSIGNED NULL,
    title           VARCHAR(140) NOT NULL,
    description     TEXT NOT NULL,
    city            VARCHAR(80) NOT NULL,
    budget_min      DECIMAL(10,2) NULL,
    budget_max      DECIMAL(10,2) NULL,
    preferred_date  DATE NULL,
    urgency         ENUM('flexible','this_week','urgent') NOT NULL DEFAULT 'flexible',
    status          ENUM('open','assigned','completed','cancelled') NOT NULL DEFAULT 'open',
    quotes_count    INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_jobs_status_created (status, created_at),
    KEY idx_jobs_category (category_id),
    KEY idx_jobs_client (client_id),
    CONSTRAINT fk_jobs_client FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_jobs_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quotes (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id        INT UNSIGNED NOT NULL,
    provider_id   INT UNSIGNED NOT NULL,
    amount        DECIMAL(10,2) NOT NULL,
    message       TEXT NOT NULL,
    days_needed   SMALLINT UNSIGNED NULL,
    status        ENUM('pending','accepted','declined','withdrawn') NOT NULL DEFAULT 'pending',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quotes_job_provider (job_id, provider_id),
    KEY idx_quotes_provider (provider_id),
    CONSTRAINT fk_quotes_job FOREIGN KEY (job_id) REFERENCES jobs (id) ON DELETE CASCADE,
    CONSTRAINT fk_quotes_provider FOREIGN KEY (provider_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Bookings: the agreed piece of work between a client and a provider
--   requested -> accepted -> in_progress -> completed
--             \-> declined         \-> cancelled
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_id       INT UNSIGNED NOT NULL,
    provider_id     INT UNSIGNED NOT NULL,
    job_id          INT UNSIGNED NULL,
    quote_id        INT UNSIGNED NULL,
    service_id      INT UNSIGNED NULL,
    title           VARCHAR(140) NOT NULL,
    details         TEXT NULL,
    scheduled_date  DATE NULL,
    scheduled_time  VARCHAR(20) NULL,
    duration_hours  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    address         VARCHAR(255) NULL,
    city            VARCHAR(80) NULL,
    amount          DECIMAL(10,2) NOT NULL DEFAULT 0,
    status          ENUM('requested','accepted','declined','in_progress','completed','cancelled') NOT NULL DEFAULT 'requested',
    payment_status  ENUM('unpaid','pending','paid','refunded') NOT NULL DEFAULT 'unpaid',
    cancel_reason   VARCHAR(255) NULL,
    proposed_date   DATE NULL,
    proposed_time   VARCHAR(20) NULL,
    proposed_by     INT UNSIGNED NULL,
    proposed_note   VARCHAR(255) NULL,
    reminder_sent_at DATETIME NULL,
    accepted_at     DATETIME NULL,
    started_at      DATETIME NULL,
    completed_at    DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bookings_client (client_id, status),
    KEY idx_bookings_provider (provider_id, status),
    KEY idx_bookings_created (created_at),
    CONSTRAINT fk_bookings_client FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_provider FOREIGN KEY (provider_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_job FOREIGN KEY (job_id) REFERENCES jobs (id) ON DELETE SET NULL,
    CONSTRAINT fk_bookings_quote FOREIGN KEY (quote_id) REFERENCES quotes (id) ON DELETE SET NULL,
    CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES provider_services (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Problem reports on a booking (no-show, quality, payment...), handled by admins
-- ---------------------------------------------------------------------
CREATE TABLE booking_issues (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id   INT UNSIGNED NOT NULL,
    reporter_id  INT UNSIGNED NOT NULL,
    reason       ENUM('no_show','quality','payment','damage','safety','other') NOT NULL DEFAULT 'other',
    details      TEXT NOT NULL,
    status       ENUM('open','resolved') NOT NULL DEFAULT 'open',
    admin_note   VARCHAR(255) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at  DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_issues_status (status, created_at),
    KEY idx_issues_booking (booking_id),
    CONSTRAINT fk_issues_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
    CONSTRAINT fk_issues_reporter FOREIGN KEY (reporter_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking_events (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id  INT UNSIGNED NOT NULL,
    actor_id    INT UNSIGNED NULL,
    event       VARCHAR(40) NOT NULL,
    note        VARCHAR(255) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_events_booking (booking_id),
    CONSTRAINT fk_events_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Payments. Default flow is "direct" (client pays the provider in cash /
-- GCash / Maya / bank and it is recorded here). Optional online flow via
-- PayMongo hosted checkout.
-- ---------------------------------------------------------------------
CREATE TABLE payments (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id    INT UNSIGNED NOT NULL,
    payer_id      INT UNSIGNED NOT NULL,
    payee_id      INT UNSIGNED NOT NULL,
    amount        DECIMAL(10,2) NOT NULL,
    method        ENUM('cash','gcash','maya','paypal','bank_transfer','paymongo') NOT NULL,
    channel       ENUM('direct','online') NOT NULL DEFAULT 'direct',
    status        ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
    reference     VARCHAR(120) NULL,
    external_id   VARCHAR(120) NULL,
    paid_at       DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_booking (booking_id),
    KEY idx_payments_external (external_id),
    KEY idx_payments_status (status, paid_at),
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Reviews: one per completed booking
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id      INT UNSIGNED NOT NULL,
    provider_id     INT UNSIGNED NOT NULL,
    client_id       INT UNSIGNED NOT NULL,
    rating          TINYINT UNSIGNED NOT NULL,
    comment         TEXT NULL,
    provider_reply  TEXT NULL,
    is_hidden       TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reviews_booking (booking_id),
    KEY idx_reviews_provider (provider_id, is_hidden, created_at),
    CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_provider FOREIGN KEY (provider_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_client FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Messaging: one conversation per pair of users
-- ---------------------------------------------------------------------
CREATE TABLE conversations (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_one_id      INT UNSIGNED NOT NULL,
    user_two_id      INT UNSIGNED NOT NULL,
    last_message_at  DATETIME NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_conversation_pair (user_one_id, user_two_id),
    KEY idx_conv_two (user_two_id),
    CONSTRAINT fk_conv_one FOREIGN KEY (user_one_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_two FOREIGN KEY (user_two_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id  INT UNSIGNED NOT NULL,
    sender_id        INT UNSIGNED NOT NULL,
    body             TEXT NOT NULL,
    read_at          DATETIME NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_conv (conversation_id, id),
    KEY idx_messages_unread (conversation_id, sender_id, read_at),
    CONSTRAINT fk_messages_conv FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- In-app notifications
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    type        VARCHAR(40) NOT NULL,
    title       VARCHAR(160) NOT NULL,
    body        VARCHAR(255) NULL,
    link        VARCHAR(255) NULL,
    read_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user (user_id, read_at, created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
