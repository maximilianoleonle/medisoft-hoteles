# Fase Auditoria Notificaciones URLs 2026-06-24

Objetivo: validar que las URLs persistidas en `notificaciones.url` sigan apuntando a rutas operativas y que la vista no exponga destinos inseguros directamente.

## Alcance

- Tabla revisada: `notificaciones`.
- Campo revisado: `url`.
- Vista revisada: `src/app/views/notificaciones/index.php`.
- Controlador revisado: `src/app/controllers/NotificacionController.php`.
- Modelo revisado: `src/app/models/Notificacion.php`.
- Servicios revisados:
  - `src/app/services/NotificacionService.php`.
  - `src/app/services/NotificacionReglasService.php`.
- No se tocaron PWA/offline, rutas, modelos, migraciones ni datos.

## Proteccion actual de la vista

La vista de notificaciones no navega directamente a la URL persistida. Cada fila con destino usa:

- `notificaciones/{id}/abrir`.

Ese endpoint marca la notificacion como leida y despues redirige al valor persistido o a `notificaciones` si no hay destino.

## Normalizacion al crear

`Notificacion::crearEvento()` normaliza el campo `url` antes de guardar:

- Rechaza URLs vacias.
- Rechaza URLs absolutas con `://`.
- Rechaza rutas con `..`.
- Quita `/` inicial para guardar rutas relativas.
- Limita el destino a 255 caracteres.

## Generadores revisados

Se ubicaron los productores actuales de URLs de notificaciones:

- `NotificacionReglasService`: `reservaciones`, `facturacion?estatus=pendiente`, `habitaciones?estado=mantenimiento`, `habitaciones?estado=limpieza`, `caja/corte`, `inventario`, `reportes/gerencial-diario?fecha=...`.
- `NotificacionService::sincronizarReglaFacturasPendientes`: `facturacion?estatus=pendiente`.
- `HabitacionController::registrarNotificacionHabitacion`: `habitaciones/{id}`.
- `FacturacionController::registrarNotificacionFacturacion`: `facturacion/ver/{id}`.
- `CajaController::registrarNotificacionCorteCerrado`: `caja/corte/{id}`.

## Validacion contra rutas activas

Se valido cada URL guardada contra las rutas `GET` activas del router, separando el query string para casos como `?fecha=...` o `?estatus=...`.

Resultado:

- URLs persistidas revisadas: 121.
- URLs validas: 121.
- URLs invalidas: 0.
- Rutas unicas validas: 15.

Rutas persistidas encontradas:

- `/caja/corte`.
- `/caja/corte/{id}`.
- `/facturacion`.
- `/facturacion/ver/{id}`.
- `/habitaciones`.
- `/inventario`.
- `/reportes/gerencial-diario`.
- `/reservaciones`.

## Resultado operativo

No se encontraron notificaciones actuales que redirijan a rutas inexistentes.

No se aplico correccion de datos porque no habia URLs rotas, absolutas o con traversal.

## Correccion preventiva aplicada

`NotificacionReglasService::actualizarReglaActiva()` actualizaba `notificaciones.url` directamente con el valor recibido. Aunque hoy ese valor viene de generadores internos revisados y validos, se agrego una normalizacion equivalente antes de actualizar una notificacion automatica existente.

La proteccion preventiva aplicada:

- Convierte URLs vacias en `null`.
- Rechaza URLs absolutas con `://`.
- Rechaza rutas con `..`.
- Quita `/` inicial para mantener destinos relativos.
- Limita el destino a 255 caracteres.
