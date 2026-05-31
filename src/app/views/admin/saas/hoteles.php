<?php
$hoteles = $hoteles ?? [];
$totalHoteles = count($hoteles);
$hotelesActivos = count(array_filter($hoteles, function ($hotel) {
    return !empty($hotel['activo']);
}));
$hotelesInactivos = max(0, $totalHoteles - $hotelesActivos);

$badgeEstado = function (array $hotel): array {
    if (empty($hotel['activo'])) {
        return ['Inactivo', 'background:rgba(100,116,139,.12);color:var(--ms-muted);'];
    }

    return ['Activo', 'background:rgba(22,163,74,.12);color:var(--ms-success);'];
};

$esHotelDemo = function (array $hotel): bool {
    $texto = strtolower(($hotel['slug'] ?? '') . ' ' . ($hotel['codigo'] ?? '') . ' ' . ($hotel['nombre'] ?? ''));
    return strpos($texto, 'demo') !== false;
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Panel Medisoft interno - Hoteles</h1>
            <p class="mt-2 text-sm text-gray-600">Administración mínima de hoteles/clientes registrados.</p>
        </div>
        <a href="<?= url('admin/saas/hoteles/crear') ?>"
           class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-px hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
           style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
            Nuevo hotel
        </a>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border bg-white px-4 py-3" style="border-color:var(--ms-border);">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Hoteles registrados</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900"><?= (int) $totalHoteles ?></div>
        </div>
        <div class="rounded-lg border bg-white px-4 py-3" style="border-color:var(--ms-border);">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Activos</div>
            <div class="mt-1 text-2xl font-semibold" style="color:var(--ms-success);"><?= (int) $hotelesActivos ?></div>
        </div>
        <div class="rounded-lg border bg-white px-4 py-3" style="border-color:var(--ms-border);">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Inactivos</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900"><?= (int) $hotelesInactivos ?></div>
        </div>
    </div>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div class="mb-4 rounded-md border px-4 py-3 text-sm <?= $tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="hidden bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden md:block">
        <div class="border-b px-5 py-4" style="border-color:var(--ms-border);">
            <h2 class="text-base font-semibold text-gray-900">Clientes hoteleros</h2>
            <p class="mt-1 text-sm text-gray-600">Listado operativo para soporte, ventas y administración SaaS.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Cliente</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Código</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Moneda</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Creado</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($hoteles)): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-sm text-gray-600 text-center">
                                No hay hoteles registrados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hoteles as $hotel): ?>
                            <?php [$estadoTexto, $estadoStyle] = $badgeEstado($hotel); ?>
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-5 py-4 text-sm">
                                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($hotel['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                                        <span>ID <?= (int) ($hotel['id'] ?? 0) ?></span>
                                        <span aria-hidden="true">·</span>
                                        <span><?= htmlspecialchars($hotel['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?= htmlspecialchars(($hotel['codigo'] ?? '') ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" style="<?= $estadoStyle ?>">
                                        <?= htmlspecialchars($estadoTexto, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if ($esHotelDemo($hotel)): ?>
                                        <span class="ml-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" style="background:rgba(6,182,212,.12);color:var(--ms-accent);">
                                            Demo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?= htmlspecialchars(($hotel['moneda_codigo'] ?? '') ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?= htmlspecialchars($hotel['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 text-sm text-right whitespace-nowrap">
                                    <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id']) ?>" class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50" style="border-color:var(--ms-border);">Ver</a>
                                    <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar') ?>" class="ml-2 inline-flex items-center rounded-md px-3 py-1.5 text-xs font-semibold hover:bg-slate-100" style="color:var(--ms-primary);">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-3 md:hidden">
        <?php if (empty($hoteles)): ?>
            <div class="rounded-lg border bg-white px-4 py-6 text-center text-sm text-gray-600" style="border-color:var(--ms-border);">
                No hay hoteles registrados.
            </div>
        <?php else: ?>
            <?php foreach ($hoteles as $hotel): ?>
                <?php [$estadoTexto, $estadoStyle] = $badgeEstado($hotel); ?>
                <article class="rounded-lg border bg-white p-4 shadow-sm" style="border-color:var(--ms-border);">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900"><?= htmlspecialchars($hotel['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="mt-1 text-xs text-gray-600"><?= htmlspecialchars($hotel['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" style="<?= $estadoStyle ?>">
                            <?= htmlspecialchars($estadoTexto, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Código</div>
                            <div class="mt-1 text-gray-900"><?= htmlspecialchars(($hotel['codigo'] ?? '') ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Moneda</div>
                            <div class="mt-1 text-gray-900"><?= htmlspecialchars(($hotel['moneda_codigo'] ?? '') ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <?php if ($esHotelDemo($hotel)): ?>
                        <div class="mt-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" style="background:rgba(6,182,212,.12);color:var(--ms-accent);">Demo</span>
                        </div>
                    <?php endif; ?>
                    <div class="mt-4 flex gap-2">
                        <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id']) ?>" class="inline-flex flex-1 items-center justify-center rounded-md border px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" style="border-color:var(--ms-border);">Ver</a>
                        <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar') ?>" class="inline-flex flex-1 items-center justify-center rounded-md px-3 py-2 text-sm font-semibold hover:bg-slate-100" style="color:var(--ms-primary);">Editar</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
