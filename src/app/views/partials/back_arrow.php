<?php
/**
 * Flecha de regreso minimalista — solo móvil (≤768px) por ahora.
 *
 * Uso desde cualquier vista:
 *   <?php $back_arrow_href = back_url('dashboard'); include APP_PATH . '/views/partials/back_arrow.php'; ?>
 *
 * Variables opcionales:
 *   $back_arrow_href  → destino (default: back_url('dashboard'))
 *   $back_arrow_label → texto accesible (default: 'Regresar')
 *   $back_arrow_class → modificadores: 'ms-back--glass' para heros oscuros,
 *                       'ms-back--inline' cuando va dentro de una toolbar/flex
 *
 * Reemplazo de botones viejos: agrega la clase `ms-back-legacy` al botón de
 * regreso anterior para ocultarlo en móvil (en desktop sigue visible).
 *
 * Lenguaje visual del dashboard móvil: superficie clara, borde marfil fino,
 * tinta navy apagada. El Dashboard NO lleva flecha (es la raíz).
 */
$msBackHref  = $back_arrow_href ?? back_url('dashboard');
$msBackLabel = $back_arrow_label ?? 'Regresar';
$msBackClass = trim('ms-back ' . ($back_arrow_class ?? ''));
unset($back_arrow_href, $back_arrow_label, $back_arrow_class);
?>
<a href="<?= htmlspecialchars($msBackHref, ENT_QUOTES, 'UTF-8') ?>"
   class="<?= htmlspecialchars($msBackClass, ENT_QUOTES, 'UTF-8') ?>"
   aria-label="<?= htmlspecialchars($msBackLabel, ENT_QUOTES, 'UTF-8') ?>"
   title="<?= htmlspecialchars($msBackLabel, ENT_QUOTES, 'UTF-8') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
    </svg>
</a>
<style>
/* ── Flecha de regreso global (partials/back_arrow.php) ── */
.ms-back { display: none; }

@media (max-width: 768px) {
    .ms-back {
        display: inline-grid;
        place-items: center;
        width: 40px;
        height: 40px;
        margin: 0 0 10px;
        flex: 0 0 auto;
        border: 1px solid #E8E0D0;
        border-radius: 13px;
        background: rgba(255, 255, 255, .92);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        color: #5B6478;
        box-shadow: 0 1px 2px rgba(27, 39, 70, .06);
        text-decoration: none;
        -webkit-tap-highlight-color: transparent;
        transition: transform .16s ease, background .16s ease, color .16s ease;
    }
    .ms-back svg { width: 16px; height: 16px; }
    .ms-back:active {
        transform: scale(.92);
        background: #F6F2EA;
        color: #1B2746;
    }
    .ms-back:focus-visible {
        outline: 2px solid #1B2746;
        outline-offset: 2px;
    }

    /* Dentro de toolbars/flex: sin margen propio */
    .ms-back--inline { margin: 0; }

    /* Botón de regreso anterior de la vista: oculto en móvil */
    .ms-back-legacy { display: none !important; }

    /* Variante para heros con fondo oscuro */
    .ms-back--glass {
        border-color: rgba(255, 255, 255, .30);
        background: rgba(255, 255, 255, .14);
        color: #fff;
        box-shadow: none;
    }
    .ms-back--glass:active {
        background: rgba(255, 255, 255, .26);
        color: #fff;
    }
}
</style>
<script>
/* Etiqueta el hero/header que contiene la flecha con data-ms-hero para que
   los temas (css/temas/*.css) puedan vestir el encabezado completo de la
   vista sin enumerar las clases de cada módulo. Se toma el ancestro MÁS
   EXTERNO con pinta de encabezado (hero/topbar/page-header). Los reportes
   quedan intactos por contrato, y los heros oscuros (.ms-back--glass)
   conservan su fondo. */
(function() {
    if (window.__msBackHeroTag) { return; }
    window.__msBackHeroTag = true;

    var marcar = function() {
        if (/reporte/i.test(window.location.pathname)) { return; }

        document.querySelectorAll('.ms-back:not(.ms-back--glass)').forEach(function(back) {
            var nodo = back.parentElement;
            var candidato = null;

            while (nodo && nodo !== document.body && !nodo.classList.contains('main-content')) {
                var cls = typeof nodo.className === 'string' ? nodo.className : '';
                if (/(^|\s)[\w-]*(hero|topbar|page-header)[\w-]*(\s|$)/.test(cls)) {
                    candidato = nodo;
                }
                nodo = nodo.parentElement;
            }

            if (candidato) {
                candidato.setAttribute('data-ms-hero', '');
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', marcar);
    } else {
        marcar();
    }
})();
</script>
