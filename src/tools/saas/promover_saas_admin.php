<?php
/**
 * Herramienta local para promover un usuario existente a SaaS admin.
 *
 * Uso seguro:
 *   php src/tools/saas/promover_saas_admin.php --usuario=admin --rol=owner
 *
 * Modo escritura explicito:
 *   php src/tools/saas/promover_saas_admin.php --usuario=admin --rol=owner --execute --confirm=admin
 *
 * Requisitos:
 * - Solo CLI.
 * - APP_ENV=local.
 * - La tabla saas_admins debe existir.
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

$opciones = getopt('', ['usuario:', 'rol::', 'execute', 'confirm::']);
$usuario = trim((string) ($opciones['usuario'] ?? ''));
$rol = trim((string) ($opciones['rol'] ?? 'owner'));
$execute = array_key_exists('execute', $opciones);
$confirm = trim((string) ($opciones['confirm'] ?? ''));
$rolesValidos = ['owner', 'admin', 'soporte'];

if ($usuario === '') {
    echo "[ERROR] Debe indicar --usuario=nombre_usuario\n";
    exit(1);
}

if (!in_array($rol, $rolesValidos, true)) {
    echo "[ERROR] Rol SaaS invalido. Use owner, admin o soporte.\n";
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

    if ((int) $usuarioRow['activo'] !== 1) {
        echo "[ERROR] Usuario inactivo: {$usuario}\n";
        exit(1);
    }

    $stmt = $pdo->prepare(
        'SELECT id, rol, activo
         FROM saas_admins
         WHERE usuario_id = :usuario_id
         LIMIT 1'
    );
    $stmt->execute(['usuario_id' => (int) $usuarioRow['id']]);
    $saasAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Usuario: {$usuarioRow['nombre_usuario']} (ID {$usuarioRow['id']})\n";
    echo "Rol operativo actual: {$usuarioRow['rol']}\n";
    echo "Rol SaaS solicitado: {$rol}\n";

    if (!$execute) {
        echo "[DRY-RUN] No se modifico la base de datos.\n";
        echo "SQL equivalente:\n";
        echo "INSERT INTO saas_admins (usuario_id, rol, activo, created_at, updated_at)\n";
        echo "VALUES ({$usuarioRow['id']}, '{$rol}', 1, NOW(), NOW())\n";
        echo "ON DUPLICATE KEY UPDATE rol = VALUES(rol), activo = 1, updated_at = NOW();\n";
        exit(0);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO saas_admins (usuario_id, rol, activo, created_at, updated_at)
         VALUES (:usuario_id, :rol, 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE rol = VALUES(rol), activo = 1, updated_at = NOW()'
    );
    $stmt->execute([
        'usuario_id' => (int) $usuarioRow['id'],
        'rol' => $rol,
    ]);

    echo $saasAdmin
        ? "[OK] SaaS admin actualizado.\n"
        : "[OK] SaaS admin creado.\n";
} catch (Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
