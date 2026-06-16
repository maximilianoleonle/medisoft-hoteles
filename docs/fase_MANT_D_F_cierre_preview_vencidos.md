# Fase MANT-D-F - Cierre tecnico preview mantenimiento programado

Estado: `BLOQUE_MANT_D_PREVIEW_VENCIDOS_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- MANT-D-0 contrato y diagnostico.
- MANT-D-A preview GET/read-only de mantenimientos programados vencidos/proximos.
- Guardrails en health y preflight.
- Documentacion de QA, rollback, fuentes de verdad, decisiones y auditoria.

## Revision tecnica

- Ruta `GET /reportes/mantenimiento-programado` registrada.
- No existe `POST /reportes/mantenimiento-programado`.
- `ReportesController::mantenimientoProgramadoAction()` solo consulta
  `Mantenimiento::previewProgramados()` y renderiza vista.
- `Mantenimiento::previewProgramados()` filtra por `hotel_id`, solo lee datos y calcula
  advertencias/candidatos.
- La vista no contiene formularios POST, CSRF ni botones operativos.
- HTTP sin sesion redirige a `/login`.

## Auditoria de seguridad

- No se llama `Mantenimiento::activarMantenimientosPendientes()`.
- No se cambia `habitaciones.estado`.
- No se crean tareas, cron, jobs, listeners ni automatizaciones.
- No se modifican reservaciones.
- No se toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- `/api/sync` sigue bloqueado por health con HTTP 423 y `sync_temporarily_disabled`.

## Verificaciones ejecutadas

- `php -l app/models/Mantenimiento.php`: OK.
- `php -l app/controllers/ReportesController.php`: OK.
- `php -l app/views/reportes/mantenimiento-programado.php`: OK.
- `php -l tools/saas/preflight_mantenimiento_operativo.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- `php tools/saas/preflight_mantenimiento_operativo.php`: `ERROR: 0`.
- `php tools/saas/health_check_fase_1a.php`: `ERROR: 0`.
- `git diff --check`: sin errores; solo warnings CRLF.

## Warning residual

- 1 habitacion historica esta en estado `mantenimiento` sin registro activo
  `en_proceso`.
- Queda documentado como warning, sin correccion automatica ni SQL manual.

## QA manual diferida

- El usuario instruyo continuar sin esperar QA manual.
- QA pendiente: abrir `/reportes/mantenimiento-programado`, validar datos por hotel,
  links a habitaciones, ausencia de botones POST/activacion y ausencia de Caja/pagos.

## Rollback

- Revertir commit `feat(phase-mant): add overdue maintenance preview`.
- Revertir commit `docs(phase-mant): close overdue maintenance preview`.
- DB: no aplica; no hay migraciones ni escrituras.

## Siguiente paso recomendado

- Abrir contrato independiente antes de cualquier activacion manual o automatica de
  mantenimientos vencidos.
- No implementar activacion sin QA manual explicita, porque cambiaria
  `mantenimientos_habitaciones` y `habitaciones.estado`.
