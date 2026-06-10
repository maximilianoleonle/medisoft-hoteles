<?php
header('Content-Type: text/html; charset=UTF-8');
/**
 * Vista Principal de Facturación
 * Paleta hotelera boutique
 */

$solicitudes  = $solicitudes  ?? [];
$estadisticas = $estadisticas ?? [];
$mensaje      = get_mensaje();
?>

<style>
/* ══════════════════════════════════════════
   Facturación
   Sage green / gold / cream palette
   ══════════════════════════════════════════ */
:root {
    --lc-green:       #5C7A4E;
    --lc-green-dark:  #4A6340;
    --lc-green-deep:  #3D5234;
    --lc-green-light: #7A9B6A;
    --lc-gold:        #C8A96A;
    --lc-gold-dark:   #B8994A;
    --lc-cream:       #F7F4EE;
    --lc-cream-mid:   #EEE9DE;
}

/* ── Page ────────────────────────────────── */
.facturacion-view {
    min-height: 100vh;
    background: linear-gradient(145deg, #EFF4EC 0%, #E8EEE3 50%, #F4F1EC 100%);
    animation: fadeIn .35s ease forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Header ──────────────────────────────── */
.fact-header {
    background: linear-gradient(135deg, #3D5234 0%, #4A6340 55%, #5C7A4E 100%);
    color: white;
    padding: 1rem 0;
    box-shadow: 0 4px 18px rgba(61,82,52,.28);
    position: sticky;
    top: 0;
    z-index: 40;
    overflow: hidden;
}
.fact-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(200,169,106,.08);
    pointer-events: none;
}
.fact-header a { color: var(--lc-gold); transition: color .2s; text-decoration: none; }
.fact-header a:hover { color: white; }

/* ── Stat cards ──────────────────────────── */
.stat-card {
    background: white;
    border-radius: 14px;
    padding: 18px 20px;
    border: 1px solid #DDE8D5;
    transition: transform .25s, box-shadow .25s;
    position: relative;
    overflow: hidden;
}
.stat-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2px;
    opacity: 0;
    transition: opacity .25s;
    background: var(--sc-accent, #5C7A4E);
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(92,122,78,.11); }
.stat-card:hover::after { opacity: 1; }

.stat-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

/* ── Filter card ─────────────────────────── */
.filter-card {
    background: white;
    border-radius: 14px;
    border: 1px solid #DDE8D5;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(92,122,78,.06);
    transition: box-shadow .25s;
}
.filter-card:hover { box-shadow: 0 4px 18px rgba(92,122,78,.09); }

.filter-hd {
    padding: 12px 18px;
    border-bottom: 1px solid #EAF0E5;
    background: linear-gradient(135deg, rgba(92,122,78,.06), rgba(92,122,78,.02));
    display: flex; align-items: center; justify-content: space-between;
}

/* ── Inputs ──────────────────────────────── */
.lc-input {
    width: 100%;
    padding: 8px 10px;
    border: 1.5px solid #C8D9BE;
    border-radius: 8px;
    font-size: .8rem;
    background: #FAFDF8;
    color: #374151;
    transition: border-color .2s, box-shadow .2s;
}
.lc-input:focus {
    outline: none;
    border-color: var(--lc-gold);
    box-shadow: 0 0 0 3px rgba(200,169,106,.15);
}
.filter-label {
    display: block;
    font-size: .68rem;
    font-weight: 700;
    color: #5C7A4E;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 4px;
}

/* ── Table ───────────────────────────────── */
.fact-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.fact-table thead th {
    background: #F0F5ED;
    padding: 10px 14px;
    font-size: .67rem;
    font-weight: 700;
    color: #7A9B6A;
    text-transform: uppercase;
    letter-spacing: .05em;
    border-bottom: 2px solid #DDE8D5;
    text-align: left;
    white-space: nowrap;
}
.fact-table tbody tr {
    transition: background .15s;
    border-bottom: 1px solid #F0F5ED;
}
.fact-table tbody tr:hover { background: #F7FCF4; }
.fact-table tbody td {
    padding: 12px 14px;
    font-size: .8rem;
    color: #1F2937;
    vertical-align: middle;
}

/* ── Badges ──────────────────────────────── */
.badge {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: .67rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .03em;
}
.badge-cliente     { background: linear-gradient(135deg,#EFF6FF,#DBEAFE); color:#1E40AF; border:1px solid #93C5FD; }
.badge-uso-interno { background: linear-gradient(135deg,#F5F3FF,#EDE9FE); color:#6D28D9; border:1px solid #C4B5FD; }
.badge-pendiente   { background: linear-gradient(135deg,#FFFBEB,#FEF3C7); color:#92400E; border:1px solid #FDE68A; }
.badge-en-proceso  { background: linear-gradient(135deg,#EFF6FF,#DBEAFE); color:#1E40AF; border:1px solid #93C5FD; }
.badge-completada  { background: rgba(92,122,78,.1);                       color:#3D5234; border:1px solid rgba(92,122,78,.25); }
.badge-cancelada   { background: #F9FAFB;                                   color:#6B7280; border:1px solid #D1D5DB; }

/* ── Action button ───────────────────────── */
.btn-ver {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: .75rem; font-weight: 700;
    border: none; cursor: pointer; text-decoration: none;
    background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark));
    color: white;
    box-shadow: 0 2px 6px rgba(92,122,78,.22);
    transition: transform .2s, box-shadow .2s;
}
.btn-ver:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(92,122,78,.32);
    color: white;
}

/* ── Filter buttons ──────────────────────── */
.btn-filter {
    padding: 8px 14px;
    border-radius: 8px;
    font-size: .78rem; font-weight: 700;
    border: none; cursor: pointer;
    background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark));
    color: white;
    display: inline-flex; align-items: center; gap: 5px;
    transition: box-shadow .2s, transform .2s;
    box-shadow: 0 2px 8px rgba(92,122,78,.22);
}
.btn-filter:hover { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(92,122,78,.3); }

.btn-clear {
    padding: 8px 12px;
    border-radius: 8px;
    font-size: .78rem; font-weight: 700;
    border: 1.5px solid #D5E4CB;
    background: rgba(92,122,78,.06);
    color: #4A6340; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px;
    transition: background .15s;
    text-decoration: none;
}
.btn-clear:hover { background: rgba(92,122,78,.12); }

/* ── Flash messages ──────────────────────── */
.flash-msg {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: .875rem; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 16px;
    animation: slideDown .3s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.flash-success { background: rgba(92,122,78,.08);   color: #3D5234; border: 1.5px solid rgba(92,122,78,.3); border-left: 4px solid var(--lc-green); }
.flash-error   { background: #FEF2F2;                color: #991B1B; border: 1.5px solid #FCA5A5;           border-left: 4px solid #EF4444; }
.flash-info    { background: #EFF6FF;                color: #1E40AF; border: 1.5px solid #93C5FD;           border-left: 4px solid #3B82F6; }

/* ── Empty state ─────────────────────────── */
.empty-state {
    text-align: center;
    padding: 48px 16px;
    color: #9CA3AF;
}
.empty-state-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: rgba(92,122,78,.07);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    font-size: 28px;
    color: #A8C4A0;
}

/* ── Pagination ──────────────────────────── */
.pagination { display: flex; gap: 3px; align-items: center; flex-wrap: wrap; justify-content: center; }
.page-btn {
    padding: 5px 11px;
    border-radius: 7px;
    font-size: .75rem; font-weight: 600;
    border: 1.5px solid #D5E4CB;
    background: white; color: #4A6340;
    cursor: pointer; text-decoration: none;
    transition: border-color .15s, background .15s;
}
.page-btn:hover    { border-color: var(--lc-green); background: #F0F5ED; }
.page-btn.active   { background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark)); color: white; border-color: transparent; }
.page-btn.disabled { opacity: .4; pointer-events: none; }

/* ── RFC badge ───────────────────────────── */
.rfc-badge {
    font-family: monospace;
    font-size: .72rem; font-weight: 700;
    background: #F0F5ED;
    color: #3D5234;
    padding: 2px 7px;
    border-radius: 5px;
    border: 1px solid #C8D9BE;
    letter-spacing: .03em;
}

/* ── ID badge ────────────────────────────── */
.id-badge {
    font-weight: 800;
    color: var(--lc-green-deep);
    font-size: .82rem;
}

/* ── Reservation link ────────────────────── */
.res-link { color: #2563EB; font-weight: 700; text-decoration: none; }
.res-link:hover { text-decoration: underline; }

/* ── Scrollbar ───────────────────────────── */
.lc-scroll::-webkit-scrollbar { height: 4px; width: 4px; }
.lc-scroll::-webkit-scrollbar-track { background: #F0F5ED; }
.lc-scroll::-webkit-scrollbar-thumb { background: #A8C4A0; border-radius: 4px; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background: var(--lc-green); }

/* ── Responsive ──────────────────────────── */
@media (max-width: 1024px) {
    .fact-header { position: relative; }
    .stats-grid { grid-template-columns: repeat(2,1fr) !important; }
    .filter-grid { grid-template-columns: 1fr 1fr !important; }
    .filter-grid .filter-actions { grid-column: 1 / -1; }
}

@media (max-width: 640px) {
    .stats-grid { grid-template-columns: repeat(2,1fr) !important; gap: .625rem !important; margin-bottom: 1rem !important; }
    .stat-card { padding: 14px; border-radius: 11px; }
    .stat-icon { width: 36px; height: 36px; font-size: 14px; border-radius: 9px; }
    .fact-header h1 { font-size: 1rem !important; }
    .fact-header p  { display: none; }
    .filter-grid { grid-template-columns: 1fr !important; gap: .625rem !important; }
    .filter-card form { padding: 12px !important; }
    .filter-actions { flex-direction: row !important; }
    .filter-actions button, .filter-actions a { flex: 1; justify-content: center; }
    .filter-toggle-btn { display: flex !important; }

    /* Table → Cards on mobile */
    .fact-table thead { display: none; }
    .fact-table tbody tr {
        display: block;
        padding: 12px;
        margin-bottom: 10px;
        background: white;
        border-radius: 11px;
        border: 1.5px solid #DDE8D5;
        box-shadow: 0 1px 4px rgba(92,122,78,.06);
    }
    .fact-table tbody tr:hover { background: white; }
    .fact-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px dashed #EAF0E5;
    }
    .fact-table tbody td:last-child { border-bottom: none; padding-top: 10px; }
    .fact-table tbody td::before {
        content: attr(data-label);
        font-weight: 700; color: #7A9B6A;
        font-size: .67rem; text-transform: uppercase;
        flex-shrink: 0; margin-right: 10px;
    }
    .fact-table tbody td:last-child .btn-ver { width: 100%; justify-content: center; padding: 8px; }
    .flash-msg { font-size: .8125rem; padding: 10px 12px; }
    .empty-state { padding: 32px 12px; }
}

@media (max-width: 380px) {
    .stats-grid { grid-template-columns: 1fr !important; }
    .fact-header h1 { font-size: .9rem !important; }
}
</style>

<!-- ═══════════════════ FACTURACIÓN ══════════════════════════ -->
<div class="facturacion-view">

    <!-- ── Header ── -->
    <div class="fact-header">
        <div class="container mx-auto px-4 relative z-10">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="/habitaciones"
                       style="background:rgba(255,255,255,.12);width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;transition:background .2s;"
                       onmouseover="this.style.background='rgba(255,255,255,.22)'"
                       onmouseout="this.style.background='rgba(255,255,255,.12)'">
                        <i class="fas fa-arrow-left text-sm text-white"></i>
                    </a>
                    <div style="width:1px;height:30px;background:rgba(255,255,255,.15);"></div>
                    <div>
                        <h1 class="text-xl font-bold flex items-center gap-2" style="line-height:1.2;">
                            <i class="fas fa-file-invoice" style="color:var(--lc-gold);opacity:.9;"></i>
                            Facturación
                        </h1>
                        <p style="font-size:.72rem;color:rgba(255,255,255,.6);margin-top:1px;">
                            Gestión de solicitudes de factura · <?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:10px;">
                    <!-- Live badge -->
                    <span style="display:flex;align-items:center;gap:5px;background:rgba(200,169,106,.18);border:1px solid rgba(200,169,106,.35);color:var(--lc-gold);padding:4px 10px;border-radius:20px;font-size:.7rem;font-weight:700;">
                        <i class="fas fa-clock text-xs"></i>
                        <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-5" style="max-width:1200px;">

        <!-- ── Flash ── -->
        <?php if ($mensaje): ?>
            <div class="flash-msg flash-<?= $mensaje['tipo'] ?>">
                <i class="fas fa-<?= $mensaje['tipo'] === 'success' ? 'check-circle' : ($mensaje['tipo'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <?= htmlspecialchars($mensaje['texto']) ?>
            </div>
        <?php endif; ?>

        <!-- ── Stat widgets ── -->
        <div class="stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">

            <!-- Pendientes -->
            <div class="stat-card" style="--sc-accent:#F59E0B;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                    <div>
                        <p style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#B45309;">Pendientes</p>
                        <p style="font-size:2rem;font-weight:800;color:#92400E;line-height:1.1;margin-top:4px;"><?= $estadisticas['pendientes'] ?? 0 ?></p>
                        <div style="display:flex;gap:10px;margin-top:5px;">
                            <span style="font-size:.67rem;color:#B45309;display:flex;align-items:center;gap:3px;">
                                <i class="fas fa-user" style="font-size:.6rem;"></i>
                                <?= $estadisticas['pendientes_cliente'] ?? 0 ?> cliente
                            </span>
                            <span style="font-size:.67rem;color:#B45309;display:flex;align-items:center;gap:3px;">
                                <i class="fas fa-building" style="font-size:.6rem;"></i>
                                <?= $estadisticas['pendientes_interno'] ?? 0 ?> interno
                            </span>
                        </div>
                    </div>
                    <div class="stat-icon" style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);color:#D97706;">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- En Proceso -->
            <div class="stat-card" style="--sc-accent:#3B82F6;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                    <div>
                        <p style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#1E40AF;">En Proceso</p>
                        <p style="font-size:2rem;font-weight:800;color:#1E40AF;line-height:1.1;margin-top:4px;"><?= $estadisticas['en_proceso'] ?? 0 ?></p>
                        <p style="font-size:.67rem;color:#3B82F6;margin-top:5px;">Con datos fiscales</p>
                    </div>
                    <div class="stat-icon" style="background:linear-gradient(135deg,#DBEAFE,#BFDBFE);color:#2563EB;">
                        <i class="fas fa-spinner"></i>
                    </div>
                </div>
            </div>

            <!-- Completadas mes -->
            <div class="stat-card" style="--sc-accent:#5C7A4E;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                    <div>
                        <p style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#3D5234;">Facturadas (Mes)</p>
                        <p style="font-size:2rem;font-weight:800;color:#3D5234;line-height:1.1;margin-top:4px;"><?= $estadisticas['completadas_mes'] ?? 0 ?></p>
                        <p style="font-size:.67rem;color:var(--lc-green-light);margin-top:5px;"><?= date('F Y') ?></p>
                    </div>
                    <div class="stat-icon" style="background:rgba(92,122,78,.12);color:#3D5234;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <!-- Monto pendiente -->
            <div class="stat-card" style="--sc-accent:#9333EA;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                    <div>
                        <p style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6D28D9;">Monto Pendiente</p>
                        <p style="font-size:1.5rem;font-weight:800;color:#6D28D9;line-height:1.1;margin-top:4px;">
                            $<?= number_format($estadisticas['monto_pendiente'] ?? 0, 2) ?>
                        </p>
                        <p style="font-size:.67rem;color:#7C3AED;margin-top:5px;">Por facturar</p>
                    </div>
                    <div class="stat-icon" style="background:linear-gradient(135deg,#EDE9FE,#DDD6FE);color:#7C3AED;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Filters ── -->
        <div class="filter-card" style="margin-bottom:16px;">
            <div class="filter-hd">
                <h3 style="font-size:.82rem;font-weight:700;color:#3D5234;display:flex;align-items:center;gap:6px;">
                    <div style="width:22px;height:22px;border-radius:6px;background:rgba(92,122,78,.1);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-filter" style="font-size:.6rem;color:var(--lc-green);"></i>
                    </div>
                    Filtros
                </h3>
                <!-- Mobile toggle -->
                <button type="button" class="filter-toggle-btn"
                        onclick="this.closest('.filter-card').classList.toggle('filters-open')"
                        style="display:none;background:rgba(92,122,78,.08);border:1.5px solid #D5E4CB;border-radius:7px;padding:4px 10px;font-size:.72rem;color:#4A6340;cursor:pointer;font-weight:700;align-items:center;gap:4px;">
                    <i class="fas fa-chevron-down" style="font-size:.6rem;"></i> Mostrar
                </button>
            </div>

            <form method="GET" action="<?= url('facturacion') ?>" class="filter-form" style="padding:14px 18px;">
                <div class="filter-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:10px;align-items:end;">

                    <div>
                        <label class="filter-label">Buscar</label>
                        <input type="text" name="buscar"
                               value="<?= htmlspecialchars($buscar ?? '') ?>"
                               placeholder="Nombre, RFC, # reservación..."
                               class="lc-input">
                    </div>

                    <div>
                        <label class="filter-label">Tipo</label>
                        <select name="tipo" class="lc-input">
                            <option value="">Todos</option>
                            <option value="cliente"      <?= ($filtro_tipo ?? '') === 'cliente'      ? 'selected' : '' ?>>Cliente</option>
                            <option value="uso_interno"  <?= ($filtro_tipo ?? '') === 'uso_interno'  ? 'selected' : '' ?>>Público General</option>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label">Estatus</label>
                        <select name="estatus" class="lc-input">
                            <option value="">Todos</option>
                            <option value="pendiente"   <?= ($filtro_estatus ?? '') === 'pendiente'   ? 'selected' : '' ?>>Pendiente</option>
                            <option value="en_proceso"  <?= ($filtro_estatus ?? '') === 'en_proceso'  ? 'selected' : '' ?>>En Proceso</option>
                            <option value="completada"  <?= ($filtro_estatus ?? '') === 'completada'  ? 'selected' : '' ?>>Completada</option>
                            <option value="cancelada"   <?= ($filtro_estatus ?? '') === 'cancelada'   ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label">Desde</label>
                        <input type="date" name="fecha_desde"
                               value="<?= htmlspecialchars($fecha_desde ?? '') ?>"
                               class="lc-input">
                    </div>

                    <div class="filter-actions" style="display:flex;gap:6px;">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search text-xs"></i> Filtrar
                        </button>
                        <a href="<?= url('facturacion') ?>" class="btn-clear">
                            <i class="fas fa-times text-xs"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- ── Table panel ── -->
        <div class="filter-card">
            <!-- Panel header -->
            <div style="padding:12px 18px;border-bottom:1px solid #EAF0E5;background:linear-gradient(135deg,#5C7A4E,#4A6340);display:flex;align-items:center;justify-content:space-between;">
                <h3 style="font-size:.85rem;font-weight:700;color:white;display:flex;align-items:center;gap:8px;">
                    <div style="background:rgba(255,255,255,.18);width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-list" style="font-size:.65rem;color:white;"></i>
                    </div>
                    Solicitudes de Factura
                </h3>
                <span style="background:rgba(255,255,255,.2);color:white;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                    <?= $total_registros ?? 0 ?> registro<?= ($total_registros ?? 0) != 1 ? 's' : '' ?>
                </span>
            </div>

            <?php if (empty($solicitudes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <p style="font-size:.95rem;font-weight:700;color:#6B7280;margin-bottom:4px;">No hay solicitudes de factura</p>
                    <p style="font-size:.8rem;color:#9CA3AF;">Las solicitudes se generan automáticamente al hacer check-in</p>
                </div>

            <?php else: ?>
                <div style="overflow-x:auto;" class="lc-scroll">
                    <table class="fact-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reservación</th>
                                <th>Huésped</th>
                                <th>Tipo</th>
                                <th>Estatus</th>
                                <th>Monto</th>
                                
                                <th>RFC</th>
                                <th>Fecha</th>
                                <th style="text-align:center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes as $sol): ?>
                            <tr>
                                <td data-label="ID">
                                    <span class="id-badge"><?= $sol['id'] ?></span>
                                </td>

                                <td data-label="Reservación">
                                    <a href="<?= url('reservaciones/ver/' . $sol['reservacion_id']) ?>" class="res-link">
                                        #<?= $sol['reservacion_id'] ?>
                                    </a>
                                </td>

                                <td data-label="Huésped">
                                    <span style="font-weight:600;color:#1F2937;"><?= htmlspecialchars($sol['huesped_nombre']) ?></span>
                                    <?php if (!empty($sol['huesped_telefono'])): ?>
                                        <br>
                                        <span style="font-size:.68rem;color:#9CA3AF;display:flex;align-items:center;gap:3px;margin-top:2px;">
                                            <i class="fas fa-phone" style="font-size:.55rem;"></i>
                                            <?= htmlspecialchars($sol['huesped_telefono']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Tipo">
                                    <?php if ($sol['tipo'] === 'cliente'): ?>
                                        <span class="badge badge-cliente">
                                            <i class="fas fa-user" style="font-size:.55rem;"></i> Cliente
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-uso-interno">
                                            <i class="fas fa-building" style="font-size:.55rem;"></i> Público General
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Estatus">
                                    <?php
                                    $iconos = [
                                        'pendiente'  => 'clock',
                                        'en_proceso' => 'spinner',
                                        'completada' => 'check-circle',
                                        'cancelada'  => 'times-circle',
                                    ];
                                    $labels = [
                                        'pendiente'  => 'Pendiente',
                                        'en_proceso' => 'En Proceso',
                                        'completada' => 'Completada',
                                        'cancelada'  => 'Cancelada',
                                    ];
                                    ?>
                                    <span class="badge badge-<?= $sol['estatus'] ?>">
                                        <i class="fas fa-<?= $iconos[$sol['estatus']] ?? 'circle' ?>" style="font-size:.55rem;"></i>
                                        <?= $labels[$sol['estatus']] ?? $sol['estatus'] ?>
                                    </span>
                                </td>

                                <td data-label="Monto">
                                    <span style="font-weight:700;color:#3D5234;">$<?= number_format($sol['monto_total'], 2) ?></span>
                                </td>


                                <td data-label="RFC">
                                    <?php if (!empty($sol['rfc'])): ?>
                                        <span class="rfc-badge"><?= htmlspecialchars($sol['rfc']) ?></span>
                                    <?php else: ?>
                                        <span style="font-size:.72rem;color:#D1D5DB;font-style:italic;">Sin RFC</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Fecha" style="white-space:nowrap;">
                                    <span style="font-size:.78rem;font-weight:600;color:#374151;"><?= date('d/m/Y', strtotime($sol['created_at'])) ?></span>
                                    <br>
                                    <span style="font-size:.68rem;color:#9CA3AF;"><?= date('H:i', strtotime($sol['created_at'])) ?></span>
                                </td>

                                <td data-label="Acción" style="text-align:center;">
                                    <a href="<?= url('facturacion/ver/' . $sol['id']) ?>" class="btn-ver">
                                        <i class="fas fa-eye text-xs"></i> Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (($total_paginas ?? 1) > 1): ?>
                    <div style="padding:14px 18px;border-top:1px solid #EAF0E5;display:flex;align-items:center;justify-content:center;">
                        <div class="pagination">
                            <?php if ($pagina_actual > 1): ?>
                                <a href="<?= url('facturacion?' . http_build_query(array_merge($_GET, ['page' => $pagina_actual - 1]))) ?>" class="page-btn">
                                    <i class="fas fa-chevron-left" style="font-size:.65rem;"></i>
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                                <a href="<?= url('facturacion?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"
                                   class="page-btn <?= $i === $pagina_actual ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($pagina_actual < $total_paginas): ?>
                                <a href="<?= url('facturacion?' . http_build_query(array_merge($_GET, ['page' => $pagina_actual + 1]))) ?>" class="page-btn">
                                    <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
// Filtros colapsables en mobile
(function() {
    if (window.innerWidth > 640) return;

    const filterCards = document.querySelectorAll('.filter-card');
    filterCards.forEach(card => {
        const form = card.querySelector('.filter-form');
        if (!form) return;

        const inputs  = form.querySelectorAll('input[type="text"], input[type="date"]');
        const selects = form.querySelectorAll('select');
        let hasActive = false;

        inputs.forEach(i  => { if (i.value)  hasActive = true; });
        selects.forEach(s => { if (s.value)  hasActive = true; });

        if (!hasActive) {
            form.style.display = 'none';
            card.classList.remove('filters-open');
        } else {
            card.classList.add('filters-open');
        }
    });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.filter-toggle-btn');
        if (!btn) return;
        const card  = btn.closest('.filter-card');
        const form  = card.querySelector('.filter-form');
        if (!form) return;
        const isOpen = card.classList.contains('filters-open');
        form.style.display = isOpen ? 'none' : 'block';
        card.classList.toggle('filters-open', !isOpen);
        btn.innerHTML = isOpen
            ? '<i class="fas fa-chevron-down" style="font-size:.6rem;"></i> Mostrar'
            : '<i class="fas fa-chevron-up" style="font-size:.6rem;"></i> Ocultar';
    });
})();
</script>
