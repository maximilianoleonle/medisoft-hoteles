<?php
/**
 * Historial de servicios de un activo (bloque mantenimiento_plus):
 * fechas, fotos, costo por servicio y costo acumulado por anio.
 */

if (!function_exists('mah_safe')) {
    function mah_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mah_fecha')) {
    function mah_fecha($value): string
    {
        if (empty($value)) {
            return '-';
        }
        $ts = strtotime((string)$value);
        return $ts ? date('d/m/Y', $ts) : '-';
    }
}

$servicios = $historial['servicios'] ?? [];
$costoPorAnio = $historial['costo_por_anio'] ?? [];
$costoTotal = (float)($historial['costo_total'] ?? 0);
$anioActual = (int)date('Y');
$costoAnioActual = 0.0;
foreach ($costoPorAnio as $fila) {
    if ((int)($fila['anio'] ?? 0) === $anioActual) {
        $costoAnioActual = (float)($fila['total'] ?? 0);
        break;
    }
}

$ubic = trim((string)($activo['habitacion_numero'] ?? '')) !== ''
    ? 'Hab. ' . $activo['habitacion_numero']
    : (trim((string)($activo['ubicacion'] ?? '')) ?: 'Instalaciones generales');

$estadoMeta = [
    'en_proceso' => ['label' => 'En proceso', 'bg' => '#E2F4F4', 'color' => '#0E8A8A'],
    'programado' => ['label' => 'Programado', 'bg' => '#E6EFFC', 'color' => '#2F77E0'],
    'completado' => ['label' => 'Completado', 'bg' => '#E7F4EC', 'color' => '#1E9E63'],
    'cancelado'  => ['label' => 'Cancelado', 'bg' => '#EEEDE9', 'color' => '#828B99'],
];
?>

<style>
.mah {
    --mh-brand: var(--brand-primary, #1B2746);
    --mh-gold: var(--brand-accent, #BD9441);
    --mh-ivory: #F6F2EA; --mh-ivory-2: #FBF8F2;
    --mh-border: color-mix(in srgb, var(--mh-brand) 7%, #E7E1D4);
    --mh-text: color-mix(in srgb, var(--mh-brand) 36%, #596474);
    --mh-muted: #828B99;
    --mh-heading: color-mix(in srgb, var(--mh-brand) 62%, #667284);
    min-height: 100%;
    color: var(--mh-text);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--mh-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--mh-ivory-2), var(--mh-ivory));
    padding: 16px 14px 90px;
}
.mah .mah-shell { max-width: 860px; margin: 0 auto; display: grid; gap: 14px; }
.mah .mah-card {
    background: #FFFFFF; border: 1px solid var(--mh-border); border-radius: 16px;
    box-shadow: 0 10px 26px -20px color-mix(in srgb, var(--mh-brand) 45%, transparent);
    padding: 16px;
}
.mah h1 { margin: 0; font-size: 1.2rem; color: var(--mh-heading); font-weight: 700; }
.mah .mah-sub { margin: 3px 0 0; font-size: .8rem; color: var(--mh-muted); }
.mah .mah-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 14px; }
@media (max-width: 560px) { .mah .mah-kpis { grid-template-columns: 1fr; } }
.mah .mah-kpi { background: color-mix(in srgb, var(--mh-brand) 3%, #FCFAF5); border: 1px solid var(--mh-border); border-radius: 12px; padding: 11px 13px; }
.mah .mah-kpi small { display: block; font-size: .68rem; letter-spacing: .08em; text-transform: uppercase; color: var(--mh-muted); font-weight: 700; }
.mah .mah-kpi strong { display: block; margin-top: 3px; font-size: 1.05rem; color: var(--mh-heading); font-weight: 700; }
.mah .mah-kpi.is-money strong { color: color-mix(in srgb, var(--mh-gold) 80%, var(--mh-brand)); }

.mah .mah-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 11px 0; border-bottom: 1px dashed var(--mh-border); }
.mah .mah-row:last-child { border-bottom: none; }
.mah .mah-row-info strong { color: var(--mh-heading); font-size: .88rem; font-weight: 700; }
.mah .mah-row-info span { display: block; font-size: .74rem; color: var(--mh-muted); margin-top: 2px; }
.mah .mah-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .7rem; font-weight: 700; white-space: nowrap; }
.mah .mah-link {
    display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
    padding: 7px 11px; border-radius: 10px; font-size: .76rem; font-weight: 700;
    border: 1px solid var(--mh-border); color: var(--mh-heading); background: #FFF; white-space: nowrap;
}
.mah .mah-vacio { padding: 22px 12px; text-align: center; color: var(--mh-muted); font-size: .84rem; }
</style>

<div class="mah">
    <div class="mah-shell">

        <section class="mah-card">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <div>
                    <h1><i class="fas fa-fire-burner" style="color:var(--mh-gold);margin-right:8px;"></i><?= mah_safe($activo['nombre']) ?></h1>
                    <p class="mah-sub">
                        <i class="fas fa-location-dot"></i> <?= mah_safe($ubic) ?>
                        · Servicio cada <?= (int)$activo['periodicidad_dias'] ?> d&iacute;as
                        · Pr&oacute;ximo: <strong><?= mah_fecha($activo['proximo_servicio'] ?? null) ?></strong>
                    </p>
                    <?php if (!empty($activo['notas'])): ?>
                        <p class="mah-sub" style="margin-top:6px;"><i class="fas fa-note-sticky" style="color:var(--mh-gold);"></i> <?= mah_safe($activo['notas']) ?></p>
                    <?php endif; ?>
                </div>
                <a class="mah-link" href="<?= url('mantenimientos/activos') ?>"><i class="fas fa-arrow-left"></i> Activos</a>
            </div>

            <div class="mah-kpis">
                <div class="mah-kpi is-money">
                    <small>Costo en <?= $anioActual ?></small>
                    <strong>$<?= number_format($costoAnioActual, 2) ?></strong>
                </div>
                <div class="mah-kpi is-money">
                    <small>Costo hist&oacute;rico</small>
                    <strong>$<?= number_format($costoTotal, 2) ?></strong>
                </div>
                <div class="mah-kpi">
                    <small>Servicios registrados</small>
                    <strong><?= count($servicios) ?></strong>
                </div>
            </div>

            <?php if (count($costoPorAnio) > 1): ?>
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;font-size:.76rem;color:var(--mh-muted);">
                    <?php foreach ($costoPorAnio as $fila): ?>
                        <span><?= (int)$fila['anio'] ?>: <strong style="color:var(--mh-heading);">$<?= number_format((float)$fila['total'], 2) ?></strong> (<?= (int)$fila['servicios'] ?>)</span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="mah-card">
            <h2 style="margin:0 0 6px;font-size:1rem;color:var(--mh-heading);font-weight:700;">
                <i class="fas fa-clock-rotate-left" style="color:var(--mh-gold);margin-right:6px;"></i>Historial de servicios
            </h2>

            <?php if (empty($servicios)): ?>
                <div class="mah-vacio">
                    <i class="fas fa-wrench" style="display:block;font-size:1.4rem;margin-bottom:6px;color:var(--mh-gold);"></i>
                    Este activo a&uacute;n no tiene servicios. Al vencer su fecha, el preventivo se genera solo.
                </div>
            <?php else: ?>
                <?php foreach ($servicios as $srv): ?>
                    <?php $em = $estadoMeta[$srv['estado'] ?? ''] ?? $estadoMeta['completado']; ?>
                    <div class="mah-row">
                        <div class="mah-row-info" style="min-width:0;flex:1 1 220px;">
                            <strong><?= mah_safe($srv['motivo'] ?? 'Servicio') ?></strong>
                            <span>
                                <?= mah_fecha($srv['fecha_inicio'] ?? null) ?>
                                <?php if (!empty($srv['fecha_fin'])): ?> &rarr; <?= mah_fecha($srv['fecha_fin']) ?><?php endif; ?>
                                <?php if (!empty($srv['proveedor'])): ?> · <?= mah_safe($srv['proveedor']) ?><?php endif; ?>
                                <?php if ((int)($srv['fotos_total'] ?? 0) > 0): ?> · <i class="fas fa-camera"></i> <?= (int)$srv['fotos_total'] ?><?php endif; ?>
                            </span>
                        </div>
                        <span class="mah-chip" style="background:<?= $em['bg'] ?>;color:<?= $em['color'] ?>;"><?= mah_safe($em['label']) ?></span>
                        <strong style="font-size:.9rem;color:var(--mh-heading);white-space:nowrap;">
                            <?= isset($srv['costo']) && $srv['costo'] !== null ? '$' . number_format((float)$srv['costo'], 2) : '-' ?>
                            <?php if (!empty($srv['gasto_movimiento_id'])): ?>
                                <i class="fas fa-circle-check" style="color:#1E9E63;font-size:.75rem;" title="Registrado en gastos"></i>
                            <?php endif; ?>
                        </strong>
                        <a class="mah-link" href="<?= url('mantenimientos/' . (int)$srv['id']) ?>"><i class="fas fa-arrow-right"></i> Detalle</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

    </div>
</div>
