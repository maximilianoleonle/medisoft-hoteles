# Fase TLM-G - Health y preflight de consistencia de tareas

## Objetivo

Agregar verificaciones tecnicas para detectar inconsistencias de tareas operativas antes
de automatizar limpieza, mantenimiento o turnos.

Esta fase no agrega funcionalidades de usuario.

## Alcance implementado

- Nuevo preflight read-only: `src/tools/saas/preflight_tareas_operativas.php`.
- Health checker actualizado a TLM-G.
- Checks de consistencia para:
  - tareas sin `hotel_id`;
  - eventos sin `hotel_id`;
  - eventos sin tarea;
  - eventos con hotel distinto a la tarea;
  - estados, categorias y prioridades invalidas;
  - fechas incoherentes;
  - tareas cerradas sin `fecha_cierre`;
  - tareas en proceso sin `fecha_inicio`;
  - tareas activas con `fecha_cierre`;
  - tareas sin evento inicial `creada`;
  - entidades vinculadas inexistentes;
  - entidades vinculadas de otro hotel;
  - `huesped_id` directo sin scope por hotel;
  - tareas activas asignadas a trabajadores inactivos;
  - movimientos de Caja con referencia textual a tarea operativa.
- Checks estaticos de rutas, controlador, modelo y partial contextual.

## Seguridad

- Solo lectura.
- Usa `START TRANSACTION READ ONLY`.
- No crea rutas.
- No crea migraciones.
- No escribe datos.
- No toca `habitaciones.estado`.
- No toca `mantenimientos_habitaciones`.
- No toca Caja, pagos, abonos, nomina, PWA/offline/cache ni `/api/sync`.

## Decision sobre huespedes

La tabla `huespedes` actual no tiene `hotel_id`. Por seguridad, TLM-G deja el vinculo
directo por `huesped_id` como inconsistencia detectable si aparece. Para tareas ligadas a
un huesped, la ruta segura futura debe pasar por una entidad con hotel, como
`reservaciones`.

## Definition of Done

- `php -l` pasa para health checker y preflight TLM-G.
- Health checker ejecuta con `ERROR: 0`.
- Preflight TLM-G ejecuta con `ERROR: 0`.
- Preflights de compras existentes siguen sin errores.
- SQL read-only confirma conteos sin escrituras.
- `git diff --check` pasa.

## QA manual diferida

No hay QA visual nueva porque TLM-G no agrega interfaz.

Cuando el usuario haga QA general de TLM, conviene ejecutar:

```bash
docker compose exec -T app php /var/www/html/tools/saas/preflight_tareas_operativas.php
```

## Rollback

- Revertir el commit de TLM-G.
- No ejecutar SQL.
- No tocar `tareas_operativas` ni `tarea_eventos`.
- No tocar Caja, mantenimiento historico ni `/api/sync`.

## Estado

`PREFLIGHT_TLM_G_COMPLETADO_QA_DIFERIDA`
