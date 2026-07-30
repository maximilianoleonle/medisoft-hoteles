#!/bin/bash
# Vigilante de infraestructura de Medisoft Hoteles.
#
# Corre en el HOST del VPS, via crontab:
#   45 7 * * * bash /opt/medisoft/tools/watchdog_infra.sh >> /var/log/medisoft_watchdog.log 2>&1
#
# QUE ES Y QUE NO ES:
# Esto NO reemplaza al monitor externo (UptimeRobot y similares). Un vigilante
# que corre DENTRO del VPS no puede avisar que el VPS se cayo: si la maquina
# muere, muere el vigilante con ella. Los dos son complementarios:
#   - Monitor EXTERNO -> "¿esta arriba?"  (lo unico que avisa una caida total)
#   - Este vigilante  -> "¿esta sano?"    (lo que un ping HTTP nunca ve:
#                        disco llenandose, backup que dejo de correr, copia
#                        offsite estancada, certificado por vencer, RAM al tope)
#
# Cada revision imprime [OK] o [ALERTA]. Sale con codigo 1 si algo esta mal,
# para que el cron o cualquier supervisor lo detecte.
#
# Canal de aviso: por ahora el log y el codigo de salida. WhatsApp NO se usa a
# proposito (WHATSAPP_SEND_ENABLED=false y su token sigue pendiente de rotar).
# Si defines WATCHDOG_NOTIFY_CMD en el .env, se ejecuta con el resumen como
# argumento cuando algo falla (ej. un curl a un webhook).
set -uo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_DIR"

# El `|| true` es obligatorio: con pipefail, una llave ausente hace fallar el
# grep y la asignacion abortaria el script (mismo motivo que en backup_db.sh).
env_val() {
    grep -E "^${1}=" .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r' || true
}

FALLAS=0
RESUMEN=""
ts() { date '+%F %T'; }
ok()     { echo "[OK]     $(ts) $1"; }
alerta() { echo "[ALERTA] $(ts) $1"; FALLAS=$((FALLAS+1)); RESUMEN="${RESUMEN}
- $1"; }

echo "===== Vigilante de infraestructura — $(ts) ====="

# ── 1) Contenedores arriba ───────────────────────────────────────────────
for c in medisoft_caddy medisoft_hoteles_app medisoft_hoteles_db; do
    estado="$(docker inspect -f '{{.State.Status}}' "$c" 2>/dev/null || echo ausente)"
    if [ "$estado" = "running" ]; then
        ok "contenedor $c: running"
    else
        alerta "contenedor $c NO esta corriendo (estado: $estado)"
    fi
done

# ── 2) Salud de la aplicacion (ejercita Caddy + PHP + MySQL de un golpe) ─
APP_DOMAIN="$(env_val APP_DOMAIN)"; APP_DOMAIN="${APP_DOMAIN:-localhost}"
HEALTH_TOKEN="$(env_val HEALTH_TOKEN)"
if [ -z "$HEALTH_TOKEN" ]; then
    alerta "HEALTH_TOKEN no esta en .env: /health responde 404 a proposito y no hay como monitorearlo"
else
    # --max-time evita que el vigilante se cuelgue si la app esta trabada.
    CODIGO="$(curl -s -o /tmp/_wd_health.json -w '%{http_code}' --max-time 20 \
              "https://${APP_DOMAIN}/health?token=${HEALTH_TOKEN}" || echo 000)"
    if [ "$CODIGO" = "200" ] && grep -q '"status"[[:space:]]*:[[:space:]]*"ok"' /tmp/_wd_health.json 2>/dev/null; then
        ok "/health responde 200 con status ok"
    else
        alerta "/health devolvio HTTP ${CODIGO} (se esperaba 200 con status ok)"
    fi
    rm -f /tmp/_wd_health.json
fi

# ── 3) Disco ─────────────────────────────────────────────────────────────
USO_DISCO="$(df --output=pcent / | tail -1 | tr -dc '0-9')"
if [ "${USO_DISCO:-0}" -ge 85 ]; then
    alerta "disco al ${USO_DISCO}% (umbral 85%) — revisar backups/ y logs de Docker"
else
    ok "disco al ${USO_DISCO}%"
fi

# ── 4) RAM disponible ────────────────────────────────────────────────────
# 'available' (no 'free'): free ignora el cache reclamable y asusta de gratis.
RAM_DISP="$(free -m | awk '/^Mem:/ {print $7}')"
if [ "${RAM_DISP:-0}" -lt 300 ]; then
    alerta "solo ${RAM_DISP} MB de RAM disponible (umbral 300) — riesgo de OOM"
else
    ok "RAM disponible: ${RAM_DISP} MB"
fi

# ── 5) Backup local reciente ─────────────────────────────────────────────
# 30h y no 24: da margen para que el cron de las 3:30am no dispare una falsa
# alarma si el vigilante corre un poco antes de tiempo.
ULTIMO_DB="$(find backups/db -name '*.sql.gz' -mmin -1800 2>/dev/null | head -1)"
if [ -n "$ULTIMO_DB" ]; then
    ok "backup local reciente: $(basename "$ULTIMO_DB")"
else
    alerta "NO hay backup local de las ultimas 30h — el cron de las 3:30am pudo dejar de correr"
fi

# ── 6) Copia offsite ─────────────────────────────────────────────────────
RCLONE_REMOTE="$(env_val BACKUP_RCLONE_REMOTE)"
B2_BUCKET="$(env_val BACKUP_B2_BUCKET)"
if [ -z "$RCLONE_REMOTE" ] && [ -z "$B2_BUCKET" ]; then
    alerta "sin copia offsite configurada: perder el VPS borraria datos y respaldos juntos"
else
    # Si esta configurada, el propio backup_db.sh ya falla ruidosamente cuando
    # la subida no ocurre; aqui solo se confirma que quedo rastro reciente.
    if grep -q "Copia offsite VERIFICADA" /var/log/medisoft_backup.log 2>/dev/null \
       && [ -n "$(find /var/log/medisoft_backup.log -mmin -1800 2>/dev/null)" ]; then
        ok "copia offsite verificada en las ultimas 30h"
    else
        alerta "no hay rastro reciente de 'Copia offsite VERIFICADA' en el log de backup"
    fi
fi

# ── 7) Certificado TLS ───────────────────────────────────────────────────
# Caddy renueva solo; esto es la red por si la renovacion se rompe en silencio.
if [ "$APP_DOMAIN" != "localhost" ]; then
    FIN_CERT="$(echo | openssl s_client -connect "${APP_DOMAIN}:443" -servername "$APP_DOMAIN" 2>/dev/null \
                | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)"
    if [ -n "$FIN_CERT" ]; then
        DIAS=$(( ( $(date -d "$FIN_CERT" +%s) - $(date +%s) ) / 86400 ))
        if [ "$DIAS" -lt 14 ]; then
            alerta "el certificado TLS vence en ${DIAS} dias — Caddy deberia haberlo renovado ya"
        else
            ok "certificado TLS valido ${DIAS} dias mas"
        fi
    else
        alerta "no se pudo leer el certificado TLS de ${APP_DOMAIN}"
    fi
fi

# ── Cierre ───────────────────────────────────────────────────────────────
echo "-----------------------------------------------------------"
if [ "$FALLAS" -eq 0 ]; then
    echo "[OK]     $(ts) Todo en orden."
    exit 0
fi

echo "[ALERTA] $(ts) ${FALLAS} problema(s) detectado(s):${RESUMEN}"
NOTIFY_CMD="$(env_val WATCHDOG_NOTIFY_CMD)"
if [ -n "$NOTIFY_CMD" ]; then
    "$NOTIFY_CMD" "Medisoft ${APP_DOMAIN}: ${FALLAS} alerta(s) de infraestructura.${RESUMEN}" >/dev/null 2>&1 \
        && echo "[INFO]   $(ts) Aviso enviado con WATCHDOG_NOTIFY_CMD." \
        || echo "[WARN]   $(ts) WATCHDOG_NOTIFY_CMD fallo."
fi
exit 1
