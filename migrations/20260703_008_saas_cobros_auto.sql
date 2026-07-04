-- Cobros SaaS - V2: ciclo automatico (vencimiento, correo y recordatorios).
-- Alcance:
-- - Estado nuevo 'vencido' para cobros pendientes que pasaron su fecha limite.
-- - vence_at: fecha limite de pago (dia SAAS_COBRO_DIA_VENCIMIENTO del mes,
--   default 10); backfill para cobros existentes.
-- - correo_enviado_at / recordatorio_enviado_at: control de envios del cron
--   (tools/cron_saas_cobros.php) para no duplicar correos.
-- - No toca Caja ni dinero de hoteles: esto es facturacion de plataforma.

START TRANSACTION;

ALTER TABLE saas_cobros
    MODIFY estado ENUM('pendiente','vencido','pagado','cancelado') NOT NULL DEFAULT 'pendiente',
    ADD COLUMN vence_at DATE DEFAULT NULL AFTER pagado_at,
    ADD COLUMN correo_enviado_at DATETIME DEFAULT NULL AFTER vence_at,
    ADD COLUMN recordatorio_enviado_at DATETIME DEFAULT NULL AFTER correo_enviado_at,
    ADD KEY idx_saas_cobros_vence (estado, vence_at);

UPDATE saas_cobros
SET vence_at = STR_TO_DATE(CONCAT(periodo, '-10'), '%Y-%m-%d')
WHERE vence_at IS NULL;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_008_saas_cobros_auto.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
