<?php
/**
 * Pagina publica de reservas (motor_reservas) - version funcional Fase 2.
 * Standalone (sin layout interno). Identidad del hotel via tokens --brand-*.
 * Fase 3 ampliara esta vista al flujo completo de 3 pasos con pago.
 */
$hotel = $hotel ?? [];
$branding = $branding ?? [];
$politica = $politica ?? '';
$minNoches = (int) ($minNoches ?? 1);
$maxNoches = (int) ($maxNoches ?? 30);
$anticipacionMaxDias = (int) ($anticipacionMaxDias ?? 180);

$nombreHotel = htmlspecialchars((string) ($branding['nombre_visual'] ?? $hotel['nombre'] ?? 'Hotel'), ENT_QUOTES, 'UTF-8');
$slugHotel = htmlspecialchars((string) ($hotel['slug'] ?? ''), ENT_QUOTES, 'UTF-8');
$monedaSimbolo = htmlspecialchars((string) ($hotel['moneda_simbolo'] ?? '$'), ENT_QUOTES, 'UTF-8');
$colorPrimario = htmlspecialchars((string) ($branding['color_primary'] ?? '#1B2746'), ENT_QUOTES, 'UTF-8');
$colorSecundario = htmlspecialchars((string) ($branding['color_secondary'] ?? '#0F172A'), ENT_QUOTES, 'UTF-8');
$colorAcento = htmlspecialchars((string) ($branding['color_accent'] ?? '#BD9441'), ENT_QUOTES, 'UTF-8');
$logoUrl = function_exists('hotel_branding_asset_url') ? hotel_branding_asset_url($branding['logo_url'] ?? null) : null;
$fechaMin = date('Y-m-d');
$fechaMax = date('Y-m-d', strtotime('+' . max(1, $anticipacionMaxDias) . ' days'));
$apiDisponibilidad = url('h/' . ($hotel['slug'] ?? '') . '/reservar/api/disponibilidad');
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservar - <?= $nombreHotel ?></title>
    <style>
    :root {
        --brand-primary: <?= $colorPrimario ?>;
        --brand-secondary: <?= $colorSecundario ?>;
        --brand-accent: <?= $colorAcento ?>;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: "Inter", "Segoe UI", system-ui, sans-serif;
        background: color-mix(in srgb, var(--brand-accent) 6%, #F7F5F0);
        color: #2A3242;
    }
    .mr-shell { max-width: 760px; margin: 0 auto; padding: 20px 16px 48px; }
    .mr-header { text-align: center; padding: 20px 0 8px; }
    .mr-header img { height: 56px; width: 56px; object-fit: contain; border-radius: 50%; background: #fff; padding: 6px; }
    .mr-header h1 { margin: 10px 0 4px; font-size: 1.4rem; color: var(--brand-primary); }
    .mr-header p { margin: 0; color: #6B7486; font-size: .9rem; }
    .mr-card {
        margin-top: 18px; background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary) 12%, #E6E2D8);
        border-radius: 14px; padding: 18px; box-shadow: 0 14px 32px -28px rgba(20,28,45,.5);
    }
    .mr-form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .mr-field { display: grid; gap: 5px; }
    .mr-field.full { grid-column: 1 / -1; }
    .mr-field label { font-size: .78rem; font-weight: 600; color: #55607A; }
    .mr-field input, .mr-field select {
        min-height: 44px; border: 1px solid #D8D4C9; border-radius: 10px; padding: 0 12px; font-size: 1rem; width: 100%;
    }
    .mr-btn {
        grid-column: 1 / -1; min-height: 48px; border: 0; border-radius: 10px; cursor: pointer;
        background: var(--brand-primary); color: #fff; font-size: 1rem; font-weight: 700;
    }
    .mr-btn:disabled { opacity: .6; cursor: wait; }
    .mr-estado { margin-top: 14px; text-align: center; color: #6B7486; font-size: .92rem; }
    .mr-error { color: #B3382F; }
    .mr-tipo {
        display: grid; grid-template-columns: 88px 1fr auto; gap: 12px; align-items: center;
        border: 1px solid #E8E4DA; border-radius: 12px; padding: 12px; margin-top: 12px; background: #fff;
    }
    .mr-tipo img, .mr-tipo .mr-foto-ph { width: 88px; height: 66px; object-fit: cover; border-radius: 8px; background: #EEEBE2; }
    .mr-foto-ph { display: grid; place-items: center; color: #B9B2A2; font-size: 1.4rem; }
    .mr-tipo h3 { margin: 0; font-size: 1rem; color: var(--brand-primary); }
    .mr-tipo .mr-meta { margin: 3px 0 0; font-size: .8rem; color: #6B7486; }
    .mr-precio { text-align: right; }
    .mr-precio strong { display: block; font-size: 1.05rem; color: var(--brand-secondary); }
    .mr-precio span { font-size: .75rem; color: #6B7486; }
    .mr-anticipo { margin-top: 4px; font-size: .75rem; font-weight: 700; color: var(--brand-accent); }
    .mr-politica2 { margin-top: 20px; font-size: .8rem; color: #778092; text-align: center; }
    @media (max-width: 560px) {
        .mr-form { grid-template-columns: 1fr; }
        .mr-tipo { grid-template-columns: 72px 1fr; }
        .mr-precio { grid-column: 1 / -1; text-align: left; }
    }
    </style>
</head>
<body>
<div class="mr-shell">
    <header class="mr-header">
        <?php if ($logoUrl): ?>
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $nombreHotel ?>">
        <?php endif; ?>
        <h1><?= $nombreHotel ?></h1>
        <p>Reserva en linea con anticipo seguro</p>
    </header>

    <section class="mr-card">
        <form id="mr-form" class="mr-form" autocomplete="off">
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
            <button type="submit" class="mr-btn" id="mr-buscar">Ver disponibilidad</button>
        </form>

        <div id="mr-estado" class="mr-estado" aria-live="polite"></div>
        <div id="mr-resultados"></div>
    </section>

    <?php if (trim((string) $politica) !== ''): ?>
        <p class="mr-politica2"><?= htmlspecialchars($politica, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>

<script>
(function () {
    var form = document.getElementById('mr-form');
    var boton = document.getElementById('mr-buscar');
    var estado = document.getElementById('mr-estado');
    var resultados = document.getElementById('mr-resultados');
    var simbolo = <?= json_encode($monedaSimbolo) ?>;

    function fmt(n) {
        return simbolo + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var entrada = document.getElementById('mr-entrada').value;
        var salida = document.getElementById('mr-salida').value;
        var personas = document.getElementById('mr-personas').value;

        if (!entrada || !salida) {
            estado.innerHTML = '<span class="mr-error">Selecciona tus fechas de llegada y salida.</span>';
            return;
        }

        boton.disabled = true;
        estado.textContent = 'Buscando disponibilidad...';
        resultados.innerHTML = '';

        var url = <?= json_encode($apiDisponibilidad) ?> +
            '?entrada=' + encodeURIComponent(entrada) +
            '&salida=' + encodeURIComponent(salida) +
            '&personas=' + encodeURIComponent(personas);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                boton.disabled = false;
                if (!data.success) {
                    estado.innerHTML = '<span class="mr-error">' + (data.message || 'No se pudo consultar la disponibilidad.') + '</span>';
                    return;
                }
                if (!data.tipos || !data.tipos.length) {
                    estado.textContent = 'No hay habitaciones disponibles para esas fechas. Prueba con otras fechas.';
                    return;
                }
                estado.textContent = data.noches + ' noche(s) · ' + data.tipos.length + ' tipo(s) de habitacion disponibles';
                data.tipos.forEach(function (t) {
                    var div = document.createElement('div');
                    div.className = 'mr-tipo';
                    var foto = t.foto_url
                        ? '<img src="' + t.foto_url + '" alt="' + t.tipo + '" loading="lazy">'
                        : '<div class="mr-foto-ph">🛏</div>';
                    div.innerHTML = foto +
                        '<div><h3>' + t.tipo + '</h3>' +
                        '<p class="mr-meta">Hasta ' + t.capacidad_personas + ' persona(s) · ' + t.disponibles + ' disponible(s)</p>' +
                        '<p class="mr-anticipo">Aparta hoy con ' + fmt(t.anticipo_requerido) + '</p></div>' +
                        '<div class="mr-precio"><strong>' + fmt(t.precio_total) + '</strong>' +
                        '<span>' + fmt(t.precio_por_noche) + ' / noche</span></div>';
                    resultados.appendChild(div);
                });
            })
            .catch(function () {
                boton.disabled = false;
                estado.innerHTML = '<span class="mr-error">Error de conexion. Intenta de nuevo.</span>';
            });
    });
})();
</script>
</body>
</html>
