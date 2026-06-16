<?php
$tipos = $tipos ?? [];
$entidadTipos = $entidadTipos ?? [];
$contextoEntidad = $contextoEntidad ?? null;

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

$tieneContexto = is_array($contextoEntidad);
?>

<style>
.doc-upload-page {
    --doc-brand: var(--brand-primary, #1f3f46);
    --doc-accent: var(--brand-accent, #b58a3c);
    --doc-line: color-mix(in srgb, var(--doc-brand) 10%, #e5e7eb);
    --doc-soft: color-mix(in srgb, var(--doc-accent) 7%, #f8fafc);
    color: #243142;
}
.doc-upload-page .doc-upload-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--doc-brand) 92%, #111827), color-mix(in srgb, var(--doc-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.doc-upload-page .doc-upload-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.doc-upload-page .doc-upload-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.doc-upload-page .doc-upload-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.doc-upload-page .doc-upload-panel {
    border: 1px solid var(--doc-line);
    background: rgba(255,255,255,.94);
}
.doc-upload-page .doc-upload-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 14px;
    border: 1px solid var(--doc-line);
    font-weight: 800;
}
.doc-upload-page .doc-upload-btn-primary {
    background: var(--doc-brand);
    border-color: var(--doc-brand);
    color: #fff;
}
.doc-upload-page .doc-upload-btn-muted {
    background: #fff;
    color: #334155;
}
.doc-upload-page .doc-upload-field {
    width: 100%;
    min-height: 42px;
    border: 1px solid var(--doc-line);
    background: #fff;
    padding: 0 12px;
}
.doc-upload-page textarea.doc-upload-field {
    min-height: 104px;
    padding: 10px 12px;
}
.doc-upload-page .doc-upload-label {
    display: block;
    margin-bottom: 6px;
    color: #334155;
    font-size: .78rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.doc-upload-page .doc-upload-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border: 1px solid var(--doc-line);
    background: var(--doc-soft);
    font-size: .78rem;
    font-weight: 800;
}
</style>

<div class="doc-upload-page">
    <section class="doc-upload-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="doc-upload-kicker">Centro documental</div>
                <h1 class="doc-upload-title">Subir documento</h1>
                <p class="doc-upload-subtitle">
                    Carga controlada hacia storage privado. No se habilita descarga, edicion ni borrado en esta fase.
                </p>
            </div>
            <span class="doc-upload-badge">
                <i class="fas fa-lock"></i>
                Storage privado
            </span>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <a class="doc-upload-btn doc-upload-btn-muted" href="<?= url('documentos') ?>">
                <i class="fas fa-arrow-left"></i>
                Centro documental
            </a>
            <span class="text-sm text-slate-500">PDF, JPG, PNG o WEBP. Maximo 10 MB.</span>
        </div>

        <form method="POST" action="<?= url('documentos/subir') ?>" enctype="multipart/form-data" class="doc-upload-panel p-5">
            <?= csrf_field() ?>
            <input type="hidden" name="MAX_FILE_SIZE" value="10485760">

            <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_.9fr] gap-5">
                <div class="space-y-4">
                    <div>
                        <label class="doc-upload-label" for="archivo">Archivo</label>
                        <input
                            class="doc-upload-field py-2"
                            type="file"
                            id="archivo"
                            name="archivo"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                            required
                        >
                        <p class="mt-2 text-sm text-slate-500">El nombre interno y la ruta privada no se muestran al usuario.</p>
                    </div>

                    <div>
                        <label class="doc-upload-label" for="titulo">Titulo</label>
                        <input class="doc-upload-field" type="text" id="titulo" name="titulo" maxlength="180" placeholder="Ej. Contrato de proveedor">
                    </div>

                    <div>
                        <label class="doc-upload-label" for="descripcion">Descripcion</label>
                        <textarea class="doc-upload-field" id="descripcion" name="descripcion" maxlength="255" placeholder="Descripcion breve del documento"></textarea>
                    </div>

                    <div>
                        <label class="doc-upload-label" for="etiquetas">Etiquetas</label>
                        <input class="doc-upload-field" type="text" id="etiquetas" name="etiquetas" maxlength="1000" placeholder="contrato, compra, fiscal">
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="doc-upload-label" for="documento_tipo_id">Tipo documental</label>
                        <select class="doc-upload-field" id="documento_tipo_id" name="documento_tipo_id">
                            <option value="0">Sin tipo especifico</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <option value="<?= (int)($tipo['id'] ?? 0) ?>">
                                    <?= doc_upload_safe($tipo['nombre'] ?? null) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($tipos)): ?>
                            <p class="mt-2 text-sm text-slate-500">No hay tipos activos; se usaran reglas base de seguridad.</p>
                        <?php endif; ?>
                    </div>

                    <div class="doc-upload-panel p-4">
                        <div class="doc-upload-label">Vinculo con entidad</div>
                        <?php if ($tieneContexto): ?>
                            <input type="hidden" name="entidad_tipo" value="<?= doc_upload_safe($contextoEntidad['tipo'] ?? '', '') ?>">
                            <input type="hidden" name="entidad_id" value="<?= (int)($contextoEntidad['id'] ?? 0) ?>">
                            <div class="font-black">
                                <?= doc_upload_safe($contextoEntidad['label'] ?? doc_upload_label($contextoEntidad['tipo'] ?? '')) ?>
                                #<?= (int)($contextoEntidad['id'] ?? 0) ?>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">El documento se vinculara a esta entidad del hotel actual.</p>
                        <?php else: ?>
                            <label class="doc-upload-label mt-1" for="entidad_tipo">Entidad opcional</label>
                            <select class="doc-upload-field" id="entidad_tipo" name="entidad_tipo">
                                <option value="">Sin vinculo inicial</option>
                                <?php foreach ($entidadTipos as $entidadTipo): ?>
                                    <option value="<?= doc_upload_safe($entidadTipo, '') ?>">
                                        <?= doc_upload_safe(doc_upload_label($entidadTipo)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label class="doc-upload-label mt-4" for="entidad_id">ID de entidad</label>
                            <input class="doc-upload-field" type="number" min="1" step="1" id="entidad_id" name="entidad_id" placeholder="Opcional">
                        <?php endif; ?>

                        <label class="doc-upload-label mt-4" for="relacion">Relacion</label>
                        <input class="doc-upload-field" type="text" id="relacion" name="relacion" maxlength="80" placeholder="Ej. comprobante, contrato, evidencia">
                    </div>

                    <div class="doc-upload-panel p-4 bg-slate-50">
                        <div class="font-black mb-2">Reglas de esta fase</div>
                        <ul class="text-sm text-slate-600 space-y-1">
                            <li>Sin descarga publica.</li>
                            <li>Sin edicion ni borrado.</li>
                            <li>Validacion por hotel y entidad.</li>
                            <li>Storage privado fuera de public_html.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                <a class="doc-upload-btn doc-upload-btn-muted" href="<?= url('documentos') ?>">Cancelar</a>
                <button class="doc-upload-btn doc-upload-btn-primary" type="submit">
                    <i class="fas fa-upload"></i>
                    Subir documento
                </button>
            </div>
        </form>
    </section>
</div>
