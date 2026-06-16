# Fase MANT-G-B-0 - Contrato creacion manual de tarea desde mantenimiento

Estado: `CONTRATO_MANT_G_B_0_CREACION_MANUAL_TAREA_MANTENIMIENTO_COMPLETADO`

## Objetivo

Definir una accion futura, manual y explicita para crear una tarea operativa vinculada a
un mantenimiento existente, sin automatizar tareas, sin cambiar disponibilidad y sin
tocar Caja.

La tarea creada debe servir como seguimiento operativo del mantenimiento. No debe
convertirse en fuente de verdad de estado de habitacion ni de mantenimiento.

## Estado previo

- MANT-G-0 documento el contrato de enlace entre mantenimiento y tareas.
- MANT-G-A muestra tareas ya vinculadas a un mantenimiento en modo read-only.
- `tareas_operativas.mantenimiento_id` ya existe y tiene llave foranea hacia
  `mantenimientos_habitaciones.id`.
- `TareaOperativa::crearParaHotel()` crea tareas manuales, pero no recibe ni persiste
  `mantenimiento_id`.
- `ReportesController::mantenimientoProgramadoAction()` ya carga tareas vinculadas para
  el preview.

## Alcance permitido para MANT-G-B-A

- Crear una tarea desde un mantenimiento mediante accion POST manual.
- Mostrar boton solo en el preview de mantenimiento programado o pantalla equivalente,
  cuando el mantenimiento sea visible para el hotel actual.
- Reutilizar permisos existentes de tareas/mantenimiento:
  - sesion activa;
  - contexto de hotel;
  - modulo `habitaciones`;
  - permiso `habitaciones.mantenimiento`;
  - CSRF.
- Guardar:
  - `hotel_id` del contexto;
  - `categoria = 'mantenimiento'`;
  - `estado = 'pendiente'`;
  - `origen = 'mantenimiento_manual'`;
  - `habitacion_id` desde el mantenimiento;
  - `mantenimiento_id` del mantenimiento origen;
  - titulo/descripcion derivados de forma segura o recibidos desde formulario minimo.
- Registrar evento inicial en `tarea_eventos`.
- Auditar con `AuditService` si esta disponible.
- Redirigir al detalle de la tarea creada o de vuelta al preview si falla.

## Fuera de alcance

- Crear tareas automaticamente al activar mantenimiento.
- Crear tareas masivas.
- Crear tareas por cron o job.
- Crear tareas desde mantenimiento futuro sin decision explicita del usuario.
- Cambiar `habitaciones.estado`.
- Cambiar `mantenimientos_habitaciones.estado`.
- Completar/cancelar mantenimiento desde tareas.
- Cerrar tareas desde mantenimiento.
- Tocar Caja, pagos, abonos, nomina, CxP operativa, offline, IndexedDB o `/api/sync`.

## Reglas de validacion

- El mantenimiento debe existir.
- El mantenimiento debe pertenecer al hotel actual.
- La habitacion del mantenimiento debe pertenecer al mismo hotel.
- El mantenimiento debe tener `habitacion_id` valido.
- No se debe permitir crear una tarea vinculada a mantenimiento de otro hotel.
- Debe bloquear duplicado activo para el mismo `hotel_id + mantenimiento_id` si ya existe
  una tarea en `pendiente`, `asignada` o `en_proceso`.
- No bloquear tareas historicas completadas/canceladas salvo que una fase futura lo
  decida.
- Titulo y descripcion deben tener longitud maxima y fallback seguro.
- Toda escritura debe ocurrir en transaccion junto con el evento inicial.

## Semaforo de riesgo

- Verde: POST manual individual con CSRF, permiso, transaccion y bloqueo de duplicado
  activo.
- Amarillo: permitir titulo/descripcion editables desde formulario minimo.
- Rojo: automatizar creacion desde activacion de mantenimiento, cron, dashboard o
  acciones masivas.

## Definition of Done para MANT-G-B-A

- Ruta POST individual registrada.
- Controlador exige sesion, hotel, modulo, permiso y CSRF.
- Modelo centraliza creacion vinculada y validaciones.
- Vista muestra boton solo donde aplique.
- No se agregan rutas GET nuevas si no hacen falta.
- No se modifica `habitaciones`.
- No se modifica `mantenimientos_habitaciones`.
- No se toca Caja, pagos, abonos, nomina ni `/api/sync`.
- Health y preflight detectan:
  - ruta POST controlada;
  - modelo con `mantenimiento_id`;
  - bloqueo de duplicado activo;
  - ausencia de escrituras a habitaciones/mantenimiento/Caja;
  - tareas vinculadas a mantenimiento inexistente u otro hotel.
- `php -l`, health, preflight, HTTP sin sesion y `git diff --check` pasan.

## Rollback

- MANT-G-B-0 solo documenta; rollback: revertir el commit documental.
- Para MANT-G-B-A futuro: revertir commit de codigo/documentacion.
- DB: no aplica para MANT-G-B-0.
- Si QA futura crea tareas reales, no borrar con SQL manual; cancelar por flujo de tareas
  o documentar IDs para decision operativa.

## QA futura sugerida

1. Abrir `/reportes/mantenimiento-programado`.
2. Confirmar boton de crear tarea solo donde corresponda y con permiso.
3. Crear tarea desde mantenimiento controlado.
4. Confirmar redireccion a detalle de tarea.
5. Confirmar `mantenimiento_id`, `habitacion_id`, `hotel_id`, `origen` y evento inicial.
6. Intentar duplicado activo y confirmar bloqueo limpio.
7. Confirmar que la habitacion no cambia de estado.
8. Confirmar que el mantenimiento no cambia de estado.
9. Confirmar que no hay Caja, pagos, abonos, nomina ni cambios en `/api/sync`.

## Siguiente cola recomendada

`[COLA_MANT_G_B_A_CREACION_MANUAL_TAREA_MANTENIMIENTO]`

Implementar el POST manual individual con validaciones centrales, sin automatizacion y
sin cambios de estado fuera de `tareas_operativas`/`tarea_eventos`.
