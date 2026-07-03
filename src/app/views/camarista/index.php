<?php
/**
 * Tablero movil de limpieza (bloque camarista). Tarjetas grandes por
 * habitacion con accion de un toque; primero lo que hay que limpiar.
 */
$habitaciones = $habitaciones ?? [];
$salidasHoy = $salidasHoy ?? [];

$camSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

// Orden de trabajo: en limpieza primero, luego salidas de hoy, luego el resto.
$prioridad = static function ($hab) use ($salidasHoy) {
    $id = (int) $hab['id'];
    if ($hab['estado'] === 'limpieza') return 0;
    if (isset($salidasHoy[$id]) && $hab['estado'] !== 'disponible') return 1;
    if ($hab['estado'] === 'ocupada') return 2;
    if ($hab['estado'] === 'disponible') return 3;
    return 4; // mantenimiento
};
usort($habitaciones, static function ($a, $b) use ($prioridad) {
    $pa = $prioridad($a);
    $pb = $prioridad($b);
    if ($pa !== $pb) return $pa <=> $pb;
    return (int) $a['numero'] <=> (int) $b['numero'];
});

$porLimpiar = count(array_filter($habitaciones, static function ($h) {
    return $h['estado'] === 'limpieza';
}));
?>

<style>
.cam { max-width: 720px; margin: 0 auto; padding: 16px 14px 60px; }
.cam h1 { margin: 0 0 2px; font-size: 1.3rem; color: var(--brand-primary, #1B2746); }
.cam .sub { margin: 0 0 14px; color: #6B7486; font-size: .9rem; }
.cam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
.cam-card { background: #fff; border: 1px solid #E6E2D8; border-radius: 14px; padding: 14px; display: flex; flex-direction: column; gap: 10px; }
.cam-card.limpieza { border-color: rgba(245,158,11,.45); background: rgba(245,158,11,.05); }
.cam-card.salida { border-color: rgba(59,130,246,.4); }
.cam-num { font-size: 1.25rem; font-weight: 700; color: var(--brand-primary, #1B2746); }
.cam-tipo { font-size: .76rem; color: #8A93A6; }
.cam-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; width: fit-content; }
.cam-btn { min-height: 46px; border: 0; border-radius: 10px; cursor: pointer; font-size: .9rem; font-weight: 700; width: 100%; }
.cam-btn.limpia { background: #16A34A; color: #fff; }
.cam-btn.marcar { background: #fff; color: #92600A; border: 1.5px solid rgba(245,158,11,.5); }
.cam-resumen { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.cam-chip { padding: 8px 14px; border-radius: 10px; font-size: .82rem; font-weight: 700; background: #fff; border: 1px solid #E6E2D8; color: #6B7486; }
.cam-chip strong { color: var(--brand-primary, #1B2746); font-size: 1rem; }
</style>

<div class="cam">
    <h1>Limpieza</h1>
    <p class="sub">Toca el botón de cada habitación cuando termines. Las de arriba son las urgentes.</p>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div style="margin-bottom:12px;padding:11px 14px;border-radius:10px;font-size:.88rem;<?= $tipo === 'error' ? 'background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);' : 'background:rgba(22,163,74,.08);color:#15803D;border:1px solid rgba(22,163,74,.2);' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="cam-resumen">
        <span class="cam-chip">Por limpiar <strong><?= (int) $porLimpiar ?></strong></span>
        <span class="cam-chip">Salidas hoy <strong><?= count($salidasHoy) ?></strong></span>
        <span class="cam-chip">Habitaciones <strong><?= count($habitaciones) ?></strong></span>
    </div>

    <div class="cam-grid">
        <?php foreach ($habitaciones as $hab): ?>
            <?php
            $id = (int) $hab['id'];
            $estado = (string) $hab['estado'];
            $esSalidaHoy = isset($salidasHoy[$id]);
            $clases = 'cam-card' . ($estado === 'limpieza' ? ' limpieza' : ($esSalidaHoy && $estado !== 'disponible' ? ' salida' : ''));
            ?>
            <div class="<?= $clases ?>">
                <div>
                    <div class="cam-num">Hab <?= $camSafe($hab['numero']) ?></div>
                    <div class="cam-tipo"><?= $camSafe(ucfirst((string) $hab['tipo'])) ?><?= $hab['piso'] !== null && $hab['piso'] !== '' ? ' · Piso ' . $camSafe($hab['piso']) : '' ?></div>
                </div>

                <?php if ($estado === 'limpieza'): ?>
                    <span class="cam-badge" style="background:rgba(245,158,11,.14);color:#92600A;">🧹 Por limpiar</span>
                <?php elseif ($estado === 'ocupada'): ?>
                    <span class="cam-badge" style="background:rgba(59,130,246,.12);color:#1D4ED8;">🛏 Ocupada<?= $esSalidaHoy ? ' · sale hoy' : '' ?></span>
                <?php elseif ($estado === 'disponible'): ?>
                    <span class="cam-badge" style="background:rgba(22,163,74,.12);color:#15803D;">✨ Limpia</span>
                <?php else: ?>
                    <span class="cam-badge" style="background:rgba(100,116,139,.12);color:#64748B;">🔧 Mantenimiento</span>
                <?php endif; ?>

                <?php if ($estado === 'limpieza'): ?>
                    <form method="POST" action="<?= url('camarista/marcar/' . $id) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="estado" value="disponible">
                        <button type="submit" class="cam-btn limpia">✓ Ya quedó limpia</button>
                    </form>
                <?php elseif ($estado === 'disponible'): ?>
                    <form method="POST" action="<?= url('camarista/marcar/' . $id) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="estado" value="limpieza">
                        <button type="submit" class="cam-btn marcar">Marcar por limpiar</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
