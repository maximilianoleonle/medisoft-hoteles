# Fase 5E-L-F - Cierre QA cierre/aprobacion persistente de pre-nomina

Estado formal:
`CIERRE_5E_L_F_CIERRE_APROBACION_PERSISTENTE_PRENOMINA_QA_LOCAL_VALIDADA`.

## Objetivo

Cerrar documentalmente 5E-L-A/5E-L-B despues de una QA local con flujo HTTP real
contra la app en Docker.

Esta fase no agrega codigo, rutas, modelos, servicios, vistas, migraciones,
permisos, PWA/offline ni `/api/sync`.

## QA local ejecutada

Contexto:

- URL local: `http://localhost:8080`.
- Usuario operativo usado por sesion temporal local: `adminmax` (`usuario_id=24`).
- Hotel: `Maximiliano` (`hotel_id=4`).
- Periodo probado: semanal `2026-06-15` a `2026-06-21`.
- Snapshot resultante: `trabajador_nomina_periodos.id = 2`.

Flujo validado:

1. `GET /trabajadores/nomina/periodos` cargo con sesion de administrador.
2. La vista genero CSRF y token de cierre.
3. `POST /trabajadores/nomina/periodos/cerrar` respondio `303` hacia
   `/trabajadores/nomina/periodos/2`.
4. El detalle mostro estado `Cerrado`, 1 trabajador y mensaje de que no genero
   pago ni movimiento de Caja.
5. El intento duplicado del mismo rango quedo bloqueado visualmente: la tarjeta
   ya no mostro boton de cierre y mostro `Cerrado #2`.
6. `POST /trabajadores/nomina/periodos/2/aprobar` respondio `303` y dejo el
   snapshot en estado `aprobado`.
7. `POST /trabajadores/nomina/periodos/2/anular` sin motivo respondio `303`,
   mostro error `El motivo de anulacion es obligatorio` y no cambio el estado.
8. `POST /trabajadores/nomina/periodos/2/anular` con motivo
   `QA manual 5E-L local` respondio `303` y dejo el snapshot en estado `anulado`.
9. El detalle final mostro `Anulado`, `Sin pago real`, el motivo y el evento de
   anulacion.

## Evidencia de datos

Estado final del snapshot:

- `trabajador_nomina_periodos.id=2`
- `hotel_id=4`
- `estado=anulado`
- `motivo_anulacion=QA manual 5E-L local`
- eventos registrados:
  - `cierre`
  - `aprobacion`
  - `anulacion`

Conteos sensibles:

- Antes del cierre:
  - `movimientos_caja`: `1413`
  - `trabajador_pagos_caja`: `1`
- Despues de cierre, aprobacion y anulacion:
  - `movimientos_caja`: `1413`
  - `trabajador_pagos_caja`: `1`

Conclusion: el flujo persistio solo snapshot administrativo de pre-nomina y no
genero pago laboral, movimiento de Caja, CFDI, timbrado, dispersion ni pago
masivo.

## Validaciones automaticas

- `tools/saas/preflight_personal_pagos_caja.php`:
  - `OK: 60`
  - `WARNING: 0`
  - `ERROR: 0`
- `tools/saas/health_check_fase_1a.php`:
  - `OK: 316`
  - `WARNING: 25`
  - `ERROR: 0`
- `POST /api/sync` con sesion activa:
  - HTTP `423`
  - JSON `sync_temporarily_disabled`

Los warnings del health son historicos/de contexto de montaje y no bloquean esta
fase.

## Fuera de alcance

Este cierre no autoriza:

- nomina oficial;
- CFDI;
- timbrado;
- dispersion;
- pago masivo;
- liquidacion automatica de anticipos o prestamos;
- movimientos de Caja desde pre-nomina;
- PWA/offline;
- cambios en `/api/sync`.

## Rollback

Ver `docs/rollback-cola.md`, seccion `Rollback Fase 5E-L-F`.
