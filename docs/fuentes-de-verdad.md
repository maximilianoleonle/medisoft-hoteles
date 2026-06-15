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
- Reanclaje Fase 3C: estado formal `SIMULADOR_3C_PARCIAL`;
- Fase 3C-A tiene codigo existente de preview read-only, pendiente de verificacion formal;
- Fase 3C-B tiene codigo existente de generacion manual, reclasificado como adelantado/no formal hasta cerrar 3C-A;
- Fase 3C-C tiene codigo existente de health/preflights, reclasificado como adelantado/no formal hasta cerrar 3C-A;
- sin pagos;
- sin Caja;
- sin generacion automatica desde compras;
- sin integracion con Caja.
- commit de cierre tecnico: `1fa1653`.

Regla vigente despues del reanclaje:

- La siguiente accion formal debe ser `COLA_3C_A_SIMULADOR_READ_ONLY`.
- No ejecutar ni ampliar generacion manual hasta revalidar formalmente el simulador.
- El preview 3C-A no es fuente de datos nueva; solo interpreta `compras` + `proveedores` + `cuentas_por_pagar`.
- La CxP historica creada en prueba local de 3C-B queda como antecedente; no borrar ni corregir automaticamente.
- `cuentas_por_pagar_movimientos` queda sin uso operativo.
- Los checkers 3C-C deben fallar si detectan CxP duplicada, sin compra/proveedor, con cruce de hotel, con total/saldo invalido o con referencia CxP en `movimientos_caja`.
- El preview solo debe enlazar a proveedor cuando el proveedor existe dentro del mismo `hotel_id`; si no, debe mostrar la compra bloqueada sin link a otro hotel.
- La auditoria de seguridad 3C es una capa de verificacion; no corrige datos automaticamente ni autoriza escrituras nuevas.
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

## Personal y Nomina (Fase NP)

Fuente nueva e independiente (modulo de trabajadores):

- `trabajadores`
- `trabajador_pagos`
- `trabajador_anticipos`
- `trabajador_prestamos`
- `trabajador_asistencias`
- `trabajador_documentos`

Estado:

- Fase NP-0 solo define contrato, diagnostico y diseno aditivo de las 6 tablas; aun no
  existen en la base de datos.
- El bloque NP es un modulo financiero-laboral INDEPENDIENTE: ledger laboral, saldos por
  persona, asistencia y comisiones, multi-hotel.
- Sin integracion con Caja, sin movimientos de Caja, sin salida real de dinero en este bloque.

Reglas:

- El "trabajador" es una entidad independiente: NO requiere usuario del sistema ni login.
- El vinculo opcional a un `usuario` es por `trabajadores.usuario_id` con `ON DELETE SET NULL`;
  nunca se altera `usuarios` de forma destructiva ni se fusionan usuarios en trabajadores.
- Un "pago a trabajador" es un REGISTRO LABORAL que afecta el saldo del trabajador, NO un
  movimiento de Caja.
- El saldo por trabajador es DERIVADO del ledger (`trabajador_pagos`, `trabajador_anticipos`,
  `trabajador_prestamos`); no es editable manualmente.
- Toda escritura valida `hotel_id` y `trabajador_id` del mismo hotel antes de persistir.
- Cualquier integracion con Caja o salida real de dinero requiere una Fase NP-Caja autorizada.
- La referencia trabajador-responsable de mantenimiento es logica/opcional y no altera
  `mantenimientos_habitaciones`.

## Sync

Fuente de verdad operativa:

- `/api/sync` sigue deshabilitado temporalmente.

Regla:

- Debe permanecer bloqueado con HTTP 423 y JSON `sync_temporarily_disabled`.
- No tocar PWA, service worker, IndexedDB, cache names ni archivos offline sin nuevo mensaje real explicito.
