<?php
/**
 * Panel de Lavanderia: blancos con stock por estado (limpio/sucio/en lavado),
 * alta y movimientos manuales, resumen de lotes y pedidos, bitacora reciente.
 * Lenguaje visual calcado de areas/index.php (header sin franja + subnav +
 * fichas stat + grid boutique); contratos: forms inline con hidden id.
 */

if (!function_exists('lvx_safe')) {
    function lvx_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$blancos = $blancos ?? [];
$categorias = $categorias ?? [];
$tiposMovimiento = $tipos_movimiento ?? [];
$resumenStock = $resumen_stock ?? ['limpio' => 0, 'sucio' => 0, 'proceso' => 0, 'bajo_minimo' => 0, 'blancos' => 0];
$resumenLotes = $resumen_lotes ?? ['en_proceso' => 0, 'gastos_pendientes' => 0];
$resumenPedidos = $resumen_pedidos ?? ['activos' => 0, 'listos' => 0, 'por_cobrar' => 0, 'monto_por_cobrar' => 0.0];
$movimientos = $movimientos ?? [];
$puedeOperar = !empty($puede_operar);

$tipoLedgerMeta = [
    'compra'  => ['label' => 'Alta de piezas',  'icono' => 'fa-cart-plus',        'color' => 'var(--c-available)'],
    'uso'     => ['label' => 'Se ensució',      'icono' => 'fa-arrow-right',      'color' => 'var(--c-maint)'],
    'baja'    => ['label' => 'Baja',            'icono' => 'fa-arrow-down',       'color' => 'var(--c-critical)'],
    'envio'   => ['label' => 'Enviado a lavar', 'icono' => 'fa-arrows-spin',      'color' => 'var(--c-cleaning)'],
    'retorno' => ['label' => 'Recibido limpio', 'icono' => 'fa-circle-check',     'color' => 'var(--c-available)'],
    'merma'   => ['label' => 'Merma',           'icono' => 'fa-triangle-exclamation', 'color' => 'var(--c-critical)'],
    'ajuste'  => ['label' => 'Ajuste',          'icono' => 'fa-rotate-left',      'color' => 'var(--lvx-muted)'],
];
?>

<?php include __DIR__ . '/_estilos.php'; ?>

<div class="lvx">

    <div class="lvx-header">
        <div class="lvx-header-shell">
            <div class="lvx-header-row">
                <div class="lvx-header-id">
                    <span class="lvx-header-ico"><i class="fas fa-shirt"></i></span>
                    <div style="min-width:0;">
                        <h1>Lavandería</h1>
                        <p class="lvx-header-sub">Blancos del hotel, ciclos de lavado y ropa de huéspedes</p>
                    </div>
                </div>
                <?php if ($puedeOperar): ?>
                <div class="lvx-header-acts">
                    <button type="button" class="lvx-btn is-outline" onclick="lvxToggleMov()">
                        <i class="fas fa-right-left"></i> <span>Registrar movimiento</span>
                    </button>
                    <button type="button" class="lvx-btn" onclick="lvxToggleForm()">
                        <i class="fas fa-plus-circle"></i> <span>Nuevo blanco</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php
            $subnav_section = 'lavanderia';
            $subnav_active = 'panel';
            include APP_PATH . '/views/partials/section_subnav.php';
            ?>
        </div>
    </div>

    <div class="lvx-shell">

        <?php if (!empty($blancos)): ?>
        <div class="lvx-stats" style="--lvx-stats-n:4;">
            <div class="lvx-stat st-ok">
                <span class="lvx-stat-ic"><i class="fas fa-circle-check"></i></span>
                <span class="lvx-stat-n"><?= (int)$resumenStock['limpio'] ?></span>
                <span class="lvx-stat-l">Piezas limpias</span>
            </div>
            <div class="lvx-stat st-warn">
                <span class="lvx-stat-ic"><i class="fas fa-basket-shopping"></i></span>
                <span class="lvx-stat-n"><?= (int)$resumenStock['sucio'] ?></span>
                <span class="lvx-stat-l">Por lavar</span>
            </div>
            <a class="lvx-stat st-clean" href="<?= url('lavanderia/lotes') ?>" data-prefetch title="Ver ciclos de lavado">
                <span class="lvx-stat-ic"><i class="fas fa-arrows-spin"></i></span>
                <span class="lvx-stat-n"><?= (int)$resumenStock['proceso'] ?></span>
                <span class="lvx-stat-l">En lavado<?= (int)$resumenLotes['en_proceso'] > 0 ? ' · ' . (int)$resumenLotes['en_proceso'] . ' lote' . ((int)$resumenLotes['en_proceso'] === 1 ? '' : 's') : '' ?></span>
            </a>
            <a class="lvx-stat st-busy" href="<?= url('lavanderia/pedidos') ?>" data-prefetch title="Ver pedidos de huéspedes">
                <span class="lvx-stat-ic"><i class="fas fa-hand-holding-heart"></i></span>
                <span class="lvx-stat-n"><?= (int)$resumenPedidos['activos'] ?></span>
                <span class="lvx-stat-l">Pedidos activos<?= (int)$resumenPedidos['listos'] > 0 ? ' · ' . (int)$resumenPedidos['listos'] . ' por entregar' : '' ?></span>
            </a>
        </div>

        <?php if ((int)$resumenStock['bajo_minimo'] > 0): ?>
        <section class="lvx-card" style="border-color:color-mix(in srgb,var(--c-critical) 35%,var(--lvx-line)); margin-bottom:14px;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="lvx-stat-ic" style="--sc:var(--c-critical); margin:0;"><i class="fas fa-triangle-exclamation"></i></span>
                <div style="min-width:0;flex:1;">
                    <strong style="color:var(--lvx-title);"><?= (int)$resumenStock['bajo_minimo'] ?> blanco<?= (int)$resumenStock['bajo_minimo'] === 1 ? '' : 's' ?> por debajo del mínimo de piezas limpias</strong>
                    <div style="font-size:.78rem;color:var(--lvx-muted);">Envía un ciclo de lavado o da de alta piezas para reponer el par.</div>
                </div>
                <?php if ($puedeOperar): ?>
                <a class="lvx-btn is-soft" href="<?= url('lavanderia/lotes/nuevo') ?>"><i class="fas fa-arrows-spin"></i> Enviar a lavar</a>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($puedeOperar): ?>
        <section class="lvx-card" id="lvxFormCard" style="margin-bottom:14px; <?= empty($blancos) ? '' : 'display:none;' ?>">
            <h2 class="lvx-card-title"><i class="fas fa-plus-circle"></i> <span id="lvxFormTitulo">Nuevo blanco</span></h2>
            <form id="lvxForm" method="POST" action="<?= url('lavanderia/blancos/guardar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="" id="lvxFormId">
                <div class="lvx-form-grid">
                    <div>
                        <label class="lvx-lbl">Nombre del blanco</label>
                        <input type="text" name="nombre" id="lvxFormNombre" required maxlength="120" placeholder="Sábana king, toalla de baño…" class="lvx-input">
                    </div>
                    <div>
                        <label class="lvx-lbl">Categoría</label>
                        <select name="categoria" id="lvxFormCategoria" class="lvx-input">
                            <?php foreach ($categorias as $catKey => $catMeta): ?>
                                <option value="<?= lvx_safe($catKey) ?>"><?= lvx_safe($catMeta['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="lvxFormStockWrap">
                        <label class="lvx-lbl">Piezas limpias iniciales</label>
                        <input type="number" name="stock_limpio" id="lvxFormStock" min="0" max="100000" placeholder="0" class="lvx-input">
                    </div>
                    <div>
                        <label class="lvx-lbl">Mínimo de piezas limpias (alerta)</label>
                        <input type="number" name="stock_minimo" id="lvxFormMinimo" min="0" max="100000" placeholder="0 = sin alerta" class="lvx-input">
                    </div>
                    <div style="grid-column:1/-1;">
                        <label class="lvx-lbl">Notas (opcional)</label>
                        <input type="text" name="notas" id="lvxFormNotas" maxlength="300" placeholder="Proveedor, medidas, color…" class="lvx-input">
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="lvx-btn"><i class="fas fa-check"></i> Guardar blanco</button>
                    <button type="button" class="lvx-btn is-outline" onclick="lvxToggleForm(false)">Cancelar</button>
                </div>
            </form>
        </section>

        <section class="lvx-card" id="lvxMovCard" style="margin-bottom:14px; display:none;">
            <h2 class="lvx-card-title"><i class="fas fa-right-left"></i> Registrar movimiento de blancos
                <span class="lvx-card-hint">Los envíos y retornos de lavado se registran desde Ciclos de lavado</span>
            </h2>
            <form method="POST" action="<?= url('lavanderia/blancos/movimiento') ?>">
                <?= csrf_field() ?>
                <div class="lvx-form-grid">
                    <div>
                        <label class="lvx-lbl">Blanco</label>
                        <select name="blanco_id" id="lvxMovBlanco" required class="lvx-input">
                            <option value="">Elige un blanco…</option>
                            <?php foreach ($blancos as $b): ?>
                                <?php if ((int)$b['activo'] === 1): ?>
                                <option value="<?= (int)$b['id'] ?>"><?= lvx_safe($b['nombre']) ?> (<?= (int)$b['stock_limpio'] ?> limpias · <?= (int)$b['stock_sucio'] ?> sucias)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="lvx-lbl">Tipo de movimiento</label>
                        <select name="tipo" id="lvxMovTipo" required class="lvx-input">
                            <?php foreach ($tiposMovimiento as $tipoKey => $tipoLabel): ?>
                                <option value="<?= lvx_safe($tipoKey) ?>"><?= lvx_safe($tipoLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="lvx-lbl">Cantidad de piezas</label>
                        <input type="number" name="cantidad" id="lvxMovCantidad" min="1" max="100000" required placeholder="1" class="lvx-input">
                    </div>
                    <div>
                        <label class="lvx-lbl">Notas (opcional)</label>
                        <input type="text" name="notas" maxlength="300" placeholder="Motivo, referencia…" class="lvx-input">
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="lvx-btn"><i class="fas fa-check"></i> Registrar</button>
                    <button type="button" class="lvx-btn is-outline" onclick="lvxToggleMov(false)">Cancelar</button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <?php if (empty($blancos)): ?>
            <section class="lvx-card">
                <div class="lvx-vacio">
                    <i class="fas fa-shirt"></i>
                    <strong style="display:block;color:var(--lvx-title);font-weight:700;">Aún no registras blancos</strong>
                    Da de alta tus sábanas, toallas y manteles con sus piezas: desde aquí controlas qué está limpio,
                    qué falta lavar y qué anda en la lavandería.
                    <?php if ($puedeOperar): ?>
                    <div style="margin-top:14px;">
                        <button type="button" class="lvx-btn" onclick="lvxToggleForm(true)">
                            <i class="fas fa-plus-circle"></i> Registrar mi primer blanco
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="lvx-grid" id="lvxGrid">
                <?php foreach ($blancos as $b): ?>
                    <?php
                    $estaActivo = (int)($b['activo'] ?? 1) === 1;
                    $catMeta = $categorias[$b['categoria'] ?? 'otro'] ?? ['label' => 'Otro', 'icono' => 'fa-layer-group'];
                    $bajoMinimo = (int)$b['stock_minimo'] > 0 && (int)$b['stock_limpio'] < (int)$b['stock_minimo'];
                    ?>
                    <div class="lvx-item <?= $estaActivo ? '' : 'is-pausada' ?>">
                        <div class="lvx-item-top">
                            <div class="lvx-item-id">
                                <span class="lvx-tipo-ico"><i class="fas <?= lvx_safe($catMeta['icono']) ?>"></i></span>
                                <div style="min-width:0;">
                                    <strong><?= lvx_safe($b['nombre']) ?></strong>
                                    <small><?= lvx_safe($catMeta['label']) ?><?= (int)$b['stock_minimo'] > 0 ? ' · mín. ' . (int)$b['stock_minimo'] : '' ?></small>
                                </div>
                            </div>
                            <?php if (!$estaActivo): ?>
                                <span class="lvx-chip st-pause"><i class="fas fa-pause"></i> Pausado</span>
                            <?php elseif ($bajoMinimo): ?>
                                <span class="lvx-chip st-off"><i class="fas fa-triangle-exclamation"></i> Bajo mínimo</span>
                            <?php endif; ?>
                        </div>
                        <div class="lvx-stock">
                            <div class="lvx-stock-cell is-ok"><b><?= (int)$b['stock_limpio'] ?></b><span>Limpias</span></div>
                            <div class="lvx-stock-cell is-warn"><b><?= (int)$b['stock_sucio'] ?></b><span>Sucias</span></div>
                            <div class="lvx-stock-cell is-clean"><b><?= (int)$b['stock_proceso'] ?></b><span>En lavado</span></div>
                        </div>
                        <?php if ($puedeOperar): ?>
                        <div class="lvx-item-acts">
                            <?php if ($estaActivo): ?>
                            <button type="button" class="lvx-mini" onclick="lvxMovPrefill(<?= (int)$b['id'] ?>, 'uso')" title="Pasar piezas de limpio a sucio">
                                <i class="fas fa-arrow-right"></i> Se ensució
                            </button>
                            <?php endif; ?>
                            <button type="button" class="lvx-mini"
                                    onclick='lvxEditar(<?= json_encode([
                                        'id' => (int)$b['id'],
                                        // La BD guarda el texto ya escapado (sanitize global de
                                        // post()): al precargar el form se DESescapa o cada
                                        // guardado apilaria una capa mas (&amp;amp;...).
                                        'nombre' => html_entity_decode((string)$b['nombre'], ENT_QUOTES, 'UTF-8'),
                                        'categoria' => (string)($b['categoria'] ?? 'otro'),
                                        'stock_minimo' => (int)($b['stock_minimo'] ?? 0),
                                        'notas' => html_entity_decode((string)($b['notas'] ?? ''), ENT_QUOTES, 'UTF-8'),
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                                <i class="fas fa-pen"></i> Editar
                            </button>
                            <form method="POST" action="<?= url('lavanderia/blancos/' . (int)$b['id'] . '/toggle') ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="lvx-mini">
                                    <i class="fas <?= $estaActivo ? 'fa-pause' : 'fa-play' ?>"></i>
                                    <?= $estaActivo ? 'Pausar' : 'Reactivar' ?>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($movimientos)): ?>
            <section class="lvx-card" style="margin-top:16px;">
                <h2 class="lvx-card-title"><i class="fas fa-clock-rotate-left"></i> Últimos movimientos</h2>
                <div class="lvx-hist">
                    <?php foreach ($movimientos as $m): ?>
                        <?php $meta = $tipoLedgerMeta[$m['tipo'] ?? ''] ?? ['label' => (string)($m['tipo'] ?? ''), 'icono' => 'fa-circle', 'color' => 'var(--lvx-muted)']; ?>
                        <div class="lvx-hist-item">
                            <div class="lvx-hist-top">
                                <strong><i class="fas <?= lvx_safe($meta['icono']) ?>" style="color:<?= $meta['color'] ?>;margin-right:6px;"></i><?= lvx_safe($meta['label']) ?> × <?= (int)$m['cantidad'] ?> — <?= lvx_safe($m['blanco_nombre']) ?></strong>
                                <span style="font-size:.72rem;color:var(--lvx-faint);"><?= lvx_safe(date('d/m/Y H:i', strtotime((string)$m['created_at']))) ?></span>
                            </div>
                            <?php if (!empty($m['notas']) || !empty($m['usuario_nombre'])): ?>
                            <div class="lvx-hist-meta">
                                <?= lvx_safe($m['notas'] ?? '') ?><?= !empty($m['notas']) && !empty($m['usuario_nombre']) ? ' · ' : '' ?><?= lvx_safe($m['usuario_nombre'] ?? '') ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<script>
function lvxToggleForm(abrir) {
    var card = document.getElementById('lvxFormCard');
    var form = document.getElementById('lvxForm');
    if (!card || !form) return;
    var abre = typeof abrir === 'boolean' ? abrir : card.style.display === 'none';
    card.style.display = abre ? '' : 'none';
    if (abre) {
        var mov = document.getElementById('lvxMovCard');
        if (mov) { mov.style.display = 'none'; }
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        var nombre = document.getElementById('lvxFormNombre');
        if (nombre) { nombre.focus(); }
    } else {
        form.reset();
        document.getElementById('lvxFormId').value = '';
        document.getElementById('lvxFormTitulo').textContent = 'Nuevo blanco';
        document.getElementById('lvxFormStockWrap').style.display = '';
    }
}

function lvxEditar(b) {
    lvxToggleForm(true);
    document.getElementById('lvxFormId').value = b.id || '';
    document.getElementById('lvxFormNombre').value = b.nombre || '';
    document.getElementById('lvxFormCategoria').value = b.categoria || 'otro';
    document.getElementById('lvxFormMinimo').value = b.stock_minimo || '';
    document.getElementById('lvxFormNotas').value = b.notas || '';
    document.getElementById('lvxFormTitulo').textContent = 'Editar blanco';
    // El stock solo se mueve por movimientos: en edicion se oculta el inicial.
    document.getElementById('lvxFormStockWrap').style.display = 'none';
}

function lvxToggleMov(abrir) {
    var card = document.getElementById('lvxMovCard');
    if (!card) return;
    var abre = typeof abrir === 'boolean' ? abrir : card.style.display === 'none';
    card.style.display = abre ? '' : 'none';
    if (abre) {
        var form = document.getElementById('lvxFormCard');
        if (form) { form.style.display = 'none'; }
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function lvxMovPrefill(blancoId, tipo) {
    lvxToggleMov(true);
    var sel = document.getElementById('lvxMovBlanco');
    var tip = document.getElementById('lvxMovTipo');
    var cant = document.getElementById('lvxMovCantidad');
    if (sel) { sel.value = String(blancoId); }
    if (tip) { tip.value = tipo || 'uso'; }
    if (cant) { cant.focus(); }
}
</script>
