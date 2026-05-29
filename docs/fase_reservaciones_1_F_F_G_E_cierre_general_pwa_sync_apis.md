# RESERVACIONES 1-F-F-G-E - Cierre general PWA/Sync/APIs

## 1. Objetivo

Documentar el cierre general del bloque PWA/Sync/APIs dentro de la migracion SaaS multi-hotel.

## 2. Que quedo cerrado

* Auditoria PWA/offline, Sync y APIs.
* Service worker/cache seguro.
* Endpoints core API de busqueda, reservaciones y habitaciones.
* APIs de ReservacionController.
* Dashboard APIs.
* Inventario APIs.
* src/api/*.php legacy/directos de lectura.
* src/api/sync.php legacy directo.
* Contexto frontend de hotel/sesion para storage local.
* Namespace de IndexedDB por storage_scope.
* Limpieza de storage offline en logout/cambio de scope.
* Proteccion temporal de /api/sync moderno.

## 3. Que se logro

* El service worker ya no cachea HTML privado/dinamico.
* API/AJAX queda network-only.
* Los endpoints modernos principales ya respetan hotel_id o quedan protegidos.
* Los endpoints legacy directos de lectura quedaron bloqueados con HTTP 410.
* src/api/sync.php quedo bloqueado con HTTP 410.
* IndexedDB dejo de usar una DB global fija.
* IndexedDB ahora usa patron loscedros-db-${storage_scope}.
* pwa.js y offline-data.js usan el mismo nombre scoped.
* Storage offline se limpia en logout/cambio de storage_scope.
* /api/sync moderno quedo bloqueado temporalmente con HTTP 423.
* /api/sync moderno preserva operaciones pendientes y no procesa operaciones inseguras.
* No se confia en hotel_id enviado por cliente.
* La autorizacion real sigue del lado servidor.

## 4. Que quedo pendiente para futuro

* Reactivar Sync real solo cuando Sync.php y Sync::procesarLote() exijan hotel_id derivado del servidor.
* Scopear operaciones offline profundas si se decide reactivar Sync:
  * crear reservacion
  * cambiar estado habitacion
  * check-in
  * check-out
  * pago caja
  * gasto caja
* Decidir si operaciones_sync requiere migracion con hotel_id.
* Revisar alertas_inventario/productos legacy si se requieren como fuente persistida.
* Pruebas reales con Docker/servidor/navegador cuando el entorno este disponible.

## 5. Que NO se toco

* WHITE LABEL / BRANDING MULTI-HOTEL.
* Logos por hotel.
* Colores por hotel.
* Manifest dinamico por hotel.
* Iconos PWA por hotel.
* Temas visuales por cliente.
* Migraciones/schema.
* Cambios de datos.
* Base de datos manualmente.

## 6. Validaciones generales

* php -l en PHP tocados durante las fases.
* node --check en JS tocados durante las fases.
* git diff --check.
* verificar_estado.php y preflight_hotel_id.php quedaron bloqueados en varias fases por Docker Desktop/engine no disponible, no por error del codigo.
* GET/POST locales quedaron bloqueados cuando no hubo servidor activo en localhost:8080.
* No se ejecuto SQL manual.
* No se toco base de datos.
* Working tree limpio al cierre de cada fase funcional/documental.

## 7. Riesgos pendientes

* Sync real sigue desactivado temporalmente.
* Operaciones offline pendientes no se procesan mientras /api/sync este bloqueado.
* Reactivacion de Sync debe hacerse en una fase futura con pruebas controladas.
* White Label debe abrirse en fase separada, sin mezclar con seguridad.

## 8. Veredicto

El bloque PWA/Sync/APIs queda cerrado de forma segura para esta etapa porque:

* los puntos de lectura/API quedaron scoped o protegidos;
* los endpoints legacy quedaron bloqueados;
* la cache privada quedo restringida;
* IndexedDB quedo separado por contexto local;
* /api/sync quedo desactivado temporalmente para evitar escrituras inseguras.

## 9. Siguiente fase recomendada

Abrir una fase nueva e independiente:

WHITE LABEL / BRANDING MULTI-HOTEL

Pero solo despues de commitear este cierre general y verificar working tree limpio.
