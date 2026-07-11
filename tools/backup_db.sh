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
#  - Retencion: BD 30 dias, uploads 7 dias.
#  - Falla RUIDOSAMENTE (exit != 0 y [ERROR] en el log) si algo sale mal:
#    un backup que falla en silencio es peor que no tener backup.
#
# Restaurar: ver tools/restore_db.sh
# Offsite (recomendado siguiente paso): sincronizar backups/ a un bucket
# externo con rclone/S3 — un backup en el mismo VPS no sobrevive al VPS.
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_DIR"

# ── Credenciales desde .env (sin exportarlas al ambiente) ────────────────
env_val() {
    grep -E "^${1}=" .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r'
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

# ── 4) Offsite (rclone) ──────────────────────────────────────────────────
# Un backup en el mismo VPS no sobrevive al VPS. Configurar una vez:
#   1. rclone config          (crear remote, ej. Backblaze B2 / Cloudflare R2 / S3)
#   2. En .env:  BACKUP_RCLONE_REMOTE=miremoto:medisoft-backups
# Si la variable no esta definida, este paso se omite con aviso (no rompe el
# backup local, pero queda registrado que NO hay copia externa).
RCLONE_REMOTE="$(env_val BACKUP_RCLONE_REMOTE)"
if [ -n "$RCLONE_REMOTE" ]; then
    if ! command -v rclone >/dev/null 2>&1; then
        echo "[ERROR] $(date '+%F %T') BACKUP_RCLONE_REMOTE definido pero rclone no esta instalado."
        exit 1
    fi
    if rclone copy "$ARCHIVO_DB" "$RCLONE_REMOTE/db/" --no-traverse \
        && { [ ! -f "$ARCHIVO_UP" ] || rclone copy "$ARCHIVO_UP" "$RCLONE_REMOTE/uploads/" --no-traverse; }; then
        echo "[OK]   $(date '+%F %T') Copia offsite subida a $RCLONE_REMOTE."
        # Retencion remota alineada a la local (30 dias BD, 7 uploads).
        rclone delete "$RCLONE_REMOTE/db/" --min-age 30d 2>/dev/null || true
        rclone delete "$RCLONE_REMOTE/uploads/" --min-age 7d 2>/dev/null || true
    else
        echo "[ERROR] $(date '+%F %T') Fallo la subida offsite a $RCLONE_REMOTE."
        exit 1
    fi
else
    echo "[WARN] $(date '+%F %T') Sin BACKUP_RCLONE_REMOTE en .env: NO hay copia fuera del VPS."
fi

echo "[OK]   $(date '+%F %T') Backup completo."
