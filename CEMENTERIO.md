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
- **Rediseñar el paso "Métodos de pago" del check-in (ver.php) como lista de casillas** (chips "exacto" + checkbox por método + ledger de 3 celdas) → el dueño lo rechazó: "muy igual al pasado, no más rápido" → modelo POS: meta con barra de progreso viva + fichas grandes que al tocarlas se autollenan con lo que falta y muestran el monto asignado; efectivo es el "relleno" automático, para dividir se teclea primero el otro método. Colores por método vía clase `.rv-pay-*` (`--rv-pay-color`); NO poner fallback `--rv-pay-color` en `.rv-tile`/`.rv-pay-panel`: pisa las clases de método (misma especificidad, gana el último) y todo sale del color de marca.

## Backend / datos

- **Arreglar cache de navegación tocando headers PHP** → Apache (.htaccess) pisa los headers de PHP → el freno vive en `.htaccess`; revisar ahí primero.
- **Verificar cifras de dinero con mysql CLI por fecha** → timezone de sesión distinto al de la app, no cuadra jamás → validar por PDO (script PHP en el contenedor).
- **Confiar en que MySQL rechaza un enum inválido** → no truena, inserta vacío/trunca (caso 'egreso' duplicando conceptos de caja) → validar enums en PHP antes del INSERT.
- **INNER JOIN a `habitacion_id` en mantenimientos** → la columna es NULLABLE (mantenimiento de áreas/activos) → LEFT JOIN siempre.
- **Duplicar pestaña "Pre-nómina" en la /nomina nueva** → doble superficie confundía; la vieja trabajadores/nomina sigue siendo la pantalla de PAGO → /nomina nueva es fachada sin pago; no borrar la vieja.
- **Dejar que la IA del Copiloto ejecute directo** → riesgo y errores → patrón fijo: propuesta determinista → confirmación humana → motor de pantalla estándar.
- **Gatear la reservación por chat con `can('reservaciones.create')`** → `can_legacy` no conoce `reservaciones.*` y bloqueaba a TODOS los roles legacy (admin incluido); la pantalla de crear no exige ese permiso → paridad exacta: un enlace nunca pide más permiso que su pantalla destino.
- **Crear la reservación completa desde el chat** → precio/tarifas/anticipo viven en `crear.php` y duplicarlos es riesgo de dinero → el chat entiende+valida (disponibilidad, huésped) y entrega el formulario PRELLENADO por query (`crearAction` ya lo soportaba).
- **Mandar JSON por POST confiando en `getPost()`** → el `sanitize()` global lo pasa por `htmlspecialchars` y el `json_decode` muere en silencio (el flujo del copiloto caía a IA "sin razón"; los tests por servicio no lo ven) → `html_entity_decode(ENT_QUOTES)` en el controller SOLO para params de json_decode, y verificar el cable real en navegador.

## Entorno / herramientas

- **`docker exec ... php /var/www/html/...` desde Git Bash** → MSYS convierte `/var/...` a ruta de Windows y falla → prefijar `MSYS_NO_PATHCONV=1` (o correr desde PowerShell).
- **Aplicar migraciones "cuando me acuerde" tras un merge multi-PC** → BDs locales divergen silenciosamente → `tools/migrate.php status` (o la radiografía) tras cada merge.
- **Escribir casos de test sin `t_fin()` al final** → los FAIL no se cuentan y el runner reporta "TODOS PASARON" con aserciones rotas → todo caso cierra con `t_fin()` (así se descubrió: un gate falso pasaba en verde).
- **Asumir que `tests/bootstrap.php` carga lo mismo que `index.php`** → faltaba `helpers/modulos.php`: `function_exists('hotel_has_module')` daba false y los gates de bloque degradaban EN SILENCIO (el caso negativo pasaba por la razón equivocada) → al usar un helper nuevo en tests, verificar que el bootstrap lo cargue.
- **Ante `Unknown column` en logs, asumir "falta una migración" o "bug en la BD real"** → las migraciones estaban al día y `medisoft_hoteles_import` tenía la columna; los errores eran `metodo:cli` = TESTS contra `medisoft_test`, recreada desde un `src/database/schema.sql` congelado → primero ver `metodo`/`ruta` del error en el log JSON: si es cli, comparar contra `medisoft_test` y regenerar schema.sql (comando en CLAUDE.md §Comandos).
- **Depurar una falla de test "imposible" (tabla existente que 'no existe', deadlock en CREATE TABLE) leyendo código** → era otra suite corriendo EN PARALELO recreando `medisoft_test` a media corrida → `SHOW PROCESSLIST` primero; esperar a que la otra conexión suelte la BD y reintentar.
