<?php
/**
 * Verificador de Rutas - Los Cedros
 * Muestra todas las rutas POST relacionadas con check-out
 */

header('Content-Type: text/html; charset=UTF-8');

echo "<h2>🔍 Verificación de Rutas - Los Cedros</h2>";
echo "<hr>";

// 1. Detectar la ubicación del archivo routes.php según la estructura
$possibleLocations = [
    __DIR__ . '/../config/routes.php',  // Desde public_html/
    __DIR__ . '/config/routes.php',      // Por si acaso
    dirname(__DIR__) . '/config/routes.php' // Alternativa
];

$routesFile = null;
foreach ($possibleLocations as $location) {
    if (file_exists($location)) {
        $routesFile = $location;
        break;
    }
}

if (!$routesFile) {
    echo "<div style='background: #FEE2E2; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: #DC2626; font-weight: bold;'>❌ ERROR: No se encuentra el archivo routes.php</p>";
    echo "<p>Ubicaciones buscadas:</p><ul>";
    foreach ($possibleLocations as $loc) {
        echo "<li><code>$loc</code></li>";
    }
    echo "</ul>";
    echo "<p><strong>Estructura esperada:</strong></p>";
    echo "<pre>
public_html/
    └── index.php
config/
    └── routes.php  ← Debe estar aquí
    </pre>";
    echo "</div>";
    exit;
}

echo "<div style='background: #DCFCE7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p style='color: #15803D;'>✅ Archivo routes.php encontrado</p>";
echo "<p>📁 Ubicación: <code>" . str_replace($_SERVER['DOCUMENT_ROOT'], '', $routesFile) . "</code></p>";
echo "<p>📏 Tamaño: " . number_format(filesize($routesFile)) . " bytes</p>";
echo "<p>📅 Última modificación: " . date('Y-m-d H:i:s', filemtime($routesFile)) . "</p>";
echo "</div>";

echo "<hr>";

// 2. Leer el contenido del archivo
$content = file_get_contents($routesFile);
$lines = file($routesFile);

echo "<h3>🔎 Búsqueda de rutas relacionadas con check-out</h3>";

// 3. Buscar líneas que contengan "check-out"
$found = false;
$checkoutLines = [];

foreach ($lines as $num => $line) {
    if (stripos($line, 'check-out') !== false || stripos($line, 'checkout') !== false) {
        $lineNum = $num + 1;
        $checkoutLines[] = [
            'num' => $lineNum,
            'content' => $line
        ];
        $found = true;
    }
}

if ($found) {
    echo "<div style='background: #DCFCE7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p style='color: #15803D;'>✅ Se encontraron " . count($checkoutLines) . " línea(s) con rutas de check-out</p>";
    echo "</div>";
    
    echo "<div style='background: #F0FDF4; padding: 15px; border-left: 4px solid #10B981; margin: 10px 0;'>";
    echo "<h4>Rutas encontradas:</h4>";
    echo "<pre style='background: white; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    
    foreach ($checkoutLines as $line) {
        $highlighted = $line['content'];
        // Resaltar check-out-parcial
        if (stripos($highlighted, 'check-out-parcial') !== false) {
            $highlighted = str_replace('check-out-parcial', '<span style="background: #FEF08A; font-weight: bold;">check-out-parcial</span>', $highlighted);
        }
        echo sprintf("<strong>Línea %d:</strong> %s", $line['num'], $highlighted);
    }
    
    echo "</pre>";
    echo "</div>";
    
    // 4. Verificar específicamente check-out-parcial
    if (stripos($content, 'check-out-parcial') !== false) {
        echo "<div style='background: #DCFCE7; padding: 20px; border-left: 4px solid #16A34A; margin: 15px 0;'>";
        echo "<p style='color: #15803D; font-weight: bold; font-size: 18px;'>✅ ¡RUTA check-out-parcial ENCONTRADA!</p>";
        echo "<p style='color: #15803D;'>La ruta está correctamente definida en routes.php</p>";
        
        // Buscar la línea exacta
        foreach ($checkoutLines as $line) {
            if (stripos($line['content'], 'check-out-parcial') !== false) {
                echo "<p><strong>Línea " . $line['num'] . ":</strong></p>";
                echo "<pre style='background: white; padding: 10px; border-radius: 5px;'>" . htmlspecialchars(trim($line['content'])) . "</pre>";
            }
        }
        
        echo "</div>";
    } else {
        echo "<div style='background: #FEE2E2; padding: 20px; border-left: 4px solid #DC2626; margin: 15px 0;'>";
        echo "<p style='color: #DC2626; font-weight: bold; font-size: 18px;'>❌ RUTA check-out-parcial NO ENCONTRADA</p>";
        echo "<p>Necesitas agregar esta línea a <code>config/routes.php</code>:</p>";
        echo "<pre style='background: #FEF2F2; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
        echo htmlspecialchars("\$router->post('/reservaciones/check-out-parcial/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'checkOutParcialAction']);");
        echo "</pre>";
        echo "<p><strong>Agrégala después de la línea de check-out normal (alrededor de la línea 178).</strong></p>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #FEE2E2; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: #DC2626;'>❌ No se encontraron rutas de check-out en el archivo</p>";
    echo "</div>";
}

echo "<hr>";

// 5. Mostrar estadísticas
$totalLines = count($lines);
$routerPosts = 0;
$routerGets = 0;

foreach ($lines as $line) {
    if (stripos($line, '$router->post') !== false) {
        $routerPosts++;
    }
    if (stripos($line, '$router->get') !== false) {
        $routerGets++;
    }
}

echo "<h3>📊 Estadísticas de routes.php</h3>";
echo "<div style='background: #EFF6FF; padding: 15px; border-radius: 5px;'>";
echo "<ul style='list-style: none; padding: 0;'>";
echo "<li>📄 <strong>Total de líneas:</strong> $totalLines</li>";
echo "<li>📮 <strong>Rutas POST:</strong> $routerPosts</li>";
echo "<li>📥 <strong>Rutas GET:</strong> $routerGets</li>";
echo "<li>📊 <strong>Total de rutas:</strong> " . ($routerPosts + $routerGets) . "</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";

// 6. Verificar que el método existe en el controlador
echo "<h3>🎯 Verificación del Controlador</h3>";

$controllerPaths = [
    __DIR__ . '/../app/controllers/ReservacionController.php',
    dirname(__DIR__) . '/app/controllers/ReservacionController.php'
];

$controllerFile = null;
foreach ($controllerPaths as $path) {
    if (file_exists($path)) {
        $controllerFile = $path;
        break;
    }
}

if ($controllerFile && file_exists($controllerFile)) {
    echo "<div style='background: #DCFCE7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p style='color: #15803D;'>✅ ReservacionController.php encontrado</p>";
    echo "<p>📁 Ubicación: <code>" . str_replace($_SERVER['DOCUMENT_ROOT'], '', $controllerFile) . "</code></p>";
    echo "</div>";
    
    $controllerContent = file_get_contents($controllerFile);
    
    if (stripos($controllerContent, 'checkOutParcialAction') !== false) {
        echo "<div style='background: #DCFCE7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p style='color: #15803D;'>✅ Método <code>checkOutParcialAction</code> encontrado en el controlador</p>";
        
        // Buscar la línea del método
        $controllerLines = file($controllerFile);
        foreach ($controllerLines as $num => $line) {
            if (stripos($line, 'checkOutParcialAction') !== false) {
                $lineNum = $num + 1;
                echo "<p>Definido en la línea <strong>$lineNum</strong></p>";
                break;
            }
        }
        echo "</div>";
    } else {
        echo "<div style='background: #FEE2E2; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p style='color: #DC2626;'>❌ Método <code>checkOutParcialAction</code> NO encontrado en el controlador</p>";
        echo "<p>Necesitas agregar el método <code>public function checkOutParcialAction()</code> en ReservacionController.php</p>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #FEE2E2; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: #DC2626;'>❌ ReservacionController.php no encontrado</p>";
    echo "<p>Ubicaciones buscadas:</p><ul>";
    foreach ($controllerPaths as $path) {
        echo "<li><code>$path</code></li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";

// 7. Verificar Router.php
echo "<h3>⚙️ Verificación del Router</h3>";

$routerPaths = [
    __DIR__ . '/../core/Router.php',
    dirname(__DIR__) . '/core/Router.php'
];

$routerPhp = null;
foreach ($routerPaths as $path) {
    if (file_exists($path)) {
        $routerPhp = $path;
        break;
    }
}

if ($routerPhp) {
    echo "<div style='background: #DCFCE7; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: #15803D;'>✅ Router.php encontrado</p>";
    echo "<p>📁 Ubicación: <code>" . str_replace($_SERVER['DOCUMENT_ROOT'], '', $routerPhp) . "</code></p>";
    echo "</div>";
} else {
    echo "<div style='background: #FEE2E2; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: #DC2626;'>⚠️ Router.php no encontrado en core/</p>";
    echo "</div>";
}

echo "<hr>";

// 8. Resumen final
echo "<h3>📋 Resumen del Diagnóstico</h3>";

$todoOk = stripos($content, 'check-out-parcial') !== false && 
          ($controllerFile && stripos(file_get_contents($controllerFile), 'checkOutParcialAction') !== false);

if ($todoOk) {
    echo "<div style='background: #DCFCE7; padding: 20px; border-radius: 5px; border-left: 4px solid #16A34A;'>";
    echo "<h4 style='color: #15803D;'>✅ TODO CONFIGURADO CORRECTAMENTE</h4>";
    echo "<p>✅ La ruta está definida en routes.php</p>";
    echo "<p>✅ El método existe en ReservacionController.php</p>";
    echo "<p>✅ El archivo Router.php existe</p>";
    echo "<br>";
    echo "<p><strong>Si aún tienes errores, el problema puede ser:</strong></p>";
    echo "<ol>";
    echo "<li>Cache de PHP/servidor no actualizado → <a href='limpiar_cache.php'>Limpiar cache</a></li>";
    echo "<li>Error en el código del método → Revisa los logs de PHP</li>";
    echo "<li>Problema con permisos de archivos</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #FEF3C7; padding: 20px; border-radius: 5px; border-left: 4px solid #F59E0B;'>";
    echo "<h4 style='color: #92400E;'>⚠️ FALTAN CONFIGURACIONES</h4>";
    
    if (stripos($content, 'check-out-parcial') === false) {
        echo "<p>❌ Falta agregar la ruta en routes.php</p>";
    }
    
    if (!$controllerFile || stripos(file_get_contents($controllerFile), 'checkOutParcialAction') === false) {
        echo "<p>❌ Falta agregar el método en ReservacionController.php</p>";
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<div style='text-align: center; padding: 20px;'>";
echo "<p><a href='test_checkout_MEJORADO.html' style='background: #F97316; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Volver al Test</a>";
echo " <a href='/' style='background: #3B82F6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Ir al Sistema</a></p>";
echo "</div>";
?>
