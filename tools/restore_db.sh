#!/bin/bash
# Restauracion de un backup de Medisoft Hoteles.
#
# Uso:
#   bash tools/restore_db.sh backups/db/archivo.sql.gz --verificar
#       Restaura en una BD TEMPORAL (<db>_verificacion), cuenta tablas y la
#       borra. NO toca la BD real. Correr esto periodicamente: un backup que
#       nunca se probo restaurar es una esperanza, no un backup.
#
#   bash tools/restore_db.sh backups/db/archivo.sql.gz --real
#       RESTAURA SOBRE LA BD REAL (pide teclear el nombre de la BD para
#       confirmar). Usar solo en recuperacion de desastre.
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_DIR"

ARCHIVO="${1:-}"
MODO="${2:-}"

if [ -z "$ARCHIVO" ] || [ ! -f "$ARCHIVO" ] || { [ "$MODO" != "--verificar" ] && [ "$MODO" != "--real" ]; }; then
    echo "Uso: bash tools/restore_db.sh <backups/db/archivo.sql.gz> --verificar | --real"
    exit 1
fi

env_val() {
    grep -E "^${1}=" .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r'
}
DB_NAME="$(env_val DB_NAME)"; DB_NAME="${DB_NAME:-medisoft_hoteles}"
DB_ROOT_PASS="$(env_val DB_ROOT_PASS)"
if [ -z "$DB_ROOT_PASS" ]; then
    echo "[ERROR] DB_ROOT_PASS no esta en .env."
    exit 1
fi

CONTENEDOR_DB="medisoft_hoteles_db"

mysql_root() {
    docker exec -i -e MYSQL_PWD="$DB_ROOT_PASS" "$CONTENEDOR_DB" mysql -uroot "$@"
}

if ! gzip -t "$ARCHIVO" 2>/dev/null; then
    echo "[ERROR] El archivo no es un gzip valido: $ARCHIVO"
    exit 1
fi

if [ "$MODO" = "--verificar" ]; then
    DB_TEST="${DB_NAME}_verificacion"
    echo "[INFO] Restaurando en BD temporal ${DB_TEST} (la real no se toca)..."
    mysql_root -e "DROP DATABASE IF EXISTS \`$DB_TEST\`; CREATE DATABASE \`$DB_TEST\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    zcat "$ARCHIVO" | mysql_root "$DB_TEST"
    TABLAS=$(mysql_root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_TEST';")
    HOTELES=$(mysql_root -N -e "SELECT COUNT(*) FROM \`$DB_TEST\`.hoteles;" 2>/dev/null || echo "?")
    mysql_root -e "DROP DATABASE \`$DB_TEST\`;"
    echo "[OK]   Backup RESTAURABLE: $TABLAS tablas, $HOTELES hoteles. BD temporal borrada."
    exit 0
fi

# ── Modo --real: recuperacion de desastre ────────────────────────────────
echo "⚠️  VAS A SOBREESCRIBIR LA BASE DE DATOS REAL '${DB_NAME}' con:"
echo "    $ARCHIVO"
echo "    Todo lo capturado despues de ese backup SE PIERDE."
read -r -p "Para continuar, teclea el nombre exacto de la BD: " CONFIRMA
if [ "$CONFIRMA" != "$DB_NAME" ]; then
    echo "[ABORTADO] La confirmacion no coincide."
    exit 1
fi

echo "[INFO] Restaurando ${DB_NAME}..."
zcat "$ARCHIVO" | mysql_root "$DB_NAME"
TABLAS=$(mysql_root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME';")
echo "[OK]   Restauracion completa: $TABLAS tablas en ${DB_NAME}."
echo "[INFO] Recrear el contenedor app para limpiar cualquier estado: docker compose up -d app"
