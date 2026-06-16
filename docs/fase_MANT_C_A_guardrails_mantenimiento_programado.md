# Fase MANT-C-A - Guardrails de mantenimiento programado existente

Estado: `MANTENIMIENTO_PROGRAMADO_MANT_C_A_COMPLETADO_QA_DIFERIDA`

## Objetivo

Endurecer las rutas existentes de programacion y cancelacion de mantenimiento programado
sin crear rutas nuevas, sin activar automatizaciones y sin cambiar Caja, pagos, abonos,
nomina, offline ni `/api/sync`.

## Superficies tocadas

- `POST /habitaciones/{id}/programar-mantenimiento`.
- `POST /habitaciones/cancelar-mantenimiento-programado/{id}`.
- `HabitacionController::programarMantenimientoAction()`.
- `HabitacionController::cancelarMantenimientoProgramadoAction()`.
- `Mantenimiento::tieneProgramadoSolapado()`.
- `src/tools/saas/preflight_mantenimiento_operativo.php`.
- `src/tools/saas/health_check_fase_1a.php`.

## Cambios aplicados

- Programacion:
  - normaliza `id` y obtiene `hotel_id` una sola vez;
  - valida formato real de `fecha_programada` y `fecha_programada_fin` con
    `DateTimeImmutable::createFromFormat('!Y-m-d', ...)`;
  - mantiene bloqueo de fecha pasada y fin anterior a inicio;
  - valida `tipo_mantenimiento` contra `Mantenimiento::getTipos()`;
  - valida `prioridad` contra `Mantenimiento::getPrioridades()`;
  - exige `motivo`;
  - bloquea mantenimientos programados solapados para la misma habitacion/hotel;
  - conserva validacion de reservaciones conflictivas.
- Cancelacion:
  - normaliza `mantenimiento_id`;
  - confirma que el mantenimiento existe en el hotel actual;
  - confirma que la habitacion asociada existe en el hotel actual;
  - normaliza motivo vacio a `Cancelado por el usuario`.
- Modelo:
  - agrega `tieneProgramadoSolapado()` con scope por `hotel_id`.
- Checkers:
  - preflight MANT detecta solapes programados historicos;
  - health general reconoce guardrails MANT-C-A.

## Fuera de alcance

- No se conecta `activarMantenimientosPendientes()`.
- No se activan mantenimientos vencidos automaticamente.
- No se cambia `habitaciones.estado` fuera de los flujos existentes.
- No se migra mantenimiento a `tareas_operativas`.
- No se crean rutas, migraciones ni tablas.
- No se toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Verificacion automatica

- `php -l app/controllers/HabitacionController.php`: OK.
- `php -l app/models/Mantenimiento.php`: OK.
- `php -l tools/saas/preflight_mantenimiento_operativo.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- `php tools/saas/preflight_mantenimiento_operativo.php`: `ERROR: 0`.
- `php tools/saas/health_check_fase_1a.php`: `ERROR: 0`.

## Warnings conocidos

- Existe 1 habitacion en estado `mantenimiento` sin registro `en_proceso`.
- Se mantiene como warning historico y no se corrige automaticamente.
- No hay mantenimientos programados solapados en la base local al momento de validar.

## QA manual diferida

1. Programar mantenimiento con fecha valida.
2. Intentar fecha invalida, pasada y fin anterior a inicio.
3. Intentar tipo/prioridad alterados por POST manual.
4. Intentar motivo vacio.
5. Intentar crear un segundo mantenimiento programado solapado.
6. Intentar programar sobre fechas con reservacion confirmada/check-in.
7. Cancelar mantenimiento programado desde la ficha.
8. Confirmar que no se activa ningun mantenimiento vencido automaticamente.
9. Confirmar que no hay Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Rollback

- Revertir el commit `fix(phase-mant): harden scheduled maintenance actions`.
- DB: no aplica; MANT-C-A no crea migraciones ni modifica datos por si mismo.
- Si QA manual crea mantenimientos reales/de prueba, documentar IDs y usar flujo
  autorizado de cancelacion; no borrar con SQL manual.

