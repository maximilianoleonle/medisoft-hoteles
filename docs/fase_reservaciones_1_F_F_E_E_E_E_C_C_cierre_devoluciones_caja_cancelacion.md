# Reservaciones 1-F-F-E-E-E-E-C-C-CIERRE

## Objetivo

Documentar el cierre técnico del scope por hotel_id en los movimientos de caja/devoluciones generados por Reservacion::cancelar().

## Qué se logró

* La consulta de ingresos previos en movimientos_caja ahora filtra por reservacion_id + hotel_id.
* Se selecciona y valida movimientos_caja.hotel_id.
* Los INSERT de egresos/devoluciones en movimientos_caja ahora incluyen hotel_id.
* Se pasa $hotel_id como parámetro del insert.
* Se valida defensivamente que el corte actual pertenece al hotel actual.
* El movimiento de devolución generado durante cancelación ya queda tenant-aware.
* categorias_movimientos queda como catálogo auxiliar.
* usuarios queda como auxiliar.
* Se conserva la lógica original de devolución.

## Archivo modificado

* src/app/models/Reservacion.php

## Qué NO se tocó

* Liberación de habitaciones.
* Update final de reservaciones.
* solicitudes_factura.
* devolverInventarioCancelacion().
* modificarDiasAction().
* PWA/offline.
* Sync.
* APIs globales.
* migraciones.
* schema.
* base de datos.

## Validaciones realizadas

* php -l en Reservacion.php.
* git diff --check.
* verificar_estado.php PASS.
* preflight_hotel_id.php PASS.
* No se hizo cancelación funcional porque escribiría en reservaciones/caja/factura sin rollback seguro autorizado.
* Confirmación de que no se tocaron habitaciones, update final de reservaciones, solicitudes_factura, modificarDiasAction(), PWA/Sync/APIs.
* 0 nuevos hotel_id NULL según herramientas.

## Riesgos pendientes

* Liberación de habitaciones sigue pendiente.
* Update final de reservaciones sigue pendiente.
* solicitudes_factura derivadas de cancelación siguen pendientes.
* modificarDiasAction() sigue pendiente.
* PWA/offline, Sync y APIs siguen fuera de scope.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-E-C-D: implementar únicamente liberación de habitaciones y update final de reservación scoped por hotel_id dentro de Reservacion::cancelar(), sin tocar todavía solicitudes_factura.
