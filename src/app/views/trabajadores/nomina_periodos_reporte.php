<?php
$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$porEstado = is_array($reporte['por_estado'] ?? null) ? $reporte['por_estado'] : [];
$filtros = is_array($reporte['filtros_normalizados'] ?? null) ? $reporte['filtros_normalizados'] : [];

if (!function_exists('trab_nomina_report_safe')) {
    function trab_nomina_report_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_nomina_report_money')) {
    function trab_nomina_report_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_nomina_report_num')) {
    function trab_nomina_report_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_nomina_report_date')) {
    function trab_nomina_report_date($value)
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

if (!function_exists('trab_nomina_report_datetime')) {
    function trab_nomina_report_datetime($value)
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

if (!function_exists('trab_nomina_report_badge_class')) {
    function trab_nomina_report_badge_class($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'aprobado') {
            return 'payroll-report-badge payroll-report-badge-ok';
        }
        if ($estado === 'anulado') {
            return 'payroll-report-badge payroll-report-badge-danger';
        }
        return 'payroll-report-badge payroll-report-badge-warn';
    }
}

if (!function_exists('trab_nomina_report_label')) {
    function trab_nomina_report_label($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'aprobado') {
            return 'Aprobado';
        }
        if ($estado === 'anulado') {
            return 'Anulado';
        }
        if ($estado === 'cerrado') {
            return 'Cerrado';
        }

        return 'Snapshot';
    }
}

if (!function_exists('trab_nomina_report_user')) {
    function trab_nomina_report_user(array $registro, string $prefijo)
    {
        $nombre = trim((string)($registro[$prefijo . '_nombre'] ?? ''));
        if ($nombre !== '') {
            return trab_nomina_report_safe($nombre);
        }

        return trab_nomina_report_safe($registro[$prefijo . '_login'] ?? '');
    }
}

$fechaInicio = (string)($filtros['fecha_inicio'] ?? '');
$fechaFin = (string)($filtros['fecha_fin'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
$tipoPeriodo = (string)($filtros['tipo_periodo'] ?? 'todos');
$buscar = (string)($filtros['buscar'] ?? '');
$exportParams = [
    'fecha_inicio' => $fechaInicio !== '' ? $fechaInicio : null,
    'fecha_fin' => $fechaFin !== '' ? $fechaFin : null,
    'estado' => $estado !== '' ? $estado : 'todos',
    'tipo_periodo' => $tipoPeriodo !== '' ? $tipoPeriodo : 'todos',
    'buscar' => $buscar !== '' ? $buscar : null,
];
$exportParams = array_filter($exportParams, static function ($value) {
    return $value !== null && trim((string)$value) !== '';
});
$exportQuery = http_build_query($exportParams);
$exportUrl = url('trabajadores/nomina/periodos/exportar' . ($exportQuery !== '' ? '?' . $exportQuery : ''));
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');
.payroll-snapshot-report {
    --pr-brand: var(--brand-primary, #1B2746);
    --pr-accent: var(--brand-accent, #BD9441);
    --pr-line: color-mix(in srgb, var(--pr-brand) 7%, #E7E1D4);
    --pr-gold: var(--brand-accent, #BD9441);
    --pr-gold-soft: color-mix(in srgb, var(--pr-accent) 15%, #FFFFFF);
    --pr-gold-line: color-mix(in srgb, var(--pr-accent) 42%, #E4D4B0);
    --pr-gold-ink: color-mix(in srgb, var(--pr-accent) 58%, var(--pr-brand));
    --pr-text: color-mix(in srgb, var(--pr-brand) 46%, #707B8C);
    --pr-muted: #8791A2;
    --pr-heading: color-mix(in srgb, var(--pr-brand) 66%, #566172);
    --pr-ring: color-mix(in srgb, var(--pr-gold) 32%, transparent);
    --pr-success: #1E9E63; --pr-success-bg: #E7F4EC;
    --pr-warning: #C2841C; --pr-warning-bg: #FAF0DC;
    --pr-danger: #B4392B; --pr-danger-bg: #F8EAE5;
    --pr-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --pr-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: var(--pr-text);
    font-family: var(--pr-sans);
    font-weight: 450;
    line-height: 1.5;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--pr-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, #FBF8F2, #F6F2EA);
}
/* Neutraliza el .grid{min-height:200px} global de performance-optimization.css */
.payroll-snapshot-report .grid { min-height: 0; }

/* Hero claro (antes banda oscura) */
.payroll-snapshot-report .payroll-hero { background: transparent; color: var(--pr-text); padding: 24px 24px 0; }
.payroll-snapshot-report .payroll-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.payroll-snapshot-report .payroll-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--pr-gold), var(--pr-brand) 54%, color-mix(in srgb, var(--pr-brand) 68%, var(--pr-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--pr-brand) 72%, transparent);
}
.payroll-snapshot-report .payroll-kicker { color: var(--pr-muted); opacity: 1; font-size: .72rem; letter-spacing: .11em; text-transform: uppercase; font-weight: 600; }
.payroll-snapshot-report .payroll-title { margin: 2px 0 0; font-family: var(--pr-serif); color: var(--pr-heading); font-weight: 650; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; letter-spacing: 0; }
.payroll-snapshot-report .payroll-subtitle { margin: 6px 0 0; max-width: 52rem; color: var(--pr-muted); font-size: .92rem; font-weight: 500; }

/* Stats hero → tarjetas con barra de acento */
.payroll-snapshot-report .payroll-stat-hero {
    position: relative; background: #FFFFFF; border: 1px solid var(--pr-line); border-radius: 14px;
    color: var(--pr-text); padding: 13px 14px 13px 18px;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22);
    animation: prRise .5s cubic-bezier(.22,1,.36,1) backwards;
}
.payroll-snapshot-report .payroll-stat-hero:nth-child(1) { animation-delay: .05s; }
.payroll-snapshot-report .payroll-stat-hero:nth-child(2) { animation-delay: .11s; }
.payroll-snapshot-report .payroll-stat-hero:nth-child(3) { animation-delay: .17s; }
.payroll-snapshot-report .payroll-stat-hero:nth-child(4) { animation-delay: .23s; }
.payroll-snapshot-report .payroll-stat-hero::before { content: ""; position: absolute; left: 7px; top: 13px; bottom: 13px; width: 3px; border-radius: 999px; background: var(--pr-line); }
.payroll-snapshot-report .payroll-stat-hero.is-ok::before { background: color-mix(in srgb, var(--pr-success) 55%, #fff); }
.payroll-snapshot-report .payroll-stat-hero.is-gold::before { background: var(--pr-gold-line); }
.payroll-snapshot-report .payroll-stat-hero.is-warn::before { background: color-mix(in srgb, var(--pr-warning) 55%, #fff); }
.payroll-snapshot-report .payroll-stat-hero .text-xs { opacity: 1; color: var(--pr-muted); font-weight: 600; letter-spacing: .04em; text-transform: uppercase; font-size: .66rem; }
.payroll-snapshot-report .payroll-stat-hero.is-ok .text-2xl { color: var(--pr-success); }
.payroll-snapshot-report .payroll-stat-hero .text-2xl, .payroll-snapshot-report .payroll-stat-hero .text-xl { font-family: var(--pr-serif); color: var(--pr-heading); }
@keyframes prRise { from { opacity: 0; transform: translateY(7px); } to { opacity: 1; transform: none; } }

/* Pesos boutique */
.payroll-snapshot-report .font-black { font-weight: 650; }
.payroll-snapshot-report .font-bold { font-weight: 620; }

/* Superficies y radios serenos */
.payroll-snapshot-report .payroll-panel { border: 1px solid var(--pr-line); background: #FFFFFF; border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -26px rgba(27,39,70,.3); }
.payroll-snapshot-report .payroll-panel > div.border-b { background: linear-gradient(180deg, color-mix(in srgb, #FCFAF5 82%, #fff), rgba(255,255,255,.92)); border-radius: 16px 16px 0 0; }
.payroll-snapshot-report .payroll-panel h2 { font-family: var(--pr-serif); color: var(--pr-heading); font-weight: 650; }
.payroll-snapshot-report .payroll-stat { position: relative; border: 1px solid var(--pr-line); background: #FFFFFF; border-radius: 14px; padding: 13px 14px 13px 18px; }
.payroll-snapshot-report .payroll-stat::before { content: ""; position: absolute; left: 7px; top: 13px; bottom: 13px; width: 3px; border-radius: 999px; background: var(--pr-line); }
.payroll-snapshot-report .payroll-stat:nth-child(1)::before { background: var(--pr-gold-line); }
.payroll-snapshot-report .payroll-stat:nth-child(3)::before { background: color-mix(in srgb, var(--pr-success) 45%, #fff); }
.payroll-snapshot-report .payroll-stat .text-2xl { font-family: var(--pr-serif); color: var(--pr-heading); }
.payroll-snapshot-report .payroll-stat-soft { background: #FCFAF5; }

/* Botones */
.payroll-snapshot-report .payroll-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    min-height: 40px; padding: 0 15px; border-radius: 11px;
    border: 1px solid var(--pr-line); background: rgba(255,255,255,.86); color: var(--pr-text);
    font-weight: 650; font-size: .84rem; white-space: nowrap; text-decoration: none; cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, color .16s ease, background .16s ease;
}
.payroll-snapshot-report .payroll-btn:hover { transform: translateY(-1px); border-color: var(--pr-gold-line); color: var(--pr-gold-ink); }
.payroll-snapshot-report .payroll-btn-primary {
    position: relative; overflow: hidden;
    background: linear-gradient(135deg, var(--pr-gold), color-mix(in srgb, var(--pr-gold) 76%, #000));
    border-color: transparent; color: #fff;
    box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--pr-gold) 58%, transparent);
}
.payroll-snapshot-report .payroll-btn-primary:hover { color: #fff; border-color: transparent; }
.payroll-snapshot-report .payroll-btn-primary::after {
    content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 40%; pointer-events: none;
    background: linear-gradient(100deg, transparent, rgba(255,255,255,.45), transparent);
    transform: translateX(-170%) skewX(-18deg);
}
.payroll-snapshot-report .payroll-btn-primary:hover::after { transition: transform .7s ease; transform: translateX(330%) skewX(-18deg); }

/* Controles + labels de filtro */
.payroll-snapshot-report .payroll-input { width: 100%; min-height: 40px; border: 1px solid var(--pr-line); background: #FCFAF5; border-radius: 11px; padding: 0 12px; color: var(--pr-text); font-weight: 560; font-size: .85rem; transition: border-color .16s ease, box-shadow .16s ease; }
.payroll-snapshot-report .payroll-input:focus { border-color: var(--pr-gold); box-shadow: 0 0 0 3px var(--pr-ring); outline: none; }
.payroll-snapshot-report select.payroll-input { cursor: pointer; }
.payroll-snapshot-report .payroll-label { color: var(--pr-muted); font-size: .66rem; font-weight: 650; letter-spacing: .045em; text-transform: uppercase; }
.payroll-snapshot-report .payroll-filter-field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.payroll-snapshot-report .payroll-filter-label { color: var(--pr-muted); font-size: .64rem; font-weight: 650; letter-spacing: .06em; line-height: 1; text-transform: uppercase; }

/* Badges semánticos redondeados */
.payroll-snapshot-report .payroll-report-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 999px; border: 1px solid var(--pr-line); background: #FCFAF5; color: var(--pr-muted); font-size: .74rem; font-weight: 650; }
.payroll-snapshot-report .payroll-report-badge-ok { background: var(--pr-success-bg); border-color: color-mix(in srgb, var(--pr-success) 26%, #fff); color: color-mix(in srgb, var(--pr-success) 78%, #000); }
.payroll-snapshot-report .payroll-report-badge-warn { background: var(--pr-warning-bg); border-color: color-mix(in srgb, var(--pr-warning) 28%, #fff); color: color-mix(in srgb, var(--pr-warning) 82%, #000); }
.payroll-snapshot-report .payroll-report-badge-danger { background: var(--pr-danger-bg); border-color: color-mix(in srgb, var(--pr-danger) 26%, #fff); color: color-mix(in srgb, var(--pr-danger) 82%, #000); }
/* Badges que son enlaces: afordancia de botón */
.payroll-snapshot-report a.payroll-report-badge { background: #FFFFFF; border-color: var(--pr-line); color: var(--pr-text); transition: transform .16s ease, border-color .16s ease, color .16s ease; }
.payroll-snapshot-report a.payroll-report-badge:hover { transform: translateY(-1px); border-color: var(--pr-gold-line); color: var(--pr-gold-ink); }
.payroll-snapshot-report a.payroll-report-badge i { color: var(--pr-gold-ink); }

/* Tabla */
.payroll-snapshot-report .payroll-table th { color: var(--pr-muted); font-size: .66rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.payroll-snapshot-report .payroll-table thead { background: #FCFAF5; }
.payroll-snapshot-report .payroll-table td, .payroll-snapshot-report .payroll-table th { border-bottom: 1px solid var(--pr-line); padding: 12px 13px; vertical-align: top; }
.payroll-snapshot-report .payroll-table tbody tr { transition: background .14s ease; }
.payroll-snapshot-report .payroll-table tbody tr:hover td { background: #FBF8F2; }
.payroll-snapshot-report .payroll-table strong { color: var(--pr-heading); font-weight: 620; }

/* Calidez sobre los grises fríos de Tailwind */
.payroll-snapshot-report .text-slate-500, .payroll-snapshot-report .text-slate-600 { color: var(--pr-muted) !important; }
.payroll-snapshot-report .border-slate-200 { border-color: var(--pr-line) !important; }
.payroll-snapshot-report .text-4xl.text-slate-300 { font-size: 1.2rem !important; }
.payroll-snapshot-report .text-4xl.text-slate-300 > i { width: 54px; height: 54px; display: grid; place-items: center; margin: 0 auto; border-radius: 16px; background: var(--pr-gold-soft); color: var(--pr-gold-ink); }

@media (min-width: 1440px) {
    .payroll-snapshot-report .payroll-filters { grid-template-columns: 140px 140px 150px 160px minmax(180px,1fr) auto auto !important; align-items: end; column-gap: 10px; }
    .payroll-snapshot-report .payroll-filters .payroll-btn { align-self: end; padding: 0 16px; }
}
@media (prefers-reduced-motion: reduce) {
    .payroll-snapshot-report .payroll-btn-primary::after { display: none; }
    .payroll-snapshot-report .payroll-stat-hero { animation: none !important; }
    .payroll-snapshot-report * { transition-duration: .01ms !important; }
}
</style>

<div class="payroll-snapshot-report">
    <section class="payroll-hero">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="payroll-lockup">
                <div class="payroll-hero-icon"><i class="fas fa-file-lines"></i></div>
                <div>
                    <div class="payroll-kicker">Personal &middot; Informes</div>
                    <h1 class="payroll-title">Snapshots de pre-n&oacute;mina</h1>
                    <p class="payroll-subtitle">
                        Historial de solo lectura de periodos cerrados, aprobados o anulados. Consulta importes congelados del snapshot; no recalcula n&oacute;mina, no registra pagos y no modifica Caja.
                    </p>
                </div>
            </div>
            <span class="payroll-report-badge">
                <i class="fas fa-lock"></i>
                Solo lectura
            </span>
        </div>
    </section>

    <section class="p-6 space-y-4">
        <?php $subnav_section = 'personal'; $subnav_active = 'informes'; include APP_PATH . '/views/partials/section_subnav.php'; ?>
        <div class="flex flex-wrap items-center gap-2">
            <?php $back_arrow_href = back_url('trabajadores/informes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="payroll-btn ms-back-legacy" href="<?= back_url('trabajadores/informes') ?>">
                <i class="fas fa-arrow-left"></i>
                Informes
            </a>
            <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?>
            <a class="payroll-btn" href="<?= $exportUrl ?>">
                <i class="fas fa-file-csv"></i>
                Exportar CSV
            </a>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="payroll-stat-hero">
                <div class="text-xs opacity-75">Snapshots</div>
                <div class="text-2xl font-black"><?= trab_nomina_report_num($resumen['total_registros'] ?? 0) ?></div>
            </div>
            <div class="payroll-stat-hero is-ok">
                <div class="text-xs opacity-75">Aprobados</div>
                <div class="text-2xl font-black"><?= trab_nomina_report_num($resumen['aprobados_count'] ?? 0) ?></div>
            </div>
            <div class="payroll-stat-hero is-gold">
                <div class="text-xs opacity-75">Neto sugerido</div>
                <div class="text-2xl font-black"><?= trab_nomina_report_money($resumen['neto_sugerido_total'] ?? 0) ?></div>
            </div>
            <div class="payroll-stat-hero is-warn">
                <div class="text-xs opacity-75">Pendiente</div>
                <div class="text-2xl font-black"><?= trab_nomina_report_money($resumen['pendiente_pago_total'] ?? 0) ?></div>
            </div>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="payroll-panel p-5">
                <strong>Reporte no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas de snapshots de pre-nomina para consultar el historial.</p>
            </div>
        <?php else: ?>
            <div class="payroll-panel p-4">
                <form method="GET" action="<?= url('trabajadores/nomina/periodos/reporte') ?>" class="payroll-filters grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="payroll-filter-field">
                        <span class="payroll-filter-label">Desde</span>
                        <input class="payroll-input" type="date" name="fecha_inicio" value="<?= trab_nomina_report_safe($fechaInicio, '') ?>">
                    </label>
                    <label class="payroll-filter-field">
                        <span class="payroll-filter-label">Hasta</span>
                        <input class="payroll-input" type="date" name="fecha_fin" value="<?= trab_nomina_report_safe($fechaFin, '') ?>">
                    </label>
                    <label class="payroll-filter-field">
                        <span class="payroll-filter-label">Estado</span>
                        <select class="payroll-input" name="estado">
                            <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="cerrado" <?= $estado === 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
                            <option value="aprobado" <?= $estado === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="anulado" <?= $estado === 'anulado' ? 'selected' : '' ?>>Anulado</option>
                        </select>
                    </label>
                    <label class="payroll-filter-field">
                        <span class="payroll-filter-label">Tipo de periodo</span>
                        <select class="payroll-input" name="tipo_periodo">
                            <option value="todos" <?= $tipoPeriodo === 'todos' ? 'selected' : '' ?>>Todos los tipos</option>
                            <option value="semanal" <?= $tipoPeriodo === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                            <option value="quincenal" <?= $tipoPeriodo === 'quincenal' ? 'selected' : '' ?>>Quincenal</option>
                            <option value="mensual" <?= $tipoPeriodo === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                            <option value="manual" <?= $tipoPeriodo === 'manual' ? 'selected' : '' ?>>Manual</option>
                        </select>
                    </label>
                    <label class="payroll-filter-field">
                        <span class="payroll-filter-label">Buscar</span>
                        <input class="payroll-input" type="search" name="buscar" value="<?= trab_nomina_report_safe($buscar, '') ?>" placeholder="Folio, etiqueta, usuario o motivo">
                    </label>
                    <button class="payroll-btn payroll-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <a class="payroll-btn" href="<?= url('trabajadores/nomina/periodos/reporte') ?>">
                        <i class="fas fa-rotate-left"></i>
                        Limpiar
                    </a>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="payroll-stat payroll-stat-soft">
                    <div class="payroll-label">Bruto congelado</div>
                    <div class="text-2xl font-black mt-1"><?= trab_nomina_report_money($resumen['bruto_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1"><?= trab_nomina_report_num($resumen['trabajadores_total'] ?? 0) ?> trabajador(es) acumulados</div>
                </div>
                <div class="payroll-stat">
                    <div class="payroll-label">Deducciones</div>
                    <div class="text-2xl font-black mt-1"><?= trab_nomina_report_money($resumen['deducciones_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1">Anticipos y prestamos informativos del snapshot</div>
                </div>
                <div class="payroll-stat">
                    <div class="payroll-label">Pagos Caja aplicados</div>
                    <div class="text-2xl font-black mt-1"><?= trab_nomina_report_money($resumen['pagos_caja_aplicados_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1">Historico congelado; no mueve Caja</div>
                </div>
                <div class="payroll-stat">
                    <div class="payroll-label">Reversiones detectadas</div>
                    <div class="text-2xl font-black mt-1"><?= trab_nomina_report_money($resumen['reversiones_detectadas_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1">Dato informativo del snapshot</div>
                </div>
            </div>

            <div class="payroll-panel overflow-hidden">
                <div class="p-4 border-b border-slate-200">
                    <h2 class="font-black">Resumen por estado</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="payroll-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Estado</th>
                                <th class="text-right">Snapshots</th>
                                <th class="text-right">Trabajadores</th>
                                <th class="text-right">Neto sugerido</th>
                                <th class="text-right">Pendiente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($porEstado)): ?>
                                <tr><td colspan="5" class="text-center text-slate-500">Sin snapshots para resumir.</td></tr>
                            <?php else: ?>
                                <?php foreach ($porEstado as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="<?= trab_nomina_report_badge_class($item['estado'] ?? '') ?>">
                                                <?= trab_nomina_report_label($item['estado'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td class="text-right"><?= trab_nomina_report_num($item['total'] ?? 0) ?></td>
                                        <td class="text-right"><?= trab_nomina_report_num($item['trabajadores_total'] ?? 0) ?></td>
                                        <td class="text-right"><?= trab_nomina_report_money($item['neto_sugerido_total'] ?? 0) ?></td>
                                        <td class="text-right"><?= trab_nomina_report_money($item['pendiente_pago_total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="payroll-panel overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-black text-lg">Snapshots persistentes</h2>
                        <p class="text-sm text-slate-500 mt-1">Listado limitado a los registros filtrados del hotel actual.</p>
                    </div>
                    <span class="payroll-report-badge">
                        <i class="fas fa-list-check"></i>
                        <?= trab_nomina_report_num(count($registros)) ?> visible(s)
                    </span>
                </div>

                <?php if (empty($registros)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-box-archive"></i></div>
                        <h3 class="font-black text-lg">Sin snapshots de pre-nomina</h3>
                        <p class="text-sm text-slate-500 mt-1">Ajusta los filtros o cierra un periodo desde la pantalla de periodos cuando corresponda.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="payroll-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Periodo</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Trabajadores</th>
                                    <th class="text-right">Importes</th>
                                    <th class="text-left">Responsables</th>
                                    <th class="text-left">Motivo</th>
                                    <th class="text-right">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $registro): ?>
                                    <tr>
                                        <td>
                                            <div class="font-black"><?= trab_nomina_report_safe($registro['etiqueta'] ?? 'Snapshot') ?></div>
                                            <div class="text-xs text-slate-500">
                                                #<?= (int)($registro['id'] ?? 0) ?> / <?= trab_nomina_report_safe($registro['tipo_periodo'] ?? '') ?>
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                <?= trab_nomina_report_date($registro['fecha_inicio'] ?? '') ?> - <?= trab_nomina_report_date($registro['fecha_fin'] ?? '') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="<?= trab_nomina_report_badge_class($registro['estado'] ?? '') ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= trab_nomina_report_label($registro['estado'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td class="text-right font-black"><?= trab_nomina_report_num($registro['trabajadores_total'] ?? 0) ?></td>
                                        <td class="text-right">
                                            <div><span class="text-slate-500">Bruto:</span> <strong><?= trab_nomina_report_money($registro['bruto_total'] ?? 0) ?></strong></div>
                                            <div><span class="text-slate-500">Neto:</span> <strong><?= trab_nomina_report_money($registro['neto_sugerido_total'] ?? 0) ?></strong></div>
                                            <div><span class="text-slate-500">Pendiente:</span> <strong><?= trab_nomina_report_money($registro['pendiente_pago_total'] ?? 0) ?></strong></div>
                                        </td>
                                        <td>
                                            <div class="text-xs text-slate-500">Cierre</div>
                                            <div class="font-bold"><?= trab_nomina_report_user($registro, 'cerrado_por') ?></div>
                                            <div class="text-xs text-slate-500"><?= trab_nomina_report_datetime($registro['cerrado_at'] ?? null) ?></div>
                                            <?php if (!empty($registro['aprobado_at'])): ?>
                                                <div class="text-xs text-slate-500 mt-2">Aprobacion</div>
                                                <div class="font-bold"><?= trab_nomina_report_user($registro, 'aprobado_por') ?></div>
                                                <div class="text-xs text-slate-500"><?= trab_nomina_report_datetime($registro['aprobado_at'] ?? null) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($registro['anulado_at'])): ?>
                                                <div class="text-xs text-slate-500 mt-2">Anulacion</div>
                                                <div class="font-bold"><?= trab_nomina_report_user($registro, 'anulado_por') ?></div>
                                                <div class="text-xs text-slate-500"><?= trab_nomina_report_datetime($registro['anulado_at'] ?? null) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="max-w-[260px]">
                                            <div class="text-sm text-slate-600"><?= trab_nomina_report_safe($registro['motivo_anulacion'] ?? '', 'Sin motivo') ?></div>
                                        </td>
                                        <td class="text-right">
                                            <a class="payroll-report-badge" href="<?= url('trabajadores/nomina/periodos/' . (int)($registro['id'] ?? 0)) ?>">
                                                <i class="fas fa-eye"></i>
                                                Ver snapshot
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
