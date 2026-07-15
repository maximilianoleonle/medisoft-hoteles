-- Guardian financiero (evolucion del bloque vigilancia_financiera):
-- concede guardian.view a los roles base 'gerente' existentes de cada hotel.
-- Propietario/superadmin ya tienen '*'. Los hoteles que personalizaron su rol
-- gerente tambien lo reciben (el dueno puede quitarlo desde Roles y permisos);
-- los demas roles NO lo reciben: los hallazgos por usuario son solo direccion.
-- Idempotente: solo agrega si el permiso no esta ya en el JSON.

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'guardian.view'),
    updated_at = NOW()
WHERE clave = 'gerente'
  AND permisos_json IS NOT NULL
  AND JSON_VALID(permisos_json)
  AND JSON_SEARCH(permisos_json, 'one', 'guardian.view') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', '*') IS NULL;
