# Fuentes de verdad - Medisoft Hoteles

## Inventario

Fuente moderna:

- `inventario_productos`
- `movimientos_inventario`

Legacy congelado:

- `productos`
- `inventario_movimientos`
- `inventario_habitacion_config`

Regla:

- No escribir en tablas legacy salvo compatibilidad estrictamente necesaria.
- No borrar ni fusionar tablas legacy sin una fase explicita de reconciliacion.

## Cuentas por pagar

Fuente nueva fundacional:

- `cuentas_por_pagar`
- `cuentas_por_pagar_movimientos`

Estado:

- fundacion read-only hasta Fase 3B;
- Fase 3C autoriza generacion manual controlada desde compras recibidas;
- Fase 3C-A solo agrega preview read-only de compras elegibles;
- Fase 3C-B permite crear CxP manualmente desde compra recibida elegible;
- sin pagos;
- sin Caja;
- sin generacion automatica desde compras;
- sin integracion con Caja.
- commit de cierre tecnico: `1fa1653`.

Regla:

- La unica escritura CxP autorizada en 3C sera generacion manual desde compra recibida.
- El preview 3C-A no es fuente de datos nueva; solo interpreta `compras` + `proveedores` + `cuentas_por_pagar`.
- La CxP 3C-B se crea en `cuentas_por_pagar` y su auditoria en `logs_auditoria`.
- `cuentas_por_pagar_movimientos` queda sin uso operativo en 3C-B.
- Cualquier integracion con Caja requiere nueva fase autorizada.
- Cualquier generacion automatica desde compras requiere nueva fase autorizada.
- Cualquier escritura futura debe validar que `proveedor_id`, `compra_id` y `hotel_id` pertenezcan al mismo hotel antes de persistir datos.

## Compras

Fuente operativa actual:

- `compras`
- `compra_detalles`
- `compras_recibidas`
- `movimientos_inventario`

Regla:

- La recepcion minima ya autorizada puede escribir inventario.
- No debe crear CxP automaticamente en Fase 3C.
- CxP solo podra generarse por accion manual posterior a la recepcion.

## Proveedores

Fuente actual:

- `proveedores`

Regla:

- La ficha de proveedor y su historial son read-only para compras recibidas.

## Sync

Fuente de verdad operativa:

- `/api/sync` sigue deshabilitado temporalmente.

Regla:

- Debe permanecer bloqueado con HTTP 423 y JSON `sync_temporarily_disabled`.
- No tocar PWA, service worker, IndexedDB, cache names ni archivos offline sin nuevo mensaje real explicito.
