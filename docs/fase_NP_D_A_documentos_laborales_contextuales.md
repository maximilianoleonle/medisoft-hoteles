# Fase NP-D-A - Documentos laborales contextuales

## Estado

`DOCUMENTOS_LABORALES_NP_D_A_COMPLETADOS_QA_DIFERIDA`

## Objetivo

Integrar trabajadores al Centro Documental moderno para vincular documentos laborales
desde la ficha de trabajador, sin crear un nuevo sistema de archivos y sin usar
`trabajador_documentos` como flujo operativo nuevo.

## Cambios tecnicos

- `Documento` acepta `trabajador` como entidad documental permitida.
- `Documento::entidadExisteEnHotel()` valida `trabajador` contra `trabajadores` y
  `hotel_id`.
- `DocumentoController` etiqueta la entidad como `Trabajador`.
- La ficha de trabajador carga documentos con:
  - `Documento::documentosPorEntidad($hotelId, 'trabajador', $id, 10)`
- La ficha de trabajador renderiza el partial existente:
  - `View::partial('documentos_entidad', ...)`

## Fuente de verdad

Fuente moderna:

- `documentos`
- `documento_entidades`
- `documento_tipos`

Relacion laboral:

- `documento_entidades.entidad_tipo = 'trabajador'`
- `documento_entidades.entidad_id = trabajadores.id`
- `documento_entidades.hotel_id = trabajadores.hotel_id`

La tabla `trabajador_documentos` queda congelada como tabla legacy/aditiva.

## Seguridad

- No se crean rutas nuevas.
- No se duplican uploads en Personal.
- No se expone `storage_path`, `nombre_archivo` ni `ruta_archivo` en la ficha.
- No se escribe en `trabajador_documentos`.
- No se toca Caja, pagos, abonos, nomina ni `/api/sync`.
- Los enlaces de vincular/ver/descargar usan el Centro Documental ya auditado.

## QA manual diferida

Pendiente probar con trabajador activo:

- Abrir `/trabajadores/{id}`.
- Confirmar seccion "Documentos vinculados".
- Confirmar boton "Vincular documento".
- Subir/vincular documento usando el flujo documental existente.
- Confirmar que aparece en ficha de trabajador.
- Confirmar que descarga respeta permisos y no expone rutas internas.

## Rollback

- Revertir el commit `feat(phase-np): link workers to document center`.
- No borrar documentos ni vinculos reales creados en QA manual.
- Si se crean vinculos de prueba, documentar IDs y esperar una fase autorizada de baja
  logica/reconciliacion documental.

## Siguiente paso recomendado

Revision tecnica y cierre del bloque NP-D-A. No avanzar a documentos legacy,
trabajador_documentos, nomina ni Caja.
