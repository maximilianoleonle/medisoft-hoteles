<?php
/**
 * Modelo para planes comerciales SaaS y presets de modulos.
 */

class Plan extends Model {
    protected $table = 'planes';
    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'activo',
        'orden',
        'precio_mensual',
        'moneda_codigo',
        'metadata'
    ];

    public function listarActivos() {
        try {
            return $this->query(
                "SELECT id, clave, nombre, descripcion, activo, orden, precio_mensual, moneda_codigo
                 FROM {$this->table}
                 WHERE activo = 1
                 ORDER BY orden ASC, nombre ASC"
            );
        } catch (Throwable $e) {
            error_log('Error al listar planes SaaS: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($planId) {
        try {
            $resultado = $this->query(
                "SELECT id, clave, nombre, descripcion, activo, orden, precio_mensual, moneda_codigo
                 FROM {$this->table}
                 WHERE id = ?
                 LIMIT 1",
                [(int) $planId]
            );

            return $resultado[0] ?? null;
        } catch (Throwable $e) {
            error_log('Error al obtener plan SaaS: ' . $e->getMessage());
            return null;
        }
    }

    public function obtenerActualDeHotel($hotelId) {
        try {
            $resultado = $this->query(
                "SELECT p.id, p.clave, p.nombre, p.descripcion, p.activo, p.orden, p.precio_mensual, p.moneda_codigo
                 FROM hoteles h
                 LEFT JOIN {$this->table} p ON p.id = h.plan_id
                 WHERE h.id = ?
                 LIMIT 1",
                [(int) $hotelId]
            );

            return $resultado[0] ?? null;
        } catch (Throwable $e) {
            error_log('Error al obtener plan actual del hotel: ' . $e->getMessage());
            return null;
        }
    }

    public function listarModulosDelPlan($planId) {
        try {
            return $this->query(
                "SELECT m.id,
                        m.clave,
                        m.nombre,
                        m.descripcion,
                        m.categoria,
                        m.activo_global,
                        m.orden,
                        m.icono,
                        m.ruta_base,
                        pm.incluido
                 FROM plan_modulos pm
                 INNER JOIN modulos m ON m.id = pm.modulo_id
                 WHERE pm.plan_id = ?
                   AND pm.incluido = 1
                   AND m.activo_global = 1
                 ORDER BY m.orden ASC, m.nombre ASC",
                [(int) $planId]
            );
        } catch (Throwable $e) {
            error_log('Error al listar modulos de plan SaaS: ' . $e->getMessage());
            return [];
        }
    }

    public function moduloIdsDelPlan($planId) {
        $modulos = $this->listarModulosDelPlan($planId);
        return array_values(array_unique(array_map('intval', array_column($modulos, 'id'))));
    }

    public function actualizarPlanHotel($hotelId, $planId) {
        try {
            $stmt = $this->db->query(
                "UPDATE hoteles
                 SET plan_id = ?, updated_at = NOW()
                 WHERE id = ?",
                [$planId ? (int) $planId : null, (int) $hotelId]
            );

            return $stmt !== false;
        } catch (Throwable $e) {
            error_log('Error al actualizar plan de hotel: ' . $e->getMessage());
            return false;
        }
    }
}
