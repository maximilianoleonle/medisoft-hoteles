<?php
/**
 * Prueba CLI de INTEGRACION del modo legal (Fase 6): config del negocio en
 * modo legal + reglas de prueba + NominaCalculoService::preview.
 * Todo con rollback: no persiste nada.
 *
 * Requiere los datos QA del hotel demo (grupo 'QA Quincenal' y trabajador
 * 'QA Nomina Empleado' con salario quincenal 3000 de las fases 2/3).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Solo CLI.\n";
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');

require_once CORE_PATH . '/Database.php';
require_once APP_PATH . '/models/ConfiguracionHotelRegistry.php';
require_once APP_PATH . '/services/NominaReglasLegalesService.php';
require_once APP_PATH . '/services/NominaCalculoService.php';

$fallos = 0;
function prueba(string $nombre, $esperado, $obtenido): void
{
    global $fallos;
    if ($esperado === $obtenido) {
        echo "  PASS  {$nombre}\n";
    } else {
        echo "  FAIL  {$nombre} — esperado [" . var_export($esperado, true) . "], obtuvo [" . var_export($obtenido, true) . "]\n";
        $fallos++;
    }
}

echo "=== Prueba CLI: preview en modo legal (rollback al final) ===\n\n";

$db = Database::getInstance();
$pdo = $db->getConnection();
$db->safeBeginTransaction();

try {
    $hotelId = 2;

    $st = $pdo->prepare("SELECT id FROM nomina_grupos WHERE hotel_id = ? AND nombre = 'QA Quincenal'");
    $st->execute([$hotelId]);
    $grupoId = (int) ($st->fetch()['id'] ?? 0);

    if ($grupoId === 0) {
        echo "  INFO  sin datos QA (grupo); prueba omitida sin fallo.\n";
        $db->safeRollBack();
        exit(0);
    }

    // Config del negocio: modo legal (se revierte con el rollback).
    $st = $pdo->prepare(
        "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
         VALUES (?, 'nomina.modo', 'legal', 'string', 'nomina', 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE valor = 'legal', updated_at = NOW()"
    );
    $st->execute([$hotelId]);

    // Reglas de prueba.
    $reglas = new NominaReglasLegalesService($db);
    $reglas->crear([
        'pais' => 'MX', 'tipo_regla' => 'isr_tabla_quincenal', 'ejercicio' => 2026,
        'vigente_desde' => '2026-01-01',
        'valores_json' => json_encode(['tramos' => [
            ['limite_inferior' => 0.01, 'limite_superior' => 1000.00, 'cuota_fija' => 0.00, 'porcentaje_excedente' => 2.00],
            ['limite_inferior' => 1000.01, 'limite_superior' => null, 'cuota_fija' => 20.00, 'porcentaje_excedente' => 10.00],
        ]]),
        'fuente' => 'PRUEBA CLI (rollback)',
    ], null);

    // Preview en rango futuro sin solape ni incidencias.
    $calculo = new NominaCalculoService($db);
    $preview = $calculo->preview($hotelId, $grupoId, '2030-02-01', '2030-02-15');

    prueba('preview en modo legal', 'legal', $preview['modo']);
    prueba('reglas fiscales congelables presentes', true, is_array($preview['reglas_fiscales']));
    prueba('ISR resuelto en reglas congelables', true, ($preview['reglas_fiscales']['isr'] ?? null) !== null);
    prueba('preview NO bloqueado', false, $preview['bloqueado']);

    $fila = $preview['trabajadores'][0] ?? null;
    prueba('trabajador calculado', true, $fila !== null);

    $lineaIsr = null;
    foreach (($fila['lineas'] ?? []) as $l) {
        if (($l['origen'] ?? '') === 'fiscal' && strpos((string) $l['concepto_nombre'], 'ISR') === 0) {
            $lineaIsr = $l;
        }
    }

    // Salario quincenal 3000 => base gravable 3000 => ISR 20+(3000-1000.01)*10% = 220.00
    prueba('linea fiscal ISR presente', true, $lineaIsr !== null);
    prueba('ISR sobre sueldo 3000 = 220.00', 220.0, $lineaIsr['monto'] ?? null);
    prueba('neto = 3000 - 220 = 2780.00', 2780.0, $fila['neto'] ?? null);

    // Alerta IMSS omitido (sin cuotas en esta prueba).
    $alertasTexto = implode(' ', $preview['alertas']);
    prueba('alerta de IMSS omitido presente', true, strpos($alertasTexto, 'IMSS') !== false);
} catch (Throwable $e) {
    echo "  FAIL  excepcion inesperada: " . $e->getMessage() . "\n";
    $fallos++;
} finally {
    $db->safeRollBack();
    echo "\n  INFO  rollback ejecutado: ningun dato persistio.\n";
}

echo "\n" . ($fallos === 0 ? "=== RESULTADO: TODAS LAS PRUEBAS PASARON ===\n" : "=== RESULTADO: {$fallos} FALLO(S) ===\n");
exit($fallos === 0 ? 0 : 1);
