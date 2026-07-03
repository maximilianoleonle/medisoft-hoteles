# Plan: Motor de Reservas Público White-Label (bloque `motor_reservas`)

**Objetivo:** que cada hotel tenga su página pública de reservas (`/h/{slug}/reservar`) con
disponibilidad real, pago de anticipo online y reservación confirmada — sin comisiones de OTA.
Bloque comercial sugerido: **$499/mes** (editable en /admin/saas/modulos).

**Regla rectora:** el dinero online NUNCA entra directo a Caja. Entra a un ledger propio
(`motor_pagos_online`) y recepción lo **concilia** a Caja vía `AnticipoService` cuando hay
corte abierto. Cero cambios a cálculos de caja (límite crítico de AGENTS.md).

---

## Fase 0 — Hechos verificados (APIs permitidas)

Verificado en código real el 2026-07-02. NO inventar métodos fuera de esta lista.

| API real | Ubicación | Uso en el motor |
|---|---|---|
| `Reservacion::crearConHabitaciones($data, $habitaciones, $cortesias = [])` | `src/app/models/Reservacion.php:1852` | Crear la reservación confirmada tras el pago |
| `Reservacion::verificarDisponibilidadMultiple($ids, $entrada, $salida, $excluir = null)` | `Reservacion.php:2171` | Re-verificación de disponibilidad (pública y dentro de la transacción post-pago) |
| `AnticipoService::registrar(int $hotelId, int $reservacionId, array $datos, ?int $usuarioId)` | `src/app/services/AnticipoService.php:83` | SOLO en conciliación manual (F5). **Exige corte de Caja abierto y usuario válido**; métodos: efectivo/tarjeta/transferencia; transaccional propio (no anidar transacciones) |
| Patrón público hotel-aware: `PwaController::manifestAction($slug)` → `normalizarSlug()` + `obtenerHotelActivoPorSlug()` | `src/app/controllers/PwaController.php:7` | COPIAR este patrón para resolver hotel por slug sin login |
| `ConfiguracionHotelRegistry::get/getInt/getBool/getJson($key, $default, $hotelId)` | `src/app/models/ConfiguracionHotelRegistry.php` | Leer config del motor por hotel (solo lectura) |
| `HotelBranding::resolverParaHotel($hotelId, $hotel)` | usado en `SaasAdminController::verHotelAction` | Colores/logo de la página pública (tokens `--brand-*`) |
| `IncrementoTarifa` (clase incremento/descuento) + cálculo en `ApiController::calcularPrecioAction` (`:1741`) | modelo + API interna | Recalcular precio SIEMPRE server-side (la API interna es autenticada: el motor expone la suya pública) |
| `LoginRateLimiter` | `src/app/services/LoginRateLimiter.php` | Patrón a copiar para rate-limit de endpoints públicos |
| `NotificacionService` / `PwaPushService` | services | Avisar "nueva reserva online" (respetando bloque `notificaciones`) |
| Regla de bloques (8 puntos) | `AGENTS.md` final | Registro en catálogo, gate, sidebar, precios sin hardcode |

**Restricción clave descubierta:** `AnticipoService` requiere corte abierto + `usuarioId` y
lanza excepción si hay transacción activa. Por eso el webhook NO llama AnticipoService.

**Anti-patrones globales:**
- ❌ Insertar en `movimientos_caja`/`reservacion_abonos` directamente desde el webhook.
- ❌ Crear la reservación ANTES de confirmar el pago.
- ❌ Confiar en el monto que manda el navegador: el server recalcula precio y anticipo.
- ❌ Exponer las APIs internas autenticadas `/api/*` al público; el motor tiene las suyas bajo `/h/{slug}/reservar/*`.
- ❌ Guardar secret keys de pasarela en git o en columnas legibles del panel.
- ❌ Olvidar `hotel_id` en cualquier query (multi-tenant).

---

## Fase 1 — Bloque comercial, config y tablas (migración `20260702_007_motor_reservas_base.sql`)

**Implementar:**
1. Registrar módulo `motor_reservas` en catálogo (copiar patrón de `20260702_006_modulos_tier3.sql`):
   clave `motor_reservas`, nombre "Motor de reservas online", categoria `canales`, precio 499.00,
   orden 118, icono `globe`, ruta_base `/motor-reservas`. **NO activarlo retroactivamente**
   (es funcionalidad nueva, opt-in; a diferencia de tiers anteriores).
2. Tabla `motor_pagos_online`:
   `id, hotel_id (FK hoteles), reservacion_id NULL (FK), proveedor VARCHAR(30), proveedor_pago_id VARCHAR(120),
   monto DECIMAL(10,2), moneda CHAR(3), estado ENUM('pendiente','pagado','conciliado','reembolsado','fallido','expirado'),
   payload_json JSON, huesped_email VARCHAR(120), abono_id NULL, conciliado_por NULL (FK usuarios), conciliado_at NULL,
   created_at, updated_at` + UNIQUE (proveedor, proveedor_pago_id) + índices hotel_id/estado.
3. Tabla `motor_holds` (bloqueo temporal anti-doble-venta mientras el huésped paga):
   `id, hotel_id, token CHAR(32) UNIQUE, habitacion_ids_json JSON, fecha_entrada DATE, fecha_salida DATE,
   pago_online_id NULL, expires_at DATETIME, created_at` + índice (hotel_id, expires_at).
4. Tabla `hotel_pasarela_credenciales`:
   `id, hotel_id UNIQUE, proveedor ENUM('stripe','mercadopago'), public_key VARCHAR(191),
   secret_key_encrypted TEXT, webhook_secret_encrypted TEXT, modo ENUM('test','live') DEFAULT 'test', activo, timestamps`.
   Cifrado con `openssl_encrypt` AES-256-GCM y llave en variable de entorno `MOTOR_PASARELA_KEY` (docker-compose env, NO en git).
5. Seeds de `hotel_configuracion` (defaults por hotel, editables): `motor_reservas_publico_activo` (0),
   `motor_anticipo_tipo` ('porcentaje'|'primera_noche'|'monto_fijo'), `motor_anticipo_valor` (30),
   `motor_min_noches` (1), `motor_max_noches` (30), `motor_anticipacion_max_dias` (180),
   `motor_politica_texto`, `motor_email_confirmacion_activo` (1). Seguir el patrón de keys existente en `hotel_configuracion`.

**Verificación:** migración aplicada a `medisoft_hoteles_import` (la DB del .env); `SELECT` de las 3 tablas;
módulo visible en /admin/saas/modulos con precio $499; ningún hotel lo tiene activo.

---

## Fase 2 — Backend público read-only (sin dinero todavía)

**Implementar `MotorReservasPublicoController`** (nuevo, sin auth) copiando la resolución
de tenant de `PwaController::manifestAction`:
- `GET /h/{slug}/reservar` → vista pública (F3). Guards en cadena, cada uno con página amable:
  hotel existe y activo → `hotel_has_module('motor_reservas', $hotelId)` → `ConfiguracionHotelRegistry::getBool('motor_reservas_publico_activo', false, $hotelId)`.
- `GET /h/{slug}/reservar/api/disponibilidad?entrada=Y-m-d&salida=Y-m-d&personas=N` → JSON:
  tipos de habitación con nombre/fotos (`HabitacionImagen`)/capacidad, unidades disponibles
  (via `Reservacion::verificarDisponibilidadMultiple` sobre habitaciones activas del hotel),
  precio por noche y total **calculado server-side** (base + `IncrementoTarifa` + reglas de clase),
  y `anticipo_requerido` según config (porcentaje/primera_noche/monto_fijo).
- Validaciones: fechas válidas, entrada >= hoy, noches entre min/max config, anticipación <= config.
- Rate-limit por IP copiando el patrón de `LoginRateLimiter` (tabla o mismo mecanismo, p.ej. 30 req/min).
- Rutas nuevas en `src/config/routes.php` junto a las `/h/{slug}/` existentes.

**Anti-patrones:** no usar sesión/TenantContext de usuario logueado; no listar números de
habitación reales al público (solo tipos y cantidad disponible); no dejar el endpoint sin rate-limit.

**Verificación:** `curl` a disponibilidad de un hotel demo devuelve JSON correcto; hotel con módulo
apagado responde página "motor no disponible" (no 500); fechas inválidas → 422 con mensaje; lint PHP.

---

## Fase 3 — Página pública white-label (mobile-first)

**Implementar** vista `src/app/views/motor/reservar.php` SIN layout interno (standalone, como el login por slug):
- Tokens `--brand-*` desde `HotelBranding::resolverParaHotel` (regla AGENTS.md: público del hotel = `--brand-*`, nunca `--ms-*`), logo, nombre visual.
- Paso 1: fechas + personas → llama API de disponibilidad (F2). Paso 2: elegir tipo de habitación
  (tarjetas con foto, precio/noche, total). Paso 3: datos del huésped (nombre, tel, email) +
  resumen: total de la estancia, **anticipo a pagar hoy**, saldo al llegar, política del hotel.
- Estados obligatorios: cargando (skeleton), sin disponibilidad (sugerir otras fechas), motor
  pausado, error de red. Targets táctiles ≥44px; una sola acción primaria por paso.
- SEO básico: title/meta con nombre del hotel; noindex si `modo=test`.

**Verificación:** render en móvil 375px y desktop; navegación completa de los 3 pasos con datos
demo; sin errores de consola; textos en español del dominio (nada de lorem).

---

## Fase 4 — Pago online y creación de la reservación

**Implementar `PasarelaPagoService`** (interfaz única) + 2 providers:
- `StripeProvider`: Checkout Session (`mode=payment`, `payment_intent_data.metadata` = hold token + hotel_id) — SDK oficial `stripe/stripe-php` vía composer (única dependencia nueva; confirmar que el proyecto acepta composer o vendorizar).
- `MercadoPagoProvider`: Preference de Checkout Pro con `external_reference` = hold token.
- Credenciales desde `hotel_pasarela_credenciales` (descifradas en memoria, nunca logueadas).

**Flujo (endpoints públicos bajo `/h/{slug}/reservar/`):**
1. `POST /h/{slug}/reservar/iniciar-pago` → valida datos + disponibilidad → crea `motor_holds`
   (expira en 20 min) → crea fila `motor_pagos_online` estado `pendiente` con monto recalculado
   server-side → crea sesión de pago en la pasarela → redirige al checkout de la pasarela.
2. `POST /h/{slug}/reservar/webhook/{proveedor}` → **verificar firma** (Stripe-Signature /
   x-signature de MP) con el webhook_secret del hotel; idempotente por UNIQUE (proveedor, proveedor_pago_id).
   Si pagado → TRANSACCIÓN: re-verificar disponibilidad del hold (`verificarDisponibilidadMultiple`)
   → buscar huésped por email/teléfono en el hotel o crearlo → `Reservacion::crearConHabitaciones`
   (estado inicial confirmada/pendiente según flujo actual del modelo; origen 'motor_online' en notas)
   → `motor_pagos_online.estado='pagado'` + reservacion_id → borrar hold → notificación push/centro
   ("Nueva reserva online") si el hotel tiene bloque `notificaciones`.
   Si al re-verificar YA NO hay disponibilidad (carrera): marcar para reembolso automático vía API
   de la pasarela + estado `reembolsado` + notificación urgente al hotel.
3. `GET /h/{slug}/reservar/confirmacion/{token}` → página de éxito con folio, fechas, anticipo
   pagado y saldo restante. Email de confirmación reutilizando el mecanismo de `ReporteEmailService` (PHPMailer/config SMTP existente).
4. Cron/limpieza: holds expirados se liberan (job en el cron existente del proyecto o al vuelo en cada consulta de disponibilidad: `DELETE WHERE expires_at < NOW()`).

**Anti-patrones:** webhook sin verificación de firma; CSRF token en webhook (va exento — es
server-to-server firmado); crear huésped duplicado sin buscar por email+hotel; asumir que el
webhook llega una sola vez (Stripe/MP reintentan: idempotencia obligatoria).

**Verificación:** pago de prueba en modo test de la pasarela crea reservación visible en
/reservaciones con su registro `pagado` en motor_pagos_online; webhook duplicado no duplica nada;
carrera simulada (2 pagos al mismo cuarto/fechas) → 1 reserva + 1 reembolso marcado.

---

## Fase 5 — Conciliación a Caja + pantalla interna

**Implementar `MotorReservasController`** (interno, autenticado):
- `before()`: `require_hotel_context()` + `require_hotel_module('motor_reservas')` + permiso `caja.view` para conciliar.
- `GET /motor-reservas` → tablero: pagos online pagados sin conciliar, conciliados del periodo,
  reembolsos, y estado del motor (activo/pausado, pasarela configurada, link público para copiar).
- `POST /motor-reservas/pagos/{id}/conciliar` → llama `AnticipoService::registrar($hotelId,
  $reservacionId, ['monto'=>$pago['monto'], 'metodo_pago'=>'transferencia', 'referencia'=>$proveedorPagoId,
  'concepto'=>'Anticipo online (motor de reservas)'], user_id())` → guarda `abono_id`,
  `conciliado_por`, `conciliado_at`, estado `conciliado`. Si no hay corte abierto, el propio
  servicio lanza el mensaje claro — mostrarlo tal cual.
- `POST /motor-reservas/configuracion` → editar keys `motor_*` de hotel_configuracion y
  credenciales de pasarela (secret con input password, nunca re-mostrado; solo "configurada ✓").
- Sidebar: entrada "Motor de reservas" bajo `$menuModuloActivo('motor_reservas')`.
- Dashboard interno: aviso "N pagos online por conciliar" (patrón de alertas existente).

**Nota de diseño:** el saldo de la reservación NO baja hasta conciliar (el dinero aún no está en
Caja). La ficha de reservación debe mostrar "Anticipo online pagado, pendiente de conciliar en
Caja: $X" leyendo motor_pagos_online — check-in saldo-aware sigue intacto.

**Verificación:** conciliar un pago crea el movimiento de Caja + abono con la misma trazabilidad
que un anticipo manual (verificado en /caja/movimientos y en la ficha de la reservación); sin
corte abierto muestra el mensaje del servicio; doble clic en conciliar no duplica (estado guard).

---

## Fase 6 — Panel SaaS y cierre comercial

1. En detalle de hotel del panel SaaS: bloque `motor_reservas` ya aparece por catálogo; añadir
   en la sección del hotel el link público `/h/{slug}/reservar` y estado de configuración
   (pasarela lista sí/no) — solo lectura para el SaaS admin.
2. AGENTS.md: añadir nota breve en la regla obligatoria: "dinero online = ledger propio +
   conciliación vía AnticipoService; nunca directo a Caja" (precedente para WhatsApp/pagos futuros).
3. Actualizar memoria del proyecto.

## Fase 7 — Verificación final (checklist E2E)

1. Hotel demo con módulo activo + pasarela test: flujo completo huésped → pago → reservación →
   push → conciliación → check-in con saldo correcto.
2. Hotel SIN el bloque: `/h/{slug}/reservar` responde página amable (no 500, no filtra datos).
3. Greps anti-patrón: `grep -rn "movimientos_caja" src/app/controllers/MotorReservas*` = 0;
   `grep -rn "secret_key" src/app/views` = 0; webhook verifica firma; UNIQUE de idempotencia existe.
4. Lint PHP de todos los archivos; smoke HTTP de rutas nuevas; precios ≥1000 sin coma en inputs.
5. Multi-tenant: hotel A no puede ver/conciliar pagos de hotel B (probar con 2 hoteles).

**Orden de ejecución:** F1 → F2 → F3 (demo visual sin dinero, ya vendible como preview) → F4 → F5 → F6 → F7.
Cada fase es un commit propio y deja el sistema funcionando.
