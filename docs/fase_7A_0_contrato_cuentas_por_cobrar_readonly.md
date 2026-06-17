# Fase 7A-0 - Contrato de Cuentas por Cobrar read-only

## Estado

`CONTRATO_7A_CXC_READONLY_COMPLETADO`

## Objetivo

Definir una fase segura para observar cuentas por cobrar en modo solo lectura, sin crear
cobros, sin Caja automatica, sin facturacion nueva y sin tocar pagos de reservaciones.

Esta subfase es solo contrato/diagnostico. No implementa rutas ni tablas.

## Diagnostico actual

- No existe tabla `cuentas_por_cobrar`.
- Fuentes candidatas existentes:
  - `reservaciones`
  - `reservacion_pagos`
  - `reservacion_abonos`
  - `solicitudes_factura`
- Existe modulo de facturacion basado en `solicitudes_factura`.
- Existen pagos/abonos de reservacion ya integrados con flujos historicos de Caja.
- Cualquier CxC operativa puede afectar finanzas, facturacion, reservaciones y Caja.

Conteos observados en base local:

- `reservaciones`: 17.
- `reservacion_pagos`: 19.
- `reservacion_abonos`: 3.
- `solicitudes_factura`: 175.
- Tablas con nombre `cuentas_por_cobrar`: ninguna.

## Riesgo principal

CxC puede confundirse con:

- cobros reales;
- pagos de reservacion;
- abonos;
- facturacion;
- movimientos de Caja;
- saldos de habitacion/reservacion.

Por eso 7A debe iniciar como read-only derivado y no como tabla operativa.

## Alcance futuro permitido para 7A-A

Solo si se autoriza despues de este contrato:

- Crear un reporte/vista GET read-only de saldos por cobrar derivados.
- Mostrar reservacion, huesped, hotel, total, pagado, abonado, saldo estimado y estado.
- Filtrar siempre por `hotel_id`.
- Enlazar a reservacion y facturacion existente.
- Mostrar estados vacios claros.
- No crear registros.
- No crear cobros.
- No crear abonos.
- No tocar Caja.
- No tocar `/api/sync`.

## Fuera de alcance

- Tabla operativa de CxC.
- Cobros.
- Abonos.
- Pagos.
- Caja.
- Facturacion nueva.
- Correccion de saldos historicos.
- Cambios profundos de reservaciones.
- Migraciones.
- `/api/sync`.

## Propuesta tecnica futura

Para 7A-A:

- Modelo read-only `CuentaPorCobrar` o servicio de reporte.
- Controlador `CuentaPorCobrarController` con rutas GET:
  - `/cuentas-por-cobrar`
  - `/cuentas-por-cobrar/{id}` si hay entidad derivada estable.
- Calculo derivado conservador desde fuentes existentes.
- Preflight que valide:
  - tablas candidatas con `hotel_id`;
  - saldos estimados no negativos;
  - ausencia de rutas POST de CxC;
  - ausencia de Caja nueva.

## Definition of Done futura

- `php -l` en archivos PHP tocados.
- Health/preflight CxC read-only.
- HTTP sin sesion redirige o bloquea.
- SQL read-only confirma cero escrituras.
- `git diff --check`.

## Siguiente accion segura

Implementar 7A-A como reporte GET/read-only derivado, sin DB nueva y sin Caja.
