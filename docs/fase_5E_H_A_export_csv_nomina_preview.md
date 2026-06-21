# Fase 5E-H-A - Export CSV del preview de pre-nomina

Estado formal:
`EXPORT_CSV_5E_H_A_NOMINA_PREVIEW_READ_ONLY_COMPLETADO_QA_MANUAL_PENDIENTE`.

## Objetivo

Implementar una exportacion CSV read-only para la pantalla
`GET /trabajadores/nomina/preview`, reutilizando los mismos filtros y calculos del
preview de pre-nomina por periodo.

## Superficie implementada

- Ruta nueva:
  `GET /trabajadores/nomina/preview/exportar`.
- Accion nueva:
  `TrabajadorController::exportarNominaPreviewAction`.
- Descarga nueva:
  `TrabajadorController::descargarNominaPreviewCsv`.
- Enlace nuevo en vista:
  `src/app/views/trabajadores/nomina_preview.php`.
- Validaciones actualizadas:
  `src/tools/saas/preflight_personal_pagos_caja.php` y
  `src/tools/saas/health_check_fase_1a.php`.

## Reglas aplicadas

- Solo usa GET.
- No agrega formularios POST.
- No agrega CSRF porque no hay escritura.
- No crea pagos, recibos, periodos oficiales ni movimientos de Caja.
- No toca migraciones, datos, permisos/auth, PWA/offline ni `/api/sync`.
- Reutiliza `Trabajador::nominaPreviewPorHotel($hotelId, $filtros, 500)`.
- Si el periodo falta o es invalido, no descarga CSV y redirige al preview con
  mensaje controlado.
- Exporta importes en decimal plano.
- Envia BOM UTF-8, `Content-Type: text/csv; charset=UTF-8`,
  `Content-Disposition: attachment` y `X-Content-Type-Options: nosniff`.

## Columnas exportadas

- `periodo_inicio`
- `periodo_fin`
- `trabajador_id`
- `trabajador`
- `identificacion`
- `rol`
- `estado_trabajador`
- `estado_preview`
- `bruto_periodo`
- `conceptos_a_favor`
- `conceptos_en_contra`
- `anticipos_saldo`
- `prestamos_saldo`
- `deducciones_informativas`
- `pagos_caja_aplicados`
- `pagos_caja_pagados`
- `pagos_caja_revertidos`
- `reversiones_detectadas`
- `neto_sugerido`
- `pendiente_pago_sugerido`
- `ultimo_pago_caja`

## Fuera de alcance

No se implementa:

- nomina automatica;
- cierre o persistencia de periodos;
- pago masivo;
- recibo oficial;
- timbrado;
- dispersion bancaria;
- liquidacion automatica de anticipos/prestamos;
- auditoria por descarga;
- cambios de Caja;
- cambios de permisos/auth;
- PWA/offline;
- `/api/sync`.

## QA manual sugerida

1. Abrir `/trabajadores/nomina/preview`.
2. Seleccionar periodo valido.
3. Descargar `Exportar CSV`.
4. Confirmar que el archivo abre y contiene encabezados esperados.
5. Confirmar que trabajadores y totales coinciden con la pantalla.
6. Probar `Pagos Caja` activado/desactivado.
7. Probar `Con saldo`.
8. Intentar exportar sin fechas y confirmar que no descarga CSV ambiguo.
9. Confirmar que no se generan pagos, recibos, movimientos de Caja ni escrituras.

## Validacion automatica requerida

- `php -l` en PHP tocados.
- `php tools/saas/preflight_personal_pagos_caja.php` con `ERROR: 0`.
- `php tools/saas/health_check_fase_1a.php` con `ERROR: 0`.
- Ruta sin sesion:
  `GET /trabajadores/nomina/preview/exportar` debe responder `303` a login, sin 404.
