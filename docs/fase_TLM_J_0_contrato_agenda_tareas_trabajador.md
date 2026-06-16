# Fase TLM-J-0 - Contrato agenda de tareas por trabajador

Estado: `CONTRATO_TLM_J_0_AGENDA_TAREAS_TRABAJADOR_COMPLETADO`.

## Objetivo

Definir una fase futura GET/read-only para visualizar la carga operativa de tareas por
trabajador y fecha, conectando Personal, Tareas, Limpieza y Mantenimiento sin crear
acciones nuevas.

## Alcance permitido futuro

- Ruta GET/read-only sugerida: `/tareas/agenda`.
- Filtros GET:
  - fecha o rango corto;
  - trabajador;
  - categoria (`limpieza`, `mantenimiento`, `general`);
  - estado.
- Mostrar:
  - tareas activas y cerradas en el rango;
  - trabajador asignado;
  - habitacion vinculada;
  - mantenimiento vinculado cuando exista;
  - origen de tarea;
  - enlaces GET al detalle de tarea, trabajador y habitacion.
- Estado vacio claro.
- Lectura scoped por `hotel_id`.

## Prohibido

- No crear tareas.
- No asignar trabajadores.
- No iniciar, completar ni cancelar tareas.
- No cambiar `habitaciones.estado`.
- No activar mantenimientos.
- No liberar habitaciones.
- No descontar inventario.
- No tocar Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- No crear migraciones.

## Semaforo de riesgo

- Verde: GET/read-only, filtros por fecha/trabajador/categoria, enlaces a vistas
  existentes.
- Amarillo: filtros con rangos amplios o joins con tablas historicas grandes; limitar
  rango por defecto y paginar si hace falta.
- Rojo: cualquier POST operativo, automatizacion de limpieza/mantenimiento o escritura
  sobre habitaciones, inventario, Caja, nomina u offline.

## Definition of Done futura

- Ruta GET registrada y protegida por las guardas existentes de tareas.
- Modelo consulta solo `tareas_operativas`, `trabajadores`, `habitaciones` y
  `mantenimientos_habitaciones` con `hotel_id`.
- Vista sin formularios POST.
- Sin `storage_path`, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Preflight o health valida ruta, ausencia de POST y consultas scoped.
- `php -l`, health, preflight, HTTP sin sesion y SQL read-only ejecutados.

## QA manual futura

1. Abrir `/tareas/agenda`.
2. Filtrar por fecha actual.
3. Filtrar por trabajador.
4. Validar que las tareas de limpieza y mantenimiento aparecen con enlaces correctos.
5. Confirmar que no hay botones de asignar/iniciar/completar/cancelar en la agenda.
6. Confirmar que no se modifica habitacion, mantenimiento, Caja, nomina, offline ni
   `/api/sync`.

## Rollback

- TLM-J-0 es documental; rollback: revertir el commit
  `docs(phase-tlm): define worker task agenda contract`.
- DB: no aplica.
- Codigo: no aplica hasta la fase TLM-J-A.

## Siguiente subfase segura

TLM-J-A: implementacion GET/read-only de `/tareas/agenda`, si se autoriza continuar.
