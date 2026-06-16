# Fase TLM-I-A - Reporte operativo read-only

Estado: `REPORTE_TLM_I_A_COMPLETADO_QA_DIFERIDA`

Fecha: 2026-06-16

## Objetivo

Implementar un reporte GET/read-only de Tareas, Limpieza y Mantenimiento que consolide el
estado operativo de tareas por hotel sin crear tareas, sin asignar trabajadores, sin
cambiar estados, sin modificar habitaciones, sin tocar mantenimiento historico y sin
integrar Caja, pagos, abonos, nomina ni `/api/sync`.

## Cambios aplicados

- Ruta GET protegida: `/tareas/reporte`.
- Accion `TareaController::reporteAction()`.
- Metodo read-only `TareaOperativa::reporteReadOnlyPorHotel()`.
- Vista `tareas/reporte.php` sin formularios POST.
- Enlace GET "Reporte" desde el listado de tareas.
- Health checker y preflight TLM actualizados para reconocer TLM-I-A.

## Datos mostrados

- Totales por estado.
- Totales por categoria.
- Totales por prioridad.
- Tareas vencidas activas.
- Tareas proximas a vencer en 24 horas.
- Tareas activas sin asignar.
- Carga por trabajador.
- Carga por habitacion.
- Tareas recientes.
- Eventos recientes.

## Reglas cumplidas

- `hotel_id` se deriva del contexto de sesion.
- No acepta `hotel_id` desde query ni formulario.
- No contiene formularios POST.
- No crea tareas.
- No asigna trabajadores.
- No inicia, completa ni cancela tareas.
- No cambia `habitaciones.estado`.
- No modifica `mantenimientos_habitaciones`.
- No crea asistencia, nomina, pagos ni abonos.
- No crea movimientos de Caja.
- No toca `/api/sync`.

## Verificacion esperada

- `php -l` en archivos PHP modificados.
- `preflight_tareas_operativas.php` con `ERROR: 0`.
- `health_check_fase_1a.php` con `ERROR: 0`.
- SQL read-only confirmando que `tareas_operativas`, `tarea_eventos` y Caja no fueron
  modificadas.
- HTTP sin sesion a `/tareas/reporte` redirige/bloquea segun patron.
- `git diff --check`.

## QA manual diferida

El usuario autorizo omitir QA manual temporalmente. Cuando se retome:

- Abrir `/tareas/reporte` con sesion de hotel.
- Confirmar estado vacio si no hay tareas.
- Confirmar datos correctos si existen tareas.
- Confirmar que no hay formularios POST ni botones de accion.
- Confirmar enlaces GET a tarea, trabajador y habitacion.
- Confirmar que no cambia disponibilidad de habitacion ni Caja.

## Rollback

- Revertir el commit `feat(phase-tlm): add read-only operations report`.
- DB: no aplica; TLM-I-A no crea migraciones ni escribe datos.
- No limpiar `tareas_operativas`, `tarea_eventos`, habitaciones, mantenimiento ni Caja
  desde este rollback.

## Siguiente paso recomendado

Revision tecnica y auditoria de seguridad TLM-I-A antes de abrir nuevas acciones
operativas o automatizaciones.
