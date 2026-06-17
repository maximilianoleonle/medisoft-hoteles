# Fase 5D-0 - Contrato de pagos laborales sin Caja automatica

## Estado

`CONTRATO_5D_PAGOS_LABORALES_SIN_CAJA_COMPLETADO`

## Objetivo

Definir una fase futura para registrar pagos laborales controlados sin crear movimientos
automaticos de Caja, sin nomina automatica y sin liquidar saldos sin autorizacion.

Esta subfase es solo diagnostico/contrato. No implementa pagos.

## Diagnostico actual

- `trabajador_pagos` existe, pero esta usado como tabla de conceptos laborales
  (`comision`, `bono`, `descuento`, `ajuste`), no como pagos reales.
- `trabajador_anticipos` y `trabajador_prestamos` almacenan saldos pendientes
  informativos.
- `trabajador_asistencias` almacena asistencia manual.
- La ficha y reporte de trabajador muestran saldos informativos.
- No existe una tabla separada y explicita para pagos laborales reales sin Caja.
- No existe integracion autorizada con Caja para Personal.
- `categorias_movimientos` no tiene categoria Nomina.
- Los preflights vigentes exigen cero movimientos de Caja asociados a nomina.

## Riesgo principal

El nombre `trabajador_pagos` puede inducir a error. En el contrato vigente esa tabla no
representa pago real; representa conceptos del ledger laboral.

No debe reutilizarse como pago real sin una migracion/contrato posterior que aclare la
semantica y proteja datos existentes.

## Alcance futuro permitido para 5D-A

Solo si se autoriza despues de este contrato:

- Crear una capa de pagos laborales sin Caja automatica.
- Registrar pagos como entidad laboral independiente y auditada.
- Mantener saldo laboral informativo antes/despues del pago.
- No crear movimientos en `movimientos_caja`.
- No crear categorias de Caja.
- No liquidar anticipos/prestamos automaticamente salvo decision explicita.
- Bloquear pagos a trabajadores inactivos o de otro hotel.
- Usar CSRF, permisos existentes, transaccion y auditoria.

## Fuera de alcance

- Caja automatica.
- Nomina automatica.
- Timbrado.
- Facturacion.
- Bancos.
- Dispersion.
- Descuentos automaticos.
- Liquidacion automatica de anticipos/prestamos.
- Edicion/borrado de pagos.
- `/api/sync`.

## Propuesta de modelo futuro

Opcion recomendada para fase futura:

- Crear tabla aditiva `trabajador_pagos_reales` o `trabajador_liquidaciones`.
- Mantener `trabajador_pagos` como conceptos laborales historicos.
- Relacionar pago con `trabajador_id`, `hotel_id`, periodo, monto, metodo informativo,
  referencia, estado y usuario.
- Agregar tabla de detalle si se requiere aplicar conceptos/saldos manualmente.

No se crea esta tabla en 5D-0.

## Definition of Done futura

- Backup previo si hay DB.
- Migracion aditiva e idempotente si se requiere tabla nueva.
- Controlador POST con CSRF.
- Modelo central con validacion `trabajador_id + hotel_id`.
- Sin escrituras en Caja.
- Auditoria en `logs_auditoria`.
- Preflight laboral actualizado.
- Health checker actualizado.
- QA manual con trabajador activo.

## Siguiente accion segura

Abrir `5D-A` solo si se acepta crear una entidad de pago laboral independiente sin Caja.
Si no, pasar al siguiente bloque read-only del roadmap: `7A Cuentas por cobrar read-only`.
