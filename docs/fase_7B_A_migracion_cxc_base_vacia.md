# Fase 7B-A - Migracion base CxC vacia

## Estado

`MIGRACION_7B_A_CXC_BASE_VACIA_COMPLETADA`

## Objetivo

Crear una estructura aditiva para una CxC operativa futura, manteniendo las tablas
vacias y aisladas de Caja, cobros, pagos, abonos, reservaciones historicas y
`/api/sync`.

Esta fase no crea UI, rutas, modelos PHP, cobros ni saldos operativos.

## Backup previo

- Archivo: `backups/medisoft_hoteles_import_before_7b_a_cxc_base_20260618_163625.sql`
- SHA256: `0E13547DF43359E71C1A3503EFD8F3A5B5CD096A9BF843EB19F282C0FBE90514`
- Tamano: `3269772` bytes

## Migracion aplicada

- Archivo: `migrations/20260618_001_fase_7b_a_cxc_base_vacia.sql`
- Registro local en `migrations`:
  - `id`: `29`
  - `nombre`: `20260618_001_fase_7b_a_cxc_base_vacia.sql`
  - `batch`: `21`
  - `checksum`: `675832647b772c1a1cbf4126c7e9eef0128bb5d02a0fd254f9d47de9bf27cc4c`
  - `estado`: `ejecutada`
  - `ejecutada_en`: `2026-06-18 22:37:22`

## Tablas creadas

### `cuentas_por_cobrar`

Tabla base para cuentas futuras. Campos principales:

- `hotel_id`
- `origen_tipo`
- `origen_id`
- `huesped_id`
- `reservacion_id`
- `solicitud_factura_id`
- `folio`
- `concepto`
- `fecha_emision`
- `fecha_vencimiento`
- `estado`
- `moneda`
- `total`
- `saldo`
- `notas`
- usuarios de creacion/actualizacion
- timestamps

Controles estructurales:

- FK a `hoteles`, `huespedes`, `reservaciones`, `solicitudes_factura` y `usuarios`.
- Indices por hotel/estado, huesped, reservacion, factura y vencimiento.
- Unico `hotel_id + origen_tipo + origen_id`.
- Checks de importes no negativos y `saldo <= total`.

### `cuentas_por_cobrar_movimientos`

Tabla base para trazabilidad futura de movimientos internos CxC, sin Caja automatica.

Campos principales:

- `hotel_id`
- `cuenta_por_cobrar_id`
- `tipo_movimiento`
- `monto`
- `saldo_anterior`
- `saldo_posterior`
- `referencia`
- `notas`
- `usuario_id`
- `created_at`

Controles estructurales:

- FK a `hoteles`, `cuentas_por_cobrar` y `usuarios`.
- Indices por cuenta, hotel/tipo y usuario.
- Checks de importes no negativos.

## Conteos posteriores

- `cuentas_por_cobrar`: `0`
- `cuentas_por_cobrar_movimientos`: `0`
- Movimientos de Caja relacionados con CxC: `0`

## Confirmaciones de alcance

- No se poblo CxC desde reservaciones.
- No se poblo CxC desde pagos historicos.
- No se poblo CxC desde abonos historicos.
- No se poblo CxC desde solicitudes de factura.
- No se crearon cobros.
- No se crearon pagos ni abonos.
- No se crearon movimientos de Caja.
- No se tocaron cortes de Caja.
- No se modificaron rutas, modelos ni vistas PHP.
- No se toco `/api/sync`.

## Verificaciones

- Aplicacion SQL: `OK: estructura CxC base 7B-A verificada`.
- Preflight CxC read-only:
  - `OK: 14`
  - `WARNING: 3`
  - `ERROR: 0`
  - `PASS_WITH_WARNINGS_ALLOWED`
- Health general:
  - `OK: 278`
  - `WARNING: 24`
  - `ERROR: 0`
  - `PASS_WITH_WARNINGS_ALLOWED`

Los warnings son historicos/conocidos de 7A y no bloquean la estructura vacia 7B-A.

No se modifico PHP, por lo tanto no aplica `php -l` en esta subfase.

## Rollback

Rollback DB solo con autorizacion explicita y si ambas tablas siguen vacias:

1. Confirmar conteos:
   - `SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos;`
   - `SELECT COUNT(*) FROM cuentas_por_cobrar;`
2. Si ambos conteos son `0`, ejecutar en este orden:
   - `DROP TABLE cuentas_por_cobrar_movimientos;`
   - `DROP TABLE cuentas_por_cobrar;`
3. Borrar el registro de `migrations` para
   `20260618_001_fase_7b_a_cxc_base_vacia.sql`.

Si existe cualquier dato real, no ejecutar `DROP` ni `DELETE`. Exportar, reconciliar y
pedir autorizacion especifica antes de tocar datos.

La restauracion completa del backup solo debe hacerse con autorizacion explicita y
preferentemente primero en una base separada.

## Siguiente accion segura

7B-B ya fue implementada como listado/detalle read-only sobre las tablas nuevas,
mostrando estado vacio.

Siguiente accion segura: abrir 7B-C-0 solo como contrato de generacion manual futura
desde reservacion elegible.

Sigue prohibido poblar CxC inicial desde huerfanos, excedentes o historicos sin una fase
de escritura nueva con backup, auditoria y rollback.
