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
| Documentos base | Aprobado, excepto vinculacion a tareas. |
| Reportes | Aprobado tras correccion: BUG-017 a BUG-021 con fix validado en Los Cedros. |
| Responsive/mobile | Parcial: navegacion base pasa, pero hay 5 bugs abiertos de accesibilidad/overflow. |
| PWA smoke `/api/sync` | Aprobado: `HTTP 423` y JSON `sync_temporarily_disabled`. |

## Cola recomendada

| Orden | ID | Prioridad operativa | Motivo | Decision antes de corregir |
|---:|---|---|---|---|
| 1 | BUG-017 | P1 bloqueante multi-hotel | Reporte por usuario exponia usuarios de otros hoteles y permitia generar PDF incoherente. | FIX VALIDADO: selector/export filtran por `hotel_usuarios.hotel_id`; usuario externo redirige sin link nuevo. |
| 2 | BUG-020 | P2 bloqueante de modulo | `/reportes/ocupacion` estaba inutilizable y provocaba fatal por consulta incompatible con `ONLY_FULL_GROUP_BY`. | FIX VALIDADO: ocupacion diario/semanal/mensual responde `200`. |
| 3 | BUG-019 | P2 500 expuesto | Exportaciones visibles de procedencia/habitaciones rentables apuntaban a metodos inexistentes. | FIX VALIDADO: ambos exports generan PDF y link seguro. |
| 4 | BUG-021 | P2 500 expuesto | Rutas de estancia/ranking-estados apuntaban a vistas inexistentes. | FIX VALIDADO: ambas rutas renderizan `200`. |
| 5 | BUG-018 | P2 export principal rota | PDF ingresos/gastos fallaba por logo PNG con alpha y dependencia TCPDF/GD/Imagick. | FIX VALIDADO: PDF genera `application/pdf` con fallback si no hay GD/Imagick. |
| 6 | BUG-016 | P2 contrato de datos | Documentos de tareas fallan por enum DB sin `tarea`. | Requiere autorizacion explicita para migracion/contrato DB o retirar accion en UI. |
| 7 | BUG-024 | P2 mobile operativo | Dashboard movil recorta agenda y genera overflow horizontal. | Fix CSS/layout del dashboard y retest 360/390px. |
| 8 | BUG-022 | P2 accesibilidad global | App bloquea zoom/pinch desde meta viewport y `pwa.js`. | Requiere autorizacion explicita para tocar PWA/offline o limitar fix a vistas no PWA. |
| 9 | BUG-026 | P3 visual tablet | Centro documental recorta hero en tablet 768px. | Fix CSS local de documentos. |
| 10 | BUG-023 | P3 login mobile | Controles secundarios de login tienen area tactil pequena. | Toca auth/login; corregir solo UI sin cambiar flujo/auth. |
| 11 | BUG-025 | P3 accesibilidad tactil | Varios targets autenticados menores a 32px. | Correccion por pantallas, despues de dashboard/documentos. |

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

Decision requerida:

- Opcion 1: autorizar migracion/ajuste DB para incluir `tarea` en `documento_entidades.entidad_tipo`.
- Opcion 2: retirar/ocultar la accion de documentos en tareas hasta que exista contrato DB aprobado.

Retest minimo si se autoriza DB:

- Subir JPG valido a tarea `#5` o nueva tarea QA.
- Confirmar documento creado, vinculo creado y descarga/preview funcionando.
- Confirmar sin archivo huerfano si falla validacion.
- Confirmar aislamiento multi-hotel.

### Bloque C - Responsive operativo

IDs: BUG-024, BUG-026, BUG-023, BUG-025.

Objetivo: quitar cortes visuales y mejorar areas tactiles sin tocar logica operativa.

Retest minimo:

- `/dashboard` en `360x740` y `390x844`: `scrollWidth == clientWidth` o scroll horizontal intencional aislado; agenda visible.
- `/documentos` en `768x1024`: hero/title dentro del viewport.
- Login en `360x740` y `390x844`: boton de password, checkbox y link con area tactil comoda.
- Inventario, habitaciones, dashboard y documentos: controles principales con area tactil al menos `32px`, ideal `44px`.

### Bloque D - Zoom mobile/PWA

ID: BUG-022.

Objetivo: permitir zoom/pinch o retirar bloqueos innecesarios.

Decision requerida:

- Este bloque toca `pwa.js`/PWA y posiblemente meta viewport global. Debe hacerse solo con autorizacion explicita porque PWA/offline esta en zona critica.

Retest minimo:

- Login y layout autenticado permiten zoom/pinch en movil real o emulado.
- PWA sigue cargando sin errores JS.
- `/api/sync` sigue respondiendo `423 sync_temporarily_disabled`.

## Recomendacion inmediata

Bloque A de Reportes cerrado con fix validado. Entrar ahora al Bloque C responsive local, porque:

- Es el siguiente bloque que no requiere DB/migraciones ni PWA/offline.
- Agrupa problemas visibles de operacion mobile/tablet.
- Reduce friccion real para recepcion sin tocar logica de negocio.

Dejar BUG-016 y BUG-022 para cuando el usuario autorice explicitamente DB/migraciones y PWA/offline.
