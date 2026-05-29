# RESERVACIONES 1-F-F-G-C-A - Auditoria de endpoints/API

## 1. Objetivo

Auditar endpoints/API para detectar consultas o escrituras globales sin hotel_id y preparar una correccion segura por fases.

## 2. Hallazgo principal

* El riesgo mas alto esta en src/app/controllers/ApiController.php y en los PHP directos src/api/*.php.
* Varias consultas sobre reservaciones, reservacion_habitaciones, habitaciones, mantenimientos_habitaciones, huespedes, inventario legacy y Sync no filtran por hotel_id.
* /api/dashboard/stats parece mejor encaminado porque delega a DashboardController::getEstadisticas(), que ya usa hotel_id.
* /api/dashboard/ocupacion, /api/dashboard/movimientos-recientes y /api/dashboard/alertas estan declarados en rutas, pero no se encontraron metodos correspondientes en ApiController.
* Sync queda fuera de esta fase.

## 3. Archivos encontrados

* src/config/routes.php
* src/app/controllers/ApiController.php
* src/app/controllers/ReservacionController.php
* src/app/models/Habitacion.php
* src/app/controllers/DashboardController.php
* src/app/services/InventarioService.php
* src/api/buscar.php
* src/api/reservaciones/hoy.php
* src/api/habitaciones/todas_con_ocupacion.php
* src/api/sync.php

## 4. Endpoints auditados

* /api/buscar
* /api/reservaciones/hoy
* /api/habitaciones/todas-con-ocupacion
* /api/sync
* /api/huespedes/search
* /api/habitaciones/verificar-disponibilidad
* /api/habitaciones/calcular-precio
* /api/habitaciones/disponibles
* /api/dashboard/stats
* /api/inventario/preview-checkin/{id}
* /api/inventario/alertas
* /api/inventario/verificar-stock/{id}
* /api/reservaciones/{id}/habitaciones
* /api/reservaciones/verificar-checkin/{id}
* /api/dashboard/ocupacion
* /api/dashboard/movimientos-recientes
* /api/dashboard/alertas

## 5. Riesgos altos

* /api/buscar: global en huespedes, reservaciones y habitaciones.
* /api/reservaciones/hoy: global en reservaciones y habitaciones.
* /api/habitaciones/todas-con-ocupacion: global en habitaciones, reservaciones y mantenimiento.
* /api/sync: delega a Sync.php; queda fuera de esta fase pero es alto riesgo.
* /api/habitaciones/verificar-disponibilidad: global por habitacion/reservacion.
* /api/habitaciones/calcular-precio: habitaciones por id IN sin hotel.
* /api/inventario/preview-checkin/{id}: primera lectura por reservacion/habitacion sin hotel.
* /api/reservaciones/{id}/habitaciones: usa Reservacion::find() y getHabitaciones($id); requiere scope.

## 6. Riesgos medios

* /api/huespedes/search: global en huespedes; huesped debe tratarse como auxiliar.
* /api/habitaciones/disponibles: usa Habitacion::disponiblesEntreFechas(), ya scoped, pero tarifa queda global.
* /api/dashboard/stats: delega a DashboardController, requiere confirmar alcance.
* /api/inventario/alertas: usa tablas legacy alertas_inventario/productos sin hotel.
* /api/inventario/verificar-stock/{id}: servicio valida habitacion por hotel, pero confirmar flujo.
* /api/reservaciones/verificar-checkin/{id}: requiere confirmar scope.
* rutas dashboard/ocupacion, movimientos-recientes y alertas declaradas sin metodo encontrado.

## 7. Ya encaminado / menor riesgo

* DashboardController::getEstadisticas() parece usar hotel_id.
* Habitacion.php tiene varias lecturas ya scoped.
* InventarioService tiene metodos principales que validan hotel/habitacion.
* Assets/PWA/Service worker ya quedaron fuera de esta fase.

## 8. Validaciones necesarias

* reservaciones.hotel_id
* reservacion_habitaciones.hotel_id
* habitaciones.hotel_id
* mantenimientos_habitaciones.hotel_id
* movimientos_caja.hotel_id
* cortes_caja.hotel_id
* solicitudes_factura.hotel_id
* inventario.hotel_id si aplica
* huespedes y usuarios como auxiliares, no como fuente de scope.

## 9. Que implementar primero

RESERVACIONES 1-F-F-G-C-B:

* endpoints de busqueda, reservaciones y habitaciones scoped:

  * /api/buscar
  * /api/reservaciones/hoy
  * /api/habitaciones/todas-con-ocupacion
  * /api/habitaciones/verificar-disponibilidad
  * /api/habitaciones/calcular-precio
  * /api/reservaciones/{id}/habitaciones

## 10. Que implementar despues

* 1-F-F-G-C-C: endpoints dashboard/reportes scoped y rutas declaradas sin metodo.
* 1-F-F-G-C-D: endpoints inventario/caja/facturacion scoped si aplica.
* 1-F-F-G-D: Sync/offline data scoped por hotel_id.
* 1-F-F-G-E: cierre general de PWA/Sync/APIs.

## 11. Que dejar fuera

* Sync.php.
* IndexedDB/offline data.
* service-worker.js, ya cerrado.
* WHITE LABEL / BRANDING MULTI-HOTEL.
* migraciones/schema.
* cambios de datos.

## 12. Pruebas necesarias

* php -l en PHP tocados.
* git diff --check.
* verificar_estado.php PASS.
* preflight_hotel_id.php PASS.
* pruebas GET sin sesion y con sesion por hotel.
* confirmar 0 nuevos hotel_id NULL.
* confirmar que no se toco WHITE LABEL / BRANDING MULTI-HOTEL.

## 13. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-B: implementacion por grupo de endpoints busqueda/reservaciones/habitaciones scoped por hotel_id, sin tocar Sync ni IndexedDB todavia.
