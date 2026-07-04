<?php
/**
 * Tablero interno del motor de reservas: pagos online, conciliacion a Caja
 * y configuracion (motor + pasarela). Vista interna con layout estandar.
 */
$pagos = $pagos ?? [];
$resumen = $resumen ?? ['por_conciliar' => 0, 'monto_por_conciliar' => 0, 'conciliados' => 0, 'con_problema' => 0];
$credenciales = $credenciales ?? null;
$config = $config ?? [];
$urlPublica = $urlPublica ?? '';
$urlWebhook = $urlWebhook ?? '';

$mrSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$mrMoney = static function ($v) {
    return '$' . number_format((float) ($v ?? 0), 2);
};
$mrEstadoBadge = static function ($estado) {
    $map = [
        'pagado' => ['Por conciliar', 'background:rgba(245,158,11,.14);color:#92600A;'],
        'conciliado' => ['Conciliado', 'background:rgba(22,163,74,.12);color:#15803D;'],
        'pendiente' => ['Esperando pago', 'background:rgba(100,116,139,.12);color:#64748B;'],
        'reembolsado' => ['Reembolsado', 'background:rgba(59,111,214,.12);color:#3B6FD6;'],
        'fallido' => ['Requiere atencion', 'background:rgba(220,38,38,.12);color:#B91C1C;'],
        'expirado' => ['Expirado', 'background:rgba(100,116,139,.10);color:#94A3B8;'],
    ];
    return $map[$estado] ?? [$estado, 'background:rgba(100,116,139,.10);color:#64748B;'];
};
$publicoActivo = !empty($config['publico_activo']);
$pasarelaLista = $credenciales && !empty($credenciales['secret_configurado']) && !empty($credenciales['activo']);
?>

<style>
.mrv { --mrv-line: color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); padding: 18px 16px 40px; max-width: 1180px; margin: 0 auto; font-size: .92rem; }
.mrv h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.mrv .sub { margin: 0 0 16px; color: #6B7486; }
.mrv-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 16px; }
.mrv-stat { background: #fff; border: 1px solid var(--mrv-line); border-radius: 12px; padding: 12px 14px; }
.mrv-stat span { display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; }
.mrv-stat strong { display: block; margin-top: 4px; font-size: 1.3rem; color: var(--brand-secondary, #0F172A); }
.mrv-card { background: #fff; border: 1px solid var(--mrv-line); border-radius: 14px; margin-bottom: 16px; overflow: hidden; }
.mrv-card-head { padding: 13px 16px; border-bottom: 1px solid var(--mrv-line); display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; }
.mrv-card-head h2 { margin: 0; font-size: 1rem; color: var(--brand-primary, #1B2746); }
.mrv-card-head p { margin: 2px 0 0; font-size: .8rem; color: #6B7486; width: 100%; }
.mrv-badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; }
.mrv-table-wrap { overflow-x: auto; }
.mrv table { width: 100%; border-collapse: collapse; }
.mrv th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid var(--mrv-line); white-space: nowrap; }
.mrv td { padding: 11px 12px; border-bottom: 1px solid color-mix(in srgb, var(--mrv-line) 70%, transparent); vertical-align: middle; }
.mrv-btn { display: inline-flex; align-items: center; gap: 6px; min-height: 38px; padding: 0 14px; border: 0; border-radius: 9px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .84rem; font-weight: 700; }
.mrv-btn:hover { opacity: .92; }
.mrv-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid var(--mrv-line); }
.mrv-empty { padding: 26px 16px; text-align: center; color: #8A93A6; }
.mrv-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; padding: 16px; }
.mrv-field { display: grid; gap: 4px; }
.mrv-field.full { grid-column: 1 / -1; }
.mrv-field label { font-size: .76rem; font-weight: 700; color: #55607A; }
.mrv-field input, .mrv-field select, .mrv-field textarea { min-height: 42px; border: 1px solid #D8D4C9; border-radius: 9px; padding: 8px 11px; font-size: .92rem; width: 100%; font-family: inherit; }
.mrv-field .hint { font-size: .72rem; color: #8A93A6; }
.mrv-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 8px; }
.mrv-link { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 12px 16px; background: color-mix(in srgb, var(--brand-accent, #BD9441) 6%, #FBF9F3); border-top: 1px solid var(--mrv-line); font-size: .84rem; }
.mrv-link code { background: #fff; border: 1px solid var(--mrv-line); border-radius: 7px; padding: 6px 10px; font-size: .8rem; word-break: break-all; }
.mrv-check { display: flex; align-items: center; gap: 8px; font-size: .9rem; font-weight: 600; color: #2A3242; }
</style>

<div class="mrv">
    <h1>Motor de reservas online</h1>
    <p class="sub">Reservas desde tu pagina publica con anticipo pagado. El dinero de la pasarela se concilia a Caja desde aqui.</p>

    <?php if (function_exists('hotel_menu_module_enabled') && hotel_menu_module_enabled('promociones')): ?>
        <p style="margin:-6px 0 16px;">
            <a href="<?= url('motor-reservas/cupones') ?>" style="font-size:.86rem;font-weight:700;color:var(--brand-primary,#1B2746);text-decoration:none;">&#127991;&#65039; Cupones y promociones &rarr;</a>
        </p>
    <?php endif; ?>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="mrv-grid">
        <div class="mrv-stat"><span>Por conciliar a Caja</span><strong><?= (int) $resumen['por_conciliar'] ?> · <?= $mrMoney($resumen['monto_por_conciliar']) ?></strong></div>
        <div class="mrv-stat"><span>Conciliados</span><strong><?= (int) $resumen['conciliados'] ?></strong></div>
        <div class="mrv-stat"><span>Con problema</span><strong><?= (int) $resumen['con_problema'] ?></strong></div>
        <div class="mrv-stat"><span>Estado del motor</span><strong style="font-size:.95rem;">
            <?= $publicoActivo ? ($pasarelaLista ? '🟢 Recibiendo reservas' : '🟡 Activo sin pasarela') : '⚪ Pausado' ?>
        </strong></div>
    </div>

    <!-- Pagos online -->
    <div class="mrv-card">
        <div class="mrv-card-head">
            <h2>Pagos online</h2>
            <p>Los pagos "Por conciliar" ya estan cobrados en la pasarela; conciliarlos registra el anticipo real en Caja (requiere corte abierto).</p>
        </div>
        <div class="mrv-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Estado</th><th>Huesped</th><th>Reservacion</th><th>Monto</th><th>Pasarela</th><th>Fecha</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($pagos)): ?>
                    <tr><td colspan="7" class="mrv-empty">Aun no hay pagos online. Comparte tu link publico para recibir la primera reserva.</td></tr>
                <?php else: ?>
                    <?php foreach ($pagos as $pago): ?>
                        <?php [$badgeTexto, $badgeStyle] = $mrEstadoBadge((string) $pago['estado']); ?>
                        <tr>
                            <td><span class="mrv-badge" style="<?= $badgeStyle ?>"><?= $mrSafe($badgeTexto) ?></span></td>
                            <td>
                                <div style="font-weight:600;"><?= $mrSafe($pago['huesped_nombre'] ?: '—') ?></div>
                                <div style="font-size:.76rem;color:#8A93A6;"><?= $mrSafe($pago['huesped_telefono'] ?: '') ?></div>
                            </td>
                            <td>
                                <?php if (!empty($pago['reservacion_id'])): ?>
                                    <a href="<?= url('reservaciones/ver/' . (int) $pago['reservacion_id']) ?>" style="font-weight:700;color:var(--brand-primary,#1B2746);">#<?= (int) $pago['reservacion_id'] ?></a>
                                    <div style="font-size:.76rem;color:#8A93A6;"><?= $mrSafe($pago['fecha_entrada'] ?? '') ?> → <?= $mrSafe($pago['fecha_salida'] ?? '') ?></div>
                                <?php else: ?>
                                    <span style="color:#8A93A6;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:700;"><?= $mrMoney($pago['monto']) ?> <span style="font-size:.72rem;color:#8A93A6;"><?= $mrSafe($pago['moneda']) ?></span></td>
                            <td>
                                <div style="text-transform:capitalize;"><?= $mrSafe($pago['proveedor']) ?></div>
                                <div style="font-size:.72rem;color:#8A93A6;word-break:break-all;"><?= $mrSafe(mb_substr((string) $pago['proveedor_pago_id'], 0, 24)) ?></div>
                            </td>
                            <td style="font-size:.8rem;color:#6B7486;white-space:nowrap;"><?= $mrSafe(date('d/m/Y H:i', strtotime((string) $pago['created_at']))) ?></td>
                            <td style="text-align:right;">
                                <?php if (($pago['estado'] ?? '') === 'pagado' && !empty($pago['reservacion_id'])): ?>
                                    <form method="POST" action="<?= url('motor-reservas/pagos/' . (int) $pago['id'] . '/conciliar') ?>"
                                          onsubmit="return confirm('Conciliar este pago registra el anticipo de <?= $mrMoney($pago['monto']) ?> en Caja. ¿Continuar?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="mrv-btn">Conciliar a Caja</button>
                                    </form>
                                <?php elseif (($pago['estado'] ?? '') === 'conciliado' && !empty($pago['conciliado_at'])): ?>
                                    <span style="font-size:.74rem;color:#15803D;">✓ <?= $mrSafe(date('d/m/Y H:i', strtotime((string) $pago['conciliado_at']))) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Configuracion -->
    <div class="mrv-card">
        <div class="mrv-card-head">
            <h2>Configuracion del motor</h2>
            <p>Enciende tu pagina publica, define el anticipo y conecta tu pasarela de pago.</p>
        </div>
        <form method="POST" action="<?= url('motor-reservas/configuracion') ?>" class="mrv-form" autocomplete="off">
            <?= csrf_field() ?>

            <div class="mrv-field full">
                <label class="mrv-check">
                    <input type="checkbox" name="publico_activo" value="1" <?= $publicoActivo ? 'checked' : '' ?> style="min-height:auto;width:18px;height:18px;">
                    Pagina publica de reservas encendida
                </label>
            </div>

            <div class="mrv-field">
                <label for="anticipo_tipo">Tipo de anticipo</label>
                <select id="anticipo_tipo" name="anticipo_tipo">
                    <option value="porcentaje" <?= ($config['anticipo_tipo'] ?? '') === 'porcentaje' ? 'selected' : '' ?>>Porcentaje del total</option>
                    <option value="primera_noche" <?= ($config['anticipo_tipo'] ?? '') === 'primera_noche' ? 'selected' : '' ?>>Primera noche</option>
                    <option value="monto_fijo" <?= ($config['anticipo_tipo'] ?? '') === 'monto_fijo' ? 'selected' : '' ?>>Monto fijo</option>
                </select>
            </div>
            <div class="mrv-field">
                <label for="anticipo_valor">Valor del anticipo</label>
                <input type="number" step="0.01" min="0" id="anticipo_valor" name="anticipo_valor" value="<?= $mrSafe(number_format((float) ($config['anticipo_valor'] ?? 30), 2, '.', '')) ?>">
                <span class="hint">% si es porcentaje; pesos si es monto fijo.</span>
            </div>
            <div class="mrv-field">
                <label for="min_noches">Noches minimas</label>
                <input type="number" min="1" id="min_noches" name="min_noches" value="<?= (int) ($config['min_noches'] ?? 1) ?>">
            </div>
            <div class="mrv-field">
                <label for="max_noches">Noches maximas</label>
                <input type="number" min="1" id="max_noches" name="max_noches" value="<?= (int) ($config['max_noches'] ?? 30) ?>">
            </div>
            <div class="mrv-field">
                <label for="anticipacion_max_dias">Anticipacion maxima (dias)</label>
                <input type="number" min="1" id="anticipacion_max_dias" name="anticipacion_max_dias" value="<?= (int) ($config['anticipacion_max_dias'] ?? 180) ?>">
            </div>
            <div class="mrv-field full">
                <label for="politica_texto">Politica visible al huesped</label>
                <textarea id="politica_texto" name="politica_texto" rows="2" maxlength="500"><?= $mrSafe($config['politica_texto'] ?? '') ?></textarea>
            </div>

            <div class="mrv-field">
                <label for="proveedor">Pasarela de pago</label>
                <select id="proveedor" name="proveedor">
                    <option value="stripe" <?= ($credenciales['proveedor'] ?? 'stripe') === 'stripe' ? 'selected' : '' ?>>Stripe</option>
                    <option value="mercadopago" <?= ($credenciales['proveedor'] ?? '') === 'mercadopago' ? 'selected' : '' ?>>MercadoPago</option>
                </select>
            </div>
            <div class="mrv-field">
                <label for="modo">Modo</label>
                <select id="modo" name="modo">
                    <option value="test" <?= ($credenciales['modo'] ?? 'test') === 'test' ? 'selected' : '' ?>>Pruebas (test)</option>
                    <option value="live" <?= ($credenciales['modo'] ?? '') === 'live' ? 'selected' : '' ?>>Produccion (live)</option>
                </select>
            </div>
            <div class="mrv-field">
                <label for="public_key">Llave publica</label>
                <input type="text" id="public_key" name="public_key" value="<?= $mrSafe($credenciales['public_key'] ?? '') ?>" placeholder="pk_test_... / APP_USR-...">
            </div>
            <div class="mrv-field">
                <label for="secret_key">Llave secreta <?= !empty($credenciales['secret_configurado']) ? '· configurada ✓' : '' ?></label>
                <input type="password" id="secret_key" name="secret_key" placeholder="<?= !empty($credenciales['secret_configurado']) ? 'Dejar vacio para conservar la actual' : 'sk_test_... / access token' ?>" autocomplete="new-password">
                <span class="hint">Se guarda cifrada; nunca se vuelve a mostrar.</span>
            </div>
            <div class="mrv-field">
                <label for="webhook_secret">Webhook secret (Stripe) <?= !empty($credenciales['webhook_configurado']) ? '· configurado ✓' : '' ?></label>
                <input type="password" id="webhook_secret" name="webhook_secret" placeholder="whsec_..." autocomplete="new-password">
                <span class="hint">Solo Stripe. MercadoPago se verifica consultando su API.</span>
            </div>
            <div class="mrv-field">
                <label class="mrv-check" style="margin-top:20px;">
                    <input type="checkbox" name="pasarela_activa" value="1" <?= empty($credenciales) || !empty($credenciales['activo']) ? 'checked' : '' ?> style="min-height:auto;width:18px;height:18px;">
                    Pasarela activa
                </label>
            </div>

            <div class="mrv-actions">
                <button type="submit" class="mrv-btn">Guardar configuracion</button>
            </div>
        </form>

        <div class="mrv-link">
            <strong>Tu pagina publica:</strong>
            <code id="mrv-url-publica"><?= $mrSafe($urlPublica) ?></code>
            <button type="button" class="mrv-btn sec" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('mrv-url-publica').textContent).then(function(){ this.textContent='Copiado ✓'; }.bind(this));">Copiar link</button>
            <span style="width:100%;font-size:.76rem;color:#8A93A6;">Webhook para tu pasarela: <code style="font-size:.74rem;"><?= $mrSafe($urlWebhook) ?></code></span>
        </div>
    </div>
</div>
