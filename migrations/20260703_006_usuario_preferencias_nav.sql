-- Bloque 2 (loop de mejora): navegacion rapida y vistas preguardadas.
-- Tabla de preferencias de navegacion por usuario Y por hotel:
--   - tipo 'reciente': se alimenta sola con cada vista principal visitada
--     (contador de frecuencia + ultima visita).
--   - tipo 'favorito': anclados manualmente por el usuario.
-- Aislamiento multi-tenant: toda lectura/escritura filtra por
-- (hotel_id, usuario_id); la visibilidad se revalida contra modulos activos
-- y permisos al momento de renderizar (nunca se confia en la tabla sola).

START TRANSACTION;

CREATE TABLE IF NOT EXISTS usuario_preferencias_nav (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('favorito', 'reciente') NOT NULL DEFAULT 'reciente',
    ruta VARCHAR(180) NOT NULL,
    contador INT UNSIGNED NOT NULL DEFAULT 1,
    ultima_visita DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pref_nav (hotel_id, usuario_id, tipo, ruta),
    KEY idx_pref_nav_lectura (hotel_id, usuario_id, tipo, ultima_visita)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_006_usuario_preferencias_nav.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
