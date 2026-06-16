# Fase MANT-G-B-A - Creacion manual de tarea desde mantenimiento

Estado: `CREACION_MANUAL_TAREA_MANT_G_B_A_COMPLETADA_QA_DIFERIDA`

## Objetivo

Permitir crear manualmente una tarea operativa vinculada a un mantenimiento existente,
sin automatizar tareas y sin modificar estados de habitaciones o mantenimientos.

## Alcance implementado

- Ruta POST individual:
  - `POST /tareas/desde-mantenimiento/{id}`.
- `TareaController::crearDesdeMantenimientoAction()`:
  - exige sesion, contexto de hotel, modulo `habitaciones`, permiso
    `habitaciones.mantenimiento` y CSRF;
  - llama al modelo central;
  - audita con `tareas.creada_desde_mantenimiento`;
  - redirige al detalle de tarea creada si todo sale bien.
- `TareaOperativa::crearDesdeMantenimientoParaHotel()`:
  - valida mantenimiento por `id + hotel_id`;
  - valida habitacion del mismo hotel mediante join;
  - bloquea duplicado activo por `hotel_id + mantenimiento_id`;
  - crea tarea en estado `pendiente`;
  - guarda `categoria = mantenimiento`;
  - guarda `origen = mantenimiento_manual`;
  - guarda `mantenimiento_id` y `habitacion_id`;
  - registra evento inicial en `tarea_eventos`;
  - usa transaccion y `FOR UPDATE`.
- El preview de mantenimiento programado:
  - muestra boton "Crear tarea" solo si el usuario tiene permiso y no hay tarea activa;
  - muestra la tarea activa vinculada si existe;
  - mantiene enlaces GET a tareas vinculadas.

## Fuera de alcance

- No crea tareas automaticamente.
- No crea tareas masivas.
- No crea cron ni jobs.
- No cambia `habitaciones.estado`.
- No cambia `mantenimientos_habitaciones.estado`.
- No completa ni cancela mantenimientos desde tareas.
- No toca Caja, pagos, abonos, nomina, CxP operativa, offline ni `/api/sync`.

## Seguridad

- Escritura limitada a `tareas_operativas` y `tarea_eventos`.
- Auditoria no financiera en `logs_auditoria` si esta disponible.
- Bloqueo de duplicado activo por mantenimiento.
- Scope por `hotel_id` en mantenimiento, habitacion y tarea.
- HTTP sin sesion debe redirigir a login.

## Verificacion automatica esperada

- `php -l` en PHP tocados.
- `php tools/saas/preflight_tareas_operativas.php`.
- `php tools/saas/preflight_mantenimiento_operativo.php`.
- `php tools/saas/health_check_fase_1a.php`.
- HTTP sin sesion a la nueva ruta POST.
- SQL read-only para confirmar conteos si no se ejecuta QA manual.
- `git diff --check`.

## QA manual diferida

Por instruccion del usuario, la QA manual queda diferida.

Checklist sugerido:

1. Abrir `/reportes/mantenimiento-programado`.
2. Crear una tarea desde un mantenimiento controlado.
3. Confirmar redireccion a `/tareas/{id}`.
4. Confirmar que la tarea tiene `mantenimiento_id`, `habitacion_id`, `hotel_id` y
   `origen = mantenimiento_manual`.
5. Confirmar evento inicial `creada`.
6. Confirmar que el preview ya no permite crear otra tarea activa para el mismo
   mantenimiento.
7. Confirmar que `habitaciones.estado` no cambia.
8. Confirmar que `mantenimientos_habitaciones.estado` no cambia.
9. Confirmar que no hay Caja, pagos, abonos, nomina ni cambios en `/api/sync`.

## Rollback

- Revertir el commit `feat(phase-mant): create tasks from maintenance manually`.
- No ejecutar SQL.
- Si QA manual crea tareas reales, no borrarlas con SQL manual; cancelar por flujo de
  tareas o documentar IDs para decision operativa.

## Siguiente paso recomendado

Cierre tecnico MANT-G o auditoria/revision antes de avanzar a cualquier automatizacion.
