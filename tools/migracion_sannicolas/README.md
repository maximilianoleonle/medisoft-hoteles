# Migración legacy San Nicolás → hotel_id=3 (producción)

Migra TODO el sistema viejo monohotel (Hostinger, `u377797534_hotel_san_nico`) al hotel 3
del multihotel en el VPS. **Re-ejecutable**: cada corrida borra lo importado por la anterior
(mapas + registro de usuarios creados en el esquema staging) y reimporta — el día del corte
se repite con dump fresco. Ensayo local 2026-07-29: **38 checks, 0 FAIL** + QA de app real
(dashboard/habitaciones/reservaciones/caja/tarifas del hotel 3 renderizando datos migrados).

## Decisiones de mapeo (congeladas en el ensayo 2026-07-29)

- **Tipos jacuzzi COLAPSAN al tipo base** (`doble_jacuzzi`→doble, `sencilla_jacuzzi`→sencilla):
  es la convención viva de la app (`hotel_room_catalog_type_aliases`). El matiz sigue en
  `caracteristicas`, en el precio por cuarto y en el catálogo `tipos_habitacion` (6 códigos del
  legado migran con hotel_id=3 y alimentan el catálogo por hotel). Los pisos sótano (−1/−2/−4)
  ya existen en el catálogo default de la app — cero seeding.
- **Usuarios**: fusión por `nombre_usuario` (case-insensitive) → solo `admin` fusiona con el de
  prod (id 4, mismo nombre completo; conserva la contraseña DE PROD). Los otros 6 se crean con su
  hash bcrypt del legado (jesus, Peter, Fernando, Esteban, maxl **inactivo**, Jardiel). Los 7
  reciben membresía en hotel 3 con el rol homónimo sembrado. `maxl` parece cuenta de prueba:
  decidir si se borra después.
- **Caja**: 143 cortes (cerrados verbatim — sus 53 descuadres son herencia del legado); el ÚNICO
  abierto (50 días) migra CERRADO con totales derivados de sus movimientos, contado=esperado,
  diferencia 0 y nota `[Migracion]`. Caja legada 1 → caja 3. Las 54 categorías (46 con `tipo=''`
  por el bug MariaDB del enum 'egreso', casi todas "Devoluciones" duplicadas) se normalizan a
  **9** por (nombre, tipo inferido del uso dominante, default gasto).
- **Huérfanos FK del legado**: se EXCLUYEN 25 reservacion_habitaciones, 10 pagos, 12 notas y
  13 vehículos (sin padre en el propio legado); se conservan con FK NULL: 18 movimientos con
  corte/reservación rotos, 2 con categoría rota, 280 historial_llaves y 21 historial_remotos con
  reservación rota, 27 logs y 6 movs de inventario sin usuario. Los 25 mantenimientos (todos con
  usuario huérfano) caen al admin fusionado. `verificar.sql` cuantifica cada exclusión.
- **Inventario**: migra el v2 (el que la app usa): 4 categorías, 13 productos, 1,274 movimientos,
  y la config por tipo SOLO en tipos base (32 de 50 filas; las 18 jacuzzi duplicarían la unique
  tras el colapso). **"Baño de burbujas"** (exclusivo de cuartos jacuzzi) queda SIN descuento
  automático — descontar a mano o aceptarlo. NO migran (muertas en la app, nadie las referencia):
  `productos` v1 (5), `categorias_producto` (4), `inventario_movimientos` (85),
  `inventario_habitacion_config` (198), `alertas_inventario`, `remember_tokens` (sesiones de otro
  dominio), `push_subscriptions`/`sync_queue`/`tarifas_temporada`/`configuracion` (vacías).
- **Tarifas dinámicas**: 4 incrementos monto_fijo por habitación; el JSON de ids se remapea id
  por id (strings, convención TarifasController) y `clase='incremento'`. OJO: "Diciembre" +50 y
  "Diciembre incremento 100" +100 son **permanentes y ACTIVOS** — los precios del multihotel
  seguirán incluyéndolos, igual que el legado hoy.
- Cada reservación lleva nota "Folio del sistema anterior: #N". Fechas en sesión `-06:00`.

## Corrida local (ensayo contra clon)

```bash
# 1) staging con el dump legado (con --force: las 2 vistas del dump están rotas y es esperado;
#    los ÚNICOS errores tolerables son vista_caja_actual y vista_productos_alerta)
docker exec medisoft_hoteles_db mysql -uroot -proot_pass -e "DROP DATABASE IF EXISTS sannicolas_legacy; CREATE DATABASE sannicolas_legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
(echo "SET FOREIGN_KEY_CHECKS=0;"; cat dump_legacy.sql; echo "SET FOREIGN_KEY_CHECKS=1;") \
  | docker exec -i medisoft_hoteles_db mysql --force -uroot -proot_pass sannicolas_legacy
# 2) clon: estructura --no-data de prod + prod_config (hoteles/roles/cajas/modulos/planes/
#    hotel_modulos/saas_admins) + seed_clon_local.sql (usuarios placeholder + centinelas con los
#    MAX ids de prod). Ver historial de la sesión 2026-07-29; el clon ya existe en la PC principal.
# 3) migrar + verificar (38 checks; exit 1 si algo FALLA)
./correr.sh migrar    sannicolas_legacy medisoft_prod_clone
./correr.sh verificar sannicolas_legacy medisoft_prod_clone
```

## Día del corte (switchover definitivo)

1. El hotel cierra su corte de caja en el sistema viejo y congela capturas (~1 h).
   Tomar dump fresco desde phpMyAdmin de Hostinger.
2. **Backup de prod obligatorio** antes de tocar nada.
3. Cargar staging + migrar + verificar (todo por SSH desde cualquier PC, sin copiar archivos):

```bash
V="ssh -i ~/.ssh/medisoft_vps root@216.238.90.237"
M="docker exec -i medisoft_hoteles_db sh -c 'mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --table'"
# 0) BACKUP
$V "docker exec medisoft_hoteles_db sh -c 'mysqldump -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --single-transaction medisoft_hoteles' | gzip > /opt/medisoft/backups/pre_migracion_sn_\$(date +%Y%m%d_%H%M).sql.gz"
# 1) staging con dump fresco (--force por las 2 vistas rotas del dump)
$V "docker exec medisoft_hoteles_db sh -c 'mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" -e \"DROP DATABASE IF EXISTS sannicolas_legacy; CREATE DATABASE sannicolas_legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"'"
(echo "SET FOREIGN_KEY_CHECKS=0;"; cat dump_legacy.sql; echo "SET FOREIGN_KEY_CHECKS=1;") | $V "docker exec -i medisoft_hoteles_db sh -c 'mysql --force -uroot -p\"\$MYSQL_ROOT_PASSWORD\" sannicolas_legacy'"
# 2) migrar + verificar (cero FAIL o rollback con el backup)
sed -e 's/@@SRC@@/sannicolas_legacy/g' -e 's/@@DST@@/medisoft_hoteles/g' migrar.sql    | $V "$M" | grep -av 'Using a password'
sed -e 's/@@SRC@@/sannicolas_legacy/g' -e 's/@@DST@@/medisoft_hoteles/g' verificar.sql | $V "$M" | grep -av 'Using a password'   # cero FAIL
# 3) fotos (2 archivos de la habitación 01; descargarlos antes del uploads/habitaciones/ de Hostinger)
scp -i ~/.ssh/medisoft_vps san_nicolas_hab_01_*.jpg root@216.238.90.237:/opt/medisoft/src/public_html/uploads/habitaciones/
$V "chown www-data:www-data /opt/medisoft/src/public_html/uploads/habitaciones/san_nicolas_hab_01_*"
```

4. En el resumen de migrar: `usuarios_fusionados` debe ser **1** (solo admin). Si sale más, un
   usuario nuevo de prod colisionó por username con uno del legado — revisar antes de seguir.
5. **Abrir la caja del hotel 3 de inmediato** (monto inicial real): el libro nace acotado al turno
   y se evita la carga histórica completa sin filtro.
6. Panel SaaS → hotel 3: configurar Marca (branding), hora de check-out y demás ajustes
   (`hotel_configuracion` del hotel 3 está virgen: 1 sola clave). Los módulos ya los configuró
   el owner (52 filas en hotel_modulos).
7. El personal entra a medisoft-hoteles.com con su usuario y contraseña de siempre
   (admin usa la contraseña DE PROD, no la del legado).

## Hallazgos de la auditoría adversarial (3 auditores en 2 tandas, 2026-07-29 — cero severidad alta)

**Decisiones que Maximiliano debe tomar ANTES del día D:**

1. **20 confirmadas futuras descuadradas** (detalle por habitación suma $18,850 MÁS que sus
   `precio_total`; herencia 1:1 del legado, folios legados 2213–2685). El check 38 las exhibe.
   Riesgo: la cotización PDF/ver.php pintan un desglose que no suma el total, y si alguien corre
   **"Revisar reservaciones existentes" de tarifas y APLICA**, el total pactado se re-deriva y
   se pisa. Opciones: sincronizar `rh.precio` al header en migrar.sql (el header es lo pactado y
   lo que cobra el check-in), o no tocar y prohibir "aplicar" sobre ellas. HOY migran verbatim.
2. **La cuenta `admin`**: fusiona con el admin de prod (misma persona en apariencia) y rige la
   contraseña DE PROD. Si quien operaba como `admin` en San Nicolás es personal del hotel (no el
   owner), mejor NO fusionar: crear `admin_sn` con el hash del legado y membresía solo en hotel 3.
   Ojo: 15 intentos fallidos con la contraseña vieja bloquean la CUENTA 30 min (afecta hotel 2).
3. **"Baño de burbujas"**: era la única config de inventario exclusiva de los 4 cuartos jacuzzi
   (45, 49, 62, 63; 2.00/estancia en doble jacuzzi, 1.00 en sencilla). Tras el colapso queda SIN
   descuento automático (stock 225 se inflará). Opciones: salidas manuales al limpiar esos 4
   cuartos, o desactivar el producto para no fingir control. Las otras 16 filas jacuzzi excluidas
   eran duplicados exactos de su tipo base: cero pérdida.
4. **117 confirmadas futuras = $892,000 con $0 pagado**: el legado dejó de registrar pagos/caja
   en abril 2026 (las reservas siguieron capturándose hasta la víspera del dump). /cuentas-por-cobrar
   abrirá con las 117 al 100% de saldo. Preguntar al hotelero si cobró anticipos por fuera
   (abril–julio) y capturarlos como abonos el día D. Además $20,800 en 10 pagos de folios que el
   legado borró en duro (279–284, 305–315, 1108, oct–dic 2025) no son representables: pérdida
   histórica documentada, no error.

**Menores (resueltos o solo para saber):** la categoría "Otros" quedó tipo `ingreso` con 40
gastos históricos colgados (fiel al legado; el dinero no miente porque todo agrega por el tipo del
MOVIMIENTO, pero el selector de gastos no la ofrecerá — opcional cambiarla a `ambos`); las 2
reservaciones con llegada vencida (4448/4446 en el ensayo) se resuelven como no-show tras el
switchover o bloquearán sus cuartos; la portada duplicada de imágenes ya se dedupe en migrar.sql;
el diff de módulos hotel 2 vs 3 (descuentos/lavandería ON solo en 3; facturación/promociones/
upsells/copiloto ON solo en 2) hay que confirmarlo como intencional.

## Esperables post-migración (no perseguir como bugs)

- `/caja/arqueo-metodos` del hotel 3: 18+18 en ERROR (movimientos sin corte del legado) y 53
  warnings de cortes descuadrados — herencia del legado, no lo creó la migración.
- El corte legado que estuvo abierto 50 días (feb→abr 2026) aparece CERRADO, cuadrado en 0, y sus
  cifras viven en el mes de su APERTURA (febrero) en el historial.
- Cuartos jacuzzi se ven como doble/sencilla con "jacuzzi" en características y su precio propio;
  "Baño de burbujas" no se descuenta solo del inventario.

## Gotchas aprendidos (no repetir)

- El dump legado phpMyAdmin/MariaDB trae 2 vistas ROTAS (`vista_caja_actual` referencia una
  columna inexistente) → SIEMPRE cargar staging con `--force` o los ALTER de índices del final
  del archivo no corren (el primer intento dejó 39 tablas sin PK).
- MariaDB laxa guardó `''` en enums (46 categorías) → MySQL 8 estricto lo rechaza: normalizar en
  la migración, jamás copiar el enum crudo.
- Todo INSERT cuyo id sea referenciado por un mapa debe llevar **id explícito del mapa**: dejarlo
  al AUTO_INCREMENT pasa la primera corrida y truena la segunda (FK a ids fantasma).
- Los DELETE multi-tabla exigen `USE <bd>` aunque todo venga calificado.
- `JSON_TABLE` en un JOIN va con COMA (`FROM t, JSON_TABLE(...)`), no con `JOIN` a secas.
- La migración toma su **foto de tenancy** (`_tenancy_pre`) tras la limpieza: verificar.sql prueba
  con ella que los demás hoteles quedaron intactos también el día D.
