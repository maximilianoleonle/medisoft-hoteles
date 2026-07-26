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
                        m.tipo_comercial,
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
                   AND m.clave NOT IN ('cuentas_cobrar')
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

        // Al APLICAR un preset solo viajan modulos contratables: los internos
        // Medisoft no se activan/desactivan desde la seleccion comercial (los
        // bloqueados ya quedan fuera por el filtro activo_global de arriba).
        $modulos = array_filter($modulos, static function ($modulo) {
            return (string) ($modulo['tipo_comercial'] ?? Modulo::TIPO_OPCIONAL) !== Modulo::TIPO_INTERNO;
        });

        return array_values(array_unique(array_map('intval', array_column($modulos, 'id'))));
    }

    public function auditarConsistenciaHotel($hotelId) {
        $plan = $this->obtenerActualDeHotel($hotelId);
        $modulosActivos = $this->listarModulosActivosDeHotel($hotelId);

        if (!$plan || empty($plan['id'])) {
            return [
                'estado' => 'sin_plan',
                'plan' => null,
                'modulos_esperados' => [],
                'modulos_activos' => $modulosActivos,
                'modulos_incluidos_apagados' => [],
                'modulos_activos_fuera_plan' => [],
                'mensaje' => 'Este hotel aun no tiene un plan comercial asignado.'
            ];
        }

        if (($plan['clave'] ?? '') === 'personalizado') {
            return [
                'estado' => 'personalizado',
                'plan' => $plan,
                'modulos_esperados' => [],
                'modulos_activos' => $modulosActivos,
                'modulos_incluidos_apagados' => [],
                'modulos_activos_fuera_plan' => [],
                'mensaje' => 'Este hotel usa configuracion manual de modulos.'
            ];
        }

        $modulosEsperados = $this->listarModulosDelPlan((int) $plan['id']);
        $esperadosPorClave = $this->indexarModulosPorClave($modulosEsperados);
        $activosPorClave = $this->indexarModulosPorClave($modulosActivos);

        $incluidosApagados = [];
        foreach ($esperadosPorClave as $clave => $modulo) {
            if (!isset($activosPorClave[$clave])) {
                $incluidosApagados[] = $modulo;
            }
        }

        $activosFueraPlan = [];
        foreach ($activosPorClave as $clave => $modulo) {
            if (!isset($esperadosPorClave[$clave])) {
                $activosFueraPlan[] = $modulo;
            }
        }

        $consistente = empty($incluidosApagados) && empty($activosFueraPlan);

        return [
            'estado' => $consistente ? 'consistente' : 'diferencias',
            'plan' => $plan,
            'modulos_esperados' => $modulosEsperados,
            'modulos_activos' => $modulosActivos,
            'modulos_incluidos_apagados' => $incluidosApagados,
            'modulos_activos_fuera_plan' => $activosFueraPlan,
            'mensaje' => $consistente
                ? 'Los modulos activos coinciden con el preset del plan.'
                : 'Hay diferencias entre el preset comercial y los modulos activos reales.'
        ];
    }

    public function actualizarPreciosPlanes(array $precios) {
        try {
            foreach ($precios as $planId => $precio) {
                $planId = (int) $planId;
                $precio = trim((string) $precio);

                if ($planId <= 0 || !is_numeric($precio) || (float) $precio < 0) {
                    continue;
                }

                $stmt = $this->db->query(
                    "UPDATE {$this->table}
                     SET precio_mensual = ?, updated_at = NOW()
                     WHERE id = ?",
                    [round((float) $precio, 2), $planId]
                );

                if ($stmt === false) {
                    return false;
                }
            }

            return true;
        } catch (Throwable $e) {
            error_log('Error al actualizar precios de planes SaaS: ' . $e->getMessage());
            return false;
        }
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

    private function listarModulosActivosDeHotel($hotelId) {
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
                        m.ruta_base
                 FROM modulos m
                 LEFT JOIN hotel_modulos hm
                    ON hm.modulo_id = m.id
                   AND hm.hotel_id = ?
                 WHERE m.activo_global = 1
                   AND (m.es_core = 1 OR hm.activo = 1)
                 ORDER BY m.orden ASC, m.nombre ASC",
                [(int) $hotelId]
            );
        } catch (Throwable $e) {
            error_log('Error al listar modulos activos para auditoria de plan: ' . $e->getMessage());
            return [];
        }
    }

    private function indexarModulosPorClave(array $modulos) {
        $indexados = [];

        foreach ($modulos as $modulo) {
            if (!empty($modulo['clave'])) {
                $indexados[(string) $modulo['clave']] = $modulo;
            }
        }

        return $indexados;
    }
}
