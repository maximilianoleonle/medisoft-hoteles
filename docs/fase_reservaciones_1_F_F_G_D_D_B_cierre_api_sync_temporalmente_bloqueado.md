# RESERVACIONES 1-F-F-G-D-D-B - Cierre de /api/sync temporalmente bloqueado

## 1. Objetivo

Documentar el cierre tecnico de la proteccion temporal de /api/sync moderno para impedir procesamiento de operaciones offline mientras Sync::procesarLote() no este scoped por hotel_id derivado del servidor.

## 2. Que se logro

* ApiController::syncAction() queda protegido temporalmente.
* Conserva validacion POST.
* Conserva validacion CSRF.
* Deriva contexto con hotelIdActual().
* No instancia Sync.
* No llama Sync::procesarLote().
* No procesa operaciones offline.
* No marca operaciones como sincronizadas.
* No borra operaciones offline.
* Responde JSON controlado.
* Usa HTTP 423 Locked porque el endpoint existe, pero queda bloqueado por seguridad multi-hotel.
* Responde pending_operations_preserved: true para que el frontend no asuma que debe borrar operaciones pendientes.

## 3. Respuesta JSON

* success: false.
* error: sync_temporarily_disabled.
* message: La sincronizacion offline esta temporalmente deshabilitada mientras se asegura el aislamiento multi-hotel.
* pending_operations_preserved: true.

## 4. Archivo modificado

* src/app/controllers/ApiController.php

## 5. Que NO se toco

* Sync.php.
* operaciones profundas de Sync.
* src/api/sync.php.
* IndexedDB/offline data.
* pwa.js.
* offline-data.js.
* reservaciones-offline.js.
* habitaciones-offline.js.
* caja-offline.js.
* service-worker.js.
* manifest/branding/White Label.
* migraciones.
* schema.
* base de datos.

## 6. Validaciones realizadas

* php -l src/app/controllers/ApiController.php: PASS.
* git diff --check: PASS.
* verificar_estado.php: bloqueado por Docker Desktop/engine no disponible.
* preflight_hotel_id.php: bloqueado por Docker Desktop/engine no disponible.
* GET/POST local: bloqueado porque no hay servidor activo en localhost:8080.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron Sync.php, JS offline, /api/sync profundo ni White Label.

## 7. Riesgos pendientes

* Sync.php sigue pendiente si en el futuro se reactiva sincronizacion real.
* Sync::procesarLote() sigue pendiente de exigir contexto hotel del servidor.
* Operaciones offline profundas siguen pendientes.
* El frontend puede seguir encolando operaciones, pero /api/sync ya no las procesa.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance hasta cerrar formalmente seguridad PWA/Sync/APIs.

## 8. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-E-A: auditoria/cierre general de PWA/Sync/APIs, verificando que:

* service worker no cachea HTML privado.
* endpoints API core/dashboard/inventario estan cerrados.
* src/api legacy de lectura esta bloqueado.
* src/api/sync.php esta bloqueado.
* IndexedDB esta namespaceado por hotel/sesion.
* storage se limpia en logout/cambio de scope.
* /api/sync moderno esta temporalmente bloqueado.
* Sync real queda desactivado hasta fase futura segura.
