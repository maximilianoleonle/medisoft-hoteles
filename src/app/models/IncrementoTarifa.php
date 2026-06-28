<?php
/**
 * Modelo de Incrementos de Tarifas
 */

class IncrementoTarifa extends Model {
    protected $table = 'incrementos_tarifas';
    protected $fillable = [
        'hotel_id',
        'nombre',
        'descripcion',
        'tipo_incremento',
        'clase',
        'valor_incremento',
        'alcance',
        'tipos_habitacion',
        'habitaciones',
        'es_permanente',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'prioridad',
        'usuario_id'
    ];

    private function hotelIdActual($hotelId = null) {
        if ($hotelId !== null && (int)$hotelId > 0) {
            return (int)$hotelId;
        }

        if (function_exists('obtenerHotelIdActualCompat')) {
            return (int)obtenerHotelIdActualCompat();
        }

        return (int)($_SESSION['hotel_id'] ?? 0);
    }

    private function hotelIdPorHabitacion($habitacionId) {
        $habitacionId = (int)$habitacionId;
        if ($habitacionId <= 0) {
            return 0;
        }

        $stmt = $this->db->query(
            "SELECT hotel_id FROM habitaciones WHERE id = ? LIMIT 1",
            [$habitacionId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return (int)($row['hotel_id'] ?? 0);
    }

    public function find($id, $columns = ['*']) {
        $hotelId = $this->hotelIdActual();
        if ($hotelId <= 0) {
            return parent::find($id, $columns);
        }

        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = ? AND hotel_id = ? LIMIT 1";
        $stmt = $this->db->query($sql, [$id, $hotelId]);
        return $stmt ? $stmt->fetch() : false;
    }

    public function create($data) {
        if (empty($data['hotel_id'])) {
            $hotelId = $this->hotelIdActual();
            if ($hotelId > 0) {
                $data['hotel_id'] = $hotelId;
            }
        }

        return parent::create($data);
    }

    public function update($id, $data) {
        $hotelId = $this->hotelIdActual();
        if ($hotelId <= 0) {
            return false;
        }

        $data = $this->filterFillable($data);
        unset($data['hotel_id']);

        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        if (empty($data)) {
            return false;
        }

        $fields = [];
        $values = [];

        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $values[] = $value;
        }

        $values[] = $id;
        $values[] = $hotelId;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) .
               " WHERE {$this->primaryKey} = ? AND hotel_id = ?";
        $stmt = $this->db->query($sql, $values);

        return $stmt ? $this->find($id) : false;
    }

    public function delete($id) {
        $hotelId = $this->hotelIdActual();
        if ($hotelId <= 0) {
            return false;
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ? AND hotel_id = ?";
        $stmt = $this->db->query($sql, [$id, $hotelId]);
        return $stmt !== false;
    }

    public function getActivosParaFecha($fecha, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return [];
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE hotel_id = ?
                AND activo = 1
                AND fecha_inicio <= ?
                AND (fecha_fin >= ? OR fecha_fin IS NULL OR es_permanente = 1)
                ORDER BY prioridad DESC, created_at DESC";

        $stmt = $this->db->query($sql, [$hotelId, $fecha, $fecha]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function getVigentes($hotelId = null) {
        return $this->getActivosParaFecha(date('Y-m-d'), $hotelId);
    }

    public function calcularPrecioConIncremento($habitacion_id, $tipo_habitacion, $precio_base, $fecha, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId ?: $this->hotelIdPorHabitacion($habitacion_id));
        $incrementos = $this->getIncrementosAplicables($habitacion_id, $tipo_habitacion, $fecha, $hotelId);

        $precio_final = $precio_base;
        $incrementos_aplicados = [];

        foreach ($incrementos as $incremento) {
            if ($incremento['tipo_incremento'] == 'porcentaje') {
                $aumento = $precio_base * ($incremento['valor_incremento'] / 100);
            } else {
                $aumento = $incremento['valor_incremento'];
            }

            $precio_final += $aumento;
            $incrementos_aplicados[] = [
                'id' => $incremento['id'],
                'nombre' => $incremento['nombre'],
                'tipo' => $incremento['tipo_incremento'],
                'valor' => $incremento['valor_incremento'],
                'aumento' => $aumento
            ];
        }

        return [
            'precio_base' => $precio_base,
            'precio_final' => $precio_final,
            'incremento_total' => $precio_final - $precio_base,
            'incrementos_aplicados' => $incrementos_aplicados
        ];
    }

    /**
     * Calcula el descuento por TIPO de habitacion (clase='descuento') sobre un precio dado.
     * Aplica registros con alcance global o por tipo de habitacion. Espejo de
     * calcularPrecioConIncremento, pero resta. El descuento total se topa al precio (no negativo).
     */
    public function calcularDescuentoPorTipo($tipo_habitacion, $precio, $fecha, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        $descuentos = $this->getIncrementosAplicables(0, $tipo_habitacion, $fecha, $hotelId, 'descuento');

        $descuento_total = 0;
        $descuentos_aplicados = [];

        foreach ($descuentos as $descuento) {
            if ($descuento['tipo_incremento'] == 'porcentaje') {
                $monto = $precio * ($descuento['valor_incremento'] / 100);
            } else {
                $monto = (float)$descuento['valor_incremento'];
            }

            $descuento_total += $monto;
            $descuentos_aplicados[] = [
                'id' => $descuento['id'],
                'nombre' => $descuento['nombre'],
                'tipo' => $descuento['tipo_incremento'],
                'valor' => $descuento['valor_incremento'],
                'monto' => $monto
            ];
        }

        if ($descuento_total > $precio) {
            $descuento_total = $precio;
        }

        return [
            'descuento_total' => $descuento_total,
            'descuentos_aplicados' => $descuentos_aplicados
        ];
    }

    private function getIncrementosAplicables($habitacion_id, $tipo_habitacion, $fecha, $hotelId = null, $clase = 'incremento') {
        $incrementos = $this->getActivosParaFecha($fecha, $hotelId);
        $aplicables = [];

        foreach ($incrementos as $inc) {
            // Separar incrementos de descuentos. Si la columna 'clase' aun no existe
            // (migracion no aplicada), se asume 'incremento' para preservar el comportamiento previo.
            $claseInc = $inc['clase'] ?? 'incremento';
            if ($claseInc !== $clase) {
                continue;
            }

            switch ($inc['alcance']) {
                case 'global':
                    $aplicables[] = $inc;
                    break;

                case 'tipo_habitacion':
                    $tipos = json_decode($inc['tipos_habitacion'], true) ?: [];
                    if (in_array($tipo_habitacion, $tipos)) {
                        $aplicables[] = $inc;
                    }
                    break;

                case 'habitacion':
                    $habitaciones = json_decode($inc['habitaciones'], true) ?: [];
                    if (in_array($habitacion_id, $habitaciones)) {
                        $aplicables[] = $inc;
                    }
                    break;
            }
        }

        return $aplicables;
    }

    public function verificarSolapamiento($fecha_inicio, $fecha_fin, $alcance, $elementos = [], $excluir_id = null, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return false;
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE hotel_id = ?
                AND activo = 1
                AND alcance = ?
                AND (
                    (fecha_inicio <= ? AND (fecha_fin >= ? OR fecha_fin IS NULL OR es_permanente = 1))
                    OR (fecha_inicio >= ? AND fecha_inicio <= ?)
                )";

        $fecha_fin_comparacion = $fecha_fin ?: '9999-12-31';
        $params = [$hotelId, $alcance, $fecha_fin_comparacion, $fecha_inicio, $fecha_inicio, $fecha_fin_comparacion];

        if ($excluir_id) {
            $sql .= " AND id != ?";
            $params[] = $excluir_id;
        }

        $stmt = $this->db->query($sql, $params);
        $solapamientos = $stmt ? $stmt->fetchAll() : [];

        foreach ($solapamientos as $solap) {
            if ($alcance == 'global') {
                return true;
            }

            if ($alcance == 'tipo_habitacion') {
                $tipos_existentes = json_decode($solap['tipos_habitacion'], true) ?: [];
                if (array_intersect($elementos, $tipos_existentes)) {
                    return true;
                }
            }

            if ($alcance == 'habitacion') {
                $habs_existentes = json_decode($solap['habitaciones'], true) ?: [];
                if (array_intersect($elementos, $habs_existentes)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAllConInfo($hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return [];
        }

        $sql = "SELECT it.*, u.nombre_completo as usuario_nombre
                FROM {$this->table} it
                LEFT JOIN usuarios u ON it.usuario_id = u.id
                WHERE it.hotel_id = ?
                ORDER BY it.prioridad DESC, it.created_at DESC";

        $stmt = $this->db->query($sql, [$hotelId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function getEstadisticas($hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return ['activos' => 0, 'vigentes' => 0, 'futuros' => 0];
        }

        $hoy = date('Y-m-d');

        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM {$this->table} WHERE hotel_id = ? AND activo = 1",
            [$hotelId]
        );
        $activos = (int)(($stmt ? $stmt->fetch() : [])['total'] ?? 0);

        $vigentes = count($this->getVigentes($hotelId));

        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM {$this->table} WHERE hotel_id = ? AND activo = 1 AND fecha_inicio > ?",
            [$hotelId, $hoy]
        );
        $futuros = (int)(($stmt ? $stmt->fetch() : [])['total'] ?? 0);

        return [
            'activos' => $activos,
            'vigentes' => $vigentes,
            'futuros' => $futuros
        ];
    }
}
