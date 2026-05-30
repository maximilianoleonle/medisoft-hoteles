<?php
/**
 * Modelo Hotel
 */

class Hotel extends Model {
    protected $table = 'hoteles';
    protected $fillable = [
        'nombre',
        'slug',
        'codigo',
        'razon_social',
        'rfc',
        'telefono',
        'email',
        'direccion',
        'ciudad',
        'estado',
        'pais',
        'zona_horaria',
        'moneda_codigo',
        'moneda_simbolo',
        'activo'
    ];

    /**
     * Listado read-only para el Panel Medisoft interno SaaS.
     */
    public function listarParaSaasAdmin() {
        return $this->query(
            "SELECT id, nombre, slug, codigo, telefono, email, ciudad, estado, activo, moneda_codigo, created_at, updated_at
             FROM {$this->table}
             ORDER BY id ASC"
        );
    }

    public function obtenerParaSaasAdmin($id) {
        $resultado = $this->query(
            "SELECT id, nombre, slug, codigo, razon_social, rfc, telefono, email, direccion,
                    ciudad, estado, pais, zona_horaria, moneda_codigo, moneda_simbolo,
                    activo, metadata, created_at, updated_at
             FROM {$this->table}
             WHERE id = ?
             LIMIT 1",
            [(int) $id]
        );

        return $resultado[0] ?? null;
    }

    public function slugExiste($slug, $exceptoId = null) {
        $params = [$slug];
        $sql = "SELECT id FROM {$this->table} WHERE slug = ?";

        if ($exceptoId) {
            $sql .= " AND id != ?";
            $params[] = (int) $exceptoId;
        }

        $sql .= " LIMIT 1";
        $resultado = $this->query($sql, $params);

        return !empty($resultado);
    }

    public function codigoExiste($codigo, $exceptoId = null) {
        if ($codigo === null || $codigo === '') {
            return false;
        }

        $params = [$codigo];
        $sql = "SELECT id FROM {$this->table} WHERE codigo = ?";

        if ($exceptoId) {
            $sql .= " AND id != ?";
            $params[] = (int) $exceptoId;
        }

        $sql .= " LIMIT 1";
        $resultado = $this->query($sql, $params);

        return !empty($resultado);
    }

    public function crearParaSaasAdmin(array $data) {
        return $this->create($data);
    }

    public function actualizarParaSaasAdmin($id, array $data) {
        return $this->update((int) $id, $data);
    }

    public function actualizarEstadoParaSaasAdmin($id, $activo) {
        return $this->update((int) $id, [
            'activo' => $activo ? 1 : 0
        ]);
    }

    public function listarUsuariosParaSaasAdmin($hotelId) {
        return $this->query(
            "SELECT hu.id AS hotel_usuario_id,
                    hu.hotel_id,
                    hu.usuario_id,
                    hu.rol AS rol_hotel,
                    hu.es_principal,
                    hu.activo AS hotel_usuario_activo,
                    hu.created_at AS vinculado_en,
                    u.nombre_usuario,
                    u.nombre_completo,
                    u.email,
                    u.rol AS rol_global,
                    u.activo AS usuario_activo
             FROM hotel_usuarios hu
             INNER JOIN usuarios u ON u.id = hu.usuario_id
             WHERE hu.hotel_id = ?
             ORDER BY hu.es_principal DESC, u.nombre_completo ASC, u.nombre_usuario ASC",
            [(int) $hotelId]
        );
    }

    public function usuarioVinculado($hotelId, $usuarioId) {
        $resultado = $this->query(
            "SELECT id
             FROM hotel_usuarios
             WHERE hotel_id = ? AND usuario_id = ?
             LIMIT 1",
            [(int) $hotelId, (int) $usuarioId]
        );

        return !empty($resultado);
    }

    public function vincularUsuarioParaSaasAdmin($hotelId, $usuarioId, $rol, $esPrincipal = false, $activo = true) {
        if ($esPrincipal) {
            $this->query(
                "UPDATE hotel_usuarios
                 SET es_principal = 0, updated_at = NOW()
                 WHERE hotel_id = ?",
                [(int) $hotelId]
            );
        }

        $stmt = $this->db->query(
            "INSERT INTO hotel_usuarios
                (hotel_id, usuario_id, rol, es_principal, activo, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
            [
                (int) $hotelId,
                (int) $usuarioId,
                $rol,
                $esPrincipal ? 1 : 0,
                $activo ? 1 : 0
            ]
        );

        return $stmt !== false;
    }
}
