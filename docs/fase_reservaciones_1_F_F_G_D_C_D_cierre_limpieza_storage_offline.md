# RESERVACIONES 1-F-F-G-D-C-D-CIERRE

## Objetivo

Documentar el cierre tecnico de la limpieza de IndexedDB/localStorage/sessionStorage en logout o cambio de hotel/sesion.

## Que se logro

* Se agrego tracking de scope local con localStorage.
* Se detecta cambio de `storage_scope` usando `window.MEDISOFT_CONTEXT`.
* Se compara `window.MEDISOFT_CONTEXT.storage_scope` saneado contra `localStorage['loscedros_offline_storage_scope']`.
* Se limpia DB legacy/anterior de forma best-effort.
* Se limpia storage en logout.
* Se protege la DB actual para no borrarla durante cambio de contexto.
* La DB actual solo se borra en logout.
* Se limpia DB anterior registrada.
* Se limpia DB legacy `loscedros-db`.
* Se limpian caches `loscedros-*`.
* Se limpian localStorage keys:
  * `loscedros_offline_storage_scope`
  * `loscedros_offline_db_name`.
* Se limpian sessionStorage keys:
  * `loscedros_sw_controller_reload`
  * `sw_cache_list`.
* `deleteIndexedDBByName(..., { preserveCurrent: true })` evita borrar la DB actual durante cambio de scope.
* No se hizo limpieza profunda de datos desconocidos fuera de claves/DB/caches conocidos.

## Archivo modificado

* `src/public_html/js/pwa.js`

## Que NO se toco

* `Sync.php`.
* `/api/sync` moderno.
* `src/api/sync.php`.
* `ApiController.php`.
* `offline-data.js`.
* `service-worker.js`.
* `manifest/branding/White Label`.
* Migraciones.
* Schema.
* Base de datos.
* Logica profunda offline.

## Validaciones realizadas

* `node --check src/public_html/js/pwa.js`: PASS.
* `node --check src/public_html/js/offline-data.js`: PASS.
* `git diff --check`: PASS.
* `verificar_estado.php`: bloqueado por Docker Desktop/engine no disponible.
* `preflight_hotel_id.php`: bloqueado por Docker Desktop/engine no disponible.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron `Sync.php`, `/api/sync`, `ApiController.php` ni White Label.

## Riesgos pendientes

* `/api/sync` moderno sigue pendiente.
* `Sync.php` sigue pendiente.
* Operaciones offline profundas siguen pendientes.
* Validacion servidor-side de `hotel_id` para sync sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## Siguiente fase recomendada

RESERVACIONES 1-F-F-G-D-D-A: auditoria especifica de `/api/sync` moderno y `Sync::procesarLote()`, sin implementacion directa.
