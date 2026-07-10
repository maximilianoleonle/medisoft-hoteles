<?php
/**
 * Prueba de regresion de la auditoria de Caja (Fable jul-2026). Verifica:
 *
 *  F2) Doble apertura de corte BLOQUEADA: tras abrir, un segundo abrirCaja
 *      devuelve "Ya existe un corte de caja abierto" (candado FOR UPDATE sobre
 *      la fila padre 'cajas', re-check dentro del lock).
 *  F3) Movimiento tras cierre RECHAZADO: registrarMovimiento no inserta en un
 *      corte ya cerrado (revalidacion de estado bajo FOR UPDATE).
 *  F4) cerrarCaja es transaccional y bloquea el doble cierre.
 *  F1) Permisos configurables: el mapeo caja.* separa cobro/gasto/corte/ajustes
 *      y el catalogo expone caja.corte (toggle del editor de roles).
 *
 * Todo el bloque de BD corre dentro de una transaccion que SE REVIERTE.
 * Uso: php src/tools/saas/probar_caja_concurrencia_permisos.php
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); echo "Solo CLI.\n"; exit(1); }

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');

$HOTEL = 2;
$USUARIO = 22;

$_SESSION = [];
$_SESSION['hotel_id'] = $HOTEL;
$_SESSION['user_id'] = $USUARIO;

// Definir el resolver de hotel ANTES de cargar los modelos: el guard
// function_exists de hotel_config.php lo respeta y evita arrastrar todo el
// helper (patron de test_motor_confirmacion.php).
if (!function_exists('obtenerHotelIdActualCompat')) {
    function obtenerHotelIdActualCompat() { return 2; }
}

require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Model.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/models/Caja.php';
require_once APP_PATH . '/models/MovimientoCaja.php';

$fallos = 0;
$ok = 0;
function check($nombre, $cond) {
    global $ok, $fallos;
    if ($cond) { $ok++; echo "  PASS  $nombre\n"; }
    else { $fallos++; echo "  FAIL  $nombre\n"; }
}

echo "== Prueba Caja: concurrencia de cortes + permisos ==\n\n";

/* -------- F1) Mapeo de permisos configurable -------- */
$cfg = require CONFIG_PATH . '/permisos.php';
$catCaja = $cfg['catalogo']['caja']['permisos'] ?? [];
check('F1 catalogo expone caja.corte (configurable en editor de roles)', isset($catCaja['caja.corte']));
check('F1 catalogo expone caja.ajustes', isset($catCaja['caja.ajustes']));

$recep = $cfg['presets']['recepcionista']['permisos'] ?? [];
check('F1 recepcionista puede cobrar (caja.cobros)', permission_in_list('caja.cobros', $recep));
check('F1 recepcionista NO cierra corte por defecto (caja.corte)', !permission_in_list('caja.corte', $recep));
check('F1 recepcionista NO registra gasto (caja.movimientos)', !permission_in_list('caja.movimientos', $recep));

$gerente = $cfg['presets']['gerente']['permisos'] ?? [];
check('F1 gerente cierra corte (caja.corte)', permission_in_list('caja.corte', $gerente));
check('F1 gerente hace ajustes (caja.ajustes)', permission_in_list('caja.ajustes', $gerente));

$admin = $cfg['presets']['administrador']['permisos'] ?? [];
check('F1 administrador cierra corte pero NO ajustes (paridad can_legacy)',
    permission_in_list('caja.corte', $admin) && !permission_in_list('caja.ajustes', $admin));

/* -------- F2/F3/F4) Concurrencia de cortes (rollback) -------- */
$db = Database::getInstance();
$pdo = $db->getConnection();
$cajaModel = new Caja();
$movModel = new MovimientoCaja();

$db->safeBeginTransaction();
try {
    // Punto de partida limpio dentro de la transaccion: cerrar cualquier corte
    // abierto real (se revierte al final, no persiste).
    $pdo->prepare("UPDATE cortes_caja SET estado = 'cerrado' WHERE hotel_id = ? AND estado = 'abierto'")
        ->execute([$HOTEL]);

    $caja = $cajaModel->obtenerCajaPrincipal();
    if (!$caja) { throw new Exception('Hotel ' . $HOTEL . ' sin caja activa para la prueba.'); }
    $cajaId = (int) $caja['id'];

    // Abrir corte.
    $r1 = $cajaModel->abrirCaja($cajaId, 1000.00, $USUARIO);
    check('F2 abrir corte inicial OK', !empty($r1['success']));
    $corteId = (int) ($r1['corte_id'] ?? 0);

    // Segundo abrir de la MISMA caja -> bloqueado.
    $r2 = $cajaModel->abrirCaja($cajaId, 500.00, $USUARIO);
    check('F2 segundo abrir BLOQUEADO (ya existe corte abierto)',
        empty($r2['success']) && strpos((string)($r2['message'] ?? ''), 'Ya existe') !== false);

    // Registrar un ingreso en el corte abierto.
    $rm = $movModel->registrarMovimiento([
        'tipo' => 'ingreso',
        'categoria_id' => 7,
        'descripcion' => 'Ingreso de prueba concurrencia',
        'monto' => 250.00,
        'metodo_pago' => 'efectivo',
        'referencia' => null,
    ]);
    check('F3 registrar movimiento con corte abierto OK', !empty($rm['success']));

    // Cerrar el corte.
    $rc = $cajaModel->cerrarCaja($corteId, 1250.00, 'cierre prueba', $USUARIO);
    check('F4 cerrar corte OK', !empty($rc['success']));

    // Doble cierre -> bloqueado.
    $rc2 = $cajaModel->cerrarCaja($corteId, 1250.00, 'segundo cierre', $USUARIO);
    check('F4 segundo cierre BLOQUEADO', empty($rc2['success']));

    // Movimiento despues del cierre -> rechazado (no cae en corte arqueado).
    $rm2 = $movModel->registrarMovimiento([
        'tipo' => 'ingreso',
        'categoria_id' => 7,
        'descripcion' => 'Ingreso tardio tras cierre',
        'monto' => 99.00,
        'metodo_pago' => 'efectivo',
        'referencia' => null,
    ]);
    check('F3 movimiento tras cierre RECHAZADO (no descuadra el arqueo)', empty($rm2['success']));

    throw new Exception('__ROLLBACK_OK__');
} catch (Throwable $e) {
    if ($db->enTransaccion()) { $db->safeRollBack(); }
    if ($e->getMessage() !== '__ROLLBACK_OK__') {
        echo "\n  ERROR de la prueba: " . $e->getMessage() . "\n";
        $fallos++;
    }
}

echo "\n== Resumen == PASS: {$ok}  FAIL: {$fallos}\n";
echo "  (rollback ejecutado: no persistio nada)\n";
exit($fallos > 0 ? 1 : 0);
