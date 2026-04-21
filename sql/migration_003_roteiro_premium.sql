-- Migration 003: Roteiro detail 100% premium
-- Adiciona campos de highlights + modo de disponibilidade
-- Safe to re-run: usa IF NOT EXISTS via workaround

ALTER TABLE roteiros
    ADD COLUMN highlights TEXT DEFAULT NULL COMMENT 'JSON array de pontos altos' AFTER description,
    ADD COLUMN availability_mode ENUM('fixed','open','on_request') NOT NULL DEFAULT 'fixed' COMMENT 'fixed=apenas datas listadas em departures | open=todas as datas futuras liberadas exceto bloqueadas | on_request=apenas sob consulta' AFTER featured;

ALTER TABLE pacotes
    ADD COLUMN availability_mode ENUM('fixed','open','on_request') NOT NULL DEFAULT 'fixed' AFTER featured;
