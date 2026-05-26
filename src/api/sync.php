<?php
/**
 * API: Sincronización de operaciones offline
 * Ruta: /api/sync
 * Método: POST
 * Los Cedros
 *
 * Recibe un lote de operaciones encoladas en IndexedDB y las aplica
 * al servidor MySQL. Usa UUID para garantizar idempotencia.
 *
 * Request body (JSON):
 * {
 *   "operaciones": [
 *     {
 *       "uuid":       "a3f8b2c1-...",   // UUIDv4 único por operación
 *       "tipo":       "checkin",         // ver tipos abajo
 *       "payload":    { ... },           // datos específicos del tipo
 *       "timestamp":  1714123456789,     // epoch ms del cliente
 *       "usuario_id": 3                  // Ignorado; el servidor usa la sesion
 *     }
 *   ]
 * }
 *
 * Tipos soportados:
 *   cambiar_estado_habitacion  → payload: { habitacion_id, estado_nuevo }
 *   checkin                    → payload: { reservacion_id }
 *   checkout                   → payload: { reservacion_id, estado_habitacion_destino? }
 *   pago_caja                  → payload: { monto, metodo_pago, descripcion, reservacion_id? }
 *
 * Response (JSON):
 * {
 *   "success": true,
 *   "exitosas": ["uuid1", "uuid2"],
 *   "fallidas": [{ "uuid": "uuid3", "error": "Mensaje de error" }],
 *   "total": 3,
 *   "procesadas": 2
 * }
 */

// ─── Bootstrap ──────────────────────────────────────────────────────────────
define('ROOT_PATH',   dirname(__DIR__));
define('APP_PATH',    ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH',   ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public_html');
define('STORAGE_PATH', ROOT_PATH . '/storage');

date_default_timezone_set('America/Mexico_City');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('session.use_strict_mode', 1);
error_reporting(E_ALL);

// Autoloader (igual que public_html/index.php)
spl_autoload_register(function (string $class): void {
    $candidates = [
        CORE_PATH  . "/$class.php",
        APP_PATH   . "/models/$class.php",
        APP_PATH   . "/controllers/$class.php",
    ];
    foreach ($candidates as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/helpers/auth.php';

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ─── Cabeceras ───────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ─── Solo POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Usa POST.']);
    exit;
}

// ─── Autenticación ───────────────────────────────────────────────────────────
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode([
        'success'  => false,
        'message'  => 'Sesión expirada. Inicia sesión para sincronizar.',
        'redirect' => 'login',
    ]);
    exit;
}

if (!verify_csrf_token(csrf_token_from_request())) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Token de seguridad invalido',
    ]);
    exit;
}

// ─── Leer body JSON ──────────────────────────────────────────────────────────
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido: ' . json_last_error_msg()]);
    exit;
}

$operaciones = $data['operaciones'] ?? [];

if (!is_array($operaciones)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El campo "operaciones" debe ser un array.']);
    exit;
}

// Sin operaciones = respuesta vacía exitosa (puede ocurrir en sync vacío)
if (empty($operaciones)) {
    echo json_encode([
        'success'    => true,
        'exitosas'   => [],
        'fallidas'   => [],
        'total'      => 0,
        'procesadas' => 0,
        'mensaje'    => 'Sin operaciones pendientes',
    ]);
    exit;
}

// ─── Límite de seguridad ─────────────────────────────────────────────────────
const MAX_OPERACIONES_POR_LOTE = 100;

if (count($operaciones) > MAX_OPERACIONES_POR_LOTE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Demasiadas operaciones en un lote (máx ' . MAX_OPERACIONES_POR_LOTE . ').',
    ]);
    exit;
}

// ─── Sanitizar operaciones ────────────────────────────────────────────────────
$operaciones_limpias = [];
foreach ($operaciones as $i => $op) {
    if (!is_array($op)) {
        continue; // ignorar entradas malformadas
    }
    $operaciones_limpias[] = [
        'uuid'       => trim((string) ($op['uuid']      ?? '')),
        'tipo'       => trim((string) ($op['tipo']      ?? '')),
        'payload'    => is_array($op['payload'] ?? null) ? $op['payload'] : [],
        'timestamp'  => (int)          ($op['timestamp'] ?? 0),
        'usuario_id' => user_id(),
    ];
}

// ─── Procesar ─────────────────────────────────────────────────────────────────
try {
    $syncModel = new Sync();
    $resultado = $syncModel->procesarLote($operaciones_limpias, user_id());

    $total      = count($operaciones_limpias);
    $procesadas = count($resultado['exitosas']);

    echo json_encode([
        'success'    => true,
        'exitosas'   => $resultado['exitosas'],
        'fallidas'   => $resultado['fallidas'],
        'total'      => $total,
        'procesadas' => $procesadas,
        'mensaje'    => "$procesadas de $total operaciones sincronizadas correctamente.",
    ]);

} catch (Throwable $e) {
    error_log('[Sync API] Error crítico: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Intenta de nuevo.',
    ]);
}
