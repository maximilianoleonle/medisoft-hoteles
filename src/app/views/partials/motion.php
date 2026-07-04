<?php
/**
 * Capa global de micro-interacciones ("la app se siente viva").
 * Sin dependencias, scoped con prefijo ms-*, incluida en header.php junto a toast/confirm.
 *
 * Utilidades declarativas (opt-in por atributo/clase):
 *   [data-ms-stagger]  → contenedor cuyos hijos entran en cascada al hacerse visibles.
 *   [data-ms-reveal]   → un solo elemento que entra (fade + subida) al hacerse visible.
 *   [data-ms-count]    → anima el número desde 0 hasta su valor final (conserva %, $, decimales).
 *   .ms-pressable      → hunde ligeramente el elemento al tocarlo (feedback táctil visual).
 *
 * Diseño clave: la entrada usa `animation` (no `transition`) y al terminar el elemento
 * queda con `.ms-shown`, que NO deja ningún override de opacity/transform. Así el estado
 * natural (incl. hover con transform) se conserva intacto y no hay "clobber".
 *
 * Seguridad:
 *   - Respeta prefers-reduced-motion: no anima, deja todo estático y visible.
 *   - Si el JS no corre, NADA queda oculto (el ocultado depende de la clase .ms-motion que
 *     solo añade el JS). Un failsafe marca todo como visible pase lo que pase.
 *   - No toca lógica, rutas ni datos: solo presentación.
 */
?>
<style>
@media (prefers-reduced-motion: no-preference){
    /* Oculto SOLO mientras no se revela; al revelar/terminar no queda override → hover intacto. */
    .ms-motion [data-ms-stagger] > *:not(.ms-in):not(.ms-shown),
    .ms-motion [data-ms-reveal]:not(.ms-in):not(.ms-shown){
        opacity:0;
    }
    /* Entrada por animación (con fill both mantiene el estado durante el retardo escalonado). */
    .ms-motion [data-ms-stagger] > *.ms-in,
    .ms-motion [data-ms-reveal].ms-in{
        animation:msRise .55s cubic-bezier(.22, 1, .36, 1) both;
        animation-delay:calc(var(--ms-i, 0) * 70ms);
    }
    @keyframes msRise{
        from{ opacity:0; transform:translateY(12px); }
        to{   opacity:1; transform:none; }
    }
    /* Feedback táctil visual (sustituye a la vibración donde no hay hardware). */
    .ms-motion .ms-pressable{
        transition:transform .12s cubic-bezier(.22, 1, .36, 1);
        -webkit-tap-highlight-color:transparent;
    }
    .ms-motion .ms-pressable:active{ transform:scale(.965); }
}

/* Números animados: evita el "salto" de ancho mientras cuentan. */
[data-ms-count]{ font-variant-numeric:tabular-nums; }
</style>

<script>
(function(){
    'use strict';

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var ok = ('IntersectionObserver' in window) && ('requestAnimationFrame' in window);
    // Sin soporte o con reduced-motion: se deja todo estático y visible (no se añade .ms-motion).
    if (reduce || !ok) { return; }

    var root = document.documentElement;
    root.classList.add('ms-motion'); // a partir de aquí, el CSS oculta los elementos opt-in

    var io = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if (!e.isIntersecting) { return; }
            var el = e.target;
            io.unobserve(el);
            if (el.hasAttribute('data-ms-count')) { runCount(el); }
            else if (el.hasAttribute('data-ms-stagger')) { revealStagger(el); }
            else { reveal(el); }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    // Revela un elemento y, al terminar la animación, lo deja en estado natural (.ms-shown).
    function reveal(el){
        if (el.classList.contains('ms-in') || el.classList.contains('ms-shown')) { return; }
        el.classList.add('ms-in');
        var done = function(){
            el.removeEventListener('animationend', done);
            el.classList.remove('ms-in');   // quita animación
            el.classList.add('ms-shown');   // estado natural sin overrides → no rompe hover
        };
        el.addEventListener('animationend', done);
    }

    function revealStagger(container){
        var kids = container.children;
        for (var i = 0; i < kids.length; i++){
            kids[i].style.setProperty('--ms-i', i % 12); // limita el retardo acumulado en listas largas
            reveal(kids[i]);
        }
    }

    // Anima "0 → valor" conservando prefijo/sufijo (%, $, texto) y el formato numérico original.
    function runCount(el){
        var text = (el.textContent || '').trim();
        var m = text.match(/-?\d[\d.,]*/);
        if (!m) { return; }
        var numStr = m[0];
        var pre = text.slice(0, m.index);
        var post = text.slice(m.index + numStr.length);
        var group = numStr.indexOf(',') > -1;                 // ¿usa separador de miles?
        var decimals = (numStr.split('.')[1] || '').length;   // decimales a conservar
        var target = parseFloat(numStr.replace(/,/g, ''));
        if (!isFinite(target)) { return; }

        var fmt = function(v){
            var s = v.toFixed(decimals);
            if (group) { s = s.replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
            return pre + s + post;
        };

        var dur = 850, t0 = performance.now();
        el.textContent = fmt(0);
        (function tick(now){
            var p = Math.min(1, (now - t0) / dur);
            var eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
            el.textContent = fmt(target * eased);
            if (p < 1) { requestAnimationFrame(tick); }
            else { el.textContent = fmt(target); }
        })(t0);
    }

    // Registra elementos aún no procesados (idempotente; re-llamable tras cargas AJAX).
    function scan(ctx){
        (ctx || document).querySelectorAll(
            '[data-ms-stagger]:not([data-ms-seen]),[data-ms-reveal]:not([data-ms-seen]),[data-ms-count]:not([data-ms-seen])'
        ).forEach(function(el){
            el.setAttribute('data-ms-seen', '1');
            io.observe(el);
        });
    }

    // Salvavidas: si algún observer/animación no dispara, deja todo visible en estado natural.
    function failSafe(){
        document.querySelectorAll(
            '[data-ms-stagger] > *:not(.ms-shown),[data-ms-reveal]:not(.ms-shown)'
        ).forEach(function(el){
            el.classList.remove('ms-in');
            el.classList.add('ms-shown');
        });
    }

    window.MedisoftMotion = { scan: scan };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function(){ scan(); });
    } else {
        scan();
    }
    setTimeout(failSafe, 1800);
})();
</script>
