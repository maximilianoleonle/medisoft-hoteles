# Fase 7B-B - Listado y detalle CxC operativa read-only

## Estado

`LISTADO_7B_B_CXC_OPERATIVA_READONLY_COMPLETADO`

## Objetivo

Exponer una lectura operativa de las tablas nuevas de CxC creadas en 7B-A, sin crear
cuentas, sin cobros, sin pagos, sin abonos, sin movimientos de Caja y sin poblar datos
historicos.

## Superficie implementada

Rutas GET:

- `GET /cuentas-por-cobrar/operativas`
- `GET /cuentas-por-cobrar/operativas/{id}`

Archivos funcionales:

- `src/app/models/CuentaPorCobrar.php`
- `src/app/controllers/CuentaPorCobrarController.php`
- `src/app/views/cuentas_por_cobrar/index.php`
- `src/app/views/cuentas_por_cobrar/operativas.php`
- `src/app/views/cuentas_por_cobrar/ver_operativa.php`
- `src/config/routes.php`
- `src/tools/saas/preflight_cuentas_por_cobrar.php`

## Comportamiento

- `/cuentas-por-cobrar` conserva el reporte estimado 7A-A derivado de reservaciones.
- `/cuentas-por-cobrar/operativas` lista solo `cuentas_por_cobrar`.
- `/cuentas-por-cobrar/operativas/{id}` muestra detalle y movimientos internos
  read-only.
- Todas las consultas filtran por `hotel_id`.
- La vista muestra estado vacio porque las tablas siguen sin datos.
- No hay formularios POST.
- No hay botones de cobro.
- No hay acciones de pago.
- No hay escritura en CxC.
- No hay escritura en Caja.
- No se toca `/api/sync`.

## Conteos posteriores

- `cuentas_por_cobrar`: `0`
- `cuentas_por_cobrar_movimientos`: `0`
- Movimientos de Caja con referencia textual a CxC: `0`

## Verificaciones

Lint PHP:

- `CuentaPorCobrar.php`: OK
- `CuentaPorCobrarController.php`: OK
- `cuentas_por_cobrar/index.php`: OK
- `cuentas_por_cobrar/operativas.php`: OK
- `cuentas_por_cobrar/ver_operativa.php`: OK
- `routes.php`: OK
- `preflight_cuentas_por_cobrar.php`: OK

Preflight CxC:

- `OK: 25`
- `WARNING: 3`
- `ERROR: 0`
- `PASS_WITH_WARNINGS_ALLOWED`

Health general:

- `OK: 277`
- `WARNING: 25`
- `ERROR: 0`
- `PASS_WITH_WARNINGS_ALLOWED`

HTTP sin sesion:

- `/cuentas-por-cobrar/operativas`: `303` a login.
- `/cuentas-por-cobrar/operativas/1`: `303` a login.

Warnings conocidos:

- 3 pagos historicos huerfanos.
- 170 solicitudes de factura huerfanas.
- 3 reservaciones con saldo estimado negativo.
- Warnings generales historicos del health checker.
- Un warning documental de ficha de reservacion no pertenece a 7B-B y queda fuera de
  este cambio.

## Confirmaciones de seguridad

- No se modifico DB.
- No se ejecutaron migraciones nuevas.
- No se poblaron cuentas.
- No se cambiaron reservaciones.
- No se cambiaron pagos, abonos ni solicitudes de factura.
- No se tocaron Caja, cortes ni movimientos.
- No se tocaron PWA/offline ni `/api/sync`.

## Rollback

Rollback de codigo/documentacion:

1. Retirar rutas GET `/cuentas-por-cobrar/operativas` y
   `/cuentas-por-cobrar/operativas/{id}`.
2. Revertir los metodos operativos read-only agregados en `CuentaPorCobrar`.
3. Revertir `operativasAction()` y `verOperativaAction()`.
4. Eliminar las vistas `operativas.php` y `ver_operativa.php`.
5. Revertir los checks 7B-B del preflight.

Rollback DB: no aplica. 7B-B no escribe datos.

No borrar las tablas `cuentas_por_cobrar` ni `cuentas_por_cobrar_movimientos`; pertenecen
a 7B-A y tienen rollback separado.

## Siguiente accion segura

7B-C-0 ya quedo abierto como contrato de generacion manual futura desde reservacion
elegible, sin implementar todavia escrituras.

## Nota posterior

La fase 7B-C-A implemento despues una accion manual POST desde el reporte derivado
`/cuentas-por-cobrar`. El listado y detalle `/cuentas-por-cobrar/operativas` siguen
siendo read-only; la escritura pertenece a 7B-C-A y queda documentada en
`docs/fase_7B_C_A_generacion_manual_cxc_reservacion.md`.

Tras QA manual de 7B-C-A, las tablas ya no estan vacias: existe CxC `#1` con movimiento
`CREACION #1`. Esa informacion pertenece a 7B-C-A/7B-C-F, no cambia el contrato
read-only de las pantallas operativas.

La implementacion 7B-C-A requerira backup, CSRF, auditoria, bloqueo de duplicados,
validacion estricta de `hotel_id` y seguir sin Caja automatica.
