<?php
/**
 * Nomina core - periodos por grupo de pago (Fase 3).
 * Formulario de previsualizacion + historial de periodos (v1 y v2).
 */
$peGrupos = is_array($grupos ?? null) ? $grupos : [];
$peSugerencias = is_array($sugerencias ?? null) ? $sugerencias : [];
$pePeriodos = is_array($periodos ?? null) ? $periodos : [];
$pePuedeCalcular = !empty($puedeCalcular);

$peEstados = [
    'cerrado' => ['t' => 'Cerrado', 'c' => 'warn'],
    'aprobado' => ['t' => 'Aprobado', 'c' => 'ok'],
    'anulado' => ['t' => 'Anulado', 'c' => 'danger'],
];

$back_arrow_href = url('nomina');
include APP_PATH . '/views/partials/back_arrow.php';
?>
<style>
.nomina-per-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F5F5F7);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text); max-width: 1120px; margin: 0 auto; padding: 4px 4px 40px;
}
.nomina-per-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-per-page .nom-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 28px; margin: 2px 0 14px; font-weight: 600; }
.nomina-per-page .per-card { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; padding: 18px 20px; margin-bottom: 16px; }
.nomina-per-page .per-card h2 { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 19px; margin: 0 0 12px; font-weight: 600; }
.nomina-per-page .per-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
.nomina-per-page .per-field { display: flex; flex-direction: column; gap: 4px; min-width: 150px; flex: 1 1 170px; }
.nomina-per-page .per-field label { font-size: 11.5px; font-weight: 700; color: var(--nom-muted); }
.nomina-per-page .per-field input, .nomina-per-page .per-field select {
    border: 1px solid var(--nom-border); border-radius: 10px; padding: 9px 11px; font-size: 16px; background: #fff; color: var(--nom-text); width: 100%;
}
.nomina-per-page .per-btn {
    display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--nom-brand); border-radius: 11px;
    padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer; background: var(--nom-brand); color: #fff; text-decoration: none;
}
.nomina-per-page table.per-tabla { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.nomina-per-page table.per-tabla th { text-align: left; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--nom-muted); padding: 9px 10px; border-bottom: 1px solid var(--nom-border); }
.nomina-per-page table.per-tabla td { padding: 9px 10px; border-bottom: 1px dashed var(--nom-border); }
.nomina-per-page table.per-tabla tr[data-easy-href] { cursor: pointer; }
.nomina-per-page table.per-tabla tr[data-easy-href]:hover td { background: color-mix(in srgb, var(--nom-gold) 6%, transparent); }
.nomina-per-page .per-badge { display: inline-block; font-size: 11.5px; font-weight: 700; border-radius: 999px; padding: 2px 10px; }
.nomina-per-page .per-badge.ok { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-per-page .per-badge.warn { background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-per-page .per-badge.danger { background: rgba(198,40,40,.10); color: #c62828; }
.nomina-per-page .per-badge.motor { background: color-mix(in srgb, var(--nom-brand) 10%, transparent); color: var(--nom-brand); }
.nomina-per-page .per-vacio { text-align: center; padding: 30px 16px; color: var(--nom-muted); }
.nomina-per-page .per-vacio i { font-size: 24px; color: var(--nom-gold); display: block; margin-bottom: 8px; }
.nomina-per-page .per-nota { font-size: 12.5px; color: var(--nom-muted); margin-top: 8px; }
@media (max-width: 768px) {
    .nomina-per-page table.per-tabla thead { display: none; }
    .nomina-per-page table.per-tabla tr { display: block; border: 1px solid var(--nom-border); border-radius: 12px; margin: 10px 0; padding: 8px; }
    .nomina-per-page table.per-tabla td { display: block; border: 0; padding: 5px 8px; }
}
</style>

<div class="nomina-per-page">
    <p class="nom-kicker">Nómina</p>
    <h1 class="nom-title">Periodos de nómina</h1>

    <?php $subnav_section = 'nomina'; $subnav_active = 'periodos'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <?php if ($pePuedeCalcular): ?>
    <div class="per-card">
        <h2><i class="fas fa-calculator" style="color:var(--nom-gold)"></i> Previsualizar periodo</h2>
        <?php if ($peGrupos === []): ?>
        <div class="per-vacio">
            <i class="fas fa-users-rectangle"></i>
            No hay grupos de pago activos. Crea uno en
            <a href="<?= url('nomina/catalogos?tipo=grupos') ?>">Catálogos → Grupos de pago</a>
            y asigna empleados en su ficha de nómina.
        </div>
        <?php else: ?>
        <form method="GET" action="<?= url('nomina/periodos/preview') ?>" class="per-form">
            <div class="per-field" style="flex:2 1 220px;">
                <label>Grupo de pago *</label>
                <select name="grupo_id" id="per-grupo" required>
                    <?php foreach ($peGrupos as $g): ?>
                    <option value="<?= (int) $g['id'] ?>"
                            data-inicio="<?= htmlspecialchars($peSugerencias[(int) $g['id']]['inicio'] ?? '') ?>"
                            data-fin="<?= htmlspecialchars($peSugerencias[(int) $g['id']]['fin'] ?? '') ?>">
                        <?= htmlspecialchars($g['nombre']) ?> (<?= htmlspecialchars($g['periodicidad']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="per-field">
                <label>Inicio *</label>
                <input type="date" name="inicio" id="per-inicio" required
                       value="<?= htmlspecialchars($peSugerencias[(int) ($peGrupos[0]['id'] ?? 0)]['inicio'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="per-field">
                <label>Fin *</label>
                <input type="date" name="fin" id="per-fin" required
                       value="<?= htmlspecialchars($peSugerencias[(int) ($peGrupos[0]['id'] ?? 0)]['fin'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="per-field" style="flex:0 0 auto;">
                <button type="submit" class="per-btn ms-pressable"><i class="fas fa-magnifying-glass-dollar"></i> Previsualizar</button>
            </div>
        </form>
        <p class="per-nota">El rango se sugiere según la periodicidad del grupo y su último periodo. La previsualización no guarda nada.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="per-card">
        <h2>Historial de periodos</h2>
        <?php if ($pePeriodos === []): ?>
        <div class="per-vacio"><i class="fas fa-calendar-week"></i> Aún no hay periodos de nómina.</div>
        <?php else: ?>
        <table class="per-tabla">
            <thead>
                <tr><th>Periodo</th><th>Grupo</th><th>Rango</th><th>Empleados</th><th>Bruto</th><th>Neto sugerido</th><th>Estado</th><th>Motor</th></tr>
            </thead>
            <tbody data-ms-stagger>
                <?php foreach ($pePeriodos as $p): ?>
                <?php
                    $esV2 = ($p['motor'] ?? 'v1') === 'v2';
                    $href = $esV2 ? url('nomina/periodos/' . (int) $p['id']) : url('trabajadores/nomina/periodos/' . (int) $p['id']);
                    $badge = $peEstados[(string) $p['estado']] ?? null;
                ?>
                <tr data-easy-href="<?= $href ?>" role="link" tabindex="0">
                    <td><strong><?= htmlspecialchars((string) $p['etiqueta']) ?></strong></td>
                    <td><?= $p['grupo_nombre'] !== null ? htmlspecialchars((string) $p['grupo_nombre']) : '—' ?></td>
                    <td><?= htmlspecialchars((string) $p['fecha_inicio']) ?> — <?= htmlspecialchars((string) $p['fecha_fin']) ?></td>
                    <td><?= (int) $p['trabajadores_total'] ?></td>
                    <td>$<?= number_format((float) $p['bruto_total'], 2) ?></td>
                    <td>$<?= number_format((float) $p['neto_sugerido_total'], 2) ?></td>
                    <td><?php if ($badge): ?><span class="per-badge <?= $badge['c'] ?>"><?= $badge['t'] ?></span><?php endif; ?></td>
                    <td><span class="per-badge motor"><?= $esV2 ? 'v2' : 'pre-nómina' ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var grupo = document.getElementById('per-grupo');
    if (!grupo) { return; }
    grupo.addEventListener('change', function () {
        var opcion = grupo.options[grupo.selectedIndex];
        var inicio = document.getElementById('per-inicio');
        var fin = document.getElementById('per-fin');
        if (opcion && opcion.dataset.inicio && inicio) { inicio.value = opcion.dataset.inicio; }
        if (opcion && opcion.dataset.fin && fin) { fin.value = opcion.dataset.fin; }
    });
})();
</script>
