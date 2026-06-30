# Reporte de issues - Testeo funcional Medisoft Hoteles - 2026-06-28

Rama objetivo: `feature/saas-multihotel`
Hotel principal probado: Los Cedros
Usuario principal: `admin` / Administrador Principal
Bitacora fuente: `docs/bitacora_testeo_funcional_20260628.md`

## Resumen ejecutivo

Se ejecuto la primera bateria funcional guiada sobre acceso, panel SaaS, dashboard, habitaciones, huespedes, reservaciones, check-in, check-out, caja, facturacion, inventario, compras ligadas a inventario, CxC/CxP con caja, documentos/adjuntos, reportes centrales, responsive/mobile preliminar y smoke PWA de `/api/sync`. Tambien se probo el caso critico reportado por el usuario: modificar noches despues de check-in y pago.

Resultado actual: todos los BUG funcionales detectados en esta bateria quedaron con fix validado. Los bloques multi-hotel amplio, Caja manual, Facturacion, Inventario/Compras, CxC/CxP, Documentos/Tareas, Reportes y Responsive/mobile quedaron aprobados en los casos probados. En Reportes quedaron aprobados los totales centrales, gerencial diario, ejecutivo, AJAX de graficas, historial de links y aislamiento de descarga; BUG-017 a BUG-021 quedaron con fix validado. En Responsive/mobile quedaron aprobados login sin overflow, sidebar/overlay movil, zoom/pinch, scroll real, header auto-hide, modal de caja y matriz general autenticada; BUG-022, BUG-023, BUG-024, BUG-025 y BUG-026 quedaron con fix validado. El smoke PWA de `/api/sync` quedo aprobado con HTTP 423 y JSON `sync_temporarily_disabled`. Despues de los fixes se ejecuto regresion funcional final de humo profundo: `32/32` checks pasaron en Los Cedros y Maximiliano, sin errores PHP/SQL detectados ni logs nuevos. No quedan BUG funcionales abiertos en esta bitacora.

Issues detectados:

| Severidad | Cantidad | IDs |
|---|---:|---|
| P0 | 2 | BUG-004, BUG-005 con fix validado |
| P1 | 9 | BUG-001, BUG-002, BUG-003, BUG-006, BUG-007, BUG-009, BUG-010, BUG-011 y BUG-017 con fix validado |
| P2 | 11 | BUG-008, BUG-012, BUG-014, BUG-015, BUG-016, BUG-018, BUG-019, BUG-020, BUG-021, BUG-022 y BUG-024 con fix validado |
| P3 | 4 | BUG-013, BUG-023, BUG-025 y BUG-026 con fix validado |

## Estado de correcciones

1. BUG-004 y BUG-005: inconsistencias financieras al modificar dias post-check-in. Fix validado en casos nuevos `#46` y `#47`.
2. BUG-001 y BUG-002: contexto hotelero/branding/logout corregidos y validados con cookie previa `maximiliano` y sesion Los Cedros.
3. BUG-008: warnings PHP al abrir detalle de reservacion. Fix validado en caso `#51`.
4. BUG-006: subtotal persistido por habitacion queda desactualizado al modificar dias. Fix validado en caso nuevo `#45`.
5. BUG-007: check-out con saldo pendiente. Fix validado en casos `#40` y `#47`.
6. BUG-009 y BUG-010: issues de facturacion corregidos y validados en `#57` y `#232`.
7. BUG-011: entradas/salidas manuales de inventario corregidas y validadas con producto `#43`.
8. BUG-012: trazabilidad de usuario en inventario manual corregida y validada.
9. BUG-013: descripcion de producto corregida y validada con producto `#46`.
10. BUG-014: exportacion rapida de inventario corregida y validada.
11. BUG-015: UI de ajuste fraccionable corregida y validada.
12. CxC/CxP con Caja: bloque probado sin issues nuevos; CxC `#3` y CxP `#3` terminan con saldo original y neto Caja `0.00` tras reversiones.
13. Documentos/Tareas: BUG-016 corregido con migracion `20260630_001_documento_entidades_tarea_enum.sql`; tarea `#5` de Maximiliano acepta documento vinculado `#22`.
14. Reportes: bloque probado y corregido; ingresos/gastos, gerencial diario, ejecutivo, AJAX, links seguros, selector/export por usuario, PDFs expuestos, ocupacion, estancia y ranking estados pasan en retest Los Cedros. BUG-017 a BUG-021 quedan con fix validado.
15. Responsive/mobile: login, sidebar/overlay, zoom/pinch, scroll real, header auto-hide, modal de caja y matriz autenticada probados; BUG-022, BUG-023, BUG-024, BUG-025 y BUG-026 quedaron con fix validado, y queda pendiente revalidar modal de check-in cuando haya boton visible.
16. PWA smoke `/api/sync`: POST autenticado con sesion Los Cedros responde `HTTP/1.1 423 Locked`, `Content-Type: application/json` y JSON `sync_temporarily_disabled`; no se tocaron `service-worker.js`, IndexedDB, cache names ni el endpoint.
17. Regresion final post-fixes: `32/32` checks pasaron. Se verificaron rutas operativas de Los Cedros, documentos/tareas de Maximiliano, descarga preview de documento `#22`, `/api/sync` bloqueado en `423`, logs limpios, lints PHP/JS y persistencia de migracion/vinculo `tarea`.

## Cierre y priorizacion

Se creo el cierre operativo en `docs/cierre_priorizacion_testeo_funcional_20260628.md`.

Regresion final ejecutada despues de cerrar correcciones funcionales:

1. `32/32` checks pasaron en Los Cedros y Maximiliano.
2. Logs post-regresion sin `PHP Warning`, `Fatal error`, `SQLSTATE`, `Uncaught`, `Undefined variable`, `ONLY_FULL_GROUP_BY` ni `Call to a member`.
3. Testeo de seguridad queda como siguiente fase separada, segun decision del usuario.

## Issues

### BUG-011 - Entradas/salidas manuales aceptan cero/negativos y truncan decimales

Severidad: P1
Estado: FIX VALIDADO
Modulo: Inventario / Entrada manual / Salida manual
URL: `/inventario/entrada`, `/inventario/salida`

Caso reproducido:

- Producto `#43`, codigo `QAINV0628A`, unidad `litro`.
- Entrada `1.75` se guarda como `1.00`; stock queda `6.00` en vez de `6.75`.
- Entrada `0` crea movimiento `#1133` por `0.00`.
- Entrada `-3` se convierte en entrada positiva `3.00`.
- Salida `0` crea movimiento `#1136` por `0.00`.
- Salida `-2` se convierte en salida positiva `2.00`.
- Salida `1.50` se guarda como `1.00`; stock queda `4.00` en vez de `3.50`.

Impacto:

El stock puede quedar incorrecto en productos fraccionables y el historial puede acumular movimientos fantasma. Fue el issue principal del bloque de inventario manual y ya quedo revalidado.

Validacion del fix:

- Entrada `1.75` queda exacta: stock `7.75 -> 9.50`, movimiento `#1142`.
- Entrada `0` y `-3` redirigen a `/inventario/entrada` y no crean movimientos.
- Salida `1.50` queda exacta: stock `9.50 -> 8.00`, movimiento `#1143`.
- Salida `0` y `-2` redirigen a `/inventario/salida` y no crean movimientos.
- Salida `99` sigue bloqueada por stock insuficiente.

### BUG-012 - Movimientos manuales de inventario no registran usuario

Severidad: P2
Estado: FIX VALIDADO
Modulo: Inventario / Auditoria
URL: `/inventario/guardar`, `/inventario/procesarEntrada`, `/inventario/procesarSalida`

Caso reproducido:

- Stock inicial de producto `#44` crea movimiento `#1141` con `usuario_id=NULL`.
- Entrada manual `#1131` y salida manual `#1135` tambien quedan con `usuario_id=NULL`.
- Ajuste manual `#1139` si registra `usuario_id=1`.

Impacto:

Se pierde trazabilidad de quien creo o movio inventario manualmente.

Validacion del fix:

- Producto `#45`, codigo `QAINV0628C`, creado con stock inicial `2.00`; movimiento `#1144` queda con `usuario_id=1`.
- Entrada manual `0.50` sobre producto `#43`; movimiento `#1145` queda con `usuario_id=1`.
- Salida manual `0.25` sobre producto `#43`; movimiento `#1146` queda con `usuario_id=1`.

### BUG-013 - Campo descripcion de producto no persiste

Severidad: P3
Estado: FIX VALIDADO
Modulo: Inventario / Catalogo de productos
URL: `/inventario/nuevo`, `/inventario/editar/43`, `/inventario/editar/46`

Caso reproducido:

Se envio descripcion al crear y editar el producto `#43`, pero `inventario_productos.descripcion` queda `NULL`.

Impacto:

El usuario captura un campo visible que el sistema descarta silenciosamente.

Fix aplicado:

Se agrego `descripcion` a `$fillable` del modelo `Inventario`; el controlador ya enviaba el campo, pero el modelo lo descartaba antes de persistir.

Validacion del fix:

- Producto `#46`, codigo `QAINV0628D`, creado con descripcion `QA BUG-013 descripcion creada`; DB guarda el valor.
- Producto `#46` editado con descripcion `QA BUG-013 descripcion editada`; DB actualiza el valor.

### BUG-014 - Exportacion rapida de inventario usa fechas hardcodeadas de 2025

Severidad: P2
Estado: FIX VALIDADO
Modulo: Inventario / Exportar PDF
URL: `/inventario/exportar`

Caso reproducido:

- La vista indica que los movimientos estan registrados con fechas de 2025.
- `getFechaActual()` retorna fijo `2025-09-13`.
- Los botones rapidos envian `2025-09-12/13`.
- Con rango correcto `2026-06-28`, el PDF si se genera correctamente.

Impacto:

El usuario puede descargar reportes vacios o desactualizados desde los botones rapidos.

Fix aplicado:

- `exportarAction()` envia `fecha_hoy` e `fecha_inicio_mes` a la vista.
- El formulario queda por defecto en inicio de mes a hoy.
- Los botones rapidos usan las constantes JS renderizadas por servidor, sin fechas fijas de 2025.

Validacion del fix:

- `/inventario/exportar` renderiza `2026-06-01` y `2026-06-28`; no aparece `2025-09` en la vista servida.
- Exportacion de hoy (`2026-06-28` a `2026-06-28`) genera `inventario_los_cedros_20260628_142314.pdf`, 14 KB.
- Exportacion de stock actual (`2026-06-01` a `2026-06-28`) genera `inventario_los_cedros_20260628_142609.pdf`, 17 KB.

### BUG-015 - Ajuste de stock muestra piezas y fuerza enteros para productos fraccionables

Severidad: P2
Estado: FIX VALIDADO
Modulo: Inventario / Ajuste
URL: `/inventario/ajuste/43`

Caso reproducido:

Producto `#43` es unidad `litro`, pero la vista de ajuste muestra `pzs`, redondea el stock a enteros y usa `step=1`. El backend si acepta un ajuste decimal `1.25` y registra movimiento `#1139`.

Impacto:

La UI no acompana productos en litros/kilos aunque el backend y la tabla si soportan decimales.

Fix aplicado:

- La vista toma la unidad real desde `unidad_medida`.
- El stock actual se muestra con dos decimales.
- El input de cantidad usa `min=0.01`, `step=0.01` e `inputmode=decimal`.
- El preview usa `parseFloat`, conserva decimales y muestra la unidad real.

Validacion del fix:

- `/inventario/ajuste/43` renderiza `8.25 litro`, input decimal y sin `pzs` visible en producto fraccionable.
- Preview entrada `1.25`: `Stock actual: 8.25 -> Stock nuevo: 9.50 litro`.
- Preview salida `1.25`: `Stock actual: 8.25 -> Stock nuevo: 7.00 litro`.
- POST entrada `0.25` crea movimiento `#1147`, stock `8.25 -> 8.50`, `usuario_id=1`.
- POST salida `0.25` crea movimiento `#1148`, stock `8.50 -> 8.25`, `usuario_id=1`.

### BUG-016 - Documentos de tareas no se pueden vincular por incompatibilidad de enum

Severidad: P2
Estado: FIX VALIDADO
Modulo: Documentos / Tareas
URL: `/tareas/5`, `/documentos/subir?entidad_tipo=tarea&entidad_id=5`

Caso reproducido:

- La tarea `#5` de Maximiliano muestra panel `Documentos vinculados` y boton `Vincular documento`.
- El formulario carga con `entidad_tipo=tarea` y `entidad_id=5`.
- Al subir un JPG valido con titulo `QA-DOC-20260628-TAREA-BUG`, el POST redirige al formulario con error SQL.

Resultado real:

La carga falla con:

- `SQLSTATE[01000]: Warning: 1265 Data truncated for column 'entidad_tipo' at row 1`.

Impacto:

El usuario ve la accion disponible en tareas, pero no puede adjuntar evidencia documental. Tambien se expone un error SQL en UI y se marca como error del campo `Tipo de documento`, aunque la falla real es el contrato de entidad.

Evidencia:

- `Documento::ENTIDAD_TIPOS` acepta `tarea`.
- `documento_entidades.entidad_tipo` no incluye `tarea` en su enum.
- No se crearon documentos ni vinculos tras el intento: `doc_tarea_after=0`, `link_tarea_after=0`.
- No quedaron archivos huerfanos recientes en `storage/documentos/hotel_4/2026/06`.

Validacion del fix:

- Con autorizacion explicita del usuario, se agrego la migracion `migrations/20260630_001_documento_entidades_tarea_enum.sql`.
- La migracion modifica solo `documento_entidades.entidad_tipo` para incluir `tarea`, valida el cambio y registra `migrations.nombre = 20260630_001_documento_entidades_tarea_enum.sql`.
- Verificacion DB: `entidad_tipo` quedo como `enum('proveedor','compra','cuenta_por_pagar','huesped','reservacion','trabajador','tarea')`; migracion `#49`, batch `40`, estado `ejecutada`.
- Retest funcional con sesion temporal Maximiliano (`hotel_id=4`, usuario `adminmax`): `POST /documentos/subir` con `entidad_tipo=tarea`, `entidad_id=5`, archivo PNG permitido y titulo `QA-DOC-20260630-TAREA-BUG016` responde `303` a `/documentos/22`.
- DB creo documento `#22`, `hotel_id=4`, `estado=activo`, `storage_path=documentos/hotel_4/2026/06/doc_20260629_184954_4a8e083554857542.png`.
- DB creo vinculo `documento_entidades`: `entidad_tipo=tarea`, `entidad_id=5`, `relacion=evidencia_qa`.
- `/tareas/5` contiene el titulo `QA-DOC-20260630-TAREA-BUG016`, confirmando que la ficha de tarea muestra el documento vinculado.
- `php -l` sin errores en `Documento.php`, `DocumentoController.php` y `TareaController.php`.

### BUG-017 - Reporte financiero por usuario lista y exporta usuarios de otros hoteles

Severidad: P1
Estado: FIX VALIDADO
Modulo: Reportes / Ingresos vs gastos / Reporte por usuario
URL: `/reportes/ingresos-gastos`, `/reportes/exportar-pdf?tipo=ingresos-gastos-usuario`

Caso reproducido:

- En Los Cedros y Maximiliano, el selector `usuario_id` del reporte financiero por usuario muestra usuarios activos globales.
- Aparecen usuarios de otros hoteles, por ejemplo `Admin Demo SaaS` y `QA Admin Temporal 174223`.
- Desde Los Cedros se genero PDF por usuario con `usuario_id=22`, correspondiente a `Admin Demo SaaS` de otro hotel.

Resultado real:

El sistema crea un link seguro de Los Cedros `#18` con parametros `{"usuario_id":22,"usuario_nombre":"Admin Demo SaaS"}`.

Impacto:

Rompe aislamiento funcional multi-hotel en la UI de reportes y permite registrar reportes administrativos incoherentes con usuarios de otro hotel. Aunque los movimientos siguen filtrados por `mc.hotel_id`, se expone identidad externa y se permite una seleccion invalida.

Validacion del fix:

- El selector ahora se alimenta desde `hotel_usuarios` + `usuarios` activos del hotel actual.
- En Los Cedros, el HTML del selector ya no contiene `Admin Demo SaaS` ni `QA Admin Temporal`.
- Exportar desde Los Cedros con `usuario_id=22` devuelve `303` a `/reportes/ingresos-gastos` y no crea link nuevo; el conteo de links cross-hotel permanece en `1`, que corresponde a la evidencia vieja `#18`.
- Exportar con usuario valido `usuario_id=1` devuelve `200 application/pdf`, firma `%PDF`, `14874` bytes y crea link `#19`.
- La sesion QA de Maximiliano expiro durante este retest; queda opcional revalidar visualmente ese selector cuando haya sesion activa.

### BUG-018 - PDF principal de ingresos/gastos devuelve error TCPDF en vez de PDF

Severidad: P2
Estado: FIX VALIDADO
Modulo: Reportes / Exportar PDF
URL: `/reportes/exportar-pdf?tipo=ingresos-gastos&fecha_inicio=2026-06-28&fecha_fin=2026-06-28`

Resultado esperado:

Debe descargarse un PDF `application/pdf` y registrarse un `reporte_links` de tipo `ingresos-gastos`.

Resultado real:

El endpoint responde `200 text/html`, 113 bytes, con:

- `TCPDF ERROR: TCPDF requires the Imagick or GD extension to handle PNG images with alpha channel.`

No se crea registro en `reporte_links`.

Impacto:

El boton/exportacion principal de ingresos vs gastos queda inutilizable para hoteles con logo PNG con transparencia; el usuario recibe un archivo HTML/error en lugar de PDF.

Validacion del fix:

- Si el runtime no tiene GD/Imagick, el PDF omite logos PNG y usa fallback textual, evitando el error TCPDF por alpha.
- `/reportes/exportar-pdf?tipo=ingresos-gastos&fecha_inicio=2026-06-28&fecha_fin=2026-06-28` responde `200 application/pdf`, firma `%PDF`, `13258` bytes.
- Se crea link seguro `#22` (`Ingresos_Gastos_los_cedros_20260628_181057.pdf`).

### BUG-019 - Exportaciones PDF de procedencia y habitaciones rentables terminan en 500

Severidad: P2
Estado: FIX VALIDADO
Modulo: Reportes / Exportar PDF
URL: `/reportes/exportar-pdf?tipo=procedencia`, `/reportes/exportar-pdf?tipo=habitaciones-rentables`

Resultado esperado:

Si esos tipos estan expuestos en el controlador/vistas, deben generar PDF o no ofrecerse como exportables.

Resultado real:

Ambos endpoints responden `500`. Logs:

- `Metodo exportarHabitacionesRentablesPdfAction no encontrado en controlador ReportesController`.
- `Metodo exportarProcedenciaPdfAction no encontrado en controlador ReportesController`.

Impacto:

El usuario puede intentar exportar reportes disponibles y caer en error de servidor.

Validacion del fix:

- Se agregaron exportaciones PDF funcionales para `procedencia` y `habitaciones-rentables`.
- `tipo=procedencia` responde `200 application/pdf`, firma `%PDF`, `8667` bytes y crea link `#20`.
- `tipo=habitaciones-rentables` responde `200 application/pdf`, firma `%PDF`, `10012` bytes y crea link `#21`.

### BUG-020 - Reporte de ocupacion falla por consultas incompatibles con ONLY_FULL_GROUP_BY

Severidad: P2
Estado: FIX VALIDADO
Modulo: Reportes / Ocupacion
URL: `/reportes/ocupacion?fecha_inicio=2026-06-01&fecha_fin=2026-06-28&tipo=diario|semanal|mensual`

Resultado esperado:

Los tres tipos de ocupacion deben cargar sin 500.

Resultado real:

`diario`, `semanal` y `mensual` devuelven `500`. Logs:

- `SQLSTATE[42000]: ... 1055 Expression ... is not in GROUP BY ... incompatible with sql_mode=only_full_group_by`.
- Fatal posterior: `Call to a member function fetchAll() on bool` en `Reporte.php`.

Impacto:

El reporte de ocupacion esta inutilizable; ademas el modelo no maneja fallas de query y provoca fatal error.

Validacion del fix:

- Se ajustaron consultas de ocupacion mensual y por dia de semana para cumplir `ONLY_FULL_GROUP_BY`.
- Se agrego vista `reportes/ocupacion`.
- `/reportes/ocupacion` responde `200 text/html` para `tipo=diario`, `tipo=semanal` y `tipo=mensual`.
- No se detectan `SQLSTATE`, `Fatal error`, `ONLY_FULL_GROUP_BY` ni `Call to a member` en los HTML revisados.

### BUG-021 - Rutas de estancia y ranking-estados apuntan a vistas inexistentes

Severidad: P2
Estado: FIX VALIDADO
Modulo: Reportes / Estancia / Ranking estados
URL: `/reportes/estancia`, `/reportes/ranking-estados`

Resultado esperado:

Las rutas registradas deben renderizar vistas funcionales o no aparecer como reportes disponibles.

Resultado real:

Ambas rutas devuelven `500`. Logs:

- `Vista reportes/estancia no encontrada`.
- `Vista reportes/ranking-estados no encontrada`.

Impacto:

El modulo expone reportes historicos que no pueden abrirse.

Validacion del fix:

- Se agregaron vistas funcionales `reportes/estancia` y `reportes/ranking-estados`.
- `/reportes/estancia?fecha_inicio=2026-06-01&fecha_fin=2026-06-28` responde `200 text/html`.
- `/reportes/ranking-estados?fecha_inicio=2026-06-01&fecha_fin=2026-06-28` responde `200 text/html`.
- No se detectan mensajes de vista inexistente, `Fatal error` ni errores SQL en los HTML revisados.

### BUG-022 - La app bloquea zoom/pinch en mobile desde meta viewport y JavaScript

Severidad: P2
Estado: FIX VALIDADO
Modulo: Responsive / Accesibilidad mobile / PWA
URL: `/h/los-cedros/login`, layout autenticado global

Caso reproducido:

- Login y layout autenticado usan `maximum-scale=1.0, user-scalable=no` en `meta viewport`.
- Login bloquea `touchstart` y `gesturestart` con `preventDefault()`.
- Paginas autenticadas cargan `pwa.js`, donde `lockMobileZoomGestures()` bloquea pinch/double-tap zoom.

Impacto:

En telefono, el usuario no puede ampliar pantallas densas como reportes, reservaciones, caja o documentos. Esto afecta accesibilidad y operacion real desde PWA o navegador movil.

Validacion del fix:

- Con autorizacion explicita del usuario, se retiro el bloqueo de zoom solo en `src/app/views/auth/login.php`, `src/app/views/layout/header.php`, `src/public_html/js/pwa.js` y `src/public_html/offline.html`.
- Login y layout autenticado ahora usan `meta viewport` con `width=device-width, initial-scale=1.0, viewport-fit=cover`, sin `maximum-scale=1.0` ni `user-scalable=no`.
- Login ya no registra listeners `touchstart`/`gesturestart` para cancelar pinch.
- `pwa.js` ya no contiene ni ejecuta `lockMobileZoomGestures()`; el archivo servido `/js/pwa.js?v=1782779600` dio `HasZoomLock=false`.
- `offline.html` ya no contiene el bloqueo duplicado de `gesturestart`, `touchmove` multi-touch ni doble toque.
- Retest Chrome mobile aislado en `390x844`: `/h/los-cedros/login` y `/offline.html` cargan con viewport accesible, sin listeners `gesture*`, sin listeners globales no pasivos de `touchmove/touchend` y sin overflow horizontal.
- Retest navegador autenticado en `/dashboard` `390x844`: viewport accesible, `scrollWidth=390`, `clientWidth=390`.
- Smoke `/api/sync` autenticado con sesion temporal Los Cedros: `HTTP/1.1 423 Locked`, `Content-Type: application/json; charset=utf-8`, body `success=false`, `error=sync_temporarily_disabled`, `pending_operations_preserved=true`.
- No se tocaron `service-worker.js`, `offline-data.js`, `reservaciones-offline.js`, IndexedDB, cache names ni la accion `/api/sync`.

### BUG-023 - Login tiene controles tactiles secundarios menores a tamano recomendado

Severidad: P3
Estado: FIX VALIDADO
Modulo: Auth / Login responsive
URL: `/h/los-cedros/login`

Caso reproducido:

Prueba visual real previa en `360x740`, `390x844`, `768x1024` y `1366x768`:

- No hay overflow horizontal en login.
- Boton `Mostrar contrasena`: aprox. `26x26`.
- Checkbox `Recordarme`: `18x18`.
- Link `Olvidaste tu contrasena`: alto visual aprox. `18px`.

Impacto:

No bloquea el inicio de sesion, pero aumenta errores de toque en telefono. Se agrava por BUG-022 porque el usuario tampoco puede hacer zoom para tocar con mas precision.

Validacion del fix:

- Cambio aplicado en `src/app/views/auth/login.php` solo a CSS de targets tactiles: boton de password `44x44`, padding derecho del campo password, label `Recordarme` con area de `44px`, checkbox visual `22x22` y link `Olvidaste tu contrasena` con alto minimo `44px`.
- No se cambiaron `action`, `method`, `name`, CSRF, hidden inputs ni flujo de autenticacion.
- Retest aislado sin sesion en Chrome con `/h/los-cedros/login`: en `360x740`, `390x844` y `768x1024`, `bodyScrollWidth` y `docScrollWidth` coinciden con el viewport; no hay overflow horizontal.
- `360x740`: boton de password `44x44`, campo password `312x46`, `Recordarme` `312x44`, checkbox `22x22`, link de recuperacion `163x44`, boton dentro del campo.
- `390x844`: boton de password `44x44`, `Recordarme` `342x44`, checkbox `22x22`, link de recuperacion `163x44`, boton dentro del campo.
- `768x1024`: boton de password `44x44`, `Recordarme` `720x44`, checkbox `22x22`, link de recuperacion `163x44`, sin desborde.
- Interaccion validada: click en `Mostrar contrasena` cambia `#password` de `type=password` a `type=text`.
- `php -l /var/www/html/app/views/auth/login.php` sin errores.

### BUG-024 - Dashboard movil recorta horarios/acciones de la agenda

Severidad: P2
Estado: FIX VALIDADO
Modulo: Dashboard / Responsive mobile
URL: `/dashboard`

Caso reproducido:

- En `360px`, `clientWidth=360` y `scrollWidth=453`.
- En `390px`, `clientWidth=390` y `scrollWidth=453`.
- En la agenda, los horarios `.dm-ag .tm` quedan en `x=403/right=453`, fuera del viewport.
- Los chevrons de agenda tambien quedan fuera del viewport.

Impacto:

El dashboard movil puede mostrar agenda cortada o generar desplazamiento horizontal. Recepcion pierde parte de la informacion rapida de llegadas/salidas desde telefono.

Validacion del fix:

- Cambio aplicado en `src/app/views/dashboard/index.php`: las filas `.dm-ag` permiten que nombre/habitacion se encojan con `min-width:0`, la hora queda fija dentro del renglon y los enlaces/botones suben a area tactil >= 44px.
- Retest navegador en `360x740`: `bodyScrollWidth=360`, `docScrollWidth=360`; `.dm-ag.is-link` queda `left=23/right=329`, `.dm-ag .tm` queda `left=268/right=321`.
- Retest navegador en `390x844`: `bodyScrollWidth=390`, `docScrollWidth=390`; `.dm-ag .tm` queda `left=297/right=351`.
- Links `Ver todas` y `Ver agenda` miden `44px` de alto; acciones de dashboard miden `45px` de alto.

### BUG-025 - Varias pantallas autenticadas tienen targets tactiles menores a 32px

Severidad: P3
Estado: FIX VALIDADO PARCIAL LOCAL
Modulo: Responsive / Accesibilidad tactil
URL: Varias autenticadas

Caso reproducido:

- Inventario: acciones `.act-btn` de producto aprox. `28x38`.
- Habitaciones: filtros/controles moviles detectados con alto aprox. `14px`.
- Dashboard: links secundarios como `Ver todas` / `Ver agenda` aprox. `17-18px` de alto.
- Documentos: nombres/enlaces de documento aprox. `18px`; iconos de acciones aprox. `25x34`.

Impacto:

No bloquea el flujo, pero aumenta errores de toque en telefono/tablet y empeora accesibilidad. Se agrava por BUG-022, ya que el usuario tampoco puede hacer zoom.

Validacion del fix:

- Cambio aplicado en `dashboard`, `documentos`, `inventario` y `habitaciones`: acciones visibles y filtros operativos pasan a objetivo tactil de 44px en las pantallas autenticadas probadas.
- Retest `/inventario` en `390x844`: `.btn-inv`, `.btn-config-inv` e `.inv-search` miden `44px` de alto; `.act-btn` queda con `min-width/min-height=44px` en reglas computadas.
- Retest `/habitaciones` en `390x844`: `.hb-filter-trigger`, `.hb-chip`, `.filter-date`, `.filter-btn` y `.btn-action` miden `44px` de alto.
- Retest `/documentos` en `768x1024`: `.dc-btn` y `.dc-card-btn` miden `44px` de alto; las acciones desktop `.dc-action` conservan `44x44` cuando aplica breakpoint de escritorio.
- Login/auth queda cubierto por BUG-023, ahora con fix validado.

### BUG-026 - Centro documental en tablet recorta el hero por ancho interno mayor al viewport

Severidad: P3
Estado: FIX VALIDADO
Modulo: Documentos / Responsive tablet
URL: `/documentos`

Caso reproducido:

En viewport `768x1024`:

- `.main-content`: `width=768`, `overflow-x=hidden`.
- `.dc-shell`: `width=728`.
- `.dc-hero-section`: `width=853`, `right=869`.
- `.dc-title-lockup`: `width=853`, `right=869`.

Impacto:

El encabezado del centro documental puede verse cortado en tablets de 768px, afectando presentacion y lectura de contexto.

Validacion del fix:

- Cambio aplicado en `src/app/views/documentos/index.php`: hero/title con `min-width:0`, `max-width:100%`, filtros en dos columnas para tablet y listado tipo card hasta `900px`.
- Retest navegador en `768x1024`: `bodyScrollWidth=768`, `docScrollWidth=768`; `.dc-hero-section` queda `left=16/right=744` y `.dc-title-lockup` queda `left=16/right=744`.
- Filtros en tablet quedan dentro de viewport: `.dc-filter-form` `left=33/right=727`; botones `Filtrar/Limpiar` miden `44px` de alto.

### BUG-009 - Cambiar metodo de pago crea solicitudes de factura duplicadas

Severidad: P1
Estado: FIX VALIDADO
Modulo: Reservaciones / Cambiar metodo de pago / Facturacion
URL: `/reservaciones/ver/57`

Caso reproducido:

- Reservacion `57`, huesped `1065`, habitacion CORAL, total `$1,000.00`.
- Check-in efectivo con `requiere_factura=no`: no crea solicitud, correcto.
- Cambio a tarjeta con `requiere_factura=no`: crea solicitud interna `#233`, correcto.
- Segundo cambio a transferencia con `requiere_factura=no`: crea otra solicitud interna `#234`.

Resultado real:

La misma reservacion queda con dos solicitudes de facturacion de uso interno para el mismo monto/concepto.

Impacto:

Puede inflar pendientes de facturacion, duplicar trabajo administrativo y causar confusion sobre cual solicitud representa el metodo de pago vigente.

Validacion del fix:

- `Reservacion::crearSolicitudFactura()` reutiliza una solicitud activa de la misma reservacion/tipo antes de insertar.
- Nuevo cambio de metodo en `#57` a tarjeta actualizo la solicitud activa `#234` a `metodo_pago_principal=tarjeta`.
- No se creo `#235`; pagos/caja quedaron en tarjeta con referencia `QA-TARJ-57-FIX`.

### BUG-010 - Registros antiguos de caja/facturacion se asocian a nuevas reservaciones por `reservacion_id`

Severidad: P1
Estado: FIX VALIDADO / DATOS HISTORICOS A AUDITAR
Modulo: Facturacion / Caja / Reservaciones
URL: `/facturacion/ver/232`

Caso reproducido:

- Reservacion nueva `53`, creada el `2026-06-28 14:59:30`, total `$800.00`.
- Check-in correcto genera movimiento caja `#1541` por `$800.00` y solicitud factura `#232`.
- El detalle de factura muestra pagos por `$2,100.00`.

Resultado real:

El detalle suma un movimiento antiguo `#68` por `$1,300.00`, creado el `2026-03-02`, porque comparte `reservacion_id=53`. Tambien se encontraron asociaciones antiguas contra IDs `50`, `51`, `55`, `56` y futuros.

Impacto:

Facturacion y caja pueden mostrar pagos/facturas ajenos a una reservacion nueva, contaminando totales, auditoria y decisiones operativas.

Validacion del fix:

- Facturacion filtra solicitudes y pagos con `created_at >= reservaciones.created_at`.
- `/facturacion/ver/232` ya no muestra `$2,100.00` ni `$1,300.00`; solo el pago correcto `$800.00`.
- `/facturacion?buscar=51` ya no lista la factura vieja `#22`.

### BUG-004 - Extender noches post-check-in registra ingreso en Caja pero no actualiza pagos/saldo

Severidad: P0
Estado: FIX VALIDADO EN CASO NUEVO `#46`
Modulo: Reservaciones / Modificar dias / Caja / Pagos
URL: `/reservaciones/ver/40`

Caso reproducido:

- Reservacion `40`, huesped `1065`, habitacion MENTA.
- Check-in con pago exacto de `$600.00`.
- Se extendio de 1 noche a 2 noches, total nuevo `$1,200.00`.

Resultado real:

- Caja registro ingreso adicional `$600.00` (`movimientos_caja #1529`).
- `reservacion_pagos` quedo solo con `$600.00`.
- `reservaciones.monto_recibido` quedo en `$600.00`.
- UI muestra total `$1,200.00`, pagado `$600.00`, saldo `$600.00`.
- No se genero CxC para ese saldo.

Impacto:

Caja dice que ya entro el dinero adicional, pero la reservacion dice que aun falta. Esto puede provocar doble cobro, reportes contradictorios y cierre de caja no conciliable.

Validacion del fix:

- `#46`, ORO, check-in exacto `$800.00`.
- Extension 1 -> 2 noches: total/subtotal `$1,600.00`, pagado `$800.00`, saldo `$800.00`.
- Caja queda con un solo ingreso `#1533` por `$800.00`; no se crea cobro automatico adicional.
- Reduccion posterior sin sobrepago real no genero devolucion indebida.

### BUG-005 - Reducir noches post-check-in registra devolucion en Caja pero deja pagos y subtotal sobrecobrados

Severidad: P0
Estado: FIX VALIDADO EN CASO NUEVO `#47`
Modulo: Reservaciones / Modificar dias / Caja / Pagos
URL: `/reservaciones/ver/41`

Caso reproducido:

- Reservacion `41`, huesped `1065`, habitacion UVA.
- Check-in con pago exacto de `$1,200.00`.
- Se redujo de 2 noches a 1 noche, total nuevo `$600.00`.

Resultado real:

- Caja registro devolucion `$600.00` (`movimientos_caja #1531`), neto correcto `$600.00`.
- `reservacion_pagos` quedo en `$1,200.00`.
- `reservaciones.monto_recibido` quedo en `$1,200.00`.
- `reservacion_habitaciones.precio` quedo en `$1,200.00`.
- UI muestra total `$600.00`, pagado `$1,200.00`, saldo `$0.00`, pero subtotal de habitacion `$1,200.00`.

Impacto:

La caja netea bien, pero la ficha y los ledgers de pagos quedan sobrecobrados. Esto puede inflar reportes por reservacion/pagos y confundir a recepcion sobre lo realmente cobrado/devuelto.

Validacion del fix:

- La devolucion se calcula contra el pagado real: `max(0, pagadoAntes - nuevoTotal)`.
- `#47`, AMARILLO, check-in exacto `$2,000.00`.
- Reduccion 2 -> 1 noches: total/subtotal `$1,000.00`, pagos netos `$1,000.00`, `monto_recibido=1000.00`, caja neta `$1,000.00`.
- Caja: ingreso `#1534` por `$2,000.00`, devolucion `#1535` por `$1,000.00`.

### BUG-003 - Crear reservacion guarda en DB pero no redirige ni confirma correctamente

Severidad: P1
Estado: FIX VALIDADO EN CASO NUEVO `#51`
Modulo: Reservaciones / Crear
URL: `/reservaciones/crear?huesped_id=1065`

Resultado real:

- Reservacion `39` se creo en DB, pero la UI termino en `404 - Pagina No Encontrada` en `/reservaciones/guardar`.
- En repeticiones, reservaciones `40`, `41`, `42` y `43` tambien se crearon, pero la UI se quedo en el formulario sin redireccion clara ni confirmacion confiable.

Impacto:

El usuario puede creer que la reservacion no se guardo, reintentar y crear duplicados.

Validacion del fix:

- El formulario conserva `action`, `method`, CSRF y campos, pero el submit confirmado se envia por AJAX para evitar la navegacion nativa duplicada a `/reservaciones/guardar`.
- `guardarAction()` responde JSON con `redirect` cuando detecta `X-Requested-With: XMLHttpRequest`.
- Reservacion `#51`, AMBAR, 2026-08-07 a 2026-08-08: creada en DB con total `$600.00`.
- UI quedo estable en `/reservaciones/ver/51` despues de esperar 10s.
- Logs: `POST /reservaciones/guardar` 200 JSON, luego `GET /reservaciones/ver/51` 200; sin `GET /reservaciones/guardar` 404 en el flujo validado.

### BUG-006 - Modificar dias antes de check-in deja subtotal por habitacion desactualizado

Severidad: P1
Estado: FIX VALIDADO EN CASO NUEVO `#45`
Modulo: Reservaciones / Modificar dias / Detalle de habitaciones
URL: `/reservaciones/ver/42`, `/reservaciones/ver/43`

Casos reproducidos:

- Reservacion `42`, PURPURA, sin pagos: se extendio de 1 a 2 noches.
- Reservacion `43`, VIOLETA, sin pagos: se redujo de 2 a 1 noche.
- Reservacion `44`, LIMON, con anticipo `$300.00`: se extendio de 1 a 2 noches.

Resultado real:

- `#42`: `precio_total=1200.00`, saldo `$1,200.00`, sin pagos/caja, pero `reservacion_habitaciones.precio=600.00` y subtotal visible `$600.00`.
- `#43`: `precio_total=600.00`, saldo `$600.00`, sin pagos/caja, pero `reservacion_habitaciones.precio=1200.00` y subtotal visible `$1,200.00`.
- `#44`: `precio_total=1200.00`, abono/caja `$300.00`, saldo `$900.00`, pero `reservacion_habitaciones.precio=600.00` y subtotal visible `$600.00`.

Impacto:

No corrompe caja en este caso, pero deja persistido un subtotal anterior por habitacion. Puede afectar reportes, tickets/cotizaciones, auditoria y lectura operativa.

Validacion del fix:

- `Reservacion::modificarFechaSalida()` actualiza ahora el subtotal de `reservacion_habitaciones.precio` con el desglose recalculado.
- Reserva QA nueva `#45`, MOKA, sin pagos/caja.
- Extension 1 -> 2 noches: DB y UI quedan en `$2,000.00` para total y subtotal.
- Reduccion 2 -> 1 noche: DB y UI quedan en `$1,000.00` para total y subtotal.
- Caja, abonos y pagos permanecen en `0`.

### BUG-007 - Check-out se puede habilitar aunque la ficha muestra saldo pendiente

Severidad: P1
Estado: FIX VALIDADO EN CASOS `#40` Y `#47`
Modulo: Reservaciones / Check-out
URL: `/reservaciones/ver/40`

Caso reproducido:

- Reservacion `40` muestra total `$1,200.00`, pagado `$600.00`, saldo `$600.00`.
- Al abrir `Registrar check-out` y seleccionar MENTA, el boton `Confirmar Check-out` queda habilitado.
- Se cancelo el modal, no se confirmo la salida.

Resultado real:

El modal no muestra advertencia de saldo ni bloquea la confirmacion antes de habilitar check-out.

Impacto:

Riesgo de cerrar una estancia que el propio sistema presenta como pendiente de pago. Agrava BUG-004.

Validacion del fix:

- La vista de detalle oculta acciones de check-out cuando el saldo calculado es positivo.
- `Reservacion::checkOut()` y `Reservacion::checkOutParcial()` bloquean salidas con saldo pendiente.
- Las rutas normal/rapida validan saldo antes de ejecutar check-out y solo recogen llaves/remotos si el cierre fue exitoso.
- `#40`: saldo `$600.00`; no muestra acciones visibles de check-out; prueba backend `checkOut(40)` devuelve `false` y conserva `estado=checked_in`, `hora_salida=NULL`.
- `#47`: saldo `$0.00`; la UI conserva los botones `Registrar check-out` y `Check-out`.

### BUG-008 - Detalle de reservacion genera warnings PHP por variables indefinidas

Severidad: P2
Estado: FIX VALIDADO EN CASO `#51`
Modulo: Reservaciones / Detalle
URL: `/reservaciones/ver/51`

Resultado real:

Al abrir la ficha de la reservacion `#51`, la UI carga, pero el contenedor app registra warnings en `app/views/reservaciones/ver.php`:

- `$remotos_info` indefinida en lineas `4534` y `4654`, seguida de `foreach()` sobre `null`.
- `$noches` indefinida en lineas `5815` y `5953`.

Impacto:

No bloquea la operacion en local, pero ensucia logs y puede ocultar errores reales. En entornos con `display_errors` activo podria mostrarse al usuario.

Validacion del fix:

- La vista inicializa `$remotos_info` como arreglo y reutiliza `$rdNoches` para definir `$noches`.
- `php -l /var/www/html/app/views/reservaciones/ver.php` sin errores.
- Re-test `/reservaciones/ver/51`: UI estable, body sin warnings y logs nuevos sin warnings de `$remotos_info` ni `$noches`.

### BUG-001 - Login general puede mostrar branding Medisoft con datos de Los Cedros

Severidad: P1
Estado: FIX VALIDADO
Modulo: Auth / Dashboard / Layout hotelero
URL: `/dashboard`, `/habitaciones`

Resultado real:

La UI mostro datos de Los Cedros, pero encabezado/titulo como `Medisoft Hoteles`. Al entrar por `/h/los-cedros/login`, el contexto se corrigio.

Impacto:

Riesgo de confusion de tenant/identidad. Puede hacer que el usuario opere datos de un hotel mientras la UI indica otra identidad.

Validacion del fix:

- El login general ahora resuelve contexto hotelero activo desde `hotel_usuarios`: para `admin` con cookie previa `maximiliano`, el contexto cae correctamente a `los-cedros`.
- `POST /login/authenticate` redirige a `/dashboard`.
- Dashboard posterior: `Dashboard - Los Cedros`, `MEDISOFT_CONTEXT hotel_id=1`, `usuario_id=1` y branding offline `Los Cedros`.
- Se uso password temporal local para la prueba y se restauro el hash original; `CodexTest12345` quedo invalido al finalizar.

### BUG-002 - Logout desde contexto Los Cedros redirige a login de Maximiliano

Severidad: P1
Estado: FIX VALIDADO
Modulo: Auth / Logout / Contexto hotelero
URL: `/dashboard` -> `/logout`

Resultado real:

Tras cerrar sesion desde contexto con datos de Los Cedros, la app redirigio a `/h/maximiliano/login`.

Impacto:

Riesgo de contexto hotelero equivocado y confusion operacional al cambiar de hotel/sesion.

Validacion del fix:

- Con sesion activa Los Cedros (`hotel_id=1`) y cookie previa `medisoft_last_hotel_slug=maximiliano`, `POST /logout` devuelve `HTTP/1.1 303 See Other`.
- `Location`: `http://localhost:8080/h/los-cedros/login`.
- La respuesta no contiene `maximiliano` y limpia la cookie `PHPSESSID`.

## Casos aprobados relevantes

- Login scoped Los Cedros por `/h/los-cedros/login`.
- Multi-hotel scoped con `adminmax`: Los Cedros, Hotel Demo SaaS y Maximiliano conservan contexto, branding y logout correcto.
- Login scoped negativo: usuarios no vinculados no quedan autenticados en hoteles ajenos.
- Panel SaaS: listado y detalle de Los Cedros.
- Dashboard Los Cedros.
- Habitaciones listado/filtros.
- Huespedes listado/busqueda.
- Creacion de huesped QA `1065`.
- Listado/calendario de reservaciones.
- Detalle de reservacion.
- Check-in normal de reservacion `39`.
- Check-out normal de reservacion `39`.
- Caja dashboard y movimiento de hospedaje de reservacion `39`.
- Caja manual: ingreso `#1536` por `$12.34`, gasto `#1537` por `$4.56`, ambos en corte `#229`; montos `0` y negativos no insertan movimientos.
- Facturacion: listado/filtros cargan; check-in con factura crea solicitud cliente `#232`; RFC invalido no persiste; datos fiscales validos pasan a `en_proceso`; completar factura guarda folio `QA-FAC-232`; efectivo sin factura no crea solicitud; cambio a tarjeta sin factura crea solicitud interna; en-proceso/cancelacion funcionan.
- Facturacion multi-hotel: sesion Maximiliano no puede ver solicitud `#232` de Los Cedros por URL directa.
- CxC: reservacion `#44` genera CxC `#3` por `$900.00`; sobrecobro y referencia duplicada se bloquean; cobro parcial `#15` crea Caja `#1548`; reversion `#16` crea Caja `#1549`; saldo final vuelve a `$900.00`.
- CxP: compra recibida `#13` genera CxP `#3` por `$31.25`; sobrepago y referencia duplicada se bloquean; pago parcial `#13` crea Caja `#1550`; reversion `#14` crea Caja `#1551`; saldo final vuelve a `$31.25`.
- CxC/CxP multi-hotel: sesion Maximiliano no puede ver CxC/CxP `#3` de Los Cedros ni generar cuentas usando reservacion `#44` o compra `#13`.
- Documentos: listado y formulario contextual cargan con CSRF; documento `#18` se sube a huesped `#1065`, descarga/preview correctos, metadata editable, archivado/restaurado correcto.
- Documentos: extension `.php` se rechaza sin crear DB ni storage; baja logica de documento `#19` bloquea descarga y conserva ficha consultable.
- Documentos: documento `#21` se vincula a CxP `#3` y aparece en `/cuentas-por-pagar/3`.
- Documentos multi-hotel: Maximiliano no puede ver/descargar documentos `#18/#21` de Los Cedros ni vincular documentos a `huesped #1065`.
- Reportes: indice `/reportes` carga con contexto correcto para Los Cedros y Maximiliano.
- Reportes: ingresos/gastos del dia coinciden con DB en ambos hoteles; Los Cedros `$1,922.34` ingresos, `$114.56` gastos, utilidad `$1,807.78`; Maximiliano `$12,600.00` ingresos, `$0.00` gastos.
- Reportes: gerencial diario y ejecutivo coinciden con DB en finanzas, habitaciones, caja, facturacion, inventario, agenda y auditoria.
- Reportes: AJAX `datos-grafica` con header `X-Requested-With` responde JSON correcto; sin header redirige a `/reportes`.
- Reportes: PDF gerencial y PDF de ingresos totales generan links seguros; el historial respeta hotel activo y bloquea descarga cruzada de otro hotel.
- Reportes: paginas secundarias de limpieza, mantenimiento programado, procedencia, habitaciones rentables y mantenimiento cargan sin 500 en pantalla.
- Reportes fix: selector por usuario filtra por hotel activo; usuario cross-hotel `#22` se bloquea sin crear link; usuario valido `#1` genera PDF/link `#19`.
- Reportes fix: PDFs `ingresos-gastos`, `procedencia` y `habitaciones-rentables` generan `application/pdf` y links `#22`, `#20`, `#21`.
- Reportes fix: ocupacion diario/semanal/mensual, estancia y ranking-estados responden `200`.
- Responsive/login: probado visualmente en `360x740`, `390x844`, `768x1024` y `1366x768`; no hay overflow horizontal en login.
- Responsive/autenticado estatico: 22 rutas principales responden `200` con contexto Los Cedros; listados densos usan tarjetas moviles o scroll controlado, y formularios largos tienen breakpoints a una columna.
- Responsive/autenticado visual: sidebar movil abre/cierra con overlay; scroll real ocurre en `.main-content`; header movil se oculta al bajar y reaparece al subir; modal de ingreso de Caja abre en 390px sin overflow horizontal y se cierra sin enviar formulario.
- PWA smoke: POST autenticado a `/api/sync` responde `423 Locked` con JSON `sync_temporarily_disabled` y `pending_operations_preserved=true`.
- Login general con cookie previa de otro hotel queda en Los Cedros.
- Logout desde Los Cedros con cookie previa de otro hotel redirige a `/h/los-cedros/login`.
- Aislamiento por URL directa: detalles de habitaciones/reservaciones de otro hotel no se exponen con sesion de hotel distinto.
- Ruta interna sin sesion redirige a login.

## Datos QA creados principales

| Registro | ID | Estado | Notas |
|---|---:|---|---|
| Huesped QA | 1065 | Creado | `QA-FUNC-20260628-JUNTOS Huesped Principal` |
| Reservacion AMBAR | 39 | Checked-out | Flujo check-in/check-out normal aprobado; movimiento caja `#1527` |
| Reservacion MENTA | 40 | Checked-in | Usada para BUG-004; movimientos caja `#1528`, `#1529` |
| Reservacion UVA | 41 | Checked-in | Usada para BUG-005; movimientos caja `#1530`, `#1531` |
| Reservacion PURPURA | 42 | Confirmada | Usada para BUG-006; sin pagos/caja; subtotal persistido viejo `$600.00` |
| Reservacion VIOLETA | 43 | Confirmada | Usada para BUG-006; sin pagos/caja; subtotal persistido viejo `$1,200.00` |
| Reservacion LIMON | 44 | Confirmada | Usada para BUG-006; abono/caja `$300.00`; saldo `$900.00`; subtotal persistido viejo `$600.00` |
| Reservacion MOKA | 45 | Confirmada | Validacion fix BUG-006; total/subtotal sincronizados al extender y reducir; sin pagos/caja |
| Reservacion ORO | 46 | Checked-in | Validacion fix BUG-004; extension deja saldo sin caja extra; reduccion sin sobrepago no genera devolucion |
| Reservacion AMARILLO | 47 | Checked-in | Validacion fix BUG-005; devolucion y pagos netos conciliados |
| Reservaciones AMBAR | 48, 49, 50 | Confirmadas | Reproducciones/retake de BUG-003 antes del fix final; guardan en DB pero la UI caia o quedaba en `/reservaciones/guardar` |
| Reservacion AMBAR | 51 | Confirmada | Validacion fix BUG-003; UI queda en `/reservaciones/ver/51`, logs sin `GET /reservaciones/guardar` |
| Reservacion VINO / factura cliente | 53 / 232 | Checked-in / completada | Solicitud `#232` completada con folio `QA-FAC-232`; BUG-010 validado, detalle ya solo muestra `$800.00` |
| Reservaciones salto IDs | 55, 56 | Confirmadas | Evidencia de IDs contaminados por movimientos antiguos; no usadas para flujo limpio |
| Reservacion CORAL / facturacion interna | 57 / 233, 234 | Checked-in | `#233` cancelada; `#234` pendiente y actualizada sin crear nuevas solicitudes; BUG-009 validado |
| Movimiento caja ingreso | 1536 | Creado | `QA-FUNC-20260628-CAJA-ING-082102`, corte `#229`, efectivo `$12.34`, categoria `Otros` |
| Movimiento caja gasto | 1537 | Creado | `QA-FUNC-20260628-CAJA-GAS-082102`, corte `#229`, efectivo `$4.56`, categoria `Despensa` |
| Producto inventario litros | 43 | Activo | `QAINV0628A`, unidad `litro`, stock final `8.25`; BUG-011 validado con movimientos `#1142` y `#1143`; BUG-012 validado con `#1145` y `#1146`; BUG-015 validado con movimientos `#1147` y `#1148`; conserva evidencia historica de BUG-013 |
| Producto inventario inicial | 44 | Activo | `QAINV0628B`, stock inicial `3.00`; movimiento inicial `#1141` sin usuario |
| Producto inventario usuario | 45 | Activo | `QAINV0628C`, stock inicial `2.00`; movimiento inicial `#1144` con `usuario_id=1` |
| Producto inventario descripcion | 46 | Activo | `QAINV0628D`; descripcion creada/editada persistida; BUG-013 validado |
| Compra inventario | 13 / 24 / 1140 | Recibida | `QA-COMP-INV-0628A`; borrador no movio stock, recepcion sumo `2.50`, segundo recibir no duplico |
| CxC reservacion 44 | 3 / 14, 15, 16 / 1548, 1549 | Pendiente | Total/saldo final `$900.00`; cobro `$100.00` revertido; neto Caja `0.00`; negativos de sobrecobro, duplicado, doble reversion y cruce hotel 4 aprobados |
| CxP compra 13 | 3 / 13, 14 / 1550, 1551 | Pendiente | Total/saldo final `$31.25`; pago `$10.00` revertido; neto Caja `0.00`; negativos de sobrepago, duplicado, doble reversion y cruce hotel 4 aprobados |
| Documento huesped | 18 / 13 | Activo | `QA-DOC-20260628-HUESPED-IMG-EDIT`; vinculado a `huesped #1065`; storage privado `0640`; descarga/preview/metadata/archivar/restaurar aprobados |
| Documento lifecycle | 19 | Eliminado | `QA-DOC-20260628-LIFECYCLE`; usado para baja logica; descarga bloqueada tras eliminar |
| Documento CxP | 21 / 14 | Activo | `QA-DOC-20260628-CXP3`; vinculado a `cuenta_por_pagar #3`; visible en ficha de CxP |
| Reporte gerencial Los Cedros | 15 | Link activo | `Reporte_Gerencial_Diario_20260628_los_cedros.pdf`, `tamano_bytes=10759`; descarga interna bloqueada desde Maximiliano |
| Reporte gerencial Maximiliano | 16 | Link activo | `Reporte_Gerencial_Diario_20260628_hotel_maximiliano.pdf`, `tamano_bytes=10747`; visible solo en historial de Maximiliano |
| Reporte ingresos totales Los Cedros | 17 | Link activo | `Reporte_Ingresos_Totales_los_cedros_20260628_170236.pdf`, `tamano_bytes=16955` |
| Reporte usuario cross-hotel | 18 | Link activo / bug | `Reporte_Movimientos_los_cedros_20260628_170445.pdf`, parametros `usuario_id=22`, `usuario_nombre=Admin Demo SaaS`; evidencia BUG-017 |
| Reporte usuario valido Los Cedros | 19 | Link activo / fix | `Reporte_Movimientos_los_cedros_20260628_181057.pdf`, `tamano_bytes=14874`; evidencia BUG-017 fix |
| Reporte procedencia Los Cedros | 20 | Link activo / fix | `Reporte_Procedencia_los_cedros_20260628_181057.pdf`, `tamano_bytes=8667`; evidencia BUG-019 fix |
| Reporte habitaciones rentables Los Cedros | 21 | Link activo / fix | `Reporte_Habitaciones_Rentables_los_cedros_20260628_181057.pdf`, `tamano_bytes=10012`; evidencia BUG-019 fix |
| Reporte ingresos/gastos Los Cedros | 22 | Link activo / fix | `Ingresos_Gastos_los_cedros_20260628_181057.pdf`, `tamano_bytes=13258`; evidencia BUG-018 fix |

## Pendientes de la siguiente bateria

- Modificar dias con varias habitaciones.
- Revalidar desde listado/habitaciones que el mensaje de saldo pendiente sea claro si se intenta check-out rapido.
- Inventario/check-in con consumo automatico despues del fix de movimientos manuales.
- Responsive/mobile: revalidar modal de check-in cuando exista una reservacion con boton visible.
- Seguridad funcional/aplicativa: abrir fase separada.
