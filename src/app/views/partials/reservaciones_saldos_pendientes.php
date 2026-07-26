<?php
$listaSaldos = isset($saldos_pendientes) && is_array($saldos_pendientes) ? $saldos_pendientes : [];
$resumenSaldos = isset($resumen_saldos_pendientes) && is_array($resumen_saldos_pendientes) ? $resumen_saldos_pendientes : [];
$cantidadSaldos = (int)($resumenSaldos['total'] ?? count($listaSaldos));
$montoSaldos = (float)($resumenSaldos['saldo_estimado'] ?? 0);
?>
<?php if ($cantidadSaldos > 0): ?>
<details id="saldos-pendientes" class="res-pending-balances no-print">
    <summary>
        <span><i class="fas fa-wallet" aria-hidden="true"></i> Saldos pendientes</span>
        <strong><?= $cantidadSaldos ?> reservaci<?= $cantidadSaldos === 1 ? '&oacute;n' : 'ones' ?> &middot; $<?= number_format($montoSaldos, 2) ?></strong>
    </summary>
    <div class="res-pending-balances__body">
        <p>Reservaciones con importe a&uacute;n no cubierto; incluye reservas sin anticipo y pagos parciales.</p>
        <div class="res-pending-balances__list">
            <?php foreach (array_slice($listaSaldos, 0, 12) as $saldo): ?>
                <?php
                $reservacionIdSaldo = (int)($saldo['reservacion_id'] ?? 0);
                $nombreHuespedSaldo = trim((string)($saldo['huesped_nombre'] ?? $saldo['nombre_completo'] ?? 'Hu&eacute;sped'));
                $montoPendienteSaldo = (float)($saldo['saldo_estimado'] ?? $saldo['saldo'] ?? 0);
                ?>
                <a href="<?= url('reservaciones/ver/' . $reservacionIdSaldo) ?>" class="res-pending-balances__item">
                    <span><strong><?= htmlspecialchars($nombreHuespedSaldo, ENT_QUOTES, 'UTF-8') ?></strong><small>Reservaci&oacute;n #<?= $reservacionIdSaldo ?></small></span>
                    <b>$<?= number_format($montoPendienteSaldo, 2) ?></b>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if (count($listaSaldos) > 12): ?><small>Se muestran las primeras 12 reservaciones pendientes.</small><?php endif; ?>
    </div>
</details>
<style>
.res-pending-balances{margin:0 0 1rem;border:1px solid color-mix(in srgb,var(--brand-primary,#1b2746) 16%,transparent);border-radius:14px;background:#fff;overflow:hidden}.res-pending-balances summary{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 1rem;cursor:pointer;color:#334155}.res-pending-balances summary::marker{color:var(--brand-primary,#1b2746)}.res-pending-balances summary span{display:flex;align-items:center;gap:.55rem}.res-pending-balances summary strong{color:var(--brand-primary,#1b2746);font-size:.9rem}.res-pending-balances__body{padding:0 1rem 1rem}.res-pending-balances__body>p{margin:0 0 .75rem;color:#64748b;font-size:.84rem}.res-pending-balances__list{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.5rem}.res-pending-balances__item{display:flex;justify-content:space-between;align-items:center;gap:.75rem;padding:.7rem .8rem;border-radius:10px;background:#f8fafc;color:#334155;text-decoration:none}.res-pending-balances__item:hover{background:#f1f5f9}.res-pending-balances__item span{display:grid;min-width:0}.res-pending-balances__item strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.res-pending-balances__item small{color:#64748b}.res-pending-balances__item b{color:#b45309;white-space:nowrap}@media(max-width:640px){.res-pending-balances summary{align-items:flex-start;flex-direction:column;gap:.25rem}}
</style>
<?php endif; ?>
