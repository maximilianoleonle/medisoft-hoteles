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
$hayDatosAnteriores = !empty($_SESSION['old_input']);
$codigoAnterior = (string) old('codigo', '');
$monedaAnterior = strtoupper(trim((string) old('moneda_codigo', 'MXN')));
$abrirConfiguracionAdicional = $esEdicion
    || ($hayDatosAnteriores && ($codigoAnterior !== '' || !preg_match('/^[A-Z]{3}$/', $monedaAnterior)));
?>

<style>
.ms-admin-scope .ms-hotel-form-page {
    --ms-form-soft: #f8fafc;
    --ms-form-blue-soft: rgba(37, 99, 235, .07);
    --ms-form-blue-border: rgba(37, 99, 235, .18);
}

.ms-admin-scope .ms-hotel-form-layout {
    display: grid;
    gap: 1.25rem;
}

.ms-admin-scope .ms-hotel-form-progress {
    display: grid;
    overflow: hidden;
    border: 1px solid var(--ms-border);
    border-radius: .75rem;
    background: var(--ms-surface);
}

.ms-admin-scope .ms-hotel-form-progress-item {
    display: flex;
    min-width: 0;
    align-items: flex-start;
    gap: .75rem;
    padding: 1rem;
    border-bottom: 1px solid var(--ms-border);
}

.ms-admin-scope .ms-hotel-form-progress-item:last-child {
    border-bottom: 0;
}

.ms-admin-scope .ms-hotel-form-progress-item.is-current {
    background: var(--ms-form-blue-soft);
}

.ms-admin-scope .ms-hotel-form-step-number {
    display: inline-flex;
    width: 1.75rem;
    height: 1.75rem;
    flex: 0 0 1.75rem;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--ms-border);
    border-radius: 999px;
    background: var(--ms-form-soft);
    color: var(--ms-muted);
    font-size: .75rem;
    font-weight: 700;
}

.ms-admin-scope .ms-hotel-form-progress-item.is-current .ms-hotel-form-step-number {
    border-color: var(--ms-primary);
    background: var(--ms-primary);
    color: #fff;
}

.ms-admin-scope .ms-hotel-form-card {
    overflow: hidden;
    border: 1px solid var(--ms-border);
    border-radius: .75rem;
    background: var(--ms-surface);
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
}

.ms-admin-scope .ms-hotel-form-card-header {
    display: flex;
    align-items: flex-start;
    gap: .875rem;
    padding: 1.125rem 1.25rem;
    border-bottom: 1px solid var(--ms-border);
}

.ms-admin-scope .ms-hotel-form-card-icon {
    display: inline-flex;
    width: 2.25rem;
    height: 2.25rem;
    flex: 0 0 2.25rem;
    align-items: center;
    justify-content: center;
    border-radius: .625rem;
    background: var(--ms-form-blue-soft);
    color: var(--ms-primary);
}

.ms-admin-scope .ms-hotel-form-card-body {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1.125rem;
    padding: 1.25rem;
}

.ms-admin-scope .ms-form-field-wide {
    grid-column: 1 / -1;
}

.ms-admin-scope .ms-input {
    min-height: 44px;
    border-color: #cbd5e1;
    background: #fff;
    color: var(--ms-text);
}

.ms-admin-scope .ms-input:hover {
    border-color: #94a3b8;
}

.ms-admin-scope .ms-input:focus {
    outline: none;
    border-color: var(--ms-primary) !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .14) !important;
}

.ms-admin-scope .ms-input::placeholder {
    color: #94a3b8;
}

.ms-admin-scope .ms-form-required-note {
    display: inline-flex;
    align-items: center;
    gap: .375rem;
    margin-top: .5rem;
    color: var(--ms-primary);
    font-size: .75rem;
    font-weight: 600;
}

.ms-admin-scope .ms-hotel-form-details > summary {
    display: flex;
    min-height: 72px;
    cursor: pointer;
    list-style: none;
    align-items: center;
    gap: .875rem;
    padding: 1rem 1.25rem;
    transition: background-color .16s ease;
}

.ms-admin-scope .ms-hotel-form-details > summary::-webkit-details-marker {
    display: none;
}

.ms-admin-scope .ms-hotel-form-details > summary:hover {
    background: var(--ms-form-soft);
}

.ms-admin-scope .ms-hotel-form-details > summary:focus-visible {
    outline: 3px solid rgba(37, 99, 235, .25);
    outline-offset: -3px;
}

.ms-admin-scope .ms-hotel-form-details[open] > summary {
    border-bottom: 1px solid var(--ms-border);
}

.ms-admin-scope .ms-hotel-form-details-chevron {
    margin-left: auto;
    color: var(--ms-muted);
    transition: transform .16s ease;
}

.ms-admin-scope .ms-hotel-form-details[open] .ms-hotel-form-details-chevron {
    transform: rotate(180deg);
}

.ms-admin-scope .ms-hotel-form-aside {
    align-self: start;
}

.ms-admin-scope .ms-hotel-form-summary {
    border: 1px solid var(--ms-form-blue-border);
    border-radius: .75rem;
    background: var(--ms-form-blue-soft);
    padding: 1.25rem;
}

.ms-admin-scope .ms-hotel-form-summary-list {
    display: grid;
    gap: .875rem;
    margin-top: 1rem;
}

.ms-admin-scope .ms-hotel-form-summary-item {
    display: flex;
    align-items: flex-start;
    gap: .625rem;
    color: var(--ms-text);
    font-size: .8125rem;
    line-height: 1.5;
}

.ms-admin-scope .ms-hotel-form-summary-item i {
    width: 1rem;
    flex: 0 0 1rem;
    margin-top: .2rem;
    color: var(--ms-primary);
    text-align: center;
}

.ms-admin-scope .ms-hotel-form-actions {
    display: flex;
    flex-direction: column-reverse;
    gap: .75rem;
    padding: 1rem 1.25rem;
    border: 1px solid var(--ms-border);
    border-radius: .75rem;
    background: var(--ms-surface);
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
}

.ms-admin-scope .ms-hotel-form-button {
    min-height: 44px;
}

.ms-admin-scope .ms-hotel-form-button:disabled {
    cursor: not-allowed;
    opacity: .7;
}

@media (min-width: 640px) {
    .ms-admin-scope .ms-hotel-form-header {
        align-items: flex-start;
    }

    .ms-admin-scope .ms-hotel-form-progress {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .ms-admin-scope .ms-hotel-form-progress-item {
        border-right: 1px solid var(--ms-border);
        border-bottom: 0;
    }

    .ms-admin-scope .ms-hotel-form-progress-item:last-child {
        border-right: 0;
    }

    .ms-admin-scope .ms-hotel-form-card-body {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        padding: 1.5rem;
    }

    .ms-admin-scope .ms-hotel-form-card-header {
        padding: 1.25rem 1.5rem;
    }

    .ms-admin-scope .ms-hotel-form-actions {
        flex-direction: row;
        align-items: center;
        justify-content: flex-end;
        padding: 1rem 1.5rem;
    }
}

@media (min-width: 1024px) {
    .ms-admin-scope .ms-hotel-form-layout {
        grid-template-columns: minmax(0, 1fr) 18rem;
        align-items: start;
    }

    .ms-admin-scope .ms-hotel-form-aside {
        position: sticky;
        top: 1.5rem;
    }
}

@media (max-width: 639px) {
    .ms-admin-scope .ms-input {
        font-size: 16px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .ms-admin-scope .ms-hotel-form-details > summary,
    .ms-admin-scope .ms-hotel-form-details-chevron {
        transition: none;
    }
}
</style>

<div class="ms-hotel-form-page max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <header class="ms-hotel-form-header mb-6 flex flex-col gap-4 sm:flex-row sm:justify-between">
        <div class="max-w-2xl">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider" style="color:var(--ms-primary);">
                <?= $esEdicion ? 'Administración del cliente' : 'Alta de cliente' ?>
            </p>
            <h1 class="text-2xl font-bold" style="color:var(--ms-text);">
                <?= $esEdicion ? 'Editar datos del hotel' : 'Registrar nuevo hotel' ?>
            </h1>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--ms-muted);">
                <?= $esEdicion
                    ? 'Actualiza la información del cliente. El estado, los accesos y los bloques se administran desde el detalle del hotel.'
                    : 'Empieza con los datos esenciales. Después podrás configurar accesos, bloques, plan e imagen antes de activarlo.' ?>
            </p>
        </div>
        <a href="<?= $volverHotelUrl ?>"
           class="ms-hotel-form-button inline-flex items-center justify-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2"
           style="border-color:var(--ms-border);color:var(--ms-text);--tw-ring-color:var(--ms-primary);">
            <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
            <?= $esEdicion ? 'Volver al detalle' : 'Volver a hoteles' ?>
        </a>
    </header>

    <?php if (!$esEdicion): ?>
        <ol class="ms-hotel-form-progress mb-6" aria-label="Proceso de alta del hotel">
            <li class="ms-hotel-form-progress-item is-current" aria-current="step">
                <span class="ms-hotel-form-step-number">1</span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold" style="color:var(--ms-text);">Registrar datos</p>
                    <p class="mt-1 text-xs leading-relaxed" style="color:var(--ms-muted);">Nombre, contacto y ubicación.</p>
                </div>
            </li>
            <li class="ms-hotel-form-progress-item">
                <span class="ms-hotel-form-step-number">2</span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold" style="color:var(--ms-text);">Preparar operación</p>
                    <p class="mt-1 text-xs leading-relaxed" style="color:var(--ms-muted);">Plan, bloques, imagen y usuario.</p>
                </div>
            </li>
            <li class="ms-hotel-form-progress-item">
                <span class="ms-hotel-form-step-number">3</span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold" style="color:var(--ms-text);">Activar hotel</p>
                    <p class="mt-1 text-xs leading-relaxed" style="color:var(--ms-muted);">Habilitar el acceso cuando esté listo.</p>
                </div>
            </li>
        </ol>
    <?php endif; ?>

    <?php if ($mensaje = get_mensaje()): ?>
        <div class="mb-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <i class="fas fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
            <div><?= $mensaje['texto'] ?? '' ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" id="hotel-form">
        <?= csrf_field() ?>

        <div class="ms-hotel-form-layout">
            <div class="space-y-5">
                <fieldset class="ms-hotel-form-card">
                    <legend class="sr-only">Información básica del hotel</legend>
                    <div class="ms-hotel-form-card-header">
                        <span class="ms-hotel-form-card-icon" aria-hidden="true">
                            <i class="fas fa-hotel text-sm"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold" style="color:var(--ms-text);">Información básica</h2>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" style="background:rgba(37,99,235,.10);color:var(--ms-primary);">Paso esencial</span>
                            </div>
                            <p class="mt-1 text-sm leading-relaxed" style="color:var(--ms-muted);">Estos son los únicos dos datos obligatorios para registrar el hotel.</p>
                        </div>
                    </div>
                    <div class="ms-hotel-form-card-body">
                        <div class="ms-form-field-wide">
                            <label for="nombre" class="block text-sm font-semibold" style="color:var(--ms-text);">
                                Nombre comercial <span class="font-normal" style="color:var(--ms-danger);">(obligatorio)</span>
                            </label>
                            <input type="text" id="nombre" name="nombre" required maxlength="150"
                                   value="<?= $valor('nombre') ?>"
                                   autocomplete="organization"
                                   placeholder="Ej. Hotel Casa del Centro"
                                   class="ms-input mt-1.5 block w-full rounded-md border shadow-sm">
                            <p class="mt-1.5 text-xs leading-relaxed" style="color:var(--ms-muted);">Es el nombre que verás en el panel y en el listado de hoteles.</p>
                        </div>
                        <div class="ms-form-field-wide">
                            <label for="slug" class="block text-sm font-semibold" style="color:var(--ms-text);">
                                Identificador del hotel (slug) <span class="font-normal" style="color:var(--ms-danger);">(obligatorio)</span>
                            </label>
                            <input type="text" id="slug" name="slug" required maxlength="120"
                                   value="<?= $valor('slug') ?>"
                                   placeholder="hotel-casa-del-centro"
                                   spellcheck="false"
                                   aria-describedby="slug-help"
                                   class="ms-input mt-1.5 block w-full rounded-md border font-mono shadow-sm">
                            <p id="slug-help" class="mt-1.5 text-xs leading-relaxed" style="color:var(--ms-muted);">
                                <?= $esEdicion
                                    ? 'Clave única usada internamente. Usa minúsculas, números y guiones.'
                                    : 'Se genera automáticamente desde el nombre. Puedes ajustarlo usando minúsculas, números y guiones.' ?>
                            </p>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="ms-hotel-form-card">
                    <legend class="sr-only">Contacto y ubicación del hotel</legend>
                    <div class="ms-hotel-form-card-header">
                        <span class="ms-hotel-form-card-icon" aria-hidden="true" style="background:rgba(6,182,212,.09);color:var(--ms-accent);">
                            <i class="fas fa-location-dot text-sm"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold" style="color:var(--ms-text);">Contacto y ubicación</h2>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" style="background:rgba(100,116,139,.10);color:var(--ms-muted);">Opcional</span>
                            </div>
                            <p class="mt-1 text-sm leading-relaxed" style="color:var(--ms-muted);">Datos útiles para identificar al cliente y mantener comunicación.</p>
                        </div>
                    </div>
                    <div class="ms-hotel-form-card-body">
                        <div>
                            <label for="telefono" class="block text-sm font-medium" style="color:var(--ms-text);">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" maxlength="15" inputmode="numeric" data-max-digits="15"
                                   value="<?= $valor('telefono') ?>"
                                   autocomplete="tel"
                                   placeholder="Ej. 9511234567"
                                   class="ms-input mt-1.5 block w-full rounded-md border shadow-sm">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium" style="color:var(--ms-text);">Correo de contacto</label>
                            <input type="email" id="email" name="email" maxlength="120"
                                   value="<?= $valor('email') ?>"
                                   autocomplete="email"
                                   placeholder="administracion@hotel.com"
                                   class="ms-input mt-1.5 block w-full rounded-md border shadow-sm">
                        </div>
                        <div>
                            <label for="ciudad" class="block text-sm font-medium" style="color:var(--ms-text);">Ciudad</label>
                            <input type="text" id="ciudad" name="ciudad" maxlength="100"
                                   value="<?= $valor('ciudad') ?>"
                                   autocomplete="address-level2"
                                   placeholder="Ej. Oaxaca de Juárez"
                                   class="ms-input mt-1.5 block w-full rounded-md border shadow-sm">
                        </div>
                        <div>
                            <label for="estado" class="block text-sm font-medium" style="color:var(--ms-text);">Estado o región</label>
                            <input type="text" id="estado" name="estado" maxlength="100"
                                   value="<?= $valor('estado') ?>"
                                   autocomplete="address-level1"
                                   placeholder="Ej. Oaxaca"
                                   class="ms-input mt-1.5 block w-full rounded-md border shadow-sm">
                        </div>
                        <div class="ms-form-field-wide">
                            <label for="pais" class="block text-sm font-medium" style="color:var(--ms-text);">País</label>
                            <input type="text" id="pais" name="pais" maxlength="100"
                                   value="<?= $valor('pais', 'Mexico') ?>"
                                   autocomplete="country-name"
                                   class="ms-input mt-1.5 block w-full rounded-md border shadow-sm">
                        </div>
                    </div>
                </fieldset>

                <section class="ms-hotel-form-card">
                    <details class="ms-hotel-form-details"<?= $abrirConfiguracionAdicional ? ' open' : '' ?>>
                        <summary>
                            <span class="ms-hotel-form-card-icon" aria-hidden="true" style="background:rgba(100,116,139,.09);color:var(--ms-muted);">
                                <i class="fas fa-sliders text-sm"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-base font-semibold" style="color:var(--ms-text);">Configuración adicional</span>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" style="background:rgba(100,116,139,.10);color:var(--ms-muted);">Opcional</span>
                                </span>
                                <span class="mt-1 block text-sm leading-relaxed" style="color:var(--ms-muted);">Datos internos, fiscales, zona horaria y moneda.</span>
                            </span>
                            <i class="ms-hotel-form-details-chevron fas fa-chevron-down text-xs" aria-hidden="true"></i>
                        </summary>

                        <fieldset>
                            <legend class="sr-only">Configuración adicional del hotel</legend>
                            <div class="ms-hotel-form-card-body">
                                <div class="ms-form-field-wide">
                                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Referencia interna</p>
                                </div>
                                <div class="ms-form-field-wide">
                                    <label for="codigo" class="block text-sm font-medium" style="color:var(--ms-text);">Código interno</label>
                                    <input type="text" id="codigo" name="codigo" maxlength="50"
                                           value="<?= $valor('codigo') ?>"
                                           placeholder="Ej. HTC-001"
                                           spellcheck="false"
                                           class="ms-input mt-1.5 block w-full rounded-md border font-mono shadow-sm">
                                    <p class="mt-1.5 text-xs" style="color:var(--ms-muted);">Referencia opcional para soporte, ventas o administración.</p>
                                </div>

                                <div class="ms-form-field-wide mt-1 border-t pt-5" style="border-color:var(--ms-border);">
                                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Datos fiscales</p>
                                </div>
                                <div class="ms-form-field-wide">
                                    <label for="razon_social" class="block text-sm font-medium" style="color:var(--ms-text);">Razón social</label>
                                    <input type="text" id="razon_social" name="razon_social" maxlength="180"
                                           value="<?= $valor('razon_social') ?>"
                                           autocomplete="organization"
                                           class="ms-input mt-1.5 block w-full rounded-md border shadow-sm">
                                </div>
                                <div>
                                    <label for="rfc" class="block text-sm font-medium" style="color:var(--ms-text);">RFC</label>
                                    <input type="text" id="rfc" name="rfc" maxlength="20"
                                           value="<?= $valor('rfc') ?>"
                                           autocomplete="off"
                                           class="ms-input mt-1.5 block w-full rounded-md border font-mono uppercase shadow-sm">
                                </div>
                                <div>
                                    <label for="direccion" class="block text-sm font-medium" style="color:var(--ms-text);">Dirección fiscal</label>
                                    <input type="text" id="direccion" name="direccion" maxlength="255"
                                           value="<?= $valor('direccion') ?>"
                                           autocomplete="street-address"
                                           class="ms-input mt-1.5 block w-full rounded-md border shadow-sm">
                                </div>

                                <div class="ms-form-field-wide mt-1 border-t pt-5" style="border-color:var(--ms-border);">
                                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Operación regional</p>
                                </div>
                                <div class="ms-form-field-wide">
                                    <label for="zona_horaria" class="block text-sm font-medium" style="color:var(--ms-text);">Zona horaria</label>
                                    <input type="text" id="zona_horaria" name="zona_horaria" maxlength="80"
                                           value="<?= $valor('zona_horaria', 'America/Mexico_City') ?>"
                                           spellcheck="false"
                                           aria-describedby="zona-horaria-help"
                                           class="ms-input mt-1.5 block w-full rounded-md border font-mono shadow-sm">
                                    <p id="zona-horaria-help" class="mt-1.5 text-xs" style="color:var(--ms-muted);">Déjala como está si el hotel opera en la zona centro de México.</p>
                                </div>
                                <div>
                                    <label for="moneda_codigo" class="block text-sm font-medium" style="color:var(--ms-text);">Código de moneda</label>
                                    <input type="text" id="moneda_codigo" name="moneda_codigo" maxlength="3"
                                           value="<?= $valor('moneda_codigo', 'MXN') ?>"
                                           placeholder="MXN"
                                           spellcheck="false"
                                           class="ms-input mt-1.5 block w-full rounded-md border font-mono uppercase shadow-sm">
                                </div>
                                <div>
                                    <label for="moneda_simbolo" class="block text-sm font-medium" style="color:var(--ms-text);">Símbolo de moneda</label>
                                    <input type="text" id="moneda_simbolo" name="moneda_simbolo" maxlength="8"
                                           value="<?= $valor('moneda_simbolo', '$') ?>"
                                           placeholder="$"
                                           class="ms-input mt-1.5 block w-full rounded-md border shadow-sm">
                                </div>

                                <?php if ($esEdicion): ?>
                                    <div class="ms-form-field-wide rounded-lg px-4 py-3 text-sm leading-relaxed" style="background:var(--ms-form-blue-soft);border:1px solid var(--ms-form-blue-border);color:var(--ms-text);">
                                        <i class="fas fa-circle-info mr-1.5" style="color:var(--ms-primary);" aria-hidden="true"></i>
                                        Estado actual: <strong><?= !empty($hotel['activo']) ? 'Activo' : 'Inactivo' ?></strong>.
                                        El estado se cambia desde el detalle del hotel.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </fieldset>
                    </details>
                </section>

                <div class="ms-hotel-form-actions">
                    <a href="<?= $volverHotelUrl ?>"
                       class="ms-hotel-form-button inline-flex items-center justify-center rounded-md border px-4 py-2 text-sm font-semibold transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2"
                       style="border-color:var(--ms-border);color:var(--ms-text);--tw-ring-color:var(--ms-primary);">
                        Cancelar
                    </a>
                    <button type="submit" id="hotel-form-submit"
                            class="ms-hotel-form-button inline-flex items-center justify-center gap-2 rounded-md px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-px hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                        <i class="fas <?= $esEdicion ? 'fa-floppy-disk' : 'fa-arrow-right' ?> text-xs" aria-hidden="true"></i>
                        <span data-submit-label><?= $esEdicion ? 'Guardar cambios' : 'Crear hotel y continuar' ?></span>
                    </button>
                </div>
            </div>

            <aside class="ms-hotel-form-aside" aria-label="Resumen del proceso">
                <div class="ms-hotel-form-summary">
                    <?php if ($esEdicion): ?>
                        <p class="text-xs font-semibold uppercase tracking-wider" style="color:var(--ms-primary);">Edición segura</p>
                        <h2 class="mt-1 text-base font-semibold" style="color:var(--ms-text);">Qué puedes cambiar aquí</h2>
                        <div class="ms-hotel-form-summary-list">
                            <div class="ms-hotel-form-summary-item"><i class="fas fa-check" aria-hidden="true"></i><span>Información comercial y de contacto.</span></div>
                            <div class="ms-hotel-form-summary-item"><i class="fas fa-check" aria-hidden="true"></i><span>Datos fiscales y configuración regional.</span></div>
                            <div class="ms-hotel-form-summary-item"><i class="fas fa-circle-info" aria-hidden="true"></i><span>El estado, los usuarios, el plan y los bloques se cambian desde el detalle.</span></div>
                        </div>
                    <?php else: ?>
                        <p class="text-xs font-semibold uppercase tracking-wider" style="color:var(--ms-primary);">Al continuar</p>
                        <h2 class="mt-1 text-base font-semibold" style="color:var(--ms-text);">El hotel quedará protegido</h2>
                        <p class="mt-2 text-sm leading-relaxed" style="color:var(--ms-muted);">Se creará <strong style="color:var(--ms-text);">inactivo</strong>, sin dar acceso operativo todavía.</p>
                        <div class="ms-hotel-form-summary-list">
                            <div class="ms-hotel-form-summary-item"><i class="fas fa-circle-check" aria-hidden="true"></i><span>Te llevaremos a su página de configuración.</span></div>
                            <div class="ms-hotel-form-summary-item"><i class="fas fa-user-shield" aria-hidden="true"></i><span>Ahí crearás el usuario administrador del hotel.</span></div>
                            <div class="ms-hotel-form-summary-item"><i class="fas fa-puzzle-piece" aria-hidden="true"></i><span>Podrás elegir plan, bloques e imagen.</span></div>
                            <div class="ms-hotel-form-summary-item"><i class="fas fa-power-off" aria-hidden="true"></i><span>Lo activarás solo cuando todo esté listo.</span></div>
                        </div>
                        <div class="mt-4 border-t pt-4 text-xs leading-relaxed" style="border-color:var(--ms-form-blue-border);color:var(--ms-muted);">
                            <strong style="color:var(--ms-text);">Para empezar:</strong> solo necesitas el nombre y su identificador.
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
(function () {
    const form = document.getElementById('hotel-form');
    const nameInput = document.getElementById('nombre');
    const slugInput = document.getElementById('slug');
    const submitButton = document.getElementById('hotel-form-submit');
    const isEditMode = <?= $esEdicion ? 'true' : 'false' ?>;
    let slugWasEdited = slugInput ? slugInput.value.trim() !== '' : false;
    const defaultSubmitLabel = submitButton ? submitButton.querySelector('[data-submit-label]').textContent : '';
    const defaultSubmitIconClass = submitButton && submitButton.querySelector('i') ? submitButton.querySelector('i').className : '';

    function slugify(value) {
        return value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 120)
            .replace(/-+$/g, '');
    }

    if (!isEditMode && nameInput && slugInput) {
        nameInput.addEventListener('input', function () {
            if (!slugWasEdited) {
                slugInput.value = slugify(nameInput.value);
            }
        });

        slugInput.addEventListener('input', function () {
            slugWasEdited = true;
        });

        if (!slugWasEdited && nameInput.value.trim() !== '') {
            slugInput.value = slugify(nameInput.value);
        }
    }

    document.querySelectorAll('input[type="tel"][data-max-digits]').forEach(function (input) {
        input.addEventListener('input', function (event) {
            const maxDigits = parseInt(event.target.dataset.maxDigits || '0', 10);
            let value = event.target.value.replace(/\D/g, '');
            if (maxDigits > 0 && value.length > maxDigits) {
                value = value.slice(0, maxDigits);
            }
            event.target.value = value;
        });
    });

    if (form && submitButton) {
        form.addEventListener('submit', function () {
            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
            const label = submitButton.querySelector('[data-submit-label]');
            if (label) {
                label.textContent = isEditMode ? 'Guardando…' : 'Creando hotel…';
            }
            const icon = submitButton.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-spinner fa-spin text-xs';
            }
        });

        window.addEventListener('pageshow', function () {
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-busy');
            const label = submitButton.querySelector('[data-submit-label]');
            if (label) {
                label.textContent = defaultSubmitLabel;
            }
            const icon = submitButton.querySelector('i');
            if (icon) {
                icon.className = defaultSubmitIconClass;
            }
        });
    }
})();
</script>
