<?php
/**
 * Vista Principal de Caja
 * Los Cedros
 */
?>

<!-- Estilos elegantes Los Cedros -->
<style>
    :root {
        --lc-green: #5C7A4E;
        --lc-green-dark: #4A6340;
        --lc-green-deeper: #3D5234;
        --lc-green-light: #7A9B6A;
        --lc-gold: #C8A96A;
        --lc-gold-light: #D9BF8A;
        --lc-cream: #F7F4EE;
        --lc-cream-dark: #EEE9DE;
    }

    .lc-bg { background: linear-gradient(135deg, #F0F4ED 0%, #E8F0E3 50%, #F5F2EC 100%); }

    /* Header card elegante */
    .header-card {
        background: linear-gradient(135deg, #fff 0%, #FAFDF8 100%);
        border-left: 4px solid var(--lc-gold);
        position: relative;
        overflow: hidden;
    }
    .header-card::before {
        content: '';
        position: absolute;
        top: -30px; right: -30px;
        width: 160px; height: 160px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(92,122,78,0.06) 0%, transparent 70%);
        pointer-events: none;
    }

    /* Stat cards con hover elegante */
    .stat-card {
        background: #fff;
        border-radius: 16px;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        position: relative;
        overflow: hidden;
    }
    .stat-card::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(92,122,78,0.12); }
    .stat-card:hover::after { opacity: 1; }
    .stat-card.card-inicial::after { background: var(--lc-green-light); }
    .stat-card.card-ingresos::after { background: #10b981; }
    .stat-card.card-gastos::after { background: #ef4444; }

    /* Icon circles */
    .icon-circle {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .icon-circle.green-ic { background: rgba(92,122,78,0.12); color: var(--lc-green); }
    .icon-circle.emerald-ic { background: rgba(16,185,129,0.12); color: #059669; }
    .icon-circle.red-ic { background: rgba(239,68,68,0.12); color: #dc2626; }

    /* Efectivo en caja - card especial */
    .card-efectivo {
        background: linear-gradient(135deg, var(--lc-green) 0%, var(--lc-green-deeper) 100%);
        border-radius: 16px;
        color: white;
        position: relative;
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .card-efectivo::before {
        content: '';
        position: absolute;
        top: -20px; right: -20px;
        width: 110px; height: 110px;
        border-radius: 50%;
        background: rgba(255,255,255,0.07);
    }
    .card-efectivo::after {
        content: '';
        position: absolute;
        bottom: -30px; left: -10px;
        width: 90px; height: 90px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
    }
    .card-efectivo:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(61,82,52,0.25); }

    /* Métodos de pago cards */
    .metodo-card {
        border-radius: 12px;
        border: 1.5px solid;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .metodo-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
    .metodo-card.mc-efectivo { border-color: #A7C89A; background: linear-gradient(135deg, #F0FAF0 0%, #E8F5E3 100%); }
    .metodo-card.mc-tarjeta  { border-color: #A8C4E0; background: linear-gradient(135deg, #F0F6FF 0%, #E8F0FA 100%); }
    .metodo-card.mc-transfer { border-color: #B8A8D8; background: linear-gradient(135deg, #F5F0FF 0%, #EDE8F8 100%); }

    /* Section headers */
    .section-header {
        padding: 16px 20px;
        display: flex; align-items: center; gap: 10px;
        font-weight: 600; color: white; font-size: 0.95rem;
    }
    .section-header.sh-ingresos { background: linear-gradient(135deg, #10b981, #059669); }
    .section-header.sh-gastos   { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .section-header.sh-movs     { background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark)); }

    /* Categoría items */
    .cat-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 14px;
        border-radius: 10px;
        background: #FAFDF8;
        border: 1px solid #EBF3E7;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .cat-item:hover { background: #F0FAF0; border-color: #C8DCC0; }

    /* Movimiento items */
    .mov-item {
        padding: 14px 16px;
        border-bottom: 1px solid #F0F0EE;
        transition: background 0.15s ease;
    }
    .mov-item:hover { background: #FAFDF8; }
    .mov-item:last-child { border-bottom: none; }

    /* Badge tipos */
    .badge-ingreso { background: rgba(16,185,129,0.1); color: #065f46; border: 1px solid rgba(16,185,129,0.25); }
    .badge-gasto   { background: rgba(239,68,68,0.1); color: #991b1b; border: 1px solid rgba(239,68,68,0.25); }

    /* Action buttons principales */
    .btn-ingreso {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white; border-radius: 10px; padding: 9px 18px;
        font-weight: 600; font-size: 0.875rem;
        display: inline-flex; align-items: center; gap: 8px;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
        border: none; cursor: pointer;
    }
    .btn-ingreso:hover { box-shadow: 0 6px 18px rgba(16,185,129,0.3); transform: translateY(-1px); }

    .btn-gasto {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white; border-radius: 10px; padding: 9px 18px;
        font-weight: 600; font-size: 0.875rem;
        display: inline-flex; align-items: center; gap: 8px;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
        border: none; cursor: pointer;
    }
    .btn-gasto:hover { box-shadow: 0 6px 18px rgba(239,68,68,0.3); transform: translateY(-1px); }

    .btn-corte {
        background: linear-gradient(135deg, var(--lc-gold), #B8994A);
        color: #3D5234; border-radius: 10px; padding: 9px 18px;
        font-weight: 700; font-size: 0.875rem;
        display: inline-flex; align-items: center; gap: 8px;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
        text-decoration: none;
    }
    .btn-corte:hover { box-shadow: 0 6px 18px rgba(200,169,106,0.35); transform: translateY(-1px); }

    /* Balance general bar */
    .balance-bar {
        background: linear-gradient(135deg, #FAFDF8, #F0F4ED);
        border-radius: 12px;
        border: 1px solid #DDE8D5;
        padding: 18px 22px;
    }

    /* Quick links */
    .quick-link {
        display: flex; align-items: center; gap: 14px;
        padding: 16px 18px;
        border-radius: 12px;
        background: #FAFDF8;
        border: 1.5px solid #EAF0E6;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .quick-link:hover {
        background: #F0F7EC;
        border-color: #C0D8B6;
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(92,122,78,0.1);
    }
    .quick-link-icon {
        width: 42px; height: 42px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    /* Modal inputs elegantes */
    .modal-input {
        width: 100%;
        padding: 9px 14px;
        border: 1.5px solid #D1D5DB;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: #FDFCFA;
        color: #374151;
    }
    .modal-input:focus {
        outline: none;
        border-color: var(--lc-green);
        box-shadow: 0 0 0 3px rgba(92,122,78,0.12);
        background: #fff;
    }
    .modal-input-red:focus {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239,68,68,0.1);
    }
    .modal-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #4A6340;
        margin-bottom: 5px;
        letter-spacing: 0.01em;
    }
    .modal-label-red { color: #6B2020; }

    /* Scrollbar elegante */
    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: #F0F4ED; border-radius: 10px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #A8C4A0; border-radius: 10px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: var(--lc-green); }
</style>

<!-- Panel Principal de Caja -->
<div class="lc-bg min-h-screen p-4 md:p-6">
    <!-- Header Principal -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="header-card rounded-2xl shadow-lg p-5 md:p-7">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:rgba(92,122,78,0.12)">
                            <i class="fas fa-cash-register text-[#5C7A4E]"></i>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-[#3D5234] font-playfair tracking-tight">
                            Sistema de Caja
                        </h1>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 ml-12">
                        <span class="flex items-center gap-1.5 bg-[#F0F4ED] px-2.5 py-1 rounded-full">
                            <i class="fas fa-store text-[#5C7A4E] text-xs"></i>
                            <?= htmlspecialchars($caja['nombre'] ?? '') ?>
                        </span>
                        <span class="flex items-center gap-1.5 bg-[#F0F4ED] px-2.5 py-1 rounded-full">
                            <i class="fas fa-user text-[#5C7A4E] text-xs"></i>
                            <?= htmlspecialchars($corte['usuario_apertura'] ?? '') ?>
                        </span>
                        <span class="flex items-center gap-1.5 bg-[#F0F4ED] px-2.5 py-1 rounded-full">
                            <i class="fas fa-clock text-[#5C7A4E] text-xs"></i>
                            Abierta: <?= date('d/m/Y H:i', strtotime($corte['fecha_apertura'])) ?>
                        </span>
                    </div>
                </div>
                
                <!-- Acciones Rápidas -->
                <div class="flex flex-wrap gap-2.5">
                    <button onclick="mostrarModalIngreso()" class="btn-ingreso">
                        <i class="fas fa-plus-circle text-sm"></i>
                        <span class="hidden sm:inline">Registrar Ingreso</span>
                        <span class="sm:hidden">Ingreso</span>
                    </button>
                    <button onclick="mostrarModalGasto()" class="btn-gasto">
                        <i class="fas fa-minus-circle text-sm"></i>
                        <span class="hidden sm:inline">Registrar Gasto</span>
                        <span class="sm:hidden">Gasto</span>
                    </button>
                    <a href="<?= url('caja/corte') ?>" class="btn-corte">
                        <i class="fas fa-scissors text-sm"></i>
                        <span class="hidden sm:inline">Realizar Corte</span>
                        <span class="sm:hidden">Corte</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resumen de Caja -->
    <div class="max-w-7xl mx-auto mb-6">
        <!-- Primera fila - Resumen General -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Monto Inicial -->
            <div class="stat-card card-inicial shadow-sm p-5 border border-[#E5EDE0]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Monto Inicial</p>
                        <p class="text-2xl font-bold text-[#3D5234]">
                            $<?= number_format($resumen['monto_inicial'], 2) ?>
                        </p>
                    </div>
                    <div class="icon-circle green-ic">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Ingresos -->
            <div class="stat-card card-ingresos shadow-sm p-5 border border-[#D1F0E3]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Ingresos</p>
                        <p class="text-2xl font-bold text-emerald-600">
                            +$<?= number_format($resumen['ingresos']['total'], 2) ?>
                        </p>
                    </div>
                    <div class="icon-circle emerald-ic">
                        <i class="fas fa-arrow-trend-up"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Gastos -->
            <div class="stat-card card-gastos shadow-sm p-5 border border-[#FDD9D9]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Gastos</p>
                        <p class="text-2xl font-bold text-red-500">
                            -$<?= number_format($resumen['gastos']['total'], 2) ?>
                        </p>
                    </div>
                    <div class="icon-circle red-ic">
                        <i class="fas fa-arrow-trend-down"></i>
                    </div>
                </div>
            </div>
            
            <!-- Efectivo en Caja -->
            <div class="card-efectivo shadow-lg p-5">
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-white/70 uppercase tracking-wider mb-1">Efectivo en Caja</p>
                        <p class="text-3xl font-bold text-white">
                            $<?= number_format($resumen['efectivo_en_caja'], 2) ?>
                        </p>
                        <p class="text-xs text-white/60 mt-1 flex items-center gap-1">
                            <i class="fas fa-info-circle text-[10px]"></i> Solo efectivo físico
                        </p>
                    </div>
                    <div class="bg-white/15 w-12 h-12 rounded-xl flex items-center justify-center relative z-10">
                        <i class="fas fa-money-bill-wave text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Segunda fila - Desglose por Método de Pago -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-[#E5EDE0]">
            <h3 class="text-sm font-bold text-[#3D5234] mb-4 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#EEF4EB] flex items-center justify-center">
                    <i class="fas fa-credit-card text-[#5C7A4E] text-xs"></i>
                </div>
                Resumen por Método de Pago
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Efectivo -->
                <div class="metodo-card mc-efectivo p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                            <i class="fas fa-money-bill-wave text-emerald-600"></i>
                            Efectivo
                        </h4>
                        <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium">
                            Afecta caja
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Ingresos</span>
                            <span class="font-semibold text-emerald-600">
                                +$<?= number_format($resumen['ingresos']['efectivo']['total'], 2) ?>
                                <?php if ($resumen['ingresos']['efectivo']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['ingresos']['efectivo']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Gastos</span>
                            <span class="font-semibold text-red-500">
                                -$<?= number_format($resumen['gastos']['efectivo']['total'], 2) ?>
                                <?php if ($resumen['gastos']['efectivo']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['gastos']['efectivo']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="pt-2 border-t border-emerald-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-600 text-sm">Balance</span>
                                <span class="font-bold text-base <?= ($resumen['ingresos']['efectivo']['total'] - $resumen['gastos']['efectivo']['total']) >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                                    $<?= number_format($resumen['ingresos']['efectivo']['total'] - $resumen['gastos']['efectivo']['total'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta -->
                <div class="metodo-card mc-tarjeta p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                            <i class="fas fa-credit-card text-blue-500"></i>
                            Tarjeta
                        </h4>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
                            No física
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Ingresos</span>
                            <span class="font-semibold text-emerald-600">
                                +$<?= number_format($resumen['ingresos']['tarjeta']['total'], 2) ?>
                                <?php if ($resumen['ingresos']['tarjeta']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['ingresos']['tarjeta']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Gastos</span>
                            <span class="font-semibold text-red-500">
                                -$<?= number_format($resumen['gastos']['tarjeta']['total'], 2) ?>
                                <?php if ($resumen['gastos']['tarjeta']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['gastos']['tarjeta']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="pt-2 border-t border-blue-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-600 text-sm">Balance</span>
                                <span class="font-bold text-base <?= ($resumen['ingresos']['tarjeta']['total'] - $resumen['gastos']['tarjeta']['total']) >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                                    $<?= number_format($resumen['ingresos']['tarjeta']['total'] - $resumen['gastos']['tarjeta']['total'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transferencia -->
                <div class="metodo-card mc-transfer p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                            <i class="fas fa-exchange-alt text-violet-500"></i>
                            Transferencia
                        </h4>
                        <span class="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full font-medium">
                            No física
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Ingresos</span>
                            <span class="font-semibold text-emerald-600">
                                +$<?= number_format($resumen['ingresos']['transferencia']['total'], 2) ?>
                                <?php if ($resumen['ingresos']['transferencia']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['ingresos']['transferencia']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Gastos</span>
                            <span class="font-semibold text-red-500">
                                -$<?= number_format($resumen['gastos']['transferencia']['total'], 2) ?>
                                <?php if ($resumen['gastos']['transferencia']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['gastos']['transferencia']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="pt-2 border-t border-violet-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-600 text-sm">Balance</span>
                                <span class="font-bold text-base <?= ($resumen['ingresos']['transferencia']['total'] - $resumen['gastos']['transferencia']['total']) >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                                    $<?= number_format($resumen['ingresos']['transferencia']['total'] - $resumen['gastos']['transferencia']['total'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Totales Generales -->
            <div class="mt-4">
                <div class="balance-bar flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h4 class="font-bold text-[#3D5234] text-sm">Balance General</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Todos los métodos de pago</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold <?= $resumen['balance_general'] >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                            <?= $resumen['balance_general'] >= 0 ? '+' : '' ?>$<?= number_format($resumen['balance_general'], 2) ?>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <span class="text-emerald-600 font-medium">↑ $<?= number_format($resumen['ingresos']['total'], 2) ?></span>
                            <span class="mx-1 text-gray-300">|</span>
                            <span class="text-red-500 font-medium">↓ $<?= number_format($resumen['gastos']['total'], 2) ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenido Principal -->
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Columna Izquierda - Movimientos por Categoría -->
        <div class="lg:col-span-2 space-y-5">
            <!-- Ingresos por Categoría -->
            <div class="bg-white rounded-xl shadow-sm border border-[#D9EDD1] overflow-hidden">
                <div class="section-header sh-ingresos rounded-t-xl">
                    <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-pie text-white text-xs"></i>
                    </div>
                    Ingresos por Categoría
                </div>
                <div class="p-4">
                    <?php if (empty($movimientos_categoria['ingresos'])): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2 opacity-40"></i>
                            <p class="text-sm">No hay ingresos registrados hoy. Cuando captures cobros, aparecerán aquí.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($movimientos_categoria['ingresos'] as $cat): ?>
                                <div class="cat-item">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 flex items-center justify-center rounded-lg flex-shrink-0" 
                                             style="background-color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>18;">
                                            <i class="<?= htmlspecialchars($cat['icono'] ?? 'fas fa-tag') ?> text-sm" 
                                               style="color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($cat['categoria'] ?? 'Sin categoría') ?></p>
                                            <p class="text-xs text-gray-400"><?= $cat['cantidad'] ?? 0 ?> movimiento<?= ($cat['cantidad'] ?? 0) != 1 ? 's' : '' ?></p>
                                        </div>
                                    </div>
                                    <p class="font-bold text-emerald-600 text-sm">
                                        +$<?= number_format($cat['total'] ?? 0, 2) ?>
                                    </p>
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
                    <?php if (empty($movimientos_categoria['gastos'])): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2 opacity-40"></i>
                            <p class="text-sm">No hay gastos registrados hoy. Los gastos de la jornada aparecerán aquí.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($movimientos_categoria['gastos'] as $cat): ?>
                                <div class="cat-item" style="background:#FFF8F8; border-color:#FDDEDE;">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 flex items-center justify-center rounded-lg flex-shrink-0" 
                                             style="background-color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>18;">
                                            <i class="<?= htmlspecialchars($cat['icono'] ?? 'fas fa-tag') ?> text-sm" 
                                               style="color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($cat['categoria'] ?? 'Sin categoría') ?></p>
                                            <p class="text-xs text-gray-400"><?= $cat['cantidad'] ?? 0 ?> movimiento<?= ($cat['cantidad'] ?? 0) != 1 ? 's' : '' ?></p>
                                        </div>
                                    </div>
                                    <p class="font-bold text-red-500 text-sm">
                                        -$<?= number_format($cat['total'] ?? 0, 2) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Columna Derecha - Últimos Movimientos -->
        <div class="bg-white rounded-xl shadow-sm border border-[#E0ECD8] overflow-hidden">
            <div class="section-header sh-movs rounded-t-xl">
                <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-history text-white text-xs"></i>
                </div>
                <span class="flex-1">Últimos Movimientos</span>
                <a href="<?= url('caja/movimientos') ?>" 
                   class="text-white/70 hover:text-white text-xs transition flex items-center gap-1">
                    Ver todos <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>
            <div class="custom-scroll divide-y-0 max-h-[600px] overflow-y-auto">
                <?php if (empty($ultimos_movimientos)): ?>
                    <div class="text-center py-10 text-gray-400">
                        <i class="fas fa-receipt text-3xl mb-2 opacity-40"></i>
                        <p class="text-sm">No hay movimientos registrados en este corte. Registra un ingreso o gasto para ver actividad.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ultimos_movimientos as $mov): ?>
    <div class="mov-item">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <div class="flex items-center gap-1.5 mb-1.5">
                    <?php 
                    $tipo_mostrar = $mov['tipo'];
                    $es_ingreso = ($tipo_mostrar == 'ingreso');
                    $color_badge = $es_ingreso ? 'green' : 'red';
                    $icono_flecha = $es_ingreso ? 'down' : 'up';
                    $label_tipo = ucfirst($tipo_mostrar);
                    if ($tipo_mostrar == 'egreso') { $label_tipo = 'Devolución'; }
                    ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $es_ingreso ? 'badge-ingreso' : 'badge-gasto' ?>">
                        <i class="fas fa-arrow-<?= $icono_flecha ?> mr-1 text-[10px]"></i>
                        <?= $label_tipo ?>
                    </span>
                    <?php 
                    $metodoPago = $metodos_pago[$mov['metodo_pago']] ?? [];
                    ?>
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <i class="fas fa-<?= htmlspecialchars($metodoPago['icon'] ?? 'circle') ?> text-[10px]"></i>
                        <?= htmlspecialchars($metodoPago['label'] ?? ucfirst($mov['metodo_pago'])) ?>
                    </span>
                </div>
                <p class="text-sm font-semibold text-gray-800 mb-1 leading-tight">
                    <?= htmlspecialchars($mov['descripcion'] ?? '') ?>
                </p>
                <div class="flex items-center gap-3 text-xs text-gray-400">
                    <span class="flex items-center gap-1">
                        <i class="far fa-clock"></i>
                        <?= date('H:i', strtotime($mov['created_at'])) ?>
                    </span>
                    <?php if (!empty($mov['categoria_nombre'])): ?>
                        <span class="flex items-center gap-1">
                            <i class="<?= htmlspecialchars($mov['categoria_icono'] ?? 'fas fa-tag') ?>" 
                               style="color: <?= htmlspecialchars($mov['categoria_color'] ?? '#9CA3AF') ?>"></i>
                            <?= htmlspecialchars($mov['categoria_nombre']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($mov['reservacion_id']): ?>
                    <div class="mt-1.5 text-xs">
                        <span class="text-[#5C7A4E] font-medium flex items-center gap-1">
                            <i class="fas fa-bed text-[10px]"></i>
                            Reserva #<?= $mov['reservacion_id'] ?>
                        </span>
                        <?php if (!empty($mov['habitaciones_detalle'])): ?>
                            <span class="text-gray-400 flex items-center gap-1 mt-0.5">
                                <i class="fas fa-door-open text-[10px]"></i>
                                <?= htmlspecialchars($mov['habitaciones_detalle']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <p class="text-base font-bold <?= $es_ingreso ? 'text-emerald-600' : 'text-red-500' ?> whitespace-nowrap">
                <?= $es_ingreso ? '+' : '-' ?>$<?= number_format($mov['monto'] ?? 0, 2) ?>
            </p>
        </div>
        <?php if (!empty($mov['editado']) && $mov['editado']): ?>
            <p class="text-xs text-amber-600 mt-1.5 flex items-center gap-1">
                <i class="fas fa-pen text-[10px]"></i> Editado
            </p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Enlaces Rápidos -->
    <div class="max-w-7xl mx-auto mt-5">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-[#E5EDE0]">
            <h3 class="text-sm font-bold text-[#3D5234] mb-4 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#EEF4EB] flex items-center justify-center">
                    <i class="fas fa-link text-[#5C7A4E] text-xs"></i>
                </div>
                Accesos Rápidos
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <a href="<?= url('caja/movimientos') ?>" class="quick-link">
                    <div class="quick-link-icon" style="background: rgba(139,92,246,0.1);">
                        <i class="fas fa-list text-violet-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Movimientos</p>
                        <p class="text-xs text-gray-400">Ver todos</p>
                    </div>
                </a>
                <a href="<?= url('caja/historial') ?>" class="quick-link">
                    <div class="quick-link-icon" style="background: rgba(59,130,246,0.1);">
                        <i class="fas fa-history text-blue-500"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Historial</p>
                        <p class="text-xs text-gray-400">Cortes anteriores</p>
                    </div>
                </a>
                <a href="<?= url('caja/reporte-metodos') ?>" class="quick-link">
                    <div class="quick-link-icon" style="background: rgba(92,122,78,0.1);">
                        <i class="fas fa-credit-card text-[#5C7A4E]"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Métodos de Pago</p>
                        <p class="text-xs text-gray-400">Reporte detallado</p>
                    </div>
                </a>
                <?php if (user_role() == 'gerente'): ?>
                    <a href="<?= url('caja/categorias') ?>" class="quick-link">
                        <div class="quick-link-icon" style="background: rgba(200,169,106,0.15);">
                            <i class="fas fa-tags text-[#C8A96A]"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">Categorías</p>
                            <p class="text-xs text-gray-400">Configurar</p>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Ingreso -->
<div id="modalIngreso" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all border border-[#E0ECD8]">
            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 p-5 rounded-t-2xl flex items-center justify-between">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-plus text-white text-sm"></i>
                    </div>
                    Registrar Ingreso
                </h3>
                <button type="button" onclick="cerrarModalIngreso()" class="text-white/70 hover:text-white transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="<?= url('caja/ingreso') ?>" class="p-5">
                <?= csrf_field() ?>
                
                <div class="space-y-4">
                    <!-- Categoría -->
                    <div>
                        <label class="modal-label">Categoría <span class="text-red-400">*</span></label>
                        <select name="categoria_id" id="categoria_ingreso" class="modal-input" required>
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias['ingreso'] as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-icono="<?= htmlspecialchars($cat['icono'] ?? '') ?>" data-color="<?= htmlspecialchars($cat['color'] ?? '') ?>">
                                    <?= htmlspecialchars($cat['nombre'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Descripción -->
                    <div>
                        <label class="modal-label">Descripción <span class="text-red-400">*</span></label>
                        <textarea name="descripcion" rows="2" class="modal-input" 
                                  placeholder="Ej: Pago habitación 101" required></textarea>
                    </div>
                    
                    <!-- Monto y Método de Pago -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="modal-label">Monto <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                                <input type="number" name="monto" step="0.01" min="0.01"
                                       class="modal-input pl-7" placeholder="0.00" required>
                            </div>
                        </div>
                        <div>
                            <label class="modal-label">Método de Pago <span class="text-red-400">*</span></label>
                            <select name="metodo_pago" id="metodo_pago_ingreso" class="modal-input" required>
                                <?php foreach ($metodos_pago as $key => $metodo): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($metodo['label'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Referencia -->
                    <div id="referencia_ingreso_div" class="hidden">
                        <label class="modal-label">Referencia/Autorización <span class="text-red-400">*</span></label>
                        <input type="text" name="referencia" class="modal-input" placeholder="Número de referencia">
                    </div>
                    
                    <!-- Comprobante -->
                    <div>
                        <label class="modal-label">Número de Comprobante</label>
                        <input type="text" name="comprobante" class="modal-input" placeholder="Ej: Ticket #123">
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="flex justify-end gap-2 mt-5 pt-4 border-t border-gray-100">
                    <button type="button" onclick="cerrarModalIngreso()" 
                            class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition font-medium">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-lg hover:shadow-md transition font-semibold flex items-center gap-1.5">
                        <i class="fas fa-save text-xs"></i>
                        Guardar Ingreso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Gasto -->
<div id="modalGasto" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all my-8 border border-[#FDDEDE]">
            <!-- Cabecera -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 p-5 rounded-t-2xl flex items-center justify-between sticky top-0 z-10">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-minus text-white text-sm"></i>
                    </div>
                    Registrar Gasto
                </h3>
                <button type="button" onclick="cerrarModalGasto()" class="text-white/70 hover:text-white transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Contenido con scroll -->
            <div class="overflow-y-auto max-h-[calc(100vh-200px)]">
                <form method="POST" action="<?= url('caja/gasto') ?>" class="p-4">
                    <?= csrf_field() ?>
                    
                    <div class="space-y-3">
                        <!-- Categoría -->
                        <div>
                            <label class="modal-label modal-label-red">Categoría <span class="text-red-400">*</span></label>
                            <select name="categoria_id" id="categoria_gasto" class="modal-input modal-input-red" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias['gasto'] as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" data-icono="<?= htmlspecialchars($cat['icono'] ?? '') ?>" data-color="<?= htmlspecialchars($cat['color'] ?? '') ?>">
                                        <?= htmlspecialchars($cat['nombre'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Descripción -->
                        <div>
                            <label class="modal-label modal-label-red">Descripción <span class="text-red-400">*</span></label>
                            <textarea name="descripcion" rows="2" class="modal-input modal-input-red"
                                      placeholder="Ej: Compra de productos de limpieza" required></textarea>
                        </div>
                        
                        <!-- Proveedor -->
                        <div>
                            <label class="modal-label">Proveedor/Beneficiario</label>
                            <input type="text" name="proveedor" class="modal-input"
                                   placeholder="Nombre del proveedor">
                        </div>
                        
                        <!-- Monto y Método de Pago -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="modal-label modal-label-red">Monto <span class="text-red-400">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                                    <input type="number" name="monto" step="0.01" min="0.01"
                                           class="modal-input modal-input-red pl-7" 
                                           placeholder="0.00" required>
                                </div>
                            </div>
                            
                            <div>
                                <label class="modal-label modal-label-red">Método <span class="text-red-400">*</span></label>
                                <select name="metodo_pago" id="metodo_pago_gasto" 
                                        class="modal-input modal-input-red" required>
                                    <?php foreach ($metodos_pago as $key => $metodo): ?>
                                        <option value="<?= $key ?>">
                                            <?= htmlspecialchars($metodo['label'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Referencia -->
                        <div id="referencia_gasto_div" class="hidden">
                            <label class="modal-label modal-label-red">
                                Referencia/Autorización <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="referencia" class="modal-input modal-input-red"
                                   placeholder="Número de referencia">
                        </div>
                        
                        <!-- Comprobante -->
                        <div>
                            <label class="modal-label">Número de Comprobante</label>
                            <input type="text" name="comprobante" class="modal-input"
                                   placeholder="Ej: Factura #ABC123">
                        </div>
                    </div>
                    
                    <!-- Botones -->
                    <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-gray-100">
                        <button type="button" onclick="cerrarModalGasto()" 
                                class="px-3 py-1.5 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition font-medium">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-3 py-1.5 text-sm bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:shadow-md transition font-semibold flex items-center gap-1.5">
                            <i class="fas fa-save text-xs"></i>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Scripts del sidebar y modal-fix -->
<script src="<?= asset('js/sidebar-scripts.js') ?>"></script>
<script src="<?= asset('js/modal-sidebar-fix.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Funciones para modales
function mostrarModalIngreso() {
    document.getElementById('modalIngreso').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalIngreso() {
    document.getElementById('modalIngreso').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalIngreso').querySelector('form').reset();
}

function mostrarModalGasto() {
    document.getElementById('modalGasto').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalGasto() {
    document.getElementById('modalGasto').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalGasto').querySelector('form').reset();
}

// Reemplazar tus funciones originales con estas
function mostrarModalIngreso() {
    document.getElementById('sidebar').style.display = 'none';
    document.getElementById('modalIngreso').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalIngreso() {
    document.getElementById('sidebar').style.display = '';
    document.getElementById('modalIngreso').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalIngreso').querySelector('form').reset();
}

function mostrarModalGasto() {
    document.getElementById('sidebar').style.display = 'none';
    document.getElementById('modalGasto').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalGasto() {
    document.getElementById('sidebar').style.display = '';
    document.getElementById('modalGasto').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalGasto').querySelector('form').reset();
}

// Cerrar modales con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalIngreso();
        cerrarModalGasto();
    }
});

// Mostrar/ocultar campo de referencia según método de pago
document.getElementById('metodo_pago_ingreso').addEventListener('change', function() {
    const referenciaDiv = document.getElementById('referencia_ingreso_div');
    const referenciaInput = referenciaDiv.querySelector('input');
    
    if (this.value === 'tarjeta' || this.value === 'transferencia') {
        referenciaDiv.classList.remove('hidden');
        referenciaInput.setAttribute('required', 'required');
    } else {
        referenciaDiv.classList.add('hidden');
        referenciaInput.removeAttribute('required');
        referenciaInput.value = '';
    }
});

document.getElementById('metodo_pago_gasto').addEventListener('change', function() {
    const referenciaDiv = document.getElementById('referencia_gasto_div');
    const referenciaInput = referenciaDiv.querySelector('input');
    
    if (this.value === 'tarjeta' || this.value === 'transferencia') {
        referenciaDiv.classList.remove('hidden');
        referenciaInput.setAttribute('required', 'required');
    } else {
        referenciaDiv.classList.add('hidden');
        referenciaInput.removeAttribute('required');
        referenciaInput.value = '';
    }
});

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + I para ingreso
    if ((e.ctrlKey || e.metaKey) && e.key === 'i') {
        e.preventDefault();
        mostrarModalIngreso();
    }
    // Ctrl/Cmd + G para gasto
    if ((e.ctrlKey || e.metaKey) && e.key === 'g') {
        e.preventDefault();
        mostrarModalGasto();
    }
});

// Auto-refresh cada 5 minutos
setInterval(function() {
    location.reload();
}, 300000);
</script>
