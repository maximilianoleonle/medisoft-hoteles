<?php
/**
 * Vista de Corrección de Precios
 * Los Cedros
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrección de Precios - Los Cedros</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .card-error {
            background: #fff5f5;
            border-left: 4px solid #dc3545;
        }
        .card h3 { margin-bottom: 15px; color: #333; }
        .detail { font-size: 14px; line-height: 1.8; color: #666; }
        .detail strong { color: #333; }
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: white;
            border-radius: 6px;
            margin: 5px 0;
        }
        .price-incorrect { color: #dc3545; font-weight: bold; }
        .price-correct { color: #28a745; font-weight: bold; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .summary-label {
            opacity: 0.9;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .actions {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #fff3cd;
            border-radius: 8px;
        }
        .actions h3 {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Corrección de Precios de Reservaciones</h1>
            <p>Sistema de corrección automática para reservaciones con precios incorrectos</p>
        </div>
        
        <div class="content">
            <div class="summary">
                <h2 style="margin-bottom: 20px;">📊 Resumen del Análisis</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-number"><?= $total_reservaciones ?></div>
                        <div class="summary-label">Total Revisadas</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number"><?= $correctas ?></div>
                        <div class="summary-label">Correctas</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number" style="color: #ffc107;"><?= count($incorrectas) ?></div>
                        <div class="summary-label">Necesitan Corrección</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number" style="color: #ff6b6b;">$<?= number_format($total_diferencia, 2) ?></div>
                        <div class="summary-label">Diferencia Total</div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($incorrectas)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle fa-2x"></i>
                    <div>
                        <strong>✓ Todas las reservaciones tienen precios correctos</strong><br>
                        No se encontraron reservaciones que necesiten corrección.
                    </div>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="<?= BASE_URL ?>/dashboard" class="btn btn-primary">
                        <i class="fas fa-home"></i> Volver al Dashboard
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <div>
                        <strong>⚠️ Se encontraron <?= count($incorrectas) ?> reservaciones con precios incorrectos</strong><br>
                        Revisa los detalles abajo y aplica las correcciones cuando estés listo.
                    </div>
                </div>
                
                <h3 style="margin: 30px 0 20px 0;">📋 Reservaciones a Corregir</h3>
                
                <?php foreach ($incorrectas as $inc): ?>
                    <div class="card card-error">
                        <h3>
                            Reservación #<?= $inc['reservacion']['id'] ?> - <?= htmlspecialchars($inc['reservacion']['huesped']) ?>
                            <span class="badge badge-success">
                                <?= date('d/m/Y', strtotime($inc['reservacion']['fecha_entrada'])) ?> → 
                                <?= date('d/m/Y', strtotime($inc['reservacion']['fecha_salida'])) ?>
                                (<?= $inc['reservacion']['noches'] ?> noches)
                            </span>
                        </h3>
                        
                        <div class="detail">
                            <strong>Habitaciones:</strong><br>
                            <?php foreach ($inc['habitaciones'] as $hab): ?>
                                • Hab. <?= $hab['numero'] ?>: 
                                $<?= number_format($hab['precio_base'], 2) ?> × <?= $inc['reservacion']['noches'] ?> = 
                                $<?= number_format($hab['precio_base'] * $inc['reservacion']['noches'], 2) ?>
                                <?= $hab['es_cortesia'] == 1 ? '<span style="color: #28a745;">(CORTESÍA)</span>' : '' ?><br>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <div class="price-row">
                                <span>Precio actual (incorrecto):</span>
                                <span class="price-incorrect">$<?= number_format($inc['reservacion']['precio_total'], 2) ?></span>
                            </div>
                            <div class="price-row">
                                <span>Precio correcto:</span>
                                <span class="price-correct">$<?= number_format($inc['precio_correcto'], 2) ?></span>
                            </div>
                            <div class="price-row" style="background: #fff5f5;">
                                <span><strong>Diferencia:</strong></span>
                                <span style="color: #dc3545; font-weight: bold;">
                                    -$<?= number_format(abs($inc['diferencia']), 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="actions">
                    <h3>¿Aplicar todas las correcciones?</h3>
                    <p style="margin-bottom: 20px; color: #856404;">
                        Esto actualizará <?= count($incorrectas) ?> reservaciones en la base de datos.<br>
                        <strong>Asegúrate de haber revisado los detalles antes de continuar.</strong>
                    </p>
                    <form method="POST" action="<?= BASE_URL ?>/correccion/aplicar" onsubmit="return confirm('¿Estás seguro de aplicar estas correcciones? Esta acción modificará la base de datos.');">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-check"></i> Aplicar Correcciones Ahora
                        </button>
                        <a href="<?= BASE_URL ?>/dashboard" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
