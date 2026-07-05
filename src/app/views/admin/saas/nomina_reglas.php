<?php
/**
 * Panel Medisoft - reglas legales de nomina versionadas por ejercicio.
 * Identidad Medisoft (tokens --ms-*); no usa branding de hotel.
 */
$rgReglas = is_array($reglas ?? null) ? $reglas : [];
$rgFiltros = is_array($filtros ?? null) ? $filtros : [];
$rgTipos = is_array($tiposSugeridos ?? null) ? $tiposSugeridos : [];
?>
<style>
.ms-nomina-reglas {
    --mr-brand: var(--ms-primary, #123047);
    --mr-accent: var(--ms-accent, #2f6f8f);
    --mr-text: var(--ms-text, #1d2733);
    --mr-muted: var(--ms-muted, #627080);
    --mr-border: var(--ms-border, #d8e0e8);
    --mr-card: var(--ms-surface, #ffffff);
    color: var(--mr-text); max-width: 1180px; margin: 0 auto; padding: 4px 4px 40px;
}
.ms-nomina-reglas h1 { font-size: 22px; margin: 0 0 4px; }
.ms-nomina-reglas .mr-sub { color: var(--mr-muted); font-size: 13px; margin: 0 0 18px; }
.ms-nomina-reglas .mr-card { background: var(--mr-card); border: 1px solid var(--mr-border); border-radius: 12px; padding: 16px 18px; margin-bottom: 14px; }
.ms-nomina-reglas .mr-card h2 { font-size: 15px; margin: 0 0 12px; }
.ms-nomina-reglas .mr-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
.ms-nomina-reglas .mr-field { display: flex; flex-direction: column; gap: 4px; min-width: 130px; flex: 1 1 150px; }
.ms-nomina-reglas .mr-field label { font-size: 11px; font-weight: 700; color: var(--mr-muted); text-transform: uppercase; letter-spacing: .05em; }
.ms-nomina-reglas .mr-field input, .ms-nomina-reglas .mr-field select, .ms-nomina-reglas .mr-field textarea {
    border: 1px solid var(--mr-border); border-radius: 8px; padding: 8px 10px; font-size: 14px; background: #fff; color: var(--mr-text); width: 100%;
}
.ms-nomina-reglas .mr-btn {
    display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--mr-brand); border-radius: 9px;
    padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer; background: var(--mr-brand); color: #fff;
}
.ms-nomina-reglas .mr-btn-sec { background: transparent; color: var(--mr-brand); }
.ms-nomina-reglas table.mr-tabla { width: 100%; border-collapse: collapse; font-size: 13px; }
.ms-nomina-reglas table.mr-tabla th { text-align: left; font-size: 10.5px; text-transform: uppercase; color: var(--mr-muted); padding: 8px; border-bottom: 1px solid var(--mr-border); }
.ms-nomina-reglas table.mr-tabla td { padding: 8px; border-bottom: 1px solid color-mix(in srgb, var(--mr-border) 55%, transparent); vertical-align: top; }
.ms-nomina-reglas .mr-badge { display: inline-block; font-size: 11px; font-weight: 700; border-radius: 999px; padding: 2px 9px; }
.ms-nomina-reglas .mr-badge.activo { background: rgba(46,125,50,.12); color: #2e7d32; }
.ms-nomina-reglas .mr-badge.inactivo { background: rgba(120,120,120,.15); color: #666; }
.ms-nomina-reglas .mr-json { font-family: monospace; font-size: 11px; color: var(--mr-muted); max-width: 340px; overflow-wrap: anywhere; }
.ms-nomina-reglas .mr-nota { font-size: 12px; color: var(--mr-muted); margin-top: 8px; }
</style>

<div class="ms-nomina-reglas">
    <h1><i class="fas fa-scale-balanced"></i> Reglas legales de nómina</h1>
    <p class="mr-sub">
        Catálogo global versionado por ejercicio (UMA, salarios mínimos, tablas ISR, cuotas IMSS, prestaciones LFT).
        Al registrar una versión nueva, la vigencia anterior se cierra automáticamente. Todo cambio queda en el historial.
    </p>

    <div class="mr-card">
        <h2>Registrar nueva versión de regla</h2>
        <form method="POST" action="<?= url('admin/saas/nomina/reglas') ?>" class="mr-form">
            <?= csrf_field() ?>
            <div class="mr-field" style="flex:0 1 80px; min-width:70px;">
                <label>País</label>
                <input type="text" name="pais" value="MX" maxlength="2" required>
            </div>
            <div class="mr-field" style="flex:2 1 210px;">
                <label>Tipo de regla</label>
                <input type="text" name="tipo_regla" list="mr-tipos" required placeholder="uma_diaria, isr_tabla_quincenal…">
                <datalist id="mr-tipos">
                    <?php foreach ($rgTipos as $t): ?><option value="<?= htmlspecialchars($t) ?>"></option><?php endforeach; ?>
                </datalist>
            </div>
            <div class="mr-field" style="flex:0 1 100px;">
                <label>Ejercicio</label>
                <input type="number" name="ejercicio" min="2000" max="2100" value="<?= (int) date('Y') ?>" required>
            </div>
            <div class="mr-field" style="flex:0 1 150px;">
                <label>Vigente desde</label>
                <input type="date" name="vigente_desde" required value="<?= date('Y') ?>-01-01">
            </div>
            <div class="mr-field" style="flex:0 1 130px;">
                <label>Valor escalar</label>
                <input type="number" name="valor" step="0.0001" min="0" placeholder="ej. 113.14">
            </div>
            <div class="mr-field" style="flex:3 1 260px;">
                <label>Tabla JSON (tramos)</label>
                <textarea name="valores_json" rows="2" placeholder='{"tramos":[{"limite_inferior":0.01,...}]}'></textarea>
            </div>
            <div class="mr-field" style="flex:2 1 200px;">
                <label>Fuente oficial (obligatoria)</label>
                <input type="text" name="fuente" required placeholder="DOF 27/12/2025">
            </div>
            <div class="mr-field" style="flex:2 1 200px;">
                <label>Descripción</label>
                <input type="text" name="descripcion" maxlength="200">
            </div>
            <div class="mr-field" style="flex:0 0 auto;">
                <button type="submit" class="mr-btn"><i class="fas fa-plus"></i> Registrar</button>
            </div>
        </form>
        <p class="mr-nota">Valor escalar O tabla JSON (al menos uno). Los negocios consumen la regla vigente a la fecha del periodo y la congelan en su snapshot.</p>
    </div>

    <form method="GET" action="<?= url('admin/saas/nomina/reglas') ?>" class="mr-form" style="margin-bottom:12px;" data-auto-filter-form>
        <div class="mr-field" style="flex:0 1 80px;">
            <label>País</label>
            <input type="text" name="pais" maxlength="2" value="<?= htmlspecialchars((string) ($rgFiltros['pais'] ?? 'MX')) ?>">
        </div>
        <div class="mr-field" style="flex:0 1 220px;">
            <label>Tipo</label>
            <select name="tipo_regla">
                <option value="">Todos</option>
                <?php foreach ($rgTipos as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= ($rgFiltros['tipo_regla'] ?? '') === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mr-field" style="flex:0 1 110px;">
            <label>Ejercicio</label>
            <input type="number" name="ejercicio" min="2000" max="2100" value="<?= htmlspecialchars((string) ($rgFiltros['ejercicio'] ?? '')) ?>">
        </div>
    </form>

    <div class="mr-card">
        <table class="mr-tabla">
            <thead>
                <tr><th>Tipo</th><th>Ejercicio</th><th>Vigencia</th><th>Valor</th><th>Fuente</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
                <?php if ($rgReglas === []): ?>
                <tr><td colspan="7" style="text-align:center; color:var(--mr-muted); padding:24px;">Sin reglas con estos filtros.</td></tr>
                <?php endif; ?>
                <?php foreach ($rgReglas as $r): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars((string) $r['tipo_regla']) ?></strong>
                        <div style="font-size:11.5px; color:var(--mr-muted);"><?= htmlspecialchars((string) ($r['descripcion'] ?? '')) ?></div>
                    </td>
                    <td><?= (int) $r['ejercicio'] ?> <span style="color:var(--mr-muted); font-size:11px;">(<?= htmlspecialchars((string) $r['pais']) ?>)</span></td>
                    <td>
                        <?= htmlspecialchars((string) $r['vigente_desde']) ?> —
                        <?= $r['vigente_hasta'] !== null ? htmlspecialchars((string) $r['vigente_hasta']) : '<strong>abierta</strong>' ?>
                    </td>
                    <td>
                        <?php if ($r['valor'] !== null): ?>
                            <?= rtrim(rtrim(number_format((float) $r['valor'], 4, '.', ','), '0'), '.') ?>
                        <?php elseif ($r['valores_json'] !== null): ?>
                            <details><summary style="cursor:pointer; color:var(--mr-accent); font-size:12px;">tabla JSON</summary>
                            <div class="mr-json"><?= htmlspecialchars((string) $r['valores_json']) ?></div></details>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;"><?= htmlspecialchars((string) ($r['fuente'] ?? '')) ?></td>
                    <td><span class="mr-badge <?= htmlspecialchars((string) $r['estado']) ?>"><?= htmlspecialchars((string) $r['estado']) ?></span></td>
                    <td>
                        <form method="POST" action="<?= url('admin/saas/nomina/reglas/' . (int) $r['id'] . '/alternar') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="mr-btn mr-btn-sec" style="padding:4px 10px; font-size:12px;">
                                <?= $r['estado'] === 'activo' ? 'Desactivar' : 'Reactivar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
