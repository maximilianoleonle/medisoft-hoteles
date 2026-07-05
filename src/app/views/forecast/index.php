<?php
/**
 * Forecast de ocupacion (bloque forecast). Solo lectura.
 */
$totalHabitaciones = (int) ($totalHabitaciones ?? 0);
$porDia = $porDia ?? [];
$kpis = $kpis ?? ['ocupacion_30' => null, 'ocupacion_60' => null, 'ocupacion_90' => null];
$pickup = $pickup ?? [];
$semanas = $semanas ?? [];

$fcSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$fcPct = static function ($v) {
    return $v !== null ? number_format((float) $v, 1) . '%' : '—';
};
$fcFecha = static function ($iso) {
    $ts = strtotime((string) $iso . ' 12:00:00');
    return $ts ? date('d/m', $ts) : (string) $iso;
};

$reservas7 = (int) ($pickup['reservas_7'] ?? 0);
$reservasPrev = (int) ($pickup['reservas_prev'] ?? 0);
$deltaPickup = $reservas7 - $reservasPrev;

$dias30 = array_slice($porDia, 0, 30, true);
?>

<style>
.fc { max-width: 1080px; margin: 0 auto; padding: 18px 16px 40px; font-size: .92rem; }
.fc h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.fc .sub { margin: 0 0 16px; color: #6B7486; }
.fc-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 16px; }
.fc-kpi { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 12px; padding: 14px 16px; }
.fc-kpi .valor { font-size: 1.5rem; font-weight: 800; color: var(--brand-primary, #1B2746); }
.fc-kpi .valor small { font-size: .82rem; font-weight: 600; color: #8A93A6; }
.fc-kpi .nombre { font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; margin-top: 2px; }
.fc-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 14px; margin-bottom: 16px; overflow: hidden; }
.fc-card h2 { margin: 0; padding: 14px 16px 0; font-size: 1rem; color: var(--brand-primary, #1B2746); }
.fc-card .nota { padding: 0 16px; margin: 4px 0 0; font-size: .78rem; color: #8A93A6; }
.fc-barras { display: flex; align-items: flex-end; gap: 3px; height: 130px; padding: 14px 16px 6px; }
.fc-barra { flex: 1; min-width: 4px; background: color-mix(in srgb, var(--brand-primary, #1B2746) 78%, #fff); border-radius: 3px 3px 0 0; position: relative; }
.fc-barra.finde { background: color-mix(in srgb, var(--brand-accent, #BD9441) 85%, #fff); }
.fc-barra:hover::after { content: attr(data-tip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: #1B2746; color: #fff; font-size: .7rem; padding: 3px 8px; border-radius: 6px; white-space: nowrap; z-index: 5; }
.fc-ejes { display: flex; justify-content: space-between; padding: 0 16px 12px; font-size: .72rem; color: #8A93A6; }
.fc table { width: 100%; border-collapse: collapse; }
.fc th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid #EDE9DF; white-space: nowrap; }
.fc td { padding: 10px 12px; border-bottom: 1px solid #F2EFE7; }
.fc-delta-pos { color: #15803D; font-weight: 700; }
.fc-delta-neg { color: #B91C1C; font-weight: 700; }
.fc-ocup-pill { display: inline-block; min-width: 58px; text-align: center; padding: 3px 8px; border-radius: 999px; font-weight: 700; font-size: .8rem; }
</style>

<div class="fc">
    <h1>Forecast de ocupación</h1>
    <p class="sub">Cómo pinta tu ocupación hacia adelante y a qué ritmo estás vendiendo. Solo lectura: nada de esto modifica reservaciones.</p>

    <div class="fc-kpis">
        <div class="fc-kpi">
            <div class="valor"><?= $fcPct($kpis['ocupacion_30']) ?></div>
            <div class="nombre">Ocupación próx. 30 días</div>
        </div>
        <div class="fc-kpi">
            <div class="valor"><?= $fcPct($kpis['ocupacion_60']) ?></div>
            <div class="nombre">Próx. 60 días</div>
        </div>
        <div class="fc-kpi">
            <div class="valor"><?= $fcPct($kpis['ocupacion_90']) ?></div>
            <div class="nombre">Próx. 90 días</div>
        </div>
        <div class="fc-kpi">
            <div class="valor">
                <?= $reservas7 ?>
                <small class="<?= $deltaPickup >= 0 ? 'fc-delta-pos' : 'fc-delta-neg' ?>">
                    <?= $deltaPickup >= 0 ? '▲' : '▼' ?> <?= abs($deltaPickup) ?> vs sem. previa
                </small>
            </div>
            <div class="nombre">Reservas últimos 7 días</div>
        </div>
    </div>

    <div class="fc-card">
        <h2>Próximos 30 días, día por día</h2>
        <p class="nota"><?= (int) $totalHabitaciones ?> habitaciones activas · barras doradas = fin de semana · pasa el cursor para el detalle</p>
        <div class="fc-barras">
            <?php foreach ($dias30 as $fecha => $ocupadas): ?>
                <?php
                $pct = $totalHabitaciones > 0 ? min(100, round($ocupadas * 100 / $totalHabitaciones)) : 0;
                $altura = max(3, $pct);
                $diaSemana = (int) date('N', strtotime($fecha . ' 12:00:00'));
                ?>
                <div class="fc-barra <?= $diaSemana >= 5 ? 'finde' : '' ?>"
                     style="height: <?= $altura ?>%;"
                     data-tip="<?= $fcSafe($fcFecha($fecha)) ?>: <?= (int) $ocupadas ?> hab · <?= $pct ?>%"></div>
            <?php endforeach; ?>
        </div>
        <div class="fc-ejes">
            <span><?= $fcSafe($fcFecha(array_key_first($dias30) ?? '')) ?></span>
            <span><?= $fcSafe($fcFecha(array_key_last($dias30) ?? '')) ?></span>
        </div>
    </div>

    <div class="fc-card">
        <h2>Ritmo de ventas (pickup)</h2>
        <p class="nota">Reservas tomadas, sin importar para qué fechas son.</p>
        <table>
            <thead>
                <tr><th></th><th>Reservas</th><th>Noches-habitación</th><th>Monto</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight:600;">Últimos 7 días</td>
                    <td><?= $reservas7 ?></td>
                    <td><?= (int) ($pickup['noches_7'] ?? 0) ?></td>
                    <td>$<?= number_format((float) ($pickup['monto_7'] ?? 0), 2) ?></td>
                </tr>
                <tr>
                    <td style="font-weight:600;color:#8A93A6;">7 días anteriores</td>
                    <td style="color:#8A93A6;"><?= $reservasPrev ?></td>
                    <td style="color:#8A93A6;"><?= (int) ($pickup['noches_prev'] ?? 0) ?></td>
                    <td style="color:#8A93A6;">$<?= number_format((float) ($pickup['monto_prev'] ?? 0), 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="fc-card">
        <h2>Próximas 12 semanas vs el año pasado</h2>
        <p class="nota">La columna "hace 1 año" usa la ocupación real que tuviste en esas mismas fechas del año anterior.</p>
        <table>
            <thead>
                <tr><th>Semana</th><th>Noches vendidas</th><th>Ocupación proyectada</th><th>Hace 1 año</th><th>Diferencia</th></tr>
            </thead>
            <tbody>
            <?php foreach ($semanas as $sem): ?>
                <?php
                $ocup = $sem['ocupacion'];
                $pillBg = $ocup === null ? 'background:#F1F0EA;color:#8A93A6;'
                    : ($ocup >= 66 ? 'background:rgba(22,163,74,.12);color:#15803D;'
                    : ($ocup >= 33 ? 'background:rgba(245,158,11,.14);color:#92600A;'
                    : 'background:rgba(220,38,38,.1);color:#B91C1C;'));
                ?>
                <tr>
                    <td style="white-space:nowrap;font-weight:600;"><?= $fcSafe($fcFecha($sem['inicio'])) ?> – <?= $fcSafe($fcFecha($sem['fin'])) ?></td>
                    <td><?= (int) $sem['noches'] ?></td>
                    <td><span class="fc-ocup-pill" style="<?= $pillBg ?>"><?= $fcPct($ocup) ?></span></td>
                    <td style="color:#8A93A6;"><?= $fcPct($sem['ocupacion_anterior']) ?></td>
                    <td>
                        <?php if ($sem['delta'] === null): ?>
                            <span style="color:#94A3B8;">—</span>
                        <?php else: ?>
                            <span class="<?= $sem['delta'] >= 0 ? 'fc-delta-pos' : 'fc-delta-neg' ?>">
                                <?= $sem['delta'] >= 0 ? '▲' : '▼' ?> <?= number_format(abs((float) $sem['delta']), 1) ?> pts
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
