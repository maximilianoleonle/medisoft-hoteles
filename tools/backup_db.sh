#!/bin/bash
# Backup diario de Medisoft Hoteles: base de datos + uploads.
#
# Corre en el HOST (no dentro del contenedor), via crontab:
#   30 3 * * * bash /ruta/al/repo/tools/backup_db.sh >> /var/log/medisoft_backup.log 2>&1
#
# Que hace:
#  - mysqldump con --single-transaction: snapshot consistente de InnoDB sin
#    bloquear la operacion (seguro correrlo con hoteles trabajando).
#  - Verifica la integridad del dump (gzip valido + marca de fin de mysqldump).
#  - Respalda src/public_html/uploads (logos, comprobantes, documentos).
#  - Retencion: BD 30 dias, uploads 7 dias (local); offsite 30 dias ambos.
#  - Copia OFFSITE con rclone (ver §4): un backup en el mismo VPS no
#    sobrevive al VPS. Sin offsite configurado avisa fuerte, no falla.
#  - Falla RUIDOSAMENTE (exit != 0 y [ERROR] en el log) si algo sale mal:
#    un backup que falla en silencio es peor que no tener backup.
#
# Restaurar: ver tools/restore_db.sh
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_DIR"

# ── Credenciales desde .env (sin exportarlas al ambiente) ────────────────
# OJO: el `|| true` NO es adorno. Con `set -euo pipefail`, si la llave no
# existe en .env el grep falla, pipefail propaga el fallo y la asignacion
# aborta el script a media corrida (backup mudo). Toda llave OPCIONAL depende
# de esto para devolver vacio en vez de matar el backup.
env_val() {
    grep -E "^${1}=" .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r' || true
}
DB_NAME="$(env_val DB_NAME)"; DB_NAME="${DB_NAME:-medisoft_hoteles}"
DB_ROOT_PASS="$(env_val DB_ROOT_PASS)"
if [ -z "$DB_ROOT_PASS" ]; then
    echo "[ERROR] $(date '+%F %T') DB_ROOT_PASS no esta en .env — backup abortado."
    exit 1
fi

CONTENEDOR_DB="medisoft_hoteles_db"
STAMP="$(date +%Y%m%d_%H%M%S)"
DIR_DB="$REPO_DIR/backups/db"
DIR_UPLOADS="$REPO_DIR/backups/uploads"
mkdir -p "$DIR_DB" "$DIR_UPLOADS"

# ── 1) Dump de la base de datos ──────────────────────────────────────────
ARCHIVO_DB="$DIR_DB/${DB_NAME}_${STAMP}.sql.gz"
echo "[INFO] $(date '+%F %T') Iniciando dump de ${DB_NAME}..."

if ! docker exec -e MYSQL_PWD="$DB_ROOT_PASS" "$CONTENEDOR_DB" \
    mysqldump --single-transaction --quick --routines --triggers --events \
    -uroot "$DB_NAME" | gzip > "$ARCHIVO_DB"; then
    echo "[ERROR] $(date '+%F %T') mysqldump fallo — revisar contenedor ${CONTENEDOR_DB}."
    rm -f "$ARCHIVO_DB"
    exit 1
fi

# Integridad: gzip valido y el dump termina con la marca de mysqldump.
if ! gzip -t "$ARCHIVO_DB" 2>/dev/null; then
    echo "[ERROR] $(date '+%F %T') El dump quedo corrupto (gzip invalido): $ARCHIVO_DB"
    exit 1
fi
if ! zcat "$ARCHIVO_DB" | tail -5 | grep -q "Dump completed"; then
    echo "[ERROR] $(date '+%F %T') El dump quedo incompleto (sin 'Dump completed'): $ARCHIVO_DB"
    exit 1
fi
echo "[OK]   $(date '+%F %T') BD respaldada: $ARCHIVO_DB ($(du -h "$ARCHIVO_DB" | cut -f1))"

# ── 2) Uploads (logos, comprobantes, documentos por hotel) ──────────────
ARCHIVO_UP="$DIR_UPLOADS/uploads_${STAMP}.tar.gz"
if [ -d "$REPO_DIR/src/public_html/uploads" ]; then
    tar -czf "$ARCHIVO_UP" -C "$REPO_DIR/src/public_html" uploads
    echo "[OK]   $(date '+%F %T') Uploads respaldados: $ARCHIVO_UP ($(du -h "$ARCHIVO_UP" | cut -f1))"
else
    echo "[WARN] $(date '+%F %T') No existe src/public_html/uploads — se omite."
fi

# ── 3) Retencion ─────────────────────────────────────────────────────────
BORRADOS_DB=$(find "$DIR_DB" -name '*.sql.gz' -mtime +30 -print -delete | wc -l)
BORRADOS_UP=$(find "$DIR_UPLOADS" -name '*.tar.gz' -mtime +7 -print -delete | wc -l)
echo "[OK]   $(date '+%F %T') Retencion aplicada (borrados: BD=$BORRADOS_DB, uploads=$BORRADOS_UP)."

# ── 4) Copia OFFSITE (rclone) ────────────────────────────────────────────
# Un backup que vive en el mismo VPS no sobrevive al VPS: perder la maquina
# borraria datos y respaldos juntos. Este paso sube la copia del dia afuera.
#
# Configurar UNA sola vez, en el .env. NO hace falta `rclone config`: el remote
# se arma con variables de entorno, asi los secretos viven en un unico lugar.
#   BACKUP_B2_ACCOUNT=<keyID de Backblaze B2>
#   BACKUP_B2_KEY=<applicationKey>
#   BACKUP_B2_BUCKET=<nombre-del-bucket>
# Alternativa: si ya existe un remote hecho a mano con `rclone config`, basta
#   BACKUP_RCLONE_REMOTE=miremoto:mibucket        (gana sobre las BACKUP_B2_*)
#
# Sin nada configurado el paso se omite con [WARN]: no rompe el backup local,
# pero deja constancia DIARIA de que no hay copia externa.
RCLONE_REMOTE="$(env_val BACKUP_RCLONE_REMOTE)"
B2_ACCOUNT="$(env_val BACKUP_B2_ACCOUNT)"
B2_KEY="$(env_val BACKUP_B2_KEY)"
B2_BUCKET="$(env_val BACKUP_B2_BUCKET)"

if [ -z "$RCLONE_REMOTE" ] && [ -n "$B2_ACCOUNT" ] && [ -n "$B2_KEY" ] && [ -n "$B2_BUCKET" ]; then
    # Sin archivo de config, rclone avisa "Config file not found" en CADA
    # corrida. Un log que se queja a diario sin motivo es un log que nadie lee.
    # Solo aqui: si el remote vino de `rclone config`, su .conf debe leerse.
    export RCLONE_CONFIG="/dev/null"
    export RCLONE_CONFIG_OFFSITE_TYPE="b2"
    export RCLONE_CONFIG_OFFSITE_ACCOUNT="$B2_ACCOUNT"
    export RCLONE_CONFIG_OFFSITE_KEY="$B2_KEY"
    # hard_delete: sin esto B2 guarda una version OCULTA de cada archivo
    # borrado y la factura crece para siempre aunque la retencion "borre".
    export RCLONE_CONFIG_OFFSITE_HARD_DELETE="true"
    RCLONE_REMOTE="offsite:${B2_BUCKET}"
fi

if [ -n "$RCLONE_REMOTE" ]; then
    if ! command -v rclone >/dev/null 2>&1; then
        echo "[ERROR] $(date '+%F %T') Offsite configurado pero rclone no esta instalado (apt-get install -y rclone)."
        exit 1
    fi

    # 4a) El .env, cifrado. Sin el un restore no arranca (claves de BD, VAPID,
    # tokens de WhatsApp/IA), pero es justo el archivo que no debe viajar en
    # claro a un bucket. Solo se sube si hay passphrase.
    # OJO: guarda esa passphrase en tu gestor de contrasenas. Si su unica copia
    # es el .env que se perdio junto con el VPS, el cifrado no sirve de nada.
    ARCHIVO_ENV=""
    ENV_PASS="$(env_val BACKUP_ENV_PASSPHRASE)"
    if [ -n "$ENV_PASS" ] && [ -f "$REPO_DIR/.env" ]; then
        DIR_ENV="$REPO_DIR/backups/env"
        mkdir -p "$DIR_ENV"
        ARCHIVO_ENV="$DIR_ENV/env_${STAMP}.enc"
        # -pass env: y no pass:, para que la passphrase no quede visible en `ps`.
        export MS_ENV_PASS="$ENV_PASS"
        if openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt \
            -in "$REPO_DIR/.env" -out "$ARCHIVO_ENV" -pass env:MS_ENV_PASS 2>/dev/null; then
            echo "[OK]   $(date '+%F %T') .env cifrado para offsite: $(basename "$ARCHIVO_ENV")"
        else
            echo "[ERROR] $(date '+%F %T') No se pudo cifrar el .env — offsite abortado."
            rm -f "$ARCHIVO_ENV"
            exit 1
        fi
        unset MS_ENV_PASS
    fi

    SUBIDA_OK=1
    rclone copy "$ARCHIVO_DB" "$RCLONE_REMOTE/db/" --no-traverse || SUBIDA_OK=0
    if [ -f "$ARCHIVO_UP" ]; then
        rclone copy "$ARCHIVO_UP" "$RCLONE_REMOTE/uploads/" --no-traverse || SUBIDA_OK=0
    fi
    if [ -n "$ARCHIVO_ENV" ] && [ -f "$ARCHIVO_ENV" ]; then
        rclone copy "$ARCHIVO_ENV" "$RCLONE_REMOTE/env/" --no-traverse || SUBIDA_OK=0
    fi

    if [ "$SUBIDA_OK" -ne 1 ]; then
        echo "[ERROR] $(date '+%F %T') Fallo la subida offsite a $RCLONE_REMOTE."
        exit 1
    fi

    # Verificacion REAL: que rclone no truene no prueba que el archivo llego.
    # Se relee el destino y se exige el dump del dia por nombre.
    if rclone lsf "$RCLONE_REMOTE/db/" 2>/dev/null | grep -qxF "$(basename "$ARCHIVO_DB")"; then
        echo "[OK]   $(date '+%F %T') Copia offsite VERIFICADA en $RCLONE_REMOTE (dump del dia presente)."
    else
        echo "[ERROR] $(date '+%F %T') rclone no reporto error pero el dump NO aparece en $RCLONE_REMOTE/db/."
        exit 1
    fi

    # Retencion remota: 30 dias para todo (el bucket es barato; el objetivo es
    # sobrevivir a un desastre, no ahorrar centavos).
    rclone delete "$RCLONE_REMOTE/db/" --min-age 30d 2>/dev/null || true
    rclone delete "$RCLONE_REMOTE/uploads/" --min-age 30d 2>/dev/null || true
    rclone delete "$RCLONE_REMOTE/env/" --min-age 30d 2>/dev/null || true
    find "$REPO_DIR/backups/env" -name '*.enc' -mtime +30 -delete 2>/dev/null || true
else
    echo "[WARN] $(date '+%F %T') Sin offsite en .env (BACKUP_B2_* o BACKUP_RCLONE_REMOTE): NO hay copia fuera del VPS."
fi

echo "[OK]   $(date '+%F %T') Backup completo."
