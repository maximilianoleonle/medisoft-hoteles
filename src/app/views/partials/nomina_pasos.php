<?php
/**
 * Barra de viaje del ciclo de nómina: ① Incidencias ② Cerrar ③ Aprobar ④ Pagar.
 * Muestra dónde está parado el usuario y qué falta, según el estado REAL del
 * periodo. Solo presentación: no toca lógica ni rutas.
 *
 * Uso (antes del include):
 *   $nomina_pasos_actual = 1|2|3|4;   // estación en curso
 *   $nomina_pasos_completado = bool;  // true = ciclo terminado (todo pagado)
 *   include APP_PATH . '/views/partials/nomina_pasos.php';
 */
$npasoActual = (int) ($nomina_pasos_actual ?? 1);
$npasoCompletado = !empty($nomina_pasos_completado);
$npasos = [
    1 => ['icono' => 'fa-clipboard-list', 'label' => 'Incidencias'],
    2 => ['icono' => 'fa-lock', 'label' => 'Cerrar'],
    3 => ['icono' => 'fa-check-double', 'label' => 'Aprobar'],
    4 => ['icono' => 'fa-hand-holding-dollar', 'label' => 'Pagar'],
];
?>
<?php if (!defined('MS_NOMINA_PASOS_CSS')): define('MS_NOMINA_PASOS_CSS', true); ?>
<style>
.ms-npasos {
    display: flex; align-items: center; flex-wrap: wrap; gap: 4px;
    margin: 0 0 14px; padding: 10px 12px; border-radius: 12px;
    background: color-mix(in srgb, var(--brand-surface, #F5F5F7) 55%, #ffffff);
    border: 1px solid var(--brand-border, #e3dccd);
    font-size: 12px; font-weight: 600;
}
.ms-npasos .ms-npaso { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; color: var(--brand-muted, #6d675e); white-space: nowrap; }
.ms-npasos .ms-npaso i { font-size: 11px; }
.ms-npasos .ms-npaso.is-done { color: #2e7d32; }
.ms-npasos .ms-npaso.is-done i::before { content: "\f00c"; }
.ms-npasos .ms-npaso.is-activa {
    background: var(--brand-primary, #1B2746); color: #fff;
    box-shadow: 0 6px 14px -8px color-mix(in srgb, var(--brand-primary, #1B2746) 70%, transparent);
}
.ms-npasos .ms-npaso-sep { color: var(--brand-border, #e3dccd); font-size: 11px; }
.ms-npasos .ms-npasos-hint { margin-left: auto; color: var(--brand-muted, #6d675e); font-weight: 600; font-size: 11.5px; }
@media (max-width: 640px) {
    .ms-npasos { overflow-x: auto; flex-wrap: nowrap; scrollbar-width: none; }
    .ms-npasos::-webkit-scrollbar { display: none; }
    .ms-npasos .ms-npasos-hint { display: none; }
}
</style>
<?php endif; ?>
<div class="ms-npasos" role="group" aria-label="Pasos del ciclo de nómina">
    <?php foreach ($npasos as $npasoNum => $npaso): ?>
        <?php
        $npasoClase = '';
        if ($npasoCompletado || $npasoNum < $npasoActual) {
            $npasoClase = 'is-done';
        } elseif ($npasoNum === $npasoActual) {
            $npasoClase = 'is-activa';
        }
        ?>
        <span class="ms-npaso <?= $npasoClase ?>">
            <i class="fas <?= $npaso['icono'] ?>" aria-hidden="true"></i>
            <?= $npasoNum ?>. <?= $npaso['label'] ?>
        </span>
        <?php if ($npasoNum < 4): ?><span class="ms-npaso-sep"><i class="fas fa-chevron-right"></i></span><?php endif; ?>
    <?php endforeach; ?>
    <span class="ms-npasos-hint">
        <?= $npasoCompletado ? 'Ciclo terminado: todo pagado.' : 'Estás en el paso ' . $npasoActual . ' de 4.' ?>
    </span>
</div>
<?php unset($nomina_pasos_actual, $nomina_pasos_completado, $npasoActual, $npasoCompletado, $npasos, $npasoNum, $npaso, $npasoClase); ?>
