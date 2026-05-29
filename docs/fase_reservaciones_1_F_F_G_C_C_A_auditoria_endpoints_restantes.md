# RESERVACIONES 1-F-F-G-C-C-A: auditoria de endpoints restantes

## 1. Objetivo

Auditar los endpoints restantes fuera del grupo core de ApiController.php para detectar consultas globales, rutas legacy, endpoints declarados sin metodo y APIs que puedan exponer datos entre hoteles.

## 2. Hallazgo principal

* El riesgo principal restante esta en endpoints fuera del grupo core y en PHP legacy/directos.
* Hay tres tipos de pendientes:
  * endpoints con lecturas globales;
  * endpoints declarados pero rotos o sin metodo;
  * archivos src/api/*.php que no parecen expuestos por Docker actual porque el DocumentRoot es public_html, pero siguen siendo riesgo si otro despliegue los sirve directamente.

## 3. Archivos encontrados

* src/config/routes.php
* src/app/controllers/ApiController.php
* src/app/controllers/ReservacionController.php
* src/app/controllers/DashboardController.php
* src/app/services/InventarioService.php
* src/api/buscar.php
* src/api/reservaciones/hoy.php
* src/api/habitaciones/todas_con_ocupacion.php
* src/api/sync.php

## 4. Endpoints auditados

* /api/reservaciones/{id}/habitaciones
* /api/reservaciones/verificar-checkin/{id}
* /api/dashboard/stats
* /api/dashboard/ocupacion
* /api/dashboard/movimientos-recientes
* /api/dashboard/alertas
* /api/inventario/preview-checkin/{id}
* /api/inventario/alertas
* /api/inventario/verificar-stock/{id}
* src/api/buscar.php
* src/api/reservaciones/hoy.php
* src/api/habitaciones/todas_con_ocupacion.php
* src/api/sync.php

## 5. Riesgos altos

* /api/reservaciones/{id}/habitaciones:
  Usa Reservacion::find($id) global; despues getHabitaciones() ya esta scoped, pero la entrada sigue global.
* /api/inventario/preview-checkin/{id}:
  Primera consulta global por reservacion_id; ademas llama metodo no encontrado obtenerProductosCheckIn().
* /api/inventario/alertas:
  Consulta alertas_inventario/productos sin hotel_id.
* src/api/buscar.php:
  PHP directo legacy global en huespedes, reservaciones y habitaciones si queda expuesto.
* src/api/reservaciones/hoy.php:
  PHP directo legacy global en reservaciones/habitaciones si queda expuesto.
* src/api/habitaciones/todas_con_ocupacion.php:
  PHP directo legacy global en habitaciones, reservaciones y mantenimientos si queda expuesto.
* src/api/sync.php:
  PHP directo legacy de escritura via Sync; queda fuera de esta fase, pero es alto riesgo.

## 6. Riesgos medios

* /api/reservaciones/verificar-checkin/{id}:
  verificarEstadoCheckIn($id) ya filtra id + hotel_id, riesgo bajo/medio por confirmar flujo.
* /api/dashboard/stats:
  Llama por reflexion a DashboardController::getEstadisticas, pero no se encontro ese metodo.
* /api/dashboard/ocupacion:
  Ruta declarada sin metodo encontrado.
* /api/dashboard/movimientos-recientes:
  Ruta declarada sin metodo encontrado.
* /api/dashboard/alertas:
  Ruta declarada sin metodo encontrado.
* /api/inventario/verificar-stock/{id}:
  Llama metodo no encontrado verificarDisponibilidadInventario(); el servicio real verificarDisponibilidad() si valida hotel.

## 7. Riesgos bajos

* DashboardController parece tener logica scoped en metodos reales como getEstadisticasCompletas(), getCajaInfo() y getReservacionesHoy().
* InventarioService tiene metodos principales con hotelIdActual(), pero algunos endpoints llaman nombres inconsistentes o hacen lecturas globales antes del servicio.

## 8. Que implementar primero

RESERVACIONES 1-F-F-G-C-C-B:

* ReservacionController APIs scoped:
  * /api/reservaciones/{id}/habitaciones
  * confirmar /api/reservaciones/verificar-checkin/{id}

Motivo:
Son endpoints pequenos, expuestos por ID y relacionados directamente con reservaciones/habitaciones. Conviene cerrar primero la entrada global con Reservacion::find($id).

## 9. Que implementar despues

* 1-F-F-G-C-C-C:
  Dashboard APIs: corregir /api/dashboard/stats y decidir rutas declaradas sin metodo.
* 1-F-F-G-C-C-D:
  Inventario APIs: preview-checkin, alertas, verificar-stock.
* 1-F-F-G-C-C-E:
  Legacy src/api/*.php: confirmar exposicion y cerrar, bloquear o scopear.
* Despues:
  1-F-F-G-D Sync/offline data scoped por hotel_id.

## 10. Que dejar fuera

* Sync.php.
* IndexedDB/offline data.
* service-worker.js, ya cerrado.
* WHITE LABEL / BRANDING MULTI-HOTEL.
* migraciones/schema.
* cambios de datos.

## 11. Pruebas necesarias

* php -l en PHP tocados.
* git diff --check.
* verificar_estado.php PASS si Docker esta disponible.
* preflight_hotel_id.php PASS si Docker esta disponible.
* GET sin sesion y con sesion por hotel.
* Confirmar 0 nuevos hotel_id NULL.
* Confirmar que White Label no se toco.

## 12. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-B: implementar unicamente ReservacionController APIs scoped:

* /api/reservaciones/{id}/habitaciones
* confirmar /api/reservaciones/verificar-checkin/{id}

Sin tocar todavia dashboard, inventario, src/api/*.php, Sync ni IndexedDB.
