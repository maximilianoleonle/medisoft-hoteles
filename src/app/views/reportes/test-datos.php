<?php
/**
 * Vista de prueba de datos - movimientos_caja
 * /app/views/reportes/test-datos.php
 */
?>

<div class="container-fluid">
    <h2 class="h3 mb-4">Prueba de Datos - Movimientos de Caja</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Estructura de la tabla -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">1. Estructura de la tabla movimientos_caja</h5>
        </div>
        <div class="card-body">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Campo</th>
                        <th>Tipo</th>
                        <th>Null</th>
                        <th>Key</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estructura as $col): ?>
                        <tr>
                            <td><?php echo $col['Field']; ?></td>
                            <td><?php echo $col['Type']; ?></td>
                            <td><?php echo $col['Null']; ?></td>
                            <td><?php echo $col['Key']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Resumen general -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">2. Resumen General de Datos</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Total de movimientos:</strong> <?php echo number_format($resumen['total']); ?></p>
                    <p><strong>Total ingresos:</strong> <?php echo number_format($resumen['total_ingresos']); ?> 
                        (Suma: $<?php echo number_format($resumen['suma_ingresos'], 2); ?>)</p>
                    <p><strong>Total gastos:</strong> <?php echo number_format($resumen['total_gastos']); ?> 
                        (Suma: $<?php echo number_format($resumen['suma_gastos'], 2); ?>)</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Fecha más antigua:</strong> <?php echo $resumen['fecha_mas_antigua']; ?></p>
                    <p><strong>Fecha más reciente:</strong> <?php echo $resumen['fecha_mas_reciente']; ?></p>
                    <p><strong>Movimientos sin usuario:</strong> <?php echo $sin_usuario['sin_usuario']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Usuarios con movimientos -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">3. Usuarios con Movimientos</h5>
        </div>
        <div class="card-body">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Movimientos</th>
                        <th>Ingresos</th>
                        <th>Gastos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo $usuario['id']; ?></td>
                            <td><?php echo $usuario['nombre_completo']; ?></td>
                            <td><?php echo number_format($usuario['total_movimientos']); ?></td>
                            <td>$<?php echo number_format($usuario['total_ingresos'], 2); ?></td>
                            <td>$<?php echo number_format($usuario['total_gastos'], 2); ?></td>
                            <td>
                                <a href="/reportes/test-usuario?usuario_id=<?php echo $usuario['id']; ?>" 
                                   class="btn btn-sm btn-info" target="_blank">
                                    Probar PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Datos del mes actual -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">4. Datos del Mes Actual</h5>
        </div>
        <div class="card-body">
            <p><strong>Período:</strong> <?php echo $mes_actual['fecha_inicio']; ?> al <?php echo $mes_actual['fecha_fin']; ?></p>
            <p><strong>Total movimientos:</strong> <?php echo number_format($mes_actual['total']); ?></p>
            <p><strong>Ingresos:</strong> $<?php echo number_format($mes_actual['ingresos'], 2); ?></p>
            <p><strong>Gastos:</strong> $<?php echo number_format($mes_actual['gastos'], 2); ?></p>
        </div>
    </div>
    
    <!-- Categorías y métodos de pago -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">5. Categorías Encontradas</h5>
                </div>
                <div class="card-body">
                    <ul>
                        <?php foreach ($categorias as $cat): ?>
                            <li><?php echo $cat['categoria'] ?: '(Vacío)'; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">6. Métodos de Pago</h5>
                </div>
                <div class="card-body">
                    <ul>
                        <?php foreach ($metodos_pago as $metodo): ?>
                            <li><?php echo $metodo['metodo_pago'] ?: '(Vacío)'; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Últimos movimientos -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">7. Últimos 20 Movimientos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Descripción</th>
                            <th>Método</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos_recientes as $mov): ?>
                            <tr>
                                <td><?php echo $mov['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($mov['created_at'])); ?></td>
                                <td><?php echo $mov['usuario'] ?: '(Sin usuario)'; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $mov['tipo'] == 'ingreso' ? 'success' : 'danger'; ?>">
                                        <?php echo $mov['tipo']; ?>
                                    </span>
                                </td>
                                <td><?php echo $mov['categoria'] ?: '(Sin categoría)'; ?></td>
                                <td><?php echo substr($mov['descripcion'], 0, 30); ?>...</td>
                                <td><?php echo $mov['metodo_pago']; ?></td>
                                <td class="text-<?php echo $mov['tipo'] == 'ingreso' ? 'success' : 'danger'; ?>">
                                    $<?php echo number_format($mov['monto'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="/reportes" class="btn btn-secondary">Volver a Reportes</a>
    </div>
</div>