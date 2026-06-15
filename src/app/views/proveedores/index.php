<?php
$proveedores = $proveedores ?? [];
$resumen = $resumen ?? ['total' => 0, 'activos' => 0, 'inactivos' => 0];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('prov_safe')) {
    function prov_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('prov_url')) {
    function prov_url(array $overrides = [])
    {
        $query = array_merge($_GET, $overrides);
        $query = array_filter($query, static function ($value) {
            return $value !== null && $value !== '';
        });

        return url('proveedores' . (!empty($query) ? '?' . http_build_query($query) : ''));
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'activos');
?>

<style>
.providers-page {
    --prov-brand: var(--brand-primary, #1f3f46);
    --prov-accent: var(--brand-accent, #b58a3c);
    --prov-line: color-mix(in srgb, var(--prov-brand) 10%, #e5e7eb);
    --prov-soft: color-mix(in srgb, var(--prov-accent) 7%, #f8fafc);
    color: #243142;
}
.providers-page .provider-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--prov-brand) 92%, #111827), color-mix(in srgb, var(--prov-accent) 58%, #5b4730));
    border-radius: 0;
    color: #fff;
    padding: 28px;
}
.providers-page .provider-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.providers-page .provider-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.providers-page .provider-subtitle {
    margin-top: 8px;
    max-width: 48rem;
    color: rgba(255,255,255,.86);
}
.providers-page .provider-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.providers-page .provider-panel {
    border: 1px solid var(--prov-line);
    background: rgba(255,255,255,.9);
}
.providers-page .provider-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--prov-line);
    font-weight: 800;
}
.providers-page .provider-btn-primary {
    background: var(--prov-brand);
    border-color: var(--prov-brand);
    color: #fff;
}
.providers-page .provider-btn-muted {
    background: #fff;
    color: #334155;
}
.providers-page .provider-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--prov-line);
    background: #fff;
    padding: 0 12px;
}
.providers-page .provider-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.providers-page .provider-table td,
.providers-page .provider-table th {
    border-bottom: 1px solid var(--prov-line);
    padding: 14px 12px;
}
.providers-page .provider-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--prov-line);
    background: var(--prov-soft);
    font-size: .78rem;
    font-weight: 800;
}
</style>

<div class="providers-page">
    <section class="provider-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="provider-kicker">Catalogo operativo</div>
                <h1 class="provider-title">Proveedores</h1>
                <p class="provider-subtitle">
                    Directorio por hotel para preparar compras futuras sin tocar caja, pagos ni recepcion de inventario.
                </p>
            </div>
            <div class="grid grid-cols-3 gap-2 min-w-[280px]">
                <div class="provider-stat">
                    <div class="text-xs opacity-75">Total</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="provider-stat">
                    <div class="text-xs opacity-75">Activos</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['activos'] ?? 0) ?></div>
                </div>
                <div class="provider-stat">
                    <div class="text-xs opacity-75">Inactivos</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['inactivos'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <?php if (!$tablaDisponible): ?>
            <div class="provider-panel p-5">
                <strong>Tabla no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Ejecuta la migracion de Fase 2F antes de usar este catalogo.</p>
            </div>
        <?php else: ?>
            <div class="provider-panel p-4 mb-4">
                <form method="GET" action="<?= url('proveedores') ?>" class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto_auto] gap-3">
                    <input class="provider-input" type="search" name="buscar" value="<?= prov_safe($buscar, '') ?>" placeholder="Buscar por nombre, RFC, telefono o correo">
                    <select class="provider-input" name="estado">
                        <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                        <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                    </select>
                    <button class="provider-btn provider-btn-muted" type="submit">
                        <i class="fas fa-search"></i>
                        Filtrar
                    </button>
                    <a class="provider-btn provider-btn-primary" href="<?= url('proveedores/crear') ?>">
                        <i class="fas fa-plus"></i>
                        Nuevo
                    </a>
                </form>
            </div>

            <div class="provider-panel overflow-hidden">
                <?php if (empty($proveedores)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-truck-field"></i></div>
                        <h2 class="font-black text-lg">No hay proveedores en esta vista</h2>
                        <p class="text-sm text-slate-500 mt-1">Crea el primer proveedor del hotel o cambia los filtros.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="provider-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Proveedor</th>
                                    <th class="text-left">Contacto</th>
                                    <th class="text-left">RFC</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <?php $activo = (int)($proveedor['activo'] ?? 0) === 1; ?>
                                    <tr>
                                        <td>
                                            <div class="font-black text-slate-800"><?= prov_safe($proveedor['nombre']) ?></div>
                                            <div class="text-xs text-slate-500"><?= prov_safe($proveedor['razon_social']) ?></div>
                                        </td>
                                        <td>
                                            <div><?= prov_safe($proveedor['telefono']) ?></div>
                                            <div class="text-xs text-slate-500"><?= prov_safe($proveedor['email']) ?></div>
                                        </td>
                                        <td><?= prov_safe($proveedor['rfc']) ?></td>
                                        <td>
                                            <span class="provider-badge">
                                                <i class="fas <?= $activo ? 'fa-check-circle' : 'fa-pause-circle' ?>"></i>
                                                <?= $activo ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex justify-end gap-2">
                                                <a class="provider-btn provider-btn-muted" href="<?= url('proveedores/' . (int)$proveedor['id'] . '/editar') ?>" title="Editar">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <?php if ($activo): ?>
                                                    <form method="POST" action="<?= url('proveedores/' . (int)$proveedor['id'] . '/desactivar') ?>">
                                                        <?= csrf_field() ?>
                                                        <button class="provider-btn provider-btn-muted" type="submit" title="Desactivar">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="<?= url('proveedores/' . (int)$proveedor['id'] . '/reactivar') ?>">
                                                        <?= csrf_field() ?>
                                                        <button class="provider-btn provider-btn-muted" type="submit" title="Reactivar">
                                                            <i class="fas fa-rotate-left"></i>
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
