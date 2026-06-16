# Fase MANT-E-F - Cierre tecnico activacion manual de mantenimiento

Estado: `BLOQUE_MANT_E_ACTIVACION_MANUAL_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- MANT-E-0 contrato de activacion manual.
- MANT-E-A POST individual controlado para activar mantenimientos vencidos o de hoy.
- Guardrails en health y preflight.
- Documentacion de QA, rollback, fuentes de verdad, decisiones y auditoria.

## Revision tecnica

- Ruta POST registrada:
  `/habitaciones/activar-mantenimiento-programado/{id}`.
- No se agrega activacion masiva.
- No se llama `Mantenimiento::activarMantenimientosPendientes()`.
- El controlador exige sesion, CSRF y permiso `habitaciones.mantenimiento`.
- El modelo centraliza validaciones y escritura transaccional.
- La vista muestra boton solo si el registro es candidato y el usuario tiene permiso.

## Auditoria de seguridad

- Se bloquea mantenimiento futuro.
- Se bloquea mantenimiento no programado o de otro hotel.
- Se bloquea habitacion no disponible.
- Se bloquea doble mantenimiento `en_proceso`.
- Se bloquean reservaciones conflictivas.
- Se auditan intentos exitosos y bloqueados si `logs_auditoria` esta disponible.
- No se toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Verificaciones ejecutadas

- `php -l app/models/Mantenimiento.php`: OK.
- `php -l app/controllers/HabitacionController.php`: OK.
- `php -l app/views/reportes/mantenimiento-programado.php`: OK.
- `php -l tools/saas/preflight_mantenimiento_operativo.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- `php tools/saas/preflight_mantenimiento_operativo.php`: `ERROR: 0`.
- `php tools/saas/health_check_fase_1a.php`: `ERROR: 0`.
- HTTP POST sin sesion a la ruta nueva: 303 a `/login`.
- SQL read-only confirma que el POST sin sesion no cambio conteos.
- `git diff --check`: sin errores; solo warnings CRLF.

## Warning residual

- 1 habitacion historica esta en estado `mantenimiento` sin registro activo
  `en_proceso`.
- Queda documentado como warning; no se corrige automaticamente.

## QA manual diferida

- No se ejecuto activacion real desde automatizacion.
- Antes de QA manual: crear backup de DB.
- QA debe confirmar activacion de un candidato controlado, doble activacion bloqueada,
  habitacion en mantenimiento y ausencia de Caja/pagos/abonos/offline.

## Rollback

- Revertir commit `feat(phase-mant): activate overdue scheduled maintenance manually`.
- Revertir commit `docs(phase-mant): close manual maintenance activation block`.
- DB: no aplica si no se activo ningun candidato real.
- Si QA manual activo datos, no borrar con SQL; revertir solo con flujos autorizados y
  documentar IDs.

## Siguiente paso recomendado

- No avanzar a activacion automatica ni cron.
- La siguiente fase segura debe ser independiente o comenzar con contrato nuevo.
