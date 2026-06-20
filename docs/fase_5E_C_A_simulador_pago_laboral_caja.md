# Fase 5E-C-A - Simulador pago laboral con Caja

## Estado

`SIMULADOR_5E_C_A_PAGO_LABORAL_CAJA_READONLY_COMPLETADO`

## Objetivo

Implementar una pantalla GET/read-only para diagnosticar si un trabajador podria
recibir un pago laboral futuro contra Caja.

Esta fase no registra pagos, no crea movimientos de Caja, no cambia saldos, no
liquida anticipos/prestamos, no crea categorias de Caja y no agrega POST ni servicio
transaccional.

## Superficie implementada

- Ruta:
  `GET /trabajadores/pagos-caja/simulador`.
- Controlador:
  `TrabajadorController::simuladorPagoCajaAction()`.
- Modelo:
  `Trabajador::simuladorPagoCajaPorHotel()`.
- Vista:
  `src/app/views/trabajadores/simulador_pago_caja.php`.
- Enlaces GET:
  `src/app/views/trabajadores/index.php`.
  `src/app/views/trabajadores/ver.php`.
- Checkers:
  `src/tools/saas/preflight_personal_pagos_caja.php`.
  `src/tools/saas/health_check_fase_1a.php`.

## Datos leidos

El simulador lee solo informacion scoped por hotel:

- `trabajadores`.
- `trabajador_pagos` como conceptos laborales.
- `trabajador_anticipos` como saldos informativos.
- `trabajador_prestamos` como saldos informativos.
- `trabajador_pagos_caja` para detectar pagos laborales reales ya registrados.
- `cajas` y `cortes_caja` para detectar corte abierto.
- `movimientos_caja` solo para detectar referencia duplicada.

## Diagnostico calculado

La pantalla calcula al vuelo:

- saldo laboral estimado;
- conceptos a favor y en contra;
- anticipos pendientes;
- prestamos vigentes;
- monto simulado;
- referencia sugerida o capturada;
- corte abierto;
- pagos laborales con Caja ya existentes;
- elegibilidad o bloqueo.

Estos valores no se persisten.

## Bloqueos mostrados

El simulador bloquea visualmente cuando:

- no hay corte de Caja abierto;
- el trabajador no esta activo;
- el trabajador no pertenece al hotel actual;
- el periodo es invalido;
- el metodo de pago no es valido;
- el monto simulado no es mayor a cero;
- el monto excede el saldo estimado;
- tarjeta o transferencia no tienen referencia manual;
- la referencia ya existe en pagos laborales con Caja;
- la referencia ya existe en Caja;
- el saldo estimado no es positivo.

## Escrituras prohibidas

Esta fase no escribe en:

- `trabajadores`;
- `trabajador_pagos`;
- `trabajador_anticipos`;
- `trabajador_prestamos`;
- `trabajador_pagos_caja`;
- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `categorias_movimientos`;
- `logs_auditoria`;
- `migrations`;
- PWA/offline;
- `/api/sync`.

## Validaciones automaticas

Ejecutadas en Docker:

- `php -l /var/www/html/app/models/Trabajador.php`.
- `php -l /var/www/html/app/controllers/TrabajadorController.php`.
- `php -l /var/www/html/app/views/trabajadores/simulador_pago_caja.php`.
- `php -l /var/www/html/tools/saas/preflight_personal_pagos_caja.php`.
- `php -l /var/www/html/tools/saas/health_check_fase_1a.php`.
- `php -l /var/www/html/config/routes.php`.

Resultados:

- Preflight 5E: `OK: 41`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 309`, `WARNING: 26`, `ERROR: 0`.
- HTTP sin sesion:
  `GET /trabajadores/pagos-caja/simulador` responde `303` por autenticacion, no `404`.
- `/api/sync` sigue bloqueado por health con HTTP 423 y JSON
  `sync_temporarily_disabled`.

## Fuera de alcance

No se implementa:

- pago laboral real;
- POST de pago;
- token de pago;
- `TrabajadorPagoCajaService`;
- prueba rollback transaccional;
- movimiento de Caja;
- categoria de Caja;
- abonos o liquidaciones de anticipos/prestamos;
- nomina automatica;
- reversion;
- permisos/auth nuevos;
- migracion;
- PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Rollback conceptual

Para retirar esta fase:

1. Retirar la ruta `GET /trabajadores/pagos-caja/simulador`.
2. Retirar `TrabajadorController::simuladorPagoCajaAction()`.
3. Retirar los metodos read-only agregados en `Trabajador`.
4. Eliminar `src/app/views/trabajadores/simulador_pago_caja.php`.
5. Retirar los enlaces GET del listado y ficha de trabajadores.
6. Retirar las validaciones 5E-C-A de preflight y health.
7. Retirar esta documentacion y sus referencias.

No ejecutar SQL ni tocar `trabajador_pagos_caja`.

## Siguiente paso seguro

El siguiente paso seguro es un contrato `5E-D-0` para el futuro servicio
transaccional de pago laboral con Caja.

No implementar pago real, POST, token, servicio ni prueba rollback sin nuevo contrato
y autorizacion explicita.
