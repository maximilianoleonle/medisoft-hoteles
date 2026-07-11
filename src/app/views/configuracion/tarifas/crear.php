<!-- Crear Incremento de Tarifa - Diseño Simplificado y Claro -->
<style>
:root {
    --primary: var(--brand-primary, #1B2746);
    --primary-dark: var(--brand-secondary, #0F172A);
    --primary-light: color-mix(in srgb, var(--brand-primary, #1B2746) 68%, #FFFFFF);
    --accent: var(--brand-accent, #BD9441);
    --brand-focus-ring: color-mix(in srgb, var(--brand-primary, #1B2746) 18%, transparent);
    --brand-hover-soft: color-mix(in srgb, var(--brand-accent, #BD9441) 12%, #FFFFFF);
    --brand-selected-soft: color-mix(in srgb, var(--brand-primary, #1B2746) 8%, #FFFFFF);
    --brand-elevated-shadow: color-mix(in srgb, var(--brand-primary, #1B2746) 22%, transparent);
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --info: #3b82f6;
}

/* Contenedor principal */
.tarifa-container {
    max-width: 1000px;
    margin: 0 auto;
}

/* Cards de sección */
.section-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.section-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.section-header {
    background: #f8fafc;
    padding: 20px 24px;
    border-bottom: 2px solid #e2e8f0;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-number {
    width: 32px;
    height: 32px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.section-body {
    padding: 24px;
}

/* Inputs mejorados */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.form-help {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 4px;
}

.form-input {
    width: 100%;
    padding: 10px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--brand-focus-ring);
}

/* Opciones de selección visual */
.option-cards {
    display: grid;
    gap: 16px;
    margin-top: 12px;
}

.option-card {
    position: relative;
    padding: 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    background: #fcfcfc;
}

.option-card:hover {
    border-color: var(--accent);
    background: var(--brand-hover-soft);
    transform: translateY(-2px);
}

.option-card.selected {
    border-color: var(--primary);
    background: var(--brand-selected-soft);
    box-shadow: 0 0 0 3px var(--brand-focus-ring);
}

.option-card input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.option-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 12px;
}

.option-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.option-desc {
    font-size: 0.875rem;
    color: #64748b;
}

/* Toggle switch mejorado */
.toggle-container {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 20px;
}

.toggle-switch {
    position: relative;
    width: 48px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: .4s;
    border-radius: 34px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: var(--success);
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

/* Preview card */
.preview-card {
    background: #f0f9ff;
    border: 2px solid #bae6fd;
    border-radius: 12px;
    padding: 20px;
    margin-top: 24px;
}

.preview-title {
    font-weight: 600;
    color: #0369a1;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Botones */
.btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px var(--brand-elevated-shadow);
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

/* Estados de validación */
.is-invalid {
    border-color: var(--danger);
}

.invalid-feedback {
    color: var(--danger);
    font-size: 0.75rem;
    margin-top: 4px;
}

/* Lista de selección mejorada */
.selection-list {
    max-height: 300px;
    overflow-y: auto;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0;
}

.selection-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: background 0.2s;
}

.selection-item:hover {
    background: #f8fafc;
}

.selection-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.selection-item label {
    flex: 1;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>

<style id="tarifa-create-boutique">
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.tarifa-create-page {
    --tar-brand: var(--brand-primary, #1B2746);
    --tar-brand-2: var(--brand-secondary, #0F172A);
    --tar-brand-dark: color-mix(in srgb, var(--tar-brand), #000 20%);
    --tar-brand-soft: color-mix(in srgb, var(--tar-brand) 5%, #FBF8F2);
    --tar-gold: var(--brand-accent, #BD9441);
    --tar-gold-soft: color-mix(in srgb, var(--tar-gold) 15%, #FFFFFF);
    --tar-gold-line: color-mix(in srgb, var(--tar-gold) 42%, #E4D4B0);
    --tar-gold-ink: color-mix(in srgb, var(--tar-gold) 72%, #000);
    --tar-ivory: #F6F2EA;
    --tar-ivory-2: #FBF8F2;
    --tar-surface: #FFFFFF;
    --tar-surface-warm: #FCFAF5;
    --tar-border: color-mix(in srgb, var(--tar-brand) 7%, #E7E1D4);
    --tar-ring: color-mix(in srgb, var(--tar-gold) 32%, transparent);
    --tar-text: #171717;
    --tar-muted: #667085;
    --tar-heading: #111827;
    --tar-success: #1E9E63;
    --tar-danger: #B95B57;
    --tar-sky: #3E7CB1;
    --tar-teal: #2F7D72;
    --tar-plum: #7C4F86;
    --tar-coral: #C66A5A;
    --tar-amber: #D0963A;
    --tar-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: var(--tar-text);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--tar-gold) 10%, transparent), transparent 60%),
        radial-gradient(900px 360px at 22% 10%, color-mix(in srgb, var(--tar-teal) 6%, transparent), transparent 58%),
        linear-gradient(180deg, var(--tar-ivory-2), var(--tar-ivory)) !important;
}

.tarifa-create-page .tarifa-container {
    max-width: 1180px;
}

.tarifa-shell {
    display: grid;
    gap: 14px;
}

.tarifa-hero {
    padding: 2px 2px 4px;
}

.tarifa-hero-icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 13px;
    background: linear-gradient(150deg, var(--tar-plum), var(--tar-brand), var(--tar-teal));
    color: #fff;
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--tar-brand) 55%, transparent);
}

.tarifa-page-kicker {
    color: color-mix(in srgb, var(--tar-coral) 70%, #000);
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.tarifa-page-title {
    color: var(--tar-brand);
    font-family: var(--tar-serif);
    font-size: clamp(1.9rem, 3.2vw, 2.55rem);
    font-weight: 600;
    line-height: 1;
    letter-spacing: 0;
}

.tarifa-page-subtitle {
    margin-top: 6px;
    max-width: 46rem;
    color: var(--tar-muted);
    font-size: .92rem;
    font-weight: 500;
    line-height: 1.45;
    text-wrap: pretty;
}

.tarifa-create-page .btn,
.tarifa-back-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-height: 38px;
    border-radius: 11px;
    border: 1px solid transparent;
    font-size: .85rem;
    font-weight: 800;
    line-height: 1;
    text-decoration: none;
    cursor: pointer;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}

.tarifa-back-btn,
.tarifa-create-page .btn-secondary {
    padding: .58rem .85rem;
    border-color: var(--tar-border);
    background: var(--tar-surface);
    color: var(--tar-muted);
    box-shadow: 0 8px 18px -18px rgba(27,39,70,.3);
}

.tarifa-back-btn:hover,
.tarifa-create-page .btn-secondary:hover {
    transform: translateY(-1px);
    border-color: var(--tar-gold-line);
    background: var(--tar-gold-soft);
    color: var(--tar-gold-ink);
}

.tarifa-summary-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.tarifa-summary-item,
.tarifa-create-page .section-card {
    background: var(--tar-surface);
    border: 1px solid var(--tar-border);
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28);
}

.tarifa-summary-item {
    --tar-summary-accent: var(--tar-teal);
    border-radius: 14px;
    padding: 11px 12px;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--tar-summary-accent) 9%, #fff), #fff 64%),
        #fff !important;
    border-color: color-mix(in srgb, var(--tar-summary-accent) 24%, var(--tar-border)) !important;
}

.tarifa-summary-item:nth-child(2) {
    --tar-summary-accent: var(--tar-sky);
}

.tarifa-summary-item:nth-child(3) {
    --tar-summary-accent: var(--tar-plum);
}

.tarifa-summary-label {
    color: color-mix(in srgb, var(--tar-summary-accent) 72%, var(--tar-muted));
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.tarifa-summary-value {
    margin-top: 2px;
    color: color-mix(in srgb, var(--tar-summary-accent) 58%, var(--tar-heading));
    font-family: var(--tar-serif);
    font-size: 1.45rem;
    font-weight: 700;
    line-height: 1.1;
}

.tarifa-create-page .section-card {
    --tar-card-accent: var(--tar-brand);
    margin-bottom: 14px;
    overflow: hidden;
    border-radius: 16px;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--tar-card-accent) 4%, #fff), #fff 48%),
        #fff;
    transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
}

.tarifa-create-page .section-card:hover {
    border-color: color-mix(in srgb, var(--tar-card-accent) 34%, var(--tar-border));
    box-shadow: 0 2px 4px rgba(27,39,70,.05), 0 16px 32px -22px color-mix(in srgb, var(--tar-card-accent) 34%, transparent);
}

.tarifa-create-page .section-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--tar-border);
    background:
        linear-gradient(90deg, color-mix(in srgb, var(--tar-card-accent) 12%, #fff), var(--tar-surface-warm) 68%) !important;
}

.tarifa-create-page .section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--tar-heading);
    font-size: .98rem;
    font-weight: 900;
    line-height: 1.2;
}

.tarifa-create-page .section-number,
.tarifa-create-page .section-icon {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 11px;
    background: linear-gradient(150deg, var(--tar-card-accent), color-mix(in srgb, var(--tar-card-accent) 72%, #111827)) !important;
    color: #fff !important;
    font-size: .84rem;
    font-weight: 900;
    box-shadow: 0 10px 20px -14px color-mix(in srgb, var(--tar-card-accent) 62%, transparent);
}

.tarifa-create-page .tarifa-step-prices {
    --tar-card-accent: var(--tar-sky);
}

.tarifa-create-page .tarifa-step-info {
    --tar-card-accent: var(--tar-plum);
}

.tarifa-create-page .tarifa-step-value {
    --tar-card-accent: var(--tar-teal);
}

.tarifa-create-page .tarifa-step-scope {
    --tar-card-accent: var(--tar-coral);
}

.tarifa-create-page .tarifa-step-period {
    --tar-card-accent: var(--tar-success);
}

.tarifa-create-page .section-body {
    padding: 18px;
}

.tarifa-create-page .form-group {
    margin-bottom: 18px;
}

.tarifa-create-page .form-label {
    display: block;
    margin-bottom: 7px;
    color: var(--tar-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .055em;
    text-transform: uppercase;
}

.tarifa-create-page .form-help {
    margin-top: 5px;
    color: var(--tar-muted);
    font-size: .75rem;
    font-weight: 650;
}

.tarifa-create-page .form-input {
    width: 100%;
    min-height: 42px;
    padding: 10px 13px;
    border: 1px solid var(--tar-border);
    border-radius: 11px;
    background: var(--tar-surface-warm);
    color: var(--tar-text);
    font-size: .9rem;
    font-weight: 650;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

.tarifa-create-page .form-input:focus {
    outline: none;
    border-color: var(--tar-gold);
    background: #fff;
    box-shadow: 0 0 0 3px var(--tar-ring);
}

.tarifa-create-page .option-cards {
    gap: 12px;
    margin-top: 12px;
}

.tarifa-create-page .option-card {
    --tar-option-accent: var(--tar-card-accent);
    position: relative;
    min-height: 126px;
    padding: 16px;
    border: 1px solid var(--tar-border);
    border-radius: 15px;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--tar-option-accent) 4%, #fff), #fff 56%),
        var(--tar-surface);
    cursor: pointer;
    overflow: hidden;
    transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

.tarifa-create-page .option-card::before {
    content: "";
    position: absolute;
    top: 12px;
    bottom: 12px;
    left: 0;
    width: 5px;
    border-radius: 0 999px 999px 0;
    background: var(--tar-option-accent);
    opacity: 0;
    transform: translateX(-3px);
    transition: opacity .16s ease, transform .16s ease;
}

.tarifa-create-page .option-card::after {
    content: "";
    position: absolute;
    inset: auto 14px 14px auto;
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--tar-option-accent) 24%, #D1D5DB);
    transition: transform .16s ease, background .16s ease;
}

.tarifa-create-page .option-card:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--tar-option-accent) 36%, var(--tar-border));
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--tar-option-accent) 8%, #fff), #fff 62%);
    box-shadow: 0 12px 28px -24px color-mix(in srgb, var(--tar-option-accent) 44%, transparent);
}

.tarifa-create-page .option-card.selected {
    border-color: color-mix(in srgb, var(--tar-option-accent) 62%, var(--tar-border));
    background:
        radial-gradient(260px 130px at 100% 0%, color-mix(in srgb, var(--tar-option-accent) 24%, transparent), transparent 72%),
        linear-gradient(180deg, color-mix(in srgb, var(--tar-option-accent) 16%, #fff), color-mix(in srgb, var(--tar-option-accent) 5%, #fff) 72%),
        var(--tar-surface);
    box-shadow:
        inset 0 0 0 2px color-mix(in srgb, var(--tar-option-accent) 26%, transparent),
        0 0 0 4px color-mix(in srgb, var(--tar-option-accent) 13%, transparent),
        0 18px 34px -26px color-mix(in srgb, var(--tar-option-accent) 68%, transparent);
}

.tarifa-create-page .option-card.selected::before {
    opacity: 1;
    transform: translateX(0);
}

.tarifa-create-page .option-card.selected::after {
    background: var(--tar-option-accent);
    width: 14px;
    height: 14px;
    box-shadow:
        0 0 0 5px color-mix(in srgb, var(--tar-option-accent) 14%, transparent),
        0 8px 16px -10px color-mix(in srgb, var(--tar-option-accent) 75%, transparent);
    transform: scale(1);
}

.tarifa-create-page .option-icon {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    margin-bottom: 12px;
    border-radius: 13px;
    background: color-mix(in srgb, var(--tar-option-accent) 12%, #fff) !important;
    color: color-mix(in srgb, var(--tar-option-accent) 78%, #111827) !important;
    font-size: 1.15rem;
    border: 1px solid color-mix(in srgb, var(--tar-option-accent) 24%, var(--tar-border));
}

.tarifa-create-page .option-card.selected .option-icon {
    background: linear-gradient(145deg, var(--tar-option-accent), color-mix(in srgb, var(--tar-option-accent) 74%, #111827)) !important;
    color: #fff !important;
    border-color: transparent;
    box-shadow: 0 12px 22px -16px color-mix(in srgb, var(--tar-option-accent) 78%, transparent);
}

.tarifa-create-page #seccionTipo .option-card:nth-child(1) {
    --tar-option-accent: var(--tar-sky);
}

.tarifa-create-page #seccionTipo .option-card:nth-child(2) {
    --tar-option-accent: var(--tar-success);
}

.tarifa-create-page #alcanceOptions .option-card:nth-child(1) {
    --tar-option-accent: var(--tar-plum);
}

.tarifa-create-page #alcanceOptions .option-card:nth-child(2) {
    --tar-option-accent: var(--tar-sky);
}

.tarifa-create-page #alcanceOptions .option-card:nth-child(3) {
    --tar-option-accent: var(--tar-amber);
}

.tarifa-create-page .option-title {
    margin-bottom: 4px;
    color: var(--tar-heading);
    font-size: .95rem;
    font-weight: 900;
}

.tarifa-create-page .option-card.selected .option-title {
    color: color-mix(in srgb, var(--tar-option-accent) 74%, #111827);
}

.tarifa-create-page .option-desc {
    max-width: 26rem;
    color: var(--tar-muted);
    font-size: .82rem;
    font-weight: 600;
    line-height: 1.35;
}

.tarifa-create-page .option-card.selected .option-desc {
    color: color-mix(in srgb, var(--tar-option-accent) 42%, var(--tar-muted));
    font-weight: 700;
}

.tarifa-create-page .toggle-container {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    margin-bottom: 18px;
    border: 1px solid var(--tar-border);
    border-radius: 14px;
    background: var(--tar-surface-warm);
}

.tarifa-create-page .toggle-switch {
    position: relative;
    width: 48px;
    height: 26px;
    flex: 0 0 auto;
}

.tarifa-create-page .toggle-slider {
    inset: 0;
    border-radius: 999px;
    background: #D7D1C6;
    transition: background .22s ease, box-shadow .22s ease;
}

.tarifa-create-page .toggle-slider:before {
    width: 18px;
    height: 18px;
    left: 4px;
    bottom: 4px;
    box-shadow: 0 3px 8px rgba(15,23,42,.18);
    transition: transform .22s ease;
}

.tarifa-create-page input:checked + .toggle-slider {
    background: var(--tar-success);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--tar-success) 16%, transparent);
}

.tarifa-create-page input:checked + .toggle-slider:before {
    transform: translateX(22px);
}

.tarifa-create-page .preview-card {
    margin-top: 22px;
    padding: 16px;
    border: 1px solid color-mix(in srgb, var(--tar-sky) 30%, var(--tar-border));
    border-radius: 15px;
    background:
        radial-gradient(220px 110px at 100% 0%, color-mix(in srgb, var(--tar-sky) 14%, transparent), transparent 72%),
        linear-gradient(180deg, color-mix(in srgb, var(--tar-sky) 7%, #fff), #fff);
}

.tarifa-create-page .preview-title {
    margin-bottom: 10px;
    color: color-mix(in srgb, var(--tar-sky) 78%, #111827);
    font-size: .88rem;
    font-weight: 900;
}

.tarifa-create-page .btn-primary {
    background: linear-gradient(135deg, var(--tar-success), color-mix(in srgb, var(--tar-teal) 78%, #111827));
    color: #fff;
    box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--tar-success) 58%, transparent);
}

.tarifa-create-page .btn-primary:hover {
    transform: translateY(-1px);
    background: linear-gradient(135deg, color-mix(in srgb, var(--tar-success) 92%, #fff), color-mix(in srgb, var(--tar-teal) 72%, #111827));
}

.tarifa-create-page .is-invalid {
    border-color: var(--tar-danger);
}

.tarifa-create-page .invalid-feedback {
    color: var(--tar-danger);
}

.tarifa-create-page .tarifa-form-alert {
    display: none;
    align-items: center;
    gap: 10px;
    margin: 18px 0 0;
    padding: 12px 14px;
    border: 1px solid color-mix(in srgb, var(--tar-danger) 30%, var(--tar-border));
    border-radius: 14px;
    background: color-mix(in srgb, var(--tar-danger) 8%, #fff);
    color: color-mix(in srgb, var(--tar-danger) 78%, #111827);
    font-size: .88rem;
    font-weight: 800;
}

.tarifa-create-page .tarifa-form-alert.is-visible {
    display: flex;
}

.tarifa-create-page .tarifa-form-alert i {
    color: var(--tar-danger);
}

.tarifa-create-page .btn-primary.is-confirming {
    background: linear-gradient(135deg, var(--tar-amber), color-mix(in srgb, var(--tar-amber) 72%, var(--tar-teal)));
}

.tarifa-create-page .selection-list {
    max-height: 330px;
    border: 1px solid var(--tar-border);
    border-radius: 14px;
    background: var(--tar-surface);
}

.tarifa-create-page .selection-item {
    padding: 12px 14px;
    border-bottom: 1px solid var(--tar-border);
    background: var(--tar-surface);
    color: var(--tar-text);
    transition: background .16s ease, border-color .16s ease;
}

.tarifa-create-page .selection-item:hover {
    background: var(--tar-ivory-2);
}

.tarifa-create-page .selection-item input[type="checkbox"] {
    accent-color: var(--tar-gold);
}

.tarifa-create-page .tipo-grupo > div:first-child {
    background: var(--tar-surface-warm) !important;
    color: var(--tar-muted);
    border-bottom: 1px solid var(--tar-border);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.tarifa-create-page table {
    border-collapse: separate;
    border-spacing: 0;
}

.tarifa-create-page thead tr {
    background: var(--tar-surface-warm) !important;
}

.tarifa-create-page th {
    color: var(--tar-muted) !important;
    font-size: .68rem;
    font-weight: 900 !important;
    letter-spacing: .055em;
    text-transform: uppercase;
}

.tarifa-create-page tbody tr {
    transition: background .16s ease, box-shadow .16s ease;
}

.tarifa-create-page tbody tr:hover {
    background: var(--tar-ivory-2) !important;
}

.tarifa-create-page .tarifa-note {
    border: 1px solid var(--tar-border);
    border-radius: 14px;
    background: var(--tar-surface-warm);
}

.tarifa-create-page .bg-amber-50,
.tarifa-create-page .bg-blue-50,
.tarifa-create-page .bg-gray-50 {
    border-color: var(--tar-border) !important;
    background: var(--tar-surface-warm) !important;
}

.tarifa-create-page .text-amber-800,
.tarifa-create-page .text-amber-700,
.tarifa-create-page .text-blue-800,
.tarifa-create-page .text-blue-700 {
    color: var(--tar-muted) !important;
}

.tarifa-create-page .bg-amber-100,
.tarifa-create-page .bg-green-100 {
    border: 1px solid var(--tar-gold-line);
    background: var(--tar-gold-soft) !important;
    color: var(--tar-gold-ink) !important;
}

.tarifa-create-page .text-primary {
    color: var(--tar-gold-ink) !important;
}

.tarifa-create-page .btn:focus-visible,
.tarifa-create-page .option-card:focus-visible,
.tarifa-create-page .tarifa-back-btn:focus-visible {
    outline: 2px solid var(--tar-gold);
    outline-offset: 3px;
}

.tarifa-create-page .btn:active,
.tarifa-create-page .tarifa-back-btn:active,
.tarifa-create-page .option-card:active {
    transform: translateY(0) scale(.985);
}

@media (max-width: 768px) {
    .tarifa-create-page {
        padding: 14px !important;
    }

    .tarifa-summary-strip {
        grid-template-columns: 1fr;
    }

    .tarifa-create-page .section-body,
    .tarifa-create-page .section-header {
        padding: 14px;
    }

    .tarifa-create-page .option-cards.grid-cols-2 {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="tarifa-create-page hotel-page min-h-screen p-4 sm:p-6">
    <div class="tarifa-container">
        <div class="tarifa-shell">
            <section class="tarifa-hero hotel-page-header">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="tarifa-hero-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="tarifa-page-kicker">Operación hotelera</p>
                            <h1 class="tarifa-page-title">Nuevo incremento</h1>
                            <p class="tarifa-page-subtitle">Configura una regla de precio para temporadas, eventos o ajustes puntuales sin perder claridad en alcance y vigencia.</p>
                        </div>
                    </div>

                    <?php $back_arrow_href = back_url('configuracion/tarifas'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                    <a href="<?= back_url('configuracion/tarifas') ?>" class="tarifa-back-btn ms-back-legacy" title="Regresar a tarifas dinámicas">
                        <i class="fas fa-arrow-left"></i>
                        Regresar
                    </a>
                </div>
            </section>

            <section class="tarifa-summary-strip" aria-label="Resumen para crear incremento">
                <div class="tarifa-summary-item">
                    <p class="tarifa-summary-label">Habitaciones</p>
                    <p class="tarifa-summary-value"><?= number_format(count($habitaciones ?? [])) ?></p>
                </div>
                <div class="tarifa-summary-item">
                    <p class="tarifa-summary-label">Tipos</p>
                    <p class="tarifa-summary-value"><?= number_format(count($tipos_habitacion ?? [])) ?></p>
                </div>
                <div class="tarifa-summary-item">
                    <p class="tarifa-summary-label">Flujo</p>
                    <p class="tarifa-summary-value">4 pasos</p>
                </div>
            </section>
        <!-- SECCIÓN: Precios Actuales con Tarifas Vigentes -->
        <div class="section-card tarifa-step-prices mb-6">
            <div class="section-header">
                <div class="flex justify-between items-center">
                    <h2 class="section-title">
                        <span class="section-icon">
                            <i class="fas fa-tags"></i>
                        </span>
                        Precios Actuales (con tarifas vigentes)
                    </h2>
                    <button type="button" onclick="togglePreciosActuales()" class="tarifa-back-btn">
                        <i class="fas fa-chevron-down" id="iconTogglePreciosActuales"></i>
                        <span id="textTogglePreciosActuales">Mostrar</span>
                    </button>
                </div>
            </div>
            <div class="section-body" id="seccionPreciosActuales" style="display: none;">
                <p class="tarifa-note text-sm text-gray-600 mb-4 p-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Estos son los precios actuales de las habitaciones considerando las tarifas vigentes. El nuevo incremento se sumará sobre estos precios.
                </p>
                
                <?php
                // Calcular precios actuales con tarifas vigentes
                $tarifaModel = new IncrementoTarifa();
                $hoy = date('Y-m-d');
                $incrementosVigentes = $tarifaModel->getVigentes();
                ?>
                
                <?php if (!empty($incrementosVigentes)): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-amber-800 font-medium mb-2">
                        <i class="fas fa-layer-group mr-1"></i>
                        Tarifas vigentes actualmente (<?= count($incrementosVigentes) ?>):
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($incrementosVigentes as $inc): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                            <?= htmlspecialchars($inc['nombre']) ?>
                            (<?= $inc['tipo_incremento'] == 'porcentaje' ? $inc['valor_incremento'] . '%' : '$' . number_format($inc['valor_incremento'], 0) ?>)
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        No hay tarifas vigentes. Los precios mostrados son los precios base.
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Habitación</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Tipo</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Precio Base</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Incremento</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Precio Actual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($habitaciones as $hab): 
                                $calculo = $tarifaModel->calcularPrecioConIncremento(
                                    $hab['id'],
                                    $hab['tipo'],
                                    $hab['precio_base'],
                                    $hoy
                                );
                                $tieneIncremento = $calculo['incremento_total'] > 0;
                            ?>
                            <tr class="hover:bg-gray-50 <?= $tieneIncremento ? 'bg-green-50' : '' ?>">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900"><?= $hab['numero'] ?></span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <?= get_tipo_habitacion($hab['tipo']) ?>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">
                                    <?= format_currency($calculo['precio_base']) ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <?php if ($tieneIncremento): ?>
                                    <span class="text-green-600 font-medium">
                                        +<?= format_currency($calculo['incremento_total']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-bold <?= $tieneIncremento ? 'text-green-700' : 'text-gray-900' ?>">
                                        <?= format_currency($calculo['precio_final']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($incrementosVigentes)): ?>
                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <p class="text-xs text-blue-700">
                        <i class="fas fa-lightbulb mr-1"></i>
                        <strong>Tip:</strong> El nuevo incremento que crees se sumará a estos precios actuales. 
                        Por ejemplo, si agregas +10% a una habitación que ya tiene un precio de $1,100, el nuevo precio será $1,210.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <form action="<?= url('configuracion/tarifas/crear') ?>" method="POST" id="formIncremento">
            <?= csrf_field() ?>
            
            <!-- PASO 1: Información básica -->
            <div class="section-card tarifa-step-info">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">1</span>
                        Información del Incremento
                    </h2>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">
                                Nombre del incremento <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="nombre" 
                                   class="form-input" 
                                   placeholder="Ej: Temporada Alta Navidad"
                                   value="<?= old('nombre') ?>"
                                   required>
                            <p class="form-help">Un nombre corto y descriptivo</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Prioridad</label>
                            <input type="number" 
                                   name="prioridad" 
                                   class="form-input" 
                                   value="<?= old('prioridad', 0) ?>"
                                   min="0"
                                   max="99">
                            <p class="form-help">Mayor número = mayor prioridad</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción (opcional)</label>
                        <textarea name="descripcion" 
                                  class="form-input" 
                                  rows="2"
                                  placeholder="Explique brevemente el motivo del incremento"><?= old('descripcion') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Tipo y valor -->
            <div class="section-card tarifa-step-value" id="seccionTipo">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">2</span>
                        Tipo y Valor del Incremento
                    </h2>
                </div>
                <div class="section-body">
                    <div class="option-cards grid-cols-2" id="claseCards" style="margin-bottom:1.25rem;">
                        <div class="option-card <?= old('clase') === 'descuento' ? '' : 'selected' ?>" onclick="selectClase('incremento')">
                            <input type="radio" name="clase" value="incremento" <?= old('clase') === 'descuento' ? '' : 'checked' ?>>
                            <div class="option-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="option-title">Incremento</div>
                            <div class="option-desc">Aumenta el precio de la habitacion</div>
                        </div>

                        <div class="option-card <?= old('clase') === 'descuento' ? 'selected' : '' ?>" onclick="selectClase('descuento')">
                            <input type="radio" name="clase" value="descuento" <?= old('clase') === 'descuento' ? 'checked' : '' ?>>
                            <div class="option-icon bg-rose-100 text-rose-600">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="option-title">Descuento</div>
                            <div class="option-desc">Resta del precio de la habitacion</div>
                        </div>
                    </div>

                    <div class="option-cards grid-cols-2" id="tipoCards">
                        <div class="option-card selected" onclick="selectTipo('porcentaje')">
                            <input type="radio" name="tipo_incremento" value="porcentaje" checked>
                            <div class="option-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="option-title">Porcentaje</div>
                            <div class="option-desc">Aumenta el precio en un porcentaje</div>
                        </div>

                        <div class="option-card" onclick="selectTipo('monto_fijo')">
                            <input type="radio" name="tipo_incremento" value="monto_fijo">
                            <div class="option-icon bg-green-100 text-green-600">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="option-title">Monto Fijo</div>
                            <div class="option-desc">Suma una cantidad fija al precio</div>
                        </div>
                    </div>

                    <div class="form-group mt-6">
                        <label class="form-label">
                            <span id="labelValor">Porcentaje de incremento</span> <span class="text-red-500">*</span>
                        </label>
                        <div class="flex">
                            <input type="number" 
                                   name="valor_incremento" 
                                   id="valor_incremento"
                                   class="form-input rounded-r-none flex-1" 
                                   step="0.01" 
                                   min="0.01"
                                   placeholder="0.00"
                                   value="<?= old('valor_incremento') ?>"
                                   required>
                            <span class="px-4 py-2 bg-gray-100 border-2 border-l-0 border-gray-300 rounded-r-lg font-semibold" id="simboloValor">%</span>
                        </div>
                        <p class="form-help" id="ayudaValor">Ejemplo: 15 para un incremento del 15%</p>
                    </div>

                    <!-- Preview del cálculo -->
                    <div class="preview-card" id="previewCalculo">
                        <div class="preview-title">
                            <i class="fas fa-calculator"></i>
                            Ejemplo de cálculo
                        </div>
                        <div class="text-sm text-gray-700" id="ejemploCalculo">
                            Si una habitación cuesta $1,000, con un incremento del <strong>15%</strong> 
                            el precio final será <strong>$1,150</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 3: Aplicación -->
            <div class="section-card tarifa-step-scope" id="seccionAlcance">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">3</span>
                        ¿Dónde se aplicará el incremento?
                    </h2>
                </div>
                <div class="section-body">
                    <div class="option-cards" id="alcanceOptions">
                        <div class="option-card selected" onclick="selectAlcance('global')">
                            <input type="radio" name="alcance" value="global" checked>
                            <div class="option-icon bg-purple-100 text-purple-600">
                                <i class="fas fa-hotel"></i>
                            </div>
                            <div class="option-title">Todas las habitaciones</div>
                            <div class="option-desc">Se aplicará a las 66 habitaciones del hotel</div>
                        </div>

                        <div class="option-card" onclick="selectAlcance('tipo_habitacion')">
                            <input type="radio" name="alcance" value="tipo_habitacion">
                            <div class="option-icon bg-indigo-100 text-indigo-600">
                                <i class="fas fa-bed"></i>
                            </div>
                            <div class="option-title">Por tipo de habitación</div>
                            <div class="option-desc">Solo a ciertos tipos (sencilla, doble, etc.)</div>
                        </div>

                        <div class="option-card" onclick="selectAlcance('habitacion')">
                            <input type="radio" name="alcance" value="habitacion">
                            <div class="option-icon bg-amber-100 text-amber-600">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="option-title">Habitaciones específicas</div>
                            <div class="option-desc">Seleccionar habitaciones individuales</div>
                        </div>
                    </div>

                    <!-- Selector de tipos -->
                    <div id="selectorTipos" style="display: none;" class="mt-6">
                        <p class="form-label mb-3">Seleccione los tipos de habitación:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach ($tipos_habitacion as $tipo): ?>
                            <label class="selection-item bg-gray-50 rounded-lg cursor-pointer">
                                <input type="checkbox" 
                                       name="tipos_habitacion[]" 
                                       value="<?= $tipo['tipo'] ?>">
                                <span class="font-medium"><?= get_tipo_habitacion($tipo['tipo']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Selector de habitaciones -->
                    <div id="selectorHabitaciones" style="display: none;" class="mt-6">
                        <div class="flex justify-between items-center mb-3">
                            <p class="form-label">Seleccione las habitaciones:</p>
                            <button type="button" class="text-sm text-primary hover:underline" onclick="mostrarFiltroTipos()">
                                <i class="fas fa-filter"></i> Filtrar por tipo
                            </button>
                        </div>
                        
                        <?php if (!empty($incrementosVigentes)): ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-2 mb-3">
                            <p class="text-xs text-amber-700">
                                <i class="fas fa-info-circle mr-1"></i>
                                Los precios mostrados incluyen las tarifas vigentes. El nuevo incremento se sumará a estos precios.
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="selection-list">
                            <?php 
                            $habitaciones_por_tipo = [];
                            foreach ($habitaciones as $hab) {
                                $habitaciones_por_tipo[$hab['tipo']][] = $hab;
                            }
                            ?>
                            <?php foreach ($habitaciones_por_tipo as $tipo => $habs): ?>
                                <div class="tipo-grupo" data-tipo="<?= $tipo ?>">
                                    <div class="px-4 py-2 bg-gray-100 font-semibold text-sm">
                                        <?= get_tipo_habitacion($tipo) ?>
                                    </div>
                                    <?php foreach ($habs as $hab): 
                                        // Calcular precio actual con tarifas vigentes
                                        $calculoHab = $tarifaModel->calcularPrecioConIncremento(
                                            $hab['id'],
                                            $hab['tipo'],
                                            $hab['precio_base'],
                                            $hoy
                                        );
                                        $tieneIncrementoHab = $calculoHab['incremento_total'] > 0;
                                        $tarifasAplicadas = $calculoHab['incrementos_aplicados'] ?? [];
                                    ?>
                                    <label class="selection-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                                        <div class="flex items-center gap-3 w-full">
                                            <input type="checkbox" 
                                                   name="habitaciones[]" 
                                                   value="<?= $hab['id'] ?>"
                                                   data-tipo="<?= $hab['tipo'] ?>"
                                                   onchange="actualizarContadorHabitaciones()">
                                            <span class="flex-1 flex justify-between items-center">
                                                <strong>Habitación <?= $hab['numero'] ?></strong>
                                                <span>
                                                    <?php if ($tieneIncrementoHab): ?>
                                                    <span class="text-gray-400 text-sm line-through"><?= format_currency($hab['precio_base']) ?></span>
                                                    <span class="text-green-600 font-semibold"><?= format_currency($calculoHab['precio_final']) ?></span>
                                                    <?php else: ?>
                                                    <span class="text-gray-600"><?= format_currency($hab['precio_base']) ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </span>
                                        </div>
                                        <?php if (!empty($tarifasAplicadas)): ?>
                                        <div class="flex flex-wrap gap-1 ml-7">
                                            <?php foreach ($tarifasAplicadas as $tarifa): ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-700">
                                                <?= htmlspecialchars($tarifa['nombre']) ?>
                                                <span class="ml-1 opacity-75">
                                                    (+<?= $tarifa['tipo'] == 'porcentaje' ? $tarifa['valor'] . '%' : format_currency($tarifa['aumento']) ?>)
                                                </span>
                                            </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">
                            <span id="contadorHabitaciones">0</span> habitaciones seleccionadas
                        </p>
                    </div>
                </div>
            </div>

            <!-- PASO 4: Vigencia -->
            <div class="section-card tarifa-step-period">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">4</span>
                        Período de Vigencia
                    </h2>
                </div>
                <div class="section-body">
                    <div class="toggle-container">
                        <label class="toggle-switch">
                            <input type="checkbox" 
                                   name="es_permanente" 
                                   id="es_permanente"
                                   value="1"
                                   onchange="togglePermanente()">
                            <span class="toggle-slider"></span>
                        </label>
                        <div>
                            <div class="font-semibold">Incremento permanente</div>
                            <div class="text-sm text-gray-600">Sin fecha de finalización</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">
                                Fecha de inicio <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   name="fecha_inicio" 
                                   class="form-input"
                                   value="<?= old('fecha_inicio', date('Y-m-d')) ?>"
                                   min="<?= date('Y-m-d') ?>"
                                   required>
                        </div>

                        <div class="form-group" id="grupoFechaFin">
                            <label class="form-label">
                                Fecha de fin <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   name="fecha_fin" 
                                   id="fecha_fin"
                                   class="form-input"
                                   value="<?= old('fecha_fin') ?>"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            El incremento se aplicará automáticamente a las reservaciones realizadas durante este período.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div id="tarifaFormAlert" class="tarifa-form-alert" role="alert" aria-live="assertive" hidden>
                <i class="fas fa-circle-exclamation"></i>
                <span data-tarifa-alert-text></span>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="<?= back_url('configuracion/tarifas') ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Guardar Incremento
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

<!-- JavaScript corregido -->
<script>
function tarifaBrandPrimary() {
    return getComputedStyle(document.documentElement).getPropertyValue('--brand-primary').trim() || '#1B2746';
}

// Toggle sección de precios actuales
function togglePreciosActuales() {
    const seccion = document.getElementById('seccionPreciosActuales');
    const icon = document.getElementById('iconTogglePreciosActuales');
    const text = document.getElementById('textTogglePreciosActuales');
    
    if (seccion.style.display === 'none') {
        seccion.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
        text.textContent = 'Ocultar';
    } else {
        seccion.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
        text.textContent = 'Mostrar';
    }
}

// Función para seleccionar tipo
function selectTipo(tipo) {
    // Buscar específicamente en las tarjetas de tipo (no en las de clase)
    document.querySelectorAll('#tipoCards .option-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    document.querySelector(`input[value="${tipo}"]`).checked = true;

    // Actualizar labels y ejemplo
    const label = document.getElementById('labelValor');
    const simbolo = document.getElementById('simboloValor');
    const ayuda = document.getElementById('ayudaValor');
    const valor = document.getElementById('valor_incremento').value || 15;

    if (tipo === 'porcentaje') {
        label.textContent = 'Porcentaje de incremento';
        simbolo.textContent = '%';
        ayuda.textContent = 'Ejemplo: 15 para un incremento del 15%';
        actualizarEjemplo(valor, 'porcentaje');
    } else {
        label.textContent = 'Monto a incrementar';
        simbolo.textContent = '$';
        ayuda.textContent = 'Ejemplo: 100 para incrementar $100';
        actualizarEjemplo(valor, 'monto_fijo');
    }
}

function selectClase(clase) {
    document.querySelectorAll('#claseCards .option-card').forEach(function (card) {
        const radio = card.querySelector('input[name="clase"]');
        const isSel = radio && radio.value === clase;
        card.classList.toggle('selected', isSel);
        if (isSel) { radio.checked = true; }
    });
}

// Función para seleccionar alcance - CORREGIDA
function selectAlcance(alcance) {
    // Buscar específicamente en la sección de alcance usando el ID
    document.querySelectorAll('#alcanceOptions .option-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Agregar la clase selected al elemento clickeado
    event.currentTarget.classList.add('selected');
    
    // Marcar el radio button correspondiente
    document.querySelector(`input[name="alcance"][value="${alcance}"]`).checked = true;

    // Mostrar/ocultar selectores
    document.getElementById('selectorTipos').style.display = 'none';
    document.getElementById('selectorHabitaciones').style.display = 'none';

    if (alcance === 'tipo_habitacion') {
        document.getElementById('selectorTipos').style.display = 'block';
    } else if (alcance === 'habitacion') {
        document.getElementById('selectorHabitaciones').style.display = 'block';
        actualizarContadorHabitaciones();
    }
}

// Toggle permanente
function togglePermanente() {
    const isPermanente = document.getElementById('es_permanente').checked;
    const fechaFinGroup = document.getElementById('grupoFechaFin');
    const fechaFinInput = document.getElementById('fecha_fin');

    if (isPermanente) {
        fechaFinGroup.style.opacity = '0.5';
        fechaFinInput.removeAttribute('required');
    } else {
        fechaFinGroup.style.opacity = '1';
        fechaFinInput.setAttribute('required', 'required');
    }
}

// Actualizar ejemplo
function actualizarEjemplo(valor, tipo) {
    const ejemplo = document.getElementById('ejemploCalculo');
    const precioBase = 1000;
    let precioFinal;

    if (tipo === 'porcentaje') {
        precioFinal = precioBase + (precioBase * valor / 100);
        ejemplo.innerHTML = `Si una habitación cuesta $${precioBase.toLocaleString()}, con un incremento del <strong>${valor}%</strong> 
                           el precio final será <strong>$${precioFinal.toLocaleString()}</strong>`;
    } else {
        precioFinal = precioBase + parseFloat(valor);
        ejemplo.innerHTML = `Si una habitación cuesta $${precioBase.toLocaleString()}, sumando <strong>$${valor}</strong> 
                           el precio final será <strong>$${precioFinal.toLocaleString()}</strong>`;
    }
}

// Actualizar contador de habitaciones
function actualizarContadorHabitaciones() {
    const total = document.querySelectorAll('input[name="habitaciones[]"]:checked').length;
    document.getElementById('contadorHabitaciones').textContent = total;
}

// Mostrar filtro de tipos
function mostrarFiltroTipos() {
    Swal.fire({
        title: 'Filtrar por tipo',
        html: `
            <div class="text-left">
                <?php foreach ($tipos_habitacion as $tipo): ?>
                <label class="block p-2 hover:bg-gray-100 rounded cursor-pointer">
                    <input type="checkbox" class="tipo-filtro mr-2" value="<?= $tipo['tipo'] ?>">
                    <?= get_tipo_habitacion($tipo['tipo']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Aplicar filtro',
        confirmButtonColor: tarifaBrandPrimary()
    }).then((result) => {
        if (result.isConfirmed) {
            const tiposSeleccionados = Array.from(document.querySelectorAll('.tipo-filtro:checked')).map(cb => cb.value);
            
            document.querySelectorAll('.tipo-grupo').forEach(grupo => {
                grupo.style.display = tiposSeleccionados.length === 0 || tiposSeleccionados.includes(grupo.dataset.tipo) ? 'block' : 'none';
            });
        }
    });
}

// Event listeners
document.getElementById('valor_incremento').addEventListener('input', function() {
    const tipo = document.querySelector('input[name="tipo_incremento"]:checked').value;
    actualizarEjemplo(this.value || 0, tipo);
});

const formIncremento = document.getElementById('formIncremento');
const tarifaFormAlert = document.getElementById('tarifaFormAlert');

function mostrarErrorFormularioTarifa(message, targetSelector) {
    if (!tarifaFormAlert) {
        return;
    }

    const text = tarifaFormAlert.querySelector('[data-tarifa-alert-text]');
    if (text) {
        text.textContent = message;
    }

    tarifaFormAlert.hidden = false;
    tarifaFormAlert.classList.add('is-visible');

    const target = targetSelector ? document.querySelector(targetSelector) : tarifaFormAlert;
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function limpiarErrorFormularioTarifa() {
    if (!tarifaFormAlert) {
        return;
    }

    tarifaFormAlert.classList.remove('is-visible');
    tarifaFormAlert.hidden = true;
}

document.querySelectorAll('input[name="tipos_habitacion[]"], input[name="habitaciones[]"], input[name="alcance"]').forEach(input => {
    input.addEventListener('change', limpiarErrorFormularioTarifa);
});

// Validación del formulario
if (formIncremento) {
    formIncremento.addEventListener('submit', function(e) {
        const alcance = document.querySelector('input[name="alcance"]:checked').value;
        limpiarErrorFormularioTarifa();

        if (alcance === 'tipo_habitacion') {
            const tipos = document.querySelectorAll('input[name="tipos_habitacion[]"]:checked');
            if (tipos.length === 0) {
                e.preventDefault();
                mostrarErrorFormularioTarifa('Selecciona al menos un tipo de habitacion para continuar.', '#selectorTipos');
                return;
            }
        }

        if (alcance === 'habitacion') {
            const habitaciones = document.querySelectorAll('input[name="habitaciones[]"]:checked');
            if (habitaciones.length === 0) {
                e.preventDefault();
                mostrarErrorFormularioTarifa('Selecciona al menos una habitacion para continuar.', '#selectorHabitaciones');
                return;
            }
        }
    });
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    togglePermanente();
    
    // Asegurar que el primer tipo esté seleccionado visualmente
    const firstTipoCard = document.querySelector('#seccionTipo .option-card');
    if (firstTipoCard) {
        firstTipoCard.classList.add('selected');
    }
    
    // Asegurar que el primer alcance esté seleccionado visualmente
    const firstAlcanceCard = document.querySelector('#alcanceOptions .option-card');
    if (firstAlcanceCard) {
        firstAlcanceCard.classList.add('selected');
    }
});
</script>

<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
