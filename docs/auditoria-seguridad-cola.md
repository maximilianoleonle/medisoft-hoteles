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
