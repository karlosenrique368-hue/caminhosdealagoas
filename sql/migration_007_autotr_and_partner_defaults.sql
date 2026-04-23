-- Migration 007: tabela de cache de tradução automática de conteúdo dinâmico
CREATE TABLE IF NOT EXISTS auto_translations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_hash     CHAR(32) NOT NULL,
    lang            VARCHAR(8) NOT NULL,
    source_text     TEXT NOT NULL,
    translated_text TEXT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hash_lang (source_hash, lang),
    KEY idx_lang (lang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zerar defaults de comissão/desconto/gratuidade nos parceiros existentes que ainda estão nos legados (10/10)
UPDATE institutions
   SET commission_percent = 0,
       discount_percent   = 0,
       bookings_threshold = 0
 WHERE (commission_percent = 10 AND discount_percent = 0 AND bookings_threshold = 10);

-- Ajusta defaults da tabela
ALTER TABLE institutions
    MODIFY COLUMN commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    MODIFY COLUMN discount_percent   DECIMAL(5,2) NOT NULL DEFAULT 0,
    MODIFY COLUMN bookings_threshold INT UNSIGNED NOT NULL DEFAULT 0;
