# Fase 4C-0 - Contrato y diagnostico de documentos por entidad

Estado: `CONTRATO_4C_DOCUMENTOS_ENTIDAD_COMPLETADO`.

Actualizacion 4C-A: se implemento la primera integracion read-only con un alcance mas
conservador que el previsto inicialmente: las fichas muestran metadata segura y enlaces
GET a detalle/descarga autenticada, pero no agregan links de carga contextual ni de
edicion de metadata desde la ficha.

## Objetivo

Definir la integracion contextual del Centro Documental en fichas operativas ya
existentes, sin crear nuevas escrituras en esta subfase.

La meta de Fase 4C es que proveedor, compra, cuenta por pagar, huesped y
reservacion puedan mostrar documentos relacionados de forma clara y segura, usando
la infraestructura ya cerrada en 4A/4B:

- `documentos`;
- `documento_entidades`;
- `documento_tipos`;
- storage privado `STORAGE_PATH/documentos`;
- descarga autenticada `GET /documentos/{id}/descargar`;
- metadata editable controlada `GET /documentos/{id}/editar` y
  `POST /documentos/{id}/actualizar`.

## Estado previo confirmado

- Fase 4A: Centro Documental Base cerrado tecnicamente.
- Fase 4B: descarga segura, auditoria de descargas y edicion de metadata cerradas.
- Estado formal previo: `CIERRE_TECNICO_4B_COMPLETADO`.
- Arbol Git limpio al iniciar.
- No hay borrado documental.
- No hay reemplazo de archivo.
- No hay links publicos.
- No hay Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.

## Diagnostico actual

Infraestructura disponible:

- `Documento::documentosPorEntidad($hotelId, $entidadTipo, $entidadId, $limite)`.
- `Documento::entidadesPermitidas()` permite `proveedor`, `compra`,
  `cuenta_por_pagar`, `huesped` y `reservacion`.
- `DocumentoController::entidadAction()` muestra una vista contextual con
  validacion previa de existencia y pertenencia al hotel.
- Ruta existente: `GET /documentos/entidad/{tipo}/{id}`.
- La carga documental ya acepta entidad opcional mediante contexto validado.
- Las vistas `documentos/index.php` y `documentos/ver.php` no exponen
  `storage_path` ni `nombre_archivo`.

Fichas candidatas:

- `src/app/views/proveedores/ver.php`.
- `src/app/views/compras/ver.php`.
- `src/app/views/cuentas_por_pagar/ver.php`.
- `src/app/views/huespedes/ver.php`.
- `src/app/views/reservaciones/ver.php`.

Controladores candidatos:

- `ProveedorController::verAction()`.
- `CompraController::verAction()`.
- `CuentaPorPagarController::verAction()`.
- `HuespedController::verAction()`.
- `ReservacionController` en su detalle vigente.

## Semaforo de riesgo

Riesgo: naranja bajo.

Motivo:

- Integra documentos dentro de fichas operativas sensibles.
- Debe respetar `hotel_id` en cada entidad.
- Puede introducir enlaces hacia documentos si no se valida correctamente.
- No requiere tablas nuevas ni escrituras nuevas si se implementa primero como
  seccion read-only con links a rutas ya protegidas.

Mitigaciones:

- No duplicar logica de permisos en vistas.
- Resolver documentos desde modelo/servicio central usando `hotel_id`.
- Mostrar solo metadata segura.
- Usar links a rutas ya protegidas: `/documentos/{id}`,
  `/documentos/{id}` y `/documentos/{id}/descargar`.
- No mostrar `storage_path`, `nombre_archivo`, rutas absolutas ni URLs privadas.
- No agregar borrado, reemplazo, links publicos, pagos, Caja ni `/api/sync`.

## Alcance permitido para Fase 4C

4C-A read-only contextual:

- Mostrar seccion documental en fichas de:
  - proveedor;
  - compra;
  - cuenta por pagar;
  - huesped;
  - reservacion.
- Mostrar estado vacio claro si no hay documentos.
- Mostrar metadata segura:
  - titulo;
  - nombre original;
  - tipo;
  - MIME;
  - tamano;
  - estado;
  - fecha de carga.
- Link a detalle documental.
- Link a descarga autenticada ya existente.
- Link a formulario de metadata ya existente si el documento esta activo queda diferido.
- Link a carga contextual ya existente si la entidad pertenece al hotel actual queda diferido.

4C-B integracion de carga contextual:

- Usar `GET /documentos/subir?entidad_tipo=...&entidad_id=...`.
- Mantener `POST /documentos/subir` existente con CSRF.
- No crear nuevo POST por entidad.
- Validar que cada entidad exista y pertenezca al hotel antes de ofrecer el link.

4C-C validaciones/checkers:

- Health debe detectar rutas/fichas con seccion documental cuando se implementen.
- SQL read-only debe confirmar relaciones cross-hotel en cero.
- Checkers deben confirmar que no hay links publicos ni exposicion de
  `storage_path`.

## Fuera de alcance

- Borrado fisico.
- Baja logica o archivado.
- Reemplazo de archivo.
- Versionado de documentos.
- Links publicos o tokens.
- Firma digital.
- OCR.
- Generacion automatica de documentos desde compras o CxP.
- Pagos, abonos, Caja, Fase 3D, NP-A.
- Cambios en `/api/sync`, PWA/offline, IndexedDB o service worker.
- Migraciones nuevas, salvo que una fase futura demuestre que hacen falta.

## Reglas por entidad

Proveedor:

- Entidad tipo: `proveedor`.
- Validar por `proveedores.id + proveedores.hotel_id`.
- Uso esperado: contratos, constancias, facturas, evidencia administrativa.

Compra:

- Entidad tipo: `compra`.
- Validar por `compras.id + compras.hotel_id`.
- Uso esperado: factura, remision, comprobante de recepcion.
- No debe crear CxP ni pagos automaticamente.

Cuenta por pagar:

- Entidad tipo: `cuenta_por_pagar`.
- Validar por `cuentas_por_pagar.id + cuentas_por_pagar.hotel_id`.
- Uso esperado: soporte documental del compromiso.
- No debe agregar pagos, abonos ni movimientos en Caja.

Huesped:

- Entidad tipo: `huesped`.
- Validar por `huespedes.id + hotel_id` si la tabla lo permite en el entorno
  actual.
- Uso esperado: identificacion, autorizaciones o evidencia.
- Revisar cuidadosamente datos personales antes de exponer enlaces en futuras fases.

Reservacion:

- Entidad tipo: `reservacion`.
- Validar por `reservaciones.id + reservaciones.hotel_id`.
- Uso esperado: comprobantes, autorizaciones, evidencia de estancia.
- No tocar logica profunda de check-in/check-out, caja, cortes ni pagos.

## Definition of Done 4C-0

- Contrato documentado.
- Diagnostico de rutas/modelos/vistas existente registrado.
- Riesgos y restricciones documentados.
- Subfases propuestas.
- QA futura documentada.
- Rollback documentado.
- Fuentes de verdad actualizadas.
- Sin cambios de codigo.
- Sin DB.
- Sin migraciones.
- Sin `/api/sync`.

## Definition of Done futura 4C-A

- Fichas muestran seccion documental por entidad.
- Seccion aparece solo si el usuario esta autenticado y en contexto hotelero.
- Documentos se consultan con `hotel_id`.
- Estado vacio claro.
- Links usan rutas existentes y protegidas.
- No se exponen rutas internas.
- `php -l` en archivos tocados.
- Health/preflight actualizado si corresponde.
- HTTP sin sesion bloquea o redirige.
- SQL read-only confirma ausencia de relaciones cross-hotel.
- QA manual por entidad.

## Comandos de verificacion esperados en 4C-A

```bash
docker exec medisoft_hoteles_app php -l /var/www/html/app/controllers/DocumentoController.php
docker exec medisoft_hoteles_app php /var/www/html/tools/saas/health_check_fase_1a.php
docker exec medisoft_hoteles_app php /var/www/html/tools/saas/preflight_compras_minimas.php
docker exec medisoft_hoteles_app php /var/www/html/tools/saas/preflight_recepcion_compras.php
git diff --check
```

SQL read-only sugerido:

```sql
SELECT entidad_tipo, COUNT(*) total
FROM documento_entidades
GROUP BY entidad_tipo;

SELECT COUNT(*) AS relaciones_cross_hotel
FROM documento_entidades de
JOIN documentos d ON d.id = de.documento_id
WHERE d.hotel_id <> de.hotel_id;
```

## Rollback

4C-0 es documentacion:

- revertir el commit `docs(phase-4c): define contextual document attachments contract`;
- no tocar documentos existentes;
- no borrar archivos en `src/storage/documentos`;
- no modificar tablas documentales.

Fases futuras 4C-A/4C-B deben ser reversibles por commit y no deben requerir borrar
documentos ni relaciones ya existentes.

## Siguiente subfase recomendada

`COLA_4C_A_DOCUMENTOS_POR_ENTIDAD_READ_ONLY`

Objetivo futuro: agregar secciones documentales read-only en fichas de proveedor,
compra, CxP, huesped y reservacion usando la ruta y modelo existentes, sin nuevas
escrituras y sin tocar Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.
