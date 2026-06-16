# Fase NP-C-0 - Contrato de ledger laboral

Estado: `CONTRATO_NP_C_LEDGER_LABORAL_COMPLETADO`

## Objetivo

Definir el contrato seguro para consultar y, en subfases posteriores, registrar conceptos
laborales por trabajador usando las tablas creadas en NP-A:

- `trabajador_pagos`
- `trabajador_anticipos`
- `trabajador_prestamos`
- `trabajador_asistencias`

La fase NP-C-0 no implementa rutas, no crea formularios, no escribe datos y no ejecuta
migraciones. Solo fija reglas para que el ledger laboral no se confunda con Caja ni con
salidas reales de dinero.

## Estado previo

- NP-0 contrato y diagnostico completado.
- NP-A migracion base de Personal aplicada y documentada.
- NP-A UI read-first implementada.
- NP-B-0 contrato CRUD trabajadores completado.
- NP-B-A CRUD basico de trabajadores implementado y verificado automaticamente.
- Bloque TLM cerrado tecnicamente con QA manual diferida.
- No hay categoria `Nomina` en Caja.
- No hay movimientos de Caja generados por Personal.
- `/api/sync` sigue fuera de alcance.

## Semaforo de riesgo

Riesgo: naranja.

Motivo:

- El ledger laboral maneja saldos sensibles por trabajador.
- Las tablas ya existen, pero todavia estan separadas de Caja.
- Un error de interpretacion podria parecer pago real aunque no debe mover dinero.

Mitigaciones:

- Subfase read-only antes de cualquier POST.
- Filtros obligatorios por `hotel_id`.
- Sin Caja, sin `movimientos_caja`, sin `cortes_caja`, sin categoria `Nomina`.
- Mensajes visibles indicando que el ledger laboral no es movimiento de Caja.
- Health/preflight antes de habilitar escrituras.
- Auditoria obligatoria en subfases con POST.

## Fuentes de verdad

- Trabajador: `trabajadores`.
- Conceptos laborales generales: `trabajador_pagos`.
- Anticipos: `trabajador_anticipos`.
- Prestamos: `trabajador_prestamos`.
- Asistencias: `trabajador_asistencias`.
- Documentos laborales legacy/base: `trabajador_documentos`.

Caja no es fuente de verdad de NP-C. Cualquier egreso real futuro requiere un bloque
NP-Caja independiente, con contrato nuevo y autorizacion explicita.

## Formula de saldo informativo

El saldo se calcula solo como lectura derivada:

```text
saldo_informativo =
  SUM(trabajador_pagos.monto WHERE efecto = 'a_favor' AND estado = 'activo')
- SUM(trabajador_pagos.monto WHERE efecto = 'en_contra' AND estado = 'activo')
- SUM(trabajador_anticipos.saldo_pendiente WHERE estado <> 'cancelado')
- SUM(trabajador_prestamos.saldo_pendiente WHERE estado <> 'cancelado')
```

Interpretacion:

- positivo: saldo informativo a favor del trabajador;
- negativo: saldo informativo en contra del trabajador;
- cero: sin saldo laboral pendiente segun las tablas de Personal.

Esta formula no crea pagos, no liquida anticipos, no descuenta prestamos y no toca Caja.

## Reglas obligatorias para subfases posteriores

- Todo acceso requiere sesion, hotel actual y guardas administrativas existentes.
- Toda consulta y escritura filtra por `hotel_id`.
- Cualquier concepto debe pertenecer a un trabajador existente del mismo hotel.
- Solo trabajadores activos podran recibir nuevos movimientos en futuras subfases con POST.
- Montos deben ser mayores a cero en escrituras futuras.
- No se permite `DELETE`; anulacion o cancelacion debe ser logica.
- Todos los POST futuros deben usar CSRF.
- Toda escritura futura debe auditarse con `AuditService` si esta disponible.
- No se permite escribir en Caja, CxP, CxC, compras, reservaciones ni `/api/sync`.

## Subfases sugeridas

- NP-C-A: ledger laboral read-only por trabajador y resumen global por hotel.
- NP-C-B: registro manual controlado de concepto laboral general en `trabajador_pagos`,
  sin Caja y sin marcarlo como egreso real.
- NP-C-C: registro manual controlado de anticipos y prestamos, sin Caja.
- NP-C-D: asistencia manual basica, sin nomina automatica.
- NP-C-E: health/preflight de consistencia de ledger laboral.
- NP-C-F: revision tecnica, auditoria y cierre del bloque.

## Definition of Done NP-C-A

- No agrega POST.
- No crea datos.
- No modifica Caja ni `/api/sync`.
- Muestra resumen informativo por trabajador.
- Muestra ultimos conceptos laborales, anticipos, prestamos y asistencias si existen.
- Estado vacio claro si las tablas estan vacias.
- No expone documentos ni rutas de archivo.
- Health checker conoce el contrato read-only.
- `php -l`, health, SQL read-only y `git diff --check` pasan sin errores.

## Rollback

- NP-C-0: revertir el commit documental.
- NP-C-A futura: revertir commit de codigo read-only; no tocar DB.
- NP-C-B en adelante: si se crean datos de prueba, no usar `DELETE`; documentar IDs y
  anular/cancelar con flujo autorizado o restaurar backup si la prueba fue aislada.

## Fuera de alcance

- Pagos reales.
- Abonos.
- Caja, cortes y movimientos.
- Categoria `Nomina`.
- Transferencias bancarias o conciliacion.
- Automatizacion desde tareas o asistencia.
- Permisos profundos nuevos.
- Documentos laborales/uploads.
- `/api/sync`, PWA/offline/cache.
