# Fase Huespedes Vehiculos - Placas Unicas Por Hotel

Fecha: 2026-06-24

## Objetivo

Resolver el riesgo de unicidad global en `huesped_vehiculos.placas`, alineando la base de datos con la validacion funcional que ya revisaba placas por hotel.

## Alcance

- `huesped_vehiculos`
- `src/app/models/HuespedVehiculo.php`
- `src/app/models/Huesped.php`
- `src/app/controllers/HuespedController.php`

No se tocaron Reservaciones, Caja, PWA/offline, `/api/sync`, permisos, auth ni logica de check-in/check-out.

## Migracion Aplicada

```text
migrations/20260624_004_huesped_vehiculos_placas_unicas_por_hotel.sql
migrations/20260624_007_archivar_huesped_vehiculos_huerfanos.sql
```

## Cambio De Schema

- Se agrego `huesped_vehiculos.hotel_id INT NULL`.
- Se hizo backfill de `hotel_id` desde `huespedes.hotel_id` para vehiculos con huesped valido.
- Se agrego `idx_huesped_vehiculos_hotel_id (hotel_id)`.
- Se agrego `fk_huesped_vehiculos_hotel`.
- Se agrego `uk_huesped_vehiculos_hotel_placas ((COALESCE(hotel_id, 0)), placas)`.
- Se elimino el indice unico global `placas_unique (placas)`.

El indice funcional usa `COALESCE(hotel_id, 0)` para mantener controlados los registros sin hotel y permitir placas repetidas entre hoteles reales.

## Cambio Funcional

- `HuespedVehiculo` ahora permite y deriva `hotel_id` al crear vehiculos.
- Las consultas principales por hotel validan `v.hotel_id` junto con `huespedes.hotel_id`.
- `HuespedController` escribe `hotel_id` al agregar, editar y crear vehiculos junto con un nuevo huesped.
- `Huesped::contarVehiculosPorHotel()` usa el mismo criterio de hotel.

## Validacion Local Ejecutada

- `huesped_vehiculos`: 863 registros.
- `hotel_id` backfilled: 850 registros.
- `hotel_id NULL`: 13 registros.
- Los 13 registros con `hotel_id NULL` corresponden a vehiculos cuyo `huesped_id` ya no existe en `huespedes`.
- Sin duplicados por scope `COALESCE(hotel_id, 0), placas`.
- Prueba transaccional exitosa permitiendo la placa `123526` en hotel 1 y hotel 4.
- Prueba transaccional exitosa bloqueando duplicado dentro del hotel 4.

## Actualizacion 2026-06-24 - Lecturas Legacy Por Hotel

Despues de agregar `hotel_id` a `huesped_vehiculos`, se reforzaron lecturas antiguas que aun consultaban vehiculos solo por `huesped_id`.

Archivos ajustados:

- `src/app/models/HuespedVehiculo.php`
- `src/app/models/Huesped.php`
- `src/app/controllers/ReservacionController.php`
- `src/app/views/dashboard/index.php`

Criterio aplicado:

- Los joins entre `huesped_vehiculos` y `huespedes` ahora validan `v.hotel_id = h.hotel_id`.
- Las busquedas y conteos legacy usan el hotel actual cuando existe contexto de hotel.
- Las lecturas directas de dashboard y reservaciones filtran por `hotel_id` y `activo = 1`.

Validacion adicional:

- `php -l` correcto para los cuatro PHP modificados.
- Prueba transaccional con un vehiculo inconsistente:
  - La consulta antigua lo habria incluido en el hotel del huesped.
  - La consulta reforzada lo excluye tanto del hotel del huesped como del hotel incorrecto del vehiculo.

## Actualizacion 2026-06-24 - Saneamiento De Huerfanos

Se aplico `migrations/20260624_007_archivar_huesped_vehiculos_huerfanos.sql`.

La migracion:

- Crea `huesped_vehiculos_orfanos_archivo`.
- Copia los vehiculos cuyo `huesped_id` ya no existe en `huespedes`.
- Conserva `vehiculo_id`, datos del vehiculo, estado original, timestamps, nombre de migracion y motivo.
- Elimina esos registros de `huesped_vehiculos` solo despues de confirmar que fueron archivados.
- Registra la migracion en `migrations`.

Resultado validado:

- `huesped_vehiculos`: 850 registros activos validos.
- Vehiculos sin `hotel_id`: 0.
- Vehiculos sin huesped existente: 0.
- Vehiculos con cruce de hotel contra su huesped: 0.
- Vehiculos archivados por la migracion: 13.
- De los archivados, 12 estaban activos antes del saneamiento.
- Migracion registrada como batch 34.

## Pendiente

- `hotel_id` queda nullable por compatibilidad historica. Con la tabla activa saneada, podria evaluarse volverlo `NOT NULL` en una fase posterior si se confirma que no queda ningun flujo legacy que inserte vehiculos sin hotel.
