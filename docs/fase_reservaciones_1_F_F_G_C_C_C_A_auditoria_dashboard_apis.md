# RESERVACIONES 1-F-F-G-C-C-C-A: auditoria de dashboard APIs

## 1. Objetivo

Auditar los endpoints API relacionados con dashboard para detectar rutas declaradas sin metodo, metodos inexistentes, consultas globales sin hotel_id o dependencias que puedan exponer datos entre hoteles.

## 2. Hallazgo principal

* El problema principal de /api/dashboard/* no parece ser una consulta global directa.
* El problema principal es cableado roto o inconsistente.
* Hay rutas declaradas hacia metodos inexistentes.
* /api/dashboard/stats llama por reflexion a DashboardController::getEstadisticas(), pero ese metodo no existe.
* Los metodos reales de DashboardController para estadisticas y caja estan mayormente scoped por hotel_id.

## 3. Archivos encontrados

* src/config/routes.php
* src/app/controllers/ApiController.php
* src/app/controllers/DashboardController.php
* src/app/models/Caja.php
* src/app/models/Reservacion.php

## 4. Endpoints auditados

* /api/dashboard/stats
* /api/dashboard/ocupacion
* /api/dashboard/movimientos-recientes
* /api/dashboard/alertas

## 5. Estado por endpoint

* /api/dashboard/stats:
  * Ruta actual: Api::estadisticasDashboard.
  * Metodo existe en ApiController.
  * Riesgo alto por llamada incorrecta a DashboardController::getEstadisticas(), metodo no encontrado.
* /api/dashboard/ocupacion:
  * Ruta declarada como Api::ocupacionActual.
  * Metodo no encontrado.
  * Riesgo medio.
* /api/dashboard/movimientos-recientes:
  * Ruta declarada como Api::movimientosRecientes.
  * Metodo no encontrado.
  * Riesgo medio.
* /api/dashboard/alertas:
  * Ruta declarada como Api::alertasDashboard.
  * Metodo no encontrado.
  * Riesgo medio.

## 6. Metodos reales scoped detectados

* DashboardController::getEstadisticasCompletas() filtra habitaciones, reservaciones, reservacion_habitaciones y movimientos_caja por hotel_id.
* DashboardController::getCajaInfo() filtra cortes_caja y movimientos_caja por hotel_id.
* DashboardController::getReservacionesHoy() usa hotelIdActual() y joins scoped.
* DashboardController::getProximasLlegadas() usa hotelIdActual() y joins scoped.
* DashboardController::getProximasSalidas() usa hotelIdActual() y joins scoped.
* DashboardController::getDatosGraficos() usa hotelIdActual() y joins scoped.
* Caja::obtenerCorteActual() valida cajas.hotel_id y cortes_caja.hotel_id.

## 7. Riesgos adicionales

* DashboardController::indexAction() llama Reservacion::getCheckInsPendientes() y Reservacion::getCheckOutsPendientes().
* Esos metodos siguen globales en reservaciones, reservacion_habitaciones y habitaciones.
* No pertenecen directamente a /api/dashboard/*, pero son flujo dashboard pendiente.

## 8. Riesgos por nivel

Alto:

* /api/dashboard/stats, por llamar metodo inexistente o incorrecto mediante reflexion.

Medio:

* /api/dashboard/ocupacion, ruta declarada sin metodo encontrado.
* /api/dashboard/movimientos-recientes, ruta declarada sin metodo encontrado.
* /api/dashboard/alertas, ruta declarada sin metodo encontrado.
* DashboardController::indexAction() por metodos colaterales getCheckInsPendientes()/getCheckOutsPendientes() pendientes de scope.

Bajo:

* Metodos reales de DashboardController que ya usan hotelIdActual().
* Caja::obtenerCorteActual(), ya scoped.

## 9. Que implementar primero

RESERVACIONES 1-F-F-G-C-C-C-B:

* Corregir /api/dashboard/stats.

Recomendacion tecnica:

* Evitar reflexion hacia DashboardController::getEstadisticas(), porque no existe.
* Usar un metodo real scoped, idealmente DashboardController::getEstadisticasCompletas() y/o getCajaInfo().
* Si el metodo requerido es privado, exponer un metodo publico controlado o ajustar ApiController de forma minima sin romper encapsulacion.
* Mantener respuesta JSON original o documentar el cambio si la estructura necesita ajustarse.

## 10. Que implementar despues

* RESERVACIONES 1-F-F-G-C-C-C-C:
  Decidir si implementar o retirar rutas /api/dashboard/ocupacion, /api/dashboard/movimientos-recientes y /api/dashboard/alertas.
* RESERVACIONES 1-F-F-G-C-C-C-D:
  Auditar/corregir metodos dashboard colaterales del modelo Reservacion usados por indexAction().
* Luego continuar con inventario APIs, legacy src/api/*.php, Sync e IndexedDB.

## 11. Que dejar fuera

* Inventario APIs.
* src/api/*.php.
* Sync.php.
* IndexedDB/offline data.
* service-worker.js.
* WHITE LABEL / BRANDING MULTI-HOTEL.
* Migraciones/schema.
* Cambios de datos.

## 12. Pruebas necesarias

* php -l en PHP tocados.
* git diff --check.
* verificar_estado.php PASS si Docker esta disponible.
* preflight_hotel_id.php PASS si Docker esta disponible.
* GET sin sesion y con sesion por hotel.
* Confirmar 0 nuevos hotel_id NULL.
* Confirmar que White Label no se toco.

## 13. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-C-B: implementar unicamente /api/dashboard/stats usando metodo real scoped, sin tocar todavia rutas declaradas sin metodo ni inventario APIs.
