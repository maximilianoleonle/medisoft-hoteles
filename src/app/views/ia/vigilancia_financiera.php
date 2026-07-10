<?php
/**
 * Vigilancia financiera (IA): informe forense sobre la conciliacion
 * determinista, con semaforo de riesgo. Vista interna con layout estandar.
 * Escalera de costo: plantilla local ($0) cuando todo cuadra; analista IA
 * cuando hay hallazgos.
 */
$resultado = $resultado ?? ['success' => false, 'message' => 'Sin datos.'];
$totales = $totales ?? ['error' => 0, 'warning' => 0, 'hallazgos' => 0];
$configurado = $configurado ?? false;

$vgfSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

/**
 * Render minimo de Markdown a HTML seguro (mismo enfoque que el asesor
 * inteligente): escapa todo primero y luego aplica solo negritas,
 * encabezados y listas. Sin HTML libre del modelo.
 */
$vgfMarkdown = static function ($texto) {
    $lineas = explode("\n", (string) $texto);
    $html = '';
    $enLista = false;

    foreach ($lineas as $linea) {
        $linea = htmlspecialchars(rtrim($linea), ENT_QUOTES, 'UTF-8');
        $linea = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $linea);

        if (preg_match('/^#{1,3}\s*(.+)$/', $linea, $m)) {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
            $html .= '<h3>' . $m[1] . '</h3>';
        } elseif (preg_match('/^\s*[-*]\s+(.+)$/', $linea, $m)) {
            if (!$enLista) { $html .= '<ul>'; $enLista = true; }
            $html .= '<li>' . $m[1] . '</li>';
        } elseif (preg_match('/^\s*\d+\.\s+(.+)$/', $linea, $m)) {
            if (!$enLista) { $html .= '<ul>'; $enLista = true; }
            $html .= '<li>' . $m[1] . '</li>';
        } elseif (trim($linea) === '') {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
        } else {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
            $html .= '<p>' . $linea . '</p>';
        }
    }

    if ($enLista) {
        $html .= '</ul>';
    }

    return $html;
};

$hayInforme = !empty($resultado['success']);
$informe = (string) ($resultado['informe'] ?? '');

// Semaforo: se deriva del propio informe (la plantilla siempre emite 🟢 y el
// analista abre con 🟡 o 🔴). Fallback a los conteos deterministas.
$semaforo = 'pendiente';
if ($hayInforme) {
    if (strpos($informe, '🔴') !== false) {
        $semaforo = 'rojo';
    } elseif (strpos($informe, '🟡') !== false) {
        $semaforo = 'amarillo';
    } elseif (strpos($informe, '🟢') !== false) {
        $semaforo = 'verde';
    } else {
        $semaforo = ((int) $totales['error'] > 0) ? 'rojo' : (((int) $totales['warning'] > 0) ? 'amarillo' : 'verde');
    }
}

$semaforoTexto = [
    'verde' => 'Todo cuadra',
    'amarillo' => 'Revisar',
    'rojo' => 'Atender hoy',
    'pendiente' => 'Pendiente',
][$semaforo];

$semaforoIcono = [
    'verde' => 'fa-circle-check',
    'amarillo' => 'fa-triangle-exclamation',
    'rojo' => 'fa-circle-exclamation',
    'pendiente' => 'fa-clock',
][$semaforo];

$esPlantilla = ($resultado['modelo'] ?? '') === 'plantilla';
$fuenteTexto = $hayInforme
    ? ($esPlantilla ? 'Verificación automática' : 'Analista IA')
    : ($configurado ? 'Listo' : 'Sin configurar');

$generadoHumano = '';
if (!empty($resultado['generado_en'])) {
    $generadoTs = strtotime((string) $resultado['generado_en']);
    $generadoHumano = $generadoTs ? date('d/m/Y H:i', $generadoTs) : '';
}

$errores = (int) ($totales['error'] ?? 0);
$warnings = (int) ($totales['warning'] ?? 0);
$hallazgos = (int) ($totales['hallazgos'] ?? 0);
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.vgf {
    --vgf-brand: var(--brand-primary, #1B2746);
    --vgf-gold: var(--brand-accent, #BD9441);
    --vgf-gold-soft: color-mix(in srgb, var(--vgf-gold) 15%, #FFFFFF);
    --vgf-gold-line: color-mix(in srgb, var(--vgf-gold) 42%, #E4D4B0);
    --vgf-gold-ink: color-mix(in srgb, var(--vgf-gold) 58%, var(--vgf-brand));
    --vgf-ivory: #F6F2EA;
    --vgf-ivory-2: #FBF8F2;
    --vgf-border: color-mix(in srgb, var(--vgf-brand) 6%, #E9E1D6);
    --vgf-text: color-mix(in srgb, var(--vgf-brand) 46%, #707B8C);
    --vgf-muted: #8791A2;
    --vgf-heading: color-mix(in srgb, var(--vgf-brand) 66%, #566172);
    --vgf-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --vgf-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --vgf-success: #1E9E63;
    --vgf-success-bg: #E7F4EC;
    --vgf-success-line: #BEE3CD;
    --vgf-warning: #C2841C;
    --vgf-warning-bg: #FAF0DC;
    --vgf-warning-line: #EBD8AC;
    --vgf-danger: #B4392B;
    --vgf-danger-bg: #F8EAE5;
    --vgf-danger-line: #EFC9BE;
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 18px 16px 44px;
    color: var(--vgf-text);
    font-family: var(--vgf-sans);
    font-size: .92rem;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--vgf-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--vgf-ivory-2), var(--vgf-ivory));
}
.vgf * { box-sizing: border-box; }
.vgf-shell {
    display: grid;
    gap: 14px;
    width: 100%;
    max-width: 1040px;
    min-width: 0;
    margin: 0 auto;
}
.vgf-hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px 28px;
    padding: 2px 0 6px;
}
.vgf-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(100%, 760px);
}
.vgf-title-lockup > div:last-child { min-width: 0; }
.vgf-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--vgf-gold), var(--vgf-brand) 54%, color-mix(in srgb, var(--vgf-brand) 68%, var(--vgf-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--vgf-brand) 72%, transparent);
}
.vgf-kicker {
    margin: 0 0 2px;
    color: var(--vgf-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}
.vgf-title {
    margin: 0;
    color: var(--vgf-heading);
    font-family: var(--vgf-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650;
    line-height: .98;
    overflow-wrap: anywhere;
}
.vgf-subtitle {
    max-width: 48rem;
    margin: 9px 0 0;
    color: var(--vgf-muted);
    font-size: .94rem;
    font-weight: 500;
    line-height: 1.5;
}
.vgf-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 36px;
    padding: 0 12px;
    border-radius: 999px;
    border: 1px solid var(--vgf-gold-line);
    background: var(--vgf-gold-soft);
    color: var(--vgf-gold-ink);
    font-size: .78rem;
    font-weight: 650;
    white-space: nowrap;
}
.vgf-status-pill.is-verde { border-color: var(--vgf-success-line); background: var(--vgf-success-bg); color: var(--vgf-success); }
.vgf-status-pill.is-amarillo { border-color: var(--vgf-warning-line); background: var(--vgf-warning-bg); color: var(--vgf-warning); }
.vgf-status-pill.is-rojo { border-color: var(--vgf-danger-line); background: var(--vgf-danger-bg); color: var(--vgf-danger); }
.vgf-status-pill i { font-size: .84rem; }
.vgf-alert {
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--vgf-border);
    background: #fff;
    font-size: .88rem;
}
.vgf-alert.is-success { border-color: var(--vgf-success-line); background: var(--vgf-success-bg); color: var(--vgf-success); }
.vgf-alert.is-error { border-color: var(--vgf-danger-line); background: var(--vgf-danger-bg); color: var(--vgf-danger); }
.vgf-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.vgf-summary-item {
    background: rgba(255,255,255,.82);
    border: 1px solid var(--vgf-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}
.vgf-summary-label {
    color: var(--vgf-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    text-transform: uppercase;
}
.vgf-summary-value {
    margin-top: 4px;
    color: var(--vgf-heading);
    font-size: 1.02rem;
    font-weight: 650;
}
.vgf-summary-value.is-verde { color: var(--vgf-success); }
.vgf-summary-value.is-amarillo { color: var(--vgf-warning); }
.vgf-summary-value.is-rojo { color: var(--vgf-danger); }
.vgf-panel {
    background: rgba(255,255,255,.9);
    border: 1px solid var(--vgf-border);
    border-radius: 16px;
    padding: 16px;
    display: grid;
    gap: 12px;
}
.vgf-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.vgf-panel-title {
    margin: 0;
    color: var(--vgf-heading);
    font-family: var(--vgf-serif);
    font-size: 1.35rem;
    font-weight: 650;
}
.vgf-panel-sub { margin: 3px 0 0; color: var(--vgf-muted); font-size: .85rem; }
.vgf-toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.vgf-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 16px;
    border: 0;
    border-radius: 12px;
    cursor: pointer;
    color: #fff;
    font-family: var(--vgf-sans);
    font-size: .85rem;
    font-weight: 650;
    background: linear-gradient(145deg, var(--vgf-gold), var(--vgf-brand) 60%);
    box-shadow: 0 12px 22px -14px color-mix(in srgb, var(--vgf-brand) 70%, transparent);
    text-decoration: none;
}
.vgf-btn[disabled] { opacity: .55; cursor: not-allowed; }
.vgf-btn.sec {
    color: var(--vgf-gold-ink);
    background: var(--vgf-gold-soft);
    border: 1px solid var(--vgf-gold-line);
    box-shadow: none;
}
.vgf-reading {
    background: #fff;
    border: 1px solid var(--vgf-border);
    border-radius: 16px;
    overflow: hidden;
}
.vgf-reading-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding: 14px 22px;
    border-bottom: 1px solid var(--vgf-border);
    background: var(--vgf-ivory-2);
}
.vgf-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 0; color: var(--vgf-muted); font-size: .8rem; }
.vgf-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid var(--vgf-gold-line);
    background: var(--vgf-gold-soft);
    color: var(--vgf-gold-ink);
    font-weight: 650;
}
.vgf-chip.is-plantilla { border-color: var(--vgf-success-line); background: var(--vgf-success-bg); color: var(--vgf-success); }
.vgf-card-body { padding: 24px 22px 28px; }
.vgf-informe {
    max-width: 46rem;
    color: var(--vgf-text);
    font-size: .96rem;
    line-height: 1.65;
}
.vgf-informe h3 {
    margin: 20px 0 9px;
    color: var(--vgf-heading);
    font-family: var(--vgf-serif);
    font-size: 1.38rem;
    font-weight: 650;
    line-height: 1.08;
}
.vgf-informe h3:first-child { margin-top: 0; }
.vgf-informe p { margin: 0 0 13px; }
.vgf-informe ul {
    display: grid;
    gap: 7px;
    margin: 0 0 14px;
    padding: 0;
    list-style: none;
}
.vgf-informe li {
    position: relative;
    padding-left: 22px;
}
.vgf-informe li::before {
    content: "";
    position: absolute;
    left: 1px;
    top: .7em;
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: var(--vgf-gold);
    box-shadow: 0 0 0 4px var(--vgf-gold-soft);
}
.vgf-informe strong { color: var(--vgf-heading); font-weight: 650; }
.vgf-vacio {
    display: grid;
    justify-items: center;
    gap: 8px;
    padding: 48px 18px;
    text-align: center;
    color: var(--vgf-muted);
    background: var(--vgf-ivory-2);
}
.vgf-vacio .ico {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: var(--vgf-gold-soft);
    color: var(--vgf-gold-ink);
    border: 1px solid var(--vgf-gold-line);
    font-size: 1.25rem;
}
.vgf-vacio strong { color: var(--vgf-heading); font-size: 1rem; font-weight: 650; }
.vgf-vacio p { max-width: 34rem; margin: 0; font-size: .88rem; line-height: 1.55; }

@media (max-width: 900px) {
    .vgf-hero-section { grid-template-columns: minmax(0, 1fr); align-items: start; gap: 12px; }
    .vgf-status-pill { justify-self: start; }
    .vgf-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
    .vgf { padding: 14px 12px 34px; }
    .vgf-summary { grid-template-columns: 1fr; }
    .vgf-title { font-size: 2rem; }
    .vgf-title-lockup { grid-template-columns: 42px minmax(0, 1fr); column-gap: 12px; }
    .vgf-hero-icon { width: 42px; height: 42px; border-radius: 14px; font-size: 1rem; }
    .vgf-toolbar, .vgf-toolbar form, .vgf-btn { width: 100%; }
    .vgf-card-body { padding: 20px 16px; }
}
@media (prefers-reduced-motion: reduce) {
    .vgf *, .vgf *::before, .vgf *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
    }
}
</style>

<div class="vgf">
    <div class="vgf-shell">
        <section class="vgf-hero-section">
            <div class="vgf-title-lockup">
                <div class="vgf-hero-icon" aria-hidden="true">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <p class="vgf-kicker">Auditor&iacute;a continua</p>
                    <h1 class="vgf-title">Vigilancia financiera</h1>
                    <p class="vgf-subtitle">Tus libros de Cuentas por Cobrar, Cuentas por Pagar y Caja se cruzan autom&aacute;ticamente. Si algo no cuadra, el analista te explica qu&eacute; significa y d&oacute;nde revisarlo.</p>
                </div>
            </div>
            <span class="vgf-status-pill is-<?= $vgfSafe($semaforo) ?>">
                <i class="fa-solid <?= $vgfSafe($semaforoIcono) ?>" aria-hidden="true"></i>
                <?= $vgfSafe($semaforoTexto) ?>
            </span>
        </section>

        <?php if ($mensaje = get_mensaje()): ?>
            <?php
                $tipo = (string) ($mensaje['tipo'] ?? 'info');
                $tipo = in_array($tipo, ['success', 'error', 'info'], true) ? $tipo : 'info';
            ?>
            <div class="vgf-alert is-<?= $vgfSafe($tipo) ?>">
                <?= $mensaje['texto'] ?? '' ?>
            </div>
        <?php endif; ?>

        <section class="vgf-summary" aria-label="Resumen de la vigilancia">
            <div class="vgf-summary-item">
                <div class="vgf-summary-label">Estado de libros</div>
                <div class="vgf-summary-value is-<?= $vgfSafe($semaforo) ?>"><?= $vgfSafe($semaforoTexto) ?></div>
            </div>
            <div class="vgf-summary-item">
                <div class="vgf-summary-label">Errores</div>
                <div class="vgf-summary-value <?= $errores > 0 ? 'is-rojo' : 'is-verde' ?>"><?= $errores ?></div>
            </div>
            <div class="vgf-summary-item">
                <div class="vgf-summary-label">Avisos</div>
                <div class="vgf-summary-value <?= $warnings > 0 ? 'is-amarillo' : 'is-verde' ?>"><?= $warnings ?></div>
            </div>
            <div class="vgf-summary-item">
                <div class="vgf-summary-label">Fuente del informe</div>
                <div class="vgf-summary-value"><?= $vgfSafe($fuenteTexto) ?></div>
            </div>
        </section>

        <section class="vgf-panel">
            <div class="vgf-panel-head">
                <div>
                    <h2 class="vgf-panel-title">Revisi&oacute;n de hoy</h2>
                    <p class="vgf-panel-sub">El informe se genera una vez al d&iacute;a y se guarda. Reg&eacute;neralo si registraste movimientos nuevos.</p>
                </div>
            </div>
            <div class="vgf-toolbar">
                <form method="POST" action="<?= url('ia/vigilancia-financiera/regenerar') ?>"
                      onsubmit="var b=this.querySelector('button'); b.disabled=true; b.textContent='Analizando...'; return true;">
                    <?= csrf_field() ?>
                    <button type="submit" class="vgf-btn">
                        <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                        <?= $hayInforme ? 'Regenerar con datos actuales' : 'Generar informe de hoy' ?>
                    </button>
                </form>
                <a class="vgf-btn sec" href="<?= url('operacion/conciliacion-financiera') ?>">
                    <i class="fa-solid fa-table-list" aria-hidden="true"></i>
                    Ver conciliaci&oacute;n completa
                </a>
            </div>
        </section>

        <article class="vgf-reading">
            <div class="vgf-reading-head">
                <p class="vgf-meta">
                    <span class="vgf-chip <?= $esPlantilla ? 'is-plantilla' : '' ?>">
                        <i class="fa-solid <?= $esPlantilla ? 'fa-circle-check' : 'fa-user-secret' ?>" aria-hidden="true"></i>
                        <?= $esPlantilla ? 'Verificaci&oacute;n autom&aacute;tica' : 'Analista IA' ?>
                    </span>
                    <?php if ($generadoHumano !== ''): ?>
                        <span>Generado el <?= $vgfSafe($generadoHumano) ?></span>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($hayInforme): ?>
                <div class="vgf-card-body">
                    <div class="vgf-informe"><?= $vgfMarkdown($informe) ?></div>
                </div>
            <?php else: ?>
                <div class="vgf-vacio">
                    <div class="ico" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></div>
                    <strong>A&uacute;n no hay informe de vigilancia</strong>
                    <p><?= $vgfSafe($resultado['message'] ?? 'Usa el boton "Generar informe" para revisar tus libros.') ?></p>
                </div>
            <?php endif; ?>
        </article>
    </div>
</div>
