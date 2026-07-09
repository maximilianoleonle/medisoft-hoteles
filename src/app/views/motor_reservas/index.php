<?php
/**
 * Tablero interno del motor de reservas: pagos online, conciliacion a Caja
 * y configuracion (motor + pasarela). Vista interna con layout estandar.
 */
$pagos = $pagos ?? [];
$resumen = $resumen ?? ['por_conciliar' => 0, 'monto_por_conciliar' => 0, 'conciliados' => 0, 'con_problema' => 0];
$credenciales = $credenciales ?? null;
$config = $config ?? [];
$urlPublica = $urlPublica ?? '';
$urlWebhook = $urlWebhook ?? '';

$mrSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$mrMoney = static function ($v) {
    return '$' . number_format((float) ($v ?? 0), 2);
};
$mrEstadoBadge = static function ($estado) {
    $map = [
        'pagado' => ['Por conciliar', 'background:rgba(245,158,11,.14);color:#92600A;'],
        'conciliado' => ['Conciliado', 'background:rgba(22,163,74,.12);color:#15803D;'],
        'pendiente' => ['Esperando pago', 'background:rgba(100,116,139,.12);color:#64748B;'],
        'reembolsado' => ['Reembolsado', 'background:rgba(59,111,214,.12);color:#3B6FD6;'],
        'fallido' => ['Requiere atencion', 'background:rgba(220,38,38,.12);color:#B91C1C;'],
        'expirado' => ['Expirado', 'background:rgba(100,116,139,.10);color:#94A3B8;'],
    ];
    return $map[$estado] ?? [$estado, 'background:rgba(100,116,139,.10);color:#64748B;'];
};
$publicoActivo = !empty($config['publico_activo']);
$pasarelaLista = $credenciales && !empty($credenciales['secret_configurado']) && !empty($credenciales['activo']);
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');
.mrv { --mrv-line: color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); padding: 18px 16px 40px; max-width: 1180px; margin: 0 auto; font-size: .92rem; }
.mrv h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.mrv .sub { margin: 0 0 16px; color: #6B7486; }
.mrv-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 16px; }
.mrv-stat { background: #fff; border: 1px solid var(--mrv-line); border-radius: 12px; padding: 12px 14px; }
.mrv-stat span { display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; }
.mrv-stat strong { display: block; margin-top: 4px; font-size: 1.3rem; color: var(--brand-secondary, #0F172A); }
.mrv-card { background: #fff; border: 1px solid var(--mrv-line); border-radius: 14px; margin-bottom: 16px; overflow: hidden; }
.mrv-card-head { padding: 13px 16px; border-bottom: 1px solid var(--mrv-line); display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; }
.mrv-card-head h2 { margin: 0; font-size: 1rem; color: var(--brand-primary, #1B2746); }
.mrv-card-head p { margin: 2px 0 0; font-size: .8rem; color: #6B7486; width: 100%; }
.mrv-badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; }
.mrv-table-wrap { overflow-x: auto; }
.mrv table { width: 100%; border-collapse: collapse; }
.mrv th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid var(--mrv-line); white-space: nowrap; }
.mrv td { padding: 11px 12px; border-bottom: 1px solid color-mix(in srgb, var(--mrv-line) 70%, transparent); vertical-align: middle; }
.mrv-btn { display: inline-flex; align-items: center; gap: 6px; min-height: 38px; padding: 0 14px; border: 0; border-radius: 9px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .84rem; font-weight: 700; }
.mrv-btn:hover { opacity: .92; }
.mrv-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid var(--mrv-line); }
.mrv-empty { padding: 26px 16px; text-align: center; color: #8A93A6; }
.mrv-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; padding: 16px; }
.mrv-field { display: grid; gap: 4px; }
.mrv-field.full { grid-column: 1 / -1; }
.mrv-field label { font-size: .76rem; font-weight: 700; color: #55607A; }
.mrv-field input, .mrv-field select, .mrv-field textarea { min-height: 42px; border: 1px solid #D8D4C9; border-radius: 9px; padding: 8px 11px; font-size: .92rem; width: 100%; font-family: inherit; }
.mrv-field .hint { font-size: .72rem; color: #8A93A6; }
.mrv-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 8px; }
.mrv-link { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 12px 16px; background: color-mix(in srgb, var(--brand-accent, #BD9441) 6%, #FBF9F3); border-top: 1px solid var(--mrv-line); font-size: .84rem; }
.mrv-link code { background: #fff; border: 1px solid var(--mrv-line); border-radius: 7px; padding: 6px 10px; font-size: .8rem; word-break: break-all; }
.mrv-check { display: flex; align-items: center; gap: 8px; font-size: .9rem; font-weight: 600; color: #2A3242; }

/* Documents-aligned polish: calmer ink, lighter weights, same motor contracts. */
.mrv {
    --mrv-brand: var(--brand-primary, #1B2746);
    --mrv-brand-2: var(--brand-secondary, #0F172A);
    --mrv-gold: var(--brand-accent, #BD9441);
    --mrv-gold-soft: color-mix(in srgb, var(--mrv-gold) 10%, #FFFFFF);
    --mrv-gold-line: color-mix(in srgb, var(--mrv-gold) 28%, #ECE1D1);
    --mrv-gold-ink: color-mix(in srgb, var(--mrv-gold) 58%, var(--mrv-brand));
    --mrv-ivory: #F6F2EA;
    --mrv-ivory-2: #FBF8F2;
    --mrv-surface: rgba(255,255,255,.86);
    --mrv-surface-warm: #FCFAF5;
    --mrv-line: color-mix(in srgb, var(--mrv-brand) 6%, #E9E1D6);
    --mrv-ring: color-mix(in srgb, var(--mrv-gold) 32%, transparent);
    --mrv-text: color-mix(in srgb, var(--mrv-brand) 46%, #707B8C);
    --mrv-muted: #8791A2;
    --mrv-heading: color-mix(in srgb, var(--mrv-brand) 66%, #566172);
    --mrv-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --mrv-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --mrv-success: #1E9E63;
    --mrv-success-bg: #E7F4EC;
    --mrv-warning: #C2841C;
    --mrv-warning-bg: #FAF0DC;
    --mrv-danger: #B4392B;
    --mrv-danger-bg: #F8EAE5;
    --mrv-info: #2F77E0;
    --mrv-info-bg: #E6EFFC;
    width: min(100%, 1180px);
    padding: 18px 16px 40px;
    color: var(--mrv-text);
    font-family: var(--mrv-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--mrv-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--mrv-ivory-2), var(--mrv-ivory));
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}
.mrv *,
.mrv *::before,
.mrv *::after {
    box-sizing: border-box;
}
.mrv :where(a, button, input, select, textarea, th, td, p, span, label) {
    font-family: var(--mrv-sans);
}
.mrv [style*="font-weight:700"],
.mrv [style*="font-weight: 700"],
.mrv [style*="font-weight:600"],
.mrv [style*="font-weight: 600"] {
    font-weight: 650 !important;
}
.mrv h1 {
    margin: 0;
    color: var(--mrv-heading);
    font-family: var(--mrv-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650;
    line-height: .98;
    overflow-wrap: anywhere;
}
.mrv .sub {
    max-width: 48rem;
    margin: 9px 0 0;
    color: var(--mrv-muted);
    font-size: .94rem;
    font-weight: 500;
    line-height: 1.5;
}
.mrv-hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 12px;
    min-width: 0;
    max-width: 100%;
    padding: 2px 0 10px;
}
.mrv-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(100%, 780px);
}
.mrv-title-lockup > div:last-child {
    min-width: 0;
}
.mrv-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--mrv-gold), var(--mrv-brand) 54%, color-mix(in srgb, var(--mrv-brand) 68%, var(--mrv-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--mrv-brand) 72%, transparent);
}
.mrv-kicker {
    margin: 0 0 2px;
    color: var(--mrv-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}
.mrv-hero-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
    min-width: 0;
    max-width: 100%;
}
.mrv-shortcut {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-height: 44px;
    padding: 0 16px;
    border: 1px solid var(--mrv-gold-line);
    border-radius: 11px;
    background: var(--mrv-gold-soft);
    color: var(--mrv-gold-ink);
    font-size: .86rem;
    font-weight: 650;
    text-decoration: none;
    white-space: nowrap;
    transition: transform .16s ease, background .16s ease, border-color .16s ease;
}
.mrv-shortcut:hover {
    transform: translateY(-1px);
    background: color-mix(in srgb, var(--mrv-gold) 14%, #FFFFFF);
}
.mrv-grid {
    gap: 10px;
    margin: 4px 0 16px;
}
.mrv-stat {
    background: var(--mrv-surface);
    border-color: var(--mrv-line);
    border-radius: 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}
.mrv-stat span {
    color: var(--mrv-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
}
.mrv-stat strong {
    margin-top: 2px;
    color: var(--mrv-heading);
    font-family: var(--mrv-serif);
    font-size: 1.7rem;
    font-weight: 650;
    line-height: 1.1;
}
.mrv-card {
    background: var(--mrv-surface);
    border-color: var(--mrv-line);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
}
.mrv-card-head {
    border-bottom-color: var(--mrv-line);
    background: var(--mrv-surface-warm);
}
.mrv-card-head h2 {
    color: var(--mrv-heading);
    font-weight: 650;
}
.mrv-card-head p {
    color: var(--mrv-muted);
    line-height: 1.45;
}
.mrv-badge {
    padding: 4px 10px;
    font-weight: 650;
    border: 1px solid transparent;
}
.mrv-table-wrap {
    -webkit-overflow-scrolling: touch;
}
.mrv th {
    padding: 12px 16px;
    color: var(--mrv-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .07em;
}
.mrv td {
    padding: 13px 16px;
    border-bottom-color: color-mix(in srgb, var(--mrv-line) 70%, transparent);
}
.mrv tbody tr {
    transition: background .16s ease, box-shadow .16s ease;
}
.mrv tbody tr:hover {
    background: rgba(251,248,242,.72);
    box-shadow: 0 10px 24px -25px rgba(27,39,70,.32);
}
.mrv table a {
    color: var(--mrv-heading) !important;
    font-weight: 650 !important;
    text-decoration-color: var(--mrv-gold) !important;
    text-underline-offset: 3px;
}
.mrv-btn {
    justify-content: center;
    min-height: 44px;
    padding: 0 16px;
    border: 1px solid transparent;
    border-radius: 11px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--mrv-gold) 86%, #fff), color-mix(in srgb, var(--mrv-gold) 72%, var(--mrv-brand)));
    color: #fff;
    font-weight: 650;
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--mrv-gold) 42%, transparent);
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.mrv-btn:hover {
    opacity: 1;
    transform: translateY(-1px);
}
.mrv-btn:active {
    transform: translateY(0) scale(.98);
}
.mrv-btn:focus-visible {
    outline: 3px solid var(--mrv-ring);
    outline-offset: 2px;
}
.mrv-btn.sec {
    background: rgba(255,255,255,.86);
    border-color: var(--mrv-line);
    color: var(--mrv-text);
    box-shadow: none;
}
.mrv-empty {
    padding: 38px 16px;
    color: var(--mrv-muted);
}
.mrv-form {
    gap: 12px;
    padding: 16px;
}
.mrv-field {
    gap: 6px;
}
.mrv-field label {
    color: var(--mrv-muted);
    font-weight: 650;
}
.mrv-field input,
.mrv-field select,
.mrv-field textarea {
    min-height: 44px;
    border-color: var(--mrv-line);
    border-radius: 11px;
    background: var(--mrv-surface-warm);
    color: var(--mrv-text);
    font-size: .9rem;
    font-weight: 560;
    padding: 9px 12px;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}
.mrv-field input::placeholder,
.mrv-field textarea::placeholder {
    color: color-mix(in srgb, var(--mrv-muted) 82%, #B8C0CB);
    font-weight: 520;
}
.mrv-field input:focus,
.mrv-field select:focus,
.mrv-field textarea:focus {
    border-color: var(--mrv-gold);
    box-shadow: 0 0 0 3px var(--mrv-ring);
    outline: none;
}
.mrv-field .hint {
    color: var(--mrv-muted);
    line-height: 1.35;
}
.mrv-link {
    border-top-color: var(--mrv-line);
    background: var(--mrv-gold-soft);
    color: var(--mrv-text);
}
.mrv-link strong {
    color: var(--mrv-heading);
    font-weight: 650;
}
.mrv-link code {
    max-width: 100%;
    border-color: var(--mrv-gold-line);
    border-radius: 9px;
    background: rgba(255,255,255,.82);
    color: var(--mrv-heading);
}
.mrv-check {
    color: var(--mrv-text);
    font-weight: 650;
}
.mrv-check input[type="checkbox"] {
    accent-color: var(--mrv-gold);
}
.mrv-message {
    margin-bottom: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    font-size: .88rem;
    font-weight: 560;
    line-height: 1.4;
}
.mrv-message.is-error {
    background: var(--mrv-danger-bg);
    color: color-mix(in srgb, var(--mrv-danger) 72%, var(--mrv-text));
    border: 1px solid color-mix(in srgb, var(--mrv-danger) 24%, #fff);
}
.mrv-message.is-success {
    background: var(--mrv-success-bg);
    color: color-mix(in srgb, var(--mrv-success) 70%, var(--mrv-text));
    border: 1px solid color-mix(in srgb, var(--mrv-success) 24%, #fff);
}
.mrv-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--mrv-heading);
    font-family: var(--mrv-sans);
    font-size: .95rem;
    font-weight: 650;
    line-height: 1.2;
}
.mrv-status-dot {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    background: var(--mrv-muted);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--mrv-muted) 12%, transparent);
    flex: 0 0 auto;
}
.mrv-status-dot.is-on {
    background: var(--mrv-success);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--mrv-success) 14%, transparent);
}
.mrv-status-dot.is-warn {
    background: var(--mrv-warning);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--mrv-warning) 14%, transparent);
}

@media (min-width: 1024px) {
    .mrv-hero-section {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 16px 28px;
    }
    .mrv-hero-actions {
        justify-content: flex-end;
        justify-self: end;
        min-width: max-content;
    }
}

@media (max-width: 767px) {
    .mrv {
        --mrv-mobile-ink: color-mix(in srgb, var(--mrv-brand) 62%, #6F7784);
        padding: 10px 12px 24px;
        background:
            radial-gradient(520px 220px at 92% -6%, color-mix(in srgb, var(--mrv-gold) 12%, transparent), transparent 62%),
            linear-gradient(180deg, #FBF8F0 0%, #F3EDE2 100%);
    }
    .mrv-hero-section {
        position: relative;
        overflow: hidden;
        min-height: 126px;
        margin: 0 0 10px;
        padding: 16px 14px 14px;
        border-radius: 22px;
        color: #fff;
        background:
            radial-gradient(circle at 88% 14%, rgba(255,255,255,.17), transparent 92px),
            linear-gradient(135deg, color-mix(in srgb, var(--mrv-brand) 94%, #000) 0%, color-mix(in srgb, var(--mrv-brand-2) 78%, var(--mrv-gold)) 100%);
        box-shadow: 0 18px 34px -26px color-mix(in srgb, var(--mrv-brand) 72%, transparent);
    }
    .mrv-hero-section::after {
        content: "";
        position: absolute;
        right: -44px;
        top: -44px;
        width: 150px;
        height: 150px;
        border-radius: 999px;
        background: rgba(255,255,255,.12);
        pointer-events: none;
    }
    .mrv-title-lockup {
        position: relative;
        z-index: 1;
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px;
        align-items: start;
    }
    .mrv-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1rem;
        background: rgba(255,255,255,.16);
        color: #fff;
        border: 1px solid rgba(255,255,255,.22);
        box-shadow: none;
        backdrop-filter: blur(10px);
    }
    .mrv-kicker {
        color: rgba(255,255,255,.76);
        font-size: .64rem;
    }
    .mrv h1 {
        color: #fff;
        font-size: clamp(2rem, 12vw, 2.55rem);
    }
    .mrv .sub {
        color: rgba(255,255,255,.72);
        font-size: .84rem;
        line-height: 1.43;
    }
    .mrv-hero-actions {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: 1fr;
        width: 100%;
    }
    .mrv-shortcut {
        width: 100%;
        background: rgba(255,255,255,.94);
        border-color: rgba(255,255,255,.28);
        color: var(--mrv-brand);
        box-shadow: 0 12px 24px -18px rgba(0,0,0,.38);
    }
    .mrv-grid {
        display: flex;
        grid-template-columns: none;
        gap: 8px;
        overflow-x: auto;
        padding: 0 2px 2px;
        margin: 0 -2px 12px;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }
    .mrv-grid::-webkit-scrollbar {
        display: none;
    }
    .mrv-stat {
        flex: 0 0 168px;
        padding: 10px 12px;
    }
    .mrv-stat strong {
        color: var(--mrv-mobile-ink);
        font-size: 1.36rem;
    }
    .mrv-card {
        border-radius: 15px;
    }
    .mrv-card-head {
        align-items: flex-start;
        padding: 12px;
    }
    .mrv-form {
        grid-template-columns: 1fr;
        padding: 12px;
    }
    .mrv-actions {
        justify-content: stretch;
    }
    .mrv-actions .mrv-btn,
    .mrv-link .mrv-btn {
        width: 100%;
    }
    .mrv-link {
        align-items: stretch;
    }
    .mrv th,
    .mrv td {
        padding: 12px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .mrv *,
    .mrv *::before,
    .mrv *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
    }
}
</style>

<div class="mrv">
    <?php
    $mrvTieneCupones = function_exists('hotel_menu_module_enabled') && hotel_menu_module_enabled('promociones');
    $mrvTieneExtras = function_exists('hotel_menu_module_enabled') && hotel_menu_module_enabled('upsells');
    ?>

    <section class="mrv-hero-section">
        <div class="mrv-title-lockup">
            <div class="mrv-hero-icon"><i class="fas fa-globe"></i></div>
            <div>
                <p class="mrv-kicker">Ventas en linea</p>
                <h1>Motor de reservas online</h1>
                <p class="sub">Reservas desde tu pagina publica con anticipo pagado. El dinero de la pasarela se concilia a Caja desde aqui.</p>
            </div>
        </div>

        <?php if ($mrvTieneCupones || $mrvTieneExtras): ?>
            <div class="mrv-hero-actions">
            <?php if ($mrvTieneCupones): ?>
                <a class="mrv-shortcut" href="<?= url('motor-reservas/cupones') ?>"><i class="fas fa-ticket-alt"></i> Cupones y promociones</a>
            <?php endif; ?>
            <?php if ($mrvTieneExtras): ?>
                <a class="mrv-shortcut" href="<?= url('motor-reservas/extras') ?>"><i class="fas fa-gift"></i> Extras y upselling</a>
            <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div class="mrv-message <?= $tipo === 'error' ? 'is-error' : 'is-success' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="mrv-grid">
        <div class="mrv-stat"><span>Por conciliar a Caja</span><strong><?= (int) $resumen['por_conciliar'] ?> · <?= $mrMoney($resumen['monto_por_conciliar']) ?></strong></div>
        <div class="mrv-stat"><span>Conciliados</span><strong><?= (int) $resumen['conciliados'] ?></strong></div>
        <div class="mrv-stat"><span>Con problema</span><strong><?= (int) $resumen['con_problema'] ?></strong></div>
        <div class="mrv-stat"><span>Estado del motor</span><strong style="font-size:.95rem;">
            <?= $publicoActivo ? ($pasarelaLista ? '🟢 Recibiendo reservas' : '🟡 Activo sin pasarela') : '⚪ Pausado' ?>
        </strong></div>
    </div>

    <!-- Pagos online -->
    <div class="mrv-card">
        <div class="mrv-card-head">
            <h2>Pagos online</h2>
            <p>Los pagos "Por conciliar" ya estan cobrados en la pasarela; conciliarlos registra el anticipo real en Caja (requiere corte abierto).</p>
        </div>
        <div class="mrv-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Estado</th><th>Huesped</th><th>Reservacion</th><th>Monto</th><th>Pasarela</th><th>Fecha</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($pagos)): ?>
                    <tr><td colspan="7" class="mrv-empty">Aun no hay pagos online. Comparte tu link publico para recibir la primera reserva.</td></tr>
                <?php else: ?>
                    <?php foreach ($pagos as $pago): ?>
                        <?php [$badgeTexto, $badgeStyle] = $mrEstadoBadge((string) $pago['estado']); ?>
                        <tr>
                            <td><span class="mrv-badge" style="<?= $badgeStyle ?>"><?= $mrSafe($badgeTexto) ?></span></td>
                            <td>
                                <div style="font-weight:600;"><?= $mrSafe($pago['huesped_nombre'] ?: '—') ?></div>
                                <div style="font-size:.76rem;color:#8A93A6;"><?= $mrSafe($pago['huesped_telefono'] ?: '') ?></div>
                            </td>
                            <td>
                                <?php if (!empty($pago['reservacion_id'])): ?>
                                    <a href="<?= url('reservaciones/ver/' . (int) $pago['reservacion_id']) ?>" style="font-weight:700;color:var(--brand-primary,#1B2746);">#<?= (int) $pago['reservacion_id'] ?></a>
                                    <div style="font-size:.76rem;color:#8A93A6;"><?= $mrSafe($pago['fecha_entrada'] ?? '') ?> → <?= $mrSafe($pago['fecha_salida'] ?? '') ?></div>
                                <?php else: ?>
                                    <span style="color:#8A93A6;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:700;"><?= $mrMoney($pago['monto']) ?> <span style="font-size:.72rem;color:#8A93A6;"><?= $mrSafe($pago['moneda']) ?></span></td>
                            <td>
                                <div style="text-transform:capitalize;"><?= $mrSafe($pago['proveedor']) ?></div>
                                <div style="font-size:.72rem;color:#8A93A6;word-break:break-all;"><?= $mrSafe(mb_substr((string) $pago['proveedor_pago_id'], 0, 24)) ?></div>
                            </td>
                            <td style="font-size:.8rem;color:#6B7486;white-space:nowrap;"><?= $mrSafe(date('d/m/Y H:i', strtotime((string) $pago['created_at']))) ?></td>
                            <td style="text-align:right;">
                                <?php if (($pago['estado'] ?? '') === 'pagado' && !empty($pago['reservacion_id'])): ?>
                                    <form method="POST" action="<?= url('motor-reservas/pagos/' . (int) $pago['id'] . '/conciliar') ?>"
                                          onsubmit="return confirm('Conciliar este pago registra el anticipo de <?= $mrMoney($pago['monto']) ?> en Caja. ¿Continuar?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="mrv-btn">Conciliar a Caja</button>
                                    </form>
                                <?php elseif (($pago['estado'] ?? '') === 'conciliado' && !empty($pago['conciliado_at'])): ?>
                                    <span style="font-size:.74rem;color:#15803D;">✓ <?= $mrSafe(date('d/m/Y H:i', strtotime((string) $pago['conciliado_at']))) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Configuracion -->
    <div class="mrv-card">
        <div class="mrv-card-head">
            <h2>Configuracion del motor</h2>
            <p>Enciende tu pagina publica, define el anticipo y conecta tu pasarela de pago.</p>
        </div>
        <form method="POST" action="<?= url('motor-reservas/configuracion') ?>" class="mrv-form" autocomplete="off">
            <?= csrf_field() ?>

            <div class="mrv-field full">
                <label class="mrv-check">
                    <input type="checkbox" name="publico_activo" value="1" <?= $publicoActivo ? 'checked' : '' ?> style="min-height:auto;width:18px;height:18px;">
                    Pagina publica de reservas encendida
                </label>
            </div>

            <div class="mrv-field">
                <label for="anticipo_tipo">Tipo de anticipo</label>
                <select id="anticipo_tipo" name="anticipo_tipo">
                    <option value="porcentaje" <?= ($config['anticipo_tipo'] ?? '') === 'porcentaje' ? 'selected' : '' ?>>Porcentaje del total</option>
                    <option value="primera_noche" <?= ($config['anticipo_tipo'] ?? '') === 'primera_noche' ? 'selected' : '' ?>>Primera noche</option>
                    <option value="monto_fijo" <?= ($config['anticipo_tipo'] ?? '') === 'monto_fijo' ? 'selected' : '' ?>>Monto fijo</option>
                </select>
            </div>
            <div class="mrv-field">
                <label for="anticipo_valor">Valor del anticipo</label>
                <input type="number" step="0.01" min="0" id="anticipo_valor" name="anticipo_valor" value="<?= $mrSafe(number_format((float) ($config['anticipo_valor'] ?? 30), 2, '.', '')) ?>">
                <span class="hint">% si es porcentaje; pesos si es monto fijo.</span>
            </div>
            <div class="mrv-field">
                <label for="min_noches">Noches minimas</label>
                <input type="number" min="1" id="min_noches" name="min_noches" value="<?= (int) ($config['min_noches'] ?? 1) ?>">
            </div>
            <div class="mrv-field">
                <label for="max_noches">Noches maximas</label>
                <input type="number" min="1" id="max_noches" name="max_noches" value="<?= (int) ($config['max_noches'] ?? 30) ?>">
            </div>
            <div class="mrv-field">
                <label for="anticipacion_max_dias">Anticipacion maxima (dias)</label>
                <input type="number" min="1" id="anticipacion_max_dias" name="anticipacion_max_dias" value="<?= (int) ($config['anticipacion_max_dias'] ?? 180) ?>">
            </div>
            <div class="mrv-field full">
                <label for="politica_texto">Politica visible al huesped</label>
                <textarea id="politica_texto" name="politica_texto" rows="2" maxlength="500"><?= $mrSafe($config['politica_texto'] ?? '') ?></textarea>
            </div>

            <div class="mrv-field">
                <label for="proveedor">Pasarela de pago</label>
                <select id="proveedor" name="proveedor">
                    <option value="stripe" <?= ($credenciales['proveedor'] ?? 'stripe') === 'stripe' ? 'selected' : '' ?>>Stripe</option>
                    <option value="mercadopago" <?= ($credenciales['proveedor'] ?? '') === 'mercadopago' ? 'selected' : '' ?>>MercadoPago</option>
                </select>
            </div>
            <div class="mrv-field">
                <label for="modo">Modo</label>
                <select id="modo" name="modo">
                    <option value="test" <?= ($credenciales['modo'] ?? 'test') === 'test' ? 'selected' : '' ?>>Pruebas (test)</option>
                    <option value="live" <?= ($credenciales['modo'] ?? '') === 'live' ? 'selected' : '' ?>>Produccion (live)</option>
                </select>
            </div>
            <div class="mrv-field">
                <label for="public_key">Llave publica</label>
                <input type="text" id="public_key" name="public_key" value="<?= $mrSafe($credenciales['public_key'] ?? '') ?>" placeholder="pk_test_... / APP_USR-...">
            </div>
            <div class="mrv-field">
                <label for="secret_key">Llave secreta <?= !empty($credenciales['secret_configurado']) ? '· configurada ✓' : '' ?></label>
                <input type="password" id="secret_key" name="secret_key" placeholder="<?= !empty($credenciales['secret_configurado']) ? 'Dejar vacio para conservar la actual' : 'sk_test_... / access token' ?>" autocomplete="new-password">
                <span class="hint">Se guarda cifrada; nunca se vuelve a mostrar.</span>
            </div>
            <div class="mrv-field">
                <label for="webhook_secret">Webhook secret (Stripe) <?= !empty($credenciales['webhook_configurado']) ? '· configurado ✓' : '' ?></label>
                <input type="password" id="webhook_secret" name="webhook_secret" placeholder="whsec_..." autocomplete="new-password">
                <span class="hint">Solo Stripe. MercadoPago se verifica consultando su API.</span>
            </div>
            <div class="mrv-field">
                <label class="mrv-check" style="margin-top:20px;">
                    <input type="checkbox" name="pasarela_activa" value="1" <?= empty($credenciales) || !empty($credenciales['activo']) ? 'checked' : '' ?> style="min-height:auto;width:18px;height:18px;">
                    Pasarela activa
                </label>
            </div>

            <div class="mrv-actions">
                <button type="submit" class="mrv-btn">Guardar configuracion</button>
            </div>
        </form>

        <div class="mrv-link">
            <strong>Tu pagina publica:</strong>
            <code id="mrv-url-publica"><?= $mrSafe($urlPublica) ?></code>
            <button type="button" class="mrv-btn sec" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('mrv-url-publica').textContent).then(function(){ this.textContent='Copiado ✓'; }.bind(this));">Copiar link</button>
            <span style="width:100%;font-size:.76rem;color:#8A93A6;">Webhook para tu pasarela: <code style="font-size:.74rem;"><?= $mrSafe($urlWebhook) ?></code></span>
        </div>
    </div>
</div>
