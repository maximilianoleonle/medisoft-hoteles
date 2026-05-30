<?php
/**
 * Modelo para catalogo global de modulos y modulos activos por hotel.
 */

class Modulo extends Model {
    protected $table = 'modulos';
    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'categoria',
        'activo_global',
        'orden',
        'icono',
        'ruta_base'
    ];

    public function listarGlobales($soloActivos = false) {
        $sql = "SELECT id, clave, nombre, descripcion, categoria, activo_global, orden, icono, ruta_base
                FROM {$this->table}";
        $params = [];

        if ($soloActivos) {
            $sql .= " WHERE activo_global = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY orden ASC, nombre ASC";

        return $this->query($sql, $params);
    }

    public function listarParaHotelSaasAdmin($hotelId) {
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
                    hm.id AS hotel_modulo_id,
                    COALESCE(hm.activo, 0) AS activo_hotel,
                    hm.fuente,
                    hm.trial_until,
                    hm.enabled_at,
                    hm.disabled_at
             FROM {$this->table} m
             LEFT JOIN hotel_modulos hm
                ON hm.modulo_id = m.id
               AND hm.hotel_id = ?
             ORDER BY m.orden ASC, m.nombre ASC",
            [(int) $hotelId]
        );
    }

    public function listarActivosDeHotel($hotelId) {
        return $this->query(
            "SELECT m.id, m.clave, m.nombre, m.descripcion, m.categoria, m.icono, m.ruta_base
             FROM hotel_modulos hm
             INNER JOIN {$this->table} m ON m.id = hm.modulo_id
             WHERE hm.hotel_id = ?
               AND hm.activo = 1
               AND m.activo_global = 1
             ORDER BY m.orden ASC, m.nombre ASC",
            [(int) $hotelId]
        );
    }

    public function hotelTieneModulo($hotelId, $clave) {
        $resultado = $this->query(
            "SELECT m.id
             FROM hotel_modulos hm
             INNER JOIN {$this->table} m ON m.id = hm.modulo_id
             WHERE hm.hotel_id = ?
               AND m.clave = ?
               AND hm.activo = 1
               AND m.activo_global = 1
             LIMIT 1",
            [(int) $hotelId, (string) $clave]
        );

        return !empty($resultado);
    }

    public function activarModuloParaHotel($hotelId, $moduloId, $enabledBy = null, $fuente = 'manual') {
        $stmt = $this->db->query(
            "INSERT INTO hotel_modulos
                (hotel_id, modulo_id, activo, fuente, enabled_by, enabled_at, disabled_at, created_at, updated_at)
             VALUES (?, ?, 1, ?, ?, NOW(), NULL, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                activo = 1,
                fuente = VALUES(fuente),
                enabled_by = VALUES(enabled_by),
                enabled_at = NOW(),
                disabled_at = NULL,
                updated_at = NOW()",
            [
                (int) $hotelId,
                (int) $moduloId,
                $fuente,
                $enabledBy ? (int) $enabledBy : null
            ]
        );

        return $stmt !== false;
    }

    public function desactivarModuloParaHotel($hotelId, $moduloId, $enabledBy = null, $fuente = 'manual') {
        $stmt = $this->db->query(
            "INSERT INTO hotel_modulos
                (hotel_id, modulo_id, activo, fuente, enabled_by, enabled_at, disabled_at, created_at, updated_at)
             VALUES (?, ?, 0, ?, ?, NULL, NOW(), NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                activo = 0,
                fuente = VALUES(fuente),
                enabled_by = VALUES(enabled_by),
                disabled_at = NOW(),
                updated_at = NOW()",
            [
                (int) $hotelId,
                (int) $moduloId,
                $fuente,
                $enabledBy ? (int) $enabledBy : null
            ]
        );

        return $stmt !== false;
    }

    public function activarModuloPorClave($hotelId, $clave, $enabledBy = null) {
        $moduloId = $this->obtenerIdPorClave($clave);
        return $moduloId ? $this->activarModuloParaHotel($hotelId, $moduloId, $enabledBy) : false;
    }

    public function desactivarModuloPorClave($hotelId, $clave, $enabledBy = null) {
        $moduloId = $this->obtenerIdPorClave($clave);
        return $moduloId ? $this->desactivarModuloParaHotel($hotelId, $moduloId, $enabledBy) : false;
    }

    public function actualizarModulosHotel($hotelId, array $moduloIdsActivos, $enabledBy = null) {
        $modulos = $this->listarGlobales();
        if (empty($modulos)) {
            return false;
        }

        $idsValidos = array_map('intval', array_column($modulos, 'id'));
        $idsActivos = array_values(array_intersect(array_map('intval', $moduloIdsActivos), $idsValidos));
        $idsActivosLookup = array_flip($idsActivos);

        foreach ($idsValidos as $moduloId) {
            $ok = isset($idsActivosLookup[$moduloId])
                ? $this->activarModuloParaHotel($hotelId, $moduloId, $enabledBy)
                : $this->desactivarModuloParaHotel($hotelId, $moduloId, $enabledBy);

            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    private function obtenerIdPorClave($clave) {
        $resultado = $this->query(
            "SELECT id
             FROM {$this->table}
             WHERE clave = ?
             LIMIT 1",
            [(string) $clave]
        );

        return !empty($resultado) ? (int) $resultado[0]['id'] : null;
    }
}
