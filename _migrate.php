<?php
/**
 * Safe migration runner: checks existence before ALTER/CREATE.
 * Run via CLI or browser. Delete after successful run.
 */
require_once __DIR__ . '/src/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

function colExists(string $table, string $col): bool {
    $r = dbOne("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?", [$table,$col]);
    return (int)($r['c'] ?? 0) > 0;
}
function tblExists(string $table): bool {
    $r = dbOne("SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?", [$table]);
    return (int)($r['c'] ?? 0) > 0;
}
function run(string $label, string $sql): void {
    try { db()->exec($sql); echo "✓ $label\n"; }
    catch (\Throwable $e) { echo "✗ $label — " . $e->getMessage() . "\n"; }
}

echo "=== MIGRATION 002 (Premium Platform) ===\n\n";

// -- customers auth fields
foreach ([
    ['password_hash',"ALTER TABLE customers ADD COLUMN password_hash VARCHAR(255) NULL AFTER email"],
    ['verified',"ALTER TABLE customers ADD COLUMN verified TINYINT(1) NOT NULL DEFAULT 0"],
    ['reset_token',"ALTER TABLE customers ADD COLUMN reset_token VARCHAR(64) NULL"],
    ['reset_expires',"ALTER TABLE customers ADD COLUMN reset_expires DATETIME NULL"],
    ['avatar',"ALTER TABLE customers ADD COLUMN avatar VARCHAR(255) NULL"],
    ['latitude',"ALTER TABLE customers ADD COLUMN latitude DECIMAL(10,7) NULL"],
    ['longitude',"ALTER TABLE customers ADD COLUMN longitude DECIMAL(10,7) NULL"],
] as [$col,$sql]) {
    if (!colExists('customers',$col)) run("customers.$col", $sql);
    else echo "· customers.$col (exists)\n";
}

// -- roteiros
foreach ([
    ['latitude',"ALTER TABLE roteiros ADD COLUMN latitude DECIMAL(10,7) NULL"],
    ['longitude',"ALTER TABLE roteiros ADD COLUMN longitude DECIMAL(10,7) NULL"],
    ['difficulty',"ALTER TABLE roteiros ADD COLUMN difficulty ENUM('facil','moderado','dificil') NULL DEFAULT 'facil'"],
    ['rating_avg',"ALTER TABLE roteiros ADD COLUMN rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0"],
    ['rating_count',"ALTER TABLE roteiros ADD COLUMN rating_count INT NOT NULL DEFAULT 0"],
] as [$col,$sql]) {
    if (!colExists('roteiros',$col)) run("roteiros.$col", $sql); else echo "· roteiros.$col (exists)\n";
}

// -- pacotes
foreach ([
    ['latitude',"ALTER TABLE pacotes ADD COLUMN latitude DECIMAL(10,7) NULL"],
    ['longitude',"ALTER TABLE pacotes ADD COLUMN longitude DECIMAL(10,7) NULL"],
    ['rating_avg',"ALTER TABLE pacotes ADD COLUMN rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0"],
    ['rating_count',"ALTER TABLE pacotes ADD COLUMN rating_count INT NOT NULL DEFAULT 0"],
] as [$col,$sql]) {
    if (!colExists('pacotes',$col)) run("pacotes.$col", $sql); else echo "· pacotes.$col (exists)\n";
}

// -- bookings
if (!colExists('bookings','customer_user_id')) run("bookings.customer_user_id", "ALTER TABLE bookings ADD COLUMN customer_user_id INT UNSIGNED NULL, ADD KEY idx_customer_user (customer_user_id)");
else echo "· bookings.customer_user_id (exists)\n";

// -- new tables
$tables = [
'wishlist' => "CREATE TABLE wishlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    entity_type ENUM('roteiro','pacote') NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_wish (customer_id, entity_type, entity_id),
    KEY idx_customer (customer_id),
    CONSTRAINT fk_wish_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
'waitlist' => "CREATE TABLE waitlist (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
'reviews' => "CREATE TABLE reviews (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
'refund_requests' => "CREATE TABLE refund_requests (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
'translations' => "CREATE TABLE translations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lang VARCHAR(8) NOT NULL,
    tkey VARCHAR(150) NOT NULL,
    value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_lang_key (lang, tkey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
'institutions' => "CREATE TABLE institutions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
'vouchers' => "CREATE TABLE vouchers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    qr_data TEXT NOT NULL,
    pdf_path VARCHAR(255) NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_voucher_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables as $t=>$sql) {
    if (!tblExists($t)) run("table $t", $sql); else echo "· table $t (exists)\n";
}

echo "\n=== DONE ===\n";
