# Auditoria de formularios: facturacion

Fecha: 2026-06-25

## Alcance

- Controlador: `src/app/controllers/FacturacionController.php`.
- Vista principal: `src/app/views/facturacion/detalle.php`.
- Vista de listado: `src/app/views/facturacion/index.php`.

## Hallazgos

- El formulario de datos fiscales ya conservaba valores en validaciones directas de RFC y codigo postal, pero los fallos generales de guardado no restauraban datos ni errores por campo.
- El modal para marcar una solicitud como facturada no conservaba el folio capturado si fallaba la actualizacion.
- El modal de cancelacion no conservaba el motivo si fallaba la actualizacion y antes podia devolver al listado, perdiendo contexto de la solicitud.
- La vista de detalle no mostraba errores junto a los campos fiscales ni en los modales de completar/cancelar.
- La accion `en-proceso` no tiene campos editables; no requirio cambios de recuperacion de datos.
- El listado de facturacion solo usa filtros GET; no requirio cambios de formularios POST.

## Correcciones aplicadas

- Se agrego mapeo de errores por campo para datos fiscales, folio de factura, motivo de cancelacion y notas.
- Los fallos de guardar datos fiscales conservan `old_input` y exponen errores con `save_form_errors`.
- Los fallos de completar factura conservan `solicitud_id`, `numero_factura` y el modal de origen.
- Los fallos de cancelar factura conservan `solicitud_id`, `motivo` y el modal de origen.
- La cancelacion fallida vuelve al detalle de la solicitud cuando existe un `solicitud_id`, para no perder el contexto operativo.
- La vista muestra errores debajo de RFC, razon social, regimen fiscal, uso CFDI, codigo postal fiscal, email, notas, numero de factura y motivo.
- Los campos con error reciben `aria-invalid` y `aria-describedby`.
- Si falla completar o cancelar desde un modal, el modal correcto se vuelve a abrir automaticamente.

## Fuera de alcance

- No se modificaron modelos, rutas, base de datos, migraciones, permisos ni autenticacion.
- No se modifico timbrado, Aspel, folios fiscales ni calculos fiscales.
- No se modifico logica profunda de reservaciones, check-in/check-out, caja, cortes o reportes.
- No se modifico PWA/offline, IndexedDB, service worker ni `/api/sync`.

## Validacion

- `php -l` en controlador y vista de detalle: sin errores.
- `git diff --check` del bloque: sin errores reales; solo avisos CRLF.
- Formularios de la vista de detalle balanceados: 4 aperturas y 4 cierres.
