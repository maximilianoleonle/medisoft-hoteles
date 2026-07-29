#!/usr/bin/env bash
# Día D de la migración San Nicolás → hotel 3 de PRODUCCIÓN, en un solo comando.
# CORRER DESDE GIT BASH (no CMD: usa cat/sed y comillas estilo POSIX).
#
#   ./tools/migracion_sannicolas/dia_d.sh "/c/Users/lic_r/Downloads/u377797534_hotel_san_nico.sql"
#
# Hace, en orden y abortando al primer error:
#   1. Respaldo completo de la BD de producción (queda en /opt/medisoft/backups/).
#   2. Recrea el esquema staging en el VPS y le carga el dump del sistema viejo.
#   3. Corre migrar.sql   (re-ejecutable: borra lo importado antes y reimporta).
#   4. Corre verificar.sql y EXIGE cero FAIL; si algo falla, sale con código 1
#      y te dice con qué respaldo revertir.
set -euo pipefail
cd "$(dirname "$0")"

DUMP="${1:-/c/Users/lic_r/Downloads/u377797534_hotel_san_nico.sql}"
LLAVE="${MS_SSH_KEY:-$HOME/.ssh/medisoft_vps}"
HOST="${MS_SSH_HOST:-root@216.238.90.237}"
SRC="${MS_SRC:-sannicolas_legacy}"
DST="${MS_DST:-medisoft_hoteles}"

V=(ssh -i "$LLAVE" "$HOST")
MYSQL_REMOTO="docker exec -i medisoft_hoteles_db sh -c 'mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --table $DST'"

[ -f "$DUMP" ] || { echo "No encuentro el dump: $DUMP" >&2; exit 2; }

echo "== 1/4 Respaldo de producción =="
SELLO=$(date +%Y%m%d_%H%M)
"${V[@]}" "docker exec medisoft_hoteles_db sh -c 'mysqldump -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --single-transaction $DST 2>/dev/null' | gzip > /opt/medisoft/backups/pre_migracion_sn_${SELLO}.sql.gz"
RESPALDO="/opt/medisoft/backups/pre_migracion_sn_${SELLO}.sql.gz"
"${V[@]}" "ls -lh $RESPALDO"

echo "== 2/4 Staging con el sistema viejo =="
"${V[@]}" "docker exec medisoft_hoteles_db sh -c 'mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" -e \"DROP DATABASE IF EXISTS $SRC; CREATE DATABASE $SRC CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"' 2>&1 | grep -av 'Using a password'" || true
# --force: el dump trae 2 vistas rotas de origen; sin él se abortan los índices del final.
{ echo "SET FOREIGN_KEY_CHECKS=0;"; cat "$DUMP"; echo "SET FOREIGN_KEY_CHECKS=1;"; } \
  | "${V[@]}" "docker exec -i medisoft_hoteles_db sh -c 'mysql --force -uroot -p\"\$MYSQL_ROOT_PASSWORD\" $SRC' 2>&1 | grep -av 'Using a password'" \
  | grep -av 'vista_caja_actual\|vista_productos_alerta\|unidades_medida' || true
# El nombre del esquema sale de DATABASE(), no incrustado: evita comillas anidadas frágiles.
TABLAS=$("${V[@]}" "docker exec medisoft_hoteles_db sh -c 'mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" -N $SRC -e \"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()\"' 2>&1 | grep -av 'Using a password'")
TABLAS=$(echo "$TABLAS" | tr -cd '0-9')
echo "staging cargado: $TABLAS tablas (esperadas 39)"
[ "$TABLAS" -ge 39 ] || { echo "El dump no cargó completo. NO se migró nada." >&2; exit 1; }

echo "== 3/4 Migrando a hotel 3 =="
sed -e "s/@@SRC@@/${SRC}/g" -e "s/@@DST@@/${DST}/g" migrar.sql \
  | "${V[@]}" "$MYSQL_REMOTO" 2>&1 | grep -av 'Using a password'

echo "== 4/4 Verificando =="
SALIDA=$(sed -e "s/@@SRC@@/${SRC}/g" -e "s/@@DST@@/${DST}/g" verificar.sql \
  | "${V[@]}" "$MYSQL_REMOTO" 2>&1 | grep -av 'Using a password')
echo "$SALIDA"

if echo "$SALIDA" | grep -q 'FAIL'; then
  echo "" >&2
  echo "== VERIFICACIÓN CON FALLAS: NO entregues el hotel ==" >&2
  echo "Revertir con: ssh -i $LLAVE $HOST \"gunzip -c $RESPALDO | docker exec -i medisoft_hoteles_db sh -c 'mysql -uroot -p\\\"\\\$MYSQL_ROOT_PASSWORD\\\" $DST'\"" >&2
  exit 1
fi

echo ""
echo "MIGRACIÓN COMPLETA Y VERIFICADA. Respaldo previo: $RESPALDO"
echo "Siguiente: abrir la caja del hotel 3, subir las 2 fotos y capturar marca/horarios."
