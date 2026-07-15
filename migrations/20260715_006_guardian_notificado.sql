-- Guardian: marca de push enviado por hallazgo (dedupe de alertas).
-- Un hallazgo de severidad alta se notifica UNA vez; si se resuelve y luego
-- reaparece con casos nuevos (estado vuelve a 'nuevo'), se limpia la marca
-- desde GuardianHallazgoEstado para poder avisar otra vez.

ALTER TABLE guardian_hallazgos_estado
    ADD COLUMN notificado_en DATETIME NULL AFTER revisado_en;
