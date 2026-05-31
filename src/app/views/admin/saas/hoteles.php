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

$fmtFecha = function ($d): string {
    if (!$d) return '—';
    $ts = strtotime((string) $d);
    return $ts ? date('d/m/Y', $ts) : (string) $d;
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold" style="color:var(--ms-text);">Hoteles y clientes</h1>
            <p class="mt-1 text-sm" style="color:var(--ms-muted);">Registro, configuración y seguimiento de clientes SaaS.</p>
        </div>
        <a href="<?= url('admin/saas/hoteles/crear') ?>"
           class="inline-flex items-center justify-center gap-1.5 rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-px hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
           style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
            <i class="fas fa-plus text-xs"></i>
            Nuevo hotel
        </a>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border bg-white flex items-center gap-3 px-4 py-3" style="border-color:var(--ms-border);">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(37,99,235,.10);">
                <i class="fas fa-building text-sm" style="color:var(--ms-primary);"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Registrados</div>
                <div class="mt-0.5 text-2xl font-semibold" style="color:var(--ms-text);"><?= (int) $totalHoteles ?></div>
            </div>
        </div>
        <div class="rounded-lg border bg-white flex items-center gap-3 px-4 py-3" style="border-color:var(--ms-border);">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(22,163,74,.10);">
                <i class="fas fa-circle-check text-sm" style="color:var(--ms-success);"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Activos</div>
                <div class="mt-0.5 text-2xl font-semibold" style="color:var(--ms-success);"><?= (int) $hotelesActivos ?></div>
            </div>
        </div>
        <div class="rounded-lg border bg-white flex items-center gap-3 px-4 py-3" style="border-color:var(--ms-border);">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(100,116,139,.10);">
                <i class="fas fa-circle-minus text-sm" style="color:var(--ms-muted);"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Inactivos</div>
                <div class="mt-0.5 text-2xl font-semibold" style="color:var(--ms-muted);"><?= (int) $hotelesInactivos ?></div>
            </div>
        </div>
    </div>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div class="mb-4 rounded-md border px-4 py-3 text-sm <?= $tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <!-- Desktop table -->
    <div class="hidden bg-white rounded-lg overflow-hidden shadow-sm md:block" style="border:1px solid var(--ms-border);">
        <div class="border-b px-5 py-4 flex items-center justify-between" style="border-color:var(--ms-border);">
            <div>
                <h2 class="text-sm font-semibold" style="color:var(--ms-text);">Clientes hoteleros</h2>
                <p class="mt-0.5 text-xs" style="color:var(--ms-muted);">Listado operativo para soporte, ventas y administración SaaS.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y" style="border-color:var(--ms-border);">
                <thead style="background:var(--ms-bg);">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Cliente</th>
                        <th scope="col" class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Código</th>
                        <th scope="col" class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Estado</th>
                        <th scope="col" class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Moneda</th>
                        <th scope="col" class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Alta</th>
                        <th scope="col" class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y" style="border-color:var(--ms-border);">
                    <?php if (empty($hoteles)): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-sm text-center" style="color:var(--ms-muted);">
                                <i class="fas fa-building mb-2 text-2xl opacity-25 block"></i>
                                No hay hoteles registrados aún.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hoteles as $hotel): ?>
                            <?php [$estadoTexto, $estadoStyle] = $badgeEstado($hotel); ?>
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-5 py-4 text-sm">
                                    <div class="font-semibold" style="color:var(--ms-text);"><?= htmlspecialchars($hotel['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs" style="color:var(--ms-muted);">
                                        <span>ID <?= (int) ($hotel['id'] ?? 0) ?></span>
                                        <span aria-hidden="true">·</span>
                                        <span class="font-mono"><?= htmlspecialchars($hotel['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm font-mono" style="color:var(--ms-text);"><?= htmlspecialchars(($hotel['codigo'] ?? '') ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="<?= $estadoStyle ?>">
                                        <?= htmlspecialchars($estadoTexto, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if ($esHotelDemo($hotel)): ?>
                                        <span class="ml-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background:rgba(6,182,212,.12);color:var(--ms-accent);">
                                            Demo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-sm font-mono" style="color:var(--ms-text);"><?= htmlspecialchars(($hotel['moneda_codigo'] ?? '') ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 text-sm" style="color:var(--ms-muted);"><?= htmlspecialchars($fmtFecha($hotel['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 text-sm text-right whitespace-nowrap">
                                    <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id']) ?>"
                                       class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90"
                                       style="background:var(--ms-primary);">
                                        <i class="fas fa-eye text-[10px]"></i> Ver
                                    </a>
                                    <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar') ?>"
                                       class="ml-2 inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-xs font-semibold transition hover:bg-slate-50"
                                       style="border-color:var(--ms-border);color:var(--ms-text);">
                                        <i class="fas fa-pencil text-[10px]"></i> Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile cards -->
    <div class="space-y-3 md:hidden">
        <?php if (empty($hoteles)): ?>
            <div class="rounded-lg border bg-white px-4 py-8 text-center text-sm" style="border-color:var(--ms-border);color:var(--ms-muted);">
                <i class="fas fa-building mb-2 text-2xl opacity-25 block"></i>
                No hay hoteles registrados aún.
            </div>
        <?php else: ?>
            <?php foreach ($hoteles as $hotel): ?>
                <?php [$estadoTexto, $estadoStyle] = $badgeEstado($hotel); ?>
                <article class="rounded-lg border bg-white p-4 shadow-sm" style="border-color:var(--ms-border);">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold" style="color:var(--ms-text);"><?= htmlspecialchars($hotel['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="mt-0.5 text-xs font-mono" style="color:var(--ms-muted);"><?= htmlspecialchars($hotel['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="<?= $estadoStyle ?>">
                                <?= htmlspecialchars($estadoTexto, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($esHotelDemo($hotel)): ?>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background:rgba(6,182,212,.12);color:var(--ms-accent);">Demo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <div class="font-semibold uppercase tracking-wider" style="color:var(--ms-muted);font-size:10px;">Código</div>
                            <div class="mt-0.5 font-mono" style="color:var(--ms-text);"><?= htmlspecialchars(($hotel['codigo'] ?? '') ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div>
                            <div class="font-semibold uppercase tracking-wider" style="color:var(--ms-muted);font-size:10px;">Moneda</div>
                            <div class="mt-0.5" style="color:var(--ms-text);"><?= htmlspecialchars(($hotel['moneda_codigo'] ?? '') ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id']) ?>"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                           style="background:var(--ms-primary);">
                            <i class="fas fa-eye text-xs"></i> Ver
                        </a>
                        <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar') ?>"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border px-3 py-2 text-sm font-semibold transition hover:bg-slate-50"
                           style="border-color:var(--ms-border);color:var(--ms-text);">
                            <i class="fas fa-pencil text-xs"></i> Editar
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
