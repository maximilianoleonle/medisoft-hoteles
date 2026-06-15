<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class Proveedor extends Model {
    protected $table = 'proveedores';
    protected $fillable = [
        'hotel_id',
        'nombre',
        'razon_social',
        'rfc',
        'telefono',
        'email',
        'direccion',
        'notas',
        'activo',
        'created_by',
        'updated_by',
    ];

    public function tablaDisponible(): bool {
        $stmt = $this->db->query(
            "SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proveedores'
             LIMIT 1"
        );

        return $stmt !== false && (bool)$stmt->fetch();
    }

    public function listarPorHotel(int $hotelId, array $filtros = [], int $limite = 100): array {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $limite = max(1, min(300, $limite));
        $where = ['hotel_id = ?'];
        $params = [$hotelId];

        $estado = (string)($filtros['estado'] ?? 'activos');
        if ($estado === 'inactivos') {
            $where[] = 'activo = 0';
        } elseif ($estado !== 'todos') {
            $where[] = 'activo = 1';
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(nombre LIKE ? OR razon_social LIKE ? OR rfc LIKE ? OR telefono LIKE ? OR email LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT *
             FROM proveedores
             WHERE " . implode(' AND ', $where) . "
             ORDER BY activo DESC, nombre ASC
             LIMIT {$limite}",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function resumenPorHotel(int $hotelId): array {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return ['total' => 0, 'activos' => 0, 'inactivos' => 0];
        }

        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos,
                SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) AS inactivos
             FROM proveedores
             WHERE hotel_id = ?",
            [$hotelId]
        );

        $row = $stmt ? $stmt->fetch() : [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'activos' => (int)($row['activos'] ?? 0),
            'inactivos' => (int)($row['inactivos'] ?? 0),
        ];
    }

    public function buscarPorIdHotel(int $id, int $hotelId): ?array {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT *
             FROM proveedores
             WHERE id = ? AND hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function crearParaHotel(int $hotelId, array $datos, ?int $usuarioId = null): int {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            throw new Exception('Catalogo de proveedores no disponible');
        }

        $datos = $this->normalizarDatos($datos);
        $this->validarDatos($datos);
        $this->validarDuplicadoPorHotel($hotelId, $datos);

        $stmt = $this->db->query(
            "INSERT INTO proveedores
                (hotel_id, nombre, razon_social, rfc, telefono, email, direccion, notas,
                 activo, created_by, updated_by, created_at, updated_at)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, NOW(), NOW())",
            [
                $hotelId,
                $datos['nombre'],
                $datos['razon_social'],
                $datos['rfc'],
                $datos['telefono'],
                $datos['email'],
                $datos['direccion'],
                $datos['notas'],
                $usuarioId,
                $usuarioId,
            ]
        );

        if (!$stmt) {
            throw new Exception('No se pudo crear el proveedor');
        }

        return (int)$this->db->lastInsertId();
    }

    public function actualizarParaHotel(int $id, int $hotelId, array $datos, ?int $usuarioId = null): bool {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        $datos = $this->normalizarDatos($datos);
        $this->validarDatos($datos);
        $this->validarDuplicadoPorHotel($hotelId, $datos, $id);

        $stmt = $this->db->query(
            "UPDATE proveedores
             SET nombre = ?,
                 razon_social = ?,
                 rfc = ?,
                 telefono = ?,
                 email = ?,
                 direccion = ?,
                 notas = ?,
                 updated_by = ?,
                 updated_at = NOW()
             WHERE id = ? AND hotel_id = ?",
            [
                $datos['nombre'],
                $datos['razon_social'],
                $datos['rfc'],
                $datos['telefono'],
                $datos['email'],
                $datos['direccion'],
                $datos['notas'],
                $usuarioId,
                $id,
                $hotelId,
            ]
        );

        return $stmt !== false;
    }

    public function cambiarActivo(int $id, int $hotelId, bool $activo, ?int $usuarioId = null): bool {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE proveedores
             SET activo = ?, updated_by = ?, updated_at = NOW()
             WHERE id = ? AND hotel_id = ?",
            [$activo ? 1 : 0, $usuarioId, $id, $hotelId]
        );

        return $stmt !== false;
    }

    public function normalizarDatos(array $datos): array {
        return [
            'nombre' => $this->limpiarTexto($datos['nombre'] ?? '', 160),
            'razon_social' => $this->nullableTexto($datos['razon_social'] ?? null, 180),
            'rfc' => $this->nullableTexto(strtoupper((string)($datos['rfc'] ?? '')), 20),
            'telefono' => $this->nullableTexto($datos['telefono'] ?? null, 40),
            'email' => $this->nullableTexto(strtolower((string)($datos['email'] ?? '')), 160),
            'direccion' => $this->nullableTexto($datos['direccion'] ?? null, 255),
            'notas' => $this->nullableTexto($datos['notas'] ?? null, 1000),
        ];
    }

    private function validarDatos(array $datos): void {
        if ($datos['nombre'] === '') {
            throw new Exception('El nombre del proveedor es obligatorio');
        }

        if ($datos['email'] !== null && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El correo del proveedor no es valido');
        }
    }

    private function validarDuplicadoPorHotel(int $hotelId, array $datos, ?int $ignorarId = null): void {
        if ($datos['rfc'] !== null) {
            $duplicadoRfc = $this->buscarDuplicado($hotelId, 'rfc', $datos['rfc'], $ignorarId, false);
            if ($duplicadoRfc) {
                throw new Exception('Ya existe un proveedor con ese RFC en el hotel actual: ' . $duplicadoRfc['nombre']);
            }
        }

        $duplicadoNombre = $this->buscarDuplicado($hotelId, 'nombre', $datos['nombre'], $ignorarId, true);
        if ($duplicadoNombre) {
            throw new Exception('Ya existe un proveedor activo con ese nombre en el hotel actual.');
        }
    }

    private function buscarDuplicado(int $hotelId, string $campo, string $valor, ?int $ignorarId, bool $soloActivos): ?array {
        $columnasPermitidas = ['nombre', 'rfc'];
        if (!in_array($campo, $columnasPermitidas, true)) {
            return null;
        }

        $where = ['hotel_id = ?', $campo . ' = ?'];
        $params = [$hotelId, $valor];

        if ($ignorarId !== null && $ignorarId > 0) {
            $where[] = 'id <> ?';
            $params[] = $ignorarId;
        }

        if ($soloActivos) {
            $where[] = 'activo = 1';
        }

        $stmt = $this->db->query(
            "SELECT id, nombre, rfc, activo
             FROM proveedores
             WHERE " . implode(' AND ', $where) . "
             LIMIT 1",
            $params
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    private function nullableTexto($value, int $limite): ?string {
        $texto = $this->limpiarTexto($value, $limite);
        return $texto === '' ? null : $texto;
    }

    private function limpiarTexto($value, int $limite): string {
        $texto = trim((string)($value ?? ''));
        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, $limite, 'UTF-8');
        }

        return substr($texto, 0, $limite);
    }
}
