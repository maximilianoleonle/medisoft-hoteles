# SSWL-01A - Guard SaaS + Panel Medisoft read-only de hoteles

## Objetivo

Crear la primera base segura del Panel Medisoft interno para administracion SaaS en modo minimo y solo lectura.

Esta fase solo agrega un guard conservador y una ruta interna para listar hoteles existentes.

## Contexto

La auditoria previa confirmo que Medisoft Hoteles ya tiene una base multi-hotel parcial, pero el Panel SaaS todavia no existia. Tambien confirmo que el login actual no usa `hotel_usuarios`, que `TenantContext` no esta integrado automaticamente con login/sesion/consultas operativas y que los permisos actuales son hoteleros.

Por eso, esta fase no usa roles hoteleros ni permisos de `can()` para proteger el Panel SaaS.

## Pre-auditoria realizada

- Rama actual: `feature/saas-multihotel`.
- `git status --short` inicial: limpio.
- Tabla `hoteles`: existe en la base actual.
- Columnas reales de `hoteles`: `id`, `nombre`, `slug`, `codigo`, `razon_social`, `rfc`, `telefono`, `email`, `direccion`, `ciudad`, `estado`, `pais`, `zona_horaria`, `moneda_codigo`, `moneda_simbolo`, `activo`, `metadata`, `created_at`, `updated_at`, `deleted_at`.
- `usuarios.rol`: existe.
- Definicion real de `usuarios.rol`: `enum('gerente','administrador','recepcionista')`.
- Valores reales encontrados en `usuarios.rol`: `gerente`, `recepcionista`.
- `administrador`: posible por enum, aunque no aparecio en el conteo actual.
- `superadmin`: no existe como rol real en la DB actual; conteo `0` y el enum no lo permite.
- Usuario autenticado: se identifica con `$_SESSION['user_id']`.
- Datos de sesion guardados por `login()`: `user_id`, `login_time`, `last_activity`.
- `current_user()` carga y cachea `$_SESSION['user']` desde `usuarios`.
- No se encontro `hotel_id` guardado en la sesion principal de auth.
- `can()` esta definido en `src/app/helpers/auth.php` con permisos hardcodeados por rol hotelero.
- Rutas definidas en `src/config/routes.php`.
- Layout interno reutilizado: `View::renderTemplate()` con `views/layout/header.php` y `views/layout/footer.php`.
- Controladores administrativos existentes revisados: `UsuarioController` y `ConfiguracionController`; no se reutilizaron porque dependen de rol hotelero `gerente`.

Consultas read-only usadas en la pre-auditoria:

```sql
SHOW TABLES LIKE 'hoteles';
DESCRIBE hoteles;
SHOW COLUMNS FROM usuarios LIKE 'rol';
SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol ORDER BY rol;
SELECT COUNT(*) AS superadmin_total FROM usuarios WHERE rol = 'superadmin';
SELECT id, nombre, slug, codigo, activo, moneda_codigo, created_at FROM hoteles ORDER BY id LIMIT 20;
```

## Resultado GO/NO-GO

Resultado: GO.

Motivos:

- El arbol estaba limpio antes de iniciar.
- Existe tabla real `hoteles`.
- Se puede listar hoteles con `SELECT` sin modificar datos.
- Existe forma segura de identificar usuario autenticado mediante `is_authenticated()`, `user_id()`, `current_user()` y `user_role()`.
- El guard SaaS puede ser conservador y cerrado por defecto.
- El guard SaaS no usa `gerente`, `administrador`, `recepcionista` ni permisos hoteleros.
- No fue necesario tocar login, PWA, cache, APIs, Sync, TenantContext ni datos existentes.

## Archivos creados/modificados

Archivos modificados:

- `src/app/helpers/auth.php`
- `src/config/routes.php`

Archivos creados:

- `src/app/controllers/SaasAdminController.php`
- `src/app/models/Hotel.php`
- `src/app/views/admin/saas/hoteles.php`
- `docs/fase_sswl_01a_guard_saas_panel_hoteles_readonly.md`

## Ruta creada

```text
GET /admin/saas/hoteles
```

La ruta se define en `src/config/routes.php` y apunta a:

```php
['controller' => 'SaasAdmin', 'action' => 'hoteles']
```

## Proteccion de la ruta

Se agregaron dos helpers en `src/app/helpers/auth.php`:

- `isSaasAdmin()`
- `requireSaasAdmin()`

La regla de acceso es exacta:

```php
user_role() === 'superadmin'
```

`SaasAdminController::before()` llama a `requireSaasAdmin()` antes de ejecutar la accion.

## Rol permitido

- `superadmin`

Nota: en la DB actual no existe ni se admite todavia `superadmin` en el enum de `usuarios.rol`, por lo que el acceso queda cerrado por defecto para todos los usuarios actuales.

## Roles no permitidos

- `gerente`
- `administrador`
- `recepcionista`
- cualquier otro rol distinto de `superadmin`

## Consulta read-only para listar hoteles

El modelo `Hotel::listarParaSaasAdmin()` usa solo:

```sql
SELECT id, nombre, slug, codigo, activo, moneda_codigo, created_at
FROM hoteles
ORDER BY id ASC
```

No hay `INSERT`, `UPDATE`, `DELETE`, `ALTER`, migraciones ni cambios de datos.

## Vista read-only

La vista `src/app/views/admin/saas/hoteles.php` muestra columnas reales disponibles:

- `id`
- `nombre`
- `slug`
- `codigo`
- `activo`
- `moneda_codigo`
- `created_at`

No incluye botones, formularios, acciones masivas, crear, editar, eliminar, upload ni cambios de estado.

## Fuera de alcance

- Crear hoteles.
- Editar hoteles.
- Eliminar hoteles.
- Branding.
- Modulos por hotel.
- Login dinamico.
- PWA.
- Manifest dinamico.
- Service worker.
- IndexedDB/offline/cache.
- Endpoints legacy `src/api`.
- `/api/sync`.
- Cambios en `hotel_id`.
- Refactor de seguridad multi-hotel.
- Migraciones o cambios de datos.

## Validaciones ejecutadas

`php -l`:

```text
No syntax errors detected in /var/www/html/app/helpers/auth.php
No syntax errors detected in /var/www/html/app/models/Hotel.php
No syntax errors detected in /var/www/html/app/controllers/SaasAdminController.php
No syntax errors detected in /var/www/html/app/views/admin/saas/hoteles.php
No syntax errors detected in /var/www/html/config/routes.php
```

Ruta sin autenticacion:

```text
GET /admin/saas/hoteles -> HTTP/1.1 303 See Other
Location: http://localhost:8080/login
```

Validacion de roles:

- `recepcionista`: bloqueado por `user_role() === 'superadmin'`.
- `administrador`: bloqueado por `user_role() === 'superadmin'`.
- `gerente`: bloqueado por `user_role() === 'superadmin'`.
- `superadmin`: permitido por codigo si existe en una fase futura.

Validacion positiva runtime de `superadmin`: no ejecutada porque la DB actual no tiene ni admite ese rol sin migracion o cambio de datos.

Validacion de alcance:

- No se modifico `AuthController.php`.
- No se modifico `login.php`.
- No se modifico `manifest.json`.
- No se modifico `service-worker.js`.
- No se modifico `pwa.js`.
- No se modifico `offline.html`.
- No se modifico IndexedDB/offline/cache.
- No se modificaron APIs.
- No se modifico `/api/sync`.
- No se modifico `TenantContext.php`.
- No se modifico `obtenerHotelIdActualCompat()`.
- No se modificaron datos de la base.

## Resultado de git diff --check

Ejecutado correctamente sin errores de whitespace.

Salida observada:

```text
warning: in the working copy of 'src/app/helpers/auth.php', LF will be replaced by CRLF the next time Git touches it
warning: in the working copy of 'src/config/routes.php', LF will be replaced by CRLF the next time Git touches it
```

## Resultado de git status --short

Resultado pre-commit:

```text
 M src/app/helpers/auth.php
 M src/config/routes.php
?? docs/fase_sswl_01a_guard_saas_panel_hoteles_readonly.md
?? src/app/controllers/SaasAdminController.php
?? src/app/models/Hotel.php
?? src/app/views/admin/
```

## Riesgos pendientes

- La DB actual no admite `superadmin`; una fase futura debe definir el modelo real de roles globales sin mezclarlo con roles hoteleros.
- El Panel SaaS no tiene enlace en sidebar para evitar tocar layout en esta fase.
- El layout reutilizado todavia arrastra hardcodes visuales de Los Cedros, ya detectados en auditoria previa.
- El Panel SaaS todavia no tiene auditoria propia ni logs de acceso.
- Futuras rutas SaaS deben mantener separacion estricta entre rol global y roles por hotel.

## Siguiente fase recomendada

SSWL-01B deberia definir el modelo de identidad SaaS global:

- decidir si `superadmin` vive en `usuarios.rol`, en una tabla separada, o en una relacion global;
- preparar migracion revisada para permitir usuarios internos Medisoft sin mezclar permisos hoteleros;
- agregar pruebas de acceso para rol global;
- mantener el Panel SaaS sin creacion/edicion de hoteles hasta que el modelo quede claro.
