# Fase 5E-S-A - Expediente administrativo de nomina read-only

Estado formal:
`IMPLEMENTACION_5E_S_A_EXPEDIENTE_ADMINISTRATIVO_NOMINA_QA_TECNICA_LOCAL_COMPLETADA`.

Fecha: 2026-06-22.

## Objetivo

Implementar en local una pantalla GET/read-only de expediente administrativo
de nomina que consolida evidencias de snapshots de pre-nomina, pagos Caja
trazados, reversiones, saldo de auditoria y bloqueos administrativos.

No registra pagos, no revierte pagos, no modifica Caja, no modifica snapshots,
no crea nomina oficial, no timbra, no genera dispersion y no escribe storage.

## Alcance implementado

- Rutas GET:
  - `/trabajadores/nomina/expediente`
  - `/trabajadores/nomina/expediente/exportar`
- Controlador:
  - `TrabajadorController::expedienteNominaAction`
  - `TrabajadorController::exportarExpedienteNominaAction`
  - `TrabajadorController::descargarExpedienteNominaCsv`
- Modelo:
  - `Trabajador::tablasExpedienteNominaAdministrativoDisponibles`
  - `Trabajador::expedienteNominaAdministrativoPorHotel`
  - helpers privados de filtros, evaluacion, resumen, agrupacion y bloqueos.
- Vista:
  - `app/views/trabajadores/nomina_expediente_administrativo.php`
- Enlaces:
  - Desde `nomina_periodos.php`.
  - Desde `nomina_pagos_snapshot_reporte.php`.
  - Desde `nomina_auditoria_consolidada.php`.
- Checkers:
  - `tools/saas/preflight_personal_pagos_caja.php`.
  - `tools/saas/health_check_fase_1a.php`.

## Lectura administrativa

El expediente reutiliza la auditoria consolidada 5E-R-A como fuente base y
clasifica cada detalle de snapshot por estado administrativo.

La lectura queda scoped por:

- `hotel_id`;
- `nomina_periodo_id`;
- `nomina_periodo_detalle_id`;
- `trabajador_id`.

## Estados del expediente

- `Listo para revision`: snapshot aprobado, sin bloqueos criticos y sin saldo
  pendiente de auditoria.
- `Con pendientes`: hay saldo de auditoria o pago parcial.
- `Requiere correccion`: la auditoria consolidada marca `Revisar` o hay
  inconsistencias de trazabilidad.
- `Bloqueado`: el snapshot no esta aprobado o el trabajador actual no esta
  activo.
- `Anulado`: el snapshot origen fue anulado.

Estos estados son informativos. No cambian datos.

## Bloqueos detectados

La capa marca bloqueos cuando detecta:

- snapshot anulado;
- snapshot no aprobado;
- auditoria consolidada que requiere correccion;
- inconsistencias de trazabilidad;
- pagos Caja mayores al pendiente congelado;
- pago sin trazabilidad de ultimo movimiento;
- reversiones laborales detectadas;
- trabajador actual no activo.

## Exportacion CSV

La exportacion:

- se genera en memoria;
- no escribe en storage;
- incluye filtros aplicados por query string;
- incluye estado de expediente;
- incluye bloqueos detectados;
- incluye importes de snapshot, pagos Caja, reversiones y saldo de auditoria;
- incluye referencias y cajas agregadas.

## QA tecnica local

- `php -l` OK:
  - `config/routes.php`
  - `app/controllers/TrabajadorController.php`
  - `app/models/Trabajador.php`
  - `app/views/trabajadores/nomina_expediente_administrativo.php`
  - `tools/saas/preflight_personal_pagos_caja.php`
  - `tools/saas/health_check_fase_1a.php`
- Modelo local validado contra `hotel_id = 4`, `periodo_id = 4`:
  - registros: `1`;
  - trabajador: `Panfilo Hernandez`;
  - estado auditoria: `parcial`;
  - estado expediente: `con_pendientes`;
  - pagos Caja: `$1.00`;
  - saldo auditoria: `$99.00`;
  - bloqueos: `0`.
- `preflight_personal_pagos_caja.php`: `OK: 78`, `WARNING: 0`, `ERROR: 0`.
- `health_check_fase_1a.php`:
  - valida `Personal 5E-S-A` como OK;
  - deja `OK: 325`, `WARNING: 27`, `ERROR: 3`;
  - los `ERROR: 3` son historicos de Compras/CxP y no pertenecen a 5E-S-A.

## QA manual sugerida

1. Abrir `/trabajadores/nomina/expediente?periodo_id=4`.
2. Confirmar que aparece `Expediente administrativo de nomina`.
3. Confirmar que aparece el trabajador `Panfilo Hernandez`.
4. Confirmar que el estado del expediente es `Con pendientes`.
5. Confirmar `Pagos Caja` `$1.00`.
6. Confirmar `Saldo auditoria` `$99.00`.
7. Confirmar que no hay bloqueos criticos para el caso parcial valido.
8. Probar filtro `Expediente = Con pendientes`.
9. Probar `Exportar CSV`.
10. Confirmar que no hay botones de registrar pago, revertir pago, aprobar
    snapshot, anular snapshot ni formularios POST.

## Limites

- No se toco produccion.
- No se agregaron migraciones.
- No se tocaron permisos, auth, storage, PWA/offline, IndexedDB, cache names ni
  `/api/sync`.
- No se agregaron acciones POST.
- No se escriben pagos, reversiones, movimientos de Caja ni snapshots.
- El CSV se genera en memoria.
