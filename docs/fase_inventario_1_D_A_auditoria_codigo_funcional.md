# Fase Inventario 1-D-A - Auditoria de codigo funcional de Inventario base

## Objetivo

Auditar que partes del codigo funcional de Inventario base deben escribir y filtrar por `hotel_id` despues de la migracion de schema/backfill de Inventario 1-C.

Esta auditoria fue solo lectura. No modifico archivos, no ejecuto SQL, no toco base de datos, no hizo migraciones, no hizo `ALTER TABLE` y no implemento cambios funcionales.

## Archivos encontrados en alcance directo

- `src/app/models/Inventario.php`
- `src/app/controllers/InventarioController.php`
- `src/app/services/ConfiguracionInventarioService.php`
- `src/app/models/ConfiguracionInventario.php`
- `src/app/views/inventario/ConfiguracionHabitacionService.php`

## Archivos relacionados pero fuera de alcance

- `src/app/services/InventarioService.php`
- `src/app/models/InventarioReportes.php`
- `src/app/helpers/integracion_inventario.php`
- `src/app/controllers/ProductoController.php`
- `src/app/models/Producto.php`
- `src/app/models/MovimientoInventario.php`
- `src/app/models/Reservacion.php`
- `src/app/controllers/ReservacionController.php`

## Lecturas a scopear

Las siguientes lecturas deben filtrar por hotel actual:

- `Inventario::getAllWithCategory()`
  - Lee `inventario_productos` y `inventario_categorias`.
  - Requiere `p.hotel_id = ?` y join seguro con `c.hotel_id = p.hotel_id`.

- `Inventario::getByIdWithCategory()`
  - Lee un producto por ID.
  - Requiere `p.id = ? AND p.hotel_id = ?`.

- `Inventario::getCategorias()`
  - Lee `inventario_categorias`.
  - Requiere `hotel_id = ?`.

- `Inventario::getProductosDescuentoAutomatico()`
  - Actualmente usa una busqueda generica sobre `inventario_productos`.
  - Debe filtrar `hotel_id = ?`, `descuento_automatico = 1` y `activo = 1`.

- `Inventario::getConfiguracionCompleta()`
  - Lee `inventario_config_habitacion` con `inventario_productos`.
  - Requiere `ich.hotel_id = ?` y `p.hotel_id = ich.hotel_id`.

## Writes a scopear

Los siguientes writes deben escribir o validar `hotel_id`:

- `InventarioController::guardarAction()`
  - Crea registros en `inventario_productos`.
  - Debe enviar `hotel_id` en el payload de creacion.

- `InventarioController::actualizarAction()`
  - Actualiza productos.
  - Debe validar que el producto pertenece al hotel actual antes de actualizar.

- `InventarioController::eliminarAction()`
  - Desactiva productos.
  - Debe validar `id` y `hotel_id` antes de desactivar.

- `Inventario::actualizarConfiguracion()`
  - Lee, actualiza o inserta en `inventario_config_habitacion`.
  - Debe usar `hotel_id` en SELECT, UPDATE e INSERT.

- `ConfiguracionInventarioService::actualizarConfiguracion()`
  - Debe scopearse solo si se confirma que esta activo en rutas actuales.
  - Si se toca, debe usar `hotel_id` en SELECT, UPDATE e INSERT.

- `ConfiguracionInventario::guardarConfiguracion()`
  - Debe scopearse solo si se confirma que esta activo en rutas actuales.
  - Si se toca, debe insertar `hotel_id` y usar una llave logica por hotel.

## Validaciones unicas

- `inventario_productos.codigo` debe validarse por hotel en codigo:
  - Futuro criterio logico: `codigo = ? AND hotel_id = ?`.

- `inventario_config_habitacion` debe validarse por:
  - `hotel_id + tipo_habitacion + producto_id`.

No se deben cambiar indices unicos todavia.

Los indices actuales siguen siendo globales:

- `inventario_productos.codigo`
- `inventario_config_habitacion.uk_tipo_producto (tipo_habitacion, producto_id)`

Estos indices se deben revisar en una fase posterior, cuando el codigo funcional ya este scoped y exista una estrategia para permitir datos repetidos entre hoteles.

## Estrategia recomendada

- Usar `obtenerHotelIdActualCompat()` como fuente temporal del hotel actual.
- No depender todavia de login ni sesiones.
- Fallar cerrado si no se puede resolver el hotel actual.
- No tocar el `Model` base global.
- Agregar `hotel_id` al `fillable` de `Inventario`.
- Mantener `inventario_config_habitacion.tipo_habitacion` textual temporalmente.
- Centralizar primero el scope en `Inventario.php` y consumirlo desde `InventarioController.php`.

## Que NO tocar todavia

- `InventarioService::descontarInventarioCheckIn()`
- Restauraciones por cancelacion/check-out.
- `movimientos_inventario`.
- `inventario_movimientos`.
- `alertas_inventario`.
- `productos` y `categorias_producto`.
- Reservaciones.
- Caja.
- PWA/offline.
- Reportes/Dashboard.
- APIs globales.
- Indices unicos.
- `hotel_id NOT NULL`.

## Riesgos

- Nuevos productos o configuraciones pueden quedar sin `hotel_id` si las rutas activas no se scopean.
- `productos` y `categorias_producto` siguen funcionando como modulo paralelo o legacy.
- El duplicado de nombre `Cloro` en `inventario_productos` no bloquea esta fase porque no se propone indice unico por nombre.
- Check-in/check-out sigue conectado a Inventario y debe esperar una fase propia.
- El indice global `inventario_productos.codigo` sigue impidiendo codigos repetidos entre hoteles.
- `inventario_config_habitacion.uk_tipo_producto` sigue impidiendo configuraciones repetidas entre hoteles.

## Propuesta para Inventario 1-D-B

Tocar solo:

- `src/app/models/Inventario.php`
- `src/app/controllers/InventarioController.php`

Opcional:

- `src/app/services/ConfiguracionInventarioService.php`, solo si se confirma que esta activo en rutas actuales.

Cambios propuestos:

- Cargar `hotel_config.php` donde sea necesario.
- Resolver hotel actual con `obtenerHotelIdActualCompat()`.
- Agregar `hotel_id` al `fillable` de `Inventario`.
- Scopear lecturas de productos/categorias/configuracion por `hotel_id`.
- Insertar `hotel_id` al crear producto.
- Validar producto por `hotel_id` antes de editar, actualizar, desactivar o ajustar.
- Scopear configuracion con `hotel_id` en SELECT, UPDATE e INSERT.
- Mantener movimientos fuera de esta fase, salvo validaciones previas si el flujo los usa indirectamente.

## Pruebas propuestas

- `php -l` en archivos modificados.
- `docker compose exec app php tools/saas/verificar_estado.php`.
- `docker compose exec app php tools/saas/preflight_hotel_id.php`.
- GET `/inventario`.
- GET `/inventario/nuevo`.
- GET `/inventario/configuracion`.
- Crear producto y confirmar `inventario_productos.hotel_id = Los Cedros`.
- Editar producto y confirmar que conserva `hotel_id`.
- Desactivar producto y confirmar que respeta `hotel_id`.
- Guardar configuracion y confirmar `inventario_config_habitacion.hotel_id = Los Cedros`.
- Confirmar 0 nuevos `hotel_id NULL` en:
  - `inventario_categorias`
  - `inventario_productos`
  - `inventario_config_habitacion`
- Confirmar que no se toco `movimientos_inventario`.

## Decision

No iniciar Inventario 1-D-B hasta que se apruebe explicitamente la implementacion.

Antes de implementar, conviene mantener el alcance reducido a `Inventario.php` e `InventarioController.php`, y dejar los caminos paralelos (`productos`, `categorias_producto`, reportes, movimientos, check-in/check-out y Reservaciones) para fases separadas.
