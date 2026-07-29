-- ============================================================================
-- Siembra del CLON LOCAL para el ensayo (medisoft_prod_clone).
-- Corre DESPUÉS de cargar la estructura --no-data de prod y el dump de tablas
-- de configuración (hoteles, roles, cajas, modulos, planes, plan_modulos,
-- hotel_modulos, saas_admins). Reproduce lo que el ensayo necesita de prod SIN
-- copiar datos sensibles: usuarios reales con hash PLACEHOLDER (nunca el real)
-- y UNA fila centinela por tabla destino que (a) fija MAX(id) al valor real de
-- prod al 2026-07-29 —así los ids del ensayo son los que producirá el día D— y
-- (b) sirve de testigo de tenancy: si la migración la toca, algo está mal.
-- ============================================================================
SET time_zone = '-06:00';
USE medisoft_prod_clone;

-- Usuarios reales de prod (11) con hash placeholder (no autenticable a propósito)
INSERT INTO usuarios (id, nombre_usuario, password, nombre_completo, email, telefono, rol, activo) VALUES
(1,'maximiliano','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','Maximiliano Leon','maximiliano.leonle@gmail.com',NULL,'gerente',1),
(2,'Leon','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','Leon',NULL,NULL,'gerente',1),
(3,'rafael','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','Rafael León Sánchez',NULL,NULL,'gerente',1),
(4,'admin','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','Administrador Principal',NULL,NULL,'gerente',1),
(5,'LETICIA','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','leticia zarate salinas',NULL,'(954) 145-7705','recepcionista',1),
(6,'JOSE','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','José de Jesús Cipriano Medina','jciprianomedina7@gmail.com','(951) 499-1002','recepcionista',1),
(7,'Adriana','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','Adriana Morales Gómez',NULL,NULL,'recepcionista',1),
(8,'Monica','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','Mónica López',NULL,NULL,'gerente',1),
(9,'Wilberto','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','WILBERTO LIBOORIO MRELGAR',NULL,'(951) 228-2190','recepcionista',1),
(10,'EVERARDO','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','JOSÉ EVERARDO ZORRILA ARAGON',NULL,'(733) 108-9060','recepcionista',1),
(11,'dafne','$2y$10$PLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLDERPLACEHOLD','Dafne',NULL,NULL,'administrador',1);

-- Membresías reales (13; el hotel 3 NO tiene ninguna: así está prod)
INSERT INTO hotel_usuarios (id, hotel_id, usuario_id, rol, role_id, activo) VALUES
(1,1,1,'gerente',3,1),(2,1,2,'gerente',3,1),(5,2,1,'recepcionista',11,1),(3,2,3,'gerente',NULL,1),
(4,2,4,'gerente',9,1),(6,2,5,'recepcionista',11,1),(7,2,6,'recepcionista',11,1),(8,2,7,'recepcionista',11,1),
(9,2,8,'gerente',9,1),(10,2,9,'recepcionista',11,1),(11,2,10,'recepcionista',11,1),
(12,4,1,'administrador',NULL,1),(13,4,11,'administrador',NULL,1);

-- Única clave de configuración que el hotel 3 tiene en prod
INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo) VALUES (3,'notificaciones.pwa_push_activo','0','string');

-- ---------------------------------------------------------------------------
-- CENTINELAS (hotel 2, intocables por la migración). Su id = MAX(id) de prod.
-- ---------------------------------------------------------------------------
INSERT INTO huespedes (id, hotel_id, nombre_completo, created_at) VALUES (1634, 2, 'SENTINEL H2 — no tocar', NOW());
INSERT INTO habitaciones (id, hotel_id, numero, tipo, piso, precio_base, estado, activa) VALUES (74, 2, 'S74', 'doble', 1, 1.00, 'disponible', 1);
INSERT INTO reservaciones (id, hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, notas) VALUES (2058, 2, 1634, '2026-01-01', '2026-01-02', 1.00, 'checked_out', 'SENTINEL H2');
INSERT INTO reservacion_habitaciones (id, hotel_id, reservacion_id, habitacion_id, precio) VALUES (3967, 2, 2058, 74, 1.00);
INSERT INTO reservacion_pagos (id, hotel_id, reservacion_id, metodo_pago, monto) VALUES (1949, 2, 2058, 'efectivo', 1.00);
INSERT INTO reservacion_notas (id, hotel_id, reservacion_id, usuario_id, nota) VALUES (2354, 2, 2058, 1, 'SENTINEL H2');
INSERT INTO huesped_vehiculos (id, hotel_id, huesped_id, marca, placas, activo) VALUES (1328, 2, 1634, 'SENTINEL', 'S-000', 1);
INSERT INTO categorias_movimientos (id, hotel_id, nombre, tipo, activa) VALUES (14, 2, 'SENTINEL H2', 'ingreso', 1);
INSERT INTO cortes_caja (id, hotel_id, caja_id, fecha_apertura, fecha_cierre, monto_inicial, estado, usuario_apertura_id, usuario_cierre_id) VALUES (400, 2, 2, '2026-01-01 08:00:00', '2026-01-01 20:00:00', 0, 'cerrado', 1, 1);
INSERT INTO denominaciones_efectivo (id, corte_id, denominacion, cantidad) VALUES (1201, 400, 500.00, 0);
INSERT INTO movimientos_caja (id, hotel_id, tipo, categoria, descripcion, monto, metodo_pago, usuario_id, corte_id) VALUES (2268, 2, 'ingreso', 'SENTINEL H2', 'centinela', 1.00, 'efectivo', 1, 400);
INSERT INTO control_llaves (id, habitacion_id, estado) VALUES (74, 74, 'disponible');
INSERT INTO historial_llaves (id, habitacion_id, tipo_movimiento, fecha_hora, usuario_id) VALUES (6748, 74, 'entrega', '2026-01-01 12:00:00', 1);
INSERT INTO control_remotos (id, habitacion_id, tiene_remoto) VALUES (49, 74, 0);
INSERT INTO mantenimientos_habitaciones (id, hotel_id, habitacion_id, tipo_mantenimiento, motivo, fecha_inicio, estado, usuario_registro_id) VALUES (1, 2, 74, 'preventivo', 'SENTINEL H2', '2026-01-01 09:00:00', 'completado', 1);
INSERT INTO incrementos_tarifas (id, hotel_id, nombre, tipo_incremento, clase, valor_incremento, alcance, fecha_inicio, activo, usuario_id) VALUES (3, 2, 'SENTINEL H2', 'porcentaje', 'incremento', 1.00, 'global', '2026-01-01', 0, 1);
INSERT INTO habitacion_imagenes (id, hotel_id, habitacion_id, url) VALUES (2, 2, 74, 'uploads/sentinel.jpg');
INSERT INTO logs_acceso (id, tipo, usuario_id, exitoso) VALUES (1625, 'login', 1, 1);
INSERT INTO inventario_categorias (id, hotel_id, nombre, activo) VALUES (4, 2, 'SENTINEL H2', 1);
INSERT INTO inventario_productos (id, hotel_id, codigo, nombre, categoria_id) VALUES (41, 2, 'SENT-01', 'SENTINEL H2', 4);
INSERT INTO movimientos_inventario (id, hotel_id, producto_id, tipo_movimiento, cantidad, stock_anterior, stock_posterior, usuario_id) VALUES (1941, 2, 41, 'ENTRADA', 1.00, 0.00, 1.00, 1);
INSERT INTO inventario_config_habitacion (id, hotel_id, tipo_habitacion, producto_id, cantidad_descontar, activo) VALUES (50, 2, 'doble', 41, 1.00, 1);

-- historial_remotos / *_backup / tipos_habitacion están VACÍAS en prod (MAX 0): sin centinela.
SELECT 'SEED OK' AS resultado;
