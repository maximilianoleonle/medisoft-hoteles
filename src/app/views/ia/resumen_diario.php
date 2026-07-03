<?php
/**
 * Asesor IA: resumen gerencial diario narrado (bloque ia_ejecutiva).
 * Vista interna con layout estandar.
 */
$fecha = $fecha ?? date('Y-m-d');
$resultado = $resultado ?? ['success' => false, 'message' => 'Sin datos.'];
$configurado = $configurado ?? false;

$iaSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

/**
 * Render minimo de Markdown a HTML seguro: escapa todo primero y luego
 * aplica solo negritas, encabezados y listas. Sin HTML libre del modelo.
 */
$iaMarkdown = static function ($texto) {
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
?>

<style>
.iav { max-width: 860px; margin: 0 auto; padding: 18px 16px 44px; }
.iav h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.iav .sub { margin: 0 0 16px; color: #6B7486; font-size: .92rem; }
.iav-bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin-bottom: 16px; }
.iav-bar form { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; }
.iav-field { display: grid; gap: 4px; }
.iav-field label { font-size: .76rem; font-weight: 700; color: #55607A; }
.iav-field input { min-height: 42px; border: 1px solid #D8D4C9; border-radius: 9px; padding: 0 11px; font-size: .95rem; }
.iav-btn { min-height: 42px; padding: 0 16px; border: 0; border-radius: 9px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .88rem; font-weight: 700; }
.iav-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 18%, #E0DCD2); }
.iav-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 16px; padding: 24px 26px; box-shadow: 0 16px 36px -30px rgba(20,28,45,.5); }
.iav-card .meta { margin: 0 0 14px; font-size: .78rem; color: #8A93A6; display: flex; gap: 8px; align-items: center; }
.iav-card .meta .chip { background: color-mix(in srgb, var(--brand-accent, #BD9441) 12%, #FBF9F3); color: color-mix(in srgb, var(--brand-accent, #BD9441) 85%, #6d520f); border-radius: 999px; padding: 3px 10px; font-weight: 700; }
.iav-resumen { color: #2A3242; font-size: .96rem; line-height: 1.65; }
.iav-resumen h3 { margin: 18px 0 8px; font-size: 1.02rem; color: var(--brand-primary, #1B2746); }
.iav-resumen p { margin: 0 0 12px; }
.iav-resumen ul { margin: 0 0 12px; padding-left: 22px; }
.iav-resumen li { margin-bottom: 6px; }
.iav-vacio { text-align: center; padding: 36px 16px; color: #77809A; }
.iav-vacio .ico { font-size: 2rem; margin-bottom: 8px; }
</style>

<div class="iav">
    <h1>Asesor IA</h1>
    <p class="sub">Tu resumen gerencial del dia, narrado y accionable. Generado a partir de los datos reales del hotel.</p>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:14px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="iav-bar">
        <form method="GET" action="<?= url('ia/resumen-diario') ?>">
            <div class="iav-field">
                <label for="iav-fecha">Dia del resumen</label>
                <input type="date" id="iav-fecha" name="fecha" value="<?= $iaSafe($fecha) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <button type="submit" class="iav-btn sec">Ver resumen</button>
        </form>
        <form method="POST" action="<?= url('ia/regenerar-resumen') ?>"
              onsubmit="var b=this.querySelector('button'); b.disabled=true; b.textContent='Generando...'; return true;">
            <?= csrf_field() ?>
            <input type="hidden" name="fecha" value="<?= $iaSafe($fecha) ?>">
            <button type="submit" class="iav-btn" <?= $configurado ? '' : 'disabled title="El asistente IA no esta configurado en el servidor."' ?>>
                <?= empty($resultado['success']) ? 'Generar resumen de hoy' : 'Regenerar con datos actuales' ?>
            </button>
        </form>
    </div>

    <div class="iav-card">
        <?php if (!empty($resultado['success'])): ?>
            <p class="meta">
                <span class="chip">✦ Asesor IA</span>
                <span>Resumen del <?= $iaSafe(date('d/m/Y', strtotime($fecha))) ?></span>
                <?php if (!empty($resultado['generado_en'])): ?>
                    <span>· generado el <?= $iaSafe(date('d/m/Y H:i', strtotime((string) $resultado['generado_en']))) ?></span>
                <?php endif; ?>
            </p>
            <div class="iav-resumen"><?= $iaMarkdown($resultado['resumen'] ?? '') ?></div>
        <?php else: ?>
            <div class="iav-vacio">
                <div class="ico" aria-hidden="true">✦</div>
                <p style="font-weight:700;color:#55607A;margin:0 0 6px;">Aun no hay resumen para este dia</p>
                <p style="margin:0;font-size:.88rem;"><?= $iaSafe($resultado['message'] ?? 'Usa el boton "Generar resumen" para crear el briefing del dia.') ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
