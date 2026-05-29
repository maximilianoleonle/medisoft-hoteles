# RESERVACIONES 1-F-F-G-D-C-B-CIERRE

## Objetivo

Documentar el cierre tecnico de la exposicion de contexto frontend de hotel/sesion para separar almacenamiento offline local en fases posteriores.

## Que se logro

* Se agrego `window.MEDISOFT_CONTEXT` en `header.php`.
* Se conservaron `window.BASE_URL` y `window.API_URL`.
* `window.USUARIO_ID` se conserva derivandolo del nuevo contexto.
* Se expone `hotel_id`.
* Se expone `usuario_id`.
* Se expone `hotel_scope`.
* Se expone `storage_scope`.
* Se expone `storage_version`.
* Se expone `generated_at`.
* El `hotel_id` se obtiene del servidor usando `obtenerHotelIdActualCompat()`.
* `obtenerHotelIdActualCompat()` prioriza `TenantContext::hotelId()`.
* El `hotel_id` expuesto en frontend se usara solo para separar storage local.
* La autorizacion real sigue dependiendo del servidor.

## Archivo modificado

* `src/app/views/layout/header.php`

## Que NO se toco

* `Sync.php`.
* `/api/sync` moderno.
* `src/api/sync.php`.
* IndexedDB/offline data.
* `pwa.js`.
* `offline-data.js`.
* `reservaciones-offline.js`.
* `habitaciones-offline.js`.
* `caja-offline.js`.
* `service-worker.js`.
* `manifest/branding/White Label`.
* Migraciones.
* Schema.
* Base de datos.

## Validaciones realizadas

* `php -l src/app/views/layout/header.php`: PASS.
* `git diff --check`: PASS.
* `verificar_estado.php`: bloqueado por Docker Desktop/engine no disponible.
* `preflight_hotel_id.php`: bloqueado por Docker Desktop/engine no disponible.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron Sync, IndexedDB/offline data ni White Label.

## Riesgos pendientes

* IndexedDB todavia debe namespacearse o protegerse por hotel/sesion.
* Limpieza de IndexedDB/localStorage/sessionStorage en logout o cambio de hotel sigue pendiente.
* `/api/sync` moderno sigue pendiente.
* `Sync.php` sigue pendiente.
* Operaciones offline de reservaciones/habitaciones/caja siguen pendientes.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## Siguiente fase recomendada

RESERVACIONES 1-F-F-G-D-C-C: namespace/proteccion de IndexedDB por hotel/sesion usando `window.MEDISOFT_CONTEXT`, sin tocar todavia `/api/sync` moderno ni `Sync.php`.
