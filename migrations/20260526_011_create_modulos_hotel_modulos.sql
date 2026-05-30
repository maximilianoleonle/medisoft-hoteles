-- Microfase 4 - Base tecnica para modulos activos por hotel.
-- Alcance:
-- - Crea catalogo global de modulos.
-- - Crea asignacion de modulos por hotel.
-- - Inserta catalogo inicial sugerido.
-- - No modifica controladores operativos, PWA, branding ni datos de hoteles.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS modulos (
    id INT NOT NULL AUTO_INCREMENT,
    clave VARCHAR(80) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    categoria VARCHAR(80) DEFAULT NULL,
    activo_global TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    icono VARCHAR(80) DEFAULT NULL,
    ruta_base VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_modulos_clave (clave),
    KEY idx_modulos_activo_global (activo_global),
    KEY idx_modulos_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hotel_modulos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    modulo_id INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fuente VARCHAR(30) NOT NULL DEFAULT 'manual',
    trial_until DATE DEFAULT NULL,
    config_json JSON DEFAULT NULL,
    enabled_by INT DEFAULT NULL,
    enabled_at TIMESTAMP NULL DEFAULT NULL,
    disabled_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_hotel_modulos_hotel_modulo (hotel_id, modulo_id),
    KEY idx_hotel_modulos_hotel (hotel_id),
    KEY idx_hotel_modulos_modulo (modulo_id),
    KEY idx_hotel_modulos_activo (activo),
    CONSTRAINT fk_hotel_modulos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_hotel_modulos_modulo
        FOREIGN KEY (modulo_id) REFERENCES modulos(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_hotel_modulos_enabled_by
        FOREIGN KEY (enabled_by) REFERENCES usuarios(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, activo_global, orden, icono, ruta_base)
VALUES
    ('dashboard', 'Dashboard', 'Indicadores operativos y resumen ejecutivo del hotel.', 'core', 1, 10, 'layout-dashboard', '/dashboard'),
    ('habitaciones', 'Habitaciones', 'Gestion de habitaciones, estados, imagenes y mantenimiento operativo.', 'operacion', 1, 20, 'bed', '/habitaciones'),
    ('reservaciones', 'Reservaciones', 'Reservas, check-in, check-out y calendario.', 'operacion', 1, 30, 'calendar-days', '/reservaciones'),
    ('huespedes', 'Huespedes', 'Directorio de huespedes y datos relacionados.', 'operacion', 1, 40, 'users', '/huespedes'),
    ('caja', 'Caja', 'Movimientos, cortes y reportes de caja.', 'finanzas', 1, 50, 'wallet', '/caja'),
    ('facturacion', 'Facturacion', 'Solicitudes y seguimiento de facturacion.', 'finanzas', 1, 60, 'file-text', '/facturacion'),
    ('inventario', 'Inventario', 'Productos, stock, entradas, salidas y consumos.', 'operacion', 1, 70, 'boxes', '/inventario'),
    ('reportes', 'Reportes', 'Reportes operativos y financieros.', 'analitica', 1, 80, 'bar-chart-3', '/reportes'),
    ('limpieza', 'Limpieza', 'Seguimiento de limpieza y tareas por habitacion.', 'operacion', 1, 90, 'sparkles', NULL),
    ('mantenimiento', 'Mantenimiento', 'Mantenimientos preventivos y correctivos.', 'operacion', 1, 100, 'wrench', NULL),
    ('lavanderia', 'Lavanderia', 'Control de lavanderia y blancos.', 'operacion', 1, 110, 'shirt', NULL),
    ('pwa', 'PWA', 'Instalacion web, cache y experiencia offline.', 'canales', 1, 120, 'smartphone', NULL),
    ('whatsapp', 'WhatsApp', 'Comunicacion y automatizaciones via WhatsApp.', 'canales', 1, 130, 'message-circle', NULL),
    ('ia_ejecutiva', 'IA Ejecutiva', 'Asistencia e inteligencia operativa para direccion.', 'inteligencia', 1, 140, 'brain', NULL),
    ('auditoria', 'Auditoria', 'Logs, trazabilidad y controles de seguridad.', 'seguridad', 1, 150, 'shield-check', NULL)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    activo_global = VALUES(activo_global),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260526_011_create_modulos_hotel_modulos.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
