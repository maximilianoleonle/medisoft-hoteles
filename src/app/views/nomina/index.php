<?php
/**
 * Nomina core - dashboard (Fase 1: base tecnica).
 * Solo lectura: resume configuracion y datos del subsistema laboral existente.
 */
$nomConfig = is_array($config ?? null) ? $config : [];
$nomStats = is_array($stats ?? null) ? $stats : [];
$nomPersonalActivo = !empty($personalActivo);
$nomPuedeConfigurar = !empty($puedeConfigurar);
$nomPuedeGestionarRoles = function_exists('can') && can('roles.manage');
$nomUltimo = is_array($nomStats['ultimo_periodo'] ?? null) ? $nomStats['ultimo_periodo'] : null;

$nomModoLabels = [
    'simplificada' => 'Operativa simplificada',
    'hibrida' => 'Híbrida (interno + contador)',
    'legal' => 'Legal / fiscal',
];
$nomModo = (string) ($nomConfig['modo'] ?? 'simplificada');
$nomModoLabel = $nomModoLabels[$nomModo] ?? $nomModoLabels['simplificada'];

$nomEstadoPeriodoLabels = [
    'cerrado' => ['texto' => 'Cerrado', 'clase' => 'warn'],
    'aprobado' => ['texto' => 'Aprobado', 'clase' => 'ok'],
    'anulado' => ['texto' => 'Anulado', 'clase' => 'danger'],
];
?>
<style>
.nomina-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F6F2EA);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text);
    max-width: 1120px;
    margin: 0 auto;
    padding: 4px 4px 40px;
}
.nomina-page .nom-title-lockup { display: flex; align-items: center; gap: 14px; margin-bottom: 4px; }
.nomina-page .nom-hero-icon {
    width: 48px; height: 48px; border-radius: 14px; flex: 0 0 auto;
    display: flex; align-items: center; justify-content: center;
    background: color-mix(in srgb, var(--nom-brand) 10%, transparent);
    color: var(--nom-brand); font-size: 20px;
}
.nomina-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-page .nom-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 30px; line-height: 1.1; margin: 2px 0 0; font-weight: 600; }
.nomina-page .nom-subtitle { color: var(--nom-muted); font-size: 14px; margin: 6px 0 18px; }
.nomina-page .nom-navrow { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 22px; }
.nomina-page .nom-btn {
    display: inline-flex; align-items: center; gap: 8px;
    border: 1px solid var(--nom-border); border-radius: 12px;
    padding: 9px 14px; font-size: 13px; font-weight: 600; text-decoration: none;
    color: var(--nom-text); background: var(--nom-card);
    transition: border-color .15s ease, transform .12s ease;
}
.nomina-page .nom-btn:hover { border-color: var(--nom-gold); transform: translateY(-1px); }
.nomina-page .nom-btn-primary { background: var(--nom-brand); border-color: var(--nom-brand); color: #fff; }
.nomina-page .nom-btn-primary:hover { border-color: var(--nom-brand); opacity: .92; }
.nomina-page .nom-permission-pill {
    display: inline-flex; align-items: center; gap: 8px;
    border: 1px dashed var(--nom-border); border-radius: 12px;
    padding: 9px 13px; font-size: 12.5px; font-weight: 600;
    color: var(--nom-muted); background: color-mix(in srgb, var(--nom-card) 78%, transparent);
}
.nomina-page .nom-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 22px; }
@media (max-width: 900px) { .nomina-page .nom-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.nomina-page .nom-kpi {
    background: var(--nom-card); border: 1px solid var(--nom-border);
    border-radius: 16px; padding: 16px 18px;
}
.nomina-page .nom-kpi-label { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; margin-bottom: 6px; }
.nomina-page .nom-kpi-value { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 26px; font-weight: 600; line-height: 1.1; }
.nomina-page .nom-kpi-hint { font-size: 12px; color: var(--nom-muted); margin-top: 4px; }
.nomina-page .nom-badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 700; border-radius: 999px; padding: 3px 11px;
}
.nomina-page .nom-badge.ok { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-page .nom-badge.warn { background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-page .nom-badge.danger { background: rgba(198,40,40,.12); color: #c62828; }
.nomina-page .nom-notice {
    display: flex; gap: 12px; align-items: flex-start;
    border: 1px solid color-mix(in srgb, var(--nom-gold) 45%, var(--nom-border));
    background: color-mix(in srgb, var(--nom-gold) 8%, var(--nom-card));
    border-radius: 14px; padding: 14px 16px; margin-bottom: 18px; font-size: 13.5px;
}
.nomina-page .nom-notice i { color: var(--nom-gold); margin-top: 2px; }
.nomina-page .nom-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
@media (max-width: 768px) { .nomina-page .nom-grid { grid-template-columns: 1fr; } }
.nomina-page .nom-card {
    background: var(--nom-card); border: 1px solid var(--nom-border);
    border-radius: 16px; padding: 18px 20px;
}
.nomina-page .nom-card h2 { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 20px; margin: 0 0 10px; font-weight: 600; }
.nomina-page .nom-card ul { margin: 0; padding: 0; list-style: none; }
.nomina-page .nom-card li { display: flex; justify-content: space-between; gap: 12px; padding: 7px 0; border-bottom: 1px dashed var(--nom-border); font-size: 13.5px; }
.nomina-page .nom-card li:last-child { border-bottom: 0; }
.nomina-page .nom-card li .nom-dato { color: var(--nom-muted); }
.nomina-page .nom-card li .nom-valor { font-weight: 600; text-align: right; }
.nomina-page .nom-empty {
    text-align: center; padding: 34px 18px; color: var(--nom-muted);
    border: 1px dashed var(--nom-border); border-radius: 16px; background: var(--nom-card);
}
.nomina-page .nom-empty i { font-size: 26px; color: var(--nom-gold); margin-bottom: 10px; display: block; }
.nomina-page .nom-roadmap { margin-top: 22px; font-size: 12.5px; color: var(--nom-muted); text-align: center; }
</style>

<div class="nomina-page">

    <div class="nom-title-lockup">
        <div class="nom-hero-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <div>
            <p class="nom-kicker">Administración</p>
            <h1 class="nom-title">Nómina</h1>
        </div>
    </div>
    <p class="nom-subtitle">
        Modo actual: <strong><?= htmlspecialchars($nomModoLabel) ?></strong>.
        Aquí se revisan catálogos, empleados, incidencias y periodos; los pagos y datos base del personal siguen conectados con Personal.
    </p>

    <?php $subnav_section = 'nomina'; $subnav_active = 'inicio'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <?php if (!$nomPuedeConfigurar): ?>
    <div class="nom-notice">
        <i class="fas fa-circle-info"></i>
        <div>
            <strong>Tu usuario puede consultar nómina, pero no configurarla.</strong><br>
            Puedes entrar a Catálogos para revisar lo existente. Para crear departamentos, puestos, contratos o grupos de pago,
            entra con un rol con <strong>nomina.configurar</strong> o pide que ajusten tus permisos.
            <?php if ($nomPuedeGestionarRoles): ?>
            <div style="margin-top:10px;">
                <a href="<?= url('configuracion/roles') ?>" class="nom-btn ms-pressable"><i class="fas fa-user-lock"></i> Roles y permisos</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$nomPersonalActivo): ?>
    <div class="nom-notice">
        <i class="fas fa-circle-info"></i>
        <div>
            <strong>El bloque Personal no está activo en este negocio.</strong><br>
            La nómina trabaja sobre los empleados del bloque Personal. Pide a tu administrador Medisoft
            activarlo para dar de alta empleados, sueldos e incidencias.
        </div>
    </div>
    <?php endif; ?>

    <div class="nom-summary" data-ms-stagger>
        <div class="nom-kpi">
            <div class="nom-kpi-label">Empleados activos</div>
            <div class="nom-kpi-value" <?= $nomStats['trabajadores_activos'] !== null ? 'data-ms-count' : '' ?>>
                <?= $nomStats['trabajadores_activos'] !== null ? (int) $nomStats['trabajadores_activos'] : '—' ?>
            </div>
            <div class="nom-kpi-hint"><?= $nomPersonalActivo ? 'Del bloque Personal' : 'Requiere bloque Personal' ?></div>
        </div>
        <div class="nom-kpi">
            <div class="nom-kpi-label">Periodos registrados</div>
            <div class="nom-kpi-value" <?= $nomStats['periodos_registrados'] !== null ? 'data-ms-count' : '' ?>>
                <?= $nomStats['periodos_registrados'] !== null ? (int) $nomStats['periodos_registrados'] : '—' ?>
            </div>
            <div class="nom-kpi-hint">Pre-nómina histórica</div>
        </div>
        <div class="nom-kpi">
            <div class="nom-kpi-label">Modo de nómina</div>
            <div class="nom-kpi-value"><?= htmlspecialchars(ucfirst($nomModo)) ?></div>
            <div class="nom-kpi-hint"><?= htmlspecialchars($nomModoLabel) ?></div>
        </div>
        <div class="nom-kpi">
            <div class="nom-kpi-label">Último periodo</div>
            <div class="nom-kpi-value" style="font-size:18px; padding-top:4px;">
                <?php if ($nomUltimo): ?>
                    <?php
                    $nomEstado = $nomEstadoPeriodoLabels[(string) ($nomUltimo['estado'] ?? '')] ?? null;
                    ?>
                    <?= htmlspecialchars((string) ($nomUltimo['fecha_inicio'] ?? '')) ?> — <?= htmlspecialchars((string) ($nomUltimo['fecha_fin'] ?? '')) ?>
                    <?php if ($nomEstado): ?>
                        <span class="nom-badge <?= $nomEstado['clase'] ?>"><?= $nomEstado['texto'] ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </div>
            <div class="nom-kpi-hint"><?= $nomUltimo ? htmlspecialchars((string) ($nomUltimo['etiqueta'] ?? '')) : 'Sin periodos aún' ?></div>
        </div>
    </div>

    <div class="nom-grid" data-ms-stagger>
        <div class="nom-card">
            <h2>Política del negocio</h2>
            <ul>
                <li><span class="nom-dato">Modo de nómina</span><span class="nom-valor"><?= htmlspecialchars($nomModoLabel) ?></span></li>
                <li><span class="nom-dato">País de reglas</span><span class="nom-valor"><?= htmlspecialchars((string) ($nomConfig['pais'] ?? 'MX')) ?></span></li>
                <li><span class="nom-dato">Redondeo</span><span class="nom-valor"><?= ($nomConfig['redondeo'] ?? 'centavos') === 'pesos' ? 'A pesos enteros' : 'A centavos' ?></span></li>
                <li><span class="nom-dato">Horas extra</span><span class="nom-valor"><?= !empty($nomConfig['permitir_horas_extra']) ? 'Permitidas' : 'No permitidas' ?></span></li>
                <li><span class="nom-dato">Descuentos manuales</span><span class="nom-valor"><?= !empty($nomConfig['permitir_descuentos_manuales']) ? 'Permitidos' : 'No permitidos' ?></span></li>
                <li><span class="nom-dato">Cierre requiere aprobación</span><span class="nom-valor"><?= !empty($nomConfig['requiere_aprobacion_cierre']) ? 'Sí' : 'No' ?></span></li>
                <li><span class="nom-dato">Reapertura de periodos</span><span class="nom-valor"><?= !empty($nomConfig['permitir_reapertura']) ? 'Permitida (con motivo)' : 'Bloqueada' ?></span></li>
            </ul>
        </div>
        <div class="nom-card">
            <h2>Qué sigue</h2>
            <?php if ($nomPersonalActivo && ($nomStats['trabajadores_activos'] ?? 0) > 0): ?>
            <ul>
                <li><span class="nom-dato">Catálogos base</span><span class="nom-valor"><a href="<?= url('nomina/catalogos') ?>"><?= $nomPuedeConfigurar ? 'Configurar' : 'Ver' ?></a></span></li>
                <li><span class="nom-dato">Ficha laboral y salarios</span><span class="nom-valor"><a href="<?= url('nomina/empleados') ?>">Abrir empleados</a></span></li>
                <li><span class="nom-dato">Incidencias del periodo</span><span class="nom-valor"><a href="<?= url('nomina/incidencias') ?>">Capturar</a></span></li>
                <li><span class="nom-dato">Preview, cierre y aprobación</span><span class="nom-valor"><a href="<?= url('nomina/periodos') ?>">Ver periodos</a></span></li>
                <li><span class="nom-dato">Alta o baja de personal</span><span class="nom-valor"><a href="<?= url('trabajadores') ?>">Abrir Personal</a></span></li>
            </ul>
            <?php else: ?>
            <div class="nom-empty">
                <i class="fas fa-seedling"></i>
                Aún no hay empleados registrados.<br>
                <?= $nomPersonalActivo
                    ? 'Da de alta a tu equipo en Personal para empezar a operar la nómina.'
                    : 'Activa el bloque Personal para dar de alta a tu equipo.' ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <p class="nom-roadmap">
        Nómina avanzada: configuración, catálogos, empleados, incidencias, periodos y recibos internos.
        Los cálculos se revisan antes de cerrar; los pagos reales siguen controlados por Caja/Personal.
    </p>
</div>
