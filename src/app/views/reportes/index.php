<?php ?>

<style>
/* ══════════════════════════════════════════
   LOS CEDROS · Centro de Reportes
   Sage green / warm gold / cream palette
   ══════════════════════════════════════════ */
:root {
    --lc-green:       #5C7A4E;
    --lc-green-dark:  #4A6340;
    --lc-green-deep:  #3D5234;
    --lc-gold:        #C8A96A;
    --lc-gold-dark:   #B8994A;
    --lc-cream:       #F7F4EE;
}

/* Page fade-in */
.reportes-view { opacity:0; transition: opacity .35s ease; }
.reportes-view.loaded { opacity:1; }

/* Background */
.rep-bg {
    background: linear-gradient(145deg,#EFF4EC 0%,#E8EEE3 50%,#F4F1EC 100%);
    min-height:100vh;
}

/* ── Hero header ─────────────────────────── */
.rep-hero {
    background: linear-gradient(135deg, #3D5234 0%, #4A6340 50%, #5C7A4E 100%);
    position: relative; overflow: hidden;
}
.rep-hero::before {
    content:'';
    position:absolute; top:-60px; right:-60px;
    width:280px; height:280px; border-radius:50%;
    background: rgba(200,169,106,.08); pointer-events:none;
}
.rep-hero::after {
    content:'';
    position:absolute; bottom:-80px; left:-40px;
    width:220px; height:220px; border-radius:50%;
    background: rgba(255,255,255,.04); pointer-events:none;
}
.gold-tag {
    display:inline-flex; align-items:center; gap:5px;
    background:rgba(200,169,106,.18); border:1px solid rgba(200,169,106,.35);
    color:var(--lc-gold-dark); border-radius:20px;
    padding:3px 10px; font-size:.7rem; font-weight:700; letter-spacing:.04em;
}

/* ── Stat widgets ────────────────────────── */
.rep-stat {
    background:#fff; border-radius:14px; border:1px solid #DDE8D5;
    padding:20px; overflow:hidden; position:relative;
    transition: transform .25s, box-shadow .25s;
    animation: slideUp .5s ease both;
}
.rep-stat:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(92,122,78,.12); }
.rep-stat-icon {
    width:44px; height:44px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; font-size:18px;
}
.rep-stat::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:2px;
    opacity:0; transition:opacity .25s;
    background: var(--accent, #5C7A4E);
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
    background:#fff; border-radius:16px;
    border:1px solid #DDE8D5;
    padding:0; overflow:hidden; position:relative;
    transition: transform .3s, box-shadow .3s;
    animation: slideUp .5s ease both;
    display:flex; flex-direction:column;
}
.rep-card:hover { transform:translateY(-5px); box-shadow:0 16px 36px rgba(61,82,52,.14); }

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
    background: var(--card-gradient, linear-gradient(90deg,#5C7A4E,#7A9B6A));
}

.card-body { padding:20px; flex:1; display:flex; flex-direction:column; }

/* Icon wrapper with animation */
.rep-icon-wrap {
    width:56px; height:56px; border-radius:14px;
    display:flex; align-items:center; justify-content:center; font-size:22px;
    transition: transform .3s, box-shadow .3s; margin-bottom:14px;
    box-shadow:0 4px 12px rgba(0,0,0,.12);
}
.rep-card:hover .rep-icon-wrap {
    transform: scale(1.08) rotate(4deg);
    box-shadow:0 8px 20px rgba(0,0,0,.18);
}

/* Feature list */
.feat-list { list-style:none; padding:0; margin:0 0 16px; flex:1; }
.feat-list li {
    display:flex; align-items:center; gap:7px;
    font-size:.75rem; color:#6B7280; padding:3px 0;
}
.feat-list li i { color:#7A9B6A; font-size:.65rem; flex-shrink:0; }

/* CTA buttons */
.rep-cta {
    display:flex; align-items:center; justify-content:center; gap:8px;
    width:100%; padding:10px 16px; border-radius:10px;
    font-size:.82rem; font-weight:700; text-decoration:none;
    transition: box-shadow .2s, transform .2s; border:none;
    position:relative; z-index:2;
}
.rep-cta:hover { transform:translateY(-1px); }

/* Category badge */
.cat-badge {
    font-size:.65rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase;
    padding:3px 9px; border-radius:20px; white-space:nowrap;
}

/* ── Scrollbar ───────────────────────────── */
.lc-scroll::-webkit-scrollbar { width:4px; }
.lc-scroll::-webkit-scrollbar-track { background:#EAF0E5; }
.lc-scroll::-webkit-scrollbar-thumb { background:#A8C4A0; border-radius:4px; }
</style>

<!-- ════════════════════════════════ REPORTES PAGE ════════ -->
<div class="reportes-view rep-bg">

    <!-- Hero Header -->
    <div class="rep-hero">
        <div class="container mx-auto px-5 sm:px-7 py-6 relative z-10">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div style="background:rgba(255,255,255,.12);width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-chart-line text-white text-lg"></i>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white font-playfair">
                            Centro de Reportes y Análisis
                        </h1>
                    </div>
                    <p class="text-sm text-white/60 ml-14">Insights detallados para la toma de decisiones estratégicas de tu hotel</p>
                </div>
                <div class="flex flex-wrap gap-2 ml-14 lg:ml-0">
                    <span class="gold-tag"><i class="fas fa-crown text-xs"></i> Gerencia</span>
                    <span class="gold-tag"><i class="fas fa-calendar-day text-xs"></i> Tiempo real</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-5 sm:px-7 py-6 max-w-7xl">

        <!-- Section title -->
        <div class="flex items-center gap-3 mb-5">
            <div style="width:4px;height:22px;background:linear-gradient(180deg,var(--lc-green),var(--lc-gold));border-radius:2px;"></div>
            <h2 class="text-base font-bold text-[#3D5234]">Reportes Disponibles</h2>
        </div>

        <!-- Report Cards Grid -->
        <div class="report-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

            <!-- 1: Ingresos vs Gastos -->
            <div class="rep-card">
                <div class="card-accent" style="--card-gradient:linear-gradient(90deg,#2563EB,#3B82F6)"></div>
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="rep-icon-wrap" style="background:linear-gradient(135deg,#2563EB,#3B82F6);">
                            <i class="fas fa-balance-scale text-white"></i>
                        </div>
                        <span class="cat-badge" style="background:#DBEAFE;color:#1D4ED8;">Financiero</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1.5">Análisis de Ingresos vs Gastos</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">Visualice y compare ingresos y gastos del hotel por período de tiempo.</p>
                    <ul class="feat-list">
                        <li><i class="fas fa-check-circle"></i>Resumen por categorías</li>
                        <li><i class="fas fa-check-circle"></i>Evolución diaria</li>
                        <li><i class="fas fa-check-circle"></i>Cálculo de utilidades</li>
                    </ul>
                    <a href="<?= url('reportes/ingresos-gastos') ?>" class="rep-cta" style="background:linear-gradient(135deg,#2563EB,#3B82F6);color:#fff;">
                        <i class="fas fa-chart-bar text-sm"></i> Generar Reporte
                    </a>
                </div>
            </div>

            <!-- 2: Procedencia Geográfica -->
            <div class="rep-card">
                <div class="card-accent" style="--card-gradient:linear-gradient(90deg,#059669,#10b981)"></div>
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="rep-icon-wrap" style="background:linear-gradient(135deg,#059669,#10b981);">
                            <i class="fas fa-map-marked-alt text-white"></i>
                        </div>
                        <span class="cat-badge" style="background:#D1FAE5;color:#065F46;">Geográfico</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1.5">Procedencia de Huéspedes</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">Analice de dónde provienen sus huéspedes para optimizar estrategias de marketing.</p>
                    <ul class="feat-list">
                        <li><i class="fas fa-check-circle"></i>Distribución por estados</li>
                        <li><i class="fas fa-check-circle"></i>Top de ciudades</li>
                        <li><i class="fas fa-check-circle"></i>Mapa de calor</li>
                    </ul>
                    <a href="<?= url('reportes/procedencia') ?>" class="rep-cta" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;">
                        <i class="fas fa-globe-americas text-sm"></i> Generar Reporte
                    </a>
                </div>
            </div>

            <!-- 3: Habitaciones Rentables -->
            <div class="rep-card">
                <div class="card-accent" style="--card-gradient:linear-gradient(90deg,#7C3AED,#8B5CF6)"></div>
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="rep-icon-wrap" style="background:linear-gradient(135deg,#7C3AED,#8B5CF6);">
                            <i class="fas fa-trophy text-white"></i>
                        </div>
                        <span class="cat-badge" style="background:#EDE9FE;color:#5B21B6;">Performance</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1.5">Habitaciones más Rentables</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">Identifique las habitaciones que generan mayores ingresos y optimice precios.</p>
                    <ul class="feat-list">
                        <li><i class="fas fa-check-circle"></i>Ranking por ingresos</li>
                        <li><i class="fas fa-check-circle"></i>Ocupación por tipo</li>
                        <li><i class="fas fa-check-circle"></i>Análisis de precios</li>
                    </ul>
                    <a href="<?= url('reportes/habitaciones-rentables') ?>" class="rep-cta" style="background:linear-gradient(135deg,#7C3AED,#8B5CF6);color:#fff;">
                        <i class="fas fa-medal text-sm"></i> Generar Reporte
                    </a>
                </div>
            </div>

            <!-- 4: Mantenimiento -->
            <div class="rep-card">
                <div class="card-accent" style="--card-gradient:linear-gradient(90deg,#D97706,#F59E0B)"></div>
                <div class="card-body">
                    <div class="flex items-start justify-between mb-3">
                        <div class="rep-icon-wrap" style="background:linear-gradient(135deg,#D97706,#F59E0B);">
                            <i class="fas fa-tools text-white"></i>
                        </div>
                        <span class="cat-badge" style="background:#FEF3C7;color:#92400E;">Operativo</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1.5">Mantenimiento de Habitaciones</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">Análisis detallado de mantenimientos: tipos, prioridades, costos y eficiencia operativa.</p>
                    <ul class="feat-list">
                        <li><i class="fas fa-check-circle"></i>Por tipo y prioridad</li>
                        <li><i class="fas fa-check-circle"></i>Costos y tiempos</li>
                        <li><i class="fas fa-check-circle"></i>Eficiencia del equipo</li>
                    </ul>
                    <a href="<?= url('reportes/mantenimiento') ?>" class="rep-cta" style="background:linear-gradient(135deg,#D97706,#F59E0B);color:#fff;">
                        <i class="fas fa-wrench text-sm"></i> Generar Reporte
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

function exportarTodos() {
    Swal.fire({
        title: 'Exportar Reportes',
        text: '¿En qué formato desea exportar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#5C7A4E',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-file-pdf mr-2"></i>PDF',
        cancelButtonText: '<i class="fas fa-file-excel mr-2"></i>Excel',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed || result.isDismissed) {
            Swal.fire({ icon:'info', title:'En desarrollo', text:'Esta función estará disponible próximamente', confirmButtonColor:'#5C7A4E' });
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
        confirmButtonColor: '#5C7A4E',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-2"></i>Programar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) Swal.fire({ icon:'success', title:'¡Programado!', text:'El reporte se enviará según lo configurado', confirmButtonColor:'#5C7A4E' });
    });
}

function crearReportePersonalizado() {
    Swal.fire({ title:'Reporte Personalizado', text:'Próximamente podrá diseñar sus propios reportes con métricas específicas', icon:'info', confirmButtonColor:'#5C7A4E', confirmButtonText:'Entendido' });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
