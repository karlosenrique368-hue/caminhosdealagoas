-- Migration 006 — Comissao por passeio/pacote + checkout em grupo (instituicao)

ALTER TABLE roteiros
    ADD COLUMN IF NOT EXISTS commission_percent DECIMAL(5,2) NULL,
    ADD COLUMN IF NOT EXISTS bookings_threshold INT UNSIGNED NULL;

ALTER TABLE pacotes
    ADD COLUMN IF NOT EXISTS commission_percent DECIMAL(5,2) NULL,
    ADD COLUMN IF NOT EXISTS bookings_threshold INT UNSIGNED NULL;

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS booking_mode ENUM('individual','grupo_instituicao') NOT NULL DEFAULT 'individual',
    ADD COLUMN IF NOT EXISTS participants LONGTEXT NULL,
    ADD COLUMN IF NOT EXISTS responsible_name VARCHAR(200) NULL,
    ADD COLUMN IF NOT EXISTS responsible_cpf VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS responsible_phone VARCHAR(30) NULL;

ALTER TABLE institutions
    ADD COLUMN IF NOT EXISTS allow_group_checkout TINYINT(1) NOT NULL DEFAULT 0;

UPDATE institutions SET allow_group_checkout=1 WHERE partner_type='instituicao';
