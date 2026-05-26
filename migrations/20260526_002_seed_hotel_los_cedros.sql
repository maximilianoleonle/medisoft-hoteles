-- Fase 1.1 - Seed inicial del hotel Los Cedros
-- Seed aditivo: no modifica login, sesiones ni tablas operativas.

START TRANSACTION;

INSERT INTO hoteles (
    nombre,
    slug,
    codigo,
    razon_social,
    telefono,
    email,
    direccion,
    ciudad,
    estado,
    pais,
    zona_horaria,
    moneda_codigo,
    moneda_simbolo,
    activo,
    metadata
) VALUES (
    'Los Cedros',
    'los-cedros',
    'LOS_CEDROS',
    NULL,
    '',
    '',
    'Santa Catarina Juquila, Oaxaca',
    'Santa Catarina Juquila',
    'Oaxaca',
    'Mexico',
    'America/Mexico_City',
    'MXN',
    '$',
    1,
    JSON_OBJECT('origen', 'seed_fase_1_1', 'modo_inicial', 'mono_hotel')
)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    activo = VALUES(activo),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO hotel_configuracion (
    hotel_id,
    clave,
    valor,
    tipo,
    grupo,
    descripcion,
    es_feature_flag,
    activo
)
SELECT h.id, cfg.clave, cfg.valor, cfg.tipo, cfg.grupo, cfg.descripcion, cfg.es_feature_flag, 1
FROM hoteles h
JOIN (
    SELECT 'hotel.nombre' AS clave, 'Los Cedros' AS valor, 'string' AS tipo, 'hotel' AS grupo,
           'Nombre comercial del hotel' AS descripcion, 0 AS es_feature_flag
    UNION ALL SELECT 'hotel.direccion', 'Santa Catarina Juquila, Oaxaca', 'string', 'hotel',
           'Direccion base del hotel actual', 0
    UNION ALL SELECT 'hotel.zona_horaria', 'America/Mexico_City', 'string', 'hotel',
           'Zona horaria operativa', 0
    UNION ALL SELECT 'hotel.moneda_codigo', 'MXN', 'string', 'hotel',
           'Codigo ISO de moneda', 0
    UNION ALL SELECT 'hotel.moneda_simbolo', '$', 'string', 'hotel',
           'Simbolo de moneda', 0
    UNION ALL SELECT 'hotel.habitaciones_actuales', '66', 'integer', 'hotel',
           'Numero historico usado por el sistema mono-hotel', 0
    UNION ALL SELECT 'sistema.modo_compatibilidad_mono_hotel', 'true', 'boolean', 'sistema',
           'Mantiene comportamiento mono-hotel mientras se migra por fases', 0
    UNION ALL SELECT 'feature.multi_hotel', 'false', 'boolean', 'features',
           'Activa aislamiento multi-hotel completo', 1
    UNION ALL SELECT 'feature.selector_hotel', 'false', 'boolean', 'features',
           'Muestra selector de hotel en la interfaz', 1
    UNION ALL SELECT 'feature.audit_logs', 'false', 'boolean', 'features',
           'Activa escritura en logs_auditoria', 1
    UNION ALL SELECT 'feature.hotel_configuracion', 'true', 'boolean', 'features',
           'Permite leer configuracion por hotel cuando se integre el helper', 1
    UNION ALL SELECT 'feature.saas_billing', 'false', 'boolean', 'features',
           'Activa funciones comerciales SaaS', 1
    UNION ALL SELECT 'feature.bitacora_inteligente', 'false', 'boolean', 'features',
           'Activa bitacora inteligente futura', 1
    UNION ALL SELECT 'feature.whatsapp_ejecutivo', 'false', 'boolean', 'features',
           'Activa reportes ejecutivos por WhatsApp', 1
    UNION ALL SELECT 'feature.ia_ejecutiva', 'false', 'boolean', 'features',
           'Activa resumenes ejecutivos con IA', 1
) cfg
WHERE h.slug = 'los-cedros'
ON DUPLICATE KEY UPDATE
    valor = VALUES(valor),
    tipo = VALUES(tipo),
    grupo = VALUES(grupo),
    descripcion = VALUES(descripcion),
    es_feature_flag = VALUES(es_feature_flag),
    activo = VALUES(activo),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO hotel_usuarios (
    hotel_id,
    usuario_id,
    rol,
    es_principal,
    activo
)
SELECT
    h.id,
    u.id,
    u.rol,
    CASE WHEN u.id = 1 THEN 1 ELSE 0 END AS es_principal,
    u.activo
FROM hoteles h
JOIN usuarios u ON u.activo = 1
WHERE h.slug = 'los-cedros'
  AND u.rol IN ('gerente', 'administrador', 'recepcionista')
ON DUPLICATE KEY UPDATE
    rol = VALUES(rol),
    es_principal = VALUES(es_principal),
    activo = VALUES(activo),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260526_002_seed_hotel_los_cedros.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
