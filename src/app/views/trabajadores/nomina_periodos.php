<?php
$periodosNomina = is_array($periodosNomina ?? null) ? $periodosNomina : [];
$tablaDisponible = $tablaDisponible ?? false;
$tablaPersistenteDisponible = $tablaPersistenteDisponible ?? false;
$periodosPersistentes = is_array($periodosPersistentes ?? null) ? $periodosPersistentes : [];
$cierreTokens = is_array($cierreTokens ?? null) ? $cierreTokens : [];
$modo = (string)($modo ?? 'lista');
$periodos = is_array($periodosNomina['periodos'] ?? null) ? $periodosNomina['periodos'] : [];
$periodoDetalle = is_array($periodosNomina['periodo_detalle'] ?? null) ? $periodosNomina['periodo_detalle'] : null;
$preview = is_array($periodosNomina['preview'] ?? null) ? $periodosNomina['preview'] : [];
$trabajadores = is_array($preview['trabajadores'] ?? null) ? $preview['trabajadores'] : [];
$resumen = is_array($preview['resumen'] ?? null) ? $preview['resumen'] : [];
$filtros = is_array($periodosNomina['filtros_normalizados'] ?? null) ? $periodosNomina['filtros_normalizados'] : [];
$bloqueos = is_array($periodosNomina['bloqueos'] ?? null) ? $periodosNomina['bloqueos'] : [];

if (!function_exists('trab_periodo_safe')) {
    function trab_periodo_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_periodo_money')) {
    function trab_periodo_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_periodo_num')) {
    function trab_periodo_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_periodo_date')) {
    function trab_periodo_date($value)
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

if (!function_exists('trab_periodo_badge_class')) {
    function trab_periodo_badge_class($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'listo_revision' || $estado === 'sin_saldo') {
            return 'period-badge period-badge-ok';
        }
        if ($estado === 'requiere_revision') {
            return 'period-badge period-badge-warn';
        }
        if ($estado === 'bloqueado') {
            return 'period-badge period-badge-danger';
        }
        return 'period-badge';
    }
}

if (!function_exists('trab_periodo_snapshot_badge_class')) {
    function trab_periodo_snapshot_badge_class($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'aprobado') {
            return 'period-badge period-badge-ok';
        }
        if ($estado === 'anulado') {
            return 'period-badge period-badge-danger';
        }
        return 'period-badge period-badge-warn';
    }
}

if (!function_exists('trab_periodo_snapshot_label')) {
    function trab_periodo_snapshot_label($estado)
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

$tipoPeriodo = (string)($filtros['tipo_periodo'] ?? 'semanal');
$fechaBase = (string)($filtros['fecha_base_raw'] ?? ($filtros['fecha_base'] ?? date('Y-m-d')));
$fechaInicio = (string)($filtros['fecha_inicio_raw'] ?? ($filtros['fecha_inicio'] ?? ''));
$fechaFin = (string)($filtros['fecha_fin_raw'] ?? ($filtros['fecha_fin'] ?? ''));
$estado = (string)($filtros['estado'] ?? 'activos');
$rolLaboral = (string)($filtros['rol_laboral'] ?? '');
$incluirPagosCaja = !empty($filtros['incluir_pagos_caja']);
$periodoActualInicio = (string)($periodoDetalle['fecha_inicio'] ?? $fechaInicio);
$periodoActualFin = (string)($periodoDetalle['fecha_fin'] ?? $fechaFin);

$baseQuery = [
    'tipo_periodo' => $tipoPeriodo,
    'fecha_base' => $fechaBase,
    'estado' => $estado,
    'rol_laboral' => $rolLaboral,
    'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
];
$previewQuery = http_build_query(array_merge($baseQuery, [
    'fecha_inicio' => $periodoActualInicio,
    'fecha_fin' => $periodoActualFin,
]));
$previewNominaQuery = http_build_query([
    'fecha_inicio' => $periodoActualInicio,
    'fecha_fin' => $periodoActualFin,
    'estado' => $estado,
    'rol_laboral' => $rolLaboral,
    'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
]);
?>

<style>
.nomina-periodos {
    --period-brand: var(--brand-primary, #1f3f46);
    --period-accent: var(--brand-accent, #b58a3c);
    --period-line: color-mix(in srgb, var(--period-brand) 10%, #e5e7eb);
    --period-soft: color-mix(in srgb, var(--period-accent) 7%, #f8fafc);
    color: #243142;
}
.nomina-periodos .period-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--period-brand) 92%, #111827), color-mix(in srgb, var(--period-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.nomina-periodos .period-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.nomina-periodos .period-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.nomina-periodos .period-subtitle {
    margin-top: 8px;
    max-width: 60rem;
    color: rgba(255,255,255,.86);
}
.nomina-periodos .period-stat-hero {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.nomina-periodos .period-panel,
.nomina-periodos .period-card {
    border: 1px solid var(--period-line);
    background: rgba(255,255,255,.94);
}
.nomina-periodos .period-card-active {
    outline: 2px solid color-mix(in srgb, var(--period-accent) 44%, transparent);
}
.nomina-periodos .period-stat {
    border: 1px solid var(--period-line);
    background: #fff;
    padding: 14px;
}
.nomina-periodos .period-stat-soft {
    background: var(--period-soft);
}
.nomina-periodos .period-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--period-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
    white-space: nowrap;
}
.nomina-periodos .period-btn-primary {
    background: var(--period-brand);
    border-color: var(--period-brand);
    color: #fff;
}
.nomina-periodos .period-btn-compact {
    min-height: 32px;
    padding: 0 10px;
    font-size: .82rem;
}
.nomina-periodos .period-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--period-line);
    background: #fff;
    padding: 0 12px;
}
.nomina-periodos .period-filter {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}
.nomina-periodos .period-filter-label {
    color: #64748b;
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .06em;
    line-height: 1;
    text-transform: uppercase;
}
.nomina-periodos .period-filter-hint {
    color: #64748b;
    font-size: .72rem;
    font-weight: 700;
    line-height: 1.15;
}
.nomina-periodos .period-filter-action {
    align-self: stretch;
}
.nomina-periodos .period-filter-action .period-btn {
    width: 100%;
}
.nomina-periodos .period-check {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    border: 1px solid var(--period-line);
    background: #fff;
    padding: 0 12px;
    font-weight: 800;
    color: #334155;
}
.nomina-periodos .period-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.nomina-periodos .period-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--period-line);
    background: var(--period-soft);
    font-size: .78rem;
    font-weight: 800;
}
.nomina-periodos .period-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.nomina-periodos .period-badge-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.nomina-periodos .period-badge-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}
.nomina-periodos .period-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.nomina-periodos .period-table td,
.nomina-periodos .period-table th {
    border-bottom: 1px solid var(--period-line);
    padding: 13px 12px;
    vertical-align: top;
}
</style>

<style id="nomina-periodos-boutique">
@import url('<?= asset('vendor/fonts/marca.css') ?>');
.nomina-periodos {
    --period-brand: var(--brand-primary, #1B2746) !important;
    --period-accent: var(--brand-accent, #BD9441) !important;
    --period-line: color-mix(in srgb, var(--brand-primary, #1B2746) 7%, #E7E1D4) !important;
    --period-soft: color-mix(in srgb, var(--brand-accent, #BD9441) 12%, #FCFAF5) !important;
    --wk-gold: var(--brand-accent, #BD9441);
    --wk-gold-soft: color-mix(in srgb, var(--brand-accent, #BD9441) 15%, #FFFFFF);
    --wk-gold-line: color-mix(in srgb, var(--brand-accent, #BD9441) 42%, #E4D4B0);
    --wk-gold-ink: color-mix(in srgb, var(--brand-accent, #BD9441) 58%, var(--period-brand));
    --pd-text: color-mix(in srgb, var(--period-brand) 46%, #707B8C);
    --pd-muted: #8791A2;
    --pd-heading: color-mix(in srgb, var(--period-brand) 66%, #566172);
    --pd-ring: color-mix(in srgb, var(--wk-gold) 32%, transparent);
    --pd-success: #1E9E63; --pd-success-bg: #E7F4EC;
    --pd-warning: #C2841C; --pd-warning-bg: #FAF0DC;
    --pd-danger: #B4392B; --pd-danger-bg: #F8EAE5;
    color: var(--pd-text) !important;
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
    font-weight: 450;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--wk-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, #FBF8F2, #F6F2EA) !important;
}

/* Hero claro (antes: banda oscura con degradado) */
.nomina-periodos .period-hero { background: transparent !important; color: var(--pd-text) !important; padding: 24px 24px 0 !important; }
.nomina-periodos .period-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.nomina-periodos .period-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--wk-gold), var(--period-brand) 54%, color-mix(in srgb, var(--period-brand) 68%, var(--wk-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--period-brand) 72%, transparent);
}
.nomina-periodos .period-kicker { color: var(--pd-muted) !important; opacity: 1 !important; font-weight: 600 !important; letter-spacing: .11em !important; }
.nomina-periodos .period-title { margin: 2px 0 0 !important; font-family: 'Cormorant Garamond', Georgia, 'Times New Roman', serif !important; font-weight: 650 !important; font-size: clamp(2rem, 3.6vw, 2.9rem) !important; color: var(--pd-heading) !important; line-height: 1 !important; }
.nomina-periodos .period-subtitle { color: var(--pd-muted) !important; font-size: .92rem !important; font-weight: 500 !important; max-width: 52rem; }
.nomina-periodos .period-stat-hero {
    position: relative; background: #FFFFFF !important; border: 1px solid var(--period-line) !important; border-radius: 14px !important;
    color: var(--pd-text) !important; padding: 13px 14px 13px 18px !important;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22) !important;
    animation: pdRise .5s cubic-bezier(.22,1,.36,1) backwards;
}
.nomina-periodos .period-stat-hero:nth-child(1) { animation-delay: .05s; }
.nomina-periodos .period-stat-hero:nth-child(2) { animation-delay: .11s; }
.nomina-periodos .period-stat-hero:nth-child(3) { animation-delay: .17s; }
.nomina-periodos .period-stat-hero:nth-child(4) { animation-delay: .23s; }
.nomina-periodos .period-stat-hero::before { content: ""; position: absolute; left: 7px; top: 13px; bottom: 13px; width: 3px; border-radius: 999px; background: var(--period-line); }
.nomina-periodos .period-stat-hero.is-gold::before { background: var(--wk-gold-line); }
.nomina-periodos .period-stat-hero.is-warn::before { background: color-mix(in srgb, var(--pd-warning) 55%, #fff); }
.nomina-periodos .period-stat-hero .text-xs { opacity: 1 !important; color: var(--pd-muted); font-weight: 600; letter-spacing: .04em; text-transform: uppercase; font-size: .66rem; }
.nomina-periodos .period-stat-hero .text-2xl, .nomina-periodos .period-stat-hero .text-xl { font-family: 'Cormorant Garamond', Georgia, serif !important; color: var(--pd-heading); }
@keyframes pdRise { from { opacity: 0; transform: translateY(7px); } to { opacity: 1; transform: none; } }

/* Pesos boutique: nunca 800/900 */
.nomina-periodos .font-black { font-weight: 650 !important; }
.nomina-periodos .font-bold { font-weight: 620 !important; }
.nomina-periodos .period-label, .nomina-periodos .period-filter-label { color: var(--pd-muted) !important; font-weight: 650 !important; }
.nomina-periodos .period-filter-hint { color: var(--pd-muted) !important; font-weight: 500 !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.nomina-periodos .period-check { font-weight: 600 !important; color: var(--pd-text) !important; border-radius: 11px !important; background: #FCFAF5 !important; }
.nomina-periodos .period-check input[type=checkbox] { accent-color: var(--wk-gold); }

/* Calidez sobre los grises fríos de Tailwind que quedaban en el cuerpo */
.nomina-periodos .text-slate-500, .nomina-periodos .text-slate-600 { color: var(--pd-muted) !important; }
.nomina-periodos .border-slate-200 { border-color: var(--period-line) !important; }
.nomina-periodos .bg-slate-50 { background: #FCFAF5 !important; }
.nomina-periodos .period-panel > div.border-b { background: linear-gradient(180deg, color-mix(in srgb, #FCFAF5 82%, #fff), rgba(255,255,255,.92)); border-radius: 16px 16px 0 0; }
.nomina-periodos a.underline { color: var(--pd-heading); text-decoration-color: var(--wk-gold); text-underline-offset: 3px; }

/* Estados vacíos con el chip dorado de la casa */
.nomina-periodos .text-4xl.text-slate-300 { font-size: 1.2rem !important; }
.nomina-periodos .text-4xl.text-slate-300 > i {
    width: 54px; height: 54px; display: grid; place-items: center; margin: 0 auto;
    border-radius: 16px; background: var(--wk-gold-soft); color: var(--wk-gold-ink);
}

/* Superficies y radios serenos */
.nomina-periodos .period-panel, .nomina-periodos .period-card, .nomina-periodos .period-stat { background: #FFFFFF !important; border-color: var(--period-line) !important; border-radius: 16px !important; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -26px rgba(27,39,70,.3) !important; }
.nomina-periodos .period-stat { border-radius: 14px !important; }
.nomina-periodos .period-stat-soft { background: #FCFAF5 !important; }
.nomina-periodos .period-stat .text-2xl, .nomina-periodos .period-card h2, .nomina-periodos h2.font-black, .nomina-periodos h3.font-black { color: var(--pd-heading) !important; }
.nomina-periodos .period-card h2, .nomina-periodos h2.font-black, .nomina-periodos h3.font-black { font-family: 'Cormorant Garamond', Georgia, serif !important; font-size: 1.25rem; }
.nomina-periodos .period-stat .text-2xl { font-family: 'Cormorant Garamond', Georgia, serif !important; }
.nomina-periodos .period-card-active { border-color: var(--wk-gold-line) !important; box-shadow: 0 0 0 1px var(--wk-gold-line), 0 14px 32px -26px rgba(27,39,70,.32) !important; }

/* Controles */
.nomina-periodos .period-input { background: #FCFAF5 !important; border-color: var(--period-line) !important; border-radius: 11px !important; color: var(--pd-text) !important; font-weight: 560; transition: border-color .16s ease, box-shadow .16s ease; }
.nomina-periodos .period-input:focus { border-color: var(--wk-gold) !important; box-shadow: 0 0 0 3px var(--pd-ring) !important; outline: none; }
.nomina-periodos .period-btn {
    border-radius: 11px !important; background: rgba(255,255,255,.86) !important; border-color: var(--period-line) !important;
    color: var(--pd-text) !important; font-weight: 650 !important;
    transition: transform .16s ease, border-color .16s ease, color .16s ease, background .16s ease;
}
.nomina-periodos .period-btn:hover { transform: translateY(-1px); border-color: var(--wk-gold-line) !important; color: var(--wk-gold-ink) !important; }
.nomina-periodos .period-btn-primary {
    position: relative; overflow: hidden;
    background: linear-gradient(135deg, var(--wk-gold), color-mix(in srgb, var(--wk-gold) 76%, #000)) !important;
    border-color: transparent !important; color: #fff !important;
    box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--wk-gold) 58%, transparent);
}
.nomina-periodos .period-btn-primary:hover { color: #fff !important; }
.nomina-periodos .period-btn-primary::after {
    content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 40%; pointer-events: none;
    background: linear-gradient(100deg, transparent, rgba(255,255,255,.45), transparent);
    transform: translateX(-170%) skewX(-18deg);
}
.nomina-periodos .period-btn-primary:hover::after { transition: transform .7s ease; transform: translateX(330%) skewX(-18deg); }

/* Badges semánticos redondeados */
.nomina-periodos .period-badge { border-radius: 999px !important; font-weight: 650 !important; color: var(--pd-muted); }
/* Badges que son acciones (enlaces): afordancia de botón suave */
.nomina-periodos a.period-badge {
    background: #FFFFFF !important; border-color: var(--period-line) !important; color: var(--pd-text) !important;
    text-decoration: none; transition: transform .16s ease, border-color .16s ease, color .16s ease;
}
.nomina-periodos a.period-badge:hover { transform: translateY(-1px); border-color: var(--wk-gold-line) !important; color: var(--wk-gold-ink) !important; }
.nomina-periodos a.period-badge i { color: var(--wk-gold-ink); }
.nomina-periodos .period-badge-ok { background: var(--pd-success-bg) !important; border-color: color-mix(in srgb, var(--pd-success) 26%, #fff) !important; color: color-mix(in srgb, var(--pd-success) 78%, #000) !important; }
.nomina-periodos .period-badge-warn { background: var(--pd-warning-bg) !important; border-color: color-mix(in srgb, var(--pd-warning) 28%, #fff) !important; color: color-mix(in srgb, var(--pd-warning) 82%, #000) !important; }
.nomina-periodos .period-badge-danger { background: var(--pd-danger-bg) !important; border-color: color-mix(in srgb, var(--pd-danger) 26%, #fff) !important; color: color-mix(in srgb, var(--pd-danger) 82%, #000) !important; }

/* Tabla */
.nomina-periodos .period-table th { color: var(--pd-muted) !important; font-weight: 600 !important; }
.nomina-periodos .period-table td { color: var(--pd-text); }
.nomina-periodos .period-table tbody tr { transition: background .14s ease; }
.nomina-periodos .period-table tbody tr:hover td { background: #FBF8F2 !important; }

/* Toggle segmentado Periodos | Calcular pre-nómina */
.nomina-periodos .period-tabs {
    display: flex; gap: 4px; padding: 5px; width: fit-content; max-width: 100%;
    background: color-mix(in srgb, var(--period-brand) 5%, #FBF8F2);
    border: 1px solid var(--period-line); border-radius: 15px;
}
.nomina-periodos .period-tab {
    position: relative; display: inline-flex; align-items: center; gap: 8px;
    border: 1px solid transparent; border-radius: 11px;
    background: transparent; color: var(--pd-muted);
    min-height: 40px; padding: 0 16px 2px; font-size: .85rem; font-weight: 650; cursor: pointer;
    line-height: 1; white-space: nowrap; text-decoration: none;
    transition: color .16s ease, background .16s ease, box-shadow .16s ease;
}
.nomina-periodos .period-tab i { font-size: .8rem; color: color-mix(in srgb, var(--pd-muted) 80%, #fff); transition: color .16s ease; }
.nomina-periodos .period-tab:hover { color: var(--pd-heading); background: rgba(255,255,255,.65); }
.nomina-periodos .period-tab:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--pd-ring); }
.nomina-periodos .period-tab.is-active {
    background: #FFFFFF; color: var(--pd-heading); border-color: var(--period-line);
    box-shadow: 0 1px 2px rgba(27,39,70,.05), 0 6px 14px -8px color-mix(in srgb, var(--period-brand) 38%, transparent);
}
.nomina-periodos .period-tab.is-active i { color: var(--wk-gold-ink); }
.nomina-periodos .period-tab.is-active::after {
    content: ""; position: absolute; left: 16px; right: 16px; bottom: 5px; height: 2px; border-radius: 999px;
    background: linear-gradient(90deg, var(--wk-gold), color-mix(in srgb, var(--wk-gold) 40%, #fff));
}
@media (max-width: 768px) {
    .nomina-periodos .period-tabs { width: 100%; flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
    .nomina-periodos .period-tabs::-webkit-scrollbar { display: none; }
    .nomina-periodos .period-tab { flex: 1 0 auto; justify-content: center; }
}
@media (max-width: 640px) {
    .nomina-periodos .period-hero [class*="min-w-"] { min-width: 0 !important; }
}
/* Neutraliza el .grid{min-height:200px} global de performance-optimization.css: inflaba tarjetas y stats con huecos */
.nomina-periodos .grid { min-height: 0 !important; }

/* Acentos en las stats del detalle (Bruto oro, Pendiente ámbar) */
.nomina-periodos .period-stat { position: relative; padding: 13px 14px 13px 18px !important; }
.nomina-periodos .period-stat::before { content: ""; position: absolute; left: 7px; top: 13px; bottom: 13px; width: 3px; border-radius: 999px; background: var(--period-line); }
.nomina-periodos .period-stat:nth-child(1)::before { background: var(--wk-gold-line); }
.nomina-periodos .period-stat:nth-child(4)::before { background: color-mix(in srgb, var(--pd-warning) 55%, #fff); }

/* Tarjeta de periodo compacta (antes: sub-grid 2col label/valor que inflaba la tarjeta y dejaba huecos) */
.nomina-periodos .pc-card { display: flex; flex-direction: column; gap: 9px; padding: 14px 16px !important; }
.nomina-periodos .pc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.nomina-periodos .pc-heading { min-width: 0; }
.nomina-periodos .pc-heading h2 { font-size: 1.02rem !important; line-height: 1.15; }
.nomina-periodos .pc-dates { margin-top: 2px; font-size: .78rem; color: var(--pd-muted); }
.nomina-periodos .pc-badge { flex: none; white-space: nowrap; align-self: flex-start; }
.nomina-periodos .pc-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; font-size: .82rem; color: var(--pd-muted); }
.nomina-periodos .pc-meta strong { color: var(--pd-heading); font-weight: 650; }
.nomina-periodos .pc-sep { width: 3px; height: 3px; border-radius: 999px; background: var(--pd-muted); opacity: .45; }
.nomina-periodos .pc-motivo { font-size: .76rem; color: var(--pd-danger); font-weight: 620; display: flex; align-items: center; gap: 6px; }
.nomina-periodos .pc-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; padding-top: 3px; border-top: 1px solid color-mix(in srgb, var(--period-line) 70%, transparent); margin-top: 2px; }
.nomina-periodos .pc-actions form { margin: 0; }
.nomina-periodos .period-card-active { position: relative; }
.nomina-periodos .period-card-active::before { content: ""; position: absolute; left: 0; top: 14px; bottom: 14px; width: 3px; border-radius: 999px; background: var(--wk-gold); }

/* Filtros: 4 columnas fluidas en escritorio (la fila única de 8 desbordaba el panel) */
@media (min-width: 1280px) {
    .nomina-periodos .period-panel form.grid { grid-template-columns: repeat(4, minmax(0, 1fr)) !important; }
    .nomina-periodos .period-filter-action { justify-self: end; align-items: flex-end; }
    .nomina-periodos .period-filter-action .period-btn { width: auto; min-width: 170px; padding: 0 24px; }
    /* Riel de periodos angosto + detalle que sigue al hacer scroll (llena el vacío de la derecha) */
    .nomina-periodos [class*="xl:grid-cols-3"] { grid-template-columns: 360px minmax(0, 1fr) !important; align-items: start; gap: 16px; }
    .nomina-periodos [class*="xl:col-span-1"], .nomina-periodos [class*="xl:col-span-2"] { grid-column: auto !important; }
    .nomina-periodos [class*="xl:col-span-2"] { position: sticky; top: 16px; }
}
@media (prefers-reduced-motion: reduce) {
    .nomina-periodos .period-btn-primary::after { display: none; }
    .nomina-periodos .period-stat-hero { animation: none !important; }
    .nomina-periodos * { transition-duration: .01ms !important; }
}
</style>

<div class="nomina-periodos">
    <section class="period-hero">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="period-lockup">
                <div class="period-hero-icon"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <div class="period-kicker">Personal del hotel</div>
                    <h1 class="period-title">Pre-n&oacute;mina</h1>
                    <p class="period-subtitle">
                        Revisi&oacute;n interna de periodos laborales con cierre persistente controlado. No genera n&oacute;mina oficial, no registra pago y no modifica Caja.
                    </p>
                </div>
            </div>
            <span class="period-badge">
                <i class="fas fa-lock"></i>
                Preview GET / Cierre controlado
            </span>
        </div>
    </section>

    <section class="p-6 space-y-4">
        <?php $subnav_section = 'personal'; $subnav_active = 'prenomina'; include APP_PATH . '/views/partials/section_subnav.php'; ?>
        <div class="flex flex-wrap items-center gap-3">
            <?php $back_arrow_href = back_url('trabajadores'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="period-btn ms-back-legacy" href="<?= back_url('trabajadores') ?>">
                <i class="fas fa-arrow-left"></i>
                Personal
            </a>
            <nav class="period-tabs" aria-label="Vistas de pre-n&oacute;mina">
                <span class="period-tab is-active" aria-current="page">
                    <i class="fas fa-calendar-check"></i>
                    Periodos
                </span>
                <a class="period-tab" href="<?= url('trabajadores/nomina/preview') ?>">
                    <i class="fas fa-clipboard-list"></i>
                    Calcular pre-n&oacute;mina
                </a>
            </nav>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="period-stat-hero">
                <div class="text-xs opacity-75">Periodos</div>
                <div class="text-2xl font-black"><?= trab_periodo_num(count($periodos)) ?></div>
            </div>
            <div class="period-stat-hero">
                <div class="text-xs opacity-75">Trabajadores</div>
                <div class="text-2xl font-black"><?= trab_periodo_num($resumen['trabajadores_total'] ?? 0) ?></div>
            </div>
            <div class="period-stat-hero is-gold">
                <div class="text-xs opacity-75">Neto sugerido</div>
                <div class="text-2xl font-black"><?= trab_periodo_money($resumen['neto_sugerido_total'] ?? 0) ?></div>
            </div>
            <div class="period-stat-hero is-warn">
                <div class="text-xs opacity-75">Pendiente</div>
                <div class="text-2xl font-black"><?= trab_periodo_money($resumen['pendiente_pago_total'] ?? 0) ?></div>
            </div>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="period-panel p-5">
                <strong>Periodos no disponibles.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas laborales o de Caja para calcular periodos con seguridad.</p>
            </div>
        <?php else: ?>
            <div class="period-panel p-4">
                <form method="GET" action="<?= url('trabajadores/nomina/periodos') ?>" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[150px_150px_150px_150px_150px_minmax(190px,1fr)_130px_120px] gap-3 items-end">
                    <label class="period-filter">
                        <span class="period-filter-label">Tipo</span>
                        <select class="period-input" name="tipo_periodo">
                            <option value="semanal" <?= $tipoPeriodo === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                            <option value="quincenal" <?= $tipoPeriodo === 'quincenal' ? 'selected' : '' ?>>Quincenal</option>
                            <option value="mensual" <?= $tipoPeriodo === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                            <option value="manual" <?= $tipoPeriodo === 'manual' ? 'selected' : '' ?>>Manual</option>
                        </select>
                        <span class="period-filter-hint">Manual usa rango.</span>
                    </label>
                    <label class="period-filter">
                        <span class="period-filter-label">Fecha base</span>
                        <input class="period-input" type="date" name="fecha_base" value="<?= trab_periodo_safe($fechaBase, date('Y-m-d')) ?>">
                        <span class="period-filter-hint">Para calculo auto.</span>
                    </label>
                    <label class="period-filter">
                        <span class="period-filter-label">Inicio manual</span>
                        <input class="period-input" type="date" name="fecha_inicio" value="<?= trab_periodo_safe($fechaInicio, '') ?>">
                        <span class="period-filter-hint">Obligatorio en manual.</span>
                    </label>
                    <label class="period-filter">
                        <span class="period-filter-label">Fin manual</span>
                        <input class="period-input" type="date" name="fecha_fin" value="<?= trab_periodo_safe($fechaFin, '') ?>">
                        <span class="period-filter-hint">Obligatorio en manual.</span>
                    </label>
                    <label class="period-filter">
                        <span class="period-filter-label">Trabajadores</span>
                        <select class="period-input" name="estado">
                            <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                            <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                            <option value="baja" <?= $estado === 'baja' ? 'selected' : '' ?>>Baja</option>
                        </select>
                        <span class="period-filter-hint">Segun estado laboral.</span>
                    </label>
                    <label class="period-filter">
                        <span class="period-filter-label">Rol laboral</span>
                        <input class="period-input" type="search" name="rol_laboral" value="<?= trab_periodo_safe($rolLaboral, '') ?>" placeholder="Ej. lavanderia">
                        <span class="period-filter-hint">Opcional.</span>
                    </label>
                    <div class="period-filter">
                        <span class="period-filter-label">Caja</span>
                        <label class="period-check">
                            <input type="hidden" name="incluir_pagos_caja" value="0">
                            <input type="checkbox" name="incluir_pagos_caja" value="1" <?= $incluirPagosCaja ? 'checked' : '' ?>>
                            Incluir
                        </label>
                        <span class="period-filter-hint">Resta pagos vigentes.</span>
                    </div>
                    <div class="period-filter period-filter-action">
                        <span class="period-filter-label">&nbsp;</span>
                        <button class="period-btn period-btn-primary" type="submit">
                            <i class="fas fa-filter"></i>
                            Evaluar
                        </button>
                        <span class="period-filter-hint">Solo revisa.</span>
                    </div>
                </form>
            </div>

            <?php if (!empty($bloqueos)): ?>
                <div class="period-panel p-5 bg-slate-50">
                    <strong>Revision bloqueada.</strong>
                    <div class="mt-2 space-y-1 text-sm text-slate-600">
                        <?php foreach ($bloqueos as $bloqueo): ?>
                            <div><i class="fas fa-circle-info mr-2"></i><?= trab_periodo_safe($bloqueo) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-1 space-y-3">
                    <?php if (empty($periodos)): ?>
                        <div class="period-card p-6 text-center">
                            <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-calendar-xmark"></i></div>
                            <h2 class="font-black text-lg">Sin periodos para revisar</h2>
                            <p class="text-sm text-slate-500 mt-1">Ajusta las fechas del periodo.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($periodos as $periodo): ?>
                            <?php
                                $isActive = $periodoDetalle
                                    && (string)($periodoDetalle['fecha_inicio'] ?? '') === (string)($periodo['fecha_inicio'] ?? '')
                                    && (string)($periodoDetalle['fecha_fin'] ?? '') === (string)($periodo['fecha_fin'] ?? '');
                                $detalleQuery = http_build_query(array_merge($baseQuery, [
                                    'fecha_inicio' => $periodo['fecha_inicio'] ?? '',
                                    'fecha_fin' => $periodo['fecha_fin'] ?? '',
                                ]));
                                $detalleUrl = url('trabajadores/nomina/periodos/preview' . ($detalleQuery !== '' ? '?' . $detalleQuery : ''));
                                $nominaPreviewUrl = url('trabajadores/nomina/preview' . ($detalleQuery !== '' ? '?' . $detalleQuery : ''));
                                $periodoResumen = is_array($periodo['resumen'] ?? null) ? $periodo['resumen'] : [];
                                $snapshotId = (int)($periodo['snapshot_id'] ?? 0);
                                $snapshotEstado = (string)($periodo['snapshot_estado'] ?? '');
                                $periodoTokenKey = (string)($periodo['fecha_inicio'] ?? '') . ':' . (string)($periodo['fecha_fin'] ?? '');
                                $cierreToken = (string)($cierreTokens[$periodoTokenKey] ?? '');
                            ?>
                            <article class="period-card <?= $isActive ? 'period-card-active' : '' ?> pc-card">
                                <div class="pc-head">
                                    <div class="pc-heading">
                                        <h2 class="font-black"><?= trab_periodo_safe($periodo['etiqueta'] ?? 'Periodo') ?></h2>
                                        <p class="pc-dates"><?= trab_periodo_date($periodo['fecha_inicio'] ?? '') ?> &ndash; <?= trab_periodo_date($periodo['fecha_fin'] ?? '') ?></p>
                                    </div>
                                    <span class="pc-badge <?= trab_periodo_badge_class($periodo['estado_periodo'] ?? '') ?>">
                                        <i class="fas fa-circle"></i>
                                        <?= trab_periodo_safe($periodo['estado_label'] ?? 'Revision') ?>
                                    </span>
                                </div>
                                <div class="pc-meta">
                                    <span><strong><?= trab_periodo_num($periodoResumen['trabajadores_total'] ?? 0) ?></strong> trabajador(es)</span>
                                    <span class="pc-sep"></span>
                                    <span>Pendiente <strong><?= trab_periodo_money($periodoResumen['pendiente_pago_total'] ?? 0) ?></strong></span>
                                </div>
                                <?php if (trim((string)($periodo['motivo_bloqueo'] ?? '')) !== ''): ?>
                                    <p class="pc-motivo"><i class="fas fa-triangle-exclamation"></i> <?= trab_periodo_safe($periodo['motivo_bloqueo'] ?? '') ?></p>
                                <?php endif; ?>
                                <div class="pc-actions">
                                    <a class="period-badge" href="<?= $detalleUrl ?>">
                                        <i class="fas fa-eye"></i>
                                        Detalle
                                    </a>
                                    <a class="period-badge" href="<?= $nominaPreviewUrl ?>">
                                        <i class="fas fa-table-list"></i>
                                        Preview
                                    </a>
                                    <?php if ($snapshotId > 0): ?>
                                        <a class="<?= trab_periodo_snapshot_badge_class($snapshotEstado) ?>" href="<?= url('trabajadores/nomina/periodos/' . $snapshotId) ?>">
                                            <i class="fas fa-box-archive"></i>
                                            <?= trab_periodo_snapshot_label($snapshotEstado) ?> #<?= (int)$snapshotId ?>
                                        </a>
                                    <?php elseif ($tablaPersistenteDisponible && !empty($periodo['puede_cerrar_persistente']) && $cierreToken !== ''): ?>
                                        <form method="POST" action="<?= url('trabajadores/nomina/periodos/cerrar') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="periodo_token" value="<?= trab_periodo_safe($cierreToken, '') ?>">
                                            <input type="hidden" name="tipo_periodo" value="<?= trab_periodo_safe($periodo['tipo_periodo'] ?? $tipoPeriodo, 'manual') ?>">
                                            <input type="hidden" name="etiqueta" value="<?= trab_periodo_safe($periodo['etiqueta'] ?? 'Periodo', '') ?>">
                                            <input type="hidden" name="fecha_inicio" value="<?= trab_periodo_safe($periodo['fecha_inicio'] ?? '', '') ?>">
                                            <input type="hidden" name="fecha_fin" value="<?= trab_periodo_safe($periodo['fecha_fin'] ?? '', '') ?>">
                                            <input type="hidden" name="estado" value="activos">
                                            <input type="hidden" name="rol_laboral" value="">
                                            <input type="hidden" name="incluir_pagos_caja" value="<?= $incluirPagosCaja ? '1' : '0' ?>">
                                            <button class="period-btn period-btn-primary period-btn-compact" type="submit">
                                                <i class="fas fa-lock"></i>
                                                Cerrar snapshot
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="period-badge">
                                            <i class="fas fa-lock"></i>
                                            Sin cierre disponible
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="xl:col-span-2 space-y-4">
                    <div class="period-panel p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="font-black text-lg">Detalle del periodo evaluado</h2>
                                <p class="text-sm text-slate-500 mt-1">
                                    <?= trab_periodo_date($periodoActualInicio) ?> - <?= trab_periodo_date($periodoActualFin) ?>
                                </p>
                            </div>
                            <?php if ($periodoDetalle): ?>
                                <div class="flex flex-wrap gap-2">
                                    <span class="<?= trab_periodo_badge_class($periodoDetalle['estado_periodo'] ?? '') ?>">
                                        <i class="fas fa-shield-halved"></i>
                                        <?= trab_periodo_safe($periodoDetalle['estado_label'] ?? 'Revision') ?>
                                    </span>
                                    <?php if ((int)($periodoDetalle['snapshot_id'] ?? 0) > 0): ?>
                                        <a class="<?= trab_periodo_snapshot_badge_class($periodoDetalle['snapshot_estado'] ?? '') ?>" href="<?= url('trabajadores/nomina/periodos/' . (int)$periodoDetalle['snapshot_id']) ?>">
                                            <i class="fas fa-box-archive"></i>
                                            Snapshot #<?= (int)$periodoDetalle['snapshot_id'] ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                        <div class="period-stat period-stat-soft">
                            <div class="period-label">Bruto</div>
                            <div class="text-2xl font-black mt-1"><?= trab_periodo_money($resumen['bruto_total'] ?? 0) ?></div>
                        </div>
                        <div class="period-stat">
                            <div class="period-label">Deducciones</div>
                            <div class="text-2xl font-black mt-1"><?= trab_periodo_money($resumen['deducciones_total'] ?? 0) ?></div>
                        </div>
                        <div class="period-stat">
                            <div class="period-label">Pagos Caja</div>
                            <div class="text-2xl font-black mt-1"><?= trab_periodo_money($resumen['pagos_caja_aplicados_total'] ?? 0) ?></div>
                        </div>
                        <div class="period-stat period-stat-soft">
                            <div class="period-label">Pendiente</div>
                            <div class="text-2xl font-black mt-1"><?= trab_periodo_money($resumen['pendiente_pago_total'] ?? 0) ?></div>
                        </div>
                    </div>

                    <div class="period-panel overflow-hidden">
                        <div class="p-5 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="font-black text-lg">Trabajadores del periodo</h2>
                                <p class="text-sm text-slate-500 mt-1">El cierre guarda snapshot administrativo; no crea recibos oficiales, pagos ni movimientos de Caja.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a class="period-badge" href="<?= url('trabajadores/nomina/preview' . ($previewNominaQuery !== '' ? '?' . $previewNominaQuery : '')) ?>">
                                    <i class="fas fa-table-list"></i>
                                    Preview completo
                                </a>
                                <span class="period-badge">
                                    <i class="fas fa-users"></i>
                                    <?= trab_periodo_num(count($trabajadores)) ?> visible(s)
                                </span>
                            </div>
                        </div>

                        <?php if (empty($trabajadores)): ?>
                            <div class="p-8 text-center">
                                <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-user-clock"></i></div>
                                <h3 class="font-black text-lg">Sin trabajadores en el detalle</h3>
                                <p class="text-sm text-slate-500 mt-1">Revisa el periodo, estado o rol laboral.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="period-table min-w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-left">Trabajador</th>
                                            <th class="text-left">Estado</th>
                                            <th class="text-right">Bruto</th>
                                            <th class="text-right">Deducciones</th>
                                            <th class="text-right">Caja</th>
                                            <th class="text-right">Neto</th>
                                            <th class="text-left">Lectura</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($trabajadores, 0, 80) as $trabajador): ?>
                                            <?php
                                                $estadoPreview = (string)($trabajador['estado_preview_nomina'] ?? '');
                                                $reciboQuery = http_build_query([
                                                    'fecha_inicio' => $periodoActualInicio,
                                                    'fecha_fin' => $periodoActualFin,
                                                    'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
                                                ]);
                                                $reciboUrl = url('trabajadores/' . (int)($trabajador['id'] ?? 0) . '/recibo-laboral' . ($reciboQuery !== '' ? '?' . $reciboQuery : ''));
                                            ?>
                                            <tr>
                                                <td>
                                                    <a class="font-black underline" href="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0)) ?>">
                                                        <?= trab_periodo_safe($trabajador['nombre_completo'] ?? null) ?>
                                                    </a>
                                                    <div class="text-xs text-slate-500"><?= trab_periodo_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?></div>
                                                </td>
                                                <td>
                                                    <span class="period-badge">
                                                        <i class="fas fa-circle"></i>
                                                        <?= trab_periodo_safe(str_replace('_', ' ', $estadoPreview)) ?>
                                                    </span>
                                                    <div class="text-xs text-slate-500 mt-2"><?= trab_periodo_safe($trabajador['estado'] ?? null) ?></div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="font-black"><?= trab_periodo_money($trabajador['bruto_periodo'] ?? 0) ?></div>
                                                    <div class="text-xs text-slate-500">
                                                        +<?= trab_periodo_money($trabajador['conceptos_a_favor'] ?? 0) ?> / -<?= trab_periodo_money($trabajador['conceptos_en_contra'] ?? 0) ?>
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="font-black"><?= trab_periodo_money($trabajador['deducciones_informativas'] ?? 0) ?></div>
                                                    <div class="text-xs text-slate-500">
                                                        Ant. <?= trab_periodo_money($trabajador['anticipos_saldo'] ?? 0) ?> / Prest. <?= trab_periodo_money($trabajador['prestamos_saldo'] ?? 0) ?>
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="font-black"><?= trab_periodo_money($trabajador['pagos_caja_aplicados'] ?? 0) ?></div>
                                                    <div class="text-xs text-slate-500">
                                                        <?= trab_periodo_num($trabajador['pagos_caja_pagados'] ?? 0) ?> vig., <?= trab_periodo_num($trabajador['pagos_caja_revertidos'] ?? 0) ?> rev.
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="font-black"><?= trab_periodo_money($trabajador['neto_sugerido'] ?? 0) ?></div>
                                                    <div class="text-xs text-slate-500">Pendiente <?= trab_periodo_money($trabajador['pendiente_pago_sugerido'] ?? 0) ?></div>
                                                </td>
                                                <td>
                                                    <?php if (trim((string)($trabajador['motivo_bloqueo_nomina'] ?? '')) !== ''): ?>
                                                        <div class="text-xs text-red-700 font-bold"><?= trab_periodo_safe($trabajador['motivo_bloqueo_nomina'] ?? '') ?></div>
                                                    <?php else: ?>
                                                        <div class="text-xs text-slate-500">Conceptos <?= trab_periodo_num($trabajador['conceptos_count'] ?? 0) ?>.</div>
                                                    <?php endif; ?>
                                                    <a class="period-badge mt-2" href="<?= $reciboUrl ?>">
                                                        <i class="fas fa-receipt"></i>
                                                        Recibo
                                                    </a>
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

            <?php if (!$tablaPersistenteDisponible): ?>
                <div class="period-panel p-5 bg-slate-50">
                    <strong>Cierre persistente no disponible.</strong>
                    <p class="text-sm text-slate-500 mt-1">Faltan las tablas de snapshots de pre-nomina. El preview queda disponible sin cierre.</p>
                </div>
            <?php elseif (!empty($periodosPersistentes)): ?>
                <div class="period-panel overflow-hidden">
                    <div class="p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-black text-lg">Cierres recientes</h2>
                            <p class="text-sm text-slate-500 mt-1">Snapshots administrativos; no son pago, CFDI, timbrado ni movimiento de Caja.</p>
                        </div>
                        <span class="period-badge">
                            <i class="fas fa-box-archive"></i>
                            <?= trab_periodo_num(count($periodosPersistentes)) ?> visible(s)
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="period-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Periodo</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Trabajadores</th>
                                    <th class="text-right">Pendiente</th>
                                    <th class="text-left">Cierre</th>
                                    <th class="text-right">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($periodosPersistentes as $snapshot): ?>
                                    <tr>
                                        <td>
                                            <div class="font-black"><?= trab_periodo_safe($snapshot['etiqueta'] ?? 'Periodo cerrado') ?></div>
                                            <div class="text-xs text-slate-500">
                                                <?= trab_periodo_date($snapshot['fecha_inicio'] ?? '') ?> - <?= trab_periodo_date($snapshot['fecha_fin'] ?? '') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="<?= trab_periodo_snapshot_badge_class($snapshot['estado'] ?? '') ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= trab_periodo_snapshot_label($snapshot['estado'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td class="text-right font-black"><?= trab_periodo_num($snapshot['trabajadores_total'] ?? 0) ?></td>
                                        <td class="text-right font-black"><?= trab_periodo_money($snapshot['pendiente_pago_total'] ?? 0) ?></td>
                                        <td>
                                            <div class="text-sm font-bold"><?= trab_periodo_safe($snapshot['cerrado_por_nombre'] ?? 'Usuario') ?></div>
                                            <div class="text-xs text-slate-500"><?= trab_periodo_safe($snapshot['cerrado_at'] ?? '-') ?></div>
                                        </td>
                                        <td class="text-right">
                                            <a class="period-badge" href="<?= url('trabajadores/nomina/periodos/' . (int)($snapshot['id'] ?? 0)) ?>">
                                                <i class="fas fa-eye"></i>
                                                Ver snapshot
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
