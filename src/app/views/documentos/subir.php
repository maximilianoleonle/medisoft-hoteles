<?php
$tipos = $tipos ?? [];
$contextoEntidad = $contextoEntidad ?? null;
$docUploadFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];

if (!function_exists('doc_upload_safe')) {
    function doc_upload_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('doc_upload_label')) {
    function doc_upload_label($tipo)
    {
        $labels = [
            'proveedor' => 'Proveedor',
            'compra' => 'Compra',
            'cuenta_por_pagar' => 'Cuenta por pagar',
            'huesped' => 'Huesped',
            'reservacion' => 'Reservacion',
        ];

        return $labels[(string)$tipo] ?? 'Entidad';
    }
}

if (!function_exists('doc_upload_form_error')) {
    function doc_upload_form_error(array $errors, string $field): string
    {
        $messages = $errors[$field] ?? [];
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        return doc_upload_safe($messages[0] ?? '', '');
    }
}

if (!function_exists('doc_upload_form_error_class')) {
    function doc_upload_form_error_class(array $errors, string $field): string
    {
        return doc_upload_form_error($errors, $field) !== '' ? ' dc-field-error' : '';
    }
}

if (!function_exists('doc_upload_form_error_attrs')) {
    function doc_upload_form_error_attrs(array $errors, string $field, string $errorId): string
    {
        if (doc_upload_form_error($errors, $field) === '') {
            return '';
        }

        return ' aria-invalid="true" aria-describedby="' . doc_upload_safe($errorId, '') . '"';
    }
}

$tieneContexto = is_array($contextoEntidad);
$documentoTipoSeleccionado = (int) old('documento_tipo_id', '0');
$tituloValor = old('titulo', '');
$descripcionValor = old('descripcion', '');
$etiquetasValor = old('etiquetas', '');
$relacionValor = old('relacion', '');
$docUploadMaxBytes = 10485760;
?>

<style>
.doc-upload-page {
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
    --dc-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --dc-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --dc-danger: #B4392B;
    min-height: 100%;
    color: var(--dc-text);
    font-family: var(--dc-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--dc-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--dc-ivory-2), var(--dc-ivory));
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.doc-upload-page .dc-shell { display: grid; gap: 14px; }
.doc-upload-page .dc-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.doc-upload-page .dc-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--dc-gold), var(--dc-brand) 54%, color-mix(in srgb, var(--dc-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--dc-brand) 72%, transparent);
}
.doc-upload-page .dc-kicker { margin: 0 0 2px; color: var(--dc-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.doc-upload-page .dc-title { margin: 0; font-family: var(--dc-serif); color: var(--dc-heading); font-weight: 700; font-size: clamp(2rem, 3.4vw, 2.7rem); line-height: 1; }
.doc-upload-page .dc-subtitle { max-width: 46rem; margin: 8px 0 0; color: var(--dc-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.doc-upload-page .dc-panel { background: var(--dc-surface); border: 1px solid var(--dc-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.doc-upload-page .dc-soft-panel { background: var(--dc-surface-warm); border: 1px solid var(--dc-border); border-radius: 13px; }
.doc-upload-page .dc-label { display: block; margin-bottom: 6px; color: var(--dc-muted); font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .045em; }
.doc-upload-page .dc-field {
    width: 100%; min-height: 44px; border: 1px solid var(--dc-border); background: var(--dc-surface-warm); border-radius: 11px; padding: 11px 13px;
    color: var(--dc-text); font-weight: 600; font-size: .9rem; font-family: var(--dc-sans); transition: border-color .16s ease, box-shadow .16s ease;
}
.doc-upload-page textarea.dc-field { min-height: 104px; resize: vertical; }
.doc-upload-page select.dc-field { cursor: pointer; }
.doc-upload-page .dc-field:focus { border-color: var(--dc-gold); box-shadow: 0 0 0 3px var(--dc-ring); outline: none; background: #fff; }
.doc-upload-page .dc-field.is-invalid { border-color: var(--dc-danger); box-shadow: 0 0 0 3px color-mix(in srgb, var(--dc-danger) 14%, transparent); }
.doc-upload-page .dc-field-error { border-color: var(--dc-danger); background: #FFF7F6; }
.doc-upload-page .dc-form-error { display: block; margin-top: 7px; color: var(--dc-danger); font-size: .78rem; font-weight: 800; line-height: 1.35; letter-spacing: 0; text-transform: none; }
.doc-upload-page .dc-hint { margin-top: 8px; font-size: .82rem; color: var(--dc-muted); }
.doc-upload-page .dc-upload-error { display: none; margin-top: 8px; color: var(--dc-danger); font-size: .85rem; font-weight: 700; }
.doc-upload-page .dc-upload-error.is-visible { display: block; }

.doc-upload-page .dc-info-title { font-weight: 700; color: var(--dc-heading); }
.doc-upload-page .dc-info-list { margin: 8px 0 0; padding: 0; list-style: none; display: grid; gap: 7px; }
.doc-upload-page .dc-info-list li { display: flex; gap: 9px; align-items: flex-start; font-size: .85rem; color: var(--dc-text); }
.doc-upload-page .dc-info-list i { color: var(--dc-success, #1E9E63); margin-top: 3px; flex-shrink: 0; }

.doc-upload-page .dc-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; padding: 0 20px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .9rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.doc-upload-page .dc-btn:hover { transform: translateY(-1px); }
.doc-upload-page .dc-btn-gold { background: linear-gradient(135deg, var(--dc-gold), color-mix(in srgb, var(--dc-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--dc-gold) 58%, transparent); }
.doc-upload-page .dc-btn-muted { background: var(--dc-surface); border-color: var(--dc-border); color: var(--dc-muted); }
.doc-upload-page .dc-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: var(--dc-surface-warm); color: var(--dc-muted); border: 1px solid var(--dc-border); font-size: .76rem; font-weight: 700; }
</style>

<div class="doc-upload-page p-4 sm:p-6">
    <div class="dc-shell" style="max-width:1100px">
        <section class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="dc-title-lockup">
                <div class="dc-hero-icon"><i class="fas fa-upload"></i></div>
                <div>
                    <p class="dc-kicker">Archivos del hotel</p>
                    <h1 class="dc-title">Subir documento</h1>
                    <p class="dc-subtitle">Sube un archivo y gu&aacute;rdalo con su informaci&oacute;n. Aceptamos PDF, JPG, PNG o WEBP, hasta 10&nbsp;MB.</p>
                </div>
            </div>
            <span class="dc-chip"><i class="fas fa-lock"></i> Archivo privado</span>
        </section>

        <div class="flex items-center justify-between gap-3">
            <?php $back_arrow_href = back_url('documentos'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="dc-btn dc-btn-muted ms-back-legacy" href="<?= back_url('documentos') ?>" style="min-height:40px">
                <i class="fas fa-arrow-left"></i>
                Volver a documentos
            </a>
        </div>

        <form method="POST" action="<?= url('documentos/subir') ?>" enctype="multipart/form-data" class="dc-panel p-5">
            <?= csrf_field() ?>
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= $docUploadMaxBytes ?>">

            <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_.9fr] gap-5">
                <div class="space-y-4">
                    <div>
                        <label class="dc-label" for="archivo">Archivo</label>
                        <input
                            class="dc-field<?= doc_upload_form_error_class($docUploadFieldErrors, 'archivo') ?>"
                            type="file"
                            id="archivo"
                            name="archivo"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                            data-doc-upload-file
                            data-max-bytes="<?= $docUploadMaxBytes ?>"
                            aria-describedby="archivoAyuda archivoError<?= doc_upload_form_error($docUploadFieldErrors, 'archivo') !== '' ? ' ms-form-error-archivo' : '' ?>"
                            required
                            <?= doc_upload_form_error($docUploadFieldErrors, 'archivo') !== '' ? 'aria-invalid="true"' : '' ?>
                        >
                        <p id="archivoAyuda" class="dc-hint">Guardamos el archivo con un nombre interno seguro; nadie ve la ruta real.</p>
                        <p id="archivoError" class="dc-upload-error" role="alert" aria-live="polite"></p>
                        <?php if (doc_upload_form_error($docUploadFieldErrors, 'archivo') !== ''): ?>
                            <p id="ms-form-error-archivo" class="dc-form-error ms-form-field-error"><?= doc_upload_form_error($docUploadFieldErrors, 'archivo') ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="dc-label" for="titulo">T&iacute;tulo</label>
                        <input class="dc-field<?= doc_upload_form_error_class($docUploadFieldErrors, 'titulo') ?>" type="text" id="titulo" name="titulo" maxlength="180" placeholder="Ej. Contrato del proveedor" value="<?= doc_upload_safe($tituloValor, '') ?>"<?= doc_upload_form_error_attrs($docUploadFieldErrors, 'titulo', 'ms-form-error-titulo') ?>>
                        <?php if (doc_upload_form_error($docUploadFieldErrors, 'titulo') !== ''): ?>
                            <span id="ms-form-error-titulo" class="dc-form-error ms-form-field-error"><?= doc_upload_form_error($docUploadFieldErrors, 'titulo') ?></span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="dc-label" for="descripcion">Descripci&oacute;n</label>
                        <textarea class="dc-field<?= doc_upload_form_error_class($docUploadFieldErrors, 'descripcion') ?>" id="descripcion" name="descripcion" maxlength="255" placeholder="Una nota breve sobre el documento"<?= doc_upload_form_error_attrs($docUploadFieldErrors, 'descripcion', 'ms-form-error-descripcion') ?>><?= doc_upload_safe($descripcionValor, '') ?></textarea>
                        <?php if (doc_upload_form_error($docUploadFieldErrors, 'descripcion') !== ''): ?>
                            <span id="ms-form-error-descripcion" class="dc-form-error ms-form-field-error"><?= doc_upload_form_error($docUploadFieldErrors, 'descripcion') ?></span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="dc-label" for="etiquetas">Etiquetas</label>
                        <input class="dc-field<?= doc_upload_form_error_class($docUploadFieldErrors, 'etiquetas') ?>" type="text" id="etiquetas" name="etiquetas" maxlength="1000" placeholder="contrato, compra, fiscal" value="<?= doc_upload_safe($etiquetasValor, '') ?>"<?= doc_upload_form_error_attrs($docUploadFieldErrors, 'etiquetas', 'ms-form-error-etiquetas') ?>>
                        <p class="dc-hint">Palabras para encontrarlo despu&eacute;s, separadas por comas.</p>
                        <?php if (doc_upload_form_error($docUploadFieldErrors, 'etiquetas') !== ''): ?>
                            <span id="ms-form-error-etiquetas" class="dc-form-error ms-form-field-error"><?= doc_upload_form_error($docUploadFieldErrors, 'etiquetas') ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="dc-label" for="documento_tipo_id">Tipo de documento</label>
                        <select class="dc-field<?= doc_upload_form_error_class($docUploadFieldErrors, 'documento_tipo_id') ?>" id="documento_tipo_id" name="documento_tipo_id"<?= doc_upload_form_error_attrs($docUploadFieldErrors, 'documento_tipo_id', 'ms-form-error-documento_tipo_id') ?>>
                            <option value="0">Sin tipo espec&iacute;fico</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <option value="<?= (int)($tipo['id'] ?? 0) ?>" <?= (int)($tipo['id'] ?? 0) === $documentoTipoSeleccionado ? 'selected' : '' ?>>
                                    <?= doc_upload_safe($tipo['nombre'] ?? null) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($tipos)): ?>
                            <p class="dc-hint">A&uacute;n no hay tipos configurados; puedes subirlo sin tipo.</p>
                        <?php endif; ?>
                        <?php if (doc_upload_form_error($docUploadFieldErrors, 'documento_tipo_id') !== ''): ?>
                            <span id="ms-form-error-documento_tipo_id" class="dc-form-error ms-form-field-error"><?= doc_upload_form_error($docUploadFieldErrors, 'documento_tipo_id') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="dc-soft-panel p-4">
                        <div class="dc-label" style="margin-bottom:8px">Vincular a</div>
                        <?php if ($tieneContexto): ?>
                            <input type="hidden" name="entidad_tipo" value="<?= doc_upload_safe($contextoEntidad['tipo'] ?? '', '') ?>">
                            <input type="hidden" name="entidad_id" value="<?= (int)($contextoEntidad['id'] ?? 0) ?>">
                            <div class="dc-info-title">
                                <?= doc_upload_safe($contextoEntidad['label'] ?? doc_upload_label($contextoEntidad['tipo'] ?? '')) ?>
                                #<?= (int)($contextoEntidad['id'] ?? 0) ?>
                            </div>
                            <p class="dc-hint">El documento quedar&aacute; ligado a este registro.</p>

                            <label class="dc-label mt-4" for="relacion">Relaci&oacute;n</label>
                            <input class="dc-field<?= doc_upload_form_error_class($docUploadFieldErrors, 'relacion') ?>" type="text" id="relacion" name="relacion" maxlength="80" placeholder="Ej. comprobante, contrato, evidencia" value="<?= doc_upload_safe($relacionValor, '') ?>"<?= doc_upload_form_error_attrs($docUploadFieldErrors, 'relacion', 'ms-form-error-relacion') ?>>
                            <?php if (doc_upload_form_error($docUploadFieldErrors, 'relacion') !== ''): ?>
                                <span id="ms-form-error-relacion" class="dc-form-error ms-form-field-error"><?= doc_upload_form_error($docUploadFieldErrors, 'relacion') ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="dc-info-title">Sin v&iacute;nculo por ahora</div>
                            <p class="dc-hint">
                                Si quieres ligarlo a un proveedor, compra, cuenta por pagar, hu&eacute;sped o reservaci&oacute;n, abre "Subir documento" desde la ficha de ese registro.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="dc-soft-panel p-4">
                        <div class="dc-info-title">C&oacute;mo se guarda</div>
                        <ul class="dc-info-list">
                            <li><i class="fas fa-circle-check"></i> Solo tu equipo puede descargarlo.</li>
                            <li><i class="fas fa-circle-check"></i> Se guarda en un espacio privado y seguro.</li>
                            <li><i class="fas fa-circle-check"></i> El archivo no se reemplaza despu&eacute;s; la informaci&oacute;n s&iacute; se puede editar.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                <a class="dc-btn dc-btn-muted" href="<?= back_url('documentos') ?>">Cancelar</a>
                <button class="dc-btn dc-btn-gold" type="submit">
                    <i class="fas fa-upload"></i>
                    Subir documento
                </button>
            </div>
        </form>
    </div>
</div>
<script>
(() => {
    const form = document.querySelector('.doc-upload-page form[method="POST"]');
    const fileInput = document.querySelector('[data-doc-upload-file]');
    const error = document.getElementById('archivoError');

    if (!form || !fileInput || !error) {
        return;
    }

    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    const allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    const maxBytes = Number(fileInput.dataset.maxBytes || 10485760);

    function formatBytes(bytes) {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 MB';
        }

        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function extensionFromName(name) {
        const parts = String(name || '').split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '';
    }

    function setFileError(message) {
        fileInput.setCustomValidity(message);
        fileInput.classList.add('is-invalid');
        fileInput.setAttribute('aria-invalid', 'true');
        error.textContent = message;
        error.classList.add('is-visible');
    }

    function clearFileError() {
        fileInput.setCustomValidity('');
        fileInput.classList.remove('is-invalid', 'dc-field-error', 'ms-form-invalid');
        fileInput.removeAttribute('aria-invalid');
        error.textContent = '';
        error.classList.remove('is-visible');

        const serverError = document.getElementById('ms-form-error-archivo');
        if (serverError) {
            serverError.remove();
        }

        const describedBy = String(fileInput.getAttribute('aria-describedby') || '')
            .split(/\s+/)
            .filter(Boolean)
            .filter(id => id !== 'ms-form-error-archivo')
            .join(' ');
        if (describedBy) {
            fileInput.setAttribute('aria-describedby', describedBy);
        }
    }

    function validateSelectedDocumentFile(showRequiredMessage = false) {
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

        if (!file) {
            if (showRequiredMessage) {
                setFileError('Selecciona un archivo PDF, JPG, PNG o WEBP antes de subirlo.');
                return false;
            }

            clearFileError();
            return true;
        }

        const extension = extensionFromName(file.name);
        const mime = String(file.type || '').toLowerCase();
        const extensionAllowed = allowedExtensions.includes(extension);
        const mimeAllowed = mime === '' || allowedMimes.includes(mime);

        if (!extensionAllowed || !mimeAllowed) {
            setFileError('Formato no permitido. Usa PDF, JPG, PNG o WEBP.');
            return false;
        }

        if (file.size > maxBytes) {
            setFileError('El archivo pesa ' + formatBytes(file.size) + '. El maximo permitido es ' + formatBytes(maxBytes) + '.');
            return false;
        }

        clearFileError();
        return true;
    }

    fileInput.addEventListener('change', () => {
        validateSelectedDocumentFile(false);
    });

    form.addEventListener('submit', (event) => {
        if (!validateSelectedDocumentFile(true)) {
            event.preventDefault();
            event.stopPropagation();
            if (typeof fileInput.reportValidity === 'function') {
                fileInput.reportValidity();
            }
            fileInput.focus({ preventScroll: false });
        }
    }, true);
})();
</script>
