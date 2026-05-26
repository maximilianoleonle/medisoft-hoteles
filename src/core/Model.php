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
    
    /**
     * Obtener todos los registros
     */
    public function all($columns = ['*']) {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar por ID
     */
    public function find($id, $columns = ['*']) {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->query($sql, [$id]);
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
        $stmt = $this->db->query($sql, [$id]);
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