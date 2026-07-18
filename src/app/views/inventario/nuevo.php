<?php
$unidadesMedida = is_array($unidadesMedida ?? null) && !empty($unidadesMedida)
    ? $unidadesMedida
    : [
        'pieza' => 'Pieza',
        'rollo' => 'Rollo',
        'caja' => 'Caja',
        'paquete' => 'Paquete',
        'litro' => 'Litro',
        'kilogramo' => 'Kilogramo',
        'unidad' => 'Unidad',
    ];

$unidadSeleccionada = (string) old('unidad_medida', 'pieza');
if (!array_key_exists($unidadSeleccionada, $unidadesMedida)) {
    foreach ($unidadesMedida as $unidadKey => $unidadLabel) {
        $unidadSeleccionada = (string) $unidadKey;
        break;
    }
}

$categoriaSeleccionada = (string) old('categoria_id', '');
$descuentoAutomaticoActivo = old('descuento_automatico', '') !== '';
$categorias = is_array($categorias ?? null) ? $categorias : [];
?>

<!-- app/views/inventario/nuevo.php — sistema visual calcado de /habitaciones/lote -->
<style id="inv-nuevo-redesign">
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

    .lote-sec-basic { --section-accent: var(--room-gold); }
    .lote-sec-range { --section-accent: var(--room-sky); }

    .lote-field label { display: block; font-size: .8rem; font-weight: 600; color: #3f4743; margin-bottom: .4rem; }
    .lote-field .req { color: #dc2626; }
    .lote-row { display: grid; gap: 1rem; }
    .lote-row.cols-2 { grid-template-columns: 1fr 1fr; }
    .lote-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }

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
    .lote-hint { font-size: .74rem; color: var(--room-muted); margin-top: .35rem; }

    .lote-side { position: sticky; top: 1rem; display: flex; flex-direction: column; gap: 1rem; align-self: start; }

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

    /* ── Extras del alta de producto sobre el sistema lote ── */
    .inv-actions { display: flex; gap: .65rem; margin-top: .2rem; }
    .inv-actions .lote-submit { flex: 1; width: auto; }
    .inv-actions .lote-cancel { flex: 0 0 auto; padding-inline: 1.4rem; }

    .inv-price { position: relative; }
    .inv-price .cur { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--room-gold); }
    .inv-price input { padding-left: 1.9rem !important; }

    .inv-note {
        display: block; padding: .9rem 1rem; border-radius: .9rem;
        border: 1px solid color-mix(in srgb, var(--room-gold) 30%, transparent);
        background: color-mix(in srgb, var(--room-gold) 12%, var(--room-paper));
    }
    .inv-note label { display: flex; align-items: flex-start; gap: .6rem; cursor: pointer; margin: 0; }
    .inv-note input[type="checkbox"] { margin-top: .2rem; width: 1.05rem; height: 1.05rem; flex: 0 0 auto; accent-color: var(--room-gold); }
    .inv-note__title { font-size: .9rem; font-weight: 700; color: var(--room-ink); }
    .inv-note p { margin-top: .25rem; font-size: .8rem; color: var(--room-muted); line-height: 1.4; }

    .inv-help-item .t { font-size: .85rem; font-weight: 700; color: var(--room-ink); }
    .inv-help-item .d { font-size: .8rem; color: var(--room-muted); margin-top: .1rem; }

    .inv-cat-list { max-height: 340px; overflow: auto; display: flex; flex-direction: column; gap: .2rem; }
    .inv-cat-item { padding: .6rem .7rem; border-radius: .7rem; }
    .inv-cat-item:hover { background: color-mix(in srgb, var(--room-sky) 10%, transparent); }
    .inv-cat-item .row { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .inv-cat-item .name { font-size: .85rem; font-weight: 600; color: var(--room-ink); }
    .inv-cat-item .id { font-size: .72rem; color: var(--room-muted); white-space: nowrap; }
    .inv-cat-item .desc { font-size: .74rem; color: var(--room-muted); margin-top: .15rem; }
    .inv-cat-empty { font-size: .82rem; color: var(--room-muted); }

    .inv-form-error { display: block; margin-top: .4rem; color: #b42318; font-size: .76rem; font-weight: 700; line-height: 1.35; }

    @media (max-width: 900px) {
        .lote-grid { grid-template-columns: 1fr; }
        .lote-side { position: relative; top: auto; }
    }
    @media (max-width: 560px) {
        .lote-row.cols-3 { grid-template-columns: 1fr; }
        .lote-page { padding: 1rem; }
    }

    /* ── Modo oscuro (theme-agnóstico: Deleite y Cupertino) ──
       Paleta canónica: #1C1C1E elevado · #161617 hundido · #38383A bordes. */
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
    html[data-theme="dark"] .lote-card__head {
        background: #161617; border-bottom-color: var(--room-line);
    }
    html[data-theme="dark"] .lote-card__head i {
        background: color-mix(in srgb, var(--section-accent, var(--room-gold)) 26%, #1C1C1E);
    }
    html[data-theme="dark"] .lote-field label { color: #D6D8D6; }
    html[data-theme="dark"] .lote-page input:not([type="checkbox"]),
    html[data-theme="dark"] .lote-page select,
    html[data-theme="dark"] .lote-page textarea {
        background: #1C1C1E; border-color: #38383A; color: #F5F5F7; box-shadow: none;
    }
    html[data-theme="dark"] .inv-note {
        border-color: color-mix(in srgb, var(--room-gold) 34%, transparent);
        background: color-mix(in srgb, var(--room-gold) 14%, #1C1C1E);
    }
    html[data-theme="dark"] .inv-cat-item:hover { background: rgba(124, 155, 179, 0.12); }
    html[data-theme="dark"] .inv-form-error { color: #f4b4ab; }
    html[data-theme="dark"] .lote-cancel {
        background: #2C2C2E; border-color: #38383A; color: #F5F5F7;
    }
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
            <h1>Nuevo producto</h1>
            <p>Registra un artículo del inventario del hotel — código, categoría, stock y costo. El stock mínimo dispara las alertas de reposición.</p>
        </div>

        <form method="POST" action="<?= url('inventario/guardar') ?>" id="formNuevoProducto">
            <?= csrf_field() ?>

            <div class="lote-grid">
                <!-- Columna principal -->
                <div class="lote-main" style="display:flex; flex-direction:column; gap:1.5rem;">
                    <div class="lote-card lote-sec-basic">
                        <div class="lote-card__head">
                            <i class="fas fa-box"></i>
                            <h2>Información del producto</h2>
                        </div>
                        <div class="lote-card__body">
                            <div class="lote-row" style="grid-template-columns:1fr 2fr;">
                                <div class="lote-field">
                                    <label>Código <span class="req">*</span></label>
                                    <input type="text" name="codigo" value="<?= old('codigo') ?>"
                                           placeholder="PAP001" pattern="[A-Za-z0-9]{3,20}"
                                           title="Solo letras y números, 3-20 caracteres"
                                           style="text-transform:uppercase;" required>
                                    <?php if (form_error('codigo')): ?>
                                        <span class="inv-form-error"><?= form_error('codigo') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="lote-field">
                                    <label>Nombre <span class="req">*</span></label>
                                    <input type="text" name="nombre" value="<?= old('nombre') ?>"
                                           placeholder="Papel higiénico" required>
                                    <?php if (form_error('nombre')): ?>
                                        <span class="inv-form-error"><?= form_error('nombre') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="lote-row cols-2">
                                <div class="lote-field">
                                    <label>Categoría <span class="req">*</span></label>
                                    <select name="categoria_id" required>
                                        <option value="">Seleccione categoría...</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?= $categoria['id'] ?>" <?= $categoriaSeleccionada === (string)$categoria['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($categoria['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (form_error('categoria_id')): ?>
                                        <span class="inv-form-error"><?= form_error('categoria_id') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="lote-field">
                                    <label>Unidad de medida</label>
                                    <select name="unidad_medida">
                                        <?php foreach ($unidadesMedida as $unidadKey => $unidadLabel): ?>
                                            <?php $unidadKey = (string) $unidadKey; ?>
                                            <option value="<?= htmlspecialchars($unidadKey, ENT_QUOTES, 'UTF-8') ?>" <?= $unidadSeleccionada === $unidadKey ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) $unidadLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (form_error('unidad_medida')): ?>
                                        <span class="inv-form-error"><?= form_error('unidad_medida') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="lote-row cols-3">
                                <div class="lote-field">
                                    <label>Stock inicial <span class="req">*</span></label>
                                    <input type="number" name="stock_inicial" value="<?= old('stock_inicial', 0) ?>" min="0" required>
                                    <?php if (form_error('stock_inicial')): ?>
                                        <span class="inv-form-error"><?= form_error('stock_inicial') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="lote-field">
                                    <label>Stock mínimo <span class="req">*</span></label>
                                    <input type="number" name="stock_minimo" value="<?= old('stock_minimo', 10) ?>" min="0" required>
                                    <?php if (form_error('stock_minimo')): ?>
                                        <span class="inv-form-error"><?= form_error('stock_minimo') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="lote-field">
                                    <label>Costo unitario</label>
                                    <div class="inv-price">
                                        <span class="cur">$</span>
                                        <input type="number" name="costo_unitario" value="<?= old('costo_unitario') ?>"
                                               step="0.01" min="0" placeholder="0.00">
                                    </div>
                                    <?php if (form_error('costo_unitario')): ?>
                                        <span class="inv-form-error"><?= form_error('costo_unitario') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="lote-field">
                                <label>Descripción</label>
                                <textarea name="descripcion" rows="2" placeholder="Detalles adicionales del producto..."><?= old('descripcion') ?></textarea>
                                <?php if (form_error('descripcion')): ?>
                                    <span class="inv-form-error"><?= form_error('descripcion') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="inv-note">
                                <label>
                                    <input type="checkbox" name="descuento_automatico" value="1" <?= $descuentoAutomaticoActivo ? 'checked' : '' ?>>
                                    <div>
                                        <span class="inv-note__title">Descuento automático en check-in</span>
                                        <p>Active si este producto se descuenta automáticamente cuando un huésped hace check-in (papel higiénico, jabón, etc.).</p>
                                    </div>
                                </label>
                            </div>

                            <div class="inv-actions">
                                <button type="submit" class="lote-submit">
                                    <i class="fas fa-save"></i>
                                    <span>Guardar producto</span>
                                </button>
                                <a href="<?= url('inventario') ?>" class="lote-cancel">
                                    <i class="fas fa-times"></i>
                                    <span>Cancelar</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna lateral -->
                <div class="lote-side">
                    <div class="lote-card lote-sec-range">
                        <div class="lote-card__head">
                            <i class="fas fa-circle-info"></i>
                            <h2>Ayuda rápida</h2>
                        </div>
                        <div class="lote-card__body">
                            <div class="inv-help-item">
                                <div class="t">Código único</div>
                                <div class="d">Ej: PAP001, JAB001, TOA001</div>
                            </div>
                            <div class="inv-help-item">
                                <div class="t">Stock mínimo</div>
                                <div class="d">Se alertará al llegar a este nivel</div>
                            </div>
                            <div class="inv-help-item">
                                <div class="t">Descuento automático</div>
                                <div class="d">Solo para productos de cortesía</div>
                            </div>
                        </div>
                    </div>

                    <div class="lote-card lote-sec-basic">
                        <div class="lote-card__head">
                            <i class="fas fa-tags"></i>
                            <h2>Categorías</h2>
                        </div>
                        <div class="lote-card__body">
                            <div class="inv-cat-list">
                                <?php foreach ($categorias as $categoria): ?>
                                    <div class="inv-cat-item">
                                        <div class="row">
                                            <span class="name"><?= htmlspecialchars($categoria['nombre']) ?></span>
                                            <span class="id">ID: <?= $categoria['id'] ?></span>
                                        </div>
                                        <?php if (!empty($categoria['descripcion'])): ?>
                                            <div class="desc"><?= htmlspecialchars($categoria['descripcion']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($categorias)): ?>
                                    <div class="inv-cat-empty">Aún no hay categorías. Créalas desde la configuración de inventario.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-mayúsculas para el código del producto.
document.addEventListener('DOMContentLoaded', function () {
    var codigo = document.querySelector('#formNuevoProducto input[name="codigo"]');
    if (codigo) {
        codigo.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }
});
</script>
