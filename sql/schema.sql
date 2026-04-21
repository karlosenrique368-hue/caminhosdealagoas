-- Caminhos de Alagoas — Schema completo
-- MySQL 8+ / utf8mb4

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ========================================================
-- ADMIN USERS
-- ========================================================
DROP TABLE IF EXISTS admin_users;
CREATE TABLE admin_users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('super','editor','operator') NOT NULL DEFAULT 'editor',
    avatar          VARCHAR(255) DEFAULT NULL,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at   DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- CATEGORIES (roteiros / pacotes / transfers)
-- ========================================================
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    slug        VARCHAR(140) NOT NULL UNIQUE,
    type        ENUM('roteiro','pacote','transfer','destino') NOT NULL DEFAULT 'roteiro',
    icon        VARCHAR(60) DEFAULT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_active (type, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- ROTEIROS / PASSEIOS
-- ========================================================
DROP TABLE IF EXISTS roteiros;
CREATE TABLE roteiros (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED DEFAULT NULL,
    title           VARCHAR(200) NOT NULL,
    slug            VARCHAR(220) NOT NULL UNIQUE,
    short_desc      VARCHAR(500) DEFAULT NULL,
    description     MEDIUMTEXT DEFAULT NULL,
    includes        TEXT DEFAULT NULL COMMENT 'JSON de itens inclusos',
    excludes        TEXT DEFAULT NULL COMMENT 'JSON de itens não inclusos',
    itinerary       TEXT DEFAULT NULL COMMENT 'JSON do roteiro dia a dia',
    duration_hours  INT DEFAULT NULL,
    duration_days   INT DEFAULT NULL,
    min_people      INT NOT NULL DEFAULT 1,
    max_people      INT NOT NULL DEFAULT 50,
    price           DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_pix       DECIMAL(10,2) DEFAULT NULL,
    price_children  DECIMAL(10,2) DEFAULT NULL,
    location        VARCHAR(200) DEFAULT NULL,
    meeting_point   VARCHAR(255) DEFAULT NULL,
    cover_image     VARCHAR(255) DEFAULT NULL,
    gallery         TEXT DEFAULT NULL COMMENT 'JSON array de imagens',
    tags            VARCHAR(255) DEFAULT NULL,
    status          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    featured        TINYINT(1) NOT NULL DEFAULT 0,
    views           INT UNSIGNED NOT NULL DEFAULT 0,
    meta_title      VARCHAR(160) DEFAULT NULL,
    meta_desc       VARCHAR(300) DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_status_featured (status, featured),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- PACOTES (viagens completas com hospedagem)
-- ========================================================
DROP TABLE IF EXISTS pacotes;
CREATE TABLE pacotes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED DEFAULT NULL,
    title           VARCHAR(200) NOT NULL,
    slug            VARCHAR(220) NOT NULL UNIQUE,
    short_desc      VARCHAR(500) DEFAULT NULL,
    description     MEDIUMTEXT DEFAULT NULL,
    highlights      TEXT DEFAULT NULL COMMENT 'JSON array de destaques',
    itinerary       TEXT DEFAULT NULL COMMENT 'JSON do roteiro dia a dia',
    includes        TEXT DEFAULT NULL,
    excludes        TEXT DEFAULT NULL,
    destination     VARCHAR(200) DEFAULT NULL,
    duration_days   INT NOT NULL DEFAULT 1,
    duration_nights INT NOT NULL DEFAULT 0,
    price           DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_pix       DECIMAL(10,2) DEFAULT NULL,
    installments    INT NOT NULL DEFAULT 1,
    cover_image     VARCHAR(255) DEFAULT NULL,
    gallery         TEXT DEFAULT NULL,
    tags            VARCHAR(255) DEFAULT NULL,
    status          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    featured        TINYINT(1) NOT NULL DEFAULT 0,
    views           INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_status_featured (status, featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- DATES (saídas programadas de roteiros/pacotes)
-- ========================================================
DROP TABLE IF EXISTS departures;
CREATE TABLE departures (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type     ENUM('roteiro','pacote') NOT NULL,
    entity_id       INT UNSIGNED NOT NULL,
    departure_date  DATE NOT NULL,
    departure_time  TIME DEFAULT NULL,
    return_date     DATE DEFAULT NULL,
    seats_total     INT NOT NULL DEFAULT 20,
    seats_sold      INT NOT NULL DEFAULT 0,
    price_override  DECIMAL(10,2) DEFAULT NULL,
    status          ENUM('open','closed','cancelled') NOT NULL DEFAULT 'open',
    note            VARCHAR(255) DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_date (departure_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- CUSTOMERS
-- ========================================================
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(160) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    phone       VARCHAR(30) DEFAULT NULL,
    document    VARCHAR(20) DEFAULT NULL COMMENT 'CPF/Passport',
    country     VARCHAR(60) DEFAULT 'Brasil',
    city        VARCHAR(120) DEFAULT NULL,
    state       VARCHAR(60) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    address     VARCHAR(255) DEFAULT NULL,
    birthdate   DATE DEFAULT NULL,
    notes       TEXT DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email (email),
    INDEX idx_document (document)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- BOOKINGS (reservas / pedidos)
-- ========================================================
DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código legível ex: CA-2026-0001',
    customer_id     INT UNSIGNED NOT NULL,
    entity_type     ENUM('roteiro','pacote','transfer') NOT NULL,
    entity_id       INT UNSIGNED NOT NULL,
    departure_id    INT UNSIGNED DEFAULT NULL,
    entity_title    VARCHAR(200) NOT NULL COMMENT 'Snapshot do título',
    adults          INT NOT NULL DEFAULT 1,
    children        INT NOT NULL DEFAULT 0,
    travel_date     DATE DEFAULT NULL,
    subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount        DECIMAL(10,2) NOT NULL DEFAULT 0,
    total           DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency        VARCHAR(6) NOT NULL DEFAULT 'BRL',
    payment_method  ENUM('pix','credit_card','boleto','bank_transfer') DEFAULT NULL,
    payment_status  ENUM('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
    payment_gateway VARCHAR(40) DEFAULT NULL,
    gateway_tx_id   VARCHAR(120) DEFAULT NULL,
    notes           TEXT DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paid_at         DATETIME DEFAULT NULL,
    cancelled_at    DATETIME DEFAULT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (departure_id) REFERENCES departures(id) ON DELETE SET NULL,
    INDEX idx_payment_status (payment_status),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_travel_date (travel_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- COUPONS
-- ========================================================
DROP TABLE IF EXISTS coupons;
CREATE TABLE coupons (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(40) NOT NULL UNIQUE,
    description     VARCHAR(200) DEFAULT NULL,
    type            ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    value           DECIMAL(10,2) NOT NULL DEFAULT 0,
    min_purchase    DECIMAL(10,2) DEFAULT NULL,
    max_uses        INT DEFAULT NULL,
    used_count      INT NOT NULL DEFAULT 0,
    valid_from      DATETIME DEFAULT NULL,
    valid_until     DATETIME DEFAULT NULL,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- TESTIMONIALS
-- ========================================================
DROP TABLE IF EXISTS testimonials;
CREATE TABLE testimonials (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    location    VARCHAR(120) DEFAULT NULL,
    avatar      VARCHAR(255) DEFAULT NULL,
    rating      TINYINT NOT NULL DEFAULT 5,
    content     TEXT NOT NULL,
    roteiro_id  INT UNSIGNED DEFAULT NULL,
    featured    TINYINT(1) NOT NULL DEFAULT 0,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (roteiro_id) REFERENCES roteiros(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- CONTACT MESSAGES
-- ========================================================
DROP TABLE IF EXISTS contact_messages;
CREATE TABLE contact_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(160) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    phone       VARCHAR(30) DEFAULT NULL,
    subject     VARCHAR(200) DEFAULT NULL,
    message     TEXT NOT NULL,
    status      ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
    ip          VARCHAR(45) DEFAULT NULL,
    user_agent  VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- NEWSLETTER
-- ========================================================
DROP TABLE IF EXISTS newsletter;
CREATE TABLE newsletter (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(190) NOT NULL UNIQUE,
    name        VARCHAR(120) DEFAULT NULL,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    ip          VARCHAR(45) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- SETTINGS (key/value platform config)
-- ========================================================
DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
    `key`       VARCHAR(100) NOT NULL PRIMARY KEY,
    `value`     TEXT DEFAULT NULL,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- ACTIVITY LOG
-- ========================================================
DROP TABLE IF EXISTS activity_log;
CREATE TABLE activity_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(80) NOT NULL,
    entity      VARCHAR(60) DEFAULT NULL,
    entity_id   INT UNSIGNED DEFAULT NULL,
    description VARCHAR(500) DEFAULT NULL,
    ip          VARCHAR(45) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
