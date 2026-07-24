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
     * Claves de roles base que no se le muestran al hotel (config/permisos.php).
     * Se siguen sembrando y can() los resuelve igual: solo desaparecen de las
     * pantallas (listado de roles y selector de rol de una persona).
     */
    public function clavesOcultas() {
        $cfg = $this->config();
        $claves = $cfg['roles_ocultos'] ?? [];

        return is_array($claves) ? array_values(array_filter(array_map('strval', $claves))) : [];
    }

    /** ¿Esta clave de rol esta oculta de la interfaz? */
    public function estaOculto($clave) {
        return in_array((string) $clave, $this->clavesOcultas(), true);
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

        // Los roles base ocultos no se le pintan al hotel en ninguna pantalla.
        $ocultas = $this->clavesOcultas();
        if ($ocultas) {
            $sql .= " AND r.clave NOT IN (" . implode(',', array_fill(0, count($ocultas), '?')) . ")";
            $params = array_merge($params, $ocultas);
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
     * Las personas que tienen cada rol
     * ------------------------------------------------------------------- */

    /**
     * Todas las personas del hotel agrupadas por rol configurable:
     * [role_id => [fila, fila, ...]]. UNA sola consulta (el listado de roles
     * pinta a su gente, y N tarjetas no pueden ser N consultas).
     *
     * Quien no tiene rol configurable (datos legacy) queda fuera de este mapa;
     * para esos esta usuariosSinRol().
     */
    public function usuariosPorRolDeHotel($hotelId) {
        $filas = $this->query(
            "SELECT hu.role_id,
                    hu.usuario_id,
                    hu.activo,
                    hu.es_principal,
                    hu.permisos_json,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.ultimo_login
             FROM hotel_usuarios hu
             INNER JOIN usuarios u ON u.id = hu.usuario_id
             WHERE hu.hotel_id = ? AND hu.role_id IS NOT NULL
             ORDER BY hu.activo DESC, u.nombre_completo ASC, u.nombre_usuario ASC",
            [(int) $hotelId]
        );

        $mapa = [];

        foreach ($filas as $fila) {
            $fila['ajustes'] = function_exists('normalizar_ajustes_permisos')
                ? normalizar_ajustes_permisos($fila['permisos_json'] ?? null)
                : null;
            unset($fila['permisos_json']);

            $mapa[(int) $fila['role_id']][] = $fila;
        }

        return $mapa;
    }

    /**
     * Personas del hotel SIN rol configurable asignado. Caen a permisos
     * antiguos por su rol-string, asi que el panel de roles las señala aparte
     * en vez de esconderlas.
     */
    public function usuariosSinRol($hotelId) {
        return $this->query(
            "SELECT hu.usuario_id,
                    hu.rol,
                    hu.activo,
                    u.nombre_completo,
                    u.nombre_usuario
             FROM hotel_usuarios hu
             INNER JOIN usuarios u ON u.id = hu.usuario_id
             WHERE hu.hotel_id = ? AND hu.role_id IS NULL
             ORDER BY hu.activo DESC, u.nombre_completo ASC",
            [(int) $hotelId]
        );
    }

    /**
     * Una persona del hotel con su rol configurable resuelto. Devuelve null si
     * no pertenece al hotel (cinturon multi-tenant de la pantalla por persona).
     */
    public function usuarioDelHotel($hotelId, $usuarioId) {
        $filas = $this->query(
            "SELECT hu.usuario_id,
                    hu.role_id,
                    hu.rol AS rol_legacy,
                    hu.activo,
                    hu.es_principal,
                    hu.permisos_json,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.email
             FROM hotel_usuarios hu
             INNER JOIN usuarios u ON u.id = hu.usuario_id
             WHERE hu.hotel_id = ? AND hu.usuario_id = ?
             LIMIT 1",
            [(int) $hotelId, (int) $usuarioId]
        );

        if (empty($filas)) {
            return null;
        }

        $fila = $filas[0];
        $fila['ajustes'] = function_exists('normalizar_ajustes_permisos')
            ? normalizar_ajustes_permisos($fila['permisos_json'] ?? null)
            : null;

        return $fila;
    }

    /**
     * Traduce lo que quedo MARCADO en la matriz de una persona a la diferencia
     * contra su rol. FUNCION PURA (probada en RolPermisosPersonaTest).
     *
     * $ambito acota la comparacion a los permisos que la pantalla realmente
     * mostro: un permiso fuera del catalogo (p. ej. el legacy 'llaves.control')
     * jamas se pierde por no haber tenido casilla que marcar.
     *
     * @return array{extra: string[], quitados: string[]}
     */
    public static function derivarAjustes(array $permisosRol, array $marcados, array $ambito) {
        $extra = [];
        $quitados = [];

        foreach ($ambito as $permiso) {
            $loDaElRol = permission_in_list($permiso, $permisosRol);

            // Para QUITAR se mira lo marcado en sentido amplio: si viene el
            // control total del area ('<modulo>.all'), sus hijos cuentan como
            // marcados aunque la pantalla no los haya enviado (van bloqueados).
            // Sin esto, poner el control total quitaria todo el detalle.
            $cubierto = permission_in_list($permiso, $marcados);

            // Para DAR se mira solo lo enviado de verdad: asi 'extra' guarda la
            // clave total sola en vez de repetir sus hijos uno por uno.
            $marcadoLiteral = in_array($permiso, $marcados, true);

            if ($marcadoLiteral && !$loDaElRol) {
                $extra[] = $permiso;
            } elseif (!$cubierto && $loDaElRol) {
                $quitados[] = $permiso;
            }
        }

        return ['extra' => $extra, 'quitados' => $quitados];
    }

    /**
     * Guarda (o borra, con null) los ajustes personales de permisos de una
     * persona. El arreglo llega ya normalizado y saneado por el controlador.
     *
     * @param array{extra: string[], quitados: string[]}|null $ajustes
     */
    public function guardarAjustesUsuario($hotelId, $usuarioId, $ajustes) {
        $json = null;

        if (is_array($ajustes) && (!empty($ajustes['extra']) || !empty($ajustes['quitados']))) {
            $json = json_encode(
                [
                    'extra' => array_values($ajustes['extra'] ?? []),
                    'quitados' => array_values($ajustes['quitados'] ?? []),
                ],
                JSON_UNESCAPED_UNICODE
            );
        }

        $stmt = $this->db->query(
            "UPDATE hotel_usuarios
             SET permisos_json = ?, updated_at = NOW()
             WHERE hotel_id = ? AND usuario_id = ?",
            [$json, (int) $hotelId, (int) $usuarioId]
        );

        return $stmt !== false;
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
