<?php
$hoteles = $hoteles ?? [];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Panel Medisoft interno - Hoteles</h1>
        <p class="mt-2 text-sm text-gray-600">Vista solo lectura de hoteles registrados.</p>
    </div>

    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nombre</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Slug</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Codigo</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Moneda</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Creado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($hoteles)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-sm text-gray-600 text-center">
                                No hay hoteles registrados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hoteles as $hotel): ?>
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= (int) ($hotel['id'] ?? 0) ?></td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= htmlspecialchars($hotel['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($hotel['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($hotel['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?= !empty($hotel['activo']) ? 'Activo' : 'Inactivo' ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($hotel['moneda_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($hotel['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
