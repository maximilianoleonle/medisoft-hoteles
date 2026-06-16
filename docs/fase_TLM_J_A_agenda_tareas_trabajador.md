# Fase TLM-J-A - Agenda read-only de tareas por trabajador

Estado: `AGENDA_TLM_J_A_TAREAS_TRABAJADOR_READONLY_COMPLETADA_QA_DIFERIDA`.

## Objetivo

Agregar una agenda operativa GET/read-only para revisar carga de tareas por fecha,
trabajador, categoria y estado, sin crear acciones nuevas ni modificar datos.

## Cambios implementados

- Ruta GET:
  - `/tareas/agenda`
- Controlador:
  - `TareaController::agendaAction()`
- Modelo:
  - `TareaOperativa::agendaReadOnlyPorHotel()`
- Vista:
  - `app/views/tareas/agenda.php`
- Navegacion:
  - enlace a Agenda desde `app/views/tareas/index.php`.
- Checkers:
  - `tools/saas/preflight_tareas_operativas.php`
  - `tools/saas/health_check_fase_1a.php`

## Guardrails

- Solo GET.
- Filtros por fecha, trabajador, categoria y estado.
- Rango normalizado a maximo 31 dias.
- Consultas scoped por `hotel_id`.
- Enlaces solo a detalle de tarea, trabajador y habitacion.
- Sin formularios POST.
- Sin CSRF porque no hay escritura.
- Sin cambios en habitaciones, mantenimientos, inventario, Caja, pagos, abonos,
  nomina, offline ni `/api/sync`.

## Verificacion automatica requerida

- `php -l` en archivos PHP tocados.
- `tools/saas/preflight_tareas_operativas.php`.
- `tools/saas/health_check_fase_1a.php`.
- HTTP sin sesion a `/tareas/agenda` debe redirigir/bloquear.
- SQL read-only debe confirmar que no se crean tareas, eventos ni movimientos de Caja.
- `git diff --check`.

## QA manual diferida

1. Abrir `/tareas/agenda`.
2. Filtrar por fecha actual.
3. Filtrar por trabajador.
4. Filtrar por categoria `limpieza` y `mantenimiento`.
5. Confirmar enlaces GET a tarea, trabajador y habitacion.
6. Confirmar que no existen botones de asignar, iniciar, completar ni cancelar.
7. Confirmar que no se modifica habitacion, mantenimiento, inventario, Caja, nomina,
   offline ni `/api/sync`.

## Rollback

- Revertir el commit `feat(phase-tlm): add worker task agenda`.
- DB: no aplica; no crea migraciones ni datos.
- No tocar tareas reales, trabajadores, habitaciones, mantenimientos, Caja, pagos,
  abonos, nomina, offline ni `/api/sync`.

## Siguiente paso recomendado

Revision tecnica/auditoria/cierre TLM-J antes de abrir cualquier accion nueva sobre la
agenda.
