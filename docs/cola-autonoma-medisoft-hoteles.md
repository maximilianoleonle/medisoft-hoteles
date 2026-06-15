# Cola autonoma - Medisoft Hoteles

## Ultimo mensaje real usado

Cola autorizada Fase 3C-B: generacion manual controlada de CxP desde compra recibida, sin Caja ni pagos.

## Estado vigente

- Fase actual: Fase 3C-B generacion manual controlada.
- Riesgo: naranja.
- Estado: POST manual implementado con CSRF, transaccion y auditoria; sin Caja ni pagos.
- Base local principal: `medisoft_hoteles_import`.
- Backup previo a DB:
  - `src/storage/backups/phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql`
  - SHA256: `24663D206AE15B86B001708D8BC2665541548443A0EA363548CAFC3FDF3A4D2C`
  - tamano: `3017728`

## Contrato activo

La Fase 3B aplicada solo permite una fundacion read-only de cuentas por pagar:

- tablas nuevas vacias de CxP;
- modelo/controlador/vistas read-only;
- rutas GET de listado y detalle;
- navegacion basica;
- health/preflight compatibles;
- documentacion de QA, decisiones, rollback y fuentes de verdad.

No permite pagos, Caja, generacion automatica desde compras, saldos operativos, CxC, nomina, permisos profundos ni cambios en `/api/sync`.

## Fases completadas con commit

- `d1f1431` - `feat: add read-only received purchase detail`
- `32abb7b` - `feat: add read-only received purchases reports`
- `052fd7a` - `fix: harden minimal purchase receiving flow`
- `3d8f997` - `feat: expand supplier profile and purchase history`
- `673f47f` - `docs: draft accounts payable foundation`
- `1fa1653` - `feat(phase-3b): add read-only accounts payable foundation`
- `2535dd1` - `docs: record post-3b audit closure`
- `ca2bda4` - `docs: record security audit closure`
- `8a49995` - `docs: close authorized technical block`
- `dc3c150` - `fix: improve late check-in modal layout`

## Diagnostico 3C-0

- Estado Git al inicio de 3C-0: limpio.
- QA manual del bloque anterior: reportada como realizada por el usuario.
- Cambio visual de reservaciones: commiteado en `dc3c150`.
- Compras recibidas detectadas: 2.
- Compras recibidas elegibles para CxP: 2.
- CxP actuales: 0.
- Movimientos Caja-CxP: 0.

## Implementacion 3C-A

- Ruta nueva: `GET /cuentas-por-pagar/generacion-preview`.
- Vista nueva: `src/app/views/cuentas_por_pagar/generacion_preview.php`.
- Modelo CxP consulta compras, proveedores, hoteles, detalles y CxP con `hotel_id`.
- La vista muestra compra, proveedor, hotel, fecha, total, estado, CxP existente, elegibilidad y bloqueo.
- No hay POST, pagos, Caja ni generacion automatica.

## Implementacion 3C-B

- Ruta nueva: `POST /cuentas-por-pagar/generar-desde-compra/{id}`.
- Controller: `CuentaPorPagarController::generarDesdeCompraAction()`.
- Modelo: `CuentaPorPagar::generarDesdeCompraRecibida()`.
- Usa CSRF y transaccion.
- Bloquea compra con `FOR UPDATE`.
- Valida compra recibida, proveedor del mismo hotel, total positivo, detalles existentes y no duplicado.
- Inserta solo en `cuentas_por_pagar`.
- Registra auditoria en `logs_auditoria` si esta disponible.
- No inserta pagos, abonos ni movimientos de Caja.

## Prueba local 3C-B

- Backup valido previo:
  - `src/storage/backups/phase3c_b_20260615_053711_before_manual_cxp_medisoft_hoteles_import_notablespaces.sql`
  - SHA256: `8086F91DF17DB09CFBB28E7E12BED475FDD81FB538948F4B60141A90BE9E801D`
  - tamano: `1528988`
- Primer intento de backup con routines/tablespaces genero advertencias de privilegios y no se toma como respaldo valido:
  - `src/storage/backups/phase3c_b_20260615_053658_before_manual_cxp_medisoft_hoteles_import.sql`
- Registro usado para prueba:
  - hotel_id: `4`
  - compra_id: `5`
  - total: `1000.00`
- Resultado:
  - CxP generada: `id = 1`
  - doble generacion: bloqueada con mensaje de CxP existente
  - `cuentas_por_pagar`: `0 -> 1`
  - `cuentas_por_pagar_movimientos`: `0 -> 0`
  - `logs_auditoria`: `23 -> 24`
  - `cajas`: `3 -> 3`
  - `movimientos_caja`: `1402 -> 1402`

## Cambios pendientes clasificados post-commit

### Relacionados con Fase 3B

- Ninguno pendiente.

### No relacionado con Fase 3B

- Ninguno pendiente. El ajuste visual de reservaciones quedo commiteado en `dc3c150`.

### Dudosos

- Ninguno identificado.

## Pruebas obligatorias para cierre

- `php -l` sobre controlador, modelo, vistas, rutas, sidebar y checkers modificados.
- `health_check_fase_1a.php`.
- `preflight_compras_minimas.php`.
- `preflight_recepcion_compras.php`.
- SQL read-only para validar tablas, migracion registrada, conteos CxP en cero y no generacion de pagos.
- HTTP sin sesion `GET /cuentas-por-pagar`.
- `git diff --check`.

## Auditoria de seguridad post-cierre

- Estado: completada sin hallazgos bloqueantes.
- CxP conserva solo lectura y rutas GET.
- No hay integracion accidental con Caja.
- No hay escritura CxP desde compras/proveedores.
- `/api/sync` sigue bloqueado y fuera de alcance.
- Riesgo residual futuro: escrituras CxP deben validar proveedor/compra por `hotel_id` antes de insertar.

## QA manual pendiente

Ver `docs/qa-pendiente-cola.md`.

## Documento de cierre

Ver `docs/cierre-tecnico-bloque-cola.md`.

## Siguiente accion

Verificar y cerrar Fase 3C-B. No avanzar a pagos, Caja ni abonos sin nueva autorizacion.
