# Fase 7B-C-A - Generacion manual CxC desde reservacion

## Estado

`GENERACION_MANUAL_CXC_7B_C_A_VALIDADA_MANUALMENTE`

## Objetivo

Implementar una accion manual y controlada para generar una cuenta por cobrar operativa
desde una reservacion elegible, usando solo el saldo pendiente neto.

Esta fase habilita la primera escritura CxC real, pero no registra cobros, pagos,
abonos, movimientos de Caja ni cambios sobre reservaciones.

## Backup previo

- Archivo:
  `backups/medisoft_hoteles_import_before_7b_c_a_cxc_manual_20260618_170535.sql`
- SHA256:
  `877BA8D0E9F97D8DA3047392EE2C5CC91DACEE20A94F5069BF57A2164377EE7B`
- Tamano: `1647809` bytes
- Verificacion: dump SQL legible con encabezado `MySQL dump 10.13`.

## Cambios implementados

Rutas:

- `POST /cuentas-por-cobrar/generar-desde-reservacion/{id}`

Modelo:

- `CuentaPorCobrar::generarDesdeReservacionElegible()`
- `CuentaPorCobrar::tablasGeneracionManualDisponibles()`
- anotacion de elegibilidad en el listado derivado.

Controller:

- `CuentaPorCobrarController::generarDesdeReservacionAction()`

Vista:

- `/cuentas-por-cobrar` conserva su filtro `GET`.
- Agrega un formulario `POST` con `csrf_field()` solo en reservaciones elegibles.
- Si ya existe CxC operativa, muestra enlace a la CxC existente.
- Si no es elegible, muestra bloqueo informativo.

Preflight:

- `tools/saas/preflight_cuentas_por_cobrar.php` valida 7A-A, 7B-B y 7B-C-A.

## Reglas de elegibilidad

La reservacion debe cumplir:

- pertenecer al hotel actual;
- existir bajo `id + hotel_id`;
- no estar cancelada;
- tener huesped valido;
- tener `precio_total > 0`;
- tener saldo pendiente neto mayor a cero;
- no tener saldo negativo/excedente;
- no tener una CxC existente para `hotel_id + origen_tipo reservacion + origen_id`.

El saldo neto se calcula como:

`precio_total - pagos scoped - abonos scoped`

Pagos y abonos solo cuentan si coinciden por `hotel_id + reservacion_id`.

## Escrituras permitidas

Solo se escribe en:

- `cuentas_por_cobrar`;
- `cuentas_por_cobrar_movimientos`;
- `logs_auditoria`.

La cuenta se crea con:

- `origen_tipo = reservacion`;
- `origen_id = reservacion.id`;
- `total = saldo pendiente neto`;
- `saldo = saldo pendiente neto`;
- `estado = pendiente`;
- `folio = CXC-RES-{reservacion_id}`.

El movimiento inicial se crea con:

- `tipo_movimiento = CREACION`;
- `monto = saldo pendiente neto`;
- `saldo_anterior = 0.00`;
- `saldo_posterior = saldo pendiente neto`;
- referencia igual al folio.

## Escrituras prohibidas

No se escribe en:

- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `reservaciones`;
- `reservacion_pagos`;
- `reservacion_abonos`;
- `solicitudes_factura`;
- PWA/offline;
- `/api/sync`.

## Validaciones tecnicas

- El POST exige sesion por `before()`.
- El POST exige contexto hotelero y modulo `reservaciones`.
- El POST exige CSRF.
- El modelo controla su propia transaccion.
- La reservacion candidata se lee con `FOR UPDATE`.
- La busqueda de duplicado CxC se lee con `FOR UPDATE`.
- La auditoria registra `cuentas_por_cobrar.generada_desde_reservacion`.
- El preflight falla si detecta escrituras hacia tablas sensibles.

## Verificacion inicial

- `php -l` en modelo, controller, vista, rutas y preflight: OK.
- Preflight CxC: `OK: 29`, `WARNING: 3`, `ERROR: 0`.
- Health general: `OK: 277`, `WARNING: 25`, `ERROR: 0`.
- HTTP sin sesion al POST nuevo: `303` a `/login`.
- Conteos posteriores:
  - `cuentas_por_cobrar = 0`;
  - `cuentas_por_cobrar_movimientos = 0`;
  - movimientos de Caja con referencia textual a CxC = `0`.
- `git diff --check`: sin errores; solo warnings CRLF del entorno.
- `/api/sync`: el codigo mantiene `sync_temporarily_disabled` con HTTP `423` en
  `ApiController::syncAction()`.
- Warnings historicos:
  - 3 pagos con reservacion inexistente o de otro hotel;
  - 170 solicitudes de factura con reservacion inexistente o de otro hotel;
  - 3 reservaciones con saldo estimado negativo.

No se creo una CxC real durante esta verificacion automatica.

## QA manual validada

El usuario reporto que la prueba manual funciono correctamente.

Evidencia post-QA:

- CxC creada: `cuentas_por_cobrar.id = 1`.
- Hotel: `4`.
- Origen: `reservacion`.
- Reservacion: `24`.
- Huesped: `825`.
- Folio: `CXC-RES-24`.
- Estado: `pendiente`.
- Total/saldo: `4250.00`.
- Movimiento interno: `cuentas_por_cobrar_movimientos.id = 1`.
- Tipo movimiento: `CREACION`.
- Saldo anterior/posterior: `0.00 -> 4250.00`.
- Auditoria: `logs_auditoria.id = 105`,
  `cuentas_por_cobrar.generada_desde_reservacion`.
- Movimientos de Caja con referencia textual a CxC: `0`.
- Duplicados por reservacion: `0`.
- CxC sin movimiento `CREACION`: `0`.
- Movimientos CxC huerfanos: `0`.

## Siguiente paso seguro

Cerrar 7B-C-A con auditoria post-QA. No implementar cobro de CxC, pagos de clientes,
abonos, integracion con Caja, facturacion nueva ni anulaciones hasta un contrato separado.
