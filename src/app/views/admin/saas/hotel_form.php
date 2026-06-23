<?php
$hotel = $hotel ?? null;
$modo = $modo ?? 'crear';
$action = $action ?? url('admin/saas/hoteles');
$esEdicion = $modo === 'editar';
$volverHotelUrl = back_url($esEdicion ? 'admin/saas/hoteles/' . (int) ($hotel['id'] ?? 0) : 'admin/saas/hoteles');
$valor = function ($campo, $default = '') use ($hotel) {
    $base = $hotel[$campo] ?? $default;
    return old($campo, htmlspecialchars((string) $base, ENT_QUOTES, 'UTF-8'));
};
?>

<style>
.ms-admin-scope .ms-input:focus {
    outline: none;
    border-color: var(--ms-primary) !important;
    box-shadow: 0 0 0 3px rgba(37,99,235,.14) !important;
}
</style>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold" style="color:var(--ms-text);"><?= $esEdicion ? 'Editar hotel' : 'Nuevo hotel' ?></h1>
            <p class="mt-1 text-sm" style="color:var(--ms-muted);">
                <?= $esEdicion
                    ? 'Actualiza los datos básicos del cliente.'
                    : 'El hotel se crea en estado inactivo. Completa la configuración antes de activarlo.' ?>
            </p>
        </div>
        <a href="<?= $volverHotelUrl ?>"
           class="inline-flex items-center gap-1.5 rounded-md border px-4 py-2 text-sm font-semibold transition hover:bg-slate-50"
           style="border-color:var(--ms-border);color:var(--ms-text);">
            <i class="fas fa-arrow-left text-xs"></i>
            <?= $esEdicion ? 'Volver al detalle' : 'Volver al listado' ?>
        </a>
    </div>

    <?php if ($mensaje = get_mensaje()): ?>
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="space-y-5">
        <?= csrf_field() ?>

        <!-- Identidad -->
        <section class="bg-white border rounded-lg shadow-sm overflow-hidden" style="border-color:var(--ms-border);">
            <div class="border-b px-6 py-4" style="border-color:var(--ms-border);">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(37,99,235,.10);">
                        <i class="fas fa-hotel text-xs" style="color:var(--ms-primary);"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Datos principales</p>
                        <h2 class="text-sm font-semibold" style="color:var(--ms-text);">Identidad del cliente</h2>
                    </div>
                </div>
                <p class="mt-2 text-xs" style="color:var(--ms-muted);">Nombre visible y clave interna para identificación en soporte y ventas.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label for="nombre" class="block text-sm font-medium" style="color:var(--ms-text);">Nombre comercial <span style="color:var(--ms-danger);">*</span></label>
                    <input type="text" id="nombre" name="nombre" required maxlength="150"
                           value="<?= $valor('nombre') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>
                <div>
                    <label for="slug" class="block text-sm font-medium" style="color:var(--ms-text);">Slug <span style="color:var(--ms-danger);">*</span></label>
                    <input type="text" id="slug" name="slug" required maxlength="120"
                           value="<?= $valor('slug') ?>"
                           placeholder="hotel-ejemplo"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm font-mono focus:border-gray-900 focus:ring-gray-900">
                    <p class="mt-1 text-xs" style="color:var(--ms-muted);">Solo minúsculas, números y guiones. No iniciar ni terminar con guion.</p>
                </div>
                <div>
                    <label for="codigo" class="block text-sm font-medium" style="color:var(--ms-text);">Código interno</label>
                    <input type="text" id="codigo" name="codigo" maxlength="50"
                           value="<?= $valor('codigo') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm font-mono focus:border-gray-900 focus:ring-gray-900">
                    <p class="mt-1 text-xs" style="color:var(--ms-muted);">Uso interno para soporte, ventas o referencia comercial.</p>
                </div>
            </div>
        </section>

        <!-- Contacto -->
        <section class="bg-white border rounded-lg shadow-sm overflow-hidden" style="border-color:var(--ms-border);">
            <div class="border-b px-6 py-4" style="border-color:var(--ms-border);">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(6,182,212,.10);">
                        <i class="fas fa-envelope text-xs" style="color:var(--ms-accent);"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Contacto</p>
                        <h2 class="text-sm font-semibold" style="color:var(--ms-text);">Datos de comunicación</h2>
                    </div>
                </div>
                <p class="mt-2 text-xs" style="color:var(--ms-muted);">Información base para seguimiento operativo y administrativo.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="telefono" class="block text-sm font-medium" style="color:var(--ms-text);">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" maxlength="30"
                           value="<?= $valor('telefono') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium" style="color:var(--ms-text);">Email</label>
                    <input type="email" id="email" name="email" maxlength="120"
                           value="<?= $valor('email') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>
            </div>
        </section>

        <!-- Fiscal -->
        <section class="bg-white border rounded-lg shadow-sm overflow-hidden" style="border-color:var(--ms-border);">
            <div class="border-b px-6 py-4" style="border-color:var(--ms-border);">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(100,116,139,.10);">
                        <i class="fas fa-file-invoice text-xs" style="color:var(--ms-muted);"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Datos fiscales</p>
                        <h2 class="text-sm font-semibold" style="color:var(--ms-text);">Razón social y dirección</h2>
                    </div>
                </div>
                <p class="mt-2 text-xs" style="color:var(--ms-muted);">Campos opcionales para preparar el expediente del cliente.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label for="razon_social" class="block text-sm font-medium" style="color:var(--ms-text);">Razón social</label>
                    <input type="text" id="razon_social" name="razon_social" maxlength="180"
                           value="<?= $valor('razon_social') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>
                <div>
                    <label for="rfc" class="block text-sm font-medium" style="color:var(--ms-text);">RFC</label>
                    <input type="text" id="rfc" name="rfc" maxlength="20"
                           value="<?= $valor('rfc') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm font-mono focus:border-gray-900 focus:ring-gray-900">
                </div>
                <div>
                    <label for="direccion" class="block text-sm font-medium" style="color:var(--ms-text);">Dirección</label>
                    <input type="text" id="direccion" name="direccion" maxlength="255"
                           value="<?= $valor('direccion') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>
            </div>
        </section>

        <!-- Regional -->
        <section class="bg-white border rounded-lg shadow-sm overflow-hidden" style="border-color:var(--ms-border);">
            <div class="border-b px-6 py-4" style="border-color:var(--ms-border);">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(22,163,74,.10);">
                        <i class="fas fa-globe text-xs" style="color:var(--ms-success);"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Configuración regional</p>
                        <h2 class="text-sm font-semibold" style="color:var(--ms-text);">Ubicación, zona horaria y moneda</h2>
                    </div>
                </div>
                <p class="mt-2 text-xs" style="color:var(--ms-muted);">Valores iniciales para la operación diaria del hotel.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="ciudad" class="block text-sm font-medium" style="color:var(--ms-text);">Ciudad</label>
                    <input type="text" id="ciudad" name="ciudad" maxlength="100"
                           value="<?= $valor('ciudad') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>
                <div>
                    <label for="estado" class="block text-sm font-medium" style="color:var(--ms-text);">Estado / región</label>
                    <input type="text" id="estado" name="estado" maxlength="100"
                           value="<?= $valor('estado') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>
                <div>
                    <label for="pais" class="block text-sm font-medium" style="color:var(--ms-text);">País</label>
                    <input type="text" id="pais" name="pais" maxlength="100"
                           value="<?= $valor('pais', 'Mexico') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>
                <div>
                    <label for="zona_horaria" class="block text-sm font-medium" style="color:var(--ms-text);">Zona horaria</label>
                    <input type="text" id="zona_horaria" name="zona_horaria" maxlength="80"
                           value="<?= $valor('zona_horaria', 'America/Mexico_City') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm font-mono focus:border-gray-900 focus:ring-gray-900">
                </div>
                <div>
                    <label for="moneda_codigo" class="block text-sm font-medium" style="color:var(--ms-text);">Moneda</label>
                    <input type="text" id="moneda_codigo" name="moneda_codigo" maxlength="3"
                           value="<?= $valor('moneda_codigo', 'MXN') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm uppercase font-mono focus:border-gray-900 focus:ring-gray-900">
                </div>
                <div>
                    <label for="moneda_simbolo" class="block text-sm font-medium" style="color:var(--ms-text);">Símbolo moneda</label>
                    <input type="text" id="moneda_simbolo" name="moneda_simbolo" maxlength="8"
                           value="<?= $valor('moneda_simbolo', '$') ?>"
                           class="ms-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>
                <?php if ($esEdicion): ?>
                    <div class="md:col-span-2 rounded-md px-4 py-3 text-sm" style="background:rgba(37,99,235,.06);border:1px solid rgba(37,99,235,.15);color:var(--ms-text);">
                        <i class="fas fa-circle-info mr-1.5" style="color:var(--ms-primary);"></i>
                        Estado actual: <strong><?= !empty($hotel['activo']) ? 'Activo' : 'Inactivo' ?></strong>.
                        El cambio de estado se realiza desde el detalle del hotel.
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Botones -->
        <div class="bg-white border rounded-lg shadow-sm px-6 py-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end" style="border-color:var(--ms-border);">
            <a href="<?= $volverHotelUrl ?>"
               class="inline-flex items-center justify-center gap-1.5 rounded-md border px-4 py-2 text-sm font-semibold transition hover:bg-slate-50"
               style="border-color:var(--ms-border);color:var(--ms-text);">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-1.5 rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-px hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                <?= $esEdicion ? 'Guardar cambios' : 'Crear hotel inactivo' ?>
            </button>
        </div>
    </form>
</div>
