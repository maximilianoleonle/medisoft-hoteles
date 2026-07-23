<?php
/**
 * Widget flotante del Copiloto Medisoft (bloque copiloto).
 * Se incluye desde footer.php solo si el hotel tiene el bloque activo.
 * Habla con POST /copiloto/preguntar (JSON de vuelta). Solo lectura.
 */
if (!function_exists('hotel_menu_module_enabled') || !hotel_menu_module_enabled('copiloto')) {
    return;
}
$copilotoToken = function_exists('csrf_token') ? csrf_token() : '';
$copilotoUrl = function_exists('url') ? url('copiloto/preguntar') : '/copiloto/preguntar';
$copilotoUrlAccion = function_exists('url') ? url('copiloto/accion') : '/copiloto/accion';
$copilotoUrlFeedback = function_exists('url') ? url('copiloto/feedback') : '/copiloto/feedback';

// Nombre white-label del asistente (config copiloto.nombre por hotel).
require_once __DIR__ . '/../../services/CopilotoService.php';
$copHotelId = (int) (function_exists('current_hotel_id') ? current_hotel_id() : 0);
try {
    $copNombre = CopilotoService::nombreAsistente($copHotelId);
} catch (Throwable $e) {
    $copNombre = 'Copiloto';
}
// El FAB usa el nombre que el hotel le puso a su asistente; si no, "Asistente"
// (no "Asesor inteligente", que es otra seccion del menu y confundia).
$copEtiquetaFab = $copNombre !== 'Copiloto' ? $copNombre : 'Asistente';
$copilotoLogoUrl = function_exists('asset_version')
    ? asset_version('img/logo.png')
    : (function_exists('asset') ? asset('img/logo.png') : '/img/logo.png');
$copilotoLogoNocheUrl = function_exists('asset_version')
    ? asset_version('img/logo_noche.png')
    : (function_exists('asset') ? asset('img/logo_noche.png') : '/img/logo_noche.png');

/**
 * Diccionario de secciones para hipervincular las negritas del chat. Consciente
 * de modulos: solo se vuelven enlace las secciones que ESTE hotel tiene activas.
 * Clave = nombre normalizado (minusculas, sin acentos); valor = URL absoluta.
 * Algunas llevan #ancla para hacer scroll a la accion exacta al llegar.
 */
$copU = function ($ruta) { return function_exists('url') ? url($ruta) : '/' . ltrim($ruta, '/'); };
$copMod = function ($clave) { return function_exists('hotel_menu_module_enabled') ? hotel_menu_module_enabled($clave) : true; };

// En el Modo Dueno (/dueno) los chips hablan el idioma del dueno remoto:
// preguntas de negocio, no de operacion.
$copEsModoDueno = (bool) preg_match('#/dueno(/|$)#', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');

$copSecciones = [
    // Core: siempre disponibles. Las negritas inline llevan a la pantalla; los
    // anclas de accion exacta los pone cada respuesta del copiloto en su enlace.
    'caja' => $copU('caja'),
    'reservaciones' => $copU('reservaciones'),
    'habitaciones' => $copU('habitaciones'),
];
$copOpcionales = [
    'huespedes' => ['huespedes' => $copU('huespedes')],
    // Nombres nuevos (renombre jul-2026) + alias viejos: el modelo puede citar
    // cualquiera de los dos y ambos deben volverse enlace.
    'motor_reservas' => ['reservas en linea' => $copU('motor-reservas'), 'motor de reservas' => $copU('motor-reservas')],
    'promociones' => ['cupones' => $copU('motor-reservas/cupones')],
    'upsells' => ['extras' => $copU('motor-reservas/extras')],
    'reputacion' => ['opiniones y encuestas' => $copU('reputacion'), 'reputacion' => $copU('reputacion')],
    'lealtad' => ['huesped frecuente' => $copU('lealtad')],
    'forecast' => ['pronostico de ocupacion' => $copU('forecast'), 'pronostico' => $copU('forecast'), 'forecast' => $copU('forecast')],
    'night_audit' => ['cierre del dia' => $copU('night-audit'), 'night audit' => $copU('night-audit')],
    'auditoria' => ['historial de actividad' => $copU('auditoria'), 'bitacora' => $copU('auditoria')],
    'ia_ejecutiva' => ['asesor inteligente' => $copU('ia/resumen-diario')],
    'reportes' => ['reportes' => $copU('reportes')],
    'inventario' => ['inventario' => $copU('inventario')],
    'tareas' => ['tareas' => $copU('tareas')],
    'personal' => ['personal' => $copU('trabajadores')],
    'nomina_avanzada' => ['nomina' => $copU('nomina')],
    'compras' => ['compras' => $copU('compras')],
    'facturacion' => ['facturacion' => $copU('facturacion')],
    'cuentas_cobrar' => ['cuentas por cobrar' => $copU('cuentas-por-cobrar')],
    'documentos' => ['documentos' => $copU('documentos')],
    'tarifas_dinamicas' => ['precios y temporadas' => $copU('configuracion/tarifas'), 'tarifas dinamicas' => $copU('configuracion/tarifas')],
    'checkin_digital' => ['check-in digital' => $copU('checkin-digital')],
    'camarista' => ['limpieza' => $copU('camarista'), 'app de camarista' => $copU('camarista')],
    'canales_ical' => ['airbnb y booking' => $copU('canales'), 'canales' => $copU('canales')],
    'whatsapp' => ['conectar whatsapp' => $copU('whatsapp'), 'whatsapp' => $copU('whatsapp')],
    'tablero_ejecutivo' => ['el hotel hoy' => $copU('operacion/diaria'), 'operacion diaria' => $copU('operacion/diaria')],
    'notificaciones' => ['notificaciones' => $copU('notificaciones')],
    'configuracion' => ['configuracion' => $copU('configuracion')],
    'usuarios' => ['usuarios' => $copU('usuarios')],
];
foreach ($copOpcionales as $clave => $mapa) {
    if ($copMod($clave)) {
        foreach ($mapa as $nombre => $urlSeccion) {
            $copSecciones[$nombre] = $urlSeccion;
        }
    }
}

/**
 * Contexto de pantalla: ruta relativa de la app ("reservaciones/ver/12").
 * Se manda con cada pregunta (el servicio la usa para responder sobre la
 * entidad visible) y aqui decide los chips contextuales de la seccion.
 */
$copBasePath = rtrim((string) parse_url($copU(''), PHP_URL_PATH), '/');
$copRuta = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if ($copBasePath !== '' && strpos($copRuta, $copBasePath) === 0) {
    $copRuta = substr($copRuta, strlen($copBasePath));
}
$copRuta = trim($copRuta, '/');
$copSegmentos = explode('/', $copRuta);
$copSeccion = strtolower((string) ($copSegmentos[0] ?? ''));

$copEntidad = null;
if (preg_match('#^reservaciones/ver/[0-9]+#i', $copRuta)) {
    $copEntidad = 'reservacion';
} elseif (preg_match('#^habitaciones/[0-9]+#i', $copRuta)) {
    $copEntidad = 'habitacion';
}

// Chips [etiqueta, pregunta data-q]. Los de la seccion visible van primero;
// si faltan, se completa con los generales de siempre (fallback).
$copChipsDefault = [
    ['📋 Resumen del día', 'Dame el resumen del día'],
    ['Habitaciones libres', '¿Cuántas habitaciones libres tengo hoy?'],
    ['Llegadas de hoy', '¿Quién llega hoy?'],
    ['¿Cómo pinta la semana?', '¿Cómo pinta la semana?'],
    ['Estado de caja', '¿Cómo voy de caja?'],
    ['¿Cómo hago un corte?', '¿Cómo hago un corte de caja?'],
];

$copChipsContexto = [];
if ($copEntidad === 'reservacion') {
    $copChipsContexto = [
        ['¿Cuánto debe?', '¿Cuánto debe esta reservación?'],
        ['¿Ya pagó anticipo?', '¿Ya pagó el anticipo?'],
        ['¿Cuántas noches?', '¿Cuántas noches se queda?'],
        ['Llegadas de hoy', '¿Quién llega hoy?'],
    ];
} elseif ($copEntidad === 'habitacion') {
    $copChipsContexto = [
        ['¿Está ocupada?', '¿Está ocupada?'],
        ['¿Quién la ocupa?', '¿Quién está en esta habitación?'],
        ['¿Cuándo se desocupa?', '¿Cuándo se desocupa?'],
        ['Estado de las habitaciones', '¿Cómo están mis habitaciones ahorita?'],
    ];
} else {
    switch ($copSeccion) {
        case 'reservaciones':
            $copChipsContexto = [
                ['Llegadas de hoy', '¿Quién llega hoy?'],
                ['Salidas de hoy', '¿Quién se va hoy?'],
                ['¿Hay no-shows?', '¿Tengo no-shows pendientes?'],
                ['Llegadas de mañana', '¿Quién llega mañana?'],
                ['Checkouts vencidos', '¿Hay checkouts vencidos?'],
            ];
            break;
        case 'habitaciones':
            $copChipsContexto = [
                ['Estado de las habitaciones', '¿Cómo están mis habitaciones ahorita?'],
                ['¿Qué limpio hoy?', '¿Qué hay que limpiar hoy?'],
                ['Habitaciones libres', '¿Cuántas habitaciones libres tengo hoy?'],
                ['Checkouts vencidos', '¿Hay checkouts vencidos?'],
            ];
            break;
        case 'caja':
            $copChipsContexto = [
                ['Estado de caja', '¿Cómo voy de caja?'],
                ['¿Cómo hago un corte?', '¿Cómo hago un corte de caja?'],
                ['Gastos del mes', '¿En qué se me va el dinero este mes?'],
                ['¿Cómo me pagan?', '¿Cómo me pagaron este mes?'],
            ];
            break;
        case 'camarista':
        case 'tareas':
            $copChipsContexto = [
                ['¿Qué limpio hoy?', '¿Qué hay que limpiar hoy?'],
                ['Estado de las habitaciones', '¿Cómo están mis habitaciones ahorita?'],
                ['Salidas de hoy', '¿Quién se va hoy?'],
            ];
            break;
        case 'inventario':
            $copChipsContexto = [
                ['Por agotarse', '¿Qué productos están por agotarse?'],
                ['¿Cómo registro un movimiento?', '¿Cómo registro un movimiento de inventario?'],
            ];
            break;
        case 'reputacion':
            $copChipsContexto = [
                ['¿Cómo me califican?', '¿Cómo me califican mis huéspedes?'],
                ['Calificaciones bajas', '¿Tengo calificaciones bajas?'],
                ['Enviar encuesta', '¿Cómo mando una encuesta?'],
            ];
            break;
        case 'compras':
            $copChipsContexto = [
                ['¿Cuánto debo?', '¿Cuánto debo a proveedores?'],
                ['Registrar compra', '¿Cómo registro una compra?'],
            ];
            break;
        case 'cuentas-por-cobrar':
            $copChipsContexto = [
                ['¿Quién me debe?', '¿Quién me debe?'],
            ];
            break;
        case 'nomina':
            $copChipsContexto = [
                ['Nómina del periodo', '¿Cuánto es la nómina de este periodo?'],
                ['¿Cómo corro la nómina?', '¿Cómo corro la nómina?'],
            ];
            break;
        case 'trabajadores':
            if ($copMod('nomina_avanzada')) {
                $copChipsContexto[] = ['Nómina del periodo', '¿Cuánto es la nómina de este periodo?'];
            }
            $copChipsContexto[] = ['Alta de trabajador', '¿Cómo registro a un trabajador?'];
            break;
        case 'forecast':
            $copChipsContexto = [
                ['¿Cómo pinta la semana?', '¿Cómo pinta la semana?'],
                ['Noches vendidas', '¿Cuántas noches vendí este mes?'],
                ['Tarifa promedio', '¿Cuál es mi tarifa promedio?'],
            ];
            break;
        case 'motor-reservas':
            $copChipsContexto = [
                ['Pagos por conciliar', '¿Tengo pagos online por conciliar?'],
            ];
            if ($copMod('promociones')) {
                $copChipsContexto[] = ['Cupones activos', '¿Qué cupones tengo activos?'];
            }
            break;
        case 'reportes':
            $copChipsContexto = [
                ['Ganancias del mes pasado', '¿Cuáles fueron las ganancias del mes pasado?'],
                ['¿Mejor o peor que el mes pasado?', '¿Voy mejor o peor que el mes pasado?'],
                ['Mi mejor día', '¿Cuál fue mi mejor día del mes?'],
            ];
            break;
    }
}

// Catalogo de capacidades por PAGINAS (saludo, flechas ‹ ›), filtrado por
// bloques: frecuentes aprendidos + categorias fijas. La pagina de la
// PANTALLA visible (chips de contexto de arriba) va primero si aplica.
try {
    $copPaginas = (new CopilotoService())->catalogoCapacidades($copHotelId);
} catch (Throwable $e) {
    $copPaginas = [];
}
if (!empty($copChipsContexto)) {
    array_unshift($copPaginas, ['titulo' => 'En esta pantalla', 'items' => array_map(function ($c) {
        return [$c[0], $c[1], 0];
    }, array_slice($copChipsContexto, 0, 5))]);
}
if (empty($copPaginas)) {
    $copPaginas = [['titulo' => 'Sugerencias', 'items' => array_map(function ($c) {
        return [$c[0], $c[1], 0];
    }, $copChipsDefault)]];
}
?>
<style>
/* ============================================================
   Copiloto — launcher (FAB) + panel.
   Estilo Apple: muelle suave, vidrio esmerilado, sombras en capas
   y microinteracciones sutiles. White-label: todo el cromado se
   deriva de --brand-*. Un solo nivel de énfasis sans = 700.
   ============================================================ */

/* ---- Launcher flotante (el "widget") ---- */
#cop-fab { position: fixed; bottom: calc(28px + env(safe-area-inset-bottom, 0px)); right: calc(22px + env(safe-area-inset-right, 0px)); z-index: 10000; width: 64px; max-width: calc(100vw - 44px); height: 64px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 14%, #FFFFFF); cursor: pointer; background: #fff; padding: 0; box-shadow: 0 1px 2px rgba(20,28,45,.14), 0 12px 30px -10px rgba(20,28,45,.5), 0 0 0 6px color-mix(in srgb, var(--brand-accent, #BD9441) 6%, transparent); display: flex; align-items: center; justify-content: flex-start; transition: width .34s cubic-bezier(.34,1.4,.5,1), transform .18s cubic-bezier(.34,1.56,.64,1), box-shadow .24s ease; overflow: visible; isolation: isolate; }
#cop-fab:focus-visible,
#cop-fab.cop-fab-discover { width: min(218px, calc(100vw - 44px)); box-shadow: 0 2px 4px rgba(20,28,45,.16), 0 18px 40px -10px rgba(20,28,45,.56), 0 0 0 8px color-mix(in srgb, var(--brand-accent, #BD9441) 10%, transparent); }
/* Expandirse con hover SOLO donde hay hover real: iOS deja el :hover "pegado"
   tras el primer toque y el FAB quedaba inflado (218px) estorbando justo en la
   franja donde el pulgar desliza. En tactil la expansion queda para focus/discover. */
@media (hover: hover) {
    #cop-fab:hover { width: min(218px, calc(100vw - 44px)); box-shadow: 0 2px 4px rgba(20,28,45,.16), 0 18px 40px -10px rgba(20,28,45,.56), 0 0 0 8px color-mix(in srgb, var(--brand-accent, #BD9441) 10%, transparent); transform: translateY(-2px) scale(1.035); }
    #cop-fab:hover .cop-fab-mark .cop-logo-img { transform: scale(2.14); }
    #cop-fab:hover .cop-fab-label { opacity: 1; transform: translateX(0); }
}
#cop-fab:active { transform: translateY(0) scale(.97); transition-duration: .08s; }
/* Anillo ambiente: latido lento y continuo para que el widget "respire"
   y no se pierda; opacidad baja y mucho reposo entre pulsos. */
#cop-fab::before { content: ''; position: absolute; inset: -5px; border-radius: 999px; border: 1.5px solid color-mix(in srgb, var(--brand-accent, #BD9441) 45%, transparent); opacity: 0; pointer-events: none; z-index: -1; animation: cop-ambient 5.6s ease-in-out 2.4s infinite; }
/* Anillo de descubrimiento: un solo destello más marcado al aparecer. */
#cop-fab::after { content: ''; position: absolute; inset: -7px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--brand-accent, #BD9441) 32%, transparent); opacity: 0; pointer-events: none; z-index: -1; }
#cop-fab.cop-fab-attention::after { animation: cop-fab-ring 2.4s ease-out .9s 1; }
#cop-fab:hover::before,
#cop-fab:focus-visible::before,
body.cop-abierto #cop-fab::before { animation: none; opacity: 0; }
.cop-fab-mark { position: relative; width: 64px; height: 64px; min-width: 64px; border-radius: 50%; display: grid; place-items: center; overflow: hidden; background: #fff; }
/* Brillo que barre el logo de vez en cuando (microrecompensa boutique). */
.cop-fab-mark::after { content: ''; position: absolute; inset: 0; border-radius: 50%; background: linear-gradient(115deg, transparent 34%, rgba(255,255,255,.55) 48%, transparent 62%); transform: translateX(-130%); pointer-events: none; animation: cop-sheen 7.5s ease-in-out 3s infinite; }
.cop-logo-img { width: 100%; height: 100%; object-fit: contain; display: block; opacity: 1; transform-origin: center; transition: opacity .18s ease, transform .3s cubic-bezier(.34,1.4,.5,1); }
.cop-logo-img-noche { position: absolute; inset: 0; opacity: 0; pointer-events: none; }
.cop-fab-mark .cop-logo-img { transform: scale(2.05); }
.cop-fab-label { color: var(--brand-primary, #1B2746); font-size: .88rem; font-weight: 700; line-height: 1; white-space: nowrap; padding: 0 18px 0 2px; opacity: 0; transform: translateX(-8px); transition: opacity .2s ease, transform .28s cubic-bezier(.34,1.4,.5,1); }
#cop-fab:focus-visible .cop-fab-label,
#cop-fab.cop-fab-discover .cop-fab-label { opacity: 1; transform: translateX(0); }
html[data-theme="dark"] #cop-fab { background: #171612; border-color: rgba(239,233,220,.14); box-shadow: 0 1px 2px rgba(0,0,0,.5), 0 16px 34px -12px rgba(0,0,0,.72), 0 0 0 6px color-mix(in srgb, var(--brand-accent, #BD9441) 8%, transparent); }
html[data-theme="dark"] .cop-fab-mark { background: #171612; }
html[data-theme="dark"] .cop-fab-label { color: #EFE9DC; }
html[data-theme="dark"] .cop-logo-img-dia { opacity: 0; }
html[data-theme="dark"] .cop-logo-img-noche { opacity: 1; }
@keyframes cop-fab-ring {
    0% { opacity: 0; transform: scale(.92); }
    22% { opacity: .26; }
    76% { opacity: 0; transform: scale(1.14); }
    100% { opacity: 0; transform: scale(1.14); }
}
@keyframes cop-ambient {
    0% { opacity: 0; transform: scale(.94); }
    14% { opacity: .22; }
    42% { opacity: 0; transform: scale(1.18); }
    100% { opacity: 0; transform: scale(1.18); }
}
@keyframes cop-sheen {
    0% { transform: translateX(-130%); }
    9% { transform: translateX(150%); }
    100% { transform: translateX(150%); }
}

/* ---- Panel (por encima del widget: z mayor + separado de la esquina) ---- */
#cop-panel { position: fixed; bottom: calc(104px + env(safe-area-inset-bottom, 0px)); right: calc(20px + env(safe-area-inset-right, 0px)); z-index: 10001; width: min(384px, calc(100vw - 32px)); max-height: min(560px, calc(100vh - 148px)); background: rgba(255,255,255,.92); -webkit-backdrop-filter: blur(24px) saturate(180%); backdrop-filter: blur(24px) saturate(180%); border: 1px solid rgba(20,28,45,.07); border-radius: 20px; box-shadow: 0 2px 6px rgba(20,28,45,.08), 0 18px 40px -14px rgba(20,28,45,.34), 0 40px 80px -30px rgba(20,28,45,.42); display: flex; flex-direction: column; overflow: hidden; transform-origin: bottom right; opacity: 0; visibility: hidden; pointer-events: none; transform: translateY(14px) scale(.92); transition: opacity .24s ease, transform .46s cubic-bezier(.34,1.4,.5,1), visibility 0s linear .3s; }
#cop-panel.abierto { opacity: 1; visibility: visible; pointer-events: auto; transform: translateY(0) scale(1); transition: opacity .28s ease, transform .5s cubic-bezier(.34,1.4,.5,1), visibility 0s; }
.cop-head { padding: 14px 16px; background: linear-gradient(135deg, var(--brand-primary, #1B2746), color-mix(in srgb, var(--brand-primary, #1B2746) 82%, #000)); color: #fff; display: flex; align-items: center; gap: 10px; position: relative; }
.cop-head::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 1px; background: rgba(255,255,255,.14); }
.cop-logo-mark { position: relative; width: 34px; height: 34px; border-radius: 10px; background: rgba(255,255,255,.18); display: grid; place-items: center; flex: none; overflow: hidden; }
.cop-logo-mark .cop-logo-img { transform: scale(1.78); }
#cop-panel.abierto .cop-logo-mark { animation: cop-pop .55s .06s cubic-bezier(.34,1.56,.64,1) both; }
html[data-theme="dark"] .cop-logo-mark { background: rgba(0,0,0,.18); }
.cop-head strong { font-size: .95rem; font-weight: 700; letter-spacing: -.01em; }
.cop-head .cop-sub { font-size: .72rem; opacity: .82; }
.cop-close { margin-left: auto; width: 30px; height: 30px; display: grid; place-items: center; background: transparent; border: 0; color: #fff; font-size: 1.25rem; line-height: 1; cursor: pointer; border-radius: 50%; transition: background .18s ease, transform .22s cubic-bezier(.34,1.4,.5,1); }
.cop-close:hover { background: rgba(255,255,255,.16); transform: rotate(90deg); }
.cop-close:active { transform: rotate(90deg) scale(.9); }
/* overscroll-behavior: contain corta el "encadenado": al llegar al final del
   chat y seguir arrastrando, la pagina de atras ya no se lleva el gesto. */
.cop-body { flex: 1; overflow-y: auto; overflow-x: hidden; overscroll-behavior: contain; -webkit-overflow-scrolling: touch; padding: 14px; display: flex; flex-direction: column; gap: 10px; background: transparent; scroll-behavior: smooth; scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--brand-primary, #1B2746) 22%, transparent) transparent; }
.cop-body::-webkit-scrollbar { width: 8px; }
.cop-body::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--brand-primary, #1B2746) 20%, transparent); border-radius: 999px; border: 2px solid transparent; background-clip: padding-box; }
.cop-body::-webkit-scrollbar-thumb:hover { background: color-mix(in srgb, var(--brand-primary, #1B2746) 34%, transparent); background-clip: padding-box; }
.cop-msg { max-width: 88%; padding: 10px 13px; border-radius: 16px; font-size: .88rem; line-height: 1.5; animation: cop-msg-in .42s cubic-bezier(.22,1,.36,1) both; }
.cop-msg.user { position: relative; align-self: flex-end; transform-origin: bottom right; background: linear-gradient(135deg, var(--brand-primary, #1B2746), color-mix(in srgb, var(--brand-primary, #1B2746) 84%, #000)); color: #fff; border-bottom-right-radius: 5px; box-shadow: 0 6px 16px -9px color-mix(in srgb, var(--brand-primary, #1B2746) 70%, transparent); }
.cop-del { position: absolute; top: -7px; left: -7px; width: 20px; height: 20px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 25%, #D8D4C9); background: #fff; color: #6B7280; font-size: 12px; line-height: 1; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity .18s ease, transform .16s ease, color .18s ease; padding: 0; }
.cop-msg.user:hover .cop-del, .cop-del:focus-visible { opacity: 1; }
.cop-del:hover { color: #B4232A; transform: scale(1.12); }
@media (hover: none) { .cop-del { opacity: .55; } }
html[data-theme="dark"] .cop-del { background: #211F1A; border-color: rgba(239,233,220,.22); color: #A8A193; }
/* Feedback 👍/👎 discreto al pie de cada respuesta */
.cop-fb { display: inline-flex; gap: 2px; margin-left: 8px; vertical-align: middle; }
.cop-fb-btn { border: none; background: transparent; cursor: pointer; font-size: .78rem; line-height: 1; padding: 2px 3px; border-radius: 6px; opacity: .3; filter: grayscale(1); transition: opacity .18s ease, transform .16s ease, filter .18s ease, background .18s ease; }
.cop-msg.bot:hover .cop-fb-btn, .cop-fb-btn:focus-visible { opacity: .7; }
.cop-fb-btn:hover { opacity: 1; filter: none; transform: scale(1.15); }
.cop-fb-btn.activa { opacity: 1; filter: none; background: color-mix(in srgb, var(--brand-primary, #1B2746) 8%, transparent); }
.cop-fb.votado .cop-fb-btn:not(.activa) { opacity: .18; }
@media (hover: none) { .cop-fb-btn { opacity: .5; } }
.cop-msg.bot { align-self: flex-start; transform-origin: bottom left; background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 8%, #EBE7DC); color: #2A3242; border-bottom-left-radius: 5px; box-shadow: 0 6px 18px -13px rgba(20,28,45,.28); }
.cop-fuente { display: inline-block; margin-top: 6px; font-size: .68rem; font-weight: 700; padding: 1px 7px; border-radius: 999px; }
.cop-fuente.reglas { background: rgba(22,163,74,.12); color: #15803D; }
.cop-fuente.ia { background: rgba(124,58,237,.12); color: #6D28D9; }
.cop-fuente.fallback { background: rgba(100,116,139,.12); color: #64748B; }
.cop-enlace { display: inline-block; margin-top: 8px; font-size: .8rem; font-weight: 700; color: var(--brand-primary, #1B2746); text-decoration: none; padding: 5px 10px; border: 1px solid #D8D4C9; border-radius: 8px; transition: transform .16s cubic-bezier(.34,1.4,.5,1), box-shadow .18s ease, background .18s ease, border-color .18s ease; }
.cop-enlace:hover { transform: translateY(-1px); border-color: var(--brand-primary, #1B2746); background: color-mix(in srgb, var(--brand-primary, #1B2746) 6%, #fff); box-shadow: 0 8px 16px -10px color-mix(in srgb, var(--brand-primary, #1B2746) 60%, transparent); }
.cop-enlace:active { transform: translateY(0) scale(.98); }
.cop-link { color: var(--brand-primary, #1B2746); font-weight: 700; text-decoration: underline; text-underline-offset: 2px; cursor: pointer; }
.cop-link:hover { opacity: .8; }
/* ---- Microvisualizaciones inline (CSS puro, cromadas desde --brand-*) ---- */
.cop-viz { margin-top: 10px; }
.cop-viz-cols { display: flex; align-items: flex-end; gap: 5px; height: 64px; padding: 2px 1px 0; }
.cop-viz-col { flex: 1; min-width: 0; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 3px; }
.cop-viz-col-bar { width: 100%; max-width: 26px; min-height: 3px; border-radius: 5px 5px 2px 2px; background: linear-gradient(180deg, color-mix(in srgb, var(--brand-primary, #1B2746) 55%, #fff), var(--brand-primary, #1B2746)); transform-origin: bottom; animation: cop-viz-crece .5s cubic-bezier(.22,1,.36,1) both; }
.cop-viz-col.destacada .cop-viz-col-bar { background: linear-gradient(180deg, color-mix(in srgb, var(--brand-accent, #BD9441) 55%, #fff), var(--brand-accent, #BD9441)); box-shadow: 0 4px 10px -6px color-mix(in srgb, var(--brand-accent, #BD9441) 80%, transparent); }
.cop-viz-col-lbl { font-size: .6rem; line-height: 1; color: #7C8496; white-space: nowrap; }
.cop-viz-rows { display: flex; flex-direction: column; gap: 6px; }
.cop-viz-row { display: flex; align-items: center; gap: 8px; }
.cop-viz-row-lbl { flex: none; width: 84px; font-size: .7rem; color: #55607A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cop-viz-track { flex: 1; height: 10px; border-radius: 999px; background: color-mix(in srgb, var(--brand-primary, #1B2746) 8%, #EFECE3); overflow: hidden; display: block; }
.cop-viz-fill { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--brand-primary, #1B2746), color-mix(in srgb, var(--brand-primary, #1B2746) 62%, var(--brand-accent, #BD9441))); transform-origin: left; animation: cop-viz-crece-x .55s cubic-bezier(.22,1,.36,1) both; }
.cop-viz-row.destacada .cop-viz-fill { background: linear-gradient(90deg, var(--brand-accent, #BD9441), color-mix(in srgb, var(--brand-accent, #BD9441) 62%, var(--brand-primary, #1B2746))); }
.cop-viz-val { flex: none; font-size: .7rem; font-weight: 700; color: var(--brand-primary, #1B2746); }
@keyframes cop-viz-crece { 0% { transform: scaleY(0); } 100% { transform: scaleY(1); } }
@keyframes cop-viz-crece-x { 0% { transform: scaleX(0); } 100% { transform: scaleX(1); } }
html[data-theme="dark"] .cop-viz-col-lbl { color: #8A8478; }
html[data-theme="dark"] .cop-viz-row-lbl { color: #C9C3B4; }
html[data-theme="dark"] .cop-viz-track { background: rgba(239,233,220,.1); }
html[data-theme="dark"] .cop-viz-val { color: #EFE9DC; }
html[data-theme="dark"] .cop-viz-col-bar { background: linear-gradient(180deg, color-mix(in srgb, var(--brand-primary, #1B2746) 45%, #EFE9DC), color-mix(in srgb, var(--brand-primary, #1B2746) 70%, #EFE9DC)); }
html[data-theme="dark"] .cop-viz-col.destacada .cop-viz-col-bar { background: linear-gradient(180deg, color-mix(in srgb, var(--brand-accent, #BD9441) 60%, #EFE9DC), var(--brand-accent, #BD9441)); }
html[data-theme="dark"] .cop-viz-fill { background: linear-gradient(90deg, color-mix(in srgb, var(--brand-primary, #1B2746) 55%, #EFE9DC), color-mix(in srgb, var(--brand-primary, #1B2746) 45%, var(--brand-accent, #BD9441))); }

/* Deep-links de respuesta: botones estilo chip, cromados desde --brand-*. */
.cop-acciones { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 9px; }
.cop-accion { display: inline-block; font-size: .78rem; font-weight: 700; color: var(--brand-primary, #1B2746); text-decoration: none; padding: 5px 11px; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 26%, #D8D4C9); border-radius: 999px; background: color-mix(in srgb, var(--brand-primary, #1B2746) 5%, #fff); transition: transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .18s ease, background .18s ease, border-color .18s ease; }
.cop-accion:hover { transform: translateY(-1px); border-color: var(--brand-primary, #1B2746); background: color-mix(in srgb, var(--brand-primary, #1B2746) 10%, #fff); box-shadow: 0 8px 16px -10px color-mix(in srgb, var(--brand-primary, #1B2746) 60%, transparent); }
.cop-accion:active { transform: translateY(0) scale(.96); }
.cop-sugerencias { display: flex; flex-wrap: wrap; gap: 6px; }
.cop-chip { border: 1px solid #D8D4C9; background: #fff; border-radius: 999px; padding: 5px 11px; font-size: .78rem; cursor: pointer; color: #55607A; transition: border-color .18s ease, color .18s ease, background .18s ease, transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .18s ease; }
.cop-chip:hover { border-color: var(--brand-primary, #1B2746); color: var(--brand-primary, #1B2746); background: color-mix(in srgb, var(--brand-primary, #1B2746) 4%, #fff); transform: translateY(-1px); box-shadow: 0 8px 16px -10px rgba(20,28,45,.4); }
.cop-chip:active { transform: translateY(0) scale(.95); }
/* Catálogo de capacidades por páginas (saludo) — navegación estilo iOS */
.cop-cat { margin-top: 10px; }
.cop-cat-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 7px; }
.cop-cat-titulo { font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #8A8471; flex: 1; text-align: center; }
.cop-cat-nav { width: 26px; height: 26px; border-radius: 999px; border: none; background: color-mix(in srgb, var(--brand-primary, #1B2746) 6%, transparent); color: #6B7280; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; transition: background .18s ease, color .18s ease, transform .16s ease; }
.cop-cat-nav:hover { background: color-mix(in srgb, var(--brand-primary, #1B2746) 12%, transparent); color: var(--brand-primary, #1B2746); }
.cop-cat-nav:active { transform: scale(.9); }
.cop-cat-page { display: none; flex-wrap: wrap; gap: 6px; }
.cop-cat-page.activa { display: flex; animation: cop-cat-in .28s cubic-bezier(.22,1,.36,1) both; }
@keyframes cop-cat-in { from { opacity: 0; transform: translateX(8px); } to { opacity: 1; transform: none; } }
.cop-cat-dots { display: flex; justify-content: center; gap: 5px; margin-top: 9px; }
.cop-cat-dot { width: 5px; height: 5px; border-radius: 999px; background: color-mix(in srgb, var(--brand-primary, #1B2746) 18%, #D8D4C9); transition: background .18s ease, transform .18s ease; }
.cop-cat-dot.activa { background: var(--brand-primary, #1B2746); transform: scale(1.25); }
html[data-theme="dark"] .cop-cat-titulo { color: #A8A193; }
html[data-theme="dark"] .cop-cat-nav { background: rgba(239,233,220,.08); color: #C9C3B4; }
html[data-theme="dark"] .cop-cat-dot { background: rgba(239,233,220,.22); }
html[data-theme="dark"] .cop-cat-dot.activa { background: #EFE9DC; }
.cop-foot { padding: 10px; border-top: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 7%, #EBE7DC); display: flex; gap: 8px; background: #fff; }
.cop-foot input { flex: 1; min-width: 0; min-height: 42px; border: 1px solid #D8D4C9; border-radius: 10px; padding: 0 12px; font-size: .9rem; background: #fff; color: #2A3242; transition: border-color .18s ease, box-shadow .2s ease, background .18s ease; }
.cop-foot input::placeholder { color: #9AA1B0; }
.cop-foot input:focus { outline: none; border-color: color-mix(in srgb, var(--brand-primary, #1B2746) 55%, #fff); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-primary, #1B2746) 14%, transparent); }
.cop-foot button { min-height: 42px; padding: 0 16px; border: 0; border-radius: 10px; background: linear-gradient(135deg, var(--brand-primary, #1B2746), color-mix(in srgb, var(--brand-primary, #1B2746) 82%, #000)); color: #fff; font-weight: 700; cursor: pointer; box-shadow: 0 6px 16px -9px color-mix(in srgb, var(--brand-primary, #1B2746) 70%, transparent); transition: transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .18s ease, opacity .18s ease; }
.cop-foot button:hover { transform: translateY(-1px); box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--brand-primary, #1B2746) 75%, transparent); }
.cop-foot button:active { transform: translateY(0) scale(.96); }
.cop-foot button:disabled { opacity: .55; cursor: wait; transform: none; box-shadow: none; }
/* ---- Dictado por voz: boton de microfono (solo se crea si hay soporte) ---- */
.cop-mic { flex: none; width: 42px; min-height: 42px; padding: 0; border: 1px solid #D8D4C9; border-radius: 10px; background: #fff; color: #55607A; display: grid; place-items: center; cursor: pointer; box-shadow: none; transition: border-color .18s ease, color .18s ease, background .18s ease, transform .16s cubic-bezier(.34,1.56,.64,1); }
.cop-mic:hover { border-color: var(--brand-primary, #1B2746); color: var(--brand-primary, #1B2746); transform: translateY(-1px); }
.cop-mic:active { transform: translateY(0) scale(.94); }
/* Rojo semantico: SOLO significa "grabando". */
.cop-mic.cop-mic-rec { border-color: #DC2626; color: #DC2626; background: rgba(220,38,38,.08); animation: cop-mic-pulso 1.4s ease-in-out infinite; }
@keyframes cop-mic-pulso { 0%, 100% { box-shadow: 0 0 0 0 rgba(220,38,38,.26); } 50% { box-shadow: 0 0 0 7px rgba(220,38,38,0); } }
html[data-theme="dark"] .cop-mic { background: #211F1A; border-color: rgba(239,233,220,.14); color: #C9C3B4; }
html[data-theme="dark"] .cop-mic:hover { border-color: rgba(239,233,220,.4); color: #EFE9DC; }
html[data-theme="dark"] .cop-mic.cop-mic-rec { background: rgba(220,38,38,.14); border-color: #EF4444; color: #F87171; }

/* ---- Estado "pensando": skeleton con brillo (shimmer) ---- */
.cop-skeleton { display: flex; flex-direction: column; gap: 8px; padding: 3px 0; min-width: 128px; }
.cop-sk-line { height: 9px; border-radius: 999px; background: linear-gradient(100deg, color-mix(in srgb, var(--brand-primary, #1B2746) 9%, #E9E5DA) 30%, color-mix(in srgb, var(--brand-primary, #1B2746) 3%, #F6F3EC) 50%, color-mix(in srgb, var(--brand-primary, #1B2746) 9%, #E9E5DA) 70%); background-size: 220% 100%; animation: cop-shimmer 1.25s ease-in-out infinite; }
.cop-skeleton .cop-sk-line:nth-child(1) { width: 100%; }
.cop-skeleton .cop-sk-line:nth-child(2) { width: 84%; animation-delay: .12s; }
.cop-skeleton .cop-sk-line:nth-child(3) { width: 56%; animation-delay: .24s; }
.cop-reveal { display: block; animation: cop-fade-in .4s ease both; }

@keyframes cop-msg-in { 0% { opacity: 0; transform: translateY(9px) scale(.96); } 100% { opacity: 1; transform: none; } }
@keyframes cop-shimmer { 0% { background-position: 180% 0; } 100% { background-position: -80% 0; } }
@keyframes cop-fade-in { 0% { opacity: 0; transform: translateY(3px); } 100% { opacity: 1; transform: none; } }
@keyframes cop-pop { 0% { transform: scale(.6); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }

/* ---- Modo oscuro del panel ---- */
html[data-theme="dark"] #cop-panel { background: rgba(24,22,18,.9); border-color: rgba(239,233,220,.12); box-shadow: 0 2px 6px rgba(0,0,0,.4), 0 20px 44px -14px rgba(0,0,0,.68), 0 44px 90px -30px rgba(0,0,0,.7); }
html[data-theme="dark"] .cop-head { background: linear-gradient(135deg, #211f1a, #141209); }
html[data-theme="dark"] .cop-body { scrollbar-color: rgba(239,233,220,.2) transparent; }
html[data-theme="dark"] .cop-body::-webkit-scrollbar-thumb { background: rgba(239,233,220,.18); background-clip: padding-box; }
html[data-theme="dark"] .cop-msg.bot { background: #211F1A; border-color: rgba(239,233,220,.1); color: #E7E1D3; box-shadow: 0 6px 18px -13px rgba(0,0,0,.6); }
html[data-theme="dark"] .cop-foot { background: #1b1915; border-top-color: rgba(239,233,220,.1); }
html[data-theme="dark"] .cop-foot input { background: #211F1A; border-color: rgba(239,233,220,.14); color: #EFE9DC; }
html[data-theme="dark"] .cop-foot input::placeholder { color: #8A8478; }
html[data-theme="dark"] .cop-chip { background: #211F1A; border-color: rgba(239,233,220,.14); color: #C9C3B4; }
html[data-theme="dark"] .cop-enlace { border-color: rgba(239,233,220,.16); color: #EFE9DC; }
html[data-theme="dark"] .cop-accion { background: #211F1A; border-color: rgba(239,233,220,.18); color: #EFE9DC; }
html[data-theme="dark"] .cop-accion:hover { background: #2A2721; border-color: rgba(239,233,220,.32); box-shadow: 0 8px 16px -10px rgba(0,0,0,.6); }
html[data-theme="dark"] .cop-link { color: #E7E1D3; }
html[data-theme="dark"] .cop-close:hover { background: rgba(239,233,220,.14); }
html[data-theme="dark"] .cop-fuente.reglas { background: rgba(34,197,94,.16); color: #4ADE80; }
html[data-theme="dark"] .cop-fuente.ia { background: rgba(167,139,250,.18); color: #C4B5FD; }
html[data-theme="dark"] .cop-fuente.fallback { background: rgba(148,163,184,.16); color: #CBD5E1; }
html[data-theme="dark"] .cop-sk-line { background: linear-gradient(100deg, rgba(239,233,220,.08) 30%, rgba(239,233,220,.17) 50%, rgba(239,233,220,.08) 70%); background-size: 220% 100%; }

@media (prefers-reduced-motion: reduce) {
    #cop-fab,
    #cop-fab::after,
    #cop-fab::before,
    .cop-fab-mark::after,
    .cop-fab-label,
    .cop-logo-img,
    #cop-panel,
    #cop-panel.abierto,
    .cop-logo-mark,
    .cop-msg,
    .cop-sk-line,
    .cop-reveal,
    .cop-close,
    .cop-chip,
    .cop-enlace,
    .cop-accion,
    .cop-viz-col-bar,
    .cop-viz-fill,
    .cop-mic,
    .cop-foot button { animation: none !important; transition: opacity .12s ease, visibility 0s !important; }
    #cop-panel { transform: none !important; }
}
@media (max-width: 1024px) {
    body.has-hotel-bottom-nav #cop-fab {
        right: calc(16px + env(safe-area-inset-right, 0px));
        bottom: calc(var(--hbn-offset, 84px) + 14px);
    }

    body.has-hotel-bottom-nav #cop-panel {
        right: 16px;
        bottom: calc(var(--hbn-offset, 84px) + 126px);
        max-height: min(560px, calc(100dvh - var(--hbn-offset, 84px) - 154px));
    }
}
@media (max-width: 780px) {
    body.page-reservaciones #cop-fab {
        width: 56px;
        max-width: 56px;
        height: 56px;
        top: calc(82px + env(safe-area-inset-top, 0px));
        right: calc(16px + env(safe-area-inset-right, 0px));
        bottom: auto;
    }

    body.page-reservaciones #cop-fab:hover,
    body.page-reservaciones #cop-fab:focus-visible,
    body.page-reservaciones #cop-fab.cop-fab-discover {
        width: 56px;
    }

    @media (hover: hover) {
        body.page-reservaciones #cop-fab:hover {
            transform: translateY(-1px) scale(1.02);
        }
    }

    body.page-reservaciones .cop-fab-mark {
        width: 56px;
        height: 56px;
        min-width: 56px;
    }

    body.page-reservaciones .cop-fab-label {
        display: none;
    }

    body.page-reservaciones #cop-fab::after {
        inset: -5px;
    }

    body.page-reservaciones.has-hotel-bottom-nav #cop-fab {
        bottom: auto;
    }
}
/* ── Copiloto: fondo difuminado suave + panel centrado SOLO en móvil ──
   (En escritorio se queda como widget de esquina y la sidebar permanece visible;
    el detector global de modales ignora el panel por su data-ms-no-modal.) */
#cop-backdrop { display: none; }
@media (max-width: 640px) {
    #cop-backdrop {
        display: block;
        position: fixed; inset: 0; z-index: 9990;
        background: rgba(20,28,45,.28);
        -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px);
        opacity: 0; visibility: hidden;
        transition: opacity .3s ease, visibility 0s linear .3s;
    }
    body.cop-abierto #cop-backdrop { opacity: 1; visibility: visible; transition: opacity .3s ease, visibility 0s; }

    /* Centrar el panel (emerge con muelle desde el centro) y ocultar el FAB
       de esquina para enfocarse en el copiloto. La selectora repite
       body.has-hotel-bottom-nav para ganarle a la regla de ≤1024px (que le
       impone bottom + max-height corto y lo dejaba diminuto). Alto fijo
       generoso (~78dvh) con aire alrededor: grande sin comerse la pantalla. */
    #cop-panel,
    body.has-hotel-bottom-nav #cop-panel {
        left: 50%; right: auto; top: 50%; bottom: auto;
        width: min(430px, calc(100vw - 32px));
        height: min(78dvh, calc(100dvh - 120px));
        max-height: min(78dvh, calc(100dvh - 120px));
        transform-origin: center;
        transform: translate(-50%, calc(-50% + 14px)) scale(.94);
    }
    #cop-panel.abierto { transform: translate(-50%, -50%) scale(1); }
    body.cop-abierto #cop-fab { display: none; }

    /* El fondo congelado con el copiloto abierto NO se define aqui: lo pone
       modal-sidebar-fix.js (body.ms-fondo-bloqueado), el mismo candado que
       usan los modales del sistema. Ver bloquearFondo() en el script de abajo. */

    /* Tipografía cómoda de dedo, sin gritar. El input va a 16px exactos
       para que iOS no haga zoom al enfocar. */
    .cop-msg { font-size: .92rem; max-width: 90%; }
    .cop-chip { font-size: .84rem; padding: 7px 13px; }
    .cop-accion { font-size: .84rem; padding: 7px 13px; }
    .cop-foot input { min-height: 46px; font-size: 16px; }
    .cop-foot button { min-height: 46px; }
    .cop-mic { width: 46px; min-height: 46px; }
}
/* ── Con un modal abierto, el copiloto se retira en móvil ──
   La clase ms-modal-abierto la pone/quita modal-sidebar-fix.js al detectar
   cualquier modal visible (el panel del propio copiloto queda excluido por su
   data-ms-no-modal, así que abrirlo no lo oculta a sí mismo). Al cerrarse
   el modal, el widget —y el panel, si estaba abierto— reaparecen solos. */
@media (max-width: 1024px) {
    body.ms-modal-abierto #cop-fab,
    body.ms-modal-abierto #cop-backdrop,
    body.ms-modal-abierto #cop-panel,
    /* ── Y con el menú lateral abierto ──
       Mismo criterio que la barra inferior (ver footer-nav.php): el cajón
       ocupa 86vw por encima de todo, así que el widget estorba sin poder
       usarse. Al cerrar el menú reaparece solo, igual que con los modales.
       Van los dos detectores a propósito: la clase la pone syncOverlayState()
       de footer-nav.php y el :has() no depende de JS; basta cualquiera. */
    body.hbn-menu-abierto #cop-fab,
    body.hbn-menu-abierto #cop-backdrop,
    body.hbn-menu-abierto #cop-panel,
    body:has(#sidebar.active) #cop-fab,
    body:has(#sidebar.active) #cop-backdrop,
    body:has(#sidebar.active) #cop-panel,
    body:has(#sidebar.open) #cop-fab,
    body:has(#sidebar.open) #cop-backdrop,
    body:has(#sidebar.open) #cop-panel {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .2s ease, visibility 0s linear .2s;
    }
}

/* ═══ Copiloto en tema Cupertino: candy glass de la marca (2026-07-11) ═══
   El owner pidió el mismo lenguaje de caja/reservaciones. SOLO Cupertino;
   la base conserva la banda oscura. Header y botón Enviar pasan de banda
   sólida a cristal claro de la marca con tinta. El logo se queda en tesela
   de marca (el glifo "día" es claro y necesita fondo oscuro para leerse).
   Modo oscuro: header en losa grafito (alinea con rdv3 dark). Gana a la base
   por especificidad + orden. Revertir: borrar este bloque. */
html[data-tema="cupertino"]:not([data-theme="dark"]) .cop-head {
    background:
        radial-gradient(62% 185% at 95% 58%, rgba(255,255,255,.58), rgba(255,255,255,0) 70%),
        linear-gradient(180deg, rgba(255,255,255,.4), rgba(255,255,255,0) 48%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--brand-primary, #1B2746) 32%, #FFFFFF) 0%,
            color-mix(in srgb, var(--brand-primary, #1B2746) 52%, #FFFFFF) 100%) !important;
    box-shadow: inset 0 1px 1px rgba(255,255,255,.85) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .cop-head::after {
    background: color-mix(in srgb, var(--brand-primary, #1B2746) 16%, rgba(0,0,0,.05)) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .cop-head strong {
    color: color-mix(in srgb, var(--brand-primary, #1B2746) 34%, #111827) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .cop-head .cop-sub {
    color: color-mix(in srgb, var(--brand-primary, #1B2746) 34%, #6E6E73) !important;
    opacity: 1 !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .cop-logo-mark {
    background: linear-gradient(160deg, var(--brand-primary, #1B2746), color-mix(in srgb, var(--brand-primary, #1B2746) 82%, #000)) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.28), 0 3px 8px -3px color-mix(in srgb, var(--brand-primary, #1B2746) 50%, transparent) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .cop-close {
    color: color-mix(in srgb, var(--brand-primary, #1B2746) 42%, #1F2937) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .cop-close:hover {
    background: color-mix(in srgb, var(--brand-primary, #1B2746) 12%, rgba(0,0,0,.05)) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .cop-foot button {
    background:
        linear-gradient(180deg, rgba(255,255,255,.55), rgba(255,255,255,0) 60%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--brand-primary, #1B2746) 30%, #FFFFFF),
            color-mix(in srgb, var(--brand-primary, #1B2746) 46%, #FFFFFF)) !important;
    color: color-mix(in srgb, var(--brand-primary, #1B2746) 46%, #0C2C1A) !important;
    box-shadow:
        inset 0 0 0 1px color-mix(in srgb, var(--brand-primary, #1B2746) 32%, rgba(255,255,255,.85)),
        inset 0 1px 0 rgba(255,255,255,.85),
        0 6px 15px -9px color-mix(in srgb, var(--brand-primary, #1B2746) 50%, transparent) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .cop-foot button:hover {
    background:
        linear-gradient(180deg, rgba(255,255,255,.6), rgba(255,255,255,0) 60%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--brand-primary, #1B2746) 38%, #FFFFFF),
            color-mix(in srgb, var(--brand-primary, #1B2746) 54%, #FFFFFF)) !important;
    box-shadow:
        inset 0 0 0 1px color-mix(in srgb, var(--brand-primary, #1B2746) 38%, rgba(255,255,255,.85)),
        inset 0 1px 0 rgba(255,255,255,.9),
        0 9px 20px -9px color-mix(in srgb, var(--brand-primary, #1B2746) 55%, transparent) !important;
}
html[data-theme="dark"][data-tema="cupertino"] .cop-head {
    background: linear-gradient(165deg, #232325 0%, #1A1A1C 100%) !important;
}

/* ═══ Copiloto quieto en móvil (2026-07-22) ═══
   En aparatos tactiles el widget NO se ensancha solo, no late, no destella
   y no le barre el brillo: se queda como boton redondo estatico y solo
   reacciona al presionarlo (abre el panel, con su animacion de apertura).
   Todo ese "descubrimiento" (ensancharse a 218px + anillos + sheen) se
   queda SOLO en escritorio, que es donde hay puntero y hover de verdad.

   El detector es "aparato sin mouse", no un ancho: un @media de pixeles
   se equivocaria en ambos sentidos (laptop con ventana angosta perderia
   la animacion; tablet grande la conservaria).

   Ojo con el hover pegado: en tactil el :hover se queda aplicado despues
   de tocar, por eso hay que neutralizarlo explicitamente o el widget se
   quedaba agrandado tras abrirlo. Revertir: borrar este bloque. */
@media (hover: none), (pointer: coarse) {
    #cop-fab,
    #cop-fab:hover,
    #cop-fab:focus-visible,
    #cop-fab.cop-fab-discover {
        width: 64px;
        max-width: 64px;
        box-shadow: 0 1px 2px rgba(20,28,45,.14), 0 12px 30px -10px rgba(20,28,45,.5), 0 0 0 6px color-mix(in srgb, var(--brand-accent, #BD9441) 6%, transparent);
        transition: transform .12s ease;
    }
    /* Sin nudge al tocar; solo el hundido de "si te presione". */
    #cop-fab:hover,
    body.page-reservaciones #cop-fab:hover { transform: none; }
    #cop-fab:active,
    body.page-reservaciones #cop-fab:active { transform: scale(.96); }
    /* Anillo ambiente, destello de descubrimiento y brillo del logo. */
    #cop-fab::before,
    #cop-fab::after,
    #cop-fab.cop-fab-attention::after,
    .cop-fab-mark::after { animation: none; }
    /* La etiqueta ya nunca se revela; fuera del DOM visual para que su
       area invisible no quede tocable al lado del boton. */
    #cop-fab .cop-fab-label { display: none; }
    /* El logo tampoco crece con el hover pegado. */
    #cop-fab:hover .cop-fab-mark .cop-logo-img { transform: scale(2.05); }
}
</style>

<button id="cop-fab" class="cop-fab-discover cop-fab-attention" type="button" aria-label="Abrir <?= htmlspecialchars($copEtiquetaFab, ENT_QUOTES, 'UTF-8') ?>">
    <span class="cop-fab-mark" aria-hidden="true">
        <img class="cop-logo-img cop-logo-img-dia" src="<?= htmlspecialchars($copilotoLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="64" height="64" decoding="async">
        <img class="cop-logo-img cop-logo-img-noche" src="<?= htmlspecialchars($copilotoLogoNocheUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="64" height="64" decoding="async">
    </span>
    <span class="cop-fab-label" aria-hidden="true"><?= htmlspecialchars($copEtiquetaFab, ENT_QUOTES, 'UTF-8') ?></span>
</button>
<div id="cop-backdrop" aria-hidden="true"></div>
<div id="cop-panel" role="dialog" aria-label="<?= htmlspecialchars($copNombre, ENT_QUOTES, 'UTF-8') ?>" data-ms-keep-sidebar data-ms-no-modal>
    <div class="cop-head">
        <span class="cop-logo-mark" aria-hidden="true">
            <img class="cop-logo-img cop-logo-img-dia" src="<?= htmlspecialchars($copilotoLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="34" height="34" decoding="async">
            <img class="cop-logo-img cop-logo-img-noche" src="<?= htmlspecialchars($copilotoLogoNocheUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="34" height="34" decoding="async">
        </span>
        <div>
            <strong><?= htmlspecialchars($copNombre, ENT_QUOTES, 'UTF-8') ?></strong>
            <div class="cop-sub">Pregunta sobre tu hotel</div>
        </div>
        <button type="button" class="cop-close" id="cop-close" aria-label="Cerrar">&times;</button>
    </div>
    <div class="cop-body" id="cop-body">
        <div class="cop-msg bot">
            <?php if ($copEsModoDueno): ?>
            Hola 👋 Preg&uacute;ntame c&oacute;mo va tu hotel.
            <div class="cop-sugerencias" style="margin-top:10px;">
                <button type="button" class="cop-chip" data-q="¿Cuánto entró hoy?">¿Cuánto entró hoy?</button>
                <button type="button" class="cop-chip" data-q="¿Cómo va el mes?">¿Cómo va el mes?</button>
                <button type="button" class="cop-chip" data-q="¿Todo en orden?">¿Todo en orden?</button>
                <button type="button" class="cop-chip" data-q="¿Quién llega hoy?">¿Quién llega hoy?</button>
            </div>
            <?php else: ?>
            Hola 👋 Preg&uacute;ntame sobre tu operaci&oacute;n, o pide una acci&oacute;n. Esto es lo que s&eacute; hacer:
            <div class="cop-cat" id="cop-cat">
                <div class="cop-cat-head">
                    <button type="button" class="cop-cat-nav" data-dir="-1" aria-label="Categoría anterior">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <span class="cop-cat-titulo" id="cop-cat-titulo"><?= htmlspecialchars($copPaginas[0]['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    <button type="button" class="cop-cat-nav" data-dir="1" aria-label="Categoría siguiente">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
                <?php foreach ($copPaginas as $copI => $copPag): ?>
                <div class="cop-cat-page<?= $copI === 0 ? ' activa' : '' ?>" data-pagina="<?= (int) $copI ?>">
                    <?php foreach ($copPag['items'] as $copItem): ?>
                    <button type="button" class="cop-chip" <?= !empty($copItem[2]) ? 'data-fill' : 'data-q' ?>="<?= htmlspecialchars($copItem[1], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($copItem[0], ENT_QUOTES, 'UTF-8') ?></button>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                <div class="cop-cat-dots" aria-hidden="true">
                    <?php foreach ($copPaginas as $copI => $copPag): ?>
                    <span class="cop-cat-dot<?= $copI === 0 ? ' activa' : '' ?>"></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <form class="cop-foot" id="cop-form" autocomplete="off">
        <input type="text" id="cop-input" maxlength="500" placeholder="Escribe tu pregunta..." aria-label="Tu pregunta">
        <button type="submit" id="cop-send">Enviar</button>
    </form>
</div>

<script>
(function () {
    var fab = document.getElementById('cop-fab');
    var panel = document.getElementById('cop-panel');
    var body = document.getElementById('cop-body');
    var form = document.getElementById('cop-form');
    var input = document.getElementById('cop-input');
    var sendBtn = document.getElementById('cop-send');
    var closeBtn = document.getElementById('cop-close');
    var URL = <?= json_encode($copilotoUrl) ?>;
    var URL_ACCION = <?= json_encode($copilotoUrlAccion) ?>;
    var URL_FEEDBACK = <?= json_encode($copilotoUrlFeedback) ?>;
    var TOKEN = <?= json_encode($copilotoToken) ?>;
    var SECCIONES = <?= json_encode($copSecciones, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var RUTA = <?= json_encode($copRuta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var BASE = <?= json_encode(function_exists('url') ? rtrim(url(''), '/') . '/' : '/') ?>;
    // Rutas relativas del sistema ("caja#ancla") -> URL absoluta con el base.
    function resolverUrl(u) {
        u = String(u || '');
        return u.charAt(0) === '/' || u.indexOf('http') === 0 ? u : BASE + u;
    }
    var ocupado = false;
    // Memoria de conversacion: el intent que respondio el servidor a la
    // pregunta anterior; se reenvia para que "¿y manana?" herede el tema.
    var intentPrevio = '';
    // Hilo reciente (multi-turno de la IA): pares pregunta/respuesta de esta
    // pagina. Se manda ANTES de agregar la pregunta nueva; el servidor lo
    // sanea y solo lo usa como contexto conversacional (cifras = snapshot).
    var historial = [];
    // Flujo campo-por-campo pendiente (reservacion). Lo dicta el servidor:
    // se conserva hasta recibir un 'flujo' nuevo o 'flujo_fin'.
    var flujoActual = null;
    // Intercambios pregunta/respuesta con su snapshot de contexto PREVIO
    // (intent, flujo, entradas del historial). Eliminar una pregunta borra
    // sus burbujas, la saca del historial y, si era la ultima, restaura el
    // contexto anterior (incluido el paso del wizard). Todo el contexto vive
    // en el cliente, asi que no hay nada que borrar del lado servidor; las
    // acciones YA ejecutadas no se deshacen por borrar el mensaje.
    var intercambios = [];
    var DISCOVERY_KEY = 'medisoft:copiloto-discovery:v1:' + (
        window.MEDISOFT_CONTEXT && window.MEDISOFT_CONTEXT.hotel_id
            ? String(window.MEDISOFT_CONTEXT.hotel_id)
            : 'global'
    );

    function marcarDescubierto() {
        fab.classList.remove('cop-fab-discover');
        fab.classList.remove('cop-fab-attention');
        try {
            window.localStorage.setItem(DISCOVERY_KEY, 'seen');
        } catch (error) {}
    }

    fab.addEventListener('animationend', function (event) {
        if (event.animationName === 'cop-fab-ring') {
            fab.classList.remove('cop-fab-attention');
        }
    });

    try {
        if (window.localStorage.getItem(DISCOVERY_KEY) === 'seen') {
            fab.classList.remove('cop-fab-discover');
        } else {
            window.setTimeout(marcarDescubierto, 7600);
        }
    } catch (error) {
        window.setTimeout(function () {
            fab.classList.remove('cop-fab-discover');
        }, 7600);
    }

    // Normaliza a minusculas sin acentos, para casar "Reputación" con "reputacion".
    function norm(s) {
        return String(s).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    }

    var backdrop = document.getElementById('cop-backdrop');

    // Congelar el fondo SOLO donde el copiloto se ve como modal a pantalla
    // completa (<=640px, el mismo corte con el que aparece #cop-backdrop). En
    // tablet y escritorio sigue siendo widget de esquina y la pagina de atras
    // tiene que poder desplazarse como siempre.
    var mqModal = window.matchMedia('(max-width: 640px)');
    function bloquearFondo(v) {
        // El candado lo lleva modal-sidebar-fix.js (footer, todas las
        // paginas), compartido con los modales del sistema: si cada uno
        // guardara SU scroll, abrir un modal encima del copiloto leeria 0
        // -- el body ya esta fijo -- y al cerrar la pagina saltaria al inicio.
        // Si ese script no cargo, no hay candado: se degrada al comportamiento
        // de antes, nunca a una pagina congelada.
        if (window.msBloquearFondo) {
            window.msBloquearFondo('copiloto', v && mqModal.matches);
        }
    }
    // Si gira el telefono o cambia el ancho con el panel abierto, el bloqueo
    // sigue al breakpoint; si no, el body se quedaba fijo ya en escritorio.
    var alCambiarModal = function () { bloquearFondo(panel.classList.contains('abierto')); };
    if (mqModal.addEventListener) {
        mqModal.addEventListener('change', alCambiarModal);
    } else if (mqModal.addListener) {
        mqModal.addListener(alCambiarModal);
    }

    function abrir(v) {
        panel.classList.toggle('abierto', v);
        document.body.classList.toggle('cop-abierto', v);
        bloquearFondo(v);
        if (v) {
            marcarDescubierto();
            setTimeout(function () { input.focus(); }, 50);
        }
    }
    // ── Deslizar sobre el FAB debe mover la página, no tragarse el gesto ──
    // El shell scrollea en main.main-content (el body es overflow:hidden y no
    // desborda): un swipe que EMPIEZA sobre el FAB —fixed, hijo de <body>—
    // sube por su cadena de ancestros sin hallar nada scrolleable y el
    // navegador mata el gesto: la página "ya no baja" cuando el dedo cae en
    // el copiloto. El FAB reenvía el arrastre al scroller del shell (con una
    // inercia breve al soltar) y, si hubo arrastre real, el click sintético
    // que pueda seguir se ignora: deslizar no es abrir.
    var gestoScroll = false;
    (function () {
        var scroller = null, yPrev = 0, acumulado = 0, inercia = 0;
        // Muestras {t, y} de los últimos ~100ms: la velocidad del aventón se
        // promedia sobre esa ventana (como el scroll nativo). Medirla solo del
        // último frame salía ruidosa: si el dedo frenaba un instante justo al
        // soltar, el flick "no volaba" y el gesto se sentía trabado.
        var muestras = [];

        function scrollerDelShell() {
            var raiz = document.scrollingElement || document.documentElement;
            // Si la página scrollea en el body (login, layouts sueltos), el
            // gesto nativo ya funciona y no hay nada que reenviar.
            if (raiz.scrollHeight > raiz.clientHeight + 5) { return null; }
            var m = document.querySelector('main.main-content');
            return (m && m.scrollHeight > m.clientHeight + 5) ? m : null;
        }

        fab.addEventListener('touchstart', function (e) {
            if (e.touches.length !== 1) { scroller = null; return; }
            window.cancelAnimationFrame(inercia);
            scroller = scrollerDelShell();
            yPrev = e.touches[0].clientY;
            acumulado = 0;
            muestras = [{ t: e.timeStamp, y: yPrev }];
            gestoScroll = false;
        }, { passive: true });

        fab.addEventListener('touchmove', function (e) {
            if (!scroller || e.touches.length !== 1) { return; }
            var y = e.touches[0].clientY;
            var dy = yPrev - y;
            yPrev = y;
            muestras.push({ t: e.timeStamp, y: y });
            while (muestras.length > 2 && e.timeStamp - muestras[0].t > 100) { muestras.shift(); }
            if (!gestoScroll) {
                acumulado += dy;
                // Umbral: el micro-tiemble de un tap no es un arrastre. Al
                // superarlo se aplica TODO lo acumulado, para que el contenido
                // alcance al dedo sin escalón de arranque.
                if (Math.abs(acumulado) < 4) { return; }
                gestoScroll = true;
                dy = acumulado;
            }
            scroller.scrollTop += dy;
            if (e.cancelable) { e.preventDefault(); }
        }, { passive: false });

        fab.addEventListener('touchend', function (e) {
            if (!gestoScroll || !scroller) { return; }
            // Aventón con la física del scroll nativo: velocidad promediada de
            // la ventana de muestras y decaimiento POR TIEMPO (0.998/ms, la
            // tasa de UIScrollView), no por frame — por frame dependía del
            // framerate y moría en ~300ms: el gesto frenaba en seco.
            var primera = muestras[0];
            var dt = Math.max(1, e.timeStamp - primera.t);
            var v = (primera.y - yPrev) / dt; // px/ms, + = el contenido baja
            var el = scroller;
            if (Math.abs(v) > 0.1) {
                var tAnt = performance.now();
                var paso = function (tAhora) {
                    var ms = Math.min(64, tAhora - tAnt); // pestaña dormida: no saltar
                    tAnt = tAhora;
                    v *= Math.pow(0.998, ms);
                    el.scrollTop += v * ms;
                    var tope = el.scrollTop <= 0 || el.scrollTop >= el.scrollHeight - el.clientHeight;
                    if (Math.abs(v) > 0.01 && !tope) { inercia = window.requestAnimationFrame(paso); }
                };
                inercia = window.requestAnimationFrame(paso);
            }
            // El flag lo consume el click sintético que el navegador pueda
            // emitir tras el arrastre; si no llega ninguno, caduca solo.
            window.setTimeout(function () { gestoScroll = false; }, 500);
        }, { passive: true });

        fab.addEventListener('touchcancel', function () {
            gestoScroll = false;
            scroller = null;
        }, { passive: true });

        // Tocar CUALQUIER parte de la página frena el aventón en curso, igual
        // que el scroll nativo (si no, el dedo nuevo y la inercia vieja se
        // peleaban el scrollTop y se sentía un tirón).
        document.addEventListener('touchstart', function (e) {
            if (e.target !== fab && !fab.contains(e.target)) {
                window.cancelAnimationFrame(inercia);
            }
        }, { passive: true, capture: true });
    })();

    fab.addEventListener('click', function () {
        if (gestoScroll) { gestoScroll = false; return; } // fue arrastre, no tap
        abrir(!panel.classList.contains('abierto'));
    });
    closeBtn.addEventListener('click', function () { abrir(false); });
    if (backdrop) { backdrop.addEventListener('click', function () { abrir(false); }); }

    function escapar(s) {
        return String(s).replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    }
    // Las negritas que casan con una seccion activa se vuelven hipervinculo;
    // el resto queda como texto en negrita normal.
    function formato(texto) {
        var t = escapar(texto);
        t = t.replace(/\*\*([^*]+)\*\*/g, function (_, contenido) {
            var url = SECCIONES[norm(contenido)];
            if (url) {
                return '<a class="cop-link" href="' + escapar(url) + '">' + contenido + '</a>';
            }
            return '<strong>' + contenido + '</strong>';
        });
        return t.replace(/\n/g, '<br>');
    }
    function agregar(clase, html) {
        var div = document.createElement('div');
        div.className = 'cop-msg ' + clase;
        div.innerHTML = html;
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
        return div;
    }

    function preguntar(texto) {
        if (ocupado || !texto.trim()) { return; }
        ocupado = true;
        sendBtn.disabled = true;
        var userDiv = agregar('user', escapar(texto));
        var del = document.createElement('button');
        del.type = 'button';
        del.className = 'cop-del';
        del.setAttribute('aria-label', 'Eliminar esta pregunta y volver al contexto anterior');
        del.title = 'Eliminar esta pregunta';
        del.textContent = '✕';
        userDiv.appendChild(del);
        input.value = '';
        var pensando = agregar('bot cop-loading',
            '<div class="cop-skeleton" role="status" aria-label="Pensando">' +
                '<span class="cop-sk-line"></span>' +
                '<span class="cop-sk-line"></span>' +
                '<span class="cop-sk-line"></span>' +
            '</div>');

        // Snapshot del contexto ANTES de esta pregunta, para poder restaurarlo
        // si el usuario la elimina.
        var registro = {
            userEl: userDiv,
            botEl: pensando,
            prevIntent: intentPrevio,
            prevFlujo: flujoActual ? JSON.parse(JSON.stringify(flujoActual)) : null,
            entradas: []
        };
        intercambios.push(registro);

        var datos = new URLSearchParams();
        datos.append('csrf_token', TOKEN);
        datos.append('pregunta', texto);
        datos.append('ruta', RUTA);
        datos.append('intent_previo', intentPrevio);
        datos.append('historial', JSON.stringify(historial.slice(-6)));
        if (flujoActual) { datos.append('flujo', JSON.stringify(flujoActual)); }

        fetch(URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json', 'X-CSRF-Token': TOKEN },
            body: datos.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                intentPrevio = typeof data.intent === 'string' ? data.intent : '';
                if (data.flujo) { flujoActual = data.flujo; }
                else if (data.flujo_fin) { flujoActual = null; }
                var entradaU = { r: 'u', t: texto };
                historial.push(entradaU);
                registro.entradas.push(entradaU);
                if (typeof data.texto === 'string' && data.texto) {
                    var entradaA = { r: 'a', t: data.texto.slice(0, 600) };
                    historial.push(entradaA);
                    registro.entradas.push(entradaA);
                }
                historial = historial.slice(-8);
                pintarRespuesta(pensando, data);
                // Accion ejecutable propuesta por el servidor: confirmar con
                // msConfirm y solo entonces ejecutar (POST /copiloto/accion).
                if (data.accion && data.accion.tipo) {
                    confirmarAccion(data.accion);
                }
            })
            .catch(function () {
                pensando.classList.remove('cop-loading');
                pensando.innerHTML = '<span class="cop-reveal">No pude responder ahora mismo. Intenta de nuevo.</span>';
            })
            .finally(function () {
                ocupado = false;
                sendBtn.disabled = false;
                input.focus();
            });
    }

    // Pinta una respuesta del copiloto (texto + etiqueta de fuente + deep-links)
    // dentro de la burbuja indicada. Compartido por preguntas y acciones.
    function pintarRespuesta(burbuja, data) {
        var html = formato(data.texto || 'No pude responder.');
        var fuente = data.fuente || 'fallback';
        var etiqueta = fuente === 'reglas' ? '⚡ Instantáneo' : (fuente === 'ia' ? '✨ Asistida' : '💡 Sugerencia');
        html += '<br><span class="cop-fuente ' + fuente + '">' + etiqueta + '</span>';
        // Microvisualizacion inline (CSS puro): la cifra ya viene en el texto,
        // la grafica es apoyo visual (aria-hidden).
        if (data.viz && data.viz.items && data.viz.items.length) {
            html += vizHtml(data.viz);
        }
        // Deep-links: botones de accion [{label, url}] (solo navegacion).
        // Si vienen, sustituyen al enlace suelto para no duplicar destinos.
        var acciones = (data.acciones || []).filter(function (a) { return a && a.url; }).slice(0, 3);
        if (acciones.length) {
            html += '<div class="cop-acciones">';
            acciones.forEach(function (a) {
                html += '<a class="cop-accion" href="' + escapar(resolverUrl(a.url)) + '">' + escapar(a.label || 'Abrir') + ' →</a>';
            });
            html += '</div>';
        } else if (data.enlace && data.enlace.url) {
            html += '<br><a class="cop-enlace" href="' + escapar(resolverUrl(data.enlace.url)) + '">' + escapar(data.enlace.texto || 'Abrir') + ' →</a>';
        }
        // Sugerencias contextuales del servidor: [etiqueta, texto, plantilla].
        // plantilla=1 rellena el input (órdenes que piden datos); si no, envía.
        // Tope holgado: las contextuales son 3, pero las respuestas posibles
        // de un paso del wizard pueden ser hasta 5 (tipos + cancelar).
        var sug = (data.sugerencias || []).slice(0, 5);
        if (sug.length) {
            html += '<div class="cop-sugerencias" style="margin-top:9px;">';
            sug.forEach(function (s) {
                if (!s || !s[0] || !s[1]) { return; }
                html += '<button type="button" class="cop-chip" ' + (s[2] ? 'data-fill' : 'data-q') + '="' + escapar(s[1]) + '">' + escapar(s[0]) + '</button>';
            });
            html += '</div>';
        }
        // Feedback 👍/👎 sobre esta respuesta (solo cuando el servidor manda
        // el ancla del log). Discreto: fantasma hasta pasar el mouse.
        if (data.mid) {
            html += '<span class="cop-fb" data-mid="' + parseInt(data.mid, 10) + '">' +
                '<button type="button" class="cop-fb-btn" data-v="1" aria-label="Me sirvió" title="Me sirvió">👍</button>' +
                '<button type="button" class="cop-fb-btn" data-v="0" aria-label="No me sirvió" title="No me sirvió">👎</button>' +
            '</span>';
        }
        burbuja.classList.remove('cop-loading');
        burbuja.innerHTML = '<span class="cop-reveal">' + html + '</span>';
        body.scrollTop = body.scrollHeight;
    }

    // Envia el voto y marca el estado en la burbuja (re-votar permitido).
    function enviarFeedback(btn) {
        var caja = btn.closest('.cop-fb');
        if (!caja) { return; }
        var datos = new URLSearchParams();
        datos.append('csrf_token', TOKEN);
        datos.append('mid', caja.getAttribute('data-mid') || '0');
        datos.append('util', btn.getAttribute('data-v') === '1' ? '1' : '0');
        fetch(URL_FEEDBACK, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json', 'X-CSRF-Token': TOKEN },
            body: datos.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { return; }
                caja.querySelectorAll('.cop-fb-btn').forEach(function (b) { b.classList.remove('activa'); });
                btn.classList.add('activa');
                caja.classList.add('votado');
            })
            .catch(function () { /* voto perdido: sin drama */ });
    }

    // Mini-grafica en CSS puro a partir de {tipo: 'columnas'|'barras', items:
    // [{etiqueta, valor, pct, destacar}]}. Todo texto pasa por escapar() y el
    // porcentaje se acota 0-100 tambien aqui.
    function vizHtml(viz) {
        var items = (viz.items || []).slice(0, 8);
        var clamp = function (p) { return Math.max(0, Math.min(100, parseInt(p, 10) || 0)); };
        var h = '';
        if (viz.tipo === 'columnas') {
            h += '<div class="cop-viz cop-viz-cols" aria-hidden="true">';
            items.forEach(function (it) {
                var pct = clamp(it.pct);
                h += '<div class="cop-viz-col' + (it.destacar ? ' destacada' : '') + '" title="' + escapar((it.etiqueta || '') + ': ' + (it.valor || '')) + '">' +
                        '<div class="cop-viz-col-bar" style="height:' + Math.max(pct, 4) + '%"></div>' +
                        '<span class="cop-viz-col-lbl">' + escapar(it.etiqueta || '') + '</span>' +
                    '</div>';
            });
            h += '</div>';
            return h;
        }
        h += '<div class="cop-viz cop-viz-rows" aria-hidden="true">';
        items.forEach(function (it) {
            var pct = clamp(it.pct);
            h += '<div class="cop-viz-row' + (it.destacar ? ' destacada' : '') + '">' +
                    '<span class="cop-viz-row-lbl">' + escapar(it.etiqueta || '') + '</span>' +
                    '<span class="cop-viz-track"><span class="cop-viz-fill" style="width:' + Math.max(pct, 2) + '%"></span></span>' +
                    '<span class="cop-viz-val">' + escapar(it.valor || '') + '</span>' +
                '</div>';
        });
        h += '</div>';
        return h;
    }

    // Confirmacion humana antes de ejecutar (msConfirm del sistema; si no
    // esta, confirm nativo). Cancelar no ejecuta nada.
    function confirmarAccion(accion) {
        var pedir = window.msConfirm
            ? window.msConfirm({ type: 'info', title: accion.confirm_titulo || '¿Confirmar acción?', msg: accion.confirm_msg || '', confirmLabel: accion.confirm_ok || 'Confirmar' })
            : Promise.resolve(window.confirm(accion.confirm_msg || '¿Confirmar acción?'));

        pedir.then(function (ok) {
            if (!ok) {
                agregar('bot', '<span class="cop-reveal">Sin problema, no programé nada.</span>');
                return;
            }
            ejecutarAccion(accion);
        });
    }

    function ejecutarAccion(accion) {
        if (ocupado) { return; }
        ocupado = true;
        sendBtn.disabled = true;
        var pensando = agregar('bot cop-loading',
            '<div class="cop-skeleton" role="status" aria-label="Ejecutando">' +
                '<span class="cop-sk-line"></span>' +
                '<span class="cop-sk-line"></span>' +
            '</div>');

        var datos = new URLSearchParams();
        datos.append('csrf_token', TOKEN);
        datos.append('tipo', accion.tipo);
        datos.append('habitacion_id', accion.habitacion_id || '');
        datos.append('fecha', accion.fecha || '');
        if (accion.area_id) { datos.append('area_id', accion.area_id); }
        if (accion.trabajador_id) { datos.append('trabajador_id', accion.trabajador_id); }
        if (accion.motivo) { datos.append('motivo', accion.motivo); }
        if (accion.monto) { datos.append('monto', accion.monto); }
        if (accion.categoria_id) { datos.append('categoria_id', accion.categoria_id); }
        if (accion.descripcion) { datos.append('descripcion', accion.descripcion); }
        if (accion.codigo) { datos.append('codigo', accion.codigo); }
        if (accion.cupon_tipo) { datos.append('cupon_tipo', accion.cupon_tipo); }
        if (accion.valor) { datos.append('valor', accion.valor); }
        if (accion.vigente_desde) { datos.append('vigente_desde', accion.vigente_desde); }
        if (accion.vigente_hasta) { datos.append('vigente_hasta', accion.vigente_hasta); }
        if (accion.limite_usos) { datos.append('limite_usos', accion.limite_usos); }
        if (accion.cuenta_id) { datos.append('cuenta_id', accion.cuenta_id); }
        if (accion.metodo) { datos.append('metodo', accion.metodo); }
        if (accion.proveedor) { datos.append('proveedor', accion.proveedor); }
        if (accion.fecha_entrada) { datos.append('fecha_entrada', accion.fecha_entrada); }
        if (accion.fecha_salida) { datos.append('fecha_salida', accion.fecha_salida); }
        if (accion.huesped_nombre) { datos.append('huesped_nombre', accion.huesped_nombre); }
        if (accion.telefono) { datos.append('telefono', accion.telefono); }

        fetch(URL_ACCION, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json', 'X-CSRF-Token': TOKEN },
            body: datos.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                pintarRespuesta(pensando, data);
                if (data.success && window.msToast) {
                    var toastPorTipo = {
                        iniciar_mantenimiento: ['Mantenimiento iniciado', 'La habitación quedó marcada y el equipo notificado. 🔧'],
                        finalizar_mantenimiento: ['Habitación liberada', 'Quedó disponible para rentar de nuevo. ✅'],
                        registrar_gasto: ['Gasto registrado', 'Quedó anotado en la caja de hoy. 💸'],
                        crear_cupon: ['Cupón creado', 'Ya está activo en Reservas en línea. 🎟️'],
                        pagar_proveedor: ['Pago registrado', 'El egreso quedó en la caja y el saldo se actualizó. 🤝'],
                        finalizar_reserva: ['Huésped listo', 'Quedó registrado; abre el formulario para terminar. 🛎️'],
                        asignar_limpieza: ['Limpieza asignada', 'La tarea quedó en el tablero de limpieza. 🗓️'],
                        cerrar_area: ['Área cerrada', 'Queda fuera de servicio hasta que la reabras. 🚧'],
                        reabrir_area: ['Área reabierta', 'Vuelve a estar disponible. ✅']
                    };
                    var toast = toastPorTipo[accion.tipo] || ['Limpieza programada', 'La tarea quedó en el tablero de limpieza. 🗓️'];
                    window.msToast('success', toast[0], toast[1]);
                }
            })
            .catch(function () {
                pensando.classList.remove('cop-loading');
                pensando.innerHTML = '<span class="cop-reveal">No pude ejecutar la acción. Intenta de nuevo.</span>';
            })
            .finally(function () {
                ocupado = false;
                sendBtn.disabled = false;
            });
    }

    // Elimina un intercambio: quita sus burbujas y sus entradas del historial
    // multi-turno; si era el ULTIMO, restaura el contexto previo (intent y
    // paso del wizard). Borrar el mensaje NO deshace una accion ya ejecutada.
    function eliminarPregunta(btn) {
        if (ocupado) { return; }
        var burbuja = btn.closest('.cop-msg');
        for (var i = 0; i < intercambios.length; i++) {
            if (intercambios[i].userEl === burbuja) {
                var ex = intercambios[i];
                var esUltimo = i === intercambios.length - 1;
                intercambios.splice(i, 1);
                if (ex.userEl && ex.userEl.parentNode) { ex.userEl.parentNode.removeChild(ex.userEl); }
                if (ex.botEl && ex.botEl.parentNode) { ex.botEl.parentNode.removeChild(ex.botEl); }
                historial = historial.filter(function (h) { return ex.entradas.indexOf(h) === -1; });
                if (esUltimo) {
                    intentPrevio = ex.prevIntent;
                    flujoActual = ex.prevFlujo;
                }
                return;
            }
        }
    }

    // ── Resiliencia bfcache (volver con las flechas del navegador) ──
    // instant-nav.js habilita el back-forward cache: al regresar, la página
    // revive CONGELADA con todo su estado JS. Si una pregunta viajaba justo
    // al navegar, el fetch muere y su promesa puede no resolverse jamás:
    // `ocupado` quedaba atorado (Enviar deshabilitado, chips ignorados,
    // esqueleto "pensando" eterno) y un body.cop-abierto huérfano deja el
    // FAB oculto en móvil. pagehide corre ANTES de congelar (la foto se
    // guarda limpia) y pageshow es el cinturón al revivir.
    function soltarCandados() {
        ocupado = false;
        sendBtn.disabled = false;
        var cargando = body.querySelectorAll('.cop-msg.cop-loading');
        for (var i = 0; i < cargando.length; i++) {
            cargando[i].classList.remove('cop-loading');
            cargando[i].innerHTML = '<span class="cop-reveal">No pude responder ahora mismo. Intenta de nuevo.</span>';
        }
        // El body y el panel deben contar la misma historia (un cop-abierto
        // huérfano en móvil esconde el FAB con display:none, y un candado de
        // fondo huérfano deja la página entera sin poder desplazarse).
        var sigueAbierto = panel.classList.contains('abierto');
        document.body.classList.toggle('cop-abierto', sigueAbierto);
        bloquearFondo(sigueAbierto);
    }
    window.addEventListener('pagehide', soltarCandados);
    window.addEventListener('pageshow', function (e) { if (e.persisted) { soltarCandados(); } });

    // Catálogo del saludo: navegación de páginas con flechas (cíclica).
    function catNavegar(dir) {
        var paginas = [].slice.call(document.querySelectorAll('#cop-cat .cop-cat-page'));
        var dots = [].slice.call(document.querySelectorAll('#cop-cat .cop-cat-dot'));
        var titulo = document.getElementById('cop-cat-titulo');
        if (!paginas.length) { return; }
        var actual = 0;
        paginas.forEach(function (p, i) { if (p.classList.contains('activa')) { actual = i; } });
        var destino = (actual + dir + paginas.length) % paginas.length;
        paginas[actual].classList.remove('activa');
        paginas[destino].classList.add('activa');
        if (dots[actual]) { dots[actual].classList.remove('activa'); }
        if (dots[destino]) { dots[destino].classList.add('activa'); }
        if (titulo) { titulo.textContent = TITULOS_CAT[destino] || ''; }
    }
    var TITULOS_CAT = [].slice.call(document.querySelectorAll('#cop-cat .cop-cat-page')).map(function () { return ''; });
    <?php if (!$copEsModoDueno): ?>
    TITULOS_CAT = <?= json_encode(array_column($copPaginas, 'titulo'), JSON_UNESCAPED_UNICODE) ?>;
    <?php endif; ?>

    form.addEventListener('submit', function (e) { e.preventDefault(); preguntar(input.value); });
    body.addEventListener('click', function (e) {
        var del = e.target.closest('.cop-del');
        if (del) { eliminarPregunta(del); return; }
        var fb = e.target.closest('.cop-fb-btn');
        if (fb) { enviarFeedback(fb); return; }
        var nav = e.target.closest('.cop-cat-nav');
        if (nav) { catNavegar(parseInt(nav.getAttribute('data-dir'), 10) || 1); return; }
        var chip = e.target.closest('.cop-chip');
        if (chip) {
            var plantilla = chip.getAttribute('data-fill');
            if (plantilla !== null) {
                // Plantilla: rellena el input para que el usuario complete
                // los datos (habitación, monto, nombre...) y se enfoca.
                input.value = plantilla;
                input.focus();
                try { input.setSelectionRange(plantilla.length, plantilla.length); } catch (err) { /* inputs sin selección */ }
                return;
            }
            preguntar(chip.getAttribute('data-q'));
        }
    });

    // ── Dictado por voz (Web Speech API, es-MX) ──
    // Feature-detect: sin soporte el boton ni se crea. En iOS PWA u otros
    // entornos que niegan el permiso, el primer error lo oculta en silencio.
    (function () {
        var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SR) { return; }

        var mic = document.createElement('button');
        mic.type = 'button';
        mic.id = 'cop-mic';
        mic.className = 'cop-mic';
        mic.setAttribute('aria-label', 'Dictar pregunta');
        mic.setAttribute('aria-pressed', 'false');
        mic.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
            '<rect x="9" y="2" width="6" height="12" rx="3"></rect>' +
            '<path d="M5 10a7 7 0 0 0 14 0"></path>' +
            '<line x1="12" y1="19" x2="12" y2="22"></line>' +
        '</svg>';
        form.insertBefore(mic, sendBtn);

        var rec = null;
        var grabando = false;

        function detener() {
            grabando = false;
            mic.classList.remove('cop-mic-rec');
            mic.setAttribute('aria-pressed', 'false');
            if (rec) { try { rec.stop(); } catch (e) {} }
        }

        mic.addEventListener('click', function () {
            if (grabando) { detener(); return; }
            try {
                rec = new SR();
            } catch (e) {
                mic.style.display = 'none';
                return;
            }
            rec.lang = 'es-MX';
            rec.interimResults = true;
            rec.maxAlternatives = 1;
            rec.onresult = function (ev) {
                var texto = '';
                var esFinal = false;
                for (var i = ev.resultIndex; i < ev.results.length; i++) {
                    texto += ev.results[i][0].transcript;
                    if (ev.results[i].isFinal) { esFinal = true; }
                }
                if (texto) { input.value = texto; }
                if (esFinal) {
                    detener();
                    if (input.value.trim()) { preguntar(input.value); }
                }
            };
            rec.onerror = function (ev) {
                detener();
                // Permiso negado o servicio no disponible: degradar en silencio.
                if (ev && (ev.error === 'not-allowed' || ev.error === 'service-not-allowed')) {
                    mic.style.display = 'none';
                }
            };
            rec.onend = detener;
            try {
                rec.start();
                grabando = true;
                mic.classList.add('cop-mic-rec');
                mic.setAttribute('aria-pressed', 'true');
            } catch (e) {
                detener();
            }
        });

        // Cerrar el panel corta cualquier dictado en curso.
        closeBtn.addEventListener('click', detener);
        if (backdrop) { backdrop.addEventListener('click', detener); }
        // Navegar también: un dictado activo no debe viajar en la foto del
        // historial (y de paso deja a la página entrar al back-forward cache).
        window.addEventListener('pagehide', detener);
    })();
})();
</script>
