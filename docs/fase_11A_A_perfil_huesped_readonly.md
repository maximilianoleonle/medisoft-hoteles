# Fase 11A-A - Perfil operativo de huesped read-only

## Estado

`PERFIL_11A_A_HUESPED_READONLY_VALIDADO_MANUALMENTE`

## Objetivo

Agregar a la ficha existente `GET /huespedes/{id}` un bloque operativo de solo
lectura para que recepcion/operacion pueda ver recurrencia, proxima estancia, saldo
CxC, documentos y alertas basicas sin abrir nuevas escrituras.

## Alcance implementado

- No se agregan rutas nuevas.
- Se conserva `GET /huespedes/{id}` como superficie principal.
- Se agrega un lector read-only en `Huesped`.
- `HuespedController::verAction` pasa el perfil calculado a la vista.
- `huespedes/ver.php` muestra un bloque `Perfil operativo` con etiqueta
  `Solo lectura`.
- El bloque nuevo no contiene formularios, acciones, enlaces ni CSRF.
- El score y clasificacion se calculan al vuelo y no se persisten.

## Archivos tocados

- `src/app/models/Huesped.php`.
- `src/app/controllers/HuespedController.php`.
- `src/app/views/huespedes/ver.php`.
- `src/tools/saas/health_check_fase_1a.php`.
- Documentacion de seguimiento.

## Lector read-only

Metodo principal:

- `Huesped::perfilOperativoReadOnlyPorHotel($huespedId, $hotelId)`.

Lecturas permitidas:

- `huespedes` filtrado por `id` y `hotel_id`.
- `reservaciones` filtrado por `huesped_id` y `hotel_id`.
- `huesped_vehiculos` derivado desde huesped del hotel actual.
- `cuentas_por_cobrar` filtrado por `hotel_id` y `huesped_id`.
- `documentos` y `documento_entidades` filtrados por hotel y entidad `huesped`.

El lector usa degradacion segura si una tabla opcional no existe.

## Superficie visual

Bloque nuevo:

- Clasificacion operativa.
- Score calculado al vuelo.
- Proxima estancia.
- Saldo CxC pendiente.
- Documentos vinculados/activos.
- Alertas visuales por saldo, documentos, vehiculos y reservaciones futuras.

Reglas visuales:

- Etiqueta `Solo lectura`.
- Sin tokens `--ms-*`.
- Sin botones operativos nuevos.
- Sin formularios nuevos.
- Sin mover formularios existentes.

## Fuera de alcance

No se implementa:

- CxC nueva.
- Cobros.
- Pagos.
- Check-in/check-out.
- Cambios de reservaciones.
- Subida, borrado o edicion documental.
- Tareas o recordatorios persistentes.
- Migraciones.
- Cambios de permisos/auth.
- PWA/offline, IndexedDB, cache names ni `/api/sync`.

`/api/sync` debe seguir bloqueado con HTTP 423 y JSON
`sync_temporarily_disabled`.

## Validaciones automaticas

Resultado final:

- `php -l app/models/Huesped.php`: OK.
- `php -l app/controllers/HuespedController.php`: OK.
- `php -l app/views/huespedes/ver.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- `php tools/saas/health_check_fase_1a.php`: `OK: 304`, `WARNING: 25`,
  `ERROR: 0`, `PASS_WITH_WARNINGS_ALLOWED`.
- HTTP sin sesion de `/huespedes/1`: `303` hacia `/login`.
- `git diff --check` en archivos tocados: OK; solo avisos LF/CRLF de Git.

## QA manual validada

El usuario confirmo manualmente que:

- El bloque `Perfil operativo` se muestra correctamente.
- La etiqueta `Solo lectura` esta visible.
- No hay botones operativos, enlaces ni formularios dentro del bloque nuevo.
- La ficha se ve correcta y no hay regresion visual evidente.

Resultado: QA manual aprobada.

## Cierre posterior

La fase queda cerrada documentalmente en:

- `docs/fase_11A_F_cierre_perfil_huesped_readonly.md`.

## Rollback

Si 11A-A causa regresion:

1. Retirar el bloque `guest-readonly-profile` de `huespedes/ver.php`.
2. Retirar variables auxiliares de perfil en la vista.
3. Retirar llamada a `perfilOperativoReadOnlyPorHotel()` de `verAction`.
4. Retirar metodos read-only agregados a `Huesped`.
5. Retirar checks 11A-A de `health_check_fase_1a.php`.
6. Retirar referencias documentales de 11A-A.

No ejecutar SQL ni tocar reservaciones, Caja, CxC, documentos, tareas, permisos/auth,
PWA/offline ni `/api/sync`.
