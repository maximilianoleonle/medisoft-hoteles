# Fase 5E-M-A - Reporte/export snapshots de pre-nomina

Estado formal:
`IMPLEMENTACION_5E_M_A_REPORTE_EXPORT_SNAPSHOTS_PRENOMINA_QA_TECNICA_COMPLETADA_MANUAL_PENDIENTE`.

## Objetivo

Implementar una superficie GET/read-only para consultar y exportar snapshots
persistentes de pre-nomina creados en 5E-L-A.

## Alcance implementado

- `GET /trabajadores/nomina/periodos/reporte`
- `GET /trabajadores/nomina/periodos/exportar`
- Vista `trabajadores/nomina_periodos_reporte`.
- Modelo read-only `Trabajador::reporteNominaPeriodosPersistentesPorHotel`.
- Export CSV en memoria mediante `php://output`.
- Checkers actualizados en:
  - `src/tools/saas/preflight_personal_pagos_caja.php`;
  - `src/tools/saas/health_check_fase_1a.php`.

## Reglas mantenidas

1. No agrega migraciones.
2. No escribe snapshots.
3. No aprueba, anula, reabre ni recalcula snapshots.
4. No crea pagos laborales.
5. No crea movimientos ni cortes de Caja.
6. No liquida anticipos ni prestamos.
7. No genera nomina oficial, CFDI, timbrado, dispersion ni folios oficiales.
8. No escribe archivos en storage.
9. No toca PWA/offline, IndexedDB, cache names ni `/api/sync`.

## QA minima

1. Abrir `/trabajadores/nomina/periodos/reporte` con sesion activa.
2. Confirmar que se muestran los snapshots del hotel actual.
3. Filtrar por estado `cerrado`, `aprobado` y `anulado`.
4. Filtrar por rango de fechas y tipo de periodo.
5. Buscar por folio, etiqueta, usuario o motivo.
6. Abrir `Ver snapshot` y confirmar que enlaza al detalle existente.
7. Exportar CSV y confirmar que respeta los filtros.
8. Confirmar que no se crea archivo en storage.
9. Confirmar que la vista no contiene POST, CSRF, pago, timbrado ni dispersion.
10. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
    `sync_temporarily_disabled`.

## QA tecnica local

La QA tecnica local quedo documentada en:

`docs/fase_5E_M_B_qa_tecnica_reporte_export_snapshots_prenomina.md`.

Estado posterior a QA tecnica:
`QA_5E_M_B_REPORTE_EXPORT_SNAPSHOTS_PRENOMINA_TECNICA_COMPLETADA_MANUAL_PENDIENTE`.

Queda pendiente la confirmacion manual del usuario en navegador antes de cerrar
5E-M-F.

## Rollback

Ver `docs/rollback-cola.md`, seccion `Rollback Fase 5E-M-A`.
