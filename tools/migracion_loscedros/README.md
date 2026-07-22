# Migración legacy Los Cedros → hotel_id=2 (producción)

Migra TODO el sistema viejo monohotel (Hostinger, `u377797534_loscedrossiste`) al hotel 2
del multihotel en el VPS. **Re-ejecutable**: cada corrida borra lo importado por la anterior
(mapas en el esquema staging) y reimporta — así el día del corte se repite con dump fresco.

Decisiones de negocio congeladas (Maximiliano, 2026-07-21): migrar TODO (usuarios con su
misma contraseña, dinero histórico como cortes CERRADOS, llaves, inventario, logs, facturas);
tipos `*_manolo` → sencilla/doble + dueño por `propietarios.distribucion` (9 asignaciones por
número, regla substring vacía, Manolo/Elia 100%, default elia, sin usuario para Manolo);
usuarios se FUSIONAN por nombre con los ya existentes en prod (Maximiliano→maximiliano,
Rafael→rafael) y se crean los faltantes; nota "Folio del sistema anterior: #N" en cada
reservación; se respeta el catálogo de tipos ya configurado en prod (no se pisa
`catalogos.*`, `ical.*`, branding ni ninguna otra clave — solo `propietarios.distribucion`,
con respaldo previo en `_backup_hotel_configuracion` del staging).

## Corrida local (ensayo contra clon)

```bash
# 1) cargar dump legacy y clon de prod (huérfanos reales → FK checks off)
(echo "SET FOREIGN_KEY_CHECKS=0;"; cat dump_legacy.sql; echo "SET FOREIGN_KEY_CHECKS=1;") \
  | docker exec -i medisoft_hoteles_db mysql -uroot -proot_pass loscedros_legacy
# 2) migrar + verificar (26 checks; exit 1 si algo FALLA)
./correr.sh migrar    loscedros_legacy medisoft_prod_clone
./correr.sh verificar loscedros_legacy medisoft_prod_clone
```

## Corrida en el VPS (desde cualquier PC, sin copiar archivos)

```bash
V="ssh -i ~/.ssh/medisoft_vps root@216.238.90.237"
M="docker exec -i medisoft_hoteles_db sh -c 'mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --table'"
# 0) BACKUP OBLIGATORIO de prod ANTES de tocar nada
$V "docker exec medisoft_hoteles_db sh -c 'mysqldump -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --single-transaction medisoft_hoteles' | gzip > /opt/medisoft/backups/pre_migracion_\$(date +%Y%m%d_%H%M).sql.gz"
# 1) staging con el dump legacy
$V "docker exec medisoft_hoteles_db sh -c 'mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" -e \"DROP DATABASE IF EXISTS loscedros_legacy; CREATE DATABASE loscedros_legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"'"
(echo "SET FOREIGN_KEY_CHECKS=0;"; cat dump_legacy.sql; echo "SET FOREIGN_KEY_CHECKS=1;") | $V "docker exec -i medisoft_hoteles_db sh -c 'mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" loscedros_legacy'"
# 2) migrar + verificar (el SQL viaja por stdin, sustituido aquí)
sed -e 's/@@SRC@@/loscedros_legacy/g' -e 's/@@DST@@/medisoft_hoteles/g' migrar.sql    | $V "$M" | grep -av 'Using a password'
sed -e 's/@@SRC@@/loscedros_legacy/g' -e 's/@@DST@@/medisoft_hoteles/g' verificar.sql | $V "$M" | grep -av 'Using a password'   # cero FAIL
# 3) fotos (2 de la habitación AMARILLO; no viajan en el dump)
scp -i ~/.ssh/medisoft_vps src/public_html/uploads/habitaciones/san_nicolas_hab_AMARILLO_*.jpeg root@216.238.90.237:/opt/medisoft/src/public_html/uploads/habitaciones/
```

## Día del corte (switchover definitivo)

1. El hotel cierra el corte de caja abierto en el sistema viejo y resuelve los check-outs del día.
2. Congelar capturas en el viejo (~1 h). Tomar dump fresco desde phpMyAdmin de Hostinger.
3. Backup de prod (paso 0) → recargar staging con el dump fresco (paso 1) → migrar + verificar (paso 2).
   La re-corrida borra sola el ensayo anterior; no hay que limpiar nada a mano.
4. Activar en `/admin/saas` los bloques que el hotel usará (facturación, inventario, llaves…):
   los datos ya migrados existen aunque el módulo esté apagado, solo no se ven.
5. El personal entra a medisoft-hoteles.com con su usuario y contraseña de siempre.

## Gotchas aprendidos (no repetir)

- Variable de usuario MySQL degrada JSON a string → `CAST(@var AS JSON)` al re-embeber (bug real del primer ensayo).
- `subtotal` de `denominaciones_efectivo` es columna GENERADA: no se inserta.
- Todo corre server-side en UNA sesión con `SET time_zone='-06:00'` (convención Database.php); un cliente externo corre la historia 6 horas.
- El dump legacy trae huérfanos FK reales (268 historial_llaves, etc.): staging se carga con FK checks off; la migración los excluye o los aNULLa según la tabla (verificar.sql los cuantifica).
- No correr la suite de tests local durante la migración local: compite por disco y la alenta ×10.
