-- UX-001: tipo de tarjeta (credito/debito) en pagos de check-in
-- Migracion ADITIVA, idempotente y multi-hotel.
--
-- Alcance:
--   - Agrega reservacion_pagos.tipo_tarjeta con el mismo tipo que ya usa
--     reservacion_abonos.tipo_tarjeta: ENUM('credito','debito','') NULL.
--   - NULL = pago anterior a esta migracion (la vista usa su fallback legacy).
--   - '' = pago que no es con tarjeta o sin tipo capturado.
--   - NO toca datos existentes, Caja, abonos, CxC ni /api/sync.
--
-- Rollback manual seguro (solo con autorizacion explicita):
--   ALTER TABLE reservacion_pagos DROP COLUMN tipo_tarjeta;
--   DELETE FROM migrations WHERE nombre = '20260702_001_reservacion_pagos_tipo_tarjeta.sql';

SET @migration_name := '20260702_001_reservacion_pagos_tipo_tarjeta.sql';

START TRANSACTION;

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'reservacion_pagos'
      AND COLUMN_NAME = 'tipo_tarjeta'
);

SET @ddl := IF(
    @col_exists = 0,
    'ALTER TABLE reservacion_pagos ADD COLUMN tipo_tarjeta ENUM(''credito'', ''debito'', '''') COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER referencia',
    'SELECT ''OK: reservacion_pagos.tipo_tarjeta ya existe'' AS resultado'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_final := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'reservacion_pagos'
      AND COLUMN_NAME = 'tipo_tarjeta'
);

SET @validation_sql := IF(
    @col_final = 1,
    'SELECT ''OK: estructura reservacion_pagos verificada'' AS resultado',
    'SELECT no_existe_columna_tipo_tarjeta_reservacion_pagos'
);

PREPARE stmt FROM @validation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
