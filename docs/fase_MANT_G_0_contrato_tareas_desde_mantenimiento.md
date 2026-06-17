# Fase MANT-G-0 - Contrato integracion tareas desde mantenimiento

Estado: `CONTRATO_MANT_G_0_TAREAS_DESDE_MANTENIMIENTO_COMPLETADO`

## Objetivo

Definir una integracion futura, controlada y reversible entre mantenimiento de
habitaciones y tareas operativas, sin implementar escrituras todavia.

La meta es permitir que un mantenimiento pueda tener seguimiento operativo mediante
`tareas_operativas`, sin convertir tareas en fuente de verdad de disponibilidad ni de
estado de mantenimiento.

## Diagnostico actual

- `mantenimientos_habitaciones` es la fuente de verdad de mantenimiento.
- `habitaciones` es la fuente de verdad del estado operativo de la habitacion.
- `tareas_operativas` ya contiene la columna `mantenimiento_id`.
- La migracion base TLM-A creo llave foranea desde `tareas_operativas.mantenimiento_id`
  hacia `mantenimientos_habitaciones.id`.
- `TareaOperativa` ya lee `mantenimiento_id` y hace joins read-only con
  `mantenimientos_habitaciones`.
- `TareaOperativa::crearParaHotel()` crea tareas manuales con `origen = 'manual'`, pero
  actualmente no inserta `mantenimiento_id`.
- MANT-D-A permite observar mantenimientos programados vencidos/proximos.
- MANT-E-A permite activar manualmente un mantenimiento programado individual.
- MANT-E-A no crea tareas y no debe crear tareas automaticamente sin contrato separado.

## Fuentes de verdad

- Estado/disponibilidad de habitacion: `habitaciones`.
- Estado de mantenimiento: `mantenimientos_habitaciones`.
- Seguimiento operativo de trabajo: `tareas_operativas`.
- Historial de tareas: `tarea_eventos`.
- Trazabilidad tecnica: `logs_auditoria`, si esta disponible.

`tareas_operativas` puede referenciar un mantenimiento por `mantenimiento_id`, pero no
debe decidir por si misma si una habitacion esta disponible, en mantenimiento o fuera de
servicio.

## Alcance futuro permitido

Una fase futura MANT-G-A puede implementar una de estas opciones seguras:

1. Lectura contextual:
   - Mostrar tareas vinculadas a un mantenimiento dentro del preview o detalle
     relacionado.
   - Solo GET.
   - Sin crear tareas.

2. Creacion manual desde mantenimiento:
   - Agregar una accion POST explicita para crear una tarea vinculada a un
     mantenimiento existente.
   - Requiere sesion, permiso, CSRF, `hotel_id`, auditoria y transaccion.
   - Debe validar que el mantenimiento y la habitacion pertenezcan al hotel actual.
   - Debe evitar duplicados no deseados para el mismo mantenimiento.

## Fuera de alcance

- Crear tareas automaticamente al activar mantenimiento.
- Crear tareas masivas.
- Crear cron, jobs o automatizaciones.
- Cambiar `habitaciones.estado` desde una tarea.
- Cambiar `mantenimientos_habitaciones.estado` desde una tarea.
- Reabrir, cerrar o cancelar mantenimiento desde tareas.
- Tocar Caja, pagos, abonos, nomina, CxP operativa, offline, IndexedDB o `/api/sync`.
- Crear migraciones nuevas en este contrato.

## Semaforo de riesgo

- Verde: lectura contextual de tareas por `mantenimiento_id`, filtrada por `hotel_id`.
- Amarillo: creacion manual de una tarea vinculada a un mantenimiento, con validaciones
  centrales y transaccion.
- Rojo: creacion automatica, cron, cambios de disponibilidad, cierres cruzados entre
  tareas y mantenimiento, o escrituras financieras.

## Reglas minimas para MANT-G-A

- Toda consulta debe filtrar por `hotel_id`.
- El mantenimiento debe pertenecer al hotel actual.
- La habitacion asociada debe pertenecer al mismo hotel.
- La tarea vinculada debe guardar `mantenimiento_id` solo si el mantenimiento es valido.
- Si se crea una tarea desde mantenimiento, el origen debe distinguirse de `manual`
  generico, usando el valor permitido `mantenimiento`.
- No debe existir escritura a Caja, pagos, abonos, nomina ni `/api/sync`.
- No debe llamar `Mantenimiento::activarMantenimientosPendientes()`.
- No debe modificar disponibilidad automaticamente.

## Definition of Done futura

- Contrato y rollback documentados.
- Rutas protegidas por sesion y permisos existentes.
- CSRF en cualquier POST.
- Modelo/servicio centraliza validaciones.
- Vista no muestra acciones en estados no permitidos.
- Health/preflight detectan:
  - tareas con `mantenimiento_id` inexistente;
  - tareas con mantenimiento de otro hotel;
  - duplicados no autorizados;
  - ausencia de cambios en Caja/pagos/abonos;
  - `/api/sync` bloqueado.
- `php -l`, health, preflight y `git diff --check` pasan.

## Rollback

- MANT-G-0 solo documenta; rollback: revertir el commit documental.
- No hay DB, migraciones, rutas, modelos ni datos nuevos.
- Si una fase futura crea tareas vinculadas, no borrar registros con SQL manual; usar
  estados operativos autorizados o documentar IDs para revision.

## QA futura sugerida

- Abrir el mantenimiento o preview con tareas vinculadas y sin tareas vinculadas.
- Confirmar estado vacio claro.
- Crear una tarea desde un mantenimiento controlado, si la fase futura lo permite.
- Confirmar que la tarea queda vinculada al `mantenimiento_id` correcto.
- Confirmar que no cambia `habitaciones.estado`.
- Confirmar que no cambia `mantenimientos_habitaciones.estado`.
- Confirmar que no hay Caja, pagos, abonos, nomina ni cambios en `/api/sync`.

## Siguiente cola recomendada

`[COLA_MANT_G_A_TAREAS_CONTEXTUALES_MANTENIMIENTO]`

Implementar primero una lectura contextual o una creacion manual estrictamente controlada,
sin automatizar tareas desde mantenimiento.
