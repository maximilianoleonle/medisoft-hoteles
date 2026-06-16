# Fase MANT-G-A - Tareas contextuales desde mantenimiento

Estado: `TAREAS_CONTEXTUALES_MANT_G_A_COMPLETADAS_QA_DIFERIDA`

## Objetivo

Mostrar tareas operativas ya vinculadas a un mantenimiento dentro del preview de
mantenimiento programado, sin crear tareas, sin acciones nuevas y sin cambiar estados.

## Alcance implementado

- `TareaOperativa::listarPorEntidadHotel()` ahora acepta la entidad `mantenimiento`.
- `ReportesController::mantenimientoProgramadoAction()` anexa hasta 3 tareas vinculadas
  por `mantenimiento_id` a cada registro del preview.
- La vista `reportes/mantenimiento-programado.php` muestra:
  - enlaces GET al detalle de tarea si existen tareas vinculadas;
  - estado vacio "Sin tareas vinculadas" cuando no existen.
- No se agregaron rutas nuevas.
- No se agregaron POST nuevos.
- No se crea tarea automaticamente al activar mantenimiento.
- No se modifica `habitaciones.estado`.
- No se modifica `mantenimientos_habitaciones.estado`.

## Archivos principales

- `src/app/models/TareaOperativa.php`
- `src/app/controllers/ReportesController.php`
- `src/app/views/reportes/mantenimiento-programado.php`
- `src/tools/saas/preflight_mantenimiento_operativo.php`
- `src/tools/saas/health_check_fase_1a.php`

## Seguridad

- Lectura filtrada por `hotel_id`.
- El enlace contextual usa `tareas_operativas.mantenimiento_id`.
- La vista solo enlaza a `GET /tareas/{id}`.
- No expone acciones de tareas dentro del preview.
- No toca Caja, pagos, abonos, nomina, CxP operativa, offline ni `/api/sync`.
- La accion manual existente de activar mantenimiento conserva su CSRF y permiso.

## Verificaciones automaticas

- `php -l app/models/TareaOperativa.php`: OK.
- `php -l app/controllers/ReportesController.php`: OK.
- `php -l app/views/reportes/mantenimiento-programado.php`: OK.
- `php -l tools/saas/preflight_mantenimiento_operativo.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- `php tools/saas/preflight_mantenimiento_operativo.php`: `ERROR: 0`, `WARNING: 1`.
- `php tools/saas/health_check_fase_1a.php`: `ERROR: 0`, `WARNING: 24`.
- HTTP sin sesion a `/reportes/mantenimiento-programado`: 303 a `/login`.
- SQL read-only:
  - `tareas_operativas`: 0.
  - `mantenimientos_habitaciones`: 10.
  - `movimientos_caja`: 1403.
  - `cuentas_por_pagar_movimientos`: 0.

## Warning residual

- Persiste el warning historico: 1 habitacion en estado `mantenimiento` sin registro
  `en_proceso` asociado.
- No se corrige automaticamente porque requiere decision operativa y/o QA manual.

## QA manual diferida

Por instruccion del usuario, la QA manual queda diferida.

Checklist sugerido:

1. Abrir `/reportes/mantenimiento-programado`.
2. Confirmar que cada mantenimiento muestra "Sin tareas vinculadas" si no hay tareas.
3. Si existe una tarea vinculada a `mantenimiento_id`, confirmar que aparece como enlace.
4. Abrir el enlace y confirmar detalle de tarea del hotel actual.
5. Confirmar que no hay botones nuevos para crear tareas.
6. Confirmar que no cambia estado de habitacion ni mantenimiento.
7. Confirmar que no hay Caja, pagos, abonos, nomina ni cambios en `/api/sync`.

## Rollback

- Revertir el commit `feat(phase-mant): show maintenance linked tasks`.
- No ejecutar SQL.
- No borrar tareas, mantenimientos ni habitaciones.

## Siguiente paso recomendado

MANT-G-B solo si se autoriza: creacion manual explicita de una tarea vinculada a un
mantenimiento, con POST, CSRF, permiso, auditoria, transaccion y bloqueo de duplicados.
