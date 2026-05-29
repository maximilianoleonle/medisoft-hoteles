# Reservaciones 1-F-F-E-E-E-E-C-E-CIERRE

## Objetivo

Documentar el cierre técnico del scope por hotel_id en las solicitudes_factura derivadas de cancelación dentro de Reservacion::cancelar().

## Qué se logró

* El bloque de solicitudes_factura dentro de Reservacion::cancelar() ya respeta hotel_id.
* El UPDATE solicitudes_factura ahora usa join contra reservaciones r.
* Se filtra sf.reservacion_id = ?.
* Se filtra sf.hotel_id = ?.
* Se valida sf.hotel_id = r.hotel_id.
* Se valida r.hotel_id = ?.
* Se conserva el filtro de estatus pendiente/en_proceso.
* Se conserva la nota automática.
* Se conserva la lógica original de cancelación de factura.

## Archivo modificado

* src/app/models/Reservacion.php

## Qué NO se tocó

* movimientos_caja/devoluciones.
* liberación de habitaciones.
* update final de reservaciones.
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
* Confirmación de que no se tocó modificarDiasAction(), PWA/Sync/APIs.
* 0 nuevos hotel_id NULL según herramientas.

## Riesgos pendientes

* modificarDiasAction() sigue pendiente.
* PWA/offline, Sync y APIs siguen fuera de scope.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-E-C-F-CIERRE: cierre general del bloque Reservacion::cancelar() scoped por hotel_id, antes de pasar a modificarDiasAction().
