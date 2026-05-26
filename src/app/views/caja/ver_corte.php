<?php
/**
 * Vista Detalle de Corte de Caja
 * Los Cedros
 */

// Calcular totales del corte
$total_ingresos = floatval($corte['total_ingresos_efectivo'] ?? 0)
                + floatval($corte['total_ingresos_tarjeta'] ?? 0)
                + floatval($corte['total_ingresos_transferencia'] ?? 0);

$total_gastos   = floatval($corte['total_gastos_efectivo'] ?? 0)
                + floatval($corte['total_gastos_tarjeta'] ?? 0)
                + floatval($corte['total_gastos_transferencia'] ?? 0);

$balance_general     = $total_ingresos - $total_gastos;
$efectivo_en_caja    = floatval($corte['monto_inicial'] ?? 0)
                     + floatval($corte['total_ingresos_efectivo'] ?? 0)
                     - floatval($corte['total_gastos_efectivo'] ?? 0);

$diferencia          = floatval($corte['diferencia'] ?? 0);
$esta_cerrado        = ($corte['estado'] ?? '') === 'cerrado';

// Agrupar movimientos por categoría
$cats_ingreso = [];
$cats_gasto   = [];
foreach ($movimientos as $mov) {
    $cat   = $mov['categoria_nombre'] ?? 'Sin categoría';
    $icono = $mov['categoria_icono']  ?? 'fas fa-tag';
    $color = $mov['categoria_color']  ?? '#6B7280';
    $tipo  = $mov['tipo'];
    if ($tipo === 'ingreso') {
        if (!isset($cats_ingreso[$cat])) $cats_ingreso[$cat] = ['total' => 0, 'cantidad' => 0, 'icono' => $icono, 'color' => $color];
        $cats_ingreso[$cat]['total']    += floatval($mov['monto']);
        $cats_ingreso[$cat]['cantidad'] += 1;
    } else {
        if (!isset($cats_gasto[$cat])) $cats_gasto[$cat] = ['total' => 0, 'cantidad' => 0, 'icono' => $icono, 'color' => $color];
        $cats_gasto[$cat]['total']    += floatval($mov['monto']);
        $cats_gasto[$cat]['cantidad'] += 1;
    }
}
arsort($cats_ingreso);
arsort($cats_gasto);
?>

<style>
    :root {
        --lc-green:        #5C7A4E;
        --lc-green-dark:   #4A6340;
        --lc-green-deeper: #3D5234;
        --lc-green-light:  #7A9B6A;
        --lc-gold:         #C8A96A;
        --lc-gold-light:   #D9BF8A;
        --lc-cream:        #F7F4EE;
        --lc-cream-dark:   #EEE9DE;
    }

    .lc-bg { background: linear-gradient(135deg, #F0F4ED 0%, #E8F0E3 50%, #F5F2EC 100%); }

    /* Header card */
    .header-card {
        background: linear-gradient(135deg, #fff 0%, #FAFDF8 100%);
        border-left: 4px solid var(--lc-gold);
        position: relative; overflow: hidden;
    }
    .header-card::before {
        content: ''; position: absolute;
        top: -30px; right: -30px;
        width: 180px; height: 180px; border-radius: 50%;
        background: radial-gradient(circle, rgba(92,122,78,0.06) 0%, transparent 70%);
        pointer-events: none;
    }

    /* Stat cards */
    .stat-card {
        background: #fff; border-radius: 16px;
        transition: transform .25s, box-shadow .25s;
        position: relative; overflow: hidden;
    }
    .stat-card::after {
        content: ''; position: absolute;
        bottom: 0; left: 0; right: 0; height: 3px;
        opacity: 0; transition: opacity .25s;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(92,122,78,.12); }
    .stat-card:hover::after { opacity: 1; }
    .stat-card.card-inicial::after    { background: var(--lc-green-light); }
    .stat-card.card-ingresos::after   { background: #10b981; }
    .stat-card.card-gastos::after     { background: #ef4444; }
    .stat-card.card-balance::after    { background: var(--lc-gold); }

    /* Icon circles */
    .icon-circle {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }
    .icon-circle.green-ic   { background: rgba(92,122,78,.12);  color: var(--lc-green); }
    .icon-circle.emerald-ic { background: rgba(16,185,129,.12); color: #059669; }
    .icon-circle.red-ic     { background: rgba(239,68,68,.12);  color: #dc2626; }
    .icon-circle.gold-ic    { background: rgba(200,169,106,.15);color: #92400e; }
    .icon-circle.blue-ic    { background: rgba(59,130,246,.12); color: #1d4ed8; }

    /* Efectivo en caja card */
    .card-efectivo {
        background: linear-gradient(135deg, var(--lc-green) 0%, var(--lc-green-deeper) 100%);
        border-radius: 16px; color: white;
        position: relative; overflow: hidden;
        transition: transform .25s, box-shadow .25s;
    }
    .card-efectivo::before {
        content: ''; position: absolute;
        top: -20px; right: -20px;
        width: 110px; height: 110px; border-radius: 50%;
        background: rgba(255,255,255,.07);
    }
    .card-efectivo::after {
        content: ''; position: absolute;
        bottom: -30px; left: -10px;
        width: 90px; height: 90px; border-radius: 50%;
        background: rgba(255,255,255,.05);
    }
    .card-efectivo:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(61,82,52,.25); }

    /* Método de pago cards */
    .metodo-card {
        border-radius: 12px; border: 1.5px solid;
        transition: box-shadow .2s, transform .2s;
    }
    .metodo-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
    .metodo-card.mc-efectivo  { border-color: #A7C89A; background: linear-gradient(135deg,#F0FAF0,#E8F5E3); }
    .metodo-card.mc-tarjeta   { border-color: #A8C4E0; background: linear-gradient(135deg,#F0F6FF,#E8F0FA); }
    .metodo-card.mc-transfer  { border-color: #B8A8D8; background: linear-gradient(135deg,#F5F0FF,#EDE8F8); }

    /* Section headers */
    .section-header {
        padding: 16px 20px;
        display: flex; align-items: center; gap: 10px;
        font-weight: 600; color: white; font-size: .95rem;
    }
    .section-header.sh-ingresos { background: linear-gradient(135deg,#10b981,#059669); }
    .section-header.sh-gastos   { background: linear-gradient(135deg,#ef4444,#dc2626); }
    .section-header.sh-movs     { background: linear-gradient(135deg,var(--lc-green),var(--lc-green-dark)); }
    .section-header.sh-denom    { background: linear-gradient(135deg,#C8A96A,#a87c3a); }
    .section-header.sh-info     { background: linear-gradient(135deg,#3b82f6,#1d4ed8); }

    /* Categoría items */
    .cat-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 14px; border-radius: 10px;
        background: #FAFDF8; border: 1px solid #EBF3E7;
        transition: background .15s, border-color .15s;
    }
    .cat-item:hover { background: #F0FAF0; border-color: #C8DCC0; }

    /* Movimiento items */
    .mov-item {
        padding: 14px 16px; border-bottom: 1px solid #F0F0EE;
        transition: background .15s;
    }
    .mov-item:hover { background: #FAFDF8; }
    .mov-item:last-child { border-bottom: none; }

    /* Badges */
    .badge-ingreso { background:rgba(16,185,129,.1);  color:#065f46; border:1px solid rgba(16,185,129,.25); }
    .badge-gasto   { background:rgba(239,68,68,.1);   color:#991b1b; border:1px solid rgba(239,68,68,.25); }
    .badge-ingreso-manual { background:rgba(245,158,11,.12); color:#92400e; border:1px solid rgba(245,158,11,.3); }
    .badge-ingreso-renta  { background:rgba(92,122,78,.1);   color:#3D5234; border:1px solid rgba(92,122,78,.25); }
    .badge-cerrado { background:rgba(92,122,78,.1);   color:#3D5234; border:1px solid rgba(92,122,78,.25); }
    .badge-abierto { background:rgba(251,191,36,.15); color:#92400e; border:1px solid rgba(251,191,36,.35); }

    /* Balance bar */
    .balance-bar {
        background: linear-gradient(135deg,#FAFDF8,#F0F4ED);
        border-radius: 12px; border: 1px solid #DDE8D5;
        padding: 18px 22px;
    }

    /* Diferencia card */
    .diferencia-positiva { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1.5px solid #86efac; }
    .diferencia-negativa { background:linear-gradient(135deg,#fff1f2,#ffe4e6); border:1.5px solid #fca5a5; }
    .diferencia-cero     { background:linear-gradient(135deg,#f8fafc,#f1f5f9); border:1.5px solid #cbd5e1; }

    /* Denominaciones table */
    .denom-row { padding: 8px 14px; border-bottom: 1px solid #EBF3E7; }
    .denom-row:hover { background: #F5FAF2; }
    .denom-row:last-child { border-bottom: none; }

    /* Scrollbar */
    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background:#F0F4ED; border-radius:10px; }
    .custom-scroll::-webkit-scrollbar-thumb { background:#A8C4A0; border-radius:10px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background:var(--lc-green); }

    /* Print */
    @media print {
        .no-print { display: none !important; }
        .lc-bg { background: white !important; }
        .stat-card, .metodo-card, .bg-white { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
    }
</style>

<div class="lc-bg min-h-screen p-4 md:p-6">

    <!-- ══════════════════════════════════════════════ HEADER ══ -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="header-card rounded-2xl shadow-lg p-5 md:p-7">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">

                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:rgba(92,122,78,.12)">
                            <i class="fas fa-file-invoice-dollar text-[#5C7A4E]"></i>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-[#3D5234] font-playfair tracking-tight">
                            Corte #<?= $corte['id'] ?>
                        </h1>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $esta_cerrado ? 'badge-cerrado' : 'badge-abierto' ?>">
                            <i class="fas fa-circle mr-1.5 text-[9px]"></i>
                            <?= $esta_cerrado ? 'Cerrado' : 'Abierto' ?>
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 ml-12">
                        <span class="flex items-center gap-1.5 bg-[#F0F4ED] px-2.5 py-1 rounded-full">
                            <i class="fas fa-store text-[#5C7A4E] text-xs"></i>
                            <?= htmlspecialchars($corte['caja_nombre'] ?? '') ?>
                        </span>
                        <span class="flex items-center gap-1.5 bg-[#F0F4ED] px-2.5 py-1 rounded-full">
                            <i class="fas fa-calendar-alt text-[#5C7A4E] text-xs"></i>
                            <?= date('d/m/Y H:i', strtotime($corte['fecha_apertura'])) ?>
                        </span>
                        <?php if ($esta_cerrado && $corte['fecha_cierre']): ?>
                        <span class="flex items-center gap-1.5 bg-[#F0F4ED] px-2.5 py-1 rounded-full">
                            <i class="fas fa-calendar-check text-[#5C7A4E] text-xs"></i>
                            Cerrado: <?= date('d/m/Y H:i', strtotime($corte['fecha_cierre'])) ?>
                        </span>
                        <?php endif; ?>
                        <span class="flex items-center gap-1.5 bg-[#F0F4ED] px-2.5 py-1 rounded-full">
                            <i class="fas fa-user text-[#5C7A4E] text-xs"></i>
                            <?= htmlspecialchars($corte['usuario_apertura'] ?? '') ?>
                        </span>
                        <?php if ($esta_cerrado && $corte['usuario_cierre']): ?>
                        <span class="flex items-center gap-1.5 bg-[#F0F4ED] px-2.5 py-1 rounded-full">
                            <i class="fas fa-user-check text-[#5C7A4E] text-xs"></i>
                            Cerró: <?= htmlspecialchars($corte['usuario_cierre']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex flex-wrap gap-2 no-print">
                    <a href="<?= url('caja/historial') ?>"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-[#D4E6CA] text-[#4A6340] bg-[#F5FAF2] hover:bg-[#EAF3E4] transition">
                        <i class="fas fa-arrow-left text-xs"></i> Volver al historial
                    </a>
                    <a href="<?= url('caja/descargar-pdf/' . $corte['id']) ?>"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white transition"
                       style="background:linear-gradient(135deg,var(--lc-gold),#B8994A);">
                        <i class="fas fa-file-pdf text-xs"></i> Descargar PDF
                    </a>
                    <button onclick="window.print()"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-gray-200 text-gray-600 bg-white hover:bg-gray-50 transition">
                        <i class="fas fa-print text-xs"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════ STAT CARDS ══ -->
    <div class="max-w-7xl mx-auto mb-5 grid grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Monto Inicial -->
        <div class="stat-card card-inicial shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="icon-circle green-ic"><i class="fas fa-wallet"></i></div>
                <span class="text-xs text-gray-400 font-medium">Apertura</span>
            </div>
            <p class="text-2xl font-bold text-[#3D5234]">$<?= number_format($corte['monto_inicial'] ?? 0, 2) ?></p>
            <p class="text-xs text-gray-400 mt-1">Monto inicial</p>
        </div>

        <!-- Total Ingresos -->
        <div class="stat-card card-ingresos shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="icon-circle emerald-ic"><i class="fas fa-arrow-down"></i></div>
                <span class="text-xs text-gray-400 font-medium"><?= count(array_filter($movimientos, fn($m) => $m['tipo'] === 'ingreso')) ?> movs.</span>
            </div>
            <p class="text-2xl font-bold text-emerald-600">+$<?= number_format($total_ingresos, 2) ?></p>
            <p class="text-xs text-gray-400 mt-1">Total ingresos</p>
        </div>

        <!-- Total Gastos -->
        <div class="stat-card card-gastos shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="icon-circle red-ic"><i class="fas fa-arrow-up"></i></div>
                <span class="text-xs text-gray-400 font-medium"><?= count(array_filter($movimientos, fn($m) => $m['tipo'] !== 'ingreso')) ?> movs.</span>
            </div>
            <p class="text-2xl font-bold text-red-500">-$<?= number_format($total_gastos, 2) ?></p>
            <p class="text-xs text-gray-400 mt-1">Total gastos</p>
        </div>

        <!-- Balance -->
        <div class="stat-card card-balance shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="icon-circle gold-ic"><i class="fas fa-balance-scale"></i></div>
                <span class="text-xs text-gray-400 font-medium">Balance</span>
            </div>
            <p class="text-2xl font-bold <?= $balance_general >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                <?= $balance_general >= 0 ? '+' : '' ?>$<?= number_format($balance_general, 2) ?>
            </p>
            <p class="text-xs text-gray-400 mt-1">General</p>
        </div>
    </div>

    <!-- ════════════════════════════════════ MÉTODOS DE PAGO + EFECTIVO ══ -->
    <div class="max-w-7xl mx-auto mb-5">
        <div class="bg-white rounded-xl shadow-sm border border-[#E0ECD8] p-5">

            <!-- Tarjetas de método -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">

                <!-- Efectivo -->
                <div class="metodo-card mc-efectivo p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                            <i class="fas fa-money-bill-wave text-emerald-600"></i> Efectivo
                        </h4>
                        <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium">Físico</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Ingresos</span>
                            <span class="font-semibold text-emerald-600">+$<?= number_format($corte['total_ingresos_efectivo'] ?? 0, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Gastos</span>
                            <span class="font-semibold text-red-500">-$<?= number_format($corte['total_gastos_efectivo'] ?? 0, 2) ?></span>
                        </div>
                        <div class="pt-2 border-t border-emerald-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-600 text-sm">Balance</span>
                                <span class="font-bold text-base <?= (($corte['total_ingresos_efectivo']??0) - ($corte['total_gastos_efectivo']??0)) >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                                    $<?= number_format(($corte['total_ingresos_efectivo']??0) - ($corte['total_gastos_efectivo']??0), 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta -->
                <div class="metodo-card mc-tarjeta p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                            <i class="fas fa-credit-card text-blue-500"></i> Tarjeta
                        </h4>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">No física</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Ingresos</span>
                            <span class="font-semibold text-emerald-600">+$<?= number_format($corte['total_ingresos_tarjeta'] ?? 0, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Gastos</span>
                            <span class="font-semibold text-red-500">-$<?= number_format($corte['total_gastos_tarjeta'] ?? 0, 2) ?></span>
                        </div>
                        <div class="pt-2 border-t border-blue-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-600 text-sm">Balance</span>
                                <span class="font-bold text-base <?= (($corte['total_ingresos_tarjeta']??0) - ($corte['total_gastos_tarjeta']??0)) >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                                    $<?= number_format(($corte['total_ingresos_tarjeta']??0) - ($corte['total_gastos_tarjeta']??0), 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transferencia -->
                <div class="metodo-card mc-transfer p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                            <i class="fas fa-exchange-alt text-violet-500"></i> Transferencia
                        </h4>
                        <span class="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full font-medium">No física</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Ingresos</span>
                            <span class="font-semibold text-emerald-600">+$<?= number_format($corte['total_ingresos_transferencia'] ?? 0, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Gastos</span>
                            <span class="font-semibold text-red-500">-$<?= number_format($corte['total_gastos_transferencia'] ?? 0, 2) ?></span>
                        </div>
                        <div class="pt-2 border-t border-violet-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-600 text-sm">Balance</span>
                                <span class="font-bold text-base <?= (($corte['total_ingresos_transferencia']??0) - ($corte['total_gastos_transferencia']??0)) >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                                    $<?= number_format(($corte['total_ingresos_transferencia']??0) - ($corte['total_gastos_transferencia']??0), 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance general + Efectivo en caja -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Balance general -->
                <div class="balance-bar flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h4 class="font-bold text-[#3D5234] text-sm">Balance General</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Todos los métodos de pago</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold <?= $balance_general >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                            <?= $balance_general >= 0 ? '+' : '' ?>$<?= number_format($balance_general, 2) ?>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <span class="text-emerald-600 font-medium">↑ $<?= number_format($total_ingresos, 2) ?></span>
                            <span class="mx-1 text-gray-300">|</span>
                            <span class="text-red-500 font-medium">↓ $<?= number_format($total_gastos, 2) ?></span>
                        </p>
                    </div>
                </div>

                <!-- Efectivo físico en caja -->
                <div class="card-efectivo p-5">
                    <div class="relative z-10">
                        <p class="text-white/70 text-xs font-medium mb-1 flex items-center gap-1.5">
                            <i class="fas fa-coins text-[10px]"></i> Efectivo físico en caja
                        </p>
                        <p class="text-3xl font-bold text-white"><?= $efectivo_en_caja >= 0 ? '' : '-' ?>$<?= number_format(abs($efectivo_en_caja), 2) ?></p>
                        <p class="text-white/50 text-xs mt-1">Inicial $<?= number_format($corte['monto_inicial'] ?? 0, 2) ?> + ingresos − gastos efectivo</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═════════════════════════ ARQUEO + DENOMINACIONES (si está cerrado) ══ -->
    <?php if ($esta_cerrado): ?>
    <div class="max-w-7xl mx-auto mb-5 grid grid-cols-1 md:grid-cols-2 gap-5">

        <!-- Arqueo -->
        <div class="bg-white rounded-xl shadow-sm border border-[#E0ECD8] overflow-hidden">
            <div class="section-header sh-info rounded-t-xl">
                <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calculator text-white text-xs"></i>
                </div>
                Arqueo de Caja
            </div>
            <div class="p-5 space-y-3">
                <div class="flex justify-between items-center text-sm py-2 border-b border-gray-100">
                    <span class="text-gray-500 flex items-center gap-2"><i class="fas fa-calculator text-gray-400 text-xs"></i> Efectivo esperado</span>
                    <span class="font-semibold text-gray-800">$<?= number_format($efectivo_en_caja, 2) ?></span>
                </div>
                <div class="flex justify-between items-center text-sm py-2 border-b border-gray-100">
                    <span class="text-gray-500 flex items-center gap-2"><i class="fas fa-hand-holding-usd text-gray-400 text-xs"></i> Efectivo contado</span>
                    <span class="font-semibold text-gray-800">$<?= number_format($corte['efectivo_contado'] ?? 0, 2) ?></span>
                </div>
                <div class="pt-1">
                    <?php
                    $clase_dif = $diferencia > 0 ? 'diferencia-positiva' : ($diferencia < 0 ? 'diferencia-negativa' : 'diferencia-cero');
                    $color_dif = $diferencia > 0 ? 'text-emerald-700' : ($diferencia < 0 ? 'text-red-600' : 'text-gray-600');
                    $icon_dif  = $diferencia > 0 ? 'fa-arrow-up text-emerald-500' : ($diferencia < 0 ? 'fa-arrow-down text-red-500' : 'fa-equals text-gray-400');
                    $label_dif = $diferencia > 0 ? 'Sobrante' : ($diferencia < 0 ? 'Faltante' : 'Cuadrado');
                    ?>
                    <div class="<?= $clase_dif ?> rounded-xl p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-white/60">
                                <i class="fas <?= $icon_dif ?> text-sm"></i>
                            </div>
                            <div>
                                <p class="font-bold <?= $color_dif ?> text-sm"><?= $label_dif ?></p>
                                <p class="text-xs text-gray-500">Diferencia de arqueo</p>
                            </div>
                        </div>
                        <p class="text-xl font-bold <?= $color_dif ?>">
                            <?= $diferencia >= 0 ? '+' : '' ?>$<?= number_format($diferencia, 2) ?>
                        </p>
                    </div>
                </div>
                <?php if (!empty($corte['observaciones'])): ?>
                <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-xs font-semibold text-amber-700 mb-1 flex items-center gap-1.5">
                        <i class="fas fa-sticky-note"></i> Observaciones
                    </p>
                    <p class="text-xs text-amber-800"><?= htmlspecialchars($corte['observaciones']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Denominaciones -->
        <?php if (!empty($denominaciones)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-[#E0ECD8] overflow-hidden">
            <div class="section-header sh-denom rounded-t-xl">
                <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-money-bill-alt text-white text-xs"></i>
                </div>
                Denominaciones de Efectivo
            </div>
            <div class="divide-y divide-gray-100">
                <div class="grid grid-cols-3 text-xs font-semibold text-gray-500 px-4 py-2 bg-gray-50">
                    <span>Denominación</span>
                    <span class="text-center">Cantidad</span>
                    <span class="text-right">Subtotal</span>
                </div>
                <?php
                $total_denom = 0;
                foreach ($denominaciones as $den):
                    $subtotal = floatval($den['denominacion']) * intval($den['cantidad']);
                    $total_denom += $subtotal;
                ?>
                <div class="denom-row grid grid-cols-3 text-sm">
                    <span class="font-semibold text-gray-700">$<?= number_format($den['denominacion'], 0) ?></span>
                    <span class="text-center text-gray-500">× <?= intval($den['cantidad']) ?></span>
                    <span class="text-right font-semibold text-[#3D5234]">$<?= number_format($subtotal, 2) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="px-4 py-3 bg-[#F0F4ED] flex justify-between items-center">
                    <span class="font-bold text-[#3D5234] text-sm">Total contado</span>
                    <span class="font-bold text-[#3D5234]">$<?= number_format($total_denom, 2) ?></span>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Sin denominaciones: mostrar observaciones si hay -->
        <div class="bg-white rounded-xl shadow-sm border border-[#E0ECD8] overflow-hidden">
            <div class="section-header sh-denom rounded-t-xl">
                <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-money-bill-alt text-white text-xs"></i>
                </div>
                Denominaciones de Efectivo
            </div>
            <div class="p-8 text-center text-gray-400">
                <i class="fas fa-money-bill-wave text-3xl mb-2 opacity-30"></i>
                <p class="text-sm">No se registraron denominaciones para este corte</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════ CATEGORÍAS + MOVIMIENTOS ══ -->
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

        <!-- Col izquierda: Ingresos y Gastos por categoría -->
        <div class="lg:col-span-1 space-y-5">

            <!-- Ingresos por Categoría -->
            <div class="bg-white rounded-xl shadow-sm border border-[#D9EDD1] overflow-hidden">
                <div class="section-header sh-ingresos rounded-t-xl">
                    <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-pie text-white text-xs"></i>
                    </div>
                    Ingresos por Categoría
                </div>
                <div class="p-4">
                    <?php if (empty($cats_ingreso)): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2 opacity-40"></i>
                            <p class="text-sm">Sin ingresos registrados</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($cats_ingreso as $nombre => $cat): ?>
                            <div class="cat-item">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 flex items-center justify-center rounded-lg flex-shrink-0"
                                         style="background-color:<?= htmlspecialchars($cat['color']) ?>18">
                                        <i class="<?= htmlspecialchars($cat['icono']) ?> text-sm"
                                           style="color:<?= htmlspecialchars($cat['color']) ?>"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($nombre) ?></p>
                                        <p class="text-xs text-gray-400"><?= $cat['cantidad'] ?> movimiento<?= $cat['cantidad'] != 1 ? 's' : '' ?></p>
                                    </div>
                                </div>
                                <p class="font-bold text-emerald-600 text-sm">+$<?= number_format($cat['total'], 2) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gastos por Categoría -->
            <div class="bg-white rounded-xl shadow-sm border border-[#FDDEDE] overflow-hidden">
                <div class="section-header sh-gastos rounded-t-xl">
                    <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-pie text-white text-xs"></i>
                    </div>
                    Gastos por Categoría
                </div>
                <div class="p-4">
                    <?php if (empty($cats_gasto)): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2 opacity-40"></i>
                            <p class="text-sm">Sin gastos registrados</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($cats_gasto as $nombre => $cat): ?>
                            <div class="cat-item" style="background:#FFF8F8;border-color:#FDDEDE;">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 flex items-center justify-center rounded-lg flex-shrink-0"
                                         style="background-color:<?= htmlspecialchars($cat['color']) ?>18">
                                        <i class="<?= htmlspecialchars($cat['icono']) ?> text-sm"
                                           style="color:<?= htmlspecialchars($cat['color']) ?>"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($nombre) ?></p>
                                        <p class="text-xs text-gray-400"><?= $cat['cantidad'] ?> movimiento<?= $cat['cantidad'] != 1 ? 's' : '' ?></p>
                                    </div>
                                </div>
                                <p class="font-bold text-red-500 text-sm">-$<?= number_format($cat['total'], 2) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Col derecha: Lista de movimientos -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-[#E0ECD8] overflow-hidden">
            <div class="section-header sh-movs rounded-t-xl">
                <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-list text-white text-xs"></i>
                </div>
                <span class="flex-1">Movimientos del Corte</span>
                <span class="text-white/70 text-xs bg-white/10 px-2.5 py-1 rounded-full">
                    <?= count($movimientos) ?> registros
                </span>
            </div>

            <?php if (empty($movimientos)): ?>
                <div class="p-10 text-center text-gray-400">
                    <i class="fas fa-receipt text-3xl mb-2 opacity-40"></i>
                    <p class="text-sm">No hay movimientos en este corte</p>
                </div>
            <?php else: ?>
                <!-- Buscador rápido -->
                <div class="p-3 border-b border-gray-100 no-print">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" id="buscarMovCorte" placeholder="Buscar en movimientos..."
                               class="w-full pl-8 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5C7A4E]/30 focus:border-[#5C7A4E] bg-[#FAFDF8]">
                    </div>
                </div>

                <div class="custom-scroll max-h-[600px] overflow-y-auto" id="listaMovCorte">
                    <?php foreach ($movimientos as $mov):
                        $es_ingreso  = $mov['tipo'] === 'ingreso';
                        $es_ingreso_manual = $es_ingreso && empty($mov['reservacion_id']);
                        $origen_ingreso = null;
                        if ($es_ingreso) {
                            $origen_ingreso = $es_ingreso_manual
                                ? ['label' => 'Ingreso manual', 'icon' => 'keyboard', 'class' => 'badge-ingreso-manual']
                                : ['label' => 'Renta habitacion', 'icon' => 'bed', 'class' => 'badge-ingreso-renta'];
                        }
                        $metodoPago  = ['efectivo' => ['icon'=>'money-bill-wave','label'=>'Efectivo'],
                                        'tarjeta'  => ['icon'=>'credit-card',    'label'=>'Tarjeta'],
                                        'transferencia' => ['icon'=>'exchange-alt','label'=>'Transferencia']];
                        $metInfo     = $metodoPago[$mov['metodo_pago']] ?? ['icon'=>'circle','label'=>ucfirst($mov['metodo_pago'])];
                        $textoBusqueda = trim(implode(' ', [
                            $mov['descripcion'] ?? '',
                            $mov['categoria_nombre'] ?? '',
                            $mov['usuario_nombre'] ?? '',
                            $mov['metodo_pago'] ?? '',
                            $origen_ingreso['label'] ?? ''
                        ]));
                    ?>
                    <div class="mov-item" data-search="<?= strtolower(htmlspecialchars($textoBusqueda)) ?>">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-1.5 mb-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $es_ingreso ? 'badge-ingreso' : 'badge-gasto' ?>">
                                        <i class="fas fa-arrow-<?= $es_ingreso ? 'down' : 'up' ?> mr-1 text-[10px]"></i>
                                        <?= ucfirst($mov['tipo']) ?>
                                    </span>
                                    <?php if ($origen_ingreso): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $origen_ingreso['class'] ?>">
                                        <i class="fas fa-<?= $origen_ingreso['icon'] ?> mr-1 text-[10px]"></i>
                                        <?= $origen_ingreso['label'] ?>
                                    </span>
                                    <?php endif; ?>
                                    <span class="text-xs text-gray-400 flex items-center gap-1">
                                        <i class="fas fa-<?= $metInfo['icon'] ?> text-[10px]"></i>
                                        <?= $metInfo['label'] ?>
                                    </span>
                                    <?php if (!empty($mov['categoria_nombre'])): ?>
                                    <span class="text-xs flex items-center gap-1" style="color:<?= htmlspecialchars($mov['categoria_color'] ?? '#9CA3AF') ?>">
                                        <i class="<?= htmlspecialchars($mov['categoria_icono'] ?? 'fas fa-tag') ?> text-[10px]"></i>
                                        <?= htmlspecialchars($mov['categoria_nombre']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm font-semibold text-gray-800 mb-1 leading-tight">
                                    <?= htmlspecialchars($mov['descripcion'] ?? '') ?>
                                </p>
                                <div class="flex items-center gap-3 text-xs text-gray-400 flex-wrap">
                                    <span class="flex items-center gap-1">
                                        <i class="far fa-clock"></i>
                                        <?= date('d/m/Y H:i', strtotime($mov['created_at'])) ?>
                                    </span>
                                    <?php if (!empty($mov['usuario_nombre'])): ?>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-user text-[10px]"></i>
                                        <?= htmlspecialchars($mov['usuario_nombre']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($mov['referencia'])): ?>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-hashtag text-[10px]"></i>
                                        <?= htmlspecialchars($mov['referencia']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($mov['reservacion_id']): ?>
                                <div class="mt-1.5 text-xs">
                                    <a href="<?= url('reservaciones/ver/' . $mov['reservacion_id']) ?>"
                                       class="text-[#5C7A4E] font-medium flex items-center gap-1 hover:underline no-print">
                                        <i class="fas fa-bed text-[10px]"></i>
                                        Reserva #<?= $mov['reservacion_id'] ?>
                                    </a>
                                    <?php if (!empty($mov['habitaciones_detalle'])): ?>
                                    <span class="text-gray-400 flex items-center gap-1 mt-0.5">
                                        <i class="fas fa-door-open text-[10px]"></i>
                                        <?= htmlspecialchars($mov['habitaciones_detalle']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($mov['editado']) && $mov['editado']): ?>
                                <p class="text-xs text-amber-600 mt-1 flex items-center gap-1">
                                    <i class="fas fa-pen text-[10px]"></i>
                                    Editado<?= !empty($mov['motivo_edicion']) ? ': ' . htmlspecialchars($mov['motivo_edicion']) : '' ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <p class="text-base font-bold <?= $es_ingreso ? 'text-emerald-600' : 'text-red-500' ?> whitespace-nowrap">
                                <?= $es_ingreso ? '+' : '-' ?>$<?= number_format($mov['monto'] ?? 0, 2) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
// Buscador rápido en movimientos
document.getElementById('buscarMovCorte')?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('#listaMovCorte .mov-item').forEach(row => {
        const text = row.dataset.search || '';
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
});
</script>
