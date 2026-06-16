# Fase TLM-0 - Contrato y diagnostico de Tareas, Limpieza y Mantenimiento

Estado: `CONTRATO_TLM_0_COMPLETADO`

Fecha: 2026-06-16

## Objetivo

Definir una fundacion segura para un bloque de Tareas, Limpieza y Mantenimiento sin
reescribir la logica actual de habitaciones, reservaciones, mantenimiento programado ni
check-in/check-out.

La fase TLM-0 es solo diagnostico y documentacion. No crea migraciones, no agrega rutas,
no modifica datos y no toca `/api/sync`.

## Alcance autorizado para TLM-0

- Revisar infraestructura actual de limpieza, mantenimiento y tareas.
- Identificar fuentes de verdad existentes.
- Definir riesgos y limites.
- Proponer modelo de datos futuro, aditivo y reversible.
- Proponer subfases de implementacion.
- Actualizar QA, rollback, decisiones tecnicas, fuentes de verdad y resumen ejecutivo.

## Fuera de alcance

- Migraciones.
- Nuevas rutas.
- Nuevos controladores/modelos/vistas.
- Cambios a `HabitacionController`.
- Cambios a `Mantenimiento`.
- Cambios a reservaciones, check-in/check-out o estados de habitacion.
- Pagos, Caja, abonos, nomina operativa o Fase 3D.
- Cambios en PWA, offline, IndexedDB, cache names o `/api/sync`.
- Borrado de datos, `DROP`, `DELETE`, renombres o fusiones.

## Diagnostico actual

### Habitaciones

- `habitaciones.estado` ya usa estados operativos `disponible`, `ocupada`,
  `limpieza` y `mantenimiento`.
- La liberacion de habitaciones en limpieza ya existe por:
  - `POST /habitaciones/{id}/liberar`;
  - `POST /habitaciones/liberar-multiples`.
- El mantenimiento de habitacion ya existe por:
  - `POST /habitaciones/{id}/mantenimiento`;
  - `POST /habitaciones/{id}/programar-mantenimiento`;
  - `POST /habitaciones/cancelar-mantenimiento-programado/{id}`.
- Las rutas existentes usan CSRF y permisos de habitaciones.

### Mantenimiento

- Existe modelo `Mantenimiento`.
- Fuente actual: `mantenimientos_habitaciones`.
- La tabla tiene `hotel_id` y los registros actuales tienen `hotel_id` completo.
- Conteo observado:
  - `mantenimientos_habitaciones`: 10 registros.
  - registros sin `hotel_id`: 0.
  - habitaciones en `limpieza`: 12.
  - habitaciones en `mantenimiento`: 2.
- Estados observados en mantenimiento:
  - `en_proceso`;
  - `completado`;
  - `programado`.
- Tipos observados:
  - `preventivo`;
  - `correctivo`;
  - `emergencia`;
  - `limpieza_profunda`.

### Tareas

- No se detecto tabla propia de tareas operativas.
- No se detecto controlador o vista independiente de tareas.
- Los modulos globales `limpieza` y `mantenimiento` existen y estan activos para los 4
  hoteles revisados.

### Personal

- Ya existe base de trabajadores desde Fase NP-A.
- Al diagnostico TLM-0, `trabajadores` tiene 0 registros.
- La relacion futura tarea-trabajador debe ser opcional y filtrada por `hotel_id`.

## Fuentes de verdad actuales

- Disponibilidad/estado operativo de habitacion: `habitaciones.estado`.
- Mantenimiento historico y programado de habitacion: `mantenimientos_habitaciones`.
- Notificaciones operativas: `notificaciones` y servicios de reglas existentes.
- Trabajadores: `trabajadores`, cuando existan registros.

El futuro modulo de tareas NO debe sustituir estas fuentes sin una fase de reconciliacion
separada.

## Riesgo

Semaforo: naranja.

Motivos:

- Limpieza y mantenimiento ya impactan disponibilidad de habitaciones.
- Reservaciones verifican conflictos con mantenimiento programado.
- Habitaciones en limpieza participan en flujos de check-out, liberacion y ocupacion.
- Hay datos reales en `mantenimientos_habitaciones`.

Mitigacion:

- Empezar con migracion aditiva nueva, sin tocar `mantenimientos_habitaciones`.
- Mantener `habitaciones.estado` como fuente de verdad de disponibilidad.
- Crear primero vistas read-only y checkers.
- Integrar asignaciones a trabajadores solo como referencia opcional.
- No automatizar cambios de estado de habitacion desde tareas en la primera etapa.

## Modelo de datos futuro propuesto

No se crea en TLM-0. Queda como propuesta para TLM-A.

### `tareas_operativas`

Tabla central para tareas generales, limpieza y mantenimiento ligero.

Campos sugeridos:

- `id`
- `hotel_id`
- `categoria` (`limpieza`, `mantenimiento`, `general`)
- `titulo`
- `descripcion`
- `prioridad` (`baja`, `media`, `alta`, `urgente`)
- `estado` (`pendiente`, `asignada`, `en_proceso`, `completada`, `cancelada`)
- `habitacion_id` nullable
- `reservacion_id` nullable
- `huesped_id` nullable
- `trabajador_id` nullable
- `mantenimiento_id` nullable
- `fecha_programada` nullable
- `fecha_limite` nullable
- `fecha_inicio` nullable
- `fecha_cierre` nullable
- `creada_por_usuario_id`
- `asignada_por_usuario_id` nullable
- `cerrada_por_usuario_id` nullable
- timestamps

### `tarea_eventos`

Historial auditable de cambios de estado/comentarios, sin reemplazar `logs_auditoria`.

Campos sugeridos:

- `id`
- `hotel_id`
- `tarea_id`
- `tipo_evento`
- `estado_anterior` nullable
- `estado_nuevo` nullable
- `comentario` nullable
- `usuario_id` nullable
- timestamps

### `tarea_checklist_items`

Checklist simple para limpieza o mantenimiento recurrente. Se recomienda dejarla para
fase posterior si TLM-A debe mantenerse minima.

## Reglas de implementacion futuras

- Toda consulta y escritura debe filtrar por `hotel_id`.
- No guardar archivos; documentos deben usar Centro Documental.
- No cambiar `habitaciones.estado` automaticamente desde la primera fase de tareas.
- No crear movimientos de Caja.
- No crear pagos, abonos ni nomina.
- No modificar `mantenimientos_habitaciones` salvo fase explicita.
- No modificar `/api/sync`.
- No exponer rutas publicas sin autenticacion.
- POST siempre con CSRF.
- Auditoria minima para crear, asignar, iniciar, completar y cancelar.

## Subfases propuestas

- TLM-0: contrato y diagnostico.
- TLM-A: migracion base aditiva de tareas operativas.
- TLM-B: capa read-only de listado/detalle de tareas.
- TLM-C: creacion manual de tarea con CSRF, sin cambiar estados de habitacion.
- TLM-D: asignacion opcional a trabajador activo del mismo hotel.
- TLM-E: cierre/cancelacion manual con auditoria, sin Caja.
- TLM-F: integracion visual contextual en habitacion/trabajador.
- TLM-G: health/preflights y consistencia.
- TLM-H: revision tecnica, auditoria de seguridad y cierre.

## Definition of Done del bloque TLM

- Contrato documentado.
- Migracion aditiva y reversible, si se autoriza.
- Read-only implementado antes de escrituras.
- Todo filtrado por `hotel_id`.
- Rutas sensibles con auth, permisos existentes y CSRF en POST.
- Sin Caja, pagos, abonos ni nomina.
- Sin cambios en `/api/sync`.
- Sin tocar PWA/offline/cache.
- Sin borrar ni fusionar `mantenimientos_habitaciones`.
- QA critica, funcional, visual y regresion documentada.
- Rollback por subfase documentado.
- Checkers sin errores bloqueantes.
- Commits por subfase.

## Siguiente accion recomendada

`[COLA_TLM_A_MIGRACION_BASE_TAREAS]`

Crear una migracion aditiva e idempotente para `tareas_operativas` y, si se justifica,
`tarea_eventos`, sin rutas ni UI todavia. Antes de aplicar, generar backup de
`medisoft_hoteles_import`.
