# Auditoria de seguridad - cola autonoma

## Alcance

Auditoria post-commit del bloque autorizado hasta Fase 3C:

- Fase 2X: detalle read-only de compra recibida.
- Fase 2Y: reportes read-only de compras recibidas.
- Fase 2Z: endurecimiento de recepcion minima.
- Fase 3A: ficha read-only de proveedor con historial.
- Fase 3B: cuentas por pagar base read-only.
- Fase 3C-A: simulador read-only de CxP generable desde compras recibidas.
- Fase 3C-B: generacion manual controlada de CxP desde compra recibida.
- Fase 3C-C: validaciones de consistencia CxP en health/preflights.

## Reanclaje Fase 3C

Estado vigente: `FASE_3C_VALIDADA_MANUALMENTE`.

Auditoria de seguridad post-3C-C completada sin hallazgos bloqueantes. La auditoria previa `2662998` queda como antecedente prematuro/documental.

Motivo:

- el repositorio ya tenia antecedente historico de simulador, generacion manual y checkers;
- el nuevo flujo reanclado activo 3C-A primero y despues 3C-B;
- Docker/PHP estan disponibles y se re-ejecutaron verificaciones completas;
- el usuario reporto QA manual completada para 3C-A y 3C-B.
- la revision tecnica post-3C-C quedo completada en `59feafc`.
- la auditoria actual verifica CxP, Caja, pagos/abonos, guards, CSRF, rollback, QA y cambios no relacionados.

## Resultado historico previo

- Bloque revisado: Fase 3C.
- Resultado historico: sin hallazgos bloqueantes por revision estatica.
- Reclasificacion: auditoria prematura; no sustituye la auditoria de seguridad post-3C-C.
- Cambios de codigo historicos: ajuste menor en preview para no enlazar proveedor si el proveedor no pertenece al hotel actual.

## Resultado actual

- Resultado: sin hallazgos bloqueantes.
- No se detecta creacion de CxP sin `hotel_id`.
- No se detecta CxP con compra/proveedor de otro hotel.
- No se detecta duplicacion de CxP por compra/hotel.
- No se detecta CxP desde compra no recibida, cancelada o borrador.
- No se detectan movimientos en `cuentas_por_pagar_movimientos`.
- No se detectan movimientos de Caja relacionados con CxP.
- No existe tabla `compra_pagos`.
- `/api/sync` sigue bloqueado por checker.
- Cambios no relacionados de dashboard/habitaciones/notificaciones quedaron fuera del bloque 3C; al iniciar esta auditoria ya estaban en commit separado `e52766e`.
- Cambios PWA no relacionados detectados al cierre 3C fueron validados manualmente y commiteados por separado en `35abdc7`.
- QA manual final 3C reportada por el usuario como OK: preview, simulador, bloqueo por CxP existente, links, detalle CxP, origen compra/proveedor, no duplicados, sin pagos, sin abonos, sin Caja y sin movimientos de Caja.

## Controles revisados

- CxP conserva rutas GET de consulta:
  - `/cuentas-por-pagar`;
  - `/cuentas-por-pagar/generacion-preview`;
  - `/cuentas-por-pagar/{id}`.
- CxP tiene un unico POST activo en 3C-B:
  - `/cuentas-por-pagar/generar-desde-compra/{id}`.
- El boton de generacion solo aparece en compras elegibles del preview.
- CxP no contiene acciones de pago.
- CxP 3C-B escribe solo en `cuentas_por_pagar` durante generacion manual.
- CxP no escribe en `cuentas_por_pagar_movimientos`.
- Health/preflights fallan si `cuentas_por_pagar_movimientos` deja de estar en cero durante Fase 3C-C.
- SQL read-only 3C-C confirma cero movimientos CxP/pagos/abonos y cero movimientos de Caja con referencia CxP.
- CxP no toca `movimientos_caja`, `cortes_caja` ni `cajas`.
- Modelo CxP filtra por `hotel_id`.
- La generacion valida compra recibida, proveedor del mismo hotel, total positivo, detalles existentes y no duplicado.
- Proveedor/Compras no generan CxP automaticamente.
- Sidebar expone CxP bajo el gate de Inventario.
- Acceso sin sesion a `/cuentas-por-pagar` redirige a login.
- `/api/sync` sigue fuera de alcance y validado por checker como bloqueado.

## Auditoria especifica Fase 3C post-3C-C

- Creacion de CxP sin `hotel_id`: bloqueada por `CuentaPorPagar::generarDesdeCompraRecibida()`, que valida `hotelId > 0` y lo inserta explicitamente.
- Creacion de CxP con proveedor de otro hotel: bloqueada por `obtenerCompraParaGeneracion()`, que une proveedor con `p.hotel_id = c.hotel_id`, y por `assertCompraGenerable()`.
- Creacion de CxP con compra de otro hotel: bloqueada por busqueda `WHERE c.id = ? AND c.hotel_id = ?`.
- Duplicacion de CxP: bloqueada por transaccion, `FOR UPDATE` sobre compra y verificacion `cuentas_por_pagar` por `(hotel_id, compra_id)`.
- Generacion desde compra no recibida: bloqueada por `assertCompraGenerable()` con estado exacto `recibida`.
- Generacion desde compra cancelada/borrador: bloqueada por la misma validacion de estado y el boton no aparece para filas no elegibles.
- Escritura accidental en Caja: no hay referencias de escritura a `movimientos_caja`, `cortes_caja` ni `cajas` en el flujo CxP.
- Creacion accidental de pagos: no hay rutas, vistas ni modelo de pagos CxP; `cuentas_por_pagar_movimientos` no se inserta en 3C.
- Endpoints POST sin proteccion: el unico POST 3C llama `validateCSRF()` y pasa por `before()` con `requireAuth`, contexto hotelero y modulo `inventario`.
- Falta de CSRF: el formulario elegible usa `csrf_field()`.
- Botones visibles en estados incorrectos: el boton solo se renderiza cuando `es_elegible` es verdadero.
- Errores de permisos: CxP comparte guardas de modulo `inventario` en controlador y sidebar.
- `/api/sync` modificado accidentalmente: no hay cambios de codigo en `ApiController` ni en la ruta `/api/sync` dentro de esta revision.
- Rollback insuficiente: `docs/rollback-cola.md` documenta rollback por 3C-A, 3C-B, 3C-C y revision post-QA.
- QA critica 3C-A/3C-B: completada manualmente por el usuario.
- QA critica 3C-C: cubierta por health/preflights y SQL read-only con `ERROR: 0`.

## Warnings conocidos

- Los checkers ejecutados dentro del contenedor no ven `docs/technical` ni `migrations/` completos por el montaje actual.
- Verificacion automatica 3C ejecutada con Docker/PHP disponible: `php -l`, health, preflights, SQL read-only, POST sin sesion historico y prueba local controlada.
- Hay tablas legacy/duplicadas documentadas que no se deben borrar ni fusionar.
- Los cambios PWA no relacionados (`src/app/services/PwaPushService.php`, `src/public_html/service-worker.js`) quedaron fuera de CxP y fueron resueltos por separado en `35abdc7`.

## Riesgos residuales

- Si una fase futura agrega pagos, debe crear contrato nuevo y revisar Caja, saldos y movimientos financieros desde cero.
- No hay indice unico fisico documentado para `(hotel_id, compra_id)`; la prevencion actual usa bloqueo transaccional sobre la compra y verificacion de CxP existente.

## Recomendacion

Bloque 3C cerrado tecnicamente. Mantener prohibidos pagos, Caja, CxC, nomina operativa, permisos profundos y `/api/sync` hasta nuevo bloque explicito.

## Fase 3C - controles esperados

- Simulador primero, sin escritura.
- Generacion manual posterior, nunca automatica.
- Validar compra recibida y `hotel_id`.
- Validar proveedor del mismo hotel.
- Validar no duplicado por `(hotel_id, compra_id)`.
- Validar `total > 0`.
- Registrar auditoria si `AuditService` esta disponible.
- Mantener movimientos Caja-CxP en cero.

## Fase 4A Centro Documental - auditoria inicial de contrato

Estado: `CIERRE_TECNICO_4A_COMPLETADO`.

- Riesgo principal: exposicion accidental de documentos privados si se guardan en
  `public_html/uploads`.
- Mitigacion definida: usar `STORAGE_PATH/documentos` y descarga por controlador.
- Patron seguro de referencia: `ReporteLinkController`, con `realpath`, raices
  permitidas, validacion de hotel/permisos y headers privados.
- Tablas generales creadas de forma aditiva y vacia:
  `documento_tipos`, `documentos`, `documento_entidades`.
- 4A-A no implementa uploads, POST, descargas, acciones de borrado ni exposicion publica.
- 4A-B implementa solo consultas GET de metadata y relaciones.
- `storage_path` queda documentado como almacenamiento privado futuro, no URL publica.
- No se insertaron documentos ni relaciones; conteos iniciales en cero.
- No se detectaron pagos/abonos CxP nuevos ni movimientos CxP.
- Prohibido en 4A: Caja, pagos, abonos, Fase 3D y `/api/sync`.

### Auditoria 4A-A

- Backup previo confirmado con SHA256 antes de aplicar DB.
- Migracion usa `CREATE TABLE IF NOT EXISTS`.
- No contiene `DROP`, `DELETE`, `UPDATE` de datos operativos ni `ALTER` destructivo.
- No modifica tablas de Caja, pagos, abonos, CxP operativa ni `/api/sync`.
- Las tablas nuevas incluyen `hotel_id` para aislamiento multi-hotel.
- Riesgo residual: las relaciones polimorficas no pueden tener FK directa contra cada
  entidad; las fases read-only/upload deben validar entidad y hotel en modelo/servicio.

### Auditoria 4A-B

- Rutas creadas: solo GET (`/documentos`, `/documentos/{id}`,
  `/documentos/entidad/{tipo}/{id}`).
- No hay rutas POST, upload, descarga, edicion ni borrado bajo `/documentos`.
- Acceso sin sesion queda protegido por middleware global de autenticacion.
- Controlador exige contexto hotelero y modulos relacionados visibles.
- Modelo filtra `documentos` y `documento_entidades` por `hotel_id`.
- Vistas no muestran `storage_path`, `nombre_archivo` ni rutas internas.
- No se tocan Caja, pagos, abonos, CxP operativa ni `/api/sync`.
- Riesgo residual: los vinculos polimorficos solo prueban `hotel_id` de
  `documento_entidades`; la fase de upload debe validar existencia real de cada entidad
  antes de insertar relaciones.

### Auditoria 4A-C

- Rutas nuevas:
  - `GET /documentos/subir`;
  - `POST /documentos/subir`.
- El POST pasa por `DocumentoController::before()`, por lo tanto exige autenticacion,
  contexto hotelero y modulo relacionado activo.
- El formulario incluye `csrf_field()` y el controlador llama `validateCSRF()`.
- El modelo valida `hotel_id > 0` antes de escribir.
- La entidad opcional se normaliza y se valida contra proveedor, compra, CxP, huesped o
  reservacion del mismo `hotel_id`.
- El archivo se valida con:
  - error de upload;
  - `is_uploaded_file`;
  - tamano maximo;
  - extension simple;
  - bloqueo de extensiones peligrosas;
  - MIME real con `finfo`;
  - coincidencia MIME-extension.
- El archivo fisico se guarda fuera de `public_html`, bajo `STORAGE_PATH/documentos`.
- El nombre fisico es aleatorio y no reutiliza el nombre original.
- El permiso del archivo se fuerza a `0640`.
- La transaccion inserta `documentos`, opcionalmente `documento_entidades`, y registra
  auditoria `documentos.cargado`.
- Si falla la transaccion despues de mover el archivo, el archivo nuevo se elimina como
  rollback interno de la misma carga.
- Las vistas no muestran `storage_path` ni `nombre_archivo`.
- No hay ruta de descarga, edicion ni borrado.
- La URL directa a `storage/documentos/...` no sirvio el archivo en prueba local.
- Archivo `.html` invalido fue rechazado sin crear registros.
- No se detectaron cambios en Caja, pagos, abonos, CxP operativa ni `/api/sync`.

Riesgos residuales 4A-C:

- La descarga segura aun no existe; no debe improvisarse con URLs directas.
- La prueba local creo `documentos.id=1` y `documento_entidades.id=1`; no borrar sin
  autorizacion explicita.
- Los tipos documentales base ya existen; las cargas siguen limitadas a PDF/JPG/PNG/WEBP
  hasta una fase posterior que autorice formatos adicionales.
- QA manual post-hotfix reportada por el usuario como funcional.

### Hotfix seguridad 4A-C

- Se agregaron tipos globales por seed idempotente; no se modifica ningun documento
  existente salvo el registro de prueba controlada.
- Cada tipo mantiene MIME permitidos acotados a PDF/JPG/PNG/WEBP y maximo `10 MB`.
- La carga general ya no permite capturar manualmente IDs de entidad, reduciendo errores
  de vinculacion a entidades inexistentes.
- El vinculo por entidad sigue disponible solo con contexto validado.
- La correccion del CSS relativo no toca PWA, service worker, cache names ni `/api/sync`.

### Revision tecnica 4A post-QA

- Hallazgo corregido: `/documentos/entidad/{tipo}/{id}` podia mostrar una vista contextual vacia para una entidad inexistente o ajena al hotel, aunque la carga contextual posterior si la bloqueaba.
- Correccion: `DocumentoController::entidadAction()` valida `Documento::entidadExisteEnHotel()` antes de consultar documentos y antes de exponer el enlace de carga contextual.
- Riesgo reducido: evita pantallas contextuales ambiguas y mantiene el guard multi-hotel consistente entre listado contextual y carga contextual.
- No se agregaron descargas, edicion, borrado, pagos, abonos, Caja ni cambios en `/api/sync`.

### Auditoria seguridad 4A post-QA

- Rutas documentales activas: `GET /documentos`, `GET /documentos/subir`, `POST /documentos/subir`, `GET /documentos/entidad/{tipo}/{id}`, `GET /documentos/{id}`.
- No existen rutas documentales de descarga, edicion ni borrado.
- El POST documental conserva `requireAuth`, contexto hotelero, modulo relacionado, CSRF y validacion central en modelo.
- `storage_path` y `nombre_archivo` no se muestran en vistas; se usan solo como metadata privada interna.
- `unlink` solo aparece como rollback interno de `Documento::crearDesdeUpload()` cuando la transaccion falla despues de mover el archivo.
- No hay referencias a documentos en PWA/offline (`service-worker.js`, `pwa.js`, `offline-data.js`, `reservaciones-offline.js`) ni cambios en `ApiController`.
- SQL read-only: `documento_tipos=6`, `documentos=3`, `documento_entidades=1`, `cuentas_por_pagar_movimientos=0`, `movimientos_caja=1403`, `logs_auditoria=29`.
- Sin Caja, pagos, abonos, Fase 3D ni `/api/sync`.

## Fase 4B Descarga segura documental - auditoria de contrato

Estado: `DESCARGA_SEGURA_4B_VALIDADA_MANUALMENTE`.

- 4B-0 no implementa rutas ni lectura de archivos.
- Riesgo principal futuro: exposicion de documentos privados si se omite `hotel_id` o
  `realpath`.
- Mitigacion obligatoria: buscar documento por `id + hotel_id`, restringir a estado
  `activo`, resolver archivo bajo `realpath(STORAGE_PATH . '/documentos')` y enviar
  headers privados.
- Prohibido: links publicos, tokens publicos, `public_html/uploads`, edicion, borrado,
  Caja, pagos, abonos, PWA/offline y `/api/sync`.

### Auditoria 4B-A

- Ruta activa: `GET /documentos/{id}/descargar`.
- El controlador usa el guard documental existente: autenticacion, contexto hotelero y
  modulo relacionado.
- El modelo busca por `id + hotel_id + estado activo`.
- La ruta fisica se resuelve con `realpath` y raiz permitida
  `STORAGE_PATH/documentos`.
- Documento `#3` de otro hotel no descarga desde sesion Los Cedros.
- No se agregan links publicos ni tokens.
- No se agregan POST, edicion, borrado, Caja, pagos, abonos ni `/api/sync`.
- No se registra auditoria de descarga en esta fase para evitar escrituras en DB.

### Auditoria 4B-B

- Estado: `AUDITORIA_DESCARGAS_4B_B_VALIDADA_MANUALMENTE`.
- Se agrega trazabilidad minima para descargas exitosas y bloqueadas.
- La auditoria se registra con `AuditService::record()` en `logs_auditoria`.
- Los fallos de auditoria son tolerantes y no rompen la descarga.
- No se registran rutas internas (`storage_path`) ni nombres fisicos (`nombre_archivo`).
- No hay nuevas rutas, POST, tokens publicos, edicion, borrado, Caja, pagos, abonos ni
  `/api/sync`.
- Riesgo residual: cada descarga exitosa o intento bloqueado genera escritura en
  `logs_auditoria`; es intencional y debe monitorearse si el volumen crece.

### Auditoria 4B-C-A

- Estado: `METADATA_DOCUMENTAL_4B_C_A_VALIDADA_MANUALMENTE`.
- La edicion se limita a metadata segura; nunca archivo fisico ni rutas internas.
- `storage_path`, `nombre_archivo`, `sha256`, `mime_type`, `size_bytes` y `hotel_id`
  quedan fuera del formulario y del update.
- POST usa CSRF y busqueda por `id + hotel_id`.
- Audita `documentos.metadata_actualizada` solo cuando hay cambios reales.

### Cierre seguridad 4B post-QA

- Estado: `CIERRE_TECNICO_4B_COMPLETADO`.
- Rutas documentales sensibles requieren sesion; HTTP sin sesion redirige a login.
- Descarga solo usa `GET /documentos/{id}/descargar`, documento `activo`,
  `id + hotel_id` y `realpath` bajo `STORAGE_PATH/documentos`.
- Metadata solo usa `POST /documentos/{id}/actualizar` con CSRF y update limitado a
  `documento_tipo_id`, `titulo`, `descripcion` y `etiquetas`.
- Las vistas no muestran `storage_path` ni `nombre_archivo`.
- No hay rutas de borrado, reemplazo de archivo, links publicos, Caja, pagos, abonos
  ni cambios en `/api/sync`.
- Riesgo residual: `logs_auditoria` crece con descargas y actualizaciones de metadata;
  aceptado como trazabilidad minima del bloque.

## Fase 4C Documentos por entidad - auditoria de contrato

Estado: `DOCUMENTOS_ENTIDAD_CONTEXTUAL_4C_VALIDADA_MANUALMENTE`.

- 4C-0 no agrega rutas, controladores, modelos, vistas ni DB.
- Riesgo principal futuro: mostrar documentos de otro hotel si una ficha no valida
  `hotel_id` antes de consultar `documento_entidades`.
- Control requerido: resolver documentos desde modelo/servicio central con
  `hotel_id`, nunca desde una consulta ad hoc en la vista.
- Las fichas no deben mostrar `storage_path`, `nombre_archivo`, rutas absolutas
  ni links publicos.
- 4C-A usa `Documento::documentosPorEntidad()` desde controladores y partial contextual
  compartido.
- 4C-A no crea rutas, POST nuevo, edicion, borrado ni reemplazo de archivo.
- La accion `Vincular documento` usa `GET /documentos/subir?entidad_tipo=...&entidad_id=...`;
  `DocumentoController::subirAction()` valida entidad y hotel antes de renderizar.
- Los enlaces visibles son GET a detalle documental y descarga autenticada ya existente.
- Sin Caja, pagos, abonos, Fase 3D, NP-A, PWA/offline ni `/api/sync`.
- QA manual post-hotfix reportada por el usuario como correcta.
- La validacion manual confirma que el enlace contextual no introdujo escrituras nuevas
  fuera del flujo de carga documental ya existente y protegido.

## Fase 4D Archivado documental - auditoria de contrato

Estado: `FASE_4D_A_VALIDADA_MANUALMENTE`.

- 4D-0 no agrega rutas, controladores, modelos, vistas ni DB.
- Riesgo principal futuro: cambiar estado de documentos de otro hotel si no se valida
  `id + hotel_id`.
- Control requerido: transiciones centralizadas en modelo/servicio y nunca desde la
  vista.
- Toda accion futura debe ser POST + CSRF.
- La baja logica no debe borrar archivos fisicos ni relaciones.
- No usar `DELETE` sobre `documentos` o `documento_entidades`.
- No mostrar `storage_path`, `nombre_archivo`, rutas absolutas ni links publicos.
- Sin Caja, pagos, abonos, Fase 3D, NP-A, PWA/offline ni `/api/sync`.
- 4D-A implementa solo `activo <-> archivado`; la baja logica `eliminado` sigue fuera
  de alcance.
- Verificacion automatica completada sin errores bloqueantes.
- QA manual reportada por el usuario como correcta.

### Auditoria 4D-A post-implementacion

- Rutas sensibles: solo `POST /documentos/{id}/archivar` y
  `POST /documentos/{id}/restaurar`.
- Proteccion: autenticacion, contexto hotelero, modulo relacionado y CSRF.
- Aislamiento multi-hotel: `Documento::actualizarEstado()` exige `id + hotel_id`.
- Transiciones: solo `activo -> archivado` y `archivado -> activo`.
- Datos: no hay `DELETE`, no se borra archivo fisico, no se borran relaciones en
  `documento_entidades`.
- Auditoria: `documentos.estado_actualizado` registra estado antes/despues.
- Vista: no envia `hotel_id`, `estado`, `storage_path` ni `nombre_archivo`.
- Descarga: documentos archivados no muestran accion de descarga en detalle.
- Fuera de alcance confirmado: baja logica `eliminado`, Caja, pagos, abonos, NP-A,
  Fase 3D y `/api/sync`.
- Resultado: cierre tecnico sin hallazgos bloqueantes y QA manual completada por el
  usuario.

### Auditoria de contrato 4D-B-0

Estado: `CONTRATO_4D_B_BAJA_LOGICA_DOCUMENTAL_COMPLETADO`.

- 4D-B-0 no agrega rutas, controladores, modelos, vistas ni DB.
- La baja logica futura queda limitada por contrato a `activo -> eliminado` y
  `archivado -> eliminado`.
- Restaurar desde `eliminado` queda fuera de alcance y requiere contrato separado.
- Toda implementacion futura debe usar POST + CSRF, filtro `id + hotel_id`, auditoria y
  confirmacion fuerte.
- Se prohibe borrado fisico, `DELETE`, baja masiva, links publicos, Caja, pagos,
  abonos, NP-A, Fase 3D, PWA/offline y `/api/sync`.
- Resultado: contrato seguro; sin superficie de ataque nueva porque no hay codigo
  funcional.

### Auditoria 4D-B-A baja logica documental

Estado: `BAJA_LOGICA_DOCUMENTAL_4D_B_A_COMPLETADA_QA_DIFERIDA`.

- Ruta sensible nueva: `POST /documentos/{id}/eliminar`.
- Proteccion: autenticacion, contexto hotelero, modulo relacionado y CSRF.
- Aislamiento multi-hotel: `Documento::actualizarEstado()` exige `id + hotel_id`.
- Transiciones: solo `activo -> eliminado` y `archivado -> eliminado` como nueva baja
  logica; cambios desde `eliminado` siguen bloqueados.
- Datos: no hay `DELETE`, no se borra archivo fisico, no se borran relaciones en
  `documento_entidades`.
- Auditoria: se reutiliza `documentos.estado_actualizado` con estado antes/despues.
- Vista: no envia `hotel_id`, `estado`, `storage_path`, `nombre_archivo` ni `sha256`.
- Fuera de alcance confirmado: restauracion desde `eliminado`, baja masiva, Caja,
  pagos, abonos, NP-A, Fase 3D, PWA/offline y `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

### Cierre seguridad Fase 4D

Estado: `CIERRE_TECNICO_4D_COMPLETADO_QA_DIFERIDA`.

- No hay hallazgos bloqueantes en 4D-A/4D-B-A.
- Health checker valida archivado/restauracion y baja logica con `ERROR: 0`.
- La unica QA manual pendiente del bloque documental es probar baja logica en navegador.
- `/api/sync` sigue fuera de alcance y bloqueado.

## Bloque Personal y Nomina (Fase NP) - controles esperados

### Estado NP-0

- Solo contrato/diagnostico/diseno; sin codigo, sin migraciones aplicadas, sin escritura
  en DB. Sin superficie de ataque nueva todavia.

### Controles a verificar en NP-A..NP-F

- Rutas sensibles del modulo bajo `requireAuth` y contexto hotelero; HTTP sin sesion bloquea.
- Toda escritura (pago/anticipo/prestamo/asistencia/documento/alta-baja) con POST + CSRF.
- Filtro `hotel_id` en todas las consultas; trabajador y movimiento del mismo hotel.
- Trabajador independiente de `usuarios`: no requiere login; vinculo opcional `usuario_id`.
- Sin ALTER destructivo ni borrado sobre `usuarios`/`hotel_usuarios`.
- Sin escritura en Caja (`cajas`, `movimientos_caja`, `cortes_caja`); movimientos
  Caja-nomina deben seguir en cero.
- Sin categoria "Nomina" nueva en `categorias_movimientos`.
- Saldos derivados del ledger, no editables manualmente.
- Baja logica de trabajador/documentos, nunca borrado fisico.
- Auditoria con `AuditService` en escrituras relevantes.
- `/api/sync` fuera de alcance y bloqueado.
- Montos no negativos garantizados por `CHECK` y por validacion de aplicacion.

### Auditoria NP-A migracion base

Estado: `MIGRACION_NP_A_PERSONAL_BASE_COMPLETADA_QA_DIFERIDA`.

- Migracion aditiva aplicada tras backup verificado.
- Tablas creadas vacias: `trabajadores`, `trabajador_pagos`,
  `trabajador_anticipos`, `trabajador_prestamos`, `trabajador_asistencias`,
  `trabajador_documentos`.
- Todas las tablas tienen `hotel_id`.
- Tablas hijas tienen `trabajador_id` con FK a `trabajadores`.
- `usuarios` no fue alterada de forma destructiva; el vinculo es opcional.
- Caja no fue tocada: movimientos Nomina 0 y categorias Nomina 0.
- No hay rutas, controladores ni vistas de Personal todavia.
- `/api/sync` fuera de alcance.

### Auditoria NP-A UI read-first

Estado: `PERSONAL_READ_ONLY_NP_A_COMPLETADO_QA_DIFERIDA`.

- Rutas nuevas: solo `GET /trabajadores` y `GET /trabajadores/{id}`.
- Proteccion: autenticacion, contexto hotelero, modulo `usuarios` y permiso
  `usuarios.view`.
- Aislamiento multi-hotel: modelo filtra por `hotel_id` y detalle por `id + hotel_id`.
- Datos: no hay `INSERT`, `UPDATE`, `DELETE`, POST, CSRF ni acciones de escritura.
- Caja/Nomina: no hay referencias a `movimientos_caja`, `cajas`, `cortes_caja` ni
  categoria Nomina.
- Archivos laborales: no se expone `ruta_archivo` de `trabajador_documentos`.
- Riesgo residual: permisos propios de Personal/Nomina quedan diferidos; temporalmente
  se reutiliza `usuarios.view` como guard administrativo conservador.
- QA manual queda diferida por instruccion del usuario.

### Auditoria NP-B-0 contrato CRUD trabajadores

Estado: `CONTRATO_NP_B_CRUD_TRABAJADORES_COMPLETADO`.

- No agrega codigo ni DB; sin superficie de ataque nueva.
- La implementacion futura queda limitada a escrituras en `trabajadores`.
- Controles obligatorios futuros: POST + CSRF, `hotel_id`, auditoria, baja logica y
  validacion de datos.
- Caja, pagos, anticipos, prestamos, asistencia operativa, documentos laborales y
  `/api/sync` siguen fuera de alcance.

### Auditoria NP-B-A CRUD trabajadores

Estado: `CRUD_TRABAJADORES_NP_B_A_COMPLETADO_QA_DIFERIDA`.

- Rutas POST nuevas: crear, actualizar, baja logica y reactivar trabajador.
- Proteccion: sesion, contexto hotelero, modulo `usuarios`, permisos `usuarios.create`
  / `usuarios.edit` y CSRF.
- Aislamiento: modelo usa `hotel_id` del contexto y detalle/actualizacion por
  `id + hotel_id`.
- `usuario_id` opcional validado contra `hotel_usuarios` del mismo hotel.
- No hay `DELETE FROM trabajadores`.
- No hay escrituras en ledger laboral, Caja, CxP, pagos, anticipos, prestamos,
  asistencia ni documentos laborales.
- Auditoria con `AuditService` en crear/actualizar/baja/reactivar.

## Auditoria TLM-0 contrato y diagnostico

Estado: `CONTRATO_TLM_0_COMPLETADO`.

- Sin codigo nuevo, sin rutas nuevas y sin migraciones.
- No hay escrituras de DB.
- Diagnostico confirma que la unica tabla operativa existente de mantenimiento es
  `mantenimientos_habitaciones`.
- `mantenimientos_habitaciones` tiene `hotel_id` completo en los 10 registros revisados.
- Riesgo principal: una fase futura de tareas podria afectar disponibilidad si cambia
  `habitaciones.estado` sin contrato. Mitigacion: TLM-A/TLM-B deben ser aditivas y
  read-first; el primer flujo de tareas no debe cambiar estados de habitacion.
- Se mantiene prohibido tocar Caja, pagos, abonos, nomina, PWA/offline/cache y `/api/sync`.

## Auditoria TLM-A migracion base

Estado: `MIGRACION_TLM_A_TAREAS_BASE_COMPLETADA_QA_DIFERIDA`.

- Migracion aditiva aplicada tras backup verificado.
- Tablas creadas vacias: `tareas_operativas` y `tarea_eventos`.
- Ambas tablas tienen `hotel_id`.
- `tareas_operativas` referencia entidades operativas de forma nullable; no fuerza
  cambios sobre habitaciones, reservaciones, huespedes, trabajadores ni mantenimientos.
- `habitaciones.estado` no fue modificado por la migracion.
- `mantenimientos_habitaciones` conserva sus 10 registros.
- Existen 4 movimientos historicos de Caja con texto de productos de limpieza; son
  preexistentes y no pertenecen a TLM-A.
- No hay rutas, UI ni POST nuevos.
- No se toco Caja, pagos, abonos, nomina, PWA/offline/cache ni `/api/sync`.

## Auditoria TLM-B read-only

Estado: `TAREAS_READ_ONLY_TLM_B_COMPLETADO_QA_DIFERIDA`.

- Rutas nuevas: `GET /tareas` y `GET /tareas/{id}`.
- Proteccion: sesion, contexto hotelero, modulo `habitaciones` y permiso
  `habitaciones.view`.
- Aislamiento: modelo filtra por `hotel_id`; detalle por `id + hotel_id`; joins por el
  mismo hotel.
- Datos: no hay `INSERT`, `UPDATE`, `DELETE`, POST, CSRF ni acciones de escritura.
- No se cambia `habitaciones.estado`.
- No se modifica `mantenimientos_habitaciones`.
- No hay escrituras en Caja, pagos, abonos, nomina ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

## Auditoria TLM-C creacion manual

Estado: `CREACION_MANUAL_TLM_C_COMPLETADA_QA_DIFERIDA`.

- Rutas nuevas: `GET /tareas/crear` y `POST /tareas`.
- Proteccion: sesion, contexto hotelero, modulo `habitaciones`, permiso
  `habitaciones.mantenimiento` y CSRF en el POST.
- Aislamiento multi-hotel: `hotel_id` se toma del contexto activo; la habitacion
  opcional se valida por `id + hotel_id`.
- Escrituras permitidas: solo `INSERT` en `tareas_operativas` y `tarea_eventos`.
- No hay `UPDATE` ni `DELETE` de tareas en esta subfase.
- No se cambia `habitaciones.estado`.
- No se modifica `mantenimientos_habitaciones`.
- No hay escrituras en Caja, pagos, abonos, nomina ni `/api/sync`.
- Auditoria: `AuditService::record()` registra la creacion cuando esta disponible.
- Riesgo residual: aun no hay asignacion/cierre/cancelacion; las tareas creadas durante
  QA deben quedar documentadas hasta una fase operativa posterior.

## Auditoria TLM-D asignacion a trabajador

Estado: `ASIGNACION_TLM_D_COMPLETADA_QA_DIFERIDA`.

- Ruta nueva: `POST /tareas/{id}/asignar`.
- Proteccion: sesion, contexto hotelero, modulo `habitaciones`, permiso
  `habitaciones.mantenimiento` y CSRF.
- Aislamiento multi-hotel: tarea por `id + hotel_id`; trabajador por
  `id + hotel_id + estado activo`.
- Escrituras permitidas: `UPDATE tareas_operativas` para trabajador/estado asignada y
  `INSERT tarea_eventos`.
- No hay `DELETE`.
- No se cambia `habitaciones.estado`.
- No se modifica `mantenimientos_habitaciones`.
- No hay escrituras en Caja, pagos, abonos, nomina, asistencia ni `/api/sync`.
- Riesgo residual: no hay trabajadores activos en la base local actual; QA real depende
  de crear o tener un trabajador activo.

## Auditoria TLM-E estados manuales

Estado: `ESTADOS_TLM_E_COMPLETADOS_QA_DIFERIDA`.

- Rutas nuevas: `POST /tareas/{id}/iniciar`, `POST /tareas/{id}/completar` y
  `POST /tareas/{id}/cancelar`.
- Proteccion: sesion, contexto hotelero, modulo `habitaciones`, permiso
  `habitaciones.mantenimiento` y CSRF.
- Aislamiento multi-hotel: tarea por `id + hotel_id`.
- Escrituras permitidas: `UPDATE tareas_operativas` para estado/fechas/notas y
  `INSERT tarea_eventos`.
- No hay `DELETE`.
- No se cambia `habitaciones.estado`.
- No se modifica `mantenimientos_habitaciones`.
- No hay escrituras en Caja, pagos, abonos, nomina, asistencia ni `/api/sync`.
- Riesgo residual: completar una tarea no libera habitaciones; esto debe quedar claro en
  QA manual.

## Auditoria TLM-F contexto visual

Estado: `CONTEXTUAL_TLM_F_COMPLETADO_QA_DIFERIDA`.

- Integracion read-only en fichas de habitacion y trabajador.
- No se agregan POST, formularios ni acciones operativas.
- `TareaOperativa::listarPorEntidadHotel()` filtra por `hotel_id` y entidad permitida.
- Los joins de habitacion/trabajador se hacen por el mismo `hotel_id`.
- El partial contextual no expone `storage_path`, rutas internas ni acciones de Caja.
- No cambia `habitaciones.estado`.
- No modifica `mantenimientos_habitaciones`.
- No crea asistencia, pagos, abonos, nomina ni movimientos de Caja.
- Riesgo residual: confusion operativa entre tarea y mantenimiento. Mitigacion: textos
  indican que el bloque es de tareas operativas y no altera disponibilidad.

## Auditoria TLM-G health y preflights

Estado: `PREFLIGHT_TLM_G_COMPLETADO_QA_DIFERIDA`.

- Nuevo preflight de tareas operativas es solo lectura y usa transaccion read-only.
- Health checker valida consistencia de `tareas_operativas` y `tarea_eventos`.
- Detecta entidades inexistentes o de otro hotel.
- Detecta estados, categorias, prioridades y fechas incoherentes.
- Detecta referencias textuales a tareas operativas en `movimientos_caja`.
- No corrige datos automaticamente.
- No agrega rutas, UI, POST, migraciones ni escrituras.
- No toca `habitaciones.estado`, `mantenimientos_habitaciones`, Caja, pagos, abonos,
  nomina ni `/api/sync`.

## Auditoria TLM-H cierre tecnico

Estado: `BLOQUE_TLM_CERRADO_QA_DIFERIDA`.

- Revision del bloque TLM completa hasta TLM-G.
- No se agregaron funcionalidades en el cierre.
- Se conserva QA manual diferida.
- Riesgo residual principal: validar visualmente flujos manuales de tareas cuando el
  usuario retome QA.

## Auditoria NP-C-0 ledger laboral

Estado: `CONTRATO_NP_C_LEDGER_LABORAL_COMPLETADO`.

- Solo documenta contrato; no agrega rutas ni escrituras.
- Riesgo principal identificado: confundir saldo laboral informativo con pago real.
- Mitigacion: NP-C-A debe ser read-only; cualquier POST futuro requiere CSRF,
  auditoria, filtro `hotel_id` y cero escritura en Caja.
- Caja, movimientos, cortes, categoria Nomina, pagos reales y `/api/sync` quedan fuera de
  alcance.

## Auditoria NP-C-A ledger read-only

Estado: `LEDGER_LABORAL_NP_C_A_COMPLETADO_QA_DIFERIDA`.

- La ficha de trabajador solo lee conceptos, anticipos y prestamos.
- No se agregan rutas nuevas ni acciones POST.
- Las consultas se filtran por `hotel_id` y `trabajador_id`.
- No se crean pagos reales, abonos, movimientos de Caja ni categoria Nomina.
- Riesgo residual: el usuario debe validar visualmente que el texto "saldo informativo"
  sea claro y que no parezca una accion de pago.

## Auditoria NP-C-E preflight

Estado: `PREFLIGHT_LEDGER_NP_C_E_COMPLETADO_QA_DIFERIDA`.

- El preflight usa transaccion read-only y no corrige datos.
- Valida integridad de hotel/trabajador en tablas `trabajador_*`.
- Valida ausencia de rutas operativas de nomina/Caja bajo `/trabajadores`.
- Valida ausencia de categoria `Nomina` en Caja.
- No toca `/api/sync`.

## Auditoria NP-C-F cierre read-only

Estado: `BLOQUE_NP_C_READ_ONLY_CERRADO_QA_DIFERIDA`.

- El bloque queda cerrado sin escrituras laborales.
- La separacion con Caja permanece intacta.
- Riesgo residual: QA visual con trabajador real queda diferida.
- Siguiente fase de escritura debe abrir contrato independiente.

## Auditoria NP-C-B-0 conceptos laborales

Estado: `CONTRATO_NP_C_B_CONCEPTOS_LABORALES_COMPLETADO`.

- Solo contrato documental.
- Riesgo principal: confundir concepto laboral con pago real.
- Mitigacion: no habilitar tipo `pago` como salida real y mantener Caja fuera.

## Auditoria NP-C-B-A conceptos laborales manuales

Estado: `CONCEPTOS_LABORALES_NP_C_B_A_COMPLETADO_QA_DIFERIDA`.

- La unica escritura nueva autorizada es `INSERT INTO trabajador_pagos`.
- Se valida trabajador activo del hotel actual antes de escribir.
- El formulario usa CSRF y permiso administrativo existente.
- No se permite tipo `pago`; solo `comision`, `bono`, `descuento` y `ajuste`.
- No hay escrituras en Caja, cortes, movimientos, anticipos, prestamos ni asistencia.
- Health checker y preflight detectan escrituras fuera de alcance.
- Riesgo residual: falta QA manual con trabajador real porque la tabla local esta vacia.

## Auditoria NP-C-B-F cierre conceptos laborales

Estado: `BLOQUE_NP_C_B_CONCEPTOS_LABORALES_CERRADO_QA_DIFERIDA`.

- Revision tecnica completada.
- Se corrigieron inconsistencias menores de texto sin cambiar logica.
- No quedan errores automaticos relacionados con NP-C-B-A.
- QA manual queda diferida; no se marca validacion de usuario.
- Riesgo residual: siguiente bloque de anticipos/prestamos/asistencia requiere contrato
  nuevo por su cercania con flujo financiero laboral.

## Auditoria NP-C-C-0 anticipos y prestamos

Estado: `CONTRATO_NP_C_C_ANTICIPOS_PRESTAMOS_COMPLETADO`.

- Solo contrato documental.
- Riesgo principal: confundir anticipo/prestamo laboral con egreso real de Caja.
- Mitigacion: no crear Caja, no crear pagos/abonos y derivar saldos iniciales del monto.
- Cualquier abono, liquidacion o movimiento financiero real queda fuera de alcance.

## Auditoria NP-C-C-A anticipos y prestamos manuales

Estado: `ANTICIPOS_PRESTAMOS_NP_C_C_A_COMPLETADO_QA_DIFERIDA`.

- Las unicas escrituras nuevas autorizadas son `INSERT INTO trabajador_anticipos` e
  `INSERT INTO trabajador_prestamos`.
- Se valida trabajador activo del hotel actual antes de escribir.
- El formulario usa CSRF y permiso administrativo existente.
- No se aceptan `estado`, `saldo_pendiente` ni `hotel_id` desde formulario.
- No hay escrituras en Caja, cortes, movimientos, asistencia ni abonos.
- Preflight y health checker detectan escrituras fuera de alcance.
- Riesgo residual: falta QA manual con trabajador real porque la tabla local esta vacia.

## Auditoria NP-C-C-F cierre anticipos y prestamos

Estado: `BLOQUE_NP_C_C_ANTICIPOS_PRESTAMOS_CERRADO_QA_DIFERIDA`.

- Revision tecnica completada.
- Se corrigio copy desactualizado sin cambiar logica.
- No quedan errores automaticos relacionados con NP-C-C-A.
- QA manual queda diferida; no se marca validacion de usuario.
- Riesgo residual: abonos/liquidaciones y asistencia requieren contrato nuevo.

## Auditoria NP-C-D-0 asistencia manual

Estado: `CONTRATO_NP_C_D_ASISTENCIA_MANUAL_COMPLETADO`.

- Solo contrato documental.
- Riesgo principal: convertir asistencia en nomina automatica sin controles.
- Mitigacion: captura manual sin calculos de pago, sin Caja y sin descuentos automaticos.
- La llave unica por trabajador/dia evita duplicados si la implementacion futura valida
  antes de insertar.

## Auditoria NP-C-D-A asistencia manual

Estado: `ASISTENCIA_MANUAL_NP_C_D_A_COMPLETADA_QA_DIFERIDA`.

- La unica escritura nueva autorizada es `INSERT INTO trabajador_asistencias`.
- Se valida trabajador activo del hotel actual antes de escribir.
- El formulario usa CSRF y permiso administrativo existente.
- No se aceptan `hotel_id`, `trabajador_id`, `created_by` ni `updated_by` desde la vista.
- No hay escrituras en Caja, cortes, movimientos, pagos reales, abonos ni nomina.
- Se bloquea duplicado por `(hotel_id, trabajador_id, fecha)`.
- Preflight y health checker detectan escrituras fuera de alcance.
- Riesgo residual: falta QA manual con trabajador real porque la tabla local esta vacia.

## Auditoria NP-C-D-F cierre asistencia manual

Estado: `BLOQUE_NP_C_D_ASISTENCIA_MANUAL_CERRADO_QA_DIFERIDA`.

- Revision tecnica completada.
- No quedan errores automaticos relacionados con NP-C-D-A.
- QA manual queda diferida; no se marca validacion de usuario.
- Riesgo residual: edicion/anulacion de asistencias, nomina y Caja requieren contrato
  nuevo.

## Auditoria NP-D-0 documentos laborales

Estado: `CONTRATO_NP_D_DOCUMENTOS_LABORALES_COMPLETADO`.

- Solo contrato documental.
- Riesgo principal: duplicar fuentes documentales o exponer rutas internas de archivos.
- Mitigacion: usar Centro Documental moderno y congelar `trabajador_documentos`.
- Cualquier implementacion futura debe validar trabajador por `hotel_id` y no mostrar
  `storage_path` ni `ruta_archivo`.

## Auditoria NP-D-A documentos laborales

Estado: `DOCUMENTOS_LABORALES_NP_D_A_COMPLETADOS_QA_DIFERIDA`.

- La integracion usa Centro Documental moderno y no escribe en `trabajador_documentos`.
- `Documento::entidadExisteEnHotel()` valida `trabajador` contra `trabajadores.hotel_id`.
- La ficha de trabajador renderiza metadata segura mediante partial existente.
- No se exponen rutas internas de storage.
- No se crean rutas nuevas ni se toca `/api/sync`.
- Riesgo residual: QA manual de vincular/subir/descargar documento de trabajador queda
  diferida.

## Auditoria NP-D-F cierre documentos laborales

Estado: `BLOQUE_NP_D_DOCUMENTOS_LABORALES_CERRADO_QA_DIFERIDA`.

- Revision tecnica completada.
- No quedan errores automaticos relacionados con NP-D-A.
- QA manual queda diferida; no se marca validacion de usuario.
- Riesgo residual: reconciliacion o retiro de `trabajador_documentos` requiere contrato
  nuevo.

## Auditoria NP-E cierre Personal operativo base

Estado: `BLOQUE_NP_PERSONAL_OPERATIVO_BASE_CERRADO_QA_DIFERIDA`.

- Cierre documental; no agrega nuevas superficies de ataque.
- No hay endpoints nuevos, migraciones ni escrituras adicionales.
- Se mantiene separacion estricta entre ledger laboral informativo y Caja.
- `/api/sync` queda fuera de alcance.
- Riesgo residual principal: QA manual diferida con trabajador activo y posible confusion
  operativa si conceptos/anticipos/prestamos se interpretan como pagos reales.

## Auditoria NP-F-0 reporte Personal read-only

Estado: `CONTRATO_NP_F_REPORTE_PERSONAL_READONLY_COMPLETADO`.

- Solo contrato documental; no hay endpoints ni escrituras nuevas.
- Riesgo futuro principal: convertir un reporte en pantalla operativa con acciones POST.
- Mitigacion definida: NP-F-A debe ser GET/read-only, scoped por `hotel_id`, sin Caja,
  sin nomina, sin pagos reales y sin `/api/sync`.

## Auditoria NP-F-A reporte Personal read-only

Estado: `REPORTE_PERSONAL_NP_F_A_COMPLETADO_QA_DIFERIDA`.

- Nueva superficie: GET `/trabajadores/reporte`.
- Sin formularios POST, sin CSRF necesario, sin acciones operativas.
- Consultas scoped por `hotel_id`.
- No hay escrituras en `trabajador_*`, tareas, documentos, Caja ni `/api/sync`.
- Riesgo residual: QA manual diferida y posibilidad de que usuarios interpreten saldos
  informativos como pagos reales; la vista mantiene copy explicito de no Caja/no nomina.

## Auditoria NP-F-F cierre reporte Personal

Estado: `BLOQUE_NP_F_REPORTE_PERSONAL_CERRADO_QA_DIFERIDA`.

- Revision tecnica y auditoria completadas sin hallazgos bloqueantes.
- Health/preflight validan que el reporte sea read-only y que no se agreguen rutas fuera
  de alcance.
- Riesgo residual: falta QA manual en navegador con datos reales de trabajadores.

## Auditoria TLM-I-0 reporte operativo read-only

Estado: `CONTRATO_TLM_I_REPORTE_OPERATIVO_READONLY_COMPLETADO`.

- Solo contrato documental; no hay endpoints ni escrituras nuevas.
- Riesgo futuro principal: convertir el reporte en pantalla de acciones operativas.
- Mitigacion definida: TLM-I-A debe ser GET/read-only, scoped por `hotel_id`, sin POST,
  sin cambios de habitacion, sin Caja y sin `/api/sync`.

## Auditoria TLM-I-A reporte operativo read-only

Estado: `REPORTE_TLM_I_A_COMPLETADO_QA_DIFERIDA`.

- Nueva superficie: GET `/tareas/reporte`.
- Sin formularios POST, sin CSRF necesario, sin acciones operativas.
- Consultas scoped por `hotel_id`.
- No hay escrituras en `tareas_operativas`, `tarea_eventos`, habitaciones,
  mantenimientos, Caja ni `/api/sync`.
- Riesgo residual: QA manual diferida y posible interpretacion del reporte como pantalla
  operativa; la vista mantiene copy explicito de solo lectura.

## Auditoria TLM-I-F cierre reporte operativo

Estado: `BLOQUE_TLM_I_REPORTE_OPERATIVO_CERRADO_QA_DIFERIDA`.

- Revision tecnica y auditoria completadas sin hallazgos bloqueantes.
- Health/preflight validan que el reporte sea read-only y que no se agreguen rutas fuera
  de alcance.
- Riesgo residual: falta QA manual en navegador con tareas reales.

## Auditoria OP-0

Estado: `CONTRATO_OP_0_TABLERO_OPERATIVO_READONLY_COMPLETADO`.

- OP-0 es solo contrato documental; no agrega superficie HTTP ni DB.
- Riesgo principal futuro: mezclar datos de hoteles al consolidar reservaciones,
  habitaciones, tareas, trabajadores y documentos. OP-A debe exigir `hotel_id`.
- Riesgo futuro rojo: convertir el tablero en panel de acciones. Cualquier POST o cambio
  de estado queda fuera de OP-A y requiere contrato nuevo.
- `/api/sync`, offline, Caja, pagos, abonos y nomina quedan explicitamente fuera.

## Auditoria OP-A

Estado: `TABLERO_OPERATIVO_OP_A_COMPLETADO_QA_DIFERIDA`.

- Nueva superficie: GET `/operacion/diaria`.
- Sin POST, formularios, CSRF ni acciones operativas.
- `OperacionDiaria` usa `hotel_id` en consultas y no contiene INSERT/UPDATE/DELETE.
- La vista no expone `storage_path`, `nombre_archivo` ni `movimientos_caja`.
- Riesgo residual: QA manual diferida para validar visualmente datos reales por hotel.

## Auditoria OP-F

Estado: `BLOQUE_OP_TABLERO_OPERATIVO_CERRADO_QA_DIFERIDA`.

- Revision de cierre sin hallazgos bloqueantes.
- Health y preflight validan que OP-A sigue read-only.
- Riesgo residual: falta QA manual visual.
- No se habilitan acciones desde el tablero.

## Auditoria MANT-A reporte mantenimiento

Estado: `REPORTE_MANTENIMIENTO_MANT_A_COMPLETADO_QA_DIFERIDA`.

- Superficie revisada: `GET /reportes/mantenimiento`.
- Hallazgo corregido: consultas activas de mantenimiento sin filtro `hotel_id`.
- Mitigacion: todos los metodos usados por `mantenimientoAction()` ahora filtran por el
  hotel actual y los joins con habitaciones validan hotel coincidente.
- Sin POST, sin formularios operativos, sin CSRF requerido para escritura porque no hay
  escritura.
- No hay migraciones, cambios de DB, Caja, pagos, abonos, nomina, offline ni cambios en
  `/api/sync`.
- Riesgo residual: QA manual diferida para confirmar visualmente datos de cada hotel y
  filtros del reporte.

## Auditoria MANT-F cierre reporte mantenimiento

Estado: `BLOQUE_MANT_REPORTE_MANTENIMIENTO_CERRADO_QA_DIFERIDA`.

- Revision de cierre sin hallazgos bloqueantes.
- Health y preflight validan que MANT-A sigue read-only y scoped por `hotel_id`.
- Riesgo residual: falta QA manual visual con datos reales por hotel.
- No se habilitan acciones de mantenimiento desde el reporte.

## Auditoria MANT-B mantenimiento inmediato

Estado: `MANTENIMIENTO_INMEDIATO_MANT_B_COMPLETADO_QA_DIFERIDA`.

- Superficie revisada: `POST /habitaciones/{id}/mantenimiento`.
- CSRF y permiso `habitaciones.mantenimiento` se mantienen.
- Se agrega whitelist de accion y validacion backend de tipo/prioridad/motivo.
- Se bloquea iniciar si la habitacion ya esta en mantenimiento o si ya existe registro
  `en_proceso` para la misma habitacion/hotel.
- Finalizar exige que la habitacion este en estado `mantenimiento`.
- No se agregan rutas, migraciones, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Riesgo residual: una habitacion historica esta en mantenimiento sin registro
  `en_proceso`; queda como warning, sin correccion automatica.

## Auditoria MANT-B-F cierre mantenimiento inmediato

Estado: `BLOQUE_MANT_B_MANTENIMIENTO_INMEDIATO_CERRADO_QA_DIFERIDA`.

- Revision de cierre sin hallazgos bloqueantes.
- Health y preflight validan que MANT-B mantiene CSRF, permisos, catalogos, duplicados y
  `hotel_id`.
- Riesgo residual: falta QA manual visual y el warning historico documentado.
- No se habilitan automatizaciones de mantenimiento programado.

## Auditoria MANT-C-0

Estado: `CONTRATO_MANT_C_0_MANTENIMIENTO_PROGRAMADO_COMPLETADO`.

- Riesgo principal: `Mantenimiento::activarMantenimientosPendientes()` puede cambiar
  mantenimientos a `en_proceso` y habitaciones a `mantenimiento`.
- El contrato prohibe conectar esa activacion a cron, dashboard o request web en la
  siguiente subfase.
- Programacion/cancelacion deben reforzar pertenencia por `hotel_id`, estados permitidos
  y duplicados solapados antes de considerarse estables.
- No se modifica codigo, DB, Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Auditoria MANT-C-A

Estado: `MANTENIMIENTO_PROGRAMADO_MANT_C_A_COMPLETADO_QA_DIFERIDA`.

- Programacion mantiene CSRF, permiso y hotel actual.
- Se bloquean fechas invalidas, catalogos alterados, motivo vacio y solapes programados.
- Cancelacion valida mantenimiento scoped y habitacion visible del hotel actual.
- Checkers confirman solapes historicos = 0 y ausencia de Caja en la superficie revisada.
- Riesgo residual: QA manual diferida y una habitacion historica en mantenimiento sin
  registro `en_proceso`.
- `/api/sync`, Caja, pagos, abonos, nomina y offline siguen fuera de alcance.

## Auditoria MANT-C-F cierre mantenimiento programado

Estado: `BLOQUE_MANT_C_MANTENIMIENTO_PROGRAMADO_CERRADO_QA_DIFERIDA`.

- Revision de cierre sin hallazgos bloqueantes.
- Health y preflight validan MANT-C-A con `ERROR: 0`.
- Riesgo residual: QA manual visual/funcional diferida.
- No se habilita activacion automatica de mantenimientos vencidos.

## Auditoria MANT-D-0

Estado: `CONTRATO_MANT_D_0_PREVIEW_VENCIDOS_COMPLETADO`.

- Contrato documental sin cambios de codigo.
- Riesgo principal identificado: activar vencidos cambia mantenimiento y habitacion.
- La fase futura queda limitada a GET/read-only y no puede llamar
  `activarMantenimientosPendientes()`.
- Caja, pagos, abonos, nomina, offline y `/api/sync` siguen fuera de alcance.

## Auditoria MANT-D-A

Estado: `PREVIEW_MANT_D_A_COMPLETADO_QA_DIFERIDA`.

- Superficie revisada: `GET /reportes/mantenimiento-programado`.
- Ruta protegida por las guardas existentes de `ReportesController`.
- Modelo scoped por `hotel_id` y lectura pura.
- Vista sin formularios POST, sin CSRF, sin botones de activacion y sin storage interno.
- No llama `activarMantenimientosPendientes()`.
- No cambia habitaciones, reservaciones, tareas ni disponibilidad.
- Checkers confirman `ERROR: 0` y `/api/sync` bloqueado en codigo.
- Riesgo residual: QA manual diferida y warning historico de 1 habitacion en
  mantenimiento sin registro activo.

## Auditoria MANT-D-F

Estado: `BLOQUE_MANT_D_PREVIEW_VENCIDOS_CERRADO_QA_DIFERIDA`.

- Revision de cierre sin hallazgos bloqueantes.
- Health y preflight validan que MANT-D-A sigue GET/read-only.
- No se agregan nuevas acciones ni automatizaciones en el cierre.
- Riesgo residual: QA manual diferida y warning historico documentado.

## Auditoria MANT-E-0

Estado: `CONTRATO_MANT_E_0_ACTIVACION_MANUAL_COMPLETADO`.

- Contrato documental sin codigo ni DB.
- Riesgo principal: activar cambia dos fuentes operativas (`mantenimientos_habitaciones`
  y `habitaciones`).
- Mitigacion definida: solo accion manual individual, transaccion, CSRF, permiso,
  auditoria, validacion de habitacion disponible y reservaciones conflictivas.
- Automatizacion masiva/cron sigue prohibida.

## Auditoria MANT-E-A

Estado: `ACTIVACION_MANUAL_MANT_E_A_COMPLETADA_QA_DIFERIDA`.

- Ruta POST protegida por sesion, CSRF y permiso.
- Modelo usa transaccion y `FOR UPDATE`.
- Se bloquea mantenimiento futuro, no programado, de otro hotel, con habitacion no
  disponible, con mantenimiento en proceso o con reservacion conflictiva.
- No se llama `activarMantenimientosPendientes()`.
- No se toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Riesgo residual: falta QA manual real con backup previo.

## Auditoria MANT-E-F

Estado: `BLOQUE_MANT_E_ACTIVACION_MANUAL_CERRADO_QA_DIFERIDA`.

- Revision de cierre sin hallazgos bloqueantes automaticos.
- Health y preflight validan que el POST es individual, con CSRF/permiso y sin Caja.
- Riesgo residual: QA manual real pendiente y warning historico documentado.
- No se autoriza activacion automatica ni masiva.

## Auditoria MANT-G-0

Estado: `CONTRATO_MANT_G_0_TAREAS_DESDE_MANTENIMIENTO_COMPLETADO`.

- Contrato documental sin cambios de codigo ni DB.
- Riesgo principal: que una tarea vinculada se interprete como fuente de disponibilidad.
- Mitigacion: el contrato fija que tareas solo son seguimiento operativo.
- Riesgo principal futuro: duplicar tareas para un mismo mantenimiento.
- Mitigacion futura: validacion por `hotel_id`, `mantenimiento_id`, estado y origen antes
  de crear.
- Caja, pagos, abonos, nomina, offline y `/api/sync` siguen fuera de alcance.

## Auditoria MANT-G-A

Estado: `TAREAS_CONTEXTUALES_MANT_G_A_COMPLETADAS_QA_DIFERIDA`.

- La integracion es GET/read-only.
- Las tareas vinculadas se consultan por `hotel_id` y `mantenimiento_id`.
- La vista solo muestra enlaces GET al detalle de tarea.
- No hay POST nuevo ni boton para crear tareas desde mantenimiento.
- No cambia `habitaciones.estado` ni `mantenimientos_habitaciones.estado`.
- Checkers validan tareas con mantenimiento inexistente y tareas con mantenimiento de
  otro hotel en cero.
- Caja, pagos, abonos, nomina, offline y `/api/sync` siguen fuera de alcance.

## Auditoria MANT-G-B-0

Estado: `CONTRATO_MANT_G_B_0_CREACION_MANUAL_TAREA_MANTENIMIENTO_COMPLETADO`.

- Contrato documental sin cambios de codigo ni DB.
- Riesgo principal futuro: duplicar tareas activas para el mismo mantenimiento.
- Mitigacion definida: bloqueo por `hotel_id + mantenimiento_id` en estados
  `pendiente`, `asignada` o `en_proceso`.
- Riesgo principal futuro: que una tarea altere disponibilidad.
- Mitigacion definida: tarea solo escribe en `tareas_operativas`/`tarea_eventos`; no
  modifica `habitaciones` ni `mantenimientos_habitaciones`.
- Caja, pagos, abonos, nomina, offline y `/api/sync` siguen fuera de alcance.

## Auditoria MANT-G-B-A

Estado: `CREACION_MANUAL_TAREA_MANT_G_B_A_COMPLETADA_QA_DIFERIDA`.

- Ruta POST protegida por sesion, modulo, permiso y CSRF.
- Modelo valida mantenimiento y habitacion por `hotel_id`.
- Modelo bloquea duplicado activo por mantenimiento.
- Escritura limitada a `tareas_operativas` y `tarea_eventos`.
- Auditoria registra `tareas.creada_desde_mantenimiento`.
- No se modifica `habitaciones` ni `mantenimientos_habitaciones`.
- Caja, pagos, abonos, nomina, offline y `/api/sync` siguen fuera de alcance.

## Auditoria MANT-G-F

Estado: `BLOQUE_MANT_G_TAREAS_DESDE_MANTENIMIENTO_CERRADO_QA_DIFERIDA`.

- Cierre tecnico del bloque MANT-G sin funcionalidades nuevas.
- Confirmado por health/preflights:
  - ruta manual registrada y controlada;
  - duplicados activos por mantenimiento en cero;
  - tareas con mantenimiento inexistente o de otro hotel en cero;
  - movimientos de Caja relacionados con tareas en cero;
  - `/api/sync` sigue bloqueado en codigo.
- No hay automatizacion, cron, accion masiva, Caja, pagos, abonos, nomina ni CxP
  operativa.
- Riesgo residual: QA manual diferida por instruccion del usuario.

## Auditoria LIM-0

Estado: `CONTRATO_LIM_0_LIMPIEZA_OPERATIVA_COMPLETADO`.

- Contrato documental sin cambios de codigo ni DB.
- Riesgo principal futuro: liberar habitaciones automaticamente sin revision humana.
- Mitigacion: LIM-0 prohibe automatizacion y exige fases read-only primero.
- Riesgo principal futuro: reactivar descuentos automaticos de inventario por limpieza.
- Mitigacion: inventario automatico queda fuera de alcance hasta contrato especifico.
- Caja, pagos, abonos, nomina, offline y `/api/sync` siguen fuera de alcance.

## Auditoria LIM-A

Estado: `REPORTE_LIM_A_LIMPIEZA_READONLY_COMPLETADO_QA_DIFERIDA`.

- Ruta nueva solo GET: `/reportes/limpieza`.
- Constructor de `ReportesController` conserva sesion, permiso y modulo `reportes`.
- Vista sin `<form>` y sin POST.
- Consultas scoped por `hotel_id`.
- Enlaces solo a GET de habitacion/tarea.
- Preflight valida ausencia de POST, Caja, `/api/sync` y tareas cross-hotel.
- Riesgo residual: QA manual diferida.

## Auditoria LIM-F

Estado: `BLOQUE_LIM_LIMPIEZA_READONLY_CERRADO_QA_DIFERIDA`.

- Cierre tecnico sin funcionalidades nuevas.
- Confirmado: no hay POST LIM, no hay formularios y no hay escrituras.
- Confirmado: sin Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Riesgo residual: QA manual diferida.

## Auditoria LIM-B-0

Estado: `CONTRATO_LIM_B_0_CREACION_MANUAL_TAREA_LIMPIEZA_COMPLETADO`.

- Contrato documental sin cambios de codigo ni DB.
- Riesgo principal futuro: crear tareas duplicadas para una misma habitacion en limpieza.
- Mitigacion definida: bloqueo por `hotel_id`, `habitacion_id`, `categoria = limpieza` y
  estado activo.
- Riesgo principal futuro: que una tarea libere habitacion automaticamente.
- Mitigacion definida: la tarea no actualiza `habitaciones.estado`.
- Caja, pagos, abonos, nomina, offline y `/api/sync` siguen fuera de alcance.

## Auditoria LIM-B-A

Estado: `CREACION_MANUAL_TAREA_LIM_B_A_COMPLETADA_QA_DIFERIDA`.

- Ruta POST protegida por sesion, permiso y CSRF.
- Modelo valida habitacion activa del hotel actual y estado `limpieza`.
- Modelo bloquea duplicado activo de categoria `limpieza` por habitacion.
- Escritura limitada a `tareas_operativas` y `tarea_eventos`; auditoria en
  `logs_auditoria`.
- No se actualiza `habitaciones`.
- Caja, pagos, abonos, nomina, offline y `/api/sync` siguen fuera de alcance.

## Auditoria LIM-B-F

Estado: `BLOQUE_LIM_B_TAREAS_DESDE_LIMPIEZA_CERRADO_QA_DIFERIDA`.

- Revision tecnica completada con health y preflight en `ERROR 0`.
- La ruta nueva se mantiene aislada en `POST /tareas/desde-limpieza/{id}`.
- La vista no libera habitaciones ni descuenta inventario.
- La accion no toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- El intento sin sesion redirige a login y no crea registros.
- Riesgo residual: QA manual queda diferida; si se crean tareas reales, el rollback
  funcional debe tratarlas como datos operativos.

## Auditoria TLM-J-0

Estado: `CONTRATO_TLM_J_0_AGENDA_TAREAS_TRABAJADOR_COMPLETADO`.

- Subfase documental sin cambios de codigo ni DB.
- La fase futura queda limitada a GET/read-only.
- Riesgos futuros: rangos de fecha amplios o exposicion cross-hotel.
- Mitigacion definida: filtros por `hotel_id`, rango por defecto corto y enlaces GET.
- Caja, pagos, abonos, nomina operativa, offline y `/api/sync` siguen fuera de alcance.

## Auditoria TLM-J-A

Estado: `AGENDA_TLM_J_A_TAREAS_TRABAJADOR_READONLY_COMPLETADA_QA_DIFERIDA`.

- Ruta nueva solo GET: `/tareas/agenda`.
- Constructor de `TareaController` conserva sesion, hotel, modulo y permiso view.
- Modelo filtra por `hotel_id` y limita rango a 31 dias.
- Vista contiene formulario GET, sin POST ni CSRF.
- Enlaces solo a vistas existentes de tarea, trabajador y habitacion.
- Caja, pagos, abonos, nomina, offline y `/api/sync` siguen fuera de alcance.

## Auditoria TLM-J-F

Estado: `BLOQUE_TLM_J_AGENDA_TAREAS_CERRADO_QA_DIFERIDA`.

- Revision tecnica completada con preflight TLM y health en `ERROR 0`.
- La agenda se mantiene sin acciones operativas.
- Riesgo residual: QA manual diferida y base local sin datos para validar filas reales.
- Cualquier integracion con turnos, nomina o automatizacion requiere contrato nuevo.

## Auditoria 6B-A

Estado: `INDICADORES_6B_A_TAREAS_HABITACIONES_READONLY_COMPLETADOS_QA_DIFERIDA`.

- No agrega rutas ni POST.
- Modelo consulta solo `tareas_operativas` activas del hotel actual y valida join con
  `habitaciones` del mismo `hotel_id`.
- La vista de habitaciones muestra conteo y categoria como informacion.
- No hay boton de crear tarea desde tarjeta o ficha de habitacion.
- No cambia `habitaciones.estado`, no toca mantenimiento, Caja, nomina, offline ni
  `/api/sync`.
- Riesgo residual: QA visual diferida para confirmar que el indicador no sature tarjetas
  con multiples incidencias.

## Auditoria 6C-0

Estado: `CONTRATO_6C_EVIDENCIAS_DOCUMENTOS_TAREAS_COMPLETADO`.

- Subfase documental sin cambios de codigo ni DB.
- Riesgo futuro principal: exponer rutas privadas o storage documental desde tarea.
- Mitigacion definida: reutilizar Centro Documental, no crear storage paralelo y no
  mostrar `storage_path`.
- Riesgo futuro cross-hotel: vincular documento a tarea de otro hotel.
- Mitigacion definida: validar entidad `tarea` contra `tareas_operativas.id` +
  `hotel_id`.
- Caja, nomina, pagos, offline y `/api/sync` siguen fuera de alcance.

## Auditoria 6C-A

Estado: `DOCUMENTOS_6C_A_TAREAS_READONLY_COMPLETADOS_QA_DIFERIDA`.

- No agrega rutas ni POST.
- No habilita upload contextual a tareas.
- Consulta documentos por tarea con join a `tareas_operativas` y `hotel_id`.
- El detalle de tarea oculta acciones de vincular documento.
- No expone `storage_path`.
- No modifica tarea, eventos, habitacion, Caja, nomina, offline ni `/api/sync`.
- Riesgo residual: QA visual diferida para confirmar estado vacio y ausencia de boton
  de vinculacion desde tarea.
## Auditoria Fase 6C-B

Resultado: sin hallazgos bloqueantes en la implementacion tecnica.

- La accion contextual de tarea reutiliza `/documentos/subir`.
- El controlador documental exige sesion y contexto hotelero.
- El POST de carga conserva CSRF.
- La entidad `tarea` se valida contra `tareas_operativas` por `hotel_id`.
- No se agregan rutas publicas de archivo.
- No se expone `storage_path`.
- No hay cambios en Caja, pagos, nomina, offline ni `/api/sync`.

Riesgo residual:

- QA manual debe confirmar carga real desde una tarea y visibilidad posterior en el
  detalle.

## Auditoria Fase 6C-F

Resultado: cierre tecnico sin hallazgos bloqueantes.

- 6C-A y 6C-B mantienen aislamiento por `hotel_id`.
- La escritura documental autorizada es solo la del Centro Documental existente.
- No se agregan acciones de borrado, reemplazo, links publicos ni storage publico.
- No hay cambios en Caja, pagos, nomina, offline ni `/api/sync`.
- QA manual queda diferida.

## Auditoria Fase 5B

Resultado: reanclaje sin hallazgos bloqueantes.

- La implementacion vigente de asistencia exige sesion, hotel actual y CSRF.
- La escritura queda centralizada en `Trabajador::registrarAsistenciaLaboralParaHotel()`.
- Se bloquean duplicados por trabajador y fecha.
- No hay Caja, pagos reales, abonos, nomina automatica ni `/api/sync`.
- QA manual queda diferida.

## Auditoria Fase 5C

Resultado: reanclaje sin hallazgos bloqueantes.

- La implementacion vigente exige sesion, hotel actual y CSRF.
- La escritura queda centralizada en `Trabajador`.
- `saldo_pendiente` no se acepta desde formularios.
- No hay liquidaciones, descuentos automaticos, pagos reales, abonos, Caja ni
  `/api/sync`.
- QA manual queda diferida.

## Auditoria Fase 5D-0

Resultado: contrato sin cambios operativos.

- No se implementaron pagos laborales.
- No se crearon rutas ni tablas.
- Se documenta el riesgo de confundir `trabajador_pagos` con pagos reales.
- Caja, abonos, liquidaciones, nomina automatica y `/api/sync` siguen fuera de alcance.

## Auditoria Fase 7A-0

Resultado: contrato sin cambios operativos.

- No se crearon tablas CxC.
- No se agregaron rutas ni controladores.
- No se tocaron reservaciones, pagos, abonos, facturacion, Caja ni `/api/sync`.
- Riesgo principal documentado: duplicar saldos/cobros si CxC se vuelve operativa sin
  reconciliacion previa.

## Auditoria Fase 7A-A

Resultado: implementacion read-only sin hallazgos bloqueantes.

- La unica ruta nueva es `GET /cuentas-por-cobrar`.
- No hay rutas POST bajo `/cuentas-por-cobrar`.
- El controlador exige sesion, contexto hotelero y modulo `reservaciones`.
- El modelo filtra por `reservaciones.hotel_id` y agrega pagos/abonos/facturas por
  `hotel_id + reservacion_id`.
- La vista comunica "solo lectura" y "saldo estimado".
- No hay botones ni acciones de cobro, abono, pago o Caja.
- No se toca `/api/sync`.

Warnings residuales:

- 3 pagos historicos con reservacion inexistente o de otro hotel.
- 170 solicitudes de factura con reservacion inexistente o de otro hotel.
- 3 reservaciones con saldo estimado negativo.

Estos warnings no bloquean la vista read-only porque la consulta excluye filas que no
coinciden por hotel/reservacion, pero bloquean cualquier avance a CxC operativa sin
reconciliacion previa.

## Auditoria Fase 7A-F

Resultado: cierre tecnico sin hallazgos bloqueantes.

- 7A mantiene solo una superficie GET/read-only.
- No hay acciones operativas de cobro.
- No hay pagos, abonos nuevos, Caja ni `/api/sync`.
- Los warnings historicos quedan documentados como prerequisito de 7B.

## Auditoria Fase 7A-R

Resultado: reconciliacion read-only sin hallazgos bloqueantes nuevos.

- Documento: `docs/fase_7A_R_reconciliacion_cxc_readonly.md`.
- No se agregan rutas, modelos, vistas, migraciones ni escrituras.
- No se modifica `reservaciones`, `reservacion_pagos`, `reservacion_abonos`,
  `solicitudes_factura`, Caja ni `/api/sync`.
- El preflight CxC sigue con `ERROR: 0` y 3 warnings historicos.
- Los 3 pagos huerfanos apuntan a reservaciones inexistentes.
- Las 170 solicitudes de factura huerfanas pertenecen a Los Cedros y apuntan a
  reservaciones inexistentes.
- Las 3 reservaciones con saldo negativo pertenecen a Maximiliano Leon y muestran
  doble cobertura por abono demo + pago completo.
- No existe tabla `cuentas_por_cobrar`.
- No existen movimientos de Caja tipo `CxC`.
- Riesgo residual: CxC operativa debe seguir bloqueada hasta contrato de
  reconciliacion controlada y backup previo.

## Auditoria Fase 7A-S-0

Resultado: contrato de reconciliacion controlada sin cambios operativos.

- Documento: `docs/fase_7A_S_0_contrato_reconciliacion_cxc_controlada.md`.
- No se agregan rutas, modelos, vistas, migraciones ni escrituras.
- No se modifica `reservaciones`, `reservacion_pagos`, `reservacion_abonos`,
  `solicitudes_factura`, Caja ni `/api/sync`.
- La opcion segura por defecto es excluir huerfanos de CxC operativa y mantener
  excedentes como informacion, sin tocar datos.
- El contrato exige backup, preview read-only, matriz de decision, auditoria y rollback
  antes de cualquier correccion futura.
- Riesgo residual: una escritura de reconciliacion futura puede alterar historia
  financiera; debe abrirse como subfase separada y autorizada.

## Auditoria Fase 7A-S-A

Resultado: preview/matriz read-only sin cambios operativos.

- Documento: `docs/fase_7A_S_A_preview_reconciliacion_cxc.md`.
- No se agregan rutas, modelos, vistas, migraciones ni escrituras.
- No se modifica `reservaciones`, `reservacion_pagos`, `reservacion_abonos`,
  `solicitudes_factura`, Caja ni `/api/sync`.
- La matriz propone decisiones default conservadoras:
  - pagos huerfanos: `excluir_cxc_operativa`;
  - facturas huerfanas: `excluir_cxc_operativa`;
  - facturas scoped validas: `mantener_en_reporte_readonly`;
  - excedentes: `mantener_excedente_informativo`.
- El preflight CxC sigue en `ERROR: 0`.
- Riesgo residual: cualquier cambio a la politica 7A-S-B debe aprobarse antes de
  cualquier escritura.

## Auditoria Fase 7A-S-B

Resultado: politica conservadora sin cambios operativos.

- Documento: `docs/fase_7A_S_B_politica_clasificacion_cxc.md`.
- No se agregan rutas, modelos, vistas, migraciones ni escrituras.
- No se modifica `reservaciones`, `reservacion_pagos`, `reservacion_abonos`,
  `solicitudes_factura`, Caja ni `/api/sync`.
- La politica excluye pagos/facturas huerfanas de CxC operativa.
- Las facturas scoped validas quedan como contexto read-only.
- Los excedentes quedan como informacion, no deuda.
- Riesgo residual: cualquier cambio de politica requiere contrato/escritura futura con
  backup, auditoria y rollback.

## Auditoria Fase 7A-S-F

Resultado: cierre documental sin cambios operativos.

- Documento: `docs/fase_7A_S_F_cierre_reconciliacion_cxc.md`.
- No se agregan rutas, modelos, vistas, migraciones ni escrituras.
- No se modifica `reservaciones`, `reservacion_pagos`, `reservacion_abonos`,
  `solicitudes_factura`, Caja ni `/api/sync`.
- El bloque 7A-S queda cerrado con politica conservadora.
- Preflight CxC: `ERROR: 0`.
- Health general: `ERROR: 0`.
- Riesgo residual vigente despues de 7B-A: 7B-B debe mantenerse GET/read-only y cualquier
  escritura CxC futura requiere contrato separado.

## Auditoria Fase 7B-0

Resultado: contrato sin cambios operativos.

- No se crearon rutas.
- No se crearon tablas.
- No se implementaron cobros, pagos ni abonos.
- No se toco Caja ni `/api/sync`.
- El contrato bloquea 7B operativa directa por inconsistencias historicas detectadas
  en 7A.

## Auditoria Fase 7B-A

Resultado: migracion base vacia sin hallazgos bloqueantes.

- Backup previo confirmado antes de aplicar DB.
- Migracion: `migrations/20260618_001_fase_7b_a_cxc_base_vacia.sql`.
- Se crearon solo tablas aditivas:
  - `cuentas_por_cobrar`;
  - `cuentas_por_cobrar_movimientos`.
- Ambas tablas quedaron en `0` registros.
- No se poblo CxC desde reservaciones, pagos, abonos, solicitudes de factura,
  huerfanos ni excedentes.
- No se crearon rutas, modelos PHP, vistas ni formularios.
- No se crearon cobros, pagos, abonos ni movimientos de Caja.
- `/api/sync` no se modifico.
- Riesgo residual: 7B-B debe seguir siendo GET/read-only; cualquier escritura CxC
  futura requiere backup, CSRF, auditoria, rollback y contrato separado.

## Auditoria Fase 7B-B

Resultado: listado/detalle read-only sin hallazgos bloqueantes.

- Las rutas nuevas son solo GET.
- No existen rutas POST bajo `/cuentas-por-cobrar/operativas`.
- El controlador conserva sesion, contexto hotelero y modulo `reservaciones`.
- El modelo filtra `cuentas_por_cobrar` por `hotel_id`.
- El detalle valida `id + hotel_id`.
- Los movimientos internos se leen desde `cuentas_por_cobrar_movimientos` por
  `cuenta_por_cobrar_id + hotel_id`.
- Las vistas no contienen formularios POST ni botones de cobro.
- No se crean cuentas, pagos, abonos ni movimientos de Caja.
- `/api/sync` no se modifica.
- Riesgo residual: 7B-C debe iniciar como contrato; la primera escritura CxC real exige
  backup y validaciones transaccionales.

## Auditoria Fase 7B-C-0

Resultado: contrato documental sin cambios operativos.

- No se crean rutas POST.
- No se crean botones.
- No se modifica DB.
- No se modifica `cuentas_por_cobrar`.
- No se modifica `cuentas_por_cobrar_movimientos`.
- No se modifican reservaciones, pagos, abonos ni solicitudes de factura.
- No se toca Caja ni `/api/sync`.
- El contrato exige que una futura CxC se genere por saldo pendiente neto elegible.
- Riesgo residual: 7B-C-A sera la primera escritura CxC real y requiere backup,
  transaccion, CSRF, auditoria y rollback.

## Auditoria Fase 7B-C-A

Resultado: implementacion tecnica validada manualmente sin hallazgos bloqueantes
automatizados.

- Backup previo confirmado con SHA256.
- La unica ruta POST nueva es
  `/cuentas-por-cobrar/generar-desde-reservacion/{id}`.
- El POST pasa por `CuentaPorCobrarController::before()`: sesion, contexto hotelero y
  modulo `reservaciones`.
- El POST llama `validateCSRF()`.
- La vista incluye `csrf_field()` y solo muestra el boton si la fila fue marcada como
  elegible.
- La generacion usa transaccion propia y `FOR UPDATE` sobre reservacion y duplicado CxC.
- La cuenta se crea por saldo pendiente neto, calculado con pagos/abonos scoped por
  `hotel_id + reservacion_id`.
- La escritura queda limitada a `cuentas_por_cobrar`,
  `cuentas_por_cobrar_movimientos` y `logs_auditoria`.
- El preflight falla si detecta escrituras hacia Caja, reservaciones, pagos, abonos o
  solicitudes de factura.
- No hay integracion con Caja, cobro de cliente, abono, facturacion nueva, offline ni
  `/api/sync`.
- QA manual validada: CxC `#1`, reservacion `#24`, hotel `4`, saldo `4250.00`,
  movimiento `CREACION #1`, auditoria `#105`.
- Consistencia post-QA: duplicados por reservacion `0`, CxC sin movimiento `CREACION`
  `0`, movimientos CxC huerfanos `0`, Caja-CxC textual `0`.
- Riesgo residual: la CxC `#1` es dato operativo real; no debe eliminarse ni modificarse
  sin fase formal de rollback/anulacion.

## Auditoria Fase 7B-C-F

Resultado: cierre tecnico sin hallazgos bloqueantes.

- 7B-C queda cerrado con contrato, implementacion y QA manual validada.
- La escritura autorizada queda limitada a generacion manual de CxC y movimiento interno
  `CREACION`.
- No se habilitan cobros, abonos, pagos de cliente, movimientos de Caja ni facturacion
  nueva.
- `/api/sync` no fue modificado.
- Siguiente fase debe iniciar con contrato separado antes de tocar Caja o saldos por
  cobro.

## Auditoria Fase 7B-D-0

Resultado: contrato documental sin cambios operativos.

- No se agregan rutas.
- No se agregan formularios.
- No se crea servicio de cobro.
- No se modifica DB.
- No se modifica CxC ni movimientos CxC.
- No se toca Caja, cortes ni movimientos de Caja.
- No se modifican reservaciones, pagos, abonos ni solicitudes de factura.
- No se toca `/api/sync`.
- Hallazgo de diseno: el enum `tipo_movimiento` de CxC no tiene tipo `COBRO`.
- Riesgo bloqueado: no usar `AJUSTE` para simular cobros.
- Siguiente fase segura: simulador/read-only antes de cualquier escritura financiera.

## Auditoria Fase 7B-D-A

Resultado: simulador read-only validado manualmente, sin hallazgos bloqueantes
automatizados.

- Ruta nueva solo GET: `/cuentas-por-cobrar/simulador-caja`.
- No hay POST nuevo de cobro CxC.
- La vista no contiene formularios `POST`.
- El controlador usa sesion, contexto hotelero y modulo `reservaciones`.
- El modelo lee CxC, corte abierto y Caja activa por `hotel_id`.
- El modelo no contiene escrituras hacia Caja, cortes, reservaciones, pagos, abonos ni
  facturacion.
- El simulador bloquea cobro real si falta tipo `COBRO`.
- No se modifica `/api/sync`.
- QA manual validada por el usuario: la CxC `#1` aparece bloqueada y no hay accion de
  cobro.
- Riesgo residual: el cobro real sigue bloqueado hasta definir esquema de movimiento
  `COBRO` o entidad de cobro.

## Auditoria Fase 7B-D-F

Resultado: cierre documental sin hallazgos bloqueantes.

- El cierre confirma que 7B-D-A no genero escrituras.
- Conteos post-QA permanecen controlados: CxC `1`, movimientos CxC `1`, Caja-CxC
  textual `0`.
- No se tocaron saldos, Caja, cortes, reservaciones, pagos, abonos, facturacion ni
  `/api/sync`.
- La CxC `#1` sigue siendo dato operativo protegido.

## Auditoria Fase 7B-D-B-0

Resultado: contrato documental sin cambios operativos.

- No se agregan migraciones.
- No se agregan rutas ni formularios.
- No se implementa servicio de cobro.
- No se modifican `cuentas_por_cobrar` ni `cuentas_por_cobrar_movimientos`.
- No se toca Caja ni cortes.
- Decision de seguridad: no registrar cobros como `AJUSTE`.
- La recomendacion incremental exige tipo semantico `COBRO` antes de cualquier cobro real.

## Auditoria Fase 7B-D-B-A

Resultado: migracion aditiva sin hallazgos bloqueantes.

- Backup previo confirmado con SHA256.
- La migracion modifica solo `cuentas_por_cobrar_movimientos.tipo_movimiento`.
- El enum conserva valores existentes y agrega `COBRO`.
- No se insertan movimientos CxC tipo `COBRO`.
- No se actualiza `cuentas_por_cobrar`.
- No se toca Caja, cortes, reservaciones, pagos, abonos ni facturacion.
- El preflight CxC valida enum, migracion registrada y `0` movimientos `COBRO`.
- `/api/sync` no se modifica.
- Riesgo residual: el primer cobro real aun requiere servicio transaccional, prueba
  rollback, token de un solo uso y QA manual.

## Auditoria Fase 7B-D-C-0

Resultado: contrato documental sin cambios operativos.

- No se agregan rutas POST.
- No se agregan formularios ni botones de cobro.
- No se implementa servicio PHP.
- No se escriben movimientos CxC tipo `COBRO`.
- No se escriben movimientos de Caja.
- No se actualizan saldos CxC.
- No se toca `reservacion_pagos`, `reservacion_abonos` ni `solicitudes_factura`.
- No se toca `/api/sync`.
- El contrato exige servicio transaccional, locks, token de un solo uso, referencia
  unica, auditoria y prueba rollback antes de cualquier cobro real.
- Riesgo residual: 7B-D-C-A sera escritura financiera real y debe requerir autorizacion
  explicita, backup y QA manual.

## Auditoria Fase 7B-D-C-A

Resultado: implementacion validada con prueba rollback y QA manual real.

- Backup previo confirmado con SHA256.
- POST protegido por sesion, contexto hotelero, modulo `reservaciones`, modulo `caja`,
  CSRF y token de un solo uso.
- Servicio transaccional bloquea CxC y corte con `FOR UPDATE`.
- Escrituras concentradas en `cuentas_por_cobrar_movimientos`, `movimientos_caja`,
  `cuentas_por_cobrar` y `logs_auditoria`.
- No escribe en `reservacion_pagos`, `reservacion_abonos`, `solicitudes_factura`,
  `reservaciones`, `cajas` ni `cortes_caja`.
- Prueba rollback crea movimiento CxC y Caja temporales, bloquea referencia duplicada y
  revierte todo.
- QA manual real: CxC `#1` queda parcial con saldo `4249.00`, movimiento `COBRO #4`
  e ingreso Caja `#1482` por `1.00`.
- Conteos post-QA confirman `1` movimiento `COBRO` persistente y `1` Caja-CxC
  persistente, asociados por referencia `QA-CXC-20260619-001`.
- Riesgo residual: los datos financieros persistentes no deben borrarse sin contrato de
  anulacion/reversion.

## Auditoria Fase 7B-D-D-0

Resultado: contrato documental sin cambios operativos.

- No implementa codigo, rutas, migraciones ni escrituras.
- Define que la reversion futura no puede borrar ni editar el cobro original.
- Exige movimiento CxC `CANCELACION`, gasto Caja `Reversion Cobro CxC`, auditoria,
  locks, token, CSRF y prueba rollback.
- Prohibe tocar reservaciones, pagos, abonos, facturacion, PWA/offline y `/api/sync`.
- La implementacion 7B-D-D-A requiere autorizacion explicita de escritura financiera.

## Auditoria Fase 7B-D-D-A

Resultado: implementacion validada con prueba rollback y QA manual real.

- Backup previo confirmado con SHA256.
- POST protegido por sesion, contexto hotelero, modulo `reservaciones`, modulo `caja`,
  CSRF y token de un solo uso.
- Servicio transaccional bloquea CxC, movimiento `COBRO`, movimiento Caja original y
  corte abierto con `FOR UPDATE`.
- Escrituras concentradas en `cuentas_por_cobrar_movimientos`, `movimientos_caja`,
  `cuentas_por_cobrar` y `logs_auditoria`.
- No borra ni edita el cobro original.
- No escribe en `reservacion_pagos`, `reservacion_abonos`, `solicitudes_factura`,
  `reservaciones`, `cajas` ni `cortes_caja`.
- Prueba rollback crea `CANCELACION` CxC y gasto Caja temporales, bloquea doble
  reversion y revierte todo.
- QA manual real: CxC `#1` queda `pendiente` con saldo `4250.00`, movimiento
  `CANCELACION #8`, gasto Caja `#1486`, referencia `REV-CXC-1-MOV-4` y auditoria
  `#113`.
- Conteos finales confirman `1` `CANCELACION` de reversion persistente, `1` gasto
  Caja `Reversion Cobro CxC` y `0` cobros con doble reversion.
- Riesgo residual: los datos financieros persistentes de la QA manual no deben
  borrarse sin fase formal.

## Auditoria Fase 7B-D-D-F

Resultado: cierre documental sin cambios operativos.

- Documento creado: `docs/fase_7B_D_D_F_cierre_reversion_cobro_cxc.md`.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Consolida evidencia de CxC `#1`, cobro `#4`, Caja `#1482`, reversion
  `CANCELACION #8`, Caja `#1486` y auditoria `#113`.
- Mantiene bloqueados cobros masivos, reversiones masivas, reversiones parciales,
  automatizaciones y cambios de facturacion hasta contrato independiente con backup y
  prueba rollback.

## Auditoria Fase 8A-0

Resultado: contrato sin cambios operativos.

- No se modifico `/operacion/diaria`.
- No se crearon rutas ni modelos nuevos.
- No hay POST, Caja, pagos, abonos, nomina ni `/api/sync`.
- El contrato limita KPIs futuros a lectura por `hotel_id`.

## Auditoria Fase 8A-A

Resultado: implementacion read-only sin hallazgos bloqueantes.

- No se agregan rutas.
- No hay formularios ni POST.
- `OperacionDiaria` calcula CxC estimada desde reservaciones, pagos y abonos por
  `hotel_id`.
- La vista etiqueta los KPIs como estimados.
- No hay Caja, pagos nuevos, abonos nuevos ni `/api/sync`.
- Preflight OP-A/8A-A valida ausencia de escrituras y storage interno.

## Auditoria Fase 8A-F

Resultado: cierre tecnico sin hallazgos bloqueantes.

- 8A queda cerrado como extension read-only.
- No se habilitan acciones desde dashboard.
- QA manual queda diferida.
- Cualquier paso a pagos proveedores con Caja debe iniciar con contrato 3D-0.

## Auditoria Fase 3D-0

Resultado: contrato sin cambios operativos.

- No se crearon rutas.
- No se crearon migraciones.
- No se insertaron movimientos CxP ni Caja.
- No se modificaron saldos.
- Se documenta que cualquier pago proveedor debe ser transaccional y con corte abierto
  del mismo hotel.
- Implementacion real queda bloqueada hasta backup y QA manual especifica.

## Auditoria Fase 3D-A

Resultado: simulador read-only sin hallazgos bloqueantes automatizados.

- Se agrega solo GET `/cuentas-por-pagar/simulador-caja`.
- No hay POST de pago proveedor.
- No hay botones de pago.
- No hay abonos.
- No hay escritura en CxP.
- No hay escritura en Caja.
- No hay movimientos CxP ni movimientos de Caja nuevos.
- La elegibilidad valida `hotel_id`, proveedor del hotel, compra del hotel, saldo,
  estado de CxP y corte abierto.
- `/api/sync` queda fuera del alcance.
- QA manual completada por el usuario.

## Auditoria Fase 3D-B

Resultado: contrato sin cambios operativos.

- No se agregan rutas.
- No se agregan botones de pago.
- No se modifica CxP.
- No se modifica Caja.
- No se crean movimientos CxP.
- No se crean movimientos de Caja.
- No se tocan cortes abiertos ni cerrados.
- `/api/sync` queda fuera del alcance.
- Contrato revisado por el usuario.
- La implementacion real queda condicionada a backup, prueba controlada y autorizacion
  explicita de escrituras financieras.

## Auditoria Fase 3D-C

Resultado: implementacion tecnica validada manualmente sin hallazgos bloqueantes post-QA.

- Backup limpio confirmado antes de habilitar escrituras.
- El POST requiere sesion, hotel actual, modulo inventario, modulo Caja y CSRF.
- El detalle genera token de pago de un solo uso por cuenta para reducir doble envio.
- El servicio valida `hotel_id`, proveedor del hotel, proveedor activo, compra recibida,
  saldo valido, total valido, corte de Caja abierto y monto no mayor al saldo.
- El servicio usa `FOR UPDATE` para CxP, corte y referencia duplicada.
- La actualizacion de CxP, movimiento CxP, movimiento Caja y auditoria ocurren en una
  sola transaccion.
- No se agregan pagos automaticos desde compras.
- No se agregan abonos.
- No se modifica UI de Caja.
- No se toca `/api/sync`.
- QA manual completada por el usuario con pago parcial y pago total restante.
- Evidencia post-QA: CxP `#1` del hotel `Maximiliano Leon`, proveedor `Juan Pedro`,
  pago parcial `10.00`, pago total restante `990.00`, saldo `1000.00 -> 0.00`,
  estado `pagada`.
- Trazabilidad creada: `cuentas_por_pagar_movimientos.id = 4`,
  `movimientos_caja.id = 1478`, referencia `CXP-1-MOV-4`; y
  `cuentas_por_pagar_movimientos.id = 5`, `movimientos_caja.id = 1479`,
  referencia `1212331312`.
- Preflight post-QA `preflight_pagos_proveedores_caja.php`: `OK: 22`,
  `WARNING: 0`, `ERROR: 0`.
- Riesgo residual: un pago real modifica datos financieros; antes de escalar en
  operacion diaria conviene implementar anulacion/reversion formal.

## Auditoria Fase 3D-D-0

Resultado: contrato documental sin cambios operativos.

- No implementa codigo, rutas, formularios, migraciones ni escrituras.
- Define que la reversion futura no puede borrar ni editar pagos proveedor originales.
- Exige movimiento CxP `CANCELACION`, ingreso Caja `Reversion Pago proveedor`,
  auditoria, locks, token, CSRF y prueba rollback.
- Confirma que el enum CxP ya incluye `CANCELACION`, por lo que no exige migracion
  previa.
- Prohibe tocar compras, proveedores, abonos, PWA/offline y `/api/sync`.
- La implementacion 3D-D-A requiere autorizacion explicita de escritura financiera.

## Auditoria Fase 3D-D-A

Resultado: implementacion tecnica con prueba rollback y QA manual validada.

- Backup previo confirmado con SHA256.
- POST protegido por sesion, contexto hotelero, modulo `inventario`, modulo `caja`,
  CSRF y token de un solo uso.
- Servicio transaccional bloquea CxP, movimiento `PAGO_REFERENCIAL`, gasto Caja
  original y corte abierto con `FOR UPDATE`.
- Escrituras concentradas en `cuentas_por_pagar_movimientos`, `movimientos_caja`,
  `cuentas_por_pagar` y `logs_auditoria`.
- No borra ni edita el pago original.
- No escribe en `compras`, `proveedores`, `cajas` ni `cortes_caja`.
- No crea abonos ni toca `/api/sync`.
- Prueba rollback crea pago temporal, `CANCELACION` CxP e ingreso Caja temporal,
  bloquea doble reversion y revierte todo.
- QA manual persistente confirmada: movimiento CxP `CANCELACION #9`, ingreso Caja
  `#1494`, referencia `REV-CXP-1-MOV-4`, auditoria `#121`.
- Conteos finales confirman `1` `CANCELACION` de reversion persistente y `1`
  ingreso Caja `Reversion Pago proveedor`, ambos vinculados por referencia.
- Riesgo residual: esos datos financieros persistentes no deben borrarse con SQL
  directo; cualquier correccion requiere fase formal, backup y rollback definido.

## Auditoria Fase 3D-D-F

Resultado: cierre documental sin cambios operativos.

- Documento creado: `docs/fase_3D_D_F_cierre_reversion_pago_proveedor_caja.md`.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Consolida evidencia de pago/reversion proveedor con CxP, Caja y auditoria.
- Mantiene bloqueadas reversiones masivas, parciales, pagos masivos y automatizaciones
  hasta contrato independiente con backup y prueba rollback.

## Auditoria Fase 9A-0

Resultado: contrato documental read-only sin cambios operativos.

- Documento creado: `docs/fase_9A_0_contrato_conciliacion_financiera_readonly.md`.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Define lectura cruzada CxC/CxP/Caja para detectar descuadres sin corregirlos.
- Prohibe pagos, cobros, reversiones, ajustes, cancelaciones, cambios de corte,
  cambios de saldo y SQL de correccion.
- Mantiene PWA/offline, IndexedDB, cache names y `/api/sync` fuera de alcance.
- Siguiente implementacion segura: preflight read-only, no UI con acciones.

## Auditoria Fase 9A-A

Resultado: preflight CLI read-only sin cambios operativos.

- Documento creado: `docs/fase_9A_A_preflight_conciliacion_financiera_readonly.md`.
- Herramienta creada: `src/tools/saas/preflight_conciliacion_financiera.php`.
- Ejecuta solo por CLI con `APP_ENV=local`.
- Abre conexion y transaccion read-only.
- Cierra la transaccion con rollback final.
- No agrega rutas, controladores, modelos, vistas, formularios, POST ni migraciones.
- No contiene escrituras SQL destructivas ni de modificacion de datos.
- Cruza CxC, CxP, Caja, cortes, cajas y auditoria para detectar descuadres.
- Resultado 9A-A: `OK: 75`, `WARNING: 0`, `ERROR: 0`.
- Regresiones CxC y CxP mantienen `ERROR: 0`.

## Auditoria Fase 9A-B-0

Resultado: contrato documental read-only sin cambios operativos.

- Documento creado:
  `docs/fase_9A_B_0_contrato_pantalla_conciliacion_financiera_readonly.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST ni
  migraciones.
- Define una ruta candidata futura, pero no la registra.
- Exige filtros GET, hotel actual desde sesion y ausencia total de acciones
  financieras.
- Prohibe botones de correccion, pago, cobro, reversion, ajuste y compensacion.
- Prohibe tocar permisos, auth, Caja, cortes, CxC, CxP, reservaciones, compras,
  proveedores, facturacion, PWA/offline y `/api/sync`.
- Obliga a usar tokens `--brand-*` en la futura UI hotelera, no `--ms-*`.

## Auditoria Fase 9A-B-A

Resultado: implementacion read-only sin hallazgos bloqueantes automatizados.

- Se agrega solo GET `/operacion/conciliacion-financiera`.
- No se agrega POST propio para conciliacion financiera.
- El controlador usa contexto de hotel existente y llama un lector read-only.
- El modelo `ConciliacionFinanciera` abre `START TRANSACTION READ ONLY` y cierra con
  rollback.
- La vista usa filtros GET, etiqueta `Solo lectura` y tokens `--brand-*`.
- No hay botones de correccion, pago, cobro, reversion, ajuste ni compensacion.
- No se crean migraciones ni escrituras sobre CxC, CxP, Caja, cortes, compras,
  proveedores, reservaciones ni auditoria.
- PWA/offline, IndexedDB, cache names y `/api/sync` quedan fuera de alcance.
- Preflight 9A-A/9A-B-A: `OK: 96`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 281`, `WARNING: 25`, `ERROR: 0`.
- QA manual con sesion completada por el usuario.

## Auditoria Fase 9A-B-F

Resultado: cierre documental sin cambios operativos.

- Documento creado:
  `docs/fase_9A_B_F_cierre_pantalla_conciliacion_financiera.md`.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Consolida evidencia de la pantalla GET/read-only de conciliacion financiera.
- Mantiene bloqueadas correcciones automaticas, ajustes de saldo, pagos/cobros masivos,
  reversiones masivas, cambios de Caja/cortes y cambios de `/api/sync`.
- Cualquier ampliacion requiere contrato independiente.

## Auditoria Fase 9C-0

Resultado: contrato documental read-only sin cambios operativos.

- Documento creado:
  `docs/fase_9C_0_contrato_arqueo_metodos_pago_readonly.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST ni
  migraciones.
- Define diagnostico futuro de arqueo por corte y metodo de pago.
- Prohibe abrir, cerrar, reabrir o recalcular cortes.
- Prohibe crear, editar o borrar movimientos de Caja.
- Prohibe cambiar metodos de pago, categorias, saldos o referencias.
- Mantiene CxC, CxP, reservaciones, compras, proveedores, facturacion, PWA/offline y
  `/api/sync` fuera de alcance.

## Auditoria Fase 9C-A

Resultado: preflight CLI/read-only sin cambios operativos.

- Documento creado:
  `docs/fase_9C_A_preflight_arqueo_metodos_pago_readonly.md`.
- Herramienta creada:
  `src/tools/saas/preflight_arqueo_metodos_pago.php`.
- Ejecuta solo por CLI con `APP_ENV=local`.
- Abre transaccion read-only y cierra con rollback.
- No agrega rutas, controladores, modelos, vistas, formularios, POST ni migraciones.
- No contiene escrituras SQL sobre Caja ni tablas financieras.
- Detecta diferencias historicas sin corregirlas.
- Resultado 9C-A: `OK: 34`, `WARNING: 2`, `ERROR: 0`.
- Health checker actualizado para validar que el preflight 9C-A mantiene contrato
  read-only.
- Health general: `OK: 282`, `WARNING: 25`, `ERROR: 0`.

## Auditoria Fase 9C-B-0

Resultado: contrato documental read-only sin cambios operativos.

- Documento creado:
  `docs/fase_9C_B_0_contrato_pantalla_arqueo_metodos_pago_readonly.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST ni
  migraciones.
- Define una ruta candidata futura, pero no la registra.
- Exige filtros GET, hotel actual desde sesion y ausencia total de acciones de Caja.
- Prohibe botones de cerrar, reabrir, recalcular, corregir, ajustar, compensar, editar
  movimiento y cambiar metodo.
- Prohibe tocar permisos, auth, Caja operativa, cortes, movimientos, CxC, CxP,
  reservaciones, compras, proveedores, facturacion, PWA/offline y `/api/sync`.
- Obliga a usar tokens `--brand-*` en la futura UI hotelera, no `--ms-*`.

## Auditoria Fase 9C-B-A

Resultado: pantalla GET/read-only implementada sin cambios operativos.

- Documento creado:
  `docs/fase_9C_B_A_pantalla_arqueo_metodos_pago_readonly.md`.
- Ruta agregada:
  `GET /caja/arqueo-metodos`.
- No se agrega `POST /caja/arqueo-metodos`.
- Controlador usa `ArqueoMetodosPago::reporteReadOnlyPorHotel()` y filtros GET.
- Modelo `ArqueoMetodosPago` abre transaccion read-only, filtra por hotel actual y
  cierra con rollback.
- Vista `caja/arqueo_metodos.php` usa `method="get"`, etiqueta `Solo lectura`,
  tokens `--brand-*`, sin `--ms-*`, sin CSRF y sin `hotel_id` editable.
- No hay botones ni flujos para cerrar, reabrir, recalcular, corregir, ajustar,
  compensar, editar movimiento o cambiar metodo.
- Preflight 9C-A/9C-B-A:
  `OK: 39`, `WARNING: 2`, `ERROR: 0`.
- Health general:
  `OK: 287`, `WARNING: 25`, `ERROR: 0`.
- `/api/sync` se mantiene fuera de alcance y validado como bloqueado.
- QA manual con sesion validada por el usuario: la pantalla se ve correctamente.

## Auditoria Fase 9C-B-F

Resultado: cierre documental sin cambios operativos.

- Documento creado:
  `docs/fase_9C_B_F_cierre_pantalla_arqueo_metodos_pago.md`.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Consolida evidencia automatica y QA manual de la pantalla
  `GET /caja/arqueo-metodos`.
- Mantiene el bloque como diagnostico read-only.
- No autoriza cierres, reaperturas, recalculos, correcciones, ajustes, edicion de
  movimientos, cambios de metodo, permisos nuevos, PWA/offline ni `/api/sync`.

## Auditoria Fase 10A-0

Resultado: contrato documental read-only sin cambios operativos.

- Documento creado:
  `docs/fase_10A_0_contrato_tablero_ejecutivo_readonly.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST ni
  migraciones.
- Define una futura ruta candidata `GET /reportes/ejecutivo`, pero no la registra.
- Consolida alcance para KPIs de operacion, finanzas, Caja, CxC, CxP, inventario,
  tareas, personal y documentos.
- Advierte que el reporte gerencial diario existente no debe tratarse como read-only
  puro si marca notificaciones como vistas.
- Prohibe escrituras por lectura simple, pagos, cobros, reversiones, cierres de corte,
  recalculos, ajustes, exports/links sin contrato, permisos nuevos, PWA/offline y
  `/api/sync`.

## Auditoria Fase 10A-A

Resultado: preflight CLI/read-only sin superficie web nueva.

- Archivo nuevo:
  `src/tools/saas/preflight_tablero_ejecutivo.php`.
- Documento nuevo:
  `docs/fase_10A_A_preflight_tablero_ejecutivo_readonly.md`.
- Health extendido:
  `src/tools/saas/health_check_fase_1a.php`.
- No agrega rutas, controladores, modelos, vistas, formularios, migraciones ni datos.
- Verifica que no exista `POST /reportes/ejecutivo`.
- Mantiene `GET /reportes/ejecutivo` sin registrar en esta fase.
- Usa transaccion read-only y rollback.
- Detecta warning controlado por escritura de notificaciones en
  `GET /reportes/gerencial-diario`.
- Preflight: `OK: 79`, `WARNING: 5`, `ERROR: 0`.
- Health general: `OK: 290`, `WARNING: 26`, `ERROR: 0`.
- PWA/offline, IndexedDB, cache names y `/api/sync` quedan fuera de alcance.

## Auditoria Fase 10A-B-0

Resultado: contrato documental de pantalla read-only, sin cambios operativos.

- Documento nuevo:
  `docs/fase_10A_B_0_contrato_pantalla_tablero_ejecutivo_readonly.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST,
  migraciones ni datos.
- Reserva `GET /reportes/ejecutivo` como ruta futura unica.
- Prohibe `POST /reportes/ejecutivo`, endpoints de escritura, exports/links sin
  contrato y acciones operativas.
- Exige para futura UI tokens `--brand-*`, cero `--ms-*`, etiqueta `Solo lectura`,
  filtros GET y ausencia de botones de pagar/cobrar/revertir/cerrar/recalcular.
- Exige para futuro backend lector dedicado, hotel actual, joins seguros con
  `huespedes`, degradacion de `ledger_laboral` y cero archivado automatico de
  notificaciones.
- No autoriza permisos nuevos, auth, PWA/offline, IndexedDB, cache names ni
  `/api/sync`.

## Auditoria Fase 10A-B-A

Resultado: pantalla GET/read-only implementada sin superficie POST.

- Ruta nueva:
  `GET /reportes/ejecutivo`.
- No existe `POST /reportes/ejecutivo`.
- Controlador:
  `ReportesController::ejecutivoAction`.
- Lector:
  `TableroEjecutivo::reporteReadOnlyPorHotel()`.
- Vista:
  `reportes/ejecutivo.php`.
- El controlador no llama `archivarNotificacionReporteGerencialVisto`.
- El lector usa `START TRANSACTION READ ONLY`, `rollBack` y filtros por hotel actual.
- La vista usa filtros GET, etiqueta `Solo lectura`, tokens `--brand-*`, cero
  `--ms-*`, sin CSRF y sin `hotel_id` editable.
- Preflight: `OK: 86`, `WARNING: 5`, `ERROR: 0`.
- Health general: `OK: 294`, `WARNING: 26`, `ERROR: 0`.
- HTTP sin sesion: `303` hacia `/login`.
- PWA/offline, IndexedDB, cache names y `/api/sync` quedan fuera de alcance.

## Auditoria Fase 10A-B-F

Resultado: cierre documental tras QA manual validada.

- Documento nuevo:
  `docs/fase_10A_B_F_cierre_tablero_ejecutivo.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST,
  migraciones ni datos.
- Confirma QA manual aprobada para `GET /reportes/ejecutivo`.
- Mantiene la pantalla como GET/read-only.
- Mantiene prohibidas acciones operativas, exports/links sin contrato, permisos nuevos,
  PWA/offline, IndexedDB, cache names y `/api/sync`.
- Siguiente riesgo recomendado para contrato independiente:
  `/reportes/gerencial-diario` archiva notificaciones al abrirse.

## Auditoria Fase 10B-0

Resultado: contrato documental para separar reporte gerencial y notificaciones.

- Documento nuevo:
  `docs/fase_10B_0_contrato_reporte_gerencial_notificaciones.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST,
  migraciones ni datos.
- Identifica como riesgo que `gerencialDiarioAction` y `gerencialDiarioPdfAction`
  archivan notificaciones por lectura directa.
- Define como contrato futuro que `GET /reportes/gerencial-diario` y
  `GET /reportes/gerencial-diario/pdf` no deben escribir en `notificaciones`.
- Mantiene cualquier escritura de notificacion dentro de un flujo controlado, scoped
  por hotel y con autorizacion explicita.
- Mantiene fuera de alcance permisos/auth, Caja, cortes, pagos, cobros, CxC, CxP,
  inventario, tareas, documentos, PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 10B-A

Resultado: separacion funcional implementada y QA manual validada.

- Documento nuevo:
  `docs/fase_10B_A_separacion_reporte_gerencial_notificaciones.md`.
- `ReportesController::gerencialDiarioAction` queda sin archivado automatico.
- `ReportesController::gerencialDiarioPdfAction` queda sin archivado automatico.
- `ReportesController` ya no contiene `archivarNotificacionReporteGerencialVisto`.
- El archivado de notificaciones de reporte gerencial queda en el flujo controlado de
  `NotificacionController::abrirAction`.
- Preflight y health validan que HTML/PDF directos no cambien notificaciones.
- No se agregan rutas, POST, formularios, migraciones, datos ni permisos.
- PWA/offline, IndexedDB, cache names y `/api/sync` quedan fuera de alcance.

## Auditoria Fase 10B-F

Resultado: cierre documental tras QA manual validada.

- Documento nuevo:
  `docs/fase_10B_F_cierre_reporte_gerencial_notificaciones.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST,
  migraciones ni datos.
- Confirma QA manual aprobada para la separacion de reporte gerencial y
  notificaciones.
- Mantiene HTML/PDF gerencial como lectura directa.
- Mantiene el archivado de notificaciones solo en flujo controlado.
- PWA/offline, IndexedDB, cache names y `/api/sync` quedan fuera de alcance.

## Auditoria Fase 11A-0

Resultado: contrato documental de perfil operativo de huesped read-only.

- Documento nuevo:
  `docs/fase_11A_0_contrato_perfil_huesped_readonly.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST,
  migraciones ni datos.
- Define como riesgo principal tocar vistas grandes de huespedes sin aislar el bloque
  read-only.
- Mantiene prohibido cambiar `action`, `method`, `name`, CSRF, hidden inputs o submits
  de formularios existentes.
- Prohibe crear CxC, cobros, pagos, check-in/check-out, cambios de reservaciones,
  documentos, tareas, permisos/auth, PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 11A-A

Resultado: perfil operativo de huesped implementado como lectura calculada al vuelo.

- Documento nuevo:
  `docs/fase_11A_A_perfil_huesped_readonly.md`.
- No agrega rutas, POST, migraciones, datos ni permisos.
- Reutiliza `GET /huespedes/{id}`.
- `HuespedController::verAction` sigue consultando el huesped con `findForHotel`.
- `Huesped::perfilOperativoReadOnlyPorHotel()` valida pertenencia por `hotel_id`.
- Las metricas de reservaciones, vehiculos, CxC y documentos se calculan en memoria
  desde fuentes existentes y no se persisten.
- El bloque visual `Perfil operativo` no contiene formularios, acciones, enlaces ni
  CSRF.
- No se cambia `action`, `method`, `name`, CSRF, hidden inputs ni submits de
  formularios existentes.
- No se toca Caja, cobros, pagos, check-in/check-out, cambios de reservaciones,
  documentos, tareas, permisos/auth, PWA/offline, IndexedDB, cache names ni
  `/api/sync`.
- QA manual validada por el usuario.

## Auditoria Fase 11A-F

Resultado: cierre documental tras QA manual validada.

- Documento nuevo:
  `docs/fase_11A_F_cierre_perfil_huesped_readonly.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST,
  migraciones ni datos.
- Confirma QA manual aprobada para `GET /huespedes/{id}` con bloque `Perfil
  operativo`.
- Mantiene el perfil como lectura calculada al vuelo.
- Mantiene prohibidas acciones operativas, CxC nueva, cobros, pagos,
  check-in/check-out, cambios de reservaciones, documentos, tareas, permisos/auth,
  PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 5E-0

Resultado: contrato documental de pagos laborales con Caja, sin superficie operativa
nueva.

- Documento nuevo:
  `docs/fase_5E_0_contrato_pagos_laborales_caja.md`.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, POST,
  migraciones ni datos.
- No crea movimientos de Caja ni categorias de Caja.
- No registra pagos laborales reales.
- No reutiliza `trabajador_pagos` como pago real.
- Define una futura entidad independiente para pagos laborales con Caja.
- Exige para una futura implementacion: backup, transaccion, corte abierto del hotel,
  referencia unica, egreso de Caja, auditoria, token de un solo uso y prueba rollback.
- Mantiene fuera de alcance abonos/liquidaciones automaticas, nomina automatica,
  reversiones, permisos nuevos, PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 5E-A

Resultado: preflight CLI/read-only de pagos laborales con Caja.

- Documento nuevo:
  `docs/fase_5E_A_preflight_pagos_laborales_caja.md`.
- Herramienta nueva:
  `src/tools/saas/preflight_personal_pagos_caja.php`.
- Health actualizado:
  `src/tools/saas/health_check_fase_1a.php`.
- No agrega rutas, controladores, modelos operativos, vistas, formularios, POST,
  migraciones ni datos.
- Usa transaccion `READ ONLY` y termina sin escrituras.
- Valida que `trabajador_pagos` no se mezcle con pagos reales.
- Valida que no existan rutas ni servicio prematuros de pago laboral con Caja.
- Valida que no existan categorias/movimientos de Caja con nomina o pago laboral.
- Resultado automatico: preflight `ERROR: 0`; health general `ERROR: 0`.
- Advertencia no bloqueante: no hay trabajadores activos para QA futura de pago real.
- Mantiene fuera de alcance abonos/liquidaciones automaticas, nomina automatica,
  reversiones, permisos nuevos, PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 5E-B-0

Resultado: contrato documental de migracion aditiva para pagos laborales con Caja.

- Documento nuevo:
  `docs/fase_5E_B_0_contrato_migracion_pagos_laborales_caja.md`.
- No agrega SQL, migracion, tabla, rutas, controladores, modelos operativos, vistas,
  formularios, POST, datos ni movimientos de Caja.
- Define `trabajador_pagos_caja` como tabla futura independiente.
- Reafirma que `trabajador_pagos` no debe alterarse ni reutilizarse como pago real.
- Define que la futura migracion no debe crear categorias de Caja ni pagos
  historicos.
- Define rollback futuro solo si la tabla existe y esta vacia.
- Exige backup, SHA256, preflight 5E-A y health `ERROR: 0` antes de cualquier
  migracion real.
- Mantiene fuera de alcance abonos/liquidaciones automaticas, nomina automatica,
  reversiones, permisos nuevos, PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 5E-B-A

Resultado: migracion aditiva aplicada para tabla independiente de pagos laborales con
Caja.

- Documento nuevo:
  `docs/fase_5E_B_A_migracion_pagos_laborales_caja.md`.
- Migracion nueva:
  `migrations/20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql`.
- Backup previo verificado:
  `backups/medisoft_hoteles_import_before_5e_b_a_trabajador_pagos_caja_20260619_220444.sql`.
- SHA256:
  `2E279999DA95C0216942F1FE480E5E43E96AAE42A06DA7C9FD83633BC53898BC`.
- `trabajador_pagos_caja` queda creada y vacia.
- La migracion no inserta pagos historicos.
- La migracion no toca `trabajador_pagos`.
- La migracion no crea movimientos de Caja ni categorias de Caja.
- Preflight 5E y health general validan la tabla, el registro en `migrations` y la
  ausencia de pagos reales.
- Mantiene fuera de alcance abonos/liquidaciones automaticas, nomina automatica,
  reversiones, permisos nuevos, PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 5E-C-0

Resultado: contrato documental para simulador GET/read-only de pago laboral con Caja.

- Documento nuevo:
  `docs/fase_5E_C_0_contrato_simulador_pago_laboral_caja.md`.
- No agrega rutas, controladores, modelos, vistas, formularios, POST, servicios,
  migraciones ni datos.
- No registra pagos laborales reales.
- No crea movimientos de Caja ni categorias de Caja.
- Define como futura superficie candidata:
  `GET /trabajadores/pagos-caja/simulador`.
- Exige que la futura pantalla sea read-only, con filtros GET y sin token de pago.
- Mantiene fuera de alcance abonos/liquidaciones automaticas, nomina automatica,
  reversiones, permisos nuevos, PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 5E-C-A

Resultado: simulador GET/read-only de pago laboral con Caja implementado.

- Documento nuevo:
  `docs/fase_5E_C_A_simulador_pago_laboral_caja.md`.
- Ruta nueva:
  `GET /trabajadores/pagos-caja/simulador`.
- Vista nueva:
  `src/app/views/trabajadores/simulador_pago_caja.php`.
- Enlaces GET desde listado y ficha de trabajadores.
- Modelo/controlador agregan solo lectura para diagnosticar saldo laboral estimado,
  corte abierto, referencia y bloqueos.
- No hay POST, CSRF, token, servicio transaccional, pago real, reversion ni
  movimiento de Caja.
- Preflight 5E valida `OK: 41`, `WARNING: 0`, `ERROR: 0`.
- Health general valida `OK: 309`, `WARNING: 26`, `ERROR: 0`.
- Mantiene fuera de alcance abonos/liquidaciones automaticas, nomina automatica,
  reversiones, permisos nuevos, PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 5E-C-F

Resultado: cierre documental del simulador con QA manual validada.

- Documento nuevo: `docs/fase_5E_C_F_cierre_simulador_pago_laboral_caja.md`.
- El usuario confirmo que la prueba manual paso correctamente.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Mantiene el simulador como GET/read-only, sin pago real.

## Auditoria Fase 5E-D-0

Resultado: contrato documental del servicio futuro de pago laboral con Caja.

- Documento nuevo: `docs/fase_5E_D_0_contrato_servicio_pago_laboral_caja.md`.
- No agrega codigo, rutas POST, formularios, migraciones, servicios ni escrituras.
- Define `TrabajadorPagoCajaService`.
- Define saldo disponible, validaciones, locks, transaccion, token de un solo uso, auditoria y prueba rollback futura.
- Mantiene fuera de alcance pago real, reversion, abonos/liquidaciones automaticas, nomina automatica, permisos nuevos, PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 5E-D-A

Resultado: pago laboral con Caja controlado implementado.

- Documento nuevo:
  `docs/fase_5E_D_A_pago_laboral_caja_controlado.md`.
- Servicio nuevo:
  `src/app/services/TrabajadorPagoCajaService.php`.
- Ruta POST nueva:
  `POST /trabajadores/{id}/registrar-pago-caja`.
- Guardas: autenticacion heredada, hotel actual, modulo `usuarios`, permiso `usuarios.edit`, modulo `caja`, CSRF y token de un solo uso.
- Servicio usa transaccion, locks `FOR UPDATE`, referencia unica y bloqueo de monto mayor al saldo disponible.
- Registra `trabajador_pagos_caja`, `movimientos_caja` categoria `Pago laboral` y auditoria.
- Rollback automatizado valida inserciones temporales y revierte sin persistencia.
- Preflight 5E valida `OK: 41`, `WARNING: 0`, `ERROR: 0`.
- Health general valida `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- Mantiene fuera de alcance reversion, nomina automatica, abonos/liquidaciones automaticas, PWA/offline, IndexedDB, cache names y `/api/sync`.

## Auditoria Fase 5E-D-F

Resultado: cierre documental del pago laboral con Caja con QA manual validada.

- Documento nuevo:
  `docs/fase_5E_D_F_cierre_pago_laboral_caja.md`.
- El usuario confirmo que la prueba manual paso correctamente.
- La ficha quedo validada con saldo disponible para pago separado del saldo bruto
  informativo.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, migraciones
  ni escrituras.
- Mantiene fuera de alcance reversion, pagos masivos, nomina automatica,
  abonos/liquidaciones automaticas, permisos/auth, PWA/offline, IndexedDB, cache names
  y `/api/sync`.

## Auditoria Fase 5E-P-0

Resultado: contrato documental de trazabilidad fuerte futura para pagos desde
snapshot de pre-nomina.

- Documento nuevo:
  `docs/fase_5E_P_0_contrato_trazabilidad_pago_snapshot_prenomina.md`.
- No agrega codigo, rutas, controladores, modelos, servicios, vistas,
  formularios, migraciones, permisos, datos ni escrituras.
- Define columnas futuras nullable en `trabajador_pagos_caja`:
  `nomina_periodo_id` y `nomina_periodo_detalle_id`.
- Prohibe backfill automatico de pagos historicos.
- Exige validacion de hotel, periodo, detalle, trabajador y snapshot aprobado.
- Mantiene fuera de alcance nomina oficial, CFDI, timbrado, dispersion, pago
  masivo, liquidaciones automaticas, PWA/offline, IndexedDB, cache names y
  `/api/sync`.

## Auditoria Fase 5E-P-B

Resultado: guardas CLI/read-only para trazabilidad fuerte futura de pagos desde
snapshot.

- Documento nuevo:
  `docs/fase_5E_P_B_guardas_trazabilidad_pago_snapshot.md`.
- Checkers actualizados:
  `src/tools/saas/preflight_personal_pagos_caja.php` y
  `src/tools/saas/health_check_fase_1a.php`.
- No crea migraciones, no altera DB, no toca rutas, controladores, modelos,
  servicios, vistas, Caja, snapshots, storage, PWA/offline ni `/api/sync`.
- Detecta implementaciones futuras parciales o inconsistentes:
  columnas incompletas, indices faltantes, relaciones parciales y cruces de
  hotel/periodo/detalle/trabajador.
- Resultado automatico local: preflight `OK: 66`, `WARNING: 0`, `ERROR: 0`;
  health `OK: 321`, `WARNING: 25`, `ERROR: 0`.

## Auditoria Fase 5E-P-A

Resultado: trazabilidad fuerte de pagos desde snapshot implementada en local.

- Documento nuevo:
  `docs/fase_5E_P_A_trazabilidad_pago_snapshot_prenomina.md`.
- Migracion local:
  `migrations/20260622_001_fase_5e_p_a_trazabilidad_pago_snapshot.sql`.
- Backup previo:
  `backups/db/20260622_102558_medisoft_hoteles_import_pre_5e_p_a.sql`.
- Se agregan columnas nullable en `trabajador_pagos_caja`:
  `nomina_periodo_id` y `nomina_periodo_detalle_id`.
- Se agregan indices y FKs restrictivas hacia snapshots persistentes.
- No se ejecuta backfill historico.
- `TrabajadorPagoCajaService` valida periodo/detalle juntos, hotel, trabajador,
  snapshot aprobado y detalle `por_pagar`.
- `TrabajadorNominaSnapshotPagoService` delega el pago individual con IDs de
  periodo y detalle.
- Prueba rollback confirma pago temporal trazado y sin persistencia.
- Preflight pagos laborales Caja: `OK: 72`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 327`, `WARNING: 25`, `ERROR: 0`.
- Mantiene fuera de alcance nomina oficial, CFDI, timbrado, dispersion, pago
  masivo, liquidaciones automaticas, PWA/offline, IndexedDB, cache names y
  `/api/sync`.

## Auditoria Fase 5E-P-F

Resultado: cierre documental tras QA manual validada de trazabilidad snapshot.

- Documento nuevo:
  `docs/fase_5E_P_F_cierre_trazabilidad_pago_snapshot_prenomina.md`.
- El usuario confirmo que la prueba manual paso.
- Pago auditado por consulta local:
  `trabajador_pagos_caja.id = 9`, referencia `TEST-5EPA-001`.
- El pago conserva trazabilidad fuerte:
  `nomina_periodo_id = 4` y `nomina_periodo_detalle_id = 4`.
- Movimiento Caja auditado:
  `movimientos_caja.id = 1510`, categoria `Pago laboral`, corte `#238`.
- Relaciones parciales posteriores: `0`.
- Pagos trazados posteriores: `1`.
- No se toco produccion, PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Auditoria Fase 5E-Q-A

Resultado: conciliacion read-only de pagos trazados desde snapshot implementada
en local.

- Documento nuevo:
  `docs/fase_5E_Q_A_conciliacion_pagos_snapshot_prenomina.md`.
- Rutas GET nuevas:
  - `/trabajadores/nomina/periodos/pagos-snapshot`.
  - `/trabajadores/nomina/periodos/pagos-snapshot/exportar`.
- No hay rutas POST nuevas.
- La vista no contiene CSRF ni formularios POST porque no escribe datos.
- El CSV se genera en memoria.
- La consulta cruza pago laboral, snapshot, detalle, movimiento, corte y caja
  para marcar `OK` o `Revisar`.
- Pago auditado:
  `trabajador_pagos_caja.id = 9`, snapshot `#4`, detalle `#4`,
  movimiento Caja `#1510`, conciliacion `ok`.
- Preflight pagos laborales Caja: `OK: 74`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 328`, `WARNING: 25`, `ERROR: 0`.
- No se toco produccion, PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Auditoria Fase 5E-Q-F

Resultado: cierre documental tras QA manual validada de conciliacion pagos
snapshot.

- Documento nuevo:
  `docs/fase_5E_Q_F_cierre_conciliacion_pagos_snapshot_prenomina.md`.
- El usuario confirmo que la pantalla funciona correctamente.
- El usuario confirmo que aparece lo esperado.
- Pantalla validada:
  `/trabajadores/nomina/periodos/pagos-snapshot?periodo_id=4`.
- Pago validado visualmente:
  `trabajador_pagos_caja.id = 9`, referencia `TEST-5EPA-001`.
- Trazabilidad visible:
  snapshot `#4`, detalle `#4`, movimiento Caja `#1510`,
  conciliacion `OK`.
- No se toco produccion, PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Auditoria Fase 5E-R-0

Resultado: contrato documental para auditoria consolidada read-only de
Personal/Nomina.

- Documento nuevo:
  `docs/fase_5E_R_0_contrato_auditoria_consolidada_nomina.md`.
- No agrega codigo, rutas, controladores, modelos, servicios, vistas,
  migraciones, permisos, datos, storage, Caja, PWA/offline ni `/api/sync`.
- Define una futura pantalla GET/read-only para cruzar snapshots, detalles,
  trabajadores, pagos Caja, reversiones, movimientos, cortes, referencias y
  conciliacion.
- Exige que toda lectura futura quede scoped por `hotel_id`.
- Mantiene fuera de alcance POST, pago masivo, nomina oficial, CFDI, timbrado,
  dispersion, backfill, recalculos, storage, PWA/offline y `/api/sync`.
- Cualquier implementacion futura 5E-R-A requiere autorizacion explicita para
  rutas, controlador, modelo read-only, vista, exportador CSV y checkers.

## Auditoria Fase 5E-R-A

Resultado: auditoria consolidada de nomina GET/read-only implementada en local.

- Documento nuevo:
  `docs/fase_5E_R_A_auditoria_consolidada_nomina.md`.
- Rutas GET nuevas:
  - `/trabajadores/nomina/auditoria`.
  - `/trabajadores/nomina/auditoria/exportar`.
- No hay rutas POST nuevas.
- La vista no contiene CSRF ni formularios POST.
- El CSV se genera en memoria.
- La consulta consolida detalle congelado de snapshot con pagos Caja trazados,
  reversiones, referencias y saldo de auditoria por `hotel_id`.
- Caso local auditado por modelo:
  `hotel_id = 4`, `periodo_id = 4`, trabajador `Panfilo Hernandez`, pagos Caja
  `$1.00`, saldo auditoria `$99.00`, estado `parcial`.
- Preflight pagos laborales Caja: `OK: 76`, `WARNING: 0`, `ERROR: 0`.
- Health general: 5E-R-A OK; quedan `ERROR: 3` historicos de Compras/CxP fuera
  de esta fase.
- No se toco produccion, migraciones, permisos, auth, storage, PWA/offline,
  IndexedDB, cache names ni `/api/sync`.
- No se registro pago, no se revirtio pago, no se modifico Caja, no se modifico
  snapshot y no se genero nomina oficial.

## Auditoria Fase 5E-R-F

Resultado: cierre documental tras QA manual validada de auditoria consolidada
de nomina.

- Documento nuevo:
  `docs/fase_5E_R_F_cierre_auditoria_consolidada_nomina.md`.
- El usuario confirmo que la pantalla quedo y paso todas las pruebas.
- Pantalla validada:
  `/trabajadores/nomina/auditoria?periodo_id=4`.
- Datos visibles validados:
  - detalles `1`;
  - trabajadores `1`;
  - pagos Caja `$1.00`;
  - saldo auditoria `$99.00`;
  - estado `Parcial`;
  - trabajador `Panfilo Hernandez`;
  - periodo `#4 Periodo seleccionado`.
- El cierre no agrega rutas, POST, migraciones ni escrituras.
- No se toco produccion, PWA/offline, IndexedDB, cache names ni `/api/sync`.
- No se registro pago, no se revirtio pago, no se modifico Caja, no se modifico
  snapshot y no se genero nomina oficial.

## Auditoria Fase 5E-S-0

Resultado: contrato documental para expediente administrativo read-only de
nomina.

- Documento nuevo:
  `docs/fase_5E_S_0_contrato_expediente_administrativo_nomina.md`.
- No agrega codigo, rutas, controladores, modelos, servicios, vistas,
  formularios, migraciones, permisos, datos ni escrituras.
- Define una futura capa GET/read-only para agrupar evidencias de pre-nomina,
  snapshots, pagos Caja, reversiones, auditoria consolidada, documentos,
  eventos y bloqueos.
- Exige que toda lectura futura quede scoped por `hotel_id`.
- Mantiene fuera de alcance nomina oficial, CFDI, timbrado, dispersion, pago
  masivo, polizas contables, liquidaciones automaticas, PWA/offline,
  IndexedDB, cache names y `/api/sync`.
- No se toco produccion, Caja, cortes, movimientos de Caja, snapshots, storage,
  PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Auditoria Fase 5E-S-A

Resultado: expediente administrativo de nomina GET/read-only implementado en
local.

- Documento nuevo:
  `docs/fase_5E_S_A_expediente_administrativo_nomina.md`.
- Rutas GET nuevas:
  - `/trabajadores/nomina/expediente`.
  - `/trabajadores/nomina/expediente/exportar`.
- No hay rutas POST nuevas.
- La vista no contiene CSRF ni formularios POST.
- El CSV se genera en memoria.
- La consulta deriva el expediente desde auditoria consolidada y marca estados
  administrativos y bloqueos por `hotel_id`.
- Caso local auditado por modelo:
  `hotel_id = 4`, `periodo_id = 4`, trabajador `Panfilo Hernandez`, estado
  expediente `con_pendientes`, pagos Caja `$1.00`, saldo auditoria `$99.00`,
  bloqueos `0`.
- Preflight pagos laborales Caja: `OK: 78`, `WARNING: 0`, `ERROR: 0`.
- Health general: 5E-S-A OK; quedan `ERROR: 3` historicos de Compras/CxP fuera
  de esta fase.
- No se toco produccion, migraciones, permisos, auth, storage, PWA/offline,
  IndexedDB, cache names ni `/api/sync`.
- No se registro pago, no se revirtio pago, no se modifico Caja, no se modifico
  snapshot y no se genero nomina oficial.

## Auditoria Fase 5E-T-0

Resultado: contrato documental para frontera de nomina oficial.

- Documento nuevo:
  `docs/fase_5E_T_0_contrato_frontera_nomina_oficial.md`.
- No agrega codigo, rutas, controladores, modelos, servicios, vistas,
  formularios, migraciones, permisos, datos ni escrituras.
- Define que el bloque 5E permanece como nomina administrativa.
- Mantiene fuera de alcance CFDI laboral, timbrado, dispersion bancaria, pago
  masivo, recibos fiscales, UUID fiscal laboral, polizas contables y calculo
  fiscal patronal.
- No se toco produccion, Caja, cortes, movimientos de Caja, snapshots, storage,
  PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Auditoria Fase 5E-T-A

Resultado: preflight CLI/read-only de frontera de nomina oficial implementado
en local.

- Documento nuevo:
  `docs/fase_5E_T_A_preflight_frontera_nomina_oficial.md`.
- Herramienta nueva:
  `src/tools/saas/preflight_frontera_nomina_oficial.php`.
- No hay rutas nuevas.
- No hay migraciones nuevas.
- El checker revisa rutas, simbolos Personal/Nomina, migraciones montadas,
  objetos DB locales y bloqueo de `/api/sync`.
- QA tecnica local:
  - lint PHP OK;
  - preflight `OK: 10`, `WARNING: 0`, `ERROR: 0`.
- Confirmo que no hay superficie activa de nomina oficial, CFDI laboral,
  timbrado, dispersion ni pago masivo.
- Confirmo que `/api/sync` mantiene `sync_temporarily_disabled` y HTTP 423.
- No se toco produccion, permisos, auth, storage, PWA/offline, IndexedDB, cache
  names ni `/api/sync`.
- No se registro pago, no se revirtio pago, no se modifico Caja, no se modifico
  snapshot y no se genero nomina oficial.

## Auditoria Fase 5E-T-B

Resultado: integracion del preflight de frontera de nomina oficial al health
general.

- Documento nuevo:
  `docs/fase_5E_T_B_health_frontera_nomina_oficial.md`.
- Archivo modificado:
  `src/tools/saas/health_check_fase_1a.php`.
- El health reconoce el preflight 5E-T-A y valida que declare guardas de rutas,
  simbolos Personal/Nomina, DB read-only, `/api/sync`, CFDI, timbrado,
  dispersion y pago masivo.
- QA tecnica local:
  - lint PHP OK;
  - health filtrado confirma 5E-T-A como OK;
  - health global conserva `ERROR: 3` historicos de Compras/CxP fuera de esta
    fase.
- No se toco produccion, rutas, modelos, controladores, vistas, migraciones,
  permisos, auth, storage, PWA/offline, IndexedDB, cache names ni `/api/sync`.
- No se registro pago, no se revirtio pago, no se modifico Caja, no se modifico
  snapshot y no se genero nomina oficial.

## Auditoria Fase 5E-U-0

Resultado: contrato documental para suite QA nomina administrativa.

- Documento nuevo:
  `docs/fase_5E_U_0_contrato_suite_qa_nomina_administrativa.md`.
- No agrega codigo, rutas, controladores, modelos, servicios, vistas,
  formularios, migraciones, permisos, datos ni escrituras.
- Define una suite CLI/local/read-only para consolidar QA tecnica de
  Personal/Nomina administrativa.
- Mantiene fuera de alcance nomina oficial, CFDI laboral, timbrado, dispersion,
  pago masivo, storage, PWA/offline, IndexedDB, cache names y `/api/sync`.
- No se toco produccion, Caja, cortes, movimientos de Caja ni snapshots.

## Auditoria Fase 5E-U-A

Resultado: suite CLI/read-only de QA nomina administrativa implementada en
local.

- Documento nuevo:
  `docs/fase_5E_U_A_suite_qa_nomina_administrativa.md`.
- Herramienta nueva:
  `src/tools/saas/preflight_nomina_administrativa_suite.php`.
- No hay rutas nuevas.
- No hay migraciones nuevas.
- La suite ejecuta preflights actuales de pagos laborales/snapshots y frontera
  de nomina oficial, y valida health 5E-T-B.
- El preflight de ledger queda como historico no bloqueante porque su contrato
  original antecede a rutas 5E autorizadas.
- QA tecnica local:
  - lint PHP OK;
  - suite `OK: 3`, `WARNING: 1`, `ERROR: 0`;
  - resultado `PASS_WITH_WARNINGS_ALLOWED`.
- No se toco produccion, rutas, modelos, controladores, vistas, migraciones,
  permisos, auth, storage, PWA/offline, IndexedDB, cache names ni `/api/sync`.
- No se registro pago, no se revirtio pago, no se modifico Caja, no se modifico
  snapshot y no se genero nomina oficial.

## Auditoria Fase 5E-U-B

Resultado: health general saneado para Compras/CxP redisenadas.

- Documento nuevo:
  `docs/fase_5E_U_B_health_baseline_compras_cxp.md`.
- Archivo modificado:
  `src/tools/saas/health_check_fase_1a.php`.
- No hay rutas nuevas.
- No hay migraciones nuevas.
- No se modificaron vistas de Compras ni CxP.
- El health ahora reconoce las marcas actuales de:
  - detalle Compras mediante `$compraUrl`;
  - boton de recepcion `cp-btn-receive` o `data-receive-form="1"`;
  - detalle CxP mediante `$cuentaUrl`;
  - simulador CxP con etiqueta `Solo consulta`.
- QA tecnica local:
  - lint PHP OK;
  - health `OK: 328`, `WARNING: 28`, `ERROR: 0`;
  - suite 5E-U-A conserva `ERROR: 0`.
- No se toco produccion, rutas, modelos, controladores, vistas, migraciones,
  permisos, auth, storage, PWA/offline, IndexedDB, cache names ni `/api/sync`.
- No se registro pago, no se revirtio pago, no se modifico Caja, no se modifico
  snapshot y no se genero nomina oficial.

## Auditoria Fase 3A-B

Resultado: health general reconoce Proveedores Fase 3A redisenado.

- Documento nuevo:
  `docs/fase_3A_B_health_baseline_proveedores.md`.
- Archivo modificado:
  `src/tools/saas/health_check_fase_1a.php`.
- No hay rutas nuevas.
- No hay migraciones nuevas.
- No se modificaron vistas de Proveedores.
- El health ahora reconoce:
  - enlace de detalle mediante `$provUrl`;
  - etiqueta `Solo consulta` como marca read-only;
  - links GET a compras y reporte;
  - ficha sin formularios nuevos ni CSRF.
- QA tecnica local:
  - lint PHP OK;
  - Proveedores Fase 3A filtrado en health: OK.
- No se toco produccion, rutas, modelos, controladores, vistas, migraciones,
  permisos, auth, storage, PWA/offline, IndexedDB, cache names ni `/api/sync`.
- No se escribieron compras, CxP, pagos, Caja ni inventario.

## Auditoria Fase NP-F-B

Resultado: health general reconoce Personal NP-F-A/5E-D-A redisenado.

- Documento nuevo:
  `docs/fase_NP_F_B_health_baseline_personal.md`.
- Archivo modificado:
  `src/tools/saas/health_check_fase_1a.php`.
- No hay rutas nuevas.
- No hay migraciones nuevas.
- No se modificaron vistas de Personal.
- El health ahora reconoce:
  - enlace de detalle historico con `url('trabajadores/' . (int...)`;
  - enlace de detalle actual mediante `$tUrl`;
  - reporte read-only sin POST ni CSRF;
  - ledger manual con CSRF;
  - pago laboral con Caja protegido por `pago_token`.
- QA tecnica local:
  - lint PHP OK;
  - Personal NP-F-A/5E-D-A filtrado en health: OK.
- No se toco produccion, rutas, modelos, controladores, vistas, migraciones,
  permisos, auth, storage, PWA/offline, IndexedDB, cache names ni `/api/sync`.
- No se escribieron pagos, reversiones, Caja, nomina oficial ni snapshots.

## Auditoria Fase TLM-I/J-B

Resultado: health general reconoce Tareas TLM-I-A/TLM-J-A redisenadas.

- Documento nuevo:
  `docs/fase_TLM_I_J_B_health_baseline_tareas.md`.
- Archivo modificado:
  `src/tools/saas/health_check_fase_1a.php`.
- No hay rutas nuevas.
- No hay migraciones nuevas.
- No se modificaron vistas de Tareas.
- El health ahora reconoce:
  - enlace de detalle actual mediante `$tareaId`;
  - reporte con titulo `Reporte de tareas`;
  - seccion de eventos actual como `Historial` + `$eventos`;
  - reporte/agenda sin POST ni CSRF;
  - alta/asignacion/estados manuales con CSRF;
  - ausencia de acciones directas de habitacion y Caja.
- QA tecnica local:
  - lint PHP OK;
  - TLM-I-A/TLM-J-A filtrado en health: OK.
- No se toco produccion, rutas, modelos, controladores, vistas, migraciones,
  permisos, auth, storage, PWA/offline, IndexedDB, cache names ni `/api/sync`.
- No se escribieron tareas, estados, habitaciones, pagos, Caja ni snapshots.
