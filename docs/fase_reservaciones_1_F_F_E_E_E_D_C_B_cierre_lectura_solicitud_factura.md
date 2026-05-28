# Reservaciones 1-F-F-E-E-E-D-C-B - Cierre de lectura de solicitud de factura

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en `Reservacion::obtenerSolicitudFactura()`.

## 2. Que se logro

- `obtenerSolicitudFactura()` ahora usa `hotelIdActual()`.
- La consulta de `solicitudes_factura` se filtra por `reservacion_id + hotel_id`.
- Se valida contra `reservaciones`.
- Se valida `sf.reservacion_id = r.id`.
- Se valida `sf.hotel_id = r.hotel_id`.
- Se valida `r.hotel_id = ?`.
- La lectura individual de solicitud de factura ya queda tenant-aware.
- Se conserva la logica original de lectura.

## 3. Archivo modificado

- `src/app/models/Reservacion.php`

## 4. Que NO se toco

- `obtenerSolicitudesPendientes()`.
- `actualizarSolicitudFactura()`.
- `FacturacionController`.
- `cancelarAction()`.
- `Reservacion::cancelar()`.
- `modificarDiasAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones.
- Schema.
- Base de datos.

## 5. Validaciones realizadas

- `php -l` en `Reservacion.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Pruebas sin escritura mediante lint, diff y herramientas SaaS.
- Confirmacion de que no se tocaron `FacturacionController`, cancelaciones, modificar dias, PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

- `obtenerSolicitudesPendientes()` sigue pendiente.
- `actualizarSolicitudFactura()` sigue pendiente.
- `FacturacionController` sigue global.
- Cancelaciones y `Reservacion::cancelar()` siguen pendientes.
- Modificar dias sigue pendiente.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-A: auditoria/preparacion de listados y actualizaciones de `solicitudes_factura`, empezando por `obtenerSolicitudesPendientes()` y `actualizarSolicitudFactura()`, sin tocar todavia `FacturacionController` completo ni cancelaciones.
