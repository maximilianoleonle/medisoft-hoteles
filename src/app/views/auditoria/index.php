<?php
/**
 * Bitacora de auditoria (bloque auditoria). Solo lectura.
 */
$eventos = $eventos ?? [];
$usuarios = $usuarios ?? [];
$modulos = $modulos ?? [];
$filtros = $filtros ?? ['usuario' => 0, 'modulo' => '', 'desde' => '', 'hasta' => ''];

$auSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

// Traduccion amable de acciones tecnicas comunes.
$auAccion = static function ($accion) {
    $map = [
        'cancelar' => 'Cancelación', 'eliminar' => 'Eliminación', 'guardar' => 'Guardado',
        'crear' => 'Creación', 'actualizar' => 'Actualización', 'revertir' => 'Reversión',
        'checkin' => 'Check-in', 'checkout' => 'Check-out', 'conciliar' => 'Conciliación',
        'config' => 'Configuración', 'guardarConfiguracion' => 'Configuración',
    ];
    return $map[$accion] ?? ucfirst($accion);
};

$auEsSensible = static function ($accion) {
    return (bool) preg_match('/cancelar|eliminar|revertir|reembols|desactivar/i', (string) $accion);
};

$auTotalEventos = count($eventos);
$auUsuariosVisibles = count($usuarios);
$auSensiblesVisibles = 0;
foreach ($eventos as $ev) {
    if ($auEsSensible($ev['accion'] ?? '')) {
        $auSensiblesVisibles++;
    }
}

$auFiltrosActivos = 0;
$auFiltrosActivos += ((int)($filtros['usuario'] ?? 0) > 0) ? 1 : 0;
$auFiltrosActivos += trim((string)($filtros['modulo'] ?? '')) !== '' ? 1 : 0;
$auFiltrosActivos += trim((string)($filtros['desde'] ?? '')) !== '' ? 1 : 0;
$auFiltrosActivos += trim((string)($filtros['hasta'] ?? '')) !== '' ? 1 : 0;
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.audit-page {
    --au-brand: var(--brand-primary, #1B2746);
    --au-brand-2: var(--brand-secondary, #0F172A);
    --au-gold: var(--brand-accent, #BD9441);
    --au-gold-soft: color-mix(in srgb, var(--au-gold) 15%, #FFFFFF);
    --au-gold-line: color-mix(in srgb, var(--au-gold) 42%, #E4D4B0);
    --au-gold-ink: color-mix(in srgb, var(--au-gold) 58%, var(--au-brand));
    --au-ivory: #F5F5F7;
    --au-ivory-2: #FAFAFC;
    --au-surface: #FFFFFF;
    --au-surface-warm: #F5F5F7;
    --au-border: color-mix(in srgb, var(--au-brand) 6%, #E9E1D6);
    --au-ring: color-mix(in srgb, var(--au-gold) 32%, transparent);
    --au-text: color-mix(in srgb, var(--au-brand) 46%, #707B8C);
    --au-muted: #8791A2;
    --au-heading: color-mix(in srgb, var(--au-brand) 66%, #566172);
    --au-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --au-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --au-danger: #B4392B;
    --au-danger-bg: #F8EAE5;
    --au-info: #2F77E0;
    --au-info-bg: #E6EFFC;
    min-height: 100%;
    color: var(--au-text);
    font-family: var(--au-sans);
    
}

.audit-page .au-shell {
    display: grid;
    gap: 14px;
    width: 100%;
    max-width: 1120px;
    margin: 0 auto;
    padding: 18px 16px 42px;
    box-sizing: border-box;
}

.audit-page .au-hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 12px;
    padding: 2px 0 6px;
}

.audit-page .au-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    max-width: min(100%, 780px);
    min-width: 0;
}

.audit-page .au-title-lockup > div:last-child { min-width: 0; }

.audit-page .au-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--au-gold), var(--au-brand) 54%, color-mix(in srgb, var(--au-brand) 68%, var(--au-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--au-brand) 72%, transparent);
}

.audit-page .au-kicker {
    margin: 0 0 2px;
    color: var(--au-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}

.audit-page .au-title {
    margin: 0;
    color: var(--au-heading);
    font-family: var(--au-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650;
    line-height: .98;
    overflow-wrap: anywhere;
}

.audit-page .au-subtitle {
    max-width: 48rem;
    margin: 9px 0 0;
    color: var(--au-muted);
    font-size: .94rem;
    font-weight: 500;
    line-height: 1.5;
}

.audit-page .au-hero-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
}

.audit-page .au-readonly,
.audit-page .au-count-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .36rem .66rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--au-gold) 10%, #FFFFFF);
    color: var(--au-gold-ink);
    border: 1px solid color-mix(in srgb, var(--au-gold) 28%, #ECE1D1);
    font-size: .72rem;
    font-weight: 650;
    white-space: nowrap;
}

.audit-page .au-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}

.audit-page .au-summary-item {
    background: rgba(255,255,255,.82);
    border: 1px solid var(--au-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}

.audit-page .au-summary-label {
    color: var(--au-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.audit-page .au-summary-value {
    margin-top: 2px;
    color: var(--au-heading);
    font-family: var(--au-serif);
    font-size: 1.7rem;
    font-weight: 650;
    line-height: 1.1;
}

.audit-page .au-panel {
    background: rgba(255,255,255,.86);
    border: 1px solid var(--au-border);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
}

.audit-page .au-filter-form {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) minmax(150px, .8fr) 150px 150px auto;
    gap: 10px;
    align-items: end;
    padding: 14px;
}

.audit-page .au-field label {
    display: block;
    margin: 0 0 5px;
    color: var(--au-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.audit-page .au-field input,
.audit-page .au-field select {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--au-border);
    border-radius: 11px;
    background: var(--au-surface-warm);
    color: var(--au-text);
    padding: 0 12px;
    font-size: .88rem;
    font-weight: 560;
    transition: border-color .16s ease, box-shadow .16s ease;
}

.audit-page .au-field input:focus,
.audit-page .au-field select:focus {
    border-color: var(--au-gold);
    box-shadow: 0 0 0 3px var(--au-ring);
    outline: none;
}

.audit-page .au-filter-actions {
    display: flex;
    gap: 8px;
    align-items: end;
}

.audit-page .au-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    min-height: 44px;
    padding: 0 16px;
    border: 1px solid transparent;
    border-radius: 11px;
    cursor: pointer;
    font-size: .88rem;
    font-weight: 650;
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}

.audit-page .au-btn:hover { transform: translateY(-1px); }
.audit-page .au-btn:active { transform: translateY(0) scale(.98); }
.audit-page .au-btn:focus-visible { outline: 3px solid var(--au-ring); outline-offset: 2px; }

.audit-page .au-btn-gold {
    background: linear-gradient(135deg, color-mix(in srgb, var(--au-gold) 86%, #fff), color-mix(in srgb, var(--au-gold) 72%, var(--au-brand)));
    color: #fff;
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--au-gold) 42%, transparent);
}

.audit-page .au-btn-muted {
    background: var(--au-surface);
    border-color: var(--au-border);
    color: var(--au-muted);
}

.audit-page .au-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 16px;
    border-bottom: 1px solid var(--au-border);
}

.audit-page .au-panel-title {
    margin: 0;
    color: var(--au-heading);
    font-size: .85rem;
    font-weight: 650;
}

.audit-page .au-panel-sub {
    margin: 2px 0 0;
    color: var(--au-muted);
    font-size: .75rem;
}

.audit-page .au-table-wrap {
    overflow-x: auto;
    border-radius: 0 0 16px 16px;
}

.audit-page .au-table {
    width: 100%;
    min-width: 860px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: .85rem;
}

.audit-page .au-table thead {
    background: var(--au-surface-warm);
    border-bottom: 1px solid var(--au-border);
}

.audit-page .au-table th {
    padding: 12px 16px;
    color: var(--au-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .07em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.audit-page .au-table td {
    padding: 13px 16px;
    border-bottom: 1px solid var(--au-border);
    vertical-align: middle;
}

.audit-page .au-table tr:last-child td { border-bottom: 0; }
.audit-page .au-table tbody tr { transition: background .16s ease, box-shadow .16s ease; }
.audit-page .au-table tbody tr:hover { background: rgba(251,248,242,.72); box-shadow: 0 10px 24px -25px rgba(27,39,70,.32); }

.audit-page .au-date { white-space: nowrap; }
.audit-page .au-date strong,
.audit-page .au-user strong,
.audit-page .au-module strong {
    color: var(--au-heading);
    font-weight: 650;
}

.audit-page .au-meta {
    margin-top: 2px;
    color: var(--au-muted);
    font-size: .74rem;
}

.audit-page .au-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--au-info) 22%, #FFFFFF);
    background: var(--au-info-bg);
    color: color-mix(in srgb, var(--au-info) 70%, var(--au-text));
    font-size: .74rem;
    font-weight: 650;
    white-space: nowrap;
}

.audit-page .au-pill.rojo {
    border-color: color-mix(in srgb, var(--au-danger) 24%, #fff);
    background: var(--au-danger-bg);
    color: color-mix(in srgb, var(--au-danger) 72%, var(--au-text));
}

.audit-page .au-ruta {
    color: var(--au-muted);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;
    font-size: .76rem;
    line-height: 1.45;
    word-break: break-word;
}

.audit-page .au-empty {
    padding: 44px 18px;
    text-align: center;
    background: var(--au-ivory-2);
    border: 1px dashed var(--au-border);
    border-radius: 16px;
    margin: 14px;
}

.audit-page .au-empty-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 14px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: var(--au-gold-soft);
    color: var(--au-gold-ink);
    font-size: 1.3rem;
}

.audit-page .au-empty h2 {
    margin: 0;
    color: var(--au-heading);
    font-size: 1.1rem;
    font-weight: 650;
}

.audit-page .au-empty p {
    max-width: 31rem;
    margin: 8px auto 0;
    color: var(--au-muted);
    font-size: .9rem;
    line-height: 1.5;
}

.audit-page .au-footnote {
    margin: 0;
    color: var(--au-muted);
    font-size: .76rem;
    line-height: 1.5;
}

@media (min-width: 1024px) {
    .audit-page .au-hero-section {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 16px 28px;
        padding-bottom: 10px;
    }

    .audit-page .au-hero-actions {
        justify-content: flex-end;
        justify-self: end;
    }
}

@media (max-width: 940px) {
    .audit-page .au-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .audit-page .au-filter-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .audit-page .au-filter-actions { grid-column: 1 / -1; }
}

@media (max-width: 640px) {
    .audit-page .au-shell { padding: 16px 12px 34px; }
    .audit-page .au-title-lockup { grid-template-columns: 42px minmax(0, 1fr); column-gap: 12px; }
    .audit-page .au-hero-icon { width: 42px; height: 42px; border-radius: 14px; font-size: 1rem; }
    .audit-page .au-title { font-size: clamp(1.85rem, 12vw, 2.35rem); }
    .audit-page .au-summary,
    .audit-page .au-filter-form { grid-template-columns: 1fr; }
    .audit-page .au-filter-actions { flex-direction: column; align-items: stretch; }
    .audit-page .au-btn { width: 100%; }
    .audit-page .au-panel-head { align-items: flex-start; flex-direction: column; }
}

@media (prefers-reduced-motion: reduce) {
    .audit-page *,
    .audit-page *::before,
    .audit-page *::after {
        transition: none !important;
        scroll-behavior: auto !important;
    }
}
</style>

<div class="audit-page">
    <div class="au-shell">
        <section class="au-hero-section" aria-labelledby="au-title">
            <div class="au-title-lockup">
                <div class="au-hero-icon" aria-hidden="true"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <p class="au-kicker">Bit&aacute;cora de auditor&iacute;a</p>
                    <h1 class="au-title" id="au-title">Historial de actividad</h1>
                    <p class="au-subtitle">Qui&eacute;n hizo qu&eacute; y cu&aacute;ndo: cada acci&oacute;n importante queda registrada con usuario, m&oacute;dulo, fecha y ruta. El registro es autom&aacute;tico y solo lectura.</p>
                </div>
            </div>
            <div class="au-hero-actions">
                <span class="au-readonly"><i class="fas fa-lock" aria-hidden="true"></i> Solo lectura</span>
            </div>
        </section>

        <section class="au-summary" aria-label="Resumen de auditoria">
            <div class="au-summary-item">
                <div class="au-summary-label">Eventos visibles</div>
                <div class="au-summary-value"><?= number_format($auTotalEventos) ?></div>
            </div>
            <div class="au-summary-item">
                <div class="au-summary-label">Acciones sensibles</div>
                <div class="au-summary-value"><?= number_format($auSensiblesVisibles) ?></div>
            </div>
            <div class="au-summary-item">
                <div class="au-summary-label">Usuarios</div>
                <div class="au-summary-value"><?= number_format($auUsuariosVisibles) ?></div>
            </div>
            <div class="au-summary-item">
                <div class="au-summary-label">Filtros activos</div>
                <div class="au-summary-value"><?= number_format($auFiltrosActivos) ?></div>
            </div>
        </section>

        <section class="au-panel" aria-label="Filtros de auditoria">
            <form class="au-filter-form" method="GET" action="<?= url('auditoria') ?>">
                <div class="au-field">
                    <label for="au-usuario">Usuario</label>
                    <select id="au-usuario" name="usuario">
                        <option value="0">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= (int) $u['usuario_id'] ?>" <?= (int) $filtros['usuario'] === (int) $u['usuario_id'] ? 'selected' : '' ?>>
                                <?= $auSafe($u['usuario_nombre'] ?: ('Usuario #' . (int) $u['usuario_id'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="au-field">
                    <label for="au-modulo">M&oacute;dulo</label>
                    <select id="au-modulo" name="modulo">
                        <option value="">Todos</option>
                        <?php foreach ($modulos as $m): ?>
                            <option value="<?= $auSafe($m) ?>" <?= $filtros['modulo'] === $m ? 'selected' : '' ?>><?= $auSafe($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="au-field">
                    <label for="au-desde">Desde</label>
                    <input type="date" id="au-desde" name="desde" value="<?= $auSafe($filtros['desde']) ?>">
                </div>
                <div class="au-field">
                    <label for="au-hasta">Hasta</label>
                    <input type="date" id="au-hasta" name="hasta" value="<?= $auSafe($filtros['hasta']) ?>">
                </div>
                <div class="au-filter-actions">
                    <button type="submit" class="au-btn au-btn-gold"><i class="fas fa-filter" aria-hidden="true"></i> Filtrar</button>
                    <a href="<?= url('auditoria') ?>" class="au-btn au-btn-muted"><i class="fas fa-undo" aria-hidden="true"></i> Limpiar</a>
                </div>
            </form>
        </section>

        <section class="au-panel" aria-labelledby="au-registro-title">
            <div class="au-panel-head">
                <div>
                    <h2 class="au-panel-title" id="au-registro-title">Registro de actividad</h2>
                    <p class="au-panel-sub">Se muestran los eventos mas recientes segun los filtros aplicados.</p>
                </div>
                <span class="au-count-pill"><i class="fas fa-history" aria-hidden="true"></i> <?= number_format($auTotalEventos) ?> visible(s)</span>
            </div>

            <?php if (empty($eventos)): ?>
                <div class="au-empty">
                    <div class="au-empty-icon" aria-hidden="true"><i class="fas fa-search"></i></div>
                    <h2>Sin eventos con estos filtros</h2>
                    <p>La bit&aacute;cora registra las acciones a partir de que el bloque se activa. Prueba limpiando filtros o ampliando el rango de fechas.</p>
                </div>
            <?php else: ?>
                <div class="au-table-wrap">
                    <table class="au-table">
                        <colgroup>
                            <col style="width: 15%;">
                            <col style="width: 22%;">
                            <col style="width: 16%;">
                            <col style="width: 17%;">
                            <col style="width: 30%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Cu&aacute;ndo</th>
                                <th>Usuario</th>
                                <th>Acci&oacute;n</th>
                                <th>M&oacute;dulo</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventos as $ev): ?>
                                <tr>
                                    <td class="au-date">
                                        <strong><?= $auSafe(date('d/m/Y', strtotime((string) $ev['created_at']))) ?></strong>
                                        <div class="au-meta"><?= $auSafe(date('H:i:s', strtotime((string) $ev['created_at']))) ?></div>
                                    </td>
                                    <td class="au-user">
                                        <strong><?= $auSafe($ev['usuario_nombre'] ?: ('Usuario #' . (int) $ev['usuario_id'])) ?></strong>
                                        <?php if (!empty($ev['ip'])): ?>
                                            <div class="au-meta">IP <?= $auSafe($ev['ip']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="au-pill <?= $auEsSensible($ev['accion']) ? 'rojo' : '' ?>">
                                            <i class="fas <?= $auEsSensible($ev['accion']) ? 'fa-exclamation-triangle' : 'fa-circle' ?>" aria-hidden="true"></i>
                                            <?= $auSafe($auAccion((string) $ev['accion'])) ?>
                                        </span>
                                    </td>
                                    <td class="au-module">
                                        <strong><?= $auSafe($ev['modulo']) ?></strong>
                                        <?php if ($ev['entidad_id'] !== null): ?>
                                            <div class="au-meta">#<?= (int) $ev['entidad_id'] ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="au-ruta"><?= $auSafe($ev['ruta']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <p class="au-footnote">Se muestran los 300 eventos mas recientes del filtro. Las acciones sensibles, como cancelaciones, eliminaciones y reversiones, se resaltan en rojo.</p>
    </div>
</div>
