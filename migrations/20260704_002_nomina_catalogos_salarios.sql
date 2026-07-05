-- Nomina Core - Fase 2: catalogos internos e historial salarial.
-- Alcance:
-- - Crea 5 catalogos por hotel: nomina_departamentos, nomina_puestos,
--   nomina_tipos_contrato, nomina_grupos (grupos de pago con periodicidad),
--   nomina_conceptos (percepciones/deducciones configurables por negocio).
-- - Crea trabajador_salarios: historial salarial con vigencias. Backfill
--   idempotente desde trabajadores.salario_base (vigencia abierta inicial).
-- - Extiende trabajadores con 4 columnas NULL-ables de asignacion
--   (puesto_id, departamento_id, tipo_contrato_id, grupo_nomina_id).
--   Aditivo: ningun flujo existente cambia; las columnas nacen NULL.
-- - NO toca Caja, snapshots de pre-nomina ni el ledger laboral.
-- - Idempotente: re-ejecutable sin duplicar filas ni columnas.

SET @migration_name := '20260704_002_nomina_catalogos_salarios.sql';

-- 1) Catalogos por hotel -------------------------------------------------

CREATE TABLE IF NOT EXISTS nomina_departamentos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200) DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_nomina_departamentos_hotel_nombre (hotel_id, nombre),
    KEY idx_nomina_departamentos_hotel_activo (hotel_id, activo),
    CONSTRAINT fk_nomina_departamentos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomina_departamentos_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_nomina_departamentos_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nomina_puestos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    departamento_id INT DEFAULT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200) DEFAULT NULL,
    salario_sugerido DECIMAL(12,2) DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_nomina_puestos_hotel_nombre (hotel_id, nombre),
    KEY idx_nomina_puestos_hotel_activo (hotel_id, activo),
    KEY idx_nomina_puestos_departamento (departamento_id),
    CONSTRAINT chk_nomina_puestos_salario CHECK (salario_sugerido IS NULL OR salario_sugerido >= 0),
    CONSTRAINT fk_nomina_puestos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomina_puestos_departamento
        FOREIGN KEY (departamento_id) REFERENCES nomina_departamentos (id) ON DELETE SET NULL,
    CONSTRAINT fk_nomina_puestos_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_nomina_puestos_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nomina_tipos_contrato (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200) DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_nomina_tipos_contrato_hotel_nombre (hotel_id, nombre),
    KEY idx_nomina_tipos_contrato_hotel_activo (hotel_id, activo),
    CONSTRAINT fk_nomina_tipos_contrato_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomina_tipos_contrato_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_nomina_tipos_contrato_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nomina_grupos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    periodicidad ENUM('semanal','quincenal','mensual') NOT NULL DEFAULT 'quincenal',
    dia_corte TINYINT DEFAULT NULL COMMENT 'semanal: dia ISO 1-7; quincenal/mensual: dia del mes 1-31',
    dia_pago TINYINT DEFAULT NULL COMMENT 'mismo criterio que dia_corte',
    descripcion VARCHAR(200) DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_nomina_grupos_hotel_nombre (hotel_id, nombre),
    KEY idx_nomina_grupos_hotel_activo (hotel_id, activo),
    CONSTRAINT chk_nomina_grupos_dia_corte CHECK (dia_corte IS NULL OR (dia_corte BETWEEN 1 AND 31)),
    CONSTRAINT chk_nomina_grupos_dia_pago CHECK (dia_pago IS NULL OR (dia_pago BETWEEN 1 AND 31)),
    CONSTRAINT fk_nomina_grupos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomina_grupos_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_nomina_grupos_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nomina_conceptos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    tipo ENUM('percepcion','deduccion') NOT NULL,
    clasificacion ENUM('sueldo','bono','comision','horas_extra','propina','destajo','descuento','anticipo','prestamo','ajuste','otro') NOT NULL DEFAULT 'otro',
    modo_calculo ENUM('manual','monto_fijo','por_cantidad') NOT NULL DEFAULT 'manual',
    monto_default DECIMAL(12,2) DEFAULT NULL,
    gravable_isr TINYINT(1) NOT NULL DEFAULT 0,
    gravable_imss TINYINT(1) NOT NULL DEFAULT 0,
    es_sistema TINYINT(1) NOT NULL DEFAULT 0,
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_nomina_conceptos_hotel_nombre (hotel_id, nombre),
    KEY idx_nomina_conceptos_hotel_activo (hotel_id, activo),
    KEY idx_nomina_conceptos_hotel_tipo (hotel_id, tipo, activo),
    CONSTRAINT chk_nomina_conceptos_monto CHECK (monto_default IS NULL OR monto_default >= 0),
    CONSTRAINT fk_nomina_conceptos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomina_conceptos_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_nomina_conceptos_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Historial salarial con vigencias ------------------------------------

CREATE TABLE IF NOT EXISTS trabajador_salarios (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    salario DECIMAL(12,2) NOT NULL,
    esquema ENUM('semanal','quincenal','mensual','diario','por_hora','por_evento') NOT NULL DEFAULT 'quincenal',
    vigente_desde DATE NOT NULL,
    vigente_hasta DATE DEFAULT NULL,
    motivo VARCHAR(200) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_trabajador_salarios_vigencia (hotel_id, trabajador_id, vigente_desde),
    KEY idx_trabajador_salarios_trabajador (hotel_id, trabajador_id, vigente_hasta),
    CONSTRAINT chk_trabajador_salarios_salario CHECK (salario >= 0),
    CONSTRAINT chk_trabajador_salarios_rango CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde),
    CONSTRAINT fk_trabajador_salarios_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_salarios_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id) ON DELETE RESTRICT,
    CONSTRAINT fk_trabajador_salarios_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill idempotente: vigencia abierta inicial desde salario_base.
INSERT INTO trabajador_salarios (hotel_id, trabajador_id, salario, esquema, vigente_desde, motivo, created_at)
SELECT t.hotel_id,
       t.id,
       t.salario_base,
       COALESCE(t.periodicidad_pago, 'quincenal'),
       COALESCE(t.fecha_alta, CURDATE()),
       'Backfill inicial desde salario_base (Fase 2 nomina core)',
       NOW()
FROM trabajadores t
WHERE t.salario_base IS NOT NULL
  AND t.salario_base > 0
  AND NOT EXISTS (
        SELECT 1 FROM trabajador_salarios ts WHERE ts.trabajador_id = t.id
  );

-- 3) Extension aditiva de trabajadores (columnas NULL-ables) --------------

DROP PROCEDURE IF EXISTS migrar_nomina_fase2_trabajadores;

DELIMITER $$

CREATE PROCEDURE migrar_nomina_fase2_trabajadores()
BEGIN
    DECLARE v_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores' AND COLUMN_NAME = 'puesto_id';
    IF v_count = 0 THEN
        ALTER TABLE trabajadores ADD COLUMN puesto_id INT NULL AFTER rol_laboral;
    END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores' AND COLUMN_NAME = 'departamento_id';
    IF v_count = 0 THEN
        ALTER TABLE trabajadores ADD COLUMN departamento_id INT NULL AFTER puesto_id;
    END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores' AND COLUMN_NAME = 'tipo_contrato_id';
    IF v_count = 0 THEN
        ALTER TABLE trabajadores ADD COLUMN tipo_contrato_id INT NULL AFTER departamento_id;
    END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores' AND COLUMN_NAME = 'grupo_nomina_id';
    IF v_count = 0 THEN
        ALTER TABLE trabajadores ADD COLUMN grupo_nomina_id INT NULL AFTER tipo_contrato_id;
    END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores'
      AND CONSTRAINT_NAME = 'fk_trabajadores_puesto' AND CONSTRAINT_TYPE = 'FOREIGN KEY';
    IF v_count = 0 THEN
        ALTER TABLE trabajadores
            ADD CONSTRAINT fk_trabajadores_puesto
                FOREIGN KEY (puesto_id) REFERENCES nomina_puestos (id) ON DELETE SET NULL;
    END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores'
      AND CONSTRAINT_NAME = 'fk_trabajadores_departamento' AND CONSTRAINT_TYPE = 'FOREIGN KEY';
    IF v_count = 0 THEN
        ALTER TABLE trabajadores
            ADD CONSTRAINT fk_trabajadores_departamento
                FOREIGN KEY (departamento_id) REFERENCES nomina_departamentos (id) ON DELETE SET NULL;
    END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores'
      AND CONSTRAINT_NAME = 'fk_trabajadores_tipo_contrato' AND CONSTRAINT_TYPE = 'FOREIGN KEY';
    IF v_count = 0 THEN
        ALTER TABLE trabajadores
            ADD CONSTRAINT fk_trabajadores_tipo_contrato
                FOREIGN KEY (tipo_contrato_id) REFERENCES nomina_tipos_contrato (id) ON DELETE SET NULL;
    END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores'
      AND CONSTRAINT_NAME = 'fk_trabajadores_grupo_nomina' AND CONSTRAINT_TYPE = 'FOREIGN KEY';
    IF v_count = 0 THEN
        ALTER TABLE trabajadores
            ADD CONSTRAINT fk_trabajadores_grupo_nomina
                FOREIGN KEY (grupo_nomina_id) REFERENCES nomina_grupos (id) ON DELETE SET NULL;
    END IF;

    SELECT COUNT(DISTINCT INDEX_NAME) INTO v_count FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores'
      AND INDEX_NAME = 'idx_trabajadores_hotel_grupo';
    IF v_count = 0 THEN
        ALTER TABLE trabajadores ADD INDEX idx_trabajadores_hotel_grupo (hotel_id, grupo_nomina_id);
    END IF;
END$$

CALL migrar_nomina_fase2_trabajadores()$$

DROP PROCEDURE IF EXISTS migrar_nomina_fase2_trabajadores$$

DELIMITER ;

-- 4) Registro de la migracion ---------------------------------------------

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual seguro (solo si los catalogos estan vacios):
-- ALTER TABLE trabajadores DROP FOREIGN KEY fk_trabajadores_puesto, DROP FOREIGN KEY fk_trabajadores_departamento,
--   DROP FOREIGN KEY fk_trabajadores_tipo_contrato, DROP FOREIGN KEY fk_trabajadores_grupo_nomina;
-- ALTER TABLE trabajadores DROP INDEX idx_trabajadores_hotel_grupo;
-- ALTER TABLE trabajadores DROP COLUMN puesto_id, DROP COLUMN departamento_id, DROP COLUMN tipo_contrato_id, DROP COLUMN grupo_nomina_id;
-- DROP TABLE IF EXISTS trabajador_salarios;   -- SOLO si COUNT(*) = 0 o es aceptable perder el backfill
-- DROP TABLE IF EXISTS nomina_puestos; DROP TABLE IF EXISTS nomina_conceptos; DROP TABLE IF EXISTS nomina_grupos;
-- DROP TABLE IF EXISTS nomina_tipos_contrato; DROP TABLE IF EXISTS nomina_departamentos;
-- DELETE FROM migrations WHERE nombre = '20260704_002_nomina_catalogos_salarios.sql';
