<?php
/**
 * Bootstrap de pruebas — Medisoft Hoteles
 *
 * Carga el nucleo de la aplicacion en CLI apuntando a la base de PRUEBA.
 * Cada archivo de tests corre en su PROPIO proceso (ver run.php): los caches
 * estaticos por-request (p.ej. obtenerHotelIdActualCompat) hacen que cambiar
 * de hotel a mitad de proceso no surta efecto.
 *
 * Requisitos: contenedores de docker-compose arriba y la BD de prueba creada
 * (run.php la recrea desde database/schema.sql en cada corrida).
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── BD de prueba: candado de seguridad ──────────────────────────────────
$dbPrueba = getenv('MS_TEST_DB') ?: 'medisoft_test';
if (stripos($dbPrueba, 'test') === false) {
    fwrite(STDERR, "[tests] MS_TEST_DB debe contener 'test' (recibido: $dbPrueba). Abortando para proteger datos reales.\n");
    exit(2);
}
putenv('DB_NAME=' . $dbPrueba);
$_ENV['DB_NAME'] = $dbPrueba;
if (getenv('DB_HOST') === false) {
    putenv('DB_HOST=db'); // dentro del contenedor app; en CI se exporta 127.0.0.1
}
putenv('DB_STRICT_ERRORS=true'); // los tests nunca toleran queries rotas

// ── Rutas (mismas constantes que public_html/index.php) ─────────────────
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public_html');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Sesion simulada para helpers que leen $_SESSION en CLI
$_SESSION = [];

require_once APP_PATH . '/helpers/log.php';

// Autoloader identico al de index.php
spl_autoload_register(function ($class) {
    foreach ([CORE_PATH, APP_PATH . '/controllers', APP_PATH . '/models', APP_PATH . '/services'] as $dir) {
        $file = $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once APP_PATH . '/helpers/cache.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/hotel_config.php';
require_once APP_PATH . '/helpers/modulos.php';
require_once APP_PATH . '/helpers/navegacion.php';
require_once APP_PATH . '/helpers/footer_nav.php';

require_once __DIR__ . '/lib.php';

// ── Utilidades de datos de prueba ────────────────────────────────────────

/** Vacia todas las tablas de la BD de prueba (estructura intacta). */
function t_reset_db(): void
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Doble candado: jamas truncar fuera de una BD de prueba.
    $actual = $pdo->query('SELECT DATABASE()')->fetchColumn();
    if (stripos((string) $actual, 'test') === false) {
        throw new RuntimeException("t_reset_db se nego a truncar '$actual' (no es BD de prueba).");
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    // Solo tablas base: las VIEWs no se truncan.
    $tablas = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($tablas as $tabla) {
        $pdo->exec("TRUNCATE TABLE `$tabla`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

/**
 * Crea el minimo comun: hotel + usuario + sesion + TenantContext.
 * Devuelve ['hotel_id' => ..., 'usuario_id' => ...].
 */
function t_seed_base(string $slug = 'hotel-test'): array
{
    $db = Database::getInstance();

    $db->query(
        "INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES (?, ?, 1, NOW())",
        ['Hotel ' . ucfirst($slug), $slug]
    );
    $hotelId = (int) $db->lastInsertId();

    $db->query(
        "INSERT INTO usuarios (nombre_usuario, password, nombre_completo, rol, activo, created_at)
         VALUES (?, 'x', 'Usuario Test', 'administrador', 1, NOW())",
        ['test_' . $slug]
    );
    $usuarioId = (int) $db->lastInsertId();

    $db->query(
        "INSERT INTO hotel_usuarios (hotel_id, usuario_id, rol, es_principal, activo, created_at)
         VALUES (?, ?, 'administrador', 1, 1, NOW())",
        [$hotelId, $usuarioId]
    );

    $_SESSION['user_id'] = $usuarioId;
    $_SESSION['hotel_id'] = $hotelId;

    TenantContext::boot([
        'hotel' => ['id' => $hotelId, 'slug' => $slug],
        'usuario_id' => $usuarioId,
        'roles' => ['administrador'],
    ]);

    return ['hotel_id' => $hotelId, 'usuario_id' => $usuarioId];
}

/** Crea huesped + habitacion + reservacion con precio_total dado. */
function t_seed_reservacion(int $hotelId, float $precioTotal): int
{
    $db = Database::getInstance();

    $db->query(
        "INSERT INTO huespedes (hotel_id, nombre_completo, created_at) VALUES (?, 'Huesped Test', NOW())",
        [$hotelId]
    );
    $huespedId = (int) $db->lastInsertId();

    // Las habitaciones se ligan por tabla puente; para dinero basta la fila
    // de reservaciones (precio_total + estado).
    $db->query(
        "INSERT INTO reservaciones
            (hotel_id, huesped_id, fecha_entrada, fecha_salida,
             precio_total, estado, created_at)
         VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), ?, 'confirmada', NOW())",
        [$hotelId, $huespedId, $precioTotal]
    );

    return (int) $db->lastInsertId();
}
