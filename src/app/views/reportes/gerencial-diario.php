<?php
$reporte = is_array($reporte ?? null) ? $reporte : [];
$fecha = (string)($fecha ?? ($reporte['fecha'] ?? date('Y-m-d')));
$finanzas = is_array($reporte['finanzas'] ?? null) ? $reporte['finanzas'] : [];
$habitaciones = is_array($reporte['habitaciones'] ?? null) ? $reporte['habitaciones'] : [];
$agenda = is_array($reporte['agenda'] ?? null) ? $reporte['agenda'] : [];
$caja = is_array($reporte['caja'] ?? null) ? $reporte['caja'] : [];
$facturacion = is_array($reporte['facturacion'] ?? null) ? $reporte['facturacion'] : [];
$inventario = is_array($reporte['inventario'] ?? null) ? $reporte['inventario'] : [];
$riesgos = is_array($reporte['riesgos'] ?? null) ? $reporte['riesgos'] : [];

if (!function_exists('gd_safe')) {
    function gd_safe($value, $fallback = '') {
        $value = (string)($value ?? $fallback);
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('gd_money')) {
    function gd_money($value) {
        if (function_exists('format_money')) {
            return format_money((float)$value);
        }

        return '$' . number_format((float)$value, 2);
    }
}

if (!function_exists('gd_date')) {
    function gd_date($value) {
        $time = strtotime((string)$value);
        return $time ? date('d/m/Y', $time) : date('d/m/Y');
    }
}

$ingresos = (float)($finanzas['ingresos'] ?? 0);
$gastos = (float)($finanzas['gastos'] ?? 0);
$balance = (float)($finanzas['balance'] ?? 0);
$ocupacionPct = (float)($habitaciones['ocupacion_pct'] ?? 0);
$riesgosTotal = (int)($riesgos['total'] ?? 0);
$entradas = is_array($agenda['entradas'] ?? null) ? $agenda['entradas'] : [];
$salidas = is_array($agenda['salidas'] ?? null) ? $agenda['salidas'] : [];
$metodos = is_array($finanzas['metodos'] ?? null) ? $finanzas['metodos'] : [];

$riesgoRows = [
    ['label' => 'Check-ins vencidos', 'value' => (int)($riesgos['checkins_vencidos'] ?? 0), 'href' => url('reservaciones')],
    ['label' => 'Check-outs vencidos', 'value' => (int)($riesgos['checkouts_vencidos'] ?? 0), 'href' => url('reservaciones')],
    ['label' => 'Facturas pendientes', 'value' => (int)($riesgos['facturas_pendientes'] ?? 0), 'href' => url('facturacion?estatus=pendiente')],
    ['label' => 'Habitaciones en limpieza', 'value' => (int)($riesgos['habitaciones_limpieza'] ?? 0), 'href' => url('habitaciones?estado=limpieza')],
    ['label' => 'Habitaciones en mantenimiento', 'value' => (int)($riesgos['habitaciones_mantenimiento'] ?? 0), 'href' => url('habitaciones?estado=mantenimiento')],
    ['label' => 'Inventario bajo', 'value' => (int)($riesgos['inventario_bajo'] ?? 0), 'href' => url('inventario')],
];
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Manrope:wght@400;500;600;700&display=swap');
</style><style>
.gd-view {
    --gd-brand: var(--brand-primary, #1B2746);
    --gd-brand-2: color-mix(in srgb, var(--gd-brand) 82%, #111827);
    --gd-accent: var(--brand-accent, #BD9441);
    --gd-line: color-mix(in srgb, var(--gd-brand) 10%, #E7E1D4);
    --gd-muted: #667085;
    --gd-text: #172033;
    --gd-bg: #FBFAF6;
    min-height: 100vh;
    padding: 24px;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--gd-accent) 5%, #FFFFFF), transparent 280px),
        var(--gd-bg);
    color: var(--gd-text);
}
.gd-shell {
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    gap: 16px;
}
.gd-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--gd-line);
}
.gd-kicker {
    margin: 0 0 7px;
    color: var(--gd-accent);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.gd-title {
    margin: 0;
    color: var(--gd-brand);
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: .98;
    letter-spacing: 0;
}
.gd-sub {
    margin: 8px 0 0;
    color: var(--gd-muted);
    font-size: .92rem;
    line-height: 1.5;
}
.gd-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 9px;
}
.gd-date-form {
    display: inline-flex;
    gap: 8px;
    align-items: center;
}
.gd-input,
.gd-btn {
    min-height: 40px;
    border-radius: 10px;
    border: 1px solid var(--gd-line);
    background: #FFFFFF;
    color: var(--gd-text);
    font-size: .85rem;
    font-weight: 600;
}
.gd-input {
    padding: 0 10px;
}
.gd-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 13px;
    text-decoration: none;
    cursor: pointer;
}
.gd-btn.primary {
    border-color: var(--gd-brand);
    background: var(--gd-brand);
    color: #FFFFFF;
}
.gd-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.gd-tile,
.gd-panel {
    border: 1px solid var(--gd-line);
    border-radius: 14px;
    background: rgba(255,255,255,.94);
    box-shadow: 0 14px 30px -26px rgba(17, 24, 39, .34);
}
.gd-tile {
    padding: 16px;
}
.gd-tile span,
.gd-panel-label {
    display: block;
    color: var(--gd-muted);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.gd-tile strong {
    display: block;
    margin-top: 7px;
    color: var(--gd-brand);
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(1.5rem, 2.4vw, 2.2rem);
    line-height: 1;
    font-weight: 700;
}
.gd-tile em {
    display: block;
    margin-top: 6px;
    color: var(--gd-muted);
    font-size: .78rem;
    font-style: normal;
    font-weight: 600;
}
.gd-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(320px, .8fr);
    gap: 14px;
}
.gd-column {
    display: grid;
    gap: 14px;
}
.gd-panel {
    padding: 16px;
}
.gd-panel h2 {
    margin: 3px 0 12px;
    color: var(--gd-brand);
    font-size: 1rem;
    font-weight: 700;
}
.gd-money-grid,
.gd-room-grid,
.gd-agenda-grid {
    display: grid;
    gap: 10px;
}
.gd-money-row,
.gd-risk-row,
.gd-stat-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 42px;
    border-top: 1px solid color-mix(in srgb, var(--gd-line) 72%, transparent);
}
.gd-money-row:first-child,
.gd-risk-row:first-child,
.gd-stat-row:first-child {
    border-top: 0;
}
.gd-money-row span,
.gd-risk-row span,
.gd-stat-row span {
    color: var(--gd-muted);
    font-size: .82rem;
    font-weight: 600;
}
.gd-money-row strong,
.gd-risk-row strong,
.gd-stat-row strong {
    color: var(--gd-text);
    font-size: .88rem;
    font-weight: 700;
    text-align: right;
}
.gd-methods {
    width: 100%;
    border-collapse: collapse;
}
.gd-methods th,
.gd-methods td {
    padding: 10px 8px;
    border-top: 1px solid color-mix(in srgb, var(--gd-line) 74%, transparent);
    font-size: .82rem;
    text-align: right;
}
.gd-methods th:first-child,
.gd-methods td:first-child {
    text-align: left;
}
.gd-methods th {
    color: var(--gd-muted);
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.gd-methods td {
    color: var(--gd-text);
    font-weight: 600;
}
.gd-risk-row {
    color: inherit;
    text-decoration: none;
}
.gd-risk-row:hover strong,
.gd-risk-row:focus-visible strong {
    color: var(--gd-brand);
}
.gd-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    min-height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--gd-accent) 12%, #FFFFFF);
    color: color-mix(in srgb, var(--gd-accent) 72%, #111827);
    border: 1px solid color-mix(in srgb, var(--gd-accent) 24%, var(--gd-line));
}
.gd-footer-note {
    color: var(--gd-muted);
    font-size: .78rem;
    font-weight: 700;
}
@media (max-width: 980px) {
    .gd-head,
    .gd-grid {
        grid-template-columns: 1fr;
        display: grid;
    }
    .gd-actions {
        justify-content: flex-start;
    }
    .gd-kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 620px) {
    .gd-view {
        padding: 18px 12px;
    }
    .gd-kpis {
        grid-template-columns: 1fr;
    }
    .gd-date-form,
    .gd-actions {
        width: 100%;
    }
    .gd-input,
    .gd-btn {
        flex: 1;
    }
}
</style>

<div class="gd-view">
    <div class="gd-shell">
        <section class="gd-head">
            <div>
                <p class="gd-kicker">Gerencia diaria</p>
                <h1 class="gd-title">Reporte ejecutivo</h1>
                <p class="gd-sub">
                    Corte operativo de <?= gd_safe(gd_date($fecha)) ?> con finanzas, ocupacion, agenda, caja y pendientes.
                </p>
            </div>
            <div class="gd-actions">
                <form method="GET" action="<?= url('reportes/gerencial-diario') ?>" class="gd-date-form" data-auto-filter-form>
                    <input type="date" name="fecha" value="<?= gd_safe($fecha) ?>" class="gd-input">
                    <button type="submit" class="gd-btn primary">
                        <i class="fas fa-rotate" aria-hidden="true"></i>
                        Actualizar
                    </button>
                </form>
                <?php $back_arrow_href = back_url('reportes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('reportes') ?>" class="gd-btn ms-back-legacy">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Reportes
                </a>
                <a href="<?= url('reportes/gerencial-diario/pdf?fecha=' . urlencode($fecha)) ?>" class="gd-btn">
                    <i class="fas fa-file-pdf" aria-hidden="true"></i>
                    Descargar PDF
                </a>
            </div>
        </section>

        <section class="gd-kpis" aria-label="Indicadores principales">
            <article class="gd-tile">
                <span>Ingresos</span>
                <strong><?= gd_safe(gd_money($ingresos)) ?></strong>
                <em><?= (int)($finanzas['movimientos'] ?? 0) ?> movimiento(s)</em>
            </article>
            <article class="gd-tile">
                <span>Balance</span>
                <strong><?= gd_safe(gd_money($balance)) ?></strong>
                <em>Gastos: <?= gd_safe(gd_money($gastos)) ?></em>
            </article>
            <article class="gd-tile">
                <span>Ocupacion</span>
                <strong><?= number_format($ocupacionPct, 1) ?>%</strong>
                <em><?= (int)($habitaciones['ocupadas'] ?? 0) ?> de <?= (int)($habitaciones['total'] ?? 0) ?> habitaciones</em>
            </article>
            <article class="gd-tile">
                <span>Pendientes</span>
                <strong><?= $riesgosTotal ?></strong>
                <em><?= (int)($facturacion['pendientes'] ?? 0) ?> facturacion, <?= (int)($inventario['bajo_minimo'] ?? 0) ?> inventario</em>
            </article>
        </section>

        <section class="gd-grid">
            <div class="gd-column">
                <article class="gd-panel">
                    <span class="gd-panel-label">Finanzas del dia</span>
                    <h2>Ingresos, gastos y metodo de pago</h2>
                    <div class="gd-money-grid">
                        <div class="gd-money-row"><span>Total ingresos</span><strong><?= gd_safe(gd_money($ingresos)) ?></strong></div>
                        <div class="gd-money-row"><span>Total gastos</span><strong><?= gd_safe(gd_money($gastos)) ?></strong></div>
                        <div class="gd-money-row"><span>Balance operativo</span><strong><?= gd_safe(gd_money($balance)) ?></strong></div>
                    </div>
                    <table class="gd-methods" aria-label="Metodos de pago">
                        <thead>
                            <tr>
                                <th>Metodo</th>
                                <th>Ingresos</th>
                                <th>Gastos</th>
                                <th>Mov.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metodos as $metodo => $valores): ?>
                                <tr>
                                    <td><?= gd_safe(ucfirst((string)$metodo)) ?></td>
                                    <td><?= gd_safe(gd_money($valores['ingresos'] ?? 0)) ?></td>
                                    <td><?= gd_safe(gd_money($valores['gastos'] ?? 0)) ?></td>
                                    <td><?= (int)($valores['movimientos'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </article>

                <article class="gd-panel">
                    <span class="gd-panel-label">Habitaciones</span>
                    <h2>Estado operativo</h2>
                    <div class="gd-room-grid">
                        <div class="gd-stat-row"><span>Disponibles reales</span><strong><?= (int)($habitaciones['disponibles_reales'] ?? 0) ?></strong></div>
                        <div class="gd-stat-row"><span>Por llegar</span><strong><?= (int)($habitaciones['por_llegar'] ?? 0) ?></strong></div>
                        <div class="gd-stat-row"><span>Limpieza</span><strong><?= (int)($habitaciones['limpieza'] ?? 0) ?></strong></div>
                        <div class="gd-stat-row"><span>Mantenimiento</span><strong><?= (int)($habitaciones['mantenimiento'] ?? 0) ?></strong></div>
                    </div>
                </article>
            </div>

            <div class="gd-column">
                <article class="gd-panel">
                    <span class="gd-panel-label">Agenda</span>
                    <h2>Entradas y salidas</h2>
                    <div class="gd-agenda-grid">
                        <div class="gd-stat-row"><span>Entradas del dia</span><strong><?= (int)($entradas['total'] ?? 0) ?></strong></div>
                        <div class="gd-stat-row"><span>Entradas pendientes</span><strong><?= (int)($entradas['pendientes'] ?? 0) ?></strong></div>
                        <div class="gd-stat-row"><span>Salidas del dia</span><strong><?= (int)($salidas['total'] ?? 0) ?></strong></div>
                        <div class="gd-stat-row"><span>Salidas pendientes</span><strong><?= (int)($salidas['pendientes'] ?? 0) ?></strong></div>
                        <div class="gd-stat-row"><span>Huespedes actuales</span><strong><?= (int)($agenda['huespedes_actuales'] ?? 0) ?></strong></div>
                    </div>
                </article>

                <article class="gd-panel">
                    <span class="gd-panel-label">Caja</span>
                    <h2>Cortes</h2>
                    <div class="gd-stat-row"><span>Cajas abiertas</span><strong><?= (int)($caja['abiertas'] ?? 0) ?></strong></div>
                    <div class="gd-stat-row"><span>Cortes cerrados hoy</span><strong><?= (int)($caja['cerradas'] ?? 0) ?></strong></div>
                </article>

                <article class="gd-panel">
                    <span class="gd-panel-label">Pendientes gerenciales</span>
                    <h2>Acciones sugeridas</h2>
                    <?php foreach ($riesgoRows as $row): ?>
                        <a href="<?= gd_safe($row['href']) ?>" class="gd-risk-row">
                            <span><?= gd_safe($row['label']) ?></span>
                            <strong class="gd-badge"><?= (int)$row['value'] ?></strong>
                        </a>
                    <?php endforeach; ?>
                </article>
            </div>
        </section>

        <p class="gd-footer-note">
            Generado: <?= gd_safe($reporte['generado_en'] ?? date('Y-m-d H:i:s')) ?>.
        </p>
    </div>
</div>
