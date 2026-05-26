# Fase Inventario 1-E-B - Ejecucion local de hotel_id en movimientos_inventario

## Objetivo

Ejecutar localmente la migracion `20260526_007_add_hotel_id_movimientos_inventario.sql` para agregar `hotel_id` a `movimientos_inventario`, derivando el hotel desde `inventario_productos.hotel_id` y validando consistencia contra `habitaciones.hotel_id` cuando existe `habitacion_id`.

## Fecha y base afectada

- Fecha de ejecucion: 2026-05-26.
- Base afectada: `medisoft_hoteles_import`.
- Entorno: local.

## Migracion ejecutada

- `migrations/20260526_007_add_hotel_id_movimientos_inventario.sql`

La migracion fue ejecutada con cliente MySQL porque el archivo usa `DELIMITER`.

## Backups

- Backup pre-migracion: `backups/backup_pre_inventario_1E_movimientos_hotel_id.sql`.
- Backup post-migracion: no se creo en esta subfase.

## Resultado de datos

`movimientos_inventario.hotel_id` existe como `INT NULL`.

Conteos verificados:

- `movimientos_inventario` total: 936.
- Con `hotel_id` de Los Cedros: 936.
- Con `hotel_id NULL`: 0.

## Indice y foreign key

Indice creado:

- `idx_movimientos_inventario_hotel_id`

Foreign key creada:

- `fk_movimientos_inventario_hotel -> hoteles(id)`

## Registro de migracion

La tabla `migrations` registro:

- `20260526_007_add_hotel_id_movimientos_inventario.sql`
- Estado: `ejecutada`.

## Tablas no tocadas

No se agrego `hotel_id` ni se modificaron estas tablas en esta ejecucion:

- `reservaciones`
- `inventario_productos`
- `inventario_categorias`
- `inventario_config_habitacion`
- `inventario_movimientos`
- `alertas_inventario`
- `productos`
- `categorias_producto`
- `cajas`
- `movimientos_caja`
- `sync_queue`
- `push_subscriptions`

Tampoco se modifico codigo funcional, `InventarioService`, check-in/check-out, Caja ni PWA/offline.

## Pruebas HTTP

Resultados sin sesion activa:

- `/login`: 200.
- `/`: 200.
- `/dashboard`: 303 hacia `/login`.
- `/habitaciones`: 303 hacia `/login`.
- `/inventario`: 303 hacia `/login`.

Las redirecciones a login son esperadas para rutas protegidas sin sesion.

## Herramientas SaaS antes de actualizarlas

Despues de ejecutar la migracion, `verificar_estado.php` y `preflight_hotel_id.php` fallaban de forma esperada porque todavia no reconocian `movimientos_inventario.hotel_id` como estado valido.

## Riesgos pendientes

- Nuevos movimientos podrian quedar con `hotel_id NULL` hasta integrar codigo funcional de movimientos.
- `InventarioService` sigue fuera de alcance y todavia no debe tocarse sin una fase aprobada.
- Check-in/check-out siguen conectados a Inventario y Reservaciones.
- Reservaciones todavia no tiene `hotel_id`.
- Cancelaciones tienen una inconsistencia documentada entre `inventario_movimientos` y `movimientos_inventario`.
- Caja y PWA/offline siguen fuera de alcance.

## Siguiente fase recomendada

Inventario 1-E-B.1: actualizar herramientas de verificacion para aceptar el estado post Inventario 1-E-B, sin tocar codigo funcional.
