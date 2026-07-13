<?php
/**
 * Router.php ACTUALIZADO - CORREGIDO
 * Los Cedros
 * 
 * Cambios:
 * - Soporte para formato array Y formato string 'Controller@action'
 * - Middleware de autenticación
 * - Fix para el error "Cannot access offset of type string on string"
 */

class Router {
    private $routes = [];
    private $params = [];
    
    // Rutas que NO requieren autenticación
    private $public_routes = [
        'GET' => [
            '/^\/$/i',                    // Página principal (redirige a login)
            '/^\/login$/i',               // Login
            '/^\/h\/[a-z0-9-]+\/login$/i', // Login scoped por hotel
            '/^\/h\/[a-z0-9-]+\/manifest\.webmanifest$/i', // Manifest scoped por hotel
            '/^\/h\/[a-z0-9-]+\/reservar$/i',              // Motor de reservas publico
            '/^\/h\/[a-z0-9-]+\/reservar\/api\/disponibilidad$/i', // Disponibilidad publica del motor
            '/^\/h\/[a-z0-9-]+\/reservar\/api\/cupon$/i',          // Validacion publica de cupon (promociones)
            '/^\/h\/[a-z0-9-]+\/reservar\/confirmacion\/[a-f0-9]+$/i', // Confirmacion publica de pago del motor
            '/^\/h\/[a-z0-9-]+\/checkin\/[a-f0-9]+$/i',    // Pre-registro publico (check-in digital)
            '/^\/h\/[a-z0-9-]+\/encuesta\/[a-f0-9]+$/i',   // Encuesta post-estancia publica (reputacion)
            '/^\/h\/[a-z0-9-]+\/ical\/[a-f0-9]+\/[0-9]+\.ics$/i', // Feed iCal publico por habitacion (canales_ical)
            '/^\/reportes\/link\/[a-f0-9]+$/i', // Link publico seguro de reporte PDF
            '/^\/health$/i',              // Health check (protegido por HEALTH_TOKEN)
            '/^\/manifest\.json$/i',      // PWA manifest
            '/^\/service-worker\.js$/i',  // Service worker
            '/^\/offline\.html$/i'        // Página offline
        ],
        'POST' => [
            '/^\/h\/[a-z0-9-]+\/login\/authenticate$/i', // Proceso de autenticacion scoped por hotel
            '/^\/login\/authenticate$/i',  // Proceso de autenticación
            '/^\/h\/[a-z0-9-]+\/reservar\/iniciar-pago$/i', // Motor: iniciar pago publico
            '/^\/h\/[a-z0-9-]+\/reservar\/webhook\/[a-z]+$/i', // Motor: webhook de pasarela (verificado por firma)
            '/^\/h\/[a-z0-9-]+\/checkin\/[a-f0-9]+\/completar$/i', // Pre-registro publico: envio del formulario
            '/^\/h\/[a-z0-9-]+\/encuesta\/[a-f0-9]+\/responder$/i', // Encuesta publica: envio de la respuesta
            '/^\/saas\/webhook\/stripe$/i' // Webhook de cobros SaaS (verificado por firma)
        ]
    ];
    
    /**
     * Agregar una ruta GET
     */
    public function get($route, $params = []) {
        $this->add($route, $params, 'GET');
    }
    
    /**
     * Agregar una ruta POST
     */
    public function post($route, $params = []) {
        $this->add($route, $params, 'POST');
    }
    
    /**
     * Agregar ruta al array de rutas
     */
    private function add($route, $params, $method) {
        // Convertir la ruta en expresión regular
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        $route = '/^' . $route . '$/i';
        
        $this->routes[$method][$route] = $params;
    }
    
    /**
     * Verificar si una ruta es pública
     */
    private function isPublicRoute($url, $method) {
        if (!isset($this->public_routes[$method])) {
            return false;
        }
        
        foreach ($this->public_routes[$method] as $public_route) {
            if (preg_match($public_route, $url)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Middleware de autenticación
     */
    private function checkAuthentication($url, $method) {
        // Si es una ruta pública, permitir acceso
        if ($this->isPublicRoute($url, $method)) {
            return true;
        }
        
        // Para todas las demás rutas, verificar autenticación
        $loginPath = function_exists('login_path_for_current_context')
            ? login_path_for_current_context($url)
            : 'login';

        if (!is_authenticated()) {
            // Si es AJAX, retornar JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Sesión expirada. Por favor, inicie sesión nuevamente.',
                    'redirect' => url($loginPath)
                ]);
                exit;
            }
            
            // Para peticiones normales, redirigir al login
            if (!headers_sent()) {
                set_mensaje('Debe iniciar sesión para acceder a esta página', 'error');
                header('Location: ' . url($loginPath), true, 303);
                exit;
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Middleware CSRF global para métodos mutantes.
     *
     * El token llega por campo de formulario o header (csrf_token_from_request);
     * el escudo del navegador (js/csrf-shield.js) lo inyecta automáticamente en
     * XHR, fetch y forms POST, así que un rechazo aquí significa petición forjada,
     * sesión/token expirado o un cliente sin el escudo.
     */
    private function checkCsrf($url, $method) {
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        // Rutas públicas: webhooks verificados por firma y formularios públicos
        // con token efímero en la URL. No dependen de la sesión.
        if ($this->isPublicRoute($url, $method)) {
            return true;
        }

        if (!function_exists('verify_csrf_token')) {
            return true; // Helpers no cargados (contexto CLI/pruebas): no bloquear.
        }

        $token = function_exists('csrf_token_from_request')
            ? csrf_token_from_request()
            : ($_POST['csrf_token'] ?? null);

        if (verify_csrf_token($token)) {
            return true;
        }

        if (function_exists('ms_log')) {
            ms_log('warning', 'CSRF global: token ausente o inválido', [
                'ruta' => $url,
                'metodo' => $method,
            ]);
        }

        $esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $aceptaJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

        if ($esAjax || $aceptaJson) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Tu sesión de seguridad expiró. Recarga la página e inténtalo de nuevo.',
                'csrf' => true,
            ]);
            exit;
        }

        if (function_exists('set_mensaje')) {
            set_mensaje('Tu sesión de seguridad expiró. Vuelve a intentarlo.', 'error');
        }

        $volverA = $_SERVER['HTTP_REFERER'] ?? null;
        $destino = ($volverA && strpos($volverA, ($_SERVER['HTTP_HOST'] ?? '')) !== false)
            ? $volverA
            : (function_exists('url') ? url('dashboard') : '/');

        header('Location: ' . $destino, true, 303);
        exit;
    }

    /**
     * Obtener todas las rutas
     */
    public function getRoutes() {
        return $this->routes;
    }
    
    /**
     * NUEVO: Convertir formato string a array
     * Convierte 'ControllerName@actionName' a ['controller' => 'ControllerName', 'action' => 'actionName']
     */
    private function parseControllerAction($params) {
        // Si ya es un array, retornarlo tal cual
        if (is_array($params)) {
            return $params;
        }
        
        // Si es un string en formato 'Controller@action'
        if (is_string($params) && strpos($params, '@') !== false) {
            list($controller, $action) = explode('@', $params, 2);
            
            // Remover 'Controller' del final si existe
            $controller = preg_replace('/Controller$/', '', $controller);
            
            // Remover 'Action' del final si existe
            $action = preg_replace('/Action$/', '', $action);
            
            return [
                'controller' => $controller,
                'action' => $action
            ];
        }
        
        // Si es un string simple sin @, asumir que es el controlador
        if (is_string($params)) {
            return [
                'controller' => $params,
                'action' => 'index'
            ];
        }
        
        // Fallback: retornar como array vacío
        return [];
    }
    
    /**
     * Verificar si la URL coincide con alguna ruta
     */
    public function match($url) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (!isset($this->routes[$method])) {
            return false;
        }
        
        foreach ($this->routes[$method] as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                // CORREGIDO: Convertir params a array si es string
                $params = $this->parseControllerAction($params);
                
                // Procesar parámetros capturados de la URL
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }
                
                $this->params = $params;
                return true;
            }
        }
        return false;
    }
    
    /**
     * Obtener los parámetros de la ruta actual
     */
    public function getParams() {
        return $this->params;
    }
    
    /**
     * Despachar la ruta - ejecutar el controlador y acción
     */
    public function dispatch($url) {
        // Eliminar query string
        $url = $this->removeQueryStringVariables($url);
        
        // Eliminar slash inicial si existe
        $url = ltrim($url, '/');
        
        // Eliminar slash final si existe
        $url = rtrim($url, '/');
        
        // Ruta vacía = home
        if ($url == '') {
            $url = '/';
        } else {
            $url = '/' . $url;
        }
        
        // Verificar autenticación ANTES de despachar
        $method = $_SERVER['REQUEST_METHOD'];
        if (!$this->checkAuthentication($url, $method)) {
            return; // Ya se manejó la redirección en checkAuthentication
        }

        // CSRF global: toda petición mutante autenticada debe traer token válido.
        // Las rutas públicas quedan exentas (webhooks con firma propia y formularios
        // públicos protegidos por token efímero en la URL). Los controladores que ya
        // llaman validateCSRF() siguen funcionando igual: validar dos veces es inocuo.
        if (!$this->checkCsrf($url, $method)) {
            return; // Ya se respondió el rechazo en checkCsrf
        }
        
        if ($this->match($url)) {
            // CORREGIDO: Verificar que params es un array antes de acceder
            if (!is_array($this->params)) {
                throw new Exception("Error en configuración de rutas: los parámetros deben ser un array");
            }
            
            // Verificar que existen las claves necesarias
            if (!isset($this->params['controller'])) {
                throw new Exception("Error en configuración de rutas: falta el parámetro 'controller'");
            }
            
            if (!isset($this->params['action'])) {
                throw new Exception("Error en configuración de rutas: falta el parámetro 'action'");
            }
            
            $controller = $this->params['controller'];
            $controller = $this->convertToStudlyCaps($controller);
            $controller = $controller . 'Controller';
            
            if (class_exists($controller)) {
                $controller_object = new $controller($this->params);
                
                $action = $this->params['action'];
                $action = $this->convertToCamelCase($action);
                
                if (method_exists($controller_object, $action . 'Action')) {
                    // El método existe con Action al final
                    $action = $action . 'Action';
                    
                    // Eliminar controller y action del array de params
                    unset($this->params['controller']);
                    unset($this->params['action']);
                    
                    // Llamar al método directamente
                    $controller_object->runAction($action, array_values($this->params));
                } elseif (method_exists($controller_object, '__call')) {
                    // Usar el método mágico __call
                    unset($this->params['controller']);
                    unset($this->params['action']);
                    
                    $controller_object->$action(...array_values($this->params));
                } else {
                    throw new Exception("Método $action no encontrado en controlador $controller");
                }
            } else {
                throw new Exception("Controlador $controller no encontrado");
            }
        } else {
            // Ruta no encontrada
            $this->notFound();
        }
    }
    
    /**
     * Convertir string a StudlyCaps
     */
    private function convertToStudlyCaps($string) {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
    
    /**
     * Convertir string a camelCase
     */
    private function convertToCamelCase($string) {
        return lcfirst($this->convertToStudlyCaps($string));
    }
    
    /**
     * Remover variables de query string de la URL
     */
    private function removeQueryStringVariables($url) {
        if ($url != '') {
            $parts = explode('?', $url, 2);
            return $parts[0];
        }
        return $url;
    }
    
    /**
     * Página no encontrada
     */
    private function notFound() {
        // También verificar autenticación para páginas 404
        if (!$this->isPublicRoute($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'])) {
            $loginPath = function_exists('login_path_for_current_context')
                ? login_path_for_current_context($_SERVER['REQUEST_URI'] ?? null)
                : 'login';

            if (!is_authenticated()) {
                set_mensaje('Debe iniciar sesión para acceder a esta página', 'error');
                header('Location: ' . url($loginPath), true, 303);
                exit;
            }
        }
        
        http_response_code(404);
        View::render('errors/404', [
            'title' => 'Página no encontrada'
        ]);
        exit;
    }
}
