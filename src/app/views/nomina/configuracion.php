<?php
/**
 * Nomina core - configuracion por negocio (Fase 1).
 * Pantalla propia (no vive en /configuracion global) porque la politica de
 * nomina es sensible: solo roles con nomina.configurar llegan aqui.
 */
$nomConfig = is_array($config ?? null) ? $config : [];
$nomModos = is_array($modos ?? null) ? $modos : ['simplificada', 'hibrida', 'legal'];
$nomRedondeos = is_array($redondeos ?? null) ? $redondeos : ['centavos', 'pesos'];

$nomModoActual = (string) ($nomConfig['modo'] ?? 'simplificada');
$nomRedondeoActual = (string) ($nomConfig['redondeo'] ?? 'centavos');

$nomModoMeta = [
    'simplificada' => [
        'titulo' => 'Operativa simplificada',
        'desc' => 'Control interno de empleados, pagos e incidencias. No es cálculo fiscal oficial.',
        'icono' => 'fa-clipboard-list',
    ],
    'hibrida' => [
        'titulo' => 'Híbrida',
        'desc' => 'Control interno en el sistema + reportes y exportaciones para tu contador externo.',
        'icono' => 'fa-scale-balanced',
    ],
    'legal' => [
        'titulo' => 'Legal / fiscal',
        'desc' => 'Cálculo conforme a reglas legales mexicanas versionadas por año (ISR, IMSS, UMA).',
        'icono' => 'fa-landmark',
    ],
];

$back_arrow_href = url('nomina');
include APP_PATH . '/views/partials/back_arrow.php';
?>
<style>
.nomina-config-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F6F2EA);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text);
    max-width: 860px;
    margin: 0 auto;
    padding: 4px 4px 40px;
}
.nomina-config-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-config-page .nom-title { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 28px; margin: 2px 0 4px; font-weight: 600; }
.nomina-config-page .nom-subtitle { color: var(--nom-muted); font-size: 13.5px; margin: 0 0 20px; }
.nomina-config-page .nom-section {
    background: var(--nom-card); border: 1px solid var(--nom-border);
    border-radius: 16px; padding: 18px 20px; margin-bottom: 16px;
}
.nomina-config-page .nom-section h2 { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 19px; margin: 0 0 4px; font-weight: 600; }
.nomina-config-page .nom-section .nom-hint { font-size: 12.5px; color: var(--nom-muted); margin: 0 0 14px; }
.nomina-config-page .nom-modos { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
@media (max-width: 768px) { .nomina-config-page .nom-modos { grid-template-columns: 1fr; } }
.nomina-config-page .nom-modo {
    position: relative; border: 1.5px solid var(--nom-border); border-radius: 14px;
    padding: 14px; cursor: pointer; background: transparent;
    transition: border-color .15s ease, background .15s ease;
    display: block;
}
.nomina-config-page .nom-modo:hover { border-color: var(--nom-gold); }
.nomina-config-page .nom-modo input { position: absolute; opacity: 0; pointer-events: none; }
.nomina-config-page .nom-modo.seleccionado {
    border-color: var(--nom-brand);
    background: color-mix(in srgb, var(--nom-brand) 6%, transparent);
}
.nomina-config-page .nom-modo i { color: var(--nom-gold); font-size: 16px; }
.nomina-config-page .nom-modo strong { display: block; font-size: 14px; margin: 6px 0 4px; }
.nomina-config-page .nom-modo span { font-size: 12.5px; color: var(--nom-muted); line-height: 1.45; display: block; }
.nomina-config-page .nom-field-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
@media (max-width: 640px) { .nomina-config-page .nom-field-row { grid-template-columns: 1fr; } }
.nomina-config-page label.nom-label { display: block; font-size: 12.5px; font-weight: 700; margin-bottom: 6px; }
.nomina-config-page select.nom-input {
    width: 100%; border: 1px solid var(--nom-border); border-radius: 10px;
    padding: 10px 12px; font-size: 16px; background: #fff; color: var(--nom-text);
}
.nomina-config-page .nom-switches { display: flex; flex-direction: column; gap: 4px; }
.nomina-config-page .nom-switch {
    display: flex; align-items: flex-start; gap: 10px; padding: 10px 4px;
    border-bottom: 1px dashed var(--nom-border); cursor: pointer;
}
.nomina-config-page .nom-switch:last-child { border-bottom: 0; }
.nomina-config-page .nom-switch input { width: 18px; height: 18px; margin-top: 2px; accent-color: var(--nom-brand); flex: 0 0 auto; }
.nomina-config-page .nom-switch strong { font-size: 13.5px; display: block; }
.nomina-config-page .nom-switch span { font-size: 12.5px; color: var(--nom-muted); }
.nomina-config-page .nom-aviso {
    border: 1px solid color-mix(in srgb, var(--nom-gold) 45%, var(--nom-border));
    background: color-mix(in srgb, var(--nom-gold) 8%, var(--nom-card));
    border-radius: 12px; padding: 12px 14px; font-size: 12.5px; color: var(--nom-muted);
    margin-bottom: 16px;
}
.nomina-config-page .nom-actions { display: flex; gap: 10px; justify-content: flex-end; }
.nomina-config-page .nom-btn {
    display: inline-flex; align-items: center; gap: 8px;
    border: 1px solid var(--nom-border); border-radius: 12px;
    padding: 10px 18px; font-size: 13.5px; font-weight: 600; text-decoration: none;
    color: var(--nom-text); background: var(--nom-card); cursor: pointer;
}
.nomina-config-page .nom-btn-primary { background: var(--nom-brand); border-color: var(--nom-brand); color: #fff; }
</style>

<div class="nomina-config-page">

    <p class="nom-kicker">Nómina</p>
    <h1 class="nom-title">Configuración de nómina</h1>
    <p class="nom-subtitle">Define cómo maneja la nómina este negocio. Cada cambio queda registrado en auditoría.</p>

    <form method="POST" action="<?= url('nomina/configuracion') ?>">
        <?= csrf_field() ?>

        <div class="nom-section">
            <h2>Modo de nómina</h2>
            <p class="nom-hint">El modo define qué tan profundo calcula el sistema. Puedes cambiarlo después.</p>
            <div class="nom-modos" data-ms-stagger>
                <?php foreach ($nomModos as $nomModoOpcion): ?>
                    <?php $meta = $nomModoMeta[$nomModoOpcion] ?? null; if (!$meta) { continue; } ?>
                    <label class="nom-modo <?= $nomModoActual === $nomModoOpcion ? 'seleccionado' : '' ?>">
                        <input type="radio" name="modo" value="<?= htmlspecialchars($nomModoOpcion) ?>" <?= $nomModoActual === $nomModoOpcion ? 'checked' : '' ?>>
                        <i class="fas <?= $meta['icono'] ?>"></i>
                        <strong><?= htmlspecialchars($meta['titulo']) ?></strong>
                        <span><?= htmlspecialchars($meta['desc']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="nom-aviso">
            <i class="fas fa-circle-info"></i>
            Los modos <strong>híbrido</strong> y <strong>legal/fiscal</strong> activan sus funciones conforme se liberen las
            siguientes fases del módulo. Hoy el sistema opera la nómina como control interno; el modo elegido
            queda guardado y se respetará al habilitarse cada función. El modo simplificado no constituye
            cálculo fiscal oficial.
        </div>

        <div class="nom-section">
            <h2>Reglas y redondeo</h2>
            <p class="nom-hint">Aplican a los cálculos y montos que muestre la nómina.</p>
            <div class="nom-field-row">
                <div>
                    <label class="nom-label" for="nom-pais">País de reglas legales</label>
                    <select id="nom-pais" name="pais" class="nom-input">
                        <option value="MX" selected>México (MX)</option>
                    </select>
                </div>
                <div>
                    <label class="nom-label" for="nom-redondeo">Redondeo de montos</label>
                    <select id="nom-redondeo" name="redondeo" class="nom-input">
                        <?php foreach ($nomRedondeos as $nomRedondeoOpcion): ?>
                        <option value="<?= htmlspecialchars($nomRedondeoOpcion) ?>" <?= $nomRedondeoActual === $nomRedondeoOpcion ? 'selected' : '' ?>>
                            <?= $nomRedondeoOpcion === 'pesos' ? 'A pesos enteros' : 'A centavos (2 decimales)' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="nom-section">
            <h2>Políticas internas</h2>
            <p class="nom-hint">Qué se permite capturar y cómo se controla el cierre.</p>
            <div class="nom-switches">
                <label class="nom-switch">
                    <input type="checkbox" name="permitir_horas_extra" value="1" <?= !empty($nomConfig['permitir_horas_extra']) ? 'checked' : '' ?>>
                    <span><strong>Permitir horas extra</strong><span>Se pueden registrar horas extra como incidencia del periodo.</span></span>
                </label>
                <label class="nom-switch">
                    <input type="checkbox" name="permitir_descuentos_manuales" value="1" <?= !empty($nomConfig['permitir_descuentos_manuales']) ? 'checked' : '' ?>>
                    <span><strong>Permitir descuentos manuales</strong><span>Usuarios autorizados pueden aplicar descuentos no catalogados.</span></span>
                </label>
                <label class="nom-switch">
                    <input type="checkbox" name="requiere_aprobacion_cierre" value="1" <?= !empty($nomConfig['requiere_aprobacion_cierre']) ? 'checked' : '' ?>>
                    <span><strong>El cierre requiere aprobación</strong><span>Un periodo cerrado debe aprobarse en un segundo paso antes de pagarse.</span></span>
                </label>
                <label class="nom-switch">
                    <input type="checkbox" name="permitir_reapertura" value="1" <?= !empty($nomConfig['permitir_reapertura']) ? 'checked' : '' ?>>
                    <span><strong>Permitir reabrir periodos cerrados</strong><span>Siempre exigirá motivo, usuario responsable y registro en auditoría.</span></span>
                </label>
            </div>
        </div>

        <div class="nom-actions">
            <a href="<?= url('nomina') ?>" class="nom-btn ms-pressable">Cancelar</a>
            <button type="submit" class="nom-btn nom-btn-primary ms-pressable">
                <i class="fas fa-check"></i> Guardar configuración
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    var tarjetas = document.querySelectorAll('.nomina-config-page .nom-modo');
    tarjetas.forEach(function (tarjeta) {
        var radio = tarjeta.querySelector('input[type="radio"]');
        if (!radio) { return; }
        radio.addEventListener('change', function () {
            tarjetas.forEach(function (t) { t.classList.remove('seleccionado'); });
            if (radio.checked) { tarjeta.classList.add('seleccionado'); }
        });
    });
})();
</script>
