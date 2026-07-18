<?php
/**
 * Pedidos de lavanderia de huesped: listado con filtros, resumen de cobro
 * y catalogo de servicios/precios del hotel (alimenta el datalist del alta).
 */

if (!function_exists('lvx_safe')) {
    function lvx_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$pedidos = $pedidos ?? [];
$filtros = $filtros ?? ['estado' => 'activos', 'buscar' => ''];
$resumenPedidos = $resumen_pedidos ?? ['activos' => 0, 'listos' => 0, 'por_cobrar' => 0, 'monto_por_cobrar' => 0.0];
$servicios = $servicios ?? [];
$estados = $estados ?? [];
$puedeOperar = !empty($puede_operar);
?>

<?php include __DIR__ . '/_estilos.php'; ?>

<div class="lvx">

    <div class="lvx-header">
        <div class="lvx-header-shell">
            <div class="lvx-header-row">
                <div class="lvx-header-id">
                    <span class="lvx-header-ico"><i class="fas fa-basket-shopping"></i></span>
                    <div style="min-width:0;">
                        <h1>Pedidos de lavandería</h1>
                        <p class="lvx-header-sub">Ropa de huéspedes: se recibe, se lava, se entrega y se cobra por Caja</p>
                    </div>
                </div>
                <?php if ($puedeOperar): ?>
                <div class="lvx-header-acts">
                    <button type="button" class="lvx-btn is-outline" onclick="lvxToggleCatalogo()">
                        <i class="fas fa-tags"></i> <span>Precios</span>
                    </button>
                    <a class="lvx-btn" href="<?= url('lavanderia/pedidos/nuevo') ?>" data-prefetch>
                        <i class="fas fa-plus-circle"></i> <span>Nuevo pedido</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php
            $subnav_section = 'lavanderia';
            $subnav_active = 'pedidos';
            include APP_PATH . '/views/partials/section_subnav.php';
            ?>
        </div>
    </div>

    <div class="lvx-shell">

        <div class="lvx-stats" style="--lvx-stats-n:3;">
            <div class="lvx-stat st-clean">
                <span class="lvx-stat-ic"><i class="fas fa-basket-shopping"></i></span>
                <span class="lvx-stat-n"><?= (int)$resumenPedidos['activos'] ?></span>
                <span class="lvx-stat-l">Pedidos activos</span>
            </div>
            <div class="lvx-stat st-ok">
                <span class="lvx-stat-ic"><i class="fas fa-circle-check"></i></span>
                <span class="lvx-stat-n"><?= (int)$resumenPedidos['listos'] ?></span>
                <span class="lvx-stat-l">Listos por entregar</span>
            </div>
            <div class="lvx-stat st-warn">
                <span class="lvx-stat-ic"><i class="fas fa-cash-register"></i></span>
                <span class="lvx-stat-n" style="font-size:1.45rem;line-height:1.4;">$<?= number_format((float)$resumenPedidos['monto_por_cobrar'], 2) ?></span>
                <span class="lvx-stat-l">Por cobrar (<?= (int)$resumenPedidos['por_cobrar'] ?> pedido<?= (int)$resumenPedidos['por_cobrar'] === 1 ? '' : 's' ?>)</span>
            </div>
        </div>

        <?php if ($puedeOperar): ?>
        <section class="lvx-card" id="lvxCatalogoCard" style="margin-bottom:14px; display:none;">
            <h2 class="lvx-card-title"><i class="fas fa-tags"></i> Catálogo de precios
                <span class="lvx-card-hint">Aparece como sugerencia al capturar pedidos; cada pedido congela su precio</span>
            </h2>
            <form method="POST" action="<?= url('lavanderia/servicios/guardar') ?>" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:12px;">
                <?= csrf_field() ?>
                <div style="flex:1;min-width:200px;">
                    <label class="lvx-lbl">Servicio</label>
                    <input type="text" name="nombre" required maxlength="120" placeholder="Camisa lavada y planchada…" class="lvx-input">
                </div>
                <div>
                    <label class="lvx-lbl">Precio</label>
                    <input type="text" name="precio" required data-money-format="true" placeholder="0.00" class="lvx-input" style="width:120px;">
                </div>
                <button type="submit" class="lvx-btn"><i class="fas fa-check"></i> Guardar</button>
            </form>
            <?php if (empty($servicios)): ?>
                <div style="font-size:.82rem;color:var(--lvx-muted);">Aún no hay servicios: registra los primeros (camisa, pantalón, tintorería…).</div>
            <?php else: ?>
            <div class="lvx-tabla-wrap">
                <table class="lvx-tabla">
                    <tbody>
                        <?php foreach ($servicios as $s): ?>
                        <tr>
                            <td <?= (int)$s['activo'] === 1 ? '' : 'style="opacity:.5;"' ?>><b><?= lvx_safe($s['nombre']) ?></b></td>
                            <td class="num" <?= (int)$s['activo'] === 1 ? '' : 'style="opacity:.5;"' ?>>$<?= number_format((float)$s['precio'], 2) ?></td>
                            <td class="num" style="width:110px;">
                                <form method="POST" action="<?= url('lavanderia/servicios/' . (int)$s['id'] . '/toggle') ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="lvx-mini">
                                        <i class="fas <?= (int)$s['activo'] === 1 ? 'fa-pause' : 'fa-play' ?>"></i>
                                        <?= (int)$s['activo'] === 1 ? 'Pausar' : 'Activar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php include APP_PATH . '/views/partials/filtros.php'; ?>
        <form class="msf-bar is-plain" method="GET" action="<?= url('lavanderia/pedidos') ?>" data-auto-filter-form>
            <label class="msf-field msf-field--grow">
                <span class="msf-label">Buscar</span>
                <input class="msf-control" type="search" name="buscar" placeholder="Huésped, habitación o # de pedido…" value="<?= lvx_safe($filtros['buscar']) ?>">
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Estado</span>
                <select class="msf-control" name="estado"><?= msf_options([
                    'activos' => 'Activos',
                    'recibido' => 'Recibidos',
                    'en_proceso' => 'En proceso',
                    'listo' => 'Listos',
                    'entregado' => 'Entregados',
                    'cancelado' => 'Cancelados',
                    'todos' => 'Todos',
                ], (string)$filtros['estado']) ?></select>
            </label>
        </form>

        <?php if (empty($pedidos)): ?>
            <section class="lvx-card">
                <div class="lvx-vacio">
                    <i class="fas fa-basket-shopping"></i>
                    <strong style="display:block;color:var(--lvx-title);font-weight:700;">Sin pedidos<?= (string)$filtros['estado'] !== 'todos' ? ' con este filtro' : '' ?></strong>
                    Cuando un huésped mande ropa a lavar, regístrala aquí con sus prendas y precios:
                    el cobro entra directo a Caja con referencia LAV-#.
                </div>
            </section>
        <?php else: ?>
            <section class="lvx-card" style="padding:6px 10px;">
                <div class="lvx-tabla-wrap">
                    <table class="lvx-tabla">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th class="num">Piezas</th>
                                <th class="num">Total</th>
                                <th>Estado</th>
                                <th>Cobro</th>
                                <th>Recibido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <?php $meta = $estados[$p['estado'] ?? 'recibido'] ?? ['label' => (string)($p['estado'] ?? ''), 'clase' => 'st-pause', 'icono' => 'fa-circle']; ?>
                                <tr data-easy-href="<?= url('lavanderia/pedidos/' . (int)$p['id']) ?>" role="link" tabindex="0">
                                    <td><b>#<?= (int)$p['id'] ?></b></td>
                                    <td>
                                        <b><?= lvx_safe($p['cliente_nombre']) ?></b>
                                        <?php if (!empty($p['habitacion_etiqueta'])): ?><br><small style="color:var(--lvx-faint);">Hab. <?= lvx_safe($p['habitacion_etiqueta']) ?></small><?php endif; ?>
                                    </td>
                                    <td class="num"><?= (int)$p['piezas'] ?></td>
                                    <td class="num"><b>$<?= number_format((float)$p['total'], 2) ?></b></td>
                                    <td><span class="lvx-chip <?= $meta['clase'] ?>"><i class="fas <?= $meta['icono'] ?>"></i> <?= lvx_safe($meta['label']) ?></span></td>
                                    <td>
                                        <?php if (!empty($p['cobro_movimiento_id'])): ?>
                                            <span class="lvx-chip st-ok"><i class="fas fa-circle-check"></i> Cobrado</span>
                                        <?php elseif ((string)$p['estado'] === 'cancelado'): ?>
                                            —
                                        <?php elseif ((float)$p['total'] > 0): ?>
                                            <span class="lvx-chip st-warn"><i class="fas fa-hourglass-half"></i> Por cobrar</span>
                                        <?php else: ?>
                                            <span class="lvx-chip st-pause">Sin importe</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= lvx_safe(date('d/m/Y H:i', strtotime((string)$p['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

    </div>
</div>

<script>
function lvxToggleCatalogo() {
    var card = document.getElementById('lvxCatalogoCard');
    if (!card) return;
    var abre = card.style.display === 'none';
    card.style.display = abre ? '' : 'none';
    if (abre) { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
}
</script>
