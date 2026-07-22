<?php
/**
 * Impacto de una tarifa sobre reservaciones existentes.
 * Muestra las futuras afectadas (precio actual -> nuevo, saldo resultante)
 * y deja confirmar el recalculo. Nada se escribe sin confirmar aqui.
 */
$afectadas = $impacto['afectadas'] ?? [];
$omitidas = $impacto['omitidas'] ?? [];
$aplicable = !empty($impacto['aplicable']);
$tarifa = $impacto['tarifa'] ?? $incremento;

$esPermanente = !empty($tarifa['es_permanente']);
$vigencia = format_date($tarifa['fecha_inicio'], 'd/m/Y') . ' — ' . ($esPermanente || empty($tarifa['fecha_fin']) ? 'Permanente' : format_date($tarifa['fecha_fin'], 'd/m/Y'));
$signoValor = ($tarifa['clase'] ?? 'incremento') === 'descuento' ? '−' : '+';
$valorTxt = $tarifa['tipo_incremento'] === 'porcentaje'
    ? $signoValor . rtrim(rtrim(number_format((float)$tarifa['valor_incremento'], 2), '0'), '.') . '%'
    : $signoValor . format_currency($tarifa['valor_incremento']);
$alcanceTxt = ['global' => 'Todo el hotel', 'tipo_habitacion' => 'Por tipo de habitación', 'habitacion' => 'Habitaciones específicas'][$tarifa['alcance']] ?? $tarifa['alcance'];
$estadoTxt = ['confirmada' => 'Confirmada', 'checked_in' => 'En casa'];
?>
<style>
.tim-page { max-width: 1080px; margin: 0 auto; padding: 1.25rem 1rem 5.5rem; --tim-ink: #1F2937; --tim-muted: #6B7280; --tim-line: #E5E7EB; --tim-surface: #FFFFFF; --tim-soft: #F5F5F7; --tim-soft-2: #FAFAFC; color: var(--tim-ink); }
html[data-theme="dark"] .tim-page { --tim-ink: #F5F5F7; --tim-muted: #98989F; --tim-line: #38383A; --tim-surface: var(--brand-surface, #1C1C1E); --tim-soft: #161617; --tim-soft-2: #1C1C1E; }
.tim-head { display: flex; align-items: flex-start; gap: .85rem; margin-bottom: 1.1rem; flex-wrap: wrap; }
.tim-head h1 { font-size: 1.35rem; font-weight: 700; margin: 0 0 .15rem; }
.tim-head p { margin: 0; color: var(--tim-muted); font-size: .9rem; max-width: 60ch; }
.tim-card { background: var(--tim-surface); border: 1px solid var(--tim-line); border-radius: 16px; padding: 1.1rem 1.2rem; margin-bottom: 1rem; }
.tim-chips { display: flex; flex-wrap: wrap; gap: .5rem .65rem; }
.tim-chip { display: inline-flex; align-items: center; gap: .4rem; background: var(--tim-soft); border: 1px solid var(--tim-line); border-radius: 999px; padding: .3rem .75rem; font-size: .82rem; color: var(--tim-ink); }
.tim-chip i { color: var(--brand-primary, #6B7280); font-size: .75rem; }
.tim-chip strong { font-weight: 600; }
.tim-note { display: flex; gap: .6rem; align-items: flex-start; background: var(--tim-soft-2); border: 1px solid var(--tim-line); border-radius: 12px; padding: .7rem .85rem; font-size: .84rem; color: var(--tim-muted); margin-top: .85rem; }
.tim-note i { margin-top: .15rem; color: var(--brand-primary, #6B7280); }
.tim-table-wrap { overflow-x: auto; border: 1px solid var(--tim-line); border-radius: 14px; background: var(--tim-surface); }
.tim-table { width: 100%; border-collapse: collapse; font-size: .86rem; min-width: 860px; }
.tim-table th { text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: var(--tim-muted); font-weight: 600; padding: .7rem .8rem; border-bottom: 1px solid var(--tim-line); background: var(--tim-soft-2); white-space: nowrap; }
.tim-table td { padding: .75rem .8rem; border-bottom: 1px solid var(--tim-line); vertical-align: top; }
.tim-table tbody tr:last-child td { border-bottom: none; }
.tim-table td.tim-num, .tim-table th.tim-num { text-align: right; white-space: nowrap; }
.tim-res-id { font-weight: 700; }
.tim-res-guest { color: var(--tim-muted); font-size: .8rem; }
.tim-estado { display: inline-block; font-size: .68rem; font-weight: 600; border-radius: 999px; padding: .1rem .5rem; margin-top: .2rem; background: var(--tim-soft); border: 1px solid var(--tim-line); color: var(--tim-muted); }
.tim-estado.checked_in { background: rgba(16,185,129,.12); border-color: rgba(16,185,129,.3); color: #047857; }
html[data-theme="dark"] .tim-estado.checked_in { color: #34D399; }
.tim-hab { white-space: nowrap; font-size: .8rem; color: var(--tim-muted); }
.tim-hab strong { color: var(--tim-ink); font-weight: 600; }
.tim-precio-nuevo { font-weight: 700; }
.tim-delta { font-size: .78rem; font-weight: 600; white-space: nowrap; }
.tim-delta.sube { color: #B45309; }
.tim-delta.baja { color: #047857; }
html[data-theme="dark"] .tim-delta.sube { color: #FBBF24; }
html[data-theme="dark"] .tim-delta.baja { color: #34D399; }
.tim-flag { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 600; border-radius: 8px; padding: .18rem .5rem; margin: .15rem .25rem 0 0; background: rgba(217,119,6,.1); border: 1px solid rgba(217,119,6,.3); color: #92400E; }
html[data-theme="dark"] .tim-flag { color: #FBBF24; }
.tim-empty { text-align: center; padding: 2.5rem 1rem; color: var(--tim-muted); }
.tim-empty i { font-size: 1.6rem; margin-bottom: .5rem; display: block; color: var(--brand-primary, #9CA3AF); }
.tim-footer { position: sticky; bottom: 0; z-index: 5; margin-top: 1rem; background: var(--tim-surface); border: 1px solid var(--tim-line); border-radius: 14px; padding: .8rem 1rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; box-shadow: 0 -6px 24px rgba(0,0,0,.06); }
.tim-footer .tim-resumen { font-size: .86rem; color: var(--tim-muted); }
.tim-footer .tim-resumen strong { color: var(--tim-ink); }
.tim-footer .tim-acciones { margin-left: auto; display: flex; gap: .6rem; flex-wrap: wrap; }
.tim-btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .55rem 1rem; font-size: .86rem; font-weight: 600; border: 1px solid var(--tim-line); background: var(--tim-surface); color: var(--tim-ink); cursor: pointer; text-decoration: none; transition: transform .15s ease, box-shadow .15s ease; }
.tim-btn:hover { transform: translateY(-1px); }
.tim-btn-primary { background: var(--brand-primary, #1F2937); border-color: var(--brand-primary, #1F2937); color: var(--brand-primary-contrast, #fff); }
.tim-btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }
.tim-check { width: 1.05rem; height: 1.05rem; accent-color: var(--brand-primary, #1F2937); cursor: pointer; }
.tim-omitidas { font-size: .82rem; color: var(--tim-muted); }
.tim-omitidas li { margin: .2rem 0; }
@media (max-width: 640px) { .tim-page { padding-inline: .6rem; } .tim-card { padding: .9rem .85rem; } }
</style>

<div class="tim-page">
    <div class="tim-head">
        <?php $back_arrow_href = back_url('configuracion/tarifas'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <div>
            <h1>Impacto en reservaciones existentes</h1>
            <p>Las reservaciones ya creadas conservan su precio congelado. Aquí ves cuáles cambiarían con la tarifa
                «<?= htmlspecialchars($tarifa['nombre']) ?>» y confirmas el recálculo — nada se modifica sin tu confirmación.</p>
        </div>
    </div>

    <div class="tim-card">
        <div class="tim-chips">
            <span class="tim-chip"><i class="fas fa-tag"></i> <strong><?= htmlspecialchars($tarifa['nombre']) ?></strong></span>
            <span class="tim-chip"><i class="fas fa-arrow-trend-up"></i> <?= $valorTxt ?></span>
            <span class="tim-chip"><i class="fas fa-calendar"></i> <?= $vigencia ?></span>
            <span class="tim-chip"><i class="fas fa-crosshairs"></i> <?= $alcanceTxt ?></span>
        </div>
        <div class="tim-note">
            <i class="fas fa-info-circle"></i>
            <span>El precio nuevo se re-deriva del motor de tarifas vigente (todas las tarifas activas, noche por noche, desde el
            precio base actual de cada habitación). Las cortesías siguen en $0 y el descuento existente de cada reservación se conserva.
            Solo se consideran reservaciones confirmadas o en casa con noches por venir; las canceladas y con check-out no se tocan.</span>
        </div>
    </div>

    <?php if (!$aplicable): ?>
        <div class="tim-card">
            <div class="tim-empty">
                <i class="fas fa-circle-info"></i>
                <p><?= htmlspecialchars($impacto['motivo'] ?? 'El recálculo no aplica a esta tarifa.') ?></p>
                <a href="<?= url('configuracion/tarifas') ?>" class="tim-btn" style="margin-top:1rem;">
                    <i class="fas fa-arrow-left"></i> Volver a tarifas
                </a>
            </div>
        </div>
    <?php elseif (empty($afectadas)): ?>
        <div class="tim-card">
            <div class="tim-empty">
                <i class="fas fa-circle-check"></i>
                <p><strong>Ninguna reservación futura cambia de precio con esta tarifa.</strong></p>
                <p style="font-size:.85rem; margin-top:.3rem;">Las nuevas reservaciones que se creen dentro de la vigencia ya la tomarán en cuenta automáticamente.</p>
                <a href="<?= url('configuracion/tarifas') ?>" class="tim-btn" style="margin-top:1rem;">
                    <i class="fas fa-arrow-left"></i> Volver a tarifas
                </a>
            </div>
        </div>
        <?php if (!empty($omitidas)): ?>
        <div class="tim-card">
            <p style="font-weight:600; margin:0 0 .4rem;">Omitidas (revísalas a mano)</p>
            <ul class="tim-omitidas">
                <?php foreach ($omitidas as $om): ?>
                <li>Reservación #<?= (int)$om['id'] ?> — <?= htmlspecialchars($om['motivo']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    <?php else: ?>

    <form action="<?= url('configuracion/tarifas/impacto/' . (int)$tarifa['id'] . '/aplicar') ?>" method="POST" id="formImpacto"
          data-no-draft data-no-unsaved-warning data-ms-no-summary="1">
        <?= csrf_field() ?>

        <div class="tim-table-wrap">
            <table class="tim-table">
                <thead>
                    <tr>
                        <th style="width:2.2rem;"><input type="checkbox" class="tim-check" id="timTodas" checked aria-label="Seleccionar todas"></th>
                        <th>Reservación</th>
                        <th>Estancia</th>
                        <th>Habitaciones</th>
                        <th class="tim-num">Precio actual</th>
                        <th class="tim-num">Precio nuevo</th>
                        <th class="tim-num">Pagado</th>
                        <th class="tim-num">Saldo resultante</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($afectadas as $af): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="tim-check tim-sel" name="reservaciones[]" value="<?= (int)$af['id'] ?>"
                                   data-delta="<?= htmlspecialchars(number_format((float)$af['delta'], 2, '.', '')) ?>" checked>
                        </td>
                        <td>
                            <div class="tim-res-id">#<?= (int)$af['id'] ?></div>
                            <div class="tim-res-guest"><?= htmlspecialchars($af['huesped'] ?? 'Sin huésped') ?></div>
                            <span class="tim-estado <?= htmlspecialchars($af['estado']) ?>"><?= $estadoTxt[$af['estado']] ?? htmlspecialchars($af['estado']) ?></span>
                        </td>
                        <td style="white-space:nowrap;">
                            <?= format_date($af['fecha_entrada'], 'd/m/Y') ?> → <?= format_date($af['fecha_salida'], 'd/m/Y') ?>
                            <div class="tim-res-guest"><?= (int)$af['noches'] ?> <?= (int)$af['noches'] === 1 ? 'noche' : 'noches' ?></div>
                        </td>
                        <td>
                            <?php foreach ($af['habitaciones'] as $hab): ?>
                                <div class="tim-hab">
                                    <strong><?= htmlspecialchars($hab['numero']) ?></strong>
                                    <?php if (!empty($hab['es_cortesia'])): ?>
                                        · Cortesía ($0)
                                    <?php else: ?>
                                        · <?= format_currency($hab['precio_actual']) ?> → <?= format_currency($hab['precio_nuevo']) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if ((float)$af['descuento_total'] > 0): ?>
                                <div class="tim-hab">Descuento (se conserva): −<?= format_currency($af['descuento_aplicado']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="tim-num"><?= format_currency($af['precio_actual']) ?></td>
                        <td class="tim-num">
                            <span class="tim-precio-nuevo"><?= format_currency($af['precio_nuevo']) ?></span>
                            <div class="tim-delta <?= $af['delta'] >= 0 ? 'sube' : 'baja' ?>">
                                <?= $af['delta'] >= 0 ? '+' : '−' ?><?= format_currency(abs($af['delta'])) ?>
                            </div>
                            <?php if (empty($af['cuadra_detalle'])): ?>
                                <span class="tim-flag" title="El total actual (<?= format_currency($af['precio_actual']) ?>) no cuadra con su detalle por habitación (<?= format_currency($af['total_segun_detalle']) ?>). Al aplicar, total y detalle quedan sincronizados con las noches reales.">
                                    <i class="fas fa-triangle-exclamation"></i> Detalle desincronizado
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($af['descuento_topado'])): ?>
                                <span class="tim-flag" title="El descuento existente supera el nuevo subtotal; se topa para que el total no quede negativo.">
                                    <i class="fas fa-scissors"></i> Descuento topado
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="tim-num"><?= format_currency($af['pagado']) ?></td>
                        <td class="tim-num">
                            <?= format_currency($af['saldo_actual']) ?> → <strong><?= format_currency($af['saldo_nuevo']) ?></strong>
                            <?php if (!empty($af['sobrepago'])): ?>
                                <div><span class="tim-flag" title="Lo ya pagado supera el precio nuevo; quedará un sobrepago por resolver en caja.">
                                    <i class="fas fa-hand-holding-dollar"></i> Sobrepago
                                </span></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($omitidas)): ?>
        <div class="tim-card" style="margin-top:1rem;">
            <p style="font-weight:600; margin:0 0 .4rem;"><i class="fas fa-eye-slash" style="color:var(--tim-muted);"></i> Omitidas (revísalas a mano)</p>
            <ul class="tim-omitidas">
                <?php foreach ($omitidas as $om): ?>
                <li>Reservación #<?= (int)$om['id'] ?> — <?= htmlspecialchars($om['motivo']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="tim-footer">
            <div class="tim-resumen">
                <strong id="timCuenta"><?= count($afectadas) ?></strong> de <?= count($afectadas) ?> seleccionadas ·
                diferencia total: <strong id="timDelta"></strong>
            </div>
            <div class="tim-acciones">
                <a href="<?= url('configuracion/tarifas') ?>" class="tim-btn">Omitir por ahora</a>
                <button type="submit" class="tim-btn tim-btn-primary" id="timAplicar">
                    <i class="fas fa-rotate"></i> Aplicar recálculo
                </button>
            </div>
        </div>
    </form>

    <?php endif; ?>
</div>

<?php if ($aplicable && !empty($afectadas)): ?>
<script>
(function () {
    var form = document.getElementById('formImpacto');
    var todas = document.getElementById('timTodas');
    var boton = document.getElementById('timAplicar');
    var confirmado = false;

    function seleccionadas() {
        return Array.prototype.slice.call(form.querySelectorAll('.tim-sel:checked'));
    }

    function formatoDinero(n) {
        var abs = Math.abs(n);
        return (n < 0 ? '−' : '+') + '$' + abs.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function actualizarResumen() {
        var sel = seleccionadas();
        var delta = sel.reduce(function (acc, chk) { return acc + (parseFloat(chk.getAttribute('data-delta')) || 0); }, 0);
        document.getElementById('timCuenta').textContent = sel.length;
        document.getElementById('timDelta').textContent = formatoDinero(delta);
        boton.disabled = sel.length === 0;
        if (todas) {
            var total = form.querySelectorAll('.tim-sel').length;
            todas.checked = sel.length === total;
            todas.indeterminate = sel.length > 0 && sel.length < total;
        }
    }

    if (todas) {
        todas.addEventListener('change', function () {
            form.querySelectorAll('.tim-sel').forEach(function (chk) { chk.checked = todas.checked; });
            actualizarResumen();
        });
    }
    form.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('tim-sel')) { actualizarResumen(); }
    });

    form.addEventListener('submit', function (e) {
        if (confirmado) { return; }
        e.preventDefault();
        var n = seleccionadas().length;
        if (n === 0) { return; }
        var msj = '¿Recalcular el precio de ' + n + (n === 1 ? ' reservación' : ' reservaciones') + '? El total y su detalle por habitación se actualizarán con la tarifa vigente.';
        var proceder = function () { confirmado = true; if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); } };
        if (window.msConfirm) {
            msConfirm({
                type: 'warning',
                icon: 'alert',
                title: '¿Aplicar recálculo?',
                msg: msj,
                confirmLabel: 'Sí, recalcular'
            }).then(function (ok) { if (ok) { proceder(); } });
        } else if (window.confirm(msj)) {
            proceder();
        }
    });

    actualizarResumen();
})();
</script>
<?php endif; ?>
