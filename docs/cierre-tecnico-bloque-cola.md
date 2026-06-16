# Cierre tecnico del bloque autorizado

## Estado

Estado objetivo: `CIERRE_TECNICO_COMPLETADO_QA_MANUAL_PENDIENTE`.

Este cierre fue seguido por QA manual reportada como realizada por el usuario y por commit separado del ajuste visual de reservaciones.

## Fases cerradas

| Fase | Alcance | Commit | Estado |
| --- | --- | --- | --- |
| 2X | Detalle read-only de compra recibida | `d1f1431` | Cerrada |
| 2Y | Reporte read-only de compras recibidas | `32abb7b` | Cerrada |
| 2Z | Endurecimiento de recepcion de compras | `052fd7a` | Cerrada |
| 3A | Ficha read-only de proveedor | `3d8f997` | Cerrada |
| 3B draft | Borrador tecnico CxP base | `673f47f` | Cerrada como draft |
| 3B aplicada | CxP base read-only | `1fa1653` | Cerrada |
| Post 3B | Auditoria/cierre documental | `2535dd1`, `ca2bda4`, `8a49995` | Cerrada |
| Reservaciones visual | Ajuste modal check-in tardio | `dc3c150` | Cerrada como cambio separado |

## Confirmaciones tecnicas

- Rutas de compras/proveedores/CxP revisadas.
- Controladores y modelos revisados para guards y `hotel_id`.
- Vistas revisadas para estados vacios y ausencia de acciones financieras no autorizadas.
- Sidebar revisado: Proveedores, Compras y CxP bajo gate visual/funcional de Inventario.
- CxP conserva solo rutas GET.
- CxP no tiene POST operativo.
- CxP no expone botones de pago.
- CxP no toca Caja.
- Compras no genera CxP automaticamente.
- Recepcion de compras mantiene proteccion contra doble recepcion.
- Inventario moderno usa `inventario_productos` y `movimientos_inventario`.
- Tablas legacy quedan congeladas y documentadas.
- `/api/sync` sigue fuera de alcance y bloqueado.

## Verificaciones automaticas registradas

- `php -l` sobre archivos PHP del bloque: sin errores.
- `src/tools/saas/health_check_fase_1a.php`: PASS con warnings permitidos.
- `src/tools/saas/preflight_compras_minimas.php`: PASS con warnings permitidos.
- `src/tools/saas/preflight_recepcion_compras.php`: PASS con warnings permitidos.
- SQL read-only:
  - CxP registrada en `migrations`;
  - `cuentas_por_pagar` vacia;
  - `cuentas_por_pagar_movimientos` vacia;
  - sin movimientos de Caja vinculados a CxP;
  - sin detalles recibidos sin movimiento de inventario.
- HTTP sin sesion:
  - `/compras/reportes/recibidas` redirige a login;
  - `/proveedores/1` redirige a login;
  - `/cuentas-por-pagar` redirige a login.

## Warnings conocidos

- El contenedor `app` no monta `docs/technical` ni `migrations/` completos, por eso algunos checkers emiten warnings de visibilidad documental.
- Existe migracion historica fuera de `migrations/`: `database/migrations/2026_05_21_create_operaciones_sync.sql`.
- Maximiliano tiene excepciones de modulos respecto a su plan: `configuracion`, `usuarios`.
- Tablas legacy/duplicadas siguen presentes y no deben borrarse:
  - `productos` vs `inventario_productos`;
  - `inventario_movimientos` vs `movimientos_inventario`;
  - `inventario_habitacion_config` vs `inventario_config_habitacion`;
  - `push_subscriptions` vs `pwa_push_subscriptions`;
  - `huespedes_vehiculos` vs `huesped_vehiculos`.
- `src/app/views/reservaciones/ver.php` fue resuelto en commit separado `dc3c150`.

## QA manual

- QA manual del bloque 2X-3B fue reportada como realizada por el usuario.
- QA de Fase 3C vive en `docs/qa-pendiente-cola.md`.

## Estado Git esperado

- HEAD funcional/documental del bloque anterior antes de 3C: `dc3c150`.
- Pendientes no relacionados al iniciar 3C-0: ninguno.

## Cierre tecnico Fase 3C

Estado final: `FASE_3C_VALIDADA_MANUALMENTE`.

La Fase 3C queda cerrada tecnicamente como generacion manual controlada de CxP desde compras recibidas, sin pagos, sin abonos, sin Caja, sin movimientos de Caja y sin cambios en `/api/sync`.

### Fases cerradas 3C

| Fase | Alcance | Commit | Estado |
| --- | --- | --- | --- |
| 3C-0 | Contrato y diagnostico | `9897465` | Cerrada |
| Reanclaje 3C | Estado real corregido | `41e6c18` | Cerrada |
| 3C-A | Simulador read-only de CxP generable | `c0ff5a1` | Cerrada y validada manualmente |
| 3C-B | Generacion manual sin Caja | `c5e9de8` | Cerrada y validada manualmente |
| QA 3C-A/B | Validacion manual documentada | `b9ca075` | Cerrada |
| 3C-C | Validaciones, health y preflights | `e91cab5` | Cerrada tecnicamente |
| Revision tecnica 3C | Estabilizacion post-3C-C | `59feafc` | Cerrada |
| Auditoria seguridad 3C | Auditoria post-3C-C | `c14d14d` | Cerrada |
| QA manual final 3C | Validacion final del usuario | reporte manual | Completada |

### Confirmaciones 3C

- 3C-0 contrato y diagnostico documentado.
- 3C-A implementado como GET read-only, ruteado, protegido, navegable y validado manualmente.
- 3C-B implementado como POST manual con CSRF, transaccion, validaciones centrales, auditoria y validacion manual.
- 3C-C implementado como health/preflights read-only de consistencia CxP.
- Revision tecnica 3C completada sin hallazgos bloqueantes.
- Auditoria seguridad 3C completada sin hallazgos bloqueantes.
- Rollback por subfase documentado.
- Fuentes de verdad actualizadas.
- QA critica, funcional, visual y regresion actualizada.
- Warnings conocidos listados.
- Commits registrados.
- Sin pagos.
- Sin abonos.
- Sin Caja.
- Sin movimientos de Caja.
- Sin cambios en `/api/sync`.
- Sin Fase 3D.
- QA manual final reportada como OK por el usuario.

### Warnings y pendientes separados

- La QA manual final del bloque 3C fue reportada como OK por el usuario. No autoriza pagos, abonos, Caja ni Fase 3D.
- Cambios no relacionados ya separados en commit `e52766e`:
  - `src/app/controllers/DashboardController.php`;
  - `src/app/controllers/HabitacionController.php`;
  - `src/app/controllers/NotificacionController.php`;
  - `src/app/views/layout/sidebar.php`;
  - `src/app/views/notificaciones/index.php`.
- Cambios no relacionados pendientes en Git al cierre:
  - `src/app/services/PwaPushService.php`;
  - `src/public_html/service-worker.js`.
- `src/public_html/service-worker.js` esta en zona protegida por `AGENTS.md`; requiere triage separado antes de cualquier commit o cambio adicional.

### Estado Git al cierre 3C

- El commit de cierre 3C debe incluir solo documentacion del bloque 3C.
- No incluir cambios PWA/no relacionados en el commit de cierre.
- No hacer push.

## Cierre tecnico Fase 4A

Estado final: `CIERRE_TECNICO_4A_COMPLETADO`.

La Fase 4A queda cerrada tecnicamente como Centro Documental Base: contrato,
migracion base, consultas read-only y carga segura hacia storage privado. No habilita
descarga, edicion, borrado, pagos, abonos, Caja, Fase 3D ni `/api/sync`.

### Fases cerradas 4A

| Fase | Alcance | Commit | Estado |
| --- | --- | --- | --- |
| 4A-0 | Contrato y diagnostico | `af126f7` | Cerrada |
| 4A-A | Migracion base documental | `6eae89b` | Cerrada |
| 4A-B | Capa read-only documental | `dc32c3a` | Cerrada |
| 4A-C | Upload seguro documental | `4668571` | Cerrada |
| Hotfix 4A-C | Tipos documentales y carga general | `a5b15f1` | Cerrada y probada manualmente |
| Revision tecnica 4A | Guard contextual por entidad/hotel | `c435ddb` | Cerrada |
| Auditoria seguridad 4A | Auditoria post-QA | `d34886f` | Cerrada |

### Confirmaciones 4A

- Tablas base creadas: `documento_tipos`, `documentos`, `documento_entidades`.
- Tipos documentales globales disponibles: Contrato, Comprobante, Identificacion,
  Factura, Evidencia y Otro.
- Rutas activas: `GET /documentos`, `GET /documentos/subir`,
  `POST /documentos/subir`, `GET /documentos/entidad/{tipo}/{id}` y
  `GET /documentos/{id}`.
- Upload con `multipart/form-data`, CSRF, validacion MIME/extension/tamano y storage
  privado bajo `STORAGE_PATH/documentos`.
- Ruta contextual valida entidad existente del hotel actual antes de mostrar listado o
  enlace de carga.
- Vistas no muestran `storage_path` ni `nombre_archivo`.
- Sin rutas documentales de descarga, edicion ni borrado.
- Sin Caja, pagos, abonos, Fase 3D ni cambios en `/api/sync`.
- QA manual post-hotfix reportada como funcional por el usuario.

### Verificaciones 4A registradas

- `php -l` en `DocumentoController.php`: sin errores.
- `health_check_fase_1a.php`: PASS con warnings permitidos.
- `preflight_compras_minimas.php`: PASS con warnings permitidos.
- `preflight_recepcion_compras.php`: PASS con warnings permitidos.
- HTTP sin sesion en `GET /documentos/subir` y `POST /documentos/subir`: redirige a
  login.
- HTTP autenticado: `/documentos` y `/documentos/entidad/proveedor/8` responden `200`;
  entidad invalida redirige `303` a `/documentos`.
- SQL read-only de cierre: `documento_tipos=6`, `documentos=3`,
  `documento_entidades=1`, `cuentas_por_pagar_movimientos=0`, `movimientos_caja=1403`,
  `logs_auditoria=29`.

### Warnings y pendientes 4A

- `documentos.id=1`, `documentos.id=2` y `documentos.id=3` existen por pruebas locales
  y/o QA manual; no borrar ni reconciliar sin autorizacion explicita.
- `src/storage/documentos/` es runtime privado e ignorado por Git.
- La descarga segura aun no existe; debe abrirse como fase nueva con contrato, guardias,
  `realpath`, headers privados y pruebas manuales.
- No hacer push.
