-- v12.16 - Macaiok Vivências Pedagógicas + Mercado Pago production fields

ALTER TABLE institutions
    ADD COLUMN IF NOT EXISTS program ENUM('parceiros','macaiok') NOT NULL DEFAULT 'parceiros' AFTER partner_type,
    ADD COLUMN IF NOT EXISTS school_code VARCHAR(40) NULL AFTER referral_code,
    ADD COLUMN IF NOT EXISTS coordinator_name VARCHAR(140) NULL AFTER contact_name,
    ADD COLUMN IF NOT EXISTS allow_group_checkout TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS parent_share_note TEXT NULL AFTER notes;

UPDATE institutions SET program='parceiros' WHERE program IS NULL OR program='';
UPDATE institutions SET program='macaiok', type='escola', partner_type='instituicao', allow_group_checkout=1 WHERE type='escola' AND partner_type='instituicao' AND name LIKE '%Macaiok%';

INSERT IGNORE INTO settings (`key`, value) VALUES
('payment_client_id', ''),
('payment_client_secret', ''),
('macaiok_brand_name', 'Macaiok Vivências Pedagógicas'),
('macaiok_whatsapp', ''),
('macaiok_intro', 'Vivências pedagógicas, estudos do meio e saídas educativas com controle de pagamentos por escola.');