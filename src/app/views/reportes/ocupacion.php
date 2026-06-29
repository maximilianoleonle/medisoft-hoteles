<?php
$datos = $datos ?? [];
$estadisticas = $estadisticas ?? [];
$ocupacionPorDia = $ocupacionPorDia ?? [];
$tipo = $tipo ?? 'diario';
$fecha_inicio = $fecha_inicio ?? date('Y-m-01');
$fecha_fin = $fecha_fin ?? date('Y-m-d');

if (!function_exists('occ_safe')) {
    function occ_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('occ_date')) {
    function occ_date($value) {
        $ts = strtotime((string)$value);
        return $ts ? date('d/m/Y', $ts) : '-';
    }
}
?>

<style>
.occ-report-view { --occ-primary: var(--brand-primary, #1B2746); --occ-accent: var(--brand-accent, #BD9441); --occ-line: #E5E7EB; --occ-muted: #667085; --occ-bg: #F8FAFC; color: #172033; }
.occ-shell { max-width: 1180px; margin: 0 auto; padding: 24px clamp(14px, 3vw, 28px) 40px; }
.occ-hero { display: flex; justify-content: space-between; gap: 18px; align-items: flex-end; border-bottom: 1px solid var(--occ-line); padding-bottom: 18px; }
.occ-kicker { display: block; color: var(--occ-accent); font-size: .78rem; font-weight: 800; text-transform: uppercase; }
.occ-hero h1 { margin: 5px 0 0; font-size: clamp(1.65rem, 2.5vw, 2.35rem); letter-spacing: 0; }
.occ-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
.occ-actions input, .occ-actions select { min-height: 42px; border: 1px solid var(--occ-line); border-radius: 8px; padding: 0 10px; background: #fff; }
.occ-btn { min-height: 42px; border: 0; border-radius: 8px; padding: 0 16px; background: var(--occ-primary); color: #fff; font-weight: 800; cursor: pointer; }
.occ-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 22px 0; }
.occ-metric { background: #fff; border: 1px solid var(--occ-line); border-radius: 8px; padding: 16px; }
.occ-metric span { display: block; color: var(--occ-muted); font-size: .78rem; font-weight: 800; text-transform: uppercase; }
.occ-metric strong { display: block; margin-top: 6px; font-size: 1.5rem; color: var(--occ-primary); }
.occ-panel { background: #fff; border: 1px solid var(--occ-line); border-radius: 8px; overflow: hidden; margin-top: 16px; }
.occ-panel h2 { margin: 0; padding: 14px 16px; font-size: 1rem; border-bottom: 1px solid var(--occ-line); }
.occ-table-wrap { overflow-x: auto; }
.occ-table { width: 100%; border-collapse: collapse; min-width: 680px; }
.occ-table th, .occ-table td { padding: 11px 14px; border-bottom: 1px solid var(--occ-line); text-align: left; font-size: .9rem; }
.occ-table th { background: var(--occ-bg); color: var(--occ-muted); font-size: .76rem; text-transform: uppercase; }
.occ-table td.num, .occ-table th.num { text-align: right; }
.occ-empty { padding: 18px; color: var(--occ-muted); }
@media (max-width: 760px) { .occ-hero { display: block; } .occ-actions { justify-content: stretch; margin-top: 16px; } .occ-actions input, .occ-actions select, .occ-actions button { width: 100%; } .occ-grid { grid-template-columns: 1fr 1fr; } }
</style>

<div class="occ-report-view">
    <main class="occ-shell">
        <section class="occ-hero">
            <div>
                <span class="occ-kicker">Reportes</span>
                <h1>Tasa de ocupacion</h1>
            </div>
            <form class="occ-actions" method="get" action="<?= url('reportes/ocupacion') ?>">
                <input type="date" name="fecha_inicio" value="<?= occ_safe($fecha_inicio) ?>">
                <input type="date" name="fecha_fin" value="<?= occ_safe($fecha_fin) ?>">
                <select name="tipo">
                    <option value="diario" <?= $tipo === 'diario' ? 'selected' : '' ?>>Diario</option>
                    <option value="semanal" <?= $tipo === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                    <option value="mensual" <?= $tipo === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                </select>
                <button class="occ-btn" type="submit">Filtrar</button>
            </form>
        </section>

        <section class="occ-grid">
            <article class="occ-metric"><span>Dias</span><strong><?= number_format((float)($estadisticas['dias_periodo'] ?? 0), 0) ?></strong></article>
            <article class="occ-metric"><span>Disponibles</span><strong><?= number_format((float)($estadisticas['habitaciones_disponibles'] ?? 0), 0) ?></strong></article>
            <article class="occ-metric"><span>Ocupadas-dia</span><strong><?= number_format((float)($estadisticas['habitaciones_ocupadas'] ?? 0), 0) ?></strong></article>
            <article class="occ-metric"><span>Ocupacion</span><strong><?= number_format((float)($estadisticas['porcentaje_ocupacion'] ?? 0), 1) ?>%</strong></article>
        </section>

        <section class="occ-panel">
            <h2>Detalle <?= occ_safe($tipo) ?></h2>
            <?php if (empty($datos)): ?>
                <div class="occ-empty">Sin datos para el periodo seleccionado.</div>
            <?php else: ?>
                <div class="occ-table-wrap">
                    <table class="occ-table">
                        <thead>
                            <tr>
                                <th>Periodo</th>
                                <th class="num">Habitaciones ocupadas</th>
                                <th class="num">Ocupacion</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($datos as $row): ?>
                            <?php
                            $periodo = $row['fecha'] ?? $row['semana'] ?? $row['mes'] ?? '-';
                            if (!empty($row['fecha_inicio_semana']) && !empty($row['fecha_fin_semana'])) {
                                $periodo = occ_date($row['fecha_inicio_semana']) . ' - ' . occ_date($row['fecha_fin_semana']);
                            } elseif (!empty($row['fecha'])) {
                                $periodo = occ_date($row['fecha']);
                            }
                            ?>
                            <tr>
                                <td><?= occ_safe($periodo) ?></td>
                                <td class="num"><?= number_format((float)($row['habitaciones_ocupadas'] ?? $row['habitaciones_ocupadas_dias'] ?? 0), 0) ?></td>
                                <td class="num"><?= number_format((float)($row['porcentaje_ocupacion'] ?? 0), 1) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="occ-panel">
            <h2>Por dia de semana</h2>
            <?php if (empty($ocupacionPorDia)): ?>
                <div class="occ-empty">Sin datos por dia de semana.</div>
            <?php else: ?>
                <div class="occ-table-wrap">
                    <table class="occ-table">
                        <thead>
                            <tr><th>Dia</th><th class="num">Ocupadas</th><th class="num">Precio promedio</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ocupacionPorDia as $row): ?>
                            <tr>
                                <td><?= occ_safe($row['nombre_dia'] ?? '-') ?></td>
                                <td class="num"><?= number_format((float)($row['habitaciones_ocupadas'] ?? 0), 0) ?></td>
                                <td class="num">$<?= number_format((float)($row['precio_promedio'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
