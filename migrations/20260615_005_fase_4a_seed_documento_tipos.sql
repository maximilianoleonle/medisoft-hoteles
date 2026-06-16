-- Fase 4A fix: tipos documentales base.
-- Migracion aditiva e idempotente.
-- Backup previo local:
--   src/storage/backups/phase4a_fix_20260615_193644_before_document_upload_fixes_medisoft_hoteles_import.sql
--   bytes: 1542543
--   sha256: FC9E0F68106A7ED49EC2228EFF0452F24710BDEF8E435AE3DE24ACD0A013C673
--
-- Alcance:
--   - Inserta tipos documentales globales si no existen.
--   - No modifica documentos existentes.
--   - No toca Caja, pagos, abonos, CxP operativa ni /api/sync.
--
-- Rollback manual seguro:
--   Ejecutar solo con autorizacion explicita y si se decide retirar estos tipos globales.
--   UPDATE documentos SET documento_tipo_id = NULL
--     WHERE documento_tipo_id IN (
--       SELECT id FROM documento_tipos
--       WHERE hotel_id IS NULL
--         AND clave IN ('contrato','comprobante','identificacion','factura','evidencia','otro')
--     );
--   DELETE FROM documento_tipos
--     WHERE hotel_id IS NULL
--       AND clave IN ('contrato','comprobante','identificacion','factura','evidencia','otro');
--   DELETE FROM migrations WHERE nombre = '20260615_005_fase_4a_seed_documento_tipos.sql';

SET @migration_name := '20260615_005_fase_4a_seed_documento_tipos.sql';
SET @allowed_mimes := '["application/pdf","image/jpeg","image/png","image/webp"]';

START TRANSACTION;

INSERT INTO documento_tipos
    (hotel_id, clave, nombre, descripcion, mime_permitidos, max_size_mb, activo, created_at, updated_at)
SELECT
    NULL,
    'contrato',
    'Contrato',
    'Contratos, convenios o acuerdos firmados.',
    @allowed_mimes,
    10.00,
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM documento_tipos WHERE hotel_id IS NULL AND clave = 'contrato'
);

INSERT INTO documento_tipos
    (hotel_id, clave, nombre, descripcion, mime_permitidos, max_size_mb, activo, created_at, updated_at)
SELECT
    NULL,
    'comprobante',
    'Comprobante',
    'Comprobantes o recibos relacionados con una entidad.',
    @allowed_mimes,
    10.00,
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM documento_tipos WHERE hotel_id IS NULL AND clave = 'comprobante'
);

INSERT INTO documento_tipos
    (hotel_id, clave, nombre, descripcion, mime_permitidos, max_size_mb, activo, created_at, updated_at)
SELECT
    NULL,
    'identificacion',
    'Identificacion',
    'Identificaciones oficiales o documentos de identidad.',
    @allowed_mimes,
    10.00,
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM documento_tipos WHERE hotel_id IS NULL AND clave = 'identificacion'
);

INSERT INTO documento_tipos
    (hotel_id, clave, nombre, descripcion, mime_permitidos, max_size_mb, activo, created_at, updated_at)
SELECT
    NULL,
    'factura',
    'Factura',
    'Facturas, XML impreso en PDF o evidencia fiscal en imagen.',
    @allowed_mimes,
    10.00,
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM documento_tipos WHERE hotel_id IS NULL AND clave = 'factura'
);

INSERT INTO documento_tipos
    (hotel_id, clave, nombre, descripcion, mime_permitidos, max_size_mb, activo, created_at, updated_at)
SELECT
    NULL,
    'evidencia',
    'Evidencia',
    'Evidencias fotograficas o documentales.',
    @allowed_mimes,
    10.00,
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM documento_tipos WHERE hotel_id IS NULL AND clave = 'evidencia'
);

INSERT INTO documento_tipos
    (hotel_id, clave, nombre, descripcion, mime_permitidos, max_size_mb, activo, created_at, updated_at)
SELECT
    NULL,
    'otro',
    'Otro',
    'Documento clasificado manualmente.',
    @allowed_mimes,
    10.00,
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM documento_tipos WHERE hotel_id IS NULL AND clave = 'otro'
);

SET @tipos_base := (
    SELECT COUNT(*)
    FROM documento_tipos
    WHERE hotel_id IS NULL
      AND clave IN ('contrato','comprobante','identificacion','factura','evidencia','otro')
      AND activo = 1
);

SET @validation_sql := IF(
    @tipos_base >= 6,
    'SELECT ''OK: tipos documentales base disponibles'' AS resultado',
    'SELECT tipos_documentales_base_incompletos'
);

PREPARE stmt FROM @validation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
