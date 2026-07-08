<?php
/**
 * Cupones del motor de reservas (bloque promociones).
 */
$cupones = $cupones ?? [];

$cpSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$cpBeneficio = static function ($c) {
    return $c['tipo'] === 'porcentaje'
        ? rtrim(rtrim(number_format((float) $c['valor'], 2), '0'), '.') . '%'
        : '$' . number_format((float) $c['valor'], 2);
};
$cpVigencia = static function ($c) {
    $desde = $c['vigente_desde'] ? date('d/m/Y', strtotime((string) $c['vigente_desde'])) : null;
    $hasta = $c['vigente_hasta'] ? date('d/m/Y', strtotime((string) $c['vigente_hasta'])) : null;
    if ($desde && $hasta) return $desde . ' – ' . $hasta;
    if ($hasta) return 'hasta ' . $hasta;
    if ($desde) return 'desde ' . $desde;
    return 'Sin límite';
};
?>

<style>
.cup { max-width: 1080px; margin: 0 auto; padding: 18px 16px 40px; font-size: .92rem; }
.cup h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.cup .sub { margin: 0 0 16px; color: #6B7486; }
.cup-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 14px; overflow: hidden; margin-bottom: 16px; }
.cup table { width: 100%; border-collapse: collapse; }
.cup th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid #EDE9DF; white-space: nowrap; }
.cup td { padding: 11px 12px; border-bottom: 1px solid #F2EFE7; vertical-align: middle; }
.cup-codigo { font-family: ui-monospace, monospace; font-weight: 700; letter-spacing: .06em; color: var(--brand-primary, #1B2746); }
.cup-badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
.cup-btn { display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 8px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .8rem; font-weight: 700; text-decoration: none; }
.cup-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid #D8D4C9; }
.cup-vacio { padding: 30px 16px; text-align: center; color: #8A93A6; }
.cup-form { padding: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; align-items: end; }
.cup-form label { display: block; font-size: .76rem; font-weight: 700; color: #55607A; margin-bottom: 4px; }
.cup-form input, .cup-form select { width: 100%; min-height: 40px; border: 1px solid #D8D4C9; border-radius: 8px; padding: 0 10px; font-size: .9rem; }
.cup-form .hint { grid-column: 1 / -1; font-size: .74rem; color: #8A93A6; margin: 0; }
</style>

<div class="cup">
    <a href="<?= url('motor-reservas') ?>" style="font-size:.82rem;color:#6B7486;text-decoration:none;">&larr; Motor de reservas</a>
    <h1>Cupones y promociones</h1>
    <p class="sub">Códigos de descuento para tu página de reservas en línea. El descuento se aplica al total de la estancia y al anticipo.</p>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="cup-card">
        <form class="cup-form" method="POST" action="<?= url('motor-reservas/cupones/crear') ?>">
            <?= csrf_field() ?>
            <div>
                <label for="cup-codigo">Código</label>
                <input type="text" id="cup-codigo" name="codigo" maxlength="30" required placeholder="RESERVA10"
                       style="text-transform:uppercase;" pattern="[A-Za-z0-9][A-Za-z0-9_-]{2,29}">
            </div>
            <div>
                <label for="cup-tipo">Tipo</label>
                <select id="cup-tipo" name="tipo">
                    <option value="porcentaje">% de descuento</option>
                    <option value="monto">Monto fijo ($)</option>
                </select>
            </div>
            <div>
                <label for="cup-valor">Valor</label>
                <input type="number" id="cup-valor" name="valor" min="1" step="0.01" required placeholder="10">
            </div>
            <div>
                <label for="cup-desde">Vigente desde</label>
                <input type="date" id="cup-desde" name="vigente_desde">
            </div>
            <div>
                <label for="cup-hasta">Vigente hasta</label>
                <input type="date" id="cup-hasta" name="vigente_hasta">
            </div>
            <div>
                <label for="cup-limite">Límite de usos</label>
                <input type="number" id="cup-limite" name="limite_usos" min="1" step="1" placeholder="Sin límite">
            </div>
            <div>
                <button type="submit" class="cup-btn" style="min-height:40px;">Crear cupón</button>
            </div>
            <p class="hint">Vigencia y límite son opcionales. El cupón se valida al momento en que el huésped inicia su pago; el uso se descuenta solo cuando el pago se confirma.</p>
        </form>
    </div>

    <div class="cup-card">
        <table>
            <thead>
                <tr>
                    <th>Código</th><th>Descuento</th><th>Vigencia</th><th>Usos</th><th>Estado</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($cupones)): ?>
                <tr><td colspan="6" class="cup-vacio">Todavía no tienes cupones. Crea el primero arriba, por ejemplo RESERVA10 con 10%.</td></tr>
            <?php else: ?>
                <?php foreach ($cupones as $c): ?>
                    <tr>
                        <td class="cup-codigo"><?= $cpSafe($c['codigo']) ?></td>
                        <td style="font-weight:600;"><?= $cpBeneficio($c) ?></td>
                        <td style="font-size:.82rem;color:#6B7486;"><?= $cpSafe($cpVigencia($c)) ?></td>
                        <td><?= (int) $c['usos'] ?><?= $c['limite_usos'] !== null ? ' / ' . (int) $c['limite_usos'] : '' ?></td>
                        <td>
                            <?php if ((int) $c['activo']): ?>
                                <span class="cup-badge" style="background:rgba(22,163,74,.12);color:#15803D;">Activo</span>
                            <?php else: ?>
                                <span class="cup-badge" style="background:rgba(100,116,139,.12);color:#64748B;">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="<?= url('motor-reservas/cupones/' . (int) $c['id'] . '/alternar') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="cup-btn sec"><?= (int) $c['activo'] ? 'Desactivar' : 'Reactivar' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
