<?php
$promedioGeneral = $promedioGeneral ?? [];
$porTipoHabitacion = $porTipoHabitacion ?? [];
$porProcedencia = $porProcedencia ?? [];
$distribucion = $distribucion ?? [];
$tendenciaMensual = $tendenciaMensual ?? [];
$fecha_inicio = $fecha_inicio ?? date('Y-m-01');
$fecha_fin = $fecha_fin ?? date('Y-m-d');

if (!function_exists('stay_safe')) {
    function stay_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}
?>

<style>
.stay-report-view { --stay-primary: var(--brand-primary, #1B2746); --stay-accent: var(--brand-accent, #BD9441); --stay-line: #E5E7EB; --stay-muted: #667085; color: #172033; }
.stay-shell { max-width: 1180px; margin: 0 auto; padding: 24px clamp(14px, 3vw, 28px) 40px; }
.stay-hero { display: flex; justify-content: space-between; gap: 18px; align-items: flex-end; border-bottom: 1px solid var(--stay-line); padding-bottom: 18px; }
.stay-kicker { color: var(--stay-accent); font-size: .78rem; font-weight: 800; text-transform: uppercase; }
.stay-hero h1 { margin: 5px 0 0; font-size: clamp(1.65rem, 2.5vw, 2.35rem); letter-spacing: 0; }
.stay-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
.stay-actions input { min-height: 42px; border: 1px solid var(--stay-line); border-radius: 8px; padding: 0 10px; }
.stay-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 22px 0; }
.stay-metric { background: #fff; border: 1px solid var(--stay-line); border-radius: 8px; padding: 16px; }
.stay-metric span { display: block; color: var(--stay-muted); font-size: .78rem; font-weight: 800; text-transform: uppercase; }
.stay-metric strong { display: block; margin-top: 6px; font-size: 1.5rem; color: var(--stay-primary); }
.stay-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.stay-panel { background: #fff; border: 1px solid var(--stay-line); border-radius: 8px; overflow: hidden; }
.stay-panel h2 { margin: 0; padding: 14px 16px; font-size: 1rem; border-bottom: 1px solid var(--stay-line); }
.stay-table-wrap { overflow-x: auto; }
.stay-table { width: 100%; border-collapse: collapse; min-width: 480px; }
.stay-table th, .stay-table td { padding: 11px 14px; border-bottom: 1px solid var(--stay-line); text-align: left; font-size: .9rem; }
.stay-table th { background: #F8FAFC; color: var(--stay-muted); font-size: .76rem; text-transform: uppercase; }
.stay-table td.num, .stay-table th.num { text-align: right; }
.stay-empty { padding: 18px; color: var(--stay-muted); }
@media (max-width: 860px) { .stay-hero { display: block; } .stay-actions { justify-content: stretch; margin-top: 16px; } .stay-actions input, .stay-actions button { width: 100%; } .stay-grid, .stay-panels { grid-template-columns: 1fr; } }
</style>

<div class="stay-report-view">
    <main class="stay-shell">
        <section class="stay-hero">
            <div>
                <span class="stay-kicker">Reportes</span>
                <h1>Promedio de estancia</h1>
            </div>
            <form class="stay-actions" method="get" action="<?= url('reportes/estancia') ?>" data-auto-filter-form>
                <input type="date" name="fecha_inicio" value="<?= stay_safe($fecha_inicio) ?>">
                <input type="date" name="fecha_fin" value="<?= stay_safe($fecha_fin) ?>">
            </form>
        </section>

        <section class="stay-grid">
            <article class="stay-metric"><span>Promedio</span><strong><?= number_format((float)($promedioGeneral['promedio_dias'] ?? 0), 1) ?> dias</strong></article>
            <article class="stay-metric"><span>Minima</span><strong><?= number_format((float)($promedioGeneral['estancia_minima'] ?? 0), 0) ?></strong></article>
            <article class="stay-metric"><span>Maxima</span><strong><?= number_format((float)($promedioGeneral['estancia_maxima'] ?? 0), 0) ?></strong></article>
            <article class="stay-metric"><span>Reservaciones</span><strong><?= number_format((float)($promedioGeneral['total_reservaciones'] ?? 0), 0) ?></strong></article>
        </section>

        <section class="stay-panels">
            <article class="stay-panel">
                <h2>Por tipo de habitacion</h2>
                <?php if (empty($porTipoHabitacion)): ?>
                    <div class="stay-empty">Sin datos para el periodo.</div>
                <?php else: ?>
                    <div class="stay-table-wrap"><table class="stay-table">
                        <thead><tr><th>Tipo</th><th class="num">Promedio</th><th class="num">Reservaciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($porTipoHabitacion as $row): ?>
                            <tr><td><?= stay_safe($row['tipo'] ?? '-') ?></td><td class="num"><?= number_format((float)($row['promedio_dias'] ?? 0), 1) ?></td><td class="num"><?= number_format((float)($row['total_reservaciones'] ?? 0), 0) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </article>

            <article class="stay-panel">
                <h2>Por procedencia</h2>
                <?php if (empty($porProcedencia)): ?>
                    <div class="stay-empty">Sin datos suficientes por procedencia.</div>
                <?php else: ?>
                    <div class="stay-table-wrap"><table class="stay-table">
                        <thead><tr><th>Estado</th><th class="num">Promedio</th><th class="num">Reservaciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($porProcedencia as $row): ?>
                            <tr><td><?= stay_safe($row['estado'] ?? 'Sin estado') ?></td><td class="num"><?= number_format((float)($row['promedio_dias'] ?? 0), 1) ?></td><td class="num"><?= number_format((float)($row['total_reservaciones'] ?? 0), 0) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </article>

            <article class="stay-panel">
                <h2>Distribucion</h2>
                <?php if (empty($distribucion)): ?>
                    <div class="stay-empty">Sin distribucion disponible.</div>
                <?php else: ?>
                    <div class="stay-table-wrap"><table class="stay-table">
                        <thead><tr><th>Dias</th><th class="num">Cantidad</th></tr></thead>
                        <tbody>
                        <?php foreach ($distribucion as $row): ?>
                            <tr><td><?= number_format((float)($row['dias_estancia'] ?? 0), 0) ?></td><td class="num"><?= number_format((float)($row['cantidad'] ?? 0), 0) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </article>

            <article class="stay-panel">
                <h2>Tendencia mensual</h2>
                <?php if (empty($tendenciaMensual)): ?>
                    <div class="stay-empty">Sin tendencia disponible.</div>
                <?php else: ?>
                    <div class="stay-table-wrap"><table class="stay-table">
                        <thead><tr><th>Mes</th><th class="num">Promedio</th><th class="num">Reservaciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($tendenciaMensual as $row): ?>
                            <tr><td><?= stay_safe($row['mes'] ?? '-') ?></td><td class="num"><?= number_format((float)($row['promedio_dias'] ?? 0), 1) ?></td><td class="num"><?= number_format((float)($row['total_reservaciones'] ?? 0), 0) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </article>
        </section>
    </main>
</div>
