-- Nomina Core - Fase 5: catalogos legales versionados por ejercicio.
-- Alcance:
-- - nomina_reglas_legales: catalogo GLOBAL (sin hotel_id: las reglas legales
--   son por pais, no por tenant) con vigencias por fecha, valor escalar
--   (DECIMAL 12,4) o valores_json (tablas de tramos ISR, cuotas IMSS),
--   fuente oficial y estado. UNIQUE (pais, tipo_regla, vigente_desde).
-- - nomina_reglas_legales_eventos: historial append-only de cambios
--   (quien, cuando, antes/despues). Obligatorio para auditoria legal.
-- - Semillas MINIMAS: solo valores estatutarios LFT estables (aguinaldo 15
--   dias, prima vacacional 25%, tabla de vacaciones reforma 2023) y
--   referencias 2025 de UMA/salario minimo marcadas "verificar". Las tablas
--   de ISR, cuotas IMSS y valores del ejercicio vigente se CAPTURAN desde el
--   panel SaaS: NINGUN valor legal vive en codigo de calculo.
-- - La escritura de este catalogo es EXCLUSIVA del panel Medisoft
--   (saas_admins); los negocios solo lo consumen.

SET @migration_name := '20260704_005_nomina_reglas_legales.sql';

CREATE TABLE IF NOT EXISTS nomina_reglas_legales (
    id INT NOT NULL AUTO_INCREMENT,
    pais CHAR(2) NOT NULL DEFAULT 'MX',
    tipo_regla VARCHAR(40) NOT NULL,
    ejercicio SMALLINT NOT NULL,
    vigente_desde DATE NOT NULL,
    vigente_hasta DATE DEFAULT NULL,
    valor DECIMAL(12,4) DEFAULT NULL,
    valores_json JSON DEFAULT NULL,
    descripcion VARCHAR(200) DEFAULT NULL,
    fuente VARCHAR(200) DEFAULT NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_nomina_reglas_pais_tipo_desde (pais, tipo_regla, vigente_desde),
    KEY idx_nomina_reglas_tipo_vigencia (pais, tipo_regla, estado, vigente_desde),
    KEY idx_nomina_reglas_ejercicio (pais, ejercicio),
    CONSTRAINT chk_nomina_reglas_valor CHECK (valor IS NULL OR valor >= 0),
    CONSTRAINT chk_nomina_reglas_rango CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde),
    CONSTRAINT fk_nomina_reglas_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_nomina_reglas_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nomina_reglas_legales_eventos (
    id INT NOT NULL AUTO_INCREMENT,
    regla_id INT NOT NULL,
    accion ENUM('creada','actualizada','desactivada','reactivada') NOT NULL,
    datos_antes JSON DEFAULT NULL,
    datos_despues JSON DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_nomina_reglas_eventos_regla (regla_id, created_at),
    CONSTRAINT fk_nomina_reglas_eventos_regla
        FOREIGN KEY (regla_id) REFERENCES nomina_reglas_legales (id) ON DELETE RESTRICT,
    CONSTRAINT fk_nomina_reglas_eventos_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Semillas minimas (idempotentes por UNIQUE; ON DUPLICATE = no-op).

-- Estatutario LFT (estable desde reforma 2023; se versiona si cambia la ley).
INSERT INTO nomina_reglas_legales
    (pais, tipo_regla, ejercicio, vigente_desde, valor, valores_json, descripcion, fuente, estado)
VALUES
    ('MX', 'aguinaldo_dias_minimo', 2023, '2023-01-01', 15.0000, NULL,
     'Dias minimos de aguinaldo (LFT art. 87)', 'LFT art. 87', 'activo'),
    ('MX', 'prima_vacacional_pct', 2023, '2023-01-01', 25.0000, NULL,
     'Prima vacacional minima sobre salario de vacaciones (LFT art. 80)', 'LFT art. 80', 'activo'),
    ('MX', 'vacaciones_tabla', 2023, '2023-01-01', NULL,
     '{"unidad":"dias_por_anio_servicio","tramos":[{"anio":1,"dias":12},{"anio":2,"dias":14},{"anio":3,"dias":16},{"anio":4,"dias":18},{"anio":5,"dias":20},{"anio_desde":6,"anio_hasta":10,"dias":22},{"anio_desde":11,"anio_hasta":15,"dias":24},{"anio_desde":16,"anio_hasta":20,"dias":26},{"anio_desde":21,"anio_hasta":25,"dias":28},{"anio_desde":26,"anio_hasta":30,"dias":30}]}',
     'Tabla de vacaciones dignas (LFT art. 76, reforma 2023)', 'LFT art. 76', 'activo')
ON DUPLICATE KEY UPDATE id = id;

-- Referencias 2025 (DOF): historicas, utiles para pruebas. El ejercicio
-- vigente DEBE capturarse desde /admin/saas/nomina/reglas con fuente DOF.
INSERT INTO nomina_reglas_legales
    (pais, tipo_regla, ejercicio, vigente_desde, valor, descripcion, fuente, estado)
VALUES
    ('MX', 'uma_diaria', 2025, '2025-02-01', 113.1400,
     'UMA diaria 2025', 'DOF 10/01/2025 (verificar ejercicio vigente)', 'activo'),
    ('MX', 'salario_minimo_general', 2025, '2025-01-01', 278.8000,
     'Salario minimo general diario 2025', 'DOF (verificar ejercicio vigente)', 'activo'),
    ('MX', 'salario_minimo_frontera', 2025, '2025-01-01', 419.8800,
     'Salario minimo ZLFN diario 2025', 'DOF (verificar ejercicio vigente)', 'activo')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual seguro:
-- DELETE FROM nomina_reglas_legales_eventos;      -- solo si es aceptable perder historial
-- DROP TABLE IF EXISTS nomina_reglas_legales_eventos;
-- DROP TABLE IF EXISTS nomina_reglas_legales;      -- solo si ningun periodo congelo reglas aun
-- DELETE FROM migrations WHERE nombre = '20260704_005_nomina_reglas_legales.sql';
