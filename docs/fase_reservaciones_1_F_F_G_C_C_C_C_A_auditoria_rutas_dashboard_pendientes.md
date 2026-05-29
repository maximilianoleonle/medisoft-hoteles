# Reservaciones 1-F-F-G-C-C-C-C-A: auditoria de rutas dashboard pendientes

## 1. Objetivo

Auditar las rutas dashboard declaradas sin metodo encontrado y decidir si conviene implementarlas, mapearlas a metodos reales scoped, desactivarlas o documentarlas como legacy.

## 2. Hallazgo principal

* Existen rutas `/api/dashboard/*` declaradas hacia metodos no encontrados en `ApiController`.
* `/api/dashboard/alertas` si se invoca desde `dashboard.js:updateAlerts()`.
* `/api/dashboard/ocupacion` y `/api/dashboard/movimientos-recientes` aparecen declaradas en `dashboard.js`, pero no se detecto invocacion directa activa.
* El riesgo principal no es una consulta global directa todavia, sino que el router puede lanzar error de metodo no encontrado.
* Si se implementa `/api/dashboard/alertas` usando metodos pendientes globales de `Reservacion`, podria introducir riesgo multi-hotel.

## 3. Archivos revisados

* `src/config/routes.php`
* `src/app/controllers/ApiController.php`
* `src/app/controllers/DashboardController.php`
* `src/public_html/js/dashboard.js`
* `src/app/views/layout/footer.php`
* `src/public_html/service-worker.js`
* `src/public_html/js/offline-data.js`

## 4. Rutas auditadas

* `/api/dashboard/ocupacion`
* `/api/dashboard/movimientos-recientes`
* `/api/dashboard/alertas`

## 5. Tabla de hallazgos

### `/api/dashboard/ocupacion`

* Metodo esperado: `ApiController::ocupacionActualAction()`.
* No existe.
* Declarada en `dashboard.js`, pero no invocada directamente.
* Posible equivalente scoped: `DashboardController::getEstadisticasCompletas()` o `getDatosGraficos()`.
* Riesgo medio.

### `/api/dashboard/movimientos-recientes`

* Metodo esperado: `ApiController::movimientosRecientesAction()`.
* No existe.
* Declarada en `dashboard.js`, pero no invocada directamente.
* Posible equivalente scoped: `MovimientoCaja::obtenerUltimosMovimientos()`, que ya filtra `hotel_id`.
* Riesgo medio.

### `/api/dashboard/alertas`

* Metodo esperado: `ApiController::alertasDashboardAction()`.
* No existe.
* Si se invoca en `dashboard.js:updateAlerts()`.
* No hay equivalente directo seguro.
* Las fuentes naturales `Reservacion::getCheckInsPendientes()` y `getCheckOutsPendientes()` siguen globales.
* Riesgo alto.

## 6. Riesgos por nivel

### Alto

* `/api/dashboard/alertas`, porque `dashboard.js` la llama y el router puede lanzar metodo no encontrado.
* `/api/dashboard/alertas` si se implementa usando metodos globales pendientes de `Reservacion`.

### Medio

* `/api/dashboard/ocupacion`, porque esta declarada y expuesta aunque no se detecto uso activo directo.
* `/api/dashboard/movimientos-recientes`, porque esta declarada y expuesta aunque no se detecto uso activo directo.

### Bajo

* Service worker/offline para estas rutas especificas; no se detecto cache ni IndexedDB usando `/api/dashboard/*`.

## 7. Recomendacion por ruta

* `/api/dashboard/alertas`: implementar primero, pero con consultas nuevas scoped o despues de scopear los metodos pendientes de `Reservacion`. No usar metodos globales.
* `/api/dashboard/ocupacion`: mapear a un metodo real scoped, segun contrato JSON esperado.
* `/api/dashboard/movimientos-recientes`: implementar usando `MovimientoCaja::obtenerUltimosMovimientos()`, siempre confirmando `hotel_id`.

## 8. Que implementar primero

RESERVACIONES 1-F-F-G-C-C-C-C-B:

* Cerrar `/api/dashboard/alertas` con fuente scoped.

Recomendacion tecnica:

* No usar `Reservacion::getCheckInsPendientes()` ni `getCheckOutsPendientes()` si siguen globales.
* Crear consultas minimas scoped en `ApiController` o usar metodos ya scoped si existen.
* Responder JSON compatible con `dashboard.js:updateAlerts()`.
* Mantener usuarios/huespedes como auxiliares, no como fuente de scope.

## 9. Que implementar despues

* RESERVACIONES 1-F-F-G-C-C-C-C-C: mapear `/api/dashboard/ocupacion` a metodo real scoped.
* RESERVACIONES 1-F-F-G-C-C-C-C-D: implementar `/api/dashboard/movimientos-recientes` con `MovimientoCaja::obtenerUltimosMovimientos()` o metodo equivalente scoped.

## 10. Que dejar fuera

* Inventario APIs.
* `src/api/*.php`.
* `Sync.php`.
* IndexedDB/offline data.
* `service-worker.js`.
* WHITE LABEL / BRANDING MULTI-HOTEL.
* Migraciones/schema.
* Cambios de datos.

## 11. Pruebas necesarias

* `php -l` en PHP tocados.
* `git diff --check`.
* `verificar_estado.php` PASS si Docker esta disponible.
* `preflight_hotel_id.php` PASS si Docker esta disponible.
* GET sin sesion y con sesion por hotel.
* Verificar que `dashboard.js:updateAlerts()` no rompa.
* Confirmar 0 nuevos `hotel_id` NULL.
* Confirmar que White Label no se toco.

## 12. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-C-C-B: implementar unicamente `/api/dashboard/alertas` con consultas scoped por `hotel_id`, sin tocar todavia ocupacion, movimientos recientes, inventario APIs, `src/api/*.php`, Sync ni IndexedDB.
