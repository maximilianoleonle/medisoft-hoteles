<?php
/**
 * Controlador Base
 * Los Cedros
 */

abstract class Controller {
    /**
     * Parámetros de la ruta
     */
    protected $route_params = [];
    
    /**
     * Constructor
     */
    public function __construct($route_params) {
        $this->route_params = $route_params;
    }
    
    /**
     * Magic method para manejar llamadas a métodos no existentes
     */
    public function __call($name, $args) {
        $method = $name . 'Action';
        
        if (method_exists($this, $method)) {
            $this->runAction($method, $args);
        } else {
            throw new Exception("Método $method no encontrado en controlador " . get_class($this));
        }
    }
    
    /**
     * Filtro antes de ejecutar la acción
     */
    /**
     * Ejecutar una accion pasando siempre por filtros before/after.
     */
    public function runAction($method, array $args = []) {
        if (!method_exists($this, $method)) {
            throw new Exception("Metodo $method no encontrado en controlador " . get_class($this));
        }

        if ($this->before() !== false) {
            // Bitacora (bloque auditoria): registra el POST autenticado ANTES de
            // ejecutarlo. Best-effort: un fallo aqui jamas bloquea la accion.
            $this->registrarAuditoria($method);
            call_user_func_array([$this, $method], $args);
            $this->after();
        }
    }

    /**
     * Hook central de la bitacora de auditoria (bloque auditoria).
     * Solo registra acciones POST de usuarios autenticados con contexto de
     * hotel y el bloque contratado. APPEND-ONLY sobre auditoria_eventos;
     * no guarda el cuerpo del POST (puede traer datos sensibles), solo
     * quien, que ruta y sobre que id.
     */
    private function registrarAuditoria($method) {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                return;
            }

            $usuarioId = function_exists('user_id') ? (int) user_id() : 0;
            if ($usuarioId <= 0) {
                return; // publico (motor, checkin, encuesta) o sin sesion
            }

            $hotelId = function_exists('obtenerHotelIdActualCompat') ? (int) obtenerHotelIdActualCompat() : 0;
            if ($hotelId <= 0) {
                return;
            }

            if (!function_exists('hotel_has_module') || !hotel_has_module('auditoria', $hotelId)) {
                return;
            }

            $ruta = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
            if ($ruta === '' || strpos($ruta, '/api/') === 0) {
                return; // endpoints de polling: ruido, no auditoria
            }

            // Primer parametro numerico de la ruta = id de la entidad afectada.
            $entidadId = null;
            foreach ($this->route_params as $valor) {
                if (is_numeric($valor)) {
                    $entidadId = (int) $valor;
                    break;
                }
            }

            $modulo = preg_replace('/Controller$/', '', get_class($this));
            $accion = preg_replace('/Action$/', '', (string) $method);
            $nombre = function_exists('user_name') ? (string) user_name() : '';

            Database::getInstance()->query(
                "INSERT INTO auditoria_eventos
                    (hotel_id, usuario_id, usuario_nombre, modulo, accion, ruta, entidad_id, ip, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $hotelId,
                    $usuarioId,
                    $nombre !== '' ? mb_substr($nombre, 0, 150) : null,
                    mb_substr($modulo, 0, 60),
                    mb_substr($accion, 0, 60),
                    mb_substr($ruta, 0, 255),
                    $entidadId,
                    substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
                ]
            );
        } catch (Throwable $e) {
            error_log('Auditoria: no se pudo registrar evento (no critico): ' . $e->getMessage());
        }
    }

    protected function before() {
        // Se puede sobrescribir en los controladores hijos
        return true;
    }
    
    /**
     * Filtro después de ejecutar la acción
     */
    protected function after() {
        // Se puede sobrescribir en los controladores hijos
    }
    
    /**
     * Redirigir a otra URL
     */
    protected function redirect($url) {
        header('Location: ' . url($url), true, 303);
        exit;
    }
    
    /**
     * Verificar si es una petición POST
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Verificar si es una petición GET
     */
    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /**
     * Verificar si es una petición AJAX
     */
    protected function isAjax() {
        return is_ajax();
    }
    
    /**
     * Obtener datos POST
     */
    protected function getPost($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return post($key, $default);
    }
    
    /**
     * Obtener datos GET
     */
    protected function getQuery($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return get($key, $default);
    }
    
    /**
     * Verificar token CSRF
     */
    protected function validateCSRF() {
        if ($this->isPost()) {
            $token = function_exists('csrf_token_from_request')
                ? csrf_token_from_request()
                : $this->getPost('csrf_token');

            if (!verify_csrf_token($token)) {
                if ($this->isAjax()) {
                    View::renderJSON([
                        'success' => false,
                        'message' => 'Token de seguridad inválido'
                    ], 403);
                } else {
                    set_mensaje('Token de seguridad inválido. Por favor, intente nuevamente.', 'error');
                    $this->redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
                }
            }
        }
    }
    
    /**
     * Requerir autenticación
     */
    protected function requireAuth() {
        require_auth();
    }
    
    /**
     * Requerir rol específico
     */
    protected function requireRole($role) {
        require_role($role);
    }
    
    /**
     * Requerir permiso específico
     */
    protected function requirePermission($permission) {
        require_permission($permission);
    }
}
