<?php
/**
 * Vista de respaldos
 */
?>

<h1>Respaldos del Sistema</h1>

<div>
    <a href="<?= url('configuracion') ?>">Volver a Configuración</a>
</div>

<div>
    <form method="POST" action="<?= url('configuracion/backup/create') ?>" style="display: inline;">
        <?= csrf_field() ?>
        <button type="submit" onclick="return confirm('¿Crear respaldo manual ahora?')">
            Crear Respaldo Manual
        </button>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Archivo</th>
            <th>Fecha</th>
            <th>Tamaño</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($backups)): ?>
            <tr>
                <td colspan="4">No hay respaldos disponibles</td>
            </tr>
        <?php else: ?>
            <?php foreach ($backups as $backup): ?>
                <tr>
                    <td><?= htmlspecialchars($backup['archivo']) ?></td>
                    <td><?= format_datetime($backup['fecha']) ?></td>
                    <td><?= $backup['tamano'] ?></td>
                    <td>
                        <a href="<?= url('configuracion/backup/descargar?archivo=' . urlencode($backup['archivo'])) ?>">
                            Descargar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div>
    <strong>Nota:</strong> Los respaldos se almacenan en el servidor y se eliminan automáticamente según la configuración establecida.
</div>
