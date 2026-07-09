<?php
/**
 * Nomina core - catalogos internos (Fase 2).
 * Tabs: departamentos, puestos, tipos de contrato, grupos de pago y conceptos.
 */
$catTipo = (string) ($tipo ?? 'departamentos');
$catRegistros = is_array($registros ?? null) ? $registros : [];
$catConteos = is_array($conteos ?? null) ? $conteos : [];
$catDepartamentos = is_array($departamentosActivos ?? null) ? $departamentosActivos : [];
$catPuedeConfigurar = !empty($puedeConfigurar);
$catPuedeGestionarRoles = function_exists('can') && can('roles.manage');

$catTabs = [
    'departamentos' => ['label' => 'Departamentos', 'icono' => 'fa-sitemap'],
    'puestos' => ['label' => 'Puestos', 'icono' => 'fa-user-tie'],
    'tipos_contrato' => ['label' => 'Contratos', 'icono' => 'fa-file-signature'],
    'grupos' => ['label' => 'Grupos de pago', 'icono' => 'fa-users-rectangle'],
    'conceptos' => ['label' => 'Conceptos', 'icono' => 'fa-list-check'],
];

$catClasificaciones = ['sueldo', 'bono', 'comision', 'horas_extra', 'propina', 'destajo', 'descuento', 'anticipo', 'prestamo', 'ajuste', 'otro'];
$catModos = ['manual' => 'Manual', 'monto_fijo' => 'Monto fijo', 'por_cantidad' => 'Por cantidad'];

$back_arrow_href = url('nomina');
include APP_PATH . '/views/partials/back_arrow.php';

// Campos del formulario (crear/editar) por tipo de catalogo.
$catRenderCampos = function (string $t, array $r = []) use ($catDepartamentos, $catClasificaciones, $catModos) {
    $v = fn($campo, $def = '') => htmlspecialchars((string) ($r[$campo] ?? $def));
    ?>
    <div class="cat-field cat-field-nombre">
        <label>Nombre *</label>
        <input type="text" name="nombre" required maxlength="<?= $t === 'conceptos' ? 120 : 100 ?>" value="<?= $v('nombre') ?>">
    </div>
    <?php if ($t !== 'conceptos'): ?>
    <div class="cat-field">
        <label>Descripción</label>
        <input type="text" name="descripcion" maxlength="200" value="<?= $v('descripcion') ?>">
    </div>
    <?php endif; ?>
    <?php if ($t === 'puestos'): ?>
    <div class="cat-field">
        <label>Departamento</label>
        <select name="departamento_id">
            <option value="">— Sin departamento —</option>
            <?php foreach ($catDepartamentos as $dep): ?>
            <option value="<?= (int) $dep['id'] ?>" <?= (int) ($r['departamento_id'] ?? 0) === (int) $dep['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dep['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="cat-field">
        <label>Salario sugerido</label>
        <input type="number" name="salario_sugerido" min="0" step="0.01" value="<?= isset($r['salario_sugerido']) && $r['salario_sugerido'] !== null ? number_format((float) $r['salario_sugerido'], 2, '.', '') : '' ?>">
    </div>
    <?php endif; ?>
    <?php if ($t === 'grupos'): ?>
    <div class="cat-field">
        <label>Periodicidad *</label>
        <select name="periodicidad" required>
            <?php foreach (['semanal', 'quincenal', 'mensual'] as $per): ?>
            <option value="<?= $per ?>" <?= ($r['periodicidad'] ?? 'quincenal') === $per ? 'selected' : '' ?>><?= ucfirst($per) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="cat-field">
        <label>Día de corte</label>
        <input type="number" name="dia_corte" min="1" max="31" value="<?= $v('dia_corte') ?>" placeholder="1-31">
    </div>
    <div class="cat-field">
        <label>Día de pago</label>
        <input type="number" name="dia_pago" min="1" max="31" value="<?= $v('dia_pago') ?>" placeholder="1-31">
    </div>
    <?php endif; ?>
    <?php if ($t === 'conceptos'): ?>
    <div class="cat-field">
        <label>Tipo *</label>
        <select name="tipo" required>
            <option value="percepcion" <?= ($r['tipo'] ?? '') === 'percepcion' ? 'selected' : '' ?>>Percepción (+)</option>
            <option value="deduccion" <?= ($r['tipo'] ?? '') === 'deduccion' ? 'selected' : '' ?>>Deducción (−)</option>
        </select>
    </div>
    <div class="cat-field">
        <label>Clasificación</label>
        <select name="clasificacion">
            <?php foreach ($catClasificaciones as $cla): ?>
            <option value="<?= $cla ?>" <?= ($r['clasificacion'] ?? 'otro') === $cla ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $cla)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="cat-field">
        <label>Modo de cálculo</label>
        <select name="modo_calculo">
            <?php foreach ($catModos as $mk => $ml): ?>
            <option value="<?= $mk ?>" <?= ($r['modo_calculo'] ?? 'manual') === $mk ? 'selected' : '' ?>><?= $ml ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="cat-field">
        <label>Monto default</label>
        <input type="number" name="monto_default" min="0" step="0.01" value="<?= isset($r['monto_default']) && $r['monto_default'] !== null ? number_format((float) $r['monto_default'], 2, '.', '') : '' ?>">
    </div>
    <?php endif; ?>
    <div class="cat-field cat-field-orden">
        <label>Orden</label>
        <input type="number" name="orden" min="0" value="<?= $v('orden', '0') ?>">
    </div>
    <?php
};
?>
<style>
.nomina-cat-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F6F2EA);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text); max-width: 1120px; margin: 0 auto; padding: 4px 4px 40px;
}
.nomina-cat-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-cat-page .nom-title { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 28px; margin: 2px 0 14px; font-weight: 600; }
.nomina-cat-page .cat-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
.nomina-cat-page .cat-tab {
    display: inline-flex; align-items: center; gap: 7px; text-decoration: none;
    border: 1px solid var(--nom-border); border-radius: 999px; padding: 8px 14px;
    font-size: 13px; font-weight: 600; color: var(--nom-text); background: var(--nom-card);
}
.nomina-cat-page .cat-tab.activa { background: var(--nom-brand); border-color: var(--nom-brand); color: #fff; }
.nomina-cat-page .cat-tab .cat-count { font-size: 11px; opacity: .75; }
.nomina-cat-page .cat-card { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; padding: 18px 20px; margin-bottom: 16px; }
.nomina-cat-page .cat-card h2 { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 19px; margin: 0 0 12px; font-weight: 600; }
.nomina-cat-page .cat-notice {
    display: flex; gap: 12px; align-items: flex-start;
    border: 1px solid color-mix(in srgb, var(--nom-gold) 45%, var(--nom-border));
    background: color-mix(in srgb, var(--nom-gold) 8%, var(--nom-card));
    border-radius: 14px; padding: 14px 16px; margin-bottom: 16px; font-size: 13.5px;
    color: var(--nom-text);
}
.nomina-cat-page .cat-notice i { color: var(--nom-gold); margin-top: 2px; }
.nomina-cat-page .cat-notice-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.nomina-cat-page .cat-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
.nomina-cat-page .cat-field { display: flex; flex-direction: column; gap: 4px; min-width: 130px; flex: 1 1 150px; }
.nomina-cat-page .cat-field-nombre { flex: 2 1 220px; }
.nomina-cat-page .cat-field-orden { flex: 0 1 90px; min-width: 80px; }
.nomina-cat-page .cat-field label { font-size: 11.5px; font-weight: 700; color: var(--nom-muted); }
.nomina-cat-page .cat-field input, .nomina-cat-page .cat-field select {
    border: 1px solid var(--nom-border); border-radius: 10px; padding: 9px 11px; font-size: 16px; background: #fff; color: var(--nom-text); width: 100%;
}
.nomina-cat-page .cat-btn {
    display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--nom-border);
    border-radius: 11px; padding: 9px 15px; font-size: 13px; font-weight: 600; cursor: pointer;
    background: var(--nom-card); color: var(--nom-text); text-decoration: none;
}
.nomina-cat-page .cat-btn-primary { background: var(--nom-brand); border-color: var(--nom-brand); color: #fff; }
.nomina-cat-page .cat-btn-sm { padding: 6px 11px; font-size: 12px; border-radius: 9px; }
.nomina-cat-page table.cat-tabla { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.nomina-cat-page table.cat-tabla th { text-align: left; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--nom-muted); padding: 8px 10px; border-bottom: 1px solid var(--nom-border); }
.nomina-cat-page table.cat-tabla td { padding: 10px; border-bottom: 1px dashed var(--nom-border); vertical-align: top; }
.nomina-cat-page .cat-badge { display: inline-block; font-size: 11.5px; font-weight: 700; border-radius: 999px; padding: 2px 10px; }
.nomina-cat-page .cat-badge.on { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-cat-page .cat-badge.off { background: rgba(120,120,120,.14); color: #666; }
.nomina-cat-page .cat-badge.tipo-percepcion { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-cat-page .cat-badge.tipo-deduccion { background: rgba(198,40,40,.10); color: #c62828; }
.nomina-cat-page details.cat-editar { margin-top: 6px; }
.nomina-cat-page details.cat-editar summary { cursor: pointer; font-size: 12px; color: var(--nom-gold); font-weight: 700; list-style: none; }
.nomina-cat-page details.cat-editar[open] summary { margin-bottom: 8px; }
.nomina-cat-page .cat-vacio { text-align: center; padding: 30px 16px; color: var(--nom-muted); }
.nomina-cat-page .cat-vacio i { font-size: 24px; color: var(--nom-gold); display: block; margin-bottom: 8px; }
.nomina-cat-page .cat-acciones { display: flex; gap: 6px; flex-wrap: wrap; }
@media (max-width: 768px) {
    .nomina-cat-page table.cat-tabla thead { display: none; }
    .nomina-cat-page table.cat-tabla tr { display: block; border: 1px solid var(--nom-border); border-radius: 12px; margin-bottom: 10px; padding: 8px; }
    .nomina-cat-page table.cat-tabla td { display: block; border: 0; padding: 6px 8px; }
}
</style>

<div class="nomina-cat-page">
    <p class="nom-kicker">Nómina</p>
    <h1 class="nom-title">Catálogos de nómina</h1>

    <?php $subnav_section = 'nomina'; $subnav_active = 'ajustes'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <?php if ($catPuedeConfigurar): ?>
    <div style="display:flex; flex-wrap:wrap; gap:8px; margin:-6px 0 14px;">
        <a class="cat-btn ms-pressable" href="<?= url('nomina/configuracion') ?>"><i class="fas fa-sliders"></i> Configuración</a>
        <span class="cat-btn cat-btn-primary" aria-current="page"><i class="fas fa-layer-group"></i> Catálogos</span>
    </div>
    <?php endif; ?>

    <div class="cat-tabs">
        <?php foreach ($catTabs as $tabKey => $tab): ?>
        <a class="cat-tab <?= $catTipo === $tabKey ? 'activa' : '' ?>" href="<?= url('nomina/catalogos?tipo=' . $tabKey) ?>">
            <i class="fas <?= $tab['icono'] ?>"></i> <?= $tab['label'] ?>
            <span class="cat-count">(<?= (int) ($catConteos[$tabKey]['activos'] ?? 0) ?>)</span>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (!$catPuedeConfigurar): ?>
    <div class="cat-notice">
        <i class="fas fa-lock"></i>
        <div>
            <strong>Estás viendo catálogos en modo lectura.</strong><br>
            Tu usuario puede consultar nómina, pero no crear ni editar catálogos. Para configurar departamentos,
            puestos, contratos o grupos de pago necesitas el permiso <strong>nomina.configurar</strong>.
            <div class="cat-notice-actions">
                <a href="<?= url('nomina') ?>" class="cat-btn ms-pressable"><i class="fas fa-arrow-left"></i> Volver a nómina</a>
                <?php if ($catPuedeGestionarRoles): ?>
                <a href="<?= url('configuracion/roles') ?>" class="cat-btn ms-pressable"><i class="fas fa-user-lock"></i> Revisar roles</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($catPuedeConfigurar): ?>
    <div class="cat-card">
        <h2><i class="fas fa-plus" style="color:var(--nom-gold)"></i> Nuevo <?= strtolower($catTabs[$catTipo]['label']) === 'contratos' ? 'tipo de contrato' : rtrim(strtolower($catTabs[$catTipo]['label']), 's') ?></h2>
        <form method="POST" action="<?= url('nomina/catalogos/' . $catTipo . '/crear') ?>" class="cat-form">
            <?= csrf_field() ?>
            <?php $catRenderCampos($catTipo); ?>
            <div class="cat-field" style="flex:0 0 auto;">
                <button type="submit" class="cat-btn cat-btn-primary ms-pressable"><i class="fas fa-check"></i> Crear</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="cat-card">
        <h2><?= $catTabs[$catTipo]['label'] ?></h2>
        <?php if ($catRegistros === []): ?>
        <div class="cat-vacio">
            <i class="fas fa-layer-group"></i>
            Este catálogo está vacío.
            <?= $catPuedeConfigurar ? 'Crea el primer registro con el formulario de arriba.' : 'Cuando un usuario con nomina.configurar cree registros, aquí podrás consultarlos.' ?>
        </div>
        <?php else: ?>
        <table class="cat-tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Detalle</th>
                    <th>Estado</th>
                    <?php if ($catPuedeConfigurar): ?><th>Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($catRegistros as $r): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($r['nombre']) ?></strong>
                        <?php if (!empty($r['es_sistema'])): ?><span class="cat-badge off" title="Concepto base del sistema">base</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($catTipo === 'conceptos'): ?>
                            <span class="cat-badge tipo-<?= htmlspecialchars($r['tipo']) ?>"><?= $r['tipo'] === 'percepcion' ? 'Percepción' : 'Deducción' ?></span>
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $r['clasificacion']))) ?>
                            · <?= $catModos[$r['modo_calculo']] ?? $r['modo_calculo'] ?>
                            <?= $r['monto_default'] !== null ? ' · $' . number_format((float) $r['monto_default'], 2) : '' ?>
                        <?php elseif ($catTipo === 'grupos'): ?>
                            <?= ucfirst((string) $r['periodicidad']) ?>
                            <?= $r['dia_corte'] !== null ? ' · corte día ' . (int) $r['dia_corte'] : '' ?>
                            <?= $r['dia_pago'] !== null ? ' · pago día ' . (int) $r['dia_pago'] : '' ?>
                        <?php elseif ($catTipo === 'puestos'): ?>
                            <?= htmlspecialchars((string) ($r['descripcion'] ?? '')) ?>
                            <?= $r['salario_sugerido'] !== null ? ' · sugerido $' . number_format((float) $r['salario_sugerido'], 2) : '' ?>
                        <?php else: ?>
                            <?= htmlspecialchars((string) ($r['descripcion'] ?? '')) ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="cat-badge <?= !empty($r['activo']) ? 'on' : 'off' ?>"><?= !empty($r['activo']) ? 'Activo' : 'Inactivo' ?></span></td>
                    <?php if ($catPuedeConfigurar): ?>
                    <td>
                        <div class="cat-acciones">
                            <form method="POST" action="<?= url('nomina/catalogos/' . $catTipo . '/' . (int) $r['id'] . '/alternar') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="cat-btn cat-btn-sm ms-pressable">
                                    <?= !empty($r['activo']) ? '<i class="fas fa-toggle-off"></i> Desactivar' : '<i class="fas fa-toggle-on"></i> Activar' ?>
                                </button>
                            </form>
                        </div>
                        <details class="cat-editar">
                            <summary>Editar</summary>
                            <form method="POST" action="<?= url('nomina/catalogos/' . $catTipo . '/' . (int) $r['id'] . '/actualizar') ?>" class="cat-form">
                                <?= csrf_field() ?>
                                <?php $catRenderCampos($catTipo, $r); ?>
                                <div class="cat-field" style="flex:0 0 auto;">
                                    <button type="submit" class="cat-btn cat-btn-primary cat-btn-sm ms-pressable">Guardar</button>
                                </div>
                            </form>
                        </details>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
