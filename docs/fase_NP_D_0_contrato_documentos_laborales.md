# Fase NP-D-0 - Contrato de documentos laborales

## Estado

`CONTRATO_NP_D_DOCUMENTOS_LABORALES_COMPLETADO`

## Objetivo

Definir la integracion futura entre Personal y el Centro Documental moderno para vincular
documentos a trabajadores, sin crear uploads nuevos, sin exponer archivos privados y sin
usar Caja, pagos, abonos, nomina ni `/api/sync`.

Esta fase solo documenta contrato y diagnostico. No agrega rutas, controladores, modelos,
vistas, migraciones ni datos.

## Diagnostico

- Existe Centro Documental moderno con tablas:
  - `documentos`
  - `documento_entidades`
  - `documento_tipos`
- El modelo `Documento` permite hoy entidades:
  - `proveedor`
  - `compra`
  - `cuenta_por_pagar`
  - `huesped`
  - `reservacion`
- No permite todavia `trabajador`.
- Existe tabla `trabajador_documentos`, actualmente vacia.
- `trabajador_documentos` guarda `ruta_archivo`, por lo que no debe convertirse en flujo
  principal sin una revision de seguridad de storage.

## Fuente de verdad propuesta

Para nuevas fases, la fuente documental moderna debe ser:

- `documentos`
- `documento_entidades`
- `documento_tipos`

La relacion laboral futura debe usar:

- `documento_entidades.entidad_tipo = 'trabajador'`
- `documento_entidades.entidad_id = trabajadores.id`
- `documento_entidades.hotel_id = trabajadores.hotel_id`

La tabla `trabajador_documentos` queda congelada como tabla legacy/aditiva de Personal
base hasta una fase explicita de reconciliacion o retiro.

## Alcance futuro permitido

Subfase futura segura:

- Agregar `trabajador` al catalogo de entidades permitidas del Centro Documental.
- Validar `trabajadores.id` por `hotel_id`.
- Mostrar documentos vinculados en ficha de trabajador usando el partial contextual
  existente.
- Crear enlaces hacia:
  - listado contextual de documentos del trabajador;
  - carga segura existente del Centro Documental, si ya esta autorizada;
  - detalle/descarga segura existente, respetando permisos.

## Fuera de alcance

- Crear una nueva tabla documental.
- Usar `trabajador_documentos` para nuevos uploads.
- Migrar documentos entre tablas.
- Borrar o renombrar `trabajador_documentos`.
- Subir archivos sin las validaciones del Centro Documental moderno.
- Exponer `ruta_archivo` o `storage_path`.
- Crear descargas publicas.
- Tocar Caja, pagos, abonos, nomina, CxP operativa o `/api/sync`.

## Reglas de seguridad

- No aceptar `hotel_id` desde formulario.
- Validar siempre que el trabajador pertenece al hotel actual.
- No mostrar rutas internas de storage.
- Reutilizar guardas y CSRF del Centro Documental.
- No duplicar logica de upload en Personal.
- Cualquier POST futuro debe vivir en el flujo documental ya auditado, no en un endpoint
  laboral nuevo.

## Definition of Done futura

- `Documento::normalizarEntidadTipo()` acepta `trabajador`.
- `Documento::entidadExisteEnHotel()` valida contra `trabajadores`.
- Ficha de trabajador muestra documentos contextuales sin storage interno.
- Rutas documentales existentes funcionan con contexto `trabajador`.
- Health/preflight detectan que documentos laborales usan Centro Documental moderno.
- QA manual confirma que se puede vincular/subir/ver/descargar documento de trabajador
  sin romper permisos ni cache.

## Rollback futuro

- Revertir commit de implementacion.
- No borrar documentos reales.
- Si se crearon vinculos de prueba en `documento_entidades`, documentar IDs y esperar
  fase autorizada de baja logica/reconciliacion.

## Siguiente subfase sugerida

`NP-D-A`: integrar `trabajador` como entidad documental moderna y mostrar documentos
contextuales en ficha de trabajador, reutilizando Centro Documental existente.
