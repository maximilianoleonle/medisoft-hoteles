# Reservaciones 1-F-C-A - Auditoria de codigo funcional de Caja

## 1. Objetivo

Auditar el codigo funcional de Caja, pagos y cortes para preparar una futura integracion de `hotel_id` sin romper ingresos, cortes cerrados ni reportes financieros.

Esta fase fue solo lectura:

- No se modificaron archivos.
- No se ejecuto SQL.
- No se toco base de datos.
- No se hicieron migraciones.
- No se hizo `ALTER TABLE`.
- No se implemento nada.

## 2. Hallazgo principal

- La base ya tiene `hotel_id` en `cajas`, `cortes_caja` y `movimientos_caja`.
- El codigo funcional de Caja todavia opera mayormente con IDs globales.
- El riesgo principal es que nuevas aperturas, cierres o movimientos creen datos sin `hotel_id` o lean/cierren movimientos de otro hotel.

## 3. Archivos encontrados

- `src/app/models/Caja.php`
- `src/app/controllers/CajaController.php`
- `src/app/models/MovimientoCaja.php`
- `src/app/models/Reservacion.php`
- `src/app/controllers/ReservacionController.php`
- `src/app/controllers/ReportesController.php`
- `src/app/controllers/DashboardController.php`
- `src/app/models/Reporte.php`
- `src/app/models/Sync.php`, solo detectado y debe esperar.
- Vistas de caja/cortes y reportes relacionados.

## 4. Metodos criticos

- `Caja::obtenerCajaPrincipal()`
- `Caja::tieneCorteAbierto()`
- `Caja::obtenerCorteActual()`
- `Caja::abrirCaja()`
- `Caja::cerrarCaja()`
- `Caja::obtenerResumenCaja()`
- `Caja::obtenerHistorialCortes()`
- `MovimientoCaja::registrarMovimiento()`
- `MovimientoCaja::obtenerMovimientosDetallados()`
- `MovimientoCaja::editarMovimiento()`
- `MovimientoCaja::eliminarMovimiento()`
- `MovimientoCaja::obtenerTotalesPorPeriodo()`
- `MovimientoCaja::obtenerUltimosMovimientos()`
- `CajaController::indexAction()`
- `CajaController::abrirAction()`
- `CajaController::registrarIngresoAction()`
- `CajaController::registrarGastoAction()`
- `CajaController::movimientosAction()`
- `CajaController::corteAction()`
- `CajaController::cerrarCorteAction()`
- `CajaController::editarMovimientoAction()`
- `CajaController::obtenerMovimientoAction()`
- `Reservacion::checkInConPagosMixtos()`
- `Reservacion::cancelar()`
- `Reservacion::checkInCheckOutExpress()`
- `ReservacionController::modificarDiasAction()`
- `ReservacionController::cambiarMetodoPagoAction()`
- `ReservacionController::cancelarAction()`

## 5. Consultas criticas a scopear

- `SELECT * FROM cajas WHERE activa = 1 LIMIT 1` debe filtrar por `hotel_id`.
- Cortes abiertos deben validar `caja_id + hotel_id`.
- `INSERT INTO cortes_caja` debe escribir `hotel_id`.
- `UPDATE cortes_caja` debe usar `id + hotel_id`.
- Lecturas de `movimientos_caja` por `corte_id` deben filtrar `hotel_id`.
- `INSERT INTO movimientos_caja` debe incluir `hotel_id`.
- Joins con `reservaciones`, `reservacion_habitaciones` y `habitaciones` deben validar `hotel_id`.

## 6. Riesgos

- `cerrarCaja()` sin `hotel_id` puede cerrar corte equivocado o calcular totales globales.
- `registrarMovimiento()` sin `hotel_id` puede crear ingresos/gastos sin tenant.
- `cambiarMetodoPagoAction()` es alto riesgo porque borra/recrea pagos y movimientos financieros.
- `cancelar()` con devolucion en caja es alto riesgo.
- `modificarDiasAction()` puede crear cobros/devoluciones adicionales.
- Reportes/Dashboard pueden mezclar datos entre hoteles.
- Sync/PWA/API siguen fuera de scope.

## 7. Que implementar primero

Reservaciones 1-F-C-B debe limitarse a Caja funcional base:

- `Caja.php`: scopear caja activa, corte actual, apertura, cierre, resumen e historial por `hotel_id`.
- `MovimientoCaja.php`: escribir `hotel_id`, leer/editar/eliminar movimientos con `hotel_id`.
- `CajaController.php`: ajustar acciones para usar metodos scoped.
- Validar que `corte_id`, `caja_id` y `movimiento_id` pertenezcan al hotel actual.

## 8. Que debe esperar

- `ReportesController`.
- `DashboardController`.
- `Reporte.php`.
- `Sync.php`.
- PWA/offline.
- APIs globales.
- `cambiarMetodoPagoAction()`.
- `modificarDiasAction()`.
- Cancelaciones financieras.
- Exportaciones/PDF/cortes historicos avanzados.
- Cambios de schema, `NOT NULL` o FKs estrictas.

## 9. Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Pruebas controladas de abrir caja.
- Registrar ingreso/gasto.
- Listar movimientos.
- Cerrar corte.
- Confirmar 0 nuevos `hotel_id` NULL en `cajas`, `cortes_caja` y `movimientos_caja`.
- Confirmar que no se toco PWA/offline, Sync, APIs, Reportes/Dashboard.

## 10. Recomendacion

Si conviene preparar Reservaciones 1-F-C-B, pero solo como Caja funcional base.

No tocar reportes financieros ni acciones de reservaciones que recrean movimientos de caja todavia.

