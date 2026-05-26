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
            call_user_func_array([$this, $method], $args);
            $this->after();
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
