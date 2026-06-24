# Resumen ejecutivo - cola autonoma

## Reanclaje Fase 3C

Estado vigente: `FASE_3C_VALIDADA_MANUALMENTE`.

Motivo: despues del reanclaje, se formalizo 3C-A primero, luego 3C-B como generacion manual controlada y despues 3C-C como validaciones read-only en health/preflights. La revision tecnica 3C confirmo rutas, guards, CSRF, `hotel_id`, relacion compra-proveedor-hotel, CxP `#2`, ausencia de pagos/abonos/Caja y `/api/sync` sin cambios. La auditoria de seguridad 3C confirma que no hay hallazgos bloqueantes.

Estado formal vigente:

- Fase 3C-0 contrato y diagnostico: completada.
- Fase 3C-A simulador read-only: completada tecnicamente, commiteada y validada manualmente.
- Fase 3C-B generacion manual: completada tecnicamente, commiteada y validada manualmente.
- Fase 3C-C validaciones/health/preflights: completada tecnicamente como verificacion read-only.
- Revision tecnica 3C actual: completada sin hallazgos bloqueantes.
- Auditoria seguridad 3C actual: completada sin hallazgos bloqueantes.
- Cierre tecnico 3C: completado documentalmente.
- QA manual final 3C: completada por el usuario.
- Auditoria `2662998`: reclasificada como auditoria prematura/documental.
- Siguiente accion recomendada: continuar solo con Fase 4A autorizada. No avanzar a pagos, abonos, Caja ni Fase 3D.

## Estado final del bloque autorizado anterior

Estado: `CIERRE_TECNICO_COMPLETADO_QA_MANUAL_PENDIENTE`.

El bloque Fase 2X-3B queda como bloque cerrado anterior; Fase 3C queda con 3C-A y 3C-B completadas y validadas manualmente.

## Fases y commits

- Fase 2X: `d1f1431` - detalle read-only de compra recibida.
- Fase 2Y: `32abb7b` - reporte read-only de compras recibidas.
- Fase 2Z: `052fd7a` - endurecimiento de recepcion de compras.
- Fase 3A: `3d8f997` - ficha read-only de proveedor.
- Fase 3B draft: `673f47f` - borrador CxP base.
- Fase 3B aplicada: `1fa1653` - CxP base read-only.
- Cierre/auditoria: `2535dd1`, `ca2bda4`.
- Cierre tecnico final: `8a49995`.
- Ajuste visual reservaciones post-QA: `dc3c150`.

## Nuevo bloque Fase 3C

Objetivo: generar CxP manualmente desde compras recibidas, sin Caja ni pagos.

Estado actual:

- Fase 3C-0 completada con contrato y diagnostico.
- Fase 3C-A completada tecnicamente como simulador read-only: ruta GET, vista, controller, modelo, navegacion desde CxP y guardas existentes.
- Fase 3C-B completada tecnicamente: POST manual con CSRF, validaciones centrales, auditoria y bloqueo de duplicados.
- Fase 3C-C completada tecnicamente: health/preflights validan duplicados, relaciones, saldos, fechas, ausencia de movimientos CxP, ausencia de Caja y ausencia de pagos/abonos.
- SQL read-only 3C-C confirmo `cuentas_por_pagar=2`, `cuentas_por_pagar_movimientos=0`, inconsistencias CxP=0, Caja-CxP=0 y `compra_pagos` inexistente.
- Revision tecnica 3C actual confirma CxP `#2` desde compra `#2`, compra recibida, mismo `hotel_id=4`, proveedor `#2`, total/saldo `1900.00` y fecha de emision presente.
- Auditoria seguridad 3C actual confirma cero movimientos CxP, cero Caja-CxP, `compra_pagos` inexistente, CSRF/guards activos y `/api/sync` bloqueado.
- Cierre tecnico 3C completado: contrato, simulador, generacion manual, validaciones, revision, auditoria, rollback, fuentes de verdad, QA y warnings quedan documentados.
- QA manual final 3C reportada como OK: preview, simulador, CxP existente bloqueada, links, detalle CxP, origen compra/proveedor, CxP desde compra recibida, no duplicados, sin pagos, sin abonos, sin Caja y sin movimientos de Caja.
- Cambios PWA no relacionados al cierre de auditoria fueron validados manualmente y commiteados por separado en `35abdc7`; no forman parte de CxP.
- Cambios no relacionados ya separados en `e52766e`: `DashboardController.php`, `HabitacionController.php`, `NotificacionController.php`, `sidebar.php` y `notificaciones/index.php`.
- QA manual 3C-A/3C-B reportada por el usuario: preview OK, CxP #2 vinculada, detalle CxP OK, origen compra/proveedor visible, sin pagos, sin abonos y sin Caja.
- Siguiente paso recomendado: Fase 4A-A migracion base documental si se autoriza; no pagos, Caja ni Fase 3D.
- No se implementaron pagos ni Caja.
- No avanzar a pagos, Caja ni Fase 3D.

## Bloque Personal operativo base

Estado actual: `BLOQUE_NP_PERSONAL_OPERATIVO_BASE_CERRADO_QA_DIFERIDA`.

- Personal base, ledger laboral, conceptos manuales, anticipos/prestamos manuales,
  asistencia manual y documentos laborales contextuales quedan cerrados tecnicamente.
- El cierre paraguas queda documentado en
  `docs/fase_NP_E_cierre_personal_operativo_base.md`.
- No hay nomina automatica, pagos reales, abonos, Caja ni cambios en `/api/sync`.
- QA manual queda diferida por instruccion del usuario porque no hay trabajadores locales
  suficientes para validar todos los flujos en navegador.
- Siguiente paso recomendado: contrato de reporte read-only de Personal antes de abrir
  cualquier escritura nueva.

## Contrato reporte Personal read-only

Estado actual: `CONTRATO_NP_F_REPORTE_PERSONAL_READONLY_COMPLETADO`.

- Queda definido el contrato NP-F-0 para un reporte read-only de Personal.
- No se implementan rutas, vistas, modelos ni DB en esta subfase.
- El reporte futuro debe consolidar trabajadores, ledger laboral, asistencia, documentos
  y tareas asignadas sin escrituras.
- Quedan prohibidos nomina automatica, pagos reales, abonos, liquidaciones, Caja y
  `/api/sync`.
- Siguiente paso recomendado: NP-F-A implementacion GET/read-only del reporte.

## Reporte Personal read-only implementado

Estado actual: `REPORTE_PERSONAL_NP_F_A_COMPLETADO_QA_DIFERIDA`.

- Ruta nueva: `/trabajadores/reporte`.
- Se implementa solo lectura: sin POST, sin nomina, sin pagos reales, sin abonos y sin
  Caja.
- El reporte consolida trabajadores, ledger laboral, asistencias, documentos y tareas por
  hotel.
- Health checker y preflight laboral conocen el contrato NP-F-A.
- QA manual queda diferida por instruccion del usuario.

## Cierre reporte Personal read-only

Estado actual: `BLOQUE_NP_F_REPORTE_PERSONAL_CERRADO_QA_DIFERIDA`.

- NP-F-A queda cerrado tecnicamente con revision/auditoria documental.
- Verificaciones automaticas: lint PHP OK, preflight Personal `ERROR: 0`, health checker
  `ERROR: 0`, HTTP sin sesion `303`, SQL read-only sin cambios operativos.
- No hay nomina, pagos reales, abonos, Caja ni cambios en `/api/sync`.
- QA manual queda diferida.

## Contrato reporte operativo TLM read-only

Estado actual: `CONTRATO_TLM_I_REPORTE_OPERATIVO_READONLY_COMPLETADO`.

- Queda definido TLM-I-0 para un reporte futuro de tareas operativas solo lectura.
- No se implementan rutas, vistas, modelos ni DB en esta subfase.
- El reporte futuro debe derivar datos de `tareas_operativas`, `tarea_eventos`,
  habitaciones, trabajadores y mantenimientos solo en lectura.
- Quedan prohibidos cambios de estado, asignaciones, habitacion, mantenimiento historico,
  Caja, nomina y `/api/sync`.

## Reporte operativo TLM read-only implementado

Estado actual: `REPORTE_TLM_I_A_COMPLETADO_QA_DIFERIDA`.

- Ruta nueva: `/tareas/reporte`.
- Se implementa solo lectura: sin POST, sin asignar, sin iniciar/completar/cancelar, sin
  cambios de habitacion y sin Caja.
- El reporte consolida tareas y eventos por hotel.
- Health checker y preflight TLM conocen el contrato TLM-I-A.
- QA manual queda diferida por instruccion del usuario.

## Cierre reporte operativo TLM

Estado actual: `BLOQUE_TLM_I_REPORTE_OPERATIVO_CERRADO_QA_DIFERIDA`.

- TLM-I-A queda cerrado tecnicamente con revision/auditoria documental.
- Verificaciones automaticas: lint PHP OK, preflight TLM `ERROR: 0`, health checker
  `ERROR: 0`, HTTP sin sesion `303`, SQL read-only sin cambios operativos.
- No hay nuevas acciones de tarea, cambios de habitacion, Caja, nomina ni `/api/sync`.
- QA manual queda diferida.

## Situacion historica

El historial contiene commits que implementan partes de Fase 3C, pero el estado documental vigente no debe tratarlos como cierre formal completo.

Las pruebas locales de 3C-B crearon CxP controladas desde compras recibidas (`id=1` para compra `#5` historica y `id=2` para compra `#2` vigente). Esos datos no deben borrarse ni corregirse automaticamente.

## Alcance de Fase 3B

Se permite:

- consultar CxP;
- listar cuentas por pagar;
- ver detalle de una cuenta por pagar;
- preparar checkers y documentacion;
- exponer navegacion basica.

No se permite:

- pagar proveedores;
- tocar Caja;
- generar CxP automaticamente desde compras;
- cambiar calculos financieros;
- modificar `/api/sync`;
- avanzar a Fase 3C.

## Estado tecnico auditado

- Codigo CxP read-only.
- Rutas GET solamente.
- Vistas sin formularios de pago.
- Health/preflight compatibles.
- Documentacion de metodologia vigente.
- Commit selectivo de cierre: `1fa1653 feat(phase-3b): add read-only accounts payable foundation`.
- `src/app/views/reservaciones/ver.php` queda fuera por no pertenecer al bloque.

## Decisiones clave

- CxP entra como fundacion, no como modulo operativo financiero.
- Caja queda intacta.
- Compras no genera CxP todavia.
- Las tablas duplicadas/legacy siguen congeladas.
- La siguiente accion despues del commit es revision manual, no nueva feature.

## Estado de riesgo

Riesgo naranja por tocar estructuras financieras de DB, mitigado por:

- backup previo;
- tablas vacias;
- implementacion read-only;
- sin integracion con Caja;
- sin pagos;
- verificaciones automaticas.

## Cierre tecnico post-commit

- CxP 3C-B tiene un unico POST manual con CSRF desde compra recibida elegible.
- CxP sigue sin integracion con Caja.
- Compras y proveedores no generan CxP automaticamente.
- Las consultas revisadas mantienen filtros por `hotel_id`.
- `POST /cuentas-por-pagar/generar-desde-compra/{id}` sin sesion redirige a login.
- `/api/sync` sigue fuera de alcance y bloqueado segun checker.

## Auditoria de seguridad

- Resultado: sin hallazgos bloqueantes.
- Perdida de datos: no detectada; no hay borrado ni migracion destructiva.
- Doble recepcion: protegida por contrato transaccional y validaciones de estado/detalles.
- Inventario: se mantiene fuente moderna `inventario_productos` + `movimientos_inventario`.
- CxP: en 3C-B permite generacion manual; no hay pagos, abonos ni movimientos de Caja.
- Riesgo residual: futuras escrituras CxP deben validar estrictamente `hotel_id` de proveedor/compra.
- 3C-C agrega validacion automatica para duplicados, compras/proveedores inexistentes, cruces de hotel, compras no recibidas, saldos/totales invalidos, fechas faltantes y referencias CxP en Caja.
- Auditoria corregida confirma: unico POST con CSRF, guardas de autenticacion/contexto/modulo, sin escrituras en Caja/pagos y sin cambios en `/api/sync`.
- Verificacion actual: Docker disponible; `php -l`, health, preflights, POST sin sesion, backup, doble generacion y conteos DB antes/despues ejecutados.

## Pendiente antes de avanzar

- No avanzar a pagos, Caja ni Fase 3D sin nuevo mensaje real o cola especifica.
- La siguiente accion recomendada es Fase 4A-A migracion base documental si se autoriza.

## Fase 9A-B-A conciliacion financiera read-only

Estado actual:
`CIERRE_9A_B_F_PANTALLA_CONCILIACION_FINANCIERA_COMPLETADO`.

- Se implementa GET `/operacion/conciliacion-financiera`.
- Se agrega lector read-only `ConciliacionFinanciera`.
- La vista muestra resumen CxC, CxP, Caja, auditoria y matriz de alertas con filtros
  GET.
- No hay POST propio, pagos, cobros, reversiones, ajustes, migraciones ni escrituras.
- El preflight de conciliacion ahora cubre 9A-A y 9A-B-A.
- Resultado inicial: lector directo `hallazgos=0`, `errores=0`; preflight
  `OK: 96`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 281`, `WARNING: 25`, `ERROR: 0`.
- QA manual con sesion completada por el usuario.
- Cierre 9A-B-F documentado; cualquier ampliacion de conciliacion requiere contrato
  independiente.

## Fase 9C-0 arqueo por corte y metodo read-only

Estado actual:
`CIERRE_9C_B_F_PANTALLA_ARQUEO_METODOS_PAGO_COMPLETADO`.

- Se crea contrato para diagnostico futuro de arqueo por corte y metodo de pago.
- No se implementa codigo, rutas, formularios, modelos, migraciones ni escrituras.
- Fuentes futuras: `movimientos_caja`, `cortes_caja`, `cajas`,
  `categorias_movimientos`, usuarios como contexto y hoteles para scope.
- Lectura local inicial: cortes `223`, abiertos `3`, movimientos Caja `1409`, metodos
  distintos `3`, sin metodo `0`, sin corte `0`.
- Se implementa 9C-A como preflight CLI/read-only:
  `src/tools/saas/preflight_arqueo_metodos_pago.php`.
- Resultado 9C-A: `OK: 34`, `WARNING: 2`, `ERROR: 0`.
- Warnings esperados: `4` cortes cerrados con totales guardados distintos a movimientos
  y `1` corte con `efectivo_esperado` distinto a formula guardada.
- Health general: `OK: 282`, `WARNING: 25`, `ERROR: 0`.
- Se crea contrato 9C-B-0 para futura pantalla GET/read-only de arqueo por corte y
  metodo.
- Se implementa 9C-B-A como pantalla GET/read-only:
  `GET /caja/arqueo-metodos`.
- Superficies nuevas: `ArqueoMetodosPago`, `CajaController::arqueoMetodosAction()`
  y `app/views/caja/arqueo_metodos.php`.
- La vista usa filtros GET, etiqueta `Solo lectura` y tokens `--brand-*`; no usa
  `--ms-*`.
- No hay POST, migraciones, escrituras, CSRF, `hotel_id` editable ni botones de
  cierre/recalculo/correccion.
- Preflight actualizado: `OK: 39`, `WARNING: 2`, `ERROR: 0`.
- Health general actualizado: `OK: 287`, `WARNING: 25`, `ERROR: 0`.
- QA manual con sesion completada por el usuario.
- Cierre 9C-B-F documentado; cualquier ampliacion de arqueo requiere contrato
  independiente.

## Fase 10A-0 tablero ejecutivo integral read-only

Estado actual:
`CONTRATO_10A_0_TABLERO_EJECUTIVO_READONLY_COMPLETADO`.

- Se crea contrato para un tablero ejecutivo integral, solo lectura.
- Ruta candidata futura: `GET /reportes/ejecutivo`.
- No se implementa codigo, rutas, formularios, modelos, migraciones ni escrituras.
- El tablero futuro debe consolidar KPIs de operacion hotelera, finanzas, Caja, CxC,
  CxP, inventario, tareas, personal y documentos.
- Se documenta que el reporte existente `GET /reportes/gerencial-diario` no debe
  reutilizarse como read-only puro mientras marque notificaciones como vistas.
- Quedan prohibidos pagos, cobros, reversiones, cierres/reaperturas de corte,
  recalculos, ajustes, ediciones de movimientos, exports/links sin contrato y
  `/api/sync`.
- Paso seguro ejecutado despues del contrato: 10A-A como preflight CLI/read-only
  antes de cualquier UI.

## Fase 10A-A preflight tablero ejecutivo integral read-only

Estado actual:
`PREFLIGHT_10A_A_TABLERO_EJECUTIVO_READONLY_COMPLETADO`.

- Se agrega `src/tools/saas/preflight_tablero_ejecutivo.php`.
- Se extiende `src/tools/saas/health_check_fase_1a.php`.
- No se registra `/reportes/ejecutivo` ni se agrega pantalla.
- El preflight revisa fuentes de operacion, cartera, Caja, cortes, compras,
  inventario, tareas, trabajadores, documentos y auditoria.
- Detecta como warning controlado que `GET /reportes/gerencial-diario` archiva
  notificaciones y por tanto no debe reutilizarse como fuente read-only pura.
- Resultado del preflight: `OK: 79`, `WARNING: 5`, `ERROR: 0`.
- Resultado del health general: `OK: 290`, `WARNING: 26`, `ERROR: 0`.
- Siguiente paso seguro: 10A-B-0 como contrato de pantalla ejecutiva GET/read-only.

## Fase 10A-B-0 contrato pantalla tablero ejecutivo read-only

Estado actual:
`CONTRATO_10A_B_0_PANTALLA_TABLERO_EJECUTIVO_READONLY_COMPLETADO`.

- Se crea contrato de pantalla ejecutiva antes de tocar codigo.
- Ruta futura candidata unica: `GET /reportes/ejecutivo`.
- No se implementa ruta, controlador, modelo, vista, navegacion, formularios,
  migraciones ni escrituras.
- La pantalla futura debe mostrar KPIs de operacion, finanzas, Caja, inventario,
  tareas, personal, documentos y auditoria, siempre en solo lectura.
- Quedan prohibidos POST, acciones operativas, archivado de notificaciones por lectura,
  exports/links sin contrato separado, permisos nuevos, PWA/offline y `/api/sync`.
- Siguiente paso posible: 10A-B-A solo con autorizacion explicita para tocar rutas,
  controlador/modelo read-only y vista.

## Fase 10A-B-A pantalla tablero ejecutivo read-only

Estado actual:
`PANTALLA_10A_B_A_TABLERO_EJECUTIVO_READONLY_IMPLEMENTADA`.

- Se implementa `GET /reportes/ejecutivo`.
- Se agrega `TableroEjecutivo` como lector read-only con transaccion y rollback.
- Se agrega vista `reportes/ejecutivo.php` con filtros GET y etiqueta `Solo lectura`.
- Se enlaza desde el indice de reportes.
- No se agrega POST, migracion, export, link publico ni accion operativa.
- Preflight: `OK: 86`, `WARNING: 5`, `ERROR: 0`.
- Health general: `OK: 294`, `WARNING: 26`, `ERROR: 0`.
- Sin sesion redirige a login con `303`.
- QA manual con sesion validada por el usuario.

## Fase 10A-B-F cierre tablero ejecutivo read-only

Estado actual:
`CIERRE_10A_B_TABLERO_EJECUTIVO_READONLY_QA_MANUAL_VALIDADA`.

- Se documenta el cierre de 10A-B tras QA manual aprobada.
- No se modifica codigo, rutas, controladores, modelos, vistas, formularios,
  migraciones ni datos.
- `GET /reportes/ejecutivo` queda como pantalla read-only validada.
- Siguiente bloque recomendado: separar lectura de reporte gerencial diario y archivado
  automatico de notificaciones.

## Fase 10B-0 contrato reporte gerencial y notificaciones

Estado actual:
`CONTRATO_10B_0_REPORTE_GERENCIAL_NOTIFICACIONES_COMPLETADO`.

- Se crea contrato documental para desacoplar lectura directa de reporte gerencial y
  archivado automatico de notificaciones.
- No se modifica codigo, rutas, controladores, modelos, vistas, formularios,
  migraciones ni datos.
- Se documenta que `GET /reportes/gerencial-diario` y su PDF llaman actualmente
  `archivarNotificacionReporteGerencialVisto`.
- La futura implementacion debera hacer que abrir el reporte directo no cambie
  `notificaciones`.
- El archivado, si aplica, debe quedar en el flujo controlado de notificaciones y
  scoped por hotel.
- Accion ejecutada despues: 10B-A con autorizacion explicita.

## Fase 10B-A separacion reporte gerencial y notificaciones

Estado actual:
`SEPARACION_10B_A_REPORTE_GERENCIAL_NOTIFICACIONES_QA_MANUAL_VALIDADA`.

- Se retiro el archivado automatico de notificaciones desde el reporte gerencial HTML
  y PDF.
- `ReportesController` ya no conserva
  `archivarNotificacionReporteGerencialVisto`.
- El flujo controlado de notificaciones sigue en `/notificaciones/{id}/abrir`.
- No se tocaron rutas, modelos de negocio, vistas, formularios, migraciones, datos,
  permisos/auth, PWA/offline ni `/api/sync`.
- Preflight tablero ejecutivo: `OK: 91`, `WARNING: 3`, `ERROR: 0`.
- Health general: `OK: 298`, `WARNING: 25`, `ERROR: 0`.
- HTTP sin sesion en HTML/PDF gerencial: `303`.
- QA manual con sesion de hotel validada por el usuario.

## Fase 10B-F cierre reporte gerencial y notificaciones

Estado actual:
`CIERRE_10B_REPORTE_GERENCIAL_NOTIFICACIONES_QA_MANUAL_VALIDADA`.

- Se documenta el cierre del bloque 10B tras QA manual aprobada.
- No se modifica codigo, rutas, controladores, modelos, vistas, formularios,
  migraciones ni datos.
- `GET /reportes/gerencial-diario` y su PDF quedan como lecturas directas sin
  archivado automatico de notificaciones.
- El archivado queda reservado al flujo controlado de notificaciones.
- Siguiente paso recomendado: abrir contrato independiente para el siguiente frente.

## Fase 11A-0 contrato perfil operativo de huesped read-only

Estado actual:
`CONTRATO_11A_0_PERFIL_HUESPED_READONLY_COMPLETADO`.

- Se crea contrato documental para enriquecer la ficha de huesped/cliente sin
  escrituras.
- No se modifica codigo, rutas, controladores, modelos, vistas, formularios,
  migraciones ni datos.
- Define una futura lectura en `GET /huespedes/{id}` con reservaciones, vehiculos,
  CxC, documentos, recurrencia y alertas visuales.
- Queda prohibido alterar formularios existentes de huespedes.
- Siguiente paso posible: 11A-A solo con autorizacion explicita.

## Nuevo bloque Fase 4A Centro Documental

Estado: `CIERRE_TECNICO_4A_COMPLETADO`.

Objetivo: crear una fundacion segura para adjuntar, consultar y relacionar documentos
con proveedor, compra, CxP, huesped, reservacion y trabajador futuro, sin Caja, pagos,
abonos, Fase 3D ni `/api/sync`.

Resultado 4A-0:

- Git inicial limpio en HEAD `35abdc7`.
- Diagnostico de uploads publicos: `public_html/uploads` sirve assets publicos.
- Diagnostico de storage privado: `storage/reportes` y `ReporteLinkController` son el
  patron de descarga segura.
- No existen tablas generales `documentos`, `documento_entidades`,
  `documento_tipos`.
- Propuesta de tablas aditivas documentada.
- Siguiente cola recomendada: `[COLA_4A_A_MIGRACION_BASE_DOCUMENTOS]`.
- No se implementaron uploads, POST, descargas ni migraciones en 4A-0.

Resultado 4A-A:

- Backup previo confirmado antes de aplicar DB:
  `src/storage/backups/phase4a_20260615_182352_before_document_center_medisoft_hoteles_import.sql`.
- SHA256: `698A304F69B312EF06EABA787C83969629F2B14BCA096906CC08CADDC7D898F0`.
- Tamano: `1535817` bytes.
- Migracion creada: `migrations/20260615_004_fase_4a_centro_documental_base.sql`.
- Migracion aplicada localmente y registrada en `migrations` como batch `17`.
- Tablas creadas y vacias: `documento_tipos=0`, `documentos=0`, `documento_entidades=0`.
- No hay uploads, POST, descargas, borrados ni exposicion publica de documentos.
- No se tocaron Caja, pagos, abonos, CxP operativa ni `/api/sync`.
- Siguiente cola recomendada tras 4A-A: `[COLA_4A_B_DOCUMENTOS_READ_ONLY]`.

Resultado 4A-B:

- Modelo read-only creado: `src/app/models/Documento.php`.
- Controlador read-only creado: `src/app/controllers/DocumentoController.php`.
- Vistas read-only creadas: `src/app/views/documentos/index.php` y
  `src/app/views/documentos/ver.php`.
- Rutas GET creadas:
  - `/documentos`;
  - `/documentos/{id}`;
  - `/documentos/entidad/{tipo}/{id}`.
- Navegacion agregada en sidebar solo cuando hay modulos relacionados activos.
- No se crean uploads, POST, descargas, edicion ni borrado.
- No se expone `storage_path`, `nombre_archivo` ni rutas internas.
- Tablas documentales siguen vacias en entorno local.
- No se tocaron Caja, pagos, abonos, CxP operativa ni `/api/sync`.

Resultado 4A-C:

- Backup previo antes de escritura:
  `src/storage/backups/phase4a_c_20260615_190912_before_document_upload_medisoft_hoteles_import.sql`.
- SHA256: `DF150F705824973621B9A1276980DC73ECB7AE5261B67A5D71E541FE13797446`.
- Tamano: `1541523` bytes.
- Rutas nuevas:
  - `GET /documentos/subir`;
  - `POST /documentos/subir`.
- Formulario de carga con `multipart/form-data` y CSRF.
- Validacion de archivo: `is_uploaded_file`, tamano maximo, extension permitida, extensiones
  peligrosas bloqueadas y MIME real con `finfo`.
- Tipos permitidos iniciales: PDF, JPG/JPEG, PNG y WEBP.
- Storage privado bajo `STORAGE_PATH/documentos/hotel_{hotel_id}/YYYY/MM`.
- No se expone `storage_path` ni `nombre_archivo` en vistas.
- Se registra metadata en `documentos`, vinculo opcional en `documento_entidades` y
  auditoria `documentos.cargado`.
- Prueba controlada: documento `#1` creado en hotel `1`, vinculado a proveedor `#8`.
- Conteos post-prueba: `documentos=1`, `documento_entidades=1`,
  `documento_tipos=0`, `cuentas_por_pagar_movimientos=0`, `movimientos_caja=1403`.
- Archivo `.html` invalido fue rechazado sin crear registros.
- No hay descarga, edicion, borrado, pagos, abonos, Caja ni `/api/sync`.
- Hotfix post-QA 4A-C:
  - tipos documentales globales creados por migracion idempotente;
  - carga general simplificada sin IDs manuales de entidad;
  - CSS relativo del layout corregido con `asset()`;
  - prueba controlada creo `documentos.id=2` con tipo Contrato y sin vinculo inicial.
- QA manual post-hotfix 4A-C: el usuario reporto que la carga documental ya funciona.
- Revision tecnica 4A: se reforzo `/documentos/entidad/{tipo}/{id}` para validar que la entidad exista y pertenezca al hotel actual antes de mostrar listado contextual o enlace de carga.
- Auditoria seguridad 4A: no hay rutas de descarga, edicion ni borrado documental; no se exponen rutas internas; no hay referencias documentales en PWA/offline; sin Caja, pagos, abonos ni `/api/sync`.
- Cierre tecnico 4A: bloque 4A-0..4A-C documentado y cerrado; no autoriza descargas, edicion, borrado, pagos, Caja, Fase 3D ni `/api/sync`.
- Siguiente paso recomendado: nuevo mensaje real para autorizar una fase posterior, por ejemplo descarga segura autenticada.

## Nuevo bloque Fase 4B Descarga segura documental

Estado: `DESCARGA_SEGURA_4B_VALIDADA_MANUALMENTE`.

Objetivo: definir descarga autenticada de documentos privados ya cargados en Centro
Documental, sin exponer `storage_path`, sin links publicos, sin Caja, pagos, abonos ni
`/api/sync`.

Resultado 4B-0:

- Documento creado: `docs/fase_4B_0_contrato_descarga_segura_documentos.md`.
- Ruta futura propuesta: `GET /documentos/{id}/descargar`.
- Implementacion futura propuesta: `DocumentoController::descargarAction()` con
  validacion `id + hotel_id`, documento `activo`, `realpath` bajo
  `STORAGE_PATH/documentos` y headers privados.
- Se tomo como referencia tecnica `ReporteLinkController`, pero sin token publico y sin
  limitar a PDF.
- No se implementaron rutas, modelos, vistas, migraciones, lectura de archivos ni
  escrituras DB.

Resultado 4B-A:

- Ruta implementada: `GET /documentos/{id}/descargar`.
- Controlador/modelo actualizados para buscar por `id + hotel_id`, exigir `estado=activo`
  y resolver archivo por `realpath` bajo `STORAGE_PATH/documentos`.
- Vistas de listado y detalle muestran accion `Descargar` sin exponer `storage_path` ni
  `nombre_archivo`.
- HTTP sin sesion redirige a login.
- Documento `#1` de Los Cedros descarga `200`; documento `#3` de otro hotel redirige.
- En 4B-A no hay POST nuevo, links publicos, edicion, borrado, Caja, pagos, abonos ni `/api/sync`;
  la edicion posterior queda limitada a metadata en 4B-C-A.
- QA manual 4B-A reportada por el usuario como completada.

Resultado 4B-B:

- Se agrega auditoria tolerante de descargas documentales con `AuditService::record()`.
- Eventos: `documentos.descargado` y `documentos.descarga_bloqueada`.
- No se registra `storage_path` ni `nombre_archivo`.
- Verificacion local genero una auditoria exitosa para documento `#1` y una bloqueada
  para documento `#3` de otro hotel.
- No hay nuevas rutas, POST, edicion, borrado, links publicos, Caja, pagos, abonos ni
  `/api/sync`.
- QA manual 4B-B reportada por el usuario como funcional.

Resultado 4B-C-A:

- Se implementa edicion controlada de metadata documental.
- Rutas: `GET /documentos/{id}/editar` y `POST /documentos/{id}/actualizar`.
- Metadata editable limitada a `titulo`, `descripcion`, `etiquetas` y
  `documento_tipo_id`.
- Auditoria: `documentos.metadata_actualizada` solo cuando hay cambios reales.
- Verificacion: POST no-op e invalido no modifican datos; prueba transaccional confirma
  update real + auditoria + rollback sin persistencia.
- Quedan prohibidos reemplazo de archivo, cambio de storage, borrado, links publicos,
  Caja, pagos, abonos y `/api/sync`.
- QA manual 4B-C-A reportada por el usuario como correcta.
- Estado formal: `METADATA_DOCUMENTAL_4B_C_A_VALIDADA_MANUALMENTE`.

Cierre tecnico 4B:

- Estado formal: `CIERRE_TECNICO_4B_COMPLETADO`.
- Revision tecnica post-QA ejecutada sin hallazgos bloqueantes.
- Auditoria seguridad post-QA confirma rutas protegidas, CSRF en POST, filtros
  `hotel_id`, storage privado, ausencia de rutas internas en vistas y ausencia de
  borrado, reemplazo, links publicos, Caja, pagos, abonos y cambios en `/api/sync`.
- Verificaciones: `php -l`, health, preflights, HTTP sin sesion, SQL read-only y
  `git diff --check`.
- Siguiente paso recomendado: nuevo bloque autorizado; no avanzar automaticamente a
  borrado, links publicos, reemplazo de archivos, pagos, Caja, Fase 3D ni NP-A.

## Nuevo bloque Fase 4C Documentos por entidad

Estado: `CIERRE_TECNICO_4C_COMPLETADO_QA_MANUAL_VALIDADA`.

Objetivo: definir como integrar documentos en fichas operativas de proveedor, compra,
cuenta por pagar, huesped y reservacion usando la relacion existente
`documento_entidades`, sin implementar todavia nuevas secciones ni escrituras.

Resultado 4C-0:

- Documento creado: `docs/fase_4C_0_contrato_documentos_entidad.md`.
- Diagnostico confirma infraestructura existente:
  - `Documento::documentosPorEntidad()`;
  - `DocumentoController::entidadAction()`;
  - `GET /documentos/entidad/{tipo}/{id}`;
  - carga contextual ya validada por entidad/hotel.
- No se tocaron controladores, modelos, rutas, vistas, DB ni migraciones.
- No hay nuevas escrituras, Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.
- Siguiente paso recomendado: `COLA_4C_A_DOCUMENTOS_POR_ENTIDAD_READ_ONLY`.

Resultado 4C-A:

- Estado tecnico: `DOCUMENTOS_ENTIDAD_CONTEXTUAL_4C_VALIDADA_MANUALMENTE`.
- Documento creado: `docs/fase_4C_A_documentos_entidad_readonly.md`.
- Partial creado: `src/app/views/partials/documentos_entidad.php`.
- Fichas con seccion documental contextual:
  - `proveedores/ver.php`;
  - `compras/ver.php`;
  - `cuentas_por_pagar/ver.php`;
  - `huespedes/ver.php`;
  - `reservaciones/ver.php`.
- Controladores consultan documentos por entidad con filtro `hotel_id`.
- La vista no expone `storage_path` ni `nombre_archivo`.
- Hotfix UX: se agrega `Vincular documento` hacia la carga contextual existente
  `documentos/subir?entidad_tipo=...&entidad_id=...`.
- No hay nuevos POST, edicion desde ficha, borrado, Caja, pagos, abonos, Fase 3D,
  NP-A ni cambios en `/api/sync`.
- QA manual post-hotfix reportada por el usuario como correcta: la accion
  `Vincular documento` aparece, abre la carga contextual existente y el flujo funciona
  correctamente.
- Cierre tecnico documental: registrado en `docs/cierre-tecnico-bloque-cola.md`.
- Siguiente paso recomendado: abrir un nuevo bloque solo con autorizacion explicita. No
  avanzar automaticamente a borrado, reemplazo, links publicos, Caja, pagos, abonos,
  Fase 3D, NP-A ni `/api/sync`.

## Nuevo bloque Fase 4D Archivado documental

Estado: `CIERRE_TECNICO_4D_COMPLETADO_QA_DIFERIDA`.

Objetivo: preparar una fase segura para archivar, restaurar y eventualmente dar baja
logica a documentos sin borrar archivos fisicos ni registros.

Resultado 4D-0:

- Documento creado: `docs/fase_4D_0_contrato_archivado_documental.md`.
- Diagnostico confirma que `documentos.estado` ya soporta `activo`, `archivado` y
  `eliminado`.
- Se define 4D-A como archivado/restauracion reversible (`activo <-> archivado`) con
  POST + CSRF, auditoria y filtro `hotel_id`.
- Se deja 4D-B como baja logica futura hacia `eliminado`, con confirmacion fuerte y
  sin borrado fisico.
- No hay codigo, rutas nuevas, POST nuevo, DB, migraciones, Caja, pagos, abonos,
  Fase 3D, NP-A ni cambios en `/api/sync`.
- Siguiente paso recomendado: `COLA_4D_A_ARCHIVADO_DOCUMENTAL_CONTROLADO`, solo si se
  autoriza implementacion.

Resultado 4D-A:

- Estado tecnico: `FASE_4D_A_VALIDADA_MANUALMENTE`.
- Documento creado: `docs/fase_4D_A_archivado_documental_controlado.md`.
- Rutas POST agregadas: `/documentos/{id}/archivar` y `/documentos/{id}/restaurar`.
- Modelo central: `Documento::actualizarEstado()` valida `id + hotel_id` y solo permite
  `activo <-> archivado`.
- Vista de detalle muestra `Archivar` o `Restaurar` segun estado, con CSRF.
- Auditoria: `documentos.estado_actualizado`.
- No hay baja logica `eliminado`, borrado fisico, `DELETE`, Caja, pagos, abonos,
  Fase 3D, NP-A ni cambios en `/api/sync`.
- Verificacion automatica: `php -l`, health, preflights, HTTP sin sesion, SQL read-only,
  prueba transaccional con rollback y `git diff --check`.
- Revision tecnica/auditoria: sin hallazgos bloqueantes; el bloque queda cerrado
  tecnicamente.
- QA manual: el usuario reporto que todas las pruebas QA responden perfectamente.
- Pendiente real: no avanzar a baja logica `eliminado`, Caja, pagos, abonos, Fase 3D,
  NP-A ni `/api/sync` sin nuevo bloque explicito.

Resultado 4D-B-0:

- Estado tecnico: `CONTRATO_4D_B_BAJA_LOGICA_DOCUMENTAL_COMPLETADO`.
- Documento creado: `docs/fase_4D_B_0_contrato_baja_logica_documental.md`.
- Se define baja logica futura hacia `eliminado` sin borrar archivo fisico, registros
  ni relaciones.
- Ruta futura propuesta: `POST /documentos/{id}/eliminar`.
- Transiciones futuras propuestas: `activo -> eliminado` y `archivado -> eliminado`.
- Se mantiene prohibida la restauracion desde `eliminado` hasta contrato separado.
- No se implementaron rutas, controladores, modelos, vistas, DB ni migraciones.
- No hay borrado fisico, `DELETE`, Caja, pagos, abonos, Fase 3D, NP-A ni cambios en
  `/api/sync`.
- Siguiente paso recomendado: implementar 4D-B-A solo con nueva autorizacion explicita.

Resultado 4D-B-A:

- Estado tecnico: `BAJA_LOGICA_DOCUMENTAL_4D_B_A_COMPLETADA_QA_DIFERIDA`.
- Ruta POST implementada: `/documentos/{id}/eliminar`.
- Controlador: `DocumentoController::eliminarAction()`.
- Modelo central: `Documento::actualizarEstado()` permite `activo/archivado -> eliminado`
  y bloquea cambios desde `eliminado`.
- Vista de detalle muestra `Baja logica` solo en documentos `activo` o `archivado`,
  con CSRF y confirmacion fuerte.
- No hay restauracion desde `eliminado`, borrado fisico, `DELETE`, Caja, pagos,
  abonos, Fase 3D, NP-A ni cambios en `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

Cierre tecnico 4D:

- Estado final: `CIERRE_TECNICO_4D_COMPLETADO_QA_DIFERIDA`.
- Bloque documental 4D culminado tecnicamente.
- QA manual pendiente solo para 4D-B-A baja logica.
- Siguiente bloque recomendado: Personal base, iniciando por contrato/diagnostico o
  migracion base segun estado real del repo.

## Nuevo bloque Personal y Nomina (Fase NP)

Objetivo: modulo INDEPENDIENTE de trabajadores con ledger laboral, saldos por persona,
asistencia y comisiones, multi-hotel, SIN integracion con Caja ni salida real de dinero.

Estado actual:

- Fase NP-0 completada: contrato, diagnostico read-only y diseno aditivo de 6 tablas.
- Fase NP-A completada tecnicamente: migracion aditiva de Personal base aplicada y
  tablas creadas vacias.
- HEAD al iniciar NP-0: `5dfe665`; Git limpio.
- Hoy "trabajador" = `usuarios` + `hotel_usuarios`; sin rol laboral, deuda ni saldo por persona.
- Caja revisada en solo lectura; NO existe categoria "Nomina"; movimientos Caja-nomina: 0.
- Tablas `trabajador*` existen desde NP-A y estan vacias.
- No se implemento funcionalidad visual ni se insertaron trabajadores/movimientos.

Riesgo: naranja (modulo financiero-laboral nuevo y concepto sensible), mitigado por
migraciones aditivas/reversibles, sin Caja, sin tocar `usuarios` destructivamente,
filtro `hotel_id` y validaciones fuertes.

Subfases planificadas: NP-A (ficha basica), NP-B (pagos/anticipos/prestamos sin Caja),
NP-C (saldos y reportes), NP-D (asistencia y comisiones), NP-E (validaciones/health),
NP-F (cierre). Contrato: `docs/fase_NP_0_contrato_diagnostico.md`.

Resultado NP-A:

- Estado tecnico: `MIGRACION_NP_A_PERSONAL_BASE_COMPLETADA_QA_DIFERIDA`.
- Backup valido: `src/storage/backups/phase_np_a_20260616_021311_before_personal_base_medisoft_hoteles_import.sql`.
- SHA256: `0F9E64B040437A73D559534E5753133F4C3508C29F5B9EA0278B351097666246`.
- Migracion: `migrations/20260616_001_fase_np_a_personal_base.sql`.
- Conteos finales de las seis tablas: 0.
- Caja/Nomina: movimientos 0, categorias 0.
- UI read-first implementada: `GET /trabajadores` y `GET /trabajadores/{id}` con
  guardas administrativas, estados vacios y sin POST.
- Sin pagos reales, anticipos, prestamos, asistencia operativa ni Caja.
- Siguiente paso recomendado: contrato NP-B para alta/edicion segura de trabajador o
  bloque Tareas/Limpieza/Mantenimiento base, segun prioridad.

Resultado NP-B-0:

- Estado tecnico: `CONTRATO_NP_B_CRUD_TRABAJADORES_COMPLETADO`.
- Documento: `docs/fase_NP_B_0_contrato_crud_trabajadores.md`.
- Proxima implementacion permitida: alta/edicion/baja logica/reactivar trabajador con
  POST + CSRF, auditoria y filtro `hotel_id`.
- Sigue fuera de alcance: pagos, anticipos, prestamos, asistencia operativa, documentos
  laborales, Caja, categoria Nomina y `/api/sync`.

Resultado NP-B-A:

- Estado tecnico: `CRUD_TRABAJADORES_NP_B_A_COMPLETADO_QA_DIFERIDA`.
- Rutas CRUD basicas de trabajador agregadas bajo `/trabajadores`.
- Validaciones centrales en `Trabajador`: hotel actual, usuario opcional del mismo hotel,
  nombre obligatorio, email valido y salario no negativo.
- POST con CSRF y auditoria para crear, actualizar, baja logica y reactivar.
- Sin pagos, anticipos, prestamos, asistencia operativa, documentos laborales, Caja ni
  `/api/sync`.

## Nuevo bloque Tareas, Limpieza y Mantenimiento (Fase TLM)

Objetivo: preparar una capa operativa de tareas para limpieza, mantenimiento ligero y
tareas generales, sin sustituir el flujo actual de habitaciones ni el historial de
mantenimiento.

Resultado TLM-0:

- Estado tecnico: `CONTRATO_TLM_0_COMPLETADO`.
- Documento: `docs/fase_TLM_0_contrato_diagnostico.md`.
- Diagnostico read-only ejecutado.
- No se crearon migraciones, rutas, controladores, modelos ni vistas.
- No se escribio en DB.
- Fuente actual de disponibilidad: `habitaciones.estado`.
- Fuente actual de mantenimiento: `mantenimientos_habitaciones`.
- Conteos revisados:
  - `mantenimientos_habitaciones`: 10 registros.
  - registros de mantenimiento sin `hotel_id`: 0.
  - habitaciones en `limpieza`: 12.
  - habitaciones en `mantenimiento`: 2.
  - `trabajadores`: 0.
- Riesgo: naranja, porque limpieza/mantenimiento afecta disponibilidad y reservaciones.
- Siguiente paso recomendado: TLM-A migracion base aditiva de tareas, con backup previo.

Resultado TLM-A:

- Estado tecnico: `MIGRACION_TLM_A_TAREAS_BASE_COMPLETADA_QA_DIFERIDA`.
- Backup valido:
  `src/storage/backups/phase_tlm_a_20260616_030648_before_tasks_base_medisoft_hoteles_import.sql`.
- SHA256: `C76243D8AF98A2D9AA9D94FB5B1BB4D29369A861594141C7EC397A13AF3CF242`.
- Migracion: `migrations/20260616_002_fase_tlm_a_tareas_base.sql`.
- Tablas creadas vacias: `tareas_operativas`, `tarea_eventos`.
- Migracion registrada en batch local `20`.
- No se crearon tareas ni eventos.
- No se crearon rutas, UI ni POST.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.
- Siguiente paso recomendado: TLM-B read-only de tareas operativas.

Resultado TLM-B:

- Estado tecnico: `TAREAS_READ_ONLY_TLM_B_COMPLETADO_QA_DIFERIDA`.
- Modelo/controlador/vistas read-only creados para tareas operativas.
- Rutas GET creadas: `/tareas` y `/tareas/{id}`.
- Sidebar expone `Tareas` bajo Operaciones.
- Health valida rutas, modelo, vistas y ausencia de POST.
- HTTP sin sesion redirige a login.
- No hay creacion/asignacion/cierre/cancelacion de tareas.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.
- Siguiente paso recomendado: TLM-C creacion manual controlada, si se autoriza.

Resultado TLM-C:

- Estado tecnico: `CREACION_MANUAL_TLM_C_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_C_creacion_manual_tareas.md`.
- Rutas agregadas:
  - `GET /tareas/crear`;
  - `POST /tareas`.
- El listado `/tareas` muestra accion `Nueva tarea` para usuarios con
  `habitaciones.mantenimiento`.
- El alta manual crea solo registros en `tareas_operativas` y `tarea_eventos`.
- La tarea nace en estado `pendiente`, con `origen = manual`.
- Se valida `hotel_id` del contexto y habitacion opcional del mismo hotel.
- POST protegido con CSRF, permiso `habitaciones.mantenimiento` y auditoria con
  `AuditService`.
- No hay asignacion, inicio, cierre, cancelacion ni cambio de `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente paso recomendado: TLM-D asignacion opcional a trabajador activo del mismo
  hotel.

Resultado TLM-D:

- Estado tecnico: `ASIGNACION_TLM_D_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_D_asignacion_trabajador.md`.
- Ruta agregada: `POST /tareas/{id}/asignar`.
- El detalle de tarea muestra seccion de asignacion solo para tareas `pendiente` o
  `asignada` y usuario con permiso.
- La asignacion valida trabajador activo del mismo hotel.
- La tarea queda en estado `asignada` y registra evento `asignada`.
- Se audita la asignacion con `AuditService`.
- No crea trabajadores, asistencia, pagos, abonos ni movimientos de Caja.
- No cambia `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, nomina ni `/api/sync`.
- QA manual queda diferida; la base local tiene 0 trabajadores activos al momento de
  implementar esta subfase.
- Siguiente paso recomendado: TLM-E inicio/cierre/cancelacion manual de tareas, si se
  autoriza.

Resultado TLM-E:

- Estado tecnico: `ESTADOS_TLM_E_COMPLETADOS_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_E_estados_tarea.md`.
- Rutas agregadas:
  - `POST /tareas/{id}/iniciar`;
  - `POST /tareas/{id}/completar`;
  - `POST /tareas/{id}/cancelar`.
- El detalle de tarea muestra acciones de estado solo para tareas activas.
- Transiciones centralizadas en `TareaOperativa::cambiarEstadoManualParaHotel()`.
- Eventos registrados: `iniciada`, `completada`, `cancelada`.
- Auditoria con `AuditService`.
- No cambia `habitaciones.estado`.
- No modifica `mantenimientos_habitaciones`.
- No crea asistencia, pagos, abonos, nomina ni movimientos de Caja.
- No se toco `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente paso recomendado: TLM-F contexto visual de tareas en habitacion/trabajador.

Resultado TLM-F:

- Estado tecnico: `CONTEXTUAL_TLM_F_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_F_contextual_tareas.md`.
- Se agrega lectura contextual de tareas por `habitacion` y `trabajador` en
  `TareaOperativa::listarPorEntidadHotel()`.
- La ficha de habitacion muestra tareas operativas vinculadas con estado vacio claro.
- La ficha de trabajador muestra tareas asignadas con estado vacio claro.
- No se agregan rutas nuevas ni POST nuevos.
- No se crean, asignan, inician, completan ni cancelan tareas desde las fichas
  contextuales.
- No cambia `habitaciones.estado`.
- No modifica `mantenimientos_habitaciones`.
- No crea asistencia, pagos, abonos, nomina ni movimientos de Caja.
- No se toco `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente paso recomendado: TLM-G health/preflights de consistencia de tareas.

Resultado TLM-G:

- Estado tecnico: `PREFLIGHT_TLM_G_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_G_preflight_consistencia.md`.
- Nuevo preflight read-only: `src/tools/saas/preflight_tareas_operativas.php`.
- Health checker actualizado para validar consistencia TLM-G.
- Se detectan tareas/eventos sin hotel, entidades cruzadas de hotel, estados invalidos,
  fechas incoherentes y movimientos de Caja con referencia a tarea operativa.
- No se agregan rutas, vistas ni acciones de usuario.
- No se escriben datos.
- No cambia `habitaciones.estado`.
- No modifica `mantenimientos_habitaciones`.
- No crea asistencia, pagos, abonos, nomina ni movimientos de Caja.
- No se toco `/api/sync`.
- Siguiente paso recomendado: TLM-H revision tecnica, auditoria y cierre del bloque.

Resultado TLM-H:

- Estado tecnico: `BLOQUE_TLM_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_H_cierre_bloque.md`.
- Se revisaron rutas, controladores, modelo, vistas, partial contextual, permisos,
  CSRF, auditoria, filtros `hotel_id`, ausencia de Caja, ausencia de pagos/abonos/nomina
  y ausencia de cambios en `/api/sync`.
- No se agregan funcionalidades nuevas.
- QA manual sigue diferida por instruccion del usuario.
- Siguiente paso recomendado: esperar QA manual TLM o pasar a un nuevo bloque autorizado
  independiente.

Resultado NP-C-0:

- Estado tecnico: `CONTRATO_NP_C_LEDGER_LABORAL_COMPLETADO`.
- Documento: `docs/fase_NP_C_0_contrato_ledger_laboral.md`.
- Se define el contrato de ledger laboral informativo sobre tablas `trabajador_*`.
- No se agregan rutas, vistas, POST, migraciones ni escrituras.
- Caja, pagos reales, abonos, categoria Nomina y `/api/sync` quedan explicitamente fuera
  de alcance.
- Siguiente paso recomendado: NP-C-A read-only de ledger laboral, sin crear datos.

Resultado NP-C-A:

- Estado tecnico: `LEDGER_LABORAL_NP_C_A_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_A_ledger_read_only.md`.
- La ficha de trabajador muestra ledger laboral read-only con saldo informativo,
  conceptos, anticipos y prestamos.
- No se agregan rutas ni POST.
- No se crean datos, pagos reales, abonos, movimientos de Caja ni categoria Nomina.
- Health checker conoce NP-C-A.
- Siguiente paso recomendado: preflight de consistencia NP-C antes de cualquier escritura.

Resultado NP-C-E:

- Estado tecnico: `PREFLIGHT_LEDGER_NP_C_E_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_E_preflight_ledger.md`.
- Nuevo preflight read-only: `src/tools/saas/preflight_personal_ledger.php`.
- Health checker detecta el preflight de ledger laboral.
- No agrega UI, rutas, POST, migraciones ni escrituras.
- Siguiente paso recomendado: revision tecnica/auditoria/cierre de NP-C read-only o
  contrato futuro para escrituras laborales sin Caja.

Resultado NP-C-F:

- Estado tecnico: `BLOQUE_NP_C_READ_ONLY_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_F_cierre_ledger_read_only.md`.
- Se cierra el bloque read-only de ledger laboral: contrato, vista y preflight.
- No hay pagos reales, abonos, Caja, categoria Nomina ni cambios en `/api/sync`.
- Siguiente paso recomendado: contrato NP-C-B-0 si se autoriza primera escritura laboral
  sin Caja, o QA manual diferida de NP-C.

Resultado NP-C-B-0:

- Estado tecnico: `CONTRATO_NP_C_B_CONCEPTOS_LABORALES_COMPLETADO`.
- Documento: `docs/fase_NP_C_B_0_contrato_conceptos_laborales.md`.
- Se define contrato futuro para registrar comision, bono, descuento y ajuste en
  `trabajador_pagos`.
- No se implementan rutas, vistas, POST ni escrituras.
- Caja, categoria Nomina, pagos reales y `/api/sync` siguen fuera de alcance.

Resultado NP-C-B-A:

- Estado tecnico: `CONCEPTOS_LABORALES_NP_C_B_A_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_B_A_conceptos_laborales.md`.
- Se habilita registro manual de concepto laboral en `trabajador_pagos` desde la ficha de
  trabajador activo.
- La escritura esta centralizada en el modelo, usa transaccion, valida `hotel_id`,
  trabajador activo, tipo, efecto, monto y fecha.
- La UI usa CSRF y deja claro que no es pago real ni movimiento de Caja.
- Health checker y preflight de ledger laboral validan NP-C-B-A.
- No se crean pagos reales, abonos, anticipos, prestamos, asistencia, categoria Nomina ni
  movimientos de Caja.
- No se toco `/api/sync`.

Resultado NP-C-B-F:

- Estado tecnico: `BLOQUE_NP_C_B_CONCEPTOS_LABORALES_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_B_F_cierre_conceptos_laborales.md`.
- Se cierra tecnicamente el bloque de conceptos laborales manuales.
- Commits incluidos: contrato, implementacion y revision tecnica.
- Checkers sin errores: preflight de ledger laboral y health general.
- QA manual queda diferida hasta que exista un trabajador activo autorizado.
- Siguiente paso recomendado: abrir contrato nuevo antes de anticipos, prestamos o
  asistencia.

Resultado NP-C-C-0:

- Estado tecnico: `CONTRATO_NP_C_C_ANTICIPOS_PRESTAMOS_COMPLETADO`.
- Documento: `docs/fase_NP_C_C_0_contrato_anticipos_prestamos.md`.
- Se define contrato para futura captura manual de anticipos y prestamos laborales.
- No se implementan rutas, vistas, POST ni escrituras.
- Caja, pagos reales, abonos, Nomina y `/api/sync` siguen fuera de alcance.

Resultado NP-C-C-A:

- Estado tecnico: `ANTICIPOS_PRESTAMOS_NP_C_C_A_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_C_A_anticipos_prestamos.md`.
- Se agregan formularios POST controlados para anticipos y prestamos en la ficha de
  trabajador.
- Modelo central valida hotel, trabajador activo, monto, fecha y motivo.
- `saldo_pendiente` inicia igual al monto.
- Health checker y preflight conocen NP-C-C-A.
- No se crean pagos reales, abonos, Nomina ni movimientos de Caja.
- No se toco `/api/sync`.

Resultado NP-C-C-F:

- Estado tecnico: `BLOQUE_NP_C_C_ANTICIPOS_PRESTAMOS_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_C_F_cierre_anticipos_prestamos.md`.
- Se cierra tecnicamente el bloque de anticipos/prestamos manuales.
- Checkers sin errores: preflight de ledger laboral y health general.
- QA manual queda diferida hasta que exista un trabajador activo autorizado.
- Siguiente paso recomendado: contrato nuevo de asistencia manual basica o QA manual NP-C.

Resultado NP-C-D-0:

- Estado tecnico: `CONTRATO_NP_C_D_ASISTENCIA_MANUAL_COMPLETADO`.
- Documento: `docs/fase_NP_C_D_0_contrato_asistencia_manual.md`.
- Se define contrato para futura captura manual de asistencia por trabajador/dia.
- No se implementan rutas, vistas, POST ni escrituras.
- No se autoriza nomina automatica, Caja, pagos reales ni `/api/sync`.

Resultado NP-C-D-A:

- Estado tecnico: `ASISTENCIA_MANUAL_NP_C_D_A_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_D_A_asistencia_manual.md`.
- Se habilita captura manual de asistencia en `trabajador_asistencias` desde la ficha de
  trabajador activo.
- La accion exige hotel actual, permiso existente, POST, CSRF y trabajador activo.
- El modelo valida fecha, tipo, horas, entrada/salida y duplicado por trabajador/dia.
- Health checker y preflight conocen NP-C-D-A.
- No se crea nomina, pagos reales, abonos, categorias Nomina ni movimientos de Caja.
- No se toco `/api/sync`.

Resultado NP-C-D-F:

- Estado tecnico: `BLOQUE_NP_C_D_ASISTENCIA_MANUAL_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_D_F_cierre_asistencia_manual.md`.
- Se cierra tecnicamente el bloque de asistencia manual.
- Checkers sin errores: preflight de ledger laboral y health general.
- QA manual queda diferida hasta que exista un trabajador activo autorizado.
- Siguiente paso recomendado: contrato nuevo independiente, sin abrir nomina ni Caja.

Resultado NP-D-0:

- Estado tecnico: `CONTRATO_NP_D_DOCUMENTOS_LABORALES_COMPLETADO`.
- Documento: `docs/fase_NP_D_0_contrato_documentos_laborales.md`.
- Se define que documentos laborales futuros deben usar Centro Documental moderno
  (`documentos` + `documento_entidades`) con `entidad_tipo = trabajador`.
- `trabajador_documentos` queda congelada como tabla legacy/aditiva hasta fase explicita
  de reconciliacion.
- No se implementan rutas, vistas, POST, migraciones ni escrituras.
- No se toca Caja, pagos, abonos, nomina ni `/api/sync`.

Resultado NP-D-A:

- Estado tecnico: `DOCUMENTOS_LABORALES_NP_D_A_COMPLETADOS_QA_DIFERIDA`.
- Documento: `docs/fase_NP_D_A_documentos_laborales_contextuales.md`.
- Se agrega `trabajador` como entidad del Centro Documental moderno.
- La ficha de trabajador muestra el partial `documentos_entidad` y enlaza al flujo
  documental existente para ver/vincular documentos.
- El contador de documentos laborales usa `documentos` + `documento_entidades` cuando la
  fuente moderna esta disponible.
- `trabajador_documentos` queda congelada; no se escribe ni se expone como storage.
- No se crean rutas nuevas, migraciones, pagos, abonos, nomina, Caja ni cambios en
  `/api/sync`.

Resultado NP-D-F:

- Estado tecnico: `BLOQUE_NP_D_DOCUMENTOS_LABORALES_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_D_F_cierre_documentos_laborales.md`.
- Se cierra tecnicamente documentos laborales contextuales.
- Health checker sin errores valida `trabajador` como entidad documental moderna.
- QA manual de vincular/subir/descargar documento de trabajador queda diferida.
- `trabajador_documentos` sigue congelada y en 0 registros.

Resultado OP-0:

- Estado tecnico: `CONTRATO_OP_0_TABLERO_OPERATIVO_READONLY_COMPLETADO`.
- Documento: `docs/fase_OP_0_contrato_tablero_operativo_readonly.md`.
- Se define un tablero operativo diario futuro en modo solo lectura.
- Alcance futuro: consolidar ocupacion, reservaciones, habitaciones, tareas,
  mantenimiento, trabajadores y documentos recientes por `hotel_id`.
- No se agregan rutas, modelos, vistas, migraciones ni escrituras.
- No se toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Siguiente paso recomendado: OP-A implementacion GET/read-only si se autoriza.

Resultado OP-A:

- Estado tecnico: `TABLERO_OPERATIVO_OP_A_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_OP_A_tablero_operativo_readonly.md`.
- Se agrega `/operacion/diaria` como tablero operativo GET/read-only.
- Consolida fuentes existentes por `hotel_id`: habitaciones, reservaciones, tareas,
  mantenimiento, trabajadores y documentos.
- Health checker y preflight OP-A validan ausencia de POST, storage interno, Caja y
  escrituras.
- No se toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.

Resultado OP-F:

- Estado tecnico: `BLOQUE_OP_TABLERO_OPERATIVO_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_OP_F_cierre_tablero_operativo.md`.
- Revision tecnica y auditoria OP-A completadas sin hallazgos bloqueantes.
- Verificaciones automaticas pasan con `ERROR: 0`.
- QA manual queda diferida.
- Siguiente paso recomendado: abrir contrato independiente para el siguiente bloque.

Resultado MANT-A:

- Estado tecnico: `REPORTE_MANTENIMIENTO_MANT_A_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_A_reporte_mantenimiento_readonly.md`.
- Se estabiliza el reporte existente `/reportes/mantenimiento` sin crear modulo nuevo.
- Se corrige el scope multihotel de las consultas activas usadas por el reporte.
- Se agrega preflight MANT-A y health general ahora valida esta superficie.
- No hay POST, migraciones, escrituras, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- QA manual queda diferida.
- Siguiente paso recomendado: nuevo contrato independiente; no abrir acciones de
  mantenimiento sin autorizacion explicita.

Resultado MANT-F:

- Estado tecnico: `BLOQUE_MANT_REPORTE_MANTENIMIENTO_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_F_cierre_reporte_mantenimiento.md`.
- Se cierra tecnicamente el reporte de mantenimiento read-only.
- Verificaciones automaticas pasan con `ERROR: 0`.
- QA manual queda diferida.
- Siguiente paso recomendado: abrir contrato independiente para el siguiente bloque.

Resultado MANT-B:

- Estado tecnico: `MANTENIMIENTO_INMEDIATO_MANT_B_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_B_mantenimiento_inmediato_guardrails.md`.
- Se endurece la accion existente `POST /habitaciones/{id}/mantenimiento`.
- Se agregan validaciones backend y bloqueo de duplicados en proceso.
- Se agrega `src/tools/saas/preflight_mantenimiento_operativo.php`.
- No hay nuevas rutas, migraciones, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- QA manual queda diferida.

Resultado MANT-B-F:

- Estado tecnico: `BLOQUE_MANT_B_MANTENIMIENTO_INMEDIATO_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_B_F_cierre_mantenimiento_inmediato.md`.
- Se cierra tecnicamente el endurecimiento de mantenimiento inmediato.
- Verificaciones automaticas pasan con `ERROR: 0`.
- QA manual queda diferida.
- Siguiente paso recomendado: contrato independiente antes de automatizar mantenimiento.

Resultado MANT-C-0:

- Estado tecnico: `CONTRATO_MANT_C_0_MANTENIMIENTO_PROGRAMADO_COMPLETADO`.
- Documento: `docs/fase_MANT_C_0_contrato_mantenimiento_programado.md`.
- Se diagnostican rutas existentes de programacion/cancelacion de mantenimiento:
  `POST /habitaciones/{id}/programar-mantenimiento` y
  `POST /habitaciones/cancelar-mantenimiento-programado/{id}`.
- Se identifica como riesgo mayor `Mantenimiento::activarMantenimientosPendientes()`,
  porque cambia mantenimientos a `en_proceso` y habitaciones a `mantenimiento`.
- No se modifica codigo, DB, Caja, pagos, abonos, offline ni `/api/sync`.
- Siguiente paso recomendado: MANT-C-A guardrails de programacion/cancelacion existente,
  sin activar automatizaciones.

Resultado MANT-C-A:

- Estado tecnico: `MANTENIMIENTO_PROGRAMADO_MANT_C_A_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_C_A_guardrails_mantenimiento_programado.md`.
- Se endurecen las rutas existentes de programacion/cancelacion de mantenimiento
  programado sin crear rutas nuevas.
- Programacion ahora valida fechas reales, tipo, prioridad, motivo y solapes por
  habitacion/hotel.
- Cancelacion confirma mantenimiento y habitacion del hotel actual antes de operar.
- Health y preflight MANT reconocen MANT-C-A; ambos quedan con `ERROR: 0`.
- No se activa `Mantenimiento::activarMantenimientosPendientes()`.
- No hay migraciones, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- QA manual queda diferida.

Resultado MANT-C-F:

- Estado tecnico: `BLOQUE_MANT_C_MANTENIMIENTO_PROGRAMADO_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_C_F_cierre_mantenimiento_programado.md`.
- Se cierra tecnicamente el bloque de mantenimiento programado.
- Verificaciones automaticas pasan con `ERROR: 0`.
- Warnings residuales son historicos y quedan documentados.
- QA manual queda diferida.
- Siguiente paso recomendado: nuevo contrato independiente antes de activar
  mantenimientos automaticamente o alterar disponibilidad.

Resultado MANT-D-0:

- Estado tecnico: `CONTRATO_MANT_D_0_PREVIEW_VENCIDOS_COMPLETADO`.
- Documento: `docs/fase_MANT_D_0_contrato_preview_vencidos.md`.
- Se define un preview futuro GET/read-only de mantenimientos programados vencidos/
  proximos y bloqueos operativos.
- Queda prohibido activar vencidos, llamar `activarMantenimientosPendientes()`, cambiar
  habitaciones, crear cron o tocar disponibilidad automaticamente.
- No se modifica codigo ni DB.
- Siguiente paso recomendado: MANT-D-A preview read-only de candidatos.

Resultado MANT-D-A:

- Estado tecnico: `PREVIEW_MANT_D_A_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_D_A_preview_mantenimiento_programado.md`.
- Se agrega `/reportes/mantenimiento-programado` como preview GET/read-only.
- Muestra programados vencidos/proximos, estado de habitacion, conflictos de
  reservacion y candidato revisable.
- Health y preflight MANT validan ruta GET, ausencia de POST, modelo read-only,
  `hotel_id` y bloqueo de activaciones automaticas.
- No se llama `activarMantenimientosPendientes()`.
- No hay migraciones, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- QA manual queda diferida.

Resultado MANT-D-F:

- Estado tecnico: `BLOQUE_MANT_D_PREVIEW_VENCIDOS_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_D_F_cierre_preview_vencidos.md`.
- Se cierra tecnicamente el preview read-only de mantenimientos vencidos/proximos.
- Verificaciones automaticas pasan con `ERROR: 0`.
- Warning residual historico documentado.
- No se agregan nuevas funcionalidades ni activaciones.
- Siguiente paso recomendado: contrato independiente antes de activar mantenimientos.

Resultado MANT-E-0:

- Estado tecnico: `CONTRATO_MANT_E_0_ACTIVACION_MANUAL_COMPLETADO`.
- Documento: `docs/fase_MANT_E_0_contrato_activacion_manual_vencidos.md`.
- Se define contrato para una futura activacion manual individual de mantenimientos
  vencidos o para hoy.
- No se modifica codigo ni DB.
- Queda prohibida la activacion masiva/automatica y el uso web de
  `activarMantenimientosPendientes()`.
- Siguiente paso recomendado: MANT-E-A solo con backup/checklist y QA manual explicita,
  o abrir otro bloque read-only independiente.

Resultado MANT-E-A:

- Estado tecnico: `ACTIVACION_MANUAL_MANT_E_A_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_E_A_activacion_manual_vencidos.md`.
- Se agrega activacion manual individual desde el preview.
- El backend valida hotel, estado programado, fecha vencida/hoy, habitacion disponible,
  ausencia de mantenimiento en proceso y reservaciones conflictivas.
- La escritura usa transaccion y no toca Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- Checkers pasan con `ERROR: 0`.
- QA manual queda diferida; no se ejecuto activacion real de datos desde automatizacion.

Resultado MANT-E-F:

- Estado tecnico: `BLOQUE_MANT_E_ACTIVACION_MANUAL_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_E_F_cierre_activacion_manual.md`.
- Se cierra tecnicamente la activacion manual individual.
- Verificaciones automaticas pasan con `ERROR: 0`.
- QA manual real queda pendiente con backup previo.
- No se autoriza activacion automatica ni masiva.

Resultado MANT-G-0:

- Estado tecnico: `CONTRATO_MANT_G_0_TAREAS_DESDE_MANTENIMIENTO_COMPLETADO`.
- Documento: `docs/fase_MANT_G_0_contrato_tareas_desde_mantenimiento.md`.
- Se diagnostica que `tareas_operativas` ya puede referenciar
  `mantenimientos_habitaciones` mediante `mantenimiento_id`.
- La creacion manual actual de tareas no llena `mantenimiento_id`, por lo que la
  integracion queda como contrato futuro y no como funcionalidad existente.
- No se modifica codigo, DB, rutas, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Siguiente paso recomendado: MANT-G-A con lectura contextual o creacion manual
  estrictamente controlada desde mantenimiento.

Resultado MANT-G-A:

- Estado tecnico: `TAREAS_CONTEXTUALES_MANT_G_A_COMPLETADAS_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_G_A_tareas_contextuales_mantenimiento.md`.
- El preview de mantenimiento programado ahora muestra tareas vinculadas por
  `mantenimiento_id`, si existen.
- La integracion es GET/read-only y no crea tareas.
- No hay rutas nuevas, POST nuevos, Caja, pagos, abonos, nomina, CxP operativa, offline
  ni `/api/sync`.
- Health y preflight pasan con `ERROR: 0`.
- QA manual queda diferida.

Resultado MANT-G-B-0:

- Estado tecnico: `CONTRATO_MANT_G_B_0_CREACION_MANUAL_TAREA_MANTENIMIENTO_COMPLETADO`.
- Documento: `docs/fase_MANT_G_B_0_contrato_creacion_manual_tarea_mantenimiento.md`.
- Se define la futura creacion manual de una tarea vinculada a mantenimiento.
- No se modifica codigo, DB, rutas, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Siguiente paso recomendado: MANT-G-B-A POST manual individual con CSRF, permiso,
  transaccion, auditoria y bloqueo de duplicado activo.

Resultado MANT-G-B-A:

- Estado tecnico: `CREACION_MANUAL_TAREA_MANT_G_B_A_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_G_B_A_creacion_manual_tarea_mantenimiento.md`.
- Se agrega `POST /tareas/desde-mantenimiento/{id}`.
- El modelo crea una tarea pendiente vinculada a mantenimiento y evento inicial.
- Se bloquea duplicado activo por mantenimiento.
- No se cambia habitacion, mantenimiento, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- QA manual queda diferida.

Resultado MANT-G-F:

- Estado tecnico: `BLOQUE_MANT_G_TAREAS_DESDE_MANTENIMIENTO_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_MANT_G_F_cierre_tareas_mantenimiento.md`.
- Se cierra tecnicamente el bloque de tareas desde mantenimiento.
- Health y preflights pasan con `ERROR: 0`.
- QA manual real queda pendiente/diferida.
- No se autoriza automatizacion, acciones masivas, cron, Caja, pagos, abonos, nomina,
  CxP operativa, offline ni `/api/sync`.

Resultado LIM-0:

- Estado tecnico: `CONTRATO_LIM_0_LIMPIEZA_OPERATIVA_COMPLETADO`.
- Documento: `docs/fase_LIM_0_contrato_limpieza_operativa.md`.
- Se diagnostica limpieza como estado operativo existente de habitaciones.
- Se define que tareas de limpieza solo son seguimiento y no fuente de disponibilidad.
- No se modifica codigo, DB, rutas, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Siguiente paso recomendado: LIM-A reporte read-only de limpieza.

Resultado LIM-A:

- Estado tecnico: `REPORTE_LIM_A_LIMPIEZA_READONLY_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_LIM_A_reporte_limpieza_readonly.md`.
- Se agrega `GET /reportes/limpieza`.
- Vista read-only de habitaciones en limpieza y tareas activas de categoria `limpieza`.
- Preflight LIM-A pasa con `ERROR: 0`; health general pasa con `ERROR: 0`.
- No hay POST, formularios, cambios de disponibilidad, Caja, pagos, abonos, nomina,
  offline ni `/api/sync`.

Resultado LIM-F:

- Estado tecnico: `BLOQUE_LIM_LIMPIEZA_READONLY_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_LIM_F_cierre_limpieza_readonly.md`.
- Se cierra tecnicamente el bloque read-only de limpieza.
- QA manual real queda pendiente/diferida.
- No se autoriza POST de limpieza ni automatizaciones.

Resultado LIM-B-0:

- Estado tecnico: `CONTRATO_LIM_B_0_CREACION_MANUAL_TAREA_LIMPIEZA_COMPLETADO`.
- Documento: `docs/fase_LIM_B_0_contrato_creacion_manual_tarea_limpieza.md`.
- Se define una futura accion manual para crear tarea de limpieza desde habitacion en
  estado `limpieza`.
- No se modifica codigo, DB, rutas, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Siguiente paso recomendado: LIM-B-A implementacion manual controlada.

Resultado LIM-B-A:

- Estado tecnico: `CREACION_MANUAL_TAREA_LIM_B_A_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_LIM_B_A_creacion_manual_tarea_limpieza.md`.
- Se agrega POST manual para crear tarea de limpieza desde habitacion en estado
  `limpieza`.
- Se bloquea duplicado activo por habitacion.
- No se cambia disponibilidad, inventario, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.

Resultado LIM-B-F:

- Estado tecnico: `BLOQUE_LIM_B_TAREAS_DESDE_LIMPIEZA_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_LIM_B_F_cierre_tareas_limpieza.md`.
- Commit funcional cerrado: `c6d6743 feat(phase-lim): create housekeeping tasks manually`.
- Verificaciones automaticas:
  - `php -l` en PHP tocado: OK.
  - `preflight_limpieza_operativa.php`: `OK 20`, `WARNING 0`, `ERROR 0`.
  - `health_check_fase_1a.php`: `ERROR 0`, warnings historicos.
  - HTTP sin sesion al POST: redirige a login.
  - SQL read-only: cero tareas creadas por intento sin sesion y Caja sin cambios.
- QA manual queda diferida; no se marca validacion de navegador.
- Siguiente paso recomendado: abrir solo un contrato nuevo e independiente antes
  de automatizar limpieza o integrarla con checkout/liberacion.

Resultado TLM-J-0:

- Estado tecnico: `CONTRATO_TLM_J_0_AGENDA_TAREAS_TRABAJADOR_COMPLETADO`.
- Documento: `docs/fase_TLM_J_0_contrato_agenda_tareas_trabajador.md`.
- Se define una agenda futura GET/read-only de tareas por trabajador y fecha.
- No hay codigo, rutas, DB ni escrituras.
- Siguiente paso recomendado: TLM-J-A implementacion GET/read-only de
  `/tareas/agenda`.

Resultado TLM-J-A:

- Estado tecnico: `AGENDA_TLM_J_A_TAREAS_TRABAJADOR_READONLY_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_J_A_agenda_tareas_trabajador.md`.
- Se agrega `GET /tareas/agenda` con filtros read-only.
- La agenda consulta tareas, trabajadores, habitaciones y mantenimientos con `hotel_id`.
- No hay POST, asignaciones, estados manuales desde agenda, Caja, nomina, offline ni
  `/api/sync`.
- Siguiente paso recomendado: revision/auditoria/cierre TLM-J.

Resultado TLM-J-F:

- Estado tecnico: `BLOQUE_TLM_J_AGENDA_TAREAS_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_J_F_cierre_agenda_tareas.md`.
- Se cierra tecnicamente el bloque de agenda de tareas.
- Health y preflight TLM pasan con `ERROR: 0`.
- QA manual real queda pendiente/diferida.
- No se autorizan acciones operativas nuevas desde la agenda.

Resultado 5A:

- Estado tecnico: `FASE_5A_PERSONAL_LABORAL_BASICO_YA_CUBIERTA_POR_NP`.
- Documento: `docs/fase_5A_reanclaje_personal_laboral_basico.md`.
- El roadmap nuevo 5A queda mapeado al bloque NP existente.
- No se duplica Personal; se conservan `trabajadores` y tablas `trabajador_*` como
  fuentes vigentes.
- No hay nomina automatica, pagos reales, Caja ni `/api/sync`.

Resultado 6B-0:

- Estado tecnico: `CONTRATO_6B_INTEGRACION_TAREAS_HABITACIONES_COMPLETADO`.
- Documento: `docs/fase_6B_0_contrato_integracion_tareas_habitaciones.md`.
- Se define una integracion segura entre tareas y habitaciones.
- La siguiente implementacion permitida debe ser read-only.
- La creacion de tareas de limpieza permanece solo en `/reportes/limpieza`.
- No se autoriza automatizar disponibilidad, Caja, nomina, offline ni `/api/sync`.

Resultado 6B-A:

- Estado tecnico: `INDICADORES_6B_A_TAREAS_HABITACIONES_READONLY_COMPLETADOS_QA_DIFERIDA`.
- Documento: `docs/fase_6B_A_indicadores_tareas_habitaciones.md`.
- Se agrega resumen read-only de tareas activas por habitacion/hotel.
- El tablero de habitaciones muestra conteo solo si hay tareas activas vinculadas.
- No hay POST nuevo, no se crean tareas desde la tarjeta y no se cambia
  `habitaciones.estado`.
- La creacion manual de tareas de limpieza sigue exclusivamente en `/reportes/limpieza`.

Resultado 6C-0:

- Estado tecnico: `CONTRATO_6C_EVIDENCIAS_DOCUMENTOS_TAREAS_COMPLETADO`.
- Documento: `docs/fase_6C_0_contrato_evidencias_documentos_tareas.md`.
- Se define integrar tareas con el Centro Documental moderno usando entidad futura
  `tarea`.
- No se implementan rutas, uploads ni cambios de base.
- Siguiente paso: 6C-A documentos read-only en detalle de tarea.

Resultado 6C-A:

- Estado tecnico: `DOCUMENTOS_6C_A_TAREAS_READONLY_COMPLETADOS_QA_DIFERIDA`.
- Documento: `docs/fase_6C_A_documentos_readonly_tareas.md`.
- Se muestra seccion documental en el detalle de tarea.
- La consulta lee `documento_entidades` con `entidad_tipo = tarea` y valida
  `tareas_operativas.hotel_id`.
- No se habilita upload desde tarea, no hay rutas nuevas ni POST nuevo.

Resultado 6C-B:

- Estado tecnico: `VINCULACION_6C_B_DOCUMENTOS_TAREAS_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_6C_B_vinculacion_segura_documentos_tareas.md`.
- Se habilita `tarea` como entidad valida del Centro Documental.
- El detalle de tarea muestra enlaces contextuales para ver/vincular documentos.
- La carga sigue usando `/documentos/subir` con CSRF, validacion de entidad por
  `hotel_id`, validacion de archivo, storage privado y auditoria existentes.
- No se agregan rutas nuevas, no se expone `storage_path`, no se cambia
  `habitaciones.estado`, no hay Caja, pagos, nomina, offline ni `/api/sync`.

Resultado 6C-F:

- Estado tecnico: `BLOQUE_6C_DOCUMENTOS_TAREAS_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_6C_F_cierre_documentos_tareas.md`.
- Se cierra tecnicamente la integracion de documentos/evidencias en tareas.
- Health y preflight de tareas pasan con `ERROR: 0`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente paso recomendado: 5B Asistencia laboral basica con contrato previo.

Resultado 5B:

- Estado tecnico: `FASE_5B_ASISTENCIA_LABORAL_BASICA_YA_CUBIERTA_POR_NP_C_D`.
- Documento: `docs/fase_5B_reanclaje_asistencia_laboral_basica.md`.
- Se confirma que la asistencia laboral basica ya esta implementada en NP-C-D.
- No se duplica Personal, no se agregan rutas nuevas ni tablas nuevas.
- Fuentes vigentes: `trabajador_asistencias`, `TrabajadorController`,
  `Trabajador::registrarAsistenciaLaboralParaHotel()`.
- No hay nomina automatica, pagos, abonos, Caja ni `/api/sync`.

Resultado 5C:

- Estado tecnico: `FASE_5C_ANTICIPOS_PRESTAMOS_SALDOS_YA_CUBIERTA_POR_NP_C_C`.
- Documento: `docs/fase_5C_reanclaje_anticipos_prestamos_saldos.md`.
- Se confirma que anticipos, prestamos y saldo informativo ya estan implementados en
  NP-C-C.
- No se duplica Personal ni se agregan rutas/tablas nuevas.
- No hay liquidaciones, descuentos automaticos, pagos reales, abonos, Caja ni
  `/api/sync`.

Resultado 5D-0:

- Estado tecnico: `CONTRATO_5D_PAGOS_LABORALES_SIN_CAJA_COMPLETADO`.
- Documento: `docs/fase_5D_0_contrato_pagos_laborales_sin_caja.md`.
- Se diagnostica que `trabajador_pagos` representa conceptos laborales, no pagos reales.
- No se implementan pagos ni DB.
- Se recomienda que una fase futura de pagos laborales use entidad independiente sin
  Caja automatica.
- No hay Caja, nomina automatica, abonos, liquidaciones ni `/api/sync`.

Resultado 7A-0:

- Estado tecnico: `CONTRATO_7A_CXC_READONLY_COMPLETADO`.
- Documento: `docs/fase_7A_0_contrato_cuentas_por_cobrar_readonly.md`.
- Se confirma que no existe tabla `cuentas_por_cobrar`.
- Se identifican fuentes derivadas candidatas: reservaciones, pagos/abonos de
  reservacion y solicitudes de factura.
- No se implementa CxC operativa, Caja, cobros, abonos, pagos ni `/api/sync`.

Resultado 7A-A:

- Estado tecnico: `REPORTE_7A_A_CXC_READONLY_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_7A_A_reporte_cxc_readonly.md`.
- Se agrega `/cuentas-por-cobrar` como reporte GET/read-only derivado.
- La vista calcula saldos estimados desde reservaciones, pagos, abonos y facturacion
  existente, siempre filtrado por `hotel_id`.
- No se crea tabla `cuentas_por_cobrar`.
- No hay POST, cobros, abonos, pagos, movimientos de Caja, facturacion nueva ni
  `/api/sync`.
- Warnings conocidos: 3 pagos historicos y 170 solicitudes de factura con reservacion
  inexistente o de otro hotel; 3 saldos estimados negativos. Quedan para reconciliacion
  antes de una CxC operativa.

Resultado 7A-F:

- Estado tecnico: `BLOQUE_7A_CXC_READONLY_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_7A_F_cierre_cxc_readonly.md`.
- Se cierra tecnicamente 7A con contrato y reporte read-only.
- 7A queda listo para QA manual diferida.
- Cualquier avance a 7B debe iniciar como contrato y no como cobro operativo directo.

Resultado 7A-R:

- Estado tecnico: `RECONCILIACION_7A_R_CXC_READONLY_DIAGNOSTICADA`.
- Documento: `docs/fase_7A_R_reconciliacion_cxc_readonly.md`.
- Diagnostico SQL read-only ejecutado sobre warnings de CxC.
- Los 3 pagos historicos apuntan a reservaciones inexistentes.
- Las 170 solicitudes de factura huerfanas pertenecen a Los Cedros, apuntan a
  reservaciones inexistentes y pertenecen al rango 2026-03 a 2026-05.
- Las 3 reservaciones con saldo negativo pertenecen a Maximiliano Leon y muestran
  doble cobertura por abono demo + pago posterior completo.
- No existe tabla `cuentas_por_cobrar` ni movimientos de Caja tipo `CxC`.
- CxC read-only se mantiene segura, pero CxC operativa sigue bloqueada hasta contrato
  de reconciliacion controlada.

Resultado 7A-S-0:

- Estado tecnico: `CONTRATO_7A_S_RECONCILIACION_CXC_CONTROLADA_COMPLETADO`.
- Documento: `docs/fase_7A_S_0_contrato_reconciliacion_cxc_controlada.md`.
- Se define la politica segura previa a cualquier escritura de reconciliacion CxC.
- La opcion segura por defecto es excluir de CxC operativa los pagos/facturas huerfanas
  y mantener excedentes como informativos.
- Cualquier correccion futura exige backup, preview read-only, matriz de decision,
  auditoria y rollback.
- No se modifican DB, rutas, modelos, Caja, reservaciones, pagos, abonos, facturacion
  ni `/api/sync`.
- Siguiente paso seguro: 7A-S-A preview read-only de reconciliacion o matriz manual de
  decision; no cobros ni escrituras.

Resultado 7A-S-A:

- Estado tecnico: `PREVIEW_7A_S_A_RECONCILIACION_CXC_READONLY_COMPLETADO`.
- Documento: `docs/fase_7A_S_A_preview_reconciliacion_cxc.md`.
- Se construye matriz read-only de reconciliacion sin codigo nuevo.
- Pagos huerfanos: 3 registros, decision default `excluir_cxc_operativa`.
- Facturas huerfanas: 170 registros, decision default `excluir_cxc_operativa`.
- Facturas scoped validas: 5 registros, decision default `mantener_en_reporte_readonly`.
- Excedentes: 3 reservaciones, decision default `mantener_excedente_informativo`.
- No se modifican PHP, DB, rutas, modelos, Caja, reservaciones, pagos, abonos,
  facturacion ni `/api/sync`.
- Siguiente paso seguro: 7A-S-B politica de clasificacion; no escrituras.

Resultado 7A-S-B:

- Estado tecnico: `POLITICA_7A_S_B_CLASIFICACION_CXC_CONSERVADORA_COMPLETADA`.
- Documento: `docs/fase_7A_S_B_politica_clasificacion_cxc.md`.
- Se adopta politica conservadora: pagos huerfanos y facturas huerfanas quedan
  excluidos de CxC operativa.
- Las 5 facturas scoped validas se mantienen solo como contexto read-only.
- Los 3 excedentes se mantienen como informativos, no como deuda ni devolucion.
- No se modifican PHP, DB, rutas, modelos, Caja, reservaciones, pagos, abonos,
  facturacion ni `/api/sync`.
- Siguiente paso seguro vigente despues de 7B-B: 7B-C-0 solo como contrato de
  generacion manual futura, sin implementar escrituras.

Resultado 7A-S-F:

- Estado tecnico: `BLOQUE_7A_S_RECONCILIACION_CXC_CERRADO_SIN_ESCRITURAS`.
- Documento: `docs/fase_7A_S_F_cierre_reconciliacion_cxc.md`.
- Se cierra el bloque de reconciliacion CxC con contrato, preview/matriz y politica
  conservadora.
- Verificaciones: preflight CxC `OK: 14`, `WARNING: 3`, `ERROR: 0`; health general
  `OK: 278`, `WARNING: 24`, `ERROR: 0`.
- No se modifican PHP, DB, rutas, modelos, Caja, reservaciones, pagos, abonos,
  facturacion ni `/api/sync`.
- Siguiente paso seguro vigente despues de 7B-B: 7B-C-0 solo como contrato de
  generacion manual futura, sin implementar escrituras.

Resultado 7B-0:

- Estado tecnico: `CONTRATO_7B_CXC_OPERATIVA_SIN_CAJA_COMPLETADO`.
- Documento: `docs/fase_7B_0_contrato_cxc_operativa_sin_caja.md`.
- Se define una CxC operativa futura como entidad aislada, no como reutilizacion directa
  de pagos/abonos historicos.
- No se crean rutas, tablas, migraciones, cobros ni movimientos.
- Caja, cortes, pagos reales, facturacion nueva y `/api/sync` quedan fuera.
- Por los warnings historicos de 7A, cualquier estructura CxC debia iniciar solo con
  backup y autorizacion explicita de migracion base vacia.

Resultado 7B-A:

- Estado tecnico: `MIGRACION_7B_A_CXC_BASE_VACIA_COMPLETADA`.
- Documento: `docs/fase_7B_A_migracion_cxc_base_vacia.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_7b_a_cxc_base_20260618_163625.sql`.
- SHA256: `0E13547DF43359E71C1A3503EFD8F3A5B5CD096A9BF843EB19F282C0FBE90514`.
- Migracion aplicada: `migrations/20260618_001_fase_7b_a_cxc_base_vacia.sql`.
- Tablas creadas y vacias: `cuentas_por_cobrar=0`,
  `cuentas_por_cobrar_movimientos=0`.
- No se poblo CxC desde historicos, huerfanos ni excedentes.
- No hay cobros, pagos, abonos, movimientos de Caja ni cambios en `/api/sync`.
- Preflight CxC y health general pasan con `ERROR: 0`.
- 7B-B ya fue implementado despues como listado/detalle read-only.

Resultado 7B-B:

- Estado tecnico: `LISTADO_7B_B_CXC_OPERATIVA_READONLY_COMPLETADO`.
- Documento: `docs/fase_7B_B_listado_cxc_operativa_readonly.md`.
- Rutas GET nuevas:
  - `/cuentas-por-cobrar/operativas`;
  - `/cuentas-por-cobrar/operativas/{id}`.
- Se mantiene `/cuentas-por-cobrar` como reporte estimado 7A-A.
- Las tablas operativas siguen vacias: `cuentas_por_cobrar=0`,
  `cuentas_por_cobrar_movimientos=0`.
- No hay POST, cobros, pagos, abonos, movimientos de Caja ni cambios en `/api/sync`.
- Preflight CxC: `OK: 25`, `WARNING: 3`, `ERROR: 0`.
- Health general: `OK: 277`, `WARNING: 25`, `ERROR: 0`.
- HTTP sin sesion a rutas operativas: `303` a login.
- 7B-C-0 ya quedo documentado despues como contrato de generacion manual futura.

Resultado 7B-C-0:

- Estado tecnico: `CONTRATO_7B_C_0_GENERACION_MANUAL_CXC_RESERVACION_COMPLETADO`.
- Documento: `docs/fase_7B_C_0_contrato_generacion_manual_cxc_reservacion.md`.
- Define una futura generacion manual desde reservacion elegible.
- No implementa codigo, rutas POST, botones, DB ni escrituras.
- Decision central: si se implementa despues, la CxC se creara por saldo pendiente neto
  elegible, no por total historico.
- Sigue prohibido poblar desde pagos huerfanos, facturas huerfanas o excedentes.
- Caja, cortes, pagos, abonos, facturacion nueva y `/api/sync` quedan fuera.
- Siguiente paso seguro: 7B-C-A solo con autorizacion explicita de escrituras CxC
  manuales y backup previo.

Resultado 7B-C-A:

- Estado tecnico: `GENERACION_MANUAL_CXC_7B_C_A_VALIDADA_MANUALMENTE`.
- Documento: `docs/fase_7B_C_A_generacion_manual_cxc_reservacion.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_7b_c_a_cxc_manual_20260618_170535.sql`.
- SHA256: `877BA8D0E9F97D8DA3047392EE2C5CC91DACEE20A94F5069BF57A2164377EE7B`.
- Se agrega POST manual con CSRF:
  `/cuentas-por-cobrar/generar-desde-reservacion/{id}`.
- La CxC se genera solo por saldo pendiente neto elegible, con movimiento interno
  `CREACION` y auditoria.
- No se escriben Caja, cortes, reservaciones, pagos, abonos, facturacion nueva ni
  `/api/sync`.
- Preflight CxC actualizado: `OK: 29`, `WARNING: 3`, `ERROR: 0`.
- QA manual validada: se creo CxC `#1` desde reservacion `#24`, hotel `4`, por
  `4250.00`, con movimiento `CREACION #1` y auditoria `#105`.
- Caja-CxC sigue en `0`, duplicados CxC por reservacion `0`.

Resultado 7B-C-F:

- Estado tecnico: `BLOQUE_7B_C_GENERACION_MANUAL_CXC_CERRADO_QA_VALIDADA`.
- Documento: `docs/fase_7B_C_F_cierre_generacion_manual_cxc.md`.
- Cierra contrato, implementacion, QA manual y auditoria post-QA de generacion manual
  CxC.
- La CxC `#1` queda como dato operativo protegido; no borrar ni modificar sin fase de
  rollback/anulacion formal.
- Siguiente paso recomendado: 7B-D-0 contrato/diagnostico de cobro futuro CxC, sin
  escribir Caja hasta autorizacion explicita.

Resultado 7B-D-0:

- Estado tecnico: `CONTRATO_7B_D_0_COBRO_CXC_CAJA_COMPLETADO`.
- Documento: `docs/fase_7B_D_0_contrato_cobro_cxc_caja.md`.
- Define el contrato de cobro futuro de CxC contra Caja.
- No implementa codigo, rutas, formularios, servicios, migraciones ni escrituras.
- Diagnostico clave: `cuentas_por_cobrar_movimientos.tipo_movimiento` no tiene tipo
  `COBRO`; no se debe usar `AJUSTE` para cobros.
- Siguiente paso seguro: 7B-D-A simulador read-only de cobro CxC contra corte abierto,
  sin POST ni escrituras.

Resultado 7B-D-A:

- Estado tecnico: `SIMULADOR_COBRO_CXC_CAJA_7B_D_A_VALIDADO_MANUALMENTE`.
- Documento: `docs/fase_7B_D_A_simulador_cobro_cxc_caja.md`.
- Ruta GET nueva: `/cuentas-por-cobrar/simulador-caja`.
- Evalua CxC operativas contra corte de Caja abierto del hotel actual.
- No tiene POST, no registra cobros, no modifica saldos y no crea movimientos de Caja.
- Bloqueo esperado: mientras `cuentas_por_cobrar_movimientos.tipo_movimiento` no tenga
  tipo `COBRO`, las cuentas quedan bloqueadas para cobro real.
- Preflight CxC: `OK: 33`, `WARNING: 3`, `ERROR: 0`.
- QA manual validada por el usuario: la CxC `#1` aparece en el simulador, sin boton de
  cobro y con bloqueo esperado por falta de tipo `COBRO`.

Resultado 7B-D-F:

- Estado tecnico: `BLOQUE_7B_D_SIMULADOR_COBRO_CXC_CERRADO_QA_VALIDADA`.
- Documento: `docs/fase_7B_D_F_cierre_simulador_cobro_cxc.md`.
- Cierra el contrato 7B-D-0 y el simulador 7B-D-A con QA manual validada.
- Confirma que no hubo escrituras de saldos CxC, movimientos CxC nuevos, Caja, cortes
  ni `/api/sync`.
- Siguiente paso recomendado: 7B-D-B-0 contrato de esquema de cobro CxC antes de
  cualquier migracion.

Resultado 7B-D-B-0:

- Estado tecnico: `CONTRATO_7B_D_B_0_ESQUEMA_COBRO_CXC_COMPLETADO`.
- Documento: `docs/fase_7B_D_B_0_contrato_esquema_cobro_cxc.md`.
- Define la decision tecnica previa a cobros reales: no usar `AJUSTE` para cobros.
- Recomendacion incremental: agregar tipo semantico `COBRO` al ledger CxC y dejar tabla
  de recibos/cobros para una fase posterior.
- No implementa DB, rutas, formularios, servicios ni escrituras.

Resultado 7B-D-B-A:

- Estado tecnico: `MIGRACION_7B_D_B_A_COBRO_ENUM_COMPLETADA`.
- Documento: `docs/fase_7B_D_B_A_migracion_cobro_enum.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_7b_d_b_a_cxc_cobro_enum_20260618_180420.sql`.
- SHA256: `906309EB73EB74B94A62FF493C1B1DAE214F82C02F253AA616C38FFEE3A6C21E`.
- Migracion aplicada:
  `migrations/20260618_002_fase_7b_d_b_a_cxc_movimiento_cobro_enum.sql`.
- `cuentas_por_cobrar_movimientos.tipo_movimiento` ahora incluye `COBRO`.
- No se crearon movimientos tipo `COBRO`, no se modificaron saldos CxC y no se toco Caja.
- Preflight CxC actualizado: `OK: 36`, `WARNING: 3`, `ERROR: 0`.
- Siguiente paso recomendado: 7B-D-C-0 contrato de servicio transaccional de cobro CxC.

Resultado 7B-D-C-0:

- Estado tecnico: `CONTRATO_7B_D_C_0_SERVICIO_COBRO_CXC_CAJA_COMPLETADO`.
- Documento: `docs/fase_7B_D_C_0_contrato_servicio_cobro_cxc_caja.md`.
- Define el servicio futuro `CuentaPorCobrarCobroService`, la ruta propuesta
  `POST /cuentas-por-cobrar/operativas/{id}/registrar-cobro-caja` y el orden
  transaccional.
- No implementa codigo, rutas, formularios, botones, migraciones ni escrituras.
- Deja prohibido escribir en `reservacion_pagos`, `reservacion_abonos`,
  `solicitudes_factura`, cortes, cajas, PWA/offline y `/api/sync`.
- Siguiente paso seguro: 7B-D-C-A solo con autorizacion explicita de escrituras
  financieras, backup y prueba rollback.

Resultado 7B-D-C-A:

- Estado tecnico: `COBRO_CXC_CAJA_7B_D_C_A_VALIDADO_MANUALMENTE`.
- Documento: `docs/fase_7B_D_C_A_cobro_cxc_caja.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_7b_d_c_a_cxc_cash_service_20260618_232434.sql`.
- SHA256: `5708BC91E1BD7A54EFA53A8BA6A92A282ACC98A2AD9237F274AAB87AB11796CD`.
- Se agrega `CuentaPorCobrarCobroService`, POST controlado, formulario con CSRF/token y
  prueba rollback.
- QA manual validada: CxC `#1` queda `parcial`, saldo `4249.00`, movimiento
  `COBRO #4` por `1.00`, ingreso Caja `#1482` con categoria `Cobro CxC`.
- Prueba rollback post-QA OK: movimiento CxC temporal `#5`, movimiento Caja temporal
  `#1483`, rollback completo sin persistir cambios adicionales.
- Conteos post-QA: CxC `1`, movimientos CxC `2`, movimientos `COBRO=1`,
  Caja-CxC `1`.
- Preflight CxC: `OK: 39`, `WARNING: 5`, `ERROR: 0`.
- Health general: `OK: 277`, `WARNING: 25`, `ERROR: 0`.

Resultado 7B-D-D-0:

- Estado tecnico: `CONTRATO_7B_D_D_0_ANULACION_REVERSION_COBRO_CXC_COMPLETADO`.
- Documento: `docs/fase_7B_D_D_0_contrato_reversion_cobro_cxc.md`.
- Define reversion sin borrar ni editar cobro original.
- La implementacion posterior crea movimiento CxC `CANCELACION`, gasto Caja
  `Reversion Cobro CxC`, auditoria y actualizacion transaccional de saldo/estado.
- No implementa codigo, rutas, DB ni escrituras.
- 7B-D-D-A fue autorizada, implementada y validada manualmente despues de este
  contrato.

Resultado 7B-D-D-A:

- Estado tecnico: `REVERSION_COBRO_CXC_CAJA_7B_D_D_A_VALIDADA_MANUALMENTE`.
- Documento: `docs/fase_7B_D_D_A_reversion_cobro_cxc.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_7b_d_d_a_cxc_reversal_20260619_091552.sql`.
- SHA256: `9B0157802EF9A959983879CF43505F6ED446613533B0E3A7C4FFCED4BFEEC0AE`.
- Se agrega `CuentaPorCobrarReversionCobroService`, POST controlado, token de
  reversion, panel en detalle y prueba rollback.
- QA manual validada: CxC `#1` vuelve a `pendiente`, saldo `4250.00`,
  `CANCELACION #8`, gasto Caja `#1486`, referencia `REV-CXC-1-MOV-4`.
- Prueba rollback post-QA OK: `COBRO` temporal `#10`, `CANCELACION` temporal `#11`,
  ingreso Caja temporal `#1488`, gasto Caja temporal `#1489`, rollback completo.
- Regresion cobro CxC post-QA OK: movimiento CxC temporal `#12`, Caja temporal
  `#1490`, rollback completo.
- Conteos finales: CxC `#1` pendiente saldo `4250.00`, `COBRO=1`,
  `CANCELACION REV=1`, Caja-CxC `1`, Caja-Reversion `1`, doble reversion `0`.
- Preflight CxC: `OK: 45`, `WARNING: 6`, `ERROR: 0`.
- Health general: `OK: 277`, `WARNING: 25`, `ERROR: 0`.
- La reversion real queda validada; no borrar `CANCELACION #8` ni Caja `#1486`
  con SQL directo.

Resultado 7B-D-D-F:

- Estado tecnico: `CIERRE_7B_D_D_F_REVERSION_COBRO_CXC_CAJA_COMPLETADO`.
- Documento: `docs/fase_7B_D_D_F_cierre_reversion_cobro_cxc.md`.
- Cierra documentalmente cobro CxC con Caja y reversion de cobro CxC.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Evidencia final: CxC `#1` pendiente saldo `4250.00`, cobro `#4`, Caja `#1482`,
  `CANCELACION #8`, Caja reversion `#1486`, referencia `REV-CXC-1-MOV-4`.
- Validacion de cierre: preflight CxC `OK: 45`, `WARNING: 6`, `ERROR: 0`;
  health general `OK: 278`, `WARNING: 25`, `ERROR: 0`.
- Deja como limite formal no avanzar a cobros masivos, reversiones masivas,
  reversiones parciales ni automatizaciones sin contrato independiente, backup y
  rollback.

Resultado 8A-0:

- Estado tecnico: `CONTRATO_8A_DASHBOARD_KPIS_READONLY_COMPLETADO`.
- Documento: `docs/fase_8A_0_contrato_dashboard_operativo_kpis.md`.
- Se reancla 8A sobre el tablero operativo existente `/operacion/diaria`.
- No se crea un dashboard paralelo.
- No se implementa codigo, rutas ni DB.
- Se proponen KPIs read-only de CxC estimada, tareas, mantenimiento, documentos y
  personal, siempre sin Caja ni `/api/sync`.

Resultado 8A-A:

- Estado tecnico: `KPIS_8A_A_DASHBOARD_READONLY_COMPLETADOS_QA_DIFERIDA`.
- Documento: `docs/fase_8A_A_kpis_readonly_tablero_operativo.md`.
- Se extiende `/operacion/diaria` con KPIs de CxC estimada.
- No se crean rutas nuevas ni dashboard paralelo.
- No hay POST, formularios, cobros, pagos, abonos, Caja ni `/api/sync`.
- `preflight_operacion_diaria.php` queda actualizado para validar OP-A/8A-A.

Resultado 8A-F:

- Estado tecnico: `BLOQUE_8A_DASHBOARD_KPIS_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_8A_F_cierre_dashboard_kpis.md`.
- Se cierra tecnicamente 8A sin QA manual.
- El siguiente bloque del roadmap es 3D, pero solo es seguro iniciar contrato/diagnostico
  porque implica pagos proveedores con Caja.

Resultado 3D-0:

- Estado tecnico: `CONTRATO_3D_PAGOS_PROVEEDORES_CAJA_COMPLETADO`.
- Documento: `docs/fase_3D_0_contrato_pagos_proveedores_caja.md`.
- Se diagnostica CxP/Caja en modo lectura.
- No se implementan pagos, rutas, movimientos, migraciones ni cambios de saldo.
- 3D queda bloqueado para implementacion real hasta backup, simulador read-only,
  servicio transaccional y QA manual especifica.

Resultado 3D-A:

- Estado tecnico: `SIMULADOR_3D_A_CAJA_READONLY_VALIDADO_MANUALMENTE`.
- Documento: `docs/fase_3D_A_simulador_caja_readonly.md`.
- Se agrega GET `/cuentas-por-pagar/simulador-caja`.
- El simulador evalua CxP contra proveedor, compra, saldo, estado y corte de Caja
  abierto del hotel actual.
- No hay POST, pagos, abonos, movimientos CxP, movimientos de Caja, cambios de saldo
  ni `/api/sync`.
- `preflight_pagos_proveedores_caja.php` valida la superficie 3D-A en modo solo lectura.
- QA manual: el usuario confirmo que reviso el bloque y esta bien.

Resultado 3D-B:

- Estado tecnico: `CONTRATO_3D_B_SERVICIO_PAGO_TRANSACCIONAL_COMPLETADO`.
- Documento: `docs/fase_3D_B_contrato_servicio_pago_transaccional.md`.
- Se define el contrato del servicio futuro para pagar proveedores con Caja.
- No se implementa codigo PHP, rutas, formularios, botones, migraciones ni escrituras.
- La implementacion real queda bloqueada hasta backup verificado, prueba local controlada
  y autorizacion explicita de escrituras financieras.
- Revision manual: el usuario confirmo que reviso el bloque y esta bien.

Resultado 3D-C:

- Estado tecnico: `PAGO_PROVEEDOR_CAJA_3D_C_VALIDADO_MANUALMENTE`.
- Documento: `docs/fase_3D_C_pago_proveedor_caja.md`.
- Backup limpio confirmado:
  `backups/medisoft_hoteles_import_before_3d_payments_20260617_105846.sql`.
- Se agrega `CuentaPorPagarPagoService` para registrar pago proveedor de forma
  transaccional.
- Se agrega POST `/cuentas-por-pagar/{id}/registrar-pago-caja` con CSRF, modulo Caja y
  token de pago de un solo uso.
- El detalle de CxP muestra formulario solo si la cuenta es elegible.
- Se agrega prueba rollback `tools/saas/probar_pago_proveedor_caja.php`.
- No se agregan pagos automaticos desde compras, abonos, cambios en pantallas de Caja ni
  `/api/sync`.
- QA manual completada por el usuario: pago parcial de `10.00` sobre CxP `#1`.
- Evidencia post-QA: pago parcial `#4` por `10.00` con Caja `#1478` y pago total
  restante `#5` por `990.00` con Caja `#1479`; CxP `#1` queda `pagada`, saldo
  `0.00`.
- Preflight post-QA `preflight_pagos_proveedores_caja.php`: `OK: 22`,
  `WARNING: 0`, `ERROR: 0`.
- Pendiente futuro: no escalar pagos reales hasta implementar anulacion/reversion formal.

Resultado 3D-D-0:

- Estado tecnico: `CONTRATO_3D_D_0_REVERSION_PAGO_PROVEEDOR_CAJA_COMPLETADO`.
- Documento: `docs/fase_3D_D_0_contrato_reversion_pago_proveedor_caja.md`.
- Define reversion sin borrar ni editar pagos proveedor originales.
- La futura implementacion debe crear movimiento CxP `CANCELACION`, ingreso Caja
  `Reversion Pago proveedor`, auditoria y actualizacion transaccional de saldo/estado.
- No implementa codigo, rutas, formularios, migraciones ni escrituras.
- 3D-D-A fue autorizada, implementada y validada manualmente despues de este
  contrato.

Resultado 3D-D-A:

- Estado tecnico: `REVERSION_PAGO_PROVEEDOR_CAJA_3D_D_A_VALIDADA_MANUALMENTE`.
- Documento: `docs/fase_3D_D_A_reversion_pago_proveedor_caja.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_3d_d_a_cxp_payment_reversal_20260619_095320.sql`.
- SHA256: `3BB71EF0C696E1AB0CB3FC4A4B2E51319DCD654F0CEAA7E83280378F9A9001FB`.
- Se agrega `CuentaPorPagarReversionPagoService`, POST controlado, token de
  reversion, panel en detalle y prueba rollback.
- Prueba rollback OK: `PAGO_REFERENCIAL` temporal `#6`, `CANCELACION` temporal
  `#7`, gasto Caja temporal `#1491`, ingreso Caja temporal `#1492`, rollback
  completo sin persistir reversion.
- Regresion pago proveedor OK: movimiento CxP temporal `#8`, Caja temporal `#1493`,
  rollback completo.
- QA manual validada: CxP `#1`, pago proveedor `#4` por `10.00`, movimiento
  CxP `CANCELACION #9`, Caja ingreso `#1494`, referencia `REV-CXP-1-MOV-4`.
- Resultado post-QA: CxP `#1` queda `parcial`, saldo `10.00`.
- Conteos finales: `PAGO_REFERENCIAL=2`, `CANCELACION REV=1`, Caja Pago proveedor
  `2`, Caja Reversion `1`.
- Preflight pagos proveedor: `OK: 29`, `WARNING: 1`, `ERROR: 0`.
  La advertencia corresponde a la `CANCELACION` real validada.
- Health general: `OK: 278`, `WARNING: 25`, `ERROR: 0`.
- Health actualizado para considerar como consistentes las cancelaciones CxP y los
  ingresos Caja de reversion.

Resultado 3D-D-F:

- Estado tecnico: `CIERRE_3D_D_F_REVERSION_PAGO_PROVEEDOR_CAJA_COMPLETADO`.
- Documento: `docs/fase_3D_D_F_cierre_reversion_pago_proveedor_caja.md`.
- Cierra documentalmente pagos y reversiones de proveedor con Caja.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Deja como limite formal no avanzar a reversiones masivas, reversiones parciales,
  pagos masivos ni automatizaciones sin contrato independiente, backup y rollback.

Resultado 9A-0:

- Estado tecnico: `CONTRATO_9A_0_CONCILIACION_FINANCIERA_READONLY_COMPLETADO`.
- Documento: `docs/fase_9A_0_contrato_conciliacion_financiera_readonly.md`.
- Define una futura conciliacion financiera operativa solo lectura entre CxC, CxP y
  Caja.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Prohibe correcciones automaticas, pagos, cobros, reversiones, ajustes, cambios de
  corte y cualquier modificacion de saldos.
- Siguiente paso recomendado: 9A-A como preflight read-only antes de cualquier UI.

Resultado 9A-A:

- Estado tecnico: `PREFLIGHT_9A_A_CONCILIACION_FINANCIERA_READONLY_COMPLETADO`.
- Documento: `docs/fase_9A_A_preflight_conciliacion_financiera_readonly.md`.
- Herramienta nueva: `src/tools/saas/preflight_conciliacion_financiera.php`.
- Implementa conciliacion CLI/read-only entre CxC, CxP, Caja, cortes y auditoria.
- No agrega rutas, vistas, formularios, POST, migraciones, modelos ni escrituras.
- Resultado del preflight 9A-A: `OK: 75`, `WARNING: 0`, `ERROR: 0`.
- Regresiones: preflight CxC `ERROR: 0`; preflight pagos proveedor `ERROR: 0`.
- Health general: `OK: 278`, `WARNING: 25`, `ERROR: 0`.
- Siguiente paso seguro: contrato 9A-B para una futura pantalla GET/read-only.

Resultado 9A-B-0:

- Estado tecnico:
  `CONTRATO_9A_B_0_PANTALLA_CONCILIACION_FINANCIERA_READONLY_COMPLETADO`.
- Documento:
  `docs/fase_9A_B_0_contrato_pantalla_conciliacion_financiera_readonly.md`.
- Define la futura pantalla `GET /operacion/conciliacion-financiera` como superficie
  candidata, sin implementarla todavia.
- Exige UI solo lectura con filtros GET, branding del hotel `--brand-*` y sin tokens
  `--ms-*`.
- Prohibe botones de corregir, ajustar, pagar, cobrar, revertir o compensar.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, migraciones ni
  escrituras.
- Siguiente paso seguro: 9A-B-A solo si se autoriza implementar una pantalla GET.

## 11A-A Perfil operativo de huesped read-only

Estado formal:
`PERFIL_11A_A_HUESPED_READONLY_VALIDADO_MANUALMENTE`.

- Documento creado:
  `docs/fase_11A_A_perfil_huesped_readonly.md`.
- Ficha usada: `GET /huespedes/{id}` existente.
- Lector nuevo: `Huesped::perfilOperativoReadOnlyPorHotel()`.
- Vista: bloque `Perfil operativo` con etiqueta `Solo lectura`.
- Datos mostrados: clasificacion, score al vuelo, proxima estancia, saldo CxC,
  documentos y alertas visuales.
- No agrega rutas, POST, formularios nuevos, migraciones ni escrituras.
- Health checker actualizado para validar ruta GET, controlador, modelo y bloque
  visual read-only.
- Validaciones automaticas: PHP lint OK, health `OK: 304`, `WARNING: 25`,
  `ERROR: 0`, HTTP sin sesion `303`, `git diff --check` scoped OK.
- QA manual validada por el usuario.

## 11A-F Cierre perfil operativo de huesped read-only

Estado formal:
`CIERRE_11A_PERFIL_HUESPED_READONLY_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_11A_F_cierre_perfil_huesped_readonly.md`.
- Cierra el bloque 11A-0/11A-A como perfil operativo de huesped read-only.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, migraciones
  ni datos.
- Mantiene el perfil como lectura informativa sin CxC nueva, cobros, pagos,
  check-in/check-out, cambios de reservaciones, documentos, tareas, permisos/auth,
  PWA/offline ni `/api/sync`.

## 5E-0 Contrato pagos laborales con Caja

Estado formal:
`CONTRATO_5E_0_PAGOS_LABORALES_CAJA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_0_contrato_pagos_laborales_caja.md`.
- Es solo contrato/diagnostico; no agrega codigo, rutas, vistas, formularios,
  migraciones ni escrituras.
- Define el futuro flujo de pagos laborales reales con Caja.
- Mantiene `trabajador_pagos` como conceptos laborales, no pagos reales.
- Recomienda entidad independiente futura `trabajador_pagos_caja`.
- Exige para una implementacion posterior: backup, corte abierto, transaccion,
  referencia unica, movimiento Caja tipo gasto, auditoria, token de pago y prueba
  rollback.
- Prohibe por ahora pagos reales, movimientos de Caja, nomina automatica,
  abonos/liquidaciones de anticipos/prestamos, reversiones y `/api/sync`.
- Siguiente paso seguro: 5E-A preflight CLI/read-only.

## 5E-A Preflight pagos laborales con Caja

Estado formal:
`PREFLIGHT_5E_A_PAGOS_LABORALES_CAJA_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_A_preflight_pagos_laborales_caja.md`.
- Se agrega `src/tools/saas/preflight_personal_pagos_caja.php`.
- Se extiende `src/tools/saas/health_check_fase_1a.php`.
- Es una fase solo lectura: no crea rutas, vistas, formularios, migraciones, tabla,
  pagos ni movimientos de Caja.
- Resultado automatico: preflight 5E-A `OK: 34`, `WARNING: 1`, `ERROR: 0`;
  health general `OK: 305`, `WARNING: 25`, `ERROR: 0`.
- Hallazgo operativo: no hay trabajadores activos para QA futura de pago real.
- Siguiente paso seguro: 5E-B-0 contrato de migracion aditiva para
  `trabajador_pagos_caja`.

## 5E-B-0 Contrato migracion pagos laborales con Caja

Estado formal:
`CONTRATO_5E_B_0_MIGRACION_PAGOS_LABORALES_CAJA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_B_0_contrato_migracion_pagos_laborales_caja.md`.
- Es contrato documental; no agrega SQL, migracion, tabla, rutas, vistas, formularios,
  servicios, datos ni movimientos de Caja.
- Define el esquema futuro de `trabajador_pagos_caja` con enlace a trabajador, corte,
  movimiento de Caja, metodo, referencia, periodo, estado y auditoria.
- Define que la referencia futura debe ser unica por hotel y compartida con
  `movimientos_caja`.
- Prohibe alterar `trabajador_pagos` y confirma que sigue siendo tabla de conceptos.
- Siguiente paso seguro: 5E-B-A migracion aditiva solo con backup y autorizacion
  explicita para tocar DB.

## 5E-B-A Migracion pagos laborales con Caja

Estado formal:
`MIGRACION_5E_B_A_PAGOS_LABORALES_CAJA_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_B_A_migracion_pagos_laborales_caja.md`.
- Migracion aplicada:
  `migrations/20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_5e_b_a_trabajador_pagos_caja_20260619_220444.sql`.
- SHA256:
  `2E279999DA95C0216942F1FE480E5E43E96AAE42A06DA7C9FD83633BC53898BC`.
- Se creo `trabajador_pagos_caja` vacia y registrada en `migrations`.
- Preflight 5E queda en `ERROR: 0`.
- Health general queda en `ERROR: 0`.
- No se implementa pago real ni se toca Caja operativa.
- Siguiente paso seguro: 5E-C-0 contrato de simulador GET/read-only.

## 5E-C-0 Contrato simulador pago laboral con Caja

Estado formal:
`CONTRATO_5E_C_0_SIMULADOR_PAGO_LABORAL_CAJA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_C_0_contrato_simulador_pago_laboral_caja.md`.
- Es contrato documental; no agrega codigo operativo, rutas, vistas, formularios,
  servicios, POST, pagos ni movimientos de Caja.
- Define la futura ruta candidata:
  `GET /trabajadores/pagos-caja/simulador`.
- El simulador futuro debera mostrar elegibilidad/bloqueos de pago laboral sin
  persistir calculos.
- Siguiente paso seguro: 5E-C-A implementacion GET/read-only solo con autorizacion
  para tocar rutas, controlador/modelo read-only, vista y checkers.

## 5E-C-A Simulador pago laboral con Caja

Estado formal:
`SIMULADOR_5E_C_A_PAGO_LABORAL_CAJA_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_C_A_simulador_pago_laboral_caja.md`.
- Ruta nueva:
  `GET /trabajadores/pagos-caja/simulador`.
- Se agrego vista read-only con filtros GET, corte abierto, saldo estimado,
  referencia y motivos de elegibilidad/bloqueo.
- Se agregaron enlaces GET desde listado y ficha de trabajadores.
- Modelo/controlador solo leen datos; no hay POST, token, servicio, pago real ni
  movimiento de Caja.
- Checkers actualizados: preflight 5E y health general.
- Validaciones automaticas: lint OK en archivos tocados, preflight 5E `OK: 41`,
  `WARNING: 0`, `ERROR: 0`; health general `OK: 309`, `WARNING: 26`, `ERROR: 0`;
  HTTP sin sesion `303`, no `404`.
- Siguiente paso seguro: 5E-D-0 contrato de servicio transaccional, sin implementar
  pago real todavia.

## 5E-C-F Cierre simulador pago laboral con Caja

Estado formal:
`CIERRE_5E_C_SIMULADOR_PAGO_LABORAL_CAJA_QA_MANUAL_VALIDADA`.

- Documento creado: `docs/fase_5E_C_F_cierre_simulador_pago_laboral_caja.md`.
- El usuario confirmo que la prueba manual del simulador paso correctamente.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Confirma que 5E-C queda como diagnostico GET/read-only, sin pago real.

## 5E-D-0 Contrato servicio pago laboral con Caja

Estado formal:
`CONTRATO_5E_D_0_SERVICIO_PAGO_LABORAL_CAJA_COMPLETADO`.

- Documento creado: `docs/fase_5E_D_0_contrato_servicio_pago_laboral_caja.md`.
- Es contrato documental; no agrega codigo, rutas POST, formularios, servicios, migraciones ni escrituras.
- Define el servicio futuro `TrabajadorPagoCajaService`.
- Define validaciones, saldo disponible, orden transaccional, token de un solo uso, auditoria y prueba rollback futura.
- Mantiene prohibidos pago real, reversion, abonos/liquidaciones, nomina automatica, permisos nuevos, PWA/offline y `/api/sync`.
- Siguiente paso seguro: 5E-D-A solo con autorizacion explicita y backup previo.

## 5E-D-A Pago laboral con Caja controlado

Estado formal:
`SERVICIO_5E_D_A_PAGO_LABORAL_CAJA_CONTROLADO_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_D_A_pago_laboral_caja_controlado.md`.
- Servicio nuevo:
  `src/app/services/TrabajadorPagoCajaService.php`.
- Ruta nueva:
  `POST /trabajadores/{id}/registrar-pago-caja`.
- Panel nuevo en ficha de trabajador con CSRF, token de un solo uso, monto, metodo y referencia.
- El servicio registra `trabajador_pagos_caja`, egreso `movimientos_caja` categoria `Pago laboral` y auditoria.
- Se ajusto el simulador para descontar pagos laborales ya registrados.
- Prueba rollback nueva:
  `src/tools/saas/probar_pago_laboral_caja.php`.
- Validaciones automaticas: lint OK, rollback OK, preflight 5E `OK: 41`, `WARNING: 0`, `ERROR: 0`; health general `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- No implementa reversion, nomina automatica ni abonos/liquidaciones de anticipos/prestamos.
- Siguiente paso seguro: 5E-D-F cierre manual/documental si la prueba manual pasa.

## 5E-D-F Cierre pago laboral con Caja

Estado formal:
`CIERRE_5E_D_F_PAGO_LABORAL_CAJA_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_5E_D_F_cierre_pago_laboral_caja.md`.
- El usuario confirmo que la prueba manual del pago laboral con Caja paso correctamente.
- La ficha muestra saldo disponible para pago, bruto laboral y pagos Caja aplicados
  separados para evitar confundir saldo bruto con deuda pendiente.
- Se valido que, tras pagar el saldo completo, el disponible queda en `0.00` y el
  panel de pago se bloquea por saldo no positivo.
- No agrega codigo, rutas, controladores, modelos, formularios, migraciones ni datos.
- Mantiene fuera de alcance reversion, pagos masivos, nomina automatica,
  abonos/liquidaciones, permisos/auth, PWA/offline y `/api/sync`.
- Siguiente paso seguro: contrato independiente para reversion o historial detallado
  read-only de pagos laborales con Caja.

## 5E-E-F Cierre historial, reversion y reporte pagos laborales con Caja

Estado formal:
`CIERRE_5E_E_F_HISTORIAL_REVERSION_REPORTE_PAGOS_LABORALES_CAJA_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_5E_E_F_cierre_historial_reversion_reporte_pagos_laborales_caja.md`.
- El usuario confirmo que pasaron las pruebas manuales de historial, reversion,
  reporte y export CSV de pagos laborales con Caja.
- Quedan cerradas las superficies:
  - historial read-only en ficha de trabajador;
  - reversion controlada individual con CSRF/token/servicio;
  - reporte read-only de pagos laborales Caja;
  - export CSV GET/read-only respetando filtros.
- Validaciones automaticas recientes: preflight pagos laborales Caja `OK: 45`,
  `WARNING: 0`, `ERROR: 0`; health general `OK: 313`, `WARNING: 25`,
  `ERROR: 0`; ruta export CSV local responde `303` a login sin 404.
- No autoriza pagos masivos, nomina automatica, recibos oficiales, dispersion,
  liquidaciones automaticas, permisos/auth, PWA/offline ni `/api/sync`.
- Siguiente paso seguro: contrato independiente antes de preview de nomina por
  periodo, recibo laboral informativo o cualquier nuevo frente financiero.

## 5E-G-0 Contrato preview read-only de nomina por periodo

Estado formal:
`CONTRATO_5E_G_0_NOMINA_PERIODO_PREVIEW_READ_ONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_G_0_contrato_nomina_periodo_preview.md`.
- Es contrato documental; no agrega codigo, rutas, modelos, vistas, formularios,
  migraciones, permisos, servicios, Caja, datos ni `/api/sync`.
- Define una futura ruta GET candidata: `/trabajadores/nomina/preview`.
- Define filtros por periodo, trabajador, busqueda, rol, estado y saldo.
- Define calculos read-only: bruto del periodo, deducciones informativas, pagos Caja
  aplicados, reversiones detectadas y neto sugerido.
- Mantiene prohibidos pagos masivos, nomina automatica, calculo fiscal oficial,
  recibos oficiales, timbrado, dispersion bancaria, liquidaciones automaticas,
  permisos/auth, PWA/offline y `/api/sync`.
- Siguiente paso seguro: `5E-G-A` solo con autorizacion explicita para tocar ruta,
  controlador, modelo, vista y checkers en modo GET/read-only.

## 5E-G-A Preview read-only de nomina por periodo

Estado formal:
`PREVIEW_5E_G_A_NOMINA_PERIODO_READ_ONLY_COMPLETADO_QA_MANUAL_PENDIENTE`.

- Documento creado:
  `docs/fase_5E_G_A_nomina_periodo_preview_read_only.md`.
- Ruta nueva:
  `GET /trabajadores/nomina/preview`.
- Implementa filtros GET por periodo, trabajador, busqueda, rol, estado, solo con saldo
  e incluir pagos Caja.
- Calcula bruto del periodo, deducciones informativas, pagos Caja aplicados,
  reversiones detectadas, neto sugerido y pendiente sugerido.
- La vista no tiene POST, CSRF, pago masivo, recibos, dispersion ni acciones de Caja.
- Validaciones automaticas: preflight pagos laborales Caja `OK: 47`, `WARNING: 0`,
  `ERROR: 0`; health general `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- Prueba CLI del modelo: hotel `4`, trabajadores `1`, bloqueos `0`, neto `100.00`.
- Siguiente paso seguro: QA manual del preview y luego cierre documental `5E-G-F` si
  la prueba pasa.

## 5E-G-F Cierre preview read-only de nomina por periodo

Estado formal:
`CIERRE_5E_G_F_NOMINA_PERIODO_PREVIEW_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_5E_G_F_cierre_nomina_periodo_preview.md`.
- El usuario confirmo que la prueba manual del preview de pre-nomina paso
  correctamente.
- Queda cerrada la pantalla GET/read-only `/trabajadores/nomina/preview`.
- La pantalla conserva filtros GET, periodo requerido, calculos read-only y ausencia de
  POST, pago masivo, recibos oficiales, dispersion o movimientos de Caja.
- No autoriza nomina automatica, periodos oficiales persistidos, timbrado,
  liquidaciones automaticas, permisos/auth, PWA/offline ni `/api/sync`.
- Siguiente paso seguro: contrato independiente antes de recibo laboral informativo,
  export CSV del preview o cualquier nuevo frente financiero.

## 5E-H-0 Contrato export CSV del preview de pre-nomina

Estado formal:
`CONTRATO_5E_H_0_EXPORT_CSV_NOMINA_PREVIEW_READ_ONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_H_0_contrato_export_csv_nomina_preview.md`.
- Es contrato documental; no agrega codigo, rutas, modelos, vistas, formularios,
  migraciones, permisos, servicios, Caja, datos ni `/api/sync`.
- Define una futura ruta GET candidata:
  `/trabajadores/nomina/preview/exportar`.
- La exportacion futura debe reutilizar los mismos filtros y calculos read-only de
  `/trabajadores/nomina/preview`.
- Define CSV con headers de descarga, columnas de periodo/trabajador/totales/pagos Caja
  y limite razonable de filas.
- Mantiene prohibidos pagos masivos, nomina automatica, recibos oficiales, timbrado,
  dispersion bancaria, liquidaciones automaticas, permisos/auth, PWA/offline y
  `/api/sync`.
- Siguiente paso seguro: `5E-H-A` solo con autorizacion explicita para tocar ruta,
  controlador/modelo read-only, vista y checkers.

## 5E-H-A Export CSV del preview de pre-nomina

Estado formal:
`EXPORT_CSV_5E_H_A_NOMINA_PREVIEW_READ_ONLY_COMPLETADO_QA_MANUAL_PENDIENTE`.

- Documento creado:
  `docs/fase_5E_H_A_export_csv_nomina_preview.md`.
- Ruta nueva:
  `GET /trabajadores/nomina/preview/exportar`.
- La exportacion reutiliza `Trabajador::nominaPreviewPorHotel` con los mismos filtros
  GET del preview.
- La vista `nomina_preview.php` agrega enlace `Exportar CSV` sin formularios POST ni
  CSRF.
- El CSV usa BOM UTF-8, headers de descarga y columnas de periodo, trabajador, bruto,
  deducciones, pagos Caja, reversiones, neto y pendiente sugerido.
- Si el periodo falta o es invalido, la accion redirige al preview y no descarga CSV
  ambiguo.
- No hay pagos masivos, nomina automatica, recibos oficiales, timbrado, dispersion,
  liquidaciones automaticas, movimientos de Caja, migraciones, datos, permisos/auth,
  PWA/offline ni `/api/sync`.
- Siguiente paso seguro: QA manual de la descarga CSV y luego cierre documental si
  pasa.

## 5E-H-F Cierre export CSV del preview de pre-nomina

Estado formal:
`CIERRE_5E_H_F_EXPORT_CSV_NOMINA_PREVIEW_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_5E_H_F_cierre_export_csv_nomina_preview.md`.
- El usuario confirmo que la prueba manual del export CSV de pre-nomina paso
  correctamente.
- Queda cerrada la ruta GET/read-only
  `/trabajadores/nomina/preview/exportar`.
- La descarga conserva filtros GET, periodo requerido, calculos read-only, headers CSV
  y ausencia de POST, pago masivo, recibos oficiales, dispersion o movimientos de Caja.
- Validaciones automaticas registradas: preflight pagos laborales Caja `OK: 48`,
  `WARNING: 0`, `ERROR: 0`; health general `OK: 313`, `WARNING: 25`,
  `ERROR: 0`; ruta export local sin sesion `303` a login, sin 404.
- No autoriza nomina automatica, periodos oficiales persistidos, timbrado,
  liquidaciones automaticas, auditoria por descarga, permisos/auth, PWA/offline ni
  `/api/sync`.
- Siguiente paso seguro: contrato independiente antes de recibo laboral informativo,
  recibo descargable, cierre formal de periodo o pago masivo.

## 5E-I-0 Contrato recibo laboral informativo

Estado formal:
`CONTRATO_5E_I_0_RECIBO_LABORAL_INFORMATIVO_READ_ONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_I_0_contrato_recibo_laboral_informativo.md`.
- Es contrato documental; no agrega codigo, rutas, modelos, vistas, formularios,
  migraciones, permisos, servicios, Caja, datos ni `/api/sync`.
- Define una futura ruta GET candidata:
  `/trabajadores/{id}/recibo-laboral`.
- El recibo futuro debe ser informativo, scoped por hotel, por trabajador y por
  periodo, reutilizando calculos read-only de pre-nomina.
- Debe mostrar etiqueta visible `No fiscal / No genera pago` y no debe confundirse con
  nomina oficial, CFDI, timbrado, dispersion o pago masivo.
- Mantiene prohibidos pagos masivos, nomina automatica, recibos fiscales oficiales,
  timbrado, dispersion bancaria, liquidaciones automaticas, auditoria por simple
  visualizacion, permisos/auth, PWA/offline y `/api/sync`.
- Siguiente paso seguro: `5E-I-A` solo con autorizacion explicita para tocar ruta,
  controlador/modelo read-only, vista y checkers.

## 5E-I-A Recibo laboral informativo read-only

Estado formal:
`RECIBO_5E_I_A_LABORAL_INFORMATIVO_READ_ONLY_COMPLETADO_QA_MANUAL_PENDIENTE`.

- Documento creado:
  `docs/fase_5E_I_A_recibo_laboral_informativo.md`.
- Ruta nueva:
  `GET /trabajadores/{id}/recibo-laboral`.
- La pantalla reutiliza `Trabajador::nominaPreviewPorHotel()` para calcular un solo
  trabajador por periodo, con scope de hotel y sin duplicar reglas financieras.
- Se agrego enlace `Recibo` desde `/trabajadores/nomina/preview`, preservando
  `fecha_inicio`, `fecha_fin` e `incluir_pagos_caja`.
- La vista muestra avisos visibles `Solo lectura`, `No fiscal` y `No genera pago`.
- No hay POST, CSRF, pagos, reversiones, movimientos de Caja, recibos persistidos,
  migraciones, permisos/auth, PWA/offline ni `/api/sync`.
- Siguiente paso seguro: QA manual del recibo; si pasa, cierre documental 5E-I-F.

## 5E-I-F Cierre recibo laboral informativo

Estado formal:
`CIERRE_5E_I_F_RECIBO_LABORAL_INFORMATIVO_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_5E_I_F_cierre_recibo_laboral_informativo.md`.
- El usuario confirmo que la prueba manual del recibo laboral informativo paso
  correctamente.
- Queda cerrada la ruta GET/read-only
  `/trabajadores/{id}/recibo-laboral`.
- El recibo conserva scope por hotel, periodo requerido, calculos derivados de
  pre-nomina y avisos visibles `Solo lectura`, `No fiscal` y `No genera pago`.
- Validaciones automaticas registradas: preflight pagos laborales Caja `OK: 50`,
  `WARNING: 0`, `ERROR: 0`; health general `OK: 313`, `WARNING: 25`,
  `ERROR: 0`; ruta local sin sesion `303` a login, sin 404.
- No autoriza nomina automatica, recibos fiscales, PDF oficial, timbrado, CFDI,
  dispersion, pagos masivos, auditoria por visualizacion, permisos/auth,
  PWA/offline ni `/api/sync`.
- Siguiente paso seguro: contrato independiente antes de recibo descargable, PDF
  informativo, cierre formal de periodo, aprobacion de nomina o pago masivo.

## 5E-J-0 Contrato recibo laboral PDF informativo

Estado formal:
`CONTRATO_5E_J_0_RECIBO_LABORAL_PDF_INFORMATIVO_READ_ONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_J_0_contrato_recibo_laboral_pdf_informativo.md`.
- Es contrato documental; no agrega codigo, rutas, modelos, vistas, servicios PDF,
  migraciones, permisos, Caja, storage, datos ni `/api/sync`.
- Define una futura ruta GET candidata:
  `/trabajadores/{id}/recibo-laboral/pdf`.
- El PDF futuro debe ser informativo, generado bajo demanda, scoped por hotel y
  derivado del recibo laboral read-only.
- Debe mostrar etiqueta visible `PDF informativo / No fiscal / No genera pago`.
- Mantiene prohibidos PDF oficial, recibos fiscales, timbrado, CFDI, folios oficiales,
  dispersion, pagos masivos, storage por defecto, auditoria por descarga,
  permisos/auth, PWA/offline y `/api/sync`.
- Siguiente paso seguro: `5E-J-A` solo con autorizacion explicita para tocar ruta,
  controlador, renderer PDF/helper, vista, checkers o modelo read-only.

## 5E-J-A Recibo laboral PDF informativo

Estado formal:
`RECIBO_5E_J_A_LABORAL_PDF_INFORMATIVO_READ_ONLY_COMPLETADO_QA_MANUAL_PENDIENTE`.

- Documento creado:
  `docs/fase_5E_J_A_recibo_laboral_pdf_informativo.md`.
- Ruta nueva:
  `GET /trabajadores/{id}/recibo-laboral/pdf`.
- La accion reutiliza `Trabajador::reciboLaboralInformativoPorHotel()` y bloquea la
  descarga si falta periodo o hay calculo ambiguo.
- El renderer `TrabajadorReciboLaboralPdfService` usa TCPDF existente y genera el PDF
  en memoria con `Output(..., 'S')`.
- La vista del recibo HTML agrega enlace `PDF informativo` preservando filtros GET.
- No hay storage, reporte_links, correo, POST, Caja, pagos, reversiones, recibos
  persistidos, migraciones, permisos/auth, PWA/offline ni `/api/sync`.
- Siguiente paso seguro: QA manual de descarga PDF y luego cierre documental si pasa.

## 5E-K-0 Contrato cierre/aprobacion de pre-nomina

Estado formal:
`CONTRATO_5E_K_0_CIERRE_APROBACION_PRENOMINA_READ_ONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_K_0_contrato_cierre_aprobacion_prenomina.md`.
- Es contrato documental; no agrega codigo, rutas, controladores, modelos, vistas,
  servicios, migraciones, permisos, Caja, storage, datos, PWA/offline ni `/api/sync`.
- Define la separacion futura entre lectura read-only de periodos y acciones
  mutantes de cierre/aprobacion/anulacion.
- Las rutas POST candidatas no quedan implementadas ni autorizadas en esta fase.
- Mantiene prohibidos nomina oficial, CFDI, timbrado, folios oficiales, dispersion,
  pagos masivos, movimientos de Caja, liquidaciones automaticas, permisos/auth,
  PWA/offline y `/api/sync`.
- Siguiente paso seguro: `5E-K-A` solo con autorizacion explicita para tocar rutas,
  controlador, modelo, vista, checkers y, si aplica, servicio, migraciones, permisos
  o Caja.

## 5E-K-A Periodos de pre-nomina read-only

Estado formal:
`PERIODOS_5E_K_A_PRENOMINA_READ_ONLY_COMPLETADO_QA_MANUAL_PENDIENTE`.

- Documento creado:
  `docs/fase_5E_K_A_periodos_prenomina_read_only.md`.
- Rutas nuevas:
  `GET /trabajadores/nomina/periodos` y
  `GET /trabajadores/nomina/periodos/preview`.
- La vista nueva `trabajadores/nomina_periodos` lista periodos candidatos y muestra
  detalle read-only reutilizando `Trabajador::nominaPreviewPorHotel()`.
- Se agregaron enlaces desde Personal y desde el preview de pre-nomina.
- No hay POST, cierre real, aprobacion real, anulacion, snapshots persistidos, pagos,
  movimientos de Caja, migraciones, permisos/auth, PWA/offline ni `/api/sync`.
- Siguiente paso seguro: QA manual de periodos; si pasa, cierre documental 5E-K-F o
  contrato separado para cualquier cierre/aprobacion persistente.

## 5E-K-F Cierre periodos de pre-nomina read-only

Estado formal:
`CIERRE_5E_K_F_PERIODOS_PRENOMINA_READ_ONLY_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_5E_K_F_cierre_periodos_prenomina_read_only.md`.
- El usuario confirmo que la prueba manual de periodos de pre-nomina funciona a la
  perfeccion.
- Quedan cerradas como read-only las rutas:
  `GET /trabajadores/nomina/periodos` y
  `GET /trabajadores/nomina/periodos/preview`.
- Validaciones automaticas registradas: lint PHP OK, preflight pagos laborales Caja
  `OK: 54`, `WARNING: 0`, `ERROR: 0`; health general `OK: 313`, `WARNING: 25`,
  `ERROR: 0`; rutas locales sin sesion `303` a login, sin 404.
- No autoriza cierre real, aprobacion, anulacion, snapshots, nomina oficial, CFDI,
  timbrado, dispersion, pagos masivos, movimientos de Caja, migraciones,
  permisos/auth, PWA/offline ni `/api/sync`.
- Siguiente paso seguro: contrato independiente para cierre/aprobacion persistente de
  pre-nomina antes de cualquier POST, migracion o escritura real.

## 5E-L-0 Contrato cierre/aprobacion persistente de pre-nomina

Estado formal:
`CONTRATO_5E_L_0_CIERRE_APROBACION_PERSISTENTE_PRENOMINA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_L_0_contrato_cierre_aprobacion_persistente_prenomina.md`.
- Es contrato documental; no agrega codigo, rutas, controladores, modelos, vistas,
  servicios, migraciones, permisos, Caja, storage, datos, PWA/offline ni `/api/sync`.
- Define que un cierre futuro debe guardar un snapshot controlado del preview, sin
  pagar, timbrar, dispersar, liquidar anticipos/prestamos ni mover Caja.
- Propone rutas POST futuras solo como candidatas:
  `/trabajadores/nomina/periodos/cerrar`,
  `/trabajadores/nomina/periodos/{id}/aprobar` y
  `/trabajadores/nomina/periodos/{id}/anular`.
- Mantiene separados preview read-only, cierre persistente, aprobacion administrativa
  y pago laboral real con Caja.
- Siguiente paso seguro: `5E-L-A` solo con autorizacion explicita para rutas POST,
  servicio, modelo, vista, checkers, migraciones, permisos y backup.

## 5E-L-A Cierre/aprobacion persistente de pre-nomina

Estado formal:
`IMPLEMENTACION_5E_L_A_CIERRE_APROBACION_PERSISTENTE_PRENOMINA_LOCAL_QA_PENDIENTE`.

- Documento creado:
  `docs/fase_5E_L_A_cierre_aprobacion_persistente_prenomina.md`.
- Migracion nueva:
  `migrations/20260621_001_fase_5e_l_a_nomina_periodos_persistentes.sql`.
- Tablas nuevas:
  `trabajador_nomina_periodos`,
  `trabajador_nomina_periodo_detalles` y
  `trabajador_nomina_periodo_eventos`.
- Rutas nuevas:
  `GET /trabajadores/nomina/periodos/{id}`,
  `POST /trabajadores/nomina/periodos/cerrar`,
  `POST /trabajadores/nomina/periodos/{id}/aprobar` y
  `POST /trabajadores/nomina/periodos/{id}/anular`.
- Se agrego `TrabajadorNominaPeriodoService` para encapsular cierre, aprobacion y
  anulacion sin usar Caja.
- Se agrego `src/tools/saas/probar_nomina_periodo_snapshot.php` como prueba rollback
  local de cierre, duplicado, aprobacion y anulacion sin persistencia.
- El cierre guarda snapshot administrativo del preview; aprobar/anular solo cambian
  estado/evento del snapshot.
- No genera nomina oficial, CFDI, timbrado, dispersion, pago masivo, liquidaciones
  automaticas, movimientos de Caja, PWA/offline ni `/api/sync`.
- Siguiente paso seguro: aplicar migracion local con backup, correr checkers y QA
  manual de cierre/aprobacion/anulacion.

## 5E-L-B QA rollback local de snapshots de pre-nomina

Estado formal:
`QA_5E_L_B_ROLLBACK_SNAPSHOT_PRENOMINA_COMPLETADO_MANUAL_PENDIENTE`.

- Documento creado:
  `docs/fase_5E_L_B_qa_rollback_snapshot_prenomina.md`.
- Se ejecuto `tools/saas/probar_nomina_periodo_snapshot.php` en local.
- La prueba crea hotel, trabajador, concepto y snapshot temporales dentro de una
  transaccion externa, valida cierre, bloqueo de duplicado, aprobacion y anulacion
  con motivo, y revierte todo.
- Validaciones automaticas registradas: lint PHP OK; preflight pagos laborales Caja
  `OK: 60`, `WARNING: 0`, `ERROR: 0`; health general `OK: 316`, `WARNING: 25`,
  `ERROR: 0`.
- No deja datos persistidos, no crea pagos, no mueve Caja, no toca PWA/offline ni
  `/api/sync`.
- QA manual local completada posteriormente en 5E-L-F.

## 5E-L-F Cierre QA cierre/aprobacion persistente de pre-nomina

Estado formal:
`CIERRE_5E_L_F_CIERRE_APROBACION_PERSISTENTE_PRENOMINA_QA_LOCAL_VALIDADA`.

- Documento creado:
  `docs/fase_5E_L_F_cierre_cierre_aprobacion_persistente_prenomina.md`.
- QA local ejecutada por flujo HTTP real contra `http://localhost:8080` con sesion
  temporal de `adminmax` en hotel `Maximiliano` (`hotel_id=4`).
- Se cerro snapshot semanal `2026-06-15` a `2026-06-21`; resultado:
  `trabajador_nomina_periodos.id=2`.
- Se valido detalle en estado `Cerrado`, bloqueo visual de cierre duplicado como
  `Cerrado #2`, aprobacion administrativa, bloqueo de anulacion sin motivo y
  anulacion con motivo `QA manual 5E-L local`.
- Estado final local del snapshot: `anulado`, con eventos `cierre`, `aprobacion`
  y `anulacion`.
- Conteos sensibles sin cambio durante el flujo: `movimientos_caja=1413` y
  `trabajador_pagos_caja=1`.
- Validaciones: preflight pagos laborales Caja `OK: 60`, `WARNING: 0`, `ERROR: 0`;
  health general `OK: 316`, `WARNING: 25`, `ERROR: 0`; `/api/sync` con sesion
  activa sigue en HTTP `423` y `sync_temporarily_disabled`.
- No autoriza nomina oficial, CFDI, timbrado, dispersion, pago masivo,
  liquidaciones automaticas, movimientos de Caja desde pre-nomina, PWA/offline ni
  cambios en `/api/sync`.
- Siguiente paso seguro: contrato separado para export/listado de snapshots,
  reabrir snapshots o avanzar a recibos/liquidaciones solo con nueva autorizacion.

## 5E-M-0 Contrato reporte/export snapshots de pre-nomina

Estado formal:
`CONTRATO_5E_M_0_REPORTE_EXPORT_SNAPSHOTS_PRENOMINA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_M_0_contrato_reporte_snapshots_prenomina.md`.
- Es contrato documental; no agrega codigo, rutas, controladores, modelos, vistas,
  servicios, migraciones, permisos, datos, storage, Caja, PWA/offline ni
  `/api/sync`.
- Define rutas candidatas futuras, no implementadas:
  `GET /trabajadores/nomina/periodos/reporte` y
  `GET /trabajadores/nomina/periodos/exportar`.
- El alcance futuro permitido es reporte GET/read-only y CSV en memoria de
  snapshots persistentes 5E-L-A, con filtros por fechas, estado, tipo de periodo
  y texto libre.
- Mantiene prohibidos crear/recalcular snapshots, aprobar/anular/reabrir desde el
  reporte, pagos laborales, movimientos de Caja, cortes, liquidaciones,
  nomina oficial, CFDI, timbrado, dispersion, folios oficiales, storage, correo,
  links publicos, PWA/offline y `/api/sync`.
- Siguiente paso seguro: `5E-M-A` solo con autorizacion explicita para tocar
  rutas, controlador, modelo read-only, vista, export CSV, checkers y
  documentacion.

## 5E-M-A Reporte/export snapshots de pre-nomina

Estado formal:
`IMPLEMENTACION_5E_M_A_REPORTE_EXPORT_SNAPSHOTS_PRENOMINA_QA_MANUAL_VALIDADA_EN_5E_M_F`.

- Documento creado:
  `docs/fase_5E_M_A_reporte_export_snapshots_prenomina.md`.
- Rutas nuevas:
  `GET /trabajadores/nomina/periodos/reporte` y
  `GET /trabajadores/nomina/periodos/exportar`.
- Se agrego vista `trabajadores/nomina_periodos_reporte` con filtros GET por
  fecha, estado, tipo de periodo y busqueda libre.
- Se agrego modelo read-only para consultar snapshots persistentes por `hotel_id`
  y sumar importes ya congelados.
- Se agrego export CSV en memoria, sin storage ni archivos temporales.
- Se enlazo el reporte desde `GET /trabajadores/nomina/periodos`.
- No agrega migraciones, datos, pagos laborales, movimientos de Caja, cortes,
  liquidaciones, nomina oficial, CFDI, timbrado, dispersion, PWA/offline ni
  cambios en `/api/sync`.
- QA tecnica local completada en 5E-M-B.
- QA manual validada por el usuario y cierre documental aplicado en 5E-M-F.

## 5E-M-B QA tecnica reporte/export snapshots de pre-nomina

Estado formal:
`QA_5E_M_B_REPORTE_EXPORT_SNAPSHOTS_PRENOMINA_MANUAL_VALIDADA_EN_5E_M_F`.

- Documento creado:
  `docs/fase_5E_M_B_qa_tecnica_reporte_export_snapshots_prenomina.md`.
- QA HTTP local ejecutada contra `http://localhost:8080` con sesion temporal de
  `adminmax` (`usuario_id=24`) en hotel `Maximiliano` (`hotel_id=4`).
- `GET /trabajadores/nomina/periodos/reporte` con sesion activa respondio
  HTTP `200`, mostro enlace de exportacion y enlace al detalle del snapshot.
- `GET /trabajadores/nomina/periodos/exportar?estado=anulado&tipo_periodo=semanal`
  respondio HTTP `200` y CSV en memoria con snapshot local `#2`.
- Rutas sin sesion respondieron HTTP `303` a `/login`.
- Conteos sensibles sin cambio: `movimientos_caja=1413`,
  `trabajador_pagos_caja=1`, `trabajador_nomina_periodos=1`,
  `trabajador_nomina_periodo_detalles=1` y
  `trabajador_nomina_periodo_eventos=3`.
- `/api/sync` con sesion activa sigue en HTTP `423` y
  `sync_temporarily_disabled`.
- Validaciones: lint PHP OK; preflight pagos laborales Caja `OK: 62`,
  `WARNING: 0`, `ERROR: 0`; health general `OK: 317`, `WARNING: 25`,
  `ERROR: 0`.
- No autoriza nomina oficial, CFDI, timbrado, dispersion, pago masivo,
  liquidaciones automaticas, movimientos de Caja, storage, PWA/offline ni
  cambios en `/api/sync`.
- QA manual validada por el usuario y cierre documental aplicado en 5E-M-F.

## 5E-M-F Cierre reporte/export snapshots de pre-nomina

Estado formal:
`CIERRE_5E_M_F_REPORTE_EXPORT_SNAPSHOTS_PRENOMINA_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_5E_M_F_cierre_reporte_export_snapshots_prenomina.md`.
- El usuario confirmo que la prueba manual del reporte/export de snapshots de
  pre-nomina paso correctamente.
- Quedan cerradas como GET/read-only:
  `GET /trabajadores/nomina/periodos/reporte` y
  `GET /trabajadores/nomina/periodos/exportar`.
- Evidencia tecnica conservada: reporte HTTP `200`, export CSV HTTP `200`,
  rutas sin sesion `303` a login, conteos sensibles sin cambio y `/api/sync`
  HTTP `423`.
- No autoriza nomina oficial, CFDI, timbrado, dispersion, pago masivo,
  liquidaciones automaticas, movimientos de Caja desde pre-nomina, storage,
  PWA/offline ni cambios en `/api/sync`.
- Siguiente paso seguro: contrato independiente antes de implementar pago
  controlado desde snapshot, reapertura de snapshots, liquidaciones automaticas
  o cualquier accion no read-only.

## 5E-N-0 Contrato pago desde snapshot de pre-nomina con Caja

Estado formal:
`CONTRATO_5E_N_0_PAGO_SNAPSHOT_PRENOMINA_CAJA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_N_0_contrato_pago_snapshot_prenomina_caja.md`.
- Es contrato documental; no agrega codigo, rutas, modelos, servicios, vistas,
  migraciones, permisos, datos, storage, Caja, PWA/offline ni `/api/sync`.
- Define un futuro pago individual controlado desde snapshot aprobado, nunca
  pago masivo ni nomina oficial.
- Regla central: el snapshot solo da contexto y tope; el saldo real debe
  recalcularse en vivo con Caja. Monto maximo futuro:
  `min(pendiente_pago_sugerido_snapshot, saldo_laboral_disponible_vivo)`.
- Recomienda trazabilidad fuerte opcional con migracion futura nullable en
  `trabajador_pagos_caja` para `nomina_periodo_id` y
  `nomina_periodo_detalle_id`; no queda autorizada por este contrato.
- Mantiene inmutable el snapshot: no recalcula totales, no cambia estado y no
  modifica detalles por pagar.
- No autoriza pago masivo, liquidacion automatica de anticipos/prestamos, CFDI,
  timbrado, dispersion, movimientos de Caja desde pre-nomina sin fase posterior,
  storage, PWA/offline ni cambios en `/api/sync`.
- Siguiente paso seguro: 5E-N-A solo con autorizacion explicita para rutas,
  controlador, servicio/modelo, vista, Caja, auditoria, checkers, prueba rollback
  y, si se decide trazabilidad fuerte, migracion con backup.

## 5E-N-A Pago individual desde snapshot de pre-nomina con Caja

Estado formal:
`IMPLEMENTACION_5E_N_A_PAGO_SNAPSHOT_PRENOMINA_CAJA_QA_MANUAL_VALIDADA_EN_5E_N_F`.

- Documento creado:
  `docs/fase_5E_N_A_pago_snapshot_prenomina_caja.md`.
- Implementacion local; no se subio a produccion.
- Ruta nueva:
  `POST /trabajadores/nomina/periodos/{periodo}/detalles/{detalle}/registrar-pago-caja`.
- Se agrego `TrabajadorNominaSnapshotPagoService` para validar snapshot,
  detalle, trabajador, saldo vivo y Caja antes de delegar el pago al servicio
  transaccional existente.
- Se agrego accion `registrarPagoSnapshotNominaAction` con permiso, modulo Caja,
  CSRF y token de un solo uso `pago_snapshot_caja`.
- La vista del detalle de snapshot aprobado muestra pago individual por fila
  solo cuando el servicio marca elegible al trabajador.
- Tope de pago:
  `min(pendiente_pago_sugerido_snapshot, saldo_laboral_disponible_vivo)`.
- El snapshot queda inmutable; no se recalculan totales, detalles ni estado.
- No agrega migracion ni columnas nuevas en `trabajador_pagos_caja`.
- QA tecnica local:
  rollback 5E-N-A OK sin persistencia; preflight pagos laborales Caja
  `OK: 65`, `WARNING: 0`, `ERROR: 0`; health general `OK: 320`,
  `WARNING: 25`, `ERROR: 0`.
- QA manual validada por el usuario y cierre documental aplicado en 5E-N-F.
- No autoriza pago masivo, liquidaciones automaticas, nomina oficial, CFDI,
  timbrado, dispersion, storage, PWA/offline ni cambios en `/api/sync`.

## 5E-N-F Cierre pago desde snapshot de pre-nomina con Caja

Estado formal:
`CIERRE_5E_N_F_PAGO_SNAPSHOT_PRENOMINA_CAJA_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_5E_N_F_cierre_pago_snapshot_prenomina_caja.md`.
- El usuario confirmo que la prueba manual del pago individual desde snapshot
  aprobado paso correctamente.
- Evidencia manual:
  snapshot `#4` aprobado, pago individual `$1.00`, movimiento Caja `#1507`,
  referencia `TEST-5ENA-001` y saldo vivo del trabajador en `$99.00`.
- Evidencia tecnica posterior:
  `trabajador_pagos_caja.id = 6`, `movimientos_caja.id = 1507`,
  snapshot `#4` sigue `aprobado` e inmutable con `pendiente_pago_total=100.00`.
- Queda cerrado localmente el flujo individual. No se subio a produccion.
- No autoriza migraciones, pago masivo, nomina oficial, CFDI, timbrado,
  dispersion, liquidaciones automaticas, storage, PWA/offline ni cambios en
  `/api/sync`.

## 5E-O-A UX filtros de periodos de pre-nomina

Estado formal:
`IMPLEMENTACION_5E_O_A_UX_FILTROS_PERIODOS_PRENOMINA_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_O_A_ux_filtros_periodos_prenomina.md`.
- Implementacion local; no se subio a produccion.
- Se mejoro la vista `src/app/views/trabajadores/nomina_periodos.php` para
  distinguir visualmente `Fecha base`, `Inicio manual` y `Fin manual`.
- El formulario conserva `GET`, `action`, `name`, hidden inputs, checkbox de
  Caja y submit dentro del mismo form.
- No cambia calculos, validaciones, rutas, controladores, modelos, permisos,
  migraciones, datos, Caja, storage, PWA/offline ni `/api/sync`.
- QA tecnica local:
  lint PHP OK; preflight pagos laborales Caja `OK: 65`, `WARNING: 0`,
  `ERROR: 0`; health general `OK: 320`, `WARNING: 25`, `ERROR: 0`.

## 5E-O-B UX lectura de pago desde snapshot de pre-nomina

Estado formal:
`IMPLEMENTACION_5E_O_B_UX_LECTURA_PAGO_SNAPSHOT_PRENOMINA_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_O_B_ux_lectura_pago_snapshot_prenomina.md`.
- Implementacion local; no se subio a produccion.
- Se mejoro la vista `src/app/views/trabajadores/nomina_periodo_detalle.php`
  para mostrar en `Pago Caja` tres importes separados: `Snapshot`,
  `Saldo vivo` y `Maximo`.
- La mejora aclara por que el pendiente congelado del snapshot puede diferir
  del maximo vivo disponible despues de pagos parciales o reversiones.
- El formulario conserva `POST`, `action`, `name`, CSRF, token de un solo uso y
  submit dentro del mismo form.
- No cambia reglas de pago, elegibilidad, Caja, transacciones, auditoria,
  rutas, controladores, modelos, servicios, permisos, migraciones, datos,
  storage, PWA/offline ni `/api/sync`.
- QA tecnica local:
  lint PHP OK; preflight pagos laborales Caja `OK: 65`, `WARNING: 0`,
  `ERROR: 0`; health general `OK: 320`, `WARNING: 25`, `ERROR: 0`.

## 5E-P-0 Contrato trazabilidad fuerte de pago desde snapshot

Estado formal:
`CONTRATO_5E_P_0_TRAZABILIDAD_PAGO_SNAPSHOT_PRENOMINA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_P_0_contrato_trazabilidad_pago_snapshot_prenomina.md`.
- Es contrato documental; no agrega codigo, rutas, modelos, servicios, vistas,
  migraciones, permisos, datos, Caja, storage, PWA/offline ni `/api/sync`.
- Define una futura migracion aditiva y nullable en `trabajador_pagos_caja`:
  `nomina_periodo_id` y `nomina_periodo_detalle_id`.
- Prohibe backfill historico por defecto; pagos existentes deben quedar `NULL`
  si una futura migracion se autoriza.
- Requiere validaciones de `hotel_id`, detalle perteneciente al periodo,
  trabajador coincidente y snapshot aprobado.
- No autoriza nomina oficial, CFDI, timbrado, dispersion, pago masivo,
  liquidaciones automaticas, reapertura de snapshots ni cambios en `/api/sync`.
- Siguiente paso seguro: 5E-P-A solo con autorizacion explicita para migracion,
  DB, modelo/servicio, checkers, backup y prueba rollback.

## 5E-P-B Guardas read-only de trazabilidad pago snapshot

Estado formal:
`GUARDAS_5E_P_B_TRAZABILIDAD_PAGO_SNAPSHOT_READONLY_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_P_B_guardas_trazabilidad_pago_snapshot.md`.
- Implementacion local; no se subio a produccion.
- Se agregaron validaciones read-only en:
  `src/tools/saas/preflight_personal_pagos_caja.php` y
  `src/tools/saas/health_check_fase_1a.php`.
- Si la trazabilidad fuerte aun no existe, los checkers lo reportan como OK.
- Si una futura migracion agrega solo una columna, falta indices o crea
  relaciones cruzadas de hotel/periodo/detalle/trabajador, los checkers marcan
  error.
- No crea migraciones, no altera DB, no toca rutas, controladores, modelos,
  servicios, vistas, Caja, snapshots, storage, PWA/offline ni `/api/sync`.
- QA tecnica local:
  lint PHP OK; preflight pagos laborales Caja `OK: 66`, `WARNING: 0`,
  `ERROR: 0`; health general `OK: 321`, `WARNING: 25`, `ERROR: 0`.

## 5E-P-A Trazabilidad fuerte de pago snapshot

Estado formal:
`IMPLEMENTACION_5E_P_A_TRAZABILIDAD_PAGO_SNAPSHOT_PRENOMINA_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_P_A_trazabilidad_pago_snapshot_prenomina.md`.
- Implementacion local; no se subio a produccion.
- Backup local previo:
  `backups/db/20260622_102558_medisoft_hoteles_import_pre_5e_p_a.sql`.
- Migracion local aplicada:
  `migrations/20260622_001_fase_5e_p_a_trazabilidad_pago_snapshot.sql`.
- La migracion agrega en `trabajador_pagos_caja`, de forma nullable:
  `nomina_periodo_id` y `nomina_periodo_detalle_id`.
- Se agregan indices y FKs restrictivas hacia
  `trabajador_nomina_periodos` y `trabajador_nomina_periodo_detalles`.
- `TrabajadorPagoCajaService` valida trazabilidad opcional por hotel, periodo,
  detalle, trabajador, snapshot aprobado y detalle `por_pagar`.
- `TrabajadorNominaSnapshotPagoService` envia los IDs al pago individual desde
  snapshot aprobado.
- La prueba rollback valida que el pago temporal queda ligado al periodo y
  detalle correctos antes de revertir todo.
- No hace backfill historico, no recalcula snapshots, no toca nomina oficial,
  CFDI, timbrado, dispersion, pago masivo, PWA/offline ni `/api/sync`.
- QA tecnica local:
  lint PHP OK; rollback pago snapshot OK; preflight pagos laborales Caja
  `OK: 72`, `WARNING: 0`, `ERROR: 0`; health general `OK: 327`,
  `WARNING: 25`, `ERROR: 0`.

## 5E-P-F Cierre trazabilidad fuerte de pago snapshot

Estado formal:
`CIERRE_5E_P_F_TRAZABILIDAD_PAGO_SNAPSHOT_PRENOMINA_QA_MANUAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_P_F_cierre_trazabilidad_pago_snapshot_prenomina.md`.
- Implementacion local validada manualmente; no se subio a produccion.
- El usuario confirmo que la prueba manual paso.
- Pago validado:
  `trabajador_pagos_caja.id = 9`, referencia `TEST-5EPA-001`,
  `nomina_periodo_id = 4`, `nomina_periodo_detalle_id = 4`.
- Movimiento Caja validado:
  `movimientos_caja.id = 1510`, categoria `Pago laboral`, corte `#238`.
- Consistencia posterior:
  relaciones parciales `0`, pagos trazados `1`.
- No hubo backfill historico, nomina oficial, CFDI, timbrado, dispersion, pago
  masivo, PWA/offline ni cambios en `/api/sync`.

## 5E-Q-A Conciliacion read-only pagos snapshot

Estado formal:
`IMPLEMENTACION_5E_Q_A_CONCILIACION_PAGOS_SNAPSHOT_PRENOMINA_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_Q_A_conciliacion_pagos_snapshot_prenomina.md`.
- Implementacion local; no se subio a produccion.
- Agrega reporte GET/read-only:
  `/trabajadores/nomina/periodos/pagos-snapshot`.
- Agrega export CSV GET/read-only:
  `/trabajadores/nomina/periodos/pagos-snapshot/exportar`.
- Cruza pago laboral, snapshot, detalle congelado, corte, caja y movimiento de
  Caja para marcar cada fila como `OK` o `Revisar`.
- Pago validado en consulta local:
  `trabajador_pagos_caja.id = 9`, snapshot `#4`, detalle `#4`,
  movimiento Caja `#1510`, conciliacion `ok`.
- No crea migraciones, no agrega POST, no escribe storage, no toca produccion,
  PWA/offline ni `/api/sync`.
- QA tecnica local:
  lint PHP OK; preflight pagos laborales Caja `OK: 74`, `WARNING: 0`,
  `ERROR: 0`; health general `OK: 328`, `WARNING: 25`, `ERROR: 0`.

## 5E-Q-F Cierre conciliacion pagos snapshot

Estado formal:
`CIERRE_5E_Q_F_CONCILIACION_PAGOS_SNAPSHOT_PRENOMINA_QA_MANUAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_Q_F_cierre_conciliacion_pagos_snapshot_prenomina.md`.
- QA manual completada en local.
- El usuario confirmo que la pantalla funciona y muestra lo esperado.
- Pantalla validada:
  `/trabajadores/nomina/periodos/pagos-snapshot?periodo_id=4`.
- Dato validado visible: pago `#9`, referencia `TEST-5EPA-001`,
  snapshot `#4`, detalle `#4`, movimiento Caja `#1510`, conciliacion `OK`.
- No se toco produccion, no se agregaron migraciones, no se agregaron POST,
  no se escribio storage y no se toco PWA/offline ni `/api/sync`.

## 5E-R-0 Contrato auditoria consolidada de nomina

Estado formal:
`CONTRATO_5E_R_0_AUDITORIA_CONSOLIDADA_NOMINA_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_R_0_contrato_auditoria_consolidada_nomina.md`.
- Fase documental; no agrega codigo, rutas, modelos, vistas, migraciones,
  permisos, datos, storage, Caja, PWA/offline ni `/api/sync`.
- Define una futura pantalla GET/read-only para consolidar snapshot,
  trabajador, pagos Caja, reversiones, movimientos, cortes, referencias,
  auditoria y conciliacion.
- Mantiene separadas las lecturas de snapshot administrativo, saldo vivo, pago
  real con Caja, reversion y conciliacion.
- Prohibe pago masivo, nomina oficial, CFDI, timbrado, dispersion, backfill,
  recalculos y cualquier POST.
- Siguiente paso seguro: 5E-R-A solo con autorizacion explicita para tocar
  rutas GET, controlador, modelo read-only, vista, exportador CSV y checkers.

## 5E-R-A Auditoria consolidada de nomina read-only

Estado formal:
`IMPLEMENTACION_5E_R_A_AUDITORIA_CONSOLIDADA_NOMINA_READONLY_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_R_A_auditoria_consolidada_nomina.md`.
- Implementacion local; no se subio a produccion.
- Agrega auditoria GET/read-only:
  `/trabajadores/nomina/auditoria`.
- Agrega export CSV GET/read-only:
  `/trabajadores/nomina/auditoria/exportar`.
- Consolida una fila por detalle de snapshot y agrega pagos Caja vinculados por
  `nomina_periodo_id`, `nomina_periodo_detalle_id` y `hotel_id`.
- Calcula estado de auditoria: `Liquidado`, `Parcial`, `Sin pago` o `Revisar`.
- Caso local validado por modelo: `hotel_id = 4`, `periodo_id = 4`,
  trabajador `Panfilo Hernandez`, pagos Caja `$1.00`, saldo auditoria `$99.00`,
  estado `parcial`.
- `preflight_personal_pagos_caja.php`: `OK: 76`, `WARNING: 0`, `ERROR: 0`.
- `health_check_fase_1a.php`: 5E-R-A OK; el resultado general mantiene
  `ERROR: 3` historicos de Compras/CxP fuera de esta fase.
- No crea migraciones, no agrega POST, no escribe storage, no toca produccion,
  PWA/offline ni `/api/sync`.

## 5E-R-F Cierre auditoria consolidada de nomina

Estado formal:
`CIERRE_5E_R_F_AUDITORIA_CONSOLIDADA_NOMINA_QA_MANUAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_R_F_cierre_auditoria_consolidada_nomina.md`.
- QA manual completada en local.
- El usuario confirmo que la pantalla funciona y paso las pruebas.
- Pantalla validada:
  `/trabajadores/nomina/auditoria?periodo_id=4`.
- Datos validados visibles: `1` detalle, `1` trabajador, pagos Caja `$1.00`,
  saldo auditoria `$99.00`, estado `Parcial`, trabajador `Panfilo Hernandez`,
  periodo `#4`.
- El cierre mantiene la auditoria como GET/read-only: no registra pagos, no
  revierte pagos, no modifica Caja, no modifica snapshots y no genera nomina
  oficial.
- No se toco produccion, migraciones, storage, PWA/offline ni `/api/sync`.

## 5E-S-0 Contrato expediente administrativo de nomina

Estado formal:
`CONTRATO_5E_S_0_EXPEDIENTE_ADMINISTRATIVO_NOMINA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_S_0_contrato_expediente_administrativo_nomina.md`.
- Fase documental; no agrega codigo, rutas, modelos, vistas, migraciones,
  permisos, datos, storage, Caja, PWA/offline ni `/api/sync`.
- Define una futura capa GET/read-only para agrupar evidencias de pre-nomina,
  snapshots aprobados, pagos Caja, reversiones, auditoria consolidada,
  documentos y bloqueos administrativos.
- Mantiene claro que el expediente no es nomina oficial, CFDI, timbrado,
  dispersion, pago masivo, poliza contable ni liquidacion automatica.
- Una futura 5E-S-A podria implementar rutas GET/read-only y CSV en memoria,
  siempre con autorizacion explicita.
- No autoriza POST, pagos, reversiones, modificaciones de Caja/cortes,
  snapshots, migraciones, backfill, storage ni cambios PWA/offline.

## 5E-S-A Expediente administrativo de nomina read-only

Estado formal:
`IMPLEMENTACION_5E_S_A_EXPEDIENTE_ADMINISTRATIVO_NOMINA_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_S_A_expediente_administrativo_nomina.md`.
- Implementacion local; no se subio a produccion.
- Agrega expediente GET/read-only:
  `/trabajadores/nomina/expediente`.
- Agrega export CSV GET/read-only:
  `/trabajadores/nomina/expediente/exportar`.
- Reutiliza auditoria consolidada 5E-R-A y clasifica cada detalle como
  `listo_revision`, `con_pendientes`, `requiere_correccion`, `bloqueado` o
  `anulado`.
- Caso local validado por modelo: `hotel_id = 4`, `periodo_id = 4`,
  trabajador `Panfilo Hernandez`, estado expediente `con_pendientes`, pagos
  Caja `$1.00`, saldo auditoria `$99.00`, bloqueos `0`.
- `preflight_personal_pagos_caja.php`: `OK: 78`, `WARNING: 0`, `ERROR: 0`.
- `health_check_fase_1a.php`: 5E-S-A OK; el resultado general mantiene
  `ERROR: 3` historicos de Compras/CxP fuera de esta fase.
- No crea migraciones, no agrega POST, no escribe storage, no toca produccion,
  PWA/offline ni `/api/sync`.

## 5E-T-0 Contrato frontera de nomina oficial

Estado formal:
`CONTRATO_5E_T_0_FRONTERA_NOMINA_OFICIAL_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_T_0_contrato_frontera_nomina_oficial.md`.
- Fase documental; no agrega codigo, rutas, modelos, vistas, migraciones,
  permisos, datos, storage, Caja, PWA/offline ni `/api/sync`.
- Define la frontera entre nomina administrativa 5E y cualquier futura nomina
  oficial.
- Mantiene fuera de alcance CFDI laboral, timbrado, dispersion bancaria, pago
  masivo, recibos fiscales, UUID fiscal laboral y polizas contables oficiales.
- Siguiente paso seguro: 5E-T-A preflight CLI/read-only de frontera.

## 5E-T-A Preflight frontera de nomina oficial

Estado formal:
`IMPLEMENTACION_5E_T_A_PREFLIGHT_FRONTERA_NOMINA_OFICIAL_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_T_A_preflight_frontera_nomina_oficial.md`.
- Herramienta nueva:
  `src/tools/saas/preflight_frontera_nomina_oficial.php`.
- Verifica que no existan rutas, simbolos activos, migraciones montadas u
  objetos DB locales que parezcan nomina oficial, CFDI laboral, timbrado,
  dispersion o pago masivo.
- Confirma que `/api/sync` conserva `sync_temporarily_disabled` y HTTP 423.
- QA tecnica local: `php -l` OK; preflight `OK: 10`, `WARNING: 0`,
  `ERROR: 0`.
- No crea migraciones, no agrega POST, no escribe storage, no toca produccion,
  PWA/offline ni `/api/sync`.

## 5E-T-B Health frontera de nomina oficial

Estado formal:
`IMPLEMENTACION_5E_T_B_HEALTH_FRONTERA_NOMINA_OFICIAL_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_T_B_health_frontera_nomina_oficial.md`.
- Integra el preflight 5E-T-A al health general.
- `health_check_fase_1a.php` ahora valida que exista
  `preflight_frontera_nomina_oficial.php` y que declare guardas de rutas,
  simbolos Personal/Nomina, DB read-only, `/api/sync`, CFDI, timbrado,
  dispersion y pago masivo.
- QA tecnica local: `php -l` OK; health reconoce 5E-T-A como OK.
- El health global conserva `ERROR: 3` historicos de Compras/CxP fuera de esta
  fase.
- No crea migraciones, no agrega POST, no escribe storage, no toca produccion,
  PWA/offline ni `/api/sync`.

## 5E-U-0 Contrato suite QA nomina administrativa

Estado formal:
`CONTRATO_5E_U_0_SUITE_QA_NOMINA_ADMINISTRATIVA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_U_0_contrato_suite_qa_nomina_administrativa.md`.
- Fase documental; no agrega codigo, rutas, modelos, vistas, migraciones,
  permisos, datos, storage, Caja, PWA/offline ni `/api/sync`.
- Define una futura suite CLI/local/read-only para consolidar preflights del
  bloque administrativo de Personal/Nomina.
- Mantiene como bloqueantes los controles actuales de pagos laborales,
  snapshots, auditoria, expediente y frontera de nomina oficial.
- Permite tratar preflights historicos como lectura informativa cuando fueron
  superados por fases posteriores autorizadas.

## 5E-U-A Suite QA nomina administrativa

Estado formal:
`IMPLEMENTACION_5E_U_A_SUITE_QA_NOMINA_ADMINISTRATIVA_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_U_A_suite_qa_nomina_administrativa.md`.
- Herramienta nueva:
  `src/tools/saas/preflight_nomina_administrativa_suite.php`.
- Ejecuta como bloqueantes:
  - `preflight_personal_pagos_caja.php`;
  - `preflight_frontera_nomina_oficial.php`;
  - validacion estatica de health 5E-T-B y `/api/sync`.
- Ejecuta `preflight_personal_ledger.php` como historico no bloqueante porque
  su contrato original es anterior a rutas 5E ya autorizadas.
- QA tecnica local: `php -l` OK; suite `OK: 3`, `WARNING: 1`, `ERROR: 0`,
  `PASS_WITH_WARNINGS_ALLOWED`.
- No crea migraciones, no agrega POST, no escribe storage, no toca produccion,
  PWA/offline ni `/api/sync`.

## 5E-U-B Health baseline Compras/CxP

Estado formal:
`IMPLEMENTACION_5E_U_B_HEALTH_BASELINE_COMPRAS_CXP_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_U_B_health_baseline_compras_cxp.md`.
- Ajusta `health_check_fase_1a.php` para reconocer las marcas actuales de
  vistas Compras/CxP tras el redisenio visual.
- Los 3 errores historicos pasan a OK:
  - vistas Compras Fase 2Y;
  - vistas CxP Fase 3D/3D-D-A;
  - simulador Caja CxP Fase 3D-A.
- QA tecnica local: `php -l` OK; health `OK: 328`, `WARNING: 28`,
  `ERROR: 0`, `PASS_WITH_WARNINGS_ALLOWED`.
- No modifica vistas, rutas, modelos, servicios, migraciones, Caja,
  produccion, PWA/offline ni `/api/sync`.

## 3A-B Health baseline Proveedores

Estado formal:
`IMPLEMENTACION_3A_B_HEALTH_BASELINE_PROVEEDORES_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_3A_B_health_baseline_proveedores.md`.
- Ajusta `health_check_fase_1a.php` para reconocer las marcas actuales de la
  ficha de Proveedores tras el redisenio visual.
- El warning de vistas Proveedores Fase 3A pasa a OK:
  `Vistas de Proveedores Fase 3A enlazan ficha read-only e historial sin formularios nuevos.`
- No modifica vistas, rutas, modelos, servicios, migraciones, Caja, produccion,
  PWA/offline ni `/api/sync`.

## NP-F-B Health baseline Personal

Estado formal:
`IMPLEMENTACION_NP_F_B_HEALTH_BASELINE_PERSONAL_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_NP_F_B_health_baseline_personal.md`.
- Ajusta `health_check_fase_1a.php` para reconocer las marcas actuales del
  listado de Personal tras el redisenio visual.
- El warning de vistas Personal NP-F-A/5E-D-A pasa a OK:
  `Vistas Personal NP-F-A/5E-D-A muestran CRUD, reporte, ledger manual y panel de pago laboral con CSRF/token.`
- QA tecnica local: `php -l` OK; health `OK: 329`, `WARNING: 27`,
  `ERROR: 0`, `PASS_WITH_WARNINGS_ALLOWED`.
- No modifica vistas, rutas, modelos, servicios, migraciones, Caja, nomina
  oficial, produccion, PWA/offline ni `/api/sync`.

## TLM-I/J-B Health baseline Tareas

Estado formal:
`IMPLEMENTACION_TLM_I_J_B_HEALTH_BASELINE_TAREAS_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_TLM_I_J_B_health_baseline_tareas.md`.
- Ajusta `health_check_fase_1a.php` para reconocer las marcas actuales de las
  vistas de Tareas tras el redisenio visual.
- El warning de vistas TLM-I-A/TLM-J-A pasa a OK:
  `Vistas TLM-I-A/TLM-J-A muestran filtros GET, reporte/agenda read-only, alta/asignacion/estados manuales con CSRF sin Caja.`
- QA tecnica local: `php -l` OK; health `OK: 330`, `WARNING: 26`,
  `ERROR: 0`, `PASS_WITH_WARNINGS_ALLOWED`.
- No modifica vistas, rutas, modelos, servicios, migraciones, Caja,
  habitaciones, produccion, PWA/offline ni `/api/sync`.

## 9C-C Diagnostico read-only de arqueo de cortes

Estado formal:
`IMPLEMENTACION_9C_C_DIAGNOSTICO_ARQUEO_CORTES_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_9C_C_diagnostico_arqueo_cortes.md`.
- Herramienta nueva:
  `src/tools/saas/diagnosticar_arqueo_cortes.php`.
- Ajusta `preflight_arqueo_metodos_pago.php` para reportar cortes
  descuadrados con movimientos posteriores al cierre.
- Diagnostico local identifica cortes `#15`, `#21`, `#48` y `#149` como
  origen de diferencias historicas de arqueo.
- QA tecnica local: lint OK; diagnostico `OK: 34`, `WARNING: 4`, `ERROR: 0`;
  preflight 9C `OK: 39`, `WARNING: 3`, `ERROR: 0`.
- No modifica rutas, controladores, modelos operativos, servicios de Caja,
  vistas, migraciones, base de datos, produccion, PWA/offline ni `/api/sync`.

## 10A-C Diagnostico fuentes Tablero Ejecutivo

Estado formal:
`IMPLEMENTACION_10A_C_DIAGNOSTICO_FUENTES_TABLERO_EJECUTIVO_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_10A_C_diagnostico_fuentes_tablero_ejecutivo.md`.
- Herramienta nueva:
  `src/tools/saas/diagnosticar_tablero_ejecutivo_fuentes.php`.
- Ajusta `preflight_tablero_ejecutivo.php` para clasificar auditoria
  global/auth sin `hotel_id` de forma explicita.
- Diagnostico local confirma que `ledger_laboral` es opcional y que existen
  fuentes laborales alternativas con `hotel_id` sano.
- Diagnostico local confirma que las 40 filas de `logs_auditoria` sin
  `hotel_id` son `auth.*` globales, no auditoria operativa de hotel.
- QA tecnica local: lint OK; diagnostico `OK: 19`, `WARNING: 2`, `ERROR: 0`;
  preflight tablero `OK: 92`, `WARNING: 2`, `ERROR: 0`.
- No modifica rutas, controladores, modelos operativos, vistas, migraciones,
  base de datos, auth operativo, permisos, Caja, produccion, PWA/offline ni
  `/api/sync`.

## NP-F-C Health baseline Personal detalle redisenado

Estado formal:
`IMPLEMENTACION_NP_F_C_HEALTH_BASELINE_PERSONAL_DETALLE_QA_TECNICA_LOCAL_COMPLETADA`.

- Documento creado:
  `docs/fase_NP_F_C_health_baseline_personal_detalle.md`.
- Ajusta `health_check_fase_1a.php` para reconocer las marcas actuales de
  `trabajadores/ver.php` tras el redisenio visual.
- El warning restante del health general pasa a OK:
  `Vistas Personal NP-F-A/5E-D-A muestran CRUD, reporte, ledger manual y panel de pago laboral con CSRF/token.`
- QA tecnica local: lint OK; health general `OK: 413`, `WARNING: 0`,
  `ERROR: 0`, `PASS_WITH_WARNINGS_ALLOWED`.
- No modifica vistas, rutas, controladores, modelos, servicios, migraciones,
  base de datos, Caja, nomina oficial, produccion, PWA/offline ni `/api/sync`.

## Cierre tecnico de bloque - estabilidad local 2026-06-23

Estado formal:
`CIERRE_TECNICO_BLOQUE_ESTABILIDAD_LOCAL_QA_TECNICA_COMPLETADA`.

- Documento creado:
  `docs/cierre_tecnico_bloque_estabilidad_local_20260623.md`.
- Consolida el estado estable local de inventario, proveedores, compras, CxP,
  CxC, documentos, tareas, mantenimiento, limpieza, Personal, nomina
  administrativa, Caja, reportes y tableros.
- Health general queda limpio: `OK: 413`, `WARNING: 0`, `ERROR: 0`.
- Suite nomina administrativa queda limpia: `OK: 4`, `WARNING: 0`,
  `ERROR: 0`.
- Warnings aceptados documentados:
  - tablero ejecutivo: `ledger_laboral` opcional;
  - tablero ejecutivo: `auth.*` global sin `hotel_id`;
  - arqueo: cortes historicos/locales con diferencias diagnosticadas.
- Define superficies listas para QA manual/redisenio visual sin cambiar
  contratos de formularios.
- No modifica codigo operativo, vistas, rutas, controladores, modelos,
  servicios, migraciones, base de datos, produccion, PWA/offline ni `/api/sync`.
