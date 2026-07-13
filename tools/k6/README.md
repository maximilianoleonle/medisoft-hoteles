# Pruebas de carga (k6) — Medisoft Hoteles

Mide cómo responde la app bajo usuarios concurrentes. No requiere instalar k6:
usa la imagen `grafana/k6` en la red de docker-compose.

## Preparar (una vez por corrida)

Crear usuarios de prueba en la BD que se vaya a golpear (dev o staging):

```bash
docker exec medisoft_hoteles_app php -r '
$pdo = new PDO("mysql:host=db;dbname=medisoft_hoteles_import;charset=utf8mb4","medisoft_user","medisoft_pass");
$hash = password_hash("K6carga!2026", PASSWORD_DEFAULT);
$u = $pdo->prepare("INSERT INTO usuarios (nombre_usuario,password,nombre_completo,rol,activo,created_at) VALUES (?,?,?,\"administrador\",1,NOW()) ON DUPLICATE KEY UPDATE activo=1, password=VALUES(password)");
$h = $pdo->prepare("INSERT INTO hotel_usuarios (hotel_id,usuario_id,rol,es_principal,activo,created_at) VALUES (1,?,\"recepcionista\",0,1,NOW())");
for ($i=1;$i<=100;$i++){ $n=sprintf("qa_k6_%03d",$i); $u->execute([$n,$hash,"K6 $i"]); $id=(int)$pdo->lastInsertId(); if($id)$h->execute([$id]); }
echo "listo\n";'
```

## Correr

```bash
# Humo (validar): 5 VUs, 1 min
docker run --rm -i --network=medisoft-hoteles_default -e ESCENARIO=humo   grafana/k6 run - < tools/k6/carga.js

# Carga realista (≈100 usuarios reales): 20 VUs, 4 min
docker run --rm -i --network=medisoft-hoteles_default -e ESCENARIO=carga  grafana/k6 run - < tools/k6/carga.js

# Estrés (punto de quiebre): rampa 0→120 VUs
docker run --rm -i --network=medisoft-hoteles_default -e ESCENARIO=estres grafana/k6 run - < tools/k6/carga.js
```

Contra el VPS/staging: agregar `-e BASE_URL=https://staging.tudominio.com`.

## Limpiar (al terminar)

```bash
docker exec medisoft_hoteles_db mysql -umedisoft_user -pmedisoft_pass medisoft_hoteles_import \
  -e "DELETE hu FROM hotel_usuarios hu JOIN usuarios u ON u.id=hu.usuario_id WHERE u.nombre_usuario LIKE 'qa_k6_%'; DELETE FROM usuarios WHERE nombre_usuario LIKE 'qa_k6_%';"
```

## Interpretar

- **1 VU sin think-time ≈ 5-10 usuarios reales.** 20 VUs con pausas ≈ 100 reales.
- Mirar: `http_req_failed` (debe ser ~0%), `sesion_perdida` (0%), y el
  `p(95)` por vista (meta operativa: <500ms en vistas, <150ms en APIs).
- El escenario **estrés** no aprueba/reprueba: muestra a qué carga empieza a
  degradar y QUÉ vista se degrada primero (la candidata a optimizar).

## Límite conocido de esta prueba

Solo ejercita LECTURAS (dashboard, vistas, APIs de snapshot) — el 90% del
tráfico real. NO prueba escrituras concurrentes de dinero (abrir/cerrar caja,
registrar movimientos a la vez), que es donde los candados FOR UPDATE se
tensan. Esa prueba se hace aparte, contra `medisoft_test`, con un escenario
de POSTs — pendiente. Historia de la primera corrida: `docs/prueba-carga-baseline.md`.
