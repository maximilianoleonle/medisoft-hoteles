# QA pendiente - cola autonoma

## QA critica

- Confirmar que CxP es solo lectura.
- Confirmar que no hay botones de pago en `/cuentas-por-pagar`.
- Confirmar que no hay botones de pago en `/cuentas-por-pagar/{id}`.
- Confirmar que no existe ningun POST operativo de CxP.
- Confirmar que no se generan movimientos de Caja al abrir CxP.
- Confirmar que no se genera CxP automaticamente desde compras.
- Confirmar que una compra recibida no puede recibirse dos veces.
- Confirmar que cada linea recibida conserva exactamente un `movimiento_inventario_id`.
- Confirmar que proveedores, compras y CxP no muestran datos de otro hotel.
- Confirmar que `/api/sync` sigue bloqueado.

## QA funcional

- Iniciar sesion en un hotel con modulo de inventario activo.
- Abrir `/cuentas-por-pagar`.
- Validar estado vacio cuando no hay CxP.
- Revisar filtros de estado/proveedor/compra en el listado.
- Revisar navegacion desde sidebar.
- Cuando exista un registro real o de prueba autorizado, abrir `/cuentas-por-pagar/{id}` y validar detalle read-only.
- Validar que el detalle preserve links de lectura hacia compra/proveedor cuando existan.

## QA regresion

- Revisar `/compras/reportes/recibidas`.
- Revisar `/proveedores/{id}`.
- Revisar detalle de compra recibida.
- Confirmar que recepcion de compra no permite doble recepcion.
- Confirmar que compras recibidas no crean CxP automaticamente todavia.
- Confirmar que Caja/cortes/movimientos no cambian al consultar CxP.

## QA visual

- Revisar diseno de tabla CxP en escritorio.
- Revisar diseno de tabla CxP en movil.
- Revisar mensajes cuando no hay datos.
- Revisar consistencia visual con compras y proveedores.
- Revisar que sidebar no mezcle identidad Medisoft SaaS con branding hotelero.

## Estado

- QA automatica post-commit Fase 3B: completada sin errores bloqueantes.
- Auditoria de seguridad post-cierre: completada sin hallazgos bloqueantes.
- Cierre tecnico del bloque: completado.
- QA manual del bloque 2X-3B: reportada como realizada por el usuario.
- Bloqueante para Fase 3C: no; Fase 3C fue autorizada por nuevo mensaje real.

## QA Fase 3C

Estado vigente: `FASE_3C_VALIDADA_MANUALMENTE`.

El usuario reporto QA manual completada para 3C-A y 3C-B. Fase 3C-C queda completada tecnicamente con validaciones read-only en health/preflights y SQL de consistencia.
Revision tecnica 3C completada sin hallazgos bloqueantes: rutas, controlador, modelo, vistas, sidebar, permisos/guards, CSRF, filtros `hotel_id`, CxP `#2`, ausencia de pagos/abonos/Caja y `/api/sync` sin cambios.
Auditoria seguridad 3C completada sin hallazgos bloqueantes: cero movimientos CxP/pagos/abonos, cero Caja-CxP, `compra_pagos` inexistente y cambios no relacionados fuera del bloque. Los cambios PWA no relacionados fueron validados manualmente y commiteados por separado en `35abdc7`.
Cierre tecnico 3C completado documentalmente. La QA manual final del bloque 3C fue reportada como OK por el usuario.

### QA critica

- Confirmar que el flujo 3C-B solo escribe cuando el usuario presiona explicitamente `Generar CxP`.
- Confirmar que la generacion 3C-B es manual, no automatica.
- Confirmar que solo compras `recibida` generan CxP.
- Confirmar que no se duplica CxP por compra.
- Confirmar que CxP generada respeta `hotel_id`.
- Confirmar que proveedor y compra pertenecen al mismo hotel.
- Confirmar que no hay Caja, pagos ni abonos.
- Confirmar que `/api/sync` sigue bloqueado.

### QA funcional

- Revisar listado de compras recibidas elegibles.
- Revisar motivos de bloqueo cuando una compra ya tiene CxP.
- Confirmar que `/cuentas-por-pagar/generacion-preview` muestra `Generar CxP` solo en compras elegibles.
- Generar CxP manualmente desde una compra recibida autorizada.
- Revisar listado y detalle CxP despues de generar.
- Revisar detalle de compra y preview para ver CxP vinculada cuando ya exista.

### QA visual

- Revisar simulador en escritorio y movil.
- Revisar estados elegible/bloqueado.
- Revisar mensajes de exito/error.
- Revisar consistencia con compras/proveedores/CxP.

### QA regresion

- Confirmar que recepcion de compra no genera CxP automaticamente.
- Confirmar que compras/proveedores siguen aislados por hotel.
- Confirmar que Caja/cortes/movimientos no cambian.
- Confirmar que reportes de compras recibidas siguen funcionando.

### QA especifica Fase 3C-A

- Estado manual: OK reportado por el usuario.
- Abrir `/cuentas-por-pagar/generacion-preview` autenticado.
- Confirmar que muestra compras recibidas del hotel actual.
- Confirmar que muestra links a compra y proveedor.
- Confirmar que muestra link a CxP solo si ya existe.
- Confirmar que las compras elegibles indican motivo de elegibilidad.
- Confirmar que las compras no elegibles indican motivo de bloqueo.
- Confirmar que el boton `Generar CxP` solo aparece en filas elegibles.
- Confirmar que no hay botones de pago, abono ni Caja.

### QA especifica Fase 3C-B

- Estado manual: OK reportado por el usuario.
- Usar una compra recibida elegible.
- Presionar `Generar CxP`.
- Confirmar redireccion al detalle de la CxP.
- Confirmar que el preview cambia la compra a bloqueada por CxP existente.
- Intentar generar de nuevo y confirmar error claro.
- Confirmar que no cambia `movimientos_caja`.
- Confirmar que no hay pagos ni abonos.

## Resultado automatico Fase 3C-B

- Backup previo confirmado: `src/storage/backups/phase3c_b_20260615_144908_before_manual_cxp_medisoft_hoteles_import.sql`.
- SHA256: `C0403F7B5ACBDA35EF4C05E2840546D5D9978802A21C9736BEBC6FF462518061`.
- Prueba local controlada ejecutada sobre compra `#2` del hotel `4`.
- Se genero CxP `#2`.
- La doble generacion fue bloqueada limpiamente.
- No se crearon movimientos en `cuentas_por_pagar_movimientos`.
- No cambiaron `cajas` ni `movimientos_caja`.
- `logs_auditoria` aumento de `25` a `26`.

## Resultado automatico Fase 3C-C

- Health checker actualizado para detectar inconsistencias CxP generadas desde compras.
- Preflight de compras minimas actualizado con las mismas reglas de consistencia.
- Preflight de recepcion de compras actualizado con las mismas reglas de consistencia.
- Validaciones automaticas agregadas:
  - CxP duplicada por compra/hotel;
  - CxP con compra inexistente;
  - CxP con proveedor inexistente;
  - CxP con `hotel_id` nulo;
  - CxP con compra de otro hotel;
  - CxP con proveedor de otro hotel;
  - CxP generada desde compra no recibida;
  - CxP con saldo mayor al total;
  - CxP con total invalido o saldo negativo;
  - CxP sin fecha de emision;
  - movimientos CxP/pagos/abonos accidentales;
  - movimientos de Caja con referencia textual a CxP.
- Resultado verificado en DB local:
  - `cuentas_por_pagar`: 2;
  - `cuentas_por_pagar_movimientos`: 0;
  - inconsistencias CxP principales: 0;
  - movimientos de Caja con referencia textual a CxP: 0;
  - `compra_pagos`: tabla inexistente.

## QA manual historica Fase 3C

- `/cuentas-por-pagar/generacion-preview`: OK.
- La compra `#2` aparece con CxP generada/bloqueada: OK.
- Link a CxP `#2`: OK.
- `/cuentas-por-pagar/2`: OK.
- Origen compra/proveedor visible: OK.
- Sin botones de pago: OK.
- Sin abonos: OK.
- Sin acciones de Caja: OK.
- Reporte de compras recibidas: OK.
- Ficha de proveedor: OK.
- Detalle de compra recibida: OK.
- `/api/sync`: no probado manualmente; cubierto por checker automatico cuando el entorno esta disponible.
- Resultado vigente: 3C-A y 3C-B quedan validadas manualmente; no autoriza pagos, Caja ni Fase 3D.
- Resultado de cierre: bloque 3C cerrado tecnicamente; no autoriza pagos, abonos, Caja ni Fase 3D.

## QA manual final Fase 3C

- `/cuentas-por-pagar/generacion-preview`: OK.
- Simulador de CxP: OK.
- Compra con CxP existente aparece bloqueada: OK.
- Link a CxP generada: OK.
- `/cuentas-por-pagar/2`: OK.
- Origen compra/proveedor visible: OK.
- CxP generada desde compra recibida: OK.
- No se permite duplicar CxP: OK.
- No hay botones de pago: OK.
- No hay abonos: OK.
- No hay acciones de Caja: OK.
- No hay movimientos de Caja: OK.
- Reporte de compras recibidas: OK.
- Ficha de proveedor: OK.
- Detalle de compra recibida: OK.
- `/api/sync`: no probado manualmente; cubierto por checker automatico cuando el entorno esta disponible.
- Resultado: Fase 3C validada manualmente. No autoriza pagos, abonos, Caja ni Fase 3D.

## Revision tecnica Fase 3C

- Vista de preview ajustada para enlazar al proveedor solo si el join scoped por `hotel_id` encontro proveedor del hotel actual.
- Esto evita un enlace visual a proveedor no valido cuando una compra queda bloqueada por proveedor inexistente o de otro hotel.
- No cambia elegibilidad, escritura ni reglas de generacion.

## Auditoria seguridad Fase 3C post-QA

- Auditoria estatica previa sin hallazgos bloqueantes, reclasificada como prematura.
- Confirmado por revision de codigo actual: hay un unico POST 3C-B con CSRF.
- Confirmado por revision de codigo: `before()` exige autenticacion, contexto hotelero y modulo `inventario`.
- Confirmado por revision de codigo: no hay escritura en Caja ni pagos desde CxP.
- Confirmado por revision de codigo: generacion valida compra recibida, proveedor del hotel, total positivo, detalles y no duplicado.
- Health/preflights se re-ejecutaron con Docker/PHP disponible y quedaron sin errores bloqueantes.

## Triage no relacionado

- Sin cambios no relacionados pendientes al iniciar 3C-0.
- El ajuste visual del modal de check-in tardio fue commiteado en `dc3c150`.
- Cambios no relacionados ya separados en commit `e52766e`: `src/app/controllers/DashboardController.php`, `src/app/controllers/HabitacionController.php`, `src/app/controllers/NotificacionController.php`, `src/app/views/layout/sidebar.php`, `src/app/views/notificaciones/index.php`.
- Cambios PWA no relacionados resueltos despues del cierre 3C: `src/app/services/PwaPushService.php` y `src/public_html/service-worker.js` quedaron en `35abdc7`.

## QA Fase 4A Centro Documental

Estado vigente: `CIERRE_TECNICO_4A_COMPLETADO`.

4A-0 no implemento funcionalidad. 4A-A creo solo esquema base, sin uploads, sin POST,
sin descargas y sin datos operativos. 4A-B agrega capa de consulta read-only de metadata
sin exponer archivos.

### Resultado automatico Fase 4A-A

- Backup previo confirmado:
  `src/storage/backups/phase4a_20260615_182352_before_document_center_medisoft_hoteles_import.sql`.
- SHA256: `698A304F69B312EF06EABA787C83969629F2B14BCA096906CC08CADDC7D898F0`.
- Migracion aplicada: `migrations/20260615_004_fase_4a_centro_documental_base.sql`.
- Tablas documentales existentes y vacias:
  - `documento_tipos`: `0`;
  - `documentos`: `0`;
  - `documento_entidades`: `0`.
- `cuentas_por_pagar_movimientos`: `0`.
- No se detectaron tablas de pagos/abonos CxP creadas.
- No se implemento acceso publico a documentos.

### Resultado automatico Fase 4A-B

- `php -l` limpio en `Documento.php`, `DocumentoController.php`, vistas de documentos,
  rutas y sidebar.
- Rutas GET creadas:
  - `/documentos`;
  - `/documentos/{id}`;
  - `/documentos/entidad/{tipo}/{id}`.
- HTTP sin sesion en `/documentos`: debe redirigir a login por middleware global.
- La vista de listado muestra estado vacio cuando no hay documentos.
- La vista de detalle no muestra `storage_path`, `nombre_archivo` ni rutas internas.
- No existen rutas POST bajo `/documentos`.
- No se implemento upload, descarga, edicion ni borrado.

### Resultado automatico Fase 4A-C

- `GET /documentos/subir` y `POST /documentos/subir` agregados.
- `php -l` limpio en modelo, controlador, vista de listado, vista de carga y rutas.
- HTTP sin sesion:
  - `GET /documentos/subir`: redirige a login;
  - `POST /documentos/subir`: redirige a login.
- Backup previo antes de escritura:
  `src/storage/backups/phase4a_c_20260615_190912_before_document_upload_medisoft_hoteles_import.sql`.
- SHA256: `DF150F705824973621B9A1276980DC73ECB7AE5261B67A5D71E541FE13797446`.
- Prueba local controlada:
  - documento creado: `documentos.id = 1`;
  - hotel: `1` (`Los Cedros`);
  - entidad vinculada: `proveedor #8`;
  - storage privado:
    `documentos/hotel_1/2026/06/doc_20260615_191313_37017248e4f9b6e2.pdf`;
  - auditoria: `logs_auditoria` aumento a `27`.
- Archivo `.html` invalido rechazado con mensaje claro y sin crear registros.
- URL directa a `storage/documentos/...` no sirve el archivo.
- `health_check_fase_1a.php`, `preflight_compras_minimas.php` y
  `preflight_recepcion_compras.php` pasan con warnings conocidos.
- No se tocaron Caja, pagos, abonos, CxP operativa ni `/api/sync`.

### QA manual completada Fase 4A-C

- El usuario reporto despues del hotfix: "Ya funciona".
- La carga documental corregida deja de bloquearse por falta de tipos documentales.
- La carga general ya no pide IDs manuales de entidad.
- El CSS corregido evita el 404 relativo desde rutas bajo `/documentos`.

### Checklist de regresion Fase 4A-C

- Iniciar sesion en Los Cedros.
- Abrir `/documentos`.
- Presionar `Subir documento`.
- Confirmar que `Tipo documental` muestra Contrato, Comprobante, Identificacion,
  Factura, Evidencia y Otro.
- Confirmar que la carga general muestra `Sin vinculo inicial` y no pide IDs manuales de
  entidad.
- Cargar un PDF o imagen valida menor a 10 MB.
- Confirmar redireccion al detalle del documento.
- Confirmar que el detalle muestra metadata y vinculo, pero no descarga ni ruta interna.
- Intentar subir `.html` o `.php` y confirmar rechazo limpio.
- Confirmar que el documento aparece en `/documentos` y en
  `/documentos/entidad/proveedor/8` si se usa el proveedor de prueba.
- Confirmar que no aparecen botones de descarga, edicion, borrado, pago, abono ni Caja.
- Confirmar que no aparece error 404 de `documentos/css/performance-optimization.css` en
  consola/red.

### Resultado automatico hotfix Fase 4A-C

- Tipos documentales globales creados: `6`.
- `GET /documentos/subir` muestra tipos documentales y ya no muestra campos manuales de
  entidad en carga general.
- `GET /css/performance-optimization.css`: `200 OK`.
- Carga controlada sin vinculo inicial creo `documentos.id = 2` con tipo `Contrato`.
- `documento_entidades` se mantuvo en `1`, sin relacion para el documento `#2`.
- Caja, pagos, abonos y CxP operativa sin cambios.

### Resultado revision tecnica Fase 4A

- `/documentos/entidad/{tipo}/{id}` ahora valida que la entidad exista y pertenezca al hotel actual antes de mostrar el listado contextual.
- El enlace de carga contextual queda disponible solo para entidades validas del hotel actual.
- Verificacion HTTP autenticada: `/documentos` y `/documentos/entidad/proveedor/8` responden `200`; `/documentos/entidad/proveedor/999999` y `/documentos/subir?entidad_tipo=proveedor&entidad_id=999999` redirigen `303` a `/documentos`.
- No se agregaron descargas, edicion, borrado, pagos, abonos, Caja ni cambios en `/api/sync`.

### Resultado auditoria seguridad Fase 4A

- Rutas documentales activas confirmadas: `GET /documentos`, `GET /documentos/subir`, `POST /documentos/subir`, `GET /documentos/entidad/{tipo}/{id}` y `GET /documentos/{id}`.
- No hay rutas documentales de descarga, edicion ni borrado.
- `storage_path` y `nombre_archivo` se usan solo dentro del modelo y no se muestran en vistas.
- `unlink` aparece solo como rollback interno para eliminar un archivo recien movido si falla la transaccion.
- No hay referencias documentales en `service-worker.js`, `pwa.js`, `offline-data.js`, `reservaciones-offline.js` ni `ApiController`.
- Conteos read-only de auditoria: `documento_tipos=6`, `documentos=3`, `documento_entidades=1`, `cuentas_por_pagar_movimientos=0`, `movimientos_caja=1403`, `logs_auditoria=29`.

### Cierre tecnico Fase 4A

- 4A-0, 4A-A, 4A-B y 4A-C quedan cerradas tecnicamente.
- QA manual post-hotfix fue reportada como funcional por el usuario.
- El cierre no habilita descarga, edicion, borrado, pagos, abonos, Caja, Fase 3D ni `/api/sync`.
- Cualquier descarga segura debe abrirse en un bloque posterior con contrato, guardias y headers privados.

## QA Fase 4B Descarga segura documental

Estado vigente: `DESCARGA_SEGURA_4B_VALIDADA_MANUALMENTE`.

4B-0 fue contrato. 4B-A implementa descarga autenticada y fue validada manualmente por
el usuario. No autoriza avanzar a edicion, borrado, links publicos o auditoria de
descargas sin nueva fase explicita.

### Resultado automatico 4B-A

- `php -l` limpio en `DocumentoController.php`, `Documento.php`, vistas documentales y
  `routes.php`.
- Health y preflights pasan con warnings conocidos.
- HTTP sin sesion en `/documentos/1/descargar`: `303` a login.
- HTTP con sesion Los Cedros en `/documentos/1/descargar`: `200`, `Content-Type:
  application/pdf`, `Content-Disposition: attachment`,
  `X-Content-Type-Options: nosniff`, `Content-Length: 132`.
- Archivo descargado de `documentos.id=1` conserva SHA256
  `18B0855BC03494BD6C3059AC14BF342366B7F3DD598C118FA98CF91F987EAC64`, coincidente
  con `documentos.sha256`.
- HTTP con sesion Los Cedros en `/documentos/3/descargar`: `303` a `/documentos`
  porque pertenece a otro hotel.
- Documento inexistente `/documentos/999999/descargar`: `303` a `/documentos`.
- La vista de detalle muestra `Descargar` y `Descarga privada` sin `storage_path` ni
  `nombre_archivo`.
- Conteos read-only: `documento_tipos=6`, `documentos=3`, `documento_entidades=1`,
  `cuentas_por_pagar_movimientos=0`, `movimientos_caja=1403`, `logs_auditoria=29`.
- Nota: la capa global mantiene `Cache-Control: no-cache, no-store, must-revalidate`,
  mas restrictivo para documentos sensibles.

### QA manual completada 4B-A

- Resultado reportado por el usuario: `QA_MANUAL_COMPLETADA_4B_A`.
- Descarga en navegador validada.
- No se autoriza edicion, borrado, links publicos, pagos, abonos, Caja ni `/api/sync`.

### QA manual completada 4B-B

Resultado automatico:

- Descarga valida `GET /documentos/1/descargar`: `200`.
- Intento cross-hotel `GET /documentos/3/descargar`: `303` a `/documentos`.
- Auditorias generadas: `documentos.descargado=1` y
  `documentos.descarga_bloqueada=1`.
- El hash SHA256 del archivo descargado coincide con el documento esperado.
- `cuentas_por_pagar_movimientos=0` y `movimientos_caja=1403`.

Resultado manual:

- El usuario reporto que funciona.
- Fase 4B-B queda validada manualmente.
- No autoriza edicion, borrado, links publicos, pagos, abonos, Caja ni cambios en
  `/api/sync`.

### QA manual completada 4B-C-A metadata documental

Resultado automatico:

- GET/POST sin sesion bloqueados o redirigidos.
- GET con sesion carga el formulario de metadata.
- POST no-op con CSRF valido redirige al detalle sin modificar datos ni auditar ruido.
- POST con CSRF invalido no modifica metadata.
- Prueba transaccional de cambio real genero `documentos.metadata_actualizada` dentro
  de transaccion y rollback dejo metadata/auditoria sin cambios persistidos.

Validacion manual reportada por el usuario:

- El usuario confirmo que la prueba manual fue correcta.
- El formulario de edicion de metadata documental funciona.
- Editar metadata conserva archivo, storage privado, `storage_path`, `nombre_archivo`,
  `sha256`, `mime_type`, `size_bytes`, `hotel_id` y relaciones.
- No aparecen acciones de borrado, reemplazo de archivo, links publicos, pagos, abonos,
  Caja ni `/api/sync`.
- Estado formal: `METADATA_DOCUMENTAL_4B_C_A_VALIDADA_MANUALMENTE`.

### Cierre tecnico 4B post-QA

Resultado automatico de cierre:

- `php -l` limpio en `DocumentoController.php`, `Documento.php`, vistas documentales,
  `routes.php` y `health_check_fase_1a.php`.
- `health_check_fase_1a.php`: PASS con warnings conocidos; rutas 4B-A/4B-C y auditoria
  4B-B detectadas.
- `preflight_compras_minimas.php`: PASS con warnings conocidos.
- `preflight_recepcion_compras.php`: PASS con warnings conocidos.
- HTTP sin sesion:
  - `GET /documentos`: `303` a login.
  - `GET /documentos/1/descargar`: `303` a login.
  - `GET /documentos/1/editar`: `303` a login.
  - `POST /documentos/1/actualizar`: `303` a login.
- SQL read-only:
  - `documento_tipos=6`;
  - `documentos=3`;
  - `documento_entidades=1`;
  - `documentos_sin_hotel=0`;
  - `documentos_eliminados=0`;
  - `documentos.descargado=1`;
  - `documentos.descarga_bloqueada=1`;
  - `documentos.metadata_actualizada=1`;
  - `cuentas_por_pagar_movimientos=0`.
- `/api/sync`: handler conserva `sync_temporarily_disabled` + HTTP `423`; sin sesion
  el middleware redirige a login antes de ejecutar el handler.
- Estado formal: `CIERRE_TECNICO_4B_COMPLETADO`.

### QA critica futura

- Documento de un hotel no visible en otro hotel.
- Archivo privado no accesible por URL directa.
- Descarga sin sesion bloqueada o redirigida.
- Upload rechaza extension, MIME o tamano invalido.
- No hay escritura en Caja, pagos, abonos ni CxP operativa.
- `/api/sync` sigue bloqueado.

### QA funcional futura

- Proveedor, compra, CxP, huesped y reservacion muestran estado vacio documental.
- Documento adjunto aparece solo en la entidad correcta.
- Descarga autenticada funciona con permisos.

## QA Fase 4C Documentos por entidad

Estado: `DOCUMENTOS_ENTIDAD_CONTEXTUAL_4C_VALIDADA_MANUALMENTE`.

4C-0 es documentacion y diagnostico; no requiere QA en navegador.

QA automatica 4C-A:

- `php -l` debe pasar en controladores/vistas/partial y health checker tocados.
- Health checker debe validar partial contextual, cinco vistas con seccion documental
  y cinco controladores con `Documento::documentosPorEntidad()`.
- SQL read-only debe confirmar que no se insertaron documentos ni relaciones durante
  esta subfase.
- HTTP sin sesion en fichas nuevas debe redirigir a login o bloquear segun el patron
  existente.

QA manual completada 4C-A:

- Ficha de proveedor muestra documentos vinculados o estado vacio.
- Ficha de compra muestra documentos vinculados o estado vacio.
- Detalle de CxP muestra documentos vinculados o estado vacio.
- Ficha de huesped muestra documentos vinculados o estado vacio.
- Detalle de reservacion muestra documentos vinculados o estado vacio.
- Links a detalle documental y descarga usan rutas protegidas existentes.
- La accion `Vincular documento` aparece en las cinco fichas y abre
  `/documentos/subir` con `entidad_tipo` y `entidad_id`.
- Al cargar desde esa accion, el formulario muestra el vinculo con la entidad correcta.
- Un documento de otro hotel no aparece en la entidad actual.
- No se muestran `storage_path`, `nombre_archivo`, rutas absolutas ni links publicos.
- No aparecen acciones de edicion, borrado ni reemplazo de archivo dentro de las fichas.
- No hay Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.
- Resultado manual reportado por el usuario: "todo funciona a la perfeccion".
- La validacion manual cierra el ajuste contextual de `Vincular documento`, pero no
  autoriza borrado, reemplazo, links publicos, Caja, pagos, abonos, Fase 3D, NP-A ni
  cambios en `/api/sync`.

### QA visual 4C-A

- Estado vacio claro.
- Lista de documentos compacta y consistente con el sistema hotelero.
- No mezclar identidad Medisoft SaaS con branding del hotel.

### QA regresion 4C-A

- Fase 3C sigue funcionando.
- PWA push sigue funcionando.
- Reportes seguros siguen descargando.
- Login/logout normal.

## QA Fase 4D Archivado documental

Estado: `CIERRE_TECNICO_4D_COMPLETADO_QA_DIFERIDA`.

4D-0 es documentacion y diagnostico; no requiere QA en navegador.

QA automatica completada 4D-A:

- `php -l` en `Documento.php`, `DocumentoController.php`, `documentos/ver.php` y
  `health_check_fase_1a.php`.
- `health_check_fase_1a.php` debe detectar rutas, CSRF, auditoria y ausencia de
  `DELETE FROM documentos`.
- `git diff --check`.
- SQL read-only antes/despues de QA manual para confirmar que no se borran documentos,
  relaciones ni archivos.
- HTTP sin sesion en `POST /documentos/1/archivar`: `303` a login.
- Prueba transaccional con rollback: `activo -> archivado`, auditoria dentro de la
  transaccion y rollback final sin persistir cambios.
- Revision tecnica y auditoria de cierre: completadas sin hallazgos bloqueantes.

QA manual completada 4D-A:

Resultado reportado por el usuario: todas las pruebas QA responden perfectamente.

- Archivar documento activo cambia estado a `archivado`.
- Restaurar documento archivado cambia estado a `activo`.
- Acciones usan POST + CSRF.
- HTTP sin sesion bloquea o redirige.
- Documento de otro hotel no puede cambiar estado.
- Se registra auditoria `documentos.estado_actualizado` con antes/despues.
- No se borra archivo fisico.
- No se borran filas en `documentos` ni `documento_entidades`.
- No se exponen `storage_path`, `nombre_archivo`, rutas absolutas ni links publicos.
- No hay Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.

QA futura 4D-B:

- 4D-B-0 solo documenta contrato; no requiere QA de navegador porque no agrega rutas ni
  codigo funcional.

QA manual diferida 4D-B-A:

- Baja logica hacia `eliminado` requiere confirmacion fuerte.
- Documento eliminado bloquea edicion y descarga.
- Recuperacion desde `eliminado` queda fuera de alcance salvo contrato separado.
- Validar `activo/archivado -> eliminado`.
- Validar auditoria `documentos.estado_actualizado`.
- Validar bloqueo de descarga/edicion.
- Validar conservacion de archivo/relaciones.
- Validar ausencia de Caja, pagos, abonos, Fase 3D, NP-A y `/api/sync`.

## QA Bloque Personal y Nomina (Fase NP)

### Estado NP-0

- Contrato y diagnostico documentados; sin funcionalidad, sin migraciones aplicadas, sin escritura en DB.
- No hay QA manual pendiente especifica de NP-0 mas alla de validar lectura del contrato.
- Git limpio al iniciar; HEAD `5dfe665`.

### Estado NP-A

- Migracion base aplicada y documentada.
- Seis tablas `trabajador*` existen y estan vacias.
- UI read-first de Personal implementada en `GET /trabajadores` y
  `GET /trabajadores/{id}`.
- No hay POST, altas, edicion, pagos, anticipos, prestamos ni Caja.
- Caja/Nomina sigue en cero.
- `/api/sync` fuera de alcance.

### QA manual diferida NP-A UI read-first

- Abrir `/trabajadores` con usuario autorizado.
- Confirmar estado vacio claro si no hay trabajadores.
- Probar filtros GET por busqueda/estado.
- Confirmar que no aparecen acciones de alta, editar, pagar, anticipo, prestamo,
  asistencia, documentos laborales, nomina ni Caja.
- Abrir `/trabajadores/{id}` cuando exista un registro de prueba autorizado.
- Confirmar que usuario sin sesion redirige/bloquea.
- Confirmar que usuario sin `usuarios.view` no accede.
- Confirmar que Caja/cortes/movimientos no cambian.

### QA manual planificada NP-B CRUD trabajador

- Crear trabajador minimo con nombre obligatorio.
- Crear trabajador sin usuario del sistema.
- Crear trabajador con usuario vinculado del hotel actual.
- Validar email invalido bloqueado.
- Validar salario negativo bloqueado.
- Validar salario no numerico bloqueado.
- Editar telefono/rol/notas y confirmar auditoria.
- Dar baja logica y confirmar que no se borra fisicamente.
- Reactivar si la accion queda implementada.
- Confirmar que no aparecen acciones de pago, anticipo, prestamo, asistencia ni Caja.
- Confirmar que un hotel no puede ver/editar trabajador de otro hotel.
- Confirmar HTTP sin sesion bloquea todos los POST.

Estado NP-B-A: implementado y verificado automaticamente; QA manual queda diferida por
instruccion del usuario.

### QA critica planificada (NP)

- Confirmar que NO existe ningun movimiento de Caja generado por nomina (debe seguir en 0).
- Confirmar que un trabajador puede existir SIN usuario del sistema (sin login).
- Confirmar que `usuarios` no se altera de forma destructiva ni se borra.
- Confirmar que toda tabla `trabajador*` respeta `hotel_id` y no muestra datos de otro hotel.
- Confirmar que pagos/anticipos/prestamos son acciones explicitas (POST + CSRF), nunca automaticas.
- Confirmar que los saldos son derivados del ledger y no editables manualmente.
- Confirmar que `/api/sync` sigue bloqueado.

### QA funcional planificada (NP)

- NP-A: listado de trabajadores por hotel y ficha read-first.
- NP-B futuro: alta/edicion controlada, baja logica y documentos laborales.
- NP-B: registrar pago/anticipo/prestamo con validacion de hotel, trabajador activo y monto valido; mensajes claros.
- NP-C: ver saldo a favor/en contra, historial y reporte semanal/quincenal por hotel y trabajador.
- NP-D: registrar asistencia y comisiones/bonos/descuentos y ver su efecto en saldos.

### QA regresion planificada (NP)

- Confirmar que Caja/cortes/movimientos no cambian al usar el modulo de nomina.
- Confirmar que `usuarios`/`hotel_usuarios` siguen intactos.
- Confirmar que mantenimiento/limpieza siguen funcionando (la referencia de responsable es logica/opcional).

### QA visual planificada (NP)

- Listado y ficha de trabajador en escritorio y movil.
- Estados vacios claros (sin trabajadores, sin movimientos).
- Consistencia visual con el resto del sistema; sin mezclar branding Medisoft SaaS con branding hotelero.

## QA Bloque Tareas, Limpieza y Mantenimiento (Fase TLM)

### Estado TLM-0

- Contrato y diagnostico documentados.
- Sin migraciones, rutas, UI ni escrituras de DB.
- QA manual diferida por instruccion del usuario.

### QA critica planificada TLM

- Confirmar que `/api/sync` sigue bloqueado.
- Confirmar que Caja/cortes/movimientos no cambian al usar tareas.
- Confirmar que tareas futuras no cambian `habitaciones.estado` automaticamente en la
  primera etapa.
- Confirmar que `mantenimientos_habitaciones` no se borra, fusiona ni renombra.
- Confirmar que todo acceso futuro filtra por `hotel_id`.
- Confirmar que tareas futuras solo se asignan a trabajadores activos del mismo hotel.

### QA funcional planificada TLM

- TLM-A: tablas nuevas vacias y migracion registrada.
- TLM-B: listado/detalle read-only con estado vacio claro.
- TLM-C: crear tarea manual con CSRF y auditoria, sin cambiar disponibilidad.
- TLM-D: asignar/reasignar trabajador activo del mismo hotel.
- TLM-E: iniciar/completar/cancelar tarea manualmente.
- TLM-F: ver tareas contextuales desde habitacion y trabajador.

### QA regresion planificada TLM

- Habitaciones en limpieza pueden liberarse como antes.
- Mantenimiento de habitacion actual sigue funcionando.
- Reservaciones siguen bloqueando conflictos de mantenimiento programado.
- Reporte de mantenimiento sigue leyendo `mantenimientos_habitaciones`.
- Notificaciones de limpieza/mantenimiento siguen funcionando.

### Estado TLM-A

- Migracion base aplicada.
- Tablas `tareas_operativas` y `tarea_eventos` creadas y vacias.
- No hay UI, rutas ni POST que probar en navegador.
- QA manual diferida: validar en una fase posterior que las vistas read-only muestren
  estado vacio y no cambien habitaciones ni mantenimiento.

### Estado TLM-B

- UI read-only implementada en `/tareas` y `/tareas/{id}`.
- No hay POST ni acciones operativas.
- QA manual diferida por instruccion del usuario.

### QA manual diferida TLM-B

- Abrir `/tareas` con usuario autorizado.
- Confirmar estado vacio claro.
- Confirmar filtros GET por busqueda, categoria, estado y prioridad.
- Confirmar que no hay botones de asignar, iniciar, completar o cancelar.
- Confirmar que el sidebar muestra `Tareas` solo dentro del contexto hotelero.
- Confirmar bloqueo/redireccion sin sesion.

### Estado TLM-C

- Creacion manual controlada implementada.
- POST autorizado unico: `POST /tareas`.
- QA manual diferida por instruccion del usuario.

### QA manual diferida TLM-C

- Abrir `/tareas/crear` con usuario autorizado.
- Confirmar que el formulario incluye CSRF y no solicita `hotel_id`.
- Crear una tarea general sin habitacion.
- Crear una tarea vinculada a una habitacion del hotel actual.
- Confirmar redireccion al detalle de la tarea creada.
- Confirmar que el detalle muestra estado `Pendiente`, origen `manual` y evento
  `creada`.
- Confirmar que no aparece asignacion, inicio, cierre, cancelacion ni acciones de Caja.
- Confirmar que `habitaciones.estado` no cambia tras crear una tarea.
- Confirmar que `mantenimientos_habitaciones` no se modifica.
- Confirmar bloqueo/redireccion sin sesion en `GET /tareas/crear` y `POST /tareas`.
- Confirmar que `/api/sync` sigue bloqueado con HTTP 423 por checker.

### Estado TLM-D

- Asignacion controlada a trabajador activo implementada.
- POST autorizado unico para asignacion: `POST /tareas/{id}/asignar`.
- QA manual diferida por instruccion del usuario.
- Nota: al implementar TLM-D, la base local reporta 0 trabajadores activos; primero debe
  existir un trabajador activo para probar asignacion real.

### QA manual diferida TLM-D

- Crear o usar trabajador activo del hotel actual.
- Crear o usar tarea pendiente del hotel actual.
- Abrir `/tareas/{id}`.
- Confirmar que aparece selector de trabajador activo.
- Asignar trabajador.
- Confirmar que la tarea queda `Asignada`.
- Confirmar evento `asignada`.
- Confirmar que no cambia `habitaciones.estado`.
- Confirmar que no se crean asistencia, pagos, abonos ni movimientos de Caja.
- Confirmar bloqueo/redireccion sin sesion para `POST /tareas/{id}/asignar`.

### Estado TLM-E

- Estados manuales de tarea implementados.
- POST autorizados:
  - `POST /tareas/{id}/iniciar`;
  - `POST /tareas/{id}/completar`;
  - `POST /tareas/{id}/cancelar`.
- QA manual diferida por instruccion del usuario.

### QA manual diferida TLM-E

- Crear o usar una tarea activa.
- Iniciar tarea y confirmar estado `En proceso` + evento `iniciada`.
- Completar tarea activa y confirmar estado `Completada` + evento `completada`.
- Cancelar tarea activa y confirmar estado `Cancelada` + evento `cancelada`.
- Confirmar que una tarea completada/cancelada ya no muestra acciones operativas.
- Confirmar que no cambia `habitaciones.estado`.
- Confirmar que no se modifica `mantenimientos_habitaciones`.
- Confirmar que no se crean asistencia, pagos, abonos ni movimientos de Caja.
- Confirmar bloqueo/redireccion sin sesion para iniciar/completar/cancelar.

### Estado TLM-F

- Integracion contextual read-only de tareas en fichas de habitacion y trabajador.
- No agrega rutas ni POST nuevos.
- QA manual diferida por instruccion del usuario.

### QA manual diferida TLM-F

- Abrir una habitacion sin tareas y confirmar estado vacio claro.
- Abrir una habitacion con tarea vinculada y confirmar enlace a `/tareas/{id}`.
- Abrir un trabajador sin tareas y confirmar estado vacio claro.
- Asignar una tarea a un trabajador activo y confirmar que aparece en su ficha.
- Confirmar que el bloque contextual no muestra formularios ni botones de asignar,
  iniciar, completar o cancelar.
- Confirmar que no cambia `habitaciones.estado`.
- Confirmar que no se modifica `mantenimientos_habitaciones`.
- Confirmar que no se crean asistencia, pagos, abonos, nomina ni movimientos de Caja.
- Confirmar bloqueo/redireccion sin sesion en `/habitaciones/{id}` y `/trabajadores/{id}`.

### Estado TLM-G

- Health/preflight de consistencia implementado.
- No agrega interfaz ni rutas nuevas.
- QA manual visual no aplica; queda diferida la ejecucion manual general de TLM.

### QA manual diferida TLM-G

- Ejecutar `docker compose exec -T app php /var/www/html/tools/saas/preflight_tareas_operativas.php`.
- Confirmar `ERROR: 0`.
- Confirmar que cualquier warning sea conocido y no implique Caja, pagos, abonos,
  nomina ni `/api/sync`.

### Estado TLM-H

- Cierre tecnico del bloque TLM completado.
- QA manual global de TLM queda diferida por instruccion del usuario.

### QA manual global TLM pendiente

- Crear tarea manual general.
- Crear tarea vinculada a habitacion.
- Asignar trabajador activo.
- Iniciar una tarea.
- Completar una tarea.
- Cancelar una tarea.
- Confirmar eventos en detalle de tarea.
- Confirmar que tareas aparecen en ficha de habitacion/trabajador.
- Confirmar que no cambia disponibilidad de habitacion.
- Confirmar que no se modifica `mantenimientos_habitaciones`.
- Confirmar que no se crean movimientos de Caja, pagos, abonos, nomina ni asistencia.

### Estado NP-C-0

- Contrato de ledger laboral completado.
- No requiere QA de navegador porque no agrega rutas ni vistas.
- QA futura NP-C-A: validar resumen read-only de ledger por trabajador y estados vacios,
  sin POST, sin Caja y sin movimientos reales.

### Estado NP-C-A

- Ledger laboral read-only implementado en ficha de trabajador.
- QA manual diferida:
  - abrir `/trabajadores`;
  - abrir `/trabajadores/{id}`;
  - confirmar bloque "Ledger laboral";
  - confirmar estados vacios si no hay conceptos;
  - confirmar ausencia de botones de pago, abono, Caja o nomina;
  - confirmar que el saldo se presenta como informativo.

### Estado NP-C-E

- Preflight de ledger laboral implementado.
- QA manual visual no aplica.
- Verificacion recomendada:
  - ejecutar `docker compose exec -T app php /var/www/html/tools/saas/preflight_personal_ledger.php`;
  - confirmar `ERROR: 0`;
  - confirmar que Caja/Nomina sigan en cero.

### Estado NP-C-F

- Bloque read-only de ledger laboral cerrado tecnicamente.
- QA manual diferida:
  - confirmar visualmente `/trabajadores/{id}` con un trabajador real o de prueba;
  - confirmar que el ledger no ofrece acciones de pago/abono/Caja;
  - confirmar que el saldo se lee como informativo.

### Estado NP-C-B-0

- Contrato de conceptos laborales completado.
- No requiere QA de navegador porque no agrega funcionalidad.
- Si se implementa NP-C-B-A en el futuro, QA debe confirmar que no toca Caja ni crea
  pagos reales.

### Estado NP-C-B-A

- Conceptos laborales manuales implementados.
- QA manual diferida por instruccion del usuario.
- No se hizo prueba de escritura automatica porque `trabajadores` esta vacia y no se
  deben fabricar datos sin autorizacion.

### QA manual diferida NP-C-B-A

- Crear o usar un trabajador activo autorizado.
- Abrir `/trabajadores/{id}`.
- Confirmar que el formulario de concepto laboral aparece solo para trabajador activo.
- Registrar una comision o bono y confirmar que aparece como `a_favor`.
- Registrar un descuento y confirmar que aparece como `en_contra`.
- Registrar un ajuste y confirmar que respeta el efecto elegido.
- Confirmar que no se crea movimiento de Caja.
- Confirmar que no aparece boton de pago real, abono, anticipo ni prestamo.
- Ejecutar `docker compose exec -T app php /var/www/html/tools/saas/preflight_personal_ledger.php`.
- Confirmar `ERROR: 0`.

### Estado NP-C-B-F

- Bloque de conceptos laborales manuales cerrado tecnicamente.
- QA manual sigue pendiente y diferida.
- No avanzar a anticipos, prestamos o asistencia sin contrato nuevo.

### Estado NP-C-C-0

- Contrato de anticipos y prestamos completado.
- No requiere QA de navegador porque no agrega funcionalidad.
- QA futura NP-C-C-A debe confirmar captura manual, saldo inicial igual al monto, ausencia
  de Caja y ausencia de pagos/abonos reales.

### Estado NP-C-C-A

- Anticipos y prestamos manuales implementados.
- QA manual diferida por instruccion del usuario.
- No se hizo prueba de escritura automatica porque `trabajadores` esta vacia.

### QA manual diferida NP-C-C-A

- Crear o usar un trabajador activo autorizado.
- Abrir `/trabajadores/{id}`.
- Registrar anticipo y confirmar saldo pendiente igual al monto.
- Registrar prestamo y confirmar saldo pendiente igual al monto.
- Confirmar que no se crean pagos reales ni abonos.
- Confirmar que no se crean movimientos de Caja.
- Ejecutar `docker compose exec -T app php /var/www/html/tools/saas/preflight_personal_ledger.php`.
- Confirmar `ERROR: 0`.

### Estado NP-C-C-F

- Bloque de anticipos y prestamos manuales cerrado tecnicamente.
- QA manual sigue pendiente y diferida.
- No avanzar a abonos, liquidaciones, Caja ni asistencia sin contrato nuevo.

### Estado NP-C-D-0

- Contrato de asistencia manual completado.
- No requiere QA de navegador porque no agrega funcionalidad.
- QA futura NP-C-D-A debe confirmar una asistencia por trabajador/dia, ausencia de Caja y
  ausencia de nomina automatica.

### Estado NP-C-D-A

- Asistencia manual implementada.
- QA manual diferida por instruccion del usuario.
- No se hizo prueba de escritura automatica porque `trabajadores` esta vacia.

### QA manual diferida NP-C-D-A

- Crear o usar un trabajador activo autorizado.
- Abrir `/trabajadores/{id}`.
- Confirmar que el formulario de asistencia aparece solo para trabajador activo.
- Registrar asistencia con fecha, tipo y horas opcionales.
- Confirmar que aparece en asistencias recientes.
- Intentar registrar otra asistencia para el mismo trabajador y fecha.
- Confirmar que la duplicada falla limpiamente.
- Confirmar que no se crean pagos reales, abonos, nomina ni movimientos de Caja.
- Ejecutar `docker compose exec -T app php /var/www/html/tools/saas/preflight_personal_ledger.php`.
- Confirmar `ERROR: 0`.

### Estado NP-C-D-F

- Bloque de asistencia manual cerrado tecnicamente.
- QA manual sigue pendiente y diferida.
- No avanzar a nomina, descuentos automaticos, abonos, liquidaciones ni Caja sin contrato
  nuevo y autorizacion explicita.

### Estado NP-D-0

- Contrato de documentos laborales completado.
- No requiere QA de navegador porque no agrega funcionalidad.
- QA futura NP-D-A debe confirmar que documentos de trabajador usan Centro Documental
  moderno, sin exponer rutas internas ni usar `trabajador_documentos` como flujo nuevo.

### Estado NP-D-A

- Documentos laborales contextuales implementados.
- QA manual diferida por instruccion del usuario.

### QA manual diferida NP-D-A

- Crear o usar un trabajador activo autorizado.
- Abrir `/trabajadores/{id}`.
- Confirmar que aparece "Documentos vinculados".
- Usar "Vincular documento" y cargar un archivo permitido.
- Confirmar que aparece en la ficha de trabajador.
- Abrir detalle y descarga segura del documento.
- Confirmar que no se expone `storage_path`, `nombre_archivo` ni `ruta_archivo`.
- Confirmar que `trabajador_documentos` no recibe registros nuevos.

### Estado NP-D-F

- Bloque de documentos laborales cerrado tecnicamente.
- QA manual sigue pendiente y diferida.
- No avanzar a reconciliacion de `trabajador_documentos` ni storage nuevo sin contrato.

### Estado NP-E

- Bloque Personal operativo base cerrado tecnicamente.
- QA manual sigue pendiente y diferida.
- Validar en navegador con un trabajador activo: ficha, concepto laboral, anticipo,
  prestamo, asistencia, documento vinculado y ausencia de Caja/pagos/abonos.
- No avanzar a nomina, pagos reales, abonos/liquidaciones ni reconciliacion documental
  legacy sin contrato nuevo.

### Estado NP-F-0

- Contrato de reporte Personal read-only completado.
- No requiere QA de navegador porque no agrega funcionalidad.
- QA futura NP-F-A debe confirmar ruta protegida, datos scoped por hotel, estados vacios,
  ausencia de POST y ausencia de botones financieros.

### Estado NP-F-A

- Reporte Personal read-only implementado.
- QA manual diferida por instruccion del usuario.
- Probar `/trabajadores/reporte` con sesion de hotel.
- Confirmar estados vacios o datos correctos segun existan trabajadores.
- Confirmar que no existen formularios POST, botones de nomina, pago, abono ni Caja.
- Confirmar que HTTP sin sesion bloquea o redirige.

### Estado NP-F-F

- Bloque reporte Personal read-only cerrado tecnicamente.
- QA manual sigue pendiente y diferida.
- No avanzar a nomina, pagos reales, abonos/liquidaciones ni Caja desde este cierre.

### Estado TLM-I-0

- Contrato de reporte operativo TLM read-only completado.
- No requiere QA de navegador porque no agrega funcionalidad.
- QA futura TLM-I-A debe confirmar ruta protegida, datos scoped por hotel, estados vacios,
  ausencia de POST y ausencia de acciones de tarea.

### Estado TLM-I-A

- Reporte operativo TLM read-only implementado.
- QA manual diferida por instruccion del usuario.
- Probar `/tareas/reporte` con sesion de hotel.
- Confirmar estados vacios o datos correctos segun existan tareas.
- Confirmar que no existen formularios POST ni botones de asignar/iniciar/completar/cancelar.
- Confirmar que HTTP sin sesion bloquea o redirige.

### Estado TLM-I-F

- Bloque reporte operativo TLM read-only cerrado tecnicamente.
- QA manual sigue pendiente y diferida.
- No avanzar a automatizaciones de limpieza/mantenimiento, disponibilidad de habitacion,
  asistencia, nomina, pagos ni Caja desde este cierre.

### Estado OP-0

- Contrato de tablero operativo diario read-only completado.
- No requiere QA de navegador porque no agrega funcionalidad.
- QA futura OP-A debe confirmar ruta protegida, datos scoped por hotel, estados vacios,
  ausencia de POST, ausencia de acciones de tarea/habitacion/reservacion y ausencia de
  Caja, pagos, abonos, nomina, offline y `/api/sync`.

### Estado OP-A

- Tablero operativo diario read-only implementado.
- QA manual diferida por instruccion del usuario.
- Probar `/operacion/diaria` con sesion de hotel.
- Confirmar que muestra datos o estados vacios sin errores.
- Confirmar que el sidebar muestra "Operacion diaria" solo cuando dashboard esta activo.
- Confirmar que HTTP sin sesion bloquea o redirige.
- Confirmar que no hay formularios POST ni botones de accion.
- Confirmar que no se exponen `storage_path`, `nombre_archivo` ni rutas privadas.
- Confirmar que Caja, pagos, abonos, nomina y `/api/sync` no participan.

### Estado OP-F

- Bloque Tablero Operativo Diario cerrado tecnicamente.
- QA manual sigue pendiente y diferida.
- No avanzar a automatizaciones, acciones de tarea, cambios de habitacion, check-in,
  check-out, pagos, Caja, nomina, offline ni `/api/sync` desde este cierre.

### Estado MANT-A

- Reporte de mantenimiento read-only estabilizado.
- QA manual diferida por instruccion del usuario.
- Probar `/reportes/mantenimiento` con sesion de hotel.
- Confirmar que los datos pertenecen solo al hotel activo.
- Probar filtros por fecha y tipo.
- Confirmar estado vacio si no hay mantenimientos.
- Confirmar que no existen formularios POST ni botones para crear/iniciar/completar/
  cancelar mantenimientos desde el reporte.
- Confirmar que HTTP sin sesion bloquea o redirige.
- Confirmar que no participa Caja, pagos, abonos, nomina, offline ni `/api/sync`.

### Estado MANT-F

- Bloque reporte mantenimiento read-only cerrado tecnicamente.
- QA manual sigue pendiente y diferida.
- No avanzar a acciones de mantenimiento, automatizacion de habitaciones, Caja, pagos,
  abonos, nomina, offline ni `/api/sync` desde este cierre.

### Estado MANT-B

- Mantenimiento inmediato existente endurecido.
- QA manual diferida por instruccion del usuario.
- Probar iniciar mantenimiento desde una habitacion disponible.
- Confirmar validaciones de tipo, prioridad y motivo.
- Confirmar que un segundo intento de iniciar queda bloqueado limpiamente.
- Probar finalizar mantenimiento desde habitacion en mantenimiento.
- Confirmar que no se crean movimientos de Caja, pagos, abonos ni cambios en `/api/sync`.

### Estado MANT-B-F

- Bloque mantenimiento inmediato cerrado tecnicamente.
- QA manual sigue pendiente y diferida.
- No avanzar a automatizacion de mantenimiento programado, disponibilidad automatica,
  Caja, pagos, abonos, nomina, offline ni `/api/sync` desde este cierre.

### Estado MANT-C-0

- Contrato de mantenimiento programado completado.
- QA manual no aplica todavia; no se modifico codigo.

### QA futura MANT-C-A

- Programar mantenimiento en una habitacion disponible con fechas validas.
- Confirmar bloqueo de fecha pasada y fin anterior a inicio.
- Confirmar bloqueo cuando existe reservacion conflictiva.
- Confirmar bloqueo de mantenimiento programado duplicado/solapado para la misma
  habitacion.
- Cancelar mantenimiento programado desde ficha de habitacion.
- Confirmar que cancelacion no opera mantenimientos de otro hotel ni estados no
  programados.
- Confirmar que no se activa automaticamente ningun mantenimiento vencido.
- Confirmar que no hay Caja, pagos, abonos, nomina, offline ni `/api/sync`.

### Estado MANT-C-A

- Guardrails de programacion/cancelacion implementados.
- QA manual sigue diferida por instruccion del usuario.
- Preflight y health ejecutados con `ERROR: 0`.

### Estado MANT-C-F

- Bloque mantenimiento programado cerrado tecnicamente.
- QA manual global de MANT-C queda diferida.
- No avanzar a activacion automatica de vencidos, cron/dashboard, disponibilidad
  automatica, Caja, pagos, abonos, nomina, offline ni `/api/sync` sin contrato nuevo.

### Estado MANT-D-0

- Contrato de preview read-only de vencidos completado.
- QA manual no aplica todavia; no se modifico codigo.

### QA futura MANT-D-A

- Abrir preview con sesion de hotel.
- Confirmar estado vacio o listado de candidatos.
- Confirmar que los registros pertenecen solo al hotel activo.
- Confirmar links a habitacion.
- Confirmar que no hay botones de activar, POST, cron ni cambios de habitacion.
- Confirmar HTTP sin sesion.
- Confirmar que Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.
- Estado tecnico actual: `PREVIEW_MANT_D_A_COMPLETADO_QA_DIFERIDA`.

### Estado MANT-D-F

- Bloque preview mantenimiento programado cerrado tecnicamente.
- QA manual sigue diferida por instruccion del usuario.
- No avanzar a activacion manual/automatica sin contrato separado y QA explicita.

### Estado MANT-E-0

- Contrato de activacion manual completado.
- No requiere QA de navegador porque no agrega codigo.
- Una futura MANT-E-A si requerira QA manual obligatoria:
  - activar un solo mantenimiento vencido/hoy de prueba;
  - confirmar cambio a `en_proceso`;
  - confirmar habitacion en `mantenimiento`;
  - confirmar bloqueo de doble activacion;
  - confirmar bloqueo con reservacion conflictiva;
  - confirmar que no hay Caja, pagos, abonos, nomina, offline ni `/api/sync`.

### QA futura MANT-E-A

- Crear backup de DB antes de activar un candidato real.
- Abrir `/reportes/mantenimiento-programado`.
- Confirmar boton "Activar" solo en candidatos vencidos/hoy y con permiso.
- Activar un candidato controlado.
- Confirmar mensaje de exito.
- Confirmar que el mantenimiento queda `en_proceso`.
- Confirmar que la habitacion queda en `mantenimiento`.
- Intentar doble activacion y confirmar bloqueo limpio.
- Confirmar que no se crean movimientos de Caja, pagos, abonos ni cambios en `/api/sync`.
- Estado tecnico actual: `ACTIVACION_MANUAL_MANT_E_A_COMPLETADA_QA_DIFERIDA`.

### Estado MANT-E-F

- Bloque activacion manual cerrado tecnicamente.
- QA manual real sigue pendiente con backup previo.
- No avanzar a activacion automatica, cron ni acciones masivas hasta completar QA y abrir
  contrato nuevo.

### Estado MANT-G-0

- Contrato de integracion tareas desde mantenimiento completado.
- No requiere QA de navegador porque no agrega codigo ni rutas.
- QA futura MANT-G-A:
  - confirmar estado vacio de tareas vinculadas a mantenimiento;
  - confirmar que solo aparecen tareas del hotel actual;
  - si se autoriza creacion manual, crear una tarea desde mantenimiento controlado;
  - confirmar `mantenimiento_id` correcto en la tarea;
  - confirmar que no cambia `habitaciones.estado`;
  - confirmar que no cambia `mantenimientos_habitaciones.estado`;
  - confirmar que no hay Caja, pagos, abonos, nomina ni cambios en `/api/sync`.

### Estado MANT-G-A

- Tareas contextuales desde mantenimiento implementadas en modo read-only.
- QA manual diferida por instruccion del usuario.
- Probar `/reportes/mantenimiento-programado`.
- Confirmar estado vacio "Sin tareas vinculadas" cuando no hay tareas.
- Confirmar enlace a `GET /tareas/{id}` si existe una tarea con `mantenimiento_id`.
- Confirmar que no hay boton de crear tarea desde mantenimiento.
- Confirmar que no cambia `habitaciones.estado`.
- Confirmar que no cambia `mantenimientos_habitaciones.estado`.
- Confirmar que no hay Caja, pagos, abonos, nomina ni cambios en `/api/sync`.

### Estado MANT-G-B-0

- Contrato de creacion manual de tarea desde mantenimiento completado.
- No requiere QA de navegador porque no agrega codigo ni rutas.
- QA futura MANT-G-B-A:
  - crear una tarea desde mantenimiento controlado;
  - confirmar detalle de tarea;
  - confirmar `mantenimiento_id`, `habitacion_id`, `hotel_id` y `origen`;
  - confirmar evento inicial;
  - confirmar bloqueo de duplicado activo;
  - confirmar que no cambia habitacion ni mantenimiento;
  - confirmar que no hay Caja, pagos, abonos, nomina ni cambios en `/api/sync`.

### Estado MANT-G-B-A

- Creacion manual de tarea desde mantenimiento implementada.
- QA manual diferida por instruccion del usuario.
- Probar `/reportes/mantenimiento-programado`.
- Crear una tarea desde un mantenimiento controlado.
- Confirmar detalle de tarea, `mantenimiento_id`, `habitacion_id`, `hotel_id` y
  `origen = mantenimiento`.
- Confirmar bloqueo de duplicado activo.
- Confirmar que no cambia habitacion ni mantenimiento.
- Confirmar que no hay Caja, pagos, abonos, nomina ni cambios en `/api/sync`.

### Estado MANT-G-F

- Bloque tareas desde mantenimiento cerrado tecnicamente.
- QA manual real sigue diferida por instruccion del usuario.
- Antes de cualquier automatizacion futura, ejecutar QA MANT-G-B-A y documentar IDs
  usados.
- No avanzar a cron, creacion automatica ni acciones masivas sin contrato nuevo.

## QA Bloque Limpieza Operativa (Fase LIM)

### Estado LIM-0

- Contrato de limpieza operativa completado.
- No requiere QA de navegador porque no agrega codigo, rutas ni DB.
- QA futura LIM-A:
  - abrir el reporte read-only de limpieza;
  - confirmar filtro por hotel;
  - confirmar estado vacio o listado de habitaciones en limpieza;
  - confirmar enlaces GET seguros a habitacion/tareas;
  - confirmar que no hay POST, liberacion automatica, Caja, pagos, abonos, nomina,
    offline ni `/api/sync`.

### Estado LIM-A

- Reporte limpieza read-only implementado.
- QA manual diferida por instruccion del usuario.
- Probar `/reportes/limpieza`.
- Confirmar resumen y habitaciones en limpieza del hotel actual.
- Confirmar que los enlaces a habitacion/tarea son GET.
- Confirmar que no hay formularios, liberacion automatica, creacion de tareas,
  inventario automatico, Caja, pagos, abonos, nomina, offline ni `/api/sync`.

### Estado LIM-F

- Bloque limpieza read-only cerrado tecnicamente.
- QA manual real sigue diferida.
- No avanzar a POST de limpieza, automatizacion ni inventario automatico sin contrato
  nuevo.

### Estado LIM-B-0

- Contrato de creacion manual de tarea de limpieza completado.
- No requiere QA de navegador porque no agrega codigo ni rutas.
- QA futura LIM-B-A:
  - crear tarea desde una habitacion en limpieza;
  - confirmar `categoria = limpieza`, `habitacion_id`, `hotel_id` y origen;
  - confirmar bloqueo de duplicado activo;
  - confirmar que no cambia `habitaciones.estado`;
  - confirmar que no hay inventario automatico, Caja, pagos, abonos, nomina, offline ni
    `/api/sync`.

### Estado LIM-B-A

- Creacion manual de tarea de limpieza implementada.
- QA manual diferida por instruccion del usuario.
- Probar `/reportes/limpieza`.
- Crear una tarea desde una habitacion en limpieza.
- Confirmar detalle de tarea, `categoria = limpieza`, `habitacion_id`, `hotel_id` y
  `origen = habitacion`.
- Confirmar bloqueo de duplicado activo.
- Confirmar que no cambia `habitaciones.estado`.
- Confirmar que no hay inventario automatico, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.

### Estado LIM-B-F

- Estado formal: `BLOQUE_LIM_B_TAREAS_DESDE_LIMPIEZA_CERRADO_QA_DIFERIDA`.
- Verificacion automatica completada:
  - `php -l`: OK.
  - `preflight_limpieza_operativa.php`: `ERROR 0`.
  - `health_check_fase_1a.php`: `ERROR 0`.
  - POST sin sesion redirige a login.
  - SQL read-only confirma cero escrituras por intento sin sesion.
- QA manual sigue pendiente y no bloquea nuevas tareas independientes por
  instruccion del usuario.

### Estado TLM-J-0

- Contrato de agenda de tareas por trabajador completado.
- No requiere QA de navegador porque no agrega codigo, rutas ni DB.
- QA futura TLM-J-A:
  - abrir `/tareas/agenda`;
  - filtrar por fecha y trabajador;
  - confirmar enlaces GET a tarea/trabajador/habitacion;
  - confirmar ausencia de POST, asignacion, estados manuales, Caja, nomina,
    offline y `/api/sync`.

### Estado TLM-J-A

- Agenda de tareas por trabajador implementada en modo GET/read-only.
- QA manual diferida por instruccion del usuario.
- Probar `/tareas/agenda`.
- Filtrar por fecha, trabajador, categoria y estado.
- Confirmar que solo hay enlaces GET y que no aparecen acciones de asignar/iniciar/
  completar/cancelar en la agenda.
- Confirmar que no cambia habitacion, mantenimiento, inventario, Caja, nomina, offline
  ni `/api/sync`.

### Estado TLM-J-F

- Bloque agenda de tareas cerrado tecnicamente.
- QA manual real sigue diferida.
- No abrir acciones operativas desde agenda sin contrato nuevo.

### Estado 5A

- 5A Personal laboral basico queda reanclada al bloque NP existente.
- QA manual real sigue diferida para flujos de trabajador/ledger si no hay datos de
  prueba suficientes.
- No hay QA nueva de navegador en el reanclaje documental.

### Estado 6B-0

- Contrato de integracion tareas + habitaciones completado.
- No requiere QA de navegador porque no agrega codigo, rutas, DB ni UI.
- QA futura 6B-A:
  - abrir listado/ficha de habitaciones cuando existan tareas vinculadas;
  - confirmar indicadores read-only;
  - confirmar que no aparece boton de crear tarea de limpieza en ficha de habitacion;
  - confirmar que crear limpieza sigue en `/reportes/limpieza`;
  - confirmar que no cambia `habitaciones.estado`;
  - confirmar que no se toca Caja, nomina, offline ni `/api/sync`.

### Estado 6B-A

- Indicadores read-only de tareas activas en habitaciones implementados.
- QA manual diferida por instruccion del usuario.
- Probar `/habitaciones`.
- Confirmar que las habitaciones con tareas activas muestran indicador de conteo.
- Voltear tarjeta y confirmar que solo hay informacion de tareas, sin boton de crear
  tarea desde habitacion.
- Probar disponibilidad por fecha y confirmar que el indicador sigue siendo lectura.
- Confirmar que la creacion de tarea de limpieza permanece en `/reportes/limpieza`.
- Confirmar que no cambia disponibilidad, Caja, nomina, offline ni `/api/sync`.

### Estado 6C-0

- Contrato de evidencias/documentos en tareas completado.
- No requiere QA de navegador porque no agrega codigo, rutas, DB ni UI.
- QA futura 6C-A:
  - abrir `/tareas/{id}`;
  - confirmar seccion documental read-only;
  - confirmar estado vacio si no hay documentos;
  - confirmar que no aparece `storage_path`;
  - confirmar que no cambia estado de tarea, habitacion, Caja, nomina ni `/api/sync`.

### Estado 6C-A

- Documentos read-only en detalle de tarea implementados.
- QA manual diferida por instruccion del usuario.
- Probar `/tareas/{id}`.
- Confirmar que aparece la seccion "Documentos vinculados".
- Confirmar estado vacio claro cuando no hay documentos.
- Confirmar que no aparece boton "Vincular documento" en la tarea.
- Confirmar que no aparece `storage_path`.
- Confirmar que no cambia tarea, habitacion, Caja, nomina, offline ni `/api/sync`.
## Fase 6C-B - Vinculacion documental en tareas

Estado: QA manual diferida por instruccion del usuario.

Pruebas recomendadas:

1. Entrar con sesion hotelera y abrir `/tareas/{id}`.
2. Confirmar que la tarea pertenece al hotel actual.
3. Usar "Vincular documento".
4. Confirmar que abre `/documentos/subir?entidad_tipo=tarea&entidad_id={id}`.
5. Subir archivo permitido.
6. Confirmar que el documento aparece en la tarea.
7. Confirmar que no se muestra `storage_path`.
8. Confirmar que no se modifican Caja, pagos, nomina ni habitaciones.

## Fase 6C-F - Cierre documentos en tareas

Estado: QA manual diferida.

Pruebas acumuladas:

1. Validar detalle de tarea con y sin documentos.
2. Validar carga contextual desde tarea.
3. Validar descarga autenticada del documento ya vinculado.
4. Confirmar que tarea/habitacion no cambian de estado por adjuntar evidencia.
5. Confirmar que `/api/sync` sigue sin usarse en este flujo.

## Fase 5B - Asistencia laboral basica

Estado: QA manual diferida; fase reanclada a NP-C-D.

Pruebas recomendadas:

1. Crear o elegir trabajador activo del hotel actual.
2. Abrir `/trabajadores/{id}`.
3. Registrar asistencia con fecha actual.
4. Confirmar que aparece en asistencias recientes.
5. Intentar duplicar la misma fecha y confirmar bloqueo.
6. Confirmar cero movimientos de Caja, cero pagos reales y cero abonos.
7. Ejecutar `preflight_personal_ledger.php`.

## Fase 5C - Anticipos/prestamos/saldos laborales

Estado: QA manual diferida; fase reanclada a NP-C-C.

Pruebas recomendadas:

1. Crear o elegir trabajador activo del hotel actual.
2. Registrar un anticipo.
3. Confirmar que aparece en el ledger con saldo pendiente igual al monto.
4. Registrar un prestamo.
5. Confirmar que aparece en el ledger con saldo pendiente igual al monto.
6. Confirmar que el saldo informativo cambia solo en Personal.
7. Confirmar cero movimientos de Caja, cero pagos reales y cero abonos.
8. Ejecutar `preflight_personal_ledger.php`.

## Fase 5D-0 - Contrato pagos laborales sin Caja

Estado: sin QA manual operativa; solo contrato.

Revision recomendada:

1. Confirmar que no hay nuevos botones de pago laboral real.
2. Confirmar que `trabajador_pagos` sigue mostrandose como conceptos laborales.
3. Confirmar que no se crean movimientos de Caja ni categoria Nomina.
4. Confirmar que `/api/sync` no participa.

## Fase 7A-0 - Contrato CxC read-only

Estado: sin QA manual operativa; solo contrato.

Revision recomendada:

1. Confirmar que no aparece nueva opcion operativa de cobro.
2. Confirmar que no hay rutas POST de CxC.
3. Confirmar que no se crean movimientos de Caja.
4. Confirmar que facturacion existente no cambia.

## Fase 7A-A - Reporte CxC read-only

Estado: QA manual diferida por instruccion del usuario.

Pruebas recomendadas:

1. Entrar con sesion hotelera.
2. Abrir `/cuentas-por-cobrar`.
3. Confirmar que el reporte carga sin errores.
4. Confirmar que solo muestra reservaciones del hotel activo.
5. Probar filtro por huesped/reservacion.
6. Probar filtro por estado de reservacion.
7. Probar filtro por estado de saldo.
8. Abrir una reservacion desde el enlace del reporte.
9. Abrir una factura desde el enlace si el registro la muestra.
10. Confirmar que no existen botones de cobro, abono, pago ni Caja.
11. Confirmar que el texto indica "solo lectura" y "saldo estimado".

## Fase 7A-F - Cierre CxC read-only

Estado: QA manual diferida.

Checklist final:

1. Completar la QA de 7A-A.
2. Confirmar que 7A sigue sin POST.
3. Confirmar que no se crearon datos de cobro.
4. Confirmar que los warnings historicos quedan solo como diagnostico.

## Fase 7B-0 - Contrato CxC operativa sin Caja

Estado: sin QA manual operativa; solo contrato.

Revision recomendada:

1. Leer `docs/fase_7B_0_contrato_cxc_operativa_sin_caja.md`.
2. Confirmar que no aparecen botones nuevos de cobro.
3. Confirmar que no hay rutas nuevas POST de CxC operativa.
4. Confirmar que no se crearon tablas CxC nuevas.
5. Confirmar que Caja y cortes no cambiaron.

## Fase 8A-0 - Contrato dashboard operativo KPIs

Estado: sin QA manual operativa; solo contrato.

Revision recomendada:

1. Leer `docs/fase_8A_0_contrato_dashboard_operativo_kpis.md`.
2. Confirmar que no se creo dashboard paralelo.
3. Confirmar que `/operacion/diaria` sigue siendo la superficie prevista.
4. Confirmar que no hay nuevos POST, Caja, pagos, abonos ni `/api/sync`.

## Fase 8A-A - KPIs read-only en tablero operativo

Estado: QA manual diferida por instruccion del usuario.

Pruebas recomendadas:

1. Abrir `/operacion/diaria`.
2. Confirmar tarjetas de CxC estimada y CxC pendientes.
3. Confirmar panel "KPIs financieros estimados".
4. Confirmar que el enlace a `/cuentas-por-cobrar` funciona.
5. Confirmar que no hay botones de cobro, pago, abono ni Caja.
6. Confirmar que no hay formularios POST.

## Fase 8A-F - Cierre dashboard KPIs

Estado: QA manual diferida.

Checklist final:

1. Completar QA de 8A-A.
2. Confirmar que no existe dashboard paralelo.
3. Confirmar que los KPIs financieros se muestran como estimados.
4. Confirmar que `/api/sync` no participa.

## Fase 3D-0 - Contrato pagos proveedores con Caja

Estado: sin QA manual operativa; solo contrato.

Revision recomendada:

1. Leer `docs/fase_3D_0_contrato_pagos_proveedores_caja.md`.
2. Confirmar que no aparecen botones nuevos de pago proveedor.
3. Confirmar que no hay rutas nuevas POST para pagar CxP.
4. Confirmar que no se crearon movimientos de Caja.
5. Confirmar que no cambian saldos de CxP.

## Fase 3D-A - Simulador Caja read-only para CxP

Estado: QA manual completada por el usuario.

Confirmacion recibida: el usuario reviso el bloque pendiente y confirmo que esta bien.

Pruebas recomendadas:

1. Abrir `/cuentas-por-pagar/simulador-caja`.
2. Confirmar que sin sesion redirige a login o bloquea.
3. Confirmar que muestra el corte abierto del hotel activo.
4. Confirmar que lista las CxP del hotel actual.
5. Confirmar que una CxP con saldo y proveedor valido aparece elegible si hay corte abierto.
6. Confirmar que CxP pagada/cancelada o sin saldo aparece bloqueada.
7. Confirmar que los enlaces a CxP, proveedor, compra y Caja abren correctamente.
8. Confirmar que no existen botones de pago.
9. Confirmar que no existen formularios POST.
10. Confirmar que no cambian saldos ni movimientos CxP/Caja.

## Fase 3D-B - Contrato servicio transaccional pago proveedor

Estado: contrato revisado por el usuario; sin QA operativa porque no hay implementacion.

Confirmacion recibida: el usuario reviso el bloque pendiente y confirmo que esta bien.

Revision recomendada:

1. Leer `docs/fase_3D_B_contrato_servicio_pago_transaccional.md`.
2. Confirmar que no aparecen botones nuevos de pago.
3. Confirmar que no hay rutas POST nuevas para pagar CxP.
4. Confirmar que no se crearon movimientos CxP.
5. Confirmar que no se crearon movimientos de Caja.
6. Confirmar que saldos de CxP no cambiaron.
7. Confirmar que 3D-B real no se implemente sin backup y QA manual disponible.

## Fase 3D-C - Pago proveedor con Caja

Estado: QA manual completada por el usuario.

Backup previo confirmado:

- `backups/medisoft_hoteles_import_before_3d_payments_20260617_105846.sql`
- SHA256:
  `9584636FF545D8370B5E84171A9B1CF4EA2A8637D73285316DC83EB14E499A16`

Pruebas manuales validadas:

1. Iniciar sesion en el hotel `Maximiliano Leon`.
2. Confirmar que existe corte de Caja abierto.
3. Abrir `/cuentas-por-pagar/1` o una CxP elegible.
4. Confirmar que se muestra panel "Pago proveedor con Caja".
5. Registrar un pago parcial pequeno.
6. Confirmar mensaje de exito y nuevo saldo.
7. Confirmar que el movimiento aparece en "Movimientos referenciales".
8. Abrir Caja y confirmar gasto con categoria `Pago proveedor`.
9. Registrar pago total restante y confirmar estado `pagada`.
10. Confirmar que compras no cambian y que `/api/sync` sigue bloqueado por checker.

Evidencia:

- Pago parcial CxP `#1`: movimiento CxP `#4`, Caja `#1478`.
- Pago total restante CxP `#1`: movimiento CxP `#5`, Caja `#1479`.
- Estado tras 3D-C: CxP `#1` `pagada`, saldo `0.00`.
- Estado vigente tras 3D-D-A: CxP `#1` `parcial`, saldo `10.00`.

## Fase 3D-D-0 - Contrato reversion pago proveedor con Caja

Estado: sin QA manual operativa; solo contrato documental.

Revision recomendada:

1. Leer `docs/fase_3D_D_0_contrato_reversion_pago_proveedor_caja.md`.
2. Confirmar que no aparecen botones nuevos de reversion.
3. Confirmar que no hay rutas POST nuevas para revertir pagos proveedor.
4. Confirmar que no se crearon movimientos CxP.
5. Confirmar que no se crearon movimientos de Caja.
6. Confirmar que saldos de CxP no cambiaron.

## Fase 3D-D-A - Reversion pago proveedor con Caja

Estado: QA manual validada por el usuario.

Backup previo confirmado:

- `backups/medisoft_hoteles_import_before_3d_d_a_cxp_payment_reversal_20260619_095320.sql`
- SHA256:
  `3BB71EF0C696E1AB0CB3FC4A4B2E51319DCD654F0CEAA7E83280378F9A9001FB`

Resultado validado:

- CxP `#1`, pago proveedor `#4`, monto `10.00`.
- Movimiento CxP `CANCELACION #9`.
- Ingreso Caja `#1494`, categoria `Reversion Pago proveedor`.
- Referencia `REV-CXP-1-MOV-4`.
- Auditoria `#121`.
- Estado final CxP `#1`: `parcial`, saldo `10.00`.
- No queda QA pendiente para esta subfase; conservar esta entrada solo como rastro
  historico de validacion.

## Fase 9A-0 - Contrato conciliacion financiera read-only

Estado: sin QA manual operativa; solo contrato documental.

Revision recomendada:

1. Leer `docs/fase_9A_0_contrato_conciliacion_financiera_readonly.md`.
2. Confirmar que no hay rutas nuevas.
3. Confirmar que no hay POST, formularios ni botones operativos.
4. Confirmar que no se crean pagos, cobros, reversiones, ajustes ni movimientos Caja.
5. Confirmar que `/api/sync` sigue fuera de alcance.

La QA real correspondera a 9A-A si se implementa preflight o vista read-only.

## Fase 9A-A - Preflight conciliacion financiera read-only

Estado: validacion automatica completada; sin QA manual de UI porque no se agrego
pantalla.

Revision recomendada:

1. Ejecutar `php tools/saas/preflight_conciliacion_financiera.php`.
2. Confirmar `ERROR: 0`.
3. Confirmar que el resultado muestra `WARNING: 0` en el estado actual validado.
4. Confirmar que no hay rutas nuevas de conciliacion financiera.
5. Confirmar que no hay POST, formularios ni botones de correccion.
6. Confirmar que no se crean pagos, cobros, reversiones, ajustes ni movimientos Caja.
7. Confirmar que `/api/sync` sigue fuera de alcance.

La QA manual real correspondera a una futura 9A-B si se implementa una pantalla
GET/read-only.

## Fase 9A-B-0 - Contrato pantalla conciliacion financiera read-only

Estado: sin QA manual operativa; solo contrato documental.

Revision recomendada:

1. Leer
   `docs/fase_9A_B_0_contrato_pantalla_conciliacion_financiera_readonly.md`.
2. Confirmar que no hay ruta nueva `/operacion/conciliacion-financiera`.
3. Confirmar que no hay controlador, modelo ni vista nuevos para conciliacion.
4. Confirmar que no hay POST, formularios ni botones operativos.
5. Confirmar que no se crean pagos, cobros, reversiones, ajustes ni movimientos Caja.
6. Confirmar que la futura UI queda limitada a tokens `--brand-*`.
7. Confirmar que `/api/sync` sigue fuera de alcance.

La QA manual real correspondera a 9A-B-A si se implementa la pantalla GET/read-only.

## Fase 9A-B-A - Pantalla conciliacion financiera read-only

Estado: QA manual completada por el usuario.

Confirmacion recibida: la pantalla paso la prueba manual con sesion.

Pruebas validadas:

1. Iniciar sesion con hotel activo.
2. Abrir `/operacion/conciliacion-financiera`.
3. Confirmar etiqueta visible `Solo lectura`.
4. Confirmar resumen CxC, CxP, Caja y Auditoria.
5. Probar filtros GET por fecha, tipo, severidad, referencia y limite.
6. Confirmar que la URL cambia por query string y no por POST.
7. Confirmar que no existen botones de corregir, pagar, cobrar, revertir, ajustar ni
   compensar.
8. Confirmar que no cambian saldos, movimientos de Caja, cortes ni auditoria por
   navegar o filtrar.
9. Confirmar que los totales coinciden con
   `php tools/saas/preflight_conciliacion_financiera.php`.
10. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
    `sync_temporarily_disabled`.

No queda QA pendiente para 9A-B-A.

## Fase 9A-B-F - Cierre pantalla conciliacion financiera read-only

Estado: cierre documental; sin QA manual operativa adicional.

Revision recomendada:

1. Leer `docs/fase_9A_B_F_cierre_pantalla_conciliacion_financiera.md`.
2. Confirmar que no agrega codigo, rutas, formularios, migraciones ni escrituras.
3. Confirmar que cualquier ampliacion futura queda condicionada a contrato
   independiente.

## Fase 9C-0 - Contrato arqueo por corte y metodo read-only

Estado: contrato documental; sin QA manual operativa.

Revision recomendada:

1. Leer `docs/fase_9C_0_contrato_arqueo_metodos_pago_readonly.md`.
2. Confirmar que no agrega rutas, modelos, vistas, formularios, migraciones ni
   escrituras.
3. Confirmar que no hay botones nuevos de cerrar, recalcular, corregir o ajustar corte.
4. Confirmar que no se modificaron movimientos de Caja ni cortes.
5. Confirmar que la futura 9C-A queda limitada a preflight/read-only.

## Fase 9C-A - Preflight arqueo por corte y metodo read-only

Estado: validacion automatica completada; sin QA manual de UI porque no se agrego
pantalla.

Revision recomendada:

1. Ejecutar `php tools/saas/preflight_arqueo_metodos_pago.php`.
2. Confirmar `ERROR: 0`.
3. Confirmar que los warnings corresponden a diferencias historicas de cortes cerrados.
4. Confirmar que no hay rutas nuevas de arqueo.
5. Confirmar que no hay POST, formularios ni botones de correccion.
6. Confirmar que no se modifican movimientos de Caja ni cortes.
7. Confirmar que `/api/sync` sigue fuera de alcance.

La QA manual real correspondera a una futura 9C-B si se implementa pantalla
GET/read-only.

## Fase 9C-B-0 - Contrato pantalla arqueo por corte y metodo read-only

Estado: sin QA manual operativa; solo contrato documental.

Revision recomendada:

1. Leer
   `docs/fase_9C_B_0_contrato_pantalla_arqueo_metodos_pago_readonly.md`.
2. Confirmar que no hay ruta nueva `/caja/arqueo-metodos`.
3. Confirmar que no hay controlador, modelo ni vista nuevos para arqueo por metodo.
4. Confirmar que no hay POST, formularios ni botones operativos.
5. Confirmar que no se crean cierres, recalculos, ajustes ni ediciones de Caja.
6. Confirmar que la futura UI queda limitada a tokens `--brand-*`.
7. Confirmar que `/api/sync` sigue fuera de alcance.

La QA manual real correspondera a 9C-B-A si se implementa la pantalla GET/read-only.

## Fase 9C-B-A - Pantalla arqueo por corte y metodo read-only

Estado: QA manual validada por el usuario.

Revision completada:

1. Abrir sin sesion `/caja/arqueo-metodos` y confirmar redireccion/bloqueo.
2. Abrir con sesion de hotel y modulo Caja activo.
3. Confirmar etiqueta visible `Solo lectura`.
4. Confirmar que la URL usa filtros GET al filtrar.
5. Confirmar resumen general: cortes, movimientos, ingresos, gastos y neto.
6. Confirmar resumen por metodo: efectivo, tarjeta y transferencia.
7. Confirmar tabla de alertas y que los warnings historicos coinciden con
   `preflight_arqueo_metodos_pago.php`.
8. Confirmar tabla de cortes con comparativo movimiento vs guardado.
9. Confirmar que cortes abiertos se muestran como datos vivos, sin accion de cierre.
10. Confirmar que no hay botones de cerrar, reabrir, recalcular, corregir, ajustar,
    compensar, editar movimiento ni cambiar metodo.
11. Confirmar que navegar o filtrar no modifica cortes ni movimientos.
12. Confirmar que `/api/sync` sigue fuera de alcance.

Resultado reportado: la pantalla se ve correctamente.

## Fase 9C-B-F - Cierre pantalla arqueo por corte y metodo read-only

Estado: cierre documental; sin QA manual operativa adicional.

Revision recomendada:

1. Leer `docs/fase_9C_B_F_cierre_pantalla_arqueo_metodos_pago.md`.
2. Confirmar que no agrega codigo, rutas, formularios, migraciones ni escrituras.
3. Confirmar que cualquier ampliacion futura queda condicionada a contrato
   independiente.

## Fase 10A-0 - Contrato tablero ejecutivo integral read-only

Estado: contrato documental; sin QA manual operativa.

Revision recomendada:

1. Leer `docs/fase_10A_0_contrato_tablero_ejecutivo_readonly.md`.
2. Confirmar que no agrega ruta nueva `/reportes/ejecutivo`.
3. Confirmar que no hay controlador, modelo, vista, formulario ni migracion nuevos.
4. Confirmar que no hay POST ni acciones operativas.
5. Confirmar que la futura implementacion queda limitada a read-only y filtros GET.
6. Confirmar que `/api/sync` sigue fuera de alcance.

La QA manual real correspondera a una futura 10A-B si se implementa pantalla.

## Fase 10A-A - Preflight tablero ejecutivo integral read-only

Estado: preflight CLI/read-only; sin QA manual operativa.

Revision recomendada:

1. Ejecutar `php tools/saas/preflight_tablero_ejecutivo.php`.
2. Confirmar `ERROR: 0`.
3. Confirmar que no se registro `GET /reportes/ejecutivo`.
4. Confirmar que no existe `POST /reportes/ejecutivo`.
5. Confirmar que el preflight abre transaccion read-only y cierra con rollback.
6. Confirmar warnings conocidos:
   `gerencial-diario` archiva notificaciones, `ledger_laboral` no existe,
   `huespedes` no tiene `hotel_id` directo y `logs_auditoria` tiene historico sin
   hotel.
7. Ejecutar `php tools/saas/health_check_fase_1a.php`.
8. Confirmar `ERROR: 0`.

La QA manual real correspondera a una futura 10A-B si se implementa pantalla
GET/read-only.

## Fase 10A-B-0 - Contrato pantalla tablero ejecutivo read-only

Estado: contrato documental; sin QA manual operativa.

Revision recomendada:

1. Leer `docs/fase_10A_B_0_contrato_pantalla_tablero_ejecutivo_readonly.md`.
2. Confirmar que no registra `GET /reportes/ejecutivo`.
3. Confirmar que no registra `POST /reportes/ejecutivo`.
4. Confirmar que no agrega controlador, modelo, vista, navegacion, formulario,
   migracion ni escritura.
5. Confirmar que la futura pantalla queda limitada a tokens `--brand-*`, etiqueta
   `Solo lectura`, filtros GET y cero acciones operativas.
6. Confirmar que `gerencial-diario` no puede reutilizarse como fuente read-only pura
   mientras archive notificaciones.
7. Confirmar que `/api/sync` sigue fuera de alcance.

La QA manual real correspondera a 10A-B-A si se implementa la pantalla
`GET /reportes/ejecutivo`.

## Fase 10A-B-A - Pantalla tablero ejecutivo read-only

Estado: QA manual validada por el usuario.

Revision recomendada:

1. Abrir `/reportes/ejecutivo` sin sesion y confirmar redireccion a login.
2. Abrir `/reportes/ejecutivo` con sesion de hotel.
3. Confirmar etiqueta visible `Solo lectura`.
4. Confirmar filtros GET: periodo, desde, hasta, area, estado y alertas.
5. Confirmar que no hay formularios POST ni CSRF.
6. Confirmar que no hay `hotel_id` editable.
7. Confirmar KPIs de operacion, finanzas, inventario, tareas/personal,
   documentos/auditoria y alertas.
8. Confirmar enlaces GET a reportes, conciliacion, operacion diaria, arqueo,
   inventario y tareas.
9. Filtrar y confirmar que no se modifican notificaciones, saldos, cortes,
   movimientos, inventario, tareas ni documentos.
10. Ejecutar `php tools/saas/preflight_tablero_ejecutivo.php` y confirmar
    `ERROR: 0`.
11. Ejecutar `php tools/saas/health_check_fase_1a.php` y confirmar `ERROR: 0`.

QA manual validada. El cierre documental corresponde a 10A-B-F.

## Fase 10A-B-F - Cierre tablero ejecutivo read-only

Estado: cierre documental; sin QA manual adicional.

Revision recomendada:

1. Leer `docs/fase_10A_B_F_cierre_tablero_ejecutivo.md`.
2. Confirmar que no agrega codigo, rutas, formularios, migraciones ni escrituras.
3. Confirmar que registra la QA manual validada de `GET /reportes/ejecutivo`.
4. Confirmar que el siguiente bloque recomendado queda como contrato independiente
   para separar lectura de reporte gerencial diario y archivado de notificaciones.

## Fase 10B-0 - Contrato reporte gerencial y notificaciones

Estado: contrato documental; sin QA manual funcional.

Revision recomendada:

1. Leer `docs/fase_10B_0_contrato_reporte_gerencial_notificaciones.md`.
2. Confirmar que no agrega codigo, rutas, formularios, migraciones ni escrituras.
3. Confirmar que identifica el side effect actual en reporte gerencial HTML y PDF.
4. Confirmar que la futura QA exigira que abrir el reporte directo no cambie
   `notificaciones`.
5. Confirmar que cualquier archivado queda reservado para el flujo controlado de
   notificaciones.

## Fase 10B-A - Separacion reporte gerencial y notificaciones

Estado: QA manual validada por el usuario.

Prueba manual validada:

1. Iniciar sesion en un hotel con modulo reportes.
2. Identificar una notificacion activa de reporte gerencial diario.
3. Abrir `/reportes/gerencial-diario?fecha=YYYY-MM-DD`.
4. Confirmar que el reporte carga correctamente.
5. Confirmar que la notificacion sigue activa y no fue archivada por abrir el reporte.
6. Abrir `/reportes/gerencial-diario/pdf?fecha=YYYY-MM-DD`.
7. Confirmar que el PDF se genera o descarga correctamente.
8. Confirmar que la notificacion sigue activa y no fue archivada por descargar el PDF.
9. Abrir esa notificacion desde `/notificaciones`.
10. Confirmar que solo ese flujo controlado archiva o marca el estado esperado.
11. Ejecutar `php tools/saas/preflight_tablero_ejecutivo.php` y confirmar `ERROR: 0`.
12. Ejecutar `php tools/saas/health_check_fase_1a.php` y confirmar `ERROR: 0`.

El usuario confirmo que la prueba paso perfectamente. El cierre documental corresponde
a 10B-F.

## Fase 10B-F - Cierre reporte gerencial y notificaciones

Estado: cierre documental; sin QA manual adicional.

Revision recomendada:

1. Leer `docs/fase_10B_F_cierre_reporte_gerencial_notificaciones.md`.
2. Confirmar que no agrega codigo, rutas, formularios, migraciones ni escrituras.
3. Confirmar que registra la QA manual validada de 10B-A.
4. Confirmar que el siguiente bloque recomendado queda como contrato independiente.

## Fase 11A-0 - Contrato perfil operativo de huesped read-only

Estado: contrato documental; sin QA manual operativa.

Revision recomendada:

1. Leer `docs/fase_11A_0_contrato_perfil_huesped_readonly.md`.
2. Confirmar que no agrega codigo, rutas, formularios, migraciones ni escrituras.
3. Confirmar que la futura 11A-A no alterara formularios existentes de huespedes.
4. Confirmar que la futura lectura se limita al hotel actual.
5. Confirmar que no autoriza CxC, cobros, pagos, check-in/check-out, documentos,
   tareas, permisos/auth, PWA/offline ni `/api/sync`.

## Fase 11A-A - Perfil operativo de huesped read-only

Estado: QA manual validada por el usuario.

Revision validada:

1. Abrir `/huespedes/{id}` sin sesion y confirmar redireccion a login.
2. Abrir `/huespedes/{id}` con sesion de hotel.
3. Confirmar que aparece el bloque `Perfil operativo`.
4. Confirmar etiqueta visible `Solo lectura`.
5. Confirmar que no hay botones, enlaces ni formularios dentro del bloque nuevo.
6. Confirmar que los formularios existentes de vehiculos siguen visualmente igual.
7. Confirmar que clasificacion, proxima estancia, saldo CxC, documentos y alertas
   corresponden al huesped del hotel actual.
8. Confirmar que abrir o refrescar la ficha no cambia saldos, reservaciones,
   documentos, vehiculos ni notificaciones.
9. Ejecutar `php tools/saas/health_check_fase_1a.php` y confirmar `ERROR: 0`.

Resultado: el usuario confirmo que el bloque se muestra correctamente, `Solo lectura`
esta visible y no hay acciones/formularios dentro del bloque nuevo.

## Fase 11A-F - Cierre perfil operativo de huesped read-only

Estado: cierre documental; sin QA manual adicional.

Revision recomendada:

1. Leer `docs/fase_11A_F_cierre_perfil_huesped_readonly.md`.
2. Confirmar que no agrega codigo, rutas, formularios, migraciones ni escrituras.
3. Confirmar que registra la QA manual validada de 11A-A.
4. Confirmar que el siguiente bloque recomendado queda como contrato independiente.

## Fase 5E-0 - Contrato pagos laborales con Caja

Estado: contrato documental; sin QA manual operativa.

Revision recomendada:

1. Leer `docs/fase_5E_0_contrato_pagos_laborales_caja.md`.
2. Confirmar que no agrega codigo, rutas, formularios, migraciones ni escrituras.
3. Confirmar que `trabajador_pagos` queda como conceptos laborales y no pagos reales.
4. Confirmar que el futuro pago real exige entidad independiente.
5. Confirmar que no hay movimientos de Caja, categorias de Caja ni pagos laborales en
   esta subfase.
6. Confirmar que la siguiente accion segura es solo un preflight CLI/read-only 5E-A.

La QA manual real correspondera a una futura fase de simulador o pago real con
trabajador activo, backup y autorizacion explicita.

## Fase 5E-A - Preflight pagos laborales con Caja

Estado: preflight CLI/read-only completado; sin QA manual funcional.

Revision recomendada:

1. Ejecutar `php tools/saas/preflight_personal_pagos_caja.php`.
2. Confirmar `ERROR: 0`.
3. Confirmar que no existe `trabajador_pagos_caja` antes de migracion 5E-B.
4. Confirmar que `trabajador_pagos` no contiene columnas financieras de Caja.
5. Confirmar que no hay rutas de pago laboral con Caja.
6. Confirmar que no existe `TrabajadorPagoCajaService`.
7. Confirmar que no hay movimientos/categorias de Caja con nomina o pago laboral.
8. Confirmar que `php tools/saas/health_check_fase_1a.php` mantiene `ERROR: 0`.

Resultado automatico actual:

- Preflight 5E-A: `OK: 34`, `WARNING: 1`, `ERROR: 0`.
- Health general: `OK: 305`, `WARNING: 25`, `ERROR: 0`.

Advertencia vigente:

- No hay trabajadores activos para una QA futura de pago real.

Antes de una fase operativa de pago laboral se debe contar con al menos un trabajador
activo del hotel de prueba, backup previo y autorizacion explicita.

## Fase 5E-B-0 - Contrato migracion pagos laborales con Caja

Estado: contrato documental; sin QA manual funcional.

Revision recomendada:

1. Leer `docs/fase_5E_B_0_contrato_migracion_pagos_laborales_caja.md`.
2. Confirmar que no agrega archivo SQL ni ejecuta migracion.
3. Confirmar que la tabla futura sera `trabajador_pagos_caja`.
4. Confirmar que `trabajador_pagos` no se altera ni se reutiliza como pago real.
5. Confirmar que la migracion futura no creara movimientos de Caja ni categorias.
6. Confirmar que la referencia futura sera unica por hotel y compartida con Caja.
7. Confirmar que rollback futuro solo permite eliminar la tabla si esta vacia.
8. Confirmar que el siguiente paso 5E-B-A requiere backup y autorizacion explicita
   para tocar DB.

No hay prueba manual en navegador porque no se implementa UI, ruta, formulario,
servicio ni pago real.

## Fase 5E-B-A - Migracion pagos laborales con Caja

Estado: migracion aplicada; sin QA manual funcional.

Revision recomendada:

1. Confirmar que existe `trabajador_pagos_caja`.
2. Confirmar que `SELECT COUNT(*) FROM trabajador_pagos_caja` devuelve `0`.
3. Confirmar que la migracion
   `20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql` figura como `ejecutada`.
4. Confirmar que no cambiaron conteos operativos por pagos laborales:
   `trabajador_pagos`, `trabajador_anticipos`, `trabajador_prestamos`.
5. Confirmar que no se crearon movimientos/categorias de Caja por nomina.
6. Ejecutar `php tools/saas/preflight_personal_pagos_caja.php` y confirmar
   `ERROR: 0`.
7. Ejecutar `php tools/saas/health_check_fase_1a.php` y confirmar `ERROR: 0`.

Resultado automatico actual:

- Preflight 5E: `OK: 37`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 307`, `WARNING: 26`, `ERROR: 0`.

No hay prueba manual en navegador porque no se implementa UI, ruta, formulario,
servicio ni pago real.

## Fase 5E-C-0 - Contrato simulador pago laboral con Caja

Estado: contrato documental; sin QA manual funcional.

Revision recomendada:

1. Leer `docs/fase_5E_C_0_contrato_simulador_pago_laboral_caja.md`.
2. Confirmar que no agrega ruta ni vista todavia.
3. Confirmar que la ruta futura candidata es
   `GET /trabajadores/pagos-caja/simulador`.
4. Confirmar que la futura pantalla debe ser solo GET/read-only.
5. Confirmar que no autoriza POST, token, servicio ni pago laboral real.
6. Confirmar que no autoriza movimientos de Caja ni categorias de Caja.
7. Confirmar que no autoriza liquidacion de anticipos/prestamos.
8. Confirmar que 5E-C-A requerira autorizacion explicita para tocar rutas,
   controlador/modelo read-only, vista y checkers.

No hay prueba manual en navegador porque no se implementa UI ni ruta en esta subfase.

## Fase 5E-C-A - Simulador pago laboral con Caja

Estado: validado manualmente por el usuario.

Revision recomendada:

1. Abrir `/trabajadores`.
2. Confirmar que existe el boton `Simulador Caja`.
3. Abrir `/trabajadores/pagos-caja/simulador`.
4. Confirmar que la pantalla muestra etiqueta `Solo GET / Read-only`.
5. Confirmar que el formulario usa filtros GET y no hay boton de registrar pago.
6. Confirmar que muestra corte abierto o bloqueo por falta de corte.
7. Confirmar que muestra trabajadores con saldo estimado, monto simulado y diagnostico.
8. Abrir la ficha de un trabajador y confirmar el enlace `Simulador Caja` con
   `trabajador_id`.
9. Probar tarjeta o transferencia sin referencia y confirmar bloqueo visual.
10. Confirmar que no cambia el conteo de `trabajador_pagos_caja`.
11. Confirmar que no cambia el conteo de `movimientos_caja`.
12. Confirmar que no hay movimientos/categorias de Caja por nomina.

Resultado automatico actual:

- Preflight 5E: `OK: 41`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 309`, `WARNING: 26`, `ERROR: 0`.
- HTTP sin sesion: `303`, no `404`.

No avanzar a pago real, POST, token, servicio transaccional ni reversion sin contrato
5E-D-0 y autorizacion explicita.

Resultado manual 5E-C-A:

- El usuario confirmo que todas las pruebas pasaron.

## Fase 5E-C-F - Cierre simulador pago laboral con Caja

Estado: cierre documental completado.

Revision recomendada:

1. Leer `docs/fase_5E_C_F_cierre_simulador_pago_laboral_caja.md`.
2. Confirmar que solo documenta la QA manual validada.
3. Confirmar que no agrega codigo, rutas, formularios ni escrituras.

## Fase 5E-D-0 - Contrato servicio pago laboral con Caja

Estado: contrato documental; sin QA manual funcional.

Revision recomendada:

1. Leer `docs/fase_5E_D_0_contrato_servicio_pago_laboral_caja.md`.
2. Confirmar que no implementa servicio ni ruta POST.
3. Confirmar que define `TrabajadorPagoCajaService` como futuro servicio.
4. Confirmar que exige backup, token de un solo uso, transaccion, locks y rollback.
5. Confirmar que no autoriza pago real todavia.

## Fase 5E-D-A - Pago laboral con Caja controlado

Estado: implementacion tecnica validada automaticamente; QA manual pendiente del usuario.

Revision recomendada:

1. Abrir `/trabajadores`.
2. Entrar a la ficha de un trabajador activo.
3. Confirmar panel `Pago laboral con Caja`.
4. Si esta bloqueado por saldo, registrar un concepto laboral a favor desde el ledger.
5. Recargar la ficha y confirmar `Maximo elegible` positivo.
6. Registrar pago con referencia unica.
7. Confirmar mensaje con `Movimiento Caja #...`.
8. Confirmar que `trabajador_pagos_caja` aumento en 1.
9. Confirmar que `movimientos_caja` aumento en 1 con categoria `Pago laboral`.
10. Confirmar que el simulador descuenta el pago registrado.
11. Intentar repetir la misma referencia y confirmar bloqueo.

Resultado automatico actual:

- Lint PHP: OK en archivos tocados.
- Rollback `probar_pago_laboral_caja.php`: OK sin cambios persistentes.
- Preflight 5E: `OK: 41`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 313`, `WARNING: 25`, `ERROR: 0`.

No probar reversion; no existe todavia en 5E-D-A.

Resultado manual 5E-D-A:

- El usuario confirmo que la prueba manual paso correctamente.
- La ficha muestra bruto laboral `$100.00`, pagos Caja `$100.00` y disponible `$0.00`.
- El panel de pago laboral queda bloqueado por saldo no positivo.

## Fase 5E-D-F - Cierre pago laboral con Caja

Estado: cierre documental aplicado despues de QA manual.

Revision recomendada:

1. Leer `docs/fase_5E_D_F_cierre_pago_laboral_caja.md`.
2. Confirmar que no agrega codigo ni escrituras.
3. Confirmar que mantiene fuera de alcance reversion, pagos masivos, nomina automatica
   y abonos/liquidaciones.
4. Confirmar que el siguiente paso requiere contrato independiente y autorizacion
   explicita si toca modelo, controlador, ruta, vista o Caja.

## Fase 5E-E-F - Cierre historial, reversion y reporte pagos laborales con Caja

Estado: cierre documental aplicado despues de QA manual.

Revision registrada:

1. Historial read-only de pagos laborales Caja en ficha: validado manualmente.
2. Reversion controlada individual: validada manualmente.
3. Reporte read-only de pagos laborales Caja: validado manualmente.
4. Export CSV read-only del reporte: validado manualmente.
5. Preflight pagos laborales Caja: `OK: 45`, `WARNING: 0`, `ERROR: 0`.
6. Health general: `OK: 313`, `WARNING: 25`, `ERROR: 0`.
7. Ruta export CSV local sin sesion: `303` a `/login`, sin 404.

No avanzar a pagos masivos, nomina automatica, recibos oficiales, dispersion,
liquidaciones automaticas, permisos/auth, PWA/offline ni `/api/sync` sin contrato
independiente y autorizacion explicita.

## Fase 5E-G-0 - Contrato preview read-only de nomina por periodo

Estado: contrato documental aplicado.

Revision recomendada:

1. Leer `docs/fase_5E_G_0_contrato_nomina_periodo_preview.md`.
2. Confirmar que no agrega codigo, rutas, modelos, vistas, formularios ni migraciones.
3. Confirmar que la ruta futura candidata es GET/read-only:
   `/trabajadores/nomina/preview`.
4. Confirmar que no autoriza pagos masivos, nomina automatica, recibos oficiales,
   timbrado, dispersion ni liquidaciones automaticas.
5. Confirmar que mantiene fuera de alcance permisos/auth, PWA/offline y `/api/sync`.
6. Para una futura 5E-G-A, exigir autorizacion explicita si se toca ruta, controlador,
   modelo, vista o checkers.

QA futura sugerida para 5E-G-A:

1. Abrir preview sin fechas y confirmar bloqueo por periodo requerido.
2. Seleccionar periodo valido y confirmar que solo aparecen trabajadores del hotel
   actual.
3. Confirmar bruto, deducciones informativas, pagos Caja aplicados y neto sugerido.
4. Confirmar que pagos revertidos no descuentan como pagos vigentes.
5. Confirmar que no hay POST, pago masivo, movimiento de Caja ni escritura laboral.

## Fase 5E-G-A - Preview read-only de nomina por periodo

Estado: implementacion tecnica validada automaticamente; QA manual pendiente del
usuario.

Resultado automatico actual:

- Lint PHP: OK en modelo, controlador, vista, index, rutas y checkers.
- Preflight pagos laborales Caja: `OK: 47`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- Ruta local sin sesion: `GET /trabajadores/nomina/preview` responde `303` a login.
- Carga de rutas: `ROUTES_LOAD_OK`.
- Prueba CLI del modelo: hotel `4`, trabajadores `1`, bloqueos `0`, neto `100.00`.

Revision manual recomendada:

1. Abrir `/trabajadores/nomina/preview`.
2. Confirmar que sin fechas muestra bloqueo por periodo requerido.
3. Seleccionar un periodo valido.
4. Confirmar que aparecen solo trabajadores del hotel actual.
5. Confirmar bruto, deducciones informativas, pagos Caja aplicados y neto sugerido.
6. Activar/desactivar `Pagos Caja` y confirmar que no escribe datos.
7. Activar `Con saldo` y confirmar que filtra trabajadores con neto positivo.
8. Confirmar que no hay botones de pago masivo, recibo oficial, dispersion ni POST.

Resultado manual 5E-G-A:

- El usuario confirmo que la prueba manual paso correctamente.

## Fase 5E-G-F - Cierre preview read-only de nomina por periodo

Estado: cierre documental aplicado despues de QA manual.

Revision recomendada:

1. Leer `docs/fase_5E_G_F_cierre_nomina_periodo_preview.md`.
2. Confirmar que no agrega codigo ni escrituras.
3. Confirmar que mantiene fuera de alcance pagos masivos, nomina automatica, recibos
   oficiales, timbrado, dispersion y liquidaciones automaticas.
4. Confirmar que el siguiente paso requiere contrato independiente y autorizacion
   explicita si toca modelo, controlador, ruta, vista, Caja o datos.

## Fase 5E-H-0 - Contrato export CSV del preview de pre-nomina

Estado: contrato documental aplicado; sin QA manual funcional.

Revision recomendada:

1. Leer `docs/fase_5E_H_0_contrato_export_csv_nomina_preview.md`.
2. Confirmar que no agrega codigo, rutas, modelos, vistas, formularios ni migraciones.
3. Confirmar que la ruta futura candidata es GET/read-only:
   `/trabajadores/nomina/preview/exportar`.
4. Confirmar que la exportacion futura reutilizara los mismos filtros y calculos del
   preview.
5. Confirmar que no autoriza pagos masivos, nomina automatica, recibos oficiales,
   timbrado, dispersion ni liquidaciones automaticas.
6. Confirmar que mantiene fuera de alcance permisos/auth, PWA/offline y `/api/sync`.
7. Para una futura 5E-H-A, exigir autorizacion explicita si se toca ruta, controlador,
   modelo, vista o checkers.

QA futura sugerida para 5E-H-A:

1. Abrir `/trabajadores/nomina/preview` con periodo valido.
2. Descargar CSV desde el preview con los mismos filtros.
3. Confirmar que el CSV contiene solo trabajadores del hotel actual.
4. Confirmar que totales y filas coinciden con la pantalla.
5. Probar `incluir_pagos_caja` activado y desactivado.
6. Probar `solo_con_saldo` y confirmar que filtra igual que la pantalla.
7. Intentar exportar sin periodo y confirmar bloqueo controlado.
8. Confirmar que no hay POST, pago masivo, recibo oficial, dispersion, movimiento de
   Caja ni escritura laboral.

## Fase 5E-H-A - Export CSV del preview de pre-nomina

Estado: implementacion tecnica validada automaticamente; QA manual pendiente del
usuario.

Revision manual recomendada:

1. Abrir `/trabajadores/nomina/preview`.
2. Confirmar que sin fechas el preview sigue bloqueado por periodo requerido.
3. Seleccionar un periodo valido.
4. Presionar `Exportar CSV`.
5. Confirmar que el navegador descarga un archivo CSV.
6. Confirmar que los encabezados incluyen periodo, trabajador, bruto, deducciones,
   pagos Caja, reversiones, neto y pendiente.
7. Confirmar que filas y totales coinciden con la pantalla.
8. Probar `Pagos Caja` activado/desactivado y repetir descarga.
9. Probar `Con saldo` y confirmar que el CSV respeta el filtro.
10. Intentar exportar sin periodo y confirmar que redirige al preview sin archivo
    ambiguo.
11. Confirmar que no hay POST, pago masivo, recibo oficial, dispersion, movimiento de
    Caja ni escritura laboral.

Resultado automatico esperado:

- Lint PHP: OK en controlador, vista, rutas y checkers.
- Preflight pagos laborales Caja: `ERROR: 0`.
- Health general: `ERROR: 0`.
- Ruta local sin sesion:
  `GET /trabajadores/nomina/preview/exportar` responde `303` a login, sin 404.

Resultado manual 5E-H-A:

- El usuario confirmo que la prueba manual paso correctamente.

## Fase 5E-H-F - Cierre export CSV del preview de pre-nomina

Estado: cierre documental aplicado despues de QA manual.

Revision recomendada:

1. Leer `docs/fase_5E_H_F_cierre_export_csv_nomina_preview.md`.
2. Confirmar que no agrega codigo ni escrituras.
3. Confirmar que registra la QA manual validada del export CSV.
4. Confirmar que mantiene fuera de alcance nomina automatica, recibos oficiales,
   timbrado, dispersion, pagos masivos, liquidaciones automaticas, permisos/auth,
   PWA/offline y `/api/sync`.
5. Confirmar que el siguiente paso requiere contrato independiente y autorizacion
   explicita si toca modelo, controlador, ruta, vista, Caja o datos.

## Fase 5E-I-0 - Contrato recibo laboral informativo

Estado: contrato documental aplicado; sin QA manual funcional.

Revision recomendada:

1. Leer `docs/fase_5E_I_0_contrato_recibo_laboral_informativo.md`.
2. Confirmar que no agrega codigo, rutas, modelos, vistas, formularios ni migraciones.
3. Confirmar que la ruta futura candidata es GET/read-only:
   `/trabajadores/{id}/recibo-laboral`.
4. Confirmar que el recibo futuro sera informativo, no fiscal y no operativo.
5. Confirmar que no autoriza pagos masivos, nomina automatica, CFDI, timbrado,
   dispersion ni liquidaciones automaticas.
6. Confirmar que mantiene fuera de alcance permisos/auth, PWA/offline y `/api/sync`.
7. Para una futura 5E-I-A, exigir autorizacion explicita si se toca ruta, controlador,
   modelo, vista o checkers.

QA futura sugerida para 5E-I-A:

1. Abrir recibo sin sesion y confirmar redireccion a login.
2. Abrir recibo de trabajador del hotel actual con periodo valido.
3. Confirmar etiqueta visible `Informativo / No fiscal / No genera pago`.
4. Confirmar que totales coinciden con el preview de pre-nomina.
5. Confirmar que pagos Caja y reversiones son solo lectura.
6. Intentar abrir sin periodo y confirmar bloqueo controlado.
7. Confirmar que no hay POST, pago masivo, timbrado, dispersion, movimiento de Caja ni
   escritura laboral.

## Fase 5E-I-A - Recibo laboral informativo read-only

Estado: implementacion tecnica validada automaticamente; QA manual pendiente del
usuario.

Revision manual recomendada:

1. Abrir `/trabajadores/nomina/preview`.
2. Seleccionar un periodo valido.
3. Entrar al enlace `Recibo` de un trabajador.
4. Confirmar que la ruta conserva `fecha_inicio`, `fecha_fin` e
   `incluir_pagos_caja`.
5. Confirmar avisos visibles `Solo lectura`, `No fiscal` y `No genera pago`.
6. Confirmar que bruto, deducciones, pagos Caja, neto y pendiente coinciden con el
   preview para ese trabajador.
7. Probar `Pagos Caja` activado/desactivado desde el recibo.
8. Abrir recibo sin periodo y confirmar bloqueo controlado.
9. Confirmar que no hay POST, boton de pagar, timbrado, dispersion, movimiento de
   Caja ni escritura laboral.

Resultado automatico esperado:

- Lint PHP: OK en controlador, modelo, vistas, rutas y checkers.
- Preflight pagos laborales Caja: `ERROR: 0`.
- Health general: `ERROR: 0`.
- Ruta local sin sesion:
  `GET /trabajadores/{id}/recibo-laboral` responde `303` a login, sin 404.

Resultado manual 5E-I-A:

- El usuario confirmo que la prueba manual paso correctamente.

## Fase 5E-I-F - Cierre recibo laboral informativo

Estado: cierre documental aplicado despues de QA manual.

Revision recomendada:

1. Leer `docs/fase_5E_I_F_cierre_recibo_laboral_informativo.md`.
2. Confirmar que no agrega codigo ni escrituras.
3. Confirmar que registra la QA manual validada del recibo laboral informativo.
4. Confirmar que mantiene fuera de alcance nomina automatica, recibos fiscales,
   PDF oficial, timbrado, CFDI, dispersion, pagos masivos, liquidaciones automaticas,
   permisos/auth, PWA/offline y `/api/sync`.
5. Confirmar que el siguiente paso requiere contrato independiente si toca modelo,
   controlador, ruta, vista, Caja, documentos descargables o datos.

## Fase 5E-J-0 - Contrato recibo laboral PDF informativo

Estado: contrato documental aplicado; sin QA manual funcional.

Revision recomendada:

1. Leer `docs/fase_5E_J_0_contrato_recibo_laboral_pdf_informativo.md`.
2. Confirmar que no agrega codigo, rutas, modelos, vistas, servicios PDF ni
   migraciones.
3. Confirmar que la ruta futura candidata es GET/read-only:
   `/trabajadores/{id}/recibo-laboral/pdf`.
4. Confirmar que el PDF futuro sera informativo, no fiscal y no operativo.
5. Confirmar que no autoriza PDF oficial, recibos fiscales, CFDI, timbrado,
   folios oficiales, dispersion, storage por defecto ni pagos masivos.
6. Confirmar que mantiene fuera de alcance permisos/auth, PWA/offline y `/api/sync`.
7. Para una futura 5E-J-A, exigir autorizacion explicita si se toca ruta,
   controlador, renderer PDF/helper, vista, checkers o modelo read-only.

QA futura sugerida para 5E-J-A:

1. Abrir ruta PDF sin sesion y confirmar redireccion a login.
2. Abrir PDF de trabajador del hotel actual con periodo valido.
3. Confirmar etiqueta visible `PDF informativo / No fiscal / No genera pago`.
4. Confirmar que totales coinciden con recibo HTML y preview de pre-nomina.
5. Confirmar que no guarda archivo en storage por defecto.
6. Intentar generar sin periodo y confirmar bloqueo controlado.
7. Confirmar que no hay POST, pago masivo, timbrado, dispersion, movimiento de Caja ni
   escritura laboral.

## Fase 5E-J-A - Recibo laboral PDF informativo

Estado: implementacion tecnica validada automaticamente; QA manual pendiente del
usuario.

Revision manual recomendada:

1. Abrir `/trabajadores/nomina/preview`.
2. Seleccionar un periodo valido.
3. Entrar al enlace `Recibo` de un trabajador.
4. Presionar `PDF informativo`.
5. Confirmar que el navegador descarga un PDF.
6. Confirmar aviso visible `PDF informativo / No fiscal / No genera pago`.
7. Confirmar que bruto, deducciones, pagos Caja, neto y pendiente coinciden con el
   recibo HTML.
8. Probar `Pagos Caja` activado/desactivado y repetir descarga.
9. Intentar generar sin periodo y confirmar redireccion/bloqueo controlado.
10. Confirmar que no hay POST, pago masivo, timbrado, dispersion, movimiento de Caja,
    storage ni escritura laboral.

Resultado automatico esperado:

- Lint PHP: OK en controlador, servicio PDF, vista, rutas y checkers.
- Preflight pagos laborales Caja: `ERROR: 0`.
- Health general: `ERROR: 0`.
- Ruta local sin sesion:
  `GET /trabajadores/{id}/recibo-laboral/pdf` responde `303` a login, sin 404.

## Fase 5E-K-0 - Contrato cierre/aprobacion de pre-nomina

Estado: contrato documental aplicado; sin QA manual funcional.

Revision recomendada:

1. Leer `docs/fase_5E_K_0_contrato_cierre_aprobacion_prenomina.md`.
2. Confirmar que no agrega codigo, rutas, controladores, modelos, vistas, servicios,
   migraciones, permisos, Caja, datos, storage, PWA/offline ni `/api/sync`.
3. Confirmar que separa lectura read-only de periodos de acciones mutantes futuras.
4. Confirmar que las rutas POST candidatas no quedan implementadas ni autorizadas.
5. Confirmar que no autoriza nomina oficial, CFDI, timbrado, dispersion, pagos
   masivos, movimientos de Caja ni liquidaciones automaticas.
6. Para una futura 5E-K-A, exigir autorizacion explicita si se toca ruta,
   controlador, modelo, vista, checkers y, si aplica, servicio, migraciones,
   permisos o Caja.

QA futura sugerida para 5E-K-A:

1. Abrir lectura de periodos sin sesion y confirmar redireccion a login.
2. Confirmar que el preview de periodo sigue siendo read-only.
3. Confirmar bloqueo con fechas faltantes o invalidas.
4. Confirmar scope por hotel y que no mezcla trabajadores de otros hoteles.
5. Confirmar CSRF, permiso y token en cualquier POST autorizado.
6. Confirmar que no se crean movimientos de Caja, CFDI, timbrado, dispersion ni pago
   masivo.
7. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
   `sync_temporarily_disabled`.

## Fase 5E-K-A - Periodos de pre-nomina read-only

Estado: implementacion tecnica pendiente de QA manual.

Revision manual recomendada:

1. Abrir `/trabajadores/nomina/periodos` con sesion activa.
2. Confirmar que carga sin 404.
3. Cambiar tipo semanal, quincenal y mensual.
4. Probar rango manual valido.
5. Probar rango manual invalido y confirmar bloqueo visible.
6. Entrar al detalle de un periodo.
7. Abrir `Preview completo` y confirmar que conserva periodo/filtros.
8. Abrir `Recibo` de un trabajador y confirmar que conserva periodo.
9. Confirmar que no hay cierre, aprobacion, anulacion, pago real ni POST.
10. Confirmar que no se genera movimiento de Caja ni cambio de datos.

Resultado automatico esperado:

- Lint PHP: OK en rutas, controlador, modelo, vista y checkers.
- Preflight pagos laborales Caja: `ERROR: 0`.
- Health general: `ERROR: 0`.
- Ruta local sin sesion:
  `GET /trabajadores/nomina/periodos` responde `303` a login, sin 404.

Resultado manual 5E-K-A:

- El usuario confirmo que la prueba manual paso correctamente.

## Fase 5E-K-F - Cierre periodos de pre-nomina read-only

Estado: cierre documental aplicado despues de QA manual.

Revision recomendada:

1. Leer `docs/fase_5E_K_F_cierre_periodos_prenomina_read_only.md`.
2. Confirmar que no agrega codigo ni escrituras.
3. Confirmar que registra la QA manual validada de periodos de pre-nomina.
4. Confirmar que mantiene fuera de alcance cierre real, aprobacion, anulacion,
   snapshots, nomina oficial, CFDI, timbrado, dispersion, pagos masivos,
   movimientos de Caja, migraciones, permisos/auth, PWA/offline y `/api/sync`.
5. Confirmar que el siguiente paso exige contrato independiente para cualquier POST,
   migracion, permiso, auditoria o persistencia real.

## Fase 5E-L-0 - Contrato cierre/aprobacion persistente de pre-nomina

Estado: contrato documental aplicado; sin QA manual funcional.

Revision recomendada:

1. Leer `docs/fase_5E_L_0_contrato_cierre_aprobacion_persistente_prenomina.md`.
2. Confirmar que no agrega codigo, rutas, controladores, modelos, vistas, servicios,
   migraciones, permisos, Caja, datos, storage, PWA/offline ni `/api/sync`.
3. Confirmar que separa preview read-only, cierre persistente, aprobacion
   administrativa y pago laboral real con Caja.
4. Confirmar que las rutas POST candidatas no quedan implementadas ni autorizadas.
5. Confirmar que exige backup, migracion y autorizacion separada antes de persistir
   snapshots.
6. Confirmar que no autoriza nomina oficial, CFDI, timbrado, dispersion, pagos
   masivos, movimientos de Caja ni liquidaciones automaticas.

QA futura sugerida para 5E-L-A:

1. Cerrar sin sesion debe redirigir a login.
2. Cerrar sin CSRF o sin token debe bloquearse.
3. Cerrar periodo valido debe crear snapshot scoped por hotel.
4. Cierre duplicado del mismo periodo debe bloquearse o exigir politica explicita.
5. Aprobar periodo de otro hotel debe bloquearse.
6. Aprobar no debe modificar el snapshot.
7. Anular debe exigir motivo y no borrar fisicamente.
8. Confirmar que no se crean movimientos de Caja, CFDI, timbrado, dispersion ni pago
   masivo.
9. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
   `sync_temporarily_disabled`.

## Fase 5E-L-A - Cierre/aprobacion persistente de pre-nomina

Estado: implementacion local pendiente de QA manual.

Revision manual recomendada:

1. Confirmar que se aplico backup SQL local antes de la migracion.
2. Abrir `/trabajadores/nomina/periodos` con sesion activa.
3. Seleccionar un periodo con trabajadores activos y sin bloqueos de preview.
4. Pulsar `Cerrar snapshot`.
5. Confirmar mensaje de exito y que el snapshot aparece en historial.
6. Abrir el snapshot y validar importes contra el preview del mismo rango.
7. Intentar cerrar el mismo rango de nuevo y confirmar bloqueo por duplicado.
8. Aprobar un snapshot cerrado y confirmar estado `aprobado`.
9. Intentar anular sin motivo y confirmar bloqueo.
10. Anular con motivo y confirmar evento de anulacion.
11. Confirmar que no se crean movimientos de Caja ni registros nuevos de pago Caja.
12. Confirmar que no hay CFDI, timbrado, dispersion ni pago masivo.
13. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
    `sync_temporarily_disabled`.

Resultado automatico esperado:

- Lint PHP: OK en rutas, controlador, modelo, servicio, vistas y checkers.
- Migracion local: registrada como `ejecutada`.
- Preflight pagos laborales Caja: `ERROR: 0`.
- Health general: `ERROR: 0`.
