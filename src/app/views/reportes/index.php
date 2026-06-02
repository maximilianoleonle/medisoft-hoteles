<?php ?>

<style>
/* Reports overview: hotel brand-aware visual layer. */
.reportes-view {
    --report-brand: var(--brand-primary, #2563EB);
    --report-brand-dark: color-mix(in srgb, var(--report-brand) 82%, #111827 18%);
    --report-brand-mid: color-mix(in srgb, var(--report-brand) 72%, #ffffff 28%);
    --report-accent: var(--brand-accent, #F59E0B);
    --report-border: color-mix(in srgb, var(--report-brand) 16%, #E5E7EB 84%);
    --report-soft: color-mix(in srgb, var(--report-brand) 9%, #F8FAFC 91%);
    --report-muted: #64748B;
    opacity:0;
    transition: opacity .35s ease;
}
.reportes-view.loaded { opacity:1; }

/* Background */
.rep-bg {
    background:
        radial-gradient(circle at top left, color-mix(in srgb, var(--report-brand) 10%, transparent) 0, transparent 34%),
        linear-gradient(145deg,#F8FAFC 0%,#F3F6F8 54%,#FFFFFF 100%);
    min-height:100vh;
}

/* ── Hero header ─────────────────────────── */
.rep-hero {
    background: linear-gradient(135deg, var(--report-brand-dark) 0%, var(--report-brand) 58%, var(--report-brand-mid) 100%);
    position: relative; overflow: hidden;
    border-bottom: 1px solid color-mix(in srgb, var(--report-brand) 28%, transparent);
}
.rep-hero::before {
    content:'';
    position:absolute; top:-60px; right:-60px;
    width:280px; height:280px; border-radius:50%;
    background: color-mix(in srgb, var(--report-accent) 16%, transparent); pointer-events:none;
}
.rep-hero::after {
    content:'';
    position:absolute; bottom:-80px; left:-40px;
    width:220px; height:220px; border-radius:50%;
    background: rgba(255,255,255,.04); pointer-events:none;
}
.rep-hero-icon {
    background:rgba(255,255,255,.14);
    width:44px; height:44px; border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    border:1px solid rgba(255,255,255,.16);
    box-shadow:0 14px 34px rgba(15,23,42,.16);
}
.report-chip {
    display:inline-flex; align-items:center; gap:5px;
    background:rgba(255,255,255,.13); border:1px solid rgba(255,255,255,.22);
    color:#fff; border-radius:999px;
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
    background:#fff; border-radius:14px;
    border:1px solid var(--report-border);
    padding:0; overflow:hidden; position:relative;
    transition: transform .3s, box-shadow .3s;
    animation: slideUp .5s ease both;
    display:flex; flex-direction:column;
}
.rep-card:hover { transform:translateY(-4px); box-shadow:0 18px 42px color-mix(in srgb, var(--report-color, var(--report-brand)) 16%, transparent); }

/* Shimmer on hover */
.rep-card::before {
    content:''; position:absolute; top:0; left:-110%;
    width:100%; height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.18),transparent);
    transition:left .55s ease; pointer-events:none; z-index:1;
}
.rep-card:hover::before { left:110%; }

.rep-card:nth-child(1) { animation-delay:.3s; }
.rep-card:nth-child(2) { animation-delay:.38s; }
.rep-card:nth-child(3) { animation-delay:.46s; }
.rep-card:nth-child(4) { animation-delay:.54s; }

/* Card accent bar */
.card-accent {
    height:4px; width:100%;
    background: linear-gradient(90deg, var(--report-color, var(--report-brand)), color-mix(in srgb, var(--report-color, var(--report-brand)) 70%, #ffffff 30%));
}

.card-body { padding:20px; flex:1; display:flex; flex-direction:column; }

/* Icon wrapper with animation */
.rep-icon-wrap {
    width:56px; height:56px; border-radius:14px;
    display:flex; align-items:center; justify-content:center; font-size:22px;
    transition: transform .3s, box-shadow .3s; margin-bottom:14px;
    background: linear-gradient(135deg, var(--report-color, var(--report-brand)), color-mix(in srgb, var(--report-color, var(--report-brand)) 72%, #ffffff 28%));
    box-shadow:0 10px 24px color-mix(in srgb, var(--report-color, var(--report-brand)) 24%, transparent);
}
.rep-card:hover .rep-icon-wrap {
    transform: translateY(-2px);
    box-shadow:0 14px 28px color-mix(in srgb, var(--report-color, var(--report-brand)) 28%, transparent);
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
    width:100%; padding:10px 16px; border-radius:10px;
    font-size:.82rem; font-weight:700; text-decoration:none;
    transition: box-shadow .2s, transform .2s; border:none;
    position:relative; z-index:2;
    background:linear-gradient(135deg, var(--report-color, var(--report-brand)), color-mix(in srgb, var(--report-color, var(--report-brand)) 82%, #111827 18%));
    color:#fff;
    box-shadow:0 10px 22px color-mix(in srgb, var(--report-color, var(--report-brand)) 18%, transparent);
}
.rep-cta:hover { transform:translateY(-1px); box-shadow:0 14px 28px color-mix(in srgb, var(--report-color, var(--report-brand)) 24%, transparent); }

/* Category badge */
.cat-badge {
    font-size:.65rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase;
    padding:3px 9px; border-radius:20px; white-space:nowrap;
    background:var(--report-color-soft, var(--report-soft));
    color:var(--report-color-strong, var(--report-brand-dark));
}
.section-kicker {
    display:flex; align-items:flex-start; gap:12px; margin-bottom:20px;
}
.section-kicker-bar {
    width:4px; height:34px; flex-shrink:0;
    background:linear-gradient(180deg,var(--report-brand),var(--report-accent));
    border-radius:999px;
}
.section-kicker h2 {
    color:#0F172A;
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
                    <span class="report-chip"><i class="fas fa-folder-open text-xs"></i> 4 reportes activos</span>
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
