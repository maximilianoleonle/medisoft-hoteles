<?php
/**
 * Detalle de un lote de lavado: partidas, recepcion con mermas y costo,
 * cancelacion y gasto en Caja (referencia LAVLOTE-{id}, idempotente).
 */

if (!function_exists('lvx_safe')) {
    function lvx_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$lote = $lote ?? [];
$items = $items ?? [];
$metodosPago = $metodos_pago ?? ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'];
$puedeOperar = !empty($puede_operar);
$puedeCobrar = !empty($puede_cobrar);

$loteId = (int)($lote['id'] ?? 0);
$estado = (string)($lote['estado'] ?? 'en_proceso');
$esExterno = (string)($lote['tipo'] ?? 'interno') === 'externo';
$costo = (float)($lote['costo'] ?? 0);
$gastoRegistrado = !empty($lote['gasto_movimiento_id']);
$gastoPendiente = $estado === 'recibido' && $costo > 0 && !$gastoRegistrado;

$estadoMeta = [
    'en_proceso' => ['label' => 'En lavado',  'clase' => 'st-clean', 'icono' => 'fa-arrows-spin'],
    'recibido'   => ['label' => 'Recibido',   'clase' => 'st-ok',    'icono' => 'fa-circle-check'],
    'cancelado'  => ['label' => 'Cancelado',  'clase' => 'st-off',   'icono' => 'fa-ban'],
];
$meta = $estadoMeta[$estado] ?? $estadoMeta['en_proceso'];
?>

<?php include __DIR__ . '/_estilos.php'; ?>

<div class="lvx">

    <div class="lvx-header">
        <div class="lvx-header-shell">
            <div class="lvx-header-row">
                <div class="lvx-header-id">
                    <span class="lvx-header-ico"><i class="fas fa-arrows-spin"></i></span>
                    <div style="min-width:0;">
                        <h1>Lote de lavado #<?= $loteId ?></h1>
                        <p class="lvx-header-sub">
                            <?= $esExterno ? 'Servicio externo' . (!empty($lote['proveedor']) ? ' · ' . lvx_safe($lote['proveedor']) : '') : 'Lavado interno' ?>
                            · enviado el <?= lvx_safe(date('d/m/Y H:i', strtotime((string)($lote['created_at'] ?? 'now')))) ?>
                        </p>
                    </div>
                </div>
                <div class="lvx-header-acts">
                    <span class="lvx-chip <?= $meta['clase'] ?>" style="font-size:.8rem;"><i class="fas <?= $meta['icono'] ?>"></i> <?= $meta['label'] ?></span>
                </div>
            </div>
            <?php
            $subnav_section = 'lavanderia';
            $subnav_active = 'lotes';
            include APP_PATH . '/views/partials/section_subnav.php';
            ?>
        </div>
    </div>

    <div class="lvx-shell">

        <div class="lvx-stats" style="--lvx-stats-n:<?= $estado === 'recibido' ? 4 : 2 ?>;">
            <div class="lvx-stat st-clean">
                <span class="lvx-stat-ic"><i class="fas fa-basket-shopping"></i></span>
                <span class="lvx-stat-n"><?= (int)($lote['piezas_enviadas'] ?? 0) ?></span>
                <span class="lvx-stat-l">Piezas enviadas</span>
            </div>
            <?php if ($estado === 'recibido'): ?>
            <div class="lvx-stat st-ok">
                <span class="lvx-stat-ic"><i class="fas fa-circle-check"></i></span>
                <span class="lvx-stat-n"><?= (int)($lote['piezas_recibidas'] ?? 0) ?></span>
                <span class="lvx-stat-l">De vuelta limpias</span>
            </div>
            <div class="lvx-stat st-off">
                <span class="lvx-stat-ic"><i class="fas fa-triangle-exclamation"></i></span>
                <span class="lvx-stat-n"><?= (int)($lote['merma_total'] ?? 0) ?></span>
                <span class="lvx-stat-l">Merma</span>
            </div>
            <?php endif; ?>
            <div class="lvx-stat <?= $gastoPendiente ? 'st-warn' : 'st-busy' ?>">
                <span class="lvx-stat-ic"><i class="fas fa-cash-register"></i></span>
                <span class="lvx-stat-n" style="font-size:1.45rem;line-height:1.4;"><?= $costo > 0 ? '$' . number_format($costo, 2) : '—' ?></span>
                <span class="lvx-stat-l"><?= $gastoRegistrado ? 'Gasto en Caja · LAVLOTE-' . $loteId : ($gastoPendiente ? 'Gasto POR REGISTRAR' : 'Costo del lavado') ?></span>
            </div>
        </div>

        <?php if ($gastoPendiente && $puedeCobrar): ?>
        <section class="lvx-card" style="border-color:color-mix(in srgb,var(--c-maint) 40%,var(--lvx-line)); margin-bottom:14px;">
            <h2 class="lvx-card-title"><i class="fas fa-cash-register"></i> Registrar el gasto en Caja</h2>
            <p style="font-size:.82rem;color:var(--lvx-muted);margin:0 0 12px;">
                El costo quedó capturado pero aún no baja de Caja (probablemente estaba cerrada). Con caja abierta, regístralo aquí.
            </p>
            <form method="POST" action="<?= url('lavanderia/lotes/' . $loteId . '/gasto') ?>"
                  data-ms-confirm data-ms-type="warning" data-ms-icon="wallet"
                  data-ms-title="¿Registrar gasto en Caja?"
                  data-ms-msg="Se registrará un gasto de $<?= number_format($costo, 2) ?> (referencia LAVLOTE-<?= $loteId ?>) en el corte abierto."
                  data-ms-ok="Registrar gasto"
                  style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
                <?= csrf_field() ?>
                <div>
                    <label class="lvx-lbl">Método de pago</label>
                    <select name="metodo_pago" class="lvx-input" style="width:auto;min-width:160px;">
                        <?php foreach ($metodosPago as $mpKey => $mpLabel): ?>
                            <option value="<?= lvx_safe($mpKey) ?>"><?= lvx_safe(is_array($mpLabel) ? ($mpLabel['label'] ?? $mpKey) : $mpLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="lvx-btn is-warn"><i class="fas fa-cash-register"></i> Registrar gasto de $<?= number_format($costo, 2) ?></button>
            </form>
        </section>
        <?php endif; ?>

        <section class="lvx-card">
            <h2 class="lvx-card-title"><i class="fas fa-list"></i> Partidas del lote
                <?php if ($estado === 'en_proceso'): ?>
                <span class="lvx-card-hint">Al recibir, captura cuántas volvieron: la diferencia se registra como merma</span>
                <?php endif; ?>
            </h2>

            <?php if ($estado === 'en_proceso' && $puedeOperar): ?>
            <form method="POST" action="<?= url('lavanderia/lotes/' . $loteId . '/recibir') ?>" id="lavRecibirForm"
                  data-ms-confirm data-ms-type="success" data-ms-icon="check"
                  data-ms-title="¿Recibir el lote?"
                  data-ms-msg="Las piezas recibidas vuelven al stock limpio y las faltantes quedan como merma definitiva."
                  data-ms-ok="Recibir lote">
                <?= csrf_field() ?>
                <div class="lvx-tabla-wrap">
                    <table class="lvx-tabla">
                        <thead>
                            <tr>
                                <th>Blanco</th>
                                <th class="num">Enviadas</th>
                                <th class="num" style="width:130px;">Recibidas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><b><?= lvx_safe($item['blanco_nombre']) ?></b></td>
                                <td class="num"><?= (int)$item['cantidad_enviada'] ?></td>
                                <td class="num">
                                    <input type="number" class="lvx-input" style="width:100px;text-align:center;display:inline-block;"
                                           name="recibidas[<?= (int)$item['id'] ?>]"
                                           min="0" max="<?= (int)$item['cantidad_enviada'] ?>"
                                           value="<?= (int)$item['cantidad_enviada'] ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="lvx-form-grid" style="margin-top:14px;">
                    <div>
                        <label class="lvx-lbl">Costo del lavado (opcional<?= $esExterno ? ', se registra como gasto en Caja' : '' ?>)</label>
                        <input type="text" name="costo" class="lvx-input" data-money-format="true" placeholder="0.00">
                    </div>
                    <div>
                        <label class="lvx-lbl">Método de pago del gasto</label>
                        <select name="metodo_pago" class="lvx-input">
                            <?php foreach ($metodosPago as $mpKey => $mpLabel): ?>
                                <option value="<?= lvx_safe($mpKey) ?>"><?= lvx_safe(is_array($mpLabel) ? ($mpLabel['label'] ?? $mpKey) : $mpLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap;">
                    <button type="submit" class="lvx-btn is-ok"><i class="fas fa-circle-check"></i> Recibir lote</button>
                </div>
            </form>

            <form method="POST" action="<?= url('lavanderia/lotes/' . $loteId . '/cancelar') ?>"
                  data-ms-confirm data-ms-type="error" data-ms-icon="x"
                  data-ms-title="¿Cancelar el lote?"
                  data-ms-msg="Las <?= (int)($lote['piezas_enviadas'] ?? 0) ?> piezas regresarán al stock sucio."
                  data-ms-ok="Sí, cancelar"
                  style="margin-top:10px;">
                <?= csrf_field() ?>
                <button type="submit" class="lvx-mini" style="color:var(--c-critical);border-color:color-mix(in srgb,var(--c-critical) 35%,var(--lvx-line));">
                    <i class="fas fa-ban"></i> Cancelar lote (todo vuelve a sucio)
                </button>
            </form>
            <?php else: ?>
            <div class="lvx-tabla-wrap">
                <table class="lvx-tabla">
                    <thead>
                        <tr>
                            <th>Blanco</th>
                            <th class="num">Enviadas</th>
                            <th class="num">Recibidas</th>
                            <th class="num">Merma</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><b><?= lvx_safe($item['blanco_nombre']) ?></b></td>
                            <td class="num"><?= (int)$item['cantidad_enviada'] ?></td>
                            <td class="num"><?= $item['cantidad_recibida'] !== null ? (int)$item['cantidad_recibida'] : '—' ?></td>
                            <td class="num"><?= (int)$item['merma'] > 0 ? '<b style="color:var(--c-critical);">' . (int)$item['merma'] . '</b>' : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>

        <section class="lvx-card" style="margin-top:14px;">
            <h2 class="lvx-card-title"><i class="fas fa-circle-info"></i> Ficha del lote</h2>
            <div class="lvx-hist">
                <div class="lvx-hist-item">
                    <div class="lvx-hist-top"><strong>Enviado</strong>
                        <span style="font-size:.72rem;color:var(--lvx-faint);"><?= lvx_safe(date('d/m/Y H:i', strtotime((string)($lote['created_at'] ?? 'now')))) ?></span>
                    </div>
                    <?php if (!empty($lote['enviado_por'])): ?><div class="lvx-hist-meta">Por <?= lvx_safe($lote['enviado_por']) ?></div><?php endif; ?>
                </div>
                <?php if (!empty($lote['recibido_en'])): ?>
                <div class="lvx-hist-item">
                    <div class="lvx-hist-top"><strong>Recibido</strong>
                        <span style="font-size:.72rem;color:var(--lvx-faint);"><?= lvx_safe(date('d/m/Y H:i', strtotime((string)$lote['recibido_en']))) ?></span>
                    </div>
                    <?php if (!empty($lote['recibido_por'])): ?><div class="lvx-hist-meta">Por <?= lvx_safe($lote['recibido_por']) ?></div><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($gastoRegistrado): ?>
                <div class="lvx-hist-item">
                    <div class="lvx-hist-top"><strong>Gasto registrado en Caja</strong>
                        <span style="font-size:.72rem;color:var(--lvx-faint);"><?= !empty($lote['gasto_registrado_en']) ? lvx_safe(date('d/m/Y H:i', strtotime((string)$lote['gasto_registrado_en']))) : '' ?></span>
                    </div>
                    <div class="lvx-hist-meta">$<?= number_format($costo, 2) ?> · referencia LAVLOTE-<?= $loteId ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($lote['notas'])): ?>
                <div class="lvx-hist-item">
                    <div class="lvx-hist-top"><strong>Notas</strong></div>
                    <div class="lvx-hist-meta"><?= lvx_safe($lote['notas']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>
