<?php /* Skeleton propio: Proveedores (hero + filtros + tabla). */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-bone psk-r16" style="width:52px;height:52px;flex-shrink:0"></div>
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:10px;width:20%"></div>
            <div class="psk-bone" style="height:22px;width:40%"></div>
            <div class="psk-bone" style="height:10px;width:54%"></div>
        </div>
        <div style="display:flex;gap:8px;margin-left:auto;flex-shrink:0">
            <div class="psk-bone psk-r12" style="height:40px;width:140px"></div>
        </div>
    </div>
    <!-- Filtros -->
    <div class="psk-toolbar">
        <div class="psk-bone psk-r12" style="height:44px;flex:1"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:120px"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:100px"></div>
    </div>
    <!-- Tabla -->
    <div class="psk-bone psk-r12" style="height:46px"></div>
    <div class="psk-rows">
        <?php $w = [60,52,68,46,58,54,64,50]; for ($i = 0; $i < 8; $i++): ?>
        <div class="psk-row">
            <div class="psk-bone psk-r12" style="width:38px;height:38px;flex-shrink:0"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:7px">
                <div class="psk-bone" style="height:11px;width:<?= $w[$i] ?>%"></div>
                <div class="psk-bone" style="height:9px;width:<?= $w[$i] - 22 ?>%"></div>
            </div>
            <div class="psk-bone" style="height:11px;width:120px;flex-shrink:0"></div>
            <div style="display:flex;gap:6px;flex-shrink:0">
                <div class="psk-bone psk-r12" style="height:30px;width:30px"></div>
                <div class="psk-bone psk-r12" style="height:30px;width:30px"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="psk-bone psk-r12" style="height:38px;width:230px;margin:0 auto"></div>
</div>
