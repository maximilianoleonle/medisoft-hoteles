# Fase LIM-A - Reporte limpieza read-only

Estado: `REPORTE_LIM_A_LIMPIEZA_READONLY_COMPLETADO_QA_DIFERIDA`.

## Objetivo

Agregar una vista GET/read-only para consultar habitaciones en limpieza y tareas activas
de categoria `limpieza` del hotel actual, sin liberar habitaciones ni crear tareas.

## Cambios implementados

- Ruta GET:
  - `/reportes/limpieza`
- Controlador:
  - `ReportesController::limpiezaAction()`
  - `ReportesController::reporteLimpiezaOperativa()`
- Vista:
  - `app/views/reportes/limpieza-operativa.php`
- Navegacion:
  - enlace desde el centro de reportes.
- Checker:
  - `tools/saas/preflight_limpieza_operativa.php`

## Alcance

- Lista habitaciones en estado `limpieza`.
- Muestra resumen del hotel actual.
- Muestra distribucion por piso.
- Muestra tareas activas de categoria `limpieza`, si existen.
- Enlaza solo por GET a habitacion y tarea.

## Guardrails

- Solo GET.
- Sin formularios.
- Sin POST.
- Sin cambios de estado.
- Sin creacion de tareas.
- Sin liberacion automatica.
- Sin inventario automatico.
- Sin Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Verificacion automatica

- `php -l` en archivos PHP tocados: OK.
- `tools/saas/preflight_limpieza_operativa.php`: `ERROR: 0`.
- `tools/saas/health_check_fase_1a.php`: `ERROR: 0`.
- HTTP sin sesion a `/reportes/limpieza`: redirige a login.

## QA manual diferida

1. Abrir `/reportes/limpieza`.
2. Confirmar que muestra habitaciones en limpieza del hotel actual.
3. Confirmar que los enlaces a habitacion/tarea son GET.
4. Confirmar que no hay botones para liberar, crear tarea, descontar inventario o cambiar
   estado.
5. Confirmar que `/api/sync` sigue bloqueado por checker.

## Rollback

- Revertir el commit `feat(phase-lim): add read-only housekeeping report`.
- DB: no aplica; LIM-A no crea migraciones ni datos.
- No borrar ni modificar habitaciones, tareas, reservaciones, Caja ni offline.

## Siguiente paso recomendado

Revision tecnica/auditoria/cierre LIM-A antes de permitir cualquier POST de limpieza.
