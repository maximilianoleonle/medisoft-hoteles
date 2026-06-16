# Auditoria de seguridad - cola autonoma

## Alcance

Auditoria post-commit del bloque autorizado hasta Fase 3C:

- Fase 2X: detalle read-only de compra recibida.
- Fase 2Y: reportes read-only de compras recibidas.
- Fase 2Z: endurecimiento de recepcion minima.
- Fase 3A: ficha read-only de proveedor con historial.
- Fase 3B: cuentas por pagar base read-only.
- Fase 3C-A: simulador read-only de CxP generable desde compras recibidas.
- Fase 3C-B: generacion manual controlada de CxP desde compra recibida.
- Fase 3C-C: validaciones de consistencia CxP en health/preflights.

## Reanclaje Fase 3C

Estado vigente: `FASE_3C_VALIDADA_MANUALMENTE`.

Auditoria de seguridad post-3C-C completada sin hallazgos bloqueantes. La auditoria previa `2662998` queda como antecedente prematuro/documental.

Motivo:

- el repositorio ya tenia antecedente historico de simulador, generacion manual y checkers;
- el nuevo flujo reanclado activo 3C-A primero y despues 3C-B;
- Docker/PHP estan disponibles y se re-ejecutaron verificaciones completas;
- el usuario reporto QA manual completada para 3C-A y 3C-B.
- la revision tecnica post-3C-C quedo completada en `59feafc`.
- la auditoria actual verifica CxP, Caja, pagos/abonos, guards, CSRF, rollback, QA y cambios no relacionados.

## Resultado historico previo

- Bloque revisado: Fase 3C.
- Resultado historico: sin hallazgos bloqueantes por revision estatica.
- Reclasificacion: auditoria prematura; no sustituye la auditoria de seguridad post-3C-C.
- Cambios de codigo historicos: ajuste menor en preview para no enlazar proveedor si el proveedor no pertenece al hotel actual.

## Resultado actual

- Resultado: sin hallazgos bloqueantes.
- No se detecta creacion de CxP sin `hotel_id`.
- No se detecta CxP con compra/proveedor de otro hotel.
- No se detecta duplicacion de CxP por compra/hotel.
- No se detecta CxP desde compra no recibida, cancelada o borrador.
- No se detectan movimientos en `cuentas_por_pagar_movimientos`.
- No se detectan movimientos de Caja relacionados con CxP.
- No existe tabla `compra_pagos`.
- `/api/sync` sigue bloqueado por checker.
- Cambios no relacionados de dashboard/habitaciones/notificaciones quedaron fuera del bloque 3C; al iniciar esta auditoria ya estaban en commit separado `e52766e`.
- Cambios PWA no relacionados detectados al cierre 3C fueron validados manualmente y commiteados por separado en `35abdc7`.
- QA manual final 3C reportada por el usuario como OK: preview, simulador, bloqueo por CxP existente, links, detalle CxP, origen compra/proveedor, no duplicados, sin pagos, sin abonos, sin Caja y sin movimientos de Caja.

## Controles revisados

- CxP conserva rutas GET de consulta:
  - `/cuentas-por-pagar`;
  - `/cuentas-por-pagar/generacion-preview`;
  - `/cuentas-por-pagar/{id}`.
- CxP tiene un unico POST activo en 3C-B:
  - `/cuentas-por-pagar/generar-desde-compra/{id}`.
- El boton de generacion solo aparece en compras elegibles del preview.
- CxP no contiene acciones de pago.
- CxP 3C-B escribe solo en `cuentas_por_pagar` durante generacion manual.
- CxP no escribe en `cuentas_por_pagar_movimientos`.
- Health/preflights fallan si `cuentas_por_pagar_movimientos` deja de estar en cero durante Fase 3C-C.
- SQL read-only 3C-C confirma cero movimientos CxP/pagos/abonos y cero movimientos de Caja con referencia CxP.
- CxP no toca `movimientos_caja`, `cortes_caja` ni `cajas`.
- Modelo CxP filtra por `hotel_id`.
- La generacion valida compra recibida, proveedor del mismo hotel, total positivo, detalles existentes y no duplicado.
- Proveedor/Compras no generan CxP automaticamente.
- Sidebar expone CxP bajo el gate de Inventario.
- Acceso sin sesion a `/cuentas-por-pagar` redirige a login.
- `/api/sync` sigue fuera de alcance y validado por checker como bloqueado.

## Auditoria especifica Fase 3C post-3C-C

- Creacion de CxP sin `hotel_id`: bloqueada por `CuentaPorPagar::generarDesdeCompraRecibida()`, que valida `hotelId > 0` y lo inserta explicitamente.
- Creacion de CxP con proveedor de otro hotel: bloqueada por `obtenerCompraParaGeneracion()`, que une proveedor con `p.hotel_id = c.hotel_id`, y por `assertCompraGenerable()`.
- Creacion de CxP con compra de otro hotel: bloqueada por busqueda `WHERE c.id = ? AND c.hotel_id = ?`.
- Duplicacion de CxP: bloqueada por transaccion, `FOR UPDATE` sobre compra y verificacion `cuentas_por_pagar` por `(hotel_id, compra_id)`.
- Generacion desde compra no recibida: bloqueada por `assertCompraGenerable()` con estado exacto `recibida`.
- Generacion desde compra cancelada/borrador: bloqueada por la misma validacion de estado y el boton no aparece para filas no elegibles.
- Escritura accidental en Caja: no hay referencias de escritura a `movimientos_caja`, `cortes_caja` ni `cajas` en el flujo CxP.
- Creacion accidental de pagos: no hay rutas, vistas ni modelo de pagos CxP; `cuentas_por_pagar_movimientos` no se inserta en 3C.
- Endpoints POST sin proteccion: el unico POST 3C llama `validateCSRF()` y pasa por `before()` con `requireAuth`, contexto hotelero y modulo `inventario`.
- Falta de CSRF: el formulario elegible usa `csrf_field()`.
- Botones visibles en estados incorrectos: el boton solo se renderiza cuando `es_elegible` es verdadero.
- Errores de permisos: CxP comparte guardas de modulo `inventario` en controlador y sidebar.
- `/api/sync` modificado accidentalmente: no hay cambios de codigo en `ApiController` ni en la ruta `/api/sync` dentro de esta revision.
- Rollback insuficiente: `docs/rollback-cola.md` documenta rollback por 3C-A, 3C-B, 3C-C y revision post-QA.
- QA critica 3C-A/3C-B: completada manualmente por el usuario.
- QA critica 3C-C: cubierta por health/preflights y SQL read-only con `ERROR: 0`.

## Warnings conocidos

- Los checkers ejecutados dentro del contenedor no ven `docs/technical` ni `migrations/` completos por el montaje actual.
- Verificacion automatica 3C ejecutada con Docker/PHP disponible: `php -l`, health, preflights, SQL read-only, POST sin sesion historico y prueba local controlada.
- Hay tablas legacy/duplicadas documentadas que no se deben borrar ni fusionar.
- Los cambios PWA no relacionados (`src/app/services/PwaPushService.php`, `src/public_html/service-worker.js`) quedaron fuera de CxP y fueron resueltos por separado en `35abdc7`.

## Riesgos residuales

- Si una fase futura agrega pagos, debe crear contrato nuevo y revisar Caja, saldos y movimientos financieros desde cero.
- No hay indice unico fisico documentado para `(hotel_id, compra_id)`; la prevencion actual usa bloqueo transaccional sobre la compra y verificacion de CxP existente.

## Recomendacion

Bloque 3C cerrado tecnicamente. Mantener prohibidos pagos, Caja, CxC, nomina operativa, permisos profundos y `/api/sync` hasta nuevo bloque explicito.

## Fase 3C - controles esperados

- Simulador primero, sin escritura.
- Generacion manual posterior, nunca automatica.
- Validar compra recibida y `hotel_id`.
- Validar proveedor del mismo hotel.
- Validar no duplicado por `(hotel_id, compra_id)`.
- Validar `total > 0`.
- Registrar auditoria si `AuditService` esta disponible.
- Mantener movimientos Caja-CxP en cero.

## Fase 4A Centro Documental - auditoria inicial de contrato

Estado: `MIGRACION_4A_COMPLETADA`.

- Riesgo principal: exposicion accidental de documentos privados si se guardan en
  `public_html/uploads`.
- Mitigacion definida: usar `STORAGE_PATH/documentos` y descarga por controlador.
- Patron seguro de referencia: `ReporteLinkController`, con `realpath`, raices
  permitidas, validacion de hotel/permisos y headers privados.
- Tablas generales creadas de forma aditiva y vacia:
  `documento_tipos`, `documentos`, `documento_entidades`.
- 4A-A no implementa uploads, POST, descargas, acciones de borrado ni exposicion publica.
- `storage_path` queda documentado como almacenamiento privado futuro, no URL publica.
- No se insertaron documentos ni relaciones; conteos iniciales en cero.
- No se detectaron pagos/abonos CxP nuevos ni movimientos CxP.
- Prohibido en 4A: Caja, pagos, abonos, Fase 3D y `/api/sync`.

### Auditoria 4A-A

- Backup previo confirmado con SHA256 antes de aplicar DB.
- Migracion usa `CREATE TABLE IF NOT EXISTS`.
- No contiene `DROP`, `DELETE`, `UPDATE` de datos operativos ni `ALTER` destructivo.
- No modifica tablas de Caja, pagos, abonos, CxP operativa ni `/api/sync`.
- Las tablas nuevas incluyen `hotel_id` para aislamiento multi-hotel.
- Riesgo residual: las relaciones polimorficas no pueden tener FK directa contra cada
  entidad; las fases read-only/upload deben validar entidad y hotel en modelo/servicio.

## Bloque Personal y Nomina (Fase NP) - controles esperados

### Estado NP-0

- Solo contrato/diagnostico/diseno; sin codigo, sin migraciones aplicadas, sin escritura
  en DB. Sin superficie de ataque nueva todavia.

### Controles a verificar en NP-A..NP-F

- Rutas sensibles del modulo bajo `requireAuth` y contexto hotelero; HTTP sin sesion bloquea.
- Toda escritura (pago/anticipo/prestamo/asistencia/documento/alta-baja) con POST + CSRF.
- Filtro `hotel_id` en todas las consultas; trabajador y movimiento del mismo hotel.
- Trabajador independiente de `usuarios`: no requiere login; vinculo opcional `usuario_id`.
- Sin ALTER destructivo ni borrado sobre `usuarios`/`hotel_usuarios`.
- Sin escritura en Caja (`cajas`, `movimientos_caja`, `cortes_caja`); movimientos
  Caja-nomina deben seguir en cero.
- Sin categoria "Nomina" nueva en `categorias_movimientos`.
- Saldos derivados del ledger, no editables manualmente.
- Baja logica de trabajador/documentos, nunca borrado fisico.
- Auditoria con `AuditService` en escrituras relevantes.
- `/api/sync` fuera de alcance y bloqueado.
- Montos no negativos garantizados por `CHECK` y por validacion de aplicacion.
