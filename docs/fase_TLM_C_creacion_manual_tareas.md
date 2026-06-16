# Fase TLM-C - Creacion manual controlada de tareas

Estado: `CREACION_MANUAL_TLM_C_COMPLETADA_QA_DIFERIDA`

## Objetivo

Permitir crear tareas operativas manualmente para limpieza, mantenimiento ligero o
seguimiento general, manteniendo el flujo actual de habitaciones y mantenimiento como
fuente de verdad.

## Alcance implementado

- `GET /tareas/crear` muestra formulario de alta.
- `POST /tareas` crea una tarea operativa manual.
- La tarea se crea en estado `pendiente`.
- La tarea registra evento inicial `creada` en `tarea_eventos`.
- Se usa `hotel_id` del contexto activo, no del formulario.
- Se valida que la habitacion opcional pertenezca al hotel actual.
- Se usa CSRF en el formulario.
- Se audita la creacion con `AuditService::record()` cuando `logs_auditoria` esta
  disponible.
- El alta corre dentro de transaccion.

## Fuera de alcance

- No asigna trabajador.
- No inicia, completa ni cancela tareas.
- No cambia `habitaciones.estado`.
- No modifica `mantenimientos_habitaciones`.
- No crea notificaciones.
- No toca Caja, pagos, abonos ni nomina.
- No toca PWA, offline, IndexedDB, cache ni `/api/sync`.

## Archivos principales

- `src/app/models/TareaOperativa.php`
- `src/app/controllers/TareaController.php`
- `src/app/views/tareas/index.php`
- `src/app/views/tareas/form.php`
- `src/config/routes.php`
- `src/tools/saas/health_check_fase_1a.php`

## Definition of Done

- Rutas autorizadas registradas: listado, formulario, alta manual y detalle.
- `POST /tareas` requiere sesion, contexto hotelero, modulo `habitaciones`, permiso
  `habitaciones.mantenimiento` y CSRF.
- `TareaOperativa::crearParaHotel()` valida datos y crea tarea + evento en transaccion.
- No hay `UPDATE` ni `DELETE` de tareas en esta fase.
- No hay escrituras a habitaciones, mantenimiento ni Caja.
- `php -l`, health checker y preflights pasan sin errores relacionados.
- QA manual queda diferida por instruccion del usuario.

## Riesgos y mitigaciones

- Riesgo: crear tareas asociadas a una habitacion de otro hotel.
  - Mitigacion: validacion por `id + hotel_id` antes del INSERT.
- Riesgo: operadores interpreten tarea de mantenimiento como cambio de disponibilidad.
  - Mitigacion: el flujo no modifica `habitaciones.estado` ni
    `mantenimientos_habitaciones`.
- Riesgo: futura duplicacion con mantenimiento actual.
  - Mitigacion: TLM-C solo registra tareas manuales generales; integracion contextual
    queda para fase separada.

## Rollback

- Revertir el commit `feat(phase-tlm): create operational tasks manually`.
- No borrar tablas `tareas_operativas` ni `tarea_eventos`; pertenecen a TLM-A.
- Si QA manual crea tareas de prueba, no borrarlas sin autorizacion. Preferir documentar
  IDs y esperar una fase de cancelacion/baja logica.

## QA manual diferida

1. Abrir `/tareas/crear` con usuario autorizado.
2. Crear tarea general sin habitacion.
3. Crear tarea vinculada a habitacion del hotel actual.
4. Confirmar redireccion al detalle.
5. Confirmar evento `creada`.
6. Confirmar que `habitaciones.estado` no cambia.
7. Confirmar que Caja, pagos, abonos y `/api/sync` no cambian.

## Siguiente fase recomendada

`[COLA_TLM_D_ASIGNACION_TRABAJADOR]`

Asignar o reasignar una tarea a trabajador activo del mismo hotel, sin pagos, sin Caja y
sin cambiar estados de habitacion.
