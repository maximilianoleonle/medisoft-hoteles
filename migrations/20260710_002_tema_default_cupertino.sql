-- Default de producto: los hoteles NUEVOS nacen con el tema Apple (cupertino).
-- Solo cambia el DEFAULT de la columna (altas futuras). NO reasigna el tema de
-- los hoteles existentes: cada uno conserva el que ya tenga guardado.

ALTER TABLE hotel_branding ALTER COLUMN tema SET DEFAULT 'cupertino';

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260710_002_tema_default_cupertino.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
