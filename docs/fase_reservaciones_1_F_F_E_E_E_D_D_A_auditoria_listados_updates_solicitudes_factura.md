# Reservaciones 1-F-F-E-E-E-D-D-A - Auditoria de listados y updates de solicitudes_factura

## 1. Objetivo

Auditar los metodos que listan y actualizan `solicitudes_factura` para preparar una correccion segura por `hotel_id`.

## 2. Hallazgo principal

- El riesgo esta concentrado en listados/updates de `solicitudes_factura`.
- `Reservacion::obtenerSolicitudesPendientes()` lista globalmente.
- `Reservacion::actualizarSolicitudFactura()` actualiza por `id` sin `hotel_id`.
- `FacturacionController` lista, detalla, actualiza y calcula estadisticas sin scope por hotel.
- Las vistas de facturacion solo renderizan datos recibidos.

## 3. Archivos encontrados

- `src/app/models/Reservacion.php`
- `src/app/controllers/FacturacionController.php`
- `src/app/controllers/ReservacionController.php`
- `src/app/views/facturacion/index.php`
- `src/app/views/facturacion/detalle.php`

## 4. Metodos auditados

- `Reservacion::obtenerSolicitudesPendientes()`
- `Reservacion::actualizarSolicitudFactura()`
- `FacturacionController::indexAction()`
- `FacturacionController::verAction()`
- `FacturacionController::obtenerSolicitudCompleta()`
- `FacturacionController::obtenerEstadisticas()`
- Vistas de facturacion

## 5. Estado actual

- `crearSolicitudFactura()`: ya scoped; inserta `hotel_id`.
- `obtenerSolicitudFactura()`: ya scoped; lee por `reservacion_id + hotel_id`.
- Exportaciones PDF/Excel de `ReservacionController`: ya filtran `solicitudes_factura` por `hotel_id`.
- `obtenerSolicitudesPendientes()`: global.
- `actualizarSolicitudFactura()`: global.
- `FacturacionController`: global en listados, detalle, estadisticas y pagos.

## 6. Consultas globales detectadas

- `SELECT` de pendientes sin `sf.hotel_id`.
- `UPDATE solicitudes_factura WHERE id = ?`.
- `FacturacionController` count/list/detail/stats sin `sf.hotel_id`.
- `FacturacionController::verAction()` consulta `movimientos_caja` por `reservacion_id` sin `hotel_id`.

## 7. Validaciones requeridas

- `sf.hotel_id = ?`
- `sf.reservacion_id = r.id`
- `sf.hotel_id = r.hotel_id`
- `r.hotel_id = ?`
- `movimientos_caja.hotel_id = ?` cuando se consulten pagos
- `huespedes` y `usuarios` como auxiliares, no como fuente de scope

## 8. Riesgos por metodo

### Alto

- `FacturacionController::indexAction()`
- `FacturacionController::obtenerSolicitudCompleta()`
- `FacturacionController::obtenerEstadisticas()`
- `Reservacion::actualizarSolicitudFactura()`

### Medio

- `Reservacion::obtenerSolicitudesPendientes()`
- `FacturacionController::verAction()`, especialmente por pagos desde `movimientos_caja`

### Bajo

- Vistas `facturacion/index.php` y `facturacion/detalle.php`, porque solo renderizan datos

## 9. Que implementar primero

Reservaciones 1-F-F-E-E-E-D-D-B:

- `Reservacion::obtenerSolicitudesPendientes()` scoped por `hotel_id`, con join validando `sf.hotel_id = r.hotel_id`.

## 10. Que implementar despues

- Reservaciones 1-F-F-E-E-E-D-D-C: `Reservacion::actualizarSolicitudFactura()` scoped.
- Reservaciones 1-F-F-E-E-E-D-D-D: `FacturacionController` listado/detalle/estadisticas scoped.

## 11. Que dejar fuera

- Creacion de `solicitudes_factura`, ya cerrada.
- Lectura individual `obtenerSolicitudFactura()`, ya cerrada.
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
- Pruebas controladas solo con rollback seguro o entorno de prueba.
- Confirmar que PWA/Sync/APIs no se tocaron.
- Confirmar 0 nuevos `hotel_id` NULL.

## 13. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-B: implementar unicamente `Reservacion::obtenerSolicitudesPendientes()` scoped por `hotel_id`, sin tocar `actualizarSolicitudFactura()` ni `FacturacionController` todavia.
