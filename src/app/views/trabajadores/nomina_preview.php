<?php
$preview = is_array($preview ?? null) ? $preview : [];
$tablaDisponible = $tablaDisponible ?? false;
$trabajadores = is_array($preview['trabajadores'] ?? null) ? $preview['trabajadores'] : [];
$resumen = is_array($preview['resumen'] ?? null) ? $preview['resumen'] : [];
$filtros = is_array($preview['filtros_normalizados'] ?? null) ? $preview['filtros_normalizados'] : [];
$bloqueos = is_array($preview['bloqueos'] ?? null) ? $preview['bloqueos'] : [];

if (!function_exists('trab_nomina_safe')) {
    function trab_nomina_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_nomina_money')) {
    function trab_nomina_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_nomina_num')) {
    function trab_nomina_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_nomina_date')) {
    function trab_nomina_date($value)
    {
        $text = trim((string)($value ?? ''));
        if ($text === '' || $text === '0000-00-00') {
            return '-';
        }

        try {
            return (new DateTime($text))->format('d/m/Y');
        } catch (Throwable $e) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
}

if (!function_exists('trab_nomina_estado_meta')) {
    function trab_nomina_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'por_pagar' => ['Por pagar', 'is-ok', 'fa-circle-dollar-to-slot'],
            'cubierto'  => ['Cubierto', 'is-warn', 'fa-circle-check'],
            'bloqueado' => ['Bloqueado', 'is-danger', 'fa-ban'],
        ];
        return $map[$key] ?? [ucfirst(str_replace('_', ' ', $key !== '' ? $key : 'sin estado')), 'is-soft', 'fa-circle'];
    }
}

$fechaInicio = (string)($filtros['fecha_inicio_raw'] ?? ($filtros['fecha_inicio'] ?? ''));
$fechaFin = (string)($filtros['fecha_fin_raw'] ?? ($filtros['fecha_fin'] ?? ''));
$trabajadorId = (int)($filtros['trabajador_id'] ?? 0);
$buscar = (string)($filtros['buscar'] ?? '');
$rolLaboral = (string)($filtros['rol_laboral'] ?? '');
$estado = (string)($filtros['estado'] ?? 'activos');
$soloConSaldo = !empty($filtros['solo_con_saldo']);
$incluirPagosCaja = !empty($filtros['incluir_pagos_caja']);
$exportQuery = http_build_query([
    'fecha_inicio' => $fechaInicio,
    'fecha_fin' => $fechaFin,
    'trabajador_id' => $trabajadorId > 0 ? $trabajadorId : '',
    'buscar' => $buscar,
    'rol_laboral' => $rolLaboral,
    'estado' => $estado,
    'solo_con_saldo' => $soloConSaldo ? '1' : '0',
    'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
]);
$exportUrl = url('trabajadores/nomina/preview/exportar' . ($exportQuery !== '' ? '?' . $exportQuery : ''));
?>

<style>
.payroll-preview {
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
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.payroll-preview .wk-shell { display: grid; gap: 14px; }
.payroll-preview .wk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.payroll-preview .wk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--wk-gold), var(--wk-brand) 54%, color-mix(in srgb, var(--wk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--wk-brand) 72%, transparent); }
.payroll-preview .wk-kicker { margin: 0 0 2px; color: var(--wk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.payroll-preview .wk-title { margin: 0; font-family: var(--wk-serif); color: var(--wk-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.payroll-preview .wk-subtitle { max-width: 52rem; margin: 8px 0 0; color: var(--wk-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.payroll-preview .wk-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
.payroll-preview .wk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 14px;
    border-radius: 11px; border: 1px solid var(--wk-border); background: var(--wk-surface); color: var(--wk-muted); font-weight: 700; font-size: .82rem; text-decoration: none; cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, color .16s ease, background .16s ease; }
.payroll-preview .wk-btn:hover { transform: translateY(-1px); border-color: var(--wk-gold-line); color: var(--wk-gold-ink); }
.payroll-preview .wk-btn-brand { background: linear-gradient(135deg, var(--wk-brand), var(--wk-brand-2)); border-color: transparent; color: #fff; }
.payroll-preview .wk-btn-brand:hover { color: #fff; border-color: transparent; }
.payroll-preview .wk-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: var(--wk-surface-warm); color: var(--wk-muted); border: 1px solid var(--wk-border); font-size: .74rem; font-weight: 700; }

.payroll-preview .wk-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.payroll-preview .wk-stats-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
.payroll-preview .wk-stat { background: var(--wk-surface); border: 1px solid var(--wk-border); border-radius: 14px; padding: 13px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.payroll-preview .wk-stat.is-warm { background: var(--wk-surface-warm); }
.payroll-preview .wk-stat-label { color: var(--wk-muted); font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.payroll-preview .wk-stat-value { margin-top: 3px; font-family: var(--wk-serif); font-size: 1.55rem; font-weight: 700; line-height: 1; color: var(--wk-heading); }

.payroll-preview .wk-panel { background: var(--wk-surface); border: 1px solid var(--wk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.payroll-preview .wk-filter-form { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; align-items: center; }
.payroll-preview .wk-control { width: 100%; min-height: 40px; border: 1px solid var(--wk-border); background: var(--wk-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--wk-text); font-weight: 600; font-size: .85rem; transition: border-color .16s ease, box-shadow .16s ease; }
.payroll-preview .wk-control:focus { border-color: var(--wk-gold); box-shadow: 0 0 0 3px var(--wk-ring); outline: none; }
.payroll-preview select.wk-control { cursor: pointer; }
.payroll-preview .wk-check { display: inline-flex; align-items: center; gap: 8px; min-height: 40px; border: 1px solid var(--wk-border); background: var(--wk-surface-warm); border-radius: 11px; padding: 0 12px; font-weight: 700; font-size: .82rem; color: var(--wk-muted); cursor: pointer; }
.payroll-preview .wk-check input[type=checkbox] { accent-color: var(--wk-gold); width: 16px; height: 16px; }

.payroll-preview .wk-panel-head { padding: 16px 18px; border-bottom: 1px solid var(--wk-border); display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 10px; }
.payroll-preview .wk-panel-title { font-family: var(--wk-serif); font-size: 1.4rem; font-weight: 700; color: var(--wk-heading); }
.payroll-preview .wk-panel-sub { font-size: .8rem; color: var(--wk-muted); margin-top: 3px; }
.payroll-preview .wk-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
.payroll-preview .wk-table thead { background: var(--wk-surface-warm); border-bottom: 1px solid var(--wk-border); }
.payroll-preview .wk-table th { padding: 11px 13px; color: var(--wk-muted); font-size: .64rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.payroll-preview .wk-table th.is-end, .payroll-preview .wk-table td.is-end { text-align: right; }
.payroll-preview .wk-table td { padding: 12px 13px; border-bottom: 1px solid var(--wk-border); vertical-align: top; }
.payroll-preview .wk-table tbody tr:last-child td { border-bottom: 0; }
.payroll-preview .wk-table tbody tr:hover { background: var(--wk-ivory-2); }
.payroll-preview .wk-link { font-weight: 700; color: var(--wk-heading); text-decoration: none; }
.payroll-preview .wk-link:hover { text-decoration: underline; text-decoration-color: var(--wk-gold); text-underline-offset: 3px; }
.payroll-preview .wk-strong { font-weight: 700; color: var(--wk-heading); }
.payroll-preview .wk-sub { color: var(--wk-muted); font-size: .7rem; }
.payroll-preview .wk-recibo { display: inline-flex; align-items: center; gap: 5px; margin-top: 8px; padding: 4px 10px; border-radius: 999px; background: var(--wk-gold-soft); color: var(--wk-gold-ink); border: 1px solid var(--wk-gold-line); font-size: .72rem; font-weight: 700; text-decoration: none; }

.payroll-preview .wk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid transparent; }
.payroll-preview .wk-badge.is-ok { color: color-mix(in srgb, var(--wk-success) 78%, #000); background: var(--wk-success-bg); border-color: color-mix(in srgb, var(--wk-success) 26%, #fff); }
.payroll-preview .wk-badge.is-warn { color: color-mix(in srgb, var(--wk-warning) 82%, #000); background: var(--wk-warning-bg); border-color: color-mix(in srgb, var(--wk-warning) 28%, #fff); }
.payroll-preview .wk-badge.is-danger { color: color-mix(in srgb, var(--wk-danger) 82%, #000); background: var(--wk-danger-bg); border-color: color-mix(in srgb, var(--wk-danger) 26%, #fff); }
.payroll-preview .wk-badge.is-soft { color: var(--wk-muted); background: var(--wk-surface-warm); border-color: var(--wk-border); }

.payroll-preview .wk-empty { text-align: center; padding: 40px 18px; }
.payroll-preview .wk-empty-icon { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 18px; display: grid; place-items: center; background: var(--wk-gold-soft); color: var(--wk-gold-ink); font-size: 1.25rem; }
.payroll-preview .wk-empty h3 { color: var(--wk-brand); font-size: 1.05rem; font-weight: 700; }
.payroll-preview .wk-empty p { color: var(--wk-muted); font-size: .88rem; margin-top: 6px; }
.payroll-preview .wk-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--wk-gold-soft); border: 1px solid var(--wk-gold-line); border-radius: 16px; }
.payroll-preview .wk-notice i { color: var(--wk-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.payroll-preview .wk-notice strong { color: var(--wk-heading); display: block; margin-bottom: 2px; }
.payroll-preview .wk-notice p { color: var(--wk-muted); font-size: .88rem; margin: 0; }

@media (min-width: 1100px) { .payroll-preview .wk-filter-form { grid-template-columns: 140px 140px 110px minmax(160px,1fr) 130px 130px auto auto auto; } }
@media (max-width: 980px) { .payroll-preview .wk-stats, .payroll-preview .wk-stats-5 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="payroll-preview p-4 sm:p-6">
    <div class="wk-shell">
        <section class="wk-title-lockup">
            <div class="wk-hero-icon"><i class="fas fa-clipboard-list"></i></div>
            <div>
                <p class="wk-kicker">Personal del hotel</p>
                <h1 class="wk-title">Pre-n&oacute;mina del periodo</h1>
                <p class="wk-subtitle">Cu&aacute;nto le tocar&iacute;a a cada quien en el periodo: bruto, descuentos, lo que ya se pag&oacute; en Caja y el neto sugerido. Es un c&aacute;lculo de apoyo; no genera n&oacute;mina ni paga.</p>
            </div>
        </section>

        <section class="wk-toolbar">
            <div class="flex flex-wrap gap-2">
                <?php $back_arrow_href = back_url('trabajadores'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a class="wk-btn ms-back-legacy" href="<?= back_url('trabajadores') ?>"><i class="fas fa-arrow-left"></i> Personal</a>
                <a class="wk-btn" href="<?= url('trabajadores/reporte') ?>"><i class="fas fa-chart-pie"></i> Reporte</a>
                <a class="wk-btn" href="<?= url('trabajadores/nomina/periodos') ?>"><i class="fas fa-calendar-check"></i> Per&iacute;odos</a>
                <a class="wk-btn" href="<?= url('trabajadores/pagos-caja/reporte') ?>"><i class="fas fa-file-invoice-dollar"></i> Pagos en Caja</a>
                <?php if ($tablaDisponible): ?><a class="wk-btn" href="<?= $exportUrl ?>"><i class="fas fa-file-csv"></i> Exportar CSV</a><?php endif; ?>
            </div>
            <span class="wk-pill"><i class="fas fa-eye"></i> Solo consulta</span>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="wk-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para calcular la pre-n&oacute;mina.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="wk-stats">
                <div class="wk-stat"><p class="wk-stat-label">Trabajadores</p><p class="wk-stat-value"><?= trab_nomina_num($resumen['trabajadores_total'] ?? 0) ?></p></div>
                <div class="wk-stat"><p class="wk-stat-label">Por pagar</p><p class="wk-stat-value" style="color:var(--wk-success)"><?= trab_nomina_num($resumen['por_pagar_count'] ?? 0) ?></p></div>
                <div class="wk-stat is-warm"><p class="wk-stat-label">Neto sugerido</p><p class="wk-stat-value"><?= trab_nomina_money($resumen['neto_sugerido_total'] ?? 0) ?></p></div>
                <div class="wk-stat"><p class="wk-stat-label">Pendiente</p><p class="wk-stat-value"><?= trab_nomina_money($resumen['pendiente_pago_total'] ?? 0) ?></p></div>
            </section>

            <section class="wk-panel p-3 md:p-4">
                <form method="GET" action="<?= url('trabajadores/nomina/preview') ?>" class="wk-filter-form" data-auto-filter-form>
                    <input class="wk-control" type="date" name="fecha_inicio" value="<?= trab_nomina_safe($fechaInicio, '') ?>">
                    <input class="wk-control" type="date" name="fecha_fin" value="<?= trab_nomina_safe($fechaFin, '') ?>">
                    <input class="wk-control" type="number" min="1" name="trabajador_id" value="<?= $trabajadorId > 0 ? (int)$trabajadorId : '' ?>" placeholder="ID">
                    <input class="wk-control" type="search" name="buscar" value="<?= trab_nomina_safe($buscar, '') ?>" placeholder="Buscar trabajador">
                    <input class="wk-control" type="search" name="rol_laboral" value="<?= trab_nomina_safe($rolLaboral, '') ?>" placeholder="Rol">
                    <select class="wk-control" name="estado">
                        <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="baja" <?= $estado === 'baja' ? 'selected' : '' ?>>Baja</option>
                    </select>
                    <label class="wk-check">
                        <input type="hidden" name="solo_con_saldo" value="0">
                        <input type="checkbox" name="solo_con_saldo" value="1" <?= $soloConSaldo ? 'checked' : '' ?>>
                        Con saldo
                    </label>
                    <label class="wk-check">
                        <input type="hidden" name="incluir_pagos_caja" value="0">
                        <input type="checkbox" name="incluir_pagos_caja" value="1" <?= $incluirPagosCaja ? 'checked' : '' ?>>
                        Pagos Caja
                    </label>
                    <button class="wk-btn wk-btn-brand" type="submit"><i class="fas fa-filter"></i> Calcular</button>
                </form>
            </section>

            <?php if (!empty($bloqueos)): ?>
                <div class="wk-notice">
                    <i class="fas fa-circle-info"></i>
                    <div>
                        <strong>Elige un periodo para ver la pre-n&oacute;mina.</strong>
                        <?php foreach ($bloqueos as $bloqueo): ?>
                            <p><?= trab_nomina_safe($bloqueo) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <section class="wk-stats wk-stats-5">
                    <div class="wk-stat is-warm"><p class="wk-stat-label">Bruto del periodo</p><p class="wk-stat-value"><?= trab_nomina_money($resumen['bruto_total'] ?? 0) ?></p></div>
                    <div class="wk-stat"><p class="wk-stat-label">Descuentos</p><p class="wk-stat-value"><?= trab_nomina_money($resumen['deducciones_total'] ?? 0) ?></p></div>
                    <div class="wk-stat"><p class="wk-stat-label">Pagos en Caja</p><p class="wk-stat-value"><?= trab_nomina_money($resumen['pagos_caja_aplicados_total'] ?? 0) ?></p></div>
                    <div class="wk-stat"><p class="wk-stat-label">Reversiones</p><p class="wk-stat-value"><?= trab_nomina_money($resumen['reversiones_detectadas_total'] ?? 0) ?></p></div>
                    <div class="wk-stat is-warm"><p class="wk-stat-label">Pendiente</p><p class="wk-stat-value"><?= trab_nomina_money($resumen['pendiente_pago_total'] ?? 0) ?></p></div>
                </section>

                <div class="wk-panel overflow-hidden">
                    <div class="wk-panel-head">
                        <div>
                            <h2 class="wk-panel-title">Detalle por trabajador</h2>
                            <p class="wk-panel-sub">Periodo <?= trab_nomina_date($filtros['fecha_inicio'] ?? null) ?> &rarr; <?= trab_nomina_date($filtros['fecha_fin'] ?? null) ?></p>
                        </div>
                        <span class="wk-pill"><i class="fas fa-list-check"></i> <?= trab_nomina_num(count($trabajadores)) ?></span>
                    </div>

                    <?php if (empty($trabajadores)): ?>
                        <div class="wk-empty">
                            <div class="wk-empty-icon"><i class="fas fa-clipboard-list"></i></div>
                            <h3>Sin trabajadores en este periodo</h3>
                            <p>Ajusta el periodo o los filtros para revisar a tu personal.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="wk-table">
                                <thead>
                                    <tr><th>Trabajador</th><th>Lectura</th><th class="is-end">Bruto</th><th class="is-end">Descuentos</th><th class="is-end">Pagos Caja</th><th class="is-end">Neto sugerido</th><th>Notas</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trabajadores as $trabajador): ?>
                                        <?php
                                        [$eLabel, $eClass, $eIcon] = trab_nomina_estado_meta($trabajador['estado_preview_nomina'] ?? '');
                                        $reciboQuery = http_build_query([
                                            'fecha_inicio' => $fechaInicio,
                                            'fecha_fin' => $fechaFin,
                                            'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
                                        ]);
                                        $reciboUrl = url('trabajadores/' . (int)($trabajador['id'] ?? 0) . '/recibo-laboral' . ($reciboQuery !== '' ? '?' . $reciboQuery : ''));
                                        ?>
                                        <tr>
                                            <td>
                                                <a class="wk-link" href="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0)) ?>"><?= trab_nomina_safe($trabajador['nombre_completo'] ?? null) ?></a>
                                                <div class="wk-sub"><?= trab_nomina_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?> &middot; <?= trab_nomina_safe($trabajador['estado'] ?? null) ?></div>
                                            </td>
                                            <td><span class="wk-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span></td>
                                            <td class="is-end">
                                                <div class="wk-strong"><?= trab_nomina_money($trabajador['bruto_periodo'] ?? 0) ?></div>
                                                <div class="wk-sub">+<?= trab_nomina_money($trabajador['conceptos_a_favor'] ?? 0) ?> / -<?= trab_nomina_money($trabajador['conceptos_en_contra'] ?? 0) ?></div>
                                            </td>
                                            <td class="is-end">
                                                <div class="wk-strong"><?= trab_nomina_money($trabajador['deducciones_informativas'] ?? 0) ?></div>
                                                <div class="wk-sub">Ant. <?= trab_nomina_money($trabajador['anticipos_saldo'] ?? 0) ?> / Pr&eacute;st. <?= trab_nomina_money($trabajador['prestamos_saldo'] ?? 0) ?></div>
                                            </td>
                                            <td class="is-end">
                                                <div class="wk-strong"><?= trab_nomina_money($trabajador['pagos_caja_aplicados'] ?? 0) ?></div>
                                                <div class="wk-sub"><?= trab_nomina_num($trabajador['pagos_caja_pagados'] ?? 0) ?> pago(s), <?= trab_nomina_num($trabajador['pagos_caja_revertidos'] ?? 0) ?> rev.</div>
                                            </td>
                                            <td class="is-end">
                                                <div class="wk-strong"><?= trab_nomina_money($trabajador['neto_sugerido'] ?? 0) ?></div>
                                                <div class="wk-sub">Pendiente <?= trab_nomina_money($trabajador['pendiente_pago_sugerido'] ?? 0) ?></div>
                                            </td>
                                            <td>
                                                <?php if (trim((string)($trabajador['motivo_bloqueo_nomina'] ?? '')) !== ''): ?>
                                                    <div class="wk-sub" style="color:var(--wk-danger);font-weight:700"><?= trab_nomina_safe($trabajador['motivo_bloqueo_nomina'] ?? '') ?></div>
                                                <?php else: ?>
                                                    <div class="wk-sub">Conceptos <?= trab_nomina_num($trabajador['conceptos_count'] ?? 0) ?> &middot; rev. <?= trab_nomina_money($trabajador['reversiones_detectadas'] ?? 0) ?></div>
                                                <?php endif; ?>
                                                <a class="wk-recibo" href="<?= $reciboUrl ?>"><i class="fas fa-receipt"></i> Recibo</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
