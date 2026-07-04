-- Nomina Core - Fase 3: motor operativo v2 (incidencias, conceptos por linea,
-- periodos por grupo de pago).
-- Alcance:
-- - nomina_incidencias: incidencias normalizadas por concepto del catalogo,
--   con origen (manual/adaptador/api) y ciclo pendiente->aprobada/rechazada.
-- - nomina_periodo_conceptos: lineas normalizadas del snapshot de periodo
--   (percepciones/deducciones por trabajador), base de recibos y fases fiscales.
-- - Extiende trabajador_nomina_periodos (ADITIVO): grupo_nomina_id, motor
--   ('v1' pre-nomina existente / 'v2' motor nuevo) y reglas_snapshot_json
--   (config de nomina congelada al cierre).
-- - Reemplaza la UNIQUE (hotel, inicio, fin) por (hotel, grupo, inicio, fin):
--   permite nomina semanal y quincenal simultaneas en grupos distintos.
--   La validacion de duplicados/solape v1 por servicio se conserva; el motor
--   v2 valida solape por grupo ANTES de cerrar.
-- - NO modifica filas existentes (quedan motor='v1', grupo NULL).

SET @migration_name := '20260704_003_nomina_motor_v2.sql';

CREATE TABLE IF NOT EXISTS nomina_incidencias (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    concepto_id INT NOT NULL,
    fecha DATE NOT NULL,
    cantidad DECIMAL(8,2) DEFAULT NULL,
    monto DECIMAL(12,2) DEFAULT NULL,
    descripcion VARCHAR(200) DEFAULT NULL,
    origen ENUM('manual','adaptador','api') NOT NULL DEFAULT 'manual',
    referencia_origen VARCHAR(120) DEFAULT NULL,
    estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'aprobada',
    aprobado_por INT DEFAULT NULL,
    aprobado_at DATETIME DEFAULT NULL,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_nomina_incidencias_hotel_trab_fecha (hotel_id, trabajador_id, fecha),
    KEY idx_nomina_incidencias_hotel_estado (hotel_id, estado, fecha),
    KEY idx_nomina_incidencias_concepto (concepto_id),
    CONSTRAINT chk_nomina_incidencias_cantidad CHECK (cantidad IS NULL OR cantidad > 0),
    CONSTRAINT chk_nomina_incidencias_monto CHECK (monto IS NULL OR monto >= 0),
    CONSTRAINT fk_nomina_incidencias_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomina_incidencias_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id) ON DELETE RESTRICT,
    CONSTRAINT fk_nomina_incidencias_concepto
        FOREIGN KEY (concepto_id) REFERENCES nomina_conceptos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_nomina_incidencias_aprobado_por
        FOREIGN KEY (aprobado_por) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_nomina_incidencias_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_nomina_incidencias_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nomina_periodo_conceptos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    periodo_id INT NOT NULL,
    detalle_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    concepto_id INT DEFAULT NULL,
    concepto_nombre VARCHAR(120) NOT NULL,
    tipo ENUM('percepcion','deduccion') NOT NULL,
    clasificacion VARCHAR(30) NOT NULL DEFAULT 'otro',
    origen ENUM('salario','incidencia','ledger','manual') NOT NULL DEFAULT 'manual',
    cantidad DECIMAL(8,2) DEFAULT NULL,
    base DECIMAL(12,2) DEFAULT NULL,
    monto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    referencia VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_nomina_periodo_conceptos_periodo (periodo_id),
    KEY idx_nomina_periodo_conceptos_detalle (detalle_id),
    KEY idx_nomina_periodo_conceptos_hotel_trab (hotel_id, trabajador_id),
    CONSTRAINT chk_nomina_periodo_conceptos_monto CHECK (monto >= 0),
    CONSTRAINT fk_nomina_periodo_conceptos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomina_periodo_conceptos_periodo
        FOREIGN KEY (periodo_id) REFERENCES trabajador_nomina_periodos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_nomina_periodo_conceptos_detalle
        FOREIGN KEY (detalle_id) REFERENCES trabajador_nomina_periodo_detalles (id) ON DELETE RESTRICT,
    CONSTRAINT fk_nomina_periodo_conceptos_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id) ON DELETE RESTRICT,
    CONSTRAINT fk_nomina_periodo_conceptos_concepto
        FOREIGN KEY (concepto_id) REFERENCES nomina_conceptos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extension aditiva de trabajador_nomina_periodos + nueva unicidad por grupo.

DROP PROCEDURE IF EXISTS migrar_nomina_fase3_periodos;

DELIMITER $$

CREATE PROCEDURE migrar_nomina_fase3_periodos()
BEGIN
    DECLARE v_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodos' AND COLUMN_NAME = 'grupo_nomina_id';
    IF v_count = 0 THEN
        ALTER TABLE trabajador_nomina_periodos ADD COLUMN grupo_nomina_id INT NULL AFTER tipo_periodo;
    END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodos' AND COLUMN_NAME = 'motor';
    IF v_count = 0 THEN
        ALTER TABLE trabajador_nomina_periodos ADD COLUMN motor VARCHAR(10) NOT NULL DEFAULT 'v1' AFTER grupo_nomina_id;
    END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodos' AND COLUMN_NAME = 'reglas_snapshot_json';
    IF v_count = 0 THEN
        ALTER TABLE trabajador_nomina_periodos ADD COLUMN reglas_snapshot_json JSON NULL AFTER resumen_json;
    END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodos'
      AND CONSTRAINT_NAME = 'fk_trabajador_nomina_periodos_grupo' AND CONSTRAINT_TYPE = 'FOREIGN KEY';
    IF v_count = 0 THEN
        ALTER TABLE trabajador_nomina_periodos
            ADD CONSTRAINT fk_trabajador_nomina_periodos_grupo
                FOREIGN KEY (grupo_nomina_id) REFERENCES nomina_grupos (id) ON DELETE SET NULL;
    END IF;

    -- anulacion_uk: 0 mientras el periodo esta vivo; al anular toma el id del
    -- periodo. Asi la UNIQUE por grupo solo aplica a periodos vigentes y un
    -- rango anulado puede volver a cerrarse (correccion = anular + re-cerrar).
    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodos' AND COLUMN_NAME = 'anulacion_uk';
    IF v_count = 0 THEN
        ALTER TABLE trabajador_nomina_periodos ADD COLUMN anulacion_uk INT NOT NULL DEFAULT 0 AFTER motivo_anulacion;
    END IF;

    -- Backfill: periodos ya anulados liberan su rango.
    UPDATE trabajador_nomina_periodos SET anulacion_uk = id WHERE estado = 'anulado' AND anulacion_uk = 0;

    -- Retirar la unicidad intermedia de 4 columnas si existe (no distinguia anulados).
    SELECT COUNT(DISTINCT INDEX_NAME) INTO v_count FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodos'
      AND INDEX_NAME = 'uk_trabajador_nomina_periodo_hotel_grupo_rango';
    IF v_count > 0 THEN
        ALTER TABLE trabajador_nomina_periodos
            DROP INDEX uk_trabajador_nomina_periodo_hotel_grupo_rango;
    END IF;

    -- Nueva unicidad por grupo SOLO para periodos vigentes.
    SELECT COUNT(DISTINCT INDEX_NAME) INTO v_count FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodos'
      AND INDEX_NAME = 'uk_trabajador_nomina_periodo_grupo_rango_vigente';
    IF v_count = 0 THEN
        ALTER TABLE trabajador_nomina_periodos
            ADD UNIQUE KEY uk_trabajador_nomina_periodo_grupo_rango_vigente (hotel_id, grupo_nomina_id, fecha_inicio, fecha_fin, anulacion_uk);
    END IF;

    SELECT COUNT(DISTINCT INDEX_NAME) INTO v_count FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodos'
      AND INDEX_NAME = 'uk_trabajador_nomina_periodo_hotel_rango';
    IF v_count > 0 THEN
        ALTER TABLE trabajador_nomina_periodos
            DROP INDEX uk_trabajador_nomina_periodo_hotel_rango;
    END IF;
END$$

CALL migrar_nomina_fase3_periodos()$$

DROP PROCEDURE IF EXISTS migrar_nomina_fase3_periodos$$

DELIMITER ;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual seguro (solo sin periodos v2 ni incidencias):
-- ALTER TABLE trabajador_nomina_periodos ADD UNIQUE KEY uk_trabajador_nomina_periodo_hotel_rango (hotel_id, fecha_inicio, fecha_fin);
-- ALTER TABLE trabajador_nomina_periodos DROP INDEX uk_trabajador_nomina_periodo_hotel_grupo_rango;
-- ALTER TABLE trabajador_nomina_periodos DROP FOREIGN KEY fk_trabajador_nomina_periodos_grupo;
-- ALTER TABLE trabajador_nomina_periodos DROP COLUMN grupo_nomina_id, DROP COLUMN motor, DROP COLUMN reglas_snapshot_json;
-- DROP TABLE IF EXISTS nomina_periodo_conceptos;  -- solo si COUNT(*) = 0
-- DROP TABLE IF EXISTS nomina_incidencias;        -- solo si COUNT(*) = 0
-- DELETE FROM migrations WHERE nombre = '20260704_003_nomina_motor_v2.sql';
