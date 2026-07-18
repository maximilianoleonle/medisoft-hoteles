<?php
/**
 * Enviar a lavar (nuevo lote): formulario boutique con el sistema lote-*
 * (patron self-contained del repo: el <style> se copia por vista). Lienzo
 * neutro #F5F5F7/#FAFAFC (sin beige) + modo oscuro inline theme-agnostico.
 */

if (!function_exists('lvx_safe')) {
    function lvx_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$blancos = $blancos ?? [];
$hayBlancos = !empty($hay_blancos);
?>

<style id="lav-lote-redesign">
    .lote-page {
        --room-ink: #2d302f;
        --room-muted: #68716d;
        --room-line: rgba(55, 64, 60, 0.12);
        --room-paper: rgba(255, 255, 255, 0.94);
        --room-gold: color-mix(in srgb, var(--brand-accent, #b58b4a) 52%, #b58b4a);
        --room-brown: color-mix(in srgb, var(--brand-primary, #765438) 24%, #6b5138);
        --room-sky: #7c9bb3;
        --room-sage: #7f987d;
        min-height: 100dvh;
        padding: clamp(1rem, 2.2vw, 2rem);
        color: var(--room-ink);
        background:
            radial-gradient(circle at 6% 8%, color-mix(in srgb, var(--brand-accent, #b58b4a) 12%, transparent), transparent 30rem),
            radial-gradient(circle at 92% 12%, rgba(124, 155, 179, 0.14), transparent 28rem),
            linear-gradient(135deg, #FAFAFC 0%, #F5F5F7 55%, #F2F4F6 100%);
    }
    .lote-page > * { position: relative; z-index: 1; }

    .lote-shell { max-width: 1040px; margin: 0 auto; }

    .lote-breadcrumb a {
        width: fit-content;
        display: inline-flex; align-items: center; gap: .5rem;
        padding: 0.6rem 0.9rem;
        border: 1px solid rgba(118, 84, 56, 0.14);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.62);
        color: var(--room-brown);
        box-shadow: 0 12px 28px rgba(59, 46, 31, 0.08);
        backdrop-filter: blur(12px);
        font-size: .85rem; font-weight: 600; text-decoration: none;
    }

    .lote-hero {
        position: relative; overflow: hidden;
        margin: 1rem 0 1.5rem;
        padding: 1.75rem 1.9rem;
        border: 1px solid rgba(70, 78, 72, 0.12);
        border-radius: 1.25rem;
        background: var(--room-paper);
        box-shadow: 0 22px 55px rgba(57, 49, 37, 0.12);
        backdrop-filter: blur(14px);
    }
    .lote-hero::before {
        content: ""; position: absolute; inset: 0;
        border-left: 7px solid var(--room-gold);
        background: linear-gradient(110deg, rgba(255,255,255,.86), rgba(255,255,255,.55));
        pointer-events: none;
    }
    .lote-hero h1 { position: relative; font-size: clamp(1.7rem, 4vw, 2.3rem); font-weight: 700; line-height: 1.05; }
    .lote-hero p { position: relative; margin-top: .4rem; color: var(--room-muted); max-width: 60ch; }

    .lote-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(280px, 340px);
        gap: clamp(1rem, 2.3vw, 1.75rem);
        align-items: start;
    }

    .lote-card {
        position: relative; overflow: hidden;
        border: 1px solid rgba(70, 78, 72, 0.12);
        border-radius: 1.25rem;
        background: var(--room-paper);
        box-shadow: 0 22px 55px rgba(57, 49, 37, 0.10);
        backdrop-filter: blur(14px);
    }
    .lote-card::before {
        content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 6px;
        background: var(--section-accent, var(--room-gold));
    }
    .lote-card__head {
        display: flex; align-items: center; gap: .7rem;
        padding: 1.1rem 1.4rem;
        border-bottom: 1px solid var(--room-line);
        background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(245,245,247,.72));
    }
    .lote-card__head i {
        display: inline-grid; place-items: center;
        width: 2.35rem; height: 2.35rem; border-radius: .9rem;
        background: color-mix(in srgb, var(--section-accent, var(--room-gold)) 18%, white);
        color: var(--section-accent, var(--room-gold));
    }
    .lote-card__head h2 { font-size: 1.05rem; font-weight: 700; }
    .lote-card__body { padding: 1.4rem; display: flex; flex-direction: column; gap: 1.1rem; }

    .lote-sec-basic { --section-accent: var(--room-gold); }
    .lote-sec-range { --section-accent: var(--room-sky); }

    .lote-field label { display: block; font-size: .8rem; font-weight: 600; color: #3f4743; margin-bottom: .4rem; }
    .lote-field .req { color: #dc2626; }
    .lote-row { display: grid; gap: 1rem; }
    .lote-row.cols-2 { grid-template-columns: 1fr 1fr; }

    .lote-page input:not([type="checkbox"]):not([type="radio"]), .lote-page select, .lote-page textarea {
        width: 100%; padding: .7rem .85rem; border-radius: .8rem;
        border: 1px solid rgba(71, 82, 76, 0.18);
        background: rgba(255, 255, 255, 0.86);
        color: var(--room-ink); font-size: .95rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
    }
    .lote-page textarea { line-height: 1.4; resize: vertical; min-height: 4.2rem; }
    .lote-page input:focus, .lote-page select:focus, .lote-page textarea:focus {
        outline: none;
        border-color: color-mix(in srgb, var(--room-brown) 62%, white);
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--room-gold) 20%, transparent);
    }
    .lote-hint { font-size: .74rem; color: var(--room-muted); margin-top: .35rem; }

    .lote-side { position: sticky; top: 1rem; display: flex; flex-direction: column; gap: 1rem; align-self: start; }

    .lote-preview {
        border: 1px solid rgba(70, 78, 72, 0.12);
        border-radius: 1.25rem; background: var(--room-paper);
        box-shadow: 0 22px 55px rgba(57, 49, 37, 0.10); overflow: hidden;
    }
    .lote-preview__head {
        display: flex; align-items: center; justify-content: space-between; gap: .6rem;
        padding: 1rem 1.2rem; border-bottom: 1px solid var(--room-line);
        background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(245,245,247,.72));
    }
    .lote-preview__head h3 { font-size: .95rem; font-weight: 700; }
    .lote-count {
        font-size: .78rem; font-weight: 700; color: var(--room-gold);
        background: color-mix(in srgb, var(--room-gold) 14%, white);
        padding: .3rem .7rem; border-radius: 999px; white-space: nowrap;
    }
    .lote-preview__body { padding: 1.1rem 1.2rem; }
    .lote-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    .lote-chip {
        font-size: .74rem; font-weight: 700; color: var(--room-ink);
        border: 1px solid var(--room-line); border-radius: .6rem;
        padding: .3rem .55rem; background: rgba(255,255,255,.8);
    }
    .lote-preview__empty { font-size: .8rem; color: var(--room-muted); }

    .lote-actions { display: flex; flex-direction: column; gap: .65rem; }
    .lote-submit {
        width: 100%; padding: .95rem 1.25rem; border: 0; border-radius: .9rem;
        background: linear-gradient(135deg, #344139, var(--room-brown)); color: #fff;
        font-weight: 700; font-size: .98rem; cursor: pointer;
        box-shadow: 0 15px 30px rgba(73, 56, 39, 0.2);
        display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .lote-submit:hover { transform: translateY(-1px); box-shadow: 0 18px 34px rgba(73, 56, 39, 0.26); }
    .lote-submit:disabled { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }
    .lote-cancel {
        padding: .8rem 1.25rem; border-radius: .9rem;
        border: 1px solid rgba(70, 78, 72, 0.16); background: rgba(255,255,255,.78);
        color: #46504b; font-weight: 600; text-align: center; text-decoration: none;
        display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
    }
    .lote-cancel:hover { background: rgba(245, 245, 247, 0.9); }

    /* ── Extras del lote de lavanderia ── */
    .lav-tipo { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; }
    .lav-tipo input[type="radio"] { position: absolute; opacity: 0; }
    .lav-tipo label {
        display: flex; align-items: center; gap: .6rem; margin: 0; cursor: pointer;
        padding: .85rem 1rem; border-radius: .9rem;
        border: 1px solid rgba(71, 82, 76, 0.18); background: rgba(255,255,255,.86);
        font-size: .9rem; font-weight: 700; color: var(--room-ink);
    }
    .lav-tipo label i { color: var(--room-muted); }
    .lav-tipo input[type="radio"]:checked + label {
        border-color: var(--room-gold);
        background: color-mix(in srgb, var(--room-gold) 12%, white);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--room-gold) 18%, transparent);
    }
    .lav-tipo input[type="radio"]:checked + label i { color: var(--room-gold); }

    .lav-items { display: flex; flex-direction: column; gap: .5rem; }
    .lav-item {
        display: grid; grid-template-columns: minmax(0,1fr) auto auto; align-items: center; gap: .8rem;
        border: 1px solid var(--room-line); border-radius: .9rem;
        padding: .65rem .9rem; background: rgba(255,255,255,.7);
    }
    .lav-item__name { min-width: 0; }
    .lav-item__name b { display: block; font-size: .9rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lav-item__name small { font-size: .72rem; color: var(--room-muted); }
    .lav-item__max { font-size: .74rem; font-weight: 700; color: var(--room-sky); white-space: nowrap; }
    .lav-item input { width: 5.2rem !important; text-align: center; }
    .lav-vacio { font-size: .85rem; color: var(--room-muted); padding: .4rem 0; }

    @media (max-width: 900px) {
        .lote-grid { grid-template-columns: 1fr; }
        .lote-side { position: relative; top: auto; }
    }
    @media (max-width: 560px) {
        .lote-page { padding: 1rem; }
        .lote-row.cols-2 { grid-template-columns: 1fr; }
        .lav-tipo { grid-template-columns: 1fr; }
    }

    /* ── Modo oscuro (theme-agnostico: Deleite y Cupertino) ──
       Paleta canonica: #1C1C1E elevado · #161617 hundido · #38383A bordes. */
    html[data-theme="dark"] .lote-page {
        --room-ink: #F5F5F7;
        --room-muted: #98989D;
        --room-line: rgba(255, 255, 255, 0.09);
        --room-paper: #1C1C1E;
        background:
            radial-gradient(circle at 6% 8%, rgba(181, 139, 74, 0.10), transparent 30rem),
            radial-gradient(circle at 92% 12%, rgba(124, 155, 179, 0.10), transparent 28rem),
            linear-gradient(135deg, #161617 0%, #1a1a1c 55%, #141416 100%);
    }
    html[data-theme="dark"] .lote-breadcrumb a {
        background: #1C1C1E; border-color: #38383A; color: #E4C58C;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.4);
    }
    html[data-theme="dark"] .lote-hero,
    html[data-theme="dark"] .lote-card,
    html[data-theme="dark"] .lote-preview { box-shadow: 0 22px 55px rgba(0, 0, 0, 0.5); }
    html[data-theme="dark"] .lote-hero::before {
        background: linear-gradient(110deg, rgba(28, 28, 30, 0.92), rgba(28, 28, 30, 0.6));
    }
    html[data-theme="dark"] .lote-card__head,
    html[data-theme="dark"] .lote-preview__head { background: #161617; border-bottom-color: var(--room-line); }
    html[data-theme="dark"] .lote-card__head i {
        background: color-mix(in srgb, var(--section-accent, var(--room-gold)) 26%, #1C1C1E);
    }
    html[data-theme="dark"] .lote-field label { color: #D6D8D6; }
    html[data-theme="dark"] .lote-page input:not([type="checkbox"]):not([type="radio"]),
    html[data-theme="dark"] .lote-page select,
    html[data-theme="dark"] .lote-page textarea {
        background: #1C1C1E; border-color: #38383A; color: #F5F5F7; box-shadow: none;
    }
    html[data-theme="dark"] .lav-tipo label { background: #1C1C1E; border-color: #38383A; }
    html[data-theme="dark"] .lav-tipo input[type="radio"]:checked + label {
        background: color-mix(in srgb, var(--room-gold) 16%, #1C1C1E);
    }
    html[data-theme="dark"] .lav-item { background: #161617; }
    html[data-theme="dark"] .lote-chip { background: #161617; color: var(--room-ink); }
    html[data-theme="dark"] .lote-count { background: color-mix(in srgb, var(--room-gold) 20%, #1C1C1E); }
    html[data-theme="dark"] .lote-cancel { background: #2C2C2E; border-color: #38383A; color: #F5F5F7; }
    html[data-theme="dark"] .lote-cancel:hover { background: #38383A; }
</style>

<div class="lote-page">
    <div class="lote-shell">
        <div class="lote-breadcrumb">
            <?php $back_arrow_href = back_url('lavanderia/lotes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
        </div>
        <div class="lote-hero">
            <h1>Enviar a lavar</h1>
            <p>Arma el lote con las piezas sucias: salen del stock "por lavar" y quedan "en lavado" hasta que las recibas de vuelta.</p>
        </div>

        <?php if (empty($blancos)): ?>
            <div class="lote-card lote-sec-basic">
                <div class="lote-card__head"><i class="fas fa-basket-shopping"></i><h2>No hay piezas sucias que enviar</h2></div>
                <div class="lote-card__body">
                    <p style="color:var(--room-muted);">
                        <?php if ($hayBlancos): ?>
                            Todos tus blancos están limpios o ya en lavado. Cuando marques piezas como "se ensució" en el panel,
                            aparecerán aquí listas para armar el lote.
                        <?php else: ?>
                            Primero da de alta tus blancos (sábanas, toallas…) en el panel de Lavandería.
                        <?php endif; ?>
                    </p>
                    <div>
                        <a href="<?= url('lavanderia') ?>" class="lote-cancel" style="width:fit-content;"><i class="fas fa-arrow-left"></i> Ir al panel de blancos</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
        <form method="POST" action="<?= url('lavanderia/lotes/guardar') ?>" id="lavLoteForm" data-ms-no-summary="1">
            <?= csrf_field() ?>
            <div class="lote-grid">
                <div class="lote-main" style="display:flex; flex-direction:column; gap:1.5rem;">

                    <div class="lote-card lote-sec-basic">
                        <div class="lote-card__head"><i class="fas fa-soap"></i><h2>Datos del ciclo</h2></div>
                        <div class="lote-card__body">
                            <div class="lote-field">
                                <label>¿Quién lava? <span class="req">*</span></label>
                                <div class="lav-tipo">
                                    <span style="position:relative;">
                                        <input type="radio" name="tipo" value="interno" id="lavTipoInterno" checked onchange="lavTipoCambio()">
                                        <label for="lavTipoInterno"><i class="fas fa-house"></i> Lavado interno</label>
                                    </span>
                                    <span style="position:relative;">
                                        <input type="radio" name="tipo" value="externo" id="lavTipoExterno" onchange="lavTipoCambio()">
                                        <label for="lavTipoExterno"><i class="fas fa-truck"></i> Servicio externo</label>
                                    </span>
                                </div>
                            </div>
                            <div class="lote-field" id="lavProveedorWrap" style="display:none;">
                                <label>Proveedor <span class="req">*</span></label>
                                <input type="text" name="proveedor" id="lavProveedor" maxlength="160" placeholder="Lavandería El Cisne…">
                                <p class="lote-hint">El costo real se captura al RECIBIR el lote y se registra como gasto en Caja.</p>
                            </div>
                            <div class="lote-field">
                                <label>Notas (opcional)</label>
                                <input type="text" name="notas" maxlength="500" placeholder="Instrucciones, urgencias…">
                            </div>
                        </div>
                    </div>

                    <div class="lote-card lote-sec-range">
                        <div class="lote-card__head"><i class="fas fa-basket-shopping"></i><h2>Piezas a enviar</h2></div>
                        <div class="lote-card__body">
                            <div class="lav-items">
                                <?php foreach ($blancos as $b): ?>
                                <div class="lav-item">
                                    <div class="lav-item__name">
                                        <b><?= lvx_safe($b['nombre']) ?></b>
                                        <small><?= (int)$b['stock_limpio'] ?> limpias en stock</small>
                                    </div>
                                    <span class="lav-item__max"><?= (int)$b['stock_sucio'] ?> sucias</span>
                                    <input type="number"
                                           name="cantidades[<?= (int)$b['id'] ?>]"
                                           min="0" max="<?= (int)$b['stock_sucio'] ?>"
                                           placeholder="0"
                                           data-lav-nombre="<?= lvx_safe($b['nombre']) ?>"
                                           oninput="lavPreview()">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="lote-hint">Deja en 0 (o vacío) lo que no vaya en este lote. El tope es lo que tengas sucio de cada blanco.</p>
                        </div>
                    </div>

                </div>
                <div class="lote-side">
                    <div class="lote-preview">
                        <div class="lote-preview__head">
                            <h3>Este lote</h3>
                            <span class="lote-count" id="lavCount">0 piezas</span>
                        </div>
                        <div class="lote-preview__body">
                            <div class="lote-chips" id="lavChips" style="display:none;"></div>
                            <div class="lote-preview__empty" id="lavEmpty">Captura cantidades para armar el lote.</div>
                        </div>
                    </div>
                    <div class="lote-actions">
                        <button type="submit" class="lote-submit" id="lavSubmit" disabled>
                            <i class="fas fa-arrows-spin"></i><span>Enviar a lavar</span>
                        </button>
                        <a href="<?= back_url('lavanderia/lotes') ?>" class="lote-cancel"><i class="fas fa-times"></i> Cancelar</a>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function lavTipoCambio() {
    var externo = document.getElementById('lavTipoExterno');
    var wrap = document.getElementById('lavProveedorWrap');
    var prov = document.getElementById('lavProveedor');
    if (!externo || !wrap || !prov) return;
    var es = externo.checked;
    wrap.style.display = es ? '' : 'none';
    prov.required = es;
    if (!es) { prov.value = ''; }
}

function lavPreview() {
    var inputs = document.querySelectorAll('#lavLoteForm .lav-item input[type="number"]');
    var chips = document.getElementById('lavChips');
    var empty = document.getElementById('lavEmpty');
    var count = document.getElementById('lavCount');
    var submit = document.getElementById('lavSubmit');
    var total = 0;
    var html = '';
    inputs.forEach(function (inp) {
        var max = parseInt(inp.getAttribute('max') || '0', 10);
        var val = parseInt(inp.value || '0', 10);
        if (isNaN(val) || val < 0) { val = 0; }
        if (val > max) { val = max; inp.value = String(max); }
        if (val > 0) {
            total += val;
            html += '<span class="lote-chip">' + val + ' × ' + (inp.getAttribute('data-lav-nombre') || '') + '</span>';
        }
    });
    if (chips) { chips.innerHTML = html; chips.style.display = total > 0 ? '' : 'none'; }
    if (empty) { empty.style.display = total > 0 ? 'none' : ''; }
    if (count) { count.textContent = total + (total === 1 ? ' pieza' : ' piezas'); }
    if (submit) { submit.disabled = total <= 0; }
}

document.addEventListener('DOMContentLoaded', function () {
    lavTipoCambio();
    lavPreview();
});
</script>
