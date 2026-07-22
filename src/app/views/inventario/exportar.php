<?php
$fecha_hoy = $fecha_hoy ?? date('Y-m-d');
$fecha_inicio_mes = $fecha_inicio_mes ?? date('Y-m-01');
?>

<style>
:root {
    --lc-green: #5C7A4E;
    --lc-green-dark: #4A6340;
    --lc-green-deep: #3D5234;
    --lc-gold: #C8A96A;
    --lc-cream: #F7F4EE;
    --lc-cream-mid: #EEE9DE;
    --exp-text: #2F3A2D;
    --exp-muted: #7A8574;
    --exp-line: #DDE8D5;
    --exp-panel: #FFFEFB;
    --exp-soft: #F3F7F0;
    --exp-red: #DC2626;
    --exp-blue: #3B6FD6;
    --exp-purple: #7C5CD6;
    --exp-shadow: 0 18px 42px -34px rgba(61, 82, 52, .36);
}

.exportar-view {
    min-height: 100vh;
    opacity: 0;
    background:
        radial-gradient(circle at 12% 0%, rgba(92,122,78,.12), transparent 24rem),
        radial-gradient(circle at 90% 5%, rgba(200,169,106,.13), transparent 26rem),
        linear-gradient(145deg,#EFF4EC 0%,#E8EFE3 45%,#F4F1EB 100%);
    color: var(--exp-text);
    font-family: "Inter", "Segoe UI", system-ui, sans-serif;
    transition: opacity .24s ease;
}

.exportar-view.loaded { opacity: 1; }

.exp-topbar {
    position: relative;
    background: rgba(255, 254, 251, .9);
    border-bottom: 1px solid var(--exp-line);
}

.exp-topbar::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--lc-green-deep), var(--lc-gold), var(--lc-green-deep));
}

.exp-shell {
    width: min(980px, calc(100% - 32px));
    margin: 0 auto;
}

.exp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 0;
}

.exp-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.exp-icon {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    background: rgba(92, 122, 78, .12);
    color: var(--lc-green);
    border: 1px solid rgba(92, 122, 78, .18);
}

.exp-title-group h1 {
    margin: 0;
    color: var(--lc-green-deep);
    font-size: 1.18rem;
    line-height: 1.15;
    font-weight: 780;
}

.exp-title-group p {
    margin: 4px 0 0;
    color: var(--exp-muted);
    font-size: .78rem;
    line-height: 1.35;
}

.exp-main {
    padding: 24px 0 52px;
}

.exp-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(280px, .82fr);
    gap: 16px;
    align-items: start;
}

.exp-panel {
    background: var(--exp-panel);
    border: 1px solid var(--exp-line);
    border-radius: 16px;
    box-shadow: var(--exp-shadow);
    overflow: hidden;
}

.exp-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 17px 18px 14px;
    border-bottom: 1px solid #EAF0E5;
}

.exp-panel-head h2,
.exp-side-title h2 {
    margin: 0;
    color: var(--lc-green-deep);
    font-size: .98rem;
    line-height: 1.2;
    font-weight: 760;
}

.exp-panel-head p,
.exp-side-title p {
    margin: 5px 0 0;
    color: var(--exp-muted);
    font-size: .78rem;
    line-height: 1.45;
}

.exp-mini-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    background: #F0F5ED;
    border: 1px solid #D5E4CB;
    color: var(--lc-green-dark);
    font-size: .7rem;
    font-weight: 720;
    white-space: nowrap;
}

.exp-form {
    padding: 18px;
}

.exp-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 16px;
    padding: 13px 14px;
    border: 1px solid #D8E6D0;
    border-radius: 12px;
    background: linear-gradient(135deg, #F8FBF5, #F0F5ED);
    color: #4A6340;
    font-size: .8rem;
    line-height: 1.45;
}

.exp-note i {
    margin-top: 2px;
    color: var(--lc-green);
}

.exp-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.exp-field label {
    display: block;
    margin-bottom: 6px;
    color: #4A5842;
    font-size: .74rem;
    line-height: 1.2;
    font-weight: 740;
}

.exp-field input {
    width: 100%;
    min-height: 42px;
    border: 1px solid #D6E2CF;
    border-radius: 11px;
    background: #FFFEFB;
    color: var(--exp-text);
    padding: 0 12px;
    font-size: .84rem;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.exp-field input:focus {
    outline: none;
    border-color: var(--lc-green);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(92, 122, 78, .16);
}

.exp-actions {
    display: flex;
    gap: 10px;
    margin-top: 18px;
}

.exp-btn {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 11px;
    border: 1px solid transparent;
    padding: 0 15px;
    font-size: .82rem;
    font-weight: 760;
    text-decoration: none;
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease, color .18s ease;
}

.exp-btn:hover,
.exp-btn:focus-visible {
    transform: translateY(-1px);
    outline: none;
}

.exp-btn:active {
    transform: translateY(0) scale(.99);
}

.exp-btn.primary {
    flex: 1;
    background: linear-gradient(135deg, var(--exp-red), #B91C1C);
    color: #FFFEFB;
    box-shadow: 0 12px 24px -18px rgba(220, 38, 38, .7);
}

.exp-btn.primary:hover,
.exp-btn.primary:focus-visible {
    box-shadow: 0 16px 28px -20px rgba(220, 38, 38, .85);
}

.exp-btn.secondary,
.exp-back {
    background: #FFFEFB;
    border-color: #DDE8D5;
    color: var(--lc-green-deep);
}

.exp-btn.secondary:hover,
.exp-btn.secondary:focus-visible,
.exp-back:hover,
.exp-back:focus-visible {
    background: #F7FCF4;
    border-color: #C7DABE;
}

.exp-back {
    min-height: 38px;
    padding: 0 13px;
}

.exp-side {
    display: grid;
    gap: 12px;
}

.exp-side-title {
    padding: 17px 18px 4px;
}

.exp-option-list {
    display: grid;
    gap: 10px;
    padding: 0 14px 14px;
}

.exp-option {
    width: 100%;
    min-height: 82px;
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr);
    gap: 11px;
    align-items: center;
    border: 1px solid var(--option-line, #DDE8D5);
    border-radius: 14px;
    background:
        radial-gradient(circle at 0% 0%, var(--option-soft, rgba(92, 122, 78, .08)), transparent 7rem),
        #FFFEFB;
    padding: 12px;
    text-align: left;
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
}

.exp-option:hover,
.exp-option:focus-visible {
    transform: translateY(-1px);
    border-color: var(--option-color, var(--lc-green));
    box-shadow: 0 14px 28px -24px var(--option-shadow, rgba(92, 122, 78, .44));
    outline: none;
}

.exp-option-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--option-soft, rgba(92, 122, 78, .1));
    color: var(--option-color, var(--lc-green));
}

.exp-option strong {
    display: block;
    color: #344054;
    font-size: .83rem;
    line-height: 1.25;
    font-weight: 760;
}

.exp-option-copy {
    display: block;
    margin-top: 3px;
    color: var(--exp-muted);
    font-size: .72rem;
    line-height: 1.35;
}

.exp-option.stock {
    --option-color: var(--exp-blue);
    --option-soft: rgba(59, 111, 214, .09);
    --option-line: rgba(59, 111, 214, .18);
    --option-shadow: rgba(59, 111, 214, .5);
}

.exp-option.today {
    --option-color: var(--exp-purple);
    --option-soft: rgba(124, 92, 214, .09);
    --option-line: rgba(124, 92, 214, .18);
    --option-shadow: rgba(124, 92, 214, .5);
}

.exp-footer-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-top: 14px;
    padding: 13px 14px;
    border: 1px solid #E7D7AA;
    border-radius: 14px;
    background: linear-gradient(135deg, #FFFBEF, #FAF4DE);
    color: #7A5A16;
    font-size: .78rem;
    line-height: 1.45;
}

.exp-footer-note i {
    margin-top: 2px;
    color: #B98A35;
}

@media (max-width: 820px) {
    .exp-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .exp-shell {
        width: min(100% - 24px, 980px);
    }

    .exp-header,
    .exp-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .exp-title-group {
        align-items: flex-start;
    }

    .exp-fields {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="exportar-view">
    <header class="exp-topbar">
        <div class="exp-shell">
            <div class="exp-header">
                <div class="exp-title-group">
                    <div class="exp-icon">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <div>
                        <h1>Exportar inventario</h1>
                        <p>Genera reportes PDF sin salir del modulo de inventario.</p>
                    </div>
                </div>

                <?php $back_arrow_href = back_url('inventario'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('inventario') ?>" class="exp-btn exp-back ms-back-legacy">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </div>
    </header>

    <main class="exp-main">
        <div class="exp-shell">
            <div class="exp-grid">
                <section class="exp-panel" aria-label="Generar reporte de inventario">
                    <div class="exp-panel-head">
                        <div>
                            <h2>Reporte por periodo</h2>
                            <p>Incluye stock actual y movimientos del rango seleccionado.</p>
                        </div>
                        <span class="exp-mini-badge">
                            <i class="fas fa-file-pdf"></i>
                            PDF
                        </span>
                    </div>

                    <form method="POST" action="<?= url('inventario/generarPdfMovimientos') ?>" class="exp-form">
                        <?= csrf_field() ?>

                        <div class="exp-note">
                            <i class="fas fa-info-circle"></i>
                            <span>Selecciona el periodo y descarga un reporte listo para revision o respaldo.</span>
                        </div>

                        <div class="exp-fields">
                            <div class="exp-field">
                                <label for="fecha_desde">Fecha desde <span class="text-red-500">*</span></label>
                                <input type="date"
                                       id="fecha_desde"
                                       name="fecha_desde"
                                       value="<?= htmlspecialchars($fecha_inicio_mes, ENT_QUOTES, 'UTF-8') ?>"
                                       required>
                            </div>

                            <div class="exp-field">
                                <label for="fecha_hasta">Fecha hasta <span class="text-red-500">*</span></label>
                                <input type="date"
                                       id="fecha_hasta"
                                       name="fecha_hasta"
                                       value="<?= htmlspecialchars($fecha_hoy, ENT_QUOTES, 'UTF-8') ?>"
                                       required>
                            </div>
                        </div>

                        <div class="exp-actions">
                            <button type="submit" class="exp-btn primary">
                                <i class="fas fa-download"></i>
                                Generar PDF
                            </button>
                            <a href="<?= url('inventario') ?>" class="exp-btn secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </section>

                <aside class="exp-panel exp-side" aria-label="Exportaciones rápidas">
                    <div class="exp-side-title">
                        <h2>Exportaciones rápidas</h2>
                        <p>Accesos simples para reportes frecuentes.</p>
                    </div>

                    <div class="exp-option-list">
                        <button type="button" onclick="exportarStockActual()" class="exp-option stock">
                            <span class="exp-option-icon">
                                <i class="fas fa-boxes"></i>
                            </span>
                            <span class="exp-option-copy">
                                <strong>Stock actual</strong>
                                <span>Inventario vigente con productos en nivel bajo.</span>
                            </span>
                        </button>

                        <button type="button" onclick="exportarMovimientosHoy()" class="exp-option today">
                            <span class="exp-option-icon">
                                <i class="fas fa-history"></i>
                            </span>
                            <span class="exp-option-copy">
                                <strong>Movimientos de hoy</strong>
                                <span>Descarga los movimientos registrados en el dia.</span>
                            </span>
                        </button>
                    </div>
                </aside>
            </div>

            <div class="exp-footer-note">
                <i class="fas fa-lightbulb"></i>
                <span>El periodo se prepara con la fecha actual del sistema. Ajustalo antes de generar el PDF si necesitas otro rango.</span>
            </div>
        </div>
    </main>
</div>

<script>
const fechaHoyExportacion = <?= json_encode($fecha_hoy) ?>;
const fechaInicioMesExportacion = <?= json_encode($fecha_inicio_mes) ?>;

// Funcion para obtener la fecha actual en formato correcto
function getFechaActual() {
    return fechaHoyExportacion;
}

function exportarStockActual() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url("inventario/generarPdfMovimientos") ?>';
    form.innerHTML = `
        <?= csrf_field() ?>
        <input type="hidden" name="fecha_desde" value="${fechaInicioMesExportacion}">
        <input type="hidden" name="fecha_hasta" value="${fechaHoyExportacion}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function exportarMovimientosHoy() {
    const hoy = getFechaActual();
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url("inventario/generarPdfMovimientos") ?>';
    form.innerHTML = `
        <?= csrf_field() ?>
        <input type="hidden" name="fecha_desde" value="${hoy}">
        <input type="hidden" name="fecha_hasta" value="${hoy}">
    `;
    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.exportar-view').classList.add('loaded');
});
</script>
