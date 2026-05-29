# Reservaciones 1-F-F-G-A - Auditoria PWA/offline, Sync y APIs globales

## 1. Objetivo

Auditar PWA/offline, service worker, manifest, sincronizacion y APIs globales para detectar posibles fugas multi-hotel, datos cacheados sin hotel_id, endpoints sin scope y rutas offline que puedan mezclar datos entre hoteles.

## 2. Hallazgo principal

* El riesgo principal no esta en branding ni assets estaticos.
* El riesgo principal esta en APIs y Sync.
* ApiController.php, Sync.php y endpoints PHP directos bajo src/api no muestran validacion directa por hotel_id.
* La PWA guarda snapshots offline en IndexedDB loscedros-db sin namespace por hotel.
* El service worker no cachea respuestas API directamente, pero si guarda paginas HTML dinamicas en caches globales.
* El manifest actual es global Los Cedros, pero White Label queda fuera de esta auditoria.

## 3. Archivos auditados

* src/public_html/service-worker.js
* src/public_html/manifest.json
* src/public_html/offline.html
* src/public_html/js/pwa.js
* src/public_html/js/offline-data.js
* src/public_html/js/reservaciones-offline.js
* src/public_html/js/habitaciones-offline.js
* src/public_html/js/caja-offline.js
* src/public_html/js/loading-screen.js
* src/public_html/css/loading-screen.css
* src/public_html/css/pwa.css
* src/config/routes.php
* src/app/controllers/ApiController.php
* src/app/models/Sync.php
* src/api/*.php

## 4. Riesgos altos

* /api/sync y Sync.php escriben reservaciones, habitaciones y caja sin hotel_id visible.
* Snapshots offline de reservaciones, habitaciones y busqueda global sin namespace por hotel.
* Endpoints de reservaciones, habitaciones, disponibilidad, busqueda y caja sin filtro visible por hotel_id.
* IndexedDB loscedros-db no esta separado por hotel.

## 5. Riesgos medios

* Service worker cachea paginas HTML dinamicas como dashboard, reservaciones, caja, facturacion y reportes en caches globales.
* Manifest/config PWA globales, aunque eso pertenece a White Label y no es fuga de datos por si solo.
* /api/dashboard/stats requiere auditoria de scope.
* APIs de inventario y algunas rutas de reservaciones requieren confirmar scope por ID.

## 6. Riesgos bajos

* Assets estaticos.
* Loading screen.
* CSS.
* offline.html como render estatico.

## 7. Endpoints/rutas detectadas

* /api/buscar
* /api/reservaciones/hoy
* /api/habitaciones/todas-con-ocupacion
* /api/sync
* /api/huespedes/search
* /api/habitaciones/verificar-disponibilidad
* /api/habitaciones/calcular-precio
* /api/habitaciones/disponibles
* /api/dashboard/stats
* /api/inventario/*
* /api/reservaciones/{id}/habitaciones
* /api/reservaciones/verificar-checkin/{id}
* /api/dashboard/ocupacion
* /api/dashboard/movimientos-recientes
* /api/dashboard/alertas

## 8. Validaciones necesarias

* reservaciones.hotel_id
* habitaciones.hotel_id
* caja/cortes/movimientos_caja.hotel_id
* solicitudes_factura.hotel_id
* reportes por hotel_id
* usuarios como auxiliares, no como fuente de scope

## 9. Que dejar fuera

* WHITE LABEL / BRANDING MULTI-HOTEL.
* logo por hotel.
* colores por hotel.
* manifest dinamico por hotel.
* iconos PWA por hotel.
* temas visuales.
* migraciones/schema.
* cambios de datos.
* implementacion funcional.

## 10. Fases propuestas

* 1-F-F-G-B: service worker/cache/offline seguro por hotel o documentacion de exclusiones.
* 1-F-F-G-C: endpoints/API scoped por hotel_id.
* 1-F-F-G-D: Sync/offline data scoped por hotel_id, incluyendo IndexedDB, cola offline, /api/sync y Sync.php.
* 1-F-F-G-E: cierre general de PWA/Sync/APIs.
* Despues de eso, si todo queda seguro, abrir WHITE LABEL / BRANDING MULTI-HOTEL.

## 11. Pruebas necesarias futuras

* php -l en PHP tocados.
* git diff --check.
* verificar_estado.php PASS.
* preflight_hotel_id.php PASS.
* pruebas GET de endpoints sin sesion y con sesion por hotel.
* revisar cache del service worker.
* confirmar que no se cachean respuestas privadas multi-hotel.
* confirmar 0 nuevos hotel_id NULL.
* confirmar que no se toco branding/white label todavia.
