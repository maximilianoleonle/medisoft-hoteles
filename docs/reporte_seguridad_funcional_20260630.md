# Reporte de seguridad funcional - Medisoft Hoteles - 2026-06-30

Rama objetivo: `feature/saas-multihotel`
Ambiente probado: `http://localhost:8080`
Modo: pruebas seguras, sin fuzzing agresivo y sin modificar flujos financieros.

## Resumen ejecutivo

Se ejecuto una primera bateria de seguridad funcional sobre autenticacion, acceso sin sesion, aislamiento multi-hotel, IDOR, CSRF, documentos privados, cabeceras/cookies, rutas debug y revision estatica de puntos sensibles.

Resultado:

- `19/19` pruebas HTTP de acceso/IDOR/storage pasaron.
- `3/3` pruebas CSRF contra Caja pasaron sin crear movimientos.
- Rutas protegidas sin sesion redirigen a login.
- Sesion de Maximiliano no expuso datos de Los Cedros en reservaciones, huespedes, documentos, facturacion, CxC/CxP ni APIs revisadas.
- Storage privado de documentos no fue accesible directo por URL.
- Cabeceras base presentes: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Cookies con `HttpOnly` y `SameSite=Lax`; `Secure` y HSTS quedan condicionados a HTTPS.
- No hubo errores PHP/SQL nuevos en logs durante la bateria.

Hallazgos abiertos:

| ID | Severidad | Estado | Hallazgo |
|---|---|---|---|
| SEC-001 | P1 | CORREGIDO (2026-07-02) | Rate limit de login depende de `$_SESSION`, por lo que puede reiniciarse con una cookie/sesion nueva. |
| SEC-002 | P2 | CORREGIDO (2026-07-02, report-only) | Content Security Policy global esta comentada; falta CSP activa o al menos modo report-only. |
| SEC-003 | P3 | OBSERVACION | Metodos debug/correccion existen en controladores aunque no estan ruteados; conviene removerlos o blindarlos para produccion. |
| SEC-004 | P3 | OBSERVACION | `medisoft_last_hotel_slug` se emite repetidamente en dashboard; no fue fuga, pero aumenta ruido de headers. |

## Matriz ejecutada

| Caso | Resultado | Evidencia |
|---|---|---|
| Rutas protegidas sin sesion | PASS | `/dashboard`, `/caja`, `/documentos/22`, `/facturacion/ver/232`, `/api/reservaciones/51/resumen-pagos`, `/api/reservaciones/51/habitaciones` respondieron `303` sin body operativo. |
| IDOR multi-hotel en vistas | PASS | Sesion Maximiliano contra IDs Los Cedros: reservacion `#51`, huesped `#1065`, docs `#18`, factura `#232`, CxC `#3`, CxP `#3`; sin patrones de fuga. |
| IDOR multi-hotel en APIs | PASS | `/api/reservaciones/51/habitaciones` `404`, `/api/reservaciones/51/resumen-pagos` `403`, `/api/reservaciones/verificar-checkin/51` `200` sin datos de habitacion/huesped. |
| Descarga documento cross-tenant | PASS | `/documentos/18/descargar?preview=1` con sesion Maximiliano respondio `303`, no imagen. |
| Acceso directo a storage | PASS | `/storage/documentos/hotel_1/...jpg` y `/storage/documentos/hotel_4/...png` respondieron `303`, no archivo. |
| CSRF Caja sin token | PASS | `POST /caja/ingreso` sin `csrf_token`: `303`, conteo `movimientos_caja` QA `0 -> 0`. |
| CSRF Caja token invalido campo | PASS | `POST /caja/ingreso` con `csrf_token=bad-token`: `303`, conteo `0 -> 0`. |
| CSRF Caja token invalido header | PASS | `POST /caja/ingreso` con `X-CSRF-TOKEN: bad-token`: `303`, conteo `0 -> 0`. |
| `/api/sync` sin sesion | PASS | `POST /api/sync` sin cookie respondio `303`, sin body. |
| Rutas debug/setup/correccion | PASS | Sin sesion: `303`; con sesion: `404` para `/inventario/debug-pdf`, `/inventario/debug-movimientos-date`, `/huespedes/debug-movimientos`, `/correccion`, `/setup/directories`. |

## Hallazgos

### SEC-001 - Rate limit de login reiniciable por sesion nueva

Severidad: P1
Estado: **CORREGIDO** (2026-07-02). Nueva tabla `login_intentos` (migracion
`20260702_002`) + servicio `LoginRateLimiter`: contador persistente por
`ip + usuario (+ hotel_slug)` con 5 intentos / bloqueo 15 min (misma UX),
mas tope global de 20 intentos por IP contra credential stuffing. Ventana
de 15 min, poda automatica a 24 h y fail-open si falta la tabla (el login
nunca se cae por el rate limiter). Verificado E2E con curl: 5 fallos →
bloqueo; sesion/cookie NUEVA → sigue bloqueado; otro usuario misma IP →
contador propio.
Modulo: Auth
Archivos:

- `src/app/controllers/AuthController.php:113`
- `src/app/controllers/AuthController.php:160`
- `src/app/controllers/AuthController.php:189`
- `src/app/controllers/AuthController.php:247`

Evidencia:

El contador de intentos y bloqueo se construye con IP/hotel, pero se guarda en `$_SESSION`:

- Scoped login: `login_intentos_...` y `login_bloqueado_...` en `$_SESSION`.
- Login general: mismo patron en `$_SESSION`.

Impacto:

Un atacante puede iniciar intentos con una cookie/sesion nueva y evitar acumular el contador anterior. Reduce la efectividad contra fuerza bruta y credential stuffing.

Recomendacion:

Persistir intentos fallidos en DB, Redis/cache o tabla dedicada con TTL, usando combinaciones `ip + usuario + hotel_slug` y, opcionalmente, limites globales por IP/subred. Mantener auditoria de login fallido separada del bloqueo operativo.

### SEC-002 - CSP global no activa

Severidad: P2
Estado: **CORREGIDO en modo report-only** (2026-07-02).
`Content-Security-Policy-Report-Only` activa en `src/public_html/.htaccess`
con el inventario real de origenes de las vistas (jsdelivr, cdnjs, tailwind
CDN, datatables, jquery, google fonts) + inline/eval que la app requiere hoy.
Verificado en navegador: 0 violaciones en dashboard, caja y reservaciones.
Siguiente paso: vigilar consola unas semanas y promover el header a
`Content-Security-Policy` (enforcement) sin cambiar la politica.
Modulo: Headers / hardening navegador
Archivo:

- `src/public_html/.htaccess:104`

Evidencia:

La app emite `X-Frame-Options`, `nosniff`, `Referrer-Policy` y `Permissions-Policy`, pero `Content-Security-Policy` esta comentada. En la prueba de `/dashboard` no se recibio header CSP.

Impacto:

Si aparece una inyeccion XSS en alguna vista, la ausencia de CSP deja menos defensa en profundidad.

Recomendacion:

Agregar primero `Content-Security-Policy-Report-Only` y revisar violaciones reales. Luego activar una politica compatible con el stack actual. Como punto inicial conservador: `default-src 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: https:; font-src 'self' data: https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline'`.

### SEC-003 - Metodos debug presentes en controladores

Severidad: P3
Estado: OBSERVACION
Archivos:

- `src/app/controllers/InventarioController.php:165`
- `src/app/controllers/HuespedController.php:608`
- `src/config/routes.php:475`
- `src/config/routes.php:576`

Evidencia:

Hay metodos debug/correccion en controladores y rutas comentadas. La prueba HTTP confirmo que no estan expuestos actualmente: con sesion responden `404`.

Impacto:

No hay fuga activa en la bateria, pero conservar metodos debug cerca de controladores productivos aumenta el riesgo de reactivacion accidental.

Recomendacion:

Eliminar metodos debug obsoletos o moverlos a herramientas CLI fuera del router web. Si se conservan, protegerlos con flag de entorno y permiso explicito.

### SEC-004 - Cookie de contexto emitida repetidamente

Severidad: P3
Estado: OBSERVACION
Archivo:

- `src/app/helpers/auth.php:113`

Evidencia:

`/dashboard` emitio multiples `Set-Cookie: medisoft_last_hotel_slug=los-cedros` en la misma respuesta. La cookie incluye `HttpOnly` y `SameSite=Lax`, y no expuso datos sensibles.

Impacto:

No se detecto fuga ni bypass. Es ruido de headers y puede crecer innecesariamente el tamano de respuesta.

Recomendacion:

Evitar reemitir la cookie si el valor actual ya coincide con el slug activo durante la misma request.

## Controles validados

- `session.use_strict_mode=1` activo en `src/public_html/index.php`.
- Cookies de sesion con `HttpOnly` y `SameSite=Lax`.
- `session_regenerate_id(true)` en login.
- `password_verify()` para validar credenciales.
- Remember token se guarda hasheado en DB.
- Documentos se almacenan bajo `STORAGE_PATH`, no bajo public, con `.htaccess` deny y descarga por controlador.
- Uploads/documentos usan allowlist MIME y nombres aleatorios.
- Debug/setup/correccion no estan ruteados actualmente.

## Siguiente orden recomendado

1. Corregir SEC-001: rate limit persistente para login.
2. Activar CSP en modo report-only y pasar a enforce cuando no rompa vistas.
3. Limpiar o aislar metodos debug/correccion.
4. Reducir repeticion de `Set-Cookie` de contexto hotelero.

