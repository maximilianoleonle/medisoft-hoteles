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
- Reanclaje Fase 3C: estado formal `FASE_3C_VALIDADA_MANUALMENTE`;
- Fase 3C-A tiene preview read-only ruteado, protegido, navegable y verificado automaticamente;
- Fase 3C-A y 3C-B fueron validadas manualmente por el usuario;
- Fase 3C-B tiene generacion manual POST activa desde compras recibidas elegibles;
- Fase 3C-C queda completada tecnicamente como health/preflights read-only de consistencia CxP;
- sin pagos;
- sin Caja;
- sin generacion automatica desde compras;
- sin integracion con Caja.
- commit de cierre tecnico: `1fa1653`.

Regla vigente despues del reanclaje:

- La siguiente accion formal recomendada es cierre tecnico 3C si se autoriza explicitamente.
- No ampliar hacia pagos, Caja ni Fase 3D sin nueva autorizacion explicita.
- El preview 3C-A no es fuente de datos nueva; solo interpreta `compras` + `proveedores` + `cuentas_por_pagar`.
- Las CxP creadas en pruebas locales controladas (`id=1` compra `#5`, `id=2` compra `#2`) quedan como evidencia local; no borrar ni corregir automaticamente.
- `cuentas_por_pagar_movimientos` queda sin uso operativo.
- Los checkers 3C-C deben fallar si detectan CxP duplicada, sin compra/proveedor, con cruce de hotel, con total/saldo invalido, sin fecha de emision, con movimientos CxP/pagos/abonos o con referencia CxP en `movimientos_caja`.
- El preview solo debe enlazar a proveedor cuando el proveedor existe dentro del mismo `hotel_id`; si no, debe mostrar la compra bloqueada sin link a otro hotel.
- La auditoria de seguridad 3C es una capa de verificacion; no corrige datos automaticamente ni autoriza escrituras nuevas.
- Auditoria seguridad 3C post-3C-C completada: no hay hallazgos bloqueantes y no autoriza pagos, Caja ni Fase 3D.
- Cierre tecnico 3C completado: no cambia fuentes de verdad, no autoriza pagos/abonos/Caja y mantiene `cuentas_por_pagar_movimientos` sin uso operativo.
- QA manual final 3C reportada como OK por el usuario; no cambia fuentes de verdad ni autoriza funcionalidades nuevas.
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

## Centro Documental (Fase 4A)

Estado formal: `MIGRACION_4A_COMPLETADA`.

Fuente fundacional creada:

- `documento_tipos`;
- `documentos`;
- `documento_entidades`.

Reglas:

- Existen como tablas base vacias desde 4A-A, pero todavia no son fuente operativa de
  archivos porque no hay uploads, descargas ni adjuntos funcionales expuestos.
- Los archivos privados deben vivir bajo `STORAGE_PATH/documentos`.
- `public_html/uploads` no debe ser fuente de documentos privados.
- `reporte_links` sigue siendo fuente especifica de reportes PDF y no debe fusionarse
  con Centro Documental en 4A base.
- Toda relacion documental debe validar `hotel_id` de documento y entidad.
- `storage_path` apunta a almacenamiento privado futuro; no debe usarse como URL publica.
- La relacion `documento_entidades` es polimorfica; la validacion de pertenencia a hotel
  de proveedor/compra/CxP/huesped/reservacion debe vivir en modelo/servicio.
- Conteos iniciales post-migracion: `documento_tipos=0`, `documentos=0`,
  `documento_entidades=0`.
- No Caja, pagos, abonos, Fase 3D ni `/api/sync`.

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
