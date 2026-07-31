<?php
$reporte = $reporte ?? [];
$tablaDisponible = $tablaDisponible ?? false;
$trabajadores = $reporte['trabajadores'] ?? [];
$ledger = $reporte['ledger'] ?? [];
$asistencias = $reporte['asistencias'] ?? [];
$documentos = $reporte['documentos'] ?? [];
$tareas = $reporte['tareas'] ?? [];
$trabajadoresRelevantes = $reporte['trabajadores_relevantes'] ?? [];

// Con la nomina fuera de Personal este reporte deja de hablar de dinero: se van
// los saldos, los anticipos, los prestamos y la asistencia (todo eso se reporta
// desde Nomina). Quedan las dimensiones que SI son de Personal: cuanta gente hay,
// su estado, sus documentos y sus tareas.
$repNominaVisible = !function_exists('personal_nomina_legacy_visible')
    || personal_nomina_legacy_visible();

if (!function_exists('trab_report_safe')) {
    function trab_report_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_report_money')) {
    function trab_report_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_report_num')) {
    function trab_report_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_report_estado_meta')) {
    function trab_report_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'activo'   => ['Activo', 'is-activo'],
            'inactivo' => ['Inactivo', 'is-inactivo'],
            'baja'     => ['Baja', 'is-baja'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft'];
    }
}
?>

<style>
.workers-report {
    --wk-brand: var(--brand-primary, #1B2746);
    --wk-brand-2: var(--brand-secondary, #0F172A);
    --wk-gold: var(--brand-accent, #BD9441);
    --wk-gold-soft: color-mix(in srgb, var(--wk-gold) 15%, #FFFFFF);
    --wk-gold-line: color-mix(in srgb, var(--wk-gold) 42%, #E4D4B0);
    --wk-gold-ink: color-mix(in srgb, var(--wk-gold) 72%, #000);
    --wk-ivory: #F5F5F7; --wk-ivory-2: #FAFAFC;
    --wk-surface: #FFFFFF; --wk-surface-warm: #F5F5F7;
    --wk-border: color-mix(in srgb, var(--wk-brand) 7%, #E7E1D4);
    --wk-text: #171717; --wk-muted: #667085; --wk-heading: #111827;
    --wk-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --wk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --wk-success: #1E9E63; --wk-success-bg: #E7F4EC;
    --wk-warning: #C2841C; --wk-warning-bg: #FAF0DC;
    --wk-danger: #B4392B; --wk-danger-bg: #F8EAE5;
    min-height: 100%; color: var(--wk-text); font-family: var(--wk-sans);
    
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.workers-report .wk-shell { display: grid; gap: 14px; }
.workers-report .wk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.workers-report .wk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--wk-gold), var(--wk-brand) 54%, color-mix(in srgb, var(--wk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--wk-brand) 72%, transparent); }
.workers-report .wk-kicker { margin: 0 0 2px; color: var(--wk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.workers-report .wk-title { margin: 0; font-family: var(--wk-serif); color: var(--wk-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.workers-report .wk-subtitle { max-width: 50rem; margin: 8px 0 0; color: var(--wk-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.workers-report .wk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid var(--wk-border); background: var(--wk-surface); color: var(--wk-muted); font-weight: 700; font-size: .85rem; text-decoration: none;
    transition: transform .16s ease, border-color .16s ease, color .16s ease; }
.workers-report .wk-btn:hover { transform: translateY(-1px); border-color: var(--wk-gold-line); color: var(--wk-gold-ink); }
.workers-report .wk-contract-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; }
.workers-report .wk-contract-pill { display: inline-flex; align-items: center; gap: .45rem; min-height: 34px; padding: 0 12px;
    border-radius: 999px; border: 1px solid var(--wk-border); background: var(--wk-surface-warm); color: var(--wk-muted); font-size: .75rem; font-weight: 800; }
.workers-report .wk-contract-pill i { color: var(--wk-gold-ink); }

.workers-report .wk-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
/* Sin la nomina quedan menos fichas y menos paneles: sin esto la rejilla fija
   deja huecos a la derecha (4 columnas con 3 fichas, 3 columnas con 1 panel). */
.workers-report .wk-stats.is-lean { grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
.workers-report .wk-grid3.is-lean { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
.workers-report .wk-stat { background: var(--wk-surface); border: 1px solid var(--wk-border); border-radius: 14px; padding: 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.workers-report .wk-stat.is-warm { background: var(--wk-surface-warm); }
.workers-report .wk-stat-label { color: var(--wk-muted); font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.workers-report .wk-stat-value { margin: 4px 0 3px; font-family: var(--wk-serif); font-size: 1.7rem; font-weight: 700; line-height: 1; color: var(--wk-heading); }
.workers-report .wk-stat-foot { color: var(--wk-muted); font-size: .72rem; }

.workers-report .wk-grid3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.workers-report .wk-panel { background: var(--wk-surface); border: 1px solid var(--wk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.workers-report .wk-panel-pad { padding: 18px; }
.workers-report .wk-panel h2 { font-family: var(--wk-serif); font-size: 1.35rem; font-weight: 700; color: var(--wk-heading); margin-bottom: 12px; }
.workers-report .wk-kv { display: flex; justify-content: space-between; gap: 14px; padding: 8px 0; border-bottom: 1px solid var(--wk-border); font-size: .88rem; }
.workers-report .wk-kv:last-child { border-bottom: 0; }
.workers-report .wk-kv span { color: var(--wk-muted); font-weight: 600; }
.workers-report .wk-kv strong { color: var(--wk-heading); font-weight: 700; }
.workers-report .wk-chips { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.workers-report .wk-chip { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 7px 11px; border-radius: 999px; background: var(--wk-surface-warm); border: 1px solid var(--wk-border); font-size: .76rem; }
.workers-report .wk-chip span { color: var(--wk-muted); font-weight: 600; text-transform: capitalize; }
.workers-report .wk-chip strong { color: var(--wk-heading); font-weight: 700; }

.workers-report .wk-panel-head { padding: 16px 18px; border-bottom: 1px solid var(--wk-border); }
.workers-report .wk-panel-title { font-family: var(--wk-serif); font-size: 1.4rem; font-weight: 700; color: var(--wk-heading); }
.workers-report .wk-panel-sub { font-size: .8rem; color: var(--wk-muted); margin-top: 3px; }
.workers-report .wk-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.workers-report .wk-table thead { background: var(--wk-surface-warm); border-bottom: 1px solid var(--wk-border); }
.workers-report .wk-table th { padding: 12px 14px; color: var(--wk-muted); font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.workers-report .wk-table th.is-end, .workers-report .wk-table td.is-end { text-align: right; }
.workers-report .wk-table td { padding: 13px 14px; border-bottom: 1px solid var(--wk-border); vertical-align: middle; }
.workers-report .wk-table tbody tr:last-child td { border-bottom: 0; }
.workers-report .wk-table tbody tr:hover { background: var(--wk-ivory-2); }
.workers-report .wk-strong { font-weight: 700; color: var(--wk-heading); }
.workers-report .wk-sub { color: var(--wk-muted); font-size: .72rem; }
.workers-report .wk-act { display: inline-flex; align-items: center; gap: .35rem; min-height: 32px; padding: 0 12px; border-radius: 9px; background: var(--wk-surface-warm); border: 1px solid var(--wk-border); color: #2F77E0; font-size: .76rem; font-weight: 700; text-decoration: none; }
.workers-report .wk-act:hover { background: #E6EFFC; }

.workers-report .wk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .73rem; font-weight: 700; border: 1px solid transparent; }
.workers-report .wk-badge.is-activo { color: color-mix(in srgb, var(--wk-success) 78%, #000); background: var(--wk-success-bg); border-color: color-mix(in srgb, var(--wk-success) 26%, #fff); }
.workers-report .wk-badge.is-inactivo { color: color-mix(in srgb, var(--wk-warning) 82%, #000); background: var(--wk-warning-bg); border-color: color-mix(in srgb, var(--wk-warning) 28%, #fff); }
.workers-report .wk-badge.is-baja { color: color-mix(in srgb, var(--wk-danger) 82%, #000); background: var(--wk-danger-bg); border-color: color-mix(in srgb, var(--wk-danger) 26%, #fff); }
.workers-report .wk-badge.is-soft { color: var(--wk-muted); background: var(--wk-surface-warm); border-color: var(--wk-border); }

.workers-report .wk-empty { text-align: center; padding: 40px 18px; }
.workers-report .wk-empty-icon { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 18px; display: grid; place-items: center; background: var(--wk-gold-soft); color: var(--wk-gold-ink); font-size: 1.25rem; }
.workers-report .wk-empty h3 { color: var(--wk-brand); font-size: 1.05rem; font-weight: 700; }
.workers-report .wk-empty p { color: var(--wk-muted); font-size: .88rem; margin-top: 6px; }
.workers-report .wk-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--wk-gold-soft); border: 1px solid var(--wk-gold-line); border-radius: 16px; }
.workers-report .wk-notice i { color: var(--wk-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.workers-report .wk-notice strong { color: var(--wk-heading); display: block; margin-bottom: 2px; }
.workers-report .wk-notice p { color: var(--wk-muted); font-size: .88rem; margin: 0; }

@media (max-width: 980px) { .workers-report .wk-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .workers-report .wk-grid3 { grid-template-columns: 1fr; } }
@media (max-width: 720px) { .workers-report .wk-contract-row { justify-content: flex-start; } }
</style>

<div class="workers-report p-4 sm:p-6">
    <div class="wk-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="wk-title-lockup">
                <div class="wk-hero-icon"><i class="fas fa-chart-pie"></i></div>
                <div>
                    <p class="wk-kicker">Personal del hotel</p>
                    <h1 class="wk-title">Reporte de Personal</h1>
                    <p class="wk-subtitle"><?= $repNominaVisible
                        ? 'Resumen de tu equipo: saldos laborales, asistencia, documentos y tareas. Es solo para consultar. No genera nomina oficial ni pagos.'
                        : 'Resumen de tu equipo: cu&aacute;nta gente hay, en qu&eacute; estado, sus documentos y sus tareas. Es solo para consultar.' ?></p>
                </div>
            </div>
            <div class="wk-contract-row">
                <span class="wk-contract-pill"><i class="fas fa-lock"></i> read-only</span>
                <?php if ($repNominaVisible): ?>
                <span class="wk-contract-pill"><i class="fas fa-file-circle-xmark"></i> No genera nomina</span>
                <?php endif; ?>
                <?php $back_arrow_href = back_url('trabajadores'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a class="wk-btn ms-back-legacy" href="<?= back_url('trabajadores') ?>"><i class="fas fa-arrow-left"></i> Volver a personal</a>
            </div>
        </section>

        <?php $subnav_section = 'personal'; $subnav_active = 'informes'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

        <?php if (!$tablaDisponible): ?>
            <section class="wk-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para ver el reporte de personal.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="wk-stats<?= $repNominaVisible ? '' : ' is-lean' ?>">
                <div class="wk-stat">
                    <p class="wk-stat-label">Trabajadores</p>
                    <p class="wk-stat-value"><?= trab_report_num($trabajadores['total'] ?? 0) ?></p>
                    <p class="wk-stat-foot">Activos: <?= trab_report_num($trabajadores['activos'] ?? 0) ?></p>
                </div>
                <?php if ($repNominaVisible): ?>
                <div class="wk-stat is-warm">
                    <p class="wk-stat-label">Saldo laboral (informativo)</p>
                    <p class="wk-stat-value"><?= trab_report_money($ledger['saldo_informativo'] ?? 0) ?></p>
                    <p class="wk-stat-foot">No es un pago real ni toca Caja</p>
                </div>
                <div class="wk-stat">
                    <p class="wk-stat-label">Asistencias</p>
                    <p class="wk-stat-value"><?= trab_report_num($asistencias['total'] ?? 0) ?></p>
                    <p class="wk-stat-foot">&Uacute;ltima: <?= trab_report_safe($asistencias['ultima_fecha'] ?? null, 'Sin registros') ?></p>
                </div>
                <?php else: ?>
                <div class="wk-stat">
                    <p class="wk-stat-label">Tareas asignadas</p>
                    <p class="wk-stat-value"><?= trab_report_num($tareas['total'] ?? 0) ?></p>
                    <p class="wk-stat-foot">Pendientes: <?= trab_report_num($tareas['pendiente'] ?? 0) ?></p>
                </div>
                <?php endif; ?>
                <div class="wk-stat">
                    <p class="wk-stat-label">Documentos</p>
                    <p class="wk-stat-value"><?= trab_report_num($documentos['total'] ?? 0) ?></p>
                    <p class="wk-stat-foot">Con documentos: <?= trab_report_num($documentos['trabajadores_con_documentos'] ?? 0) ?></p>
                </div>
            </section>

            <div class="wk-grid3<?= $repNominaVisible ? '' : ' is-lean' ?>">
                <?php if ($repNominaVisible): ?>
                <div class="wk-panel wk-panel-pad">
                    <h2>Saldos laborales</h2>
                    <div class="wk-kv"><span>Conceptos</span><strong><?= trab_report_num($ledger['conceptos_count'] ?? 0) ?></strong></div>
                    <div class="wk-kv"><span>A favor</span><strong style="color:var(--wk-success)"><?= trab_report_money($ledger['conceptos_a_favor'] ?? 0) ?></strong></div>
                    <div class="wk-kv"><span>En contra</span><strong style="color:var(--wk-danger)"><?= trab_report_money($ledger['conceptos_en_contra'] ?? 0) ?></strong></div>
                    <div class="wk-kv"><span>Anticipos pendientes</span><strong><?= trab_report_money($ledger['anticipos_saldo'] ?? 0) ?></strong></div>
                    <div class="wk-kv"><span>Pr&eacute;stamos vigentes</span><strong><?= trab_report_money($ledger['prestamos_saldo'] ?? 0) ?></strong></div>
                </div>

                <div class="wk-panel wk-panel-pad">
                    <h2>Asistencia</h2>
                    <div class="wk-chips">
                        <?php foreach (['asistencia', 'falta', 'retardo', 'permiso', 'incapacidad', 'descanso', 'horas_extra'] as $tipo): ?>
                            <div class="wk-chip">
                                <span><?= trab_report_safe(str_replace('_', ' ', $tipo)) ?></span>
                                <strong><?= trab_report_num($asistencias[$tipo] ?? 0) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="wk-panel wk-panel-pad">
                    <h2>Tareas asignadas</h2>
                    <div class="wk-kv"><span>Total</span><strong><?= trab_report_num($tareas['total'] ?? 0) ?></strong></div>
                    <div class="wk-kv"><span>Pendientes</span><strong><?= trab_report_num($tareas['pendiente'] ?? 0) ?></strong></div>
                    <div class="wk-kv"><span>Asignadas</span><strong><?= trab_report_num($tareas['asignada'] ?? 0) ?></strong></div>
                    <div class="wk-kv"><span>En proceso</span><strong><?= trab_report_num($tareas['en_proceso'] ?? 0) ?></strong></div>
                    <div class="wk-kv"><span>Completadas</span><strong style="color:var(--wk-success)"><?= trab_report_num($tareas['completada'] ?? 0) ?></strong></div>
                </div>
            </div>

            <div class="wk-panel overflow-hidden">
                <div class="wk-panel-head">
                    <h2 class="wk-panel-title"><?= $repNominaVisible ? 'Trabajadores y saldos' : 'Trabajadores' ?></h2>
                    <p class="wk-panel-sub"><?= $repNominaVisible
                        ? 'Saldos informativos por persona. No son movimientos de Caja.'
                        : 'Qui&eacute;n est&aacute; dado de alta y en qu&eacute; puesto.' ?></p>
                </div>
                <?php if (empty($trabajadoresRelevantes)): ?>
                    <div class="wk-empty">
                        <div class="wk-empty-icon"><i class="fas fa-chart-pie"></i></div>
                        <h3>Sin trabajadores para reportar</h3>
                        <p><?= $repNominaVisible
                            ? 'Cuando registres personal, aqu&iacute; ver&aacute;s su resumen laboral.'
                            : 'Cuando registres personal, aqu&iacute; ver&aacute;s a tu equipo.' ?></p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="wk-table">
                            <thead>
                                <tr>
                                    <th>Trabajador</th><th>Estado</th>
                                    <?php if ($repNominaVisible): ?>
                                    <th class="is-end">A favor</th><th class="is-end">En contra</th>
                                    <th class="is-end">Anticipos</th><th class="is-end">Pr&eacute;stamos</th>
                                    <th class="is-end">Saldo</th>
                                    <?php endif; ?>
                                    <th class="is-end">Acci&oacute;n</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trabajadoresRelevantes as $trabajador): ?>
                                    <?php
                                    $resumen = $trabajador['resumen_laboral'] ?? [];
                                    [$eLabel, $eClass] = trab_report_estado_meta($trabajador['estado'] ?? null);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="wk-strong"><?= trab_report_safe($trabajador['nombre_completo'] ?? null) ?></div>
                                            <div class="wk-sub"><?= trab_report_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?></div>
                                        </td>
                                        <td><span class="wk-badge <?= $eClass ?>"><?= $eLabel ?></span></td>
                                        <?php if ($repNominaVisible): ?>
                                        <td class="is-end"><?= trab_report_money($resumen['conceptos_a_favor'] ?? 0) ?></td>
                                        <td class="is-end"><?= trab_report_money($resumen['conceptos_en_contra'] ?? 0) ?></td>
                                        <td class="is-end"><?= trab_report_money($resumen['anticipos_saldo'] ?? 0) ?></td>
                                        <td class="is-end"><?= trab_report_money($resumen['prestamos_saldo'] ?? 0) ?></td>
                                        <td class="is-end wk-strong"><?= trab_report_money($resumen['saldo_informativo'] ?? 0) ?></td>
                                        <?php endif; ?>
                                        <td class="is-end"><a class="wk-act" href="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0)) ?>"><i class="fas fa-eye"></i> Ver</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
