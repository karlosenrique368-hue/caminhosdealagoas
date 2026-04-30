-- v12.15 - Mercado Pago unico, relatorios e sincronizacao de fotos

INSERT INTO settings (`key`, value) VALUES ('payment_provider', 'mercadopago')
ON DUPLICATE KEY UPDATE value = 'mercadopago';

DELETE FROM settings
WHERE `key` LIKE CONCAT('payment', CHAR(95), 'pag', 'seguro', CHAR(95), '%');

SET @fallback_cover = 'uploads/roteiros/20260421_150746_0db8e8f8.jpg';
SET @fallback_gallery = '["uploads/roteiros/20260421_163430_07e7d780.jpg","uploads/roteiros/20260421_163430_1b43456f.jpg"]';
SET @marechal_cover = (SELECT cover_image FROM roteiros WHERE slug='tour-historico-marechal-la-rue' LIMIT 1);
SET @marechal_gallery = (SELECT gallery FROM roteiros WHERE slug='tour-historico-marechal-la-rue' LIMIT 1);
SET @source_cover = COALESCE(NULLIF(@marechal_cover, ''), @fallback_cover);
SET @source_gallery = COALESCE(NULLIF(@marechal_gallery, ''), @fallback_gallery);

UPDATE roteiros
SET cover_image = @source_cover,
    gallery = @source_gallery
WHERE slug = 'tour-historico-marechal-la-rue'
  AND (cover_image IS NULL OR cover_image = '' OR gallery IS NULL OR gallery = '');

UPDATE roteiros
SET cover_image = @source_cover,
    gallery = @source_gallery
WHERE slug <> 'tour-historico-marechal-la-rue';

UPDATE pacotes
SET cover_image = @source_cover,
    gallery = @source_gallery;
