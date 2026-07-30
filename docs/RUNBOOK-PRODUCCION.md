# Runbook de producción — Medisoft Hoteles (VPS)

Guía rápida para operar/modificar producción desde CUALQUIER PC (escritorio o Surface).
Última actualización: 2026-07-20 (primer deploy).

## Datos del servidor

- **URL**: https://medisoft-hoteles.com  ·  Panel SaaS: https://medisoft-hoteles.com/admin/saas/hoteles
- **VPS**: Vultr, Ciudad de México. IP **216.238.90.237**. Ubuntu 24.04.
- **Código en el servidor**: `/opt/medisoft` (src/docs/migrations/docker/docker-compose.prod.yml).
- **Stack**: Caddy (HTTPS auto) + PHP/Apache (`medisoft_hoteles_app`) + MySQL 8 (`medisoft_hoteles_db`). BD: `medisoft_hoteles`.
- **Secretos**: en `/opt/medisoft/.env` del servidor (NO en el repo). Ver por SSH si se necesitan.

## Acceso SSH (requisito en cada PC)

La llave privada `medisoft_vps` debe estar en `~/.ssh/` de la PC. Conectar:

```bash
ssh -i ~/.ssh/medisoft_vps root@216.238.90.237
```

Para habilitar una PC nueva: copiar `~/.ssh/medisoft_vps` + `medisoft_vps.pub` desde una PC que ya la tenga (USB), o generar una llave nueva en la PC nueva (`ssh-keygen -t ed25519 -f ~/.ssh/medisoft_vps`) y agregar su `.pub` a `/root/.ssh/authorized_keys` del VPS desde una PC con acceso.

## Modificar producción (flujo estándar)

1. Editar el/los archivo(s) en el repo LOCAL (con la Surface al día vía `git pull`).
2. Subir SOLO los archivos cambiados por scp:
   ```bash
   scp -i ~/.ssh/medisoft_vps src/ruta/al/archivo.php root@216.238.90.237:/opt/medisoft/src/ruta/al/archivo.php
   ```
3. Verificar sintaxis y ownership dentro del contenedor:
   ```bash
   ssh -i ~/.ssh/medisoft_vps root@216.238.90.237 'docker exec medisoft_hoteles_app php -l /var/www/html/ruta/al/archivo.php && docker exec medisoft_hoteles_app chown 33:33 /var/www/html/ruta/al/archivo.php'
   ```
4. Si tocaste **clases Tailwind**: `npm run build:css` local y subir `src/public_html/css/tailwind.css`.
5. Si tocaste **PHP con OPcache cacheado**: reiniciar app → `ssh ... 'docker restart medisoft_hoteles_app'` (también limpia APCu de módulos/permisos).

## Migraciones nuevas en producción

```bash
scp -i ~/.ssh/medisoft_vps migrations/NUEVA.sql root@216.238.90.237:/opt/medisoft/migrations/
ssh -i ~/.ssh/medisoft_vps root@216.238.90.237 'docker exec medisoft_hoteles_app php /var/www/html/tools/migrate.php status'
ssh -i ~/.ssh/medisoft_vps root@216.238.90.237 'docker exec medisoft_hoteles_app php /var/www/html/tools/migrate.php up'
```
Tras cambiar esquema, regenerar `src/database/schema.sql` (comando en CLAUDE.md §Comandos).

## Salud, logs y backup

- **Health**: `curl "https://medisoft-hoteles.com/health?token=<HEALTH_TOKEN del .env>"` → debe dar `status: ok`.
- **Logs app**: `ssh ... 'docker logs --tail 50 medisoft_hoteles_app'`.
- **Backup manual**: `ssh ... 'bash /opt/medisoft/tools/backup_db.sh'` (los crons ya lo corren diario 3:30am).
- **Contenedores**: `ssh ... 'cd /opt/medisoft && docker compose -f docker-compose.prod.yml ps'`.
- **Reiniciar todo**: `ssh ... 'cd /opt/medisoft && APP_DOMAIN=medisoft-hoteles.com docker compose -f docker-compose.prod.yml up -d'`.

## Copia offsite (Backblaze B2)

Un respaldo que vive en el mismo VPS no sobrevive al VPS. `backup_db.sh` §4 sube
la copia del día a un bucket externo; rclone ya está instalado en el VPS.

**Alta (una sola vez, la hace el dueño de la cuenta):**
1. En Backblaze B2 → crear bucket **privado** (ej. `medisoft-backups`).
2. *App Keys* → *Add a New Application Key*, restringida **a ese bucket**, con permiso *Read and Write*. Backblaze muestra el `applicationKey` **una sola vez**.
3. Pegar en `/opt/medisoft/.env` (no requiere `rclone config`):
   ```
   BACKUP_B2_ACCOUNT=<keyID>
   BACKUP_B2_KEY=<applicationKey>
   BACKUP_B2_BUCKET=medisoft-backups
   BACKUP_ENV_PASSPHRASE=<passphrase larga>
   ```
   `BACKUP_ENV_PASSPHRASE` cifra el `.env` (AES-256) para que también viaje al bucket: sin él un restore no arranca. **Guardar esa passphrase en el gestor de contraseñas** — si su única copia es el `.env` que se perdió con el VPS, el cifrado no sirve de nada.
4. Probar: `ssh ... 'bash /opt/medisoft/tools/backup_db.sh'` → debe decir `Copia offsite VERIFICADA`.

Estas variables solo las lee el script del host; **no hace falta recrear el contenedor**.

**Restaurar desde el bucket (VPS perdido):** aprovisionar máquina nueva con `tools/provision_vps.sh`, y desde ella:
```bash
export RCLONE_CONFIG=/dev/null RCLONE_CONFIG_OFFSITE_TYPE=b2
export RCLONE_CONFIG_OFFSITE_ACCOUNT=<keyID> RCLONE_CONFIG_OFFSITE_KEY=<applicationKey>
rclone lsf offsite:medisoft-backups/db/          # ver qué hay
rclone copy offsite:medisoft-backups/db/<archivo>.sql.gz .
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in env_<fecha>.enc -out .env
```
Luego `tools/restore_db.sh <archivo>.sql.gz` y desplegar normal.

- Retención en el bucket: 30 días (BD, uploads y `.env` cifrado), aplicada por el propio script con `hard_delete` (sin eso B2 conserva versiones ocultas y la factura crece para siempre).
- Si el offsite no está configurado, el backup local **sigue funcionando** y el log deja `[WARN] ... NO hay copia fuera del VPS` cada día. Si está configurado y falla la subida, el script sale con código 1 y `[ERROR]`.

## Vigilante de infraestructura

`tools/watchdog_infra.sh` corre por cron (7:45 diario, log en `/var/log/medisoft_watchdog.log`) y revisa lo que un monitor HTTP **no** ve: contenedores, `/health`, disco, RAM disponible, frescura del backup local, rastro de la copia offsite y caducidad del certificado TLS. Imprime `[OK]`/`[ALERTA]` por revisión y sale con código 1 si algo falla.

Correrlo a mano: `ssh ... 'bash /opt/medisoft/tools/watchdog_infra.sh'`.

**No sustituye al monitor externo.** Un vigilante dentro del VPS no puede avisar que el VPS se cayó. El externo responde "¿está arriba?"; este responde "¿está sano?".

### Monitor externo (pendiente — lo da de alta el owner)
1. Crear cuenta en UptimeRobot (o similar) y agregar un monitor **HTTP(s)**.
2. URL: `https://medisoft-hoteles.com/health?token=<HEALTH_TOKEN del .env>`.
3. Intervalo 5 min. Configurar *keyword* `"status":"ok"` para que un 200 con la BD caída también cuente como falla.
4. Alerta a correo y push al teléfono.

⚠️ El canal WhatsApp **no sirve** hoy como vía de alerta: `WHATSAPP_SEND_ENABLED=false` y su token sigue pendiente de rotar. Si algún día se habilita, `watchdog_infra.sh` acepta un `WATCHDOG_NOTIFY_CMD` en el `.env`.

## Staging bajo demanda (para ensayar sin tocar prod)

Vive en `/opt/medisoft-staging/` — directorio, `.env` y volumen de MySQL **aparte** de producción. Se maneja siempre con `tools/staging.sh`, nunca con `docker compose` a mano: ese script deriva el `.env` de staging del de prod **neutralizando los secretos peligrosos** (WhatsApp, Anthropic, VAPID, pasarela, `HEALTH_TOKEN` y las llaves de B2). Copiar el `.env` tal cual es el error clásico: un staging con el token real puede mandarle mensajes a huéspedes de verdad y escribir sobre tus respaldos.

```bash
bash tools/staging.sh up                      # levanta con el código de prod
bash tools/staging.sh restaurar <dump.sql.gz> # siembra datos y CRONOMETRA
bash tools/staging.sh estado                  # qué hay arriba y cuánta RAM usa
bash tools/staging.sh down                    # apaga (conserva datos)
```

Acceso (no se expone a internet, escucha solo en loopback):
```bash
ssh -i ~/.ssh/medisoft_vps -L 8090:127.0.0.1:8090 root@216.238.90.237
```
y abrir `http://localhost:8090`.

- **Apagarlo al terminar.** Comparte los 3.9 GB con producción (~450 MB mientras corre) y `restart: "no"` es deliberado para que no reviva tras un reinicio del VPS.
- **Sirve para**: drill de restauración, QA de deploys, probar migraciones.
- **No sirve para medir capacidad con k6**: comparte los 2 vCPU con prod, así que el número saldría contaminado y de paso le pegaría al hotel. Para eso hace falta una máquina aparte.
- Drill ejecutado 2026-07-27: restaurar el dump real tomó **5 segundos** (126 tablas, 2 hoteles, 2,031 reservaciones). Ese número es el tiempo de recuperación *de datos*; el tiempo total de un desastre real incluye aprovisionar la máquina y desplegar.

## Editar el .env de producción (recrea el contenedor)

```bash
ssh -i ~/.ssh/medisoft_vps root@216.238.90.237
nano /opt/medisoft/.env    # p.ej. pegar ANTHROPIC_API_KEY
cd /opt/medisoft && APP_DOMAIN=medisoft-hoteles.com docker compose -f docker-compose.prod.yml up -d app
```

## Reglas de oro

- **NUNCA** editar directo en `/opt/medisoft` sin reflejar el cambio en el repo (se pierde en el próximo deploy y diverge).
- Cambio en prod = commitear también en el repo y `git push` para que las 2 PCs queden iguales.
- Ante duda de dinero/caja, verificar por PDO dentro del contenedor, no por mysql CLI (timezone).
- Gotchas de deploy fresco y de auto-edición de usuario: ver `CLAUDE.md` (secciones Infra de producción y Gotchas).
