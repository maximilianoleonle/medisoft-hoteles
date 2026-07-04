<?php
/**
 * Bitacora de auditoria (bloque auditoria). Solo lectura.
 */
$eventos = $eventos ?? [];
$usuarios = $usuarios ?? [];
$modulos = $modulos ?? [];
$filtros = $filtros ?? ['usuario' => 0, 'modulo' => '', 'desde' => '', 'hasta' => ''];

$auSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
// Traduccion amable de acciones tecnicas comunes.
$auAccion = static function ($accion) {
    $map = [
        'cancelar' => 'Cancelacion', 'eliminar' => 'Eliminacion', 'guardar' => 'Guardado',
        'crear' => 'Creacion', 'actualizar' => 'Actualizacion', 'revertir' => 'Reversion',
        'checkin' => 'Check-in', 'checkout' => 'Check-out', 'conciliar' => 'Conciliacion',
        'config' => 'Configuracion', 'guardarConfiguracion' => 'Configuracion',
    ];
    return $map[$accion] ?? ucfirst($accion);
};
$auEsSensible = static function ($accion) {
    return (bool) preg_match('/cancelar|eliminar|revertir|reembols|desactivar/i', (string) $accion);
};
?>

<style>
.au { max-width: 1080px; margin: 0 auto; padding: 18px 16px 40px; font-size: .92rem; }
.au h1 { margin: 0 0 4px; font-size: 1.35rem; color: var(--brand-primary, #1B2746); }
.au .sub { margin: 0 0 16px; color: #6B7486; }
.au-card { background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E6E2D8); border-radius: 14px; overflow: hidden; margin-bottom: 16px; }
.au-filtros { padding: 14px 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; align-items: end; }
.au-filtros label { display: block; font-size: .74rem; font-weight: 700; color: #55607A; margin-bottom: 4px; }
.au-filtros input, .au-filtros select { width: 100%; min-height: 40px; border: 1px solid #D8D4C9; border-radius: 8px; padding: 0 10px; font-size: .88rem; }
.au-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: 0 16px; border: 0; border-radius: 8px; cursor: pointer; background: var(--brand-primary, #1B2746); color: #fff; font-size: .84rem; font-weight: 700; text-decoration: none; }
.au-btn.sec { background: #fff; color: var(--brand-primary, #1B2746); border: 1px solid #D8D4C9; }
.au table { width: 100%; border-collapse: collapse; }
.au th { padding: 10px 12px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8A93A6; border-bottom: 1px solid #EDE9DF; white-space: nowrap; }
.au td { padding: 10px 12px; border-bottom: 1px solid #F2EFE7; vertical-align: middle; }
.au-vacio { padding: 30px 16px; text-align: center; color: #8A93A6; }
.au-pill { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; background: rgba(37,99,235,.08); color: #1D4ED8; }
.au-pill.rojo { background: rgba(220,38,38,.1); color: #B91C1C; }
.au-ruta { font-family: ui-monospace, monospace; font-size: .76rem; color: #8A93A6; word-break: break-all; }
</style>

<div class="au">
    <h1>Bitácora de auditoría</h1>
    <p class="sub">Cada acción que un usuario ejecutó en el sistema: quién, qué y cuándo. El registro es automático y no se puede editar ni borrar.</p>

    <div class="au-card">
        <form class="au-filtros" method="GET" action="<?= url('auditoria') ?>">
            <div>
                <label for="au-usuario">Usuario</label>
                <select id="au-usuario" name="usuario">
                    <option value="0">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= (int) $u['usuario_id'] ?>" <?= (int) $filtros['usuario'] === (int) $u['usuario_id'] ? 'selected' : '' ?>>
                            <?= $auSafe($u['usuario_nombre'] ?: ('Usuario #' . (int) $u['usuario_id'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="au-modulo">Módulo</label>
                <select id="au-modulo" name="modulo">
                    <option value="">Todos</option>
                    <?php foreach ($modulos as $m): ?>
                        <option value="<?= $auSafe($m) ?>" <?= $filtros['modulo'] === $m ? 'selected' : '' ?>><?= $auSafe($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="au-desde">Desde</label>
                <input type="date" id="au-desde" name="desde" value="<?= $auSafe($filtros['desde']) ?>">
            </div>
            <div>
                <label for="au-hasta">Hasta</label>
                <input type="date" id="au-hasta" name="hasta" value="<?= $auSafe($filtros['hasta']) ?>">
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="au-btn">Filtrar</button>
                <a href="<?= url('auditoria') ?>" class="au-btn sec">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="au-card">
        <table>
            <thead>
                <tr>
                    <th>Cuándo</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Detalle</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($eventos)): ?>
                <tr><td colspan="5" class="au-vacio">
                    Sin eventos con estos filtros. La bitácora registra las acciones a partir de que el bloque se activa.
                </td></tr>
            <?php else: ?>
                <?php foreach ($eventos as $ev): ?>
                    <tr>
                        <td style="white-space:nowrap;">
                            <div style="font-weight:600;"><?= $auSafe(date('d/m/Y', strtotime((string) $ev['created_at']))) ?></div>
                            <div style="font-size:.76rem;color:#8A93A6;"><?= $auSafe(date('H:i:s', strtotime((string) $ev['created_at']))) ?></div>
                        </td>
                        <td>
                            <div style="font-weight:600;"><?= $auSafe($ev['usuario_nombre'] ?: ('Usuario #' . (int) $ev['usuario_id'])) ?></div>
                            <?php if (!empty($ev['ip'])): ?>
                                <div style="font-size:.74rem;color:#8A93A6;">IP <?= $auSafe($ev['ip']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="au-pill <?= $auEsSensible($ev['accion']) ? 'rojo' : '' ?>"><?= $auSafe($auAccion((string) $ev['accion'])) ?></span>
                        </td>
                        <td style="font-weight:600;"><?= $auSafe($ev['modulo']) ?><?= $ev['entidad_id'] !== null ? ' <span style="color:#8A93A6;font-weight:400;">#' . (int) $ev['entidad_id'] . '</span>' : '' ?></td>
                        <td class="au-ruta"><?= $auSafe($ev['ruta']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p style="font-size:.76rem;color:#9AA0AE;">Se muestran los 300 eventos más recientes del filtro. Las acciones marcadas en rojo (cancelaciones, eliminaciones, reversiones) son las más sensibles.</p>
</div>
