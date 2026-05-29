# RESERVACIONES 1-F-F-G-D-C-A - Auditoria de namespace/proteccion IndexedDB offline

## 1. Objetivo

Auditar como proteger IndexedDB/offline data para evitar que snapshots, busquedas, colas offline y operaciones pendientes se mezclen entre hoteles o sesiones en el mismo navegador.

## 2. Hallazgo principal

* IndexedDB usa una base global llamada `loscedros-db`.
* No esta separada por hotel.
* No esta separada por sesion.
* Snapshots, busquedas y colas offline pueden mezclarse si el mismo navegador cambia de hotel o sesion.
* No se encontro una variable JS confiable con el hotel actual.
* El frontend solo expone `BASE_URL`, `API_URL`, `csrf-token` y `USUARIO_ID`.
* `hotelIdActual()` y `TenantContext` existen del lado servidor, pero no estan expuestos al frontend.
* El `hotel_id` del cliente solo debe servir para separar almacenamiento local, nunca para autorizar datos.

## 3. Archivos revisados

* `src/public_html/js/pwa.js`
* `src/public_html/js/offline-data.js`
* `src/public_html/js/reservaciones-offline.js`
* `src/public_html/js/habitaciones-offline.js`
* `src/public_html/js/caja-offline.js`
* `src/app/views/layout/header.php`
* `src/app/views/layout/sidebar.php`
* `src/app/helpers/auth.php`
* `src/app/controllers/AuthController.php`

## 4. Object stores encontrados

* `offline_queue`
* `habitaciones`
* `reservaciones`
* `huespedes`
* `busqueda_global`
* `reservaciones_busqueda`
* `meta`
* `operaciones_offline`

## 5. Riesgos por store

* `offline_queue`: medio; cola generica sin namespace.
* `habitaciones`: medio/alto; snapshot de habitaciones sin namespace.
* `reservaciones`: alto; snapshot y reservas locales sin namespace.
* `huespedes`: medio; busqueda/snapshot de huespedes sin namespace.
* `busqueda_global`: medio/alto; indice offline global.
* `reservaciones_busqueda`: alto; reservaciones para busqueda offline.
* `meta`: medio; timestamps de sync sin scope.
* `operaciones_offline`: alto; cola tipada de checkin, checkout, caja y reservas sin hotel.

## 6. Contexto hotel/sesion encontrado

* `window.USUARIO_ID` existe, pero representa usuario, no hotel.
* `window.BASE_URL` y `window.API_URL` existen, pero son rutas, no scope.
* `meta csrf-token` existe, pero no identifica hotel.
* `window.APP_CONFIG` no fue encontrado.
* `window.hotelId` no fue encontrado.
* No se encontro una fuente frontend clara de hotel actual.
* Logout servidor destruye sesion, pero no limpia storage del navegador por si mismo.
* `pwa.js` intenta borrar IndexedDB/caches en submit de logout de forma best-effort.
* No se encontro limpieza especifica por cambio de hotel/sesion.

## 7. Riesgos por nivel

### Alto

* `operaciones_offline` compartida entre hoteles/sesiones.
* Envio a `/api/sync` antes de que servidor/Sync esten scoped.
* Reservaciones, habitaciones y caja offline por IDs sin contexto local de hotel.

### Medio

* Snapshots offline globales.
* Busqueda global offline.
* `offline_queue` generica.
* `localStorage`/`sessionStorage` compartidos para UI/SW sin scope por hotel.

### Bajo

* Assets estaticos.
* Service worker para este punto, porque no cachea API y solo dispara `PROCESS_QUEUE`.

## 8. Recomendacion tecnica

* Primero exponer o confirmar un contexto local seguro de hotel/sesion desde servidor.
* Ese contexto debe usarse solo para separar almacenamiento local.
* El servidor debe seguir derivando `hotel_id` para autorizacion.
* Namespacear IndexedDB por hotel/sesion, por ejemplo `loscedros-db-hotel-{id}`.
* Limpiar IndexedDB/caches/colas al logout y al cambio de hotel/sesion.
* Bloquear envio a `/api/sync` hasta que `/api/sync` y `Sync::procesarLote()` esten scoped por servidor.
* Mantener offline solo lectura si no hay contexto seguro.

## 9. Que NO implementar todavia

* `/api/sync` moderno.
* `Sync.php`.
* Operaciones offline profundas.
* WHITE LABEL / BRANDING MULTI-HOTEL.
* Logos.
* Colores.
* Manifest dinamico.
* Migraciones/schema.
* Cambios de datos.

## 10. Fases propuestas

* `1-F-F-G-D-C-B`: exponer o confirmar contexto seguro de hotel/sesion para frontend.
* `1-F-F-G-D-C-C`: namespace/proteccion de IndexedDB por hotel/sesion.
* `1-F-F-G-D-C-D`: limpieza de IndexedDB/localStorage/sessionStorage en logout o cambio de hotel.
* `1-F-F-G-D-D`: `/api/sync` moderno y `Sync::procesarLote()` scoped por `hotel_id`.
* `1-F-F-G-D-E`: operaciones offline de reservaciones/habitaciones/caja scoped.
* `1-F-F-G-E`: cierre general de PWA/Sync/APIs.
* Despues de eso, si todo queda seguro, abrir WHITE LABEL / BRANDING MULTI-HOTEL.

## 11. Pruebas necesarias futuras

* `node --check` en JS tocados.
* `php -l` en PHP tocados si aplica.
* `git diff --check`.
* `verificar_estado.php` si Docker esta disponible.
* `preflight_hotel_id.php` si Docker esta disponible.
* Prueba navegador con hotel A y luego hotel B.
* Confirmar que no se mezclan snapshots ni colas.
* Confirmar que no se envian operaciones offline sin contexto seguro.
* Confirmar que White Label no se toco.

## 12. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-D-C-B: exponer o confirmar contexto seguro de hotel/sesion para frontend, sin tocar todavia `/api/sync` moderno ni `Sync.php`.
