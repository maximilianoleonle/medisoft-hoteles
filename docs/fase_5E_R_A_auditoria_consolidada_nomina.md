# Fase 5E-R-A - Auditoria consolidada de nomina read-only

Estado formal:
`IMPLEMENTACION_5E_R_A_AUDITORIA_CONSOLIDADA_NOMINA_READONLY_QA_TECNICA_LOCAL_COMPLETADA`.

Fecha: 2026-06-22.

## Objetivo

Implementar en local una pantalla GET/read-only para consolidar snapshots de
pre-nomina, detalle congelado por trabajador, pagos Caja trazados,
reversiones, saldo de auditoria y exportacion CSV.

No registra pagos, no revierte pagos, no modifica Caja, no modifica snapshots y
no genera nomina oficial.

## Alcance implementado

- Rutas GET:
  - `/trabajadores/nomina/auditoria`
  - `/trabajadores/nomina/auditoria/exportar`
- Controlador:
  - `TrabajadorController::auditoriaNominaAction`
  - `TrabajadorController::exportarAuditoriaNominaAction`
  - `TrabajadorController::descargarAuditoriaNominaCsv`
- Modelo:
  - `Trabajador::tablasAuditoriaNominaConsolidadaDisponibles`
  - `Trabajador::auditoriaNominaConsolidadaPorHotel`
  - helpers privados de filtros, resumen y agrupacion read-only.
- Vista:
  - `app/views/trabajadores/nomina_auditoria_consolidada.php`
- Enlaces:
  - Desde `nomina_periodos.php`.
  - Desde `nomina_pagos_snapshot_reporte.php`.
- Checkers:
  - `tools/saas/preflight_personal_pagos_caja.php`.
  - `tools/saas/health_check_fase_1a.php`.

## Lectura consolidada

La consulta devuelve una fila por detalle congelado de snapshot y agrega los
pagos Caja vinculados por:

- `nomina_periodo_id`;
- `nomina_periodo_detalle_id`;
- `hotel_id`.

Calcula:

- pendiente congelado;
- pagos Caja vigentes;
- pagos revertidos;
- saldo de auditoria;
- ultimo pago;
- referencias;
- cajas;
- inconsistencias detectadas.

## Estados de auditoria

- `Liquidado`: el pendiente congelado ya no tiene saldo de auditoria.
- `Parcial`: hay pagos vigentes, pero queda saldo.
- `Sin pago`: hay detalle sin pagos Caja vigentes.
- `Revisar`: hay inconsistencia de trazabilidad o pago mayor al pendiente
  congelado.

## QA tecnica local

- `php -l` OK:
  - `config/routes.php`
  - `app/controllers/TrabajadorController.php`
  - `app/models/Trabajador.php`
  - `app/views/trabajadores/nomina_auditoria_consolidada.php`
  - `app/views/trabajadores/nomina_periodos.php`
  - `app/views/trabajadores/nomina_pagos_snapshot_reporte.php`
  - `tools/saas/preflight_personal_pagos_caja.php`
  - `tools/saas/health_check_fase_1a.php`
- Modelo local validado contra `hotel_id = 4`, `periodo_id = 4`:
  - registros: `1`;
  - pagos Caja: `$1.00`;
  - saldo de auditoria: `$99.00`;
  - estado: `parcial`;
  - trabajador: `Panfilo Hernandez`.
- `preflight_personal_pagos_caja.php`: `OK: 76`, `WARNING: 0`, `ERROR: 0`.
- `health_check_fase_1a.php`:
  - valida `Personal 5E-R-A` como OK;
  - deja `OK: 325`, `WARNING: 26`, `ERROR: 3`;
  - los `ERROR: 3` son historicos de Compras/CxP y no pertenecen a 5E-R-A.

## Prueba visual

El navegador integrado redirigio a login al abrir:

```text
http://localhost:8080/trabajadores/nomina/auditoria?periodo_id=4
```

No se usaron credenciales. La prueba visual queda para la sesion local del
usuario.

## QA manual sugerida

1. Abrir `/trabajadores/nomina/auditoria?periodo_id=4`.
2. Confirmar que aparece `Auditoria consolidada de nomina`.
3. Confirmar que aparece el trabajador `Panfilo Hernandez`.
4. Confirmar que el estado visible es `Parcial`.
5. Confirmar que `Pagos Caja` muestra `$1.00`.
6. Confirmar que `Saldo auditoria` muestra `$99.00`.
7. Probar filtro `Auditoria = Parcial`.
8. Probar filtro `Snapshot = Aprobado`.
9. Probar `Exportar CSV`.
10. Confirmar que no hay botones de registrar pago ni formularios POST.

## Limites

- No se toco produccion.
- No se agregaron migraciones.
- No se tocaron permisos, auth, storage, PWA/offline, IndexedDB, cache names ni
  `/api/sync`.
- No se agregaron acciones POST.
- No se escriben pagos, reversiones, movimientos de Caja ni snapshots.
- El CSV se genera en memoria.
