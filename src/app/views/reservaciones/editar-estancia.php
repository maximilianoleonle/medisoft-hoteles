<?php
/**
 * Vista: Modificar Estancia de una Reservación
 * Reemplaza el modal "Modificar días". Permite mover check-out y —en reservaciones
 * confirmadas— también el check-in, con verificación de disponibilidad y precio en vivo.
 * Estilo alineado con "Modificar Habitaciones" (editar-habitaciones).
 */

if (!isset($reservacion)) {
    set_mensaje('Error: Datos incompletos', 'error');
    redirect('reservaciones');
    exit;
}

$es_checked_in   = ($reservacion['estado'] === 'checked_in');
$huesped_nombre  = $reservacion['huesped_nombre'] ?? 'Huésped';
$precio_actual   = floatval($reservacion['precio_total']);
$habitaciones    = $reservacion['habitaciones'] ?? [];
?>

<style>
.ee-page {
    --ee-primary: var(--brand-primary, #1B2746);
    --ee-accent: var(--brand-accent, #BD9441);
    --ee-accent-soft: color-mix(in srgb, var(--ee-accent) 13%, #fffaf0);
    --ee-ink: #172033;
    --ee-muted: #69738a;
    --ee-line: rgba(27, 39, 70, .12);
    --ee-surface: rgba(255, 253, 248, .94);
    --ee-success: #148653;
    --ee-success-soft: #e8f7ef;
    --ee-danger: #c2413d;
    --ee-danger-soft: #fff0ef;
    --ee-warning: #c47a1d;
    --ee-warning-soft: #fff5de;
    --ee-info: #2563EB;
    background:
        radial-gradient(circle at 8% 6%, color-mix(in srgb, var(--ee-accent) 16%, transparent) 0 260px, transparent 261px),
        linear-gradient(180deg, #fbf8f0 0%, #f4efe5 46%, #f8f5ee 100%);
    min-height: 100vh;
    padding: 28px 0 42px;
    font-family: 'Manrope', system-ui, sans-serif;
}
.ee-wrap { width: calc(100% - 48px); max-width: 1800px; margin: 0 auto; }

/* Header */
.ee-header {
    position: relative; overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--ee-accent) 26%, transparent);
    border-radius: 24px;
    background:
        linear-gradient(135deg, rgba(255, 253, 248, .96), rgba(255, 248, 230, .9)),
        radial-gradient(circle at right top, color-mix(in srgb, var(--ee-accent) 22%, transparent), transparent 36%);
    box-shadow: 0 22px 54px rgba(27, 39, 70, .12);
    margin-bottom: 18px; padding: 22px;
    display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap;
}
.ee-header::after {
    content: ""; position: absolute; right: 22px; bottom: 0;
    width: 210px; height: 3px; border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, transparent, var(--ee-accent), var(--ee-primary)); opacity: .7;
}
.ee-header-tt h2 { color: var(--ee-primary); font-size: clamp(1.4rem, 2.2vw, 1.9rem); margin: 0 0 6px; font-weight: 600; }
.ee-header-tt p { color: var(--ee-muted); font-weight: 500; margin: 0; }
.ee-header-tt p b { color: var(--ee-ink); font-weight: 600; }

/* Layout */
.ee-grid { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 18px; align-items: start; }

.ee-card {
    border: 1px solid var(--ee-line); border-radius: 22px;
    background: var(--ee-surface); box-shadow: 0 18px 48px rgba(27, 39, 70, .1); overflow: hidden;
}
.ee-card-head {
    border-bottom: 1px solid var(--ee-line);
    background: linear-gradient(180deg, #fffdf8, #fbf7ef);
    color: var(--ee-primary); padding: 16px 20px; font-weight: 600; font-size: 1rem;
    display: flex; align-items: center; gap: 9px;
}
.ee-card-body { padding: 20px; }

/* Date pickers */
.ee-dates { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 520px) { .ee-dates { grid-template-columns: 1fr; } }
.ee-field { display: flex; flex-direction: column; gap: 7px; }
.ee-field-label {
    font-size: .74rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--ee-muted);
    display: flex; align-items: center; gap: 7px;
}
.ee-field-label i { color: var(--ee-accent); }
.ee-input {
    min-height: 52px; border: 1px solid var(--ee-line); border-radius: 14px;
    background: rgba(255, 255, 255, .92); color: var(--ee-ink);
    font-family: inherit; font-size: 1rem; font-weight: 500; padding: 0 14px;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .72); transition: border-color .2s, box-shadow .2s;
}
.ee-input:focus {
    outline: none; border-color: color-mix(in srgb, var(--ee-accent) 68%, #ffffff); background: #fff;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--ee-accent) 18%, transparent);
}
.ee-input:disabled { background: #f3f0ea; color: var(--ee-muted); cursor: not-allowed; }
.ee-field-hint { font-size: .74rem; font-weight: 500; color: var(--ee-muted); }
.ee-locked-note {
    margin-top: 12px; display: flex; align-items: center; gap: 9px;
    padding: 10px 13px; border-radius: 12px; font-size: .8rem; font-weight: 500;
    background: var(--ee-warning-soft); border: 1px solid color-mix(in srgb, var(--ee-warning) 24%, transparent); color: #7c4a0d;
}

/* Timeline */
.ee-timeline-wrap { margin-top: 22px; }
.ee-timeline-title { font-size: .74rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--ee-muted); margin-bottom: 10px; }
.ee-track { display: flex; gap: 6px; flex-wrap: wrap; }
.ee-cell {
    min-width: 46px; flex: 0 0 auto; height: 56px; border-radius: 11px;
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
    font-size: .72rem; font-weight: 500;
}
.ee-cell i { font-size: .82rem; }
.ee-cell.on { background: var(--ee-primary); color: #fff; }
.ee-cell.on.first { background: linear-gradient(135deg, var(--ee-primary), color-mix(in srgb, var(--ee-accent) 60%, var(--ee-primary))); }
.ee-cell-day { font-size: .64rem; opacity: .9; }
.ee-cell-mon { font-size: .58rem; opacity: .72; text-transform: uppercase; }

/* Limit messages */
.ee-msg {
    display: none; align-items: center; gap: 9px; margin-top: 16px;
    padding: 11px 14px; border-radius: 12px; font-size: .82rem; font-weight: 500;
}
.ee-msg.show { display: flex; }
.ee-msg b { font-weight: 600; }
.ee-msg span { line-height: 1.45; }

/* Tarjeta "reserva próxima" */
.ee-next { display: none; position: relative; overflow: hidden; margin-top: 16px;
    border: 1px solid color-mix(in srgb, var(--ee-info) 20%, transparent); border-radius: 16px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--ee-info) 9%, #fff) 0%, #ffffff 60%);
    box-shadow: 0 12px 30px rgba(37, 99, 235, .10); }
.ee-next.show { display: flex; gap: 14px; padding: 15px 16px 15px 19px; }
.ee-next::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
    background: linear-gradient(180deg, var(--ee-info), color-mix(in srgb, var(--ee-info) 45%, #fff)); }
.ee-next-ico { flex: 0 0 auto; width: 44px; height: 44px; border-radius: 13px; display: grid; place-items: center;
    background: linear-gradient(135deg, color-mix(in srgb, var(--ee-info) 18%, #fff), color-mix(in srgb, var(--ee-info) 8%, #fff));
    color: var(--ee-info); font-size: 1.1rem;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--ee-info) 16%, transparent); }
.ee-next-body { flex: 1; min-width: 0; }
.ee-next-head { display: flex; align-items: center; gap: 8px; }
.ee-next-title { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--ee-info); }
.ee-next-id { font-size: .68rem; font-weight: 600; color: var(--ee-info);
    background: color-mix(in srgb, var(--ee-info) 12%, #fff); border-radius: 999px; padding: 2px 9px; }
.ee-next-guest { font-size: 1.02rem; font-weight: 600; color: var(--ee-ink); margin: 4px 0 9px; line-height: 1.2; }
.ee-next-chips { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 10px; }
.ee-chip { display: inline-flex; align-items: center; gap: 6px; font-size: .76rem; font-weight: 500; color: var(--ee-ink);
    background: #fff; border: 1px solid var(--ee-line); border-radius: 999px; padding: 5px 11px; }
.ee-chip i { color: var(--ee-info); font-size: .72rem; }
.ee-chip.ghost { color: var(--ee-muted); background: color-mix(in srgb, var(--ee-info) 6%, #fff);
    border-color: color-mix(in srgb, var(--ee-info) 16%, transparent); }
.ee-next-foot { font-size: .8rem; font-weight: 500; color: var(--ee-muted); display: flex; align-items: center; gap: 8px; line-height: 1.4; }
.ee-next-foot i { color: var(--ee-info); }
.ee-next-foot b { font-weight: 600; color: var(--ee-ink); }
@media (max-width: 520px) { .ee-next-ico { display: none; } }
.ee-msg.info { background: color-mix(in srgb, var(--ee-info) 8%, #fff); border: 1px solid color-mix(in srgb, var(--ee-info) 26%, transparent); color: #1e40af; }
.ee-msg.warn { background: var(--ee-warning-soft); border: 1px solid color-mix(in srgb, var(--ee-warning) 24%, transparent); color: #7c4a0d; }
.ee-msg.error { background: var(--ee-danger-soft); border: 1px solid color-mix(in srgb, var(--ee-danger) 30%, transparent); color: #8d3430; }

/* Summary */
.ee-summary { position: sticky; top: 18px; }
.ee-sum-section {
    border: 1px solid var(--ee-line); border-radius: 16px;
    background: linear-gradient(180deg, rgba(255,255,255,.82), rgba(251,247,239,.82));
    padding: 14px; margin-bottom: 12px;
}
.ee-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 7px 0; border-bottom: 1px solid rgba(27, 39, 70, .08); font-size: .86rem; color: var(--ee-muted); font-weight: 500; }
.ee-row:last-child { border-bottom: none; }
.ee-row b { color: var(--ee-ink); font-weight: 600; }
.ee-diff-box { border-radius: 16px; padding: 16px; background: #f8fafc; border: 1px solid var(--ee-line); margin-bottom: 14px; }
.ee-diff-row { display: flex; justify-content: space-between; align-items: center; }
.ee-diff-label { font-size: .82rem; font-weight: 500; color: var(--ee-muted); }
.ee-diff-val { font-size: 1.35rem; font-weight: 600; color: var(--ee-ink); }

.ee-btn {
    width: 100%; min-height: 50px; border: none; border-radius: 14px;
    font-family: inherit; font-size: .9rem; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: transform .2s, box-shadow .2s, background .2s; margin-bottom: 10px;
}
.ee-btn:hover:not(:disabled) { transform: translateY(-1px); }
.ee-btn-primary { background: linear-gradient(135deg, var(--ee-primary), color-mix(in srgb, var(--ee-primary) 78%, #000)); color: #fff; box-shadow: 0 14px 26px rgba(27, 39, 70, .22); }
.ee-btn-primary.charge { background: var(--ee-success); box-shadow: 0 14px 26px rgba(20, 134, 83, .24); }
.ee-btn-primary.refund { background: var(--ee-info); box-shadow: 0 14px 26px rgba(37, 99, 235, .24); }
.ee-btn-primary:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; }
.ee-btn-ghost { background: #fff; color: var(--ee-primary); border: 1px solid var(--ee-line); text-decoration: none; }
.ee-btn-ghost:hover { background: #f7f1e6; }

@media (max-width: 980px) {
    .ee-grid { grid-template-columns: 1fr; }
    .ee-summary { position: relative; top: auto; }
}
</style>

<div class="ee-page">
    <div class="ee-wrap">
        <div class="ee-header">
            <div class="ee-header-tt">
                <h2><i class="far fa-calendar-alt" style="margin-right:10px;"></i>Modificar Estancia</h2>
                <p>
                    Reservación #<?= (int)$reservacion['id'] ?> &middot;
                    <b><?= htmlspecialchars($huesped_nombre, ENT_QUOTES, 'UTF-8') ?></b>
                    <?php if (!empty($habitaciones)): ?>
                        &middot; <?= count($habitaciones) ?> habitación<?= count($habitaciones) > 1 ? 'es' : '' ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php $back_arrow_href = back_url('reservaciones/ver/' . $reservacion['id']); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('reservaciones/ver/' . $reservacion['id']) ?>" class="ee-btn-ghost ms-back-legacy" style="width:auto; padding:12px 18px; border-radius:14px; min-height:auto; display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="ee-grid">
            <!-- Columna principal -->
            <div class="ee-card">
                <div class="ee-card-head"><i class="fas fa-calendar-week"></i> Fechas de la estancia</div>
                <div class="ee-card-body">
                    <div class="ee-dates">
                        <div class="ee-field">
                            <label class="ee-field-label" for="ee-entrada"><i class="fas fa-plane-arrival"></i> Llegada (check-in)</label>
                            <input type="date" id="ee-entrada" class="ee-input"
                                   value="<?= htmlspecialchars($reservacion['fecha_entrada'], ENT_QUOTES, 'UTF-8') ?>"
                                   <?= $es_checked_in ? 'disabled' : '' ?>>
                            <span class="ee-field-hint" id="ee-entrada-hint"></span>
                        </div>
                        <div class="ee-field">
                            <label class="ee-field-label" for="ee-salida"><i class="fas fa-plane-departure"></i> Salida (check-out)</label>
                            <input type="date" id="ee-salida" class="ee-input"
                                   value="<?= htmlspecialchars($reservacion['fecha_salida'], ENT_QUOTES, 'UTF-8') ?>">
                            <span class="ee-field-hint" id="ee-salida-hint"></span>
                        </div>
                    </div>

                    <?php if ($es_checked_in): ?>
                        <div class="ee-locked-note">
                            <i class="fas fa-lock"></i>
                            El huésped ya hizo check-in: la llegada queda fija. Puedes ajustar la salida (extender o recortar).
                        </div>
                    <?php endif; ?>

                    <div class="ee-timeline-wrap">
                        <div class="ee-timeline-title">Línea de noches</div>
                        <div class="ee-track" id="ee-track"></div>
                    </div>

                    <div class="ee-next" id="ee-limit"></div>
                    <div class="ee-msg" id="ee-status"></div>
                </div>
            </div>

            <!-- Columna resumen -->
            <div>
                <div class="ee-card ee-summary">
                    <div class="ee-card-head"><i class="fas fa-calculator"></i> Resumen del cambio</div>
                    <div class="ee-card-body">
                        <div class="ee-sum-section">
                            <div class="ee-row"><span>Check-in</span><b id="ee-r-checkin">–</b></div>
                            <div class="ee-row"><span>Check-out</span><b id="ee-r-checkout">–</b></div>
                            <div class="ee-row"><span>Noches</span><b id="ee-r-noches">–</b></div>
                        </div>

                        <div class="ee-sum-section">
                            <div class="ee-row"><span>Total actual</span><b id="ee-r-total-actual">–</b></div>
                            <div class="ee-row"><span>Total nuevo</span><b id="ee-r-total-nuevo">–</b></div>
                        </div>

                        <div class="ee-diff-box">
                            <div class="ee-diff-row">
                                <span class="ee-diff-label" id="ee-diff-label">Sin cambios</span>
                                <span class="ee-diff-val" id="ee-diff-val">$0.00</span>
                            </div>
                        </div>

                        <button type="button" id="ee-confirm" class="ee-btn ee-btn-primary" disabled>
                            <i class="fas fa-check"></i> Confirmar cambio
                        </button>
                        <a href="<?= back_url('reservaciones/ver/' . $reservacion['id']) ?>" class="ee-btn ee-btn-ghost">Cancelar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const EST = {
        reservacionId: <?= (int)$reservacion['id'] ?>,
        estado: '<?= $reservacion['estado'] ?>',
        entradaOriginal: '<?= $reservacion['fecha_entrada'] ?>',
        salidaOriginal: '<?= $reservacion['fecha_salida'] ?>',
        precioActual: <?= json_encode($precio_actual) ?>,
        nochesActuales: <?= (int)$noches ?>,
        minEntrada: null,
        maxCheckout: null,
        proxima: null,
        verifyTimer: null,
        verified: false,
        nuevoPrecio: <?= json_encode($precio_actual) ?>,
        urls: {
            tope: '<?= url('reservaciones/tope-modificar-dias') ?>',
            verificar: '<?= url('reservaciones/verificar-modificar-dias') ?>',
            guardar: '<?= url('reservaciones/modificar-dias') ?>',
            ver: '<?= url('reservaciones/ver/' . $reservacion['id']) ?>'
        }
    };
    EST.precioPorNoche = EST.nochesActuales > 0 ? EST.precioActual / EST.nochesActuales : EST.precioActual;

    const MESES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    const MESES_LARGO = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    const $ = (id) => document.getElementById(id);
    const inEntrada = $('ee-entrada');
    const inSalida  = $('ee-salida');

    function parseFecha(str) { return new Date(str + 'T12:00:00'); }
    function fmtISO(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function fmtCorto(str) { const d = parseFecha(str); return d.getDate() + ' ' + MESES[d.getMonth()]; }
    function fmtLargo(str) { const d = parseFecha(str); return d.getDate() + ' ' + MESES_LARGO[d.getMonth()] + ' ' + d.getFullYear(); }
    function money(n) { return '$' + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2 }); }
    function diffDias(a, b) { return Math.round((parseFecha(b) - parseFecha(a)) / 86400000); }
    function addDias(str, n) { const d = parseFecha(str); d.setDate(d.getDate() + n); return fmtISO(d); }

    function toast(tipo, msg) {
        if (window.msToast) { window.msToast(tipo, null, msg); }
    }

    function aplicarLimites() {
        // Salida: mínimo = entrada + 1 noche
        const entrada = inEntrada.value || EST.entradaOriginal;
        inSalida.min = addDias(entrada, 1);
        if (EST.maxCheckout) { inSalida.max = EST.maxCheckout; }

        // Entrada (solo confirmada): no antes de minEntrada, no después de salida - 1
        if (EST.estado === 'confirmada') {
            if (EST.minEntrada) { inEntrada.min = EST.minEntrada; }
            const salida = inSalida.value || EST.salidaOriginal;
            inEntrada.max = addDias(salida, -1);
        }
    }

    function pintarLimiteHints() {
        const limitEl = $('ee-limit');
        if (EST.proxima) {
            const p = EST.proxima;
            const fechaTope = fmtCorto(p.proxima_entrada);
            const huesped = p.proxima_huesped ? p.proxima_huesped : ('Reserva #' + p.proxima_reserva_id);

            let chips = '';
            if (p.proxima_habitacion) {
                chips += '<span class="ee-chip"><i class="fas fa-door-open"></i> Hab. ' + p.proxima_habitacion + '</span>';
            }
            chips += '<span class="ee-chip"><i class="fas fa-plane-arrival"></i> Llega ' + fechaTope + '</span>';
            if (p.proxima_entrada && p.proxima_salida) {
                chips += '<span class="ee-chip ghost">' + fmtCorto(p.proxima_entrada) + ' → ' + fmtCorto(p.proxima_salida) + '</span>';
            }

            limitEl.className = 'ee-next show';
            limitEl.innerHTML =
                '<div class="ee-next-ico"><i class="fas fa-calendar-day"></i></div>' +
                '<div class="ee-next-body">' +
                    '<div class="ee-next-head">' +
                        '<span class="ee-next-title">Reserva próxima</span>' +
                        '<span class="ee-next-id">#' + p.proxima_reserva_id + '</span>' +
                    '</div>' +
                    '<div class="ee-next-guest">' + huesped + '</div>' +
                    '<div class="ee-next-chips">' + chips + '</div>' +
                    '<div class="ee-next-foot"><i class="fas fa-right-from-bracket"></i>' +
                        '<span>Puedes extender la salida hasta el <b>' + fechaTope + '</b> como máximo.</span>' +
                    '</div>' +
                '</div>';
        } else {
            limitEl.className = 'ee-next';
            limitEl.innerHTML = '';
        }

        const hintEntrada = $('ee-entrada-hint');
        if (EST.estado === 'confirmada' && EST.minEntrada && EST.minEntrada !== EST.entradaOriginal) {
            hintEntrada.textContent = 'No antes del ' + fmtCorto(EST.minEntrada) + ' (reserva previa).';
        } else if (EST.estado === 'confirmada') {
            hintEntrada.textContent = 'Puedes adelantar o retrasar la llegada.';
        }
    }

    function renderTimeline() {
        const entrada = inEntrada.value || EST.entradaOriginal;
        const salida  = inSalida.value || EST.salidaOriginal;
        const noches  = Math.max(0, diffDias(entrada, salida));
        const track   = $('ee-track');
        const maxCeldas = Math.min(noches, 21);

        let html = '';
        for (let i = 0; i < maxCeldas; i++) {
            const iso = addDias(entrada, i);
            const d = parseFecha(iso);
            const icono = i === 0 ? 'fa-' + (EST.estado === 'checked_in' ? 'lock' : 'plane-arrival') : 'fa-moon';
            html += '<div class="ee-cell on' + (i === 0 ? ' first' : '') + '">' +
                        '<i class="fas ' + icono + '"></i>' +
                        '<span class="ee-cell-day">' + d.getDate() + '</span>' +
                        '<span class="ee-cell-mon">' + MESES[d.getMonth()] + '</span>' +
                    '</div>';
        }
        if (noches > maxCeldas) {
            html += '<div class="ee-cell on" style="opacity:.7;"><i class="fas fa-ellipsis-h"></i><span class="ee-cell-day">+' + (noches - maxCeldas) + '</span></div>';
        }
        track.innerHTML = html || '<span class="ee-field-hint">Selecciona fechas válidas.</span>';
        return noches;
    }

    function pintarDiferencia(totalNuevo, exacto) {
        const diff = totalNuevo - EST.precioActual;
        const lbl = $('ee-diff-label');
        const val = $('ee-diff-val');
        const btn = $('ee-confirm');
        const sinCambio = sinCambios();

        $('ee-r-total-nuevo').textContent = money(totalNuevo);

        if (Math.abs(diff) < 0.004) {
            lbl.textContent = sinCambio ? 'Sin cambios' : 'Mismo total, nuevas fechas';
            val.textContent = '$0.00'; val.style.color = '#172033';
            btn.className = 'ee-btn ee-btn-primary';
            btn.innerHTML = '<i class="fas fa-check"></i> ' + (sinCambio ? 'Confirmar cambio' : 'Guardar nuevas fechas');
            btn.disabled = sinCambio;
            return;
        }
        if (diff > 0) {
            lbl.textContent = exacto ? 'A cobrar al huésped' : 'A cobrar (estimado)';
            val.textContent = '+' + money(diff); val.style.color = '#148653';
            btn.className = 'ee-btn ee-btn-primary charge';
            btn.innerHTML = '<i class="fas fa-cash-register"></i> Cobrar ' + money(diff) + ' y guardar';
        } else {
            lbl.textContent = exacto ? 'A reembolsar' : 'A reembolsar (estimado)';
            val.textContent = '−' + money(-diff); val.style.color = '#2563EB';
            btn.className = 'ee-btn ee-btn-primary refund';
            btn.innerHTML = '<i class="fas fa-rotate-left"></i> Reembolsar y guardar';
        }
        btn.disabled = sinCambio;
    }

    function sinCambios() {
        const entrada = inEntrada.value || EST.entradaOriginal;
        const salida  = inSalida.value || EST.salidaOriginal;
        return entrada === EST.entradaOriginal && salida === EST.salidaOriginal;
    }

    function actualizarResumen() {
        const entrada = inEntrada.value || EST.entradaOriginal;
        const salida  = inSalida.value || EST.salidaOriginal;
        const noches  = renderTimeline();

        $('ee-r-checkin').textContent  = fmtLargo(entrada);
        $('ee-r-checkout').textContent = noches > 0 ? fmtLargo(salida) : '–';
        $('ee-r-noches').textContent   = noches + (noches === 1 ? ' noche' : ' noches');
        $('ee-r-total-actual').textContent = money(EST.precioActual);

        const status = $('ee-status');
        status.className = 'ee-msg'; status.innerHTML = '';

        if (noches < 1) {
            status.className = 'ee-msg error show';
            status.innerHTML = '<i class="fas fa-triangle-exclamation"></i><span>La salida debe ser posterior a la llegada.</span>';
            $('ee-confirm').disabled = true;
            $('ee-r-total-nuevo').textContent = '–';
            return;
        }

        // Estimación inmediata
        EST.verified = false;
        const estimado = noches * EST.precioPorNoche;
        pintarDiferencia(estimado, false);

        if (sinCambios()) { return; }

        // Cálculo exacto + disponibilidad (debounced)
        if (EST.verifyTimer) clearTimeout(EST.verifyTimer);
        EST.verifyTimer = setTimeout(verificarExacto, 320);
    }

    async function verificarExacto() {
        const entrada = inEntrada.value || EST.entradaOriginal;
        const salida  = inSalida.value || EST.salidaOriginal;
        try {
            const resp = await fetch(EST.urls.verificar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    reservacion_id: EST.reservacionId,
                    nueva_fecha_entrada: entrada,
                    nueva_fecha_salida: salida
                })
            });
            const data = await resp.json();
            // Ignorar si las fechas cambiaron mientras tanto
            if (entrada !== (inEntrada.value || EST.entradaOriginal) || salida !== (inSalida.value || EST.salidaOriginal)) return;

            const status = $('ee-status');
            if (!data.disponible) {
                status.className = 'ee-msg error show';
                status.innerHTML = '<i class="fas fa-ban"></i><span>' + (data.mensaje || 'Alguna habitación no está libre en esas fechas.') + '</span>';
                $('ee-confirm').disabled = true;
                EST.verified = false;
                return;
            }
            status.className = 'ee-msg info show';
            status.innerHTML = '<i class="fas fa-circle-check"></i><span>Disponibilidad confirmada para las nuevas fechas.</span>';
            EST.verified = true;
            EST.nuevoPrecio = data.nuevo_precio;
            pintarDiferencia(data.nuevo_precio, true);
        } catch (e) {
            /* deja la estimación local; el guardado revalida en el servidor */
        }
    }

    async function confirmar() {
        if (sinCambios()) { toast('warning', 'No hay cambios respecto a la estancia actual.'); return; }
        const entrada = inEntrada.value || EST.entradaOriginal;
        const salida  = inSalida.value || EST.salidaOriginal;
        if (diffDias(entrada, salida) < 1) { toast('error', 'La salida debe ser posterior a la llegada.'); return; }

        const btn = $('ee-confirm');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

        try {
            const resp = await fetch(EST.urls.guardar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    reservacion_id: EST.reservacionId,
                    nueva_fecha_entrada: entrada,
                    nueva_fecha_salida: salida
                })
            });
            const data = await resp.json();

            if (data.success) {
                if (data.requiere_cobro) {
                    const montoPendiente = Number(data.saldo_pendiente || data.diferencia || 0);
                    sessionStorage.setItem('rdv3PendingPayment:' + EST.reservacionId, JSON.stringify({
                        monto: montoPendiente,
                        diferencia: Number(data.diferencia || 0)
                    }));
                }
                toast('success', 'Estancia actualizada.');
                window.location.href = EST.urls.ver;
                return;
            }

            const status = $('ee-status');
            status.className = 'ee-msg error show';
            status.innerHTML = '<i class="fas fa-times"></i><span>' + (data.mensaje || 'Error al guardar el cambio.') + '</span>';
            btn.disabled = false;
            btn.innerHTML = original;
        } catch (e) {
            toast('error', 'Error de conexión.');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }

    async function cargarTope() {
        try {
            const resp = await fetch(EST.urls.tope, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ reservacion_id: EST.reservacionId })
            });
            const data = await resp.json();
            if (data && data.ok) {
                EST.maxCheckout = data.max_checkout || null;
                EST.minEntrada  = data.min_entrada || null;
                EST.proxima     = data.tiene_limite ? data : null;
                aplicarLimites();
                pintarLimiteHints();
            }
        } catch (e) { /* sin tope, el servidor revalida al guardar */ }
    }

    inEntrada.addEventListener('change', function () { aplicarLimites(); actualizarResumen(); });
    inSalida.addEventListener('change', function () { aplicarLimites(); actualizarResumen(); });
    $('ee-confirm').addEventListener('click', confirmar);

    aplicarLimites();
    actualizarResumen();
    cargarTope();
})();
</script>
