# Reservaciones 1-F-F-E-E-E-F-A

## Objetivo

Auditar modificarDiasAction() para detectar riesgos de scope por hotel_id en cambios de fechas/días de reservación, habitaciones, pagos, movimientos de caja, tarifas y estado de reservación.

## Hallazgo principal

* modificarDiasAction() es un flujo de riesgo alto porque puede modificar fechas, precio, disponibilidad y movimientos de caja.
* verificarModificarDiasAction() también depende de métodos que aún tienen lecturas globales.
* obtenerDatosBasicos() consulta reservaciones con WHERE id = ? sin hotel_id.
* obtenerHabitacionIds() consulta reservacion_habitaciones por reservacion_id sin hotel_id.
* modificarFechaSalida() actualiza reservaciones solo por id.
* El ajuste en movimientos_caja no incluye hotel_id.
* SELECT metodo_pago FROM reservaciones WHERE id = ? es global.

## Archivos encontrados

* src/app/controllers/ReservacionController.php
* src/app/models/Reservacion.php
* src/app/models/Caja.php
* src/app/models/MovimientoCaja.php
* src/app/models/IncrementoTarifa.php
* src/app/views/reservaciones/ver.php
* src/config/routes.php

## Flujo actual

* reservaciones/ver.php llama /reservaciones/verificar-modificar-dias.
* verificarModificarDiasAction() carga reservación con obtenerDatosBasicos().
* Obtiene habitaciones con obtenerHabitacionIds().
* Verifica disponibilidad y recalcula precio.
* La vista llama /reservaciones/modificar-dias.
* modificarDiasAction() repite carga, disponibilidad y cálculo.
* Actualiza fecha/precio con modificarFechaSalida().
* Si está checked_in y hay diferencia, inserta ajuste en movimientos_caja.

## Métodos auditados

* ReservacionController::verificarModificarDiasAction()
* ReservacionController::modificarDiasAction()
* Reservacion::obtenerDatosBasicos()
* Reservacion::obtenerHabitacionIds()
* Reservacion::verificarDisponibilidadMultipleExcluyendo()
* Reservacion::calcularPrecioTotal()
* Reservacion::modificarFechaSalida()
* Caja::obtenerCorteActual()
* MovimientoCaja::registrarMovimiento()
* IncrementoTarifa
* Vista reservaciones/ver.php

## Riesgos por nivel

### Alto

* modificarDiasAction()
* modificarFechaSalida()
* INSERT INTO movimientos_caja sin hotel_id
* SELECT metodo_pago FROM reservaciones WHERE id = ?

### Medio

* verificarModificarDiasAction()
* obtenerHabitacionIds()
* IncrementoTarifa como cálculo desde tarifas globales
* categorias_movimientos como catálogo global auxiliar

### Bajo

* Caja::obtenerCorteActual()
* Habitacion::find()
* Vista y rutas

## Validaciones necesarias

* reservaciones.hotel_id = ?
* reservacion_habitaciones.hotel_id = reservaciones.hotel_id
* habitaciones.hotel_id = reservacion_habitaciones.hotel_id
* movimientos_caja.hotel_id = reservaciones.hotel_id
* reservacion_pagos.hotel_id = reservaciones.hotel_id si se toca en fase futura
* cortes_caja.hotel_id = movimientos_caja.hotel_id
* huespedes y usuarios como auxiliares, no como fuente de scope.

## Qué implementar primero

Reservaciones 1-F-F-E-E-E-F-B:

* Entrada/controller scoped.
* Validar hotel actual en verificarModificarDiasAction() y modificarDiasAction() antes de cálculos o escrituras.

## Qué implementar después

* F-C: scope de modificarFechaSalida(), obtenerDatosBasicos() y obtenerHabitacionIds().
* F-D: scope de ajustes en movimientos_caja, incluyendo hotel_id, corte del hotel actual y reservación del mismo hotel.
* F-E: cierre general de modificar días.

## Qué dejar fuera

* PWA/offline.
* Sync.
* APIs globales.
* Migraciones/schema.
* Cambios de datos.
* Huéspedes tenant.
* Usuarios tenant.

## Pruebas necesarias

* php -l en archivos tocados.
* git diff --check.
* verificar_estado.php PASS.
* preflight_hotel_id.php PASS.
* Pruebas de modificación solo con rollback seguro o entorno controlado.
* Confirmar 0 nuevos hotel_id NULL.
* Confirmar que PWA/Sync/APIs no se tocaron.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-F-B: implementar únicamente la entrada/controller scoped de verificarModificarDiasAction() y modificarDiasAction(), sin tocar todavía modelo ni movimientos_caja.
