<?php
/**
 * Nomina core - ficha de nomina del empleado (Fase 2).
 * Asignaciones (puesto/departamento/contrato/grupo) e historial salarial.
 */
$fiTrab = is_array($trabajador ?? null) ? $trabajador : [];
$fiPuestos = is_array($puestos ?? null) ? $puestos : [];
$fiDeptos = is_array($departamentos ?? null) ? $departamentos : [];
$fiContratos = is_array($contratos ?? null) ? $contratos : [];
$fiGrupos = is_array($grupos ?? null) ? $grupos : [];
$fiPuedeEmpleados = !empty($puedeEmpleados);
$fiPuedeSalarios = !empty($puedeSalarios);
$fiVigente = is_array($salarioVigente ?? null) ? $salarioVigente : null;
$fiHistorial = is_array($historialSalarios ?? null) ? $historialSalarios : [];

$fiEsquemas = [
    'semanal' => 'Semanal', 'quincenal' => 'Quincenal', 'mensual' => 'Mensual',
    'diario' => 'Diario', 'por_hora' => 'Por hora', 'por_evento' => 'Por evento',
];

/*
 * Ruta de nomina: orientacion en vez de candado. El grupo de pago y el salario
 * son dos requisitos INDEPENDIENTES (no hay dependencia tecnica entre ellos),
 * asi que no bloqueamos el salario detras de las asignaciones: guiamos. Estos
 * son los dos "portones" reales para que el empleado entre bien a los periodos,
 * la misma verdad que el listado ya marca con los badges "Sin grupo/Sin salario".
 */
$fiNombre = trim((string) ($fiTrab['nombre_completo'] ?? ''));
$fiPrimerNombre = $fiNombre !== '' ? explode(' ', $fiNombre)[0] : 'Este empleado';
$fiActivo = (string) ($fiTrab['estado'] ?? '') === 'activo';
$fiTieneGrupo = !empty($fiTrab['grupo_nomina_id']);
$fiSinGruposCat = $fiGrupos === [];
$fiTieneSalario = $fiVigente !== null;

// Pasos que ESTE usuario puede ver/actuar (el salario solo si tiene permiso).
$fiPasos = [[
    'clave' => 'grupo', 'label' => 'Grupo de pago',
    'listo' => $fiTieneGrupo, 'ancla' => '#fi-paso-asignaciones',
]];
if ($fiPuedeSalarios) {
    $fiPasos[] = [
        'clave' => 'salario', 'label' => 'Salario vigente',
        'listo' => $fiTieneSalario, 'ancla' => '#fi-paso-salario',
    ];
}
$fiPasosListos = 0;
foreach ($fiPasos as $fiP) { if ($fiP['listo']) { $fiPasosListos++; } }
$fiPasosTotal = count($fiPasos);
$fiListoNomina = $fiPasosTotal > 0 && $fiPasosListos === $fiPasosTotal;

// Modo ver/editar: si ya hay datos guardados, la tarjeta abre en solo lectura
// (campos en gris) con boton "Editar"; al editar se habilita y aparece Guardar.
// Es mejora progresiva: sin JS el formulario queda editable normal.
$fiAsigLock = $fiTieneGrupo;    // asignaciones ya guardadas (tiene grupo de pago)
$fiSalLock  = $fiTieneSalario;  // salario ya registrado (hay vigente)

$back_arrow_href = url('nomina/empleados');
include APP_PATH . '/views/partials/back_arrow.php';

$fiSelect = function (string $name, array $opciones, $seleccionado) {
    ?>
    <select name="<?= $name ?>">
        <option value="">— Sin asignar —</option>
        <?php foreach ($opciones as $op): ?>
        <option value="<?= (int) $op['id'] ?>" <?= (int) $seleccionado === (int) $op['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($op['nombre']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php
};
?>
<style>
.nomina-ficha-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F5F5F7);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text); max-width: 980px; margin: 0 auto; padding: 4px 4px 40px;
}
.nomina-ficha-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-ficha-page .nom-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 28px; margin: 2px 0 2px; font-weight: 600; }
.nomina-ficha-page .fi-sub { color: var(--nom-muted); font-size: 13.5px; margin: 0 0 18px; }
.nomina-ficha-page .fi-badge { display: inline-block; font-size: 11.5px; font-weight: 700; border-radius: 999px; padding: 2px 10px; vertical-align: middle; }
.nomina-ficha-page .fi-badge.activo { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-ficha-page .fi-badge.inactivo { background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-ficha-page .fi-badge.baja { background: rgba(198,40,40,.10); color: #c62828; }
.nomina-ficha-page .fi-card { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; padding: 18px 20px; margin-bottom: 16px; }
.nomina-ficha-page .fi-card h2 { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 19px; margin: 0 0 4px; font-weight: 600; }
.nomina-ficha-page .fi-hint { font-size: 12.5px; color: var(--nom-muted); margin: 0 0 14px; }
.nomina-ficha-page .fi-warn {
    display: flex; align-items: center; gap: 8px; margin: 0 0 14px;
    padding: 9px 12px; border-radius: 10px; font-size: 13px; line-height: 1.4;
    background: rgba(191,144,0,.10); border: 1px solid rgba(191,144,0,.30); color: #7a5c00;
}
.nomina-ficha-page .fi-warn i { color: #9a7400; }
.nomina-ficha-page .fi-warn a { color: inherit; font-weight: 700; text-decoration: underline; }
.nomina-ficha-page .fi-field-hint { display: block; margin-top: 4px; font-size: 11.5px; color: var(--nom-muted); line-height: 1.35; }
.nomina-ficha-page .fi-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
@media (max-width: 640px) { .nomina-ficha-page .fi-grid { grid-template-columns: 1fr; } }
.nomina-ficha-page .fi-field { display: flex; flex-direction: column; gap: 4px; }
.nomina-ficha-page .fi-field label { font-size: 11.5px; font-weight: 700; color: var(--nom-muted); }
.nomina-ficha-page .fi-field input, .nomina-ficha-page .fi-field select, .nomina-ficha-page .fi-field textarea {
    border: 1px solid var(--nom-border); border-radius: 10px; padding: 9px 11px; font-size: 16px; background: #fff; color: var(--nom-text); width: 100%;
}
.nomina-ficha-page .fi-actions { margin-top: 14px; display: flex; justify-content: flex-end; }
.nomina-ficha-page .fi-btn {
    display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--nom-brand);
    border-radius: 11px; padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
    background: var(--nom-brand); color: #fff;
}
.nomina-ficha-page .fi-salario-actual { display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
.nomina-ficha-page .fi-salario-monto { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 30px; font-weight: 600; }
.nomina-ficha-page table.fi-tabla { width: 100%; border-collapse: collapse; font-size: 13px; }
.nomina-ficha-page table.fi-tabla th { text-align: left; font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: var(--nom-muted); padding: 8px; border-bottom: 1px solid var(--nom-border); }
.nomina-ficha-page table.fi-tabla td { padding: 8px; border-bottom: 1px dashed var(--nom-border); }
.nomina-ficha-page .fi-vigente-row { background: color-mix(in srgb, var(--nom-gold) 7%, transparent); }
.nomina-ficha-page .fi-notice { border: 1px dashed var(--nom-border); border-radius: 12px; padding: 12px 14px; font-size: 12.5px; color: var(--nom-muted); }

/* --- Ruta de nomina: encabezado de preparacion --- */
.nomina-ficha-page .fi-ruta { border: 1px solid var(--nom-border); border-radius: 16px; padding: 15px 17px; margin-bottom: 16px; background: color-mix(in srgb, var(--nom-surface) 45%, #fff); }
.nomina-ficha-page .fi-ruta.is-ready { border-color: rgba(46,125,50,.30); background: rgba(46,125,50,.055); }
.nomina-ficha-page .fi-ruta-head { display: flex; gap: 12px; align-items: flex-start; }
.nomina-ficha-page .fi-ruta-icon { flex: 0 0 auto; width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; font-size: 17px; background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-ficha-page .fi-ruta.is-ready .fi-ruta-icon { background: rgba(46,125,50,.14); color: #2e7d32; }
.nomina-ficha-page .fi-ruta-kicker { font-size: 10.5px; letter-spacing: .12em; text-transform: uppercase; font-weight: 700; color: var(--nom-muted); margin: 1px 0 2px; }
.nomina-ficha-page .fi-ruta-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 17px; font-weight: 600; margin: 0 0 2px; }
.nomina-ficha-page .fi-ruta-sub { font-size: 13px; color: var(--nom-muted); margin: 0; line-height: 1.4; }
.nomina-ficha-page .fi-ruta-lista { list-style: none; margin: 13px 0 0; padding: 0; display: flex; flex-direction: column; gap: 7px; }
.nomina-ficha-page .fi-ruta-lista li { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 11px; border: 1px solid var(--nom-border); background: #fff; }
.nomina-ficha-page .fi-ruta-lista li.done { border-color: rgba(46,125,50,.22); background: rgba(46,125,50,.045); }
.nomina-ficha-page .fi-ruta-check { flex: 0 0 auto; width: 20px; height: 20px; display: grid; place-items: center; border-radius: 999px; font-size: 10px; }
.nomina-ficha-page .fi-ruta-lista li.done .fi-ruta-check { background: #2e7d32; color: #fff; }
.nomina-ficha-page .fi-ruta-lista li.todo .fi-ruta-check { color: color-mix(in srgb, var(--nom-gold) 60%, #8a8a8a); box-shadow: inset 0 0 0 1.6px currentColor; font-size: 7px; }
.nomina-ficha-page .fi-ruta-label { font-size: 13.5px; font-weight: 600; flex: 1 1 auto; }
.nomina-ficha-page .fi-ruta-estado { font-size: 11.5px; font-weight: 700; color: #2e7d32; }
.nomina-ficha-page .fi-ruta-cta { font-size: 12.5px; font-weight: 700; text-decoration: none; color: var(--nom-brand); white-space: nowrap; }
.nomina-ficha-page .fi-ruta-cta:hover { text-decoration: underline; }
.nomina-ficha-page .fi-ruta-wait { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700; color: var(--nom-muted); white-space: nowrap; }
.nomina-ficha-page .fi-ruta-wait i { font-size: 9px; }

/* --- Candado del Paso 2: salario bloqueado hasta asignar grupo de pago --- */
.nomina-ficha-page .fi-locked { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; border-radius: 12px; border: 1px dashed var(--nom-border); background: color-mix(in srgb, var(--nom-surface) 50%, #fff); }
.nomina-ficha-page .fi-locked-ico { flex: 0 0 auto; width: 34px; height: 34px; display: grid; place-items: center; border-radius: 10px; background: rgba(0,0,0,.05); color: var(--nom-muted); font-size: 15px; }
.nomina-ficha-page .fi-locked-copy strong { display: block; font-size: 14px; margin-bottom: 3px; color: var(--nom-text); }
.nomina-ficha-page .fi-locked-copy p { margin: 0 0 9px; font-size: 12.5px; color: var(--nom-muted); line-height: 1.45; }
.nomina-ficha-page .fi-locked-copy .fi-ruta-cta { display: inline-flex; align-items: center; gap: 6px; }

/* --- Modo ver/editar: campos en gris cuando ya esta guardado --- */
.nomina-ficha-page .fi-actions { gap: 8px; align-items: center; }
.nomina-ficha-page .fi-btn-ghost { background: transparent; color: var(--nom-brand); border: 1px solid var(--nom-border); }
.nomina-ficha-page .fi-btn-ghost:hover { background: color-mix(in srgb, var(--nom-brand) 7%, transparent); }
.nomina-ficha-page .fi-btn-link { background: transparent; border: none; color: var(--nom-muted); font-size: 13px; font-weight: 600; cursor: pointer; padding: 9px 8px; border-radius: 9px; }
.nomina-ficha-page .fi-btn-link:hover { color: var(--nom-text); text-decoration: underline; }
.nomina-ficha-page form[data-editlock]:not(.is-editing) .fi-field input,
.nomina-ficha-page form[data-editlock]:not(.is-editing) .fi-field select,
.nomina-ficha-page form[data-editlock]:not(.is-editing) .fi-field textarea {
    background: rgba(0,0,0,.04); color: var(--nom-muted); -webkit-text-fill-color: var(--nom-muted); opacity: 1; cursor: not-allowed; border-style: dashed;
}
.nomina-ficha-page form.is-editing .fi-field input,
.nomina-ficha-page form.is-editing .fi-field select,
.nomina-ficha-page form.is-editing .fi-field textarea { background: #fff; border-style: solid; }

/* --- Pasos: numeral + estado en el encabezado de cada tarjeta --- */
.nomina-ficha-page .fi-step-head { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 4px; }
.nomina-ficha-page .fi-step-num { flex: 0 0 auto; width: 28px; height: 28px; display: grid; place-items: center; border-radius: 999px; font-size: 13px; font-weight: 700; font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: var(--nom-brand); color: #fff; margin-top: 1px; }
.nomina-ficha-page .fi-step-head-main { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; flex: 1 1 auto; }
.nomina-ficha-page .fi-step-head-main h2 { margin: 0; }
.nomina-ficha-page .fi-status { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700; border-radius: 999px; padding: 2px 10px; }
.nomina-ficha-page .fi-status.ok { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-ficha-page .fi-status.warn { background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-ficha-page .fi-status.muted { background: rgba(0,0,0,.05); color: var(--nom-muted); }
</style>
<noscript>
<style>
/* Sin JS no hay modo ver/editar: mostramos el formulario editable con su boton
   Guardar y ocultamos "Editar" (que no haria nada sin JS). */
.nomina-ficha-page form[data-editlock] [data-el-edit] { display: none !important; }
.nomina-ficha-page form[data-editlock] [data-el-save] { display: inline-flex !important; }
.nomina-ficha-page form[data-editlock]:not(.is-editing) .fi-field input,
.nomina-ficha-page form[data-editlock]:not(.is-editing) .fi-field select,
.nomina-ficha-page form[data-editlock]:not(.is-editing) .fi-field textarea {
    background: #fff !important; color: var(--nom-text) !important; -webkit-text-fill-color: currentColor !important; border-style: solid !important; cursor: auto !important;
}
</style>
</noscript>

<div class="nomina-ficha-page">
    <p class="nom-kicker">Nómina · Ficha del empleado</p>
    <h1 class="nom-title">
        <?= htmlspecialchars((string) ($fiTrab['nombre_completo'] ?? '')) ?>
        <span class="fi-badge <?= htmlspecialchars((string) ($fiTrab['estado'] ?? '')) ?>"><?= htmlspecialchars(ucfirst((string) ($fiTrab['estado'] ?? ''))) ?></span>
    </h1>
    <p class="fi-sub">
        <?= htmlspecialchars((string) ($fiTrab['rol_laboral'] ?? '')) ?>
        <?= !empty($fiTrab['identificacion']) ? ' · ID ' . htmlspecialchars((string) $fiTrab['identificacion']) : '' ?>
        · <a href="<?= url('trabajadores/' . (int) ($fiTrab['id'] ?? 0)) ?>">Ver expediente en Personal</a>
    </p>

    <?php $subnav_section = 'nomina'; $subnav_active = 'empleados'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <?php if ($fiActivo): ?>
    <section class="fi-ruta <?= $fiListoNomina ? 'is-ready' : 'is-pending' ?>">
        <div class="fi-ruta-head">
            <span class="fi-ruta-icon"><i class="fas <?= $fiListoNomina ? 'fa-circle-check' : 'fa-route' ?>"></i></span>
            <div class="fi-ruta-copy">
                <p class="fi-ruta-kicker">Ruta de nómina · <?= (int) $fiPasosListos ?> de <?= (int) $fiPasosTotal ?> listo</p>
                <?php if ($fiListoNomina): ?>
                <h2 class="fi-ruta-title">Listo para nómina</h2>
                <p class="fi-ruta-sub"><?= htmlspecialchars($fiPrimerNombre) ?> ya entra a los periodos con su sueldo. Abajo puedes ajustar lo que necesites.</p>
                <?php else: ?>
                <h2 class="fi-ruta-title">Aún falta para entrar a nómina</h2>
                <p class="fi-ruta-sub">Completa lo pendiente y <?= htmlspecialchars($fiPrimerNombre) ?> entrará correctamente a los periodos de nómina.</p>
                <?php endif; ?>
            </div>
        </div>
        <ol class="fi-ruta-lista">
            <?php foreach ($fiPasos as $fiP): ?>
            <li class="<?= $fiP['listo'] ? 'done' : 'todo' ?>">
                <span class="fi-ruta-check"><i class="fas <?= $fiP['listo'] ? 'fa-check' : 'fa-circle' ?>"></i></span>
                <span class="fi-ruta-label"><?= htmlspecialchars($fiP['label']) ?></span>
                <?php if ($fiP['listo']): ?>
                    <span class="fi-ruta-estado">Listo</span>
                <?php elseif ($fiP['clave'] === 'grupo' && $fiSinGruposCat): ?>
                    <a class="fi-ruta-cta" href="<?= url('nomina/catalogos') ?>">Crear grupos &rarr;</a>
                <?php elseif ($fiP['clave'] === 'salario' && !$fiTieneGrupo): ?>
                    <span class="fi-ruta-wait"><i class="fas fa-lock"></i> Tras el grupo</span>
                <?php else: ?>
                    <a class="fi-ruta-cta" href="<?= $fiP['ancla'] ?>">Completar &darr;</a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </section>
    <?php endif; ?>

    <div class="fi-card fi-step-card" id="fi-paso-asignaciones">
        <div class="fi-step-head">
            <span class="fi-step-num">1</span>
            <div class="fi-step-head-main">
                <h2>Asignaciones de nómina</h2>
                <?php if ($fiActivo): ?>
                <span class="fi-status <?= $fiTieneGrupo ? 'ok' : 'warn' ?>">
                    <i class="fas <?= $fiTieneGrupo ? 'fa-check' : 'fa-triangle-exclamation' ?>"></i>
                    <?= $fiTieneGrupo ? 'Grupo asignado' : 'Falta grupo de pago' ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <p class="fi-hint">Puesto, departamento, contrato y grupo de pago. El grupo define con qué periodicidad entra a los periodos de nómina.</p>
        <?php if ($fiGrupos === []): ?>
        <p class="fi-warn"><i class="fas fa-triangle-exclamation"></i>
            <span>Aún no hay grupos de pago creados: sin grupo, nadie entra a los periodos de nómina.
            Créalos primero en <a href="<?= url('nomina/catalogos') ?>">Ajustes &rarr; Catálogos</a>.</span>
        </p>
        <?php endif; ?>
        <?php if ($fiPuedeEmpleados): ?>
        <form method="POST" action="<?= url('nomina/empleados/' . (int) ($fiTrab['id'] ?? 0) . '/asignaciones') ?>"<?= $fiAsigLock ? ' data-editlock' : '' ?>>
            <?= csrf_field() ?>
            <div class="fi-grid">
                <div class="fi-field"><label>Puesto</label><?php $fiSelect('puesto_id', $fiPuestos, $fiTrab['puesto_id'] ?? 0); ?></div>
                <div class="fi-field"><label>Departamento</label><?php $fiSelect('departamento_id', $fiDeptos, $fiTrab['departamento_id'] ?? 0); ?></div>
                <div class="fi-field"><label>Tipo de contrato</label><?php $fiSelect('tipo_contrato_id', $fiContratos, $fiTrab['tipo_contrato_id'] ?? 0); ?></div>
                <div class="fi-field"><label>Grupo de pago</label><?php $fiSelect('grupo_nomina_id', $fiGrupos, $fiTrab['grupo_nomina_id'] ?? 0); ?><small class="fi-field-hint">El calendario en el que cobra (define sus periodos de n&oacute;mina).</small></div>
            </div>
            <div class="fi-actions">
                <?php if ($fiAsigLock): ?>
                <button type="button" class="fi-btn fi-btn-ghost ms-pressable" data-el-edit><i class="fas fa-pen"></i> Editar</button>
                <button type="button" class="fi-btn-link" data-el-cancel hidden>Cancelar</button>
                <?php endif; ?>
                <button type="submit" class="fi-btn ms-pressable"<?= $fiAsigLock ? ' data-el-save hidden' : '' ?>><i class="fas fa-check"></i> Guardar asignaciones</button>
            </div>
        </form>
        <?php else: ?>
        <div class="fi-grid">
            <div class="fi-field"><label>Puesto</label><div><?= htmlspecialchars((string) ($fiTrab['puesto_nombre'] ?? '—')) ?></div></div>
            <div class="fi-field"><label>Departamento</label><div><?= htmlspecialchars((string) ($fiTrab['departamento_nombre'] ?? '—')) ?></div></div>
            <div class="fi-field"><label>Tipo de contrato</label><div><?= htmlspecialchars((string) ($fiTrab['contrato_nombre'] ?? '—')) ?></div></div>
            <div class="fi-field"><label>Grupo de pago</label><div><?= htmlspecialchars((string) ($fiTrab['grupo_nombre'] ?? '—')) ?></div></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($fiPuedeSalarios): ?>
    <div class="fi-card fi-step-card" id="fi-paso-salario">
        <div class="fi-step-head">
            <span class="fi-step-num">2</span>
            <div class="fi-step-head-main">
                <h2>Salario</h2>
                <?php if ($fiActivo): ?>
                    <?php if ($fiTieneSalario): ?>
                    <span class="fi-status ok"><i class="fas fa-check"></i> Salario vigente</span>
                    <?php elseif (!$fiTieneGrupo): ?>
                    <span class="fi-status muted"><i class="fas fa-lock"></i> Requiere grupo de pago</span>
                    <?php else: ?>
                    <span class="fi-status warn"><i class="fas fa-triangle-exclamation"></i> Sin salario aún</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($fiVigente): ?>
        <div class="fi-salario-actual">
            <span class="fi-salario-monto">$<?= number_format((float) $fiVigente['salario'], 2) ?></span>
            <span><?= $fiEsquemas[$fiVigente['esquema']] ?? htmlspecialchars((string) $fiVigente['esquema']) ?> · vigente desde <?= htmlspecialchars((string) $fiVigente['vigente_desde']) ?></span>
        </div>
        <?php endif; ?>

        <?php if (!$fiTieneGrupo): /* Candado: el salario se aplica sobre un calendario de pago; sin grupo asignado no hay a qué aplicarlo. */ ?>
        <div class="fi-locked">
            <span class="fi-locked-ico"><i class="fas fa-lock"></i></span>
            <div class="fi-locked-copy">
                <strong>Asigna primero un grupo de pago</strong>
                <p>El salario se aplica sobre un calendario de pago. Elige el <b>grupo de pago</b> en el Paso&nbsp;1 y guarda las asignaciones; con eso se habilita el registro de salario.</p>
                <a class="fi-ruta-cta" href="#fi-paso-asignaciones"><i class="fas fa-arrow-up"></i> Ir a Asignaciones</a>
            </div>
        </div>
        <?php else: ?>
        <?php if (!$fiVigente): ?>
        <p class="fi-hint">Sin salario vigente registrado. Registra el primero abajo.</p>
        <?php endif; ?>

        <form method="POST" action="<?= url('nomina/empleados/' . (int) ($fiTrab['id'] ?? 0) . '/salario') ?>"<?= $fiSalLock ? ' data-editlock' : '' ?>>
            <?= csrf_field() ?>
            <div class="fi-grid">
                <div class="fi-field">
                    <label>Nuevo salario *</label>
                    <input type="number" name="salario" min="0.01" step="0.01" required
                           value="<?= $fiVigente ? number_format((float) $fiVigente['salario'], 2, '.', '') : '' ?>">
                </div>
                <div class="fi-field">
                    <label>Esquema *</label>
                    <select name="esquema" required>
                        <?php foreach ($fiEsquemas as $ek => $el): ?>
                        <option value="<?= $ek ?>" <?= ($fiVigente['esquema'] ?? $fiTrab['periodicidad_pago'] ?? 'quincenal') === $ek ? 'selected' : '' ?>><?= $el ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="fi-field-hint">C&oacute;mo est&aacute; expresado el monto (p. ej. $5,000 <em>por semana</em>). Puede cobrar en un calendario distinto: se prorratea por d&iacute;a.</small>
                </div>
                <div class="fi-field">
                    <label>Vigente desde *</label>
                    <input type="date" name="vigente_desde" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="fi-field">
                    <label>Motivo</label>
                    <input type="text" name="motivo" maxlength="200" placeholder="Aumento anual, promoción…">
                </div>
            </div>
            <div class="fi-actions">
                <?php if ($fiSalLock): ?>
                <button type="button" class="fi-btn fi-btn-ghost ms-pressable" data-el-edit><i class="fas fa-pen"></i> Editar</button>
                <button type="button" class="fi-btn-link" data-el-cancel hidden>Cancelar</button>
                <?php endif; ?>
                <button type="submit" class="fi-btn ms-pressable"<?= $fiSalLock ? ' data-el-save hidden' : '' ?>><i class="fas fa-money-check-dollar"></i> Registrar cambio salarial</button>
            </div>
        </form>

        <?php if ($fiHistorial !== []): ?>
        <h2 style="margin-top:18px;">Historial salarial</h2>
        <table class="fi-tabla">
            <thead>
                <tr><th>Vigencia</th><th>Salario</th><th>Esquema</th><th>Motivo</th></tr>
            </thead>
            <tbody>
                <?php foreach ($fiHistorial as $h): ?>
                <tr class="<?= $h['vigente_hasta'] === null ? 'fi-vigente-row' : '' ?>">
                    <td>
                        <?= htmlspecialchars((string) $h['vigente_desde']) ?> —
                        <?= $h['vigente_hasta'] !== null ? htmlspecialchars((string) $h['vigente_hasta']) : '<strong>vigente</strong>' ?>
                    </td>
                    <td>$<?= number_format((float) $h['salario'], 2) ?></td>
                    <td><?= $fiEsquemas[$h['esquema']] ?? htmlspecialchars((string) $h['esquema']) ?></td>
                    <td><?= htmlspecialchars((string) ($h['motivo'] ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php endif; /* fin candado grupo de pago */ ?>
    </div>
    <?php else: ?>
    <div class="fi-card fi-step-card" id="fi-paso-salario">
        <div class="fi-step-head">
            <span class="fi-step-num">2</span>
            <div class="fi-step-head-main">
                <h2>Salario</h2>
                <span class="fi-status muted"><i class="fas fa-lock"></i> Requiere permiso</span>
            </div>
        </div>
        <div class="fi-notice"><i class="fas fa-lock"></i> No tienes permiso para ver ni editar información salarial (requiere <code>nomina.salarios</code>).</div>
    </div>
    <?php endif; ?>
</div>

<script>
// Ficha de nomina: modo ver/editar. Una tarjeta con data-editlock abre en solo
// lectura (campos deshabilitados) y muestra "Editar"; al pulsarlo se habilitan
// los campos y aparecen Guardar + Cancelar. Sin JS el form queda editable normal.
(function () {
    var forms = document.querySelectorAll('.nomina-ficha-page form[data-editlock]');
    Array.prototype.forEach.call(forms, function (form) {
        var btnEdit   = form.querySelector('[data-el-edit]');
        var btnSave   = form.querySelector('[data-el-save]');
        var btnCancel = form.querySelector('[data-el-cancel]');
        if (!btnEdit || !btnSave) { return; }
        var fields = form.querySelectorAll('input, select, textarea');

        function apply(editing) {
            Array.prototype.forEach.call(fields, function (f) {
                if (f.type === 'hidden') { return; }   // no tocar CSRF ni hidden
                f.disabled = !editing;
            });
            form.classList.toggle('is-editing', editing);
            btnEdit.hidden = editing;
            btnSave.hidden = !editing;
            if (btnCancel) { btnCancel.hidden = !editing; }
        }

        apply(false);  // arranca en solo lectura

        btnEdit.addEventListener('click', function () {
            apply(true);
            var first = form.querySelector('input:not([type=hidden]):not([readonly]), select, textarea');
            if (first) { try { first.focus(); } catch (e) {} }
        });
        if (btnCancel) {
            btnCancel.addEventListener('click', function () {
                form.reset();     // vuelve a los valores guardados
                apply(false);
            });
        }
    });
})();
</script>
