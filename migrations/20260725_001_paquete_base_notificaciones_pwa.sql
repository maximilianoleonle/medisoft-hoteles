-- Notificaciones y PWA pasan al paquete base (2026-07-25).
--
-- Decision de producto (owner, 2026-07-25): 'notificaciones' (Notificaciones y
-- push) y 'pwa' (aplicacion instalable) dejan de venderse como bloques
-- opcionales de $149 y pasan a formar parte del paquete base, SIN subir el
-- precio del plan Basico. El paquete base pasa de 8 a 10 modulos.
--
-- Motivo: la PWA nunca tuvo gate de servidor (no existe ni un solo
-- require_hotel_module('pwa') en el codigo: manifest, service worker, offline,
-- boton instalar y barra inferior ya funcionaban para todo hotel), y el aviso
-- operativo es parte de la promesa minima del producto.
--
-- Efecto tecnico: tipo_comercial='base' + es_core=1 => el modulo queda activo
-- para TODO hotel sin necesitar fila en hotel_modulos (listarActivosDeHotel
-- resuelve "activo_global = 1 AND (es_core = 1 OR hm.activo = 1)"), y sale del
-- cobro mensual (resumenCobroMensual exige es_core=0 AND tipo_comercial='opcional').
--
-- hotel_modulos NO se toca: se conserva como historial (mismo precedente que
-- 20260724_001 con usuarios/roles_avanzados/mantenimiento). es_core manda; una
-- fila con activo=0 para un modulo base es historia, no un bug. Las filas de
-- plan_modulos de 'pro'/'premium' (planes inactivos) tampoco se tocan.
--
-- PUSH OPT-IN (decision del owner): al abrirse el gate, PwaPushController deja
-- de responder 403 y pwa.js §8b suscribiria solo en cada hotel. Para que nadie
-- reciba push sin pedirlo, se siembra notificaciones.pwa_push_activo='0' en los
-- hoteles que NUNCA fijaron la clave; los que ya la tienen en '1' se respetan.
-- El default del catalogo (helpers/hotel_config.php) baja a false en el mismo
-- commit para cubrir a los hoteles creados despues de esta migracion.
--
-- Idempotente: UPDATE declarativo re-ejecutable + INSERT ... ON DUPLICATE KEY
-- UPDATE (plan_modulos tiene UNIQUE uk_plan_modulos_plan_modulo(plan_id, modulo_id);
-- hotel_configuracion tiene UNIQUE uk_hotel_configuracion_clave(hotel_id, clave)).
-- NO borra modulos, hotel_modulos, planes, plan_modulos ni notificaciones.
-- No toca dinero operativo del hotel ni cobros SaaS ya emitidos.
--
-- ATOMICIDAD OBLIGATORIA: tipo_comercial, es_core, precio_mensual y activo_global
-- viajan en el MISMO UPDATE dentro de la transaccion. Un estado intermedio
-- commiteado con activo_global=1 pero tipo_comercial='opcional'/es_core=0 y
-- precio 149 haria que SaasCobroService::generarPeriodo cobrara 149 por cada
-- activacion vigente en hotel_modulos. Prohibido aplicar estos UPDATE sueltos.
--
-- REVERSION (manual, documentada; respaldo previo en
-- backups/respaldo_catalogo_modulos_20260725.sql, que incluye modulos,
-- hotel_modulos, planes y plan_modulos):
--   1) Restaurar catalogo:  mysql medisoft_hoteles_import < backups/respaldo_catalogo_modulos_20260725.sql
--   2) DELETE FROM schema_migrations WHERE archivo = '20260725_001_paquete_base_notificaciones_pwa.sql';
--   3) DELETE FROM migrations WHERE nombre = '20260725_001_paquete_base_notificaciones_pwa.sql';
--   4) Revertir $BASE a 8 claves en src/tools/saas/verificar_clasificacion_comercial.php
--      y el default de notificaciones.pwa_push_activo a true en helpers/hotel_config.php.
--   NOTA: el respaldo de 20260724 NO sirve para revertir esto (es PRE-migracion,
--   trae DROP TABLE y borraria la clasificacion comercial completa).

SET @migration_name := '20260725_001_paquete_base_notificaciones_pwa.sql';

START TRANSACTION;

UPDATE modulos
SET tipo_comercial = 'base',
    es_core = 1,
    activo_global = 1,
    precio_mensual = 0.00,
    motivo_bloqueo = NULL,
    updated_at = CURRENT_TIMESTAMP
WHERE clave IN ('notificaciones', 'pwa');

-- Preset del plan Basico = paquete base (ahora 10 modulos). No existe fila
-- previa para basico+notificaciones ni basico+pwa: un UPDATE afectaria 0 filas
-- en silencio, por eso va INSERT ... ON DUPLICATE KEY UPDATE.
INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m
   ON m.clave IN ('notificaciones', 'pwa')
WHERE p.clave = 'basico'
ON DUPLICATE KEY UPDATE
    incluido = 1,
    updated_at = CURRENT_TIMESTAMP;

-- Push APAGADO en TODOS los hoteles, sin excepcion (decision del owner
-- 2026-07-25: "que lo enciendan a proposito"). No se respeta el valor previo a
-- proposito: los hoteles que tenian '1' lo heredaron del default true del
-- catalogo -- bastaba abrir y guardar /configuracion para persistirlo -- asi que
-- ese '1' no prueba que nadie eligiera recibir push. A partir de aqui, encenderlo
-- es un acto deliberado desde /configuracion (pantalla de Medisoft).
--
-- Se fuerza activo=1 ademas del valor: hotel_config_get lee SOLO filas activo=1
-- (helpers/hotel_config.php, tanto la carga en lote como la individual), asi que
-- una fila con activo=0 seria invisible en runtime y el hotel caeria al default
-- del codigo en vez de a este '0'.
--
-- OJO: esta sentencia es la UNICA de la migracion que NO es inocua al re-ejecutar
-- a mano. El resto son declarativas; esta APAGA el push que algun hotel haya
-- encendido despues. El runner no la repite (schema_migrations la marca
-- aplicada), pero si se corre el .sql suelto, revisar antes que ningun hotel
-- tenga la clave en '1'.
INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, descripcion)
SELECT h.id, 'notificaciones.pwa_push_activo', '0', 'boolean', 'notificaciones',
       'Apagado al pasar Notificaciones al paquete base (2026-07-25): push opt-in, lo enciende el hotel.'
FROM hoteles h
ON DUPLICATE KEY UPDATE valor = '0', activo = 1, updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name,
       COALESCE(MAX(batch), 0) + 1,
       SHA2(CONCAT(@migration_name, '|v1'), 256),
       'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
