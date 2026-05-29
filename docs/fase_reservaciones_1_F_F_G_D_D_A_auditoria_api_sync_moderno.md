# RESERVACIONES 1-F-F-G-D-D-A

## Objetivo

Auditar `/api/sync` moderno y `Sync::procesarLote()` para definir como cerrar la sincronizacion offline por `hotel_id` derivado del servidor, sin confiar en `hotel_id` enviado por cliente.

## Hallazgo principal

* `/api/sync` moderno sigue siendo alto riesgo multi-hotel.
* La ruta entra por `ApiController::syncAction()`.
* `ApiController::syncAction()` valida POST, CSRF y autenticacion.
* `ApiController::syncAction()` reemplaza `usuario_id` por el usuario del servidor.
* `ApiController::syncAction()` no llama `hotelIdActual()`.
* `ApiController::syncAction()` no pasa `hotel_id` a `Sync::procesarLote()`.
* `Sync.php` procesa operaciones por IDs globales.
* `Sync.php` contiene lecturas y escrituras sin `hotel_id`.
* `Sync::__construct()` ejecuta `asegurarTablaOperacionesSync()`.
* `asegurarTablaOperacionesSync()` ejecuta `CREATE TABLE IF NOT EXISTS operaciones_sync`.
* `operaciones_sync` no tiene `hotel_id`.

## Archivos revisados

* `src/config/routes.php`
* `src/app/controllers/ApiController.php`
* `src/app/models/Sync.php`
* `src/public_html/js/offline-data.js`
* `src/public_html/js/pwa.js`
* `src/public_html/js/reservaciones-offline.js`
* `src/public_html/js/habitaciones-offline.js`
* `src/public_html/js/caja-offline.js`
* `src/app/views/reservaciones/crear.php`

## Flujo actual

* `routes.php` declara `POST /api/sync -> Api::sync`.
* `offline-data.js` envia `fetch(BASE + '/api/sync')` con JSON `{ operaciones: pendientes }`.
* El request incluye CSRF y credenciales same-origin.
* Cada operacion trae `uuid`, `tipo`, `payload`, `timestamp`, `usuario_id`, `estado` y datos relacionados.
* `ApiController::syncAction()` sanitiza el lote.
* `ApiController::syncAction()` reemplaza `usuario_id` por `user_id()`.
* `ApiController::syncAction()` instancia `new Sync()`.
* `ApiController::syncAction()` llama `procesarLote($operaciones_limpias, $usuario_id)`.
* No deriva ni pasa hotel servidor.

## Metodos auditados

* `ApiController::syncAction()`
* `Sync::__construct()`
* `Sync::asegurarTablaOperacionesSync()`
* `Sync::procesarLote(array $operaciones, ?int $usuarioActualId = null)`
* `Sync::validarOperacionAutorizada()`

## Operaciones auditadas

* `crear_reservacion`
* `cambiar_estado_habitacion`
* `checkin`
* `checkout`
* `pago_caja`
* `gasto_caja`
* Idempotencia con `operaciones_sync`

## Consultas criticas globales detectadas

* `SELECT * FROM habitaciones WHERE id IN (...) AND activa = 1`
* `INSERT INTO reservaciones (...)` sin `hotel_id`
* `INSERT INTO reservacion_habitaciones (...)` sin `hotel_id`
* `SELECT id, estado FROM habitaciones WHERE id = ?`
* `UPDATE habitaciones SET estado = ? WHERE id = ?`
* `SELECT id, estado FROM reservaciones WHERE id = ?`
* `UPDATE reservaciones SET estado = ... WHERE id = ?`
* `UPDATE habitaciones ... WHERE rh.reservacion_id = ?` sin validar `rh.hotel_id` ni `h.hotel_id`
* `SELECT id FROM cortes_caja WHERE estado = 'abierto' ORDER BY fecha_apertura DESC LIMIT 1`
* `INSERT INTO movimientos_caja (...)` sin `hotel_id`

## Riesgos por nivel

### Alto

* `/api/sync` activo sin hotel servidor.
* `Sync::procesarLote()` sin contexto hotel.
* Operaciones por IDs globales.
* `operaciones_sync` sin `hotel_id`.
* Caja usa corte abierto global.
* `INSERT`/`UPDATE` en `reservaciones`, `reservacion_habitaciones`, `habitaciones` y `movimientos_caja` sin `hotel_id` seguro.

### Medio

* Frontend ya separa storage local, pero todavia envia operaciones a endpoint servidor no scoped.
* Usuario/permisos si se validan, pero no aislan hotel.

### Bajo

* `pwa.js` y `offline-data.js` para namespace/limpieza local ya estan mas seguros.
* `service-worker.js` queda solo referencial.

## Validaciones necesarias

* El servidor debe derivar `hotel_id` con `hotelIdActual()`, `TenantContext` o sesion.
* No confiar en `hotel_id` del cliente.
* `reservaciones.hotel_id = ?`
* `reservacion_habitaciones.hotel_id = reservaciones.hotel_id`
* `habitaciones.hotel_id = reservacion_habitaciones.hotel_id`
* `movimientos_caja.hotel_id = ?`
* `cortes_caja.hotel_id = movimientos_caja.hotel_id`
* `cajas.hotel_id = cortes_caja.hotel_id`
* `reservacion_pagos.hotel_id = ?`
* Usuarios como auxiliares, no como fuente de scope.
* Huespedes como auxiliares, no como fuente de scope.

## Recomendacion tecnica

* No procesar operaciones reales en `/api/sync` hasta que el servidor derive `hotel_id` y `Sync` lo exija.
* Proteger temporalmente `/api/sync` con respuesta controlada, o pasar `hotel_id` servidor a `Sync` y hacer que `Sync::procesarLote()` rechace operaciones si no recibe contexto hotel valido.
* No confiar en `hotel_id` enviado por cliente.
* No tocar schema por ahora.
* `operaciones_sync` debe quedar fuera del uso profundo o usarse con cautela hasta decidir si se migra con `hotel_id`.
* No tocar WHITE LABEL / BRANDING MULTI-HOTEL.

## Fases propuestas

* 1-F-F-G-D-D-B: proteger `/api/sync` moderno o pasar `hotel_id` servidor a `Sync` de forma controlada.
* 1-F-F-G-D-D-C: cambiar `Sync::procesarLote()` para exigir contexto hotel del servidor.
* 1-F-F-G-D-D-D: scopear operaciones de reservaciones/habitaciones.
* 1-F-F-G-D-D-E: scopear operaciones de caja/pagos.
* 1-F-F-G-D-E: ajustar operaciones offline frontend al sync seguro.
* 1-F-F-G-E: cierre general PWA/Sync/APIs.
* Despues de eso, si todo queda seguro, abrir WHITE LABEL / BRANDING MULTI-HOTEL.

## Que dejar fuera

* WHITE LABEL / BRANDING MULTI-HOTEL.
* Logos por hotel.
* Colores por hotel.
* Manifest dinamico.
* `service-worker.js`, ya cerrado.
* Schema/migraciones.
* Cambios de datos.
* Implementacion funcional.

## Pruebas necesarias futuras

* `php -l` en PHP tocados.
* `node --check` en JS tocados si aplica.
* `git diff --check`.
* `verificar_estado.php` si Docker esta disponible.
* `preflight_hotel_id.php` si Docker esta disponible.
* Pruebas de `/api/sync` sin sesion y con sesion por hotel.
* Pruebas offline con hotel A y hotel B.
* Confirmar 0 nuevos `hotel_id NULL`.
* Confirmar que White Label no se toco.

## Siguiente fase recomendada

RESERVACIONES 1-F-F-G-D-D-B: proteger temporalmente `/api/sync` moderno con respuesta controlada, sin tocar todavia `Sync.php` ni operaciones offline profundas.

Motivo: mientras `Sync::procesarLote()` procese operaciones globales, lo mas seguro es que `/api/sync` no acepte escrituras reales.
