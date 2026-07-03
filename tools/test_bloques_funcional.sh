#!/bin/bash
# Testeo funcional del sistema de bloques comerciales (hotel demo, id 2).
# Con sesion real: enciende/apaga bloques y verifica gates HTTP, menu y core.
# Uso (host): bash tools/test_bloques_funcional.sh
set -u

BASE="http://localhost:8080"
SLUG="hotel-demo-saas"
HOTEL_ID=2
JAR=$(mktemp)
FALLOS=0

sql() {
  docker exec medisoft_hoteles_db sh -c "mysql -umedisoft_user -pmedisoft_pass medisoft_hoteles_import -N -e \"$1\"" 2>/dev/null
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

echo "=== Testeo funcional de bloques comerciales — Hotel Demo SaaS ==="
echo

# ── Login real con usuario QA ──
curl -s -c "$JAR" "$BASE/h/$SLUG/login" -o /tmp/login.html
CSRF=$(grep -o 'name="csrf_token" value="[^"]*"' /tmp/login.html | head -1 | sed 's/.*value="//;s/"//')
if [ -z "$CSRF" ]; then echo "ABORT: no se pudo extraer CSRF del login"; exit 1; fi

curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/h/$SLUG/login/authenticate" \
  --data-urlencode "nombre_usuario=qa_bloques" \
  --data-urlencode "password=QaBloques2026!" \
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
check "ia OFF -> Asesor IA bloqueado" "302" "$(status_de "ia/resumen-diario")"
toggle ia_ejecutiva 1
check "ia ON  -> Asesor IA accesible" "200" "$(status_de "ia/resumen-diario")"

echo
echo "── Bloque 'motor_reservas' (pagina publica, sin sesion) ──"
toggle motor_reservas 0
check "motor OFF -> pagina publica 404 amable" "404" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/h/$SLUG/reservar")"
toggle motor_reservas 1
check "motor ON  -> pagina publica viva" "200" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/h/$SLUG/reservar")"

echo
echo "── Inmunidad de bloques CORE (es_core) ──"
sql "DELETE FROM hotel_modulos WHERE hotel_id=$HOTEL_ID AND modulo_id=(SELECT id FROM modulos WHERE clave='caja');"
check "caja SIN fila en hotel_modulos -> sigue accesible (core)" "200" "$(status_de caja)"
check "reservaciones (core) accesible" "200" "$(status_de reservaciones)"
sql "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at, updated_at) SELECT $HOTEL_ID, id, 1, 'manual', NOW(), NOW(), NOW() FROM modulos WHERE clave='caja';"

echo
echo "── Cobro mensual refleja los bloques ──"
COBRO=$(sql "SELECT CONCAT(COUNT(*), '|', COALESCE(SUM(COALESCE(hm.precio_override, m.precio_mensual)),0)) FROM modulos m INNER JOIN hotel_modulos hm ON hm.modulo_id=m.id AND hm.hotel_id=$HOTEL_ID WHERE m.activo_global=1 AND m.es_core=0 AND hm.activo=1;")
echo "  INFO  bloques opcionales activos|suma mensual: $COBRO"

rm -f "$JAR" /tmp/login.html
echo
if [ "$FALLOS" -eq 0 ]; then
  echo "=== RESULTADO: TODAS LAS PRUEBAS PASARON ==="
  exit 0
else
  echo "=== RESULTADO: $FALLOS FALLO(S) ==="
  exit 1
fi
