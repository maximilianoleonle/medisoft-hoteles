-- Fase 1C - Reparar vista_caja_actual para dumps locales completos.
-- Alcance:
-- - No modifica tablas ni datos.
-- - No cambia calculos financieros existentes de la vista.
-- - Reemplaza el DEFINER heredado por SQL SECURITY INVOKER.
-- - Permite que mysqldump no falle por un definer inexistente o sin permisos.

CREATE OR REPLACE
ALGORITHM = UNDEFINED
SQL SECURITY INVOKER
VIEW vista_caja_actual AS
SELECT
    c.id AS caja_id,
    c.nombre AS caja_nombre,
    cc.id AS corte_id,
    cc.fecha_apertura AS fecha_apertura,
    cc.monto_inicial AS monto_inicial,
    cc.usuario_apertura_id AS usuario_apertura_id,
    u.nombre_completo AS usuario_apertura,
    COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0) AS total_ingresos_efectivo,
    COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'tarjeta' THEN mc.monto ELSE 0 END), 0) AS total_ingresos_tarjeta,
    COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'transferencia' THEN mc.monto ELSE 0 END), 0) AS total_ingresos_transferencia,
    COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0) AS total_gastos_efectivo,
    COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'tarjeta' THEN mc.monto ELSE 0 END), 0) AS total_gastos_tarjeta,
    COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'transferencia' THEN mc.monto ELSE 0 END), 0) AS total_gastos_transferencia,
    (
        cc.monto_inicial
        + COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0)
        - COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0)
    ) AS efectivo_en_caja
FROM cajas c
LEFT JOIN cortes_caja cc
    ON c.id = cc.caja_id
   AND cc.estado = 'abierto'
LEFT JOIN movimientos_caja mc
    ON mc.corte_id = cc.id
LEFT JOIN usuarios u
    ON cc.usuario_apertura_id = u.id
WHERE c.activa = 1
GROUP BY
    c.id,
    c.nombre,
    cc.id,
    cc.fecha_apertura,
    cc.monto_inicial,
    cc.usuario_apertura_id,
    u.nombre_completo;

INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
SELECT
    '20260614_003_fix_vista_caja_actual_invoker.sql',
    COALESCE(MAX(batch), 0) + 1,
    NULL,
    'ejecutada',
    NOW()
FROM migrations
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual documentado:
-- 1) Restaurar la vista desde el backup previo a Fase 1C si se necesita volver
--    exactamente al definer heredado.
-- 2) Quitar el registro de migracion:
-- DELETE FROM migrations
-- WHERE nombre = '20260614_003_fix_vista_caja_actual_invoker.sql';
--
-- No usar DROP VIEW como rollback por defecto: aunque la vista no tenga datos
-- propios, eliminarla puede romper consumidores legacy si reaparecen.
