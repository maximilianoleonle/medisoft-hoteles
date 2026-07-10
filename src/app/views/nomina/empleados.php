<?php
/**
 * Nomina core - listado de empleados con asignaciones (Fase 2).
 * Solo lectura sobre trabajadores del bloque personal + catalogos de nomina.
 */
$empLista = is_array($empleados ?? null) ? $empleados : [];
$empBuscar = (string) ($buscar ?? '');
$empEstado = (string) ($estado ?? 'activo');
$empPersonalActivo = !empty($personalActivo);

$back_arrow_href = url('nomina');
include APP_PATH . '/views/partials/back_arrow.php';
?>
<style>
.nomina-emp-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F6F2EA);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text); max-width: 1120px; margin: 0 auto; padding: 4px 4px 40px;
}
.nomina-emp-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-emp-page .nom-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 28px; margin: 2px 0 14px; font-weight: 600; }
.nomina-emp-page .emp-filtros { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
.nomina-emp-page .emp-filtros input, .nomina-emp-page .emp-filtros select {
    border: 1px solid var(--nom-border); border-radius: 10px; padding: 9px 12px; font-size: 16px; background: #fff; color: var(--nom-text);
}
.nomina-emp-page .emp-card { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; padding: 6px 14px 14px; }
.nomina-emp-page table.emp-tabla { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.nomina-emp-page table.emp-tabla th { text-align: left; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--nom-muted); padding: 10px; border-bottom: 1px solid var(--nom-border); }
.nomina-emp-page table.emp-tabla td { padding: 10px; border-bottom: 1px dashed var(--nom-border); }
.nomina-emp-page table.emp-tabla tr[data-easy-href] { cursor: pointer; }
.nomina-emp-page table.emp-tabla tr[data-easy-href]:hover td { background: color-mix(in srgb, var(--nom-gold) 6%, transparent); }
.nomina-emp-page .emp-badge { display: inline-block; font-size: 11.5px; font-weight: 700; border-radius: 999px; padding: 2px 10px; }
.nomina-emp-page .emp-badge.activo { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-emp-page .emp-badge.inactivo { background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-emp-page .emp-badge.baja { background: rgba(198,40,40,.10); color: #c62828; }
.nomina-emp-page .emp-sin-dato { color: var(--nom-muted); font-style: italic; }
.nomina-emp-page .emp-vacio { text-align: center; padding: 34px 16px; color: var(--nom-muted); }
.nomina-emp-page .emp-vacio i { font-size: 26px; color: var(--nom-gold); display: block; margin-bottom: 10px; }
@media (max-width: 768px) {
    .nomina-emp-page table.emp-tabla thead { display: none; }
    .nomina-emp-page table.emp-tabla tr { display: block; border: 1px solid var(--nom-border); border-radius: 12px; margin: 10px 0; padding: 8px; }
    .nomina-emp-page table.emp-tabla td { display: block; border: 0; padding: 5px 8px; }
}
</style>

<div class="nomina-emp-page">
    <p class="nom-kicker">Nómina</p>
    <h1 class="nom-title">Empleados</h1>

    <?php $subnav_section = 'nomina'; $subnav_active = 'empleados'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <form method="GET" action="<?= url('nomina/empleados') ?>" class="emp-filtros" data-auto-filter-form>
        <input type="text" name="buscar" placeholder="Buscar por nombre…" value="<?= htmlspecialchars($empBuscar) ?>">
        <select name="estado">
            <?php foreach (['activo' => 'Activos', 'inactivo' => 'Inactivos', 'baja' => 'Bajas', 'todos' => 'Todos'] as $ek => $el): ?>
            <option value="<?= $ek ?>" <?= $empEstado === $ek ? 'selected' : '' ?>><?= $el ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="emp-card">
        <?php if ($empLista === []): ?>
        <div class="emp-vacio">
            <i class="fas fa-address-book"></i>
            <?php if (!$empPersonalActivo): ?>
                El bloque Personal no está activo: no hay empleados que mostrar.
            <?php elseif ($empBuscar !== '' || $empEstado !== 'activo'): ?>
                Sin resultados con estos filtros.
            <?php else: ?>
                Aún no hay empleados. Da de alta a tu equipo en <a href="<?= url('trabajadores') ?>">Personal</a> y aquí les asignas puesto, grupo y salario.
            <?php endif; ?>
        </div>
        <?php else: ?>
        <table class="emp-tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Puesto</th>
                    <th>Departamento</th>
                    <th>Grupo de pago</th>
                    <th>Contrato</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody data-ms-stagger>
                <?php foreach ($empLista as $e): ?>
                <tr data-easy-href="<?= url('nomina/empleados/' . (int) $e['id']) ?>" role="link" tabindex="0">
                    <td><strong><?= htmlspecialchars($e['nombre_completo']) ?></strong></td>
                    <td><?= $e['puesto'] !== null ? htmlspecialchars($e['puesto']) : '<span class="emp-sin-dato">' . htmlspecialchars((string) ($e['rol_laboral'] ?? 'sin puesto')) . '</span>' ?></td>
                    <td><?= $e['departamento'] !== null ? htmlspecialchars($e['departamento']) : '<span class="emp-sin-dato">—</span>' ?></td>
                    <td><?= $e['grupo_nomina'] !== null ? htmlspecialchars($e['grupo_nomina']) : '<span class="emp-sin-dato">' . htmlspecialchars(ucfirst((string) ($e['periodicidad_pago'] ?? '—'))) . '</span>' ?></td>
                    <td><?= $e['tipo_contrato'] !== null ? htmlspecialchars($e['tipo_contrato']) : '<span class="emp-sin-dato">—</span>' ?></td>
                    <td><span class="emp-badge <?= htmlspecialchars((string) $e['estado']) ?>"><?= htmlspecialchars(ucfirst((string) $e['estado'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
