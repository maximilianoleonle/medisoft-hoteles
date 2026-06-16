<?php
$documento = $documento ?? [];
$entidades = $entidades ?? [];

if (!function_exists('doc_view_safe')) {
    function doc_view_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('doc_view_bytes')) {
    function doc_view_bytes($value)
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

if (!function_exists('doc_view_entidad_label')) {
    function doc_view_entidad_label($tipo)
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

if (!function_exists('doc_view_entidad_url')) {
    function doc_view_entidad_url($tipo, $id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        $routes = [
            'proveedor' => 'proveedores/' . $id,
            'compra' => 'compras/' . $id,
            'cuenta_por_pagar' => 'cuentas-por-pagar/' . $id,
            'huesped' => 'huespedes/' . $id,
            'reservacion' => 'reservaciones/ver/' . $id,
        ];

        return isset($routes[(string)$tipo]) ? url($routes[(string)$tipo]) : null;
    }
}

$documentoId = (int)($documento['id'] ?? 0);
?>

<style>
.doc-detail-page {
    --doc-brand: var(--brand-primary, #1f3f46);
    --doc-accent: var(--brand-accent, #b58a3c);
    --doc-line: color-mix(in srgb, var(--doc-brand) 10%, #e5e7eb);
    --doc-soft: color-mix(in srgb, var(--doc-accent) 7%, #f8fafc);
    color: #243142;
}
.doc-detail-page .doc-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--doc-brand) 92%, #111827), color-mix(in srgb, var(--doc-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.doc-detail-page .doc-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.doc-detail-page .doc-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.doc-detail-page .doc-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.doc-detail-page .doc-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.doc-detail-page .doc-panel {
    border: 1px solid var(--doc-line);
    background: rgba(255,255,255,.92);
}
.doc-detail-page .doc-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--doc-line);
    font-weight: 800;
}
.doc-detail-page .doc-btn-muted {
    background: #fff;
    color: #334155;
}
.doc-detail-page .doc-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--doc-line);
    background: var(--doc-soft);
    font-size: .78rem;
    font-weight: 800;
}
.doc-detail-page .doc-meta-label {
    font-size: .72rem;
    color: #64748b;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.doc-detail-page .doc-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.doc-detail-page .doc-table td,
.doc-detail-page .doc-table th {
    border-bottom: 1px solid var(--doc-line);
    padding: 14px 12px;
}
</style>

<div class="doc-detail-page">
    <section class="doc-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="doc-kicker">Centro documental</div>
                <h1 class="doc-title"><?= doc_view_safe($documento['titulo'] ?? null, 'Documento #' . $documentoId) ?></h1>
                <p class="doc-subtitle">
                    Ficha de metadata y descarga autenticada. El archivo se sirve desde storage privado sin exponer rutas internas.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[320px]">
                <div class="doc-stat">
                    <div class="text-xs opacity-75">Estado</div>
                    <div class="text-xl font-black"><?= doc_view_safe($documento['estado'] ?? null) ?></div>
                </div>
                <div class="doc-stat">
                    <div class="text-xs opacity-75">Tipo</div>
                    <div class="text-xl font-black"><?= doc_view_safe($documento['tipo_nombre'] ?? null, 'Sin tipo') ?></div>
                </div>
                <div class="doc-stat">
                    <div class="text-xs opacity-75">Tamano</div>
                    <div class="text-xl font-black"><?= doc_view_bytes($documento['size_bytes'] ?? 0) ?></div>
                </div>
                <div class="doc-stat">
                    <div class="text-xs opacity-75">Vinculos</div>
                    <div class="text-2xl font-black"><?= count($entidades) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="doc-btn doc-btn-muted" href="<?= url('documentos') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <?php if (($documento['estado'] ?? '') === 'activo'): ?>
                    <a class="doc-btn doc-btn-muted" href="<?= url('documentos/' . $documentoId . '/descargar') ?>">
                        <i class="fas fa-download"></i>
                        Descargar
                    </a>
                <?php endif; ?>
            </div>
            <span class="doc-badge">
                <i class="fas fa-shield-alt"></i>
                Descarga privada
            </span>
        </div>

        <div class="doc-panel p-5 mb-4">
            <h2 class="font-black text-lg mb-4">Metadata</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="doc-meta-label">Nombre original</div>
                    <div class="font-black mt-1"><?= doc_view_safe($documento['nombre_original'] ?? null) ?></div>
                </div>
                <div>
                    <div class="doc-meta-label">MIME</div>
                    <div class="font-black mt-1"><?= doc_view_safe($documento['mime_type'] ?? null) ?></div>
                </div>
                <div>
                    <div class="doc-meta-label">Subido por</div>
                    <div class="font-black mt-1"><?= doc_view_safe($documento['subido_por_nombre'] ?? null, 'Sin usuario') ?></div>
                </div>
                <div>
                    <div class="doc-meta-label">Fecha</div>
                    <div class="font-black mt-1"><?= doc_view_safe($documento['created_at'] ?? null) ?></div>
                </div>
                <div class="md:col-span-2">
                    <div class="doc-meta-label">Descripcion</div>
                    <div class="mt-1 text-slate-700"><?= doc_view_safe($documento['descripcion'] ?? null, 'Sin descripcion') ?></div>
                </div>
                <div class="md:col-span-2">
                    <div class="doc-meta-label">Etiquetas</div>
                    <div class="mt-1 text-slate-700"><?= doc_view_safe($documento['etiquetas'] ?? null, 'Sin etiquetas') ?></div>
                </div>
            </div>
        </div>

        <div class="doc-panel overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-black text-lg">Entidades vinculadas</h2>
                <span class="doc-badge">
                    <i class="fas fa-link"></i>
                    Read-only
                </span>
            </div>

            <?php if (empty($entidades)): ?>
                <div class="p-8 text-center">
                    <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-link-slash"></i></div>
                    <h3 class="font-black text-lg">Sin vinculos</h3>
                    <p class="text-sm text-slate-500 mt-1">Este documento no tiene entidades relacionadas registradas.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="doc-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Entidad</th>
                                <th class="text-left">Relacion</th>
                                <th class="text-left">Fecha</th>
                                <th class="text-right">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entidades as $entidad): ?>
                                <?php
                                $entidadTipo = (string)($entidad['entidad_tipo'] ?? '');
                                $entidadId = (int)($entidad['entidad_id'] ?? 0);
                                $entidadUrl = doc_view_entidad_url($entidadTipo, $entidadId);
                                ?>
                                <tr>
                                    <td>
                                        <div class="font-black"><?= doc_view_entidad_label($entidadTipo) ?> #<?= $entidadId ?></div>
                                        <div class="text-xs text-slate-500"><?= doc_view_safe($entidadTipo) ?></div>
                                    </td>
                                    <td><?= doc_view_safe($entidad['relacion'] ?? null, 'Sin relacion') ?></td>
                                    <td><?= doc_view_safe($entidad['created_at'] ?? null) ?></td>
                                    <td class="text-right">
                                        <?php if ($entidadUrl): ?>
                                            <a class="doc-btn doc-btn-muted" href="<?= $entidadUrl ?>">
                                                <i class="fas fa-eye"></i>
                                                Ver entidad
                                            </a>
                                        <?php else: ?>
                                            <span class="text-sm text-slate-400">Sin ruta</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
