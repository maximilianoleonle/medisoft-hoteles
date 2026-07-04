<?php /* Skeleton propio: Huéspedes (header kicker/título/subtítulo + toolbar + tabla). */ ?>
<div class="psk-wrap">
    <div class="psk-hdr-col">
        <div class="psk-bone" style="height:10px;width:18%"></div>
        <div class="psk-bone" style="height:26px;width:40%"></div>
        <div class="psk-bone" style="height:11px;width:56%"></div>
    </div>
    <!-- Toolbar / filtros -->
    <div class="psk-toolbar">
        <div class="psk-bone psk-r12" style="height:44px;flex:1"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:120px"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:100px"></div>
    </div>
    <!-- Encabezado de tabla -->
    <div class="psk-bone psk-r12" style="height:46px"></div>
    <!-- Filas de huéspedes -->
    <div class="psk-rows">
        <?php
        $w1 = [66,54,72,50,64,58,70,56];
        $w2 = [40,34,46,32,42,36,44,38];
        for ($i = 0; $i < 8; $i++):
        ?>
        <div class="psk-row">
            <div class="psk-bone psk-circle" style="width:40px;height:40px;flex-shrink:0"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:7px">
                <div class="psk-bone" style="height:11px;width:<?= $w1[$i] ?>%"></div>
                <div class="psk-bone" style="height:9px;width:<?= $w2[$i] ?>%"></div>
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
