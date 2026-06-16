# Fase TLM-E - Estados manuales de tareas

Estado: `ESTADOS_TLM_E_COMPLETADOS_QA_DIFERIDA`

## Objetivo

Permitir iniciar, completar o cancelar tareas operativas de forma manual, con auditoria y
sin automatizar cambios de disponibilidad de habitaciones.

## Alcance implementado

- `POST /tareas/{id}/iniciar`
- `POST /tareas/{id}/completar`
- `POST /tareas/{id}/cancelar`
- Acciones visibles en el detalle de tarea solo para tareas activas.
- Cada accion requiere CSRF y permiso `habitaciones.mantenimiento`.
- El modelo valida transiciones:
  - `pendiente` o `asignada` -> `en_proceso`;
  - `pendiente`, `asignada` o `en_proceso` -> `completada`;
  - `pendiente`, `asignada` o `en_proceso` -> `cancelada`.
- Se registran eventos `iniciada`, `completada` o `cancelada`.
- Se auditan cambios con `AuditService::record()`.

## Fuera de alcance

- No cambia `habitaciones.estado`.
- No libera, ocupa ni bloquea habitaciones.
- No modifica `mantenimientos_habitaciones`.
- No genera notificaciones automaticas.
- No crea asistencia laboral.
- No crea pagos, abonos, nomina ni movimientos de Caja.
- No toca PWA, offline, IndexedDB, cache ni `/api/sync`.

## Archivos principales

- `src/app/models/TareaOperativa.php`
- `src/app/controllers/TareaController.php`
- `src/app/views/tareas/ver.php`
- `src/config/routes.php`
- `src/tools/saas/health_check_fase_1a.php`

## Definition of Done

- Rutas POST de iniciar/completar/cancelar registradas.
- Acciones protegidas por sesion, contexto hotelero, modulo `habitaciones`, permiso
  `habitaciones.mantenimiento` y CSRF.
- Transiciones centralizadas en modelo.
- Cambios escritos solo en `tareas_operativas` y `tarea_eventos`.
- Sin escrituras en habitaciones, mantenimiento, Caja, pagos, abonos, nomina ni
  `/api/sync`.
- Health checker conoce TLM-E.
- QA manual queda diferida por instruccion del usuario.

## Riesgos y mitigaciones

- Riesgo: completar una tarea y que el usuario espere liberar una habitacion.
  - Mitigacion: la UI indica que no modifica disponibilidad; no se escribe en
    `habitaciones`.
- Riesgo: cancelar tareas sin historial.
  - Mitigacion: se crea evento `cancelada` y auditoria.
- Riesgo: mezclar cierre de tarea con pagos o asistencia.
  - Mitigacion: no se escribe en tablas laborales financieras ni Caja.

## Rollback

- Revertir el commit `feat(phase-tlm): manage task lifecycle manually`.
- No borrar tareas ni eventos existentes.
- Si QA manual inicio/completo/cancelo tareas de prueba, documentar IDs antes de
  cualquier correccion. No ejecutar `DELETE` ni `UPDATE` manual sin autorizacion.

## QA manual diferida

1. Crear tarea manual.
2. Iniciarla y confirmar `En proceso`.
3. Completar una tarea activa y confirmar evento `completada`.
4. Cancelar una tarea activa y confirmar evento `cancelada`.
5. Confirmar que no cambia `habitaciones.estado`.
6. Confirmar que no hay movimientos de Caja, pagos, abonos ni asistencia.
7. Confirmar bloqueo/redireccion sin sesion para los tres POST.

## Siguiente fase recomendada

`[COLA_TLM_F_CONTEXTUAL_HABITACION_TRABAJADOR]`

Mostrar tareas relacionadas en fichas de habitacion y trabajador, sin crear nuevos POST.
