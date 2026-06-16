# Fase 4D-A - Archivado documental controlado

Estado tecnico: `ARCHIVADO_DOCUMENTAL_4D_A_COMPLETADO_QA_MANUAL_PENDIENTE`.

## Objetivo

Permitir archivar y restaurar documentos de forma reversible, sin borrar archivos
fisicos, sin borrar registros y sin exponer rutas internas.

## Implementacion

- Rutas POST agregadas:
  - `POST /documentos/{id}/archivar`;
  - `POST /documentos/{id}/restaurar`.
- Controlador:
  - `DocumentoController::archivarAction()`;
  - `DocumentoController::restaurarAction()`;
  - `DocumentoController::cambiarEstadoAction()` como flujo compartido.
- Modelo:
  - `Documento::actualizarEstado()`.
- Vista:
  - `src/app/views/documentos/ver.php` muestra accion `Archivar` solo si el documento
    esta `activo`.
  - Muestra accion `Restaurar` solo si el documento esta `archivado`.
- Health checker:
  - valida rutas, CSRF, acciones del controlador, modelo centralizado, auditoria y
    ausencia de `DELETE FROM documentos`.

## Reglas aplicadas

- Solo transiciones `activo -> archivado` y `archivado -> activo`.
- No se permite restaurar desde `eliminado`.
- No se implementa baja logica hacia `eliminado`.
- No se borra archivo fisico.
- No se borra fila en `documentos`.
- No se borra relacion en `documento_entidades`.
- Todo cambio usa POST + CSRF.
- Todo cambio busca por `id + hotel_id`.
- La vista no acepta `hotel_id`, `estado`, `storage_path` ni `nombre_archivo` desde el
  formulario.
- Auditoria: `documentos.estado_actualizado` con estado antes/despues seguro.
- La descarga sigue disponible solo para documentos `activo`.
- No se toca Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.

## Verificacion automatica ejecutada

```bash
docker exec medisoft_hoteles_app php -l /var/www/html/app/models/Documento.php
docker exec medisoft_hoteles_app php -l /var/www/html/app/controllers/DocumentoController.php
docker exec medisoft_hoteles_app php -l /var/www/html/app/views/documentos/ver.php
docker exec medisoft_hoteles_app php -l /var/www/html/tools/saas/health_check_fase_1a.php
docker exec medisoft_hoteles_app php /var/www/html/tools/saas/health_check_fase_1a.php
docker exec medisoft_hoteles_app php /var/www/html/tools/saas/preflight_compras_minimas.php
docker exec medisoft_hoteles_app php /var/www/html/tools/saas/preflight_recepcion_compras.php
curl.exe -i -s -o NUL -w "STATUS=%{http_code} REDIRECT=%{redirect_url}\n" -X POST http://localhost:8080/documentos/1/archivar
git diff --check
```

Resultado:

- `php -l`: sin errores en los cuatro PHP tocados.
- `health_check_fase_1a.php`: `PASS_WITH_WARNINGS_ALLOWED`, sin `ERROR`.
- Preflights de compras/recepcion: `PASS_WITH_WARNINGS_ALLOWED`, sin `ERROR`.
- HTTP sin sesion en `POST /documentos/1/archivar`: `303` a `/login`.
- SQL read-only final: `documentos=5`, `documentos_archivados=0`,
  `documento_entidades=3`, `documentos.estado_actualizado=0`, `movimientos_caja=1403`
  y `cuentas_por_pagar_movimientos=0`.
- Prueba transaccional con rollback: documento `#1` cambio `activo -> archivado` dentro
  de transaccion, auditoria delta `1`, rollback exitoso, estado final `activo` y
  auditoria delta final `0`.
- `git diff --check`: sin errores de whitespace; solo warnings CRLF normales de Windows.

## QA manual requerida

1. Entrar a `/documentos/{id}` con un documento en estado `activo`.
2. Confirmar que aparece `Archivar` y no aparece `Restaurar`.
3. Archivar el documento.
4. Resultado esperado: vuelve al detalle, el estado cambia a `archivado`, desaparece
   `Descargar`, aparece `Restaurar`.
5. Restaurar el documento.
6. Resultado esperado: vuelve al detalle, el estado cambia a `activo`, vuelve a aparecer
   `Descargar`.
7. Revisar que no se borro el archivo ni la relacion con entidades.
8. Revisar que `logs_auditoria` tenga `documentos.estado_actualizado`.
9. Confirmar que no hay Caja, pagos, abonos ni cambios en `/api/sync`.

## Rollback

- Revertir el commit `feat(phase-4d): add controlled document archival`.
- DB: si se hicieron pruebas manuales de archivado/restauracion, revertir el estado del
  documento con la accion inversa desde la UI, no con `DELETE`.
- No borrar archivos fisicos bajo `src/storage/documentos/`.
- No borrar filas en `documentos`, `documento_entidades` ni `logs_auditoria`.
