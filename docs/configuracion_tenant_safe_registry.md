# ConfiguracionHotelRegistry tenant-safe

## Objetivo

`ConfiguracionHotelRegistry` introduce una capa read-only para leer configuracion simple por hotel desde `hotel_configuracion`.

La microfase 2B no integra esta capa en vistas operativas sensibles. Su objetivo es dejar una API segura, tipada y limitada por whitelist para futuras integraciones controladas.

## Claves permitidas iniciales

| Clave | Tipo | Default | Comentario |
| --- | --- | --- | --- |
| `operacion.checkin_hora` | string | `15:00` | Hora base informativa para check-in. |
| `operacion.checkout_hora` | string | `12:00` | Hora base informativa para check-out. |
| `operacion.moneda` | string | `MXN` | Codigo de moneda operativa. |
| `contacto.telefono` | string | `` | Telefono publico del hotel. |
| `contacto.direccion` | string | `` | Direccion publica del hotel. |
| `documentos.mostrar_logo` | boolean | `true` | Permite mostrar logo en documentos futuros. |
| `pwa.nombre_app` | string | `Medisoft Hoteles` | Nombre visible sugerido para la app. |

No se permiten claves arbitrarias. Si una clave no esta en la whitelist, el registry devuelve el default recibido por el consumidor.

## API disponible

- `ConfiguracionHotelRegistry::get(string $key, $default = null, ?int $hotelId = null)`
- `ConfiguracionHotelRegistry::getInt(string $key, int $default = 0, ?int $hotelId = null): int`
- `ConfiguracionHotelRegistry::getBool(string $key, bool $default = false, ?int $hotelId = null): bool`
- `ConfiguracionHotelRegistry::getJson(string $key, array $default = [], ?int $hotelId = null): array`
- `ConfiguracionHotelRegistry::getMany(array $keys, ?int $hotelId = null): array`
- `ConfiguracionHotelRegistry::allForHotel(?int $hotelId = null): array`
- `ConfiguracionHotelRegistry::hasKey(string $key): bool`
- `ConfiguracionHotelRegistry::definition(string $key): ?array`

Wrappers agregados en `hotel_config.php`:

- `hotel_setting($key, $default = null)`
- `hotel_setting_bool($key, $default = false)`
- `hotel_setting_int($key, $default = 0)`
- `hotel_setting_json($key, $default = [])`

Los wrappers usan `ConfiguracionHotelRegistry` si la clase existe. Si no existe o ocurre un error, devuelven el default.

## Resolucion de hotel

La lectura intenta resolver `hotel_id` de forma compatible con el proyecto actual:

1. `hotel_config_resolve_hotel_id()` si existe.
2. `TenantContext::hotelId()` si existe.
3. `obtenerHotelIdActualCompat()` si existe.

Si no puede resolver un hotel valido, devuelve defaults seguros. No inserta, actualiza ni elimina datos.

## Fallback controlado

El orden de lectura es:

1. `hotel_configuracion` con `hotel_id`, `clave` y `activo = 1`.
2. Fallback legacy declarado solo para claves de bajo riesgo.
3. Default tipado definido en el registry o enviado por el consumidor.

Fallbacks legacy declarados:

- `contacto.direccion` puede leer `hotel.direccion`.
- `operacion.moneda` puede leer `hotel.moneda_codigo`.

No hay fallback legacy para caja, reservaciones, reportes, facturacion, permisos ni reglas financieras.

## Que NO cubre esta microfase

- No modifica `Configuracion.php` legacy.
- No modifica `ConfiguracionController.php`.
- No crea migraciones.
- No ejecuta SQL.
- No hace seeds.
- No integra vistas operativas.
- No toca caja.
- No toca reservaciones.
- No toca reportes.
- No toca facturacion.
- No toca habitaciones operativas.
- No toca PWA/offline/sync/service-worker/manifest/IndexedDB.
- No toca `src/public_html/js/app.js`.

## Archivos tocados

- `src/app/models/ConfiguracionHotelRegistry.php`
- `src/app/helpers/hotel_config.php`
- `docs/configuracion_tenant_safe_registry.md`

## Validaciones

Comandos usados o esperados para validar esta microfase:

```bash
docker compose exec app php -l app/models/ConfiguracionHotelRegistry.php
docker compose exec app php -l app/helpers/hotel_config.php
git -c core.autocrlf=false --no-pager diff --check
git -c core.autocrlf=false --no-pager diff -- src/public_html/js/app.js
git diff --cached --name-status
git status --short
```

## Riesgos

- Usar el registry para claves operativas profundas antes de aprobar diseno.
- Agregar claves sin tipo/default definido.
- Confiar en fallback legacy para reglas financieras o reservaciones.
- Resolver hotel incorrecto si una futura integracion se ejecuta sin contexto de tenant.
- Usar `pwa.nombre_app` sin sanitizacion en HTML o manifest futuro.

## Proximos pasos

1. Revisar y aprobar la whitelist inicial.
2. Definir si se poblaran claves nuevas en `hotel_configuracion` en una fase posterior.
3. Elegir una integracion segura, preferentemente documental o visual, antes de tocar modulos operativos.
4. Mantener caja, reservaciones, reportes, facturacion y PWA/offline fuera del alcance hasta autorizacion explicita.
