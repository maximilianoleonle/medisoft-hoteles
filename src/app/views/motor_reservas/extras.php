<?php
/**
 * Extras del motor de reservas (bloque upsells).
 */
require_once dirname(__DIR__, 2) . '/services/MotorExtraService.php';

$extras = $extras ?? [];

$exSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
?>

<style>
.ext { max-width: 1080px; margin: 0 auto; padding: 18px 16px 40px; font-size: .92rem; }
.ext h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.ext .sub { margin: 0 0 16px; color: #6B7486; }
.ext-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 14px; overflow: hidden; margin-bottom: 16px; }
.ext table { width: 100%; border-collapse: collapse; }
.ext th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid #EDE9DF; white-space: nowrap; }
.ext td { padding: 11px 12px; border-bottom: 1px solid #F2EFE7; vertical-align: middle; }
.ext-badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
.ext-btn { display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 8px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .8rem; font-weight: 700; text-decoration: none; }
.ext-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid #D8D4C9; }
.ext-vacio { padding: 30px 16px; text-align: center; color: #8A93A6; }
.ext-form { padding: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; align-items: end; }
.ext-form label { display: block; font-size: .76rem; font-weight: 700; color: #55607A; margin-bottom: 4px; }
.ext-form input, .ext-form select { width: 100%; min-height: 40px; border: 1px solid #D8D4C9; border-radius: 8px; padding: 0 10px; font-size: .9rem; }
.ext-form .hint { grid-column: 1 / -1; font-size: .74rem; color: #8A93A6; margin: 0; }
</style>

<div class="ext">
    <a href="<?= url('motor-reservas') ?>" style="font-size:.82rem;color:#6B7486;text-decoration:none;">&larr; Motor de reservas</a>
    <h1>Extras y upselling</h1>
    <p class="sub">Lo que el huésped puede agregar a su reserva en línea (desayuno, late checkout, decoración). El importe se suma al total de la estancia.</p>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="ext-card">
        <form class="ext-form" method="POST" action="<?= url('motor-reservas/extras/crear') ?>">
            <?= csrf_field() ?>
            <div style="grid-column: span 2;">
                <label for="ext-nombre">Nombre</label>
                <input type="text" id="ext-nombre" name="nombre" maxlength="100" required placeholder="Desayuno buffet">
            </div>
            <div style="grid-column: span 2;">
                <label for="ext-desc">Descripción (opcional)</label>
                <input type="text" id="ext-desc" name="descripcion" maxlength="255" placeholder="Incluye café, jugo y fruta">
            </div>
            <div>
                <label for="ext-precio">Precio</label>
                <input type="number" id="ext-precio" name="precio" min="1" step="0.01" required placeholder="120.00">
            </div>
            <div>
                <label for="ext-tipo">Se cobra</label>
                <select id="ext-tipo" name="tipo_cobro">
                    <option value="por_reserva">Por reserva</option>
                    <option value="por_noche">Por noche</option>
                    <option value="por_persona">Por persona</option>
                    <option value="por_persona_noche">Por persona / noche</option>
                </select>
            </div>
            <div>
                <button type="submit" class="ext-btn" style="min-height:40px;">Crear extra</button>
            </div>
            <p class="hint">El importe se calcula en automático con las noches y personas de cada reserva. Los cupones de descuento no aplican a extras.</p>
        </form>
    </div>

    <div class="ext-card">
        <table>
            <thead>
                <tr>
                    <th>Extra</th><th>Precio</th><th>Se cobra</th><th>Estado</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($extras)): ?>
                <tr><td colspan="5" class="ext-vacio">Todavía no tienes extras. Crea el primero arriba, por ejemplo "Desayuno" a $120 por persona / noche.</td></tr>
            <?php else: ?>
                <?php foreach ($extras as $e): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?= $exSafe($e['nombre']) ?></div>
                            <?php if (!empty($e['descripcion'])): ?>
                                <div style="font-size:.78rem;color:#8A93A6;"><?= $exSafe($e['descripcion']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600;white-space:nowrap;">$<?= number_format((float) $e['precio'], 2) ?></td>
                        <td style="font-size:.82rem;color:#6B7486;white-space:nowrap;"><?= $exSafe(MotorExtraService::etiquetaTipo((string) $e['tipo_cobro'])) ?></td>
                        <td>
                            <?php if ((int) $e['activo']): ?>
                                <span class="ext-badge" style="background:rgba(22,163,74,.12);color:#15803D;">Visible</span>
                            <?php else: ?>
                                <span class="ext-badge" style="background:rgba(100,116,139,.12);color:#64748B;">Oculto</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="<?= url('motor-reservas/extras/' . (int) $e['id'] . '/alternar') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="ext-btn sec"><?= (int) $e['activo'] ? 'Ocultar' : 'Mostrar' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
