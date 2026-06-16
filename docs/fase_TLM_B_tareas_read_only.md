# Fase TLM-B - Tareas operativas read-only

Estado: `TAREAS_READ_ONLY_TLM_B_COMPLETADO_QA_DIFERIDA`

Fecha: 2026-06-16

## Objetivo

Agregar la primera capa de consulta para tareas operativas sin permitir todavia crear,
asignar, iniciar, completar, cancelar ni modificar tareas.

## Alcance implementado

- Modelo read-only `TareaOperativa`.
- Controlador GET `TareaController`.
- Rutas:
  - `GET /tareas`;
  - `GET /tareas/{id}`.
- Vistas:
  - `app/views/tareas/index.php`;
  - `app/views/tareas/ver.php`.
- Navegacion en sidebar bajo Operaciones.
- Validaciones en health checker.

## Guardas

- Sesion requerida.
- Contexto de hotel requerido.
- Modulo `habitaciones` requerido.
- Permiso `habitaciones.view` requerido.

## Reglas de datos

- Todas las consultas filtran por `hotel_id`.
- Los joins con habitaciones, trabajadores, huespedes y mantenimientos se hacen por el
  mismo hotel.
- El detalle usa `id + hotel_id`.
- La vista no muestra rutas internas ni datos privados de archivos.

## Fuera de alcance

- POST.
- Formularios de creacion.
- Asignacion a trabajadores.
- Cambiar estado de tarea.
- Cambiar `habitaciones.estado`.
- Modificar `mantenimientos_habitaciones`.
- Caja, pagos, abonos, nomina.
- PWA/offline/cache y `/api/sync`.

## Verificacion automatica

- `php -l` en modelo, controlador, vistas, rutas y checker.
- `health_check_fase_1a.php`: `ERROR: 0`.
- HTTP sin sesion:
  - `/tareas` -> 303 login.
  - `/tareas/1` -> 303 login.
- SQL read-only confirma tablas TLM en 0 registros desde TLM-A.

## QA manual diferida

- Abrir `/tareas` con usuario autorizado.
- Confirmar estado vacio claro.
- Probar filtros GET.
- Confirmar que no hay botones de crear/asignar/iniciar/completar/cancelar.
- Confirmar que `/tareas/{id}` solo muestra metadata si existe una tarea futura.
- Confirmar que habitaciones/mantenimiento/Caja no cambian.

## Siguiente accion recomendada

`[COLA_TLM_C_CREACION_MANUAL_TAREAS]`

Implementar creacion manual controlada con POST + CSRF, auditoria y validacion de
hotel, sin cambiar automaticamente `habitaciones.estado`.
