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
- [ ] `ANTHROPIC_API_KEY`: llave de producción (OJO: la de dev dio 401 el 2026-07-09 — generar una NUEVA en console.anthropic.com; sin ella los bloques IA que se venden no funcionan).
- [ ] `MYSQL_BUFFER_POOL`: memoria de trabajo de MySQL segun RAM del VPS (~50-60% si comparte con PHP; ej. `2G` en VPS de 4 GB; default 1G).
- [ ] Docker: recordar que el env se inyecta al CREAR el contenedor (`docker compose up -d`).

## 4. Seguridad (estado y pendientes)
- [x] SEC-001 rate-limit de logins — hecho (tabla login_intentos, sobrevive sesión nueva).
- [x] SEC-003 debug — CorreccionController_FINAL eliminado; rutas debug comentadas.
- [ ] SEC-002: tras 1-2 semanas sin violaciones en consola, promover CSP de
      `Content-Security-Policy-Report-Only` a `Content-Security-Policy` en `src/public_html/.htaccess`.
- [x] `display_errors=Off` en producción — `docker/php/php-prod.ini` montado por el compose prod (errores al stderr del contenedor, cookie de sesión Secure, expose_php Off). Verificar igualmente `APP_DEBUG=false`.
- [x] Rotación de logs de docker (10MB×3 por servicio, ambos compose) — sin esto el disco se llena solo.
- [ ] phpMyAdmin: NO exponer en producción (quitar del compose o proteger por IP/VPN).
- [x] Puerto MySQL no expuesto públicamente (compose prod sin `ports` en db).
- [x] Backups automáticos diarios de BD + uploads — ver sección 6b (falta solo el offsite).
- [ ] Deudas MEDIAS de auditorías previas antes del go-live: rate-limit fail-closed del motor público, `llave()` a 32 bytes, CSV injection en exportes de nómina, TOCTOU en cierre de nómina.

## 5. Motor de reservas (por hotel que lo contrate)
- [ ] Cambiar credenciales a `sk_live_`/`pk_live_` y modo "Producción" en su tablero.
- [ ] Registrar webhook en dashboard de Stripe: `https://DOMINIO/h/{slug}/reservar/webhook/stripe`
      evento `checkout.session.completed`; pegar el `whsec_` de producción en el tablero.
- [ ] Probar un pago real chico y reembolsarlo.
- [ ] Verificar la página pública con el dominio real (URL visible en el tablero del hotel).

## 6. Crons del servidor (crontab del host)
Son CUATRO crons de la app + backup. Instalar el bloque completo (ajustar `/ruta/al/repo`):
```cron
# Canales iCal: sincroniza Airbnb/Booking de todos los hoteles (cada 30 min)
*/30 * * * * docker exec medisoft_hoteles_app php /var/www/html/tools/cron_ical_sync.php >> /var/log/medisoft_cron.log 2>&1
# Backup diario BD + uploads (verificado restaurable; retencion 30d/7d)
30 3 * * * bash /ruta/al/repo/tools/backup_db.sh >> /var/log/medisoft_backup.log 2>&1
# Night audit: cierre nocturno del dia anterior (4:00 AM)
0 4 * * * docker exec medisoft_hoteles_app php /var/www/html/tools/cron_night_audit.php >> /var/log/medisoft_cron.log 2>&1
# Resumen inteligente matutino (7:00 AM hora del servidor)
0 7 * * * docker exec medisoft_hoteles_app php /var/www/html/tools/cron_resumen_ia.php >> /var/log/medisoft_cron.log 2>&1
# Cobros SaaS: facturacion mensual a hoteles (diario 8:00, idempotente)
0 8 * * * docker exec medisoft_hoteles_app php /var/www/html/tools/cron_saas_cobros.php >> /var/log/medisoft_cron.log 2>&1
# Truncar el slow log de MySQL (dia 1 de cada mes, tras revisarlo)
0 5 1 * * docker exec medisoft_hoteles_db sh -c 'truncate -s 0 /var/lib/mysql/slow.log' >> /var/log/medisoft_cron.log 2>&1
# Verificacion mensual de que el backup ES restaurable (dia 2)
0 5 2 * * bash -c 'cd /ruta/al/repo && bash tools/restore_db.sh "$(ls -t backups/db/*.sql.gz | head -1)" --verificar' >> /var/log/medisoft_backup.log 2>&1
```
(Los holds expirados del motor se limpian solos en cada consulta de disponibilidad.)

## 6b. Backups (hecho 2026-07-09 — mantener)
- [x] `tools/backup_db.sh`: mysqldump --single-transaction (no bloquea la operacion) + uploads; retencion BD 30d, uploads 7d; verifica integridad del dump y falla ruidosamente.
- [x] `tools/restore_db.sh --verificar`: restaura en BD temporal sin tocar la real (probado: 113 tablas). `--real` para desastre, con confirmacion tecleada.
- [ ] OFFSITE: sincronizar `backups/` a un destino FUERA del VPS (rclone a S3/B2/Drive). Un backup en el mismo disco no sobrevive al VPS.
- [ ] Probar `--verificar` en el servidor tras el primer backup nocturno.

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
