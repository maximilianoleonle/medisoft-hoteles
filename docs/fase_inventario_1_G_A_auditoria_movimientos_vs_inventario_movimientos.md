# Fase Inventario 1-G-A - Auditoria de movimientos vs inventario_movimientos

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Auditar la inconsistencia entre las tablas:

- `movimientos_inventario`
- `inventario_movimientos`

La auditoria fue solo lectura:

- No se modificaron archivos.
- No se ejecuto SQL destructivo.
- No se hicieron migraciones.
- No se hizo `ALTER TABLE`.
- No se hicieron `INSERT`, `UPDATE` ni `DELETE`.
- No se implemento nada.

## Hallazgo central

- `movimientos_inventario` es la tabla activa.
- Tiene 936 registros.
- Tiene foreign keys reales.
- Tiene `hotel_id`.
- Recibe descuentos de check-in y movimientos manuales.
- `inventario_movimientos` tiene 0 registros.
- `inventario_movimientos` sigue referenciada por `Reservacion::devolverInventarioCancelacion()`.

## Riesgo principal

Check-in descuenta inventario en `movimientos_inventario`, pero cancelacion intenta devolver usando `inventario_movimientos`.

Esto puede provocar que el stock descontado no se restaure correctamente en cancelaciones, porque la devolucion busca salidas en una tabla vacia.

## Comparativa

| Aspecto | `movimientos_inventario` | `inventario_movimientos` |
| --- | --- | --- |
| Registros | 936 | 0 |
| `ENTRADA` | 34 | 0 |
| `SALIDA` | 902 | 0 |
| Con `reservacion_id` | 865 | 0 |
| Con `habitacion_id` | 865 | 0 |
| Tiene `hotel_id` | Si | No |
| Foreign keys | hotel, producto, habitacion, reservacion, usuario | No detectadas |
| Estado probable | Activa | Legacy/paralela, pero aun referenciada |

## Referencias encontradas

| Archivo | Referencia | Hallazgo |
| --- | --- | --- |
| `src/app/models/Reservacion.php` | `devolverInventarioCancelacion()` | Usa `inventario_movimientos` para leer salidas e insertar devoluciones. |
| `src/app/services/InventarioService.php` | `descontarInventarioCheckIn()` | Check-in automatico escribe salidas en `movimientos_inventario`. |
| `src/app/models/MovimientoInventario.php` | Modelo activo | Usa `movimientos_inventario` para movimientos manuales y lecturas principales. |
| `src/app/controllers/InventarioController.php` | Lecturas/PDF/debugs | Tiene consultas directas a `movimientos_inventario` en PDF/debugs. |
| `src/app/helpers/integracion_inventario.php` | Mantenimiento legacy | Contiene operaciones potencialmente destructivas sobre `movimientos_inventario`. |
| `src/app/controllers/HuespedController.php` | Debug de movimientos | Consulta `movimientos_inventario` para debug. |
| `src/app/controllers/ReservacionController.php` | Descuento de inventario | Invoca `InventarioService::descontarInventarioCheckIn()`. |

## Decision

- No tocar implementacion todavia.
- No tocar `InventarioService` todavia.
- No tocar Reservaciones todavia.
- No tocar cancelaciones todavia.
- No eliminar `inventario_movimientos` todavia.
- No declarar `inventario_movimientos` legacy hasta corregir referencias.

## Siguiente fase recomendada

Inventario 1-G-B-A: propuesta tecnica para corregir cancelaciones/devoluciones, sin ejecutar cambios.

## Recomendacion tecnica probable

- Mantener `movimientos_inventario` como tabla activa.
- Preparar correccion para que cancelaciones lean salidas desde `movimientos_inventario`.
- Tratar `inventario_movimientos` como legacy/paralela hasta documentar retiro.
- No tocar check-in/check-out hasta coordinar con Reservaciones.

## Que NO se toco

- Reservaciones funcionales.
- Check-in/check-out.
- `InventarioService`.
- Caja.
- PWA/offline.
- Migraciones.
- Base de datos.
