<?php
$hotel = $hotel ?? [];
$usuariosHotel = $usuariosHotel ?? [];
$modulosHotel = $modulosHotel ?? [];
$planes = $planes ?? [];
$planActual = $planActual ?? null;
$modulosPorPlan = $modulosPorPlan ?? [];
$auditoriaPlan = $auditoriaPlan ?? [
    'estado' => 'sin_plan',
    'mensaje' => 'No hay auditoria disponible.',
    'modulos_incluidos_apagados' => [],
    'modulos_activos_fuera_plan' => [],
];
$activo = !empty($hotel['activo']);
$fila = function ($label, $value) {
    $value = $value === null || $value === '' ? '-' : $value;
    ?>
    <div class="py-3 grid grid-cols-1 sm:grid-cols-3 gap-1 border-b border-gray-100">
        <dt class="text-sm font-medium text-gray-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
        <dd class="sm:col-span-2 text-sm text-gray-900"><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></dd>
    </div>
    <?php
};
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($hotel['nombre'] ?? 'Hotel', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mt-2 text-sm text-gray-600">Detalle minimo para administracion SaaS.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= url('admin/saas/hoteles') ?>" class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-white">
                Volver
            </a>
            <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar') ?>" class="px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                Editar
            </a>
        </div>
    </div>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div class="mb-4 rounded-md border px-4 py-3 text-sm <?= $tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Datos del hotel</h2>
                <p class="text-sm text-gray-500">ID <?= (int) ($hotel['id'] ?? 0) ?> · Slug <?= htmlspecialchars($hotel['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1 text-sm font-semibold <?= $activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' ?>">
                <?= $activo ? 'Activo' : 'Inactivo' ?>
            </span>
        </div>

        <dl class="px-6 py-2">
            <?php
            $fila('Nombre comercial', $hotel['nombre'] ?? null);
            $fila('Slug', $hotel['slug'] ?? null);
            $fila('Codigo interno', $hotel['codigo'] ?? null);
            $fila('Razon social', $hotel['razon_social'] ?? null);
            $fila('RFC', $hotel['rfc'] ?? null);
            $fila('Telefono', $hotel['telefono'] ?? null);
            $fila('Email', $hotel['email'] ?? null);
            $fila('Direccion', $hotel['direccion'] ?? null);
            $fila('Ciudad', $hotel['ciudad'] ?? null);
            $fila('Estado / region', $hotel['estado'] ?? null);
            $fila('Pais', $hotel['pais'] ?? null);
            $fila('Zona horaria', $hotel['zona_horaria'] ?? null);
            $fila('Moneda', trim(($hotel['moneda_codigo'] ?? '') . ' ' . ($hotel['moneda_simbolo'] ?? '')));
            $fila('Plan comercial', $planActual['nombre'] ?? 'Sin plan');
            $fila('Creado', $hotel['created_at'] ?? null);
            $fila('Actualizado', $hotel['updated_at'] ?? null);
            ?>
        </dl>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/estado') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="activo" value="<?= $activo ? '0' : '1' ?>">
                <button type="submit"
                        class="px-4 py-2 rounded-md text-sm font-medium <?= $activo ? 'bg-gray-700 text-white hover:bg-gray-800' : 'bg-green-700 text-white hover:bg-green-800' ?>">
                    <?= $activo ? 'Suspender hotel' : 'Activar hotel' ?>
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Plan comercial</h2>
            <p class="text-sm text-gray-500">El plan define un preset comercial. El acceso real sigue dependiendo de los modulos activos del hotel.</p>
        </div>

        <?php if (empty($planes)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">
                No hay catalogo de planes disponible. Aplique la migracion de planes antes de configurar esta seccion.
            </div>
        <?php else: ?>
            <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/plan') ?>">
                <?= csrf_field() ?>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div class="lg:col-span-1">
                        <label for="plan_id" class="block text-sm font-medium text-gray-700">Plan actual</label>
                        <select id="plan_id" name="plan_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            <?php foreach ($planes as $plan): ?>
                                <option value="<?= (int) $plan['id'] ?>" <?= !empty($planActual['id']) && (int) $planActual['id'] === (int) $plan['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plan['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="mt-4 flex items-start gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="aplicar_modulos" value="1"
                                   class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <span>
                                Aplicar modulos sugeridos por el plan.
                                <span class="block text-xs text-amber-700">
                                    Esto puede activar o desactivar modulos segun el preset seleccionado.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach ($planes as $plan): ?>
                                <?php
                                $planId = (int) $plan['id'];
                                $clavesPlan = array_column($modulosPorPlan[$planId] ?? [], 'clave');
                                ?>
                                <div class="rounded-md border border-gray-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <h3 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($plan['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                                        <?php if (!empty($planActual['id']) && (int) $planActual['id'] === $planId): ?>
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Actual</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500"><?= htmlspecialchars($plan['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php if (($plan['clave'] ?? '') === 'personalizado'): ?>
                                        <p class="mt-3 text-xs text-gray-600">No fuerza modulos. Use la seccion manual de modulos activos.</p>
                                    <?php else: ?>
                                        <p class="mt-3 text-xs font-medium text-gray-700">Preset:</p>
                                        <p class="mt-1 text-xs text-gray-600"><?= htmlspecialchars(implode(', ', $clavesPlan), ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                        Guardar plan
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php
    $estadoAuditoria = $auditoriaPlan['estado'] ?? 'sin_plan';
    $estadoClase = [
        'consistente' => 'bg-green-100 text-green-800',
        'diferencias' => 'bg-amber-100 text-amber-800',
        'personalizado' => 'bg-blue-100 text-blue-800',
        'sin_plan' => 'bg-gray-100 text-gray-700',
    ][$estadoAuditoria] ?? 'bg-gray-100 text-gray-700';
    $estadoTexto = [
        'consistente' => 'Consistente',
        'diferencias' => 'Con diferencias',
        'personalizado' => 'Personalizado',
        'sin_plan' => 'Sin plan',
    ][$estadoAuditoria] ?? 'No disponible';
    $modulosApagados = $auditoriaPlan['modulos_incluidos_apagados'] ?? [];
    $modulosFueraPlan = $auditoriaPlan['modulos_activos_fuera_plan'] ?? [];
    ?>
    <div class="mt-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Consistencia del plan</h2>
                <p class="text-sm text-gray-500">Auditoria no bloqueante entre el plan comercial y los modulos activos reales.</p>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1 text-sm font-semibold <?= $estadoClase ?>">
                <?= htmlspecialchars($estadoTexto, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>

        <div class="px-6 py-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-md border border-gray-200 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Plan actual</div>
                    <div class="mt-1 text-sm font-medium text-gray-900"><?= htmlspecialchars($planActual['nombre'] ?? 'Sin plan', ENT_QUOTES, 'UTF-8') ?></div>
                    <p class="mt-2 text-xs text-gray-600"><?= htmlspecialchars($auditoriaPlan['mensaje'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="rounded-md border border-gray-200 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Incluidos pero apagados</div>
                    <?php if (empty($modulosApagados)): ?>
                        <p class="mt-2 text-sm text-gray-600">Sin diferencias.</p>
                    <?php else: ?>
                        <ul class="mt-2 space-y-1 text-sm text-gray-800">
                            <?php foreach ($modulosApagados as $modulo): ?>
                                <li><?= htmlspecialchars($modulo['nombre'] ?? $modulo['clave'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="rounded-md border border-gray-200 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Activos fuera del plan</div>
                    <?php if (empty($modulosFueraPlan)): ?>
                        <p class="mt-2 text-sm text-gray-600">Sin diferencias.</p>
                    <?php else: ?>
                        <ul class="mt-2 space-y-1 text-sm text-gray-800">
                            <?php foreach ($modulosFueraPlan as $modulo): ?>
                                <li><?= htmlspecialchars($modulo['nombre'] ?? $modulo['clave'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($estadoAuditoria === 'diferencias' && !empty($planActual['id'])): ?>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-amber-800">Reaplicar el preset puede activar o desactivar modulos para coincidir con el plan actual.</p>
                <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/plan') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan_id" value="<?= (int) $planActual['id'] ?>">
                    <input type="hidden" name="aplicar_modulos" value="1">
                    <button type="submit" class="px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                        Reaplicar preset del plan
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
    <div class="mt-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Modulos activos</h2>
            <p class="text-sm text-gray-500">Base inicial para habilitar o deshabilitar secciones por hotel.</p>
        </div>

        <?php if (empty($modulosHotel)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">
                No hay catalogo de modulos disponible. Aplique la migracion de modulos antes de configurar esta seccion.
            </div>
        <?php else: ?>
            <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/modulos') ?>">
                <?= csrf_field() ?>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Activo</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Modulo</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Categoria</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ruta base</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Global</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($modulosHotel as $modulo): ?>
                                <?php $globalActivo = !empty($modulo['activo_global']); ?>
                                <tr class="<?= $globalActivo ? '' : 'bg-gray-50 text-gray-500' ?>">
                                    <td class="px-4 py-3 text-sm">
                                        <input type="checkbox"
                                               name="modulos[]"
                                               value="<?= (int) $modulo['id'] ?>"
                                               <?= !empty($modulo['activo_hotel']) ? 'checked' : '' ?>
                                               <?= $globalActivo ? '' : 'disabled' ?>
                                               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="font-medium"><?= htmlspecialchars($modulo['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($modulo['clave'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!empty($modulo['descripcion'])): ?>
                                            <div class="mt-1 text-xs text-gray-500"><?= htmlspecialchars($modulo['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($modulo['categoria'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($modulo['ruta_base'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold <?= $globalActivo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' ?>">
                                            <?= $globalActivo ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                        Guardar modulos
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="mt-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Usuarios administradores del hotel</h2>
            <p class="text-sm text-gray-500">Usuarios vinculados a este hotel para acceso hotel-aware.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Usuario</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rol hotel</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rol global</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Principal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($usuariosHotel)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-sm text-gray-600 text-center">
                                Este hotel aun no tiene usuarios administradores vinculados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuariosHotel as $usuarioHotel): ?>
                            <?php
                            $hotelUsuarioActivo = !empty($usuarioHotel['hotel_usuario_activo']);
                            $usuarioActivo = !empty($usuarioHotel['usuario_activo']);
                            ?>
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="font-medium"><?= htmlspecialchars($usuarioHotel['nombre_completo'] ?: $usuarioHotel['nombre_usuario'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($usuarioHotel['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($usuarioHotel['email'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($usuarioHotel['rol_hotel'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($usuarioHotel['rol_global'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold <?= ($hotelUsuarioActivo && $usuarioActivo) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' ?>">
                                        <?= ($hotelUsuarioActivo && $usuarioActivo) ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?= !empty($usuarioHotel['es_principal']) ? 'Si' : 'No' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/usuarios') ?>" class="border-t border-gray-200 bg-gray-50">
            <?= csrf_field() ?>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="nombre_usuario" class="block text-sm font-medium text-gray-700">Usuario *</label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" required maxlength="80"
                           value="<?= old('nombre_usuario') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                    <p class="mt-1 text-xs text-gray-500">Si ya existe, se vincula al hotel sin cambiar su contrasena.</p>
                </div>

                <div>
                    <label for="rol_hotel" class="block text-sm font-medium text-gray-700">Rol hotelero *</label>
                    <select id="rol_hotel" name="rol_hotel" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        <option value="administrador" <?= old('rol_hotel', 'administrador') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                        <option value="gerente" <?= old('rol_hotel') === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                    </select>
                </div>

                <div>
                    <label for="nombre_completo" class="block text-sm font-medium text-gray-700">Nombre completo</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" maxlength="150"
                           value="<?= old('nombre_completo') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" maxlength="120"
                           value="<?= old('email') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contrasena temporal</label>
                    <input type="password" id="password" name="password" minlength="10" autocomplete="new-password"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div class="flex flex-col justify-end gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="es_principal" value="1" <?= old('es_principal') ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        Marcar como principal
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="activo" value="1" <?= old('activo', '1') ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        Vinculo activo
                    </label>
                </div>
            </div>

            <div class="px-6 py-4 bg-white border-t border-gray-200 flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                    Crear o vincular administrador
                </button>
            </div>
        </form>
    </div>
</div>
