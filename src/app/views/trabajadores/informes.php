<?php
/**
 * Personal - Informes: índice único de los reportes de la sección.
 * Vista liviana de navegación; cada reporte conserva su propia vista y exportador.
 */
$infReportes = [
    [
        'url' => url('trabajadores/reporte'),
        'icono' => 'fa-chart-pie',
        'titulo' => 'Personal',
        'desc' => 'Personas por rol y estado: altas, bajas y composición del equipo.',
    ],
    [
        'url' => url('trabajadores/nomina/periodos/reporte'),
        'icono' => 'fa-file-lines',
        'titulo' => 'Snapshots de pre-nómina',
        'desc' => 'Periodos cerrados y aprobados: totales, estados y detalle histórico.',
    ],
    [
        'url' => url('trabajadores/nomina/periodos/pagos-snapshot'),
        'icono' => 'fa-link',
        'titulo' => 'Conciliación de pagos',
        'desc' => 'Cruce entre lo cerrado en pre-nómina y los pagos registrados por Caja.',
    ],
    [
        'url' => url('trabajadores/nomina/auditoria'),
        'icono' => 'fa-list-check',
        'titulo' => 'Auditoría de pre-nómina',
        'desc' => 'Bitácora consolidada: quién cerró, aprobó o anuló cada periodo y cuándo.',
    ],
    [
        'url' => url('trabajadores/nomina/expediente'),
        'icono' => 'fa-folder-open',
        'titulo' => 'Expediente administrativo',
        'desc' => 'Constancia administrativa por periodo para respaldo y revisión externa.',
    ],
];
?>
<style>
.informes-page {
    --inf-brand: var(--brand-primary, #1B2746);
    --inf-gold: var(--brand-accent, #BD9441);
    --inf-surface: var(--brand-surface, #F6F2EA);
    --inf-text: var(--brand-text, #232323);
    --inf-muted: var(--brand-muted, #6d675e);
    --inf-border: var(--brand-border, #e3dccd);
    --inf-card: color-mix(in srgb, var(--inf-surface) 55%, #ffffff);
    color: var(--inf-text);
    max-width: 1120px;
    margin: 0 auto;
    padding: 4px 4px 40px;
}
.informes-page .inf-title-lockup { display: flex; align-items: center; gap: 14px; margin-bottom: 4px; }
.informes-page .inf-hero-icon {
    width: 48px; height: 48px; border-radius: 14px; flex: 0 0 auto;
    display: flex; align-items: center; justify-content: center;
    background: color-mix(in srgb, var(--inf-brand) 10%, transparent);
    color: var(--inf-brand); font-size: 20px;
}
.informes-page .inf-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--inf-gold); font-weight: 700; margin: 0; }
.informes-page .inf-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 30px; line-height: 1.1; margin: 2px 0 0; font-weight: 600; }
.informes-page .inf-subtitle { color: var(--inf-muted); font-size: 14px; margin: 6px 0 18px; }
.informes-page .inf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.informes-page .inf-card {
    display: flex; align-items: flex-start; gap: 14px;
    border: 1px solid var(--inf-border); border-radius: 16px;
    background: var(--inf-card); padding: 18px 16px;
    text-decoration: none; color: var(--inf-text);
    transition: border-color .15s ease, transform .12s ease, box-shadow .15s ease;
}
.informes-page .inf-card:hover {
    border-color: var(--inf-gold);
    transform: translateY(-2px);
    box-shadow: 0 14px 28px -24px color-mix(in srgb, var(--inf-brand) 45%, transparent);
}
.informes-page .inf-card-icon {
    width: 40px; height: 40px; border-radius: 12px; flex: 0 0 auto;
    display: flex; align-items: center; justify-content: center;
    background: color-mix(in srgb, var(--inf-brand) 8%, transparent);
    color: var(--inf-brand); font-size: 16px;
}
.informes-page .inf-card h2 { font-size: 15px; font-weight: 700; margin: 0 0 4px; display: flex; align-items: center; gap: 8px; }
.informes-page .inf-card h2 i.inf-arrow { font-size: 11px; color: var(--inf-gold); transition: transform .15s ease; }
.informes-page .inf-card:hover h2 i.inf-arrow { transform: translateX(3px); }
.informes-page .inf-card p { font-size: 13px; color: var(--inf-muted); margin: 0; line-height: 1.45; }
</style>

<div class="informes-page">
    <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>

    <div class="inf-title-lockup">
        <div class="inf-hero-icon"><i class="fas fa-chart-pie"></i></div>
        <div>
            <p class="inf-kicker">Personal</p>
            <h1 class="inf-title">Informes</h1>
        </div>
    </div>
    <p class="inf-subtitle">Todos los reportes de Personal en un solo lugar: equipo, pre-nómina, conciliación y respaldos.</p>

    <?php $subnav_section = 'personal'; $subnav_active = 'informes'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <div class="inf-grid" data-ms-stagger>
        <?php foreach ($infReportes as $infReporte): ?>
        <a class="inf-card ms-pressable" href="<?= $infReporte['url'] ?>">
            <div class="inf-card-icon"><i class="fas <?= $infReporte['icono'] ?>"></i></div>
            <div>
                <h2><?= $infReporte['titulo'] ?> <i class="fas fa-arrow-right inf-arrow"></i></h2>
                <p><?= $infReporte['desc'] ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
