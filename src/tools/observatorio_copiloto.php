<?php
/**
 * Observatorio del Copiloto — Medisoft Hoteles
 *
 * F1 del ciclo de mejora continua del Copiloto: SOLO LECTURA sobre
 * copiloto_mensajes (que ya registra cada pregunta con fuente
 * reglas|ia|fallback). Responde dos preguntas:
 *
 *   1. Salud global: ¿qué % de preguntas cae en cada fuente? (si fallback
 *      sube semana a semana, el Copiloto va perdiendo terreno)
 *   2. ¿Qué están preguntando los usuarios que el Copiloto responde con
 *      genérico (fallback)? Agrupado por FIRMA de tema para ver frecuencia.
 *
 * Estado persistente en observatorio_estado.json (viaja por git):
 *   - NUEVA:     tema nunca visto → candidato a enseñárselo al Copiloto.
 *   - REGRESIÓN: tema marcado "atendida" (ya se le enseñó) que volvió a
 *                caer en fallback DESPUÉS de esa fecha → se le enseñó mal.
 *   - conocida:  ya reportado, pendiente de enseñar (se lista compacto).
 *   - ignorada:  ruido aceptado (saludos, pruebas) — no se lista.
 *
 * Uso (en contenedor, como todas las tools):
 *   MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php /var/www/html/tools/observatorio_copiloto.php
 *   ... observatorio_copiloto.php --dias=90        # ventana (default 30)
 *   ... observatorio_copiloto.php --json           # contrato de automatización
 *   ... observatorio_copiloto.php atendida <firma> "qué se le enseñó"
 *   ... observatorio_copiloto.php ignorar  <firma> "por qué es ruido"
 *
 * Exit: 0 = sin temas nuevos ni regresiones · 1 = hay material para enseñar.
 *
 * Pipeline futuro (F4, autocorrección con Claude): cron corre `--json`; si
 * exit=1, cada tema nuevo/regresión se manda a Claude con sus ejemplos
 * textuales; Claude propone la enseñanza por el camino estándar (acción
 * nueva = parser en CopilotoService + campos del widget + whitelist, ver
 * CLAUDE.md) como PR; humano aprueba; al mergear se marca `atendida`.
 * Guardarraíl: la IA jamás modifica prompts/comportamiento en caliente.
 */

$src = dirname(__DIR__);
$rutaEstado = __DIR__ . '/observatorio_estado.json';

// ── Argumentos ────────────────────────────────────────────────────────────
$dias = 30;
$modoJson = false;
$accion = null;
$accionFirma = null;
$accionNota = '';
foreach (array_slice($argv, 1) as $i => $arg) {
    if (preg_match('/^--dias=(\d+)$/', $arg, $m)) {
        $dias = (int) $m[1];
    } elseif ($arg === '--json') {
        $modoJson = true;
    } elseif (in_array($arg, ['atendida', 'ignorar'], true)) {
        $accion = $arg;
        $accionFirma = $argv[$i + 2] ?? null;
        $accionNota = $argv[$i + 3] ?? '';
    }
}

$estado = is_readable($rutaEstado)
    ? (json_decode(file_get_contents($rutaEstado), true) ?: [])
    : [];

if ($accion !== null) {
    if (!$accionFirma) {
        fwrite(STDERR, "Falta la firma: observatorio_copiloto.php $accion <firma> [nota]\n");
        exit(2);
    }
    // Fecha con el reloj de la BD, no el de PHP: created_at viene de MySQL y
    // los relojes difieren (gotcha timezone del CEMENTERIO). Mezclarlos
    // abriría una ventana de falsas regresiones.
    $cfg = require $src . '/config/database.php';
    $fechaBd = (new PDO(
        "mysql:host={$cfg['host']};dbname={$cfg['database']};charset={$cfg['charset']}",
        $cfg['username'],
        $cfg['password']
    ))->query('SELECT NOW()')->fetchColumn();
    $estado[$accionFirma] = [
        'estado' => $accion === 'atendida' ? 'atendida' : 'ignorada',
        'nota' => $accionNota,
        'fecha' => $fechaBd,
    ];
    file_put_contents($rutaEstado, json_encode($estado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "Tema $accionFirma marcado como {$estado[$accionFirma]['estado']}.\n";
    exit(0);
}

// ── Conexión (solo SELECT) ────────────────────────────────────────────────
$config = require $src . '/config/database.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ── Salud global por fuente (ventana y últimos 7 días para tendencia) ────
$salud = ['ventana' => [], 'ultimos_7' => []];
foreach ([['ventana', $dias], ['ultimos_7', 7]] as [$clave, $d]) {
    $st = $pdo->prepare(
        'SELECT fuente, COUNT(*) n FROM copiloto_mensajes
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY fuente'
    );
    $st->execute([$d]);
    $tot = 0;
    $porFuente = ['reglas' => 0, 'ia' => 0, 'fallback' => 0];
    foreach ($st as $r) {
        $porFuente[$r['fuente']] = (int) $r['n'];
        $tot += (int) $r['n'];
    }
    $salud[$clave] = ['total' => $tot] + $porFuente;
    $salud[$clave]['fallback_pct'] = $tot ? round($porFuente['fallback'] * 100 / $tot, 1) : 0.0;
}

// ── Temas: agrupar preguntas en fallback por firma de tokens ─────────────
// Firma = tokens significativos (sin acentos, números→N, sin muletillas),
// únicos y ordenados: "¿cuánto hay en caja?" y "en caja cuanto hay" caen
// en el MISMO tema.
$stopwords = array_flip([
    'de', 'la', 'el', 'los', 'las', 'un', 'una', 'unos', 'unas', 'en', 'y',
    'o', 'u', 'a', 'al', 'del', 'que', 'se', 'me', 'mi', 'tu', 'su', 'por',
    'para', 'con', 'sin', 'es', 'esta', 'estan', 'hay', 'ya', 'lo', 'le',
    'les', 'hola', 'buenas', 'buenos', 'dias', 'tardes', 'noches', 'porfa',
    'favor', 'quiero', 'quisiera', 'puedes', 'podrias', 'dame', 'dime',
    'ayuda', 'ayudame', 'como', 'oye',
]);
$firmaDe = function (string $pregunta) use ($stopwords): array {
    $t = mb_strtolower(trim($pregunta));
    $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $t = preg_replace('/\d+/', 'N', $t);
    $t = preg_replace('/[^a-zN ]+/', ' ', $t);
    $tokens = array_filter(explode(' ', $t), fn ($w) => $w !== '' && !isset($stopwords[$w]));
    $tokens = array_unique($tokens);
    sort($tokens);
    if (!$tokens) {
        $tokens = ['(charla)']; // saludos y frases sin contenido operativo
    }
    return [substr(md5(implode(' ', $tokens)), 0, 10), implode(' ', $tokens)];
};

$st = $pdo->prepare(
    "SELECT pregunta, hotel_id, usuario_id, created_at FROM copiloto_mensajes
     WHERE fuente = 'fallback' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     ORDER BY created_at"
);
$st->execute([$dias]);

$temas = [];
foreach ($st as $r) {
    [$firma, $tema] = $firmaDe($r['pregunta']);
    if (!isset($temas[$firma])) {
        $temas[$firma] = [
            'firma' => $firma,
            'tema' => $tema,
            'conteo' => 0,
            'ejemplos' => [],
            'primera_vez' => $r['created_at'],
            'ultima_vez' => $r['created_at'],
            'usuarios' => [],
            'hoteles' => [],
        ];
    }
    $t = &$temas[$firma];
    $t['conteo']++;
    $t['ultima_vez'] = $r['created_at'];
    if (count($t['ejemplos']) < 3 && !in_array($r['pregunta'], $t['ejemplos'], true)) {
        $t['ejemplos'][] = $r['pregunta'];
    }
    if ($r['usuario_id'] !== null) { $t['usuarios'][$r['usuario_id']] = true; }
    $t['hoteles'][$r['hotel_id']] = true;
    unset($t);
}

// ── Clasificar contra el estado ───────────────────────────────────────────
$nuevos = $regresiones = $conocidos = [];
foreach ($temas as $firma => $t) {
    $t['usuarios'] = count($t['usuarios']);
    $t['hoteles'] = array_keys($t['hoteles']);
    $reg = $estado[$firma] ?? null;
    if ($reg === null) {
        $t['clasificacion'] = 'NUEVA';
        $nuevos[] = $t;
    } elseif ($reg['estado'] === 'ignorada') {
        continue;
    } elseif ($reg['estado'] === 'atendida' && $t['ultima_vez'] > $reg['fecha']) {
        $t['clasificacion'] = 'REGRESION';
        $t['atendida_en'] = $reg['fecha'];
        $t['nota_ensenanza'] = $reg['nota'] ?? '';
        $regresiones[] = $t;
    } elseif ($reg['estado'] === 'atendida') {
        continue;
    } else {
        $t['clasificacion'] = 'conocida';
        $conocidos[] = $t;
    }
}
$ordenar = fn ($a, $b) => $b['conteo'] <=> $a['conteo'];
usort($nuevos, $ordenar);
usort($regresiones, $ordenar);
usort($conocidos, $ordenar);

foreach ($temas as $firma => $t) {
    if (!isset($estado[$firma])) {
        $estado[$firma] = ['estado' => 'conocida', 'nota' => '', 'fecha' => date('Y-m-d H:i:s')];
    }
}
file_put_contents($rutaEstado, json_encode($estado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$hayMaterial = $nuevos || $regresiones;

// ── Salida ────────────────────────────────────────────────────────────────
if ($modoJson) {
    echo json_encode([
        'generado' => date('Y-m-d H:i:s'),
        'ventana_dias' => $dias,
        'salud' => $salud,
        'material_para_ensenar' => $hayMaterial,
        'regresiones' => $regresiones,
        'nuevos' => $nuevos,
        'conocidos' => $conocidos,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit($hayMaterial ? 1 : 0);
}

$v = $salud['ventana'];
$s7 = $salud['ultimos_7'];
echo '== OBSERVATORIO DEL COPILOTO ' . date('Y-m-d H:i') . " (últimos $dias días) ==\n";
echo "Salud: {$v['total']} preguntas · reglas {$v['reglas']} · ia {$v['ia']} · fallback {$v['fallback']} ({$v['fallback_pct']}%)\n";
echo "Últimos 7 días: {$s7['total']} preguntas · fallback {$s7['fallback_pct']}%"
    . ($s7['fallback_pct'] > $v['fallback_pct'] ? ' ⚠ subiendo' : '') . "\n";

$pintar = function (array $t) {
    echo "[{$t['clasificacion']}] firma {$t['firma']} · {$t['conteo']}x · {$t['usuarios']} usuario(s)\n";
    echo "  tema: {$t['tema']}\n";
    foreach ($t['ejemplos'] as $ej) {
        echo "  «{$ej}»\n";
    }
    echo "  {$t['primera_vez']} → {$t['ultima_vez']}\n";
    if (!empty($t['atendida_en'])) {
        echo "  se le enseñó el {$t['atendida_en']} ({$t['nota_ensenanza']}) y SIGUE cayendo en fallback\n";
    }
};

if ($regresiones) {
    echo "\n-- REGRESIONES (se le enseñó y sigue fallando) --\n";
    array_map($pintar, $regresiones);
}
if ($nuevos) {
    echo "\n-- TEMAS NUEVOS sin respuesta digna --\n";
    array_map($pintar, $nuevos);
}
if ($conocidos) {
    echo "\n-- Conocidos pendientes de enseñar (compacto) --\n";
    foreach ($conocidos as $t) {
        echo "  {$t['firma']} · {$t['conteo']}x · «" . mb_substr($t['ejemplos'][0] ?? '', 0, 70) . "»\n";
    }
}
if (!$temas) {
    echo "\nSin preguntas en fallback en la ventana. El Copiloto está respondiendo todo.\n";
} elseif (!$hayMaterial) {
    echo "\nSin temas nuevos ni regresiones.\n";
}
echo "\nEnseñado al Copiloto: php tools/observatorio_copiloto.php atendida <firma> \"nota\" · ruido: ignorar <firma>\n";
exit($hayMaterial ? 1 : 0);
