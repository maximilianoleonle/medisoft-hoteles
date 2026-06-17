# Fase MANT-G-F - Cierre tecnico tareas desde mantenimiento

Estado: `BLOQUE_MANT_G_TAREAS_DESDE_MANTENIMIENTO_CERRADO_QA_DIFERIDA`.

## Alcance cerrado

- MANT-G-0: contrato de integracion entre mantenimiento y tareas.
- MANT-G-A: lectura contextual de tareas vinculadas a mantenimiento en el preview.
- MANT-G-B-0: contrato de creacion manual de tarea desde mantenimiento.
- MANT-G-B-A: creacion manual individual de tarea desde mantenimiento.

## Commits del bloque

- `e07e321 docs(phase-mant): define maintenance task linkage contract`
- `1d33ee8 feat(phase-mant): show maintenance linked tasks`
- `46fcf70 docs(phase-mant): define manual maintenance task creation contract`
- `8841430 feat(phase-mant): create tasks from maintenance manually`

## Revision tecnica

- La ruta nueva autorizada es solo `POST /tareas/desde-mantenimiento/{id}`.
- La accion requiere sesion, permiso existente, metodo POST y CSRF.
- La creacion queda centralizada en `TareaOperativa::crearDesdeMantenimientoParaHotel()`.
- El modelo valida `hotel_id`, mantenimiento existente, habitacion del mismo hotel y
  bloqueo de duplicado activo por mantenimiento.
- La escritura usa transaccion y crea solo `tareas_operativas` y `tarea_eventos`.
- El preview muestra tareas vinculadas por `mantenimiento_id`, tarea activa existente y
  accion manual solo cuando no hay tarea activa vinculada.

## Auditoria de seguridad

- No hay creacion automatica de tareas.
- No hay acciones masivas.
- No hay cron.
- No hay cambios de estado en `habitaciones`.
- No hay cambios de estado en `mantenimientos_habitaciones`.
- No hay Caja, pagos, abonos, nomina, CxP operativa, offline ni `/api/sync`.
- `/api/sync` sigue validado por health checker como bloqueado con HTTP 423 en codigo.

## Verificacion automatica

- `php -l` en archivos PHP tocados: OK.
- `tools/saas/health_check_fase_1a.php`: `ERROR: 0`, warnings conocidos.
- `tools/saas/preflight_tareas_operativas.php`: `ERROR: 0`, `WARNING: 0`.
- `tools/saas/preflight_mantenimiento_operativo.php`: `ERROR: 0`, `WARNING: 1`.
- HTTP sin sesion a `POST /tareas/desde-mantenimiento/1`: redirige a login.
- SQL read-only previo/posterior a intento sin sesion: sin escrituras en tareas,
  eventos, Caja ni CxP movimientos.
- `git diff --check`: sin errores; solo avisos normales de CRLF en Windows.

## Warning conocido

- Existe un warning historico: una habitacion en `mantenimiento` sin registro
  `en_proceso` en `mantenimientos_habitaciones`.
- No se corrige automaticamente porque puede representar estado historico/manual.

## QA manual diferida

Por instruccion del usuario, la QA manual queda diferida temporalmente. Cuando se ejecute,
probar:

1. Abrir `/reportes/mantenimiento-programado`.
2. Confirmar tareas vinculadas y estado vacio.
3. Crear una tarea desde un mantenimiento controlado.
4. Confirmar detalle de tarea, `hotel_id`, `habitacion_id`, `mantenimiento_id` y
   `origen = mantenimiento`.
5. Intentar crear una segunda tarea activa para el mismo mantenimiento y confirmar
   bloqueo limpio.
6. Confirmar que no cambian habitacion ni mantenimiento.
7. Confirmar que no hay Caja, pagos, abonos, nomina ni cambios en `/api/sync`.

## Rollback

- Revertir el commit de cierre documental si solo se desea retirar este documento.
- Para retirar la funcionalidad MANT-G-B-A, revertir
  `8841430 feat(phase-mant): create tasks from maintenance manually`.
- Si ya se crearon tareas reales en QA, no borrar datos con SQL manual; usar el flujo
  existente de cancelacion de tareas o documentar IDs.

## Siguiente paso recomendado

No automatizar tareas desde mantenimiento todavia. El siguiente bloque seguro puede ser
QA manual MANT-G o un contrato independiente para automatizacion futura, con backup y
criterios explicitos.
