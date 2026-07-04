<?php /* Skeleton propio: Habitaciones (6 stat cards + filtros + rejilla de habitaciones). */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:11px;width:26%"></div>
            <div class="psk-bone" style="height:24px;width:44%"></div>
        </div>
        <div style="display:flex;gap:8px;margin-left:auto">
            <div class="psk-bone psk-r12" style="height:38px;width:110px"></div>
            <div class="psk-bone psk-r12" style="height:38px;width:130px"></div>
        </div>
    </div>
    <!-- Tira de 6 indicadores de estado -->
    <div class="psk-g6">
        <?php for ($i = 0; $i < 6; $i++): ?>
        <div class="psk-bone psk-r16" style="height:74px"></div>
        <?php endfor; ?>
    </div>
    <!-- Filtros -->
    <div class="psk-toolbar">
        <div class="psk-bone psk-r12" style="height:40px;flex:1"></div>
        <div class="psk-bone psk-r12" style="height:40px;width:96px"></div>
        <div class="psk-bone psk-r12" style="height:40px;width:96px"></div>
        <div class="psk-bone psk-r12" style="height:40px;width:96px"></div>
    </div>
    <!-- Rejilla de habitaciones (tarjetas) -->
    <div class="psk-g6" style="gap:12px">
        <?php for ($i = 0; $i < 18; $i++): ?>
        <div class="psk-bone psk-r16" style="height:118px"></div>
        <?php endfor; ?>
    </div>
</div>
