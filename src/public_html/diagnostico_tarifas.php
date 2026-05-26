<?php
/**
 * Diagnóstico de Configuración de Base de Datos
 * Los Cedros
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
    h2 { color: #555; background: #e8f5e9; padding: 10px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .warning { color: #ff9800; font-weight: bold; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 15px; overflow-x: auto; border-left: 4px solid #2196F3; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
</style>";

echo "<h1>🔍 Diagnóstico de Configuración de Base de Datos</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Detectar estructura
$app_root = __DIR__;
$config_path = $app_root . '/config/database.php';

if (!file_exists($config_path)) {
    // Intentar un nivel arriba
    $app_root = dirname(__DIR__);
    $config_path = $app_root . '/config/database.php';
}

echo "<div class='section'>";
echo "<h2>1. Archivo de Configuración</h2>";
echo "<p>Ruta: <code>$config_path</code></p>";

if (!file_exists($config_path)) {
    echo "<p class='error'>❌ Archivo de configuración NO encontrado</p>";
    echo "<p>Buscado en:</p>";
    echo "<ul>";
    echo "<li><code>" . __DIR__ . "/config/database.php</code></li>";
    echo "<li><code>" . dirname(__DIR__) . "/config/database.php</code></li>";
    echo "</ul>";
    exit;
}

echo "<p class='success'>✓ Archivo encontrado</p>";

// Cargar y mostrar configuración
$config = require $config_path;

echo "<h3>Configuración Actual:</h3>";
echo "<pre>";
echo "Host: " . ($config['host'] ?? 'NO DEFINIDO') . "\n";
echo "Database: " . ($config['database'] ?? 'NO DEFINIDO') . "\n";
echo "Username: " . ($config['username'] ?? 'NO DEFINIDO') . "\n";
echo "Password: " . (isset($config['password']) ? str_repeat('*', strlen($config['password'])) : 'NO DEFINIDO') . "\n";
echo "Charset: " . ($config['charset'] ?? 'NO DEFINIDO') . "\n";
echo "Collation: " . ($config['collation'] ?? 'NO DEFINIDO') . "\n";
echo "</pre>";

// Verificar charset
echo "<h3>Verificación de Charset:</h3>";
$charset = $config['charset'] ?? '';

echo "<p>Charset configurado: <code>$charset</code></p>";

$charsets_validos = ['utf8', 'utf8mb4', 'latin1'];
if (in_array($charset, $charsets_validos)) {
    echo "<p class='success'>✓ Charset válido</p>";
} else {
    echo "<p class='error'>❌ Charset NO válido</p>";
    echo "<p><strong>Problema detectado:</strong> El charset '$charset' no es reconocido por MySQL</p>";
    echo "<p><strong>Charsets válidos:</strong> utf8, utf8mb4, latin1</p>";
    echo "<p><strong>Recomendación:</strong> Cambiar a 'utf8mb4'</p>";
}

echo "</div>";

// Probar conexión
echo "<div class='section'>";
echo "<h2>2. Prueba de Conexión</h2>";

echo "<h3>Intento 1: Con configuración actual</h3>";
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    echo "<p>DSN: <code>$dsn</code></p>";
    
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $config['options'] ?? []
    );
    
    echo "<p class='success'>✓ Conexión exitosa con configuración actual</p>";
    
    // Probar query
    $stmt = $pdo->query("SELECT DATABASE() as db, VERSION() as version");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Base de datos: <strong>{$result['db']}</strong></p>";
    echo "<p>Versión MySQL: <strong>{$result['version']}</strong></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error de conexión: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Intentar sin charset
    echo "<h3>Intento 2: Sin especificar charset en DSN</h3>";
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']}";
        echo "<p>DSN: <code>$dsn</code></p>";
        
        $pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options'] ?? []
        );
        
        echo "<p class='success'>✓ Conexión exitosa SIN charset en DSN</p>";
        
        // Configurar charset después
        $pdo->exec("SET NAMES 'utf8mb4'");
        echo "<p class='success'>✓ Charset configurado mediante SET NAMES</p>";
        
        // Verificar charsets soportados
        $stmt = $pdo->query("SHOW CHARACTER SET");
        $charsets = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Charsets soportados por tu servidor MySQL:</h3>";
        echo "<pre>";
        foreach (array_slice($charsets, 0, 20) as $cs) {
            echo "- $cs\n";
        }
        if (count($charsets) > 20) {
            echo "... y " . (count($charsets) - 20) . " más\n";
        }
        echo "</pre>";
        
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px;'>";
        echo "<h3 style='margin-top: 0;'>⚠️ Solución Encontrada</h3>";
        echo "<p><strong>El problema está en el charset del DSN.</strong></p>";
        echo "<p>Solución recomendada:</p>";
        echo "<ol>";
        echo "<li>Cambiar el charset en <code>config/database.php</code> a <code>utf8mb4</code></li>";
        echo "<li>O eliminar el charset del DSN y configurarlo con SET NAMES</li>";
        echo "</ol>";
        echo "</div>";
        
    } catch (PDOException $e2) {
        echo "<p class='error'>❌ Error incluso sin charset: " . htmlspecialchars($e2->getMessage()) . "</p>";
        
        echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin-top: 20px;'>";
        echo "<h3 style='margin-top: 0;'>❌ Problema de Conexión Grave</h3>";
        echo "<p>No se puede conectar a la base de datos. Verifica:</p>";
        echo "<ul>";
        echo "<li>Host: ¿Es 'localhost' o necesita ser una IP?</li>";
        echo "<li>Credenciales: ¿Usuario y contraseña correctos?</li>";
        echo "<li>Base de datos: ¿Existe '{$config['database']}'?</li>";
        echo "<li>Permisos: ¿El usuario tiene acceso?</li>";
        echo "</ul>";
        echo "</div>";
    }
}

echo "</div>";

// Generar configuración corregida
echo "<div class='section'>";
echo "<h2>3. Configuración Recomendada</h2>";

$config_recomendada = "<?php
/**
 * Configuración de Base de Datos
 * Los Cedros
 * VERSIÓN CORREGIDA
 */

return [
    'host' => '{$config['host']}',
    'database' => '{$config['database']}',
    'username' => '{$config['username']}',
    'password' => '" . str_repeat('*', 10) . "',  // REEMPLAZAR con tu contraseña real
    'charset' => 'utf8mb4',  // CORREGIDO
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
";

echo "<p><strong>Copia esta configuración en tu archivo <code>config/database.php</code>:</strong></p>";
echo "<pre>" . htmlspecialchars($config_recomendada) . "</pre>";

echo "<p class='warning'>⚠️ <strong>IMPORTANTE:</strong> Reemplaza los asteriscos con tu contraseña real</p>";

echo "</div>";

echo "<hr>";
echo "<p style='text-align: center; color: #666;'>";
echo "<em>Diagnóstico completado - " . date('Y-m-d H:i:s') . "</em>";
echo "</p>";