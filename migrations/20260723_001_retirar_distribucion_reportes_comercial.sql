-- Retira Distribucion de reportes del catalogo comercial.
--
-- Decision de producto (2026-07-23): los hoteles conservan Exportaciones
-- para descargar PDF/Excel y compartirlos manualmente. Los links publicos,
-- expiracion, revocacion y envio por correo quedan congelados para una
-- posible version empresarial.
--
-- Esta migracion elimina solamente el bloque `reportes_distribucion` y sus
-- asignaciones comerciales por cascada. No borra reporte_links (incluidos
-- sus contadores de acceso), reporte_link_envios, archivos PDF ni configuracion.

SET @migration_name := '20260723_001_retirar_distribucion_reportes_comercial.sql';

START TRANSACTION;

DELETE FROM modulos
WHERE clave = 'reportes_distribucion';

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
