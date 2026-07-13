<?php
/**
 * Orquestador de pruebas de CONCURRENCIA de dinero (Caja) — Medisoft.
 *
 * Lanza N procesos worker EN PARALELO (proc_open) que atacan el MISMO recurso
 * en el mismo instante (gate sincronizado) y verifica que los candados
 * FOR UPDATE hacen cumplir los invariantes bajo carrera real:
 *
 *   P1 (doble apertura): 25 workers abren la misma caja a la vez
 *       → exactamente 1 corte abierto, exactamente 1 success.
 *   P2 (suma exacta): 40 workers registran $100 a la vez en el corte abierto
 *       → 40 movimientos, suma 4000, todos en el corte correcto.
 *   P3 (cierre vs registro): 1 cierra mientras 25 registran, simultáneos
 *       → corte cerrado; ningún movimiento se cuela después; los totales
 *         congelados del cierre = suma de los movimientos que SÍ entraron.
 *
 * Corre contra la BD de PRUEBA (bootstrap fuerza medisoft_test).
 *   docker exec medisoft_hoteles_app php /var/www/html/tests/concurrencia/run_concurrencia.php
 */

require_once __DIR__ . '/../bootstrap.php';

$WORKER = __DIR__ . '/worker_caja.php';
$fallos = 0;

function lanzarEnParalelo(string $worker, array $args, int $n, float $gateUs): array
{
    $procs = [];
    $pipes = [];
    for ($i = 0; $i < $n; $i++) {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($worker) . ' '
            . implode(' ', array_map('escapeshellarg', array_merge($args, [(string) $gateUs])));
        $p = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipe);
        $procs[$i] = $p;
        $pipes[$i] = $pipe;
    }

    $resultados = [];
    foreach ($procs as $i => $p) {
        $out = stream_get_contents($pipes[$i][1]);
        fclose($pipes[$i][1]);
        fclose($pipes[$i][2]);
        proc_close($p);
        foreach (explode("\n", trim($out)) as $linea) {
            $linea = trim($linea);
            if ($linea !== '' && $linea[0] === '{') {
                $resultados[] = json_decode($linea, true);
            }
        }
    }
    return $resultados;
}

function gate(int $ms = 400): float
{
    return (microtime(true) * 1_000_000) + ($ms * 1000);
}

$db = Database::getInstance();

echo "== Concurrencia de Caja (procesos paralelos, gate sincronizado) ==\n";

// ─────────────────────────────────────────────────────────────────────────
// P1 — Doble apertura: 25 workers abren la MISMA caja al mismo tiempo
// ─────────────────────────────────────────────────────────────────────────
t_reset_db();
$base = t_seed_base('concurrencia-test');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];
$caja = new Caja();
$caja->ensureDefaultCajaForHotel($hotelId);
$cajaId = (int) $caja->obtenerCajaPrincipal()['id'];

$res = lanzarEnParalelo($WORKER, ['abrir', $hotelId, $usuarioId, $cajaId, 0], 25, gate());
$exitos = count(array_filter($res, fn ($r) => $r['ok'] ?? false));
$abiertos = (int) $db->query(
    "SELECT COUNT(*) FROM cortes_caja WHERE caja_id = ? AND estado = 'abierto'",
    [$cajaId]
)->fetchColumn();

echo "\nP1 doble apertura (25 simultáneos):\n";
echo "   aperturas exitosas: $exitos (esperado 1)\n";
echo "   cortes abiertos en BD: $abiertos (esperado 1)\n";
if ($exitos === 1 && $abiertos === 1) { echo "   PASS\n"; } else { echo "   FAIL\n"; $fallos++; }

// ─────────────────────────────────────────────────────────────────────────
// P2 — Suma exacta: 40 workers registran $100 en paralelo en el corte abierto
// ─────────────────────────────────────────────────────────────────────────
$corteId = (int) $db->query(
    "SELECT id FROM cortes_caja WHERE caja_id = ? AND estado = 'abierto'",
    [$cajaId]
)->fetchColumn();

$res = lanzarEnParalelo($WORKER, ['registrar', $hotelId, $usuarioId, $cajaId, $corteId], 40, gate());
$okReg = count(array_filter($res, fn ($r) => $r['ok'] ?? false));
$fila = $db->query(
    "SELECT COUNT(*) c, COALESCE(SUM(monto),0) s FROM movimientos_caja WHERE corte_id = ?",
    [$corteId]
)->fetch();

echo "\nP2 registro concurrente (40 simultáneos de \$100):\n";
echo "   registros exitosos: $okReg (esperado 40)\n";
echo "   movimientos en BD: {$fila['c']} · suma: {$fila['s']} (esperado 40 · 4000.00)\n";
if ($okReg === 40 && (int) $fila['c'] === 40 && round((float) $fila['s'], 2) === 4000.00) {
    echo "   PASS\n";
} else { echo "   FAIL\n"; $fallos++; }

// ─────────────────────────────────────────────────────────────────────────
// P3 — Cierre vs registro: 1 cierra mientras 25 registran, todos a la vez
// ─────────────────────────────────────────────────────────────────────────
// Nueva caja/corte limpios para aislar la prueba.
t_reset_db();
$base = t_seed_base('concurrencia-cierre');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];
$caja = new Caja();
$caja->ensureDefaultCajaForHotel($hotelId);
$cajaId = (int) $caja->obtenerCajaPrincipal()['id'];
$ap = $caja->abrirCaja($cajaId, 1000.00, $usuarioId);
$corteId = (int) $ap['corte_id'];

$g = gate();
// Mezclamos 25 "registrar" + 1 "cerrar" con el MISMO gate: carrera real.
$procs = [];
$pipes = [];
$plan = array_fill(0, 25, 'registrar');
$plan[] = 'cerrar';
shuffle($plan);
foreach ($plan as $op) {
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($WORKER) . ' '
        . implode(' ', array_map('escapeshellarg', [$op, $hotelId, $usuarioId, $cajaId, $corteId, (string) $g]));
    $p = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipe);
    $procs[] = $p; $pipes[] = $pipe;
}
$res = [];
foreach ($procs as $i => $p) {
    $out = stream_get_contents($pipes[$i][1]);
    fclose($pipes[$i][1]); fclose($pipes[$i][2]); proc_close($p);
    foreach (explode("\n", trim($out)) as $l) {
        $l = trim($l);
        if ($l !== '' && $l[0] === '{') { $res[] = json_decode($l, true); }
    }
}

$corte = $db->query("SELECT estado, total_ingresos_efectivo FROM cortes_caja WHERE id = ?", [$corteId])->fetch();
$movs = $db->query("SELECT COUNT(*) c, COALESCE(SUM(monto),0) s FROM movimientos_caja WHERE corte_id = ?", [$corteId])->fetch();
$regOk = count(array_filter($res, fn ($r) => ($r['op'] ?? '') === 'registrar' && ($r['ok'] ?? false)));
$cierreOk = count(array_filter($res, fn ($r) => ($r['op'] ?? '') === 'cerrar' && ($r['ok'] ?? false)));

echo "\nP3 cierre vs registro (1 cierre + 25 registros simultáneos):\n";
echo "   estado final del corte: {$corte['estado']} (esperado cerrado)\n";
echo "   registros que entraron: $regOk · movimientos en BD: {$movs['c']}\n";
echo "   total congelado en cierre: {$corte['total_ingresos_efectivo']} · suma real movimientos: {$movs['s']}\n";

// Invariante clave: NINGÚN movimiento se contó fuera del arqueo. Si el cierre
// ganó la carrera, el total congelado = suma de los movimientos aceptados.
// (los registros que perdieron la carrera fueron rechazados con "caja se cerró")
$cuadra = ($corte['estado'] === 'cerrado')
    && ((int) $movs['c'] === $regOk)
    && (round((float) $corte['total_ingresos_efectivo'], 2) === round((float) $movs['s'], 2));

if ($cuadra && $cierreOk === 1) { echo "   PASS\n"; } else { echo "   FAIL\n"; $fallos++; }

// ─────────────────────────────────────────────────────────────────────────
echo "\n==============================\n";
if ($fallos === 0) {
    echo "CONCURRENCIA OK: los candados de dinero resisten la carrera.\n";
    exit(0);
}
echo "FALLARON $fallos prueba(s) de concurrencia.\n";
exit(1);
