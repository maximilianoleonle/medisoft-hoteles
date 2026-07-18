<?php
/**
 * Ciclos de lavado (lotes) del modulo Lavanderia: listado con filtros por
 * estado/tipo y acceso al detalle para recibir/cancelar/registrar gasto.
 */

if (!function_exists('lvx_safe')) {
    function lvx_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$lotes = $lotes ?? [];
$filtros = $filtros ?? ['estado' => 'todos', 'tipo' => 'todos'];
$resumenLotes = $resumen_lotes ?? ['en_proceso' => 0, 'gastos_pendientes' => 0];
$puedeOperar = !empty($puede_operar);

$estadoMeta = [
    'en_proceso' => ['label' => 'En lavado',  'clase' => 'st-clean', 'icono' => 'fa-arrows-spin'],
    'recibido'   => ['label' => 'Recibido',   'clase' => 'st-ok',    'icono' => 'fa-circle-check'],
    'cancelado'  => ['label' => 'Cancelado',  'clase' => 'st-off',   'icono' => 'fa-ban'],
];
?>

<?php include __DIR__ . '/_estilos.php'; ?>

<div class="lvx">

    <div class="lvx-header">
        <div class="lvx-header-shell">
            <div class="lvx-header-row">
                <div class="lvx-header-id">
                    <span class="lvx-header-ico"><i class="fas fa-arrows-spin"></i></span>
                    <div style="min-width:0;">
                        <h1>Ciclos de lavado</h1>
                        <p class="lvx-header-sub">Lo sucio sale por lote, regresa limpio y las mermas quedan contadas</p>
                    </div>
                </div>
                <?php if ($puedeOperar): ?>
                <div class="lvx-header-acts">
                    <a class="lvx-btn" href="<?= url('lavanderia/lotes/nuevo') ?>" data-prefetch>
                        <i class="fas fa-plus-circle"></i> <span>Enviar a lavar</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php
            $subnav_section = 'lavanderia';
            $subnav_active = 'lotes';
            include APP_PATH . '/views/partials/section_subnav.php';
            ?>
        </div>
    </div>

    <div class="lvx-shell">

        <?php include APP_PATH . '/views/partials/filtros.php'; ?>
        <form class="msf-bar is-plain" method="GET" action="<?= url('lavanderia/lotes') ?>" data-auto-filter-form>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Estado</span>
                <select class="msf-control" name="estado"><?= msf_options([
                    'todos' => 'Todos',
                    'en_proceso' => 'En lavado',
                    'recibido' => 'Recibidos',
                    'cancelado' => 'Cancelados',
                ], (string)$filtros['estado']) ?></select>
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Tipo</span>
                <select class="msf-control" name="tipo"><?= msf_options([
                    'todos' => 'Todos',
                    'interno' => 'Lavado interno',
                    'externo' => 'Servicio externo',
                ], (string)$filtros['tipo']) ?></select>
            </label>
        </form>

        <?php if ((int)$resumenLotes['gastos_pendientes'] > 0): ?>
        <section class="lvx-card" style="border-color:color-mix(in srgb,var(--c-maint) 40%,var(--lvx-line)); margin-bottom:14px;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="lvx-stat-ic" style="--sc:var(--c-maint); margin:0;"><i class="fas fa-cash-register"></i></span>
                <div style="min-width:0;flex:1;">
                    <strong style="color:var(--lvx-title);"><?= (int)$resumenLotes['gastos_pendientes'] ?> lote<?= (int)$resumenLotes['gastos_pendientes'] === 1 ? '' : 's' ?> con gasto POR REGISTRAR en Caja</strong>
                    <div style="font-size:.78rem;color:var(--lvx-muted);">Se capturó el costo con la caja cerrada: entra al detalle del lote y regístralo al abrir caja.</div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if (empty($lotes)): ?>
            <section class="lvx-card">
                <div class="lvx-vacio">
                    <i class="fas fa-arrows-spin"></i>
                    <strong style="display:block;color:var(--lvx-title);font-weight:700;">Sin ciclos de lavado<?= (string)$filtros['estado'] !== 'todos' || (string)$filtros['tipo'] !== 'todos' ? ' con este filtro' : '' ?></strong>
                    Cuando envíes blancos sucios a lavar (con tu equipo o con un proveedor externo), cada lote vivirá aquí
                    con sus piezas, mermas y costo.
                </div>
            </section>
        <?php else: ?>
            <section class="lvx-card" style="padding:6px 10px;">
                <div class="lvx-tabla-wrap">
                    <table class="lvx-tabla">
                        <thead>
                            <tr>
                                <th>Lote</th>
                                <th>Tipo</th>
                                <th class="num">Piezas</th>
                                <th class="num">Merma</th>
                                <th>Estado</th>
                                <th class="num">Costo</th>
                                <th>Enviado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lotes as $l): ?>
                                <?php
                                $meta = $estadoMeta[$l['estado'] ?? 'en_proceso'] ?? $estadoMeta['en_proceso'];
                                $gastoPendiente = (string)$l['estado'] === 'recibido' && (float)($l['costo'] ?? 0) > 0 && empty($l['gasto_movimiento_id']);
                                ?>
                                <tr data-easy-href="<?= url('lavanderia/lotes/' . (int)$l['id']) ?>" role="link" tabindex="0">
                                    <td><b>#<?= (int)$l['id'] ?></b><br><small style="color:var(--lvx-faint);"><?= (int)$l['partidas'] ?> partida<?= (int)$l['partidas'] === 1 ? '' : 's' ?></small></td>
                                    <td>
                                        <?= (string)$l['tipo'] === 'externo' ? '<i class="fas fa-truck" style="color:var(--lvx-muted);"></i> Externo' : '<i class="fas fa-house" style="color:var(--lvx-muted);"></i> Interno' ?>
                                        <?php if (!empty($l['proveedor'])): ?><br><small style="color:var(--lvx-faint);"><?= lvx_safe($l['proveedor']) ?></small><?php endif; ?>
                                    </td>
                                    <td class="num">
                                        <b><?= (int)$l['piezas_enviadas'] ?></b>
                                        <?php if ($l['piezas_recibidas'] !== null): ?><br><small style="color:var(--lvx-faint);"><?= (int)$l['piezas_recibidas'] ?> de vuelta</small><?php endif; ?>
                                    </td>
                                    <td class="num"><?= (int)$l['merma_total'] > 0 ? '<b style="color:var(--c-critical);">' . (int)$l['merma_total'] . '</b>' : '—' ?></td>
                                    <td>
                                        <span class="lvx-chip <?= $meta['clase'] ?>"><i class="fas <?= $meta['icono'] ?>"></i> <?= $meta['label'] ?></span>
                                        <?php if ($gastoPendiente): ?><br><small style="color:var(--c-maint);font-weight:700;">Gasto por registrar</small><?php endif; ?>
                                    </td>
                                    <td class="num"><?= (float)($l['costo'] ?? 0) > 0 ? '$' . number_format((float)$l['costo'], 2) : '—' ?></td>
                                    <td>
                                        <?= lvx_safe(date('d/m/Y H:i', strtotime((string)$l['created_at']))) ?>
                                        <?php if (!empty($l['enviado_por'])): ?><br><small style="color:var(--lvx-faint);"><?= lvx_safe($l['enviado_por']) ?></small><?php endif; ?>
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
