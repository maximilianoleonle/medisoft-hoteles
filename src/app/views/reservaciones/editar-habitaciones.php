<?php
/**
 * Vista de Editar Habitaciones de Reservación - Versión Limpia y Funcional
 * Vista hotelera
 */

// Validar que tenemos los datos necesarios
if (!isset($reservacion) || !isset($habitaciones) || !isset($habitaciones_seleccionadas)) {
    set_mensaje('Error: Datos incompletos', 'error');
    redirect('reservaciones');
    exit;
}

// Solo permitir edición en estado confirmada
if ($reservacion['estado'] !== 'confirmada') {
    set_mensaje('Solo se pueden modificar habitaciones en reservaciones confirmadas', 'warning');
    redirect('reservaciones/ver/' . $reservacion['id']);
    exit;
}

$habitaciones_ids = array_column($habitaciones_seleccionadas, 'habitacion_id');
$cortesias_actuales = array_column(array_filter($habitaciones_seleccionadas, function($h) {
    return $h['es_cortesia'] == 1;
}), 'habitacion_id');
?>

<style>
/* Variables CSS */
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
    --hotel-cream: #FFF8F3;
}

/* Estilos base limpios */
.page-container {
    background: #f8f9fa;
    min-height: 100vh;
    padding-top: 20px;
}

.main-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Header simple */
.page-header {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

/* Cards base */
.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.card-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    font-weight: 600;
}

.card-body {
    padding: 20px;
}

/* Grid de habitaciones */
.habitaciones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

/* Cards de habitación */
.habitacion-item {
    background: white;
    border: 3px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.habitacion-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Estado seleccionado */
.habitacion-item.seleccionada {
    border-color: #28a745 !important;
    background: #f0f9ff !important;
    transform: scale(1.02);
}

.habitacion-item.seleccionada::after {
    content: '✓';
    position: absolute;
    top: 5px;
    right: 5px;
    background: #28a745;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Estado ocupado */
.habitacion-item.ocupada {
    background: #fee;
    border-color: #dc3545;
    cursor: not-allowed;
    opacity: 0.7;
}

/* Estado cortesía */
.habitacion-item.es-cortesia {
    border-color: #ffc107 !important;
    background: #fffbf0 !important;
}

.badge-cortesia {
    display: inline-block;
    background: #ffc107;
    color: #000;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 8px;
}

/* Buscador */
.search-container {
    position: relative;
    margin-bottom: 20px;
}

.search-input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: var(--hotel-brown);
}

.search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

/* Botones */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: var(--hotel-brown);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: var(--hotel-brown-dark);
    transform: translateY(-1px);
}

.btn-primary:disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.65;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-warning {
    background: #ffc107;
    color: #000;
}

.btn-warning:hover {
    background: #e0a800;
}

/* Alertas */
.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-info {
    background: #e7f3ff;
    color: #0c5da5;
    border: 1px solid #b8daff;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

/* Resumen */
.resumen-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.resumen-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.resumen-row:last-child {
    border-bottom: none;
}

/* Lista de habitaciones en resumen */
.habitaciones-list {
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    background: white;
    border-radius: 6px;
    margin: 10px 0;
}

.habitacion-resumen-item {
    display: flex;
    justify-content: space-between;
    padding: 8px;
    border-bottom: 1px solid #f0f0f0;
}

.habitacion-resumen-item:last-child {
    border-bottom: none;
}

.habitacion-resumen-item.cortesia {
    background: #fffbf0;
    margin: 0 -8px;
    padding: 8px;
}

/* Info ocupación */
.info-ocupacion {
    background: white;
    border: 1px solid #dc3545;
    border-radius: 4px;
    padding: 8px;
    margin-top: 10px;
    font-size: 12px;
    color: #dc3545;
}

/* Responsive */

/* Tablets (hasta 1024px) */
@media (max-width: 1024px) {
    .main-container {
        padding: 0 15px;
    }

    .habitaciones-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }
}

/* Móviles (hasta 768px) */
@media (max-width: 768px) {
    .main-container {
        padding: 0 10px;
    }

    .page-header {
        padding: 15px;
    }

    .page-header h2 {
        font-size: 1.3rem;
    }

    .page-header > div {
        flex-direction: column;
        gap: 15px;
        align-items: stretch !important;
    }

    form > div {
        grid-template-columns: 1fr !important;
    }

    .card:last-child {
        position: static !important;
    }

    .habitaciones-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .habitacion-item {
        padding: 10px;
        font-size: 14px;
    }

    /* Modal responsive */
    .modal-content {
        width: 95%;
        margin: 20px auto;
    }

    .modal-body {
        padding: 15px;
        max-height: 70vh;
    }

    .modal-header {
        padding: 15px;
    }

    .modal-header h3 {
        font-size: 1.1rem;
    }

    .modal-footer {
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .modal-footer .btn {
        width: 100%;
        margin: 0 !important;
    }

    /* Botones más grandes para touch */
    .btn {
        padding: 12px 20px;
        font-size: 15px;
        min-height: 44px;
    }

    .search-input {
        padding: 10px 35px 10px 12px;
    }

    .card-body {
        padding: 15px;
    }

    .card-header {
        padding: 12px 15px;
    }

    /* Grid de cortesías en 1 columna */
    #gridCortesias {
        grid-template-columns: 1fr !important;
    }
}

/* Móviles pequeños (hasta 480px) */
@media (max-width: 480px) {
    .habitaciones-grid {
        grid-template-columns: 1fr;
    }

    .page-header h2 {
        font-size: 1.2rem;
    }
}

/* Landscape móvil */
@media (max-width: 768px) and (orientation: landscape) {
    .habitaciones-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .modal-content {
        max-height: 85vh;
    }
}

/* Modal simple */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    background: white;
    width: 90%;
    max-width: 600px;
    margin: 50px auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 25px rgba(0,0,0,0.2);
}

.modal-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    background: #f8f9fa;
    padding: 15px 20px;
    border-top: 1px solid #e9ecef;
    text-align: right;
}

.close-modal {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
}

/* === Rediseño operativo: editar habitaciones === */
:root {
    --er-primary: var(--brand-primary, #1B2746);
    --er-primary-soft: color-mix(in srgb, var(--er-primary) 10%, #ffffff);
    --er-accent: var(--brand-accent, #BD9441);
    --er-accent-soft: color-mix(in srgb, var(--er-accent) 13%, #fffaf0);
    --er-ink: #172033;
    --er-muted: #69738a;
    --er-line: rgba(27, 39, 70, .12);
    --er-surface: rgba(255, 253, 248, .94);
    --er-surface-strong: #fffdf8;
    --er-success: #148653;
    --er-success-soft: #e8f7ef;
    --er-danger: #c2413d;
    --er-danger-soft: #fff0ef;
    --er-warning: #c47a1d;
    --er-warning-soft: #fff5de;
}

.page-container {
    --er-primary: var(--brand-primary, #1B2746);
    --er-primary-soft: color-mix(in srgb, var(--er-primary) 10%, #ffffff);
    --er-accent: var(--brand-accent, #BD9441);
    --er-accent-soft: color-mix(in srgb, var(--er-accent) 13%, #fffaf0);
    --er-ink: #172033;
    --er-muted: #69738a;
    --er-line: rgba(27, 39, 70, .12);
    --er-surface: rgba(255, 253, 248, .94);
    --er-surface-strong: #fffdf8;
    --er-success: #148653;
    --er-success-soft: #e8f7ef;
    --er-danger: #c2413d;
    --er-danger-soft: #fff0ef;
    --er-warning: #c47a1d;
    --er-warning-soft: #fff5de;
    background:
        radial-gradient(circle at 8% 6%, color-mix(in srgb, var(--er-accent) 16%, transparent) 0 260px, transparent 261px),
        linear-gradient(180deg, #fbf8f0 0%, #f4efe5 46%, #f8f5ee 100%);
    min-height: 100vh;
    padding: 28px 0 42px;
}

.main-container {
    width: min(1480px, calc(100% - 32px));
    max-width: none;
    margin: 0 auto;
    padding: 0;
}

.page-header {
    position: relative;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--er-accent) 26%, transparent);
    border-radius: 24px;
    background:
        linear-gradient(135deg, rgba(255, 253, 248, .96), rgba(255, 248, 230, .9)),
        radial-gradient(circle at right top, color-mix(in srgb, var(--er-accent) 22%, transparent), transparent 36%);
    box-shadow: 0 22px 54px rgba(27, 39, 70, .12);
    margin-bottom: 18px;
    padding: 22px;
}

.page-header::after {
    content: "";
    position: absolute;
    right: 22px;
    bottom: 0;
    width: 210px;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, transparent, var(--er-accent), var(--er-primary));
    opacity: .7;
}

.page-header > div {
    position: relative;
    z-index: 1;
    gap: 18px;
}

.page-header h2 {
    color: var(--er-primary);
    font-size: clamp(1.65rem, 2.4vw, 2.35rem);
    letter-spacing: 0;
    margin-bottom: 6px;
}

.page-header p {
    color: var(--er-muted);
    font-weight: 600;
}

#formEditarHabitaciones > div {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) minmax(320px, .38fr) !important;
    gap: 18px !important;
    align-items: start;
}

.card {
    border: 1px solid var(--er-line);
    border-radius: 22px;
    background: var(--er-surface);
    box-shadow: 0 18px 48px rgba(27, 39, 70, .1);
    overflow: hidden;
}

.card:hover {
    box-shadow: 0 22px 56px rgba(27, 39, 70, .13);
}

.card-header {
    border-bottom: 1px solid var(--er-line);
    background: linear-gradient(180deg, #fffdf8, #fbf7ef);
    color: var(--er-primary);
    padding: 18px 20px;
}

.card-title {
    color: var(--er-primary);
    font-size: 1.1rem;
    letter-spacing: 0;
}

.card-body {
    padding: 18px;
}

.search-container {
    margin-bottom: 16px;
}

.search-input {
    min-height: 50px;
    border: 1px solid var(--er-line);
    border-radius: 16px;
    background: rgba(255, 255, 255, .88);
    color: var(--er-ink);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .72);
}

.search-input:focus {
    border-color: color-mix(in srgb, var(--er-accent) 68%, #ffffff);
    background: #ffffff;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--er-accent) 18%, transparent);
}

.habitaciones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(218px, 1fr));
    gap: 12px;
}

.habitacion-item {
    position: relative;
    min-height: 158px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12px;
    padding: 16px;
    border: 1px solid rgba(27, 39, 70, .13);
    border-radius: 18px;
    background: #ffffff;
    color: var(--er-ink);
    box-shadow: 0 10px 26px rgba(27, 39, 70, .08);
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, background .22s ease;
}

.habitacion-item::before {
    content: "";
    position: absolute;
    inset: 0 0 auto;
    height: 4px;
    background: linear-gradient(90deg, var(--er-primary), var(--er-accent));
    opacity: .22;
}

.habitacion-item:hover:not(.ocupada) {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--er-accent) 55%, rgba(27, 39, 70, .13));
    box-shadow: 0 16px 34px rgba(27, 39, 70, .13);
}

.habitacion-item.seleccionada {
    border-color: color-mix(in srgb, var(--er-success) 74%, #ffffff);
    background: linear-gradient(180deg, #f1fbf5 0%, var(--er-success-soft) 100%);
    box-shadow: 0 16px 36px rgba(20, 134, 83, .18);
}

.habitacion-item.seleccionada::before {
    background: linear-gradient(90deg, var(--er-success), #6fc58f);
    opacity: 1;
}

.habitacion-item.seleccionada::after {
    content: "\2713";
    position: absolute;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: var(--er-success);
    color: #ffffff;
    font-size: 15px;
    font-weight: 900;
    box-shadow: 0 8px 18px rgba(20, 134, 83, .28);
}

.habitacion-item.ocupada {
    border-color: color-mix(in srgb, var(--er-danger) 46%, #ffffff);
    background: linear-gradient(180deg, #fff7f6 0%, var(--er-danger-soft) 100%);
    opacity: 1;
    box-shadow: 0 10px 24px rgba(194, 65, 61, .12);
}

.habitacion-item.ocupada::before {
    background: linear-gradient(90deg, var(--er-danger), #f59f9a);
    opacity: 1;
}

.habitacion-item.es-cortesia {
    border-color: color-mix(in srgb, var(--er-accent) 50%, #ffffff);
    background: linear-gradient(180deg, #fffdf7 0%, var(--er-accent-soft) 100%);
}

.habitacion-item.es-cortesia::before {
    background: linear-gradient(90deg, var(--er-accent), #e5c46e);
    opacity: 1;
}

.habitacion-numero {
    color: var(--er-primary);
    font-size: 1.42rem;
    line-height: 1;
    padding-right: 34px;
}

.habitacion-tipo,
.habitacion-piso {
    color: var(--er-muted);
    font-weight: 700;
    font-size: .82rem;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.habitacion-precio {
    color: var(--er-primary);
    font-size: 1.02rem;
    margin-top: auto;
}

.selection-counter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 128px;
    padding: 7px 12px;
    border: 1px solid color-mix(in srgb, var(--er-primary) 14%, transparent);
    border-radius: 999px;
    background: #ffffff;
    color: var(--er-primary);
    font-size: .86rem;
    font-weight: 800;
}

.habitacion-precio-row {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 12px;
}

.habitacion-precio-row span {
    color: var(--er-muted);
    font-size: .74rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.habitacion-precio-row strong {
    color: var(--er-primary);
    font-size: 1rem;
}

.badge-cortesia {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    border: 1px solid color-mix(in srgb, var(--er-accent) 42%, transparent);
    border-radius: 999px;
    background: #fff8e6;
    color: #805213;
    box-shadow: none;
}

.info-ocupacion {
    border: 1px solid color-mix(in srgb, var(--er-danger) 22%, transparent);
    border-radius: 12px;
    background: rgba(255, 255, 255, .68);
    color: #8d3430;
}

.alert {
    border: 1px solid transparent;
    border-radius: 16px;
    padding: 14px 16px;
    box-shadow: 0 12px 26px rgba(27, 39, 70, .07);
}

.alert-warning {
    border-color: color-mix(in srgb, var(--er-warning) 24%, transparent);
    background: var(--er-warning-soft);
    color: #7c4a0d;
}

.alert-info {
    border-color: color-mix(in srgb, var(--er-primary) 18%, transparent);
    background: var(--er-primary-soft);
    color: var(--er-primary);
}

.btn {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 14px;
    font-weight: 800;
    letter-spacing: 0;
    transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-primary {
    background: linear-gradient(135deg, var(--er-primary), color-mix(in srgb, var(--er-primary) 78%, #000000));
    color: #ffffff;
    box-shadow: 0 14px 26px rgba(27, 39, 70, .22);
}

.btn-primary:hover {
    background: linear-gradient(135deg, color-mix(in srgb, var(--er-primary) 90%, #ffffff), var(--er-primary));
}

.btn-secondary {
    border: 1px solid var(--er-line);
    background: #ffffff;
    color: var(--er-primary);
}

.btn-secondary:hover {
    background: #f7f1e6;
}

.btn-warning {
    border: 1px solid color-mix(in srgb, var(--er-accent) 38%, transparent);
    background: var(--er-accent);
    color: #ffffff;
    box-shadow: 0 12px 24px color-mix(in srgb, var(--er-accent) 30%, transparent);
}

.card[style*="sticky"] {
    top: 18px !important;
    border-color: color-mix(in srgb, var(--er-accent) 26%, transparent);
}

#resumenReservacion {
    display: grid;
    gap: 12px;
}

.resumen-section {
    border: 1px solid var(--er-line);
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(255,255,255,.82), rgba(251,247,239,.82));
    padding: 14px;
}

.resumen-title {
    color: var(--er-primary);
    font-size: .9rem;
    letter-spacing: .03em;
    text-transform: uppercase;
}

.resumen-row {
    border-bottom: 1px solid rgba(27, 39, 70, .08);
    color: var(--er-muted);
    gap: 12px;
}

.resumen-row span:last-child {
    color: var(--er-ink);
    font-weight: 800;
    text-align: right;
}

.resumen-row strong {
    color: var(--er-ink);
    font-weight: 800;
    text-align: right;
}

.habitaciones-list {
    display: grid;
    gap: 8px;
    margin-top: 10px;
}

.habitacion-resumen-item {
    border: 1px solid rgba(27, 39, 70, .1);
    border-radius: 13px;
    background: #ffffff;
    color: var(--er-ink);
    font-weight: 800;
    box-shadow: 0 8px 18px rgba(27, 39, 70, .06);
}

.total-section {
    border-radius: 18px;
    background: linear-gradient(135deg, var(--er-primary), color-mix(in srgb, var(--er-primary) 78%, #000000));
    box-shadow: 0 16px 32px rgba(27, 39, 70, .2);
}

.total-amount {
    font-size: clamp(1.55rem, 2vw, 2rem);
    letter-spacing: 0;
}

.modal {
    z-index: 13000;
    background: rgba(12, 18, 31, .64);
    backdrop-filter: blur(7px);
}

.modal-content {
    width: min(720px, calc(100% - 28px));
    max-height: min(82vh, 720px);
    border: 1px solid color-mix(in srgb, var(--er-accent) 28%, transparent);
    border-radius: 24px;
    background: var(--er-surface-strong);
    box-shadow: 0 30px 80px rgba(12, 18, 31, .34);
}

.modal-header {
    border-bottom: 1px solid var(--er-line);
    background: linear-gradient(135deg, var(--er-primary), color-mix(in srgb, var(--er-primary) 80%, #000000));
    color: #ffffff;
    padding: 18px 20px;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    border-top: 1px solid var(--er-line);
    background: #fbf7ef;
}

.close-modal {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: rgba(255,255,255,.13);
    color: #ffffff;
    line-height: 1;
}

.close-modal:hover {
    background: rgba(255,255,255,.22);
}

#gridCortesias {
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)) !important;
    gap: 10px !important;
}

#gridCortesias > div {
    border-radius: 16px !important;
    box-shadow: 0 10px 24px rgba(27, 39, 70, .08);
    transition: transform .2s ease, box-shadow .2s ease;
}

#gridCortesias > div:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 30px rgba(27, 39, 70, .12);
}

.cortesia-option {
    border: 1px solid var(--er-line);
    border-radius: 16px;
    background: #ffffff;
    padding: 14px;
    cursor: pointer;
}

.cortesia-option.activa {
    border-color: color-mix(in srgb, var(--er-accent) 62%, #ffffff);
    background: var(--er-accent-soft);
}

.cortesia-option-main {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.cortesia-option-main strong {
    color: var(--er-primary);
}

.cortesia-option-main small {
    color: var(--er-muted);
    font-weight: 700;
}

.cortesia-check {
    width: 20px;
    height: 20px;
    cursor: pointer;
    pointer-events: none;
    accent-color: var(--er-accent);
}

.cortesia-option-price {
    margin-top: 12px;
    text-align: right;
    color: var(--er-muted);
    font-weight: 800;
}

.cortesia-chip {
    display: inline-flex;
    border-radius: 999px;
    background: color-mix(in srgb, var(--er-accent) 18%, #ffffff);
    color: #805213;
    padding: 5px 9px;
    font-size: .76rem;
    font-weight: 900;
}

@media (max-width: 1180px) {
    #formEditarHabitaciones > div {
        grid-template-columns: 1fr !important;
    }

    .card[style*="sticky"] {
        position: relative !important;
        top: auto !important;
    }
}

@media (max-width: 768px) {
    .page-container {
        padding: 14px 0 28px;
    }

    .main-container {
        width: min(100% - 18px, 720px);
    }

    .page-header {
        border-radius: 20px;
        padding: 18px;
    }

    .page-header > div {
        align-items: stretch !important;
        flex-direction: column;
    }

    .page-header .btn {
        width: 100%;
    }

    .card {
        border-radius: 20px;
    }

    .card-header,
    .card-body {
        padding: 16px;
    }

    .habitaciones-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .habitacion-item {
        min-height: 150px;
        padding: 14px;
    }

    .habitacion-numero {
        font-size: 1.22rem;
    }

    .modal-content {
        width: calc(100% - 18px);
        max-height: 86vh;
        border-radius: 20px;
    }
}

@media (max-width: 520px) {
    .habitaciones-grid {
        grid-template-columns: 1fr;
    }

    .habitacion-item {
        min-height: 136px;
    }

    .btn {
        width: 100%;
    }
}
</style>

<div class="page-container">
    <div class="main-container">
        <!-- Header -->
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="margin: 0 0 10px 0;">
                        <i class="fas fa-bed" style="margin-right: 10px;"></i>
                        Modificar Habitaciones
                    </h2>
                    <p style="margin: 0;">
                        Reservación #<?= $reservacion['id'] ?> -
                        <?= htmlspecialchars($huesped['nombre_completo'] ?? '') ?> -
                        <?= $noches ?> noche<?= $noches > 1 ? 's' : '' ?>
                    </p>
                </div>
                <?php $back_arrow_href = back_url('reservaciones/ver/' . $reservacion['id']); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('reservaciones/ver/' . $reservacion['id']) ?>" class="btn btn-secondary ms-back-legacy">
                    <i class="fas fa-arrow-left" style="margin-right: 5px;"></i>
                    Volver
                </a>
            </div>
        </div>

        <form id="formEditarHabitaciones" action="<?= url('reservaciones/actualizar-habitaciones') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="reservacion_id" value="<?= $reservacion['id'] ?>">

            <div style="display: grid; grid-template-columns: 1fr 350px; gap: 20px;">
                <!-- Columna principal -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>Habitaciones Disponibles</span>
                                <span id="contadorHabitaciones" class="selection-counter">
                                    0 seleccionadas
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Buscador -->
                            <div class="search-container">
                                <input type="text"
                                       id="buscarHabitacion"
                                       class="search-input"
                                       placeholder="Buscar por número, tipo o piso...">
                                <i class="fas fa-search search-icon"></i>
                            </div>

                            <!-- Alerta de cortesías -->
                            <div id="alertaCortesias" class="alert alert-warning" style="display: none;">
                                <i class="fas fa-gift"></i>
                                <span id="mensajeCortesias"></span>
                            </div>

                            <!-- Grid de habitaciones -->
                            <div class="habitaciones-grid">
                                <?php foreach ($habitaciones as $hab):
                                    $esta_seleccionada = in_array($hab['id'], $habitaciones_ids);
                                    $es_cortesia = in_array($hab['id'], $cortesias_actuales);
                                    $ocupada = !empty($hab['ocupacion']) && !$esta_seleccionada;

                                    $nombre_piso = [
                                        '-4' => '4 niveles abajo',
                                        '-2' => '2 niveles abajo',
                                        '-1' => 'Un nivel abajo',
                                        '1' => 'Nivel de piso',
                                        '2' => '2º Nivel',
                                        '3' => '3º Nivel'
                                    ][$hab['piso']] ?? "Piso {$hab['piso']}";
                                ?>
                                    <div class="habitacion-item <?= $ocupada ? 'ocupada' : '' ?> <?= $esta_seleccionada ? 'seleccionada' : '' ?> <?= $es_cortesia ? 'es-cortesia' : '' ?>"
                                         data-habitacion-id="<?= $hab['id'] ?>"
                                         data-precio="<?= $hab['precio_con_incremento'] ?? $hab['precio_base'] ?>"
                                         data-numero="<?= $hab['numero'] ?>"
                                         data-tipo="<?= $hab['tipo'] ?>"
                                         data-piso="<?= $nombre_piso ?>"
                                         onclick="<?= !$ocupada ? 'toggleHabitacion(this)' : '' ?>">

                                        <?php if ($es_cortesia): ?>
                                            <div class="badge-cortesia">CORTESÍA</div>
                                        <?php endif; ?>

                                        <div class="habitacion-numero">
                                            Hab. <?= $hab['numero'] ?>
                                        </div>

                                        <div class="habitacion-piso">
                                            <i class="fas fa-building" style="margin-right: 5px;"></i><?= $nombre_piso ?>
                                        </div>

                                        <div class="habitacion-tipo">
                                            <?= ucfirst($hab['tipo']) ?>
                                            <?php if (strpos($hab['tipo'], 'jacuzzi') !== false): ?>
                                                <i class="fas fa-hot-tub" style="color: #17a2b8; margin-left: 5px;"></i>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($ocupada && !empty($hab['ocupacion'])): ?>
                                            <div class="info-ocupacion">
                                                <strong>Ocupada</strong><br>
                                                <?= date('d/m', strtotime($hab['ocupacion']['fecha_entrada'])) ?> -
                                                <?= date('d/m', strtotime($hab['ocupacion']['fecha_salida'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="habitacion-precio-row">
                                                <span>Por noche</span>
                                                <strong>
                                                    $<?= number_format($hab['precio_con_incremento'] ?? $hab['precio_base'], 0) ?>
                                                </strong>
                                            </div>
                                        <?php endif; ?>

                                        <input type="checkbox"
                                               name="habitaciones[]"
                                               value="<?= $hab['id'] ?>"
                                               class="habitacion-check"
                                               style="display: none;"
                                               <?= $esta_seleccionada ? 'checked' : '' ?>>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna de resumen -->
                <div>
                    <div class="card" style="position: sticky; top: 20px;">
                        <div class="card-header">
                            <i class="fas fa-calculator" style="margin-right: 8px;"></i>
                            Resumen de Cambios
                        </div>
                        <div class="card-body">
                            <div id="resumenReservacion">
                                <!-- Se llenará con JavaScript -->
                            </div>

                            <div style="margin-top: 20px;">
                                <button type="submit"
                                        id="btnGuardar"
                                        class="btn btn-primary"
                                        style="width: 100%; margin-bottom: 10px;"
                                        disabled>
                                    <i class="fas fa-save" style="margin-right: 8px;"></i>
                                    Guardar Cambios
                                </button>
                                <a href="<?= back_url('reservaciones/ver/' . $reservacion['id']) ?>"
                                   class="btn btn-secondary"
                                   style="width: 100%; display: block; text-decoration: none;">
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal de cortesías -->
<div id="modalCortesias" class="modal" data-ms-overlay-close>
    <div class="modal-content ms-anim-panel">
        <div class="modal-header">
            <h3 style="margin: 0;">
                <i class="fas fa-gift" style="margin-right: 8px; color: var(--er-accent);"></i>
                Seleccionar Cortesías
            </h3>
            <button class="close-modal" onclick="cerrarModalCortesias()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Cortesías disponibles: <span id="cortesiasDisponibles">0</span></strong><br>
                    <small>Por cada 11 habitaciones, 1 puede ser de cortesía</small>
                </div>
            </div>
            <div id="listaHabitacionesCortesia" style="margin-top: 20px;">
                <!-- Se llenará dinámicamente -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModalCortesias()" style="margin-right: 10px;">
                Cancelar
            </button>
            <button class="btn btn-warning" onclick="aplicarCortesias()">
                <i class="fas fa-check" style="margin-right: 5px;"></i>
                Aplicar Cortesías
            </button>
        </div>
    </div>
</div>

<script>
// Variables globales
let habitacionesSeleccionadas = [];
let habitacionesCortesiaSeleccionadas = <?= json_encode($cortesias_actuales) ?>;
const fechaEntrada = '<?= $reservacion['fecha_entrada'] ?>';
const fechaSalida = '<?= $reservacion['fecha_salida'] ?>';
const noches = <?= $noches ?>;
const precioOriginal = <?= $reservacion['precio_total'] ?>; // Precio total actual de la reservación

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Cargar habitaciones ya seleccionadas
    document.querySelectorAll('.habitacion-check:checked').forEach(function(checkbox) {
        const habitacionDiv = checkbox.closest('.habitacion-item');
        const hab = {
            id: habitacionDiv.dataset.habitacionId,
            numero: habitacionDiv.dataset.numero,
            tipo: habitacionDiv.dataset.tipo,
            piso: habitacionDiv.dataset.piso,
            precio: parseFloat(habitacionDiv.dataset.precio)
        };
        habitacionesSeleccionadas.push(hab);
    });

    actualizarContador();
    actualizarResumen();
    verificarCortesias();

    // Configurar búsqueda
    document.getElementById('buscarHabitacion').addEventListener('input', function(e) {
        const busqueda = e.target.value.toLowerCase();

        document.querySelectorAll('.habitacion-item').forEach(function(item) {
            const numero = item.dataset.numero.toLowerCase();
            const tipo = item.dataset.tipo.toLowerCase();
            const piso = item.dataset.piso.toLowerCase();

            if (numero.includes(busqueda) || tipo.includes(busqueda) || piso.includes(busqueda)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

function toggleHabitacion(element) {
    const habitacionId = element.dataset.habitacionId;
    const checkbox = element.querySelector('.habitacion-check');
    const isChecked = checkbox.checked;

    if (!isChecked) {
        // Seleccionar habitación
        checkbox.checked = true;
        element.classList.add('seleccionada');

        const hab = {
            id: habitacionId,
            numero: element.dataset.numero,
            tipo: element.dataset.tipo,
            piso: element.dataset.piso,
            precio: parseFloat(element.dataset.precio)
        };
        habitacionesSeleccionadas.push(hab);
    } else {
        // Deseleccionar habitación
        checkbox.checked = false;
        element.classList.remove('seleccionada');
        element.classList.remove('es-cortesia');

        // Remover de seleccionadas
        habitacionesSeleccionadas = habitacionesSeleccionadas.filter(h => h.id !== habitacionId);

        // Remover de cortesías si estaba
        const indexCortesia = habitacionesCortesiaSeleccionadas.indexOf(habitacionId);
        if (indexCortesia > -1) {
            habitacionesCortesiaSeleccionadas.splice(indexCortesia, 1);
        }
    }

    actualizarContador();
    actualizarResumen();
    verificarCortesias();
}

function actualizarContador() {
    document.getElementById('contadorHabitaciones').textContent =
        habitacionesSeleccionadas.length + ' seleccionadas';
}

function verificarCortesias() {
    const totalHabs = habitacionesSeleccionadas.length;
    const cortesiasDisponibles = Math.floor(totalHabs / 11);
    const cortesiasActuales = habitacionesCortesiaSeleccionadas.length;

    const alertaCortesias = document.getElementById('alertaCortesias');
    const mensajeCortesias = document.getElementById('mensajeCortesias');

    if (cortesiasDisponibles > 0) {
        alertaCortesias.style.display = 'flex';

        let mensaje = '';
        if (cortesiasActuales === 0) {
            mensaje = `Tienes <strong>${cortesiasDisponibles}</strong> cortesía${cortesiasDisponibles > 1 ? 's' : ''} disponible${cortesiasDisponibles > 1 ? 's' : ''}. `;
        } else if (cortesiasActuales < cortesiasDisponibles) {
            mensaje = `Usando <strong>${cortesiasActuales}</strong> de <strong>${cortesiasDisponibles}</strong> cortesía${cortesiasDisponibles > 1 ? 's' : ''} disponible${cortesiasDisponibles > 1 ? 's' : ''}. `;
        } else {
            mensaje = `Usando todas las cortesías disponibles (${cortesiasActuales}). `;
        }

        mensaje += `<button type="button" class="btn btn-warning" style="margin-left: 10px; padding: 5px 15px; font-size: 13px;" onclick="mostrarModalCortesias()">
                        <i class="fas fa-gift" style="margin-right: 5px;"></i>
                        ${cortesiasActuales === 0 ? 'Seleccionar' : 'Modificar'}
                    </button>`;

        mensajeCortesias.innerHTML = mensaje;
    } else {
        alertaCortesias.style.display = 'none';
        // Limpiar cortesías si no hay suficientes habitaciones
        if (habitacionesCortesiaSeleccionadas.length > 0) {
            habitacionesCortesiaSeleccionadas = [];
            document.querySelectorAll('.habitacion-item').forEach(item => {
                item.classList.remove('es-cortesia');
            });
        }
    }
}

function mostrarModalCortesias() {
    const modal = document.getElementById('modalCortesias');
    const totalHabs = habitacionesSeleccionadas.length;
    const cortesiasDisponibles = Math.floor(totalHabs / 11);

    document.getElementById('cortesiasDisponibles').textContent = cortesiasDisponibles;

    // Generar lista
    let html = '<div id="gridCortesias">';
    habitacionesSeleccionadas.forEach(hab => {
        const esCortesia = habitacionesCortesiaSeleccionadas.includes(hab.id);
        html += `
            <div class="cortesia-option ${esCortesia ? 'activa' : ''}"
                 onclick="toggleCortesiaModal('${hab.id}', this)">
                <div class="cortesia-option-main">
                    <div>
                        <strong>Hab. ${hab.numero}</strong><br>
                        <small>${hab.tipo}</small>
                    </div>
                    <input type="checkbox"
                           class="cortesia-check"
                           data-hab-id="${hab.id}"
                           ${esCortesia ? 'checked' : ''}>
                </div>
                <div class="cortesia-option-price">
                    ${esCortesia ? '<span class="cortesia-chip">CORTESÍA</span>' :
                                  '<span>$' + (hab.precio * noches).toLocaleString() + '</span>'}
                </div>
            </div>
        `;
    });
    html += '</div>';

    document.getElementById('listaHabitacionesCortesia').innerHTML = html;
    if (window.msModal) {
        window.msModal.open(modal, { display: 'block' });
    } else {
        modal.style.display = 'block';
    }
}

function toggleCortesiaModal(habId, element) {
    const checkbox = element.querySelector('.cortesia-check');
    const totalHabs = habitacionesSeleccionadas.length;
    const cortesiasDisponibles = Math.floor(totalHabs / 11);
    const cortesiasActualesSeleccionadas = document.querySelectorAll('.cortesia-check:checked').length;

    // Si el checkbox ya está marcado, permitir desmarcarlo
    // Si no está marcado, verificar que no se exceda el límite
    if (!checkbox.checked && cortesiasActualesSeleccionadas >= cortesiasDisponibles) {
        window.msToast('warning', null, `Solo puedes seleccionar ${cortesiasDisponibles} habitación${cortesiasDisponibles > 1 ? 'es' : ''} de cortesía`);
        return;
    }

    // Toggle el checkbox
    checkbox.checked = !checkbox.checked;

    // Actualizar visual del contenedor
    element.classList.toggle('activa', checkbox.checked);
    if (checkbox.checked) {
        element.querySelector('.cortesia-option-price').innerHTML = '<span class="cortesia-chip">CORTESÍA</span>';
    } else {
        const hab = habitacionesSeleccionadas.find(h => h.id === habId);
        element.querySelector('.cortesia-option-price').innerHTML = '<span>$' + (hab.precio * noches).toLocaleString() + '</span>';
    }
}

function aplicarCortesias() {
    habitacionesCortesiaSeleccionadas = [];

    document.querySelectorAll('.cortesia-check:checked').forEach(checkbox => {
        habitacionesCortesiaSeleccionadas.push(checkbox.dataset.habId);
    });

    // Actualizar visual en el grid principal
    document.querySelectorAll('.habitacion-item').forEach(item => {
        item.classList.remove('es-cortesia');
    });

    habitacionesCortesiaSeleccionadas.forEach(habId => {
        const elemento = document.querySelector(`.habitacion-item[data-habitacion-id="${habId}"]`);
        if (elemento) {
            elemento.classList.add('es-cortesia');
        }
    });

    cerrarModalCortesias();
    actualizarResumen();
    verificarCortesias();
}

function cerrarModalCortesias() {
    const modal = document.getElementById('modalCortesias');
    if (window.msModal) {
        window.msModal.close(modal, { display: 'block' });
    } else if (modal) {
        modal.style.display = 'none';
    }
}

function actualizarResumen() {
    const totalHabs = habitacionesSeleccionadas.length;
    const habsCortesia = habitacionesCortesiaSeleccionadas.length;

    let precioTotal = 0;
    let precioSinDescuento = 0;

    habitacionesSeleccionadas.forEach(hab => {
        // El precio YA incluye las noches, no multiplicar de nuevo
        const precioHab = hab.precio;
        precioSinDescuento += precioHab;

        if (!habitacionesCortesiaSeleccionadas.includes(hab.id)) {
            precioTotal += precioHab;
        }
    });

    let html = '';

    // Información básica
    html += `
        <div class="resumen-section">
            <div class="resumen-row">
                <span>Check-in:</span>
                <strong>${formatearFecha(fechaEntrada)}</strong>
            </div>
            <div class="resumen-row">
                <span>Check-out:</span>
                <strong>${formatearFecha(fechaSalida)}</strong>
            </div>
            <div class="resumen-row">
                <span>Noches:</span>
                <strong>${noches}</strong>
            </div>
            <div class="resumen-row">
                <span>Habitaciones:</span>
                <strong>${totalHabs}</strong>
            </div>
        </div>
    `;

    // Lista de habitaciones
    if (habitacionesSeleccionadas.length > 0) {
        html += `
            <div style="margin: 15px 0;">
                <strong style="display: block; margin-bottom: 10px;">Habitaciones seleccionadas:</strong>
                <div class="habitaciones-list">
        `;

        habitacionesSeleccionadas.forEach(hab => {
            const esCortesia = habitacionesCortesiaSeleccionadas.includes(hab.id);
            html += `
                <div class="habitacion-resumen-item ${esCortesia ? 'cortesia' : ''}">
                    <span>
                        Hab. ${hab.numero}
                        ${esCortesia ? '<span class="badge-cortesia" style="margin-left: 8px;">CORTESÍA</span>' : ''}
                    </span>
                    <span style="font-weight: 600; ${esCortesia ? 'text-decoration: line-through;' : ''}">
                        $${hab.precio.toLocaleString()}
                    </span>
                </div>
            `;
        });

        html += '</div></div>';
    }

    // Resumen de precios
    html += `
        <div class="resumen-section">
            <div class="resumen-row">
                <span>Precio original:</span>
                <strong>$${precioOriginal.toLocaleString()}</strong>
            </div>
            <div class="resumen-row">
                <span>Precio nuevo:</span>
                <strong style="font-size: 18px; color: var(--er-primary);">$${precioTotal.toLocaleString()}</strong>
            </div>
        </div>
    `;

    // Diferencia
    const diferencia = precioTotal - precioOriginal;
    if (diferencia !== 0) {
        html += `
            <div class="alert ${diferencia > 0 ? 'alert-warning' : 'alert-info'}" style="margin: 15px 0;">
                <i class="fas fa-${diferencia > 0 ? 'arrow-up' : 'arrow-down'}"></i>
                <span>
                    ${diferencia > 0 ? 'Aumento' : 'Reducción'}:
                    <strong>${diferencia > 0 ? '+' : ''}$${Math.abs(diferencia).toLocaleString()}</strong>
                </span>
            </div>
        `;
    }

    document.getElementById('resumenReservacion').innerHTML = html;

    // Habilitar/deshabilitar botón
    document.getElementById('btnGuardar').disabled = totalHabs === 0;
}

function formatearFecha(fecha) {
    const [año, mes, dia] = fecha.split('-');
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return `${dia} ${meses[parseInt(mes)-1]} ${año}`;
}

// Manejar envío del formulario
document.getElementById('formEditarHabitaciones').addEventListener('submit', function(e) {
    e.preventDefault();

    const totalHabs = habitacionesSeleccionadas.length;

    if (totalHabs === 0) {
        window.msToast('warning', null, 'Debe seleccionar al menos una habitación');
        return;
    }

    // Agregar campos ocultos para las cortesías
    document.querySelectorAll('.cortesia-hidden').forEach(input => input.remove());

    habitacionesCortesiaSeleccionadas.forEach(habId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'cortesias[]';
        input.value = habId;
        input.className = 'cortesia-hidden';
        this.appendChild(input);
    });

    // Confirmar con el usuario
    const form = this;
    msConfirm({
        type: 'warning',
        icon: 'alert',
        title: '¿Confirmar cambios?',
        msg: 'Se actualizarán las habitaciones de esta reservación.',
        confirmLabel: 'Guardar cambios'
    }).then(ok => {
        if (ok) form.submit();
    });
});
</script>
