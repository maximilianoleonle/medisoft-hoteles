-- Fase 7B-D-B-A - Tipo semantico COBRO para movimientos CxC.
--
-- OBJETIVO:
-- - Agregar el tipo COBRO a cuentas_por_cobrar_movimientos.tipo_movimiento.
-- - Mantener el cambio aditivo y sin modificar filas existentes.
-- - No registrar cobros.
-- - No tocar saldos CxC.
-- - No tocar Caja, cortes, reservaciones, pagos, abonos ni facturacion.
-- - No tocar /api/sync.
--
-- BACKUP REALIZADO ANTES DE EJECUTAR EN LOCAL:
-- - Archivo: backups/medisoft_hoteles_import_before_7b_d_b_a_cxc_cobro_enum_20260618_180420.sql
-- - SHA256: 906309EB73EB74B94A62FF493C1B1DAE214F82C02F253AA616C38FFEE3A6C21E
-- - Tamano: 1646040 bytes

SET @migration_name := '20260618_002_fase_7b_d_b_a_cxc_movimiento_cobro_enum.sql';

SET @current_tipo_movimiento := (
    SELECT COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cuentas_por_cobrar_movimientos'
      AND COLUMN_NAME = 'tipo_movimiento'
    LIMIT 1
);

SET @alter_cobro_sql := IF(
    @current_tipo_movimiento LIKE '%''COBRO''%',
    'SELECT ''OK: tipo COBRO ya existe en movimientos CxC'' AS resultado',
    'ALTER TABLE cuentas_por_cobrar_movimientos
        MODIFY tipo_movimiento ENUM(
            ''CREACION'',
            ''AJUSTE'',
            ''CANCELACION'',
            ''NOTA'',
            ''RECLASIFICACION'',
            ''COBRO''
        ) NOT NULL'
);

PREPARE stmt FROM @alter_cobro_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @cobro_enum_ok := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cuentas_por_cobrar_movimientos'
      AND COLUMN_NAME = 'tipo_movimiento'
      AND COLUMN_TYPE LIKE '%''COBRO''%'
);

SET @validation_sql := IF(
    @cobro_enum_ok = 1,
    'SELECT ''OK: tipo COBRO disponible para movimientos CxC'' AS resultado',
    'SELECT no_existe_tipo_cobro_cxc'
);

PREPARE stmt FROM @validation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1|enum-cobro'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual documentado:
--
-- Ejecutar solo con autorizacion explicita y si no existen movimientos tipo COBRO:
--
-- SELECT COUNT(*) AS movimientos_cobro
-- FROM cuentas_por_cobrar_movimientos
-- WHERE tipo_movimiento = 'COBRO';
--
-- ALTER TABLE cuentas_por_cobrar_movimientos
--     MODIFY tipo_movimiento ENUM(
--         'CREACION',
--         'AJUSTE',
--         'CANCELACION',
--         'NOTA',
--         'RECLASIFICACION'
--     ) NOT NULL;
--
-- DELETE FROM migrations
-- WHERE nombre = '20260618_002_fase_7b_d_b_a_cxc_movimiento_cobro_enum.sql';
--
-- Si existen movimientos COBRO reales, no revertir el enum sin fase formal de
-- anulacion/reconciliacion.
