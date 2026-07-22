<?php
/**
 * Vista de respaldos
 */
?>

<h1>Respaldos del sistema</h1>

<div>
    <a href="<?= back_url('configuracion') ?>">Volver a Configuración</a>
</div>

<div>
    <form method="POST" action="<?= url('configuracion/backup/create') ?>" style="display: inline;" data-backup-confirm="1">
        <?= csrf_field() ?>
        <button type="submit" data-default-label="Crear respaldo manual">
            Crear respaldo manual
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-backup-confirm]');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        if (form.dataset.confirmedBackup === '1') {
            delete form.dataset.confirmedBackup;
            return;
        }

        event.preventDefault();
        msConfirm({
            type: 'info',
            icon: 'check',
            title: '¿Crear respaldo manual?',
            msg: 'Se generará un respaldo de la información del hotel en el servidor.',
            confirmLabel: 'Crear respaldo'
        }).then(function (ok) {
            if (!ok) return;
            form.dataset.confirmedBackup = '1';
            if (form.requestSubmit) form.requestSubmit();
            else form.submit();
        });
    });
});
</script>
