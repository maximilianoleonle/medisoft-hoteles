# Fase 7A-R - Reconciliacion CxC read-only

## Estado

`RECONCILIACION_7A_R_CXC_READONLY_DIAGNOSTICADA`

## Objetivo

Identificar el origen exacto de los warnings de CxC detectados por el preflight,
sin modificar datos, sin crear CxC operativa, sin cobros, sin abonos, sin Caja y
sin tocar `/api/sync`.

Esta fase solo ejecuta diagnostico read-only.

## Verificacion ejecutada

- `src/tools/saas/preflight_cuentas_por_cobrar.php`: `OK: 14`, `WARNING: 3`,
  `ERROR: 0`.
- Consultas SQL read-only contra:
  - `reservaciones`;
  - `reservacion_pagos`;
  - `reservacion_abonos`;
  - `solicitudes_factura`;
  - `hoteles`;
  - `movimientos_caja`;
  - `information_schema.TABLES`.

Confirmaciones:

- No existe tabla `cuentas_por_cobrar`.
- No existen movimientos de Caja con referencia/descripcion/categoria tipo `CxC`.
- No hay abonos con reservacion inexistente o de otro hotel.
- El reporte `/cuentas-por-cobrar` sigue siendo solo GET/read-only.

## Hallazgo 1 - Pagos historicos sin reservacion scoped

El preflight detecta 3 pagos en `reservacion_pagos` cuyo `reservacion_id` no existe
como reservacion del mismo hotel.

| pago_id | hotel_id | hotel | reservacion_id | metodo | monto | created_at | diagnostico |
| --- | ---: | --- | ---: | --- | ---: | --- | --- |
| 1 | 2 | Hotel Demo SaaS | 1 | efectivo | 550.00 | 2026-06-02 17:24:30 | reservacion inexistente |
| 2 | 1 | Los Cedros | 5 | efectivo | 600.00 | 2026-06-04 15:30:50 | reservacion inexistente |
| 3 | 1 | Los Cedros | 6 | efectivo | 600.00 | 2026-06-06 22:00:36 | reservacion inexistente |

Impacto:

- El reporte CxC read-only los excluye porque agrega pagos por
  `hotel_id + reservacion_id`.
- Bloquean una CxC operativa si no se decide primero si son datos historicos,
  pruebas, registros a migrar o registros a anular contablemente.

## Hallazgo 2 - Solicitudes de factura historicas sin reservacion scoped

El preflight detecta 170 solicitudes de factura con `reservacion_id` inexistente
para el mismo hotel.

Resumen:

| hotel_id | hotel | motivo | estatus | requiere_factura | total | rango_id | monto_total |
| --- | ---: | --- | --- | --- | ---: | --- | ---: |
| 1 | Los Cedros | reservacion_inexistente | en_proceso | si | 1 | 192-192 | 2200.00 |
| 1 | Los Cedros | reservacion_inexistente | completada | si | 59 | 21-217 | 83400.00 |
| 1 | Los Cedros | reservacion_inexistente | completada | no | 109 | 19-220 | 370200.00 |
| 1 | Los Cedros | reservacion_inexistente | cancelada | no | 1 | 139-139 | 2400.00 |

Totales:

- Solicitudes de factura totales: 175.
- Solicitudes scoped validas contra reservacion + hotel: 5.
- Solicitudes historicas sin reservacion scoped: 170.
- Monto historico sin reservacion scoped: 458200.00.
- Rango temporal de los historicos: 2026-03 a 2026-05.

Impacto:

- El reporte read-only solo enlaza solicitudes validas por `hotel_id + reservacion_id`.
- Estos registros no deben reutilizarse para CxC operativa sin decision contable.
- No deben borrarse automaticamente desde esta fase.

## Hallazgo 3 - Reservaciones con saldo estimado excedente

El preflight detecta 3 reservaciones con saldo estimado negativo en Maximiliano Leon.
El patron observado es doble cobertura: abono demo + pago posterior completo.

| reservacion_id | hotel | huesped | estado | total | pagos | abonos | saldo_raw |
| ---: | --- | --- | --- | ---: | ---: | ---: | ---: |
| 10 | Maximiliano Leon | Carlos Mendez Demo H4 | checked_out | 3600.00 | 2000.00 | 2000.00 | -400.00 |
| 11 | Maximiliano Leon | Mariana Torres Demo H4 | checked_out | 1200.00 | 1200.00 | 600.00 | -600.00 |
| 15 | Maximiliano Leon | Sofia Carrillo Demo H4 | checked_out | 4400.00 | 4400.00 | 1200.00 | -1200.00 |

Movimientos relacionados:

- Reservacion `#10`: abono `#1` por `2000.00` y pago `#7` por `2000.00`.
- Reservacion `#11`: abono `#2` por `600.00` y pago `#13` por `1200.00`.
- Reservacion `#15`: abono `#3` por `1200.00` y pago `#20` por `4400.00`.

Impacto:

- El reporte CxC muestra estos casos como `excedente` y no como saldo a cobrar.
- La CxC operativa no debe nacer sobre estos saldos sin una regla explicita para
  anticipos, abonos, pagos completos y posibles devoluciones.

## Foto actual por hotel

| hotel_id | hotel | reservaciones | total_reservado | pagos | abonos | pendiente_estimado | excedentes | excedente_monto |
| ---: | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 1 | Los Cedros | 4 | 17100.00 | 17100.00 | 0.00 | 0.00 | 0 | 0.00 |
| 4 | Maximiliano Leon | 13 | 38200.00 | 35950.00 | 3800.00 | 650.00 | 3 | 2200.00 |

## Decision tecnica

7A-R confirma que el reporte read-only es seguro, pero tambien confirma que una
CxC operativa directa sigue bloqueada.

No implementar todavia:

- tabla `cuentas_por_cobrar`;
- cobros CxC;
- abonos CxC;
- movimientos de Caja desde CxC;
- migraciones de saldos;
- limpieza automatica de historicos;
- cambios en reservaciones, pagos, abonos o facturacion.

## Siguiente accion segura

El contrato de reconciliacion controlada queda definido en
`docs/fase_7A_S_0_contrato_reconciliacion_cxc_controlada.md`.

Antes de cualquier 7B operativa, ejecutar primero 7A-S-A preview read-only y llenar una
matriz de decision:

1. Definir si los 3 pagos huerfanos son pruebas, historicos migrados o registros a
   anular contablemente.
2. Definir politica para las 170 solicitudes de factura huerfanas: conservar como
   historico, archivarlas logicamente, asociarlas por migracion o excluirlas
   permanentemente de CxC.
3. Definir regla de excedentes: abono + pago completo, devolucion, credito a favor
   o ajuste informativo.
4. Hacer backup antes de cualquier escritura futura.

Hasta esa decision, CxC debe seguir en modo read-only.
