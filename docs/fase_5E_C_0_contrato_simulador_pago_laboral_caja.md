# Fase 5E-C-0 - Contrato simulador pago laboral con Caja

## Estado

`CONTRATO_5E_C_0_SIMULADOR_PAGO_LABORAL_CAJA_COMPLETADO`

## Objetivo

Definir el contrato para una futura pantalla GET/read-only que diagnostique si un
trabajador podria recibir un pago laboral real contra Caja.

Esta fase es solo documental. No agrega ruta, controlador, modelo, vista, formulario,
servicio, POST, pago laboral, movimiento de Caja, categoria de Caja, datos,
migraciones, permisos nuevos, PWA/offline ni cambios en `/api/sync`.

## Contexto previo

La fase 5E-B-A ya creo la tabla futura:

- `trabajador_pagos_caja`.

Estado actual:

- `trabajador_pagos_caja`: `0` registros.
- `trabajador_pagos`: conceptos laborales manuales, no pagos reales.
- `trabajador_anticipos`: saldos informativos.
- `trabajador_prestamos`: saldos informativos.
- Preflight 5E: `OK: 37`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 307`, `WARNING: 26`, `ERROR: 0`.

La tabla existe, pero todavia no hay servicio de pago, ruta operativa ni POST.

## Superficie futura recomendada

Ruta candidata:

- `GET /trabajadores/pagos-caja/simulador`.

Parametros GET candidatos:

- `trabajador_id`.
- `periodo_inicio`.
- `periodo_fin`.
- `metodo_pago`.
- `monto`.

Reglas de formulario:

- Debe usar `method="GET"`.
- No debe incluir CSRF porque no escribe.
- No debe incluir botones de pago real.
- No debe incluir `POST`.
- No debe crear ni consumir token de pago.

El simulador puede enlazarse desde `GET /trabajadores/{id}`, pero la ficha de
trabajador no debe ganar formularios POST de pago en esta fase.

## Datos que debe leer

El simulador futuro debe leer solamente:

- `trabajadores` por `id + hotel_id`;
- `trabajador_pagos` como conceptos activos del periodo;
- `trabajador_anticipos` como saldos informativos;
- `trabajador_prestamos` como saldos informativos;
- `trabajador_pagos_caja` para detectar pagos laborales reales ya registrados;
- `cajas` del hotel actual;
- `cortes_caja` abiertos del hotel actual;
- `movimientos_caja` solo para diagnosticar duplicados o referencias;
- `logs_auditoria` solo si se necesita mostrar contexto historico read-only.

No debe leer datos de otro hotel.

## Calculo read-only permitido

El simulador puede calcular al vuelo:

- total de conceptos a favor;
- total de conceptos en contra;
- saldo laboral estimado;
- anticipos pendientes informativos;
- prestamos pendientes informativos;
- monto maximo sugerido para pago;
- corte abierto elegible;
- motivo de elegibilidad o bloqueo;
- referencia futura sugerida.

Estos calculos no se persisten.

## Elegibilidad futura

Un trabajador debe aparecer elegible solo si:

- existe en el hotel actual;
- esta activo;
- hay corte de Caja abierto del mismo hotel;
- el monto simulado es mayor a cero;
- el monto no excede el saldo laboral estimado si el simulador usa saldo;
- el metodo de pago es valido: `efectivo`, `tarjeta` o `transferencia`;
- la referencia sugerida o capturada no esta duplicada en `trabajador_pagos_caja`;
- la referencia no esta duplicada en `movimientos_caja` para el hotel;
- el periodo es valido si se captura;
- no existe un pago laboral real duplicado para el mismo trabajador/referencia;
- `trabajador_pagos_caja` existe y esta disponible.

## Bloqueos que debe mostrar

El simulador debe mostrar motivos claros cuando:

- el trabajador no existe o no pertenece al hotel;
- el trabajador no esta activo;
- no hay corte abierto;
- no hay caja activa;
- el monto es invalido;
- el metodo de pago es invalido;
- falta referencia obligatoria para tarjeta o transferencia;
- la referencia esta duplicada;
- el periodo es invalido;
- ya existe pago laboral real con esa referencia;
- la tabla `trabajador_pagos_caja` no existe;
- el preflight 5E detecta inconsistencias.

## Escrituras prohibidas

El simulador futuro no debe escribir en:

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

Tampoco debe:

- crear pagos reales;
- crear movimientos de Caja;
- modificar saldos;
- liquidar anticipos/prestamos;
- generar nomina automatica;
- crear categorias de Caja;
- crear tokens de pago;
- cambiar permisos.

## Archivos que podria tocar una futura 5E-C-A

Solo con autorizacion explicita:

- `src/config/routes.php`.
- `src/app/controllers/TrabajadorController.php`.
- `src/app/models/Trabajador.php`.
- `src/app/views/trabajadores/simulador_pago_caja.php`.
- `src/app/views/trabajadores/ver.php` solo para enlace GET opcional.
- `src/tools/saas/preflight_personal_pagos_caja.php`.
- `src/tools/saas/health_check_fase_1a.php`.
- Documentacion de seguimiento.

No se debe tocar:

- `TrabajadorPagoCajaService.php`;
- servicios transaccionales;
- POST de pago;
- `movimientos_caja` con escrituras;
- permisos/auth;
- PWA/offline;
- `/api/sync`.

## Validaciones obligatorias para 5E-C-A

Una futura implementacion del simulador debe demostrar:

1. PHP lint en archivos tocados.
2. Ruta GET registrada.
3. HTTP sin sesion redirige a login.
4. Pantalla autenticada no contiene `method="POST"`.
5. Pantalla autenticada no contiene boton de pago real.
6. No cambia conteo de `trabajador_pagos_caja`.
7. No cambia conteo de `movimientos_caja`.
8. No cambia conteo de `trabajador_pagos`, `trabajador_anticipos` ni
   `trabajador_prestamos`.
9. Preflight 5E con `ERROR: 0`.
10. Health general con `ERROR: 0`.
11. `/api/sync` sigue bloqueado con HTTP 423 y JSON `sync_temporarily_disabled`.

## Fuera de alcance de 5E-C-0

No se implementa:

- ruta;
- controlador;
- modelo;
- vista;
- formulario;
- boton;
- POST;
- servicio;
- token;
- prueba rollback;
- pago laboral real;
- movimiento de Caja;
- categoria de Caja;
- liquidacion de anticipos/prestamos;
- nomina automatica;
- reversion;
- permisos nuevos;
- migracion;
- PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Siguiente paso seguro

El siguiente paso seguro es `5E-C-A`: implementar el simulador GET/read-only, solo si
el usuario autoriza tocar rutas, controlador/modelo read-only, vista y checkers.

No implementar pago real ni POST hasta un contrato posterior de servicio
transaccional.

## Seguimiento 5E-C-A

La implementacion GET/read-only fue completada en:

- `docs/fase_5E_C_A_simulador_pago_laboral_caja.md`.
- Ruta: `GET /trabajadores/pagos-caja/simulador`.

El contrato de esta fase sigue sin autorizar pago real, POST, servicio
transaccional, token, reversion ni movimientos de Caja.
