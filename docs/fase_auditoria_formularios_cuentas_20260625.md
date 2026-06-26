# Auditoria de formularios de cuentas por cobrar y pagar - 2026-06-25

## Alcance

Bloque revisado:

- `src/app/controllers/CuentaPorCobrarController.php`
- `src/app/controllers/CuentaPorPagarController.php`
- `src/app/views/cuentas_por_cobrar/ver_operativa.php`
- `src/app/views/cuentas_por_pagar/ver.php`

El enfoque fue conservar datos capturados y mostrar errores junto al campo correcto en acciones operativas de cobro, pago y reversion.

## Problemas encontrados

- Registrar cobro CxC mostraba solo mensaje general al fallar y no conservaba monto, metodo, referencia ni notas.
- Revertir cobro CxC mostraba solo mensaje general al fallar y no conservaba el motivo.
- Registrar pago CxP mostraba solo mensaje general al fallar y no conservaba monto, metodo, referencia ni notas.
- Revertir pago CxP mostraba solo mensaje general al fallar y no conservaba el motivo.
- En pantallas con varios movimientos reversibles, el motivo restaurado podia necesitar contexto para mostrarse solo en el movimiento correcto.

## Correcciones aplicadas

- Se agrego `save_old_input($_POST)` en fallos de registrar cobro CxC y registrar pago CxP.
- Se agrego `save_form_errors(...)` con mapeo por campo para `monto`, `metodo_pago`, `referencia`, `notas` y `motivo`.
- En reversiones se guarda `reversion_movimiento_id` como contexto interno para restaurar el motivo solo en el movimiento que fallo.
- En exito de cobro, pago y reversion se llama `clear_old_input()` para limpiar datos anteriores y errores consumidos.
- Las vistas de detalle ahora leen `$layoutFieldErrors` y `$_SESSION['old_input']`.
- Los campos muestran estado visual de error, `aria-invalid`, `aria-describedby` y el mensaje inline con clase `ms-form-field-error`.

## Formularios verificados

- CxC: registrar cobro en Caja.
- CxC: revertir cobro en Caja.
- CxP: registrar pago desde Caja.
- CxP: revertir pago desde Caja.

## Fuera de alcance

No se modificaron:

- Rutas.
- Modelos.
- Base de datos ni migraciones.
- Permisos ni auth.
- Calculos de caja, cortes, saldos o movimientos.
- Logica profunda de cobro, pago, check-in/check-out o reservaciones.
- PWA, service worker, IndexedDB, cache names ni `/api/sync`.

## Validacion

- `php -l` en ambos controladores y ambas vistas dentro del contenedor `medisoft_hoteles_app`.
- Revision de formularios: se mantienen `method`, `action`, `name`, CSRF e inputs ocultos existentes.
- `git diff --check` sin errores reales; solo avisos esperados de CRLF en Windows.
