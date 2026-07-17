# CLAUDE.md — Mapa vivo del sistema (LEER SIEMPRE, se inyecta solo)

Medisoft Hoteles: SaaS hotelero white-label multihotel. PHP 8.2 puro (MVC propio) + MySQL 8 + Tailwind precompilado + jQuery. Sin framework. Idioma de trabajo: español.

## ⚡ REGLA DE ALIMENTACIÓN (obligatoria)

Este archivo es un **documento vivo**. Al cerrar cualquier tarea, si descubriste algo que le hubiera ahorrado tokens/tiempo a la siguiente sesión (una ruta no obvia, un gotcha, un comando, un atajo, un contrato), **agrégalo aquí en la sección que corresponda, en la misma sesión**, como UNA línea densa. Si una entrada resultó falsa u obsoleta, corrígela o bórrala. No agregues prosa ni entradas derivables del código en 10 segundos. Este archivo viaja por git a las 2 PCs (principal + Surface).

Gatillo automático: un hook de Stop (`.claude/hooks/alimentar-docs.sh`) recuerda esta regla UNA vez por sesión cuando el repo tiene cambios — al recibirlo, alimenta lo que aplique o declara que no hubo nada.

Documentos hermanos (misma regla de alimentación, se leen **bajo demanda**):
- `CEMENTERIO.md` — enfoques ya intentados que fallaron. Leerlo ANTES de atacar un problema conocido; enterrar ahí cada callejón sin salida nuevo.
- `docs/QA-RECETAS.md` — flujos de verificación por módulo. Leer la receta del módulo tocado tras cada cambio; completar los ⬜ al ejecutarlos.

## Entorno local

- App: Docker → http://localhost:8080 · phpMyAdmin: 8081 · MySQL expuesto: 3307 (user `medisoft_user`/`medisoft_pass`).
- **BD real de trabajo: `medisoft_hoteles_import`** (no `medisoft_hoteles` que dice el README).
- Cada PC tiene su BD local independiente: tras merge, correr migraciones a mano (`php src/tools/migrate.php`).
- Hotel semilla/QA: **Los Cedros** (`hotel_id=1`, slug `los-cedros`). Usuarios QA: `claude_qa1` (nómina/general, `usuarios.id=43`), `dueno_qa` (modo dueño).
- Verificar cifras de dinero SIEMPRE vía PDO (script PHP), nunca mysql CLI directo: el timezone de sesión difiere y no cuadra con la app.
- **QA en navegador (Browser pane)**: no hay dev-login. Para entrar con sesión, crear endpoint temporal en `src/public_html/` que haga `session_start()` + setee `$_SESSION` (`user_id=43`, `hotel_id=1`, `hotel_slug='los-cedros'`, `hotel_usuario=['id'=>34,'rol'=>'gerente']`), navegar a él y **borrarlo al terminar**. GOTCHA visor: el app tapa viewport horizontal con overlay "Modo vertical requerido" y el screenshot del pane se cuelga en landscape → usar viewport **portrait** (390×844) o desktop alto (1280×900); si aun así `screenshot` se cuelga, verificar por `get_page_text`+asserts JS, y si el clic físico no dispara `onclick`, usar `dispatchEvent`/`requestSubmit` vía javascript_tool. GOTCHA msConfirm en el pane: el visor throttlea `requestAnimationFrame` → el overlay nunca recibe la clase `open` y `settle()` no resuelve el promise → antes del click sintético forzar `document.querySelector('.ms-cf-ov').classList.add('open')`. Con el renderer congelado las transiciones CSS/rAF NO avanzan: los modales con fade quedan en `opacity:0` y los detectores por visibilidad (modal-sidebar-fix.js → `ms-modal-abierto`) nunca disparan — verificar CSS dependiente de esas clases toggleándolas a mano con JS; además `resize_window` puede no aplicar (innerWidth se queda en el ancho físico del pane, ~638px = layout móvil). El `.htaccess` cachea la vista (bfcache): recargar con query `?nc=N` para ver cambios. Reserva útil para check-in: **#32869** (confirmada, $2,000, Los Cedros).

## Mapa del código

- `src/app/controllers/` → `XController.php`; ruta `/seccion/accion` → `SeccionController::accion()`; vistas en `src/app/views/<seccion>/`.
- `src/app/models/` modelos · `src/app/services/` servicios (AnticipoService, etc.) · `src/app/helpers/functions.php` = helpers globales (archivo gigante: leer quirúrgico con Grep, jamás completo).
- Config global (permisos.php del RBAC, etc.) vive en `src/config/`, NO en `src/app/config/`.
- `src/app/views/partials/` componentes compartidos: `filtros.php` (barras filtro boutique), `confirm.php` (msConfirm), `back_arrow.php`, `view_topbar.php` (inyectado desde header.php a TODAS las vistas), `section_subnav.php`.
- Layout: `src/app/views/layout/` (header.php, footer.php, sidebar.php).
- CSS: `src/public_html/css/` (tailwind.css generado, dark-theme.css, cupertino.css) · JS: `src/public_html/js/` (app.js, sidebar-rail.js, instant-nav.js, modal-sidebar-fix.js).
- Copiloto IA: `CopilotoController` + `CopilotoIaController` + widget `views/layout/copiloto_widget.php`. Acción nueva por chat = 3 puntos: `detectarAccion*`+`ejecutar*` en CopilotoService, campos en `ejecutarAccion()` del widget, whitelist en `accionAction` del controller. Parsers de frases = estáticos puros públicos con test propio (`parsearGasto/Cupon/PagoProveedor/FechasReserva`, `sanearHistorial/Flujo`). Wizard de reservación: campo/flag nuevo del estado = 2 puntos o se PIERDE en el round-trip (`flujoReservaVacio()` + whitelist de `sanearFlujo`); las respuestas posibles de cada paso viajan por `sugerencias` desde `preguntaFlujoReserva()` y el `slice()` del widget debe ser ≥5 (o corta el chip Cancelar). Tool use IA: herramienta nueva = `herramientasIa()` (schema) + case en `ejecutarHerramientaIa()` (whitelist primero; solo lectura, hotel amarrado en servidor); el loop agéntico vive en `llamarClaude()` (máx 4 rondas, thinking intacto en el turno assistant). Al probarlo, esquivar keywords del router determinista ("caja") o nunca llega a la IA.
- Áreas del hotel (alberca, lobby…): `AreaController` + modelo `Area` + vistas `views/areas/` (index con form inline patrón activos, ver = acciones+historial, mapa = `/mapa` habitaciones+áreas por piso); viven DENTRO del módulo `habitaciones` (mismos gates, jamás claves `areas.*` nuevas por el gotcha can_legacy); subnav compartida vía `section_subnav.php` sección 'habitaciones' (Mapa·Habitaciones·Áreas); `area_id` nullable en tareas_operativas/mantenimientos_habitaciones/activos_hotel; limpieza de área = métodos `*AreaParaHotel` de TareaOperativa, mantenimiento = `iniciar/finalizarParaAreaHotel` de MantenimientoService.
- `dist/` = builds de deploy (Hostinger): **nunca editar ahí**, solo `src/`.

## Comandos

- **Radiografía (correr AL ARRANCAR y tras merges)**: `MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php /var/www/html/tools/radiografia.php` — migraciones pendientes, tenancy, frescura CSS, errores en logs, tests. GOTCHA Git Bash: sin `MSYS_NO_PATHCONV=1`, MSYS rompe las rutas `/var/...` de todo `docker exec`.
- CSS: `npm run build:css` tras tocar clases Tailwind (o `watch:css`). GOTCHA: los globs de `tailwind.config` jamás deben incluir helpers/tcpdf/fonts (líneas de 1.1M chars → build de 5 min).
- Tests: `php src/tests/run.php` (casos en `src/tests/casos/`, concurrencia en `src/tests/concurrencia/`). **Todo caso DEBE terminar con `t_fin()`** (sin él los FAIL no tumban el runner y reporta verde); si un caso necesita un helper nuevo, cargarlo en `tests/bootstrap.php` espejo de `index.php`.
- Linter tenancy: `php src/tools/lint_tenancy.php` (baseline en `lint_tenancy_baseline.json`; no subir el conteo).
- Migraciones: `php src/tools/migrate.php` (SQL en `migrations/` de la RAÍZ del repo, ~100 archivos; `src/database/migrations/` es un directorio muerto). GOTCHA: tras cualquier migración que cambie esquema, regenerar `src/database/schema.sql` (los tests recrean `medisoft_test` desde ahí; si queda viejo, la suite llena el log de `Unknown column`): `docker exec medisoft_hoteles_db mysqldump --no-data --skip-comments --ignore-table=medisoft_hoteles_import.vista_caja_actual -umedisoft_user -pmedisoft_pass medisoft_hoteles_import | sed 's/ AUTO_INCREMENT=[0-9]*//' > src/database/schema.sql`.
- Tests en paralelo NO: dos corridas simultáneas se pisan (`run.php` hace DROP/CREATE de `medisoft_test` → deadlocks, `1146 table doesn't exist`, duplicados de semilla fantasma). Si fallan raro, revisar `SHOW PROCESSLIST` por otra conexión a `medisoft_test` antes de depurar.
- **Centinela de errores**: `MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php /var/www/html/tools/centinela.php` — agrupa errores de logs por firma con estado persistente (NUEVA/REGRESIÓN gritan, conocidas callan; exit 1 = actuar). `resolver <firma> "nota"` al arreglar un bug, `ignorar <firma>` para ruido; `--json` = contrato para autocorrección futura con IA. Canal cli suele ser tests, no la BD real.
- **Observatorio del Copiloto**: `MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php /var/www/html/tools/observatorio_copiloto.php` — salud reglas/ia/fallback + preguntas caídas en fallback agrupadas por tema (firma de tokens sin orden/muletillas); `atendida <firma> "nota"` al enseñarle algo, `ignorar` para saludos/ruido; REGRESIÓN = se le enseñó y sigue cayendo; `--json` = contrato de automatización. GOTCHA: al fechar estado usar reloj de la BD, no PHP (relojes difieren 6h).
- Crons de referencia en `src/tools/cron_*.php` (night audit, ical, copiloto, cobros saas…).

## Invariantes — NO ROMPER

- **Tenancy**: toda query nueva filtra por `hotel_id`; el linter vigila.
- **Caja**: 3 candados de concurrencia de cortes (un corte abierto por caja, movimiento no cae en corte cerrado, cierre atómico) — patrón `FOR UPDATE` + `enTransaccion` (commit 875c51bd).
- **Nómina v2**: crédito = bruto − ledger absorbido + candado anti doble pago en anular/reabrir (b275390d). La vista vieja trabajadores/nomina sigue siendo la pantalla de PAGO — no borrar; `/nomina` nueva es fachada sin pago.
- **Anticipos**: ligados a caja vía `reservacion_abonos` + AnticipoService; check-in es saldo-aware (sin doble cobro).
- **Descuentos**: motor reusa `IncrementoTarifa(clase)`; el form activo de reservación es `crear.php` (jQuery) — `crear.js` está muerto.
- Acciones del Copiloto por chat: propuesta determinista → confirmación humana → motor de pantalla estándar (nunca SQL directo desde IA).

## Gotchas conocidos

- Cache/navegación: el freno era `.htaccess` (Apache pisa headers de PHP), no PHP — revisar ahí primero ante temas de cache.
- Validador de mantenimiento: campos `*_costo` tienen trampa de validación; `habitacion_id` es NULLABLE (usar LEFT JOIN).
- Headers glass Cupertino: `padding-inline` de la vista pisa al header → usar `!important`; si la vista fija geometría con `!important`, agregar regla propia en §48c de cupertino.css.
- Sesiones concurrentes de edición se pisan sin commit (mantenimiento) — avisar al usuario si aplica.
- `navegacion.php` tiene un OR de permisos traicionero (caso Guardián).
- MySQL enum: insertar valor inválido (ej. 'egreso') no truena, duplica conceptos — validar enums en PHP.
- `can_legacy` (roles sin `role_id`) NO conoce `reservaciones.*`: un gate `can('reservaciones.x')` bloquea a TODOS los roles legacy (admin incluido). Regla: un enlace/acción jamás exige más permiso que la pantalla a la que apunta.
- Los modales de habitaciones/index (limpieza, vista rápida…) viven DENTRO de `.container` (`position:relative; z-index:1`): su z-index alto (900/10040) queda ATRAPADO en ese contexto y cualquier elemento externo con z>1 (ej. header sticky z40) les pinta encima. Arreglo canónico: bajar el elemento invasor con `body.ms-modal-abierto` (clase que publica modal-sidebar-fix.js), patrón sidebar — no mover el modal ni ocultar con display.
- **JSON por POST**: `post()`/`getPost()` pasan TODO por `htmlspecialchars(ENT_QUOTES)` → un JSON llega como `{&quot;...}` y `json_decode` falla EN SILENCIO. Revertir con `html_entity_decode(..., ENT_QUOTES, 'UTF-8')` solo para params que alimentan json_decode (caso copiloto historial/flujo). Los tests por servicio NO lo detectan: probar el cable real.

## Componentes reutilizables (no reinventar)

- Toast/confirm: `msToast` + `msConfirm` (modal PC / sheet móvil) + `msPageState` · Modales: `window.msModal` (animación+skeleton, footer.php).
- Validación de forms (app.js): todo POST guardado recibe el recuadro genérico `.ms-form-error-summary` + anti-doble-submit + borrador. Opt-outs por atributo: `data-form-guard="off"` (apaga TODO), `data-ms-no-summary="1"` (solo suprime el recuadro, útil si el form ya avisa por su cuenta — ej. wizard de check-in que usa `mostrarMensaje`→`msToast`), `data-no-unsaved-warning`, `data-no-draft`.
- Inputs de dinero: `data-money-format="true"` (MedisoftMoneyInput ya vive en app.js ~2204-2335).
- Filtros de listados: partial `filtros.php` (.msf-*) · Badges de novedad sidebar: helper `sidebar_novedades()`.
- Push PWA: cadena completa con ruteo por rol (ver PwaPushController); iOS requiere instalación.

## UI / temas

- White-label: TODO el cromado deriva de `--brand-*`; semánticos (éxito/error/limpieza) reservados a su significado. Skill `deleite-sereno` para pantallas nuevas "premium".
- Temas: base Deleite (editorial) + Cupertino (liquid glass, cupertino.css con secciones §N) + modo oscuro (dark-theme.css, remapeo de tokens).
- Tipografía: font-weight máx 700 en sans, nunca 800/900. Mobile-first ≤768px compacto.

## Estilo de trabajo del usuario (Maximiliano)

- **Auditar antes de implementar**; propuestas concisas; economía de tokens (sin .md innecesarios, lecturas quirúrgicas).
- Toda mejora verificada del giro SaaS se vuelca a `docs/PLAYBOOK-SAAS-VERTICALES.md` en la misma sesión (§3/§4/§5/§7a según tipo).
- Commits en español con prefijo tipo `feat(modulo):` / `fix:` / `chore:`.
