<?php /* Skeleton propio: Inventario (header + 4 widgets + toolbar + tabla de escritorio). */ ?>
<div class="psk-wrap">
    <div class="psk-hdr-col">
        <div class="psk-bone" style="height:10px;width:16%"></div>
        <div class="psk-bone" style="height:26px;width:38%"></div>
        <div class="psk-bone" style="height:11px;width:52%"></div>
    </div>
    <!-- 4 widgets de estadística -->
    <div class="psk-stats">
        <div class="psk-bone psk-r16" style="height:86px"></div>
        <div class="psk-bone psk-r16" style="height:86px"></div>
        <div class="psk-bone psk-r16" style="height:86px"></div>
        <div class="psk-bone psk-r16" style="height:86px"></div>
    </div>
    <!-- Toolbar / filtros -->
    <div class="psk-toolbar">
        <div class="psk-bone psk-r12" style="height:44px;flex:1"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:130px"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:110px"></div>
    </div>
    <!-- Encabezado de tabla -->
    <div class="psk-bone psk-r12" style="height:46px"></div>
    <!-- Filas de inventario -->
    <div class="psk-rows">
        <?php
        $w1 = [58,48,64,44,56,50,60,46];
        for ($i = 0; $i < 8; $i++):
        ?>
        <div class="psk-row">
            <div class="psk-bone psk-r12" style="width:38px;height:38px;flex-shrink:0"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:7px">
                <div class="psk-bone" style="height:11px;width:<?= $w1[$i] ?>%"></div>
                <div class="psk-bone" style="height:9px;width:<?= $w1[$i] - 16 ?>%"></div>
            </div>
            <div class="psk-bone psk-r12" style="height:24px;width:70px;flex-shrink:0"></div>
            <div class="psk-bone" style="height:12px;width:60px;flex-shrink:0"></div>
            <div style="display:flex;gap:6px;flex-shrink:0">
                <div class="psk-bone psk-r12" style="height:30px;width:30px"></div>
                <div class="psk-bone psk-r12" style="height:30px;width:30px"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</div>
