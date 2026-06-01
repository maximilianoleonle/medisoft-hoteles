<?php require_once APP_PATH . '/views/layout/header.php'; ?>

<style>
/* ══════════════════════════════════════════
   LOS CEDROS · Configuración Inventario
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

.config-page {
    background: linear-gradient(145deg,#EFF4EC 0%,#E8EFE3 45%,#F4F1EB 100%);
    min-height:100vh;
}

/* ── Top bar ─────────────────────────────── */
.cfg-topbar {
    background: #fff;
    border-bottom: 1px solid #DDE8D5;
    position: relative;
}
.cfg-topbar::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:2px;
    background: linear-gradient(90deg, var(--lc-green-deep), var(--lc-gold), var(--lc-green-deep));
}

/* ── Panels ──────────────────────────────── */
.cfg-panel {
    background:#fff; border-radius:14px;
    border:1px solid #DDE8D5; overflow:hidden;
}
.cfg-panel-hd {
    background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark));
    padding:12px 16px;
    display:flex; align-items:center; justify-content:space-between;
}
.cfg-panel-hd-icon {
    width:28px; height:28px; border-radius:8px;
    background:rgba(255,255,255,.18);
    display:flex; align-items:center; justify-content:center;
}

/* ── Info box ────────────────────────────── */
.cfg-info {
    background:linear-gradient(135deg,#F0F5ED,#E8EFE3);
    border:1px solid #D5E4CB;
    border-radius:12px;
    padding:14px;
}
.cfg-info-item {
    display:flex; align-items:center; gap:6px;
    font-size:.72rem; color:#4A6340; font-weight:500;
}
.cfg-info-item i { font-size:.65rem; }

/* ── Table config ────────────────────────── */
.cfg-th {
    font-size:.63rem; font-weight:700; letter-spacing:.04em;
    text-transform:uppercase; color:#7A9B6A;
    padding:8px 6px; white-space:nowrap;
}
.cfg-td {
    padding:8px 4px;
    border-bottom:1px solid #F0F5ED;
    transition: background .15s;
}
.cfg-tr:hover .cfg-td { background:#F7FCF4; }

.cfg-tipo-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 8px; border-radius:6px;
    font-size:.72rem; font-weight:600;
}

/* ── Counter ─────────────────────────────── */
.cfg-counter {
    display:inline-flex; align-items:center;
    background:#F0F5ED; border:1px solid #D5E4CB;
    border-radius:8px; overflow:hidden;
}
.cfg-counter button {
    width:28px; height:28px; border:none; background:transparent;
    color:#5C7A4E; font-size:.7rem; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    transition: background .15s;
}
.cfg-counter button:hover { background:#E5EDE0; }
.cfg-counter button:active { background:#D5E4CB; }
.cfg-counter input {
    width:32px; text-align:center; border:none; background:transparent;
    font-size:.8rem; font-weight:700; color:#3D5234;
    -moz-appearance:textfield;
}
.cfg-counter input::-webkit-outer-spin-button,
.cfg-counter input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }

/* ── Total badge ─────────────────────────── */
.cfg-total {
    display:inline-flex; align-items:center;
    background:rgba(92,122,78,.1); color:#3D5234;
    border:1px solid rgba(92,122,78,.22);
    padding:2px 10px; border-radius:20px;
    font-size:.72rem; font-weight:700;
}

/* ── Buttons ─────────────────────────────── */
.btn-cfg {
    display:inline-flex; align-items:center; justify-content:center;
    gap:6px; padding:8px 16px; border-radius:9px;
    font-size:.78rem; font-weight:700;
    transition: transform .2s, box-shadow .2s;
    text-decoration:none; border:none; cursor:pointer;
}
.btn-cfg:hover { transform:translateY(-1px); }
.btn-cfg.save {
    background:linear-gradient(135deg,#5C7A4E,#4A6340); color:#fff;
}
.btn-cfg.save:hover { box-shadow: 0 6px 16px rgba(74,99,64,.3); }
.btn-cfg.reset {
    background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff;
}
.btn-cfg.reset:hover { box-shadow: 0 6px 16px rgba(245,158,11,.3); }
.btn-cfg.back {
    background:#F0F5ED; color:#4A6340; border:1px solid #C8D8BE;
}
.btn-cfg.back:hover { background:#E5EDE0; box-shadow: 0 3px 10px rgba(92,122,78,.12); }

/* ── Product preview cards ───────────────── */
.cfg-preview-card {
    background:#fff; border:1px solid #E0EBD8;
    border-radius:10px; padding:12px;
    transition: transform .2s, box-shadow .2s;
}
.cfg-preview-card:hover {
    transform:translateY(-2px);
    box-shadow: 0 6px 16px rgba(92,122,78,.1);
}

/* ── Mobile: card-based config ───────────── */
.cfg-mobile-card {
    background:#fff; border:1px solid #E0EBD8;
    border-radius:10px; padding:10px 12px;
    margin-bottom:8px;
}
.cfg-mobile-card .tipo-label {
    display:flex; align-items:center; gap:6px;
    font-size:.78rem; font-weight:600; color:#3D5234;
    margin-bottom:8px; padding-bottom:6px;
    border-bottom:1px solid #F0F5ED;
}
.cfg-mobile-card .tipo-label i { font-size:.7rem; }
.cfg-mobile-row {
    display:flex; align-items:center; justify-content:space-between;
    padding:4px 0;
}
.cfg-mobile-row + .cfg-mobile-row { border-top:1px solid #FAFDF8; }
.cfg-mobile-row .prod-name {
    font-size:.72rem; color:#4A6340; font-weight:500;
    flex:1; min-width:0;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    margin-right:8px;
}

/* Show/hide based on screen */
@media (max-width: 639px) {
    .cfg-table-desktop { display:none !important; }
    .cfg-cards-mobile  { display:block !important; }
}
@media (min-width: 640px) {
    .cfg-cards-mobile  { display:none !important; }
}

/* ── Custom scrollbar ────────────────────── */
.lc-scroll::-webkit-scrollbar { width:4px; height:4px; }
.lc-scroll::-webkit-scrollbar-track { background:#F0F5ED; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb { background:#A8C4A0; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background:var(--lc-green); }

/* ── Prevent FOUC ────────────────────────── */
.config-view { opacity:0; transition:opacity .3s ease; }
.config-view.loaded { opacity:1; }
</style>

<div class="config-view config-page">

    <!-- Top Bar -->
    <div class="cfg-topbar">
        <div class="px-4 sm:px-5 lg:px-8 py-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div style="width:40px;height:40px;border-radius:11px;background:rgba(92,122,78,.12);color:#5C7A4E;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div>
                        <h1 class="text-base sm:text-lg font-bold text-[#3D5234] leading-tight">Configuración de Descuentos</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Descuento automático al check-in · <?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                <a href="<?= url('inventario') ?>" class="btn-cfg back">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span class="hidden sm:inline">Volver</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="px-4 sm:px-5 lg:px-8 py-5">

        <!-- Info Box -->
        <div class="cfg-info mb-4">
            <div class="flex items-start gap-3 mb-2">
                <i class="fas fa-info-circle text-[#5C7A4E] mt-0.5 flex-shrink-0"></i>
                <p class="text-xs text-[#3D5234]">
                    <strong>¿Cómo funciona?</strong> Configure cuántas unidades de cada producto se descuentan al hacer check-in, según el tipo de habitación.
                </p>
            </div>
            <div class="flex flex-wrap gap-2 ml-6">
                <div class="cfg-info-item"><i class="fas fa-check-circle"></i>Solo productos con auto-descuento</div>
                <div class="cfg-info-item"><i class="fas fa-sign-in-alt"></i>Se aplica una vez al check-in</div>
                <div class="cfg-info-item"><i class="fas fa-exclamation-triangle"></i>Sin stock = sin descuento</div>
            </div>
        </div>

        <?php if (empty($productos_automaticos)): ?>
            <div class="cfg-panel">
                <div class="text-center py-12 px-4">
                    <i class="fas fa-exclamation-triangle text-3xl mb-2" style="color:#D5E4CB"></i>
                    <p class="text-gray-500 text-sm mb-1 font-semibold">No hay productos configurados</p>
                    <p class="text-gray-400 text-xs mb-4">No hay productos marcados para descuento automático.</p>
                    <a href="<?= url('inventario') ?>" class="btn-cfg save text-xs">
                        <i class="fas fa-cog"></i> Configurar productos
                    </a>
                </div>
            </div>
        <?php else: ?>

        <form method="POST" action="<?= url('inventario/guardarConfiguracion') ?>" id="formConfiguracion">
            <?= csrf_field() ?>

            <!-- Config Panel -->
            <div class="cfg-panel mb-4">
                <div class="cfg-panel-hd">
                    <div class="flex items-center gap-2.5">
                        <div class="cfg-panel-hd-icon"><i class="fas fa-th text-white text-xs"></i></div>
                        <h3 class="text-sm font-bold text-white">Cantidad por Tipo de Habitación</h3>
                    </div>
                </div>

                <!-- ═══ DESKTOP TABLE ═══ -->
                <div class="cfg-table-desktop">
                    <div class="overflow-x-auto lc-scroll">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-[#EAF0E5]">
                                    <th class="cfg-th text-left pl-4 sticky left-0 bg-white z-10">Tipo Habitación</th>
                                    <?php foreach ($productos_automaticos as $producto): ?>
                                    <th class="cfg-th text-center" style="min-width:110px;">
                                        <div class="font-bold text-gray-600 text-[.65rem]"><?= htmlspecialchars($producto['nombre']) ?></div>
                                        <span class="inline-block mt-1 text-[.58rem] font-normal px-1.5 py-0.5 rounded-full bg-[#F0F5ED] text-[#5C7A4E]">
                                            Stock: <?= $producto['stock_actual'] ?>
                                        </span>
                                    </th>
                                    <?php endforeach; ?>
                                    <th class="cfg-th text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $colores_tipo = [
                                    'sencilla' => ['bg'=>'#EFF6FF','color'=>'#2563EB','icon'=>'fa-bed'],
                                    'doble' => ['bg'=>'#F0FDF4','color'=>'#16A34A','icon'=>'fa-bed'],
                                    'triple' => ['bg'=>'#FAF5FF','color'=>'#9333EA','icon'=>'fa-bed'],
                                    'cuadruple' => ['bg'=>'#FFF7ED','color'=>'#EA580C','icon'=>'fa-bed'],
                                    'sencilla_manolo' => ['bg'=>'#F0FDFA','color'=>'#0D9488','icon'=>'fa-bed'],
                                    'doble_manolo' => ['bg'=>'#ECFEFF','color'=>'#0891B2','icon'=>'fa-bed'],
                                    'doble_jacuzzi' => ['bg'=>'#FDF2F8','color'=>'#DB2777','icon'=>'fa-hot-tub'],
                                    'sencilla_jacuzzi' => ['bg'=>'#EEF2FF','color'=>'#4F46E5','icon'=>'fa-hot-tub'],
                                ];
                                foreach ($tipos_habitacion as $tipo_key => $tipo_nombre): 
                                    $tc = $colores_tipo[$tipo_key] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','icon'=>'fa-bed'];
                                ?>
                                <tr class="cfg-tr">
                                    <td class="cfg-td pl-4 sticky left-0 bg-white z-10">
                                        <span class="cfg-tipo-badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>">
                                            <i class="fas <?= $iconos[$tipo_key] ?? $tc['icon'] ?> text-[.65rem]"></i>
                                            <?= $tipo_nombre ?>
                                        </span>
                                    </td>
                                    <?php foreach ($productos_automaticos as $producto): ?>
                                        <?php
                                        $cantidad_actual = 0;
                                        if (isset($configuracion[$tipo_key])) {
                                            foreach ($configuracion[$tipo_key] as $config) {
                                                if ($config['producto_id'] == $producto['id']) {
                                                    $cantidad_actual = $config['cantidad_descontar'];
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                        <td class="cfg-td text-center">
                                            <div class="cfg-counter mx-auto">
                                                <button type="button" class="btn-decrease"
                                                        data-tipo="<?= $tipo_key ?>" data-producto="<?= $producto['id'] ?>">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number"
                                                       class="cantidad-input"
                                                       name="config[<?= $tipo_key ?>][<?= $producto['id'] ?>]"
                                                       value="<?= $cantidad_actual ?>"
                                                       min="0" max="10"
                                                       data-tipo="<?= $tipo_key ?>"
                                                       data-producto="<?= $producto['id'] ?>">
                                                <button type="button" class="btn-increase"
                                                        data-tipo="<?= $tipo_key ?>" data-producto="<?= $producto['id'] ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="cfg-td text-center">
                                        <span class="cfg-total total-row" data-tipo="<?= $tipo_key ?>">0</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Action buttons desktop -->
                    <div class="px-4 py-3 border-t border-[#EAF0E5] flex justify-between gap-3">
                        <button type="button" class="btn-cfg reset" id="btnReset">
                            <i class="fas fa-undo text-xs"></i> Restablecer a 0
                        </button>
                        <button type="submit" class="btn-cfg save">
                            <i class="fas fa-save text-xs"></i> Guardar Configuración
                        </button>
                    </div>
                </div>

                <!-- ═══ MOBILE CARDS ═══ -->
                <div class="cfg-cards-mobile p-3" style="display:none;">
                    <?php 
                    foreach ($tipos_habitacion as $tipo_key => $tipo_nombre): 
                        $tc = $colores_tipo[$tipo_key] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','icon'=>'fa-bed'];
                    ?>
                    <div class="cfg-mobile-card">
                        <div class="tipo-label">
                            <span style="width:22px;height:22px;border-radius:6px;background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;display:inline-flex;align-items:center;justify-content:center;font-size:.6rem;flex-shrink:0;">
                                <i class="fas <?= $iconos[$tipo_key] ?? $tc['icon'] ?>"></i>
                            </span>
                            <?= $tipo_nombre ?>
                            <span class="cfg-total total-row ml-auto" data-tipo="<?= $tipo_key ?>">0</span>
                        </div>
                        <?php foreach ($productos_automaticos as $producto): ?>
                            <?php
                            $cantidad_actual = 0;
                            if (isset($configuracion[$tipo_key])) {
                                foreach ($configuracion[$tipo_key] as $config) {
                                    if ($config['producto_id'] == $producto['id']) {
                                        $cantidad_actual = $config['cantidad_descontar'];
                                        break;
                                    }
                                }
                            }
                            ?>
                            <div class="cfg-mobile-row">
                                <span class="prod-name"><?= htmlspecialchars($producto['nombre']) ?></span>
                                <div class="cfg-counter">
                                    <button type="button" class="btn-decrease"
                                            data-tipo="<?= $tipo_key ?>" data-producto="<?= $producto['id'] ?>">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number"
                                           class="cantidad-input"
                                           name="config_m[<?= $tipo_key ?>][<?= $producto['id'] ?>]"
                                           value="<?= $cantidad_actual ?>"
                                           min="0" max="10"
                                           data-tipo="<?= $tipo_key ?>"
                                           data-producto="<?= $producto['id'] ?>">
                                    <button type="button" class="btn-increase"
                                            data-tipo="<?= $tipo_key ?>" data-producto="<?= $producto['id'] ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                    <!-- Action buttons mobile -->
                    <div class="flex gap-2 mt-3">
                        <button type="button" class="btn-cfg reset flex-1" id="btnResetMobile">
                            <i class="fas fa-undo text-xs"></i> Restablecer
                        </button>
                        <button type="submit" class="btn-cfg save flex-1">
                            <i class="fas fa-save text-xs"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Preview Panel -->
        <div class="cfg-panel">
            <div class="cfg-panel-hd">
                <div class="flex items-center gap-2.5">
                    <div class="cfg-panel-hd-icon"><i class="fas fa-eye text-white text-xs"></i></div>
                    <h3 class="text-sm font-bold text-white">Vista Previa de Consumo</h3>
                </div>
            </div>
            <div class="p-3 sm:p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php foreach ($productos_automaticos as $producto): ?>
                    <div class="cfg-preview-card">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-gray-800 truncate"><?= htmlspecialchars($producto['nombre']) ?></p>
                            <div style="width:26px;height:26px;border-radius:7px;background:rgba(92,122,78,.12);color:#5C7A4E;display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0;">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                        <div class="mb-2">
                            <p class="text-[.6rem] text-gray-400 uppercase tracking-wider mb-0.5">Consumo diario estimado</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-xl font-bold text-[#3D5234] consumo-diario" data-producto="<?= $producto['id'] ?>">0</span>
                                <span class="text-[.65rem] text-gray-400">uds</span>
                            </div>
                        </div>
                        <div class="mb-2">
                            <?php
                            $porcentaje_consumo = $producto['stock_actual'] > 0 ? 20 : 0;
                            ?>
                            <div class="w-full bg-[#E5EDE0] rounded-full h-1.5 overflow-hidden">
                                <div class="bg-gradient-to-r from-[#5C7A4E] to-[#7A9B6A] h-1.5 rounded-full transition-all duration-300"
                                     style="width:<?= $porcentaje_consumo ?>%"></div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center text-[.65rem]">
                            <span class="text-gray-400">Stock actual:</span>
                            <span class="font-semibold text-[#4A6340]"><?= $producto['stock_actual'] ?> uds</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<!-- JavaScript -->
<script>
// Sync mobile/desktop inputs
function syncInputs(tipo, productoId, value) {
    document.querySelectorAll(`input[data-tipo="${tipo}"][data-producto="${productoId}"]`).forEach(inp => {
        inp.value = value;
    });
    // Also sync the name-based mobile inputs
    const mobileInput = document.querySelector(`input[name="config_m[${tipo}][${productoId}]"]`);
    const desktopInput = document.querySelector(`input[name="config[${tipo}][${productoId}]"]`);
    if (mobileInput && desktopInput) {
        mobileInput.value = desktopInput.value = value;
    }
}

// Before submit: sync mobile values to desktop names
document.getElementById('formConfiguracion')?.addEventListener('submit', function(e) {
    // Copy mobile values to desktop inputs
    document.querySelectorAll('input[name^="config_m["]').forEach(mInput => {
        const name = mInput.name.replace('config_m[', 'config[');
        const dInput = document.querySelector(`input[name="${name}"]`);
        if (dInput) dInput.value = mInput.value;
    });

    let hayConfiguracion = false;
    document.querySelectorAll('input[name^="config["]').forEach(input => {
        if (parseInt(input.value) > 0) hayConfiguracion = true;
    });

    if (!hayConfiguracion) {
        e.preventDefault();
        if (!confirm('No hay ninguna configuración establecida. ¿Desea continuar?')) return false;
        this.submit();
    }
});

// Decrease / increase buttons
document.querySelectorAll('.btn-decrease').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        const value = parseInt(input.value) || 0;
        if (value > 0) {
            const newVal = value - 1;
            syncInputs(input.dataset.tipo, input.dataset.producto, newVal);
            actualizarTotales();
        }
    });
});

document.querySelectorAll('.btn-increase').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        const value = parseInt(input.value) || 0;
        if (value < 10) {
            const newVal = value + 1;
            syncInputs(input.dataset.tipo, input.dataset.producto, newVal);
            actualizarTotales();
        }
    });
});

document.querySelectorAll('.cantidad-input').forEach(input => {
    input.addEventListener('change', function() {
        syncInputs(this.dataset.tipo, this.dataset.producto, this.value);
        actualizarTotales();
    });
    input.addEventListener('input', function() {
        syncInputs(this.dataset.tipo, this.dataset.producto, this.value);
        actualizarTotales();
    });
});

function actualizarTotales() {
    <?php foreach ($tipos_habitacion as $tipo_key => $tipo_nombre): ?>
    let total_<?= $tipo_key ?> = 0;
    document.querySelectorAll(`input[data-tipo="<?= $tipo_key ?>"][name^="config["]`).forEach(input => {
        total_<?= $tipo_key ?> += parseInt(input.value) || 0;
    });
    document.querySelectorAll(`.total-row[data-tipo="<?= $tipo_key ?>"]`).forEach(el => {
        el.textContent = total_<?= $tipo_key ?>;
    });
    <?php endforeach; ?>

    actualizarConsumoDiario();
}

function actualizarConsumoDiario() {
    <?php foreach ($productos_automaticos as $producto): ?>
    let consumo_<?= $producto['id'] ?> = 0;
    document.querySelectorAll(`input[data-producto="<?= $producto['id'] ?>"][name^="config["]`).forEach(input => {
        consumo_<?= $producto['id'] ?> += parseInt(input.value) || 0;
    });
    consumo_<?= $producto['id'] ?> = Math.round(consumo_<?= $producto['id'] ?> * 0.5);
    document.querySelectorAll(`.consumo-diario[data-producto="<?= $producto['id'] ?>"]`).forEach(el => {
        el.textContent = consumo_<?= $producto['id'] ?>;
    });
    <?php endforeach; ?>
}

function resetAll() {
    if (confirm('¿Está seguro de restablecer toda la configuración a 0?')) {
        document.querySelectorAll('.cantidad-input').forEach(input => { input.value = 0; });
        actualizarTotales();
    }
}

document.getElementById('btnReset')?.addEventListener('click', resetAll);
document.getElementById('btnResetMobile')?.addEventListener('click', resetAll);

// Init
document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.config-view');
    if (view) view.classList.add('loaded');
    actualizarTotales();
});
</script>

<?php require_once APP_PATH . '/views/layout/footer.php'; ?>
