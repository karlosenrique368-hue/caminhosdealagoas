-- ============================================================
-- Caminhos de Alagoas — Migration 002 (Premium Platform)
-- Adds: customer auth, wishlist, waitlist, reviews, refunds,
-- translations, currencies, institutions, vouchers, analytics fields
-- ============================================================

-- Add password + location fields to customers (idempotent via dbMigrate helper)
ALTER TABLE customers
    ADD COLUMN password_hash VARCHAR(255) NULL AFTER email,
    ADD COLUMN verified TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash,
    ADD COLUMN reset_token VARCHAR(64) NULL AFTER verified,
    ADD COLUMN reset_expires DATETIME NULL AFTER reset_token,
    ADD COLUMN avatar VARCHAR(255) NULL AFTER reset_expires,
    ADD COLUMN latitude DECIMAL(10,7) NULL,
    ADD COLUMN longitude DECIMAL(10,7) NULL;

-- Add geo + extras to roteiros
ALTER TABLE roteiros
    ADD COLUMN latitude DECIMAL(10,7) NULL,
    ADD COLUMN longitude DECIMAL(10,7) NULL,
    ADD COLUMN difficulty ENUM('facil','moderado','dificil') NULL DEFAULT 'facil',
    ADD COLUMN rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
    ADD COLUMN rating_count INT NOT NULL DEFAULT 0;

ALTER TABLE pacotes
    ADD COLUMN latitude DECIMAL(10,7) NULL,
    ADD COLUMN longitude DECIMAL(10,7) NULL,
    ADD COLUMN rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
    ADD COLUMN rating_count INT NOT NULL DEFAULT 0;

-- Wishlist
CREATE TABLE IF NOT EXISTS wishlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    entity_type ENUM('roteiro','pacote') NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_wish (customer_id, entity_type, entity_id),
    KEY idx_customer (customer_id),
    CONSTRAINT fk_wish_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Waitlist (when roteiro fully booked)
CREATE TABLE IF NOT EXISTS waitlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    phone VARCHAR(30) NULL,
    entity_type ENUM('roteiro','pacote') NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    departure_id INT UNSIGNED NULL,
    desired_date DATE NULL,
    notes TEXT NULL,
    status ENUM('waiting','notified','converted','cancelled') NOT NULL DEFAULT 'waiting',
    notified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_entity (entity_type, entity_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews (verified purchase)
CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NULL,
    entity_type ENUM('roteiro','pacote') NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    title VARCHAR(200) NULL,
    content TEXT NOT NULL,
    photos JSON NULL,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_entity (entity_type, entity_id),
    KEY idx_status (status),
    CONSTRAINT fk_review_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Refund requests
CREATE TABLE IF NOT EXISTS refund_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('em_analise','aprovado','negado','pago') NOT NULL DEFAULT 'em_analise',
    admin_note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    KEY idx_booking (booking_id),
    KEY idx_status (status),
    CONSTRAINT fk_refund_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_refund_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Translations
CREATE TABLE IF NOT EXISTS translations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lang VARCHAR(8) NOT NULL,
    tkey VARCHAR(150) NOT NULL,
    value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_lang_key (lang, tkey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Institutions / partners
CREATE TABLE IF NOT EXISTS institutions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    type ENUM('escola','empresa','ong','governo','outro') NOT NULL DEFAULT 'empresa',
    cnpj VARCHAR(20) NULL,
    contact_name VARCHAR(120) NULL,
    contact_email VARCHAR(160) NULL,
    contact_phone VARCHAR(30) NULL,
    logo VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vouchers
CREATE TABLE IF NOT EXISTS vouchers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    qr_data TEXT NOT NULL,
    pdf_path VARCHAR(255) NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_voucher_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Link bookings to customer accounts (for user dashboard)
ALTER TABLE bookings
    ADD COLUMN customer_user_id INT UNSIGNED NULL AFTER customer_id,
    ADD KEY idx_customer_user (customer_user_id);
