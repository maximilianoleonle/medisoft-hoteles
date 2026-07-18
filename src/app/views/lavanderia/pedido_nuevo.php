<?php
/**
 * Nuevo pedido de lavanderia de huesped: formulario boutique lote-* con
 * partidas dinamicas (descripcion + cantidad + precio, sugeridas por el
 * catalogo de servicios) y total en vivo. Lienzo neutro + dark inline.
 */

if (!function_exists('lvx_safe')) {
    function lvx_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$reservaciones = $reservaciones ?? [];
$servicios = $servicios ?? [];

// Repoblado tras rechazo del servidor (save_old_input en el controller):
// campos escalares via old(); las partidas dinamicas se reconstruyen aqui
// como filas ya renderizadas (el JS las enlaza en el arranque).
$lavOldVinculo = old('vinculo', '');
$lavOldReservacion = (int)($_SESSION['old_input']['reservacion_id'] ?? 0);
$lavOldItems = [];
$lavOldDesc = $_SESSION['old_input']['item_descripcion'] ?? [];
if (is_array($lavOldDesc)) {
    $lavOldCantArr = $_SESSION['old_input']['item_cantidad'] ?? [];
    $lavOldPrecioArr = $_SESSION['old_input']['item_precio'] ?? [];
    foreach ($lavOldDesc as $i => $lavOldD) {
        $lavOldD = trim((string)$lavOldD);
        $lavOldC = (int)($lavOldCantArr[$i] ?? 1);
        $lavOldP = trim((string)($lavOldPrecioArr[$i] ?? ''));
        if ($lavOldD !== '' || $lavOldP !== '') {
            $lavOldItems[] = ['descripcion' => $lavOldD, 'cantidad' => max(1, $lavOldC), 'precio' => $lavOldP];
        }
    }
}
?>

<style id="lav-pedido-redesign">
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
        width: fit-content; display: inline-flex; align-items: center; gap: .5rem;
        padding: 0.6rem 0.9rem; border: 1px solid rgba(118, 84, 56, 0.14); border-radius: 999px;
        background: rgba(255, 255, 255, 0.62); color: var(--room-brown);
        box-shadow: 0 12px 28px rgba(59, 46, 31, 0.08); backdrop-filter: blur(12px);
        font-size: .85rem; font-weight: 600; text-decoration: none;
    }
    .lote-hero {
        position: relative; overflow: hidden; margin: 1rem 0 1.5rem; padding: 1.75rem 1.9rem;
        border: 1px solid rgba(70, 78, 72, 0.12); border-radius: 1.25rem;
        background: var(--room-paper); box-shadow: 0 22px 55px rgba(57, 49, 37, 0.12); backdrop-filter: blur(14px);
    }
    .lote-hero::before {
        content: ""; position: absolute; inset: 0; border-left: 7px solid var(--room-gold);
        background: linear-gradient(110deg, rgba(255,255,255,.86), rgba(255,255,255,.55)); pointer-events: none;
    }
    .lote-hero h1 { position: relative; font-size: clamp(1.7rem, 4vw, 2.3rem); font-weight: 700; line-height: 1.05; }
    .lote-hero p { position: relative; margin-top: .4rem; color: var(--room-muted); max-width: 60ch; }
    .lote-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, 340px); gap: clamp(1rem, 2.3vw, 1.75rem); align-items: start; }
    .lote-card {
        position: relative; overflow: hidden; border: 1px solid rgba(70, 78, 72, 0.12); border-radius: 1.25rem;
        background: var(--room-paper); box-shadow: 0 22px 55px rgba(57, 49, 37, 0.10); backdrop-filter: blur(14px);
    }
    .lote-card::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 6px; background: var(--section-accent, var(--room-gold)); }
    .lote-card__head {
        display: flex; align-items: center; gap: .7rem; padding: 1.1rem 1.4rem;
        border-bottom: 1px solid var(--room-line);
        background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(245,245,247,.72));
    }
    .lote-card__head i {
        display: inline-grid; place-items: center; width: 2.35rem; height: 2.35rem; border-radius: .9rem;
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
        border: 1px solid rgba(71, 82, 76, 0.18); background: rgba(255, 255, 255, 0.86);
        color: var(--room-ink); font-size: .95rem; box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
    }
    .lote-page textarea { line-height: 1.4; resize: vertical; min-height: 4.2rem; }
    .lote-page input:focus, .lote-page select:focus, .lote-page textarea:focus {
        outline: none; border-color: color-mix(in srgb, var(--room-brown) 62%, white);
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--room-gold) 20%, transparent);
    }
    .lote-hint { font-size: .74rem; color: var(--room-muted); margin-top: .35rem; }
    .lote-side { position: sticky; top: 1rem; display: flex; flex-direction: column; gap: 1rem; align-self: start; }
    .lote-preview {
        border: 1px solid rgba(70, 78, 72, 0.12); border-radius: 1.25rem; background: var(--room-paper);
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

    /* ── Extras del pedido ── */
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
        border-color: var(--room-gold); background: color-mix(in srgb, var(--room-gold) 12%, white);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--room-gold) 18%, transparent);
    }
    .lav-tipo input[type="radio"]:checked + label i { color: var(--room-gold); }

    .lav-rows { display: flex; flex-direction: column; gap: .55rem; }
    .lav-row {
        display: grid; grid-template-columns: minmax(0,1fr) 5rem 7rem auto; align-items: center; gap: .6rem;
    }
    .lav-row input[name="item_cantidad[]"] { text-align: center; }
    .lav-row-quitar {
        border: 0; background: transparent; color: #b42318; cursor: pointer;
        width: 2.1rem; height: 2.1rem; border-radius: .6rem; display: grid; place-items: center;
    }
    .lav-row-quitar:hover { background: rgba(214, 69, 57, .1); }
    .lav-row-head { display: grid; grid-template-columns: minmax(0,1fr) 5rem 7rem auto; gap: .6rem; }
    .lav-row-head span { font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--room-muted); }
    .lav-agregar {
        width: fit-content; display: inline-flex; align-items: center; gap: .5rem;
        border: 1px dashed color-mix(in srgb, var(--room-sky) 55%, transparent); border-radius: .8rem;
        padding: .55rem 1rem; background: transparent; cursor: pointer;
        font-size: .84rem; font-weight: 700; color: var(--room-sky);
    }
    .lav-agregar:hover { background: color-mix(in srgb, var(--room-sky) 10%, transparent); }
    .lav-total-line { display: flex; justify-content: space-between; align-items: baseline; padding: .35rem 0; font-size: .84rem; color: var(--room-muted); }
    .lav-total-line b { font-size: 1.45rem; color: var(--room-ink); font-variant-numeric: tabular-nums; }

    @media (max-width: 900px) {
        .lote-grid { grid-template-columns: 1fr; }
        .lote-side { position: relative; top: auto; }
    }
    @media (max-width: 560px) {
        .lote-page { padding: 1rem; }
        .lote-row.cols-2, .lav-tipo { grid-template-columns: 1fr; }
        .lav-row, .lav-row-head { grid-template-columns: minmax(0,1fr) 4rem 5.5rem auto; }
    }

    /* ── Modo oscuro (theme-agnostico) ── */
    html[data-theme="dark"] .lote-page {
        --room-ink: #F5F5F7; --room-muted: #98989D;
        --room-line: rgba(255, 255, 255, 0.09); --room-paper: #1C1C1E;
        background:
            radial-gradient(circle at 6% 8%, rgba(181, 139, 74, 0.10), transparent 30rem),
            radial-gradient(circle at 92% 12%, rgba(124, 155, 179, 0.10), transparent 28rem),
            linear-gradient(135deg, #161617 0%, #1a1a1c 55%, #141416 100%);
    }
    html[data-theme="dark"] .lote-breadcrumb a { background: #1C1C1E; border-color: #38383A; color: #E4C58C; box-shadow: 0 12px 28px rgba(0,0,0,.4); }
    html[data-theme="dark"] .lote-hero,
    html[data-theme="dark"] .lote-card,
    html[data-theme="dark"] .lote-preview { box-shadow: 0 22px 55px rgba(0, 0, 0, 0.5); }
    html[data-theme="dark"] .lote-hero::before { background: linear-gradient(110deg, rgba(28,28,30,.92), rgba(28,28,30,.6)); }
    html[data-theme="dark"] .lote-card__head,
    html[data-theme="dark"] .lote-preview__head { background: #161617; border-bottom-color: var(--room-line); }
    html[data-theme="dark"] .lote-card__head i { background: color-mix(in srgb, var(--section-accent, var(--room-gold)) 26%, #1C1C1E); }
    html[data-theme="dark"] .lote-field label { color: #D6D8D6; }
    html[data-theme="dark"] .lote-page input:not([type="checkbox"]):not([type="radio"]),
    html[data-theme="dark"] .lote-page select,
    html[data-theme="dark"] .lote-page textarea { background: #1C1C1E; border-color: #38383A; color: #F5F5F7; box-shadow: none; }
    html[data-theme="dark"] .lav-tipo label { background: #1C1C1E; border-color: #38383A; }
    html[data-theme="dark"] .lav-tipo input[type="radio"]:checked + label { background: color-mix(in srgb, var(--room-gold) 16%, #1C1C1E); }
    html[data-theme="dark"] .lote-count { background: color-mix(in srgb, var(--room-gold) 20%, #1C1C1E); }
    html[data-theme="dark"] .lav-total-line b { color: #F5F5F7; }
    html[data-theme="dark"] .lote-cancel { background: #2C2C2E; border-color: #38383A; color: #F5F5F7; }
    html[data-theme="dark"] .lote-cancel:hover { background: #38383A; }
</style>

<div class="lote-page">
    <div class="lote-shell">
        <div class="lote-breadcrumb">
            <?php $back_arrow_href = back_url('lavanderia/pedidos'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
        </div>
        <div class="lote-hero">
            <h1>Nuevo pedido de lavandería</h1>
            <p>Registra la ropa que deja el huésped con sus prendas y precios. El cobro entra a Caja cuando tú lo decidas (al recibir o al entregar).</p>
        </div>

        <form method="POST" action="<?= url('lavanderia/pedidos/guardar') ?>" id="lavPedidoForm" data-ms-no-summary="1">
            <?= csrf_field() ?>
            <div class="lote-grid">
                <div class="lote-main" style="display:flex; flex-direction:column; gap:1.5rem;">

                    <div class="lote-card lote-sec-basic">
                        <div class="lote-card__head"><i class="fas fa-user"></i><h2>¿De quién es la ropa?</h2></div>
                        <div class="lote-card__body">
                            <div class="lote-field">
                                <?php $lavVinculoHuesped = !empty($reservaciones) && $lavOldVinculo !== 'externo'; ?>
                                <div class="lav-tipo">
                                    <span style="position:relative;">
                                        <input type="radio" name="vinculo" value="huesped" id="lavVinHuesped" <?= empty($reservaciones) ? 'disabled' : ($lavVinculoHuesped ? 'checked' : '') ?> onchange="lavVinculoCambio()">
                                        <label for="lavVinHuesped" <?= empty($reservaciones) ? 'style="opacity:.5;cursor:not-allowed;"' : '' ?>><i class="fas fa-bed"></i> Huésped en casa</label>
                                    </span>
                                    <span style="position:relative;">
                                        <input type="radio" name="vinculo" value="externo" id="lavVinExterno" <?= $lavVinculoHuesped ? '' : 'checked' ?> onchange="lavVinculoCambio()">
                                        <label for="lavVinExterno"><i class="fas fa-person-walking"></i> Cliente externo</label>
                                    </span>
                                </div>
                                <?php if (empty($reservaciones)): ?>
                                <p class="lote-hint">No hay huéspedes con check-in ahora mismo; el pedido se registra a nombre del cliente.</p>
                                <?php endif; ?>
                            </div>
                            <div class="lote-field" id="lavWrapHuesped" style="display:none;">
                                <label>Huésped <span class="req">*</span></label>
                                <select name="reservacion_id" id="lavReservacion">
                                    <?php foreach ($reservaciones as $r): ?>
                                        <option value="<?= (int)$r['id'] ?>" <?= $lavOldReservacion === (int)$r['id'] ? 'selected' : '' ?>>
                                            <?= lvx_safe($r['huesped']) ?><?= !empty($r['habitaciones']) ? ' — hab. ' . lvx_safe($r['habitaciones']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="lote-hint">El pedido queda ligado a su estancia (solo como referencia; el cobro es aparte del hospedaje).</p>
                            </div>
                            <div class="lote-field" id="lavWrapExterno" style="display:none;">
                                <label>Nombre del cliente <span class="req">*</span></label>
                                <input type="text" name="cliente_nombre" id="lavCliente" maxlength="160" placeholder="Nombre y apellido…" value="<?= old('cliente_nombre') ?>">
                            </div>
                            <div class="lote-field">
                                <label>Notas (opcional)</label>
                                <input type="text" name="notas" maxlength="500" placeholder="Mancha difícil, urgente para mañana…" value="<?= old('notas') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="lote-card lote-sec-range">
                        <div class="lote-card__head"><i class="fas fa-shirt"></i><h2>Prendas y servicios</h2></div>
                        <div class="lote-card__body">
                            <div class="lav-row-head">
                                <span>Prenda / servicio</span><span>Cant.</span><span>Precio</span><span></span>
                            </div>
                            <div class="lav-rows" id="lavRows">
                                <?php foreach ($lavOldItems as $lavOldItem): ?>
                                <div class="lav-row">
                                    <input type="text" name="item_descripcion[]" maxlength="160" list="lavServicios" placeholder="Camisa, pantalón, tintorería…" autocomplete="off" value="<?= lvx_safe($lavOldItem['descripcion']) ?>">
                                    <input type="number" name="item_cantidad[]" min="1" max="999" value="<?= (int)$lavOldItem['cantidad'] ?>">
                                    <input type="text" name="item_precio[]" inputmode="decimal" data-money-format="true" placeholder="0.00" value="<?= lvx_safe($lavOldItem['precio']) ?>">
                                    <button type="button" class="lav-row-quitar" title="Quitar prenda"><i class="fas fa-times"></i></button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="lav-agregar" onclick="lavAgregarFila()">
                                <i class="fas fa-plus"></i> Agregar prenda
                            </button>
                            <?php if (!empty($servicios)): ?>
                            <p class="lote-hint">Escribe y elige del catálogo para autollenar el precio; también puedes capturar libre.</p>
                            <?php else: ?>
                            <p class="lote-hint">Tip: registra tu catálogo de precios en Pedidos → Precios para autollenar estas partidas.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <div class="lote-side">
                    <div class="lote-preview">
                        <div class="lote-preview__head">
                            <h3>Cuenta del pedido</h3>
                            <span class="lote-count" id="lavPiezas">0 piezas</span>
                        </div>
                        <div class="lote-preview__body">
                            <div class="lav-total-line"><span>Total a cobrar</span><b id="lavTotal">$0.00</b></div>
                            <div class="lote-preview__empty">El cobro se registra en Caja desde el detalle del pedido (referencia LAV-#).</div>
                        </div>
                    </div>
                    <div class="lote-actions">
                        <button type="submit" class="lote-submit" id="lavSubmit" disabled>
                            <i class="fas fa-basket-shopping"></i><span>Registrar pedido</span>
                        </button>
                        <a href="<?= back_url('lavanderia/pedidos') ?>" class="lote-cancel"><i class="fas fa-times"></i> Cancelar</a>
                    </div>
                </div>
            </div>

            <datalist id="lavServicios">
                <?php foreach ($servicios as $s): ?>
                    <option value="<?= lvx_safe($s['nombre']) ?>" data-precio="<?= number_format((float)$s['precio'], 2, '.', '') ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </form>
    </div>
</div>

<script>
(function () {
    var precios = {};
    document.querySelectorAll('#lavServicios option').forEach(function (opt) {
        precios[(opt.value || '').toLowerCase()] = opt.getAttribute('data-precio') || '';
    });

    function lavEnlazarFila(row) {
        var desc = row.querySelector('input[name="item_descripcion[]"]');
        if (desc) {
            desc.addEventListener('change', function () {
                var precio = precios[(desc.value || '').toLowerCase()];
                var inp = row.querySelector('input[name="item_precio[]"]');
                if (precio && inp && !inp.value) { inp.value = precio; }
                lavRecalcular();
            });
        }
        row.querySelectorAll('input').forEach(function (inp) {
            inp.addEventListener('input', lavRecalcular);
        });
        var quitar = row.querySelector('.lav-row-quitar');
        if (quitar) {
            quitar.addEventListener('click', function () {
                row.remove();
                lavRecalcular();
            });
        }
    }

    window.lavAgregarFila = function (foco) {
        var rows = document.getElementById('lavRows');
        if (!rows) return;
        var row = document.createElement('div');
        row.className = 'lav-row';
        row.innerHTML =
            '<input type="text" name="item_descripcion[]" maxlength="160" list="lavServicios" placeholder="Camisa, pantalón, tintorería…" autocomplete="off">' +
            '<input type="number" name="item_cantidad[]" min="1" max="999" value="1">' +
            '<input type="text" name="item_precio[]" inputmode="decimal" data-money-format="true" placeholder="0.00">' +
            '<button type="button" class="lav-row-quitar" title="Quitar prenda"><i class="fas fa-times"></i></button>';
        rows.appendChild(row);
        // El init de arranque solo cubre nodos presentes en DOMContentLoaded.
        if (window.MedisoftMoneyInput && typeof window.MedisoftMoneyInput.init === 'function') {
            window.MedisoftMoneyInput.init(row);
        }
        lavEnlazarFila(row);
        var desc = row.querySelector('input[name="item_descripcion[]"]');
        if (foco !== false && desc) { desc.focus(); }
        lavRecalcular();
    };

    window.lavRecalcular = function () {
        var total = 0;
        var piezas = 0;
        var validas = 0;
        document.querySelectorAll('#lavRows .lav-row').forEach(function (row) {
            var desc = (row.querySelector('input[name="item_descripcion[]"]').value || '').trim();
            var cant = parseInt(row.querySelector('input[name="item_cantidad[]"]').value || '0', 10);
            var precioRaw = (row.querySelector('input[name="item_precio[]"]').value || '').replace(/[$,\s]/g, '');
            var precio = parseFloat(precioRaw);
            if (isNaN(cant) || cant < 0) { cant = 0; }
            if (isNaN(precio) || precio < 0) { precio = 0; }
            if (desc !== '' && cant > 0) {
                validas += 1;
                piezas += cant;
                total += cant * precio;
            }
        });
        var elTotal = document.getElementById('lavTotal');
        var elPiezas = document.getElementById('lavPiezas');
        var submit = document.getElementById('lavSubmit');
        if (elTotal) { elTotal.textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        if (elPiezas) { elPiezas.textContent = piezas + (piezas === 1 ? ' pieza' : ' piezas'); }
        if (submit) { submit.disabled = validas <= 0; }
    };

    window.lavVinculoCambio = function () {
        var esHuesped = document.getElementById('lavVinHuesped');
        var wrapH = document.getElementById('lavWrapHuesped');
        var wrapE = document.getElementById('lavWrapExterno');
        var cliente = document.getElementById('lavCliente');
        var activo = esHuesped && esHuesped.checked && !esHuesped.disabled;
        if (wrapH) { wrapH.style.display = activo ? '' : 'none'; }
        if (wrapE) { wrapE.style.display = activo ? 'none' : ''; }
        if (cliente) { cliente.required = !activo; }
    };

    document.addEventListener('DOMContentLoaded', function () {
        lavVinculoCambio();
        // Filas repobladas por el servidor (old input): solo se enlazan; si
        // no hay ninguna, arranca con una vacia.
        var existentes = document.querySelectorAll('#lavRows .lav-row');
        if (existentes.length > 0) {
            existentes.forEach(lavEnlazarFila);
            lavRecalcular();
        } else {
            lavAgregarFila(false);
        }
    });
})();
</script>
