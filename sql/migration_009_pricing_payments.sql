-- Migration 009: PIX parcelado, faixas etárias, moedas globais
-- 2026-04-26 v12.6

-- 1) Faixa "bebê" para roteiros e pacotes (criança já existe em roteiros)
ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS price_infant DECIMAL(10,2) NULL AFTER price_children;
ALTER TABLE pacotes  ADD COLUMN IF NOT EXISTS price_children DECIMAL(10,2) NULL AFTER price_pix;
ALTER TABLE pacotes  ADD COLUMN IF NOT EXISTS price_infant   DECIMAL(10,2) NULL AFTER price_children;

-- 2) Bookings: PIX parcelado + bebês + data escolhida no carrinho
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS infants TINYINT NOT NULL DEFAULT 0 AFTER children;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS installments TINYINT NULL AFTER payment_method;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS installment_amount DECIMAL(10,2) NULL AFTER installments;

-- 3) Carrinho com data pré-escolhida (sessão guarda travel_date no item)
-- Nada a alterar no schema — o carrinho vive em sessão

-- 4) Settings: moedas e fatores de preço por faixa
INSERT IGNORE INTO settings (`key`, value) VALUES
  ('currency_code',        'BRL'),
  ('currency_symbol',      'R$'),
  ('currency_locale',      'pt-BR'),
  ('price_factor_child',   '0.5'),
  ('price_factor_infant',  '0'),
  ('pix_installments_enabled', '1'),
  ('pix_installments_min_days', '7'),
  ('pix_installments_max', '12');
