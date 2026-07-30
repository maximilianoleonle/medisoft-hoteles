#!/bin/bash
# Staging bajo demanda en el VPS — ensayar sin tocar produccion.
#
#   bash tools/staging.sh up [origen]     levanta staging con el codigo de <origen>
#                                         (default: /opt/medisoft, o sea prod)
#   bash tools/staging.sh restaurar <f>   carga un dump .sql.gz en la BD de staging
#   bash tools/staging.sh estado          que hay levantado y cuanta RAM usa
#   bash tools/staging.sh down            apaga (conserva los datos)
#   bash tools/staging.sh destruir        apaga y BORRA el volumen de staging
#
# Acceso: no se expone a internet. Desde tu PC:
#   ssh -i ~/.ssh/medisoft_vps -L 8090:127.0.0.1:8090 root@<vps>
#   y abrir http://localhost:8090
#
# POR QUE ESTE SCRIPT Y NO `docker compose` A MANO: porque el .env de staging se
# DERIVA del de produccion neutralizando los secretos peligrosos. Un staging con
# el token de WhatsApp de prod puede mandarle mensajes a huespedes REALES, y con
# las llaves de B2 puede escribir sobre tus respaldos. Copiar el .env tal cual es
# el error clasico; aqui es imposible cometerlo.
set -euo pipefail

PROD_DIR="/opt/medisoft"
STAGING_DIR="/opt/medisoft-staging"
COMPOSE="docker-compose.staging.yml"

# Llaves que JAMAS deben viajar vivas a staging, con el valor que se les fuerza.
# Regla: si puede gastar dinero, mandar un mensaje a un tercero o escribir sobre
# los respaldos, va aqui.
neutralizar_env() {
    local origen="$1" destino="$2"
    tr -d '\r' < "$origen" \
      | sed -E \
        -e 's/^(APP_ENV)=.*/\1=staging/' \
        -e 's/^(DB_NAME)=.*/\1=medisoft_staging/' \
        -e 's/^(DB_USER)=.*/\1=medisoft_user/' \
        -e 's/^(DB_PASS)=.*/\1=staging_local/' \
        -e 's/^(DB_ROOT_PASS)=.*/\1=staging_root_local/' \
        -e 's/^(WHATSAPP_SEND_ENABLED|APP_WHATSAPP_SEND_ENABLED)=.*/\1=false/' \
        -e 's/^(WHATSAPP_API_TOKEN|APP_WHATSAPP_API_TOKEN)=.*/\1=/' \
        -e 's/^(WHATSAPP_DESTINATION|APP_WHATSAPP_DESTINATION)=.*/\1=/' \
        -e 's/^(ANTHROPIC_API_KEY)=.*/\1=/' \
        -e 's/^(MOTOR_PASARELA_KEY)=.*/\1=/' \
        -e 's/^(PWA_VAPID_PRIVATE_KEY|PWA_VAPID_PUBLIC_KEY)=.*/\1=/' \
        -e 's/^(HEALTH_TOKEN)=.*/\1=/' \
        -e 's/^(BACKUP_RCLONE_REMOTE|BACKUP_B2_ACCOUNT|BACKUP_B2_KEY|BACKUP_B2_BUCKET|BACKUP_ENV_PASSPHRASE)=.*/\1=/' \
        -e 's/^(TRUSTED_PROXIES)=.*/\1=/' \
      > "$destino"
    chmod 600 "$destino"
}

case "${1:-}" in

up)
    ORIGEN="${2:-$PROD_DIR}"
    [ -d "$ORIGEN/src" ] || { echo "[ERROR] $ORIGEN no tiene src/"; exit 1; }
    echo "[INFO] Levantando staging con el codigo de: $ORIGEN"

    mkdir -p "$STAGING_DIR"
    # --delete para que staging sea un espejo exacto y no acumule archivos
    # borrados en el origen (un staging sucio miente en el QA de deploys).
    rsync -a --delete "$ORIGEN/src/" "$STAGING_DIR/src/"
    rsync -a --delete "$ORIGEN/migrations/" "$STAGING_DIR/migrations/" 2>/dev/null || true
    mkdir -p "$STAGING_DIR/docker/php"
    cp -a "$ORIGEN/docker/php/apache-prod.conf" "$STAGING_DIR/docker/php/" 2>/dev/null || true
    cp -a "$PROD_DIR/$COMPOSE" "$STAGING_DIR/$COMPOSE"

    neutralizar_env "$PROD_DIR/.env" "$STAGING_DIR/.env"
    echo "[OK]   .env de staging derivado del de prod, con secretos neutralizados."

    cd "$STAGING_DIR"
    docker compose -f "$COMPOSE" up -d
    echo "[OK]   Staging arriba en 127.0.0.1:8090"
    echo "       Tunel:  ssh -i ~/.ssh/medisoft_vps -L 8090:127.0.0.1:8090 root@<vps>"
    echo "       Luego:  http://localhost:8090"
    echo "[SIG.] Sembrar datos:  bash tools/staging.sh restaurar <dump.sql.gz>"
    ;;

restaurar)
    DUMP="${2:-}"
    [ -f "$DUMP" ] || { echo "[ERROR] Falta el dump. Ej: bash tools/staging.sh restaurar $PROD_DIR/backups/db/xxx.sql.gz"; exit 1; }
    docker inspect -f '{{.State.Status}}' medisoft_staging_db 2>/dev/null | grep -q running \
        || { echo "[ERROR] staging no esta arriba. Corre primero: bash tools/staging.sh up"; exit 1; }

    echo "[INFO] Restaurando $(basename "$DUMP") en la BD de staging..."
    INICIO=$(date +%s)
    # El dump viene de prod y trae su propio nombre de BD en los USE/CREATE:
    # se fuerza el destino con --database para que caiga en medisoft_staging.
    zcat "$DUMP" | docker exec -i medisoft_staging_db \
        mysql -uroot -pstaging_root_local --force medisoft_staging 2>&1 \
        | grep -v "Using a password" || true
    FIN=$(date +%s)

    FILAS=$(docker exec medisoft_staging_db mysql -uroot -pstaging_root_local -N -B \
            -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='medisoft_staging';" 2>/dev/null \
            | grep -v "Using a password" || echo "?")
    echo "[OK]   Restauracion terminada en $((FIN-INICIO))s — ${FILAS} tablas en medisoft_staging."
    echo "       ESE numero de segundos es tu tiempo de recuperacion de datos: anotalo."
    ;;

estado)
    echo "== contenedores de staging =="
    docker ps -a --filter "name=medisoft_staging" --format "{{.Names}}\t{{.Status}}" || true
    echo "== memoria =="
    docker stats --no-stream --format "{{.Name}}\t{{.MemUsage}}" 2>/dev/null | grep staging || echo "(apagado)"
    echo "== RAM libre del VPS =="
    free -m | awk '/^Mem:/ {print $7" MB disponibles"}'
    ;;

down)
    cd "$STAGING_DIR" 2>/dev/null || { echo "[INFO] No hay staging que apagar."; exit 0; }
    docker compose -f "$COMPOSE" down
    echo "[OK]   Staging apagado (los datos se conservan en el volumen)."
    ;;

destruir)
    cd "$STAGING_DIR" 2>/dev/null || { echo "[INFO] No hay staging que destruir."; exit 0; }
    docker compose -f "$COMPOSE" down -v
    echo "[OK]   Staging apagado y volumen borrado."
    ;;

*)
    sed -n '2,20p' "$0" | sed 's/^# \{0,1\}//'
    exit 1
    ;;
esac
