<?php
/**
 * Herramienta local para resetear la contrasena de un usuario existente.
 *
 * Uso seguro:
 *   php src/tools/saas/reset_password_local.php --usuario=admin --password='NuevaClaveSegura123'
 *
 * Modo escritura explicito:
 *   php src/tools/saas/reset_password_local.php --usuario=admin --password='NuevaClaveSegura123' --execute --confirm=admin
 *
 * Requisitos:
 * - Solo CLI.
 * - APP_ENV=local.
 * - No cambia roles, hotel_usuarios ni saas_admins.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    echo "[ERROR] APP_ENV debe ser local. Valor actual: " . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv) . "\n";
    exit(1);
}

$opciones = getopt('', ['usuario:', 'password:', 'execute', 'confirm::']);
$usuario = trim((string) ($opciones['usuario'] ?? ''));
$password = (string) ($opciones['password'] ?? '');
$execute = array_key_exists('execute', $opciones);
$confirm = trim((string) ($opciones['confirm'] ?? ''));

if ($usuario === '') {
    echo "[ERROR] Debe indicar --usuario=nombre_usuario\n";
    exit(1);
}

if ($password === '') {
    echo "[ERROR] Debe indicar --password=valor\n";
    exit(1);
}

if (strlen($password) < 10) {
    echo "[ERROR] La contrasena debe tener al menos 10 caracteres.\n";
    exit(1);
}

if ($execute && $confirm !== $usuario) {
    echo "[ERROR] Para escribir cambios use --confirm={$usuario}\n";
    exit(1);
}

$configPath = dirname(__DIR__, 2) . '/config/database.php';
if (!is_file($configPath)) {
    echo "[ERROR] No se encontro configuracion de base de datos: {$configPath}\n";
    exit(1);
}

$dbConfig = require $configPath;

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['database'],
        $dbConfig['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $stmt = $pdo->prepare(
        'SELECT id, nombre_usuario, nombre_completo, rol, activo
         FROM usuarios
         WHERE nombre_usuario = :usuario
         LIMIT 1'
    );
    $stmt->execute(['usuario' => $usuario]);
    $usuarioRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioRow) {
        echo "[ERROR] Usuario no encontrado: {$usuario}\n";
        exit(1);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    echo "Usuario: {$usuarioRow['nombre_usuario']} (ID {$usuarioRow['id']})\n";
    echo "Nombre: {$usuarioRow['nombre_completo']}\n";
    echo "Rol operativo actual: {$usuarioRow['rol']}\n";
    echo "Activo: {$usuarioRow['activo']}\n";

    if (!$execute) {
        echo "[DRY-RUN] No se modifico la base de datos.\n";
        echo "Operacion: actualizar solo usuarios.password para {$usuarioRow['nombre_usuario']}.\n";
        echo "Algoritmo: password_hash(..., PASSWORD_DEFAULT).\n";
        exit(0);
    }

    $stmt = $pdo->prepare(
        'UPDATE usuarios
         SET password = :password, updated_at = NOW()
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([
        'password' => $hash,
        'id' => (int) $usuarioRow['id'],
    ]);

    echo "[OK] Contrasena actualizada para {$usuarioRow['nombre_usuario']}.\n";
    echo "No se modificaron roles, hotel_usuarios ni saas_admins.\n";
} catch (Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
