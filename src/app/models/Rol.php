<?php
/**
 * Modelo de roles configurables por hotel.
 *
 * Cada hotel tiene su propio conjunto de roles (clonados de los presets de
 * config/permisos.php al sembrarse). Los roles con es_sistema = 1 son los
 * roles base (no se pueden eliminar, pero si reconfigurar sus permisos).
 *
 * La resolucion de permisos (permisosDeRol) la consume can() en auth.php.
 */

class Rol extends Model {
    protected $table = 'roles';
    protected $fillable = [
        'hotel_id',
        'clave',
        'nombre',
        'descripcion',
        'es_sistema',
        'activo',
        'permisos_json',
    ];

    /* ---------------------------------------------------------------------
     * Catalogo y presets (config/permisos.php)
     * ------------------------------------------------------------------- */

    private function config() {
        static $cfg = null;

        if ($cfg === null) {
            $base = defined('CONFIG_PATH') ? CONFIG_PATH : __DIR__ . '/../../config';
            $path = $base . '/permisos.php';
            $cfg = is_readable($path) ? (require $path) : ['catalogo' => [], 'presets' => []];
        }

        return $cfg;
    }

    public function catalogo() {
        $cfg = $this->config();
        return $cfg['catalogo'] ?? [];
    }

    public function presets() {
        $cfg = $this->config();
        return $cfg['presets'] ?? [];
    }

    /**
     * Lista plana de todas las claves de permiso validas (catalogo + legacy).
     */
    public function permisosValidos() {
        static $claves = null;

        if ($claves !== null) {
            return $claves;
        }

        $claves = [];
        foreach ($this->catalogo() as $grupo) {
            foreach (array_keys($grupo['permisos'] ?? []) as $perm) {
                $claves[] = $perm;
            }
        }

        // Permisos legacy que no viven en un grupo del catalogo.
        $claves[] = 'llaves.control';

        $claves = array_values(array_unique($claves));
        return $claves;
    }

    /**
     * Filtra una lista de permisos dejando solo los validos. '*' colapsa a
     * acceso total.
     */
    public function sanitizarPermisos(array $permisos) {
        $permisos = array_values(array_unique(array_filter(array_map('strval', $permisos))));

        if (in_array('*', $permisos, true)) {
            return ['*'];
        }

        return array_values(array_intersect($permisos, $this->permisosValidos()));
    }

    /* ---------------------------------------------------------------------
     * Lectura
     * ------------------------------------------------------------------- */

    public function listarPorHotel($hotelId, $soloActivos = false) {
        $sql = "SELECT r.*,
                       (SELECT COUNT(*) FROM hotel_usuarios hu WHERE hu.role_id = r.id) AS usuarios_count
                FROM {$this->table} r
                WHERE r.hotel_id = ?";
        $params = [(int) $hotelId];

        if ($soloActivos) {
            $sql .= " AND r.activo = 1";
        }

        $sql .= " ORDER BY r.es_sistema DESC, r.nombre ASC";

        return $this->query($sql, $params);
    }

    public function obtenerPorId($id, $hotelId = null) {
        if ($hotelId !== null) {
            $rows = $this->query(
                "SELECT * FROM {$this->table} WHERE id = ? AND hotel_id = ? LIMIT 1",
                [(int) $id, (int) $hotelId]
            );
        } else {
            $rows = $this->query(
                "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1",
                [(int) $id]
            );
        }

        return !empty($rows) ? $rows[0] : null;
    }

    public function obtenerPorClave($hotelId, $clave) {
        $rows = $this->query(
            "SELECT * FROM {$this->table} WHERE hotel_id = ? AND clave = ? LIMIT 1",
            [(int) $hotelId, (string) $clave]
        );

        return !empty($rows) ? $rows[0] : null;
    }

    /**
     * Permisos concedidos a un rol como arreglo. Cacheado por request: lo
     * consume can() en cada verificacion de permisos.
     */
    public function permisosDeRol($roleId) {
        $roleId = (int) $roleId;

        if ($roleId <= 0) {
            return [];
        }

        static $cache = [];

        if (array_key_exists($roleId, $cache)) {
            return $cache[$roleId];
        }

        // Capa APCu (60s, compartida entre requests) sobre el cache estatico:
        // can() consulta esto en cada pagina de cada usuario. Editar el rol
        // la invalida al instante (actualizarRol); el TTL corto es la red.
        $consultar = function () use ($roleId) {
            $rows = $this->query(
                "SELECT permisos_json FROM {$this->table} WHERE id = ? AND activo = 1 LIMIT 1",
                [$roleId]
            );

            if (empty($rows)) {
                return [];
            }

            $permisos = json_decode($rows[0]['permisos_json'] ?? '[]', true);
            return is_array($permisos) ? $permisos : [];
        };

        $cache[$roleId] = function_exists('ms_cache_remember')
            ? ms_cache_remember('rol_permisos_' . $roleId, 60, $consultar)
            : $consultar();

        return $cache[$roleId];
    }

    public function contarUsuarios($roleId) {
        $rows = $this->query(
            "SELECT COUNT(*) AS total FROM hotel_usuarios WHERE role_id = ?",
            [(int) $roleId]
        );

        return !empty($rows) ? (int) $rows[0]['total'] : 0;
    }

    /* ---------------------------------------------------------------------
     * Siembra de presets
     * ------------------------------------------------------------------- */

    /**
     * Crea (o repara) los roles base de un hotel a partir de los presets.
     * Idempotente: no pisa ediciones del dueno salvo $sobrescribir = true.
     */
    public function sembrarPresetsParaHotel($hotelId, $sobrescribir = false) {
        $hotelId = (int) $hotelId;

        if ($hotelId <= 0) {
            return false;
        }

        $presets = $this->presets();

        if (empty($presets)) {
            return false;
        }

        $ownTransaction = !$this->db->enTransaccion();

        try {
            if ($ownTransaction) {
                $this->db->safeBeginTransaction();
            }

            foreach ($presets as $clave => $preset) {
                $permisosJson = json_encode(
                    array_values($preset['permisos'] ?? []),
                    JSON_UNESCAPED_UNICODE
                );
                $existente = $this->obtenerPorClave($hotelId, $clave);

                if ($existente) {
                    if ($sobrescribir) {
                        $this->db->query(
                            "UPDATE {$this->table}
                             SET nombre = ?, descripcion = ?, permisos_json = ?, updated_at = NOW()
                             WHERE id = ?",
                            [
                                $preset['nombre'] ?? $clave,
                                $preset['descripcion'] ?? null,
                                $permisosJson,
                                (int) $existente['id'],
                            ]
                        );
                    }
                    continue;
                }

                $this->db->query(
                    "INSERT INTO {$this->table}
                        (hotel_id, clave, nombre, descripcion, es_sistema, activo, permisos_json, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())",
                    [
                        $hotelId,
                        (string) $clave,
                        $preset['nombre'] ?? $clave,
                        $preset['descripcion'] ?? null,
                        (int) ($preset['es_sistema'] ?? 1),
                        $permisosJson,
                    ]
                );
            }

            if ($ownTransaction) {
                $this->db->safeCommit();
            }

            return true;
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->safeRollBack();
            }

            error_log('Error al sembrar presets de roles para hotel ' . $hotelId . ': ' . $e->getMessage());
            return false;
        }
    }

    /* ---------------------------------------------------------------------
     * Escritura (la UI de gestion se conecta en la Fase 3)
     * ------------------------------------------------------------------- */

    public function crearParaHotel($hotelId, array $data) {
        $hotelId = (int) $hotelId;
        $nombre = trim($data['nombre'] ?? '');

        if ($hotelId <= 0 || $nombre === '') {
            return false;
        }

        $clave = $this->generarClaveUnica($hotelId, $data['clave'] ?? $nombre);
        $permisosJson = json_encode($this->sanitizarPermisos($data['permisos'] ?? []), JSON_UNESCAPED_UNICODE);

        $stmt = $this->db->query(
            "INSERT INTO {$this->table}
                (hotel_id, clave, nombre, descripcion, es_sistema, activo, permisos_json, created_at, updated_at)
             VALUES (?, ?, ?, ?, 0, ?, ?, NOW(), NOW())",
            [
                $hotelId,
                $clave,
                $nombre,
                trim($data['descripcion'] ?? '') ?: null,
                (int) (($data['activo'] ?? 1) ? 1 : 0),
                $permisosJson,
            ]
        );

        return $stmt !== false ? (int) $this->db->lastInsertId() : false;
    }

    public function actualizarRol($id, $hotelId, array $data) {
        $rol = $this->obtenerPorId($id, $hotelId);

        if (!$rol) {
            return false;
        }

        $campos = [];
        $params = [];

        // El nombre/descripcion de roles de sistema se puede ajustar; la clave no.
        if (isset($data['nombre']) && trim($data['nombre']) !== '') {
            $campos[] = 'nombre = ?';
            $params[] = trim($data['nombre']);
        }

        if (array_key_exists('descripcion', $data)) {
            $campos[] = 'descripcion = ?';
            $params[] = trim($data['descripcion']) ?: null;
        }

        if (array_key_exists('permisos', $data)) {
            $campos[] = 'permisos_json = ?';
            $params[] = json_encode($this->sanitizarPermisos($data['permisos']), JSON_UNESCAPED_UNICODE);
        }

        if (array_key_exists('activo', $data) && empty($rol['es_sistema'])) {
            $campos[] = 'activo = ?';
            $params[] = (int) ($data['activo'] ? 1 : 0);
        }

        if (empty($campos)) {
            return true;
        }

        $campos[] = 'updated_at = NOW()';
        $params[] = (int) $rol['id'];

        $stmt = $this->db->query(
            "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE id = ?",
            $params
        );

        // Invalida el cache APCu de permisos del rol (lo consume can()).
        if (function_exists('ms_cache_forget')) {
            ms_cache_forget('rol_permisos_' . (int) $rol['id']);
        }

        return $stmt !== false;
    }

    /**
     * Elimina un rol custom del hotel. No permite borrar roles de sistema ni
     * roles con usuarios asignados (el llamador debe reasignar antes).
     */
    public function eliminar($id, $hotelId) {
        $rol = $this->obtenerPorId($id, $hotelId);

        if (!$rol || !empty($rol['es_sistema'])) {
            return false;
        }

        if ($this->contarUsuarios((int) $rol['id']) > 0) {
            return false;
        }

        $stmt = $this->db->query(
            "DELETE FROM {$this->table} WHERE id = ? AND hotel_id = ? AND es_sistema = 0",
            [(int) $rol['id'], (int) $hotelId]
        );

        // Invalida el cache APCu de permisos del rol (lo consume can()).
        if (function_exists('ms_cache_forget')) {
            ms_cache_forget('rol_permisos_' . (int) $rol['id']);
        }

        return $stmt !== false;
    }

    private function generarClaveUnica($hotelId, $base) {
        $slug = strtolower(trim((string) $base));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim($slug, '_');

        if ($slug === '') {
            $slug = 'rol';
        }

        $slug = substr($slug, 0, 50);
        $candidato = $slug;
        $i = 2;

        while ($this->obtenerPorClave($hotelId, $candidato)) {
            $candidato = substr($slug, 0, 47) . '_' . $i;
            $i++;
        }

        return $candidato;
    }
}
