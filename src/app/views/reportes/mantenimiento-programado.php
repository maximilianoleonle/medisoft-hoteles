<?php
if (!function_exists('mant_prog_safe')) {
    function mant_prog_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mant_prog_num')) {
    function mant_prog_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('mant_prog_date')) {
    function mant_prog_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y', $timestamp) : '-';
    }
}

$preview = is_array($preview ?? null) ? $preview : [];
$resumen = is_array($preview['resumen'] ?? null) ? $preview['resumen'] : [];
$registros = is_array($preview['registros'] ?? null) ? $preview['registros'] : [];
$dias = (int)($dias ?? ($preview['dias'] ?? 30));
$dias = max(0, min(90, $dias));

$categoriaLabels = [
    'vencido' => 'Vencido',
    'hoy' => 'Hoy',
    'proximo' => 'Proximo',
];

$categoriaClass = [
    'vencido' => 'is-danger',
    'hoy' => 'is-warning',
    'proximo' => 'is-neutral',
];

$puedeActivarMantenimiento = function_exists('can') ? can('habitaciones.mantenimiento') : false;
?>

<style>
.mant-prog{padding:24px;max-width:1320px;margin:0 auto;color:#172033}
.mant-prog-hero{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:20px}
.mant-prog-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#64748b;font-weight:900}
.mant-prog-title{font-size:30px;line-height:1.1;margin:6px 0 8px;font-weight:900;color:#101828}
.mant-prog-subtitle{color:#64748b;max-width:820px;margin:0}
.mant-prog-actions{display:flex;gap:10px;flex-wrap:wrap}
.mant-prog-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:7px;background:#fff;color:#172033;border:1px solid #d1d5db;font-weight:800;padding:10px 14px;text-decoration:none;min-height:40px}
.mant-prog-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:18px}
.mant-prog-metric{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.mant-prog-metric span{display:block;font-size:12px;color:#64748b;font-weight:800;text-transform:uppercase}
.mant-prog-metric strong{display:block;font-size:25px;line-height:1.1;margin-top:8px;color:#111827}
.mant-prog-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 28px rgba(15,23,42,.06);overflow:hidden}
.mant-prog-panel-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;padding:16px 18px;border-bottom:1px solid #e5e7eb;background:#fbfcfe}
.mant-prog-panel-title{font-size:16px;font-weight:900;color:#111827;margin:0}
.mant-prog-panel-subtitle{font-size:13px;color:#64748b;margin:4px 0 0}
.mant-prog-tabs{display:flex;gap:8px;flex-wrap:wrap}
.mant-prog-tab{display:inline-flex;align-items:center;border-radius:999px;padding:7px 11px;border:1px solid #d1d5db;background:#fff;color:#475569;font-size:12px;font-weight:900;text-decoration:none}
.mant-prog-tab.is-active{background:#172033;color:#fff;border-color:#172033}
.mant-prog-table-wrap{overflow-x:auto}
.mant-prog-table{width:100%;border-collapse:collapse;min-width:980px}
.mant-prog-table th{font-size:12px;text-align:left;text-transform:uppercase;letter-spacing:.08em;color:#64748b;background:#fbfcfe;padding:12px 14px;border-bottom:1px solid #e5e7eb}
.mant-prog-table td{padding:13px 14px;border-bottom:1px solid #eef2f7;vertical-align:top;font-size:14px}
.mant-prog-link{font-weight:900;color:#102a43;text-decoration:none}
.mant-prog-muted{color:#64748b;font-size:13px;margin-top:4px}
.mant-prog-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:900;background:#eef2ff;color:#3730a3}
.mant-prog-pill.is-danger{background:#fee2e2;color:#991b1b}
.mant-prog-pill.is-warning{background:#fef3c7;color:#92400e}
.mant-prog-pill.is-neutral{background:#e0f2fe;color:#075985}
.mant-prog-warnings{display:flex;flex-direction:column;gap:5px}
.mant-prog-warning{font-size:12px;color:#475569;background:#f8fafc;border:1px solid #e5e7eb;border-radius:7px;padding:6px 8px;font-weight:700}
.mant-prog-inline-form{margin-top:9px}
.mant-prog-activate{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:0;border-radius:7px;background:#0f766e;color:#fff;font-size:12px;font-weight:900;padding:8px 10px;cursor:pointer}
.mant-prog-activate:hover{background:#115e59}
.mant-prog-empty{padding:38px 20px;text-align:center;color:#64748b}
.mant-prog-empty strong{display:block;color:#172033;font-size:18px;margin-bottom:8px}
.mant-prog-note{padding:14px 18px;background:#f8fafc;color:#475569;border-top:1px solid #e5e7eb;font-size:13px;font-weight:700}
@media (max-width:1100px){.mant-prog-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:720px){.mant-prog-hero{display:block}.mant-prog-actions{margin-top:14px}.mant-prog-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.mant-prog-title{font-size:24px}.mant-prog-panel-head{display:block}.mant-prog-tabs{margin-top:12px}}
</style>

<div class="mant-prog">
    <div class="mant-prog-hero">
        <div>
            <div class="mant-prog-kicker">Reportes / Mantenimiento</div>
            <h1 class="mant-prog-title">Preview de mantenimiento programado</h1>
            <p class="mant-prog-subtitle">Lectura de mantenimientos vencidos o proximos para el hotel actual. Esta pantalla no activa mantenimientos, no cambia habitaciones y no ejecuta automatizaciones.</p>
        </div>
        <div class="mant-prog-actions">
            <a class="mant-prog-btn" href="<?= url('reportes') ?>"><i class="fas fa-arrow-left"></i> Reportes</a>
            <a class="mant-prog-btn" href="<?= url('reportes/mantenimiento') ?>"><i class="fas fa-chart-line"></i> Reporte general</a>
        </div>
    </div>

    <div class="mant-prog-grid" aria-label="Resumen de mantenimiento programado">
        <div class="mant-prog-metric"><span>Total</span><strong><?= mant_prog_num($resumen['total'] ?? 0) ?></strong></div>
        <div class="mant-prog-metric"><span>Vencidos</span><strong><?= mant_prog_num($resumen['vencidos'] ?? 0) ?></strong></div>
        <div class="mant-prog-metric"><span>Hoy</span><strong><?= mant_prog_num($resumen['hoy'] ?? 0) ?></strong></div>
        <div class="mant-prog-metric"><span>Proximos</span><strong><?= mant_prog_num($resumen['proximos'] ?? 0) ?></strong></div>
        <div class="mant-prog-metric"><span>Conflictos</span><strong><?= mant_prog_num($resumen['con_conflictos'] ?? 0) ?></strong></div>
        <div class="mant-prog-metric"><span>Candidatos</span><strong><?= mant_prog_num($resumen['candidatos'] ?? 0) ?></strong></div>
    </div>

    <section class="mant-prog-panel">
        <div class="mant-prog-panel-head">
            <div>
                <h2 class="mant-prog-panel-title">Candidatos y advertencias</h2>
                <p class="mant-prog-panel-subtitle">Ventana: <?= mant_prog_num($dias) ?> dias, hasta <?= mant_prog_safe(mant_prog_date($preview['hasta'] ?? null)) ?>.</p>
            </div>
            <div class="mant-prog-tabs" aria-label="Ventana de revision">
                <?php foreach ([7, 30, 90] as $opcionDias): ?>
                    <a class="mant-prog-tab <?= $dias === $opcionDias ? 'is-active' : '' ?>" href="<?= url('reportes/mantenimiento-programado?dias=' . $opcionDias) ?>"><?= $opcionDias ?> dias</a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($registros)): ?>
            <div class="mant-prog-empty">
                <strong>Sin mantenimientos programados en la ventana</strong>
                <p>No hay candidatos vencidos o proximos para revisar en este momento.</p>
            </div>
        <?php else: ?>
            <div class="mant-prog-table-wrap">
                <table class="mant-prog-table">
                    <thead>
                        <tr>
                            <th>Habitacion</th>
                            <th>Programacion</th>
                            <th>Tipo / prioridad</th>
                            <th>Estado habitacion</th>
                            <th>Advertencias</th>
                            <th>Revision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $item): ?>
                            <?php
                            $categoria = (string)($item['categoria_preview'] ?? 'proximo');
                            $pillClass = $categoriaClass[$categoria] ?? 'is-neutral';
                            $advertencias = is_array($item['preview_advertencias'] ?? null) ? $item['preview_advertencias'] : [];
                            ?>
                            <tr>
                                <td>
                                    <a class="mant-prog-link" href="<?= url('habitaciones/' . (int)($item['habitacion_id'] ?? 0)) ?>">
                                        Habitacion <?= mant_prog_safe($item['habitacion_numero'] ?? null) ?>
                                    </a>
                                    <div class="mant-prog-muted"><?= mant_prog_safe($item['habitacion_tipo'] ?? null) ?> - Piso <?= mant_prog_safe($item['habitacion_piso'] ?? null) ?></div>
                                </td>
                                <td>
                                    <span class="mant-prog-pill <?= $pillClass ?>"><?= mant_prog_safe($categoriaLabels[$categoria] ?? $categoria) ?></span>
                                    <div class="mant-prog-muted"><?= mant_prog_date($item['fecha_programada'] ?? null) ?><?= !empty($item['fecha_programada_fin']) ? ' - ' . mant_prog_date($item['fecha_programada_fin']) : '' ?></div>
                                </td>
                                <td>
                                    <strong><?= mant_prog_safe(ucfirst(str_replace('_', ' ', (string)($item['tipo_mantenimiento'] ?? '')))) ?></strong>
                                    <div class="mant-prog-muted">Prioridad <?= mant_prog_safe(ucfirst((string)($item['prioridad'] ?? 'media'))) ?></div>
                                </td>
                                <td>
                                    <span class="mant-prog-pill"><?= mant_prog_safe(ucfirst((string)($item['habitacion_estado'] ?? ''))) ?></span>
                                    <div class="mant-prog-muted">Reservaciones conflictivas: <?= mant_prog_num($item['reservaciones_conflicto'] ?? 0) ?></div>
                                </td>
                                <td>
                                    <div class="mant-prog-warnings">
                                        <?php foreach ($advertencias as $advertencia): ?>
                                            <span class="mant-prog-warning"><?= mant_prog_safe($advertencia) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="mant-prog-pill <?= !empty($item['preview_candidato']) ? 'is-warning' : 'is-neutral' ?>">
                                        <?= !empty($item['preview_candidato']) ? 'Candidato' : 'Solo lectura' ?>
                                    </span>
                                    <?php if (!empty($item['preview_candidato']) && $puedeActivarMantenimiento): ?>
                                        <form class="mant-prog-inline-form"
                                              method="POST"
                                              action="<?= url('habitaciones/activar-mantenimiento-programado/' . (int)($item['id'] ?? 0)) ?>"
                                              onsubmit="return confirm('Activar este mantenimiento programado y marcar la habitacion en mantenimiento?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="dias" value="<?= (int)$dias ?>">
                                            <input type="hidden" name="return_to" value="preview">
                                            <button type="submit" class="mant-prog-activate">
                                                <i class="fas fa-play"></i>
                                                Activar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!empty($item['motivo'])): ?>
                                        <div class="mant-prog-muted"><?= mant_prog_safe($item['motivo']) ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mant-prog-note">
            Esta vista no llama activaciones automaticas, no crea tareas y no toca Caja, pagos, abonos, nomina, offline ni /api/sync. La activacion manual disponible para candidatos requiere permiso, CSRF y validacion backend.
        </div>
    </section>
</div>
