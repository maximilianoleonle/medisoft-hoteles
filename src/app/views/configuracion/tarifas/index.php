<?php ?>

<!-- ── Librerías ─────────────────────────── -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ══════════════════════════════════════════
   Tarifas Dinámicas
   Sage green / gold / cream palette
   ══════════════════════════════════════════ */
:root {
    --lc-green:       #5C7A4E;
    --lc-green-dark:  #4A6340;
    --lc-green-deep:  #3D5234;
    --lc-gold:        #C8A96A;
    --lc-gold-dark:   #B8994A;
    --lc-cream:       #F7F4EE;
}

/* Page */
.tar-page { background:linear-gradient(145deg,#EFF4EC 0%,#E8EEE3 50%,#F4F1EC 100%); min-height:100vh; }

/* ── Top bar ─────────────────────────────── */
.tar-topbar {
    background:#fff;
    border-bottom:1px solid #DDE8D5;
    position:relative;
}
.tar-topbar::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:2px;
    background:linear-gradient(90deg,var(--lc-green-deep),var(--lc-gold),var(--lc-green-deep));
}

/* ── Action buttons ──────────────────────── */
.btn-tar {
    display:inline-flex; align-items:center; justify-content:center;
    gap:6px; padding:7px 13px; border-radius:9px;
    font-size:.78rem; font-weight:700;
    transition:transform .2s, box-shadow .2s;
    text-decoration:none; border:none; cursor:pointer;
}
.btn-tar:hover { transform:translateY(-1px); }
.btn-tar.calc {
    background:linear-gradient(135deg,#5C7A4E,#4A6340); color:#fff;
}
.btn-tar.calc:hover { box-shadow:0 6px 16px rgba(74,99,64,.3); color:#fff; }
.btn-tar.new {
    background:linear-gradient(135deg,var(--lc-gold),var(--lc-gold-dark));
    color:#3D5234;
}
.btn-tar.new:hover { box-shadow:0 6px 16px rgba(200,169,106,.35); color:#3D5234; }

/* ── Stat widgets ────────────────────────── */
.tar-stat {
    background:#fff; border-radius:14px; border:1px solid #DDE8D5;
    padding:16px; transition:transform .25s, box-shadow .25s;
    position:relative; overflow:hidden;
}
.tar-stat::after {
    content:''; position:absolute; bottom:0; left:0; right:0;
    height:2px; opacity:0; transition:opacity .25s;
    background:var(--ws, #5C7A4E);
}
.tar-stat:hover { transform:translateY(-3px); box-shadow:0 10px 26px rgba(92,122,78,.11); }
.tar-stat:hover::after { opacity:1; }
.tar-stat-icon {
    width:36px; height:36px; border-radius:10px;
    display:flex; align-items:center; justify-content:center; font-size:14px;
}

/* ── Filters panel ───────────────────────── */
.tar-filters {
    background:#fff; border-radius:14px; border:1px solid #DDE8D5;
    overflow:hidden; margin-bottom:16px;
}
.tar-filters-hd {
    background:linear-gradient(135deg,rgba(92,122,78,.07),rgba(92,122,78,.03));
    border-bottom:1px solid #EAF0E5;
    padding:12px 16px;
    display:flex; align-items:center; justify-content:space-between;
}
.filter-select {
    border:1.5px solid #C8D9BE; border-radius:8px;
    padding:6px 10px; font-size:.78rem; color:#374151;
    background:#FAFDF8; width:100%;
    transition:border-color .2s, box-shadow .2s;
}
.filter-select:focus {
    outline:none; border-color:var(--lc-gold);
    box-shadow:0 0 0 3px rgba(200,169,106,.15);
}
.filter-label { font-size:.7rem; font-weight:700; color:#5C7A4E; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.btn-reset { font-size:.72rem; color:#9CA3AF; background:none; border:none; cursor:pointer; transition:color .15s; }
.btn-reset:hover { color:var(--lc-green); }

/* ── Main table panel ────────────────────── */
.tar-panel {
    background:#fff; border-radius:14px;
    border:1px solid #DDE8D5; overflow:hidden;
}
.tar-panel-body { padding:16px; }

/* Table override for DataTables */
#tablaTarifas { width:100% !important; }
#tablaTarifas thead tr th {
    background:#F0F5ED !important;
    font-size:.67rem; font-weight:700; letter-spacing:.05em;
    text-transform:uppercase; color:#7A9B6A;
    border-bottom:2px solid #DDE8D5 !important;
    padding:10px 10px !important; white-space:nowrap;
}
#tablaTarifas tbody tr { border-bottom:1px solid #F0F5ED; transition:background .15s; }
#tablaTarifas tbody tr:hover { background:#F7FCF4 !important; }
#tablaTarifas tbody td { padding:10px 10px; font-size:.8rem; vertical-align:middle; border-top:none !important; }

/* ── Priority badge ──────────────────────── */
.prio-badge {
    width:28px; height:28px; border-radius:8px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.72rem; font-weight:800;
    background:linear-gradient(135deg,rgba(92,122,78,.12),rgba(92,122,78,.06));
    color:#3D5234; border:1px solid rgba(92,122,78,.2);
}

/* ── Value badges ────────────────────────── */
.val-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:3px 9px; border-radius:8px; font-size:.72rem; font-weight:700;
    background:linear-gradient(135deg,rgba(16,185,129,.1),rgba(5,150,105,.06));
    color:#065F46; border:1px solid rgba(16,185,129,.2);
}

/* ── Scope badges ────────────────────────── */
.scope-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:3px 9px; border-radius:8px; font-size:.7rem; font-weight:600;
}
.scope-global   { background:rgba(37,99,235,.09);  color:#1D4ED8; border:1px solid rgba(37,99,235,.18); }
.scope-tipo     { background:rgba(92,122,78,.09);   color:#3D5234; border:1px solid rgba(92,122,78,.2); }
.scope-hab      { background:rgba(245,158,11,.09);  color:#92400E; border:1px solid rgba(245,158,11,.2); }

/* ── Vigency badges ──────────────────────── */
.vig-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 8px; border-radius:6px; font-size:.68rem; font-weight:600;
}
.vig-vigente    { background:rgba(16,185,129,.1);  color:#065F46; border:1px solid rgba(16,185,129,.2); }
.vig-futuro     { background:rgba(37,99,235,.08);  color:#1D4ED8; border:1px solid rgba(37,99,235,.15); }
.vig-pasado     { background:#F3F4F6;               color:#6B7280; border:1px solid #E5E7EB; }
.vig-perm       { background:rgba(92,122,78,.08);   color:#4A6340; border:1px solid rgba(92,122,78,.18); }

/* ── Toggle switch override ──────────────── */
.peer-checked\:bg-gradient-to-r:checked ~ div {
    background:linear-gradient(to right,#5C7A4E,#7A9B6A) !important;
}
/* Force green for checked toggle */
input.toggle-activo:checked ~ div {
    background-image: linear-gradient(to right, #5C7A4E, #7A9B6A) !important;
}

/* ── Action icon buttons ─────────────────── */
.icn-btn {
    width:28px; height:28px; border-radius:7px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.7rem; transition:background .15s; border:none; cursor:pointer;
    background:transparent; text-decoration:none;
}
.icn-edit   { color:#5C7A4E; }
.icn-edit:hover   { background:#EEF4EB; color:#3D5234; }
.icn-view   { color:#2563EB; }
.icn-view:hover   { background:#EFF6FF; }
.icn-del    { color:#DC2626; }
.icn-del:hover    { background:#FEF2F2; }
.icn-btn:disabled { opacity:.35; cursor:not-allowed; }

/* ── Empty state ─────────────────────────── */
.empty-state {
    text-align:center; padding:48px 24px;
}

/* ── Modal overrides ─────────────────────── */
.modal-content {
    border:none !important; border-radius:16px !important; overflow:hidden;
    box-shadow:0 20px 50px rgba(61,82,52,.18) !important;
}
.modal-header {
    background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark)) !important;
    border-bottom:none !important; padding:16px 20px !important;
}
.modal-header .modal-title { color:#fff !important; font-size:.95rem; font-weight:700; }
.modal-header .close { color:rgba(255,255,255,.7) !important; text-shadow:none !important; font-size:1.4rem; }
.modal-header .close:hover { color:#fff !important; }
.modal-body { padding:20px !important; }

/* Modal detail fields */
.detail-field {
    background:#FAFDF8; border:1px solid #E5EDE0;
    border-radius:10px; padding:12px 14px; margin-bottom:10px;
}
.detail-field-label { font-size:.68rem; font-weight:700; color:#7A9B6A; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.detail-field-val   { font-size:.82rem; color:#374151; }

/* Date input */
.date-input {
    border:1.5px solid #C8D9BE; border-radius:9px;
    padding:8px 12px; font-size:.82rem; width:100%;
    background:#FAFDF8; color:#374151;
    transition:border-color .2s, box-shadow .2s;
}
.date-input:focus {
    outline:none; border-color:var(--lc-gold);
    box-shadow:0 0 0 3px rgba(200,169,106,.15);
}

/* ── DataTables custom styles ────────────── */
.dataTables_filter input {
    border:1.5px solid #C8D9BE !important; border-radius:8px !important;
    padding:5px 10px !important; font-size:.78rem !important;
    background:#FAFDF8 !important; color:#374151 !important;
    margin-left:6px !important;
}
.dataTables_filter input:focus {
    outline:none !important; border-color:var(--lc-gold) !important;
    box-shadow:0 0 0 3px rgba(200,169,106,.15) !important;
}
.dataTables_info { font-size:.72rem; color:#9CA3AF; }
.paginate_button {
    border-radius:7px !important; font-size:.72rem !important;
    border:none !important; padding:4px 9px !important;
}
.paginate_button:hover:not(.disabled) {
    background:#EEF4EB !important; color:#3D5234 !important;
    border:none !important;
}
.paginate_button.current, .paginate_button.current:hover {
    background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark)) !important;
    color:#fff !important; border:none !important;
}

/* ── Scrollbar ───────────────────────────── */
.lc-scroll::-webkit-scrollbar { width:4px; height:4px; }
.lc-scroll::-webkit-scrollbar-track { background:#F0F5ED; }
.lc-scroll::-webkit-scrollbar-thumb { background:#A8C4A0; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background:var(--lc-green); }

/* Toast overrides */
.toast-success { background-color:#ECFDF5 !important; color:#065F46 !important; border-left:3px solid #10b981 !important; }
.toast-error   { background-color:#FEF2F2 !important; color:#991B1B !important; border-left:3px solid #EF4444 !important; }
.toast-info    { background-color:#EFF6FF !important; color:#1E40AF !important; border-left:3px solid #3B82F6 !important; }

/* Disable Bootstrap overriding Tailwind gradients */
.bg-gradient-to-br { background-image:none !important; }
</style>

<!-- ════════════════ TARIFAS PAGE ══════════════════════════ -->
<div class="tar-page">

    <!-- Top Bar -->
    <div class="tar-topbar bg-white">
        <div class="px-3 sm:px-5 lg:px-7 py-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">

                <!-- Title -->
                <div class="flex items-center gap-3">
                    <div style="background:rgba(92,122,78,.1);width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-tags text-lg" style="color:#5C7A4E"></i>
                    </div>
                    <div>
                        <h1 class="text-base sm:text-xl font-bold text-[#3D5234] leading-tight">Gestión de Tarifas Dinámicas</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Administra incrementos y promociones de precios · <?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 w-full sm:w-auto">
                    <button onclick="previsualizarPrecios()" class="btn-tar calc flex-1 sm:flex-none">
                        <i class="fas fa-calculator text-xs"></i>
                        <span>Calcular</span><span class="hidden sm:inline"> Precios</span>
                    </button>
                    <a href="<?= url('configuracion/tarifas/crear') ?>" class="btn-tar new flex-1 sm:flex-none">
                        <i class="fas fa-plus text-xs"></i>
                        <span>Nuevo</span><span class="hidden sm:inline"> Incremento</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-3 sm:px-5 lg:px-7 py-5">

        <!-- Stat Widgets -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">

            <!-- Activos -->
            <div class="tar-stat" style="--ws:#5C7A4E">
                <div class="flex items-center justify-between mb-3">
                    <div class="tar-stat-icon" style="background:rgba(92,122,78,.1);color:#5C7A4E;">
                        <i class="fas fa-list"></i>
                    </div>
                    <span class="text-xl font-bold text-[#3D5234]"><?= $estadisticas['activos'] ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Activos</p>
                <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">Incrementos actualmente activos</p>
            </div>

            <!-- Vigentes -->
            <div class="tar-stat" style="--ws:#10b981">
                <div class="flex items-center justify-between mb-3">
                    <div class="tar-stat-icon" style="background:rgba(16,185,129,.1);color:#059669;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <span class="text-xl font-bold text-emerald-600"><?= $estadisticas['vigentes'] ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Vigentes</p>
                <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">Aplicándose hoy</p>
            </div>

            <!-- Programados -->
            <div class="tar-stat" style="--ws:#2563EB">
                <div class="flex items-center justify-between mb-3">
                    <div class="tar-stat-icon" style="background:rgba(37,99,235,.1);color:#2563EB;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <span class="text-xl font-bold text-blue-600"><?= $estadisticas['futuros'] ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Programados</p>
                <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">Para fechas futuras</p>
            </div>

            <!-- Por tipo -->
            <div class="tar-stat" style="--ws:#C8A96A">
                <div class="flex items-center justify-between mb-2">
                    <div class="tar-stat-icon" style="background:rgba(200,169,106,.12);color:#B8994A;">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Por Tipo</p>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 flex items-center gap-1">
                            <i class="fas fa-circle text-[#8b5cf6]" style="font-size:.45rem"></i>
                            <span class="hidden xs:inline">Porcentaje</span><span class="xs:hidden">%</span>
                        </span>
                        <span class="font-bold text-gray-700"><?= $estadisticas['porcentaje'] ?? 0 ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 flex items-center gap-1">
                            <i class="fas fa-circle text-[#10b981]" style="font-size:.45rem"></i>
                            <span class="hidden xs:inline">Monto Fijo</span><span class="xs:hidden">$</span>
                        </span>
                        <span class="font-bold text-gray-700"><?= $estadisticas['monto_fijo'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="tar-filters">
            <div class="tar-filters-hd">
                <div class="flex items-center gap-2">
                    <div style="width:24px;height:24px;border-radius:6px;background:rgba(92,122,78,.1);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-filter text-xs" style="color:#5C7A4E"></i>
                    </div>
                    <span class="text-xs font-bold text-[#3D5234]">Filtros</span>
                </div>
                <button onclick="resetFiltros()" class="btn-reset">
                    <i class="fas fa-sync-alt mr-1 text-xs"></i>Limpiar
                </button>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <p class="filter-label">Estado</p>
                    <select id="filtroEstado" class="filter-select">
                        <option value="">Todos los estados</option>
                        <option value="activo">Activos</option>
                        <option value="vigente">Vigentes Hoy</option>
                        <option value="futuro">Futuros</option>
                        <option value="pasado">Finalizados</option>
                    </select>
                </div>
                <div>
                    <p class="filter-label">Tipo</p>
                    <select id="filtroTipo" class="filter-select">
                        <option value="">Todos los tipos</option>
                        <option value="permanente">Permanentes</option>
                        <option value="temporal">Temporales</option>
                    </select>
                </div>
                <div>
                    <p class="filter-label">Alcance</p>
                    <select id="filtroAlcance" class="filter-select">
                        <option value="">Todos los alcances</option>
                        <option value="global">Global</option>
                        <option value="tipo_habitacion">Por Tipo de Habitación</option>
                        <option value="habitacion">Por Habitación</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Panel -->
        <div class="tar-panel">
            <div class="tar-panel-body">
                <?php if (empty($incrementos)): ?>
                    <div class="empty-state">
                        <div style="width:64px;height:64px;border-radius:50%;background:rgba(92,122,78,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                            <i class="fas fa-tags text-2xl" style="color:#A8C4A0"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-600 mb-2">No hay incrementos configurados</h3>
                        <p class="text-xs text-gray-400 mb-5 max-w-xs mx-auto">
                            Crea tu primer incremento para gestionar precios dinámicamente.
                        </p>
                        <a href="<?= url('configuracion/tarifas/crear') ?>" class="btn-tar new">
                            <i class="fas fa-plus text-xs"></i> Crear Primer Incremento
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto lc-scroll">
                        <table class="min-w-full" id="tablaTarifas">
                            <thead>
                                <tr>
                                    <th class="text-left">
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-sort-numeric-up"></i>
                                            <span class="hidden xs:inline">Prior.</span>
                                        </span>
                                    </th>
                                    <th class="text-left">Incremento</th>
                                    <th class="text-left hidden sm:table-cell">Valor</th>
                                    <th class="text-left hidden md:table-cell">Alcance</th>
                                    <th class="text-left">Vigencia</th>
                                    <th class="text-center">
                                        <span class="hidden sm:inline">Estado</span>
                                        <i class="fas fa-toggle-on sm:hidden"></i>
                                    </th>
                                    <th class="text-left hidden lg:table-cell">Creado</th>
                                    <th class="text-center">
                                        <span class="hidden sm:inline">Acciones</span>
                                        <i class="fas fa-ellipsis-h sm:hidden"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($incrementos as $inc): ?>
                                <?php
                                    $hoy = date('Y-m-d');
                                    if (!$inc['es_permanente']) {
                                        if ($inc['fecha_inicio'] > $hoy) $estado_fecha = 'futuro';
                                        elseif ($inc['fecha_fin'] && $inc['fecha_fin'] < $hoy) $estado_fecha = 'pasado';
                                        else $estado_fecha = 'vigente';
                                    } else {
                                        $estado_fecha = $inc['fecha_inicio'] <= $hoy ? 'vigente' : 'futuro';
                                    }
                                ?>
                                <tr data-id="<?= $inc['id'] ?>"
                                    data-estado="<?= $inc['activo'] ? 'activo' : 'inactivo' ?>"
                                    data-tipo="<?= $inc['es_permanente'] ? 'permanente' : 'temporal' ?>"
                                    data-alcance="<?= $inc['alcance'] ?>"
                                    data-vigencia="<?= $estado_fecha ?>"
                                    class="<?= !$inc['activo'] ? 'opacity-60' : '' ?>">

                                    <!-- Prioridad -->
                                    <td>
                                        <span class="prio-badge"><?= $inc['prioridad'] ?></span>
                                    </td>

                                    <!-- Nombre -->
                                    <td>
                                        <p class="text-xs font-semibold text-gray-800 truncate max-w-[160px]">
                                            <?= htmlspecialchars($inc['nombre']) ?>
                                        </p>
                                        <?php if ($inc['descripcion']): ?>
                                            <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">
                                                <?= htmlspecialchars($inc['descripcion']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <!-- Valor en móvil -->
                                        <div class="sm:hidden mt-1">
                                            <?php if ($inc['tipo_incremento'] == 'porcentaje'): ?>
                                                <span class="val-badge"><i class="fas fa-percentage text-xs"></i>+<?= number_format($inc['valor_incremento'],2) ?>%</span>
                                            <?php else: ?>
                                                <span class="val-badge"><i class="fas fa-dollar-sign text-xs"></i>+<?= format_currency($inc['valor_incremento']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Valor desktop -->
                                    <td class="hidden sm:table-cell">
                                        <?php if ($inc['tipo_incremento'] == 'porcentaje'): ?>
                                            <span class="val-badge"><i class="fas fa-percentage text-xs"></i>+<?= number_format($inc['valor_incremento'],2) ?>%</span>
                                        <?php else: ?>
                                            <span class="val-badge"><i class="fas fa-dollar-sign text-xs"></i>+<?= format_currency($inc['valor_incremento']) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Alcance -->
                                    <td class="hidden md:table-cell">
                                        <?php switch($inc['alcance']):
                                            case 'global': ?>
                                                <span class="scope-badge scope-global"><i class="fas fa-globe text-xs"></i>Global</span>
                                                <?php break; case 'tipo_habitacion':
                                                $tipos = json_decode($inc['tipos_habitacion'],true) ?: []; ?>
                                                <span class="scope-badge scope-tipo" title="<?= implode(', ',array_map('get_tipo_habitacion',$tipos)) ?>">
                                                    <i class="fas fa-bed text-xs"></i><?= count($tipos) ?> tipos
                                                </span>
                                                <?php break; case 'habitacion':
                                                $habs = json_decode($inc['habitaciones'],true) ?: []; ?>
                                                <span class="scope-badge scope-hab">
                                                    <i class="fas fa-door-open text-xs"></i><?= count($habs) ?> hab.
                                                </span>
                                                <?php break; endswitch; ?>
                                    </td>

                                    <!-- Vigencia -->
                                    <td>
                                        <?php if ($inc['es_permanente']): ?>
                                            <span class="vig-badge vig-perm"><i class="fas fa-infinity text-xs"></i><span class="hidden xs:inline">Permanente</span></span>
                                            <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">Desde <?= format_date($inc['fecha_inicio'],'d/m/Y') ?></p>
                                        <?php else: ?>
                                            <?php if ($estado_fecha=='vigente'): ?>
                                                <span class="vig-badge vig-vigente"><i class="fas fa-check-circle text-xs"></i><span class="hidden xs:inline">Vigente</span></span>
                                            <?php elseif ($estado_fecha=='futuro'): ?>
                                                <span class="vig-badge vig-futuro"><i class="fas fa-clock text-xs"></i><span class="hidden xs:inline">Programado</span></span>
                                            <?php else: ?>
                                                <span class="vig-badge vig-pasado"><i class="fas fa-times-circle text-xs"></i><span class="hidden xs:inline">Finalizado</span></span>
                                            <?php endif; ?>
                                            <p class="text-xs text-gray-400 mt-0.5">
                                                <?= format_date($inc['fecha_inicio'],'d/m') ?> – <?= format_date($inc['fecha_fin'],'d/m/Y') ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Toggle -->
                                    <td class="text-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                   class="sr-only peer toggle-activo"
                                                   data-id="<?= $inc['id'] ?>"
                                                   <?= $inc['activo'] ? 'checked' : '' ?>>
                                            <div class="w-9 h-5 bg-gray-200 rounded-full peer
                                                        peer-checked:after:translate-x-full peer-checked:after:border-white
                                                        after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                        after:bg-white after:border-gray-300 after:border after:rounded-full
                                                        after:h-4 after:w-4 after:transition-all
                                                        peer-checked:bg-green-600"></div>
                                            <span class="ml-2 text-xs font-medium text-gray-500 peer-checked:text-[#3D5234] hidden sm:inline">
                                                <?= $inc['activo'] ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </label>
                                    </td>

                                    <!-- Creado -->
                                    <td class="hidden lg:table-cell">
                                        <p class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($inc['usuario_nombre']) ?></p>
                                        <p class="text-xs text-gray-400"><?= format_date($inc['created_at'],'d/m/Y H:i') ?></p>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-0.5">
                                            <a href="<?= url('configuracion/tarifas/editar/'.$inc['id']) ?>"
                                               class="icn-btn icn-edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="icn-btn icn-view btn-detalle"
                                                    data-id="<?= $inc['id'] ?>" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="icn-btn icn-del btn-eliminar"
                                                    data-id="<?= $inc['id'] ?>" title="Eliminar"
                                                    <?= ($inc['activo'] && $estado_fecha=='vigente') ? 'disabled' : '' ?>>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div><!-- end page -->

<!-- ══════ Modal Calculadora de Precios ══════ -->
<div class="modal fade" id="modalPrecios" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title flex items-center gap-2">
                    <i class="fas fa-calculator opacity-80"></i>
                    Calculadora de Precios con Incrementos
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-5">
                    <label class="filter-label block mb-1.5">Seleccionar fecha para calcular precios</label>
                    <input type="date" class="date-input" id="fechaPreview"
                           value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div id="resultadoPrecios">
                    <div class="empty-state py-12">
                        <div style="width:56px;height:56px;border-radius:50%;background:rgba(92,122,78,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="fas fa-calendar-check text-xl" style="color:#A8C4A0"></i>
                        </div>
                        <p class="text-sm text-gray-400">Seleccione una fecha para ver los precios aplicables</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Modal Detalle Incremento ══════ -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title flex items-center gap-2">
                    <i class="fas fa-info-circle opacity-80"></i>
                    Detalle del Incremento
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="contenidoDetalle">
                <!-- Llenado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var tabla = $('#tablaTarifas').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        order: [[0,'desc'],[4,'desc']],
        pageLength: 10,
        lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']],
        columnDefs: [{ orderable:false, targets:[7] }],
        dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"<"flex items-center"f>>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"<"text-xs text-gray-400"i><"flex items-center"p>>',
        initComplete: function() {
            $('.dataTables_length').hide();
        }
    });

    $('[data-toggle="tooltip"]').tooltip();

    // Filtros
    $('#filtroEstado, #filtroTipo, #filtroAlcance').change(function() {
        aplicarFiltros();
    });

    function aplicarFiltros() {
        var estado   = $('#filtroEstado').val();
        var tipo     = $('#filtroTipo').val();
        var alcance  = $('#filtroAlcance').val();

        $('#tablaTarifas tbody tr').each(function() {
            var $r = $(this); var mostrar = true;
            if (estado) {
                if (estado === 'vigente') mostrar = $r.data('vigencia')==='vigente' && $r.data('estado')==='activo';
                else if (estado === 'activo') mostrar = $r.data('estado')==='activo';
                else mostrar = $r.data('vigencia')===estado;
            }
            if (tipo   && mostrar) mostrar = $r.data('tipo')===tipo;
            if (alcance && mostrar) mostrar = $r.data('alcance')===alcance;
            $r.toggle(mostrar);
        });
        tabla.draw();
    }

    // Toggle activo
    $('.toggle-activo').change(function() {
        const $sw = $(this), id = $sw.data('id'), activo = $sw.prop('checked');
        $.post('<?= url("configuracion/tarifas/toggle") ?>', { id:id, csrf_token:'<?= csrf_token() ?>' })
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                $sw.siblings('span').text(activo ? 'Activo' : 'Inactivo');
                const $row = $sw.closest('tr');
                activo ? $row.removeClass('opacity-60') : $row.addClass('opacity-60');
                $row.data('estado', activo ? 'activo' : 'inactivo');
                if ($row.data('vigencia')==='vigente') $row.find('.btn-eliminar').prop('disabled', activo);
            } else {
                toastr.error('Error al cambiar estado');
                $sw.prop('checked', !activo);
            }
        })
        .fail(function() { toastr.error('Error de conexión'); $sw.prop('checked', !activo); });
    });

    // Ver detalle
    $('.btn-detalle').click(function() {
        const id = $(this).data('id');
        $.get('<?= url("configuracion/tarifas/detalle") ?>', { id:id })
        .done(function(response) { if (response.success) mostrarDetalle(response.incremento); });
    });

    function mostrarDetalle(inc) {
        let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

        // Col izquierda
        html += '<div>';
        html += '<p class="text-xs font-bold text-[#5C7A4E] uppercase tracking-wider mb-3 flex items-center gap-1.5"><i class="fas fa-info-circle"></i>Información General</p>';

        html += `<div class="detail-field"><p class="detail-field-label">Nombre</p><p class="detail-field-val">${inc.nombre}</p></div>`;
        html += `<div class="detail-field"><p class="detail-field-label">Descripción</p><p class="detail-field-val">${inc.descripcion||'Sin descripción'}</p></div>`;

        html += '<div class="detail-field"><p class="detail-field-label">Tipo de Incremento</p><p class="detail-field-val">';
        if (inc.tipo_incremento==='porcentaje')
            html += `<span class="val-badge"><i class="fas fa-percentage text-xs"></i>+${inc.valor_incremento}%</span>`;
        else
            html += `<span class="val-badge"><i class="fas fa-dollar-sign text-xs"></i>+${formatCurrency(inc.valor_incremento)}</span>`;
        html += '</p></div>';

        html += `<div class="detail-field"><p class="detail-field-label">Prioridad</p><p class="detail-field-val"><span class="prio-badge">${inc.prioridad}</span></p></div>`;
        html += '</div>';

        // Col derecha
        html += '<div>';
        html += '<p class="text-xs font-bold text-[#5C7A4E] uppercase tracking-wider mb-3 flex items-center gap-1.5"><i class="fas fa-sliders-h"></i>Configuración de Aplicación</p>';

        html += '<div class="detail-field"><p class="detail-field-label">Alcance</p><p class="detail-field-val">';
        switch(inc.alcance) {
            case 'global':
                html += '<span class="scope-badge scope-global"><i class="fas fa-globe text-xs"></i>Global</span>';
                html += '<p class="text-xs text-gray-400 mt-1">Aplica a todas las habitaciones</p>'; break;
            case 'tipo_habitacion':
                html += '<span class="scope-badge scope-tipo"><i class="fas fa-bed text-xs"></i>Por Tipo</span>';
                html += '<div class="flex flex-wrap gap-1 mt-1.5">';
                inc.tipos_habitacion_array.forEach(t => { html += `<span class="vig-badge vig-perm text-xs">${gettipoHabitacion(t)}</span>`; });
                html += '</div>'; break;
            case 'habitacion':
                html += '<span class="scope-badge scope-hab"><i class="fas fa-door-open text-xs"></i>Habitaciones Específicas</span>';
                html += '<div class="flex flex-wrap gap-1 mt-1.5">';
                (inc.numeros_habitaciones||inc.habitaciones_array).forEach(h => { html += `<span class="vig-badge vig-perm text-xs">Hab. ${h}</span>`; });
                html += '</div>'; break;
        }
        html += '</p></div>';

        html += '<div class="detail-field"><p class="detail-field-label">Vigencia</p><p class="detail-field-val">';
        if (inc.es_permanente) {
            html += `<span class="vig-badge vig-perm"><i class="fas fa-infinity text-xs"></i>Permanente</span>`;
            html += `<p class="text-xs text-gray-400 mt-1">Desde: ${formatDate(inc.fecha_inicio)}</p>`;
        } else {
            html += `<span class="vig-badge vig-futuro"><i class="fas fa-calendar-alt text-xs"></i>Temporal</span>`;
            html += `<p class="text-xs text-gray-400 mt-1">${formatDate(inc.fecha_inicio)} – ${formatDate(inc.fecha_fin)}</p>`;
        }
        html += '</p></div>';

        html += '<div class="detail-field"><p class="detail-field-label">Estado</p><p class="detail-field-val">';
        html += inc.activo
            ? '<span class="vig-badge vig-vigente"><i class="fas fa-check-circle text-xs"></i>Activo</span>'
            : '<span class="vig-badge vig-pasado"><i class="fas fa-times-circle text-xs"></i>Inactivo</span>';
        html += '</p></div>';
        html += '</div></div>';

        html += `<div class="mt-4 pt-4 border-t border-[#EAF0E5]">
            <p class="text-xs text-gray-400 flex items-center gap-1.5">
                <i class="fas fa-user-clock" style="color:#A8C4A0"></i>
                Creado por <span class="font-semibold text-gray-600 ml-1">${inc.usuario_nombre}</span>
                &nbsp;·&nbsp; ${formatDate(inc.created_at, true)}
            </p></div>`;

        $('#contenidoDetalle').html(html);
        $('#modalDetalle').modal('show');
    }

    // Eliminar
    $('.btn-eliminar').click(function() {
        const $btn = $(this), id = $btn.data('id');
        if ($btn.prop('disabled')) return;
        Swal.fire({
            title: '¿Está seguro?', text: 'Esta acción no se puede deshacer',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#DC2626', cancelButtonColor: '#5C7A4E',
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: { popup:'rounded-xl', confirmButton:'rounded-lg', cancelButton:'rounded-lg' }
        }).then(result => {
            if (result.isConfirmed) {
                $.post('<?= url("configuracion/tarifas/eliminar") ?>', { id:id, csrf_token:'<?= csrf_token() ?>' })
                .done(function(r) {
                    if (r.success) {
                        toastr.success(r.message);
                        $btn.closest('tr').fadeOut(() => tabla.row($btn.closest('tr')).remove().draw());
                    } else toastr.error(r.message||'Error al eliminar');
                })
                .fail(() => toastr.error('Error de conexión'));
            }
        });
    });

    // Previsualizar precios
    window.previsualizarPrecios = function() {
        $('#modalPrecios').modal('show');
        cargarPreciosPreview();
    };

    $('#fechaPreview').change(cargarPreciosPreview);

    function cargarPreciosPreview() {
        const fecha = $('#fechaPreview').val();
        $('#resultadoPrecios').html(
            '<div class="text-center py-10">' +
            '<div style="width:52px;height:52px;border-radius:50%;background:rgba(92,122,78,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">' +
            '<i class="fas fa-spinner fa-spin text-xl" style="color:#5C7A4E"></i></div>' +
            '<p class="text-xs text-gray-400">Calculando precios...</p></div>'
        );
        $.post('<?= url("configuracion/tarifas/previsualizar") ?>', { fecha:fecha, csrf_token:'<?= csrf_token() ?>' })
        .done(function(r) { if (r.success) mostrarTablaPrecios(r); })
        .fail(() => {
            $('#resultadoPrecios').html(
                '<div class="p-3 rounded-xl" style="background:#FEF2F2;border:1px solid #FECACA;">' +
                '<p class="text-xs text-red-700"><i class="fas fa-exclamation-triangle mr-1.5"></i>Error al cargar los precios</p></div>'
            );
        });
    }

    function mostrarTablaPrecios(data) {
        let html = `<div class="flex items-center justify-between mb-4">
            <p class="text-xs font-bold text-[#3D5234]">Precios para el ${data.fecha_formateada}</p>
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="flex items-center gap-1"><i class="fas fa-circle text-xs" style="color:#C8A96A"></i>Con incremento</span>
                <span class="flex items-center gap-1"><i class="fas fa-circle text-xs text-gray-200"></i>Sin incremento</span>
            </div></div>`;

        const porTipo = {};
        data.precios.forEach(p => { if (!porTipo[p.tipo]) porTipo[p.tipo]=[]; porTipo[p.tipo].push(p); });

        html += '<div class="space-y-3">';
        Object.keys(porTipo).forEach((tipo, idx) => {
            const habs = porTipo[tipo];
            const tieneInc = habs.some(h => h.incremento > 0);
            html += `<div class="tar-panel overflow-hidden">
                <button class="w-full px-4 py-3 text-left flex items-center justify-between hover:bg-[#FAFDF8] transition-colors"
                        onclick="$('#precio-tipo-${idx}').toggleClass('hidden')">
                    <span class="text-xs font-bold text-gray-700">${tipo}</span>
                    <div class="flex items-center gap-2">
                        ${tieneInc ? '<span class="vig-badge" style="background:rgba(200,169,106,.12);color:#B8994A;border:1px solid rgba(200,169,106,.25);"><i class="fas fa-tag text-xs"></i>Con incremento</span>' : ''}
                        <i class="fas fa-chevron-down text-gray-300 text-xs"></i>
                    </div>
                </button>
                <div id="precio-tipo-${idx}" class="${idx>0?'hidden':''}">
                    <div class="overflow-x-auto lc-scroll">
                    <table class="min-w-full">
                        <thead><tr style="border-bottom:2px solid #DDE8D5;">
                            <th class="px-4 py-2 text-left" style="font-size:.65rem;font-weight:700;color:#7A9B6A;text-transform:uppercase;letter-spacing:.05em;">Habitación</th>
                            <th class="px-4 py-2 text-right" style="font-size:.65rem;font-weight:700;color:#7A9B6A;text-transform:uppercase;letter-spacing:.05em;">P. Base</th>
                            <th class="px-4 py-2 text-left hidden sm:table-cell" style="font-size:.65rem;font-weight:700;color:#7A9B6A;text-transform:uppercase;letter-spacing:.05em;">Incrementos</th>
                            <th class="px-4 py-2 text-right" style="font-size:.65rem;font-weight:700;color:#7A9B6A;text-transform:uppercase;letter-spacing:.05em;">P. Final</th>
                            <th class="px-4 py-2 text-right" style="font-size:.65rem;font-weight:700;color:#7A9B6A;text-transform:uppercase;letter-spacing:.05em;">Dif.</th>
                        </tr></thead>
                        <tbody>`;
            habs.forEach(h => {
                const tiInc = h.incremento > 0;
                html += `<tr style="border-bottom:1px solid #F0F5ED;${tiInc?'background:#FAFDF8;':''}">
                    <td class="px-4 py-2 text-xs font-semibold text-gray-800">Hab. ${h.habitacion}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 text-right">${formatCurrency(h.precio_base)}</td>
                    <td class="px-4 py-2 text-xs hidden sm:table-cell">`;
                if (h.incrementos.length) {
                    h.incrementos.forEach(i => {
                        html += `<div class="flex items-center gap-1 mb-0.5"><i class="fas fa-tag text-xs" style="color:#C8A96A"></i><span class="text-gray-600">${i.nombre}: <strong class="text-emerald-600">+${formatCurrency(i.aumento)}</strong>${i.tipo==='porcentaje'?` <span class="text-gray-400">(${i.valor}%)</span>`:''}</span></div>`;
                    });
                } else html += '<span class="text-gray-300 text-xs">Sin incrementos</span>';
                html += `</td>
                    <td class="px-4 py-2 text-xs font-bold text-right ${tiInc?'text-[#B8994A]':'text-gray-800'}">${formatCurrency(h.precio_final)}</td>
                    <td class="px-4 py-2 text-xs text-right">`;
                if (h.incremento>0) {
                    const pct = ((h.incremento/h.precio_base)*100).toFixed(1);
                    html += `<span class="text-red-500 font-semibold">+${formatCurrency(h.incremento)}</span><span class="text-gray-400 block text-xs">(+${pct}%)</span>`;
                } else html += '<span class="text-gray-300">—</span>';
                html += `</td></tr>`;
            });
            html += '</tbody></table></div></div></div>';
        });
        html += '</div>';
        $('#resultadoPrecios').html(html);
    }

    window.formatCurrency = val => '$'+parseFloat(val).toFixed(2).replace(/\d(?=(\d{3})+\.)/g,'$&,');
    window.formatDate = (str,time=false) => {
        if (!str) return '';
        const d = new Date(str);
        const o = {year:'numeric',month:'short',day:'numeric'};
        if (time) { o.hour='2-digit'; o.minute='2-digit'; }
        return d.toLocaleDateString('es-MX',o);
    };
    window.gettipoHabitacion = t => ({
        sencilla:'Sencilla',doble:'Doble',triple:'Triple',cuadruple:'Cuádruple',
        doble_jacuzzi:'Doble c/ Jacuzzi',sencilla_jacuzzi:'Sencilla c/ Jacuzzi'
    }[t]||t);
    window.resetFiltros = () => $('#filtroEstado,#filtroTipo,#filtroAlcance').val('').trigger('change');
});
</script>

<script>
toastr.options = {
    closeButton:true, progressBar:true,
    positionClass:'toast-top-right',
    timeOut:'3000', extendedTimeOut:'1000',
    showMethod:'fadeIn', hideMethod:'fadeOut'
};
</script>

<?php include __DIR__ . '/../../layout/footer.php'; ?>
