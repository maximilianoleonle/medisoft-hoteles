<?php
$cuentas = $cuentas ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$corte = $corte ?? null;
$tablaDisponible = $tablaDisponible ?? false;
$tipoCobroDisponible = $tipoCobroDisponible ?? false;

if (!function_exists('cxc_cash_safe')) {
    function cxc_cash_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxc_cash_money')) {
    function cxc_cash_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('cxc_cash_estado_meta')) {
    function cxc_cash_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente'  => ['Pendiente', 'is-pendiente', 'fa-clock'],
            'parcial'    => ['Parcial', 'is-parcial', 'fa-circle-half-stroke'],
            'vencida'    => ['Vencida', 'is-vencida', 'fa-triangle-exclamation'],
            'liquidada'  => ['Liquidada', 'is-liquidada', 'fa-circle-check'],
            'cancelada'  => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
            'incobrable' => ['Incobrable', 'is-incobrable', 'fa-ban'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
?>

<style>
.cxc-cash-page {
    --cx-brand: var(--brand-primary, #1B2746);
    --cx-brand-2: var(--brand-secondary, #0F172A);
    --cx-gold: var(--brand-accent, #BD9441);
    --cx-gold-soft: color-mix(in srgb, var(--cx-gold) 15%, #FFFFFF);
    --cx-gold-line: color-mix(in srgb, var(--cx-gold) 42%, #E4D4B0);
    --cx-gold-ink: color-mix(in srgb, var(--cx-gold) 78%, var(--cx-brand));
    --cx-ivory: #F6F2EA; --cx-ivory-2: #FBF8F2;
    --cx-surface: #FFFFFF; --cx-surface-warm: #FCFAF5;
    --cx-border: color-mix(in srgb, var(--cx-brand) 7%, #E7E1D4);
    --cx-ring: color-mix(in srgb, var(--cx-gold) 32%, transparent);
    --cx-text: color-mix(in srgb, var(--cx-brand) 34%, #647080);
    --cx-text-soft: color-mix(in srgb, var(--cx-brand) 24%, #7B8492);
    --cx-muted: #7A8493; --cx-heading: color-mix(in srgb, var(--cx-brand) 62%, #6B7280);
    --cx-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cx-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cx-success: #1E9E63; --cx-success-bg: #E7F4EC;
    --cx-warning: #C2841C; --cx-warning-bg: #FAF0DC;
    --cx-danger: #B4392B; --cx-danger-bg: #F8EAE5;
    --cx-info: #2F77E0; --cx-info-bg: #E6EFFC;
    min-height: 100%; color: var(--cx-text); font-family: var(--cx-sans);
    background: radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cx-gold) 8%, transparent), transparent 60%), linear-gradient(180deg, var(--cx-ivory-2), var(--cx-ivory));
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.cxc-cash-page .cx-shell { display: grid; gap: 14px; }
.cxc-cash-page .cx-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.cxc-cash-page .cx-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cx-gold), var(--cx-brand) 54%, color-mix(in srgb, var(--cx-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cx-brand) 72%, transparent); }
.cxc-cash-page .cx-kicker { margin: 0 0 2px; color: var(--cx-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.cxc-cash-page .cx-title { margin: 0; font-family: var(--cx-serif); color: var(--cx-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.cxc-cash-page .cx-subtitle { max-width: 52rem; margin: 8px 0 0; color: var(--cx-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.cxc-cash-page .cx-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.cxc-cash-page .cx-stat { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.cxc-cash-page .cx-stat-label { color: var(--cx-muted); font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.cxc-cash-page .cx-stat-value { margin-top: 2px; font-family: var(--cx-serif); font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: var(--cx-heading); }

.cxc-cash-page .cx-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.cxc-cash-page .cx-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease; }
.cxc-cash-page .cx-btn:hover { transform: translateY(-1px); }
.cxc-cash-page .cx-btn-brand { background: linear-gradient(135deg, var(--cx-brand), var(--cx-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--cx-brand) 60%, transparent); }
.cxc-cash-page .cx-btn-muted { background: var(--cx-surface); border-color: var(--cx-border); color: var(--cx-muted); }

.cxc-cash-page .cx-panel { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.cxc-cash-page .cx-meta-label { font-size: .66rem; color: var(--cx-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.cxc-cash-page .cx-meta-value { margin-top: 3px; font-weight: 700; color: var(--cx-heading); }
.cxc-cash-page .cx-sub { color: var(--cx-muted); font-size: .72rem; }
.cxc-cash-page .cx-strong { font-weight: 700; color: var(--cx-heading); }

.cxc-cash-page .cx-control { width: 100%; min-height: 40px; border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--cx-text); font-weight: 600; font-size: .88rem; transition: border-color .16s ease, box-shadow .16s ease; }
.cxc-cash-page .cx-control:focus { border-color: var(--cx-gold); box-shadow: 0 0 0 3px var(--cx-ring); outline: none; }
.cxc-cash-page .cx-control::placeholder { color: color-mix(in srgb, var(--cx-muted) 72%, #fff); font-weight: 600; }
.cxc-cash-page select.cx-control { cursor: pointer; }
.cxc-cash-page .cx-filter-form { display: grid; grid-template-columns: minmax(240px, 1fr) minmax(150px, 180px) 42px; max-width: 650px; gap: 10px; align-items: center; }
.cxc-cash-page .cx-filter-form .cx-btn-brand { width: 42px; min-width: 42px; padding: 0; border-radius: 12px; overflow: hidden; font-size: 0; }
.cxc-cash-page .cx-filter-form .cx-btn-brand i { margin: 0; font-size: .86rem; }

.cxc-cash-page .cx-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
.cxc-cash-page .cx-badge.is-pendiente { color: color-mix(in srgb, var(--cx-info) 80%, var(--cx-brand)); background: var(--cx-info-bg); border-color: color-mix(in srgb, var(--cx-info) 26%, #fff); }
.cxc-cash-page .cx-badge.is-parcial { color: color-mix(in srgb, var(--cx-warning) 82%, var(--cx-brand)); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }
.cxc-cash-page .cx-badge.is-vencida { color: color-mix(in srgb, var(--cx-danger) 82%, var(--cx-brand)); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxc-cash-page .cx-badge.is-liquidada { color: color-mix(in srgb, var(--cx-success) 78%, var(--cx-brand)); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxc-cash-page .cx-badge.is-incobrable { color: color-mix(in srgb, var(--cx-danger) 82%, var(--cx-brand)); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxc-cash-page .cx-badge.is-cancelada, .cxc-cash-page .cx-badge.is-soft { color: var(--cx-muted); background: var(--cx-surface-warm); border-color: var(--cx-border); }
.cxc-cash-page .cx-badge-ok { color: color-mix(in srgb, var(--cx-success) 78%, var(--cx-brand)); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxc-cash-page .cx-badge-blocked { color: color-mix(in srgb, var(--cx-warning) 82%, var(--cx-brand)); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }

.cxc-cash-page .cx-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 15px 18px; border-bottom: 1px solid var(--cx-border); }
.cxc-cash-page .cx-panel-title { font-family: var(--cx-serif); font-size: 1.35rem; font-weight: 700; color: var(--cx-heading); }
.cxc-cash-page .cx-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.cxc-cash-page .cx-table thead { background: var(--cx-surface-warm); border-bottom: 1px solid var(--cx-border); }
.cxc-cash-page .cx-table th { padding: 12px 14px; color: var(--cx-muted); font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.cxc-cash-page .cx-table th.is-end, .cxc-cash-page .cx-table td.is-end { text-align: right; }
.cxc-cash-page .cx-table td { padding: 13px 14px; border-bottom: 1px solid var(--cx-border); vertical-align: top; }
.cxc-cash-page .cx-table tbody tr:last-child td { border-bottom: 0; }
.cxc-cash-page .cx-table tbody tr:hover { background: var(--cx-ivory-2); }
.cxc-cash-page .cx-link { font-weight: 700; color: var(--cx-heading); text-decoration: none; }
.cxc-cash-page .cx-link:hover { text-decoration: underline; text-decoration-color: var(--cx-gold); text-underline-offset: 3px; }
.cxc-cash-page .cx-faint { color: var(--cx-muted); }

.cxc-cash-page .cx-empty { text-align: center; padding: 40px 18px; }
.cxc-cash-page .cx-empty-icon { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 18px; display: grid; place-items: center; background: var(--cx-gold-soft); color: var(--cx-gold-ink); font-size: 1.25rem; }
.cxc-cash-page .cx-empty h2 { color: var(--cx-heading); font-size: 1.05rem; font-weight: 700; }
.cxc-cash-page .cx-empty p { color: var(--cx-muted); font-size: .88rem; margin-top: 6px; }
.cxc-cash-page .cx-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--cx-gold-soft); border: 1px solid var(--cx-gold-line); border-radius: 16px; }
.cxc-cash-page .cx-notice i { color: var(--cx-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.cxc-cash-page .cx-notice strong { color: var(--cx-heading); display: block; margin-bottom: 2px; }
.cxc-cash-page .cx-notice p { color: var(--cx-muted); font-size: .88rem; margin: 0; }

@media (max-width: 980px) { .cxc-cash-page .cx-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cxc-cash-page .cx-filter-form { grid-template-columns: 1fr; } .cxc-cash-page .cx-filter-form .cx-btn-brand { justify-self: end; } }
</style>

<div class="cxc-cash-page p-4 sm:p-6">
    <div class="cx-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="cx-title-lockup">
                <div class="cx-hero-icon"><i class="fas fa-cash-register"></i></div>
                <div>
                    <p class="cx-kicker">Cobros a hu&eacute;spedes</p>
                    <h1 class="cx-title">Simulador de cobros</h1>
                    <p class="cx-subtitle">Te dice qu&eacute; cuentas podr&iacute;as cobrar ahora mismo con Caja. Esta pantalla solo consulta: no registra cobros ni cambia saldos.</p>
                </div>
            </div>
            <span class="cx-badge is-soft"><i class="fas fa-eye"></i> Solo consulta</span>
        </section>

        <section class="cx-toolbar">
            <div class="flex flex-wrap gap-2">
                <?php $back_arrow_href = back_url('cuentas-por-cobrar/operativas'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a class="cx-btn cx-btn-muted ms-back-legacy" href="<?= back_url('cuentas-por-cobrar/operativas') ?>"><i class="fas fa-arrow-left"></i> Volver a cuentas por cobrar</a>
                <a class="cx-btn cx-btn-muted" href="<?= url('caja') ?>"><i class="fas fa-cash-register"></i> Ver Caja</a>
            </div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="cx-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>El simulador todav&iacute;a no est&aacute; disponible.</strong>
                    <p>Faltan datos base de cuentas por cobrar o de Caja para poder evaluar los cobros.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="cx-stats">
                <div class="cx-stat"><p class="cx-stat-label">Cuentas</p><p class="cx-stat-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p></div>
                <div class="cx-stat"><p class="cx-stat-label">Se pueden cobrar</p><p class="cx-stat-value"><?= number_format((int)($resumen['elegibles'] ?? 0)) ?></p></div>
                <div class="cx-stat"><p class="cx-stat-label">Saldo que se podr&iacute;a cobrar</p><p class="cx-stat-value"><?= cxc_cash_money($resumen['saldo_elegible'] ?? 0) ?></p></div>
                <div class="cx-stat"><p class="cx-stat-label">Corte abierto</p><p class="cx-stat-value"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'No' ?></p></div>
            </section>

            <div class="cx-panel p-5">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div><div class="cx-meta-label">Corte</div><div class="cx-meta-value"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'Sin corte abierto' ?></div></div>
                    <div><div class="cx-meta-label">Caja</div><div class="cx-meta-value"><?= cxc_cash_safe($corte['caja_nombre'] ?? null, 'No disponible') ?></div><div class="cx-sub"><?= cxc_cash_safe($corte['caja_ubicacion'] ?? null, 'Sin ubicación') ?></div></div>
                    <div><div class="cx-meta-label">Apertura</div><div class="cx-meta-value"><?= cxc_cash_safe($corte['fecha_apertura'] ?? null, 'Pendiente') ?></div></div>
                    <div>
                        <div class="cx-meta-label">Cobro en Caja</div>
                        <?php if ($tipoCobroDisponible): ?>
                            <span class="cx-badge cx-badge-ok mt-1"><i class="fas fa-circle-check"></i> Disponible</span>
                        <?php else: ?>
                            <span class="cx-badge cx-badge-blocked mt-1"><i class="fas fa-triangle-exclamation"></i> Pendiente</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="cx-meta-label">Estado</div>
                        <?php if (!empty($corte)): ?>
                            <span class="cx-badge cx-badge-ok mt-1"><i class="fas fa-circle-check"></i> Abierto</span>
                        <?php else: ?>
                            <span class="cx-badge cx-badge-blocked mt-1"><i class="fas fa-triangle-exclamation"></i> Bloquea cobros</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <section class="cx-panel p-3 md:p-4">
                <form method="GET" action="<?= url('cuentas-por-cobrar/simulador-caja') ?>" class="cx-filter-form" data-auto-filter-form>
                    <input class="cx-control" type="search" name="buscar" value="<?= cxc_cash_safe($buscar, '') ?>" placeholder="Buscar por cuenta, folio, reservaci&oacute;n, hu&eacute;sped o factura">
                    <select class="cx-control" name="estado">
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="parcial" <?= $estado === 'parcial' ? 'selected' : '' ?>>Parciales</option>
                        <option value="vencida" <?= $estado === 'vencida' ? 'selected' : '' ?>>Vencidas</option>
                        <option value="liquidada" <?= $estado === 'liquidada' ? 'selected' : '' ?>>Liquidadas</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        <option value="incobrable" <?= $estado === 'incobrable' ? 'selected' : '' ?>>Incobrables</option>
                    </select>
                    <button class="cx-btn cx-btn-brand" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                </form>
            </section>

            <div class="cx-panel overflow-hidden">
                <div class="cx-panel-head"><h2 class="cx-panel-title">Cuentas evaluadas</h2></div>
                <?php if (empty($cuentas)): ?>
                    <div class="cx-empty">
                        <div class="cx-empty-icon"><i class="fas fa-hand-holding-dollar"></i></div>
                        <h2>No hay cuentas para evaluar</h2>
                        <p>Cambia los filtros o genera una cuenta por cobrar desde una reservaci&oacute;n con saldo.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="cx-table">
                            <thead>
                                <tr><th>Cuenta</th><th>Hu&eacute;sped</th><th>Origen</th><th>Estado</th><th class="is-end">Total</th><th class="is-end">Saldo</th><th>&iquest;Se puede cobrar?</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cuentas as $cuenta): ?>
                                    <?php
                                    $esElegible = !empty($cuenta['es_elegible_caja']);
                                    [$eLabel, $eClass, $eIcon] = cxc_cash_estado_meta($cuenta['estado'] ?? null);
                                    ?>
                                    <tr>
                                        <td>
                                            <a class="cx-link" href="<?= url('cuentas-por-cobrar/operativas/' . (int)($cuenta['id'] ?? 0)) ?>">#<?= (int)($cuenta['id'] ?? 0) ?></a>
                                            <div class="cx-sub"><?= cxc_cash_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($cuenta['huesped_id']) && !empty($cuenta['huesped_nombre'])): ?>
                                                <a class="cx-link" href="<?= url('huespedes/' . (int)$cuenta['huesped_id']) ?>"><?= cxc_cash_safe($cuenta['huesped_nombre'] ?? null) ?></a>
                                                <div class="cx-sub"><?= cxc_cash_safe($cuenta['huesped_telefono'] ?? null, 'Sin teléfono') ?></div>
                                            <?php else: ?>
                                                <span class="cx-faint">Sin hu&eacute;sped</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= cxc_cash_safe($cuenta['origen_tipo'] ?? null) ?>
                                            <?php if (!empty($cuenta['origen_id'])): ?><div class="cx-sub">#<?= (int)$cuenta['origen_id'] ?></div><?php endif; ?>
                                            <?php if (!empty($cuenta['reservacion_id'])): ?><div class="cx-sub">Reservaci&oacute;n #<?= (int)$cuenta['reservacion_id'] ?></div><?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="cx-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span>
                                            <div class="cx-sub mt-1"><?= cxc_cash_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin vencimiento') ?></div>
                                        </td>
                                        <td class="is-end"><?= cxc_cash_money($cuenta['total'] ?? 0) ?></td>
                                        <td class="is-end cx-strong"><?= cxc_cash_money($cuenta['saldo'] ?? 0) ?></td>
                                        <td>
                                            <?php if ($esElegible): ?>
                                                <span class="cx-badge cx-badge-ok"><i class="fas fa-circle-check"></i> S&iacute;, se puede</span>
                                                <div class="cx-sub mt-1"><?= cxc_cash_safe($cuenta['motivo_elegibilidad_caja'] ?? null) ?></div>
                                            <?php else: ?>
                                                <span class="cx-badge cx-badge-blocked"><i class="fas fa-ban"></i> Todav&iacute;a no</span>
                                                <div class="cx-sub mt-1"><?= cxc_cash_safe($cuenta['motivo_bloqueo_caja'] ?? null, 'No se puede cobrar ahora.') ?></div>
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
