<?php
$cuentas = $cuentas ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$corte = $corte ?? null;
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('cxp_cash_safe')) {
    function cxp_cash_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxp_cash_money')) {
    function cxp_cash_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('cxp_cash_estado_meta')) {
    function cxp_cash_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente' => ['Pendiente', 'is-pendiente', 'fa-clock'],
            'parcial'   => ['Parcial',   'is-parcial',   'fa-circle-half-stroke'],
            'vencida'   => ['Vencida',   'is-vencida',   'fa-triangle-exclamation'],
            'pagada'    => ['Pagada',    'is-pagada',    'fa-circle-check'],
            'cancelada' => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
?>

<style>
.cxp-cash-page {
    --cx-brand: var(--brand-primary, #1B2746);
    --cx-brand-2: var(--brand-secondary, #0F172A);
    --cx-gold: var(--brand-accent, #BD9441);
    --cx-gold-soft: color-mix(in srgb, var(--cx-gold) 15%, #FFFFFF);
    --cx-gold-line: color-mix(in srgb, var(--cx-gold) 42%, #E4D4B0);
    --cx-gold-ink: color-mix(in srgb, var(--cx-gold) 72%, #000);
    --cx-ivory: #F6F2EA;
    --cx-ivory-2: #FBF8F2;
    --cx-surface: #FFFFFF;
    --cx-surface-warm: #FCFAF5;
    --cx-border: color-mix(in srgb, var(--cx-brand) 7%, #E7E1D4);
    --cx-ring: color-mix(in srgb, var(--cx-gold) 32%, transparent);
    --cx-text: #171717;
    --cx-muted: #667085;
    --cx-heading: #111827;
    --cx-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --cx-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cx-success: #1E9E63; --cx-success-bg: #E7F4EC;
    --cx-warning: #C2841C; --cx-warning-bg: #FAF0DC;
    --cx-danger: #B4392B; --cx-danger-bg: #F8EAE5;
    --cx-info: #2F77E0; --cx-info-bg: #E6EFFC;
    min-height: 100%;
    color: var(--cx-text);
    font-family: var(--cx-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cx-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cx-ivory-2), var(--cx-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.cxp-cash-page .cx-shell { display: grid; gap: 14px; }
.cxp-cash-page .cx-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.cxp-cash-page .cx-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cx-gold), var(--cx-brand) 54%, color-mix(in srgb, var(--cx-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cx-brand) 72%, transparent);
}
.cxp-cash-page .cx-kicker { margin: 0 0 2px; color: var(--cx-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.cxp-cash-page .cx-title { margin: 0; font-family: var(--cx-serif); color: var(--cx-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.cxp-cash-page .cx-subtitle { max-width: 52rem; margin: 8px 0 0; color: var(--cx-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.cxp-cash-page .cx-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.cxp-cash-page .cx-stat { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.cxp-cash-page .cx-stat-label { color: var(--cx-muted); font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.cxp-cash-page .cx-stat-value { margin-top: 2px; font-family: var(--cx-serif); font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: var(--cx-heading); }

.cxp-cash-page .cx-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.cxp-cash-page .cx-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease;
}
.cxp-cash-page .cx-btn:hover { transform: translateY(-1px); }
.cxp-cash-page .cx-btn-brand { background: linear-gradient(135deg, var(--cx-brand), var(--cx-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--cx-brand) 60%, transparent); }
.cxp-cash-page .cx-btn-muted { background: var(--cx-surface); border-color: var(--cx-border); color: var(--cx-muted); }

.cxp-cash-page .cx-panel { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.cxp-cash-page .cx-meta-label { font-size: .66rem; color: var(--cx-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.cxp-cash-page .cx-meta-value { margin-top: 3px; font-weight: 700; color: var(--cx-heading); }
.cxp-cash-page .cx-sub { color: var(--cx-muted); font-size: .72rem; }
.cxp-cash-page .cx-faint { color: var(--cx-muted); }
.cxp-cash-page .cx-strong { font-weight: 700; color: var(--cx-heading); }

.cxp-cash-page .cx-control { width: 100%; min-height: 40px; border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--cx-text); font-weight: 600; font-size: .88rem; transition: border-color .16s ease, box-shadow .16s ease; }
.cxp-cash-page .cx-control:focus { border-color: var(--cx-gold); box-shadow: 0 0 0 3px var(--cx-ring); outline: none; }
.cxp-cash-page select.cx-control { cursor: pointer; }
.cxp-cash-page .cx-filter-form { display: grid; grid-template-columns: 1fr 180px auto; gap: 10px; align-items: center; }

.cxp-cash-page .cx-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
.cxp-cash-page .cx-badge.is-pendiente { color: color-mix(in srgb, var(--cx-info) 80%, #000); background: var(--cx-info-bg); border-color: color-mix(in srgb, var(--cx-info) 26%, #fff); }
.cxp-cash-page .cx-badge.is-parcial { color: color-mix(in srgb, var(--cx-warning) 82%, #000); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }
.cxp-cash-page .cx-badge.is-vencida { color: color-mix(in srgb, var(--cx-danger) 82%, #000); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxp-cash-page .cx-badge.is-pagada { color: color-mix(in srgb, var(--cx-success) 78%, #000); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxp-cash-page .cx-badge.is-cancelada, .cxp-cash-page .cx-badge.is-soft { color: var(--cx-muted); background: var(--cx-surface-warm); border-color: var(--cx-border); }
.cxp-cash-page .cx-badge-ok { color: color-mix(in srgb, var(--cx-success) 78%, #000); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxp-cash-page .cx-badge-blocked { color: color-mix(in srgb, var(--cx-warning) 82%, #000); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }

.cxp-cash-page .cx-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 15px 18px; border-bottom: 1px solid var(--cx-border); }
.cxp-cash-page .cx-panel-title { font-family: var(--cx-serif); font-size: 1.35rem; font-weight: 700; color: var(--cx-heading); }
.cxp-cash-page .cx-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.cxp-cash-page .cx-table thead { background: var(--cx-surface-warm); border-bottom: 1px solid var(--cx-border); }
.cxp-cash-page .cx-table th { padding: 12px 14px; color: var(--cx-muted); font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.cxp-cash-page .cx-table th.is-end, .cxp-cash-page .cx-table td.is-end { text-align: right; }
.cxp-cash-page .cx-table td { padding: 13px 14px; border-bottom: 1px solid var(--cx-border); vertical-align: top; }
.cxp-cash-page .cx-table tbody tr:last-child td { border-bottom: 0; }
.cxp-cash-page .cx-table tbody tr:hover { background: var(--cx-ivory-2); }
.cxp-cash-page .cx-link { font-weight: 700; color: var(--cx-heading); text-decoration: none; }
.cxp-cash-page .cx-link:hover { text-decoration: underline; text-decoration-color: var(--cx-gold); text-underline-offset: 3px; }

.cxp-cash-page .cx-empty { text-align: center; padding: 40px 18px; }
.cxp-cash-page .cx-empty-icon { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 18px; display: grid; place-items: center; background: var(--cx-gold-soft); color: var(--cx-gold-ink); font-size: 1.25rem; }
.cxp-cash-page .cx-empty h2 { color: var(--cx-brand); font-size: 1.05rem; font-weight: 700; }
.cxp-cash-page .cx-empty p { color: var(--cx-muted); font-size: .88rem; margin-top: 6px; }
.cxp-cash-page .cx-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--cx-gold-soft); border: 1px solid var(--cx-gold-line); border-radius: 16px; }
.cxp-cash-page .cx-notice i { color: var(--cx-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.cxp-cash-page .cx-notice strong { color: var(--cx-heading); display: block; margin-bottom: 2px; }
.cxp-cash-page .cx-notice p { color: var(--cx-muted); font-size: .88rem; margin: 0; }

@media (max-width: 980px) { .cxp-cash-page .cx-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cxp-cash-page .cx-filter-form { grid-template-columns: 1fr; } }
</style>

<div class="cxp-cash-page p-4 sm:p-6">
    <div class="cx-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="cx-title-lockup">
                <div class="cx-hero-icon"><i class="fas fa-cash-register"></i></div>
                <div>
                    <p class="cx-kicker">Pagos a proveedores</p>
                    <h1 class="cx-title">Simulador de pagos</h1>
                    <p class="cx-subtitle">Te dice qu&eacute; cuentas podr&iacute;as pagar ahora mismo con Caja. Esta pantalla solo consulta: no registra pagos ni cambia saldos.</p>
                </div>
            </div>
            <span class="cx-badge is-soft"><i class="fas fa-eye"></i> Solo consulta</span>
        </section>

        <section class="cx-toolbar">
            <div class="flex flex-wrap gap-2">
                <a class="cx-btn cx-btn-muted" href="<?= back_url('cuentas-por-pagar') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver a cuentas por pagar
                </a>
                <a class="cx-btn cx-btn-muted" href="<?= url('caja') ?>">
                    <i class="fas fa-cash-register"></i>
                    Ver Caja
                </a>
            </div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="cx-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>El simulador todav&iacute;a no est&aacute; disponible.</strong>
                    <p>Faltan datos base de cuentas por pagar o de Caja para poder evaluar los pagos.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="cx-stats">
                <div class="cx-stat"><p class="cx-stat-label">Cuentas</p><p class="cx-stat-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p></div>
                <div class="cx-stat"><p class="cx-stat-label">Se pueden pagar</p><p class="cx-stat-value"><?= number_format((int)($resumen['elegibles'] ?? 0)) ?></p></div>
                <div class="cx-stat"><p class="cx-stat-label">Saldo que se podr&iacute;a pagar</p><p class="cx-stat-value"><?= cxp_cash_money($resumen['saldo_elegible'] ?? 0) ?></p></div>
                <div class="cx-stat"><p class="cx-stat-label">Corte abierto</p><p class="cx-stat-value"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'No' ?></p></div>
            </section>

            <div class="cx-panel p-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <div class="cx-meta-label">Corte</div>
                        <div class="cx-meta-value"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'Sin corte abierto' ?></div>
                    </div>
                    <div>
                        <div class="cx-meta-label">Caja</div>
                        <div class="cx-meta-value"><?= cxp_cash_safe($corte['caja_nombre'] ?? null, 'No disponible') ?></div>
                        <div class="cx-sub"><?= cxp_cash_safe($corte['caja_ubicacion'] ?? null, 'Sin ubicaci&oacute;n') ?></div>
                    </div>
                    <div>
                        <div class="cx-meta-label">Apertura</div>
                        <div class="cx-meta-value"><?= cxp_cash_safe($corte['fecha_apertura'] ?? null, 'Pendiente') ?></div>
                    </div>
                    <div>
                        <div class="cx-meta-label">Estado</div>
                        <?php if (!empty($corte)): ?>
                            <span class="cx-badge cx-badge-ok mt-1"><i class="fas fa-circle-check"></i> Abierto</span>
                        <?php else: ?>
                            <span class="cx-badge cx-badge-blocked mt-1"><i class="fas fa-triangle-exclamation"></i> Bloquea pagos</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <section class="cx-panel p-3 md:p-4">
                <form method="GET" action="<?= url('cuentas-por-pagar/simulador-caja') ?>" class="cx-filter-form" data-auto-filter-form>
                    <input class="cx-control" type="search" name="buscar" value="<?= cxp_cash_safe($buscar, '') ?>" placeholder="Buscar por cuenta, folio, compra o proveedor">
                    <select class="cx-control" name="estado">
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="parcial" <?= $estado === 'parcial' ? 'selected' : '' ?>>Parciales</option>
                        <option value="vencida" <?= $estado === 'vencida' ? 'selected' : '' ?>>Vencidas</option>
                        <option value="pagada" <?= $estado === 'pagada' ? 'selected' : '' ?>>Pagadas</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                    </select>
                    <button class="cx-btn cx-btn-brand" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                </form>
            </section>

            <div class="cx-panel overflow-hidden">
                <div class="cx-panel-head">
                    <h2 class="cx-panel-title">Cuentas evaluadas</h2>
                </div>
                <?php if (empty($cuentas)): ?>
                    <div class="cx-empty">
                        <div class="cx-empty-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        <h2>No hay cuentas para evaluar</h2>
                        <p>Cambia los filtros o genera cuentas por pagar desde una compra recibida.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="cx-table">
                            <thead>
                                <tr>
                                    <th>Cuenta</th>
                                    <th>Proveedor</th>
                                    <th>Compra</th>
                                    <th>Estado</th>
                                    <th class="is-end">Total</th>
                                    <th class="is-end">Saldo</th>
                                    <th>&iquest;Se puede pagar?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cuentas as $cuenta): ?>
                                    <?php
                                    $esElegible = !empty($cuenta['es_elegible_caja']);
                                    [$cEstadoLabel, $cEstadoClass, $cEstadoIcon] = cxp_cash_estado_meta($cuenta['estado'] ?? null);
                                    ?>
                                    <tr>
                                        <td>
                                            <a class="cx-link" href="<?= url('cuentas-por-pagar/' . (int)($cuenta['id'] ?? 0)) ?>">#<?= (int)($cuenta['id'] ?? 0) ?></a>
                                            <div class="cx-sub"><?= cxp_cash_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($cuenta['proveedor_id']) && !empty($cuenta['proveedor_nombre'])): ?>
                                                <a class="cx-link" href="<?= url('proveedores/' . (int)$cuenta['proveedor_id']) ?>"><?= cxp_cash_safe($cuenta['proveedor_nombre'] ?? null) ?></a>
                                                <div class="cx-sub"><?= cxp_cash_safe($cuenta['proveedor_rfc'] ?? null, 'Sin RFC') ?></div>
                                            <?php else: ?>
                                                <span class="cx-faint">Sin proveedor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($cuenta['compra_id'])): ?>
                                                <a class="cx-link" href="<?= url('compras/' . (int)$cuenta['compra_id']) ?>">#<?= (int)$cuenta['compra_id'] ?></a>
                                                <div class="cx-sub">
                                                    <?= cxp_cash_safe($cuenta['compra_folio'] ?? null, 'Sin folio') ?>
                                                    &middot; <?= cxp_cash_safe($cuenta['compra_estado'] ?? null, 'Sin estado') ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="cx-faint">Sin compra</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="cx-badge <?= $cEstadoClass ?>">
                                                <i class="fas <?= $cEstadoIcon ?>"></i>
                                                <?= $cEstadoLabel ?>
                                            </span>
                                            <div class="cx-sub mt-1"><?= cxp_cash_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin vencimiento') ?></div>
                                        </td>
                                        <td class="is-end"><?= cxp_cash_money($cuenta['total'] ?? 0) ?></td>
                                        <td class="is-end cx-strong"><?= cxp_cash_money($cuenta['saldo'] ?? 0) ?></td>
                                        <td>
                                            <?php if ($esElegible): ?>
                                                <span class="cx-badge cx-badge-ok"><i class="fas fa-circle-check"></i> S&iacute;, se puede</span>
                                                <div class="cx-sub mt-1"><?= cxp_cash_safe($cuenta['motivo_elegibilidad_caja'] ?? null) ?></div>
                                            <?php else: ?>
                                                <span class="cx-badge cx-badge-blocked"><i class="fas fa-ban"></i> Todav&iacute;a no</span>
                                                <div class="cx-sub mt-1"><?= cxp_cash_safe($cuenta['motivo_bloqueo_caja'] ?? null, 'No se puede pagar ahora.') ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
