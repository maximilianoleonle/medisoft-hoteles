# Contratos de fase - cola autonoma

## Fase 3B aplicada: cuentas por pagar base read-only

### Objetivo

Alinear codigo y base de datos local despues de haber aplicado la migracion CxP, dejando una fundacion read-only segura para cuentas por pagar.

### Riesgo

Naranja.

Motivo:

- toca base de datos;
- crea estructuras financieras;
- debe mantenerse read-only;
- no debe tocar Caja;
- no debe registrar pagos;
- no debe generar saldos automaticamente desde compras.

### Incluye

- migracion no destructiva ya aplicada:
  - `cuentas_por_pagar`;
  - `cuentas_por_pagar_movimientos`.
- modelo read-only de CxP;
- controlador read-only;
- rutas:
  - listado CxP;
  - detalle CxP.
- vistas read-only:
  - indice;
  - detalle.
- navegacion basica en sidebar bajo alcance de inventario;
- health/preflight compatible con las nuevas tablas;
- documentacion de QA, rollback y decisiones.

### No incluye

- pagos a proveedores;
- integracion con Caja;
- generacion automatica de CxP desde compras;
- edicion operativa de saldos;
- eliminacion de datos;
- cambios en facturacion;
- cambios en `/api/sync`;
- CxC;
- nomina;
- permisos profundos.

### Definition of Done

- `git status` revisado.
- Cambios pendientes clasificados.
- Archivos CxP identificados.
- Cambio no relacionado en `src/app/views/reservaciones/ver.php` separado o reportado.
- `php -l` ejecutado sobre:
  - `src/app/controllers/CuentaPorPagarController.php`;
  - `src/app/models/CuentaPorPagar.php`;
  - `src/app/views/cuentas_por_pagar/index.php`;
  - `src/app/views/cuentas_por_pagar/ver.php`;
  - `src/config/routes.php`;
  - `src/app/views/layout/sidebar.php`;
  - checkers modificados.
- Checkers ejecutados:
  - `src/tools/saas/health_check_fase_1a.php`;
  - `src/tools/saas/preflight_compras_minimas.php`;
  - `src/tools/saas/preflight_recepcion_compras.php`.
- Validacion SQL confirmada:
  - tablas existen;
  - migracion registrada;
  - tablas CxP siguen vacias si no hay datos de prueba;
  - no se generaron pagos;
  - no se toco Caja.
- Prueba HTTP sin sesion:
  - `/cuentas-por-pagar` redirige a login o bloquea acceso segun patron del sistema.
- `/api/sync` sigue bloqueado con HTTP 423 segun checker.
- `git diff --check` sin errores bloqueantes.
- Documentacion actualizada:
  - estado autonomo;
  - QA pendiente;
  - contrato;
  - decisiones;
  - rollback;
  - resumen ejecutivo;
  - fuentes de verdad.
- Commit estable realizado solo con archivos relacionados con Fase 3B y documentacion correspondiente.

### Commit esperado

`feat(phase-3b): add read-only accounts payable foundation`

### Estado posterior

Al cerrar esta fase, no avanzar a Fase 3C sin nuevo mensaje real. El estado debe quedar en revision de usuario o cierre del bloque autorizado.

## Fase 3C: CxP operativa controlada sin Caja

### Reanclaje de estado

Estado formal vigente: `AUDITORIA_SEGURIDAD_3C_COMPLETADA`.

El repositorio contiene commits de implementacion posteriores al contrato, pero el nuevo reanclaje impide tratarlos como cierre formal:

- `9897465`: contrato 3C-0 completado.
- `c216dc5`: codigo de simulador, base parcial para 3C-A.
- `cb83121`: antecedente historico de generacion manual; la implementacion vigente reintroduce 3C-B de forma controlada.
- `5dfe665`: codigo de validaciones read-only conservado en health/preflights.
- `8765258`: antecedente de ajuste/revision prematura; la revision tecnica actual ya fue ejecutada despues de 3C-C.
- `2662998`: auditoria prematura/documental; no sustituye auditoria de seguridad post-3C-C.

Siguiente paso formal: cierre tecnico 3C, solo si el usuario lo autoriza.

### Objetivo

Permitir generar cuentas por pagar manualmente desde compras recibidas, de forma controlada, auditable y sin integracion con Caja ni pagos.

### Riesgo

Naranja.

Motivo:

- escribe en `cuentas_por_pagar`;
- crea saldos financieros;
- requiere evitar duplicados por compra;
- no debe mover dinero;
- no debe tocar Caja.

### Incluye

- contrato y diagnostico;
- simulador read-only de CxP generable desde compras recibidas;
- vista de compras recibidas elegibles y bloqueadas;
- accion manual para generar CxP desde compra recibida, solo despues del simulador;
- validaciones por hotel, proveedor, estado, total y no duplicado;
- auditoria si aplica;
- health/preflight/checkers;
- QA, rollback, fuentes de verdad y cierre tecnico.

### No incluye

- pagos a proveedores;
- abonos;
- Caja;
- movimientos de Caja;
- conciliacion;
- generacion automatica al recibir compra;
- edicion manual de saldos;
- cancelacion operativa de CxP;
- CxC;
- nomina;
- permisos profundos;
- cambios en `/api/sync`.

### Definition of Done

- Contrato documentado.
- Simulador read-only verificado.
- Generacion manual implementada solo si simulador quedo estable.
- No hay generacion automatica.
- No hay pagos ni Caja.
- CxP no duplica compras.
- CxP respeta `hotel_id`.
- CxP respeta proveedor/compra del mismo hotel.
- CxP solo nace de compra recibida.
- Checkers sin errores bloqueantes.
- `php -l` en archivos tocados.
- HTTP sin sesion bloquea rutas sensibles.
- QA manual documentada.
- Rollback documentado.
- Revision tecnica y auditoria de seguridad completadas.
- Commits por subfase.

### Estado 3C-0

Contrato y diagnostico documentados en `docs/fase_3C_0_contrato_diagnostico.md`. No se implemento simulador ni generacion manual en 3C-0.

### Estado 3C-A

Simulador read-only de generacion CxP desde compras implementado como preview GET:

- ruta: `GET /cuentas-por-pagar/generacion-preview`;
- controlador: `CuentaPorPagarController::generacionPreviewAction()`;
- modelo: `CuentaPorPagar::previewGeneracionDesdeCompras()`;
- vista: `app/views/cuentas_por_pagar/generacion_preview.php`;
- acceso protegido por los mismos guards de CxP (`requireAuth`, contexto hotelero y modulo `inventario`);
- consulta filtrada por `hotel_id`;
- sin POST;
- sin CSRF porque no hay escritura;
- sin pagos;
- sin Caja;
- sin generacion automatica.

Definition of Done 3C-A:

- mostrar compra, proveedor, hotel, fecha, total y estado;
- mostrar si ya existe CxP vinculada;
- mostrar motivo de elegibilidad o bloqueo;
- incluir links read-only a compra, proveedor y CxP cuando existan;
- mantener estado vacio claro;
- validar `php -l` en archivos PHP tocados;
- ejecutar checkers/preflights;
- confirmar HTTP sin sesion redirige o bloquea;
- confirmar conteos DB sin escritura antes/despues.

### Estado 3C-B

La generacion manual controlada de CxP desde compra recibida esta implementada y verificada automaticamente:

- ruta: `POST /cuentas-por-pagar/generar-desde-compra/{id}`;
- controlador: `CuentaPorPagarController::generarDesdeCompraAction()`;
- modelo: `CuentaPorPagar::generarDesdeCompraRecibida()`;
- boton `Generar CxP` visible solo en filas elegibles del preview;
- CSRF obligatorio;
- transaccion propia;
- bloqueo `FOR UPDATE` sobre la compra y verificacion de CxP existente;
- auditoria con `AuditService::record()`;
- redireccion al detalle de CxP generada;
- sin pagos;
- sin abonos;
- sin Caja;
- sin movimientos de Caja;
- sin generacion automatica desde recepcion.

Definition of Done 3C-B:

- no genera CxP desde compras no recibidas;
- no duplica CxP por compra;
- valida `hotel_id` de compra y proveedor;
- valida total positivo y detalles existentes;
- prueba controlada local con backup previo;
- prueba de doble generacion falla limpiamente;
- checkers/preflights actualizados;
- `php -l`, health, preflights y `git diff --check` sin errores bloqueantes.
- QA manual reportada por el usuario como OK para preview, CxP `#2`, detalle CxP, origen compra/proveedor, compras recibidas, proveedor, detalle compra y ausencia de pagos/abonos/Caja.

Prueba local controlada:

- backup previo: `src/storage/backups/phase3c_b_20260615_144908_before_manual_cxp_medisoft_hoteles_import.sql`;
- SHA256: `C0403F7B5ACBDA35EF4C05E2840546D5D9978802A21C9736BEBC6FF462518061`;
- compra usada: `compra_id=2`, `hotel_id=4`;
- CxP creada: `id=2`, `total=1900.00`, `saldo=1900.00`;
- doble generacion bloqueada con mensaje de CxP existente;
- `cuentas_por_pagar`: `1 -> 2`;
- `cuentas_por_pagar_movimientos`: `0 -> 0`;
- `cajas`: `3 -> 3`;
- `movimientos_caja`: `1402 -> 1402`;
- `logs_auditoria`: `25 -> 26`.

### Codigo historico 3C-C y revision post-QA

Validaciones de consistencia agregadas a health/preflights quedan formalizadas como Fase 3C-C read-only:

- CxP duplicada por compra/hotel;
- CxP con compra inexistente;
- CxP con proveedor inexistente;
- CxP con `hotel_id` nulo;
- CxP con compra o proveedor de otro hotel;
- CxP generada desde compra no recibida;
- CxP con saldo/total invalido;
- CxP sin fecha de emision;
- movimientos CxP/pagos/abonos accidentales;
- referencias CxP en movimientos de Caja.

Revision tecnica 3C actual: rutas, controlador, modelo, vistas, sidebar, guards, CSRF, filtros `hotel_id`, CxP `#2`, ausencia de pagos/abonos/Caja y `/api/sync` sin cambios revisados sin hallazgos bloqueantes.

Revision tecnica post-QA:

- el preview enlaza proveedor solo si el proveedor fue resuelto dentro del mismo `hotel_id`;
- sin nuevas rutas;
- sin nuevas migraciones;
- sin pagos;
- sin Caja;
- sin Fase 3D.

## Bloque Personal y Nomina (Fase NP): modulo independiente de trabajadores

### Objetivo

Crear un modulo INDEPENDIENTE de Personal y Nomina para registrar trabajadores
(sin acceso obligatorio al sistema), roles laborales, pagos, anticipos, prestamos,
asistencia y saldos por persona; con contabilidad laboral auditable y multi-hotel,
PERO SIN integracion con Caja ni salida real de dinero en este bloque.

Un "pago a trabajador" en este bloque es un REGISTRO LABORAL que afecta el saldo del
trabajador; NO genera movimiento de Caja. La salida real de dinero se difiere a un
bloque posterior (Fase NP-Caja).

### Riesgo

Naranja.

Motivo:

- crea un modulo financiero-laboral nuevo (deuda y saldos por persona);
- toca un concepto sensible (trabajadores antes ligados a `usuarios`);
- pero NO mueve dinero, NO toca Caja, NO altera `usuarios` destructivamente, NO toca `/api/sync`.

### Incluye

- contrato y diagnostico (NP-0);
- migraciones ADITIVAS reversibles de 6 tablas nuevas (`trabajadores`,
  `trabajador_pagos`, `trabajador_anticipos`, `trabajador_prestamos`,
  `trabajador_asistencias`, `trabajador_documentos`);
- ficha basica de trabajador (listado + ficha read-first, luego CRUD controlado);
- pagos, anticipos y prestamos como ledger laboral;
- saldos por trabajador y reportes read-only semanal/quincenal e historial;
- asistencia y comisiones/bonos/descuentos como conceptos del ledger;
- referencia logica/opcional trabajador-responsable de mantenimiento;
- checkers de consistencia laboral, health/preflight;
- QA, rollback, fuentes de verdad, revision tecnica, auditoria y cierre.

### No incluye

- integracion con Caja; categoria "Nomina" que mueva Caja; movimientos de Caja;
- salida real de dinero; conciliacion;
- edicion manual destructiva de saldos;
- conversion/fusion de usuarios existentes en trabajadores;
- ALTER destructivo sobre `usuarios`; borrado de usuarios; permisos profundos / auth;
- CxC; cambios en `/api/sync`;
- migraciones destructivas; eliminacion de tablas/columnas; borrado de datos;
- push; produccion; secrets; refactors grandes.

### Definition of Done del bloque NP

1. Contrato documentado.
2. Migraciones aditivas creadas y reversibles.
3. Ficha basica de trabajador creada y verificada.
4. Trabajador independiente de `usuarios` (no requiere login).
5. Pagos/anticipos/prestamos registrados como ledger laboral.
6. Saldos calculados correctamente desde el ledger.
7. Reportes semanal/quincenal e historial read-only funcionando.
8. Asistencia y comisiones registradas.
9. No hay integracion con Caja.
10. No hay movimientos de Caja.
11. No hay salida real de dinero.
12. No se altero `usuarios` de forma destructiva.
13. Todo respeta `hotel_id`.
14. Checkers pasan con cero errores bloqueantes.
15. `php -l` pasa en archivos tocados.
16. HTTP sin sesion bloquea rutas sensibles.
17. QA manual documentada.
18. Rollback documentado.
19. Revision tecnica completada.
20. Auditoria de seguridad completada.
21. Commit por fase/subfase.
22. Estado Git explicado.

### Subfases y commits sugeridos

- NP-0: contrato y diagnostico -> `docs(phase-np): define independent payroll module contract`.
- NP-A: ficha basica de trabajador -> `feat(phase-np): add worker basic profile and listing`.
- NP-B: pagos, anticipos y prestamos (sin Caja) -> `feat(phase-np): record worker payments, advances and loans`.
- NP-C: saldos y reportes read-only -> `feat(phase-np): add worker balances and payroll reports`.
- NP-D: asistencia y comisiones -> `feat(phase-np): add worker attendance and commissions`.
- NP-E: validaciones, health y preflights -> `test(phase-np): add payroll consistency checks`.
- NP-F: documentacion y cierre -> `docs(phase-np): close independent payroll module block`.

### Estado NP-0

Contrato, diagnostico read-only y diseno aditivo de las 6 tablas documentados en
`docs/fase_NP_0_contrato_diagnostico.md`. No se implemento funcionalidad, no se crearon
migraciones aplicadas y no se escribio en la base de datos. La formula de saldo por
trabajador y la referencia logica de "responsable" quedan especificadas para NP-C y el
alcance #9.
