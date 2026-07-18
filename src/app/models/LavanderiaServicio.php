<?php
/**
 * Catalogo de servicios/precios de lavanderia de huesped por hotel
 * (camisa lavada, planchado, tintoreria...). Alimenta el datalist del
 * formulario de pedidos; las partidas del pedido congelan su precio.
 */
class LavanderiaServicio extends Model {
    protected $table = 'lavanderia_servicios';
    protected $fillable = [
        'hotel_id',
        'nombre',
        'precio',
        'activo',
    ];

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
        $sql .= " ORDER BY activo DESC, nombre ASC";

        $stmt = $this->db->query($sql, [$hotelId]);
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function guardar(array $datos, ?int $hotelId = null): int {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($hotelId <= 0) {
            throw new InvalidArgumentException('Hotel no valido.');
        }

        $nombre = trim((string)($datos['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            throw new InvalidArgumentException('El nombre del servicio es obligatorio (maximo 120 caracteres).');
        }

        $precio = round((float)($datos['precio'] ?? 0), 2);
        if ($precio < 0 || $precio > 999999) {
            throw new InvalidArgumentException('El precio del servicio no es valido.');
        }

        // Upsert amable por nombre: si ya existe, actualiza precio y reactiva.
        $stmt = $this->db->query(
            "SELECT id FROM {$this->table} WHERE hotel_id = ? AND nombre = ? LIMIT 1",
            [$hotelId, $nombre]
        );
        $existente = $stmt ? $stmt->fetch() : null;

        if ($existente) {
            $this->db->query(
                "UPDATE {$this->table} SET precio = ?, activo = 1, updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?",
                [$precio, (int)$existente['id'], $hotelId]
            );
            return (int)$existente['id'];
        }

        return (int)$this->create([
            'hotel_id' => $hotelId,
            'nombre' => $nombre,
            'precio' => $precio,
            'activo' => 1,
        ]);
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
}
