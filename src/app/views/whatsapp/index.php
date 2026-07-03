<?php
/**
 * WhatsApp del hotel (bloque whatsapp): conexion Green API + toggles.
 */
$cred = $cred ?? null;
$config = $config ?? ['confirmacion_huesped' => true, 'aviso_dueno' => true];

$waSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$conectado = $cred && !empty($cred['token_configurado']) && !empty($cred['activo']);
?>

<style>
.wav { max-width: 760px; margin: 0 auto; padding: 18px 16px 44px; font-size: .92rem; }
.wav h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.wav .sub { margin: 0 0 16px; color: #6B7486; }
.wav-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 14px; margin-bottom: 16px; overflow: hidden; }
.wav-head { padding: 13px 16px; border-bottom: 1px solid #EDE9DF; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.wav-head h2 { margin: 0; font-size: 1rem; color: var(--brand-primary, #1B2746); }
.wav-badge { padding: 4px 12px; border-radius: 999px; font-size: .76rem; font-weight: 700; }
.wav-form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 16px; }
.wav-field { display: grid; gap: 4px; }
.wav-field.full { grid-column: 1 / -1; }
.wav-field label { font-size: .76rem; font-weight: 700; color: #55607A; }
.wav-field input { min-height: 42px; border: 1px solid #D8D4C9; border-radius: 9px; padding: 0 11px; font-size: .92rem; width: 100%; }
.wav-field .hint { font-size: .72rem; color: #8A93A6; }
.wav-check { display: flex; align-items: center; gap: 8px; font-size: .9rem; font-weight: 600; color: #2A3242; }
.wav-check input { width: 18px; height: 18px; min-height: auto; }
.wav-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
.wav-btn { min-height: 42px; padding: 0 16px; border: 0; border-radius: 9px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .88rem; font-weight: 700; }
.wav-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid #D8D4C9; }
.wav-pasos { padding: 14px 16px; font-size: .84rem; color: #55607A; line-height: 1.6; }
.wav-pasos ol { margin: 6px 0 0; padding-left: 20px; }
</style>

<div class="wav">
    <h1>WhatsApp del hotel</h1>
    <p class="sub">Conecta el numero de tu hotel para confirmar reservas online al huesped y avisarte de cada venta.</p>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="wav-card">
        <div class="wav-head">
            <h2>Conexion (Green API)</h2>
            <span class="wav-badge" style="<?= $conectado ? 'background:rgba(22,163,74,.12);color:#15803D;' : 'background:rgba(245,158,11,.14);color:#92600A;' ?>">
                <?= $conectado ? '🟢 Conectado' : '🟡 Sin conectar' ?>
            </span>
        </div>

        <form method="POST" action="<?= url('whatsapp/guardar') ?>" class="wav-form" autocomplete="off">
            <?= csrf_field() ?>

            <div class="wav-field">
                <label for="wa-instance">ID de instancia</label>
                <input type="text" id="wa-instance" name="id_instance" value="<?= $waSafe($cred['id_instance'] ?? '') ?>" placeholder="1101123456">
            </div>
            <div class="wav-field">
                <label for="wa-token">API Token <?= !empty($cred['token_configurado']) ? '· configurado ✓' : '' ?></label>
                <input type="password" id="wa-token" name="api_token" placeholder="<?= !empty($cred['token_configurado']) ? 'Dejar vacio para conservar' : 'd75b3a66374942c5...' ?>" autocomplete="new-password">
                <span class="hint">Se guarda cifrado; nunca se vuelve a mostrar.</span>
            </div>
            <div class="wav-field">
                <label for="wa-numero">Numero para avisos (dueno/recepcion)</label>
                <input type="tel" id="wa-numero" name="numero_avisos" value="<?= $waSafe($cred['numero_avisos'] ?? '') ?>" placeholder="10 digitos" inputmode="tel">
            </div>
            <div class="wav-field">
                <label class="wav-check" style="margin-top:22px;">
                    <input type="checkbox" name="activo" value="1" <?= empty($cred) || !empty($cred['activo']) ? 'checked' : '' ?>>
                    Conexion activa
                </label>
            </div>

            <div class="wav-field full" style="border-top:1px dashed #EDE9DF;padding-top:12px;">
                <label>Mensajes automaticos del motor de reservas</label>
                <label class="wav-check">
                    <input type="checkbox" name="confirmacion_huesped" value="1" <?= !empty($config['confirmacion_huesped']) ? 'checked' : '' ?>>
                    Confirmar al huesped su reserva online pagada
                </label>
                <label class="wav-check">
                    <input type="checkbox" name="aviso_dueno" value="1" <?= !empty($config['aviso_dueno']) ? 'checked' : '' ?>>
                    Avisarme cada reserva online (al numero de avisos)
                </label>
            </div>

            <div class="wav-actions">
                <button type="submit" class="wav-btn">Guardar</button>
            </div>
        </form>

        <form method="POST" action="<?= url('whatsapp/probar') ?>" style="padding:0 16px 16px;display:flex;justify-content:flex-end;">
            <?= csrf_field() ?>
            <button type="submit" class="wav-btn sec" <?= $conectado ? '' : 'disabled title="Primero guarda la conexion."' ?>>Enviar mensaje de prueba</button>
        </form>
    </div>

    <div class="wav-card">
        <div class="wav-head"><h2>¿Como conecto mi numero?</h2></div>
        <div class="wav-pasos">
            Usa una linea del hotel (puede ser un chip nuevo). Toma 5 minutos:
            <ol>
                <li>Crea una cuenta gratis en <strong>green-api.com</strong> y crea una <em>instancia</em> (plan Developer es gratis para empezar).</li>
                <li>En la instancia, escanea el <strong>codigo QR</strong> con el WhatsApp del numero del hotel (como WhatsApp Web).</li>
                <li>Copia el <strong>ID de instancia</strong> y el <strong>API Token</strong> aqui arriba, guarda, y manda el mensaje de prueba.</li>
            </ol>
        </div>
    </div>
</div>
