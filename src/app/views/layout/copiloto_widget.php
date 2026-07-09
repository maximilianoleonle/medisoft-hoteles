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
?>
<style>
#cop-fab { position: fixed; bottom: calc(28px + env(safe-area-inset-bottom, 0px)); right: calc(22px + env(safe-area-inset-right, 0px)); z-index: 10000; width: 64px; max-width: calc(100vw - 44px); height: 64px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 14%, #FFFFFF); cursor: pointer; background: #fff; padding: 0; box-shadow: 0 12px 30px -8px rgba(20,28,45,.58); display: flex; align-items: center; justify-content: flex-start; transition: width .22s ease, transform .12s ease, box-shadow .18s ease; overflow: visible; isolation: isolate; }
#cop-fab:hover,
#cop-fab:focus-visible,
#cop-fab.cop-fab-discover { width: min(218px, calc(100vw - 44px)); box-shadow: 0 16px 34px -9px rgba(20,28,45,.62); }
#cop-fab:hover { transform: translateY(-1px) scale(1.03); }
#cop-fab::after { content: ''; position: absolute; inset: -7px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--brand-accent, #BD9441) 32%, transparent); opacity: 0; pointer-events: none; z-index: -1; }
#cop-fab.cop-fab-attention::after { animation: cop-fab-ring 2.4s ease-out .9s 1; }
.cop-fab-mark { position: relative; width: 64px; height: 64px; min-width: 64px; border-radius: 50%; display: grid; place-items: center; overflow: hidden; background: #fff; }
.cop-logo-img { width: 100%; height: 100%; object-fit: contain; display: block; opacity: 1; transform-origin: center; transition: opacity .18s ease; }
.cop-logo-img-noche { position: absolute; inset: 0; opacity: 0; pointer-events: none; }
.cop-fab-mark .cop-logo-img { transform: scale(2.05); }
.cop-fab-label { color: var(--brand-primary, #1B2746); font-size: .88rem; font-weight: 800; line-height: 1; white-space: nowrap; padding: 0 18px 0 2px; opacity: 0; transform: translateX(-8px); transition: opacity .18s ease, transform .18s ease; }
#cop-fab:hover .cop-fab-label,
#cop-fab:focus-visible .cop-fab-label,
#cop-fab.cop-fab-discover .cop-fab-label { opacity: 1; transform: translateX(0); }
html[data-theme="dark"] #cop-fab { background: #171612; border-color: rgba(239,233,220,.14); box-shadow: 0 16px 34px -12px rgba(0,0,0,.72); }
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
#cop-panel { position: fixed; bottom: 84px; right: 18px; z-index: 10000; width: min(380px, calc(100vw - 32px)); max-height: min(560px, calc(100vh - 120px)); background: #fff; border: 1px solid #E1DED4; border-radius: 16px; box-shadow: 0 24px 60px -20px rgba(20,28,45,.5); display: none; flex-direction: column; overflow: hidden; }
#cop-panel.abierto { display: flex; }
.cop-head { padding: 14px 16px; background: var(--brand-primary, #1B2746); color: #fff; display: flex; align-items: center; gap: 8px; }
.cop-logo-mark { position: relative; width: 34px; height: 34px; border-radius: 10px; background: rgba(255,255,255,.18); display: grid; place-items: center; flex: none; overflow: hidden; }
.cop-logo-mark .cop-logo-img { transform: scale(1.78); }
html[data-theme="dark"] .cop-logo-mark { background: rgba(0,0,0,.18); }
.cop-head strong { font-size: .95rem; }
.cop-head .cop-sub { font-size: .72rem; opacity: .8; }
.cop-close { margin-left: auto; background: none; border: 0; color: #fff; font-size: 1.3rem; cursor: pointer; line-height: 1; }
.cop-body { flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 10px; background: #FAF9F5; }
.cop-msg { max-width: 90%; padding: 9px 12px; border-radius: 12px; font-size: .88rem; line-height: 1.5; }
.cop-msg.user { align-self: flex-end; background: var(--brand-primary, #1B2746); color: #fff; border-bottom-right-radius: 3px; }
.cop-msg.bot { align-self: flex-start; background: #fff; border: 1px solid #EBE7DC; color: #2A3242; border-bottom-left-radius: 3px; }
.cop-fuente { display: inline-block; margin-top: 6px; font-size: .68rem; font-weight: 700; padding: 1px 7px; border-radius: 999px; }
.cop-fuente.reglas { background: rgba(22,163,74,.12); color: #15803D; }
.cop-fuente.ia { background: rgba(124,58,237,.12); color: #6D28D9; }
.cop-fuente.fallback { background: rgba(100,116,139,.12); color: #64748B; }
.cop-enlace { display: inline-block; margin-top: 8px; font-size: .8rem; font-weight: 700; color: var(--brand-primary, #1B2746); text-decoration: none; padding: 5px 10px; border: 1px solid #D8D4C9; border-radius: 8px; }
.cop-link { color: var(--brand-primary, #1B2746); font-weight: 700; text-decoration: underline; text-underline-offset: 2px; cursor: pointer; }
.cop-link:hover { opacity: .8; }
.cop-sugerencias { display: flex; flex-wrap: wrap; gap: 6px; }
.cop-chip { border: 1px solid #D8D4C9; background: #fff; border-radius: 999px; padding: 5px 11px; font-size: .78rem; cursor: pointer; color: #55607A; }
.cop-chip:hover { border-color: var(--brand-primary, #1B2746); color: var(--brand-primary, #1B2746); }
.cop-foot { padding: 10px; border-top: 1px solid #EBE7DC; display: flex; gap: 8px; background: #fff; }
.cop-foot input { flex: 1; min-height: 42px; border: 1px solid #D8D4C9; border-radius: 10px; padding: 0 12px; font-size: .9rem; }
.cop-foot button { min-height: 42px; padding: 0 14px; border: 0; border-radius: 10px; background: var(--brand-primary, #1B2746); color: #fff; font-weight: 700; cursor: pointer; }
.cop-foot button:disabled { opacity: .55; cursor: wait; }
.cop-typing { font-size: .82rem; color: #8A93A6; font-style: italic; }
@media (prefers-reduced-motion: reduce) {
    #cop-fab,
    #cop-fab::after,
    .cop-fab-label,
    .cop-logo-img { animation: none !important; transition: none !important; }
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
    el ocultador global lo ignora por el atributo data-ms-keep-sidebar del panel.) */
#cop-backdrop { display: none; }
@media (max-width: 640px) {
    #cop-backdrop {
        position: fixed; inset: 0; z-index: 9990;
        background: rgba(20,28,45,.20);
        -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px);
        opacity: 0; visibility: hidden;
        transition: opacity .22s ease, visibility .22s ease;
    }
    body.cop-abierto #cop-backdrop { display: block; opacity: 1; visibility: visible; }

    /* Centrar el panel y ocultar el FAB de esquina para enfocarse en el copiloto */
    body.cop-abierto #cop-panel.abierto {
        left: 50%; right: auto; top: 50%; bottom: auto;
        transform: translate(-50%, -50%);
        width: min(420px, calc(100vw - 28px));
        max-height: min(74dvh, calc(100dvh - 96px));
    }
    body.cop-abierto #cop-fab { display: none; }
}
</style>

<button id="cop-fab" class="cop-fab-discover cop-fab-attention" type="button" aria-label="Abrir asesor inteligente">
    <span class="cop-fab-mark" aria-hidden="true">
        <img class="cop-logo-img cop-logo-img-dia" src="<?= htmlspecialchars($copilotoLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="64" height="64" decoding="async">
        <img class="cop-logo-img cop-logo-img-noche" src="<?= htmlspecialchars($copilotoLogoNocheUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="64" height="64" decoding="async">
    </span>
    <span class="cop-fab-label" aria-hidden="true">Asesor inteligente</span>
</button>
<div id="cop-backdrop" aria-hidden="true"></div>
<div id="cop-panel" role="dialog" aria-label="Copiloto Medisoft" data-ms-keep-sidebar>
    <div class="cop-head">
        <span class="cop-logo-mark" aria-hidden="true">
            <img class="cop-logo-img cop-logo-img-dia" src="<?= htmlspecialchars($copilotoLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="34" height="34" decoding="async">
            <img class="cop-logo-img cop-logo-img-noche" src="<?= htmlspecialchars($copilotoLogoNocheUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="34" height="34" decoding="async">
        </span>
        <div>
            <strong>Copiloto</strong>
            <div class="cop-sub">Pregunta sobre tu hotel</div>
        </div>
        <button type="button" class="cop-close" id="cop-close" aria-label="Cerrar">&times;</button>
    </div>
    <div class="cop-body" id="cop-body">
        <div class="cop-msg bot">
            Hola 👋 Preg&uacute;ntame sobre tu operaci&oacute;n o c&oacute;mo hacer algo.
            <div class="cop-sugerencias" style="margin-top:10px;">
                <button type="button" class="cop-chip" data-q="Dame el resumen del día">📋 Resumen del día</button>
                <button type="button" class="cop-chip" data-q="¿Cuántas habitaciones libres tengo hoy?">Habitaciones libres</button>
                <button type="button" class="cop-chip" data-q="¿Quién llega hoy?">Llegadas de hoy</button>
                <button type="button" class="cop-chip" data-q="¿Cómo pinta la semana?">¿Cómo pinta la semana?</button>
                <button type="button" class="cop-chip" data-q="¿Cómo voy de caja?">Estado de caja</button>
                <button type="button" class="cop-chip" data-q="¿Cómo hago un corte de caja?">¿Cómo hago un corte?</button>
            </div>
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
    var TOKEN = <?= json_encode($copilotoToken) ?>;
    var SECCIONES = <?= json_encode($copSecciones, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var ocupado = false;
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
        var pensando = agregar('bot', '<span class="cop-typing">Pensando...</span>');

        var datos = new URLSearchParams();
        datos.append('csrf_token', TOKEN);
        datos.append('pregunta', texto);

        fetch(URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json', 'X-CSRF-Token': TOKEN },
            body: datos.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var html = formato(data.texto || 'No pude responder.');
                var fuente = data.fuente || 'fallback';
                var etiqueta = fuente === 'reglas' ? '⚡ Instantáneo' : (fuente === 'ia' ? '✨ Asistida' : '💡 Sugerencia');
                html += '<br><span class="cop-fuente ' + fuente + '">' + etiqueta + '</span>';
                if (data.enlace && data.enlace.url) {
                    html += '<br><a class="cop-enlace" href="' + escapar(data.enlace.url.charAt(0) === '/' ? data.enlace.url : (data.enlace.url)) + '">' + escapar(data.enlace.texto || 'Abrir') + ' →</a>';
                }
                pensando.innerHTML = html;
                // Los enlaces vienen como ruta relativa del sistema; resolverlos con el base.
                var a = pensando.querySelector('.cop-enlace');
                if (a && data.enlace && data.enlace.url && data.enlace.url.charAt(0) !== '/') {
                    a.setAttribute('href', <?= json_encode(function_exists('url') ? rtrim(url(''), '/') . '/' : '/') ?> + data.enlace.url);
                }
                body.scrollTop = body.scrollHeight;
            })
            .catch(function () {
                pensando.innerHTML = 'No pude responder ahora mismo. Intenta de nuevo.';
            })
            .finally(function () {
                ocupado = false;
                sendBtn.disabled = false;
                input.focus();
            });
    }

    form.addEventListener('submit', function (e) { e.preventDefault(); preguntar(input.value); });
    body.addEventListener('click', function (e) {
        var chip = e.target.closest('.cop-chip');
        if (chip) { preguntar(chip.getAttribute('data-q')); }
    });
})();
</script>
