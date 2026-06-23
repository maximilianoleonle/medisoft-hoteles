<?php
/**
 * Suite Fase 5E-U-A - QA de nomina administrativa.
 *
 * Ejecuta validaciones locales de solo lectura sobre el bloque administrativo
 * de Personal/Nomina. No crea rutas, migraciones, datos, pagos ni movimientos
 * de Caja.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

$ok = 0;
$warnings = 0;
$errors = 0;
$recommendations = [];

function qaNomLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function qaNomOk(string $message): void
{
    global $ok;
    $ok++;
    qaNomLine('OK', $message);
}

function qaNomWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    qaNomLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function qaNomError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    qaNomLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function qaNomParseCounts(string $output): array
{
    $counts = [
        'ok' => null,
        'warnings' => null,
        'errors' => null,
        'result' => null,
    ];

    if (preg_match('/^OK:\s*(\d+)/m', $output, $match) === 1) {
        $counts['ok'] = (int)$match[1];
    }

    if (preg_match('/^WARNING:\s*(\d+)/m', $output, $match) === 1) {
        $counts['warnings'] = (int)$match[1];
    }

    if (preg_match('/^ERROR:\s*(\d+)/m', $output, $match) === 1) {
        $counts['errors'] = (int)$match[1];
    }

    if (preg_match('/^Resultado general:\s*(.+)$/m', $output, $match) === 1) {
        $counts['result'] = trim((string)$match[1]);
    }

    return $counts;
}

function qaNomTail(string $text, int $maxLines = 12): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $lines = preg_split('/\R/', $text) ?: [];
    $tail = array_slice($lines, -$maxLines);

    return implode("\n", $tail);
}

function qaNomRunPhpTool(string $path): array
{
    if (!is_file($path)) {
        return [
            'exit_code' => 127,
            'stdout' => '',
            'stderr' => 'Archivo no encontrado: ' . $path,
            'counts' => qaNomParseCounts(''),
        ];
    }

    if (!function_exists('proc_open')) {
        return [
            'exit_code' => 126,
            'stdout' => '',
            'stderr' => 'proc_open no esta disponible en este runtime PHP.',
            'counts' => qaNomParseCounts(''),
        ];
    }

    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $env = [];
    foreach (array_merge($_SERVER, $_ENV) as $key => $value) {
        if (is_scalar($value)) {
            $env[(string)$key] = (string)$value;
        }
    }
    $env['APP_ENV'] = 'local';
    $process = proc_open([PHP_BINARY, $path], $descriptorSpec, $pipes, dirname(__DIR__, 2), $env);
    if (!is_resource($process)) {
        return [
            'exit_code' => 126,
            'stdout' => '',
            'stderr' => 'No se pudo iniciar el proceso PHP para ' . basename($path) . '.',
            'counts' => qaNomParseCounts(''),
        ];
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    $stdout = is_string($stdout) ? $stdout : '';
    $stderr = is_string($stderr) ? $stderr : '';

    return [
        'exit_code' => is_int($exitCode) ? $exitCode : 1,
        'stdout' => $stdout,
        'stderr' => $stderr,
        'counts' => qaNomParseCounts($stdout),
    ];
}

function qaNomDescribeCounts(array $counts): string
{
    $ok = $counts['ok'];
    $warnings = $counts['warnings'];
    $errors = $counts['errors'];
    $result = $counts['result'];

    $parts = [];
    if ($ok !== null) {
        $parts[] = 'OK=' . (string)$ok;
    }
    if ($warnings !== null) {
        $parts[] = 'WARNING=' . (string)$warnings;
    }
    if ($errors !== null) {
        $parts[] = 'ERROR=' . (string)$errors;
    }
    if ($result !== null && $result !== '') {
        $parts[] = 'RESULTADO=' . $result;
    }

    return $parts === [] ? 'sin resumen parseable' : implode(', ', $parts);
}

function qaNomValidateHealthIntegration(string $healthPath): void
{
    if (!is_file($healthPath)) {
        qaNomError(
            'No se encontro health_check_fase_1a.php para validar integracion 5E-T-B.',
            'Restaurar el health general o ajustar la ruta de la suite 5E-U-A.'
        );
        return;
    }

    $code = (string)file_get_contents($healthPath);
    $needles = [
        'preflight_frontera_nomina_oficial.php',
        'Preflight Personal 5E-T-A',
        'sync_temporarily_disabled',
        '/api/sync',
    ];

    $missing = [];
    foreach ($needles as $needle) {
        if (strpos($code, $needle) === false) {
            $missing[] = $needle;
        }
    }

    if ($missing === []) {
        qaNomOk('Health general conserva integracion estatica 5E-T-B y bloqueo /api/sync documentado.');
        return;
    }

    qaNomError(
        'Health general no conserva todas las marcas esperadas 5E-T-B: ' . implode(', ', $missing) . '.',
        'Revisar health_check_fase_1a.php antes de continuar con nuevas fases de nomina.'
    );
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    qaNomError(
        'APP_ENV debe ser local para ejecutar esta suite. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local.'
    );
}

$appRoot = dirname(__DIR__, 2);
$toolsRoot = __DIR__;

echo "Suite Fase 5E-U-A - QA nomina administrativa\n";
echo "=====================================================\n";

$targets = [
    [
        'label' => '5E-D/5E-S pagos laborales, snapshots, auditoria y expediente',
        'file' => $toolsRoot . '/preflight_personal_pagos_caja.php',
        'blocking' => true,
    ],
    [
        'label' => '5E-T-A frontera de nomina oficial',
        'file' => $toolsRoot . '/preflight_frontera_nomina_oficial.php',
        'blocking' => true,
    ],
    [
        'label' => 'NP-C ledger laboral historico',
        'file' => $toolsRoot . '/preflight_personal_ledger.php',
        'blocking' => false,
        'non_blocking_reason' => 'Preflight historico previo a rutas 5E; sus errores por rutas autorizadas no bloquean la suite administrativa actual.',
    ],
];

foreach ($targets as $target) {
    $label = (string)$target['label'];
    $path = (string)$target['file'];
    $blocking = (bool)$target['blocking'];

    qaNomLine('INFO', 'Ejecutando ' . $label . ' (' . basename($path) . ').');
    $result = qaNomRunPhpTool($path);
    $counts = is_array($result['counts']) ? $result['counts'] : qaNomParseCounts('');
    $childErrors = $counts['errors'];
    $hasErrors = (int)$result['exit_code'] !== 0 || ($childErrors !== null && (int)$childErrors > 0);
    $description = qaNomDescribeCounts($counts);

    if (!$hasErrors) {
        qaNomOk($label . ' paso. ' . $description . '.');
        continue;
    }

    $detail = trim((string)$result['stderr']);
    if ($detail === '') {
        $detail = qaNomTail((string)$result['stdout']);
    }

    if ($blocking) {
        qaNomError(
            $label . ' fallo. exit=' . (string)$result['exit_code'] . ', ' . $description . '.',
            'Revisar ' . basename($path) . ' antes de continuar.'
        );
        if ($detail !== '') {
            qaNomLine('DETAIL', $detail);
        }
        continue;
    }

    qaNomWarning(
        $label . ' reporto hallazgos no bloqueantes. exit=' . (string)$result['exit_code'] . ', ' . $description . '.',
        (string)($target['non_blocking_reason'] ?? 'Revisar manualmente si el hallazgo deja de ser historico.')
    );
}

qaNomValidateHealthIntegration($appRoot . '/tools/saas/health_check_fase_1a.php');

echo "=====================================================\n";
echo "Resumen suite\n";
echo "OK: {$ok}\n";
echo "WARNING: {$warnings}\n";
echo "ERROR: {$errors}\n";

if ($recommendations !== []) {
    echo "Recomendaciones concretas:\n";
    foreach (array_values(array_unique($recommendations)) as $recommendation) {
        echo '- ' . $recommendation . "\n";
    }
}

if ($errors > 0) {
    echo "Resultado general: FAIL\n";
    exit(1);
}

echo "Resultado general: PASS_WITH_WARNINGS_ALLOWED\n";
exit(0);
