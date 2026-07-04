<?php
/**
 * Modal de confirmación global (boutique) — PC: modal centrado / Móvil: hoja inferior.
 *
 * Uso desde JS:
 *   msConfirm({ type, icon, title, msg, confirmLabel, cancelLabel })
 *     .then(function(ok){ if (ok) ... });
 *   - type: 'error' (destructivo) | 'warning' (precaución) | 'success' | 'info'
 *   - icon (opcional): 'trash' | 'logout' | 'login' | 'check' | 'alert' | 'info' | 'x' | 'key' | 'wallet'
 *
 * Uso declarativo en forms (sin tocar action/method/CSRF):
 *   <form ... data-ms-confirm data-ms-type="error" data-ms-icon="trash"
 *         data-ms-title="¿Eliminar?" data-ms-msg="Esta acción no se puede deshacer."
 *         data-ms-ok="Sí, eliminar">
 *   El submit se intercepta, se muestra el modal y al aceptar se envía el form.
 *
 * Estado de página completa (éxito/error tras una operación):
 *   msPageState({ type, icon, title, msg,
 *                 primary:{ label, href|onclick }, secondary:{ label, href|onclick } });
 */
?>
<style>
.ms-cf-ov{ position:fixed; inset:0; z-index:10005; background:rgba(18,22,34,.5);
    -webkit-backdrop-filter:blur(3px); backdrop-filter:blur(3px);
    display:flex; align-items:flex-end; justify-content:center;
    opacity:0; visibility:hidden; transition:opacity .22s ease, visibility .22s ease; }
.ms-cf-ov.open{ opacity:1; visibility:visible; }

/* ── Móvil: hoja inferior (action sheet) ── */
.ms-cf-modal{ width:100%; background:#FFFFFF; border-radius:24px 24px 0 0;
    box-shadow:0 -12px 40px rgba(18,22,34,.2);
    padding:0 20px calc(env(safe-area-inset-bottom, 0px) + 20px); text-align:center;
    transform:translateY(102%); transition:transform .34s cubic-bezier(.22,1,.36,1);
    font-family:'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; }
.ms-cf-ov.open .ms-cf-modal{ transform:none; }
.ms-cf-grab{ width:40px; height:5px; border-radius:99px; background:#ECE5D8; margin:10px auto 16px; }
.ms-cf-ic{ width:58px; height:58px; border-radius:17px; background:var(--ms-bg,#FAF0DC); color:var(--ms-c,#C2841C);
    display:grid; place-items:center; margin:0 auto 14px; }
.ms-cf-ic svg{ width:28px; height:28px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
.ms-cf-t{ font-family:'Cormorant Garamond', Georgia, serif; font-size:22px; font-weight:600; color:#1B2746; line-height:1.1; }
.ms-cf-m{ font-size:13.5px; color:#6C7689; line-height:1.55; margin-top:8px; }
.ms-cf-x{ display:none; }
.ms-cf-btns{ display:flex; flex-direction:column; gap:9px; margin-top:20px; }
.ms-cf-btn{ font-family:inherit; font-size:14px; font-weight:700; padding:13px 18px; border-radius:13px;
    border:1px solid transparent; cursor:pointer; transition:transform .14s, filter .14s;
    display:inline-flex; align-items:center; justify-content:center; gap:8px; width:100%; }
.ms-cf-btn:active{ transform:scale(.98); }
.ms-cf-btn.ok{ background:var(--ms-c,#C2841C); color:#fff; }
.ms-cf-btn.ok:hover{ filter:brightness(1.06); }
.ms-cf-btn.ghost{ background:#FFFFFF; color:#3E4A66; border-color:#ECE5D8; }
.ms-cf-btn.ghost:hover{ border-color:#E4D4B0; color:#1B2746; }

/* ── PC: modal centrado, icono a la izquierda ── */
@media (min-width:1025px){
    .ms-cf-ov{ align-items:center; padding:30px; }
    .ms-cf-modal{ width:440px; max-width:100%; border-radius:22px; padding:0; text-align:left;
        box-shadow:0 18px 48px rgba(27,39,70,.12);
        transform:translateY(16px) scale(.97); overflow:hidden; }
    .ms-cf-grab{ display:none; }
    .ms-cf-top{ display:flex; align-items:flex-start; gap:15px; padding:24px 24px 18px; }
    .ms-cf-ic{ width:50px; height:50px; border-radius:15px; margin:0; flex:none; }
    .ms-cf-ic svg{ width:25px; height:25px; }
    .ms-cf-t{ font-size:23px; }
    .ms-cf-m{ margin-top:7px; }
    .ms-cf-x{ display:grid; margin-left:auto; width:32px; height:32px; border-radius:9px;
        background:#FEFCF7; border:1px solid #ECE5D8; cursor:pointer; place-items:center; color:#6C7689; flex:none; }
    .ms-cf-x svg{ width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; }
    .ms-cf-btns{ flex-direction:row; gap:11px; margin-top:0; padding:16px 24px; background:#FEFCF7; border-top:1px solid #ECE5D8; }
    .ms-cf-btns .ms-cf-btn{ flex:1; width:auto; border-radius:12px; }
    /* En PC el orden es Cancelar | Confirmar */
    .ms-cf-btns .ms-cf-btn.ghost{ order:0; }
    .ms-cf-btns .ms-cf-btn.ok{ order:1; }
}

.ms-cf-ov[data-type="error"] .ms-cf-modal{   --ms-c:#D64539; --ms-bg:#FBE9E7; }
.ms-cf-ov[data-type="warning"] .ms-cf-modal{ --ms-c:#C2841C; --ms-bg:#FAF0DC; }
.ms-cf-ov[data-type="success"] .ms-cf-modal{ --ms-c:#1E9E63; --ms-bg:#E7F4EC; }
.ms-cf-ov[data-type="info"] .ms-cf-modal{    --ms-c:#0E96B8; --ms-bg:#E2F2F6; }

/* ── Estado de página completa ── */
.ms-pagestate-modal{ position:fixed; inset:0; z-index:10004; background:#F6F2EA;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    text-align:center; padding:40px 34px; animation:msPsIn .3s ease;
    font-family:'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; }
@keyframes msPsIn{ from{ opacity:0; } to{ opacity:1; } }
.ms-ps-ic{ width:96px; height:96px; border-radius:28px; background:var(--ms-bg,#E7F4EC); color:var(--ms-c,#1E9E63);
    display:grid; place-items:center; position:relative; margin-bottom:24px;
    animation:msPsPop .5s cubic-bezier(.34,1.56,.64,1); }
@keyframes msPsPop{ from{ transform:scale(.4); opacity:0; } to{ transform:scale(1); opacity:1; } }
.ms-ps-ic svg{ width:46px; height:46px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; position:relative; }
.ms-ps-pulse{ position:absolute; inset:0; border-radius:28px; border:2px solid var(--ms-c,#1E9E63);
    animation:msPsPulse 2s ease-out infinite; opacity:0; }
@keyframes msPsPulse{ 0%{ transform:scale(1); opacity:.5; } 100%{ transform:scale(1.5); opacity:0; } }
.ms-ps-t{ font-family:'Cormorant Garamond', Georgia, serif; font-size:28px; font-weight:600; color:#1B2746; }
.ms-ps-m{ font-size:14px; color:#6C7689; line-height:1.6; margin-top:10px; max-width:320px; }
.ms-ps-btns{ display:flex; flex-direction:column; gap:10px; width:100%; max-width:340px; margin-top:28px; }
.ms-ps-btns .ms-cf-btn.ok{ background:var(--ms-c,#1E9E63); }
.ms-pagestate-modal[data-type="error"]{   --ms-c:#D64539; --ms-bg:#FBE9E7; }
.ms-pagestate-modal[data-type="success"]{ --ms-c:#1E9E63; --ms-bg:#E7F4EC; }
.ms-pagestate-modal[data-type="warning"]{ --ms-c:#C2841C; --ms-bg:#FAF0DC; }
.ms-pagestate-modal[data-type="info"]{    --ms-c:#0E96B8; --ms-bg:#E2F2F6; }
.ms-ps-x{ position:absolute; top:calc(env(safe-area-inset-top, 0px) + 18px); right:18px;
    width:38px; height:38px; border-radius:11px; background:#FFFFFF; border:1px solid #ECE5D8;
    display:grid; place-items:center; cursor:pointer; box-shadow:0 1px 2px rgba(27,39,70,.05); }
.ms-ps-x svg{ width:18px; height:18px; color:#1B2746; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; }
@media (min-width:1025px){
    .ms-ps-ic{ width:110px; height:110px; border-radius:32px; }
    .ms-ps-ic svg{ width:52px; height:52px; }
    .ms-ps-t{ font-size:34px; }
    .ms-ps-m{ font-size:15px; max-width:420px; }
    .ms-ps-btns{ flex-direction:row; justify-content:center; max-width:none; width:auto; }
    .ms-ps-btns .ms-cf-btn{ width:auto; padding:13px 22px; }
}

@media (prefers-reduced-motion: reduce){
    .ms-cf-ov, .ms-cf-modal, .ms-pagestate-modal, .ms-ps-ic{ transition:none; animation:none; }
    .ms-ps-pulse{ animation:none; }
}
</style>

<script>
(function(){
    'use strict';

    var ICONS = {
        check:  '<path d="M5 12.5 10 17 19 7"/>',
        x:      '<path d="M6 6l12 12M18 6 6 18"/>',
        alert:  '<path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4M12 17.5v.5"/>',
        info:   '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.5"/>',
        trash:  '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>',
        logout: '<path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M16 12h7m-7 0 4-4m-4 4 4 4"/>',
        login:  '<path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4M10 12H3m0 0 4-4m-4 4 4 4"/>',
        'wifi-off': '<path d="M3 3l18 18M8.5 12.5a6 6 0 0 1 5-1M5 9.5a11 11 0 0 1 4-2.3M19 9.5a11 11 0 0 0-3-2M11 16a2 2 0 0 1 2.5 0"/><circle cx="12" cy="20" r=".6" fill="currentColor"/>',
        key:    '<circle cx="8" cy="15" r="4"/><path d="M11 12 20 3m-3 3 3 3"/>',
        wallet: '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H18a2 2 0 0 1 2 2H5.5"/><rect x="3" y="7" width="18" height="12.5" rx="2.5"/><circle cx="16.5" cy="13.2" r="1.4" fill="currentColor" stroke="none"/>'
    };
    var TYPE_ICON = { error:'trash', warning:'alert', success:'check', info:'info' };
    var svg = function(p){ return '<svg viewBox="0 0 24 24" aria-hidden="true">' + p + '</svg>'; };

    var ov = null, resolver = null, lastFocus = null;

    function build(){
        if (ov) return ov;
        ov = document.createElement('div');
        ov.className = 'ms-cf-ov';
        ov.setAttribute('role', 'presentation');
        ov.innerHTML =
            '<div class="ms-cf-modal" role="alertdialog" aria-modal="true" aria-labelledby="ms-cf-t" aria-describedby="ms-cf-m">' +
                '<div class="ms-cf-grab" aria-hidden="true"></div>' +
                '<div class="ms-cf-top">' +
                    '<span class="ms-cf-ic" aria-hidden="true"></span>' +
                    '<div style="flex:1;min-width:0;">' +
                        '<div class="ms-cf-t" id="ms-cf-t"></div>' +
                        '<div class="ms-cf-m" id="ms-cf-m"></div>' +
                    '</div>' +
                    '<button type="button" class="ms-cf-x" aria-label="Cerrar">' + svg(ICONS.x) + '</button>' +
                '</div>' +
                '<div class="ms-cf-btns">' +
                    '<button type="button" class="ms-cf-btn ok"></button>' +
                    '<button type="button" class="ms-cf-btn ghost"></button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(ov);

        ov.addEventListener('click', function(e){ if (e.target === ov) settle(false); });
        ov.querySelector('.ms-cf-x').addEventListener('click', function(){ settle(false); });
        ov.querySelector('.ms-cf-btn.ghost').addEventListener('click', function(){ settle(false); });
        ov.querySelector('.ms-cf-btn.ok').addEventListener('click', function(){ settle(true); });
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape' && ov.classList.contains('open')) settle(false);
        });
        return ov;
    }

    function settle(ok){
        if (!ov || !ov.classList.contains('open')) return;
        ov.classList.remove('open');
        var r = resolver; resolver = null;
        if (lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch(e){} }
        lastFocus = null;
        if (r) r(ok);
    }

    window.msConfirm = function(opts){
        opts = opts || {};
        var o = build();
        var type = ({ error:1, warning:1, success:1, info:1 })[opts.type] ? opts.type : 'warning';
        o.setAttribute('data-type', type);
        o.querySelector('.ms-cf-ic').innerHTML = svg(ICONS[opts.icon] ? ICONS[opts.icon] : ICONS[TYPE_ICON[type]]);
        o.querySelector('.ms-cf-t').textContent = opts.title || '¿Confirmar acción?';
        o.querySelector('.ms-cf-m').textContent = opts.msg || '';
        o.querySelector('.ms-cf-btn.ok').textContent = opts.confirmLabel || 'Confirmar';
        o.querySelector('.ms-cf-btn.ghost').textContent = opts.cancelLabel || 'Cancelar';
        lastFocus = document.activeElement;

        return new Promise(function(resolve){
            // Si ya hay uno abierto, se cancela el anterior.
            if (resolver) { var prev = resolver; resolver = null; prev(false); }
            resolver = resolve;
            if (window.MedisoftHaptics) window.MedisoftHaptics.fire('tap');
            requestAnimationFrame(function(){
                o.classList.add('open');
                var okBtn = o.querySelector('.ms-cf-btn.ok');
                if (okBtn) okBtn.focus();
            });
        });
    };

    // ── Interceptor declarativo para forms con data-ms-confirm ──
    document.addEventListener('submit', function(e){
        var form = e.target;
        if (!form || !form.hasAttribute || !form.hasAttribute('data-ms-confirm')) return;
        if (form.__msConfirmed) { form.__msConfirmed = false; return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        window.msConfirm({
            type:  form.getAttribute('data-ms-type') || 'warning',
            icon:  form.getAttribute('data-ms-icon') || null,
            title: form.getAttribute('data-ms-title') || '¿Confirmar acción?',
            msg:   form.getAttribute('data-ms-msg') || '',
            confirmLabel: form.getAttribute('data-ms-ok') || 'Confirmar'
        }).then(function(ok){
            if (!ok) return;
            form.__msConfirmed = true;
            if (form.requestSubmit) form.requestSubmit();
            else form.submit();
        });
    }, true);

    // ── Estado de página completa ──
    window.msPageState = function(opts){
        opts = opts || {};
        var type = ({ error:1, warning:1, success:1, info:1 })[opts.type] ? opts.type : 'success';
        // Momento fuerte (check-in/out ok, error de operación): vibración según el tipo.
        if (window.MedisoftHaptics) window.MedisoftHaptics.fire(type);
        var root = document.createElement('div');
        root.className = 'ms-pagestate-modal';
        root.setAttribute('data-type', type);
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');

        var close = function(){ if (root.parentNode) root.parentNode.removeChild(root); };
        var mkBtn = function(cfg, cls){
            if (!cfg || !cfg.label) return null;
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'ms-cf-btn ' + cls;
            b.textContent = cfg.label;
            b.addEventListener('click', function(){
                if (cfg.href) { window.location.href = cfg.href; return; }
                if (typeof cfg.onclick === 'function') { cfg.onclick(); }
                close();
            });
            return b;
        };

        var ic = document.createElement('div');
        ic.className = 'ms-ps-ic';
        ic.innerHTML = '<span class="ms-ps-pulse" aria-hidden="true"></span>' + svg(ICONS[opts.icon] ? ICONS[opts.icon] : ICONS[TYPE_ICON[type]]);

        var t = document.createElement('div'); t.className = 'ms-ps-t'; t.textContent = opts.title || '';
        var m = document.createElement('div'); m.className = 'ms-ps-m'; m.textContent = opts.msg || '';
        var btns = document.createElement('div'); btns.className = 'ms-ps-btns';
        var p = mkBtn(opts.primary, 'ok');      if (p) btns.appendChild(p);
        var s = mkBtn(opts.secondary, 'ghost'); if (s) btns.appendChild(s);

        var x = document.createElement('button');
        x.type = 'button'; x.className = 'ms-ps-x'; x.setAttribute('aria-label', 'Cerrar');
        x.innerHTML = svg(ICONS.x);
        x.addEventListener('click', close);

        root.appendChild(x); root.appendChild(ic); root.appendChild(t); root.appendChild(m); root.appendChild(btns);
        document.body.appendChild(root);
        if (p) { requestAnimationFrame(function(){ p.focus(); }); }
        return { close: close };
    };
})();
</script>
