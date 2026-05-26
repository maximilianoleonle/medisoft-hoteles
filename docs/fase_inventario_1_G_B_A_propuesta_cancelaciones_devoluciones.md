# Fase Inventario 1-G-B-A - Propuesta de cancelaciones y devoluciones

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Disenar como corregir cancelaciones/devoluciones de inventario para que usen la tabla activa `movimientos_inventario`.

La auditoria y propuesta fueron solo lectura:

- No se modificaron archivos funcionales.
- No se ejecuto SQL.
- No se hicieron migraciones.
- No se hizo `ALTER TABLE`.
- No se hicieron `INSERT`, `UPDATE` ni `DELETE`.
- No se implemento nada.

## Flujo actual de check-in

- `ReservacionController` carga `InventarioService`.
- `InventarioService::descontarInventarioCheckIn()` descuenta stock.
- El descuento registra un movimiento `SALIDA` en `movimientos_inventario`.
- El registro guarda:
  - `producto_id`
  - `cantidad`
  - `stock_anterior`
  - `stock_posterior`
  - `motivo`
  - `habitacion_id`
  - `reservacion_id`
  - `usuario_id`
  - `created_at`
- Riesgo actual: `InventarioService` todavia no escribe `hotel_id` desde codigo funcional.

## Flujo actual de cancelacion

- `Reservacion::cancelar()` llama a `devolverInventarioCancelacion()` si la reservacion estaba `checked_in`.
- `devolverInventarioCancelacion()` busca movimientos `SALIDA` en `inventario_movimientos`.
- Luego intenta insertar la devolucion `ENTRADA` en `inventario_movimientos`.
- Problema central: check-in escribe en `movimientos_inventario`, pero cancelacion lee e inserta en `inventario_movimientos`.

Esto deja una inconsistencia operativa: la salida real queda registrada en la tabla activa, pero la devolucion se calcula contra una tabla vacia/paralela.

## Propuesta tecnica

- Usar `movimientos_inventario` como fuente de verdad.
- Buscar movimientos `SALIDA` por `reservacion_id`.
- Validar `m.hotel_id` contra el hotel actual.
- Agrupar por `producto_id` para calcular cantidades netas a devolver.
- Evitar doble devolucion buscando `ENTRADA` previa con motivo/reservacion equivalente.
- Validar `inventario_productos.hotel_id = movimientos_inventario.hotel_id`.
- Actualizar stock del producto con scope por hotel.
- Registrar la devolucion `ENTRADA` en `movimientos_inventario`.
- No tocar `inventario_movimientos`, salvo documentar que queda como tabla legacy/paralela hasta una fase de retiro formal.

## Validaciones necesarias

- El producto debe pertenecer al hotel actual.
- El movimiento `SALIDA` debe pertenecer al hotel actual.
- El `reservacion_id` del movimiento debe coincidir con la reservacion cancelada.
- La cantidad a devolver debe ser mayor que 0.
- No debe existir devolucion previa para esa reservacion/producto.
- Si el movimiento tiene `habitacion_id`, la habitacion debe pertenecer al mismo hotel.
- El motivo de la devolucion debe mantener trazabilidad clara:
  - `Devolucion por cancelacion - Reservacion #ID`

## Riesgos

- Devolver stock dos veces si no se detecta una devolucion previa.
- No devolver stock si movimientos historicos no tienen `hotel_id`.
- Reservaciones todavia no tiene `hotel_id`.
- Tocar cancelaciones afecta operacion real.
- `InventarioService` sigue como deuda tecnica porque check-in aun debe escribir `hotel_id` desde codigo funcional.
- Puede haber movimientos parciales o historicos especiales que no sigan el flujo automatico esperado.

## Archivos candidatos para implementacion

| Archivo | Uso propuesto |
| --- | --- |
| `src/app/models/Reservacion.php` | Corregir `devolverInventarioCancelacion()` para leer y registrar devoluciones en `movimientos_inventario`. |
| `src/app/models/MovimientoInventario.php` | Opcional: crear metodo/helper de devolucion si reduce riesgo y centraliza validaciones. |

## Archivos que NO se deben tocar todavia

- `src/app/services/InventarioService.php`
- `src/app/controllers/ReservacionController.php`, salvo que sea estrictamente necesario y con aprobacion previa.
- Caja.
- PWA/offline.
- Check-in/check-out general.
- Schema de `inventario_movimientos`.
- Migraciones.
- Base de datos.

## Plan de pruebas para Inventario 1-G-B-B

- Ejecutar `php -l` en los archivos tocados.
- Ejecutar `git diff --check`.
- Ejecutar `docker compose exec app php tools/saas/verificar_estado.php` y confirmar `PASS`.
- Ejecutar `docker compose exec app php tools/saas/preflight_hotel_id.php` y confirmar `PASS`.
- Hacer prueba transaccional de cancelacion de reservacion `checked_in` con movimientos `SALIDA`.
- Confirmar que el stock se restaura.
- Confirmar que se registra una sola `ENTRADA` de devolucion.
- Reintentar devolucion y confirmar que no duplica stock ni movimientos.
- Confirmar que Caja no se altera fuera del flujo existente.
- Confirmar que no se toca `InventarioService`.
- Confirmar que no se toca PWA/offline.

## Recomendacion

- Si conviene preparar Inventario 1-G-B-B, pero con alcance limitado.
- Implementar preferentemente en `Reservacion::devolverInventarioCancelacion()`.
- Crear helper/metodo en `MovimientoInventario` solo si reduce riesgo y evita duplicacion de validaciones.
- No tocar check-in/check-out todavia.
- No eliminar `inventario_movimientos` todavia.

## Decision de alcance

- `movimientos_inventario` debe quedar como fuente de verdad para devoluciones.
- `inventario_movimientos` debe permanecer sin cambios hasta una fase posterior de retiro o compatibilidad.
- La correccion debe ser transaccional y defensiva para evitar doble devolucion.
- La integracion con Reservaciones tenant-aware debe esperar a una fase propia.
