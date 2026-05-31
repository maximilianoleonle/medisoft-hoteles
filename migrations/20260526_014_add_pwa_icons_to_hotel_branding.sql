-- Microfase 6.2: iconos PWA por hotel para manifest dinamico.
-- Agrega rutas controladas para iconos 192x192 y 512x512.

SET @pwa_icon_192_col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hotel_branding'
      AND COLUMN_NAME = 'pwa_icon_192_url'
);

SET @add_pwa_icon_192_col := IF(
    @pwa_icon_192_col_exists = 0,
    'ALTER TABLE hotel_branding ADD COLUMN pwa_icon_192_url VARCHAR(255) NULL AFTER login_background_url',
    'SELECT 1'
);
PREPARE stmt FROM @add_pwa_icon_192_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @pwa_icon_512_col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hotel_branding'
      AND COLUMN_NAME = 'pwa_icon_512_url'
);

SET @add_pwa_icon_512_col := IF(
    @pwa_icon_512_col_exists = 0,
    'ALTER TABLE hotel_branding ADD COLUMN pwa_icon_512_url VARCHAR(255) NULL AFTER pwa_icon_192_url',
    'SELECT 1'
);
PREPARE stmt FROM @add_pwa_icon_512_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260526_014_add_pwa_icons_to_hotel_branding.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
