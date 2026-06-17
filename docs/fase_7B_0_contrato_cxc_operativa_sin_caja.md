# Fase 7B-0 - Contrato CxC operativa sin Caja automatica

## Estado

`CONTRATO_7B_CXC_OPERATIVA_SIN_CAJA_COMPLETADO`

## Objetivo

Definir el camino seguro para una CxC operativa futura, sin integrar Caja de forma
automatica y sin reutilizar directamente pagos/abonos historicos de reservaciones como
cuentas nuevas.

Esta subfase es solo contrato y diagnostico. No implementa rutas, tablas, cobros ni
migraciones.

## Estado previo

- 7A-0 contrato read-only: completado.
- 7A-A reporte read-only: completado.
- 7A-F cierre tecnico: completado.
- No existe tabla `cuentas_por_cobrar`.
- Fuentes existentes:
  - `reservaciones`
  - `reservacion_pagos`
  - `reservacion_abonos`
  - `solicitudes_factura`
  - `movimientos_caja`

## Diagnostico local

Conteos observados:

- `reservaciones`: 17.
- `reservacion_pagos`: 19.
- `reservacion_abonos`: 3.
- `solicitudes_factura`: 175.
- `movimientos_caja`: 1404.

Inconsistencias que bloquean CxC operativa directa:

- 3 pagos con reservacion inexistente o de otro hotel.
- 170 solicitudes de factura con reservacion inexistente o de otro hotel.
- 3 reservaciones con saldo estimado negativo.

## Riesgo principal

CxC operativa puede duplicar o contradecir:

- deuda de reservaciones;
- pagos de reservacion;
- abonos existentes;
- solicitudes de facturacion;
- movimientos de Caja;
- cortes de Caja;
- estados historicos de reservacion.

Por eso 7B no debe iniciar creando cobros directos. Primero debe crear una entidad
operativa aislada y reconciliable.

## Propuesta de modelo futuro

Solo si se autoriza una migracion aditiva posterior:

### `cuentas_por_cobrar`

Campos sugeridos:

- `id`
- `hotel_id`
- `origen_tipo`
- `origen_id`
- `huesped_id` nullable
- `reservacion_id` nullable
- `solicitud_factura_id` nullable
- `folio` nullable
- `concepto`
- `total`
- `saldo`
- `estado`
- `fecha_emision`
- `fecha_vencimiento` nullable
- `notas` nullable
- `creado_por_usuario_id` nullable
- timestamps

### `cuentas_por_cobrar_movimientos`

Campos sugeridos:

- `id`
- `hotel_id`
- `cuenta_por_cobrar_id`
- `tipo`
- `monto`
- `referencia` nullable
- `notas` nullable
- `creado_por_usuario_id` nullable
- timestamps

En 7B inicial, estos movimientos no deben crear Caja automaticamente.

## Reglas obligatorias futuras

- No generar CxC automaticamente desde reservaciones.
- No generar cobros automaticamente.
- No crear movimientos de Caja.
- No tocar cortes de Caja.
- No tocar `/api/sync`.
- No permitir CxC sin `hotel_id`.
- No permitir CxC con reservacion de otro hotel.
- No permitir CxC con huesped o factura de otro hotel.
- No permitir duplicados por `hotel_id + origen_tipo + origen_id` cuando el origen sea
  unico.
- No convertir saldos negativos en deuda.
- Toda operacion POST debe usar CSRF y auditoria.

## Subfases sugeridas

### 7B-A Migracion base CxC

- Crear tablas aditivas e idempotentes.
- No poblar datos.
- No crear Caja.

### 7B-B Listado y detalle read-only operativo

- Leer solo tabla CxC nueva.
- Mostrar estado vacio.
- Sin POST.

### 7B-C Generacion manual desde reservacion elegible

- Simulador previo.
- Accion manual con CSRF.
- Bloquear duplicados.
- No generar Caja.

### 7B-D Movimiento manual no Caja

- Registrar ajuste/nota/movimiento interno de CxC sin Caja.
- No marcar como pago real si no hay contrato contable.

### 7B-E Preflights y consistencia

- Duplicados.
- Origen inexistente.
- Hotel cruzado.
- Saldo invalido.
- Movimientos de Caja accidentalmente relacionados.

### 7B-F Revision, auditoria y cierre

- QA critica/funcional/visual/regresion.
- Rollback.
- Fuentes de verdad.

## Definition of Done futura

- Backup antes de cualquier migracion.
- Migracion aditiva, idempotente y reversible manualmente.
- `php -l`.
- Preflight CxC operativo.
- Health checker.
- HTTP sin sesion bloquea rutas.
- SQL read-only confirma cero Caja.
- QA manual antes de avanzar a cobros reales.

## Fuera de alcance

- Caja automatica.
- Cortes de Caja.
- Pagos reales.
- Facturacion nueva.
- CxC masiva automatica.
- Reconciliacion destructiva.
- `/api/sync`.

## Siguiente accion segura

No implementar 7B-A hasta reconciliar warnings o aceptar explicitamente una migracion
base vacia. Si se continua sin QA manual, el siguiente bloque mas seguro es 8A dashboard
operativo con KPIs read-only.
