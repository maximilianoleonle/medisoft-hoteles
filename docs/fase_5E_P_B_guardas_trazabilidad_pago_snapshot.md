# Fase 5E-P-B - Guardas read-only de trazabilidad pago snapshot

Estado formal:
`GUARDAS_5E_P_B_TRAZABILIDAD_PAGO_SNAPSHOT_READONLY_QA_TECNICA_LOCAL_COMPLETADA`.

Fecha: 2026-06-21.

## Objetivo

Agregar validaciones read-only en los checkers para detectar una futura
trazabilidad fuerte de pagos desde snapshot mal aplicada.

Esta fase no implementa la trazabilidad fuerte. Solo prepara alarmas tecnicas
para cuando una futura 5E-P-A sea autorizada.

## Alcance aplicado

Archivos modificados:

- `src/tools/saas/preflight_personal_pagos_caja.php`
- `src/tools/saas/health_check_fase_1a.php`

Validaciones agregadas:

- Si `trabajador_pagos_caja` no tiene `nomina_periodo_id` ni
  `nomina_periodo_detalle_id`, el estado actual se considera correcto porque
  5E-P-A aun no esta aplicada.
- Si aparece solo una de las dos columnas, el checker marca error.
- Si aparecen ambas columnas, exige indices:
  - `idx_trabajador_pagos_caja_nomina_periodo`
  - `idx_trabajador_pagos_caja_nomina_detalle`
- Si aparecen ambas columnas, valida que no existan:
  - relaciones parciales;
  - periodo inexistente o de otro hotel;
  - detalle inexistente o de otro hotel;
  - detalle fuera del periodo;
  - detalle de un trabajador distinto al trabajador pagado.

## Limites respetados

- No crea migraciones.
- No altera base de datos.
- No modifica rutas, controladores, modelos, servicios, vistas ni formularios.
- No cambia reglas de Caja, pagos, snapshots, auditoria ni saldos.
- No toca storage, PWA/offline, IndexedDB, cache names ni `/api/sync`.

## QA tecnica local

Comandos ejecutados:

```bash
docker compose exec -T app php -l tools/saas/preflight_personal_pagos_caja.php
docker compose exec -T app php -l tools/saas/health_check_fase_1a.php
docker compose exec -T app php tools/saas/preflight_personal_pagos_caja.php
docker compose exec -T app php tools/saas/health_check_fase_1a.php
```

Resultado:

- Lint PHP: OK en ambos checkers.
- Preflight pagos laborales Caja: `OK: 66`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 321`, `WARNING: 25`, `ERROR: 0`.

## Criterio de avance futuro

5E-P-A sigue requiriendo autorizacion explicita para migracion, DB,
modelo/servicio, checkers de aplicacion, backup y prueba rollback.
