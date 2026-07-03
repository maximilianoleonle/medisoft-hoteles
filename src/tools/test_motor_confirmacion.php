<?php
/**
 * Arnes CLI: prueba E2E interna del motor de reservas (Paso 0 del testeo).
 *
 * Simula el camino webhook -> confirmarPagoPorHold SIN pasarela real:
 *  1. Crea hold + pago 'pendiente' (como lo haria iniciarPago).
 *  2. Llama MotorReservaOnlineService::confirmarPagoPorHold como si la
 *     pasarela hubiera confirmado el cobro.
 *  3. Verifica: huesped creado/reutilizado, reservacion confirmada con
 *     habitacion y precio, ledger 'pagado' ligado, hold eliminado,
 *     idempotencia (segunda llamada no duplica) y CERO movimientos de Caja.
 *
 * Deja el pago 'pagado' en el tablero /motor-reservas para probar el boton
 * "Conciliar a Caja" desde la UI. Solo CLI:
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/test_motor_confirmacion.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

define('ROOT_PATH', dirname(__DIR__, 2) === '/' ? '/var/www' : dirname(__DIR__, 2));
define('APP_PATH', __DIR__ . '/../app');

// Cargar .env como lo hace index.php (el env del contenedor puede estar desactualizado).
$envPath = '/var/www/.env';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
        $linea = trim($linea);
        if ($linea === '' || strpos($linea, '#') === 0 || strpos($linea, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $linea, 2);
        $k = trim($k);
        if ($k !== '' && getenv($k) === false) {
            putenv($k . '=' . trim($v));
        }
    }
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/TenantContext.php';
require_once __DIR__ . '/../core/Model.php';

// Stub del helper de tenant para no cargar los helpers web completos.
if (!function_exists('obtenerHotelIdActualCompat')) {
    function obtenerHotelIdActualCompat()
    {
        return TenantContext::hotelId();
    }
}

require_once __DIR__ . '/../app/models/ConfiguracionHotelRegistry.php';
require_once __DIR__ . '/../app/models/Habitacion.php';
require_once __DIR__ . '/../app/models/IncrementoTarifa.php';
require_once __DIR__ . '/../app/models/Huesped.php';
require_once __DIR__ . '/../app/models/Reservacion.php';
require_once __DIR__ . '/../app/services/MotorReservaOnlineService.php';

$fallos = 0;
$check = function (string $nombre, bool $ok, string $detalle = '') use (&$fallos) {
    echo ($ok ? '  PASS  ' : '  FAIL  ') . $nombre . ($detalle !== '' ? ' — ' . $detalle : '') . PHP_EOL;
    if (!$ok) {
        $fallos++;
    }
};

echo "=== Motor de reservas: prueba interna de confirmacion (sin pasarela) ===\n\n";

$db = Database::getInstance();
$pdo = $db->getConnection();

// Hotel demo (2 = hotel-demo-saas, con bloque y flag activos).
$stmt = $pdo->prepare("SELECT id, nombre, slug, activo, moneda_codigo, moneda_simbolo FROM hoteles WHERE slug = 'hotel-demo-saas' LIMIT 1");
$stmt->execute();
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$hotel) {
    echo "ABORT: hotel demo no encontrado\n";
    exit(1);
}
TenantContext::setHotel($hotel);
$hotelId = (int) $hotel['id'];
echo "Hotel de prueba: {$hotel['nombre']} (id {$hotelId})\n";

// Conteo de movimientos de caja ANTES (el motor jamas debe tocar Caja).
$movsCajaAntes = (int) $pdo->query("SELECT COUNT(*) FROM movimientos_caja WHERE hotel_id = {$hotelId}")->fetchColumn();

// Fechas y habitacion disponible tipo 'doble'.
$entrada = date('Y-m-d', strtotime('+14 days'));
$salida = date('Y-m-d', strtotime('+16 days'));

$habitacionModel = new Habitacion();
$disponibles = $habitacionModel->disponiblesEntreFechas($entrada, $salida);
$candidata = null;
foreach ($disponibles as $hab) {
    if ((string) $hab['tipo'] === 'doble') {
        $candidata = $hab;
        break;
    }
}
if (!$candidata) {
    echo "ABORT: sin habitacion 'doble' disponible para {$entrada}..{$salida}\n";
    exit(1);
}
echo "Habitacion candidata: #{$candidata['numero']} (id {$candidata['id']}) {$entrada} -> {$salida}\n\n";

// 1) Simular iniciarPago: hold + pago pendiente (mismo formato que el servicio).
$holdToken = bin2hex(random_bytes(16));
$anticipo = 510.00; // 30% de 2 noches doble ($1,700) segun la API publica ya verificada

$pdo->prepare("INSERT INTO motor_holds (hotel_id, token, habitacion_ids_json, fecha_entrada, fecha_salida, expires_at, created_at)
               VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 20 MINUTE), NOW())")
    ->execute([$hotelId, $holdToken, json_encode([(int) $candidata['id']]), $entrada, $salida]);

$pdo->prepare("INSERT INTO motor_pagos_online
                (hotel_id, proveedor, proveedor_pago_id, monto, moneda, estado,
                 huesped_nombre, huesped_email, huesped_telefono, payload_json, created_at, updated_at)
               VALUES (?, 'stripe', ?, ?, 'MXN', 'pendiente', ?, ?, ?, ?, NOW(), NOW())")
    ->execute([
        $hotelId,
        'cs_test_simulado_' . substr($holdToken, 0, 12),
        number_format($anticipo, 2, '.', ''),
        'Prueba Interna Motor',
        'prueba.motor@test.local',
        '5599887766',
        json_encode([
            'hold_token' => $holdToken,
            'entrada' => $entrada,
            'salida' => $salida,
            'personas' => 2,
            'tipo' => 'doble',
            'habitacion_id' => (int) $candidata['id'],
            'precio_total_estancia' => 1700.00,
            'anticipo' => $anticipo,
        ]),
    ]);

$check('Hold y pago pendiente creados (simulando iniciarPago)', true);

// 2) Confirmar como lo haria el webhook.
$servicio = new MotorReservaOnlineService();
$resultado = $servicio->confirmarPagoPorHold($hotelId, $holdToken, 'pi_test_simulado', [
    'id' => 'evt_test_simulado',
    'type' => 'checkout.session.completed',
]);

$check('confirmarPagoPorHold devuelve success', !empty($resultado['success']), json_encode($resultado));
$reservacionId = (int) ($resultado['reservacion_id'] ?? 0);
$check('Reservacion creada con id', $reservacionId > 0, 'id=' . $reservacionId);

if ($reservacionId > 0) {
    // 3) Verificaciones de integridad.
    $stmt = $pdo->prepare("SELECT * FROM reservaciones WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$reservacionId, $hotelId]);
    $reservacion = $stmt->fetch(PDO::FETCH_ASSOC);

    $check('Reservacion pertenece al hotel y esta confirmada',
        $reservacion && $reservacion['estado'] === 'confirmada',
        'estado=' . ($reservacion['estado'] ?? 'null'));
    $check('Precio total correcto ($1,700.00)',
        $reservacion && abs((float) $reservacion['precio_total'] - 1700.00) < 0.01,
        'precio_total=' . ($reservacion['precio_total'] ?? 'null'));
    $check('Notas marcan pendiente de conciliar',
        $reservacion && strpos((string) $reservacion['notas'], 'PENDIENTE DE CONCILIAR') !== false);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservacion_habitaciones WHERE reservacion_id = ? AND hotel_id = ? AND habitacion_id = ?");
    $stmt->execute([$reservacionId, $hotelId, (int) $candidata['id']]);
    $check('Habitacion ligada a la reservacion', (int) $stmt->fetchColumn() === 1);

    $stmt = $pdo->prepare("SELECT h.* FROM huespedes h INNER JOIN reservaciones r ON r.huesped_id = h.id WHERE r.id = ?");
    $stmt->execute([$reservacionId]);
    $huesped = $stmt->fetch(PDO::FETCH_ASSOC);
    $check('Huesped creado y scoped al hotel',
        $huesped && (int) $huesped['hotel_id'] === $hotelId && $huesped['telefono'] === '5599887766',
        'huesped_id=' . ($huesped['id'] ?? 'null'));

    $stmt = $pdo->prepare("SELECT * FROM motor_pagos_online WHERE hotel_id = ? AND JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.hold_token')) = ?");
    $stmt->execute([$hotelId, $holdToken]);
    $pago = $stmt->fetch(PDO::FETCH_ASSOC);
    $check('Ledger en estado pagado y ligado a la reservacion',
        $pago && $pago['estado'] === 'pagado' && (int) $pago['reservacion_id'] === $reservacionId,
        'estado=' . ($pago['estado'] ?? 'null'));

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM motor_holds WHERE token = ?");
    $stmt->execute([$holdToken]);
    $check('Hold eliminado tras confirmar', (int) $stmt->fetchColumn() === 0);

    // 4) Idempotencia: el webhook se reintenta; no debe duplicar nada.
    $reservasAntes = (int) $pdo->query("SELECT COUNT(*) FROM reservaciones WHERE hotel_id = {$hotelId}")->fetchColumn();
    $repetido = $servicio->confirmarPagoPorHold($hotelId, $holdToken, 'pi_test_simulado', ['id' => 'evt_reintento']);
    $reservasDespues = (int) $pdo->query("SELECT COUNT(*) FROM reservaciones WHERE hotel_id = {$hotelId}")->fetchColumn();
    $check('Webhook repetido responde ok sin duplicar',
        !empty($repetido['success']) && $reservasAntes === $reservasDespues
            && (int) ($repetido['reservacion_id'] ?? 0) === $reservacionId);

    // 5) Regla de oro: cero movimientos de Caja desde el motor.
    $movsCajaDespues = (int) $pdo->query("SELECT COUNT(*) FROM movimientos_caja WHERE hotel_id = {$hotelId}")->fetchColumn();
    $check('CERO movimientos de Caja generados por el motor', $movsCajaAntes === $movsCajaDespues,
        "antes={$movsCajaAntes} despues={$movsCajaDespues}");
}

echo "\n=== Resultado: " . ($fallos === 0 ? 'TODAS LAS PRUEBAS PASARON' : "{$fallos} FALLO(S)") . " ===\n";
if ($fallos === 0 && $reservacionId > 0) {
    echo "El pago quedo 'pagado' en /motor-reservas del hotel demo: usalo para probar 'Conciliar a Caja' desde la UI.\n";
    echo "Reservacion de prueba: #{$reservacionId} (huesped 'Prueba Interna Motor').\n";
}
exit($fallos === 0 ? 0 : 1);
