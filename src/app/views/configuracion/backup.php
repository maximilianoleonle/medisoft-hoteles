<?php
/**
 * Vista de respaldos
 */
?>

<h1>Respaldos del Sistema</h1>

<div>
    <a href="<?= back_url('configuracion') ?>">Volver a Configuración</a>
</div>

<div>
    <form method="POST" action="<?= url('configuracion/backup/create') ?>" style="display: inline;" data-backup-confirm="1">
        <?= csrf_field() ?>
        <button type="submit" data-default-label="Crear Respaldo Manual">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-backup-confirm]');
    if (!form) {
        return;
    }

    var button = form.querySelector('button[type="submit"]');
    var resetTimer = null;

    function showBackupNotice(message) {
        var notice = document.querySelector('[data-backup-confirm-notice]');
        if (!notice) {
            notice = document.createElement('div');
            notice.setAttribute('data-backup-confirm-notice', '1');
            notice.setAttribute('role', 'status');
            notice.style.position = 'fixed';
            notice.style.right = '24px';
            notice.style.bottom = '24px';
            notice.style.zIndex = '9999';
            notice.style.maxWidth = '320px';
            notice.style.padding = '12px 14px';
            notice.style.border = '1px solid rgba(180, 128, 32, .32)';
            notice.style.borderRadius = '14px';
            notice.style.background = 'rgba(255, 250, 239, .98)';
            notice.style.color = '#7a4b0d';
            notice.style.boxShadow = '0 16px 36px rgba(30, 24, 18, .14)';
            notice.style.fontSize = '14px';
            notice.style.fontWeight = '700';
            document.body.appendChild(notice);
        }

        notice.textContent = message;
        notice.style.display = 'block';
        window.clearTimeout(notice._hideTimer);
        notice._hideTimer = window.setTimeout(function () {
            notice.style.display = 'none';
        }, 3200);
    }

    form.addEventListener('submit', function (event) {
        if (form.dataset.confirmedBackup === '1') {
            return;
        }

        event.preventDefault();
        form.dataset.confirmedBackup = '1';

        if (button) {
            button.textContent = 'Confirmar respaldo';
            button.classList.add('is-confirming');
        }

        showBackupNotice('Haz clic otra vez para crear el respaldo manual.');
        window.clearTimeout(resetTimer);
        resetTimer = window.setTimeout(function () {
            delete form.dataset.confirmedBackup;
            if (button) {
                button.textContent = button.dataset.defaultLabel || 'Crear Respaldo Manual';
                button.classList.remove('is-confirming');
            }
        }, 6000);
    });
});
</script>
