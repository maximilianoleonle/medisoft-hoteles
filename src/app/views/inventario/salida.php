<!-- app/views/inventario/salida.php — sistema visual calcado de /habitaciones/lote -->
<style id="inv-salida-redesign">
    .lote-page {
        --room-ink: #2d302f;
        --room-muted: #68716d;
        --room-line: rgba(55, 64, 60, 0.12);
        --room-paper: rgba(255, 255, 255, 0.94);
        --room-gold: color-mix(in srgb, var(--brand-accent, #b58b4a) 52%, #b58b4a);
        --room-brown: color-mix(in srgb, var(--brand-primary, #765438) 24%, #6b5138);
        --room-sky: #7c9bb3;
        --room-warn: #C2841C;
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
        border-left: 7px solid var(--room-warn);
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

    .lote-sec-basic { --section-accent: var(--room-warn); }
    .lote-sec-range { --section-accent: var(--room-sky); }

    .lote-field label { display: block; font-size: .8rem; font-weight: 600; color: #3f4743; margin-bottom: .4rem; }
    .lote-field .req { color: #dc2626; }
    .lote-row { display: grid; gap: 1rem; }
    .lote-row.cols-2 { grid-template-columns: 1fr 1fr; }

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
    .lote-page input.ms-form-invalid { border-color: #dc2626 !important; box-shadow: 0 0 0 4px rgba(220,38,38,.14) !important; }
    .inv-stock-info { display: block; margin-top: .35rem; font-size: .74rem; color: var(--room-muted); }
    .inv-stock-err { display: block; margin-top: .3rem; font-size: .74rem; font-weight: 700; color: #dc2626; }

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
    .inv-pv-after.is-down { background: color-mix(in srgb, #C2841C 12%, var(--room-paper)); border: 1px solid color-mix(in srgb, #C2841C 30%, transparent); color: #C2841C; }
    .inv-pv-after.is-neg { background: color-mix(in srgb, #D64539 12%, var(--room-paper)); border: 1px solid color-mix(in srgb, #D64539 30%, transparent); color: #D64539; }
    .inv-pv-warn { text-align: center; font-weight: 700; color: #D64539; font-size: .84rem; }
    .inv-pv-stack { display: flex; flex-direction: column; gap: .8rem; }

    @media (max-width: 900px) {
        .lote-grid { grid-template-columns: 1fr; }
        .lote-side { position: relative; top: auto; }
    }
    @media (max-width: 560px) {
        .lote-row.cols-2 { grid-template-columns: 1fr; }
        .lote-page { padding: 1rem; }
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
            <h1>Salida de inventario</h1>
            <p>Descuenta existencias de un producto — consumo, solicitud de huésped o limpieza. La vista previa valida que haya stock suficiente antes de confirmar.</p>
        </div>

        <form method="POST" action="<?= url('inventario/procesarSalida') ?>" id="formSalida">
            <?= csrf_field() ?>

            <div class="lote-grid">
                <!-- Columna principal -->
                <div class="lote-main">
                    <div class="lote-card lote-sec-basic">
                        <div class="lote-card__head">
                            <i class="fas fa-arrow-up"></i>
                            <h2>Datos de la salida</h2>
                        </div>
                        <div class="lote-card__body">
                            <div class="lote-field">
                                <label>Producto <span class="req">*</span></label>
                                <select name="producto_id" id="producto_id" required>
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

                            <div class="lote-row cols-2">
                                <div class="lote-field">
                                    <label>Cantidad <span class="req">*</span></label>
                                    <input type="number" name="cantidad" id="cantidad" min="0.01" step="0.01" value="<?= old('cantidad') ?>" required>
                                    <small class="inv-stock-info" id="stock_info"></small>
                                    <small class="inv-stock-err hidden" id="stock_error" aria-live="polite"></small>
                                    <?php if (form_error('cantidad')): ?>
                                        <span class="inv-form-error"><?= form_error('cantidad') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="lote-field">
                                    <label>Habitación</label>
                                    <select name="habitacion_id">
                                        <option value="">Sin habitación</option>
                                        <?php foreach ($habitaciones as $habitacion): ?>
                                            <option value="<?= $habitacion['id'] ?>" <?= old('habitacion_id') == $habitacion['id'] ? 'selected' : '' ?>>
                                                Hab. <?= htmlspecialchars($habitacion['numero']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (form_error('habitacion_id')): ?>
                                        <span class="inv-form-error"><?= form_error('habitacion_id') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="lote-field">
                                <label>Tipo de salida</label>
                                <select name="motivo_tipo" id="motivo_tipo">
                                    <option value="manual" <?= old('motivo_tipo', 'manual') === 'manual' ? 'selected' : '' ?>>Salida manual</option>
                                    <option value="solicitud" <?= old('motivo_tipo') === 'solicitud' ? 'selected' : '' ?>>Solicitud de huésped</option>
                                    <option value="limpieza" <?= old('motivo_tipo') === 'limpieza' ? 'selected' : '' ?>>Limpieza/Mantenimiento</option>
                                    <option value="otro" <?= old('motivo_tipo') === 'otro' ? 'selected' : '' ?>>Otro motivo</option>
                                </select>
                            </div>

                            <div class="lote-field">
                                <label>Detalles <span class="req">*</span></label>
                                <textarea name="motivo" id="motivo" rows="2" placeholder="Escribe el motivo..." required><?= old('motivo') ?></textarea>
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
                            <span>Registrar salida</span>
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
document.getElementById('producto_id').addEventListener('change', function() {
    updatePreview();

    const selectedOption = this.options[this.selectedIndex];
    const stock = selectedOption.getAttribute('data-stock');
    const stockInfo = document.getElementById('stock_info');
    const cantidadInput = document.getElementById('cantidad');

    if (stock) {
        stockInfo.textContent = `Stock disponible: ${stock}`;
        cantidadInput.max = stock;
    } else {
        stockInfo.textContent = '';
    }

    validarStockSalida();
});

document.getElementById('cantidad').addEventListener('input', function() {
    updatePreview();
    validarStockSalida();
});

function updatePreview() {
    const productoSelect = document.getElementById('producto_id');
    const selectedOption = productoSelect.options[productoSelect.selectedIndex];
    const previewPanel = document.getElementById('preview-panel');
    const cantidad = parseFloat(document.getElementById('cantidad').value) || 0;

    if (productoSelect.value) {
        const stock = parseFloat(selectedOption.getAttribute('data-stock'));
        const nombre = selectedOption.getAttribute('data-nombre');
        const stockFinal = stock - cantidad;

        let warnHtml = '';
        if (stock === 0) {
            warnHtml = '<div class="inv-pv-warn">Sin stock disponible</div>';
        } else if (stockFinal < 0) {
            warnHtml = '<div class="inv-pv-warn">Stock insuficiente</div>';
        }

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
                <div class="inv-pv-after ${stockFinal < 0 ? 'is-neg' : 'is-down'}">
                    <span class="lbl">Stock después</span>
                    <span class="val">${stockFinal.toFixed(2)}</span>
                </div>
                ${warnHtml}` : ''}
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

document.getElementById('motivo_tipo').addEventListener('change', function() {
    const motivoTextarea = document.getElementById('motivo');
    switch(this.value) {
        case 'solicitud':
            motivoTextarea.placeholder = 'Ej: Huésped de habitación 101 solicitó...';
            break;
        case 'limpieza':
            motivoTextarea.placeholder = 'Ej: Para limpieza de habitaciones...';
            break;
        default:
            motivoTextarea.placeholder = 'Escribe el motivo...';
    }
});

document.getElementById('formSalida').addEventListener('submit', function(e) {
    if (!validarStockSalida(true)) {
        e.preventDefault();
        return false;
    }
});

function validarStockSalida(enviar = false) {
    const productoSelect = document.getElementById('producto_id');
    const cantidadInput = document.getElementById('cantidad');
    const stockError = document.getElementById('stock_error');
    const cantidad = parseFloat(cantidadInput.value) || 0;
    const selectedOption = productoSelect.options[productoSelect.selectedIndex];
    const stockActual = parseFloat(selectedOption.getAttribute('data-stock')) || 0;
    let mensaje = '';

    if (productoSelect.value && cantidad > stockActual) {
        mensaje = `No hay suficiente stock. Disponible: ${stockActual.toFixed(2)}.`;
    }

    cantidadInput.setCustomValidity(mensaje);
    cantidadInput.classList.toggle('ms-form-invalid', Boolean(mensaje));

    if (mensaje) {
        cantidadInput.setAttribute('aria-invalid', 'true');
    } else {
        cantidadInput.removeAttribute('aria-invalid');
        const errorId = cantidadInput.dataset.msErrorId;
        if (errorId) {
            document.getElementById(errorId)?.remove();
            const describedBy = String(cantidadInput.getAttribute('aria-describedby') || '')
                .split(/\s+/)
                .filter(Boolean)
                .filter(id => id !== errorId)
                .join(' ');
            if (describedBy) {
                cantidadInput.setAttribute('aria-describedby', describedBy);
            } else {
                cantidadInput.removeAttribute('aria-describedby');
            }
        }
        if (document.getElementById('formSalida')?.checkValidity()) {
            document.getElementById('formSalida')?.querySelector('.ms-form-error-summary')?.remove();
        }
    }

    if (stockError) {
        stockError.textContent = mensaje;
        stockError.classList.toggle('hidden', !mensaje);
    }

    if (mensaje && enviar) {
        cantidadInput.focus();
    }

    return !mensaje;
}
</script>
