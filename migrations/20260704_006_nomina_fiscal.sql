-- Nomina Core - Fase 6: motor legal/fiscal MX (base).
-- Alcance:
-- - Extiende el ENUM de origen de lineas congeladas con 'fiscal' (lineas de
--   ISR retenido, subsidio e IMSS obrero calculadas por el motor legal).
-- - NO agrega tablas: el motor consume nomina_reglas_legales (Fase 5) y
--   congela lo aplicado en reglas_snapshot_json del periodo.
-- - Frontera: esto es CALCULO INTERNO AUDITABLE. Sigue sin existir CFDI,
--   timbrado, dispersion bancaria ni nomina oficial timbrada.

SET @migration_name := '20260704_006_nomina_fiscal.sql';

DROP PROCEDURE IF EXISTS migrar_nomina_fase6_origen;

DELIMITER $$

CREATE PROCEDURE migrar_nomina_fase6_origen()
BEGIN
    DECLARE v_tipo TEXT;

    SELECT COLUMN_TYPE INTO v_tipo FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'nomina_periodo_conceptos'
      AND COLUMN_NAME = 'origen';

    IF v_tipo IS NOT NULL AND LOCATE('fiscal', v_tipo) = 0 THEN
        ALTER TABLE nomina_periodo_conceptos
            MODIFY COLUMN origen ENUM('salario','incidencia','ledger','manual','fiscal') NOT NULL DEFAULT 'manual';
    END IF;
END$$

CALL migrar_nomina_fase6_origen()$$

DROP PROCEDURE IF EXISTS migrar_nomina_fase6_origen$$

DELIMITER ;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual seguro (solo si no hay lineas origen='fiscal'):
-- ALTER TABLE nomina_periodo_conceptos MODIFY COLUMN origen ENUM('salario','incidencia','ledger','manual') NOT NULL DEFAULT 'manual';
-- DELETE FROM migrations WHERE nombre = '20260704_006_nomina_fiscal.sql';
