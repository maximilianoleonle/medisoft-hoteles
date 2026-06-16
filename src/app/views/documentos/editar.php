<?php
$documento = $documento ?? [];
$tipos = $tipos ?? [];

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

$documentoId = (int)($documento['id'] ?? 0);
$tipoActual = (int)($documento['documento_tipo_id'] ?? 0);
?>

<style>
.doc-edit-page {
    --doc-brand: var(--brand-primary, #1f3f46);
    --doc-accent: var(--brand-accent, #b58a3c);
    --doc-line: color-mix(in srgb, var(--doc-brand) 10%, #e5e7eb);
    --doc-soft: color-mix(in srgb, var(--doc-accent) 7%, #f8fafc);
    color: #243142;
}
.doc-edit-page .doc-edit-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--doc-brand) 92%, #111827), color-mix(in srgb, var(--doc-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.doc-edit-page .doc-edit-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.doc-edit-page .doc-edit-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.doc-edit-page .doc-edit-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.doc-edit-page .doc-edit-panel {
    border: 1px solid var(--doc-line);
    background: rgba(255,255,255,.94);
}
.doc-edit-page .doc-edit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 14px;
    border: 1px solid var(--doc-line);
    font-weight: 800;
}
.doc-edit-page .doc-edit-btn-primary {
    background: var(--doc-brand);
    border-color: var(--doc-brand);
    color: #fff;
}
.doc-edit-page .doc-edit-btn-muted {
    background: #fff;
    color: #334155;
}
.doc-edit-page .doc-edit-field {
    width: 100%;
    min-height: 42px;
    border: 1px solid var(--doc-line);
    background: #fff;
    padding: 0 12px;
}
.doc-edit-page textarea.doc-edit-field {
    min-height: 104px;
    padding: 10px 12px;
    resize: vertical;
}
.doc-edit-page .doc-edit-label {
    display: block;
    margin-bottom: 6px;
    color: #334155;
    font-size: .78rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.doc-edit-page .doc-edit-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border: 1px solid var(--doc-line);
    background: var(--doc-soft);
    font-size: .78rem;
    font-weight: 800;
}
.doc-edit-page .doc-edit-meta-label {
    font-size: .72rem;
    color: #64748b;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}
</style>

<div class="doc-edit-page">
    <section class="doc-edit-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="doc-edit-kicker">Centro documental</div>
                <h1 class="doc-edit-title">Editar metadata</h1>
                <p class="doc-edit-subtitle">
                    Solo se actualizan datos descriptivos. El archivo privado, su ruta interna, hash, MIME, tamano y hotel no se modifican.
                </p>
            </div>
            <span class="doc-edit-badge">
                <i class="fas fa-edit"></i>
                Metadata segura
            </span>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <a class="doc-edit-btn doc-edit-btn-muted" href="<?= url('documentos/' . $documentoId) ?>">
                <i class="fas fa-arrow-left"></i>
                Volver al documento
            </a>
            <span class="text-sm text-slate-500">Los cambios quedan auditados solo si hay diferencias reales.</span>
        </div>

        <form method="POST" action="<?= url('documentos/' . $documentoId . '/actualizar') ?>" class="doc-edit-panel p-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_.9fr] gap-5">
                <div class="space-y-4">
                    <div>
                        <label class="doc-edit-label" for="titulo">Titulo</label>
                        <input
                            class="doc-edit-field"
                            type="text"
                            id="titulo"
                            name="titulo"
                            maxlength="180"
                            value="<?= doc_edit_safe($documento['titulo'] ?? '') ?>"
                            placeholder="Ej. Contrato de proveedor"
                        >
                    </div>

                    <div>
                        <label class="doc-edit-label" for="descripcion">Descripcion</label>
                        <textarea class="doc-edit-field" id="descripcion" name="descripcion" maxlength="255" placeholder="Descripcion breve del documento"><?= doc_edit_safe($documento['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="doc-edit-label" for="etiquetas">Etiquetas</label>
                        <input
                            class="doc-edit-field"
                            type="text"
                            id="etiquetas"
                            name="etiquetas"
                            maxlength="1000"
                            value="<?= doc_edit_safe($documento['etiquetas'] ?? '') ?>"
                            placeholder="contrato, compra, fiscal"
                        >
                    </div>

                    <div>
                        <label class="doc-edit-label" for="documento_tipo_id">Tipo documental</label>
                        <select class="doc-edit-field" id="documento_tipo_id" name="documento_tipo_id">
                            <option value="0">Sin tipo especifico</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <?php $tipoId = (int)($tipo['id'] ?? 0); ?>
                                <option value="<?= $tipoId ?>" <?= $tipoId === $tipoActual ? 'selected' : '' ?>>
                                    <?= doc_edit_safe($tipo['nombre'] ?? null) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="doc-edit-panel p-4">
                        <div class="font-black mb-3">Archivo protegido</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="doc-edit-meta-label">Documento</div>
                                <div class="font-black mt-1">#<?= $documentoId ?></div>
                            </div>
                            <div>
                                <div class="doc-edit-meta-label">Estado</div>
                                <div class="font-black mt-1"><?= doc_edit_safe($documento['estado'] ?? null, '-') ?></div>
                            </div>
                            <div class="sm:col-span-2">
                                <div class="doc-edit-meta-label">Nombre original</div>
                                <div class="font-black mt-1 break-words"><?= doc_edit_safe($documento['nombre_original'] ?? null, '-') ?></div>
                            </div>
                            <div>
                                <div class="doc-edit-meta-label">MIME</div>
                                <div class="font-black mt-1"><?= doc_edit_safe($documento['mime_type'] ?? null, '-') ?></div>
                            </div>
                            <div>
                                <div class="doc-edit-meta-label">Tamano</div>
                                <div class="font-black mt-1"><?= doc_edit_bytes($documento['size_bytes'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="doc-edit-panel p-4 bg-slate-50">
                        <div class="font-black mb-2">Fuera de alcance</div>
                        <ul class="text-sm text-slate-600 space-y-1">
                            <li>No reemplaza el archivo.</li>
                            <li>No cambia storage, hash, MIME ni tamano.</li>
                            <li>No cambia hotel ni vinculos.</li>
                            <li>No habilita borrado ni links publicos.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                <a class="doc-edit-btn doc-edit-btn-muted" href="<?= url('documentos/' . $documentoId) ?>">Cancelar</a>
                <button class="doc-edit-btn doc-edit-btn-primary" type="submit">
                    <i class="fas fa-save"></i>
                    Guardar metadata
                </button>
            </div>
        </form>
    </section>
</div>
