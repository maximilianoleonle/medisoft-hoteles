<!-- app/views/inventario/entrada.php — sistema visual calcado de /habitaciones/lote -->
<style id="inv-entrada-redesign">
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
            radial-gradient(circle at 6% 8%, rgba(181, 139, 74, 0.16), transparent 30rem),
            radial-gradient(circle at 92% 12%, rgba(124, 155, 179, 0.18), transparent 28rem),
            linear-gradient(135deg, #fbf7ef 0%, #f5f2ea 42%, #eef3f0 100%);
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
        border-left: 7px solid var(--room-sage);
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
        background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(247,244,237,.72));
    }
    .lote-card__head i {
        display: inline-grid; place-items: center;
        width: 2.35rem; height: 2.35rem; border-radius: .9rem;
        background: color-mix(in srgb, var(--section-accent, var(--room-gold)) 18%, white);
        color: var(--section-accent, var(--room-gold));
    }
    .lote-card__head h2 { font-size: 1.05rem; font-weight: 700; }
    .lote-card__body { padding: 1.4rem; display: flex; flex-direction: column; gap: 1.1rem; }

    .lote-sec-basic { --section-accent: var(--room-sage); }
    .lote-sec-range { --section-accent: var(--room-sky); }

    .lote-field label { display: block; font-size: .8rem; font-weight: 600; color: #3f4743; margin-bottom: .4rem; }
    .lote-field .req { color: #dc2626; }

    .lote-page input:not([type="checkbox"]), .lote-page select, .lote-page textarea {
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
        box-shadow: 0 0 0 4px rgba(181, 139, 74, 0.16);
    }

    .lote-side { position: sticky; top: 1rem; display: flex; flex-direction: column; gap: 1rem; align-self: start; }
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
    .lote-cancel {
        padding: .8rem 1.25rem; border-radius: .9rem;
        border: 1px solid rgba(70, 78, 72, 0.16); background: rgba(255,255,255,.78);
        color: #46504b; font-weight: 600; text-align: center; text-decoration: none;
        display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
    }
    .lote-cancel:hover { background: rgba(247, 244, 237, 0.9); }

    /* ── Extras del alta de entrada ── */
    .inv-qty { display: flex; gap: .5rem; }
    .inv-qty input { flex: 1; }
    .inv-qchip {
        padding: .5rem .7rem; border-radius: .7rem;
        border: 1px solid rgba(71, 82, 76, 0.18); background: rgba(255,255,255,.7);
        font-size: .8rem; font-weight: 700; color: var(--room-brown); cursor: pointer;
        white-space: nowrap; transition: background .15s ease, border-color .15s ease;
    }
    .inv-qchip:hover { background: color-mix(in srgb, var(--room-gold) 12%, #fff); border-color: color-mix(in srgb, var(--room-gold) 40%, transparent); }

    .inv-form-error { display: block; margin-top: .4rem; color: #b42318; font-size: .76rem; font-weight: 700; line-height: 1.35; }

    .inv-pv-empty { text-align: center; color: var(--room-muted); padding: 1.4rem 0; }
    .inv-pv-empty i { font-size: 2rem; display: block; margin-bottom: .5rem; opacity: .55; }
    .inv-pv-cap { font-size: .72rem; color: var(--room-muted); }
    .inv-pv-name { font-size: .95rem; font-weight: 700; color: var(--room-ink); margin-top: .1rem; }
    .inv-pv-line { display: flex; align-items: center; justify-content: space-between; padding: .6rem 0; border-top: 1px solid var(--room-line); border-bottom: 1px solid var(--room-line); }
    .inv-pv-line .lbl { font-size: .84rem; color: var(--room-muted); }
    .inv-pv-line .val { font-size: .95rem; font-weight: 800; color: var(--room-ink); }
    .inv-pv-after { display: flex; align-items: center; justify-content: space-between; padding: .75rem .9rem; border-radius: .8rem; }
    .inv-pv-after .lbl { font-size: .82rem; font-weight: 600; }
    .inv-pv-after .val { font-size: 1.15rem; font-weight: 800; }
    .inv-pv-after.is-up { background: color-mix(in srgb, #1E9E63 12%, var(--room-paper)); border: 1px solid color-mix(in srgb, #1E9E63 30%, transparent); color: #1E9E63; }
    .inv-pv-stack { display: flex; flex-direction: column; gap: .8rem; }

    @media (max-width: 900px) {
        .lote-grid { grid-template-columns: 1fr; }
        .lote-side { position: relative; top: auto; }
    }
    @media (max-width: 560px) {
        .lote-page { padding: 1rem; }
        .inv-qchip { padding: .5rem .55rem; font-size: .74rem; }
    }

    /* ── Modo oscuro (theme-agnóstico: Deleite y Cupertino) ── */
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
    html[data-theme="dark"] .lote-card { box-shadow: 0 22px 55px rgba(0, 0, 0, 0.5); }
    html[data-theme="dark"] .lote-hero::before {
        background: linear-gradient(110deg, rgba(28, 28, 30, 0.92), rgba(28, 28, 30, 0.6));
    }
    html[data-theme="dark"] .lote-card__head { background: #161617; border-bottom-color: var(--room-line); }
    html[data-theme="dark"] .lote-card__head i {
        background: color-mix(in srgb, var(--section-accent, var(--room-gold)) 26%, #1C1C1E);
    }
    html[data-theme="dark"] .lote-field label { color: #D6D8D6; }
    html[data-theme="dark"] .lote-page input:not([type="checkbox"]),
    html[data-theme="dark"] .lote-page select,
    html[data-theme="dark"] .lote-page textarea {
        background: #1C1C1E; border-color: #38383A; color: #F5F5F7; box-shadow: none;
    }
    html[data-theme="dark"] .inv-qchip { background: #1C1C1E; border-color: #38383A; color: #E4C58C; }
    html[data-theme="dark"] .inv-qchip:hover { background: #232326; }
    html[data-theme="dark"] .inv-form-error { color: #f4b4ab; }
    html[data-theme="dark"] .lote-cancel { background: #2C2C2E; border-color: #38383A; color: #F5F5F7; }
    html[data-theme="dark"] .lote-cancel:hover { background: #38383A; }
</style>

<div class="lote-page">
    <div class="lote-shell">
        <div class="lote-breadcrumb">
            <?php $back_arrow_href = back_url('inventario'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('inventario') ?>" class="ms-back-legacy">
                <i class="fas fa-arrow-left"></i> Volver a Inventario
            </a>
        </div>

        <div class="lote-hero">
            <h1>Entrada de inventario</h1>
            <p>Suma existencias a un producto — compras, reposición o ajustes al alza. La vista previa te muestra el stock resultante antes de confirmar.</p>
        </div>

        <form method="POST" action="<?= url('inventario/procesarEntrada') ?>" id="formEntrada">
            <?= csrf_field() ?>

            <div class="lote-grid">
                <!-- Columna principal -->
                <div class="lote-main">
                    <div class="lote-card lote-sec-basic">
                        <div class="lote-card__head">
                            <i class="fas fa-arrow-down"></i>
                            <h2>Datos de la entrada</h2>
                        </div>
                        <div class="lote-card__body">
                            <div class="lote-field">
                                <label>Producto <span class="req">*</span></label>
                                <select name="producto_id" id="producto_select" required>
                                    <option value="">Elige un producto...</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= $producto['id'] ?>"
                                                <?= old('producto_id') == $producto['id'] ? 'selected' : '' ?>
                                                data-stock="<?= $producto['stock_actual'] ?>"
                                                data-nombre="<?= htmlspecialchars($producto['nombre']) ?>">
                                            <?= htmlspecialchars($producto['codigo']) ?> - <?= htmlspecialchars($producto['nombre']) ?> (Stock: <?= $producto['stock_actual'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (form_error('producto_id')): ?>
                                    <span class="inv-form-error"><?= form_error('producto_id') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="lote-field">
                                <label>Cantidad <span class="req">*</span></label>
                                <div class="inv-qty">
                                    <input type="number" name="cantidad" id="cantidad_input" min="0.01" step="0.01" placeholder="0" value="<?= old('cantidad') ?>" required>
                                    <button type="button" class="inv-qchip" onclick="setCantidad(10)">+10</button>
                                    <button type="button" class="inv-qchip" onclick="setCantidad(25)">+25</button>
                                    <button type="button" class="inv-qchip" onclick="setCantidad(50)">+50</button>
                                </div>
                                <?php if (form_error('cantidad')): ?>
                                    <span class="inv-form-error"><?= form_error('cantidad') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="lote-field">
                                <label>Motivo <span class="req">*</span></label>
                                <textarea name="motivo" rows="3" placeholder="Ej: Compra mensual, Reposición de stock..." required><?= old('motivo') ?></textarea>
                                <?php if (form_error('motivo')): ?>
                                    <span class="inv-form-error"><?= form_error('motivo') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna lateral -->
                <div class="lote-side">
                    <div class="lote-card lote-sec-range">
                        <div class="lote-card__head">
                            <i class="fas fa-eye"></i>
                            <h2>Vista previa</h2>
                        </div>
                        <div class="lote-card__body">
                            <div id="preview-panel">
                                <div class="inv-pv-empty">
                                    <i class="fas fa-box-open"></i>
                                    <p>Seleccione un producto</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lote-actions">
                        <button type="submit" class="lote-submit">
                            <i class="fas fa-check"></i>
                            <span>Registrar entrada</span>
                        </button>
                        <a href="<?= url('inventario') ?>" class="lote-cancel">
                            <i class="fas fa-times"></i>
                            <span>Cancelar</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function setCantidad(valor) {
    const input = document.getElementById('cantidad_input');
    const currentValue = parseFloat(input.value) || 0;
    input.value = currentValue + valor;
    updatePreview();
}

document.getElementById('producto_select').addEventListener('change', updatePreview);
document.getElementById('cantidad_input').addEventListener('input', updatePreview);

function updatePreview() {
    const productoSelect = document.getElementById('producto_select');
    const selectedOption = productoSelect.options[productoSelect.selectedIndex];
    const previewPanel = document.getElementById('preview-panel');
    const cantidad = parseFloat(document.getElementById('cantidad_input').value) || 0;

    if (productoSelect.value) {
        const stock = parseFloat(selectedOption.getAttribute('data-stock'));
        const nombre = selectedOption.getAttribute('data-nombre');
        const nuevoStock = stock + cantidad;

        previewPanel.innerHTML = `
            <div class="inv-pv-stack">
                <div>
                    <p class="inv-pv-cap">Producto</p>
                    <p class="inv-pv-name">${nombre}</p>
                </div>
                <div class="inv-pv-line">
                    <span class="lbl">Stock actual</span>
                    <span class="val">${stock.toFixed(2)}</span>
                </div>
                ${cantidad > 0 ? `
                <div class="inv-pv-after is-up">
                    <span class="lbl">Stock después</span>
                    <span class="val">${nuevoStock.toFixed(2)}</span>
                </div>` : ''}
            </div>
        `;
    } else {
        previewPanel.innerHTML = `
            <div class="inv-pv-empty">
                <i class="fas fa-box-open"></i>
                <p>Seleccione un producto</p>
            </div>
        `;
    }
}
</script>
