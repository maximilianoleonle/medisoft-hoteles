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

// Nombre white-label del asistente (config copiloto.nombre por hotel).
require_once __DIR__ . '/../../services/CopilotoService.php';
$copHotelId = (int) (function_exists('current_hotel_id') ? current_hotel_id() : 0);
try {
    $copNombre = CopilotoService::nombreAsistente($copHotelId);
} catch (Throwable $e) {
    $copNombre = 'Copiloto';
}
// El FAB conserva su etiqueta comercial de siempre salvo que el hotel haya
// bautizado a su asistente.
$copEtiquetaFab = $copNombre !== 'Copiloto' ? $copNombre : 'Asesor inteligente';
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
    'motor_reservas' => ['motor de reservas' => $copU('motor-reservas')],
    'promociones' => ['cupones' => $copU('motor-reservas/cupones')],
    'upsells' => ['extras' => $copU('motor-reservas/extras')],
    'reputacion' => ['reputacion' => $copU('reputacion')],
    'lealtad' => ['huesped frecuente' => $copU('lealtad')],
    'forecast' => ['forecast' => $copU('forecast')],
    'night_audit' => ['night audit' => $copU('night-audit')],
    'auditoria' => ['bitacora' => $copU('auditoria')],
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
    'tarifas_dinamicas' => ['tarifas dinamicas' => $copU('configuracion/tarifas')],
    'checkin_digital' => ['check-in digital' => $copU('checkin-digital')],
    'camarista' => ['app de camarista' => $copU('camarista')],
    'canales_ical' => ['canales' => $copU('canales')],
    'whatsapp' => ['whatsapp' => $copU('whatsapp')],
    'tablero_ejecutivo' => ['operacion diaria' => $copU('operacion/diaria')],
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

$copChips = $copChipsContexto;

// Chips que aprenden: sin chips de seccion, el orden lo dicta la frecuencia
// real de uso del hotel (log copiloto_mensajes, cache 1h). Los fijos de
// siempre quedan como relleno/fallback.
if (empty($copChips)) {
    try {
        $copChips = (new CopilotoService())->chipsFrecuentes($copHotelId, 6);
    } catch (Throwable $e) {
        $copChips = [];
    }
}

foreach ($copChipsDefault as $chip) {
    if (count($copChips) >= 6) {
        break;
    }
    $repetido = false;
    foreach ($copChips as $existente) {
        if ($existente[1] === $chip[1]) {
            $repetido = true;
            break;
        }
    }
    if (!$repetido) {
        $copChips[] = $chip;
    }
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
#cop-fab:hover,
#cop-fab:focus-visible,
#cop-fab.cop-fab-discover { width: min(218px, calc(100vw - 44px)); box-shadow: 0 2px 4px rgba(20,28,45,.16), 0 18px 40px -10px rgba(20,28,45,.56), 0 0 0 8px color-mix(in srgb, var(--brand-accent, #BD9441) 10%, transparent); }
#cop-fab:hover { transform: translateY(-2px) scale(1.035); }
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
#cop-fab:hover .cop-fab-mark .cop-logo-img { transform: scale(2.14); }
.cop-fab-label { color: var(--brand-primary, #1B2746); font-size: .88rem; font-weight: 700; line-height: 1; white-space: nowrap; padding: 0 18px 0 2px; opacity: 0; transform: translateX(-8px); transition: opacity .2s ease, transform .28s cubic-bezier(.34,1.4,.5,1); }
#cop-fab:hover .cop-fab-label,
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
.cop-body { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 14px; display: flex; flex-direction: column; gap: 10px; background: transparent; scroll-behavior: smooth; scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--brand-primary, #1B2746) 22%, transparent) transparent; }
.cop-body::-webkit-scrollbar { width: 8px; }
.cop-body::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--brand-primary, #1B2746) 20%, transparent); border-radius: 999px; border: 2px solid transparent; background-clip: padding-box; }
.cop-body::-webkit-scrollbar-thumb:hover { background: color-mix(in srgb, var(--brand-primary, #1B2746) 34%, transparent); background-clip: padding-box; }
.cop-msg { max-width: 88%; padding: 10px 13px; border-radius: 16px; font-size: .88rem; line-height: 1.5; animation: cop-msg-in .42s cubic-bezier(.22,1,.36,1) both; }
.cop-msg.user { align-self: flex-end; transform-origin: bottom right; background: linear-gradient(135deg, var(--brand-primary, #1B2746), color-mix(in srgb, var(--brand-primary, #1B2746) 84%, #000)); color: #fff; border-bottom-right-radius: 5px; box-shadow: 0 6px 16px -9px color-mix(in srgb, var(--brand-primary, #1B2746) 70%, transparent); }
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
.cop-foot { padding: 10px; border-top: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 7%, #EBE7DC); display: flex; gap: 8px; background: #fff; }
.cop-foot input { flex: 1; min-height: 42px; border: 1px solid #D8D4C9; border-radius: 10px; padding: 0 12px; font-size: .9rem; background: #fff; color: #2A3242; transition: border-color .18s ease, box-shadow .2s ease, background .18s ease; }
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

    body.page-reservaciones #cop-fab:hover {
        transform: translateY(-1px) scale(1.02);
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
       de esquina para enfocarse en el copiloto. */
    #cop-panel {
        left: 50%; right: auto; top: 50%; bottom: auto;
        width: min(420px, calc(100vw - 28px));
        max-height: min(76dvh, calc(100dvh - 96px));
        transform-origin: center;
        transform: translate(-50%, calc(-50% + 14px)) scale(.94);
    }
    #cop-panel.abierto { transform: translate(-50%, -50%) scale(1); }
    body.cop-abierto #cop-fab { display: none; }
}
/* ── Con un modal abierto, el copiloto se retira en móvil ──
   La clase ms-modal-abierto la pone/quita modal-sidebar-fix.js al detectar
   cualquier modal visible (el panel del propio copiloto queda excluido por su
   data-ms-no-modal, así que abrirlo no lo oculta a sí mismo). Al cerrarse
   el modal, el widget —y el panel, si estaba abierto— reaparecen solos. */
@media (max-width: 1024px) {
    body.ms-modal-abierto #cop-fab,
    body.ms-modal-abierto #cop-backdrop,
    body.ms-modal-abierto #cop-panel {
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
            Hola 👋 Preg&uacute;ntame sobre tu operaci&oacute;n o c&oacute;mo hacer algo.
            <div class="cop-sugerencias" style="margin-top:10px;">
                <?php foreach ($copChips as $copChip): ?>
                <button type="button" class="cop-chip" data-q="<?= htmlspecialchars($copChip[1], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($copChip[0], ENT_QUOTES, 'UTF-8') ?></button>
                <?php endforeach; ?>
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
    function abrir(v) {
        panel.classList.toggle('abierto', v);
        document.body.classList.toggle('cop-abierto', v);
        if (v) {
            marcarDescubierto();
            setTimeout(function () { input.focus(); }, 50);
        }
    }
    fab.addEventListener('click', function () { abrir(!panel.classList.contains('abierto')); });
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
        agregar('user', escapar(texto));
        input.value = '';
        var pensando = agregar('bot cop-loading',
            '<div class="cop-skeleton" role="status" aria-label="Pensando">' +
                '<span class="cop-sk-line"></span>' +
                '<span class="cop-sk-line"></span>' +
                '<span class="cop-sk-line"></span>' +
            '</div>');

        var datos = new URLSearchParams();
        datos.append('csrf_token', TOKEN);
        datos.append('pregunta', texto);
        datos.append('ruta', RUTA);
        datos.append('intent_previo', intentPrevio);
        datos.append('historial', JSON.stringify(historial.slice(-6)));

        fetch(URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json', 'X-CSRF-Token': TOKEN },
            body: datos.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                intentPrevio = typeof data.intent === 'string' ? data.intent : '';
                historial.push({ r: 'u', t: texto });
                if (typeof data.texto === 'string' && data.texto) {
                    historial.push({ r: 'a', t: data.texto.slice(0, 600) });
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
        burbuja.classList.remove('cop-loading');
        burbuja.innerHTML = '<span class="cop-reveal">' + html + '</span>';
        body.scrollTop = body.scrollHeight;
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
            ? window.msConfirm({ type: 'info', title: accion.confirm_titulo || '¿Confirmar accion?', msg: accion.confirm_msg || '', confirmLabel: accion.confirm_ok || 'Confirmar' })
            : Promise.resolve(window.confirm(accion.confirm_msg || '¿Confirmar accion?'));

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
                        crear_cupon: ['Cupón creado', 'Ya está activo en el motor de reservas. 🎟️'],
                        pagar_proveedor: ['Pago registrado', 'El egreso quedó en la caja y el saldo se actualizó. 🤝'],
                        asignar_limpieza: ['Limpieza asignada', 'La tarea quedó en el tablero de limpieza. 🗓️']
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

    form.addEventListener('submit', function (e) { e.preventDefault(); preguntar(input.value); });
    body.addEventListener('click', function (e) {
        var chip = e.target.closest('.cop-chip');
        if (chip) { preguntar(chip.getAttribute('data-q')); }
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
    })();
})();
</script>
