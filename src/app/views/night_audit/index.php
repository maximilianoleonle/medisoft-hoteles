<?php
/**
 * Night audit (bloque night_audit): historial de cierres nocturnos.
 */
$historial = $historial ?? [];
$ultimo = $ultimo ?? null;
$hallazgosUltimo = $hallazgosUltimo ?? [];
$fechaSugerida = $fechaSugerida ?? date('Y-m-d', strtotime('-1 day'));

$naSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$naFecha = static function ($iso) {
    $ts = strtotime((string) $iso . ' 12:00:00');
    return $ts ? date('d/m/Y', $ts) : (string) $iso;
};
$naPendientes = static function ($c) {
    return (int) $c['no_shows'] + (int) $c['checkouts_vencidos'] + (int) $c['cortes_abiertos'];
};
?>

<style>
.na { max-width: 1080px; margin: 0 auto; padding: 18px 16px 40px; font-size: .92rem; }
.na h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.na .sub { margin: 0 0 16px; color: #6B7486; }
.na-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 14px; overflow: hidden; margin-bottom: 16px; }
.na-card h2 { margin: 0; padding: 14px 16px 4px; font-size: 1rem; color: var(--brand-primary, #1B2746); }
.na table { width: 100%; border-collapse: collapse; }
.na th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid #EDE9DF; white-space: nowrap; }
.na td { padding: 10px 12px; border-bottom: 1px solid #F2EFE7; vertical-align: middle; }
.na-vacio { padding: 30px 16px; text-align: center; color: #8A93A6; }
.na-pill { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
.na-btn { display: inline-flex; align-items: center; gap: 6px; min-height: 40px; padding: 0 14px; border: 0; border-radius: 8px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .84rem; font-weight: 700; }
.na-manual { padding: 14px 16px; display: flex; gap: 10px; flex-wrap: wrap; align-items: end; }
.na-manual label { display: block; font-size: .74rem; font-weight: 700; color: #55607A; margin-bottom: 4px; }
.na-manual input { min-height: 40px; border: 1px solid #D8D4C9; border-radius: 8px; padding: 0 10px; font-size: .88rem; }
.na-lista { margin: 6px 16px 16px; padding-left: 20px; color: #55607A; font-size: .86rem; line-height: 1.7; }
</style>

<div class="na">
    <h1>Night audit</h1>
    <p class="sub">El cierre nocturno de cada día: no-shows, checkouts vencidos y cortes abiertos. Solo detecta y avisa — corregir sigue siendo decisión tuya.</p>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="na-card">
        <form class="na-manual" method="POST" action="<?= url('night-audit/ejecutar') ?>">
            <?= csrf_field() ?>
            <div>
                <label for="na-fecha">Cerrar el día</label>
                <input type="date" id="na-fecha" name="fecha" value="<?= $naSafe($fechaSugerida) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <button type="submit" class="na-btn">Ejecutar cierre ahora</button>
            <span style="font-size:.76rem;color:#8A93A6;">El cron lo hace solo cada madrugada; este botón es para no esperar. Si el día ya está cerrado, no se duplica.</span>
        </form>
    </div>

    <?php if ($ultimo): ?>
    <div class="na-card">
        <h2>Último cierre · <?= $naSafe($naFecha($ultimo['fecha'])) ?></h2>
        <table>
            <thead>
                <tr><th>Llegadas</th><th>Salidas</th><th>Ocupadas</th><th>Pagos online</th><th>No-shows</th><th>Checkouts vencidos</th><th>Cortes abiertos</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= (int) $ultimo['llegadas'] ?></td>
                    <td><?= (int) $ultimo['salidas'] ?></td>
                    <td><?= (int) $ultimo['ocupadas_noche'] ?></td>
                    <td><?= (int) $ultimo['pagos_online'] ?> ($<?= number_format((float) $ultimo['monto_online'], 2) ?>)</td>
                    <td style="<?= (int) $ultimo['no_shows'] > 0 ? 'color:#B91C1C;font-weight:700;' : '' ?>"><?= (int) $ultimo['no_shows'] ?></td>
                    <td style="<?= (int) $ultimo['checkouts_vencidos'] > 0 ? 'color:#B91C1C;font-weight:700;' : '' ?>"><?= (int) $ultimo['checkouts_vencidos'] ?></td>
                    <td style="<?= (int) $ultimo['cortes_abiertos'] > 0 ? 'color:#B91C1C;font-weight:700;' : '' ?>"><?= (int) $ultimo['cortes_abiertos'] ?></td>
                </tr>
            </tbody>
        </table>
        <?php
        $listaPendientes = [];
        foreach (($hallazgosUltimo['no_shows'] ?? []) as $r) {
            $listaPendientes[] = 'No-show: <a href="' . url('reservaciones/ver/' . (int) $r['id']) . '" style="font-weight:700;color:var(--brand-primary,#1B2746);">' . $naSafe($r['nombre_completo']) . ' #' . (int) $r['id'] . '</a> (llegaba el ' . $naSafe($naFecha($r['fecha_entrada'])) . ')';
        }
        foreach (($hallazgosUltimo['checkouts_vencidos'] ?? []) as $r) {
            $listaPendientes[] = 'Checkout vencido: <a href="' . url('reservaciones/ver/' . (int) $r['id']) . '" style="font-weight:700;color:var(--brand-primary,#1B2746);">' . $naSafe($r['nombre_completo']) . ' #' . (int) $r['id'] . '</a> (salía el ' . $naSafe($naFecha($r['fecha_salida'])) . ')';
        }
        foreach (($hallazgosUltimo['cortes_abiertos'] ?? []) as $c) {
            $listaPendientes[] = 'Corte de caja #' . (int) $c['id'] . ' abierto desde ' . $naSafe($c['fecha_apertura']);
        }
        ?>
        <?php if (!empty($listaPendientes)): ?>
            <ul class="na-lista">
                <?php foreach ($listaPendientes as $item): ?><li><?= $item ?></li><?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="na-lista" style="list-style:none;color:#15803D;font-weight:700;">✔ Día cerrado limpio, sin pendientes.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="na-card">
        <h2>Historial de cierres</h2>
        <table>
            <thead>
                <tr><th>Fecha</th><th>Llegadas</th><th>Salidas</th><th>Ocupadas</th><th>Pendientes</th><th>Correo</th></tr>
            </thead>
            <tbody>
            <?php if (empty($historial)): ?>
                <tr><td colspan="6" class="na-vacio">Todavía no hay cierres. Ejecuta el primero arriba o espera al cron de la madrugada.</td></tr>
            <?php else: ?>
                <?php foreach ($historial as $c): ?>
                    <?php $p = $naPendientes($c); ?>
                    <tr>
                        <td style="font-weight:600;white-space:nowrap;"><?= $naSafe($naFecha($c['fecha'])) ?></td>
                        <td><?= (int) $c['llegadas'] ?></td>
                        <td><?= (int) $c['salidas'] ?></td>
                        <td><?= (int) $c['ocupadas_noche'] ?></td>
                        <td>
                            <?php if ($p > 0): ?>
                                <span class="na-pill" style="background:rgba(220,38,38,.1);color:#B91C1C;"><?= $p ?> pendiente(s)</span>
                            <?php else: ?>
                                <span class="na-pill" style="background:rgba(22,163,74,.12);color:#15803D;">Limpio</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.78rem;color:#8A93A6;"><?= $c['correo_enviado_at'] ? '✉ ' . $naSafe(date('d/m H:i', strtotime((string) $c['correo_enviado_at']))) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
