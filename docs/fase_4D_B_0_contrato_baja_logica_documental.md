# Fase 4D-B-0 - Contrato de baja logica documental

Estado: `BAJA_LOGICA_DOCUMENTAL_4D_B_A_COMPLETADA_QA_DIFERIDA`.

## Objetivo

Definir una fase segura para marcar documentos como `eliminado` de forma logica, sin
borrar archivos fisicos, sin borrar registros, sin borrar relaciones y sin exponer
rutas privadas.

Este contrato no implementa funcionalidad. Cualquier POST real de baja logica requiere
una subfase posterior autorizada.

## Estado previo confirmado

- Fase 4D-A esta validada manualmente como `FASE_4D_A_VALIDADA_MANUALMENTE`.
- `documentos.estado` ya reconoce `activo`, `archivado` y `eliminado`.
- La vista de listado ya permite filtrar `eliminado`.
- La vista de detalle no muestra descarga si el documento no esta `activo`.
- La edicion de metadata bloquea documentos con estado `eliminado`.
- `Documento::actualizarEstado()` actualmente solo permite `activo <-> archivado` y
  bloquea restaurar documentos `eliminado`.
- No existe ruta formal para baja logica documental.
- No existe accion de borrado fisico documental.

## Alcance permitido futuro 4D-B-A

Solo si se autoriza implementacion:

- Agregar accion manual explicita para baja logica.
- Ruta propuesta: `POST /documentos/{id}/eliminar`.
- Metodo propuesto: `DocumentoController::eliminarAction()`.
- Metodo de modelo propuesto: `Documento::marcarEliminado()` o extension controlada de
  `Documento::actualizarEstado()` si mantiene reglas claras.
- Transiciones permitidas:
  - `activo -> eliminado`;
  - `archivado -> eliminado`.
- Auditar `documentos.estado_actualizado` con estado antes/despues y motivo opcional.
- Mostrar boton solo en detalle documental, para documentos `activo` o `archivado`.
- Requerir confirmacion fuerte en UI.
- Mantener metadata visible solo bajo acceso autenticado, hotel actual y modulo valido.

## Fuera de alcance

- Borrado fisico de archivos.
- `DELETE` SQL sobre `documentos`, `documento_entidades` o `logs_auditoria`.
- Restaurar desde `eliminado`.
- Baja masiva.
- Reemplazo de archivo.
- Links publicos o tokens.
- Exponer `storage_path` o `nombre_archivo`.
- Cambiar hash, MIME, tamano, hotel, usuario o relaciones.
- Caja, pagos, abonos, Fase 3D, NP-A, PWA/offline, IndexedDB, service worker y
  `/api/sync`.
- Migraciones destructivas o cambios de esquema.

## Reglas obligatorias si se implementa

- Solo POST; nunca GET destructivo.
- CSRF obligatorio.
- Autenticacion y contexto hotelero obligatorio.
- Busqueda por `id + hotel_id`.
- Rechazar documentos ya `eliminado`.
- Rechazar estados no permitidos.
- No aceptar `estado`, `hotel_id`, `storage_path`, `nombre_archivo` ni `sha256` desde el
  formulario.
- Usar transaccion si se registra auditoria junto con el cambio.
- Mantener relaciones en `documento_entidades`.
- Mantener archivo fisico en `STORAGE_PATH/documentos`.
- La descarga y edicion deben seguir bloqueadas para documentos `eliminado`.

## Riesgos

- Ocultar un documento activo por clic accidental.
- Dar baja logica a un documento de otro hotel si falta `hotel_id`.
- Confundir baja logica con borrado fisico.
- Perder trazabilidad si no se audita.
- Crear una recuperacion desde `eliminado` sin contrato propio.

## Mitigaciones

- Confirmacion fuerte en UI.
- POST + CSRF.
- Validacion central en modelo.
- Filtro `id + hotel_id`.
- Auditoria antes/despues.
- Sin borrado fisico ni `DELETE`.
- Sin restauracion desde `eliminado` en 4D-B.
- QA manual obligatoria antes de cerrar una implementacion futura.

## Definition of Done 4D-B-0

- Contrato documentado.
- Estado previo 4D-A reconocido como validado manualmente.
- Transiciones futuras definidas.
- Riesgos y mitigaciones documentados.
- QA futura documentada.
- Rollback documentado.
- Sin codigo funcional.
- Sin rutas nuevas.
- Sin DB.
- Sin migraciones.
- Sin `/api/sync`.

## Definition of Done futura 4D-B-A

- `php -l` en archivos PHP tocados.
- Health checker valida ruta, metodo, CSRF, modelo central y ausencia de `DELETE`.
- HTTP sin sesion bloquea o redirige.
- SQL read-only confirma que no se borran documentos, relaciones ni archivos.
- Prueba transaccional con rollback si se usa DB local para validar.
- QA manual confirma `activo/archivado -> eliminado`, bloqueo de descarga/edicion y
  auditoria.

## Rollback

4D-B-0 es solo documentacion:

- Revertir el commit documental de contrato.
- No tocar documentos existentes.
- No borrar archivos bajo `src/storage/documentos/`.
- No modificar tablas documentales.

Si se implementa una fase futura y se marca un documento como `eliminado`, el rollback
operativo debe definirse antes de ejecutar la baja. No se debe usar `DELETE` como
rollback.

## Resultado Fase 4D-B-A

Estado tecnico: `BAJA_LOGICA_DOCUMENTAL_4D_B_A_COMPLETADA_QA_DIFERIDA`.

Se implemento baja logica documental controlada con:

- Ruta POST: `/documentos/{id}/eliminar`.
- Controlador: `DocumentoController::eliminarAction()`.
- Modelo central: `Documento::actualizarEstado()` permite `activo -> eliminado` y
  `archivado -> eliminado`.
- Vista: boton `Baja logica` visible solo para documentos `activo` o `archivado`.
- CSRF obligatorio en formulario.
- Auditoria existente: `documentos.estado_actualizado`.
- Sin restauracion desde `eliminado`.
- Sin borrado fisico.
- Sin `DELETE` SQL.
- Sin borrar relaciones en `documento_entidades`.
- Sin Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.

QA manual queda diferida por instruccion del usuario. Debe validarse despues:

- `activo -> eliminado`.
- `archivado -> eliminado`.
- documento `eliminado` no muestra descarga, edicion ni acciones de restauracion;
- archivo fisico y relaciones se conservan;
- auditoria `documentos.estado_actualizado` queda registrada.
