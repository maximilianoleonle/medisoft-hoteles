# Reservaciones 1-F-F-E-E-E-F-B-CIERRE

## Objetivo

Documentar el cierre técnico del scope defensivo por hotel_id en la entrada/controller del flujo de modificar días.

## Qué se logró

* verificarModificarDiasAction() ahora resuelve el hotel actual con obtenerHotelIdActualCompat().
* verificarModificarDiasAction() carga la reservación con obtenerPorId().
* verificarModificarDiasAction() valida que la reservación exista y pertenezca al hotel actual antes de verificar disponibilidad o recalcular precio.
* modificarDiasAction() ahora resuelve el hotel actual con obtenerHotelIdActualCompat().
* modificarDiasAction() carga la reservación con obtenerPorId().
* modificarDiasAction() valida que la reservación exista y pertenezca al hotel actual antes de modificar fechas, precio o caja.
* Se agregó validación defensiva de que el corte abierto pertenezca al hotel actual.
* Se conserva la lógica original de fechas, disponibilidad y cálculo.

## Archivo modificado

* src/app/controllers/ReservacionController.php

## Qué NO se tocó

* Reservacion::obtenerDatosBasicos().
* Reservacion::obtenerHabitacionIds().
* Reservacion::modificarFechaSalida().
* movimientos_caja.
* reservacion_pagos.
* IncrementoTarifa.
* lógica de tarifas.
* PWA/offline.
* Sync.
* APIs globales.
* migraciones.
* schema.
* base de datos.

## Validaciones realizadas

* php -l en ReservacionController.php.
* git diff --check.
* verificar_estado.php PASS.
* preflight_hotel_id.php PASS.
* No se hizo modificación funcional de días porque escribiría en base de datos sin rollback seguro autorizado.
* Confirmación de que no se tocaron modelos, movimientos_caja, PWA/Sync/APIs.
* 0 nuevos hotel_id NULL según herramientas.

## Riesgos pendientes

* Reservacion::obtenerDatosBasicos() sigue pendiente.
* Reservacion::obtenerHabitacionIds() sigue pendiente.
* Reservacion::modificarFechaSalida() sigue pendiente.
* Ajustes de movimientos_caja derivados de modificar días siguen pendientes.
* Tarifas/globales en IncrementoTarifa quedan fuera de esta fase.
* PWA/offline, Sync y APIs siguen fuera de scope.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-F-C: scope de métodos del modelo relacionados con modificar días:

* obtenerDatosBasicos()
* obtenerHabitacionIds()
* modificarFechaSalida()

Sin tocar todavía movimientos_caja.
