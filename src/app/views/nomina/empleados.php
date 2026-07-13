<?php
/**
 * Nomina core - listado de empleados con asignaciones (Fase 2).
 * Solo lectura sobre trabajadores del bloque personal + catalogos de nomina.
 */
$empLista = is_array($empleados ?? null) ? $empleados : [];
$empBuscar = (string) ($buscar ?? '');
$empEstado = (string) ($estado ?? 'activo');
$empPersonalActivo = !empty($personalActivo);
// El alta de empleado es un registro del bloque Personal: solo se ofrece si el
// modulo esta activo y el usuario tiene la misma autoridad que el alta original.
$empPuedeCrear = $empPersonalActivo && function_exists('can') && can('personal.gestionar');

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
.nomina-emp-page .emp-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.nomina-emp-page .emp-filtros { display: flex; flex-wrap: wrap; gap: 10px; }
.nomina-emp-page .emp-toolbar .emp-filtros { margin: 0; flex: 1 1 auto; }
.nomina-emp-page .emp-filtros input, .nomina-emp-page .emp-filtros select {
    border: 1px solid var(--nom-border); border-radius: 10px; padding: 9px 12px; font-size: 16px; background: #fff; color: var(--nom-text);
}
.nomina-emp-page .emp-nuevo-btn {
    display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; text-decoration: none;
    background: linear-gradient(135deg, var(--nom-gold), color-mix(in srgb, var(--nom-gold) 76%, #000)); color: #fff;
    border: 1px solid transparent; border-radius: 10px; padding: 10px 16px; font-size: 14px; font-weight: 700;
    box-shadow: 0 12px 26px -12px color-mix(in srgb, var(--nom-gold) 60%, transparent); transition: transform .15s ease;
}
.nomina-emp-page .emp-nuevo-btn:hover { transform: translateY(-1px); color: #fff; }
@media (max-width: 560px) {
    .nomina-emp-page .emp-toolbar { flex-direction: column; align-items: stretch; }
    .nomina-emp-page .emp-nuevo-btn { justify-content: center; }
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
.nomina-emp-page .emp-badge.sin-grupo { background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-emp-page .emp-badge.sin-grupo i { margin-right: 4px; }
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
    <?php include APP_PATH . '/views/partials/filtros.php'; ?>

    <div class="emp-toolbar">
        <form method="GET" action="<?= url('nomina/empleados') ?>" class="msf-bar is-plain" data-auto-filter-form>
            <label class="msf-field msf-field--grow">
                <span class="msf-label">Buscar</span>
                <input class="msf-control" type="search" name="buscar" placeholder="Buscar empleado por nombre…" value="<?= htmlspecialchars($empBuscar) ?>">
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Estado</span>
                <select class="msf-control" name="estado"><?= msf_options(['activo' => 'Activos', 'inactivo' => 'Inactivos', 'baja' => 'Bajas', 'todos' => 'Todos'], $empEstado) ?></select>
            </label>
        </form>
        <?php if ($empPuedeCrear): ?>
        <a href="<?= url('nomina/empleados/crear') ?>" class="emp-nuevo-btn ms-pressable"><i class="fas fa-user-plus"></i> Nuevo empleado</a>
        <?php endif; ?>
    </div>

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
                    <td>
                        <?php if ($e['grupo_nomina'] !== null): ?>
                            <?= htmlspecialchars($e['grupo_nomina']) ?>
                            <?php if ((string) $e['estado'] === 'activo' && empty($e['tiene_salario'])): ?>
                                <span class="emp-badge sin-grupo" title="Sin salario registrado en nómina: entraría a los periodos con sueldo $0. Regístralo en su ficha."><i class="fas fa-triangle-exclamation"></i>Sin salario</span>
                            <?php endif; ?>
                        <?php elseif ((string) $e['estado'] === 'activo'): ?>
                            <span class="emp-badge sin-grupo" title="Sin grupo de pago no entra a los periodos de nómina. Asígnaselo en su ficha."><i class="fas fa-triangle-exclamation"></i>Sin grupo de pago</span>
                        <?php else: ?>
                            <span class="emp-sin-dato"><?= htmlspecialchars(ucfirst((string) ($e['periodicidad_pago'] ?? '—'))) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $e['tipo_contrato'] !== null ? htmlspecialchars($e['tipo_contrato']) : '<span class="emp-sin-dato">—</span>' ?></td>
                    <td><span class="emp-badge <?= htmlspecialchars((string) $e['estado']) ?>"><?= htmlspecialchars(ucfirst((string) $e['estado'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
