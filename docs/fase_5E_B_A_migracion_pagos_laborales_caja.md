# Fase 5E-B-A - Migracion pagos laborales con Caja

## Estado

`MIGRACION_5E_B_A_PAGOS_LABORALES_CAJA_COMPLETADA`

## Objetivo

Crear la tabla independiente `trabajador_pagos_caja` para una futura fase de pagos
laborales reales con Caja.

Esta fase solo crea esquema. No registra pagos, no crea movimientos de Caja, no crea
categorias de Caja, no agrega rutas, controladores, modelos operativos, vistas,
formularios, servicios, permisos ni cambios en PWA/offline o `/api/sync`.

## Autorizacion

El usuario autorizo explicitamente continuar con la migracion 5E-B-A.

## Backup previo

- Archivo:
  `backups/medisoft_hoteles_import_before_5e_b_a_trabajador_pagos_caja_20260619_220444.sql`.
- SHA256:
  `2E279999DA95C0216942F1FE480E5E43E96AAE42A06DA7C9FD83633BC53898BC`.
- Tamano:
  `1685511` bytes.

El primer intento de backup con el usuario de aplicacion fallo por privilegios de
`PROCESS`/rutinas. Se repitio correctamente con usuario root local del contenedor y
`--no-tablespaces`.

## Migracion aplicada

- Archivo:
  `migrations/20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql`.
- Resultado MySQL:
  `OK: trabajador_pagos_caja creada, vacia y con contrato 5E-B-A`.
- Registro en `migrations`:
  `20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql`, estado `ejecutada`.
- Fecha registrada en DB:
  `2026-06-20 04:08:07`.

## Esquema creado

Tabla:

- `trabajador_pagos_caja`.

Columnas:

- `id`.
- `hotel_id`.
- `trabajador_id`.
- `movimiento_caja_id`.
- `corte_id`.
- `monto`.
- `metodo_pago`.
- `referencia`.
- `periodo_inicio`.
- `periodo_fin`.
- `concepto`.
- `fecha_pago`.
- `estado`.
- `notas`.
- `created_by`.
- `updated_by`.
- `created_at`.
- `updated_at`.

Reglas principales:

- `monto > 0`.
- periodo final no puede ser anterior al inicial cuando ambos existen.
- referencia unica por hotel.
- un movimiento de Caja solo puede vincularse a un pago laboral.
- llaves hacia hotel, trabajador, movimiento de Caja, corte y usuarios de auditoria.

## Validacion posterior

Resultado de metadata:

- `trabajador_pagos_caja`: `0` registros.
- columnas detectadas: `18`.
- indices detectados: `11` totales; todos los indices requeridos presentes.
- constraints detectados: `11` totales; todos los constraints requeridos presentes.

Conteos operativos posteriores:

- `trabajador_pagos`: `0`.
- `trabajador_anticipos`: `0`.
- `trabajador_prestamos`: `0`.
- `movimientos_caja`: `1410`.
- `categorias_movimientos`: `33`.

No se insertaron pagos laborales, conceptos laborales, anticipos, prestamos,
movimientos de Caja ni categorias de Caja.

## Herramientas actualizadas

- `src/tools/saas/preflight_personal_pagos_caja.php`.
- `src/tools/saas/health_check_fase_1a.php`.

El preflight ahora valida la tabla si existe:

- columnas;
- indices;
- constraints;
- tabla vacia antes del servicio de pago real;
- migracion registrada como ejecutada.

El health general valida el mismo contrato desde la auditoria global.

## Resultados automaticos

Preflight 5E:

- `OK: 37`.
- `WARNING: 0`.
- `ERROR: 0`.

Health general:

- `OK: 307`.
- `WARNING: 26`.
- `ERROR: 0`.

## Fuera de alcance

No se implementa:

- ruta;
- controlador;
- modelo operativo;
- servicio de pago;
- vista;
- formulario;
- boton de pago;
- token de pago;
- prueba rollback de servicio;
- pago laboral real;
- movimiento de Caja;
- categoria de Caja;
- abono/liquidacion de anticipos o prestamos;
- nomina automatica;
- timbrado;
- bancos;
- dispersion;
- recibos;
- reversion;
- permisos nuevos;
- PWA/offline, IndexedDB, cache names ni `/api/sync`.

`/api/sync` debe seguir bloqueado con HTTP 423 y JSON
`sync_temporarily_disabled`.

## Rollback

Rollback permitido solo con autorizacion explicita y si la tabla sigue vacia:

```sql
SELECT COUNT(*) AS pagos_laborales_caja
FROM trabajador_pagos_caja;

DROP TABLE trabajador_pagos_caja;

DELETE FROM migrations
WHERE nombre = '20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql';
```

Si existen pagos laborales reales en el futuro, no eliminar la tabla sin una fase de
reversion/reconciliacion.

## Siguiente paso seguro

El siguiente paso seguro es `5E-C-0`: contrato de simulador GET/read-only para pago
laboral contra Caja.

No implementar servicio real ni POST de pago sin contrato separado, prueba rollback y
QA manual.

## Seguimiento 5E-C-0

Estado posterior:

`CONTRATO_5E_C_0_SIMULADOR_PAGO_LABORAL_CAJA_COMPLETADO`

Documento:

- `docs/fase_5E_C_0_contrato_simulador_pago_laboral_caja.md`.

Se definio el contrato para una futura pantalla GET/read-only de simulacion de pago
laboral contra Caja.

No se agregaron rutas, vistas, controladores, modelos, formularios, servicios, POST,
pagos reales ni movimientos de Caja.
