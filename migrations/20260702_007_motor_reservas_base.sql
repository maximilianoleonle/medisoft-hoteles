-- Motor de reservas publico - Fase 1: bloque comercial y tablas base.
-- Alcance:
-- - Registra el bloque motor_reservas ($499, opt-in: SIN activacion retroactiva).
-- - Crea motor_pagos_online (ledger de pagos de pasarela, separado de Caja).
-- - Crea motor_holds (bloqueo temporal anti doble-venta durante el pago).
-- - Crea hotel_pasarela_credenciales (llaves cifradas por hotel).
-- - No toca Caja, reservaciones ni datos operativos.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('motor_reservas', 'Motor de reservas online', 'Pagina publica de reservas con pago de anticipo online y conciliacion a Caja.', 'canales', 0, 499.00, 1, 118, 'globe', '/motor-reservas')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;

-- Preset comercial: solo premium lo incluye como sugerencia; el resto lo contrata a la carte.
INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave = 'motor_reservas'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

-- Ledger de pagos online. El dinero de pasarela NO entra a Caja aqui:
-- se concilia despues via AnticipoService (estado 'conciliado' + abono_id).
CREATE TABLE IF NOT EXISTS motor_pagos_online (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    reservacion_id INT DEFAULT NULL,
    proveedor VARCHAR(30) NOT NULL,
    proveedor_pago_id VARCHAR(120) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    moneda CHAR(3) NOT NULL DEFAULT 'MXN',
    estado ENUM('pendiente','pagado','conciliado','reembolsado','fallido','expirado') NOT NULL DEFAULT 'pendiente',
    huesped_nombre VARCHAR(150) DEFAULT NULL,
    huesped_email VARCHAR(120) DEFAULT NULL,
    huesped_telefono VARCHAR(30) DEFAULT NULL,
    payload_json JSON DEFAULT NULL,
    abono_id INT DEFAULT NULL,
    conciliado_por INT DEFAULT NULL,
    conciliado_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_motor_pagos_proveedor (proveedor, proveedor_pago_id),
    KEY idx_motor_pagos_hotel_estado (hotel_id, estado),
    KEY idx_motor_pagos_reservacion (reservacion_id),
    CONSTRAINT fk_motor_pagos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_motor_pagos_reservacion
        FOREIGN KEY (reservacion_id) REFERENCES reservaciones(id) ON DELETE SET NULL,
    CONSTRAINT fk_motor_pagos_conciliador
        FOREIGN KEY (conciliado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hold temporal de habitaciones mientras el huesped completa el pago (expira en ~20 min).
CREATE TABLE IF NOT EXISTS motor_holds (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    token CHAR(32) NOT NULL,
    habitacion_ids_json JSON NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    pago_online_id INT DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_motor_holds_token (token),
    KEY idx_motor_holds_hotel_exp (hotel_id, expires_at),
    CONSTRAINT fk_motor_holds_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    CONSTRAINT fk_motor_holds_pago
        FOREIGN KEY (pago_online_id) REFERENCES motor_pagos_online(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Credenciales de pasarela por hotel. secret_key y webhook_secret van cifrados
-- (AES-256-GCM con llave en env MOTOR_PASARELA_KEY), nunca en texto plano.
CREATE TABLE IF NOT EXISTS hotel_pasarela_credenciales (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    proveedor ENUM('stripe','mercadopago') NOT NULL DEFAULT 'stripe',
    public_key VARCHAR(191) DEFAULT NULL,
    secret_key_encrypted TEXT DEFAULT NULL,
    webhook_secret_encrypted TEXT DEFAULT NULL,
    modo ENUM('test','live') NOT NULL DEFAULT 'test',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_pasarela_hotel (hotel_id),
    CONSTRAINT fk_pasarela_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260702_007_motor_reservas_base.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
