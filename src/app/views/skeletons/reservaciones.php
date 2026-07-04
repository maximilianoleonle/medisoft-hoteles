<?php /* Skeleton propio: Reservaciones (chips de estado + tabla de reservas). */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:11px;width:24%"></div>
            <div class="psk-bone" style="height:24px;width:40%"></div>
        </div>
        <div style="display:flex;gap:8px;margin-left:auto">
            <div class="psk-bone psk-r12" style="height:38px;width:110px"></div>
            <div class="psk-bone psk-r12" style="height:38px;width:140px"></div>
        </div>
    </div>
    <!-- Chips de filtro por estado -->
    <div class="psk-toolbar" style="flex-wrap:wrap">
        <?php $rw = [96, 118, 104, 90, 112]; for ($i = 0; $i < 5; $i++): ?>
        <div class="psk-bone psk-r12" style="height:34px;width:<?= $rw[$i] ?>px"></div>
        <?php endfor; ?>
    </div>
    <!-- Búsqueda -->
    <div class="psk-toolbar">
        <div class="psk-bone psk-r12" style="height:44px;flex:1"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:120px"></div>
    </div>
    <!-- Encabezado de tabla -->
    <div class="psk-bone psk-r12" style="height:46px"></div>
    <!-- Filas -->
    <div class="psk-rows">
        <?php
        $w1 = [70,58,66,52,62,55,68,60];
        $w2 = [45,38,42,34,40,36,44,39];
        for ($i = 0; $i < 8; $i++):
        ?>
        <div class="psk-row">
            <div class="psk-bone psk-circle" style="width:38px;height:38px;flex-shrink:0"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:7px">
                <div class="psk-bone" style="height:11px;width:<?= $w1[$i] ?>%"></div>
                <div class="psk-bone" style="height:9px;width:<?= $w2[$i] ?>%"></div>
            </div>
            <div class="psk-bone psk-r12" style="height:26px;width:90px;flex-shrink:0"></div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0">
                <div class="psk-bone" style="height:12px;width:80px"></div>
                <div class="psk-bone" style="height:9px;width:54px"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="psk-bone psk-r12" style="height:38px;width:230px;margin:0 auto"></div>
</div>
