-- Conceptos de caja (catalogo de ingresos y gastos) pasa a permiso propio
-- 'caja.conceptos'. Antes lo gateaba 'caja.ajustes', que ademas permite editar
-- el MONTO de un movimiento: por eso solo Gerente/Propietario lo traian y la
-- seccion "Conceptos" aparecia en un hotel y faltaba en otro.
--
-- Este backfill deja la seccion disponible DE FABRICA en todos los hoteles ya
-- creados, sin repartir de paso el poder de corregir dinero:
--   1) roles que ya tenian 'caja.ajustes' -> conservan el acceso (nadie pierde);
--   2) roles base 'administrador'         -> lo reciben (nuevo default del preset).
-- Propietario/superadmin ('*') y Gerente ('caja.all') ya lo cubren por comodin.
-- Recepcionista NO lo recibe: cobra, no administra el catalogo.
-- Idempotente: solo agrega si el permiso no esta ya en el JSON.

-- 1) Nadie pierde lo que ya podia hacer.
UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'caja.conceptos'),
    updated_at = NOW()
WHERE permisos_json IS NOT NULL
  AND JSON_VALID(permisos_json)
  AND JSON_SEARCH(permisos_json, 'one', 'caja.ajustes') IS NOT NULL
  AND JSON_SEARCH(permisos_json, 'one', 'caja.conceptos') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', 'caja.all') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', '*') IS NULL;

-- 2) Nuevo default del preset Administrador.
UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'caja.conceptos'),
    updated_at = NOW()
WHERE clave = 'administrador'
  AND permisos_json IS NOT NULL
  AND JSON_VALID(permisos_json)
  AND JSON_SEARCH(permisos_json, 'one', 'caja.conceptos') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', 'caja.all') IS NULL
  AND JSON_SEARCH(permisos_json, 'one', '*') IS NULL;
