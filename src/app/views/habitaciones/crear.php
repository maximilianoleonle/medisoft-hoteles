<!-- app/views/habitaciones/crear.php -->
<style id="create-room-redesign">
    .create-room-page {
        --room-ink: #2d302f;
        --room-muted: #68716d;
        --room-line: rgba(55, 64, 60, 0.12);
        --room-paper: rgba(255, 255, 255, 0.94);
        --room-soft: #f7f4ed;
        --room-gold: color-mix(in srgb, var(--brand-accent, #b58b4a) 52%, #b58b4a);
        --room-brown: color-mix(in srgb, var(--brand-primary, #765438) 24%, #6b5138);
        --room-sky: #7c9bb3;
        --room-sage: #7f987d;
        --room-clay: #c47f67;
        min-height: 100dvh;
        padding: clamp(1rem, 2.2vw, 2rem);
        color: var(--room-ink);
        background:
            radial-gradient(circle at 6% 8%, rgba(181, 139, 74, 0.16), transparent 30rem),
            radial-gradient(circle at 92% 12%, rgba(124, 155, 179, 0.18), transparent 28rem),
            linear-gradient(135deg, #fbf7ef 0%, #f5f2ea 42%, #eef3f0 100%);
    }
    .create-room-page > * { position: relative; z-index: 1; }

    .create-room-shell { max-width: 1040px; margin: 0 auto; }

    .create-room-breadcrumb {
        display: flex; align-items: center; gap: .5rem;
        margin-bottom: .25rem;
    }
    .create-room-breadcrumb a {
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

    .create-room-hero-card {
        position: relative; overflow: hidden;
        margin: 1rem 0 1.5rem;
        padding: 1.75rem 1.9rem;
        border: 1px solid rgba(70, 78, 72, 0.12);
        border-radius: 1.25rem;
        background: var(--room-paper);
        box-shadow: 0 22px 55px rgba(57, 49, 37, 0.12);
        backdrop-filter: blur(14px);
    }
    .create-room-hero-card::before {
        content: ""; position: absolute; inset: 0;
        border-left: 7px solid var(--room-gold);
        background: linear-gradient(110deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.55));
        pointer-events: none;
    }
    .create-room-hero-card h1 {
        position: relative;
        font-size: clamp(1.7rem, 4vw, 2.3rem);
        font-weight: 700; line-height: 1.05; color: var(--room-ink);
        text-wrap: balance;
    }
    .create-room-hero-card p {
        position: relative; margin-top: .4rem;
        color: var(--room-muted); max-width: 60ch;
    }
    .create-room-lote-link {
        position: relative;
        display: inline-flex; align-items: center; gap: .5rem;
        margin-top: .9rem; padding: .55rem .95rem;
        border: 1px solid rgba(118, 84, 56, 0.18);
        border-radius: 999px;
        background: color-mix(in srgb, var(--room-gold) 10%, white);
        color: var(--room-brown);
        font-size: .82rem; font-weight: 600; text-decoration: none;
        transition: background .15s ease, transform .15s ease;
    }
    .create-room-lote-link:hover {
        background: color-mix(in srgb, var(--room-gold) 18%, white);
        transform: translateY(-1px);
    }
    .create-room-lote-link i { color: var(--room-gold); font-size: .78rem; }

    .create-room-form-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(280px, 340px);
        gap: clamp(1rem, 2.3vw, 1.75rem);
        align-items: start;
    }
    .create-room-main { min-width: 0; display: flex; flex-direction: column; gap: 1.5rem; }

    .create-room-card {
        position: relative; overflow: hidden;
        border: 1px solid rgba(70, 78, 72, 0.12);
        border-radius: 1.25rem;
        background: var(--room-paper);
        box-shadow: 0 22px 55px rgba(57, 49, 37, 0.10);
        backdrop-filter: blur(14px);
    }
    .create-room-card::before,
    .create-room-active-card::before {
        content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 6px;
        background: var(--section-accent, var(--room-gold));
    }
    .create-room-card > div:first-child {
        display: flex; align-items: center; gap: .7rem;
        padding: 1.1rem 1.4rem 1.1rem 1.6rem;
        border-bottom: 1px solid var(--room-line);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(247, 244, 237, 0.72));
    }
    .create-room-card > div:first-child h2 {
        font-size: 1.05rem; font-weight: 700; color: var(--room-ink);
        display: flex; align-items: center;
    }
    .create-room-card > div:first-child h2 i {
        display: inline-grid; place-items: center;
        width: 2.35rem; height: 2.35rem; margin-right: 0.7rem;
        border-radius: .9rem;
        background: color-mix(in srgb, var(--section-accent, var(--room-gold)) 18%, white);
        color: var(--section-accent, var(--room-gold));
    }
    .create-room-card__body { padding: 1.4rem; display: flex; flex-direction: column; gap: 1.4rem; }

    .create-section-basic { --section-accent: var(--room-gold); }
    .create-section-features { --section-accent: var(--room-sky); }
    .create-section-photos { --section-accent: var(--room-clay); }
    .create-room-active-card { --section-accent: var(--room-sage); }

    .create-room-row { display: grid; gap: 1rem; }
    .create-room-row.cols-2 { grid-template-columns: 1fr 1fr; }
    .create-room-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }

    .create-room-page label.field-label,
    .create-room-field > label {
        display: block; font-size: .8rem; font-weight: 600; color: #3f4743; margin-bottom: .4rem;
    }
    .create-room-field .req { color: #dc2626; }
    .create-room-hint { font-size: .74rem; color: var(--room-muted); margin-top: .35rem; }

    .create-room-page input:not([type="checkbox"]):not([type="file"]):not([type="hidden"]),
    .create-room-page select,
    .create-room-page textarea {
        width: 100%; padding: .7rem .85rem; border-radius: .8rem;
        border: 1px solid rgba(71, 82, 76, 0.18);
        background: rgba(255, 255, 255, 0.86);
        color: var(--room-ink); font-size: .95rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }
    .create-room-page input:not([type="checkbox"]):not([type="file"]):not([type="hidden"]):focus,
    .create-room-page select:focus,
    .create-room-page textarea:focus {
        outline: none;
        border-color: color-mix(in srgb, var(--room-brown) 62%, white);
        box-shadow: 0 0 0 4px rgba(181, 139, 74, 0.16);
    }
    .create-room-page input[type="checkbox"] { accent-color: var(--room-sage); }

    .create-room-price-wrap { position: relative; }
    .create-room-price-wrap .cur { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--room-gold); }
    .create-room-price-wrap .mxn { position: absolute; right: .85rem; top: 50%; transform: translateY(-50%); font-size: .75rem; color: var(--room-muted); }
    .create-room-price-wrap input { padding-left: 2rem !important; padding-right: 3rem !important; font-weight: 600; }

    .create-room-error {
        display: block; margin-top: 0.4rem;
        color: #b42318; font-size: 0.76rem; font-weight: 700;
    }

    /* Características incluidas + especiales */
    .create-room-standard-feature {
        display: flex; align-items: center;
        min-height: 3.4rem; padding: .8rem;
        border: 1px solid rgba(71, 82, 76, 0.10);
        border-radius: 0.8rem;
        background: rgba(247, 249, 248, 0.72);
        color: var(--room-ink);
    }
    .create-room-standard-feature i,
    .create-room-special-feature i { width: 1.2rem; margin-right: 0.6rem; text-align: center; }
    .create-room-standard-feature span, .create-room-special-feature span { min-width: 0; }

    .create-room-special-feature {
        display: flex; align-items: center; min-height: 3.2rem; padding: .8rem;
        border: 1px solid rgba(71, 82, 76, 0.13);
        border-radius: 0.8rem;
        background: rgba(249, 248, 244, 0.78);
        box-shadow: 0 10px 22px rgba(57, 49, 37, 0.05);
        cursor: pointer; transition: border-color .15s ease, background .15s ease, transform .15s ease;
    }
    .create-room-special-feature:hover {
        border-color: rgba(124, 155, 179, 0.45);
        background: rgba(240, 246, 247, 0.95);
        transform: translateY(-1px);
    }
    .create-room-special-feature input[type="checkbox"] {
        width: 1rem; height: 1rem; margin-right: 0.7rem; flex: 0 0 auto;
    }

    /* Zona de fotos */
    .create-section-photos #drop-zone {
        border: 2px dashed rgba(196, 127, 103, 0.34) !important;
        border-radius: .9rem;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(250, 243, 238, 0.88));
        transition: border-color .15s ease, background .15s ease;
    }
    .create-section-photos #drop-zone:hover,
    .create-section-photos #drop-zone.border-purple-400 {
        border-color: var(--room-clay) !important;
        background: rgba(252, 241, 236, 0.9) !important;
    }
    .create-section-photos .text-purple-800,
    .create-section-photos .text-purple-700,
    .create-section-photos .text-blue-600 { color: #8d5b4b !important; }
    .create-section-photos .bg-purple-50 { background-color: rgba(196, 127, 103, 0.12) !important; }
    .create-section-photos .bg-purple-500 { background-color: var(--room-clay) !important; }

    .create-room-upload-alert {
        display: none; align-items: flex-start; gap: 0.65rem;
        margin-top: 1rem; padding: 0.8rem 0.9rem;
        border: 1px solid rgba(196, 69, 54, 0.24);
        border-radius: 0.9rem;
        background: rgba(254, 242, 242, 0.92);
        color: #991b1b; font-size: 0.84rem; font-weight: 700; line-height: 1.4;
    }
    .create-room-upload-alert.is-visible { display: flex; }
    .create-room-upload-alert i { margin-top: 0.12rem; color: #b91c1c; }

    /* Panel lateral sticky */
    .create-room-side { position: sticky; top: 1rem; display: flex; flex-direction: column; gap: 1rem; align-self: start; }

    .create-room-active-card {
        position: relative; overflow: hidden;
        border: 1px solid rgba(70, 78, 72, 0.12);
        border-radius: 1.25rem;
        padding: 1.25rem 1.25rem 1.25rem 1.5rem;
        background: var(--room-paper);
        box-shadow: 0 22px 55px rgba(57, 49, 37, 0.10);
        backdrop-filter: blur(14px);
    }
    .create-room-active-card span { color: var(--room-ink); font-weight: 600; }
    .create-room-active-card p { color: var(--room-muted); }

    .create-room-actions { display: flex; flex-direction: column; gap: .65rem; }
    .create-room-submit {
        width: 100%; padding: .95rem 1.25rem; border: 0; border-radius: .9rem;
        background: linear-gradient(135deg, #344139, var(--room-brown)); color: #fff;
        font-weight: 700; font-size: .98rem; cursor: pointer;
        box-shadow: 0 15px 30px rgba(73, 56, 39, 0.2);
        display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .create-room-submit:hover { transform: translateY(-1px); box-shadow: 0 18px 34px rgba(73, 56, 39, 0.26); }
    .create-room-cancel {
        width: 100%; padding: .8rem 1.25rem; border-radius: .9rem;
        border: 1px solid rgba(70, 78, 72, 0.16); background: rgba(255, 255, 255, 0.78);
        color: #46504b; font-weight: 600; text-align: center;
        display: inline-flex; align-items: center; justify-content: center; gap: .5rem; text-decoration: none;
    }
    .create-room-cancel:hover { background: rgba(247, 244, 237, 0.9); }

    @media (max-width: 900px) {
        .create-room-form-grid { grid-template-columns: 1fr; }
        .create-room-side { position: relative; top: auto; }
    }
    @media (max-width: 560px) {
        .create-room-row.cols-2,
        .create-room-row.cols-3 { grid-template-columns: 1fr; }
        .create-room-page { padding: 1rem; }
        .create-room-hero-card { padding: 1.5rem; }
    }

    /* ── Modo oscuro (theme-agnóstico: aplica en Deleite y Cupertino) ──
       Remapea los tokens --room-* y oscurece las superficies con color fijo.
       Paleta canónica: #1C1C1E elevado · #161617 hundido · #38383A bordes. */
    html[data-theme="dark"] .create-room-page {
        --room-ink: #F5F5F7;
        --room-muted: #98989D;
        --room-line: rgba(255, 255, 255, 0.09);
        --room-paper: #1C1C1E;
        --room-soft: #161617;
        background:
            radial-gradient(circle at 6% 8%, rgba(181, 139, 74, 0.10), transparent 30rem),
            radial-gradient(circle at 92% 12%, rgba(124, 155, 179, 0.10), transparent 28rem),
            linear-gradient(135deg, #161617 0%, #1a1a1c 55%, #141416 100%);
    }
    html[data-theme="dark"] .create-room-breadcrumb a {
        background: #1C1C1E; border-color: #38383A; color: #E4C58C;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.4);
    }
    html[data-theme="dark"] .create-room-lote-link {
        background: #2C2C2E; border-color: #38383A; color: #E4C58C;
    }
    html[data-theme="dark"] .create-room-lote-link:hover { background: #38383A; }
    html[data-theme="dark"] .create-room-hero-card,
    html[data-theme="dark"] .create-room-card,
    html[data-theme="dark"] .create-room-active-card { box-shadow: 0 22px 55px rgba(0, 0, 0, 0.5); }
    html[data-theme="dark"] .create-room-hero-card::before {
        background: linear-gradient(110deg, rgba(28, 28, 30, 0.92), rgba(28, 28, 30, 0.6));
    }
    html[data-theme="dark"] .create-room-card > div:first-child {
        background: #161617; border-bottom-color: var(--room-line);
    }
    html[data-theme="dark"] .create-room-card > div:first-child h2 i {
        background: color-mix(in srgb, var(--section-accent, var(--room-gold)) 26%, #1C1C1E);
    }
    html[data-theme="dark"] .create-room-field > label { color: #D6D8D6; }
    html[data-theme="dark"] .create-room-page input:not([type="checkbox"]):not([type="file"]):not([type="hidden"]),
    html[data-theme="dark"] .create-room-page select,
    html[data-theme="dark"] .create-room-page textarea {
        background: #1C1C1E; border-color: #38383A; color: #F5F5F7; box-shadow: none;
    }
    html[data-theme="dark"] .create-room-standard-feature {
        background: #161617; border-color: rgba(255, 255, 255, 0.06);
    }
    html[data-theme="dark"] .create-room-special-feature {
        background: #1C1C1E; border-color: #38383A; box-shadow: none;
    }
    html[data-theme="dark"] .create-room-special-feature:hover {
        background: #232326; border-color: rgba(124, 155, 179, 0.5);
    }
    html[data-theme="dark"] .create-section-photos #drop-zone {
        background: linear-gradient(135deg, #1c1c1e, #161617) !important;
        border-color: rgba(196, 127, 103, 0.4) !important;
    }
    html[data-theme="dark"] .create-section-photos .bg-purple-50 { background-color: rgba(196, 127, 103, 0.14) !important; }
    html[data-theme="dark"] .create-section-photos .text-purple-800,
    html[data-theme="dark"] .create-section-photos .text-purple-700 { color: #d9a88f !important; }
    html[data-theme="dark"] .create-room-upload-alert {
        background: rgba(120, 30, 25, 0.28); border-color: rgba(196, 69, 54, 0.4); color: #f4b4ab;
    }
    html[data-theme="dark"] .create-room-upload-alert i { color: #f4b4ab; }
    html[data-theme="dark"] .create-room-cancel {
        background: #2C2C2E; border-color: #38383A; color: #F5F5F7;
    }
    html[data-theme="dark"] .create-room-cancel:hover { background: #38383A; }
</style>

<div class="create-room-page">
    <div class="create-room-shell">
        <div class="create-room-breadcrumb">
            <?php $back_arrow_href = back_url('habitaciones'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('habitaciones') ?>" class="ms-back-legacy">
                <i class="fas fa-arrow-left"></i> Volver a Habitaciones
            </a>
        </div>

        <div class="create-room-hero-card">
            <h1>Registrar nueva habitación</h1>
            <p>Complete la información para agregar una habitación al hotel — tipo, tarifa, capacidad, características y fotografías.</p>
            <a href="<?= url('habitaciones/lote') ?>" class="create-room-lote-link" title="Crear varias habitaciones a la vez (por piso y rango)">
                <i class="fas fa-layer-group"></i>
                <span>¿Vas a registrar varias? Créalas en lote</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <form method="POST" action="<?= url('habitaciones/store') ?>" enctype="multipart/form-data" id="form-habitacion">
            <?= csrf_field() ?>

            <div class="create-room-form-grid">
                <!-- Columna principal -->
                <div class="create-room-main">
                    <!-- Información Básica -->
                    <div class="create-room-card create-section-basic">
                        <div>
                            <h2><i class="fas fa-info-circle"></i>Información básica</h2>
                        </div>

                        <div class="create-room-card__body">
                            <div class="create-room-row cols-2">
                                <div class="create-room-field">
                                    <label>Número de habitación <span class="req">*</span></label>
                                    <input type="text" name="numero" value="<?= old('numero') ?>" required>
                                    <?php if (form_error('numero')): ?>
                                        <span class="create-room-error"><?= form_error('numero') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="create-room-field">
                                    <label>Tipo de habitación <span class="req">*</span></label>
                                    <select name="tipo" required>
                                        <option value="">Seleccione el tipo</option>
                                        <?php foreach ($tipos as $key => $tipo): ?>
                                            <option value="<?= $key ?>" <?= old('tipo') == $key ? 'selected' : '' ?>>
                                                <?= $tipo ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (form_error('tipo')): ?>
                                        <span class="create-room-error"><?= form_error('tipo') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="create-room-row cols-2">
                                <div class="create-room-field">
                                    <label>Piso <span class="req">*</span></label>
                                    <select name="piso" required>
                                        <option value="">Seleccione el piso</option>
                                        <?php foreach ($pisos as $key => $piso): ?>
                                            <option value="<?= $key ?>" <?= old('piso') == $key ? 'selected' : '' ?>>
                                                <?= $piso ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (form_error('piso')): ?>
                                        <span class="create-room-error"><?= form_error('piso') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="create-room-field">
                                    <label>Precio por noche <span class="req">*</span></label>
                                    <div class="create-room-price-wrap">
                                        <span class="cur">$</span>
                                        <input type="number" name="precio_base" data-money-format="true"
                                               value="<?= old('precio_base', '550.00') ?>" min="0" step="0.01" required>
                                        <span class="mxn">MXN</span>
                                    </div>
                                    <?php if (form_error('precio_base')): ?>
                                        <span class="create-room-error"><?= form_error('precio_base') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="create-room-row cols-3">
                                <div class="create-room-field">
                                    <label>Capacidad de personas <span class="req">*</span></label>
                                    <input type="number" name="capacidad_personas" value="<?= old('capacidad_personas', '2') ?>"
                                           min="1" max="30" step="1" required>
                                    <?php if (form_error('capacidad_personas')): ?>
                                        <span class="create-room-error"><?= form_error('capacidad_personas') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="create-room-field">
                                    <label>Camas matrimoniales <span class="req">*</span></label>
                                    <input type="number" name="camas_matrimoniales" value="<?= old('camas_matrimoniales', '1') ?>"
                                           min="0" max="20" step="1" required>
                                    <?php if (form_error('camas_matrimoniales')): ?>
                                        <span class="create-room-error"><?= form_error('camas_matrimoniales') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="create-room-field">
                                    <label>Camas individuales</label>
                                    <input type="number" name="camas_individuales" value="<?= old('camas_individuales', '0') ?>"
                                           min="0" max="20" step="1">
                                    <?php if (form_error('camas_individuales')): ?>
                                        <span class="create-room-error"><?= form_error('camas_individuales') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Características -->
                    <div class="create-room-card create-section-features">
                        <div>
                            <h2><i class="fas fa-list-check"></i>Características de la habitación</h2>
                        </div>

                        <div class="create-room-card__body">
                            <?php
                            // Lo incluido en TODAS las habitaciones se configura por hotel
                            // (/configuracion → Habitaciones): varia mucho de un hotel a otro.
                            $caracteristicasBaseHabitacion = is_array($incluidos ?? null) ? $incluidos : [];
                            // Solo Medisoft edita esta lista (Panel SaaS): para el
                            // hotel se muestra sin enlace.
                            $urlConfigIncluidos = function_exists('config_hotel_url') ? config_hotel_url('#hc-rooms') : null;
                            $puedeConfigurarIncluidos = $urlConfigIncluidos !== null
                                && function_exists('can') && can('configuracion.edit');
                            ?>
                            <div>
                                <?php if (!empty($caracteristicasBaseHabitacion)): ?>
                                    <p class="create-room-hint" style="font-size:.82rem;color:var(--room-ink);font-weight:600;margin-bottom:.6rem;">Características incluidas en todas las habitaciones:</p>
                                    <div class="create-room-row" style="grid-template-columns:repeat(2,1fr);">
                                        <?php foreach ($caracteristicasBaseHabitacion as $caracteristicaBase): ?>
                                            <div class="create-room-standard-feature">
                                                <i class="fas fa-<?= htmlspecialchars((string) ($caracteristicaBase['icono'] ?? 'check-circle'), ENT_QUOTES, 'UTF-8') ?>"
                                                   style="color: <?= htmlspecialchars((string) ($caracteristicaBase['color'] ?? '#64748b'), ENT_QUOTES, 'UTF-8') ?>;"></i>
                                                <span class="text-xs font-medium"><?= htmlspecialchars((string) ($caracteristicaBase['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($puedeConfigurarIncluidos): ?>
                                        <p class="create-room-hint" style="margin-top:.55rem;">
                                            Esta lista se ajusta en
                                            <a href="<?= htmlspecialchars($urlConfigIncluidos, ENT_QUOTES, 'UTF-8') ?>" style="color:var(--room-ink);text-decoration:underline;">Configuración › Habitaciones</a>.
                                        </p>
                                    <?php endif; ?>
                                <?php elseif ($puedeConfigurarIncluidos): ?>
                                    <p class="create-room-hint" style="margin-bottom:.6rem;">
                                        Este hotel no tiene servicios incluidos en todas las habitaciones.
                                        <a href="<?= htmlspecialchars($urlConfigIncluidos, ENT_QUOTES, 'UTF-8') ?>" style="color:var(--room-ink);text-decoration:underline;">Configurarlos</a>.
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <p class="create-room-hint" style="font-size:.82rem;color:var(--room-ink);font-weight:600;margin-bottom:.6rem;">Características especiales:</p>
                                <div class="create-room-row cols-2">
                                    <?php
                                    $amenidadesHabitacion = is_array($amenidades ?? null) && !empty($amenidades)
                                        ? $amenidades
                                        : [
                                            'pantalla' => 'Pantalla',
                                            'balcon' => 'Balcon',
                                            'jacuzzi' => 'Jacuzzi',
                                            'amplia' => 'Mas amplia',
                                        ];
                                    $amenidadIconos = [
                                        'pantalla' => ['icon' => 'tv', 'color' => '#64748b'],
                                        'balcon' => ['icon' => 'home', 'color' => '#64748b'],
                                        'jacuzzi' => ['icon' => 'bath', 'color' => '#64748b', 'disabled_for' => ['doble_jacuzzi', 'sencilla_jacuzzi']],
                                        'amplia' => ['icon' => 'expand-arrows-alt', 'color' => '#64748b'],
                                        'internet' => ['icon' => 'check-circle', 'color' => '#64748b'],
                                    ];
                                    $oldEspeciales = $_SESSION['old_input']['caracteristicas_especiales'] ?? [];
                                    $oldEspeciales = is_array($oldEspeciales) ? $oldEspeciales : [];
                                    $tipoSeleccionadoCaracteristicas = (string) old('tipo', '');
                                    ?>
                                    <?php foreach ($amenidadesHabitacion as $amenidadKey => $amenidadLabel): ?>
                                        <?php
                                        $amenidadKey = (string) $amenidadKey;
                                        $amenidadMeta = $amenidadIconos[$amenidadKey] ?? ['icon' => 'check-circle', 'color' => '#64748b'];
                                        $isDisabled = isset($amenidadMeta['disabled_for']) && in_array($tipoSeleccionadoCaracteristicas, $amenidadMeta['disabled_for'], true);
                                        $isChecked = in_array($amenidadKey, $oldEspeciales, true) || $isDisabled;
                                        ?>
                                        <label class="create-room-special-feature <?= $isDisabled ? 'opacity-60 cursor-not-allowed' : '' ?>">
                                            <input type="checkbox"
                                                   name="caracteristicas_especiales[]"
                                                   value="<?= htmlspecialchars($amenidadKey, ENT_QUOTES, 'UTF-8') ?>"
                                                   <?= $isChecked ? 'checked' : '' ?>
                                                   <?= $isDisabled ? 'disabled' : '' ?>>
                                            <i class="fas fa-<?= htmlspecialchars($amenidadMeta['icon'], ENT_QUOTES, 'UTF-8') ?>"
                                               style="color: <?= htmlspecialchars($amenidadMeta['color'], ENT_QUOTES, 'UTF-8') ?>;"></i>
                                            <span class="text-sm font-medium"><?= htmlspecialchars((string) $amenidadLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if ($isDisabled): ?>
                                                <span class="ml-2 text-xs" style="color:var(--room-muted);">(incluido en el tipo)</span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="create-room-field">
                                <label>Descripción completa de características</label>
                                <?php
                                $ejemploIncluidos = implode(', ', array_filter(array_map(static function ($item) {
                                    return trim((string) ($item['label'] ?? ''));
                                }, $caracteristicasBaseHabitacion)));
                                $placeholderCaracteristicas = 'Ejemplo: 2 camas matrimoniales, pantalla, balcón'
                                    . ($ejemploIncluidos !== '' ? ', ' . $ejemploIncluidos : '');
                                ?>
                                <textarea name="caracteristicas" rows="4"
                                          placeholder="<?= htmlspecialchars($placeholderCaracteristicas, ENT_QUOTES, 'UTF-8') ?>"><?= old('caracteristicas') ?></textarea>
                                <p class="create-room-hint">Esta descripción se genera automáticamente con el tipo y características seleccionadas, pero puede personalizarse.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Fotografías -->
                    <div class="create-room-card create-section-photos">
                        <div>
                            <h2><i class="fas fa-images"></i>Fotografías de la habitación</h2>
                        </div>

                        <div class="create-room-card__body">
                            <!-- Zona de carga múltiple -->
                            <div id="drop-zone" style="padding:2rem;text-align:center;">
                                <input type="file" name="fotos[]" accept="image/*" id="fotos-input" multiple class="hidden">
                                <label for="fotos-input" class="cursor-pointer">
                                    <i class="fas fa-cloud-upload-alt" style="font-size:2.6rem;color:#c9b8ac;display:block;margin-bottom:.7rem;"></i>
                                    <p style="color:var(--room-ink);font-weight:600;margin-bottom:.35rem;">Click para subir imágenes</p>
                                    <p class="text-sm" style="color:var(--room-muted);">JPG, PNG, GIF o WebP - Máximo 5MB por imagen</p>
                                    <p class="text-xs" style="color:var(--room-muted);margin-top:.2rem;">Puedes seleccionar múltiples imágenes</p>
                                    <p class="text-xs text-blue-600" style="margin-top:.5rem;">O arrastra y suelta las imágenes aquí</p>
                                </label>
                            </div>

                            <div id="upload-alert" class="create-room-upload-alert" role="alert" aria-live="assertive" hidden>
                                <i class="fas fa-circle-exclamation"></i>
                                <span></span>
                            </div>
                            <?php if (form_error('fotos[]')): ?>
                                <span class="create-room-error"><?= form_error('fotos[]') ?></span>
                            <?php endif; ?>

                            <div id="preview-container" class="mt-4 hidden">
                                <h4 class="text-sm font-semibold" style="color:#3f4743;margin-bottom:.7rem;">Imágenes seleccionadas:</h4>
                                <div id="preview-grid" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <!-- Las previsualizaciones se agregarán aquí dinámicamente -->
                                </div>
                            </div>

                            <div class="mt-2 bg-purple-50 rounded-lg" style="padding:.9rem 1rem;">
                                <h4 class="text-sm font-semibold text-purple-800" style="margin-bottom:.4rem;">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Información sobre las imágenes
                                </h4>
                                <ul class="text-xs text-purple-700 space-y-1">
                                    <li>• La primera imagen será la principal</li>
                                    <li>• Puedes subir hasta 10 imágenes por habitación</li>
                                    <li>• Las imágenes se pueden reordenar después</li>
                                    <li>• Tamaño recomendado: 1920x1080 píxeles o mayor</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Script para manejo de múltiples imágenes -->
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const fotosInput = document.getElementById('fotos-input');
                        const previewContainer = document.getElementById('preview-container');
                        const previewGrid = document.getElementById('preview-grid');
                        const dropZone = document.getElementById('drop-zone');
                        const uploadAlert = document.getElementById('upload-alert');

                        let selectedFiles = [];
                        const maxFiles = 10;
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                        // Manejar selección de archivos
                        fotosInput.addEventListener('change', function(e) {
                            handleFiles(e.target.files);
                        });

                        // Drag and drop
                        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                            dropZone.addEventListener(eventName, preventDefaults, false);
                        });

                        function preventDefaults(e) {
                            e.preventDefault();
                            e.stopPropagation();
                        }

                        ['dragenter', 'dragover'].forEach(eventName => {
                            dropZone.addEventListener(eventName, highlight, false);
                        });

                        ['dragleave', 'drop'].forEach(eventName => {
                            dropZone.addEventListener(eventName, unhighlight, false);
                        });

                        function highlight(e) {
                            dropZone.classList.add('border-purple-400', 'bg-purple-50');
                        }

                        function unhighlight(e) {
                            dropZone.classList.remove('border-purple-400', 'bg-purple-50');
                        }

                        dropZone.addEventListener('drop', handleDrop, false);

                        function handleDrop(e) {
                            const dt = e.dataTransfer;
                            const files = dt.files;
                            handleFiles(files);
                        }

                        function handleFiles(files) {
                            const newFiles = Array.from(files);
                            clearUploadError();

                            // Validar cantidad total
                            if (selectedFiles.length + newFiles.length > maxFiles) {
                                showError(`Solo puedes subir hasta ${maxFiles} imagenes por habitacion.`);
                                return;
                            }

                            // Validar cada archivo
                            const validFiles = [];
                            for (let file of newFiles) {
                                if (!allowedTypes.includes(file.type)) {
                                    showError(`${file.name} no es una imagen válida`);
                                    continue;
                                }

                                if (file.size > maxSize) {
                                    showError(`${file.name} excede el tamaño máximo de 5MB`);
                                    continue;
                                }

                                validFiles.push(file);
                            }

                            if (validFiles.length > 0) {
                                selectedFiles = selectedFiles.concat(validFiles);
                                updatePreview();
                                updateFileInput();
                            }
                        }

                        function updatePreview() {
                            previewGrid.innerHTML = '';

                            if (selectedFiles.length === 0) {
                                previewContainer.classList.add('hidden');
                                return;
                            }

                            previewContainer.classList.remove('hidden');

                            selectedFiles.forEach((file, index) => {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    const previewItem = createPreviewItem(e.target.result, file, index);
                                    previewGrid.appendChild(previewItem);
                                };
                                reader.readAsDataURL(file);
                            });
                        }

                        function createPreviewItem(src, file, index) {
                            const div = document.createElement('div');
                            div.className = 'relative group';
                            div.innerHTML = `
                                <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden shadow-md">
                                    <img src="${src}" alt="${file.name}" class="w-full h-32 object-cover">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center">
                                        <button type="button" onclick="removeImage(${index})" class="bg-red-500 text-white px-3 py-1 rounded-lg text-sm hover:bg-red-600">
                                            <i class="fas fa-trash mr-1"></i>Eliminar
                                        </button>
                                    </div>
                                    ${index === 0 ? '<div class="absolute top-2 left-2 bg-purple-500 text-white px-2 py-1 rounded text-xs font-medium">Principal</div>' : ''}
                                </div>
                                <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
                                <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
                            `;
                            return div;
                        }

                        window.removeImage = function(index) {
                            selectedFiles.splice(index, 1);
                            updatePreview();
                            updateFileInput();
                        };

                        function updateFileInput() {
                            // Crear un nuevo DataTransfer para mantener los archivos seleccionados
                            const dataTransfer = new DataTransfer();
                            selectedFiles.forEach(file => {
                                dataTransfer.items.add(file);
                            });
                            fotosInput.files = dataTransfer.files;
                        }

                        function formatFileSize(bytes) {
                            if (bytes === 0) return '0 Bytes';
                            const k = 1024;
                            const sizes = ['Bytes', 'KB', 'MB'];
                            const i = Math.floor(Math.log(bytes) / Math.log(k));
                            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                        }

                        function showError(message) {
                            if (!uploadAlert) {
                                return;
                            }

                            const text = uploadAlert.querySelector('span');
                            if (text) {
                                text.textContent = message;
                            }

                            uploadAlert.hidden = false;
                            uploadAlert.classList.add('is-visible');
                            uploadAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }

                        function clearUploadError() {
                            if (!uploadAlert) {
                                return;
                            }

                            uploadAlert.hidden = true;
                            uploadAlert.classList.remove('is-visible');
                        }
                    });
                    </script>
                </div>

                <!-- Columna lateral -->
                <div class="create-room-side">
                    <!-- Estado activo -->
                    <div class="create-room-active-card">
                        <label class="flex items-start cursor-pointer" style="gap:.75rem;">
                            <input type="checkbox" name="activa" value="1" checked
                                   style="margin-top:.2rem;width:1.15rem;height:1.15rem;">
                            <div>
                                <span>Activar habitación</span>
                                <p class="text-sm" style="margin-top:.2rem;">La habitación estará disponible para reservas</p>
                            </div>
                        </label>
                    </div>

                    <!-- Botones -->
                    <div class="create-room-actions">
                        <button type="submit" class="create-room-submit">
                            <i class="fas fa-save"></i>
                            <span>Crear habitación</span>
                        </button>

                        <a href="<?= back_url('habitaciones') ?>" class="create-room-cancel">
                            <i class="fas fa-times"></i>
                            <span>Cancelar</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= asset('js/habitacion-images.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoSelect = document.querySelector('#form-habitacion select[name="tipo"]');
    const jacuzziCheckbox = document.querySelector('#form-habitacion input[name="caracteristicas_especiales[]"][value="jacuzzi"]');

    if (!tipoSelect || !jacuzziCheckbox) {
        return;
    }

    const jacuzziLabel = jacuzziCheckbox.closest('label');
    function actualizarJacuzziIncluido() {
        const incluido = tipoSelect.value === 'doble_jacuzzi' || tipoSelect.value === 'sencilla_jacuzzi';
        jacuzziCheckbox.disabled = incluido;
        jacuzziCheckbox.checked = incluido || jacuzziCheckbox.checked;

        if (jacuzziLabel) {
            jacuzziLabel.classList.toggle('opacity-60', incluido);
            jacuzziLabel.classList.toggle('cursor-not-allowed', incluido);
        }
    }

    tipoSelect.addEventListener('change', actualizarJacuzziIncluido);
    actualizarJacuzziIncluido();
});
</script>
