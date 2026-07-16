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
- Hotel semilla/QA: **Los Cedros**. Usuarios QA: `claude_qa1` (nómina/general), `dueno_qa` (modo dueño).
- Verificar cifras de dinero SIEMPRE vía PDO (script PHP), nunca mysql CLI directo: el timezone de sesión difiere y no cuadra con la app.

## Mapa del código

- `src/app/controllers/` → `XController.php`; ruta `/seccion/accion` → `SeccionController::accion()`; vistas en `src/app/views/<seccion>/`.
- `src/app/models/` modelos · `src/app/services/` servicios (AnticipoService, etc.) · `src/app/helpers/functions.php` = helpers globales (archivo gigante: leer quirúrgico con Grep, jamás completo).
- `src/app/views/partials/` componentes compartidos: `filtros.php` (barras filtro boutique), `confirm.php` (msConfirm), `back_arrow.php`, `view_topbar.php` (inyectado desde header.php a TODAS las vistas), `section_subnav.php`.
- Layout: `src/app/views/layout/` (header.php, footer.php, sidebar.php).
- CSS: `src/public_html/css/` (tailwind.css generado, dark-theme.css, cupertino.css) · JS: `src/public_html/js/` (app.js, sidebar-rail.js, instant-nav.js, modal-sidebar-fix.js).
- Copiloto IA: `CopilotoController` + `CopilotoIaController` + widget `views/layout/copiloto_widget.php`. Acción nueva por chat = 3 puntos: `detectarAccion*`+`ejecutar*` en CopilotoService, campos en `ejecutarAccion()` del widget, whitelist en `accionAction` del controller. Parsers de frases = estáticos puros públicos con test propio (`parsearGasto/Cupon/PagoProveedor/FechasReserva`, `sanearHistorial/Flujo`).
- `dist/` = builds de deploy (Hostinger): **nunca editar ahí**, solo `src/`.

## Comandos

- **Radiografía (correr AL ARRANCAR y tras merges)**: `MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php /var/www/html/tools/radiografia.php` — migraciones pendientes, tenancy, frescura CSS, errores en logs, tests. GOTCHA Git Bash: sin `MSYS_NO_PATHCONV=1`, MSYS rompe las rutas `/var/...` de todo `docker exec`.
- CSS: `npm run build:css` tras tocar clases Tailwind (o `watch:css`). GOTCHA: los globs de `tailwind.config` jamás deben incluir helpers/tcpdf/fonts (líneas de 1.1M chars → build de 5 min).
- Tests: `php src/tests/run.php` (casos en `src/tests/casos/`, concurrencia en `src/tests/concurrencia/`). **Todo caso DEBE terminar con `t_fin()`** (sin él los FAIL no tumban el runner y reporta verde); si un caso necesita un helper nuevo, cargarlo en `tests/bootstrap.php` espejo de `index.php`.
- Linter tenancy: `php src/tools/lint_tenancy.php` (baseline en `lint_tenancy_baseline.json`; no subir el conteo).
- Migraciones: `php src/tools/migrate.php` (SQL en `src/database/migrations/`).
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

## Componentes reutilizables (no reinventar)

- Toast/confirm: `msToast` + `msConfirm` (modal PC / sheet móvil) + `msPageState` · Modales: `window.msModal` (animación+skeleton, footer.php).
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
