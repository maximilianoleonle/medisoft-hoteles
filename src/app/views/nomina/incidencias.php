<?php
/**
 * Nomina core - incidencias del periodo (Fase 3).
 */
$inLista = is_array($incidencias ?? null) ? $incidencias : [];
$inFiltros = is_array($filtros ?? null) ? $filtros : [];
$inConceptos = is_array($conceptos ?? null) ? $conceptos : [];
$inTrabajadores = is_array($trabajadores ?? null) ? $trabajadores : [];
$inPuedeCapturar = !empty($puedeCapturar);
$inPuedeRegistrar = $inPuedeCapturar && $inConceptos !== [] && $inTrabajadores !== [];

$back_arrow_href = url('nomina');
include APP_PATH . '/views/partials/back_arrow.php';
?>
<style>
.nomina-inc-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F5F5F7);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text); max-width: 1120px; margin: 0 auto; padding: 4px 4px 40px;
}
.nomina-inc-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-inc-page .nom-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 28px; margin: 2px 0 14px; font-weight: 600; }
.nomina-inc-page .inc-card { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; padding: 18px 20px; margin-bottom: 16px; }
.nomina-inc-page .inc-card h2 { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 19px; margin: 0 0 12px; font-weight: 600; }
.nomina-inc-page .inc-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
.nomina-inc-page .inc-field { display: flex; flex-direction: column; gap: 4px; min-width: 140px; flex: 1 1 160px; }
.nomina-inc-page .inc-field label { font-size: 11.5px; font-weight: 700; color: var(--nom-muted); }
.nomina-inc-page .inc-help { margin: -4px 0 12px; font-size: 12.5px; color: var(--nom-muted); }
.nomina-inc-page .inc-alert {
    border: 1px solid rgba(191,144,0,.28); background: rgba(191,144,0,.08); color: #7a5c00;
    border-radius: 12px; padding: 10px 12px; margin-bottom: 12px; font-size: 13px;
}
.nomina-inc-page .inc-alert a { color: inherit; font-weight: 700; text-decoration: underline; }
.nomina-inc-page .inc-field-hint { font-size: 11px; color: var(--nom-muted); line-height: 1.3; }
.nomina-inc-page .inc-guia {
    margin: 12px 0 0; padding: 9px 12px; border-radius: 10px; font-size: 12.5px; line-height: 1.45;
    background: color-mix(in srgb, var(--nom-gold) 9%, #ffffff); border: 1px solid color-mix(in srgb, var(--nom-gold) 30%, #ffffff);
    color: color-mix(in srgb, var(--nom-gold) 72%, #000);
}
.nomina-inc-page .inc-field input, .nomina-inc-page .inc-field select {
    border: 1px solid var(--nom-border); border-radius: 10px; padding: 9px 11px; font-size: 16px; background: #fff; color: var(--nom-text); width: 100%;
}
.nomina-inc-page .inc-btn {
    display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--nom-brand); border-radius: 11px;
    padding: 9px 15px; font-size: 13px; font-weight: 600; cursor: pointer; background: var(--nom-brand); color: #fff;
}
.nomina-inc-page .inc-btn:disabled { opacity: .55; cursor: not-allowed; }
.nomina-inc-page .inc-btn-sec { background: var(--nom-card); border-color: var(--nom-border); color: var(--nom-text); }
.nomina-inc-page table.inc-tabla { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.nomina-inc-page table.inc-tabla th { text-align: left; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--nom-muted); padding: 9px 10px; border-bottom: 1px solid var(--nom-border); }
.nomina-inc-page table.inc-tabla td { padding: 9px 10px; border-bottom: 1px dashed var(--nom-border); }
.nomina-inc-page .inc-badge { display: inline-block; font-size: 11.5px; font-weight: 700; border-radius: 999px; padding: 2px 10px; }
.nomina-inc-page .inc-badge.aprobada { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-inc-page .inc-badge.pendiente { background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-inc-page .inc-badge.rechazada { background: rgba(198,40,40,.10); color: #c62828; }
.nomina-inc-page .inc-badge.percepcion { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-inc-page .inc-badge.deduccion { background: rgba(198,40,40,.10); color: #c62828; }
.nomina-inc-page .inc-vacio { text-align: center; padding: 30px 16px; color: var(--nom-muted); }
.nomina-inc-page .inc-vacio i { font-size: 24px; color: var(--nom-gold); display: block; margin-bottom: 8px; }
@media (max-width: 768px) {
    .nomina-inc-page table.inc-tabla thead { display: none; }
    .nomina-inc-page table.inc-tabla tr { display: block; border: 1px solid var(--nom-border); border-radius: 12px; margin: 10px 0; padding: 8px; }
    .nomina-inc-page table.inc-tabla td { display: block; border: 0; padding: 5px 8px; }
}
</style>

<div class="nomina-inc-page">
    <p class="nom-kicker">Nómina</p>
    <h1 class="nom-title">Incidencias</h1>

    <?php $subnav_section = 'nomina'; $subnav_active = 'incidencias'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <?php if ($inPuedeCapturar): ?>
    <div class="inc-card">
        <h2><i class="fas fa-plus" style="color:var(--nom-gold)"></i> Nueva incidencia</h2>
        <?php if ($inConceptos === []): ?>
        <div class="inc-alert">
            No hay conceptos activos de nomina. Abre <a href="<?= url('nomina/catalogos?tipo=conceptos') ?>">Catalogos &gt; Conceptos</a>
            y activa o crea el concepto que vas a usar.
        </div>
        <?php elseif ($inTrabajadores === []): ?>
        <div class="inc-alert">
            No hay empleados activos para registrar incidencias.
        </div>
        <?php else: ?>
        <p class="inc-help">El concepto define si la incidencia suma o descuenta en nomina: bono, descuento, horas extra, ajuste, etc.</p>
        <?php endif; ?>
        <form method="POST" action="<?= url('nomina/incidencias') ?>" class="inc-form">
            <?= csrf_field() ?>
            <div class="inc-field" style="flex:2 1 200px;">
                <label>Empleado *</label>
                <select name="trabajador_id" required <?= $inTrabajadores === [] ? 'disabled' : '' ?>>
                    <option value=""><?= $inTrabajadores === [] ? 'Sin empleados activos' : '-- Selecciona --' ?></option>
                    <?php foreach ($inTrabajadores as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['nombre_completo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="inc-field" style="flex:2 1 200px;">
                <label>Concepto *</label>
                <select name="concepto_id" required <?= $inConceptos === [] ? 'disabled' : '' ?>
                        oninvalid="this.setCustomValidity('Selecciona el concepto de la incidencia.')"
                        onchange="this.setCustomValidity('')">
                    <option value=""><?= $inConceptos === [] ? 'Sin conceptos activos' : '-- Selecciona --' ?></option>
                    <?php foreach ($inConceptos as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"
                            data-modo="<?= htmlspecialchars((string) ($c['modo_calculo'] ?? 'manual')) ?>"
                            data-default="<?= $c['monto_default'] !== null ? htmlspecialchars((string) $c['monto_default']) : '' ?>"
                            data-tipo="<?= htmlspecialchars((string) ($c['tipo'] ?? '')) ?>">
                        <?= htmlspecialchars($c['nombre']) ?> (<?= $c['tipo'] === 'percepcion' ? '+' : '−' ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="inc-field">
                <label>Fecha *</label>
                <input type="date" name="fecha" required value="<?= date('Y-m-d') ?>">
                <small class="inc-field-hint">Cuenta en el periodo que incluya este d&iacute;a.</small>
            </div>
            <div class="inc-field" style="flex:0 1 110px; min-width:100px;">
                <label>Cantidad</label>
                <input type="number" name="cantidad" id="inc-cantidad" min="0.01" step="0.01" placeholder="hrs/uds">
                <small class="inc-field-hint">Solo horas o piezas.</small>
            </div>
            <div class="inc-field" style="flex:0 1 130px; min-width:110px;">
                <label>Monto</label>
                <input type="number" name="monto" id="inc-monto" min="0" step="0.01" placeholder="$">
                <small class="inc-field-hint" id="inc-monto-hint">En pesos.</small>
            </div>
            <div class="inc-field" style="flex:2 1 180px;">
                <label>Descripción</label>
                <input type="text" name="descripcion" maxlength="200" placeholder="Ej. cubrió turno del sábado">
            </div>
            <div class="inc-field" style="flex:0 0 auto;">
                <button type="submit" class="inc-btn ms-pressable" <?= $inPuedeRegistrar ? '' : 'disabled' ?>><i class="fas fa-check"></i> Registrar</button>
            </div>
        </form>
        <p class="inc-guia" id="inc-guia" hidden></p>
        <script>
        (function () {
            var sel = document.querySelector('.nomina-inc-page select[name="concepto_id"]');
            var guia = document.getElementById('inc-guia');
            var cantidad = document.getElementById('inc-cantidad');
            var monto = document.getElementById('inc-monto');
            if (!sel || !guia) { return; }

            function pesos(v) {
                var n = parseFloat(v);
                return isNaN(n) ? '' : '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2 });
            }

            sel.addEventListener('change', function () {
                var op = sel.options[sel.selectedIndex];
                if (!op || !op.value) { guia.hidden = true; return; }
                var modo = op.getAttribute('data-modo') || 'manual';
                var def = op.getAttribute('data-default') || '';
                var suma = (op.getAttribute('data-tipo') === 'percepcion');
                var texto = '';

                if (modo === 'por_cantidad') {
                    texto = 'Este concepto se paga POR CANTIDAD: captura las horas o piezas en "Cantidad". ';
                    texto += def !== ''
                        ? 'El precio por unidad ya está en el catálogo (' + pesos(def) + '); deja "Monto" vacío o ponlo solo si esta vez vale distinto.'
                        : 'En "Monto" pon cuánto vale cada hora o pieza.';
                    cantidad.placeholder = 'hrs/uds';
                    monto.placeholder = def !== '' ? pesos(def) + ' c/u' : '$ por unidad';
                } else {
                    texto = 'Este concepto es de MONTO DIRECTO: deja "Cantidad" vacío. ';
                    texto += def !== ''
                        ? 'Si dejas "Monto" vacío se usa el del catálogo (' + pesos(def) + ').'
                        : 'Captura el monto total en "Monto".';
                    cantidad.placeholder = 'no aplica';
                    monto.placeholder = def !== '' ? pesos(def) : '$';
                }
                texto += suma ? ' Le SUMA al pago.' : ' Le DESCUENTA al pago.';
                guia.textContent = texto;
                guia.hidden = false;
            });
        })();
        </script>
    </div>
    <?php endif; ?>

    <?php if ($inPuedeCapturar): ?>
    <form method="POST" action="<?= url('nomina/incidencias/proponer') ?>" class="inc-form" style="margin-bottom:14px;">
        <?= csrf_field() ?>
        <input type="hidden" name="desde" value="<?= htmlspecialchars((string) ($inFiltros['desde'] ?? date('Y-m-01'))) ?>">
        <input type="hidden" name="hasta" value="<?= htmlspecialchars((string) ($inFiltros['hasta'] ?? date('Y-m-d'))) ?>">
        <button type="submit" class="inc-btn inc-btn-sec ms-pressable">
            <i class="fas fa-wand-magic-sparkles"></i> Sugerir incidencias según la actividad del hotel
        </button>
        <span style="font-size:12px; color:var(--nom-muted); align-self:center;">
            Usa el rango de los filtros; las propuestas quedan pendientes de tu aprobación.
        </span>
    </form>
    <?php endif; ?>

    <?php include APP_PATH . '/views/partials/filtros.php'; ?>
    <form method="GET" action="<?= url('nomina/incidencias') ?>" class="msf-bar" data-auto-filter-form>
        <label class="msf-field msf-field--grow">
            <span class="msf-label">Trabajador</span>
            <select class="msf-control" name="trabajador_id"><?= msf_worker_options($inTrabajadores, (int) ($inFiltros['trabajador_id'] ?? 0)) ?></select>
        </label>
        <label class="msf-field msf-field--sm">
            <span class="msf-label">Desde</span>
            <input class="msf-control" type="date" name="desde" value="<?= htmlspecialchars((string) ($inFiltros['desde'] ?? '')) ?>">
        </label>
        <label class="msf-field msf-field--sm">
            <span class="msf-label">Hasta</span>
            <input class="msf-control" type="date" name="hasta" value="<?= htmlspecialchars((string) ($inFiltros['hasta'] ?? '')) ?>">
        </label>
        <label class="msf-field msf-field--sm">
            <span class="msf-label">Estado</span>
            <select class="msf-control" name="estado">
                <option value="">Todos</option>
                <?php foreach (['aprobada', 'pendiente', 'rechazada'] as $es): ?>
                <option value="<?= $es ?>" <?= ($inFiltros['estado'] ?? '') === $es ? 'selected' : '' ?>><?= ucfirst($es) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="msf-ranges" data-msf-from="desde" data-msf-to="hasta">
            <span class="msf-ranges-label">Rango</span>
            <button type="button" class="msf-chip" data-msf-range="hoy">Hoy</button>
            <button type="button" class="msf-chip" data-msf-range="7d">7 días</button>
            <button type="button" class="msf-chip" data-msf-range="mes">Este mes</button>
            <button type="button" class="msf-chip" data-msf-range="mes-pasado">Mes pasado</button>
        </div>
    </form>

    <div class="inc-card">
        <?php if ($inLista === []): ?>
        <div class="inc-vacio"><i class="fas fa-clipboard-list"></i> Sin incidencias entre el
            <strong><?= htmlspecialchars((string) ($inFiltros['desde'] ?? '')) ?></strong> y el
            <strong><?= htmlspecialchars((string) ($inFiltros['hasta'] ?? '')) ?></strong>.<br>
            <span style="font-size:12px;">Solo se muestran las que caen en ese rango: si registraste una con otra fecha, ajusta los filtros "Desde/Hasta" de arriba.</span>
        </div>
        <?php else: ?>
        <table class="inc-tabla">
            <thead>
                <tr><th>Fecha</th><th>Empleado</th><th>Concepto</th><th>Cant.</th><th>Monto</th><th>Estado</th><th>Origen</th><?php if ($inPuedeCapturar): ?><th></th><?php endif; ?></tr>
            </thead>
            <tbody>
                <?php foreach ($inLista as $i): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $i['fecha']) ?></td>
                    <td><strong><?= htmlspecialchars((string) $i['trabajador_nombre']) ?></strong></td>
                    <td>
                        <span class="inc-badge <?= htmlspecialchars((string) $i['concepto_tipo']) ?>"><?= $i['concepto_tipo'] === 'percepcion' ? '+' : '−' ?></span>
                        <?= htmlspecialchars((string) $i['concepto_nombre']) ?>
                        <?= !empty($i['descripcion']) ? '<br><small style="color:var(--nom-muted)">' . htmlspecialchars((string) $i['descripcion']) . '</small>' : '' ?>
                    </td>
                    <td><?= $i['cantidad'] !== null ? number_format((float) $i['cantidad'], 2) : '—' ?></td>
                    <td><?= $i['monto'] !== null ? '$' . number_format((float) $i['monto'], 2) : '—' ?></td>
                    <td><span class="inc-badge <?= htmlspecialchars((string) $i['estado']) ?>"><?= htmlspecialchars(ucfirst((string) $i['estado'])) ?></span></td>
                    <td><?= htmlspecialchars((string) $i['origen']) ?></td>
                    <?php if ($inPuedeCapturar): ?>
                    <td>
                        <?php if ($i['estado'] === 'pendiente'): ?>
                        <form method="POST" action="<?= url('nomina/incidencias/' . (int) $i['id'] . '/estado') ?>" style="display:inline-block; margin-bottom:4px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="estado" value="aprobada">
                            <button type="submit" class="inc-btn ms-pressable" style="padding:5px 10px; font-size:12px;">Aprobar</button>
                        </form>
                        <?php endif; ?>
                        <?php if ($i['estado'] !== 'rechazada'): ?>
                        <form method="POST" action="<?= url('nomina/incidencias/' . (int) $i['id'] . '/estado') ?>"
                              data-ms-confirm data-ms-type="warning" data-ms-icon="trash"
                              data-ms-title="Rechazar incidencia"
                              data-ms-msg="La incidencia dejara de contar para la nomina. ¿Continuar?"
                              data-ms-ok="Rechazar">
                            <?= csrf_field() ?>
                            <input type="hidden" name="estado" value="rechazada">
                            <button type="submit" class="inc-btn inc-btn-sec ms-pressable" style="padding:5px 10px; font-size:12px;">Rechazar</button>
                        </form>
                        <?php elseif ($i['estado'] === 'rechazada'): ?>
                        <form method="POST" action="<?= url('nomina/incidencias/' . (int) $i['id'] . '/estado') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="estado" value="aprobada">
                            <button type="submit" class="inc-btn inc-btn-sec ms-pressable" style="padding:5px 10px; font-size:12px;">Reaprobar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
