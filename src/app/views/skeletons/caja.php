<?php /* Skeleton propio: Caja (5 KPIs + movimientos + panel resumen). */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:11px;width:22%"></div>
            <div class="psk-bone" style="height:24px;width:38%"></div>
        </div>
        <div style="display:flex;gap:8px;margin-left:auto">
            <div class="psk-bone psk-r12" style="height:38px;width:120px"></div>
            <div class="psk-bone psk-r12" style="height:38px;width:120px"></div>
        </div>
    </div>
    <!-- 5 KPIs de caja -->
    <div class="psk-g5">
        <?php for ($i = 0; $i < 5; $i++): ?>
        <div class="psk-bone psk-r16" style="height:86px"></div>
        <?php endfor; ?>
    </div>
    <!-- Movimientos + resumen -->
    <div class="psk-split">
        <div class="psk-main">
            <div class="psk-toolbar">
                <div class="psk-bone psk-r12" style="height:42px;flex:1"></div>
                <div class="psk-bone psk-r12" style="height:42px;width:110px"></div>
            </div>
            <div class="psk-bone psk-r12" style="height:44px"></div>
            <div class="psk-rows">
                <?php $cw = [64,52,60,48,58,50,62]; for ($i = 0; $i < 7; $i++): ?>
                <div class="psk-row">
                    <div class="psk-bone psk-r12" style="width:34px;height:34px;flex-shrink:0"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:6px">
                        <div class="psk-bone" style="height:10px;width:<?= $cw[$i] ?>%"></div>
                        <div class="psk-bone" style="height:9px;width:<?= $cw[$i] - 18 ?>%"></div>
                    </div>
                    <div class="psk-bone" style="height:14px;width:84px;flex-shrink:0"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <div class="psk-side">
            <div class="psk-bone psk-r16" style="height:210px"></div>
            <div class="psk-bone psk-r16" style="height:150px"></div>
        </div>
    </div>
</div>
