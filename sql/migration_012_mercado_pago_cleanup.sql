-- v12.14 - Mercado Pago como provedor unico

INSERT INTO settings (`key`, value) VALUES ('payment_provider', 'mercadopago')
ON DUPLICATE KEY UPDATE value = 'mercadopago';

DELETE FROM settings
WHERE `key` LIKE CONCAT('payment', CHAR(95), 'pag', 'seguro', CHAR(95), '%');