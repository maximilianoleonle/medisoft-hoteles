# RESERVACIONES 1-F-F-G-D-A - Auditoria de Sync, IndexedDB y offline data

## 1. Objetivo

Auditar el flujo de sincronizacion offline para detectar escrituras o lecturas sin `hotel_id`, datos en IndexedDB sin namespace por hotel, colas offline mezclables entre hoteles y operaciones Sync que puedan crear, actualizar o enviar datos a otro hotel.

## 2. Hallazgo principal

* El riesgo principal esta en `Sync.php` y en IndexedDB/offline data.
* `/api/sync` moderno entra por `ApiController::syncAction()`.
* `ApiController::syncAction()` no usa `hotelIdActual()`.
* `ApiController::syncAction()` no pasa contexto de hotel a `Sync::procesarLote()`.
* `Sync.php` procesa operaciones por IDs globales.
* `Sync.php` tiene lecturas/escrituras sin `hotel_id`.
* `src/api/sync.php` esta fuera del DocumentRoot actual, pero si otro entorno expone `src/api` seria un endpoint directo de escritura de alto riesgo.

## 3. Archivos encontrados

* `src/app/models/Sync.php`
* `src/api/sync.php`
* `src/app/controllers/ApiController.php`
* `src/public_html/js/offline-data.js`
* `src/public_html/js/pwa.js`
* `src/public_html/js/reservaciones-offline.js`
* `src/public_html/js/habitaciones-offline.js`
* `src/public_html/js/caja-offline.js`
* `src/public_html/service-worker.js`

## 4. IndexedDB / offline data

* La base IndexedDB se llama `loscedros-db`.
* No esta separada por hotel.
* No esta separada por sesion.
* Los object stores encontrados son:

  * `offline_queue`
  * `habitaciones`
  * `reservaciones`
  * `huespedes`
  * `busqueda_global`
  * `reservaciones_busqueda`
  * `meta`
  * `operaciones_offline`

* Ninguno esta namespaceado por hotel.
* `operaciones_offline` guarda cola tipada de checkin, checkout, caja y otras operaciones sin `hotel_id`.

## 5. Operaciones Sync auditadas

* `crear_reservacion`.
* `cambiar_estado_habitacion`.
* `checkin`.
* `checkout`.
* `pago_caja`.
* `gasto_caja`.

## 6. Riesgos altos

* `Sync.php` completo.
* `/api/sync` moderno sin hotel.
* `src/api/sync.php` si queda expuesto.
* `operaciones_offline` sin namespace.
* Operaciones de reservacion/habitacion/caja por IDs globales.
* `INSERT`/`UPDATE` en `reservaciones`, `reservacion_habitaciones`, `habitaciones` y `movimientos_caja` sin `hotel_id` seguro.

## 7. Riesgos medios

* `offline_queue` generica de `pwa.js`.
* Snapshots offline sin namespace por hotel.
* Cambio de hotel/sesion en el mismo navegador sin limpieza garantizada.

## 8. Riesgos bajos

* `service-worker.js` para este flujo, porque no cachea API y solo notifica a clientes.

## 9. Puntos criticos

* `Sync::__construct()` llama `asegurarTablaOperacionesSync()`.
* `asegurarTablaOperacionesSync()` ejecuta `CREATE TABLE IF NOT EXISTS operaciones_sync` si se usa el flujo.
* `operaciones_sync` no incluye `hotel_id`.
* `ApiController::syncAction()` no usa `hotelIdActual()`.
* Las operaciones offline no incluyen `hotel_id`.
* Aunque el cliente enviara `hotel_id`, no se debe confiar en el cliente; el servidor debe derivar `hotel_id` desde sesion/hotel actual.
* `usuarios` y `huespedes` son auxiliares, no fuente de scope.

## 10. Validaciones necesarias

* `reservaciones.hotel_id = ?`
* `reservacion_habitaciones.hotel_id = reservaciones.hotel_id`
* `habitaciones.hotel_id = reservacion_habitaciones.hotel_id`
* `movimientos_caja.hotel_id = ?`
* `reservacion_pagos.hotel_id = ?`
* `cortes_caja.hotel_id = movimientos_caja.hotel_id`
* `cajas.hotel_id = cortes_caja.hotel_id`
* `usuarios` como auxiliares, no como fuente de scope
* `huespedes` como auxiliares, no como fuente de scope

## 11. Recomendacion tecnica

* Bloquear o proteger primero `src/api/sync.php` legacy directo si no debe servirse.
* Namespacear IndexedDB por hotel/sesion o limpiar al cambiar de hotel/sesion.
* Mantener `/api/sync` activo solo cuando `ApiController` derive `hotel_id` del servidor y lo pase a `Sync`.
* Cambiar `Sync::procesarLote()` para exigir contexto de hotel del servidor.
* Scopear cada operacion por `hotel_id`.
* Desactivar temporalmente Sync si no se puede asegurar scope antes de uso real multi-hotel.

## 12. Fases propuestas

* `1-F-F-G-D-B`: bloquear/proteger `src/api/sync.php` legacy directo.
* `1-F-F-G-D-C`: IndexedDB/offline data namespaceado o protegido por hotel/sesion.
* `1-F-F-G-D-D`: `/api/sync` y `Sync::procesarLote()` scoped por `hotel_id`.
* `1-F-F-G-D-E`: operaciones offline de reservaciones/habitaciones/caja scoped.
* `1-F-F-G-E`: cierre general de PWA/Sync/APIs.
* Despues de eso, si todo queda seguro, abrir WHITE LABEL / BRANDING MULTI-HOTEL.

## 13. Que dejar fuera

* WHITE LABEL / BRANDING MULTI-HOTEL.
* Logos.
* Colores.
* Manifest dinamico.
* Service worker funcional, ya cerrado.
* Endpoints `ApiController` ya cerrados salvo referencia.
* Migraciones/schema.
* Cambios de datos.
* Implementacion funcional.

## 14. Pruebas necesarias futuras

* `php -l` en PHP tocados.
* `node --check` en JS tocados.
* `git diff --check`.
* `verificar_estado.php` si Docker esta disponible.
* `preflight_hotel_id.php` si Docker esta disponible.
* Pruebas IndexedDB en navegador.
* Pruebas offline con hotel A y luego hotel B.
* Confirmar que no se mezclan colas offline.
* Confirmar 0 nuevos `hotel_id` NULL.
* Confirmar que White Label no se toco.

## 15. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-D-B: bloquear/proteger `src/api/sync.php` legacy directo, sin tocar todavia `Sync.php` ni IndexedDB/offline data.
