# Fase NP-C-F - Cierre tecnico del bloque ledger laboral read-only

Estado: `BLOQUE_NP_C_READ_ONLY_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- NP-C-0 contrato de ledger laboral.
- NP-C-A vista read-only del ledger en ficha de trabajador.
- NP-C-E preflight de consistencia de ledger laboral.

## Confirmaciones

- No se agregaron rutas operativas nuevas.
- No se agregaron POST de ledger.
- No se crearon conceptos, anticipos, prestamos ni asistencias.
- No se modifico Caja.
- No se creo categoria `Nomina`.
- No se tocaron pagos reales, abonos, CxP, CxC ni `/api/sync`.
- La ficha de trabajador muestra el saldo como informativo.
- El preflight `preflight_personal_ledger.php` valida consistencia antes de escrituras
  futuras.

## Verificaciones automáticas

- `php -l` en PHP modificado de NP-C-A y NP-C-E.
- `health_check_fase_1a.php`: `ERROR: 0`.
- `preflight_personal_ledger.php`: `OK: 42`, `WARNING: 0`, `ERROR: 0`.
- SQL read-only confirma:
  - `trabajadores`: 0;
  - `trabajador_pagos`: 0;
  - `trabajador_anticipos`: 0;
  - `trabajador_prestamos`: 0;
  - `trabajador_asistencias`: 0;
  - `categorias_nomina`: 0.
- HTTP sin sesion en `/trabajadores/1`: `303`.
- `git diff --check`: sin errores, solo warnings CRLF conocidos.

## QA manual diferida

- Abrir `/trabajadores`.
- Crear o usar trabajador autorizado.
- Abrir `/trabajadores/{id}`.
- Confirmar bloque "Ledger laboral".
- Confirmar estados vacios claros.
- Confirmar ausencia de botones de pago, abono, Caja o nomina.
- Confirmar que el saldo se interpreta como informativo.

## Riesgos residuales

- La base local no tiene trabajadores, por lo que la visualizacion con datos reales queda
  pendiente de QA manual.
- Cualquier escritura laboral futura es sensible y debe abrir contrato propio antes de
  implementarse.
- Caja debe mantenerse separada hasta un bloque NP-Caja explicitamente autorizado.

## Siguiente paso seguro

Contrato `NP-C-B-0` para primera escritura laboral controlada en `trabajador_pagos`, sin
Caja, o esperar QA manual del bloque read-only antes de avanzar.
