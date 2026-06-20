# Fase 11A-F - Cierre perfil operativo de huesped read-only

## Estado

`CIERRE_11A_PERFIL_HUESPED_READONLY_QA_MANUAL_VALIDADA`

## Objetivo

Cerrar documentalmente el bloque 11A despues de que el usuario valido en navegador
que el perfil operativo de huesped se muestra correctamente y conserva el contrato de
solo lectura.

## Fases cerradas

- 11A-0 contrato de perfil operativo de huesped read-only.
- 11A-A implementacion del bloque read-only en `GET /huespedes/{id}`.

## Resultado funcional

La ficha de huesped ahora muestra un bloque `Perfil operativo` con:

- clasificacion operativa;
- score calculado al vuelo;
- proxima estancia;
- saldo CxC pendiente;
- documentos vinculados;
- alertas visuales.

El bloque es informativo y no ejecuta acciones.

## Validacion automatica registrada

- PHP lint en archivos tocados: OK.
- Health general: `OK: 304`, `WARNING: 25`, `ERROR: 0`.
- Resultado health: `PASS_WITH_WARNINGS_ALLOWED`.
- HTTP sin sesion en `/huespedes/1`: `303` hacia `/login`.
- `git diff --check` scoped: OK.

## QA manual

El usuario confirmo manualmente que:

- El bloque se muestra.
- La etiqueta `Solo lectura` esta presente.
- No hay botones, enlaces ni formularios dentro del bloque nuevo.
- La revision visual fue correcta.

Resultado: QA manual validada.

## Garantias de alcance

Este cierre no autoriza:

- crear CxC;
- cobrar;
- pagar;
- cambiar reservaciones;
- check-in/check-out;
- subir, editar o borrar documentos;
- crear tareas o recordatorios persistentes;
- cambiar permisos/auth;
- tocar migraciones o base de datos;
- tocar PWA/offline, IndexedDB, cache names o `/api/sync`.

`/api/sync` debe seguir bloqueado con HTTP 423 y JSON
`sync_temporarily_disabled`.

## Siguiente paso recomendado

Abrir un nuevo contrato independiente para el siguiente frente de mejora antes de tocar
codigo operativo. Un candidato seguro es definir el siguiente bloque read-only o una
mejora visual controlada, evitando escrituras financieras o cambios de flujo sin
backup y contrato especifico.

## Rollback

Si se detecta una regresion posterior:

1. Usar el rollback documentado en `docs/rollback-cola.md` para 11A-A.
2. Retirar referencias 11A-F de documentacion.
3. No ejecutar SQL.
4. No tocar reservaciones, Caja, CxC, documentos, tareas, permisos/auth,
   PWA/offline ni `/api/sync`.
