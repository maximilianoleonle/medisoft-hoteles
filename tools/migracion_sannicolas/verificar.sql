-- ============================================================================
-- Verificación de la migración San Nicolás → hotel 3. Corre DESPUÉS de migrar.sql
-- en la MISMA sesión de convenciones (-06:00). Cero FAIL = migración válida.
-- Los checks recomputan desde el origen con los mapas (no confían en el resumen).
-- ============================================================================
SET time_zone = '-06:00';
USE @@DST@@;

DROP TABLE IF EXISTS @@SRC@@._verificacion;
CREATE TABLE @@SRC@@._verificacion (orden INT AUTO_INCREMENT PRIMARY KEY, chk VARCHAR(80), resultado VARCHAR(400));

-- 01-05 HABITACIONES
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '01 habitaciones conteo',
  IF((SELECT COUNT(*) FROM @@DST@@.habitaciones WHERE hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.habitaciones),'PASS',
     CONCAT('FAIL dst=',(SELECT COUNT(*) FROM @@DST@@.habitaciones WHERE hotel_id=3)));
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '02 habitaciones numeros identicos',
  IF((SELECT SUM(CRC32(numero)) FROM @@DST@@.habitaciones WHERE hotel_id=3)=(SELECT SUM(CRC32(numero)) FROM @@SRC@@.habitaciones),'PASS','FAIL checksum numeros');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '03 tipos colapsados espejo',
  IF((SELECT COUNT(*) FROM (
      SELECT CASE l.tipo WHEN 'doble_jacuzzi' THEN 'doble' WHEN 'sencilla_jacuzzi' THEN 'sencilla' ELSE l.tipo END tl, COUNT(*) n
      FROM @@SRC@@.habitaciones l GROUP BY tl) a
      JOIN (SELECT tipo, COUNT(*) n FROM @@DST@@.habitaciones WHERE hotel_id=3 GROUP BY tipo) b ON b.tipo=a.tl AND b.n=a.n)=
     (SELECT COUNT(DISTINCT CASE tipo WHEN 'doble_jacuzzi' THEN 'doble' WHEN 'sencilla_jacuzzi' THEN 'sencilla' ELSE tipo END) FROM @@SRC@@.habitaciones),
     'PASS','FAIL distribucion de tipos no espeja');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '04 sin tipos fuera de catalogo',
  IF((SELECT COUNT(*) FROM @@DST@@.habitaciones WHERE hotel_id=3 AND tipo NOT IN ('sencilla','doble','triple','cuadruple'))=0,'PASS','FAIL tipos extranos');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '05 precios base espejo',
  IF((SELECT SUM(precio_base) FROM @@DST@@.habitaciones WHERE hotel_id=3)=(SELECT SUM(precio_base) FROM @@SRC@@.habitaciones),'PASS','FAIL suma precio_base');

-- 06 TIPOS DE HABITACION (catalogo)
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '06 tipos_habitacion catalogo',
  IF((SELECT COUNT(*) FROM @@DST@@.tipos_habitacion WHERE hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.tipos_habitacion)
     AND (SELECT SUM(CRC32(codigo)) FROM @@DST@@.tipos_habitacion WHERE hotel_id=3)=(SELECT SUM(CRC32(codigo)) FROM @@SRC@@.tipos_habitacion),'PASS','FAIL catalogo tipos');

-- 07-08 HUESPEDES Y VEHICULOS
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '07 huespedes conteo+nombres',
  IF((SELECT COUNT(*) FROM @@DST@@.huespedes WHERE hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.huespedes)
     AND (SELECT SUM(CRC32(nombre_completo)) FROM @@DST@@.huespedes WHERE hotel_id=3)=(SELECT SUM(CRC32(nombre_completo)) FROM @@SRC@@.huespedes),'PASS','FAIL huespedes');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '08 vehiculos (excluye huerfanos)',
  IF((SELECT COUNT(*) FROM @@DST@@.huesped_vehiculos WHERE hotel_id=3)=
     (SELECT COUNT(*) FROM @@SRC@@.huesped_vehiculos v JOIN @@SRC@@.huespedes h ON h.id=v.huesped_id),'PASS',
     CONCAT('FAIL dst=',(SELECT COUNT(*) FROM @@DST@@.huesped_vehiculos WHERE hotel_id=3)));

-- 09-12 RESERVACIONES
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '09 reservaciones por estado espejo',
  IF((SELECT COUNT(*) FROM (SELECT estado, COUNT(*) n FROM @@SRC@@.reservaciones GROUP BY estado) a
      JOIN (SELECT estado, COUNT(*) n FROM @@DST@@.reservaciones WHERE hotel_id=3 GROUP BY estado) b ON b.estado=a.estado AND b.n=a.n)
     =(SELECT COUNT(DISTINCT estado) FROM @@SRC@@.reservaciones),'PASS','FAIL estados');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '10 dinero reservaciones espejo',
  IF((SELECT CONCAT(SUM(precio_total),'|',COALESCE(SUM(monto_recibido),0)) FROM @@DST@@.reservaciones WHERE hotel_id=3)=
     (SELECT CONCAT(SUM(precio_total),'|',COALESCE(SUM(monto_recibido),0)) FROM @@SRC@@.reservaciones),'PASS','FAIL sumas dinero');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '11 nota de folio anterior en todas',
  IF((SELECT COUNT(*) FROM @@DST@@.reservaciones WHERE hotel_id=3 AND notas LIKE '%Folio del sistema anterior: #%')=
     (SELECT COUNT(*) FROM @@SRC@@.reservaciones),'PASS','FAIL notas de folio');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '12 fechas espejo (timezone integro)',
  IF((SELECT CONCAT(MIN(fecha_entrada),'|',MAX(fecha_salida),'|',MIN(created_at),'|',MAX(created_at)) FROM @@DST@@.reservaciones WHERE hotel_id=3)=
     (SELECT CONCAT(MIN(fecha_entrada),'|',MAX(fecha_salida),'|',MIN(created_at),'|',MAX(created_at)) FROM @@SRC@@.reservaciones),'PASS','FAIL corrimiento de fechas');

-- 13-15 DETALLE DE RESERVACION
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '13 reservacion_habitaciones',
  IF((SELECT COUNT(*) FROM @@DST@@.reservacion_habitaciones WHERE hotel_id=3)=
     (SELECT COUNT(*) FROM @@SRC@@.reservacion_habitaciones rh JOIN @@SRC@@.reservaciones r ON r.id=rh.reservacion_id JOIN @@SRC@@.habitaciones hb ON hb.id=rh.habitacion_id)
     AND (SELECT SUM(precio) FROM @@DST@@.reservacion_habitaciones WHERE hotel_id=3)=
     (SELECT SUM(rh.precio) FROM @@SRC@@.reservacion_habitaciones rh JOIN @@SRC@@.reservaciones r ON r.id=rh.reservacion_id JOIN @@SRC@@.habitaciones hb ON hb.id=rh.habitacion_id),
     'PASS','FAIL noches-cuarto');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '14 pagos (excluye 10 huerfanos)',
  IF((SELECT CONCAT(COUNT(*),'|',SUM(monto)) FROM @@DST@@.reservacion_pagos WHERE hotel_id=3)=
     (SELECT CONCAT(COUNT(*),'|',SUM(p.monto)) FROM @@SRC@@.reservacion_pagos p JOIN @@SRC@@.reservaciones r ON r.id=p.reservacion_id),'PASS','FAIL pagos');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '15 notas de reservacion',
  IF((SELECT COUNT(*) FROM @@DST@@.reservacion_notas WHERE hotel_id=3)=
     (SELECT COUNT(*) FROM @@SRC@@.reservacion_notas n JOIN @@SRC@@.reservaciones r ON r.id=n.reservacion_id),'PASS','FAIL notas');

-- 16-19 CAJA: CORTES
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '16 cortes conteo y cero abiertos',
  IF((SELECT COUNT(*) FROM @@DST@@.cortes_caja WHERE hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.cortes_caja)
     AND (SELECT COUNT(*) FROM @@DST@@.cortes_caja WHERE hotel_id=3 AND estado<>'cerrado')=0
     AND (SELECT COUNT(*) FROM @@DST@@.cortes_caja WHERE hotel_id=3 AND caja_id<>3)=0,'PASS','FAIL cortes');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '17 cortes cerrados verbatim',
  IF((SELECT COUNT(*) FROM @@SRC@@.cortes_caja c JOIN @@SRC@@._map_cortes m ON m.old_id=c.id
      JOIN @@DST@@.cortes_caja d ON d.id=m.new_id
      WHERE c.estado='cerrado' AND NOT (
        d.total_ingresos_efectivo<=>c.total_ingresos_efectivo AND d.total_ingresos_tarjeta<=>c.total_ingresos_tarjeta
        AND d.total_ingresos_transferencia<=>c.total_ingresos_transferencia AND d.total_gastos_efectivo<=>c.total_gastos_efectivo
        AND d.total_gastos_tarjeta<=>c.total_gastos_tarjeta AND d.total_gastos_transferencia<=>c.total_gastos_transferencia
        AND d.efectivo_esperado<=>c.efectivo_esperado AND d.efectivo_contado<=>c.efectivo_contado
        AND d.diferencia<=>c.diferencia AND d.monto_inicial<=>c.monto_inicial
        AND d.fecha_apertura<=>c.fecha_apertura AND d.fecha_cierre<=>c.fecha_cierre))=0,'PASS','FAIL cortes alterados');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '18 ex-abierto cerrado y cuadrado',
  IF((SELECT COUNT(*) FROM @@SRC@@.cortes_caja c JOIN @@SRC@@._map_cortes m ON m.old_id=c.id
      JOIN @@DST@@.cortes_caja d ON d.id=m.new_id
      WHERE c.estado='abierto' AND d.estado='cerrado' AND d.diferencia=0
        AND d.total_ingresos_efectivo=(SELECT COALESCE(SUM(monto),0) FROM @@DST@@.movimientos_caja x WHERE x.corte_id=d.id AND x.tipo='ingreso' AND x.metodo_pago='efectivo')
        AND d.total_gastos_efectivo=(SELECT COALESCE(SUM(monto),0) FROM @@DST@@.movimientos_caja x WHERE x.corte_id=d.id AND x.tipo='gasto' AND x.metodo_pago='efectivo')
        AND d.efectivo_contado=d.efectivo_esperado
        AND d.fecha_cierre=(SELECT MAX(x.created_at) FROM @@DST@@.movimientos_caja x WHERE x.corte_id=d.id)
        AND d.observaciones LIKE '%[Migracion]%')=
     (SELECT COUNT(*) FROM @@SRC@@.cortes_caja WHERE estado='abierto'),'PASS','FAIL cierre del corte abierto');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '19 denominaciones espejo',
  IF((SELECT CONCAT(COUNT(*),'|',SUM(d.denominacion*d.cantidad)) FROM @@DST@@.denominaciones_efectivo d JOIN @@DST@@.cortes_caja c ON c.id=d.corte_id WHERE c.hotel_id=3)=
     (SELECT CONCAT(COUNT(*),'|',SUM(denominacion*cantidad)) FROM @@SRC@@.denominaciones_efectivo),'PASS','FAIL denominaciones');

-- 20-22 CAJA: MOVIMIENTOS
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '20 movimientos por tipo/metodo espejo',
  IF((SELECT COUNT(*) FROM (SELECT tipo, metodo_pago, COUNT(*) n, SUM(monto) s FROM @@SRC@@.movimientos_caja GROUP BY tipo, metodo_pago) a
      JOIN (SELECT tipo, metodo_pago, COUNT(*) n, SUM(monto) s FROM @@DST@@.movimientos_caja WHERE hotel_id=3 GROUP BY tipo, metodo_pago) b
        ON b.tipo=a.tipo AND b.metodo_pago=a.metodo_pago AND b.n=a.n AND b.s=a.s)=
     (SELECT COUNT(*) FROM (SELECT DISTINCT tipo, metodo_pago FROM @@SRC@@.movimientos_caja) x),'PASS','FAIL sumas por tipo/metodo');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '21 huerfanos legado anulados (18/18/2)',
  IF((SELECT CONCAT(SUM(corte_id IS NULL),'|',SUM(reservacion_id IS NULL AND referencia_ok=1),'|',SUM(cat_null)) FROM
      (SELECT d.corte_id, d.reservacion_id, 1 referencia_ok, (d.categoria_id IS NULL) cat_null
       FROM @@DST@@.movimientos_caja d WHERE d.hotel_id=3) x)=
     (SELECT CONCAT(
        SUM(c.id IS NULL),'|',
        SUM(m.reservacion_id IS NULL OR r.id IS NULL),'|',
        SUM(m.categoria_id IS NULL OR cm.id IS NULL))
      FROM @@SRC@@.movimientos_caja m
      LEFT JOIN @@SRC@@.cortes_caja c ON c.id=m.corte_id
      LEFT JOIN @@SRC@@.reservaciones r ON r.id=m.reservacion_id
      LEFT JOIN @@SRC@@.categorias_movimientos cm ON cm.id=m.categoria_id),'PASS','FAIL anulacion de huerfanos');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '22 tenancy cruzada movimientos',
  IF((SELECT COUNT(*) FROM @@DST@@.movimientos_caja d
      LEFT JOIN @@DST@@.cortes_caja c ON c.id=d.corte_id
      LEFT JOIN @@DST@@.reservaciones r ON r.id=d.reservacion_id
      LEFT JOIN @@DST@@.categorias_movimientos cm ON cm.id=d.categoria_id
      WHERE d.hotel_id=3 AND ((d.corte_id IS NOT NULL AND COALESCE(c.hotel_id,0)<>3)
        OR (d.reservacion_id IS NOT NULL AND COALESCE(r.hotel_id,0)<>3)
        OR (d.categoria_id IS NOT NULL AND COALESCE(cm.hotel_id,0)<>3)))=0,'PASS','FAIL FK cruza hoteles');

-- 23-25 USUARIOS Y CATEGORIAS
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '23 usuarios creados con su password',
  IF((SELECT COUNT(*) FROM @@SRC@@._map_usuarios WHERE accion='creado')=
     (SELECT COUNT(*) FROM @@SRC@@.usuarios l JOIN @@SRC@@._map_usuarios m ON m.old_id=l.id AND m.accion='creado'
      JOIN @@DST@@.usuarios d ON d.id=m.new_id AND d.password=l.password AND d.nombre_usuario=l.nombre_usuario AND d.activo=l.activo),
     'PASS','FAIL usuarios creados');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '24 membresias hotel 3 con rol sembrado',
  IF((SELECT COUNT(*) FROM @@DST@@.hotel_usuarios hu JOIN @@DST@@.roles ro ON ro.id=hu.role_id
      WHERE hu.hotel_id=3 AND ro.hotel_id=3 AND ro.clave=hu.rol)=
     (SELECT COUNT(*) FROM @@SRC@@.usuarios),'PASS',
     CONCAT('FAIL membresias=',(SELECT COUNT(*) FROM @@DST@@.hotel_usuarios WHERE hotel_id=3)));
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '25 categorias normalizadas',
  IF((SELECT COUNT(*) FROM @@DST@@.categorias_movimientos WHERE hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@._cat_grupos)
     AND (SELECT COUNT(*) FROM @@DST@@.categorias_movimientos WHERE hotel_id=3 AND (tipo IS NULL OR tipo NOT IN ('ingreso','gasto')))=0
     AND (SELECT COUNT(DISTINCT nombre) FROM @@DST@@.categorias_movimientos WHERE hotel_id=3)=(SELECT COUNT(DISTINCT nombre) FROM @@SRC@@.categorias_movimientos),
     'PASS','FAIL categorias');

-- 26-27 LLAVES Y REMOTOS
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '26 llaves control+historial',
  IF((SELECT COUNT(*) FROM @@DST@@.control_llaves cl JOIN @@DST@@.habitaciones h ON h.id=cl.habitacion_id WHERE h.hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.control_llaves)
     AND (SELECT COUNT(*) FROM @@DST@@.historial_llaves hl JOIN @@DST@@.habitaciones h ON h.id=hl.habitacion_id WHERE h.hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.historial_llaves)
     AND (SELECT COUNT(*) FROM @@DST@@.historial_llaves hl JOIN @@DST@@.habitaciones h ON h.id=hl.habitacion_id WHERE h.hotel_id=3 AND hl.reservacion_id IS NULL)=
         (SELECT COUNT(*) FROM @@SRC@@.historial_llaves l LEFT JOIN @@SRC@@.reservaciones r ON r.id=l.reservacion_id WHERE l.reservacion_id IS NULL OR r.id IS NULL),
     'PASS','FAIL llaves');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '27 remotos + backups',
  IF((SELECT COUNT(*) FROM @@DST@@.control_remotos cr JOIN @@DST@@.habitaciones h ON h.id=cr.habitacion_id WHERE h.hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.control_remotos)
     AND (SELECT COUNT(*) FROM @@DST@@.historial_remotos hr JOIN @@DST@@.habitaciones h ON h.id=hr.habitacion_id WHERE h.hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.historial_remotos)
     AND (SELECT COUNT(*) FROM @@DST@@.control_remotos_backup cb JOIN @@DST@@.habitaciones h ON h.id=cb.habitacion_id WHERE h.hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.control_remotos_backup)
     AND (SELECT COUNT(*) FROM @@DST@@.historial_remotos_backup hb JOIN @@DST@@.habitaciones h ON h.id=hb.habitacion_id WHERE h.hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.historial_remotos_backup),
     'PASS','FAIL remotos');

-- 28-29 MANTENIMIENTOS E INCREMENTOS
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '28 mantenimientos con usuario resuelto',
  IF((SELECT COUNT(*) FROM @@DST@@.mantenimientos_habitaciones WHERE hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.mantenimientos_habitaciones)
     AND (SELECT COUNT(*) FROM @@DST@@.mantenimientos_habitaciones mm JOIN @@DST@@.usuarios u ON u.id=mm.usuario_registro_id WHERE mm.hotel_id=3)=
         (SELECT COUNT(*) FROM @@SRC@@.mantenimientos_habitaciones),'PASS','FAIL mantenimientos');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '29 incrementos con JSON remapeado',
  IF((SELECT COUNT(*) FROM @@DST@@.incrementos_tarifas WHERE hotel_id=3 AND clase='incremento')=(SELECT COUNT(*) FROM @@SRC@@.incrementos_tarifas)
     AND (SELECT COALESCE(SUM(JSON_LENGTH(habitaciones)),0) FROM @@DST@@.incrementos_tarifas WHERE hotel_id=3)=
         (SELECT COALESCE(SUM(JSON_LENGTH(habitaciones)),0) FROM @@SRC@@.incrementos_tarifas)
     AND (SELECT COUNT(*) FROM @@DST@@.incrementos_tarifas i,
          JSON_TABLE(i.habitaciones, '$[*]' COLUMNS (hab_id INT PATH '$')) jt
          LEFT JOIN @@DST@@.habitaciones h ON h.id=jt.hab_id AND h.hotel_id=3
          WHERE i.hotel_id=3 AND i.habitaciones IS NOT NULL AND h.id IS NULL)=0,
     'PASS','FAIL incrementos/JSON');

-- 30-31 INVENTARIO
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '30 inventario v2 espejo',
  IF(CONCAT(
       (SELECT COUNT(*) FROM @@DST@@.inventario_categorias WHERE hotel_id=3),'|',
       (SELECT COUNT(*) FROM @@DST@@.inventario_productos WHERE hotel_id=3),'|',
       (SELECT SUM(stock_actual) FROM @@DST@@.inventario_productos WHERE hotel_id=3),'|',
       (SELECT COUNT(*) FROM @@DST@@.movimientos_inventario WHERE hotel_id=3))=
     CONCAT(
       (SELECT COUNT(*) FROM @@SRC@@.inventario_categorias),'|',
       (SELECT COUNT(*) FROM @@SRC@@.inventario_productos),'|',
       (SELECT SUM(stock_actual) FROM @@SRC@@.inventario_productos),'|',
       (SELECT COUNT(*) FROM @@SRC@@.movimientos_inventario)),'PASS','FAIL inventario');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '31 config inventario solo tipos base',
  IF((SELECT COUNT(*) FROM @@DST@@.inventario_config_habitacion WHERE hotel_id=3)=
     (SELECT COUNT(*) FROM @@SRC@@.inventario_config_habitacion WHERE tipo_habitacion NOT IN ('doble_jacuzzi','sencilla_jacuzzi'))
     AND (SELECT COUNT(*) FROM @@DST@@.inventario_config_habitacion WHERE hotel_id=3 AND tipo_habitacion LIKE '%jacuzzi%')=0,
     'PASS','FAIL config inventario');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '31b INFO config jacuzzi excluida',
  CONCAT('INFO ', (SELECT COUNT(*) FROM @@SRC@@.inventario_config_habitacion WHERE tipo_habitacion IN ('doble_jacuzzi','sencilla_jacuzzi')),
         ' filas jacuzzi fuera; productos SOLO-jacuzzi sin descuento auto: ',
         COALESCE((SELECT GROUP_CONCAT(DISTINCT p.nombre)
          FROM @@SRC@@.inventario_config_habitacion cj
          JOIN @@SRC@@.inventario_productos p ON p.id=cj.producto_id
          WHERE cj.tipo_habitacion IN ('doble_jacuzzi','sencilla_jacuzzi')
            AND NOT EXISTS (SELECT 1 FROM @@SRC@@.inventario_config_habitacion cb
                            WHERE cb.producto_id=cj.producto_id
                              AND cb.tipo_habitacion=CASE cj.tipo_habitacion WHEN 'doble_jacuzzi' THEN 'doble' ELSE 'sencilla' END)),'(ninguno)'));

-- 32-34 IMAGENES, LOGS, USUARIOS TOTALES
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '32 imagenes de habitacion',
  IF((SELECT COUNT(*) FROM @@DST@@.habitacion_imagenes WHERE hotel_id=3)=(SELECT COUNT(*) FROM @@SRC@@.habitacion_imagenes)
     AND (SELECT SUM(CRC32(url)) FROM @@DST@@.habitacion_imagenes WHERE hotel_id=3)=(SELECT SUM(CRC32(url)) FROM @@SRC@@.habitacion_imagenes),'PASS','FAIL imagenes');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '33 logs de acceso = pre + legado',
  IF((SELECT COUNT(*) FROM @@DST@@.logs_acceso)=
     (SELECT filas_otros FROM @@SRC@@._tenancy_pre WHERE tabla='logs_acceso_total_pre')+(SELECT COUNT(*) FROM @@SRC@@.logs_acceso),'PASS','FAIL logs');
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '34 usuarios totales = pre + creados',
  IF((SELECT COUNT(*) FROM @@DST@@.usuarios)=
     (SELECT filas_otros FROM @@SRC@@._tenancy_pre WHERE tabla='usuarios_total_pre')+(SELECT COUNT(*) FROM @@SRC@@._map_usuarios WHERE accion='creado'),'PASS','FAIL usuarios totales');

-- 35 TENANCY GLOBAL: nada de otros hoteles cambio de conteo
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '35 tenancy: otros hoteles intactos',
  IF((SELECT COUNT(*) FROM @@SRC@@._tenancy_pre t WHERE t.tabla NOT IN ('usuarios_total_pre','logs_acceso_total_pre') AND t.filas_otros <> CASE t.tabla
      WHEN 'habitaciones' THEN (SELECT COUNT(*) FROM @@DST@@.habitaciones WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'huespedes' THEN (SELECT COUNT(*) FROM @@DST@@.huespedes WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'reservaciones' THEN (SELECT COUNT(*) FROM @@DST@@.reservaciones WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'reservacion_habitaciones' THEN (SELECT COUNT(*) FROM @@DST@@.reservacion_habitaciones WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'reservacion_pagos' THEN (SELECT COUNT(*) FROM @@DST@@.reservacion_pagos WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'reservacion_notas' THEN (SELECT COUNT(*) FROM @@DST@@.reservacion_notas WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'huesped_vehiculos' THEN (SELECT COUNT(*) FROM @@DST@@.huesped_vehiculos WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'movimientos_caja' THEN (SELECT COUNT(*) FROM @@DST@@.movimientos_caja WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'cortes_caja' THEN (SELECT COUNT(*) FROM @@DST@@.cortes_caja WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'categorias_movimientos' THEN (SELECT COUNT(*) FROM @@DST@@.categorias_movimientos WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'incrementos_tarifas' THEN (SELECT COUNT(*) FROM @@DST@@.incrementos_tarifas WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'mantenimientos_habitaciones' THEN (SELECT COUNT(*) FROM @@DST@@.mantenimientos_habitaciones WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'tipos_habitacion' THEN (SELECT COUNT(*) FROM @@DST@@.tipos_habitacion WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'habitacion_imagenes' THEN (SELECT COUNT(*) FROM @@DST@@.habitacion_imagenes WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'inventario_categorias' THEN (SELECT COUNT(*) FROM @@DST@@.inventario_categorias WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'inventario_productos' THEN (SELECT COUNT(*) FROM @@DST@@.inventario_productos WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'inventario_config_habitacion' THEN (SELECT COUNT(*) FROM @@DST@@.inventario_config_habitacion WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'movimientos_inventario' THEN (SELECT COUNT(*) FROM @@DST@@.movimientos_inventario WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'hotel_usuarios' THEN (SELECT COUNT(*) FROM @@DST@@.hotel_usuarios WHERE COALESCE(hotel_id,0)<>3)
      WHEN 'control_llaves' THEN (SELECT COUNT(*) FROM @@DST@@.control_llaves cl JOIN @@DST@@.habitaciones h ON h.id=cl.habitacion_id WHERE COALESCE(h.hotel_id,0)<>3)
      WHEN 'historial_llaves' THEN (SELECT COUNT(*) FROM @@DST@@.historial_llaves hl JOIN @@DST@@.habitaciones h ON h.id=hl.habitacion_id WHERE COALESCE(h.hotel_id,0)<>3)
      WHEN 'control_remotos' THEN (SELECT COUNT(*) FROM @@DST@@.control_remotos cr JOIN @@DST@@.habitaciones h ON h.id=cr.habitacion_id WHERE COALESCE(h.hotel_id,0)<>3)
      WHEN 'historial_remotos' THEN (SELECT COUNT(*) FROM @@DST@@.historial_remotos hr JOIN @@DST@@.habitaciones h ON h.id=hr.habitacion_id WHERE COALESCE(h.hotel_id,0)<>3)
      WHEN 'denominaciones_efectivo' THEN (SELECT COUNT(*) FROM @@DST@@.denominaciones_efectivo d JOIN @@DST@@.cortes_caja c ON c.id=d.corte_id WHERE COALESCE(c.hotel_id,0)<>3)
      ELSE -1 END)=0,'PASS','FAIL conteos de otros hoteles cambiaron');

-- 36 INTEGRIDAD FK GLOBAL DEL HOTEL 3
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '36 cero FKs rotas en hotel 3',
  IF((SELECT
      (SELECT COUNT(*) FROM @@DST@@.reservaciones r LEFT JOIN @@DST@@.huespedes h ON h.id=r.huesped_id AND h.hotel_id=3 WHERE r.hotel_id=3 AND h.id IS NULL)
     +(SELECT COUNT(*) FROM @@DST@@.reservacion_habitaciones rh LEFT JOIN @@DST@@.reservaciones r ON r.id=rh.reservacion_id AND r.hotel_id=3 LEFT JOIN @@DST@@.habitaciones hb ON hb.id=rh.habitacion_id AND hb.hotel_id=3 WHERE rh.hotel_id=3 AND (r.id IS NULL OR hb.id IS NULL))
     +(SELECT COUNT(*) FROM @@DST@@.reservacion_pagos p LEFT JOIN @@DST@@.reservaciones r ON r.id=p.reservacion_id AND r.hotel_id=3 WHERE p.hotel_id=3 AND r.id IS NULL)
     +(SELECT COUNT(*) FROM @@DST@@.reservacion_notas n LEFT JOIN @@DST@@.reservaciones r ON r.id=n.reservacion_id AND r.hotel_id=3 WHERE n.hotel_id=3 AND r.id IS NULL)
     +(SELECT COUNT(*) FROM @@DST@@.huesped_vehiculos v LEFT JOIN @@DST@@.huespedes h ON h.id=v.huesped_id AND h.hotel_id=3 WHERE v.hotel_id=3 AND h.id IS NULL)
     +(SELECT COUNT(*) FROM @@DST@@.movimientos_caja m LEFT JOIN @@DST@@.usuarios u ON u.id=m.usuario_id WHERE m.hotel_id=3 AND u.id IS NULL)
     +(SELECT COUNT(*) FROM @@DST@@.cortes_caja c LEFT JOIN @@DST@@.usuarios u ON u.id=c.usuario_apertura_id WHERE c.hotel_id=3 AND u.id IS NULL)
     +(SELECT COUNT(*) FROM @@DST@@.movimientos_inventario mi LEFT JOIN @@DST@@.inventario_productos p ON p.id=mi.producto_id AND p.hotel_id=3 WHERE mi.hotel_id=3 AND p.id IS NULL)
     +(SELECT COUNT(*) FROM @@DST@@.inventario_config_habitacion cf LEFT JOIN @@DST@@.inventario_productos p ON p.id=cf.producto_id AND p.hotel_id=3 WHERE cf.hotel_id=3 AND p.id IS NULL)
     )=0,'PASS','FAIL hay FKs rotas o cruzadas');

-- 37 INFO herencia del legado (para el runbook, no es falla de migracion)
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '37 INFO herencia arqueo',
  CONCAT('INFO mov sin corte en h3: ',
    (SELECT COUNT(*) FROM @@DST@@.movimientos_caja WHERE hotel_id=3 AND corte_id IS NULL),
    ' | cortes h3 con totales<>suma de movs (herencia legado): ',
    (SELECT COUNT(*) FROM @@DST@@.cortes_caja c WHERE c.hotel_id=3 AND (
      c.total_ingresos_efectivo <> (SELECT COALESCE(SUM(monto),0) FROM @@DST@@.movimientos_caja x WHERE x.corte_id=c.id AND x.tipo='ingreso' AND x.metodo_pago='efectivo')
      OR c.total_gastos_efectivo <> (SELECT COALESCE(SUM(monto),0) FROM @@DST@@.movimientos_caja x WHERE x.corte_id=c.id AND x.tipo='gasto' AND x.metodo_pago='efectivo'))));

-- 38 INFO confirmadas con header vs detalle descuadrado (herencia legado, guard #1867)
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '38 INFO confirmadas descuadradas',
  (SELECT CONCAT('INFO ', COUNT(*), ' confirmadas con detalle<>precio_total (dif $',
          COALESCE(SUM(dif),0), ', herencia legado — NO correr aplicar de impacto de tarifas sobre ellas sin decidir fuente de verdad)')
   FROM (SELECT r.id, SUM(CASE WHEN rh.es_cortesia=1 THEN 0 ELSE rh.precio END) - r.precio_total AS dif
         FROM @@DST@@.reservaciones r JOIN @@DST@@.reservacion_habitaciones rh ON rh.reservacion_id = r.id
         WHERE r.hotel_id=3 AND r.estado='confirmada'
         GROUP BY r.id, r.precio_total HAVING ABS(dif) > 0.01) x);

-- 39 imagenes: una sola portada por cuarto
INSERT INTO @@SRC@@._verificacion (chk, resultado) SELECT '39 una sola imagen principal por cuarto',
  IF((SELECT COALESCE(MAX(n),0) FROM (SELECT habitacion_id, SUM(es_principal) n FROM @@DST@@.habitacion_imagenes WHERE hotel_id=3 GROUP BY habitacion_id) x) <= 1,
     'PASS','FAIL portadas duplicadas');

-- RESULTADOS
SELECT chk, resultado FROM @@SRC@@._verificacion ORDER BY orden;
SELECT CONCAT('TOTAL: ', COUNT(*), ' checks | FAIL: ', SUM(resultado LIKE 'FAIL%'), ' | INFO: ', SUM(resultado LIKE 'INFO%')) AS resumen
FROM @@SRC@@._verificacion;
