# Fase 9C-A - Preflight arqueo por corte y metodo read-only

## Estado

`PREFLIGHT_9C_A_ARQUEO_METODOS_PAGO_READONLY_COMPLETADO`

## Objetivo

Implementar un preflight CLI/read-only para diagnosticar cortes de Caja y metodos de
pago sin modificar datos.

El preflight compara movimientos de Caja contra cortes, valida integridad de metodos y
detecta diferencias historicas sin cerrar, recalcular ni corregir cortes.

## Alcance implementado

- Herramienta nueva:
  - `src/tools/saas/preflight_arqueo_metodos_pago.php`.
- Ejecucion solo por CLI.
- Requiere `APP_ENV=local`.
- Abre transaccion `READ ONLY`.
- Cierra con rollback.
- No agrega rutas, controladores, modelos, vistas ni formularios.
- No agrega migraciones ni escrituras.

## Fuentes leidas

- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `categorias_movimientos`.

## Validaciones incluidas

Esquema:

- columnas requeridas de `movimientos_caja`;
- columnas requeridas de `cortes_caja`;
- columnas requeridas de `cajas`;
- columnas requeridas de `categorias_movimientos`.

Resumen:

- cortes totales, abiertos, cerrados y cancelados;
- movimientos totales, ingresos y gastos;
- metodos distintos;
- importe total de ingresos, gastos y neto;
- resumen por metodo de pago.

Alertas estructurales:

- movimientos sin `metodo_pago`;
- movimientos sin `corte_id`;
- movimientos con corte inexistente o de otro hotel;
- cortes con caja invalida o de otro hotel;
- movimientos con tipo invalido;
- movimientos con monto no positivo;
- metodos fuera del enum esperado;
- cortes con estado invalido;
- cortes cerrados sin fecha de cierre;
- cortes abiertos con fecha de cierre.

Alertas de arqueo cerrado:

- cortes cerrados con totales guardados distintos a la suma de movimientos;
- cortes cerrados con `efectivo_esperado` distinto a formula guardada;
- cortes cerrados con `diferencia` distinta a `efectivo_contado - efectivo_esperado`.

## Resultado local

Ejecucion:

```bash
php tools/saas/preflight_arqueo_metodos_pago.php
```

Resultado:

- `OK: 34`;
- `WARNING: 2`;
- `ERROR: 0`;
- `Resultado general: PASS_WITH_WARNINGS_ALLOWED`.

Resumen detectado:

- cortes totales: `223`;
- cortes abiertos: `3`;
- cortes cerrados: `220`;
- cortes cancelados: `0`;
- movimientos Caja totales: `1409`;
- movimientos ingreso: `1290`;
- movimientos gasto: `119`;
- metodos distintos: `3`;
- ingresos Caja: `$1,813,646.00`;
- gastos Caja: `$95,267.00`;
- neto Caja por movimientos: `$1,718,379.00`.

Metodos:

- efectivo: ingresos `1130` / `$1,614,996.00`, gastos `115` / `$90,507.00`;
- tarjeta: ingresos `93` / `$98,050.00`, gastos `0` / `$0.00`;
- transferencia: ingresos `67` / `$100,600.00`, gastos `4` / `$4,760.00`.

Warnings esperados:

- `4` cortes cerrados con totales guardados distintos a movimientos;
- `1` corte cerrado con `efectivo_esperado` distinto a formula guardada.

Estos warnings son diagnosticos historicos. No se corrigen automaticamente.

## Guardrails

El preflight no puede:

- abrir cortes;
- cerrar cortes;
- recalcular cortes;
- editar movimientos;
- cambiar metodos de pago;
- cambiar categorias;
- tocar CxC, CxP, reservaciones, compras, proveedores o facturacion;
- tocar PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Validaciones complementarias

- `php -l tools/saas/preflight_arqueo_metodos_pago.php`: OK.
- Health checker actualizado para reconocer el preflight 9C-A como CLI/read-only.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- `health_check_fase_1a.php`: `OK: 282`, `WARNING: 25`, `ERROR: 0`.
- `preflight_conciliacion_financiera.php`: `OK: 96`, `WARNING: 0`, `ERROR: 0`.
- `preflight_cuentas_por_cobrar.php`: `OK: 45`, `WARNING: 6`, `ERROR: 0`.
- `preflight_pagos_proveedores_caja.php`: `OK: 29`, `WARNING: 1`, `ERROR: 0`.
- `git diff --check`: sin errores de whitespace.

## Siguiente accion segura

La siguiente accion segura es 9C-B-0 como contrato de pantalla GET/read-only de
arqueo por corte y metodo, usando este preflight como fuente de reglas.
