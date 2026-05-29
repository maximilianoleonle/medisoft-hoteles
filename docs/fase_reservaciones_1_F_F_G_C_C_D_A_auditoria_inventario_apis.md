# RESERVACIONES 1-F-F-G-C-C-D-A - Auditoria de inventario APIs

## 1. Objetivo

Auditar los endpoints API de inventario para detectar consultas globales, metodos inexistentes, rutas legacy o validaciones faltantes por hotel_id antes de implementar cambios.

## 2. Hallazgo principal

* El riesgo mas fuerte en inventario APIs esta en ApiController.php.
* Los tres endpoints existen, pero tienen mezcla de problemas de scope y cableado legacy.
* Dos endpoints llaman metodos inexistentes de InventarioService.
* Los tres usan $this->db / $this->jsonResponse(), que no estan definidos en ApiController ni en Controller.
* Hay lecturas globales antes de llegar a InventarioService.

## 3. Archivos revisados

* src/config/routes.php
* src/app/controllers/ApiController.php
* src/app/services/InventarioService.php
* src/app/models/Inventario.php
* src/app/models/MovimientoInventario.php
* src/app/services/ConfiguracionInventarioService.php
* src/app/models/Producto.php

## 4. Endpoints auditados

* /api/inventario/preview-checkin/{id}
* /api/inventario/alertas
* /api/inventario/verificar-stock/{id}

## 5. Estado por endpoint

### /api/inventario/preview-checkin/{id}

* Metodo: ApiController::previewCheckinInventarioAction().
* Existe.
* Primera lectura global por reservacion_id.
* Llama InventarioService::obtenerProductosCheckIn(), metodo no encontrado.
* Riesgo alto.

### /api/inventario/alertas

* Metodo: ApiController::alertasInventarioAction().
* Existe.
* Consulta alertas_inventario + productos sin hotel_id.
* Riesgo alto.

### /api/inventario/verificar-stock/{id}

* Metodo: ApiController::verificarStockHabitacionAction().
* Existe.
* Llama InventarioService::verificarDisponibilidadInventario(), metodo no encontrado.
* El metodo real scoped parece ser InventarioService::verificarDisponibilidad().
* Riesgo medio/alto.

## 6. Scope encontrado

* InventarioService::verificarDisponibilidad() ya valida:
  * habitaciones.hotel_id.
  * inventario_config_habitacion.hotel_id.
  * inventario_productos.hotel_id.
* InventarioService::descontarInventarioCheckIn() valida hotel en habitacion/config/producto.
* descontarInventarioCheckIn() actualiza inventario_productos por id + hotel_id.
* descontarInventarioCheckIn() inserta movimientos_inventario.hotel_id.

## 7. Global o legacy pendiente

* preview-checkin hace lectura inicial sobre reservacion_habitaciones / habitaciones sin hotel_id.
* alertasInventarioAction() usa alertas_inventario + productos sin scope.
* ConfiguracionInventarioService y Producto usan tablas/consultas legacy sin hotel.
* Algunos metodos debug consultan global, aunque no pertenecen directamente a estos endpoints.

## 8. Riesgos por nivel

### Alto

* /api/inventario/preview-checkin/{id}, por lectura global y metodo no encontrado.
* /api/inventario/alertas, por consultas sin hotel_id en tablas legacy.

### Medio/alto

* /api/inventario/verificar-stock/{id}, por llamar metodo inexistente aunque existe una alternativa scoped.

### Medio

* ConfiguracionInventarioService y Producto como dependencias legacy.

### Bajo/medio

* InventarioService::verificarDisponibilidad(), porque ya esta mejor scoped.
* InventarioService::descontarInventarioCheckIn(), porque ya valida hotel y escribe hotel_id.

## 9. Que implementar primero

RESERVACIONES 1-F-F-G-C-C-D-B:

* Corregir base segura de endpoints inventario en ApiController sin tocar otros modulos.

Motivo:

Antes de corregir logica especifica, hay que resolver llamadas a metodos inexistentes y uso de helpers no definidos.

## 10. Que implementar despues

* 1-F-F-G-C-C-D-C:
  Implementar /api/inventario/verificar-stock/{id} usando InventarioService::verificarDisponibilidad().
* 1-F-F-G-C-C-D-D:
  Implementar /api/inventario/preview-checkin/{id} scoped por reservacion/habitacion/hotel.
* 1-F-F-G-C-C-D-E:
  Resolver /api/inventario/alertas con fuente scoped o documentar dependencia de schema.
* Despues:
  src/api/*.php, Sync, IndexedDB/offline data.

## 11. Que dejar fuera

* src/api/*.php.
* Sync.php.
* IndexedDB/offline data.
* service worker.
* WHITE LABEL / BRANDING MULTI-HOTEL.
* migraciones/schema.
* cambios de datos.

## 12. Pruebas necesarias

* php -l en PHP tocados.
* git diff --check.
* verificar_estado.php y preflight_hotel_id.php si Docker vuelve a estar disponible.
* GET sin sesion y con sesion por hotel.
* Confirmar 0 nuevos hotel_id NULL.
* Confirmar que White Label no se toco.

## 13. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-D-B: correccion base segura de endpoints inventario en ApiController, sin tocar todavia src/api/*.php, Sync, IndexedDB ni White Label.
