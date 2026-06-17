# Fase 7A-A - Reporte CxC read-only derivado

## Estado

`REPORTE_7A_A_CXC_READONLY_COMPLETADO_QA_DIFERIDA`

## Objetivo

Crear una superficie segura para consultar saldos por cobrar estimados desde fuentes
existentes, sin crear una CxC operativa, sin registrar cobros, sin abonos, sin pagos y
sin tocar Caja.

## Implementacion

- Ruta GET: `/cuentas-por-cobrar`.
- Controlador: `CuentaPorCobrarController`.
- Modelo: `CuentaPorCobrar`.
- Vista: `cuentas_por_cobrar/index`.
- Preflight: `tools/saas/preflight_cuentas_por_cobrar.php`.

El reporte deriva datos desde:

- `reservaciones`
- `huespedes`
- `reservacion_pagos`
- `reservacion_abonos`
- `solicitudes_factura`

No existe ni se crea tabla `cuentas_por_cobrar`.

## Contrato read-only

- Solo usa GET.
- No agrega POST.
- No crea cobros.
- No crea abonos.
- No registra pagos.
- No crea movimientos de Caja.
- No modifica reservaciones.
- No modifica facturacion.
- No toca `/api/sync`.
- No expone datos de otro hotel.

## Reglas multihotel

- La consulta principal filtra `reservaciones.hotel_id = hotel actual`.
- Pagos, abonos y solicitudes de factura se agregan por `hotel_id + reservacion_id`.
- Filas historicas de pagos/facturacion que no coinciden con la reservacion del mismo
  hotel quedan fuera del calculo derivado.

## Datos mostrados

- Reservacion.
- Huesped.
- Fechas.
- Estado de reservacion.
- Total reservado.
- Pagos y abonos existentes.
- Monto cubierto.
- Saldo estimado.
- Estado de saldo: pendiente, liquidada o excedente.
- Enlaces GET a reservacion y factura existente si aplica.

## Warnings conocidos

El preflight detecta datos historicos que no bloquean la vista read-only, pero deben
reconciliarse antes de cualquier CxC operativa:

- 3 pagos con reservacion inexistente o de otro hotel.
- 170 solicitudes de factura con reservacion inexistente o de otro hotel.
- 3 reservaciones con saldo estimado negativo.

El reporte conserva estos casos como advertencias porque la consulta excluye filas que no
coinciden por hotel/reservacion y etiqueta los saldos negativos como `excedente`.

## Verificacion automatica

- `php -l` en modelo, controlador, vista, rutas, sidebar y preflight.
- `tools/saas/preflight_cuentas_por_cobrar.php`: `ERROR: 0`.
- `tools/saas/health_check_fase_1a.php`: `ERROR: 0`.
- HTTP sin sesion a `/cuentas-por-cobrar`: redireccion a login.
- SQL read-only de conteos base y Caja.
- `git diff --check`.

## QA manual diferida

Por instruccion del usuario, la QA manual queda diferida.

Pruebas recomendadas:

1. Entrar con sesion hotelera.
2. Abrir `/cuentas-por-cobrar`.
3. Confirmar que solo aparecen reservaciones del hotel activo.
4. Probar filtros de busqueda, estado de reservacion y estado de saldo.
5. Confirmar enlace a reservacion.
6. Confirmar enlace a factura cuando exista.
7. Confirmar que no hay botones de cobro, pago, abono ni Caja.
8. Confirmar que el texto indica saldo estimado y solo lectura.

## Rollback

1. Revertir el commit de 7A-A.
2. No tocar reservaciones, pagos, abonos, facturacion ni Caja.
3. No crear ni borrar tablas.
4. Conservar warnings historicos para una fase futura de reconciliacion.

## Siguiente accion segura

Cerrar/revisar 7A-A o preparar contrato de 7B solo si se autoriza CxC operativa sin Caja
automatica.
