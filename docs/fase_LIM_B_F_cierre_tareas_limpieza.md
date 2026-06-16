# Fase LIM-B-F - Cierre tecnico tareas desde limpieza

Estado: `BLOQUE_LIM_B_TAREAS_DESDE_LIMPIEZA_CERRADO_QA_DIFERIDA`.

## Alcance cerrado

- LIM-B-0: contrato para creacion manual de tareas de limpieza.
- LIM-B-A: implementacion manual controlada desde `/reportes/limpieza`.

## Commits del bloque

- `0165d5c docs(phase-lim): define manual housekeeping task contract`
- `c6d6743 feat(phase-lim): create housekeeping tasks manually`

## Revision tecnica

- La unica ruta nueva funcional del bloque es
  `POST /tareas/desde-limpieza/{id}`.
- La accion exige sesion, permiso `habitaciones.mantenimiento` y CSRF.
- La vista solo muestra el boton cuando la habitacion no tiene tarea activa de
  limpieza.
- El modelo valida `hotel_id`, habitacion activa del hotel actual y estado
  `limpieza`.
- El modelo bloquea duplicados activos por `hotel_id`, `habitacion_id` y
  categoria `limpieza`.
- La escritura queda limitada a `tareas_operativas`, `tarea_eventos` y
  auditoria.

## Auditoria de seguridad

- No libera habitaciones.
- No cambia `habitaciones.estado`.
- No crea tareas automaticas ni masivas.
- No descuenta inventario.
- No toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- No integra con checkout ni con liberacion de habitaciones.

## Verificacion automatica

- `php -l` en archivos PHP tocados: OK usando PHP del contenedor.
- `tools/saas/preflight_limpieza_operativa.php`: `OK 20`, `WARNING 0`,
  `ERROR 0`.
- `tools/saas/health_check_fase_1a.php`: `ERROR 0`, warnings conocidos.
- HTTP sin sesion a `POST /tareas/desde-limpieza/1`: `303` a `/login`.
- SQL read-only despues del intento sin sesion:
  - `tareas_operativas = 0`
  - `tarea_eventos = 0`
  - `tareas_limpieza = 0`
  - `movimientos_caja = 1403`
- `git diff --check`: sin errores reales; solo avisos normales de CRLF en
  Windows.

## Warnings conocidos

- El health checker mantiene warnings historicos no relacionados con LIM-B-A:
  docs/technical no montado en contenedor, migraciones historicas no visibles,
  tablas legacy congeladas y una habitacion en mantenimiento sin registro activo.
- QA manual queda diferida por instruccion del usuario.

## QA manual diferida

Cuando el usuario retome pruebas manuales:

1. Abrir `/reportes/limpieza`.
2. Elegir una habitacion en estado `limpieza` sin tarea activa.
3. Crear tarea desde el boton del reporte.
4. Confirmar redireccion al detalle de tarea.
5. Confirmar `categoria = limpieza`, `origen = limpieza_manual`,
   `habitacion_id` y `hotel_id`.
6. Intentar crear segunda tarea activa para la misma habitacion y confirmar
   bloqueo limpio.
7. Confirmar que la habitacion sigue en `limpieza`.
8. Confirmar que no hay Caja, pagos, abonos, nomina, inventario, offline ni
   `/api/sync`.

## Rollback

- Revertir `c6d6743 feat(phase-lim): create housekeeping tasks manually`.
- Si QA manual crea tareas reales, no borrar datos con SQL manual; usar
  cancelacion de tareas o documentar IDs para reconciliacion.
- DB/migraciones: no aplica; el bloque no crea ni altera tablas.

## Siguiente paso recomendado

No automatizar limpieza todavia. El siguiente bloque seguro debe ser otro
contrato controlado, por ejemplo un reporte/agenda read-only de tareas por
trabajador o una revision general de QA diferida acumulada.
