<!-- app/views/habitaciones/historial.php - Historial Completo de Reservaciones -->
<style>
:root {
    --hotel-purple: #8B5CF6;
    --hotel-purple-dark: #7C3AED;
}

.vista-historial { 
    opacity: 0; 
    transition: opacity 0.4s ease;
    min-height: 100vh;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}
.vista-historial.loaded { opacity: 1; }

/* Animación de entrada */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.reservacion-card {
    animation: fadeInUp 0.5s ease-out;
    animation-fill-mode: both;
}

.reservacion-card:nth-child(1) { animation-delay: 0.05s; }
.reservacion-card:nth-child(2) { animation-delay: 0.1s; }
.reservacion-card:nth-child(3) { animation-delay: 0.15s; }
.reservacion-card:nth-child(4) { animation-delay: 0.2s; }
.reservacion-card:nth-child(5) { animation-delay: 0.25s; }

.info-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.12);
}

.btn-modern {
    border: none;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.2);
}
</style>

<style id="room-history-redesign">
.vista-historial {
    --history-ink: #2d302f;
    --history-muted: #66716c;
    --history-line: rgba(64, 73, 68, 0.12);
    --history-paper: rgba(255, 255, 255, 0.9);
    --history-brand: color-mix(in srgb, var(--brand-primary, #765438) 28%, #65513d);
    --history-gold: color-mix(in srgb, var(--brand-accent, #b58b4a) 58%, #b58b4a);
    --history-sage: #7f987d;
    --history-sky: #7c9bb3;
    --history-clay: #c47f67;
    --history-olive: #8d936b;
    min-height: 100dvh;
    color: var(--history-ink);
    background:
        radial-gradient(circle at 7% 7%, rgba(181, 139, 74, 0.16), transparent 28rem),
        radial-gradient(circle at 92% 9%, rgba(124, 155, 179, 0.2), transparent 30rem),
        radial-gradient(circle at 76% 84%, rgba(127, 152, 125, 0.17), transparent 32rem),
        linear-gradient(135deg, #fbf7ef 0%, #f5f2ea 44%, #eef3f0 100%) !important;
}

.vista-historial::before {
    content: "";
    position: fixed;
    inset: 0;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(45, 48, 47, 0.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(45, 48, 47, 0.03) 1px, transparent 1px);
    background-size: 34px 34px;
    mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.62), transparent 78%);
}

.vista-historial > * {
    position: relative;
    z-index: 1;
}

.history-topbar {
    border-bottom: 1px solid rgba(70, 78, 72, 0.11) !important;
    background: rgba(255, 255, 255, 0.74) !important;
    box-shadow: 0 18px 45px rgba(57, 49, 37, 0.1) !important;
    backdrop-filter: blur(16px);
}

.history-header-inner,
.history-shell {
    max-width: 1120px;
}

.history-back-link {
    width: 2.8rem;
    height: 2.8rem;
    display: inline-grid;
    place-items: center;
    border: 1px solid rgba(118, 84, 56, 0.14);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.72);
    color: var(--history-brand) !important;
    box-shadow: 0 12px 26px rgba(59, 46, 31, 0.08);
}

.history-back-link:hover {
    transform: translateY(-1px) !important;
    color: var(--history-ink) !important;
}

.history-title h1 {
    color: var(--history-ink) !important;
    line-height: 1.05;
    text-wrap: balance;
}

.history-title p {
    color: var(--history-muted) !important;
}

.history-title p i {
    color: var(--history-gold) !important;
}

.history-stats-grid {
    gap: 1rem !important;
}

.history-stat-card {
    --stat-accent: var(--history-gold);
    position: relative;
    overflow: hidden;
    min-height: 8.5rem;
    border: 1px solid rgba(70, 78, 72, 0.12);
    background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.94), rgba(249, 247, 241, 0.78)) !important;
    color: var(--history-ink) !important;
    box-shadow: 0 18px 42px rgba(57, 49, 37, 0.1) !important;
}

.history-stat-card::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 6px;
    background: var(--stat-accent);
}

.history-stat-card::after {
    content: "";
    position: absolute;
    right: 0.75rem;
    top: 0.75rem;
    width: 4.75rem;
    height: 4.75rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--stat-accent) 16%, transparent);
}

.history-stat-card > * {
    position: relative;
    z-index: 1;
}

.history-stat-card i {
    color: var(--stat-accent);
    opacity: 1 !important;
}

.history-stat-card span {
    background: color-mix(in srgb, var(--stat-accent) 13%, white) !important;
    color: color-mix(in srgb, var(--stat-accent) 70%, #343a37) !important;
}

.history-stat-card .text-3xl,
.history-stat-card .text-2xl {
    color: var(--history-ink);
    font-variant-numeric: tabular-nums;
}

.history-stat-card .text-xs {
    color: var(--history-muted);
    opacity: 1 !important;
}

.history-stat-total { --stat-accent: var(--history-gold); }
.history-stat-nights { --stat-accent: var(--history-sky); }
.history-stat-revenue { --stat-accent: var(--history-sage); }
.history-stat-guests { --stat-accent: var(--history-clay); }

.history-filter-card,
.history-empty-card {
    border: 1px solid rgba(70, 78, 72, 0.12);
    background: rgba(255, 255, 255, 0.82) !important;
    box-shadow: 0 18px 42px rgba(57, 49, 37, 0.09) !important;
    backdrop-filter: blur(14px);
}

.history-filter-card i {
    color: var(--history-sky) !important;
}

.history-filter-card select {
    min-width: 11rem;
    border: 1px solid rgba(71, 82, 76, 0.16) !important;
    background: rgba(255, 255, 255, 0.88) !important;
    color: var(--history-ink);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
}

.history-filter-card select:focus {
    border-color: color-mix(in srgb, var(--history-brand) 56%, white) !important;
    box-shadow: 0 0 0 4px rgba(181, 139, 74, 0.16) !important;
}

.history-list {
    position: relative;
}

.info-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(70, 78, 72, 0.12);
    background: var(--history-paper) !important;
    border-radius: 1.15rem !important;
    box-shadow: 0 16px 38px rgba(57, 49, 37, 0.09) !important;
    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
}

.info-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
    background: var(--history-gold);
}

.info-card:hover {
    transform: translateY(-2px);
    border-color: rgba(181, 139, 74, 0.28);
    box-shadow: 0 22px 46px rgba(57, 49, 37, 0.14) !important;
}

.history-reservation-row {
    align-items: stretch;
}

.history-index-badge {
    flex: 0 0 auto;
    background: color-mix(in srgb, var(--history-gold) 14%, white) !important;
    color: color-mix(in srgb, var(--history-brand) 72%, #2d302f) !important;
    border: 1px solid rgba(181, 139, 74, 0.2);
}

.history-date-grid > div {
    border: 1px solid rgba(70, 78, 72, 0.09);
    background: rgba(248, 247, 242, 0.78) !important;
}

.history-date-in i { color: var(--history-sage) !important; }
.history-date-out i { color: var(--history-clay) !important; }

.history-pill,
.history-extra span,
.history-card-badges span {
    border: 1px solid rgba(70, 78, 72, 0.08);
    background: rgba(248, 247, 242, 0.82) !important;
    color: #55605b !important;
}

.history-pill i,
.history-extra i,
.history-card-badges i {
    color: var(--pill-accent, var(--history-sky)) !important;
}

.history-pill-money { --pill-accent: var(--history-sage); }
.history-pill-place { --pill-accent: var(--history-clay); }
.history-pill-phone { --pill-accent: var(--history-gold); }
.history-pill-time { --pill-accent: var(--history-olive); }
.history-pill-nights { --pill-accent: var(--history-sky); }

.history-card-badges .history-badge-recent {
    --pill-accent: var(--history-sage);
    background: rgba(127, 152, 125, 0.12) !important;
    color: #4c634b !important;
}

.history-card-badges .history-badge-group {
    --pill-accent: var(--history-sky);
    background: rgba(124, 155, 179, 0.13) !important;
    color: #4e6474 !important;
}

.history-extra .history-folio { --pill-accent: var(--history-gold); }
.history-extra .history-vehicle { --pill-accent: var(--history-muted); }
.history-extra .history-note { --pill-accent: var(--history-clay); }

.btn-modern {
    border: none;
    font-weight: 700;
    transition: transform 0.22s ease, box-shadow 0.22s ease, background 0.22s ease;
    padding: 0.62rem 0.95rem;
    border-radius: 0.85rem;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #344139, var(--history-brand)) !important;
    color: white !important;
    box-shadow: 0 14px 28px rgba(57, 49, 37, 0.18);
}

.btn-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 18px 34px rgba(57, 49, 37, 0.24);
}

.history-empty-card .inline-flex {
    background: rgba(181, 139, 74, 0.12) !important;
}

.history-empty-card i {
    color: var(--history-gold) !important;
}

@media (max-width: 720px) {
    .history-topbar {
        position: relative !important;
    }

    .history-header-inner {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }

    .history-header-inner .flex.items-center.justify-between,
    .history-reservation-row {
        display: block !important;
    }

    .history-back-link {
        margin-bottom: 0.9rem;
    }

    .history-title h1 {
        font-size: 1.7rem !important;
    }

    .history-filter-card .flex {
        align-items: stretch !important;
        flex-wrap: wrap;
    }

    .history-filter-card select {
        width: 100%;
    }

    .btn-modern {
        width: 100%;
        justify-content: center;
        margin-top: 1rem;
    }
}
</style>

<div class="vista-historial">
    <!-- Header -->
    <div class="history-topbar bg-white shadow-lg border-b sticky top-0 z-30">
        <div class="history-header-inner container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <?php $back_arrow_href = back_url('habitaciones/' . $habitacion['id']); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                    <a href="<?= back_url('habitaciones/' . $habitacion['id']) ?>"
                       class="history-back-link text-purple-600 hover:text-purple-800 transition-all hover:scale-110 ms-back-legacy">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div class="history-title">
                        <h1 class="text-2xl font-bold text-gray-800">
                            Historial Completo - Habitación <?= htmlspecialchars($habitacion['numero']) ?>
                        </h1>
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-history mr-1 text-purple-500"></i>
                            <?= count($historial) ?> reservaciones en total
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="history-shell container mx-auto px-4 py-6 max-w-5xl">
        
        <!-- Estadísticas Rápidas -->
        <div class="history-stats-grid grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <?php 
            $total_reservaciones = count($historial);
            $total_noches = 0;
            $total_ingresos = 0;
            $huespedes_unicos = [];
            
            foreach ($historial as $reservacion) {
                $fecha_entrada = new DateTime($reservacion['fecha_entrada']);
                $fecha_salida = new DateTime($reservacion['fecha_salida']);
                $total_noches += $fecha_entrada->diff($fecha_salida)->days;
                
                if (isset($reservacion['precio_total']) && $reservacion['precio_total'] > 0) {
                    $total_ingresos += $reservacion['precio_total'];
                } elseif (isset($reservacion['total']) && $reservacion['total'] > 0) {
                    $total_ingresos += $reservacion['total'];
                }
                
                if (!empty($reservacion['huesped_id'])) {
                    $huespedes_unicos[$reservacion['huesped_id']] = true;
                }
            }
            ?>
            
            <div class="history-stat-card history-stat-total bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-calendar-check text-2xl opacity-80"></i>
                    <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded-full">Total</span>
                </div>
                <div class="text-3xl font-bold"><?= $total_reservaciones ?></div>
                <div class="text-xs opacity-90 mt-1">Reservaciones</div>
            </div>
            
            <div class="history-stat-card history-stat-nights bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-moon text-2xl opacity-80"></i>
                    <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded-full">Noches</span>
                </div>
                <div class="text-3xl font-bold"><?= $total_noches ?></div>
                <div class="text-xs opacity-90 mt-1">Total de noches</div>
            </div>
            
            <div class="history-stat-card history-stat-revenue bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-dollar-sign text-2xl opacity-80"></i>
                    <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded-full">Ingresos</span>
                </div>
                <div class="text-2xl font-bold"><?= format_money($total_ingresos) ?></div>
                <div class="text-xs opacity-90 mt-1">Total generado</div>
            </div>
            
            <div class="history-stat-card history-stat-guests bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-users text-2xl opacity-80"></i>
                    <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded-full">Únicos</span>
                </div>
                <div class="text-3xl font-bold"><?= count($huespedes_unicos) ?></div>
                <div class="text-xs opacity-90 mt-1">Huéspedes únicos</div>
            </div>
        </div>

        <!-- Filtros (Opcional - puedes expandir esto) -->
        <div class="history-filter-card bg-white rounded-xl shadow-md p-4 mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-filter text-purple-600"></i>
                <span class="font-semibold text-gray-700">Filtros</span>
                <div class="flex-1"></div>
                <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent" onchange="filtrarPorAno(this.value)">
                    <option value="">Todos los años</option>
                    <?php 
                    $anos = [];
                    foreach ($historial as $reservacion) {
                        $ano = date('Y', strtotime($reservacion['fecha_salida']));
                        $anos[$ano] = true;
                    }
                    krsort($anos);
                    foreach (array_keys($anos) as $ano): ?>
                    <option value="<?= $ano ?>"><?= $ano ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Lista de Reservaciones -->
        <div class="history-list space-y-3">
            <?php 
            $hoy = new DateTime('today');
            foreach ($historial as $index => $reservacion):
                $fecha_entrada = new DateTime($reservacion['fecha_entrada']);
                $fecha_salida = new DateTime($reservacion['fecha_salida']);
                $duracion = $fecha_entrada->diff($fecha_salida)->days;

                // diff()->days es absoluto: hay que reponer el signo para no tratar una reserva futura como pasada.
                $ent_solo = (new DateTime($reservacion['fecha_entrada']))->setTime(0, 0, 0);
                $sal_solo = (new DateTime($reservacion['fecha_salida']))->setTime(0, 0, 0);
                $es_futura = $ent_solo > $hoy;
                $en_estadia = !$es_futura && $sal_solo > $hoy;
                $dias_hasta_entrada = (int)$hoy->diff($ent_solo)->days;
                $dias_desde_salida = (int)$sal_solo->diff($hoy)->days * ($sal_solo > $hoy ? -1 : 1);

                $es_reciente = !$es_futura && $dias_desde_salida <= 7;
                
                $total_pagado = null;
                if (isset($reservacion['precio_total']) && $reservacion['precio_total'] > 0) {
                    $total_pagado = $reservacion['precio_total'];
                } elseif (isset($reservacion['total']) && $reservacion['total'] > 0) {
                    $total_pagado = $reservacion['total'];
                }
                
                $procedencia = '';
                if (!empty($reservacion['procedencia'])) {
                    $procedencia = $reservacion['procedencia'];
                } elseif (!empty($reservacion['estado_procedencia'])) {
                    $procedencia = $reservacion['estado_procedencia'];
                }
            ?>
            <div class="info-card p-4 reservacion-card" data-ano="<?= date('Y', strtotime($reservacion['fecha_salida'])) ?>">
                <div class="history-reservation-row flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-start gap-2 mb-2">
                            <div class="history-index-badge bg-purple-100 text-purple-700 rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm">
                                <?= $index + 1 ?>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-lg">
                                    <?= htmlspecialchars($reservacion['nombre_huesped']) ?>
                                </h4>
                                <div class="history-card-badges flex flex-wrap gap-2 mt-1">
                                    <?php if ($es_reciente): ?>
                                    <span class="history-badge-recent inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800 font-bold">
                                        <i class="fas fa-star mr-1"></i>
                                        Reciente
                                    </span>
                                    <?php endif; ?>
                                    <?php if (isset($reservacion['total_habitaciones']) && $reservacion['total_habitaciones'] > 1): ?>
                                    <span class="history-badge-group inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800 font-bold">
                                        <i class="fas fa-users mr-1"></i>
                                        Grupo (<?= $reservacion['total_habitaciones'] ?>)
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="history-date-grid grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div class="history-date-in flex items-center gap-2 bg-green-50 rounded-lg p-2">
                                <i class="fas fa-sign-in-alt text-green-600"></i>
                                <div>
                                    <div class="text-xs text-gray-600 font-semibold">Check-in</div>
                                    <div class="text-sm font-bold text-gray-900"><?= $fecha_entrada->format('d/m/Y') ?></div>
                                </div>
                            </div>
                            <div class="history-date-out flex items-center gap-2 bg-red-50 rounded-lg p-2">
                                <i class="fas fa-sign-out-alt text-red-600"></i>
                                <div>
                                    <div class="text-xs text-gray-600 font-semibold">Check-out</div>
                                    <div class="text-sm font-bold text-gray-900"><?= $fecha_salida->format('d/m/Y') ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <div class="history-pill history-pill-nights flex items-center gap-1 text-xs text-gray-600 bg-blue-50 px-2 py-1 rounded">
                                <i class="fas fa-moon text-blue-600"></i>
                                <span class="font-semibold">
                                    <?= $duracion ?> <?= $duracion == 1 ? 'noche' : 'noches' ?>
                                </span>
                            </div>
                            
                            <div class="history-pill history-pill-time flex items-center gap-1 text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                <i class="fas fa-calendar-alt text-gray-500"></i>
                                <span class="font-semibold">
                                    <?php if ($es_futura): ?>
                                        <?= $dias_hasta_entrada == 1 ? 'Mañana' : 'En ' . $dias_hasta_entrada . ' días' ?>
                                    <?php elseif ($en_estadia): ?>
                                        En estadía
                                    <?php elseif ($dias_desde_salida == 0): ?>
                                        Salió hoy
                                    <?php elseif ($dias_desde_salida == 1): ?>
                                        Ayer
                                    <?php elseif ($dias_desde_salida < 7): ?>
                                        Hace <?= $dias_desde_salida ?> días
                                    <?php elseif ($dias_desde_salida < 30): ?>
                                        Hace <?= floor($dias_desde_salida / 7) ?> <?= floor($dias_desde_salida / 7) == 1 ? 'sem.' : 'sem.' ?>
                                    <?php elseif ($dias_desde_salida < 365): ?>
                                        Hace <?= floor($dias_desde_salida / 30) ?> <?= floor($dias_desde_salida / 30) == 1 ? 'mes' : 'meses' ?>
                                    <?php else: ?>
                                        Hace <?= floor($dias_desde_salida / 365) ?> <?= floor($dias_desde_salida / 365) == 1 ? 'año' : 'años' ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if ($total_pagado): ?>
                            <div class="history-pill history-pill-money flex items-center gap-1 text-xs text-gray-700 bg-emerald-50 px-2 py-1 rounded">
                                <i class="fas fa-dollar-sign text-emerald-600"></i>
                                <span class="font-bold"><?= format_money($total_pagado) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($procedencia)): ?>
                            <div class="history-pill history-pill-place flex items-center gap-1 text-xs text-gray-600 bg-orange-50 px-2 py-1 rounded">
                                <i class="fas fa-map-marker-alt text-orange-500"></i>
                                <span class="font-semibold"><?= htmlspecialchars($procedencia) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($reservacion['telefono'])): ?>
                            <div class="history-pill history-pill-phone flex items-center gap-1 text-xs text-gray-600 bg-purple-50 px-2 py-1 rounded">
                                <i class="fas fa-phone text-purple-500"></i>
                                <span class="font-semibold"><?= htmlspecialchars($reservacion['telefono']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($reservacion['observaciones']) || !empty($reservacion['vehiculos']) || !empty($reservacion['folio'])): ?>
                        <div class="history-extra mt-2 flex flex-wrap gap-1.5">
                            <?php if (!empty($reservacion['folio'])): ?>
                            <span class="history-folio inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700 font-bold">
                                <i class="fas fa-hashtag mr-1"></i>
                                <?= htmlspecialchars($reservacion['folio']) ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php // El chip cuenta desde la columna legacy huespedes.vehiculo_marca,
                            // no desde huesped_vehiculos, pero sigue siendo dato de estacionamiento:
                            // sin el bloque 'vehiculos' tampoco se pinta. ?>
                            <?php if ((!function_exists('hotel_parking_visible') || hotel_parking_visible())
                                && !empty($reservacion['vehiculos']) && $reservacion['vehiculos'] > 0): ?>
                            <span class="history-vehicle inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-200 text-gray-700 font-bold">
                                <i class="fas fa-car mr-1"></i>
                                <?= $reservacion['vehiculos'] > 1 ? $reservacion['vehiculos'] . ' veh.' : '1 veh.' ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($reservacion['observaciones'])): ?>
                            <span class="history-note inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700 font-bold"
                                  title="<?= htmlspecialchars($reservacion['observaciones']) ?>">
                                <i class="fas fa-sticky-note mr-1"></i>
                                Con obs.
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <a href="<?= url('/reservaciones/ver/' . $reservacion['id']) ?>"  
                           class="btn-modern bg-purple-600 text-white hover:bg-purple-700">
                            <i class="fas fa-eye"></i>
                            Ver
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($historial)): ?>
        <div class="history-empty-card bg-white rounded-xl shadow-md p-12 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-purple-100 rounded-full mb-4">
                <i class="fas fa-inbox text-3xl text-purple-400"></i>
            </div>
            <p class="text-gray-700 font-bold text-lg">Sin historial de reservaciones</p>
            <p class="text-sm text-gray-500 mt-2">Esta habitación no tiene reservaciones registradas</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.vista-historial')?.classList.add('loaded');
});

function filtrarPorAno(ano) {
    const cards = document.querySelectorAll('.reservacion-card');
    cards.forEach(card => {
        if (ano === '' || card.dataset.ano === ano) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
