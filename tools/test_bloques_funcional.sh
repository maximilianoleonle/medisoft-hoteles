#!/bin/bash
# Testeo funcional del sistema de bloques comerciales.
# Con sesion real: enciende/apaga bloques y verifica gates HTTP, menu y core.
# Requiere variables de entorno; nunca versionar credenciales QA aqui.
# Uso (host): bash tools/test_bloques_funcional.sh
set -uo pipefail

BASE="${QA_BASE_URL:-${APP_URL:-http://localhost:8080}}"
SLUG="${QA_HOTEL_SLUG:-}"
HOTEL_ID="${QA_HOTEL_ID:-}"
QA_USER="${QA_BLOQUES_USER:-}"
QA_PASS="${QA_BLOQUES_PASSWORD:-}"
DB_CONTAINER="${QA_DB_CONTAINER:-medisoft_hoteles_db}"
DB_NAME_VALUE="${DB_NAME:-}"
DB_USER_VALUE="${DB_USER:-}"
DB_PASS_VALUE="${DB_PASS:-}"
FALLOS=0
ESTADOS_ORIGINALES=""

abortar() {
  echo "ABORT: $1" >&2
  exit 2
}

case "$BASE" in
  http://localhost:*|https://localhost:*|http://127.0.0.1:*|https://127.0.0.1:*) ;;
  *)
    [ "${QA_ALLOW_REMOTE:-0}" = "1" ] || abortar "QA_BASE_URL debe ser local; para staging explicito usa QA_ALLOW_REMOTE=1"
    ;;
esac

case "$HOTEL_ID" in
  ''|*[!0-9]*) abortar "QA_HOTEL_ID debe ser un entero positivo" ;;
esac

[ -n "$SLUG" ] || abortar "falta QA_HOTEL_SLUG"
[ -n "$QA_USER" ] || abortar "falta QA_BLOQUES_USER"
[ -n "$QA_PASS" ] || abortar "falta QA_BLOQUES_PASSWORD"
[ -n "$DB_NAME_VALUE" ] || abortar "falta DB_NAME"
[ -n "$DB_USER_VALUE" ] || abortar "falta DB_USER"
[ -n "$DB_PASS_VALUE" ] || abortar "falta DB_PASS"

JAR=$(mktemp)
LOGIN_HTML=$(mktemp)

sql() {
  docker exec -e MYSQL_PWD="$DB_PASS_VALUE" "$DB_CONTAINER" \
    mysql --user="$DB_USER_VALUE" --batch --skip-column-names \
    "$DB_NAME_VALUE" --execute="$1"
}

toggle() { # toggle <clave> <0|1>
  sql "UPDATE hotel_modulos hm JOIN modulos m ON m.id=hm.modulo_id SET hm.activo=$2 WHERE hm.hotel_id=$HOTEL_ID AND m.clave='$1';"
}

status_de() { # status_de <ruta> -> codigo http (sin seguir redirects)
  curl -s -o /dev/null -w "%{http_code}" -b "$JAR" "$BASE/$1"
}

check() { # check <nombre> <esperado> <obtenido>
  if [ "$2" = "$3" ]; then
    echo "  PASS  $1 (HTTP $3)"
  else
    echo "  FAIL  $1 — esperado $2, obtuvo $3"
    FALLOS=$((FALLOS+1))
  fi
}

restaurar() {
  if [ -n "$ESTADOS_ORIGINALES" ]; then
    while IFS='=' read -r clave activo; do
      [ -n "$clave" ] && toggle "$clave" "$activo" >/dev/null 2>&1 || true
    done <<EOF
$ESTADOS_ORIGINALES
EOF
  fi
  rm -f "$JAR" "$LOGIN_HTML"
}

trap restaurar EXIT

sql "SELECT 1" >/dev/null || abortar "no se pudo conectar a la BD QA"

# Guardar el estado exacto de las filas existentes y restaurarlo incluso si
# curl, el login o una asercion fallan. El script ya no elimina/crea filas core.
ESTADOS_ORIGINALES=$(sql "
  SELECT CONCAT(m.clave, '=', hm.activo)
  FROM modulos m
  INNER JOIN hotel_modulos hm ON hm.modulo_id = m.id
  WHERE hm.hotel_id = $HOTEL_ID
    AND m.clave IN ('inventario','exportaciones','ia_ejecutiva','motor_reservas','caja')
  ORDER BY m.clave
") || abortar "no se pudieron guardar los estados originales de modulos"

echo "=== Testeo funcional de bloques comerciales — Hotel Demo SaaS ==="
echo

# ── Login real con usuario QA ──
curl -s -c "$JAR" "$BASE/h/$SLUG/login" -o "$LOGIN_HTML"
CSRF=$(grep -o 'name="csrf_token" value="[^"]*"' "$LOGIN_HTML" | head -1 | sed 's/.*value="//;s/"//')
if [ -z "$CSRF" ]; then echo "ABORT: no se pudo extraer CSRF del login"; exit 1; fi

curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/h/$SLUG/login/authenticate" \
  --data-urlencode "nombre_usuario=$QA_USER" \
  --data-urlencode "password=$QA_PASS" \
  --data-urlencode "csrf_token=$CSRF" -o /dev/null

DASH=$(status_de "dashboard")
check "Login QA y acceso a dashboard" "200" "$DASH"
[ "$DASH" != "200" ] && { echo "ABORT: sin sesion no hay prueba"; exit 1; }

echo
echo "── Bloque 'inventario' (pagina interna) ──"
toggle inventario 1
check "inventario ON  -> /inventario accesible" "200" "$(status_de inventario)"
toggle inventario 0
check "inventario OFF -> /inventario bloqueado (redirect)" "302" "$(status_de inventario)"
HTML=$(curl -s -b "$JAR" "$BASE/dashboard")
if echo "$HTML" | grep -q 'href="[^"]*inventario"'; then
  echo "  FAIL  inventario OFF -> menu aun muestra Inventario"; FALLOS=$((FALLOS+1))
else
  echo "  PASS  inventario OFF -> desaparece del menu"
fi
toggle inventario 1
check "inventario ON de nuevo -> restaurado" "200" "$(status_de inventario)"

echo
echo "── Bloque 'exportaciones' (accion puntual) ──"
toggle exportaciones 0
check "exportaciones OFF -> exportar Excel bloqueado" "302" "$(status_de "reservaciones/exportar-excel?fecha=2026-07-04")"
toggle exportaciones 1
check "exportaciones ON  -> exportar Excel responde" "200" "$(status_de "reservaciones/exportar-excel?fecha=2026-07-04")"

echo
echo "── Bloque 'ia_ejecutiva' ──"
toggle ia_ejecutiva 0
check "ia OFF -> Asesor inteligente bloqueado" "302" "$(status_de "ia/resumen-diario")"
toggle ia_ejecutiva 1
check "ia ON  -> Asesor inteligente accesible" "200" "$(status_de "ia/resumen-diario")"

echo
echo "── Bloque 'motor_reservas' (pagina publica, sin sesion) ──"
toggle motor_reservas 0
check "motor OFF -> pagina publica 404 amable" "404" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/h/$SLUG/reservar")"
toggle motor_reservas 1
check "motor ON  -> pagina publica viva" "200" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/h/$SLUG/reservar")"

echo
echo "── Inmunidad de bloques CORE (es_core) ──"
toggle caja 0
check "caja con activo=0 -> sigue accesible (core)" "200" "$(status_de caja)"
check "reservaciones (core) accesible" "200" "$(status_de reservaciones)"

echo
echo "── Cobro mensual refleja los bloques ──"
COBRO=$(sql "SELECT CONCAT(COUNT(*), '|', COALESCE(SUM(COALESCE(hm.precio_override, m.precio_mensual)),0)) FROM modulos m INNER JOIN hotel_modulos hm ON hm.modulo_id=m.id AND hm.hotel_id=$HOTEL_ID WHERE m.activo_global=1 AND m.es_core=0 AND hm.activo=1;")
echo "  INFO  bloques opcionales activos|suma mensual: $COBRO"

echo
if [ "$FALLOS" -eq 0 ]; then
  echo "=== RESULTADO: TODAS LAS PRUEBAS PASARON ==="
  exit 0
else
  echo "=== RESULTADO: $FALLOS FALLO(S) ==="
  exit 1
fi
