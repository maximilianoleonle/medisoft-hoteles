# Fase Auditoria JS Dinamico Rutas 2026-06-24

Objetivo: revisar acciones JavaScript con IDs dinamicos, `fetch`, `$.ajax`, `form.action` y prefijos `url(".../")` contra rutas activas.

## Alcance

- Vistas PHP bajo `src/app/views`.
- JS externo no protegido bajo `src/public_html/js`.
- No se modificaron archivos PWA/offline protegidos.
- No se tocaron `pwa.js`, `offline-data.js`, `reservaciones-offline.js`, `caja-offline.js` ni `habitaciones-offline.js`.

## Prefijos dinamicos validados

Se validaron 17 prefijos dinamicos contra rutas activas. Entre los principales:

- `reservaciones/ver/{id}`.
- `api/reservaciones/{id}/habitaciones`.
- `reservaciones/check-out-rapido/{id}`.
- `reservaciones/check-out-parcial/{id}`.
- `habitaciones/{id}`.
- `habitaciones/{id}/liberar`.
- `inventario/eliminar/{id}`.
- `reservaciones/check-in/{id}`.
- `api/huespedes/vehiculos/{id}`.
- `huespedes/{id}`.
- `usuarios/{id}/update`.

## Correccion aplicada

### `src/public_html/js/reservaciones/crear.js`

- Antes: `/api/huespedes/search?term=...`.
- Despues: `/api/huespedes/search?q=...`.
- Motivo: `ApiController::buscarHuespedesAction()` lee el parametro `q`.
- Estado: el archivo externo no se detecto cargado desde las vistas actuales, pero queda corregido si se reactiva.

## Resultado

No quedaron botones o acciones JavaScript dinamicas activas apuntando a rutas inexistentes.

## Pendiente consciente

Las rutas dinamicas construidas a partir de datos de notificaciones (`data-notif-url`) dependen de datos persistidos. Deben validarse por origen de datos en una fase separada si se detectan notificaciones antiguas con URLs obsoletas.

