# Reservaciones 1-F-F-E-E-E-D-D-D-A - Auditoria de FacturacionController

## 1. Objetivo

Auditar `FacturacionController` para preparar una correccion segura por `hotel_id` en listados, detalle, estadisticas y pagos relacionados con `solicitudes_factura`.

## 2. Hallazgo principal

- `FacturacionController` sigue siendo el bloque pendiente.
- Lista, detalla, calcula estadisticas y consulta pagos sin `hotel_id`.
- Los metodos del modelo para crear, leer individualmente, listar pendientes y actualizar solicitudes ya estan scoped.
- El controller todavia arma consultas propias globales.

## 3. Archivos encontrados

- `src/app/controllers/FacturacionController.php`
- `src/app/models/Reservacion.php`
- `src/app/views/facturacion/index.php`
- `src/app/views/facturacion/detalle.php`
- `src/config/routes.php`

## 4. Metodos auditados

- `FacturacionController::indexAction()`
- `FacturacionController::verAction()`
- `FacturacionController::obtenerSolicitudCompleta()`
- `FacturacionController::obtenerEstadisticas()`
- `guardarAction()`
- `completarAction()`
- `enProcesoAction()`
- `cancelarAction()`
- Vistas `facturacion/index.php` y `facturacion/detalle.php`

## 5. Estado actual

- `Reservacion::crearSolicitudFactura()`: ya scoped.
- `Reservacion::obtenerSolicitudFactura()`: ya scoped.
- `Reservacion::obtenerSolicitudesPendientes()`: ya scoped.
- `Reservacion::actualizarSolicitudFactura()`: ya scoped.
- `FacturacionController::indexAction()`: global.
- `FacturacionController::verAction()`: global en pagos y detalle.
- `FacturacionController::obtenerSolicitudCompleta()`: global.
- `FacturacionController::obtenerEstadisticas()`: global.
- Vistas `facturacion/*`: render-only.

## 6. Consultas globales pendientes

- `indexAction()`: count/list sin `sf.hotel_id`.
- `indexAction()`: joins sin `sf.hotel_id = r.hotel_id`.
- `obtenerSolicitudCompleta()`: detalle por `sf.id = ?` sin `hotel_id`.
- `obtenerEstadisticas()`: `COUNT/SUM` sin `hotel_id`.
- `verAction()`: pagos desde `movimientos_caja` por `reservacion_id` sin `hotel_id`.

## 7. Validaciones requeridas

- `sf.hotel_id = ?`
- `sf.reservacion_id = r.id`
- `sf.hotel_id = r.hotel_id`
- `r.hotel_id = ?`
- `movimientos_caja.hotel_id = ?`
- `huespedes` y `usuarios` deben quedar como auxiliares, no como fuente de scope.

## 8. Riesgos por metodo

### Alto

- `FacturacionController::indexAction()`
- `FacturacionController::obtenerSolicitudCompleta()`
- `FacturacionController::obtenerEstadisticas()`

### Medio

- `FacturacionController::verAction()`, por pagos desde `movimientos_caja`.
- Acciones POST que dependen de detalle global antes de llamar al modelo scoped.

### Bajo

- Vistas `facturacion/index.php` y `facturacion/detalle.php`, porque solo renderizan datos recibidos.

## 9. Que implementar primero

Reservaciones 1-F-F-E-E-E-D-D-D-B:

- `FacturacionController::indexAction()` scoped, incluyendo count/list y llamada a estadisticas preparada para hotel.

## 10. Que implementar despues

- Reservaciones 1-F-F-E-E-E-D-D-D-C: `obtenerSolicitudCompleta()` y pagos de `verAction()` scoped.
- Reservaciones 1-F-F-E-E-E-D-D-D-D: `obtenerEstadisticas()` scoped.

## 11. Que dejar fuera

- Cancelaciones.
- Modificar dias.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.
- Usuarios tenant.

## 12. Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET controlado de `/facturacion` y `/facturacion/ver/{id}` si aplica.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que PWA/Sync/APIs no se tocaron.

## 13. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-B: implementar unicamente `FacturacionController::indexAction()` scoped por `hotel_id`, sin tocar todavia detalle, pagos ni estadisticas completas si requieren fase separada.
