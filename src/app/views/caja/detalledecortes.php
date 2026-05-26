<?php
/**
 * Vista de Detalle de Corte
 * Los Cedros
 */
?>

<!-- Detalle de Corte -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
    <!-- Header -->
    <div class="max-w-6xl mx-auto mb-6">
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border-t-4 border-indigo-500">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-hotel-brown font-playfair mb-2">
                        Detalle de Corte #<?= $corte['id'] ?>
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                        <span class="flex items-center">
                            <i class="fas fa-cash-register mr-2"></i>
                            <?= htmlspecialchars($corte['caja_nombre']) ?>
                        </span>
                        <span class="text-gray-400">•</span>
                        <span class="flex items-center">
                            <i class="fas fa-calendar mr-2"></i>
                            <?= date('d/m/Y', strtotime($corte['fecha_apertura'])) ?>
                        </span>
                        <span class="text-gray-400">•</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            <?= $corte['estado'] == 'cerrado' ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800' ?>">
                            <i class="fas fa-<?= $corte['estado'] == 'cerrado' ? 'lock' : 'lock-open' ?> mr-1"></i>
                            <?= ucfirst($corte['estado']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <a href="<?= url('caja/historial') ?>" 
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        <span>Historial</span>
                    </a>
                    <button onclick="imprimirCorte()" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        <span>Imprimir</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Información General -->
    <div class="max-w-6xl mx-auto mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Datos de Apertura -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-unlock text-green-500 mr-2"></i>
                    Apertura de Caja
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b">
                        <span class="text-gray-600">Fecha y Hora:</span>
                        <span class="font-medium">
                            <?= date('d/m/Y H:i:s', strtotime($corte['fecha_apertura'])) ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b">
                        <span class="text-gray-600">Usuario:</span>
                        <span class="font-medium"><?= htmlspecialchars($corte['usuario_apertura']) ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600">Monto Inicial:</span>
                        <span class="font-bold text-lg">$<?= number_format($corte['monto_inicial'], 2) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Datos de Cierre -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-lock text-red-500 mr-2"></i>
                    Cierre de Caja
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b">
                        <span class="text-gray-600">Fecha y Hora:</span>
                        <span class="font-medium">
                            <?php if ($corte['fecha_cierre']): ?>
                                <?= date('d/m/Y H:i:s', strtotime($corte['fecha_cierre'])) ?>
                            <?php else: ?>
                                <span class="text-gray-400">Pendiente</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b">
                        <span class="text-gray-600">Usuario:</span>
                        <span class="font-medium">
                            <?= $corte['usuario_cierre'] ? htmlspecialchars($corte['usuario_cierre']) : '-' ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600">Duración:</span>
                        <span class="font-medium">
                            <?php if ($corte['fecha_cierre']): ?>
                                <?php 
                                $duracion = strtotime($corte['fecha_cierre']) - strtotime($corte['fecha_apertura']);
                                $horas = floor($duracion / 3600);
                                $minutos = floor(($duracion % 3600) / 60);
                                ?>
                                <?= $horas ?>h <?= $minutos ?>m
                            <?php else: ?>
                                <span class="text-gray-400">En curso</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resumen Financiero -->
    <div class="max-w-6xl mx-auto mb-6">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 p-4">
                <h2 class="text-lg font-semibold text-white">
                    <i class="fas fa-chart-pie mr-2"></i>
                    Resumen Financiero
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Ingresos -->
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3">Ingresos</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">
                                    <i class="fas fa-money-bill-wave text-green-500 mr-1"></i>
                                    Efectivo:
                                </span>
                                <span class="font-medium">$<?= number_format($corte['total_ingresos_efectivo'], 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">
                                    <i class="fas fa-credit-card text-blue-500 mr-1"></i>
                                    Tarjeta:
                                </span>
                                <span class="font-medium">$<?= number_format($corte['total_ingresos_tarjeta'], 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">
                                    <i class="fas fa-exchange-alt text-purple-500 mr-1"></i>
                                    Transferencia:
                                </span>
                                <span class="font-medium">$<?= number_format($corte['total_ingresos_transferencia'], 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t">
                                <span class="font-semibold text-green-600">Total:</span>
                                <span class="font-bold text-green-600">
                                    $<?= number_format($corte['total_ingresos_efectivo'] + $corte['total_ingresos_tarjeta'] + $corte['total_ingresos_transferencia'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gastos -->
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3">Gastos</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">
                                    <i class="fas fa-money-bill-wave text-green-500 mr-1"></i>
                                    Efectivo:
                                </span>
                                <span class="font-medium">$<?= number_format($corte['total_gastos_efectivo'], 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">
                                    <i class="fas fa-credit-card text-blue-500 mr-1"></i>
                                    Tarjeta:
                                </span>
                                <span class="font-medium">$<?= number_format($corte['total_gastos_tarjeta'], 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">
                                    <i class="fas fa-exchange-alt text-purple-500 mr-1"></i>
                                    Transferencia:
                                </span>
                                <span class="font-medium">$<?= number_format($corte['total_gastos_transferencia'], 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t">
                                <span class="font-semibold text-red-600">Total:</span>
                                <span class="font-bold text-red-600">
                                    $<?= number_format($corte['total_gastos_efectivo'] + $corte['total_gastos_tarjeta'] + $corte['total_gastos_transferencia'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Arqueo -->
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3">Arqueo de Efectivo</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Efectivo Esperado:</span>
                                <span class="font-medium">$<?= number_format($corte['efectivo_esperado'], 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Efectivo Contado:</span>
                                <span class="font-medium">
                                    <?php if ($corte['efectivo_contado'] !== null): ?>
                                        $<?= number_format($corte['efectivo_contado'], 2) ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t">
                                <span class="font-semibold">Diferencia:</span>
                                <span class="font-bold text-lg">
                                    <?php if ($corte['diferencia'] !== null): ?>
                                        <?php if ($corte['diferencia'] > 0): ?>
                                            <span class="text-green-600">+$<?= number_format($corte['diferencia'], 2) ?></span>
                                        <?php elseif ($corte['diferencia'] < 0): ?>
                                            <span class="text-red-600">-$<?= number_format(abs($corte['diferencia']), 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-600">$0.00</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Observaciones -->
                <?php if ($corte['observaciones']): ?>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-comments mr-2"></i>
                            Observaciones
                        </h4>
                        <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($corte['observaciones'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Detalle de Denominaciones (si existen) -->
    <?php if (!empty($denominaciones)): ?>
        <div class="max-w-6xl mx-auto mb-6">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-4">
                    <h2 class="text-lg font-semibold text-white">
                        <i class="fas fa-money-bill mr-2"></i>
                        Detalle del Arqueo
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <?php 
                        $totalArqueo = 0;
                        foreach ($denominaciones as $denom): 
                            $totalArqueo += $denom['subtotal'];
                        ?>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-gray-700">
                                        $<?= number_format($denom['denominacion'], 2) ?>
                                    </span>
                                    <span class="text-sm text-gray-600">
                                        x<?= $denom['cantidad'] ?>
                                    </span>
                                </div>
                                <p class="text-right font-semibold text-gray-800">
                                    $<?= number_format($denom['subtotal'], 2) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t text-right">
                        <span class="text-lg font-semibold text-gray-800">
                            Total Contado: $<?= number_format($totalArqueo, 2) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Lista de Movimientos -->
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-gray-700 to-gray-800 p-4">
                <h2 class="text-lg font-semibold text-white">
                    <i class="fas fa-list mr-2"></i>
                    Movimientos del Corte (<?= count($movimientos) ?>)
                </h2>
            </div>
            
            <?php if (empty($movimientos)): ?>
                <div class="p-8 text-center">
                    <p class="text-gray-500">No hay movimientos en este corte</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-gray-600">
                                <th class="text-left p-3">Hora</th>
                                <th class="text-left p-3">Tipo</th>
                                <th class="text-left p-3">Descripción</th>
                                <th class="text-left p-3">Categoría</th>
                                <th class="text-left p-3">Método</th>
                                <th class="text-right p-3">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($movimientos as $mov): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3"><?= date('H:i', strtotime($mov['created_at'])) ?></td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                            <?= $mov['tipo'] == 'ingreso' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= ucfirst($mov['tipo']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <?= htmlspecialchars($mov['descripcion']) ?>
                                        <?php if ($mov['referencia']): ?>
                                            <span class="text-xs text-gray-500 block">
                                                Ref: <?= htmlspecialchars($mov['referencia']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3">
                                        <?php if ($mov['categoria_nombre']): ?>
                                            <span class="flex items-center gap-1">
                                                <i class="<?= $mov['categoria_icono'] ?> text-xs" 
                                                   style="color: <?= $mov['categoria_color'] ?>"></i>
                                                <?= htmlspecialchars($mov['categoria_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3"><?= ucfirst($mov['metodo_pago']) ?></td>
                                    <td class="p-3 text-right font-semibold <?= $mov['tipo'] == 'ingreso' ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= $mov['tipo'] == 'ingreso' ? '+' : '-' ?>$<?= number_format($mov['monto'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Contenido para impresión -->
<div id="contenidoImprimir" class="hidden print:block">
    <!-- Aquí va el contenido formateado para impresión -->
</div>

<script>
function imprimirCorte() {
    window.print();
}

// Estilos para impresión
const estilosImpresion = `
    @media print {
        body { 
            margin: 0; 
            font-family: Arial, sans-serif;
        }
        .no-print { 
            display: none !important; 
        }
        .print\\:block {
            display: block !important;
        }
        table { 
            border-collapse: collapse; 
            width: 100%;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left;
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold;
        }
    }
`;

// Agregar estilos de impresión
const styleSheet = document.createElement("style");
styleSheet.innerText = estilosImpresion;
document.head.appendChild(styleSheet);
</script>