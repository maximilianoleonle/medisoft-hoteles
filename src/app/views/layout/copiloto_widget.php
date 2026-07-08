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

/**
 * Diccionario de secciones para hipervincular las negritas del chat. Consciente
 * de modulos: solo se vuelven enlace las secciones que ESTE hotel tiene activas.
 * Clave = nombre normalizado (minusculas, sin acentos); valor = URL absoluta.
 * Algunas llevan #ancla para hacer scroll a la accion exacta al llegar.
 */
$copU = function ($ruta) { return function_exists('url') ? url($ruta) : '/' . ltrim($ruta, '/'); };
$copMod = function ($clave) { return function_exists('hotel_menu_module_enabled') ? hotel_menu_module_enabled($clave) : true; };

$copSecciones = [
    // Core: siempre disponibles.
    'caja' => $copU('caja') . '#cop-ancla-corte',
    'reservaciones' => $copU('reservaciones'),
    'habitaciones' => $copU('habitaciones'),
];
$copOpcionales = [
    'motor_reservas' => ['motor de reservas' => $copU('motor-reservas')],
    'promociones' => ['cupones' => $copU('motor-reservas/cupones')],
    'upsells' => ['extras' => $copU('motor-reservas/extras')],
    'reputacion' => ['reputacion' => $copU('reputacion')],
    'lealtad' => ['huesped frecuente' => $copU('lealtad')],
    'forecast' => ['forecast' => $copU('forecast')],
    'night_audit' => ['night audit' => $copU('night-audit')],
    'auditoria' => ['bitacora' => $copU('auditoria')],
    'ia_ejecutiva' => ['asesor ia' => $copU('ia/resumen-diario')],
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
#cop-fab { position: fixed; bottom: 18px; right: 18px; z-index: 10000; width: 56px; height: 56px; border-radius: 50%; border: 0; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: 1.5rem; box-shadow: 0 10px 28px -8px rgba(20,28,45,.55); display: grid; place-items: center; transition: transform .12s ease; }
#cop-fab:hover { transform: scale(1.06); }
#cop-panel { position: fixed; bottom: 84px; right: 18px; z-index: 10000; width: min(380px, calc(100vw - 32px)); max-height: min(560px, calc(100vh - 120px)); background: #fff; border: 1px solid #E1DED4; border-radius: 16px; box-shadow: 0 24px 60px -20px rgba(20,28,45,.5); display: none; flex-direction: column; overflow: hidden; }
#cop-panel.abierto { display: flex; }
.cop-head { padding: 14px 16px; background: var(--brand-primary, #1B2746); color: #fff; display: flex; align-items: center; gap: 8px; }
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
</style>

<button id="cop-fab" type="button" aria-label="Abrir copiloto">🤖</button>
<div id="cop-panel" role="dialog" aria-label="Copiloto Medisoft">
    <div class="cop-head">
        <span style="font-size:1.2rem;">🤖</span>
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
                <button type="button" class="cop-chip" data-q="¿Cuántas habitaciones libres tengo hoy?">Habitaciones libres</button>
                <button type="button" class="cop-chip" data-q="¿Cómo voy de caja?">Estado de caja</button>
                <button type="button" class="cop-chip" data-q="¿Quién llega hoy?">Llegadas de hoy</button>
                <button type="button" class="cop-chip" data-q="¿Hay checkouts vencidos?">Checkouts vencidos</button>
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

    // Normaliza a minusculas sin acentos, para casar "Reputación" con "reputacion".
    function norm(s) {
        return String(s).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    }

    function abrir(v) {
        panel.classList.toggle('abierto', v);
        if (v) { setTimeout(function () { input.focus(); }, 50); }
    }
    fab.addEventListener('click', function () { abrir(!panel.classList.contains('abierto')); });
    closeBtn.addEventListener('click', function () { abrir(false); });

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
                var etiqueta = fuente === 'reglas' ? '⚡ Instantáneo' : (fuente === 'ia' ? '✨ IA' : '💡 Sugerencia');
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
