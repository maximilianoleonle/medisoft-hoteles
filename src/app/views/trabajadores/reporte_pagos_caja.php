<?php
$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$porEstado = is_array($reporte['por_estado'] ?? null) ? $reporte['por_estado'] : [];
$porMetodo = is_array($reporte['por_metodo'] ?? null) ? $reporte['por_metodo'] : [];
$porCorte = is_array($reporte['por_corte'] ?? null) ? $reporte['por_corte'] : [];
$filtros = is_array($reporte['filtros_normalizados'] ?? null) ? $reporte['filtros_normalizados'] : [];

if (!function_exists('trab_cash_report_safe')) {
    function trab_cash_report_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_cash_report_money')) {
    function trab_cash_report_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_cash_report_num')) {
    function trab_cash_report_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_cash_report_datetime')) {
    function trab_cash_report_datetime($value)
    {
        $text = trim((string)($value ?? ''));
        if ($text === '' || $text === '0000-00-00 00:00:00' || $text === '0000-00-00') {
            return '-';
        }

        try {
            return (new DateTime($text))->format('d/m/Y H:i');
        } catch (Throwable $e) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
}

$trabajadorId = (int)($filtros['trabajador_id'] ?? 0);
$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
$metodoPago = (string)($filtros['metodo_pago'] ?? 'todos');
$corteId = (int)($filtros['corte_id'] ?? 0);
$fechaInicio = (string)($filtros['fecha_inicio'] ?? '');
$fechaFin = (string)($filtros['fecha_fin'] ?? '');
$exportParams = [
    'trabajador_id' => $trabajadorId > 0 ? $trabajadorId : null,
    'buscar' => $buscar !== '' ? $buscar : null,
    'estado' => $estado !== '' ? $estado : 'todos',
    'metodo_pago' => $metodoPago !== '' ? $metodoPago : 'todos',
    'corte_id' => $corteId > 0 ? $corteId : null,
    'fecha_inicio' => $fechaInicio !== '' ? $fechaInicio : null,
    'fecha_fin' => $fechaFin !== '' ? $fechaFin : null,
];
$exportParams = array_filter($exportParams, static function ($value) {
    return $value !== null && trim((string)$value) !== '';
});
$exportQuery = http_build_query($exportParams);
$exportUrl = url('trabajadores/pagos-caja/reporte/exportar' . ($exportQuery !== '' ? '?' . $exportQuery : ''));
?>

<style>
.labor-cash-report {
    --wk-brand: var(--brand-primary, #1B2746);
    --wk-brand-2: var(--brand-secondary, #0F172A);
    --wk-gold: var(--brand-accent, #BD9441);
    --wk-gold-soft: color-mix(in srgb, var(--wk-gold) 15%, #FFFFFF);
    --wk-gold-line: color-mix(in srgb, var(--wk-gold) 42%, #E4D4B0);
    --wk-gold-ink: color-mix(in srgb, var(--wk-gold) 72%, #000);
    --wk-ivory: #F6F2EA; --wk-ivory-2: #FBF8F2;
    --wk-surface: #FFFFFF; --wk-surface-warm: #FCFAF5;
    --wk-border: color-mix(in srgb, var(--wk-brand) 7%, #E7E1D4);
    --wk-ring: color-mix(in srgb, var(--wk-gold) 32%, transparent);
    --wk-text: #171717; --wk-muted: #667085; --wk-heading: #111827;
    --wk-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --wk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --wk-success: #1E9E63; --wk-success-bg: #E7F4EC;
    --wk-warning: #C2841C; --wk-warning-bg: #FAF0DC;
    --wk-danger: #B4392B; --wk-danger-bg: #F8EAE5;
    min-height: 100%; color: var(--wk-text); font-family: var(--wk-sans);
    background: radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--wk-gold) 8%, transparent), transparent 60%), linear-gradient(180deg, var(--wk-ivory-2), var(--wk-ivory));
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.labor-cash-report .wk-shell { display: grid; gap: 14px; }
.labor-cash-report .wk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.labor-cash-report .wk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--wk-gold), var(--wk-brand) 54%, color-mix(in srgb, var(--wk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--wk-brand) 72%, transparent); }
.labor-cash-report .wk-kicker { margin: 0 0 2px; color: var(--wk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.labor-cash-report .wk-title { margin: 0; font-family: var(--wk-serif); color: var(--wk-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.labor-cash-report .wk-subtitle { max-width: 52rem; margin: 8px 0 0; color: var(--wk-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.labor-cash-report .wk-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
.labor-cash-report .wk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 14px;
    border-radius: 11px; border: 1px solid var(--wk-border); background: var(--wk-surface); color: var(--wk-muted); font-weight: 700; font-size: .82rem; text-decoration: none; cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, color .16s ease, background .16s ease; }
.labor-cash-report .wk-btn:hover { transform: translateY(-1px); border-color: var(--wk-gold-line); color: var(--wk-gold-ink); }
.labor-cash-report .wk-btn-brand { background: linear-gradient(135deg, var(--wk-brand), var(--wk-brand-2)); border-color: transparent; color: #fff; }
.labor-cash-report .wk-btn-brand:hover { color: #fff; border-color: transparent; }
.labor-cash-report .wk-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: var(--wk-surface-warm); color: var(--wk-muted); border: 1px solid var(--wk-border); font-size: .74rem; font-weight: 700; }

.labor-cash-report .wk-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.labor-cash-report .wk-stat { background: var(--wk-surface); border: 1px solid var(--wk-border); border-radius: 14px; padding: 13px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.labor-cash-report .wk-stat.is-warm { background: var(--wk-surface-warm); }
.labor-cash-report .wk-stat-label { color: var(--wk-muted); font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.labor-cash-report .wk-stat-value { margin-top: 3px; font-family: var(--wk-serif); font-size: 1.55rem; font-weight: 700; line-height: 1; color: var(--wk-heading); }
.labor-cash-report .wk-stat-foot { color: var(--wk-muted); font-size: .7rem; margin-top: 3px; }

.labor-cash-report .wk-panel { background: var(--wk-surface); border: 1px solid var(--wk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.labor-cash-report .wk-control { width: 100%; min-height: 40px; border: 1px solid var(--wk-border); background: var(--wk-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--wk-text); font-weight: 600; font-size: .85rem; transition: border-color .16s ease, box-shadow .16s ease; }
.labor-cash-report .wk-control:focus { border-color: var(--wk-gold); box-shadow: 0 0 0 3px var(--wk-ring); outline: none; }
.labor-cash-report select.wk-control { cursor: pointer; }
.labor-cash-report .wk-filter-form { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; align-items: center; }

.labor-cash-report .wk-grid3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.labor-cash-report .wk-panel-head { padding: 15px 18px; border-bottom: 1px solid var(--wk-border); display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 10px; }
.labor-cash-report .wk-panel-title { font-family: var(--wk-serif); font-size: 1.3rem; font-weight: 700; color: var(--wk-heading); }
.labor-cash-report .wk-panel-sub { font-size: .8rem; color: var(--wk-muted); margin-top: 3px; }
.labor-cash-report .wk-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
.labor-cash-report .wk-table thead { background: var(--wk-surface-warm); border-bottom: 1px solid var(--wk-border); }
.labor-cash-report .wk-table th { padding: 11px 13px; color: var(--wk-muted); font-size: .64rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.labor-cash-report .wk-table th.is-end, .labor-cash-report .wk-table td.is-end { text-align: right; }
.labor-cash-report .wk-table td { padding: 12px 13px; border-bottom: 1px solid var(--wk-border); vertical-align: top; }
.labor-cash-report .wk-table tbody tr:last-child td { border-bottom: 0; }
.labor-cash-report .wk-table tbody tr:hover { background: var(--wk-ivory-2); }
.labor-cash-report .wk-link { font-weight: 700; color: var(--wk-heading); text-decoration: none; }
.labor-cash-report .wk-link:hover { text-decoration: underline; text-decoration-color: var(--wk-gold); text-underline-offset: 3px; }
.labor-cash-report .wk-strong { font-weight: 700; color: var(--wk-heading); }
.labor-cash-report .wk-sub { color: var(--wk-muted); font-size: .7rem; }
.labor-cash-report .wk-empty-cell { text-align: center; color: var(--wk-muted); padding: 18px; }

.labor-cash-report .wk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid transparent; }
.labor-cash-report .wk-badge.is-pagado { color: color-mix(in srgb, var(--wk-success) 78%, #000); background: var(--wk-success-bg); border-color: color-mix(in srgb, var(--wk-success) 26%, #fff); }
.labor-cash-report .wk-badge.is-revertido { color: color-mix(in srgb, var(--wk-warning) 82%, #000); background: var(--wk-warning-bg); border-color: color-mix(in srgb, var(--wk-warning) 28%, #fff); }
.labor-cash-report .wk-badge.is-soft { color: var(--wk-muted); background: var(--wk-surface-warm); border-color: var(--wk-border); }

.labor-cash-report .wk-empty { text-align: center; padding: 40px 18px; }
.labor-cash-report .wk-empty-icon { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 18px; display: grid; place-items: center; background: var(--wk-gold-soft); color: var(--wk-gold-ink); font-size: 1.25rem; }
.labor-cash-report .wk-empty h3 { color: var(--wk-brand); font-size: 1.05rem; font-weight: 700; }
.labor-cash-report .wk-empty p { color: var(--wk-muted); font-size: .88rem; margin-top: 6px; }
.labor-cash-report .wk-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--wk-gold-soft); border: 1px solid var(--wk-gold-line); border-radius: 16px; }
.labor-cash-report .wk-notice i { color: var(--wk-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.labor-cash-report .wk-notice strong { color: var(--wk-heading); display: block; margin-bottom: 2px; }
.labor-cash-report .wk-notice p { color: var(--wk-muted); font-size: .88rem; margin: 0; }

@media (min-width: 1100px) { .labor-cash-report .wk-filter-form { grid-template-columns: 110px minmax(180px,1fr) 140px 160px 110px 140px 140px auto auto; } }
@media (max-width: 980px) { .labor-cash-report .wk-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .labor-cash-report .wk-grid3 { grid-template-columns: 1fr; } }
</style>

<div class="labor-cash-report p-4 sm:p-6">
    <div class="wk-shell">
        <section class="wk-title-lockup">
            <div class="wk-hero-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <div>
                <p class="wk-kicker">Personal del hotel</p>
                <h1 class="wk-title">Pagos en Caja</h1>
                <p class="wk-subtitle">Todos los pagos que le has hecho a tu personal desde Caja, con su corte, referencia y estado. Es solo para consultar.</p>
            </div>
        </section>

        <section class="wk-toolbar">
            <div class="flex flex-wrap gap-2">
                <?php $back_arrow_href = back_url('trabajadores'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a class="wk-btn ms-back-legacy" href="<?= back_url('trabajadores') ?>"><i class="fas fa-arrow-left"></i> Personal</a>
                <a class="wk-btn" href="<?= url('trabajadores/reporte') ?>"><i class="fas fa-chart-pie"></i> Reporte</a>
                <a class="wk-btn" href="<?= url('trabajadores/pagos-caja/simulador') ?>"><i class="fas fa-cash-register"></i> Simulador</a>
                <?php if ($tablaDisponible && (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones'))): ?><a class="wk-btn" href="<?= $exportUrl ?>"><i class="fas fa-file-csv"></i> Exportar CSV</a><?php endif; ?>
            </div>
            <span class="wk-pill"><i class="fas fa-eye"></i> Solo consulta</span>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="wk-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>Faltan datos de personal o de Caja para consultar los pagos laborales.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="wk-panel p-3 md:p-4">
                <form method="GET" action="<?= url('trabajadores/pagos-caja/reporte') ?>" class="wk-filter-form" data-auto-filter-form>
                    <input class="wk-control" type="number" min="1" name="trabajador_id" value="<?= $trabajadorId > 0 ? (int)$trabajadorId : '' ?>" placeholder="ID">
                    <input class="wk-control" type="search" name="buscar" value="<?= trab_cash_report_safe($buscar, '') ?>" placeholder="Buscar trabajador, referencia o caja">
                    <select class="wk-control" name="estado">
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pagado" <?= $estado === 'pagado' ? 'selected' : '' ?>>Pagado</option>
                        <option value="revertido" <?= $estado === 'revertido' ? 'selected' : '' ?>>Revertido</option>
                    </select>
                    <select class="wk-control" name="metodo_pago">
                        <option value="todos" <?= $metodoPago === 'todos' ? 'selected' : '' ?>>Todos los m&eacute;todos</option>
                        <option value="efectivo" <?= $metodoPago === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                        <option value="tarjeta" <?= $metodoPago === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                        <option value="transferencia" <?= $metodoPago === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                    </select>
                    <input class="wk-control" type="number" min="1" name="corte_id" value="<?= $corteId > 0 ? (int)$corteId : '' ?>" placeholder="Corte">
                    <input class="wk-control" type="date" name="fecha_inicio" value="<?= trab_cash_report_safe($fechaInicio, '') ?>">
                    <input class="wk-control" type="date" name="fecha_fin" value="<?= trab_cash_report_safe($fechaFin, '') ?>">
                    <button class="wk-btn wk-btn-brand" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                    <a class="wk-btn" href="<?= url('trabajadores/pagos-caja/reporte') ?>"><i class="fas fa-rotate-left"></i> Limpiar</a>
                </form>
            </section>

            <section class="wk-stats">
                <div class="wk-stat is-warm"><p class="wk-stat-label">Pagado (vigente)</p><p class="wk-stat-value"><?= trab_cash_report_money($resumen['pagado_vigente_total'] ?? 0) ?></p><p class="wk-stat-foot"><?= trab_cash_report_num($resumen['pagados_count'] ?? 0) ?> pago(s) aplicados</p></div>
                <div class="wk-stat"><p class="wk-stat-label">Total emitido</p><p class="wk-stat-value"><?= trab_cash_report_money($resumen['egreso_original_total'] ?? 0) ?></p><p class="wk-stat-foot">Suma hist&oacute;rica</p></div>
                <div class="wk-stat"><p class="wk-stat-label">Revertido</p><p class="wk-stat-value"><?= trab_cash_report_money($resumen['revertido_total'] ?? 0) ?></p><p class="wk-stat-foot"><?= trab_cash_report_num($resumen['revertidos_count'] ?? 0) ?> registro(s)</p></div>
                <div class="wk-stat"><p class="wk-stat-label">Devuelto a Caja</p><p class="wk-stat-value"><?= trab_cash_report_money($resumen['reversion_caja_total'] ?? 0) ?></p><p class="wk-stat-foot">Por reversiones</p></div>
            </section>

            <div class="wk-grid3">
                <div class="wk-panel overflow-hidden">
                    <div class="wk-panel-head"><h2 class="wk-panel-title">Por m&eacute;todo</h2></div>
                    <div class="overflow-x-auto">
                        <table class="wk-table">
                            <thead><tr><th>M&eacute;todo</th><th class="is-end">Pagado</th><th class="is-end">Revertido</th><th class="is-end">Devuelto</th></tr></thead>
                            <tbody>
                                <?php if (empty($porMetodo)): ?>
                                    <tr><td colspan="4" class="wk-empty-cell">Sin movimientos.</td></tr>
                                <?php else: foreach ($porMetodo as $item): ?>
                                    <tr>
                                        <td class="wk-strong" style="text-transform:capitalize"><?= trab_cash_report_safe($item['metodo_pago'] ?? null) ?></td>
                                        <td class="is-end"><?= trab_cash_report_money($item['pagado_vigente'] ?? 0) ?></td>
                                        <td class="is-end"><?= trab_cash_report_money($item['revertido'] ?? 0) ?></td>
                                        <td class="is-end"><?= trab_cash_report_money($item['reversion_caja'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="wk-panel overflow-hidden">
                    <div class="wk-panel-head"><h2 class="wk-panel-title">Por estado</h2></div>
                    <div class="overflow-x-auto">
                        <table class="wk-table">
                            <thead><tr><th>Estado</th><th class="is-end">Registros</th><th class="is-end">Monto</th></tr></thead>
                            <tbody>
                                <?php if (empty($porEstado)): ?>
                                    <tr><td colspan="3" class="wk-empty-cell">Sin estados.</td></tr>
                                <?php else: foreach ($porEstado as $item): ?>
                                    <tr>
                                        <td><span class="wk-badge <?= ($item['estado'] ?? '') === 'pagado' ? 'is-pagado' : 'is-revertido' ?>"><?= trab_cash_report_safe($item['estado'] ?? null) ?></span></td>
                                        <td class="is-end"><?= trab_cash_report_num($item['total'] ?? 0) ?></td>
                                        <td class="is-end"><?= trab_cash_report_money($item['monto'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="wk-panel overflow-hidden">
                    <div class="wk-panel-head"><h2 class="wk-panel-title">Por corte</h2></div>
                    <div class="overflow-x-auto" style="max-height:260px">
                        <table class="wk-table">
                            <thead><tr><th>Corte</th><th class="is-end">Pagado</th><th class="is-end">Revertido</th></tr></thead>
                            <tbody>
                                <?php if (empty($porCorte)): ?>
                                    <tr><td colspan="3" class="wk-empty-cell">Sin cortes.</td></tr>
                                <?php else: foreach ($porCorte as $item): ?>
                                    <tr>
                                        <td>
                                            <a class="wk-link" href="<?= url('caja/corte/' . (int)($item['corte_id'] ?? 0)) ?>">#<?= (int)($item['corte_id'] ?? 0) ?></a>
                                            <div class="wk-sub"><?= trab_cash_report_safe($item['caja_nombre'] ?? null) ?> &middot; <?= trab_cash_report_safe($item['corte_estado'] ?? null) ?></div>
                                        </td>
                                        <td class="is-end"><?= trab_cash_report_money($item['pagado_vigente'] ?? 0) ?></td>
                                        <td class="is-end"><?= trab_cash_report_money($item['revertido'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="wk-panel overflow-hidden">
                <div class="wk-panel-head">
                    <div>
                        <h2 class="wk-panel-title">Pagos registrados</h2>
                        <p class="wk-panel-sub">Pagos laborales con Caja del hotel actual, seg&uacute;n tus filtros.</p>
                    </div>
                    <span class="wk-pill"><i class="fas fa-list-check"></i> <?= trab_cash_report_num(count($registros)) ?></span>
                </div>

                <?php if (empty($registros)): ?>
                    <div class="wk-empty">
                        <div class="wk-empty-icon"><i class="fas fa-receipt"></i></div>
                        <h3>Sin pagos laborales con Caja</h3>
                        <p>Ajusta los filtros o registra pagos desde la ficha del trabajador.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="wk-table">
                            <thead>
                                <tr><th>Fecha</th><th>Trabajador</th><th class="is-end">Monto</th><th>Caja / corte</th><th>Referencia</th><th>Estado</th><th>Reversi&oacute;n</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $registro): ?>
                                    <?php
                                        $estadoRegistro = (string)($registro['estado'] ?? '');
                                        $creadoPor = trim((string)($registro['creado_por_nombre'] ?? ''));
                                        if ($creadoPor === '') { $creadoPor = trim((string)($registro['creado_por_login'] ?? '')); }
                                        $actualizadoPor = trim((string)($registro['actualizado_por_nombre'] ?? ''));
                                        if ($actualizadoPor === '') { $actualizadoPor = trim((string)($registro['actualizado_por_login'] ?? '')); }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="wk-strong"><?= trab_cash_report_datetime($registro['fecha_pago'] ?? null) ?></div>
                                            <div class="wk-sub">Registro #<?= (int)($registro['id'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <a class="wk-link" href="<?= url('trabajadores/' . (int)($registro['trabajador_id'] ?? 0)) ?>"><?= trab_cash_report_safe($registro['trabajador_nombre'] ?? null) ?></a>
                                            <div class="wk-sub"><?= trab_cash_report_safe($registro['trabajador_rol'] ?? null, 'Sin rol') ?></div>
                                        </td>
                                        <td class="is-end">
                                            <div class="wk-strong"><?= trab_cash_report_money($registro['monto'] ?? 0) ?></div>
                                            <div class="wk-sub" style="text-transform:capitalize"><?= trab_cash_report_safe($registro['metodo_pago'] ?? null) ?></div>
                                        </td>
                                        <td>
                                            <div class="wk-strong"><?= trab_cash_report_safe($registro['caja_nombre'] ?? null) ?></div>
                                            <a class="wk-link wk-sub" href="<?= url('caja/corte/' . (int)($registro['corte_id'] ?? 0)) ?>">Corte #<?= (int)($registro['corte_id'] ?? 0) ?></a>
                                        </td>
                                        <td>
                                            <div class="wk-strong"><?= trab_cash_report_safe($registro['referencia'] ?? null) ?></div>
                                            <div class="wk-sub">Mov. Caja #<?= (int)($registro['movimiento_caja_id'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <span class="wk-badge <?= $estadoRegistro === 'pagado' ? 'is-pagado' : 'is-revertido' ?>">
                                                <i class="fas <?= $estadoRegistro === 'pagado' ? 'fa-circle-check' : 'fa-rotate-left' ?>"></i>
                                                <?= trab_cash_report_safe($estadoRegistro) ?>
                                            </span>
                                            <div class="wk-sub mt-2"><?= $creadoPor !== '' ? 'Por ' . trab_cash_report_safe($creadoPor) : 'Usuario no disponible' ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($registro['movimiento_reversion_id'])): ?>
                                                <div class="wk-strong">Mov. Caja #<?= (int)$registro['movimiento_reversion_id'] ?></div>
                                                <div class="wk-sub"><?= trab_cash_report_datetime($registro['movimiento_reversion_created_at'] ?? null) ?></div>
                                                <div class="wk-sub"><?= $actualizadoPor !== '' ? 'Por ' . trab_cash_report_safe($actualizadoPor) : 'Usuario no disponible' ?></div>
                                            <?php elseif ($estadoRegistro === 'revertido'): ?>
                                                <span class="wk-badge is-revertido"><i class="fas fa-triangle-exclamation"></i> Sin ingreso vinculado</span>
                                            <?php else: ?>
                                                <span class="wk-badge is-soft"><i class="fas fa-lock"></i> Sin reversi&oacute;n</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if (trim((string)($registro['notas'] ?? '')) !== ''): ?>
                                        <tr><td colspan="7" class="wk-sub"><strong style="color:var(--wk-heading)">Notas:</strong> <?= trab_cash_report_safe($registro['notas'] ?? null) ?></td></tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
