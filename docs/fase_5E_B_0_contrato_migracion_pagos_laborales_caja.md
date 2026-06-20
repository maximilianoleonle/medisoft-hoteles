# Fase 5E-B-0 - Contrato migracion pagos laborales con Caja

## Estado

`CONTRATO_5E_B_0_MIGRACION_PAGOS_LABORALES_CAJA_COMPLETADO`

## Objetivo

Definir el contrato tecnico para una futura migracion aditiva que cree una tabla
independiente de pagos laborales reales vinculados a Caja.

Esta fase es solo documental. No crea migracion, no ejecuta SQL, no modifica DB,
no toca PHP operativo, rutas, controladores, modelos, vistas, formularios, Caja,
cortes, movimientos, permisos, PWA/offline ni `/api/sync`.

## Contexto previo

La fase 5E-A dejo verificado:

- `trabajador_pagos_caja` no existe todavia.
- `trabajador_pagos` sigue sin columnas financieras de Caja.
- No hay rutas laborales de pago con Caja.
- No existe `TrabajadorPagoCajaService`.
- No hay movimientos/categorias de Caja con nomina o pago laboral.
- Preflight 5E-A: `OK: 34`, `WARNING: 1`, `ERROR: 0`.
- Health general: `OK: 305`, `WARNING: 25`, `ERROR: 0`.

Advertencia vigente: no hay trabajadores activos para QA futura de pago real.

## Decision de esquema

Crear en una fase futura una tabla nueva:

- `trabajador_pagos_caja`.

No reutilizar `trabajador_pagos` para pagos reales.

Motivo:

- `trabajador_pagos` representa conceptos laborales manuales.
- Un pago real debe enlazar con Caja, corte, referencia, auditoria y usuario.
- Mezclar ambos conceptos haria ambiguo si un registro laboral es devengo,
  descuento, ajuste o salida real de dinero.

## Tabla futura propuesta

La migracion 5E-B-A debera crear una tabla aditiva e idempotente con estos campos
minimos:

- `id INT NOT NULL AUTO_INCREMENT`.
- `hotel_id INT NOT NULL`.
- `trabajador_id INT NOT NULL`.
- `movimiento_caja_id INT NOT NULL`.
- `corte_id INT NOT NULL`.
- `monto DECIMAL(12,2) NOT NULL`.
- `metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia') NOT NULL`.
- `referencia VARCHAR(120) NOT NULL`.
- `periodo_inicio DATE NULL`.
- `periodo_fin DATE NULL`.
- `concepto VARCHAR(160) NULL`.
- `fecha_pago DATETIME NOT NULL`.
- `estado ENUM('pagado', 'revertido') NOT NULL DEFAULT 'pagado'`.
- `notas TEXT NULL`.
- `created_by INT NULL`.
- `updated_by INT NULL`.
- `created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP`.
- `updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP`.

Campos deliberadamente no incluidos en esta primera migracion:

- datos fiscales;
- timbrado;
- banco/cuenta destino;
- dispersion bancaria;
- recibo PDF;
- firma;
- columnas de abono a anticipos/prestamos;
- columnas de reversion detallada.

La reversion queda para contrato independiente. El campo `estado` solo reserva el
estado final del pago original si una fase futura la implementa.

## Indices y llaves requeridas

La migracion futura debera incluir:

- `PRIMARY KEY (id)`.
- `UNIQUE KEY uk_trabajador_pagos_caja_hotel_referencia (hotel_id, referencia)`.
- `UNIQUE KEY uk_trabajador_pagos_caja_movimiento (movimiento_caja_id)`.
- `KEY idx_trabajador_pagos_caja_hotel_trabajador (hotel_id, trabajador_id)`.
- `KEY idx_trabajador_pagos_caja_hotel_fecha (hotel_id, fecha_pago)`.
- `KEY idx_trabajador_pagos_caja_hotel_corte (hotel_id, corte_id)`.
- `KEY idx_trabajador_pagos_caja_estado (hotel_id, estado)`.
- `KEY idx_trabajador_pagos_caja_created_by (created_by)`.
- `KEY idx_trabajador_pagos_caja_updated_by (updated_by)`.

Llaves foraneas recomendadas:

- `hotel_id` hacia `hoteles(id)`.
- `trabajador_id` hacia `trabajadores(id)`.
- `movimiento_caja_id` hacia `movimientos_caja(id)`.
- `corte_id` hacia `cortes_caja(id)`.
- `created_by` hacia `usuarios(id)`.
- `updated_by` hacia `usuarios(id)`.

Validacion adicional requerida por servicio futuro:

- `trabajador.hotel_id = trabajador_pagos_caja.hotel_id`.
- `corte.hotel_id = trabajador_pagos_caja.hotel_id`.
- `movimiento_caja.hotel_id = trabajador_pagos_caja.hotel_id`.
- `movimiento_caja.corte_id = trabajador_pagos_caja.corte_id`.

Estas reglas no deben depender solo de llaves foraneas simples.

## Checks requeridos

La migracion futura debe intentar incluir:

- `CHECK (monto > 0)`.
- `CHECK (periodo_inicio IS NULL OR periodo_fin IS NULL OR periodo_fin >= periodo_inicio)`.

Si el motor MySQL local no aplica `CHECK`, el servicio futuro debera validar esas
reglas obligatoriamente.

## Contrato de migracion 5E-B-A

Solo con autorizacion explicita, backup previo y validacion post-backup.

La migracion futura debe:

1. Ser aditiva e idempotente.
2. Crear solo `trabajador_pagos_caja`.
3. No insertar pagos historicos.
4. No crear movimientos de Caja.
5. No crear categorias de Caja.
6. No alterar `trabajador_pagos`.
7. No cambiar anticipos ni prestamos.
8. No cambiar cortes ni saldos.
9. Registrar la migracion en `migrations`.
10. Actualizar preflight/health para validar la nueva tabla.

La migracion futura no debe:

- ejecutar `ALTER TABLE trabajador_pagos`;
- cambiar el enum actual de `trabajador_pagos`;
- insertar en `movimientos_caja`;
- insertar en `categorias_movimientos`;
- crear rutas;
- crear botones;
- crear servicios de pago;
- modificar permisos/auth;
- tocar PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Contrato de referencia

Formato recomendado para futuras referencias automaticas:

- `NOM-{trabajador_id}-PAY-{pago_id}`;

o, si el movimiento Caja se crea antes de conocer el `pago_id`:

- `NOM-{trabajador_id}-MC-{movimiento_caja_id}`.

Reglas:

- La referencia debe ser unica por hotel.
- La referencia debe existir tanto en `trabajador_pagos_caja.referencia` como en
  `movimientos_caja.referencia`.
- El servicio futuro debe rechazar duplicados.
- No usar referencias manuales vacias para tarjeta o transferencia.

## Contrato de Caja futuro

La migracion 5E-B-A no crea movimientos. Una fase posterior de servicio debe crear:

- `movimientos_caja.tipo = 'gasto'`.
- `movimientos_caja.categoria = 'Pago laboral'` o equivalente definido por contrato.
- `movimientos_caja.hotel_id = trabajador_pagos_caja.hotel_id`.
- `movimientos_caja.corte_id = trabajador_pagos_caja.corte_id`.
- `movimientos_caja.monto = trabajador_pagos_caja.monto`.
- `movimientos_caja.metodo_pago = trabajador_pagos_caja.metodo_pago`.
- `movimientos_caja.referencia = trabajador_pagos_caja.referencia`.

La categoria de Caja no debe crearse en esta migracion.

## Rollback futuro de la migracion

Rollback permitido solo si:

- `trabajador_pagos_caja` existe;
- `SELECT COUNT(*) FROM trabajador_pagos_caja` devuelve `0`;
- la migracion no fue usada por ningun servicio operativo;
- hay autorizacion explicita para tocar DB.

Rollback conceptual:

1. Confirmar tabla vacia.
2. Eliminar la tabla `trabajador_pagos_caja`.
3. Retirar registro de `migrations` correspondiente.
4. Ejecutar preflight 5E-A y health general.

Si la tabla tiene registros, no se debe eliminar. Se requiere contrato de
reconciliacion.

## Validaciones obligatorias para 5E-B-A

Antes:

1. Backup completo.
2. SHA256 documentado.
3. Preflight 5E-A con `ERROR: 0`.
4. Health general con `ERROR: 0`.
5. Confirmar que `trabajador_pagos_caja` no existe.

Despues:

1. `trabajador_pagos_caja` existe.
2. La tabla queda vacia.
3. Columnas, indices y llaves esperadas existen.
4. No cambian conteos en `trabajador_pagos`, `trabajador_anticipos`,
   `trabajador_prestamos`, `movimientos_caja` ni `categorias_movimientos`.
5. Preflight 5E-A actualizado con `ERROR: 0`.
6. Health general con `ERROR: 0`.
7. `/api/sync` sigue bloqueado con HTTP 423 y JSON `sync_temporarily_disabled`.

## Fuera de alcance de 5E-B-0

No se implementa:

- archivo SQL de migracion;
- ejecucion de migracion;
- backup;
- tabla nueva;
- ruta;
- controlador;
- modelo operativo;
- servicio;
- vista;
- formulario;
- boton de pago;
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

## Siguiente paso seguro

El siguiente paso seguro es `5E-B-A`: crear y ejecutar la migracion aditiva de
`trabajador_pagos_caja`, solo si el usuario autoriza explicitamente tocar DB y con
backup previo.

## Seguimiento 5E-B-A

Estado posterior:

`MIGRACION_5E_B_A_PAGOS_LABORALES_CAJA_COMPLETADA`

Documento:

- `docs/fase_5E_B_A_migracion_pagos_laborales_caja.md`.

Migracion aplicada:

- `migrations/20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql`.

Backup previo:

- `backups/medisoft_hoteles_import_before_5e_b_a_trabajador_pagos_caja_20260619_220444.sql`.
- SHA256:
  `2E279999DA95C0216942F1FE480E5E43E96AAE42A06DA7C9FD83633BC53898BC`.

Resultado:

- `trabajador_pagos_caja` creada y vacia.
- Preflight 5E: `OK: 37`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 307`, `WARNING: 26`, `ERROR: 0`.

No se implemento pago real, movimiento de Caja, categoria de Caja, ruta, vista,
formulario, servicio ni POST.
