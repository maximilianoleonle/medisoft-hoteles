<?php
/**
 * Mapa digital del hotel (bloque habitaciones y areas): habitaciones + areas
 * por piso con estado en vivo. Lenguaje calcado de habitaciones/index.php:
 * header glass con subnav integrada, fichas stat (filtran el mapa al tocar) y
 * separadores de piso serif. Solo lectura: cada ficha enlaza a su detalle.
 */

if (!function_exists('mxp_safe')) {
    function mxp_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$habitaciones = $habitaciones ?? [];
$areas = $areas ?? [];
$conteo = $conteo ?? [];
$tiposArea = $tipos_area ?? [];

$estadoMeta = [
    'disponible'    => ['label' => 'Disponible',    'clase' => 'st-ok',    'icono' => 'fa-circle-check'],
    'ocupada'       => ['label' => 'Ocupada',       'clase' => 'st-busy',  'icono' => 'fa-bed'],
    'limpieza'      => ['label' => 'En limpieza',   'clase' => 'st-clean', 'icono' => 'fa-broom'],
    'mantenimiento' => ['label' => 'Mantenimiento', 'clase' => 'st-warn',  'icono' => 'fa-wrench'],
    'cerrada'       => ['label' => 'Cerrada',       'clase' => 'st-off',   'icono' => 'fa-ban'],
];

// Agrupar por piso: habitaciones y areas juntas; areas sin piso van a "Exterior".
$porPiso = [];
$exterior = [];
foreach ($habitaciones as $h) {
    $porPiso[(int)$h['piso']]['habitaciones'][] = $h;
}
foreach ($areas as $a) {
    if ($a['piso'] === null || $a['piso'] === '') {
        $exterior[] = $a;
    } else {
        $porPiso[(int)$a['piso']]['areas'][] = $a;
    }
}
ksort($porPiso);

$hayContenido = !empty($porPiso) || !empty($exterior);
?>

<?php include __DIR__ . '/_estilos.php'; ?>

<div class="arx">

    <div class="arx-header">
        <div class="arx-header-shell">
            <div class="arx-header-row">
                <div class="arx-header-id">
                    <span class="arx-header-ico"><i class="fas fa-map"></i></span>
                    <div style="min-width:0;">
                        <h1>Mapa del hotel</h1>
                        <p class="arx-header-sub">Habitaciones y áreas piso por piso, con su estado en este momento</p>
                    </div>
                </div>
            </div>
            <?php
            $subnav_section = 'habitaciones';
            $subnav_active = 'mapa';
            include APP_PATH . '/views/partials/section_subnav.php';
            ?>
        </div>
    </div>

    <div class="arx-shell">

        <?php if (!$hayContenido): ?>
            <section class="arx-card">
                <div class="arx-vacio">
                    <i class="fas fa-map"></i>
                    <strong style="display:block;color:var(--ax-primary);font-weight:700;">El mapa está vacío</strong>
                    Da de alta habitaciones y áreas y aquí verás todo el hotel de un vistazo.
                </div>
            </section>
        <?php else: ?>

        <div class="arx-stats" style="--ax-stats-n:5;" id="mxpStats">
            <?php foreach ($estadoMeta as $estadoKey => $meta): ?>
            <div class="arx-stat <?= $meta['clase'] ?>" data-estado="<?= $estadoKey ?>" role="button" tabindex="0"
                 onclick="mxpFiltrar(this)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();mxpFiltrar(this);}"
                 title="Resaltar en el mapa: <?= mxp_safe($meta['label']) ?>">
                <span class="arx-stat-ic"><i class="fas <?= $meta['icono'] ?>"></i></span>
                <span class="arx-stat-n"><?= (int)($conteo[$estadoKey] ?? 0) ?></span>
                <span class="arx-stat-l"><?= mxp_safe($meta['label']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <?php foreach ($porPiso as $piso => $grupo): ?>
            <?php
            $habsPiso = $grupo['habitaciones'] ?? [];
            $areasPiso = $grupo['areas'] ?? [];
            $totalPiso = count($habsPiso) + count($areasPiso);
            ?>
            <div class="arx-floor">
                <span class="arx-floor-t"><?= $piso === 0 ? 'Planta baja' : 'Piso ' . (int)$piso ?></span>
                <span class="arx-floor-rule"></span>
                <span class="arx-floor-ct"><?= $totalPiso ?> espacio<?= $totalPiso === 1 ? '' : 's' ?></span>
            </div>
            <?php if (!empty($habsPiso)): ?>
            <div class="arx-map-grid">
                <?php foreach ($habsPiso as $h): ?>
                    <?php $em = $estadoMeta[$h['estado'] ?? 'disponible'] ?? $estadoMeta['disponible']; ?>
                    <a class="arx-room <?= $em['clase'] ?>" href="<?= url('habitaciones/' . (int)$h['id']) ?>"
                       data-estado="<?= mxp_safe((string)($h['estado'] ?? 'disponible')) ?>"
                       title="Habitación <?= mxp_safe($h['numero']) ?> · <?= mxp_safe($em['label']) ?>">
                        <span class="arx-dot" aria-hidden="true"></span>
                        <b><?= mxp_safe($h['numero']) ?></b>
                        <small><?= mxp_safe($em['label']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($areasPiso)): ?>
            <div class="arx-map-areas">
                <?php foreach ($areasPiso as $a): ?>
                    <?php
                    $em = $estadoMeta[$a['estado'] ?? 'disponible'] ?? $estadoMeta['disponible'];
                    $tm = $tiposArea[$a['tipo'] ?? 'otra'] ?? ['label' => 'Área', 'icono' => 'fa-location-dot'];
                    ?>
                    <a class="arx-area" href="<?= url('areas/' . (int)$a['id']) ?>" data-prefetch
                       data-estado="<?= mxp_safe((string)($a['estado'] ?? 'disponible')) ?>"
                       title="<?= mxp_safe($a['nombre']) ?> · <?= mxp_safe($em['label']) ?>">
                        <span class="arx-tipo-ico"><i class="fas <?= mxp_safe($tm['icono']) ?>"></i></span>
                        <span class="arx-area-txt">
                            <strong><?= mxp_safe($a['nombre']) ?></strong>
                            <small><?= mxp_safe($tm['label']) ?></small>
                        </span>
                        <span class="arx-chip <?= $em['clase'] ?>"><i class="fas <?= $em['icono'] ?>"></i> <?= mxp_safe($em['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!empty($exterior)): ?>
            <div class="arx-floor">
                <span class="arx-floor-t">Exterior y planta baja</span>
                <span class="arx-floor-rule"></span>
                <span class="arx-floor-ct"><?= count($exterior) ?> área<?= count($exterior) === 1 ? '' : 's' ?></span>
            </div>
            <div class="arx-map-areas" style="margin-top:0;">
                <?php foreach ($exterior as $a): ?>
                    <?php
                    $em = $estadoMeta[$a['estado'] ?? 'disponible'] ?? $estadoMeta['disponible'];
                    $tm = $tiposArea[$a['tipo'] ?? 'otra'] ?? ['label' => 'Área', 'icono' => 'fa-location-dot'];
                    ?>
                    <a class="arx-area" href="<?= url('areas/' . (int)$a['id']) ?>" data-prefetch
                       data-estado="<?= mxp_safe((string)($a['estado'] ?? 'disponible')) ?>"
                       title="<?= mxp_safe($a['nombre']) ?> · <?= mxp_safe($em['label']) ?>">
                        <span class="arx-tipo-ico"><i class="fas <?= mxp_safe($tm['icono']) ?>"></i></span>
                        <span class="arx-area-txt">
                            <strong><?= mxp_safe($a['nombre']) ?></strong>
                            <small><?= mxp_safe($tm['label']) ?></small>
                        </span>
                        <span class="arx-chip <?= $em['clase'] ?>"><i class="fas <?= $em['icono'] ?>"></i> <?= mxp_safe($em['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="arx-card" style="margin-top:18px;">
            <div class="arx-leyenda">
                <span class="st-ok"><i class="fas fa-circle"></i>Disponible</span>
                <span class="st-busy"><i class="fas fa-circle"></i>Ocupada</span>
                <span class="st-clean"><i class="fas fa-circle"></i>En limpieza</span>
                <span class="st-warn"><i class="fas fa-circle"></i>Mantenimiento</span>
                <span class="st-off"><i class="fas fa-circle"></i>Cerrada</span>
            </div>
        </section>

        <?php endif; ?>

    </div>
</div>

<script>
// Fichas stat como resaltador del mapa (calco del patron hbSetEstado):
// tocar una ficha atenua habitaciones/areas de otros estados; repetir limpia.
function mxpFiltrar(ficha) {
    var estado = ficha.getAttribute('data-estado') || '';
    var activa = ficha.classList.contains('is-active');
    document.querySelectorAll('#mxpStats .arx-stat').forEach(function (f) { f.classList.remove('is-active'); });
    var fichas = document.querySelectorAll('.arx-room[data-estado], .arx-area[data-estado]');
    if (activa) {
        fichas.forEach(function (i) { i.classList.remove('is-dim'); });
        return;
    }
    ficha.classList.add('is-active');
    fichas.forEach(function (i) {
        i.classList.toggle('is-dim', (i.getAttribute('data-estado') || '') !== estado);
    });
}
</script>
