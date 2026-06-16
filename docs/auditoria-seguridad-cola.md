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
