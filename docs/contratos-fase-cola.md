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

Estado formal vigente: `FASE_3C_VALIDADA_MANUALMENTE`.

El repositorio contiene commits de implementacion posteriores al contrato, pero el nuevo reanclaje impide tratarlos como cierre formal:

- `9897465`: contrato 3C-0 completado.
- `c216dc5`: codigo de simulador, base parcial para 3C-A.
- `cb83121`: antecedente historico de generacion manual; la implementacion vigente reintroduce 3C-B de forma controlada.
- `5dfe665`: codigo de validaciones read-only conservado en health/preflights.
- `8765258`: antecedente de ajuste/revision prematura; la revision tecnica actual ya fue ejecutada despues de 3C-C.
- `2662998`: auditoria prematura/documental; no sustituye auditoria de seguridad post-3C-C.

Siguiente paso formal: triage separado de cambios PWA/no relacionados. No avanzar a pagos, abonos, Caja ni Fase 3D.

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

### Cierre tecnico 3C

Estado: `FASE_3C_VALIDADA_MANUALMENTE`.

- Contrato, 3C-A, 3C-B, 3C-C, revision tecnica, auditoria seguridad, rollback, fuentes de verdad y QA quedan documentados.
- 3C-A y 3C-B fueron validadas manualmente por el usuario.
- QA manual final del bloque 3C fue reportada como OK por el usuario.
- 3C-C queda validada automaticamente por health/preflights y SQL read-only.
- No se autoriza pagos, abonos, Caja, Fase 3D ni cambios en `/api/sync`.
- Cambios no relacionados quedan fuera del bloque CxP.

## Fase 4A: Centro Documental Base

### Estado

Estado formal vigente: `CONTRATO_4A_COMPLETADO`.

### Objetivo

Crear una fundacion segura para adjuntar, consultar y relacionar documentos con
proveedores, compras, CxP, huespedes, reservaciones y trabajadores futuros, sin Caja,
pagos, abonos, Fase 3D ni `/api/sync`.

### Alcance 4A-0

- Diagnostico de infraestructura documental/uploads.
- Revision de almacenamiento local, guards, permisos y rutas.
- Propuesta de tablas base.
- Contrato, semaforo, Definition of Done, QA y rollback.
- Sin uploads, sin POST, sin migraciones aplicadas, sin escrituras en DB.

### Propuesta base

- `documento_tipos`: catalogo global/por hotel de tipos.
- `documentos`: metadata del archivo, `hotel_id`, storage privado, MIME, tamano,
  hash, estado y autoria.
- `documento_entidades`: relacion polimorfica con `proveedor`, `compra`,
  `cuenta_por_pagar`, `huesped`, `reservacion` y `trabajador`.

### Regla de seguridad

El almacenamiento predeterminado debe ser privado en `STORAGE_PATH/documentos`, con
descarga por controlador autenticado y validacion de `hotel_id`. No usar
`public_html/uploads` para documentos privados.

### Siguiente paso

`[COLA_4A_A_MIGRACION_BASE_DOCUMENTOS]`, solo si se autoriza crear migracion
idempotente y no destructiva.

## Fase 4B-0: Descarga segura de documentos

### Estado

Estado formal vigente: `DESCARGA_SEGURA_4B_VALIDADA_MANUALMENTE`.

### Objetivo

Definir la descarga autenticada de archivos privados del Centro Documental sin
implementar todavia rutas ni lectura de archivos.

### Alcance 4B-0

- Contrato de ruta futura `GET /documentos/{id}/descargar`.
- Reglas de `requireAuth`, contexto hotelero, modulo relacionado y `hotel_id`.
- Regla de `realpath` bajo `STORAGE_PATH/documentos`.
- Headers privados y nombre de archivo seguro.
- Riesgos, rollback y Definition of Done.
- Sin implementacion, sin POST, sin DB, sin Caja, pagos, abonos ni `/api/sync`.

### Siguiente paso

QA manual de `GET /documentos/{id}/descargar` antes de avanzar a edicion, borrado,
links publicos o auditoria de descargas.

## Fase 4B-B: Auditoria de descargas documentales

### Estado

Estado formal vigente: `AUDITORIA_DESCARGAS_4B_B_VALIDADA_MANUALMENTE`.

### Contrato

- Registrar descarga exitosa de documentos privados.
- Registrar intentos bloqueados despues de autenticacion y contexto hotelero.
- Usar `AuditService::record()`.
- No exponer `storage_path`, `nombre_archivo` ni URL publica.
- No agregar rutas, POST, edicion, borrado, Caja, pagos, abonos ni `/api/sync`.

### Definition of Done

- `documentos.descargado` aparece para descarga valida.
- `documentos.descarga_bloqueada` aparece para documento no disponible o archivo no
  resoluble.
- La descarga sigue funcionando.
- Los fallos de auditoria son tolerantes.
- QA manual documentada.

## Fase 4B-C-A: Edicion controlada de metadata documental

### Estado

Estado formal vigente: `METADATA_DOCUMENTAL_4B_C_A_VALIDADA_MANUALMENTE`.

### Contrato

- Permitir editar solo metadata segura: `titulo`, `descripcion`, `etiquetas` y tipo
  documental valido para el hotel.
- Mantener archivo fisico, `storage_path`, `nombre_archivo`, `sha256`, `mime_type`,
  `size_bytes` y `hotel_id` fuera del formulario.
- Usar GET protegido para formulario y POST con CSRF para guardar.
- Registrar `documentos.metadata_actualizada` con antes/despues seguro.
- No implementar borrado, reemplazo de archivo, links publicos, Caja, pagos, abonos ni
  `/api/sync`.

### Implementacion

- `GET /documentos/{id}/editar`.
- `POST /documentos/{id}/actualizar`.
- `Documento::actualizarMetadata()` centraliza validacion y escritura.
- Vista `documentos/editar.php` no expone rutas internas.

## Cierre tecnico Fase 4B

Estado formal vigente: `CIERRE_TECNICO_4B_COMPLETADO`.

Alcance del cierre:

- 4B-0 contrato de descarga segura.
- 4B-A descarga autenticada.
- 4B-B auditoria de descargas.
- 4B-C-A edicion controlada de metadata documental.
- Revision tecnica y auditoria seguridad post-QA.

Fuera de alcance del cierre:

- Borrado fisico o baja logica.
- Reemplazo de archivo.
- Links publicos o tokens de documentos.
- Caja, pagos, abonos, Fase 3D, NP-A y `/api/sync`.

## Fase 4C-0: Documentos por entidad

Estado formal vigente: `CONTRATO_4C_DOCUMENTOS_ENTIDAD_COMPLETADO`.

Objetivo:

- Definir la integracion contextual de documentos en fichas operativas.
- Entidades objetivo: proveedor, compra, cuenta por pagar, huesped y reservacion.
- Usar `documento_entidades` como relacion polimorfica ya existente.
- No implementar funcionalidad en 4C-0; solo contrato y diagnostico.

Alcance implementado 4C-A:

- Secciones contextuales en fichas de entidad.
- Estado vacio claro.
- Links a detalle, descarga autenticada y metadata ya protegidas.
- Link a carga contextual existente solo si la entidad pertenece al hotel.

Fuera de alcance:

- Borrado, reemplazo de archivo, links publicos, pagos, abonos, Caja, Fase 3D, NP-A,
  PWA/offline y `/api/sync`.

Documento:

- `docs/fase_4C_0_contrato_documentos_entidad.md`.

## Fase 4C-A: Secciones documentales contextuales por entidad

Estado formal vigente: `DOCUMENTOS_ENTIDAD_CONTEXTUAL_4C_VALIDADA_MANUALMENTE`.

Alcance implementado:

- Partial reutilizable `src/app/views/partials/documentos_entidad.php`.
- Secciones de lista segura en fichas de proveedor, compra, cuenta por pagar, huesped y
  reservacion.
- Consultas por entidad usando `Documento::documentosPorEntidad()` y filtro `hotel_id`.
- Enlaces GET a detalle documental y descarga autenticada ya existente.
- Enlace GET `Vincular documento` hacia `documentos/subir?entidad_tipo=...&entidad_id=...`
  usando el flujo protegido existente.
- Estado vacio claro cuando no hay documentos vinculados.
- QA manual post-hotfix reportada por el usuario como correcta.

Fuera de alcance:

- Formularios nuevos en fichas, POST nuevos, edicion de metadata desde la ficha,
  borrado, reemplazo de archivo, links publicos, permisos nuevos, Caja, pagos, abonos,
  Fase 3D, NP-A, PWA/offline y `/api/sync`.

Documento:

- `docs/fase_4C_A_documentos_entidad_readonly.md`.

## Fase 4D-0: Archivado documental

Estado formal vigente: `CONTRATO_4D_ARCHIVADO_DOCUMENTAL_COMPLETADO`.

Objetivo:

- Definir archivado/restauracion y baja logica futura de documentos.
- Mantener todo reversible por estado; sin borrado fisico.
- No implementar funcionalidad en 4D-0; solo contrato y diagnostico.

Alcance futuro propuesto:

- 4D-A: `activo <-> archivado` con POST + CSRF, filtro `hotel_id` y auditoria.
- 4D-B: baja logica hacia `eliminado` con confirmacion fuerte, si se autoriza despues.

Fuera de alcance:

- Borrado fisico, `DELETE` SQL, reemplazo de archivo, links publicos, Caja, pagos,
  abonos, Fase 3D, NP-A, PWA/offline y `/api/sync`.

Documento:

- `docs/fase_4D_0_contrato_archivado_documental.md`.

## Fase 4D-A: Archivado/restauracion documental

Estado formal vigente: `FASE_4D_A_VALIDADA_MANUALMENTE`.

Alcance implementado:

- POST `/documentos/{id}/archivar`.
- POST `/documentos/{id}/restaurar`.
- Transiciones permitidas: `activo -> archivado` y `archivado -> activo`.
- Validacion central en `Documento::actualizarEstado()`.
- Auditoria `documentos.estado_actualizado`.
- Acciones visibles solo en detalle documental y con CSRF.
- Revision tecnica/auditoria: sin hallazgos bloqueantes; no hay borrado fisico,
  `DELETE`, Caja, pagos, abonos ni cambios en `/api/sync`.
- QA manual: reportada por el usuario como correcta.

Fuera de alcance:

- Baja logica hacia `eliminado`, borrado fisico, `DELETE` SQL, reemplazo de archivo,
  links publicos, Caja, pagos, abonos, Fase 3D, NP-A y `/api/sync`.

Documento:

- `docs/fase_4D_A_archivado_documental_controlado.md`.

## Fase 4D-B-0: Contrato de baja logica documental

Estado formal vigente: `CONTRATO_4D_B_BAJA_LOGICA_DOCUMENTAL_COMPLETADO`.

Alcance documentado:

- Baja logica futura hacia `eliminado`.
- Ruta futura propuesta: `POST /documentos/{id}/eliminar`.
- Transiciones futuras propuestas: `activo -> eliminado` y `archivado -> eliminado`.
- Confirmacion fuerte, POST + CSRF, filtro `id + hotel_id` y auditoria obligatoria.
- Sin restauracion desde `eliminado` en 4D-B.

Fuera de alcance:

- Implementacion de rutas, controladores, modelo, vistas o DB en 4D-B-0.
- Borrado fisico, `DELETE`, baja masiva, reemplazo de archivo, links publicos, Caja,
  pagos, abonos, Fase 3D, NP-A y `/api/sync`.

Documento:

- `docs/fase_4D_B_0_contrato_baja_logica_documental.md`.

## Fase 4D-B-A: Baja logica documental controlada

Estado formal vigente: `BAJA_LOGICA_DOCUMENTAL_4D_B_A_COMPLETADA_QA_DIFERIDA`.

Alcance implementado:

- POST `/documentos/{id}/eliminar`.
- `DocumentoController::eliminarAction()`.
- `Documento::actualizarEstado()` permite `activo/archivado -> eliminado`.
- Boton `Baja logica` en detalle documental solo para documentos `activo` o
  `archivado`.
- CSRF, confirmacion fuerte y auditoria `documentos.estado_actualizado`.

Fuera de alcance:

- Restauracion desde `eliminado`, borrado fisico, `DELETE`, baja masiva, reemplazo de
  archivo, links publicos, Caja, pagos, abonos, Fase 3D, NP-A y `/api/sync`.

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

### Estado NP-A

Estado formal vigente: `PERSONAL_READ_ONLY_NP_A_COMPLETADO_QA_DIFERIDA`.

- Migracion aditiva aplicada: `migrations/20260616_001_fase_np_a_personal_base.sql`.
- Tablas creadas vacias: `trabajadores`, `trabajador_pagos`,
  `trabajador_anticipos`, `trabajador_prestamos`, `trabajador_asistencias`,
  `trabajador_documentos`.
- Backup previo verificado.
- Sin datos semilla.
- UI read-first implementada:
  - `GET /trabajadores`;
  - `GET /trabajadores/{id}`.
- Sin POST, altas, edicion, pagos, anticipos, prestamos, asistencia operativa ni
  documentos laborales.
- Sin Caja, pagos reales, categoria Nomina ni `/api/sync`.

### Estado siguiente sugerido NP-B

Definir contrato de alta/edicion segura de trabajador antes de implementar escritura.
Debe seguir sin Caja, sin nomina operativa y sin pagos reales, con POST + CSRF,
auditoria, validacion de duplicados razonable y filtro `hotel_id`.

### Estado NP-B-0

Estado formal vigente: `CONTRATO_NP_B_CRUD_TRABAJADORES_COMPLETADO`.

- Documento: `docs/fase_NP_B_0_contrato_crud_trabajadores.md`.
- Se limita la siguiente implementacion a CRUD basico de `trabajadores`.
- No autoriza pagos, anticipos, prestamos, asistencia operativa, documentos laborales,
  Caja, categoria Nomina ni `/api/sync`.
- Define POST + CSRF, auditoria, filtro `hotel_id`, baja logica y rollback sin `DELETE`.

### Estado NP-B-A

Estado formal vigente: `CRUD_TRABAJADORES_NP_B_A_COMPLETADO_QA_DIFERIDA`.

- CRUD basico de trabajador implementado con POST + CSRF.
- Solo escribe en `trabajadores`.
- No implementa pagos, anticipos, prestamos, asistencia operativa, documentos laborales,
  Caja, categoria Nomina ni `/api/sync`.

## Bloque Tareas, Limpieza y Mantenimiento (Fase TLM)

### Objetivo

Crear una capa operativa de tareas para limpieza, mantenimiento ligero y tareas generales
sin reemplazar la fuente actual de estados de habitacion ni el historial existente de
`mantenimientos_habitaciones`.

### Estado TLM-0

Estado formal vigente: `CONTRATO_TLM_0_COMPLETADO`.

- Documento: `docs/fase_TLM_0_contrato_diagnostico.md`.
- Diagnostico confirma que hoy NO existe tabla propia de tareas.
- Fuente actual de disponibilidad: `habitaciones.estado`.
- Fuente actual de mantenimiento historico/programado: `mantenimientos_habitaciones`.
- `mantenimientos_habitaciones` tiene 10 registros y 0 sin `hotel_id`.
- Habitaciones actuales: 12 en `limpieza` y 2 en `mantenimiento`.
- Modulos `limpieza` y `mantenimiento` existen y estan activos para los 4 hoteles revisados.
- TLM-0 no crea migraciones, rutas, controladores, modelos, vistas ni cambios de DB.

### Reglas duras TLM

- No tocar `/api/sync`.
- No tocar Caja, pagos, abonos ni nomina.
- No reescribir check-in/check-out, reservaciones ni disponibilidad.
- No borrar ni fusionar `mantenimientos_habitaciones`.
- No cambiar `habitaciones.estado` automaticamente desde tareas en la primera etapa.
- Todo futuro POST debe usar CSRF y auditar cambios sensibles.
- Toda tabla futura debe incluir `hotel_id`.

### Subfases sugeridas TLM

- TLM-0: contrato y diagnostico -> `docs(phase-tlm): define tasks housekeeping maintenance contract`.
- TLM-A: migracion base aditiva -> `feat(phase-tlm): add operational tasks base schema`.
- TLM-B: capa read-only -> `feat(phase-tlm): add read-only operational tasks layer`.
- TLM-C: creacion manual de tarea -> `feat(phase-tlm): create operational tasks manually`.
- TLM-D: asignacion opcional a trabajador -> `feat(phase-tlm): assign tasks to workers`.
- TLM-E: cierre/cancelacion manual -> `feat(phase-tlm): complete and cancel operational tasks`.
- TLM-F: integracion contextual en habitacion/trabajador -> `feat(phase-tlm): show contextual operational tasks`.
- TLM-G: health/preflights -> `test(phase-tlm): add operational task consistency checks`.
- TLM-H: revision, auditoria y cierre -> `docs(phase-tlm): close operational tasks block`.

### Estado TLM-A

Estado formal vigente: `MIGRACION_TLM_A_TAREAS_BASE_COMPLETADA_QA_DIFERIDA`.

- Migracion aplicada: `migrations/20260616_002_fase_tlm_a_tareas_base.sql`.
- Backup previo verificado:
  `src/storage/backups/phase_tlm_a_20260616_030648_before_tasks_base_medisoft_hoteles_import.sql`.
- SHA256: `C76243D8AF98A2D9AA9D94FB5B1BB4D29369A861594141C7EC397A13AF3CF242`.
- Tablas creadas vacias:
  - `tareas_operativas`;
  - `tarea_eventos`.
- No se crearon rutas, UI, controladores, modelos ni POST.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.

### Estado TLM-B

Estado formal vigente: `TAREAS_READ_ONLY_TLM_B_COMPLETADO_QA_DIFERIDA`.

- Modelo read-only: `TareaOperativa`.
- Controlador GET: `TareaController`.
- Rutas:
  - `GET /tareas`;
  - `GET /tareas/{id}`.
- Vistas read-only con filtros GET y estado vacio.
- Sidebar muestra `Tareas` bajo Operaciones.
- No hay POST, creacion, asignacion, cierre, cancelacion ni cambios de habitacion.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.

### Estado TLM-C

Estado formal vigente: `CREACION_MANUAL_TLM_C_COMPLETADA_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_C_creacion_manual_tareas.md`.
- Rutas:
  - `GET /tareas/crear`;
  - `POST /tareas`;
  - `GET /tareas/{id}`.
- Alta manual con POST + CSRF, permiso `habitaciones.mantenimiento`, auditoria y
  transaccion.
- La tarea se crea en `tareas_operativas`, estado `pendiente`, origen `manual`.
- El evento inicial se crea en `tarea_eventos`.
- No hay asignacion, inicio, cierre, cancelacion ni cambios de habitacion.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

### Estado TLM-D

Estado formal vigente: `ASIGNACION_TLM_D_COMPLETADA_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_D_asignacion_trabajador.md`.
- Ruta:
  - `POST /tareas/{id}/asignar`.
- Asignacion controlada con POST + CSRF, permiso `habitaciones.mantenimiento`,
  auditoria y transaccion.
- Solo permite tareas `pendiente` o `asignada`.
- Valida trabajador activo del mismo hotel.
- La tarea queda `asignada` y registra evento `asignada`.
- No inicia, completa ni cancela tareas.
- No crea asistencia, pagos, abonos, Caja ni nomina.
- No cambia `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones` ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

### Estado TLM-E

Estado formal vigente: `ESTADOS_TLM_E_COMPLETADOS_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_E_estados_tarea.md`.
- Rutas:
  - `POST /tareas/{id}/iniciar`;
  - `POST /tareas/{id}/completar`;
  - `POST /tareas/{id}/cancelar`.
- Transiciones manuales con POST + CSRF, permiso `habitaciones.mantenimiento`,
  auditoria y transaccion.
- Eventos registrados: `iniciada`, `completada`, `cancelada`.
- No crea asistencia, pagos, abonos, Caja ni nomina.
- No cambia `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones` ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

### Estado TLM-F

Estado formal vigente: `CONTEXTUAL_TLM_F_COMPLETADO_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_F_contextual_tareas.md`.
- Integracion contextual read-only completada.
- Fichas impactadas:
  - `habitaciones/ver.php`;
  - `trabajadores/ver.php`.
- Consulta central: `TareaOperativa::listarPorEntidadHotel()`.
- Sin rutas nuevas, sin POST nuevos y sin cambios de DB.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.

### Estado TLM-G

Estado formal vigente: `PREFLIGHT_TLM_G_COMPLETADO_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_G_preflight_consistencia.md`.
- Herramienta: `src/tools/saas/preflight_tareas_operativas.php`.
- Health checker conoce TLM-G.
- Solo lectura; sin rutas, UI, POST ni migraciones.
- Valida consistencia de tareas, eventos, entidades, fechas, estados y ausencia de Caja.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.
