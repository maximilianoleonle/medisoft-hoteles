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
