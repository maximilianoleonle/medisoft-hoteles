-- Clasificacion comercial del catalogo de modulos (2026-07-24).
--
-- Decision de producto: el sistema se vende como paquete base + bloques
-- opcionales contratados individualmente. Cada modulo declara:
--   tipo_comercial: 'base' (incluido en el paquete), 'opcional' (contratable),
--                   'interno' (funcion Medisoft, jamas vendible ni cobrable).
--   activo_global:  1 disponible / 0 bloqueado temporalmente (ya existia).
--   motivo_bloqueo: texto visible en el panel Medisoft cuando esta bloqueado.
--
-- Reglas aplicadas aqui:
--   * Paquete base autorizado (es_core=1, precio 0): dashboard, habitaciones,
--     reservaciones, huespedes, caja, usuarios, roles_avanzados ("Roles y
--     permisos", solo cambia el nombre comercial) y mantenimiento (ya absorbio
--     Mantenimiento Plus; no se recrea Plus).
--   * Internos: configuracion (solo equipo Medisoft), anticipos (integrado a
--     Reservaciones/Caja; sube a es_core=1 para que sus flujos de dinero jamas
--     dependan de una fila en hotel_modulos), reportes (contenedor del Centro
--     de Reportes; los reportes analiticos se venden por separado).
--   * Re-registro trazable de bloques retirados por migraciones previas
--     (20260722_002 tareas, 20260723_001 reportes_distribucion): quedan como
--     internos bloqueados, SIN restaurar activaciones por hotel ni por plan.
--   * Opcionales aprobados: documentos, facturacion, compras, camarista
--     (bloqueados hasta auditoria) e inventario (disponible; su suite pasa).
--   * Bloqueos obligatorios: llaves_remotos, cuentas_cobrar, vehiculos.
--   * Todo opcional sin promesa comercial/precio autorizado queda bloqueado
--     provisionalmente (lista explicita, sin catch-all).
--   * Reportes individuales nuevos (precio provisional 0, bloqueados hasta
--     precio autorizado): reporte_ingresos_egresos, reporte_procedencia
--     (incluye ranking de estados), reporte_habitaciones_rentables,
--     reporte_ocupacion, reporte_promedio_estancia.
--   * Planes: pro y premium se desactivan (planes.activo=0) conservando sus
--     plan_modulos como historial; basico queda con el paquete base exacto
--     (sale el modulo general reportes, entran usuarios/roles/mantenimiento).
--
-- Idempotente: columnas via information_schema + PREPARE; filas via INSERT
-- ... ON DUPLICATE KEY UPDATE; UPDATEs declarativos re-ejecutables.
-- NO borra modulos, hotel_modulos, planes ni plan_modulos. No toca tablas
-- operativas ni cifras de dinero.
--
-- REVERSION (manual, documentada; respaldo previo en
-- backups/respaldo_catalogo_modulos_20260724.sql):
--   1) Restaurar catalogo:  mysql medisoft_hoteles_import < backups/respaldo_catalogo_modulos_20260724.sql
--   2) (Solo si se desea eliminar el modelo) ALTER TABLE modulos
--      DROP COLUMN tipo_comercial, DROP COLUMN motivo_bloqueo;
--   3) DELETE FROM migrations WHERE nombre = '20260724_001_clasificacion_comercial_modulos.sql';

SET @migration_name := '20260724_001_clasificacion_comercial_modulos.sql';

-- ---------------------------------------------------------------------------
-- 1. Columnas nuevas (fuera de transaccion: ALTER hace commit implicito).
--    VARCHAR + validacion en PHP (gotcha: enum invalido no truena en MySQL).
-- ---------------------------------------------------------------------------

SET @tipo_comercial_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'modulos'
      AND COLUMN_NAME = 'tipo_comercial'
);
SET @add_tipo_comercial := IF(
    @tipo_comercial_exists = 0,
    'ALTER TABLE modulos ADD COLUMN tipo_comercial VARCHAR(20) NOT NULL DEFAULT ''opcional'' AFTER es_core',
    'SELECT 1'
);
PREPARE stmt FROM @add_tipo_comercial;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @motivo_bloqueo_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'modulos'
      AND COLUMN_NAME = 'motivo_bloqueo'
);
SET @add_motivo_bloqueo := IF(
    @motivo_bloqueo_exists = 0,
    'ALTER TABLE modulos ADD COLUMN motivo_bloqueo VARCHAR(255) NULL DEFAULT NULL AFTER activo_global',
    'SELECT 1'
);
PREPARE stmt FROM @add_motivo_bloqueo;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_tipo_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'modulos'
      AND INDEX_NAME = 'idx_modulos_tipo_comercial'
);
SET @add_idx_tipo := IF(
    @idx_tipo_exists = 0,
    'ALTER TABLE modulos ADD INDEX idx_modulos_tipo_comercial (tipo_comercial)',
    'SELECT 1'
);
PREPARE stmt FROM @add_idx_tipo;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 2. Clasificacion de datos (transaccional).
-- ---------------------------------------------------------------------------

START TRANSACTION;

-- 2.1 Paquete base autorizado.
UPDATE modulos
SET tipo_comercial = 'base',
    es_core = 1,
    activo_global = 1,
    precio_mensual = 0.00,
    motivo_bloqueo = NULL,
    updated_at = CURRENT_TIMESTAMP
WHERE clave IN ('dashboard', 'habitaciones', 'reservaciones', 'huespedes',
                'caja', 'usuarios', 'roles_avanzados', 'mantenimiento');

-- Solo el nombre comercial cambia; la clave tecnica se conserva.
UPDATE modulos
SET nombre = 'Roles y permisos',
    updated_at = CURRENT_TIMESTAMP
WHERE clave = 'roles_avanzados'
  AND nombre <> 'Roles y permisos';

-- 2.2 Funciones internas Medisoft (activas tecnicamente, jamas cobrables).
--     configuracion conserva es_core=1; anticipos SUBE a es_core=1 para que
--     los gates existentes de Reservaciones/Caja nunca dependan de hotel_modulos.
UPDATE modulos
SET tipo_comercial = 'interno',
    es_core = 1,
    activo_global = 1,
    precio_mensual = 0.00,
    motivo_bloqueo = NULL,
    updated_at = CURRENT_TIMESTAMP
WHERE clave IN ('configuracion', 'anticipos');

-- reportes = contenedor interno del Centro de Reportes (no siempre-activo:
-- el acceso al Centro pasa a depender de tener >=1 reporte permitido).
UPDATE modulos
SET tipo_comercial = 'interno',
    es_core = 0,
    activo_global = 1,
    precio_mensual = 0.00,
    motivo_bloqueo = NULL,
    descripcion = 'Contenedor interno del Centro de Reportes; los reportes analiticos se contratan por separado.',
    updated_at = CURRENT_TIMESTAMP
WHERE clave = 'reportes';

-- 2.3 Re-registro trazable de bloques retirados (solo catalogo; sin
--     activaciones por hotel ni por plan, que ya no pueden comprobarse).
INSERT INTO modulos (clave, nombre, descripcion, categoria, es_core, tipo_comercial,
                     precio_mensual, activo_global, motivo_bloqueo, orden, icono, ruta_base)
VALUES
    ('tareas', 'Tareas operativas',
     'Infraestructura compartida por Habitaciones, Limpieza, Mantenimiento, Areas, Personal y Copiloto.',
     'operacion', 0, 'interno', 0.00, 0,
     'Infraestructura interna; retirada del catalogo vendible el 2026-07-22.',
     91, 'tasks', '/tareas'),
    ('reportes_distribucion', 'Distribucion de reportes',
     'Links publicos, expiracion, revocacion y envio por correo de reportes.',
     'analitica', 0, 'interno', 0.00, 0,
     'Funcion comercial retirada; congelada para posible version empresarial (2026-07-23).',
     83, 'share-nodes', '/reportes/links')
ON DUPLICATE KEY UPDATE
    tipo_comercial = VALUES(tipo_comercial),
    es_core = VALUES(es_core),
    precio_mensual = VALUES(precio_mensual),
    activo_global = VALUES(activo_global),
    motivo_bloqueo = VALUES(motivo_bloqueo),
    updated_at = CURRENT_TIMESTAMP;

-- 2.4 Opcionales aprobados con auditoria pendiente: se conservan con precio
--     e historial, bloqueados hasta pasar auditoria.
UPDATE modulos
SET tipo_comercial = 'opcional',
    activo_global = 0,
    motivo_bloqueo = 'Pendiente de auditoria antes de venta',
    updated_at = CURRENT_TIMESTAMP
WHERE clave IN ('documentos', 'facturacion', 'compras', 'camarista');

-- 2.5 Inventario: opcional disponible (funciona sin Compras; su suite pasa).
UPDATE modulos
SET tipo_comercial = 'opcional',
    activo_global = 1,
    motivo_bloqueo = NULL,
    updated_at = CURRENT_TIMESTAMP
WHERE clave = 'inventario';

-- 2.6 Bloqueos obligatorios con motivo propio.
UPDATE modulos
SET tipo_comercial = 'opcional',
    activo_global = 0,
    motivo_bloqueo = 'Funcionalidad aun no lista para venta',
    updated_at = CURRENT_TIMESTAMP
WHERE clave = 'llaves_remotos';

UPDATE modulos
SET tipo_comercial = 'opcional',
    activo_global = 0,
    motivo_bloqueo = 'Funcion comercial retirada; no es credito empresarial',
    updated_at = CURRENT_TIMESTAMP
WHERE clave = 'cuentas_cobrar';

UPDATE modulos
SET tipo_comercial = 'opcional',
    activo_global = 0,
    motivo_bloqueo = 'Pendiente de integracion como complemento interno de Huespedes o Reservaciones',
    updated_at = CURRENT_TIMESTAMP
WHERE clave = 'vehiculos';

-- 2.7 Opcionales sin promesa comercial definitiva ni precio autorizado:
--     bloqueados provisionalmente (la duena decide cuales salen a venta).
UPDATE modulos
SET tipo_comercial = 'opcional',
    activo_global = 0,
    motivo_bloqueo = 'Pendiente de revision comercial antes de venta',
    updated_at = CURRENT_TIMESTAMP
WHERE clave IN ('nomina_avanzada', 'checkin_digital', 'canales_ical', 'reputacion',
                'night_audit', 'descuentos', 'tablero_ejecutivo', 'exportaciones',
                'forecast', 'personal', 'modo_dueno', 'tarifas_dinamicas',
                'auditoria', 'limpieza', 'lavanderia', 'notificaciones',
                'canal_whatsapp', 'motor_reservas', 'promociones', 'pwa',
                'upsells', 'lealtad', 'motor_idiomas', 'whatsapp',
                'ia_ejecutiva', 'copiloto', 'copiloto_ia', 'copiloto_briefing');

-- 2.8 Reportes analiticos contratables individualmente. Precio provisional
--     0.00 y bloqueados: NO llevar a produccion sin precio autorizado.
INSERT INTO modulos (clave, nombre, descripcion, categoria, es_core, tipo_comercial,
                     precio_mensual, activo_global, motivo_bloqueo, orden, icono, ruta_base)
VALUES
    ('reporte_ingresos_egresos', 'Reporte: Ingresos y egresos',
     'Ingresos y gastos por categoria, evolucion diaria y utilidad del periodo.',
     'analitica', 0, 'opcional', 0.00, 0,
     'Pendiente de precio y auditoria comercial',
     801, 'scale-balanced', '/reportes/ingresos-gastos'),
    ('reporte_procedencia', 'Reporte: Procedencia de huespedes',
     'Origen de huespedes, procedencia internacional y ranking comparativo de estados.',
     'analitica', 0, 'opcional', 0.00, 0,
     'Pendiente de precio y auditoria comercial',
     802, 'map-location-dot', '/reportes/procedencia'),
    ('reporte_habitaciones_rentables', 'Reporte: Habitaciones mas rentables',
     'Ranking de habitaciones por ingresos, ocupacion y precio promedio.',
     'analitica', 0, 'opcional', 0.00, 0,
     'Pendiente de precio y auditoria comercial',
     803, 'trophy', '/reportes/habitaciones-rentables'),
    ('reporte_ocupacion', 'Reporte: Ocupacion',
     'Ocupacion por periodo con noches reales y comparativos.',
     'analitica', 0, 'opcional', 0.00, 0,
     'Pendiente de precio y auditoria comercial',
     804, 'bed', '/reportes/ocupacion'),
    ('reporte_promedio_estancia', 'Reporte: Promedio de estancia',
     'Duracion promedio de estancia y distribucion por noches.',
     'analitica', 0, 'opcional', 0.00, 0,
     'Pendiente de precio y auditoria comercial',
     805, 'clock', '/reportes/estancia')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    es_core = VALUES(es_core),
    tipo_comercial = VALUES(tipo_comercial),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;
-- Nota: el ON DUPLICATE no pisa precio_mensual/activo_global/motivo_bloqueo
-- para no revertir un precio o desbloqueo autorizado despues por la duena.

-- 2.9 Planes antiguos: pro y premium se conservan pero dejan de ofrecerse.
UPDATE planes
SET activo = 0,
    updated_at = CURRENT_TIMESTAMP
WHERE clave IN ('pro', 'premium');

UPDATE planes
SET activo = 1,
    updated_at = CURRENT_TIMESTAMP
WHERE clave IN ('basico', 'personalizado');

-- 2.10 Preset del plan Basico = paquete base autorizado.
--      Sale el modulo general 'reportes' (incluido=0, la fila se conserva).
UPDATE plan_modulos pm
INNER JOIN planes p ON p.id = pm.plan_id
INNER JOIN modulos m ON m.id = pm.modulo_id
SET pm.incluido = 0,
    pm.updated_at = CURRENT_TIMESTAMP
WHERE p.clave = 'basico'
  AND m.clave = 'reportes';

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m
   ON m.clave IN ('dashboard', 'habitaciones', 'reservaciones', 'huespedes',
                  'caja', 'usuarios', 'roles_avanzados', 'mantenimiento')
WHERE p.clave = 'basico'
ON DUPLICATE KEY UPDATE
    incluido = 1,
    updated_at = CURRENT_TIMESTAMP;

-- ---------------------------------------------------------------------------
-- 3. Registro de la migracion.
-- ---------------------------------------------------------------------------

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name,
       COALESCE(MAX(batch), 0) + 1,
       SHA2(CONCAT(@migration_name, '|v1'), 256),
       'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
