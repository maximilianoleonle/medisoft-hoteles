<!-- Editar ajuste de precio - Diseño Simplificado y Claro -->
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

.btn-primary.is-confirming {
    background: color-mix(in srgb, var(--accent) 48%, var(--primary-dark));
}

.tarifa-form-alert {
    display: none;
    align-items: center;
    gap: 10px;
    margin: 18px 0 0;
    padding: 12px 14px;
    border: 1px solid color-mix(in srgb, var(--danger) 30%, #e2e8f0);
    border-radius: 14px;
    background: color-mix(in srgb, var(--danger) 8%, #fff);
    color: color-mix(in srgb, var(--danger) 78%, #111827);
    font-size: .88rem;
    font-weight: 800;
}

.tarifa-form-alert.is-visible {
    display: flex;
}

.tarifa-form-alert.is-confirmation {
    border-color: color-mix(in srgb, var(--accent) 38%, #e2e8f0);
    background: color-mix(in srgb, var(--accent) 12%, #fff);
    color: color-mix(in srgb, var(--accent) 70%, #111827);
}

.tarifa-form-alert i {
    color: var(--danger);
}

.tarifa-form-alert.is-confirmation i {
    color: color-mix(in srgb, var(--accent) 76%, #111827);
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

/* Info card */
.info-card {
    background: #fffbeb;
    border: 1px solid #fbbf24;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
    display: flex;
    align-items: start;
    gap: 12px;
}

.info-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: #fbbf24;
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
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
</style>

<style id="tarifa-edit-serene">
.tarifa-edit-page {
    --tar-ink: #172033;
    --tar-muted: #66758a;
    --tar-line: color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #e7e2d8);
    --tar-soft: color-mix(in srgb, var(--brand-primary, #1B2746) 4%, #fbfaf7);
    --tar-accent-soft: color-mix(in srgb, var(--brand-accent, #BD9441) 10%, #fffdf8);
    min-height: 100vh;
    padding: clamp(18px, 2.4vw, 34px);
    color: var(--tar-ink);
    background:
        radial-gradient(circle at 7% 2%, color-mix(in srgb, var(--brand-accent, #BD9441) 9%, transparent), transparent 27rem),
        linear-gradient(180deg, #fbfaf7 0, #f7f8fa 100%);
}

.tarifa-edit-page * { box-sizing: border-box; }
.tarifa-edit-page .tarifa-edit-shell { max-width: 1420px; margin: 0 auto; }
.tarifa-edit-page .tarifa-container { max-width: none; margin: 0; padding: 0 !important; }

.tarifa-edit-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 18px;
}

.tarifa-edit-heading { display: flex; align-items: center; gap: 16px; min-width: 0; }
.tarifa-edit-heading-icon {
    width: 54px;
    height: 54px;
    flex: 0 0 54px;
    display: grid;
    place-items: center;
    border-radius: 17px;
    color: #fff;
    font-size: 1.2rem;
    background: linear-gradient(145deg, var(--brand-primary, #1B2746), color-mix(in srgb, var(--brand-primary, #1B2746) 62%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 30px color-mix(in srgb, var(--brand-primary, #1B2746) 22%, transparent);
}

.tarifa-edit-eyebrow {
    margin: 0 0 3px;
    color: color-mix(in srgb, var(--brand-primary, #1B2746) 68%, #708096);
    font-size: .72rem;
    font-weight: 850;
    letter-spacing: .13em;
    text-transform: uppercase;
}

.tarifa-edit-header h1 { margin: 0; color: var(--tar-ink); font-size: clamp(1.7rem, 3vw, 2.65rem); line-height: 1.05; letter-spacing: -.035em; }
.tarifa-edit-header p:not(.tarifa-edit-eyebrow) { margin: 7px 0 0; color: var(--tar-muted); font-size: .94rem; }
.tarifa-edit-header .btn { min-height: 44px; border: 1px solid var(--tar-line); background: rgba(255,255,255,.82); color: var(--tar-ink); box-shadow: none; }
.tarifa-edit-header .btn:hover { border-color: color-mix(in srgb, var(--brand-primary, #1B2746) 35%, #ddd7cc); background: #fff; transform: translateY(-1px); }

.tarifa-edit-context {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 18px;
    padding: 14px 16px;
    border: 1px solid color-mix(in srgb, var(--brand-accent, #BD9441) 23%, #e7e2d8);
    border-radius: 17px;
    background: color-mix(in srgb, var(--brand-accent, #BD9441) 5%, rgba(255,255,255,.92));
    box-shadow: 0 9px 24px rgba(31,41,55,.045);
}

.tarifa-edit-context-main { display: flex; align-items: center; gap: 12px; min-width: 0; }
.tarifa-edit-context-icon { width: 38px; height: 38px; flex: 0 0 38px; display: grid; place-items: center; border-radius: 12px; color: color-mix(in srgb, var(--brand-accent, #BD9441) 82%, #563d14); background: var(--tar-accent-soft); }
.tarifa-edit-context strong { display: block; color: var(--tar-ink); font-size: .92rem; }
.tarifa-edit-context span { display: block; margin-top: 2px; color: var(--tar-muted); font-size: .78rem; }
.tarifa-current-pill { flex: 0 0 auto; padding: 7px 11px; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 16%, #e3ddd2); border-radius: 999px; color: var(--brand-primary, #1B2746); background: #fff; font-size: .72rem; font-weight: 850; }

.tarifa-edit-progress {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    margin: 0 0 18px;
    padding: 8px;
    border: 1px solid var(--tar-line);
    border-radius: 18px;
    background: rgba(255,255,255,.76);
}

.tarifa-edit-progress a { min-height: 48px; display: flex; align-items: center; gap: 9px; padding: 9px 12px; border-radius: 12px; color: var(--tar-muted); text-decoration: none; font-size: .78rem; font-weight: 750; }
.tarifa-edit-progress a:hover { color: var(--tar-ink); background: var(--tar-soft); }
.tarifa-edit-progress span { width: 27px; height: 27px; flex: 0 0 27px; display: grid; place-items: center; border: 1px solid var(--tar-line); border-radius: 9px; color: var(--brand-primary, #1B2746); background: #fff; font-size: .72rem; font-weight: 900; }

.tarifa-edit-grid { display: grid; grid-template-columns: minmax(0, 1fr) 290px; align-items: start; gap: 18px; }
.tarifa-edit-main { min-width: 0; }

.tarifa-edit-page .section-card {
    margin-bottom: 16px;
    overflow: hidden;
    border: 1px solid var(--tar-line);
    border-radius: 20px;
    background: rgba(255,255,255,.94);
    box-shadow: 0 12px 34px rgba(31,41,55,.055);
    transition: border-color .2s ease, box-shadow .2s ease;
}
.tarifa-edit-page .section-card:hover { border-color: color-mix(in srgb, var(--brand-primary, #1B2746) 21%, #e5e0d7); box-shadow: 0 16px 38px rgba(31,41,55,.075); }
.tarifa-edit-page .section-header { padding: 16px 20px; border-bottom: 1px solid var(--tar-line); background: linear-gradient(90deg, var(--tar-soft), #fff); }
.tarifa-edit-page .section-title { margin: 0; color: var(--tar-ink); font-size: 1rem; font-weight: 850; letter-spacing: -.01em; }
.tarifa-edit-page .section-number { width: 30px; height: 30px; background: var(--brand-primary, #1B2746); box-shadow: 0 6px 16px color-mix(in srgb, var(--brand-primary, #1B2746) 16%, transparent); }
.tarifa-edit-page .section-body { padding: 20px; }

.tarifa-edit-page .form-group { margin-bottom: 18px; }
.tarifa-edit-page .form-label { margin-bottom: 7px; color: #334155; font-size: .79rem; font-weight: 820; }
.tarifa-edit-page .form-help { margin-top: 6px; color: var(--tar-muted); font-size: .74rem; }
.tarifa-edit-page .form-input { min-height: 48px; padding: 11px 13px; border: 1px solid #d9d5cd; border-radius: 12px; color: var(--tar-ink); background: #fff; font-size: .9rem; }
.tarifa-edit-page textarea.form-input { min-height: 84px; resize: vertical; }
.tarifa-edit-page .form-input:hover { border-color: #bdb8ae; }
.tarifa-edit-page .form-input:focus { border-color: var(--brand-primary, #1B2746); box-shadow: 0 0 0 4px var(--brand-focus-ring); }

.tarifa-edit-page .option-cards { gap: 10px; margin-top: 10px; }
.tarifa-edit-page .option-card { min-height: 110px; padding: 15px 15px 14px; border: 1px solid #ddd8cf; border-radius: 16px; background: #fff; box-shadow: none; }
.tarifa-edit-page .option-card:hover { border-color: color-mix(in srgb, var(--brand-primary, #1B2746) 38%, #ddd8cf); background: var(--tar-soft); transform: translateY(-1px); }
.tarifa-edit-page .option-card.selected { border-color: color-mix(in srgb, var(--brand-primary, #1B2746) 66%, #fff); background: color-mix(in srgb, var(--brand-primary, #1B2746) 6%, #fff); box-shadow: inset 4px 0 0 var(--brand-primary, #1B2746), 0 8px 20px rgba(31,41,55,.06); }
.tarifa-edit-page .option-card input[type="radio"] { width: 1px; height: 1px; }
.tarifa-edit-page .option-icon { width: 38px; height: 38px; margin-bottom: 10px; border-radius: 11px; font-size: 1rem; }
.tarifa-edit-page .option-title { color: var(--tar-ink); font-size: .9rem; font-weight: 850; }
.tarifa-edit-page .option-desc { color: var(--tar-muted); font-size: .75rem; line-height: 1.4; }
.tarifa-edit-page .option-card:focus-within { outline: 3px solid var(--brand-focus-ring); outline-offset: 2px; }

.tarifa-edit-page .preview-card { margin-top: 18px; padding: 15px 16px; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 19%, #dce4eb); border-radius: 14px; background: color-mix(in srgb, var(--brand-primary, #1B2746) 4%, #f8fbfd); }
.tarifa-edit-page .preview-title { margin-bottom: 7px; color: var(--brand-primary, #1B2746); font-size: .82rem; }
.tarifa-edit-page .toggle-container { margin-bottom: 18px; padding: 14px 15px; border: 1px solid var(--tar-line); border-radius: 14px; background: var(--tar-soft); }
.tarifa-edit-page .selection-list { max-height: 330px; border: 1px solid var(--tar-line); border-radius: 14px; }
.tarifa-edit-page .selection-item { min-height: 48px; border-bottom-color: #eeeae3; }

.tarifa-edit-summary { position: sticky; top: 18px; overflow: hidden; border: 1px solid var(--tar-line); border-radius: 20px; background: rgba(255,255,255,.96); box-shadow: 0 14px 34px rgba(31,41,55,.07); }
.tarifa-edit-summary-header { padding: 17px; border-bottom: 1px solid var(--tar-line); background: linear-gradient(145deg, color-mix(in srgb, var(--brand-primary, #1B2746) 7%, #fff), #fff); }
.tarifa-edit-summary-header i { width: 34px; height: 34px; display: grid; place-items: center; margin-bottom: 10px; border-radius: 11px; color: #fff; background: var(--brand-primary, #1B2746); }
.tarifa-edit-summary h2 { margin: 0; color: var(--tar-ink); font-size: 1rem; }
.tarifa-edit-summary p { margin: 5px 0 0; color: var(--tar-muted); font-size: .75rem; line-height: 1.45; }
.tarifa-edit-summary-list { margin: 0; padding: 4px 17px; }
.tarifa-edit-summary-row { padding: 12px 0; border-bottom: 1px solid #eeeae3; }
.tarifa-edit-summary-row:last-child { border-bottom: 0; }
.tarifa-edit-summary-row dt { margin-bottom: 3px; color: #8a96a6; font-size: .65rem; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
.tarifa-edit-summary-row dd { margin: 0; color: var(--tar-ink); font-size: .82rem; font-weight: 760; line-height: 1.4; }
.tarifa-edit-summary-note { margin: 0 !important; padding: 13px 17px; border-top: 1px solid var(--tar-line); color: color-mix(in srgb, var(--brand-primary, #1B2746) 76%, #66758a) !important; background: var(--tar-soft); }

.tarifa-edit-page .tarifa-form-alert { grid-column: 1 / -1; margin: 0; }
.tarifa-edit-actions { grid-column: 1 / -1; position: sticky; bottom: 10px; z-index: 8; display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 13px 14px; border: 1px solid var(--tar-line); border-radius: 17px; background: rgba(255,255,255,.93); box-shadow: 0 16px 40px rgba(31,41,55,.12); backdrop-filter: blur(14px); }
.tarifa-edit-action-copy strong { display: block; color: var(--tar-ink); font-size: .82rem; }
.tarifa-edit-action-copy span { display: block; margin-top: 2px; color: var(--tar-muted); font-size: .72rem; }
.tarifa-edit-action-buttons { display: flex; gap: 9px; }
.tarifa-edit-page .btn { min-height: 44px; padding: 10px 16px; border-radius: 12px; }
.tarifa-edit-page .btn-primary { background: var(--brand-primary, #1B2746); }
.tarifa-edit-page .btn-secondary { border: 1px solid var(--tar-line); background: #fff; }

@media (max-width: 1040px) {
    .tarifa-edit-grid { grid-template-columns: minmax(0, 1fr) 250px; }
}

@media (max-width: 860px) {
    .tarifa-edit-grid { grid-template-columns: 1fr; }
    .tarifa-edit-summary { position: static; }
    .tarifa-edit-progress { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 640px) {
    .tarifa-edit-page { padding: 14px 12px 96px; }
    .tarifa-edit-header { align-items: flex-start; }
    .tarifa-edit-heading-icon { width: 46px; height: 46px; flex-basis: 46px; border-radius: 14px; }
    .tarifa-edit-header p:not(.tarifa-edit-eyebrow) { font-size: .83rem; }
    .tarifa-edit-header .btn { width: 44px; flex: 0 0 44px; justify-content: center; padding: 0; }
    .tarifa-edit-back-label { display: none; }
    .tarifa-edit-context { align-items: flex-start; }
    .tarifa-current-pill { display: none; }
    .tarifa-edit-progress { display: flex; overflow-x: auto; scrollbar-width: none; }
    .tarifa-edit-progress::-webkit-scrollbar { display: none; }
    .tarifa-edit-progress a { min-width: 150px; }
    .tarifa-edit-page .section-header, .tarifa-edit-page .section-body { padding: 15px; }
    .tarifa-edit-page .option-cards.grid-cols-2 { grid-template-columns: 1fr; }
    .tarifa-edit-actions { position: fixed; right: 10px; bottom: calc(82px + env(safe-area-inset-bottom)); left: 10px; }
    .tarifa-edit-action-copy { display: none; }
    .tarifa-edit-action-buttons { width: 100%; }
    .tarifa-edit-action-buttons .btn { flex: 1; justify-content: center; }
}
</style>

<div class="tarifa-edit-page">
    <div class="tarifa-edit-shell">
        <header class="tarifa-edit-header">
            <div class="tarifa-edit-heading">
                <span class="tarifa-edit-heading-icon" aria-hidden="true"><i class="fas fa-pen"></i></span>
                <div>
                    <p class="tarifa-edit-eyebrow">Configuración de tarifas</p>
                    <h1>Editar ajuste de precio</h1>
                    <p>Revisa la configuración actual y modifica solo lo necesario.</p>
                </div>
            </div>
            <a href="<?= back_url('configuracion/tarifas') ?>" class="btn btn-secondary" aria-label="Volver al listado de tarifas">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span class="tarifa-edit-back-label">Volver a tarifas</span>
            </a>
        </header>

        <section class="tarifa-edit-context" aria-label="Ajuste que se está editando">
            <div class="tarifa-edit-context-main">
                <span class="tarifa-edit-context-icon" aria-hidden="true"><i class="fas fa-tag"></i></span>
                <div>
                    <strong><?= htmlspecialchars($incremento['nombre']) ?></strong>
                    <span>Creado el <?= format_date($incremento['created_at'], 'd/m/Y H:i') ?> por <?= htmlspecialchars($incremento['usuario_nombre'] ?? 'Usuario desconocido') ?></span>
                </div>
            </div>
            <span class="tarifa-current-pill"><i class="fas fa-check-circle" aria-hidden="true"></i> Valores actuales</span>
        </section>

        <nav class="tarifa-edit-progress" aria-label="Secciones del formulario">
            <a href="#seccionInformacion"><span>1</span> Información</a>
            <a href="#seccionTipo"><span>2</span> Operación y valor</a>
            <a href="#seccionAlcance"><span>3</span> Alcance</a>
            <a href="#seccionVigencia"><span>4</span> Vigencia</a>
        </nav>

    <div class="tarifa-container px-4">
        <form action="<?= url('configuracion/tarifas/editar/' . $incremento['id']) ?>" method="POST" id="formIncremento">
            <?= csrf_field() ?>
            <div class="tarifa-edit-grid">
            <div class="tarifa-edit-main">
            
            <!-- PASO 1: Información básica -->
            <div class="section-card" id="seccionInformacion">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">1</span>
                        Identidad del ajuste
                    </h2>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label" for="nombre">
                                Nombre del ajuste <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="nombre" 
                                   id="nombre"
                                   class="form-input" 
                                   placeholder="Ej: Temporada Alta Navidad"
                                   value="<?= htmlspecialchars($incremento['nombre']) ?>"
                                   required>
                            <p class="form-help">Un nombre corto y descriptivo</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="prioridad">Prioridad</label>
                            <input type="number" 
                                   name="prioridad" 
                                   id="prioridad"
                                   class="form-input" 
                                   value="<?= $incremento['prioridad'] ?>"
                                   min="0"
                                   max="99">
                            <p class="form-help">Mayor número = mayor prioridad</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="descripcion">Descripción <span class="font-normal text-gray-500">(opcional)</span></label>
                        <textarea name="descripcion" 
                                  id="descripcion"
                                  class="form-input" 
                                  rows="2"
                                  placeholder="Explique brevemente el motivo del incremento"><?= htmlspecialchars($incremento['descripcion']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Tipo y valor -->
            <div class="section-card" id="seccionTipo">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">2</span>
                        Operación y forma de cálculo
                    </h2>
                </div>
                <div class="section-body">
                    <?php $incClase = $incremento['clase'] ?? 'incremento'; ?>
                    <p class="form-label">Operación <span class="text-red-500">*</span></p>
                    <p class="form-help">Se muestra la operación guardada actualmente. Elige otra solo si deseas cambiarla.</p>
                    <div class="option-cards grid-cols-2" id="claseCards" style="margin-bottom:1.25rem;">
                        <label class="option-card <?= $incClase === 'descuento' ? '' : 'selected' ?>">
                            <input type="radio" name="clase" value="incremento" <?= $incClase === 'descuento' ? '' : 'checked' ?> required>
                            <div class="option-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="option-title">Incremento</div>
                            <div class="option-desc">Aumenta el precio de la habitacion</div>
                        </label>

                        <label class="option-card <?= $incClase === 'descuento' ? 'selected' : '' ?>">
                            <input type="radio" name="clase" value="descuento" <?= $incClase === 'descuento' ? 'checked' : '' ?>>
                            <div class="option-icon bg-rose-100 text-rose-600">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="option-title">Descuento</div>
                            <div class="option-desc">Resta del precio de la habitacion</div>
                        </label>
                    </div>

                    <p class="form-label">Forma de cálculo <span class="text-red-500">*</span></p>
                    <div class="option-cards grid-cols-2" id="tipoCards">
                        <label class="option-card <?= $incremento['tipo_incremento'] == 'porcentaje' ? 'selected' : '' ?>">
                            <input type="radio" name="tipo_incremento" value="porcentaje" <?= $incremento['tipo_incremento'] == 'porcentaje' ? 'checked' : '' ?> required>
                            <div class="option-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="option-title">Porcentaje</div>
                            <div class="option-desc">Aumenta el precio en un porcentaje</div>
                        </label>

                        <label class="option-card <?= $incremento['tipo_incremento'] == 'monto_fijo' ? 'selected' : '' ?>">
                            <input type="radio" name="tipo_incremento" value="monto_fijo" <?= $incremento['tipo_incremento'] == 'monto_fijo' ? 'checked' : '' ?>>
                            <div class="option-icon bg-green-100 text-green-600">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="option-title">Monto Fijo</div>
                            <div class="option-desc">Suma una cantidad fija al precio</div>
                        </label>
                    </div>

                    <div class="form-group mt-6">
                        <label class="form-label" for="valor_incremento">
                            <span id="labelValor"><?= $incremento['tipo_incremento'] == 'porcentaje' ? 'Porcentaje de incremento' : 'Monto a incrementar' ?></span> <span class="text-red-500">*</span>
                        </label>
                        <div class="flex">
                            <input type="number" 
                                   name="valor_incremento" 
                                   id="valor_incremento"
                                   class="form-input rounded-r-none flex-1" 
                                   step="0.01" 
                                   min="0.01"
                                   placeholder="0.00"
                                   value="<?= $incremento['valor_incremento'] ?>"
                                   required>
                            <span class="px-4 py-2 bg-gray-100 border-2 border-l-0 border-gray-300 rounded-r-lg font-semibold" id="simboloValor">
                                <?= $incremento['tipo_incremento'] == 'porcentaje' ? '%' : '$' ?>
                            </span>
                        </div>
                        <p class="form-help" id="ayudaValor">
                            <?= $incremento['tipo_incremento'] == 'porcentaje' 
                                ? 'Ejemplo: 15 para un incremento del 15%' 
                                : 'Ejemplo: 100 para incrementar $100' ?>
                        </p>
                    </div>

                    <!-- Preview del cálculo -->
                    <div class="preview-card" id="previewCalculo">
                        <div class="preview-title">
                            <i class="fas fa-calculator"></i>
                            Ejemplo de cálculo
                        </div>
                        <div class="text-sm text-gray-700" id="ejemploCalculo">
                            <!-- Se actualizará con JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 3: Aplicación -->
            <div class="section-card" id="seccionAlcance">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">3</span>
                        Alcance del ajuste
                    </h2>
                </div>
                <div class="section-body">
                    <p class="form-label">Aplicar en <span class="text-red-500">*</span></p>
                    <p class="form-help">La opción marcada corresponde a la configuración guardada.</p>
                    <div class="option-cards" id="alcanceOptions">
                        <label class="option-card <?= $incremento['alcance'] == 'global' ? 'selected' : '' ?>">
                            <input type="radio" name="alcance" value="global" <?= $incremento['alcance'] == 'global' ? 'checked' : '' ?> required>
                            <div class="option-icon bg-purple-100 text-purple-600">
                                <i class="fas fa-hotel"></i>
                            </div>
                            <div class="option-title">Todas las habitaciones</div>
                            <div class="option-desc">Se aplicará a las <?= count($habitaciones) ?> habitaciones del hotel</div>
                        </label>

                        <label class="option-card <?= $incremento['alcance'] == 'tipo_habitacion' ? 'selected' : '' ?>">
                            <input type="radio" name="alcance" value="tipo_habitacion" <?= $incremento['alcance'] == 'tipo_habitacion' ? 'checked' : '' ?>>
                            <div class="option-icon bg-indigo-100 text-indigo-600">
                                <i class="fas fa-bed"></i>
                            </div>
                            <div class="option-title">Por tipo de habitación</div>
                            <div class="option-desc">Solo a ciertos tipos (sencilla, doble, etc.)</div>
                        </label>

                        <label class="option-card <?= $incremento['alcance'] == 'habitacion' ? 'selected' : '' ?>">
                            <input type="radio" name="alcance" value="habitacion" <?= $incremento['alcance'] == 'habitacion' ? 'checked' : '' ?>>
                            <div class="option-icon bg-amber-100 text-amber-600">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="option-title">Habitaciones específicas</div>
                            <div class="option-desc">Seleccionar habitaciones individuales</div>
                        </label>
                    </div>

                    <!-- Selector de tipos -->
                    <div id="selectorTipos" style="display: <?= $incremento['alcance'] == 'tipo_habitacion' ? 'block' : 'none' ?>;" class="mt-6">
                        <p class="form-label mb-3">Seleccione los tipos de habitación:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach ($tipos_habitacion as $tipo): ?>
                            <label class="selection-item bg-gray-50 rounded-lg cursor-pointer">
                                <input type="checkbox" 
                                       name="tipos_habitacion[]" 
                                       value="<?= $tipo['tipo'] ?>"
                                       <?= in_array($tipo['tipo'], $incremento['tipos_habitacion_array']) ? 'checked' : '' ?>>
                                <span class="font-medium"><?= get_tipo_habitacion($tipo['tipo']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Selector de habitaciones -->
                    <div id="selectorHabitaciones" style="display: <?= $incremento['alcance'] == 'habitacion' ? 'block' : 'none' ?>;" class="mt-6">
                        <div class="flex justify-between items-center mb-3">
                            <p class="form-label">Seleccione las habitaciones:</p>
                            <button type="button" class="text-sm text-primary hover:underline" onclick="mostrarFiltroTipos()">
                                <i class="fas fa-filter"></i> Filtrar por tipo
                            </button>
                        </div>
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
                                    <?php foreach ($habs as $hab): ?>
                                    <label class="selection-item">
                                        <input type="checkbox" 
                                               name="habitaciones[]" 
                                               value="<?= $hab['id'] ?>"
                                               data-tipo="<?= $hab['tipo'] ?>"
                                               <?= in_array($hab['id'], $incremento['habitaciones_array']) ? 'checked' : '' ?>
                                               onchange="actualizarContadorHabitaciones()">
                                        <span>
                                            <strong>Habitación <?= $hab['numero'] ?></strong>
                                            <span class="text-gray-600 text-sm"><?= format_currency($hab['precio_base']) ?></span>
                                        </span>
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
            <div class="section-card" id="seccionVigencia">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">4</span>
                        Vigencia del ajuste
                    </h2>
                </div>
                <div class="section-body">
                    <div class="toggle-container">
                        <label class="toggle-switch" aria-label="Mantener el ajuste sin fecha de finalización">
                            <input type="checkbox" 
                                   name="es_permanente" 
                                   id="es_permanente"
                                   value="1"
                                   <?= $incremento['es_permanente'] ? 'checked' : '' ?>
                                   onchange="togglePermanente()">
                            <span class="toggle-slider"></span>
                        </label>
                        <div>
                            <div class="font-semibold">Ajuste permanente</div>
                            <div class="text-sm text-gray-600">No tendrá fecha de finalización</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label" for="fecha_inicio">
                                Fecha de inicio <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   name="fecha_inicio" 
                                   id="fecha_inicio"
                                   class="form-input"
                                   value="<?= $incremento['fecha_inicio'] ?>"
                                   required>
                            <p class="form-help">
                                <?php if ($incremento['fecha_inicio'] < date('Y-m-d')): ?>
                                    <i class="fas fa-info-circle text-blue-500"></i>
                                    Este incremento ya está en curso
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="form-group" id="grupoFechaFin">
                            <label class="form-label" for="fecha_fin">
                                Fecha de fin <span class="text-red-500" id="requeridoFin">*</span>
                            </label>
                            <input type="date" 
                                   name="fecha_fin" 
                                   id="fecha_fin"
                                   class="form-input"
                                   value="<?= $incremento['fecha_fin'] ?>">
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            El ajuste se aplicará automáticamente a las reservaciones realizadas durante este período.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Reservaciones existentes: revision opcional del impacto -->
            <div class="section-card" id="cardImpactoReservas">
                <div class="section-body" style="display:flex; align-items:flex-start; gap:.85rem;">
                    <label class="toggle-switch" style="flex-shrink:0; margin-top:.15rem;">
                        <input type="checkbox"
                               name="revisar_reservaciones"
                               id="revisar_reservaciones"
                               value="1">
                        <span class="toggle-slider"></span>
                    </label>
                    <div>
                        <div class="font-semibold">Revisar reservaciones existentes al guardar</div>
                        <div class="text-sm text-gray-600">
                            Las reservaciones ya creadas conservan su precio congelado. Con esta opción, al guardar verás
                            las futuras afectadas (precio actual → nuevo y saldo resultante) y confirmarás el recálculo.
                            Nada se modifica sin tu confirmación.
                        </div>
                    </div>
                </div>
            </div>

            </div><!-- end tarifa-edit-main -->

            <?php
                $editTipo = $incremento['tipo_incremento'] ?? 'porcentaje';
                $editValor = (float) ($incremento['valor_incremento'] ?? 0);
                $editOperacion = $incClase === 'descuento' ? 'Descuento' : 'Incremento';
                $editUnidad = $editTipo === 'porcentaje'
                    ? number_format($editValor, 2, '.', '') . '%'
                    : '$' . number_format($editValor, 2, '.', ',');
                $editAlcance = 'Todas las habitaciones';
                if (($incremento['alcance'] ?? '') === 'tipo_habitacion') {
                    $editTotalTipos = count($incremento['tipos_habitacion_array'] ?? []);
                    $editAlcance = $editTotalTipos . ($editTotalTipos === 1 ? ' tipo de habitación' : ' tipos de habitación');
                } elseif (($incremento['alcance'] ?? '') === 'habitacion') {
                    $editTotalHabitaciones = count($incremento['habitaciones_array'] ?? []);
                    $editAlcance = $editTotalHabitaciones . ($editTotalHabitaciones === 1 ? ' habitación' : ' habitaciones');
                }
            ?>
            <aside class="tarifa-edit-summary" aria-labelledby="tarifaEditSummaryTitle">
                <div class="tarifa-edit-summary-header">
                    <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                    <h2 id="tarifaEditSummaryTitle">Resumen actual</h2>
                    <p>Confirma aquí cómo quedará el ajuste antes de guardar.</p>
                </div>
                <dl class="tarifa-edit-summary-list" aria-live="polite">
                    <div class="tarifa-edit-summary-row">
                        <dt>Nombre</dt>
                        <dd id="resumenEditNombre"><?= htmlspecialchars($incremento['nombre']) ?></dd>
                    </div>
                    <div class="tarifa-edit-summary-row">
                        <dt>Cambio</dt>
                        <dd id="resumenEditCambio"><?= $editOperacion ?> de <?= $editUnidad ?></dd>
                    </div>
                    <div class="tarifa-edit-summary-row">
                        <dt>Alcance</dt>
                        <dd id="resumenEditAlcance"><?= htmlspecialchars($editAlcance) ?></dd>
                    </div>
                    <div class="tarifa-edit-summary-row">
                        <dt>Vigencia</dt>
                        <dd id="resumenEditVigencia">
                            Desde <?= format_date($incremento['fecha_inicio'], 'd M Y') ?><?= $incremento['es_permanente'] ? ', sin fecha de fin' : ' hasta ' . format_date($incremento['fecha_fin'], 'd M Y') ?>
                        </dd>
                    </div>
                </dl>
                <p class="tarifa-edit-summary-note"><i class="fas fa-shield-alt" aria-hidden="true"></i> Los cambios solo se aplican al confirmar el guardado.</p>
            </aside>

            <!-- Botones de acción -->
            <div id="tarifaFormAlert" class="tarifa-form-alert" role="alert" aria-live="assertive" hidden>
                <i class="fas fa-circle-exclamation"></i>
                <span data-tarifa-alert-text></span>
            </div>

            <div class="tarifa-edit-actions">
                <div class="tarifa-edit-action-copy">
                    <strong>Verifica el resumen antes de guardar</strong>
                    <span>Se solicitará una segunda confirmación para evitar cambios accidentales.</span>
                </div>
                <div class="tarifa-edit-action-buttons">
                    <a href="<?= back_url('configuracion/tarifas') ?>" class="btn btn-secondary">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        Guardar cambios
                    </button>
                </div>
            </div>
            </div><!-- end tarifa-edit-grid -->
        </form>
    </div>
    </div><!-- end tarifa-edit-shell -->
</div>

<!-- JavaScript corregido -->
<script>
function tarifaBrandPrimary() {
    return getComputedStyle(document.documentElement).getPropertyValue('--brand-primary').trim() || '#1B2746';
}

function sincronizarTarjetaSeleccionada(name, rootSelector) {
    document.querySelectorAll(`${rootSelector} .option-card`).forEach(card => {
        const radio = card.querySelector(`input[name="${name}"]`);
        card.classList.toggle('selected', Boolean(radio && radio.checked));
    });
}

// Selección de forma de cálculo
function selectTipo(tipo) {
    const radio = document.querySelector(`input[name="tipo_incremento"][value="${tipo}"]`);
    if (radio) radio.checked = true;
    sincronizarTarjetaSeleccionada('tipo_incremento', '#tipoCards');

    // Actualizar labels y ejemplo
    const label = document.getElementById('labelValor');
    const simbolo = document.getElementById('simboloValor');
    const ayuda = document.getElementById('ayudaValor');
    const valor = document.getElementById('valor_incremento').value || 15;

    const esDescuento = document.querySelector('input[name="clase"]:checked')?.value === 'descuento';
    const operacion = esDescuento ? 'descuento' : 'incremento';

    if (tipo === 'porcentaje') {
        label.textContent = `Porcentaje de ${operacion}`;
        simbolo.textContent = '%';
        ayuda.textContent = `Ejemplo: 15 para un ${operacion} del 15%`;
        actualizarEjemplo(valor, 'porcentaje');
    } else {
        label.textContent = esDescuento ? 'Monto a descontar' : 'Monto a incrementar';
        simbolo.textContent = '$';
        ayuda.textContent = esDescuento ? 'Ejemplo: 100 para descontar $100' : 'Ejemplo: 100 para incrementar $100';
        actualizarEjemplo(valor, 'monto_fijo');
    }

    actualizarResumenEdicion();
}

function selectClase(clase) {
    const radio = document.querySelector(`input[name="clase"][value="${clase}"]`);
    if (radio) radio.checked = true;
    sincronizarTarjetaSeleccionada('clase', '#claseCards');
    actualizarCardImpacto();
    const tipo = document.querySelector('input[name="tipo_incremento"]:checked')?.value;
    if (tipo) selectTipo(tipo);
    actualizarResumenEdicion();
}

// La revision de reservaciones existentes solo aplica a incrementos:
// los descuentos se calculan al crear cada reservacion, no en retro.
function actualizarCardImpacto() {
    const card = document.getElementById('cardImpactoReservas');
    if (!card) { return; }
    const claseSel = document.querySelector('input[name="clase"]:checked');
    const esDescuento = claseSel && claseSel.value === 'descuento';
    card.style.display = esDescuento ? 'none' : '';
    if (esDescuento) {
        const chk = document.getElementById('revisar_reservaciones');
        if (chk) { chk.checked = false; }
    }
}
document.addEventListener('DOMContentLoaded', actualizarCardImpacto);

// Selección del alcance
function selectAlcance(alcance) {
    const radio = document.querySelector(`input[name="alcance"][value="${alcance}"]`);
    if (radio) radio.checked = true;
    sincronizarTarjetaSeleccionada('alcance', '#alcanceOptions');

    // Mostrar/ocultar selectores
    document.getElementById('selectorTipos').style.display = 'none';
    document.getElementById('selectorHabitaciones').style.display = 'none';

    if (alcance === 'tipo_habitacion') {
        document.getElementById('selectorTipos').style.display = 'block';
    } else if (alcance === 'habitacion') {
        document.getElementById('selectorHabitaciones').style.display = 'block';
        actualizarContadorHabitaciones();
    }

    actualizarResumenEdicion();
}

// Toggle permanente
function togglePermanente() {
    const isPermanente = document.getElementById('es_permanente').checked;
    const fechaFinGroup = document.getElementById('grupoFechaFin');
    const fechaFinInput = document.getElementById('fecha_fin');
    const requerido = document.getElementById('requeridoFin');

    if (isPermanente) {
        fechaFinGroup.style.opacity = '0.5';
        fechaFinInput.removeAttribute('required');
        requerido.style.display = 'none';
    } else {
        fechaFinGroup.style.opacity = '1';
        fechaFinInput.setAttribute('required', 'required');
        requerido.style.display = 'inline';
    }
    actualizarResumenEdicion();
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
    actualizarResumenEdicion();
}

function formatearFechaEdicion(value) {
    if (!value) return '';
    const date = new Date(`${value}T00:00:00`);
    return new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
}

function actualizarResumenEdicion() {
    const nombre = document.getElementById('nombre')?.value.trim() || 'Sin nombre';
    const clase = document.querySelector('input[name="clase"]:checked')?.value || '';
    const tipo = document.querySelector('input[name="tipo_incremento"]:checked')?.value || '';
    const valor = Number.parseFloat(document.getElementById('valor_incremento')?.value) || 0;
    const alcance = document.querySelector('input[name="alcance"]:checked')?.value || '';
    const inicio = document.getElementById('fecha_inicio')?.value || '';
    const fin = document.getElementById('fecha_fin')?.value || '';
    const permanente = Boolean(document.getElementById('es_permanente')?.checked);

    const cambio = document.getElementById('resumenEditCambio');
    const alcanceResumen = document.getElementById('resumenEditAlcance');
    const vigencia = document.getElementById('resumenEditVigencia');
    const nombreResumen = document.getElementById('resumenEditNombre');

    if (nombreResumen) nombreResumen.textContent = nombre;
    if (cambio) {
        const operacion = clase === 'descuento' ? 'Descuento' : 'Incremento';
        const unidad = tipo === 'porcentaje' ? `${valor}%` : `$${valor.toLocaleString('es-MX')}`;
        cambio.textContent = `${operacion} de ${unidad}`;
    }
    if (alcanceResumen) {
        if (alcance === 'tipo_habitacion') {
            const total = document.querySelectorAll('input[name="tipos_habitacion[]"]:checked').length;
            alcanceResumen.textContent = `${total} ${total === 1 ? 'tipo de habitación' : 'tipos de habitación'}`;
        } else if (alcance === 'habitacion') {
            const total = document.querySelectorAll('input[name="habitaciones[]"]:checked').length;
            alcanceResumen.textContent = `${total} ${total === 1 ? 'habitación' : 'habitaciones'}`;
        } else {
            alcanceResumen.textContent = 'Todas las habitaciones';
        }
    }
    if (vigencia) {
        const desde = inicio ? `Desde ${formatearFechaEdicion(inicio)}` : 'Sin fecha de inicio';
        vigencia.textContent = permanente ? `${desde}, sin fecha de fin` : (fin ? `${desde} hasta ${formatearFechaEdicion(fin)}` : `${desde}, sin fecha de fin`);
    }
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
        confirmButtonColor: tarifaBrandPrimary(),
        cancelButtonText: 'Cancelar'
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
    const tipo = document.querySelector('input[name="tipo_incremento"]:checked')?.value;
    if (tipo) actualizarEjemplo(this.value || 0, tipo);
    actualizarResumenEdicion();
});

const formIncremento = document.getElementById('formIncremento');
const tarifaFormAlert = document.getElementById('tarifaFormAlert');
const tarifaSubmitButton = formIncremento ? formIncremento.querySelector('button[type="submit"]') : null;
const tarifaSubmitDefaultHtml = tarifaSubmitButton ? tarifaSubmitButton.innerHTML : '';
let tarifaSaveConfirmTimer = null;

document.querySelectorAll('#claseCards input[name="clase"]').forEach(input => {
    input.addEventListener('change', () => selectClase(input.value));
});

document.querySelectorAll('#tipoCards input[name="tipo_incremento"]').forEach(input => {
    input.addEventListener('change', () => selectTipo(input.value));
});

document.querySelectorAll('#alcanceOptions input[name="alcance"]').forEach(input => {
    input.addEventListener('change', () => selectAlcance(input.value));
});

document.querySelectorAll('#nombre, #fecha_inicio, #fecha_fin, #es_permanente').forEach(input => {
    input.addEventListener(input.type === 'text' ? 'input' : 'change', actualizarResumenEdicion);
});

function mostrarErrorFormularioTarifa(message, targetSelector, mode) {
    if (!tarifaFormAlert) {
        return;
    }

    const text = tarifaFormAlert.querySelector('[data-tarifa-alert-text]');
    if (text) {
        text.textContent = message;
    }

    tarifaFormAlert.hidden = false;
    tarifaFormAlert.classList.add('is-visible');
    tarifaFormAlert.classList.toggle('is-confirmation', mode === 'confirmation');

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
    tarifaFormAlert.classList.remove('is-confirmation');
    tarifaFormAlert.hidden = true;
}

function setTarifaSaveConfirmState(active) {
    if (!tarifaSubmitButton) {
        return;
    }

    tarifaSubmitButton.classList.toggle('is-confirming', active);
    tarifaSubmitButton.innerHTML = active
        ? '<i class="fas fa-check"></i> Confirmar cambios'
        : tarifaSubmitDefaultHtml;
}

document.querySelectorAll('input[name="tipos_habitacion[]"], input[name="habitaciones[]"], input[name="alcance"]').forEach(input => {
    input.addEventListener('change', function () {
        limpiarErrorFormularioTarifa();
        actualizarResumenEdicion();
    });
});

// Validación del formulario
if (formIncremento) {
    formIncremento.addEventListener('submit', function(e) {
        const alcance = document.querySelector('input[name="alcance"]:checked')?.value;
        limpiarErrorFormularioTarifa();

        if (!alcance) {
            e.preventDefault();
            mostrarErrorFormularioTarifa('Selecciona dónde se aplicará el ajuste.', '#alcanceOptions');
            return;
        }

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

        e.preventDefault();

        if (this.dataset.confirmedTarifaSave === '1') {
            this.submit();
            return;
        }

        this.dataset.confirmedTarifaSave = '1';
        setTarifaSaveConfirmState(true);
        mostrarErrorFormularioTarifa('Vuelve a hacer clic en Confirmar cambios para guardar esta tarifa.', null, 'confirmation');

        window.clearTimeout(tarifaSaveConfirmTimer);
        tarifaSaveConfirmTimer = window.setTimeout(() => {
            delete this.dataset.confirmedTarifaSave;
            setTarifaSaveConfirmState(false);
            limpiarErrorFormularioTarifa();
        }, 6500);
    });
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    togglePermanente();
    actualizarContadorHabitaciones();
    sincronizarTarjetaSeleccionada('clase', '#claseCards');
    sincronizarTarjetaSeleccionada('tipo_incremento', '#tipoCards');
    sincronizarTarjetaSeleccionada('alcance', '#alcanceOptions');
    
    // Actualizar ejemplo inicial
    const tipo = document.querySelector('input[name="tipo_incremento"]:checked')?.value;
    const valor = document.getElementById('valor_incremento').value;
    if (tipo) {
        selectTipo(tipo);
        actualizarEjemplo(valor, tipo);
    }
    actualizarResumenEdicion();
});
</script>

<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
