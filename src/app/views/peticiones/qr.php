<?php
/**
 * QR de habitaciones: lo que ve el huesped al escanear + los codigos para
 * imprimir. Solo gerencia/administracion (reputacion.configurar).
 */
$config = $config ?? [];
$habitaciones = $habitaciones ?? [];
$pisos = $pisos ?? [];
$muestra = $muestra ?? null;
$muestraSvg = $muestraSvg ?? '';

$qrSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$checkout = EstanciaService::horaHumana((string) ($config['checkout_hora'] ?? ''));
$checkin = EstanciaService::horaHumana((string) ($config['checkin_hora'] ?? ''));
$activas = !empty($config['peticiones_activas']);
$hayWifi = trim((string) ($config['wifi_red'] ?? '')) !== '' || trim((string) ($config['wifi_clave'] ?? '')) !== '';
?>
<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.qrc {
    --qrc-brand: var(--brand-primary, #1B2746);
    --qrc-gold: var(--brand-accent, #BD9441);
    --qrc-gold-soft: color-mix(in srgb, var(--qrc-gold) 15%, #FFFFFF);
    --qrc-gold-line: color-mix(in srgb, var(--qrc-gold) 42%, #E4D4B0);
    --qrc-gold-ink: color-mix(in srgb, var(--qrc-gold) 58%, var(--qrc-brand));
    --qrc-surface: #FFFFFF;
    --qrc-surface-2: #FAFAFC;
    --qrc-border: color-mix(in srgb, var(--qrc-brand) 6%, #E9E1D6);
    --qrc-ring: color-mix(in srgb, var(--qrc-gold) 32%, transparent);
    --qrc-text: color-mix(in srgb, var(--qrc-brand) 46%, #707B8C);
    --qrc-muted: #7F899A;
    --qrc-heading: color-mix(in srgb, var(--qrc-brand) 66%, #566172);
    --qrc-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --qrc-success: #1E7F4F;
    --qrc-success-bg: #E7F4EC;
    --qrc-danger: #B4392B;
    --qrc-danger-bg: #F8EAE5;
    --qrc-ease: cubic-bezier(.22, 1, .36, 1);
    width: 100%;
    padding: 18px 16px 40px;
    color: var(--qrc-text);
    font-family: var(--qrc-sans);
    font-size: .92rem;
}
html[data-theme="dark"] .qrc {
    --qrc-surface: #1C1C1E;
    --qrc-surface-2: #232326;
    --qrc-border: rgba(255, 255, 255, 0.09);
    --qrc-text: #D1D1D6;
    --qrc-muted: #98989D;
    --qrc-heading: #F5F5F7;
    --qrc-gold-soft: color-mix(in srgb, var(--qrc-gold) 18%, #1C1C1E);
    --qrc-gold-line: color-mix(in srgb, var(--qrc-gold) 40%, #38383A);
    --qrc-gold-ink: color-mix(in srgb, var(--qrc-gold) 78%, #F5F5F7);
    --qrc-success: #6FD19E;
    --qrc-success-bg: #16301F;
    --qrc-danger: #F59A91;
    --qrc-danger-bg: #3A1714;
}
.qrc * { box-sizing: border-box; }
.qrc-shell { display: grid; gap: 16px; max-width: 1100px; margin: 0 auto; min-width: 0; }
.qrc-shell > * { min-width: 0; }
.qrc-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px 28px; align-items: center; }
.qrc-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); column-gap: 14px; align-items: center; min-width: 0; }
.qrc-lockup > div:last-child { min-width: 0; }
.qrc-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--qrc-gold), var(--qrc-brand) 54%, color-mix(in srgb, var(--qrc-brand) 68%, var(--qrc-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--qrc-brand) 72%, transparent);
}
.qrc-kicker { margin: 0 0 2px; color: var(--qrc-muted); font-size: .72rem; font-weight: 650; letter-spacing: .11em; text-transform: uppercase; }
.qrc-title { margin: 0; color: var(--qrc-heading); font-size: clamp(1.9rem, 4vw, 2.7rem); font-weight: 650; line-height: 1.02; }
.qrc-subtitle { margin: 9px 0 0; max-width: 46rem; color: var(--qrc-muted); font-size: .94rem; line-height: 1.5; }
.qrc-hero-side { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.qrc-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 44px; padding: 0 16px; border-radius: 12px;
    border: 1px solid var(--qrc-border); background: var(--qrc-surface); color: var(--qrc-heading);
    font: inherit; font-weight: 700; font-size: .86rem; cursor: pointer; text-decoration: none; white-space: nowrap;
    transition: transform .14s var(--qrc-ease), box-shadow .14s var(--qrc-ease);
}
.qrc-btn.is-primary { background: var(--qrc-brand); border-color: var(--qrc-brand); color: #fff; }
html[data-theme="dark"] .qrc-btn.is-primary { background: var(--qrc-gold-soft); border-color: var(--qrc-gold-line); color: var(--qrc-gold-ink); }
.qrc-btn.is-quiet { background: transparent; border-color: transparent; color: var(--qrc-muted); }
.qrc-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 18px -14px rgba(27,39,70,.35); }
.qrc-btn:focus-visible, .qrc input:focus-visible, .qrc textarea:focus-visible, .qrc select:focus-visible { outline: 3px solid var(--qrc-ring); outline-offset: 2px; }

.qrc-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(280px, .8fr); gap: 16px; align-items: start; }
.qrc-card { background: var(--qrc-surface); border: 1px solid var(--qrc-border); border-radius: 18px; padding: 18px; }
.qrc-card h2 { margin: 0 0 4px; color: var(--qrc-heading); font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: 9px; }
.qrc-card h2 .fas { color: var(--qrc-gold-ink); }
.qrc-card > p.qrc-lead { margin: 0 0 14px; color: var(--qrc-muted); line-height: 1.5; }
.qrc-seccion { padding: 14px 0; border-top: 1px solid var(--qrc-border); }
.qrc-seccion:first-of-type { border-top: 0; padding-top: 4px; }
.qrc-seccion h3 { margin: 0 0 10px; color: var(--qrc-heading); font-size: .92rem; font-weight: 700; }
.qrc-fila { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.qrc-campo label { display: block; color: var(--qrc-heading); font-weight: 650; font-size: .86rem; margin-bottom: 6px; }
.qrc-campo input[type="text"], .qrc-campo input[type="tel"], .qrc-campo textarea, .qrc-campo select {
    width: 100%; min-height: 44px; padding: 10px 12px; border-radius: 11px; border: 1px solid var(--qrc-border);
    background: var(--qrc-surface-2); color: var(--qrc-heading); font: inherit;
}
.qrc-campo textarea { min-height: 96px; resize: vertical; line-height: 1.5; }
.qrc-ayuda { margin: 6px 0 0; color: var(--qrc-muted); font-size: .8rem; line-height: 1.45; }
.qrc-switch { display: flex; align-items: flex-start; gap: 12px; cursor: pointer; }
.qrc-switch input { width: 20px; height: 20px; margin-top: 2px; accent-color: var(--qrc-brand); flex: none; }
.qrc-switch strong { display: block; color: var(--qrc-heading); }
.qrc-acciones-form { display: flex; justify-content: flex-end; padding-top: 14px; border-top: 1px solid var(--qrc-border); }

.qrc-muestra { position: sticky; top: 84px; display: grid; gap: 12px; justify-items: center; text-align: center; }
.qrc-muestra .qrc-qr { width: 190px; height: 190px; padding: 10px; border-radius: 14px; background: #fff; border: 1px solid var(--qrc-border); }
.qrc-muestra .qrc-qr svg { width: 100%; height: 100%; display: block; }
.qrc-muestra p { margin: 0; color: var(--qrc-muted); line-height: 1.5; }
.qrc-muestra strong { color: var(--qrc-heading); }

.qrc-imprimir { display: flex; flex-wrap: wrap; gap: 10px 12px; align-items: flex-end; margin-bottom: 14px; }
.qrc-imprimir .qrc-campo { min-width: 180px; }
.qrc-tabla { width: 100%; border-collapse: collapse; }
.qrc-tabla th { text-align: left; color: var(--qrc-muted); font-size: .72rem; font-weight: 650; letter-spacing: .06em; text-transform: uppercase; padding: 8px 10px; border-bottom: 1px solid var(--qrc-border); }
.qrc-tabla td { padding: 10px; border-bottom: 1px solid var(--qrc-border); vertical-align: middle; }
.qrc-tabla tr:target td { background: var(--qrc-gold-soft); }
.qrc-num { color: var(--qrc-heading); font-weight: 700; font-size: 1rem; }
.qrc-acciones { display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; }
.qrc-acciones form { margin: 0; }
.qrc-acciones .qrc-btn { min-height: 40px; padding: 0 12px; font-size: .8rem; }

@media (max-width: 900px) {
    .qrc-grid { grid-template-columns: 1fr; }
    .qrc-muestra { position: static; }
}
@media (max-width: 640px) {
    .qrc-hero { grid-template-columns: 1fr; }
    .qrc-hero-side { justify-content: flex-start; }
    .qrc-fila { grid-template-columns: 1fr; }
    .qrc-tabla thead { display: none; }
    .qrc-tabla tr { display: grid; grid-template-columns: 1fr auto; gap: 8px; padding: 10px 0; border-bottom: 1px solid var(--qrc-border); }
    .qrc-tabla td { border: 0; padding: 0; }
    .qrc-tabla td.qrc-acciones-td { grid-column: 1 / -1; }
    .qrc-acciones { justify-content: flex-start; }
}
@media (prefers-reduced-motion: reduce) { .qrc * { transition: none !important; } }
</style>

<div class="qrc">
    <div class="qrc-shell">
        <section class="qrc-hero">
            <div class="qrc-lockup">
                <div class="qrc-hero-icon" aria-hidden="true"><i class="fas fa-qrcode"></i></div>
                <div>
                    <p class="qrc-kicker">Peticiones de huéspedes</p>
                    <h1 class="qrc-title">QR de habitaciones</h1>
                    <p class="qrc-subtitle">Pega el QR de cada cuarto a la vista. Al escanearlo, tu huésped ve el Wi-Fi y los horarios, y pide toallas, limpieza o avisa si algo no funciona. Todo llega a tu bandeja.</p>
                </div>
            </div>
            <div class="qrc-hero-side">
                <a class="qrc-btn" href="<?= url('peticiones') ?>"><i class="fas fa-bell-concierge" aria-hidden="true"></i>Ver peticiones</a>
                <?php if (!empty($habitaciones)): ?>
                    <a class="qrc-btn is-primary" href="<?= url('peticiones/qr/imprimir') ?>" target="_blank" rel="noopener"><i class="fas fa-print" aria-hidden="true"></i>Imprimir todos</a>
                <?php endif; ?>
            </div>
        </section>


        <div class="qrc-grid">
            <form class="qrc-card" method="POST" action="<?= url('peticiones/qr/configuracion') ?>">
                <?= csrf_field() ?>
                <h2><i class="fas fa-mobile-screen" aria-hidden="true"></i>Lo que ve tu huésped</h2>
                <p class="qrc-lead">Lo que dejes vacío no aparece en su pantalla.</p>

                <div class="qrc-seccion">
                    <h3>Wi-Fi</h3>
                    <div class="qrc-fila">
                        <div class="qrc-campo">
                            <label for="qrc-wifi-red">Nombre de la red</label>
                            <input type="text" id="qrc-wifi-red" name="wifi_red" maxlength="80" autocomplete="off" value="<?= $qrSafe($config['wifi_red'] ?? '') ?>">
                        </div>
                        <div class="qrc-campo">
                            <label for="qrc-wifi-clave">Contraseña</label>
                            <input type="text" id="qrc-wifi-clave" name="wifi_clave" maxlength="80" autocomplete="off" spellcheck="false" value="<?= $qrSafe($config['wifi_clave'] ?? '') ?>">
                        </div>
                    </div>
                    <p class="qrc-ayuda">El huésped la copia con un toque. Si cambias la contraseña, no hace falta reimprimir los QR.</p>
                </div>

                <div class="qrc-seccion">
                    <h3>Recepción</h3>
                    <div class="qrc-campo">
                        <label for="qrc-whatsapp">WhatsApp de recepción</label>
                        <input type="tel" id="qrc-whatsapp" name="contacto_whatsapp" maxlength="25" inputmode="tel" autocomplete="off" placeholder="10 dígitos" value="<?= $qrSafe($config['contacto_whatsapp'] ?? '') ?>">
                        <p class="qrc-ayuda">Para el botón "Escribir por WhatsApp". Si lo dejas vacío usamos el teléfono del hotel.</p>
                    </div>
                </div>

                <div class="qrc-seccion">
                    <h3>Información para el huésped</h3>
                    <div class="qrc-campo">
                        <label for="qrc-info">Información útil</label>
                        <textarea id="qrc-info" name="info" maxlength="1500" placeholder="Desayuno: 8:00 a 11:00 en el restaurante.&#10;Alberca: 9:00 a 19:00."><?= $qrSafe($config['info'] ?? '') ?></textarea>
                    </div>
                    <div class="qrc-campo" style="margin-top:12px">
                        <label for="qrc-reglamento">Reglamento del hotel</label>
                        <textarea id="qrc-reglamento" name="reglamento" maxlength="3000"><?= $qrSafe($config['reglamento'] ?? '') ?></textarea>
                        <p class="qrc-ayuda">Se muestra plegado; el huésped lo abre si lo necesita.</p>
                    </div>
                    <?php if ($checkout !== '' || $checkin !== ''): ?>
                        <p class="qrc-ayuda"><i class="fas fa-clock" aria-hidden="true"></i> Los horarios de entrada (<?= $qrSafe($checkin) ?>) y salida (<?= $qrSafe($checkout) ?>) salen de la configuración del hotel y se muestran solos.</p>
                    <?php endif; ?>
                </div>

                <div class="qrc-seccion">
                    <input type="hidden" name="peticiones_activas" value="0">
                    <label class="qrc-switch" for="qrc-activas">
                        <input type="checkbox" id="qrc-activas" name="peticiones_activas" value="1" <?= $activas ? 'checked' : '' ?>>
                        <span>
                            <strong>Recibir peticiones desde el QR</strong>
                            <span class="qrc-ayuda" style="display:block;margin-top:2px">Solo mientras la habitación tiene un huésped con check-in. Si lo apagas, el QR solo muestra la información y el contacto de recepción.</span>
                        </span>
                    </label>
                </div>

                <div class="qrc-acciones-form">
                    <button type="submit" class="qrc-btn is-primary"><i class="fas fa-floppy-disk" aria-hidden="true"></i>Guardar cambios</button>
                </div>
            </form>

            <aside class="qrc-card qrc-muestra" aria-label="Ejemplo de QR">
                <?php if ($muestra && $muestraSvg !== ''): ?>
                    <div class="qrc-qr"><?= $muestraSvg ?></div>
                    <p><strong>Habitación <?= $qrSafe($muestra['numero']) ?></strong><br>Escanéalo con tu celular para ver lo que verá tu huésped.</p>
                    <a class="qrc-btn" href="<?= $qrSafe($muestra['link']) ?>" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>Abrir como huésped</a>
                    <?php if (!$hayWifi): ?>
                        <p class="qrc-ayuda">Tip: agrega el Wi-Fi; es lo primero que buscan.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Da de alta tus habitaciones para generar sus QR.</p>
                <?php endif; ?>
            </aside>
        </div>

        <?php if (!empty($habitaciones)): ?>
            <section class="qrc-card" aria-labelledby="qrc-lista">
                <h2 id="qrc-lista"><i class="fas fa-door-closed" aria-hidden="true"></i>Códigos por habitación</h2>
                <p class="qrc-lead">Cada cuarto tiene su propio QR. Si uno se pierde o alguien se lo lleva, regenéralo: el anterior deja de funcionar.</p>

                <form class="qrc-imprimir" method="GET" action="<?= url('peticiones/qr/imprimir') ?>" target="_blank" data-form-guard="off">
                    <div class="qrc-campo">
                        <label for="qrc-piso">Imprimir</label>
                        <select id="qrc-piso" name="piso">
                            <option value="">Todas las habitaciones (<?= count($habitaciones) ?>)</option>
                            <?php foreach ($pisos as $piso): ?>
                                <option value="<?= $qrSafe($piso) ?>">Solo el piso <?= $qrSafe($piso) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="wifi" value="0">
                    <label class="qrc-switch" for="qrc-imprimir-wifi" style="min-height:44px;align-items:center">
                        <input type="checkbox" id="qrc-imprimir-wifi" name="wifi" value="1" <?= $hayWifi ? 'checked' : 'disabled' ?>>
                        <span>Imprimir también el Wi-Fi en la tarjeta</span>
                    </label>
                    <button type="submit" class="qrc-btn"><i class="fas fa-print" aria-hidden="true"></i>Abrir hoja para imprimir</button>
                </form>

                <table class="qrc-tabla">
                    <thead>
                        <tr><th scope="col">Habitación</th><th scope="col">Piso</th><th scope="col"><span class="sr-only">Acciones</span></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($habitaciones as $h): $idHab = (int) $h['id']; ?>
                            <tr id="hab-<?= $idHab ?>">
                                <td><span class="qrc-num"><?= $qrSafe($h['numero']) ?></span></td>
                                <td><?= $qrSafe($h['piso']) ?></td>
                                <td class="qrc-acciones-td">
                                    <div class="qrc-acciones">
                                        <a class="qrc-btn is-quiet" href="<?= $qrSafe($h['link']) ?>" target="_blank" rel="noopener"><i class="fas fa-eye" aria-hidden="true"></i>Ver</a>
                                        <button type="button" class="qrc-btn is-quiet" data-qrc-copiar="<?= $qrSafe($h['link']) ?>"><i class="fas fa-link" aria-hidden="true"></i><span>Copiar link</span></button>
                                        <a class="qrc-btn" href="<?= url('peticiones/qr/imprimir?habitacion=' . $idHab) ?>" target="_blank" rel="noopener"><i class="fas fa-print" aria-hidden="true"></i>Imprimir</a>
                                        <form method="POST" action="<?= url('peticiones/qr/regenerar/' . $idHab) ?>" data-form-guard="off"
                                              data-ms-confirm data-ms-type="warning" data-ms-icon="alert"
                                              data-ms-title="¿Cambiar el QR de la habitación <?= $qrSafe($h['numero']) ?>?"
                                              data-ms-msg="El QR que está pegado en el cuarto dejará de funcionar. Tendrás que imprimir y pegar el nuevo."
                                              data-ms-ok="Sí, cambiar el QR">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="qrc-btn is-quiet"><i class="fas fa-rotate" aria-hidden="true"></i>Regenerar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('[data-qrc-copiar]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var texto = btn.getAttribute('data-qrc-copiar');
            var span = btn.querySelector('span');
            var listo = function () {
                if (span) { span.textContent = 'Copiado'; }
                if (window.msToast) { window.msToast('success', 'Link copiado', 'Puedes mandarlo por WhatsApp al huésped.'); }
                setTimeout(function () { if (span) { span.textContent = 'Copiar link'; } }, 2000);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(texto).then(listo, function () { window.prompt('Copia el link:', texto); });
            } else {
                window.prompt('Copia el link:', texto);
            }
        });
    });
})();
</script>
