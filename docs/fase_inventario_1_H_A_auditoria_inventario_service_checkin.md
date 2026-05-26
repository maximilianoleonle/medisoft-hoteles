# Fase Inventario 1-H-A - Auditoria de InventarioService en check-in

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Auditar el flujo de descuento automatico de inventario durante check-in para planear como escribir `hotel_id` correctamente en movimientos nuevos.

La auditoria fue solo lectura:

- No se modificaron archivos.
- No se ejecuto SQL.
- No se toco base de datos.
- No se hicieron migraciones.
- No se hizo `ALTER TABLE`.
- No se implemento nada.

## Flujo actual

- `ReservacionController::checkInAction()` llama a `procesarDescuentoInventario($id)` despues de check-in exitoso.
- El flujo express tambien invoca ese descuento.
- `procesarDescuentoInventario()` carga `InventarioService`.
- Por cada habitacion llama:
  - `InventarioService::verificarDisponibilidad($habitacion_id)`
  - `InventarioService::descontarInventarioCheckIn($habitacion_id, $reservacion_id)`

## Consultas actuales sin scope

- `SELECT tipo FROM habitaciones WHERE id = ?`
- Configuracion con `inventario_config_habitacion` + `inventario_productos` usando solo `tipo_habitacion`.
- `UPDATE inventario_productos SET stock_actual = ? WHERE id = ?`
- `INSERT` en `movimientos_inventario` sin `hotel_id`.

## Que ya puede usar hotel_id

- `habitaciones.hotel_id`
- `inventario_config_habitacion.hotel_id`
- `inventario_productos.hotel_id`
- `movimientos_inventario.hotel_id`

## Propuesta tecnica para Inventario 1-H-B

- Tocar solo `src/app/services/InventarioService.php`.
- Cargar `hotel_config.php`.
- Usar `obtenerHotelIdActualCompat()`.
- En habitacion usar `WHERE id = ? AND hotel_id = ?`.
- En configuracion usar `ich.hotel_id = ?`.
- En producto usar `ip.id = ich.producto_id AND ip.hotel_id = ich.hotel_id`.
- Actualizar stock con `WHERE id = ? AND hotel_id = ?`.
- Insertar `movimientos_inventario.hotel_id`.
- No tocar `ReservacionController`.
- No tocar Caja.
- No tocar PWA/offline.
- No cambiar schema.

## Riesgos

- Es check-in real, un error afecta stock operativo.
- Reservaciones aun no tiene `hotel_id`.
- `tipo_habitacion` sigue siendo textual.
- Si el descuento falla parcialmente puede dejar stock/movimientos inconsistentes.
- El flujo express tambien llama al descuento.
- Cancelaciones ya fueron corregidas, pero dependen de movimientos con `hotel_id` correcto.

## Pruebas necesarias para 1-H-B

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php`: `PASS`.
- `preflight_hotel_id.php`: `PASS`.
- Prueba transaccional de check-in con descuento.
- Confirmar `movimientos_inventario.hotel_id`.
- Confirmar stock correcto.
- Confirmar rollback sin datos persistidos.
- Confirmar que cancelacion sigue devolviendo correctamente.
- Confirmar que Caja/PWA no se tocaron.

## Que NO se debe tocar todavia

- Schema de Reservaciones.
- `ReservacionController`, salvo aprobacion explicita.
- Caja.
- PWA/offline.
- Migraciones.
- `inventario_movimientos`.
- Dashboard/reportes.
