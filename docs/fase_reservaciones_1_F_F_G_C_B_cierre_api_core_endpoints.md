# RESERVACIONES 1-F-F-G-C-B-CIERRE: endpoints core API scoped por hotel_id

## 1. Objetivo

Documentar el cierre tecnico del scope por hotel_id en endpoints core de busqueda, reservaciones y habitaciones dentro de ApiController.php.

## 2. Que se logro

* Se agrego helper hotelIdActual().
* /api/buscar quedo scoped por hotel_id.
* /api/reservaciones/hoy quedo scoped por hotel_id.
* /api/habitaciones/todas-con-ocupacion quedo scoped por hotel_id.
* /api/habitaciones/verificar-disponibilidad quedo scoped por hotel_id.
* /api/habitaciones/calcular-precio quedo scoped por hotel_id.
* Se filtra reservaciones.hotel_id.
* Se filtra habitaciones.hotel_id.
* Se valida reservacion_habitaciones.hotel_id = reservaciones.hotel_id.
* Se valida habitaciones.hotel_id = reservacion_habitaciones.hotel_id.
* En busquedas, huespedes quedan como auxiliares, no como fuente de scope.
* Se conserva la logica original de respuesta JSON.

## 3. Archivo modificado

* src/app/controllers/ApiController.php

## 4. Que NO se toco

* Sync.php.
* src/api/*.php.
* IndexedDB/offline data.
* pwa.js.
* offline-data.js.
* reservaciones-offline.js.
* habitaciones-offline.js.
* caja-offline.js.
* service-worker.js.
* manifest/branding/White Label.
* migraciones.
* schema.
* base de datos.

## 5. Validaciones realizadas

* php -l src/app/controllers/ApiController.php: PASS.
* git diff --check: PASS.
* verificar_estado.php: bloqueado por Docker Desktop/engine no disponible.
* preflight_hotel_id.php: bloqueado por Docker Desktop/engine no disponible.
* GET local: bloqueado si no hay servidor activo.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron Sync, IndexedDB/offline data, src/api/*.php ni White Label.

## 6. Riesgos pendientes

* /api/reservaciones/{id}/habitaciones sigue pendiente porque no esta en ApiController.php.
* /api/reservaciones/verificar-checkin/{id} sigue pendiente.
* Endpoints dashboard/reportes siguen pendientes.
* Endpoints inventario/caja/facturacion siguen pendientes.
* src/api/*.php legacy/directos siguen pendientes.
* Sync.php sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* White Label sigue fuera de alcance.

## 7. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-A: auditoria de endpoints restantes fuera del grupo core, incluyendo ReservacionController APIs, dashboard, inventario y rutas declaradas sin metodo encontrado, sin implementacion directa.
