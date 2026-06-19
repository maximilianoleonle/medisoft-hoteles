# Fase 3D-C - Pago proveedor con Caja

Estado: `PAGO_PROVEEDOR_CAJA_3D_C_VALIDADO_MANUALMENTE`.

## Objetivo

Registrar pagos de cuentas por pagar a proveedores desde el detalle de CxP, creando de
forma atomica:

- movimiento referencial en `cuentas_por_pagar_movimientos`;
- egreso en `movimientos_caja`;
- actualizacion de saldo/estado en `cuentas_por_pagar`;
- auditoria en `logs_auditoria`.

## Backup

Backup limpio confirmado antes de habilitar escrituras:

- Archivo:
  `backups/medisoft_hoteles_import_before_3d_payments_20260617_105846.sql`
- Tamano: `1631469` bytes.
- SHA256:
  `9584636FF545D8370B5E84171A9B1CF4EA2A8637D73285316DC83EB14E499A16`
- Base: `medisoft_hoteles_import`.

El intento previo `medisoft_hoteles_import_before_3d_payments_20260617_105636.sql`
queda descartado como backup tecnico porque `mysqldump` reporto permisos insuficientes.

## Alcance implementado

- Servicio transaccional: `CuentaPorPagarPagoService`.
- Ruta POST controlada:
  `/cuentas-por-pagar/{id}/registrar-pago-caja`.
- Formulario visible solo en detalle de CxP elegible.
- CSRF obligatorio.
- Token de pago de un solo uso por sesion para reducir doble envio.
- Requiere modulo `inventario` para CxP y modulo `caja` en el POST operativo.
- Prueba CLI rollback:
  `tools/saas/probar_pago_proveedor_caja.php`.

## Reglas de seguridad

- No crea pagos automaticos al recibir compras.
- No toca compras durante el pago.
- No crea abonos.
- No toca `/api/sync`.
- No modifica pantallas de Caja.
- Solo paga CxP del hotel actual.
- Solo acepta proveedor del mismo hotel y activo.
- Si la CxP viene de compra, la compra debe existir en el mismo hotel y estar `recibida`.
- Requiere corte de Caja abierto del mismo hotel.
- No permite monto mayor al saldo.
- No permite saldo invalido ni saldo mayor al total.
- Bloquea referencia duplicada para la misma cuenta cuando se captura referencia.

## Fuentes de verdad

- Deuda y saldo: `cuentas_por_pagar`.
- Trazabilidad del pago CxP: `cuentas_por_pagar_movimientos`.
- Egreso real de Caja: `movimientos_caja`.
- Corte operativo: `cortes_caja`.
- Caja activa: `cajas`.
- Proveedor moderno: `proveedores`.

## Rollback funcional

Si el pago se registro por error, no borrar fisicamente. Revertir con script/manual SQL
controlado dentro de transaccion:

1. Localizar `cuentas_por_pagar_movimientos.id`.
2. Localizar `movimientos_caja.id` por referencia/descripcion/corte.
3. Restaurar `cuentas_por_pagar.saldo` y `estado` al valor previo.
4. Anular o revertir el movimiento de Caja segun politica contable del hotel.
5. Registrar auditoria del rollback.

Para retirar la funcionalidad sin tocar datos:

1. Revertir el commit de Fase 3D-C.
2. Retirar ruta POST `registrar-pago-caja`.
3. Retirar `CuentaPorPagarPagoService`.
4. Retirar formulario del detalle CxP.
5. Mantener datos historicos ya generados hasta reconciliacion financiera.

## Definition of Done

- `php -l` sin errores en archivos PHP tocados.
- `health_check_fase_1a.php` pasa.
- `preflight_pagos_proveedores_caja.php` pasa.
- `preflight_compras_minimas.php` pasa.
- `preflight_recepcion_compras.php` pasa.
- `probar_pago_proveedor_caja.php` confirma escritura temporal y rollback.
- HTTP sin sesion bloquea o redirige el POST.
- `git diff --check` sin errores.
- QA manual confirma pago real en navegador antes de cerrar como validado.

## QA manual completada

El usuario confirmo que todas las pruebas manuales del flujo pasaron.

1. Abrir `/cuentas-por-pagar/1` o una CxP elegible del hotel actual.
2. Confirmar que el formulario aparece solo si hay corte abierto.
3. Registrar pago parcial pequeño con referencia unica.
4. Confirmar que baja el saldo y el estado pasa a `parcial`.
5. Confirmar que aparece movimiento referencial en el detalle.
6. Confirmar que Caja muestra un gasto con categoria `Pago proveedor`.
7. Reintentar el mismo POST desde el navegador y confirmar bloqueo por token/referencia.
8. Pagar saldo restante en una CxP de prueba y confirmar estado `pagada`.
9. Confirmar que no se toca `/api/sync`.

Evidencia post-QA del pago real controlado:

- Hotel: `Maximiliano Leon`.
- CxP: `#1`.
- Proveedor: `Juan Pedro`.
- Pago parcial registrado: `10.00` en efectivo.
- Saldo CxP: `1000.00 -> 990.00`.
- Estado CxP: `pendiente -> parcial`.
- Movimiento CxP creado: `cuentas_por_pagar_movimientos.id = 4`.
- Movimiento de Caja creado: `movimientos_caja.id = 1478`.
- Categoria Caja: `Pago proveedor`.
- Corte de Caja: `corte_id = 237`.
- Referencia Caja: `CXP-1-MOV-4`.
- Preflight post-QA: `preflight_pagos_proveedores_caja.php` con `OK: 22`,
  `WARNING: 0`, `ERROR: 0`.
- `/api/sync` no fue tocado.

## Pendiente futuro

- Probar pago total sobre una CxP dedicada cuando se cree un escenario de prueba
  limpio para validar estado `pagada`.
- Disenar un flujo formal de anulacion/reversion antes de escalar pagos reales en
  operacion diaria.
