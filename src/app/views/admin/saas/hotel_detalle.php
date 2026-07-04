<?php
$hotel = $hotel ?? [];
$usuariosHotel = $usuariosHotel ?? [];
$modulosHotel = $modulosHotel ?? [];
$planes = $planes ?? [];
$planActual = $planActual ?? null;
$modulosPorPlan = $modulosPorPlan ?? [];
$brandingHotel = $brandingHotel ?? [];
$auditoriaPlan = $auditoriaPlan ?? [
    'estado' => 'sin_plan',
    'mensaje' => 'No hay auditoria disponible.',
    'modulos_incluidos_apagados' => [],
    'modulos_activos_fuera_plan' => [],
];
$activo = !empty($hotel['activo']);
$usuariosAdminCount = count($usuariosHotel);
$modulosActivosCount = count(array_filter($modulosHotel, function ($modulo) {
    return !empty($modulo['activo_hotel']);
}));
$estadoAuditoria = $auditoriaPlan['estado'] ?? 'sin_plan';
$estadoStyle = [
    'consistente'   => 'background:rgba(22,163,74,.12);color:var(--ms-success);',
    'diferencias'   => 'background:rgba(245,158,11,.12);color:var(--ms-warning);',
    'personalizado' => 'background:rgba(37,99,235,.12);color:var(--ms-primary);',
    'sin_plan'      => 'background:rgba(100,116,139,.10);color:var(--ms-muted);',
][$estadoAuditoria] ?? 'background:rgba(100,116,139,.10);color:var(--ms-muted);';
$estadoTexto = [
    'consistente' => 'Consistente',
    'diferencias' => 'Con diferencias',
    'personalizado' => 'Personalizado',
    'sin_plan' => 'Sin plan',
][$estadoAuditoria] ?? 'No disponible';
$modulosApagados = $auditoriaPlan['modulos_incluidos_apagados'] ?? [];
$modulosFueraPlan = $auditoriaPlan['modulos_activos_fuera_plan'] ?? [];
$planBadgeStyle = function (string $clave): string {
    $map = [
        'basico'        => 'background:rgba(100,116,139,.12);color:var(--ms-muted);',
        'pro'           => 'background:rgba(37,99,235,.12);color:var(--ms-primary);',
        'premium'       => 'background:rgba(6,182,212,.15);color:var(--ms-accent);',
        'personalizado' => 'border:1px solid var(--ms-border);color:var(--ms-muted);',
    ];
    return $map[$clave] ?? 'background:rgba(100,116,139,.10);color:var(--ms-muted);';
};
$brandingOld = $_SESSION['old_input'] ?? [];
$brandingCampo = function ($key, $default = '') use ($brandingOld, $brandingHotel) {
    $value = array_key_exists($key, $brandingOld)
        ? $brandingOld[$key]
        : ($brandingHotel[$key] ?? $default);

    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};
$brandingActivo = array_key_exists('activo', $brandingOld)
    ? !empty($brandingOld['activo'])
    : (!isset($brandingHotel['activo']) || !empty($brandingHotel['activo']));
$brandingNombrePreview = $brandingHotel['nombre_visual'] ?? $hotel['nombre'] ?? 'Medisoft Hoteles';
$brandingLogoPreview = function_exists('hotel_branding_asset_url')
    ? hotel_branding_asset_url($brandingHotel['logo_url'] ?? null)
    : null;
$brandingFaviconPreview = function_exists('hotel_branding_asset_url')
    ? hotel_branding_asset_url($brandingHotel['favicon_url'] ?? null)
    : null;
$brandingLoginBgPreview = function_exists('hotel_branding_asset_url')
    ? hotel_branding_asset_url($brandingHotel['login_background_url'] ?? null)
    : null;
$brandingPwaIcon192Preview = function_exists('hotel_branding_pwa_icon_asset_url')
    ? hotel_branding_pwa_icon_asset_url($brandingHotel['pwa_icon_192_url'] ?? null, 192)
    : null;
$brandingPwaIcon512Preview = function_exists('hotel_branding_pwa_icon_asset_url')
    ? hotel_branding_pwa_icon_asset_url($brandingHotel['pwa_icon_512_url'] ?? null, 512)
    : null;
$copyVisible = function ($value) {
    return strtr((string) ($value ?? ''), [
        'Administracion' => 'Administración',
        'administracion' => 'administración',
        'Auditoria' => 'Auditoría',
        'auditoria' => 'auditoría',
        'Basico' => 'Básico',
        'basico' => 'básico',
        'Catalogo' => 'Catálogo',
        'catalogo' => 'catálogo',
        'Codigo' => 'Código',
        'codigo' => 'código',
        'Configuracion' => 'Configuración',
        'configuracion' => 'configuración',
        'Direccion' => 'Dirección',
        'direccion' => 'dirección',
        'Facturacion' => 'Facturación',
        'facturacion' => 'facturación',
        'Icono' => 'Ícono',
        'icono' => 'ícono',
        'Maximo' => 'Máximo',
        'maximo' => 'máximo',
        'Migracion' => 'Migración',
        'migracion' => 'migración',
        'Modulo' => 'Módulo',
        'modulo' => 'módulo',
        'Modulos' => 'Módulos',
        'modulos' => 'módulos',
        'Operacion' => 'Operación',
        'operacion' => 'operación',
        'Razon social' => 'Razón social',
        'Telefono' => 'Teléfono',
        'contrasena' => 'contraseña',
        'estatico' => 'estático',
        'menu' => 'menú',
        'seccion' => 'sección',
        'segun' => 'según',
        'validos' => 'válidos',
    ]);
};
$escapeCopy = function ($value) use ($copyVisible) {
    return htmlspecialchars($copyVisible($value), ENT_QUOTES, 'UTF-8');
};
$fila = function ($label, $value) {
    $value = $value === null || $value === '' ? '-' : $value;
    ?>
    <div class="py-3 grid grid-cols-1 sm:grid-cols-3 gap-1 border-b border-gray-100">
        <dt class="text-sm font-medium text-gray-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
        <dd class="sm:col-span-2 text-sm text-gray-900"><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></dd>
    </div>
    <?php
};
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold" style="color:var(--ms-text);"><?= htmlspecialchars($hotel['nombre'] ?? 'Hotel', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mt-1 text-sm" style="color:var(--ms-muted);">Ficha de configuración SaaS del cliente.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= back_url('admin/saas/hoteles') ?>" class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-white">
                Volver
            </a>
            <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar') ?>" class="px-4 py-2 rounded-md text-white text-sm font-medium transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
               style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                Editar
            </a>
        </div>
    </div>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div class="mb-4 rounded-md border px-4 py-3 text-sm <?= $tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">¿Qué quieres hacer con este hotel?</p>
            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-5">
                <?php
                $mapaPasos = [
                    ['#resumen',  '1', 'Datos del hotel', 'Ficha, contacto y estado'],
                    ['#plan',     '2', 'Plan',            'Combo comercial contratado'],
                    ['#modulos',  '3', 'Bloques y cobro', 'Qué paga y cuánto al mes'],
                    ['#usuarios', '4', 'Accesos',         'Quién puede entrar'],
                    ['#branding', '5', 'Marca y PWA',     'Logo, colores e íconos'],
                ];
                ?>
                <?php foreach ($mapaPasos as [$mapaHref, $mapaNum, $mapaTitulo, $mapaDesc]): ?>
                    <a href="<?= $mapaHref ?>" class="flex items-start gap-2.5 rounded-md border px-3 py-2.5 transition hover:bg-slate-50" style="border-color:var(--ms-border);">
                        <span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white" style="background:var(--ms-primary);"><?= $mapaNum ?></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold leading-tight" style="color:var(--ms-text);"><?= $mapaTitulo ?></span>
                            <span class="mt-0.5 block text-[11px] leading-tight" style="color:var(--ms-muted);"><?= $mapaDesc ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="grid grid-cols-1 divide-y md:grid-cols-5 md:divide-x md:divide-y-0" style="border-color:var(--ms-border);">
            <div class="px-5 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Estado</div>
                <div class="mt-1.5">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="<?= $activo ? 'background:rgba(22,163,74,.12);color:var(--ms-success);' : 'background:rgba(100,116,139,.12);color:var(--ms-muted);' ?>">
                        <?= $activo ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>
            </div>
            <div class="px-5 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Plan</div>
                <div class="mt-1.5">
                    <?php $planClaveStrip = $planActual['clave'] ?? 'sin_plan'; ?>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="<?= $planBadgeStyle($planClaveStrip) ?>">
                        <?= $escapeCopy($planActual['nombre'] ?? 'Sin plan') ?>
                    </span>
                </div>
            </div>
            <div class="px-5 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Cobro estimado</div>
                <div class="mt-1.5 text-sm font-semibold" style="color:var(--ms-text);">
                    <?php if (isset($resumenCobro['total'])): ?>
                        $<?= number_format((float) $resumenCobro['total'], 2) ?> <span class="font-normal" style="color:var(--ms-muted);">/ mes</span>
                    <?php else: ?>
                        <span class="font-normal" style="color:var(--ms-muted);">—</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="px-5 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Bloques extra</div>
                <?php $bloquesExtraActivos = count(array_filter($modulosHotel, function ($m) {
                    return empty($m['es_core']) && !empty($m['activo_hotel']);
                })); ?>
                <div class="mt-1.5 text-sm font-semibold" style="color:var(--ms-text);"><?= (int) $bloquesExtraActivos ?> <span class="font-normal" style="color:var(--ms-muted);">contratados</span></div>
            </div>
            <div class="px-5 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Admins hotel</div>
                <div class="mt-1.5 text-sm font-semibold" style="color:var(--ms-text);"><?= (int) $usuariosAdminCount ?> <span class="font-normal" style="color:var(--ms-muted);">vinculados</span></div>
            </div>
        </div>
    </div>

    <?php $cobrosHotel = $cobrosHotel ?? []; ?>
    <?php if (!empty($cobrosHotel)): ?>
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Estado de cuenta</h2>
            <p class="text-sm text-gray-500">Cobros mensuales de este hotel (los gestionas en <a href="<?= url('admin/saas/cobros') ?>" class="underline">Cobros</a>).</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y" style="border-color:var(--ms-border);">
                <thead style="background:var(--ms-bg);">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Periodo</th>
                        <th class="px-5 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Monto</th>
                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Estado</th>
                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Pago</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color:var(--ms-border);">
                <?php
                $badgeCuenta = static function ($estado) {
                    $map = [
                        'pagado' => ['Pagado', 'background:rgba(22,163,74,.12);color:var(--ms-success);'],
                        'pendiente' => ['Pendiente', 'background:rgba(245,158,11,.14);color:#92600A;'],
                        'vencido' => ['Vencido', 'background:rgba(220,38,38,.12);color:#B91C1C;'],
                        'cancelado' => ['Cancelado', 'background:rgba(100,116,139,.12);color:var(--ms-muted);'],
                    ];
                    return $map[$estado] ?? [$estado, ''];
                };
                foreach ($cobrosHotel as $cc): [$ccTxt, $ccStyle] = $badgeCuenta($cc['estado']); ?>
                    <tr>
                        <td class="px-5 py-3 text-sm font-medium" style="color:var(--ms-text);"><?= htmlspecialchars((string) $cc['periodo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-5 py-3 text-right text-sm font-semibold" style="color:var(--ms-text);">$<?= number_format((float) $cc['monto'], 2) ?></td>
                        <td class="px-5 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="<?= $ccStyle ?>"><?= htmlspecialchars($ccTxt, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="px-5 py-3 text-xs" style="color:var(--ms-muted);">
                            <?= $cc['metodo'] ? htmlspecialchars((string) $cc['metodo'], ENT_QUOTES, 'UTF-8') : '—' ?><?= $cc['pagado_at'] ? ' · ' . htmlspecialchars(date('d/m/Y', strtotime((string) $cc['pagado_at'])), ENT_QUOTES, 'UTF-8') : '' ?>
                            <?= (!$cc['pagado_at'] && !empty($cc['vence_at']) && in_array($cc['estado'], ['pendiente', 'vencido'], true)) ? 'vence ' . htmlspecialchars(date('d/m/Y', strtotime((string) $cc['vence_at'])), ENT_QUOTES, 'UTF-8') : '' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div id="resumen" class="scroll-mt-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:var(--ms-primary);">1</span>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Datos del hotel</h2>
                    <p class="text-sm text-gray-500">ID <?= (int) ($hotel['id'] ?? 0) ?> · Slug <?= htmlspecialchars($hotel['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1 text-sm font-semibold" style="<?= $activo ? 'background:rgba(22,163,74,.12);color:var(--ms-success);' : 'background:rgba(100,116,139,.12);color:var(--ms-muted);' ?>">
                <?= $activo ? 'Activo' : 'Inactivo' ?>
            </span>
        </div>

        <dl class="px-6 py-2">
            <?php
            $fila('Nombre comercial', $hotel['nombre'] ?? null);
            $fila('Slug', $hotel['slug'] ?? null);
            $fila('Código interno', $hotel['codigo'] ?? null);
            $fila('Razón social', $hotel['razon_social'] ?? null);
            $fila('RFC', $hotel['rfc'] ?? null);
            $fila('Teléfono', $hotel['telefono'] ?? null);
            $fila('Email', $hotel['email'] ?? null);
            $fila('Dirección', $hotel['direccion'] ?? null);
            $fila('Ciudad', $hotel['ciudad'] ?? null);
            $fila('Estado / región', $hotel['estado'] ?? null);
            $fila('País', $hotel['pais'] ?? null);
            $fila('Zona horaria', $hotel['zona_horaria'] ?? null);
            $fila('Moneda', trim(($hotel['moneda_codigo'] ?? '') . ' ' . ($hotel['moneda_simbolo'] ?? '')));
            $fila('Plan comercial', $copyVisible($planActual['nombre'] ?? 'Sin plan'));
            $fila('Motor de reservas (link publico)', url('h/' . ($hotel['slug'] ?? '') . '/reservar'));
            $fila('Creado', $hotel['created_at'] ?? null);
            $fila('Actualizado', $hotel['updated_at'] ?? null);
            ?>
        </dl>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/estado') ?>" data-ms-confirm data-ms-type="<?= $activo ? 'error' : 'success' ?>" data-ms-icon="<?= $activo ? 'logout' : 'login' ?>" data-ms-title="<?= $activo ? '¿Suspender hotel?' : '¿Activar hotel?' ?>" data-ms-msg="<?= $activo ? 'El hotel quedará suspendido y sus usuarios no podrán entrar al sistema.' : 'El hotel quedará activo y sus usuarios podrán volver a entrar.' ?>" data-ms-ok="<?= $activo ? 'Suspender hotel' : 'Activar hotel' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="activo" value="<?= $activo ? '0' : '1' ?>">
                <button type="submit"
                        class="px-4 py-2 rounded-md text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                        style="<?= $activo ? 'background:var(--ms-danger);--tw-ring-color:var(--ms-danger);' : 'background:var(--ms-success);--tw-ring-color:var(--ms-success);' ?>">
                    <?= $activo ? 'Suspender hotel' : 'Activar hotel' ?>
                </button>
            </form>
        </div>
    </div>

    <div id="plan" class="mt-6 scroll-mt-6">
        <div class="mb-3 flex items-start gap-3">
            <span class="mt-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:var(--ms-primary);">2</span>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Plan</h2>
                <p class="mt-1 text-sm text-gray-500">El plan es solo un combo sugerido de bloques. Lo que el hotel realmente tiene encendido (y lo que paga) se controla en el paso 3.</p>
            </div>
        </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">

        <?php if (empty($planes)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">
                No hay catálogo de planes disponible. Aplique la migración de planes antes de configurar esta sección.
            </div>
        <?php else: ?>
            <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/plan') ?>" id="form-plan-hotel">
                <?= csrf_field() ?>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div class="lg:col-span-1">
                        <label for="plan_id" class="block text-sm font-medium text-gray-700">Plan actual</label>
                        <select id="plan_id" name="plan_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            <?php foreach ($planes as $plan): ?>
                                <option value="<?= (int) $plan['id'] ?>" <?= !empty($planActual['id']) && (int) $planActual['id'] === (int) $plan['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plan['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="mt-4 flex items-start gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="aplicar_modulos" value="1"
                                   class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <span>
                                Aplicar módulos sugeridos por el plan.
                                <span class="block text-xs text-amber-700">
                                    Esto puede activar o desactivar módulos según el preset seleccionado.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach ($planes as $plan): ?>
                                <?php
                                $planId = (int) $plan['id'];
                                $clavesPlan = array_column($modulosPorPlan[$planId] ?? [], 'clave');
                                ?>
                                <div class="rounded-md border p-4 transition hover:shadow-sm" style="border-color:var(--ms-border);">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-semibold" style="color:var(--ms-text);"><?= $escapeCopy($plan['nombre'] ?? '') ?></h3>
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold" style="<?= $planBadgeStyle($plan['clave'] ?? '') ?>">
                                                <?= htmlspecialchars(ucfirst($plan['clave'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($planActual['id']) && (int) $planActual['id'] === $planId): ?>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background:rgba(22,163,74,.12);color:var(--ms-success);">Actual</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-1 text-xs" style="color:var(--ms-muted);"><?= $escapeCopy($plan['descripcion'] ?? '') ?></p>
                                    <?php if (($plan['clave'] ?? '') === 'personalizado'): ?>
                                        <p class="mt-3 text-xs" style="color:var(--ms-muted);">Sin combo fijo: se arma bloque por bloque en el paso 3.</p>
                                    <?php else: ?>
                                        <p class="mt-2 text-sm font-semibold" style="color:var(--ms-text);">
                                            <?= $plan['precio_mensual'] !== null ? '$' . number_format((float) $plan['precio_mensual'], 2) . ' <span class="text-xs font-normal" style="color:var(--ms-muted);">/ mes</span>' : '' ?>
                                        </p>
                                        <p class="mt-2 text-xs" style="color:var(--ms-muted);">Incluye <?= count($clavesPlan) ?> bloques: <?= $escapeCopy(implode(', ', $clavesPlan)) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-md text-white text-sm font-medium transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                        Guardar plan
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($estadoAuditoria === 'diferencias' && !empty($planActual['id'])): ?>
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-5 py-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-triangle-exclamation mt-0.5 text-amber-600"></i>
                <div class="min-w-0 text-sm text-amber-900">
                    <p class="font-semibold">Lo contratado no coincide con el plan <?= $escapeCopy($planActual['nombre'] ?? '') ?>.</p>
                    <?php if (!empty($modulosApagados)): ?>
                        <p class="mt-1">El plan los incluye pero están apagados: <span class="font-medium"><?= $escapeCopy(implode(', ', array_map(function ($m) { return $m['nombre'] ?? $m['clave'] ?? ''; }, $modulosApagados))) ?></span>.</p>
                    <?php endif; ?>
                    <?php if (!empty($modulosFueraPlan)): ?>
                        <p class="mt-1">Están encendidos aunque el plan no los incluye: <span class="font-medium"><?= $escapeCopy(implode(', ', array_map(function ($m) { return $m['nombre'] ?? $m['clave'] ?? ''; }, $modulosFueraPlan))) ?></span>.</p>
                    <?php endif; ?>
                    <p class="mt-1 text-xs text-amber-800">Esto no es un error: puedes dejarlo así (se cobra lo encendido) o alinear los bloques al plan.</p>
                </div>
                <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/plan') ?>" class="flex-shrink-0" data-ms-confirm data-ms-type="warning" data-ms-icon="alert" data-ms-title="¿Alinear bloques al plan?" data-ms-msg="Esto encenderá/apagará bloques para coincidir con el plan actual." data-ms-ok="Alinear al plan">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan_id" value="<?= (int) $planActual['id'] ?>">
                    <input type="hidden" name="aplicar_modulos" value="1">
                    <button type="submit" class="px-3 py-2 rounded-md bg-amber-700 text-white text-xs font-medium hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                        Alinear al plan
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    </div>

    <div id="modulos" class="mt-6 scroll-mt-6">
        <div class="mb-3 flex items-start gap-3">
            <span class="mt-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:var(--ms-primary);">3</span>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Bloques y cobro mensual</h2>
                <p class="mt-1 text-sm text-gray-500">Palomea lo que este hotel contrata. Cada bloque enciende su sección en el sistema del hotel y suma a su cobro mensual.</p>
            </div>
        </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">

        <?php if (empty($modulosHotel)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">
                No hay catálogo de módulos disponible. Aplique la migración de módulos antes de configurar esta sección.
            </div>
        <?php else: ?>
            <?php
            $precioBaseCobro = isset($resumenCobro['precio_base']) ? (float) $resumenCobro['precio_base'] : 0.0;
            $monedaHotel = $hotel['moneda_codigo'] ?? 'MXN';
            $modulosBasicos = array_filter($modulosHotel, function ($m) { return !empty($m['es_core']); });
            $modulosOpcionalesHotel = array_filter($modulosHotel, function ($m) { return empty($m['es_core']); });
            ?>

            <div class="px-6 py-4 border-b border-gray-200" style="background:rgba(37,99,235,.04);">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold" style="color:var(--ms-text);"><i class="fas fa-lock text-xs mr-1.5" style="color:var(--ms-primary);"></i>Paquete básico — siempre incluido</p>
                        <p class="mt-0.5 text-xs text-gray-500">Lo mínimo para operar un hotel. No se puede apagar y ya está cubierto por el precio base.</p>
                    </div>
                    <span class="text-sm font-semibold" style="color:var(--ms-primary);">$<?= number_format($precioBaseCobro, 2) ?> / mes</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <?php foreach ($modulosBasicos as $modulo): ?>
                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" style="border-color:var(--ms-border);background:#fff;color:var(--ms-text);">
                            <?= $escapeCopy($modulo['nombre'] ?? '') ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/modulos') ?>" id="form-modulos-hotel">
                <?= csrf_field() ?>

                <div class="px-6 pt-4">
                    <p class="text-sm font-semibold" style="color:var(--ms-text);">Bloques opcionales</p>
                    <p class="mt-0.5 text-xs text-gray-500">Palomeado = el hotel lo tiene y lo paga. "Precio especial" solo si acordaste un precio distinto al de lista para este hotel.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 mt-3">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contratado</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bloque</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio de lista</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio especial</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($modulosOpcionalesHotel as $modulo): ?>
                                <?php
                                $globalActivo = !empty($modulo['activo_global']);
                                $precioCatalogo = (float) ($modulo['precio_mensual'] ?? 0);
                                $precioOverride = $modulo['precio_override'] ?? null;
                                $precioAplicado = $precioOverride !== null && $precioOverride !== '' ? (float) $precioOverride : $precioCatalogo;
                                ?>
                                <tr class="<?= $globalActivo ? '' : 'bg-gray-50 text-gray-400' ?>">
                                    <td class="px-4 py-3 text-sm">
                                        <input type="checkbox"
                                               name="modulos[]"
                                               value="<?= (int) $modulo['id'] ?>"
                                               data-precio="<?= number_format($precioAplicado, 2, '.', '') ?>"
                                               <?= !empty($modulo['activo_hotel']) ? 'checked' : '' ?>
                                               <?= $globalActivo ? '' : 'disabled' ?>
                                               class="modulo-toggle h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium"><?= $escapeCopy($modulo['nombre'] ?? '') ?></span>
                                            <?php if (!$globalActivo): ?>
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background:rgba(100,116,139,.10);color:var(--ms-muted);">No disponible aún</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($modulo['descripcion'])): ?>
                                            <div class="mt-1 text-xs text-gray-500"><?= $escapeCopy($modulo['descripcion']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">$<?= number_format($precioCatalogo, 2) ?> <span class="text-xs text-gray-400">/ mes</span></td>
                                    <td class="px-4 py-3 text-sm">
                                        <input type="number" step="0.01" min="0"
                                               name="precio_override[<?= (int) $modulo['id'] ?>]"
                                               value="<?= $precioOverride !== null && $precioOverride !== '' ? number_format((float) $precioOverride, 2, '.', '') : '' ?>"
                                               placeholder="<?= number_format($precioCatalogo, 2, '.', '') ?>"
                                               data-modulo="<?= (int) $modulo['id'] ?>"
                                               data-precio-catalogo="<?= number_format($precioCatalogo, 2, '.', '') ?>"
                                               class="precio-override w-24 rounded-md border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900"
                                               <?= $globalActivo ? '' : 'disabled' ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm" style="color:var(--ms-text);">
                        <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Cobro mensual estimado</div>
                        <div class="mt-0.5">
                            Paquete básico $<span id="cobro-base"><?= number_format($precioBaseCobro, 2) ?></span>
                            + bloques $<span id="cobro-modulos">0.00</span>
                            = <span class="text-lg font-bold">$<span id="cobro-total">0.00</span> <?= htmlspecialchars($monedaHotel, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="text-xs" style="color:var(--ms-muted);">Se recalcula al activar/desactivar bloques o cambiar precios especiales. Precios editables en <a href="<?= url('admin/saas/modulos') ?>" class="underline">Bloques y precios</a>.</div>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md text-white text-sm font-medium transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                        Guardar bloques y cobro
                    </button>
                </div>
            </form>

            <script>
            (function () {
                var form = document.getElementById('form-modulos-hotel');
                if (!form) return;
                var base = <?= json_encode(round($precioBaseCobro, 2)) ?>;

                function precioDeModulo(toggle) {
                    var override = form.querySelector('.precio-override[data-modulo="' + toggle.value + '"]');
                    if (override && override.value !== '' && !isNaN(parseFloat(override.value))) {
                        return Math.max(0, parseFloat(override.value));
                    }
                    return parseFloat(toggle.getAttribute('data-precio')) || 0;
                }

                function recalcular() {
                    var totalModulos = 0;
                    form.querySelectorAll('.modulo-toggle:checked:not(:disabled)').forEach(function (toggle) {
                        totalModulos += precioDeModulo(toggle);
                    });
                    var fmt = function (n) { return n.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };
                    document.getElementById('cobro-modulos').textContent = fmt(totalModulos);
                    document.getElementById('cobro-total').textContent = fmt(base + totalModulos);
                }

                form.addEventListener('change', recalcular);
                form.addEventListener('input', recalcular);
                recalcular();
            })();
            </script>
        <?php endif; ?>
    </div>
    </div>

    <div id="usuarios" class="mt-6 scroll-mt-6">
        <div class="mb-3 flex items-start gap-3">
            <span class="mt-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:var(--ms-primary);">4</span>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Accesos del hotel</h2>
                <p class="mt-1 text-sm text-gray-500">Quién puede entrar al sistema de este hotel y con qué rol.</p>
            </div>
        </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Usuarios vinculados</h3>
            <p class="text-sm text-gray-500">Usuarios vinculados a este hotel para acceso hotel-aware.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Usuario</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rol hotel</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rol global</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Principal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($usuariosHotel)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-sm text-gray-600 text-center">
                                Este hotel aún no tiene usuarios administradores vinculados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuariosHotel as $usuarioHotel): ?>
                            <?php
                            $hotelUsuarioActivo = !empty($usuarioHotel['hotel_usuario_activo']);
                            $usuarioActivo = !empty($usuarioHotel['usuario_activo']);
                            ?>
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="font-medium"><?= htmlspecialchars($usuarioHotel['nombre_completo'] ?: $usuarioHotel['nombre_usuario'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($usuarioHotel['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($usuarioHotel['email'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($usuarioHotel['rol_hotel'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($usuarioHotel['rol_global'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" style="<?= ($hotelUsuarioActivo && $usuarioActivo) ? 'background:rgba(22,163,74,.12);color:var(--ms-success);' : 'background:rgba(100,116,139,.10);color:var(--ms-muted);' ?>">
                                        <?= ($hotelUsuarioActivo && $usuarioActivo) ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?= !empty($usuarioHotel['es_principal']) ? 'Sí' : 'No' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/usuarios') ?>" class="border-t border-gray-200 bg-gray-50">
            <?= csrf_field() ?>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="nombre_usuario" class="block text-sm font-medium text-gray-700">Usuario *</label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" required maxlength="80"
                           value="<?= old('nombre_usuario') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                    <p class="mt-1 text-xs text-gray-500">Si ya existe, se vincula al hotel sin cambiar su contraseña.</p>
                </div>

                <div>
                    <label for="rol_hotel" class="block text-sm font-medium text-gray-700">Rol hotelero *</label>
                    <select id="rol_hotel" name="rol_hotel" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        <option value="administrador" <?= old('rol_hotel', 'administrador') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                        <option value="gerente" <?= old('rol_hotel') === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                    </select>
                </div>

                <div>
                    <label for="nombre_completo" class="block text-sm font-medium text-gray-700">Nombre completo</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" maxlength="150"
                           value="<?= old('nombre_completo') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" maxlength="120"
                           value="<?= old('email') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña temporal</label>
                    <input type="password" id="password" name="password" minlength="10" autocomplete="new-password"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div class="flex flex-col justify-end gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="es_principal" value="1" <?= old('es_principal') ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        Marcar como principal
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="activo" value="1" <?= old('activo', '1') ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        Vínculo activo
                    </label>
                </div>
            </div>

            <div class="px-6 py-4 bg-white border-t border-gray-200 flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-md text-white text-sm font-medium transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                        style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                    Crear o vincular administrador
                </button>
            </div>
        </form>
    </div>
    </div>
    <div id="branding" class="mt-6 scroll-mt-6">
        <div class="mb-3 flex items-start gap-3">
            <span class="mt-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:var(--ms-primary);">5</span>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Marca y PWA</h2>
                <p class="mt-1 text-sm text-gray-500">Cómo se ve el sistema para este hotel: logo, colores, fondo de login e íconos de la app instalable.</p>
            </div>
        </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Configuración visual</h3>
            <p class="text-sm text-gray-500">Identidad visual controlada para login y layout hotelero. No permite CSS, HTML ni JavaScript libre.</p>
        </div>

        <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/branding') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="nombre_visual" class="block text-sm font-medium text-gray-700">Nombre visual</label>
                        <input type="text" id="nombre_visual" name="nombre_visual" maxlength="150"
                               value="<?= $brandingCampo('nombre_visual', $hotel['nombre'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="Hotel Demo SaaS">
                    </div>

                    <div>
                        <label for="color_primary" class="block text-sm font-medium text-gray-700">Color primario</label>
                        <input type="text" id="color_primary" name="color_primary"
                               value="<?= $brandingCampo('color_primary') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="#0F766E">
                    </div>

                    <div>
                        <label for="color_secondary" class="block text-sm font-medium text-gray-700">Color secundario</label>
                        <input type="text" id="color_secondary" name="color_secondary"
                               value="<?= $brandingCampo('color_secondary') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="#115E59">
                    </div>

                    <div>
                        <label for="color_accent" class="block text-sm font-medium text-gray-700">Color acento</label>
                        <input type="text" id="color_accent" name="color_accent"
                               value="<?= $brandingCampo('color_accent') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="#F59E0B">
                    </div>

                    <div>
                        <label for="sidebar_style" class="block text-sm font-medium text-gray-700">Estilo sidebar</label>
                        <select id="sidebar_style" name="sidebar_style"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            <?php foreach (['default' => 'Default', 'solid' => 'Sólido', 'dark' => 'Oscuro'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($brandingHotel['sidebar_style'] ?? 'default') === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="logo_url" class="block text-sm font-medium text-gray-700">Logo URL/ruta</label>
                        <input type="text" id="logo_url" name="logo_url"
                               value="<?= $brandingCampo('logo_url') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="/img/logo.png">
                        <label for="logo_file" class="mt-3 block text-sm font-medium text-gray-700">Subir logo</label>
                        <input type="file" id="logo_file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-[var(--ms-primary)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[var(--ms-primary-hover)]">
                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, JPEG o WebP. Máximo 2 MB. No SVG.</p>
                    </div>

                    <div>
                        <label for="favicon_url" class="block text-sm font-medium text-gray-700">Favicon URL/ruta</label>
                        <input type="text" id="favicon_url" name="favicon_url"
                               value="<?= $brandingCampo('favicon_url') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="/img/favicon.png">
                        <label for="favicon_file" class="mt-3 block text-sm font-medium text-gray-700">Subir favicon</label>
                        <input type="file" id="favicon_file" name="favicon_file" accept=".ico,.png,image/x-icon,image/vnd.microsoft.icon,image/png"
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-[var(--ms-primary)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[var(--ms-primary-hover)]">
                        <p class="mt-1 text-xs text-gray-500">ICO o PNG. Máximo 512 KB.</p>
                    </div>

                    <div>
                        <label for="login_background_url" class="block text-sm font-medium text-gray-700">Fondo login URL/ruta</label>
                        <input type="text" id="login_background_url" name="login_background_url"
                               value="<?= $brandingCampo('login_background_url') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="/uploads/branding/hotel/fondo.webp">
                        <label for="login_background_file" class="mt-3 block text-sm font-medium text-gray-700">Subir fondo login</label>
                        <input type="file" id="login_background_file" name="login_background_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-[var(--ms-primary)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[var(--ms-primary-hover)]">
                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, JPEG o WebP. Máximo 4 MB. No SVG.</p>
                    </div>

                    <div>
                        <label for="pwa_icon_192_url" class="block text-sm font-medium text-gray-700">Ícono PWA 192 URL/ruta</label>
                        <input type="text" id="pwa_icon_192_url" name="pwa_icon_192_url"
                               value="<?= $brandingCampo('pwa_icon_192_url') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="/uploads/branding/hotel/pwa-icons/icon-192.png">
                        <label for="pwa_icon_192_file" class="mt-3 block text-sm font-medium text-gray-700">Subir ícono PWA 192x192</label>
                        <input type="file" id="pwa_icon_192_file" name="pwa_icon_192_file" accept=".png,.webp,image/png,image/webp"
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-[var(--ms-primary)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[var(--ms-primary-hover)]">
                        <p class="mt-1 text-xs text-gray-500">PNG o WebP. Exactamente 192x192 px. Máximo 1 MB. No SVG.</p>
                    </div>

                    <div>
                        <label for="pwa_icon_512_url" class="block text-sm font-medium text-gray-700">Ícono PWA 512 URL/ruta</label>
                        <input type="text" id="pwa_icon_512_url" name="pwa_icon_512_url"
                               value="<?= $brandingCampo('pwa_icon_512_url') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="/uploads/branding/hotel/pwa-icons/icon-512.png">
                        <label for="pwa_icon_512_file" class="mt-3 block text-sm font-medium text-gray-700">Subir ícono PWA 512x512</label>
                        <input type="file" id="pwa_icon_512_file" name="pwa_icon_512_file" accept=".png,.webp,image/png,image/webp"
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-[var(--ms-primary)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[var(--ms-primary-hover)]">
                        <p class="mt-1 text-xs text-gray-500">PNG o WebP. Exactamente 512x512 px. Máximo 1 MB. No SVG.</p>
                    </div>

                    <div>
                        <label for="login_style" class="block text-sm font-medium text-gray-700">Estilo login</label>
                        <select id="login_style" name="login_style"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            <?php foreach (['default' => 'Default', 'soft' => 'Suave', 'image' => 'Con imagen'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($brandingHotel['login_style'] ?? 'default') === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <label class="mt-6 flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="activo" value="1"
                               <?= $brandingActivo ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        Branding activo
                    </label>
                </div>

                <div class="lg:col-span-1">
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div style="background:linear-gradient(135deg, <?= htmlspecialchars($brandingHotel['color_primary'] ?? '#9CA777', ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($brandingHotel['color_secondary'] ?? '#7A8B5C', ENT_QUOTES, 'UTF-8') ?>);" class="px-4 py-8 text-center text-white">
                            <?php if ($brandingLogoPreview): ?>
                                <img src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($brandingNombrePreview, ENT_QUOTES, 'UTF-8') ?>" class="mx-auto h-16 w-16 rounded-full bg-white object-contain p-2">
                            <?php else: ?>
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-2xl font-bold">
                                    <?= htmlspecialchars(strtoupper(substr((string) $brandingNombrePreview, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                            <div class="mt-3 text-sm font-semibold"><?= htmlspecialchars($brandingNombrePreview, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="mt-1 text-xs opacity-80">Vista previa básica</div>
                        </div>
                        <div class="p-4 text-xs text-gray-600">
                            Los valores se imprimen como CSS variables sanitizadas:
                            <span class="font-mono">--brand-primary</span>,
                            <span class="font-mono">--brand-secondary</span> y
                            <span class="font-mono">--brand-accent</span>.
                        </div>
                        <div class="border-t border-gray-200 p-4">
                            <div class="grid grid-cols-2 gap-3 text-xs text-gray-600">
                                <div>
                                    <div class="font-semibold text-gray-700">Favicon</div>
                                    <?php if ($brandingFaviconPreview): ?>
                                        <img src="<?= htmlspecialchars($brandingFaviconPreview, ENT_QUOTES, 'UTF-8') ?>" alt="Favicon" class="mt-2 h-8 w-8 object-contain">
                                    <?php else: ?>
                                        <div class="mt-2 text-gray-400">Sin favicon</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-700">Fondo login</div>
                                    <?php if ($brandingLoginBgPreview): ?>
                                        <div class="mt-2 h-12 rounded bg-cover bg-center" style="background-image:url('<?= htmlspecialchars($brandingLoginBgPreview, ENT_QUOTES, 'UTF-8') ?>')"></div>
                                    <?php else: ?>
                                        <div class="mt-2 text-gray-400">Sin fondo</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 p-4">
                            <div class="font-semibold text-gray-700 text-xs">Íconos PWA del manifest</div>
                            <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-gray-600">
                                <div>
                                    <div class="font-medium text-gray-700">192x192</div>
                                    <?php if ($brandingPwaIcon192Preview): ?>
                                        <img src="<?= htmlspecialchars($brandingPwaIcon192Preview, ENT_QUOTES, 'UTF-8') ?>" alt="Ícono PWA 192" class="mt-2 h-12 w-12 rounded object-contain">
                                    <?php else: ?>
                                        <div class="mt-2 text-gray-400">Fallback estático</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-700">512x512</div>
                                    <?php if ($brandingPwaIcon512Preview): ?>
                                        <img src="<?= htmlspecialchars($brandingPwaIcon512Preview, ENT_QUOTES, 'UTF-8') ?>" alt="Ícono PWA 512" class="mt-2 h-12 w-12 rounded object-contain">
                                    <?php else: ?>
                                        <div class="mt-2 text-gray-400">Fallback estático</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="mt-3 text-xs text-gray-500">El manifest usa íconos del hotel solo cuando existen 192 y 512 válidos.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-md text-white text-sm font-medium transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                        style="background:var(--ms-primary);--tw-ring-color:var(--ms-primary);">
                    Guardar branding
                </button>
            </div>
        </form>
    </div>
    </div>

</div>

<script>
// Confirmar guardado del plan solo cuando se aplicará el preset de módulos.
(function () {
    var form = document.getElementById('form-plan-hotel');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        var aplicar = form.querySelector('[name=aplicar_modulos]');
        if (!aplicar || !aplicar.checked) return;
        if (form.dataset.msOk === '1') { delete form.dataset.msOk; return; }
        e.preventDefault();
        msConfirm({
            type: 'warning',
            icon: 'alert',
            title: '¿Aplicar preset del plan?',
            msg: 'Guardar este plan aplicando el preset puede activar o desactivar módulos del hotel.',
            confirmLabel: 'Guardar y aplicar'
        }).then(function (ok) {
            if (!ok) return;
            form.dataset.msOk = '1';
            if (form.requestSubmit) form.requestSubmit();
            else form.submit();
        });
    });
})();
</script>
