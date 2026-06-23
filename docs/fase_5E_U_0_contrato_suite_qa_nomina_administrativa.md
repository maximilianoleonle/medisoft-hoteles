# Fase 5E-U-0 - Contrato suite QA nomina administrativa

Estado formal:
`CONTRATO_5E_U_0_SUITE_QA_NOMINA_ADMINISTRATIVA_COMPLETADO`.

Fecha: 2026-06-23.

## Objetivo

Definir una suite local de QA tecnica para el bloque administrativo de
Personal/Nomina, de forma que las validaciones principales puedan ejecutarse
juntas antes de continuar con nuevas fases.

## Alcance autorizado

Una futura 5E-U-A puede agregar una herramienta CLI/read-only que:

- ejecute preflights existentes;
- resuma resultados en una salida compacta;
- trate como bloqueantes los preflights actuales de pagos laborales, snapshots,
  auditoria, expediente y frontera de nomina oficial;
- trate como informativo cualquier preflight historico que represente una
  frontera anterior ya superada por fases autorizadas;
- valide de forma estatica que el health general conserva la integracion
  5E-T-B y el bloqueo documentado de `/api/sync`.

## Fuera de alcance

Este contrato no autoriza:

- rutas nuevas;
- controladores, modelos, servicios o vistas;
- formularios POST;
- migraciones, columnas, indices, seeds o backfill;
- escrituras en base de datos;
- pagos, reversiones, movimientos Caja, cortes o snapshots;
- storage;
- PWA/offline, IndexedDB, cache names, `service-worker.js`, `pwa.js`,
  `offline-data.js`, `reservaciones-offline.js`;
- cambios en `/api/sync`;
- nomina oficial, CFDI laboral, timbrado, dispersion bancaria, pago masivo,
  polizas contables oficiales o calculo fiscal patronal.

## Criterios de aceptacion

- La suite debe ejecutarse solo por CLI.
- Debe exigir `APP_ENV=local`.
- Debe salir con `ERROR: 0` cuando los preflights bloqueantes pasen.
- Debe mostrar claramente warnings no bloqueantes.
- Debe mantener `/api/sync` como superficie no modificada.
- No debe sustituir el QA manual de vistas; solo consolida QA tecnica local.
