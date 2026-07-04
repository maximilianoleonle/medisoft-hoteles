<?php
/**
 * Huesped frecuente (bloque lealtad).
 */
$frecuentes = $frecuentes ?? [];
$config = $config ?? ['min_estancias' => 3, 'descuento_pct' => 10, 'vigencia_dias' => 90];
$urlMotor = $urlMotor ?? '';
$promocionesActivo = (bool) ($promocionesActivo ?? false);

$leSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
?>

<style>
.le { max-width: 1080px; margin: 0 auto; padding: 18px 16px 40px; font-size: .92rem; }
.le h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.le .sub { margin: 0 0 16px; color: #6B7486; }
.le-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 14px; overflow: hidden; margin-bottom: 16px; }
.le table { width: 100%; border-collapse: collapse; }
.le th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid #EDE9DF; white-space: nowrap; }
.le td { padding: 11px 12px; border-bottom: 1px solid #F2EFE7; vertical-align: middle; }
.le-vacio { padding: 30px 16px; text-align: center; color: #8A93A6; }
.le-btn { display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 8px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .8rem; font-weight: 700; text-decoration: none; }
.le-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid #D8D4C9; }
.le-codigo { font-family: ui-monospace, monospace; font-weight: 700; letter-spacing: .06em; color: var(--brand-primary, #1B2746); }
.le-badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
.le-config { padding: 16px; }
.le-config h2 { margin: 0 0 10px; font-size: 1rem; color: var(--brand-primary, #1B2746); }
.le-config .fila { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; align-items: end; }
.le-config label { display: block; font-size: .76rem; font-weight: 700; color: #55607A; margin-bottom: 4px; }
.le-config input { width: 100%; min-height: 40px; border: 1px solid #D8D4C9; border-radius: 8px; padding: 0 10px; font-size: .9rem; }
.le-acciones { display: flex; gap: 6px; flex-wrap: wrap; }
</style>

<div class="le">
    <h1>Huésped frecuente</h1>
    <p class="sub">Tus huéspedes que regresan, listos para recibir un cupón personal de agradecimiento canjeable en tu página de reservas.</p>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <?php if (!$promocionesActivo): ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;background:rgba(245,158,11,.1);color:#92600A;border:1px solid rgba(245,158,11,.25);">
            Para que el huésped pueda canjear su cupón en línea necesitas activos el <strong>motor de reservas</strong> y el bloque de <strong>cupones y promociones</strong>.
        </div>
    <?php endif; ?>

    <div class="le-card le-config">
        <h2>Reglas del programa</h2>
        <form method="POST" action="<?= url('lealtad/config') ?>">
            <?= csrf_field() ?>
            <div class="fila">
                <div>
                    <label for="le-min">Frecuente a partir de</label>
                    <input type="number" id="le-min" name="min_estancias" min="1" max="50" step="1" value="<?= (int) $config['min_estancias'] ?>">
                </div>
                <div>
                    <label for="le-pct">% de descuento del cupón</label>
                    <input type="number" id="le-pct" name="descuento_pct" min="1" max="100" step="1" value="<?= (int) $config['descuento_pct'] ?>">
                </div>
                <div>
                    <label for="le-vig">Vigencia del cupón (días)</label>
                    <input type="number" id="le-vig" name="vigencia_dias" min="7" max="365" step="1" value="<?= (int) $config['vigencia_dias'] ?>">
                </div>
                <div>
                    <button type="submit" class="le-btn" style="min-height:40px;">Guardar</button>
                </div>
            </div>
            <p style="font-size:.74rem;color:#8A93A6;margin:10px 0 0;">Cada cupón es personal: un solo uso, con vigencia, y aplica al hospedaje (no a extras). Un huésped no puede tener dos cupones vigentes a la vez.</p>
        </form>
    </div>

    <div class="le-card">
        <table>
            <thead>
                <tr>
                    <th>Huésped</th><th>Estancias</th><th>Última visita</th><th>Cupón vigente</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($frecuentes)): ?>
                <tr><td colspan="5" class="le-vacio">
                    Aún no hay huéspedes con <?= (int) $config['min_estancias'] ?>+ estancias completadas. Baja el mínimo arriba si quieres ver más candidatos.
                </td></tr>
            <?php else: ?>
                <?php foreach ($frecuentes as $f): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?= $leSafe($f['nombre_completo']) ?></div>
                            <div style="font-size:.76rem;color:#8A93A6;"><?= $leSafe($f['email'] ?: ($f['telefono'] ?: '')) ?></div>
                        </td>
                        <td><span class="le-badge" style="background:rgba(37,99,235,.08);color:#1D4ED8;">★ <?= (int) $f['estancias'] ?></span></td>
                        <td style="white-space:nowrap;color:#6B7486;"><?= $f['ultima_salida'] ? $leSafe(date('d/m/Y', strtotime((string) $f['ultima_salida']))) : '—' ?></td>
                        <td>
                            <?php if (!empty($f['cupon_codigo'])): ?>
                                <div class="le-codigo"><?= $leSafe($f['cupon_codigo']) ?></div>
                                <div style="font-size:.74rem;color:#8A93A6;">
                                    <?= $leSafe(rtrim(rtrim(number_format((float) $f['cupon_valor'], 2), '0'), '.')) ?>%
                                    <?= $f['cupon_vigencia'] ? ' · vence ' . $leSafe(date('d/m/Y', strtotime((string) $f['cupon_vigencia']))) : '' ?>
                                    <?= $f['correo_enviado_at'] ? ' · ✉ enviado' : '' ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#94A3B8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="le-acciones">
                                <?php if (empty($f['cupon_codigo'])): ?>
                                    <form method="POST" action="<?= url('lealtad/generar/' . (int) $f['id']) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="le-btn">Generar cupón</button>
                                    </form>
                                <?php else: ?>
                                    <?php if (!empty($f['email'])): ?>
                                        <form method="POST" action="<?= url('lealtad/enviar/' . (int) $f['lealtad_id']) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="le-btn"><?= $f['correo_enviado_at'] ? 'Reenviar correo' : 'Enviar por correo' ?></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!empty($f['telefono'])): ?>
                                        <a class="le-btn sec" target="_blank" rel="noopener"
                                           href="https://wa.me/52<?= $leSafe(preg_replace('/\D/', '', substr((string) $f['telefono'], -10))) ?>?text=<?= rawurlencode('Hola ' . $f['nombre_completo'] . ', gracias por tu preferencia. Te regalamos un cupon de ' . rtrim(rtrim(number_format((float) $f['cupon_valor'], 2), '0'), '.') . '% para tu proxima reserva: ' . $f['cupon_codigo'] . '. Usalo aqui: ' . $urlMotor) ?>">
                                            WhatsApp
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="le-btn sec"
                                            data-codigo="<?= $leSafe($f['cupon_codigo']) ?>"
                                            onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.codigo).then(() => { this.textContent='Copiado ✓'; })">
                                        Copiar código
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
