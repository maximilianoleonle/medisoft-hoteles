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
    --pv-brand: var(--brand-primary, #1B2746);
    --pv-brand-2: var(--brand-secondary, #0F172A);
    --pv-gold: var(--brand-accent, #BD9441);
    --pv-gold-soft: color-mix(in srgb, var(--pv-gold) 15%, #FFFFFF);
    --pv-gold-line: color-mix(in srgb, var(--pv-gold) 42%, #E4D4B0);
    --pv-gold-ink: color-mix(in srgb, var(--pv-gold) 72%, #000);
    --pv-ivory: #F6F2EA;
    --pv-ivory-2: #FBF8F2;
    --pv-surface: #FFFFFF;
    --pv-surface-warm: #FCFAF5;
    --pv-border: color-mix(in srgb, var(--pv-brand) 7%, #E7E1D4);
    --pv-ring: color-mix(in srgb, var(--pv-gold) 32%, transparent);
    --pv-text: #171717;
    --pv-muted: #667085;
    --pv-heading: #111827;
    --pv-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --pv-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100%;
    color: var(--pv-text);
    font-family: var(--pv-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--pv-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--pv-ivory-2), var(--pv-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.provider-form-page .pv-shell { display: grid; gap: 14px; }
.provider-form-page .pv-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.provider-form-page .pv-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--pv-gold), var(--pv-brand) 54%, color-mix(in srgb, var(--pv-brand) 68%, #2F8A70));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--pv-brand) 72%, transparent);
}
.provider-form-page .pv-kicker { margin: 0 0 2px; color: var(--pv-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.provider-form-page .pv-title { margin: 0; font-family: var(--pv-serif); color: var(--pv-heading); font-weight: 700; font-size: clamp(2rem, 3.4vw, 2.7rem); line-height: 1; }
.provider-form-page .pv-subtitle { max-width: 44rem; margin: 8px 0 0; color: var(--pv-muted); font-size: .92rem; font-weight: 600; line-height: 1.5; }

.provider-form-page .pv-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, 360px); gap: 16px; align-items: start; }
.provider-form-page .pv-panel {
    background: var(--pv-surface); border: 1px solid var(--pv-border); border-radius: 16px;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28);
}
.provider-form-page label { display: block; font-size: .74rem; font-weight: 700; color: var(--pv-muted); text-transform: uppercase; letter-spacing: .045em; margin-bottom: 6px; }
.provider-form-page .pv-input, .provider-form-page .pv-textarea {
    width: 100%; border: 1px solid var(--pv-border); background: var(--pv-surface-warm); border-radius: 11px; padding: 11px 13px;
    color: var(--pv-text); font-weight: 600; font-size: .9rem; font-family: var(--pv-sans);
    transition: border-color .16s ease, box-shadow .16s ease;
}
.provider-form-page .pv-input { min-height: 44px; }
.provider-form-page .pv-textarea { min-height: 96px; resize: vertical; }
.provider-form-page .pv-input:focus, .provider-form-page .pv-textarea:focus {
    border-color: var(--pv-gold); box-shadow: 0 0 0 3px var(--pv-ring); outline: none; background: #fff;
}
.provider-form-page .pv-req { color: var(--pv-gold-ink); }
.provider-form-page .pv-hint { margin-top: 6px; font-size: .74rem; color: var(--pv-muted); font-weight: 600; }

.provider-form-page .pv-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; padding: 0 20px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .9rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.provider-form-page .pv-btn:hover { transform: translateY(-1px); }
.provider-form-page .pv-btn:active { transform: translateY(0) scale(.98); }
.provider-form-page .pv-btn:focus-visible { outline: 3px solid var(--pv-ring); outline-offset: 2px; }
.provider-form-page .pv-btn-gold { background: linear-gradient(135deg, var(--pv-gold), color-mix(in srgb, var(--pv-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--pv-gold) 58%, transparent); }
.provider-form-page .pv-btn-muted { background: var(--pv-surface); border-color: var(--pv-border); color: var(--pv-muted); }

.provider-form-page .pv-aside-title { font-family: var(--pv-serif); font-size: 1.5rem; font-weight: 700; color: var(--pv-heading); }
.provider-form-page .pv-aside-sub { margin-top: 4px; font-size: .85rem; color: var(--pv-muted); font-weight: 600; line-height: 1.5; }
.provider-form-page .pv-help { margin: 16px 0 0; padding: 0; list-style: none; }
.provider-form-page .pv-help li { display: flex; gap: 11px; padding: 13px 0; border-bottom: 1px solid var(--pv-border); color: var(--pv-text); font-size: .88rem; line-height: 1.45; }
.provider-form-page .pv-help li:last-child { border-bottom: 0; }
.provider-form-page .pv-help i { color: var(--pv-gold-ink); margin-top: 3px; flex-shrink: 0; }

@media (max-width: 980px) { .provider-form-page .pv-grid { grid-template-columns: 1fr; } }
</style>

<div class="provider-form-page p-4 sm:p-6">
    <div class="pv-shell">
        <section class="pv-title-lockup">
            <div class="pv-hero-icon"><i class="fas fa-truck-field"></i></div>
            <div>
                <p class="pv-kicker">Compras y abastecimiento</p>
                <h1 class="pv-title"><?= $esEditar ? 'Editar proveedor' : 'Nuevo proveedor' ?></h1>
                <p class="pv-subtitle">Datos b&aacute;sicos del proveedor. Con esto lo dejas listo para tus compras; aqu&iacute; no se cobra ni se paga nada.</p>
            </div>
        </section>

        <div class="pv-grid">
            <form method="POST" action="<?= $action ?>" class="pv-panel p-5">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nombre">Nombre comercial <span class="pv-req">*</span></label>
                        <input class="pv-input" id="nombre" name="nombre" type="text" maxlength="160" required
                               placeholder="Como lo conoces en el hotel"
                               value="<?= old('nombre', prov_form_safe($proveedor['nombre'] ?? '')) ?>">
                    </div>

                    <div>
                        <label for="razon_social">Raz&oacute;n social</label>
                        <input class="pv-input" id="razon_social" name="razon_social" type="text" maxlength="180"
                               placeholder="Nombre fiscal (para facturas)"
                               value="<?= old('razon_social', prov_form_safe($proveedor['razon_social'] ?? '')) ?>">
                    </div>

                    <div>
                        <label for="rfc">RFC</label>
                        <input class="pv-input" id="rfc" name="rfc" type="text" maxlength="20"
                               placeholder="Para facturaci&oacute;n"
                               value="<?= old('rfc', prov_form_safe($proveedor['rfc'] ?? '')) ?>">
                    </div>

                    <div>
                        <label for="telefono">Tel&eacute;fono</label>
                        <input class="pv-input" id="telefono" name="telefono" type="text" maxlength="40"
                               placeholder="Para contactarlo"
                               value="<?= old('telefono', prov_form_safe($proveedor['telefono'] ?? '')) ?>">
                    </div>

                    <div>
                        <label for="email">Correo</label>
                        <input class="pv-input" id="email" name="email" type="email" maxlength="160"
                               placeholder="correo@proveedor.com"
                               value="<?= old('email', prov_form_safe($proveedor['email'] ?? '')) ?>">
                    </div>

                    <div>
                        <label for="direccion">Direcci&oacute;n</label>
                        <input class="pv-input" id="direccion" name="direccion" type="text" maxlength="255"
                               placeholder="Calle, colonia, ciudad"
                               value="<?= old('direccion', prov_form_safe($proveedor['direccion'] ?? '')) ?>">
                    </div>

                    <div class="md:col-span-2">
                        <label for="notas">Notas internas</label>
                        <textarea class="pv-textarea" id="notas" name="notas" maxlength="1000"
                                  placeholder="Lo que quieras recordar de este proveedor (horarios, descuentos, qu&eacute; te vende...)"><?= old('notas', prov_form_safe($proveedor['notas'] ?? '')) ?></textarea>
                        <p class="pv-hint">Solo las ve tu equipo. El proveedor nunca las ve.</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 mt-6">
                    <button class="pv-btn pv-btn-gold" type="submit">
                        <i class="fas fa-save"></i>
                        <?= $esEditar ? 'Guardar cambios' : 'Guardar proveedor' ?>
                    </button>
                    <a class="pv-btn pv-btn-muted" href="<?= back_url('proveedores') ?>">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </form>

            <aside class="pv-panel p-5">
                <h2 class="pv-aside-title">Antes de guardar</h2>
                <p class="pv-aside-sub">Solo el nombre es obligatorio. Lo dem&aacute;s lo puedes completar despu&eacute;s.</p>
                <ul class="pv-help">
                    <li>
                        <i class="fas fa-building"></i>
                        <span>El <strong>nombre comercial</strong> es como aparece en tus listas y reportes.</span>
                    </li>
                    <li>
                        <i class="fas fa-file-invoice"></i>
                        <span>La <strong>raz&oacute;n social</strong> y el <strong>RFC</strong> sirven cuando necesites factura.</span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <span>Tel&eacute;fono y correo te dejan contactarlo con un clic desde la lista.</span>
                    </li>
                    <li>
                        <i class="fas fa-circle-check"></i>
                        <span>Guardar no genera compras ni pagos: solo registra al proveedor.</span>
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</div>
