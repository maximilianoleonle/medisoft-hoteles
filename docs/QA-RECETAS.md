# QA-RECETAS.md — Flujos de verificación por módulo

Recetas paso a paso para verificar cada módulo tras un cambio, sin redescubrir el flujo. La IA lee la receta del módulo tocado y la ejecuta en el navegador (localhost:8080).

## ⚡ Regla de alimentación

Cada flujo que se verifique de verdad deja aquí sus pasos exactos. Los ⬜ son huecos por confirmar en la primera ejecución real: **al ejecutarlos, se reemplazan por el paso confirmado en la misma sesión**. Si la UI cambia y una receta miente, se corrige al detectarlo. Nunca escribir pasos no ejecutados como si fueran hechos.

## Base común

- App: http://localhost:8080 (Docker arriba). Hotel semilla: **Los Cedros**.
- Usuarios QA: `claude_qa1` (general/nómina), `dueno_qa` (rol dueño remoto). ⬜ contraseñas: pedirlas a Maximiliano la primera vez y anotar aquí dónde viven (no anotar la contraseña).
- Verificación de dinero: SIEMPRE por script PDO en el contenedor, nunca mysql CLI (timezone). Patrón:
  `MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php -r "...PDO..."`
- Estado previo: correr la radiografía (`tools/radiografia.php`) para descartar migraciones pendientes antes de culpar al cambio.

## Reservaciones (crear → anticipo → check-in → checkout)

1. `/reservaciones` → botón crear (form activo: `crear.php`, jQuery).
2. Crear reservación con huésped nuevo y ⬜ (datos mínimos exactos del form).
3. Registrar **anticipo** desde ⬜ (pantalla/modal) → debe crear fila en `reservacion_abonos` ligada a caja (AnticipoService).
4. **Check-in**: el cobro debe ser saldo-aware (total − anticipo, sin doble cobro). En móvil el modal es wizard de 2 pasos (≤640px); `ver.php` tiene su propio wizard distinto.
5. **Paso 2 del check-in de `ver.php` (cobro tipo POS, rediseñado jul-2026)** — verificado en navegador: (a) 1 toque a una ficha → autollena el total, barra 100%, "Cobro completo"; (b) split: teclear $800 en tarjeta → "Falta $1,200" → tocar efectivo → rellena $1,200 (efectivo es el relleno automático); (c) saldo pendiente + efectivo $500 → barra 25%, "Se cobra una parte hoy", `permitir_saldo_pendiente=1`. **Invariante**: nunca coexisten "Cobro completo" + "paga solo una parte" marcado — al cubrir el total el checkbox se apaga solo (`calcularTotales`), y activarlo estando cubierto parte de cero para capturar el parcial real (`toggleSaldoPendienteCheckIn`). IDs de inputs y validación (`validarPagoCheckInWizard`) intactos. GOTCHA de prueba: `SALDO_RESERVACION_CHECKIN` puede ser 0 en reservas ya saldadas (#32869 quedó en 0) → para probar la lógica, forzar `totalReservacion=2000` en la consola. ⬜ falta confirmar el mismo rediseño en el modal de **check-in tardío** (`#modalCheckInTardio`, aún patrón viejo de chips).
6. Checkout → verificar por PDO que los movimientos de caja cuadran con lo cobrado.

## Caja (cortes y movimientos)

1. `/caja` → abrir corte (solo puede existir UN corte abierto por caja — probar doble apertura debe fallar).
2. Registrar un gasto y un ingreso → verificar que caen en el corte abierto.
3. Cerrar corte → un movimiento posterior NO debe caer en el corte cerrado.
4. Cifras del corte: validar por PDO contra `Caja/Reportes` (deben cuadrar exacto).

## Nómina (v2)

1. Login `claude_qa1` en Los Cedros → `/nomina` (fachada: subnav fija, ficha con 4 tabs, toggles Pagos/Pre-nómina).
2. ⬜ receta completa de pre-nómina → período → revisión (reconstruir de la receta original de la fase NP al primer uso).
3. **PAGAR se hace en la pantalla VIEJA** (trabajadores/nomina), no en /nomina.
4. Invariante a verificar si se tocó crédito: crédito = bruto − ledger absorbido; anular/reabrir no debe permitir doble pago.

## Modo dueño

1. Login `dueno_qa` → `/dueno`.
2. Debe mostrar score determinista del hotel y ⬜ (secciones exactas del tablero).
3. Si se tocó push: verificar que las notificaciones ruteen por clave de rol `dueno_remoto`.

## Copiloto (acciones por chat)

1. Abrir widget del Copiloto (exento del difuminador de sidebar).
2. Pedir una acción: "registra un gasto de 450 de plomería en Mantenimiento" (exige caja abierta) / "bloquea la 204 por pintura" / "desbloquea la 204" / "crea un cupón de 10% para agosto" / "págale 500 al proveedor X".
3. Debe responder con **propuesta** de campos fijos → confirmar → la ejecuta el motor de pantalla estándar (verificar el registro resultante igual que en la receta del módulo destino).
4. Rechazar una propuesta también es caso de prueba: no debe ejecutar nada.
5. Reservación campo a campo: "quiero hacer una reservación" → debe ir pidiendo fechas → habitación (rechaza ocupadas) → nombre → teléfono si es nuevo → confirmar registra al huésped y da botón al formulario prellenado. **Verificado en navegador (jul-2026)**: wizard completo con "mañana por 2 noches" → "cualquiera" → "sin nombre" → botón abre `reservaciones/crear` con fechas puestas en los inputs. ⬜ confirmar prellenado de habitación/huésped (mismo mecanismo de query) y "cancelar" a media captura desde el widget.
6. Multi-turno IA: pregunta abierta ("dame un consejo para temporada baja") y luego "¿y de esas ideas cuál primero?" → debe retomar el hilo (verificado por CLI contra el API; ⬜ confirmar desde el widget).
6b. Tool use IA: pregunta de rango arbitrario que NO case con intents — ojo: "caja"/"ganancias del mes" las atrapa el router determinista; usar algo como "del 1 al 10 de mayo, ¿el total que entró y el neto?" → fuente=ia con cifras EXACTAS de la BD (verificado por CLI jul-2026: llamó dinero_entre_fechas y respondió $5,500/$4,700 sembrados). También: "¿tengo lugar del X al Y?" (disponibilidad) y "¿quién es <huésped>?" (búsqueda).
7. Pregunta de datos a media captura ("¿cuánto tengo en caja?") → responde normal y el wizard sigue donde iba.
8. **Eliminar pregunta (✕ en la burbuja, hover en PC)** — verificado en navegador (jul-2026): borra pregunta+respuesta del hilo y, si era la última, restaura el contexto anterior — probado con el wizard: eliminar la respuesta de fechas regresa al paso de fechas y la siguiente frase se interpreta como fechas. Borrar el mensaje NO deshace acciones ya ejecutadas.
9. GOTCHA de QA por widget: los params JSON (`historial`, `flujo`) viajan por POST y el `sanitize()` global los rompía — si el wizard "cae a IA sin razón", revisar el `html_entity_decode` del controller antes de culpar al servicio.
10. **Catálogo y sugerencias** — verificado en navegador (jul-2026): el saludo muestra el catálogo paginado (flechas ‹ › ciclan páginas, dots, "Tus frecuentes" primero en Los Cedros, "Acciones rápidas"/"Dinero"/"Tus bloques" según bloques); un chip plantilla ("💸 Registrar un gasto") RELLENA el input con la orden a medias y enfoca (no envía); tras responder "¿cómo voy de caja?" aparecen 3 sugerencias del tema dinero. Caso negativo por test: propuestas de acción no traen sugerencias.
11. **Respuestas posibles del wizard (chips)** — verificado en navegador (jul-2026): cada paso muestra sus opciones tocables — fechas: Hoy/Mañana/Del…al…/Cancelar; habitación: los TIPOS reales del hotel (Sencilla/Doble/Cuádruple en Los Cedros, aparta una libre del tipo) + Cualquiera; nombre: "🆕 Huésped no registrado" (→ "Va, lo registro como nuevo ¿cómo se llama?", el nombre va directo al alta sin buscar catálogo) + "📝 Capturarlo en el formulario"; teléfono: "Sin teléfono". Cancelar por chip corta el flujo. GOTCHA: el widget recorta sugerencias con slice — el tope debe ser ≥5 o el chip Cancelar desaparece.

## Áreas del hotel (habitaciones y áreas)

1. `/areas` (subnav Habitaciones · Áreas; sidebar dice "Habitaciones y áreas"). **Verificado en navegador (jul-2026)**: alta inline "Nueva área" (nombre+tipo catálogo+piso+descripción) → tarjeta con chip Disponible y KPIs por estado; Editar rellena el mismo form (hidden id); Pausar/Reactivar togglea `activa` (tarjeta opaca + chip "Pausada"). Nombre duplicado en el hotel debe rechazarse con mensaje digno.
2. Gates: ver = módulo `habitaciones`; gestionar = `can('habitaciones.edit')` (gerente/admin vía `habitaciones.all`; recepcionista NO ve botones de gestión — ⬜ confirmar con sesión recepcionista).
3. **Acciones por área (`/areas/{id}`, "Ver y operar")** — verificado en navegador (jul-2026): (a) Mandar a limpieza → chip "En limpieza" + tarea Pendiente en historial; (b) Marcar limpia SIN seleccionar personal ni "Sin registrar" → RECHAZADO (estado no cambia); (c) con trabajador → Disponible + tarea Completada + nota "Limpieza realizada por: X." + fila en `tarea_trabajadores`; (d) Reportar mantenimiento (motivo+tipo+prioridad) → "En mantenimiento" + registro `en_proceso` con `area_id` y `habitacion_id NULL`; (e) Finalizar → Disponible + completado con fecha_fin; (f) Cerrar → "Cerrada" (única acción Reabrir) → Reabrir → Disponible. Verificar filas por PDO en `tareas_operativas`/`mantenimientos_habitaciones` con `area_id`.
4. GOTCHA de QA en el pane: si `screenshot` se cuelga (pasó incluso en portrait) verificar por `get_page_text` + asserts JS; si un clic físico no dispara `onclick`, usar `dispatchEvent(new MouseEvent('click'))` o `requestSubmit()` vía javascript_tool.

## Limpieza / camaristas

1. Marcar habitación como limpia → debe EXIGIR quién limpió (personal obligatorio, contrato `personal_confirmado`/`trabajador_ids`/`sin_personal`).
2. Programar limpieza con fecha+personal → verificar fila en `tareas_operativas`.

## Mantenimiento Plus

1. Crear mantenimiento con fotos y costo → el costo debe generar gasto en caja.
2. Gotcha del validador en campos `*_costo`; mantenimiento sin habitación (área/activo) es válido (`habitacion_id` NULL).

## Dinero E2E (regresión global)

- Suite: `MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php /var/www/html/tests/run.php` (recrea la BD de prueba; filtrar por nombre para iterar rápido).
- Referencia histórica: la verificación E2E de dinero del ciclo jul-2026 cerró con 22 PASS — si algo de dinero baja de ahí, es regresión.
