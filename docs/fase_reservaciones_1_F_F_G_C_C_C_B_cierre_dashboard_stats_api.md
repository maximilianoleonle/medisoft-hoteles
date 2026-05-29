# Reservaciones 1-F-F-G-C-C-C-B-CIERRE: cierre tecnico de /api/dashboard/stats

## 1. Objetivo

Documentar el cierre tecnico del endpoint `/api/dashboard/stats`.

## 2. Que se logro

* `ApiController::estadisticasDashboardAction()` ya no intenta reflejar `DashboardController::getEstadisticas()`.
* Se elimino/reemplazo la llamada a un metodo inexistente.
* El endpoint ahora usa `DashboardController::getEstadisticasCompletas()`.
* `getEstadisticasCompletas()` es un metodo real y scoped por `hotel_id`.
* Se conserva la respuesta JSON del endpoint en lo posible.
* Se evita que `/api/dashboard/stats` quede roto por reflexion incorrecta.

## 3. Endpoint cerrado

* `/api/dashboard/stats`

## 4. Archivo modificado

* `src/app/controllers/ApiController.php`

## 5. Que NO se toco

* `/api/dashboard/ocupacion`.
* `/api/dashboard/movimientos-recientes`.
* `/api/dashboard/alertas`.
* Inventario APIs.
* `src/api/*.php`.
* `Sync.php`.
* IndexedDB/offline data.
* `pwa.js`.
* `offline-data.js`.
* `service-worker.js`.
* manifest/branding/White Label.
* migraciones.
* schema.
* base de datos.

## 6. Validaciones realizadas

* `php -l src/app/controllers/ApiController.php`: PASS.
* `git diff --check`: PASS.
* `verificar_estado.php`: bloqueado por Docker Desktop/engine no disponible.
* `preflight_hotel_id.php`: bloqueado por Docker Desktop/engine no disponible.
* `GET /api/dashboard/stats`: bloqueado porque no hay servidor activo en `localhost:8080`.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron inventario APIs, `src/api/*.php`, Sync, IndexedDB/offline data ni White Label.

## 7. Riesgos pendientes

* `/api/dashboard/ocupacion` sigue declarado sin metodo encontrado.
* `/api/dashboard/movimientos-recientes` sigue declarado sin metodo encontrado.
* `/api/dashboard/alertas` sigue declarado sin metodo encontrado.
* Inventario APIs siguen pendientes.
* `src/api/*.php` legacy/directos siguen pendientes.
* `Sync.php` sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## 8. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-C-C-A: auditoria/decision sobre rutas dashboard declaradas sin metodo:

* `/api/dashboard/ocupacion`
* `/api/dashboard/movimientos-recientes`
* `/api/dashboard/alertas`
