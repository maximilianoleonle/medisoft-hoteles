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

        // Vistas standalone: sin layout interno (login, errores y paginas publicas motor/checkin).
        $esVistaStandalone = in_array($view, ['auth/login', 'errors/404', 'errors/500'])
            || strpos($view, 'motor/') === 0
            || strpos($view, 'checkin/') === 0
            || strpos($view, 'encuesta/') === 0;

        // Registrar la vista como "reciente" para navegacion rapida
        // (fire-and-forget: nunca rompe la pagina si falla).
        if (!$esVistaStandalone && function_exists('nav_registrar_visita')) {
            nav_registrar_visita();
        }

        // Incluir header si no es una vista standalone
        if (!$esVistaStandalone) {
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
        
        // Incluir footer si no es una vista standalone
        if (!$esVistaStandalone) {
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