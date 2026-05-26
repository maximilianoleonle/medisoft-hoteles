# Fase 2A.2-C - Cierre tecnico Habitaciones

## Objetivo

Documentar el estado tecnico del modulo Habitaciones despues de integrar `hotel_id` de forma progresiva, manteniendo el sistema compatible con el modo mono-hotel Los Cedros y sin avanzar todavia a Reservaciones, Caja, PWA/offline, Dashboard ni APIs globales.

## Que Se Logro

### Schema y backfill

La base local `medisoft_hoteles_import` ya tiene `hotel_id INT NULL` en las tablas operativas iniciales del modulo Habitaciones:

- `tipos_habitacion`
- `habitaciones`
- `habitacion_imagenes`
- `mantenimientos_habitaciones`

Los registros existentes fueron asociados al hotel inicial Los Cedros mediante backfill.

Conteos documentados despues de la ejecucion local:

| Tabla | Registros | Registros con hotel_id Los Cedros | hotel_id NULL |
| --- | ---: | ---: | ---: |
| `tipos_habitacion` | 6 | 6 | 0 |
| `habitaciones` | 49 | 49 | 0 |
| `habitacion_imagenes` | 2 | 2 | 0 |
| `mantenimientos_habitaciones` | 9 | 9 | 0 |

### Indices y foreign keys

Se agregaron indices simples por `hotel_id`:

- `idx_tipos_habitacion_hotel_id`
- `idx_habitaciones_hotel_id`
- `idx_habitacion_imagenes_hotel_id`
- `idx_mantenimientos_habitaciones_hotel_id`

Se agregaron foreign keys hacia `hoteles(id)`:

- `fk_tipos_habitacion_hotel`
- `fk_habitaciones_hotel`
- `fk_habitacion_imagenes_hotel`
- `fk_mantenimientos_habitaciones_hotel`

### Codigo funcional

El codigo puro del modulo Habitaciones ya escribe y respeta `hotel_id` usando compatibilidad mono-hotel con Los Cedros.

Puntos cubiertos:

- Creacion de habitaciones con `hotel_id`.
- Lecturas propias de habitaciones filtradas por `hotel_id`.
- Actualizaciones y cambios de estado validados por `hotel_id`.
- Imagenes de habitaciones con `hotel_id`.
- Lecturas y operaciones de imagenes validadas por `hotel_id`.
- Mantenimientos de habitaciones con `hotel_id`.
- Operaciones de mantenimiento validadas por `hotel_id`.

### Lecturas cruzadas endurecidas

Las lecturas cruzadas de bajo y medio riesgo entre Habitaciones y Reservaciones fueron endurecidas usando `habitaciones.hotel_id` como filtro, sin agregar `hotel_id` a tablas de Reservaciones.

Consultas cubiertas:

- `Habitacion::tieneReservacionPendienteHoy`
- `Habitacion::disponiblesEntreFechas`
- `Habitacion::disponiblesEnFechas`
- `Habitacion::getOcupacionActual`
- `Habitacion::estaDisponible`
- `Habitacion::getProximaSalida`
- `HabitacionController::indexAction`
- `HabitacionController::mostrarDisponibilidadPorFecha`
- `HabitacionController::verAction`
- `HabitacionController::historial`
- Validacion de conflictos en `HabitacionController::programarMantenimientoAction`

## Commits Relacionados

| Fase | Commit / mensaje | Alcance |
| --- | --- | --- |
| Fase 2A.1 preparacion | `2e892dba1d24e53f73ac916a2190351c0dbb3052` - `chore: prepare habitaciones hotel_id migration` | Migracion SQL preparada para `hotel_id` en Habitaciones. |
| Fase 2A.1.1 | `5300f001c2a3dbcbbba9f3f798fc2ef1aa54dec5` - `chore: update saas tools for habitaciones hotel_id` | Herramientas SaaS actualizadas al estado post-migracion. |
| Fase 2A.2-B | `fe8f6ce5a5688472e6cf8bfbe124ea9233332ea1` - `feat: scope habitaciones module to current hotel` | Codigo puro de Habitaciones, imagenes y mantenimientos escribe y respeta `hotel_id`. |
| Fase 2A.2-FIX-B prep | `cf92cde706ce2921d4da36660428e47a49375a82` - `fix: prepare scheduled maintenance status migration` | Migracion preparada para permitir estado `programado`. |
| Fase 2A.2-FIX-B docs | `e068a5ffae396a9b3b70865b2cb7ec0558a8d8d2` - `docs: record scheduled maintenance status fix execution` | Ejecucion local del fix de estado `programado` documentada. |
| Fase 2A.2-C-B | `fix: scope habitaciones cross reads by hotel` | Lecturas cruzadas de Habitaciones endurecidas con `habitaciones.hotel_id`. Verificar hash local al cerrar commit si el workspace aun muestra cambios pendientes. |

## Que NO Se Toco

- No se migro `reservaciones`.
- No se agrego `hotel_id` a `reservaciones`.
- No se tocaron `reservacion_habitaciones`, `reservacion_pagos` ni `reservacion_abonos`.
- No se tocaron caja, cortes ni movimientos de caja.
- No se toco PWA/offline ni service worker.
- No se toco Dashboard.
- No se tocaron reportes.
- No se tocaron login ni sesiones.
- No se tocaron APIs globales.
- No se modificaron indices unicos globales.
- No se convirtio `hotel_id` a `NOT NULL`.

## Riesgos Pendientes

- Reservaciones sigue sin `hotel_id`, por lo que el aislamiento completo de ocupacion y disponibilidad todavia depende de filtrar por `habitaciones.hotel_id`.
- APIs/PWA siguen globales y deben auditarse antes de cualquier cambio.
- Dashboard y reportes siguen globales.
- `habitaciones.numero` sigue siendo unico global.
- `tipos_habitacion.codigo` sigue siendo unico global.
- `hotel_id` sigue siendo `NULL` temporalmente para no romper inserciones mientras no se complete la integracion por modulos.
- Hay vistas con SQL directo que deben tratarse en fases futuras con cambios pequenos y revisados.
- Reservaciones, check-in/check-out y caja siguen siendo zonas de alto riesgo y requieren fases separadas.

## Validaciones Actuales

Estado validado al cierre tecnico del modulo Habitaciones:

- `tools/saas/verificar_estado.php`: PASS.
- `tools/saas/preflight_hotel_id.php`: PASS.
- `GET /login`: 200.
- `GET /habitaciones` sin sesion: redireccion esperada a login.
- `hotel_id NULL`: 0 registros en tablas de Habitaciones:
  - `tipos_habitacion`
  - `habitaciones`
  - `habitacion_imagenes`
  - `mantenimientos_habitaciones`

## Recomendacion De Siguiente Fase

### Opcion A: Fase 2A.3 - Auditoria de indices unicos de Habitaciones

Esta fase revisaria `habitaciones.numero`, `tipos_habitacion.codigo` y otros unicos globales para decidir como migrarlos a unicos compuestos por hotel, por ejemplo `(hotel_id, numero)` o `(hotel_id, codigo)`.

Ventajas:

- Reduce una deuda propia del modulo Habitaciones.
- Evita choques futuros cuando existan varios hoteles con numeraciones o codigos similares.
- Mantiene el foco en Habitaciones sin entrar todavia a Reservaciones.

Riesgos:

- Cambiar indices unicos puede afectar validaciones existentes.
- Requiere revisar duplicados potenciales antes de modificar constraints.
- Conviene hacerlo cuando el codigo ya escribe `hotel_id`, que ahora se cumple.

### Opcion B: Fase 2B - Schema/backfill Reservaciones

Esta fase agregaria `hotel_id` de forma controlada a Reservaciones y tablas relacionadas, con backfill hacia Los Cedros.

Ventajas:

- Permite avanzar hacia aislamiento real de ocupacion, disponibilidad, check-in/check-out y flujo operativo.
- Desbloquea futuras correcciones en APIs, dashboard y reportes.

Riesgos:

- Es una fase de mayor impacto porque toca el corazon operativo del sistema.
- Puede afectar check-in/check-out, pagos, historial, ocupacion y reportes.
- Requiere backup fresco, migracion cuidadosamente validada y pruebas mas amplias.

### Recomendacion

Conviene hacer primero la **Fase 2A.3 - Auditoria de indices unicos de Habitaciones**.

Motivo: Habitaciones ya tiene schema, backfill y codigo funcional scoped por hotel. Antes de abrir Reservaciones, es mas seguro cerrar la deuda local del modulo y preparar una propuesta exacta para los unicos globales sin cambiar nada todavia. Despues de esa auditoria, Fase 2B Reservaciones tendra un punto de partida mas limpio y menos deuda cruzada.

## Rollback Documental

Este documento no modifica base de datos ni codigo funcional. Su rollback consiste en borrar:

```text
docs/fase_2A_2_C_cierre_habitaciones.md
```
