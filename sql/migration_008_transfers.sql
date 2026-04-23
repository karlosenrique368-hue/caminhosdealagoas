-- Migration 008: post type Transfers
CREATE TABLE IF NOT EXISTS transfers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200) NOT NULL,
    slug            VARCHAR(220) NOT NULL UNIQUE,
    short_desc      VARCHAR(500) DEFAULT NULL,
    description     MEDIUMTEXT DEFAULT NULL,
    location_from   VARCHAR(200) DEFAULT NULL,
    location_to     VARCHAR(200) DEFAULT NULL,
    vehicle_type    VARCHAR(80)  DEFAULT NULL,
    capacity        INT NOT NULL DEFAULT 4,
    duration_minutes INT DEFAULT NULL,
    distance_km     INT DEFAULT NULL,
    price           DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_pix       DECIMAL(10,2) DEFAULT NULL,
    one_way         TINYINT(1) NOT NULL DEFAULT 1,
    includes        TEXT DEFAULT NULL COMMENT 'JSON',
    cover_image     VARCHAR(255) DEFAULT NULL,
    gallery         TEXT DEFAULT NULL COMMENT 'JSON array',
    tags            VARCHAR(255) DEFAULT NULL,
    status          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    featured        TINYINT(1) NOT NULL DEFAULT 0,
    views           INT UNSIGNED NOT NULL DEFAULT 0,
    meta_title      VARCHAR(160) DEFAULT NULL,
    meta_desc       VARCHAR(300) DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_featured (status, featured),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permitir transfers no enum de departures (saídas e vagas)
ALTER TABLE departures
    MODIFY entity_type ENUM('roteiro','pacote','transfer') NOT NULL;

-- Seed de exemplo
INSERT INTO transfers (title, slug, short_desc, description, location_from, location_to, vehicle_type, capacity, duration_minutes, distance_km, price, price_pix, one_way, status, featured)
VALUES
('Aeroporto Maceió → Maragogi', 'aeroporto-maceio-maragogi', 'Transfer privativo direto do aeroporto até as praias paradisíacas de Maragogi.', 'Receba nosso motorista no desembarque com placa personalizada. Veículo confortável, água gelada e Wi-Fi a bordo.', 'Aeroporto Zumbi dos Palmares (MCZ)', 'Maragogi - AL', 'SUV / Van executiva', 6, 180, 130, 480.00, 432.00, 1, 'published', 1),
('Maceió → São Miguel dos Milagres', 'maceio-sao-miguel-milagres', 'Saia de Maceió rumo à Costa dos Corais com conforto e segurança.', 'Transfer privativo com motorista bilingue opcional. Roteiro flexível.', 'Maceió - AL', 'São Miguel dos Milagres - AL', 'SUV', 4, 120, 105, 380.00, 342.00, 1, 'published', 0),
('Aeroporto MCZ → Hotéis Maceió', 'aeroporto-mcz-hoteis-maceio', 'Translado curto e rápido entre o aeroporto e a orla de Maceió.', 'Atendimento 24h. Acompanhamento de voo em tempo real.', 'Aeroporto Zumbi dos Palmares (MCZ)', 'Pajuçara / Ponta Verde', 'Sedan executivo', 3, 30, 22, 130.00, 117.00, 1, 'published', 0)
ON DUPLICATE KEY UPDATE updated_at=CURRENT_TIMESTAMP;
