<?php
/**
 * Worker de concurrencia de Caja — ejecuta UNA operación de dinero y sale.
 *
 * Cada worker es un PROCESO independiente (conexión PDO propia): así los
 * candados FOR UPDATE de Caja se tensan de verdad, no simulados en un hilo.
 * Un "gate" (microtime objetivo) alinea a todos los workers para que ataquen
 * el mismo recurso en el mismo instante — máxima probabilidad de carrera.
 *
 * Uso (lo invoca run_concurrencia.php, no a mano):
 *   php worker_caja.php <op> <hotel_id> <usuario_id> <caja_id> <corte_id> <gate_us>
 *   op ∈ { abrir | registrar | cerrar }
 *
 * Imprime UNA línea JSON con el resultado. Nunca lanza: los errores se
 * reportan como {"ok":false,...} para que el orquestador los cuente.
 */

require_once __DIR__ . '/../bootstrap.php';

[$_, $op, $hotelId, $usuarioId, $cajaId, $corteId, $gateUs] = array_pad($argv, 7, null);
$hotelId = (int) $hotelId;
$usuarioId = (int) $usuarioId;
$cajaId = (int) $cajaId;
$corteId = (int) $corteId;
$gateUs = (float) $gateUs;

// Contexto de tenant idéntico al de una petición real de este hotel.
TenantContext::boot([
    'hotel' => ['id' => $hotelId, 'slug' => 'concurrencia-test'],
    'usuario_id' => $usuarioId,
    'roles' => ['administrador'],
]);
$_SESSION['user_id'] = $usuarioId;
$_SESSION['hotel_id'] = $hotelId;

// Esperar al gate: alinea el ataque de todos los workers al mismo microsegundo.
$ahora = microtime(true) * 1_000_000;
if ($gateUs > $ahora) {
    usleep((int) ($gateUs - $ahora));
}

$salida = ['op' => $op, 'ok' => false, 'msg' => ''];

try {
    if ($op === 'abrir') {
        $caja = new Caja();
        $r = $caja->abrirCaja($cajaId, 1000.00, $usuarioId);
        $salida['ok'] = !empty($r['success']);
        $salida['msg'] = $r['message'] ?? '';
        $salida['corte_id'] = $r['corte_id'] ?? null;
    } elseif ($op === 'registrar') {
        $mov = new MovimientoCaja();
        $r = $mov->registrarMovimiento([
            'tipo' => 'ingreso',
            'monto' => 100.00,
            'metodo_pago' => 'efectivo',
            'descripcion' => 'Concurrencia PID ' . getmypid(),
            'categoria' => 'Otros ingresos',
        ]);
        $salida['ok'] = !empty($r['success']);
        $salida['msg'] = $r['message'] ?? '';
    } elseif ($op === 'cerrar') {
        $caja = new Caja();
        $r = $caja->cerrarCaja($corteId, 0.00, 'Cierre concurrente', $usuarioId);
        $salida['ok'] = !empty($r['success']);
        $salida['msg'] = $r['message'] ?? '';
    } else {
        $salida['msg'] = "op desconocida: $op";
    }
} catch (Throwable $e) {
    $salida['ok'] = false;
    $salida['msg'] = 'EXCEPCION: ' . $e->getMessage();
}

echo json_encode($salida) . "\n";
