<?php
$trabajadores = $trabajadores ?? [];
$resumen = $resumen ?? ['total' => 0, 'activos' => 0, 'inactivos' => 0, 'baja' => 0];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('trab_safe')) {
    function trab_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_money')) {
    function trab_money($value)
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return '$' . number_format((float)$value, 2);
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'activos');
?>

<style>
.workers-page {
    --trab-brand: var(--brand-primary, #1f3f46);
    --trab-accent: var(--brand-accent, #b58a3c);
    --trab-line: color-mix(in srgb, var(--trab-brand) 10%, #e5e7eb);
    --trab-soft: color-mix(in srgb, var(--trab-accent) 7%, #f8fafc);
    color: #243142;
}
.workers-page .worker-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--trab-brand) 92%, #111827), color-mix(in srgb, var(--trab-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.workers-page .worker-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.workers-page .worker-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.workers-page .worker-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.workers-page .worker-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.workers-page .worker-panel {
    border: 1px solid var(--trab-line);
    background: rgba(255,255,255,.92);
}
.workers-page .worker-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--trab-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
}
.workers-page .worker-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--trab-line);
    background: #fff;
    padding: 0 12px;
}
.workers-page .worker-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.workers-page .worker-table td,
.workers-page .worker-table th {
    border-bottom: 1px solid var(--trab-line);
    padding: 14px 12px;
}
.workers-page .worker-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--trab-line);
    background: var(--trab-soft);
    font-size: .78rem;
    font-weight: 800;
}
</style>

<div class="workers-page">
    <section class="worker-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="worker-kicker">Administracion / Personal</div>
                <h1 class="worker-title">Personal</h1>
                <p class="worker-subtitle">
                    Directorio laboral por hotel en modo lectura. No registra nomina, pagos, descuentos ni movimientos de caja.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[340px]">
                <div class="worker-stat">
                    <div class="text-xs opacity-75">Total</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="worker-stat">
                    <div class="text-xs opacity-75">Activos</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['activos'] ?? 0) ?></div>
                </div>
                <div class="worker-stat">
                    <div class="text-xs opacity-75">Inactivos</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['inactivos'] ?? 0) ?></div>
                </div>
                <div class="worker-stat">
                    <div class="text-xs opacity-75">Baja</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['baja'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <?php if (!$tablaDisponible): ?>
            <div class="worker-panel p-5">
                <strong>Personal no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">La migracion base de Personal aun no esta aplicada en esta instalacion.</p>
            </div>
        <?php else: ?>
            <div class="worker-panel p-4 mb-4">
                <form method="GET" action="<?= url('trabajadores') ?>" class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto_auto] gap-3">
                    <input class="worker-input" type="search" name="buscar" value="<?= trab_safe($buscar, '') ?>" placeholder="Buscar por nombre, identificacion, rol, telefono o correo">
                    <select class="worker-input" name="estado">
                        <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                        <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="baja" <?= $estado === 'baja' ? 'selected' : '' ?>>Baja</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                    </select>
                    <button class="worker-btn" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <a class="worker-btn" href="<?= url('trabajadores/crear') ?>">
                        <i class="fas fa-plus"></i>
                        Nuevo
                    </a>
                </form>
            </div>

            <div class="worker-panel overflow-hidden">
                <?php if (empty($trabajadores)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-id-card"></i></div>
                        <h2 class="font-black text-lg">No hay trabajadores en esta vista</h2>
                        <p class="text-sm text-slate-500 mt-1">La estructura esta lista para consultar personal cuando existan registros del hotel.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="worker-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Trabajador</th>
                                    <th class="text-left">Rol</th>
                                    <th class="text-left">Contacto</th>
                                    <th class="text-left">Alta</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Salario base</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trabajadores as $trabajador): ?>
                                    <tr>
                                        <td>
                                            <a class="font-black text-slate-800 underline" href="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0)) ?>">
                                                <?= trab_safe($trabajador['nombre_completo'] ?? null) ?>
                                            </a>
                                            <div class="text-xs text-slate-500"><?= trab_safe($trabajador['identificacion'] ?? null, 'Sin identificacion') ?></div>
                                        </td>
                                        <td><?= trab_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?></td>
                                        <td>
                                            <div><?= trab_safe($trabajador['telefono'] ?? null) ?></div>
                                            <div class="text-xs text-slate-500"><?= trab_safe($trabajador['email'] ?? null) ?></div>
                                        </td>
                                        <td><?= trab_safe($trabajador['fecha_alta'] ?? null, 'Sin fecha') ?></td>
                                        <td>
                                            <span class="worker-badge">
                                                <i class="fas fa-circle-dot"></i>
                                                <?= trab_safe($trabajador['estado'] ?? null) ?>
                                            </span>
                                        </td>
                                        <td class="text-right"><?= trab_money($trabajador['salario_base'] ?? null) ?></td>
                                        <td class="text-right">
                                            <div class="flex justify-end gap-2">
                                                <a class="worker-btn" href="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0)) ?>" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a class="worker-btn" href="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0) . '/editar') ?>" title="Editar">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <?php if (($trabajador['estado'] ?? '') === 'baja'): ?>
                                                    <form method="POST" action="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0) . '/reactivar') ?>">
                                                        <?= csrf_field() ?>
                                                        <button class="worker-btn" type="submit" title="Reactivar">
                                                            <i class="fas fa-rotate-left"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0) . '/baja-logica') ?>" onsubmit="return confirm('Confirmar baja logica del trabajador. No se borrara el registro.');">
                                                        <?= csrf_field() ?>
                                                        <button class="worker-btn" type="submit" title="Baja logica">
                                                            <i class="fas fa-user-slash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
