-- El Gerente pasa a ser el techo del hotel: acceso total a todas las areas.
-- Alcance:
-- - roles: permisos_json del rol base 'gerente' de CADA hotel.
-- - roles: descripcion del mismo rol (solo si conserva el texto sembrado).
-- NO toca claves, ni otros roles, ni permisos por persona (hotel_usuarios).
--
-- Se le da el "control total" de cada area ('<modulo>.all') y NO el comodin
-- '*': con el comodin el sistema bloquea editar el rol y recortarle permisos a
-- una persona concreta. Asi el Gerente puede todo, pero se sigue viendo y
-- ajustando desde Roles y permisos.
--
-- Los permisos aqui deben coincidir con el preset 'gerente' de
-- config/permisos.php (fuente de verdad para los hoteles NUEVOS).
--
-- Idempotente: re-ejecutarla deja los datos identicos.
-- OJO: pisa el permisos_json de TODOS los roles 'gerente' de sistema, incluido
-- el de un hotel que lo hubiera personalizado. Se verifico antes de escribirla
-- que ningun hotel (local ni produccion) lo tenia personalizado.

SET NAMES utf8mb4;
SET @migration_name := '20260723_001_gerente_acceso_total.sql';

START TRANSACTION;

-- 1) Permisos: control total de cada area del catalogo.
UPDATE roles
SET permisos_json = '["habitaciones.all","reservaciones.all","huespedes.all","caja.all","facturacion.all","cuentas_por_cobrar.all","cuentas_por_pagar.all","inventarios.all","compras.all","proveedores.all","documentos.all","tareas.all","lavanderia.all","camarista.view","reportes.all","personal.all","nomina.all","tarifas.all","usuarios.all","roles.manage","configuracion.all","notificaciones.view","guardian.view","reputacion.view","dueno.view","llaves.control"]'
WHERE clave = 'gerente'
  AND es_sistema = 1;

-- 2) Descripcion: solo si el hotel conserva la sembrada (o nunca tuvo una).
UPDATE roles
SET descripcion = 'Manda en todo el hotel: todas las áreas, más usuarios y configuración.'
WHERE clave = 'gerente'
  AND es_sistema = 1
  AND (descripcion IS NULL
       OR descripcion IN ('Dirección operativa y financiera del hotel.',
                          'Direccion operativa y financiera del hotel.'));

-- Registro de la migracion.
INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;

-- Rollback manual (deja al Gerente como estaba antes del 23 jul 2026):
-- UPDATE roles SET permisos_json = '["habitaciones.all","reservaciones.all","huespedes.all","caja.view","caja.cobros","caja.movimientos","caja.corte","caja.ajustes","facturacion.view","cuentas_por_cobrar.all","inventarios.all","compras.all","proveedores.all","cuentas_por_pagar.all","documentos.all","tareas.all","lavanderia.all","reportes.all","personal.view","personal.gestionar","personal.pagar","nomina.all","tarifas.view","tarifas.edit","usuarios.view","usuarios.create","usuarios.edit","usuarios.delete","roles.manage","configuracion.view","configuracion.edit","notificaciones.view","guardian.view"]',
--        descripcion = 'Dirección operativa y financiera del hotel.'
-- WHERE clave = 'gerente' AND es_sistema = 1;
-- DELETE FROM migrations WHERE nombre = '20260723_001_gerente_acceso_total.sql';
