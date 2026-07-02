<?php
$modulos = $modulos ?? [];
$planes = $planes ?? [];

// Sin separador de miles: estos valores van a value="" de <input type="number">.
$fmtMoney = function ($v): string {
    return number_format((float) $v, 2, '.', '');
};

$modulosCore = array_filter($modulos, function ($m) { return !empty($m['es_core']); });
$modulosOpcionales = array_filter($modulos, function ($m) { return empty($m['es_core']); });
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold" style="color:var(--ms-text);">Bloques y precios</h1>
            <p class="mt-1 text-sm" style="color:var(--ms-muted);">Catálogo comercial: define qué incluye el paquete básico y cuánto cuesta cada bloque opcional.</p>
        </div>
        <a href="<?= url('admin/saas/hoteles') ?>"
           class="inline-flex items-center justify-center gap-1.5 rounded-md border px-4 py-2 text-sm font-medium transition hover:bg-slate-50"
           style="border-color:var(--ms-border);color:var(--ms-text);">
            <i class="fas fa-arrow-left text-xs" style="color:var(--ms-muted);"></i>
            Volver a hoteles
        </a>
    </div>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div class="mb-4 rounded-md border px-4 py-3 text-sm <?= $tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <!-- Precios de planes -->
    <div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Planes comerciales</h2>
            <p class="text-sm text-gray-500">El plan <span class="font-semibold">Básico</span> es la base del cobro mensual à la carte: básico + suma de bloques opcionales activos por hotel.</p>
        </div>
        <?php if (empty($planes)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">No hay planes disponibles. Aplique la migración de planes.</div>
        <?php else: ?>
            <form method="POST" action="<?= url('admin/saas/planes/precios') ?>">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                    <?php foreach ($planes as $plan): ?>
                        <div class="rounded-lg border px-4 py-3" style="border-color:var(--ms-border);">
                            <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($plan['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-xs text-gray-500 mb-2"><?= htmlspecialchars($plan['clave'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (($plan['clave'] ?? '') === 'personalizado'): ?>
                                <div class="text-sm text-gray-500">Sin precio fijo (à la carte)</div>
                            <?php else: ?>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500">$</span>
                                    <input type="number" step="0.01" min="0"
                                           name="precios_planes[<?= (int) $plan['id'] ?>]"
                                           value="<?= $fmtMoney($plan['precio_mensual'] ?? 0) ?>"
                                           class="w-full rounded-md border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                                    <span class="text-xs text-gray-500"><?= htmlspecialchars($plan['moneda_codigo'] ?? 'MXN', ENT_QUOTES, 'UTF-8') ?>/mes</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-md text-white text-sm font-medium transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                        Guardar precios de planes
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Paquete basico -->
    <div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Paquete básico (incluido, no desactivable)</h2>
            <p class="text-sm text-gray-500">Lo mínimo indispensable para operar un hotel. Su costo está cubierto por el precio del plan Básico.</p>
        </div>
        <div class="flex flex-wrap gap-2 px-6 py-5">
            <?php foreach ($modulosCore as $modulo): ?>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold" style="background:rgba(37,99,235,.10);color:var(--ms-primary);">
                    <i class="fas fa-lock text-[10px]"></i>
                    <?= htmlspecialchars($modulo['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endforeach; ?>
            <?php if (empty($modulosCore)): ?>
                <span class="text-sm text-gray-600">Aún no hay módulos core. Aplique la migración de precios de módulos.</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bloques opcionales -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Bloques opcionales</h2>
            <p class="text-sm text-gray-500">Precio mensual default de cada bloque. Puede sobreescribirse por hotel desde su detalle.</p>
        </div>
        <?php if (empty($modulosOpcionales)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">No hay bloques opcionales en el catálogo.</div>
        <?php else: ?>
            <form method="POST" action="<?= url('admin/saas/modulos/precios') ?>">
                <?= csrf_field() ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bloque</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Categoría</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio mensual (MXN)</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Global</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($modulosOpcionales as $modulo): ?>
                                <?php $globalActivo = !empty($modulo['activo_global']); ?>
                                <tr class="<?= $globalActivo ? '' : 'bg-gray-50 text-gray-500' ?>">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="font-medium"><?= htmlspecialchars($modulo['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($modulo['clave'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!empty($modulo['descripcion'])): ?>
                                            <div class="mt-1 text-xs text-gray-500"><?= htmlspecialchars($modulo['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($modulo['categoria'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-500">$</span>
                                            <input type="number" step="0.01" min="0"
                                                   name="precios[<?= (int) $modulo['id'] ?>]"
                                                   value="<?= $fmtMoney($modulo['precio_mensual'] ?? 0) ?>"
                                                   class="w-28 rounded-md border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" style="<?= $globalActivo ? 'background:rgba(22,163,74,.12);color:var(--ms-success);' : 'background:rgba(100,116,139,.10);color:var(--ms-muted);' ?>">
                                            <?= $globalActivo ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-md text-white text-sm font-medium transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                        Guardar precios de bloques
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
