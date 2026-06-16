<?php
$documentosEntidad = is_array($documentosEntidad ?? null) ? $documentosEntidad : [];
$documentosEntidadContexto = is_array($documentosEntidadContexto ?? null) ? $documentosEntidadContexto : [];

if (!function_exists('doc_entity_safe')) {
    function doc_entity_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('doc_entity_bytes')) {
    function doc_entity_bytes($bytes): string
    {
        $bytes = (int)($bytes ?? 0);
        if ($bytes <= 0) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float)$bytes;
        $unitIndex = 0;
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, $unitIndex === 0 ? 0 : 1) . ' ' . $units[$unitIndex];
    }
}

if (!function_exists('doc_entity_date')) {
    function doc_entity_date($value): string
    {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return '-';
        }

        if (function_exists('format_datetime')) {
            return format_datetime($text);
        }

        if (function_exists('format_date')) {
            return format_date($text);
        }

        return doc_entity_safe($text);
    }
}

$entityLabel = doc_entity_safe($documentosEntidadContexto['label'] ?? 'Entidad');
$entityTipo = doc_entity_safe($documentosEntidadContexto['tipo'] ?? '');
$entityId = (int)($documentosEntidadContexto['id'] ?? 0);
$hasEntityContext = $entityTipo !== '' && $entityId > 0;
$entityQuery = $hasEntityContext
    ? '?entidad_tipo=' . rawurlencode((string)($documentosEntidadContexto['tipo'] ?? '')) . '&entidad_id=' . $entityId
    : '';
?>

<?php if (!defined('DOCUMENTOS_ENTIDAD_PARTIAL_CSS')): ?>
    <?php define('DOCUMENTOS_ENTIDAD_PARTIAL_CSS', true); ?>
    <style>
        .documentos-entidad-panel {
            border: 1px solid color-mix(in srgb, var(--brand-primary, #1f3f46) 10%, #e5e7eb);
            background: rgba(255, 255, 255, .94);
            color: #243142;
        }
        .documentos-entidad-panel .de-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 9px;
            border: 1px solid color-mix(in srgb, var(--brand-primary, #1f3f46) 10%, #e5e7eb);
            background: color-mix(in srgb, var(--brand-accent, #b58a3c) 7%, #f8fafc);
            font-size: .78rem;
            font-weight: 800;
        }
        .documentos-entidad-panel .de-table th {
            color: #64748b;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .documentos-entidad-panel .de-table td,
        .documentos-entidad-panel .de-table th {
            border-bottom: 1px solid color-mix(in srgb, var(--brand-primary, #1f3f46) 9%, #e5e7eb);
            padding: 13px 12px;
        }
        .documentos-entidad-panel .de-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 32px;
            padding: 0 10px;
            border: 1px solid color-mix(in srgb, var(--brand-primary, #1f3f46) 10%, #e5e7eb);
            background: #fff;
            color: #334155;
            font-size: .78rem;
            font-weight: 800;
        }
        .documentos-entidad-panel .de-action:hover {
            background: #f8fafc;
        }
    </style>
<?php endif; ?>

<div class="documentos-entidad-panel overflow-hidden mb-4">
    <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-black text-lg">Documentos vinculados</h2>
            <p class="text-sm text-slate-500 mt-1">
                <?= $entityLabel ?>
                <?php if ($entityTipo !== '' && $entityId > 0): ?>
                    <span class="text-slate-400">/ <?= $entityTipo ?> #<?= $entityId ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="inline-flex flex-wrap items-center justify-end gap-2">
            <span class="de-badge">
                <i class="fas fa-lock"></i>
                Lista segura
            </span>
            <?php if ($hasEntityContext): ?>
                <a class="de-action" href="<?= url('documentos/entidad/' . rawurlencode((string)($documentosEntidadContexto['tipo'] ?? '')) . '/' . $entityId) ?>">
                    <i class="fas fa-folder-tree"></i>
                    Ver todos
                </a>
                <a class="de-action" href="<?= url('documentos/subir' . $entityQuery) ?>">
                    <i class="fas fa-paperclip"></i>
                    Vincular documento
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($documentosEntidad)): ?>
        <div class="p-8 text-center">
            <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-folder-open"></i></div>
            <h3 class="font-black text-lg">Sin documentos vinculados</h3>
            <p class="text-sm text-slate-500 mt-1">
                Esta ficha aun no tiene documentos asociados en el Centro Documental.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="de-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="text-left">Documento</th>
                        <th class="text-left">Tipo</th>
                        <th class="text-left">Relacion</th>
                        <th class="text-left">Estado</th>
                        <th class="text-right">Tamano</th>
                        <th class="text-left">Subido</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentosEntidad as $documento): ?>
                        <?php
                        $documentoId = (int)($documento['id'] ?? 0);
                        $estado = trim((string)($documento['estado'] ?? ''));
                        $titulo = trim((string)($documento['titulo'] ?? ''));
                        $nombre = $titulo !== '' ? $titulo : ($documento['nombre_original'] ?? 'Documento');
                        ?>
                        <tr>
                            <td>
                                <div class="font-black text-slate-800"><?= doc_entity_safe($nombre, 'Documento') ?></div>
                                <div class="text-xs text-slate-500"><?= doc_entity_safe($documento['nombre_original'] ?? null) ?></div>
                            </td>
                            <td>
                                <div class="font-bold text-slate-700"><?= doc_entity_safe($documento['tipo_nombre'] ?? null, 'Sin tipo') ?></div>
                                <div class="text-xs text-slate-500"><?= doc_entity_safe($documento['mime_type'] ?? null) ?></div>
                            </td>
                            <td><?= doc_entity_safe($documento['relacion'] ?? null, 'General') ?></td>
                            <td>
                                <span class="de-badge">
                                    <i class="fas fa-circle-dot"></i>
                                    <?= doc_entity_safe($estado, 'activo') ?>
                                </span>
                            </td>
                            <td class="text-right"><?= doc_entity_bytes($documento['size_bytes'] ?? 0) ?></td>
                            <td>
                                <div class="font-bold text-slate-700"><?= doc_entity_date($documento['created_at'] ?? null) ?></div>
                                <div class="text-xs text-slate-500"><?= doc_entity_safe($documento['subido_por_nombre'] ?? null, 'Sin usuario') ?></div>
                            </td>
                            <td class="text-right">
                                <?php if ($documentoId > 0): ?>
                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                        <a class="de-action" href="<?= url('documentos/' . $documentoId) ?>">
                                            <i class="fas fa-eye"></i>
                                            Ver
                                        </a>
                                        <?php if ($estado === '' || $estado === 'activo'): ?>
                                            <a class="de-action" href="<?= url('documentos/' . $documentoId . '/descargar') ?>">
                                                <i class="fas fa-download"></i>
                                                Descargar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
