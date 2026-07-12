<?php
/**
 * Nomina core - ficha de nomina del empleado (Fase 2).
 * Asignaciones (puesto/departamento/contrato/grupo) e historial salarial.
 */
$fiTrab = is_array($trabajador ?? null) ? $trabajador : [];
$fiPuestos = is_array($puestos ?? null) ? $puestos : [];
$fiDeptos = is_array($departamentos ?? null) ? $departamentos : [];
$fiContratos = is_array($contratos ?? null) ? $contratos : [];
$fiGrupos = is_array($grupos ?? null) ? $grupos : [];
$fiPuedeEmpleados = !empty($puedeEmpleados);
$fiPuedeSalarios = !empty($puedeSalarios);
$fiVigente = is_array($salarioVigente ?? null) ? $salarioVigente : null;
$fiHistorial = is_array($historialSalarios ?? null) ? $historialSalarios : [];

$fiEsquemas = [
    'semanal' => 'Semanal', 'quincenal' => 'Quincenal', 'mensual' => 'Mensual',
    'diario' => 'Diario', 'por_hora' => 'Por hora', 'por_evento' => 'Por evento',
];

$back_arrow_href = url('nomina/empleados');
include APP_PATH . '/views/partials/back_arrow.php';

$fiSelect = function (string $name, array $opciones, $seleccionado) {
    ?>
    <select name="<?= $name ?>">
        <option value="">— Sin asignar —</option>
        <?php foreach ($opciones as $op): ?>
        <option value="<?= (int) $op['id'] ?>" <?= (int) $seleccionado === (int) $op['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($op['nombre']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php
};
?>
<style>
.nomina-ficha-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F6F2EA);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text); max-width: 980px; margin: 0 auto; padding: 4px 4px 40px;
}
.nomina-ficha-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-ficha-page .nom-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 28px; margin: 2px 0 2px; font-weight: 600; }
.nomina-ficha-page .fi-sub { color: var(--nom-muted); font-size: 13.5px; margin: 0 0 18px; }
.nomina-ficha-page .fi-badge { display: inline-block; font-size: 11.5px; font-weight: 700; border-radius: 999px; padding: 2px 10px; vertical-align: middle; }
.nomina-ficha-page .fi-badge.activo { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-ficha-page .fi-badge.inactivo { background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-ficha-page .fi-badge.baja { background: rgba(198,40,40,.10); color: #c62828; }
.nomina-ficha-page .fi-card { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; padding: 18px 20px; margin-bottom: 16px; }
.nomina-ficha-page .fi-card h2 { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 19px; margin: 0 0 4px; font-weight: 600; }
.nomina-ficha-page .fi-hint { font-size: 12.5px; color: var(--nom-muted); margin: 0 0 14px; }
.nomina-ficha-page .fi-warn {
    display: flex; align-items: center; gap: 8px; margin: 0 0 14px;
    padding: 9px 12px; border-radius: 10px; font-size: 13px; line-height: 1.4;
    background: rgba(191,144,0,.10); border: 1px solid rgba(191,144,0,.30); color: #7a5c00;
}
.nomina-ficha-page .fi-warn i { color: #9a7400; }
.nomina-ficha-page .fi-warn a { color: inherit; font-weight: 700; text-decoration: underline; }
.nomina-ficha-page .fi-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
@media (max-width: 640px) { .nomina-ficha-page .fi-grid { grid-template-columns: 1fr; } }
.nomina-ficha-page .fi-field { display: flex; flex-direction: column; gap: 4px; }
.nomina-ficha-page .fi-field label { font-size: 11.5px; font-weight: 700; color: var(--nom-muted); }
.nomina-ficha-page .fi-field input, .nomina-ficha-page .fi-field select, .nomina-ficha-page .fi-field textarea {
    border: 1px solid var(--nom-border); border-radius: 10px; padding: 9px 11px; font-size: 16px; background: #fff; color: var(--nom-text); width: 100%;
}
.nomina-ficha-page .fi-actions { margin-top: 14px; display: flex; justify-content: flex-end; }
.nomina-ficha-page .fi-btn {
    display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--nom-brand);
    border-radius: 11px; padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
    background: var(--nom-brand); color: #fff;
}
.nomina-ficha-page .fi-salario-actual { display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
.nomina-ficha-page .fi-salario-monto { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 30px; font-weight: 600; }
.nomina-ficha-page table.fi-tabla { width: 100%; border-collapse: collapse; font-size: 13px; }
.nomina-ficha-page table.fi-tabla th { text-align: left; font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: var(--nom-muted); padding: 8px; border-bottom: 1px solid var(--nom-border); }
.nomina-ficha-page table.fi-tabla td { padding: 8px; border-bottom: 1px dashed var(--nom-border); }
.nomina-ficha-page .fi-vigente-row { background: color-mix(in srgb, var(--nom-gold) 7%, transparent); }
.nomina-ficha-page .fi-notice { border: 1px dashed var(--nom-border); border-radius: 12px; padding: 12px 14px; font-size: 12.5px; color: var(--nom-muted); }
</style>

<div class="nomina-ficha-page">
    <p class="nom-kicker">Nómina · Ficha del empleado</p>
    <h1 class="nom-title">
        <?= htmlspecialchars((string) ($fiTrab['nombre_completo'] ?? '')) ?>
        <span class="fi-badge <?= htmlspecialchars((string) ($fiTrab['estado'] ?? '')) ?>"><?= htmlspecialchars(ucfirst((string) ($fiTrab['estado'] ?? ''))) ?></span>
    </h1>
    <p class="fi-sub">
        <?= htmlspecialchars((string) ($fiTrab['rol_laboral'] ?? '')) ?>
        <?= !empty($fiTrab['identificacion']) ? ' · ID ' . htmlspecialchars((string) $fiTrab['identificacion']) : '' ?>
        · <a href="<?= url('trabajadores/' . (int) ($fiTrab['id'] ?? 0)) ?>">Ver expediente en Personal</a>
    </p>

    <?php $subnav_section = 'nomina'; $subnav_active = 'empleados'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <div class="fi-card">
        <h2>Asignaciones de nómina</h2>
        <p class="fi-hint">Puesto, departamento, contrato y grupo de pago. El grupo define con qué periodicidad entra a los periodos de nómina.</p>
        <?php if ($fiGrupos === []): ?>
        <p class="fi-warn"><i class="fas fa-triangle-exclamation"></i>
            <span>Aún no hay grupos de pago creados: sin grupo, nadie entra a los periodos de nómina.
            Créalos primero en <a href="<?= url('nomina/catalogos') ?>">Ajustes &rarr; Catálogos</a>.</span>
        </p>
        <?php endif; ?>
        <?php if ($fiPuedeEmpleados): ?>
        <form method="POST" action="<?= url('nomina/empleados/' . (int) ($fiTrab['id'] ?? 0) . '/asignaciones') ?>">
            <?= csrf_field() ?>
            <div class="fi-grid">
                <div class="fi-field"><label>Puesto</label><?php $fiSelect('puesto_id', $fiPuestos, $fiTrab['puesto_id'] ?? 0); ?></div>
                <div class="fi-field"><label>Departamento</label><?php $fiSelect('departamento_id', $fiDeptos, $fiTrab['departamento_id'] ?? 0); ?></div>
                <div class="fi-field"><label>Tipo de contrato</label><?php $fiSelect('tipo_contrato_id', $fiContratos, $fiTrab['tipo_contrato_id'] ?? 0); ?></div>
                <div class="fi-field"><label>Grupo de pago</label><?php $fiSelect('grupo_nomina_id', $fiGrupos, $fiTrab['grupo_nomina_id'] ?? 0); ?></div>
            </div>
            <div class="fi-actions">
                <button type="submit" class="fi-btn ms-pressable"><i class="fas fa-check"></i> Guardar asignaciones</button>
            </div>
        </form>
        <?php else: ?>
        <div class="fi-grid">
            <div class="fi-field"><label>Puesto</label><div><?= htmlspecialchars((string) ($fiTrab['puesto_nombre'] ?? '—')) ?></div></div>
            <div class="fi-field"><label>Departamento</label><div><?= htmlspecialchars((string) ($fiTrab['departamento_nombre'] ?? '—')) ?></div></div>
            <div class="fi-field"><label>Tipo de contrato</label><div><?= htmlspecialchars((string) ($fiTrab['contrato_nombre'] ?? '—')) ?></div></div>
            <div class="fi-field"><label>Grupo de pago</label><div><?= htmlspecialchars((string) ($fiTrab['grupo_nombre'] ?? '—')) ?></div></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($fiPuedeSalarios): ?>
    <div class="fi-card">
        <h2>Salario</h2>
        <?php if ($fiVigente): ?>
        <div class="fi-salario-actual">
            <span class="fi-salario-monto">$<?= number_format((float) $fiVigente['salario'], 2) ?></span>
            <span><?= $fiEsquemas[$fiVigente['esquema']] ?? htmlspecialchars((string) $fiVigente['esquema']) ?> · vigente desde <?= htmlspecialchars((string) $fiVigente['vigente_desde']) ?></span>
        </div>
        <?php else: ?>
        <p class="fi-hint">Sin salario vigente registrado. Registra el primero abajo.</p>
        <?php endif; ?>

        <form method="POST" action="<?= url('nomina/empleados/' . (int) ($fiTrab['id'] ?? 0) . '/salario') ?>">
            <?= csrf_field() ?>
            <div class="fi-grid">
                <div class="fi-field">
                    <label>Nuevo salario *</label>
                    <input type="number" name="salario" min="0.01" step="0.01" required
                           value="<?= $fiVigente ? number_format((float) $fiVigente['salario'], 2, '.', '') : '' ?>">
                </div>
                <div class="fi-field">
                    <label>Esquema *</label>
                    <select name="esquema" required>
                        <?php foreach ($fiEsquemas as $ek => $el): ?>
                        <option value="<?= $ek ?>" <?= ($fiVigente['esquema'] ?? $fiTrab['periodicidad_pago'] ?? 'quincenal') === $ek ? 'selected' : '' ?>><?= $el ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fi-field">
                    <label>Vigente desde *</label>
                    <input type="date" name="vigente_desde" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="fi-field">
                    <label>Motivo</label>
                    <input type="text" name="motivo" maxlength="200" placeholder="Aumento anual, promoción…">
                </div>
            </div>
            <div class="fi-actions">
                <button type="submit" class="fi-btn ms-pressable"><i class="fas fa-money-check-dollar"></i> Registrar cambio salarial</button>
            </div>
        </form>

        <?php if ($fiHistorial !== []): ?>
        <h2 style="margin-top:18px;">Historial salarial</h2>
        <table class="fi-tabla">
            <thead>
                <tr><th>Vigencia</th><th>Salario</th><th>Esquema</th><th>Motivo</th></tr>
            </thead>
            <tbody>
                <?php foreach ($fiHistorial as $h): ?>
                <tr class="<?= $h['vigente_hasta'] === null ? 'fi-vigente-row' : '' ?>">
                    <td>
                        <?= htmlspecialchars((string) $h['vigente_desde']) ?> —
                        <?= $h['vigente_hasta'] !== null ? htmlspecialchars((string) $h['vigente_hasta']) : '<strong>vigente</strong>' ?>
                    </td>
                    <td>$<?= number_format((float) $h['salario'], 2) ?></td>
                    <td><?= $fiEsquemas[$h['esquema']] ?? htmlspecialchars((string) $h['esquema']) ?></td>
                    <td><?= htmlspecialchars((string) ($h['motivo'] ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="fi-card">
        <h2>Salario</h2>
        <div class="fi-notice"><i class="fas fa-lock"></i> No tienes permiso para ver ni editar información salarial (requiere <code>nomina.salarios</code>).</div>
    </div>
    <?php endif; ?>
</div>
