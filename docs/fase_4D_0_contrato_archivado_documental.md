# Fase 4D-0 - Contrato y diagnostico de archivado documental

Estado: `CONTRATO_4D_ARCHIVADO_DOCUMENTAL_COMPLETADO`.

## Objetivo

Definir una fase segura para archivar, restaurar o dar baja logica a documentos del
Centro Documental, sin borrar archivos fisicos y sin exponer rutas privadas.

Esta subfase es solo documental. No implementa rutas nuevas, no agrega POST, no ejecuta
migraciones y no modifica datos.

## Estado previo confirmado

- Fase 4A Centro Documental Base: cerrada.
- Fase 4B descarga segura, auditoria de descargas y metadata: cerrada.
- Fase 4C documentos por entidad: cerrada y validada manualmente.
- Estado formal previo: `CIERRE_TECNICO_4C_COMPLETADO_QA_MANUAL_VALIDADA`.
- Arbol Git limpio al iniciar.
- No hay pagos, abonos, Caja, Fase 3D, NP-A ni cambios en `/api/sync`.

## Diagnostico actual

Infraestructura disponible:

- La tabla `documentos` ya contiene `estado ENUM('activo','archivado','eliminado')`.
- El modelo `Documento` ya filtra por `estado` en listados y resumen.
- La vista `documentos/index.php` permite filtrar `activo`, `archivado`,
  `eliminado` y `todos`.
- `DocumentoController` usa `requireAuth()`, contexto hotelero y guard de modulo.
- La edicion de metadata bloquea documentos en estado `eliminado`.
- La descarga segura se resuelve por `id + hotel_id` y storage privado.
- La auditoria existente usa `AuditService::record()` para cargas, descargas y metadata.

Brechas detectadas:

- No existe accion formal para archivar o restaurar documentos.
- No existe accion formal para marcar un documento como `eliminado`.
- No existe auditoria especifica para cambio de estado documental.
- No existe confirmacion visual dedicada para acciones destructivas o sensibles.
- No existe borrado fisico autorizado, y debe seguir fuera de alcance.

## Semaforo de riesgo

Riesgo: naranja.

Motivo:

- Cambiar estado de documentos afecta visibilidad y descargas.
- Un error de `hotel_id` podria archivar documentos de otro hotel.
- Una accion sin CSRF podria permitir cambios no autorizados.
- Confundir baja logica con borrado fisico podria provocar perdida irreversible.

Mitigaciones obligatorias para implementacion futura:

- Toda accion de cambio de estado debe ser `POST`.
- Toda accion `POST` debe validar CSRF.
- Todo documento debe buscarse por `id + hotel_id`.
- No aceptar `hotel_id`, `storage_path`, `nombre_archivo` ni `estado` libre desde el
  formulario.
- Validar transiciones permitidas de estado en modelo/servicio central.
- Auditar antes/despues con accion explicita.
- No borrar archivos fisicos bajo `src/storage/documentos/`.
- No borrar registros en `documentos` ni `documento_entidades`.

## Alcance permitido futuro 4D-A

Archivado/restauracion controlada:

- `activo -> archivado`.
- `archivado -> activo`.
- Mensajes claros de exito/error.
- Boton visible solo en detalle documental y solo para estados permitidos.
- POST con CSRF.
- Modelo/servicio central para validar transicion.
- Auditoria `documentos.estado_actualizado`.
- Sin cambios de archivo fisico, storage, hash, MIME, tamano, hotel ni vinculos.

## Alcance permitido futuro 4D-B

Baja logica controlada:

- `activo -> eliminado`.
- `archivado -> eliminado`.
- Bloquear edicion y descarga de documentos `eliminado`.
- Mantener metadata visible solo si el usuario tiene acceso al hotel y al modulo.
- Requerir confirmacion clara en UI.
- Auditar `documentos.estado_actualizado` con motivo opcional.

## Fuera de alcance

- Borrado fisico de archivos.
- `DELETE` SQL sobre `documentos` o `documento_entidades`.
- Reemplazo de archivo.
- Versionado documental.
- Links publicos o tokens.
- Permisos profundos nuevos.
- Cambios en Caja, pagos, abonos, Fase 3D o NP-A.
- Cambios en `/api/sync`, PWA/offline, IndexedDB o service worker.
- Migraciones destructivas o alteraciones de esquema.

## Reglas de transicion

Transiciones iniciales propuestas:

| Desde | Hacia | Permitida | Motivo |
| --- | --- | --- | --- |
| `activo` | `archivado` | Si | Ocultar de uso operativo sin perder historial |
| `archivado` | `activo` | Si | Restauracion reversible |
| `activo` | `eliminado` | Si, con confirmacion | Baja logica sin borrar archivo |
| `archivado` | `eliminado` | Si, con confirmacion | Baja logica desde archivo historico |
| `eliminado` | `activo` | No en 4D-A/B | Requiere contrato de recuperacion separado |
| `eliminado` | `archivado` | No en 4D-A/B | Requiere contrato de recuperacion separado |

## Definition of Done 4D-0

- Contrato documentado.
- Diagnostico de modelo, controlador y vistas registrado.
- Riesgos y mitigaciones documentados.
- Transiciones permitidas propuestas.
- QA futura documentada.
- Rollback documentado.
- Fuentes de verdad actualizadas.
- Sin codigo funcional.
- Sin DB.
- Sin migraciones.
- Sin `/api/sync`.

## Definition of Done futura 4D-A/B

- Acciones POST con CSRF.
- Busqueda por `id + hotel_id`.
- Validacion centralizada de transiciones.
- Auditoria con antes/despues.
- UI clara y sin formularios anidados.
- `php -l` en archivos tocados.
- Health checker valida rutas/metodos y ausencia de borrado fisico.
- HTTP sin sesion bloquea o redirige.
- SQL read-only confirma que no hubo DELETE ni perdida de relaciones.
- QA manual valida archivar/restaurar/baja logica segun subfase autorizada.

## Rollback

4D-0 es documentacion:

- revertir el commit `docs(phase-4d): define document archival contract`;
- no tocar documentos existentes;
- no borrar archivos en `src/storage/documentos`;
- no modificar tablas documentales.

Fases futuras 4D-A/4D-B deben ser reversibles por commit y no deben requerir borrar
documentos, relaciones ni archivos fisicos.

## Siguiente subfase recomendada

`COLA_4D_A_ARCHIVADO_DOCUMENTAL_CONTROLADO`

Objetivo: implementar solo archivado/restauracion reversible (`activo <-> archivado`)
con POST + CSRF, auditoria, filtro `hotel_id`, sin baja logica `eliminado`, sin borrado
fisico, sin Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.

## Resultado Fase 4D-A

Estado tecnico: `CIERRE_TECNICO_4D_A_COMPLETADO_QA_MANUAL_PENDIENTE`.

Se implemento archivado/restauracion reversible con:

- POST `/documentos/{id}/archivar`;
- POST `/documentos/{id}/restaurar`;
- CSRF obligatorio;
- validacion central `Documento::actualizarEstado()`;
- busqueda por `id + hotel_id`;
- auditoria `documentos.estado_actualizado`;
- botones visibles solo en detalle documental y segun estado.

No se implemento baja logica `eliminado`, borrado fisico, `DELETE` SQL, reemplazo de
archivo, links publicos, Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.

Documento de implementacion:

- `docs/fase_4D_A_archivado_documental_controlado.md`.
- Revision tecnica/auditoria de cierre: sin hallazgos bloqueantes; QA manual de
  navegador sigue pendiente.
