<?php
/**
 * Herramienta local para asignar huespedes historicos sin hotel_id.
 *
 * Modo auditoria:
 *   php tools/saas/asignar_huespedes_sin_hotel.php
 *
 * Dry-run de asignacion:
 *   php tools/saas/asignar_huespedes_sin_hotel.php --hotel-slug=los-cedros --all
 *   php tools/saas/asignar_huespedes_sin_hotel.php --hotel-id=1 --from-id=21 --to-id=100
 *   php tools/saas/asignar_huespedes_sin_hotel.php --hotel-slug=los-cedros --ids=21,22,23
 *
 * Escritura explicita:
 *   php tools/saas/asignar_huespedes_sin_hotel.php --hotel-slug=los-cedros --all --execute --confirm=los-cedros
 *
 * Requisitos:
 * - Solo CLI.
 * - APP_ENV=local.
 * - Nunca reasigna huespedes que ya tienen hotel_id.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    echo "[ERROR] APP_ENV debe ser local. Valor actual: " . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv) . "\n";
    exit(1);
}

$opciones = getopt('', [
    'hotel-id::',
    'hotel-slug::',
    'ids::',
    'from-id::',
    'to-id::',
    'limit::',
    'all',
    'execute',
    'confirm::',
    'allow-phone-duplicates',
    'help',
]);

if (array_key_exists('help', $opciones)) {
    mostrarUso();
    exit(0);
}

$execute = array_key_exists('execute', $opciones);
$all = array_key_exists('all', $opciones);
$confirm = trim((string)($opciones['confirm'] ?? ''));
$allowPhoneDuplicates = array_key_exists('allow-phone-duplicates', $opciones);
$limitPreview = max(1, min(200, (int)($opciones['limit'] ?? 20)));

$configPath = dirname(__DIR__, 2) . '/config/database.php';
if (!is_file($configPath)) {
    echo "[ERROR] No se encontro configuracion de base de datos: {$configPath}\n";
    exit(1);
}

$dbConfig = require $configPath;

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['database'],
        $dbConfig['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $hotel = resolverHotel($pdo, $opciones);
    $scope = construirScope($opciones);

    imprimirAuditoriaBase($pdo);

    if (!$hotel) {
        echo "\n[INFO] No se indico hotel destino. Use --hotel-id=ID o --hotel-slug=slug para preparar asignacion.\n";
        imprimirHoteles($pdo);
        exit(0);
    }

    if (!$scope['has_scope']) {
        echo "\n[ERROR] Indique el alcance: --all, --ids=1,2,3 o --from-id=N --to-id=M.\n";
        echo "        Esto evita asignaciones masivas accidentales.\n";
        exit(1);
    }

    echo "\nHotel destino: {$hotel['nombre']} (ID {$hotel['id']}, slug {$hotel['slug']})\n";
    echo "Alcance: {$scope['label']}\n";

    $seleccion = contarSeleccion($pdo, $scope);
    echo "Huespedes seleccionados sin hotel_id: {$seleccion}\n";

    if ($seleccion <= 0) {
        echo "[OK] No hay registros por asignar con ese alcance.\n";
        exit(0);
    }

    imprimirMuestra($pdo, $scope, $limitPreview);

    $reservacionesConflictivas = contarReservacionesConflictivas($pdo, $scope, (int)$hotel['id']);
    if ($reservacionesConflictivas > 0) {
        echo "[ERROR] Hay {$reservacionesConflictivas} huesped(es) seleccionados con reservaciones de otro hotel.\n";
        echo "        No se permite asignarlos automaticamente.\n";
        exit(1);
    }

    $duplicadosTelefono = contarTelefonosDuplicados($pdo, $scope, (int)$hotel['id']);
    if ($duplicadosTelefono > 0) {
        echo "[WARN] La asignacion crearia {$duplicadosTelefono} telefono(s) duplicados dentro del hotel destino.\n";
        imprimirTelefonosDuplicados($pdo, $scope, (int)$hotel['id']);

        if (!$allowPhoneDuplicates) {
            echo "[ERROR] Escritura bloqueada. Revise duplicados o use --allow-phone-duplicates si decide aceptarlos.\n";
            exit(1);
        }
    }

    if (!$execute) {
        echo "\n[DRY-RUN] No se modifico la base de datos.\n";
        echo "Para ejecutar: agregue --execute --confirm={$hotel['slug']}\n";
        exit(0);
    }

    if ($confirm !== $hotel['slug']) {
        echo "[ERROR] Para escribir cambios use --confirm={$hotel['slug']}\n";
        exit(1);
    }

    $pdo->beginTransaction();

    $sql = "UPDATE huespedes
            SET hotel_id = :hotel_id, updated_at = NOW()
            WHERE hotel_id IS NULL
              AND {$scope['where']}";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('hotel_id', (int)$hotel['id'], PDO::PARAM_INT);
    enlazarParametros($stmt, $scope['params']);
    $stmt->execute();
    $afectados = $stmt->rowCount();

    $pdo->commit();

    echo "\n[OK] Huespedes asignados: {$afectados}\n";
    echo "Hotel destino: {$hotel['slug']} (ID {$hotel['id']})\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

function mostrarUso(): void
{
    echo "Uso:\n";
    echo "  php tools/saas/asignar_huespedes_sin_hotel.php\n";
    echo "  php tools/saas/asignar_huespedes_sin_hotel.php --hotel-slug=los-cedros --all\n";
    echo "  php tools/saas/asignar_huespedes_sin_hotel.php --hotel-id=1 --ids=21,22,23\n";
    echo "  php tools/saas/asignar_huespedes_sin_hotel.php --hotel-id=1 --from-id=21 --to-id=100\n";
    echo "  php tools/saas/asignar_huespedes_sin_hotel.php --hotel-slug=los-cedros --all --execute --confirm=los-cedros\n";
}

function resolverHotel(PDO $pdo, array $opciones): ?array
{
    $hotelId = (int)($opciones['hotel-id'] ?? 0);
    $hotelSlug = trim((string)($opciones['hotel-slug'] ?? ''));

    if ($hotelId <= 0 && $hotelSlug === '') {
        return null;
    }

    if ($hotelId > 0) {
        $stmt = $pdo->prepare('SELECT id, nombre, slug, activo FROM hoteles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $hotelId]);
    } else {
        $stmt = $pdo->prepare('SELECT id, nombre, slug, activo FROM hoteles WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $hotelSlug]);
    }

    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$hotel) {
        throw new RuntimeException('Hotel destino no encontrado.');
    }

    if ((int)($hotel['activo'] ?? 0) !== 1) {
        throw new RuntimeException('Hotel destino inactivo.');
    }

    return $hotel;
}

function construirScope(array $opciones): array
{
    $ids = parsearIds((string)($opciones['ids'] ?? ''));
    $fromId = (int)($opciones['from-id'] ?? 0);
    $toId = (int)($opciones['to-id'] ?? 0);
    $all = array_key_exists('all', $opciones);

    if (!empty($ids)) {
        $params = [];
        $placeholders = [];
        foreach ($ids as $index => $id) {
            $key = 'id_' . $index;
            $params[$key] = $id;
            $placeholders[] = ':' . $key;
        }

        return [
            'has_scope' => true,
            'where' => 'id IN (' . implode(', ', $placeholders) . ')',
            'whereForAlias' => 'h.id IN (' . implode(', ', $placeholders) . ')',
            'whereForSelectedAlias' => 's.id IN (' . implode(', ', $placeholders) . ')',
            'params' => $params,
            'label' => 'IDs: ' . implode(',', $ids),
        ];
    }

    if ($fromId > 0 || $toId > 0) {
        if ($fromId <= 0 || $toId <= 0 || $fromId > $toId) {
            throw new RuntimeException('Rango invalido. Use --from-id=N --to-id=M con N <= M.');
        }

        return [
            'has_scope' => true,
            'where' => 'id BETWEEN :from_id AND :to_id',
            'whereForAlias' => 'h.id BETWEEN :from_id AND :to_id',
            'whereForSelectedAlias' => 's.id BETWEEN :from_id AND :to_id',
            'params' => ['from_id' => $fromId, 'to_id' => $toId],
            'label' => "Rango ID {$fromId}-{$toId}",
        ];
    }

    if ($all) {
        return [
            'has_scope' => true,
            'where' => '1 = 1',
            'whereForAlias' => '1 = 1',
            'whereForSelectedAlias' => '1 = 1',
            'params' => [],
            'label' => 'Todos los huespedes con hotel_id NULL',
        ];
    }

    return [
        'has_scope' => false,
        'where' => '1 = 0',
        'whereForAlias' => '1 = 0',
        'whereForSelectedAlias' => '1 = 0',
        'params' => [],
        'label' => 'Sin alcance',
    ];
}

function parsearIds(string $raw): array
{
    if (trim($raw) === '') {
        return [];
    }

    $ids = [];
    foreach (explode(',', $raw) as $part) {
        $id = (int)trim($part);
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function imprimirAuditoriaBase(PDO $pdo): void
{
    $row = $pdo->query(
        'SELECT COUNT(*) AS total,
                MIN(id) AS min_id,
                MAX(id) AS max_id,
                MIN(created_at) AS min_created,
                MAX(created_at) AS max_created
         FROM huespedes
         WHERE hotel_id IS NULL'
    )->fetch(PDO::FETCH_ASSOC) ?: [];

    echo "Huespedes con hotel_id NULL: " . (int)($row['total'] ?? 0) . "\n";
    echo "Rango ID: " . (($row['min_id'] ?? '') ?: '-') . " a " . (($row['max_id'] ?? '') ?: '-') . "\n";
    echo "Rango fechas: " . (($row['min_created'] ?? '') ?: '-') . " a " . (($row['max_created'] ?? '') ?: '-') . "\n";
}

function imprimirHoteles(PDO $pdo): void
{
    echo "\nHoteles activos:\n";
    $stmt = $pdo->query(
        'SELECT id, nombre, slug
         FROM hoteles
         WHERE activo = 1
         ORDER BY id'
    );

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $hotel) {
        echo "- ID {$hotel['id']} | {$hotel['slug']} | {$hotel['nombre']}\n";
    }
}

function contarSeleccion(PDO $pdo, array $scope): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM huespedes
         WHERE hotel_id IS NULL
           AND {$scope['where']}"
    );
    enlazarParametros($stmt, $scope['params']);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

function imprimirMuestra(PDO $pdo, array $scope, int $limit): void
{
    $sql = "SELECT id, nombre_completo, telefono, procedencia_estado, created_at
            FROM huespedes
            WHERE hotel_id IS NULL
              AND {$scope['where']}
            ORDER BY id
            LIMIT {$limit}";
    $stmt = $pdo->prepare($sql);
    enlazarParametros($stmt, $scope['params']);
    $stmt->execute();

    echo "\nMuestra:\n";
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- #{$row['id']} | {$row['nombre_completo']} | {$row['telefono']} | {$row['procedencia_estado']} | {$row['created_at']}\n";
    }
}

function contarReservacionesConflictivas(PDO $pdo, array $scope, int $hotelId): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT h.id)
         FROM huespedes h
         INNER JOIN reservaciones r ON r.huesped_id = h.id
         WHERE h.hotel_id IS NULL
           AND {$scope['whereForAlias']}
           AND r.hotel_id <> :hotel_id"
    );
    $params = aliasParams($scope['params']);
    $params['hotel_id'] = $hotelId;
    enlazarParametros($stmt, $params);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

function contarTelefonosDuplicados(PDO $pdo, array $scope, int $hotelId): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM (
             SELECT s.telefono
             FROM huespedes s
             LEFT JOIN huespedes e
                ON e.hotel_id = :hotel_id
               AND e.telefono = s.telefono
             WHERE s.hotel_id IS NULL
               AND {$scope['whereForSelectedAlias']}
               AND s.telefono IS NOT NULL
               AND s.telefono <> ''
             GROUP BY s.telefono
             HAVING COUNT(DISTINCT s.id) + COUNT(DISTINCT e.id) > 1
         ) duplicados"
    );
    $params = selectedAliasParams($scope['params']);
    $params['hotel_id'] = $hotelId;
    enlazarParametros($stmt, $params);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

function imprimirTelefonosDuplicados(PDO $pdo, array $scope, int $hotelId): void
{
    $stmt = $pdo->prepare(
        "SELECT s.telefono,
                COUNT(DISTINCT s.id) AS seleccionados,
                COUNT(DISTINCT e.id) AS existentes
         FROM huespedes s
         LEFT JOIN huespedes e
            ON e.hotel_id = :hotel_id
           AND e.telefono = s.telefono
         WHERE s.hotel_id IS NULL
           AND {$scope['whereForSelectedAlias']}
           AND s.telefono IS NOT NULL
           AND s.telefono <> ''
         GROUP BY s.telefono
         HAVING COUNT(DISTINCT s.id) + COUNT(DISTINCT e.id) > 1
         ORDER BY seleccionados DESC, existentes DESC, s.telefono
         LIMIT 20"
    );
    $params = selectedAliasParams($scope['params']);
    $params['hotel_id'] = $hotelId;
    enlazarParametros($stmt, $params);
    $stmt->execute();

    echo "Telefonos duplicados (max 20):\n";
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- {$row['telefono']} | seleccionados {$row['seleccionados']} | existentes {$row['existentes']}\n";
    }
}

function enlazarParametros(PDOStatement $stmt, array $params): void
{
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . ltrim((string)$key, ':'), $value, PDO::PARAM_INT);
    }
}

function aliasParams(array $params): array
{
    $aliased = [];
    foreach ($params as $key => $value) {
        $aliased[$key] = $value;
    }
    return $aliased;
}

function selectedAliasParams(array $params): array
{
    $aliased = [];
    foreach ($params as $key => $value) {
        $aliased[$key] = $value;
    }
    return $aliased;
}
