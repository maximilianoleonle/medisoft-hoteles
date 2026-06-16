# Fase 4C-A - Documentos por entidad read-only

Estado tecnico: `DOCUMENTOS_ENTIDAD_READ_ONLY_4C_COMPLETADO`.

## Objetivo

Mostrar documentos vinculados dentro de fichas operativas sin crear nuevas escrituras ni
exponer rutas internas de storage.

Entidades incluidas:

- proveedor;
- compra;
- cuenta por pagar;
- huesped;
- reservacion.

## Implementacion

- Se reutiliza `Documento::documentosPorEntidad($hotelId, $entidadTipo, $entidadId)`.
- Se crea el partial `src/app/views/partials/documentos_entidad.php`.
- Las fichas autorizadas incluyen el partial en modo read-only.
- Los controladores pasan solo metadata documental segura a la vista.
- Los enlaces disponibles son GET a detalle documental y descarga autenticada ya existente.

## Seguridad

- Todo listado se filtra por `hotel_id`.
- No se muestra `storage_path`.
- No se muestra `nombre_archivo`.
- No se agregan formularios, POST, upload, edicion, borrado ni reemplazo de archivo.
- No se tocan Caja, pagos, abonos, Fase 3D, NP-A, PWA/offline ni `/api/sync`.

## Definition of Done

- Las cinco fichas muestran estado vacio claro si no hay documentos.
- Las cinco fichas muestran documentos vinculados si existen relaciones en
  `documento_entidades`.
- Los enlaces de documentos usan rutas documentales existentes y protegidas.
- `php -l` pasa en archivos PHP tocados.
- Health checker detecta partial, vistas y consultas por entidad.
- SQL de verificacion confirma que no se insertaron documentos ni relaciones.

## Rollback

- Revertir el commit `feat(phase-4c): add read-only entity document sections`.
- DB: no aplica; esta subfase no crea ni modifica datos.
- Archivos subidos y metadata documental previa permanecen intactos.
