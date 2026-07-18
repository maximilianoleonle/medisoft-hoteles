<?php
/**
 * Blancos del hotel (modulo lavanderia): catalogo con stock por estado
 * (limpio / sucio / en lavado) y ledger inmutable en lavanderia_movimientos.
 *
 * Invariante: el stock por estado SOLO se muta bajo FOR UPDATE de la fila
 * del blanco y dentro de una transaccion, con la cantidad validada contra
 * el estado de origen (nunca queda stock negativo). Los movimientos de
 * lote (envio/retorno/merma) los aplica LavanderiaLote dentro de SU
 * transaccion via los metodos *EnTransaccion de este modelo.
 */
class LavanderiaBlanco extends Model {
    protected $table = 'lavanderia_blancos';
    protected $fillable = [
        'hotel_id',
        'nombre',
        'categoria',
        'stock_limpio',
        'stock_sucio',
        'stock_proceso',
        'stock_minimo',
        'activo',
        'notas',
    ];

    // Movimientos manuales permitidos desde la pantalla (los de lote van aparte).
    public const TIPOS_MANUALES = ['compra', 'uso', 'baja_limpio', 'baja_sucio'];

    public static function catalogoCategorias(): array {
        return [
            'cama'     => ['label' => 'Ropa de cama',   'icono' => 'fa-bed'],
            'bano'     => ['label' => 'Baño',           'icono' => 'fa-bath'],
            'comedor'  => ['label' => 'Comedor',        'icono' => 'fa-utensils'],
            'uniforme' => ['label' => 'Uniformes',      'icono' => 'fa-user-tie'],
            'otro'     => ['label' => 'Otro',           'icono' => 'fa-layer-group'],
        ];
    }

    public static function categoriaValida(string $categoria): bool {
        return array_key_exists($categoria, self::catalogoCategorias());
    }

    public static function catalogoTiposManuales(): array {
        return [
            'compra'      => 'Alta de piezas (compra o reposición)',
            'uso'         => 'Se ensució (limpio → sucio)',
            'baja_limpio' => 'Baja definitiva desde limpio',
            'baja_sucio'  => 'Baja definitiva desde sucio',
        ];
    }

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    public function listar(?int $hotelId = null, bool $soloActivos = false): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($hotelId <= 0) {
            return [];
        }

        $sql = "SELECT * FROM {$this->table} WHERE hotel_id = ?";
        if ($soloActivos) {
            $sql .= " AND activo = 1";
        }
        $sql .= " ORDER BY activo DESC, categoria ASC, nombre ASC";

        $stmt = $this->db->query($sql, [$hotelId]);
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function obtenerPorId(int $id, ?int $hotelId = null): ?array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($id <= 0 || $hotelId <= 0) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT * FROM {$this->table} WHERE id = ? AND hotel_id = ? LIMIT 1",
            [$id, $hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    /**
     * Totales de stock del hotel + cuantos blancos activos andan bajo minimo.
     */
    public function resumenStock(?int $hotelId = null): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        $base = ['limpio' => 0, 'sucio' => 0, 'proceso' => 0, 'bajo_minimo' => 0, 'blancos' => 0];
        if ($hotelId <= 0) {
            return $base;
        }

        $stmt = $this->db->query(
            "SELECT
                COALESCE(SUM(stock_limpio), 0) AS limpio,
                COALESCE(SUM(stock_sucio), 0) AS sucio,
                COALESCE(SUM(stock_proceso), 0) AS proceso,
                COALESCE(SUM(CASE WHEN stock_minimo > 0 AND stock_limpio < stock_minimo THEN 1 ELSE 0 END), 0) AS bajo_minimo,
                COUNT(*) AS blancos
             FROM {$this->table}
             WHERE hotel_id = ? AND activo = 1",
            [$hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        if (!$row) {
            return $base;
        }

        return [
            'limpio' => (int)$row['limpio'],
            'sucio' => (int)$row['sucio'],
            'proceso' => (int)$row['proceso'],
            'bajo_minimo' => (int)$row['bajo_minimo'],
            'blancos' => (int)$row['blancos'],
        ];
    }

    /**
     * Alta o edicion (hidden id, patron areas). La edicion NO toca los
     * stocks: esos solo se mueven por movimientos.
     */
    public function guardar(array $datos, ?int $id = null, ?int $hotelId = null): int {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($hotelId <= 0) {
            throw new InvalidArgumentException('Hotel no valido.');
        }

        $nombre = trim((string)($datos['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            throw new InvalidArgumentException('El nombre del blanco es obligatorio (maximo 120 caracteres).');
        }

        $categoria = (string)($datos['categoria'] ?? 'otro');
        if (!self::categoriaValida($categoria)) {
            $categoria = 'otro';
        }

        $stockMinimo = max(0, (int)($datos['stock_minimo'] ?? 0));
        $notas = trim((string)($datos['notas'] ?? ''));
        $notas = $notas === '' ? null : mb_substr($notas, 0, 300);

        // Duplicado por nombre (el UNIQUE lo respalda; el mensaje amable va aqui).
        $stmt = $this->db->query(
            "SELECT id FROM {$this->table} WHERE hotel_id = ? AND nombre = ? AND id <> ? LIMIT 1",
            [$hotelId, $nombre, (int)$id]
        );
        if ($stmt && $stmt->fetch()) {
            throw new InvalidArgumentException('Ya existe un blanco con ese nombre.');
        }

        if ($id) {
            $existente = $this->obtenerPorId((int)$id, $hotelId);
            if (!$existente) {
                throw new InvalidArgumentException('Blanco no encontrado para el hotel actual.');
            }
            $this->db->query(
                "UPDATE {$this->table}
                 SET nombre = ?, categoria = ?, stock_minimo = ?, notas = ?, updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?",
                [$nombre, $categoria, $stockMinimo, $notas, (int)$id, $hotelId]
            );
            return (int)$id;
        }

        $stockInicial = max(0, (int)($datos['stock_limpio'] ?? 0));
        $nuevoId = (int)$this->create([
            'hotel_id' => $hotelId,
            'nombre' => $nombre,
            'categoria' => $categoria,
            'stock_limpio' => $stockInicial,
            'stock_sucio' => 0,
            'stock_proceso' => 0,
            'stock_minimo' => $stockMinimo,
            'activo' => 1,
            'notas' => $notas,
        ]);

        if ($nuevoId > 0 && $stockInicial > 0) {
            $this->insertarMovimiento($hotelId, $nuevoId, 'compra', $stockInicial, null, (int)($datos['usuario_id'] ?? 0) ?: null, 'Stock inicial al dar de alta');
        }

        return $nuevoId;
    }

    public function toggle(int $id, ?int $hotelId = null): bool {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($id <= 0 || $hotelId <= 0) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE {$this->table} SET activo = IF(activo = 1, 0, 1), updated_at = NOW()
             WHERE id = ? AND hotel_id = ?",
            [$id, $hotelId]
        );
        return (bool)$stmt;
    }

    /**
     * Movimiento manual de stock (compra / uso / baja_*). Atomico: FOR UPDATE
     * del blanco + validacion de stock del estado de origen + ledger.
     */
    public function registrarMovimiento(int $hotelId, int $blancoId, string $tipo, int $cantidad, ?int $usuarioId = null, string $notas = ''): array {
        if ($hotelId <= 0 || $blancoId <= 0) {
            throw new InvalidArgumentException('Blanco no valido.');
        }
        if (!in_array($tipo, self::TIPOS_MANUALES, true)) {
            throw new InvalidArgumentException('Tipo de movimiento no valido.');
        }
        if ($cantidad <= 0 || $cantidad > 100000) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $this->db->safeBeginTransaction();
        try {
            $stmt = $this->db->query(
                "SELECT * FROM {$this->table} WHERE id = ? AND hotel_id = ? AND activo = 1 LIMIT 1 FOR UPDATE",
                [$blancoId, $hotelId]
            );
            $blanco = $stmt ? $stmt->fetch() : null;
            if (!$blanco) {
                throw new RuntimeException('Blanco no encontrado o pausado para el hotel actual.');
            }

            switch ($tipo) {
                case 'compra':
                    $this->db->query(
                        "UPDATE {$this->table} SET stock_limpio = stock_limpio + ?, updated_at = NOW()
                         WHERE id = ? AND hotel_id = ?",
                        [$cantidad, $blancoId, $hotelId]
                    );
                    break;
                case 'uso':
                    if ((int)$blanco['stock_limpio'] < $cantidad) {
                        throw new RuntimeException('No hay suficientes piezas limpias (' . (int)$blanco['stock_limpio'] . ' disponibles).');
                    }
                    $this->db->query(
                        "UPDATE {$this->table} SET stock_limpio = stock_limpio - ?, stock_sucio = stock_sucio + ?, updated_at = NOW()
                         WHERE id = ? AND hotel_id = ?",
                        [$cantidad, $cantidad, $blancoId, $hotelId]
                    );
                    break;
                case 'baja_limpio':
                    if ((int)$blanco['stock_limpio'] < $cantidad) {
                        throw new RuntimeException('No hay suficientes piezas limpias para dar de baja.');
                    }
                    $this->db->query(
                        "UPDATE {$this->table} SET stock_limpio = stock_limpio - ?, updated_at = NOW()
                         WHERE id = ? AND hotel_id = ?",
                        [$cantidad, $blancoId, $hotelId]
                    );
                    break;
                case 'baja_sucio':
                    if ((int)$blanco['stock_sucio'] < $cantidad) {
                        throw new RuntimeException('No hay suficientes piezas sucias para dar de baja.');
                    }
                    $this->db->query(
                        "UPDATE {$this->table} SET stock_sucio = stock_sucio - ?, updated_at = NOW()
                         WHERE id = ? AND hotel_id = ?",
                        [$cantidad, $blancoId, $hotelId]
                    );
                    break;
            }

            $tipoLedger = in_array($tipo, ['baja_limpio', 'baja_sucio'], true) ? 'baja' : $tipo;
            $notaLedger = trim($notas);
            if ($tipo === 'baja_limpio') {
                $notaLedger = trim('Desde limpio. ' . $notaLedger);
            } elseif ($tipo === 'baja_sucio') {
                $notaLedger = trim('Desde sucio. ' . $notaLedger);
            }
            $this->insertarMovimiento($hotelId, $blancoId, $tipoLedger, $cantidad, null, $usuarioId, $notaLedger);

            $this->db->safeCommit();
            return ['success' => true, 'blanco' => (string)$blanco['nombre']];
        } catch (Throwable $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    /**
     * Ultimos movimientos del hotel (bitacora del panel).
     */
    public function movimientosRecientes(?int $hotelId = null, int $limite = 12): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($hotelId <= 0) {
            return [];
        }
        $limite = max(1, min(100, $limite));

        $stmt = $this->db->query(
            "SELECT m.*, b.nombre AS blanco_nombre, u.nombre_completo AS usuario_nombre
             FROM lavanderia_movimientos m
             INNER JOIN {$this->table} b
                ON b.id = m.blanco_id
               AND b.hotel_id = m.hotel_id
             LEFT JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.hotel_id = ?
             ORDER BY m.id DESC
             LIMIT {$limite}",
            [$hotelId]
        );
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    /* --------------------------------------------------------------------
     * Ayudas para LavanderiaLote: corren DENTRO de la transaccion del lote.
     * ------------------------------------------------------------------- */

    /**
     * sucio -> en lavado. Exige transaccion abierta por el caller.
     */
    public function enviarALavadoEnTransaccion(int $hotelId, int $blancoId, int $cantidad, int $loteId, ?int $usuarioId): string {
        $stmt = $this->db->query(
            "SELECT id, nombre, stock_sucio FROM {$this->table}
             WHERE id = ? AND hotel_id = ? AND activo = 1 LIMIT 1 FOR UPDATE",
            [$blancoId, $hotelId]
        );
        $blanco = $stmt ? $stmt->fetch() : null;
        if (!$blanco) {
            throw new RuntimeException('Blanco no encontrado o pausado para el hotel actual.');
        }
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad a enviar debe ser mayor a cero.');
        }
        if ((int)$blanco['stock_sucio'] < $cantidad) {
            throw new RuntimeException('"' . $blanco['nombre'] . '" solo tiene ' . (int)$blanco['stock_sucio'] . ' piezas sucias.');
        }

        $this->db->query(
            "UPDATE {$this->table} SET stock_sucio = stock_sucio - ?, stock_proceso = stock_proceso + ?, updated_at = NOW()
             WHERE id = ? AND hotel_id = ?",
            [$cantidad, $cantidad, $blancoId, $hotelId]
        );
        $this->insertarMovimiento($hotelId, $blancoId, 'envio', $cantidad, $loteId, $usuarioId, 'Enviado a lavado (lote #' . $loteId . ')');

        return (string)$blanco['nombre'];
    }

    /**
     * en lavado -> limpio (recibida) + merma (baja definitiva). Exige
     * transaccion abierta por el caller.
     */
    public function recibirDeLavadoEnTransaccion(int $hotelId, int $blancoId, int $enviada, int $recibida, int $loteId, ?int $usuarioId): void {
        $stmt = $this->db->query(
            "SELECT id, nombre, stock_proceso FROM {$this->table}
             WHERE id = ? AND hotel_id = ? LIMIT 1 FOR UPDATE",
            [$blancoId, $hotelId]
        );
        $blanco = $stmt ? $stmt->fetch() : null;
        if (!$blanco) {
            throw new RuntimeException('Blanco no encontrado para el hotel actual.');
        }
        if ((int)$blanco['stock_proceso'] < $enviada) {
            throw new RuntimeException('"' . $blanco['nombre'] . '" no tiene ' . $enviada . ' piezas en lavado (ajuste manual de por medio).');
        }

        $merma = $enviada - $recibida;
        $this->db->query(
            "UPDATE {$this->table} SET stock_proceso = stock_proceso - ?, stock_limpio = stock_limpio + ?, updated_at = NOW()
             WHERE id = ? AND hotel_id = ?",
            [$enviada, $recibida, $blancoId, $hotelId]
        );

        if ($recibida > 0) {
            $this->insertarMovimiento($hotelId, $blancoId, 'retorno', $recibida, $loteId, $usuarioId, 'Recibido limpio (lote #' . $loteId . ')');
        }
        if ($merma > 0) {
            $this->insertarMovimiento($hotelId, $blancoId, 'merma', $merma, $loteId, $usuarioId, 'Merma al recibir el lote #' . $loteId);
        }
    }

    /**
     * en lavado -> sucio (cancelacion de lote). Exige transaccion del caller.
     */
    public function devolverASucioEnTransaccion(int $hotelId, int $blancoId, int $cantidad, int $loteId, ?int $usuarioId): void {
        $stmt = $this->db->query(
            "SELECT id, nombre, stock_proceso FROM {$this->table}
             WHERE id = ? AND hotel_id = ? LIMIT 1 FOR UPDATE",
            [$blancoId, $hotelId]
        );
        $blanco = $stmt ? $stmt->fetch() : null;
        if (!$blanco) {
            throw new RuntimeException('Blanco no encontrado para el hotel actual.');
        }
        if ((int)$blanco['stock_proceso'] < $cantidad) {
            throw new RuntimeException('"' . $blanco['nombre'] . '" no tiene ' . $cantidad . ' piezas en lavado que devolver.');
        }

        $this->db->query(
            "UPDATE {$this->table} SET stock_proceso = stock_proceso - ?, stock_sucio = stock_sucio + ?, updated_at = NOW()
             WHERE id = ? AND hotel_id = ?",
            [$cantidad, $cantidad, $blancoId, $hotelId]
        );
        $this->insertarMovimiento($hotelId, $blancoId, 'ajuste', $cantidad, $loteId, $usuarioId, 'Lote #' . $loteId . ' cancelado: piezas devueltas a sucio');
    }

    private function insertarMovimiento(int $hotelId, int $blancoId, string $tipo, int $cantidad, ?int $loteId, ?int $usuarioId, string $notas = ''): void {
        $stmt = $this->db->query(
            "INSERT INTO lavanderia_movimientos
                (hotel_id, blanco_id, tipo, cantidad, lote_id, usuario_id, notas, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [$hotelId, $blancoId, $tipo, $cantidad, $loteId, $usuarioId ?: null, $notas !== '' ? mb_substr($notas, 0, 300) : null]
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo registrar el movimiento de blancos.');
        }
    }
}
