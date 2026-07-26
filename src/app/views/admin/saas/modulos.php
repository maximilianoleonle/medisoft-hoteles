<?php
$modulos = $modulos ?? [];
$planes = $planes ?? [];

// Sin separador de miles: estos valores van a value="" de <input type="number">.
$fmtMoney = function ($v): string {
    return number_format((float) $v, 2, '.', '');
};
// Para montos de solo lectura (texto), con separador de miles.
$fmtMoneyTexto = function ($v): string {
    return number_format((float) $v, 2);
};

// Clasificacion (solo lectura, misma fuente de datos del controller):
// base = paquete basico; los internos Medisoft van en su seccion informativa
// (sin precio editable: el servidor tampoco lo permite) y NO como beneficio.
$modulosCore = array_filter($modulos, function ($m) {
    return ($m['tipo_comercial'] ?? (empty($m['es_core']) ? 'opcional' : 'base')) === 'base';
});
$modulosOpcionales = array_filter($modulos, function ($m) {
    return empty($m['es_core']) && (($m['tipo_comercial'] ?? 'opcional') === 'opcional');
});
$modulosInternosCatalogo = array_filter($modulos, function ($m) {
    return ($m['tipo_comercial'] ?? '') === 'interno';
});

// Subgrupos de opcionales para la jerarquia visual. Un reporte que se
// desbloquee en el futuro cae solo en "Disponibles" sin rehacer la pantalla.
$esReporteIndividual = function (array $m): bool {
    return strpos((string) ($m['clave'] ?? ''), 'reporte_') === 0;
};
$opcionalesDisponibles = array_filter($modulosOpcionales, function ($m) {
    return !empty($m['activo_global']);
});
$reportesIndividuales = array_filter($modulosOpcionales, function ($m) use ($esReporteIndividual) {
    return empty($m['activo_global']) && $esReporteIndividual($m);
});
$opcionalesPendientes = array_filter($modulosOpcionales, function ($m) use ($esReporteIndividual) {
    return empty($m['activo_global']) && !$esReporteIndividual($m);
});

// Planes: el controller solo entrega planes activos (Basico y Personalizado);
// Pro y Premium estan retirados de la oferta y se conservan como historial.
$planBasico = null;
$planPersonalizado = null;
foreach ($planes as $plan) {
    if (($plan['clave'] ?? '') === 'basico') {
        $planBasico = $plan;
    } elseif (($plan['clave'] ?? '') === 'personalizado') {
        $planPersonalizado = $plan;
    }
}
?>

<style>
/* Catalogo comercial: solo presentacion, tokens --ms-* del Panel Medisoft. */

/* Inputs con altura tactil, mismo tratamiento que el detalle de hotel. */
.ms-admin-scope .ms-cat-input {
    min-height: 44px;
    border-color: #cbd5e1;
    border-radius: .5rem;
    background: #fff;
    color: var(--ms-text);
}
.ms-admin-scope .ms-cat-input:focus {
    border-color: var(--ms-primary) !important;
    outline: 0;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .13) !important;
}

.ms-admin-scope .ms-cat-summary {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: 1rem 1.5rem;
    min-height: 44px;
    cursor: pointer;
    list-style: none;
    -webkit-user-select: none;
    user-select: none;
}
.ms-admin-scope .ms-cat-summary::-webkit-details-marker { display: none; }
.ms-admin-scope .ms-cat-summary:focus-visible {
    outline: 2px solid var(--ms-primary);
    outline-offset: -2px;
    border-radius: .5rem;
}
.ms-admin-scope .ms-cat-summary .ms-cat-chevron {
    margin-left: auto;
    color: var(--ms-muted);
    transition: transform .15s ease;
    flex-shrink: 0;
}
.ms-admin-scope details[open] > .ms-cat-summary .ms-cat-chevron { transform: rotate(180deg); }
.ms-admin-scope details[open] > .ms-cat-summary { border-bottom: 1px solid var(--ms-border); }

/* Tablas del catalogo en movil: filas como tarjetas legibles (mismo patron
   data-label del detalle de hotel). */
@media (max-width: 640px) {
    .ms-admin-scope .ms-cat-table thead {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0 0 0 0);
        white-space: nowrap;
    }
    .ms-admin-scope .ms-cat-table,
    .ms-admin-scope .ms-cat-table tbody,
    .ms-admin-scope .ms-cat-table tr,
    .ms-admin-scope .ms-cat-table td {
        display: block;
        width: 100%;
    }
    .ms-admin-scope .ms-cat-table tbody { padding: .75rem; }
    .ms-admin-scope .ms-cat-table tr {
        margin-bottom: .75rem;
        border: 1px solid var(--ms-border);
        border-radius: .65rem;
        background: #fff;
        overflow: hidden;
    }
    .ms-admin-scope .ms-cat-table tr:last-child { margin-bottom: 0; }
    .ms-admin-scope .ms-cat-table td {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .35rem .75rem;
        padding: .55rem .85rem;
        border: 0;
    }
    .ms-admin-scope .ms-cat-table td + td { border-top: 1px dashed var(--ms-border); }
    .ms-admin-scope .ms-cat-table td::before {
        content: attr(data-label);
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--ms-muted);
    }
    .ms-admin-scope .ms-cat-table td[data-label=""]::before { display: none; }
}
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- A. Encabezado -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold" style="color:var(--ms-text);">Catálogo comercial</h1>
            <p class="mt-1 text-sm" style="color:var(--ms-muted);">Administra el paquete base, los módulos opcionales y sus precios mensuales por hotel.</p>
        </div>
        <a href="<?= url('admin/saas/hoteles') ?>"
           class="inline-flex items-center justify-center gap-1.5 rounded-md border px-4 py-2 text-sm font-medium transition hover:bg-slate-50"
           style="border-color:var(--ms-border);color:var(--ms-text);">
            <i class="fas fa-arrow-left text-xs" style="color:var(--ms-muted);"></i>
            Volver a hoteles
        </a>
    </div>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div class="mb-4 rounded-md border px-4 py-3 text-sm <?= $tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <!-- Resumen compacto del catalogo -->
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border bg-white flex items-center gap-3 px-4 py-3" style="border-color:var(--ms-border);">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(37,99,235,.10);">
                <i class="fas fa-lock text-sm" style="color:var(--ms-primary);"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Paquete base</div>
                <div class="mt-0.5 text-2xl font-semibold" style="color:var(--ms-text);"><?= count($modulosCore) ?></div>
            </div>
        </div>
        <div class="rounded-lg border bg-white flex items-center gap-3 px-4 py-3" style="border-color:var(--ms-border);">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(22,163,74,.10);">
                <i class="fas fa-circle-check text-sm" style="color:var(--ms-success);"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Opcionales disponibles</div>
                <div class="mt-0.5 text-2xl font-semibold" style="color:var(--ms-success);"><?= count($opcionalesDisponibles) ?></div>
            </div>
        </div>
        <div class="rounded-lg border bg-white flex items-center gap-3 px-4 py-3" style="border-color:var(--ms-border);">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(245,158,11,.12);">
                <i class="fas fa-circle-pause text-sm" style="color:var(--ms-warning);"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Bloqueados temporalmente</div>
                <div class="mt-0.5 text-2xl font-semibold" style="color:var(--ms-text);"><?= count($reportesIndividuales) + count($opcionalesPendientes) ?></div>
            </div>
        </div>
        <div class="rounded-lg border bg-white flex items-center gap-3 px-4 py-3" style="border-color:var(--ms-border);">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(100,116,139,.10);">
                <i class="fas fa-screwdriver-wrench text-sm" style="color:var(--ms-muted);"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Funciones internas</div>
                <div class="mt-0.5 text-2xl font-semibold" style="color:var(--ms-muted);"><?= count($modulosInternosCatalogo) ?></div>
            </div>
        </div>
    </div>

    <!-- B. Precio del paquete base -->
    <div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Precio del paquete base</h2>
            <p class="text-sm text-gray-500">El cobro mensual de cada hotel es este paquete más la suma de sus bloques opcionales contratados.</p>
        </div>
        <?php if (empty($planes)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">No hay planes disponibles. Aplique la migración de planes.</div>
        <?php else: ?>
            <form method="POST" action="<?= url('admin/saas/planes/precios') ?>">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2">
                    <?php if ($planBasico): ?>
                        <div class="rounded-lg border px-4 py-3" style="border-color:var(--ms-border);">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-cube text-sm" style="color:var(--ms-primary);"></i>
                                <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($planBasico['nombre'] ?? 'Básico', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="mt-0.5 mb-2 text-xs text-gray-500">Incluye los <?= count($modulosCore) ?> módulos del paquete base.</p>
                            <div class="flex items-center gap-2">
                                <label for="precio-plan-basico" class="text-sm text-gray-500">$</label>
                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                       id="precio-plan-basico"
                                       name="precios_planes[<?= (int) $planBasico['id'] ?>]"
                                       value="<?= $fmtMoney($planBasico['precio_mensual'] ?? 0) ?>"
                                       class="ms-cat-input w-full rounded-md border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                                <span class="text-xs whitespace-nowrap text-gray-500"><?= htmlspecialchars($planBasico['moneda_codigo'] ?? 'MXN', ENT_QUOTES, 'UTF-8') ?>/mes</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($planPersonalizado): ?>
                        <div class="rounded-lg border px-4 py-3" style="border-color:var(--ms-border);">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-sliders text-sm" style="color:var(--ms-muted);"></i>
                                <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($planPersonalizado['nombre'] ?? 'Personalizado', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500">Configuración por hotel: sin precio fijo, se arma módulo por módulo desde el detalle de cada hotel.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="px-6 pb-4 text-xs text-gray-500">
                    <i class="fas fa-circle-info mr-1" aria-hidden="true" style="color:var(--ms-muted);"></i>
                    Los planes Pro y Premium están retirados de la oferta; se conservan solo como historial y no pueden asignarse.
                </p>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-md text-white text-sm font-medium transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                        Guardar precio del paquete
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- C. Paquete base -->
    <div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Paquete base</h2>
            <p class="text-sm text-gray-500">Lo mínimo para operar un hotel. Estos módulos están siempre incluidos: no se contratan por separado ni pueden desactivarse.</p>
        </div>
        <?php if (empty($modulosCore)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">Aún no hay módulos base. Aplique la migración de precios de módulos.</div>
        <?php else: ?>
            <ul class="grid grid-cols-1 gap-2 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($modulosCore as $modulo): ?>
                    <li class="flex items-center gap-2.5 rounded-lg border px-3 py-2.5" style="border-color:var(--ms-border);">
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md" style="background:rgba(37,99,235,.10);">
                            <i class="fas fa-lock text-[11px]" aria-hidden="true" style="color:var(--ms-primary);"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium" style="color:var(--ms-text);"><?= htmlspecialchars($modulo['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="block text-[11px]" style="color:var(--ms-muted);">Incluido · no desactivable</span>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- D/E/F. Modulos opcionales (un solo form de precios de catalogo) -->
    <div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Módulos opcionales</h2>
            <p class="text-sm text-gray-500">Se contratan por hotel y suman al cobro mensual. El precio de lista puede sobreescribirse por hotel desde su detalle.</p>
        </div>

        <?php if (empty($modulosOpcionales)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">No hay bloques opcionales en el catálogo.</div>
        <?php else: ?>
            <form method="POST" action="<?= url('admin/saas/modulos/precios') ?>">
                <?= csrf_field() ?>

                <!-- D. Disponibles hoy -->
                <div class="px-6 pt-4 pb-1">
                    <h3 class="text-sm font-semibold" style="color:var(--ms-text);">
                        <i class="fas fa-circle-check mr-1.5 text-xs" aria-hidden="true" style="color:var(--ms-success);"></i>
                        Disponibles para la venta
                        <span class="ml-1 font-normal" style="color:var(--ms-muted);">(<?= count($opcionalesDisponibles) ?>)</span>
                    </h3>
                </div>
                <?php if (empty($opcionalesDisponibles)): ?>
                    <div class="px-6 pb-5 text-sm text-gray-600">Sin opcionales disponibles por ahora.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 ms-cat-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bloque</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Categoría</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio mensual (MXN)</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($opcionalesDisponibles as $modulo): ?>
                                    <tr>
                                        <td data-label="Bloque" class="px-4 py-3 text-sm text-gray-900">
                                            <div class="min-w-0">
                                                <div class="font-medium"><?= htmlspecialchars($modulo['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="text-xs text-gray-500"><?= htmlspecialchars($modulo['clave'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php if (!empty($modulo['descripcion'])): ?>
                                                    <div class="mt-1 text-xs text-gray-500"><?= htmlspecialchars($modulo['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Categoría" class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($modulo['categoria'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td data-label="Precio mensual" class="px-4 py-3 text-sm">
                                            <div class="flex items-center gap-2">
                                                <label for="precio-mod-<?= (int) $modulo['id'] ?>" class="text-gray-500">$</label>
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                       id="precio-mod-<?= (int) $modulo['id'] ?>"
                                                       name="precios[<?= (int) $modulo['id'] ?>]"
                                                       value="<?= $fmtMoney($modulo['precio_mensual'] ?? 0) ?>"
                                                       class="ms-cat-input w-28 rounded-md border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                                            </div>
                                        </td>
                                        <td data-label="Estado" class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold" style="background:rgba(22,163,74,.12);color:var(--ms-success);">
                                                <i class="fas fa-circle-check text-[10px]" aria-hidden="true"></i>
                                                Disponible
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- E. Reportes individuales -->
                <div class="border-t border-gray-200 px-6 pt-4 pb-1">
                    <h3 class="text-sm font-semibold" style="color:var(--ms-text);">
                        <i class="fas fa-chart-line mr-1.5 text-xs" aria-hidden="true" style="color:var(--ms-muted);"></i>
                        Reportes individuales
                        <span class="ml-1 font-normal" style="color:var(--ms-muted);">(<?= count($reportesIndividuales) ?>)</span>
                    </h3>
                    <p class="mt-0.5 text-xs text-gray-500">Cada reporte analítico se contratará por separado. Ranking y comparativa de estados está incluido en Procedencia de huéspedes; no es un producto aparte.</p>
                </div>
                <?php if (empty($reportesIndividuales)): ?>
                    <div class="px-6 pb-5 text-sm text-gray-600">No hay reportes individuales pendientes: los desbloqueados aparecen arriba, entre los disponibles.</div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200 border-t border-gray-100">
                        <?php foreach ($reportesIndividuales as $modulo): ?>
                            <li class="flex flex-col gap-1.5 px-6 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($modulo['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($modulo['descripcion'])): ?>
                                        <div class="mt-0.5 text-xs text-gray-500"><?= htmlspecialchars($modulo['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($modulo['motivo_bloqueo'])): ?>
                                        <div class="mt-1 text-xs font-medium" style="color:var(--ms-warning);">
                                            <i class="fas fa-circle-info text-[10px] mr-1" aria-hidden="true"></i><?= htmlspecialchars($modulo['motivo_bloqueo'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-shrink-0 items-center gap-2 text-sm">
                                    <span class="text-gray-500">$<?= $fmtMoneyTexto($modulo['precio_mensual'] ?? 0) ?></span>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold" style="background:rgba(245,158,11,.12);color:#92400E;">
                                        <i class="fas fa-circle-pause text-[10px]" aria-hidden="true"></i>
                                        Pendiente de precio
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <!-- F. Pendientes de revision (contraida) -->
                <details class="border-t border-gray-200">
                    <summary class="ms-cat-summary">
                        <i class="fas fa-circle-pause text-sm" aria-hidden="true" style="color:var(--ms-warning);"></i>
                        <h3 class="min-w-0 flex-1 text-sm font-semibold" style="color:var(--ms-text);">
                            Pendientes de revisión
                            <span class="ml-1 font-normal" style="color:var(--ms-muted);">(<?= count($opcionalesPendientes) ?> módulos)</span>
                            <span class="block text-xs font-normal" style="color:var(--ms-muted);">Conservan su registro y precio, pero todavía no pueden contratarse ni cobrarse.</span>
                        </h3>
                        <i class="fas fa-chevron-down text-xs ms-cat-chevron" aria-hidden="true"></i>
                    </summary>
                    <?php if (empty($opcionalesPendientes)): ?>
                        <div class="px-6 py-5 text-sm text-gray-600">No hay módulos pendientes de revisión.</div>
                    <?php else: ?>
                        <div class="px-6 py-3 border-b border-gray-100">
                            <label for="catalogo-pendientes-buscar" class="sr-only">Buscar módulo pendiente</label>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <input type="search" id="catalogo-pendientes-buscar" autocomplete="off"
                                       placeholder="Buscar por nombre, clave, categoría o motivo"
                                       class="ms-cat-input w-full rounded-md border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900 sm:max-w-sm">
                                <p class="text-xs whitespace-nowrap" style="color:var(--ms-muted);"><span id="catalogo-pendientes-count"><?= count($opcionalesPendientes) ?></span> de <?= count($opcionalesPendientes) ?> módulos</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 ms-cat-table">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Módulo</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Categoría</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio de lista</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Motivo del bloqueo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($opcionalesPendientes as $modulo): ?>
                                        <tr data-pendiente-row
                                            data-pendiente-texto="<?= htmlspecialchars(strtolower(($modulo['nombre'] ?? '') . ' ' . ($modulo['clave'] ?? '') . ' ' . ($modulo['categoria'] ?? '') . ' ' . ($modulo['descripcion'] ?? '') . ' ' . ($modulo['motivo_bloqueo'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                            <td data-label="Módulo" class="px-4 py-3 text-sm">
                                                <div class="min-w-0">
                                                    <div class="font-medium text-gray-700"><?= htmlspecialchars($modulo['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($modulo['clave'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                            </td>
                                            <td data-label="Categoría" class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($modulo['categoria'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td data-label="Precio de lista" class="px-4 py-3 text-sm text-gray-500">$<?= $fmtMoneyTexto($modulo['precio_mensual'] ?? 0) ?></td>
                                            <td data-label="Motivo del bloqueo" class="px-4 py-3 text-sm">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold" style="background:rgba(100,116,139,.10);color:var(--ms-muted);">
                                                    <i class="fas fa-circle-pause text-[10px]" aria-hidden="true"></i>
                                                    No disponible aún
                                                </span>
                                                <div class="mt-1 text-xs" style="color:var(--ms-warning);"><?= htmlspecialchars($modulo['motivo_bloqueo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="catalogo-pendientes-vacio" class="hidden px-6 py-5 text-sm text-gray-600" role="status">
                            No hay módulos que coincidan con esa búsqueda.
                            <button type="button" id="catalogo-pendientes-limpiar" class="ml-1 font-medium underline" style="color:var(--ms-primary);">Limpiar búsqueda</button>
                        </div>
                    <?php endif; ?>
                </details>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-gray-500">El guardado aplica a los precios editables (bloques disponibles).</p>
                    <button type="submit" class="px-4 py-2 rounded-md text-white text-sm font-medium transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                        Guardar precios de bloques
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- G. Herramientas internas Medisoft (contraida) -->
    <?php if (!empty($modulosInternosCatalogo)): ?>
    <div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <details>
            <summary class="ms-cat-summary">
                <i class="fas fa-screwdriver-wrench text-sm" aria-hidden="true" style="color:var(--ms-muted);"></i>
                <h2 class="min-w-0 flex-1 text-base font-semibold text-gray-900">
                    Herramientas internas
                    <span class="ml-1 text-sm font-normal" style="color:var(--ms-muted);">(<?= count($modulosInternosCatalogo) ?>)</span>
                    <span class="block text-xs font-normal" style="color:var(--ms-muted);">Infraestructura Medisoft: no se vende, no se cobra al hotel y no aparece en su contratación.</span>
                </h2>
                <i class="fas fa-chevron-down text-xs ms-cat-chevron" aria-hidden="true"></i>
            </summary>
            <ul class="divide-y divide-gray-100">
                <?php foreach ($modulosInternosCatalogo as $modulo): ?>
                    <li class="flex flex-col gap-1 px-6 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-700"><?= htmlspecialchars($modulo['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($modulo['descripcion'])): ?>
                                <div class="mt-0.5 text-xs text-gray-500"><?= htmlspecialchars($modulo['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if (empty($modulo['activo_global']) && !empty($modulo['motivo_bloqueo'])): ?>
                                <div class="mt-1 text-xs" style="color:var(--ms-warning);"><?= htmlspecialchars($modulo['motivo_bloqueo'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-shrink-0 items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium" style="background:rgba(100,116,139,.08);color:var(--ms-muted);">
                                <i class="fas fa-screwdriver-wrench text-[10px]" aria-hidden="true"></i>
                                Uso interno
                            </span>
                            <?php if (empty($modulo['activo_global'])): ?>
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold" style="background:rgba(245,158,11,.12);color:#92400E;">
                                    <i class="fas fa-circle-pause text-[10px]" aria-hidden="true"></i>
                                    En pausa
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
    </div>
    <?php endif; ?>
</div>

<script>
(function () {
    // Busqueda client-side de "Pendientes de revision" (mismo patron del
    // buscador de bloques del detalle de hotel; sin solicitudes de red).
    var input = document.getElementById('catalogo-pendientes-buscar');
    if (!input) return;

    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-pendiente-row]'));
    var count = document.getElementById('catalogo-pendientes-count');
    var empty = document.getElementById('catalogo-pendientes-vacio');
    var limpiar = document.getElementById('catalogo-pendientes-limpiar');

    function filtrar() {
        var query = input.value.trim().toLocaleLowerCase('es-MX');
        var visibles = 0;

        rows.forEach(function (row) {
            var contenido = row.getAttribute('data-pendiente-texto') || '';
            var visible = query === '' || contenido.indexOf(query) !== -1;
            row.style.display = visible ? '' : 'none';
            if (visible) visibles++;
        });

        if (count) count.textContent = String(visibles);
        if (empty) empty.classList.toggle('hidden', !(query !== '' && visibles === 0));
    }

    input.addEventListener('input', filtrar);
    if (limpiar) limpiar.addEventListener('click', function () {
        input.value = '';
        filtrar();
        input.focus();
    });
})();
</script>
