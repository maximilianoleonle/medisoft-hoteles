# Fase 6B-A - Indicadores read-only de tareas en habitaciones

## Objetivo

Mostrar en el tablero de habitaciones un indicador compacto de tareas operativas activas vinculadas a cada habitacion, sin crear nuevas acciones, sin cambiar disponibilidad y sin mover la creacion manual de limpieza fuera de `/reportes/limpieza`.

## Alcance implementado

- `TareaOperativa::resumenActivoPorHabitacionesHotel()` agrega tareas activas por `hotel_id` y `habitacion_id`.
- `HabitacionController` anexa el resumen a las habitaciones visibles en listado normal y disponibilidad por fecha.
- `habitaciones/index.php` muestra un indicador de lectura en la tarjeta y en el reverso si existen tareas activas.
- El preflight TLM valida que el indicador 6B-A exista y que la vista no exponga creacion de tareas desde la tarjeta.

## Fuentes de verdad

- Disponibilidad fisica: `habitaciones.estado`.
- Tareas operativas: `tareas_operativas`.
- Historial tecnico: `tarea_eventos`.

## Semaforo de riesgo

- Verde: lectura por `hotel_id`, sin POST, sin migraciones, sin Caja, sin nomina y sin `/api/sync`.
- Amarillo: el indicador depende de que las tareas activas esten correctamente vinculadas a `habitacion_id`.
- Rojo diferido: cualquier sincronizacion futura que cambie `habitaciones.estado` desde tareas requiere contrato separado.

## Definition of Done

- El listado de habitaciones no crea ni cierra tareas.
- Las tarjetas muestran conteo solo si hay tareas activas.
- La consulta respeta `hotel_id`.
- El flujo de creacion manual de limpieza permanece en reportes.
- Preflight TLM y lint PHP pasan.

## QA manual diferida

1. Entrar a `/habitaciones`.
2. Confirmar que una habitacion con tarea activa muestra indicador de tareas.
3. Voltear la tarjeta y confirmar que solo aparece informacion, sin boton de crear tarea.
4. Entrar a `/habitaciones?fecha_consulta=YYYY-MM-DD&mostrar_disponibilidad=1` y confirmar que el indicador se mantiene.
5. Confirmar que `/reportes/limpieza` sigue siendo el lugar para crear tareas de limpieza.

## Rollback

- Revertir el commit de 6B-A.
- No borrar ni modificar registros de `tareas_operativas` o `tarea_eventos`.
- No tocar `habitaciones.estado`, Caja, nomina, offline ni `/api/sync`.
