# Fase TLM-F - Tareas contextuales en habitacion y trabajador

## Objetivo

Mostrar tareas operativas relacionadas dentro de las fichas de habitacion y trabajador,
sin crear nuevas acciones, sin cambiar estados operativos y sin tocar Caja, pagos,
abonos, nomina, mantenimiento historico ni `/api/sync`.

## Alcance implementado

- `TareaOperativa::listarPorEntidadHotel()` permite lecturas contextuales por:
  - `habitacion`;
  - `trabajador`.
- La consulta siempre filtra por `hotel_id`.
- Los joins con `habitaciones` y `trabajadores` se hacen por el mismo hotel.
- La ficha de habitacion muestra un bloque read-only de tareas vinculadas.
- La ficha de trabajador muestra un bloque read-only de tareas asignadas.
- El partial contextual solo muestra metadata segura y enlace GET al detalle de tarea.

## Rutas

No se agregaron rutas nuevas en esta subfase.

La integracion reutiliza rutas ya existentes:

- `GET /habitaciones/{id}`
- `GET /trabajadores/{id}`
- `GET /tareas/{id}`

## Seguridad

- Sin POST nuevo.
- Sin formularios nuevos.
- Sin CSRF nuevo porque no hay escritura nueva.
- Sin escritura en `tareas_operativas`.
- Sin escritura en `tarea_eventos`.
- Sin escritura en `habitaciones`.
- Sin escritura en `mantenimientos_habitaciones`.
- Sin escritura en Caja, pagos, abonos o nomina.
- Sin exposicion de rutas internas de storage.
- Sin cambios en `/api/sync`.

## Definition of Done

- Fichas de habitacion y trabajador renderizan tareas contextuales con estado vacio claro.
- La lectura respeta `hotel_id`.
- El partial contextual no contiene formularios, POST ni acciones operativas.
- El health checker valida modelo, partial e integracion de ambas fichas.
- `php -l` pasa en archivos PHP tocados.
- `health_check_fase_1a.php` pasa sin errores.
- `git diff --check` pasa.

## QA manual diferida

Por instruccion del usuario, la QA manual queda diferida.

Checklist sugerido:

- Abrir una habitacion sin tareas y confirmar estado vacio.
- Abrir una habitacion con tarea vinculada y confirmar enlace a detalle.
- Abrir un trabajador sin tareas y confirmar estado vacio.
- Asignar una tarea a un trabajador y confirmar que aparece en su ficha.
- Confirmar que no aparecen botones de iniciar, completar, cancelar o asignar dentro del
  bloque contextual.
- Confirmar que no cambia `habitaciones.estado`.
- Confirmar que no se modifica `mantenimientos_habitaciones`.

## Rollback

- Revertir el commit de TLM-F.
- No ejecutar SQL.
- No borrar `tareas_operativas` ni `tarea_eventos`; pertenecen a TLM-A.
- No tocar mantenimiento historico ni Caja.

## Estado

`CONTEXTUAL_TLM_F_COMPLETADO_QA_DIFERIDA`

