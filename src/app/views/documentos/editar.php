<?php
$documento = $documento ?? [];
$tipos = $tipos ?? [];
$docEditFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];

if (!function_exists('doc_edit_safe')) {
    function doc_edit_safe($value, $fallback = '')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('doc_edit_bytes')) {
    function doc_edit_bytes($value)
    {
        $bytes = max(0, (int)($value ?? 0));
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}

if (!function_exists('doc_edit_form_error')) {
    function doc_edit_form_error(array $errors, string $field): string
    {
        $messages = $errors[$field] ?? [];
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        return doc_edit_safe($messages[0] ?? '', '');
    }
}

if (!function_exists('doc_edit_form_error_class')) {
    function doc_edit_form_error_class(array $errors, string $field): string
    {
        return doc_edit_form_error($errors, $field) !== '' ? ' dc-field-error' : '';
    }
}

if (!function_exists('doc_edit_form_error_attrs')) {
    function doc_edit_form_error_attrs(array $errors, string $field, string $errorId): string
    {
        if (doc_edit_form_error($errors, $field) === '') {
            return '';
        }

        return ' aria-invalid="true" aria-describedby="' . doc_edit_safe($errorId, '') . '"';
    }
}

$documentoId = (int)($documento['id'] ?? 0);
$tipoActual = (int) old('documento_tipo_id', (string)($documento['documento_tipo_id'] ?? 0));
$tituloValor = old('titulo', doc_edit_safe($documento['titulo'] ?? ''));
$descripcionValor = old('descripcion', doc_edit_safe($documento['descripcion'] ?? ''));
$etiquetasValor = old('etiquetas', doc_edit_safe($documento['etiquetas'] ?? ''));
?>

<style>
.doc-edit-page {
    --dc-brand: var(--brand-primary, #1B2746);
    --dc-brand-2: var(--brand-secondary, #0F172A);
    --dc-gold: var(--brand-accent, #BD9441);
    --dc-gold-soft: color-mix(in srgb, var(--dc-gold) 15%, #FFFFFF);
    --dc-gold-line: color-mix(in srgb, var(--dc-gold) 42%, #E4D4B0);
    --dc-gold-ink: color-mix(in srgb, var(--dc-gold) 72%, #000);
    --dc-ivory: #F6F2EA;
    --dc-ivory-2: #FBF8F2;
    --dc-surface: #FFFFFF;
    --dc-surface-warm: #FCFAF5;
    --dc-border: color-mix(in srgb, var(--dc-brand) 7%, #E7E1D4);
    --dc-ring: color-mix(in srgb, var(--dc-gold) 32%, transparent);
    --dc-text: #171717;
    --dc-muted: #667085;
    --dc-heading: #111827;
    --dc-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --dc-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100%;
    color: var(--dc-text);
    font-family: var(--dc-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--dc-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--dc-ivory-2), var(--dc-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.doc-edit-page .dc-shell { display: grid; gap: 14px; max-width: 1100px; }
.doc-edit-page .dc-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.doc-edit-page .dc-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--dc-gold), var(--dc-brand) 54%, color-mix(in srgb, var(--dc-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--dc-brand) 72%, transparent);
}
.doc-edit-page .dc-kicker { margin: 0 0 2px; color: var(--dc-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.doc-edit-page .dc-title { margin: 0; font-family: var(--dc-serif); color: var(--dc-heading); font-weight: 700; font-size: clamp(2rem, 3.4vw, 2.7rem); line-height: 1; }
.doc-edit-page .dc-subtitle { max-width: 46rem; margin: 8px 0 0; color: var(--dc-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.doc-edit-page .dc-panel { background: var(--dc-surface); border: 1px solid var(--dc-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.doc-edit-page .dc-soft-panel { background: var(--dc-surface-warm); border: 1px solid var(--dc-border); border-radius: 13px; }
.doc-edit-page .dc-label { display: block; margin-bottom: 6px; color: var(--dc-muted); font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .045em; }
.doc-edit-page .dc-field {
    width: 100%; min-height: 44px; border: 1px solid var(--dc-border); background: var(--dc-surface-warm); border-radius: 11px; padding: 11px 13px;
    color: var(--dc-text); font-weight: 600; font-size: .9rem; font-family: var(--dc-sans); transition: border-color .16s ease, box-shadow .16s ease;
}
.doc-edit-page textarea.dc-field { min-height: 104px; resize: vertical; }
.doc-edit-page select.dc-field { cursor: pointer; }
.doc-edit-page .dc-field:focus { border-color: var(--dc-gold); box-shadow: 0 0 0 3px var(--dc-ring); outline: none; background: #fff; }
.doc-edit-page .dc-field-error { border-color: #B4392B; background: #FFF7F6; }
.doc-edit-page .dc-form-error { display: block; margin-top: 7px; color: #B4392B; font-size: .78rem; font-weight: 800; line-height: 1.35; letter-spacing: 0; text-transform: none; }
.doc-edit-page .dc-meta-label { font-size: .66rem; color: var(--dc-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.doc-edit-page .dc-meta-value { margin-top: 3px; font-weight: 700; color: var(--dc-heading); word-break: break-word; }
.doc-edit-page .dc-info-title { font-weight: 700; color: var(--dc-heading); }
.doc-edit-page .dc-info-list { margin: 8px 0 0; padding: 0; list-style: none; display: grid; gap: 7px; }
.doc-edit-page .dc-info-list li { display: flex; gap: 9px; align-items: flex-start; font-size: .85rem; color: var(--dc-text); }
.doc-edit-page .dc-info-list i { color: var(--dc-muted); margin-top: 3px; flex-shrink: 0; }

.doc-edit-page .dc-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; padding: 0 20px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .9rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.doc-edit-page .dc-btn:hover { transform: translateY(-1px); }
.doc-edit-page .dc-btn-gold { background: linear-gradient(135deg, var(--dc-gold), color-mix(in srgb, var(--dc-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--dc-gold) 58%, transparent); }
.doc-edit-page .dc-btn-muted { background: var(--dc-surface); border-color: var(--dc-border); color: var(--dc-muted); }
.doc-edit-page .dc-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: var(--dc-surface-warm); color: var(--dc-muted); border: 1px solid var(--dc-border); font-size: .76rem; font-weight: 700; }
</style>

<div class="doc-edit-page p-4 sm:p-6">
    <div class="dc-shell">
        <section class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="dc-title-lockup">
                <div class="dc-hero-icon"><i class="fas fa-pen"></i></div>
                <div>
                    <p class="dc-kicker">Archivos del hotel</p>
                    <h1 class="dc-title">Editar informaci&oacute;n</h1>
                    <p class="dc-subtitle">Aqu&iacute; solo cambias los datos del documento (t&iacute;tulo, descripci&oacute;n, etiquetas y tipo). El archivo en s&iacute; no se toca.</p>
                </div>
            </div>
            <span class="dc-chip"><i class="fas fa-shield-halved"></i> El archivo no cambia</span>
        </section>

        <div class="flex items-center justify-between gap-3">
            <a class="dc-btn dc-btn-muted" href="<?= back_url('documentos/' . $documentoId) ?>" style="min-height:40px">
                <i class="fas fa-arrow-left"></i>
                Volver al documento
            </a>
        </div>

        <form method="POST" action="<?= url('documentos/' . $documentoId . '/actualizar') ?>" class="dc-panel p-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_.9fr] gap-5">
                <div class="space-y-4">
                    <div>
                        <label class="dc-label" for="titulo">T&iacute;tulo</label>
                        <input class="dc-field<?= doc_edit_form_error_class($docEditFieldErrors, 'titulo') ?>" type="text" id="titulo" name="titulo" maxlength="180" value="<?= $tituloValor ?>" placeholder="Ej. Contrato del proveedor"<?= doc_edit_form_error_attrs($docEditFieldErrors, 'titulo', 'ms-form-error-titulo') ?>>
                        <?php if (doc_edit_form_error($docEditFieldErrors, 'titulo') !== ''): ?>
                            <span id="ms-form-error-titulo" class="dc-form-error ms-form-field-error"><?= doc_edit_form_error($docEditFieldErrors, 'titulo') ?></span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="dc-label" for="descripcion">Descripci&oacute;n</label>
                        <textarea class="dc-field<?= doc_edit_form_error_class($docEditFieldErrors, 'descripcion') ?>" id="descripcion" name="descripcion" maxlength="255" placeholder="Una nota breve sobre el documento"<?= doc_edit_form_error_attrs($docEditFieldErrors, 'descripcion', 'ms-form-error-descripcion') ?>><?= $descripcionValor ?></textarea>
                        <?php if (doc_edit_form_error($docEditFieldErrors, 'descripcion') !== ''): ?>
                            <span id="ms-form-error-descripcion" class="dc-form-error ms-form-field-error"><?= doc_edit_form_error($docEditFieldErrors, 'descripcion') ?></span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="dc-label" for="etiquetas">Etiquetas</label>
                        <input class="dc-field<?= doc_edit_form_error_class($docEditFieldErrors, 'etiquetas') ?>" type="text" id="etiquetas" name="etiquetas" maxlength="1000" value="<?= $etiquetasValor ?>" placeholder="contrato, compra, fiscal"<?= doc_edit_form_error_attrs($docEditFieldErrors, 'etiquetas', 'ms-form-error-etiquetas') ?>>
                        <?php if (doc_edit_form_error($docEditFieldErrors, 'etiquetas') !== ''): ?>
                            <span id="ms-form-error-etiquetas" class="dc-form-error ms-form-field-error"><?= doc_edit_form_error($docEditFieldErrors, 'etiquetas') ?></span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="dc-label" for="documento_tipo_id">Tipo de documento</label>
                        <select class="dc-field<?= doc_edit_form_error_class($docEditFieldErrors, 'documento_tipo_id') ?>" id="documento_tipo_id" name="documento_tipo_id"<?= doc_edit_form_error_attrs($docEditFieldErrors, 'documento_tipo_id', 'ms-form-error-documento_tipo_id') ?>>
                            <option value="0">Sin tipo espec&iacute;fico</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <?php $tipoId = (int)($tipo['id'] ?? 0); ?>
                                <option value="<?= $tipoId ?>" <?= $tipoId === $tipoActual ? 'selected' : '' ?>>
                                    <?= doc_edit_safe($tipo['nombre'] ?? null) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (doc_edit_form_error($docEditFieldErrors, 'documento_tipo_id') !== ''): ?>
                            <span id="ms-form-error-documento_tipo_id" class="dc-form-error ms-form-field-error"><?= doc_edit_form_error($docEditFieldErrors, 'documento_tipo_id') ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="dc-soft-panel p-4">
                        <div class="dc-info-title" style="margin-bottom:10px">Archivo (no se modifica)</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="dc-meta-label">Documento</div>
                                <div class="dc-meta-value">#<?= $documentoId ?></div>
                            </div>
                            <div>
                                <div class="dc-meta-label">Estado</div>
                                <div class="dc-meta-value"><?= doc_edit_safe($documento['estado'] ?? null, '-') ?></div>
                            </div>
                            <div class="sm:col-span-2">
                                <div class="dc-meta-label">Nombre del archivo</div>
                                <div class="dc-meta-value"><?= doc_edit_safe($documento['nombre_original'] ?? null, '-') ?></div>
                            </div>
                            <div>
                                <div class="dc-meta-label">Formato</div>
                                <div class="dc-meta-value"><?= doc_edit_safe($documento['mime_type'] ?? null, '-') ?></div>
                            </div>
                            <div>
                                <div class="dc-meta-label">Tama&ntilde;o</div>
                                <div class="dc-meta-value"><?= doc_edit_bytes($documento['size_bytes'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="dc-soft-panel p-4">
                        <div class="dc-info-title">Esto no cambia</div>
                        <ul class="dc-info-list">
                            <li><i class="fas fa-circle-minus"></i> El archivo original (no se reemplaza).</li>
                            <li><i class="fas fa-circle-minus"></i> Sus v&iacute;nculos con otros registros.</li>
                            <li><i class="fas fa-circle-minus"></i> El hotel al que pertenece.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                <a class="dc-btn dc-btn-muted" href="<?= back_url('documentos/' . $documentoId) ?>">Cancelar</a>
                <button class="dc-btn dc-btn-gold" type="submit">
                    <i class="fas fa-save"></i>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
