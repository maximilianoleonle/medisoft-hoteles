<!-- app/views/habitaciones/lote.php -->
<style id="lote-room-redesign">
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
        font-size: .85rem; font-weight: 600;
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

    .lote-page input:not([type="checkbox"]), .lote-page select {
        width: 100%; padding: .7rem .85rem; border-radius: .8rem;
        border: 1px solid rgba(71, 82, 76, 0.18);
        background: rgba(255, 255, 255, 0.86);
        color: var(--room-ink); font-size: .95rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
    }
    .lote-page input:focus, .lote-page select:focus {
        outline: none;
        border-color: color-mix(in srgb, var(--room-brown) 62%, white);
        box-shadow: 0 0 0 4px rgba(181, 139, 74, 0.16);
    }
    .lote-hint { font-size: .74rem; color: var(--room-muted); margin-top: .35rem; }

    /* Panel de vista previa (sticky) */
    .lote-side { position: sticky; top: 1rem; display: flex; flex-direction: column; gap: 1rem; }
    .lote-preview {
        border: 1px solid rgba(70, 78, 72, 0.12);
        border-radius: 1.25rem;
        background: var(--room-paper);
        box-shadow: 0 22px 55px rgba(57, 49, 37, 0.10);
        backdrop-filter: blur(14px);
        overflow: hidden;
    }
    .lote-preview__head {
        padding: 1rem 1.25rem; border-bottom: 1px solid var(--room-line);
        display: flex; align-items: baseline; justify-content: space-between;
        background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(238,243,240,.72));
    }
    .lote-preview__head h3 { font-size: .95rem; font-weight: 700; }
    .lote-count { font-size: .8rem; font-weight: 700; color: var(--room-sage); }
    .lote-count.is-error { color: #dc2626; }
    .lote-preview__body { padding: 1rem 1.25rem; max-height: 320px; overflow: auto; }
    .lote-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    .lote-chip {
        padding: .25rem .55rem; border-radius: 999px;
        background: rgba(124, 155, 179, 0.14);
        border: 1px solid rgba(124, 155, 179, 0.28);
        color: #375063; font-size: .78rem; font-weight: 600;
    }
    .lote-preview__empty { color: var(--room-muted); font-size: .85rem; }
    .lote-preview__error {
        margin: 0 1.25rem 1rem; padding: .7rem .85rem;
        border: 1px solid rgba(196, 69, 54, 0.24); border-radius: .9rem;
        background: rgba(254, 242, 242, 0.92); color: #991b1b;
        font-size: .82rem; font-weight: 600; display: none;
    }
    .lote-preview__error.is-visible { display: block; }

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
    .lote-submit:disabled { opacity: .55; cursor: not-allowed; transform: none; }
    .lote-cancel {
        width: 100%; padding: .8rem 1.25rem; border-radius: .9rem;
        border: 1px solid rgba(70, 78, 72, 0.16); background: rgba(255,255,255,.78);
        color: #46504b; font-weight: 600; text-align: center;
        display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
    }
    .lote-cancel:hover { background: rgba(247, 244, 237, 0.9); }

    .lote-toggle-inherit {
        display: inline-flex; align-items: center; gap: .5rem;
        font-size: .82rem; color: var(--room-brown); font-weight: 600; cursor: pointer;
        background: none; border: 0; padding: 0;
    }
    .lote-advanced { display: none; }
    .lote-advanced.is-open { display: block; }

    @media (max-width: 900px) {
        .lote-grid { grid-template-columns: 1fr; }
        .lote-side { position: relative; top: auto; }
    }
    @media (max-width: 560px) {
        .lote-row.cols-3 { grid-template-columns: 1fr; }
        .lote-page { padding: 1rem; }
    }

    /* ── Modo oscuro (theme-agnóstico: aplica en Deleite y Cupertino) ──
       Misma paleta que /create: #1C1C1E elevado · #161617 hundido · #38383A bordes. */
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
    html[data-theme="dark"] .lote-preview__head {
        background: #161617; border-bottom-color: var(--room-line);
    }
    html[data-theme="dark"] .lote-card__head i {
        background: color-mix(in srgb, var(--section-accent, var(--room-gold)) 26%, #1C1C1E);
    }
    html[data-theme="dark"] .lote-field label { color: #D6D8D6; }
    html[data-theme="dark"] .lote-page input:not([type="checkbox"]),
    html[data-theme="dark"] .lote-page select {
        background: #1C1C1E; border-color: #38383A; color: #F5F5F7; box-shadow: none;
    }
    html[data-theme="dark"] .lote-chip {
        background: rgba(124, 155, 179, 0.20); border-color: rgba(124, 155, 179, 0.34); color: #bcd2e2;
    }
    html[data-theme="dark"] .lote-preview__error {
        background: rgba(120, 30, 25, 0.28); border-color: rgba(196, 69, 54, 0.4); color: #f4b4ab;
    }
    html[data-theme="dark"] .lote-toggle-inherit { color: #E4C58C; }
    html[data-theme="dark"] .lote-cancel {
        background: #2C2C2E; border-color: #38383A; color: #F5F5F7;
    }
    html[data-theme="dark"] .lote-cancel:hover { background: #38383A; }
</style>

<div class="lote-page">
    <div class="lote-shell">
        <div class="lote-breadcrumb">
            <?php $back_arrow_href = back_url('habitaciones'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('habitaciones') ?>" class="ms-back-legacy">
                <i class="fas fa-arrow-left"></i> Volver a Habitaciones
            </a>
        </div>

        <div class="lote-hero">
            <h1>Crear habitaciones en lote</h1>
            <p>Registra un bloque de habitaciones iguales de una sola vez. Ideal para dar de alta un hotel completo por piso — define el tipo, el precio y el rango de números, y el sistema las genera todas.</p>
        </div>

        <form method="POST" action="<?= url('habitaciones/lote/guardar') ?>" id="form-lote" data-ms-no-summary="1">
            <?= csrf_field() ?>

            <div class="lote-grid">
                <!-- Columna principal -->
                <div class="lote-main" style="display:flex; flex-direction:column; gap:1.5rem;">

                    <!-- Atributos comunes -->
                    <div class="lote-card lote-sec-basic">
                        <div class="lote-card__head">
                            <i class="fas fa-layer-group"></i>
                            <h2>Datos comunes del bloque</h2>
                        </div>
                        <div class="lote-card__body">
                            <div class="lote-row cols-2">
                                <div class="lote-field">
                                    <label>Piso <span class="req">*</span></label>
                                    <select name="piso" required>
                                        <option value="">Seleccione el piso</option>
                                        <?php foreach ($pisos as $key => $piso): ?>
                                            <option value="<?= $key ?>" <?= (string)old('piso') === (string)$key ? 'selected' : '' ?>><?= htmlspecialchars((string)$piso, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="lote-field">
                                    <label>Tipo de habitación <span class="req">*</span></label>
                                    <select name="tipo" required>
                                        <option value="">Seleccione el tipo</option>
                                        <?php foreach ($tipos as $key => $tipo): ?>
                                            <option value="<?= $key ?>" <?= (string)old('tipo') === (string)$key ? 'selected' : '' ?>><?= htmlspecialchars((string)$tipo, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="lote-field">
                                <label>Precio por noche <span class="req">*</span></label>
                                <input type="number" name="precio_base" data-money-format="true" value="<?= old('precio_base', '550.00') ?>" min="0" step="0.01" required>
                                <p class="lote-hint">Se aplica a todas las habitaciones del lote. Podrás ajustar tarifas por temporada después.</p>
                            </div>

                            <button type="button" class="lote-toggle-inherit" id="lote-toggle-advanced">
                                <i class="fas fa-sliders-h"></i>
                                <span>Capacidad y camas (opcional — se heredan del tipo)</span>
                            </button>
                            <div class="lote-advanced" id="lote-advanced">
                                <div class="lote-row cols-3" style="margin-top:.4rem;">
                                    <div class="lote-field">
                                        <label>Capacidad</label>
                                        <input type="number" name="capacidad_personas" value="<?= old('capacidad_personas') ?>" min="1" max="30" step="1" placeholder="Auto">
                                    </div>
                                    <div class="lote-field">
                                        <label>Camas matrimoniales</label>
                                        <input type="number" name="camas_matrimoniales" value="<?= old('camas_matrimoniales') ?>" min="0" max="20" step="1" placeholder="Auto">
                                    </div>
                                    <div class="lote-field">
                                        <label>Camas individuales</label>
                                        <input type="number" name="camas_individuales" value="<?= old('camas_individuales') ?>" min="0" max="20" step="1" placeholder="Auto">
                                    </div>
                                </div>
                            </div>

                            <label style="display:flex; align-items:flex-start; gap:.6rem; cursor:pointer;">
                                <input type="checkbox" name="activa" value="1" checked style="width:1.1rem;height:1.1rem;margin-top:.15rem;">
                                <span style="font-size:.9rem;color:var(--room-ink);">Activar las habitaciones (disponibles para reserva)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Numeración por rango -->
                    <div class="lote-card lote-sec-range">
                        <div class="lote-card__head">
                            <i class="fas fa-hashtag"></i>
                            <h2>Numeración (piso + rango)</h2>
                        </div>
                        <div class="lote-card__body">
                            <div class="lote-row cols-3">
                                <div class="lote-field">
                                    <label>Del número <span class="req">*</span></label>
                                    <input type="number" name="numero_desde" id="lote-desde" value="<?= old('numero_desde', '101') ?>" min="0" step="1" required>
                                </div>
                                <div class="lote-field">
                                    <label>Al número <span class="req">*</span></label>
                                    <input type="number" name="numero_hasta" id="lote-hasta" value="<?= old('numero_hasta', '120') ?>" min="0" step="1" required>
                                </div>
                                <div class="lote-field">
                                    <label>Prefijo (opcional)</label>
                                    <input type="text" name="prefijo" id="lote-prefijo" value="<?= old('prefijo') ?>" maxlength="8" placeholder="Ej: A">
                                </div>
                            </div>
                            <div class="lote-field">
                                <label>Relleno con ceros (opcional)</label>
                                <select name="ancho" id="lote-ancho">
                                    <option value="0" <?= (string)old('ancho', '0') === '0' ? 'selected' : '' ?>>Sin relleno (101, 102…)</option>
                                    <option value="2" <?= (string)old('ancho') === '2' ? 'selected' : '' ?>>2 dígitos (01, 02…)</option>
                                    <option value="3" <?= (string)old('ancho') === '3' ? 'selected' : '' ?>>3 dígitos (001, 002…)</option>
                                </select>
                                <p class="lote-hint">Convención típica: piso 1 → 101–120, piso 2 → 201–220. Los números ya ocupados se omiten automáticamente.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral: vista previa -->
                <div class="lote-side">
                    <div class="lote-preview">
                        <div class="lote-preview__head">
                            <h3>Vista previa</h3>
                            <span class="lote-count" id="lote-count">0 habitaciones</span>
                        </div>
                        <div class="lote-preview__error" id="lote-error"></div>
                        <div class="lote-preview__body">
                            <div class="lote-chips" id="lote-chips"></div>
                            <div class="lote-preview__empty" id="lote-empty">Ajusta el rango para ver los números que se crearán.</div>
                        </div>
                    </div>

                    <div class="lote-actions">
                        <button type="submit" class="lote-submit" id="lote-submit">
                            <i class="fas fa-bolt"></i>
                            <span id="lote-submit-label">Crear habitaciones</span>
                        </button>
                        <a href="<?= back_url('habitaciones') ?>" class="lote-cancel">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var desde = document.getElementById('lote-desde');
    var hasta = document.getElementById('lote-hasta');
    var prefijo = document.getElementById('lote-prefijo');
    var ancho = document.getElementById('lote-ancho');
    var chips = document.getElementById('lote-chips');
    var empty = document.getElementById('lote-empty');
    var count = document.getElementById('lote-count');
    var errorBox = document.getElementById('lote-error');
    var submit = document.getElementById('lote-submit');
    var submitLabel = document.getElementById('lote-submit-label');
    var MAX = 500;

    function pad(n, width) {
        var s = String(n);
        while (width > 0 && s.length < width) { s = '0' + s; }
        return s;
    }

    function setError(msg) {
        if (msg) {
            errorBox.textContent = msg;
            errorBox.classList.add('is-visible');
            count.classList.add('is-error');
        } else {
            errorBox.textContent = '';
            errorBox.classList.remove('is-visible');
            count.classList.remove('is-error');
        }
    }

    function render() {
        var d = parseInt(desde.value, 10);
        var h = parseInt(hasta.value, 10);
        var w = parseInt(ancho.value, 10) || 0;
        var pre = (prefijo.value || '').trim();

        chips.innerHTML = '';

        if (isNaN(d) || isNaN(h) || desde.value === '' || hasta.value === '') {
            empty.style.display = '';
            count.textContent = '0 habitaciones';
            setError('');
            submit.disabled = false;
            submitLabel.textContent = 'Crear habitaciones';
            return;
        }
        if (h < d) {
            empty.style.display = 'none';
            count.textContent = '—';
            setError('El número final debe ser mayor o igual que el inicial.');
            submit.disabled = true;
            return;
        }

        var total = h - d + 1;
        if (total > MAX) {
            empty.style.display = 'none';
            count.textContent = total + ' habitaciones';
            setError('El lote no puede exceder ' + MAX + ' habitaciones por vez.');
            submit.disabled = true;
            return;
        }

        // Verificar longitud del número final (varchar 10).
        var muestraFinal = pre + pad(h, w);
        if (muestraFinal.length > 10) {
            empty.style.display = 'none';
            count.textContent = total + ' habitaciones';
            setError('El número "' + muestraFinal + '" excede 10 caracteres; acorta el prefijo o el relleno.');
            submit.disabled = true;
            return;
        }

        setError('');
        submit.disabled = false;
        empty.style.display = 'none';
        count.textContent = total + (total === 1 ? ' habitación' : ' habitaciones');
        submitLabel.textContent = 'Crear ' + total + (total === 1 ? ' habitación' : ' habitaciones');

        // Mostrar hasta 60 chips; el resto se resume.
        var limite = Math.min(total, 60);
        var frag = document.createDocumentFragment();
        for (var i = 0; i < limite; i++) {
            var span = document.createElement('span');
            span.className = 'lote-chip';
            span.textContent = pre + pad(d + i, w);
            frag.appendChild(span);
        }
        chips.appendChild(frag);
        if (total > limite) {
            var more = document.createElement('span');
            more.className = 'lote-chip';
            more.style.background = 'rgba(127,152,125,.16)';
            more.style.borderColor = 'rgba(127,152,125,.32)';
            more.style.color = '#496047';
            more.textContent = '+' + (total - limite) + ' más';
            chips.appendChild(more);
        }
    }

    [desde, hasta, prefijo, ancho].forEach(function (el) {
        el.addEventListener('input', render);
        el.addEventListener('change', render);
    });
    render();

    // Toggle de capacidad/camas avanzado.
    var toggle = document.getElementById('lote-toggle-advanced');
    var advanced = document.getElementById('lote-advanced');
    if (toggle && advanced) {
        toggle.addEventListener('click', function () {
            advanced.classList.toggle('is-open');
        });
    }
});
</script>
