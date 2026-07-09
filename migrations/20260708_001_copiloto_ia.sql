-- Bloque copiloto_ia - V1: IA sobre datos existentes (premium, $299).
-- Alcance:
-- - Registra el bloque copiloto_ia (opt-in: sin activacion retroactiva).
-- - Tres funciones que reciclan datos de bloques ya vendidos:
--   1) Borrador de respuesta a resenas/encuestas (requiere bloque reputacion).
--   2) Analisis mensual de encuestas: quejas y elogios recurrentes (reputacion).
--   3) Consejo de tarifa segun ocupacion proyectada y pickup (requiere forecast).
-- - Los numeros del prompt los pone SIEMPRE el servidor; la IA solo redacta.
-- - copiloto_ia_generaciones: cache por (hotel, tipo, referencia) para no
--   pagar el API dos veces por lo mismo, bitacora de tokens, y conteo de la
--   prueba gratis (3 usos por funcion para hoteles SIN el bloque, en_prueba=1).
-- - Solo lectura sobre la operacion: jamas escribe en Caja, reservaciones,
--   tarifas ni datos operativos. La sugerencia de tarifa es texto, no un cambio.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('copiloto_ia', 'Copiloto IA', 'La IA trabaja con tus datos: borradores listos para responder resenas, analisis mensual de encuestas con las quejas mas repetidas y consejo de tarifa segun tu ocupacion proyectada.', 'inteligencia', 0, 299.00, 1, 142, 'wand-magic-sparkles', NULL)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;

-- Preset: premium lo incluye.
INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave = 'copiloto_ia'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS copiloto_ia_generaciones (
    id BIGINT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    tipo ENUM('resena','analisis','tarifa') NOT NULL,
    -- resena: id de la encuesta | analisis: 'YYYY-MM' | tarifa: 'YYYY-MM-DD'
    ref_clave VARCHAR(20) NOT NULL,
    contenido MEDIUMTEXT NOT NULL,
    -- generaciones acumuladas de esta fila (1 + regeneraciones)
    veces INT NOT NULL DEFAULT 1,
    -- 1 = se genero como prueba gratis (hotel sin el bloque contratado)
    en_prueba TINYINT(1) NOT NULL DEFAULT 0,
    tokens_entrada INT NOT NULL DEFAULT 0,
    tokens_salida INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_ia_gen (hotel_id, tipo, ref_clave),
    KEY idx_ia_gen_prueba (hotel_id, tipo, en_prueba),
    KEY idx_ia_gen_fecha (hotel_id, tipo, created_at),
    CONSTRAINT fk_ia_gen_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_ia_gen_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260708_001_copiloto_ia.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
