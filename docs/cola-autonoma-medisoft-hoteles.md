# Cola autonoma - Medisoft Hoteles

## Ultimo mensaje real usado

COLA_AUDITORIA_SEGURIDAD_3C: auditar seguridad/riesgo del bloque 3C completo, sin pagos, abonos, Caja ni Fase 3D.

## Estado vigente

- Bloque actual: Fase 3C CxP.
- Fase actual: auditoria seguridad 3C.
- Riesgo: naranja.
- Estado: `AUDITORIA_SEGURIDAD_3C_COMPLETADA`.
- HEAD base antes del reanclaje: `2662998 docs(phase-3c): record payable generation security audit`.
- Estado Git al iniciar reanclaje: limpio.
- Base local principal: `medisoft_hoteles_import`.
- No avanzar a pagos, Caja, Fase 3D ni nuevas funcionalidades.
- Nota: existen commits de 3C-A/B/C y revisiones posteriores, pero el nuevo reanclaje no los considera cierre formal.
- Verificacion actual: Docker disponible; `php -l`, health, preflights, HTTP sin sesion, conteos DB antes/despues y `git diff --check` ejecutados.

## Reanclaje Fase 3C

- 3C-0 contrato y diagnostico: completada en `9897465`.
- 3C-A simulador: codigo vigente con ruta/vista/modelo verificados y commiteados en `c0ff5a1`.
- 3C-B generacion manual: implementada tecnicamente y validada manualmente por el usuario.
- 3C-C validaciones: checkers read-only refuerzan reglas de consistencia CxP y ausencia de movimientos/pagos/abonos.
- SQL read-only 3C-C: CxP=2, movimientos CxP=0, inconsistencias=0, Caja-CxP=0, `compra_pagos` inexistente.
- Revision tecnica 3C actual: completada sin hallazgos bloqueantes.
- Auditoria seguridad 3C actual: completada sin hallazgos bloqueantes.
- Auditoria `2662998`: documentacion de auditoria prematura bajo el reanclaje.
- Cambios no relacionados pendientes al cierre de auditoria 3C: `src/app/services/PwaPushService.php` y `src/public_html/service-worker.js`; no forman parte del bloque CxP y requieren triage separado.
- Siguiente accion recomendada: cierre tecnico 3C, solo si el usuario lo autoriza.

## Bloque base previo: Fase 3B CxP read-only

- Ultimo mensaje real del bloque previo: Fase 3C-B generacion manual controlada de CxP desde compra recibida, sin Caja ni pagos.
- Estado: cerrado tecnicamente; QA manual reportada por el usuario.
- Backup previo a DB del bloque previo:
  - `src/storage/backups/phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql`
  - SHA256: `24663D206AE15B86B001708D8BC2665541548443A0EA363548CAFC3FDF3A4D2C`
  - tamano: `3017728`

## Contrato activo

La Fase 3B aplicada solo permite una fundacion read-only de cuentas por pagar:

- tablas nuevas vacias de CxP;
- modelo/controlador/vistas read-only;
- rutas GET de listado y detalle;
- navegacion basica;
- health/preflight compatibles;
- documentacion de QA, decisiones, rollback y fuentes de verdad.

No permite pagos, Caja, generacion automatica desde compras, saldos operativos, CxC, nomina, permisos profundos ni cambios en `/api/sync`.

## Fases completadas con commit

- `d1f1431` - `feat: add read-only received purchase detail`
- `32abb7b` - `feat: add read-only received purchases reports`
- `052fd7a` - `fix: harden minimal purchase receiving flow`
- `3d8f997` - `feat: expand supplier profile and purchase history`
- `673f47f` - `docs: draft accounts payable foundation`
- `1fa1653` - `feat(phase-3b): add read-only accounts payable foundation`
- `2535dd1` - `docs: record post-3b audit closure`
- `ca2bda4` - `docs: record security audit closure`
- `8a49995` - `docs: close authorized technical block`
- `dc3c150` - `fix: improve late check-in modal layout`

## Diagnostico 3C-0

- Estado Git al inicio de 3C-0: limpio.
- QA manual del bloque anterior: reportada como realizada por el usuario.
- Cambio visual de reservaciones: commiteado en `dc3c150`.
- Compras recibidas detectadas: 2.
- Compras recibidas elegibles para CxP: 2.
- CxP actuales: 0.
- Movimientos Caja-CxP: 0.

## Implementacion 3C-A

- Ruta nueva: `GET /cuentas-por-pagar/generacion-preview`.
- Vista nueva: `src/app/views/cuentas_por_pagar/generacion_preview.php`.
- Modelo CxP consulta compras, proveedores, hoteles, detalles y CxP con `hotel_id`.
- La vista muestra compra, proveedor, hotel, fecha, total, estado, CxP existente, elegibilidad y bloqueo.
- No hay POST, pagos, Caja ni generacion automatica.
- Estado corregido: ruteado, protegido por `CuentaPorPagarController::before()`, navegable desde CxP y verificado automaticamente.

## Implementacion 3C-B

- Ruta nueva activa: `POST /cuentas-por-pagar/generar-desde-compra/{id}`.
- Controller: `CuentaPorPagarController::generarDesdeCompraAction()`.
- Modelo: `CuentaPorPagar::generarDesdeCompraRecibida()`.
- Usa CSRF, transaccion y bloqueo `FOR UPDATE`.
- Valida compra recibida, proveedor del mismo hotel, total positivo, detalles existentes y no duplicado.
- Inserta solo en `cuentas_por_pagar`.
- Registra auditoria en `logs_auditoria`.
- No inserta pagos, abonos, movimientos de CxP ni movimientos de Caja.

## Prueba local historica 3C-B

- Backup valido previo:
  - `src/storage/backups/phase3c_b_20260615_053711_before_manual_cxp_medisoft_hoteles_import_notablespaces.sql`
  - SHA256: `8086F91DF17DB09CFBB28E7E12BED475FDD81FB538948F4B60141A90BE9E801D`
  - tamano: `1528988`
- Primer intento de backup con routines/tablespaces genero advertencias de privilegios y no se toma como respaldo valido:
  - `src/storage/backups/phase3c_b_20260615_053658_before_manual_cxp_medisoft_hoteles_import.sql`
- Registro usado para prueba:
  - hotel_id: `4`
  - compra_id: `5`
  - total: `1000.00`
- Resultado:
  - CxP generada: `id = 1`
  - doble generacion: bloqueada con mensaje de CxP existente
  - `cuentas_por_pagar`: `0 -> 1`
  - `cuentas_por_pagar_movimientos`: `0 -> 0`
  - `logs_auditoria`: `23 -> 24`
  - `cajas`: `3 -> 3`
  - `movimientos_caja`: `1402 -> 1402`

## Prueba local vigente 3C-B

- Backup valido previo:
  - `src/storage/backups/phase3c_b_20260615_144908_before_manual_cxp_medisoft_hoteles_import.sql`
  - SHA256: `C0403F7B5ACBDA35EF4C05E2840546D5D9978802A21C9736BEBC6FF462518061`
  - tamano: `1532615`
- Registro usado para prueba:
  - hotel_id: `4`
  - compra_id: `2`
  - total: `1900.00`
- Resultado:
  - CxP generada: `id = 2`
  - doble generacion: bloqueada con mensaje de CxP existente
  - `cuentas_por_pagar`: `1 -> 2`
  - `cuentas_por_pagar_movimientos`: `0 -> 0`
  - `logs_auditoria`: `25 -> 26`
  - `cajas`: `3 -> 3`
  - `movimientos_caja`: `1402 -> 1402`

## Cambios pendientes clasificados post-commit

### Relacionados con Fase 3B

- Ninguno pendiente.

### No relacionado con Fase 3B

- Ninguno pendiente. El ajuste visual de reservaciones quedo commiteado en `dc3c150`.

### Dudosos

- Ninguno identificado.

## Pruebas obligatorias para cierre

- `php -l` sobre controlador, modelo, vistas, rutas, sidebar y checkers modificados.
- `health_check_fase_1a.php`.
- `preflight_compras_minimas.php`.
- `preflight_recepcion_compras.php`.
- SQL read-only para validar tablas, migracion registrada, conteos CxP en cero y no generacion de pagos.
- HTTP sin sesion `GET /cuentas-por-pagar`.
- `git diff --check`.

## Auditoria de seguridad post-cierre

- Estado: completada sin hallazgos bloqueantes.
- CxP conserva solo lectura y rutas GET.
- No hay integracion accidental con Caja.
- No hay escritura CxP desde compras/proveedores.
- `/api/sync` sigue bloqueado y fuera de alcance.
- Riesgo residual futuro: escrituras CxP deben validar proveedor/compra por `hotel_id` antes de insertar.

## QA manual

Fase 3C-A/3C-B: QA manual reportada por el usuario como completada. Ver `docs/qa-pendiente-cola.md`.

## Documento de cierre

Ver `docs/cierre-tecnico-bloque-cola.md`.

## Diagnostico NP-0 (Personal y Nomina)

- HEAD al iniciar: `5dfe665`; Git limpio; rama `feature/saas-multihotel` 21 commits por delante de origin (sin push).
- Hoy "trabajador" = `usuarios` (tabla global, sin `hotel_id`, `rol` de sistema) + pivote `hotel_usuarios`. No hay rol laboral, deuda ni saldo por persona.
- Caja revisada en solo lectura: `cajas`, `movimientos_caja`, `cortes_caja`, `categorias_movimientos`. NO existe categoria "Nomina". Movimientos Caja-nomina: 0 (debe seguir en 0).
- Responsable de mantenimiento/limpieza: hoy es texto libre `mantenimientos_habitaciones.realizado_por`; no hay tabla `limpieza`/`tareas`. La referencia NP sera logica/opcional, sin alterar mantenimiento.
- No existe ninguna tabla `trabajador*`: el bloque es 100% aditivo.
- Patrones reutilizables: `AuditService::record()` (auditoria), migracion aditiva idempotente (modelo `20260615_003_fase_3b_cxp_base.sql`), modelos con filtro `hotel_id`.
- Confirmado: NO hace falta tocar Caja, ni `usuarios` destructivamente, ni `/api/sync`.
- Diseno de 6 tablas (`trabajadores`, `trabajador_pagos`, `trabajador_anticipos`, `trabajador_prestamos`, `trabajador_asistencias`, `trabajador_documentos`) documentado en `docs/fase_NP_0_contrato_diagnostico.md`.

## Siguiente accion

Siguiente paso formal recomendado: cierre tecnico 3C, solo si el usuario lo autoriza. No avanzar a pagos, Caja, Fase 3D, NP-A ni salida real de dinero. No alterar `usuarios` de forma destructiva. No tocar `/api/sync`. No hacer push.
