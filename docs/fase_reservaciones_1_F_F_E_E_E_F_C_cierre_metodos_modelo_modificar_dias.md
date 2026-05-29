# Reservaciones 1-F-F-E-E-E-F-C-CIERRE: cierre tecnico de metodos del modelo usados por modificar dias

## 1. Objetivo

Documentar el cierre tecnico del scope por hotel_id en los metodos del modelo usados por modificar dias.

## 2. Que se logro

* obtenerDatosBasicos() ahora usa hotelIdActual().
* obtenerDatosBasicos() consulta reservaciones por id + hotel_id.
* obtenerHabitacionIds() ahora usa hotelIdActual().
* obtenerHabitacionIds() consulta reservacion_habitaciones por reservacion_id + hotel_id.
* obtenerHabitacionIds() valida rh.reservacion_id = r.id.
* obtenerHabitacionIds() valida rh.hotel_id = r.hotel_id.
* obtenerHabitacionIds() valida r.hotel_id = ?.
* modificarFechaSalida() ahora usa hotelIdActual().
* modificarFechaSalida() actualiza reservaciones con WHERE id = ? AND hotel_id = ?.
* Se conserva la logica original de fechas, precio y salida.

## 3. Archivo modificado

* src/app/models/Reservacion.php

## 4. Que NO se toco

* movimientos_caja.
* reservacion_pagos.
* IncrementoTarifa.
* logica de tarifas.
* ReservacionController.
* verificarModificarDiasAction().
* modificarDiasAction().
* PWA/offline.
* Sync.
* APIs globales.
* migraciones.
* schema.
* base de datos.

## 5. Validaciones realizadas

* php -l en Reservacion.php.
* git diff --check.
* verificar_estado.php PASS.
* preflight_hotel_id.php PASS.
* No se hizo modificacion funcional de dias porque escribiria en base de datos sin rollback seguro autorizado.
* Confirmacion de que no se tocaron movimientos_caja, IncrementoTarifa, PWA/Sync/APIs.
* 0 nuevos hotel_id NULL segun herramientas.

## 6. Riesgos pendientes

* Ajustes de movimientos_caja derivados de modificar dias siguen pendientes.
* IncrementoTarifa/tarifas globales quedan fuera de esta fase.
* PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-F-D: scope de ajustes en movimientos_caja derivados de modificar dias, incluyendo hotel_id, corte del hotel actual y reservacion del mismo hotel.
