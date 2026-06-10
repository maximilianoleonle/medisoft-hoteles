<?php require_once APP_PATH . '/views/layout/header.php'; ?>

<style>
/* ══════════════════════════════════════════
   Control de Inventario
   Sage green / gold / warm cream palette
   ══════════════════════════════════════════ */
:root {
    --lc-green:        #5C7A4E;
    --lc-green-dark:   #4A6340;
    --lc-green-deep:   #3D5234;
    --lc-green-light:  #7A9B6A;
    --lc-gold:         #C8A96A;
    --lc-gold-light:   #D9BF8A;
    --lc-cream:        #F7F4EE;
    --lc-cream-mid:    #EEE9DE;
}

/* Background */
.inv-page { background: linear-gradient(145deg,#EFF4EC 0%,#E8EFE3 45%,#F4F1EB 100%); min-height:100vh; }

/* ── Top bar ─────────────────────────────── */
.inv-topbar {
    background: #fff;
    border-bottom: 1px solid #DDE8D5;
    position: relative;
}
.inv-topbar::after {
    content:'';
    position:absolute; bottom:0; left:0; right:0; height:2px;
    background: linear-gradient(90deg, var(--lc-green-deep), var(--lc-gold), var(--lc-green-deep));
}

/* ── Stat pills ─────────────────────────── */
.inv-pill {
    display:inline-flex; align-items:center; gap:6px;
    background:#F0F5ED; border:1px solid #D5E4CB;
    border-radius:20px; padding:3px 10px;
    font-size:0.7rem; font-weight:600; color:#4A6340;
    transition: box-shadow .2s;
}
.inv-pill:hover { box-shadow: 0 3px 8px rgba(92,122,78,.15); }
.inv-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

/* ── Action buttons ──────────────────────── */
.btn-inv {
    display:inline-flex; align-items:center; justify-content:center;
    gap:6px; padding:7px 13px; border-radius:9px;
    font-size:0.78rem; font-weight:700;
    transition: transform .2s, box-shadow .2s; text-decoration:none;
    border: none; cursor: pointer;
}
.btn-inv:hover { transform:translateY(-1px); }
.btn-inv.primary { background:linear-gradient(135deg,#5C7A4E,#4A6340); color:#fff; }
.btn-inv.primary:hover { box-shadow: 0 6px 16px rgba(74,99,64,.3); }
.btn-inv.entrada { background:linear-gradient(135deg,#10b981,#059669); color:#fff; }
.btn-inv.entrada:hover { box-shadow: 0 6px 16px rgba(16,185,129,.3); }
.btn-inv.salida { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }
.btn-inv.salida:hover { box-shadow: 0 6px 16px rgba(245,158,11,.3); }
.btn-inv.pdf { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; }
.btn-inv.pdf:hover { box-shadow: 0 6px 16px rgba(239,68,68,.3); }

/* ── Stat widgets ────────────────────────── */
.stat-widget {
    background:#fff; border-radius:16px;
    border:1px solid #E0EBD8;
    padding:18px 16px; position:relative; overflow:hidden;
    transition: transform .25s, box-shadow .25s;
}
.stat-widget::before {
    content:''; position:absolute;
    bottom:-20px; right:-20px;
    width:70px; height:70px; border-radius:50%;
    opacity:.06; background: var(--accent-color, #5C7A4E);
    pointer-events:none;
}
.stat-widget:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(92,122,78,.12); }
.stat-icon {
    width:40px; height:40px; border-radius:11px;
    display:flex; align-items:center; justify-content:center;
    font-size:15px; flex-shrink:0;
}
.bar-track { height:3px; background:#E5EDE0; border-radius:2px; margin-top:10px; overflow:hidden; }
.bar-fill  { height:100%; border-radius:2px; transition:width 1s ease; }

/* ── Panels ──────────────────────────────── */
.inv-panel {
    background:#fff; border-radius:14px;
    border:1px solid #DDE8D5; overflow:hidden;
}
.panel-hd {
    background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark));
    padding:13px 16px;
    display:flex; align-items:center; justify-content:space-between;
}
.panel-hd-icon {
    width:28px; height:28px; border-radius:8px;
    background:rgba(255,255,255,.18);
    display:flex; align-items:center; justify-content:center;
}

/* ── Search ──────────────────────────────── */
.inv-search {
    padding:6px 12px 6px 30px;
    border:1.5px solid rgba(255,255,255,.5);
    border-radius:8px; font-size:0.78rem;
    background:rgba(255,255,255,.88); color:#374151;
    width:160px; transition:width .25s, border-color .2s, box-shadow .2s;
}
.inv-search::placeholder { color:#9CA3AF; }
.inv-search:focus {
    outline:none; width:200px;
    border-color:var(--lc-gold);
    box-shadow:0 0 0 3px rgba(200,169,106,.2);
    background:#fff;
}

/* ── Table (desktop) ────────────────────── */
.inv-th {
    font-size:.67rem; font-weight:700; letter-spacing:.05em;
    text-transform:uppercase; color:#7A9B6A;
    padding:10px 8px; white-space:nowrap;
}
.inv-tr { border-bottom:1px solid #F0F5ED; transition:background .15s; }
.inv-tr:hover { background:#F7FCF4; }
.inv-tr:last-child { border-bottom:none; }
.code-tag {
    font-family:ui-monospace,monospace; font-size:.7rem;
    background:#F0F5ED; color:#4A6340;
    border:1px solid #D0E0C8; padding:2px 7px; border-radius:5px;
}

/* ── Stock badges ────────────────────────── */
.badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 9px; border-radius:20px;
    font-size:.69rem; font-weight:700;
}
.badge-ok   { background:rgba(16,185,129,.1);  color:#065F46; border:1px solid rgba(16,185,129,.22); }
.badge-low  { background:rgba(245,158,11,.1);  color:#92400E; border:1px solid rgba(245,158,11,.22); }
.badge-out  { background:rgba(239,68,68,.1);   color:#991B1B; border:1px solid rgba(239,68,68,.22);  }
.badge-auto { background:rgba(92,122,78,.1);   color:#3D5234; border:1px solid rgba(92,122,78,.22); }

/* ── Action icons ────────────────────────── */
.act-btn {
    width:28px; height:28px; border-radius:7px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.7rem; transition:background .15s; border:none; cursor:pointer;
    text-decoration:none;
}
.act-btn.edit  { color:#5C7A4E; background:transparent; }
.act-btn.edit:hover  { background:#EEF4EB; }
.act-btn.del   { color:#DC2626; background:transparent; }
.act-btn.del:hover   { background:#FEF2F2; }

/* ── Movement items ──────────────────────── */
.mov-row { padding:11px 14px; border-bottom:1px solid #F0F5ED; transition:background .15s; }
.mov-row:hover { background:#FAFDF8; }
.mov-row:last-child { border-bottom:none; }
.mov-icon-w {
    width:30px; height:30px; border-radius:9px;
    display:flex; align-items:center; justify-content:center; font-size:11px; flex-shrink:0;
}

/* ── Alerts ──────────────────────────────── */
.alert-stock {
    border-radius:0 10px 10px 0; padding:12px 14px;
    display:flex; align-items:flex-start; gap:10px;
    margin-bottom:16px;
}
.alert-stock.amber { background:linear-gradient(135deg,#FFFBEB,#FEF3C7); border-left:4px solid #F59E0B; }
.alert-stock.green { background:linear-gradient(135deg,#ECFDF5,#D1FAE5); border-left:4px solid #5C7A4E; }

/* ── Custom scrollbar ────────────────────── */
.lc-scroll::-webkit-scrollbar { width:4px; height:4px; }
.lc-scroll::-webkit-scrollbar-track { background:#F0F5ED; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb { background:#A8C4A0; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background:var(--lc-green); }

/* ══════════════════════════════════════════
   MOBILE CARD VIEW - Products
   ══════════════════════════════════════════ */
.producto-card-mobile {
    background:#fff;
    border:1px solid #E0EBD8;
    border-radius:10px;
    padding:10px 12px;
    display:flex;
    align-items:center;
    gap:10px;
    transition: background .15s;
}
.producto-card-mobile:hover { background:#F7FCF4; }
.producto-card-mobile + .producto-card-mobile { margin-top:6px; }

.producto-card-mobile .prod-info { flex:1; min-width:0; }
.producto-card-mobile .prod-name {
    font-size:.78rem; font-weight:600; color:#374151;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.producto-card-mobile .prod-cat {
    font-size:.65rem; color:#9CA3AF; margin-top:1px;
}
.producto-card-mobile .prod-actions {
    display:flex; align-items:center; gap:4px; flex-shrink:0;
}

/* Hide table on mobile, show cards */
@media (max-width: 639px) {
    .inv-table-desktop { display:none !important; }
    .inv-cards-mobile  { display:block !important; }
    .inv-search { width:120px; font-size:.72rem; }
    .inv-search:focus { width:150px; }
}
@media (min-width: 640px) {
    .inv-cards-mobile  { display:none !important; }
}

/* ── Config button (more visible) ────────── */
.btn-config-inv {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 14px; border-radius:8px;
    font-size:.75rem; font-weight:600;
    background:linear-gradient(135deg, #F0F5ED, #E5EDE0);
    color:#4A6340; border:1px solid #C8D8BE;
    transition: all .2s; text-decoration:none;
}
.btn-config-inv:hover {
    background:linear-gradient(135deg, #E5EDE0, #D5E4CB);
    box-shadow: 0 3px 10px rgba(92,122,78,.15);
    transform:translateY(-1px);
}
</style>

<!-- ═══════════════════════════════════════ INVENTARIO PAGE ═══ -->
<div class="inv-page">

    <!-- Top Bar -->
    <div class="inv-topbar bg-white">
        <div class="px-4 sm:px-5 lg:px-8 py-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">

                <!-- Title -->
                <div class="flex items-center gap-3">
                    <div class="stat-icon" style="background:rgba(92,122,78,.12);color:var(--lc-green);width:44px;height:44px;">
                        <i class="fas fa-boxes text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-[#3D5234] leading-tight">Control de Inventario</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Gestión y monitoreo de productos · <?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <a href="<?= url('inventario/nuevo') ?>" class="btn-inv primary flex-1 sm:flex-none">
                        <i class="fas fa-plus text-xs"></i>
                        <span class="hidden xs:inline">Nuevo</span> Producto
                    </a>
                    <a href="<?= url('inventario/entrada') ?>" class="btn-inv entrada flex-1 sm:flex-none">
                        <i class="fas fa-arrow-down text-xs"></i> Entrada
                    </a>
                    <a href="<?= url('inventario/salida') ?>" class="btn-inv salida flex-1 sm:flex-none">
                        <i class="fas fa-arrow-up text-xs"></i> Salida
                    </a>
                    <a href="<?= url('inventario/exportar') ?>" class="btn-inv pdf flex-1 sm:flex-none">
                        <i class="fas fa-file-pdf text-xs"></i>
                        <span class="hidden xs:inline">PDF</span>
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-[#EAF0E5]">
                <?php
                $total_productos     = count($productos);
                $productos_ok        = count(array_filter($productos, fn($p) => $p['stock_actual'] > $p['stock_minimo']));
                $productos_bajos     = count(array_filter($productos, fn($p) => $p['stock_actual'] <= $p['stock_minimo'] && $p['stock_actual'] > 0));
                $productos_agotados  = count(array_filter($productos, fn($p) => $p['stock_actual'] == 0));
                $productos_auto_cnt  = count(array_filter($productos, fn($p) => $p['descuento_automatico']));
                ?>
                <span class="inv-pill"><span class="inv-dot" style="background:#5C7A4E"></span>Total: <b><?= $total_productos ?></b></span>
                <span class="inv-pill"><span class="inv-dot" style="background:#10b981"></span>Correcto: <b><?= $productos_ok ?></b></span>
                <span class="inv-pill"><span class="inv-dot" style="background:#f59e0b"></span>Stock bajo: <b><?= $productos_bajos ?></b></span>
                <span class="inv-pill"><span class="inv-dot" style="background:#ef4444"></span>Agotados: <b><?= $productos_agotados ?></b></span>
                <span class="inv-pill"><span class="inv-dot" style="background:#8b5cf6"></span>Auto check-in: <b><?= $productos_auto_cnt ?></b></span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 sm:px-5 lg:px-8 py-5">

        <!-- Alerts -->
        <?php
        $productos_alerta    = array_filter($productos, fn($p) => $p['stock_actual'] <= $p['stock_minimo']);
        $productos_auto_list = array_filter($productos, fn($p) => $p['descuento_automatico']);
        ?>
        <?php if (!empty($productos_alerta)): ?>
        <div class="alert-stock amber shadow-sm">
            <i class="fas fa-exclamation-triangle text-amber-500 text-sm mt-0.5 flex-shrink-0"></i>
            <p class="text-xs sm:text-sm text-amber-800">
                <strong>Atención:</strong> <?= count($productos_alerta) ?> producto(s) con stock bajo o crítico requieren reposición.
            </p>
        </div>
        <?php endif; ?>
        <?php if (!empty($productos_auto_list)): ?>
        <div class="alert-stock green shadow-sm">
            <i class="fas fa-bed text-[#5C7A4E] text-sm mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-xs sm:text-sm text-[#3D5234]">
                    <strong>Descuento automático activo:</strong> <?= count($productos_auto_list) ?> producto(s) se descuentan al registrar check-in.
                </p>
                <p class="text-xs text-[#4A6340] mt-1">
                    Los marcados con <span class="badge badge-auto ml-1"><i class="fas fa-check-circle"></i>Auto</span> se reducen automáticamente.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stat Widgets -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">

            <!-- Total -->
            <div class="stat-widget" style="--accent-color:#5C7A4E">
                <div class="flex items-center justify-between mb-3">
                    <div class="stat-icon" style="background:rgba(92,122,78,.12);color:#5C7A4E;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <span class="text-2xl font-bold text-[#3D5234]"><?= $total_productos ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Productos</p>
                <p class="text-xs text-gray-400 mt-0.5">en catálogo</p>
                <div class="bar-track"><div class="bar-fill" style="width:100%;background:#5C7A4E"></div></div>
            </div>

            <!-- OK -->
            <div class="stat-widget" style="--accent-color:#10b981">
                <div class="flex items-center justify-between mb-3">
                    <div class="stat-icon" style="background:rgba(16,185,129,.12);color:#059669;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <span class="text-2xl font-bold text-emerald-600"><?= $productos_ok ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Stock Correcto</p>
                <p class="text-xs text-gray-400 mt-0.5"><?= $total_productos > 0 ? round($productos_ok/$total_productos*100) : 0 ?>% óptimo</p>
                <div class="bar-track"><div class="bar-fill" style="width:<?= $total_productos > 0 ? $productos_ok/$total_productos*100 : 0 ?>%;background:#10b981"></div></div>
            </div>

            <!-- Bajo -->
            <div class="stat-widget" style="--accent-color:#f59e0b">
                <div class="flex items-center justify-between mb-3">
                    <div class="stat-icon" style="background:rgba(245,158,11,.12);color:#d97706;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <span class="text-2xl font-bold text-amber-600"><?= $productos_bajos ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Stock Bajo</p>
                <p class="text-xs text-gray-400 mt-0.5">requieren atención</p>
                <div class="bar-track"><div class="bar-fill" style="width:<?= $total_productos > 0 ? $productos_bajos/$total_productos*100 : 0 ?>%;background:#f59e0b"></div></div>
            </div>

            <!-- Agotados -->
            <div class="stat-widget" style="--accent-color:#ef4444">
                <div class="flex items-center justify-between mb-3">
                    <div class="stat-icon" style="background:rgba(239,68,68,.12);color:#dc2626;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <span class="text-2xl font-bold text-red-500"><?= $productos_agotados ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Sin Stock</p>
                <p class="text-xs text-gray-400 mt-0.5">agotados</p>
                <div class="bar-track"><div class="bar-fill" style="width:<?= $total_productos > 0 ? $productos_agotados/$total_productos*100 : 0 ?>%;background:#ef4444"></div></div>
            </div>
        </div>

        <!-- Table + Movements -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Products Table -->
            <div class="lg:col-span-2 inv-panel">
                <div class="panel-hd">
                    <div class="flex items-center gap-2.5">
                        <div class="panel-hd-icon"><i class="fas fa-list text-white text-xs"></i></div>
                        <h3 class="text-sm font-bold text-white">Productos en Inventario</h3>
                    </div>
                    <div class="relative">
                        <input type="text" id="buscarProducto" placeholder="Buscar..." class="inv-search">
                        <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div class="p-3 sm:p-4">
                    <?php if (empty($productos)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-4xl mb-3" style="color:#D5E4CB"></i>
                            <p class="text-gray-400 text-sm mb-3">No hay productos registrados</p>
                            <a href="<?= url('inventario/nuevo') ?>" class="btn-inv primary text-xs">
                                <i class="fas fa-plus"></i> Agregar primer producto
                            </a>
                        </div>
                    <?php else: ?>

                        <!-- ═══ DESKTOP TABLE ═══ -->
                        <div class="inv-table-desktop overflow-x-auto lc-scroll -mx-4 px-4">
                            <table class="min-w-full" id="tablaProductos">
                                <thead>
                                    <tr class="border-b border-[#EAF0E5]">
                                        <th class="inv-th text-left">Código</th>
                                        <th class="inv-th text-left">Producto</th>
                                        <th class="inv-th text-center">Stock</th>
                                        <th class="inv-th text-center hidden sm:table-cell">Mín</th>
                                        <th class="inv-th text-center">Auto</th>
                                        <th class="inv-th text-center"><i class="fas fa-ellipsis-h text-gray-300"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $producto): ?>
                                    <tr class="inv-tr <?= $producto['descuento_automatico'] ? 'bg-[#FAFDF8]' : '' ?>"
                                        data-producto="<?= htmlspecialchars(strtolower($producto['nombre'].' '.$producto['codigo'])) ?>">

                                        <td class="py-3 px-1">
                                            <span class="code-tag"><?= htmlspecialchars($producto['codigo']) ?></span>
                                        </td>

                                        <td class="py-3 px-1">
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <p class="text-xs font-semibold text-gray-800 truncate max-w-[140px] sm:max-w-none">
                                                    <?= htmlspecialchars($producto['nombre']) ?>
                                                </p>
                                                <?php if ($producto['descuento_automatico']): ?>
                                                    <span class="inline-flex items-center px-1 py-0.5 rounded" style="background:rgba(92,122,78,.12);color:#4A6340;" title="Auto check-in">
                                                        <i class="fas fa-bed text-xs"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-xs text-gray-400"><?= htmlspecialchars($producto['categoria_nombre']) ?></p>
                                        </td>

                                        <td class="py-3 px-1 text-center">
                                            <?php
                                            $pct = $producto['stock_minimo'] > 0 ? ($producto['stock_actual']/$producto['stock_minimo'])*100 : 100;
                                            if ($pct <= 0)  { $bc='badge-out'; $bi='<i class="fas fa-times-circle"></i>'; }
                                            elseif ($pct<=50){ $bc='badge-low'; $bi='<i class="fas fa-exclamation-circle"></i>'; }
                                            else            { $bc='badge-ok';  $bi=''; }
                                            ?>
                                            <span class="badge <?= $bc ?>"><?= $bi ?><?= $producto['stock_actual'] ?></span>
                                        </td>

                                        <td class="py-3 px-1 text-center text-xs text-gray-500 hidden sm:table-cell">
                                            <?= $producto['stock_minimo'] ?>
                                        </td>

                                        <td class="py-3 px-1 text-center">
                                            <?php if ($producto['descuento_automatico']): ?>
                                                <span class="badge badge-auto" title="Descuento automático al check-in">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span class="hidden sm:inline">Auto</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-300 text-xs"><i class="fas fa-times"></i></span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="py-3 px-1 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="<?= url('inventario/editar/'.$producto['id']) ?>" class="act-btn edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button"
                                                        onclick="confirmarEliminarProducto(<?= $producto['id'] ?>, '<?= htmlspecialchars($producto['nombre']) ?>', <?= $producto['stock_actual'] ?>)"
                                                        class="act-btn del" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- ═══ MOBILE CARDS ═══ -->
                        <div class="inv-cards-mobile" style="display:none;">
                            <?php foreach ($productos as $producto): ?>
                            <div class="producto-card-mobile <?= $producto['descuento_automatico'] ? 'border-l-[3px] border-l-[#5C7A4E]' : '' ?>"
                                 data-producto-card="<?= htmlspecialchars(strtolower($producto['nombre'].' '.$producto['codigo'])) ?>">

                                <!-- Stock badge -->
                                <div style="flex-shrink:0;">
                                    <?php
                                    $pct = $producto['stock_minimo'] > 0 ? ($producto['stock_actual']/$producto['stock_minimo'])*100 : 100;
                                    if ($pct <= 0)  { $bc='badge-out'; $bi='<i class="fas fa-times-circle"></i>'; }
                                    elseif ($pct<=50){ $bc='badge-low'; $bi='<i class="fas fa-exclamation-circle"></i>'; }
                                    else            { $bc='badge-ok';  $bi=''; }
                                    ?>
                                    <span class="badge <?= $bc ?>"><?= $bi ?><?= $producto['stock_actual'] ?></span>
                                </div>

                                <!-- Info -->
                                <div class="prod-info">
                                    <div class="flex items-center gap-1">
                                        <p class="prod-name"><?= htmlspecialchars($producto['nombre']) ?></p>
                                        <?php if ($producto['descuento_automatico']): ?>
                                            <i class="fas fa-bed text-[10px]" style="color:#5C7A4E;" title="Auto check-in"></i>
                                        <?php endif; ?>
                                    </div>
                                    <p class="prod-cat">
                                        <span class="code-tag" style="font-size:.6rem;padding:1px 5px;"><?= htmlspecialchars($producto['codigo']) ?></span>
                                        <?= htmlspecialchars($producto['categoria_nombre']) ?>
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="prod-actions">
                                    <a href="<?= url('inventario/editar/'.$producto['id']) ?>" class="act-btn edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button"
                                            onclick="confirmarEliminarProducto(<?= $producto['id'] ?>, '<?= htmlspecialchars($producto['nombre']) ?>', <?= $producto['stock_actual'] ?>)"
                                            class="act-btn del" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Config button (more visible) -->
                        <div class="mt-3 pt-3 border-t border-[#EAF0E5] text-center">
                            <a href="<?= url('inventario/configuracion') ?>" class="btn-config-inv">
                                <i class="fas fa-cog"></i>Configuración de inventario
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Movements -->
            <div class="inv-panel">
                <div class="panel-hd">
                    <div class="flex items-center gap-2.5">
                        <div class="panel-hd-icon"><i class="fas fa-history text-white text-xs"></i></div>
                        <h3 class="text-sm font-bold text-white">Movimientos Recientes</h3>
                    </div>
                    <span class="bg-white/20 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                        <?= count($movimientos_recientes ?? []) ?>
                    </span>
                </div>

                <?php if (empty($movimientos_recientes)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-clipboard-list text-3xl mb-2" style="color:#D5E4CB"></i>
                        <p class="text-gray-400 text-sm">Sin movimientos recientes</p>
                    </div>
                <?php else: ?>
                    <div class="max-h-[460px] overflow-y-auto lc-scroll">
                        <?php foreach (array_slice($movimientos_recientes, 0, 10) as $mov): ?>
                        <div class="mov-row">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2.5">
                                    <?php if ($mov['tipo_movimiento'] == 'ENTRADA'): ?>
                                        <div class="mov-icon-w" style="background:rgba(16,185,129,.12);color:#059669;">
                                            <i class="fas fa-arrow-down"></i>
                                        </div>
                                    <?php elseif ($mov['tipo_movimiento'] == 'SALIDA'): ?>
                                        <div class="mov-icon-w" style="background:rgba(245,158,11,.12);color:#d97706;">
                                            <i class="fas fa-arrow-up"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="mov-icon-w" style="background:rgba(92,122,78,.12);color:#5C7A4E;">
                                            <i class="fas fa-sync"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="text-xs font-semibold text-gray-800 leading-tight">
                                            <?= htmlspecialchars($mov['producto_nombre']) ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            <?= date('d/m H:i', strtotime($mov['created_at'])) ?>
                                            <?php if ($mov['habitacion_numero']): ?> · Hab <?= $mov['habitacion_numero'] ?><?php endif; ?>
                                        </p>
                                        <?php if ($mov['motivo']): ?>
                                            <p class="text-xs text-gray-400 mt-0.5 italic"><?= htmlspecialchars($mov['motivo']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="text-sm font-bold whitespace-nowrap <?= $mov['tipo_movimiento']=='ENTRADA' ? 'text-emerald-600' : 'text-amber-600' ?>">
                                    <?= $mov['tipo_movimiento']=='ENTRADA' ? '+' : '−' ?><?= $mov['cantidad'] ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- end grid -->
    </div><!-- end main content -->
</div><!-- end page -->

<!-- Scripts -->
<script>
document.getElementById('buscarProducto').addEventListener('keyup', function() {
    const q = this.value.toLowerCase();
    // Desktop table
    document.querySelectorAll('#tablaProductos tbody tr').forEach(row => {
        const txt = row.getAttribute('data-producto') || '';
        row.style.display = txt.includes(q) ? '' : 'none';
    });
    // Mobile cards
    document.querySelectorAll('[data-producto-card]').forEach(card => {
        const txt = card.getAttribute('data-producto-card') || '';
        card.style.display = txt.includes(q) ? '' : 'none';
    });
});

function confirmarEliminarProducto(id, nombre, stock) {
    if (stock > 0) {
        Swal.fire({
            title: '¿Eliminar producto con stock?',
            html: `<div class="text-left">
                <p class="mb-2"><strong>${nombre}</strong></p>
                <p class="text-sm text-gray-600 mb-3">Este producto tiene <strong class="text-red-600">${stock} unidades</strong> en inventario.</p>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <p class="text-sm text-amber-800"><i class="fas fa-exclamation-triangle mr-1"></i><strong>Advertencia:</strong> Se perderá todo el registro de stock y movimientos asociados.</p>
                </div></div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#5C7A4E',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(r => { if (r.isConfirmed) eliminarProducto(id, nombre); });
    } else {
        Swal.fire({
            title: '¿Eliminar producto?',
            html: `¿Está seguro de eliminar <strong>${nombre}</strong>?<br>Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#5C7A4E',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(r => { if (r.isConfirmed) eliminarProducto(id, nombre); });
    }
}

function eliminarProducto(id, nombre) {
    Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, showConfirmButton: false, willOpen: () => Swal.showLoading() });
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url("inventario/eliminar/") ?>' + id;
    const csrfField = '<?= csrf_field() ?>';
    if (csrfField) form.innerHTML += csrfField;
    document.body.appendChild(form);
    form.submit();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once APP_PATH . '/views/layout/footer.php'; ?>
