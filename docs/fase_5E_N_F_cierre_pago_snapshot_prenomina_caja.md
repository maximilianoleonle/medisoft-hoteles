# Fase 5E-N-F - Cierre pago desde snapshot de pre-nomina con Caja

Estado formal:
`CIERRE_5E_N_F_PAGO_SNAPSHOT_PRENOMINA_CAJA_QA_MANUAL_VALIDADA`.

## Objetivo

Cerrar documentalmente la implementacion 5E-N-A despues de QA tecnica local y
prueba manual validada por el usuario.

Este cierre es documental. No agrega codigo, rutas, modelos, servicios, vistas,
migraciones, permisos, datos, storage, Caja, PWA/offline ni `/api/sync`.

## Alcance cerrado

Queda validado en local el flujo de pago individual desde snapshot aprobado de
pre-nomina hacia Caja:

- El snapshot aprobado habilita pago individual por trabajador elegible.
- El pago usa CSRF y token de un solo uso.
- El monto maximo se calcula con saldo vivo:

```text
min(pendiente_pago_sugerido_snapshot, saldo_laboral_disponible_vivo)
```

- El pago real queda en `trabajador_pagos_caja`.
- El egreso queda en `movimientos_caja`.
- La auditoria registra el origen desde snapshot.
- El snapshot permanece inmutable.

## Evidencia QA tecnica

Lint PHP dentro de Docker: OK en archivos tocados.

Rollback local:

```bash
docker compose exec -T app env APP_ENV=local php tools/saas/probar_pago_snapshot_prenomina_caja.php
```

Resultado: pago y movimiento Caja temporales creados dentro de transaccion,
snapshot temporal inmutable y rollback completo sin persistencia.

Preflight:

```bash
docker compose exec -T app env APP_ENV=local php tools/saas/preflight_personal_pagos_caja.php
```

Resultado: `OK: 65`, `WARNING: 0`, `ERROR: 0`.

Health general:

```bash
docker compose exec -T app env APP_ENV=local php tools/saas/health_check_fase_1a.php
```

Resultado: `OK: 320`, `WARNING: 25`, `ERROR: 0`.

Las advertencias del health corresponden a elementos historicos o de montaje de
documentacion/migrations y no bloquean 5E-N-A.

## Evidencia QA manual

El usuario ejecuto el flujo en local y confirmo visualmente:

1. Periodo manual `20/06/2026 - 20/06/2026` evaluado con trabajador Panfilo
   Hernandez por `$100.00`.
2. Snapshot persistente `#4` cerrado correctamente sin pago ni movimiento Caja.
3. Snapshot `#4` aprobado administrativamente.
4. Formulario `Pago Caja` visible solo despues de aprobar.
5. Pago individual registrado por `$1.00`.
6. Mensaje de exito:
   `Pago desde snapshot registrado. Movimiento Caja #1507. El snapshot permanece inmutable.`
7. Ficha del trabajador muestra saldo disponible vivo de `$99.00`.

Lectura posterior local:

- `trabajador_pagos_caja.id = 6`.
- `movimientos_caja.id = 1507`.
- `corte_id = 238`.
- `monto = 1.00`.
- `metodo_pago = efectivo`.
- `referencia = TEST-5ENA-001`.
- `concepto = Pago desde snapshot pre-nomina #4 detalle #4`.
- Snapshot `#4` sigue `aprobado`.
- Snapshot `#4` conserva:
  - `bruto_total = 100.00`;
  - `pagos_caja_aplicados_total = 0.00`;
  - `pendiente_pago_total = 100.00`.

## Exclusiones confirmadas

Este cierre no autoriza:

- subir a produccion;
- migraciones;
- columnas nuevas en `trabajador_pagos_caja`;
- pago masivo;
- nomina oficial;
- CFDI;
- timbrado;
- dispersion;
- liquidacion automatica de anticipos o prestamos;
- modificar snapshots;
- reabrir snapshots;
- tocar PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Siguiente paso seguro

Antes de continuar con nuevas funciones de nomina/pagos, mantener este bloque
como validado localmente y decidir el siguiente contrato independiente.

Opciones futuras posibles, solo con autorizacion explicita:

- mejorar UX del filtro de fechas de periodos para evitar confusion entre fecha
  base, inicio y fin;
- contrato para trazabilidad fuerte con columnas nullable hacia snapshot;
- contrato para reversion contextual vista desde snapshot;
- contrato para reporte de pagos originados desde snapshots.
