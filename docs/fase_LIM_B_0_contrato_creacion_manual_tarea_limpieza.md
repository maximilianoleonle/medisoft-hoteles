# Fase LIM-B-0 - Contrato creacion manual de tarea de limpieza

Estado: `CONTRATO_LIM_B_0_CREACION_MANUAL_TAREA_LIMPIEZA_COMPLETADO`.

## Objetivo

Definir una futura accion manual para crear una tarea operativa de categoria `limpieza`
desde una habitacion que ya esta en estado `limpieza`.

LIM-B-0 no implementa codigo. Solo fija reglas para una implementacion futura segura.

## Contexto

- LIM-0 definio limpieza como bloque independiente.
- LIM-A agrego `GET /reportes/limpieza` en modo read-only.
- `tareas_operativas` ya soporta categoria `limpieza`.
- `TareaOperativa::crearParaHotel()` ya crea tareas manuales con evento inicial.
- `habitaciones.estado` sigue siendo la fuente de verdad de disponibilidad.

## Alcance permitido futuro

- Agregar una accion POST manual, explicita y protegida.
- Crear solo una tarea de categoria `limpieza`.
- Vincular la tarea a `habitacion_id`.
- Usar `origen = habitacion`, valor permitido por el enum actual de `tareas_operativas.origen`.
- Bloquear duplicado activo de limpieza por habitacion.
- Mostrar boton solo en habitaciones con estado `limpieza`.
- Auditar la creacion si el patron existe.
- Redirigir al detalle de la tarea creada o al reporte de limpieza.

## Prohibido

- Liberar habitaciones.
- Cambiar `habitaciones.estado`.
- Crear tareas automaticamente al checkout.
- Crear tareas masivas.
- Descontar inventario automaticamente.
- Tocar Caja, pagos, abonos, nomina, offline o `/api/sync`.
- Reusar helpers legacy de inventario automatico por limpieza.

## Validaciones futuras obligatorias

- Sesion activa.
- Permiso compatible con tareas/habitaciones.
- CSRF.
- `hotel_id` actual.
- Habitacion existente, activa y del hotel actual.
- Habitacion en estado `limpieza`.
- Sin tarea activa de categoria `limpieza` para esa habitacion.
- Titulo, prioridad y fechas normalizadas por el modelo.

## Semaforo de riesgo

- Verde:
  - boton manual con CSRF en `/reportes/limpieza`;
  - creacion de tarea en `tareas_operativas`;
  - evento inicial en `tarea_eventos`.
- Amarillo:
  - mostrar boton tambien en ficha de habitacion;
  - notificaciones a rol limpieza.
- Rojo:
  - liberar habitacion al completar tarea;
  - crear tarea automatica por checkout;
  - inventario automatico por limpieza;
  - acciones masivas.

## Definition of Done futura

- Ruta POST registrada y acotada.
- Modelo centraliza validaciones.
- Vista muestra boton solo si aplica.
- Preflight valida duplicados activos y ausencia de Caja/offline.
- HTTP sin sesion bloquea.
- Doble creacion falla limpiamente.
- QA manual diferida o completada, segun instruccion vigente.

## Rollback

LIM-B-0 es documental. Rollback: revertir el commit
`docs(phase-lim): define manual housekeeping task contract`.

## Siguiente paso recomendado

LIM-B-A: implementacion manual controlada desde `/reportes/limpieza`, si se autoriza.
