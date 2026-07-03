<?php
/**
 * Tablero interno de check-in digital (bloque checkin_digital).
 */
$filas = $filas ?? [];
$slugHotel = $slugHotel ?? '';

$cdiSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$cdiBadge = static function ($estado) {
    $map = [
        'completado' => ['✅ Completado', 'background:rgba(22,163,74,.12);color:#15803D;'],
        'pendiente' => ['⏳ Enviado, sin llenar', 'background:rgba(245,158,11,.14);color:#92600A;'],
        'expirado' => ['⌛ Expirado', 'background:rgba(100,116,139,.12);color:#64748B;'],
    ];
    return $map[$estado] ?? ['— Sin link', 'background:rgba(100,116,139,.08);color:#94A3B8;'];
};
?>

<style>
.cdi { max-width: 1080px; margin: 0 auto; padding: 18px 16px 40px; font-size: .92rem; }
.cdi h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.cdi .sub { margin: 0 0 16px; color: #6B7486; }
.cdi-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 14px; overflow: hidden; }
.cdi table { width: 100%; border-collapse: collapse; }
.cdi th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid #EDE9DF; white-space: nowrap; }
.cdi td { padding: 11px 12px; border-bottom: 1px solid #F2EFE7; vertical-align: middle; }
.cdi-badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
.cdi-btn { display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 8px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .8rem; font-weight: 700; text-decoration: none; }
.cdi-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid #D8D4C9; }
.cdi-vacio { padding: 30px 16px; text-align: center; color: #8A93A6; }
.cdi-acciones { display: flex; gap: 6px; flex-wrap: wrap; }
</style>

<div class="cdi">
    <h1>Check-in digital</h1>
    <p class="sub">Manda el link de pre-registro a tus llegadas próximas; el huésped llena sus datos y sube su ID antes de llegar.</p>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="cdi-card">
        <table>
            <thead>
                <tr>
                    <th>Llegada</th><th>Huésped</th><th>Reservación</th><th>Pre-registro</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($filas)): ?>
                <tr><td colspan="5" class="cdi-vacio">No hay llegadas próximas confirmadas.</td></tr>
            <?php else: ?>
                <?php foreach ($filas as $fila): ?>
                    <?php
                    [$badgeTexto, $badgeStyle] = $cdiBadge($fila['link_estado'] ?? null);
                    $urlPublica = $fila['token']
                        ? url('h/' . $slugHotel . '/checkin/' . $fila['token'])
                        : null;
                    ?>
                    <tr>
                        <td style="white-space:nowrap;font-weight:600;"><?= $cdiSafe(date('d/m/Y', strtotime((string) $fila['fecha_entrada']))) ?></td>
                        <td>
                            <div style="font-weight:600;"><?= $cdiSafe($fila['nombre_completo']) ?></div>
                            <div style="font-size:.76rem;color:#8A93A6;"><?= $cdiSafe($fila['telefono'] ?: '') ?></div>
                        </td>
                        <td><a href="<?= url('reservaciones/ver/' . (int) $fila['reservacion_id']) ?>" style="font-weight:700;color:var(--brand-primary,#1B2746);">#<?= (int) $fila['reservacion_id'] ?></a></td>
                        <td><span class="cdi-badge" style="<?= $badgeStyle ?>"><?= $badgeTexto ?></span></td>
                        <td>
                            <div class="cdi-acciones">
                                <?php if (!$fila['token'] || $fila['link_estado'] === 'expirado'): ?>
                                    <form method="POST" action="<?= url('checkin-digital/generar/' . (int) $fila['reservacion_id']) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="cdi-btn">Generar link</button>
                                    </form>
                                <?php elseif ($fila['link_estado'] === 'pendiente'): ?>
                                    <button type="button" class="cdi-btn sec"
                                            data-link="<?= $cdiSafe($urlPublica) ?>"
                                            onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.link).then(() => { this.textContent='Copiado ✓'; })">
                                        Copiar link
                                    </button>
                                    <a class="cdi-btn sec" target="_blank" rel="noopener"
                                       href="https://wa.me/52<?= $cdiSafe(preg_replace('/\D/', '', substr((string) $fila['telefono'], -10))) ?>?text=<?= rawurlencode('Hola ' . $fila['nombre_completo'] . ', completa tu pre-registro para tu llegada aqui: ' . $urlPublica) ?>">
                                        Enviar por WhatsApp
                                    </a>
                                <?php elseif ($fila['link_estado'] === 'completado'): ?>
                                    <?php if (!empty($fila['id_documento_path'])): ?>
                                        <a class="cdi-btn sec" target="_blank" href="<?= url('checkin-digital/id/' . (int) $fila['reservacion_id']) ?>">Ver ID</a>
                                    <?php endif; ?>
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
