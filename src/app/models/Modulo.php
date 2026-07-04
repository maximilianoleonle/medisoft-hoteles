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
        'es_core',
        'precio_mensual',
        'activo_global',
        'orden',
        'icono',
        'ruta_base'
    ];

    public function listarGlobales($soloActivos = false) {
        $sql = "SELECT id, clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base
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
                    m.es_core,
                    m.precio_mensual,
                    m.activo_global,
                    m.orden,
                    m.icono,
                    m.ruta_base,
                    hm.id AS hotel_modulo_id,
                    CASE WHEN m.es_core = 1 THEN 1 ELSE COALESCE(hm.activo, 0) END AS activo_hotel,
                    hm.precio_override,
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
        // Los modulos core (paquete basico) siempre estan activos, exista o no fila en hotel_modulos.
        return $this->query(
            "SELECT m.id, m.clave, m.nombre, m.descripcion, m.categoria, m.es_core, m.precio_mensual, m.icono, m.ruta_base
             FROM {$this->table} m
             LEFT JOIN hotel_modulos hm
                ON hm.modulo_id = m.id
               AND hm.hotel_id = ?
             WHERE m.activo_global = 1
               AND (m.es_core = 1 OR hm.activo = 1)
             ORDER BY m.orden ASC, m.nombre ASC",
            [(int) $hotelId]
        );
    }

    public function hotelTieneModulo($hotelId, $clave) {
        $resultado = $this->query(
            "SELECT m.id
             FROM {$this->table} m
             LEFT JOIN hotel_modulos hm
                ON hm.modulo_id = m.id
               AND hm.hotel_id = ?
             WHERE m.clave = ?
               AND m.activo_global = 1
               AND (m.es_core = 1 OR hm.activo = 1)
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

    public function actualizarModulosHotel($hotelId, array $moduloIdsActivos, $enabledBy = null, array $preciosOverride = []) {
        $hotelId = (int) $hotelId;

        if ($hotelId <= 0) {
            return false;
        }

        $modulos = $this->listarGlobales();
        if (empty($modulos)) {
            return false;
        }

        $idsValidos = array_map('intval', array_column($modulos, 'id'));
        $idsActivos = array_values(array_intersect(array_map('intval', $moduloIdsActivos), $idsValidos));

        // Los modulos core (paquete basico) no se pueden desactivar.
        foreach ($modulos as $modulo) {
            if (!empty($modulo['es_core'])) {
                $idsActivos[] = (int) $modulo['id'];
            }
        }
        $idsActivos = array_values(array_unique($idsActivos));

        $idsActivosLookup = array_flip($idsActivos);
        $ownTransaction = !$this->db->enTransaccion();

        try {
            if ($ownTransaction) {
                $this->db->safeBeginTransaction();
            }

            foreach ($idsValidos as $moduloId) {
                $ok = isset($idsActivosLookup[$moduloId])
                    ? $this->activarModuloParaHotel($hotelId, $moduloId, $enabledBy)
                    : $this->desactivarModuloParaHotel($hotelId, $moduloId, $enabledBy);

                if (!$ok) {
                    throw new Exception('No se pudo actualizar el modulo ' . $moduloId . ' para el hotel.');
                }
            }

            if (!empty($preciosOverride)) {
                $this->aplicarPreciosOverride($hotelId, $preciosOverride, $idsValidos);
            }

            if ($this->seleccionIncluyeModuloCaja($modulos, $idsActivosLookup)) {
                $cajaModel = new Caja();

                if (!$cajaModel->ensureDefaultCajaForHotel($hotelId)) {
                    throw new Exception('No se pudo asegurar la caja inicial del hotel.');
                }
            }

            if ($ownTransaction) {
                $this->db->safeCommit();
            }

            return true;
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->safeRollBack();
            }

            error_log('Error al actualizar modulos del hotel ' . $hotelId . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Overrides de precio por hotel. Valor '' o null limpia el override
     * (vuelve al precio de catalogo); un numero >= 0 lo fija.
     */
    private function aplicarPreciosOverride($hotelId, array $preciosOverride, array $idsValidos) {
        $idsValidosLookup = array_flip($idsValidos);

        foreach ($preciosOverride as $moduloId => $precio) {
            $moduloId = (int) $moduloId;

            if (!isset($idsValidosLookup[$moduloId])) {
                continue;
            }

            $precio = trim((string) $precio);
            $valor = ($precio === '' || !is_numeric($precio) || (float) $precio < 0)
                ? null
                : round((float) $precio, 2);

            $stmt = $this->db->query(
                "UPDATE hotel_modulos
                 SET precio_override = ?, updated_at = NOW()
                 WHERE hotel_id = ? AND modulo_id = ?",
                [$valor, $hotelId, $moduloId]
            );

            if ($stmt === false) {
                throw new Exception('No se pudo guardar el precio del modulo ' . $moduloId . ' para el hotel.');
            }
        }
    }

    /**
     * Edicion de precios del catalogo global desde el panel SaaS.
     * Los modulos core mantienen precio 0 (incluidos en el paquete basico).
     */
    public function actualizarPreciosCatalogo(array $precios) {
        try {
            foreach ($precios as $moduloId => $precio) {
                $moduloId = (int) $moduloId;
                $precio = trim((string) $precio);

                if ($moduloId <= 0 || !is_numeric($precio) || (float) $precio < 0) {
                    continue;
                }

                $stmt = $this->db->query(
                    "UPDATE {$this->table}
                     SET precio_mensual = ?, updated_at = NOW()
                     WHERE id = ? AND es_core = 0",
                    [round((float) $precio, 2), $moduloId]
                );

                if ($stmt === false) {
                    return false;
                }
            }

            return true;
        } catch (Throwable $e) {
            error_log('Error al actualizar precios de catalogo de modulos: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cobro mensual estimado del hotel: paquete basico + bloques opcionales activos.
     * El precio aplicado por modulo es precio_override (si existe) o el de catalogo.
     */
    public function resumenCobroMensual($hotelId, $precioBase = null) {
        $hotelId = (int) $hotelId;

        try {
            $modulos = $this->query(
                "SELECT m.id, m.clave, m.nombre, m.precio_mensual, hm.precio_override,
                        COALESCE(hm.precio_override, m.precio_mensual) AS precio_aplicado
                 FROM {$this->table} m
                 INNER JOIN hotel_modulos hm
                    ON hm.modulo_id = m.id
                   AND hm.hotel_id = ?
                 WHERE m.activo_global = 1
                   AND m.es_core = 0
                   AND hm.activo = 1
                 ORDER BY m.orden ASC, m.nombre ASC",
                [$hotelId]
            );
        } catch (Throwable $e) {
            error_log('Error al calcular resumen de cobro mensual del hotel: ' . $e->getMessage());
            return null;
        }

        if ($precioBase === null) {
            try {
                $base = $this->query("SELECT precio_mensual FROM planes WHERE clave = 'basico' LIMIT 1");
                $precioBase = isset($base[0]['precio_mensual']) ? (float) $base[0]['precio_mensual'] : 0.0;
            } catch (Throwable $e) {
                $precioBase = 0.0;
            }
        }

        $totalModulos = 0.0;
        foreach ($modulos as $modulo) {
            $totalModulos += (float) $modulo['precio_aplicado'];
        }

        return [
            'precio_base' => (float) $precioBase,
            'modulos' => $modulos,
            'total_modulos' => $totalModulos,
            'total' => (float) $precioBase + $totalModulos
        ];
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

    private function seleccionIncluyeModuloCaja(array $modulos, array $idsActivosLookup) {
        foreach ($modulos as $modulo) {
            if (($modulo['clave'] ?? '') === 'caja' && isset($idsActivosLookup[(int) $modulo['id']])) {
                return true;
            }
        }

        return false;
    }
}
