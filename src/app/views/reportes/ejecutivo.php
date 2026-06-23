<?php
if (!function_exists('exec_safe')) {
    function exec_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('exec_money')) {
    function exec_money($value): string
    {
        if (function_exists('format_money')) {
            return format_money((float)($value ?? 0));
        }

        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('exec_num')) {
    function exec_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('exec_pct')) {
    function exec_pct($value): string
    {
        return number_format((float)($value ?? 0), 1) . '%';
    }
}

if (!function_exists('exec_date')) {
    function exec_date($value, bool $withTime = false): string
    {
        $time = strtotime((string)($value ?? ''));
        if (!$time) {
            return '-';
        }

        return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $time);
    }
}

$reporte = is_array($reporte ?? null) ? $reporte : [];
$filtros = is_array($reporte['filtros'] ?? null) ? $reporte['filtros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$operacion = is_array($reporte['operacion'] ?? null) ? $reporte['operacion'] : [];
$finanzas = is_array($reporte['finanzas'] ?? null) ? $reporte['finanzas'] : [];
$inventario = is_array($reporte['inventario'] ?? null) ? $reporte['inventario'] : [];
$personal = is_array($reporte['personal'] ?? null) ? $reporte['personal'] : [];
$documentos = is_array($reporte['documentos'] ?? null) ? $reporte['documentos'] : [];
$alertas = is_array($reporte['alertas'] ?? null) ? $reporte['alertas'] : [];
$totalesAlertas = is_array($reporte['totales_alertas'] ?? null) ? $reporte['totales_alertas'] : [];
$schemaOk = !empty($reporte['schema_ok']);

$periodos = ['hoy' => 'Hoy', '7d' => '7 dias', '30d' => '30 dias', 'mes' => 'Mes', 'custom' => 'Custom'];
$areas = ['todas' => 'Todas', 'operacion' => 'Operacion', 'finanzas' => 'Finanzas', 'inventario' => 'Inventario', 'personal' => 'Personal', 'documentos' => 'Documentos'];
$severidades = ['todas' => 'Todas', 'ok' => 'OK', 'warning' => 'Warnings', 'error' => 'Errores'];
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Manrope:wght@400;500;600;700&display=swap');
</style><style>
.exec-page{--exec-brand:var(--brand-primary,#1B2746);--exec-brand-2:var(--brand-secondary,#0F172A);--exec-accent:var(--brand-accent,#BD9441);--exec-text:var(--brand-text,#172033);--exec-muted:var(--brand-muted,#64748B);--exec-border:var(--brand-border,#E5E7EB);--exec-soft:color-mix(in srgb,var(--exec-brand) 5%,#F8FAFC);--exec-accent-soft:color-mix(in srgb,var(--exec-accent) 12%,#FFFFFF);max-width:1440px;margin:0 auto;padding:24px;color:var(--exec-text)}
.exec-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px;padding-bottom:16px;border-bottom:1px solid var(--exec-border)}
.exec-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:var(--exec-muted);font-weight:700}
.exec-title{margin:5px 0 7px;color:var(--exec-brand-2);font-family:'Cormorant Garamond',Georgia,serif;font-size:32px;line-height:1.08;font-weight:700;letter-spacing:0}
.exec-subtitle{margin:0;max-width:820px;color:var(--exec-muted);font-size:14px;line-height:1.5}
.exec-actions{display:flex;gap:9px;flex-wrap:wrap;justify-content:flex-end}
.exec-btn,.exec-badge{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:38px;padding:9px 12px;border-radius:8px;border:1px solid var(--exec-border);background:#fff;color:var(--exec-text);font-size:13px;font-weight:700;text-decoration:none}
.exec-btn.primary{background:var(--exec-brand);border-color:var(--exec-brand);color:var(--brand-action-text,#fff)}
.exec-badge{background:var(--exec-accent-soft);border-color:color-mix(in srgb,var(--exec-accent) 34%,#fff);color:var(--exec-brand)}
.exec-filter{margin-bottom:16px;padding:14px;border:1px solid var(--exec-border);border-radius:8px;background:#fff}
.exec-filter-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:10px;align-items:end}
.exec-field label{display:block;margin-bottom:6px;color:var(--exec-muted);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.exec-field input,.exec-field select{width:100%;height:38px;border:1px solid var(--exec-border);border-radius:7px;background:#fff;color:var(--exec-text);font-size:13px;font-weight:600;padding:0 10px}
.exec-field input:focus,.exec-field select:focus{outline:0;border-color:var(--exec-brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--exec-brand) 13%,transparent)}
.exec-metrics{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-bottom:16px}
.exec-metric{border:1px solid var(--exec-border);border-radius:8px;background:#fff;padding:14px;min-height:104px}
.exec-metric span{display:block;color:var(--exec-muted);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.exec-metric strong{display:block;margin-top:8px;color:var(--exec-brand-2);font-family:'Cormorant Garamond',Georgia,serif;font-size:24px;line-height:1.08;font-weight:700}
.exec-metric small{display:block;margin-top:7px;color:var(--exec-muted);font-size:12px;font-weight:600}
.exec-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(360px,.8fr);gap:14px}
.exec-stack{display:grid;gap:14px}
.exec-panel{border:1px solid var(--exec-border);border-radius:8px;background:#fff;overflow:hidden}
.exec-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid var(--exec-border);background:var(--exec-soft)}
.exec-panel-title{margin:0;color:var(--exec-brand-2);font-family:'Cormorant Garamond',Georgia,serif;font-size:18px;font-weight:700}
.exec-panel-subtitle{margin:4px 0 0;color:var(--exec-muted);font-size:12px}
.exec-panel-body{padding:14px 16px}
.exec-stat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.exec-stat{border:1px solid color-mix(in srgb,var(--exec-border) 78%,transparent);border-radius:8px;padding:11px;background:#fff}
.exec-stat span{display:block;color:var(--exec-muted);font-size:11px;font-weight:700}
.exec-stat strong{display:block;margin-top:5px;color:var(--exec-brand);font-family:'Cormorant Garamond',Georgia,serif;font-size:20px;font-weight:700}
.exec-row{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:42px;border-top:1px solid color-mix(in srgb,var(--exec-border) 70%,transparent)}
.exec-row:first-child{border-top:0}
.exec-row span{color:var(--exec-muted);font-size:13px;font-weight:600}
.exec-row strong{color:var(--exec-brand-2);font-size:14px;font-weight:700;text-align:right}
.exec-table-wrap{overflow-x:auto}
.exec-table{width:100%;border-collapse:collapse;min-width:620px}
.exec-table th{padding:10px 12px;text-align:left;color:var(--exec-muted);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;border-bottom:1px solid var(--exec-border)}
.exec-table td{padding:11px 12px;border-bottom:1px solid #eef2f7;vertical-align:top;font-size:13px}
.exec-status{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:700}
.exec-status.ok{background:#ecfdf5;color:#047857}
.exec-status.warning{background:#fffbeb;color:#b45309}
.exec-status.error{background:#fef2f2;color:#b91c1c}
.exec-link{color:var(--exec-brand);font-weight:700;text-decoration:none}
.exec-empty{padding:24px;text-align:center;color:var(--exec-muted)}
.exec-empty strong{display:block;color:var(--exec-brand-2);margin-bottom:6px}
.exec-footnote{margin-top:10px;color:var(--exec-muted);font-size:12px;font-weight:500}
@media (max-width:1220px){.exec-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}.exec-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.exec-layout{grid-template-columns:1fr}.exec-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:720px){.exec-page{padding:16px}.exec-hero{display:block}.exec-actions{justify-content:flex-start;margin-top:12px}.exec-title{font-size:24px}.exec-metrics,.exec-filter-grid,.exec-stat-grid{grid-template-columns:1fr}.exec-table{min-width:540px}}
</style>

<div class="exec-page">
    <div class="exec-hero">
        <div>
            <div class="exec-kicker">Reportes / Solo lectura</div>
            <h1 class="exec-title">Tablero ejecutivo</h1>
            <p class="exec-subtitle">KPIs consolidados del periodo, alertas por area y enlaces de contexto del hotel actual.</p>
        </div>
        <div class="exec-actions">
            <span class="exec-badge"><i class="fas fa-lock"></i> Solo lectura</span>
            <a class="exec-btn" href="<?= back_url('reportes') ?>"><i class="fas fa-arrow-left"></i> Reportes</a>
            <a class="exec-btn" href="<?= url('operacion/conciliacion-financiera') ?>"><i class="fas fa-balance-scale"></i> Conciliacion</a>
        </div>
    </div>

    <form class="exec-filter" method="get" action="<?= url('reportes/ejecutivo') ?>">
        <div class="exec-filter-grid">
            <div class="exec-field">
                <label for="periodo">Periodo</label>
                <select id="periodo" name="periodo">
                    <?php foreach ($periodos as $value => $label): ?>
                        <option value="<?= exec_safe($value) ?>" <?= (($filtros['periodo'] ?? 'mes') === $value) ? 'selected' : '' ?>><?= exec_safe($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="exec-field">
                <label for="fecha_desde">Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="<?= exec_safe($filtros['fecha_desde'] ?? '', '') ?>">
            </div>
            <div class="exec-field">
                <label for="fecha_hasta">Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= exec_safe($filtros['fecha_hasta'] ?? '', '') ?>">
            </div>
            <div class="exec-field">
                <label for="area">Area</label>
                <select id="area" name="area">
                    <?php foreach ($areas as $value => $label): ?>
                        <option value="<?= exec_safe($value) ?>" <?= (($filtros['area'] ?? 'todas') === $value) ? 'selected' : '' ?>><?= exec_safe($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="exec-field">
                <label for="severidad">Estado</label>
                <select id="severidad" name="severidad">
                    <?php foreach ($severidades as $value => $label): ?>
                        <option value="<?= exec_safe($value) ?>" <?= (($filtros['severidad'] ?? 'todas') === $value) ? 'selected' : '' ?>><?= exec_safe($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="exec-field">
                <label for="limit">Alertas</label>
                <select id="limit" name="limit">
                    <?php foreach ([5, 10, 25] as $limit): ?>
                        <option value="<?= $limit ?>" <?= ((int)($filtros['limit'] ?? 10) === $limit) ? 'selected' : '' ?>><?= $limit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="exec-field">
                <button class="exec-btn primary" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
        </div>
    </form>

    <div class="exec-metrics">
        <div class="exec-metric"><span>Ingresos</span><strong><?= exec_money($resumen['ingresos_periodo'] ?? 0) ?></strong><small><?= exec_safe($filtros['fecha_desde'] ?? '') ?> a <?= exec_safe($filtros['fecha_hasta'] ?? '') ?></small></div>
        <div class="exec-metric"><span>Gastos</span><strong><?= exec_money($resumen['gastos_periodo'] ?? 0) ?></strong><small>Neto <?= exec_money($resumen['neto_periodo'] ?? 0) ?></small></div>
        <div class="exec-metric"><span>CxC pendiente</span><strong><?= exec_money($resumen['saldo_cxc'] ?? 0) ?></strong><small><?= exec_num($finanzas['cxc_pendientes'] ?? 0) ?> cuentas</small></div>
        <div class="exec-metric"><span>CxP pendiente</span><strong><?= exec_money($resumen['saldo_cxp'] ?? 0) ?></strong><small><?= exec_num($finanzas['cxp_pendientes'] ?? 0) ?> cuentas</small></div>
        <div class="exec-metric"><span>Ocupacion</span><strong><?= exec_pct($operacion['ocupacion_pct'] ?? 0) ?></strong><small><?= exec_num($operacion['habitaciones_ocupadas'] ?? 0) ?> de <?= exec_num($operacion['habitaciones_total'] ?? 0) ?> habitaciones</small></div>
        <div class="exec-metric"><span>Alertas</span><strong><?= exec_num(($totalesAlertas['warning'] ?? 0) + ($totalesAlertas['error'] ?? 0)) ?></strong><small><?= $schemaOk ? 'Esquema disponible' : 'Esquema con advertencias' ?></small></div>
    </div>

    <div class="exec-layout">
        <div class="exec-stack">
            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Operacion hotelera</h2>
                        <p class="exec-panel-subtitle">Habitaciones, agenda y reservaciones proximas.</p>
                    </div>
                    <a class="exec-btn" href="<?= url('operacion/diaria') ?>">Operacion diaria</a>
                </div>
                <div class="exec-panel-body">
                    <div class="exec-stat-grid">
                        <div class="exec-stat"><span>Disponibles</span><strong><?= exec_num($operacion['habitaciones_disponibles'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Ocupadas</span><strong><?= exec_num($operacion['habitaciones_ocupadas'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Limpieza</span><strong><?= exec_num($operacion['habitaciones_limpieza'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Mantenimiento</span><strong><?= exec_num($operacion['habitaciones_mantenimiento'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Check-ins hoy</span><strong><?= exec_num($operacion['checkins_hoy'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Check-outs hoy</span><strong><?= exec_num($operacion['checkouts_hoy'] ?? 0) ?></strong></div>
                    </div>
                </div>
                <div class="exec-table-wrap">
                    <table class="exec-table">
                        <thead><tr><th>Reservacion</th><th>Huesped</th><th>Entrada</th><th>Salida</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach (($operacion['proximas_reservaciones'] ?? []) as $reservacion): ?>
                            <tr>
                                <td><a class="exec-link" href="<?= url('reservaciones/ver/' . (int)($reservacion['id'] ?? 0)) ?>">#<?= (int)($reservacion['id'] ?? 0) ?></a></td>
                                <td><?= exec_safe($reservacion['huesped'] ?? 'Sin huesped') ?></td>
                                <td><?= exec_date($reservacion['fecha_entrada'] ?? '') ?></td>
                                <td><?= exec_date($reservacion['fecha_salida'] ?? '') ?></td>
                                <td><?= exec_safe($reservacion['estado'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($operacion['proximas_reservaciones'])): ?>
                            <tr><td colspan="5" class="exec-empty"><strong>Sin reservaciones proximas</strong></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Finanzas y Caja</h2>
                        <p class="exec-panel-subtitle">Cartera, movimientos por metodo y cortes.</p>
                    </div>
                    <a class="exec-btn" href="<?= url('caja/arqueo-metodos') ?>">Arqueo</a>
                </div>
                <div class="exec-panel-body">
                    <div class="exec-stat-grid">
                        <div class="exec-stat"><span>CxC vencidas</span><strong><?= exec_num($finanzas['cxc_vencidas'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>CxP vencidas</span><strong><?= exec_num($finanzas['cxp_vencidas'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Cortes abiertos</span><strong><?= exec_num($resumen['cortes_abiertos'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Cortes con diferencia</span><strong><?= exec_num($finanzas['cortes_con_diferencia'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Cobros CxC</span><strong><?= exec_num($finanzas['cxc_cobros_periodo'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Pagos CxP</span><strong><?= exec_num($finanzas['cxp_pagos_periodo'] ?? 0) ?></strong></div>
                    </div>
                    <div class="exec-footnote">Reversiones del periodo: CxC <?= exec_num($finanzas['cxc_reversiones_periodo'] ?? 0) ?> / CxP <?= exec_num($finanzas['cxp_reversiones_periodo'] ?? 0) ?>.</div>
                </div>
                <div class="exec-table-wrap">
                    <table class="exec-table">
                        <thead><tr><th>Metodo</th><th>Ingresos</th><th>Total ingresos</th><th>Gastos</th><th>Total gastos</th></tr></thead>
                        <tbody>
                        <?php foreach (($finanzas['metodos'] ?? []) as $metodo): ?>
                            <tr>
                                <td><?= exec_safe($metodo['metodo_pago'] ?? 'sin metodo') ?></td>
                                <td><?= exec_num($metodo['ingresos_count'] ?? 0) ?></td>
                                <td><?= exec_money($metodo['ingresos_total'] ?? 0) ?></td>
                                <td><?= exec_num($metodo['gastos_count'] ?? 0) ?></td>
                                <td><?= exec_money($metodo['gastos_total'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($finanzas['metodos'])): ?>
                            <tr><td colspan="5" class="exec-empty"><strong>Sin movimientos por metodo</strong></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="exec-stack">
            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Alertas</h2>
                        <p class="exec-panel-subtitle">Filtradas por area y estado.</p>
                    </div>
                    <span class="exec-badge"><?= exec_num(count($alertas)) ?> visibles</span>
                </div>
                <div class="exec-panel-body">
                    <?php foreach ($alertas as $alerta): ?>
                        <div class="exec-row">
                            <span>
                                <span class="exec-status <?= exec_safe($alerta['severidad'] ?? 'warning') ?>"><?= exec_safe($alerta['severidad'] ?? 'warning') ?></span>
                                <?= exec_safe($alerta['titulo'] ?? '') ?><br>
                                <small><?= exec_safe($alerta['detalle'] ?? '') ?></small>
                            </span>
                            <strong>
                                <?= exec_num($alerta['valor'] ?? 0) ?>
                                <?php if (!empty($alerta['href'])): ?><br><a class="exec-link" href="<?= url($alerta['href']) ?>">Ver</a><?php endif; ?>
                            </strong>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($alertas)): ?>
                        <div class="exec-empty"><strong>Sin alertas para los filtros actuales</strong></div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Inventario y compras</h2>
                        <p class="exec-panel-subtitle">Stock, compras recibidas y movimientos.</p>
                    </div>
                    <a class="exec-btn" href="<?= url('inventario') ?>">Inventario</a>
                </div>
                <div class="exec-panel-body">
                    <div class="exec-row"><span>Productos bajo minimo</span><strong><?= exec_num($inventario['productos_bajo_minimo'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Sin movimiento en 30 dias</span><strong><?= exec_num($inventario['productos_sin_movimiento_30d'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Compras recibidas</span><strong><?= exec_num($inventario['compras_recibidas_periodo'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Total compras</span><strong><?= exec_money($inventario['compras_total_periodo'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Entradas / salidas</span><strong><?= exec_num($inventario['movimientos_entrada'] ?? 0) ?> / <?= exec_num($inventario['movimientos_salida'] ?? 0) ?></strong></div>
                </div>
            </section>

            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Tareas y personal</h2>
                        <p class="exec-panel-subtitle">Carga activa por operacion.</p>
                    </div>
                    <a class="exec-btn" href="<?= url('tareas') ?>">Tareas</a>
                </div>
                <div class="exec-panel-body">
                    <div class="exec-row"><span>Trabajadores activos</span><strong><?= exec_num($personal['trabajadores_activos'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Tareas activas</span><strong><?= exec_num($personal['tareas_activas'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Tareas vencidas</span><strong><?= exec_num($personal['tareas_vencidas'] ?? 0) ?></strong></div>
                    <?php foreach (($personal['tareas_por_categoria'] ?? []) as $categoria): ?>
                        <div class="exec-row"><span><?= exec_safe($categoria['categoria'] ?? 'Sin categoria') ?></span><strong><?= exec_num($categoria['total'] ?? 0) ?></strong></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Documentos y auditoria</h2>
                        <p class="exec-panel-subtitle">Resumen documental del periodo.</p>
                    </div>
                </div>
                <div class="exec-panel-body">
                    <div class="exec-row"><span>Documentos del periodo</span><strong><?= exec_num($documentos['documentos_periodo'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Documentos archivados</span><strong><?= exec_num($documentos['documentos_archivados'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Eventos de auditoria</span><strong><?= exec_num($documentos['auditoria_periodo'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Auditoria historica sin hotel</span><strong><?= exec_num($documentos['auditoria_sin_hotel'] ?? 0) ?></strong></div>
                    <?php foreach (($documentos['documentos_por_entidad'] ?? []) as $entidad): ?>
                        <div class="exec-row"><span><?= exec_safe($entidad['entidad_tipo'] ?? 'Entidad') ?></span><strong><?= exec_num($entidad['total'] ?? 0) ?></strong></div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</div>
