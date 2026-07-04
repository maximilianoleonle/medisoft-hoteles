<?php /* Skeleton propio: Dashboard (hero + 4 KPIs + gráfico semanal + panel lateral). */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-bone psk-circle psk-hd-circle"></div>
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:11px;width:38%"></div>
            <div class="psk-bone" style="height:20px;width:55%"></div>
        </div>
        <div class="psk-bone psk-r12" style="height:36px;width:120px;margin-left:auto"></div>
    </div>
    <div class="psk-stats">
        <div class="psk-bone psk-r16" style="height:96px"></div>
        <div class="psk-bone psk-r16" style="height:96px"></div>
        <div class="psk-bone psk-r16" style="height:96px"></div>
        <div class="psk-bone psk-r16" style="height:96px"></div>
    </div>
    <div class="psk-split">
        <div class="psk-main">
            <div class="psk-bone psk-r16" style="height:44px"></div>
            <div class="psk-bone psk-r16" style="height:220px"></div>
            <div class="psk-rows">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="psk-row">
                    <div class="psk-bone psk-circle" style="width:36px;height:36px;flex-shrink:0"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:6px">
                        <div class="psk-bone" style="height:10px;width:<?= [60,75,50,68][$i] ?>%"></div>
                        <div class="psk-bone" style="height:10px;width:<?= [40,55,35,45][$i] ?>%"></div>
                    </div>
                    <div class="psk-bone psk-r12" style="height:24px;width:64px"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <div class="psk-side">
            <div class="psk-bone psk-r16" style="height:170px"></div>
            <div class="psk-bone psk-r16" style="height:120px"></div>
            <div class="psk-bone psk-r16" style="height:96px"></div>
        </div>
    </div>
</div>
