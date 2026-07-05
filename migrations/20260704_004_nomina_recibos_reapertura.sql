-- Nomina Core - Fase 4: recibos internos y reapertura controlada.
-- Alcance:
-- - nomina_recibos: recibos INTERNOS (no fiscales) emitidos desde el snapshot
--   de un periodo v2 aprobado, con folio consecutivo por negocio, totales y
--   lineas congeladas en JSON. Cancelables con motivo (cancelacion_uk libera
--   la unicidad por detalle para poder re-emitir).
-- - Extiende el ENUM de eventos de periodo con 'reapertura' (aprobado->cerrado,
--   solo si la config del negocio lo permite, con motivo y auditoria).
-- - Idempotente y aditivo. No toca Caja ni pagos.

SET @migration_name := '20260704_004_nomina_recibos_reapertura.sql';

CREATE TABLE IF NOT EXISTS nomina_recibos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    periodo_id INT NOT NULL,
    detalle_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    folio_numero INT NOT NULL,
    folio VARCHAR(30) NOT NULL,
    trabajador_nombre VARCHAR(160) NOT NULL,
    periodo_etiqueta VARCHAR(120) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    percepciones DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    deducciones DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    deducciones_informativas DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    neto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    lineas_json JSON NULL,
    estado ENUM('emitido','cancelado') NOT NULL DEFAULT 'emitido',
    cancelacion_uk INT NOT NULL DEFAULT 0,
    emitido_por INT DEFAULT NULL,
    emitido_at DATETIME NOT NULL,
    cancelado_por INT DEFAULT NULL,
    cancelado_at DATETIME DEFAULT NULL,
    motivo_cancelacion VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_nomina_recibos_folio (hotel_id, folio_numero),
    UNIQUE KEY uk_nomina_recibos_detalle_vigente (detalle_id, cancelacion_uk),
    KEY idx_nomina_recibos_hotel_periodo (hotel_id, periodo_id),
    KEY idx_nomina_recibos_trabajador (hotel_id, trabajador_id),
    CONSTRAINT chk_nomina_recibos_montos CHECK (percepciones >= 0 AND deducciones >= 0 AND neto >= 0),
    CONSTRAINT fk_nomina_recibos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomina_recibos_periodo
        FOREIGN KEY (periodo_id) REFERENCES trabajador_nomina_periodos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_nomina_recibos_detalle
        FOREIGN KEY (detalle_id) REFERENCES trabajador_nomina_periodo_detalles (id) ON DELETE RESTRICT,
    CONSTRAINT fk_nomina_recibos_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id) ON DELETE RESTRICT,
    CONSTRAINT fk_nomina_recibos_emitido_por
        FOREIGN KEY (emitido_por) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_nomina_recibos_cancelado_por
        FOREIGN KEY (cancelado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ampliar ENUM de eventos con 'reapertura' (aditivo, seguro).
DROP PROCEDURE IF EXISTS migrar_nomina_fase4_eventos;

DELIMITER $$

CREATE PROCEDURE migrar_nomina_fase4_eventos()
BEGIN
    DECLARE v_tipo TEXT;

    SELECT COLUMN_TYPE INTO v_tipo FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_nomina_periodo_eventos'
      AND COLUMN_NAME = 'tipo';

    IF v_tipo IS NOT NULL AND LOCATE('reapertura', v_tipo) = 0 THEN
        ALTER TABLE trabajador_nomina_periodo_eventos
            MODIFY COLUMN tipo ENUM('cierre','aprobacion','anulacion','reapertura') NOT NULL;
    END IF;
END$$

CALL migrar_nomina_fase4_eventos()$$

DROP PROCEDURE IF EXISTS migrar_nomina_fase4_eventos$$

DELIMITER ;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual seguro:
-- DROP TABLE IF EXISTS nomina_recibos;  -- solo si COUNT(*) = 0
-- ALTER TABLE trabajador_nomina_periodo_eventos MODIFY COLUMN tipo ENUM('cierre','aprobacion','anulacion') NOT NULL;
--   (solo si no existen eventos tipo 'reapertura')
-- DELETE FROM migrations WHERE nombre = '20260704_004_nomina_recibos_reapertura.sql';
