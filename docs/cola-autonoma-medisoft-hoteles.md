# Cola autonoma - Medisoft Hoteles

## Ultimo mensaje real usado

Roadmap autonomo recibido para avanzar fase por fase desde Fase 2X completada, iniciando con checkpoint estable y commit antes de continuar con Fase 2Y.

## Fase actual

- Fase 3B: cuentas por pagar base read-only.
- Estado: siguiente subfase segura pendiente; no se han ejecutado migraciones de CxP.

## Fases completadas

- Fase 2V: recepcion minima real de compras.
- Fase 2W: recepcion real controlada de compra #2 de Maximiliano.
- Fase 2X: detalle read-only de compra recibida.
- Fase 2Y: reporte read-only de compras recibidas por proveedor/producto.
- Fase 2Z: guardas explicitas de recepcion e idempotencia basica.
- Fase 3A: ficha read-only de proveedor e historial de compras.

## Commits realizados

- `d1f1431` - `feat: add read-only received purchase detail`
- `32abb7b` - `feat: add read-only received purchases reports`
- `052fd7a` - `fix: harden minimal purchase receiving flow`
- Pendiente: hash del checkpoint Fase 3A se reporta al cerrar el commit actual.

## Pruebas ejecutadas

- `php -l` en controlador, servicio, vistas, rutas y tools modificados: sin errores de sintaxis.
- `git diff --check`: sin errores bloqueantes; solo warnings de normalizacion CRLF.
- `preflight_compras_minimas.php`: OK 48, WARNING 0, ERROR 0.
- `preflight_recepcion_compras.php`: OK 27, WARNING 0, ERROR 0.
- `health_check_fase_1a.php`: OK 194, WARNING 14, ERROR 0.
- `/api/sync`: validado por health checker como bloqueado con HTTP 423 y `sync_temporarily_disabled`.
- Fase 2Y `php -l`: controlador, servicio, rutas, vista `compras/reporte_recibidas.php`, preflights y health checker sin errores.
- Fase 2Y `preflight_compras_minimas.php`: OK 49, WARNING 0, ERROR 0.
- Fase 2Y `preflight_recepcion_compras.php`: OK 27, WARNING 0, ERROR 0.
- Fase 2Y `health_check_fase_1a.php`: OK 196, WARNING 14, ERROR 0.
- Fase 2Y smoke read-only de `CompraService::reporteRecibidas(4)`: 1 compra, 3 lineas, 2 productos, cantidad total `300.00`, total lineas `1900.00`.
- Fase 2Y HTTP sin sesion `GET /compras/reportes/recibidas`: 303 a `/login`, sin exponer datos.
- Fase 2Y HTTP sin sesion `POST /api/sync`: 303 a `/login`; health checker confirma bloqueo estatico 423 en codigo.
- Fase 2Z `php -l`: `CompraService.php`, `compras/ver.php`, `preflight_compras_minimas.php`, `preflight_recepcion_compras.php`, `health_check_fase_1a.php` sin errores.
- Fase 2Z `preflight_compras_minimas.php`: OK 50, WARNING 0, ERROR 0.
- Fase 2Z `preflight_recepcion_compras.php`: OK 27, WARNING 0, ERROR 0.
- Fase 2Z `health_check_fase_1a.php`: OK 198, WARNING 14, ERROR 0.
- Fase 2Z smoke anti doble recepcion `CompraService::recibirCompra(4, 2, 24)`: error esperado `La compra #2 ya fue recibida y no puede recibirse dos veces`; conteos antes/despues sin cambios (`compras_recibidas=1`, `movimientos=3`, `auditoria=1`).
- Fase 3A `php -l` via Docker: `ProveedorController.php`, `Proveedor.php`, `proveedores/index.php`, `proveedores/ver.php`, `routes.php`, `health_check_fase_1a.php` sin errores.
- Fase 3A `health_check_fase_1a.php` via Docker: OK 141, WARNING 21, ERROR 0. Warnings principales por docs no montados en contenedor y legacy conocido.
- Fase 3A smoke read-only `Proveedor::resumenComprasPorProveedor(2, 4)`: proveedor `Juan Pedro`, 1 compra recibida, 3 lineas, 2 productos, cantidad `300.00`, total recibido `1900.00`.
- Fase 3A HTTP sin sesion `GET /proveedores/1`: 303 a `/login`, sin exponer datos.
- Fase 3A `preflight_compras_minimas.php` via Docker: OK 34, WARNING 3, ERROR 0. Warnings por docs/migraciones no visibles en el montaje del contenedor.
- Fase 3A `preflight_recepcion_compras.php` via Docker: OK 25, WARNING 2, ERROR 0. Warnings por docs no montados en el contenedor.
- Fase 3A `git diff --check`: sin errores bloqueantes; solo warnings de normalizacion CRLF.

## Warnings conocidos

- Migracion fuera de `migrations/`: `database/migrations/2026_05_21_create_operaciones_sync.sql`.
- Diferencia conocida de plan/modulos en Maximiliano: modulos extra `configuracion`, `usuarios`.
- Tablas legacy congeladas: `productos`, `inventario_movimientos`, `inventario_habitacion_config`.
- Duplicadas conocidas: `push_subscriptions` vs `pwa_push_subscriptions`, `huespedes_vehiculos` vs `huesped_vehiculos`.
- Archivos/vistas legacy de inventario permanecen sin ruta oficial.

## Bloqueos

- Ninguno para continuar con el roadmap seguro.

## Pruebas manuales pendientes

- Fase 2X fue probada manualmente por el usuario: la compra #2 aparece como `recibida`.
- No queda prueba manual bloqueante para pasar a Fase 2Y.
- Fase 2Y pendiente de revision visual opcional: abrir `/compras/reportes/recibidas` autenticado en Maximiliano y confirmar filtros/totales.

## Siguiente fase recomendada

- Fase 3B: preparar CxP base read-only con una subfase no destructiva y sin ejecutar migraciones hasta backup/validacion.

## Decisiones tecnicas importantes

- `medisoft_hoteles_import` sigue siendo la base principal local.
- `inventario_productos` y `movimientos_inventario` son la fuente moderna de inventario.
- Las tablas legacy no se borran ni se fusionan.
- Compras minimas no toca pagos, caja, CxP, documentos ni `/api/sync`.
- Fase 2Y debe mantenerse read-only y scoped por `hotel_id`.
- Fase 2Y usa solo `compras`, `compra_detalles`, `proveedores`, `inventario_productos` y `movimientos_inventario`.
- La revision visual de 2Y no bloquea 2Z porque las verificaciones automaticas pasaron y no hay escritura nueva.
- Fase 2Z no agrega rutas de escritura ni toca DB; solo endurece precondiciones de recepcion y navegacion read-only.
- Fase 3A se implemento sin migraciones ni cambios de DB; expone lectura y navegacion con campos existentes.
- Fase 3A permite lectura de `compras` desde `Proveedor`, pero no escribe en compras, inventario, Caja ni CxP.
- Fase 3B debe iniciar con diseno/migracion no destructiva y no debe integrarse con Caja.

## Archivos modificados por fase

### Fase 2X

- `src/config/routes.php`
- `src/app/controllers/CompraController.php`
- `src/app/services/CompraService.php`
- `src/app/views/compras/index.php`
- `src/app/views/compras/ver.php`
- `src/tools/saas/preflight_compras_minimas.php`
- `src/tools/saas/preflight_recepcion_compras.php`
- `src/tools/saas/health_check_fase_1a.php`
- `docs/technical/purchasing_inventory_contract.md`
- `docs/technical/inventory_reconciliation.md`

### Fase 2Y

- `src/config/routes.php`
- `src/app/controllers/CompraController.php`
- `src/app/services/CompraService.php`
- `src/app/views/compras/index.php`
- `src/app/views/compras/reporte_recibidas.php`
- `src/tools/saas/preflight_compras_minimas.php`
- `src/tools/saas/preflight_recepcion_compras.php`
- `src/tools/saas/health_check_fase_1a.php`
- `docs/technical/purchasing_inventory_contract.md`
- `docs/technical/inventory_reconciliation.md`

### Fase 2Z

- `src/app/services/CompraService.php`
- `src/app/views/compras/ver.php`
- `src/tools/saas/preflight_compras_minimas.php`
- `src/tools/saas/preflight_recepcion_compras.php`
- `src/tools/saas/health_check_fase_1a.php`
- `docs/technical/purchasing_inventory_contract.md`
- `docs/technical/inventory_reconciliation.md`

### Fase 3A

- `src/config/routes.php`
- `src/app/controllers/ProveedorController.php`
- `src/app/models/Proveedor.php`
- `src/app/views/proveedores/index.php`
- `src/app/views/proveedores/ver.php`
- `src/tools/saas/health_check_fase_1a.php`
- `docs/technical/purchasing_inventory_contract.md`
- `docs/technical/inventory_reconciliation.md`
