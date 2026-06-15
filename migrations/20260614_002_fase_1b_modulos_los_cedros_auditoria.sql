-- Fase 1B minima - Modulos base de Los Cedros y base operativa para auditoria.
-- Alcance:
-- - No crea tablas.
-- - No borra ni desactiva modulos existentes.
-- - No cambia planes comerciales.
-- - Solo inserta catalogo global faltante y asignaciones faltantes para Los Cedros.
-- - Reversible mediante rollback manual documentado al final.

START TRANSACTION;

SET @migration_name := '20260614_002_fase_1b_modulos_los_cedros_auditoria.sql';
SET @migration_source := 'fase_1b_minima';

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, activo_global, orden, icono, ruta_base)
VALUES
    ('configuracion', 'Configuracion', 'Configuracion operativa del hotel.', 'administracion', 1, 85, 'settings', '/configuracion'),
    ('usuarios', 'Usuarios', 'Usuarios y accesos operativos del hotel.', 'administracion', 1, 86, 'user-cog', '/usuarios'),
    ('tarifas_dinamicas', 'Tarifas dinamicas', 'Incrementos y reglas de tarifas por temporada, fecha o habitacion.', 'administracion', 1, 87, 'tags', '/configuracion/tarifas')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    activo_global = VALUES(activo_global),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO hotel_modulos
    (hotel_id, modulo_id, activo, fuente, enabled_by, enabled_at, disabled_at, created_at, updated_at)
SELECT
    h.id,
    m.id,
    1,
    @migration_source,
    NULL,
    NOW(),
    NULL,
    NOW(),
    NOW()
FROM hoteles h
INNER JOIN modulos m
    ON m.clave IN (
        'dashboard',
        'habitaciones',
        'reservaciones',
        'huespedes',
        'caja',
        'facturacion',
        'inventario',
        'reportes',
        'configuracion',
        'usuarios',
        'tarifas_dinamicas',
        'limpieza',
        'mantenimiento',
        'auditoria'
    )
LEFT JOIN hotel_modulos hm
    ON hm.hotel_id = h.id
   AND hm.modulo_id = m.id
WHERE h.slug = 'los-cedros'
  AND h.activo = 1
  AND hm.id IS NULL;

INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
SELECT
    @migration_name,
    COALESCE(MAX(batch), 0) + 1,
    NULL,
    'ejecutada',
    NOW()
FROM migrations
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;

-- Rollback manual documentado:
-- 1) Quitar solo las asignaciones creadas por esta fase:
-- DELETE hm
-- FROM hotel_modulos hm
-- INNER JOIN hoteles h ON h.id = hm.hotel_id
-- INNER JOIN modulos m ON m.id = hm.modulo_id
-- WHERE h.slug = 'los-cedros'
--   AND hm.fuente = 'fase_1b_minima'
--   AND m.clave IN (
--       'dashboard', 'habitaciones', 'reservaciones', 'huespedes', 'caja',
--       'facturacion', 'inventario', 'reportes', 'configuracion', 'usuarios',
--       'tarifas_dinamicas', 'limpieza', 'mantenimiento', 'auditoria'
--   );
--
-- 2) Quitar el registro de migracion:
-- DELETE FROM migrations
-- WHERE nombre = '20260614_002_fase_1b_modulos_los_cedros_auditoria.sql';
--
-- No borrar filas de modulos en rollback: es catalogo global y puede estar referenciado
-- por otros hoteles, planes o registros historicos.
