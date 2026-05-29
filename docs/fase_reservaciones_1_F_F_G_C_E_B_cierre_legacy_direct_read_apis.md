# RESERVACIONES 1-F-F-G-C-E-B-CIERRE - Cierre tecnico de endpoints legacy directos de lectura

## 1. Objetivo

Documentar el cierre tecnico del bloqueo de endpoints PHP directos/legacy de lectura bajo `src/api`.

## 2. Que se logro

* `src/api/buscar.php` quedo deshabilitado con HTTP 410 Gone.
* `src/api/reservaciones/hoy.php` quedo deshabilitado con HTTP 410 Gone.
* `src/api/habitaciones/todas_con_ocupacion.php` quedo deshabilitado con HTTP 410 Gone.
* Los tres responden JSON controlado.
* Los tres indican error `legacy_endpoint_disabled`.
* Los tres indican mensaje de endpoint legacy deshabilitado por seguridad multi-hotel.
* Los tres indican el endpoint moderno correspondiente.
* No ejecutan autoload.
* No inician sesion.
* No abren conexion a base de datos.
* No hacen consultas.
* No hacen escrituras.
* No se borraron archivos.

## 3. Reemplazos modernos

* `src/api/buscar.php` -> `/api/buscar`
* `src/api/reservaciones/hoy.php` -> `/api/reservaciones/hoy`
* `src/api/habitaciones/todas_con_ocupacion.php` -> `/api/habitaciones/todas-con-ocupacion`

## 4. Archivos modificados

* `src/api/buscar.php`
* `src/api/reservaciones/hoy.php`
* `src/api/habitaciones/todas_con_ocupacion.php`

## 5. Que NO se toco

* `src/api/sync.php`.
* `Sync.php`.
* IndexedDB/offline data.
* `ApiController.php`.
* `service-worker.js`.
* manifest/branding/White Label.
* Migraciones.
* Schema.
* Base de datos.

## 6. Validaciones realizadas

* `php -l src/api/buscar.php`: PASS.
* `php -l src/api/reservaciones/hoy.php`: PASS.
* `php -l src/api/habitaciones/todas_con_ocupacion.php`: PASS.
* `git diff --check`: PASS.
* `verificar_estado.php`: bloqueado por Docker Desktop/engine no disponible.
* `preflight_hotel_id.php`: bloqueado por Docker Desktop/engine no disponible.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se toco `src/api/sync.php`, `Sync.php`, IndexedDB/offline data ni White Label.

## 7. Riesgos pendientes

* `src/api/sync.php` sigue pendiente.
* `Sync.php` sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance hasta cerrar seguridad multi-hotel.

## 8. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-D-A: auditoria de `Sync.php`, `src/api/sync.php` e IndexedDB/offline data, sin implementacion directa.
