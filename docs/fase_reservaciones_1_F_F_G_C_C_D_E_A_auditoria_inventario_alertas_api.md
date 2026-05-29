# RESERVACIONES 1-F-F-G-C-C-D-E-A - Auditoria inventario alertas API

## 1. Objetivo

Auditar /api/inventario/alertas para decidir si debe implementarse con una fuente scoped por hotel_id, mantenerse protegido con respuesta controlada, o documentarse como dependiente de schema/tablas legacy.

## 2. Hallazgo principal

* /api/inventario/alertas hoy esta protegido en ApiController::alertasInventarioAction() con respuesta 501 controlada.
* Actualmente no consulta base de datos.
* La implementacion legacy anterior consultaba alertas_inventario + productos sin hotel_id.
* inventario_productos ya tiene hotel_id.
* alertas_inventario y productos siguen tratados como tablas legacy/pendientes.
* verificar_estado.php los lista como operativas esperadas sin hotel_id.
* preflight_hotel_id.php marca que ambos necesitan hotel_id en fase futura.

## 3. Archivos encontrados

* src/config/routes.php
* src/app/controllers/ApiController.php
* src/app/services/InventarioService.php
* src/app/models/Inventario.php
* src/app/models/Producto.php
* src/app/models/MovimientoInventario.php
* src/app/services/ConfiguracionInventarioService.php
* migrations/20260526_006_add_hotel_id_inventario_base.sql

## 4. Opciones evaluadas

* Mantener 501 protegido.
* Implementar ahora desde inventario_productos.hotel_id.
* Implementar usando alertas_inventario + productos.
* Crear fase futura de schema para alertas_inventario/productos.
* Documentar como legacy.

## 5. Riesgo por opcion

* Mantener 501 protegido: riesgo bajo, pero el endpoint no entrega alertas.
* Implementar desde inventario_productos.hotel_id: riesgo bajo/medio, recomendado si se aceptan alertas derivadas de stock actual.
* Implementar usando alertas_inventario + productos: riesgo alto, no recomendado sin schema.
* Crear fase futura de schema: riesgo medio, correcto si se requieren alertas persistidas legacy.
* Documentar como legacy: riesgo bajo si el producto ya migro a inventario_productos.

## 6. Recomendacion final

Implementar /api/inventario/alertas usando fuente scoped derivada de inventario_productos.hotel_id, por ejemplo productos activos del hotel actual donde stock_actual <= stock_minimo, sin tocar alertas_inventario ni productos.

## 7. Que NO se debe hacer

* No usar alertas_inventario + productos sin hotel_id.
* No hacer migraciones.
* No modificar schema.
* No tocar base de datos.
* No tocar src/api/*.php.
* No tocar Sync.php.
* No tocar IndexedDB/offline data.
* No tocar WHITE LABEL / BRANDING MULTI-HOTEL.

## 8. Fases propuestas

* 1-F-F-G-C-C-D-E-B: implementar /api/inventario/alertas con inventario_productos.hotel_id, sin tocar schema.
* 1-F-F-G-C-C-D-E-C: documentar o aislar alertas_inventario/productos como legacy pendiente.
* Despues continuar con src/api/*.php, Sync e IndexedDB/offline data.

## 9. Pruebas futuras

* php -l en PHP tocado.
* git diff --check.
* verificar_estado.php si Docker esta disponible.
* preflight_hotel_id.php si Docker esta disponible.
* GET de /api/inventario/alertas si hay servidor activo.
* Confirmar que White Label no se toco.
* Confirmar que no se ejecuto SQL.
* Confirmar que no se toco base de datos.

## 10. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-D-E-B: implementar unicamente /api/inventario/alertas usando inventario_productos.hotel_id como fuente scoped, sin tocar schema, src/api/*.php, Sync, IndexedDB ni White Label.
