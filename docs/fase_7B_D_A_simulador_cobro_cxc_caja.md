# Fase 7B-D-A - Simulador cobro CxC con Caja

## Estado

`SIMULADOR_COBRO_CXC_CAJA_7B_D_A_VALIDADO_MANUALMENTE`

## Objetivo

Implementar una pantalla GET/read-only para diagnosticar si una cuenta por cobrar podria
cobrarse contra Caja en una fase futura.

Esta fase no registra cobros, no cambia saldos, no crea movimientos CxC, no crea
movimientos de Caja y no modifica cortes.

## Superficie implementada

Ruta nueva:

- `GET /cuentas-por-cobrar/simulador-caja`

Archivos funcionales:

- `src/app/models/CuentaPorCobrar.php`
- `src/app/controllers/CuentaPorCobrarController.php`
- `src/app/views/cuentas_por_cobrar/simulador_caja.php`
- `src/app/views/cuentas_por_cobrar/operativas.php`
- `src/app/views/cuentas_por_cobrar/ver_operativa.php`
- `src/config/routes.php`
- `src/tools/saas/preflight_cuentas_por_cobrar.php`

## Comportamiento

- Lee CxC operativas por `hotel_id`.
- Lee corte de Caja abierto por `hotel_id`.
- Lee metadatos de caja activa.
- Evalua estado, saldo, total, reservacion vinculada y factura vinculada.
- Muestra motivos de bloqueo por cuenta.
- Conserva formulario `GET` de filtros.
- No contiene formularios `POST`.

## Bloqueo de diseno vigente

El simulador detecta que `cuentas_por_cobrar_movimientos.tipo_movimiento` aun no tiene
tipo `COBRO`.

Por eso, aunque una CxC tenga saldo y exista corte abierto, el simulador debe mantenerla
bloqueada para cobro real hasta que una fase posterior defina el esquema correcto.

No se debe usar `AJUSTE` para simular cobros.

## Escrituras prohibidas

7B-D-A no escribe en:

- `cuentas_por_cobrar`;
- `cuentas_por_cobrar_movimientos`;
- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `reservaciones`;
- `reservacion_pagos`;
- `reservacion_abonos`;
- `solicitudes_factura`;
- `logs_auditoria`;
- PWA/offline;
- `/api/sync`.

## Validaciones automaticas

- `php -l` en PHP tocado: OK.
- Preflight CxC actualizado:
  - `OK: 33`;
  - `WARNING: 3`;
  - `ERROR: 0`.
- Health general:
  - `OK: 277`;
  - `WARNING: 25`;
  - `ERROR: 0`.
- HTTP sin sesion a `/cuentas-por-cobrar/simulador-caja`: `303` a login.
- Conteos post-implementacion:
  - `cuentas_por_cobrar = 1`;
  - `cuentas_por_cobrar_movimientos = 1`;
  - auditoria de generacion CxC = `1`;
  - movimientos de Caja con referencia textual a CxC = `0`.
- Enum actual de `tipo_movimiento`:
  `CREACION`, `AJUSTE`, `CANCELACION`, `NOTA`, `RECLASIFICACION`.

## QA manual validada

El usuario reporto QA manual correcta despues de abrir el simulador en navegador.

Validacion esperada confirmada:

1. `/cuentas-por-cobrar/simulador-caja` abre correctamente con sesion.
2. La CxC `#1` aparece en el diagnostico.
3. No hay boton ni formulario de cobro.
4. El cobro real queda bloqueado por ausencia de tipo semantico `COBRO`.
5. Los filtros `GET` no crean movimientos ni cambian saldos.

Evidencia post-QA:

- `cuentas_por_cobrar = 1`;
- `cuentas_por_cobrar_movimientos = 1`;
- auditoria de generacion CxC = `1`;
- movimientos de Caja con referencia textual a CxC = `0`;
- HTTP sin sesion a `/cuentas-por-cobrar/simulador-caja`: `303` a login.

## Siguiente paso seguro

Cerrar documentalmente 7B-D-A y definir 7B-D-B-0 como contrato de esquema de cobros
CxC. No implementar cobro real hasta decidir si se agregara tipo `COBRO`, tabla de
cobros CxC o entidad de recibos/cobros.
