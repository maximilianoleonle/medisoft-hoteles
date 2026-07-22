-- Proyección de estacionamiento: al reservar se pregunta cuántos vehículos trae el huésped.
-- NULL = reservación previa a esta función (no se preguntó) → la proyección cae al
-- fallback de vehículos registrados del huésped (huesped_vehiculos activos).
ALTER TABLE reservaciones
  ADD COLUMN vehiculos_estimados TINYINT UNSIGNED NULL DEFAULT NULL
  COMMENT 'Vehiculos que el huesped dice traer (NULL = no preguntado)'
  AFTER habitaciones_cortesia;
