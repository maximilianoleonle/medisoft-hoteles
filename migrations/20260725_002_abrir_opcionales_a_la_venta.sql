-- Apertura comercial de los bloques opcionales autorizados (2026-07-25).
--
-- Decision de producto (owner, 2026-07-25): salen a la venta los 8 bloques
-- opcionales que el owner aprobo. Hasta hoy solo 'inventario' estaba
-- activo_global=1; los otros 7 quedaron bloqueados por 20260724_001 a la espera
-- de autorizacion comercial. Esa autorizacion ya se dio.
--
--   inventario        $299  (ya estaba disponible; se incluye por idempotencia)
--   facturacion       $249
--   compras           $199  (Compras y proveedores)
--   documentos        $149  (Centro documental)
--   reputacion        $149  (Reputacion y encuestas)
--   descuentos        $ 99
--   tarifas_dinamicas $149
--   lealtad           $179  (Huesped frecuente)
--
-- NOTA EXPRESA sobre 'documentos': 20260724_001 lo dejo bloqueado porque
-- almacena documentos de huespedes (INE, comprobantes) y faltaba definir la
-- politica de borrado/retencion de PII. El owner decidio el 2026-07-25 abrirlo
-- igual y retirar ese bloqueo. Queda constancia aqui: la politica de retencion
-- sigue PENDIENTE y es deuda de cumplimiento, no un olvido.
--
-- Lo que este cambio NO hace: no altera precios (los de arriba ya estaban en
-- catalogo), no contrata nada a ningun hotel (hotel_modulos intacto) y no toca
-- planes. Desbloquear solo permite que el bloque APAREZCA como contratable en
-- /admin/saas/modulos y en el detalle de cada hotel, y que sus gates dejen de
-- responder 403 a los hoteles que SI lo tengan contratado.
--
-- Efecto colateral REAL de dinero: un hotel con contratacion historica en
-- hotel_modulos (activo=1) de alguno de estos bloques vuelve a COBRARSE en el
-- siguiente periodo, porque resumenCobroMensual exige tipo='opcional' AND
-- activo_global=1 AND hm.activo=1 y esa fila jamas se borro (se congelo como
-- historial). Revisar el cobro estimado de cada hotel tras aplicar; si alguna
-- contratacion historica no debe reactivarse, apagarla desde el panel.
--
-- Idempotente: UPDATE declarativo re-ejecutable, sin DDL.
-- NO borra ni crea modulos. No toca hotel_modulos, planes ni plan_modulos.
--
-- REVERSION (manual; respaldo previo en
-- backups/respaldo_catalogo_modulos_20260725_002.sql):
--   UPDATE modulos SET activo_global = 0,
--          motivo_bloqueo = 'Pendiente de revision comercial antes de venta'
--    WHERE clave IN ('facturacion','compras','documentos','reputacion',
--                    'descuentos','tarifas_dinamicas','lealtad');
--   DELETE FROM schema_migrations WHERE archivo = '20260725_002_abrir_opcionales_a_la_venta.sql';
--   DELETE FROM migrations WHERE nombre = '20260725_002_abrir_opcionales_a_la_venta.sql';
--   Revertir tambien $OPCIONALES_VENTA en src/tools/saas/verificar_clasificacion_comercial.php.

SET @migration_name := '20260725_002_abrir_opcionales_a_la_venta.sql';

START TRANSACTION;

UPDATE modulos
SET tipo_comercial = 'opcional',
    es_core = 0,
    activo_global = 1,
    motivo_bloqueo = NULL,
    updated_at = CURRENT_TIMESTAMP
WHERE clave IN ('inventario', 'facturacion', 'compras', 'documentos',
                'reputacion', 'descuentos', 'tarifas_dinamicas', 'lealtad');

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
