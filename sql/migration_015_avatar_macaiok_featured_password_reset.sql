-- v12.17 — Avatar (customers/institution_users), curadoria Macaiok e recuperacao de senha
-- Aplicar com: php -r "..." (ver __apply_migration_015.php)

-- 1) Avatar para clientes e usuarios de parceiro/escola
ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) NULL AFTER phone;

ALTER TABLE institution_users
    ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) NULL AFTER email;

-- 2) Curadoria Macaiok: marca produtos visiveis no portal /macaiok
ALTER TABLE roteiros
    ADD COLUMN IF NOT EXISTS macaiok_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER featured;

ALTER TABLE pacotes
    ADD COLUMN IF NOT EXISTS macaiok_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER featured;

ALTER TABLE transfers
    ADD COLUMN IF NOT EXISTS macaiok_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER featured;

-- 3) Recuperacao de senha (cliente, parceiro/escola, admin)
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope ENUM('customer','institution','admin') NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(160) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token_hash),
    INDEX idx_scope_user (scope, user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Settings de marca Macaiok (paleta oficial enviada pela equipe)
INSERT IGNORE INTO settings (`key`, value) VALUES
('macaiok_color_sepia', '#2F1607'),
('macaiok_color_terracota', '#DA4A34'),
('macaiok_color_areia', '#FFFACF'),
('macaiok_color_origem', '#A9D750'),
('macaiok_color_mangue', '#324500'),
('macaiok_logo_horizontal', '/assets/img/macaiok/VerdeEscuro_Horizontal.png'),
('macaiok_logo_principal', '/assets/img/macaiok/VerdeEscuro_Principal.png'),
('macaiok_logo_selo', '/assets/img/macaiok/Adesivo7.png');
