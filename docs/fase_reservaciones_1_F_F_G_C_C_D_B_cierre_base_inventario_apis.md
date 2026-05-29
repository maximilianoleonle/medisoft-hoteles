# RESERVACIONES 1-F-F-G-C-C-D-B - Cierre base inventario APIs

## 1. Objetivo

Documentar el cierre tecnico de la correccion base segura de endpoints inventario en ApiController.

## 2. Que se logro

* previewCheckinInventarioAction() normaliza id a entero.
* previewCheckinInventarioAction() responde JSON controlado.
* alertasInventarioAction() responde JSON controlado sin consultar tablas legacy globales.
* verificarStockHabitacionAction() normaliza id a entero.
* verificarStockHabitacionAction() responde JSON controlado.
* Se removieron llamadas activas a $this->jsonResponse().
* Se removieron llamadas activas a $this->db.
* Se removieron llamadas activas a InventarioService::obtenerProductosCheckIn().
* Se removieron llamadas activas a InventarioService::verificarDisponibilidadInventario().
* Los endpoints quedan protegidos con success: false y estructura vacia/controlada mientras se implementa el scope profundo.
* preview-checkin y verificar-stock devuelven 400 si el ID es invalido.
* Los pendientes devuelven 501.
* No se agregaron consultas.
* No se agregaron escrituras.

## 3. Endpoints cubiertos

* /api/inventario/preview-checkin/{id}
* /api/inventario/alertas
* /api/inventario/verificar-stock/{id}

## 4. Archivo modificado

* src/app/controllers/ApiController.php

## 5. Que NO se toco

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
* Confirmacion de que no se tocaron InventarioService, modelos, src/api/*.php, Sync, IndexedDB/offline data ni White Label.

## 7. Riesgos pendientes

* Implementar /api/inventario/verificar-stock/{id} usando InventarioService::verificarDisponibilidad().
* Implementar preview-checkin scoped por reservacion/habitacion/hotel.
* Resolver alertas con fuente scoped o decision de schema.
* src/api/*.php legacy/directos siguen pendientes.
* Sync.php sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## 8. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-D-C: implementar unicamente /api/inventario/verificar-stock/{id} usando InventarioService::verificarDisponibilidad(), sin tocar todavia preview-checkin, alertas, src/api/*.php, Sync ni IndexedDB.
