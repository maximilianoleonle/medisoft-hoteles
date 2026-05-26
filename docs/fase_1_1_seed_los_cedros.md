# Fase 1.1 - Seed Los Cedros

## Que se ejecuto

Se ejecuta la migracion base SaaS:

- `migrations/20260526_001_crear_base_saas_multihotel.sql`

Despues se ejecuta el seed inicial:

- `migrations/20260526_002_seed_hotel_los_cedros.sql`

Ambos scripts son aditivos. No agregan `hotel_id` a tablas operativas y no
integran `TenantContext` al flujo real del sistema.

## Hotel creado

Se crea o actualiza el hotel inicial:

- Nombre: `Los Cedros`
- Slug: `los-cedros`
- Codigo: `LOS_CEDROS`
- Direccion: `Santa Catarina Juquila, Oaxaca`
- Ciudad: `Santa Catarina Juquila`
- Estado: `Oaxaca`
- Pais: `Mexico`
- Zona horaria: `America/Mexico_City`
- Moneda: `MXN`

## Configuracion inicial creada

Configuracion base:

- `hotel.nombre = Los Cedros`
- `hotel.direccion = Santa Catarina Juquila, Oaxaca`
- `hotel.zona_horaria = America/Mexico_City`
- `hotel.moneda_codigo = MXN`
- `hotel.moneda_simbolo = $`
- `hotel.habitaciones_actuales = 66`
- `sistema.modo_compatibilidad_mono_hotel = true`

Feature flags:

- `feature.multi_hotel = false`
- `feature.selector_hotel = false`
- `feature.audit_logs = false`
- `feature.hotel_configuracion = true`
- `feature.saas_billing = false`
- `feature.bitacora_inteligente = false`
- `feature.whatsapp_ejecutivo = false`
- `feature.ia_ejecutiva = false`

## Usuarios asociados

El seed asocia al hotel `Los Cedros` todos los usuarios activos existentes en
`usuarios` con roles compatibles:

- `gerente`
- `administrador`
- `recepcionista`

Para la base local actual, los usuarios detectados fueron:

- `admin` como `gerente`
- `Rafael` como `gerente`
- `Maximiliano` como `recepcionista`
- `LETICIA` como `recepcionista`
- `JOSE` como `recepcionista`
- `Adriana` como `recepcionista`
- `Monica` como `gerente`
- `Wilberto` como `recepcionista`

No se modifica la tabla `usuarios`; solo se crean relaciones en
`hotel_usuarios`.

## Que NO se toco

- Caja.
- Reservaciones.
- Check-in/check-out.
- Login.
- Sesiones.
- PWA/offline.
- Rutas.
- Dashboard.
- Consultas operativas.
- Tablas operativas existentes.

## Rollback

Para revertir solo los datos creados por el seed:

```sql
START TRANSACTION;

DELETE hu
FROM hotel_usuarios hu
JOIN hoteles h ON h.id = hu.hotel_id
WHERE h.slug = 'los-cedros';

DELETE hc
FROM hotel_configuracion hc
JOIN hoteles h ON h.id = hc.hotel_id
WHERE h.slug = 'los-cedros';

DELETE FROM hoteles
WHERE slug = 'los-cedros';

DELETE FROM migrations
WHERE nombre = '20260526_002_seed_hotel_los_cedros.sql';

COMMIT;
```

No tocar `usuarios` ni tablas operativas.

Para revertir tambien la estructura base SaaS en un entorno local sin datos
reales:

```sql
DROP TABLE IF EXISTS logs_auditoria;
DROP TABLE IF EXISTS hotel_usuarios;
DROP TABLE IF EXISTS hotel_configuracion;
DROP TABLE IF EXISTS hoteles;
DROP TABLE IF EXISTS migrations;
```
