<?php
/**
 * Vista de prueba de usuario - movimientos_caja
 * /app/views/reportes/test-usuario.php
 */
?>

<div class="container-fluid">
    <h2 class="h3 mb-4">Prueba de Datos por Usuario</h2>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Parámetros de Prueba</h5>
        </div>
        <div class="card-body">
            <p><strong>Usuario ID:</strong> <?php echo $usuario_id; ?></p>
            <p><strong>Fecha Inicio:</strong> <?php echo $fecha_inicio; ?></p>
            <p><strong>Fecha Fin:</strong> <?php echo $fecha_fin; ?></p>
            <p><strong>Movimientos encontrados:</strong> <?php echo $cantidad_movimientos; ?></p>
        </div>
    </div>
    
    <!-- Totales calculados -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Totales Calculados (como en el PDF)</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Efectivo</th>
                        <th>Tarjeta</th>
                        <th>Transferencia</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Ingresos</strong></td>
                        <td>$<?php echo number_format($totales['ingresos']['efectivo'], 2); ?></td>
                        <td>$<?php echo number_format($totales['ingresos']['tarjeta'], 2); ?></td>
                        <td>$<?php echo number_format($totales['ingresos']['transferencia'], 2); ?></td>
                        <td><strong>$<?php echo number_format($totales['ingresos']['total'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Gastos</strong></td>
                        <td>$<?php echo number_format($totales['gastos']['efectivo'], 2); ?></td>
                        <td>$<?php echo number_format($totales['gastos']['tarjeta'], 2); ?></td>
                        <td>$<?php echo number_format($totales['gastos']['transferencia'], 2); ?></td>
                        <td><strong>$<?php echo number_format($totales['gastos']['total'], 2); ?></strong></td>
                    </tr>
                    <tr class="table-active">
                        <td><strong>Balance</strong></td>
                        <td>$<?php echo number_format($totales['ingresos']['efectivo'] - $totales['gastos']['efectivo'], 2); ?></td>
                        <td>$<?php echo number_format($totales['ingresos']['tarjeta'] - $totales['gastos']['tarjeta'], 2); ?></td>
                        <td>$<?php echo number_format($totales['ingresos']['transferencia'] - $totales['gastos']['transferencia'], 2); ?></td>
                        <td><strong>$<?php echo number_format($totales['ingresos']['total'] - $totales['gastos']['total'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Detalle de movimientos -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Detalle de Movimientos (<?php echo count($movimientos); ?> registros)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($movimientos)): ?>
                <div class="alert alert-info">
                    No se encontraron movimientos para este usuario en el período seleccionado.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Categoría</th>
                                <th>Categoría Nombre</th>
                                <th>Descripción</th>
                                <th>Método</th>
                                <th>Monto</th>
                                <th>Usuario ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $mov): ?>
                                <tr>
                                    <td><?php echo $mov['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($mov['created_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $mov['tipo'] == 'ingreso' ? 'success' : 'danger'; ?>">
                                            <?php echo $mov['tipo']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $mov['categoria'] ?: '(Sin categoría)'; ?></td>
                                    <td><?php echo $mov['categoria_nombre'] ?: '(Sin nombre)'; ?></td>
                                    <td><?php echo $mov['descripcion'] ?: '(Sin descripción)'; ?></td>
                                    <td><?php echo $mov['metodo_pago']; ?></td>
                                    <td class="text-<?php echo $mov['tipo'] == 'ingreso' ? 'success' : 'danger'; ?>">
                                        $<?php echo number_format($mov['monto'], 2); ?>
                                    </td>
                                    <td><?php echo $mov['usuario_id']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (isset($debug)): ?>
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Debug - Análisis de Cálculos</h5>
    </div>
    <div class="card-body">
        <h6>Tipos encontrados:</h6>
        <table class="table table-sm">
            <tr>
                <th>Original</th>
                <th>Procesado</th>
            </tr>
            <?php foreach ($debug['tipos_encontrados'] as $original => $procesado): ?>
            <tr>
                <td>"<?php echo $original; ?>"</td>
                <td>"<?php echo $procesado; ?>"</td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h6>Métodos de pago encontrados:</h6>
        <table class="table table-sm">
            <tr>
                <th>Original</th>
                <th>Procesado</th>
            </tr>
            <?php foreach ($debug['metodos_encontrados'] as $original => $procesado): ?>
            <tr>
                <td>"<?php echo $original; ?>"</td>
                <td>"<?php echo $procesado; ?>"</td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h6>Primeros 5 cálculos detallados:</h6>
        <pre><?php print_r(array_slice($debug['calculos_detallados'], 0, 5)); ?></pre>
    </div>
</div>
<?php endif; ?>

                <!-- Debug - Primer registro completo -->
                <div class="mt-4">
                    <h6>Debug - Estructura del primer registro:</h6>
                    <pre class="bg-light p-3">
<?php print_r($movimientos[0]); ?>
                    </pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="/reportes/exportar-pdf?tipo=ingresos-gastos-usuario&usuario_id=<?php echo $usuario_id; ?>&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>" 
           class="btn btn-primary" target="_blank">
            Generar PDF con estos datos
        </a>
        <a href="/reportes/test-datos" class="btn btn-secondary">Volver a Test General</a>
        <a href="/reportes" class="btn btn-secondary">Volver a Reportes</a>
    </div>
</div>