# Fase MANT-C-F - Cierre tecnico mantenimiento programado

Estado: `BLOQUE_MANT_C_MANTENIMIENTO_PROGRAMADO_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- Contrato: `docs/fase_MANT_C_0_contrato_mantenimiento_programado.md`.
- Implementacion: `docs/fase_MANT_C_A_guardrails_mantenimiento_programado.md`.
- Controlador: `src/app/controllers/HabitacionController.php`.
- Modelo: `src/app/models/Mantenimiento.php`.
- Preflight: `src/tools/saas/preflight_mantenimiento_operativo.php`.
- Health general: `src/tools/saas/health_check_fase_1a.php`.

## Confirmaciones tecnicas

- No se crearon rutas nuevas.
- `POST /habitaciones/{id}/programar-mantenimiento` mantiene CSRF y permiso.
- `POST /habitaciones/cancelar-mantenimiento-programado/{id}` mantiene CSRF y permiso.
- La programacion valida fecha real, catalogos, motivo y solapes por habitacion/hotel.
- La cancelacion valida mantenimiento scoped y habitacion del hotel actual.
- `Mantenimiento::activarMantenimientosPendientes()` sigue desconectado.
- No hay migraciones ni cambios directos de datos.
- No hay Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Verificacion automatica

- `php -l app/controllers/HabitacionController.php`: OK.
- `php -l app/models/Mantenimiento.php`: OK.
- `php -l tools/saas/preflight_mantenimiento_operativo.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- `php tools/saas/preflight_mantenimiento_operativo.php`: `OK: 15`, `WARNING: 1`, `ERROR: 0`.
- `php tools/saas/health_check_fase_1a.php`: `OK: 253`, `WARNING: 24`, `ERROR: 0`.
- POST sin sesion a `/habitaciones/1/programar-mantenimiento`: `303` a `/login`.
- POST sin sesion a `/habitaciones/cancelar-mantenimiento-programado/1`: `303` a `/login`.
- SQL read-only: `mantenimientos_total=10`, `mantenimientos_programados=1`,
  `solapes_programados=0`, `cuentas_por_pagar_movimientos=0`.
- `git diff --check`: sin errores.

## Warnings conocidos

- Existe 1 habitacion en estado `mantenimiento` sin registro `en_proceso`; queda como
  warning historico y no se corrige automaticamente.
- Warnings historicos del health general siguen documentados y no bloquean MANT-C.

## QA manual diferida

1. Programar mantenimiento con fechas validas.
2. Probar fecha invalida, fecha pasada y fin anterior a inicio.
3. Probar tipo/prioridad alterados por POST manual.
4. Probar motivo vacio.
5. Probar solape con otro mantenimiento programado.
6. Probar conflicto con reservacion.
7. Cancelar mantenimiento programado.
8. Confirmar que no se activa ningun mantenimiento automaticamente.
9. Confirmar ausencia de Caja, pagos, abonos, nomina, offline y `/api/sync`.

## Rollback

- Revertir `fix(phase-mant): harden scheduled maintenance actions`.
- Revertir `docs(phase-mant): close scheduled maintenance guardrails`.
- DB: no aplica; el cierre no crea migraciones.
- Si QA manual crea registros, usar cancelacion autorizada; no borrar con SQL manual.

