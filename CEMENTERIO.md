# CEMENTERIO.md — Callejones sin salida (no volver a caminar)

Cosas que **ya se intentaron y fallaron**, o rutas que parecen correctas y no lo son. Los fracasos no dejan rastro en el código; este archivo es su lápida. Formato: **Intento → por qué falló → qué se hace en su lugar.**

## ⚡ Regla de alimentación

Cada vez que un enfoque se descarte tras haberlo intentado (o se descubra que "el camino obvio" está muerto), se entierra aquí en la misma sesión, UNA línea por lápida. Antes de atacar un problema conocido, revisar si su solución obvia ya tiene tumba.

## Frontend / UI

- **Editar `crear.js` para el form de reservación** → archivo muerto, nada lo carga → el form activo es `views/reservaciones/crear.php` con jQuery inline.
- **Tailwind Play CDN** → compilaba CSS en el navegador en cada carga (lento) → reemplazado por precompilado; `npm run build:css`.
- **Meter helpers/tcpdf/fonts en los globs de `tailwind.config`** → líneas de 1.1M chars, build de 5 min → globs solo sobre vistas y JS propios.
- **Ocultar la sidebar ante modales (display/z-index)** → frágil, rompía layouts → `modal-sidebar-fix.js` la DIFUMINA (blur+dim+z1) con detector genérico; opt-out `data-ms-keep-sidebar`.
- **`confirm()`/`alert()` nativos** → inconsistentes y feos en móvil → `msConfirm`/`msToast` globales (partials/confirm.php).
- **font-weight 800/900 en sans** → se ve tosco en el estilo boutique → máximo 700 (feedback directo del dueño).
- **Migas de pan locales por vista** → duplicaban la navegación → kill-list aplicada; `view_topbar.php` global las provee.
- **Ajustar headers glass Cupertino sin `!important`** → el `padding-inline` de cada vista los pisa → override con `!important` en la sección §N correspondiente de cupertino.css.
- **Reimplementar formato de dinero en inputs** → ya existe `MedisoftMoneyInput` en app.js (~2204) → solo agregar `data-money-format="true"`.

## Backend / datos

- **Arreglar cache de navegación tocando headers PHP** → Apache (.htaccess) pisa los headers de PHP → el freno vive en `.htaccess`; revisar ahí primero.
- **Verificar cifras de dinero con mysql CLI por fecha** → timezone de sesión distinto al de la app, no cuadra jamás → validar por PDO (script PHP en el contenedor).
- **Confiar en que MySQL rechaza un enum inválido** → no truena, inserta vacío/trunca (caso 'egreso' duplicando conceptos de caja) → validar enums en PHP antes del INSERT.
- **INNER JOIN a `habitacion_id` en mantenimientos** → la columna es NULLABLE (mantenimiento de áreas/activos) → LEFT JOIN siempre.
- **Duplicar pestaña "Pre-nómina" en la /nomina nueva** → doble superficie confundía; la vieja trabajadores/nomina sigue siendo la pantalla de PAGO → /nomina nueva es fachada sin pago; no borrar la vieja.
- **Dejar que la IA del Copiloto ejecute directo** → riesgo y errores → patrón fijo: propuesta determinista → confirmación humana → motor de pantalla estándar.
- **Gatear la reservación por chat con `can('reservaciones.create')`** → `can_legacy` no conoce `reservaciones.*` y bloqueaba a TODOS los roles legacy (admin incluido); la pantalla de crear no exige ese permiso → paridad exacta: un enlace nunca pide más permiso que su pantalla destino.
- **Crear la reservación completa desde el chat** → precio/tarifas/anticipo viven en `crear.php` y duplicarlos es riesgo de dinero → el chat entiende+valida (disponibilidad, huésped) y entrega el formulario PRELLENADO por query (`crearAction` ya lo soportaba).

## Entorno / herramientas

- **`docker exec ... php /var/www/html/...` desde Git Bash** → MSYS convierte `/var/...` a ruta de Windows y falla → prefijar `MSYS_NO_PATHCONV=1` (o correr desde PowerShell).
- **Aplicar migraciones "cuando me acuerde" tras un merge multi-PC** → BDs locales divergen silenciosamente → `tools/migrate.php status` (o la radiografía) tras cada merge.
- **Escribir casos de test sin `t_fin()` al final** → los FAIL no se cuentan y el runner reporta "TODOS PASARON" con aserciones rotas → todo caso cierra con `t_fin()` (así se descubrió: un gate falso pasaba en verde).
- **Asumir que `tests/bootstrap.php` carga lo mismo que `index.php`** → faltaba `helpers/modulos.php`: `function_exists('hotel_has_module')` daba false y los gates de bloque degradaban EN SILENCIO (el caso negativo pasaba por la razón equivocada) → al usar un helper nuevo en tests, verificar que el bootstrap lo cargue.
