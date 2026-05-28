# Reservaciones 1-F-F-E-E-E-D-C-A - Auditoria de lectura de solicitud de factura

## 1. Objetivo

Auditar `Reservacion::obtenerSolicitudFactura()` para preparar una correccion segura por `hotel_id`.

## 2. Hallazgo principal

- `Reservacion::obtenerSolicitudFactura()` consulta `solicitudes_factura` solo por `reservacion_id`.
- No filtra por `hotel_id`.
- Aunque no se encontraron llamadas externas en otros PHP, si se usa despues podria leer una solicitud de factura de otro hotel si coincide el `reservacion_id`.

## 3. Archivos encontrados

- `src/app/models/Reservacion.php`
- `src/app/controllers/ReservacionController.php`
- `src/app/views/reservaciones/ver.php`

## 4. Metodos y zonas auditadas

- `Reservacion::obtenerSolicitudFactura()`
- `Reservacion::crearSolicitudFactura()`
- `ReservacionController::procesarSolicitudFactura()`
- Consultas de exportaciones PDF/Excel de reservaciones que usan `solicitudes_factura`
- `reservaciones/ver.php` como vista/render

## 5. Estado por metodo

- `Reservacion::obtenerSolicitudFactura()`: riesgo medio; lectura individual global por `reservacion_id`.
- `Reservacion::crearSolicitudFactura()`: ya scoped; inserta `hotel_id`.
- `ReservacionController::procesarSolicitudFactura()`: ya valida reservacion por hotel y pasa `hotel_id`.
- Exportaciones PDF/Excel de reservaciones: ya filtran `solicitudes_factura` por `hotel_id` en las consultas revisadas.
- `reservaciones/ver.php`: bajo riesgo; render/formulario, sin consulta directa.

## 6. Consulta global detectada

```sql
SELECT * FROM solicitudes_factura WHERE reservacion_id = ?
```

Falta filtrar por `hotel_id`.

## 7. Propuesta tecnica

En la siguiente fase tocar unicamente `Reservacion::obtenerSolicitudFactura()` en `src/app/models/Reservacion.php`:

- Obtener `hotel_id` con `hotelIdActual()`.
- Consultar `solicitudes_factura` por `reservacion_id + hotel_id`.
- Idealmente unir con `reservaciones` y validar `sf.hotel_id = r.hotel_id`.
- Mantener `huespedes` y `usuarios` como auxiliares si aparecen despues, no como fuente de scope.

## 8. Que dejar fuera

- `obtenerSolicitudesPendientes()`.
- `actualizarSolicitudFactura()`.
- `FacturacionController`.
- Cancelaciones.
- Modificar dias.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.

## 9. Pruebas necesarias

- `php -l src/app/models/Reservacion.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Prueba controlada sin escritura.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que no se tocaron `FacturacionController`, cancelaciones, modificar dias, PWA/Sync/APIs.

## 10. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-C-B: implementar unicamente `Reservacion::obtenerSolicitudFactura()` scoped por `hotel_id`, sin tocar todavia pendientes, actualizaciones, `FacturacionController` ni cancelaciones.
