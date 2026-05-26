<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
// ── Valores seguros ────────────────────────────────────────────────────────
$estadisticas = $estadisticas ?? [];
$total        = intval($estadisticas['total_mantenimientos']    ?? 0);
$completados  = intval($estadisticas['completados']             ?? 0);
$en_proceso   = intval($estadisticas['en_proceso']              ?? 0);
$cancelados   = intval($estadisticas['cancelados']              ?? 0);
$programados  = intval($estadisticas['programados']             ?? 0);
$costo_total  = floatval($estadisticas['costo_total']           ?? 0);
$costo_prom   = floatval($estadisticas['costo_promedio']        ?? 0);
$dur_prom_h   = floatval($estadisticas['duracion_promedio_horas'] ?? 0);

$tasa = $total > 0 ? round(($completados / $total) * 100) : 0;

$porTipo               = $porTipo               ?? [];
$porPrioridad          = $porPrioridad          ?? [];
$habitacionesMasMant   = $habitacionesMasMantenimiento ?? [];
$ultimosMantenimientos = $ultimosMantenimientos ?? [];
$tendenciaMensual      = $tendenciaMensual      ?? [];
$realizadoPorTop       = $realizadoPorTop       ?? [];

$tipoConfig = [
    'preventivo'       => ['color'=>'#5C7A4E', 'bg'=>'#EBF2E6', 'icon'=>'fa-shield-alt',            'label'=>'Preventivo'],
    'correctivo'       => ['color'=>'#D97706', 'bg'=>'#FEF3C7', 'icon'=>'fa-wrench',                'label'=>'Correctivo'],
    'emergencia'       => ['color'=>'#DC2626', 'bg'=>'#FEE2E2', 'icon'=>'fa-exclamation-triangle',   'label'=>'Emergencia'],
    'limpieza_profunda'=> ['color'=>'#0891B2', 'bg'=>'#CFFAFE', 'icon'=>'fa-broom',                 'label'=>'Lim. Profunda'],
];
$prioridadColor = [
    'baja'    => ['dot'=>'#86EFAC', 'bg'=>'#F0FDF4', 'text'=>'#166534'],
    'media'   => ['dot'=>'#FCD34D', 'bg'=>'#FFFBEB', 'text'=>'#92400E'],
    'alta'    => ['dot'=>'#FCA5A5', 'bg'=>'#FFF1F1', 'text'=>'#991B1B'],
    'urgente' => ['dot'=>'#C084FC', 'bg'=>'#FAF5FF', 'text'=>'#6B21A8'],
];
$estadoConfig = [
    'en_proceso' => ['bg'=>'#DBEAFE','text'=>'#1D4ED8','dot'=>'#3B82F6','label'=>'En Proceso'],
    'completado' => ['bg'=>'#DCFCE7','text'=>'#15803D','dot'=>'#22C55E','label'=>'Completado'],
    'cancelado'  => ['bg'=>'#FEE2E2','text'=>'#B91C1C','dot'=>'#EF4444','label'=>'Cancelado'],
    'programado' => ['bg'=>'#F1F5F9','text'=>'#475569','dot'=>'#94A3B8','label'=>'Programado'],
];

$maxHab   = !empty($habitacionesMasMant) ? max(array_column($habitacionesMasMant, 'total_mantenimientos')) : 1;
$maxPrio  = !empty($porPrioridad)        ? max(array_column($porPrioridad, 'cantidad')) : 1;

$tiposLabels = array_map(fn($t) => $tipoConfig[$t['tipo_mantenimiento'] ?? $t['tipo'] ?? '']['label'] ?? ucfirst($t['tipo_mantenimiento'] ?? $t['tipo'] ?? ''), $porTipo);
$tiposData   = array_column($porTipo, 'cantidad');
$tiposColors = array_map(fn($t) => $tipoConfig[$t['tipo_mantenimiento'] ?? $t['tipo'] ?? '']['color'] ?? '#888', $porTipo);
$mesLabels   = array_column($tendenciaMensual, 'mes_label');
$mesData     = array_map('intval', array_column($tendenciaMensual, 'cantidad'));
?>
<style>
:root{
    --gd:#2D4A2D;--gm:#3D6B3D;--gl:#5C7A4E;--gs:#8EB07A;--gp:#EBF2E6;
    --bg:#F2F4EF;--wh:#FFFFFF;--bd:#E2E8DC;
    --t1:#1A2A1A;--t2:#4A6145;--t3:#8AA080;
    --r:10px;
    --ss:0 1px 3px rgba(45,74,45,.07),0 1px 2px rgba(45,74,45,.05);
    --sm:0 4px 14px rgba(45,74,45,.09);
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);font-family:'Segoe UI',system-ui,sans-serif;color:var(--t1)}
.rpt{width:100%;padding:1.25rem 1.5rem}

/* top bar */
.rpt-bar{background:linear-gradient(135deg,var(--gd),var(--gm));border-radius:var(--r);padding:1.25rem 1.75rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.25rem;box-shadow:var(--sm);position:relative;overflow:hidden}
.rpt-bar::after{content:'';position:absolute;right:-30px;top:-30px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.04)}
.rpt-bar h1{color:#fff;font-size:1.2rem;font-weight:700;display:flex;align-items:center;gap:.5rem}
.rpt-bar p{color:rgba(255,255,255,.65);font-size:.78rem;margin-top:.2rem}
.btns-top{display:flex;gap:.5rem;z-index:1}
.btn-top{display:inline-flex;align-items:center;gap:.4rem;padding:.42rem .95rem;border-radius:7px;font-size:.78rem;font-weight:600;cursor:pointer;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.18);text-decoration:none;transition:background .2s}
.btn-top:hover{background:rgba(255,255,255,.22)}

/* filtros */
.rpt-f{background:var(--wh);border-radius:var(--r);border:1px solid var(--bd);padding:.875rem 1.25rem;display:flex;flex-wrap:wrap;gap:.875rem;align-items:flex-end;margin-bottom:1.25rem;box-shadow:var(--ss)}
.fg{display:flex;flex-direction:column;gap:.28rem;flex:1;min-width:160px}
.fg label{font-size:.68rem;font-weight:700;color:var(--t2);text-transform:uppercase;letter-spacing:.05em}
.fg input,.fg select{padding:.45rem .7rem;border:1.5px solid var(--bd);border-radius:7px;font-size:.83rem;color:var(--t1);background:var(--bg);transition:border-color .2s}
.fg input:focus,.fg select:focus{outline:none;border-color:var(--gl);background:#fff}
.btn-f{display:inline-flex;align-items:center;gap:.4rem;padding:.48rem 1.25rem;border-radius:7px;font-size:.83rem;font-weight:700;background:var(--gl);color:#fff;border:none;cursor:pointer;white-space:nowrap;transition:background .15s}
.btn-f:hover{background:var(--gm)}

/* kpi */
.kpi-grid{display:grid;gap:.875rem;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));margin-bottom:1.25rem}
.kpi{background:var(--wh);border-radius:var(--r);border:1px solid var(--bd);padding:1.1rem 1.25rem;box-shadow:var(--ss);position:relative;overflow:hidden;animation:kpiin .45s ease both;transition:transform .2s,box-shadow .2s}
.kpi:hover{transform:translateY(-2px);box-shadow:var(--sm)}
.kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--kl,var(--gl));border-radius:var(--r) var(--r) 0 0}
@keyframes kpiin{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.kpi:nth-child(1){animation-delay:.04s}.kpi:nth-child(2){animation-delay:.08s}.kpi:nth-child(3){animation-delay:.12s}
.kpi:nth-child(4){animation-delay:.16s}.kpi:nth-child(5){animation-delay:.20s}.kpi:nth-child(6){animation-delay:.24s}
.kpi-ic{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem;margin-bottom:.65rem}
.kpi-v{font-size:1.75rem;font-weight:800;line-height:1;color:var(--t1)}
.kpi-l{font-size:.68rem;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.05em;margin-top:.28rem}
.kpi-s{font-size:.7rem;color:var(--t3);margin-top:.2rem}

/* card */
.card{background:var(--wh);border-radius:var(--r);border:1px solid var(--bd);box-shadow:var(--ss);overflow:hidden}
.ch{display:flex;align-items:center;gap:.55rem;padding:.85rem 1.25rem;border-bottom:1px solid var(--bd)}
.ch h3{font-size:.88rem;font-weight:700;color:var(--t1)}
.ci{width:28px;height:28px;border-radius:7px;font-size:.75rem;display:flex;align-items:center;justify-content:center;background:var(--gp);color:var(--gl)}
.ch-extra{margin-left:auto;font-size:.72rem;color:var(--t3)}

/* layouts */
.row2{display:grid;grid-template-columns:1fr 2fr;gap:.875rem;margin-bottom:.875rem}
.row2x{display:grid;grid-template-columns:1fr 1fr;gap:.875rem;margin-bottom:.875rem}

/* gauge */
.gw{display:flex;flex-direction:column;align-items:center;padding:1.25rem 1rem .75rem}
.gr{width:120px;height:120px;position:relative}
.gs-svg{width:100%;height:100%;transform:rotate(-90deg)}
.gb{fill:none;stroke:#EBF2E6;stroke-width:11}
.gf{fill:none;stroke:var(--gl);stroke-width:11;stroke-linecap:round;transition:stroke-dashoffset 1.1s ease}
.gt{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.gp2{font-size:1.5rem;font-weight:800;color:var(--gd);line-height:1}
.gs2{font-size:.6rem;color:var(--t3);font-weight:600}
.gl2{font-size:.72rem;font-weight:700;color:var(--t2);margin-top:.6rem}
.gm{display:flex;gap:1.2rem;margin-top:.875rem;justify-content:center}
.gm .v{font-size:.95rem;font-weight:800}
.gm .l{font-size:.62rem;color:var(--t3);text-transform:uppercase;letter-spacing:.04em}

/* prio */
.prio-list{padding:.6rem 1.1rem .9rem;display:flex;flex-direction:column;gap:.55rem}
.prio-row{display:flex;align-items:center;gap:.65rem}
.prio-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.prio-name{font-size:.78rem;font-weight:600;color:var(--t2);flex:1;text-transform:capitalize}
.prio-bg{flex:2;height:5px;background:#EBF2E6;border-radius:99px;overflow:hidden}
.prio-bar{height:100%;border-radius:99px;transition:width 1s ease}
.prio-cnt{font-size:.78rem;font-weight:700;color:var(--t1);min-width:24px;text-align:right}

/* chips */
.chip{display:inline-flex;align-items:center;gap:.28rem;padding:.18rem .55rem;border-radius:6px;font-size:.7rem;font-weight:700}
.pill{display:inline-flex;align-items:center;gap:.28rem;padding:.18rem .55rem;border-radius:99px;font-size:.68rem;font-weight:700}
.sd{width:5px;height:5px;border-radius:50%}

/* tabla */
.rtbl{width:100%;border-collapse:collapse}
.rtbl thead th{background:#F7FAF5;font-size:.68rem;font-weight:700;color:var(--t2);text-transform:uppercase;letter-spacing:.05em;padding:.65rem 1rem;text-align:left;border-bottom:1px solid var(--bd);white-space:nowrap}
.rtbl tbody tr{transition:background .12s}
.rtbl tbody tr:hover{background:#F7FAF5}
.rtbl tbody td{padding:.7rem 1rem;font-size:.8rem;border-bottom:1px solid var(--bd)}
.rtbl tbody tr:last-child td{border-bottom:none}
.hab-p{display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:6px;background:var(--gp);color:var(--gd);font-size:.75rem;font-weight:800;white-space:nowrap}

/* hab cards */
.hab-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:.75rem;padding:1.1rem}
.hc{padding:.875rem;border-radius:8px;border:1.5px solid var(--bd);background:#FAFCF8;transition:border-color .2s,transform .2s}
.hc:hover{border-color:var(--gs);transform:translateY(-2px)}
.hct{display:flex;align-items:center;justify-content:space-between;margin-bottom:.45rem}
.hcn{font-size:1rem;font-weight:800;color:var(--gd)}
.hctp{font-size:.68rem;color:var(--t3);font-weight:600;text-transform:capitalize;margin-top:.1rem}
.hcc{font-size:1.2rem;font-weight:800;color:var(--gl)}
.hccl{font-size:.62rem;color:var(--t3)}
.hbb{width:100%;height:4px;background:#DDE8D6;border-radius:99px;overflow:hidden;margin:.45rem 0}
.hbf{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--gs),var(--gd));transition:width 1s ease}
.hst{display:flex;gap:.875rem}
.hs .v{font-size:.78rem;font-weight:700}
.hs .l{font-size:.6rem;color:var(--t3);text-transform:uppercase;letter-spacing:.03em}

/* realizadores */
.rl{padding:.7rem 1.25rem;display:flex;flex-direction:column;gap:.5rem}
.rr{display:flex;align-items:center;gap:.65rem}
.ra{width:30px;height:30px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,var(--gs),var(--gd));color:#fff;font-size:.7rem;font-weight:700;display:flex;align-items:center;justify-content:center}
.rn{font-size:.8rem;font-weight:600;color:var(--t1);flex:1}
.rc{font-size:.78rem;font-weight:700;color:var(--t2);white-space:nowrap}

/* empty */
.empty{text-align:center;padding:2.5rem 1.5rem}
.empty i{font-size:2rem;color:#C8D8BF;margin-bottom:.6rem;display:block}
.empty p{color:var(--t3);font-size:.85rem}

@media(max-width:768px){.row2,.row2x{grid-template-columns:1fr}.rpt-bar{flex-direction:column;align-items:flex-start}.btns-top{width:100%}}
@media print{body{background:#fff!important}.btns-top,.rpt-f{display:none!important}.card,.kpi{box-shadow:none!important;border:1px solid #ddd!important}}
</style>

<div class="rpt">

    <div class="rpt-bar">
        <div>
            <h1><i class="fas fa-hard-hat"></i> Reporte de Mantenimiento</h1>
            <p><?= date('d M Y', strtotime($fecha_inicio)) ?> → <?= date('d M Y', strtotime($fecha_fin)) ?></p>
        </div>
        <div class="btns-top">
            <a href="<?= url('reportes') ?>" class="btn-top"><i class="fas fa-arrow-left"></i> Volver</a>
            <button onclick="window.print()" class="btn-top"><i class="fas fa-print"></i> Imprimir</button>
        </div>
    </div>

    <div class="rpt-f">
        <form method="GET" action="<?= url('reportes/mantenimiento') ?>" style="display:contents;">
            <div class="fg">
                <label>Fecha inicio</label>
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div class="fg">
                <label>Fecha fin</label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div class="fg" style="max-width:175px;">
                <label>Tipo</label>
                <select name="tipo">
                    <option value="">Todos</option>
                    <?php foreach ($tipoConfig as $k=>$tc): ?>
                    <option value="<?= $k ?>" <?= (($_GET['tipo']??'')===$k)?'selected':'' ?>><?= $tc['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-f"><i class="fas fa-filter"></i> Filtrar</button>
        </form>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid">
        <div class="kpi" style="--kl:#5C7A4E;">
            <div class="kpi-ic" style="background:#EBF2E6;color:#3D6B3D;"><i class="fas fa-clipboard-list"></i></div>
            <div class="kpi-v"><?= number_format($total) ?></div>
            <div class="kpi-l">Total</div><div class="kpi-s">Período analizado</div>
        </div>
        <div class="kpi" style="--kl:#22C55E;">
            <div class="kpi-ic" style="background:#DCFCE7;color:#15803D;"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-v"><?= number_format($completados) ?></div>
            <div class="kpi-l">Completados</div><div class="kpi-s"><?= $tasa ?>% del total</div>
        </div>
        <div class="kpi" style="--kl:#3B82F6;">
            <div class="kpi-ic" style="background:#DBEAFE;color:#1D4ED8;"><i class="fas fa-spinner"></i></div>
            <div class="kpi-v"><?= number_format($en_proceso) ?></div>
            <div class="kpi-l">En Proceso</div><div class="kpi-s">Activos ahora</div>
        </div>
        <div class="kpi" style="--kl:#8B5CF6;">
            <div class="kpi-ic" style="background:#EDE9FE;color:#6D28D9;"><i class="fas fa-calendar-alt"></i></div>
            <div class="kpi-v"><?= number_format($programados) ?></div>
            <div class="kpi-l">Programados</div><div class="kpi-s">Pendientes de iniciar</div>
        </div>
        <div class="kpi" style="--kl:#059669;">
            <div class="kpi-ic" style="background:#D1FAE5;color:#047857;"><i class="fas fa-dollar-sign"></i></div>
            <div class="kpi-v">$<?= number_format($costo_total, 0) ?></div>
            <div class="kpi-l">Costo Total</div><div class="kpi-s">Prom. $<?= number_format($costo_prom, 0) ?></div>
        </div>
        <div class="kpi" style="--kl:#F59E0B;">
            <div class="kpi-ic" style="background:#FEF3C7;color:#B45309;"><i class="fas fa-clock"></i></div>
            <div class="kpi-v">
                <?php if ($dur_prom_h>=24): ?><?= round($dur_prom_h/24,1) ?><span style="font-size:.95rem;font-weight:600;">d</span>
                <?php else: ?><?= round($dur_prom_h,1) ?><span style="font-size:.95rem;font-weight:600;">h</span><?php endif; ?>
            </div>
            <div class="kpi-l">Duración Prom.</div><div class="kpi-s">Por trabajo completado</div>
        </div>
    </div>

    <!-- Gauge + Tendencia -->
    <div class="row2">
        <div class="card" style="display:flex;flex-direction:column;">
            <div class="ch"><div class="ci"><i class="fas fa-chart-pie"></i></div><h3>Tasa de Completitud</h3></div>
            <div class="gw">
                <div class="gr">
                    <svg class="gs-svg" viewBox="0 0 100 100">
                        <circle class="gb" cx="50" cy="50" r="42"/>
                        <circle class="gf" cx="50" cy="50" r="42"
                            stroke-dasharray="<?= round(2*M_PI*42,2) ?>"
                            stroke-dashoffset="<?= round(2*M_PI*42*(1-$tasa/100),2) ?>" id="gaugeFill"/>
                    </svg>
                    <div class="gt"><span class="gp2"><?= $tasa ?>%</span><span class="gs2">completado</span></div>
                </div>
                <div class="gl2">Efectividad del período</div>
                <div class="gm">
                    <div><div class="v" style="color:#22C55E;"><?= $completados ?></div><div class="l">Completados</div></div>
                    <div><div class="v" style="color:#EF4444;"><?= $cancelados ?></div><div class="l">Cancelados</div></div>
                    <div><div class="v" style="color:#3B82F6;"><?= $en_proceso ?></div><div class="l">En proceso</div></div>
                </div>
            </div>
            <?php if (!empty($porPrioridad)): ?>
            <div style="border-top:1px solid var(--bd);padding:.55rem 1.1rem .3rem;">
                <p style="font-size:.65rem;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.05em;"><i class="fas fa-flag" style="margin-right:.3rem;"></i>Por Prioridad</p>
            </div>
            <div class="prio-list">
                <?php foreach ($porPrioridad as $p):
                    $pc = $prioridadColor[$p['prioridad']] ?? ['dot'=>'#ccc','bg'=>'#f9f9f9','text'=>'#333'];
                    $pp = $maxPrio>0 ? round(($p['cantidad']/$maxPrio)*100) : 0;
                ?>
                <div class="prio-row">
                    <div class="prio-dot" style="background:<?= $pc['dot'] ?>;"></div>
                    <span class="prio-name"><?= ucfirst($p['prioridad']) ?></span>
                    <div class="prio-bg"><div class="prio-bar" style="width:<?= $pp ?>%;background:<?= $pc['dot'] ?>;"></div></div>
                    <span class="prio-cnt"><?= $p['cantidad'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="ch"><div class="ci"><i class="fas fa-chart-bar"></i></div><h3>Tendencia Mensual</h3></div>
            <div style="padding:1rem;height:265px;position:relative;">
                <?php if (!empty($tendenciaMensual)): ?><canvas id="chartTend"></canvas>
                <?php else: ?><div class="empty"><i class="fas fa-chart-bar"></i><p>Sin datos de tendencia</p></div><?php endif; ?>
            </div>
            <?php if (!empty($porTipo)): ?>
            <div style="padding:.65rem 1.25rem;border-top:1px solid var(--bd);display:flex;flex-wrap:wrap;gap:.4rem;">
                <?php foreach ($porTipo as $t):
                    $tkey = $t['tipo_mantenimiento'] ?? $t['tipo'] ?? ''; $tc = $tipoConfig[$tkey] ?? ['color'=>'#888','label'=>ucfirst($tkey)];
                ?>
                <span style="display:inline-flex;align-items:center;gap:.3rem;background:#F7FAF5;padding:.25rem .65rem;border-radius:99px;border:1px solid var(--bd);font-size:.72rem;">
                    <span style="width:7px;height:7px;border-radius:50%;background:<?= $tc['color'] ?>;display:inline-block;"></span>
                    <span style="font-weight:600;color:var(--t2);"><?= $tc['label'] ?></span>
                    <span style="font-weight:800;color:var(--t1);"><?= $t['cantidad'] ?></span>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dona + Realizadores -->
    <?php if (!empty($porTipo) || !empty($realizadoPorTop)): ?>
    <div class="row2x">
        <?php if (!empty($porTipo)): ?>
        <div class="card">
            <div class="ch"><div class="ci"><i class="fas fa-tools"></i></div><h3>Distribución por Tipo</h3></div>
            <div style="padding:1.1rem;display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;">
                <div style="width:150px;height:150px;flex-shrink:0;position:relative;"><canvas id="chartDonut"></canvas></div>
                <div style="flex:1;min-width:130px;">
                    <?php foreach ($porTipo as $t):
                        $tkey = $t['tipo_mantenimiento'] ?? $t['tipo'] ?? ''; $tc = $tipoConfig[$tkey] ?? ['color'=>'#888','label'=>ucfirst($tkey)];
                        $pct = $total>0 ? round(($t['cantidad']/$total)*100) : 0; // pct ok
                    ?>
                    <div style="display:flex;align-items:center;gap:.55rem;margin-bottom:.6rem;">
                        <span style="width:9px;height:9px;border-radius:2px;background:<?= $tc['color'] ?>;flex-shrink:0;"></span>
                        <span style="font-size:.76rem;font-weight:600;color:var(--t2);flex:1;"><?= $tc['label'] ?></span>
                        <span style="font-size:.76rem;font-weight:800;color:var(--t1);"><?= $t['cantidad'] ?></span>
                        <span style="font-size:.68rem;color:var(--t3);">(<?= $pct ?>%)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="card">
            <div class="ch"><div class="ci" style="background:#EDE9FE;color:#6D28D9;"><i class="fas fa-user-cog"></i></div><h3>Realizados por</h3></div>
            <?php if (!empty($realizadoPorTop)): ?>
            <div class="rl">
                <?php foreach ($realizadoPorTop as $r): $ini=strtoupper(substr($r['realizado_por']??'NN',0,2)); ?>
                <div class="rr">
                    <div class="ra"><?= $ini ?></div>
                    <span class="rn"><?= htmlspecialchars($r['realizado_por']??'Sin asignar') ?></span>
                    <span class="rc"><?= $r['cantidad'] ?> trabajos</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?><div class="empty"><i class="fas fa-user-slash"></i><p>Sin datos de responsables</p></div><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Habitaciones top -->
    <?php if (!empty($habitacionesMasMant)): ?>
    <div class="card" style="margin-bottom:.875rem;">
        <div class="ch">
            <div class="ci" style="background:#FEF3C7;color:#B45309;"><i class="fas fa-door-open"></i></div>
            <h3>Habitaciones con Más Mantenimientos</h3>
            <span class="ch-extra">Top <?= count($habitacionesMasMant) ?></span>
        </div>
        <div class="hab-grid">
            <?php foreach ($habitacionesMasMant as $h):
                $pct3 = $maxHab>0 ? round(($h['total_mantenimientos']/$maxHab)*100) : 0;
                $tas3 = $h['total_mantenimientos']>0 ? round((intval($h['completados'] ?? 0)/max(intval($h['total_mantenimientos']),1))*100) : 0;
                $otr  = intval($h['total_mantenimientos'] ?? 0)-intval($h['completados'] ?? 0);
            ?>
            <div class="hc">
                <div class="hct">
                    <div><div class="hcn">Hab. <?= htmlspecialchars($h['numero']) ?></div><div class="hctp"><?= ucfirst($h['tipo']??'') ?></div></div>
                    <div style="text-align:right;"><div class="hcc"><?= $h['total_mantenimientos'] ?></div><div class="hccl">trabajos</div></div>
                </div>
                <div class="hbb"><div class="hbf" style="width:<?= $pct3 ?>%;"></div></div>
                <div class="hst">
                    
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Registro -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="ch">
            <div class="ci" style="background:#FEE2E2;color:#B91C1C;"><i class="fas fa-list-ul"></i></div>
            <h3>Registro de Mantenimientos</h3>
            <?php if (!empty($ultimosMantenimientos)): ?>
            <span class="ch-extra"><?= count($ultimosMantenimientos) ?> registros</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($ultimosMantenimientos)): ?>
        <div style="overflow-x:auto;">
            <table class="rtbl">
                <thead><tr>
                    <th>Habitación</th><th>Tipo</th><th>Motivo / Descripción</th>
                    <th>Prioridad</th><th>Inicio</th><th>Fin</th>
                    <th>Duración</th><th>Realizado por</th><th>Costo</th><th>Estado</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($ultimosMantenimientos as $m):
                        $tc  = $tipoConfig[$m['tipo_mantenimiento']]  ?? ['color'=>'#888','bg'=>'#f9f9f9','icon'=>'fa-tools','label'=>ucfirst($m['tipo_mantenimiento']??'')];
                        $prc = $prioridadColor[$m['prioridad']]        ?? ['dot'=>'#ccc','bg'=>'#f9f9f9','text'=>'#555'];
                        $ec  = $estadoConfig[$m['estado']]             ?? ['bg'=>'#F1F5F9','text'=>'#475569','dot'=>'#94A3B8','label'=>ucfirst($m['estado']??'')];

                        $dur = '—';
                        if (!empty($m['fecha_inicio']) && !empty($m['fecha_fin'])) {
                            $dh = (strtotime($m['fecha_fin']) - strtotime($m['fecha_inicio'])) / 3600;
                            if ($dh>=24)     $dur = round($dh/24,1).' días';
                            elseif ($dh>=1)  $dur = round($dh,1).' h';
                            else             $dur = round($dh*60).' min';
                        } elseif (($m['estado']==='en_proceso') && !empty($m['fecha_inicio'])) {
                            $dh = (time()-strtotime($m['fecha_inicio']))/3600;
                            $dur = round($dh,1).'h <span style="color:#3B82F6;font-size:.62rem;">en curso</span>';
                        }
                        $resp = !empty($m['realizado_por']) ? $m['realizado_por'] : ($m['usuario_nombre'] ?? '—');
                    ?>
                    <tr>
                        <td><span class="hab-p"><?= htmlspecialchars($m['habitacion_numero']??'—') ?></span></td>
                        <td><span class="chip" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;"><i class="fas <?= $tc['icon'] ?>"></i><?= $tc['label'] ?></span></td>
                        <td style="max-width:200px;">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;" title="<?= htmlspecialchars($m['motivo']??'') ?>">
                                <?= htmlspecialchars($m['motivo']??'—') ?>
                            </div>
                            <?php if (!empty($m['descripcion'])): ?>
                            <div style="font-size:.68rem;color:var(--t3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;">
                                <?= htmlspecialchars(mb_substr($m['descripcion'],0,60)) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="chip" style="background:<?= $prc['bg'] ?>;color:<?= $prc['text'] ?>;"><?= ucfirst($m['prioridad']??'—') ?></span></td>
                        <td style="color:var(--t2);white-space:nowrap;font-size:.78rem;"><?= !empty($m['fecha_inicio']) ? date('d/m/y H:i',strtotime($m['fecha_inicio'])) : '—' ?></td>
                        <td style="color:var(--t2);white-space:nowrap;font-size:.78rem;"><?= !empty($m['fecha_fin']) ? date('d/m/y H:i',strtotime($m['fecha_fin'])) : '—' ?></td>
                        <td style="white-space:nowrap;font-size:.78rem;"><?= $dur ?></td>
                        <td style="color:var(--t2);font-size:.78rem;white-space:nowrap;"><?= htmlspecialchars($resp) ?></td>
                        <td style="font-weight:700;font-size:.8rem;white-space:nowrap;"><?= isset($m['costo'])&&$m['costo']!==null ? '$'.number_format($m['costo'],2) : '—' ?></td>
                        <td><span class="pill" style="background:<?= $ec['bg'] ?>;color:<?= $ec['text'] ?>;"><span class="sd" style="background:<?= $ec['dot'] ?>;"></span><?= $ec['label'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty"><i class="fas fa-tools"></i><p>No hay registros en el período seleccionado</p></div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family="'Segoe UI',system-ui,sans-serif";
<?php if (!empty($porTipo)): ?>
(function(){
    const c=document.getElementById('chartDonut'); if(!c) return;
    new Chart(c,{type:'doughnut',data:{labels:<?= json_encode($tiposLabels) ?>,datasets:[{data:<?= json_encode($tiposData) ?>,backgroundColor:<?= json_encode($tiposColors) ?>,borderWidth:2,borderColor:'#fff',hoverOffset:5}]},options:{cutout:'65%',plugins:{legend:{display:false}}}});
})();
<?php endif; ?>
<?php if (!empty($tendenciaMensual)): ?>
(function(){
    const c=document.getElementById('chartTend'); if(!c) return;
    new Chart(c,{type:'bar',data:{labels:<?= json_encode($mesLabels) ?>,datasets:[{label:'Mantenimientos',data:<?= json_encode($mesData) ?>,backgroundColor:'rgba(92,122,78,.75)',borderWidth:0,borderRadius:5,hoverBackgroundColor:'#8EB07A'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:{size:11},color:'#8AA080'}},y:{grid:{color:'#EBF2E6'},ticks:{font:{size:11},color:'#8AA080',stepSize:1},beginAtZero:true}}}});
})();
<?php endif; ?>
document.addEventListener('DOMContentLoaded',function(){
    const f=document.getElementById('gaugeFill');
    if(f){const c=parseFloat(f.getAttribute('stroke-dasharray'));f.style.strokeDashoffset=c*(1-<?= $tasa ?>/100);}
});
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>