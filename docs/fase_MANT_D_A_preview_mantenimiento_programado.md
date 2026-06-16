# Fase MANT-D-A - Preview read-only de mantenimiento programado

Estado: `PREVIEW_MANT_D_A_COMPLETADO_QA_DIFERIDA`

## Objetivo

Agregar una superficie segura para revisar mantenimientos programados vencidos o
proximos sin activarlos, sin cambiar habitaciones y sin crear automatizaciones.

## Alcance implementado

- Ruta GET protegida: `/reportes/mantenimiento-programado`.
- Accion read-only: `ReportesController::mantenimientoProgramadoAction()`.
- Metodo central de consulta: `Mantenimiento::previewProgramados()`.
- Vista read-only: `app/views/reportes/mantenimiento-programado.php`.
- Enlace secundario desde el indice de reportes.
- Guardrails en `preflight_mantenimiento_operativo.php`.
- Guardrails en `health_check_fase_1a.php`.

## Datos mostrados

- Habitacion y enlace a su ficha.
- Fecha programada e intervalo visible.
- Tipo y prioridad.
- Estado actual de la habitacion.
- Conteo de reservaciones conflictivas.
- Categoria preview: vencido, hoy o proximo.
- Candidato revisable cuando esta vencido/hoy, la habitacion esta disponible y no hay
  conflicto de reservacion.
- Advertencias de bloqueo cuando no es candidato.

## Seguridad y limites

- Solo GET.
- Sin formularios POST.
- Sin CSRF porque no hay escritura en esta vista.
- Sin llamada a `Mantenimiento::activarMantenimientosPendientes()`.
- Sin cambios en `habitaciones.estado`.
- Sin crear tareas, cron, jobs, listeners ni automatizaciones.
- Sin Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Todas las consultas usan `hotel_id` del contexto actual.

## Verificacion automatica

- `php -l app/models/Mantenimiento.php`: OK.
- `php -l app/controllers/ReportesController.php`: OK.
- `php -l app/views/reportes/mantenimiento-programado.php`: OK.
- `php -l tools/saas/preflight_mantenimiento_operativo.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- `php tools/saas/preflight_mantenimiento_operativo.php`: `ERROR: 0`.
- `php tools/saas/health_check_fase_1a.php`: `ERROR: 0`.
- HTTP sin sesion a `/reportes/mantenimiento-programado`: 303 a `/login`.
- SQL read-only:
  - `mantenimientos_total`: 10.
  - `programados`: 1.
  - `vencidos`: 1.
  - `movimientos_caja`: 1403 historicos.
  - `cuentas_por_pagar_movimientos`: 0.

## Warning conocido

- Existe 1 habitacion en estado `mantenimiento` sin registro activo `en_proceso`.
- Es historico y no se corrige automaticamente en esta fase.

## QA manual diferida

- Abrir `/reportes/mantenimiento-programado` con sesion de hotel.
- Confirmar listado o estado vacio.
- Confirmar que los registros pertenecen al hotel actual.
- Confirmar links a habitaciones.
- Confirmar que no hay botones de activar, formularios POST ni cambios de habitacion.
- Confirmar que Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

## Rollback

- Revertir el commit `feat(phase-mant): add overdue maintenance preview`.
- Retirar ruta GET `/reportes/mantenimiento-programado`.
- Retirar `ReportesController::mantenimientoProgramadoAction()`.
- Retirar `Mantenimiento::previewProgramados()`.
- Retirar vista `app/views/reportes/mantenimiento-programado.php`.
- Retirar enlace desde `app/views/reportes/index.php`.
- Retirar checks MANT-D-A de health/preflight.
- DB: no aplica; no hay migraciones ni escrituras.
