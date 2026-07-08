# Checklist de deploy a producción — Medisoft Hoteles

Actualizado: 2026-07-03. Orden pensado para la primera puesta en producción.

## 1. Servidor y dominio
- [ ] Hosting decidido (VPS con Docker recomendado; `index.php` también soporta layout Hostinger `private/staging`).
- [ ] Dominio apuntando al servidor (A/AAAA). Sugerido: `app.tudominio.com` para el sistema.
- [ ] SSL activo (Let's Encrypt/certbot o el del hosting). El motor de reservas y la PWA **requieren HTTPS**.
- [ ] PHP 8.1+ con extensiones: pdo_mysql, curl, openssl, sodium, zip, mbstring, gd.

## 2. Base de datos
- [ ] MySQL 8 con contraseñas fuertes (no las de desarrollo).
- [ ] Importar esquema: correr TODAS las migraciones de `migrations/` en orden.
- [ ] Verificar `sql_mode` incluye `only_full_group_by` (el código ya es compatible).
- [ ] Usuario de BD con permisos solo sobre la BD de la app (no root).

## 3. Variables de entorno (.env del servidor)
- [ ] Copiar `.env.example` → `.env` y llenar todo.
- [ ] `APP_URL` con el dominio real HTTPS (afecta links de PWA, motor y webhooks).
- [ ] `MOTOR_PASARELA_KEY`: generar NUEVA (`openssl rand -base64 32`) — no reusar la de dev.
- [ ] `PWA_VAPID_*`: generar par NUEVO para producción.
- [ ] `ANTHROPIC_API_KEY`: llave de producción (puede ser la misma cuenta).
- [ ] Docker: recordar que el env se inyecta al CREAR el contenedor (`docker compose up -d`).

## 4. Seguridad (estado y pendientes)
- [x] SEC-001 rate-limit de logins — hecho (tabla login_intentos, sobrevive sesión nueva).
- [x] SEC-003 debug — CorreccionController_FINAL eliminado; rutas debug comentadas.
- [ ] SEC-002: tras 1-2 semanas sin violaciones en consola, promover CSP de
      `Content-Security-Policy-Report-Only` a `Content-Security-Policy` en `src/public_html/.htaccess`.
- [ ] `APP_DEBUG=false` y verificar que errores NO se imprimen en pantalla (solo log).
- [ ] phpMyAdmin: NO exponer en producción (quitar del compose o proteger por IP/VPN).
- [ ] Puerto MySQL no expuesto públicamente.
- [ ] Backups automáticos diarios de BD (mysqldump + retención 30d) y de `src/uploads/`.

## 5. Motor de reservas (por hotel que lo contrate)
- [ ] Cambiar credenciales a `sk_live_`/`pk_live_` y modo "Producción" en su tablero.
- [ ] Registrar webhook en dashboard de Stripe: `https://DOMINIO/h/{slug}/reservar/webhook/stripe`
      evento `checkout.session.completed`; pegar el `whsec_` de producción en el tablero.
- [ ] Probar un pago real chico y reembolsarlo.
- [ ] Verificar la página pública con el dominio real (URL visible en el tablero del hotel).

## 6. Crons del servidor (crontab del host)
```cron
# Resumen inteligente matutino (7:00 AM hora del servidor)
0 7 * * * docker exec medisoft_hoteles_app php /var/www/html/tools/cron_resumen_ia.php >> /var/log/medisoft_cron.log 2>&1
```
(Los holds expirados del motor se limpian solos en cada consulta de disponibilidad.)

## 7. Humo post-deploy (15 min)
- [ ] Login por slug de un hotel + dashboard carga.
- [ ] `bash tools/test_bloques_funcional.sh` adaptado al dominio (o repetir manualmente: apagar/encender un bloque y verificar).
- [ ] Motor: disponibilidad pública responde; pago test → reservación → conciliar.
- [ ] Asesor inteligente: generar un resumen desde la UI.
- [ ] Push: instalar PWA en un teléfono y recibir una notificación.
- [ ] Verificar HTTPS en todas las páginas (sin contenido mixto).

## Lecciones de infra ya aprendidas (no tropezar dos veces)
- `index.php` busca `.env` en la raíz del código desplegado (`ROOT_PATH/.env`); en Docker
  el web vive del `env_file` inyectado al crear el contenedor.
- Tras editar `.env` en Docker: `docker compose up -d app` (recrear), no `restart`.
- La BD activa es la que diga `DB_NAME` del `.env` — verificar antes de migrar.
