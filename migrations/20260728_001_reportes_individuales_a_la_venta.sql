-- Los 5 reportes individuales salen a la venta a $49/mes c/u (2026-07-28).
--
-- Decision de producto (owner, 2026-07-28): los reportes analiticos dejan de
-- estar "pendientes de precio" y quedan contratables por separado. Precio de
-- lista $49.00 mensuales cada uno, elegido como precio de entrada para que un
-- hotel pueda llevarse varios (los 5 juntos = $245/mes) en vez de uno solo.
--
-- Alcance: SOLO los 5 reporte_*. Los 26 modulos de "No disponibles todavia"
-- siguen bloqueados; su apertura se decide aparte porque SI reactivaria cobro
-- (hotel_modulos conserva contrataciones historicas de esos 26).
--
-- EL COBRO NO SE MUEVE EN NINGUN HOTEL. El preflight de produccion del
-- 2026-07-29 encontro filas reporte_* ya activas: los hoteles de legado tienen
-- precio_override=0 y Hotel Demo tenia 3 activas con precio_override=NULL. Si
-- solo se subiera el precio de catalogo, esas 3 empezarian a sumar $147/mes.
-- Por eso, dentro de la MISMA transaccion, esta migracion congela en 0.00
-- unicamente las filas que YA estan activas y aun heredan el precio de catalogo.
-- Las filas inactivas conservan NULL y una activacion futura toma los $49.00.
-- Las contrataciones creadas despues de esta migracion tambien toman el precio
-- de lista salvo que Medisoft les asigne un override explicito.
--
-- Tampoco toca plan_modulos: son opcionales a la carta, no entran al preset del
-- plan Basico (que es el paquete base exacto).
--
-- GATE DE SERVIDOR: ya existe y no depende de require_hotel_module. El Centro de
-- Reportes gatea por pantalla con require_hotel_report(), que resuelve el mapa
-- hotel_report_screen_modules() de helpers/modulos.php (ingresos-gastos,
-- procedencia, ranking-estados, habitaciones-rentables, ocupacion, estancia).
-- Al pasar a activo_global=1 esas pantallas dejan de estar muertas para quien
-- contrate el reporte; para quien no, siguen en 403. Efecto secundario buscado:
-- ranking-estados viaja dentro de reporte_procedencia (no es producto aparte).
--
-- ATOMICIDAD OBLIGATORIA: precio_mensual, activo_global y motivo_bloqueo viajan
-- en el MISMO UPDATE dentro de la transaccion. Un estado intermedio commiteado
-- con activo_global=1 y precio_mensual=0.00 pondria los 5 reportes A LA VENTA
-- REGALADOS, y SaasCobroService congelaria ese 0.00 en el desglose_json de todo
-- cobro generado en esa ventana (los cobros no se re-derivan nunca). Prohibido
-- aplicar estos UPDATE sueltos.
--
-- Idempotente: UPDATEs declarativos re-ejecutables acotados por clave. No borra
-- ni crea modulos, hoteles, planes ni contrataciones. Solo preserva en 0.00 el
-- precio efectivo de contrataciones que ya estaban activas. No toca dinero
-- operativo del hotel ni cobros SaaS ya emitidos.
--
-- REVERSION (manual, documentada; respaldo previo en
-- backups/respaldo_catalogo_modulos_20260728.sql, que incluye modulos, planes,
-- plan_modulos y hotel_modulos):
--   1) Restaurar modulos + hotel_modulos desde ese respaldo. El estado previo
--      de los 5 reportes era activo_global=1, precio_mensual=0.00 y motivo
--      "Legado 2026-07-28..."; el respaldo tambien revierte los overrides que
--      esta migracion congele en 0.00.
--   2) DELETE FROM schema_migrations WHERE archivo = '20260728_001_reportes_individuales_a_la_venta.sql';
--   3) DELETE FROM migrations WHERE nombre = '20260728_001_reportes_individuales_a_la_venta.sql';
--   4) Restaurar la version anterior de
--      src/tools/saas/verificar_clasificacion_comercial.php
--      al assert de "bloqueados con motivo, precio provisional 0".
--   OJO: si para entonces algun hotel ya contrato un reporte, no restaurar
--   hotel_modulos a ciegas: revisar esas filas y sus cobros abiertos primero.

SET @migration_name := '20260728_001_reportes_individuales_a_la_venta.sql';

START TRANSACTION;

UPDATE hotel_modulos hm
INNER JOIN modulos m ON m.id = hm.modulo_id
SET hm.precio_override = 0.00,
    hm.updated_at = CURRENT_TIMESTAMP
WHERE m.clave IN ('reporte_ingresos_egresos',
                  'reporte_procedencia',
                  'reporte_habitaciones_rentables',
                  'reporte_ocupacion',
                  'reporte_promedio_estancia')
  AND hm.activo = 1
  AND hm.precio_override IS NULL;

UPDATE modulos
SET activo_global = 1,
    precio_mensual = 49.00,
    motivo_bloqueo = NULL,
    updated_at = CURRENT_TIMESTAMP
WHERE clave IN ('reporte_ingresos_egresos',
                'reporte_procedencia',
                'reporte_habitaciones_rentables',
                'reporte_ocupacion',
                'reporte_promedio_estancia');

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
