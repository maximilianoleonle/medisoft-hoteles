<?php
if (!function_exists('arqueo_safe')) {
    function arqueo_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('arqueo_num')) {
    function arqueo_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('arqueo_money')) {
    function arqueo_money($value, bool $signed = false): string
    {
        $amount = (float)($value ?? 0);
        $prefix = '';

        if ($amount < 0) {
            $prefix = '-';
            $amount = abs($amount);
        } elseif ($signed && $amount > 0) {
            $prefix = '+';
        }

        return $prefix . '$' . number_format($amount, 2);
    }
}

if (!function_exists('arqueo_date')) {
    function arqueo_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

if (!function_exists('arqueo_query')) {
    function arqueo_query(array $filtros, array $overrides = []): string
    {
        $query = array_merge($filtros, $overrides);
        foreach ($query as $key => $value) {
            if ($value === '' || $value === 0 || $value === '0' || $value === null || ($key === 'page' && (int)$value <= 1)) {
                unset($query[$key]);
            }
        }

        return http_build_query($query);
    }
}

if (!function_exists('arqueo_method_meta')) {
    function arqueo_method_meta(string $metodo, array $metodosPago): array
    {
        $fallback = [
            'efectivo' => ['label' => 'Efectivo', 'icon' => 'money-bill-wave'],
            'tarjeta' => ['label' => 'Tarjeta', 'icon' => 'credit-card'],
            'transferencia' => ['label' => 'Transferencia', 'icon' => 'exchange-alt'],
        ];

        $meta = $metodosPago[$metodo] ?? ($fallback[$metodo] ?? []);
        return [
            'label' => $meta['label'] ?? ucfirst(str_replace('_', ' ', $metodo)),
            'icon' => $meta['icon'] ?? 'circle',
        ];
    }
}

if (!function_exists('arqueo_severity_label')) {
    function arqueo_severity_label(string $severity): string
    {
        return [
            'ok' => 'Correcto',
            'warning' => 'Con aviso',
            'error' => 'Con error',
        ][$severity] ?? 'OK';
    }
}

$reporte = is_array($reporte ?? null) ? $reporte : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$metodos = is_array($reporte['metodos'] ?? null) ? $reporte['metodos'] : [];
$alertas = is_array($reporte['alertas'] ?? null) ? $reporte['alertas'] : [];
$anomalias = is_array($reporte['anomalias'] ?? null) ? $reporte['anomalias'] : [];
$cortes = is_array($reporte['cortes'] ?? null) ? $reporte['cortes'] : [];
$filtros = is_array($reporte['filtros'] ?? null) ? $reporte['filtros'] : [];
$pagination = is_array($reporte['pagination'] ?? null) ? $reporte['pagination'] : ['page' => 1, 'limit' => 25, 'total' => 0, 'pages' => 1];
$metodos_pago = is_array($metodos_pago ?? null) ? $metodos_pago : [];
$schemaOk = !empty($reporte['schema_ok']);
$mensaje = (string)($reporte['mensaje'] ?? '');
$generatedAt = (string)($reporte['generated_at'] ?? '');

$estadoOptions = [
    'todos' => 'Todos',
    'abierto' => 'Abierto',
    'cerrado' => 'Cerrado',
    'cancelado' => 'Cancelado',
];
$severidadOptions = [
    'todos' => 'Todas',
    'ok' => 'Correcto',
    'warning' => 'Con aviso',
    'error' => 'Con error',
];
$limitOptions = [10, 25, 50, 100];
$methodKeys = array_values(array_unique(array_merge(['efectivo', 'tarjeta', 'transferencia'], array_keys($metodos))));
?>

<style>
.arqueo-page{--am-brand:var(--brand-primary,#1B2746);--am-brand-2:var(--brand-secondary,#0F172A);--am-accent:var(--brand-accent,#BD9441);--am-text:var(--brand-text,#172033);--am-muted:var(--brand-muted,#64748B);--am-border:var(--brand-border,#E5E7EB);--am-soft:color-mix(in srgb,var(--am-brand) 5%,#F8FAFC);--am-accent-soft:color-mix(in srgb,var(--am-accent) 12%,#FFFFFF);--am-ok:#047857;--am-warning:#B45309;--am-error:#B91C1C;max-width:1500px;margin:0 auto;padding:24px;color:var(--am-text)}
.arqueo-hero{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:18px}
.arqueo-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:var(--am-muted);font-weight:900}
.arqueo-title{font-size:30px;line-height:1.08;margin:6px 0 8px;font-weight:900;color:var(--am-brand-2)}
.arqueo-subtitle{max-width:840px;margin:0;color:var(--am-muted)}
.arqueo-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
.arqueo-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;padding:10px 14px;border-radius:7px;border:1px solid var(--am-border);background:#fff;color:var(--am-text);font-weight:850;text-decoration:none}
.arqueo-btn.primary{background:var(--am-brand);border-color:var(--am-brand);color:var(--brand-action-text,#fff)}
.arqueo-badge{display:inline-flex;align-items:center;gap:7px;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900;background:var(--am-accent-soft);color:var(--am-brand);border:1px solid color-mix(in srgb,var(--am-accent) 25%,transparent)}
.arqueo-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
.arqueo-card{background:#fff;border:1px solid var(--am-border);border-radius:8px;padding:14px;box-shadow:0 8px 22px rgba(15,23,42,.05)}
.arqueo-card span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--am-muted);font-weight:900}
.arqueo-card strong{display:block;margin-top:8px;font-size:24px;line-height:1.1;color:var(--am-brand-2)}
.arqueo-card small{display:block;margin-top:7px;color:var(--am-muted);font-weight:700}
.arqueo-band{background:linear-gradient(135deg,var(--am-brand),var(--am-brand-2));border-radius:8px;color:#fff;padding:16px 18px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:14px}
.arqueo-band strong{display:block;font-size:16px}
.arqueo-band span{color:rgba(255,255,255,.78);font-size:13px}
.arqueo-chips{display:flex;gap:8px;flex-wrap:wrap}
.arqueo-chip{border-radius:999px;padding:6px 9px;font-size:12px;font-weight:900;border:1px solid rgba(255,255,255,.24);background:rgba(255,255,255,.12)}
.arqueo-filter{background:#fff;border:1px solid var(--am-border);border-radius:8px;padding:14px;margin-bottom:16px;box-shadow:0 8px 22px rgba(15,23,42,.04)}
.arqueo-filter-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:10px;align-items:end}
.arqueo-field label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:var(--am-muted);font-weight:900;margin-bottom:6px}
.arqueo-field input,.arqueo-field select{width:100%;height:40px;border:1px solid var(--am-border);border-radius:6px;padding:0 10px;color:var(--am-text);background:#fff;font-weight:700}
.arqueo-field input:focus,.arqueo-field select:focus{outline:0;border-color:var(--am-brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--am-brand) 14%,transparent)}
.arqueo-panel{background:#fff;border:1px solid var(--am-border);border-radius:8px;box-shadow:0 10px 28px rgba(15,23,42,.06);overflow:hidden;margin-bottom:16px}
.arqueo-panel-head{padding:16px 18px;border-bottom:1px solid var(--am-border);background:var(--am-soft);display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
.arqueo-panel-title{font-size:16px;font-weight:900;margin:0;color:var(--am-brand-2)}
.arqueo-panel-subtitle{font-size:13px;color:var(--am-muted);margin:4px 0 0}
.arqueo-table-wrap{overflow-x:auto}
.arqueo-table{width:100%;border-collapse:collapse;min-width:980px}
.arqueo-table th{font-size:12px;text-align:left;text-transform:uppercase;letter-spacing:.08em;color:var(--am-muted);background:#fff;padding:12px 14px;border-bottom:1px solid var(--am-border)}
.arqueo-table td{padding:13px 14px;border-bottom:1px solid #eef2f7;vertical-align:top}
.arqueo-table tr:last-child td{border-bottom:0}
.arqueo-status{display:inline-flex;align-items:center;gap:7px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:900}
.arqueo-status.ok{background:#ECFDF5;color:var(--am-ok)}
.arqueo-status.warning{background:#FFFBEB;color:var(--am-warning)}
.arqueo-status.error{background:#FEF2F2;color:var(--am-error)}
.arqueo-state{display:inline-flex;border-radius:999px;padding:5px 9px;background:#F8FAFC;color:var(--am-brand);font-size:12px;font-weight:900;text-transform:uppercase}
.arqueo-methods{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
.arqueo-method{background:#fff;border:1px solid var(--am-border);border-radius:8px;padding:14px;box-shadow:0 8px 22px rgba(15,23,42,.05)}
.arqueo-method-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
.arqueo-method-title{display:flex;align-items:center;gap:9px;font-weight:900;color:var(--am-brand-2)}
.arqueo-method-icon{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;background:var(--am-accent-soft);color:var(--am-brand)}
.arqueo-method-row{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-top:1px solid #EEF2F7;color:var(--am-muted);font-weight:800}
.arqueo-method-row strong{color:var(--am-text)}
.arqueo-mini{width:100%;border-collapse:collapse;min-width:360px}
.arqueo-mini th{padding:5px 7px;border-bottom:1px solid #EEF2F7;font-size:11px;text-align:left;color:var(--am-muted);text-transform:uppercase;letter-spacing:.06em}
.arqueo-mini td{padding:6px 7px;border-bottom:1px solid #F1F5F9;font-size:12px;font-weight:800}
.arqueo-mini tr:last-child td{border-bottom:0}
.arqueo-diff-zero{color:var(--am-ok)}
.arqueo-diff-warn{color:var(--am-warning)}
.arqueo-diff-error{color:var(--am-error)}
.arqueo-muted{color:var(--am-muted);font-size:13px}
.arqueo-code{font-size:12px;color:var(--am-muted);font-weight:800;margin-top:3px}
.arqueo-link{font-weight:900;color:var(--am-brand);text-decoration:none}
.arqueo-tags{display:flex;gap:6px;flex-wrap:wrap}
.arqueo-tag{display:inline-flex;border-radius:999px;padding:4px 8px;background:#F8FAFC;border:1px solid #E2E8F0;color:var(--am-muted);font-size:12px;font-weight:850}
.arqueo-empty{padding:34px 20px;text-align:center;color:var(--am-muted)}
.arqueo-empty strong{display:block;color:var(--am-brand-2);font-size:18px;margin-bottom:8px}
.arqueo-pagination{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:14px 18px;border-top:1px solid var(--am-border);background:#fff}
.arqueo-pages{display:flex;gap:8px;flex-wrap:wrap}
.arqueo-page-link{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:36px;border-radius:7px;border:1px solid var(--am-border);color:var(--am-text);text-decoration:none;font-weight:900;background:#fff}
.arqueo-page-link.active{background:var(--am-brand);border-color:var(--am-brand);color:var(--brand-action-text,#fff)}
.arqueo-page-link.disabled{opacity:.45;pointer-events:none}
@media (max-width:1100px){.arqueo-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.arqueo-methods{grid-template-columns:1fr}.arqueo-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.arqueo-hero{flex-direction:column}.arqueo-actions{justify-content:flex-start}}
@media (max-width:760px){.arqueo-page{padding:18px}.arqueo-grid{grid-template-columns:1fr}.arqueo-filter-grid{grid-template-columns:1fr}.arqueo-title{font-size:25px}.arqueo-band{align-items:flex-start;flex-direction:column}.arqueo-pagination{align-items:flex-start;flex-direction:column}}
</style>

<div class="arqueo-page">
    <div class="arqueo-hero">
        <div>
            <div class="arqueo-kicker">Caja / Corte por metodo</div>
            <h1 class="arqueo-title">Arqueo por metodo de pago</h1>
            <p class="arqueo-subtitle">
                Consulta operativa de cortes, movimientos y diferencias historicas.
            </p>
        </div>
        <div class="arqueo-actions">
            <span class="arqueo-badge"><i class="fas fa-eye"></i> Solo lectura</span>
            <?php $back_arrow_href = back_url('caja'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('caja') ?>" class="arqueo-btn ms-back-legacy"><i class="fas fa-arrow-left"></i> Caja</a>
            <a href="<?= url('caja/reporte-metodos') ?>" class="arqueo-btn primary"><i class="fas fa-chart-bar"></i> Reporte metodos</a>
        </div>
    </div>

    <div class="arqueo-band">
        <div>
            <strong>Consulta generada <?= arqueo_safe(arqueo_date($generatedAt)) ?></strong>
            <span><?= $schemaOk ? 'Datos obtenidos desde Caja en modo lectura.' : arqueo_safe($mensaje, 'No se pudo generar la consulta.') ?></span>
        </div>
        <div class="arqueo-chips">
            <span class="arqueo-chip"><?= arqueo_num($resumen['cortes_total'] ?? 0) ?> cortes</span>
            <span class="arqueo-chip"><?= arqueo_num($resumen['movimientos_total'] ?? 0) ?> movimientos</span>
            <span class="arqueo-chip"><?= arqueo_num($resumen['cortes_con_warning'] ?? 0) ?> warnings</span>
            <span class="arqueo-chip"><?= arqueo_num($resumen['cortes_con_error'] ?? 0) ?> errores</span>
        </div>
    </div>

    <form method="get" action="<?= url('caja/arqueo-metodos') ?>" class="arqueo-filter" data-auto-filter-form>
        <div class="arqueo-filter-grid">
            <div class="arqueo-field">
                <label for="fecha_desde">Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="<?= arqueo_safe($filtros['fecha_desde'] ?? '', '') ?>">
            </div>
            <div class="arqueo-field">
                <label for="fecha_hasta">Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= arqueo_safe($filtros['fecha_hasta'] ?? '', '') ?>">
            </div>
            <div class="arqueo-field">
                <label for="corte_id">Corte</label>
                <input type="number" min="0" id="corte_id" name="corte_id" value="<?= arqueo_safe((string)($filtros['corte_id'] ?? ''), '') ?>">
            </div>
            <div class="arqueo-field">
                <label for="caja_id">Caja</label>
                <input type="number" min="0" id="caja_id" name="caja_id" value="<?= arqueo_safe((string)($filtros['caja_id'] ?? ''), '') ?>">
            </div>
            <div class="arqueo-field">
                <label for="estado_corte">Estado</label>
                <select id="estado_corte" name="estado_corte">
                    <?php foreach ($estadoOptions as $value => $label): ?>
                        <option value="<?= arqueo_safe($value) ?>" <?= (($filtros['estado_corte'] ?? 'todos') === $value) ? 'selected' : '' ?>><?= arqueo_safe($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="arqueo-field">
                <label for="metodo_pago">Metodo</label>
                <select id="metodo_pago" name="metodo_pago">
                    <option value="todos" <?= (($filtros['metodo_pago'] ?? 'todos') === 'todos') ? 'selected' : '' ?>>Todos</option>
                    <?php foreach (['efectivo', 'tarjeta', 'transferencia'] as $metodoKey): ?>
                        <?php $meta = arqueo_method_meta($metodoKey, $metodos_pago); ?>
                        <option value="<?= arqueo_safe($metodoKey) ?>" <?= (($filtros['metodo_pago'] ?? 'todos') === $metodoKey) ? 'selected' : '' ?>><?= arqueo_safe($meta['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="arqueo-field">
                <label for="severidad">Severidad</label>
                <select id="severidad" name="severidad">
                    <?php foreach ($severidadOptions as $value => $label): ?>
                        <option value="<?= arqueo_safe($value) ?>" <?= (($filtros['severidad'] ?? 'todos') === $value) ? 'selected' : '' ?>><?= arqueo_safe($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="arqueo-field">
                <label for="limit">Filas</label>
                <select id="limit" name="limit">
                    <?php foreach ($limitOptions as $limit): ?>
                        <option value="<?= (int)$limit ?>" <?= ((int)($filtros['limit'] ?? 25) === $limit) ? 'selected' : '' ?>><?= (int)$limit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="arqueo-field">
                <a href="<?= url('caja/arqueo-metodos') ?>" class="arqueo-btn" style="width:100%;height:40px"><i class="fas fa-times"></i> Limpiar</a>
            </div>
        </div>
    </form>

    <div class="arqueo-grid">
        <div class="arqueo-card">
            <span>Cortes</span>
            <strong><?= arqueo_num($resumen['cortes_total'] ?? 0) ?></strong>
            <small><?= arqueo_num($resumen['cortes_abiertos'] ?? 0) ?> abiertos / <?= arqueo_num($resumen['cortes_cerrados'] ?? 0) ?> cerrados</small>
        </div>
        <div class="arqueo-card">
            <span>Ingresos</span>
            <strong><?= arqueo_money($resumen['ingresos_total'] ?? 0) ?></strong>
            <small>Por movimientos de Caja</small>
        </div>
        <div class="arqueo-card">
            <span>Gastos</span>
            <strong><?= arqueo_money($resumen['gastos_total'] ?? 0) ?></strong>
            <small>Por movimientos de Caja</small>
        </div>
        <div class="arqueo-card">
            <span>Neto</span>
            <strong><?= arqueo_money($resumen['neto_total'] ?? 0, true) ?></strong>
            <small><?= arqueo_num($resumen['movimientos_total'] ?? 0) ?> movimientos</small>
        </div>
    </div>

    <div class="arqueo-methods">
        <?php foreach ($methodKeys as $methodKey): ?>
            <?php
            $method = $metodos[$methodKey] ?? ['ingresos_count' => 0, 'ingresos_total' => 0, 'gastos_count' => 0, 'gastos_total' => 0, 'neto' => 0];
            $meta = arqueo_method_meta((string)$methodKey, $metodos_pago);
            ?>
            <div class="arqueo-method">
                <div class="arqueo-method-head">
                    <div class="arqueo-method-title">
                        <span class="arqueo-method-icon"><i class="fas fa-<?= arqueo_safe($meta['icon']) ?>"></i></span>
                        <?= arqueo_safe($meta['label']) ?>
                    </div>
                    <span class="arqueo-status ok"><?= arqueo_num(($method['ingresos_count'] ?? 0) + ($method['gastos_count'] ?? 0)) ?></span>
                </div>
                <div class="arqueo-method-row"><span>Ingresos</span><strong><?= arqueo_money($method['ingresos_total'] ?? 0) ?></strong></div>
                <div class="arqueo-method-row"><span>Gastos</span><strong><?= arqueo_money($method['gastos_total'] ?? 0) ?></strong></div>
                <div class="arqueo-method-row"><span>Neto</span><strong><?= arqueo_money($method['neto'] ?? 0, true) ?></strong></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="arqueo-panel">
        <div class="arqueo-panel-head">
            <div>
                <h2 class="arqueo-panel-title">Alertas de consistencia</h2>
                <p class="arqueo-panel-subtitle">Avisos hist&oacute;ricos como diagn&oacute;stico; nada se corrige en autom&aacute;tico.</p>
            </div>
            <span class="arqueo-badge"><i class="fas fa-shield-alt"></i> Solo lectura</span>
        </div>
        <div class="arqueo-table-wrap">
            <table class="arqueo-table">
                <thead>
                    <tr>
                        <th>Severidad</th>
                        <th>Codigo</th>
                        <th>Revision</th>
                        <th>Hallazgos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alertas as $alerta): ?>
                        <?php $severity = (string)($alerta['severidad'] ?? 'ok'); ?>
                        <tr>
                            <td><span class="arqueo-status <?= arqueo_safe($severity) ?>"><?= arqueo_safe(arqueo_severity_label($severity)) ?></span></td>
                            <td><strong><?= arqueo_safe($alerta['codigo'] ?? '') ?></strong></td>
                            <td><?= arqueo_safe($alerta['titulo'] ?? '') ?></td>
                            <td><strong><?= arqueo_num($alerta['hallazgos'] ?? 0) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($alertas)): ?>
                        <tr><td colspan="4"><div class="arqueo-empty"><strong>Todo en orden</strong>No encontramos diferencias en los cortes consultados.</div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="arqueo-panel">
        <div class="arqueo-panel-head">
            <div>
                <h2 class="arqueo-panel-title">Cortes por metodo</h2>
                <p class="arqueo-panel-subtitle"><?= arqueo_num($pagination['total'] ?? 0) ?> cortes encontrados.</p>
            </div>
        </div>
        <div class="arqueo-table-wrap">
            <table class="arqueo-table">
                <thead>
                    <tr>
                        <th>Corte</th>
                        <th>Caja</th>
                        <th>Estado</th>
                        <th>Apertura / cierre</th>
                        <th>Sumado ahora vs guardado al cierre</th>
                        <th>Efectivo</th>
                        <th>Hallazgos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cortes as $corte): ?>
                        <?php
                        $severity = (string)($corte['severidad'] ?? 'ok');
                        $rowsMetodo = [
                            ['label' => 'Efectivo', 'ing_key' => 'ingresos_efectivo', 'gas_key' => 'gastos_efectivo'],
                            ['label' => 'Tarjeta', 'ing_key' => 'ingresos_tarjeta', 'gas_key' => 'gastos_tarjeta'],
                            ['label' => 'Transf.', 'ing_key' => 'ingresos_transferencia', 'gas_key' => 'gastos_transferencia'],
                        ];
                        ?>
                        <tr>
                            <td>
                                <a class="arqueo-link" href="<?= url('caja/corte/' . (int)($corte['id'] ?? 0)) ?>">#<?= arqueo_num($corte['id'] ?? 0) ?></a>
                                <div class="arqueo-code"><?= arqueo_num($corte['movimientos_total'] ?? 0) ?> movimientos</div>
                            </td>
                            <td>
                                <strong><?= arqueo_safe($corte['caja_nombre'] ?? '') ?></strong>
                                <div class="arqueo-code">Caja #<?= arqueo_num($corte['caja_id'] ?? 0) ?></div>
                            </td>
                            <td>
                                <span class="arqueo-state"><?= arqueo_safe($corte['estado'] ?? '') ?></span>
                                <div style="margin-top:8px"><span class="arqueo-status <?= arqueo_safe($severity) ?>"><?= arqueo_safe(arqueo_severity_label($severity)) ?></span></div>
                            </td>
                            <td>
                                <div><strong><?= arqueo_safe(arqueo_date($corte['fecha_apertura'] ?? '')) ?></strong></div>
                                <div class="arqueo-muted"><?= arqueo_safe(arqueo_date($corte['fecha_cierre'] ?? '')) ?></div>
                            </td>
                            <td>
                                <table class="arqueo-mini">
                                    <thead>
                                        <tr>
                                            <th>Metodo</th>
                                            <th>Mov.</th>
                                            <th>Guard.</th>
                                            <th>Dif.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rowsMetodo as $rowMetodo): ?>
                                            <?php
                                            $ingKey = $rowMetodo['ing_key'];
                                            $gasKey = $rowMetodo['gas_key'];
                                            $movTotal = (float)($corte['movimientos'][$ingKey] ?? 0) - (float)($corte['movimientos'][$gasKey] ?? 0);
                                            $guardTotal = (float)($corte['guardado'][$ingKey] ?? 0) - (float)($corte['guardado'][$gasKey] ?? 0);
                                            $diffTotal = (float)($corte['diferencias'][$ingKey] ?? 0) - (float)($corte['diferencias'][$gasKey] ?? 0);
                                            $diffClass = abs($diffTotal) > 0.01 ? (abs($diffTotal) > 100 ? 'arqueo-diff-error' : 'arqueo-diff-warn') : 'arqueo-diff-zero';
                                            ?>
                                            <tr>
                                                <td><?= arqueo_safe($rowMetodo['label']) ?></td>
                                                <td><?= arqueo_money($movTotal, true) ?></td>
                                                <td><?= arqueo_money($guardTotal, true) ?></td>
                                                <td class="<?= $diffClass ?>"><?= arqueo_money($diffTotal, true) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                            <td>
                                <div><span class="arqueo-muted">Esperado</span> <strong><?= arqueo_money($corte['efectivo_esperado'] ?? 0) ?></strong></div>
                                <div><span class="arqueo-muted">Calculado</span> <strong><?= arqueo_money($corte['efectivo_calculado'] ?? 0) ?></strong></div>
                                <div><span class="arqueo-muted">Dif.</span> <strong><?= arqueo_money($corte['efectivo_diff'] ?? 0, true) ?></strong></div>
                            </td>
                            <td>
                                <?php if (!empty($corte['hallazgos'])): ?>
                                    <div class="arqueo-tags">
                                        <?php foreach ($corte['hallazgos'] as $hallazgo): ?>
                                            <span class="arqueo-tag"><?= arqueo_safe($hallazgo) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="arqueo-status ok">OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cortes)): ?>
                        <tr><td colspan="7"><div class="arqueo-empty"><strong>Sin cortes para los filtros actuales</strong>No se encontraron cortes dentro de esta consulta.</div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="arqueo-pagination">
            <div class="arqueo-muted">
                Pagina <?= arqueo_num($pagination['page'] ?? 1) ?> de <?= arqueo_num($pagination['pages'] ?? 1) ?>.
            </div>
            <div class="arqueo-pages">
                <?php
                $currentPage = (int)($pagination['page'] ?? 1);
                $totalPages = max(1, (int)($pagination['pages'] ?? 1));
                $prevQuery = arqueo_query($filtros, ['page' => max(1, $currentPage - 1)]);
                $nextQuery = arqueo_query($filtros, ['page' => min($totalPages, $currentPage + 1)]);
                ?>
                <a class="arqueo-page-link <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="<?= url('caja/arqueo-metodos') . ($prevQuery ? '?' . $prevQuery : '') ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($page = max(1, $currentPage - 2); $page <= min($totalPages, $currentPage + 2); $page++): ?>
                    <?php $pageQuery = arqueo_query($filtros, ['page' => $page]); ?>
                    <a class="arqueo-page-link <?= $page === $currentPage ? 'active' : '' ?>" href="<?= url('caja/arqueo-metodos') . ($pageQuery ? '?' . $pageQuery : '') ?>"><?= (int)$page ?></a>
                <?php endfor; ?>
                <a class="arqueo-page-link <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" href="<?= url('caja/arqueo-metodos') . ($nextQuery ? '?' . $nextQuery : '') ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>
    </div>

    <div class="arqueo-panel">
        <div class="arqueo-panel-head">
            <div>
                <h2 class="arqueo-panel-title">Movimientos anomalos</h2>
                <p class="arqueo-panel-subtitle">Ultimos 50 movimientos detectados por las reglas de consistencia.</p>
            </div>
        </div>
        <div class="arqueo-table-wrap">
            <table class="arqueo-table">
                <thead>
                    <tr>
                        <th>Movimiento</th>
                        <th>Fecha</th>
                        <th>Corte</th>
                        <th>Tipo</th>
                        <th>Metodo</th>
                        <th>Monto</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($anomalias as $movimiento): ?>
                        <tr>
                            <td><strong>#<?= arqueo_num($movimiento['id'] ?? 0) ?></strong></td>
                            <td><?= arqueo_safe(arqueo_date($movimiento['created_at'] ?? '')) ?></td>
                            <td>
                                <?php if (!empty($movimiento['corte_id'])): ?>
                                    <a class="arqueo-link" href="<?= url('caja/corte/' . (int)$movimiento['corte_id']) ?>">#<?= arqueo_num($movimiento['corte_id']) ?></a>
                                    <div class="arqueo-code"><?= arqueo_safe($movimiento['corte_estado'] ?? '') ?></div>
                                <?php else: ?>
                                    <span class="arqueo-status error">Sin corte</span>
                                <?php endif; ?>
                            </td>
                            <td><?= arqueo_safe($movimiento['tipo'] ?? '') ?></td>
                            <td><?= arqueo_safe($movimiento['metodo_pago'] ?? '') ?></td>
                            <td><strong><?= arqueo_money($movimiento['monto'] ?? 0) ?></strong></td>
                            <td><?= arqueo_safe($movimiento['referencia'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($anomalias)): ?>
                        <tr><td colspan="7"><div class="arqueo-empty"><strong>Sin movimientos anomalos</strong>No hay movimientos con reglas criticas dentro de los filtros actuales.</div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
