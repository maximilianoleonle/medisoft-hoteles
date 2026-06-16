# Fase MANT-C-0 - Contrato de mantenimiento programado

Estado: `CONTRATO_MANT_C_0_MANTENIMIENTO_PROGRAMADO_COMPLETADO`

## Objetivo

Definir el siguiente bloque seguro sobre mantenimiento programado existente antes de
modificar codigo operativo. La fase futura debe estabilizar y auditar la programacion,
cancelacion y eventual activacion de mantenimientos programados sin cambiar
disponibilidad de habitaciones de forma automatica fuera de un contrato explicito.

## Diagnostico

- Ya existen rutas operativas:
  - `POST /habitaciones/{id}/programar-mantenimiento`.
  - `POST /habitaciones/cancelar-mantenimiento-programado/{id}`.
- Ambas rutas viven en `HabitacionController`.
- La ficha de habitacion muestra mantenimientos programados y permite cancelarlos.
- `Mantenimiento` usa `mantenimientos_habitaciones` como fuente actual.
- `Mantenimiento::programar()` crea registros con `estado = programado` y
  `programado = 1`.
- `Mantenimiento::programadosPorHabitacion()` y `todosProgramados()` ya filtran por
  `hotel_id`.
- `Mantenimiento::activarMantenimientosPendientes()` existe y puede cambiar registros a
  `en_proceso` y habitaciones a `mantenimiento`; por riesgo, no debe activarse ni
  conectarse a cron/dashboard sin contrato separado.
- Reservaciones ya consultan conflictos con mantenimientos programados/en proceso.
- Tareas operativas (`tareas_operativas`, `tarea_eventos`) existen como capa moderna
  paralela, pero no sustituyen `mantenimientos_habitaciones`.

## Alcance permitido para MANT-C-A

- Revisar y endurecer las rutas existentes de programacion/cancelacion.
- Mantener POST, CSRF, permisos y names/actions existentes.
- Validar backend de tipo, prioridad, motivo y fechas.
- Validar que la habitacion pertenece al hotel actual antes de escribir.
- Validar que el mantenimiento cancelado pertenece al hotel actual.
- Bloquear duplicados programados solapados para la misma habitacion/hotel.
- Mantener la validacion de conflictos con reservaciones activas.
- Agregar o reforzar preflight/health para mantenimiento programado.
- Documentar QA, rollback y warnings.

## Fuera de alcance

- No activar automaticamente mantenimientos vencidos.
- No conectar `activarMantenimientosPendientes()` a cron, dashboard ni request web.
- No cambiar estado de habitaciones fuera de los flujos existentes.
- No migrar `mantenimientos_habitaciones` a `tareas_operativas`.
- No borrar, fusionar ni renombrar tablas.
- No cambiar reglas profundas de reservaciones/disponibilidad.
- No tocar Caja, pagos, abonos, nomina, offline, PWA, cache ni `/api/sync`.
- No crear migraciones en MANT-C-0.

## Fuentes de verdad

- Habitaciones: `habitaciones`.
- Mantenimiento historico/programado: `mantenimientos_habitaciones`.
- Tareas operativas modernas: `tareas_operativas` y `tarea_eventos`, solo como contexto.
- Reservaciones: `reservaciones` y `reservacion_habitaciones`.
- Hotel actual: `obtenerHotelIdActualCompat()` / contexto de sesion.

## Semaforo de riesgo

- Verde: documentacion, preflights read-only y validaciones de forma/catalogos.
- Amarillo: programar/cancelar mantenimientos escribe en `mantenimientos_habitaciones` y
  afecta disponibilidad futura.
- Rojo: activar mantenimientos automaticamente, tocar habitaciones por cron/dashboard,
  romper disponibilidad de reservaciones o mezclar hoteles.

## Definition of Done de MANT-C-A

- Rutas existentes siguen protegidas por sesion, CSRF y permiso
  `habitaciones.mantenimiento`.
- Validaciones backend no dependen solo de la vista.
- Todas las escrituras usan `hotel_id` y verifican pertenencia de habitacion/
  mantenimiento.
- Duplicados solapados se bloquean limpiamente.
- Cancelacion no puede operar mantenimientos de otro hotel ni estados no programados.
- Health/preflight detecta rutas, guardas, catalogos, conflictos y ausencia de Caja.
- `php -l` pasa en archivos PHP tocados.
- `git diff --check` no reporta errores.
- QA manual queda documentada si el usuario la difiere.

## Rollback previsto

MANT-C-0 solo documenta contrato; rollback documental:

- Revertir el commit `docs(phase-mant): define scheduled maintenance contract`.
- DB: no aplica.
- Codigo: no aplica.

Para una futura MANT-C-A, el rollback esperado seria revertir el commit de guardrails de
programacion/cancelacion y conservar intactos los registros reales creados por usuarios,
sin SQL manual destructivo.

## Subfases sugeridas

- MANT-C-0: contrato y diagnostico.
- MANT-C-A: guardrails de programacion/cancelacion existente.
- MANT-C-B: preflight/health especifico de mantenimiento programado.
- MANT-C-F: revision tecnica, auditoria y cierre.

