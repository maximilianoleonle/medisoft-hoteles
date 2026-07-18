<?php
/**
 * Pedidos de lavanderia de huesped (modulo lavanderia).
 *
 * Ciclo: recibido -> en_proceso -> listo -> entregado (o cancelado).
 *
 * DINERO: el cobro entra por MovimientoCaja::registrarMovimiento (tipo
 * ingreso, categoria lazy 'Lavanderia', referencia LAV-{id}) desde el
 * controller, con idempotencia via cobro_movimiento_id. reservacion_id es
 * SOLO informativo: JAMAS se crean reservacion_abonos ni CxC ligadas a la
 * reserva (contaminarian el saldo de hospedaje de resumenPagos).
 */
class LavanderiaPedido extends Model {
    protected $table = 'lavanderia_pedidos';
    protected $fillable = [
        'hotel_id',
        'cliente_nombre',
        'reservacion_id',
        'habitacion_etiqueta',
        'estado',
        'total',
        'notas',
        'recibido_por_usuario_id',
    ];

    public const ESTADOS = ['recibido', 'en_proceso', 'listo', 'entregado', 'cancelado'];
    public const ESTADOS_ACTIVOS = ['recibido', 'en_proceso', 'listo'];

    public static function catalogoEstados(): array {
        return [
            'recibido'   => ['label' => 'Recibido',    'clase' => 'st-warn',  'icono' => 'fa-inbox'],
            'en_proceso' => ['label' => 'En proceso',  'clase' => 'st-clean', 'icono' => 'fa-arrows-spin'],
            'listo'      => ['label' => 'Listo',       'clase' => 'st-ok',    'icono' => 'fa-circle-check'],
            'entregado'  => ['label' => 'Entregado',   'clase' => 'st-busy',  'icono' => 'fa-hand-holding-heart'],
            'cancelado'  => ['label' => 'Cancelado',   'clase' => 'st-off',   'icono' => 'fa-ban'],
        ];
    }

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    public function listar(?int $hotelId = null, array $filtros = [], int $limite = 100): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($hotelId <= 0) {
            return [];
        }
        $limite = max(1, min(300, $limite));

        $sql = "SELECT p.*,
                       (SELECT COALESCE(SUM(pi.cantidad), 0) FROM lavanderia_pedido_items pi
                         WHERE pi.pedido_id = p.id AND pi.hotel_id = p.hotel_id) AS piezas
                FROM {$this->table} p
                WHERE p.hotel_id = ?";
        $params = [$hotelId];

        $estado = (string)($filtros['estado'] ?? '');
        if ($estado === 'activos') {
            $sql .= " AND p.estado IN ('recibido', 'en_proceso', 'listo')";
        } elseif ($estado !== '' && $estado !== 'todos' && in_array($estado, self::ESTADOS, true)) {
            $sql .= " AND p.estado = ?";
            $params[] = $estado;
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $sql .= " AND (p.cliente_nombre LIKE ? OR p.habitacion_etiqueta LIKE ? OR p.id = ?)";
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = (int)$buscar;
        }

        $sql .= " ORDER BY FIELD(p.estado, 'listo', 'en_proceso', 'recibido', 'entregado', 'cancelado'), p.id DESC LIMIT {$limite}";

        $stmt = $this->db->query($sql, $params);
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function obtenerPorId(int $id, ?int $hotelId = null): ?array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($id <= 0 || $hotelId <= 0) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT p.*, u.nombre_completo AS recibido_por
             FROM {$this->table} p
             LEFT JOIN usuarios u ON u.id = p.recibido_por_usuario_id
             WHERE p.id = ? AND p.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function items(int $pedidoId, ?int $hotelId = null): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($pedidoId <= 0 || $hotelId <= 0) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT * FROM lavanderia_pedido_items
             WHERE pedido_id = ? AND hotel_id = ?
             ORDER BY id ASC",
            [$pedidoId, $hotelId]
        );
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function resumen(?int $hotelId = null): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        $base = ['activos' => 0, 'listos' => 0, 'por_cobrar' => 0, 'monto_por_cobrar' => 0.0];
        if ($hotelId <= 0) {
            return $base;
        }

        $stmt = $this->db->query(
            "SELECT
                COALESCE(SUM(CASE WHEN estado IN ('recibido', 'en_proceso', 'listo') THEN 1 ELSE 0 END), 0) AS activos,
                COALESCE(SUM(CASE WHEN estado = 'listo' THEN 1 ELSE 0 END), 0) AS listos,
                COALESCE(SUM(CASE WHEN estado <> 'cancelado' AND total > 0 AND cobro_movimiento_id IS NULL THEN 1 ELSE 0 END), 0) AS por_cobrar,
                COALESCE(SUM(CASE WHEN estado <> 'cancelado' AND total > 0 AND cobro_movimiento_id IS NULL THEN total ELSE 0 END), 0) AS monto_por_cobrar
             FROM {$this->table}
             WHERE hotel_id = ?",
            [$hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        if (!$row) {
            return $base;
        }

        return [
            'activos' => (int)$row['activos'],
            'listos' => (int)$row['listos'],
            'por_cobrar' => (int)$row['por_cobrar'],
            'monto_por_cobrar' => (float)$row['monto_por_cobrar'],
        ];
    }

    /**
     * Reservaciones con huesped en casa (checked_in) para vincular un pedido.
     */
    public function reservacionesEnCasa(?int $hotelId = null): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($hotelId <= 0) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT r.id,
                    h.nombre_completo AS huesped,
                    COALESCE(GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', '), '') AS habitaciones
             FROM reservaciones r
             INNER JOIN huespedes h ON h.id = r.huesped_id
             LEFT JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id
             LEFT JOIN habitaciones hab ON hab.id = rh.habitacion_id AND hab.hotel_id = r.hotel_id
             WHERE r.hotel_id = ? AND r.estado = 'checked_in'
             GROUP BY r.id, h.nombre_completo
             ORDER BY h.nombre_completo ASC",
            [$hotelId]
        );
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    /**
     * Crea el pedido con sus partidas en una transaccion.
     * $items = [['descripcion' =>, 'cantidad' =>, 'precio' =>], ...].
     */
    public function crear(int $hotelId, array $datos, array $items, ?int $usuarioId): int {
        if ($hotelId <= 0) {
            throw new InvalidArgumentException('Hotel no valido.');
        }

        $reservacionId = (int)($datos['reservacion_id'] ?? 0);
        $clienteNombre = trim((string)($datos['cliente_nombre'] ?? ''));
        $habitacionEtiqueta = null;

        if ($reservacionId > 0) {
            // El vinculo a reserva es informativo, pero se valida tenencia y
            // se toma el nombre/habitacion reales del huesped en casa.
            $stmt = $this->db->query(
                "SELECT r.id,
                        h.nombre_completo AS huesped,
                        COALESCE(GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', '), '') AS habitaciones
                 FROM reservaciones r
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 LEFT JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id
                 LEFT JOIN habitaciones hab ON hab.id = rh.habitacion_id AND hab.hotel_id = r.hotel_id
                 WHERE r.id = ? AND r.hotel_id = ? AND r.estado = 'checked_in'
                 GROUP BY r.id, h.nombre_completo
                 LIMIT 1",
                [$reservacionId, $hotelId]
            );
            $reserva = $stmt ? $stmt->fetch() : null;
            if (!$reserva) {
                throw new InvalidArgumentException('La reservacion elegida no esta en casa (check-in) en este hotel.');
            }
            $clienteNombre = (string)$reserva['huesped'];
            $habitacionEtiqueta = trim((string)$reserva['habitaciones']) ?: null;
            if ($habitacionEtiqueta !== null) {
                $habitacionEtiqueta = mb_substr($habitacionEtiqueta, 0, 40);
            }
        } else {
            $reservacionId = null;
        }

        if ($clienteNombre === '' || mb_strlen($clienteNombre) > 160) {
            throw new InvalidArgumentException('Indica el nombre del cliente (maximo 160 caracteres).');
        }

        $limpios = [];
        foreach ($items as $item) {
            $descripcion = trim((string)($item['descripcion'] ?? ''));
            $cantidad = (int)($item['cantidad'] ?? 0);
            $precio = round((float)($item['precio'] ?? 0), 2);
            if ($descripcion === '') {
                continue;
            }
            if (mb_strlen($descripcion) > 160) {
                throw new InvalidArgumentException('La descripcion de una prenda es demasiado larga (maximo 160).');
            }
            if ($cantidad <= 0 || $cantidad > 999) {
                throw new InvalidArgumentException('La cantidad de "' . $descripcion . '" debe estar entre 1 y 999.');
            }
            if ($precio < 0 || $precio > 999999) {
                throw new InvalidArgumentException('El precio de "' . $descripcion . '" no es valido.');
            }
            $limpios[] = [
                'descripcion' => $descripcion,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'importe' => round($cantidad * $precio, 2),
            ];
        }
        if (empty($limpios)) {
            throw new InvalidArgumentException('Agrega al menos una prenda o servicio al pedido.');
        }
        if (count($limpios) > 60) {
            throw new InvalidArgumentException('Un pedido admite maximo 60 partidas.');
        }

        $total = 0.0;
        foreach ($limpios as $item) {
            $total += $item['importe'];
        }
        $total = round($total, 2);

        $notas = trim((string)($datos['notas'] ?? ''));
        $notas = $notas === '' ? null : mb_substr($notas, 0, 500);

        $this->db->safeBeginTransaction();
        try {
            $stmt = $this->db->query(
                "INSERT INTO {$this->table}
                    (hotel_id, cliente_nombre, reservacion_id, habitacion_etiqueta, estado, total, notas, recibido_por_usuario_id, created_at)
                 VALUES (?, ?, ?, ?, 'recibido', ?, ?, ?, NOW())",
                [$hotelId, $clienteNombre, $reservacionId, $habitacionEtiqueta, $total, $notas, $usuarioId ?: null]
            );
            if (!$stmt) {
                throw new RuntimeException('No se pudo crear el pedido.');
            }
            $pedidoId = (int)$this->db->lastInsertId();

            foreach ($limpios as $item) {
                $ok = $this->db->query(
                    "INSERT INTO lavanderia_pedido_items
                        (hotel_id, pedido_id, descripcion, cantidad, precio_unitario, importe, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())",
                    [$hotelId, $pedidoId, $item['descripcion'], $item['cantidad'], $item['precio'], $item['importe']]
                );
                if (!$ok) {
                    throw new RuntimeException('No se pudo registrar una partida del pedido.');
                }
            }

            $this->db->safeCommit();
            return $pedidoId;
        } catch (Throwable $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    /**
     * Transiciones de estado con candado optimista (WHERE estado = anterior).
     * Acciones: iniciar (recibido->en_proceso), listo (en_proceso->listo),
     * entregar (listo->entregado), cancelar (cualquier activo, sin cobro).
     */
    public function cambiarEstado(int $hotelId, int $pedidoId, string $accion, ?int $usuarioId): array {
        if ($hotelId <= 0 || $pedidoId <= 0) {
            throw new InvalidArgumentException('Pedido no valido.');
        }

        $transiciones = [
            'iniciar'  => ['desde' => ['recibido'], 'hacia' => 'en_proceso'],
            'listo'    => ['desde' => ['en_proceso', 'recibido'], 'hacia' => 'listo'],
            'entregar' => ['desde' => ['listo', 'en_proceso', 'recibido'], 'hacia' => 'entregado'],
            'cancelar' => ['desde' => ['recibido', 'en_proceso', 'listo'], 'hacia' => 'cancelado'],
        ];
        if (!isset($transiciones[$accion])) {
            throw new InvalidArgumentException('Accion no valida.');
        }

        $this->db->safeBeginTransaction();
        try {
            $stmt = $this->db->query(
                "SELECT * FROM {$this->table} WHERE id = ? AND hotel_id = ? LIMIT 1 FOR UPDATE",
                [$pedidoId, $hotelId]
            );
            $pedido = $stmt ? $stmt->fetch() : null;
            if (!$pedido) {
                throw new RuntimeException('Pedido no encontrado para el hotel actual.');
            }

            $estadoAnterior = (string)$pedido['estado'];
            if (!in_array($estadoAnterior, $transiciones[$accion]['desde'], true)) {
                throw new RuntimeException('El pedido esta "' . $estadoAnterior . '" y no admite esta accion.');
            }
            if ($accion === 'cancelar' && !empty($pedido['cobro_movimiento_id'])) {
                throw new RuntimeException('El pedido ya fue cobrado en Caja: registra la devolucion desde Caja antes de cancelarlo.');
            }

            $hacia = $transiciones[$accion]['hacia'];
            $extra = $hacia === 'entregado' ? ", entregado_en = NOW()" : '';
            $upd = $this->db->query(
                "UPDATE {$this->table}
                 SET estado = ?{$extra}, updated_at = NOW()
                 WHERE id = ? AND hotel_id = ? AND estado = ?",
                [$hacia, $pedidoId, $hotelId, $estadoAnterior]
            );
            if (!$upd || $upd->rowCount() !== 1) {
                throw new RuntimeException('El pedido cambio de estado en otra sesion: recarga la pagina.');
            }

            $this->db->safeCommit();
            return ['anterior' => $estadoAnterior, 'nuevo' => $hacia, 'pedido' => $pedido];
        } catch (Throwable $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }
}
