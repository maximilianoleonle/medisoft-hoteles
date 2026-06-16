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

    public function registrarConceptoLaboralParaHotel(int $trabajadorId, int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaDisponible() || !$this->tablaExiste('trabajador_pagos')) {
            throw new Exception('Ledger laboral no disponible');
        }

        $trabajador = $this->buscarPorIdHotel($trabajadorId, $hotelId);
        if (!$trabajador) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        if (($trabajador['estado'] ?? '') !== 'activo') {
            throw new Exception('Solo se pueden registrar conceptos a trabajadores activos');
        }

        $concepto = $this->normalizarConceptoLaboral($datos);
        $this->validarConceptoLaboral($concepto);

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "INSERT INTO trabajador_pagos
                    (hotel_id, trabajador_id, tipo, efecto, monto, concepto,
                     periodo_inicio, periodo_fin, fecha, referencia, notas,
                     estado, created_by, updated_by, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                     'activo', ?, ?, NOW(), NOW())",
                [
                    $hotelId,
                    $trabajadorId,
                    $concepto['tipo'],
                    $concepto['efecto'],
                    $concepto['monto'],
                    $concepto['concepto'],
                    $concepto['periodo_inicio'],
                    $concepto['periodo_fin'],
                    $concepto['fecha'],
                    $concepto['referencia'],
                    $concepto['notas'],
                    $usuarioId,
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new Exception('No se pudo registrar el concepto laboral');
            }

            $conceptoId = (int)$this->db->lastInsertId();
            $this->db->safeCommit();

            return $conceptoId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function conceptoLaboralPorIdHotel(int $conceptoId, int $trabajadorId, int $hotelId): ?array
    {
        if ($conceptoId <= 0 || $trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_pagos')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    trabajador_id,
                    tipo,
                    efecto,
                    monto,
                    concepto,
                    periodo_inicio,
                    periodo_fin,
                    fecha,
                    referencia,
                    notas,
                    estado,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
             FROM trabajador_pagos
             WHERE id = ?
               AND trabajador_id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$conceptoId, $trabajadorId, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function registrarAnticipoLaboralParaHotel(int $trabajadorId, int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaDisponible() || !$this->tablaExiste('trabajador_anticipos')) {
            throw new Exception('Ledger de anticipos no disponible');
        }

        $trabajador = $this->buscarPorIdHotel($trabajadorId, $hotelId);
        if (!$trabajador) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        if (($trabajador['estado'] ?? '') !== 'activo') {
            throw new Exception('Solo se pueden registrar anticipos a trabajadores activos');
        }

        $anticipo = $this->normalizarAnticipoLaboral($datos);
        $this->validarAnticipoLaboral($anticipo);

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "INSERT INTO trabajador_anticipos
                    (hotel_id, trabajador_id, monto, saldo_pendiente, fecha,
                     motivo, estado, referencia, notas, created_by, updated_by,
                     created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, ?, ?, NOW(), NOW())",
                [
                    $hotelId,
                    $trabajadorId,
                    $anticipo['monto'],
                    $anticipo['monto'],
                    $anticipo['fecha'],
                    $anticipo['motivo'],
                    $anticipo['referencia'],
                    $anticipo['notas'],
                    $usuarioId,
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new Exception('No se pudo registrar el anticipo laboral');
            }

            $anticipoId = (int)$this->db->lastInsertId();
            $this->db->safeCommit();

            return $anticipoId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function registrarPrestamoLaboralParaHotel(int $trabajadorId, int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaDisponible() || !$this->tablaExiste('trabajador_prestamos')) {
            throw new Exception('Ledger de prestamos no disponible');
        }

        $trabajador = $this->buscarPorIdHotel($trabajadorId, $hotelId);
        if (!$trabajador) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        if (($trabajador['estado'] ?? '') !== 'activo') {
            throw new Exception('Solo se pueden registrar prestamos a trabajadores activos');
        }

        $prestamo = $this->normalizarPrestamoLaboral($datos);
        $this->validarPrestamoLaboral($prestamo);

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "INSERT INTO trabajador_prestamos
                    (hotel_id, trabajador_id, monto, saldo_pendiente, fecha,
                     plazo_meses, abono_periodico, motivo, estado, referencia,
                     notas, created_by, updated_by, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, 'vigente', ?, ?, ?, ?, NOW(), NOW())",
                [
                    $hotelId,
                    $trabajadorId,
                    $prestamo['monto'],
                    $prestamo['monto'],
                    $prestamo['fecha'],
                    $prestamo['plazo_meses'],
                    $prestamo['abono_periodico'],
                    $prestamo['motivo'],
                    $prestamo['referencia'],
                    $prestamo['notas'],
                    $usuarioId,
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new Exception('No se pudo registrar el prestamo laboral');
            }

            $prestamoId = (int)$this->db->lastInsertId();
            $this->db->safeCommit();

            return $prestamoId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function anticipoLaboralPorIdHotel(int $anticipoId, int $trabajadorId, int $hotelId): ?array
    {
        if ($anticipoId <= 0 || $trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_anticipos')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    trabajador_id,
                    monto,
                    saldo_pendiente,
                    fecha,
                    motivo,
                    estado,
                    referencia,
                    notas,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
             FROM trabajador_anticipos
             WHERE id = ?
               AND trabajador_id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$anticipoId, $trabajadorId, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function prestamoLaboralPorIdHotel(int $prestamoId, int $trabajadorId, int $hotelId): ?array
    {
        if ($prestamoId <= 0 || $trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_prestamos')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    trabajador_id,
                    monto,
                    saldo_pendiente,
                    fecha,
                    plazo_meses,
                    abono_periodico,
                    motivo,
                    estado,
                    referencia,
                    notas,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
             FROM trabajador_prestamos
             WHERE id = ?
               AND trabajador_id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$prestamoId, $trabajadorId, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function registrarAsistenciaLaboralParaHotel(int $trabajadorId, int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaDisponible() || !$this->tablaExiste('trabajador_asistencias')) {
            throw new Exception('Ledger de asistencias no disponible');
        }

        $trabajador = $this->buscarPorIdHotel($trabajadorId, $hotelId);
        if (!$trabajador) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        if (($trabajador['estado'] ?? '') !== 'activo') {
            throw new Exception('Solo se pueden registrar asistencias a trabajadores activos');
        }

        $asistencia = $this->normalizarAsistenciaLaboral($datos);
        $this->validarAsistenciaLaboral($asistencia);

        $duplicada = $this->fetchOne(
            "SELECT id
             FROM trabajador_asistencias
             WHERE hotel_id = ?
               AND trabajador_id = ?
               AND fecha = ?
             LIMIT 1",
            [$hotelId, $trabajadorId, $asistencia['fecha']]
        );

        if (!empty($duplicada)) {
            throw new Exception('Ya existe asistencia para este trabajador en la fecha indicada');
        }

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "INSERT INTO trabajador_asistencias
                    (hotel_id, trabajador_id, fecha, tipo, hora_entrada, hora_salida,
                     horas, horas_extra, observaciones, created_by, updated_by,
                     created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $hotelId,
                    $trabajadorId,
                    $asistencia['fecha'],
                    $asistencia['tipo'],
                    $asistencia['hora_entrada'],
                    $asistencia['hora_salida'],
                    $asistencia['horas'],
                    $asistencia['horas_extra'],
                    $asistencia['observaciones'],
                    $usuarioId,
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new Exception('No se pudo registrar la asistencia laboral');
            }

            $asistenciaId = (int)$this->db->lastInsertId();
            $this->db->safeCommit();

            return $asistenciaId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function asistenciaLaboralPorIdHotel(int $asistenciaId, int $trabajadorId, int $hotelId): ?array
    {
        if ($asistenciaId <= 0 || $trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_asistencias')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    trabajador_id,
                    fecha,
                    tipo,
                    hora_entrada,
                    hora_salida,
                    horas,
                    horas_extra,
                    observaciones,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
             FROM trabajador_asistencias
             WHERE id = ?
               AND trabajador_id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$asistenciaId, $trabajadorId, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
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
            'conceptos_a_favor' => '0.00',
            'conceptos_en_contra' => '0.00',
            'anticipos_count' => 0,
            'anticipos_saldo' => '0.00',
            'prestamos_count' => 0,
            'prestamos_saldo' => '0.00',
            'asistencias_count' => 0,
            'documentos_count' => 0,
            'saldo_informativo' => '0.00',
        ];

        if ($trabajadorId <= 0 || $hotelId <= 0) {
            return $resumen;
        }

        if ($this->tablaExiste('trabajador_pagos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'activo' THEN monto ELSE 0 END), 0) AS monto,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'a_favor' THEN monto ELSE 0 END), 0) AS a_favor,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'en_contra' THEN monto ELSE 0 END), 0) AS en_contra
                 FROM trabajador_pagos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['pagos_count'] = (int)($row['total'] ?? 0);
            $resumen['pagos_total'] = $this->decimal($row['monto'] ?? 0);
            $resumen['conceptos_a_favor'] = $this->decimal($row['a_favor'] ?? 0);
            $resumen['conceptos_en_contra'] = $this->decimal($row['en_contra'] ?? 0);
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

        $resumen['saldo_informativo'] = $this->decimal(
            (float)$resumen['conceptos_a_favor']
            - (float)$resumen['conceptos_en_contra']
            - (float)$resumen['anticipos_saldo']
            - (float)$resumen['prestamos_saldo']
        );

        return $resumen;
    }

    public function conceptosLaboralesPorTrabajador(int $trabajadorId, int $hotelId, int $limite = 20): array
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_pagos')) {
            return [];
        }

        $limite = max(1, min(50, $limite));
        $stmt = $this->db->query(
            "SELECT id,
                    tipo,
                    efecto,
                    monto,
                    concepto,
                    periodo_inicio,
                    periodo_fin,
                    fecha,
                    referencia,
                    estado,
                    created_at
             FROM trabajador_pagos
             WHERE hotel_id = ?
               AND trabajador_id = ?
             ORDER BY fecha DESC, id DESC
             LIMIT {$limite}",
            [$hotelId, $trabajadorId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function anticiposPorTrabajador(int $trabajadorId, int $hotelId, int $limite = 20): array
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_anticipos')) {
            return [];
        }

        $limite = max(1, min(50, $limite));
        $stmt = $this->db->query(
            "SELECT id,
                    monto,
                    saldo_pendiente,
                    fecha,
                    motivo,
                    estado,
                    referencia,
                    created_at
             FROM trabajador_anticipos
             WHERE hotel_id = ?
               AND trabajador_id = ?
             ORDER BY fecha DESC, id DESC
             LIMIT {$limite}",
            [$hotelId, $trabajadorId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function prestamosPorTrabajador(int $trabajadorId, int $hotelId, int $limite = 20): array
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_prestamos')) {
            return [];
        }

        $limite = max(1, min(50, $limite));
        $stmt = $this->db->query(
            "SELECT id,
                    monto,
                    saldo_pendiente,
                    fecha,
                    plazo_meses,
                    abono_periodico,
                    motivo,
                    estado,
                    referencia,
                    created_at
             FROM trabajador_prestamos
             WHERE hotel_id = ?
               AND trabajador_id = ?
             ORDER BY fecha DESC, id DESC
             LIMIT {$limite}",
            [$hotelId, $trabajadorId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
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

    public function normalizarConceptoLaboral(array $datos): array
    {
        $tipo = strtolower(trim((string)($datos['tipo'] ?? '')));
        $efecto = strtolower(trim((string)($datos['efecto'] ?? '')));
        $monto = trim((string)($datos['monto'] ?? ''));
        $montoNormalizado = is_numeric($monto)
            ? number_format((float)$monto, 2, '.', '')
            : 'INVALIDO';

        if (in_array($tipo, ['comision', 'bono'], true)) {
            $efecto = 'a_favor';
        } elseif ($tipo === 'descuento') {
            $efecto = 'en_contra';
        }

        return [
            'tipo' => $tipo,
            'efecto' => in_array($efecto, ['a_favor', 'en_contra'], true) ? $efecto : '',
            'monto' => $montoNormalizado,
            'concepto' => $this->limpiarTexto($datos['concepto'] ?? '', 160),
            'periodo_inicio' => $this->nullableFecha($datos['periodo_inicio'] ?? null),
            'periodo_fin' => $this->nullableFecha($datos['periodo_fin'] ?? null),
            'fecha' => $this->nullableFecha($datos['fecha'] ?? null),
            'referencia' => $this->nullableTexto($datos['referencia'] ?? null, 120),
            'notas' => $this->nullableTexto($datos['notas'] ?? null, 1000),
        ];
    }

    private function validarConceptoLaboral(array $datos): void
    {
        if (!in_array($datos['tipo'], ['comision', 'bono', 'descuento', 'ajuste'], true)) {
            throw new Exception('Tipo de concepto laboral no permitido');
        }

        if (!in_array($datos['efecto'], ['a_favor', 'en_contra'], true)) {
            throw new Exception('Efecto de concepto laboral no valido');
        }

        if ($datos['monto'] === 'INVALIDO' || !is_numeric($datos['monto']) || (float)$datos['monto'] <= 0) {
            throw new Exception('El monto debe ser mayor a cero');
        }

        if ($datos['concepto'] === '') {
            throw new Exception('El concepto es obligatorio');
        }

        if ($datos['fecha'] === null) {
            throw new Exception('La fecha del concepto es obligatoria');
        }

        if ($datos['periodo_inicio'] !== null && $datos['periodo_fin'] !== null && $datos['periodo_fin'] < $datos['periodo_inicio']) {
            throw new Exception('El periodo fin no puede ser anterior al periodo inicio');
        }
    }

    private function normalizarAnticipoLaboral(array $datos): array
    {
        return [
            'monto' => $this->normalizarMontoLaboral($datos['monto'] ?? ''),
            'fecha' => $this->nullableFecha($datos['fecha'] ?? null),
            'motivo' => $this->limpiarTexto($datos['motivo'] ?? '', 160),
            'referencia' => $this->nullableTexto($datos['referencia'] ?? null, 120),
            'notas' => $this->nullableTexto($datos['notas'] ?? null, 1000),
        ];
    }

    private function normalizarPrestamoLaboral(array $datos): array
    {
        $plazo = trim((string)($datos['plazo_meses'] ?? ''));
        $abono = trim((string)($datos['abono_periodico'] ?? ''));

        return [
            'monto' => $this->normalizarMontoLaboral($datos['monto'] ?? ''),
            'fecha' => $this->nullableFecha($datos['fecha'] ?? null),
            'plazo_meses' => $plazo === '' ? null : (is_numeric($plazo) ? (int)$plazo : 'INVALIDO'),
            'abono_periodico' => $abono === '' ? null : $this->normalizarMontoLaboral($abono, true),
            'motivo' => $this->limpiarTexto($datos['motivo'] ?? '', 160),
            'referencia' => $this->nullableTexto($datos['referencia'] ?? null, 120),
            'notas' => $this->nullableTexto($datos['notas'] ?? null, 1000),
        ];
    }

    private function normalizarAsistenciaLaboral(array $datos): array
    {
        return [
            'fecha' => $this->nullableFecha($datos['fecha'] ?? null),
            'tipo' => strtolower(trim((string)($datos['tipo'] ?? ''))),
            'hora_entrada' => $this->nullableHora($datos['hora_entrada'] ?? null),
            'hora_salida' => $this->nullableHora($datos['hora_salida'] ?? null),
            'horas' => $this->normalizarHorasLaborales($datos['horas'] ?? null),
            'horas_extra' => $this->normalizarHorasLaborales($datos['horas_extra'] ?? null),
            'observaciones' => $this->nullableTexto($datos['observaciones'] ?? null, 255),
        ];
    }

    private function validarAnticipoLaboral(array $datos): void
    {
        $this->validarMontoLaboral($datos['monto']);

        if ($datos['fecha'] === null) {
            throw new Exception('La fecha del anticipo es obligatoria');
        }

        if ($datos['motivo'] === '') {
            throw new Exception('El motivo del anticipo es obligatorio');
        }
    }

    private function validarPrestamoLaboral(array $datos): void
    {
        $this->validarMontoLaboral($datos['monto']);

        if ($datos['fecha'] === null) {
            throw new Exception('La fecha del prestamo es obligatoria');
        }

        if ($datos['motivo'] === '') {
            throw new Exception('El motivo del prestamo es obligatorio');
        }

        if ($datos['plazo_meses'] === 'INVALIDO' || ($datos['plazo_meses'] !== null && (int)$datos['plazo_meses'] <= 0)) {
            throw new Exception('El plazo del prestamo debe ser mayor a cero cuando se capture');
        }

        if ($datos['abono_periodico'] === 'INVALIDO' || ($datos['abono_periodico'] !== null && (float)$datos['abono_periodico'] < 0)) {
            throw new Exception('El abono periodico debe ser cero o mayor cuando se capture');
        }
    }

    private function validarAsistenciaLaboral(array $datos): void
    {
        $tiposPermitidos = ['asistencia', 'falta', 'retardo', 'permiso', 'incapacidad', 'descanso', 'horas_extra'];

        if ($datos['fecha'] === null) {
            throw new Exception('La fecha de asistencia es obligatoria');
        }

        if (!in_array($datos['tipo'], $tiposPermitidos, true)) {
            throw new Exception('Tipo de asistencia laboral no permitido');
        }

        if ($datos['hora_entrada'] === 'INVALIDO' || $datos['hora_salida'] === 'INVALIDO') {
            throw new Exception('Formato de hora invalido');
        }

        if ($datos['hora_entrada'] !== null && $datos['hora_salida'] !== null && $datos['hora_salida'] < $datos['hora_entrada']) {
            throw new Exception('La hora de salida no puede ser anterior a la hora de entrada');
        }

        if ($datos['horas'] === 'INVALIDO' || ($datos['horas'] !== null && (float)$datos['horas'] < 0)) {
            throw new Exception('Las horas deben ser cero o mayores cuando se capturen');
        }

        if ($datos['horas_extra'] === 'INVALIDO' || ($datos['horas_extra'] !== null && (float)$datos['horas_extra'] < 0)) {
            throw new Exception('Las horas extra deben ser cero o mayores cuando se capturen');
        }
    }

    private function normalizarMontoLaboral($value, bool $permiteCero = false): string
    {
        $monto = trim((string)($value ?? ''));
        if (!is_numeric($monto)) {
            return 'INVALIDO';
        }

        $numero = (float)$monto;
        if ($permiteCero && $numero === 0.0) {
            return '0.00';
        }

        return number_format($numero, 2, '.', '');
    }

    private function normalizarHorasLaborales($value): ?string
    {
        $horas = trim((string)($value ?? ''));
        if ($horas === '') {
            return null;
        }

        return $this->normalizarMontoLaboral($horas, true);
    }

    private function validarMontoLaboral($monto): void
    {
        if ($monto === 'INVALIDO' || !is_numeric($monto) || (float)$monto <= 0) {
            throw new Exception('El monto debe ser mayor a cero');
        }
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

    private function nullableHora($value)
    {
        $texto = trim((string)($value ?? ''));
        if ($texto === '') {
            return null;
        }

        $hora = DateTime::createFromFormat('H:i', $texto);
        return $hora && $hora->format('H:i') === $texto ? $texto : 'INVALIDO';
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
