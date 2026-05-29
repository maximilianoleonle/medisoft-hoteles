# Reservaciones 1-F-F-E-E-E-E-C-D-CIERRE

## Objetivo

Documentar el cierre técnico del scope por hotel_id en la liberación de habitaciones y update final de reservación dentro de Reservacion::cancelar().

## Qué se logró

* En la liberación de habitaciones se agregó join con reservaciones r.
* Se valida r.hotel_id = ?.
* Se valida rh.hotel_id = r.hotel_id.
* Se valida h.hotel_id = rh.hotel_id.
* El NOT EXISTS también queda acotado al mismo hotel.
* Se evita liberar habitaciones de otro hotel.
* El UPDATE final de reservaciones ahora usa WHERE id = ? AND hotel_id = ?.
* Se conserva la lógica original de cancelación.

## Archivo modificado

* src/app/models/Reservacion.php

## Qué NO se tocó

* movimientos_caja/devoluciones.
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
* Confirmación de que no se tocaron solicitudes_factura, modificarDiasAction(), PWA/Sync/APIs.
* 0 nuevos hotel_id NULL según herramientas.

## Riesgos pendientes

* solicitudes_factura derivadas de cancelación siguen pendientes.
* modificarDiasAction() sigue pendiente.
* PWA/offline, Sync y APIs siguen fuera de scope.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-E-C-E: implementar únicamente solicitudes_factura derivadas de cancelación scoped por hotel_id dentro de Reservacion::cancelar(), sin tocar modificarDiasAction().
