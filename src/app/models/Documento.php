<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../services/AuditService.php';

class Documento extends Model
{
    protected $table = 'documentos';

    private const MAX_UPLOAD_BYTES = 10485760;

    private const MIME_PERMITIDOS = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    private const EXTENSIONES_PELIGROSAS = [
        'php',
        'phtml',
        'phar',
        'js',
        'html',
        'htm',
        'svg',
        'exe',
        'bat',
        'cmd',
        'sh',
        'ps1',
    ];

    private const ENTIDAD_TIPOS = [
        'proveedor',
        'compra',
        'cuenta_por_pagar',
        'huesped',
        'reservacion',
        'trabajador',
        'tarea',
    ];

    public function tablasDisponibles(): bool
    {
        foreach (['documento_tipos', 'documentos', 'documento_entidades'] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function tiposActivosPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaExiste('documento_tipos')) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    clave,
                    nombre,
                    descripcion,
                    mime_permitidos,
                    max_size_mb
             FROM documento_tipos
             WHERE activo = 1
               AND (hotel_id IS NULL OR hotel_id = ?)
             ORDER BY hotel_id IS NULL DESC, nombre ASC",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function resumenPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaExiste('documentos')) {
            return $this->resumenVacio();
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN estado = 'archivado' THEN 1 ELSE 0 END) AS archivados,
                    SUM(CASE WHEN estado = 'eliminado' THEN 1 ELSE 0 END) AS eliminados,
                    COALESCE(SUM(size_bytes), 0) AS bytes_total
             FROM documentos
             WHERE hotel_id = ?",
            [$hotelId]
        );

        $row = $stmt ? ($stmt->fetch() ?: []) : [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'activos' => (int)($row['activos'] ?? 0),
            'archivados' => (int)($row['archivados'] ?? 0),
            'eliminados' => (int)($row['eliminados'] ?? 0),
            'bytes_total' => (int)($row['bytes_total'] ?? 0),
        ];
    }

    public function listarPorHotel(int $hotelId, array $filtros = [], int $limite = 100): array
    {
        if ($hotelId <= 0 || !$this->tablasDisponibles()) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $where = ['d.hotel_id = ?'];
        $params = [$hotelId, $hotelId];

        $estado = $this->normalizarEstado($filtros['estado'] ?? 'todos');
        if ($estado !== 'todos') {
            $where[] = 'd.estado = ?';
            $params[] = $estado;
        }

        $tipoId = (int)($filtros['documento_tipo_id'] ?? 0);
        if ($tipoId > 0) {
            $where[] = 'd.documento_tipo_id = ?';
            $params[] = $tipoId;
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(d.titulo LIKE ? OR d.nombre_original LIKE ? OR d.descripcion LIKE ? OR d.mime_type LIKE ? OR dt.nombre LIKE ? OR dt.clave LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT d.id,
                    d.hotel_id,
                    d.documento_tipo_id,
                    d.nombre_original,
                    d.mime_type,
                    d.size_bytes,
                    d.titulo,
                    d.descripcion,
                    d.etiquetas,
                    d.estado,
                    d.subido_por_usuario_id,
                    d.created_at,
                    d.updated_at,
                    dt.clave AS tipo_clave,
                    dt.nombre AS tipo_nombre,
                    u.nombre_completo AS subido_por_nombre,
                    COALESCE(rel.entidades_count, 0) AS entidades_count,
                    rel.entidades_resumen
             FROM documentos d
             LEFT JOIN documento_tipos dt
                ON dt.id = d.documento_tipo_id
               AND (dt.hotel_id IS NULL OR dt.hotel_id = d.hotel_id)
             LEFT JOIN usuarios u
                ON u.id = d.subido_por_usuario_id
             LEFT JOIN (
                 SELECT hotel_id,
                        documento_id,
                        COUNT(*) AS entidades_count,
                        GROUP_CONCAT(CONCAT(entidad_tipo, '#', entidad_id) ORDER BY id SEPARATOR ', ') AS entidades_resumen
                 FROM documento_entidades
                 WHERE hotel_id = ?
                 GROUP BY hotel_id, documento_id
             ) rel
                ON rel.hotel_id = d.hotel_id
               AND rel.documento_id = d.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY d.created_at DESC, d.id DESC
             LIMIT {$limite}",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function buscarPorIdHotel(int $id, int $hotelId): ?array
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaExiste('documentos')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT d.id,
                    d.hotel_id,
                    d.documento_tipo_id,
                    d.nombre_original,
                    d.mime_type,
                    d.size_bytes,
                    d.titulo,
                    d.descripcion,
                    d.etiquetas,
                    d.estado,
                    d.subido_por_usuario_id,
                    d.created_at,
                    d.updated_at,
                    dt.clave AS tipo_clave,
                    dt.nombre AS tipo_nombre,
                    dt.descripcion AS tipo_descripcion,
                    u.nombre_completo AS subido_por_nombre
             FROM documentos d
             LEFT JOIN documento_tipos dt
                ON dt.id = d.documento_tipo_id
               AND (dt.hotel_id IS NULL OR dt.hotel_id = d.hotel_id)
             LEFT JOIN usuarios u
                ON u.id = d.subido_por_usuario_id
             WHERE d.id = ?
               AND d.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function buscarDescargablePorIdHotel(int $id, int $hotelId): ?array
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaExiste('documentos')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    nombre_original,
                    nombre_archivo,
                    storage_path,
                    mime_type,
                    size_bytes,
                    estado
             FROM documentos
             WHERE id = ?
               AND hotel_id = ?
               AND estado = 'activo'
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function resolverRutaPrivada(array $documento): string
    {
        if (!defined('STORAGE_PATH')) {
            throw new Exception('STORAGE_PATH no esta definido');
        }

        $storagePath = trim((string)($documento['storage_path'] ?? ''));
        if ($storagePath === '') {
            throw new Exception('Ruta privada no disponible');
        }

        $storagePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storagePath);
        $storagePath = ltrim($storagePath, DIRECTORY_SEPARATOR);
        $segments = array_filter(explode(DIRECTORY_SEPARATOR, $storagePath), 'strlen');
        if (in_array('..', $segments, true)) {
            throw new Exception('Ruta privada no valida');
        }

        $root = rtrim(STORAGE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'documentos';
        $rootReal = realpath($root);
        if (!$rootReal || !is_dir($rootReal)) {
            throw new Exception('Raiz de documentos privada no disponible');
        }

        $absolutePath = rtrim(STORAGE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storagePath;
        $real = realpath($absolutePath);
        if (!$real || !is_file($real)) {
            throw new Exception('Archivo privado no encontrado');
        }

        $rootPrefix = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($real, $rootPrefix) !== 0) {
            throw new Exception('Archivo fuera de la raiz privada permitida');
        }

        return $real;
    }

    public function documentosPorEntidad(int $hotelId, string $entidadTipo, int $entidadId, int $limite = 100): array
    {
        $entidadTipo = $this->normalizarEntidadTipo($entidadTipo);
        if ($hotelId <= 0 || $entidadTipo === null || $entidadId <= 0 || !$this->tablasDisponibles()) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $stmt = $this->db->query(
            "SELECT d.id,
                    d.hotel_id,
                    d.documento_tipo_id,
                    d.nombre_original,
                    d.mime_type,
                    d.size_bytes,
                    d.titulo,
                    d.descripcion,
                    d.etiquetas,
                    d.estado,
                    d.subido_por_usuario_id,
                    d.created_at,
                    d.updated_at,
                    dt.clave AS tipo_clave,
                    dt.nombre AS tipo_nombre,
                    u.nombre_completo AS subido_por_nombre,
                    de.relacion,
                    1 AS entidades_count,
                    CONCAT(de.entidad_tipo, '#', de.entidad_id) AS entidades_resumen
             FROM documento_entidades de
             INNER JOIN documentos d
                ON d.id = de.documento_id
               AND d.hotel_id = de.hotel_id
             LEFT JOIN documento_tipos dt
                ON dt.id = d.documento_tipo_id
               AND (dt.hotel_id IS NULL OR dt.hotel_id = d.hotel_id)
             LEFT JOIN usuarios u
                ON u.id = d.subido_por_usuario_id
             WHERE de.hotel_id = ?
               AND de.entidad_tipo = ?
               AND de.entidad_id = ?
             ORDER BY d.created_at DESC, d.id DESC
             LIMIT {$limite}",
            [$hotelId, $entidadTipo, $entidadId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function documentosPorTareaHotel(int $hotelId, int $tareaId, int $limite = 100): array
    {
        if (
            $hotelId <= 0
            || $tareaId <= 0
            || !$this->tablasDisponibles()
            || !$this->tablaExiste('tareas_operativas')
        ) {
            return [];
        }

        $limite = max(1, min(100, $limite));
        $stmt = $this->db->query(
            "SELECT d.id,
                    d.hotel_id,
                    d.documento_tipo_id,
                    d.nombre_original,
                    d.mime_type,
                    d.size_bytes,
                    d.titulo,
                    d.descripcion,
                    d.etiquetas,
                    d.estado,
                    d.subido_por_usuario_id,
                    d.created_at,
                    d.updated_at,
                    dt.clave AS tipo_clave,
                    dt.nombre AS tipo_nombre,
                    u.nombre_completo AS subido_por_nombre,
                    de.relacion,
                    1 AS entidades_count,
                    CONCAT(de.entidad_tipo, '#', de.entidad_id) AS entidades_resumen
             FROM documento_entidades de
             INNER JOIN tareas_operativas t
                ON t.id = de.entidad_id
               AND t.hotel_id = de.hotel_id
             INNER JOIN documentos d
                ON d.id = de.documento_id
               AND d.hotel_id = de.hotel_id
             LEFT JOIN documento_tipos dt
                ON dt.id = d.documento_tipo_id
               AND (dt.hotel_id IS NULL OR dt.hotel_id = d.hotel_id)
             LEFT JOIN usuarios u
                ON u.id = d.subido_por_usuario_id
             WHERE de.hotel_id = ?
               AND de.entidad_tipo = 'tarea'
               AND de.entidad_id = ?
               AND d.estado <> 'eliminado'
             ORDER BY de.created_at DESC, de.id DESC
             LIMIT {$limite}",
            [$hotelId, $tareaId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function entidadesPorDocumento(int $documentoId, int $hotelId): array
    {
        if ($documentoId <= 0 || $hotelId <= 0 || !$this->tablaExiste('documento_entidades')) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    documento_id,
                    entidad_tipo,
                    entidad_id,
                    relacion,
                    created_at
             FROM documento_entidades
             WHERE documento_id = ?
               AND hotel_id = ?
             ORDER BY entidad_tipo ASC, entidad_id ASC, id ASC",
            [$documentoId, $hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function crearDesdeUpload(int $hotelId, array $archivo, array $datos = [], ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');

        if (!$this->tablasDisponibles()) {
            throw new Exception('Tablas documentales no disponibles');
        }

        $tipo = $this->resolverTipoDocumento($hotelId, (int)($datos['documento_tipo_id'] ?? 0));
        $validado = $this->validarArchivoSubido($archivo, $tipo);
        $entidad = $this->resolverEntidad($hotelId, $datos);
        $storage = $this->prepararStoragePrivado($hotelId, $validado['extension']);
        $movedFile = null;

        if (!move_uploaded_file($validado['tmp_name'], $storage['absolute_path'])) {
            throw new Exception('No se pudo guardar el archivo en storage privado');
        }

        $movedFile = $storage['absolute_path'];
        @chmod($movedFile, 0640);
        $pdo = $this->db->getConnection();
        $usarTransaccionExterna = !empty($datos['_usar_transaccion_externa']);
        $controlaTransaccion = !$pdo->inTransaction();

        if (!$controlaTransaccion && !$usarTransaccionExterna) {
            $this->eliminarArchivoNuevo($movedFile);
            throw new Exception('La carga documental debe controlar su propia transaccion');
        }

        if ($controlaTransaccion) {
            $pdo->beginTransaction();
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO documentos
                    (hotel_id, documento_tipo_id, nombre_original, nombre_archivo,
                     storage_path, mime_type, size_bytes, sha256, titulo,
                     descripcion, etiquetas, estado, subido_por_usuario_id,
                     created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?, NOW(), NOW())"
            );
            $stmt->execute([
                $hotelId,
                $tipo ? (int)$tipo['id'] : null,
                $validado['nombre_original'],
                $storage['nombre_archivo'],
                $storage['storage_path'],
                $validado['mime_type'],
                $validado['size_bytes'],
                $validado['sha256'],
                $this->limitarTexto($datos['titulo'] ?? null, 180),
                $this->limitarTexto($datos['descripcion'] ?? null, 255),
                $this->limitarTexto($datos['etiquetas'] ?? null, 1000),
                $this->normalizarUsuarioId($usuarioId),
            ]);

            $documentoId = (int)$pdo->lastInsertId();

            if ($entidad) {
                $stmt = $pdo->prepare(
                    "INSERT INTO documento_entidades
                        (hotel_id, documento_id, entidad_tipo, entidad_id, relacion, created_at, updated_at)
                     VALUES
                        (?, ?, ?, ?, ?, NOW(), NOW())"
                );
                $stmt->execute([
                    $hotelId,
                    $documentoId,
                    $entidad['tipo'],
                    $entidad['id'],
                    $this->limitarTexto($datos['relacion'] ?? null, 80),
                ]);
            }

            $this->auditarCarga($hotelId, $documentoId, $usuarioId, $validado, $entidad);

            if ($controlaTransaccion) {
                $pdo->commit();
            }

            return [
                'documento_id' => $documentoId,
                'hotel_id' => $hotelId,
                'storage_path' => $storage['storage_path'],
                '_absolute_path' => $movedFile,
                'entidad' => $entidad,
            ];
        } catch (Throwable $e) {
            if ($controlaTransaccion && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->eliminarArchivoNuevo($movedFile);
            throw $e;
        }
    }

    public function actualizarMetadata(int $id, int $hotelId, array $datos, ?int $usuarioId = null): array
    {
        $id = $this->validarId($id, 'Documento invalido');
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');

        if (!$this->tablaExiste('documentos')) {
            throw new Exception('Tabla documentos no disponible');
        }

        $antes = $this->buscarPorIdHotel($id, $hotelId);
        if (!$antes) {
            throw new Exception('Documento no encontrado para el hotel actual');
        }

        if (($antes['estado'] ?? '') === 'eliminado') {
            throw new Exception('No se puede editar metadata de documentos eliminados');
        }

        $tipoId = (int)($datos['documento_tipo_id'] ?? 0);
        $tipo = $tipoId > 0 ? $this->resolverTipoDocumento($hotelId, $tipoId) : null;

        $nuevaMetadata = [
            'documento_tipo_id' => $tipo ? (int)$tipo['id'] : null,
            'titulo' => $this->limitarTexto($datos['titulo'] ?? null, 180),
            'descripcion' => $this->limitarTexto($datos['descripcion'] ?? null, 255),
            'etiquetas' => $this->limitarTexto($datos['etiquetas'] ?? null, 1000),
        ];

        $metadataAntes = $this->metadataAuditable($antes);
        if ($metadataAntes === $nuevaMetadata) {
            return [
                'documento_id' => $id,
                'hotel_id' => $hotelId,
                'changed' => false,
                'antes' => $metadataAntes,
                'despues' => $nuevaMetadata,
                'cambios' => [],
            ];
        }

        $stmt = $this->db->query(
            "UPDATE documentos
             SET documento_tipo_id = ?,
                 titulo = ?,
                 descripcion = ?,
                 etiquetas = ?,
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?",
            [
                $nuevaMetadata['documento_tipo_id'],
                $nuevaMetadata['titulo'],
                $nuevaMetadata['descripcion'],
                $nuevaMetadata['etiquetas'],
                $id,
                $hotelId,
            ]
        );

        if ($stmt === false) {
            throw new Exception('No se pudo guardar la metadata documental');
        }

        $despues = $this->buscarPorIdHotel($id, $hotelId);
        $metadataDespues = $this->metadataAuditable($despues ?: $nuevaMetadata);
        $cambios = $this->diferenciasMetadata($metadataAntes, $metadataDespues);

        $this->auditarMetadataActualizada($hotelId, $id, $usuarioId, $metadataAntes, $metadataDespues, $cambios);

        return [
            'documento_id' => $id,
            'hotel_id' => $hotelId,
            'changed' => true,
            'antes' => $metadataAntes,
            'despues' => $metadataDespues,
            'cambios' => $cambios,
        ];
    }

    public function actualizarEstado(int $id, int $hotelId, string $nuevoEstado, ?int $usuarioId = null): array
    {
        $id = $this->validarId($id, 'Documento invalido');
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $nuevoEstado = $this->normalizarEstadoCambio($nuevoEstado);

        if (!$this->tablaExiste('documentos')) {
            throw new Exception('Tabla documentos no disponible');
        }

        $antes = $this->buscarPorIdHotel($id, $hotelId);
        if (!$antes) {
            throw new Exception('Documento no encontrado para el hotel actual');
        }

        $estadoAntes = (string)($antes['estado'] ?? '');
        if ($estadoAntes === 'eliminado') {
            throw new Exception('Los documentos eliminados no se pueden cambiar en esta fase');
        }

        $transiciones = [
            'activo' => ['archivado', 'eliminado'],
            'archivado' => ['activo', 'eliminado'],
        ];

        if (!in_array($nuevoEstado, $transiciones[$estadoAntes] ?? [], true)) {
            throw new Exception('Transicion de estado documental no permitida');
        }

        $stmt = $this->db->query(
            "UPDATE documentos
             SET estado = ?,
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?
               AND estado = ?",
            [$nuevoEstado, $id, $hotelId, $estadoAntes]
        );

        if ($stmt === false || $stmt->rowCount() < 1) {
            throw new Exception('No se pudo actualizar el estado documental');
        }

        $despues = $this->buscarPorIdHotel($id, $hotelId);
        $this->auditarEstadoActualizado($hotelId, $id, $usuarioId, $antes, $despues ?: []);

        return [
            'documento_id' => $id,
            'hotel_id' => $hotelId,
            'changed' => true,
            'estado_antes' => $estadoAntes,
            'estado_despues' => $nuevoEstado,
        ];
    }

    public function normalizarEntidadTipo(?string $entidadTipo): ?string
    {
        $entidadTipo = trim((string)$entidadTipo);
        return in_array($entidadTipo, self::ENTIDAD_TIPOS, true) ? $entidadTipo : null;
    }

    public function entidadTiposPermitidos(): array
    {
        return self::ENTIDAD_TIPOS;
    }

    public function entidadExisteEnHotel(int $hotelId, string $entidadTipo, int $entidadId): bool
    {
        $entidadTipo = $this->normalizarEntidadTipo($entidadTipo);
        if ($hotelId <= 0 || $entidadTipo === null || $entidadId <= 0) {
            return false;
        }

        $tablas = [
            'proveedor' => 'proveedores',
            'compra' => 'compras',
            'cuenta_por_pagar' => 'cuentas_por_pagar',
            'huesped' => 'huespedes',
            'reservacion' => 'reservaciones',
            'trabajador' => 'trabajadores',
            'tarea' => 'tareas_operativas',
        ];

        $tabla = $tablas[$entidadTipo] ?? null;
        if (!$tabla || !$this->tablaExiste($tabla)) {
            return false;
        }

        $stmt = $this->db->query(
            "SELECT 1
             FROM {$tabla}
             WHERE id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$entidadId, $hotelId]
        );

        return $stmt !== false && (bool)$stmt->fetch();
    }

    private function resumenVacio(): array
    {
        return [
            'total' => 0,
            'activos' => 0,
            'archivados' => 0,
            'eliminados' => 0,
            'bytes_total' => 0,
        ];
    }

    private function normalizarEstado($value): string
    {
        $estado = (string)($value ?? 'todos');
        return in_array($estado, ['activo', 'archivado', 'eliminado', 'todos'], true)
            ? $estado
            : 'todos';
    }

    private function normalizarEstadoCambio(string $estado): string
    {
        $estado = trim($estado);
        if (!in_array($estado, ['activo', 'archivado', 'eliminado'], true)) {
            throw new Exception('Estado documental no permitido en esta fase');
        }

        return $estado;
    }

    private function tablaExiste(string $tabla): bool
    {
        $stmt = $this->db->query(
            "SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
             LIMIT 1",
            [$tabla]
        );

        return $stmt !== false && (bool)$stmt->fetch();
    }

    private function resolverTipoDocumento(int $hotelId, int $tipoId): ?array
    {
        if ($tipoId <= 0) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    clave,
                    nombre,
                    mime_permitidos,
                    max_size_mb,
                    activo
             FROM documento_tipos
             WHERE id = ?
               AND activo = 1
               AND (hotel_id IS NULL OR hotel_id = ?)
             LIMIT 1",
            [$tipoId, $hotelId]
        );

        $tipo = $stmt ? $stmt->fetch() : null;
        if (!$tipo) {
            throw new Exception('Tipo de documento no disponible para el hotel actual');
        }

        return $tipo;
    }

    private function validarArchivoSubido(array $archivo, ?array $tipo): array
    {
        $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new Exception($this->mensajeUploadError($error));
        }

        $tmpName = (string)($archivo['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new Exception('Archivo de carga no valido');
        }

        $size = (int)($archivo['size'] ?? 0);
        $maxBytes = $this->maxBytesParaTipo($tipo);
        if ($size <= 0) {
            throw new Exception('El archivo esta vacio');
        }

        if ($size > $maxBytes) {
            throw new Exception('El archivo excede el tamano maximo permitido de ' . $this->formatBytes($maxBytes));
        }

        $nombreOriginal = $this->limpiarNombreOriginal($archivo['name'] ?? 'documento');
        $extension = strtolower((string)pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if ($extension === '' || !preg_match('/^[a-z0-9]+$/', $extension)) {
            throw new Exception('Extension de archivo no valida');
        }

        if (in_array($extension, self::EXTENSIONES_PELIGROSAS, true)) {
            throw new Exception('Extension de archivo no permitida');
        }

        $mimeType = $this->detectarMime($tmpName);
        $mimesPermitidos = $this->mimesPermitidosParaTipo($tipo);
        if (!isset($mimesPermitidos[$mimeType])) {
            throw new Exception('Tipo MIME no permitido: ' . $mimeType);
        }

        if (!in_array($extension, $mimesPermitidos[$mimeType], true)) {
            throw new Exception('La extension no coincide con el tipo MIME detectado');
        }

        return [
            'tmp_name' => $tmpName,
            'nombre_original' => $nombreOriginal,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size_bytes' => $size,
            'sha256' => hash_file('sha256', $tmpName),
        ];
    }

    private function resolverEntidad(int $hotelId, array $datos): ?array
    {
        $entidadTipoRaw = trim((string)($datos['entidad_tipo'] ?? ''));
        $entidadId = (int)($datos['entidad_id'] ?? 0);

        if ($entidadTipoRaw === '' && $entidadId <= 0) {
            return null;
        }

        $entidadTipo = $this->normalizarEntidadTipo($entidadTipoRaw);
        if ($entidadTipo === null || $entidadId <= 0) {
            throw new Exception('Entidad documental no valida');
        }

        if (!$this->entidadExisteEnHotel($hotelId, $entidadTipo, $entidadId)) {
            throw new Exception('La entidad no existe o no pertenece al hotel actual');
        }

        return [
            'tipo' => $entidadTipo,
            'id' => $entidadId,
        ];
    }

    private function prepararStoragePrivado(int $hotelId, string $extension): array
    {
        if (!defined('STORAGE_PATH')) {
            throw new Exception('STORAGE_PATH no esta definido');
        }

        $root = rtrim(STORAGE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'documentos';
        $hotelDir = 'hotel_' . $hotelId;
        $year = date('Y');
        $month = date('m');
        $relativeDir = 'documentos/' . $hotelDir . '/' . $year . '/' . $month;
        $absoluteDir = $root . DIRECTORY_SEPARATOR . $hotelDir . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month;

        if (!$this->asegurarDirectorioPrivado($root) || !$this->asegurarDirectorioPrivado($absoluteDir)) {
            throw new Exception('No se pudo preparar el storage privado');
        }

        $rootReal = realpath($root);
        $dirReal = realpath($absoluteDir);
        if (!$rootReal || !$dirReal || strpos($dirReal, $rootReal) !== 0) {
            throw new Exception('Ruta de storage privada no valida');
        }

        for ($i = 0; $i < 10; $i++) {
            $nombreArchivo = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $absolutePath = $dirReal . DIRECTORY_SEPARATOR . $nombreArchivo;
            if (!file_exists($absolutePath)) {
                return [
                    'nombre_archivo' => $nombreArchivo,
                    'storage_path' => $relativeDir . '/' . $nombreArchivo,
                    'absolute_path' => $absolutePath,
                ];
            }
        }

        throw new Exception('No se pudo generar un nombre de archivo unico');
    }

    private function asegurarDirectorioPrivado(string $dir): bool
    {
        if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
            return false;
        }

        $guard = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($guard)) {
            @file_put_contents($guard, "Require all denied\nDeny from all\n");
        }

        return is_dir($dir) && is_writable($dir);
    }

    private function mimesPermitidosParaTipo(?array $tipo): array
    {
        $permitidos = self::MIME_PERMITIDOS;
        $mimesTipo = $this->parseMimePermitidos($tipo['mime_permitidos'] ?? null);

        if (empty($mimesTipo)) {
            return $permitidos;
        }

        return array_intersect_key($permitidos, array_flip($mimesTipo));
    }

    private function parseMimePermitidos($value): array
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return [];
        }

        $json = json_decode($value, true);
        if (is_array($json)) {
            return array_values(array_filter(array_map('strval', $json)));
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    private function maxBytesParaTipo(?array $tipo): int
    {
        $maxBytes = self::MAX_UPLOAD_BYTES;
        $tipoMb = (float)($tipo['max_size_mb'] ?? 0);

        if ($tipoMb > 0) {
            $maxBytes = min($maxBytes, (int)floor($tipoMb * 1024 * 1024));
        }

        return max(1, $maxBytes);
    }

    private function detectarMime(string $tmpName): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $tmpName) : null;

        if ($finfo) {
            finfo_close($finfo);
        }

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }

    private function limpiarNombreOriginal($nombre): string
    {
        $nombre = basename(str_replace('\\', '/', (string)$nombre));
        $nombre = preg_replace('/[\x00-\x1F\x7F]+/', '', $nombre);
        $nombre = trim((string)$nombre);

        if ($nombre === '') {
            $nombre = 'documento';
        }

        return $this->substrSeguro($nombre, 255);
    }

    private function limitarTexto($value, int $max): ?string
    {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return null;
        }

        return $this->substrSeguro($text, $max);
    }

    private function substrSeguro(string $text, int $max): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $max, 'UTF-8');
        }

        return substr($text, 0, $max);
    }

    private function validarId($value, string $message): int
    {
        $id = (int)$value;
        if ($id <= 0) {
            throw new Exception($message);
        }

        return $id;
    }

    private function normalizarUsuarioId($value): ?int
    {
        $id = (int)($value ?? 0);
        return $id > 0 ? $id : null;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    private function mensajeUploadError(int $error): string
    {
        $mensajes = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el limite configurado del servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el limite del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se cargo parcialmente',
            UPLOAD_ERR_NO_FILE => 'Debe seleccionar un archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'No hay directorio temporal disponible',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo temporal',
            UPLOAD_ERR_EXTENSION => 'Una extension de PHP detuvo la carga',
        ];

        return $mensajes[$error] ?? 'Error de carga no identificado';
    }

    private function eliminarArchivoNuevo(?string $path): void
    {
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }

    private function auditarCarga(int $hotelId, int $documentoId, ?int $usuarioId, array $archivo, ?array $entidad): void
    {
        try {
            AuditService::record('documentos.cargado', [
                'hotel_id' => $hotelId,
                'usuario_id' => $this->normalizarUsuarioId($usuarioId),
                'entidad_tipo' => 'documento',
                'entidad_id' => (string)$documentoId,
                'descripcion' => 'Documento cargado en storage privado',
                'datos_despues' => [
                    'documento_id' => $documentoId,
                    'nombre_original' => $archivo['nombre_original'],
                    'mime_type' => $archivo['mime_type'],
                    'size_bytes' => $archivo['size_bytes'],
                    'sha256' => $archivo['sha256'],
                    'vinculo' => $entidad,
                    'storage_privado' => true,
                ],
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar carga documental: ' . $e->getMessage());
        }
    }

    private function metadataAuditable(array $documento): array
    {
        return [
            'documento_tipo_id' => !empty($documento['documento_tipo_id']) ? (int)$documento['documento_tipo_id'] : null,
            'titulo' => $this->limitarTexto($documento['titulo'] ?? null, 180),
            'descripcion' => $this->limitarTexto($documento['descripcion'] ?? null, 255),
            'etiquetas' => $this->limitarTexto($documento['etiquetas'] ?? null, 1000),
        ];
    }

    private function diferenciasMetadata(array $antes, array $despues): array
    {
        $cambios = [];
        foreach ($despues as $campo => $valor) {
            if (($antes[$campo] ?? null) !== $valor) {
                $cambios[$campo] = [
                    'antes' => $antes[$campo] ?? null,
                    'despues' => $valor,
                ];
            }
        }

        return $cambios;
    }

    private function auditarMetadataActualizada(int $hotelId, int $documentoId, ?int $usuarioId, array $antes, array $despues, array $cambios): void
    {
        if (empty($cambios)) {
            return;
        }

        try {
            AuditService::record('documentos.metadata_actualizada', [
                'hotel_id' => $hotelId,
                'usuario_id' => $this->normalizarUsuarioId($usuarioId),
                'entidad_tipo' => 'documento',
                'entidad_id' => (string)$documentoId,
                'descripcion' => 'Metadata documental actualizada',
                'datos_antes' => [
                    'documento_id' => $documentoId,
                    'metadata' => $antes,
                ],
                'datos_despues' => [
                    'documento_id' => $documentoId,
                    'metadata' => $despues,
                    'cambios' => array_keys($cambios),
                ],
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar metadata documental: ' . $e->getMessage());
        }
    }

    private function auditarEstadoActualizado(int $hotelId, int $documentoId, ?int $usuarioId, array $antes, array $despues): void
    {
        $estadoAntes = (string)($antes['estado'] ?? '');
        $estadoDespues = (string)($despues['estado'] ?? '');
        if ($estadoAntes === '' || $estadoDespues === '' || $estadoAntes === $estadoDespues) {
            return;
        }

        try {
            AuditService::record('documentos.estado_actualizado', [
                'hotel_id' => $hotelId,
                'usuario_id' => $this->normalizarUsuarioId($usuarioId),
                'entidad_tipo' => 'documento',
                'entidad_id' => (string)$documentoId,
                'descripcion' => 'Estado documental actualizado',
                'datos_antes' => [
                    'documento_id' => $documentoId,
                    'estado' => $estadoAntes,
                    'titulo' => $this->limitarTexto($antes['titulo'] ?? null, 180),
                ],
                'datos_despues' => [
                    'documento_id' => $documentoId,
                    'estado' => $estadoDespues,
                    'titulo' => $this->limitarTexto($despues['titulo'] ?? null, 180),
                ],
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar estado documental: ' . $e->getMessage());
        }
    }
}
