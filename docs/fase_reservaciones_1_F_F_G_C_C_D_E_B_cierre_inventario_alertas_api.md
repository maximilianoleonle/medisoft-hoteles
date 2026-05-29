# RESERVACIONES 1-F-F-G-C-C-D-E-B - Cierre inventario alertas API

## 1. Objetivo

Documentar el cierre tecnico del endpoint /api/inventario/alertas usando inventario_productos.hotel_id como fuente scoped.

## 2. Que se logro

* alertasInventarioAction() ahora usa hotelIdActual().
* Consulta unicamente inventario_productos.
* Filtra inventario_productos.hotel_id = ?.
* Genera alertas derivadas de stock actual.
* Detecta productos con stock_actual <= stock_minimo.
* Clasifica como CRITICO / SIN_STOCK cuando stock_actual <= 0.
* Clasifica como ADVERTENCIA / STOCK_BAJO cuando esta bajo minimo.
* Responde data: [] si no hay alertas.
* Responde success: false, data: [] y mensaje controlado si hay excepcion.
* No consulta alertas_inventario.
* No consulta productos legacy.
* No agrega escrituras.

## 3. Endpoint cerrado

* /api/inventario/alertas

## 4. Fuente usada

* inventario_productos.hotel_id

## 5. Archivo modificado

* src/app/controllers/ApiController.php

## 6. Que NO se toco

* alertas_inventario.
* productos legacy.
* InventarioService.php.
* Modelos de inventario.
* src/api/*.php.
* Sync.php.
* IndexedDB/offline data.
* pwa.js.
* offline-data.js.
* service-worker.js.
* manifest/branding/White Label.
* migraciones.
* schema.
* base de datos.

## 7. Validaciones realizadas

* php -l src/app/controllers/ApiController.php: PASS.
* git diff --check: PASS.
* verificar_estado.php: bloqueado por Docker Desktop/engine no disponible.
* preflight_hotel_id.php: bloqueado por Docker Desktop/engine no disponible.
* GET local: bloqueado porque no hay servidor activo en localhost:8080.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron src/api/*.php, Sync, IndexedDB/offline data ni White Label.

## 8. Riesgos pendientes

* alertas_inventario y productos legacy quedan como pendientes/documentables.
* src/api/*.php legacy/directos siguen pendientes.
* Sync.php sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## 9. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-D-F-CIERRE: cierre general del bloque inventario APIs antes de pasar a src/api/*.php legacy/directos.
