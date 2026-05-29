# Reservaciones 1-F-F-G-C-C-C-C-C-CIERRE: cierre tecnico de /api/dashboard/ocupacion

## 1. Objetivo

Documentar el cierre tecnico del endpoint `/api/dashboard/ocupacion` scoped por `hotel_id`.

## 2. Que se logro

* Se agrego `ApiController::ocupacionActualAction()`.
* El endpoint `/api/dashboard/ocupacion` ya responde de forma controlada.
* Usa `hotelIdActual()` como fuente del hotel actual.
* Reutiliza `DashboardController::getEstadisticasCompletas()`.
* Reutiliza `DashboardController::getDatosGraficos()`.
* No duplica consultas globales.
* Aprovecha metodos reales ya scoped por `hotel_id`.
* Responde JSON con `success`, `data` y `timestamp`.
* La estructura `data` incluye:
  * `habitaciones`
  * `porcentaje_ocupacion`
  * `ocupadas`
  * `disponibles`
  * `total`
  * `ocupacion_semanal`
* Si hay error, responde `success: false` con estructura vacia.

## 3. Endpoint cerrado

* `/api/dashboard/ocupacion`

## 4. Archivo modificado

* `src/app/controllers/ApiController.php`

## 5. Que NO se toco

* `/api/dashboard/movimientos-recientes`.
* Inventario APIs.
* `src/api/*.php`.
* `Sync.php`.
* IndexedDB/offline data.
* `pwa.js`.
* `offline-data.js`.
* `service-worker.js`.
* manifest/branding/White Label.
* `DashboardController.php`.
* migraciones.
* schema.
* base de datos.

## 6. Validaciones realizadas

* `php -l src/app/controllers/ApiController.php`: PASS.
* `git diff --check`: PASS.
* `verificar_estado.php`: bloqueado por Docker Desktop/engine no disponible.
* `preflight_hotel_id.php`: bloqueado por Docker Desktop/engine no disponible.
* `GET /api/dashboard/ocupacion`: bloqueado porque no hay servidor activo en `localhost:8080`.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron movimientos-recientes, inventario APIs, `src/api/*.php`, Sync, IndexedDB/offline data ni White Label.

## 7. Riesgos pendientes

* `/api/dashboard/movimientos-recientes` sigue pendiente.
* Inventario APIs siguen pendientes.
* `src/api/*.php` legacy/directos siguen pendientes.
* `Sync.php` sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## 8. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-C-C-D: implementar unicamente `/api/dashboard/movimientos-recientes`, preferentemente usando `MovimientoCaja::obtenerUltimosMovimientos()` o una fuente ya scoped por `hotel_id`, sin tocar todavia inventario, `src/api/*.php`, Sync ni IndexedDB.
