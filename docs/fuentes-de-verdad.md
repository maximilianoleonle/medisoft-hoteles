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

- fundacion read-only;
- sin pagos;
- sin Caja;
- sin generacion automatica desde compras todavia;
- sin datos operativos generados por la aplicacion.

Regla:

- Cualquier escritura en CxP requiere nueva fase autorizada.
- Cualquier integracion con Caja requiere nueva fase autorizada.

## Compras

Fuente operativa actual:

- `compras`
- `compra_detalles`
- `compras_recibidas`
- `movimientos_inventario`

Regla:

- La recepcion minima ya autorizada puede escribir inventario.
- No debe crear CxP automaticamente en Fase 3B.

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
