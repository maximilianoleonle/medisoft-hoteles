# Fase Inventario 1-F-C-A - Auditoria de lecturas de movimientos

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Auditar lecturas, historiales y consultas de `movimientos_inventario` antes de scopearlas por `hotel_id`.

La auditoria fue solo lectura:

- No se modificaron archivos.
- No se ejecuto SQL.
- No se toco base de datos.
- No se hicieron migraciones.
- No se implemento nada.

## Conclusion principal

- Las lecturas principales estan concentradas en `MovimientoInventario.php`.
- `InventarioController.php` queda cubierto parcialmente si el modelo se corrige.
- PDF/debugs deben esperar.
- `InventarioService` debe esperar porque esta ligado a check-in/check-out.
- Reservaciones y cancelaciones deben esperar.

## Consultas candidatas para Inventario 1-F-C-B

Las consultas con mejor perfil para implementar primero son:

- `MovimientoInventario::getMovimientosDetallados()`.
- `MovimientoInventario::getMovimientosPorProducto()`.
- `MovimientoInventario::getMovimientosRecientes()`.

Estas consultas alimentan el dashboard/listado interno de Inventario y el historial por producto sin tocar `InventarioService`, Reservaciones, check-in/check-out ni cancelaciones.

## Cambios propuestos para Inventario 1-F-C-B

- Obtener hotel actual con `obtenerHotelIdActualCompat()`.
- Agregar `WHERE m.hotel_id = ?`.
- Usar joins seguros con `inventario_productos` agregando `p.hotel_id = m.hotel_id`.
- Usar joins seguros con `habitaciones` agregando `h.hotel_id = m.hotel_id`.
- Hacer que el historial por producto respete `m.hotel_id`.

## Tabla de consultas auditadas

| Archivo | Metodo/seccion | Consulta o uso | Riesgo | Recomendacion |
| --- | --- | --- | --- | --- |
| `src/app/models/MovimientoInventario.php` | `getMovimientosDetallados()` | Lee `movimientos_inventario m` y obtiene producto por subconsultas a `inventario_productos`. | Bajo | Implementar en 1-F-C-B con `m.hotel_id = ?` y producto scoped por hotel. |
| `src/app/models/MovimientoInventario.php` | `getMovimientosPorProducto()` | Lee historial por `m.producto_id` y hace joins con `inventario_productos` y `habitaciones`. | Bajo | Implementar en 1-F-C-B con `m.hotel_id = ?`, `p.hotel_id = m.hotel_id` y `h.hotel_id = m.hotel_id`. |
| `src/app/models/MovimientoInventario.php` | `getMovimientosRecientes()` | Lee movimientos recientes con joins a producto y habitacion. | Bajo | Implementar en 1-F-C-B con filtro por hotel y joins scoped. |
| `src/app/models/MovimientoInventario.php` | `debugMovimientos()` | Lee `SELECT * FROM movimientos_inventario`. | Medio | Dejar fuera por ser debug. Revisar en fase de limpieza/debugs. |
| `src/app/controllers/InventarioController.php` | `indexAction()` | Usa `getMovimientosDetallados()` y fallback `getMovimientosRecientes()`. | Bajo | Queda cubierto si se corrige `MovimientoInventario`. |
| `src/app/controllers/InventarioController.php` | `historialAction()` | Valida producto con `getByIdWithCategory()` y luego usa `getMovimientosPorProducto()`. | Bajo | Queda cubierto si se corrige `MovimientoInventario`. |
| `src/app/controllers/InventarioController.php` | `generarPdfMovimientosAction()` | Consulta directa para PDF/export entre fechas. | Medio | Dejar fuera. PDF/export requiere fase separada. |
| `src/app/controllers/InventarioController.php` | `debugPdfAction()` | Varias consultas directas de debug contra `movimientos_inventario`. | Medio | Dejar fuera. Debugs no entran en 1-F-C-B. |
| `src/app/controllers/InventarioController.php` | `debugMovimientosDateAction()` | Consulta directa de movimientos por fecha. | Medio | Dejar fuera. Debugs no entran en 1-F-C-B. |
| `src/app/controllers/InventarioController.php` | `logInventarioAction()` | Lee ultimos movimientos y escribe archivo de log local. | Medio | Dejar fuera por ser debug/log. |
| `src/app/services/InventarioService.php` | `debugUltimosMovimientos()` | Lee conteo y ultimos movimientos. | Alto | Dejar fuera porque `InventarioService` esta ligado a check-in/check-out. |
| `src/app/controllers/HuespedController.php` | `debugMovimientosAction()` | Debug externo que consulta movimientos y llama al modelo. | Alto | Dejar fuera. No pertenece a Inventario 1-F-C-B. |
| `src/app/helpers/integracion_inventario.php` | `mantenimiento_inventario()` | Contiene `DELETE FROM movimientos_inventario` y recalculos legacy. | Alto | No tocar. Requiere fase propia y aprobacion explicita. |
| `src/app/models/Reservacion.php` | `devolverInventarioCancelacion()` | Usa `inventario_movimientos`, no `movimientos_inventario`. | Alto | Dejar fuera. Cancelaciones deben esperar fase propia. |

## Que dejar fuera

- `InventarioController::generarPdfMovimientosAction()`.
- `InventarioController::debugPdfAction()`.
- `InventarioController::debugMovimientosDateAction()`.
- `InventarioController::logInventarioAction()`.
- `InventarioService::debugUltimosMovimientos()`.
- `HuespedController::debugMovimientosAction()`.
- `integracion_inventario.php`.
- `Reservacion::devolverInventarioCancelacion()`.
- `inventario_movimientos`.
- Check-in/check-out.
- Caja.
- PWA/offline.

## Riesgos pendientes

- PDF/debugs aun tienen lecturas globales de `movimientos_inventario`.
- `InventarioService` sigue global y esta conectado a check-in/check-out.
- Cancelaciones usan `inventario_movimientos`, no `movimientos_inventario`.
- Reservaciones aun no tiene `hotel_id`.
- `inventario_movimientos` sigue inconsistente y debe tratarse aparte.

## Pruebas necesarias para Inventario 1-F-C-B

- Ejecutar `php -l` en archivos tocados.
- Ejecutar `git diff --check`.
- Ejecutar `docker compose exec app php tools/saas/verificar_estado.php` y confirmar `PASS`.
- Ejecutar `docker compose exec app php tools/saas/preflight_hotel_id.php` y confirmar `PASS`.
- Probar `GET /inventario`.
- Probar historial de producto.
- Confirmar que movimientos recientes solo muestran datos del hotel actual.
- Confirmar que historial por producto respeta `movimientos_inventario.hotel_id`.
- Confirmar que no se toco `InventarioService`.
- Confirmar que no se tocaron Reservaciones, Caja ni PWA/offline.

## Decision

No se implementa nada en esta fase. La siguiente fase recomendada es Inventario 1-F-C-B, limitada a lecturas internas de `MovimientoInventario.php` y, si hiciera falta, ajustes minimos de llamadas desde `InventarioController.php`.
