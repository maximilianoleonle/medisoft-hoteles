<?php
/**
 * Pagina publica de reservas (motor_reservas) - Fase 3: flujo de 3 pasos.
 * Standalone (sin layout interno). Identidad del hotel via tokens --brand-*.
 *
 * Paso 1: fechas y personas  ->  Paso 2: elegir habitacion  ->  Paso 3: datos + resumen.
 * El CTA final llama POST /h/{slug}/reservar/iniciar-pago (se implementa en Fase 4);
 * mientras no exista pasarela configurada, el servidor responde con mensaje claro.
 */
$hotel = $hotel ?? [];
$branding = $branding ?? [];
$politica = $politica ?? '';
$anticipacionMaxDias = (int) ($anticipacionMaxDias ?? 180);

$nombreHotel = htmlspecialchars((string) ($branding['nombre_visual'] ?? $hotel['nombre'] ?? 'Hotel'), ENT_QUOTES, 'UTF-8');
$monedaSimbolo = htmlspecialchars((string) ($hotel['moneda_simbolo'] ?? '$'), ENT_QUOTES, 'UTF-8');
$colorPrimario = htmlspecialchars((string) ($branding['color_primary'] ?? '#1B2746'), ENT_QUOTES, 'UTF-8');
$colorSecundario = htmlspecialchars((string) ($branding['color_secondary'] ?? '#0F172A'), ENT_QUOTES, 'UTF-8');
$colorAcento = htmlspecialchars((string) ($branding['color_accent'] ?? '#BD9441'), ENT_QUOTES, 'UTF-8');
$logoUrl = function_exists('hotel_branding_asset_url') ? hotel_branding_asset_url($branding['logo_url'] ?? null) : null;
$fechaMin = date('Y-m-d');
$fechaMax = date('Y-m-d', strtotime('+' . max(1, $anticipacionMaxDias) . ' days'));
$slugSeguro = (string) ($hotel['slug'] ?? '');
$apiDisponibilidad = url('h/' . $slugSeguro . '/reservar/api/disponibilidad');
$apiIniciarPago = url('h/' . $slugSeguro . '/reservar/iniciar-pago');
$apiCupon = url('h/' . $slugSeguro . '/reservar/api/cupon');
$promocionesActivo = (bool) ($promocionesActivo ?? false);
$anticipoTipo = (string) ($anticipoTipo ?? 'porcentaje');
$extrasDisponibles = is_array($extrasDisponibles ?? null) ? $extrasDisponibles : [];

// Idioma (bloque motor_idiomas): 'en' solo si el controlador lo autorizo.
$idiomasActivo = (bool) ($idiomasActivo ?? false);
$lang = in_array(($lang ?? 'es'), ['es', 'en'], true) ? $lang : 'es';
$textos = require __DIR__ . '/_textos.php';
$L = $textos[$lang] ?? $textos['es'];
$LJS = $L['js'];
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <title><?= $lang === 'en' ? 'Book' : 'Reservar' ?> - <?= $nombreHotel ?></title>
    <style>
    :root {
        --brand-primary: <?= $colorPrimario ?>;
        --brand-secondary: <?= $colorSecundario ?>;
        --brand-accent: <?= $colorAcento ?>;
        --mr-line: color-mix(in srgb, var(--brand-primary) 12%, #E6E2D8);
        --mr-muted: #6B7486;
        --mr-bg: color-mix(in srgb, var(--brand-accent) 6%, #F7F5F0);
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Inter", "Segoe UI", system-ui, sans-serif; background: var(--mr-bg); color: #2A3242; }
    .mr-shell { max-width: 780px; margin: 0 auto; padding: 20px 16px 56px; }
    .mr-header { text-align: center; padding: 18px 0 6px; }
    .mr-header img { height: 58px; width: 58px; object-fit: contain; border-radius: 50%; background: #fff; padding: 6px; box-shadow: 0 6px 18px -12px rgba(20,28,45,.45); }
    .mr-header h1 { margin: 10px 0 4px; font-size: 1.45rem; color: var(--brand-primary); }
    .mr-header p { margin: 0; color: var(--mr-muted); font-size: .9rem; }

    /* Indicador de pasos */
    .mr-steps { display: flex; justify-content: center; gap: 6px; margin: 18px 0 4px; }
    .mr-step { display: flex; align-items: center; gap: 7px; padding: 6px 12px; border-radius: 999px; font-size: .78rem; font-weight: 600; color: var(--mr-muted); background: #fff; border: 1px solid var(--mr-line); }
    .mr-step .n { display: grid; place-items: center; width: 20px; height: 20px; border-radius: 50%; background: #E9E5DB; color: var(--mr-muted); font-size: .72rem; font-weight: 700; }
    .mr-step.activo { color: var(--brand-primary); border-color: color-mix(in srgb, var(--brand-primary) 35%, var(--mr-line)); }
    .mr-step.activo .n { background: var(--brand-primary); color: #fff; }
    .mr-step.hecho .n { background: color-mix(in srgb, var(--brand-accent) 80%, #8a6a20); color: #fff; }
    .mr-step span.txt { display: none; }
    @media (min-width: 560px) { .mr-step span.txt { display: inline; } }

    .mr-card { margin-top: 14px; background: #fff; border: 1px solid var(--mr-line); border-radius: 16px; padding: 20px; box-shadow: 0 16px 36px -30px rgba(20,28,45,.55); }
    .mr-panel { display: none; }
    .mr-panel.activo { display: block; }
    .mr-titulo-paso { margin: 0 0 14px; font-size: 1.05rem; color: var(--brand-primary); }

    .mr-form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .mr-field { display: grid; gap: 5px; }
    .mr-field.full { grid-column: 1 / -1; }
    .mr-field label { font-size: .78rem; font-weight: 600; color: #55607A; }
    .mr-field input, .mr-field select { min-height: 46px; border: 1px solid #D8D4C9; border-radius: 10px; padding: 0 12px; font-size: 1rem; width: 100%; background: #fff; }
    .mr-field input:focus, .mr-field select:focus { outline: 2px solid color-mix(in srgb, var(--brand-primary) 45%, transparent); border-color: var(--brand-primary); }
    .mr-field .err { font-size: .75rem; color: #B3382F; min-height: 1em; }

    .mr-btn { min-height: 48px; border: 0; border-radius: 10px; cursor: pointer; background: var(--brand-primary); color: #fff; font-size: 1rem; font-weight: 700; width: 100%; transition: opacity .15s ease, transform .12s ease; }
    .mr-btn:hover { opacity: .92; }
    .mr-btn:active { transform: scale(.99); }
    .mr-btn:disabled { opacity: .55; cursor: wait; }
    .mr-btn.full { grid-column: 1 / -1; }
    .mr-volver { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px; background: none; border: 0; cursor: pointer; color: var(--mr-muted); font-size: .85rem; font-weight: 600; padding: 6px 0; }
    .mr-volver:hover { color: var(--brand-primary); }

    .mr-estado { margin-top: 14px; text-align: center; color: var(--mr-muted); font-size: .92rem; }
    .mr-error { color: #B3382F; }

    /* Skeleton de carga */
    .mr-skel { border: 1px solid #EEEAE0; border-radius: 12px; padding: 12px; margin-top: 12px; display: grid; grid-template-columns: 92px 1fr; gap: 12px; }
    .mr-skel div { border-radius: 8px; background: linear-gradient(90deg, #F0EDE5 25%, #FAF8F2 50%, #F0EDE5 75%); background-size: 200% 100%; animation: mr-shimmer 1.2s infinite; }
    .mr-skel .ph-img { height: 70px; }
    .mr-skel .ph-lineas { display: grid; gap: 8px; align-content: center; background: none; animation: none; }
    .mr-skel .ph-lineas div { height: 13px; }
    .mr-skel .ph-lineas div:last-child { width: 55%; }
    @keyframes mr-shimmer { to { background-position: -200% 0; } }

    /* Tarjetas de tipo (paso 2) */
    .mr-tipo { display: grid; grid-template-columns: 92px 1fr auto; gap: 14px; align-items: center; border: 1px solid #E8E4DA; border-radius: 12px; padding: 12px; margin-top: 12px; background: #fff; transition: border-color .15s ease, box-shadow .15s ease; }
    .mr-tipo:hover { border-color: color-mix(in srgb, var(--brand-primary) 32%, #E8E4DA); box-shadow: 0 10px 24px -22px rgba(20,28,45,.5); }
    .mr-tipo img, .mr-tipo .mr-foto-ph { width: 92px; height: 70px; object-fit: cover; border-radius: 8px; background: #EEEBE2; }
    .mr-foto-ph { display: grid; place-items: center; color: #B9B2A2; font-size: 1.5rem; }
    .mr-tipo h3 { margin: 0; font-size: 1rem; color: var(--brand-primary); text-transform: capitalize; }
    .mr-tipo .mr-meta { margin: 3px 0 0; font-size: .8rem; color: var(--mr-muted); }
    .mr-tipo .mr-anticipo { margin: 5px 0 0; font-size: .76rem; font-weight: 700; color: color-mix(in srgb, var(--brand-accent) 82%, #7a5c14); }
    .mr-cta-tipo { display: grid; gap: 8px; justify-items: end; }
    .mr-precio { text-align: right; }
    .mr-precio strong { display: block; font-size: 1.1rem; color: var(--brand-secondary); }
    .mr-precio span { font-size: .74rem; color: var(--mr-muted); }
    .mr-elegir { min-height: 40px; padding: 0 18px; border: 0; border-radius: 9px; cursor: pointer; background: var(--brand-primary); color: #fff; font-size: .86rem; font-weight: 700; }
    .mr-elegir:hover { opacity: .92; }

    /* Paso 3: resumen + datos */
    .mr-resumen { border: 1px solid var(--mr-line); border-radius: 12px; overflow: hidden; margin-bottom: 18px; }
    .mr-resumen-head { background: color-mix(in srgb, var(--brand-primary) 5%, #FBFAF6); padding: 12px 14px; font-size: .9rem; font-weight: 700; color: var(--brand-primary); border-bottom: 1px solid var(--mr-line); }
    .mr-resumen dl { margin: 0; padding: 6px 14px; }
    .mr-resumen .fila { display: flex; justify-content: space-between; gap: 10px; padding: 7px 0; font-size: .88rem; border-bottom: 1px dashed #EEEAE0; }
    .mr-resumen .fila:last-child { border-bottom: 0; }
    .mr-resumen dt { color: var(--mr-muted); }
    .mr-resumen dd { margin: 0; font-weight: 600; text-align: right; }
    .mr-resumen .fila.total dd { font-size: 1.02rem; color: var(--brand-secondary); }
    .mr-resumen .fila.anticipo { background: color-mix(in srgb, var(--brand-accent) 8%, transparent); margin: 0 -14px; padding: 10px 14px; border-radius: 0; }
    .mr-resumen .fila.anticipo dt, .mr-resumen .fila.anticipo dd { color: color-mix(in srgb, var(--brand-accent) 85%, #6d520f); font-weight: 800; }

    .mr-politica { margin-top: 18px; font-size: .8rem; color: #778092; text-align: center; line-height: 1.5; }
    .mr-footer { margin-top: 26px; text-align: center; font-size: .74rem; color: #9AA0AE; }
    .mr-exito { text-align: center; padding: 8px 0 4px; }
    .mr-exito .ico { font-size: 2rem; }
    </style>
</head>
<body>
<div class="mr-shell">
    <header class="mr-header">
        <?php if ($logoUrl): ?>
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $nombreHotel ?>">
        <?php endif; ?>
        <h1><?= $nombreHotel ?></h1>
        <p><?= htmlspecialchars($L['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($idiomasActivo): ?>
            <p style="margin-top:8px;font-size:.8rem;">
                <a href="?lang=es" style="font-weight:<?= $lang === 'es' ? '800' : '400' ?>;color:var(--brand-primary);text-decoration:none;">Español</a>
                <span style="color:#B9B2A2;"> · </span>
                <a href="?lang=en" style="font-weight:<?= $lang === 'en' ? '800' : '400' ?>;color:var(--brand-primary);text-decoration:none;">English</a>
            </p>
        <?php endif; ?>
    </header>

    <nav class="mr-steps" aria-label="Progreso de tu reservacion">
        <div class="mr-step activo" data-step-chip="1"><span class="n">1</span><span class="txt"><?= htmlspecialchars($L['paso_fechas'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="mr-step" data-step-chip="2"><span class="n">2</span><span class="txt"><?= htmlspecialchars($L['paso_habitacion'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="mr-step" data-step-chip="3"><span class="n">3</span><span class="txt"><?= htmlspecialchars($L['paso_datos'], ENT_QUOTES, 'UTF-8') ?></span></div>
    </nav>

    <div class="mr-card">
        <!-- PASO 1: fechas -->
        <section class="mr-panel activo" data-panel="1" aria-label="Paso 1: elige tus fechas">
            <h2 class="mr-titulo-paso"><?= htmlspecialchars($L['paso1_titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
            <form id="mr-form-fechas" class="mr-form" autocomplete="off">
                <div class="mr-field">
                    <label for="mr-entrada"><?= htmlspecialchars($L['llegada'], ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="date" id="mr-entrada" min="<?= $fechaMin ?>" max="<?= $fechaMax ?>" required>
                </div>
                <div class="mr-field">
                    <label for="mr-salida"><?= htmlspecialchars($L['salida'], ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="date" id="mr-salida" min="<?= $fechaMin ?>" max="<?= $fechaMax ?>" required>
                </div>
                <div class="mr-field full">
                    <label for="mr-personas"><?= htmlspecialchars($L['personas'], ENT_QUOTES, 'UTF-8') ?></label>
                    <select id="mr-personas">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>><?= $i ?> <?= $i > 1 ? htmlspecialchars($L['persona_varias'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($L['persona_uno'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="mr-btn full" id="mr-buscar"><?= htmlspecialchars($L['ver_disponibilidad'], ENT_QUOTES, 'UTF-8') ?></button>
            </form>
            <div id="mr-estado-1" class="mr-estado" aria-live="polite"></div>
        </section>

        <!-- PASO 2: habitaciones -->
        <section class="mr-panel" data-panel="2" aria-label="Paso 2: elige tu habitacion">
            <button type="button" class="mr-volver" data-volver="1">&larr; <?= htmlspecialchars($L['cambiar_fechas'], ENT_QUOTES, 'UTF-8') ?></button>
            <h2 class="mr-titulo-paso"><?= htmlspecialchars($L['paso2_titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div id="mr-estado-2" class="mr-estado" aria-live="polite"></div>
            <div id="mr-resultados"></div>
        </section>

        <!-- PASO 3: datos y resumen -->
        <section class="mr-panel" data-panel="3" aria-label="Paso 3: tus datos y confirmacion">
            <button type="button" class="mr-volver" data-volver="2">&larr; <?= htmlspecialchars($L['elegir_otra'], ENT_QUOTES, 'UTF-8') ?></button>
            <h2 class="mr-titulo-paso"><?= htmlspecialchars($L['paso3_titulo'], ENT_QUOTES, 'UTF-8') ?></h2>

            <div class="mr-resumen">
                <div class="mr-resumen-head" id="mr-res-titulo"><?= htmlspecialchars($L['resumen_head'], ENT_QUOTES, 'UTF-8') ?></div>
                <dl>
                    <div class="fila"><dt><?= htmlspecialchars($L['res_fechas'], ENT_QUOTES, 'UTF-8') ?></dt><dd id="mr-res-fechas">—</dd></div>
                    <div class="fila"><dt><?= htmlspecialchars($L['res_noches'], ENT_QUOTES, 'UTF-8') ?></dt><dd id="mr-res-noches">—</dd></div>
                    <div class="fila"><dt><?= htmlspecialchars($L['res_habitacion'], ENT_QUOTES, 'UTF-8') ?></dt><dd id="mr-res-tipo">—</dd></div>
                    <div class="fila"><dt><?= htmlspecialchars($L['res_personas'], ENT_QUOTES, 'UTF-8') ?></dt><dd id="mr-res-personas">—</dd></div>
                    <div class="fila" id="mr-res-desc-fila" style="display:none;color:#15803D;"><dt id="mr-res-desc-label"><?= htmlspecialchars($L['res_descuento'], ENT_QUOTES, 'UTF-8') ?></dt><dd id="mr-res-desc">—</dd></div>
                    <div class="fila" id="mr-res-extras-fila" style="display:none;"><dt><?= htmlspecialchars($L['res_extras'], ENT_QUOTES, 'UTF-8') ?></dt><dd id="mr-res-extras">—</dd></div>
                    <div class="fila total"><dt><?= htmlspecialchars($L['res_total'], ENT_QUOTES, 'UTF-8') ?></dt><dd id="mr-res-total">—</dd></div>
                    <div class="fila anticipo"><dt><?= htmlspecialchars($L['res_anticipo'], ENT_QUOTES, 'UTF-8') ?></dt><dd id="mr-res-anticipo">—</dd></div>
                    <div class="fila"><dt><?= htmlspecialchars($L['res_saldo'], ENT_QUOTES, 'UTF-8') ?></dt><dd id="mr-res-saldo">—</dd></div>
                </dl>
            </div>

            <?php if (!empty($extrasDisponibles)): ?>
            <div class="mr-extras" style="margin:14px 0 4px;">
                <div style="font-size:.82rem;font-weight:700;color:#55607A;margin-bottom:8px;"><?= htmlspecialchars($L['extras_titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php foreach ($extrasDisponibles as $extra): ?>
                    <label style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border:1px solid var(--mr-line);border-radius:10px;margin-bottom:8px;cursor:pointer;background:#fff;">
                        <input type="checkbox" class="mr-extra-check" style="margin-top:3px;"
                               value="<?= (int) $extra['id'] ?>"
                               data-nombre="<?= htmlspecialchars((string) $extra['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                               data-precio="<?= htmlspecialchars(number_format((float) $extra['precio'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                               data-tipo="<?= htmlspecialchars((string) $extra['tipo_cobro'], ENT_QUOTES, 'UTF-8') ?>">
                        <span style="flex:1;">
                            <span style="font-weight:700;color:#2A3242;"><?= htmlspecialchars((string) $extra['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="mr-extra-precio" style="float:right;font-weight:700;color:var(--brand-primary);"></span>
                            <?php if (!empty($extra['descripcion'])): ?>
                                <br><span style="font-size:.8rem;color:#8A93A6;"><?= htmlspecialchars((string) $extra['descripcion'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($promocionesActivo): ?>
            <div class="mr-cupon" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:12px 0 4px;">
                <input type="text" id="mr-cupon-codigo" maxlength="30" placeholder="<?= htmlspecialchars($L['cupon_placeholder'], ENT_QUOTES, 'UTF-8') ?>"
                       style="flex:1;min-width:200px;min-height:44px;border:1px solid var(--mr-line);border-radius:10px;padding:0 12px;font-size:.95rem;text-transform:uppercase;" autocomplete="off">
                <button type="button" id="mr-cupon-aplicar"
                        style="min-height:44px;padding:0 16px;border:1px solid var(--brand-primary);border-radius:10px;background:#fff;color:var(--brand-primary);font-weight:700;cursor:pointer;"><?= htmlspecialchars($L['cupon_aplicar'], ENT_QUOTES, 'UTF-8') ?></button>
                <div id="mr-cupon-estado" style="width:100%;font-size:.82rem;" aria-live="polite"></div>
            </div>
            <?php endif; ?>

            <form id="mr-form-datos" class="mr-form" autocomplete="on">
                <div class="mr-field full">
                    <label for="mr-nombre"><?= htmlspecialchars($L['nombre_label'], ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" id="mr-nombre" name="nombre" maxlength="150" required autocomplete="name" placeholder="<?= htmlspecialchars($L['nombre_ph'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="err" data-err="nombre"></span>
                </div>
                <div class="mr-field">
                    <label for="mr-telefono"><?= htmlspecialchars($L['telefono_label'], ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="tel" id="mr-telefono" name="telefono" maxlength="20" required inputmode="tel" autocomplete="tel" placeholder="<?= htmlspecialchars($L['telefono_ph'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="err" data-err="telefono"></span>
                </div>
                <div class="mr-field">
                    <label for="mr-email"><?= htmlspecialchars($L['email_label'], ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="email" id="mr-email" name="email" maxlength="120" required autocomplete="email" placeholder="<?= htmlspecialchars($L['email_ph'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="err" data-err="email"></span>
                </div>
                <button type="submit" class="mr-btn full" id="mr-pagar"><?= htmlspecialchars($L['continuar_pago'], ENT_QUOTES, 'UTF-8') ?></button>
            </form>
            <div id="mr-estado-3" class="mr-estado" aria-live="polite"></div>
        </section>
    </div>

    <?php if (trim((string) $politica) !== ''): ?>
        <p class="mr-politica"><?= htmlspecialchars($politica, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <p class="mr-footer"><?= str_replace('{hotel}', $nombreHotel, htmlspecialchars($L['footer'], ENT_QUOTES, 'UTF-8')) ?></p>
</div>

<script>
(function () {
    var simbolo = <?= json_encode($monedaSimbolo) ?>;
    var apiDisponibilidad = <?= json_encode($apiDisponibilidad) ?>;
    var apiIniciarPago = <?= json_encode($apiIniciarPago) ?>;
    var apiCupon = <?= json_encode($apiCupon) ?>;
    var anticipoTipo = <?= json_encode($anticipoTipo) ?>;
    var T = <?= json_encode($LJS, JSON_UNESCAPED_UNICODE) ?>;

    var seleccion = { entrada: null, salida: null, personas: 2, noches: 0, tipo: null, cupon: null };

    function fmt(n) {
        return simbolo + Number(n).toLocaleString(T.locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fmtFecha(iso) {
        var d = new Date(iso + 'T12:00:00');
        return d.toLocaleDateString(T.locale, { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function irAPaso(n) {
        document.querySelectorAll('.mr-panel').forEach(function (p) {
            p.classList.toggle('activo', p.getAttribute('data-panel') === String(n));
        });
        document.querySelectorAll('[data-step-chip]').forEach(function (chip) {
            var num = parseInt(chip.getAttribute('data-step-chip'), 10);
            chip.classList.toggle('activo', num === n);
            chip.classList.toggle('hecho', num < n);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.querySelectorAll('[data-volver]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            irAPaso(parseInt(btn.getAttribute('data-volver'), 10));
        });
    });

    // ── Paso 1 → 2: buscar disponibilidad ──
    document.getElementById('mr-form-fechas').addEventListener('submit', function (e) {
        e.preventDefault();
        var entrada = document.getElementById('mr-entrada').value;
        var salida = document.getElementById('mr-salida').value;
        var personas = parseInt(document.getElementById('mr-personas').value, 10) || 1;
        var estado1 = document.getElementById('mr-estado-1');

        if (!entrada || !salida) {
            estado1.innerHTML = '<span class="mr-error">' + T.selecciona_fechas + '</span>';
            return;
        }
        if (salida <= entrada) {
            estado1.innerHTML = '<span class="mr-error">' + T.salida_posterior + '</span>';
            return;
        }

        estado1.textContent = '';
        seleccion.entrada = entrada;
        seleccion.salida = salida;
        seleccion.personas = personas;
        irAPaso(2);
        buscarDisponibilidad();
    });

    function skeletons() {
        var html = '';
        for (var i = 0; i < 3; i++) {
            html += '<div class="mr-skel"><div class="ph-img"></div><div class="ph-lineas"><div></div><div></div></div></div>';
        }
        return html;
    }

    function buscarDisponibilidad() {
        var boton = document.getElementById('mr-buscar');
        var estado2 = document.getElementById('mr-estado-2');
        var resultados = document.getElementById('mr-resultados');

        boton.disabled = true;
        estado2.textContent = T.buscando;
        resultados.innerHTML = skeletons();

        var url = apiDisponibilidad +
            '?entrada=' + encodeURIComponent(seleccion.entrada) +
            '&salida=' + encodeURIComponent(seleccion.salida) +
            '&personas=' + encodeURIComponent(seleccion.personas);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                boton.disabled = false;
                resultados.innerHTML = '';

                if (!data.success) {
                    estado2.innerHTML = '<span class="mr-error">' + (data.message || T.error_disponibilidad) + '</span>';
                    return;
                }
                if (!data.tipos || !data.tipos.length) {
                    estado2.innerHTML = T.sin_disponibilidad;
                    return;
                }

                seleccion.noches = data.noches;
                estado2.textContent = fmtFecha(data.entrada) + ' → ' + fmtFecha(data.salida) + ' · ' + data.noches + ' ' + T.noches;

                data.tipos.forEach(function (t) {
                    var div = document.createElement('div');
                    div.className = 'mr-tipo';
                    var foto = t.foto_url
                        ? '<img src="' + t.foto_url + '" alt="Habitacion ' + t.tipo + '" loading="lazy">'
                        : '<div class="mr-foto-ph" aria-hidden="true">&#128716;</div>';
                    div.innerHTML = foto +
                        '<div><h3>' + t.tipo + '</h3>' +
                        '<p class="mr-meta">' + T.hasta + ' ' + t.capacidad_personas + ' ' + T.personas_disp + ' · ' + t.disponibles + ' ' + T.disponibles + '</p>' +
                        '<p class="mr-anticipo">' + T.apartala + ' ' + fmt(t.anticipo_requerido) + '</p></div>' +
                        '<div class="mr-cta-tipo"><div class="mr-precio"><strong>' + fmt(t.precio_total) + '</strong>' +
                        '<span>' + fmt(t.precio_por_noche) + ' ' + T.por_noche + '</span></div>' +
                        '<button type="button" class="mr-elegir">' + T.elegir + '</button></div>';
                    div.querySelector('.mr-elegir').addEventListener('click', function () {
                        elegirTipo(t);
                    });
                    resultados.appendChild(div);
                });
            })
            .catch(function () {
                boton.disabled = false;
                resultados.innerHTML = '';
                estado2.innerHTML = '<span class="mr-error">' + T.error_conexion + '</span>';
            });
    }

    // ── Paso 2 → 3: resumen ──
    // Mismo calculo que el servidor: el cupon descuenta el hospedaje, los
    // extras se suman despues (sin descuento), y el anticipo escala con el
    // total final salvo cuando es monto fijo o primera noche.
    function factorExtra(tipo) {
        var noches = Math.max(1, seleccion.noches || 1);
        var personas = Math.max(1, seleccion.personas || 1);
        if (tipo === 'por_noche') { return noches; }
        if (tipo === 'por_persona') { return personas; }
        if (tipo === 'por_persona_noche') { return noches * personas; }
        return 1;
    }

    function extrasSeleccionados() {
        var lista = [];
        document.querySelectorAll('.mr-extra-check:checked').forEach(function (chk) {
            lista.push(parseInt(chk.value, 10));
        });
        return lista;
    }

    function refrescarEtiquetasExtras() {
        document.querySelectorAll('.mr-extra-check').forEach(function (chk) {
            var importe = Number(chk.dataset.precio) * factorExtra(chk.dataset.tipo);
            var etiqueta = chk.closest('label').querySelector('.mr-extra-precio');
            if (etiqueta) { etiqueta.textContent = '+' + fmt(importe); }
        });
    }

    function refrescarResumen() {
        var t = seleccion.tipo;
        if (!t) { return; }

        var totalOriginal = Number(t.precio_total);
        var anticipoOriginal = Number(t.anticipo_requerido);
        var total = totalOriginal;
        var descuento = 0;
        var factorCupon = 1;

        if (seleccion.cupon) {
            descuento = seleccion.cupon.tipo === 'porcentaje'
                ? Math.round(total * seleccion.cupon.valor) / 100
                : Math.min(seleccion.cupon.valor, total);
            total = Math.round((total - descuento) * 100) / 100;
            factorCupon = totalOriginal > 0 ? total / totalOriginal : 1;
        }

        var totalExtras = 0;
        document.querySelectorAll('.mr-extra-check:checked').forEach(function (chk) {
            totalExtras += Math.round(Number(chk.dataset.precio) * factorExtra(chk.dataset.tipo) * 100) / 100;
        });
        var totalFinal = Math.round((total + totalExtras) * 100) / 100;

        var anticipo;
        if (anticipoTipo === 'monto_fijo') {
            anticipo = Math.min(anticipoOriginal, totalFinal);
        } else if (anticipoTipo === 'primera_noche') {
            anticipo = Math.round(anticipoOriginal * factorCupon * 100) / 100;
        } else {
            anticipo = Math.round(anticipoOriginal * (totalOriginal > 0 ? totalFinal / totalOriginal : 1) * 100) / 100;
        }

        var filaDesc = document.getElementById('mr-res-desc-fila');
        if (filaDesc) {
            filaDesc.style.display = descuento > 0 ? '' : 'none';
            if (descuento > 0) {
                document.getElementById('mr-res-desc-label').textContent = T.descuento + ' (' + seleccion.cupon.codigo + ')';
                document.getElementById('mr-res-desc').textContent = '-' + fmt(descuento);
            }
        }
        var filaExtras = document.getElementById('mr-res-extras-fila');
        if (filaExtras) {
            filaExtras.style.display = totalExtras > 0 ? '' : 'none';
            if (totalExtras > 0) {
                document.getElementById('mr-res-extras').textContent = '+' + fmt(totalExtras);
            }
        }
        document.getElementById('mr-res-total').textContent = fmt(totalFinal);
        document.getElementById('mr-res-anticipo').textContent = fmt(anticipo);
        document.getElementById('mr-res-saldo').textContent = fmt(Math.max(0, totalFinal - anticipo));
        refrescarEtiquetasExtras();
    }

    document.querySelectorAll('.mr-extra-check').forEach(function (chk) {
        chk.addEventListener('change', refrescarResumen);
    });

    function elegirTipo(t) {
        seleccion.tipo = t;
        document.getElementById('mr-res-fechas').textContent = fmtFecha(seleccion.entrada) + ' → ' + fmtFecha(seleccion.salida);
        document.getElementById('mr-res-noches').textContent = seleccion.noches + ' ' + T.noches;
        document.getElementById('mr-res-tipo').textContent = t.tipo.charAt(0).toUpperCase() + t.tipo.slice(1);
        document.getElementById('mr-res-personas').textContent = seleccion.personas + ' ' + T.personas_disp;
        refrescarResumen();
        document.getElementById('mr-estado-3').textContent = '';
        irAPaso(3);
    }

    // ── Cupon promocional (bloque promociones) ──
    var btnCupon = document.getElementById('mr-cupon-aplicar');
    if (btnCupon) {
        btnCupon.addEventListener('click', function () {
            var input = document.getElementById('mr-cupon-codigo');
            var estadoCupon = document.getElementById('mr-cupon-estado');
            var codigo = (input.value || '').trim().toUpperCase();

            if (seleccion.cupon) {
                seleccion.cupon = null;
                input.value = '';
                input.disabled = false;
                btnCupon.textContent = T.aplicar;
                estadoCupon.textContent = '';
                refrescarResumen();
                return;
            }

            if (!codigo) {
                estadoCupon.innerHTML = '<span class="mr-error">' + T.escribe_codigo + '</span>';
                return;
            }

            btnCupon.disabled = true;
            estadoCupon.textContent = T.validando_codigo;

            fetch(apiCupon + '?codigo=' + encodeURIComponent(codigo), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    btnCupon.disabled = false;
                    if (!data.success) {
                        estadoCupon.innerHTML = '<span class="mr-error">' + (data.message || T.codigo_invalido) + '</span>';
                        return;
                    }
                    seleccion.cupon = { codigo: data.codigo, tipo: data.tipo, valor: Number(data.valor) };
                    input.disabled = true;
                    btnCupon.textContent = T.quitar;
                    estadoCupon.innerHTML = '<span style="color:#15803D;font-weight:700;">✓ ' + data.codigo + ': ' + data.descripcion + '</span>';
                    refrescarResumen();
                })
                .catch(function () {
                    btnCupon.disabled = false;
                    estadoCupon.innerHTML = '<span class="mr-error">' + T.codigo_error + '</span>';
                });
        });
    }

    // ── Paso 3: validar datos e iniciar pago ──
    function marcarError(campo, mensaje) {
        var err = document.querySelector('[data-err="' + campo + '"]');
        if (err) { err.textContent = mensaje || ''; }
    }

    document.getElementById('mr-form-datos').addEventListener('submit', function (e) {
        e.preventDefault();
        var nombre = document.getElementById('mr-nombre').value.trim();
        var telefono = document.getElementById('mr-telefono').value.trim();
        var email = document.getElementById('mr-email').value.trim();
        var estado3 = document.getElementById('mr-estado-3');
        var boton = document.getElementById('mr-pagar');
        var valido = true;

        marcarError('nombre'); marcarError('telefono'); marcarError('email');

        if (nombre.length < 5 || nombre.indexOf(' ') === -1) {
            marcarError('nombre', T.err_nombre);
            valido = false;
        }
        if (telefono.replace(/\D/g, '').length < 10) {
            marcarError('telefono', T.err_telefono);
            valido = false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            marcarError('email', T.err_email);
            valido = false;
        }
        if (!valido || !seleccion.tipo) { return; }

        boton.disabled = true;
        estado3.textContent = T.preparando_pago;

        fetch(apiIniciarPago, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                entrada: seleccion.entrada,
                salida: seleccion.salida,
                personas: seleccion.personas,
                tipo: seleccion.tipo.tipo,
                nombre: nombre,
                telefono: telefono,
                email: email,
                cupon: seleccion.cupon ? seleccion.cupon.codigo : '',
                extras: extrasSeleccionados()
            })
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
                if (res.ok && res.data.success && res.data.checkout_url) {
                    estado3.textContent = T.redirigiendo;
                    window.location.href = res.data.checkout_url;
                    return;
                }
                boton.disabled = false;
                estado3.innerHTML = '<span class="mr-error">' + (res.data.message || T.pagos_no_disponibles) + '</span>';
            })
            .catch(function () {
                boton.disabled = false;
                estado3.innerHTML = '<span class="mr-error">' + T.pagos_no_disponibles + '</span>';
            });
    });
})();
</script>
</body>
</html>
