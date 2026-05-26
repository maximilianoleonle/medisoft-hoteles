# Fase Reservaciones 1-D-A - Auditoria de codigo base

## Objetivo

Auditar que partes del codigo base de Reservaciones deben escribir y filtrar por `hotel_id`, antes de implementar cambios funcionales sobre creacion, edicion basica, listados, detalle, asignacion de habitaciones, disponibilidad base y notas.

Esta auditoria fue solo lectura:

- No modifico codigo funcional.
- No ejecuto SQL.
- No toco base de datos.
- No hizo migraciones.
- No hizo `ALTER TABLE`.
- No implemento cambios.

## Conclusion principal

Las tablas base de Reservaciones ya tienen `hotel_id`, pero el codigo funcional todavia opera mayormente por IDs globales.

La siguiente implementacion debe limitarse a:

- creacion de reservaciones;
- edicion basica;
- listado;
- detalle;
- asignacion de habitaciones;
- disponibilidad base;
- notas.

Deben quedar fuera:

- check-in;
- check-out;
- cancelaciones;
- caja;
- pagos;
- PWA/offline;
- APIs globales;
- Sync;
- Dashboard/reportes.

## Archivos criticos

Archivos en alcance directo:

- `src/app/models/Reservacion.php`
- `src/app/controllers/ReservacionController.php`
- `src/app/models/ReservacionNota.php`
- `src/config/routes.php`
- vistas de reservaciones
- JS de reservaciones

Vistas principales revisadas:

- `src/app/views/reservaciones/index.php`
- `src/app/views/reservaciones/crear.php`
- `src/app/views/reservaciones/ver.php`
- `src/app/views/reservaciones/editar-habitaciones.php`
- `src/app/views/reservaciones/calendario.php`

Archivos relacionados pero fuera de alcance para esta fase:

- `src/app/controllers/ApiController.php`
- `src/api/reservaciones/hoy.php`
- `src/api/sync.php`
- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`
- vistas de Caja, Dashboard, Facturacion, Huespedes y APIs/PWA.

## Metodos principales

Creacion:

- `ReservacionController::guardarAction()`
- `Reservacion::crearConHabitaciones()`

Edicion basica y asignacion de habitaciones:

- `ReservacionController::editarHabitacionesAction()`
- `ReservacionController::actualizarHabitacionesAction()`
- `Reservacion::actualizarHabitaciones()`

Listado, busqueda y calendario:

- `ReservacionController::indexAction()`
- `Reservacion::obtenerHabitacionesReservadasPorFecha()`
- `Reservacion::buscarConDetalles()`
- `Reservacion::paraCalendario()`

Detalle:

- `ReservacionController::verAction()`
- `Reservacion::obtenerPorId()`
- `Reservacion::getHabitaciones()`

Notas:

- `ReservacionController::agregarNotaAction()`
- `ReservacionController::obtenerNotasAction()`
- `ReservacionNota::agregarNota()`
- `ReservacionNota::obtenerPorReservacion()`
- `ReservacionNota::contarNotas()`

Disponibilidad base:

- `Reservacion::verificarDisponibilidadMultiple()`
- `Reservacion::verificarDisponibilidadMultipleExcluyendo()`

## Consultas a scopear

Las consultas sobre `reservaciones` deben agregar:

```sql
r.hotel_id = ?
```

Las consultas sobre `reservacion_habitaciones` deben agregar consistencia tenant:

```sql
rh.hotel_id = r.hotel_id
```

o, cuando no haya alias de `reservaciones`:

```sql
rh.hotel_id = ?
```

Las consultas que cruzan con `habitaciones` deben validar:

```sql
h.hotel_id = rh.hotel_id
```

o:

```sql
h.hotel_id = r.hotel_id
```

Los inserts en `reservaciones` deben incluir:

```sql
hotel_id
```

Los inserts en `reservacion_habitaciones` deben incluir:

```sql
hotel_id
```

Los inserts en `reservacion_notas` deben incluir:

```sql
hotel_id
```

Los updates de `reservaciones` deben usar:

```sql
WHERE id = ? AND hotel_id = ?
```

Los reemplazos de habitaciones en `reservacion_habitaciones` deben usar:

```sql
WHERE reservacion_id = ? AND hotel_id = ?
```

## Consultas cruzadas

Consultas que cruzan con `habitaciones` y ya pueden validarse por hotel:

- listados de reservaciones;
- detalle de reservacion;
- disponibilidad multiple;
- disponibilidad multiple excluyendo una reservacion;
- asignacion y reemplazo de habitaciones;
- calendario.

Consultas que cruzan con pagos/caja y deben esperar:

- pagos de reservacion;
- cambio de metodo de pago;
- check-in con pagos;
- check-out;
- cortes de caja;
- movimientos de caja;
- facturacion funcional.

Consultas que cruzan con PWA/Sync/API y deben esperar:

- endpoints globales de reservaciones;
- sincronizacion offline;
- APIs usadas por PWA;
- cambios de estado desde flujos externos.

## Que debe esperar

Queda fuera de Reservaciones 1-D-B:

- check-in;
- check-out;
- cancelaciones;
- pagos/caja;
- cortes;
- facturacion funcional;
- Sync/PWA/offline;
- APIs globales;
- Dashboard/reportes;
- huespedes, hasta decidir si el modelo sera global o por hotel.

## Propuesta para Reservaciones 1-D-B

Tocar solo:

- `src/app/models/Reservacion.php`
- `src/app/controllers/ReservacionController.php`
- `src/app/models/ReservacionNota.php`

Cambios propuestos:

- usar `obtenerHotelIdActualCompat()`;
- no depender todavia de login/sesiones;
- no integrar `TenantContext` global;
- agregar `hotel_id` a `$fillable` donde aplique;
- crear helper interno para obtener hotel actual si reduce duplicacion;
- crear reservaciones con `hotel_id`;
- crear `reservacion_habitaciones` con `hotel_id`;
- crear `reservacion_notas` con `hotel_id`;
- scopear listados, detalle, calendario y disponibilidad por `hotel_id`;
- validar habitaciones seleccionadas contra el hotel actual;
- mantener huespedes fuera de esta fase.

## Estrategia tecnica

La estrategia recomendada es usar `obtenerHotelIdActualCompat()` como fuente temporal del hotel actual.

No debe dependerse todavia de login/sesiones, porque la fase busca mantener compatibilidad con el estado actual mono-hotel mientras se avanza hacia SaaS.

No se recomienda introducir un `TenantContext` global en esta fase, para evitar ampliar el alcance y tocar flujos sensibles.

Cada escritura nueva de Reservaciones base debe recibir `hotel_id`, y cada lectura base debe filtrar por `hotel_id`.

En asignacion de habitaciones, todas las habitaciones seleccionadas deben validarse contra el hotel actual antes de crear o reemplazar registros en `reservacion_habitaciones`.

## Riesgos

- Reservaciones multi-habitacion: se debe validar que todas las habitaciones pertenezcan al mismo hotel.
- Reservaciones historicas: ya tienen `hotel_id`, pero el codigo todavia no lo respeta.
- Pagos/caja: dependen de `reservacion_id` y deben tratarse en fase propia.
- Check-in/check-out: no deben tocarse en Reservaciones 1-D-B.
- PWA/offline y Sync pueden crear o modificar reservaciones sin tenant si se tocan antes de tiempo.
- Huespedes siguen como decision pendiente: globales vs por hotel.
- El detalle de reservacion puede leer pagos; si se ajusta en una fase futura, debe ser solo lectura y sin tocar Caja.

## Pruebas propuestas

Para Reservaciones 1-D-B:

- `php -l` en archivos tocados;
- `git diff --check`;
- `docker compose exec app php tools/saas/verificar_estado.php`;
- `docker compose exec app php tools/saas/preflight_hotel_id.php`;
- `GET /reservaciones`;
- crear reservacion y confirmar `reservaciones.hotel_id`;
- confirmar `reservacion_habitaciones.hotel_id`;
- editar habitaciones de reservacion basica;
- ver detalle;
- agregar/listar notas;
- confirmar 0 nuevos `hotel_id NULL`;
- confirmar que no se toco check-in/check-out;
- confirmar que no se toco Caja;
- confirmar que no se toco PWA/offline.

## Decision

Reservaciones 1-D-B puede prepararse como una implementacion controlada y acotada al codigo base de Reservaciones.

No debe incluir check-in, check-out, cancelaciones, pagos, Caja, PWA/offline, Sync, APIs globales, Dashboard/reportes ni huespedes.
