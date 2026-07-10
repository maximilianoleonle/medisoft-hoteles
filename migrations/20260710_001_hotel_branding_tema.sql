-- Multi-diseno SaaS: tema visual por hotel. 'deleite' es el tema fundador
-- (Deleite Sereno, el diseno actual); 'cupertino' es el primer tema alterno.
-- Vive junto al branding porque se elige en Configuracion > Apariencia.

SET @tema_col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hotel_branding'
      AND COLUMN_NAME = 'tema'
);

SET @add_tema_col := IF(
    @tema_col_exists = 0,
    'ALTER TABLE hotel_branding ADD COLUMN tema VARCHAR(30) NOT NULL DEFAULT ''deleite'' AFTER login_style',
    'SELECT 1'
);
PREPARE stmt FROM @add_tema_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260710_001_hotel_branding_tema.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
