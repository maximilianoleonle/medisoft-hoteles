# Fase 5E-P-A - Trazabilidad fuerte de pago desde snapshot de pre-nomina

Estado formal:
`IMPLEMENTACION_5E_P_A_TRAZABILIDAD_PAGO_SNAPSHOT_PRENOMINA_QA_TECNICA_LOCAL_COMPLETADA`.

Fecha: 2026-06-22.

## Objetivo

Guardar una relacion fuerte y nullable entre pagos laborales reales registrados
con Caja y el snapshot administrativo de pre-nomina que sirvio como contexto.

La fase mantiene el snapshot inmutable y no convierte la pre-nomina en nomina
oficial.

## Alcance implementado

- Migracion local:
  `migrations/20260622_001_fase_5e_p_a_trazabilidad_pago_snapshot.sql`.
- Columnas nuevas nullable en `trabajador_pagos_caja`:
  - `nomina_periodo_id`;
  - `nomina_periodo_detalle_id`.
- Indices nuevos:
  - `idx_trabajador_pagos_caja_nomina_periodo`;
  - `idx_trabajador_pagos_caja_nomina_detalle`.
- FKs restrictivas:
  - `fk_trabajador_pagos_caja_nomina_periodo`;
  - `fk_trabajador_pagos_caja_nomina_detalle`.
- `TrabajadorPagoCajaService` acepta trazabilidad opcional y valida:
  - periodo y detalle juntos;
  - columnas disponibles si se requiere trazabilidad;
  - periodo/detalle del mismo hotel;
  - detalle perteneciente al periodo;
  - trabajador del detalle igual al trabajador pagado;
  - snapshot aprobado;
  - detalle en estado `por_pagar`.
- `TrabajadorNominaSnapshotPagoService` envia `nomina_periodo_id` y
  `nomina_periodo_detalle_id` al registrar el pago individual desde snapshot.
- `probar_pago_snapshot_prenomina_caja.php` valida la relacion temporal y luego
  revierte todo con rollback.
- `preflight_personal_pagos_caja.php` y `health_check_fase_1a.php` validan
  columnas, indices, FKs, registro en `migrations` y consistencia relacional.

## Fuera de alcance

No se implementa:

- backfill historico;
- recalculo de snapshots;
- reapertura de snapshots;
- nomina oficial;
- CFDI;
- timbrado;
- dispersion bancaria;
- pago masivo;
- liquidacion automatica de anticipos o prestamos;
- cambios en PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Ejecucion local

Backup previo local:

```text
backups/db/20260622_102558_medisoft_hoteles_import_pre_5e_p_a.sql
```

Base local:

```text
medisoft_hoteles_import
```

Migracion registrada en `migrations`:

```text
20260622_001_fase_5e_p_a_trazabilidad_pago_snapshot.sql
estado: ejecutada
batch: 27
```

## QA tecnica

Lint PHP:

```text
app/services/TrabajadorPagoCajaService.php: OK
app/services/TrabajadorNominaSnapshotPagoService.php: OK
tools/saas/probar_pago_snapshot_prenomina_caja.php: OK
tools/saas/preflight_personal_pagos_caja.php: OK
tools/saas/health_check_fase_1a.php: OK
```

Prueba rollback:

```text
tools/saas/probar_pago_snapshot_prenomina_caja.php
```

Resultado:

```text
Trazabilidad 5E-P-A temporal ligada a periodo #5 y detalle #5.
Snapshot temporal permanecio inmutable tras registrar pago.
Rollback confirmado: pago, Caja, auditoria y snapshot quedaron iguales.
```

Preflight:

```text
OK: 72
WARNING: 0
ERROR: 0
```

Health general:

```text
OK: 327
WARNING: 25
ERROR: 0
```

Las advertencias del health son historicas o de montaje documental/migrations y
no bloquean esta fase.

## Rollback

Rollback de datos de QA:

- La prueba automatica ya usa transaccion externa y `rollBack`.
- No deja pagos, movimientos, auditoria ni snapshots temporales.

Rollback de migracion local:

Ejecutar solo con autorizacion explicita y backup verificado.

Primero validar que no existan pagos dependientes:

```sql
SELECT COUNT(*) AS pagos_snapshot_trazados
FROM trabajador_pagos_caja
WHERE nomina_periodo_id IS NOT NULL
   OR nomina_periodo_detalle_id IS NOT NULL;
```

Si el conteo es `0`, se puede retirar estructura:

```sql
ALTER TABLE trabajador_pagos_caja
  DROP FOREIGN KEY fk_trabajador_pagos_caja_nomina_detalle;

ALTER TABLE trabajador_pagos_caja
  DROP FOREIGN KEY fk_trabajador_pagos_caja_nomina_periodo;

ALTER TABLE trabajador_pagos_caja
  DROP INDEX idx_trabajador_pagos_caja_nomina_detalle;

ALTER TABLE trabajador_pagos_caja
  DROP INDEX idx_trabajador_pagos_caja_nomina_periodo;

ALTER TABLE trabajador_pagos_caja
  DROP COLUMN nomina_periodo_detalle_id,
  DROP COLUMN nomina_periodo_id;

DELETE FROM migrations
WHERE nombre = '20260622_001_fase_5e_p_a_trazabilidad_pago_snapshot.sql';
```

Si existen pagos trazados, no eliminar columnas sin fase formal de
reconciliacion.
