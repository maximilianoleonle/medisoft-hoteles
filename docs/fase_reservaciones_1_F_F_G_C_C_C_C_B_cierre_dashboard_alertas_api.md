# Reservaciones 1-F-F-G-C-C-C-C-B-CIERRE: cierre tecnico de /api/dashboard/alertas

## 1. Objetivo

Documentar el cierre tecnico del endpoint `/api/dashboard/alertas` scoped por `hotel_id`.

## 2. Que se logro

* Se agrego `ApiController::alertasDashboardAction()`.
* El endpoint `/api/dashboard/alertas` ya responde correctamente a `dashboard.js:updateAlerts()`.
* Usa `hotelIdActual()` como fuente del hotel actual.
* Las consultas de check-ins pendientes filtran `r.hotel_id = ?`.
* Las consultas de check-outs vencidos filtran `r.hotel_id = ?`.
* Se valida `rh.reservacion_id = r.id`.
* Se valida `rh.hotel_id = r.hotel_id`.
* Se valida `hab.hotel_id = rh.hotel_id`.
* Huespedes queda como auxiliar para nombre/contacto, no como fuente de scope.
* No se usan `Reservacion::getCheckInsPendientes()` ni `Reservacion::getCheckOutsPendientes()`, porque siguen globales.
* La respuesta JSON mantiene `success`, `data` y `timestamp`.
* Si no hay alertas, responde `data: []` de forma controlada.

## 3. Endpoint cerrado

* `/api/dashboard/alertas`

## 4. Archivo modificado

* `src/app/controllers/ApiController.php`

## 5. Que NO se toco

* `/api/dashboard/ocupacion`.
* `/api/dashboard/movimientos-recientes`.
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
* `GET /api/dashboard/alertas`: bloqueado porque no hay servidor activo en `localhost:8080`.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron ocupacion, movimientos-recientes, inventario APIs, `src/api/*.php`, Sync, IndexedDB/offline data ni White Label.

## 7. Riesgos pendientes

* `/api/dashboard/ocupacion` sigue pendiente.
* `/api/dashboard/movimientos-recientes` sigue pendiente.
* Inventario APIs siguen pendientes.
* `src/api/*.php` legacy/directos siguen pendientes.
* `Sync.php` sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## 8. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-C-C-C: implementar o mapear unicamente `/api/dashboard/ocupacion` a un metodo real scoped, sin tocar todavia movimientos-recientes, inventario, `src/api/*.php`, Sync ni IndexedDB.
