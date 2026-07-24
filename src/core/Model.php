<?php
/**
 * Modelo Base
 * Los Cedros
 */

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /* ---------------------------------------------------------------------
     * Aislamiento multi-hotel automatico (auditoria de accesos, 23 jul 2026).
     *
     * Cuando la tabla del modelo tiene columna hotel_id Y hay un hotel activo
     * en contexto, los metodos por clave (find/update/delete) y de barrido
     * (all/paginate) filtran por ese hotel. Asi un id ajeno que llegue por la
     * URL no alcanza el registro de otro hotel aunque el controlador olvide
     * validarlo -- es la red que faltaba, no la unica defensa.
     *
     * SIN hotel en contexto (CLI, cron, panel SaaS interno) NO filtra: conserva
     * exactamente el comportamiento previo. Un modelo puede desactivarlo con
     * protected $aislarPorHotel = false; ademas la deteccion por columna ya
     * exime sola a las tablas globales (hoteles, modulos, planes, usuarios...).
     *
     * where()/first()/count() NO llevan scope automatico a proposito: reciben
     * condiciones arbitrarias y los modelos ya les pasan hotel_id donde toca.
     * El scope cubre el acceso-por-id, que es el vector que abre fuga en
     * silencio. Los modelos que ya blindan find/where a mano (Habitacion y
     * afines) no pasan por aqui: sobreescriben estos metodos.
     * ------------------------------------------------------------------- */
    protected $aislarPorHotel = true;
    protected $columnaHotel = 'hotel_id';

    /** Hotel activo por el que acotar, o null si no hay que acotar. */
    private function hotelParaAcotar() {
        if (!$this->aislarPorHotel || empty($this->table)) {
            return null;
        }
        if (!$this->tablaAceptaHotel()) {
            return null;
        }
        $hotelId = function_exists('obtenerHotelIdActualCompat') ? (int) obtenerHotelIdActualCompat() : 0;
        return $hotelId > 0 ? $hotelId : null;
    }

    /** ¿La tabla tiene la columna de hotel? Se resuelve una vez por tabla/request. */
    private function tablaAceptaHotel() {
        static $cache = [];
        $t = (string) $this->table;
        if (array_key_exists($t, $cache)) {
            return $cache[$t];
        }
        try {
            // information_schema SÍ acepta parametros preparados (SHOW COLUMNS
            // LIKE ? no: da error de sintaxis). DATABASE() es la BD activa de la
            // conexion (la del hotel), no una global.
            $stmt = $this->db->query(
                "SELECT 1 FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
                 LIMIT 1",
                [$t, $this->columnaHotel]
            );
            return $cache[$t] = ($stmt && $stmt->fetch()) ? true : false;
        } catch (Throwable $e) {
            // Ante cualquier duda, no acotar: esta red nunca debe romper una query.
            return $cache[$t] = false;
        }
    }

    /**
     * Obtener todos los registros (acotado al hotel activo si aplica).
     */
    public function all($columns = ['*']) {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table}";
        $params = [];

        $hotelId = $this->hotelParaAcotar();
        if ($hotelId !== null) {
            $sql .= " WHERE {$this->columnaHotel} = ?";
            $params[] = $hotelId;
        }

        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Buscar por ID (acotado al hotel activo si aplica).
     */
    public function find($id, $columns = ['*']) {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $params = [$id];

        $hotelId = $this->hotelParaAcotar();
        if ($hotelId !== null) {
            $sql .= " AND {$this->columnaHotel} = ?";
            $params[] = $hotelId;
        }

        $stmt = $this->db->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Buscar por condiciones
     */
    public function where($conditions, $columns = ['*']) {
        $columns = implode(', ', $columns);
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $operator = $value[0];
                $val = $value[1];
                $where[] = "{$field} {$operator} ?";
                $params[] = $val;
            } else {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
        }
        
        $whereClause = implode(' AND ', $where);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$whereClause}";
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar el primer registro
     */
    public function first($conditions, $columns = ['*']) {
        $results = $this->where($conditions, $columns);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
 * Crear nuevo registro
 */
public function create($data) {
    // Filtrar solo campos permitidos
    $data = $this->filterFillable($data);
    
    // Agregar timestamps si están habilitados
    if ($this->timestamps) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
    }
    
    $fields = array_keys($data);
    $values = array_values($data);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $this->db->query($sql, $values);
    
    if ($stmt) {
        // Retornar el ID insertado directamente
        return $this->db->lastInsertId();
    }
    
    return false;
}
    
    /**
     * Actualizar registro
     */
    public function update($id, $data) {
        // Filtrar solo campos permitidos
        $data = $this->filterFillable($data);
        
        // Actualizar timestamp
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $values[] = $value;
        }
        
        $values[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) .
               " WHERE {$this->primaryKey} = ?";

        // Aislamiento: no dejar tocar el registro de otro hotel aunque el id
        // llegue de fuera. Sin hotel en contexto, no acota (comportamiento previo).
        $hotelId = $this->hotelParaAcotar();
        if ($hotelId !== null) {
            $sql .= " AND {$this->columnaHotel} = ?";
            $values[] = $hotelId;
        }

        $stmt = $this->db->query($sql, $values);

        if ($stmt) {
            return $this->find($id);
        }

        return false;
    }

    /**
     * Eliminar registro
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $params = [$id];

        $hotelId = $this->hotelParaAcotar();
        if ($hotelId !== null) {
            $sql .= " AND {$this->columnaHotel} = ?";
            $params[] = $hotelId;
        }

        $stmt = $this->db->query($sql, $params);
        return $stmt !== false;
    }
    
    /**
     * Contar registros
     */
    public function count($conditions = []) {
        if (empty($conditions)) {
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            $stmt = $this->db->query($sql);
        } else {
            $where = [];
            $params = [];
            
            foreach ($conditions as $field => $value) {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $whereClause = implode(' AND ', $where);
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
            $stmt = $this->db->query($sql, $params);
        }
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Verificar si existe
     */
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    /**
     * Paginar resultados
     */
    public function paginate($perPage = 15, $page = 1, $conditions = []) {
        $offset = ($page - 1) * $perPage;

        // Aislamiento: acotar al hotel activo salvo que quien llama ya fije
        // hotel_id. Se inyecta en las condiciones para que el conteo y el
        // listado usen el MISMO filtro.
        $hotelId = $this->hotelParaAcotar();
        if ($hotelId !== null && !array_key_exists($this->columnaHotel, $conditions)) {
            $conditions[$this->columnaHotel] = $hotelId;
        }

        // Obtener total
        $total = $this->count($conditions);
        
        // Construir query
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->db->query($sql, $params);
        $data = $stmt->fetchAll();
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    /**
     * Ejecutar query personalizado
     */
    /**
 * Ejecutar query personalizado
 */
public function query($sql, $params = []) {
    $stmt = $this->db->query($sql, $params);
    
    // Verificar si la consulta fue exitosa
    if ($stmt === false) {
        return [];
    }
    
    // Si es una consulta SELECT, retornar los resultados
    if (stripos(trim($sql), 'SELECT') === 0) {
        return $stmt->fetchAll();
    }
    
    // Para otras consultas (INSERT, UPDATE, DELETE), retornar el statement
    return $stmt;
}
    
    /**
     * Obtener query builder
     */
    public function getConnection() {
        return $this->db->getConnection();
    }
    
    /**
     * Filtrar solo campos fillable
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Ordenar resultados
     */
    public function orderBy($field, $direction = 'ASC') {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$field} {$direction}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar con LIKE
     */
    public function like($field, $value, $position = 'both') {
        switch ($position) {
            case 'start':
                $value = $value . '%';
                break;
            case 'end':
                $value = '%' . $value;
                break;
            case 'both':
            default:
                $value = '%' . $value . '%';
                break;
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE {$field} LIKE ?";
        $stmt = $this->db->query($sql, [$value]);
        return $stmt->fetchAll();
    }
}