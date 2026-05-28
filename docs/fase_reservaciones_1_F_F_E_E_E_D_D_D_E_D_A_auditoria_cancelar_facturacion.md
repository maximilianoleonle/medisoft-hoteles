# Reservaciones 1-F-F-E-E-E-D-D-D-E-D-A

## Auditoria de FacturacionController::cancelarAction()

## 1. Objetivo

Auditar `FacturacionController::cancelarAction()` para preparar una correccion segura por `hotel_id`, cuidando el manejo de notas y la actualizacion de `solicitudes_factura`.

## 2. Hallazgo principal

- `cancelarAction()` ya se apoya en piezas scoped.
- Usa `obtenerSolicitudCompleta()` para leer detalle.
- Usa `Reservacion::actualizarSolicitudFactura()` para actualizar.
- El punto pendiente es defensivo: validar explicitamente que la solicitud exista y pertenezca al hotel actual antes de concatenar notas y cancelar.

## 3. Archivos encontrados

- `src/app/controllers/FacturacionController.php`
- `src/app/models/Reservacion.php`
- `src/app/views/facturacion/detalle.php`
- `src/config/routes.php`

## 4. Metodos auditados

- `FacturacionController::cancelarAction()`
- `FacturacionController::obtenerSolicitudCompleta()`
- `Reservacion::actualizarSolicitudFactura()`
- `facturacion/detalle.php`
- Ruta `POST /facturacion/cancelar`

## 5. Estado por metodo

- `cancelarAction()`: riesgo medio; modifica via modelo scoped, pero debe validar solicitud scoped antes de manejar notas.
- `obtenerSolicitudCompleta()`: riesgo bajo; ya scoped por `hotel_id`.
- `Reservacion::actualizarSolicitudFactura()`: riesgo bajo; ya scoped por `hotel_id`.
- `facturacion/detalle.php`: riesgo bajo; formulario POST con CSRF y `solicitud_id`.
- Ruta `POST /facturacion/cancelar`: riesgo medio; expone accion de cancelacion.

## 6. Validaciones requeridas

Antes de concatenar notas y actualizar:

- `sf.hotel_id = ?`
- `sf.reservacion_id = r.id`
- `sf.hotel_id = r.hotel_id`
- `r.hotel_id = ?`
- `usuarios` debe seguir como auxiliar, no como fuente de scope.

## 7. Propuesta tecnica

En la siguiente fase tocar solo `cancelarAction()`:

- Resolver hotel con `obtenerHotelIdActualCompat()`.
- Cargar solicitud con `obtenerSolicitudCompleta($id)`.
- Si no existe o no pertenece al hotel actual, redirigir con error controlado.
- Solo despues leer/concatenar notas.
- Luego llamar `Reservacion::actualizarSolicitudFactura()`.
- Mantener la logica original de cancelacion de solicitud de factura.

## 8. Que dejar fuera

- Cancelaciones de reservacion.
- `modificarDiasAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.
- Usuarios tenant.

## 9. Pruebas necesarias

- `php -l` en archivos tocados durante implementacion futura.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Pruebas POST solo con rollback seguro o entorno controlado.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que PWA/Sync/APIs no se tocaron.

## 10. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-E-D-B: implementar unicamente `FacturacionController::cancelarAction()` scoped/defensivo, sin tocar cancelaciones de reservacion ni modificar dias.
