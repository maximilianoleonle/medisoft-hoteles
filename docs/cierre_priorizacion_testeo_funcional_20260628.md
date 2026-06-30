# Cierre y priorizacion del testeo funcional guiado - 2026-06-28

Este documento cierra la bateria funcional guiada y ordena los issues abiertos para entrar a correccion/retest sin seguir agregando casos generales.

Alcance ya cubierto:

- Acceso, contexto multi-hotel, panel SaaS, dashboard, habitaciones, huespedes, reservaciones, check-in/check-out, caja, facturacion, inventario/compras, CxC/CxP, documentos, reportes, responsive/mobile y smoke PWA de `/api/sync`.
- Caso critico del usuario: modificar noches despues de check-in y pago.
- Seguridad queda fuera de este ciclo, por decision del usuario.

## Estado final

| Area | Resultado |
|---|---|
| Reservaciones / pagos / caja | Aprobado en casos probados, incluyendo fixes ya validados para saldo post-extension/reduccion. |
| Facturacion | Aprobado en casos probados, con fixes validados. |
| Inventario / compras | Aprobado en casos probados, con fixes validados. |
| CxC / CxP con caja | Aprobado en casos probados, incluyendo negativos y reversiones. |
| Documentos/Tareas | Aprobado en casos probados, incluyendo vinculacion de documentos a tarea `#5`. |
| Reportes | Aprobado tras correccion: BUG-017 a BUG-021 con fix validado en Los Cedros. |
| Responsive/mobile | Parcial: navegacion base pasa; BUG-022, BUG-023, BUG-024, BUG-025 y BUG-026 quedaron con fix validado. |
| PWA smoke `/api/sync` | Aprobado: `HTTP 423` y JSON `sync_temporarily_disabled`. |
| Regresion final post-fixes | Aprobado: `32/32` checks funcionales pasaron en Los Cedros y Maximiliano, sin errores PHP/SQL detectados ni logs nuevos. |

## Cola recomendada

| Orden | ID | Prioridad operativa | Motivo | Decision antes de corregir |
|---:|---|---|---|---|
| 1 | BUG-017 | P1 bloqueante multi-hotel | Reporte por usuario exponia usuarios de otros hoteles y permitia generar PDF incoherente. | FIX VALIDADO: selector/export filtran por `hotel_usuarios.hotel_id`; usuario externo redirige sin link nuevo. |
| 2 | BUG-020 | P2 bloqueante de modulo | `/reportes/ocupacion` estaba inutilizable y provocaba fatal por consulta incompatible con `ONLY_FULL_GROUP_BY`. | FIX VALIDADO: ocupacion diario/semanal/mensual responde `200`. |
| 3 | BUG-019 | P2 500 expuesto | Exportaciones visibles de procedencia/habitaciones rentables apuntaban a metodos inexistentes. | FIX VALIDADO: ambos exports generan PDF y link seguro. |
| 4 | BUG-021 | P2 500 expuesto | Rutas de estancia/ranking-estados apuntaban a vistas inexistentes. | FIX VALIDADO: ambas rutas renderizan `200`. |
| 5 | BUG-018 | P2 export principal rota | PDF ingresos/gastos fallaba por logo PNG con alpha y dependencia TCPDF/GD/Imagick. | FIX VALIDADO: PDF genera `application/pdf` con fallback si no hay GD/Imagick. |
| 6 | BUG-016 | P2 contrato de datos | Documentos de tareas fallaban por enum DB sin `tarea`. | FIX VALIDADO: migracion `20260630_001_documento_entidades_tarea_enum.sql`; documento `#22` vinculado a tarea `#5`. |
| 7 | BUG-024 | P2 mobile operativo | Dashboard movil recorta agenda y genera overflow horizontal. | FIX VALIDADO: dashboard 360/390 sin scroll horizontal global y hora de agenda dentro del renglon. |
| 8 | BUG-022 | P2 accesibilidad global | App bloqueaba zoom/pinch desde meta viewport y `pwa.js`. | FIX VALIDADO: viewport accesible en login/layout/offline, sin lock JS de zoom; `/api/sync` sigue en `423 sync_temporarily_disabled`. |
| 9 | BUG-026 | P3 visual tablet | Centro documental recorta hero en tablet 768px. | FIX VALIDADO: `/documentos` 768x1024 sin recorte de hero/title/filtros. |
| 10 | BUG-023 | P3 login mobile | Controles secundarios de login tenian area tactil pequena. | FIX VALIDADO: login 360/390/768 sin overflow; password toggle `44x44`, `Recordarme` `44px`, checkbox `22x22`, link recuperacion `44px`; sin cambiar flujo/auth. |
| 11 | BUG-025 | P3 accesibilidad tactil | Varios targets autenticados menores a 32px. | FIX VALIDADO PARCIAL LOCAL: dashboard/documentos/inventario/habitaciones con targets de 44px; login cubierto por BUG-023. |

## Bloques de correccion propuestos

### Bloque A - Reportes funcionales

IDs: BUG-017, BUG-018, BUG-019, BUG-020, BUG-021.

Objetivo: que el modulo Reportes no exponga cruces multi-hotel ni rutas/exportaciones con 500.

Riesgo: medio/alto porque Reportes toca calculos y exportaciones. Debe ser una correccion controlada y con retest amplio de los reportes que ya pasan.

Retest minimo:

- Los Cedros y Maximiliano: `/reportes`, `/reportes/ingresos-gastos`, `/reportes/gerencial-diario`, `/reportes/ejecutivo`.
- Selector de usuario solo muestra usuarios del hotel activo.
- Export por usuario extranjero se bloquea o redirige sin crear `reporte_links`.
- Export por usuario valido del hotel activo crea PDF correcto.
- PDF ingresos/gastos responde `application/pdf` y crea link seguro.
- Procedencia y habitaciones rentables no devuelven 500: generan PDF o no ofrecen export roto.
- Ocupacion diario/semanal/mensual responde 200.
- Estancia/ranking-estados responden 200 o dejan de aparecer como disponibles.
- Revalidar links seguros cross-hotel.

### Bloque B - Documentos/Tareas

ID: BUG-016.

Objetivo: resolver la accion visible `Vincular documento` en tareas.

Decision:

- Autorizacion explicita recibida para migracion/ajuste DB.
- Se agrego `tarea` a `documento_entidades.entidad_tipo` mediante `20260630_001_documento_entidades_tarea_enum.sql`.

Retest minimo:

- Subir archivo valido a tarea `#5` o nueva tarea QA. VALIDADO con documento `#22`.
- Confirmar documento creado y vinculo creado. VALIDADO: `entidad_tipo=tarea`, `entidad_id=5`, `relacion=evidencia_qa`.
- Confirmar que la ficha de tarea muestra el documento. VALIDADO en `/tareas/5`.
- Confirmar aislamiento multi-hotel. Cubierto por DOC-10 y por REG-FINAL-02 con documento `#22`.

### Bloque C - Responsive operativo

IDs originales: BUG-024, BUG-026, BUG-023, BUG-025.

Objetivo: quitar cortes visuales y mejorar areas tactiles sin tocar logica operativa.

Estado:

- BUG-024 FIX VALIDADO: `/dashboard` en 360x740 y 390x844 mantiene `scrollWidth == clientWidth`; la hora de agenda queda dentro del viewport.
- BUG-026 FIX VALIDADO: `/documentos` en 768x1024 mantiene hero/title/filtros dentro del viewport.
- BUG-025 FIX VALIDADO PARCIAL LOCAL: dashboard, documentos, inventario y habitaciones tienen controles principales de 44px en los retests.
- BUG-023 FIX VALIDADO: login en 360x740, 390x844 y 768x1024 mantiene `scrollWidth == clientWidth`; boton de password `44x44`, label `Recordarme` `44px`, checkbox `22x22` y link de recuperacion `44px`.

Retest minimo:

- `/dashboard` en `360x740` y `390x844`: `scrollWidth == clientWidth` o scroll horizontal intencional aislado; agenda visible. VALIDADO.
- `/documentos` en `768x1024`: hero/title dentro del viewport. VALIDADO.
- Login en `360x740` y `390x844`: boton de password, checkbox y link con area tactil comoda. VALIDADO.
- Inventario, habitaciones, dashboard y documentos: controles principales con area tactil al menos `32px`, ideal `44px`. VALIDADO en pantallas autenticadas.

### Bloque D - Zoom mobile/PWA

ID: BUG-022.

Objetivo: permitir zoom/pinch o retirar bloqueos innecesarios.

Decision requerida:

- Autorizacion explicita recibida. Se tocaron solo `pwa.js`, `offline.html` y los meta viewport de login/layout para retirar bloqueo de zoom.
- No se tocaron `service-worker.js`, `offline-data.js`, `reservaciones-offline.js`, IndexedDB, cache names ni `/api/sync`.

Retest minimo:

- Login y layout autenticado permiten zoom/pinch en movil real o emulado. VALIDADO por viewport sin scale lock y sin listeners `gesture*`/bloqueo touch global.
- PWA sigue cargando sin errores JS. VALIDADO: `/dashboard` 390x844 carga con viewport accesible y `pwa.js` servido sin `lockMobileZoomGestures`.
- `/api/sync` sigue respondiendo `423 sync_temporarily_disabled`. VALIDADO con POST autenticado y JSON esperado.

## Recomendacion inmediata

Bloques A, B, C y D quedan cerrados con fix validado en los casos probados. La regresion final post-fixes tambien queda cerrada:

- `32/32` checks pasaron sobre rutas operativas de Los Cedros, documentos/tareas de Maximiliano, descarga preview de documento `#22`, CxC/CxP, reportes y `/api/sync`.
- Logs post-regresion sin `PHP Warning`, `PHP Fatal`, `Fatal error`, `SQLSTATE`, `Uncaught`, `Undefined variable`, `ONLY_FULL_GROUP_BY` ni `Call to a member`.
- Validaciones tecnicas finales OK: `php -l` en archivos PHP tocados, `node --check src/public_html/js/pwa.js`, enum `documento_entidades.entidad_tipo` con `tarea`, migracion registrada y vinculo documento `#22` -> tarea `#5` vigente.

Siguiente fase recomendada:

- Abrir testeo de seguridad como ciclo separado.
- Mantener como regresiones futuras no bloqueantes: modificar dias con varias habitaciones, consumo automatico de inventario en check-in y modal de check-in movil cuando exista una reservacion con accion visible.

No quedan BUG funcionales abiertos en esta bateria.
