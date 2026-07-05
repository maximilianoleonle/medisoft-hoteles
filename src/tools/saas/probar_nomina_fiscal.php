<?php
/**
 * Prueba CLI del motor legal/fiscal (Fase 6). Todo con rollback: no persiste.
 *
 * Usa una tabla ISR de PRUEBA con tramos conocidos para verificar la
 * mecanica del calculo (no valores reales del DOF): la exactitud legal
 * depende del catalogo que capture el owner con fuente oficial.
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
require_once APP_PATH . '/services/NominaFiscalService.php';

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

echo "=== Prueba CLI: NominaFiscalService (rollback al final) ===\n\n";

$db = Database::getInstance();
$db->safeBeginTransaction();

try {
    $reglas = new NominaReglasLegalesService($db);
    $fiscal = new NominaFiscalService($reglas);

    // --- Sembrar reglas de PRUEBA (se revierten). ---
    // Tabla ISR quincenal de prueba: 3 tramos simples.
    $reglas->crear([
        'pais' => 'MX', 'tipo_regla' => 'isr_tabla_quincenal', 'ejercicio' => 2026,
        'vigente_desde' => '2026-01-01',
        'valores_json' => json_encode(['tramos' => [
            ['limite_inferior' => 0.01, 'limite_superior' => 1000.00, 'cuota_fija' => 0.00, 'porcentaje_excedente' => 2.00],
            ['limite_inferior' => 1000.01, 'limite_superior' => 5000.00, 'cuota_fija' => 20.00, 'porcentaje_excedente' => 10.00],
            ['limite_inferior' => 5000.01, 'limite_superior' => null, 'cuota_fija' => 420.00, 'porcentaje_excedente' => 20.00],
        ]]),
        'fuente' => 'PRUEBA CLI (rollback)',
    ], null);

    $reglas->crear([
        'pais' => 'MX', 'tipo_regla' => 'subsidio_empleo_tabla', 'ejercicio' => 2026,
        'vigente_desde' => '2026-01-01',
        'valores_json' => json_encode(['tramos' => [
            ['hasta' => 2000.00, 'subsidio' => 100.00],
            ['hasta' => null, 'subsidio' => 0.00],
        ]]),
        'fuente' => 'PRUEBA CLI (rollback)',
    ], null);

    $reglas->crear([
        'pais' => 'MX', 'tipo_regla' => 'imss_cuotas_obrero', 'ejercicio' => 2026,
        'vigente_desde' => '2026-01-01',
        'valores_json' => json_encode(['conceptos' => [
            ['nombre' => 'Prueba cuota SBC', 'porcentaje' => 2.00, 'base' => 'sbc'],
            ['nombre' => 'Prueba excedente 3 UMA', 'porcentaje' => 1.00, 'base' => 'sbc_excedente_3uma'],
        ]]),
        'fuente' => 'PRUEBA CLI (rollback)',
    ], null);

    $reglas->crear([
        'pais' => 'MX', 'tipo_regla' => 'factor_integracion_minimo', 'ejercicio' => 2026,
        'vigente_desde' => '2026-01-01', 'valor' => '1.0452',
        'fuente' => 'PRUEBA CLI (rollback)',
    ], null);

    $reglas->crear([
        'pais' => 'MX', 'tipo_regla' => 'uma_diaria', 'ejercicio' => 2026,
        'vigente_desde' => '2026-02-01', 'valor' => '120.0000',
        'fuente' => 'PRUEBA CLI (rollback)',
    ], null);

    // --- Resolucion de reglas. ---
    $resolucion = $fiscal->resolverReglas('MX', 'quincenal', '2026-07-04');
    prueba('resolucion completa NO bloqueada', false, $resolucion['bloqueado']);
    prueba('tabla ISR quincenal resuelta', true, $resolucion['reglas']['isr'] !== null);

    $resolucionSemanal = $fiscal->resolverReglas('MX', 'semanal', '2026-07-04');
    prueba('sin tabla ISR semanal => BLOQUEADO', true, $resolucionSemanal['bloqueado']);

    // --- ISR tramo 2: base 3000 => 20 + (3000-1000.01)*10% = 219.9990 ~ 220.00;
    //     subsidio 0 (base > 2000) => ISR 220.00. ---
    $r = $fiscal->lineasFiscales(3000.00, 200.00, 15, 'quincenal', $resolucion['reglas']);
    $isr = null;
    $imss = null;
    foreach ($r['lineas'] as $l) {
        if (strpos($l['concepto_nombre'], 'ISR') === 0) { $isr = $l['monto']; }
        if (strpos($l['concepto_nombre'], 'IMSS') === 0) { $imss = $l['monto']; }
    }
    prueba('ISR base 3000 (tramo 2) = 220.00', 220.0, $isr);

    // IMSS: SBC = 200*1.0452 = 209.04; cuota sbc 2% * 209.04 * 15 = 62.712;
    // excedente 3 UMA (360): max(0, 209.04-360)=0 => total 62.71.
    prueba('IMSS obrero (2% SBC 15 dias) = 62.71', 62.71, $imss);

    // --- Subsidio: base 1500 => ISR = 20+(1500-1000.01)*10% = 69.999 ~ 70.00;
    //     subsidio 100 > ISR => retencion 0 y alerta. ---
    $r2 = $fiscal->lineasFiscales(1500.00, 0.0, 15, 'quincenal', $resolucion['reglas']);
    $tieneIsr = false;
    foreach ($r2['lineas'] as $l) {
        if (strpos($l['concepto_nombre'], 'ISR') === 0) { $tieneIsr = true; }
    }
    prueba('subsidio > ISR => sin linea ISR', false, $tieneIsr);
    prueba('alerta de subsidio emitida', true, count($r2['alertas']) > 0);

    // --- Tramo 3 (abierto): base 10000 => 420 + (10000-5000.01)*20% = 1419.998 ~ 1420.00. ---
    $r3 = $fiscal->lineasFiscales(10000.00, 0.0, 15, 'quincenal', $resolucion['reglas']);
    $isr3 = null;
    foreach ($r3['lineas'] as $l) {
        if (strpos($l['concepto_nombre'], 'ISR') === 0) { $isr3 = $l['monto']; }
    }
    prueba('ISR base 10000 (tramo abierto) = 1420.00', 1420.0, $isr3);

    // --- Tope 25 UMA: salario diario 4000 => SBC 4180.8 > 3000 (25*120) => tope. ---
    // cuota sbc 2% * 3000 * 15 = 900; excedente (3000-360)=2640 * 1% * 15 = 396 => 1296.00
    $r4 = $fiscal->lineasFiscales(0.0, 4000.00, 15, 'quincenal', $resolucion['reglas']);
    $imss4 = null;
    foreach ($r4['lineas'] as $l) {
        if (strpos($l['concepto_nombre'], 'IMSS') === 0) { $imss4 = $l['monto']; }
    }
    prueba('IMSS con tope 25 UMA = 1296.00', 1296.0, $imss4);
} catch (Throwable $e) {
    echo "  FAIL  excepcion inesperada: " . $e->getMessage() . "\n";
    $fallos++;
} finally {
    $db->safeRollBack();
    echo "\n  INFO  rollback ejecutado: ningun dato persistio.\n";
}

echo "\n" . ($fallos === 0 ? "=== RESULTADO: TODAS LAS PRUEBAS PASARON ===\n" : "=== RESULTADO: {$fallos} FALLO(S) ===\n");
exit($fallos === 0 ? 0 : 1);
