<?php
/**
 * Temporadas y eventos marcados por el hotel (tabla temporadas_hotel).
 *
 * Alimentan el forecast y el consejo de tarifa del Copiloto IA: "Feria del
 * pueblo, 15-20 mar, alta, se repite cada anio". Tenancy estricto: todas las
 * operaciones van amarradas al hotel actual.
 */

class TemporadaHotel extends Model {
    protected $table = 'temporadas_hotel';
    protected $fillable = [
        'hotel_id',
        'nombre',
        'desde',
        'hasta',
        'intensidad',
        'recurrente_anual',
        'notas'
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

    public function create($data) {
        if (empty($data['hotel_id'])) {
            $hotelId = $this->hotelIdActual();
            if ($hotelId > 0) {
                $data['hotel_id'] = $hotelId;
            }
        }
        return parent::create($data);
    }

    public function find($id, $columns = ['*']) {
        $hotelId = $this->hotelIdActual();
        if ($hotelId <= 0) {
            return false;
        }
        $columns = implode(', ', $columns);
        $stmt = $this->db->query(
            "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = ? AND hotel_id = ? LIMIT 1",
            [$id, $hotelId]
        );
        return $stmt ? $stmt->fetch() : false;
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

        $stmt = $this->db->query(
            "UPDATE {$this->table} SET " . implode(', ', $fields) .
            " WHERE {$this->primaryKey} = ? AND hotel_id = ?",
            $values
        );
        return $stmt ? $this->find($id) : false;
    }

    public function delete($id) {
        $hotelId = $this->hotelIdActual();
        if ($hotelId <= 0) {
            return false;
        }
        $stmt = $this->db->query(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ? AND hotel_id = ?",
            [$id, $hotelId]
        );
        return $stmt !== false;
    }

    /** Todas las temporadas del hotel, proximas primero (por mes-dia de inicio). */
    public function delHotel($hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return [];
        }
        $stmt = $this->db->query(
            "SELECT * FROM {$this->table} WHERE hotel_id = ? ORDER BY DATE_FORMAT(desde, '%m-%d') ASC, nombre ASC",
            [$hotelId]
        );
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Temporadas que tocan el rango [desde, hasta], con las recurrentes
     * anuales PROYECTADAS a fechas concretas de ese rango (una temporada
     * "15-20 mar recurrente" capturada en 2024 aparece como 15-20 mar del
     * anio del rango; las que cruzan fin de anio, p.ej. 15 dic - 10 ene,
     * tambien se resuelven bien). Devuelve filas con desde/hasta concretos.
     */
    public function enRango(string $desde, string $hasta, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0 || $desde > $hasta) {
            return [];
        }

        $resultado = [];
        foreach ($this->delHotel($hotelId) as $t) {
            foreach ($this->proyectar($t, $desde, $hasta) as $concreta) {
                $resultado[] = $concreta;
            }
        }

        usort($resultado, static function ($a, $b) {
            return strcmp($a['desde'], $b['desde']);
        });
        return $resultado;
    }

    /** Proyecta una temporada (recurrente o no) sobre [desde, hasta]. */
    private function proyectar(array $t, string $desde, string $hasta): array {
        if (empty($t['recurrente_anual'])) {
            return ($t['desde'] <= $hasta && $t['hasta'] >= $desde) ? [$t] : [];
        }

        $duracionDias = (int) ((strtotime($t['hasta']) - strtotime($t['desde'])) / 86400);
        $ocurrencias = [];

        // El rango consultado es corto (90 dias): probar el anio del inicio,
        // el anterior (por temporadas que cruzan fin de anio) y el siguiente.
        $anioBase = (int) substr($desde, 0, 4);
        for ($anio = $anioBase - 1; $anio <= (int) substr($hasta, 0, 4) + 1; $anio++) {
            $inicio = $this->fechaEnAnio($t['desde'], $anio);
            $fin = date('Y-m-d', strtotime($inicio . ' +' . max(0, $duracionDias) . ' days'));

            if ($inicio <= $hasta && $fin >= $desde) {
                $copia = $t;
                $copia['desde'] = $inicio;
                $copia['hasta'] = $fin;
                $ocurrencias[$inicio] = $copia; // clave = dedupe de anios repetidos
            }
        }

        return array_values($ocurrencias);
    }

    /** Mes-dia de $fecha en el anio $anio (29 feb cae a 28 feb si no es bisiesto). */
    private function fechaEnAnio(string $fecha, int $anio): string {
        $mes = (int) substr($fecha, 5, 2);
        $dia = (int) substr($fecha, 8, 2);
        if (!checkdate($mes, $dia, $anio)) {
            $dia = (int) date('t', mktime(12, 0, 0, $mes, 1, $anio));
        }
        return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
    }
}
