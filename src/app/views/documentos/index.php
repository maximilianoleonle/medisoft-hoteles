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

if (!function_exists('doc_estado_meta')) {
    function doc_estado_meta($estado)
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

if (!function_exists('doc_mime_meta')) {
    function doc_mime_meta($mime)
    {
        $m = strtolower(trim((string)($mime ?? '')));
        if ($m === 'application/pdf') {
            return ['fa-file-pdf', 'is-pdf'];
        }
        if (strpos($m, 'image/') === 0) {
            return ['fa-file-image', 'is-img'];
        }
        return ['fa-file', 'is-file'];
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
$visibles = count($documentos);
?>

<style>
.docs-page {
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
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.docs-page .dc-shell { display: grid; gap: 14px; }
.docs-page .dc-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.docs-page .dc-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--dc-gold), var(--dc-brand) 54%, color-mix(in srgb, var(--dc-brand) 68%, #2F8A70));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--dc-brand) 72%, transparent);
}
.docs-page .dc-kicker { margin: 0 0 2px; color: var(--dc-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.docs-page .dc-title { margin: 0; font-family: var(--dc-serif); color: var(--dc-heading); font-weight: 700; font-size: clamp(2.1rem, 4vw, 3rem); line-height: .98; }
.docs-page .dc-subtitle { max-width: 48rem; margin: 9px 0 0; color: var(--dc-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }

.docs-page .dc-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 42px; padding: 0 18px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .9rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.docs-page .dc-btn:hover { transform: translateY(-1px); }
.docs-page .dc-btn:active { transform: translateY(0) scale(.98); }
.docs-page .dc-btn:focus-visible { outline: 3px solid var(--dc-ring); outline-offset: 2px; }
.docs-page .dc-btn-gold { background: linear-gradient(135deg, var(--dc-gold), color-mix(in srgb, var(--dc-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--dc-gold) 58%, transparent); }
.docs-page .dc-btn-muted { background: var(--dc-surface); border-color: var(--dc-border); color: var(--dc-muted); }

.docs-page .dc-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.docs-page .dc-summary-item { background: var(--dc-surface); border: 1px solid var(--dc-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.docs-page .dc-summary-label { color: var(--dc-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.docs-page .dc-summary-value { margin-top: 2px; font-family: var(--dc-serif); font-size: 1.7rem; font-weight: 700; line-height: 1.1; color: var(--dc-heading); }
.docs-page .dc-summary-value.is-active { color: var(--dc-success); }

.docs-page .dc-panel { background: var(--dc-surface); border: 1px solid var(--dc-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.docs-page .dc-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }

.docs-page .dc-filter-form { display: grid; grid-template-columns: minmax(200px, 1fr) 170px 200px auto auto; gap: 10px; align-items: center; }
.docs-page .dc-control { width: 100%; min-height: 40px; border: 1px solid var(--dc-border); background: var(--dc-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--dc-text); font-weight: 600; font-size: .88rem; transition: border-color .16s ease, box-shadow .16s ease; }
.docs-page .dc-control:focus { border-color: var(--dc-gold); box-shadow: 0 0 0 3px var(--dc-ring); outline: none; }
.docs-page select.dc-control { cursor: pointer; }
.docs-page .dc-search { position: relative; }
.docs-page .dc-search .dc-control { padding-left: 38px; }
.docs-page .dc-search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--dc-muted); font-size: .9rem; pointer-events: none; }

.docs-page .dc-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--dc-border); }
.docs-page .dc-panel-title { font-size: .85rem; font-weight: 700; color: var(--dc-heading); }
.docs-page .dc-panel-sub { font-size: .75rem; color: var(--dc-muted); }
.docs-page .dc-count-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .36rem .66rem; border-radius: 999px; background: var(--dc-gold-soft); color: var(--dc-gold-ink); border: 1px solid var(--dc-gold-line); font-size: .72rem; font-weight: 700; white-space: nowrap; }

.docs-page .dc-desktop { display: none; }
.docs-page .dc-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: .85rem; }
.docs-page .dc-table thead { background: var(--dc-surface-warm); border-bottom: 1px solid var(--dc-border); }
.docs-page .dc-table th { padding: 12px 16px; color: var(--dc-muted); font-size: .68rem; font-weight: 700; letter-spacing: .07em; text-align: left; text-transform: uppercase; }
.docs-page .dc-table th.is-end { text-align: right; }
.docs-page .dc-table td { padding: 13px 16px; vertical-align: middle; }
.docs-page .dc-table td.is-end { text-align: right; }
.docs-page .dc-row { border-bottom: 1px solid var(--dc-border); transition: background .16s ease, box-shadow .16s ease; }
.docs-page .dc-row:last-child { border-bottom: 0; }
.docs-page .dc-row:hover { background: var(--dc-ivory-2); box-shadow: 0 10px 24px -24px rgba(27,39,70,.48); }
.docs-page .dc-doc-cell { display: flex; align-items: center; gap: 11px; min-width: 0; }
.docs-page .dc-fileicon { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; flex-shrink: 0; font-size: 1rem; }
.docs-page .dc-fileicon.is-pdf { background: var(--dc-danger-bg); color: var(--dc-danger); }
.docs-page .dc-fileicon.is-img { background: var(--dc-info-bg); color: var(--dc-info); }
.docs-page .dc-fileicon.is-file { background: var(--dc-surface-warm); color: var(--dc-muted); border: 1px solid var(--dc-border); }
.docs-page .dc-name { font-weight: 700; color: var(--dc-heading); line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.docs-page .dc-name-link { color: inherit; text-decoration: none; }
.docs-page .dc-name-link:hover { text-decoration: underline; text-decoration-color: var(--dc-gold); text-underline-offset: 3px; }
.docs-page .dc-sub { color: var(--dc-muted); font-size: .74rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.docs-page .dc-cell-strong { font-weight: 700; color: var(--dc-heading); }

.docs-page .dc-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
.docs-page .dc-badge.is-activo { color: color-mix(in srgb, var(--dc-success) 78%, #000); background: var(--dc-success-bg); border-color: color-mix(in srgb, var(--dc-success) 26%, #fff); }
.docs-page .dc-badge.is-archivado { color: color-mix(in srgb, var(--dc-warning) 82%, #000); background: var(--dc-warning-bg); border-color: color-mix(in srgb, var(--dc-warning) 28%, #fff); }
.docs-page .dc-badge.is-eliminado { color: color-mix(in srgb, var(--dc-danger) 82%, #000); background: var(--dc-danger-bg); border-color: color-mix(in srgb, var(--dc-danger) 26%, #fff); }
.docs-page .dc-badge.is-soft { color: var(--dc-muted); background: var(--dc-surface-warm); border-color: var(--dc-border); }

.docs-page .dc-actions { display: flex; justify-content: flex-end; gap: 7px; }
.docs-page .dc-action { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 10px; background: var(--dc-surface-warm); border: 1px solid var(--dc-border); color: var(--dc-muted); text-decoration: none; transition: transform .16s ease, background .16s ease, color .16s ease, border-color .16s ease; }
.docs-page .dc-action:hover { transform: translateY(-1px); }
.docs-page .dc-action-view { color: var(--dc-info); } .docs-page .dc-action-view:hover { background: var(--dc-info-bg); }
.docs-page .dc-action-edit { color: var(--dc-gold-ink); } .docs-page .dc-action-edit:hover { background: var(--dc-gold-soft); }
.docs-page .dc-action-dl { color: var(--dc-success); } .docs-page .dc-action-dl:hover { background: var(--dc-success-bg); }

.docs-page .dc-mobile { display: grid; gap: 10px; padding: 12px; }
.docs-page .dc-mobile-card { background: var(--dc-surface); border: 1px solid var(--dc-border); border-radius: 16px; padding: 12px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 26px -20px rgba(27,39,70,.25); transition: transform .16s ease, border-color .16s ease; }
.docs-page .dc-mobile-card:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--dc-gold) 38%, var(--dc-border)); }
.docs-page .dc-mobile-top { display: flex; align-items: center; gap: 11px; }
.docs-page .dc-mobile-top .min-w-0 { min-width: 0; flex: 1; }
.docs-page .dc-mobile-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; margin-top: 11px; }
.docs-page .dc-mini-label { font-size: .64rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--dc-muted); }
.docs-page .dc-mini-value { font-weight: 700; color: var(--dc-heading); font-size: .84rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.docs-page .dc-mobile-actions { display: flex; gap: 7px; margin-top: 11px; }
.docs-page .dc-card-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 36px; border-radius: 10px; background: var(--dc-surface-warm); border: 1px solid var(--dc-border); color: var(--dc-text); font-size: .76rem; font-weight: 700; text-decoration: none; transition: background .16s ease, color .16s ease, border-color .16s ease; }
.docs-page .dc-card-btn:hover { border-color: var(--dc-gold-line); color: var(--dc-gold-ink); background: var(--dc-gold-soft); }

.docs-page .dc-empty { text-align: center; padding: 44px 18px; background: var(--dc-ivory-2); border: 1px dashed var(--dc-border); border-radius: 16px; }
.docs-page .dc-empty-icon { width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 18px; display: grid; place-items: center; background: var(--dc-gold-soft); color: var(--dc-gold-ink); font-size: 1.3rem; }
.docs-page .dc-empty h2 { color: var(--dc-brand); font-size: 1.1rem; font-weight: 700; }
.docs-page .dc-empty p { color: var(--dc-muted); margin: 8px auto 0; max-width: 30rem; font-size: .9rem; }
.docs-page .dc-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--dc-gold-soft); border: 1px solid var(--dc-gold-line); border-radius: 16px; }
.docs-page .dc-notice i { color: var(--dc-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.docs-page .dc-notice strong { color: var(--dc-heading); display: block; margin-bottom: 2px; }
.docs-page .dc-notice p { color: var(--dc-muted); font-size: .88rem; margin: 0; }

.docs-page [data-dc-results-region] { transition: opacity .18s ease, filter .18s ease; }
.docs-page [data-dc-results-region].is-updating { opacity: .58; filter: saturate(.88); pointer-events: none; }

@media (min-width: 768px) {
    .docs-page .dc-desktop { display: block; }
    .docs-page .dc-mobile { display: none; }
}
@media (max-width: 767px) {
    .docs-page .dc-filter-form { grid-template-columns: 1fr; }
    .docs-page .dc-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .docs-page .dc-title { font-size: 1.9rem; }
}
</style>

<div class="docs-page p-4 sm:p-6">
    <div class="dc-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="dc-title-lockup">
                <div class="dc-hero-icon"><i class="fas fa-folder-open"></i></div>
                <div>
                    <p class="dc-kicker"><?= $esEntidad ? doc_safe($contextoEntidad['label'] ?? 'Entidad') : 'Archivos del hotel' ?></p>
                    <h1 class="dc-title"><?= $esEntidad ? doc_safe($contextoEntidad['label'] ?? 'Entidad') . ' #' . (int)($contextoEntidad['id'] ?? 0) : 'Documentos' ?></h1>
                    <p class="dc-subtitle">Guarda y organiza los documentos del hotel: contratos, comprobantes y facturas. Los archivos se guardan de forma privada y solo tu equipo los descarga.</p>
                </div>
            </div>
            <?php if ($tablaDisponible): ?>
                <div class="flex flex-wrap gap-2">
                    <?php if ($esEntidad): ?>
                        <a class="dc-btn dc-btn-muted" href="<?= url('documentos') ?>">
                            <i class="fas fa-arrow-left"></i>
                            Todos los documentos
                        </a>
                    <?php endif; ?>
                    <a class="dc-btn dc-btn-gold" href="<?= doc_safe($uploadUrl, '') ?>">
                        <i class="fas fa-upload"></i>
                        Subir documento
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="dc-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para empezar a guardar documentos.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="dc-summary">
                <div class="dc-summary-item">
                    <p class="dc-summary-label">Documentos</p>
                    <p class="dc-summary-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p>
                </div>
                <div class="dc-summary-item">
                    <p class="dc-summary-label">Activos</p>
                    <p class="dc-summary-value is-active"><?= number_format((int)($resumen['activos'] ?? 0)) ?></p>
                </div>
                <div class="dc-summary-item">
                    <p class="dc-summary-label">Archivados</p>
                    <p class="dc-summary-value"><?= number_format((int)($resumen['archivados'] ?? 0)) ?></p>
                </div>
                <div class="dc-summary-item">
                    <p class="dc-summary-label">Espacio usado</p>
                    <p class="dc-summary-value"><?= doc_bytes($resumen['bytes_total'] ?? 0) ?></p>
                </div>
            </section>

            <?php if (!$esEntidad): ?>
                <section class="dc-panel p-3 md:p-4">
                    <form method="GET" action="<?= url('documentos') ?>" class="dc-filter-form" data-dc-live-search-form data-auto-filter-form>
                        <div class="dc-search">
                            <i class="fas fa-search dc-search-icon" data-dc-search-icon></i>
                            <input class="dc-control" type="search" name="buscar" autocomplete="off" inputmode="search"
                                   value="<?= doc_safe($buscar, '') ?>"
                                   placeholder="Buscar por t&iacute;tulo, archivo o tipo"
                                   data-dc-live-search-input>
                        </div>
                        <select class="dc-control" name="estado" title="Filtrar por estado">
                            <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activos</option>
                            <option value="archivado" <?= $estado === 'archivado' ? 'selected' : '' ?>>Archivados</option>
                            <option value="eliminado" <?= $estado === 'eliminado' ? 'selected' : '' ?>>Eliminados</option>
                        </select>
                        <select class="dc-control" name="documento_tipo_id" title="Filtrar por tipo">
                            <option value="0">Todos los tipos</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <?php $tipoId = (int)($tipo['id'] ?? 0); ?>
                                <option value="<?= $tipoId ?>" <?= $tipoFiltro === $tipoId ? 'selected' : '' ?>>
                                    <?= doc_safe($tipo['nombre'] ?? null) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="dc-btn dc-btn-muted" type="submit" style="border-color:transparent;background:linear-gradient(135deg,var(--dc-brand),var(--dc-brand-2));color:#fff">
                            <i class="fas fa-filter"></i>
                            Filtrar
                        </button>
                        <a class="dc-btn dc-btn-muted dc-reset" href="<?= url('documentos') ?>" title="Limpiar filtros">
                            <i class="fas fa-times"></i>
                            Limpiar
                        </a>
                    </form>
                </section>
            <?php endif; ?>

            <div data-dc-results-region aria-live="polite" aria-busy="false">
                <?php if (empty($documentos)): ?>
                    <section class="dc-empty">
                        <div class="dc-empty-icon"><i class="fas fa-folder-open"></i></div>
                        <h2>A&uacute;n no hay documentos</h2>
                        <p>Sube el primero o cambia la b&uacute;squeda. Aceptamos PDF, JPG, PNG o WEBP.</p>
                        <a class="dc-btn dc-btn-gold mt-4" href="<?= doc_safe($uploadUrl, '') ?>" style="display:inline-flex">
                            <i class="fas fa-upload"></i>
                            Subir documento
                        </a>
                    </section>
                <?php else: ?>
                    <section class="dc-panel overflow-hidden">
                        <div class="dc-panel-head">
                            <div>
                                <div class="dc-panel-title">Lista de documentos</div>
                                <div class="dc-panel-sub">T&iacute;tulo, archivo y estado de cada documento.</div>
                            </div>
                            <span class="dc-count-pill">
                                <i class="fas fa-list"></i>
                                <?= number_format($visibles) ?> <?= $visibles === 1 ? 'documento' : 'documentos' ?>
                            </span>
                        </div>

                        <div class="dc-desktop">
                            <table class="dc-table">
                                <colgroup>
                                    <col style="width: 26%;">
                                    <col style="width: 15%;">
                                    <col style="width: 20%;">
                                    <col style="width: 11%;">
                                    <col style="width: 16%;">
                                    <col style="width: 12%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Documento</th>
                                        <th>Tipo</th>
                                        <th>Archivo</th>
                                        <th>Estado</th>
                                        <th>V&iacute;nculos</th>
                                        <th class="is-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documentos as $documento): ?>
                                        <?php
                                        $docId = (int)($documento['id'] ?? 0);
                                        $docUrl = url('documentos/' . $docId);
                                        $docEstado = (string)($documento['estado'] ?? '');
                                        [$estadoLabel, $estadoClass, $estadoIcon] = doc_estado_meta($docEstado);
                                        [$fileIcon, $fileClass] = doc_mime_meta($documento['mime_type'] ?? '');
                                        ?>
                                        <tr class="dc-row">
                                            <td>
                                                <div class="dc-doc-cell">
                                                    <div class="dc-fileicon <?= $fileClass ?>"><i class="fas <?= $fileIcon ?>"></i></div>
                                                    <div class="min-w-0">
                                                        <a class="dc-name dc-name-link" href="<?= $docUrl ?>"><?= doc_safe($documento['titulo'] ?? null, 'Documento #' . $docId) ?></a>
                                                        <div class="dc-sub"><?= doc_safe($documento['descripcion'] ?? null, 'Sin descripci&oacute;n') ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dc-cell-strong"><?= doc_safe($documento['tipo_nombre'] ?? null, 'Sin tipo') ?></div>
                                                <div class="dc-sub"><?= doc_safe($documento['tipo_clave'] ?? null, '') ?></div>
                                            </td>
                                            <td>
                                                <div class="dc-cell-strong" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= doc_safe($documento['nombre_original'] ?? null) ?></div>
                                                <div class="dc-sub"><?= doc_safe($documento['mime_type'] ?? null) ?> &middot; <?= doc_bytes($documento['size_bytes'] ?? 0) ?></div>
                                            </td>
                                            <td>
                                                <span class="dc-badge <?= $estadoClass ?>">
                                                    <i class="fas <?= $estadoIcon ?>"></i>
                                                    <?= $estadoLabel ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dc-cell-strong"><?= (int)($documento['entidades_count'] ?? 0) ?> v&iacute;nculo<?= (int)($documento['entidades_count'] ?? 0) === 1 ? '' : 's' ?></div>
                                                <div class="dc-sub"><?= doc_safe($documento['entidades_resumen'] ?? null, 'Sin v&iacute;nculos') ?></div>
                                            </td>
                                            <td class="is-end">
                                                <div class="dc-actions">
                                                    <a class="dc-action dc-action-view" href="<?= $docUrl ?>" title="Ver" aria-label="Ver documento"><i class="fas fa-eye"></i></a>
                                                    <?php if ($docEstado !== 'eliminado'): ?>
                                                        <a class="dc-action dc-action-edit" href="<?= url('documentos/' . $docId . '/editar') ?>" title="Editar" aria-label="Editar documento"><i class="fas fa-pen"></i></a>
                                                    <?php endif; ?>
                                                    <?php if ($docEstado === 'activo'): ?>
                                                        <a class="dc-action dc-action-dl" href="<?= url('documentos/' . $docId . '/descargar') ?>" title="Descargar" aria-label="Descargar documento"><i class="fas fa-download"></i></a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="dc-mobile">
                            <?php foreach ($documentos as $documento): ?>
                                <?php
                                $docId = (int)($documento['id'] ?? 0);
                                $docUrl = url('documentos/' . $docId);
                                $docEstado = (string)($documento['estado'] ?? '');
                                [$estadoLabel, $estadoClass, $estadoIcon] = doc_estado_meta($docEstado);
                                [$fileIcon, $fileClass] = doc_mime_meta($documento['mime_type'] ?? '');
                                ?>
                                <article class="dc-mobile-card">
                                    <div class="dc-mobile-top">
                                        <div class="dc-fileicon <?= $fileClass ?>"><i class="fas <?= $fileIcon ?>"></i></div>
                                        <div class="min-w-0">
                                            <a class="dc-name dc-name-link" href="<?= $docUrl ?>"><?= doc_safe($documento['titulo'] ?? null, 'Documento #' . $docId) ?></a>
                                            <div class="dc-sub"><?= doc_safe($documento['nombre_original'] ?? null) ?></div>
                                        </div>
                                        <span class="dc-badge <?= $estadoClass ?>">
                                            <i class="fas <?= $estadoIcon ?>"></i>
                                            <?= $estadoLabel ?>
                                        </span>
                                    </div>
                                    <div class="dc-mobile-meta">
                                        <div>
                                            <div class="dc-mini-label">Tipo</div>
                                            <div class="dc-mini-value"><?= doc_safe($documento['tipo_nombre'] ?? null, 'Sin tipo') ?></div>
                                        </div>
                                        <div>
                                            <div class="dc-mini-label">Tama&ntilde;o</div>
                                            <div class="dc-mini-value"><?= doc_bytes($documento['size_bytes'] ?? 0) ?></div>
                                        </div>
                                        <div>
                                            <div class="dc-mini-label">V&iacute;nculos</div>
                                            <div class="dc-mini-value"><?= (int)($documento['entidades_count'] ?? 0) ?></div>
                                        </div>
                                        <div>
                                            <div class="dc-mini-label">Fecha</div>
                                            <div class="dc-mini-value"><?= doc_safe($documento['created_at'] ?? null) ?></div>
                                        </div>
                                    </div>
                                    <div class="dc-mobile-actions">
                                        <a class="dc-card-btn" href="<?= $docUrl ?>"><i class="fas fa-eye"></i> Ver</a>
                                        <?php if ($docEstado !== 'eliminado'): ?>
                                            <a class="dc-card-btn" href="<?= url('documentos/' . $docId . '/editar') ?>"><i class="fas fa-pen"></i> Editar</a>
                                        <?php endif; ?>
                                        <?php if ($docEstado === 'activo'): ?>
                                            <a class="dc-card-btn" href="<?= url('documentos/' . $docId . '/descargar') ?>"><i class="fas fa-download"></i> Descargar</a>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(() => {
    const form = document.querySelector('[data-dc-live-search-form]');
    const input = document.querySelector('[data-dc-live-search-input]');
    const searchIcon = document.querySelector('[data-dc-search-icon]');
    if (!form || !input) return;

    let liveSearchTimer = null;
    let isComposing = false;
    let lastQuery = input.value.trim();
    let activeRequest = null;
    const parser = new DOMParser();
    const delay = 280;

    const getResultsRegion = () => document.querySelector('[data-dc-results-region]');

    const setSearching = (isSearching) => {
        getResultsRegion()?.classList.toggle('is-updating', isSearching);
        getResultsRegion()?.setAttribute('aria-busy', isSearching ? 'true' : 'false');
        if (searchIcon) {
            searchIcon.classList.toggle('fa-search', !isSearching);
            searchIcon.classList.toggle('fa-circle-notch', isSearching);
            searchIcon.classList.toggle('fa-spin', isSearching);
        }
    };

    const buildSearchUrl = (targetUrl = null) => {
        const url = targetUrl ? new URL(targetUrl, window.location.origin) : new URL(form.action, window.location.origin);
        const data = new FormData(form);
        for (const [key, value] of data.entries()) {
            const normalized = String(value || '').trim();
            if (normalized && normalized !== '0') {
                url.searchParams.set(key, normalized);
            } else {
                url.searchParams.delete(key);
            }
        }
        return url;
    };

    const updateFromDocument = (doc) => {
        const incoming = doc.querySelector('[data-dc-results-region]');
        const current = getResultsRegion();
        if (incoming && current) {
            current.replaceWith(incoming);
        }
    };

    const fetchResults = async (url, { pushState = true } = {}) => {
        if (activeRequest) activeRequest.abort();
        const controller = new AbortController();
        activeRequest = controller;
        setSearching(true);
        try {
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('No se pudo cargar la busqueda');
            const html = await response.text();
            const doc = parser.parseFromString(html, 'text/html');
            updateFromDocument(doc);
            if (pushState) {
                window.history.replaceState({}, '', url.pathname + url.search);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error en busqueda de documentos:', error);
                HTMLFormElement.prototype.submit.call(form);
            }
        } finally {
            if (activeRequest === controller) {
                activeRequest = null;
                setSearching(false);
            }
        }
    };

    const submitLiveSearch = () => {
        const current = input.value.trim();
        if (current === lastQuery) return;
        lastQuery = current;
        fetchResults(buildSearchUrl());
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        lastQuery = input.value.trim();
        fetchResults(buildSearchUrl());
    });

    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => {
            lastQuery = input.value.trim();
            fetchResults(buildSearchUrl());
        });
    });

    form.querySelector('.dc-reset')?.addEventListener('click', (event) => {
        event.preventDefault();
        input.value = '';
        form.querySelectorAll('select').forEach((select) => { select.selectedIndex = 0; });
        lastQuery = '';
        fetchResults(new URL(event.currentTarget.href, window.location.origin));
        input.focus();
    });

    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        input.value = params.get('buscar') || '';
        const estadoSel = form.querySelector('[name="estado"]');
        if (estadoSel) estadoSel.value = params.get('estado') || 'todos';
        const tipoSel = form.querySelector('[name="documento_tipo_id"]');
        if (tipoSel) tipoSel.value = params.get('documento_tipo_id') || '0';
        lastQuery = input.value.trim();
        fetchResults(new URL(window.location.href), { pushState: false });
    });

    input.addEventListener('compositionstart', () => { isComposing = true; });
    input.addEventListener('compositionend', () => {
        isComposing = false;
        window.clearTimeout(liveSearchTimer);
        liveSearchTimer = window.setTimeout(submitLiveSearch, delay);
    });
    input.addEventListener('input', () => {
        if (isComposing) return;
        window.clearTimeout(liveSearchTimer);
        liveSearchTimer = window.setTimeout(submitLiveSearch, delay);
    });
})();
</script>
