# RESERVACIONES 1-F-F-G-D-B-CIERRE - Cierre tecnico de src/api/sync.php legacy directo

## 1. Objetivo

Documentar el cierre tecnico del bloqueo de `src/api/sync.php` como endpoint legacy directo de sincronizacion.

## 2. Que se logro

* `src/api/sync.php` quedo bloqueado con HTTP 410 Gone.
* Responde JSON controlado.
* Indica error `legacy_sync_endpoint_disabled`.
* Indica que el endpoint legacy de sincronizacion fue deshabilitado por seguridad multi-hotel.
* Indica `replacement: /api/sync`.
* No ejecuta autoload.
* No inicia sesion.
* No conecta a base de datos.
* No llama `Sync::procesarLote()`.
* No ejecuta consultas.
* No ejecuta escrituras.
* No se borro el archivo.

## 3. Archivo modificado

* `src/api/sync.php`

## 4. Que NO se toco

* `Sync.php`.
* `ApiController.php`.
* `/api/sync` moderno.
* IndexedDB/offline data.
* `pwa.js`.
* `offline-data.js`.
* `reservaciones-offline.js`.
* `habitaciones-offline.js`.
* `caja-offline.js`.
* `service-worker.js`.
* manifest/branding/White Label.
* Migraciones.
* Schema.
* Base de datos.

## 5. Validaciones realizadas

* `php -l src/api/sync.php`: PASS.
* `git diff --check`: PASS.
* `verificar_estado.php`: bloqueado por Docker Desktop/engine no disponible.
* `preflight_hotel_id.php`: bloqueado por Docker Desktop/engine no disponible.
* No se hicieron pruebas funcionales de sync.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron `Sync.php`, `ApiController.php`, IndexedDB/offline data ni White Label.

## 6. Riesgos pendientes

* `Sync.php` sigue pendiente.
* `/api/sync` moderno sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* Colas offline sin namespace por hotel/sesion siguen pendientes.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## 7. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-D-C: IndexedDB/offline data namespaceado o protegido por hotel/sesion, sin tocar todavia `Sync.php` ni `/api/sync` moderno.
