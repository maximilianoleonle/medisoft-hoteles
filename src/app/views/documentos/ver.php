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

if (!function_exists('doc_view_estado_meta')) {
    function doc_view_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'activo'    => ['Activo',    'is-activo',    'fa-circle-check'],
            'archivado' => ['Archivado', 'is-archivado', 'fa-box-archive'],
            'eliminado' => ['Eliminado', 'is-eliminado', 'fa-ban'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

if (!function_exists('doc_view_preview_kind')) {
    function doc_view_preview_kind($mime)
    {
        $m = strtolower(trim((string)($mime ?? '')));
        if ($m === 'application/pdf') {
            return 'pdf';
        }
        if (strpos($m, 'image/') === 0) {
            return 'image';
        }

        return null;
    }
}

$documentoId = (int)($documento['id'] ?? 0);
$docEstado = (string)($documento['estado'] ?? '');
[$estadoLabel, $estadoClass, $estadoIcon] = doc_view_estado_meta($docEstado);
$docPreviewKind = $docEstado === 'activo' ? doc_view_preview_kind($documento['mime_type'] ?? '') : null;
$docPreviewUrl = $docPreviewKind !== null ? url('documentos/' . $documentoId . '/descargar') . '?preview=1' : null;
?>

<style>
.doc-detail-page {
    --dc-brand: var(--brand-primary, #1B2746);
    --dc-brand-2: var(--brand-secondary, #0F172A);
    --dc-gold: var(--brand-accent, #BD9441);
    --dc-gold-soft: color-mix(in srgb, var(--dc-gold) 15%, #FFFFFF);
    --dc-gold-line: color-mix(in srgb, var(--dc-gold) 42%, #E4D4B0);
    --dc-gold-ink: color-mix(in srgb, var(--dc-gold) 78%, var(--dc-brand));
    --dc-ivory: #F6F2EA;
    --dc-ivory-2: #FBF8F2;
    --dc-surface: #FFFFFF;
    --dc-surface-warm: #FCFAF5;
    --dc-border: color-mix(in srgb, var(--dc-brand) 7%, #E7E1D4);
    --dc-text: color-mix(in srgb, var(--dc-brand) 36%, #596474);
    --dc-text-soft: color-mix(in srgb, var(--dc-brand) 30%, #6C7788);
    --dc-muted: #828B99;
    --dc-heading: color-mix(in srgb, var(--dc-brand) 62%, #667284);
    --dc-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --dc-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --dc-success: #1E9E63; --dc-success-bg: #E7F4EC;
    --dc-warning: #C2841C; --dc-warning-bg: #FAF0DC;
    --dc-danger: #B4392B; --dc-danger-bg: #F8EAE5;
    --dc-info: #2F77E0; --dc-info-bg: #E6EFFC;
    min-height: 100%;
    color: var(--dc-text);
    font-family: var(--dc-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--dc-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--dc-ivory-2), var(--dc-ivory));
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.doc-detail-page .dc-shell { display: grid; gap: 14px; }
.doc-detail-page .dc-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.doc-detail-page .dc-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--dc-gold), var(--dc-brand) 54%, color-mix(in srgb, var(--dc-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--dc-brand) 72%, transparent);
}
.doc-detail-page .dc-kicker { margin: 0 0 2px; color: var(--dc-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.doc-detail-page .dc-title { margin: 0; font-family: var(--dc-serif); color: var(--dc-heading); font-weight: 700; font-size: clamp(1.9rem, 3.4vw, 2.7rem); line-height: 1.02; }
.doc-detail-page .dc-subtitle { max-width: 48rem; margin: 8px 0 0; color: var(--dc-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.doc-detail-page .dc-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.doc-detail-page .dc-stat { background: var(--dc-surface); border: 1px solid var(--dc-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.doc-detail-page .dc-stat-label { color: var(--dc-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.doc-detail-page .dc-stat-value { margin-top: 2px; font-family: var(--dc-serif); font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: var(--dc-heading); }

.doc-detail-page .dc-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.doc-detail-page .dc-toolbar form { display: inline-flex; margin: 0; }
.doc-detail-page .dc-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 15px;
    border-radius: 11px; border: 1px solid var(--dc-border); background: var(--dc-surface); color: var(--dc-text); font-weight: 700; font-size: .84rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease;
}
.doc-detail-page .dc-btn:hover { transform: translateY(-1px); border-color: var(--dc-gold-line); color: var(--dc-gold-ink); }
.doc-detail-page .dc-btn-dl { color: var(--dc-success); border-color: color-mix(in srgb, var(--dc-success) 26%, var(--dc-border)); }
.doc-detail-page .dc-btn-dl:hover { color: var(--dc-success); background: var(--dc-success-bg); border-color: color-mix(in srgb, var(--dc-success) 40%, var(--dc-border)); }
.doc-detail-page .dc-btn-preview { color: var(--dc-gold-ink); border-color: var(--dc-gold-line); background: var(--dc-gold-soft); }
.doc-detail-page .dc-btn-preview:hover { color: var(--dc-gold-ink); background: color-mix(in srgb, var(--dc-gold) 20%, #fff); }
.doc-detail-page .dc-act { color: var(--dc-text); }
.doc-detail-page .dc-act-warn { color: color-mix(in srgb, var(--dc-warning) 82%, var(--dc-brand)); border-color: color-mix(in srgb, var(--dc-warning) 28%, var(--dc-border)); background: var(--dc-warning-bg); }
.doc-detail-page .dc-act-warn.is-confirming { background: linear-gradient(135deg, var(--dc-warning), color-mix(in srgb, var(--dc-warning) 72%, #000)); color: #fff; border-color: transparent; }
.doc-detail-page .dc-act-ok { color: color-mix(in srgb, var(--dc-success) 78%, var(--dc-brand)); border-color: color-mix(in srgb, var(--dc-success) 26%, var(--dc-border)); background: var(--dc-success-bg); }
.doc-detail-page .dc-act-ok.is-confirming { background: linear-gradient(135deg, var(--dc-success), color-mix(in srgb, var(--dc-success) 72%, #000)); color: #fff; border-color: transparent; }
.doc-detail-page .dc-act-danger { color: color-mix(in srgb, var(--dc-danger) 82%, var(--dc-brand)); border-color: color-mix(in srgb, var(--dc-danger) 26%, var(--dc-border)); background: var(--dc-danger-bg); }
.doc-detail-page .dc-act-danger.is-confirming { background: linear-gradient(135deg, var(--dc-danger), color-mix(in srgb, var(--dc-danger) 72%, #000)); color: #fff; border-color: transparent; }

.doc-action-toast {
    position: fixed; right: 22px; bottom: 22px; z-index: 15000; max-width: min(390px, calc(100vw - 32px));
    border: 1px solid var(--dc-gold-line, #E4D4B0); border-radius: 14px; background: var(--dc-gold-soft, #FBF3DE); color: var(--dc-gold-ink, #6b521f);
    padding: 12px 14px; box-shadow: 0 18px 42px rgba(24, 32, 48, .18); font-size: .82rem; font-weight: 600; line-height: 1.42;
    opacity: 0; transform: translateY(10px); pointer-events: none; transition: opacity .18s ease, transform .18s ease;
}
.doc-action-toast.is-visible { opacity: 1; transform: translateY(0); }

.doc-detail-page .dc-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: .76rem; font-weight: 700; border: 1px solid transparent; }
.doc-detail-page .dc-badge.is-activo { color: color-mix(in srgb, var(--dc-success) 78%, var(--dc-brand)); background: var(--dc-success-bg); border-color: color-mix(in srgb, var(--dc-success) 26%, #fff); }
.doc-detail-page .dc-badge.is-archivado { color: color-mix(in srgb, var(--dc-warning) 82%, var(--dc-brand)); background: var(--dc-warning-bg); border-color: color-mix(in srgb, var(--dc-warning) 28%, #fff); }
.doc-detail-page .dc-badge.is-eliminado { color: color-mix(in srgb, var(--dc-danger) 82%, var(--dc-brand)); background: var(--dc-danger-bg); border-color: color-mix(in srgb, var(--dc-danger) 26%, #fff); }
.doc-detail-page .dc-badge.is-soft { color: var(--dc-muted); background: var(--dc-surface-warm); border-color: var(--dc-border); }

.doc-detail-page .dc-panel { background: var(--dc-surface); border: 1px solid var(--dc-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.doc-detail-page .dc-panel-title { font-family: var(--dc-serif); font-size: 1.4rem; font-weight: 700; color: var(--dc-heading); }
.doc-detail-page .dc-preview-stage { min-height: 280px; display: grid; place-items: center; background: var(--dc-surface-warm); border-top: 1px solid var(--dc-border); }
.doc-detail-page .dc-preview-stage[hidden] { display: none; }
.doc-detail-page .dc-preview-empty { display: grid; place-items: center; gap: 10px; padding: 42px 20px; text-align: center; color: var(--dc-muted); }
.doc-detail-page .dc-preview-empty i { width: 54px; height: 54px; display: grid; place-items: center; border-radius: 16px; background: var(--dc-gold-soft); color: var(--dc-gold-ink); font-size: 1.25rem; }
.doc-detail-page .dc-preview-empty strong { color: var(--dc-text); font-size: .95rem; }
.doc-detail-page .dc-preview-empty p { margin: 3px 0 0; }
.doc-detail-page .dc-preview-empty .dc-preview-empty-btn { margin-top: 8px; min-width: 150px; }
.doc-detail-page .dc-preview-embed { width: 100%; height: min(74vh, 760px); min-height: 420px; border: 0; background: #f8fafc; }
.doc-detail-page .dc-preview-image { width: 100%; max-height: min(74vh, 760px); object-fit: contain; padding: 14px; }
.doc-detail-page .dc-meta-label { font-size: .68rem; color: var(--dc-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.doc-detail-page .dc-meta-value { margin-top: 3px; font-weight: 700; color: var(--dc-text); word-break: break-word; }
.doc-detail-page .dc-meta-value.is-soft { font-weight: 500; color: var(--dc-text-soft); }

.doc-detail-page .dc-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--dc-border); }
.doc-detail-page .dc-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.doc-detail-page .dc-table thead { background: var(--dc-surface-warm); border-bottom: 1px solid var(--dc-border); }
.doc-detail-page .dc-table th { padding: 12px 14px; color: var(--dc-muted); font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-align: left; }
.doc-detail-page .dc-table th.is-end, .doc-detail-page .dc-table td.is-end { text-align: right; }
.doc-detail-page .dc-table td { padding: 13px 14px; border-bottom: 1px solid var(--dc-border); vertical-align: middle; }
.doc-detail-page .dc-table tbody tr:last-child td { border-bottom: 0; }
.doc-detail-page .dc-table tbody tr:hover { background: var(--dc-ivory-2); }
.doc-detail-page .dc-strong { font-weight: 700; color: var(--dc-text); }
.doc-detail-page .dc-sub { color: var(--dc-muted); font-size: .72rem; }
.doc-detail-page .dc-link-btn { display: inline-flex; align-items: center; gap: .4rem; min-height: 32px; padding: 0 12px; border-radius: 9px; background: var(--dc-surface-warm); border: 1px solid var(--dc-border); color: var(--dc-info); font-size: .78rem; font-weight: 700; text-decoration: none; }
.doc-detail-page .dc-link-btn:hover { background: var(--dc-info-bg); }
.doc-detail-page .dc-empty { text-align: center; padding: 38px 18px; }
.doc-detail-page .dc-empty-icon { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 18px; display: grid; place-items: center; background: var(--dc-gold-soft); color: var(--dc-gold-ink); font-size: 1.25rem; }
.doc-detail-page .dc-empty h3 { color: var(--dc-heading); font-size: 1.05rem; font-weight: 700; }
.doc-detail-page .dc-empty p { color: var(--dc-muted); font-size: .88rem; margin-top: 6px; }

@media (max-width: 720px) {
    .doc-detail-page .dc-stats { grid-template-columns: 1fr; }
    .doc-detail-page .dc-preview-embed { height: 62vh; min-height: 340px; }
}
</style>

<div class="doc-detail-page p-4 sm:p-6">
    <div class="dc-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="dc-title-lockup">
                <div class="dc-hero-icon"><i class="fas fa-file-lines"></i></div>
                <div>
                    <p class="dc-kicker">Archivos del hotel</p>
                    <h1 class="dc-title"><?= doc_view_safe($documento['titulo'] ?? null, 'Documento #' . $documentoId) ?></h1>
                    <p class="dc-subtitle">Informaci&oacute;n del documento y descarga segura. El archivo se guarda en un espacio privado.</p>
                </div>
            </div>
            <span class="dc-badge <?= $estadoClass ?>">
                <i class="fas <?= $estadoIcon ?>"></i>
                <?= $estadoLabel ?>
            </span>
        </section>

        <section class="dc-stats">
            <div class="dc-stat"><p class="dc-stat-label">Tipo</p><p class="dc-stat-value"><?= doc_view_safe($documento['tipo_nombre'] ?? null, 'Sin tipo') ?></p></div>
            <div class="dc-stat"><p class="dc-stat-label">Tama&ntilde;o</p><p class="dc-stat-value"><?= doc_view_bytes($documento['size_bytes'] ?? 0) ?></p></div>
            <div class="dc-stat"><p class="dc-stat-label">V&iacute;nculos</p><p class="dc-stat-value"><?= count($entidades) ?></p></div>
        </section>

        <section class="dc-toolbar">
            <?php $back_arrow_href = back_url('documentos'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="dc-btn ms-back-legacy" href="<?= back_url('documentos') ?>">
                <i class="fas fa-arrow-left"></i>
                Volver
            </a>
            <?php if ($docEstado !== 'eliminado'): ?>
                <a class="dc-btn" href="<?= url('documentos/' . $documentoId . '/editar') ?>">
                    <i class="fas fa-pen"></i>
                    Editar informaci&oacute;n
                </a>
            <?php endif; ?>
            <?php if ($docEstado === 'activo'): ?>
                <a class="dc-btn dc-btn-dl" href="<?= url('documentos/' . $documentoId . '/descargar') ?>" download data-medisoft-download="silent">
                    <i class="fas fa-download"></i>
                    Descargar
                </a>
                <?php if ($docPreviewUrl): ?>
                    <a class="dc-btn dc-btn-preview" href="<?= doc_view_safe($docPreviewUrl, '') ?>" target="_blank" rel="noopener">
                        <i class="fas fa-magnifying-glass"></i>
                        Abrir preview
                    </a>
                <?php endif; ?>
                <form method="POST" action="<?= url('documentos/' . $documentoId . '/archivar') ?>" data-ms-confirm data-ms-type="warning" data-ms-icon="logout" data-ms-title="¿Archivar documento?" data-ms-msg="Archivar quita el documento de los activos. Podrás restaurarlo cuando quieras." data-ms-ok="Confirmar archivar">
                    <?= csrf_field() ?>
                    <button class="dc-btn dc-act dc-act-warn" type="submit">
                        <i class="fas fa-box-archive"></i>
                        Archivar
                    </button>
                </form>
            <?php elseif ($docEstado === 'archivado'): ?>
                <form method="POST" action="<?= url('documentos/' . $documentoId . '/restaurar') ?>" data-ms-confirm data-ms-type="success" data-ms-icon="login" data-ms-title="¿Restaurar documento?" data-ms-msg="Restaurar regresa el documento a los activos." data-ms-ok="Confirmar restaurar">
                    <?= csrf_field() ?>
                    <button class="dc-btn dc-act dc-act-ok" type="submit">
                        <i class="fas fa-rotate-left"></i>
                        Restaurar
                    </button>
                </form>
            <?php endif; ?>
            <?php if (in_array($docEstado, ['activo', 'archivado'], true)): ?>
                <form method="POST" action="<?= url('documentos/' . $documentoId . '/eliminar') ?>" data-ms-confirm data-ms-type="error" data-ms-icon="trash" data-ms-title="¿Dar de baja el documento?" data-ms-msg="Dar de baja oculta el documento, pero no borra el archivo ni sus vínculos. Lo verás en Eliminados." data-ms-ok="Confirmar baja">
                    <?= csrf_field() ?>
                    <button class="dc-btn dc-act dc-act-danger" type="submit">
                        <i class="fas fa-ban"></i>
                        Baja logica
                    </button>
                </form>
            <?php endif; ?>
        </section>

        <?php if ($docPreviewUrl): ?>
            <section class="dc-panel overflow-hidden" data-doc-inline-preview data-preview-url="<?= doc_view_safe($docPreviewUrl, '') ?>" data-preview-kind="<?= doc_view_safe($docPreviewKind, '') ?>" data-preview-name="<?= doc_view_safe($documento['nombre_original'] ?? ('Documento #' . $documentoId), '') ?>">
                <div class="dc-panel-head">
                    <div>
                        <h2 class="dc-panel-title">Previsualizaci&oacute;n</h2>
                        <p class="dc-sub">Disponible para PDF e im&aacute;genes. La vista se carga autom&aacute;ticamente.</p>
                    </div>
                    <button type="button" class="dc-btn dc-btn-preview" data-doc-preview-toggle>
                        <i class="fas fa-eye"></i>
                        Cargar vista
                    </button>
                </div>
                <div class="dc-preview-stage" data-doc-preview-stage>
                    <div class="dc-preview-empty">
                        <i class="fas fa-file-lines"></i>
                        <div>
                            <strong>Cargando vista del documento</strong>
                            <p>El archivo se mostrar&aacute; aqu&iacute; sin descargarlo.</p>
                        </div>
                        <button type="button" class="dc-btn dc-btn-preview dc-preview-empty-btn" data-doc-preview-load>
                            <i class="fas fa-eye"></i>
                            Reintentar vista
                        </button>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <div class="dc-panel p-5">
            <h2 class="dc-panel-title mb-4">Informaci&oacute;n del documento</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="dc-meta-label">Nombre del archivo</div>
                    <div class="dc-meta-value"><?= doc_view_safe($documento['nombre_original'] ?? null) ?></div>
                </div>
                <div>
                    <div class="dc-meta-label">Formato</div>
                    <div class="dc-meta-value"><?= doc_view_safe($documento['mime_type'] ?? null) ?></div>
                </div>
                <div>
                    <div class="dc-meta-label">Subido por</div>
                    <div class="dc-meta-value"><?= doc_view_safe($documento['subido_por_nombre'] ?? null, 'Sin usuario') ?></div>
                </div>
                <div>
                    <div class="dc-meta-label">Fecha</div>
                    <div class="dc-meta-value"><?= doc_view_safe($documento['created_at'] ?? null) ?></div>
                </div>
                <div class="md:col-span-2">
                    <div class="dc-meta-label">Descripci&oacute;n</div>
                    <div class="dc-meta-value is-soft"><?= doc_view_safe($documento['descripcion'] ?? null, 'Sin descripci&oacute;n') ?></div>
                </div>
                <div class="md:col-span-2">
                    <div class="dc-meta-label">Etiquetas</div>
                    <div class="dc-meta-value is-soft"><?= doc_view_safe($documento['etiquetas'] ?? null, 'Sin etiquetas') ?></div>
                </div>
            </div>
        </div>

        <div class="dc-panel overflow-hidden">
            <div class="dc-panel-head">
                <h2 class="dc-panel-title">Vinculado a</h2>
                <span class="dc-badge is-soft"><i class="fas fa-link"></i> Solo consulta</span>
            </div>

            <?php if (empty($entidades)): ?>
                <div class="dc-empty">
                    <div class="dc-empty-icon"><i class="fas fa-link-slash"></i></div>
                    <h3>Sin v&iacute;nculos</h3>
                    <p>Este documento no est&aacute; ligado a ning&uacute;n registro.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="dc-table">
                        <thead>
                            <tr>
                                <th>Registro</th>
                                <th>Relaci&oacute;n</th>
                                <th>Fecha</th>
                                <th class="is-end">Acci&oacute;n</th>
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
                                        <div class="dc-strong"><?= doc_view_entidad_label($entidadTipo) ?> #<?= $entidadId ?></div>
                                        <div class="dc-sub"><?= doc_view_safe($entidadTipo) ?></div>
                                    </td>
                                    <td><?= doc_view_safe($entidad['relacion'] ?? null, 'Sin relaci&oacute;n') ?></td>
                                    <td><?= doc_view_safe($entidad['created_at'] ?? null) ?></td>
                                    <td class="is-end">
                                        <?php if ($entidadUrl): ?>
                                            <a class="dc-link-btn" href="<?= $entidadUrl ?>">
                                                <i class="fas fa-eye"></i>
                                                Ver
                                            </a>
                                        <?php else: ?>
                                            <span class="dc-sub">Sin enlace</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    /* Las confirmaciones (archivar/restaurar/baja) usan el modal global msConfirm. */
    document.querySelectorAll('[data-doc-inline-preview]').forEach(panel => {
        const toggleButton = panel.querySelector('[data-doc-preview-toggle]');
        const stage = panel.querySelector('[data-doc-preview-stage]');
        const previewUrl = panel.dataset.previewUrl || '';
        const previewKind = panel.dataset.previewKind || '';
        const previewName = panel.dataset.previewName || 'Documento';

        if (!toggleButton || !stage || !previewUrl) {
            return;
        }

        function updatePreviewToggle() {
            if (panel.dataset.previewLoaded !== '1') {
                toggleButton.innerHTML = '<i class="fas fa-eye"></i> Cargar vista';
                return;
            }

            if (stage.hidden) {
                toggleButton.innerHTML = '<i class="fas fa-eye"></i> Mostrar vista';
                return;
            }

            toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i> Ocultar vista';
        }

        function loadPreview() {
            if (panel.dataset.previewLoaded === '1') {
                stage.hidden = false;
                updatePreviewToggle();
                return;
            }

            stage.hidden = false;
            stage.replaceChildren();

            if (previewKind === 'image') {
                const image = document.createElement('img');
                image.className = 'dc-preview-image';
                image.src = previewUrl;
                image.alt = previewName;
                image.loading = 'lazy';
                stage.appendChild(image);
            } else {
                const frame = document.createElement('iframe');
                frame.className = 'dc-preview-embed';
                frame.src = previewUrl;
                frame.title = 'Previsualizacion de ' + previewName;
                frame.loading = 'lazy';
                stage.appendChild(frame);
            }

            panel.dataset.previewLoaded = '1';
            updatePreviewToggle();
        }

        toggleButton.addEventListener('click', () => {
            if (panel.dataset.previewLoaded === '1' && !stage.hidden) {
                stage.hidden = true;
                updatePreviewToggle();
                return;
            }

            loadPreview();
        });

        stage.addEventListener('click', event => {
            const trigger = event.target instanceof Element ? event.target.closest('[data-doc-preview-load]') : null;
            if (!trigger) {
                return;
            }

            loadPreview();
        });

        loadPreview();
    });

})();
</script>
