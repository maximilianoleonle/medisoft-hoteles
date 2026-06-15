-- Fase 2J - Constraints SQL controlados para proveedores.
-- Alcance:
-- - Agrega indice unico por hotel + RFC cuando RFC no es NULL.
-- - Agrega columna generada para bloquear nombres activos duplicados por hotel.
-- - No crea compras, pagos, cuentas por pagar ni documentos.
-- - No toca caja, inventario, movimientos financieros ni /api/sync.
-- - No modifica datos existentes; si hay duplicados, el ALTER debe fallar.

SET @migration_name := '20260615_001_fase_2j_unique_proveedores.sql';

SET @has_nombre_activo_key := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'proveedores'
      AND COLUMN_NAME = 'nombre_activo_key'
);

SET @sql := IF(
    @has_nombre_activo_key = 0,
    'ALTER TABLE proveedores
        ADD COLUMN nombre_activo_key VARCHAR(160) COLLATE utf8mb4_unicode_ci
        GENERATED ALWAYS AS (CASE WHEN activo = 1 THEN nombre ELSE NULL END) STORED
        AFTER nombre',
    'SELECT ''nombre_activo_key ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_uk_rfc := (
    SELECT COUNT(DISTINCT INDEX_NAME)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'proveedores'
      AND INDEX_NAME = 'uk_proveedores_hotel_rfc'
);

SET @sql := IF(
    @has_uk_rfc = 0,
    'ALTER TABLE proveedores
        ADD UNIQUE KEY uk_proveedores_hotel_rfc (hotel_id, rfc)',
    'SELECT ''uk_proveedores_hotel_rfc ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_uk_nombre_activo := (
    SELECT COUNT(DISTINCT INDEX_NAME)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'proveedores'
      AND INDEX_NAME = 'uk_proveedores_hotel_nombre_activo'
);

SET @sql := IF(
    @has_uk_nombre_activo = 0,
    'ALTER TABLE proveedores
        ADD UNIQUE KEY uk_proveedores_hotel_nombre_activo (hotel_id, nombre_activo_key)',
    'SELECT ''uk_proveedores_hotel_nombre_activo ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
SELECT
    @migration_name,
    COALESCE(MAX(batch), 0) + 1,
    NULL,
    'ejecutada',
    NOW()
FROM migrations
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual documentado:
-- 1) Confirmar que ninguna fase posterior depende de estos constraints.
-- 2) Quitar indices:
-- ALTER TABLE proveedores DROP INDEX uk_proveedores_hotel_nombre_activo;
-- ALTER TABLE proveedores DROP INDEX uk_proveedores_hotel_rfc;
-- 3) Quitar columna generada:
-- ALTER TABLE proveedores DROP COLUMN nombre_activo_key;
-- 4) Quitar registro de migracion:
-- DELETE FROM migrations
-- WHERE nombre = '20260615_001_fase_2j_unique_proveedores.sql';
