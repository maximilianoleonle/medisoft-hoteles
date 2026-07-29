-- ============================================================================
-- Migración legacy San Nicolás (monohotel Hostinger) → hotel_id=3 multihotel
-- RE-EJECUTABLE: cada corrida borra lo importado por la anterior y reimporta.
-- Placeholders: @@SRC@@ = esquema staging con el dump legado (sannicolas_legacy)
--               @@DST@@ = esquema destino (medisoft_prod_clone | medisoft_hoteles)
-- Convención de reloj: TODO corre server-side en UNA sesión con -06:00
-- (igual que Database.php); un cliente en UTC correría la historia 6 horas.
-- ============================================================================
SET time_zone = '-06:00';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
-- Los DELETE multi-tabla exigen una BD por defecto aunque todo venga calificado
USE @@DST@@;

-- ---------------------------------------------------------------------------
-- 0) GUARDAS: el destino debe tener al hotel 3 = San Nicolás, con su caja.
--    (el truco IF(cond, ok, subquery-multifila) aborta el script si falla)
-- ---------------------------------------------------------------------------
SELECT IF(
  (SELECT COUNT(*) FROM @@DST@@.hoteles WHERE id = 3 AND slug = 'hotel-san-nicolas' AND activo = 1) = 1,
  'GUARDA OK: hotel 3 = hotel-san-nicolas activo',
  (SELECT 1 UNION ALL SELECT 2)
) AS guarda_hotel;

SELECT IF(
  (SELECT COUNT(*) FROM @@DST@@.cajas WHERE id = 3 AND hotel_id = 3 AND activa = 1) = 1,
  'GUARDA OK: caja 3 del hotel 3 activa',
  (SELECT 1 UNION ALL SELECT 2)
) AS guarda_caja;

SELECT IF(
  (SELECT COUNT(*) FROM @@SRC@@.habitaciones) = 66,
  'GUARDA OK: staging legado cargado (66 habitaciones)',
  (SELECT 1 UNION ALL SELECT 2)
) AS guarda_staging;

-- Los roles sembrados del hotel 3 (gerente/administrador/recepcionista) existen.
SELECT IF(
  (SELECT COUNT(*) FROM @@DST@@.roles WHERE hotel_id = 3 AND clave IN ('gerente','administrador','recepcionista') AND activo = 1) = 3,
  'GUARDA OK: roles sembrados del hotel 3',
  (SELECT 1 UNION ALL SELECT 2)
) AS guarda_roles;

-- ---------------------------------------------------------------------------
-- 1) LIMPIEZA de la corrida anterior (orden hijos → padres, FK-safe).
--    Todo lo hotel-scoped sale por hotel_id=3; lo sin hotel_id sale por JOIN
--    a las habitaciones del hotel 3 o por el registro de usuarios creados.
-- ---------------------------------------------------------------------------
DELETE d FROM @@DST@@.denominaciones_efectivo d JOIN @@DST@@.cortes_caja c ON c.id = d.corte_id WHERE c.hotel_id = 3;
DELETE FROM @@DST@@.movimientos_caja WHERE hotel_id = 3;
DELETE FROM @@DST@@.cortes_caja WHERE hotel_id = 3;
DELETE hl FROM @@DST@@.historial_llaves hl JOIN @@DST@@.habitaciones h ON h.id = hl.habitacion_id WHERE h.hotel_id = 3;
DELETE cl FROM @@DST@@.control_llaves cl JOIN @@DST@@.habitaciones h ON h.id = cl.habitacion_id WHERE h.hotel_id = 3;
DELETE hr FROM @@DST@@.historial_remotos hr JOIN @@DST@@.habitaciones h ON h.id = hr.habitacion_id WHERE h.hotel_id = 3;
DELETE cr FROM @@DST@@.control_remotos cr JOIN @@DST@@.habitaciones h ON h.id = cr.habitacion_id WHERE h.hotel_id = 3;
DELETE crb FROM @@DST@@.control_remotos_backup crb JOIN @@DST@@.habitaciones h ON h.id = crb.habitacion_id WHERE h.hotel_id = 3;
DELETE hrb FROM @@DST@@.historial_remotos_backup hrb JOIN @@DST@@.habitaciones h ON h.id = hrb.habitacion_id WHERE h.hotel_id = 3;
DELETE FROM @@DST@@.movimientos_inventario WHERE hotel_id = 3;
DELETE FROM @@DST@@.inventario_config_habitacion WHERE hotel_id = 3;
DELETE FROM @@DST@@.inventario_productos WHERE hotel_id = 3;
DELETE FROM @@DST@@.inventario_categorias WHERE hotel_id = 3;
DELETE FROM @@DST@@.habitacion_imagenes WHERE hotel_id = 3;
DELETE FROM @@DST@@.mantenimientos_habitaciones WHERE hotel_id = 3;
DELETE FROM @@DST@@.reservacion_pagos WHERE hotel_id = 3;
DELETE FROM @@DST@@.reservacion_notas WHERE hotel_id = 3;
DELETE FROM @@DST@@.reservacion_habitaciones WHERE hotel_id = 3;
DELETE FROM @@DST@@.reservaciones WHERE hotel_id = 3;
DELETE FROM @@DST@@.huesped_vehiculos WHERE hotel_id = 3;
DELETE FROM @@DST@@.huespedes WHERE hotel_id = 3;
DELETE FROM @@DST@@.incrementos_tarifas WHERE hotel_id = 3;
DELETE FROM @@DST@@.categorias_movimientos WHERE hotel_id = 3;
DELETE FROM @@DST@@.tipos_habitacion WHERE hotel_id = 3;
DELETE FROM @@DST@@.habitaciones WHERE hotel_id = 3;
DELETE FROM @@DST@@.hotel_usuarios WHERE hotel_id = 3;

-- usuarios creados por una corrida previa (jamás los fusionados): registro persistente
CREATE TABLE IF NOT EXISTS @@SRC@@._migracion_usuarios_creados (new_id INT PRIMARY KEY);
DELETE l FROM @@DST@@.logs_acceso l JOIN @@SRC@@._migracion_usuarios_creados r ON l.usuario_id = r.new_id;
DELETE u FROM @@DST@@.usuarios u JOIN @@SRC@@._migracion_usuarios_creados r ON u.id = r.new_id;
DELETE FROM @@SRC@@._migracion_usuarios_creados;

-- ---------------------------------------------------------------------------
-- 1b) FOTO DE TENANCY: conteos de todo lo que NO es hotel 3, tomados tras la
--     limpieza y antes de insertar. verificar.sql exige que sigan idénticos —
--     prueba de que la migración no rozó a los demás hoteles (día D incluido).
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS @@SRC@@._tenancy_pre;
CREATE TABLE @@SRC@@._tenancy_pre (tabla VARCHAR(64) PRIMARY KEY, filas_otros BIGINT NOT NULL);
INSERT INTO @@SRC@@._tenancy_pre
SELECT 'habitaciones', COUNT(*) FROM @@DST@@.habitaciones WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'huespedes', COUNT(*) FROM @@DST@@.huespedes WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'reservaciones', COUNT(*) FROM @@DST@@.reservaciones WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'reservacion_habitaciones', COUNT(*) FROM @@DST@@.reservacion_habitaciones WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'reservacion_pagos', COUNT(*) FROM @@DST@@.reservacion_pagos WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'reservacion_notas', COUNT(*) FROM @@DST@@.reservacion_notas WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'huesped_vehiculos', COUNT(*) FROM @@DST@@.huesped_vehiculos WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'movimientos_caja', COUNT(*) FROM @@DST@@.movimientos_caja WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'cortes_caja', COUNT(*) FROM @@DST@@.cortes_caja WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'categorias_movimientos', COUNT(*) FROM @@DST@@.categorias_movimientos WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'incrementos_tarifas', COUNT(*) FROM @@DST@@.incrementos_tarifas WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'mantenimientos_habitaciones', COUNT(*) FROM @@DST@@.mantenimientos_habitaciones WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'tipos_habitacion', COUNT(*) FROM @@DST@@.tipos_habitacion WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'habitacion_imagenes', COUNT(*) FROM @@DST@@.habitacion_imagenes WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'inventario_categorias', COUNT(*) FROM @@DST@@.inventario_categorias WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'inventario_productos', COUNT(*) FROM @@DST@@.inventario_productos WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'inventario_config_habitacion', COUNT(*) FROM @@DST@@.inventario_config_habitacion WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'movimientos_inventario', COUNT(*) FROM @@DST@@.movimientos_inventario WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'hotel_usuarios', COUNT(*) FROM @@DST@@.hotel_usuarios WHERE COALESCE(hotel_id,0) <> 3
UNION ALL SELECT 'control_llaves', (SELECT COUNT(*) FROM @@DST@@.control_llaves cl JOIN @@DST@@.habitaciones h ON h.id = cl.habitacion_id WHERE COALESCE(h.hotel_id,0) <> 3)
UNION ALL SELECT 'historial_llaves', (SELECT COUNT(*) FROM @@DST@@.historial_llaves hl JOIN @@DST@@.habitaciones h ON h.id = hl.habitacion_id WHERE COALESCE(h.hotel_id,0) <> 3)
UNION ALL SELECT 'control_remotos', (SELECT COUNT(*) FROM @@DST@@.control_remotos cr JOIN @@DST@@.habitaciones h ON h.id = cr.habitacion_id WHERE COALESCE(h.hotel_id,0) <> 3)
UNION ALL SELECT 'historial_remotos', (SELECT COUNT(*) FROM @@DST@@.historial_remotos hr JOIN @@DST@@.habitaciones h ON h.id = hr.habitacion_id WHERE COALESCE(h.hotel_id,0) <> 3)
UNION ALL SELECT 'denominaciones_efectivo', (SELECT COUNT(*) FROM @@DST@@.denominaciones_efectivo d JOIN @@DST@@.cortes_caja c ON c.id = d.corte_id WHERE COALESCE(c.hotel_id,0) <> 3)
UNION ALL SELECT 'usuarios_total_pre', COUNT(*) FROM @@DST@@.usuarios
UNION ALL SELECT 'logs_acceso_total_pre', COUNT(*) FROM @@DST@@.logs_acceso;

-- ---------------------------------------------------------------------------
-- 2) MAPAS old_id → new_id (viven en el esquema staging, se rehacen por corrida)
--    new_id = MAX(id) del destino + ROW_NUMBER() ordenado por old_id.
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS @@SRC@@._map_habitaciones, @@SRC@@._map_huespedes, @@SRC@@._map_reservaciones,
  @@SRC@@._map_cortes, @@SRC@@._map_usuarios, @@SRC@@._map_categorias, @@SRC@@._cat_grupos,
  @@SRC@@._map_inv_categorias, @@SRC@@._map_inv_productos;

SET @off_hab  = (SELECT COALESCE(MAX(id),0) FROM @@DST@@.habitaciones);
SET @off_hue  = (SELECT COALESCE(MAX(id),0) FROM @@DST@@.huespedes);
SET @off_res  = (SELECT COALESCE(MAX(id),0) FROM @@DST@@.reservaciones);
SET @off_cor  = (SELECT COALESCE(MAX(id),0) FROM @@DST@@.cortes_caja);
SET @off_usr  = (SELECT COALESCE(MAX(id),0) FROM @@DST@@.usuarios);
SET @off_cat  = (SELECT COALESCE(MAX(id),0) FROM @@DST@@.categorias_movimientos);
SET @off_icat = (SELECT COALESCE(MAX(id),0) FROM @@DST@@.inventario_categorias);
SET @off_ipro = (SELECT COALESCE(MAX(id),0) FROM @@DST@@.inventario_productos);

CREATE TABLE @@SRC@@._map_habitaciones (old_id INT PRIMARY KEY, new_id INT NOT NULL)
SELECT id AS old_id, @off_hab + ROW_NUMBER() OVER (ORDER BY id) AS new_id FROM @@SRC@@.habitaciones;

CREATE TABLE @@SRC@@._map_huespedes (old_id INT PRIMARY KEY, new_id INT NOT NULL)
SELECT id AS old_id, @off_hue + ROW_NUMBER() OVER (ORDER BY id) AS new_id FROM @@SRC@@.huespedes;

CREATE TABLE @@SRC@@._map_reservaciones (old_id INT PRIMARY KEY, new_id INT NOT NULL)
SELECT id AS old_id, @off_res + ROW_NUMBER() OVER (ORDER BY id) AS new_id FROM @@SRC@@.reservaciones;

CREATE TABLE @@SRC@@._map_cortes (old_id INT PRIMARY KEY, new_id INT NOT NULL)
SELECT id AS old_id, @off_cor + ROW_NUMBER() OVER (ORDER BY id) AS new_id FROM @@SRC@@.cortes_caja;

-- Usuarios: fusión por nombre_usuario (case-insensitive) con los ya existentes
-- en el destino (hoy solo colisiona `admin`, mismo nombre completo); el resto se crea.
CREATE TABLE @@SRC@@._map_usuarios (old_id INT PRIMARY KEY, new_id INT NOT NULL, accion VARCHAR(10) NOT NULL);
INSERT INTO @@SRC@@._map_usuarios
SELECT l.id, d.id, 'fusionado'
FROM @@SRC@@.usuarios l JOIN @@DST@@.usuarios d ON LOWER(d.nombre_usuario) = LOWER(l.nombre_usuario);
INSERT INTO @@SRC@@._map_usuarios
SELECT l.id, @off_usr + ROW_NUMBER() OVER (ORDER BY l.id), 'creado'
FROM @@SRC@@.usuarios l
WHERE NOT EXISTS (SELECT 1 FROM @@SRC@@._map_usuarios m WHERE m.old_id = l.id);

-- Categorías de caja: el legado trae 46 filas con tipo='' (secuela del bug del
-- enum 'egreso' en MariaDB laxa; 46 son "Devoluciones" duplicadas). Se normaliza:
-- tipo efectivo = el declarado, o el tipo dominante de sus movimientos, o 'gasto';
-- después se DEDUPLICA por (nombre, tipo efectivo) → ~9 categorías limpias.
CREATE TABLE @@SRC@@._cat_grupos (
  nombre VARCHAR(100) NOT NULL, tipo_efectivo VARCHAR(10) NOT NULL,
  rep_id INT NOT NULL, new_id INT NOT NULL, PRIMARY KEY (nombre, tipo_efectivo)
)
SELECT nombre, tipo_efectivo, MIN(old_id) AS rep_id,
       @off_cat + ROW_NUMBER() OVER (ORDER BY MIN(old_id)) AS new_id
FROM (
  SELECT c.id AS old_id, c.nombre,
         CASE WHEN c.tipo IN ('ingreso','gasto') THEN c.tipo
              ELSE COALESCE(
                (SELECT m.tipo FROM @@SRC@@.movimientos_caja m
                 WHERE m.categoria_id = c.id GROUP BY m.tipo ORDER BY COUNT(*) DESC, m.tipo LIMIT 1),
                'gasto') END AS tipo_efectivo
  FROM @@SRC@@.categorias_movimientos c
) n GROUP BY nombre, tipo_efectivo;

CREATE TABLE @@SRC@@._map_categorias (old_id INT PRIMARY KEY, new_id INT NOT NULL)
SELECT n.old_id, g.new_id
FROM (
  SELECT c.id AS old_id, c.nombre,
         CASE WHEN c.tipo IN ('ingreso','gasto') THEN c.tipo
              ELSE COALESCE(
                (SELECT m.tipo FROM @@SRC@@.movimientos_caja m
                 WHERE m.categoria_id = c.id GROUP BY m.tipo ORDER BY COUNT(*) DESC, m.tipo LIMIT 1),
                'gasto') END AS tipo_efectivo
  FROM @@SRC@@.categorias_movimientos c
) n JOIN @@SRC@@._cat_grupos g ON g.nombre = n.nombre AND g.tipo_efectivo = n.tipo_efectivo;

CREATE TABLE @@SRC@@._map_inv_categorias (old_id INT PRIMARY KEY, new_id INT NOT NULL)
SELECT id AS old_id, @off_icat + ROW_NUMBER() OVER (ORDER BY id) AS new_id FROM @@SRC@@.inventario_categorias;

CREATE TABLE @@SRC@@._map_inv_productos (old_id INT PRIMARY KEY, new_id INT NOT NULL)
SELECT id AS old_id, @off_ipro + ROW_NUMBER() OVER (ORDER BY id) AS new_id FROM @@SRC@@.inventario_productos;

-- ---------------------------------------------------------------------------
-- 3) HABITACIONES (66). Los tipos jacuzzi COLAPSAN al tipo base, que es la
--    convención viva de la app (hotel_room_catalog_type_aliases:
--    doble_jacuzzi→doble, sencilla_jacuzzi→sencilla); el matiz "con jacuzzi"
--    sigue vivo en `caracteristicas`, en el precio y en tipos_habitacion (§4).
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.habitaciones
  (id, hotel_id, numero, tipo, capacidad_personas, camas_individuales, camas_matrimoniales,
   piso, precio_base, estado, caracteristicas, tiene_aire_acondicionado, tiene_tv,
   tiene_bano_privado, foto_url, activa, created_at, updated_at)
SELECT m.new_id, 3, h.numero,
       CASE h.tipo WHEN 'doble_jacuzzi' THEN 'doble' WHEN 'sencilla_jacuzzi' THEN 'sencilla' ELSE h.tipo END,
       h.capacidad_personas, h.camas_individuales, h.camas_matrimoniales,
       h.piso, h.precio_base, h.estado, h.caracteristicas, h.tiene_aire_acondicionado, h.tiene_tv,
       h.tiene_bano_privado, h.foto_url, h.activa, h.created_at, h.updated_at
FROM @@SRC@@.habitaciones h JOIN @@SRC@@._map_habitaciones m ON m.old_id = h.id;

-- ---------------------------------------------------------------------------
-- 4) TIPOS DE HABITACIÓN (catálogo del hotel, 6 filas). Alimenta directo el
--    catálogo por hotel (hotel_room_catalog_default_type_rows lee esta tabla);
--    los códigos *_jacuzzi son válidos como código configurable vía aliases.
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.tipos_habitacion
  (hotel_id, codigo, nombre, descripcion, capacidad_default, precio_base_default, activo, orden, created_at, updated_at)
SELECT 3, codigo, nombre, descripcion, capacidad_default, precio_base_default, activo, orden, created_at, updated_at
FROM @@SRC@@.tipos_habitacion;

-- ---------------------------------------------------------------------------
-- 5) HUÉSPEDES (1,958) y sus VEHÍCULOS (326 con dueño; 13 huérfanos se excluyen)
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.huespedes
  (id, hotel_id, nombre_completo, telefono, email, procedencia_estado, procedencia_ciudad,
   vehiculo_marca, vehiculo_placas, notas, created_at, updated_at)
SELECT m.new_id, 3, h.nombre_completo, h.telefono, h.email, h.procedencia_estado, h.procedencia_ciudad,
       h.vehiculo_marca, h.vehiculo_placas, h.notas, h.created_at, h.updated_at
FROM @@SRC@@.huespedes h JOIN @@SRC@@._map_huespedes m ON m.old_id = h.id;

INSERT INTO @@DST@@.huesped_vehiculos
  (hotel_id, huesped_id, marca, modelo, placas, color, estacionamiento, activo, created_at, updated_at)
SELECT 3, m.new_id, v.marca, v.modelo, v.placas, v.color, v.estacionamiento, v.activo, v.created_at, v.updated_at
FROM @@SRC@@.huesped_vehiculos v JOIN @@SRC@@._map_huespedes m ON m.old_id = v.huesped_id;

-- ---------------------------------------------------------------------------
-- 6) USUARIOS: se crean los que no existen (conservan SU contraseña bcrypt del
--    legado) y todos (creados + fusionados) reciben su membresía en el hotel 3
--    con el rol homónimo sembrado (gerente/administrador/recepcionista).
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.usuarios
  (id, nombre_usuario, password, nombre_completo, email, telefono, rol, activo, ultimo_login, ip_ultimo_login, created_at, updated_at)
SELECT m.new_id, u.nombre_usuario, u.password, u.nombre_completo, u.email, u.telefono, u.rol, u.activo,
       u.ultimo_login, u.ip_ultimo_login, u.created_at, u.updated_at
FROM @@SRC@@.usuarios u JOIN @@SRC@@._map_usuarios m ON m.old_id = u.id AND m.accion = 'creado';

INSERT INTO @@SRC@@._migracion_usuarios_creados
SELECT new_id FROM @@SRC@@._map_usuarios WHERE accion = 'creado';

INSERT INTO @@DST@@.hotel_usuarios (hotel_id, usuario_id, rol, role_id, activo, created_at, updated_at)
SELECT 3, m.new_id, u.rol,
       (SELECT r.id FROM @@DST@@.roles r WHERE r.hotel_id = 3 AND r.clave = u.rol),
       u.activo, u.created_at, u.updated_at
FROM @@SRC@@.usuarios u JOIN @@SRC@@._map_usuarios m ON m.old_id = u.id;

-- ---------------------------------------------------------------------------
-- 7) CATEGORÍAS DE CAJA normalizadas (~9). Los atributos salen de la fila
--    representante (MIN id) de cada grupo.
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.categorias_movimientos
  (id, hotel_id, nombre, tipo, descripcion, icono, color, activa, orden, created_at)
SELECT g.new_id, 3, g.nombre, g.tipo_efectivo, c.descripcion, c.icono, c.color, c.activa, c.orden, c.created_at
FROM @@SRC@@._cat_grupos g JOIN @@SRC@@.categorias_movimientos c ON c.id = g.rep_id;

-- ---------------------------------------------------------------------------
-- 8) RESERVACIONES (2,401) con la nota de rastro "Folio del sistema anterior".
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.reservaciones
  (id, hotel_id, huesped_id, total_habitaciones, habitaciones_cortesia, fecha_entrada,
   hora_llegada_estimada, hora_entrada, fecha_salida, hora_salida, precio_total,
   monto_recibido, cambio, metodo_pago, estado, notas, usuario_registro_id, created_at, updated_at)
SELECT mr.new_id, 3, mh.new_id, r.total_habitaciones, r.habitaciones_cortesia, r.fecha_entrada,
       r.hora_llegada_estimada, r.hora_entrada, r.fecha_salida, r.hora_salida, r.precio_total,
       r.monto_recibido, r.cambio, r.metodo_pago, r.estado,
       CONCAT(COALESCE(NULLIF(TRIM(r.notas), ''), ''),
              CASE WHEN COALESCE(NULLIF(TRIM(r.notas), ''), '') = '' THEN '' ELSE '\n' END,
              'Folio del sistema anterior: #', r.id),
       mu.new_id, r.created_at, r.updated_at
FROM @@SRC@@.reservaciones r
JOIN @@SRC@@._map_reservaciones mr ON mr.old_id = r.id
JOIN @@SRC@@._map_huespedes mh ON mh.old_id = r.huesped_id
LEFT JOIN @@SRC@@._map_usuarios mu ON mu.old_id = r.usuario_registro_id;

INSERT INTO @@DST@@.reservacion_habitaciones (id, hotel_id, reservacion_id, habitacion_id, precio, es_cortesia)
SELECT NULL, 3, mr.new_id, mh.new_id, rh.precio, rh.es_cortesia
FROM @@SRC@@.reservacion_habitaciones rh
JOIN @@SRC@@._map_reservaciones mr ON mr.old_id = rh.reservacion_id
JOIN @@SRC@@._map_habitaciones mh ON mh.old_id = rh.habitacion_id;

INSERT INTO @@DST@@.reservacion_pagos (hotel_id, reservacion_id, metodo_pago, monto, referencia, created_at)
SELECT 3, mr.new_id, p.metodo_pago, p.monto, p.referencia, p.created_at
FROM @@SRC@@.reservacion_pagos p JOIN @@SRC@@._map_reservaciones mr ON mr.old_id = p.reservacion_id;

INSERT INTO @@DST@@.reservacion_notas (hotel_id, reservacion_id, usuario_id, nota, created_at)
SELECT 3, mr.new_id, mu.new_id, n.nota, n.created_at
FROM @@SRC@@.reservacion_notas n
JOIN @@SRC@@._map_reservaciones mr ON mr.old_id = n.reservacion_id
LEFT JOIN @@SRC@@._map_usuarios mu ON mu.old_id = n.usuario_id;

-- ---------------------------------------------------------------------------
-- 9) CAJA. Cortes (143): los cerrados van VERBATIM (sus descuadres son herencia
--    del legado, no de la migración); el ÚNICO abierto se importa CERRADO:
--    totales re-derivados de sus movimientos, efectivo esperado = inicial +
--    ingresos - gastos en efectivo, contado = esperado (nadie contó ese cajón:
--    diferencia 0 y nota de migración lo dejan explícito).
--    caja_id: la única caja legada (1) es la caja principal del hotel 3 (id 3).
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.cortes_caja
  (id, hotel_id, caja_id, fecha_apertura, fecha_cierre, monto_inicial,
   total_ingresos_efectivo, total_ingresos_tarjeta, total_ingresos_transferencia,
   total_gastos_efectivo, total_gastos_tarjeta, total_gastos_transferencia,
   efectivo_esperado, efectivo_contado, diferencia, estado, observaciones,
   usuario_apertura_id, usuario_cierre_id, created_at, updated_at)
SELECT m.new_id, 3, 3, c.fecha_apertura,
       CASE WHEN c.estado = 'abierto'
            THEN COALESCE((SELECT MAX(mc.created_at) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id), c.fecha_apertura)
            ELSE c.fecha_cierre END,
       c.monto_inicial,
       CASE WHEN c.estado = 'abierto' THEN COALESCE((SELECT SUM(mc.monto) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id AND mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo'), 0) ELSE c.total_ingresos_efectivo END,
       CASE WHEN c.estado = 'abierto' THEN COALESCE((SELECT SUM(mc.monto) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id AND mc.tipo = 'ingreso' AND mc.metodo_pago = 'tarjeta'), 0) ELSE c.total_ingresos_tarjeta END,
       CASE WHEN c.estado = 'abierto' THEN COALESCE((SELECT SUM(mc.monto) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id AND mc.tipo = 'ingreso' AND mc.metodo_pago = 'transferencia'), 0) ELSE c.total_ingresos_transferencia END,
       CASE WHEN c.estado = 'abierto' THEN COALESCE((SELECT SUM(mc.monto) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id AND mc.tipo = 'gasto' AND mc.metodo_pago = 'efectivo'), 0) ELSE c.total_gastos_efectivo END,
       CASE WHEN c.estado = 'abierto' THEN COALESCE((SELECT SUM(mc.monto) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id AND mc.tipo = 'gasto' AND mc.metodo_pago = 'tarjeta'), 0) ELSE c.total_gastos_tarjeta END,
       CASE WHEN c.estado = 'abierto' THEN COALESCE((SELECT SUM(mc.monto) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id AND mc.tipo = 'gasto' AND mc.metodo_pago = 'transferencia'), 0) ELSE c.total_gastos_transferencia END,
       CASE WHEN c.estado = 'abierto'
            THEN c.monto_inicial
                 + COALESCE((SELECT SUM(mc.monto) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id AND mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo'), 0)
                 - COALESCE((SELECT SUM(mc.monto) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id AND mc.tipo = 'gasto' AND mc.metodo_pago = 'efectivo'), 0)
            ELSE c.efectivo_esperado END,
       CASE WHEN c.estado = 'abierto'
            THEN c.monto_inicial
                 + COALESCE((SELECT SUM(mc.monto) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id AND mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo'), 0)
                 - COALESCE((SELECT SUM(mc.monto) FROM @@SRC@@.movimientos_caja mc WHERE mc.corte_id = c.id AND mc.tipo = 'gasto' AND mc.metodo_pago = 'efectivo'), 0)
            ELSE c.efectivo_contado END,
       CASE WHEN c.estado = 'abierto' THEN 0 ELSE c.diferencia END,
       'cerrado',
       CASE WHEN c.estado = 'abierto'
            THEN CONCAT(COALESCE(NULLIF(TRIM(c.observaciones), ''), ''),
                        CASE WHEN COALESCE(NULLIF(TRIM(c.observaciones), ''), '') = '' THEN '' ELSE '\n' END,
                        '[Migracion] Corte que quedo abierto en el sistema anterior; se cerro automaticamente al migrar con totales derivados de sus movimientos.')
            ELSE c.observaciones END,
       ua.new_id, COALESCE(uc.new_id, CASE WHEN c.estado = 'abierto' THEN ua.new_id ELSE NULL END),
       c.created_at, c.updated_at
FROM @@SRC@@.cortes_caja c
JOIN @@SRC@@._map_cortes m ON m.old_id = c.id
JOIN @@SRC@@._map_usuarios ua ON ua.old_id = c.usuario_apertura_id
LEFT JOIN @@SRC@@._map_usuarios uc ON uc.old_id = c.usuario_cierre_id;

INSERT INTO @@DST@@.denominaciones_efectivo (corte_id, denominacion, cantidad)
SELECT m.new_id, d.denominacion, d.cantidad
FROM @@SRC@@.denominaciones_efectivo d JOIN @@SRC@@._map_cortes m ON m.old_id = d.corte_id;

-- Movimientos (1,761). 18 traen corte_id que apunta a un corte inexistente en el
-- legado (y reservacion_id igual de roto): viajan con corte/reservación NULL —
-- el feed de caja los agrupa como "Sin turno asignado". 2 traen categoría
-- huérfana: viajan con categoria_id NULL (el texto `categoria` se conserva).
INSERT INTO @@DST@@.movimientos_caja
  (hotel_id, tipo, categoria, categoria_id, descripcion, monto, metodo_pago, referencia,
   comprobante, proveedor, reservacion_id, usuario_id, corte_id, created_at,
   editado, motivo_edicion, usuario_edicion_id, fecha_edicion)
SELECT 3, mc.tipo, mc.categoria, mcat.new_id, mc.descripcion, mc.monto, mc.metodo_pago, mc.referencia,
       mc.comprobante, mc.proveedor, mres.new_id, mu.new_id, mcor.new_id, mc.created_at,
       mc.editado, mc.motivo_edicion, mue.new_id, mc.fecha_edicion
FROM @@SRC@@.movimientos_caja mc
LEFT JOIN @@SRC@@._map_categorias mcat ON mcat.old_id = mc.categoria_id
LEFT JOIN @@SRC@@._map_reservaciones mres ON mres.old_id = mc.reservacion_id
LEFT JOIN @@SRC@@._map_usuarios mu ON mu.old_id = mc.usuario_id
LEFT JOIN @@SRC@@._map_usuarios mue ON mue.old_id = mc.usuario_edicion_id
LEFT JOIN @@SRC@@._map_cortes mcor ON mcor.old_id = mc.corte_id
ORDER BY mc.id;

-- ---------------------------------------------------------------------------
-- 10) LLAVES Y REMOTOS (sin hotel_id: la tenencia viaja por habitacion_id).
--     280 historial_llaves y 21 historial_remotos apuntan a reservaciones que ya
--     no existen en el legado → viajan con reservacion_id NULL, la fila se queda.
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.control_llaves
  (habitacion_id, reservacion_id, estado, fecha_prestamo, fecha_devolucion,
   usuario_presta_id, usuario_recibe_id, notas, created_at, updated_at, tiene_llave,
   ultima_entrega_at, ultima_recogida_at, entregada_por_id, recibida_por_id,
   recibida_por_manual, entregada_por_manual)
SELECT mh.new_id, mres.new_id, cl.estado, cl.fecha_prestamo, cl.fecha_devolucion,
       up.new_id, ur.new_id, cl.notas, cl.created_at, cl.updated_at, cl.tiene_llave,
       cl.ultima_entrega_at, cl.ultima_recogida_at, ue.new_id, urx.new_id,
       cl.recibida_por_manual, cl.entregada_por_manual
FROM @@SRC@@.control_llaves cl
JOIN @@SRC@@._map_habitaciones mh ON mh.old_id = cl.habitacion_id
LEFT JOIN @@SRC@@._map_reservaciones mres ON mres.old_id = cl.reservacion_id
LEFT JOIN @@SRC@@._map_usuarios up ON up.old_id = cl.usuario_presta_id
LEFT JOIN @@SRC@@._map_usuarios ur ON ur.old_id = cl.usuario_recibe_id
LEFT JOIN @@SRC@@._map_usuarios ue ON ue.old_id = cl.entregada_por_id
LEFT JOIN @@SRC@@._map_usuarios urx ON urx.old_id = cl.recibida_por_id;

INSERT INTO @@DST@@.historial_llaves
  (habitacion_id, reservacion_id, tipo_movimiento, fecha_hora, usuario_id, usuario_manual, notas, created_at)
SELECT mh.new_id, mres.new_id, hl.tipo_movimiento, hl.fecha_hora, mu.new_id, hl.usuario_manual, hl.notas, hl.created_at
FROM @@SRC@@.historial_llaves hl
JOIN @@SRC@@._map_habitaciones mh ON mh.old_id = hl.habitacion_id
LEFT JOIN @@SRC@@._map_reservaciones mres ON mres.old_id = hl.reservacion_id
LEFT JOIN @@SRC@@._map_usuarios mu ON mu.old_id = hl.usuario_id
ORDER BY hl.id;

INSERT INTO @@DST@@.control_remotos
  (habitacion_id, reservacion_id, tiene_remoto, ultima_entrega_at, ultima_recogida_at,
   entregada_por_id, recibida_por_id, entregada_por_manual, recibida_por_manual,
   tipo_identificacion, nombre_propietario_ine, notas, created_at, updated_at)
SELECT mh.new_id, mres.new_id, cr.tiene_remoto, cr.ultima_entrega_at, cr.ultima_recogida_at,
       ue.new_id, ur.new_id, cr.entregada_por_manual, cr.recibida_por_manual,
       cr.tipo_identificacion, cr.nombre_propietario_ine, cr.notas, cr.created_at, cr.updated_at
FROM @@SRC@@.control_remotos cr
JOIN @@SRC@@._map_habitaciones mh ON mh.old_id = cr.habitacion_id
LEFT JOIN @@SRC@@._map_reservaciones mres ON mres.old_id = cr.reservacion_id
LEFT JOIN @@SRC@@._map_usuarios ue ON ue.old_id = cr.entregada_por_id
LEFT JOIN @@SRC@@._map_usuarios ur ON ur.old_id = cr.recibida_por_id;

INSERT INTO @@DST@@.historial_remotos
  (habitacion_id, reservacion_id, tipo_movimiento, fecha_hora, usuario_id, usuario_manual,
   tipo_identificacion, nombre_propietario_ine, notas, created_at)
SELECT mh.new_id, mres.new_id, hr.tipo_movimiento, hr.fecha_hora, mu.new_id, hr.usuario_manual,
       hr.tipo_identificacion, hr.nombre_propietario_ine, hr.notas, hr.created_at
FROM @@SRC@@.historial_remotos hr
JOIN @@SRC@@._map_habitaciones mh ON mh.old_id = hr.habitacion_id
LEFT JOIN @@SRC@@._map_reservaciones mres ON mres.old_id = hr.reservacion_id
LEFT JOIN @@SRC@@._map_usuarios mu ON mu.old_id = hr.usuario_id
ORDER BY hr.id;

INSERT INTO @@DST@@.control_remotos_backup
  (habitacion_id, reservacion_id, tiene_remoto, ultima_entrega_at, ultima_recogida_at,
   entregada_por_id, recibida_por_id, entregada_por_manual, recibida_por_manual,
   tipo_identificacion, notas, created_at, updated_at)
SELECT mh.new_id, mres.new_id, cb.tiene_remoto, cb.ultima_entrega_at, cb.ultima_recogida_at,
       ue.new_id, ur.new_id, cb.entregada_por_manual, cb.recibida_por_manual,
       cb.tipo_identificacion, cb.notas, cb.created_at, cb.updated_at
FROM @@SRC@@.control_remotos_backup cb
JOIN @@SRC@@._map_habitaciones mh ON mh.old_id = cb.habitacion_id
LEFT JOIN @@SRC@@._map_reservaciones mres ON mres.old_id = cb.reservacion_id
LEFT JOIN @@SRC@@._map_usuarios ue ON ue.old_id = cb.entregada_por_id
LEFT JOIN @@SRC@@._map_usuarios ur ON ur.old_id = cb.recibida_por_id;

INSERT INTO @@DST@@.historial_remotos_backup
  (habitacion_id, reservacion_id, tipo_movimiento, fecha_hora, usuario_id, usuario_manual,
   tipo_identificacion, notas, created_at)
SELECT mh.new_id, mres.new_id, hb.tipo_movimiento, hb.fecha_hora, mu.new_id, hb.usuario_manual,
       hb.tipo_identificacion, hb.notas, hb.created_at
FROM @@SRC@@.historial_remotos_backup hb
JOIN @@SRC@@._map_habitaciones mh ON mh.old_id = hb.habitacion_id
LEFT JOIN @@SRC@@._map_reservaciones mres ON mres.old_id = hb.reservacion_id
LEFT JOIN @@SRC@@._map_usuarios mu ON mu.old_id = hb.usuario_id;

-- ---------------------------------------------------------------------------
-- 11) MANTENIMIENTOS (25, todos completados). Los 25 traen usuario_registro
--     huérfano en el legado → caen al admin fusionado del destino.
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.mantenimientos_habitaciones
  (hotel_id, habitacion_id, tipo_mantenimiento, motivo, descripcion, prioridad,
   fecha_inicio, fecha_fin, estado, realizado_por, costo, observaciones,
   usuario_registro_id, created_at, updated_at)
SELECT 3, mh.new_id, mm.tipo_mantenimiento, mm.motivo, mm.descripcion, mm.prioridad,
       mm.fecha_inicio, mm.fecha_fin, mm.estado, mm.realizado_por, mm.costo, mm.observaciones,
       COALESCE(mu.new_id, (SELECT new_id FROM @@SRC@@._map_usuarios WHERE accion = 'fusionado' LIMIT 1)),
       mm.created_at, mm.updated_at
FROM @@SRC@@.mantenimientos_habitaciones mm
JOIN @@SRC@@._map_habitaciones mh ON mh.old_id = mm.habitacion_id
LEFT JOIN @@SRC@@._map_usuarios mu ON mu.old_id = mm.usuario_registro_id;

-- ---------------------------------------------------------------------------
-- 12) TARIFAS DINÁMICAS (4 incrementos monto_fijo por habitación; 2 permanentes
--     activos +50/+100 y 2 de Semana Santa vencidos). El JSON de habitaciones
--     se REMAPEA id por id (convención TarifasController: arreglo de strings).
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.incrementos_tarifas
  (hotel_id, nombre, descripcion, tipo_incremento, clase, valor_incremento, alcance,
   tipos_habitacion, habitaciones, es_permanente, fecha_inicio, fecha_fin, activo,
   prioridad, usuario_id, created_at, updated_at)
SELECT 3, i.nombre, i.descripcion, i.tipo_incremento, 'incremento', i.valor_incremento, i.alcance,
       i.tipos_habitacion,
       CASE WHEN i.habitaciones IS NULL THEN NULL ELSE
         (SELECT JSON_ARRAYAGG(CAST(m.new_id AS CHAR))
          FROM JSON_TABLE(i.habitaciones, '$[*]' COLUMNS (old_id INT PATH '$')) jt
          JOIN @@SRC@@._map_habitaciones m ON m.old_id = jt.old_id)
       END,
       i.es_permanente, i.fecha_inicio, i.fecha_fin, i.activo,
       i.prioridad, mu.new_id, i.created_at, i.updated_at
FROM @@SRC@@.incrementos_tarifas i
JOIN @@SRC@@._map_usuarios mu ON mu.old_id = i.usuario_id;

-- ---------------------------------------------------------------------------
-- 13) INVENTARIO v2 (el vivo en la app). La config por tipo de habitación migra
--     SOLO los tipos base: tras el colapso jacuzzi→base, las filas jacuzzi
--     duplicarían la clave única (hotel, tipo, producto) y el producto exclusivo
--     de jacuzzi aplicaría a TODAS las dobles/sencillas (sobre-descuento).
--     Las filas excluidas quedan cuantificadas en verificar.sql y en el README.
-- ---------------------------------------------------------------------------
INSERT INTO @@DST@@.inventario_categorias (id, hotel_id, nombre, descripcion, orden, activo, created_at)
SELECT m.new_id, 3, c.nombre, c.descripcion, c.orden, c.activo, c.created_at
FROM @@SRC@@.inventario_categorias c JOIN @@SRC@@._map_inv_categorias m ON m.old_id = c.id;

INSERT INTO @@DST@@.inventario_productos
  (id, hotel_id, codigo, nombre, descripcion, categoria_id, unidad_medida, stock_actual,
   stock_minimo, costo_unitario, descuento_automatico, activo, created_at, updated_at)
SELECT m.new_id, 3, p.codigo, p.nombre, p.descripcion, mc.new_id, p.unidad_medida, p.stock_actual,
       p.stock_minimo, p.costo_unitario, p.descuento_automatico, p.activo, p.created_at, p.updated_at
FROM @@SRC@@.inventario_productos p
JOIN @@SRC@@._map_inv_productos m ON m.old_id = p.id
LEFT JOIN @@SRC@@._map_inv_categorias mc ON mc.old_id = p.categoria_id;

INSERT INTO @@DST@@.inventario_config_habitacion (hotel_id, tipo_habitacion, producto_id, cantidad_descontar, activo, created_at)
SELECT 3, cf.tipo_habitacion, mp.new_id, cf.cantidad_descontar, cf.activo, cf.created_at
FROM @@SRC@@.inventario_config_habitacion cf
JOIN @@SRC@@._map_inv_productos mp ON mp.old_id = cf.producto_id
WHERE cf.tipo_habitacion NOT IN ('doble_jacuzzi', 'sencilla_jacuzzi');

INSERT INTO @@DST@@.movimientos_inventario
  (hotel_id, producto_id, tipo_movimiento, cantidad, stock_anterior, stock_posterior,
   motivo, habitacion_id, reservacion_id, usuario_id, created_at, updated_at)
SELECT 3, mp.new_id, mi.tipo_movimiento, mi.cantidad, mi.stock_anterior, mi.stock_posterior,
       mi.motivo, mh.new_id, mres.new_id, mu.new_id, mi.created_at, mi.updated_at
FROM @@SRC@@.movimientos_inventario mi
JOIN @@SRC@@._map_inv_productos mp ON mp.old_id = mi.producto_id
LEFT JOIN @@SRC@@._map_habitaciones mh ON mh.old_id = mi.habitacion_id
LEFT JOIN @@SRC@@._map_reservaciones mres ON mres.old_id = mi.reservacion_id
LEFT JOIN @@SRC@@._map_usuarios mu ON mu.old_id = mi.usuario_id
ORDER BY mi.id;

-- ---------------------------------------------------------------------------
-- 14) IMÁGENES de habitación (2; los ARCHIVOS físicos viajan por scp el día D)
--     y LOGS de acceso (2,228; 27 sin usuario resoluble viajan con NULL).
-- ---------------------------------------------------------------------------
-- El legado traía las 2 imágenes del mismo cuarto marcadas es_principal=1 a la vez:
-- solo la primera (menor id) conserva la marca, para que la portada sea determinista.
INSERT INTO @@DST@@.habitacion_imagenes (hotel_id, habitacion_id, url, descripcion, es_principal, orden, created_at, updated_at)
SELECT 3, mh.new_id, hi.url, hi.descripcion,
       IF(hi.es_principal = 1 AND hi.id = (SELECT MIN(x.id) FROM @@SRC@@.habitacion_imagenes x
                                           WHERE x.habitacion_id = hi.habitacion_id AND x.es_principal = 1), 1, 0),
       hi.orden, hi.created_at, hi.updated_at
FROM @@SRC@@.habitacion_imagenes hi JOIN @@SRC@@._map_habitaciones mh ON mh.old_id = hi.habitacion_id;

INSERT INTO @@DST@@.logs_acceso (tipo, usuario_id, exitoso, ip, user_agent, detalles, created_at)
SELECT l.tipo, mu.new_id, l.exitoso, l.ip, l.user_agent, l.detalles, l.created_at
FROM @@SRC@@.logs_acceso l
LEFT JOIN @@SRC@@._map_usuarios mu ON mu.old_id = l.usuario_id
ORDER BY l.id;

-- ---------------------------------------------------------------------------
-- 15) RESUMEN de la corrida
-- ---------------------------------------------------------------------------
SELECT 'habitaciones' AS tabla, COUNT(*) AS importadas FROM @@DST@@.habitaciones WHERE hotel_id = 3
UNION ALL SELECT 'tipos_habitacion', COUNT(*) FROM @@DST@@.tipos_habitacion WHERE hotel_id = 3
UNION ALL SELECT 'huespedes', COUNT(*) FROM @@DST@@.huespedes WHERE hotel_id = 3
UNION ALL SELECT 'huesped_vehiculos', COUNT(*) FROM @@DST@@.huesped_vehiculos WHERE hotel_id = 3
UNION ALL SELECT 'usuarios_creados', (SELECT COUNT(*) FROM @@SRC@@._map_usuarios WHERE accion = 'creado')
UNION ALL SELECT 'usuarios_fusionados', (SELECT COUNT(*) FROM @@SRC@@._map_usuarios WHERE accion = 'fusionado')
UNION ALL SELECT 'hotel_usuarios', COUNT(*) FROM @@DST@@.hotel_usuarios WHERE hotel_id = 3
UNION ALL SELECT 'categorias_movimientos', COUNT(*) FROM @@DST@@.categorias_movimientos WHERE hotel_id = 3
UNION ALL SELECT 'reservaciones', COUNT(*) FROM @@DST@@.reservaciones WHERE hotel_id = 3
UNION ALL SELECT 'reservacion_habitaciones', COUNT(*) FROM @@DST@@.reservacion_habitaciones WHERE hotel_id = 3
UNION ALL SELECT 'reservacion_pagos', COUNT(*) FROM @@DST@@.reservacion_pagos WHERE hotel_id = 3
UNION ALL SELECT 'reservacion_notas', COUNT(*) FROM @@DST@@.reservacion_notas WHERE hotel_id = 3
UNION ALL SELECT 'cortes_caja', COUNT(*) FROM @@DST@@.cortes_caja WHERE hotel_id = 3
UNION ALL SELECT 'denominaciones', (SELECT COUNT(*) FROM @@DST@@.denominaciones_efectivo d JOIN @@DST@@.cortes_caja c ON c.id = d.corte_id WHERE c.hotel_id = 3)
UNION ALL SELECT 'movimientos_caja', COUNT(*) FROM @@DST@@.movimientos_caja WHERE hotel_id = 3
UNION ALL SELECT 'control_llaves', (SELECT COUNT(*) FROM @@DST@@.control_llaves cl JOIN @@DST@@.habitaciones h ON h.id = cl.habitacion_id WHERE h.hotel_id = 3)
UNION ALL SELECT 'historial_llaves', (SELECT COUNT(*) FROM @@DST@@.historial_llaves hl JOIN @@DST@@.habitaciones h ON h.id = hl.habitacion_id WHERE h.hotel_id = 3)
UNION ALL SELECT 'control_remotos', (SELECT COUNT(*) FROM @@DST@@.control_remotos cr JOIN @@DST@@.habitaciones h ON h.id = cr.habitacion_id WHERE h.hotel_id = 3)
UNION ALL SELECT 'historial_remotos', (SELECT COUNT(*) FROM @@DST@@.historial_remotos hr JOIN @@DST@@.habitaciones h ON h.id = hr.habitacion_id WHERE h.hotel_id = 3)
UNION ALL SELECT 'mantenimientos', COUNT(*) FROM @@DST@@.mantenimientos_habitaciones WHERE hotel_id = 3
UNION ALL SELECT 'incrementos_tarifas', COUNT(*) FROM @@DST@@.incrementos_tarifas WHERE hotel_id = 3
UNION ALL SELECT 'inventario_categorias', COUNT(*) FROM @@DST@@.inventario_categorias WHERE hotel_id = 3
UNION ALL SELECT 'inventario_productos', COUNT(*) FROM @@DST@@.inventario_productos WHERE hotel_id = 3
UNION ALL SELECT 'inventario_config', COUNT(*) FROM @@DST@@.inventario_config_habitacion WHERE hotel_id = 3
UNION ALL SELECT 'movimientos_inventario', COUNT(*) FROM @@DST@@.movimientos_inventario WHERE hotel_id = 3
UNION ALL SELECT 'habitacion_imagenes', COUNT(*) FROM @@DST@@.habitacion_imagenes WHERE hotel_id = 3
UNION ALL SELECT 'logs_acceso_total_dst', (SELECT COUNT(*) FROM @@DST@@.logs_acceso);
