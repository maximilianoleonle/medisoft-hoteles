# Fase Inventario 1-D-C - Cierre tecnico de Inventario base

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Documentar el cierre tecnico de la integracion tenant-aware del inventario base, limitada a catalogos y configuracion:

- `inventario_categorias`
- `inventario_productos`
- `inventario_config_habitacion`

Esta fase no implementa nuevos cambios funcionales ni modifica base de datos. Solo registra el estado alcanzado y los riesgos que deben seguir controlados.

## Que se logro

### Schema y datos

- Se agrego `hotel_id INT NULL` a:
  - `inventario_categorias`
  - `inventario_productos`
  - `inventario_config_habitacion`
- Se hizo backfill de registros existentes con el hotel inicial `Los Cedros`.
- Se crearon indices simples por `hotel_id`:
  - `idx_inventario_categorias_hotel_id`
  - `idx_inventario_productos_hotel_id`
  - `idx_inventario_config_habitacion_hotel_id`
- Se crearon foreign keys hacia `hoteles(id)`:
  - `fk_inventario_categorias_hotel`
  - `fk_inventario_productos_hotel`
  - `fk_inventario_config_habitacion_hotel`
- La migracion `20260526_006_add_hotel_id_inventario_base.sql` quedo registrada en `migrations`.

### Herramientas SaaS

- `tools/saas/verificar_estado.php` fue actualizado para aceptar el estado post Inventario 1-C.
- `tools/saas/preflight_hotel_id.php` fue actualizado para reconocer las tres tablas base de inventario como migradas.
- Ambas herramientas quedaron en `PASS`.

### Codigo funcional de inventario base

El codigo funcional de inventario base ya escribe y filtra por `hotel_id` en:

- productos de inventario
- categorias de inventario
- configuracion por tipo de habitacion

Cambios principales:

- `Inventario::hotelIdActual()` usa `obtenerHotelIdActualCompat()`.
- `Inventario::create()` fuerza `hotel_id` si no viene en los datos.
- `Inventario::getAllWithCategory()` filtra productos por hotel y une categorias del mismo hotel.
- `Inventario::getByIdWithCategory()` valida producto por `id` y `hotel_id`.
- `Inventario::getCategorias()` filtra categorias por hotel.
- `Inventario::getProductosDescuentoAutomatico()` filtra productos por hotel.
- `Inventario::getConfiguracionCompleta()` filtra configuracion por hotel y une productos del mismo hotel.
- `Inventario::actualizarConfiguracion()` valida producto y configuracion por hotel.
- `InventarioController::guardarAction()` crea productos con `hotel_id`.
- `InventarioController::actualizarAction()` valida que el producto/codigo/categoria correspondan al hotel actual.
- `InventarioController::eliminarAction()` desactiva productos respetando `hotel_id`.

## Commits relacionados

- `a58d2e7` - `docs: document tipos habitacion codigo consumers`
  - Documenta consumidores de `tipos_habitacion.codigo` y `habitaciones.tipo`.
- `deb8fd3` - `docs: document inventory tipo habitacion audit`
  - Documenta auditoria inicial de consumidores de tipo de habitacion en Inventario.
- `4f776c0` - `docs: document inventory live schema audit`
  - Documenta auditoria live read-only de schema/datos de Inventario.
- `bbb4c81` - `chore: prepare inventory base hotel_id migration`
  - Prepara migracion schema/backfill para inventario base.
- `2ba4914` - `chore: update saas tools for inventory base hotel_id`
  - Actualiza herramientas SaaS y documenta ejecucion local post migracion.
- `39c4aa1` - `docs: document inventory functional scope audit`
  - Documenta auditoria del codigo funcional que debia scopearse.
- `589bd03` - `feat: scope inventory base to current hotel`
  - Integra `hotel_id` en codigo funcional de Inventario base.

## Que NO se toco

- `movimientos_inventario`
- `inventario_movimientos`
- `alertas_inventario`
- `productos`
- `categorias_producto`
- Reservaciones
- Check-in / Check-out
- Caja
- PWA/offline
- Dashboard
- Reportes
- APIs globales
- Service worker
- Indices unicos globales
- Conversion de `hotel_id` a `NOT NULL`
- Migracion a `tipo_habitacion_id`

## Riesgos pendientes

### Movimientos y operacion

- `movimientos_inventario` sigue sin `hotel_id`.
- Los flujos de entrada, salida y ajuste de inventario siguen conectados a movimientos y quedaron fuera de Inventario 1-D-B.
- Check-in/check-out siguen conectados a Inventario y Reservaciones, por lo que no deben tocarse sin una auditoria especifica.

### Indices y unicidad

- `inventario_productos.codigo` sigue siendo `UNIQUE` global.
- `inventario_config_habitacion.uk_tipo_producto` sigue siendo global sobre `(tipo_habitacion, producto_id)`.
- Estos indices impiden todavia permitir codigos/configuraciones repetidas por hotel.

### Modulos paralelos o legacy

- `productos` y `categorias_producto` siguen como posible modulo paralelo o legacy.
- `inventario_movimientos` existe como tabla distinta y no debe eliminarse sin auditoria.
- `alertas_inventario` sigue fuera de scope.

### APIs y PWA

- PWA/API todavia no son tenant-aware para inventario.
- Cualquier consumo offline o endpoint global debe esperar una fase separada.

## Validaciones actuales

Estado reportado al cierre:

- `verificar_estado.php`: `PASS`.
- `preflight_hotel_id.php`: `PASS`.
- `inventario_categorias`: 0 registros con `hotel_id IS NULL`.
- `inventario_productos`: 0 registros con `hotel_id IS NULL`.
- `inventario_config_habitacion`: 0 registros con `hotel_id IS NULL`.
- `movimientos_inventario` no fue modificado.
- No se tocaron Reservaciones, Caja, Check-in/Check-out ni PWA/offline.

## Recomendacion de siguiente fase

Siguiente fase recomendada:

`Inventario 1-E-A: Auditoria de movimientos_inventario y relacion con Reservaciones/Check-in`

No conviene implementar directamente todavia porque:

- `movimientos_inventario` tiene relaciones con `inventario_productos`, `habitaciones`, `reservaciones` y `usuarios`.
- Reservaciones todavia no tiene `hotel_id`.
- Check-in/check-out pueden disparar descuentos o restauraciones de inventario.
- Un cambio prematuro podria mezclar trazabilidad de stock, ocupacion y reservaciones.
- Antes de migrar movimientos hay que definir si `hotel_id` se deriva desde producto, habitacion, reservacion o contexto activo, y como resolver inconsistencias historicas.

## Decision de cierre

Inventario base queda cerrado en estado tenant-aware para catalogos/configuracion, manteniendo el sistema compatible con modo mono-hotel `Los Cedros`.

No se debe avanzar a movimientos, Reservaciones, Check-in/Check-out, Caja o PWA/offline sin una auditoria y aprobacion especifica.
