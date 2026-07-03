<?php
/**
 * Tablero de canales iCal (bloque canales_ical): export por habitacion +
 * calendarios importados con estado de sincronizacion.
 */
$habitaciones = $habitaciones ?? [];
$feeds = $feeds ?? [];
$token = $token ?? '';
$slug = $slug ?? '';

$cnSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$cnEtiqueta = static function ($hab) {
    $numero = trim((string) ($hab['habitacion_numero'] ?? $hab['numero'] ?? ''));
    $tipo = trim((string) ($hab['habitacion_tipo'] ?? $hab['tipo'] ?? ''));
    return $numero !== '' ? ('Hab ' . $numero . ($tipo !== '' ? ' · ' . ucfirst($tipo) : '')) : ucfirst($tipo);
};
?>

<style>
.cnl { max-width: 1080px; margin: 0 auto; padding: 18px 16px 40px; font-size: .92rem; }
.cnl h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.cnl .sub { margin: 0 0 16px; color: #6B7486; }
.cnl h2 { margin: 22px 0 8px; font-size: 1.02rem; color: var(--brand-primary, #1B2746); }
.cnl-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 14px; overflow: hidden; }
.cnl table { width: 100%; border-collapse: collapse; }
.cnl th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid #EDE9DF; white-space: nowrap; }
.cnl td { padding: 11px 12px; border-bottom: 1px solid #F2EFE7; vertical-align: middle; }
.cnl-btn { display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 0 12px; border: 0; border-radius: 8px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .8rem; font-weight: 700; text-decoration: none; }
.cnl-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid #D8D4C9; }
.cnl-btn.rojo { background: #fff; color: #B91C1C; border: 1px solid rgba(220,38,38,.35); }
.cnl-badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
.cnl-vacio { padding: 26px 16px; text-align: center; color: #8A93A6; }
.cnl-form { display: grid; grid-template-columns: 1fr 1fr 2fr auto; gap: 10px; padding: 14px; align-items: end; }
.cnl-form label { display: block; font-size: .74rem; font-weight: 700; color: #6B7486; margin-bottom: 4px; }
.cnl-form input, .cnl-form select { width: 100%; min-height: 38px; padding: 0 10px; border: 1px solid #D8D4C9; border-radius: 8px; font-size: .88rem; background: #fff; }
.cnl-ayuda { margin: 10px 0 0; padding: 12px 14px; border-radius: 10px; background: rgba(59,130,246,.06); border: 1px solid rgba(59,130,246,.18); color: #3A4A66; font-size: .84rem; line-height: 1.55; }
@media (max-width: 768px) { .cnl-form { grid-template-columns: 1fr; } }
</style>

<div class="cnl">
    <h1>Canales (iCal)</h1>
    <p class="sub">Sincroniza tu calendario con Airbnb y Booking para no vender dos veces la misma habitación.</p>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : ($tipo === 'warning' ? 'background:rgba(245,158,11,.1);color:#92600A;border:1px solid rgba(245,158,11,.25);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);') ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <h2>1. Exporta tus reservas hacia las plataformas</h2>
    <p class="sub" style="margin-bottom:8px;">Copia el link de cada habitación y pégalo en Airbnb (Disponibilidad → Importar calendario) o Booking (Calendario → Sincronizar). Ellas leerán tus reservas automáticamente.</p>
    <div class="cnl-card">
        <table>
            <thead><tr><th>Habitación</th><th>Link iCal para pegar en la plataforma</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($habitaciones)): ?>
                <tr><td colspan="3" class="cnl-vacio">No hay habitaciones activas.</td></tr>
            <?php else: ?>
                <?php foreach ($habitaciones as $hab): ?>
                    <?php $urlIcs = url('h/' . $slug . '/ical/' . $token . '/' . (int) $hab['id'] . '.ics'); ?>
                    <tr>
                        <td style="white-space:nowrap;font-weight:600;"><?= $cnSafe($cnEtiqueta($hab)) ?></td>
                        <td style="font-size:.78rem;color:#6B7486;word-break:break-all;"><?= $cnSafe($urlIcs) ?></td>
                        <td style="text-align:right;">
                            <button type="button" class="cnl-btn sec"
                                    data-link="<?= $cnSafe($urlIcs) ?>"
                                    onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.link).then(() => { this.textContent='Copiado ✓'; })">
                                Copiar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2>2. Importa los calendarios de las plataformas</h2>
    <p class="sub" style="margin-bottom:8px;">Pega aquí el link iCal que te da cada plataforma por habitación. Sus reservas bloquearán la habitación aquí y en tu página de reservas.</p>

    <div class="cnl-card" style="margin-bottom:12px;">
        <form method="POST" action="<?= url('canales/feed/guardar') ?>" class="cnl-form">
            <?= csrf_field() ?>
            <div>
                <label>Habitación</label>
                <select name="habitacion_id" required>
                    <option value="">Elige…</option>
                    <?php foreach ($habitaciones as $hab): ?>
                        <option value="<?= (int) $hab['id'] ?>"><?= $cnSafe($cnEtiqueta($hab)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Nombre</label>
                <input type="text" name="nombre" maxlength="100" placeholder="Ej. Airbnb Hab 5" required>
            </div>
            <div>
                <label>Link iCal de la plataforma (.ics)</label>
                <input type="url" name="url" maxlength="500" placeholder="https://www.airbnb.mx/calendar/ical/…" required>
            </div>
            <button type="submit" class="cnl-btn">Agregar</button>
        </form>
    </div>

    <div class="cnl-card">
        <table>
            <thead><tr><th>Habitación</th><th>Calendario</th><th>Última sincronización</th><th>Bloqueos</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($feeds)): ?>
                <tr><td colspan="5" class="cnl-vacio">Aún no importas ningún calendario.</td></tr>
            <?php else: ?>
                <?php foreach ($feeds as $feed): ?>
                    <tr>
                        <td style="white-space:nowrap;font-weight:600;"><?= $cnSafe($cnEtiqueta($feed)) ?></td>
                        <td>
                            <div style="font-weight:600;"><?= $cnSafe($feed['nombre']) ?></div>
                            <div style="font-size:.74rem;color:#8A93A6;word-break:break-all;"><?= $cnSafe(mb_strimwidth((string) $feed['url'], 0, 70, '…')) ?></div>
                        </td>
                        <td style="white-space:nowrap;">
                            <?php if (!$feed['last_sync_at']): ?>
                                <span class="cnl-badge" style="background:rgba(100,116,139,.08);color:#94A3B8;">— Sin sincronizar</span>
                            <?php elseif ($feed['last_sync_estado'] === 'ok'): ?>
                                <span class="cnl-badge" style="background:rgba(22,163,74,.12);color:#15803D;">✅ <?= $cnSafe(date('d/m H:i', strtotime((string) $feed['last_sync_at']))) ?></span>
                            <?php else: ?>
                                <span class="cnl-badge" style="background:rgba(220,38,38,.1);color:#B91C1C;" title="<?= $cnSafe($feed['last_sync_error']) ?>">⚠ Error</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:700;"><?= (int) $feed['eventos_activos'] ?></td>
                        <td style="text-align:right;">
                            <form method="POST" action="<?= url('canales/feed/eliminar/' . (int) $feed['id']) ?>" style="display:inline;" class="cnl-form-eliminar">
                                <?= csrf_field() ?>
                                <button type="submit" class="cnl-btn rojo">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <form method="POST" action="<?= url('canales/sincronizar') ?>" style="margin-top:12px;">
        <?= csrf_field() ?>
        <button type="submit" class="cnl-btn">🔄 Sincronizar ahora</button>
    </form>

    <div class="cnl-ayuda">
        <strong>¿Cómo funciona?</strong> La sincronización corre automáticamente varias veces al día y también con el botón de arriba.
        Las fechas ocupadas en Airbnb/Booking bloquean la habitación en todo el sistema (recepción y página de reservas).
        Si cancelan allá, el bloqueo se libera en la siguiente sincronización.
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('.cnl-form-eliminar').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.msOk === '1') { delete form.dataset.msOk; return; }
            e.preventDefault();
            var continuar = function (ok) {
                if (!ok) return;
                form.dataset.msOk = '1';
                if (form.requestSubmit) form.requestSubmit();
                else form.submit();
            };
            if (window.msConfirm) {
                msConfirm({
                    type: 'warning',
                    icon: 'alert',
                    title: '¿Eliminar este calendario?',
                    msg: 'Se dejarán de importar sus reservas y sus bloqueos se liberarán.',
                    confirmLabel: 'Eliminar'
                }).then(continuar);
            } else {
                continuar(confirm('¿Eliminar este calendario?'));
            }
        });
    });
})();
</script>
