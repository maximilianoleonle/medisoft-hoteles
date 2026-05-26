# Fase Inventario 1-F-C-C - Cierre tecnico de movimientos manuales e historial

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Documentar el cierre tecnico de la migracion progresiva de `movimientos_inventario` para movimientos manuales e historial interno de Inventario.

Esta fase es solo documental:

- No se modifico codigo funcional.
- No se ejecuto SQL.
- No se hizo `ALTER TABLE`.
- No se toco base de datos.
- No se avanzo a `InventarioService`.
- No se avanzo a check-in/check-out.
- No se avanzo a Reservaciones.
- No se avanzo a cancelaciones/devoluciones.

## Que quedo logrado

- `movimientos_inventario` ya tiene columna `hotel_id`.
- El backfill historico quedo completo a Los Cedros.
- Las herramientas SaaS quedan en `PASS`.
- Los movimientos manuales nuevos escriben `hotel_id`.
- Entrada manual, salida manual, ajuste manual y movimiento inicial respetan `hotel_id`.
- El ajuste manual ya no duplica movimiento.
- Las lecturas principales de movimientos respetan `hotel_id`:
  - `MovimientoInventario::getMovimientosDetallados()`.
  - `MovimientoInventario::getMovimientosPorProducto()`.
  - `MovimientoInventario::getMovimientosRecientes()`.

## Commits relacionados

- Migracion de `movimientos_inventario.hotel_id`.
- Actualizacion de herramientas SaaS para aceptar `movimientos_inventario.hotel_id`.
- Scope de movimientos manuales de Inventario al hotel actual.
- Scope de lecturas e historial de movimientos al hotel actual.

## Que NO se toco

- `InventarioService`.
- Check-in/check-out.
- Reservaciones.
- Cancelaciones/devoluciones.
- `inventario_movimientos`.
- Caja.
- PWA/offline.
- PDF/export/debugs.
- Dashboard/reportes.

## Riesgos pendientes

- `InventarioService` sigue global.
- Check-in/check-out sigue fuera de alcance.
- Reservaciones todavia no tiene `hotel_id`.
- Cancelaciones usan `inventario_movimientos`, no `movimientos_inventario`.
- PDF/export/debugs siguen con lecturas globales.
- `inventario_movimientos` sigue como tabla vacia pero referenciada.
- `alertas_inventario` sigue sin `hotel_id`.
- `productos` y `categorias_producto` siguen como modulo paralelo o legacy.

## Decision

- No tocar `InventarioService` todavia.
- No tocar check-in/check-out hasta auditar Reservaciones.
- No tocar cancelaciones hasta resolver `inventario_movimientos` vs `movimientos_inventario`.
- No tocar PDF/debugs todavia.

## Siguiente fase recomendada

La siguiente fase recomendada es Inventario 1-G-A: auditoria especifica de la inconsistencia entre `inventario_movimientos` y `movimientos_inventario`.

Despues de resolver esa auditoria, conviene avanzar con Reservaciones schema/backfill antes de tocar check-in/check-out.
