# Fase MANT-E-A - Activacion manual de mantenimiento programado

Estado: `ACTIVACION_MANUAL_MANT_E_A_COMPLETADA_QA_DIFERIDA`

## Objetivo

Permitir activar manualmente un mantenimiento programado vencido o de hoy desde el
preview de mantenimiento, mediante una accion individual explicita.

## Alcance implementado

- Ruta POST controlada:
  `/habitaciones/activar-mantenimiento-programado/{id}`.
- Accion de controlador:
  `HabitacionController::activarMantenimientoProgramadoAction()`.
- Metodo transaccional:
  `Mantenimiento::activarProgramadoManual()`.
- Boton visible solo para candidatos y usuarios con permiso
  `habitaciones.mantenimiento`.
- Auditoria no bloqueante:
  - `mantenimiento_programado.activado_manual`;
  - `mantenimiento_programado.activacion_bloqueada`.
- Guardrails en `preflight_mantenimiento_operativo.php` y
  `health_check_fase_1a.php`.

## Validaciones backend

- CSRF obligatorio.
- Permiso `habitaciones.mantenimiento`.
- Mantenimiento existe en el hotel actual.
- `programado = 1`.
- `estado = 'programado'`.
- `fecha_programada` existe y es vencida o de hoy.
- Habitacion del mismo hotel en estado `disponible`.
- No existe otro mantenimiento `en_proceso` para la habitacion/hotel.
- No hay reservaciones conflictivas en el rango programado.
- Transaccion con `FOR UPDATE`.

## Escrituras permitidas

Solo cuando el usuario autorizado confirma el POST:

- `mantenimientos_habitaciones.estado`: `programado` -> `en_proceso`.
- `mantenimientos_habitaciones.fecha_inicio`: `NOW()`.
- `mantenimientos_habitaciones.realizado_por`: usuario actual.
- `habitaciones.estado`: `disponible` -> `mantenimiento`.
- `logs_auditoria`: registro de exito o bloqueo, si la tabla esta disponible.
- `notificaciones`: aviso operativo no bloqueante.

## Fuera de alcance

- No activacion automatica.
- No activacion masiva.
- No cron, jobs, listeners ni dashboard automation.
- No llamada a `activarMantenimientosPendientes()`.
- No cierre automatico.
- No crear tareas automaticamente.
- No modificar reservaciones.
- No tocar Caja, pagos, abonos, nomina, offline, PWA ni `/api/sync`.

## Verificacion automatica

- `php -l app/models/Mantenimiento.php`: OK.
- `php -l app/controllers/HabitacionController.php`: OK.
- `php -l app/views/reportes/mantenimiento-programado.php`: OK.
- `php -l tools/saas/preflight_mantenimiento_operativo.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- `php tools/saas/preflight_mantenimiento_operativo.php`: `ERROR: 0`.
- `php tools/saas/health_check_fase_1a.php`: `ERROR: 0`.
- HTTP POST sin sesion a la ruta nueva: 303 a `/login`.
- SQL read-only antes/despues del POST sin sesion sin cambios:
  - `programados`: 1.
  - `en_proceso`: 1.
  - `habitaciones_mantenimiento`: 2.
  - `movimientos_caja`: 1403 historicos.
  - `cuentas_por_pagar_movimientos`: 0.

## QA manual diferida

Antes de probar una activacion real:

- Crear backup de DB.
- Abrir `/reportes/mantenimiento-programado` con sesion de hotel.
- Confirmar que solo aparece boton en candidatos.
- Activar un candidato vencido o de hoy.
- Confirmar que el mantenimiento pasa a `en_proceso`.
- Confirmar que la habitacion pasa a `mantenimiento`.
- Confirmar que el segundo intento queda bloqueado.
- Confirmar que no hay Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Rollback

- Revertir commit `feat(phase-mant): activate overdue scheduled maintenance manually`.
- Si QA manual activa un registro real, no borrar con SQL manual.
- Para revertir datos de prueba, usar solo flujos autorizados de mantenimiento segun
  estado y documentar IDs usados.
