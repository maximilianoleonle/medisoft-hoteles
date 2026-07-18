<?php
/**
 * Detalle de un pedido de lavanderia: linea de tiempo del estado, partidas
 * con total, avance de estado, cobro por Caja (LAV-{id}, idempotente) y
 * cancelacion (solo sin cobro).
 */

if (!function_exists('lvx_safe')) {
    function lvx_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$pedido = $pedido ?? [];
$items = $items ?? [];
$estados = $estados ?? [];
$metodosPago = $metodos_pago ?? ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'];
$puedeOperar = !empty($puede_operar);
$puedeCobrar = !empty($puede_cobrar);

$pedidoId = (int)($pedido['id'] ?? 0);
$estado = (string)($pedido['estado'] ?? 'recibido');
$total = (float)($pedido['total'] ?? 0);
$cobrado = !empty($pedido['cobro_movimiento_id']);
$meta = $estados[$estado] ?? ['label' => $estado, 'clase' => 'st-pause', 'icono' => 'fa-circle'];

$flujo = ['recibido', 'en_proceso', 'listo', 'entregado'];
$idxActual = array_search($estado, $flujo, true);

$accionSiguiente = [
    'recibido'   => ['accion' => 'iniciar',  'label' => 'Empezar a lavar',  'icono' => 'fa-arrows-spin', 'clase' => ''],
    'en_proceso' => ['accion' => 'listo',    'label' => 'Marcar listo',     'icono' => 'fa-circle-check', 'clase' => 'is-ok'],
    'listo'      => ['accion' => 'entregar', 'label' => 'Entregar al huésped', 'icono' => 'fa-hand-holding-heart', 'clase' => 'is-ok'],
][$estado] ?? null;
?>

<?php include __DIR__ . '/_estilos.php'; ?>

<div class="lvx">

    <div class="lvx-header">
        <div class="lvx-header-shell">
            <div class="lvx-header-row">
                <div class="lvx-header-id">
                    <span class="lvx-header-ico"><i class="fas fa-basket-shopping"></i></span>
                    <div style="min-width:0;">
                        <h1>Pedido #<?= $pedidoId ?></h1>
                        <p class="lvx-header-sub">
                            <?= lvx_safe($pedido['cliente_nombre'] ?? '') ?>
                            <?= !empty($pedido['habitacion_etiqueta']) ? ' · hab. ' . lvx_safe($pedido['habitacion_etiqueta']) : '' ?>
                            · recibido el <?= lvx_safe(date('d/m/Y H:i', strtotime((string)($pedido['created_at'] ?? 'now')))) ?>
                        </p>
                    </div>
                </div>
                <div class="lvx-header-acts">
                    <span class="lvx-chip <?= $meta['clase'] ?>" style="font-size:.8rem;"><i class="fas <?= $meta['icono'] ?>"></i> <?= lvx_safe($meta['label']) ?></span>
                    <?php if ($cobrado): ?>
                    <span class="lvx-chip st-ok" style="font-size:.8rem;"><i class="fas fa-cash-register"></i> Cobrado</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            $subnav_section = 'lavanderia';
            $subnav_active = 'pedidos';
            include APP_PATH . '/views/partials/section_subnav.php';
            ?>
        </div>
    </div>

    <div class="lvx-shell">

        <?php if ($estado !== 'cancelado'): ?>
        <section class="lvx-card" style="margin-bottom:14px;">
            <div class="lvx-steps">
                <?php foreach ($flujo as $i => $paso): ?>
                    <?php
                    $pasoMeta = $estados[$paso] ?? ['label' => $paso, 'icono' => 'fa-circle'];
                    $clase = '';
                    if ($idxActual !== false) {
                        if ($i < $idxActual) { $clase = 'is-done'; }
                        elseif ($i === $idxActual) { $clase = $estado === 'entregado' ? 'is-done' : 'is-actual'; }
                    }
                    ?>
                    <?php if ($i > 0): ?><span class="lvx-step-sep"></span><?php endif; ?>
                    <span class="lvx-step <?= $clase ?>">
                        <i class="fas <?= lvx_safe($pasoMeta['icono']) ?>"></i> <?= lvx_safe($pasoMeta['label']) ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <?php if ($puedeOperar && ($accionSiguiente || in_array($estado, ['recibido', 'en_proceso', 'listo'], true))): ?>
            <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap;">
                <?php if ($accionSiguiente): ?>
                <form method="POST" action="<?= url('lavanderia/pedidos/' . $pedidoId . '/estado') ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="<?= $accionSiguiente['accion'] ?>">
                    <button type="submit" class="lvx-btn <?= $accionSiguiente['clase'] ?>">
                        <i class="fas <?= $accionSiguiente['icono'] ?>"></i> <?= $accionSiguiente['label'] ?>
                    </button>
                </form>
                <?php endif; ?>
                <?php if (in_array($estado, ['recibido', 'en_proceso', 'listo'], true)): ?>
                <form method="POST" action="<?= url('lavanderia/pedidos/' . $pedidoId . '/estado') ?>"
                      data-ms-confirm data-ms-type="error" data-ms-icon="x"
                      data-ms-title="¿Cancelar el pedido?"
                      data-ms-msg="<?= $cobrado ? 'El pedido ya fue cobrado: primero registra la devolución en Caja.' : 'El pedido quedará cancelado sin cobro.' ?>"
                      data-ms-ok="Sí, cancelar" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="cancelar">
                    <button type="submit" class="lvx-btn is-outline" style="color:var(--c-critical);">
                        <i class="fas fa-ban"></i> Cancelar pedido
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if (!$cobrado && $total > 0 && $estado !== 'cancelado' && $puedeCobrar): ?>
        <section class="lvx-card" style="border-color:color-mix(in srgb,var(--c-maint) 40%,var(--lvx-line)); margin-bottom:14px;">
            <h2 class="lvx-card-title"><i class="fas fa-cash-register"></i> Cobrar en Caja</h2>
            <form method="POST" action="<?= url('lavanderia/pedidos/' . $pedidoId . '/cobrar') ?>"
                  data-ms-confirm data-ms-type="warning" data-ms-icon="wallet"
                  data-ms-title="¿Cobrar el pedido?"
                  data-ms-msg="Se registrará un ingreso de $<?= number_format($total, 2) ?> en el corte abierto (referencia LAV-<?= $pedidoId ?>)."
                  data-ms-ok="Cobrar $<?= number_format($total, 2) ?>"
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
                <button type="submit" class="lvx-btn is-warn"><i class="fas fa-cash-register"></i> Cobrar $<?= number_format($total, 2) ?></button>
            </form>
            <p style="font-size:.76rem;color:var(--lvx-muted);margin:10px 0 0;">
                El cobro es independiente de la cuenta de hospedaje: entra a Caja como ingreso de Lavandería.
            </p>
        </section>
        <?php endif; ?>

        <section class="lvx-card">
            <h2 class="lvx-card-title"><i class="fas fa-list"></i> Prendas y servicios</h2>
            <div class="lvx-tabla-wrap">
                <table class="lvx-tabla">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th class="num">Cant.</th>
                            <th class="num">Precio</th>
                            <th class="num">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><b><?= lvx_safe($item['descripcion']) ?></b></td>
                            <td class="num"><?= (int)$item['cantidad'] ?></td>
                            <td class="num">$<?= number_format((float)$item['precio_unitario'], 2) ?></td>
                            <td class="num"><b>$<?= number_format((float)$item['importe'], 2) ?></b></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" style="text-align:right;font-weight:700;color:var(--lvx-muted);">Total</td>
                            <td class="num"><span class="lvx-total">$<?= number_format($total, 2) ?></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="lvx-card" style="margin-top:14px;">
            <h2 class="lvx-card-title"><i class="fas fa-circle-info"></i> Ficha del pedido</h2>
            <div class="lvx-hist">
                <div class="lvx-hist-item">
                    <div class="lvx-hist-top"><strong>Recibido</strong>
                        <span style="font-size:.72rem;color:var(--lvx-faint);"><?= lvx_safe(date('d/m/Y H:i', strtotime((string)($pedido['created_at'] ?? 'now')))) ?></span>
                    </div>
                    <?php if (!empty($pedido['recibido_por'])): ?><div class="lvx-hist-meta">Por <?= lvx_safe($pedido['recibido_por']) ?></div><?php endif; ?>
                </div>
                <?php if ($cobrado): ?>
                <div class="lvx-hist-item">
                    <div class="lvx-hist-top"><strong>Cobrado en Caja</strong>
                        <span style="font-size:.72rem;color:var(--lvx-faint);"><?= !empty($pedido['cobrado_en']) ? lvx_safe(date('d/m/Y H:i', strtotime((string)$pedido['cobrado_en']))) : '' ?></span>
                    </div>
                    <div class="lvx-hist-meta">$<?= number_format($total, 2) ?> · <?= lvx_safe($pedido['metodo_pago'] ?? 'efectivo') ?> · referencia LAV-<?= $pedidoId ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($pedido['entregado_en'])): ?>
                <div class="lvx-hist-item">
                    <div class="lvx-hist-top"><strong>Entregado</strong>
                        <span style="font-size:.72rem;color:var(--lvx-faint);"><?= lvx_safe(date('d/m/Y H:i', strtotime((string)$pedido['entregado_en']))) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($pedido['reservacion_id'])): ?>
                <div class="lvx-hist-item">
                    <div class="lvx-hist-top"><strong>Estancia vinculada</strong></div>
                    <div class="lvx-hist-meta">
                        <a href="<?= url('reservaciones/ver/' . (int)$pedido['reservacion_id']) ?>" style="color:var(--c-cleaning);font-weight:700;text-decoration:none;">
                            Reservación #<?= (int)$pedido['reservacion_id'] ?> <i class="fas fa-arrow-up-right-from-square" style="font-size:.65rem;"></i>
                        </a>
                        · solo referencia: el cobro de lavandería no toca la cuenta del hospedaje
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($pedido['notas'])): ?>
                <div class="lvx-hist-item">
                    <div class="lvx-hist-top"><strong>Notas</strong></div>
                    <div class="lvx-hist-meta"><?= lvx_safe($pedido['notas']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>
