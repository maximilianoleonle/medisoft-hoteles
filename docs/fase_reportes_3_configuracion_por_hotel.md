# Fase reportes 3 - Configuracion por hotel

## Alcance implementado

Se agregaron opciones por hotel para controlar el comportamiento de links seguros de reportes PDF desde la pantalla de configuracion.

No se tocaron WhatsApp, PWA, service worker, sync, caja, cortes, movimientos ni calculos de reportes.

La seccion editable de configuracion ahora agrupa los campos por area. El grupo `Reportes seguros` queda destacado para que el hotel encuentre rapido las opciones de links PDF.

## Claves nuevas

### `reportes.links_publicos_activos`

- Tipo: boolean.
- Default: activo.
- Uso actual: `ReporteLinkController` bloquea descargas publicas si esta opcion esta apagada para el hotel del reporte.

### `reportes.link_expiracion_dias`

- Tipo: integer.
- Default: `7`.
- Rango: 1 a 90 dias.
- Uso actual: `ReporteLink::crearDesdeArchivo()` usa este valor como vigencia predeterminada de nuevos links.

## Validaciones

- La normalizacion de configuracion editable ahora valida enteros.
- El campo numerico en UI expone `min`, `max` y `step`.
- La validacion final sigue en backend para evitar valores fuera de rango.

## Verificacion local

- `php -l` correcto en:
  - `src/app/helpers/hotel_config.php`
  - `src/app/views/configuracion/index.php`
  - `src/app/models/ReporteLink.php`
  - `src/app/controllers/ReporteLinkController.php`
- Pantalla `http://localhost:8080/configuracion` muestra:
  - `Permitir links seguros publicos`
  - `Dias de vigencia del link`
- La pantalla agrupa 6 areas editables y conserva los mismos nombres de formulario `hotel_config[...]`.

## Siguiente fase recomendada

Fase 4: envio por correo.

- Agregar configuracion SMTP o proveedor de correo por hotel/sistema.
- Configurar destinatarios o correo operativo.
- Enviar correo con link seguro, no PDF adjunto.
- Registrar envio en historial del reporte.
