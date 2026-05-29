# RESERVACIONES 1-F-F-G-C-E-A - Auditoria de src/api legacy/directos

## 1. Objetivo

Auditar los archivos PHP directos/legacy bajo `src/api` para determinar si estan expuestos, si duplican endpoints ya cerrados, si consultan o escriben datos sin `hotel_id`, y decidir si conviene bloquearlos, eliminarlos del flujo, documentarlos como legacy o scopearlos.

## 2. Hallazgo principal

* `src/api/*.php` son endpoints PHP directos/legacy fuera del DocumentRoot actual de Docker.
* Apache apunta a `src/public_html`.
* En el despliegue actual no deberian servirse directamente.
* Siguen siendo riesgo latente si otro entorno expone `src` o aliasa `/api`.
* Los tres endpoints de lectura duplican endpoints modernos ya cerrados en `ApiController`.
* Los endpoints legacy de lectura siguen globales sin `hotel_id`.
* `src/api/sync.php` es de escritura y debe quedar fuera hasta la fase Sync.

## 3. Archivos auditados

* `src/api/buscar.php`
* `src/api/reservaciones/hoy.php`
* `src/api/habitaciones/todas_con_ocupacion.php`
* `src/api/sync.php`

## 4. Estado por archivo

### src/api/buscar.php

* Tipo: lectura.
* Usa `huespedes`, `reservaciones`, `reservacion_habitaciones`, `habitaciones`.
* Global sin `hotel_id`.
* Duplica `/api/buscar`.
* Riesgo alto si queda expuesto.

### src/api/reservaciones/hoy.php

* Tipo: lectura.
* Usa `reservaciones`, `huespedes`, `reservacion_habitaciones`, `habitaciones`.
* Global sin `hotel_id`.
* Duplica `/api/reservaciones/hoy`.
* Riesgo alto si queda expuesto.

### src/api/habitaciones/todas_con_ocupacion.php

* Tipo: lectura.
* Usa `habitaciones`, `reservaciones`, `reservacion_habitaciones`, `huespedes`, `mantenimientos_habitaciones`.
* Global sin `hotel_id`.
* Duplica `/api/habitaciones/todas-con-ocupacion`.
* Riesgo alto si queda expuesto.

### src/api/sync.php

* Tipo: escritura.
* Delega a `Sync::procesarLote()`.
* Alto riesgo.
* Debe quedar fuera y decidirse junto con `Sync.php` e IndexedDB/offline data.

## 5. Exposicion

* DocumentRoot actual: `src/public_html`.
* `src/api` no queda servido directamente en Docker actual.
* No se encontraron referencias JS/PWA/frontend a `.php` directos.
* Las referencias activas usan rutas modernas `/api/...`.
* Riesgo alto si otro despliegue expone `src/api`.

## 6. Recomendacion por archivo

* `src/api/buscar.php`: bloquear o documentar como legacy; no scopear si ya duplica `ApiController`.
* `src/api/reservaciones/hoy.php`: bloquear o documentar como legacy.
* `src/api/habitaciones/todas_con_ocupacion.php`: bloquear o documentar como legacy.
* `src/api/sync.php`: dejar fuera; decidir junto con `Sync.php` e IndexedDB/offline data.

## 7. Fases propuestas

* `1-F-F-G-C-E-B`: bloquear o documentar `src/api/*.php` legacy de lectura.
* `1-F-F-G-C-E-C`: decidir `src/api/sync.php` junto con `Sync.php`.
* `1-F-F-G-D-A`: auditoria de `Sync.php` e IndexedDB/offline data.
* `1-F-F-G-E`: cierre general de PWA/Sync/APIs.

## 8. Que dejar fuera

* `Sync.php` profundo.
* IndexedDB/offline data.
* `service-worker.js`, ya cerrado.
* `ApiController.php` funcional, ya cerrado por fases.
* WHITE LABEL / BRANDING MULTI-HOTEL.
* Migraciones/schema.
* Cambios de datos.

## 9. Pruebas necesarias futuras

* `php -l` en PHP tocados.
* `git diff --check`.
* `verificar_estado.php` si Docker esta disponible.
* `preflight_hotel_id.php` si Docker esta disponible.
* Pruebas GET directas solo si hay servidor activo.
* Confirmar si `src/api` esta expuesto o no.
* Confirmar que White Label no se toco.

## 10. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-E-B: bloquear o documentar los endpoints legacy de lectura:

* `src/api/buscar.php`
* `src/api/reservaciones/hoy.php`
* `src/api/habitaciones/todas_con_ocupacion.php`

Sin tocar todavia:

* `src/api/sync.php`
* `Sync.php`
* IndexedDB/offline data
* WHITE LABEL / BRANDING MULTI-HOTEL
