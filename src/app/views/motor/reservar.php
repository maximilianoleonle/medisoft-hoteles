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
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <title>Reservar - <?= $nombreHotel ?></title>
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
        <p>Reserva en linea · confirma con un anticipo seguro</p>
    </header>

    <nav class="mr-steps" aria-label="Progreso de tu reservacion">
        <div class="mr-step activo" data-step-chip="1"><span class="n">1</span><span class="txt">Fechas</span></div>
        <div class="mr-step" data-step-chip="2"><span class="n">2</span><span class="txt">Habitacion</span></div>
        <div class="mr-step" data-step-chip="3"><span class="n">3</span><span class="txt">Tus datos</span></div>
    </nav>

    <div class="mr-card">
        <!-- PASO 1: fechas -->
        <section class="mr-panel activo" data-panel="1" aria-label="Paso 1: elige tus fechas">
            <h2 class="mr-titulo-paso">¿Cuando te gustaria hospedarte?</h2>
            <form id="mr-form-fechas" class="mr-form" autocomplete="off">
                <div class="mr-field">
                    <label for="mr-entrada">Llegada</label>
                    <input type="date" id="mr-entrada" min="<?= $fechaMin ?>" max="<?= $fechaMax ?>" required>
                </div>
                <div class="mr-field">
                    <label for="mr-salida">Salida</label>
                    <input type="date" id="mr-salida" min="<?= $fechaMin ?>" max="<?= $fechaMax ?>" required>
                </div>
                <div class="mr-field full">
                    <label for="mr-personas">Personas</label>
                    <select id="mr-personas">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>><?= $i ?> persona<?= $i > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="mr-btn full" id="mr-buscar">Ver disponibilidad</button>
            </form>
            <div id="mr-estado-1" class="mr-estado" aria-live="polite"></div>
        </section>

        <!-- PASO 2: habitaciones -->
        <section class="mr-panel" data-panel="2" aria-label="Paso 2: elige tu habitacion">
            <button type="button" class="mr-volver" data-volver="1">&larr; Cambiar fechas</button>
            <h2 class="mr-titulo-paso">Elige tu habitacion</h2>
            <div id="mr-estado-2" class="mr-estado" aria-live="polite"></div>
            <div id="mr-resultados"></div>
        </section>

        <!-- PASO 3: datos y resumen -->
        <section class="mr-panel" data-panel="3" aria-label="Paso 3: tus datos y confirmacion">
            <button type="button" class="mr-volver" data-volver="2">&larr; Elegir otra habitacion</button>
            <h2 class="mr-titulo-paso">Confirma tu reservacion</h2>

            <div class="mr-resumen">
                <div class="mr-resumen-head" id="mr-res-titulo">Resumen de tu estancia</div>
                <dl>
                    <div class="fila"><dt>Fechas</dt><dd id="mr-res-fechas">—</dd></div>
                    <div class="fila"><dt>Noches</dt><dd id="mr-res-noches">—</dd></div>
                    <div class="fila"><dt>Habitacion</dt><dd id="mr-res-tipo">—</dd></div>
                    <div class="fila"><dt>Personas</dt><dd id="mr-res-personas">—</dd></div>
                    <div class="fila total"><dt>Total de la estancia</dt><dd id="mr-res-total">—</dd></div>
                    <div class="fila anticipo"><dt>Pagas hoy (anticipo)</dt><dd id="mr-res-anticipo">—</dd></div>
                    <div class="fila"><dt>Pagas al llegar</dt><dd id="mr-res-saldo">—</dd></div>
                </dl>
            </div>

            <form id="mr-form-datos" class="mr-form" autocomplete="on">
                <div class="mr-field full">
                    <label for="mr-nombre">Nombre completo</label>
                    <input type="text" id="mr-nombre" name="nombre" maxlength="150" required autocomplete="name" placeholder="Como aparece en tu identificacion">
                    <span class="err" data-err="nombre"></span>
                </div>
                <div class="mr-field">
                    <label for="mr-telefono">Telefono (WhatsApp)</label>
                    <input type="tel" id="mr-telefono" name="telefono" maxlength="20" required inputmode="tel" autocomplete="tel" placeholder="10 digitos">
                    <span class="err" data-err="telefono"></span>
                </div>
                <div class="mr-field">
                    <label for="mr-email">Correo electronico</label>
                    <input type="email" id="mr-email" name="email" maxlength="120" required autocomplete="email" placeholder="Para enviarte tu confirmacion">
                    <span class="err" data-err="email"></span>
                </div>
                <button type="submit" class="mr-btn full" id="mr-pagar">Continuar al pago seguro</button>
            </form>
            <div id="mr-estado-3" class="mr-estado" aria-live="polite"></div>
        </section>
    </div>

    <?php if (trim((string) $politica) !== ''): ?>
        <p class="mr-politica"><?= htmlspecialchars($politica, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <p class="mr-footer">Reservas en linea de <?= $nombreHotel ?> · Impulsado por Medisoft Hoteles</p>
</div>

<script>
(function () {
    var simbolo = <?= json_encode($monedaSimbolo) ?>;
    var apiDisponibilidad = <?= json_encode($apiDisponibilidad) ?>;
    var apiIniciarPago = <?= json_encode($apiIniciarPago) ?>;

    var seleccion = { entrada: null, salida: null, personas: 2, noches: 0, tipo: null };

    function fmt(n) {
        return simbolo + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fmtFecha(iso) {
        var d = new Date(iso + 'T12:00:00');
        return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
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
            estado1.innerHTML = '<span class="mr-error">Selecciona tus fechas de llegada y salida.</span>';
            return;
        }
        if (salida <= entrada) {
            estado1.innerHTML = '<span class="mr-error">La fecha de salida debe ser posterior a la de llegada.</span>';
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
        estado2.textContent = 'Buscando habitaciones disponibles...';
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
                    estado2.innerHTML = '<span class="mr-error">' + (data.message || 'No se pudo consultar la disponibilidad.') + '</span>';
                    return;
                }
                if (!data.tipos || !data.tipos.length) {
                    estado2.innerHTML = 'No hay habitaciones disponibles para esas fechas.<br>Prueba con otras fechas.';
                    return;
                }

                seleccion.noches = data.noches;
                estado2.textContent = fmtFecha(data.entrada) + ' → ' + fmtFecha(data.salida) + ' · ' + data.noches + ' noche(s)';

                data.tipos.forEach(function (t) {
                    var div = document.createElement('div');
                    div.className = 'mr-tipo';
                    var foto = t.foto_url
                        ? '<img src="' + t.foto_url + '" alt="Habitacion ' + t.tipo + '" loading="lazy">'
                        : '<div class="mr-foto-ph" aria-hidden="true">&#128716;</div>';
                    div.innerHTML = foto +
                        '<div><h3>' + t.tipo + '</h3>' +
                        '<p class="mr-meta">Hasta ' + t.capacidad_personas + ' persona(s) · ' + t.disponibles + ' disponible(s)</p>' +
                        '<p class="mr-anticipo">Apartala hoy con ' + fmt(t.anticipo_requerido) + '</p></div>' +
                        '<div class="mr-cta-tipo"><div class="mr-precio"><strong>' + fmt(t.precio_total) + '</strong>' +
                        '<span>' + fmt(t.precio_por_noche) + ' / noche</span></div>' +
                        '<button type="button" class="mr-elegir">Elegir</button></div>';
                    div.querySelector('.mr-elegir').addEventListener('click', function () {
                        elegirTipo(t);
                    });
                    resultados.appendChild(div);
                });
            })
            .catch(function () {
                boton.disabled = false;
                resultados.innerHTML = '';
                estado2.innerHTML = '<span class="mr-error">Error de conexion. Revisa tu internet e intenta de nuevo.</span>';
            });
    }

    // ── Paso 2 → 3: resumen ──
    function elegirTipo(t) {
        seleccion.tipo = t;
        document.getElementById('mr-res-fechas').textContent = fmtFecha(seleccion.entrada) + ' → ' + fmtFecha(seleccion.salida);
        document.getElementById('mr-res-noches').textContent = seleccion.noches + ' noche(s)';
        document.getElementById('mr-res-tipo').textContent = t.tipo.charAt(0).toUpperCase() + t.tipo.slice(1);
        document.getElementById('mr-res-personas').textContent = seleccion.personas + ' persona(s)';
        document.getElementById('mr-res-total').textContent = fmt(t.precio_total);
        document.getElementById('mr-res-anticipo').textContent = fmt(t.anticipo_requerido);
        document.getElementById('mr-res-saldo').textContent = fmt(Math.max(0, t.precio_total - t.anticipo_requerido));
        document.getElementById('mr-estado-3').textContent = '';
        irAPaso(3);
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
            marcarError('nombre', 'Escribe tu nombre y apellido.');
            valido = false;
        }
        if (telefono.replace(/\D/g, '').length < 10) {
            marcarError('telefono', 'Escribe un telefono de al menos 10 digitos.');
            valido = false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            marcarError('email', 'Escribe un correo valido.');
            valido = false;
        }
        if (!valido || !seleccion.tipo) { return; }

        boton.disabled = true;
        estado3.textContent = 'Preparando tu pago seguro...';

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
                email: email
            })
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
                if (res.ok && res.data.success && res.data.checkout_url) {
                    estado3.textContent = 'Redirigiendo al pago seguro...';
                    window.location.href = res.data.checkout_url;
                    return;
                }
                boton.disabled = false;
                estado3.innerHTML = '<span class="mr-error">' + (res.data.message || 'Los pagos en linea de este hotel aun no estan disponibles. Contacta al hotel para completar tu reservacion.') + '</span>';
            })
            .catch(function () {
                boton.disabled = false;
                estado3.innerHTML = '<span class="mr-error">Los pagos en linea de este hotel aun no estan disponibles. Contacta al hotel para completar tu reservacion.</span>';
            });
    });
})();
</script>
</body>
</html>
