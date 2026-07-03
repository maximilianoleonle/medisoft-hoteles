-- Motor de reservas - claim atomico anti doble-webhook.
-- Alcance:
-- - Agrega el estado transitorio 'procesando' al ENUM de motor_pagos_online.
--   Un webhook de pasarela reclama el pago (pendiente -> procesando) ANTES de crear
--   la reservacion; entregas concurrentes ven 0 filas afectadas y no duplican.
--   En falla, el pago se revierte a 'pendiente'. Un 'procesando' que persiste indica
--   un webhook colgado a medio proceso (util para el health-check FIN-A).
-- - Cambio puramente aditivo: no reescribe filas existentes ni toca Caja.

START TRANSACTION;

ALTER TABLE motor_pagos_online
    MODIFY COLUMN estado
        ENUM('pendiente','procesando','pagado','conciliado','reembolsado','fallido','expirado')
        NOT NULL DEFAULT 'pendiente';

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_001_motor_pagos_estado_procesando.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
