<?php

require_once __DIR__ . '/LavanderiaBlanco.php';

/**
 * Ciclos de lavado por lote (modulo lavanderia).
 *
 * Ciclo de vida: en_proceso -> recibido | cancelado.
 *  - Crear lote mueve sucio -> en lavado por cada partida (atomico).
 *  - Recibir captura piezas recibidas por partida: recibida vuelve a
 *    limpio y la diferencia es merma (baja definitiva), + costo real.
 *  - Cancelar devuelve todo el lote de en lavado -> sucio.
 *
 * El gasto en Caja del costo (lotes externos) NO vive aqui: lo registra
 * el controller con MovimientoCaja (patron mantenimiento, idempotente
 * via gasto_movimiento_id).
 */
class LavanderiaLote extends Model {
    protected $table = 'lavanderia_lotes';
    protected $fillable = [
        'hotel_id',
        'tipo',
        'estado',
        'proveedor',
        'piezas_enviadas',
        'piezas_recibidas',
        'merma_total',
        'costo',
        'notas',
        'enviado_por_usuario_id',
    ];

    public const TIPOS = ['interno', 'externo'];
    public const ESTADOS = ['en_proceso', 'recibido', 'cancelado'];

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    public function listar(?int $hotelId = null, array $filtros = [], int $limite = 100): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($hotelId <= 0) {
            return [];
        }
        $limite = max(1, min(300, $limite));

        $sql = "SELECT l.*,
                       ue.nombre_completo AS enviado_por,
                       ur.nombre_completo AS recibido_por,
                       (SELECT COUNT(*) FROM lavanderia_lote_items li
                         WHERE li.lote_id = l.id AND li.hotel_id = l.hotel_id) AS partidas
                FROM {$this->table} l
                LEFT JOIN usuarios ue ON ue.id = l.enviado_por_usuario_id
                LEFT JOIN usuarios ur ON ur.id = l.recibido_por_usuario_id
                WHERE l.hotel_id = ?";
        $params = [$hotelId];

        $estado = (string)($filtros['estado'] ?? '');
        if ($estado !== '' && $estado !== 'todos' && in_array($estado, self::ESTADOS, true)) {
            $sql .= " AND l.estado = ?";
            $params[] = $estado;
        }

        $tipo = (string)($filtros['tipo'] ?? '');
        if ($tipo !== '' && $tipo !== 'todos' && in_array($tipo, self::TIPOS, true)) {
            $sql .= " AND l.tipo = ?";
            $params[] = $tipo;
        }

        $sql .= " ORDER BY l.id DESC LIMIT {$limite}";

        $stmt = $this->db->query($sql, $params);
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function obtenerPorId(int $id, ?int $hotelId = null): ?array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($id <= 0 || $hotelId <= 0) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT l.*,
                    ue.nombre_completo AS enviado_por,
                    ur.nombre_completo AS recibido_por
             FROM {$this->table} l
             LEFT JOIN usuarios ue ON ue.id = l.enviado_por_usuario_id
             LEFT JOIN usuarios ur ON ur.id = l.recibido_por_usuario_id
             WHERE l.id = ? AND l.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function items(int $loteId, ?int $hotelId = null): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($loteId <= 0 || $hotelId <= 0) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT li.*, b.nombre AS blanco_nombre, b.categoria AS blanco_categoria
             FROM lavanderia_lote_items li
             INNER JOIN lavanderia_blancos b
                ON b.id = li.blanco_id
               AND b.hotel_id = li.hotel_id
             WHERE li.lote_id = ? AND li.hotel_id = ?
             ORDER BY b.nombre ASC",
            [$loteId, $hotelId]
        );
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function resumen(?int $hotelId = null): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        $base = ['en_proceso' => 0, 'gastos_pendientes' => 0];
        if ($hotelId <= 0) {
            return $base;
        }

        $stmt = $this->db->query(
            "SELECT
                COALESCE(SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END), 0) AS en_proceso,
                COALESCE(SUM(CASE WHEN estado = 'recibido' AND costo > 0 AND gasto_movimiento_id IS NULL THEN 1 ELSE 0 END), 0) AS gastos_pendientes
             FROM {$this->table}
             WHERE hotel_id = ?",
            [$hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return $row
            ? ['en_proceso' => (int)$row['en_proceso'], 'gastos_pendientes' => (int)$row['gastos_pendientes']]
            : $base;
    }

    /**
     * Crea el lote y mueve sucio -> en lavado por cada partida, TODO en una
     * transaccion. $items = [blanco_id => cantidad].
     */
    public function crear(int $hotelId, string $tipo, ?string $proveedor, ?string $notas, array $items, ?int $usuarioId): int {
        if ($hotelId <= 0) {
            throw new InvalidArgumentException('Hotel no valido.');
        }
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException('Tipo de lavado no valido.');
        }

        $limpios = [];
        foreach ($items as $blancoId => $cantidad) {
            $blancoId = (int)$blancoId;
            $cantidad = (int)$cantidad;
            if ($blancoId > 0 && $cantidad > 0) {
                $limpios[$blancoId] = $cantidad;
            }
        }
        if (empty($limpios)) {
            throw new InvalidArgumentException('Indica al menos una pieza a enviar a lavar.');
        }
        if (count($limpios) > 100) {
            throw new InvalidArgumentException('Un lote admite maximo 100 partidas.');
        }
        // Orden determinista de locks (por blanco_id): dos lotes concurrentes
        // con blancos traslapados jamas se bloquean en orden cruzado.
        ksort($limpios);

        $proveedor = trim((string)$proveedor);
        if ($tipo === 'externo' && $proveedor === '') {
            throw new InvalidArgumentException('Indica el proveedor del lavado externo.');
        }
        $proveedor = $proveedor === '' ? null : mb_substr($proveedor, 0, 160);
        $notas = trim((string)$notas);
        $notas = $notas === '' ? null : mb_substr($notas, 0, 500);

        $blancoModel = new LavanderiaBlanco();

        $this->db->safeBeginTransaction();
        try {
            $stmt = $this->db->query(
                "INSERT INTO {$this->table}
                    (hotel_id, tipo, estado, proveedor, piezas_enviadas, notas, enviado_por_usuario_id, created_at)
                 VALUES (?, ?, 'en_proceso', ?, 0, ?, ?, NOW())",
                [$hotelId, $tipo, $proveedor, $notas, $usuarioId ?: null]
            );
            if (!$stmt) {
                throw new RuntimeException('No se pudo crear el lote de lavado.');
            }
            $loteId = (int)$this->db->lastInsertId();

            $totalPiezas = 0;
            foreach ($limpios as $blancoId => $cantidad) {
                // FOR UPDATE + validacion de stock sucio dentro del modelo de blancos.
                $blancoModel->enviarALavadoEnTransaccion($hotelId, $blancoId, $cantidad, $loteId, $usuarioId);

                $ok = $this->db->query(
                    "INSERT INTO lavanderia_lote_items
                        (hotel_id, lote_id, blanco_id, cantidad_enviada, created_at)
                     VALUES (?, ?, ?, ?, NOW())",
                    [$hotelId, $loteId, $blancoId, $cantidad]
                );
                if (!$ok) {
                    throw new RuntimeException('No se pudo registrar la partida del lote.');
                }
                $totalPiezas += $cantidad;
            }

            $this->db->query(
                "UPDATE {$this->table} SET piezas_enviadas = ?, updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?",
                [$totalPiezas, $loteId, $hotelId]
            );

            $this->db->safeCommit();
            return $loteId;
        } catch (Throwable $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    /**
     * Recibe el lote: por partida, recibida vuelve a limpio y la diferencia
     * es merma. Guarda costo real (el gasto en Caja lo registra el caller).
     * $recibidas = [item_id => cantidad_recibida].
     */
    public function recibir(int $hotelId, int $loteId, array $recibidas, ?float $costo, ?int $usuarioId): array {
        if ($hotelId <= 0 || $loteId <= 0) {
            throw new InvalidArgumentException('Lote no valido.');
        }
        if ($costo !== null && ($costo < 0 || $costo > 9999999)) {
            throw new InvalidArgumentException('El costo del lavado no es valido.');
        }

        $blancoModel = new LavanderiaBlanco();

        $this->db->safeBeginTransaction();
        try {
            $stmt = $this->db->query(
                "SELECT * FROM {$this->table} WHERE id = ? AND hotel_id = ? LIMIT 1 FOR UPDATE",
                [$loteId, $hotelId]
            );
            $lote = $stmt ? $stmt->fetch() : null;
            if (!$lote) {
                throw new RuntimeException('Lote no encontrado para el hotel actual.');
            }
            if ((string)$lote['estado'] !== 'en_proceso') {
                throw new RuntimeException('Este lote ya fue ' . ((string)$lote['estado'] === 'recibido' ? 'recibido' : 'cancelado') . '.');
            }

            $items = $this->items($loteId, $hotelId);
            if (empty($items)) {
                throw new RuntimeException('El lote no tiene partidas.');
            }
            // Mismo orden de locks que crear() (blanco_id ascendente): sin
            // esto, un crear() concurrente con blancos traslapados deadlockea.
            usort($items, static fn($a, $b) => (int)$a['blanco_id'] <=> (int)$b['blanco_id']);

            $totalRecibidas = 0;
            $totalMerma = 0;
            foreach ($items as $item) {
                $itemId = (int)$item['id'];
                $enviada = (int)$item['cantidad_enviada'];
                $recibida = array_key_exists($itemId, $recibidas) ? (int)$recibidas[$itemId] : $enviada;
                if ($recibida < 0 || $recibida > $enviada) {
                    throw new RuntimeException('La cantidad recibida de "' . $item['blanco_nombre'] . '" debe estar entre 0 y ' . $enviada . '.');
                }

                $blancoModel->recibirDeLavadoEnTransaccion($hotelId, (int)$item['blanco_id'], $enviada, $recibida, $loteId, $usuarioId);

                $this->db->query(
                    "UPDATE lavanderia_lote_items
                     SET cantidad_recibida = ?, merma = ?
                     WHERE id = ? AND hotel_id = ?",
                    [$recibida, $enviada - $recibida, $itemId, $hotelId]
                );

                $totalRecibidas += $recibida;
                $totalMerma += ($enviada - $recibida);
            }

            $this->db->query(
                "UPDATE {$this->table}
                 SET estado = 'recibido', piezas_recibidas = ?, merma_total = ?, costo = ?,
                     recibido_por_usuario_id = ?, recibido_en = NOW(), updated_at = NOW()
                 WHERE id = ? AND hotel_id = ? AND estado = 'en_proceso'",
                [$totalRecibidas, $totalMerma, $costo, $usuarioId ?: null, $loteId, $hotelId]
            );

            $this->db->safeCommit();
            return ['recibidas' => $totalRecibidas, 'merma' => $totalMerma];
        } catch (Throwable $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancela un lote en proceso devolviendo todas las piezas a sucio.
     */
    public function cancelar(int $hotelId, int $loteId, ?int $usuarioId): void {
        if ($hotelId <= 0 || $loteId <= 0) {
            throw new InvalidArgumentException('Lote no valido.');
        }

        $blancoModel = new LavanderiaBlanco();

        $this->db->safeBeginTransaction();
        try {
            $stmt = $this->db->query(
                "SELECT * FROM {$this->table} WHERE id = ? AND hotel_id = ? LIMIT 1 FOR UPDATE",
                [$loteId, $hotelId]
            );
            $lote = $stmt ? $stmt->fetch() : null;
            if (!$lote) {
                throw new RuntimeException('Lote no encontrado para el hotel actual.');
            }
            if ((string)$lote['estado'] !== 'en_proceso') {
                throw new RuntimeException('Solo se puede cancelar un lote en proceso.');
            }

            $items = $this->items($loteId, $hotelId);
            usort($items, static fn($a, $b) => (int)$a['blanco_id'] <=> (int)$b['blanco_id']);
            foreach ($items as $item) {
                $blancoModel->devolverASucioEnTransaccion($hotelId, (int)$item['blanco_id'], (int)$item['cantidad_enviada'], $loteId, $usuarioId);
            }

            $this->db->query(
                "UPDATE {$this->table}
                 SET estado = 'cancelado', updated_at = NOW()
                 WHERE id = ? AND hotel_id = ? AND estado = 'en_proceso'",
                [$loteId, $hotelId]
            );

            $this->db->safeCommit();
        } catch (Throwable $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }
}
