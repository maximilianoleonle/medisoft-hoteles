<?php
/**
 * Mapa digital del hotel (bloque habitaciones y areas): habitaciones + areas
 * agrupadas por piso con estado en vivo. Solo lectura: cada ficha enlaza a su
 * detalle. Lenguaje Deleite Sereno: base marfil serena, numeros serif,
 * recompensas puntuales (lift al hover, pulso solo en estados vivos).
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

// Semanticos fijos (no re-tematizan): exito, limpieza, alerta, error, ocupada terracota.
$estadoMeta = [
    'disponible'    => ['label' => 'Disponible',    'clase' => 'st-ok',     'icono' => 'fa-circle-check'],
    'ocupada'       => ['label' => 'Ocupada',       'clase' => 'st-busy',   'icono' => 'fa-user'],
    'limpieza'      => ['label' => 'En limpieza',   'clase' => 'st-clean',  'icono' => 'fa-broom'],
    'mantenimiento' => ['label' => 'Mantenimiento', 'clase' => 'st-warn',   'icono' => 'fa-wrench'],
    'cerrada'       => ['label' => 'Cerrada',       'clase' => 'st-off',    'icono' => 'fa-ban'],
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

<?php
$subnav_section = 'habitaciones';
$subnav_active = 'mapa';
include APP_PATH . '/views/partials/section_subnav.php';
?>

<style>
.mxp{
    /* Marca (re-tematiza por hotel) */
    --mx-brand: var(--brand-primary, #1B2746);
    --mx-gold: var(--brand-accent, #BD9441);
    /* Semanticos (fijos por significado) */
    --mx-ok:#1E9E63;    --mx-ok-soft:#E7F4EC;
    --mx-clean:#2F77E0; --mx-clean-soft:#E6EFFC;
    --mx-warn:#C2841C;  --mx-warn-soft:#FAF0DC;
    --mx-error:#D64539; --mx-error-soft:#F8EAE5;
    --mx-busy:#C0653F;  --mx-busy-soft:#F7EAE2;
    /* Superficies serenas */
    --mx-surface:#FFFFFF; --mx-warm:#FCFAF5; --mx-ivory:#F6F2EA; --mx-ivory-2:#FBF8F2;
    --mx-line: color-mix(in srgb, var(--mx-brand) 7%, #E7E1D4);
    --mx-ink: color-mix(in srgb, var(--mx-brand) 62%, #667284);
    --mx-ink-soft: color-mix(in srgb, var(--mx-brand) 36%, #596474);
    --mx-muted:#828B99;
    --mx-serif:'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --mx-ease:cubic-bezier(.22,1,.36,1);
    min-height:100%;
    color:var(--mx-ink-soft);
    font-family:'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--mx-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--mx-ivory-2), var(--mx-ivory));
    padding:16px 14px 90px;
}
.mxp .mxp-shell{max-width:1080px;margin:0 auto;display:grid;gap:14px;}
.mxp .mxp-card{
    background:var(--mx-surface);
    border:1px solid var(--mx-line);
    border-radius:16px;
    box-shadow:0 10px 26px -20px color-mix(in srgb, var(--mx-brand) 45%, transparent);
    padding:16px;
}
.mxp .mxp-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;}
.mxp .mxp-head h1{margin:0;font-size:1.2rem;color:var(--mx-ink);font-weight:700;}
.mxp .mxp-head p{margin:2px 0 0;font-size:.8rem;color:var(--mx-muted);}

/* KPIs: numero serif grande sobre etiqueta pequena (recompensa tipografica) */
.mxp .mxp-kpis{display:grid;gap:10px;grid-template-columns:repeat(5,minmax(0,1fr));margin-top:14px;}
@media (max-width:700px){ .mxp .mxp-kpis{grid-template-columns:repeat(2,minmax(0,1fr));} }
.mxp .mxp-kpi{
    border:1px solid var(--mx-line);border-radius:13px;padding:10px 12px;
    background:var(--mx-warm);display:flex;flex-direction:column;gap:1px;
}
.mxp .mxp-kpi b{font-family:var(--mx-serif);font-size:1.7rem;font-weight:600;line-height:1;}
.mxp .mxp-kpi span{font-size:.68rem;letter-spacing:.07em;text-transform:uppercase;font-weight:700;color:var(--mx-muted);}
.mxp .mxp-kpi.st-ok b{color:var(--mx-ok);}
.mxp .mxp-kpi.st-busy b{color:var(--mx-busy);}
.mxp .mxp-kpi.st-clean b{color:var(--mx-clean);}
.mxp .mxp-kpi.st-warn b{color:var(--mx-warn);}
.mxp .mxp-kpi.st-off b{color:var(--mx-error);}

/* Piso */
.mxp .mxp-piso-head{display:flex;align-items:baseline;gap:10px;margin:2px 0 10px;}
.mxp .mxp-piso-head h2{margin:0;font-family:var(--mx-serif);font-size:1.45rem;font-weight:600;color:var(--mx-ink);}
.mxp .mxp-piso-head small{font-size:.72rem;color:var(--mx-muted);font-weight:600;}
.mxp .mxp-grid{display:grid;gap:8px;grid-template-columns:repeat(auto-fill,minmax(86px,1fr));}

/* Ficha habitacion: cuadrada, numero serif, dot de estado */
.mxp .mxp-room{
    position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;
    aspect-ratio:1/.92;border-radius:13px;text-decoration:none;
    background:var(--mx-warm);border:1px solid var(--mx-line);
    transition:transform .15s var(--mx-ease), box-shadow .15s var(--mx-ease), border-color .15s var(--mx-ease);
}
.mxp .mxp-room:hover{transform:translateY(-2px);box-shadow:0 12px 22px -14px color-mix(in srgb, var(--mx-brand) 55%, transparent);border-color:color-mix(in srgb, var(--mx-gold) 45%, var(--mx-line));}
.mxp .mxp-room b{font-family:var(--mx-serif);font-size:1.45rem;font-weight:600;color:var(--mx-ink);line-height:1;}
.mxp .mxp-room small{font-size:.62rem;letter-spacing:.05em;text-transform:uppercase;font-weight:700;color:var(--mx-muted);max-width:92%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.mxp .mxp-dot{position:absolute;top:8px;right:8px;width:9px;height:9px;border-radius:999px;}
.mxp .st-ok .mxp-dot{background:var(--mx-ok);}
.mxp .st-busy .mxp-dot{background:var(--mx-busy);}
.mxp .st-clean .mxp-dot{background:var(--mx-clean);animation:mxpPulse 2.4s ease-in-out infinite;}
.mxp .st-warn .mxp-dot{background:var(--mx-warn);animation:mxpPulse 2.4s ease-in-out infinite;}
.mxp .st-off .mxp-dot{background:var(--mx-error);}
.mxp .mxp-room.st-busy{background:color-mix(in srgb, var(--mx-busy) 5%, var(--mx-warm));}
.mxp .mxp-room.st-clean{background:color-mix(in srgb, var(--mx-clean) 5%, var(--mx-warm));}
.mxp .mxp-room.st-warn{background:color-mix(in srgb, var(--mx-warn) 6%, var(--mx-warm));}
.mxp .mxp-room.st-off{background:color-mix(in srgb, var(--mx-error) 5%, var(--mx-warm));opacity:.85;}
@keyframes mxpPulse{0%,100%{transform:scale(1);opacity:1;}50%{transform:scale(1.45);opacity:.55;}}

/* Ficha area: ancha, icono dorado + nombre + badge de estado */
.mxp .mxp-areas{display:grid;gap:8px;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-top:8px;}
.mxp .mxp-area{
    display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:13px;text-decoration:none;
    background:var(--mx-warm);border:1px solid var(--mx-line);
    transition:transform .15s var(--mx-ease), box-shadow .15s var(--mx-ease), border-color .15s var(--mx-ease);
}
.mxp .mxp-area:hover{transform:translateY(-2px);box-shadow:0 12px 22px -14px color-mix(in srgb, var(--mx-brand) 55%, transparent);border-color:color-mix(in srgb, var(--mx-gold) 45%, var(--mx-line));}
.mxp .mxp-area-ico{
    width:34px;height:34px;border-radius:10px;flex:none;display:grid;place-items:center;font-size:.9rem;
    background:color-mix(in srgb, var(--mx-gold) 14%, #FFF);color:color-mix(in srgb, var(--mx-gold) 80%, var(--mx-brand));
}
.mxp .mxp-area-txt{min-width:0;flex:1;}
.mxp .mxp-area-txt strong{display:block;font-size:.84rem;font-weight:700;color:var(--mx-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.mxp .mxp-area-txt small{font-size:.68rem;color:var(--mx-muted);font-weight:600;}
.mxp .mxp-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:999px;font-size:.66rem;font-weight:700;white-space:nowrap;}
.mxp .mxp-badge.st-ok{background:var(--mx-ok-soft);color:var(--mx-ok);}
.mxp .mxp-badge.st-clean{background:var(--mx-clean-soft);color:var(--mx-clean);}
.mxp .mxp-badge.st-warn{background:var(--mx-warn-soft);color:var(--mx-warn);}
.mxp .mxp-badge.st-off{background:var(--mx-error-soft);color:var(--mx-error);}
.mxp .mxp-badge.st-busy{background:var(--mx-busy-soft);color:var(--mx-busy);}

/* Leyenda */
.mxp .mxp-leyenda{display:flex;gap:14px;flex-wrap:wrap;font-size:.72rem;color:var(--mx-muted);font-weight:600;}
.mxp .mxp-leyenda i{font-size:.6rem;margin-right:4px;}
.mxp .mxp-leyenda .st-ok i{color:var(--mx-ok);} .mxp .mxp-leyenda .st-busy i{color:var(--mx-busy);}
.mxp .mxp-leyenda .st-clean i{color:var(--mx-clean);} .mxp .mxp-leyenda .st-warn i{color:var(--mx-warn);}
.mxp .mxp-leyenda .st-off i{color:var(--mx-error);}

.mxp .mxp-vacio{padding:30px 14px;text-align:center;color:var(--mx-muted);font-size:.86rem;}
.mxp .mxp-vacio i{display:block;font-size:1.7rem;margin-bottom:8px;color:var(--mx-gold);}

.mxp a:focus-visible{outline:2px solid var(--mx-clean);outline-offset:2px;}

@media (max-width:640px){
    .mxp{padding:12px 10px 90px;}
    .mxp .mxp-grid{grid-template-columns:repeat(auto-fill,minmax(72px,1fr));}
    .mxp .mxp-kpi b{font-size:1.4rem;}
}
@media (prefers-reduced-motion: reduce){
    .mxp .mxp-dot{animation:none!important;}
    .mxp *{transition-duration:.01ms!important;}
}
</style>

<div class="mxp">
    <div class="mxp-shell">

        <section class="mxp-card">
            <div class="mxp-head">
                <div>
                    <h1><i class="fas fa-map" style="color:var(--mx-gold);margin-right:8px;"></i>Mapa del hotel</h1>
                    <p>Habitaciones y áreas piso por piso, con su estado en este momento.</p>
                </div>
            </div>
            <?php if ($hayContenido): ?>
            <div class="mxp-kpis">
                <div class="mxp-kpi st-ok"><b><?= (int)($conteo['disponible'] ?? 0) ?></b><span>Disponibles</span></div>
                <div class="mxp-kpi st-busy"><b><?= (int)($conteo['ocupada'] ?? 0) ?></b><span>Ocupadas</span></div>
                <div class="mxp-kpi st-clean"><b><?= (int)($conteo['limpieza'] ?? 0) ?></b><span>En limpieza</span></div>
                <div class="mxp-kpi st-warn"><b><?= (int)($conteo['mantenimiento'] ?? 0) ?></b><span>Mantenimiento</span></div>
                <div class="mxp-kpi st-off"><b><?= (int)($conteo['cerrada'] ?? 0) ?></b><span>Cerradas</span></div>
            </div>
            <?php endif; ?>
        </section>

        <?php if (!$hayContenido): ?>
            <section class="mxp-card">
                <div class="mxp-vacio">
                    <i class="fas fa-map"></i>
                    <strong style="display:block;color:var(--mx-ink);font-weight:700;">El mapa está vacío</strong>
                    Da de alta habitaciones y áreas y aquí verás todo el hotel de un vistazo.
                </div>
            </section>
        <?php endif; ?>

        <?php foreach ($porPiso as $piso => $grupo): ?>
            <?php
            $habsPiso = $grupo['habitaciones'] ?? [];
            $areasPiso = $grupo['areas'] ?? [];
            $totalPiso = count($habsPiso) + count($areasPiso);
            ?>
            <section class="mxp-card">
                <div class="mxp-piso-head">
                    <h2><?= $piso === 0 ? 'Planta baja' : 'Piso ' . (int)$piso ?></h2>
                    <small><?= $totalPiso ?> espacio<?= $totalPiso === 1 ? '' : 's' ?></small>
                </div>
                <?php if (!empty($habsPiso)): ?>
                <div class="mxp-grid">
                    <?php foreach ($habsPiso as $h): ?>
                        <?php $em = $estadoMeta[$h['estado'] ?? 'disponible'] ?? $estadoMeta['disponible']; ?>
                        <a class="mxp-room <?= $em['clase'] ?>" href="<?= url('habitaciones/' . (int)$h['id']) ?>"
                           title="Habitación <?= mxp_safe($h['numero']) ?> · <?= mxp_safe($em['label']) ?>">
                            <span class="mxp-dot" aria-hidden="true"></span>
                            <b><?= mxp_safe($h['numero']) ?></b>
                            <small><?= mxp_safe($em['label']) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($areasPiso)): ?>
                <div class="mxp-areas">
                    <?php foreach ($areasPiso as $a): ?>
                        <?php
                        $em = $estadoMeta[$a['estado'] ?? 'disponible'] ?? $estadoMeta['disponible'];
                        $tm = $tiposArea[$a['tipo'] ?? 'otra'] ?? ['label' => 'Área', 'icono' => 'fa-location-dot'];
                        ?>
                        <a class="mxp-area" href="<?= url('areas/' . (int)$a['id']) ?>" title="<?= mxp_safe($a['nombre']) ?> · <?= mxp_safe($em['label']) ?>">
                            <span class="mxp-area-ico"><i class="fas <?= mxp_safe($tm['icono']) ?>"></i></span>
                            <span class="mxp-area-txt">
                                <strong><?= mxp_safe($a['nombre']) ?></strong>
                                <small><?= mxp_safe($tm['label']) ?></small>
                            </span>
                            <span class="mxp-badge <?= $em['clase'] ?>"><i class="fas <?= $em['icono'] ?>"></i> <?= mxp_safe($em['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <?php if (!empty($exterior)): ?>
            <section class="mxp-card">
                <div class="mxp-piso-head">
                    <h2>Exterior y planta baja</h2>
                    <small><?= count($exterior) ?> área<?= count($exterior) === 1 ? '' : 's' ?></small>
                </div>
                <div class="mxp-areas" style="margin-top:0;">
                    <?php foreach ($exterior as $a): ?>
                        <?php
                        $em = $estadoMeta[$a['estado'] ?? 'disponible'] ?? $estadoMeta['disponible'];
                        $tm = $tiposArea[$a['tipo'] ?? 'otra'] ?? ['label' => 'Área', 'icono' => 'fa-location-dot'];
                        ?>
                        <a class="mxp-area" href="<?= url('areas/' . (int)$a['id']) ?>" title="<?= mxp_safe($a['nombre']) ?> · <?= mxp_safe($em['label']) ?>">
                            <span class="mxp-area-ico"><i class="fas <?= mxp_safe($tm['icono']) ?>"></i></span>
                            <span class="mxp-area-txt">
                                <strong><?= mxp_safe($a['nombre']) ?></strong>
                                <small><?= mxp_safe($tm['label']) ?></small>
                            </span>
                            <span class="mxp-badge <?= $em['clase'] ?>"><i class="fas <?= $em['icono'] ?>"></i> <?= mxp_safe($em['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($hayContenido): ?>
        <section class="mxp-card">
            <div class="mxp-leyenda">
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
