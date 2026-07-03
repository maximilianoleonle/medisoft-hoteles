<?php
/**
 * Toast / alerta flotante global (siempre visible, no se pierde con el scroll).
 *
 * Dos formas de uso:
 *  1) Mensajes flash del servidor: get_mensaje() se renderiza automáticamente.
 *  2) Desde JS en cualquier vista:  window.msToast(tipo, titulo, mensaje, opts)
 *     - tipo: 'success' | 'error' | 'warning' | 'info'
 *     - titulo: string | null  (null = título por tipo)
 *     - mensaje: string
 *     - opts: { duration: ms }
 *
 * No cambia la lógica de set_mensaje(). Diseño "boutique" Medisoft (móvil-first).
 */

$msToastMensaje = function_exists('get_mensaje') ? get_mensaje() : null;
$msToastServer = null;

if ($msToastMensaje) {
    if (is_string($msToastMensaje)) {
        $msToastMensaje = ['texto' => $msToastMensaje, 'tipo' => 'info'];
    }

    // El header consume el flash antes de que corra la vista; se expone aquí
    // para que las vistas puedan reaccionar (p.ej. pantalla de check-in exitoso).
    $GLOBALS['ms_flash_consumido'] = $msToastMensaje;

    $msTexto = $msToastMensaje['texto'] ?? 'Operación realizada';
    $msTipo  = $msToastMensaje['tipo']  ?? 'info';

    if (!in_array($msTipo, ['success', 'error', 'warning', 'info'], true)) {
        $msTipo = $msTipo === 'danger' ? 'error' : ($msTipo === 'warn' ? 'warning' : 'info');
    }

    $msTitulos = ['success' => 'Éxito', 'error' => 'Error', 'warning' => 'Atención', 'info' => 'Información'];
    $msIconos = [
        'success' => '<path d="M5 12.5 10 17 19 7"/>',
        'error'   => '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
        'warning' => '<path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4M12 17.5v.5"/>',
        'info'    => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.5"/>',
    ];

    // Escapar todo y reintroducir solo <br> (los mensajes multi-error usan <br>).
    $msMsgHtml = htmlspecialchars($msTexto, ENT_QUOTES, 'UTF-8');
    $msMsgHtml = str_replace(['&lt;br&gt;', '&lt;br/&gt;', '&lt;br /&gt;'], '<br>', $msMsgHtml);

    $msToastServer = [
        'tipo'  => $msTipo,
        'titulo' => $msTitulos[$msTipo],
        'icono' => $msIconos[$msTipo],
        'msg'   => $msMsgHtml,
        'role'  => in_array($msTipo, ['error', 'warning'], true) ? 'alert' : 'status',
        'dur'   => in_array($msTipo, ['error', 'warning'], true) ? 8000 : 5000,
    ];
}
?>
<div id="ms-toast-stack" aria-live="polite" aria-atomic="true">
    <?php if ($msToastServer): ?>
    <div class="ms-toast" data-type="<?= $msToastServer['tipo'] ?>" data-dur="<?= (int) $msToastServer['dur'] ?>" role="<?= $msToastServer['role'] ?>">
        <span class="ms-toast-ic" aria-hidden="true"><svg viewBox="0 0 24 24"><?= $msToastServer['icono'] ?></svg></span>
        <div class="ms-toast-body">
            <div class="ms-toast-t"><?= $msToastServer['titulo'] ?></div>
            <div class="ms-toast-m"><?= $msToastServer['msg'] ?></div>
        </div>
        <button type="button" class="ms-toast-x" aria-label="Cerrar aviso" onclick="msToastClose(this.closest('.ms-toast'))">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
        <span class="ms-toast-bar" aria-hidden="true"></span>
    </div>
    <?php endif; ?>
</div>

<style>
/* ── Móvil: toasts en la PARTE INFERIOR (no choca con el header) ── */
#ms-toast-stack{
    position:fixed; z-index:10010;
    bottom:calc(env(safe-area-inset-bottom, 0px) + 14px);
    left:10px; right:10px;
    display:flex; flex-direction:column-reverse; gap:8px;
    pointer-events:none;
}
/* ── Desktop: debajo del header sticky (~64px) ── */
@media (min-width:1025px){
    #ms-toast-stack{
        top:70px; bottom:auto;
        right:18px; left:auto;
        width:380px; max-width:calc(100vw - 36px);
        flex-direction:column;
    }
}
.ms-toast{
    pointer-events:auto;
    display:flex; align-items:center; gap:11px;
    background:#FFFFFF; border:1px solid #ECE5D8; border-left:4px solid var(--ms-c, #0E96B8);
    border-radius:14px; padding:12px 13px;
    box-shadow:0 10px 30px rgba(27,39,70,.16);
    position:relative; overflow:hidden;
    font-family:'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    animation:msToastIn .38s cubic-bezier(.22,1,.36,1);
}
/* Móvil entra desde abajo, desktop desde arriba */
@keyframes msToastIn{ from{ opacity:0; transform:translateY(18px) scale(.96); } to{ opacity:1; transform:none; } }
@media (min-width:1025px){
    @keyframes msToastIn{ from{ opacity:0; transform:translateY(-14px) scale(.96); } to{ opacity:1; transform:none; } }
}
.ms-toast.ms-out{ animation:msToastOut .28s ease forwards; }
@keyframes msToastOut{ to{ opacity:0; transform:translateY(12px) scale(.97); } }
@media (min-width:1025px){
    .ms-toast.ms-out{ animation:msToastOut-up .28s ease forwards; }
    @keyframes msToastOut-up{ to{ opacity:0; transform:translateY(-10px) scale(.97); } }
}

.ms-toast-ic{ width:34px; height:34px; border-radius:10px; background:var(--ms-bg, #E2F2F6); color:var(--ms-c, #0E96B8); display:grid; place-items:center; flex:none; }
.ms-toast-ic svg{ width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
.ms-toast-body{ flex:1; min-width:0; }
.ms-toast-t{ font-size:13.5px; font-weight:700; color:#1B2746; line-height:1.2; }
.ms-toast-m{ font-size:12px; color:#6C7689; margin-top:1px; line-height:1.45; overflow-wrap:anywhere; }
.ms-toast-m:empty{ display:none; }
.ms-toast-x{ width:26px; height:26px; border-radius:7px; border:0; background:transparent; color:#939BAD; cursor:pointer; display:grid; place-items:center; flex:none; transition:background .15s, color .15s; }
.ms-toast-x:hover{ background:#F1ECE2; color:#6C7689; }
.ms-toast-x svg{ width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
.ms-toast-bar{ position:absolute; left:0; bottom:0; height:3px; width:100%; background:var(--ms-c, #0E96B8); border-radius:99px; }
.ms-toast-bar.run{ animation:msToastBar var(--ms-dur, 5s) linear forwards; }
@keyframes msToastBar{ from{ width:100%; } to{ width:0%; } }

.ms-toast[data-type="success"]{ --ms-c:#1E9E63; --ms-bg:#E7F4EC; }
.ms-toast[data-type="error"]{   --ms-c:#D64539; --ms-bg:#FBE9E7; }
.ms-toast[data-type="warning"]{ --ms-c:#C2841C; --ms-bg:#FAF0DC; }
.ms-toast[data-type="info"]{    --ms-c:#0E96B8; --ms-bg:#E2F2F6; }

@media (prefers-reduced-motion: reduce){
    .ms-toast{ animation:none; }
    .ms-toast-bar.run{ animation:none; }
}
</style>

<script>
(function(){
    var ICONS = {
        success: '<path d="M5 12.5 10 17 19 7"/>',
        error:   '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
        warning: '<path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4M12 17.5v.5"/>',
        info:    '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.5"/>'
    };
    var TITLES = { success:'Éxito', error:'Error', warning:'Atención', info:'Información' };
    var CLOSE = '<path d="M6 6l12 12M18 6 6 18"/>';
    var MAX = 4;

    function close(t){
        if(!t || t.classList.contains('ms-out')) return;
        t.classList.add('ms-out');
        setTimeout(function(){ if(t && t.parentNode) t.parentNode.removeChild(t); }, 300);
    }
    window.msToastClose = close;

    function ensureStack(){
        var s = document.getElementById('ms-toast-stack');
        if(!s){
            s = document.createElement('div');
            s.id = 'ms-toast-stack';
            s.setAttribute('aria-live', 'polite');
            s.setAttribute('aria-atomic', 'true');
            (document.body || document.documentElement).appendChild(s);
        }
        return s;
    }

    // Arranca barra de progreso + auto-cierre con pausa al hover/touch.
    function hydrate(t){
        var dur = parseInt(t.getAttribute('data-dur') || '5000', 10);
        var bar = t.querySelector('.ms-toast-bar');
        if(bar){ bar.style.setProperty('--ms-dur', dur + 'ms'); bar.classList.add('run'); }
        var timer = setTimeout(function(){ close(t); }, dur + 250);
        var pause = function(){ clearTimeout(timer); if(bar) bar.style.animationPlayState = 'paused'; };
        var resume = function(){ if(bar) bar.style.animationPlayState = 'running'; timer = setTimeout(function(){ close(t); }, 2000); };
        t.addEventListener('mouseenter', pause);
        t.addEventListener('mouseleave', resume);
        t.addEventListener('touchstart', pause, {passive:true});
    }

    function svg(paths){ return '<svg viewBox="0 0 24 24" aria-hidden="true">' + paths + '</svg>'; }

    // API pública: crea un toast desde JS con el mismo diseño boutique.
    window.msToast = function(tipo, titulo, mensaje, opts){
        opts = opts || {};
        if(!TITLES[tipo]) tipo = 'info';
        var dur = opts.duration || ((tipo === 'error' || tipo === 'warning') ? 8000 : 5000);
        var role = (tipo === 'error' || tipo === 'warning') ? 'alert' : 'status';

        var t = document.createElement('div');
        t.className = 'ms-toast';
        t.setAttribute('data-type', tipo);
        t.setAttribute('data-dur', dur);
        t.setAttribute('role', role);

        var ic = document.createElement('span');
        ic.className = 'ms-toast-ic'; ic.setAttribute('aria-hidden', 'true');
        ic.innerHTML = svg(ICONS[tipo]);

        var body = document.createElement('div');
        body.className = 'ms-toast-body';
        var tt = document.createElement('div'); tt.className = 'ms-toast-t';
        tt.textContent = (titulo != null && titulo !== '') ? titulo : TITLES[tipo];
        var mm = document.createElement('div'); mm.className = 'ms-toast-m';
        mm.textContent = mensaje || '';   // textContent = XSS-safe
        body.appendChild(tt); body.appendChild(mm);

        var x = document.createElement('button');
        x.type = 'button'; x.className = 'ms-toast-x'; x.setAttribute('aria-label', 'Cerrar aviso');
        x.innerHTML = svg(CLOSE);
        x.addEventListener('click', function(){ close(t); });

        var bar = document.createElement('span');
        bar.className = 'ms-toast-bar'; bar.setAttribute('aria-hidden', 'true');

        t.appendChild(ic); t.appendChild(body); t.appendChild(x); t.appendChild(bar);

        var stack = ensureStack();
        stack.appendChild(t);
        // Limitar la pila: descartar los más viejos.
        var items = stack.querySelectorAll('.ms-toast');
        for(var i = 0; i <= items.length - MAX - 1; i++){ if(items[i]) items[i].remove(); }
        hydrate(t);
        return t;
    };

    function initServer(){
        var stack = document.getElementById('ms-toast-stack');
        if(!stack) return;
        stack.querySelectorAll('.ms-toast').forEach(hydrate);
    }

    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initServer);
    else initServer();
})();
</script>
