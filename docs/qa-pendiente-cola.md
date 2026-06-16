# QA pendiente - cola autonoma

## QA critica

- Confirmar que CxP es solo lectura.
- Confirmar que no hay botones de pago en `/cuentas-por-pagar`.
- Confirmar que no hay botones de pago en `/cuentas-por-pagar/{id}`.
- Confirmar que no existe ningun POST operativo de CxP.
- Confirmar que no se generan movimientos de Caja al abrir CxP.
- Confirmar que no se genera CxP automaticamente desde compras.
- Confirmar que una compra recibida no puede recibirse dos veces.
- Confirmar que cada linea recibida conserva exactamente un `movimiento_inventario_id`.
- Confirmar que proveedores, compras y CxP no muestran datos de otro hotel.
- Confirmar que `/api/sync` sigue bloqueado.

## QA funcional

- Iniciar sesion en un hotel con modulo de inventario activo.
- Abrir `/cuentas-por-pagar`.
- Validar estado vacio cuando no hay CxP.
- Revisar filtros de estado/proveedor/compra en el listado.
- Revisar navegacion desde sidebar.
- Cuando exista un registro real o de prueba autorizado, abrir `/cuentas-por-pagar/{id}` y validar detalle read-only.
- Validar que el detalle preserve links de lectura hacia compra/proveedor cuando existan.

## QA regresion

- Revisar `/compras/reportes/recibidas`.
- Revisar `/proveedores/{id}`.
- Revisar detalle de compra recibida.
- Confirmar que recepcion de compra no permite doble recepcion.
- Confirmar que compras recibidas no crean CxP automaticamente todavia.
- Confirmar que Caja/cortes/movimientos no cambian al consultar CxP.

## QA visual

- Revisar diseno de tabla CxP en escritorio.
- Revisar diseno de tabla CxP en movil.
- Revisar mensajes cuando no hay datos.
- Revisar consistencia visual con compras y proveedores.
- Revisar que sidebar no mezcle identidad Medisoft SaaS con branding hotelero.

## Estado

- QA automatica post-commit Fase 3B: completada sin errores bloqueantes.
- Auditoria de seguridad post-cierre: completada sin hallazgos bloqueantes.
- Cierre tecnico del bloque: completado.
- QA manual del bloque 2X-3B: reportada como realizada por el usuario.
- Bloqueante para Fase 3C: no; Fase 3C fue autorizada por nuevo mensaje real.

## QA Fase 3C

Estado vigente: `FASE_3C_VALIDADA_MANUALMENTE`.

El usuario reporto QA manual completada para 3C-A y 3C-B. Fase 3C-C queda completada tecnicamente con validaciones read-only en health/preflights y SQL de consistencia.
Revision tecnica 3C completada sin hallazgos bloqueantes: rutas, controlador, modelo, vistas, sidebar, permisos/guards, CSRF, filtros `hotel_id`, CxP `#2`, ausencia de pagos/abonos/Caja y `/api/sync` sin cambios.
Auditoria seguridad 3C completada sin hallazgos bloqueantes: cero movimientos CxP/pagos/abonos, cero Caja-CxP, `compra_pagos` inexistente y cambios no relacionados fuera del bloque. Los cambios PWA no relacionados fueron validados manualmente y commiteados por separado en `35abdc7`.
Cierre tecnico 3C completado documentalmente. La QA manual final del bloque 3C fue reportada como OK por el usuario.

### QA critica

- Confirmar que el flujo 3C-B solo escribe cuando el usuario presiona explicitamente `Generar CxP`.
- Confirmar que la generacion 3C-B es manual, no automatica.
- Confirmar que solo compras `recibida` generan CxP.
- Confirmar que no se duplica CxP por compra.
- Confirmar que CxP generada respeta `hotel_id`.
- Confirmar que proveedor y compra pertenecen al mismo hotel.
- Confirmar que no hay Caja, pagos ni abonos.
- Confirmar que `/api/sync` sigue bloqueado.

### QA funcional

- Revisar listado de compras recibidas elegibles.
- Revisar motivos de bloqueo cuando una compra ya tiene CxP.
- Confirmar que `/cuentas-por-pagar/generacion-preview` muestra `Generar CxP` solo en compras elegibles.
- Generar CxP manualmente desde una compra recibida autorizada.
- Revisar listado y detalle CxP despues de generar.
- Revisar detalle de compra y preview para ver CxP vinculada cuando ya exista.

### QA visual

- Revisar simulador en escritorio y movil.
- Revisar estados elegible/bloqueado.
- Revisar mensajes de exito/error.
- Revisar consistencia con compras/proveedores/CxP.

### QA regresion

- Confirmar que recepcion de compra no genera CxP automaticamente.
- Confirmar que compras/proveedores siguen aislados por hotel.
- Confirmar que Caja/cortes/movimientos no cambian.
- Confirmar que reportes de compras recibidas siguen funcionando.

### QA especifica Fase 3C-A

- Estado manual: OK reportado por el usuario.
- Abrir `/cuentas-por-pagar/generacion-preview` autenticado.
- Confirmar que muestra compras recibidas del hotel actual.
- Confirmar que muestra links a compra y proveedor.
- Confirmar que muestra link a CxP solo si ya existe.
- Confirmar que las compras elegibles indican motivo de elegibilidad.
- Confirmar que las compras no elegibles indican motivo de bloqueo.
- Confirmar que el boton `Generar CxP` solo aparece en filas elegibles.
- Confirmar que no hay botones de pago, abono ni Caja.

### QA especifica Fase 3C-B

- Estado manual: OK reportado por el usuario.
- Usar una compra recibida elegible.
- Presionar `Generar CxP`.
- Confirmar redireccion al detalle de la CxP.
- Confirmar que el preview cambia la compra a bloqueada por CxP existente.
- Intentar generar de nuevo y confirmar error claro.
- Confirmar que no cambia `movimientos_caja`.
- Confirmar que no hay pagos ni abonos.

## Resultado automatico Fase 3C-B

- Backup previo confirmado: `src/storage/backups/phase3c_b_20260615_144908_before_manual_cxp_medisoft_hoteles_import.sql`.
- SHA256: `C0403F7B5ACBDA35EF4C05E2840546D5D9978802A21C9736BEBC6FF462518061`.
- Prueba local controlada ejecutada sobre compra `#2` del hotel `4`.
- Se genero CxP `#2`.
- La doble generacion fue bloqueada limpiamente.
- No se crearon movimientos en `cuentas_por_pagar_movimientos`.
- No cambiaron `cajas` ni `movimientos_caja`.
- `logs_auditoria` aumento de `25` a `26`.

## Resultado automatico Fase 3C-C

- Health checker actualizado para detectar inconsistencias CxP generadas desde compras.
- Preflight de compras minimas actualizado con las mismas reglas de consistencia.
- Preflight de recepcion de compras actualizado con las mismas reglas de consistencia.
- Validaciones automaticas agregadas:
  - CxP duplicada por compra/hotel;
  - CxP con compra inexistente;
  - CxP con proveedor inexistente;
  - CxP con `hotel_id` nulo;
  - CxP con compra de otro hotel;
  - CxP con proveedor de otro hotel;
  - CxP generada desde compra no recibida;
  - CxP con saldo mayor al total;
  - CxP con total invalido o saldo negativo;
  - CxP sin fecha de emision;
  - movimientos CxP/pagos/abonos accidentales;
  - movimientos de Caja con referencia textual a CxP.
- Resultado verificado en DB local:
  - `cuentas_por_pagar`: 2;
  - `cuentas_por_pagar_movimientos`: 0;
  - inconsistencias CxP principales: 0;
  - movimientos de Caja con referencia textual a CxP: 0;
  - `compra_pagos`: tabla inexistente.

## QA manual historica Fase 3C

- `/cuentas-por-pagar/generacion-preview`: OK.
- La compra `#2` aparece con CxP generada/bloqueada: OK.
- Link a CxP `#2`: OK.
- `/cuentas-por-pagar/2`: OK.
- Origen compra/proveedor visible: OK.
- Sin botones de pago: OK.
- Sin abonos: OK.
- Sin acciones de Caja: OK.
- Reporte de compras recibidas: OK.
- Ficha de proveedor: OK.
- Detalle de compra recibida: OK.
- `/api/sync`: no probado manualmente; cubierto por checker automatico cuando el entorno esta disponible.
- Resultado vigente: 3C-A y 3C-B quedan validadas manualmente; no autoriza pagos, Caja ni Fase 3D.
- Resultado de cierre: bloque 3C cerrado tecnicamente; no autoriza pagos, abonos, Caja ni Fase 3D.

## QA manual final Fase 3C

- `/cuentas-por-pagar/generacion-preview`: OK.
- Simulador de CxP: OK.
- Compra con CxP existente aparece bloqueada: OK.
- Link a CxP generada: OK.
- `/cuentas-por-pagar/2`: OK.
- Origen compra/proveedor visible: OK.
- CxP generada desde compra recibida: OK.
- No se permite duplicar CxP: OK.
- No hay botones de pago: OK.
- No hay abonos: OK.
- No hay acciones de Caja: OK.
- No hay movimientos de Caja: OK.
- Reporte de compras recibidas: OK.
- Ficha de proveedor: OK.
- Detalle de compra recibida: OK.
- `/api/sync`: no probado manualmente; cubierto por checker automatico cuando el entorno esta disponible.
- Resultado: Fase 3C validada manualmente. No autoriza pagos, abonos, Caja ni Fase 3D.

## Revision tecnica Fase 3C

- Vista de preview ajustada para enlazar al proveedor solo si el join scoped por `hotel_id` encontro proveedor del hotel actual.
- Esto evita un enlace visual a proveedor no valido cuando una compra queda bloqueada por proveedor inexistente o de otro hotel.
- No cambia elegibilidad, escritura ni reglas de generacion.

## Auditoria seguridad Fase 3C post-QA

- Auditoria estatica previa sin hallazgos bloqueantes, reclasificada como prematura.
- Confirmado por revision de codigo actual: hay un unico POST 3C-B con CSRF.
- Confirmado por revision de codigo: `before()` exige autenticacion, contexto hotelero y modulo `inventario`.
- Confirmado por revision de codigo: no hay escritura en Caja ni pagos desde CxP.
- Confirmado por revision de codigo: generacion valida compra recibida, proveedor del hotel, total positivo, detalles y no duplicado.
- Health/preflights se re-ejecutaron con Docker/PHP disponible y quedaron sin errores bloqueantes.

## Triage no relacionado

- Sin cambios no relacionados pendientes al iniciar 3C-0.
- El ajuste visual del modal de check-in tardio fue commiteado en `dc3c150`.
- Cambios no relacionados ya separados en commit `e52766e`: `src/app/controllers/DashboardController.php`, `src/app/controllers/HabitacionController.php`, `src/app/controllers/NotificacionController.php`, `src/app/views/layout/sidebar.php`, `src/app/views/notificaciones/index.php`.
- Cambios PWA no relacionados resueltos despues del cierre 3C: `src/app/services/PwaPushService.php` y `src/public_html/service-worker.js` quedaron en `35abdc7`.

## QA Fase 4A Centro Documental

Estado vigente: `DOCUMENTOS_READ_ONLY_4A_COMPLETADO`.

4A-0 no implemento funcionalidad. 4A-A creo solo esquema base, sin uploads, sin POST,
sin descargas y sin datos operativos. 4A-B agrega capa de consulta read-only de metadata
sin exponer archivos.

### Resultado automatico Fase 4A-A

- Backup previo confirmado:
  `src/storage/backups/phase4a_20260615_182352_before_document_center_medisoft_hoteles_import.sql`.
- SHA256: `698A304F69B312EF06EABA787C83969629F2B14BCA096906CC08CADDC7D898F0`.
- Migracion aplicada: `migrations/20260615_004_fase_4a_centro_documental_base.sql`.
- Tablas documentales existentes y vacias:
  - `documento_tipos`: `0`;
  - `documentos`: `0`;
  - `documento_entidades`: `0`.
- `cuentas_por_pagar_movimientos`: `0`.
- No se detectaron tablas de pagos/abonos CxP creadas.
- No se implemento acceso publico a documentos.

### Resultado automatico Fase 4A-B

- `php -l` limpio en `Documento.php`, `DocumentoController.php`, vistas de documentos,
  rutas y sidebar.
- Rutas GET creadas:
  - `/documentos`;
  - `/documentos/{id}`;
  - `/documentos/entidad/{entidad_tipo}/{entidad_id}`.
- HTTP sin sesion en `/documentos`: debe redirigir a login por middleware global.
- La vista de listado muestra estado vacio cuando no hay documentos.
- La vista de detalle no muestra `storage_path`, `nombre_archivo` ni rutas internas.
- No existen rutas POST bajo `/documentos`.
- No se implemento upload, descarga, edicion ni borrado.

### QA critica futura

- Documento de un hotel no visible en otro hotel.
- Archivo privado no accesible por URL directa.
- Descarga sin sesion bloqueada o redirigida.
- Upload rechaza extension, MIME o tamano invalido.
- No hay escritura en Caja, pagos, abonos ni CxP operativa.
- `/api/sync` sigue bloqueado.

### QA funcional futura

- Proveedor, compra, CxP, huesped y reservacion muestran estado vacio documental.
- Documento adjunto aparece solo en la entidad correcta.
- Descarga autenticada funciona con permisos.

### QA visual futura

- Estado vacio claro.
- Lista de documentos compacta y consistente con el sistema hotelero.
- No mezclar identidad Medisoft SaaS con branding del hotel.

### QA regresion futura

- Fase 3C sigue funcionando.
- PWA push sigue funcionando.
- Reportes seguros siguen descargando.
- Login/logout normal.

## QA Bloque Personal y Nomina (Fase NP)

### Estado NP-0

- Contrato y diagnostico documentados; sin funcionalidad, sin migraciones aplicadas, sin escritura en DB.
- No hay QA manual pendiente especifica de NP-0 mas alla de validar lectura del contrato.
- Git limpio al iniciar; HEAD `5dfe665`.

### QA critica planificada (NP)

- Confirmar que NO existe ningun movimiento de Caja generado por nomina (debe seguir en 0).
- Confirmar que un trabajador puede existir SIN usuario del sistema (sin login).
- Confirmar que `usuarios` no se altera de forma destructiva ni se borra.
- Confirmar que toda tabla `trabajador*` respeta `hotel_id` y no muestra datos de otro hotel.
- Confirmar que pagos/anticipos/prestamos son acciones explicitas (POST + CSRF), nunca automaticas.
- Confirmar que los saldos son derivados del ledger y no editables manualmente.
- Confirmar que `/api/sync` sigue bloqueado.

### QA funcional planificada (NP)

- NP-A: listado de trabajadores por hotel, ficha read-first, alta/edicion controlada, baja logica, documentos.
- NP-B: registrar pago/anticipo/prestamo con validacion de hotel, trabajador activo y monto valido; mensajes claros.
- NP-C: ver saldo a favor/en contra, historial y reporte semanal/quincenal por hotel y trabajador.
- NP-D: registrar asistencia y comisiones/bonos/descuentos y ver su efecto en saldos.

### QA regresion planificada (NP)

- Confirmar que Caja/cortes/movimientos no cambian al usar el modulo de nomina.
- Confirmar que `usuarios`/`hotel_usuarios` siguen intactos.
- Confirmar que mantenimiento/limpieza siguen funcionando (la referencia de responsable es logica/opcional).

### QA visual planificada (NP)

- Listado y ficha de trabajador en escritorio y movil.
- Estados vacios claros (sin trabajadores, sin movimientos).
- Consistencia visual con el resto del sistema; sin mezclar branding Medisoft SaaS con branding hotelero.
