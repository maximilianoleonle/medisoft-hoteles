# Fase TLM-J-F - Cierre tecnico agenda de tareas

Estado: `BLOQUE_TLM_J_AGENDA_TAREAS_CERRADO_QA_DIFERIDA`.

## Alcance cerrado

- TLM-J-0: contrato de agenda de tareas por trabajador.
- TLM-J-A: implementacion GET/read-only de `/tareas/agenda`.

## Commits del bloque

- `968c4be docs(phase-tlm): define worker task agenda contract`
- `8c035a7 feat(phase-tlm): add worker task agenda`

## Revision tecnica

- La ruta nueva es solo `GET /tareas/agenda`.
- La accion `TareaController::agendaAction()` reutiliza guardas existentes de tareas:
  sesion, contexto hotel, modulo `habitaciones` y permiso `habitaciones.view`.
- El modelo `TareaOperativa::agendaReadOnlyPorHotel()` filtra por `hotel_id`.
- El rango de fechas se normaliza y queda limitado a 31 dias.
- La vista usa solo formulario GET y enlaces GET a tarea, trabajador y habitacion.
- No hay POST ni CSRF en la agenda porque no existe escritura.

## Auditoria de seguridad

- No crea tareas.
- No asigna trabajadores.
- No inicia, completa ni cancela tareas.
- No cambia `habitaciones.estado`.
- No activa mantenimientos.
- No descuenta inventario.
- No toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Verificacion automatica

- `php -l` en PHP tocado: OK.
- `tools/saas/preflight_tareas_operativas.php`: `OK 52`, `WARNING 0`,
  `ERROR 0`.
- `tools/saas/health_check_fase_1a.php`: `ERROR 0`, warnings conocidos.
- HTTP sin sesion a `/tareas/agenda`: `303` a `/login`.
- SQL read-only:
  - `tareas_operativas = 0`
  - `tarea_eventos = 0`
  - `movimientos_caja = 1403`
  - `trabajadores = 0`
- `git diff --check`: sin errores reales; solo avisos normales de CRLF en Windows.

## Warnings conocidos

- La base local no tiene trabajadores ni tareas, por lo que la agenda queda lista con
  estado vacio hasta que existan datos operativos.
- El health conserva warnings historicos no relacionados: docs/technical no montado,
  migraciones historicas no visibles, tablas legacy congeladas y una habitacion en
  mantenimiento sin registro activo.
- QA manual queda diferida por instruccion del usuario.

## QA manual diferida

1. Abrir `/tareas/agenda`.
2. Confirmar estado vacio si no hay trabajadores/tareas.
3. Cuando existan tareas, filtrar por fecha, trabajador, categoria y estado.
4. Confirmar enlaces GET a tarea, trabajador y habitacion.
5. Confirmar que no hay acciones de asignar, iniciar, completar o cancelar en agenda.
6. Confirmar que no se toca Caja, nomina, offline ni `/api/sync`.

## Rollback

- Revertir `8c035a7 feat(phase-tlm): add worker task agenda`.
- Para retirar solo el contrato, revertir
  `968c4be docs(phase-tlm): define worker task agenda contract`.
- DB: no aplica; el bloque no crea migraciones ni datos.

## Siguiente paso recomendado

Con TLM-J cerrado tecnicamente, el siguiente bloque seguro puede ser un contrato
independiente de planeacion/turnos read-only o una revision de QA diferida acumulada.
