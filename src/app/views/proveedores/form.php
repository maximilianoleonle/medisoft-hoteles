<?php
$modo = $modo ?? 'crear';
$proveedor = $proveedor ?? [];
$esEditar = $modo === 'editar';
$action = $esEditar
    ? url('proveedores/' . (int)($proveedor['id'] ?? 0) . '/actualizar')
    : url('proveedores');

if (!function_exists('prov_form_safe')) {
    function prov_form_safe($value, $fallback = '')
    {
        $text = (string)($value ?? $fallback);
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
?>

<style>
.provider-form-page {
    --prov-brand: var(--brand-primary, #1f3f46);
    --prov-accent: var(--brand-accent, #b58a3c);
    --prov-line: color-mix(in srgb, var(--prov-brand) 10%, #e5e7eb);
    color: #243142;
}
.provider-form-page .provider-form-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--prov-brand) 92%, #111827), color-mix(in srgb, var(--prov-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.provider-form-page .provider-form-panel {
    border: 1px solid var(--prov-line);
    background: rgba(255,255,255,.92);
}
.provider-form-page .provider-input,
.provider-form-page .provider-textarea {
    width: 100%;
    border: 1px solid var(--prov-line);
    background: #fff;
    padding: 10px 12px;
}
.provider-form-page .provider-input {
    min-height: 42px;
}
.provider-form-page .provider-textarea {
    min-height: 92px;
    resize: vertical;
}
.provider-form-page label {
    display: block;
    font-size: .78rem;
    font-weight: 900;
    color: #475569;
    margin-bottom: 6px;
}
.provider-form-page .provider-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 15px;
    border: 1px solid var(--prov-line);
    font-weight: 900;
}
.provider-form-page .provider-btn-primary {
    background: var(--prov-brand);
    border-color: var(--prov-brand);
    color: #fff;
}
.provider-form-page .provider-btn-muted {
    background: #fff;
    color: #334155;
}
</style>

<div class="provider-form-page">
    <section class="provider-form-hero">
        <div class="text-xs uppercase tracking-widest opacity-75 font-black">Catalogo operativo</div>
        <h1 class="text-2xl md:text-3xl font-black mt-2"><?= $esEditar ? 'Editar proveedor' : 'Nuevo proveedor' ?></h1>
        <p class="mt-2 text-white/80 max-w-3xl">
            Informacion basica por hotel. Esta fase no crea compras, pagos, caja ni cuentas por pagar.
        </p>
    </section>

    <section class="p-6">
        <form method="POST" action="<?= $action ?>" class="provider-form-panel p-5 max-w-5xl">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nombre">Nombre comercial *</label>
                    <input class="provider-input" id="nombre" name="nombre" type="text" maxlength="160" required
                           value="<?= prov_form_safe($proveedor['nombre'] ?? '') ?>">
                </div>

                <div>
                    <label for="razon_social">Razon social</label>
                    <input class="provider-input" id="razon_social" name="razon_social" type="text" maxlength="180"
                           value="<?= prov_form_safe($proveedor['razon_social'] ?? '') ?>">
                </div>

                <div>
                    <label for="rfc">RFC</label>
                    <input class="provider-input" id="rfc" name="rfc" type="text" maxlength="20"
                           value="<?= prov_form_safe($proveedor['rfc'] ?? '') ?>">
                </div>

                <div>
                    <label for="telefono">Telefono</label>
                    <input class="provider-input" id="telefono" name="telefono" type="text" maxlength="40"
                           value="<?= prov_form_safe($proveedor['telefono'] ?? '') ?>">
                </div>

                <div>
                    <label for="email">Correo</label>
                    <input class="provider-input" id="email" name="email" type="email" maxlength="160"
                           value="<?= prov_form_safe($proveedor['email'] ?? '') ?>">
                </div>

                <div>
                    <label for="direccion">Direccion</label>
                    <input class="provider-input" id="direccion" name="direccion" type="text" maxlength="255"
                           value="<?= prov_form_safe($proveedor['direccion'] ?? '') ?>">
                </div>

                <div class="md:col-span-2">
                    <label for="notas">Notas internas</label>
                    <textarea class="provider-textarea" id="notas" name="notas" maxlength="1000"><?= prov_form_safe($proveedor['notas'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 mt-5">
                <button class="provider-btn provider-btn-primary" type="submit">
                    <i class="fas fa-save"></i>
                    Guardar
                </button>
                <a class="provider-btn provider-btn-muted" href="<?= url('proveedores') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </form>
    </section>
</div>
