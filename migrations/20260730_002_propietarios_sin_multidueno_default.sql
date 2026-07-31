-- Multi-dueño: quitar la semilla Manolo/Elia que se coló en hoteles ajenos.
--
-- El default de fabrica de `propietarios.distribucion` traia dos dueños
-- cableados (Manolo y Elia, del hotel original). Como la pantalla de
-- configuracion pre-llenaba el formulario con ese default, CUALQUIER hotel que
-- guardara configuracion terminaba con esos dos socios escritos en su fila,
-- sin haberlos dado de alta nunca. Desde hoy el default es "sin multi-dueño"
-- (hotel_owner_distribution_default), asi que borrar la fila = quedar apagado.
--
-- CANDADO: solo se borran las filas que son la semilla INTACTA (exactamente
-- manolo+elia al 100%, sin asignaciones por habitacion y como mucho la regla
-- "manolo"). Un hotel que de verdad reparte -- Los Cedros en produccion tiene
-- sus 9 habitaciones asignadas a Manolo -- NO cumple la condicion y se queda
-- exactamente como esta. Un hotel que sí quiera repartir vuelve a dar de alta
-- sus dueños desde la pantalla; nada mas de su configuracion se toca.
DELETE FROM hotel_configuracion
WHERE clave = 'propietarios.distribucion'
  AND JSON_VALID(valor)
  AND JSON_UNQUOTE(JSON_EXTRACT(CAST(valor AS JSON), '$.propietario_default')) = 'elia'
  AND JSON_LENGTH(JSON_EXTRACT(CAST(valor AS JSON), '$.propietarios')) = 2
  AND JSON_CONTAINS_PATH(CAST(valor AS JSON), 'all', '$.propietarios.manolo', '$.propietarios.elia')
  AND JSON_EXTRACT(CAST(valor AS JSON), '$.propietarios.manolo.participacion_pct') = 100
  AND JSON_EXTRACT(CAST(valor AS JSON), '$.propietarios.elia.participacion_pct') = 100
  AND JSON_EXTRACT(CAST(valor AS JSON), '$.propietarios.manolo.nombre') = CAST('"Manolo"' AS JSON)
  AND JSON_EXTRACT(CAST(valor AS JSON), '$.propietarios.elia.nombre') = CAST('"Elia"' AS JSON)
  AND COALESCE(JSON_LENGTH(JSON_EXTRACT(CAST(valor AS JSON), '$.habitaciones')), 0) = 0
  AND COALESCE(JSON_LENGTH(JSON_EXTRACT(CAST(valor AS JSON), '$.reglas_tipo_contiene')), 0) <= 1;
