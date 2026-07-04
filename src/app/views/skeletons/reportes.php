<?php /* Skeleton propio: Reportes (hero + grid de tarjetas de reporte, 3 col en PC). */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-bone psk-r16" style="width:52px;height:52px;flex-shrink:0"></div>
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:10px;width:20%"></div>
            <div class="psk-bone" style="height:24px;width:42%"></div>
            <div class="psk-bone" style="height:10px;width:58%"></div>
        </div>
    </div>
    <div class="psk-g3" style="gap:14px">
        <?php for ($i = 0; $i < 6; $i++): ?>
        <div class="psk-fieldset">
            <div style="display:flex;gap:11px;align-items:center">
                <div class="psk-bone psk-r12" style="width:42px;height:42px;flex-shrink:0"></div>
                <div style="flex:1;display:flex;flex-direction:column;gap:6px">
                    <div class="psk-bone" style="height:12px;width:70%"></div>
                    <div class="psk-bone" style="height:9px;width:45%"></div>
                </div>
            </div>
            <div class="psk-bone" style="height:9px;width:92%"></div>
            <div class="psk-bone" style="height:9px;width:84%"></div>
            <div class="psk-bone" style="height:9px;width:88%"></div>
            <div class="psk-bone psk-r12" style="height:32px;width:110px;margin-top:2px"></div>
        </div>
        <?php endfor; ?>
    </div>
</div>
