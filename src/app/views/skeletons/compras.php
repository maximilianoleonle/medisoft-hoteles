<?php /* Skeleton propio: Compras (hero + filtros + 4 stats resumen + tabla). */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-bone psk-r16" style="width:52px;height:52px;flex-shrink:0"></div>
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:10px;width:20%"></div>
            <div class="psk-bone" style="height:22px;width:40%"></div>
            <div class="psk-bone" style="height:10px;width:56%"></div>
        </div>
        <div style="display:flex;gap:8px;margin-left:auto;flex-shrink:0">
            <div class="psk-bone psk-r12" style="height:40px;width:130px"></div>
        </div>
    </div>
    <!-- 4 tarjetas de resumen -->
    <div class="psk-stats">
        <div class="psk-bone psk-r16" style="height:82px"></div>
        <div class="psk-bone psk-r16" style="height:82px"></div>
        <div class="psk-bone psk-r16" style="height:82px"></div>
        <div class="psk-bone psk-r16" style="height:82px"></div>
    </div>
    <!-- Filtros -->
    <div class="psk-toolbar">
        <div class="psk-bone psk-r12" style="height:44px;flex:1"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:130px"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:110px"></div>
    </div>
    <!-- Tabla -->
    <div class="psk-bone psk-r12" style="height:46px"></div>
    <div class="psk-rows">
        <?php $w = [62,50,58,46,54,60,48,56]; for ($i = 0; $i < 8; $i++): ?>
        <div class="psk-row">
            <div class="psk-bone psk-r12" style="width:36px;height:36px;flex-shrink:0"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:7px">
                <div class="psk-bone" style="height:11px;width:<?= $w[$i] ?>%"></div>
                <div class="psk-bone" style="height:9px;width:<?= $w[$i] - 16 ?>%"></div>
            </div>
            <div class="psk-bone psk-r12" style="height:24px;width:88px;flex-shrink:0"></div>
            <div class="psk-bone" style="height:13px;width:84px;flex-shrink:0"></div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="psk-bone psk-r12" style="height:38px;width:230px;margin:0 auto"></div>
</div>
