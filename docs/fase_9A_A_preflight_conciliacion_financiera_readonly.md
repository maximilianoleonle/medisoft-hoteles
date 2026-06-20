# Fase 9A-A - Preflight conciliacion financiera read-only

## Estado

`PREFLIGHT_9A_A_CONCILIACION_FINANCIERA_READONLY_COMPLETADO`

## Objetivo

Implementar una verificacion CLI de solo lectura para cruzar CxC, CxP y Caja antes de
crear cualquier UI de conciliacion financiera.

## Alcance implementado

- Nuevo preflight: `src/tools/saas/preflight_conciliacion_financiera.php`.
- Ejecucion solo por CLI.
- Conexion y transaccion `READ ONLY`.
- Rollback final explicito.
- Lectura de:
  - `cuentas_por_cobrar`;
  - `cuentas_por_cobrar_movimientos`;
  - `cuentas_por_pagar`;
  - `cuentas_por_pagar_movimientos`;
  - `movimientos_caja`;
  - `cortes_caja`;
  - `cajas`;
  - `logs_auditoria`;
  - tablas de contexto `reservaciones`, `compras`, `proveedores` y `hoteles`.

## Comprobaciones agregadas

- Esquema requerido y columnas minimas.
- Resumen CxC:
  - cuentas abiertas;
  - cuentas liquidadas o saldo cero;
  - saldo pendiente;
  - cobros y reversiones.
- Resumen CxP:
  - cuentas abiertas;
  - cuentas pagadas o saldo cero;
  - saldo pendiente;
  - pagos proveedor y reversiones.
- Resumen Caja:
  - movimientos vinculados a CxC;
  - movimientos vinculados a CxP;
  - movimientos en cortes abiertos/cerrados.
- Alertas CxC/Caja:
  - saldo fuera de rango;
  - movimiento sin cuenta del mismo hotel;
  - COBRO sin ingreso Caja;
  - ingreso Caja sin COBRO;
  - reversion sin gasto Caja;
  - gasto Caja sin CANCELACION;
  - doble reversion;
  - referencia `REV-CXC-*` con formato inesperado.
- Alertas CxP/Caja:
  - saldo fuera de rango;
  - movimiento sin cuenta del mismo hotel;
  - PAGO_REFERENCIAL sin gasto Caja;
  - gasto Caja sin PAGO_REFERENCIAL;
  - reversion sin ingreso Caja;
  - ingreso Caja sin CANCELACION;
  - doble reversion;
  - referencia `REV-CXP-*` con formato inesperado.
- Alertas transversales Caja:
  - tipo/categoria incompatible;
  - `hotel_id` faltante;
  - corte inexistente o de otro hotel;
  - caja/corte de hotel cruzado;
  - referencias financieras duplicadas.
- Auditoria esperada para:
  - cobro CxC;
  - reversion cobro CxC;
  - pago proveedor;
  - reversion pago proveedor.

## Prohibiciones respetadas

No se agrego:

- rutas;
- controladores;
- modelos;
- vistas;
- formularios;
- POST;
- migraciones;
- escrituras de DB;
- cambios en Caja;
- cambios en saldos;
- correcciones automaticas;
- cambios en reservaciones, compras, proveedores o facturacion;
- cambios en PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Evidencia de ejecucion

`php -l tools/saas/preflight_conciliacion_financiera.php`:

- sin errores de sintaxis.

`php tools/saas/preflight_conciliacion_financiera.php`:

- `OK: 75`;
- `WARNING: 0`;
- `ERROR: 0`;
- `Resultado general: PASS_WITH_WARNINGS_ALLOWED`.

Resumen observado:

- CxC totales: `1`.
- CxC abiertas con saldo: `1`.
- Saldo CxC pendiente no cancelado: `$4,250.00`.
- Movimientos CxC `COBRO`: `1`.
- Movimientos CxC reversion: `1`.
- CxP totales: `2`.
- CxP abiertas con saldo: `2`.
- Saldo CxP pendiente no cancelado: `$1,910.00`.
- Movimientos CxP `PAGO_REFERENCIAL`: `2`.
- Movimientos CxP reversion: `1`.
- Movimientos Caja vinculados a CxC: `2`.
- Movimientos Caja vinculados a CxP: `3`.
- Logs de auditoria esperados completos.

## Regresiones ejecutadas

`php tools/saas/preflight_cuentas_por_cobrar.php`:

- `OK: 45`;
- `WARNING: 6`;
- `ERROR: 0`.

`php tools/saas/preflight_pagos_proveedores_caja.php`:

- `OK: 29`;
- `WARNING: 1`;
- `ERROR: 0`.

`php tools/saas/health_check_fase_1a.php`:

- `OK: 278`;
- `WARNING: 25`;
- `ERROR: 0`.

Las advertencias corresponden a datos historicos o movimientos reales ya validados.

## Riesgo residual

- Este preflight no corrige diferencias: solo las detecta.
- Aun no existe pantalla operativa 9A.
- Cualquier UI futura debe mantenerse GET/read-only y sin botones de correccion.

## Siguiente accion segura

Abrir 9A-B como contrato de pantalla read-only de conciliacion financiera antes de
implementar UI. La pantalla debe consumir la misma logica conceptual del preflight y no
permitir acciones operativas.
