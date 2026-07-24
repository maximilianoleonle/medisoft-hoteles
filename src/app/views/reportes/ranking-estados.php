<?php
$ranking = $ranking ?? [];
$comparativa = $comparativa ?? [];
$fecha_inicio = $fecha_inicio ?? date('Y-m-01');
$fecha_fin = $fecha_fin ?? date('Y-m-d');

if (!function_exists('rank_safe')) {
    function rank_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}
?>

<style>
.rank-report-view { --rank-primary: var(--brand-primary, #1B2746); --rank-accent: var(--brand-accent, #BD9441); --rank-line: #E5E7EB; --rank-muted: #667085; color: #172033; }
.rank-shell { max-width: 1180px; margin: 0 auto; padding: 24px clamp(14px, 3vw, 28px) 40px; }
.rank-hero { display: flex; justify-content: space-between; gap: 18px; align-items: flex-end; border-bottom: 1px solid var(--rank-line); padding-bottom: 18px; }
.rank-kicker { color: var(--rank-accent); font-size: .78rem; font-weight: 800; text-transform: uppercase; }
.rank-hero h1 { margin: 5px 0 0; font-size: clamp(1.65rem, 2.5vw, 2.35rem); letter-spacing: 0; }
.rank-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
.rank-actions input { min-height: 42px; border: 1px solid var(--rank-line); border-radius: 8px; padding: 0 10px; }
.rank-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 22px 0; }
.rank-metric { background: #fff; border: 1px solid var(--rank-line); border-radius: 8px; padding: 16px; }
.rank-metric span { display: block; color: var(--rank-muted); font-size: .78rem; font-weight: 800; text-transform: uppercase; }
.rank-metric strong { display: block; margin-top: 6px; font-size: 1.5rem; color: var(--rank-primary); }
.rank-panel { background: #fff; border: 1px solid var(--rank-line); border-radius: 8px; overflow: hidden; margin-top: 16px; }
.rank-panel h2 { margin: 0; padding: 14px 16px; font-size: 1rem; border-bottom: 1px solid var(--rank-line); }
.rank-table-wrap { overflow-x: auto; }
.rank-table { width: 100%; border-collapse: collapse; min-width: 780px; }
.rank-table th, .rank-table td { padding: 11px 14px; border-bottom: 1px solid var(--rank-line); text-align: left; font-size: .9rem; }
.rank-table th { background: #F8FAFC; color: var(--rank-muted); font-size: .76rem; text-transform: uppercase; }
.rank-table td.num, .rank-table th.num { text-align: right; }
.rank-empty { padding: 18px; color: var(--rank-muted); }
@media (max-width: 760px) { .rank-hero { display: block; } .rank-actions { justify-content: stretch; margin-top: 16px; } .rank-actions input, .rank-actions button { width: 100%; } .rank-grid { grid-template-columns: 1fr; } }
</style>

<div class="rank-report-view">
    <main class="rank-shell">
        <section class="rank-hero">
            <div>
                <span class="rank-kicker">Reportes</span>
                <h1>Ranking de estados</h1>
            </div>
            <form class="rank-actions" method="get" action="<?= url('reportes/ranking-estados') ?>" data-auto-filter-form>
                <input type="date" name="fecha_inicio" value="<?= rank_safe($fecha_inicio) ?>">
                <input type="date" name="fecha_fin" value="<?= rank_safe($fecha_fin) ?>">
            </form>
        </section>

        <?php
        $totalReservaciones = array_sum(array_map(function($row) { return (int)($row['total_reservaciones'] ?? 0); }, $ranking));
        $totalIngresos = array_sum(array_map(function($row) { return (float)($row['ingresos_totales'] ?? 0); }, $ranking));
        ?>
        <section class="rank-grid">
            <article class="rank-metric"><span>Estados</span><strong><?= count($ranking) ?></strong></article>
            <article class="rank-metric"><span>Reservaciones</span><strong><?= number_format($totalReservaciones, 0) ?></strong></article>
            <article class="rank-metric"><span>Ingresos</span><strong>$<?= number_format($totalIngresos, 0) ?></strong></article>
        </section>

        <section class="rank-panel">
            <h2>Ranking</h2>
            <?php if (empty($ranking)): ?>
                <div class="rank-empty">Sin datos para el periodo seleccionado.</div>
            <?php else: ?>
                <div class="rank-table-wrap">
                    <table class="rank-table">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th class="num">Huespedes</th>
                                <th class="num">Reservaciones</th>
                                <th class="num">Ingresos</th>
                                <th class="num">Ticket promedio</th>
                                <th class="num">Estancia prom.</th>
                                <th class="num">% total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ranking as $row): ?>
                            <tr>
                                <td><?= rank_safe($row['estado'] ?? 'Sin estado') ?></td>
                                <td class="num"><?= number_format((float)($row['total_huespedes'] ?? 0), 0) ?></td>
                                <td class="num"><?= number_format((float)($row['total_reservaciones'] ?? 0), 0) ?></td>
                                <td class="num">$<?= number_format((float)($row['ingresos_totales'] ?? 0), 2) ?></td>
                                <td class="num">$<?= number_format((float)($row['ticket_promedio'] ?? 0), 2) ?></td>
                                <td class="num"><?= number_format((float)($row['estancia_promedio'] ?? 0), 1) ?></td>
                                <td class="num"><?= number_format((float)($row['porcentaje_del_total'] ?? 0), 1) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="rank-panel">
            <h2>Comparativa contra periodo anterior</h2>
            <?php if (empty($comparativa)): ?>
                <div class="rank-empty">Sin comparativa disponible.</div>
            <?php else: ?>
                <div class="rank-table-wrap">
                    <table class="rank-table">
                        <thead><tr><th>Estado</th><th class="num">Actual</th><th class="num">Anterior</th><th class="num">Variacion</th></tr></thead>
                        <tbody>
                        <?php foreach ($comparativa as $row): ?>
                            <tr>
                                <td><?= rank_safe($row['estado'] ?? 'Sin estado') ?></td>
                                <td class="num"><?= number_format((float)($row['total_actual'] ?? 0), 0) ?></td>
                                <td class="num"><?= number_format((float)($row['total_anterior'] ?? 0), 0) ?></td>
                                <td class="num"><?= number_format((float)($row['variacion_porcentaje'] ?? 0), 1) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
