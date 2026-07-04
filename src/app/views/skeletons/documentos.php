<?php /* Skeleton propio: Documentos (hero + filtros + tabla). */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-bone psk-r16" style="width:52px;height:52px;flex-shrink:0"></div>
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:10px;width:20%"></div>
            <div class="psk-bone" style="height:22px;width:38%"></div>
            <div class="psk-bone" style="height:10px;width:56%"></div>
        </div>
        <div style="display:flex;gap:8px;margin-left:auto;flex-shrink:0">
            <div class="psk-bone psk-r12" style="height:40px;width:110px"></div>
            <div class="psk-bone psk-r12" style="height:40px;width:130px"></div>
        </div>
    </div>
    <!-- Filtros -->
    <div class="psk-toolbar">
        <div class="psk-bone psk-r12" style="height:44px;flex:1"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:130px"></div>
    </div>
    <!-- Tabla -->
    <div class="psk-bone psk-r12" style="height:46px"></div>
    <div class="psk-rows">
        <?php $w = [56,64,48,60,52,58,46,62]; for ($i = 0; $i < 8; $i++): ?>
        <div class="psk-row">
            <div class="psk-bone psk-r12" style="width:36px;height:44px;flex-shrink:0"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:7px">
                <div class="psk-bone" style="height:11px;width:<?= $w[$i] ?>%"></div>
                <div class="psk-bone" style="height:9px;width:<?= $w[$i] - 20 ?>%"></div>
            </div>
            <div class="psk-bone psk-r12" style="height:24px;width:76px;flex-shrink:0"></div>
            <div class="psk-bone" style="height:11px;width:70px;flex-shrink:0"></div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="psk-bone psk-r12" style="height:38px;width:230px;margin:0 auto"></div>
</div>
