# Fase 5E-Q-A - Conciliacion read-only de pagos desde snapshot de pre-nomina

## Objetivo

Agregar una pantalla local de auditoria para revisar pagos laborales con Caja que
quedaron trazados a un snapshot persistente de pre-nomina.

La pantalla no registra pagos, no revierte pagos, no modifica Caja y no cambia el
snapshot. Solo lee:

- `trabajador_pagos_caja`
- `trabajador_nomina_periodos`
- `trabajador_nomina_periodo_detalles`
- `movimientos_caja`
- `cortes_caja`
- `cajas`

## Alcance implementado

- Ruta GET:
  - `/trabajadores/nomina/periodos/pagos-snapshot`
  - `/trabajadores/nomina/periodos/pagos-snapshot/exportar`
- Controlador:
  - `TrabajadorController::reporteNominaPagosSnapshotAction`
  - `TrabajadorController::exportarNominaPagosSnapshotAction`
  - `TrabajadorController::descargarNominaPagosSnapshotCsv`
- Modelo:
  - `Trabajador::tablasReporteNominaPagosSnapshotDisponibles`
  - `Trabajador::reporteNominaPagosSnapshotPorHotel`
- Vista:
  - `app/views/trabajadores/nomina_pagos_snapshot_reporte.php`
- Enlaces:
  - Desde `nomina_periodos.php`
  - Desde `nomina_periodo_detalle.php`
- Checkers:
  - `preflight_personal_pagos_caja.php`
  - `health_check_fase_1a.php`

## Conciliacion

Cada fila marca `OK` cuando:

- El pago apunta a `nomina_periodo_id` y `nomina_periodo_detalle_id`.
- El snapshot existe y pertenece al mismo hotel.
- El detalle existe, pertenece al snapshot y corresponde al mismo trabajador.
- El movimiento de Caja existe, pertenece al mismo hotel y corte.
- El monto, referencia, tipo `gasto` y categoria `Pago laboral` coinciden.
- Si el pago esta revertido, existe el movimiento de reversion esperado.

Si alguna condicion falla, la fila queda como `Revisar`.

## QA tecnica local

- `php -l` OK:
  - `config/routes.php`
  - `app/models/Trabajador.php`
  - `app/controllers/TrabajadorController.php`
  - `app/views/trabajadores/nomina_pagos_snapshot_reporte.php`
  - `tools/saas/preflight_personal_pagos_caja.php`
  - `tools/saas/health_check_fase_1a.php`
- `preflight_personal_pagos_caja.php`: OK 74, WARNING 0, ERROR 0.
- `health_check_fase_1a.php`: OK 328, WARNING 25, ERROR 0.

## Dato validado

Pago local trazado:

- `trabajador_pagos_caja.id = 9`
- `referencia = TEST-5EPA-001`
- `nomina_periodo_id = 4`
- `nomina_periodo_detalle_id = 4`
- `movimientos_caja.id = 1510`
- `conciliacion = ok`

## Limites

- No se toco produccion.
- No se agregaron migraciones.
- No se tocaron PWA/offline, IndexedDB, cache names ni `/api/sync`.
- No se agregaron acciones POST.
- El CSV se genera en memoria; no escribe en storage.
