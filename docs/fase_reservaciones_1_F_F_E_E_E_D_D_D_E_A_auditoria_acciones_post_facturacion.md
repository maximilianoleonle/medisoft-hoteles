# Reservaciones 1-F-F-E-E-E-D-D-D-E-A - Auditoria de acciones POST de facturacion

## 1. Objetivo

Auditar las acciones POST de `FacturacionController` para preparar una correccion segura por `hotel_id` antes de modificar estados o datos de `solicitudes_factura`.

## 2. Hallazgo principal

- Las acciones POST de `FacturacionController` ya delegan cambios a `Reservacion::actualizarSolicitudFactura()`.
- `Reservacion::actualizarSolicitudFactura()` ya valida `hotel_id` con `sf.hotel_id`, `sf.hotel_id = r.hotel_id` y `r.hotel_id = ?`.
- El riesgo restante esta en el flujo del controller: validar explicitamente la solicitud scoped antes de operar.
- `cancelarAction()` requiere mas cuidado porque lee detalle para concatenar notas.

## 3. Archivos encontrados

- `src/app/controllers/FacturacionController.php`.
- `src/app/models/Reservacion.php`.
- `src/app/views/facturacion/detalle.php`.
- `src/config/routes.php`.

## 4. Metodos auditados

- `guardarAction()`.
- `completarAction()`.
- `enProcesoAction()`.
- `cancelarAction()`.
- `obtenerSolicitudCompleta()`.
- `Reservacion::actualizarSolicitudFactura()`.
- `facturacion/detalle.php`.

## 5. Estado por metodo

- `guardarAction()`: riesgo medio; modifica via modelo scoped, pero conviene validar solicitud del hotel actual antes de guardar.
- `completarAction()`: riesgo medio; modifica via modelo scoped, pero conviene validar solicitud scoped antes de completar.
- `enProcesoAction()`: riesgo bajo/medio; modifica via modelo scoped, pero conviene validar solicitud scoped antes de cambiar estado.
- `cancelarAction()`: riesgo medio; detalle y update ya scoped, pero debe endurecerse porque concatena notas.
- `obtenerSolicitudCompleta()`: riesgo bajo; ya scoped.
- `Reservacion::actualizarSolicitudFactura()`: riesgo bajo; ya scoped.
- `facturacion/detalle.php`: riesgo bajo; render/form POST.

## 6. Validaciones requeridas

Antes de actualizar, cada POST debe validar:

- `sf.hotel_id = ?`.
- `sf.reservacion_id = r.id`.
- `sf.hotel_id = r.hotel_id`.
- `r.hotel_id = ?`.
- `usuarios` debe seguir como auxiliar, no como fuente de scope.

## 7. Que implementar primero

Reservaciones 1-F-F-E-E-E-D-D-D-E-B:

- `guardarAction()` scoped/defensivo.

Motivo:

Es el flujo POST mas directo para guardar datos fiscales; conviene empezar por el mas pequeno antes de estados y cancelacion.

## 8. Que implementar despues

- Reservaciones 1-F-F-E-E-E-D-D-D-E-C: `completarAction()` y `enProcesoAction()` scoped.
- Reservaciones 1-F-F-E-E-E-D-D-D-E-D: `cancelarAction()` de `FacturacionController` scoped, en fase propia por manejo de notas.

## 9. Que dejar fuera

- Cancelaciones de reservacion.
- `modificarDiasAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.
- Usuarios tenant.

## 10. Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Pruebas POST solo con rollback seguro o entorno controlado.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que PWA/Sync/APIs no se tocaron.

## 11. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-E-B: implementar unicamente `guardarAction()` scoped/defensivo, sin tocar todavia `completarAction()`, `enProcesoAction()` ni `cancelarAction()`.
