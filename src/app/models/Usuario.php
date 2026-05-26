<?php
/**
 * Modelo de Usuario
 * Los Cedros
 */

class Usuario extends Model {
    protected $table = 'usuarios';
    protected $fillable = [
        'nombre_usuario',
        'password',
        'nombre_completo',
        'email',
        'telefono',
        'rol',
        'activo'
    ];
    
    /**
     * Buscar usuario por nombre de usuario
     */
    public function findByUsername($username) {
        return $this->first(['nombre_usuario' => $username]);
    }
    
    /**
     * Obtener usuarios activos
     */
    public function activos() {
        return $this->where(['activo' => 1]);
    }
    
    /**
     * Obtener usuarios por rol
     */
    public function porRol($rol) {
        return $this->where(['rol' => $rol]);
    }
    
    /**
     * Crear usuario con password hasheado
     */
    public function crearUsuario($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->create($data);
    }
    
    /**
     * Actualizar usuario
     */
    public function actualizarUsuario($id, $data) {
        // Si se está actualizando la contraseña, hashearla
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Si no se envió contraseña, no actualizarla
            unset($data['password']);
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Verificar si un nombre de usuario existe
     */
    public function usernameExiste($username, $exceptId = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE nombre_usuario = ?";
        $params = [$username];
        
        if ($exceptId) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result['total'] > 0;
    }
    
    /**
     * Actualizar último login
     */
    public function actualizarUltimoLogin($id) {
        $sql = "UPDATE {$this->table} SET ultimo_login = NOW(), ip_ultimo_login = ? WHERE id = ?";
        return $this->db->query($sql, [get_client_ip(), $id]);
    }
    
    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleActivo($id) {
        $sql = "UPDATE {$this->table} SET activo = NOT activo WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
}