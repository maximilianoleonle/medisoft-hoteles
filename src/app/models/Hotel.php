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
}