# Fase Reservaciones 1-E-A - Auditoria de check-in/check-out

## Objetivo

Auditar el flujo de check-in y check-out para preparar como hacerlo tenant-aware sin romper habitaciones, inventario, caja, pagos, limpieza, Sync ni PWA/offline.

Esta fase fue solo lectura:

- No se modificaron archivos.
- No se ejecuto SQL.
- No se toco base de datos.
- No se hicieron migraciones.
- No se hizo ALTER TABLE.
- No se implemento nada.

## Hallazgo principal

Check-in/check-out todavia tiene puntos globales por `id` o `reservacion_id`.

Las piezas clave ya existen para preparar el cambio:

- `reservaciones.hotel_id`.
- `reservacion_habitaciones.hotel_id`.
- `habitaciones.hotel_id`.
- `InventarioService` scoped por hotel.
- `movimientos_inventario.hotel_id`.

El mayor riesgo esta en el flujo combinado:

- Reservaciones.
- Habitaciones.
- Caja/Pagos.
- PWA/Sync.

Por eso no conviene hacer una implementacion amplia directa.

## Flujo actual de check-in

Ruta principal:

- `/reservaciones/check-in/{id}`

Flujo:

1. `ReservacionController::checkInAction()`.
2. `Reservacion::checkInConPagosMixtos()`.
3. `ReservacionController::procesarDescuentoInventario()`.
4. Procesamiento de llaves/remotos.
5. Procesamiento de factura.

Tambien existen caminos relacionados:

- `Reservacion::checkIn()` como fallback simple.
- `ReservacionController::checkInTardioAction()`.
- `ReservacionController::procesarCheckInTardioAction()`.
- `Reservacion::checkInTardio()`.
- `Reservacion::checkInCheckOutExpress()`.
- PWA/Sync con operaciones offline de check-in.

## Puntos criticos de check-in

### `ReservacionController::checkInAction()`

Archivo:

- `src/app/controllers/ReservacionController.php`

Hallazgos:

- Usa `find($id)` global para cargar reservacion.
- Exige caja abierta antes del check-in.
- Procesa pagos desde POST.
- Llama a `Reservacion::checkInConPagosMixtos()`.
- Despues del check-in llama a `procesarDescuentoInventario()`.
- Procesa entrega automatica de llaves.
- Procesa solicitud de factura.

Riesgo:

- Alto.

Motivo:

- El `id` de reservacion se usa como clave global.
- El flujo cruza Reservaciones, Caja, Inventario, llaves/remotos y Facturacion.

### `Reservacion::checkInConPagosMixtos()`

Archivo:

- `src/app/models/Reservacion.php`

Hallazgos:

- Actualiza `reservaciones` a `checked_in`.
- Actualiza `habitaciones` a `ocupada`.
- Registra ingresos en `movimientos_caja`.
- Intenta registrar pagos en `reservacion_pagos`.
- Usa caja/corte abierto.
- No aplica scope completo por `hotel_id` en todas las consultas.

Riesgo:

- Alto.

Motivo:

- Puede cambiar estado de una reservacion o habitacion de otro hotel si se invoca con un `id` global incorrecto.
- Caja y pagos todavia no son tenant-aware.

### `ReservacionController::procesarDescuentoInventario()`

Archivo:

- `src/app/controllers/ReservacionController.php`

Hallazgos:

- Consulta configuraciones activas de `inventario_config_habitacion` sin `hotel_id`.
- Lee habitaciones de la reservacion desde `reservacion_habitaciones` + `habitaciones` usando solo `reservacion_id`.
- Para cada habitacion llama a:
  - `InventarioService::verificarDisponibilidad($habitacion_id)`.
  - `InventarioService::descontarInventarioCheckIn($habitacion_id, $reservacion_id)`.

Riesgo:

- Medio.

Motivo:

- Las preconsultas siguen globales, aunque `InventarioService` ya valida hotel y escribe movimientos con `hotel_id`.

### `InventarioService::descontarInventarioCheckIn()`

Archivo:

- `src/app/services/InventarioService.php`

Hallazgos:

- Ya obtiene hotel actual.
- Valida `habitaciones.id` con `hotel_id`.
- Lee `inventario_config_habitacion` por `hotel_id`.
- Une `inventario_productos` por `hotel_id`.
- Actualiza stock con `WHERE id = ? AND hotel_id = ?`.
- Inserta `movimientos_inventario.hotel_id`.

Riesgo:

- Bajo en inventario.
- Medio en el flujo completo porque el llamador todavia no esta completamente scoped.

## Flujo actual de check-out

Ruta principal:

- `/reservaciones/check-out/{id}`

Flujo:

1. `ReservacionController::checkOutAction()`.
2. `Reservacion::checkOut()`.
3. Procesamiento de recogida de llaves.
4. Procesamiento de recogida de remotos.

Tambien existen caminos relacionados:

- `ReservacionController::checkOutRapidoAction()`.
- `ReservacionController::checkOutParcialAction()`.
- `Reservacion::checkOutParcial()`.
- `Reservacion::checkInCheckOutExpress()`.
- PWA/Sync con operaciones offline de check-out.

## Puntos criticos de check-out

### `Reservacion::checkOut()`

Archivo:

- `src/app/models/Reservacion.php`

Hallazgos:

- Actualiza `reservaciones` a `checked_out` usando `id` y `estado`.
- Cambia habitaciones a `limpieza`.
- Usa `reservacion_id` para ubicar habitaciones.
- Evita liberar habitaciones ocupadas por otra reservacion activa, pero sin scope completo por hotel.

Consultas a corregir en futura fase:

- `UPDATE reservaciones ... WHERE id = ? AND estado = 'checked_in'`
- `UPDATE habitaciones h INNER JOIN reservacion_habitaciones rh ... WHERE rh.reservacion_id = ?`
- Subconsulta contra `reservacion_habitaciones` + `reservaciones` para otra reservacion activa.

Necesitan:

- `reservaciones.hotel_id = ?`.
- `reservacion_habitaciones.hotel_id = ?`.
- `habitaciones.hotel_id = ?`.
- Validacion de `r2.hotel_id = ?`.

Riesgo:

- Alto.

Motivo:

- Puede mandar habitaciones a limpieza sin validar tenant.

### `Reservacion::checkOutParcial()`

Archivo:

- `src/app/models/Reservacion.php`

Hallazgos:

- Valida que la reservacion exista con `find($reservacion_id)`.
- Valida estado `checked_in`.
- Lee habitaciones por `reservacion_id`.
- Valida que los IDs seleccionados pertenezcan a la reservacion.
- Cambia habitaciones seleccionadas a `limpieza`.
- Si no quedan habitaciones ocupadas, marca reservacion como `checked_out`.
- Si quedan habitaciones ocupadas, agrega nota y mantiene la reservacion activa.

Riesgo:

- Alto.

Motivo:

- La validacion de pertenencia no valida `hotel_id`.
- La actualizacion de habitaciones y reservacion usa IDs globales.

### `ReservacionController::checkOutRapidoAction()`

Archivo:

- `src/app/controllers/ReservacionController.php`

Hallazgos:

- Usa `find($id)` global.
- Verifica estado `checked_in`.
- Consulta habitaciones para respuesta con `reservacion_id`.
- Llama a `Reservacion::checkOut()`.
- Procesa llaves/remotos.

Riesgo:

- Alto.

Motivo:

- Es camino alterno de check-out y puede saltarse validaciones tenant si solo se corrige el flujo principal.

## Tablas tocadas por los flujos

Los flujos de check-in/check-out pueden tocar o leer:

- `reservaciones`.
- `reservacion_habitaciones`.
- `habitaciones`.
- `movimientos_caja`.
- `reservacion_pagos`.
- `solicitudes_factura`.
- `movimientos_inventario`.
- Tablas de control de llaves.
- Tablas de control de remotos.

## Riesgos principales

| Area | Riesgo | Nivel |
| --- | --- | --- |
| Reservaciones | Cambiar estado de reservacion de otro hotel por ID global | alto |
| Habitaciones | Cambiar habitacion de otro hotel a ocupada, limpieza o disponible | alto |
| Caja | Caja aun no es tenant-aware | alto |
| Pagos | `reservacion_pagos` puede recibir inserts sin `hotel_id` en algunos caminos | alto |
| Check-out | Puede mandar habitaciones a limpieza sin validar hotel | alto |
| PWA/Sync | Tiene caminos paralelos globales para check-in/check-out | alto |
| APIs globales | Exponen/consumen reservaciones y habitaciones sin scope completo | alto |
| Llaves/remotos | Operan por `habitacion_id`/`reservacion_id` sin tenant | medio |
| Inventario | Servicio principal ya esta scoped, pero preconsultas del controlador siguen globales | medio |

## Que esta listo por fases anteriores

- Reservaciones base ya tiene `hotel_id`.
- `reservacion_habitaciones` ya tiene `hotel_id`.
- `reservacion_pagos`, `reservacion_abonos`, `reservacion_notas` y `solicitudes_factura` ya tienen `hotel_id`.
- Habitaciones ya tiene `hotel_id`.
- Inventario base ya respeta `hotel_id`.
- `movimientos_inventario` ya tiene `hotel_id`.
- `InventarioService` ya esta scoped por hotel.
- Cancelaciones de inventario usan `movimientos_inventario`.

## Que NO esta listo

- Caja/pagos.
- Sync/PWA/offline.
- APIs globales.
- Huespedes como tenant.
- `movimientos_caja`.
- `cortes_caja`.
- `cajas`.
- Llaves/remotos.
- Dashboard/reportes.

## Recomendacion

No hacer una implementacion amplia directa.

La siguiente fase debe ser una propuesta tecnica detallada o una implementacion muy limitada al scope Reservaciones/Habitaciones.

La implementacion futura deberia:

- Validar reservacion con `id + hotel_id`.
- Validar `reservacion_habitaciones.hotel_id`.
- Validar `habitaciones.hotel_id`.
- Scopear updates de `reservaciones` por `hotel_id`.
- Scopear updates de `habitaciones` por `hotel_id`.
- Mantener `InventarioService` como esta, pero scopear las preconsultas del controlador.
- Evitar tocar Caja/Pagos salvo aprobacion explicita.
- Evitar tocar PWA/Sync/API en la misma fase.

## Que NO se debe tocar todavia

- Caja funcional.
- Pagos/cortes.
- PWA/offline.
- Sync.
- APIs globales.
- Dashboard/reportes.
- Migraciones/schema.
- Huespedes como modelo tenant.
- Llaves/remotos como correccion funcional.

## Siguiente fase recomendada

Reservaciones 1-E-B-PREP: propuesta tecnica detallada para scopear check-in/check-out sin tocar Caja/Pagos/PWA/Sync todavia.

La propuesta debe separar:

- Scope minimo de Reservaciones/Habitaciones.
- Deuda de Caja/Pagos.
- Deuda de PWA/Sync/API.
- Deuda de llaves/remotos.

Esto reduce el riesgo de mezclar tenant-scope con cambios contables u offline en una sola fase.
