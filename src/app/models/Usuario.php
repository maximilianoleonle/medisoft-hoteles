<?php
/**
 * Modelo de Usuario
 * Sistema hotelero
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
     * Buscar usuario por nombre de usuario para flujos SaaS.
     */
    public function buscarPorNombreUsuario($username) {
        $resultado = $this->query(
            "SELECT id, nombre_usuario, nombre_completo, email, rol, activo
             FROM {$this->table}
             WHERE nombre_usuario = ?
             LIMIT 1",
            [$username]
        );

        return $resultado[0] ?? null;
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

    public function listarTrabajadoresHotel($hotelId) {
        return $this->query(
            "SELECT u.id,
                    u.nombre_usuario,
                    u.nombre_completo,
                    u.email,
                    u.telefono,
                    hu.rol AS rol,
                    u.rol AS rol_global,
                    hu.activo AS activo,
                    u.activo AS usuario_activo,
                    hu.es_principal,
                    hu.created_at,
                    u.ultimo_login,
                    u.ip_ultimo_login
             FROM hotel_usuarios hu
             INNER JOIN {$this->table} u ON u.id = hu.usuario_id
             WHERE hu.hotel_id = ?
             ORDER BY hu.rol ASC, u.nombre_completo ASC, u.nombre_usuario ASC",
            [(int) $hotelId]
        );
    }

    public function findTrabajadorHotel($hotelId, $usuarioId) {
        $resultado = $this->query(
            "SELECT u.id,
                    u.nombre_usuario,
                    u.nombre_completo,
                    u.email,
                    u.telefono,
                    hu.rol AS rol,
                    u.rol AS rol_global,
                    hu.activo AS activo,
                    u.activo AS usuario_activo,
                    hu.es_principal,
                    hu.created_at,
                    u.ultimo_login,
                    u.ip_ultimo_login
             FROM hotel_usuarios hu
             INNER JOIN {$this->table} u ON u.id = hu.usuario_id
             WHERE hu.hotel_id = ? AND hu.usuario_id = ?
             LIMIT 1",
            [(int) $hotelId, (int) $usuarioId]
        );

        return $resultado[0] ?? null;
    }

    public function vincularAHotel($hotelId, $usuarioId, $rol, $activo = true) {
        $stmt = $this->db->query(
            "INSERT INTO hotel_usuarios
                (hotel_id, usuario_id, rol, es_principal, activo, created_at, updated_at)
             VALUES (?, ?, ?, 0, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                rol = VALUES(rol),
                activo = VALUES(activo),
                updated_at = NOW()",
            [(int) $hotelId, (int) $usuarioId, $rol, $activo ? 1 : 0]
        );

        return $stmt !== false;
    }

    public function actualizarRolHotel($hotelId, $usuarioId, $rol) {
        $stmt = $this->db->query(
            "UPDATE hotel_usuarios
             SET rol = ?, updated_at = NOW()
             WHERE hotel_id = ? AND usuario_id = ?",
            [$rol, (int) $hotelId, (int) $usuarioId]
        );

        return $stmt !== false;
    }

    public function toggleActivoHotel($hotelId, $usuarioId) {
        $stmt = $this->db->query(
            "UPDATE hotel_usuarios
             SET activo = NOT activo, updated_at = NOW()
             WHERE hotel_id = ? AND usuario_id = ?",
            [(int) $hotelId, (int) $usuarioId]
        );

        return $stmt !== false;
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
