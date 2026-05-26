<?php
/**
 * Script de Instalación Automática
 * Sistema de Corrección de Precios
 * 
 * INSTRUCCIONES:
 * 1. Sube este archivo a la raíz de tu proyecto (donde está public_html/)
 * 2. Sube los archivos CorreccionController.php y correccion_index.php a la misma carpeta
 * 3. Accede a: https://tu-hotel.com/instalar_correccion.php
 * 4. Sigue las instrucciones
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Detectar la ruta base del proyecto
// Si estamos en public_html, el proyecto está un nivel arriba
$base_path = dirname(__FILE__);
$project_root = (basename($base_path) === 'public_html') ? dirname($base_path) : $base_path;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - Sistema de Corrección</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 24px; margin-bottom: 10px; }
        .content { padding: 30px; }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .step-success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .step-error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .step-warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .alert-info { background: #d1ecf1; color: #0c5460; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Instalador del Sistema de Corrección de Precios</h1>
            <p>Los Cedros</p>
        </div>
        
        <div class="content">
            <?php
            $install = isset($_POST['install']);
            
            if (!$install) {
                // Mostrar pre-instalación
                ?>
                <div class="alert alert-info">
                    <strong>ℹ️ Información del Sistema:</strong><br>
                    <ul style="margin-top: 10px; margin-left: 20px;">
                        <li><strong>Ubicación del instalador:</strong> <code><?= $base_path ?></code></li>
                        <li><strong>Raíz del proyecto:</strong> <code><?= $project_root ?></code></li>
                        <li><strong>Carpeta app/:</strong> <code><?= $project_root ?>/app/</code></li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <strong>ℹ️ Antes de continuar:</strong><br>
                    Asegúrate de tener los siguientes archivos en la misma carpeta que este instalador (<code><?= $base_path ?></code>):
                    <ul style="margin-top: 10px; margin-left: 20px;">
                        <li><code>CorreccionController.php</code> <?= file_exists($base_path . '/CorreccionController.php') ? '✓' : '❌ NO ENCONTRADO' ?></li>
                        <li><code>correccion_index.php</code> <?= file_exists($base_path . '/correccion_index.php') ? '✓' : '❌ NO ENCONTRADO' ?></li>
                    </ul>
                </div>
                
                <div class="step">
                    <h3>📋 Lo que hará este instalador:</h3>
                    <ol style="margin-left: 20px; margin-top: 10px; line-height: 1.8;">
                        <li>Crear la carpeta <code>app/views/correccion/</code></li>
                        <li>Copiar <code>CorreccionController.php</code> a <code>app/controllers/</code></li>
                        <li>Copiar <code>correccion_index.php</code> a <code>app/views/correccion/index.php</code></li>
                        <li>Mostrar las rutas que debes agregar manualmente a <code>config/routes.php</code></li>
                    </ol>
                </div>
                
                <form method="POST" style="text-align: center; margin-top: 30px;">
                    <button type="submit" name="install" class="btn">
                        ✓ Iniciar Instalación
                    </button>
                </form>
                <?php
            } else {
                // Realizar instalación
                $errors = [];
                $success = [];
                
                // Verificar archivos fuente
                if (!file_exists($base_path . '/CorreccionController.php')) {
                    $errors[] = "No se encontró CorreccionController.php";
                }
                if (!file_exists($base_path . '/correccion_index.php')) {
                    $errors[] = "No se encontró correccion_index.php";
                }
                
                if (empty($errors)) {
                    // Crear carpeta de vistas
                    $views_dir = $project_root . '/app/views/correccion';
                    if (!is_dir($views_dir)) {
                        if (mkdir($views_dir, 0755, true)) {
                            $success[] = "✓ Carpeta app/views/correccion/ creada en: $views_dir";
                        } else {
                            $errors[] = "No se pudo crear la carpeta app/views/correccion/ en: $views_dir";
                        }
                    } else {
                        $success[] = "✓ Carpeta app/views/correccion/ ya existe";
                    }
                    
                    // Copiar controlador
                    $controller_dest = $project_root . '/app/controllers/CorreccionController.php';
                    if (copy($base_path . '/CorreccionController.php', $controller_dest)) {
                        $success[] = "✓ CorreccionController.php copiado a app/controllers/";
                    } else {
                        $errors[] = "No se pudo copiar CorreccionController.php a: $controller_dest";
                    }
                    
                    // Copiar vista
                    $view_dest = $project_root . '/app/views/correccion/index.php';
                    if (copy($base_path . '/correccion_index.php', $view_dest)) {
                        $success[] = "✓ index.php copiado a app/views/correccion/";
                    } else {
                        $errors[] = "No se pudo copiar correccion_index.php a: $view_dest";
                    }
                }
                
                // Mostrar resultados
                if (!empty($success)) {
                    echo '<div class="alert alert-success">';
                    echo '<strong>✓ Instalación Exitosa</strong><br><br>';
                    foreach ($success as $msg) {
                        echo $msg . '<br>';
                    }
                    echo '</div>';
                }
                
                if (!empty($errors)) {
                    echo '<div class="alert alert-danger">';
                    echo '<strong>❌ Errores</strong><br><br>';
                    foreach ($errors as $msg) {
                        echo $msg . '<br>';
                    }
                    echo '</div>';
                }
                
                if (empty($errors)) {
                    ?>
                    <div class="step step-warning">
                        <h3>⚠️ ÚLTIMO PASO - Agregar Rutas Manualmente</h3>
                        <p style="margin: 10px 0;">Edita el archivo <code>config/routes.php</code> y agrega estas líneas al final:</p>
                        <div class="code">
// Sistema de Corrección de Precios (solo gerente)<br>
$router->get('/correccion', ['controller' => 'Correccion', 'action' => 'index']);<br>
$router->post('/correccion/aplicar', ['controller' => 'Correccion', 'action' => 'aplicar']);
                        </div>
                    </div>
                    
                    <div class="alert alert-success">
                        <strong>🎉 ¡Instalación Completada!</strong><br><br>
                        Una vez que agregues las rutas, podrás acceder al sistema en:<br>
                        <strong>https://tu-hotel.com/correccion</strong>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/correccion" class="btn">
                            🚀 Ir al Sistema de Corrección
                        </a>
                    </div>
                    
                    <div class="step">
                        <h4>📝 Limpieza (Opcional)</h4>
                        <p>Puedes eliminar estos archivos de la raíz del proyecto:</p>
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>instalar_correccion.php (este archivo)</li>
                            <li>CorreccionController.php</li>
                            <li>correccion_index.php</li>
                        </ul>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</body>
</html>