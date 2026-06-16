# Fase MANT-A - Reporte de mantenimiento read-only

Estado: `REPORTE_MANTENIMIENTO_MANT_A_COMPLETADO_QA_DIFERIDA`

## Objetivo

Estabilizar el reporte existente `/reportes/mantenimiento` como vista GET/read-only y
multihotel-safe, sin crear un modulo nuevo ni agregar acciones operativas de
mantenimiento.

## Diagnostico

- La ruta `GET /reportes/mantenimiento` ya existia.
- La vista `src/app/views/reportes/mantenimiento.php` ya existia y usa formulario `GET`
  para filtros de periodo/tipo.
- El controlador `ReportesController::mantenimientoAction()` ya existia y queda bajo las
  guardas generales del controlador de reportes: sesion, permiso `reportes.view` o
  `reportes.all`, y modulo `reportes`.
- Las consultas activas del reporte estaban en `Reporte.php`.
- Riesgo encontrado: la familia activa de consultas de mantenimiento no estaba filtrando
  por `hotel_id`, por lo que podia mezclar informacion entre hoteles.

## Cambios aplicados

- Se agrego filtro por hotel actual en las consultas activas usadas por
  `mantenimientoAction()`:
  - `obtenerMantenimientosPorTipo()`.
  - `obtenerMantenimientosPorPrioridad()`.
  - `obtenerHabitacionesConMasMantenimientos()`.
  - `obtenerRegistroMantenimientos()`.
  - `obtenerTopResponsablesMantenimiento()`.
  - `obtenerTendenciaMensualMantenimiento()`.
  - `obtenerEstadisticasMantenimientoCompletas()`.
- En joins con `habitaciones`, se agrego condicion de hotel para evitar cruces entre
  hoteles.
- Se creo `src/tools/saas/preflight_reporte_mantenimiento.php`.
- Se actualizo `health_check_fase_1a.php` para vigilar MANT-A.

## Alcance permitido

- Solo lectura.
- Ruta existente GET.
- Correccion de scope multihotel en consultas activas.
- Validaciones tecnicas y documentacion.

## Fuera de alcance

- Crear, iniciar, completar, cancelar o reasignar mantenimientos.
- Automatizar disponibilidad de habitaciones.
- Crear nuevas tablas o migraciones.
- Tocar Caja, pagos, abonos, nomina, offline o `/api/sync`.
- Cambiar calculos financieros profundos.
- Implementar acciones desde el tablero operativo.

## Fuentes de verdad

- Mantenimiento historico: `mantenimientos_habitaciones`.
- Habitaciones: `habitaciones`.
- Usuario de registro/responsable: `usuarios`, solo como metadata de lectura.
- Hotel actual: `obtenerHotelIdActualCompat()`.

## Semaforo de riesgo

- Verde: ruta GET existente, sin POST, sin escrituras, sin migraciones.
- Amarillo: historico de mantenimiento puede contener datos heredados; el preflight
  advierte inconsistencias de fechas o habitaciones huerfanas.
- Rojo: cualquier mantenimiento sin `hotel_id`, con habitacion de otro hotel, o nueva
  ruta POST bajo `/reportes/mantenimiento`.

## Definition of Done

- `/reportes/mantenimiento` sigue siendo GET-only.
- Las consultas activas usadas por la vista filtran por `hotel_id`.
- La vista no expone POST ni storage interno.
- Health checker valida MANT-A.
- Preflight MANT-A pasa sin errores.
- `/api/sync` sigue bloqueado por el checker general.

## Rollback

- Revertir el commit `test(phase-mant): add read-only maintenance report guardrails`.
- DB: no aplica; MANT-A no crea migraciones ni escribe datos.
- Si se revierte, revisar manualmente que no quede lectura cross-hotel en
  `/reportes/mantenimiento`.

## QA manual diferida

1. Abrir `/reportes/mantenimiento` con sesion de hotel.
2. Confirmar que los datos corresponden solo al hotel activo.
3. Probar filtros por fecha y tipo.
4. Confirmar estado vacio si no hay mantenimientos.
5. Confirmar que no hay botones POST ni acciones de mantenimiento.
6. Confirmar que HTTP sin sesion redirige a login o bloquea segun patron del sistema.
