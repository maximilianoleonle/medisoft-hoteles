# Fase 5E-N-A - Pago individual desde snapshot de pre-nomina con Caja

Estado formal:
`IMPLEMENTACION_5E_N_A_PAGO_SNAPSHOT_PRENOMINA_CAJA_QA_MANUAL_VALIDADA_EN_5E_N_F`.

## Objetivo

Permitir registrar un pago laboral individual desde el detalle de un snapshot de
pre-nomina aprobado, usando Caja y el servicio transaccional existente de pagos
laborales.

La implementacion es local. No se subio a produccion.

## Alcance implementado

- Ruta POST controlada:
  `/trabajadores/nomina/periodos/{periodo}/detalles/{detalle}/registrar-pago-caja`.
- Accion `registrarPagoSnapshotNominaAction` en `TrabajadorController`.
- Servicio nuevo `TrabajadorNominaSnapshotPagoService`.
- Columna visual `Pago Caja` en `nomina_periodo_detalle.php`.
- Token de un solo uso `pago_snapshot_caja` por periodo/detalle elegible.
- Recalculo vivo con `TrabajadorPagoCajaService`.
- Tope operativo:

```text
min(pendiente_pago_sugerido_snapshot, saldo_laboral_disponible_vivo)
```

- Auditoria adicional:
  `trabajadores.nomina_snapshot_pago_caja_registrado`.
- Prueba rollback:
  `tools/saas/probar_pago_snapshot_prenomina_caja.php`.
- Validaciones en:
  `tools/saas/preflight_personal_pagos_caja.php` y
  `tools/saas/health_check_fase_1a.php`.

## Reglas de seguridad

- Solo snapshots `aprobado` pueden mostrar accion de pago.
- El pago es individual por trabajador, no masivo.
- El request no acepta `hotel_id`, `trabajador_id`, `periodo_inicio`,
  `periodo_fin`, `corte_id` ni identificadores de Caja.
- El periodo y trabajador salen del snapshot y se validan por `hotel_id`.
- El snapshot permanece inmutable: no se actualizan sus detalles, totales ni
  estado.
- Caja se mueve solo por el servicio transaccional existente.

## Fuera de alcance

- No agrega migracion.
- No agrega columnas en `trabajador_pagos_caja`.
- No crea nomina oficial.
- No crea CFDI, timbrado, dispersion ni folios oficiales.
- No liquida anticipos o prestamos automaticamente.
- No crea pago masivo.
- No toca PWA/offline, IndexedDB, cache names ni `/api/sync`.

## QA tecnica local

Lint PHP dentro de Docker:

- `app/services/TrabajadorNominaSnapshotPagoService.php`: OK.
- `app/controllers/TrabajadorController.php`: OK.
- `app/views/trabajadores/nomina_periodo_detalle.php`: OK.
- `config/routes.php`: OK.
- `tools/saas/probar_pago_snapshot_prenomina_caja.php`: OK.
- `tools/saas/preflight_personal_pagos_caja.php`: OK.
- `tools/saas/health_check_fase_1a.php`: OK.

Prueba rollback local:

```bash
docker compose exec -T app env APP_ENV=local php tools/saas/probar_pago_snapshot_prenomina_caja.php
```

Resultado:

- Creo trabajador, concepto, snapshot y detalle temporales dentro de transaccion.
- Registro pago laboral temporal y movimiento Caja temporal.
- Confirmo que el snapshot temporal permanecio inmutable.
- Revirtio todo con rollback.
- No dejo datos persistentes.

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

Las advertencias del health son historicas o de montaje documental/migrations y
no corresponden a error de 5E-N-A.

## QA manual validada

El usuario valido manualmente el flujo completo en local:

1. Evaluo periodo manual `20/06/2026 - 20/06/2026`.
2. Cerro snapshot persistente `#4`.
3. Aprobo administrativamente el snapshot.
4. Registro pago individual desde el snapshot por `$1.00` con referencia
   `TEST-5ENA-001`.
5. El sistema mostro:
   `Pago desde snapshot registrado. Movimiento Caja #1507. El snapshot permanece inmutable.`
6. La ficha del trabajador mostro saldo disponible vivo de `$99.00`.

Validacion de lectura posterior:

- `trabajador_pagos_caja.id = 6`.
- `movimientos_caja.id = 1507`.
- Monto registrado: `$1.00`.
- Referencia: `TEST-5ENA-001`.
- Snapshot `#4` permanecio `aprobado`.
- Snapshot `#4` conservo `bruto_total = 100.00`,
  `pagos_caja_aplicados_total = 0.00` y
  `pendiente_pago_total = 100.00`.

El cierre documental queda registrado en:

`docs/fase_5E_N_F_cierre_pago_snapshot_prenomina_caja.md`.

## QA manual sugerida

1. Abrir un snapshot aprobado en
   `/trabajadores/nomina/periodos/{id}`.
2. Confirmar que solo trabajadores elegibles muestran formulario de pago.
3. Confirmar que snapshots cerrados o anulados muestran bloqueo.
4. Intentar monto mayor al maximo y confirmar bloqueo.
5. Registrar un pago individual valido.
6. Confirmar mensaje de exito con movimiento Caja.
7. Confirmar que el snapshot sigue igual.
8. Confirmar el nuevo pago en historial laboral/Caja.
9. Confirmar que no aparece pago masivo, CFDI, timbrado ni dispersion.
10. Confirmar que `/api/sync` sigue bloqueado con HTTP 423.
