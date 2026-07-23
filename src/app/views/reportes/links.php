<?php
$links = $links ?? [];
$resumen = $resumen ?? [];
$tipos = $tipos ?? [];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;
$emailEnvioActivo = !empty($emailEnvioActivo);
$emailDestinatariosConfigurados = !empty($emailDestinatariosConfigurados);

if (!function_exists('rep_link_safe')) {
    function rep_link_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rep_link_date')) {
    function rep_link_date($value, $format = 'd/m/Y H:i') {
        if (!$value) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('rep_link_bytes')) {
    function rep_link_bytes($bytes) {
        $bytes = (float)($bytes ?? 0);
        if ($bytes <= 0) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int)floor(log($bytes, 1024)), count($units) - 1);
        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1) . ' ' . $units[$power];
    }
}

$estadoFiltro = (string)($filtros['estado'] ?? '');
$tipoFiltro = (string)($filtros['tipo_reporte'] ?? '');
?>

<style>
.report-link-view {
    --rl-primary: var(--brand-primary, #1B2746);
    --rl-secondary: var(--brand-secondary, #0F172A);
    --rl-accent: var(--brand-accent, #BD9441);
    --rl-bg: color-mix(in srgb, var(--rl-primary) 4%, #F8FAFC);
    --rl-surface: #FFFFFF;
    --rl-line: color-mix(in srgb, var(--rl-primary) 14%, #E5E7EB);
    --rl-muted: #667085;
    min-height: 100vh;
    background:
        linear-gradient(120deg, color-mix(in srgb, var(--rl-primary) 4%, transparent) 0 1px, transparent 1px 24px),
        radial-gradient(circle at 90% 2%, color-mix(in srgb, var(--rl-accent) 24%, transparent), transparent 30rem),
        linear-gradient(180deg, var(--rl-bg), #FBFCFE 62%);
    color: #172033;
}
.report-link-shell {
    width: min(1380px, calc(100% - 28px));
    margin: 0 auto;
    padding: 28px 0 46px;
}
.report-link-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 18px;
    align-items: stretch;
    margin-bottom: 18px;
}
.report-link-title {
    padding: 26px;
    border-radius: 18px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--rl-primary) 94%, #111827), var(--rl-secondary));
    color: #fff;
    border: 1px solid color-mix(in srgb, var(--rl-accent) 24%, transparent);
    box-shadow: 0 24px 60px -42px rgba(15, 23, 42, .74);
}
.report-link-title p {
    max-width: 760px;
    color: rgba(255,255,255,.72);
    margin: 8px 0 0;
}
.report-link-actions {
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    gap: 10px;
}
.report-link-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 14px;
    border-radius: 10px;
    font-size: .86rem;
    font-weight: 800;
    border: 1px solid var(--rl-line);
    color: var(--rl-primary);
    background: rgba(255,255,255,.86);
    text-decoration: none;
}
.report-link-btn:hover {
    color: var(--rl-primary);
    background: #fff;
}
.report-link-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.report-link-metric,
.report-link-panel {
    background: rgba(255,255,255,.92);
    border: 1px solid var(--rl-line);
    border-radius: 14px;
    box-shadow: 0 18px 48px -42px rgba(15, 23, 42, .42);
}
.report-link-metric {
    padding: 16px;
}
.report-link-metric span {
    display: block;
    color: var(--rl-muted);
    font-size: .74rem;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.report-link-metric strong {
    display: block;
    margin-top: 5px;
    font-size: 1.55rem;
    color: var(--rl-primary);
}
.report-link-panel {
    overflow: hidden;
}
.report-link-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: end;
    padding: 16px;
    border-bottom: 1px solid var(--rl-line);
    background: color-mix(in srgb, var(--rl-primary) 3%, #fff);
}
.report-link-field {
    display: grid;
    gap: 6px;
}
.report-link-field label {
    font-size: .74rem;
    font-weight: 900;
    color: var(--rl-muted);
    text-transform: uppercase;
}
.report-link-field select {
    min-width: 180px;
    height: 40px;
    padding: 0 10px;
    border-radius: 10px;
    border: 1px solid var(--rl-line);
    background: #fff;
}
.report-link-table-wrap {
    overflow-x: auto;
}
.report-link-table {
    width: 100%;
    min-width: 920px;
    border-collapse: collapse;
}
.report-link-table th,
.report-link-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #EEF0F4;
    text-align: left;
    vertical-align: middle;
}
.report-link-table th {
    font-size: .72rem;
    color: var(--rl-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
    background: #FBFCFE;
}
.report-link-name {
    font-weight: 900;
    color: #172033;
}
.report-link-sub {
    color: var(--rl-muted);
    font-size: .8rem;
    margin-top: 3px;
}
.report-link-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 9px;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 900;
}
.report-link-badge.activo { background:#DCFCE7; color:#166534; }
.report-link-badge.expirado { background:#FEF3C7; color:#92400E; }
.report-link-badge.revocado { background:#FEE2E2; color:#991B1B; }
.report-link-row-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
}
.report-link-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 9px;
    border: 1px solid var(--rl-line);
    background: #fff;
    color: var(--rl-primary);
}
.report-link-icon-btn.danger {
    color: #B42318;
}
.report-link-icon-btn.is-confirming {
    background: #FFF8E8;
    border-color: #F3D08A;
    color: #8A5B12;
}
.report-link-icon-btn.danger.is-confirming {
    background: #FEF2F2;
    border-color: #FECACA;
    color: #B42318;
}
.report-link-icon-btn:disabled {
    opacity: .42;
    cursor: not-allowed;
    background: #F3F4F6;
}
.report-link-toast {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 15000;
    max-width: min(390px, calc(100vw - 32px));
    border: 1px solid #F3D08A;
    border-radius: 14px;
    background: #FFF8E8;
    color: #8A5B12;
    padding: 12px 14px;
    box-shadow: 0 18px 42px rgba(24, 32, 48, .18);
    font-size: .82rem;
    font-weight: 850;
    line-height: 1.42;
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
    transition: opacity .18s ease, transform .18s ease;
}
.report-link-toast.is-visible {
    opacity: 1;
    transform: translateY(0);
}
.report-link-empty {
    padding: 38px 20px;
    text-align: center;
    color: var(--rl-muted);
}
.report-link-empty i {
    display: block;
    color: color-mix(in srgb, var(--rl-primary) 62%, #98A2B3);
    font-size: 2rem;
    margin-bottom: 10px;
}

@media (max-width: 920px) {
    .report-link-hero {
        grid-template-columns: 1fr;
    }
    .report-link-actions {
        justify-content: flex-start;
    }
    .report-link-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .report-link-metrics {
        grid-template-columns: 1fr;
    }
    .report-link-title {
        padding: 20px;
    }
}
</style>

<div class="report-link-view">
    <div class="report-link-shell">
        <div class="report-link-hero">
            <section class="report-link-title">
                <div class="text-sm font-black uppercase tracking-wide text-white/70 mb-2">
                    <i class="fas fa-link mr-2"></i> Reportes compartibles
                </div>
                <h1 class="text-2xl font-black">Historial y links seguros</h1>
                <p>Control interno de PDFs guardados para compartir por link con expiracion, revocacion, correo y conteo de accesos.</p>
            </section>
            <div class="report-link-actions">
                <?php $back_arrow_href = back_url('reportes'); $back_arrow_class = 'ms-back--inline ms-back--glass'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('reportes') ?>" class="report-link-btn ms-back-legacy">
                    <i class="fas fa-arrow-left"></i> Reportes
                </a>
            </div>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="report-link-panel">
                <div class="report-link-empty">
                    <i class="fas fa-database"></i>
                    <strong>Esta función aún no está activada en tu hotel.</strong>
                    <p class="mt-2">Contacta a soporte de Medisoft para activarla.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="report-link-metrics">
                <div class="report-link-metric">
                    <span>Total</span>
                    <strong><?= (int)($resumen['total'] ?? 0) ?></strong>
                </div>
                <div class="report-link-metric">
                    <span>Activos</span>
                    <strong><?= (int)($resumen['activos'] ?? 0) ?></strong>
                </div>
                <div class="report-link-metric">
                    <span>Expirados</span>
                    <strong><?= (int)($resumen['expirados'] ?? 0) ?></strong>
                </div>
                <div class="report-link-metric">
                    <span>Accesos</span>
                    <strong><?= (int)($resumen['accesos'] ?? 0) ?></strong>
                </div>
            </div>

            <section class="report-link-panel">
                <form method="GET" action="<?= url('reportes/links') ?>" class="report-link-filter" data-auto-filter-form>
                    <div class="report-link-field">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="">Todos</option>
                            <option value="activo" <?= $estadoFiltro === 'activo' ? 'selected' : '' ?>>Activos</option>
                            <option value="expirado" <?= $estadoFiltro === 'expirado' ? 'selected' : '' ?>>Expirados</option>
                            <option value="revocado" <?= $estadoFiltro === 'revocado' ? 'selected' : '' ?>>Revocados</option>
                        </select>
                    </div>
                    <div class="report-link-field">
                        <label for="tipo_reporte">Tipo</label>
                        <select id="tipo_reporte" name="tipo_reporte">
                            <option value="">Todos</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <?php $tipoValor = (string)($tipo['tipo_reporte'] ?? ''); ?>
                                <option value="<?= rep_link_safe($tipoValor) ?>" <?= $tipoFiltro === $tipoValor ? 'selected' : '' ?>>
                                    <?= rep_link_safe($tipoValor) ?> (<?= (int)($tipo['total'] ?? 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="report-link-btn">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="<?= url('reportes/links') ?>" class="report-link-btn">
                        <i class="fas fa-rotate-left"></i> Limpiar
                    </a>
                </form>

                <div class="report-link-table-wrap">
                    <table class="report-link-table">
                        <thead>
                            <tr>
                                <th>Reporte</th>
                                <th>Estado</th>
                                <th>Expira</th>
                                <th>Accesos</th>
                                <th>Archivo</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($links)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="report-link-empty">
                                            <i class="fas fa-file-pdf"></i>
                                            <strong>No hay links de reportes todavia.</strong>
                                            <p class="mt-2">Aqui apareceran los cortes de caja y reportes PDF cuando se generen con links seguros.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($links as $link): ?>
                                <?php
                                    $estado = (string)($link['estado_calculado'] ?? $link['estado'] ?? 'activo');
                                    $id = (int)($link['id'] ?? 0);
                                    $puedeEnviarCorreo = $estado !== 'revocado' && $emailEnvioActivo && $emailDestinatariosConfigurados;
                                    $correoTitle = $puedeEnviarCorreo
                                        ? 'Enviar link seguro por correo'
                                        : 'Configura y activa correo en Configuracion > Reportes seguros';
                                    $correoEstado = (string)($link['ultimo_correo_estado'] ?? '');
                                ?>
                                <tr>
                                    <td>
                                        <div class="report-link-name"><?= rep_link_safe($link['titulo'] ?? 'Reporte PDF') ?></div>
                                        <div class="report-link-sub">
                                            <?= rep_link_safe($link['tipo_reporte'] ?? 'general') ?>
                                            &middot; creado <?= rep_link_date($link['created_at'] ?? null) ?>
                                            &middot; token ...<?= rep_link_safe($link['token_hint'] ?? '') ?>
                                        </div>
                                        <div class="report-link-sub">
                                            Correo:
                                            <?php if ($correoEstado === 'enviado'): ?>
                                                enviado <?= rep_link_date($link['ultimo_correo_en'] ?? null) ?>
                                            <?php elseif ($correoEstado === 'fallido'): ?>
                                                fallido <?= rep_link_date($link['ultimo_correo_en'] ?? null) ?>
                                                <?php if (!empty($link['ultimo_correo_error'])): ?>
                                                    &middot; <?= rep_link_safe($link['ultimo_correo_error']) ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                sin envio
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="report-link-badge <?= rep_link_safe($estado, 'activo') ?>">
                                            <i class="fas fa-circle text-[0.45rem]"></i> <?= rep_link_safe(ucfirst($estado)) ?>
                                        </span>
                                    </td>
                                    <td><?= rep_link_date($link['expira_en'] ?? null) ?></td>
                                    <td>
                                        <strong><?= (int)($link['accesos'] ?? 0) ?></strong>
                                        <div class="report-link-sub">ultimo: <?= rep_link_date($link['ultimo_acceso_en'] ?? null) ?></div>
                                    </td>
                                    <td>
                                        <div class="report-link-name"><?= rep_link_safe($link['archivo_nombre'] ?? 'reporte.pdf') ?></div>
                                        <div class="report-link-sub"><?= rep_link_bytes($link['tamano_bytes'] ?? 0) ?></div>
                                    </td>
                                    <td>
                                        <div class="report-link-row-actions">
                                            <a class="report-link-icon-btn" href="<?= url('reportes/links/' . $id . '/descargar') ?>" title="Abrir PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            <?php if ($estado !== 'revocado'): ?>
                                                <form method="POST" action="<?= url('reportes/links/' . $id . '/enviar-correo') ?>" data-ms-confirm data-ms-type="info" data-ms-icon="check" data-ms-title="¿Enviar reporte por correo?" data-ms-msg="Se enviará este reporte por correo con un link seguro nuevo." data-ms-ok="Enviar correo">
                                                    <?= csrf_field() ?>
                                                    <button class="report-link-icon-btn" type="submit" title="<?= rep_link_safe($correoTitle) ?>" <?= $puedeEnviarCorreo ? '' : 'disabled' ?>>
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($estado === 'activo'): ?>
                                                <form method="POST" action="<?= url('reportes/links/' . $id . '/revocar') ?>" data-ms-confirm data-ms-type="error" data-ms-icon="x" data-ms-title="¿Revocar link?" data-ms-msg="Revocar este link impedirá nuevos accesos con esta URL." data-ms-ok="Sí, revocar">
                                                    <?= csrf_field() ?>
                                                    <button class="report-link-icon-btn danger" type="submit" title="Revocar link">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<script>
/* Las confirmaciones (enviar correo / revocar) usan el modal global msConfirm. */
</script>
