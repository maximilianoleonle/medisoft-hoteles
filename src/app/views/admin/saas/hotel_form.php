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
                <?= $esEdicion ? 'Actualiza los datos básicos del hotel.' : 'Crea el hotel en estado inactivo para completar configuración antes de activarlo.' ?>
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

    <form method="POST" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="space-y-5">
        <?= csrf_field() ?>

        <section class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Datos principales</p>
                <h2 class="mt-1 text-base font-semibold text-gray-900">Identidad del cliente</h2>
                <p class="mt-1 text-sm text-gray-600">Define el nombre visible y la clave interna con la que soporte identificará el hotel.</p>
            </div>
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
                    <p class="mt-1 text-xs text-gray-500">Solo minúsculas, números y guiones. No iniciar ni terminar con guion.</p>
                </div>

                <div>
                    <label for="codigo" class="block text-sm font-medium text-gray-700">Código interno</label>
                    <input type="text" id="codigo" name="codigo" maxlength="50"
                           value="<?= $valor('codigo') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                    <p class="mt-1 text-xs text-gray-500">Uso interno para soporte, ventas o referencia comercial.</p>
                </div>
            </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Contacto</p>
                <h2 class="mt-1 text-base font-semibold text-gray-900">Datos de comunicación</h2>
                <p class="mt-1 text-sm text-gray-600">Información base para seguimiento operativo y administrativo.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
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
            </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Datos fiscales</p>
                <h2 class="mt-1 text-base font-semibold text-gray-900">Razón social y dirección</h2>
                <p class="mt-1 text-sm text-gray-600">Campos opcionales para dejar preparado el expediente del cliente.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label for="razon_social" class="block text-sm font-medium text-gray-700">Razón social</label>
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
                    <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
                    <input type="text" id="direccion" name="direccion" maxlength="255"
                           value="<?= $valor('direccion') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>
            </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Configuración regional</p>
                <h2 class="mt-1 text-base font-semibold text-gray-900">Ubicación, zona horaria y moneda</h2>
                <p class="mt-1 text-sm text-gray-600">Valores iniciales para la operación diaria del hotel.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="ciudad" class="block text-sm font-medium text-gray-700">Ciudad</label>
                    <input type="text" id="ciudad" name="ciudad" maxlength="100"
                           value="<?= $valor('ciudad') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700">Estado / región</label>
                    <input type="text" id="estado" name="estado" maxlength="100"
                           value="<?= $valor('estado') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div>
                    <label for="pais" class="block text-sm font-medium text-gray-700">País</label>
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
                    <label for="moneda_simbolo" class="block text-sm font-medium text-gray-700">Símbolo moneda</label>
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
        </section>

        <div class="bg-white border border-gray-200 rounded-lg shadow-sm px-6 py-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="<?= $esEdicion ? url('admin/saas/hoteles/' . (int) $hotel['id']) : url('admin/saas/hoteles') ?>"
               class="inline-flex items-center justify-center rounded-md border px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
               style="border-color:var(--ms-border);">
                Cancelar
            </a>
            <button type="submit" class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-px hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                <?= $esEdicion ? 'Guardar cambios' : 'Crear hotel inactivo' ?>
            </button>
        </div>
    </form>
</div>
