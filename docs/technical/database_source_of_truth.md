# Fuente de verdad local de base de datos

## Estado actual

La base activa para desarrollo es `medisoft_hoteles_import`.

El archivo `.env` del proyecto apunta a:

```env
DB_NAME=medisoft_hoteles_import
```

La base `medisoft_hoteles` se conserva como base secundaria historica y posible fuente legacy de datos operativos, pero no debe usarse como base activa ni mezclarse con `medisoft_hoteles_import` sin una fase de migracion aprobada.

## Roles de cada base

| Base | Rol recomendado | Uso permitido |
| --- | --- | --- |
| `medisoft_hoteles_import` | Fuente de verdad tecnica actual | Desarrollo activo, SaaS/multihotel, migraciones nuevas, validaciones. |
| `medisoft_hoteles` | Respaldo historico / fuente legacy | Consulta comparativa y planeacion de reconciliacion de datos. |

## Riesgos de mezclar bases

- `medisoft_hoteles_import` tiene tablas SaaS, `hotel_id`, `hotel_modulos`, `logs_auditoria` y migraciones registradas.
- `medisoft_hoteles` tiene mas volumen historico operativo, pero esta atrasada en SaaS/multihotel.
- Copiar datos operativos sin reconciliacion puede duplicar reservaciones, cortes, movimientos de caja, huespedes o inventario.
- Los IDs primarios no deben asumirse equivalentes entre bases.
- Las tablas duplicadas documentadas en `docs/technical/duplicated_tables.md` deben resolverse antes de cualquier fusion.

## Backup recomendado

Antes de cualquier cambio de DB:

```bash
docker compose exec -T db mysqldump --single-transaction --quick --routines --triggers -uroot -proot_pass medisoft_hoteles_import > backups/db/YYYYMMDD_HHMMSS_medisoft_hoteles_import_pre.sql
docker compose exec -T db mysqldump --single-transaction --quick --routines --triggers -uroot -proot_pass medisoft_hoteles > backups/db/YYYYMMDD_HHMMSS_medisoft_hoteles_pre.sql
```

Desde Fase 1C, `vista_caja_actual` fue recreada con `SQL SECURITY INVOKER` para evitar fallos por definer heredado. Si vuelve a romper dumps por un definer invalido, usar temporalmente:

```bash
docker compose exec -T db mysqldump --single-transaction --quick --routines --triggers --ignore-table=medisoft_hoteles_import.vista_caja_actual -uroot -proot_pass medisoft_hoteles_import > backups/db/YYYYMMDD_HHMMSS_medisoft_hoteles_import_pre_ignore_broken_view.sql
```

## Validaciones antes de migrar datos legacy

Antes de traer datos desde `medisoft_hoteles` hacia `medisoft_hoteles_import`:

1. Confirmar backup reciente de ambas bases.
2. Comparar conteos por tabla critica.
3. Identificar hotel destino por `hotel_id`.
4. Mapear IDs de habitaciones, huespedes, reservaciones, cajas, cortes, productos y usuarios.
5. Validar llaves foraneas y registros huerfanos.
6. Definir estrategia para tablas duplicadas.
7. Ejecutar primero en entorno local con script idempotente.
8. Ejecutar health check y verificadores multihotel despues de la importacion.

## Regla operativa

Todo desarrollo nuevo debe apuntar a `medisoft_hoteles_import`.

`medisoft_hoteles` no debe recibir migraciones nuevas ni cambios manuales mientras sea respaldo historico.
