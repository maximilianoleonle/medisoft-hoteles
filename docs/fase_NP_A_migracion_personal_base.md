# Fase NP-A - Migracion base de Personal

Estado tecnico: `MIGRACION_NP_A_PERSONAL_BASE_COMPLETADA_QA_DIFERIDA`.

## Objetivo

Crear la estructura base de Personal y Nomina como modulo independiente de trabajadores,
sin crear registros, sin tocar Caja, sin alterar usuarios de forma destructiva y sin
tocar `/api/sync`.

## Backup previo valido

- Ruta: `src/storage/backups/phase_np_a_20260616_021311_before_personal_base_medisoft_hoteles_import.sql`
- Bytes: `3161464`
- SHA256: `0F9E64B040437A73D559534E5753133F4C3508C29F5B9EA0278B351097666246`

Nota: hubo un intento previo con usuario sin privilegios suficientes para rutinas; no se
toma como respaldo valido. El respaldo valido es el listado arriba.

## Migracion

- Archivo: `migrations/20260616_001_fase_np_a_personal_base.sql`
- Batch local: `19`
- Registro en `migrations`: `ejecutada`

Tablas creadas:

- `trabajadores`
- `trabajador_pagos`
- `trabajador_anticipos`
- `trabajador_prestamos`
- `trabajador_asistencias`
- `trabajador_documentos`

## Reglas aplicadas

- Todas las tablas tienen `hotel_id`.
- Tablas hijas tienen `trabajador_id`.
- FKs con `ON DELETE RESTRICT` para trabajadores y hoteles.
- Vinculo opcional a `usuarios` via `usuario_id`, `created_by`, `updated_by` o
  `subido_por`, sin alterar `usuarios`.
- Montos protegidos con `CHECK` de no negatividad.
- `trabajador_asistencias` tiene `UNIQUE (hotel_id, trabajador_id, fecha)`.
- No se insertaron datos semilla.
- No se creo categoria Nomina en Caja.
- No se creo movimiento de Caja.

## Conteos posteriores

- `trabajadores`: 0
- `trabajador_pagos`: 0
- `trabajador_anticipos`: 0
- `trabajador_prestamos`: 0
- `trabajador_asistencias`: 0
- `trabajador_documentos`: 0
- `movimientos_caja` con categoria Nomina: 0
- `categorias_movimientos` Nomina: 0

## Fuera de alcance

- Listado/ficha/CRUD de trabajador.
- Pagos reales.
- Movimientos de Caja.
- Categoria Nomina en Caja.
- Asistencia operativa.
- Saldos calculados en UI.
- Vinculo real con mantenimiento/tareas.
- `/api/sync`.

## QA automatica

- Migracion aplicada con resultado: `OK: estructura Personal base NP-A verificada`.
- Health checker actualizado para validar tablas NP-A, migracion registrada y cero
  Caja/Nomina.

## Actualizacion NP-A UI read-first

La subfase visual read-first ya expone listado y ficha de trabajadores en modo lectura:

- `GET /trabajadores`
- `GET /trabajadores/{id}`
- Sin POST, sin altas, sin edicion, sin pagos, sin anticipos, sin prestamos, sin Caja.
- Guardas: sesion, contexto hotel, modulo `usuarios` y permiso `usuarios.view`.
- Documentacion especifica: `docs/fase_NP_A_ui_personal_readonly.md`.

## QA manual diferida

Cuando se retome QA en navegador, validar:

- tablas vacias no rompen UI;
- listado `/trabajadores` muestra estado vacio;
- filtros GET funcionan;
- ficha `/trabajadores/{id}` respeta hotel actual cuando existan registros;
- trabajador puede existir sin usuario del sistema;
- todos los listados filtran por `hotel_id`;
- Caja no cambia.

## Rollback

Solo con autorizacion explicita y si las seis tablas siguen vacias:

```sql
DROP TABLE trabajador_documentos;
DROP TABLE trabajador_asistencias;
DROP TABLE trabajador_prestamos;
DROP TABLE trabajador_anticipos;
DROP TABLE trabajador_pagos;
DROP TABLE trabajadores;
DELETE FROM migrations WHERE nombre = '20260616_001_fase_np_a_personal_base.sql';
```

No usar rollback destructivo si ya existen trabajadores o movimientos reales.
