# Fase TLM-A - Migracion base de tareas operativas

Estado: `MIGRACION_TLM_A_TAREAS_BASE_COMPLETADA_QA_DIFERIDA`

Fecha: 2026-06-16

## Objetivo

Crear una base aditiva, reversible y multi-hotel para tareas operativas de limpieza,
mantenimiento ligero y tareas generales.

Esta fase solo crea estructura de datos. No crea UI, rutas, controladores, modelos,
POST funcionales ni automatizaciones.

## Backup previo

- Archivo: `src/storage/backups/phase_tlm_a_20260616_030648_before_tasks_base_medisoft_hoteles_import.sql`
- Tamano: `3192456` bytes.
- SHA256: `C76243D8AF98A2D9AA9D94FB5B1BB4D29369A861594141C7EC397A13AF3CF242`
- Base: `medisoft_hoteles_import`.

## Migracion

- Archivo: `migrations/20260616_002_fase_tlm_a_tareas_base.sql`.
- Registro en `migrations`: `20260616_002_fase_tlm_a_tareas_base.sql`.
- Batch local: `20`.
- Estado local: `ejecutada`.

## Tablas creadas

### `tareas_operativas`

Tabla central de tareas por hotel.

Campos clave:

- `hotel_id`
- `categoria`
- `titulo`
- `prioridad`
- `estado`
- `habitacion_id` nullable
- `reservacion_id` nullable
- `huesped_id` nullable
- `trabajador_id` nullable
- `mantenimiento_id` nullable
- fechas de programacion/inicio/cierre
- usuarios de creacion/asignacion/cierre/cancelacion

### `tarea_eventos`

Historial tecnico de eventos de tarea.

Campos clave:

- `hotel_id`
- `tarea_id`
- `tipo_evento`
- `estado_anterior`
- `estado_nuevo`
- `comentario`
- `usuario_id`

## Conteos posteriores

- `tareas_operativas`: 0.
- `tarea_eventos`: 0.
- `mantenimientos_habitaciones`: 10, sin cambios esperados.
- `trabajadores`: 0, sin cambios esperados.

Nota: existen 4 movimientos historicos de Caja con descripciones de productos de limpieza.
Son gastos preexistentes y no pertenecen a TLM-A.

## Garantias de alcance

- No se insertaron tareas.
- No se insertaron eventos.
- No se cambio `habitaciones.estado`.
- No se modifico `mantenimientos_habitaciones`.
- No se tocaron Caja, pagos, abonos ni nomina.
- No se tocaron PWA/offline/cache ni `/api/sync`.

## Rollback manual

Solo con autorizacion explicita y si las tablas siguen vacias:

```sql
SELECT COUNT(*) FROM tarea_eventos;
SELECT COUNT(*) FROM tareas_operativas;

DROP TABLE tarea_eventos;
DROP TABLE tareas_operativas;

DELETE FROM migrations
WHERE nombre = '20260616_002_fase_tlm_a_tareas_base.sql';
```

Si existe cualquier dato real, no ejecutar `DROP`. Exportar, reconciliar y documentar
rollback especifico.

## Siguiente accion recomendada

`[COLA_TLM_B_TAREAS_READ_ONLY]`

Crear modelo/controlador/vistas GET read-only para listar y consultar tareas, con estado
vacio claro y sin POST.
