#!/usr/bin/env bash
# Migración legacy San Nicolás → hotel_id=3. Sustituye placeholders y ejecuta.
# Uso LOCAL (Git Bash, contenedor medisoft_hoteles_db):
#   ./correr.sh migrar    sannicolas_legacy medisoft_prod_clone
#   ./correr.sh verificar sannicolas_legacy medisoft_prod_clone
# Uso VPS (dentro del host, contenedor medisoft_hoteles_db de prod):
#   MYSQL_CMD="docker exec -i medisoft_hoteles_db sh -c 'mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --table'" \
#     ./correr.sh migrar sannicolas_legacy medisoft_hoteles
# La verificación regresa exit 1 si alguna fila sale FAIL.
set -euo pipefail
cd "$(dirname "$0")"

ACCION="${1:?uso: correr.sh migrar|verificar SRC DST}"
SRC="${2:?falta esquema SRC}"
DST="${3:?falta esquema DST}"
MYSQL_CMD="${MYSQL_CMD:-docker exec -i medisoft_hoteles_db mysql -uroot -proot_pass --table}"

case "$ACCION" in
  migrar)    ARCHIVO=migrar.sql ;;
  verificar) ARCHIVO=verificar.sql ;;
  *) echo "accion invalida: $ACCION" >&2; exit 2 ;;
esac

SALIDA=$(sed -e "s/@@SRC@@/${SRC}/g" -e "s/@@DST@@/${DST}/g" "$ARCHIVO" | eval "$MYSQL_CMD" 2>&1 | grep -av "Using a password")
echo "$SALIDA"

if [ "$ACCION" = "verificar" ] && echo "$SALIDA" | grep -q "FAIL"; then
  echo "== VERIFICACION CON FALLAS ==" >&2
  exit 1
fi
