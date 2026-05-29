# RESERVACIONES 1-F-F-G-C-C-D-C - Cierre inventario verificar stock API

## 1. Objetivo

Documentar el cierre tecnico del endpoint /api/inventario/verificar-stock/{id} usando una fuente real y scoped por hotel_id.

## 2. Que se logro

* verificarStockHabitacionAction() mantiene normalizacion de id a entero.
* Si el id es invalido, responde JSON controlado con status 400.
* Usa Database::getInstance()->getConnection().
* Usa InventarioService::verificarDisponibilidad($habitacion_id).
* No usa InventarioService::verificarDisponibilidadInventario(), porque no existe.
* No usa $this->db.
* No usa $this->jsonResponse().
* Devuelve JSON controlado con:
  * success
  * disponible
  * productos_faltantes
  * total_productos
  * mensaje
* Si hay excepcion, responde success: false con error controlado.
* No se agrego SQL nuevo en ApiController.

## 3. Endpoint cerrado

* /api/inventario/verificar-stock/{id}

## 4. Archivo modificado

* src/app/controllers/ApiController.php

## 5. Que NO se toco

* /api/inventario/preview-checkin/{id}.
* /api/inventario/alertas.
* InventarioService.php.
* Modelos de inventario.
* src/api/*.php.
* Sync.php.
* IndexedDB/offline data.
* pwa.js.
* offline-data.js.
* service-worker.js.
* manifest/branding/White Label.
* migraciones.
* schema.
* base de datos.

## 6. Validaciones realizadas

* php -l src/app/controllers/ApiController.php: PASS.
* git diff --check: PASS.
* verificar_estado.php: bloqueado por Docker Desktop/engine no disponible.
* preflight_hotel_id.php: bloqueado por Docker Desktop/engine no disponible.
* GET local: bloqueado porque no hay servidor activo en localhost:8080.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron preview-checkin, alertas, InventarioService, modelos, src/api/*.php, Sync, IndexedDB/offline data ni White Label.

## 7. Riesgos pendientes

* /api/inventario/preview-checkin/{id} sigue pendiente.
* /api/inventario/alertas sigue pendiente.
* src/api/*.php legacy/directos siguen pendientes.
* Sync.php sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## 8. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-D-D: implementar unicamente /api/inventario/preview-checkin/{id} scoped por reservacion/habitacion/hotel, sin tocar todavia alertas, src/api/*.php, Sync ni IndexedDB.
