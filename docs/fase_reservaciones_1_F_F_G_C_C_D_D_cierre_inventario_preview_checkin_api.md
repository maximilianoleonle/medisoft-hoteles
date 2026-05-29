# RESERVACIONES 1-F-F-G-C-C-D-D - Cierre inventario preview check-in API

## 1. Objetivo

Documentar el cierre tecnico del endpoint /api/inventario/preview-checkin/{id} scoped por hotel_id.

## 2. Que se logro

* previewCheckinInventarioAction() normaliza reservacion_id a entero.
* Si el ID es invalido, responde JSON controlado con status 400.
* Usa hotelIdActual() como fuente del hotel actual.
* Valida la reservacion con r.id = ? AND r.hotel_id = ?.
* Valida rh.reservacion_id = r.id.
* Valida rh.hotel_id = r.hotel_id.
* Valida h.id = rh.habitacion_id.
* Valida h.hotel_id = rh.hotel_id.
* Si no encuentra la reservacion/habitaciones del hotel actual, responde 404 controlado.
* No usa InventarioService::obtenerProductosCheckIn(), porque no existe.
* Como no existe una fuente real scoped para el preview, responde controlado indicando que el preview aun no esta disponible.
* No se agregaron escrituras.

## 3. Endpoint cerrado

* /api/inventario/preview-checkin/{id}

## 4. Archivo modificado

* src/app/controllers/ApiController.php

## 5. Que NO se toco

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
* Confirmacion de que no se tocaron alertas, InventarioService, modelos, src/api/*.php, Sync, IndexedDB/offline data ni White Label.

## 7. Riesgos pendientes

* /api/inventario/alertas sigue pendiente.
* Fuente real scoped para preview de inventario sigue pendiente si se requiere calculo de productos.
* src/api/*.php legacy/directos siguen pendientes.
* Sync.php sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## 8. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-D-E-A: auditoria/decision especifica de /api/inventario/alertas, porque puede depender de tablas legacy o decision de schema.
