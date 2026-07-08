<?php
/**
 * Tablero interno de reputacion (bloque reputacion).
 */
$kpis = $kpis ?? [];
$filas = $filas ?? [];
$slugHotel = $slugHotel ?? '';
$config = $config ?? ['google_review_url' => '', 'umbral_alerta' => 3];

$repSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$repBadge = static function ($estado) {
    $map = [
        'respondida' => ['💬 Respondida', 'background:rgba(22,163,74,.12);color:#15803D;'],
        'enviada' => ['✉️ Enviada, sin responder', 'background:rgba(245,158,11,.14);color:#92600A;'],
        'pendiente' => ['🔗 Link listo', 'background:rgba(37,99,235,.1);color:#1D4ED8;'],
        'expirada' => ['⌛ Expirada', 'background:rgba(100,116,139,.12);color:#64748B;'],
    ];
    return $map[$estado] ?? ['— Sin encuesta', 'background:rgba(100,116,139,.08);color:#94A3B8;'];
};
$repEstrellas = static function ($n) {
    $n = (int) $n;
    return $n >= 1 ? str_repeat('★', $n) . str_repeat('☆', 5 - $n) : '';
};
?>

<style>
.rep { max-width: 1080px; margin: 0 auto; padding: 18px 16px 40px; font-size: .92rem; }
.rep h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.rep .sub { margin: 0 0 16px; color: #6B7486; }
.rep-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 16px; }
.rep-kpi { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 12px; padding: 14px 16px; }
.rep-kpi .valor { font-size: 1.5rem; font-weight: 800; color: var(--brand-primary, #1B2746); }
.rep-kpi .valor small { font-size: .85rem; font-weight: 600; color: #8A93A6; }
.rep-kpi .nombre { font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; margin-top: 2px; }
.rep-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 14px; overflow: hidden; margin-bottom: 16px; }
.rep table { width: 100%; border-collapse: collapse; }
.rep th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid #EDE9DF; white-space: nowrap; }
.rep td { padding: 11px 12px; border-bottom: 1px solid #F2EFE7; vertical-align: middle; }
.rep-badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
.rep-btn { display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 8px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .8rem; font-weight: 700; text-decoration: none; }
.rep-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid #D8D4C9; }
.rep-vacio { padding: 30px 16px; text-align: center; color: #8A93A6; }
.rep-acciones { display: flex; gap: 6px; flex-wrap: wrap; }
.rep-estrellas { color: var(--brand-accent, #BD9441); letter-spacing: 2px; white-space: nowrap; }
.rep-comentario { font-size: .8rem; color: #667086; max-width: 260px; }
.rep-config { padding: 16px; }
.rep-config h2 { margin: 0 0 10px; font-size: 1rem; color: var(--brand-primary, #1B2746); }
.rep-config .fila { display: grid; grid-template-columns: 2fr 1fr auto; gap: 10px; align-items: end; }
.rep-config label { display: block; font-size: .76rem; font-weight: 700; color: #55607A; margin-bottom: 4px; }
.rep-config input, .rep-config select { width: 100%; min-height: 40px; border: 1px solid #D8D4C9; border-radius: 8px; padding: 0 10px; font-size: .9rem; }
.rep-config .hint { font-size: .74rem; color: #8A93A6; margin-top: 6px; }
@media (max-width: 640px) { .rep-config .fila { grid-template-columns: 1fr; } }
</style>

<div class="rep">
    <h1>Reputacion y encuestas</h1>
    <p class="sub">Manda la encuesta a tus checkouts recientes; las buenas calificaciones van a Google y las malas te llegan a ti primero.</p>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="rep-kpis">
        <div class="rep-kpi">
            <div class="valor"><?= $kpis['promedio'] !== null ? $repSafe(number_format((float) $kpis['promedio'], 1)) : '—' ?> <small>/ 5</small></div>
            <div class="nombre">Calificacion promedio</div>
        </div>
        <div class="rep-kpi">
            <div class="valor"><?= (int) ($kpis['respondidas'] ?? 0) ?> <small>de <?= (int) ($kpis['generadas'] ?? 0) ?></small></div>
            <div class="nombre">Encuestas respondidas</div>
        </div>
        <div class="rep-kpi">
            <div class="valor"><?= $kpis['tasa_respuesta'] !== null ? (int) $kpis['tasa_respuesta'] . '<small>%</small>' : '—' ?></div>
            <div class="nombre">Tasa de respuesta</div>
        </div>
        <div class="rep-kpi">
            <div class="valor"><?= $kpis['nps'] !== null ? (int) $kpis['nps'] : '—' ?></div>
            <div class="nombre">NPS (90 dias)</div>
        </div>
    </div>

    <div class="rep-card">
        <table>
            <thead>
                <tr>
                    <th>Salida</th><th>Huésped</th><th>Encuesta</th><th>Calificación</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($filas)): ?>
                <tr><td colspan="5" class="rep-vacio">No hay checkouts en los últimos 30 días.</td></tr>
            <?php else: ?>
                <?php foreach ($filas as $fila): ?>
                    <?php
                    [$badgeTexto, $badgeStyle] = $repBadge($fila['encuesta_estado'] ?? null);
                    $urlPublica = !empty($fila['token'])
                        ? url('h/' . $slugHotel . '/encuesta/' . $fila['token'])
                        : null;
                    $estadoEncuesta = $fila['encuesta_estado'] ?? null;
                    ?>
                    <tr>
                        <td style="white-space:nowrap;font-weight:600;"><?= $repSafe(date('d/m/Y', strtotime((string) $fila['fecha_salida']))) ?></td>
                        <td>
                            <div style="font-weight:600;"><?= $repSafe($fila['nombre_completo']) ?></div>
                            <div style="font-size:.76rem;color:#8A93A6;"><?= $repSafe($fila['email'] ?: ($fila['telefono'] ?: '')) ?></div>
                        </td>
                        <td><span class="rep-badge" style="<?= $badgeStyle ?>"><?= $badgeTexto ?></span></td>
                        <td>
                            <?php if ($estadoEncuesta === 'respondida'): ?>
                                <div class="rep-estrellas" title="<?= (int) $fila['calificacion'] ?> de 5"><?= $repEstrellas($fila['calificacion']) ?></div>
                                <?php if ($fila['nps'] !== null): ?>
                                    <div style="font-size:.74rem;color:#8A93A6;">NPS <?= (int) $fila['nps'] ?>/10</div>
                                <?php endif; ?>
                                <?php if (!empty($fila['comentario'])): ?>
                                    <div class="rep-comentario">"<?= $repSafe(mb_strimwidth((string) $fila['comentario'], 0, 140, '…', 'UTF-8')) ?>"</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#94A3B8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="rep-acciones">
                                <?php if (!$fila['token'] || $estadoEncuesta === 'expirada'): ?>
                                    <form method="POST" action="<?= url('reputacion/generar/' . (int) $fila['reservacion_id']) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="rep-btn">Generar encuesta</button>
                                    </form>
                                <?php elseif (in_array($estadoEncuesta, ['pendiente', 'enviada'], true)): ?>
                                    <?php if (!empty($fila['email'])): ?>
                                        <form method="POST" action="<?= url('reputacion/enviar/' . (int) $fila['encuesta_id']) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="rep-btn">Enviar por correo</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!empty($fila['telefono'])): ?>
                                        <a class="rep-btn sec" target="_blank" rel="noopener"
                                           href="https://wa.me/52<?= $repSafe(preg_replace('/\D/', '', substr((string) $fila['telefono'], -10))) ?>?text=<?= rawurlencode('Hola ' . $fila['nombre_completo'] . ', gracias por hospedarte con nosotros. ¿Nos cuentas como te fue? ' . $urlPublica) ?>">
                                            WhatsApp
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="rep-btn sec"
                                            data-link="<?= $repSafe($urlPublica) ?>"
                                            onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.link).then(() => { this.textContent='Copiado ✓'; })">
                                        Copiar link
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

    <div class="rep-card rep-config">
        <h2>Configuración</h2>
        <form method="POST" action="<?= url('reputacion/config') ?>">
            <?= csrf_field() ?>
            <div class="fila">
                <div>
                    <label for="rep-google">Link de reseñas de Google</label>
                    <input type="url" id="rep-google" name="google_review_url" maxlength="500"
                           placeholder="https://g.page/r/..." value="<?= $repSafe($config['google_review_url']) ?>">
                </div>
                <div>
                    <label for="rep-umbral">Alertarme si califican con</label>
                    <select id="rep-umbral" name="umbral_alerta">
                        <?php foreach ([1 => '1 estrella o menos', 2 => '2 estrellas o menos', 3 => '3 estrellas o menos', 4 => '4 estrellas o menos'] as $v => $txt): ?>
                            <option value="<?= $v ?>" <?= (int) $config['umbral_alerta'] === $v ? 'selected' : '' ?>><?= $txt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="rep-btn" style="min-height:40px;">Guardar</button>
                </div>
            </div>
            <p class="hint">Al huésped que califica con 4-5 estrellas se le invita a dejar reseña en Google con este link. Las calificaciones bajas generan una notificación interna para el gerente (requiere el bloque de notificaciones).</p>
        </form>
    </div>
</div>
