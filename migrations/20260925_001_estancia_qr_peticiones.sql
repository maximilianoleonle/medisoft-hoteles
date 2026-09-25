-- QR en la habitacion + peticiones del huesped (dentro del bloque reputacion).
-- Alcance:
-- - estancia_qr: un token por habitacion (el QR impreso que se pega en el
--   cuarto). Rotable: al regenerarlo el QR viejo deja de funcionar.
-- - estancia_peticiones: lo que el huesped pide desde ese QR (toallas,
--   amenidades, limpieza, algo que no funciona, otra cosa). Solo se aceptan
--   mientras la habitacion tiene una reservacion en check-in; viven en su
--   propia bandeja y NO cambian el estado del cuarto ni crean mantenimientos
--   solas (eso bloquearia un cuarto ocupado).
-- - mensajes_whatsapp.tipo gana 'seguimiento': el "¿todo bien?" que la cola
--   de Mensajes ofrece la manana siguiente a la llegada.
-- - Actualiza la descripcion comercial del bloque reputacion para que diga
--   que incluye el QR (no toca precio, activo_global ni hotel_modulos).
--
-- Idempotente: re-ejecutarla no cambia nada una vez aplicada.

CREATE TABLE IF NOT EXISTS estancia_qr (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    habitacion_id INT NOT NULL,
    token CHAR(32) NOT NULL,
    generado_por INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_estancia_qr_token (token),
    UNIQUE KEY uk_estancia_qr_habitacion (hotel_id, habitacion_id),
    KEY fk_estancia_qr_habitacion (habitacion_id),
    KEY fk_estancia_qr_usuario (generado_por),
    CONSTRAINT fk_estancia_qr_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_estancia_qr_habitacion
        FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_estancia_qr_usuario
        FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estancia_peticiones (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    habitacion_id INT NOT NULL,
    reservacion_id INT DEFAULT NULL,
    huesped_id INT DEFAULT NULL,
    tipo VARCHAR(20) NOT NULL,
    detalle VARCHAR(500) DEFAULT NULL,
    foto_path VARCHAR(255) DEFAULT NULL,
    estado ENUM('nueva','en_atencion','resuelta','descartada') NOT NULL DEFAULT 'nueva',
    nota_cierre VARCHAR(300) DEFAULT NULL,
    atendida_por INT DEFAULT NULL,
    atendida_at DATETIME DEFAULT NULL,
    cerrada_por INT DEFAULT NULL,
    cerrada_at DATETIME DEFAULT NULL,
    origen_ip_hash CHAR(64) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_estancia_pet_hotel_estado (hotel_id, estado, created_at),
    KEY idx_estancia_pet_habitacion (hotel_id, habitacion_id, created_at),
    KEY idx_estancia_pet_reservacion (hotel_id, reservacion_id),
    KEY fk_estancia_pet_habitacion (habitacion_id),
    KEY fk_estancia_pet_reservacion (reservacion_id),
    KEY fk_estancia_pet_huesped (huesped_id),
    KEY fk_estancia_pet_atendida (atendida_por),
    KEY fk_estancia_pet_cerrada (cerrada_por),
    CONSTRAINT fk_estancia_pet_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_estancia_pet_habitacion
        FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_estancia_pet_reservacion
        FOREIGN KEY (reservacion_id) REFERENCES reservaciones(id) ON DELETE SET NULL,
    CONSTRAINT fk_estancia_pet_huesped
        FOREIGN KEY (huesped_id) REFERENCES huespedes(id) ON DELETE SET NULL,
    CONSTRAINT fk_estancia_pet_atendida
        FOREIGN KEY (atendida_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_estancia_pet_cerrada
        FOREIGN KEY (cerrada_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE mensajes_whatsapp
    MODIFY tipo ENUM('confirmacion','recordatorio','anticipo','encuesta','seguimiento')
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

UPDATE modulos
   SET descripcion = 'El huésped califica su estancia al salir y, durante la estancia, pide toallas o reporta fallas desde un QR en su habitación: te enteras antes de que se vuelva una mala reseña.'
 WHERE clave = 'reputacion';

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260925_001_estancia_qr_peticiones.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
