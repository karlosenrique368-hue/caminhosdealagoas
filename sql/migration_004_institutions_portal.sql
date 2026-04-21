-- Migration 004 — Portal da Instituição (multi-tenant leve)
-- Adiciona: login próprio para instituições, grupo de reservas, link entre bookings e instituição.

-- 1) Usuários da instituição (login independente do admin-geral)
CREATE TABLE IF NOT EXISTS institution_users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id  INT UNSIGNED NOT NULL,
    name            VARCHAR(140) NOT NULL,
    email           VARCHAR(190) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('owner','manager','viewer') NOT NULL DEFAULT 'manager'
        COMMENT 'owner=dono da conta; manager=gerencia reservas; viewer=somente leitura',
    active          TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at   DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    INDEX idx_institution (institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Configurações da instituição (desconto padrão, comissão, logo exibida em vouchers, etc.)
ALTER TABLE institutions
    ADD COLUMN IF NOT EXISTS discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0
        COMMENT 'Desconto automático aplicado a qualquer booking desta instituição',
    ADD COLUMN IF NOT EXISTS commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0
        COMMENT 'Comissão que a instituição recebe sobre as reservas indicadas',
    ADD COLUMN IF NOT EXISTS slug VARCHAR(140) DEFAULT NULL UNIQUE
        COMMENT 'Slug do link exclusivo: /parceiros/<slug>',
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL;

-- 3) Liga cada reserva a uma instituição (opcional)
ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS institution_id INT UNSIGNED DEFAULT NULL
        COMMENT 'Se a reserva veio por um parceiro/instituição',
    ADD COLUMN IF NOT EXISTS institution_user_id INT UNSIGNED DEFAULT NULL
        COMMENT 'Qual usuário da instituição criou a reserva',
    ADD INDEX idx_institution (institution_id);

-- 4) Pedidos de orçamento em grupo (instituição pede cotação customizada)
CREATE TABLE IF NOT EXISTS group_requests (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id        INT UNSIGNED NOT NULL,
    institution_user_id   INT UNSIGNED DEFAULT NULL,
    entity_type           ENUM('roteiro','pacote','custom') NOT NULL DEFAULT 'custom',
    entity_id             INT UNSIGNED DEFAULT NULL,
    title                 VARCHAR(200) NOT NULL,
    people                INT NOT NULL DEFAULT 10,
    desired_date          DATE DEFAULT NULL,
    message               TEXT DEFAULT NULL,
    status                ENUM('new','in_review','quoted','confirmed','declined','cancelled') NOT NULL DEFAULT 'new',
    quoted_total          DECIMAL(10,2) DEFAULT NULL,
    quoted_note           TEXT DEFAULT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_institution (institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Cupons privados por instituição (opcional — reaproveita tabela coupons)
ALTER TABLE coupons
    ADD COLUMN IF NOT EXISTS institution_id INT UNSIGNED DEFAULT NULL
        COMMENT 'Se != NULL, cupom só vale para esta instituição',
    ADD INDEX idx_institution (institution_id);
