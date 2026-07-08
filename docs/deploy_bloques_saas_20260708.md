# Checklist de despliegue — bloques comerciales nuevos (2026-07-08)

Guía para subir a producción los 10 bloques construidos en la sesión del
2026-07-03 al 2026-07-08. Verificado en local (BD `medisoft_hoteles_import`):
todos los módulos registrados, migraciones aplicadas, rutas gateadas y crons
con lint OK.

> **Regla de oro:** aplicar las migraciones ANTES de subir el código que
> consulta las columnas/tablas nuevas.

---

## 1. Bloques incluidos

| Bloque | Clave | Precio | Necesita |
|--------|-------|--------|----------|
| Reputación y encuestas | `reputacion` | $149 | correo del hotel |
| Cupones y promociones | `promociones` | $129 | motor_reservas |
| Extras / upselling | `upsells` | $149 | motor_reservas |
| Forecast de ocupación | `forecast` | $199 | — (solo lectura) |
| Bitácora de auditoría | `auditoria` | $149–199 | — |
| Night audit | `night_audit` | $199 | cron |
| Huésped frecuente | `lealtad` | $179 | promociones |
| Motor en inglés | `motor_idiomas` | $99 | motor_reservas |
| Copiloto Medisoft | `copiloto` | $149 | ANTHROPIC_API_KEY (opcional) |
| Cobro SaaS automático | *(interno)* | — | vars SAAS_* + cron |

Los precios se fijan al correr la migración y son **editables** en
`/admin/saas/modulos`.

---

## 2. Migraciones — aplicar EN ESTE ORDEN

```bash
# Patron: docker exec -i medisoft_hoteles_db mysql -u<user> -p<pass> <db> < <archivo>
# (o el runner de migraciones del proyecto, que respeta el orden por nombre)

20260703_005_saas_cobros.sql          # base de cobros SaaS (si no estaba en prod)
20260703_006_reputacion.sql
20260703_008_saas_cobros_auto.sql     # vencimiento + correo en saas_cobros
20260703_009_promociones_cupones.sql
20260703_010_upsells_extras.sql
20260703_011_forecast.sql             # solo registra el modulo (sin tablas)
20260703_012_auditoria.sql
20260703_013_night_audit.sql
20260703_014_lealtad.sql
20260703_015_motor_idiomas.sql        # solo registra el modulo (sin tablas)
20260703_016_copiloto.sql
```

**Aviso de numeración:** existe también `20260703_006_usuario_preferencias_nav.sql`
(de la otra PC) que comparte el número 006 con reputación. **No se pisan**: son
archivos distintos y la tabla `migrations` los registra por nombre completo.
Ambos deben aplicarse. La rama también trae `20260704_005_nomina_reglas_legales.sql`
y `20260704_006_nomina_fiscal.sql` (nómina, otra PC) — aplicarlas también si no
están en prod.

Todas las migraciones son **aditivas** (crean módulos/tablas, no borran datos)
y usan `IF NOT EXISTS` / `ON DUPLICATE KEY`, así que reaplicar es seguro.

---

## 3. Variables de entorno (`.env` de producción)

Ya presentes (no tocar): `ANTHROPIC_API_KEY`, `MOTOR_PASARELA_KEY`.

**Agregar solo si se usará el cobro SaaS automático a hoteles:**

```env
SAAS_STRIPE_SECRET_KEY=sk_live_...        # llave de TU cuenta Stripe (Medisoft)
SAAS_STRIPE_WEBHOOK_SECRET=whsec_...      # firma del webhook de cobros SaaS
SAAS_EMAIL_REMITENTE=cobros@tudominio.com # remitente de los correos de cobro
SAAS_EMAIL_NOMBRE=Medisoft Hoteles        # opcional (nombre del remitente)
SAAS_COBRO_DIA_VENCIMIENTO=10             # opcional (dia limite de pago; default 10)
```

> Tras cambiar el `.env`, **recrear el contenedor** para que tome las variables.

El Copiloto y el Asesor IA usan `ANTHROPIC_API_KEY` (ya existe). Sin ella, el
Copiloto sigue funcionando **solo con reglas** (datos + FAQ), sin costo de API.

---

## 4. Crons a programar en el host

```cron
# Cobro SaaS: genera cobros, manda correos/recordatorios, marca vencidos (diario 8am)
0 8 * * * docker exec medisoft_hoteles_app php /var/www/html/tools/cron_saas_cobros.php

# Night audit: cierra el dia de AYER de cada hotel con el bloque (madrugada 4am)
0 4 * * * docker exec medisoft_hoteles_app php /var/www/html/tools/cron_night_audit.php
```

Ambos son **idempotentes**: correrlos dos veces no duplica nada.

---

## 5. Activación por hotel (post-deploy)

Todos los bloques son **opt-in** (no se activan solos en hoteles existentes).
Para cada hotel que los contrate:

1. Entrar a `/admin/saas/modulos` y activar el bloque.
2. Ajustar el precio por hotel si aplica (`precio_override`).
3. Configuración específica por bloque:
   - **reputacion**: link de Google Reviews en `/reputacion`.
   - **motor_idiomas**: nada; el selector ES/EN aparece solo.
   - **promociones / upsells**: crear cupones/extras en el motor.
   - **copiloto**: opcional apagar la IA con `copiloto.ia_activa` (solo reglas).

---

## 6. Verificación post-deploy (smoke)

```bash
# Rutas internas -> deben redirigir a login (303)
curl -s -o /dev/null -w "%{http_code}\n" https://TU-DOMINIO/forecast

# Motor publico -> 200 (ES) y 200 (EN si el hotel tiene motor_idiomas)
curl -s -o /dev/null -w "%{http_code}\n" https://TU-DOMINIO/h/<slug>/reservar

# Webhook SaaS sin firma -> 400 (rechaza)
curl -s -o /dev/null -w "%{http_code}\n" -X POST https://TU-DOMINIO/saas/webhook/stripe
```

Verificado en local el 2026-07-08: internas 303, motor ES/EN 200, webhook 400. ✅
