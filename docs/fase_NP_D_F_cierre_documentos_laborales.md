# Fase NP-D-F - Cierre tecnico de documentos laborales

## Estado

`BLOQUE_NP_D_DOCUMENTOS_LABORALES_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- NP-D-0 contrato de documentos laborales.
- NP-D-A integracion de `trabajador` al Centro Documental moderno.
- Health checker actualizado para validar entidad `trabajador`.
- Documentacion de QA, rollback, fuentes de verdad, decisiones y auditoria actualizada.

## Validaciones tecnicas

- `Documento::normalizarEntidadTipo()` permite `trabajador`.
- `Documento::entidadExisteEnHotel()` valida `trabajador` contra `trabajadores` y
  `hotel_id`.
- `TrabajadorController` consulta `Documento::documentosPorEntidad($hotelId,
  'trabajador', $id)`.
- La ficha de trabajador renderiza `View::partial('documentos_entidad')`.
- El contador de documentos laborales usa fuente moderna cuando existe.
- Health checker pasa sin errores.

## Seguridad

- No se crean rutas nuevas.
- No se crea upload propio de Personal.
- No se expone `storage_path`, `nombre_archivo` ni `ruta_archivo` en ficha de trabajador.
- No se escribe en `trabajador_documentos`.
- No se toca Caja, pagos, abonos, nomina ni `/api/sync`.

## Warnings conocidos

- `health_check_fase_1a.php` mantiene warnings historicos por docs/migrations no visibles
  desde el contenedor y tablas legacy congeladas.
- No se ejecuto QA manual de upload/vinculacion porque el usuario pidio diferir QA manual.

## QA manual diferida

- Probar con trabajador activo.
- Vincular documento desde ficha de trabajador.
- Confirmar que aparece en documentos vinculados.
- Confirmar descarga segura y ausencia de rutas internas.
- Confirmar que `trabajador_documentos` sigue en 0 si no se autoriza reconciliacion.

## Siguiente paso recomendado

No abrir `trabajador_documentos` legacy. Si se continua con Personal/Tareas, abrir contrato
nuevo para la siguiente necesidad concreta, manteniendo Caja y nomina fuera salvo
autorizacion explicita.
