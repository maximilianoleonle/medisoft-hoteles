-- Bloque copiloto - V1: asistente hibrido (reglas primero + IA opcional).
-- Alcance:
-- - Registra el bloque copiloto ($149, opt-in: sin activacion retroactiva).
-- - Preguntas de DATOS (ocupacion, caja, llegadas...) se responden con REGLAS
--   deterministas: consultas de solo lectura + respuesta con plantilla.
--   Instantaneo, sin costo de API, funciona sin internet y nunca inventa.
-- - Preguntas abiertas o de "como hago X" caen a Claude SOLO si el hotel tiene
--   la IA encendida (config copiloto.ia_activa) y hay ANTHROPIC_API_KEY. Los
--   numeros los pone SIEMPRE el servidor, no el modelo.
-- - copiloto_mensajes: bitacora append-only (para costo/auditoria). Solo lectura
--   sobre la operacion; jamas escribe en Caja, reservaciones ni nada operativo.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('copiloto', 'Copiloto Medisoft', 'Asistente en todas las pantallas: pregunta por tu ocupacion, caja o llegadas y te responde al instante; tambien te explica como hacer las cosas.', 'inteligencia', 0, 149.00, 1, 141, 'robot', NULL)
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
INNER JOIN modulos m ON m.clave = 'copiloto'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS copiloto_mensajes (
    id BIGINT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    pregunta VARCHAR(500) NOT NULL,
    fuente ENUM('reglas','ia','fallback') NOT NULL DEFAULT 'reglas',
    intent VARCHAR(40) DEFAULT NULL,
    tokens_entrada INT NOT NULL DEFAULT 0,
    tokens_salida INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_copiloto_hotel_fecha (hotel_id, created_at),
    KEY idx_copiloto_hotel_fuente (hotel_id, fuente, created_at),
    CONSTRAINT fk_copiloto_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_copiloto_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_016_copiloto.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
