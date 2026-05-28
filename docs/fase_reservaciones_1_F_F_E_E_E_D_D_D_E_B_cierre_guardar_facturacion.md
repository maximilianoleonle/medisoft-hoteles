# Reservaciones 1-F-F-E-E-E-D-D-D-E-B - Cierre de guardarAction scoped

## 1. Objetivo

Documentar el cierre tecnico del scope defensivo por `hotel_id` en `FacturacionController::guardarAction()`.

## 2. Que se logro

- `guardarAction()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()`.
- Carga la solicitud con `obtenerSolicitudCompleta()`, que ya esta scoped por `hotel_id`.
- Valida que la solicitud exista y pertenezca al hotel actual antes de guardar datos fiscales.
- Si la solicitud no existe o no pertenece al hotel actual, redirige con error controlado.
- Mantiene `Reservacion::actualizarSolicitudFactura()`, que ya actualiza por `hotel_id`.
- Conserva la logica original de guardado.

## 3. Archivo modificado

- `src/app/controllers/FacturacionController.php`.

## 4. Que NO se toco

- `completarAction()`.
- `enProcesoAction()`.
- `cancelarAction()`.
- `indexAction()`.
- `obtenerSolicitudCompleta()`.
- `obtenerEstadisticas()`.
- `verAction()`.
- `Reservacion.php`.
- Cancelaciones de reservacion.
- `modificarDiasAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones.
- Schema.
- Base de datos.

## 5. Validaciones realizadas

- `php -l` en `FacturacionController.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- No se hizo POST funcional porque guardaria datos fiscales sin rollback seguro autorizado.
- Confirmacion de que no se tocaron otros POST, cancelaciones, modificar dias, PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

- `completarAction()` sigue pendiente.
- `enProcesoAction()` sigue pendiente.
- `cancelarAction()` sigue pendiente.
- Cancelaciones de reservacion siguen pendientes.
- `modificarDiasAction()` sigue pendiente.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-E-C: implementar `completarAction()` y `enProcesoAction()` scoped/defensivos, sin tocar todavia `cancelarAction()`.
