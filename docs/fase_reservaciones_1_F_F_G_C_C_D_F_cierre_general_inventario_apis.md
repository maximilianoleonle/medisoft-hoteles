# RESERVACIONES 1-F-F-G-C-C-D-F - Cierre general inventario APIs

## 1. Objetivo

Documentar el cierre general del bloque de inventario APIs scoped/protegido por hotel_id.

## 2. Que quedo cerrado

* Correccion base de endpoints inventario en ApiController.
* /api/inventario/verificar-stock/{id}.
* /api/inventario/preview-checkin/{id}.
* /api/inventario/alertas.

## 3. Que se logro

* Los endpoints de inventario ya no dependen de helpers inexistentes como $this->db o $this->jsonResponse().
* Se eliminaron llamadas activas a metodos inexistentes de InventarioService.
* /api/inventario/verificar-stock/{id} usa InventarioService::verificarDisponibilidad().
* /api/inventario/preview-checkin/{id} valida reservacion/habitaciones por hotel_id antes de responder.
* /api/inventario/preview-checkin/{id} responde controlado si no existe fuente segura para calcular productos.
* /api/inventario/alertas usa inventario_productos.hotel_id como fuente scoped.
* /api/inventario/alertas no consulta alertas_inventario.
* /api/inventario/alertas no consulta productos legacy.
* No se agregaron escrituras nuevas.
* No se toco schema ni base de datos.

## 4. Que NO se toco

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

## 5. Validaciones generales

* php -l en ApiController.php durante fases funcionales.
* git diff --check.
* verificar_estado.php quedo bloqueado por Docker Desktop/engine no disponible cuando aplico.
* preflight_hotel_id.php quedo bloqueado por Docker Desktop/engine no disponible cuando aplico.
* GET local quedo bloqueado cuando no hubo servidor activo en localhost:8080.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se toco White Label.

## 6. Riesgos pendientes

* src/api/*.php legacy/directos siguen pendientes.
* Sync.php sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* alertas_inventario y productos legacy quedan como pendientes/documentables o decision futura de schema.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance hasta cerrar seguridad multi-hotel.

## 7. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-E-A: auditoria de src/api/*.php legacy/directos, sin implementacion directa.
