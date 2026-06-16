# Fase TLM-H - Revision, auditoria y cierre tecnico del bloque TLM

## Objetivo

Cerrar tecnicamente el bloque Tareas, Limpieza y Mantenimiento base, sin agregar nuevas
funcionalidades y dejando QA manual diferida por instruccion del usuario.

## Fases incluidas

- TLM-0 contrato y diagnostico.
- TLM-A migracion base aditiva.
- TLM-B capa read-only.
- TLM-C creacion manual controlada.
- TLM-D asignacion opcional a trabajador.
- TLM-E estados manuales.
- TLM-F contexto visual en habitacion/trabajador.
- TLM-G health y preflight de consistencia.

## Commits

- `90fffab` - `docs(phase-tlm): define tasks housekeeping maintenance contract`
- `185b64b` - `feat(phase-tlm): add operational tasks base schema`
- `17c07fb` - `feat(phase-tlm): add read-only operational tasks layer`
- `6015ef3` - `feat(phase-tlm): create operational tasks manually`
- `581a711` - `feat(phase-tlm): assign tasks to workers`
- `5794dd4` - `feat(phase-tlm): manage task lifecycle manually`
- `093d916` - `feat(phase-tlm): show contextual operational tasks`
- `ddcde28` - `test(phase-tlm): add operational task consistency checks`

## Revision tecnica

- Rutas TLM autorizadas registradas:
  - `GET /tareas`;
  - `GET /tareas/crear`;
  - `POST /tareas`;
  - `GET /tareas/{id:[0-9]+}`;
  - `POST /tareas/{id:[0-9]+}/asignar`;
  - `POST /tareas/{id:[0-9]+}/iniciar`;
  - `POST /tareas/{id:[0-9]+}/completar`;
  - `POST /tareas/{id:[0-9]+}/cancelar`.
- Controlador con sesion, contexto hotelero, modulo `habitaciones`, permisos y CSRF en
  acciones POST.
- Modelo con operaciones transaccionales para alta, asignacion y estados.
- Vistas de tareas con formularios operativos solo en detalle/formulario de tareas.
- Partial contextual read-only sin formularios ni acciones operativas.
- Health/preflight TLM-G validan consistencia.

## Auditoria de seguridad

- Sin cambios en Caja.
- Sin pagos.
- Sin abonos.
- Sin nomina.
- Sin asistencia laboral.
- Sin cambios en `habitaciones.estado`.
- Sin cambios en `mantenimientos_habitaciones`.
- Sin cambios en PWA/offline/cache/IndexedDB.
- Sin cambios en `/api/sync`.
- `huesped_id` directo queda bloqueado por preflight porque `huespedes` no tiene
  `hotel_id`.

## Verificaciones automaticas de cierre

- `php -l` en archivos PHP tocados de TLM-F/TLM-G.
- `health_check_fase_1a.php`: `ERROR: 0`.
- `preflight_tareas_operativas.php`: `ERROR: 0`.
- `preflight_compras_minimas.php`: `ERROR: 0`.
- `preflight_recepcion_compras.php`: `ERROR: 0`.
- SQL read-only confirma:
  - `tareas_operativas = 0`;
  - `tarea_eventos = 0`;
  - `trabajadores = 0`;
  - `mantenimientos_habitaciones = 10`;
  - `movimientos_caja = 1403`.
- Rutas `/tareas` y `/tareas/1` sin sesion redirigen a login.
- `git diff --check` sin errores, solo warnings CRLF conocidos.

## QA manual diferida

El usuario pidio omitir QA manual por ahora y continuar con bloques necesarios.

QA pendiente recomendada:

- Crear tarea manual.
- Ver tarea en listado/detalle.
- Asignar trabajador activo.
- Iniciar, completar y cancelar tareas.
- Ver tareas contextuales en habitacion y trabajador.
- Confirmar que disponibilidad de habitacion no cambia por tareas.
- Confirmar que mantenimiento historico sigue funcionando.
- Confirmar que Caja no recibe movimientos.

## Estado final

`BLOQUE_TLM_CERRADO_QA_DIFERIDA`

