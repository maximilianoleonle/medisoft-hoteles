# Reservaciones 1-F-F-E-E-E-E-C-F-CIERRE

## Objetivo

Documentar el cierre general del flujo de cancelación de reservación scoped por hotel_id.

## Qué quedó cerrado

* ReservacionController::cancelarAction()
* Carga inicial de Reservacion::cancelar()
* Devoluciones/movimientos_caja dentro de Reservacion::cancelar()
* Liberación de habitaciones dentro de Reservacion::cancelar()
* Update final de reservaciones dentro de Reservacion::cancelar()
* solicitudes_factura derivadas de cancelación dentro de Reservacion::cancelar()

## Qué se logró

* La entrada de cancelación valida que la reservación pertenezca al hotel actual.
* El modelo Reservacion::cancelar() ya carga la reservación de forma scoped.
* Los movimientos de caja por devolución ya escriben hotel_id.
* Las consultas de ingresos previos de movimientos_caja ya filtran por hotel_id.
* La liberación de habitaciones ya valida hotel_id en reservaciones, reservacion_habitaciones y habitaciones.
* El update final de reservaciones ya usa id + hotel_id.
* Las solicitudes_factura relacionadas con cancelación ya se actualizan con hotel_id.
* Se evita que la cancelación de un hotel afecte datos de otro hotel.
* huespedes y usuarios quedan como auxiliares, no como fuente de scope.

## Qué NO se tocó

* modificarDiasAction().
* PWA/offline.
* Sync.
* APIs globales.
* migraciones.
* schema.
* base de datos.
* huéspedes tenant.
* usuarios tenant.

## Validaciones generales

* php -l en archivos tocados durante las fases.
* git diff --check.
* verificar_estado.php PASS.
* preflight_hotel_id.php PASS.
* No se hicieron cancelaciones funcionales porque escribirían en reservaciones/caja/factura sin rollback seguro autorizado.
* 0 nuevos hotel_id NULL según herramientas.
* Confirmación de que no se tocaron PWA/Sync/APIs.

## Riesgos pendientes

* modificarDiasAction() sigue pendiente.
* PWA/offline, Sync y APIs siguen fuera de scope.
* Huéspedes tenant y usuarios tenant quedan como decisiones futuras.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-F-A: auditoría de modificarDiasAction(), sin implementación directa.
