<?php
$hotel = $hotel ?? null;
$modo = $modo ?? 'crear';
$action = $action ?? url('admin/saas/hoteles');
$esEdicion = $modo === 'editar';
$valor = function ($campo, $default = '') use ($hotel) {
    $base = $hotel[$campo] ?? $default;
    return old($campo, htmlspecialchars((string) $base, ENT_QUOTES, 'UTF-8'));
};
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= $esEdicion ? 'Editar hotel' : 'Crear hotel' ?></h1>
            <p class="mt-2 text-sm text-gray-600">
                <?= $esEdicion ? 'Actualiza los datos basicos del hotel.' : 'Crea el hotel en estado inactivo para completar configuracion antes de activarlo.' ?>
            </p>
        </div>
        <a href="<?= url('admin/saas/hoteles') ?>" class="text-sm font-medium text-gray-700 hover:text-gray-900">
            Volver al listado
        </a>
    </div>

    <?php if ($mensaje = get_mensaje()): ?>
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="bg-white border border-gray-200 rounded-lg shadow-sm">
        <?= csrf_field() ?>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre comercial *</label>
                <input type="text" id="nombre" name="nombre" required maxlength="150"
                       value="<?= $valor('nombre') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700">Slug *</label>
                <input type="text" id="slug" name="slug" required maxlength="120"
                       value="<?= $valor('slug') ?>"
                       placeholder="hotel-ejemplo"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                <p class="mt-1 text-xs text-gray-500">Solo minusculas, numeros y guiones. No iniciar ni terminar con guion.</p>
            </div>

            <div>
                <label for="codigo" class="block text-sm font-medium text-gray-700">Codigo interno</label>
                <input type="text" id="codigo" name="codigo" maxlength="50"
                       value="<?= $valor('codigo') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="telefono" class="block text-sm font-medium text-gray-700">Telefono</label>
                <input type="text" id="telefono" name="telefono" maxlength="30"
                       value="<?= $valor('telefono') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" maxlength="120"
                       value="<?= $valor('email') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div class="md:col-span-2">
                <label for="razon_social" class="block text-sm font-medium text-gray-700">Razon social</label>
                <input type="text" id="razon_social" name="razon_social" maxlength="180"
                       value="<?= $valor('razon_social') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="rfc" class="block text-sm font-medium text-gray-700">RFC</label>
                <input type="text" id="rfc" name="rfc" maxlength="20"
                       value="<?= $valor('rfc') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="direccion" class="block text-sm font-medium text-gray-700">Direccion</label>
                <input type="text" id="direccion" name="direccion" maxlength="255"
                       value="<?= $valor('direccion') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="ciudad" class="block text-sm font-medium text-gray-700">Ciudad</label>
                <input type="text" id="ciudad" name="ciudad" maxlength="100"
                       value="<?= $valor('ciudad') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700">Estado / region</label>
                <input type="text" id="estado" name="estado" maxlength="100"
                       value="<?= $valor('estado') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="pais" class="block text-sm font-medium text-gray-700">Pais</label>
                <input type="text" id="pais" name="pais" maxlength="100"
                       value="<?= $valor('pais', 'Mexico') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="zona_horaria" class="block text-sm font-medium text-gray-700">Zona horaria</label>
                <input type="text" id="zona_horaria" name="zona_horaria" maxlength="80"
                       value="<?= $valor('zona_horaria', 'America/Mexico_City') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="moneda_codigo" class="block text-sm font-medium text-gray-700">Moneda</label>
                <input type="text" id="moneda_codigo" name="moneda_codigo" maxlength="3"
                       value="<?= $valor('moneda_codigo', 'MXN') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm uppercase focus:border-gray-900 focus:ring-gray-900">
            </div>

            <div>
                <label for="moneda_simbolo" class="block text-sm font-medium text-gray-700">Simbolo moneda</label>
                <input type="text" id="moneda_simbolo" name="moneda_simbolo" maxlength="8"
                       value="<?= $valor('moneda_simbolo', '$') ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
            </div>

            <?php if ($esEdicion): ?>
                <div class="md:col-span-2 rounded-md bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-700">
                    Estado actual: <strong><?= !empty($hotel['activo']) ? 'Activo' : 'Inactivo' ?></strong>.
                    El cambio de estado se realiza desde el detalle del hotel.
                </div>
            <?php endif; ?>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <a href="<?= $esEdicion ? url('admin/saas/hoteles/' . (int) $hotel['id']) : url('admin/saas/hoteles') ?>"
               class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-white">
                Cancelar
            </a>
            <button type="submit" class="px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                <?= $esEdicion ? 'Guardar cambios' : 'Crear hotel inactivo' ?>
            </button>
        </div>
    </form>
</div>
