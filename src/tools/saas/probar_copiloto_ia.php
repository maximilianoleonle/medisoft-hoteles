<?php
/**
 * Prueba del bloque copiloto_ia (CLI, solo local).
 *
 * - Aplica la migracion 20260708_001_copiloto_ia.sql si hace falta (persiste).
 * - Prueba el gating dentro de una TRANSACCION que se revierte al final:
 *   sin API key, referencias invalidas, prueba gratis, cache y regeneracion.
 * - Si hay ANTHROPIC_API_KEY y datos, hace UNA generacion real por funcion
 *   disponible (costo: centavos) y muestra el texto.
 *
 * Uso: php src/tools/saas/probar_copiloto_ia.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

// Cargar .env de la raiz del repo (mismo patron que cron_resumen_ia).
$envPath = dirname(__DIR__, 3) . '/.env';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
        $linea = trim($linea);
        if ($linea === '' || strpos($linea, '#') === 0 || strpos($linea, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $linea, 2);
        if (trim($k) !== '' && getenv(trim($k)) === false) {
            putenv(trim($k) . '=' . trim($v));
        }
    }
}

if (getenv('APP_ENV') !== 'local') {
    echo "[ERROR] APP_ENV debe ser local para esta prueba.\n";
    exit(1);
}

require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/app/services/CopilotoIaService.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$fallas = 0;
$ok = static function (bool $cond, string $msg) use (&$fallas) {
    echo ($cond ? '[OK]   ' : '[FALLA]') . ' ' . $msg . "\n";
    if (!$cond) {
        $fallas++;
    }
};
$info = static function (string $msg) {
    echo '[INFO] ' . $msg . "\n";
};

// ── 1) Migracion (persistente, idempotente) ─────────────────────────────
$tiene = static function (string $sql, array $p = []) use ($pdo) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($p);
    return (int) $stmt->fetchColumn() > 0;
};

$tablaExiste = $tiene(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'copiloto_ia_generaciones'"
);
$moduloExiste = $tiene("SELECT COUNT(*) FROM modulos WHERE clave = 'copiloto_ia'");
$planIncluido = $tiene(
    "SELECT COUNT(*) FROM plan_modulos pm
     INNER JOIN modulos m ON m.id = pm.modulo_id
     INNER JOIN planes p ON p.id = pm.plan_id
     WHERE m.clave = 'copiloto_ia' AND p.clave = 'premium' AND pm.incluido = 1"
);

if (!$tablaExiste || !$moduloExiste || !$planIncluido) {
    $info('Aplicando migracion 20260708_001_copiloto_ia.sql (idempotente)...');
    $sqlCompleto = (string) file_get_contents(dirname(__DIR__, 3) . '/migrations/20260708_001_copiloto_ia.sql');
    // Sin procedimientos ni triggers: separar por ';' al final de linea es seguro
    // aqui. Las lineas de comentario se quitan DENTRO de cada fragmento (un
    // fragmento puede empezar con un comentario y traer una sentencia abajo).
    foreach (preg_split('/;\s*\n/', $sqlCompleto) as $fragmento) {
        $lineas = array_filter(explode("\n", $fragmento), static function ($l) {
            return strpos(ltrim($l), '--') !== 0;
        });
        $sentencia = trim(implode("\n", $lineas));
        if ($sentencia === '') {
            continue;
        }
        $pdo->exec($sentencia);
    }
}

$ok($tiene("SELECT COUNT(*) FROM modulos WHERE clave = 'copiloto_ia' AND precio_mensual > 0"), 'Bloque copiloto_ia registrado en el catalogo con precio');
$ok($tiene("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'copiloto_ia_generaciones'"), 'Tabla copiloto_ia_generaciones existe');
$ok($tiene("SELECT COUNT(*) FROM plan_modulos pm INNER JOIN modulos m ON m.id = pm.modulo_id INNER JOIN planes p ON p.id = pm.plan_id WHERE m.clave = 'copiloto_ia' AND p.clave = 'premium' AND pm.incluido = 1"), 'Plan premium incluye el bloque');

// ── 2) Hotel de prueba ──────────────────────────────────────────────────
$stmt = $pdo->query('SELECT id, nombre FROM hoteles WHERE activo = 1 ORDER BY id LIMIT 1');
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$hotel) {
    echo "[ERROR] No hay hoteles activos en la BD local.\n";
    exit(1);
}
$hotelId = (int) $hotel['id'];
$info('Hotel de prueba: #' . $hotelId . ' ' . $hotel['nombre']);
$info('En CLI hotel_has_module no existe: el hotel cuenta como SIN bloque (ruta de prueba gratis).');

$apiKey = trim((string) (getenv('ANTHROPIC_API_KEY') ?: ''));
$info('ANTHROPIC_API_KEY: ' . ($apiKey !== '' ? 'presente' : 'AUSENTE (se omiten generaciones reales)'));

// ── 3) Gating (transaccion con rollback) ────────────────────────────────
$pdo->beginTransaction();

try {
    $servicio = new CopilotoIaService($db);

    // 3a. Sin API key configurada -> mensaje claro, sin tocar el API.
    putenv('ANTHROPIC_API_KEY');
    $r = $servicio->consejoTarifa($hotelId);
    $ok(!$r['success'] && strpos((string) $r['message'], 'ANTHROPIC_API_KEY') !== false, 'Sin API key: mensaje de no configurado (' . $r['message'] . ')');
    if ($apiKey !== '') {
        putenv('ANTHROPIC_API_KEY=' . $apiKey);
    }

    // 3b. Referencias invalidas: fallan ANTES de consumir prueba o API.
    $r = $servicio->borradorResena($hotelId, 99999999);
    $ok(!$r['success'] && empty($r['upsell']), 'Resena inexistente: falla limpia (' . $r['message'] . ')');

    $r = $servicio->analisisEncuestas($hotelId, '2031-01');
    $ok(!$r['success'] && strpos((string) $r['message'], 'aun no ocurre') !== false, 'Mes futuro: rechazado');

    $r = $servicio->analisisEncuestas($hotelId, '2020-01');
    $ok(!$r['success'], 'Mes sin encuestas: falla limpia sin consumir prueba (' . $r['message'] . ')');

    $sinConsumo = $tiene('SELECT COUNT(*) FROM copiloto_ia_generaciones WHERE hotel_id = ?', [$hotelId]);
    $ok(!$sinConsumo, 'Ningun intento fallido consumio prueba gratis (0 filas)');

    // 3c. Upsell: simular 3 pruebas gastadas de tarifa.
    $ins = $pdo->prepare(
        "INSERT INTO copiloto_ia_generaciones (hotel_id, tipo, ref_clave, contenido, veces, en_prueba)
         VALUES (?, 'tarifa', ?, 'x', 1, 1)"
    );
    foreach (['2026-01-01', '2026-01-02', '2026-01-03'] as $f) {
        $ins->execute([$hotelId, $f]);
    }
    $r = $servicio->consejoTarifa($hotelId);
    $ok(!$r['success'] && !empty($r['upsell']) && strpos((string) $r['message'], '$') !== false,
        'Pruebas agotadas: mensaje de contratacion con precio del catalogo');
    $info('Upsell: ' . $r['message']);

    // 3d. Cache: una fila previa se sirve sin API (aunque no haya pruebas restantes).
    $ins2 = $pdo->prepare(
        "INSERT INTO copiloto_ia_generaciones (hotel_id, tipo, ref_clave, contenido, veces, en_prueba)
         VALUES (?, 'tarifa', ?, 'CONSEJO CACHEADO DE PRUEBA', 1, 1)"
    );
    $ins2->execute([$hotelId, date('Y-m-d')]);
    $r = $servicio->consejoTarifa($hotelId);
    $ok($r['success'] && !empty($r['desde_cache']) && $r['texto'] === 'CONSEJO CACHEADO DE PRUEBA', 'Cache: se sirve sin pagar API');
    $ok(is_array($r['prueba']) && $r['prueba']['restantes'] === 0, 'Cache: reporta 0 pruebas restantes');

    // 3e. Tope de regeneraciones por elemento.
    $pdo->prepare("UPDATE copiloto_ia_generaciones SET veces = 99 WHERE hotel_id = ? AND tipo = 'tarifa' AND ref_clave = ?")
        ->execute([$hotelId, date('Y-m-d')]);
    $r = $servicio->consejoTarifa($hotelId, true);
    $ok(!$r['success'] && strpos((string) $r['message'], 'regeneraste') !== false, 'Regeneracion: tope por elemento respetado');
} finally {
    $pdo->rollBack();
}
$ok(!$tiene('SELECT COUNT(*) FROM copiloto_ia_generaciones WHERE hotel_id = ?', [$hotelId]), 'Rollback: la tabla quedo limpia');

// ── 4) Generaciones reales (si hay key; costo de centavos) ─────────────
if ($apiKey !== '') {
    $pdo->beginTransaction();
    try {
        $servicio = new CopilotoIaService($db);

        // Consejo de tarifa (solo requiere habitaciones/reservaciones).
        $r = $servicio->consejoTarifa($hotelId);
        $ok(!empty($r['success']), 'REAL consejo de tarifa: ' . (!empty($r['success']) ? 'generado (' . mb_strlen($r['texto']) . ' chars)' : ($r['message'] ?? '?')));
        if (!empty($r['success'])) {
            echo "\n──── CONSEJO DE TARIFA (real) ────\n" . $r['texto'] . "\n──────────────────────────────────\n\n";
            $r2 = $servicio->consejoTarifa($hotelId);
            $ok(!empty($r2['desde_cache']), 'REAL consejo: segunda llamada salio del cache');
        }

        // Borrador de resena: usa la encuesta respondida mas reciente, si hay.
        $stmt = $pdo->prepare(
            "SELECT id FROM reputacion_encuestas
             WHERE hotel_id = ? AND estado = 'respondida'
               AND (comentario IS NOT NULL AND comentario <> '' OR calificacion IS NOT NULL)
             ORDER BY respondida_at DESC LIMIT 1"
        );
        $stmt->execute([$hotelId]);
        $encuestaId = (int) $stmt->fetchColumn();
        if ($encuestaId > 0) {
            $r = $servicio->borradorResena($hotelId, $encuestaId);
            $ok(!empty($r['success']), 'REAL borrador de resena (encuesta #' . $encuestaId . '): ' . (!empty($r['success']) ? 'generado' : ($r['message'] ?? '?')));
            if (!empty($r['success'])) {
                echo "\n──── BORRADOR DE RESPUESTA (real) ────\n" . $r['texto'] . "\n──────────────────────────────────────\n\n";
            }
        } else {
            $info('Sin encuestas respondidas: se omite la prueba real de resena.');
        }

        // Analisis del mes actual, si hay encuestas respondidas en el mes.
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM reputacion_encuestas
             WHERE hotel_id = ? AND estado = 'respondida' AND respondida_at >= ?"
        );
        $stmt->execute([$hotelId, date('Y-m-01 00:00:00')]);
        if ((int) $stmt->fetchColumn() > 0) {
            $r = $servicio->analisisEncuestas($hotelId, date('Y-m'));
            $ok(!empty($r['success']), 'REAL analisis de encuestas: ' . (!empty($r['success']) ? 'generado' : ($r['message'] ?? '?')));
            if (!empty($r['success'])) {
                echo "\n──── ANALISIS DEL MES (real) ────\n" . $r['texto'] . "\n─────────────────────────────────\n\n";
            }
        } else {
            $info('Sin encuestas respondidas este mes: se omite la prueba real de analisis.');
        }
    } finally {
        $pdo->rollBack();
    }
    $ok(!$tiene('SELECT COUNT(*) FROM copiloto_ia_generaciones WHERE hotel_id = ?', [$hotelId]), 'Rollback final: sin residuos en la tabla');
}

echo "\n" . ($fallas === 0 ? 'TODO OK' : $fallas . ' FALLA(S)') . "\n";
exit($fallas === 0 ? 0 : 1);
