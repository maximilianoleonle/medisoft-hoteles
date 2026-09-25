<?php
/**
 * "Tu estancia": lo que ve el huesped al escanear el QR de su habitacion.
 * Standalone (sin layout interno). JAMAS pinta datos del huesped: el QR vive
 * pegado en el cuarto y lo escanea cualquiera que entre.
 */
$hotel = $hotel ?? [];
$branding = $branding ?? [];
$habitacion = $habitacion ?? [];
$token = $token ?? '';
$config = $config ?? [];
$contacto = $contacto ?? ['texto' => '', 'tel' => null, 'wa' => null];
$aceptaPeticiones = !empty($aceptaPeticiones);
$hayEstancia = !empty($hayEstancia);
$peticiones = $peticiones ?? [];
$tipos = $tipos ?? EstanciaService::TIPOS;
$resultado = $resultado ?? '';

$tuSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$tuParrafos = static function ($texto) use ($tuSafe) {
    $bloques = preg_split("/\n{2,}/", trim((string) $texto)) ?: [];
    $html = '';
    foreach ($bloques as $bloque) {
        if (trim($bloque) !== '') {
            $html .= '<p>' . nl2br($tuSafe(trim($bloque)), false) . '</p>';
        }
    }
    return $html;
};

$colorHex = static function ($valor, $defecto) {
    $valor = trim((string) $valor);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $valor) ? strtoupper($valor) : $defecto;
};

$nombreHotel = (string) ($branding['nombre_visual'] ?? $hotel['nombre'] ?? 'Hotel');
$colorPrimario = $colorHex($branding['color_primary'] ?? '', '#1B2746');
$colorAcento = $colorHex($branding['color_accent'] ?? '', '#BD9441');
$textoSobrePrimario = EstanciaService::colorTextoSobre($colorPrimario);
$logoUrl = function_exists('hotel_branding_asset_url') ? hotel_branding_asset_url($branding['logo_url'] ?? null) : null;

$numero = (string) ($habitacion['numero'] ?? '');
$wifiRed = trim((string) ($config['wifi_red'] ?? ''));
$wifiClave = trim((string) ($config['wifi_clave'] ?? ''));
$checkout = EstanciaService::horaHumana((string) ($config['checkout_hora'] ?? ''));
$checkin = EstanciaService::horaHumana((string) ($config['checkin_hora'] ?? ''));
$info = trim((string) ($config['info'] ?? ''));
$reglamento = trim((string) ($config['reglamento'] ?? ''));

$accion = url('h/' . ($hotel['slug'] ?? '') . '/estancia/' . $token . '/pedir');
$mensajeWa = 'Hola, estoy en la habitación ' . $numero . ' de ' . $nombreHotel . '.';
$linkWa = !empty($contacto['wa']) ? 'https://wa.me/' . rawurlencode((string) $contacto['wa']) . '?text=' . rawurlencode($mensajeWa) : null;
$linkTel = !empty($contacto['tel']) ? 'tel:' . preg_replace('/[^0-9+]/', '', (string) $contacto['tel']) : null;

$avisos = [
    'ok' => ['exito', 'fa-circle-check', 'Listo, ya avisamos al equipo.', 'Te atendemos en breve. Aquí abajo ves cómo va.'],
    'ok_sin_foto' => ['exito', 'fa-circle-check', 'Listo, ya avisamos al equipo.', 'La foto no se pudo adjuntar (formato o tamaño), pero tu petición sí llegó.'],
    'duplicada' => ['info', 'fa-circle-info', 'Ya teníamos tu petición.', 'El equipo ya está en ello; no hace falta enviarla otra vez.'],
    'detalle' => ['error', 'fa-circle-exclamation', 'Falta un detalle.', 'Cuéntanos en pocas palabras qué necesitas y vuelve a enviarlo.'],
    'tipo' => ['error', 'fa-circle-exclamation', 'Elige qué necesitas.', 'Toca una de las opciones y vuelve a enviarlo.'],
    'limite' => ['aviso', 'fa-triangle-exclamation', 'Recibimos varias peticiones seguidas.', 'El equipo está en ello. Si es urgente, comunícate con recepción.'],
    'desactivadas' => ['info', 'fa-circle-info', 'Por ahora las peticiones se atienden en recepción.', 'Escríbenos o llámanos con los botones de abajo.'],
    'sin_estancia' => ['info', 'fa-circle-info', 'Esta habitación no tiene una estancia activa.', 'Para cualquier cosa, comunícate con recepción.'],
    'error' => ['error', 'fa-circle-exclamation', 'No pudimos enviar tu petición.', 'Intenta de nuevo en un momento o comunícate con recepción.'],
];
$aviso = $avisos[$resultado] ?? null;

$estadosHuesped = [
    'nueva' => ['Recibida', 'is-nueva', 'fa-inbox'],
    'en_atencion' => ['En camino', 'is-camino', 'fa-person-walking'],
    'resuelta' => ['Resuelta', 'is-resuelta', 'fa-check'],
    'descartada' => ['Cerrada', 'is-cerrada', 'fa-minus'],
];

$horaCorta = static function ($fecha) {
    $ts = strtotime((string) $fecha);
    if (!$ts) {
        return '';
    }
    $hora = EstanciaService::horaHumana(date('H:i', $ts));
    return date('Y-m-d', $ts) === date('Y-m-d') ? 'Hoy, ' . $hora : date('d/m', $ts) . ', ' . $hora;
};
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="referrer" content="no-referrer">
    <meta name="theme-color" content="<?= $tuSafe($colorPrimario) ?>">
    <title>Habitación <?= $tuSafe($numero) ?> · <?= $tuSafe($nombreHotel) ?></title>
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">
    <style>
    :root {
        --tu-primary: <?= $tuSafe($colorPrimario) ?>;
        --tu-on-primary: <?= $tuSafe($textoSobrePrimario) ?>;
        --tu-accent: <?= $tuSafe($colorAcento) ?>;
        --tu-bg: #F5F5F7;
        --tu-surface: #FFFFFF;
        --tu-ink: #1F2937;
        --tu-muted: #5B6475;
        --tu-line: #E3E5EA;
        --tu-ring: color-mix(in srgb, var(--tu-primary) 38%, transparent);
        --tu-soft: color-mix(in srgb, var(--tu-primary) 8%, #FFFFFF);
        /* Iconos sobre blanco: el color de marca oscurecido para que se lean
           tambien con paletas claras (azul cielo, verde menta...). */
        --tu-icon: color-mix(in srgb, var(--tu-primary) 58%, #0F172A);
        --tu-ok: #1E7F4F;
        --tu-ok-bg: #E8F5EE;
        --tu-info: #245B9B;
        --tu-info-bg: #EAF1FA;
        --tu-warn: #8A5A00;
        --tu-warn-bg: #FFF4DB;
        --tu-err: #B42318;
        --tu-err-bg: #FDECEA;
        --tu-radius: 16px;
        --tu-radius-in: 11px;
        color-scheme: light;
    }
    @media (prefers-color-scheme: dark) {
        :root {
            --tu-bg: #111214;
            --tu-surface: #1C1C1E;
            --tu-ink: #F2F3F5;
            --tu-muted: #A7ADB8;
            --tu-line: #34363B;
            --tu-soft: color-mix(in srgb, var(--tu-primary) 16%, #1C1C1E);
            --tu-icon: var(--tu-accent);
            --tu-ok: #6FD19E;
            --tu-ok-bg: #16301F;
            --tu-info: #8DB8F0;
            --tu-info-bg: #16243A;
            --tu-warn: #F2C063;
            --tu-warn-bg: #33270D;
            --tu-err: #F59A91;
            --tu-err-bg: #3A1714;
            color-scheme: dark;
        }
    }
    * { box-sizing: border-box; }
    html { -webkit-text-size-adjust: 100%; }
    body {
        margin: 0;
        font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
        font-size: 16px;
        line-height: 1.5;
        background: var(--tu-bg);
        color: var(--tu-ink);
    }
    .tu-hero {
        background: var(--tu-primary);
        color: var(--tu-on-primary);
        padding: calc(20px + env(safe-area-inset-top)) 16px 28px;
    }
    .tu-hero-in { max-width: 560px; margin: 0 auto; display: flex; align-items: center; gap: 14px; }
    .tu-logo {
        width: 52px; height: 52px; flex: none; border-radius: 50%;
        background: #FFFFFF; object-fit: contain; padding: 4px;
    }
    .tu-hotel { margin: 0; font-size: .95rem; font-weight: 600; opacity: .92; }
    .tu-room { margin: 2px 0 0; font-size: 1.75rem; font-weight: 700; line-height: 1.15; letter-spacing: -.01em; }
    .tu-shell { max-width: 560px; margin: -14px auto 0; padding: 0 16px calc(32px + env(safe-area-inset-bottom)); }
    .tu-card {
        background: var(--tu-surface);
        border: 1px solid var(--tu-line);
        border-radius: var(--tu-radius);
        padding: 18px 16px;
        margin-bottom: 14px;
    }
    .tu-card h2 {
        margin: 0 0 12px; font-size: 1.05rem; font-weight: 700;
        display: flex; align-items: center; gap: 10px;
    }
    .tu-card h2 .fas { color: var(--tu-icon); width: 1.25em; text-align: center; }
    .tu-lead { margin: -4px 0 14px; color: var(--tu-muted); font-size: .95rem; }

    .tu-aviso {
        display: flex; gap: 12px; align-items: flex-start;
        border-radius: var(--tu-radius); padding: 14px 16px; margin-bottom: 14px;
        border: 1px solid transparent;
    }
    .tu-aviso .fas { font-size: 1.15rem; margin-top: 2px; }
    .tu-aviso strong { display: block; }
    .tu-aviso p { margin: 2px 0 0; font-size: .92rem; }
    .tu-aviso.is-exito { background: var(--tu-ok-bg); color: var(--tu-ok); }
    .tu-aviso.is-info { background: var(--tu-info-bg); color: var(--tu-info); }
    .tu-aviso.is-aviso { background: var(--tu-warn-bg); color: var(--tu-warn); }
    .tu-aviso.is-error { background: var(--tu-err-bg); color: var(--tu-err); }
    .tu-card .tu-aviso { border-radius: var(--tu-radius-in); margin-top: -2px; }

    .tu-dato { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; }
    .tu-dato + .tu-dato { border-top: 1px solid var(--tu-line); }
    .tu-dato-lbl { display: block; color: var(--tu-muted); font-size: .88rem; }
    .tu-dato-val { display: block; margin-top: 2px; font-weight: 600; font-size: 1.05rem; overflow-wrap: anywhere; }
    .tu-copiado { display: block; min-height: 0; }
    .tu-dato-txt { min-width: 0; }

    .tu-btn, .tu-btn-sec {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-height: 48px; padding: 0 18px; border-radius: 12px;
        font: inherit; font-weight: 600; cursor: pointer; text-decoration: none;
        transition: transform .12s ease, box-shadow .12s ease, background-color .12s ease;
    }
    .tu-btn { background: var(--tu-primary); color: var(--tu-on-primary); border: 0; width: 100%; font-size: 1.02rem; }
    .tu-btn:disabled { opacity: .6; cursor: progress; }
    .tu-btn-sec { background: transparent; color: var(--tu-ink); border: 1px solid var(--tu-line); }
    .tu-btn-sec.is-mini { min-height: 44px; padding: 0 14px; font-size: .92rem; flex: none; }
    .tu-btn:focus-visible, .tu-btn-sec:focus-visible, .tu-op:focus-within, summary:focus-visible, textarea:focus-visible {
        outline: 3px solid var(--tu-ring); outline-offset: 2px;
    }
    @media (hover: hover) { .tu-btn:hover:not(:disabled) { box-shadow: 0 8px 18px -12px var(--tu-primary); } .tu-btn-sec:hover { background: var(--tu-soft); } }

    .tu-ops { border: 0; margin: 0 0 14px; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .tu-ops legend { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
    .tu-op {
        position: relative; display: flex; flex-direction: column; justify-content: center; gap: 8px;
        min-height: 88px; padding: 14px; border-radius: var(--tu-radius-in);
        border: 1px solid var(--tu-line); background: var(--tu-surface); cursor: pointer;
        font-weight: 600; font-size: .95rem; line-height: 1.25;
        transition: border-color .12s ease, background-color .12s ease;
    }
    label.tu-op:last-of-type:nth-of-type(odd) { grid-column: 1 / -1; min-height: 64px; flex-direction: row; align-items: center; justify-content: flex-start; }
    .tu-op input { position: absolute; opacity: 0; inset: 0; margin: 0; cursor: pointer; }
    .tu-op .fas { font-size: 1.3rem; color: var(--tu-icon); width: 1.3em; }
    .tu-op:has(input:checked) { border-color: var(--tu-primary); background: var(--tu-soft); box-shadow: inset 0 0 0 1px var(--tu-primary); }
    .tu-op .tu-check { position: absolute; top: 10px; right: 10px; font-size: .9rem; opacity: 0; transition: opacity .12s ease; }
    .tu-op:has(input:checked) .tu-check { opacity: 1; }

    .tu-campo { margin: 0 0 14px; }
    .tu-campo label, .tu-campo .tu-lbl { display: block; font-weight: 600; font-size: .95rem; margin-bottom: 6px; }
    .tu-campo textarea {
        width: 100%; min-height: 92px; resize: vertical; padding: 12px;
        border: 1px solid var(--tu-line); border-radius: var(--tu-radius-in);
        background: var(--tu-surface); color: var(--tu-ink); font: inherit;
    }
    .tu-ayuda { margin: 6px 0 0; font-size: .88rem; color: var(--tu-muted); }
    .tu-foto-btn {
        display: flex; align-items: center; gap: 10px; min-height: 48px; padding: 10px 14px;
        border: 1px dashed var(--tu-line); border-radius: var(--tu-radius-in); cursor: pointer; font-weight: 600;
    }
    .tu-foto-btn input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .tu-foto-btn:focus-within { outline: 3px solid var(--tu-ring); outline-offset: 2px; }
    .js .tu-pendiente[hidden] { display: none; }

    .tu-lista { list-style: none; margin: 0; padding: 0; }
    .tu-lista li { display: flex; align-items: center; gap: 12px; padding: 12px 0; }
    .tu-lista li + li { border-top: 1px solid var(--tu-line); }
    .tu-lista .fas.tu-ico { color: var(--tu-muted); width: 1.25em; text-align: center; }
    .tu-lista-txt { flex: 1; min-width: 0; }
    .tu-lista-txt strong { display: block; font-size: .96rem; }
    .tu-lista-txt small { color: var(--tu-muted); font-size: .86rem; }
    .tu-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .82rem; font-weight: 600; white-space: nowrap; }
    .tu-chip.is-nueva { background: var(--tu-info-bg); color: var(--tu-info); }
    .tu-chip.is-camino { background: var(--tu-warn-bg); color: var(--tu-warn); }
    .tu-chip.is-resuelta { background: var(--tu-ok-bg); color: var(--tu-ok); }
    .tu-chip.is-cerrada { background: color-mix(in srgb, var(--tu-muted) 14%, transparent); color: var(--tu-muted); }

    .tu-texto p { margin: 0 0 10px; }
    .tu-texto p:last-child { margin-bottom: 0; }
    details.tu-card summary {
        cursor: pointer; font-weight: 700; font-size: 1.05rem; list-style: none;
        display: flex; align-items: center; gap: 10px; min-height: 28px;
    }
    details.tu-card summary::-webkit-details-marker { display: none; }
    details.tu-card summary .tu-flecha { margin-left: auto; transition: transform .15s ease; color: var(--tu-muted); }
    details.tu-card[open] summary .tu-flecha { transform: rotate(180deg); }
    details.tu-card summary .fas:first-child { color: var(--tu-icon); width: 1.25em; text-align: center; }
    details.tu-card .tu-texto { margin-top: 12px; }
    .tu-contacto { display: grid; gap: 10px; }
    .tu-contacto .tu-btn-sec { width: 100%; }
    .tu-pie { text-align: center; color: var(--tu-muted); font-size: .84rem; margin: 22px 0 0; }
    @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    @media (min-width: 420px) { .tu-contacto { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>
<header class="tu-hero">
    <div class="tu-hero-in">
        <?php if ($logoUrl): ?>
            <img class="tu-logo" src="<?= $tuSafe($logoUrl) ?>" alt="" width="52" height="52" onerror="this.remove()">
        <?php endif; ?>
        <div>
            <p class="tu-hotel"><?= $tuSafe($nombreHotel) ?></p>
            <h1 class="tu-room">Habitación <?= $tuSafe($numero) ?></h1>
        </div>
    </div>
</header>

<main class="tu-shell">
    <?php if ($wifiRed !== '' || $wifiClave !== ''): ?>
        <section class="tu-card" aria-labelledby="tu-wifi">
            <h2 id="tu-wifi"><i class="fas fa-wifi" aria-hidden="true"></i>Wi-Fi</h2>
            <div>
                <?php if ($wifiRed !== ''): ?>
                    <div class="tu-dato"><div class="tu-dato-txt"><span class="tu-dato-lbl">Red</span><span class="tu-dato-val"><?= $tuSafe($wifiRed) ?></span></div></div>
                <?php endif; ?>
                <?php if ($wifiClave !== ''): ?>
                    <div class="tu-dato">
                        <div class="tu-dato-txt"><span class="tu-dato-lbl">Contraseña</span><span class="tu-dato-val ms-copiable" id="tu-clave"><?= $tuSafe($wifiClave) ?></span></div>
                        <button type="button" class="tu-btn-sec is-mini" data-copiar="#tu-clave" aria-describedby="tu-copiado">
                            <i class="fas fa-copy" aria-hidden="true"></i><span>Copiar</span>
                        </button>
                    </div>
                    <span id="tu-copiado" class="tu-ayuda tu-copiado" role="status" aria-live="polite"></span>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="tu-card" id="peticiones" aria-labelledby="tu-pedir">
        <h2 id="tu-pedir"><i class="fas fa-bell-concierge" aria-hidden="true"></i>¿Necesitas algo?</h2>

        <?php if ($aviso): /* Dentro de la seccion: el regreso cae en #peticiones y el aviso queda a la vista. */ ?>
            <div class="tu-aviso is-<?= $tuSafe($aviso[0]) ?>" role="<?= $aviso[0] === 'error' ? 'alert' : 'status' ?>">
                <i class="fas <?= $tuSafe($aviso[1]) ?>" aria-hidden="true"></i>
                <div><strong><?= $tuSafe($aviso[2]) ?></strong><p><?= $tuSafe($aviso[3]) ?></p></div>
            </div>
        <?php endif; ?>

        <?php if ($aceptaPeticiones): ?>
            <p class="tu-lead">Pídelo aquí y le llega directo al equipo del hotel.</p>
            <form method="POST" action="<?= $tuSafe($accion) ?>" enctype="multipart/form-data" id="tu-form" novalidate>
                <fieldset class="tu-ops">
                    <legend>¿Qué necesitas?</legend>
                    <?php foreach ($tipos as $clave => $meta): ?>
                        <label class="tu-op">
                            <input type="radio" name="tipo" value="<?= $tuSafe($clave) ?>" required
                                   data-pide-detalle="<?= !empty($meta['pide_detalle']) ? '1' : '0' ?>"
                                   data-permite-foto="<?= !empty($meta['permite_foto']) ? '1' : '0' ?>"
                                   data-ayuda="<?= $tuSafe($meta['ayuda'] ?? '') ?>">
                            <i class="fas <?= $tuSafe($meta['icono']) ?>" aria-hidden="true"></i>
                            <span><?= $tuSafe($meta['etiqueta']) ?></span>
                            <i class="fas fa-circle-check tu-check" aria-hidden="true"></i>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <div class="tu-pendiente" data-tu-pendiente>
                    <div class="tu-campo">
                        <label for="tu-detalle" data-tu-label>Detalles <span data-tu-opcional>(opcional)</span></label>
                        <textarea id="tu-detalle" name="detalle" maxlength="<?= EstanciaService::DETALLE_MAX ?>" rows="3" aria-describedby="tu-detalle-ayuda"></textarea>
                        <p class="tu-ayuda" id="tu-detalle-ayuda" data-tu-ayuda>Si quieres, agrega algo para el equipo.</p>
                    </div>

                    <div class="tu-campo" data-tu-foto hidden>
                        <label class="tu-foto-btn" for="tu-foto">
                            <i class="fas fa-camera" aria-hidden="true"></i>
                            <span data-tu-foto-texto>Agregar una foto (opcional)</span>
                            <input type="file" id="tu-foto" name="foto" accept="image/jpeg,image/png,image/webp">
                        </label>
                        <p class="tu-ayuda" data-tu-foto-ayuda>Ayuda al equipo a llegar con lo necesario. Máximo 8 MB.</p>
                    </div>

                    <button type="submit" class="tu-btn" data-tu-enviar>
                        <i class="fas fa-paper-plane" aria-hidden="true"></i><span>Enviar al equipo</span>
                    </button>
                </div>
            </form>
        <?php elseif ($hayEstancia): ?>
            <p class="tu-lead" style="margin-bottom:0">Por ahora las peticiones se atienden directo en recepción. Escríbenos o llámanos con los botones de abajo.</p>
        <?php else: ?>
            <p class="tu-lead" style="margin-bottom:0">Las peticiones desde este código se activan durante tu estancia. Mientras tanto, recepción te atiende con gusto.</p>
        <?php endif; ?>

        <?php if (!empty($peticiones)): ?>
            <h3 style="margin:18px 0 4px;font-size:.98rem">Tus peticiones</h3>
            <ul class="tu-lista">
                <?php foreach ($peticiones as $p):
                    $meta = $tipos[$p['tipo']] ?? null;
                    $estado = $estadosHuesped[$p['estado']] ?? ['Recibida', 'is-nueva', 'fa-inbox'];
                ?>
                    <li>
                        <i class="fas <?= $tuSafe($meta['icono'] ?? 'fa-bell-concierge') ?> tu-ico" aria-hidden="true"></i>
                        <div class="tu-lista-txt">
                            <strong><?= $tuSafe($meta['etiqueta'] ?? 'Petición') ?></strong>
                            <small><?= $tuSafe($horaCorta($p['created_at'] ?? '')) ?></small>
                        </div>
                        <span class="tu-chip <?= $tuSafe($estado[1]) ?>"><i class="fas <?= $tuSafe($estado[2]) ?>" aria-hidden="true"></i><?= $tuSafe($estado[0]) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if ($checkout !== '' || $checkin !== ''): ?>
        <section class="tu-card" aria-labelledby="tu-horarios">
            <h2 id="tu-horarios"><i class="fas fa-clock" aria-hidden="true"></i>Horarios</h2>
            <div>
                <?php if ($checkout !== ''): ?>
                    <div class="tu-dato"><div class="tu-dato-txt"><span class="tu-dato-lbl">Salida (check-out)</span><span class="tu-dato-val">Hasta las <?= $tuSafe($checkout) ?></span></div></div>
                <?php endif; ?>
                <?php if ($checkin !== ''): ?>
                    <div class="tu-dato"><div class="tu-dato-txt"><span class="tu-dato-lbl">Entrada (check-in)</span><span class="tu-dato-val">Desde las <?= $tuSafe($checkin) ?></span></div></div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($info !== ''): ?>
        <section class="tu-card" aria-labelledby="tu-info">
            <h2 id="tu-info"><i class="fas fa-circle-info" aria-hidden="true"></i>Información útil</h2>
            <div class="tu-texto"><?= $tuParrafos($info) ?></div>
        </section>
    <?php endif; ?>

    <?php if ($reglamento !== ''): ?>
        <details class="tu-card">
            <summary><i class="fas fa-scroll" aria-hidden="true"></i>Reglamento del hotel<i class="fas fa-chevron-down tu-flecha" aria-hidden="true"></i></summary>
            <div class="tu-texto"><?= $tuParrafos($reglamento) ?></div>
        </details>
    <?php endif; ?>

    <?php if ($linkWa || $linkTel): ?>
        <section class="tu-card" aria-labelledby="tu-recepcion">
            <h2 id="tu-recepcion"><i class="fas fa-headset" aria-hidden="true"></i>Recepción</h2>
            <div class="tu-contacto">
                <?php if ($linkWa): ?>
                    <a class="tu-btn-sec" href="<?= $tuSafe($linkWa) ?>" rel="noopener noreferrer"><i class="fab fa-whatsapp" aria-hidden="true"></i>Escribir por WhatsApp</a>
                <?php endif; ?>
                <?php if ($linkTel): ?>
                    <a class="tu-btn-sec" href="<?= $tuSafe($linkTel) ?>"><i class="fas fa-phone" aria-hidden="true"></i>Llamar</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <p class="tu-pie">Lo que pides aquí le llega directo al equipo de <?= $tuSafe($nombreHotel) ?>.</p>
</main>

<script>
(function () {
    document.documentElement.classList.add('js');

    // Copiar la clave del Wi-Fi.
    document.querySelectorAll('[data-copiar]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var origen = document.querySelector(btn.getAttribute('data-copiar'));
            var aviso = document.getElementById('tu-copiado');
            if (!origen) { return; }
            var texto = origen.textContent.trim();
            var listo = function () {
                var span = btn.querySelector('span');
                if (span) { span.textContent = 'Copiada'; }
                if (aviso) { aviso.textContent = 'Contraseña copiada.'; }
                setTimeout(function () { if (span) { span.textContent = 'Copiar'; } if (aviso) { aviso.textContent = ''; } }, 2200);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(texto).then(listo, function () { seleccionar(origen); });
            } else {
                seleccionar(origen);
            }
        });
    });
    function seleccionar(el) {
        var rango = document.createRange();
        rango.selectNodeContents(el);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(rango);
    }

    var form = document.getElementById('tu-form');
    if (!form) { return; }

    var pendiente = form.querySelector('[data-tu-pendiente]');
    var detalle = form.querySelector('#tu-detalle');
    var opcional = form.querySelector('[data-tu-opcional]');
    var ayuda = form.querySelector('[data-tu-ayuda]');
    var bloqueFoto = form.querySelector('[data-tu-foto]');
    var foto = form.querySelector('#tu-foto');
    var fotoTexto = form.querySelector('[data-tu-foto-texto]');
    var fotoAyuda = form.querySelector('[data-tu-foto-ayuda]');
    var enviar = form.querySelector('[data-tu-enviar]');
    var MAX = <?= (int) EstanciaService::FOTO_MAX_BYTES ?>;

    // Divulgacion progresiva: sin JS todo se ve; con JS primero se elige.
    pendiente.hidden = true;

    function elegido() { return form.querySelector('input[name="tipo"]:checked'); }

    form.querySelectorAll('input[name="tipo"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var pide = radio.getAttribute('data-pide-detalle') === '1';
            var conFoto = radio.getAttribute('data-permite-foto') === '1';
            pendiente.hidden = false;
            detalle.required = pide;
            opcional.hidden = pide;
            ayuda.textContent = radio.getAttribute('data-ayuda') || '';
            detalle.setAttribute('placeholder', pide ? 'Escribe aquí' : '');
            bloqueFoto.hidden = !conFoto;
            if (!conFoto && foto) { foto.value = ''; fotoTexto.textContent = 'Agregar una foto (opcional)'; }
            if (pide) { detalle.focus({ preventScroll: false }); }
        });
    });

    if (foto) {
        foto.addEventListener('change', function () {
            var archivo = foto.files && foto.files[0];
            if (!archivo) { fotoTexto.textContent = 'Agregar una foto (opcional)'; return; }
            if (archivo.size > MAX) {
                foto.value = '';
                fotoTexto.textContent = 'Agregar una foto (opcional)';
                fotoAyuda.textContent = 'Esa foto pesa más de 8 MB. Elige otra o envía sin foto.';
                return;
            }
            fotoTexto.textContent = 'Foto lista: ' + archivo.name;
            fotoAyuda.textContent = 'Puedes cambiarla tocando aquí otra vez.';
        });
    }

    form.addEventListener('submit', function (e) {
        var tipo = elegido();
        if (!tipo) {
            e.preventDefault();
            form.querySelector('input[name="tipo"]').focus();
            return;
        }
        if (detalle.required && detalle.value.trim().length < 3) {
            e.preventDefault();
            ayuda.textContent = 'Cuéntanos en pocas palabras qué necesitas.';
            detalle.focus();
            return;
        }
        enviar.disabled = true;
        enviar.querySelector('span').textContent = 'Enviando…';
    });

    // Volver con "atras" (bfcache) no debe dejar el boton bloqueado.
    window.addEventListener('pageshow', function () {
        enviar.disabled = false;
        enviar.querySelector('span').textContent = 'Enviar al equipo';
    });
})();
</script>
</body>
</html>
