# Fase 2A.2-FIX-B - Ejecucion local

## Objetivo

Ejecutar localmente la migracion minima que permite el estado `programado` en `mantenimientos_habitaciones.estado`, sin cambiar logica funcional ni tocar modulos sensibles.

## Bug Corregido

`Mantenimiento::programar()` intentaba guardar mantenimientos futuros con:

```php
$data['estado'] = 'programado';
$data['programado'] = 1;
```

La columna `mantenimientos_habitaciones.estado` no admitia ese valor, porque el ENUM solo permitia:

```sql
enum('en_proceso','completado','cancelado')
```

La correccion fue agregar `programado` al final del ENUM, conservando el orden de los valores existentes.

## Base Afectada

```text
medisoft_hoteles_import
```

## Migracion Ejecutada

```text
migrations/20260526_004_fix_estado_programado_mantenimientos.sql
```

Ejecucion autorizada con cliente MySQL por uso de `DELIMITER`:

```powershell
docker exec -i medisoft_hoteles_db mysql -u root -proot_pass medisoft_hoteles_import < migrations/20260526_004_fix_estado_programado_mantenimientos.sql
```

Resultado: migracion ejecutada correctamente y registrada en `migrations`.

## Backups

Backup pre-fix creado:

```text
backups/backup_pre_fix_estado_programado_mantenimientos.sql
```

Nota: no se encontro backup post-fix especifico al momento de documentar esta ejecucion.

## COLUMN_TYPE

Antes:

```text
enum('en_proceso','completado','cancelado')
```

Despues:

```text
enum('en_proceso','completado','cancelado','programado')
```

## Conteo Por Estado

El conteo por estado quedo sin cambios despues de la migracion y de la prueba funcional con rollback:

| Estado | Conteo |
| --- | ---: |
| en_proceso | 1 |
| completado | 8 |
| cancelado | 0 |
| programado | 0 |

## Herramientas SaaS

Resultados posteriores a la migracion:

```text
tools/saas/verificar_estado.php   PASS
tools/saas/preflight_hotel_id.php PASS
```

Resumen:

- `verificar_estado.php`: 57 OK, 0 WARN, 0 ERROR.
- `preflight_hotel_id.php`: 100 OK, 0 WARN, 0 ERROR.

## Prueba Funcional Transaccional

Se ejecuto una prueba controlada con rollback usando `Mantenimiento::programar()`.

Resultado antes del rollback:

- `estado = programado`: OK.
- `programado = 1`: OK.
- `hotel_id = Los Cedros`: OK.
- `habitacion_id` valido: OK.

Resultado despues del rollback:

- Registros QA persistidos: 0.
- Conteo por estado se mantuvo igual.

## Pruebas HTTP

Sin sesion activa:

| Ruta | Resultado |
| --- | --- |
| `/login` | 200 |
| `/` | 200 |
| `/dashboard` | 303, redireccion esperada a login |
| `/habitaciones` | 303, redireccion esperada a login |

## Que NO Se Toco

- No se modifico codigo funcional.
- No se tocaron caja, cortes ni movimientos de caja.
- No se tocaron reservaciones, pagos ni check-in/check-out.
- No se tocaron login ni sesiones.
- No se tocaron dashboard ni reportes.
- No se toco PWA/offline ni service worker.
- No se cambio `hotel_id`.
- No se modificaron datos existentes.

## Riesgos Pendientes

- Falta validar el flujo completo desde UI con sesion real: programar, cancelar e iniciar mantenimiento programado.
- La migracion usa `ALTER TABLE`, que en MySQL hace commit implicito.
- Si en el futuro se requiere rollback y ya existen registros con `estado = 'programado'`, el rollback debe detenerse y requerir decision manual.
- Aun queda pendiente la auditoria/implementacion de lecturas cruzadas Habitaciones + Reservaciones en Fase 2A.2-C-B.

## Siguiente Fase Recomendada

Retomar Fase 2A.2-C-B: integracion controlada de lecturas cruzadas del modulo Habitaciones, sin tocar Reservaciones como modulo principal y manteniendo fuera caja, login/sesiones, dashboard y PWA/offline.
