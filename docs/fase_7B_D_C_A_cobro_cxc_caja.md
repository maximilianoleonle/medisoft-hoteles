# Fase 7B-D-C-A - Cobro CxC con Caja

## Estado

`COBRO_CXC_CAJA_7B_D_C_A_VALIDADO_MANUALMENTE`

## Objetivo

Implementar el flujo controlado para registrar cobros de cuentas por cobrar contra Caja
desde el detalle de una CxC operativa elegible.

La implementacion queda validada con QA manual real en navegador y verificacion
post-QA por SQL, preflight, health general y prueba rollback.

## Backup previo

- Archivo:
  `backups/medisoft_hoteles_import_before_7b_d_c_a_cxc_cash_service_20260618_232434.sql`
- SHA256:
  `5708BC91E1BD7A54EFA53A8BA6A92A282ACC98A2AD9237F274AAB87AB11796CD`
- Tamano: `1646216` bytes

## Alcance implementado

- Servicio transaccional:
  `src/app/services/CuentaPorCobrarCobroService.php`.
- Ruta POST controlada:
  `/cuentas-por-cobrar/operativas/{id}/registrar-cobro-caja`.
- Controlador actualizado:
  `CuentaPorCobrarController::registrarCobroCajaAction()`.
- Formulario en detalle CxC operativo:
  `src/app/views/cuentas_por_cobrar/ver_operativa.php`.
- Token de cobro de un solo uso por cuenta/sesion.
- Prueba rollback:
  `src/tools/saas/probar_cobro_cxc_caja.php`.
- Preflight CxC actualizado para validar la superficie 7B-D-C-A y consistencia
  post-QA entre `COBRO` e ingreso Caja.

## Reglas implementadas

El cobro exige:

- sesion activa;
- contexto hotelero;
- modulo `reservaciones`;
- modulo `caja` en el POST;
- CSRF;
- token de cobro valido y no reutilizado;
- CxC por `id + hotel_id`;
- estado `pendiente`, `parcial` o `vencida`;
- saldo positivo;
- monto mayor a cero y no mayor al saldo;
- corte de Caja abierto del mismo hotel;
- caja activa;
- tipo semantico `COBRO` disponible;
- referencia no duplicada para la misma CxC, si se captura.

## Escrituras del servicio

Cuando se use desde navegador, el servicio escribira atomica y transaccionalmente:

- `cuentas_por_cobrar_movimientos` con `tipo_movimiento = COBRO`;
- `movimientos_caja` con `tipo = ingreso` y `categoria = Cobro CxC`;
- `cuentas_por_cobrar.saldo`;
- `cuentas_por_cobrar.estado`;
- `logs_auditoria` con `cuentas_por_cobrar.cobro_caja_registrado`.

## Verificacion automatica inicial

La prueba automatica se ejecuto con rollback, por lo que no dejo datos persistentes.

Conteos antes del QA manual:

- `cuentas_por_cobrar = 1`;
- `cuentas_por_cobrar_movimientos = 1`;
- movimientos CxC tipo `COBRO = 0`;
- movimientos de Caja CxC persistentes = `0`;
- CxC `#1`: estado `pendiente`, saldo `4250.00`.

## QA manual validada

El usuario valido en navegador el cobro parcial controlado sobre la CxC `#1`.

Datos persistidos:

- CxC `#1`: estado `parcial`, saldo `4249.00`.
- Movimiento CxC `#4`: `COBRO`, monto `1.00`, saldo anterior `4250.00`,
  saldo posterior `4249.00`, referencia `QA-CXC-20260619-001`.
- Movimiento Caja `#1482`: tipo `ingreso`, categoria `Cobro CxC`, monto `1.00`,
  metodo `efectivo`, corte `#237`, referencia `QA-CXC-20260619-001`.
- Auditoria `#109`: `cuentas_por_cobrar.cobro_caja_registrado`,
  entidad `cuentas_por_cobrar`, entidad id `1`.

## Prueba rollback

Comando:

```bash
php tools/saas/probar_cobro_cxc_caja.php
```

Resultado:

- CxC elegida post-QA: `#1`, hotel `4`, saldo `4249.00`.
- Corte abierto detectado: `#237`.
- Movimiento CxC temporal: `#5`.
- Movimiento Caja temporal: `#1483`.
- Saldo temporal: `4248.00`.
- Referencia duplicada bloqueada dentro de la transaccion.
- Rollback confirmado: movimientos CxC, movimientos `COBRO`, Caja, auditoria, saldo y
  estado quedaron iguales.

## Validaciones automaticas

- `php -l` OK en:
  - `app/services/CuentaPorCobrarCobroService.php`;
  - `app/controllers/CuentaPorCobrarController.php`;
  - `app/views/cuentas_por_cobrar/ver_operativa.php`;
  - `tools/saas/probar_cobro_cxc_caja.php`;
  - `tools/saas/preflight_cuentas_por_cobrar.php`;
  - `config/routes.php`.
- Preflight CxC:
  - `OK: 39`;
  - `WARNING: 5`;
  - `ERROR: 0`.
- Health general:
  - `OK: 277`;
  - `WARNING: 25`;
  - `ERROR: 0`.
- HTTP sin sesion:
  - `GET /cuentas-por-cobrar/operativas/1`: `303` a login;
  - `POST /cuentas-por-cobrar/operativas/1/registrar-cobro-caja`: `303` a login.

## Riesgo residual

Despues del primer cobro manual real existen datos financieros persistentes. No deben
borrarse con SQL directo.

El cobro real `#4` fue revertido posteriormente en 7B-D-D-A mediante `CANCELACION #8`
y gasto Caja `#1486`; el cobro original y el ingreso Caja original se conservan como
historicos.

## Siguiente paso seguro

La reversion/anulacion de cobro CxC ya quedo contratada en 7B-D-D-0 e implementada
tecnicamente en 7B-D-D-A.

No avanzar a cobros masivos, reversiones parciales o automatizaciones sin contrato
independiente, backup, prueba rollback y QA manual especifica.
