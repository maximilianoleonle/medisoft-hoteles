# Reservaciones 1-F-F-E-E-E-D-D-D-E-C - Cierre de estados de facturacion scoped

## 1. Objetivo

Documentar el cierre tecnico del scope defensivo por `hotel_id` en `FacturacionController::completarAction()` y `FacturacionController::enProcesoAction()`.

## 2. Que se logro

- `completarAction()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()`.
- `completarAction()` carga la solicitud con `obtenerSolicitudCompleta()`, ya scoped por `hotel_id`.
- `completarAction()` valida que la solicitud exista y pertenezca al hotel actual antes de completar.
- `enProcesoAction()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()`.
- `enProcesoAction()` carga la solicitud con `obtenerSolicitudCompleta()`, ya scoped por `hotel_id`.
- `enProcesoAction()` valida que la solicitud exista y pertenezca al hotel actual antes de cambiar a en proceso.
- Si la solicitud no existe o no pertenece al hotel actual, ambos metodos redirigen con error controlado.
- Ambos mantienen `Reservacion::actualizarSolicitudFactura()`, que ya actualiza por `hotel_id`.
- Se conserva la logica original de cambio de estado.

## 3. Archivo modificado

- `src/app/controllers/FacturacionController.php`.

## 4. Que NO se toco

- `guardarAction()`.
- `cancelarAction()`.
- `indexAction()`.
- `verAction()`.
- `obtenerSolicitudCompleta()`.
- `obtenerEstadisticas()`.
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
- No se hizo POST funcional porque cambiaria estados en base de datos sin rollback seguro autorizado.
- Confirmacion de que no se toco `cancelarAction()`, cancelaciones, modificar dias, PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

- `cancelarAction()` sigue pendiente.
- Cancelaciones de reservacion siguen pendientes.
- `modificarDiasAction()` sigue pendiente.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-E-D-A: auditoria/preparacion de `FacturacionController::cancelarAction()`, en fase propia por manejo de notas.
