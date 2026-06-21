# Fase 5E-J-A - Recibo laboral PDF informativo

Estado formal:
`RECIBO_5E_J_A_LABORAL_PDF_INFORMATIVO_READ_ONLY_COMPLETADO_QA_MANUAL_PENDIENTE`.

## Objetivo

Agregar una descarga PDF informativa del recibo laboral por trabajador y periodo,
sin convertirlo en nomina oficial, recibo fiscal, timbrado, dispersion ni pago masivo.

## Alcance implementado

- Ruta GET/read-only:
  `/trabajadores/{id}/recibo-laboral/pdf`.
- Accion `TrabajadorController::reciboLaboralPdfAction`.
- Renderer `TrabajadorReciboLaboralPdfService`.
- Enlace `PDF informativo` desde el recibo laboral HTML.
- Validaciones en preflight de pagos laborales Caja.
- Validaciones en health general de rutas Personal.

## Fuente de calculo

El PDF reutiliza `Trabajador::reciboLaboralInformativoPorHotel()` con:

- `trabajador_id` fijo al trabajador de la ruta;
- mismo periodo GET;
- pagos Caja incluidos o excluidos segun filtro;
- mismo calculo read-only del recibo HTML.

Si el recibo tiene bloqueos por periodo faltante o invalido, la accion redirige al
recibo HTML y no descarga PDF ambiguo.

## Renderer

El renderer usa TCPDF existente en:

- `src/app/helpers/tcpdf/tcpdf.php`

La salida se genera en memoria con `Output(..., 'S')` y se envia al navegador con
headers `application/pdf`.

## Garantias

- No agrega migraciones.
- No escribe en base de datos.
- No guarda archivo en storage.
- No crea links seguros.
- No envia correos.
- No crea movimientos de Caja.
- No registra pagos laborales.
- No revierte pagos.
- No crea recibos persistidos.
- No toca permisos/auth.
- No toca PWA/offline, IndexedDB, cache names ni `/api/sync`.
- El PDF muestra aviso visible `PDF informativo / No fiscal / No genera pago`.

## QA automatica ejecutada

- `php -l` en archivos PHP tocados.
- `tools/saas/preflight_personal_pagos_caja.php`: `OK: 52`, `WARNING: 0`,
  `ERROR: 0`.
- `tools/saas/health_check_fase_1a.php`: `OK: 313`, `WARNING: 25`,
  `ERROR: 0`.
- Prueba de humo CLI del renderer TCPDF con datos ficticios:
  salida inicia con `%PDF` y se genera en memoria.
- Ruta local sin sesion:
  `GET /trabajadores/{id}/recibo-laboral/pdf?...` responde `303` a login, sin 404.

## QA manual pendiente

1. Abrir `/trabajadores/nomina/preview` con periodo valido.
2. Entrar al `Recibo` de un trabajador.
3. Presionar `PDF informativo`.
4. Confirmar que el navegador descarga un PDF.
5. Confirmar aviso visible `PDF informativo / No fiscal / No genera pago`.
6. Confirmar que bruto, deducciones, pagos Caja, neto y pendiente coinciden con el
   recibo HTML.
7. Probar con `Pagos Caja` activado y desactivado.
8. Intentar generar sin periodo y confirmar redireccion/bloqueo controlado.
9. Confirmar que no hay POST, pago masivo, timbrado, dispersion, movimiento de Caja,
   storage ni escritura laboral.

## Fuera de alcance

- Nomina oficial.
- CFDI o UUID fiscal.
- Timbrado.
- PDF fiscal u oficial.
- Envio por correo o WhatsApp.
- Guardado en Centro Documental.
- Cierre de periodo.
- Pago masivo.
- Liquidacion automatica de anticipos o prestamos.
- Auditoria por simple descarga.
