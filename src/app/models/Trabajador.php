<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class Trabajador extends Model
{
    protected $table = 'trabajadores';

    public function tablaDisponible(): bool
    {
        return $this->tablaExiste('trabajadores');
    }

    public function tablasLedgerDisponibles(): array
    {
        $tablas = [
            'trabajador_pagos',
            'trabajador_anticipos',
            'trabajador_prestamos',
            'trabajador_asistencias',
            'trabajador_documentos',
        ];

        $disponibles = [];
        foreach ($tablas as $tabla) {
            $disponibles[$tabla] = $this->tablaExiste($tabla);
        }

        return $disponibles;
    }

    public function listarPorHotel(int $hotelId, array $filtros = [], int $limite = 100): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $limite = max(1, min(300, $limite));
        $where = ['t.hotel_id = ?'];
        $params = [$hotelId];

        $estado = (string)($filtros['estado'] ?? 'activos');
        if ($estado === 'inactivos') {
            $where[] = "t.estado = 'inactivo'";
        } elseif ($estado === 'baja') {
            $where[] = "t.estado = 'baja'";
        } elseif ($estado !== 'todos') {
            $where[] = "t.estado = 'activo'";
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(t.nombre_completo LIKE ? OR t.identificacion LIKE ? OR t.rol_laboral LIKE ? OR t.telefono LIKE ? OR t.email LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.usuario_id,
                    t.nombre_completo,
                    t.identificacion,
                    t.rol_laboral,
                    t.telefono,
                    t.email,
                    t.estado,
                    t.fecha_alta,
                    t.fecha_baja,
                    t.salario_base,
                    t.periodicidad_pago,
                    t.created_at,
                    t.updated_at,
                    u.nombre_completo AS usuario_nombre,
                    u.nombre_usuario AS usuario_login
             FROM trabajadores t
             LEFT JOIN usuarios u
                ON u.id = t.usuario_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY FIELD(t.estado, 'activo', 'inactivo', 'baja'), t.nombre_completo ASC
             LIMIT {$limite}",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function resumenPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'baja' => 0,
            ];
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) AS inactivos,
                    SUM(CASE WHEN estado = 'baja' THEN 1 ELSE 0 END) AS baja
             FROM trabajadores
             WHERE hotel_id = ?",
            [$hotelId]
        );

        $row = $stmt ? ($stmt->fetch() ?: []) : [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'activos' => (int)($row['activos'] ?? 0),
            'inactivos' => (int)($row['inactivos'] ?? 0),
            'baja' => (int)($row['baja'] ?? 0),
        ];
    }

    public function buscarPorIdHotel(int $id, int $hotelId): ?array
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.usuario_id,
                    t.nombre_completo,
                    t.identificacion,
                    t.rol_laboral,
                    t.telefono,
                    t.email,
                    t.estado,
                    t.fecha_alta,
                    t.fecha_baja,
                    t.salario_base,
                    t.periodicidad_pago,
                    t.notas,
                    t.created_by,
                    t.updated_by,
                    t.created_at,
                    t.updated_at,
                    u.nombre_completo AS usuario_nombre,
                    u.nombre_usuario AS usuario_login
             FROM trabajadores t
             LEFT JOIN usuarios u
                ON u.id = t.usuario_id
             WHERE t.id = ?
               AND t.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function usuariosVinculablesPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaExiste('hotel_usuarios') || !$this->tablaExiste('usuarios')) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT u.id,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.email,
                    hu.rol AS rol_hotel
             FROM hotel_usuarios hu
             INNER JOIN usuarios u
                ON u.id = hu.usuario_id
             WHERE hu.hotel_id = ?
               AND hu.activo = 1
               AND u.activo = 1
             ORDER BY u.nombre_completo ASC, u.nombre_usuario ASC",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function crearParaHotel(int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            throw new Exception('Modulo de Personal no disponible');
        }

        $datos = $this->normalizarDatos($datos);
        $this->validarDatos($hotelId, $datos);

        $stmt = $this->db->query(
            "INSERT INTO trabajadores
                (hotel_id, usuario_id, nombre_completo, identificacion, rol_laboral,
                 telefono, email, estado, fecha_alta, fecha_baja, salario_base,
                 periodicidad_pago, notas, created_by, updated_by, created_at, updated_at)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, 'activo', ?, NULL, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $hotelId,
                $datos['usuario_id'],
                $datos['nombre_completo'],
                $datos['identificacion'],
                $datos['rol_laboral'],
                $datos['telefono'],
                $datos['email'],
                $datos['fecha_alta'],
                $datos['salario_base'],
                $datos['periodicidad_pago'],
                $datos['notas'],
                $usuarioId,
                $usuarioId,
            ]
        );

        if (!$stmt) {
            throw new Exception('No se pudo crear el trabajador');
        }

        return (int)$this->db->lastInsertId();
    }

    public function actualizarParaHotel(int $id, int $hotelId, array $datos, ?int $usuarioId = null): bool
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        if (!$this->buscarPorIdHotel($id, $hotelId)) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        $datos = $this->normalizarDatos($datos);
        $this->validarDatos($hotelId, $datos);

        $stmt = $this->db->query(
            "UPDATE trabajadores
             SET usuario_id = ?,
                 nombre_completo = ?,
                 identificacion = ?,
                 rol_laboral = ?,
                 telefono = ?,
                 email = ?,
                 fecha_alta = ?,
                 salario_base = ?,
                 periodicidad_pago = ?,
                 notas = ?,
                 updated_by = ?,
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?",
            [
                $datos['usuario_id'],
                $datos['nombre_completo'],
                $datos['identificacion'],
                $datos['rol_laboral'],
                $datos['telefono'],
                $datos['email'],
                $datos['fecha_alta'],
                $datos['salario_base'],
                $datos['periodicidad_pago'],
                $datos['notas'],
                $usuarioId,
                $id,
                $hotelId,
            ]
        );

        return $stmt !== false;
    }

    public function cambiarEstadoParaHotel(int $id, int $hotelId, string $estado, ?int $usuarioId = null): bool
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        $estado = strtolower(trim($estado));
        if (!in_array($estado, ['activo', 'inactivo', 'baja'], true)) {
            throw new Exception('Estado de trabajador no valido');
        }

        $actual = $this->buscarPorIdHotel($id, $hotelId);
        if (!$actual) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        if (($actual['estado'] ?? null) === $estado) {
            return true;
        }

        $fechaBajaSql = $estado === 'baja'
            ? 'COALESCE(fecha_baja, CURDATE())'
            : 'NULL';

        $stmt = $this->db->query(
            "UPDATE trabajadores
             SET estado = ?,
                 fecha_baja = {$fechaBajaSql},
                 updated_by = ?,
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?",
            [$estado, $usuarioId, $id, $hotelId]
        );

        return $stmt !== false;
    }

    public function resumenLedgerPorTrabajador(int $trabajadorId, int $hotelId): array
    {
        $resumen = [
            'pagos_count' => 0,
            'pagos_total' => '0.00',
            'anticipos_count' => 0,
            'anticipos_saldo' => '0.00',
            'prestamos_count' => 0,
            'prestamos_saldo' => '0.00',
            'asistencias_count' => 0,
            'documentos_count' => 0,
        ];

        if ($trabajadorId <= 0 || $hotelId <= 0) {
            return $resumen;
        }

        if ($this->tablaExiste('trabajador_pagos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'activo' THEN monto ELSE 0 END), 0) AS monto
                 FROM trabajador_pagos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['pagos_count'] = (int)($row['total'] ?? 0);
            $resumen['pagos_total'] = $this->decimal($row['monto'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_anticipos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_anticipos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['anticipos_count'] = (int)($row['total'] ?? 0);
            $resumen['anticipos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_prestamos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'vigente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_prestamos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['prestamos_count'] = (int)($row['total'] ?? 0);
            $resumen['prestamos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_asistencias')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM trabajador_asistencias
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['asistencias_count'] = (int)($row['total'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_documentos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM trabajador_documentos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?
                   AND estado = 'activo'",
                [$hotelId, $trabajadorId]
            );
            $resumen['documentos_count'] = (int)($row['total'] ?? 0);
        }

        return $resumen;
    }

    public function ultimosMovimientosPorTrabajador(int $trabajadorId, int $hotelId, int $limite = 20): array
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_asistencias')) {
            return [];
        }

        $limite = max(1, min(50, $limite));
        $stmt = $this->db->query(
            "SELECT id,
                    fecha,
                    tipo,
                    hora_entrada,
                    hora_salida,
                    horas,
                    horas_extra,
                    observaciones
             FROM trabajador_asistencias
             WHERE hotel_id = ?
               AND trabajador_id = ?
             ORDER BY fecha DESC, id DESC
             LIMIT {$limite}",
            [$hotelId, $trabajadorId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function normalizarDatos(array $datos): array
    {
        $usuarioId = (int)($datos['usuario_id'] ?? 0);
        $salarioBase = trim((string)($datos['salario_base'] ?? ''));
        $salarioNormalizado = null;
        if ($salarioBase !== '') {
            $salarioNormalizado = is_numeric($salarioBase)
                ? number_format((float)$salarioBase, 2, '.', '')
                : 'INVALIDO';
        }

        return [
            'usuario_id' => $usuarioId > 0 ? $usuarioId : null,
            'nombre_completo' => $this->limpiarTexto($datos['nombre_completo'] ?? '', 150),
            'identificacion' => $this->nullableTexto($datos['identificacion'] ?? null, 60),
            'rol_laboral' => $this->nullableTexto($datos['rol_laboral'] ?? null, 80),
            'telefono' => $this->nullableTexto($datos['telefono'] ?? null, 30),
            'email' => $this->nullableTexto(strtolower((string)($datos['email'] ?? '')), 120),
            'fecha_alta' => $this->nullableFecha($datos['fecha_alta'] ?? null),
            'salario_base' => $salarioNormalizado,
            'periodicidad_pago' => $this->nullablePeriodicidad($datos['periodicidad_pago'] ?? null),
            'notas' => $this->nullableTexto($datos['notas'] ?? null, 1000),
        ];
    }

    private function validarDatos(int $hotelId, array $datos): void
    {
        if ($datos['nombre_completo'] === '') {
            throw new Exception('El nombre completo del trabajador es obligatorio');
        }

        if ($datos['email'] !== null && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El correo del trabajador no es valido');
        }

        if ($datos['salario_base'] !== null && !is_numeric($datos['salario_base'])) {
            throw new Exception('El salario base debe ser numerico');
        }

        if ($datos['salario_base'] !== null && (float)$datos['salario_base'] < 0) {
            throw new Exception('El salario base no puede ser negativo');
        }

        if ($datos['usuario_id'] !== null && !$this->usuarioPerteneceAlHotel((int)$datos['usuario_id'], $hotelId)) {
            throw new Exception('El usuario vinculado no pertenece al hotel actual');
        }
    }

    private function usuarioPerteneceAlHotel(int $usuarioId, int $hotelId): bool
    {
        if ($usuarioId <= 0 || $hotelId <= 0 || !$this->tablaExiste('hotel_usuarios')) {
            return false;
        }

        $stmt = $this->db->query(
            "SELECT 1
             FROM hotel_usuarios hu
             INNER JOIN usuarios u
                ON u.id = hu.usuario_id
             WHERE hu.hotel_id = ?
               AND hu.usuario_id = ?
               AND hu.activo = 1
               AND u.activo = 1
             LIMIT 1",
            [$hotelId, $usuarioId]
        );

        return $stmt !== false && (bool)$stmt->fetch();
    }

    private function fetchOne(string $sql, array $params = []): array
    {
        $stmt = $this->db->query($sql, $params);
        return $stmt ? ($stmt->fetch() ?: []) : [];
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

    private function decimal($value): string
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }

    private function nullableFecha($value): ?string
    {
        $texto = trim((string)($value ?? ''));
        if ($texto === '') {
            return null;
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $texto);
        return $fecha && $fecha->format('Y-m-d') === $texto ? $texto : null;
    }

    private function nullablePeriodicidad($value): ?string
    {
        $texto = strtolower(trim((string)($value ?? '')));
        return in_array($texto, ['semanal', 'quincenal', 'mensual', 'por_evento'], true)
            ? $texto
            : null;
    }

    private function nullableTexto($value, int $limite): ?string
    {
        $texto = $this->limpiarTexto($value, $limite);
        return $texto === '' ? null : $texto;
    }

    private function limpiarTexto($value, int $limite): string
    {
        $texto = trim((string)($value ?? ''));
        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, $limite, 'UTF-8');
        }

        return substr($texto, 0, $limite);
    }
}
