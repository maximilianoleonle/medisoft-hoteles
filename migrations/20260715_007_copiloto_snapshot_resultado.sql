-- Copiloto IA - Fase 5: cierre del circulo (resultado del ajuste).
-- Alcance:
-- - Al aplicar un ajuste origen='copiloto' se guarda en el propio registro la
--   foto del momento: ocupacion proyectada (%) y tarifa promedio por noche de
--   la ventana. Cuando la ventana ya paso, el resultado REAL se calcula
--   on-demand al renderizar (sin cron) y se muestra junto al ajuste y en el
--   consejo siguiente: "ocupacion 84% real vs 71% proyectada".
-- - Solo columnas informativas: no cambian el motor de precios ni tocan Caja.
-- - Idempotente: cada columna se agrega solo si no existe.

SET @migration_name := '20260715_007_copiloto_snapshot_resultado.sql';

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incrementos_tarifas' AND COLUMN_NAME = 'snapshot_ocupacion') = 0,
    'ALTER TABLE incrementos_tarifas ADD COLUMN snapshot_ocupacion DECIMAL(5,1) NULL DEFAULT NULL COMMENT ''Ocupacion proyectada (%) de la ventana al momento de aplicar el ajuste del copiloto'' AFTER aprobado_por',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incrementos_tarifas' AND COLUMN_NAME = 'snapshot_tarifa') = 0,
    'ALTER TABLE incrementos_tarifas ADD COLUMN snapshot_tarifa DECIMAL(10,2) NULL DEFAULT NULL COMMENT ''Tarifa promedio por noche-habitacion vendida en la ventana al momento de aplicar'' AFTER snapshot_ocupacion',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
