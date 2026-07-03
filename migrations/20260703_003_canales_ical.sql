-- Bloque canales_ical - V1: sincronizacion de calendarios con OTAs (anti-overbooking).
-- Alcance:
-- - Registra el bloque canales_ical ($249, opt-in).
-- - ical_feeds: calendarios externos (Airbnb/Booking) que el hotel importa por habitacion.
-- - ical_bloqueos: eventos importados que bloquean la habitacion en TODA la disponibilidad
--   (interna y motor publico) mientras el bloque este activo.
-- - La exportacion .ics por habitacion no requiere tablas (token en hotel_configuracion).

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('canales_ical', 'Canales (iCal)', 'Sincroniza tu calendario con Airbnb y Booking: exporta tus reservas e importa las de ellos para no vender dos veces la misma habitacion.', 'operacion', 0, 249.00, 1, 33, 'calendar-alt', '/canales')
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
INNER JOIN modulos m ON m.clave = 'canales_ical'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS ical_feeds (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    habitacion_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    last_sync_at DATETIME DEFAULT NULL,
    last_sync_estado ENUM('ok','error') DEFAULT NULL,
    last_sync_error VARCHAR(255) DEFAULT NULL,
    eventos_activos INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ical_feeds_hotel (hotel_id, activo),
    KEY idx_ical_feeds_habitacion (habitacion_id),
    CONSTRAINT fk_ical_feeds_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_ical_feeds_habitacion
        FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ical_bloqueos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    habitacion_id INT NOT NULL,
    feed_id INT NOT NULL,
    uid VARCHAR(191) NOT NULL,
    resumen VARCHAR(150) DEFAULT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('activo','liberado') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_ical_bloqueo_uid (feed_id, uid),
    KEY idx_ical_bloqueos_disponibilidad (hotel_id, estado, fecha_inicio, fecha_fin),
    KEY idx_ical_bloqueos_habitacion (habitacion_id, estado),
    CONSTRAINT fk_ical_bloqueos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_ical_bloqueos_habitacion
        FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_ical_bloqueos_feed
        FOREIGN KEY (feed_id) REFERENCES ical_feeds(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_003_canales_ical.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
