# Fase TLM-D - Asignacion controlada de tareas a trabajadores

Estado: `ASIGNACION_TLM_D_COMPLETADA_QA_DIFERIDA`

## Objetivo

Permitir asignar o reasignar una tarea operativa pendiente a un trabajador activo del
mismo hotel, sin iniciar, completar, cancelar ni cambiar estados de habitaciones.

## Alcance implementado

- `POST /tareas/{id}/asignar` asigna trabajador a una tarea.
- El detalle de tarea muestra una seccion de asignacion si el usuario tiene permiso y la
  tarea esta `pendiente` o `asignada`.
- Si no hay trabajadores activos, la vista muestra estado vacio.
- La asignacion valida:
  - tarea existente del hotel actual;
  - tarea en estado `pendiente` o `asignada`;
  - trabajador activo del mismo hotel;
  - POST con CSRF.
- Al asignar:
  - `tareas_operativas.trabajador_id` se actualiza;
  - `tareas_operativas.asignada_por_usuario_id` se actualiza;
  - `tareas_operativas.estado` queda `asignada`;
  - se registra evento `asignada` en `tarea_eventos`;
  - se registra auditoria con `AuditService::record()`.

## Fuera de alcance

- No crea trabajadores.
- No inicia tareas.
- No completa tareas.
- No cancela tareas.
- No genera asistencia, nomina, pagos, abonos ni movimientos de Caja.
- No cambia `habitaciones.estado`.
- No modifica `mantenimientos_habitaciones`.
- No toca PWA, offline, IndexedDB, cache ni `/api/sync`.

## Archivos principales

- `src/app/models/TareaOperativa.php`
- `src/app/controllers/TareaController.php`
- `src/app/views/tareas/ver.php`
- `src/config/routes.php`
- `src/tools/saas/health_check_fase_1a.php`

## Definition of Done

- Ruta `POST /tareas/{id}/asignar` registrada.
- POST protegido por sesion, contexto hotelero, modulo `habitaciones`, permiso
  `habitaciones.mantenimiento` y CSRF.
- Modelo valida trabajador activo del mismo hotel.
- Modelo actualiza solo `tareas_operativas` y registra evento en `tarea_eventos`.
- No hay escrituras en habitaciones, mantenimiento, Caja, pagos, abonos ni nomina.
- Health checker conoce TLM-D.
- QA manual queda diferida por instruccion del usuario.

## Riesgos y mitigaciones

- Riesgo: asignar trabajador de otro hotel.
  - Mitigacion: `trabajadorActivoEnHotel()` valida `id + hotel_id + estado = activo`.
- Riesgo: convertir asignacion en inicio/cierre implicito.
  - Mitigacion: TLM-D solo permite `pendiente/asignada -> asignada`; no usa
    `fecha_inicio` ni `fecha_cierre`.
- Riesgo: mezclar TLM con nomina o asistencia.
  - Mitigacion: no se escribe en tablas laborales financieras ni asistencia.

## Rollback

- Revertir el commit `feat(phase-tlm): assign tasks to workers`.
- No borrar tareas ni eventos existentes.
- Si QA manual asigna tareas de prueba, documentar IDs y esperar una fase autorizada de
  reasignacion/cancelacion para reconciliarlas.

## QA manual diferida

1. Crear o usar un trabajador activo del hotel actual.
2. Crear una tarea manual pendiente.
3. Abrir detalle de tarea.
4. Asignar trabajador activo.
5. Confirmar que la tarea queda `asignada`.
6. Confirmar que se registra evento `asignada`.
7. Confirmar que no cambia `habitaciones.estado`.
8. Confirmar que no hay movimientos de Caja, pagos, abonos ni asistencia.

## Siguiente fase recomendada

`[COLA_TLM_E_ESTADOS_TAREA]`

Implementar inicio, cierre y cancelacion manual de tareas con auditoria, sin Caja y sin
cambios automaticos de disponibilidad.
