-- Migration 005 — Parceiros (qualquer pessoa pode indicar)
-- Extende institutions para suportar individuos/familias/grupos/revendedores
-- Adiciona tracking de indicacao em bookings

-- ============ INSTITUTIONS -> PARCEIROS ============
ALTER TABLE institutions
    ADD COLUMN IF NOT EXISTS partner_type ENUM('individual','familia','grupo','instituicao','revendedor') NOT NULL DEFAULT 'individual' AFTER type,
    ADD COLUMN IF NOT EXISTS referral_code VARCHAR(16) NULL UNIQUE AFTER slug,
    ADD COLUMN IF NOT EXISTS cpf VARCHAR(20) NULL AFTER cnpj,
    ADD COLUMN IF NOT EXISTS rg VARCHAR(20) NULL AFTER cpf,
    ADD COLUMN IF NOT EXISTS birth_date DATE NULL AFTER rg,
    ADD COLUMN IF NOT EXISTS whatsapp VARCHAR(30) NULL AFTER contact_phone,
    ADD COLUMN IF NOT EXISTS bookings_threshold INT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'A cada N reservas pagas indicadas, +1 gratuidade',
    ADD COLUMN IF NOT EXISTS free_spots_earned INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS free_spots_used INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS commission_pending DECIMAL(10,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS commission_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS bookings_count_paid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'total historico p/ calcular proxima gratuidade';

-- Relaxa a ENUM 'type' existente (escola/empresa/ong/governo/outro) — se vier individual vira 'outro'
-- partner_type e a nova fonte de verdade

-- Gera referral_code para quem nao tem
UPDATE institutions
SET referral_code = UPPER(SUBSTRING(MD5(CONCAT(id,name,RAND())),1,8))
WHERE referral_code IS NULL OR referral_code = '';

-- ============ BOOKINGS — tracking indicacao + formulario ============
ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS referral_code VARCHAR(16) NULL AFTER institution_user_id,
    ADD COLUMN IF NOT EXISTS source ENUM('instagram','whatsapp','indicacao','google','outro') NULL AFTER referral_code,
    ADD COLUMN IF NOT EXISTS source_detail VARCHAR(200) NULL AFTER source COMMENT 'nome da pessoa que indicou (texto livre)',
    ADD COLUMN IF NOT EXISTS comorbidity TEXT NULL AFTER source_detail,
    ADD COLUMN IF NOT EXISTS booking_answers JSON NULL AFTER comorbidity,
    ADD COLUMN IF NOT EXISTS commission_value DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'valor de comissao calculado',
    ADD COLUMN IF NOT EXISTS commission_credited TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=ja creditado ao parceiro',
    ADD INDEX IF NOT EXISTS idx_bookings_ref (referral_code);

-- ============ CUSTOMERS — ampliar cadastro ============
ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS rg VARCHAR(20) NULL AFTER document,
    ADD COLUMN IF NOT EXISTS birth_date DATE NULL AFTER rg;
