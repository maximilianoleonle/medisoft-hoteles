<?php
/**
 * Prueba CLI del servicio de reglas legales de nomina (Fase 5).
 * TODO corre dentro de una transaccion que se REVIERTE al final:
 * no persiste nada. Formato PASS/FAIL con exit code.
 *
 * Uso (contenedor): php /var/www/html/tools/saas/probar_nomina_reglas.php
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
require_once APP_PATH . '/services/NominaReglasLegalesService.php';

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

echo "=== Prueba CLI: NominaReglasLegalesService (rollback al final) ===\n\n";

$db = Database::getInstance();
$db->safeBeginTransaction();

try {
    $servicio = new NominaReglasLegalesService($db);

    // 1) Resolucion de semillas estatutarias.
    $aguinaldo = $servicio->vigente('MX', 'aguinaldo_dias_minimo', '2026-07-04');
    prueba('aguinaldo LFT resuelto a 2026-07-04', 15.0, $aguinaldo !== null ? (float) $aguinaldo['valor'] : null);

    $vacaciones = $servicio->vigente('MX', 'vacaciones_tabla', '2026-07-04');
    $tabla = $vacaciones !== null ? json_decode((string) $vacaciones['valores_json'], true) : null;
    prueba('tabla de vacaciones JSON valida', true, is_array($tabla) && isset($tabla['tramos']));

    $uma2025 = $servicio->vigente('MX', 'uma_diaria', '2025-06-15');
    prueba('UMA 2025 resuelta a mitad de 2025', 113.14, $uma2025 !== null ? (float) $uma2025['valor'] : null);

    $umaAntes = $servicio->vigente('MX', 'uma_diaria', '2025-01-15');
    prueba('UMA no vigente antes de su fecha (2025-01-15)', null, $umaAntes);

    // 2) Version nueva cierra la anterior.
    $nuevaId = $servicio->crear([
        'pais' => 'MX',
        'tipo_regla' => 'uma_diaria',
        'ejercicio' => 2026,
        'vigente_desde' => '2026-02-01',
        'valor' => '999.9900',
        'fuente' => 'PRUEBA CLI (rollback)',
        'descripcion' => 'valor de prueba, no persiste',
    ], null);
    prueba('crear version 2026 devuelve id', true, $nuevaId > 0);

    $uma2026 = $servicio->vigente('MX', 'uma_diaria', '2026-07-04');
    prueba('resolucion 2026 usa la version nueva', 999.99, $uma2026 !== null ? (float) $uma2026['valor'] : null);

    $uma2025b = $servicio->vigente('MX', 'uma_diaria', '2025-06-15');
    prueba('la version 2025 sigue resolviendo su rango (cerrada, no borrada)', 113.14, $uma2025b !== null ? (float) $uma2025b['valor'] : null);
    prueba('la version 2025 quedo cerrada al 2026-01-31', '2026-01-31', $uma2025b['vigente_hasta'] ?? null);

    // 3) Vigencia anterior o igual rechazada.
    $rechazada = false;
    try {
        $servicio->crear([
            'pais' => 'MX', 'tipo_regla' => 'uma_diaria', 'ejercicio' => 2026,
            'vigente_desde' => '2026-01-01', 'valor' => '1', 'fuente' => 'x',
        ], null);
    } catch (Throwable $e) {
        $rechazada = true;
    }
    prueba('vigencia no posterior rechazada', true, $rechazada);

    // 4) Sin fuente rechazada.
    $rechazada = false;
    try {
        $servicio->crear([
            'pais' => 'MX', 'tipo_regla' => 'regla_de_prueba', 'ejercicio' => 2026,
            'vigente_desde' => '2026-01-01', 'valor' => '1', 'fuente' => '',
        ], null);
    } catch (Throwable $e) {
        $rechazada = true;
    }
    prueba('regla sin fuente oficial rechazada', true, $rechazada);

    // 5) Desactivar saca la regla de la resolucion.
    $servicio->alternarEstado($nuevaId, null);
    $umaTrasDesactivar = $servicio->vigente('MX', 'uma_diaria', '2026-07-04');
    prueba('desactivada NO resuelve (y no cae a la 2025 cerrada)', null, $umaTrasDesactivar);

    // 6) Historial de eventos registrado.
    $eventos = $servicio->eventos($nuevaId);
    prueba('historial con creada + desactivada', 2, count($eventos));
} catch (Throwable $e) {
    echo "  FAIL  excepcion inesperada: " . $e->getMessage() . "\n";
    $fallos++;
} finally {
    $db->safeRollBack();
    echo "\n  INFO  rollback ejecutado: ningun dato persistio.\n";
}

echo "\n" . ($fallos === 0 ? "=== RESULTADO: TODAS LAS PRUEBAS PASARON ===\n" : "=== RESULTADO: {$fallos} FALLO(S) ===\n");
exit($fallos === 0 ? 0 : 1);
