<?php
$documentos = $documentos ?? [];
$resumen = $resumen ?? [];
$tipos = $tipos ?? [];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;
$contextoEntidad = $contextoEntidad ?? null;

if (!function_exists('doc_safe')) {
    function doc_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('doc_bytes')) {
    function doc_bytes($value)
    {
        $bytes = max(0, (int)($value ?? 0));
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
$tipoFiltro = (int)($filtros['documento_tipo_id'] ?? 0);
$esEntidad = is_array($contextoEntidad);
$uploadUrl = url('documentos/subir');

if ($esEntidad) {
    $uploadUrl .= '?' . http_build_query([
        'entidad_tipo' => (string)($contextoEntidad['tipo'] ?? ''),
        'entidad_id' => (int)($contextoEntidad['id'] ?? 0),
    ]);
}
?>

<style>
.docs-page {
    --docs-brand: var(--brand-primary, #1f3f46);
    --docs-accent: var(--brand-accent, #b58a3c);
    --docs-line: color-mix(in srgb, var(--docs-brand) 10%, #e5e7eb);
    --docs-soft: color-mix(in srgb, var(--docs-accent) 7%, #f8fafc);
    color: #243142;
}
.docs-page .docs-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--docs-brand) 92%, #111827), color-mix(in srgb, var(--docs-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.docs-page .docs-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.docs-page .docs-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.docs-page .docs-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.docs-page .docs-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.docs-page .docs-panel {
    border: 1px solid var(--docs-line);
    background: rgba(255,255,255,.92);
}
.docs-page .docs-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--docs-line);
    font-weight: 800;
}
.docs-page .docs-btn-primary {
    background: var(--docs-brand);
    border-color: var(--docs-brand);
    color: #fff;
}
.docs-page .docs-btn-muted {
    background: #fff;
    color: #334155;
}
.docs-page .docs-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--docs-line);
    background: #fff;
    padding: 0 12px;
}
.docs-page .docs-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.docs-page .docs-table td,
.docs-page .docs-table th {
    border-bottom: 1px solid var(--docs-line);
    padding: 14px 12px;
}
.docs-page .docs-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--docs-line);
    background: var(--docs-soft);
    font-size: .78rem;
    font-weight: 800;
}
</style>

<div class="docs-page">
    <section class="docs-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="docs-kicker">Centro documental</div>
                <h1 class="docs-title"><?= $esEntidad ? doc_safe($contextoEntidad['label'] ?? 'Entidad') . ' #' . (int)($contextoEntidad['id'] ?? 0) : 'Documentos' ?></h1>
                <p class="docs-subtitle">
                    Metadata documental del hotel actual. Los archivos se descargan de forma autenticada desde storage privado.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[320px]">
                <div class="docs-stat">
                    <div class="text-xs opacity-75">Documentos</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="docs-stat">
                    <div class="text-xs opacity-75">Activos</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['activos'] ?? 0) ?></div>
                </div>
                <div class="docs-stat">
                    <div class="text-xs opacity-75">Archivados</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['archivados'] ?? 0) ?></div>
                </div>
                <div class="docs-stat">
                    <div class="text-xs opacity-75">Tamano</div>
                    <div class="text-xl font-black"><?= doc_bytes($resumen['bytes_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <?php if (!$tablaDisponible): ?>
            <div class="docs-panel p-5">
                <strong>Centro documental no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">La migracion base documental no esta aplicada en esta instalacion.</p>
            </div>
        <?php else: ?>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <?php if ($esEntidad): ?>
                        <a class="docs-btn docs-btn-muted" href="<?= url('documentos') ?>">
                            <i class="fas fa-arrow-left"></i>
                            Centro documental
                        </a>
                    <?php endif; ?>
                    <a class="docs-btn docs-btn-primary" href="<?= doc_safe($uploadUrl, '') ?>">
                        <i class="fas fa-upload"></i>
                        Subir documento
                    </a>
                </div>
                <span class="docs-badge">
                    <i class="fas fa-shield-alt"></i>
                    Descarga privada
                </span>
            </div>

            <?php if (!$esEntidad): ?>
                <div class="docs-panel p-4 mb-4">
                    <form method="GET" action="<?= url('documentos') ?>" class="grid grid-cols-1 md:grid-cols-[1fr_180px_220px_auto] gap-3">
                        <input class="docs-input" type="search" name="buscar" value="<?= doc_safe($buscar, '') ?>" placeholder="Buscar por titulo, archivo, tipo o MIME">
                        <select class="docs-input" name="estado">
                            <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activos</option>
                            <option value="archivado" <?= $estado === 'archivado' ? 'selected' : '' ?>>Archivados</option>
                            <option value="eliminado" <?= $estado === 'eliminado' ? 'selected' : '' ?>>Eliminados</option>
                        </select>
                        <select class="docs-input" name="documento_tipo_id">
                            <option value="0">Todos los tipos</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <?php $tipoId = (int)($tipo['id'] ?? 0); ?>
                                <option value="<?= $tipoId ?>" <?= $tipoFiltro === $tipoId ? 'selected' : '' ?>>
                                    <?= doc_safe($tipo['nombre'] ?? null) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="docs-btn docs-btn-primary" type="submit">
                            <i class="fas fa-filter"></i>
                            Filtrar
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="docs-panel overflow-hidden">
                <?php if (empty($documentos)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-folder-open"></i></div>
                        <h2 class="font-black text-lg">Sin documentos</h2>
                        <p class="text-sm text-slate-500 mt-1">La estructura esta lista para cargas seguras; aun no hay metadata documental registrada.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="docs-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Documento</th>
                                    <th class="text-left">Tipo</th>
                                    <th class="text-left">Archivo</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-left">Entidades</th>
                                    <th class="text-left">Fecha</th>
                                    <th class="text-right">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documentos as $documento): ?>
                                    <tr>
                                        <td>
                                            <a class="font-black text-slate-800 underline" href="<?= url('documentos/' . (int)($documento['id'] ?? 0)) ?>">
                                                <?= doc_safe($documento['titulo'] ?? null, 'Documento #' . (int)($documento['id'] ?? 0)) ?>
                                            </a>
                                            <div class="text-xs text-slate-500"><?= doc_safe($documento['descripcion'] ?? null, 'Sin descripcion') ?></div>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?= doc_safe($documento['tipo_nombre'] ?? null, 'Sin tipo') ?></div>
                                            <div class="text-xs text-slate-500"><?= doc_safe($documento['tipo_clave'] ?? null, '') ?></div>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?= doc_safe($documento['nombre_original'] ?? null) ?></div>
                                            <div class="text-xs text-slate-500"><?= doc_safe($documento['mime_type'] ?? null) ?> &middot; <?= doc_bytes($documento['size_bytes'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <span class="docs-badge">
                                                <i class="fas fa-circle-dot"></i>
                                                <?= doc_safe($documento['estado'] ?? null) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?= (int)($documento['entidades_count'] ?? 0) ?> vinculo(s)</div>
                                            <div class="text-xs text-slate-500"><?= doc_safe($documento['entidades_resumen'] ?? null, 'Sin entidades') ?></div>
                                        </td>
                                        <td>
                                            <div><?= doc_safe($documento['created_at'] ?? null) ?></div>
                                            <div class="text-xs text-slate-500"><?= doc_safe($documento['subido_por_nombre'] ?? null, 'Sin usuario') ?></div>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex justify-end gap-2">
                                                    <a class="docs-btn docs-btn-muted" href="<?= url('documentos/' . (int)($documento['id'] ?? 0)) ?>">
                                                        <i class="fas fa-eye"></i>
                                                        Ver
                                                    </a>
                                                    <?php if (($documento['estado'] ?? '') !== 'eliminado'): ?>
                                                        <a class="docs-btn docs-btn-muted" href="<?= url('documentos/' . (int)($documento['id'] ?? 0) . '/editar') ?>">
                                                            <i class="fas fa-edit"></i>
                                                            Editar
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (($documento['estado'] ?? '') === 'activo'): ?>
                                                        <a class="docs-btn docs-btn-muted" href="<?= url('documentos/' . (int)($documento['id'] ?? 0) . '/descargar') ?>">
                                                            <i class="fas fa-download"></i>
                                                        Descargar
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
