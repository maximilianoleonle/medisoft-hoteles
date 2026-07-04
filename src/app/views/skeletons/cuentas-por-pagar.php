<?php /* Skeleton propio: Cuentas por pagar (hero + filtros + resumen + tabla). */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-bone psk-r16" style="width:52px;height:52px;flex-shrink:0"></div>
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:10px;width:22%"></div>
            <div class="psk-bone" style="height:22px;width:42%"></div>
            <div class="psk-bone" style="height:10px;width:56%"></div>
        </div>
        <div style="display:flex;gap:8px;margin-left:auto;flex-shrink:0">
            <div class="psk-bone psk-r12" style="height:40px;width:120px"></div>
        </div>
    </div>
    <!-- Resumen (3 tarjetas) -->
    <div class="psk-g3">
        <div class="psk-bone psk-r16" style="height:88px"></div>
        <div class="psk-bone psk-r16" style="height:88px"></div>
        <div class="psk-bone psk-r16" style="height:88px"></div>
    </div>
    <!-- Filtros -->
    <div class="psk-toolbar">
        <div class="psk-bone psk-r12" style="height:44px;flex:1"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:120px"></div>
    </div>
    <!-- Tabla -->
    <div class="psk-bone psk-r12" style="height:46px"></div>
    <div class="psk-rows">
        <?php $w = [54,62,48,60,52,58,44,56]; for ($i = 0; $i < 8; $i++): ?>
        <div class="psk-row">
            <div class="psk-bone psk-r12" style="width:38px;height:38px;flex-shrink:0"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:7px">
                <div class="psk-bone" style="height:11px;width:<?= $w[$i] ?>%"></div>
                <div class="psk-bone" style="height:9px;width:<?= $w[$i] - 18 ?>%"></div>
            </div>
            <div class="psk-bone psk-r12" style="height:24px;width:80px;flex-shrink:0"></div>
            <div class="psk-bone" style="height:13px;width:90px;flex-shrink:0"></div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="psk-bone psk-r12" style="height:38px;width:230px;margin:0 auto"></div>
</div>
