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

Solo contrato 7B si se requiere planear CxC operativa sin Caja automatica. No implementar
cobros hasta reconciliar warnings historicos.
