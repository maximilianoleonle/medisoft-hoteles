# Fase NP-C-E - Preflight de consistencia del ledger laboral

Estado: `PREFLIGHT_LEDGER_NP_C_E_COMPLETADO_QA_DIFERIDA`

## Objetivo

Agregar un verificador tecnico read-only para detectar inconsistencias en las tablas de
Personal antes de habilitar escrituras futuras de conceptos, anticipos, prestamos o
asistencias.

## Implementacion

- Nuevo script CLI:
  - `src/tools/saas/preflight_personal_ledger.php`
- Health checker actualizado para detectar el preflight NP-C-A/NP-C-E.

## Validaciones

- Existencia de tablas `trabajador_*`.
- `hotel_id` presente y no nulo.
- Movimientos hijos con trabajador existente.
- Movimientos hijos con trabajador del mismo hotel.
- Montos invalidos.
- Saldos de anticipos/prestamos mayores al monto o negativos.
- Conceptos laborales con tipo, efecto o estado invalido.
- Asistencias sin fecha o duplicadas por trabajador/dia.
- Ausencia de categoria `Nomina` en Caja.
- Ausencia de movimientos de Caja con categoria `Nomina`.
- Ausencia de rutas operativas de pagos, anticipos, prestamos, asistencia, nomina o Caja.
- Ausencia de escrituras de ledger/Caja en `Trabajador.php` y `TrabajadorController.php`.

## Seguridad

- CLI-only.
- Usa `START TRANSACTION READ ONLY`.
- No corrige datos automaticamente.
- No crea rutas, vistas, migraciones ni registros.
- No toca Caja ni `/api/sync`.

## Rollback

- Revertir el commit `test(phase-np): add worker ledger consistency checks`.
- DB: no aplica; la subfase no escribe datos.
- No tocar tablas `trabajador_*`, Caja ni `/api/sync`.
