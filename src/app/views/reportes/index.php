<?php ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

/* Reports overview: hotel brand-aware visual layer. */
.reportes-view {
    --report-brand: var(--brand-action-bg, var(--brand-primary, #1B2746));
    --report-brand-dark: var(--brand-action-bg-hover, color-mix(in srgb, var(--report-brand) 84%, #020617 16%));
    --report-on-brand: var(--brand-action-text, #FFFEFB);
    --report-brand-mid: color-mix(in srgb, var(--report-brand) 18%, #FCFAF5);
    --report-accent: var(--brand-accent, #BD9441);
    --report-accent-dark: color-mix(in srgb, var(--report-accent) 72%, #3F2E12);
    --report-accent-soft: color-mix(in srgb, var(--report-accent) 15%, #FFFFFF);
    --report-accent-line: color-mix(in srgb, var(--report-accent) 36%, #E9DDC8);
    --report-border: color-mix(in srgb, var(--report-brand) 11%, #E7E1D4);
    --report-soft: color-mix(in srgb, var(--report-brand) 6%, #FCFAF5);
    --report-ivory: #F6F2EA;
    --report-ivory-2: #FBF8F2;
    --report-surface: rgba(255,255,255,.96);
    --report-surface-warm: #FCFAF5;
    --report-muted: #6C7689;
    --report-text: #1B2746;
    --report-success: #1E9E63;
    --report-success-soft: #E8F4ED;
    --report-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --report-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: var(--report-text);
    font-family: var(--report-sans);
    opacity:0;
    transition: opacity .35s ease;
}
.reportes-view.loaded { opacity:1; }

/* Background */
.rep-bg {
    background:
        linear-gradient(135deg, rgba(255,255,255,.32) 0 25%, transparent 25% 50%) 0 0 / 22px 22px,
        linear-gradient(180deg, var(--report-ivory-2), var(--report-ivory) 100%);
    min-height:100vh;
}

/* ── Hero header ─────────────────────────── */
.rep-hero {
    background: transparent;
    position: relative; overflow: visible;
    border-bottom: 1px solid var(--report-border);
}
.rep-hero::before {
    content:none;
}
.rep-hero::after {
    content:none;
}
.rep-hero-icon {
    background:linear-gradient(150deg, var(--report-brand), var(--report-brand-dark));
    color: var(--report-on-brand);
    width:46px; height:46px; border-radius:13px;
    display:flex; align-items:center; justify-content:center;
    border:0;
    box-shadow:0 12px 24px -10px color-mix(in srgb, var(--report-brand) 58%, transparent);
}
.reportes-view .rep-hero-icon i {
    color: var(--report-on-brand) !important;
}
.reportes-view .rep-hero h1 {
    color:var(--report-brand) !important;
    font-family:var(--report-serif);
    font-size:clamp(2rem, 3vw, 2.6rem);
    font-weight:700;
    line-height:1;
    letter-spacing:0;
}
.reportes-view .rep-hero p {
    color:var(--report-muted) !important;
    font-weight:500;
}
.report-chip {
    display:inline-flex; align-items:center; gap:5px;
    background:var(--report-accent-soft); border:1px solid var(--report-accent-line);
    color:var(--report-accent-dark); border-radius:999px;
    padding:6px 11px; font-size:.72rem; font-weight:800; letter-spacing:.03em;
}

/* ── Stat widgets ────────────────────────── */
.rep-stat {
    background:#fff; border-radius:14px; border:1px solid var(--report-border);
    padding:20px; overflow:hidden; position:relative;
    transition: transform .25s, box-shadow .25s;
    animation: slideUp .5s ease both;
}
.rep-stat:hover { transform:translateY(-3px); box-shadow:0 10px 28px color-mix(in srgb, var(--report-brand) 14%, transparent); }
.rep-stat-icon {
    width:44px; height:44px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; font-size:18px;
}
.rep-stat::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:2px;
    opacity:0; transition:opacity .25s;
    background: var(--accent, var(--report-brand));
}
.rep-stat:hover::after { opacity:1; }

@keyframes slideUp {
    from { opacity:0; transform:translateY(18px); }
    to   { opacity:1; transform:translateY(0); }
}
.rep-stat:nth-child(1) { animation-delay:.05s; }
.rep-stat:nth-child(2) { animation-delay:.12s; }
.rep-stat:nth-child(3) { animation-delay:.19s; }
.rep-stat:nth-child(4) { animation-delay:.26s; }

/* ── Report cards ────────────────────────── */
.rep-card {
    background:var(--report-surface); border-radius:16px;
    border:1px solid var(--report-border);
    padding:0; overflow:hidden; position:relative;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    animation: slideUp .5s ease both;
    display:flex; flex-direction:column;
    box-shadow:0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28);
}
.rep-card:hover {
    transform:translateY(-3px);
    border-color:color-mix(in srgb, var(--report-color, var(--report-brand)) 30%, var(--report-border));
    box-shadow:0 16px 34px -24px color-mix(in srgb, var(--report-color, var(--report-brand)) 58%, #172033);
}

/* Shimmer on hover */
.rep-card::before {
    content:none;
}
.rep-card:hover::before { left:auto; }

.rep-card:nth-child(1) { animation-delay:.3s; }
.rep-card:nth-child(2) { animation-delay:.38s; }
.rep-card:nth-child(3) { animation-delay:.46s; }
.rep-card:nth-child(4) { animation-delay:.54s; }

/* Card accent bar */
.card-accent {
    height:1px; width:100%;
    background:color-mix(in srgb, var(--report-color, var(--report-brand)) 42%, var(--report-border));
}

.card-body { padding:22px; flex:1; display:flex; flex-direction:column; }

/* Icon wrapper with animation */
.rep-icon-wrap {
    width:56px; height:56px; border-radius:14px;
    display:flex; align-items:center; justify-content:center; font-size:22px;
    transition: transform .22s ease, box-shadow .22s ease; margin-bottom:14px;
    background:var(--report-color-soft, var(--report-soft));
    color:var(--report-color, var(--report-brand));
    box-shadow:0 10px 22px -16px color-mix(in srgb, var(--report-color, var(--report-brand)) 68%, transparent);
}
.rep-icon-wrap i {
    color:var(--report-color, var(--report-brand)) !important;
}
.rep-card:hover .rep-icon-wrap {
    transform: translateY(-2px);
    box-shadow:0 14px 26px -16px color-mix(in srgb, var(--report-color, var(--report-brand)) 72%, transparent);
}

/* Feature list */
.feat-list { list-style:none; padding:0; margin:0 0 16px; flex:1; }
.feat-list li {
    display:flex; align-items:center; gap:7px;
    font-size:.75rem; color:var(--report-muted); padding:3px 0;
}
.feat-list li i { color:var(--report-color, var(--report-brand)); font-size:.65rem; flex-shrink:0; }

/* CTA buttons */
.rep-cta {
    display:flex; align-items:center; justify-content:center; gap:8px;
    width:100%; padding:11px 16px; border-radius:11px;
    font-size:.82rem; font-weight:800; text-decoration:none;
    transition: box-shadow .18s ease, transform .18s ease, background .18s ease; border:none;
    position:relative; z-index:2;
    background:linear-gradient(135deg, var(--report-brand), var(--report-brand-dark));
    color:#fff;
    box-shadow:0 12px 24px -12px color-mix(in srgb, var(--report-brand) 64%, transparent);
}
.rep-cta:hover { transform:translateY(-1px); box-shadow:0 16px 30px -14px color-mix(in srgb, var(--report-brand) 70%, transparent); }
.rep-cta:focus-visible { outline:3px solid color-mix(in srgb, var(--report-accent) 36%, transparent); outline-offset:3px; }

/* Category badge */
.cat-badge {
    font-size:.65rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase;
    padding:4px 9px; border-radius:999px; white-space:nowrap;
    background:var(--report-color-soft, var(--report-soft));
    color:var(--report-color-strong, var(--report-brand-dark));
    border:1px solid color-mix(in srgb, var(--report-color, var(--report-brand)) 24%, var(--report-border));
}
.section-kicker {
    display:flex; align-items:flex-start; gap:12px; margin-bottom:20px;
}
.section-kicker-bar {
    width:34px; height:1px; flex-shrink:0; margin-top:12px;
    background:var(--report-accent-line);
    border-radius:999px;
}
.section-kicker h2 {
    color:var(--report-brand);
    font-family:var(--report-serif);
    font-size:1.45rem;
    font-weight:700;
    line-height:1;
}
.section-kicker p { color:var(--report-muted) !important; }

.reportes-view .report-grid .rep-card:nth-child(1) {
    --report-color: var(--report-brand) !important;
    --report-color-strong: var(--report-brand-dark) !important;
    --report-color-soft: var(--report-soft) !important;
}
.reportes-view .report-grid .rep-card:nth-child(2) {
    --report-color: var(--report-success) !important;
    --report-color-strong: color-mix(in srgb, var(--report-success) 72%, #123322) !important;
    --report-color-soft: var(--report-success-soft) !important;
}
.reportes-view .report-grid .rep-card:nth-child(3) {
    --report-color: var(--report-accent) !important;
    --report-color-strong: var(--report-accent-dark) !important;
    --report-color-soft: var(--report-accent-soft) !important;
}
.reportes-view .report-grid .rep-card:nth-child(4) {
    --report-color: #607084 !important;
    --report-color-strong: #334155 !important;
    --report-color-soft: #EEF1F4 !important;
}
.reportes-view .rep-card h3 {
    color:var(--report-brand) !important;
    font-weight:800;
}
.reportes-view .rep-card p {
    color:var(--report-muted) !important;
}

/* Color balance: keep report categories distinct, use hotel color as an accent. */
.reportes-view {
    --report-heading: var(--brand-text, #111827);
    --report-text: var(--brand-text, #1F2937);
    --report-muted: var(--brand-muted, #667085);
    --report-border: color-mix(in srgb, var(--report-brand) 6%, #E6E0D4);
    --report-soft: color-mix(in srgb, var(--report-brand) 3%, #FAF8F2);
    --report-surface: #FFFFFF;
    --report-surface-warm: color-mix(in srgb, var(--report-accent) 3%, #FFFFFF);
    --report-shadow: 0 1px 2px rgba(17,24,39,.035), 0 14px 30px -24px rgba(17,24,39,.34);
    color: var(--report-text);
}

.rep-bg {
    background:
        radial-gradient(circle at 10% 0%, color-mix(in srgb, var(--report-accent) 6%, transparent) 0, transparent 26%),
        radial-gradient(circle at 92% 4%, color-mix(in srgb, var(--report-brand) 4%, transparent) 0, transparent 24%),
        linear-gradient(180deg, #FBFAF6, #FFFFFF 48%, #F8F5ED) !important;
}

.reportes-view .rep-hero {
    border-bottom-color: var(--report-border);
}

.reportes-view .rep-hero-icon {
    background: linear-gradient(150deg, var(--report-brand), var(--report-brand-dark)) !important;
    color: var(--report-on-brand) !important;
    box-shadow: 0 14px 24px -18px color-mix(in srgb, var(--report-brand) 72%, #111827) !important;
}

.reportes-view .rep-hero h1,
.reportes-view .section-kicker h2,
.reportes-view .rep-card h3 {
    color: var(--report-heading) !important;
}

.reportes-view .report-chip {
    background: var(--report-surface) !important;
    border-color: var(--report-border) !important;
    color: var(--report-heading) !important;
    box-shadow: 0 10px 22px -20px rgba(17,24,39,.45);
}

.reportes-view .section-kicker-bar {
    background: color-mix(in srgb, var(--report-accent) 38%, var(--report-border)) !important;
}

.reportes-view .report-grid .rep-card:nth-child(1) {
    --report-color: #3B6EA8 !important;
    --report-color-strong: #234C78 !important;
    --report-color-soft: #EAF1F8 !important;
}
.reportes-view .report-grid .rep-card:nth-child(2) {
    --report-color: #2F855A !important;
    --report-color-strong: #276749 !important;
    --report-color-soft: #E8F4ED !important;
}
.reportes-view .report-grid .rep-card:nth-child(3) {
    --report-color: #A16207 !important;
    --report-color-strong: #7C4A03 !important;
    --report-color-soft: #F8F0DC !important;
}
.reportes-view .report-grid .rep-card:nth-child(4) {
    --report-color: #64748B !important;
    --report-color-strong: #334155 !important;
    --report-color-soft: #EEF1F4 !important;
}

.reportes-view .rep-card {
    background: var(--report-surface) !important;
    border-color: var(--report-border) !important;
    box-shadow: var(--report-shadow) !important;
}

.reportes-view .rep-card:hover {
    border-color: color-mix(in srgb, var(--report-color) 20%, var(--report-border)) !important;
    box-shadow: 0 16px 34px -26px color-mix(in srgb, var(--report-color) 42%, #111827) !important;
}

.reportes-view .card-accent {
    height: 2px;
    background: color-mix(in srgb, var(--report-color) 28%, var(--report-border)) !important;
}

.reportes-view .rep-icon-wrap {
    background: color-mix(in srgb, var(--report-color) 9%, #FFFFFF) !important;
    color: var(--report-color-strong) !important;
    border: 1px solid color-mix(in srgb, var(--report-color) 18%, var(--report-border));
    box-shadow: none !important;
}

.reportes-view .rep-icon-wrap i,
.reportes-view .feat-list li i {
    color: var(--report-color-strong) !important;
}

.reportes-view .cat-badge {
    background: color-mix(in srgb, var(--report-color) 8%, #FFFFFF) !important;
    color: var(--report-color-strong) !important;
    border-color: color-mix(in srgb, var(--report-color) 18%, var(--report-border)) !important;
}

.reportes-view .rep-cta {
    background: linear-gradient(
        135deg,
        var(--report-brand),
        var(--report-brand-dark)
    ) !important;
    color: var(--report-on-brand) !important;
    border-radius: 10px !important;
    box-shadow: 0 12px 22px -18px color-mix(in srgb, var(--report-brand) 72%, #111827) !important;
}

.reportes-view .rep-cta:hover {
    background: linear-gradient(
        135deg,
        color-mix(in srgb, var(--report-brand) 92%, #FFFEFB),
        var(--report-brand-dark)
    ) !important;
    box-shadow: 0 14px 26px -18px color-mix(in srgb, var(--report-brand) 78%, #111827) !important;
}

.reportes-view .rep-cta:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--report-brand) 28%, transparent) !important;
    outline-offset: 3px;
}

.reportes-view .report-scroll::-webkit-scrollbar-thumb {
    background: color-mix(in srgb, var(--report-brand) 48%, #667085) !important;
}

@media (min-width: 1280px) {
    .reportes-view .report-grid {
        grid-template-columns:repeat(4, minmax(0, 1fr));
    }
}

/* ── Scrollbar ───────────────────────────── */
.report-scroll::-webkit-scrollbar { width:4px; }
.report-scroll::-webkit-scrollbar-track { background:#E5E7EB; }
.report-scroll::-webkit-scrollbar-thumb { background:var(--report-brand); border-radius:4px; }
</style>

<!-- ════════════════════════════════ REPORTES PAGE ════════ -->
<div class="reportes-view rep-bg">

    <!-- Hero Header -->
    <div class="rep-hero">
        <div class="container mx-auto px-5 sm:px-7 py-6 relative z-10">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div class="flex items-start gap-3">
                    <div class="rep-hero-icon flex-shrink-0">
                        <i class="fas fa-chart-line text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white">
                            Centro de Reportes y Análisis
                        </h1>
                        <p class="text-sm text-white/75 mt-1 max-w-2xl">Consulta los reportes activos del hotel con filtros propios en cada módulo.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="report-chip"><i class="fas fa-user-shield text-xs"></i> Gerencia</span>
                    <span class="report-chip"><i class="fas fa-folder-open text-xs"></i> 6 areas activas</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-5 sm:px-7 py-6 max-w-7xl">

        <!-- Section title -->
        <div class="section-kicker">
            <div class="section-kicker-bar"></div>
            <div>
                <h2 class="text-base font-bold">Reportes disponibles</h2>
                <p class="text-sm text-slate-500">Elige el área a revisar; cada reporte conserva sus filtros, tablas y exportaciones existentes.</p>
            </div>
        </div>

        <!-- Report Cards Grid -->
        <div class="report-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

            <!-- 1: Ingresos vs Gastos -->
            <div class="rep-card" style="--report-color:#2563EB;--report-color-strong:#1D4ED8;--report-color-soft:#DBEAFE;">
                <div class="card-accent"></div>
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="rep-icon-wrap">
                            <i class="fas fa-balance-scale text-white"></i>
                        </div>
                        <span class="cat-badge">Financiero</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1.5">Análisis de Ingresos vs Gastos</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">Compara movimientos del hotel por período para revisar balance y utilidad.</p>
                    <ul class="feat-list">
                        <li><i class="fas fa-check-circle"></i>Ingresos y gastos por categoría</li>
                        <li><i class="fas fa-check-circle"></i>Evolución diaria</li>
                        <li><i class="fas fa-check-circle"></i>Utilidad del período</li>
                    </ul>
                    <a href="<?= url('reportes/ingresos-gastos') ?>" class="rep-cta">
                        <i class="fas fa-chart-bar text-sm"></i> Abrir reporte
                    </a>
                </div>
            </div>

            <!-- 2: Procedencia Geográfica -->
            <div class="rep-card" style="--report-color:#059669;--report-color-strong:#047857;--report-color-soft:#D1FAE5;">
                <div class="card-accent"></div>
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="rep-icon-wrap">
                            <i class="fas fa-map-marked-alt text-white"></i>
                        </div>
                        <span class="cat-badge">Geográfico</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1.5">Procedencia de Huéspedes</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">Revisa de dónde llegan los huéspedes y cómo se distribuye su actividad.</p>
                    <ul class="feat-list">
                        <li><i class="fas fa-check-circle"></i>Distribución por estados</li>
                        <li><i class="fas fa-check-circle"></i>Top geográfico</li>
                        <li><i class="fas fa-check-circle"></i>Evolución por origen</li>
                    </ul>
                    <a href="<?= url('reportes/procedencia') ?>" class="rep-cta">
                        <i class="fas fa-globe-americas text-sm"></i> Abrir reporte
                    </a>
                </div>
            </div>

            <!-- 3: Habitaciones Rentables -->
            <div class="rep-card" style="--report-color:#7C3AED;--report-color-strong:#5B21B6;--report-color-soft:#EDE9FE;">
                <div class="card-accent"></div>
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="rep-icon-wrap">
                            <i class="fas fa-trophy text-white"></i>
                        </div>
                        <span class="cat-badge">Performance</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1.5">Habitaciones más Rentables</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">Identifica rendimiento por habitación, tipo y ocupación del período.</p>
                    <ul class="feat-list">
                        <li><i class="fas fa-check-circle"></i>Ranking por ingresos</li>
                        <li><i class="fas fa-check-circle"></i>Ocupación y precio promedio</li>
                        <li><i class="fas fa-check-circle"></i>Análisis por tipo</li>
                    </ul>
                    <a href="<?= url('reportes/habitaciones-rentables') ?>" class="rep-cta">
                        <i class="fas fa-medal text-sm"></i> Abrir reporte
                    </a>
                </div>
            </div>

            <!-- 4: Mantenimiento -->
            <div class="rep-card" style="--report-color:#D97706;--report-color-strong:#92400E;--report-color-soft:#FEF3C7;">
                <div class="card-accent"></div>
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="rep-icon-wrap">
                            <i class="fas fa-tools text-white"></i>
                        </div>
                        <span class="cat-badge">Operativo</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1.5">Mantenimiento de Habitaciones</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">Consulta trabajos, costos y prioridades sin mezclarlo con el flujo de caja.</p>
                    <ul class="feat-list">
                        <li><i class="fas fa-check-circle"></i>Tipos y prioridades</li>
                        <li><i class="fas fa-check-circle"></i>Costos y duración</li>
                        <li><i class="fas fa-check-circle"></i>Responsables y registro</li>
                    </ul>
                    <a href="<?= url('reportes/mantenimiento') ?>" class="rep-cta">
                        <i class="fas fa-wrench text-sm"></i> Abrir reporte
                    </a>
                </div>
            </div>

            <!-- 5: Links seguros -->
            <div class="rep-card" style="--report-color:#0F766E;--report-color-strong:#115E59;--report-color-soft:#CCFBF1;">
                <div class="card-accent"></div>
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="rep-icon-wrap">
                            <i class="fas fa-link text-white"></i>
                        </div>
                        <span class="cat-badge">Seguridad</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1.5">Historial y Links Seguros</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">Administra PDFs guardados para compartirlos con expiracion y revocacion.</p>
                    <ul class="feat-list">
                        <li><i class="fas fa-check-circle"></i>Historial interno</li>
                        <li><i class="fas fa-check-circle"></i>Vencimiento de links</li>
                        <li><i class="fas fa-check-circle"></i>Control de accesos</li>
                    </ul>
                    <a href="<?= url('reportes/links') ?>" class="rep-cta">
                        <i class="fas fa-shield-alt text-sm"></i> Abrir historial
                    </a>
                </div>
            </div>

            <!-- 6: Reporte gerencial diario -->
            <div class="rep-card" style="--report-color:#1F2937;--report-color-strong:#111827;--report-color-soft:#EEF2F7;">
                <div class="card-accent"></div>
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="rep-icon-wrap">
                            <i class="fas fa-briefcase text-white"></i>
                        </div>
                        <span class="cat-badge">Gerencial</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1.5">Reporte Gerencial Diario</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">Resume ingresos, ocupacion, agenda, caja y pendientes criticos del dia.</p>
                    <ul class="feat-list">
                        <li><i class="fas fa-check-circle"></i>Vista ejecutiva diaria</li>
                        <li><i class="fas fa-check-circle"></i>Pendientes accionables</li>
                        <li><i class="fas fa-check-circle"></i>Notificacion automatica</li>
                    </ul>
                    <a href="<?= url('reportes/gerencial-diario') ?>" class="rep-cta">
                        <i class="fas fa-chart-pie text-sm"></i> Abrir reporte
                    </a>
                </div>
            </div>

        </div><!-- end report-grid -->

        <!-- Bottom spacer -->
        <div class="h-8"></div>

    </div><!-- end container -->
</div><!-- end reportes-view -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.reportes-view');
    if (view) setTimeout(() => view.classList.add('loaded'), 80);
});

function reportBrandColor() {
    const styles = getComputedStyle(document.documentElement);
    return (styles.getPropertyValue('--brand-primary') || '#2563EB').trim();
}

function exportarTodos() {
    Swal.fire({
        title: 'Exportar Reportes',
        text: '¿En qué formato desea exportar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: reportBrandColor(),
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-file-pdf mr-2"></i>PDF',
        cancelButtonText: '<i class="fas fa-file-excel mr-2"></i>Excel',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed || result.isDismissed) {
            Swal.fire({ icon:'info', title:'En desarrollo', text:'Esta función estará disponible próximamente', confirmButtonColor:reportBrandColor() });
        }
    });
}

function programarReporte() {
    Swal.fire({
        title: 'Programar Reporte',
        html: `<div class="text-left space-y-3">
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de Reporte</label>
            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option>Reporte de Ocupación</option><option>Análisis de Ingresos</option><option>Todos los reportes</option>
            </select></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Frecuencia</label>
            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option>Diario</option><option>Semanal</option><option>Mensual</option>
            </select></div>
            <div><label class="block text-xs font-semibold text-gray-600 mb-1">Hora de envío</label>
            <input type="time" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="08:00"></div>
        </div>`,
        showCancelButton: true,
        confirmButtonColor: reportBrandColor(),
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-2"></i>Programar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) Swal.fire({ icon:'success', title:'¡Programado!', text:'El reporte se enviará según lo configurado', confirmButtonColor:reportBrandColor() });
    });
}

function crearReportePersonalizado() {
    Swal.fire({ title:'Reporte Personalizado', text:'Próximamente podrá diseñar sus propios reportes con métricas específicas', icon:'info', confirmButtonColor:reportBrandColor(), confirmButtonText:'Entendido' });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
