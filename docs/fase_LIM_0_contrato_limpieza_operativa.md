# Fase LIM-0 - Contrato limpieza operativa

Estado: `CONTRATO_LIM_0_LIMPIEZA_OPERATIVA_COMPLETADO`.

## Objetivo

Definir una base segura para evolucionar la operacion de limpieza sin romper los flujos
existentes de habitaciones, reservaciones, tareas, mantenimiento, inventario, offline ni
`/api/sync`.

LIM-0 no implementa codigo. Solo documenta contrato, riesgos y fases sugeridas.

## Diagnostico actual

- `habitaciones.estado = limpieza` ya existe como estado operativo.
- El checkout de reservaciones puede enviar habitaciones a `limpieza`.
- La ficha de habitacion ya permite finalizar limpieza con `POST /habitaciones/{id}/liberar`.
- El listado de habitaciones ya filtra y muestra habitaciones en limpieza.
- Existen tareas operativas con categoria `limpieza`, pero no sustituyen la fuente de
  verdad de disponibilidad.
- Existen notificaciones relacionadas con habitaciones en limpieza.
- Existen referencias legacy/congeladas a integracion automatica de inventario por
  limpieza; no deben reactivarse sin contrato especifico.

## Fuentes de verdad

- `habitaciones.estado` sigue siendo la fuente de verdad para disponibilidad fisica.
- `reservaciones` y `reservacion_habitaciones` siguen siendo fuente de verdad del flujo
  de checkout.
- `tareas_operativas` y `tarea_eventos` pueden servir como seguimiento operativo, no como
  autoridad para cambiar disponibilidad.
- `logs_auditoria` es la fuente de trazabilidad no financiera.
- `movimientos_caja`, pagos, abonos, nomina, offline e `/api/sync` no participan.

## Semaforo de riesgo

- Verde:
  - reportes GET/read-only de habitaciones en limpieza;
  - vistas de seguimiento sin POST;
  - enlaces a tareas existentes;
  - checkers de consistencia.
- Amarillo:
  - creacion manual de tarea de limpieza desde una habitacion en estado `limpieza`;
  - finalizacion manual de limpieza si usa el flujo existente y conserva CSRF;
  - notificaciones controladas.
- Rojo:
  - liberar habitaciones automaticamente;
  - cambiar disponibilidad desde tareas sin accion explicita;
  - descontar inventario automaticamente por limpieza;
  - crear movimientos de Caja, pagos, abonos o nomina;
  - tocar IndexedDB, offline, service worker o `/api/sync`.

## Reglas obligatorias para fases futuras

- Todo acceso debe filtrar por `hotel_id`.
- No cambiar los formularios existentes de habitaciones sin una fase especifica.
- No modificar `POST /habitaciones/{id}/liberar` salvo auditoria/guardas compatibles y
  QA manual.
- No automatizar liberacion de habitaciones.
- No crear tareas automaticamente al checkout.
- No reactivar descuentos automaticos de inventario por limpieza.
- No tocar Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Las acciones POST futuras deben tener sesion, permiso, CSRF, auditoria y validacion
  central.

## Fases sugeridas

### LIM-A - Reporte limpieza read-only

- Crear o ampliar una vista GET/read-only para habitaciones en limpieza.
- Mostrar habitacion, tipo, piso, ultima salida si aplica, tareas vinculadas y antiguedad
  del estado.
- Sin POST, sin cambio de estado y sin crear tareas.

### LIM-B - Tareas contextuales de limpieza

- Mostrar en la ficha/listado las tareas de categoria `limpieza` vinculadas a una
  habitacion.
- Reutilizar `TareaOperativa::listarPorEntidadHotel()` si el patron ya existe.
- Sin automatismos.

### LIM-C - Creacion manual de tarea de limpieza

- Permitir accion manual controlada para crear tarea de limpieza desde habitacion en
  estado `limpieza`.
- Bloquear duplicado activo por habitacion/categoria.
- No cambiar `habitaciones.estado`.

### LIM-D - Cierre operativo controlado

- Revisar si el flujo actual `liberar` necesita auditoria/refuerzo sin cambiar contrato.
- Cualquier cambio debe ser compatible con CSRF, permisos y QA manual.

### LIM-E - Health y preflights

- Validar habitaciones en limpieza, tareas activas duplicadas, tareas cross-hotel y
  ausencia de escrituras financieras/offline.

### LIM-F - Revision, auditoria y cierre

- Cerrar tecnicamente el bloque sin automatizar disponibilidad.

## Definition of Done

- Contrato documentado.
- Riesgos y rollback documentados.
- Fuentes de verdad actualizadas.
- QA futura definida.
- Sin cambios de codigo, rutas, DB o datos en LIM-0.

## Rollback

LIM-0 es documental. Rollback: revertir el commit
`docs(phase-lim): define housekeeping operations contract`.

## Siguiente paso recomendado

LIM-A reporte read-only de limpieza, solo GET y sin acciones.
