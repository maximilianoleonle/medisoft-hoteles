# Fase 5E-P-0 - Contrato trazabilidad fuerte de pago desde snapshot

Estado formal:
`CONTRATO_5E_P_0_TRAZABILIDAD_PAGO_SNAPSHOT_PRENOMINA_COMPLETADO`.

Fecha: 2026-06-21.

## Objetivo

Definir el contrato para una futura trazabilidad fuerte entre pagos laborales
registrados con Caja y el snapshot de pre-nomina que sirvio como contexto.

Esta fase es solo documental. No agrega codigo, rutas, controladores, modelos,
servicios, vistas, migraciones, permisos, datos, storage, Caja, PWA/offline ni
`/api/sync`.

## Contexto

5E-N-A ya permite registrar un pago individual desde un snapshot aprobado de
pre-nomina. El flujo actual conserva el origen en:

- concepto;
- notas;
- referencia;
- auditoria `trabajadores.nomina_snapshot_pago_caja_registrado`.

Ese nivel de trazabilidad es suficiente para operar localmente, pero no es la
relacion mas fuerte para reportes, conciliacion o auditoria financiera futura.

## Principio central

La trazabilidad fuerte debe ser aditiva y nullable.

No debe recalcular snapshots, no debe reescribir pagos existentes y no debe
convertir la pre-nomina en nomina oficial.

## Migracion futura candidata

Una futura fase 5E-P-A podria agregar a `trabajador_pagos_caja`:

```sql
nomina_periodo_id INT NULL,
nomina_periodo_detalle_id INT NULL
```

Indices candidatos:

```sql
KEY idx_trabajador_pagos_caja_nomina_periodo (nomina_periodo_id),
KEY idx_trabajador_pagos_caja_nomina_detalle (nomina_periodo_detalle_id)
```

Llaves foraneas candidatas, solo si el motor/hosting lo permite sin riesgo:

```sql
FOREIGN KEY (nomina_periodo_id)
  REFERENCES trabajador_nomina_periodos(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE,

FOREIGN KEY (nomina_periodo_detalle_id)
  REFERENCES trabajador_nomina_periodo_detalles(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE
```

Si se agregan FKs, deben ser restrictivas. No debe haber `ON DELETE CASCADE`.

## Reglas de integridad

Una futura implementacion debe validar en aplicacion:

- `trabajador_pagos_caja.hotel_id` debe coincidir con
  `trabajador_nomina_periodos.hotel_id`;
- `trabajador_pagos_caja.hotel_id` debe coincidir con
  `trabajador_nomina_periodo_detalles.hotel_id`;
- el detalle debe pertenecer al periodo;
- el trabajador del detalle debe coincidir con el trabajador pagado;
- el snapshot debe estar `aprobado` al momento del pago;
- el pago debe seguir usando el tope:

```text
min(
  pendiente_pago_sugerido_del_snapshot,
  saldo_laboral_disponible_recalculado_en_vivo
)
```

La base de datos puede ayudar con FKs, pero no sustituye las validaciones de
hotel, trabajador, estado y tope.

## Backfill prohibido por defecto

5E-P-0 no autoriza backfill de pagos existentes.

Una futura 5E-P-A debe dejar los registros historicos con:

```text
nomina_periodo_id = NULL
nomina_periodo_detalle_id = NULL
```

Solo los pagos nuevos desde snapshot aprobado podrian guardar ambos IDs.

Cualquier backfill historico requeriria contrato independiente porque podria
inferir relaciones erroneas a partir de fechas, referencias o notas.

## Cambios futuros permitidos solo con autorizacion

Una futura 5E-P-A podria tocar, con autorizacion explicita:

- migracion aditiva nullable en `trabajador_pagos_caja`;
- modelo/servicio de pago laboral con Caja para persistir los IDs opcionales;
- servicio de pago desde snapshot para enviar los IDs;
- checkers para validar columnas, indices y ausencia de backfill accidental;
- prueba rollback que cree pago temporal con relacion y revierta todo.

No deberia requerir rutas nuevas ni vistas nuevas.

## Prohibido por este contrato

5E-P-0 no autoriza:

- ejecutar migraciones;
- modificar base de datos;
- alterar pagos historicos;
- backfill automatico;
- reabrir snapshots;
- modificar detalles de snapshot;
- recalcular totales;
- crear nomina oficial;
- CFDI;
- timbrado;
- dispersion bancaria;
- pago masivo;
- liquidacion automatica de anticipos o prestamos;
- cambios en Caja/cortes/movimientos fuera del flujo ya controlado;
- storage;
- cambios en PWA/offline, IndexedDB, cache names o `/api/sync`.

## QA futura minima

1. Crear backup SQL antes de migrar.
2. Aplicar migracion aditiva nullable en entorno local.
3. Confirmar que pagos historicos quedan con columnas nuevas en `NULL`.
4. Confirmar que no cambian montos, saldos, estados ni movimientos de Caja.
5. Registrar un pago nuevo desde snapshot aprobado.
6. Confirmar que `trabajador_pagos_caja.nomina_periodo_id` queda ligado al
   periodo correcto.
7. Confirmar que `trabajador_pagos_caja.nomina_periodo_detalle_id` queda ligado
   al detalle correcto.
8. Confirmar que hotel, trabajador y detalle coinciden.
9. Intentar guardar una relacion de otro hotel y confirmar bloqueo.
10. Ejecutar prueba rollback y confirmar cero persistencia.
11. Ejecutar preflight de pagos laborales Caja con `ERROR: 0`.
12. Ejecutar health general con `ERROR: 0`.
13. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
    `sync_temporarily_disabled`.

## Rollback futuro esperado

Si una futura 5E-P-A falla antes de produccion:

1. Revertir codigo de servicio/checkers.
2. Eliminar columnas nullable agregadas, solo si no hay datos productivos que
   dependan de ellas.
3. Eliminar indices/FKs asociados.
4. Restaurar backup si hubo datos persistidos durante QA.

En produccion, cualquier rollback debe partir de backup y confirmacion explicita
del usuario.

## Criterio de avance futuro

Una futura 5E-P-A solo debe iniciar con autorizacion explicita para tocar
migraciones, base de datos, modelo/servicio de pagos laborales con Caja,
servicio de snapshot, checkers y prueba rollback.
