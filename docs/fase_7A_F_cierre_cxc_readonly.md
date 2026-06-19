# Fase 7A-F - Cierre tecnico CxC read-only

## Estado

`BLOQUE_7A_CXC_READONLY_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- 7A-0 contrato y diagnostico de CxC read-only.
- 7A-A reporte GET/read-only derivado.

## Commits del bloque

- `4385202 docs(phase-7a): define read-only receivables contract`
- `5a2cee3 feat(phase-7a): add read-only receivables report`

## Confirmaciones tecnicas

- Existe ruta `GET /cuentas-por-cobrar`.
- No existen rutas POST bajo `/cuentas-por-cobrar`.
- No existe tabla `cuentas_por_cobrar`.
- El reporte usa `reservaciones` como fuente principal por `hotel_id`.
- Pagos, abonos y facturacion se agregan por `hotel_id + reservacion_id`.
- La UI comunica "solo lectura" y "saldo estimado".
- No se crean cobros.
- No se crean abonos.
- No se registran pagos.
- No se generan movimientos de Caja.
- No se toca `/api/sync`.

## Verificacion automatica

- `php -l` en archivos PHP de 7A-A: OK.
- `tools/saas/preflight_cuentas_por_cobrar.php`: `ERROR: 0`.
- `tools/saas/health_check_fase_1a.php`: `ERROR: 0`.
- HTTP sin sesion a `/cuentas-por-cobrar`: `303` a login.
- SQL read-only: conteos consultados sin escrituras.
- `git diff --check`: sin errores, solo warnings CRLF.

## Warnings no bloqueantes

- 3 pagos historicos con reservacion inexistente o de otro hotel.
- 170 solicitudes de factura con reservacion inexistente o de otro hotel.
- 3 reservaciones con saldo estimado negativo.

Estos warnings no bloquean la vista read-only, pero bloquean cualquier CxC operativa
hasta reconciliar origen, hotel y saldo.

## Reconciliacion read-only posterior

Documento: `docs/fase_7A_R_reconciliacion_cxc_readonly.md`.

Estado: `RECONCILIACION_7A_R_CXC_READONLY_DIAGNOSTICADA`.

Resultado:

- Los 3 pagos historicos apuntan a reservaciones inexistentes.
- Las 170 solicitudes de factura huerfanas pertenecen a Los Cedros y apuntan a
  reservaciones inexistentes, con rango temporal 2026-03 a 2026-05.
- Solo 5 solicitudes de factura actuales coinciden por `hotel_id + reservacion_id`.
- Las 3 reservaciones con saldo negativo pertenecen a Maximiliano Leon y muestran
  doble cobertura por abono demo + pago posterior completo.
- No existe tabla `cuentas_por_cobrar`.
- No existen movimientos de Caja tipo `CxC`.
- El preflight CxC post-diagnostico sigue en `ERROR: 0`.

Decision:

- CxC read-only puede mantenerse.
- CxC operativa sigue bloqueada hasta contrato de reconciliacion controlada.
- No borrar ni corregir historicos automaticamente.

## Contrato de reconciliacion controlada

Documento: `docs/fase_7A_S_0_contrato_reconciliacion_cxc_controlada.md`.

Estado: `CONTRATO_7A_S_RECONCILIACION_CXC_CONTROLADA_COMPLETADO`.

Alcance:

- Define matriz de decision para pagos huerfanos, facturas huerfanas y excedentes.
- No ejecuta escrituras.
- Exige backup, preview, decision del usuario, auditoria y rollback antes de cualquier
  correccion futura.
- Mantiene prohibidos cobros CxC, movimientos de Caja, cambios de reservaciones,
  pagos, abonos, facturacion y `/api/sync`.

## Preview/matriz de reconciliacion

Documento: `docs/fase_7A_S_A_preview_reconciliacion_cxc.md`.

Estado: `PREVIEW_7A_S_A_RECONCILIACION_CXC_READONLY_COMPLETADO`.

Resultado:

- Matriz de 3 pagos huerfanos con decision default `excluir_cxc_operativa`.
- Matriz resumida de 170 solicitudes de factura huerfanas con decision default
  `excluir_cxc_operativa`.
- Lista de 5 solicitudes scoped validas que pueden seguir en reporte read-only.
- Matriz de 3 excedentes con decision default `mantener_excedente_informativo`.
- Sin cambios en PHP, DB, Caja ni `/api/sync`.

## Politica de clasificacion

Documento: `docs/fase_7A_S_B_politica_clasificacion_cxc.md`.

Estado: `POLITICA_7A_S_B_CLASIFICACION_CXC_CONSERVADORA_COMPLETADA`.

Decision vigente:

- Pagos huerfanos: excluir de CxC operativa.
- Facturas huerfanas: excluir de CxC operativa.
- Facturas scoped validas: mantener en reporte read-only.
- Excedentes: mantener como informativos, no deuda.
- Sin escrituras, sin Caja y sin `/api/sync`.

## Cierre reconciliacion CxC

Documento: `docs/fase_7A_S_F_cierre_reconciliacion_cxc.md`.

Estado: `BLOQUE_7A_S_RECONCILIACION_CXC_CERRADO_SIN_ESCRITURAS`.

Resultado:

- 7A-S queda cerrado con contrato, preview/matriz y politica conservadora.
- Preflight CxC: `OK: 14`, `WARNING: 3`, `ERROR: 0`.
- Health general: `OK: 278`, `WARNING: 24`, `ERROR: 0`.
- Sin cambios PHP, DB, Caja ni `/api/sync`.

## QA manual diferida

La QA manual queda diferida por instruccion del usuario.

Debe validarse en navegador:

1. `/cuentas-por-cobrar`.
2. Filtros.
3. Enlaces a reservacion/factura.
4. Ausencia de botones de cobro, abono, pago y Caja.
5. Scoping por hotel activo.

## Rollback

1. Revertir `5a2cee3`.
2. No tocar datos historicos.
3. No borrar pagos, abonos ni solicitudes de factura.
4. No tocar Caja ni `/api/sync`.

## Siguiente accion segura

7B-A ya quedo aplicada como migracion base vacia, sin poblar datos y sin Caja.

7B-B ya quedo implementada como listado/detalle read-only sobre las tablas nuevas vacias.

Siguiente accion segura: 7B-C-0 solo como contrato de generacion manual futura. No
implementar cobros ni escrituras sobre historicos sin una fase separada.
