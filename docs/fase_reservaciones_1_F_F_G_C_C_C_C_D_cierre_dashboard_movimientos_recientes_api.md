# Reservaciones 1-F-F-G-C-C-C-C-D-CIERRE: cierre tecnico de /api/dashboard/movimientos-recientes

## 1. Objetivo

Documentar el cierre tecnico del endpoint `/api/dashboard/movimientos-recientes` scoped por `hotel_id`.

## 2. Que se logro

* Se agrego `ApiController::movimientosRecientesAction()`.
* El endpoint `/api/dashboard/movimientos-recientes` ya responde de forma controlada.
* Usa `hotelIdActual()` como fuente del hotel actual.
* Usa `MovimientoCaja::obtenerUltimosMovimientos(10)`.
* Se confirma que `MovimientoCaja::obtenerUltimosMovimientos()` ya filtra `mc.hotel_id = ?`.
* Se agrega filtro defensivo por `hotel_id` en los resultados devueltos.
* Usuarios queda como auxiliar.
* `categorias_movimientos` queda como auxiliar.
* Responde JSON con `success`, `data` y `timestamp`.
* Si no hay movimientos, responde `data: []`.
* Si hay error, responde `success: false` y `data: []`.

## 3. Endpoint cerrado

* `/api/dashboard/movimientos-recientes`

## 4. Archivo modificado

* `src/app/controllers/ApiController.php`

## 5. Que NO se toco

* Inventario APIs.
* `src/api/*.php`.
* `Sync.php`.
* IndexedDB/offline data.
* `pwa.js`.
* `offline-data.js`.
* `service-worker.js`.
* manifest/branding/White Label.
* `MovimientoCaja.php`.
* migraciones.
* schema.
* base de datos.

## 6. Validaciones realizadas

* `php -l src/app/controllers/ApiController.php`: PASS.
* `git diff --check`: PASS.
* `verificar_estado.php`: bloqueado por Docker Desktop/engine no disponible.
* `preflight_hotel_id.php`: bloqueado por Docker Desktop/engine no disponible.
* `GET /api/dashboard/movimientos-recientes`: bloqueado porque no hay servidor activo en `localhost:8080`.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron inventario APIs, `src/api/*.php`, Sync, IndexedDB/offline data ni White Label.

## 7. Riesgos pendientes

* Inventario APIs siguen pendientes.
* `src/api/*.php` legacy/directos siguen pendientes.
* `Sync.php` sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## 8. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-D-A: auditoria especifica de inventario APIs, sin implementacion directa.
