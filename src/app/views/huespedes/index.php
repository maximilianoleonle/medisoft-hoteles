<?php
$huespedes = $huespedes ?? [];
$estados = $estados ?? [];
$guestRows = [];
$vehiculoModel = !empty($huespedes) ? new HuespedVehiculo() : null;

foreach ($huespedes as $huesped) {
    $vehiculos = $vehiculoModel ? $vehiculoModel->porHuesped($huesped['id']) : [];
    $guestRows[] = [
        'huesped' => $huesped,
        'vehiculos' => $vehiculos,
        'total_vehiculos' => count($vehiculos),
    ];
}

$huespedesVisibles = count($guestRows);
$origenesVisibles = count(array_filter(array_unique(array_column($huespedes, 'procedencia_estado'))));
?>

<style>
.guests-page {
    --guest-brand: var(--brand-primary, #2563EB);
    --guest-brand-dark: color-mix(in srgb, var(--guest-brand), #000 26%);
    --guest-brand-soft: color-mix(in srgb, var(--guest-brand) 9%, #F8FAFC);
    --guest-brand-softer: color-mix(in srgb, var(--guest-brand) 5%, #FFFFFF);
    --guest-accent: var(--brand-accent, #F59E0B);
    --guest-border: color-mix(in srgb, var(--guest-brand) 14%, #E2E8F0);
    --guest-ring: color-mix(in srgb, var(--guest-brand) 22%, transparent);
    --guest-text: #0F172A;
    --guest-muted: #64748B;
    color: var(--guest-text);
}

.guest-shell {
    display: grid;
    gap: 14px;
}

.guest-hero {
    background:
        radial-gradient(circle at right top, rgba(255,255,255,.16), transparent 34%),
        linear-gradient(135deg, var(--guest-brand-dark), var(--guest-brand));
    border-radius: 14px;
    padding: 14px;
    box-shadow: 0 16px 34px color-mix(in srgb, var(--guest-brand) 16%, transparent);
    overflow: hidden;
}

.guest-hero-icon,
.guest-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.guest-hero-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(255,255,255,.16);
    color: #fff;
    border: 1px solid rgba(255,255,255,.16);
}

.guest-primary-btn,
.guest-filter-btn,
.guest-reset-btn,
.guest-action,
.guest-card-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-height: 38px;
    border-radius: 10px;
    font-weight: 800;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
}

.guest-primary-btn {
    padding: .55rem .85rem;
    background: #fff;
    color: var(--guest-brand-dark);
    box-shadow: 0 10px 22px rgba(15,23,42,.14);
    white-space: nowrap;
}

.guest-primary-btn:hover,
.guest-filter-btn:hover,
.guest-card-action:hover,
.guest-action:hover {
    transform: translateY(-1px);
}

.guest-summary-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.guest-summary-item,
.guest-panel,
.guest-card {
    background: rgba(255,255,255,.96);
    border: 1px solid var(--guest-border);
    box-shadow: 0 8px 24px rgba(15,23,42,.045);
}

.guest-summary-item {
    border-radius: 12px;
    padding: 11px 12px;
}

.guest-summary-label {
    color: var(--guest-muted);
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.guest-summary-value {
    margin-top: 2px;
    color: var(--guest-text);
    font-size: 1.15rem;
    font-weight: 900;
    line-height: 1.15;
}

.guest-panel {
    border-radius: 14px;
}

.guest-filter-form {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) minmax(190px, 260px) auto;
    gap: 10px;
    align-items: end;
}

.guest-control {
    width: 100%;
    min-height: 38px;
    border-color: var(--guest-border) !important;
    background: color-mix(in srgb, var(--guest-brand) 3%, #fff);
    border-radius: 10px;
}

.guest-control:focus {
    border-color: var(--guest-brand) !important;
    box-shadow: 0 0 0 3px var(--guest-ring) !important;
    outline: none !important;
}

.guest-filter-btn {
    padding: .5rem .8rem;
    background: linear-gradient(135deg, var(--guest-brand), var(--guest-brand-dark));
    color: #fff;
    box-shadow: 0 10px 20px color-mix(in srgb, var(--guest-brand) 18%, transparent);
}

.guest-reset-btn {
    padding: .5rem .8rem;
    background: #F1F5F9;
    color: #475569;
}

.guest-count-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .36rem .62rem;
    border-radius: 999px;
    background: var(--guest-brand-soft);
    color: var(--guest-brand-dark);
    font-size: .72rem;
    font-weight: 800;
}

.guest-desktop-table {
    display: none;
}

.guest-table {
    width: 100%;
    table-layout: fixed;
}

.guest-table thead {
    background: #F8FAFC;
    border-bottom: 1px solid var(--guest-border);
}

.guest-table th {
    padding: 12px 16px;
    color: var(--guest-muted);
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .055em;
    text-align: left;
    text-transform: uppercase;
}

.guest-table td {
    padding: 13px 16px;
    vertical-align: middle;
}

.guest-row {
    border-bottom: 1px solid #EEF2F7;
    transition: background .16s ease;
}

.guest-row:hover {
    background: var(--guest-brand-softer);
}

.guest-id,
.guest-muted {
    color: var(--guest-muted);
    font-size: .74rem;
}

.guest-name {
    color: var(--guest-text);
    font-size: .9rem;
    font-weight: 900;
    line-height: 1.25;
}

.guest-contact-line,
.guest-meta-line {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: .45rem;
    color: #334155;
    font-size: .8rem;
    line-height: 1.35;
}

.guest-contact-line span,
.guest-meta-line span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.guest-cell-stack {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 4px;
}

.guest-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    width: fit-content;
    max-width: 100%;
    padding: .32rem .55rem;
    border-radius: 999px;
    background: #F8FAFC;
    color: #475569;
    border: 1px solid #E2E8F0;
    font-size: .72rem;
    font-weight: 800;
}

.guest-chip-brand {
    background: var(--guest-brand-soft);
    color: var(--guest-brand-dark);
    border-color: color-mix(in srgb, var(--guest-brand) 20%, #E2E8F0);
}

.guest-chip-warning {
    background: #FEF3C7;
    color: #92400E;
    border-color: #FDE68A;
}

.guest-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--guest-brand), var(--guest-brand-dark));
    color: #fff;
    font-size: .95rem;
    font-weight: 900;
    box-shadow: 0 10px 22px color-mix(in srgb, var(--guest-brand) 18%, transparent);
}

.guest-action {
    width: 34px;
    height: 34px;
    background: #F8FAFC;
    color: #475569;
}

.guest-action-view { color: #2563EB; }
.guest-action-edit { color: #B45309; }
.guest-action-book { color: #047857; }
.guest-action-view:hover { background: #DBEAFE; }
.guest-action-edit:hover { background: #FEF3C7; }
.guest-action-book:hover { background: #D1FAE5; }

.guest-mobile-list {
    display: grid;
    gap: 10px;
}

.guest-card {
    border-radius: 14px;
    padding: 12px;
}

.guest-card-top {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 10px;
    align-items: start;
}

.guest-card-title {
    min-width: 0;
}

.guest-card-contact {
    display: grid;
    gap: 5px;
    margin-top: 9px;
}

.guest-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}

.guest-card-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 7px;
    margin-top: 11px;
}

.guest-card-action {
    min-height: 34px;
    padding: .45rem .4rem;
    border: 1px solid #E2E8F0;
    background: #fff;
    color: #334155;
    font-size: .75rem;
}

.guest-card-action.primary {
    background: var(--guest-brand);
    border-color: var(--guest-brand);
    color: #fff;
}

.guest-empty {
    border-radius: 14px;
    border: 1px solid var(--guest-border);
    background: linear-gradient(180deg, #fff, var(--guest-brand-soft));
}

.guest-empty-icon {
    display: grid;
    place-items: center;
}

.guest-pagination {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 6px;
}

.guest-page-link,
.guest-page-current {
    min-width: 36px;
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    background: #fff;
    color: #475569;
    font-size: .85rem;
    font-weight: 800;
}

.guest-page-current {
    border-color: var(--guest-brand);
    background: var(--guest-brand);
    color: #fff;
}

@media (min-width: 768px) {
    .guest-desktop-table {
        display: block;
    }

    .guest-mobile-list {
        display: none;
    }
}

@media (max-width: 767px) {
    .guests-page {
        padding-left: 12px !important;
        padding-right: 12px !important;
    }

    .guest-hero {
        padding: 13px;
    }

    .guest-summary-strip {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 7px;
    }

    .guest-summary-item {
        padding: 9px 8px;
    }

    .guest-summary-label {
        font-size: .6rem;
    }

    .guest-summary-value {
        font-size: .95rem;
    }

    .guest-filter-form {
        grid-template-columns: 1fr;
    }

    .guest-filter-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .guest-primary-btn {
        width: 100%;
    }
}
</style>

<div class="guests-page p-4 sm:p-6">
    <div class="guest-shell">
        <section class="guest-hero">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="guest-hero-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-xl md:text-2xl font-extrabold text-white leading-tight">Huéspedes</h1>
                        <p class="text-white/78 mt-1 text-sm max-w-2xl">Directorio operativo para contacto, origen, visitas y nueva reservación.</p>
                    </div>
                </div>

                <a href="<?= url('huespedes/create') ?>" class="guest-primary-btn">
                    <i class="fas fa-user-plus"></i>
                    Nuevo huésped
                </a>
            </div>
        </section>

        <section class="guest-summary-strip" aria-label="Resumen de huéspedes">
            <div class="guest-summary-item">
                <p class="guest-summary-label">Total</p>
                <p class="guest-summary-value"><?= number_format($total_huespedes) ?></p>
            </div>
            <div class="guest-summary-item">
                <p class="guest-summary-label">Mostrando</p>
                <p class="guest-summary-value"><?= number_format($huespedesVisibles) ?></p>
            </div>
            <div class="guest-summary-item">
                <p class="guest-summary-label">Orígenes</p>
                <p class="guest-summary-value"><?= number_format($origenesVisibles) ?></p>
            </div>
        </section>

        <section class="guest-panel p-3 md:p-4">
            <form method="GET" action="<?= url('huespedes') ?>" class="guest-filter-form">
                <div>
                    <label class="block text-xs font-extrabold text-slate-600 uppercase tracking-wide mb-1">Buscar huésped</label>
                    <div class="relative">
                        <input type="text"
                               name="buscar"
                               value="<?= htmlspecialchars($buscar ?? '') ?>"
                               placeholder="Nombre, teléfono, email o placas"
                               class="guest-control pl-10 pr-4 py-2 border text-sm">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-600 uppercase tracking-wide mb-1">Procedencia</label>
                    <select name="estado" class="guest-control px-3 py-2 border text-sm">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado ?>" <?= ($estado_filtro ?? '') == $estado ? 'selected' : '' ?>>
                                <?= $estado ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="guest-filter-actions flex gap-2">
                    <button type="submit" class="guest-filter-btn">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <a href="<?= url('huespedes') ?>" class="guest-reset-btn">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </a>
                </div>
            </form>
        </section>

        <?php if (!empty($guestRows)): ?>
        <section class="guest-panel overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-sm font-black text-slate-900">Directorio de huéspedes</h2>
                    <p class="text-xs text-slate-500">Contacto, origen y acciones rápidas para recepción.</p>
                </div>
                <span class="guest-count-pill">
                    <i class="fas fa-list"></i>
                    <?= number_format($huespedesVisibles) ?> visibles
                </span>
            </div>

            <div class="guest-desktop-table">
                <table class="guest-table">
                    <colgroup>
                        <col style="width: 28%;">
                        <col style="width: 27%;">
                        <col style="width: 24%;">
                        <col style="width: 11%;">
                        <col style="width: 10%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Huésped</th>
                            <th>Contacto</th>
                            <th>Origen y vehículo</th>
                            <th class="text-center">Reservas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guestRows as $guestRow): ?>
                            <?php
                            $huesped = $guestRow['huesped'];
                            $vehiculos = $guestRow['vehiculos'];
                            $total_vehiculos = $guestRow['total_vehiculos'];
                            $reservas = intval($huesped['total_reservaciones'] ?? 0);
                            ?>
                            <tr class="guest-row">
                                <td>
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="guest-avatar">
                                            <?= htmlspecialchars(strtoupper(substr(trim($huesped['nombre_completo'] ?? 'H'), 0, 1))) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="guest-name truncate">
                                                <?= htmlspecialchars($huesped['nombre_completo']) ?>
                                            </div>
                                            <div class="guest-id">ID <?= $huesped['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="guest-cell-stack">
                                        <?php if (!empty($huesped['telefono'])): ?>
                                            <div class="guest-contact-line">
                                                <i class="fas fa-phone text-slate-400"></i>
                                                <span><?= htmlspecialchars($huesped['telefono']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($huesped['email'])): ?>
                                            <div class="guest-contact-line">
                                                <i class="fas fa-envelope text-slate-400"></i>
                                                <span><?= htmlspecialchars($huesped['email']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (empty($huesped['telefono']) && empty($huesped['email'])): ?>
                                            <span class="guest-muted">Sin contacto registrado</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="guest-cell-stack">
                                        <?php if (!empty($huesped['procedencia_estado'])): ?>
                                            <div class="guest-meta-line">
                                                <i class="fas fa-map-marker-alt text-slate-400"></i>
                                                <span>
                                                    <?= htmlspecialchars($huesped['procedencia_estado']) ?>
                                                    <?php if (!empty($huesped['procedencia_ciudad'])): ?>
                                                        · <?= htmlspecialchars($huesped['procedencia_ciudad']) ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="guest-muted">Origen no especificado</span>
                                        <?php endif; ?>

                                        <?php if ($total_vehiculos > 0): ?>
                                            <div class="guest-meta-line">
                                                <i class="fas fa-car text-slate-400"></i>
                                                <span>
                                                    <?= $total_vehiculos ?> <?= $total_vehiculos == 1 ? 'vehículo' : 'vehículos' ?>
                                                    <?php if ($total_vehiculos == 1 && !empty($vehiculos[0]['placas'])): ?>
                                                        · <?= htmlspecialchars($vehiculos[0]['placas']) ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="guest-muted">Sin vehículo</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($reservas > 0): ?>
                                        <span class="guest-chip <?= $reservas >= 3 ? 'guest-chip-warning' : 'guest-chip-brand' ?>">
                                            <?= $reservas ?>
                                            <?php if ($reservas >= 3): ?>
                                                <i class="fas fa-star"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="guest-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <a href="<?= url('huespedes/' . $huesped['id']) ?>"
                                           class="guest-action guest-action-view"
                                           title="Ver detalles"
                                           aria-label="Ver detalles de <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= url('huespedes/' . $huesped['id'] . '/edit') ?>"
                                           class="guest-action guest-action-edit"
                                           title="Editar"
                                           aria-label="Editar <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= url('reservaciones/crear?huesped_id=' . $huesped['id']) ?>"
                                           class="guest-action guest-action-book"
                                           title="Nueva reservación"
                                           aria-label="Crear reservación para <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                            <i class="fas fa-calendar-plus"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="guest-mobile-list p-3">
                <?php foreach ($guestRows as $guestRow): ?>
                    <?php
                    $huesped = $guestRow['huesped'];
                    $vehiculos = $guestRow['vehiculos'];
                    $total_vehiculos = $guestRow['total_vehiculos'];
                    $reservas = intval($huesped['total_reservaciones'] ?? 0);
                    ?>
                    <article class="guest-card">
                        <div class="guest-card-top">
                            <div class="guest-avatar">
                                <?= htmlspecialchars(strtoupper(substr(trim($huesped['nombre_completo'] ?? 'H'), 0, 1))) ?>
                            </div>
                            <div class="guest-card-title">
                                <h3 class="guest-name truncate"><?= htmlspecialchars($huesped['nombre_completo']) ?></h3>
                                <p class="guest-id">ID <?= $huesped['id'] ?></p>
                            </div>
                            <span class="guest-chip <?= $reservas >= 3 ? 'guest-chip-warning' : 'guest-chip-brand' ?>">
                                <i class="fas fa-calendar-check"></i>
                                <?= $reservas ?>
                            </span>
                        </div>

                        <div class="guest-card-contact">
                            <?php if (!empty($huesped['telefono'])): ?>
                                <div class="guest-contact-line">
                                    <i class="fas fa-phone text-slate-400"></i>
                                    <span><?= htmlspecialchars($huesped['telefono']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($huesped['email'])): ?>
                                <div class="guest-contact-line">
                                    <i class="fas fa-envelope text-slate-400"></i>
                                    <span><?= htmlspecialchars($huesped['email']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (empty($huesped['telefono']) && empty($huesped['email'])): ?>
                                <div class="guest-contact-line text-slate-400">
                                    <i class="fas fa-address-book"></i>
                                    <span>Sin contacto registrado</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="guest-card-meta">
                            <?php if (!empty($huesped['procedencia_estado'])): ?>
                                <span class="guest-chip">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($huesped['procedencia_estado']) ?>
                                    <?php if (!empty($huesped['procedencia_ciudad'])): ?>
                                        · <?= htmlspecialchars($huesped['procedencia_ciudad']) ?>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="guest-chip">Origen no especificado</span>
                            <?php endif; ?>

                            <?php if ($total_vehiculos > 0): ?>
                                <span class="guest-chip">
                                    <i class="fas fa-car"></i>
                                    <?= $total_vehiculos ?> <?= $total_vehiculos == 1 ? 'vehículo' : 'vehículos' ?>
                                </span>
                            <?php else: ?>
                                <span class="guest-chip">Sin vehículo</span>
                            <?php endif; ?>
                        </div>

                        <div class="guest-card-actions">
                            <a href="<?= url('huespedes/' . $huesped['id']) ?>" class="guest-card-action">
                                <i class="fas fa-eye"></i>
                                Ver
                            </a>
                            <a href="<?= url('huespedes/' . $huesped['id'] . '/edit') ?>" class="guest-card-action">
                                <i class="fas fa-edit"></i>
                                Editar
                            </a>
                            <a href="<?= url('reservaciones/crear?huesped_id=' . $huesped['id']) ?>" class="guest-card-action primary">
                                <i class="fas fa-calendar-plus"></i>
                                Reservar
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php else: ?>
        <section class="guest-empty text-center py-11 px-4">
            <div class="guest-empty-icon mx-auto mb-4 w-14 h-14 rounded-2xl" style="background:var(--guest-brand-soft);color:var(--guest-brand);">
                <i class="fas fa-users text-xl"></i>
            </div>
            <h2 class="text-lg font-black text-slate-800">No se encontraron huéspedes</h2>
            <p class="text-slate-500 mt-2 max-w-md mx-auto">Aún no hay huéspedes con esos filtros. Crea una reservación o registra un huésped para verlo aquí.</p>
            <a href="<?= url('huespedes/create') ?>" class="guest-primary-btn mt-5">
                <i class="fas fa-user-plus"></i>
                Nuevo huésped
            </a>
        </section>
        <?php endif; ?>

        <?php if ($total_paginas > 1): ?>
        <nav class="guest-pagination mt-2" aria-label="Paginación de huéspedes">
            <?php if ($pagina_actual > 1): ?>
                <a href="?page=<?= $pagina_actual - 1 ?>&buscar=<?= urlencode($buscar ?? '') ?>&estado=<?= urlencode($estado_filtro ?? '') ?>"
                   class="guest-page-link"
                   aria-label="Página anterior">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <?php if ($i == $pagina_actual): ?>
                    <span class="guest-page-current" aria-current="page">
                        <?= $i ?>
                    </span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&buscar=<?= urlencode($buscar ?? '') ?>&estado=<?= urlencode($estado_filtro ?? '') ?>"
                       class="guest-page-link">
                        <?= $i ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?page=<?= $pagina_actual + 1 ?>&buscar=<?= urlencode($buscar ?? '') ?>&estado=<?= urlencode($estado_filtro ?? '') ?>"
                   class="guest-page-link"
                   aria-label="Página siguiente">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</div>
