# RESERVACIONES 1-F-F-G-D-C-C-CIERRE

## Objetivo

Documentar el cierre tecnico del namespace/proteccion de IndexedDB por hotel/sesion usando `window.MEDISOFT_CONTEXT`.

## Que se logro

* `pwa.js` ya no usa de forma fija `loscedros-db`.
* `offline-data.js` ya no usa de forma fija `loscedros-db`.
* El nuevo patron de base IndexedDB es `loscedros-db-${storage_scope}`.
* Se usa `window.MEDISOFT_CONTEXT` como fuente local para separar storage.
* Se prioriza `storage_scope`.
* Se usa `hotel_scope` como fallback.
* Se usa `hotel_id` como fallback local.
* El scope se sanea antes de construir el nombre de DB.
* `offline-data.js` reutiliza `window.LosCedrosDB.name` para coincidir con `pwa.js`.
* Si no existe contexto local, no se abre IndexedDB.
* Si no existe contexto local, no se crea cola offline peligrosa.
* Las lecturas sin contexto devuelven arreglos vacios o `null`.
* Las escrituras/encolados sin contexto fallan de forma controlada con warning/toast.
* El `hotel_id` frontend sigue siendo solo para storage local, no para autorizacion.
* La autorizacion real sigue del lado servidor.

## Stores conservados

* `offline_queue`.
* `habitaciones`.
* `reservaciones`.
* `huespedes`.
* `busqueda_global`.
* `reservaciones_busqueda`.
* `meta`.
* `operaciones_offline`.

## Archivos modificados

* `src/public_html/js/pwa.js`
* `src/public_html/js/offline-data.js`

## Que NO se toco

* `Sync.php`.
* `/api/sync` moderno.
* `src/api/sync.php`.
* `ApiController.php`.
* `service-worker.js`.
* `manifest/branding/White Label`.
* Migraciones.
* Schema.
* Base de datos.
* Limpieza profunda de DBs antiguas.
* Logout/cambio de hotel.
* Logica profunda de operaciones offline.

## Validaciones realizadas

* `node --check src/public_html/js/pwa.js`: PASS.
* `node --check src/public_html/js/offline-data.js`: PASS.
* `git diff --check`: PASS.
* `verificar_estado.php`: bloqueado por Docker Desktop/engine no disponible.
* `preflight_hotel_id.php`: bloqueado por Docker Desktop/engine no disponible.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron `Sync.php`, `/api/sync` moderno, `ApiController.php` ni White Label.

## Riesgos pendientes

* Limpieza de IndexedDB/localStorage/sessionStorage sigue pendiente.
* DB global antigua `loscedros-db` puede seguir existiendo en navegadores ya usados.
* Limpieza en logout o cambio de hotel sigue pendiente.
* `/api/sync` moderno sigue pendiente.
* `Sync.php` sigue pendiente.
* Operaciones offline profundas siguen pendientes.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## Siguiente fase recomendada

RESERVACIONES 1-F-F-G-D-C-D: limpieza de IndexedDB/localStorage/sessionStorage en logout o cambio de hotel, sin tocar todavia `Sync.php` ni `/api/sync` moderno.
