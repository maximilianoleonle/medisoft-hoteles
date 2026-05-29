# RESERVACIONES 1-F-F-G-C-C-B-CIERRE: APIs de ReservacionController scoped por hotel_id

## 1. Objetivo

Documentar el cierre tecnico del scope por hotel_id en los endpoints API de ReservacionController.

## 2. Que se logro

* habitacionesApiAction() ahora normaliza $id a entero.
* Se reemplazo Reservacion::find($id) por Reservacion::obtenerPorId($id).
* Se usa hotelIdActual().
* Se valida que la reservacion exista.
* Se valida que la reservacion pertenezca al hotel actual.
* Si la reservacion no existe o no pertenece al hotel actual, responde JSON con error controlado.
* Se conserva getHabitaciones($id), que ya esta scoped.
* Se conserva la estructura JSON original.
* verificarCheckInAction() no se modifico porque ya delega a Reservacion::verificarEstadoCheckIn($id), que filtra id + hotel_id.

## 3. Endpoint cerrado

* /api/reservaciones/{id}/habitaciones

## 4. Archivo modificado

* src/app/controllers/ReservacionController.php

## 5. Que NO se toco

* ApiController.php.
* Sync.php.
* src/api/*.php.
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

* php -l src/app/controllers/ReservacionController.php: PASS.
* git diff --check: PASS.
* verificar_estado.php: bloqueado por Docker Desktop/engine no disponible.
* preflight_hotel_id.php: bloqueado por Docker Desktop/engine no disponible.
* GET local: bloqueado porque no hay servidor activo en localhost:8080.
* Confirmacion de que no se ejecuto SQL manual.
* Confirmacion de que no se toco base de datos.
* Confirmacion de que no se tocaron Sync, IndexedDB/offline data, src/api/*.php ni White Label.

## 7. Riesgos pendientes

* Dashboard APIs siguen pendientes.
* Inventario APIs siguen pendientes.
* src/api/*.php legacy/directos siguen pendientes.
* Sync.php sigue pendiente.
* IndexedDB/offline data sigue pendiente.
* WHITE LABEL / BRANDING MULTI-HOTEL sigue fuera de alcance.

## 8. Siguiente fase recomendada

RESERVACIONES 1-F-F-G-C-C-C-A: auditoria especifica de dashboard APIs y rutas declaradas sin metodo encontrado, sin implementacion directa.
