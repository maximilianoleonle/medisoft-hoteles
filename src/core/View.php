<?php
/**
 * Clase View - Motor de Vistas
 * Los Cedros
 */

class View {
    /**
     * Renderizar una vista
     */
    public static function render($view, $args = []) {
        extract($args, EXTR_SKIP);
        
        $file = APP_PATH . "/views/$view.php";
        
        if (is_readable($file)) {
            require $file;
        } else {
            throw new Exception("Vista $view no encontrada");
        }
    }
    
    /**
     * Renderizar una vista con layout
     */
    public static function renderTemplate($view, $args = []) {
        static $twig = null;
        
        // Por ahora usaremos includes simples de PHP
        // En el futuro se puede implementar Twig si es necesario
        
        // Extraer variables para la vista
        extract($args, EXTR_SKIP);
        
        // Buffer de salida
        ob_start();
        
        // Incluir header si no es una vista de error o login
        if (!in_array($view, ['auth/login', 'errors/404', 'errors/500'])) {
            require APP_PATH . '/views/layout/header.php';
            // El sidebar ya se incluye dentro del header.php, no lo incluimos aquí
        }
        
        // Incluir la vista principal
        $file = APP_PATH . "/views/$view.php";
        if (is_readable($file)) {
            require $file;
        } else {
            throw new Exception("Vista $view no encontrada");
        }
        
        // Incluir footer si no es una vista de error o login
        if (!in_array($view, ['auth/login', 'errors/404', 'errors/500'])) {
            require APP_PATH . '/views/layout/footer.php';
        }
        
        // Obtener y limpiar el buffer
        echo ob_get_clean();
    }
    
    /**
     * Renderizar JSON
     */
    public static function renderJSON($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Incluir un partial
     */
    public static function partial($partial, $args = []) {
        extract($args, EXTR_SKIP);
        
        $file = APP_PATH . "/views/partials/$partial.php";
        
        if (is_readable($file)) {
            require $file;
        } else {
            throw new Exception("Partial $partial no encontrado");
        }
    }
}